<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/Manager.php');

class SpecEditReviewManager extends Manager{

	private $collId;
	private $collAcronym;

	private $display = 1;
	private $appliedStatusFilter = '';
	private $reviewStatusFilter;
	private $editorUidFilter;
	private $queryOccidFilter;
	private $pageNumber = 0;
	private $limitNumber;
	private $sqlBase;
	
	function __construct(){
		parent::__construct(null,'write');
	}

	function __destruct(){
 		parent::__destruct();
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

	public function getEditCnt(){
		if($this->display == 1){
			return $this->getOccurEditCnt();
		}
		elseif($this->display == 2){
			return $this->getRevisionCnt();
		}
		return 0;
	}
	
	public function getEditArr(){
		if($this->display == 1){
			return $this->getOccurEditArr();
		}
		elseif($this->display == 2){
			return $this->getRevisionArr();
		}
		return null;
	}
	
	//Occurrence edits (omoccuredits)
	private function getOccurEditCnt(){
		if(!$this->sqlBase) $this->setEditBase();
		$sql = 'SELECT COUNT(e.ocedid) AS fullcnt '.$this->sqlBase;
		//echo $sql; exit;
		$rsCnt = $this->conn->query($sql);
		if($rCnt = $rsCnt->fetch_object()){
			$recCnt = $rCnt->fullcnt;
		}
		$rsCnt->free();
		return $recCnt;
	}

	private function getOccurEditArr(){
		if(!$this->sqlBase) $this->setEditBase();
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
				$occId = $r->occid;
				$ts = $r->initialtimestamp;
				$retArr[$occId][$ts]['id'] = $r->ocedid;
				$retArr[$occId][$ts]['catnum'] = $r->catalognumber;
				$retArr[$occId][$ts]['rstatus'] = $r->reviewstatus;
				$retArr[$occId][$ts]['astatus'] = $r->appliedstatus;
				$retArr[$occId][$ts]['editor'] = $r->username;
				$retArr[$occId][$ts]['f'][$r->fieldname]['old'] = $r->fieldvalueold;
				$retArr[$occId][$ts]['f'][$r->fieldname]['new'] = $r->fieldvaluenew;
			}
			$rs->free();
		}
		return $retArr;
	}
	
	private function setEditBase(){
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
			if($this->editorFilter){
				$this->sqlBase .= 'AND (e.uid = '.$this->editorFilter.') ';
			}
			if($this->queryOccidFilter){
				$this->sqlBase .= 'AND (e.occid = '.$this->queryOccidFilter.') ';
			}
		}
	}
	
	//Occurrence revisions 
	private function getRevisionCnt(){
		if(!$this->sqlBase) $this->setRevisionBase();
		$sql = 'SELECT COUNT(r.orid) AS fullcnt '.$this->sqlBase;
		//echo $sql; exit;
		$rsCnt = $this->conn->query($sql);
		if($rCnt = $rsCnt->fetch_object()){
			$recCnt = $rCnt->fullcnt;
		}
		$rsCnt->free();
		return $recCnt;
	}
	
	private function getRevisionArr(){
		if(!$this->sqlBase) $this->setRevisionBase();
		$retArr = Array();
		if($this->sqlBase){
			//Grab records
			$sql = 'SELECT r.orid, r.occid, o.catalognumber, r.oldvalues, r.newvalues, r.externalsource, r.externaleditor, r.reviewstatus, r.appliedstatus, r.errormessage, '.
					'CONCAT_WS(", ",u.lastname,u.firstname) AS username, r.externaltimestamp, r.initialtimestamp '.
					$this->sqlBase.'ORDER BY r.initialtimestamp DESC '.
					'LIMIT '.($this->pageNumber*$this->limitNumber).','.($this->limitNumber+1);
					//echo '<div>'.$sql.'</div>';
					$rs = $this->conn->query($sql);
					while($r = $rs->fetch_object()){
						$occId = $r->occid;
						$editor = $r->externaleditor;
						if($r->username) $editor .= ' ('.$r->username.')';
						$ts = $r->initialtimestamp;

						$retArr[$occId][$ts]['id'] = $r->orid;
						$retArr[$occId][$ts]['catnum'] = $r->catalognumber;
						$retArr[$occId][$ts]['exsource'] = $r->externalsource;
						$retArr[$occId][$ts]['exeditor'] = $r->externaleditor;
						$retArr[$occId][$ts]['rstatus'] = $r->reviewstatus;
						$retArr[$occId][$ts]['astatus'] = $r->appliedstatus;
						$retArr[$occId][$ts]['errmsg'] = $r->errormessage;
						$retArr[$occId][$ts]['editor'] = $editor;
						$retArr[$occId][$ts]['extstamp'] = $r->externaltimestamp;
						
						$oldValues = json_decode($r->oldvalues,true);
						$newValues = json_decode($r->newvalues,true);
						$cnt = 0;
						foreach($oldValues as $fieldName => $value){
							if($fieldName != 'georeferencesources' && $fieldName != 'georeferencedby'){
								$retArr[$occId][$ts]['f'][$fieldName]['old'] = $value;
								$retArr[$occId][$ts]['f'][$fieldName]['new'] = (isset($newValues[$fieldName])?$newValues[$fieldName]:'ERROR');
								$cnt++;
							}
						}
					}
					$rs->free();
		}
		return $retArr;
	}
	
	private function setRevisionBase(){
		if($this->collId){
			$this->sqlBase = 'FROM omoccurRevisions r INNER JOIN omoccurrences o ON r.occid = o.occid '.
					'LEFT JOIN users u ON r.uid = u.uid '.
					'WHERE (o.collid = '.$this->collId.') ';
			if($this->appliedStatusFilter !== ''){
				$this->sqlBase .= 'AND (r.appliedstatus = '.$this->appliedStatusFilter.') ';
			}
			if($this->reviewStatusFilter){
				$this->sqlBase .= 'AND (r.reviewstatus IN('.$this->reviewStatusFilter.')) ';
			}
			if($this->editorFilter){
				if(is_numeric($this->editorFilter)){
					$this->sqlBase .= 'AND (u.uid = '.$this->editorFilter.') ';
				}
				else{
					$this->sqlBase .= 'AND (r.externaleditor = "'.$this->editorFilter.'") ';
				}
			}
			if($this->queryOccidFilter){
				$this->sqlBase .= 'AND (r.occid = '.$this->queryOccidFilter.') ';
			}
		}
	}
	
	//Actions
	public function updateRecords($postArr){
		if($this->display == 1){
			return $this->updateOccurEditRecords($postArr);
		}
		elseif($this->display == 2){
			return $this->updateRevisionRecords();
		}
		return null;
	}

	private function updateOccurEditRecords($postArr){
		if(!array_key_exists('id',$postArr)) return;
		$statusStr = 'SUCCESS: ';
		$idStr = implode(',',$postArr['id']);
		if($idStr){
			$ocedidStr = $this->getFullOcedidStr($idStr);
			//Apply edits
			$applyTask = $postArr['applytask'];
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
			if($postArr['rstatus']){
				$sql .= ',reviewstatus = '.$postArr['rstatus'];
			}
			$sql .= ' WHERE (ocedid IN('.$ocedidStr.'))';
			//echo '<div>'.$sql.'</div>'; exit;
			$this->conn->query($sql);
		}
		return $statusStr;
	}

	private function updateRevisionRecords($postArr){
		
	}

	public function deleteEdits($postArr){
		if(!array_key_exists('id',$postArr)) return;
		$idArr = $postArr['id'];
		$ocedidStr = $this->getFullOcedidStr($idStr);
		$sql = 'DELETE FROM omoccuredits WHERE (ocedid IN('.implode(',',$ocedidStr).'))';
		//echo '<div>'.$sql.'</div>';
		if($this->conn->query($sql)){
			return 'SUCCESS: Selected records deleted';
		}
		return 0;
	}
	
	public function downloadRecords($reqArr){
		if(!array_key_exists('id',$reqArr)) return;
		$idArr = $reqArr['id'];
		$ocedidStr = $this->getFullOcedidStr($idStr);
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
				'WHERE (o.collid = '.$this->collId.') AND (ocedid IN('.implode(',',$ocedidStr).')) '.
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

	private function getFullOcedidStr($idStr){
		//Get ocedid ids (this work around needed until we covert totally to just having omoccurrevisions table
		$ocedidArr = array();
		$sql = 'SELECT e.ocedid '.
			'FROM omoccuredits e INNER JOIN omoccuredits e2 ON e.occid = e2.occid AND e.initialtimestamp = e2.initialtimestamp '.
			'WHERE e2.ocedid IN('.$idStr.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$ocedidArr[] = $r->ocedid;
		}
		$rs->free();
		return implode(',',$ocedidArr);
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
	public function setDisplay($d){
		if(is_numeric($d)){
			$this->display = $d;
		}
	}

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

	public function setEditorFilter($f){
		$this->editorFilter = $this->cleanInStr($f);
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
		$sql = '';
		if($this->display == 1){
			$sql = 'SELECT DISTINCT u.uid AS id, CONCAT_WS(", ",u.lastname,u.firstname) AS name '.
				'FROM omoccuredits e INNER JOIN omoccurrences o ON e.occid = o.occid '.
				'INNER JOIN users u ON e.uid = u.uid '.
				'WHERE (o.collid = '.$this->collId.') ';
		}
		else{
			$sql = 'SELECT DISTINCT IFNULL(l.uid,r.externaleditor) as id, IFNULL(l.username,r.externaleditor) AS name '.
					'FROM omoccurrevisions r INNER JOIN omoccurrences o ON r.occid = o.occid '.
					'LEFT JOIN userlogin l ON r.uid = l.uid '.
					'WHERE (o.collid = '.$this->collId.') ';
		}
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$retArr[$row->id] = $row->name;
		}
		$result->free();
		asort($retArr);
		return $retArr;
	}
}
?> 