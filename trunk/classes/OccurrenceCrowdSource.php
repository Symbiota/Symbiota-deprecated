<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class OccurrenceCrowdSource {

	private $conn;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getTopScores(){
		$retArr = array(); 
		$sql = 'SELECT CONCAT_WS(u.firstname,u.lastname) as user, sum(q.points) AS toppoints '.
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
		$sql = 'SELECT CONCAT_WS(":",c.institutioncode,c.collectioncode) as collcode, c.collectionname, '.
			'q.reviewstatus, COUNT(q.occid) AS cnt, sum(q.points) AS points '.
			'FROM omcrowdsourcequeue q INNER JOIN omcrowdsourcecentral csc ON q.omcsid = csc.omcsid '.
			'INNER JOIN omcollections c ON csc.collid = c.collid '.
			'GROUP BY q.reviewstatus,q.uidprocessor '.
			'HAVING q.uidprocessor = '.$symbUid.' AND q.reviewstatus IN(5,10) '.
			'ORDER BY c.institutioncode,c.collectioncode,q.reviewstatus';
		$rs = $this->conn->query($sql);
		$pPoints = 0;
		$aPoints = 0;
		$totalCnt = 0;
		while($r = $rs->fetch_object()){
			$rStat = $r->reviewstatus;
			$retArr[$r->collname]['cnt'][$rStat] = $r->cnt;
			$retArr[$r->collname]['points'][$rStat] = $r->points;
			if($rPoints==10){
				$pPoints += $r->points;
			}
			else{
				$aPoints += $r->points;
			}
			$totalCnt += $r->cnt;
		}
		$retArr['ppoints'] = $pPoints;
		$retArr['apoints'] = $aPoints;
		$retArr['totalcnt'] = $totalCnt;
		$rs->free();
		return $retArr;
	}

	public function getReviewArr($symbUid,$startIndex=0,$limit=500,$returnAll=0){
		if($startIndex<0) $startIndex = 0;
		$retArr = array();
		//Get total record count
		$sql = 'SELECT COUNT(occid) AS cnt '.
			'FROM omcrowdsourcequeue '.
			'WHERE uidprocessor = '.$symbUid.' ';
		if($returnAll) $sql .= 'AND q.reviewstatus = 5 '.
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$retArr['totalcnt'] = $r->cnt;
		}
		$rs->free();
		
		$sql = 'SELECT CONCAT_WS(":",c.institutioncode,c.collectioncode) as collcode, c.collectionname, '.
			'q.occid, q.reviewstatus, q.points, q.notes, q.initialtimestamp '.
			'FROM omcrowdsourcequeue q INNER JOIN omcrowdsourcecentral csc ON q.omcsid = csc.omcsid '.
			'INNER JOIN omcollections c ON csc.collid = c.collid '.
			'WHERE q.uidprocessor = '.$symbUid.' ';
		if($returnAll) $sql .= 'AND q.reviewstatus = 5 '.
		$sql .= 'ORDER BY c.institutioncode,c.collectioncode,q.initialtimestamp DESC'.
			'LIMIT '.$startIndex.','.$limit;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collcode]['name'] = $r->collectionname;
			$retArr[$r->collcode][$r->occid]['pts'] = $r->points;
			$retArr[$r->collcode][$r->occid]['rs'] = $r->reviewstatus;
			$retArr[$r->collcode][$r->occid]['n'] = $r->notes;
			$retArr[$r->collcode][$r->occid]['ts'] = $r->initialtimestamp;
		}
		$rs->free();
		return $retArr;
	}
	
	public function getCollArr(){
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

	public function getCollDetails($collId){
		$retArr = array();
		$sql = 'SELECT CONCAT_WS(":",c.institutioncode,c.collectioncode) as collcode, c.collectionname '.
			'csc.instructions, csc.trainingurl '.
			'FROM omcrowdsourcecentral csc INNER JOIN omcollections c ON csc.collid = c.collid '.
			'WHERE c.collid = '.$collId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['name'] = $r->collectionname.' ('.$r->collcode.')';
			$retArr['instr'] = $r->instructions;
			$retArr['url'] = $r->trainingurl;
		}
		$rs->free();
		return $retArr;
	}

	public function getQueueArr($collId){
		$retArr = array();
		$sql = 'SELECT o.occid,  '.
			'csc.instructions, csc.trainingurl '.
			'FROM omcrowdsourcequeue q INNER JOIN omoccurrences o ON q.occid = o.occid '.
			'WHERE q.reviewstatus = 0 '.
			'ORDER BY c.collectionname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid] = $r->collectionname.' ('.$r->collcode.')';
		}
		$rs->free();
		return $retArr;
	}
}
?>