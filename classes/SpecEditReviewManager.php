<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class SpecEditReviewManager {

	private $conn;
	private $collId;
	private $collAcronym;

	private $sqlBase;
	private $appliedStatusFilter = '';
	private $reviewStatusFilter;
	private $editorUidFilter;
	private $queryOccidFilter;
	private $pageNumber = 0;
	private $limitNumber;

	function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}
	
	public function setCollId($id){
		if(is_numeric($id)){
			$this->collId = $id;
			$sql = 'SELECT collectionname, institutioncode, collectioncode FROM omcollections WHERE (collid = '.$id.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$collName = $r->collectionname.' (';
				$this->collAcronym = $r->institutioncode;
				$collName .= $r->institutioncode;
				if($r->collectioncode){
					$collName .= ':'.$r->collectioncode;
					$this->collAcronym .= ':'.$r->collectioncode;
				}
				$collName .= ')';
			}
			$rs->free();
		}
		return $collName;
	}

	public function getRecCnt(){
		if(!$this->sqlBase) $this->setSqlBase();
		$sql = 'SELECT COUNT(e.ocedid) AS fullcnt '.$this->sqlBase;
		//echo $sql; exit;
		$rsCnt = $this->conn->query($sql);
		if($rCnt = $rsCnt->fetch_object()){
			$recCnt = $rCnt->fullcnt;
		}
		$rsCnt->free();
		return $recCnt;
	}

	public function getEditArr(){
		if(!$this->sqlBase) $this->setSqlBase();
		$retArr = Array();
		if($this->sqlBase){
			//Grab records
			$sql = 'SELECT e.ocedid,e.occid,o.catalognumber,e.fieldname,e.fieldvaluenew,e.fieldvalueold,e.reviewstatus,e.appliedstatus,'.
				'CONCAT_WS(", ",u.lastname,u.firstname) AS username, e.initialtimestamp '.
				$this->sqlBase.'ORDER BY e.initialtimestamp DESC, e.fieldname ASC '.
				'LIMIT '.($this->pageNumber*$this->limitNumber).','.($this->limitNumber+1);
			//echo '<div>'.$sql.'</div>';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$ocedid = $r->ocedid;
				$occId = $r->occid;
				$retArr[$occId][$ocedid]['catnum'] = $r->catalognumber;
				$retArr[$occId][$ocedid]['fname'] = $r->fieldname;
				$retArr[$occId][$ocedid]['fvalueold'] = $r->fieldvalueold;
				$retArr[$occId][$ocedid]['fvaluenew'] = $r->fieldvaluenew;
				$retArr[$occId][$ocedid]['rstatus'] = $r->reviewstatus;
				$retArr[$occId][$ocedid]['astatus'] = $r->appliedstatus;
				$retArr[$occId][$ocedid]['uname'] = $r->username;
				$retArr[$occId][$ocedid]['tstamp'] = $r->initialtimestamp;
			}
			$rs->free();
		}
		return $retArr;
	}
	
	private function setSqlBase(){
		//Build SQL WHERE fragment
		if($this->collId){
			$this->sqlBase = 'FROM omoccuredits e INNER JOIN omoccurrences o ON e.occid = o.occid '.
				'INNER JOIN users u ON e.uid = u.uid '.
				'WHERE (o.collid = '.$this->collId.') ';
			if($this->appliedStatusFilter !== ''){
				$this->sqlBase .= 'AND (e.appliedstatus = '.$this->appliedStatusFilter.') ';
			}
			if($this->reviewStatusFilter){
				$this->sqlBase .= 'AND (e.reviewstatus IN('.$this->reviewStatusFilter.')) ';
			}
			if($this->editorUidFilter){
				$this->sqlBase .= 'AND (e.uid = '.$this->editorUidFilter.') ';
			}
			if($this->queryOccidFilter){
				$this->sqlBase .= 'AND (e.occid = '.$this->queryOccidFilter.') ';
			}
		}
	}
	
	public function applyAction($reqArr){
		if(!array_key_exists('ocedid',$reqArr)) return;
		$statusStr = 'SUCCESS: ';
		$ocedidStr = implode(',',$reqArr['ocedid']);
		$applyTask = $reqArr['applytask'];
		if($ocedidStr){
			if($applyTask == 'apply'){
				//Apply edits with applied status = 0
				$sql = 'SELECT occid, fieldname, fieldvaluenew '.
					'FROM omoccuredits WHERE appliedstatus = 0 AND (ocedid IN('.$ocedidStr.'))';
				$rs = $this->conn->query($sql);
				$eCnt=0;$oCnt=0;$lastOccid = 0;
				while($r = $rs->fetch_object()){
					$uSql = 'UPDATE omoccurrences SET '.$r->fieldname.' = "'.$r->fieldvaluenew.'" WHERE (occid = '.$r->occid.')';
					//echo '<div>'.$uSql.'</div>';
					$this->conn->query($uSql);
					$eCnt++;
					if($r->occid != $lastOccid) $oCnt++;
				}
				$rs->free();
				$statusStr .= $eCnt.' edits applied to '.$oCnt.' specimen records';
			}
			else{
				//Revert edits with applied status = 1
				$sql = 'SELECT occid, fieldname, fieldvalueold '.
					'FROM omoccuredits WHERE appliedstatus = 1 AND (ocedid IN('.$ocedidStr.'))';
				$rs = $this->conn->query($sql);
				$oCnt=0;$lastOccid = 0;
				while($r = $rs->fetch_object()){
					$uSql = 'UPDATE omoccurrences SET '.$r->fieldname.' = "'.$r->fieldvalueold.'" WHERE (occid = '.$r->occid.')';
					//echo '<div>'.$uSql.'</div>';
					$this->conn->query($uSql);
					if($r->occid != $lastOccid) $oCnt++;
				}
				$rs->free();
				$statusStr .= $oCnt.' specimen records reverted to previous values';
			}
			//Change status
			$sql = 'UPDATE omoccuredits SET appliedstatus = '.($applyTask=='apply'?1:0);
			if($reqArr['rstatus']){
				$sql .= ',reviewstatus = '.$reqArr['rstatus'];
			}
			$sql .= ' WHERE (ocedid IN('.$ocedidStr.'))';
			//echo '<div>'.$sql.'</div>'; exit;
			$this->conn->query($sql);
		}
		return $statusStr;
	}

	public function deleteEdits($postArr){
		if(!array_key_exists('ocedid',$postArr)) return;
		$ocedidArr = $postArr['ocedid'];
		$sql = 'DELETE FROM omoccuredits WHERE (ocedid IN('.implode(',',$ocedidArr).'))';
		//echo '<div>'.$sql.'</div>';
		if($this->conn->query($sql)){
			return 'SUCCESS: Selected records deleted'; 
		}
		return 0;
	}
	
	public function downloadRecords($reqArr){
		if(!array_key_exists('ocedid',$reqArr)) return;
		$ocedidArr = $reqArr['ocedid'];
		//Initiate file
    	$fileName = $this->collAcronym.'SpecimenEdits_'.time().".csv";
    	header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header ('Content-Type: text/csv');
		header ("Content-Disposition: attachment; filename=\"$fileName\""); 
		//Get Records
		$sql = 'SELECT e.ocedid,e.occid,e.dbpk, e.fieldname,e.fieldvaluenew,e.fieldvalueold,e.reviewstatus,e.appliedstatus,'.
			'CONCAT_WS(", ",u.lastname,u.firstname) AS username '.
			'FROM omoccuredits e INNER JOIN omoccurrences o ON e.occid = o.occid '.
			'INNER JOIN users u ON e.uid = u.uid '.
			'WHERE (o.collid = '.$this->collId.') AND (ocedid IN('.implode(',',$ocedidArr).')) '.
			'ORDER BY e.fieldname ASC, e.initialtimestamp DESC';
		//echo '<div>'.$sql.'</div>';
		$rs = $this->conn->query($sql);
		if($rs){
			echo "EditId,\"RecordNumber\",\"dbpk\",\"FieldName\",\"NewValue\",\"OldValue\",\"ReviewStatus\",\"AppliedStatus\",\"UserName\"\n";
			while($r = $rs->fetch_assoc()){
				$reviewStr = '';
				if($r['reviewstatus'] == 1){
					$reviewStr = 'OPEN';
				}
				elseif($r['reviewstatus'] == 2){
					$reviewStr = 'PENDING';
				}
				elseif($r['reviewstatus'] == 3){
					$reviewStr = 'CLOSED';
				}
				echo $r['ocedid'].",".$r['occid'].",\"".$r['dbpk']."\",\"".$r['fieldname']."\",\"".$r['fieldvaluenew']."\",\"".$r['fieldvalueold']."\",\"".
				$reviewStr."\",\"".($r['appliedstatus']?"APPLIED":"NOT APPLIED")."\",\"".$r['username']."\"\n";
			}
			$rs->free();
		}
		else{
			echo "Recordset is empty.\n";
		}
	}

	public function exportCsvFile(){
		$sql = 'SELECT e.ocedid,e.occid,o.dbpk,o.catalognumber,e.fieldname,e.fieldvaluenew,e.fieldvalueold,'.
			'CASE e.reviewstatus WHEN 1 THEN "OPEN" WHEN 2 THEN "PENDING" WHEN 3 THEN "CLOSED" ELSE "UNKNOWN" END AS reviewstatus,'.
			'CASE e.appliedstatus WHEN 1 THEN "APPLIED" ELSE "NOT APPLIED" END AS appliedstatus,'.
			'CONCAT_WS(", ",u.lastname,u.firstname) AS username, e.initialtimestamp '.
			'FROM omoccuredits e INNER JOIN omoccurrences o ON e.occid = o.occid '.
			'INNER JOIN users u ON e.uid = u.uid '.
			'WHERE (o.collid = '.$this->collId.') AND e.reviewstatus <> 3';
		
		if($sql){
	    	$fileName = 'edited_recordset_'.date('Ymd').".csv";
			header ('Content-Type: text/csv');
			header ("Content-Disposition: attachment; filename=\"$fileName\""); 
			
			$rs = $this->conn->query($sql);
			if($rs){
				echo "PortalID,\"SourcePK\",\"CatalogNumber\",\"EditedFieldName\",\"OldValue\",\"NewValue\",\"ReviewStatus\",".
					"\"AppliedStatus\",\"EditorName\",\"DateEdited\"\n";
				
				while($row = $rs->fetch_assoc()){
					echo $row['occid'].",\"".$row["dbpk"]."\",\"".$row["catalognumber"]."\",\"".
						$row["fieldname"]."\","."\"".$row["fieldvalueold"]."\",\"".$row["fieldvaluenew"]."\",\"".
						$row['reviewstatus']."\",\"".$row["appliedstatus"]."\",\"".$row["username"]."\",\"".
						$row["initialtimestamp"]."\"\n";
				}
				$rs->free();
			}
			else{
				echo "Recordset is empty.\n";
			}
	        exit();
		}
	}

	//Setters and getters
	public function setAppliedStatusFilter($status){
		if(is_numeric($status)){
			$this->appliedStatusFilter = $status;
		}
	}

	public function setReviewStatusFilter($status){
		if(preg_match('/^[,\d]+$/', $status)){
			$this->reviewStatusFilter = $status;
		}
	}

	public function setEditorUidFilter($f){
		if(is_numeric($f)){
			$this->editorUidFilter = $f;
		}
	}
	
	public function setQueryOccidFilter($num){
		if(is_numeric($num)){
			$this->queryOccidFilter = $num;
		}
	}

	public function setPageNumber($num){
		if(is_numeric($num)){
			$this->pageNumber = $num;
		}
	}

	public function setLimitNumber($limit){
		if(is_numeric($limit)){
			$this->limitNumber = $limit;
		}
	}

	public function getEditorList(){
		$retArr = Array();
		$sql = 'SELECT DISTINCT u.uid, CONCAT_WS(", ",u.lastname,u.firstname) AS username '.
			'FROM omoccuredits e INNER JOIN omoccurrences o ON e.occid = o.occid '.
			'INNER JOIN users u ON e.uid = u.uid '.
			'WHERE (o.collid = '.$this->collId.') ';
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$retArr[$row->uid] = $row->username;
		}
		$result->free();
		asort($retArr);
		return $retArr;
	}
}
?> 