<?php
include_once($serverRoot.'/config/dbconnection.php');

class OccurrenceCleaner {

	private $conn;
	private $collId;
	private $obsUid;
	
	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
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

	public function getDuplicateCatalogNumber(){
		$returnArr = array();
		$sql = 'SELECT o.occid, o.catalognumber, o.family, o.sciname, o.recordedBy, o.recordNumber, o.associatedCollectors, '.
			'o.eventDate, o.verbatimEventDate, o.country, o.stateProvince, o.county, o.municipality, o.locality '.
			'FROM omoccurrences o INNER JOIN (SELECT catalognumber FROM omoccurrences GROUP BY catalognumber, collid '.($this->obsUid?', observeruid ':''). 
			'HAVING Count(*)>1 AND collid = '.$this->collId.($this->obsUid?' AND observeruid = '.$this->obsUid:'').' AND catalognumber IS NOT NULL) rt ON o.catalognumber = rt.catalognumber '.
			'WHERE o.collid = '.$this->collId.($this->obsUid?' AND o.observeruid = '.$this->obsUid:'').' ORDER BY o.catalognumber LIMIT 505';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->occid]['occid'] = $row->occid;
			$returnArr[$row->occid]['catalognumber'] = $row->catalognumber;
			$returnArr[$row->occid]['family'] = $row->family;
			$returnArr[$row->occid]['sciname'] = $row->sciname;
			$returnArr[$row->occid]['recordedBy'] = $row->recordedBy;
			$returnArr[$row->occid]['recordNumber'] = $row->recordNumber;
			$returnArr[$row->occid]['associatedCollectors'] = $row->associatedCollectors;
			$returnArr[$row->occid]['eventDate'] = $row->eventDate;
			$returnArr[$row->occid]['verbatimEventDate'] = $row->verbatimEventDate;
			$returnArr[$row->occid]['country'] = $row->country;
			$returnArr[$row->occid]['stateProvince'] = $row->stateProvince;
			$returnArr[$row->occid]['county'] = $row->county;
			$returnArr[$row->occid]['municipality'] = $row->municipality;
			$returnArr[$row->occid]['locality'] = $row->locality;
		}
		$rs->free();
		return $returnArr;
	}
	
	public function getDuplicateCollectorNumber($lastName = ''){
		$returnArr = array();
		$sql = 'SELECT o.occid, o.catalognumber, o.othercatalognumbers, o.family, o.sciname, o.recordedBy, o.recordNumber, o.associatedCollectors, '.
			'o.eventDate, o.verbatimEventDate, o.country, o.stateProvince, o.county, o.municipality, o.locality '. 
			'FROM omoccurrences o INNER JOIN '. 
			'(SELECT count(occid) as cnt, eventdate, recordnumber '.
			'FROM omoccurrences '.
			'WHERE collid = '.$this->collId.($this->obsUid?' AND observeruid = '.$this->obsUid:'').' AND eventdate IS NOT NULL AND recordnumber IS NOT NULL '.
			'AND recordnumber NOT LIKE "s%n%" '. 
			'GROUP BY eventdate, recordnumber) intab ON o.eventdate = intab.eventdate AND o.recordnumber = intab.recordnumber '.
			'WHERE intab.cnt > 2 AND collid = '.$this->collId.($this->obsUid?' AND observeruid = '.$this->obsUid:'').' '.
			'ORDER BY o.recordedBy, o.recordNumber LIMIT 505';
		/*$sql = 'SELECT o.occid, o.catalognumber, o.family, o.sciname, o.recordedBy, o.recordNumber, o.associatedCollectors, '.
			'o.eventDate, o.verbatimEventDate, o.country, o.stateProvince, o.county, o.municipality, o.locality '.
			'FROM omoccurrences o INNER JOIN '. 
			'(SELECT recordedby, recordnumber, count(*) as reccnt '. 
			'FROM omoccurrences '. 
			'WHERE collid = '.$this->collId.' AND recordedby IS NOT NULL '. 
			'AND recordnumber IS NOT NULL AND recordnumber != "s.n." AND recordnumber != "sn" '.
			'GROUP BY recordedby, recordnumber) intab ON o.recordedby = intab.recordedby AND o.recordnumber = intab.recordnumber '.
			'WHERE collid = collid = '.$this->collId.' AND intab.reccnt > 1 '.
			'ORDER BY o.recordedBy, o.recordNumber LIMIT 505';*/
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->occid]['occid'] = $row->occid;
			$returnArr[$row->occid]['catalognumber'] = $row->catalognumber;
			$returnArr[$row->occid]['othercatalognumbers'] = $row->othercatalognumbers;
			$returnArr[$row->occid]['family'] = $row->family;
			$returnArr[$row->occid]['sciname'] = $row->sciname;
			$returnArr[$row->occid]['recordedBy'] = $row->recordedBy;
			$returnArr[$row->occid]['recordNumber'] = $row->recordNumber;
			$returnArr[$row->occid]['associatedCollectors'] = $row->associatedCollectors;
			$returnArr[$row->occid]['eventDate'] = $row->eventDate;
			$returnArr[$row->occid]['verbatimEventDate'] = $row->verbatimEventDate;
			$returnArr[$row->occid]['country'] = $row->country;
			$returnArr[$row->occid]['stateProvince'] = $row->stateProvince;
			$returnArr[$row->occid]['county'] = $row->county;
			$returnArr[$row->occid]['municipality'] = $row->municipality;
			$returnArr[$row->occid]['locality'] = $row->locality;
		}
		$rs->free();
		return $returnArr;
	}
	
	public function mergeDupeArr($occidArr){
		$dupArr = array();
		foreach($occidArr as $v){
			$vArr = explode(':',$v);
			$dupArr[$vArr[0]][] = $vArr[1];
		}
		foreach($dupArr as $catNum => $occArr){
			if(count($occArr) > 1){
				$targetOccid = array_shift($occArr);
				$statusStr = $targetOccid;
				foreach($occArr as $sourceOccid){
					$this->mergeRecords($targetOccid,$sourceOccid);
					$statusStr .= ', '.$sourceOccid;
				}
				echo '<li>Merging records: '.$statusStr.'</li>';
			}
			else{
				echo '<li>Record # '.array_shift($occArr).' skipped because only one record was selected</li>';
			}
		}
	}
	
	public function mergeRecords($targetOccid,$sourceOccid){
		if(!$targetOccid || !$sourceOccid) return 'ERROR: target or source is null';
		if($targetOccid == $sourceOccid) return 'ERROR: target and source are equal';
		$status = true;

		$oArr = array();
		//Merge records
		$sql = 'SELECT * FROM omoccurrences WHERE occid = '.$targetOccid.' OR occid = '.$sourceOccid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_assoc()){
			$tempArr = array_change_key_case($r);
			$id = $tempArr['occid'];
			unset($tempArr['occid']);
			unset($tempArr['collid']);
			unset($tempArr['dbpk']);
			unset($tempArr['datelastmodified']);
			$oArr[$id] = $tempArr;
		}
		$rs->free();

		$tArr = $oArr[$targetOccid];
		$sArr = $oArr[$sourceOccid];
		$sqlFrag = '';
		foreach($sArr as $k => $v){
			if(($v != '') && $tArr[$k] == ''){
				$sqlFrag .= ','.$k.'="'.$v.'"';
			} 
		}
		if($sqlFrag){
			//Remap source to target
			$sqlIns = 'UPDATE omoccurrences SET '.substr($sqlFrag,1).' WHERE occid = '.$targetOccid;
			//echo $sqlIns;
			$this->conn->query($sqlIns);
		}

		//Remap determinations
		$sql = 'UPDATE omoccurdeterminations SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Delete occurrence edits
		$sql = 'DELETE FROM omoccuredits WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap images
		$sql = 'UPDATE images SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap comments
		$sql = 'UPDATE omoccurcomments SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap exsiccati
		$sql = 'UPDATE omexsiccatiocclink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap occurrence dataset links
		$sql = 'UPDATE omoccurdatasetlink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap loans
		$sql = 'UPDATE omoccurloanslink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap checklists voucher links
		$sql = 'UPDATE fmvouchers SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap survey lists
		$sql = 'UPDATE omsurveyoccurlink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Delete source
		$sql = 'DELETE FROM omoccurrences WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			$status .= '<li><span style="color:red">ERROR:</span> unable to delete occurrence record #'.$sourceOccid.
			': '.$this->conn->error.'</li>';
		}
		return $status;
	}
	
	//Parse, index, and link collector's to Collector table 
	public function outputLastName(){
		$sql = 'SELECT o.recordedby '.
			'FROM omoccurrences o LEFT JOIN omcollectors c ON o.recordedById = c.recordedById '.
			'WHERE c.recordedById IS NULL ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$collector = $r->recordedby;
			$collArr = $this->parseCollectorName($collector);
			
		}
		$rs->close();
		
	} 

	public function indexCollectors(){
		//Try to populate using already linked names 
		$sql = 'UPDATE omoccurrences o1 INNER JOIN (SELECT DISTINCT recordedbyid, recordedby FROM omoccurrences WHERE recordedbyid IS NOT NULL) o2 ON o1.recordedby = o2.recordedby '.
			'SET o1.recordedbyid = o2.recordedbyid '.
			'WHERE o1.recordedbyid IS NULL';
		$this->conn->query($sql); 
		
		//Query unlinked specimens and try to parse each collector
		$collArr = array();
		$sql = 'SELECT occid, recordedby '.
			'FROM omoccurrences '.
			'WHERE recordedbyid IS NULL';
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$collArr[$r->recordedby][] = $r->occid;
		}
		$rs->close();
		
		foreach($collArr as $collStr => $occidArr){
			$collArr = $this->parseCollectorName($collStr);
			//Check to make sure collector is not already in system 
			$sql = 'SELECT recordedbyid '.
				'FROM omcollectors '.
				'WHERE familyname = "'.$collArr['last'].'" AND firstname = "'.$collArr['first'].'" AND middlename = "'.$collArr['middle'].'"';
			$rs = $this->conn->query($sql);
			$recById = 0; 
			if($r = $rs->fetch_object()){
				$recById = $r->recordedbyid;
			}
			else{
				//Not in system, thus load and get PK
				$sql = 'INSERT omcollectors(familyname, firstname, middlename) '.
					'VALUES("'.$collArr['last'].'","'.$collArr['first'].'","'.$collArr['middle'].'")';
				$this->conn->query($sql);
				$recById = $this->conn->insert_id;
			}
			$rs->close();
			//Add recordedbyid to omoccurrence table
			if($recById){
				$sql = 'UPDATE omoccurrences '.
					'SET recordedbyid = '.$recById.
					' WHERE occid IN('.implode(',',$occidArr).') AND recordedbyid IS NULL ';
				$this->conn->query($sql);
			}
		}
	}
	
	private function parseCollectorName($inStr){
		$name = array();
		$primaryArr = '';
		$primaryArr = explode(';',$inStr);
		$primaryArr = explode('&',$primaryArr[0]);
		$primaryArr = explode(' and ',$primaryArr[0]);
		$lastNameArr = explode(',',$primaryArr[0]);
		if(count($lastNameArr) > 1){
			//formats: Last, F.I.; Last, First I.; Last, First Initial Last
			$name['last'] = $lastNameArr[0];
			if($pos = strpos($lastNameArr[1],' ')){
				$name['first'] = substr($lastNameArr[1],0,$pos);
				$name['middle'] = substr($lastNameArr[1],$pos);
			}
			elseif($pos = strpos($lastNameArr[1],'.')){
				$name['first'] = substr($lastNameArr[1],0,$pos);
				$name['middle'] = substr($lastNameArr[1],$pos);
			}
			else{
				$name['first'] = $lastNameArr[1];
			}
		}
		else{
			//Formats: F.I. Last; First I. Last; First Initial Last
			$tempArr = explode(' ',$lastNameArr[0]);
			$name['last'] = array_pop($tempArr);
			if($tempArr){
				$arrCnt = count($tempArr);
				if($arrCnt == 1){
					if(preg_match('/(\D+\.+)(\D+\.+)/',$tempArr[0],$m)){
						$name['first'] = $m[1];
						$name['middle'] = $m[2];
					}
					else{
						$name['first'] = $tempArr[0];
					}
				}
				elseif($arrCnt == 2){
					$name['first'] = $tempArr[0];
					$name['middle'] = $tempArr[1];
				}
				else{
					$name['first'] = implode(' ',$tempArr);
				}
			}
		}
		return $name;
	}

	public function getDuplicateClusters($limitToConflicts = 0){
		$retArr = array();
		if($this->collId){
			//Grab clusters
			$sqlPrefix = 'SELECT DISTINCT d.duplicateid, d.projIdentifier AS title, d.projDescription AS description, d.notes ';
			//$sqlPrefix = 'SELECT DISTINCT d.duplicateid, d.title, d.description, d.notes ';
			$sqlSuffix = '';
			if($limitToConflicts){
				$sqlSuffix = 'FROM omoccurduplicates d INNER JOIN omoccurduplicatelink dl1 ON d.duplicateid = dl1.duplicateid '.
					'INNER JOIN omoccurrences o1 ON dl1.occid = o1.occid '.
					'INNER JOIN omoccurduplicatelink dl2 ON d.duplicateid = dl2.duplicateid '.
					'INNER JOIN omoccurrences o2 ON dl2.occid = o2.occid '.
					'WHERE o1.collid = '.$this->collId.($this->obsUid?' AND o1.observeruid = '.$this->obsUid:'').' AND o1.tidinterpreted <> o2.tidinterpreted '.
					'ORDER BY d.projIdentifier';
				/*$sqlSuffix = 'FROM omoccurduplicates d INNER JOIN omoccurduplicatelink dl1 ON d.duplicateid = dl1.duplicateid '.
					'INNER JOIN omoccurrences o1 ON dl1.occid = o1.occid '.
					'INNER JOIN omoccurduplicatelink dl2 ON d.duplicateid = dl2.duplicateid '.
					'INNER JOIN omoccurrences o2 ON dl2.occid = o2.occid '.
					'WHERE o1.collid = '.$this->collId.($this->obsUid?' AND o1.observeruid = '.$this->obsUid:'').' AND o1.tidinterpreted <> o2.tidinterpreted '.
					'ORDER BY d.title';*/
			}
			else{
				$sqlSuffix = 'FROM omoccurduplicates d INNER JOIN omoccurduplicatelink dl ON d.duplicateid = dl.duplicateid '.
					'INNER JOIN omoccurrences o ON dl.occid = o.occid '.
					'WHERE o.collid = '.$this->collId.($this->obsUid?' AND o.observeruid = '.$this->obsUid:'').' ORDER BY d.projIdentifier';
				/*$sqlSuffix = 'FROM omoccurduplicates d INNER JOIN omoccurduplicatelink dl ON d.duplicateid = dl.duplicateid '.
					'INNER JOIN omoccurrences o ON dl.occid = o.occid '.
					'WHERE o.collid = '.$this->collId.($this->obsUid?' AND o.observeruid = '.$this->obsUid:'').' ORDER BY d.title';*/
			}
			//echo $sqlPrefix.$sqlSuffix;
			$rs = $this->conn->query($sqlPrefix.$sqlSuffix);
			while($r = $rs->fetch_object()){
				$retArr[$r->duplicateid]['title'] = $r->title;
				$retArr[$r->duplicateid]['desc'] = $r->description;
				$retArr[$r->duplicateid]['notes'] = $r->notes;
			}
			$rs->free();
			//Grab occurrences for each cluster
			$sql = 'SELECT dl.duplicateid, o.occid, IFNULL(o.occurrenceid,o.catalognumber) AS identifier, '.
				'o.sciname, o.tidinterpreted, o.recordedby, o.recordnumber, CONCAT_WS(":",c.institutioncode ,c.collectioncode) as code '.
				'FROM omoccurduplicatelink dl INNER JOIN omoccurrences o ON dl.occid = o.occid '.
				'INNER JOIN omcollections c ON o.collid = c.collid '.
				'WHERE dl.duplicateid IN (SELECT d.duplicateid '.$sqlSuffix.')';
			/*$sql = 'SELECT dl.duplicateid, o2.occid, IFNULL(o2.occurrenceid,o2.catalognumber) AS identifier, '.
				'o2.sciname, o2.tidinterpreted, o2.recordedby, o2.recordnumber, CONCAT_WS(":",c.institutioncode ,c.collectioncode) as code '.
				'FROM omoccurrences o INNER JOIN omoccurduplicatelink dl ON o.occid = dl.occid '.
				'INNER JOIN omoccurduplicatelink dl2 ON dl.duplicateid = dl2.duplicateid '.
				'INNER JOIN omoccurrences o2 ON dl2.occid = o2.occid '.
				'INNER JOIN omcollections c ON o2.collid = c.collid '.
				'WHERE o.collid = '.$this->collId;*/
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$idStr = $r->identifier;
				if(is_numeric($idStr)) $idStr = $r->code.':'.$idStr;
				if(!$idStr) $idStr = $r->code.':'.'undefined';
				$retArr[$r->duplicateid][$r->occid]['id'] = $idStr;
				$retArr[$r->duplicateid][$r->occid]['sciname'] = $r->sciname;
				$retArr[$r->duplicateid][$r->occid]['tid'] = $r->tidinterpreted;
				$retArr[$r->duplicateid][$r->occid]['recby'] = $r->recordedby.' '.$r->recordnumber;
			}
			$rs->free();
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
						$nameArr = $this->parseCollectorName($r2->recordedby);
						$lastName = $nameArr['last'];
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
	
	private function cleanInStr($str){
		return $this->conn->real_escape_string(trim($str));
	}
}
?>