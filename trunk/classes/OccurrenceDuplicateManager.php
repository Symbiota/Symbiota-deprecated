<?php
include_once($serverRoot.'/config/dbconnection.php');

class OccurrenceDuplicateManager {

	private $conn;
	private $collId;
	private $obsUid;
	
	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function getDuplicateClusters($limitToConflicts,$start,$limit){
		$retArr = array();
		if($this->collId){
			//Grab clusters
			$sqlPrefix = 'SELECT DISTINCT d.duplicateid, d.projIdentifier AS title, d.projDescription AS description, d.notes ';
			//$sqlPrefix = 'SELECT DISTINCT d.duplicateid, d.title, d.description, d.notes ';
			$sqlSuffix = '';
			if($limitToConflicts){
				$sqlSuffix = 'FROM omoccurduplicates d INNER JOIN omoccurduplicatelink dl1 ON d.duplicateid = dl1.duplicateid '.
					'INNER JOIN omoccurrences o ON dl1.occid = o.occid '.
					'INNER JOIN omoccurduplicatelink dl2 ON d.duplicateid = dl2.duplicateid '.
					'INNER JOIN omoccurrences o2 ON dl2.occid = o2.occid '.
					'WHERE o.collid = '.$this->collId.($this->obsUid?' AND o.observeruid = '.$this->obsUid:'').' AND o.tidinterpreted <> o2.tidinterpreted ';
			}
			else{
				$sqlSuffix = 'FROM omoccurduplicates d INNER JOIN omoccurduplicatelink dl ON d.duplicateid = dl.duplicateid '.
					'INNER JOIN omoccurrences o ON dl.occid = o.occid '.
					'WHERE o.collid = '.$this->collId.($this->obsUid?' AND o.observeruid = '.$this->obsUid:'').' ';
			}
			//Get total counts
			$totalCnt = 0;
			$rsCnt = $this->conn->query('SELECT count(DISTINCT d.duplicateid) as cnt '.$sqlSuffix);
			if($rCnt = $rsCnt->fetch_object()){
				$totalCnt = $rCnt->cnt;
			}
			$rsCnt->free();
			
			$sql = $sqlPrefix.$sqlSuffix.' ORDER BY o.recordedby,o.recordnumber LIMIT '.$start.','.$limit;
			//echo 'sql: '.$sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->duplicateid]['title'] = $r->title;
				$retArr[$r->duplicateid]['desc'] = $r->description;
				$retArr[$r->duplicateid]['notes'] = $r->notes;
			}
			$rs->free();
			if($retArr){
				//Grab occurrences for each cluster
				$sql = 'SELECT dl.duplicateid, o.occid, IFNULL(IFNULL(o.occurrenceid,o.catalognumber),othercatalognumbers) AS identifier, '.
					'o.sciname, o.tidinterpreted, o.recordedby, o.recordnumber, CONCAT_WS(":",c.institutioncode ,c.collectioncode) as code, '.
					'o.identifiedby, o.dateidentified '.
					'FROM omoccurduplicatelink dl INNER JOIN omoccurrences o ON dl.occid = o.occid '.
					'INNER JOIN omcollections c ON o.collid = c.collid '.
					'WHERE dl.duplicateid IN ('.implode(',',array_keys($retArr)).')';
				//echo $sql;
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$idStr = $r->identifier;
					if(is_numeric($idStr)) $idStr = $r->code.':'.$idStr;
					if(!$idStr) $idStr = $r->code.':'.'undefined';
					$retArr[$r->duplicateid][$r->occid]['id'] = $idStr;
					$retArr[$r->duplicateid][$r->occid]['sciname'] = $r->sciname;
					$retArr[$r->duplicateid][$r->occid]['tid'] = $r->tidinterpreted;
					$retArr[$r->duplicateid][$r->occid]['idby'] = $r->identifiedby;
					$retArr[$r->duplicateid][$r->occid]['dateid'] = $r->dateidentified;
					$retArr[$r->duplicateid][$r->occid]['recby'] = $r->recordedby.' '.$r->recordnumber;
				}
				$rs->free();
			}
			$retArr['cnt'] = $totalCnt;
		}
		return $retArr;
	}

	public function linkDuplicates($collid = 0, $verbose = true){
		//The code is setup so that it can be triggered for all collections (collid = 0)
		ini_set('max_execution_time', 1800);
		$startDate = '1700-00-00';
		$recCnt = 0;
		if($verbose) echo '<li>Starting to search for duplicates '.date('Y-m-d H:i:s').'</li>';
		ob_flush();
		flush();
		do{
			$sql = 'SELECT DISTINCT o.eventdate '.
				'FROM omoccurrences o LEFT JOIN omoccurduplicatelink d ON o.occid = d.occid '.
				'WHERE o.eventdate > "'.$startDate.'" AND d.occid IS NULL ';
			if($collid) $sql .= 'AND o.collid = '.$collid.' ';
			if($this->obsUid) $sql .= 'AND o.observeruid = '.$this->obsUid.' ';
			$sql .= 'ORDER BY o.eventdate LIMIT 500';
			$rs = $this->conn->query($sql);
			$recCnt = $rs->num_rows;
			if($verbose) echo '<li>Start date '.$startDate.' with '.$recCnt.' dates to be evaluated</li>';
			ob_flush();
			flush();
			while($r = $rs->fetch_object()){
				$startDate = $r->eventdate;
				//Grab all recs with matching date
				$sql2 = 'SELECT o.recordedby, o.recordnumber, o.occid, IFNULL(d.duplicateid,0) as dupid, o.collid, o.observeruid '.
					'FROM omoccurrences o LEFT JOIN omoccurduplicatelink d ON o.occid = d.occid '.
					'WHERE o.eventdate = "'.$r->eventdate.'" AND o.recordedby IS NOT NULL AND o.recordnumber IS NOT NULL '; 
				$rs2 = $this->conn->query($sql2);
				$rArr = array();
				$keepArr = array();
				while($r2 = $rs2->fetch_object()){
					$recNum = str_replace(array(' ','-',':'),'',$r2->recordnumber);
					if(preg_match('#\d#',$recNum)){
						$lastName = $this->parseCollectorLastname($r2->recordedby);
						if(strpos($lastName,'.')) $lastName = $r2->recordedby;
						if(isset($lastName) && $lastName && !preg_match('#\d#',$lastName)){
							$rArr[$recNum][$lastName][$r2->dupid][] = $r2->occid;
							if($r2->collid == $collid && ($this->obsUid || $r2->observeruid == $this->obsUid)) $keepArr[$recNum][$lastName] = 1;
						}
					}
				}
				$recArr = array();
				if($collid){
					//Only use the sets that have a reference to given collid
					foreach($keepArr as $n => $lArr){
						foreach($lArr as $l => $v){
							$recArr[$n][$l] = $rArr[$n][$l];
						}
					}
				}
				else{
					$recArr = $rArr;
				}
				//if($verbose) echo '<li>Event date '.$r->eventdate.' with '.count($recArr).' records</li>';
				//ob_flush();
				//flush();
				//Process rec array
				foreach($recArr as $numStr => $collArr){
					foreach($collArr as $lastnameStr => $mArr){
						$unlinkedArr = isset($mArr[0])?$mArr[0]:null;
						unset($mArr[0]);
						if(count($unlinkedArr) > 1 || ($unlinkedArr && $mArr)){
							$dupIdStr = $lastnameStr.' '.$numStr.' '.$r->eventdate;
							if($verbose) echo '<li>Duplicates located: '.$dupIdStr.'</li>';
							ob_flush();
							flush();
							$dupId = 0;
							if($mArr) $dupId = key($mArr);
							if(!$dupId){
								//Create a new dupliate project
								$sqlI1 = 'INSERT INTO omoccurduplicates(projIdentifier,exactdupe) VALUES("'.$this->cleanInStr($dupIdStr).'",1)';
								//$sqlI1 = 'INSERT INTO omoccurduplicates(title,dupetype) VALUES("'.$dupIdStr.'",1)';
								if($this->conn->query($sqlI1)){
									$dupId = $this->conn->insert_id;
									if($verbose) echo '<li style="margin-left:10px;">New duplicate project created: #'.$dupId.'</li>';
								}
								else{
									if($verbose) echo '<li style="margin-left:10px;">ERROR creating dupe project: '.$this->conn->error.'</li>';
									if($verbose) echo '<li style="margin-left:10px;">sql: '.$sqlI1.'</li>';
								}
								ob_flush();
								flush();
							}
							if($dupId){
								//Add unlinked to duplicate project
								$outLink = '';
								$sqlI2 = 'INSERT INTO omoccurduplicatelink(duplicateid,occid) VALUES ';
								foreach($unlinkedArr as $v){
									$sqlI2 .= '('.$dupId.','.$v.'),';
									$outLink .= ' <a href="../individual/index.php?occid='.$v.'" target="_blank">'.$v.'</a>,';
								}
								if($this->conn->query(trim($sqlI2,','))){
									if($verbose) echo '<li style="margin-left:10px;">'.count($unlinkedArr).' duplicates linked ('.trim($outLink,' ,').')</li>';
								}
								else{
									if($verbose) echo '<li style="margin-left:10px;">ERROR linking dupes: '.$this->conn->error.'</li>';
								}
								ob_flush();
								flush();
							}							
						}
						//Check to see if two duplicate projects exists; if so, they should maybe be merged
						if(count($mArr) > 1){
							if($verbose) echo '<li style="margin-left:10px;">Two matching duplicate projects located</li>';
							ob_flush();
							flush();
							
						}
					}
				}
			}
			$rs->close();
		}while($recCnt);
		if($verbose) echo '<li>Finished linking duplicates '.date('Y-m-d H:i:s').'</li>';
	}
	
	public function editDuplicateCluster($dupId, $title, $description, $notes){
		$statusStr = 'SUCCESS: duplicate cluster edited';
		$sql = 'UPDATE omoccurduplicates SET projIdentifier = '.($title?'"'.$this->cleanInStr($title).'"':'NULL').', '.
			'projdescription = '.($description?'"'.$this->cleanInStr($description).'"':'NULL').', '.
			'notes = '.($notes?'"'.$this->cleanInStr($notes).'"':'NULL').' '.
			'WHERE (duplicateid = '.$dupId.')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$statusStr = 'ERROR editing duplicate cluster: '.$this->conn->error;
		}
		return $statusStr;
	}
	
	public function deleteDuplicateCluster($dupId){
		$statusStr = 'SUCCESS: duplicate cluster deleted';
		$sql = 'DELETE FROM omoccurduplicates WHERE duplicateid = '.$dupId;
		if(!$this->conn->query($sql)){
			$statusStr = 'ERROR deleting duplicate cluster: '.$this->conn->error;
		}
		return $statusStr;
	}
	
	public function deleteOccurFromCluster($dupId, $occid){
		$statusStr = 'SUCCESS: occurrence removed from duplicate cluster';
		//If duplicate cluster only consists of two occurrences, remove whole cluster
		$rs = $this->conn->query('SELECT duplicateid FROM omoccurduplicates WHERE duplicateid = '.$dupId);
		if($rs->num_rows == 2){
			$sql = 'DELETE FROM omoccurduplicates WHERE (duplicateid = '.$dupId.')';
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR deleting duplicate cluster: '.$this->conn->error;
			}
		}
		else{
			$sql = 'DELETE FROM omoccurduplicatelink WHERE (duplicateid = '.$dupId.') AND (occid = '.$occid.')';
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR deleting occurrence from duplicate cluster: '.$this->conn->error;
			}
		}
		return $statusStr;
	}
	
	private function parseCollectorLastname($inStr){
		$lastName = '';
		$primaryArr = explode(';',$inStr);
		$primaryArr = explode('&',$primaryArr[0]);
		$primaryArr = explode(' and ',$primaryArr[0]);
		$lastNameArr = explode(',',$primaryArr[0]);
		if(count($lastNameArr) > 1){
			//formats: Last, F.I.; Last, First I.; Last, First Initial
			$lastName = array_shift($lastNameArr);
		}
		else{
			//Formats: F.I. Last; First I. Last; First Initial Last
			$tempArr = explode(' ',$lastNameArr[0]);
			$lastName = array_pop($tempArr);
			if(strpos($lastName,'.') || $lastName = 'III' || strlen($lastName)<3) $lastName = array_pop($tempArr);
		}
		return $lastName;
	}

	public function setCollId($collId){
		if(is_numeric($collId)){
			$this->collId = $collId;
		}
	}

	public function setObsuid($obsUid){
		if(is_numeric($obsUid)){
			$this->obsUid = $obsUid;
		}
	}

	public function getCollMap(){
		$returnArr = Array();
		if($this->collId){
			$sql = 'SELECT c.institutioncode, c.collectioncode, c.collectionname, '.
				'c.icon, c.colltype, c.managementtype '.
				'FROM omcollections c '.
				'WHERE (c.collid = '.$this->collId.') ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$returnArr['institutioncode'] = $row->institutioncode;
				$returnArr['collectioncode'] = $row->collectioncode;
				$returnArr['collectionname'] = $row->collectionname;
				$returnArr['icon'] = $row->icon;
				$returnArr['colltype'] = $row->colltype;
				$returnArr['managementtype'] = $row->managementtype;
			}
			$rs->close();
		}
		return $returnArr;
	}
	
	private function cleanInStr($str){
		return $this->conn->real_escape_string(trim($str));
	}
}
?>