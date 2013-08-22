<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class OccurrenceCrowdSource {

	private $conn;
	private $collid;
	private $omcsid;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
	}
	
	public function getTopScores(){
		$retArr = array(); 
		$sql = 'SELECT CONCAT_WS(", ",u.lastname,u.firstname) as user, sum(q.points) AS toppoints '.
			'FROM omcrowdsourcequeue q INNER JOIN users u ON q.uidprocessor = u.uid '.
			'GROUP BY firstname,u.lastname '.
			'ORDER BY sum(q.points) DESC '.
			'LIMIT 10';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->toppoints] = $r->user;
		}
		$rs->free();
		return $retArr;
	}

	public function getUserStats($symbUid){
		$retArr = array();
		$sql = 'SELECT c.collid, CONCAT_WS(":",c.institutioncode,c.collectioncode) as collcode, c.collectionname, '.
			'q.reviewstatus, IFNULL(COUNT(q.occid),0) AS cnt, IFNULL(SUM(q.points),0) AS points '.
			'FROM omcrowdsourcequeue q INNER JOIN omcrowdsourcecentral csc ON q.omcsid = csc.omcsid '.
			'INNER JOIN omcollections c ON csc.collid = c.collid '.
			'GROUP BY c.collid,q.reviewstatus,q.uidprocessor '.
			'HAVING (q.uidprocessor = '.$symbUid.' OR q.uidprocessor IS NULL) '.
			'ORDER BY c.institutioncode,c.collectioncode,q.reviewstatus';
		//echo $sql;
		$rs = $this->conn->query($sql);
		$pPoints = 0;
		$aPoints = 0;
		$totalCnt = 0;
		while($r = $rs->fetch_object()){
			$retArr[$r->collid]['name'] = $r->collectionname.' ('.$r->collcode.')';
			$retArr[$r->collid]['cnt'][$r->reviewstatus] = $r->cnt;
			$retArr[$r->collid]['points'][$r->reviewstatus] = $r->points;
			if($r->points==10){
				$aPoints += $r->points;
			}
			elseif($r->points==5){
				$pPoints += $r->points;
			}
			if($r->reviewstatus > 0) $totalCnt += $r->cnt;
		}
		$retArr['ppoints'] = $pPoints;
		$retArr['apoints'] = $aPoints;
		$retArr['totalcnt'] = $totalCnt;
		$rs->free();
		return $retArr;
	}

	//Functions used in controlpanel.php
	public function getProjectDetails(){
		$retArr = array();
		if($this->collid){
			$sql = 'SELECT CONCAT_WS(":",c.institutioncode,c.collectioncode) as collcode, c.collectionname, '.
				'csc.omcsid, csc.instructions, csc.trainingurl '.
				'FROM omcrowdsourcecentral csc INNER JOIN omcollections c ON csc.collid = c.collid '.
				'WHERE c.collid = '.$this->collid;
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['name'] = $r->collectionname.' ('.$r->collcode.')';
				$retArr['instr'] = $r->instructions;
				$retArr['url'] = $r->trainingurl;
				$retArr['omcsid'] = $r->omcsid;
				$this->omcsid = $r->omcsid;
			}
			$rs->free();
			//Get review status total counts
			$sql = 'SELECT o.processingstatus, count(q.occid) as cnt '.
				'FROM omcrowdsourcequeue q INNER JOIN omcrowdsourcecentral c ON q.omcsid = c.omcsid '.
				'INNER JOIN omoccurrences o ON q.occid = o.occid '.
				'WHERE c.collid = '.$this->collid.' GROUP BY o.processingstatus';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['rs'][$r->processingstatus] = $r->cnt;
			}
			$rs->free();
			//Get record count for those available for adding to queue
			$sql = 'SELECT count(o.occid) as cnt '.
				'FROM omoccurrences o LEFT JOIN omcrowdsourcequeue q ON o.occid = q.occid '.
				'WHERE o.collid = '.$this->collid.' AND o.processingstatus = "unprocessed" AND q.occid IS NULL ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retArr['rs']['toadd'] = $r->cnt;
			}
			$rs->free();
			//Processing scores by user
			$sql = 'SELECT CONCAT_WS(", ", u.lastname, u.firstname) as username, u.uid, sum(q.points) as usersum '.
				'FROM omcrowdsourcequeue q INNER JOIN omcrowdsourcecentral c ON q.omcsid = c.omcsid '.
				'INNER JOIN users u ON q.uidprocessor = u.uid '.
				'WHERE c.collid = '.$this->collid.' GROUP BY username ORDER BY usersum DESC ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['ps'][$r->username]['score'] = $r->usersum;
				$retArr['ps'][$r->username]['uid'] = $r->uid;
			}
			$rs->free();
			//Processing counts by user
			$sql = 'SELECT CONCAT_WS(", ", u.lastname, u.firstname) as username, o.processingstatus, count(q.occid) as cnt '.
				'FROM omcrowdsourcequeue q INNER JOIN omcrowdsourcecentral c ON q.omcsid = c.omcsid '.
				'INNER JOIN users u ON q.uidprocessor = u.uid '.
				'INNER JOIN omoccurrences o ON q.occid = o.occid '.
				'WHERE c.collid = '.$this->collid.' GROUP BY username, o.processingstatus';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['ps'][$r->username][$r->processingstatus] = $r->cnt;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getReviewArr($startIndex,$limit,$uid,$pStatus){
		$retArr = array();
		if($this->collid){
			$pStatusStr = '"'.$pStatus.'"';
			if($pStatus == "reviewed") $pStatusStr = '"reviewed","closed"';
			if($pStatus == "pending") $pStatusStr = '"pending review"';
			$sqlFrag = 'FROM omcrowdsourcequeue q INNER JOIN omcrowdsourcecentral csc ON q.omcsid = csc.omcsid '.
				'INNER JOIN userlogin u ON q.uidprocessor = u.uid '.
				'INNER JOIN omoccurrences o ON q.occid = o.occid '.
				'WHERE csc.collid = '.$this->collid.'  AND o.processingstatus IN('.$pStatusStr.') ';
			if($uid) $sqlFrag .= 'AND q.uidprocessor = '.$uid.' ';
			$sql = 'SELECT o.*, u.username, q.uidprocessor, q.points, q.notes '.
				$sqlFrag.'ORDER BY o.datelastmodified DESC '.
				'LIMIT '.$startIndex.','.$limit;
			//echo $sql;
			$rs = $this->conn->query($sql);
			$recArr = array();
			$headerArr = array();
			while($r = $rs->fetch_assoc()){
				$recArr[$r['occid']] = $r;
				//Collection fields that have a value in at least on record
				foreach($r as $field => $value){
					if($value && !array_key_exists($field, $headerArr)) $headerArr[$field] = '';
				}
			}
			$rs->free();
			//Remove fields that are not relavent to Crowd Source review
			unset($headerArr['occid']);
			unset($headerArr['collid']);
			unset($headerArr['basisOfRecord']);
			unset($headerArr['scientificName']);
			unset($headerArr['tidinterpreted']);
			unset($headerArr['scientificNameAuthorship']);
			unset($headerArr['year']);
			unset($headerArr['month']);
			unset($headerArr['day']);
			unset($headerArr['startDayOfYear']);
			unset($headerArr['endDayOfYear']);
			unset($headerArr['uidprocessor']);
			
			//Limit record array to only fields in headerArr (fields with a value in at least one record)
			foreach($recArr as $k => $occArr){
				$retArr[$k] = array_intersect_key($occArr,$headerArr);
			}
			$retArr['header'] = $headerArr;
			 
			//Get count if array size is equal to limit
			if(count($retArr) <= $limit){
				$retArr['totalcnt'] = count($retArr);
			}
			else{
				$sql = 'SELECT count(q.occid) AS cnt '.$sqlFrag;
				$rs = $this->conn->query($sql);
				$retArr['totalcnt'] = $rs->cnt;
			}
		}
		return $retArr;
	}

	public function addToQueue($variableMap = null){
		$statusStr = 'SUCCESS: specimens added to queue';
		$sql = 'INSERT INTO omcrowdsourcequeue(occid, omcsid) '.
			'SELECT o.occid, '.$this->omcsid.' AS csid '.
			'FROM omoccurrences o LEFT JOIN omcrowdsourcequeue q ON o.occid = q.occid '.
			'WHERE o.collid = '.$this->collid.' AND q.occid IS NULL AND processingstatus = "unprocessed" AND locality IS NULL ';
		if($variableMap){
			$sqlVar = '';
			foreach($variableMap as $k => $v){
				$sqlVar .= 'AND '.$k.' = "'.$v.'" ';
			}
			$sql .= $sqlVar;
		}
		if(!$this->conn->query($sql)){
			$statusStr = 'ERROR adding to queue: '.$this->conn->error;
		}
		return $statusStr;
	}
	
	//General functions
	public function getCollArr_old(){
		$retArr = array();
		$sql = 'SELECT c.collid, CONCAT_WS(":",c.institutioncode,c.collectioncode) as collcode, c.collectionname '.
			'FROM omcrowdsourcequeue q INNER JOIN omcrowdsourcecentral csc ON q.omcsid = csc.omcsid '.
			'INNER JOIN omcollections c ON csc.collid = c.collid '.
			'WHERE q.reviewstatus = 0 '.
			'ORDER BY c.collectionname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid] = $r->collectionname.' ('.$r->collcode.')';
		}
		$rs->free();
		return $retArr;
	}

	public function setCollid($id){
		if($id && is_numeric($id)){
			$this->collid = $id;
		}
	}
}
?>