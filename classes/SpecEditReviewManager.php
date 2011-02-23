<?php
/*
 * Built 26 Jan 2011
 * By E.E. Gilbert
 */
include_once($serverRoot.'/config/dbconnection.php');

class SpecEditReviewManager {

	private $conn;
	private $collId;

	function __construct($id) {
		if($id){
			$this->collId = $id;
		}
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getEditArr($aStatus, $rStatus){
		if(!$this->collId) return;
		$retArr = Array();
		$sql = 'SELECT e.ocedid,e.occid,e.fieldname,e.fieldvaluenew,e.fieldvalueold,e.reviewstatus,e.appliedstatus,CONCAT_WS(" ",u.firstname,u.lastname) AS username '.
			'FROM omoccuredits e INNER JOIN omoccurrences o ON e.occid = o.occid '.
			'INNER JOIN users u ON e.uid = u.uid '.
			'WHERE o.collid = '.$this->collId;
		if($aStatus) $sql .= ' AND e.appliedstatus = '.$aStatus.' ';
		if($rStatus) $sql .= ' AND e.reviewstatus = '.$rStatus.' ';
		$sql .= ' ORDER BY e.fieldname ASC, e.initialtimestamp DESC';
		//echo '<div>'.$sql.'</div>';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$ocedid = $r->ocedid;
			$occId = $r->occid;
			$retArr[$occid][$ocedid]['fname'] = $r->fieldname;
			$retArr[$occid][$ocedid]['fvalueold'] = $r->fieldvallueold;
			$retArr[$occid][$ocedid]['fvaluenew'] = $r->fieldvaluenew;
			$retArr[$occid][$ocedid]['rstatus'] = $r->reviewstatus;
			$retArr[$occid][$ocedid]['astatus'] = $r->appliedstatus;
			$retArr[$occid][$ocedid]['uname'] = $r->username;
		}
		return $retArr;
	}

	public function getCollectionList(){
		global $isAdmin, $userRights;
		$returnArr = Array();
		if($isAdmin || array_key_exists("CollAdmin",$userRights)){
			$sql = 'SELECT DISTINCT c.collid, c.collectionname '.
				'FROM omcollections c '.
				'WHERE colltype LIKE "%specimens%" ';
			if(array_key_exists('CollAdmin',$userRights)){
				$sql .= 'AND c.collid IN('.implode(',',$userRights['CollAdmin']).') '; 
			}
			$sql .= 'ORDER BY c.collectionname';
			//echo $sql;
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$returnArr[$row->collid] = $row->collectionname;
			}
			$result->close();
		}
		return $returnArr;
	}

	protected function cleanStr($str){
		$str = str_replace('"','',$str);
		return $str;
	}
}
?>
 