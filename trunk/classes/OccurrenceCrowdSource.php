<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class OccurrenceCrowdSource {

	private $conn;
	private $collid;
	private $omcsid;
	private $headArr = Array();

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
		$this->headArr = array('catalogNumber','family','sciname','identifiedBy','dateIdentified','recordedBy','recordNumber',
			'associatedCollectors','eventDate','verbatimEventDate','country','stateProvince','county','locality',
			'decimalLatitude','decimalLongitude','coordinateUncertaintyInMeters','verbatimCoordinates','minimumElevationInMeters',
			'maximumElevationInMeters','verbatimElevation','habitat','reproductiveCondition','substrate','occurrenceRemarks',
			'processingstatus','dateLastModified');
	}

	function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
	}

	//Functions used in controlpanel.php
	public function getProjectDetails(){
		$retArr = array();
		//Currently returns one first CS project associated with collection
		//we could support multiple CS projects per collection each with different instructions, training, and data entry personnel  
		if($this->collid){
			$sql = 'SELECT CONCAT_WS(":",c.institutioncode,c.collectioncode) AS collcode, c.collectionname, '.
				'csc.omcsid, csc.instructions, csc.trainingurl '.
				'FROM omcollections c LEFT JOIN omcrowdsourcecentral csc ON c.collid = csc.collid '.
				'WHERE c.collid = '.$this->collid;
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retArr['name'] = $r->collectionname.' ('.$r->collcode.')';
				$retArr['instr'] = $r->instructions;
				$retArr['url'] = $r->trainingurl;
				$retArr['omcsid'] = $r->omcsid;
				$this->omcsid = $r->omcsid;
			}
			$rs->free();
		}
		return $retArr;
	}
	
	public function editProject($omcsid,$instr,$url){
		$statusStr = '';
		if(is_numeric($omcsid)){
			$sql = 'UPDATE omcrowdsourcecentral '.
				'SET instructions = '.($instr?'"'.$this->cleanInStr($instr).'"':'NULL').',trainingurl = '.($url?'"'.$this->cleanInStr($url).'"':'NULL').
				' WHERE omcsid = '.$omcsid;
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR editing project: '.$this->conn->error;
			}
		}
		return $statusStr;
	}
	
	public function createProject($cid,$instr,$url){
		$statusStr = '';
		if(is_numeric($cid)){
			$sql = 'INSERT INTO omcrowdsourcecentral(collid,instructions,trainingurl) '.
				'VALUES('.$cid.','.($instr?'"'.$this->cleanInStr($instr).'"':'NULL').','.($url?'"'.$this->cleanInStr($url).'"':'NULL').')';
			//echo $sql;
			if($this->conn->query($sql)){
				$this->omcsid = $this->conn->insert_id;
			}
			else{
				$statusStr = 'ERROR editing project: '.$this->conn->error;
			}
		}
		return $statusStr;
	}
	
	public function getProjectStats(){
		$retArr = array();
		if($this->collid){
			//Get review status total counts
			$sql = 'SELECT q.reviewstatus, count(q.occid) as cnt '.
				'FROM omcrowdsourcequeue q INNER JOIN omcrowdsourcecentral c ON q.omcsid = c.omcsid '.
				'WHERE c.collid = '.$this->collid.' '.
				'GROUP BY q.reviewstatus';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['rs'][$r->reviewstatus] = $r->cnt;
			}
			$rs->free();
			
			//Get record count for those available for adding to queue
			$sql = 'SELECT count(o.occid) as cnt '.
				'FROM omoccurrences o LEFT JOIN omcrowdsourcequeue q ON o.occid = q.occid '.
				'WHERE o.collid = '.$this->collid.' AND o.processingstatus = "unprocessed" AND q.occid IS NULL ';
			$toAddCnt = 0;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$toAddCnt = $r->cnt;
			}
			$rs->free();
			$retArr['rs']['toadd'] = $toAddCnt;
			
			//Processing scores by user
			$sql = 'SELECT CONCAT_WS(", ", u.lastname, u.firstname) as username, u.uid, sum(IFNULL(q.points,0)) as usersum '.
				'FROM omcrowdsourcequeue q INNER JOIN omcrowdsourcecentral c ON q.omcsid = c.omcsid '.
				'INNER JOIN users u ON q.uidprocessor = u.uid '.
				'WHERE c.collid = '.$this->collid.' GROUP BY username ORDER BY usersum DESC ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['ps'][$r->uid]['score'] = $r->usersum;
				$retArr['ps'][$r->uid]['name'] = $r->username;
			}
			$rs->free();

			//Processing counts by user
			$sql = 'SELECT q.uidprocessor, q.reviewstatus, count(q.occid) as cnt '.
				'FROM omcrowdsourcequeue q INNER JOIN omcrowdsourcecentral c ON q.omcsid = c.omcsid '.
				'WHERE c.collid = '.$this->collid.' AND q.uidprocessor IS NOT NULL '.
				'GROUP BY q.uidprocessor, q.reviewstatus';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['ps'][$r->uidprocessor][$r->reviewstatus] = $r->cnt;
			}
			$rs->free();
		}
		return $retArr;
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
			'q.reviewstatus, COUNT(q.occid) AS cnt, SUM(IFNULL(q.points,0)) AS points '.
			'FROM omcrowdsourcequeue q INNER JOIN omcrowdsourcecentral csc ON q.omcsid = csc.omcsid '.
			'INNER JOIN omcollections c ON csc.collid = c.collid '.
			'GROUP BY c.collid,q.reviewstatus,q.uidprocessor '.
			'HAVING (q.uidprocessor = '.$symbUid.') '.
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
			if($r->reviewstatus==10){
				$aPoints += $r->points;
			}
			elseif($r->reviewstatus==5){
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

	public function addToQueue($variableMap = null){
		$statusStr = 'SUCCESS: specimens added to queue';
		if(!$this->omcsid) return 'ERROR adding to queue, omcsid is null';
		if(!$this->collid) return 'ERROR adding to queue, collid is null';
		$sql = 'INSERT INTO omcrowdsourcequeue(occid, omcsid) '.
			'SELECT o.occid, '.$this->omcsid.' AS csid '.
			'FROM omoccurrences o LEFT JOIN omcrowdsourcequeue q ON o.occid = q.occid '.
			'WHERE o.collid = '.$this->collid.' AND q.occid IS NULL AND o.processingstatus = "unprocessed" AND o.locality IS NULL ';
		if($variableMap){
			$sqlVar = '';
			foreach($variableMap as $k => $v){
				$sqlVar .= 'AND '.$k.' = "'.$v.'" ';
			}
			$sql .= $sqlVar;
		}
		if(!$this->conn->query($sql)){
			$statusStr = 'ERROR adding to queue: '.$this->conn->error;
			$statusStr .= '; SQL: '.$sql;
		}
		return $statusStr;
	}

	//Reveiw functions
	public function getReviewArr($startIndex,$limit,$uid,$rStatus){
		$retArr = array();
		if($this->collid || $uid){
			$sql = 'FROM omcrowdsourcequeue q INNER JOIN omcrowdsourcecentral csc ON q.omcsid = csc.omcsid '.
				'INNER JOIN omoccurrences o ON q.occid = o.occid '.
				'WHERE q.reviewstatus IN('.$rStatus.') ';
			if($this->collid){
				$sql .= 'AND csc.collid = '.$this->collid.' ';
			}
			if($uid){
				$sql .= 'AND (q.uidprocessor = '.$uid.') ';
			}
			$sql .= 'ORDER BY o.datelastmodified DESC '.
				'LIMIT '.$startIndex.','.$limit;
			//Get occurrence records
			$sqlRec = 'SELECT o.occid, '.implode(', ',$this->headArr).', q.uidprocessor, q.reviewstatus, q.points, q.notes '.$sql;
			//echo $sqlRec;
			$rs = $this->conn->query($sqlRec);
			$headerArr = array();
			while($r = $rs->fetch_assoc()){
				$retArr[$r['occid']] = $r;
				//Collection fields that have a value in at least on record
				foreach($r as $field => $value){
					if($value && !in_array($field, $headerArr)) $headerArr[] = $field;
				}
			}
			$rs->free();
			//Remove fields from $this->headArr that are not in $headerArr
			$this->headArr = array_intersect($this->headArr,$headerArr); 
			
			//Get count
			$sqlCnt = 'SELECT COUNT(o.occid) AS cnt '.$sql;
			//echo $sqlCnt;
			$rs = $this->conn->query($sqlCnt);
			if($row = $rs->fetch_object()){
				$retArr['totalcnt'] = $row->cnt;
			}
		}
		else{
			echo "ERROR: both collid and user id are null";
		}
		return $retArr;
	}
	
	public function submitReviews($occidArr, $pointsArr, $commentsArr){
		$statusStr = '';
		foreach($occidArr as $k => $v){
			$sql = 'UPDATE omcrowdsourcequeue SET points = '.($pointsArr[$k]?$pointsArr[$k]:'NULL').
				',notes = '.($commentsArr[$k]?'"'.$this->cleanInStr($commentsArr[$k]).'"':'NULL').
				',reviewstatus = 10 '.
				'WHERE occid = '.$v;
			if($this->conn->query($sql)){
				//Change status to reviewed
				$sql2 = 'UPDATE omoccurrences SET processingstatus = "reviewed" WHERE occid = '.$v;
				$this->conn->query($sql2);
			}
			else{
				$statusStr = 'ERROR submitting reviews; '.$this->conn->error.'<br/>SQL = '.$sql;
			}
		}
		return $statusStr;
	}

	//Setters and Getters and General functions
	public function setCollid($id){
		if($id && is_numeric($id)){
			$this->collid = $id;
			if(!$this->omcsid){
				//set omcsid
				$sql = 'SELECT omcsid FROM omcrowdsourcecentral WHERE collid = '.$this->collid;
				//echo $sql;
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$this->omcsid = $r->omcsid;
				}
				$rs->free();
			}
		}
	}

	public function setOmcsid($id){
		if($id && is_numeric($id)){
			$this->omcsid = $id;
		}
	}
	
	public function getOmcsid(){
		return $this->omcsid;
	}

	public function getEditorList(){
		$retArr = array();
		if($this->collid){
			$sql = 'SELECT DISTINCT u.uid, CONCAT_WS(", ", u.lastname, u.firstname) as user '.
				'FROM omcrowdsourcequeue q INNER JOIN users u ON q.uidprocessor = u.uid '.
				'INNER JOIN omcrowdsourcecentral c ON q.omcsid = c.omcsid '.
				'WHERE c.collid = '.$this->collid.' '.
				'ORDER BY u.lastname, u.firstname';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->uid] = $r->user;
			}
			$rs->free();
		}
		return $retArr;
	}

/*
	public function getProcessingStatusList(){
		$retArr = array();
		$sql = 'SELECT DISTINCT o.processingstatus '.
			'FROM omcrowdsourcequeue q INNER JOIN omoccurrences o ON q.occid = o.occid '.
			'INNER JOIN omcrowdsourcecentral c ON q.omcsid = c.omcsid '.
			'WHERE (o.processingstatus IS NOT NULL) ';
		if($this->collid){
			$sql .= 'AND (c.collid = '.$this->collid.') ';
		}
		else{
			$sql .= 'AND (q.uidprocessor = '.$this->symbUid.') ';
		}
		$sql .= 'ORDER BY o.processingstatus';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[] = $r->processingstatus;
		}
		$rs->free();
		return $retArr;
	}
*/

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	public function getHeaderArr(){
    	return $this->headArr;
    }
}
?>