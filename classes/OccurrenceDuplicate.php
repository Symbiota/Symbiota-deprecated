<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorManager.php');

class OccurrenceDuplicate {

	private $conn;
	private $obsUid;
	private $relevantFields = array();

	private $errorStr;
	
	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	//Duplicate editing functions used occurrenceeditor.php  
	public function getClusterArr($occid){
		$retArr = array();
		$sql1 = 'SELECT DISTINCT d.duplicateid, d.title, d.description, d.notes '.
			'FROM omoccurduplicates d INNER JOIN omoccurduplicatelink l ON d.duplicateid = l.duplicateid '.
			'WHERE (l.occid = '.$occid.')';
		if($rs1 = $this->conn->query($sql1)){
			while($r1 = $rs1->fetch_object()){
				$retArr[$r1->duplicateid]['title'] = $r1->title;
				$retArr[$r1->duplicateid]['description'] = $r1->description;
				$retArr[$r1->duplicateid]['notes'] = $r1->notes;
			}
			$rs1->close();
		}
		else{
			$this->errorStr = 'ERROR getting list of duplicate records [1]: '.$this->conn->error;
			$retArr = false;
		}

		if($retArr){
			$sql = 'SELECT d.duplicateid, d.occid, c.institutioncode, c.collectioncode, c.collectionname, o.catalognumber, '.
				'o.occurrenceid, o.sciname, o.identifiedby, o.dateidentified, '.
				'o.recordedby, o.recordnumber, o.eventdate, d.notes, i.url, i.thumbnailurl '.
				'FROM omoccurduplicatelink d INNER JOIN omoccurrences o ON d.occid = o.occid '.
				'INNER JOIN omcollections c ON o.collid = c.collid '.
			 	'LEFT JOIN images i ON o.occid = i.occid '.
				'WHERE (d.duplicateid IN('.implode(',',array_keys($retArr)).'))';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr[$r->duplicateid]['o'][$r->occid] = array('instcode' => $r->institutioncode, 'collcode' => $r->collectioncode, 
					'collname' => $r->collectionname, 'catnum' => $r->catalognumber, 'occurrenceid' => $r->occurrenceid, 'sciname' => $r->sciname,
					'identifiedby' => $r->identifiedby, 'dateidentified' => $r->dateidentified, 'recordedby' => $r->recordedby,
					'recordnumber' => $r->recordnumber, 'eventdate' => $r->eventdate, 'notes' => $r->notes, 'tnurl' => $r->thumbnailurl, 
					'url' => $r->url);
				}
				$rs->free();
			}
			else{
				$this->errorStr = 'ERROR getting list of duplicate records [2]: '.$this->conn->error;
				$retArr = false;
			}
		}
		return $retArr;
	}

	public function linkDuplicates($occid1,$occidStr,$dupTitle=''){
		$status = true;
		if($occid1 && $occidStr){
			$targetDupID = 0;
			//Look for an existing duplicate cluster id
			$dupArr = array(); 
			$sql = 'SELECT occid, duplicateid FROM omoccurduplicatelink WHERE occid IN('.$occid1.','.$occidStr.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$dupArr[$r->duplicateid] = $r->occid;
			}
			$rs->free();
			if(count($dupArr) == 1){
				//Use already existing cluster already exists
				$targetDupID = key($dupArr);
			}
			elseif(count($dupArr) > 1){
				$targetDupID = $this->mergeClusters(array_keys($dupArr));
			}
			else{
				$targetDupID = $this->createCluster($occid1,$dupTitle);
			}
			if($targetDupID){
				//Add subject specimens to duplicate cluster
				$sql2 = 'INSERT IGNORE INTO omoccurduplicatelink(duplicateid,occid) '.
					'VALUES('.$targetDupID.','.$occid1.'),('.$targetDupID.','.$occidStr.')';
				if(!$this->conn->query($sql2)){
					$status = false;
					$this->errorStr = 'ERROR linking occurrences to duplicate cluster: '.$this->conn->error;
				}
			}
			else{
				$status = false;
			}						
		}
		return $status;
	}
	
	private function createCluster($occid,$title=''){
		$retId = 0;
		if(!$title){
			//Create a duplicate cluster title
			$sqlTitle = 'SELECT recordedby, recordnumber, eventdate '.
				'FROM omoccurrences '.
				'WHERE occid = '.$occid; 
			$rsTitle = $this->conn->query($sqlTitle);
			while($r = $rsTitle->fetch_object()){
				$title = $this->parseLastName($r->recordedby).' '.$r->recordnumber.' '.$r->eventdate;
			}
			$rsTitle->free();
			if(!$title) $title = 'Undefined Collector';
		}
		//If duplicate id does not exist, create one
		$sql1 = 'INSERT INTO omoccurduplicates(title,dupetype) VALUES("'.$this->cleanInStr($title).'",1)';
		if($this->conn->query($sql1)){
			$retId = $this->conn->insert_id;
		}
		else{
			$this->errorStr = 'ERROR creating new dupliate cluster: '.$this->conn->error;
			$status = false;
		}
		return $retId;
	}

	private function mergeClusters($dupArr){
		$targetId = 0;
		//Merge duplicate clusters
		if(count($dupArr)>1){
			$targetId = min($dupArr);
			//remove value from array
			unset($dupArr[array_search($targetId, $dupArr)]);
			$sql = 'UPDATE omoccurduplicatelink SET duplicateid = '.$targetId.' WHERE duplicateid IN('.$dupArr.')';
			if($this->conn->query($sql)){
				if(!$this->conn->query('DELETE FROM omoccurduplicates WHERE duplicateid IN('.$dupArr.')')){
					$this->errorStr = 'ERROR merging duplicate clusters: '.$this->conn->error;
				}
			}
			else{
				$this->errorStr = 'ERROR removing extract duplicate cluster: '.$this->conn->error;
			}
		}
		return $targetId;
	}

	public function editCluster($dupId, $title, $description, $notes){
		$status = true;
		$sql = 'UPDATE omoccurduplicates SET title = '.($title?'"'.$this->cleanInStr($title).'"':'NULL').', '.
			'description = '.($description?'"'.$this->cleanInStr($description).'"':'NULL').', '.
			'notes = '.($notes?'"'.$this->cleanInStr($notes).'"':'NULL').' '.
			'WHERE (duplicateid = '.$dupId.')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$this->errorStr = 'ERROR editing duplicate cluster: '.$this->conn->error;
			$status = false;
		}
		return $status;
	}
	
	public function deleteOccurFromCluster($dupId, $occid, $permissionsArr){
		$status = true;
		//If duplicate cluster only consists of two occurrences, remove whole cluster
		$rs = $this->conn->query('SELECT duplicateid FROM omoccurduplicatelink WHERE duplicateid = '.$dupId);
		if($rs->num_rows == 2){
			$sql = 'DELETE FROM omoccurduplicates WHERE (duplicateid = '.$dupId.')';
			if(!$this->conn->query($sql)){
				$this->errorStr = 'ERROR deleting duplicate cluster: '.$this->conn->error;
				$status = false;
			}
		}
		else{
			$sql = 'DELETE FROM omoccurduplicatelink WHERE (duplicateid = '.$dupId.') AND (occid = '.$occid.')';
			if(!$this->conn->query($sql)){
				$this->errorStr = 'ERROR deleting occurrence from duplicate cluster: '.$this->conn->error;
				$status = false;
			}
		}
		return $status;
	}

	public function deleteCluster($dupId){
		$status = true;
		$sql = 'DELETE FROM omoccurduplicates WHERE duplicateid = '.$dupId;
		if(!$this->conn->query($sql)){
			$this->errorStr = 'ERROR deleting duplicate cluster: '.$this->conn->error;
			$status = false;
		}
		return $status;
	}

	//Used in dupesearch.php as handler within occurrenceeditor.php
	public function getDupes($collName, $collNum, $collDate, $ometid, $exsNumber, $currentOccid){
		$retStr = '';
		//Check exsiccati, exact dupes, and then duplicate events, in that order
		$collName = $this->cleanInStr($collName);
		$collNum = $this->cleanInStr($collNum);
		$collDate = $this->cleanInStr($collDate);
		$exsNumber = $this->cleanInStr($exsNumber);
		if(!is_numeric($currentOccid)) $currentOccid = 0;
		//Check exsiccati dupes
		if(is_numeric($ometid) && $exsNumber){
			$occArr = $this->getDupesExsiccati($ometid, $exsNumber, $currentOccid);
			//Remove current occid
			if($occArr){
				$retStr = 'exsic:'.implode(',',$occArr);
			}
		}
		
		//Check for exact dupes
		if(!$retStr){
			$occArr = $this->getDupesCollector($collName, $collNum, $currentOccid);
			//Remove current occid
			if($occArr){
				$retStr = 'exact:'.implode(',',$occArr);
			}
		}
		
		//Check for duplicate events
		if(!$retStr){
			$occArr = $this->getDupesCollectorEvent($collName, $collNum, $collDate, $currentOccid);
			//Remove current occid
			if($occArr){
				$retStr = 'event:'.implode(',',$occArr);
			}
		}
		return $retStr;
	}

	private function getDupesExsiccati($ometid, $exsNumber, $currentOccid){
		$retArr = array();
		$sql = 'SELECT el.occid '.
			'FROM omexsiccatiocclink el INNER JOIN omexsiccatinumbers en ON el.omenid = en.omenid '.
			'WHERE (en.ometid = '.$ometid.') AND (en.exsnumber = "'.$exsNumber.'") AND (occid != '.$currentOccid.') ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->occid] = $r->occid;
		}
		$rs->free();
		return $retArr;
	}

	private function getDupesCollector($collName, $collNum, $skipOccid){
		$retArr = array();
		$lastName = $this->parseLastName($collName);
		if($lastName && $collNum){
			$sql = 'SELECT occid FROM omoccurrences '.
				'WHERE (recordedby LIKE "%'.$lastName.'%") AND (recordnumber = "'.$collNum.'") AND (occid != '.$skipOccid.') ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->occid] = $r->occid;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getDupesCatalogNumber($catNum, $collid, $skipOccid){
		$retArr = array();
		if(is_numeric($collid) && is_numeric($skipOccid) && $catNum){
			$catNumber = $this->cleanInStr($catNum);
			$sql = 'SELECT occid FROM omoccurrences '.
				'WHERE (catalognumber = "'.$catNumber.'") AND (collid = '.$collid.') AND (occid != '.$skipOccid.') ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->occid] = $r->occid;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getDupesOtherCatalogNumbers($otherCatNum, $collid, $skipOccid){
		$retArr = array();
		if(is_numeric($collid) && is_numeric($skipOccid) && $otherCatNum){
			$sql = 'SELECT occid FROM omoccurrences '.
				'WHERE (othercatalognumbers = "'.$this->cleanInStr($otherCatNum).'") AND (collid = '.$collid.') AND (occid != '.$skipOccid.') ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->occid] = $r->occid;
			}
			$rs->free();
		}
		return $retArr;
	}

	private function getDupesCollectorEvent($collName, $collNum, $collDate, $skipOccid){
		$retArr = array();
		$lastName = $this->parseLastName($collName);
		if($lastName){
			$sql = 'SELECT occid '.
				'FROM omoccurrences '.
				'WHERE (processingstatus IS NULL OR processingstatus != "unprocessed") AND (recordedby LIKE "%'.$lastName.'%") AND (occid != '.$skipOccid.') ';
			$runQry = true;
			if($collNum){
				if(is_numeric($collNum)){
					$nStart = $collNum - 4;
					if($nStart < 1) $nStart = 1;
					$nEnd = $collNum + 4;
					$sql .= 'AND (recordnumber BETWEEN '.$nStart.' AND '.$nEnd.') ';
				}
				elseif(preg_match('/^(\d+)-{0,1}[a-zA-Z]{1,2}$/',$collNum,$m)){
					//ex: 123a, 123b, 123-a
					$cNum = $m[1];
					$nStart = $cNum - 4;
					if($nStart < 1) $nStart = 1;
					$nEnd = $cNum + 4;
					$sql .= 'AND (CAST(recordnumber AS SIGNED) BETWEEN '.$nStart.' AND '.$nEnd.') ';
				}
				elseif(preg_match('/^(\D+-?)(\d+)-{0,1}[a-zA-Z]{0,2}$/',$collNum,$m)){
					//RM-123, RM123
					$prefix = $m[1];
					$num = $m[2];
					$nStart = $num - 5;
					if($nStart < 1) $nStart = 1;
					$rangeArr = array();
					for($x=1;$x<11;$x++){
						$rangeArr[] = $prefix.($nStart+$x);
					}
					$sql .= 'AND recordnumber IN("'.implode('","',$rangeArr).'") ';
				}
				elseif(preg_match('/^(\d{2,4}-{1})(\d+)-{0,1}[a-zA-Z]{0,2}$/',$collNum,$m)){
					//95-123, 1995-123
					$prefix = $m[1];
					$num = $m[2];
					$nStart = $num - 5;
					if($nStart < 1) $nStart = 1;
					$rangeArr = array();
					for($x=1;$x<11;$x++){
						$rangeArr[] = $prefix.($nStart+$x);
					}
					$sql .= 'AND recordnumber IN("'.implode('","',$rangeArr).'") ';
				}
				else{
					$runQry = false;
				}
				if($collDate) $sql .= 'AND (eventdate = "'.$collDate.'") ';
			}
			elseif($collDate){
				$sql .= 'AND (eventdate = "'.$collDate.'") LIMIT 10'; 
			}
			else{
				$runQry = false;
			}
			if($runQry){
				//echo $sql;
				$result = $this->conn->query($sql);
				while ($r = $result->fetch_object()) {
					$retArr[$r->occid] = $r->occid;
				}
				$result->free();
			}
		}
		return $retArr;
	}

	public function getDupesOccid($occidQuery){
		$retArr = array();
		if($occidQuery){
			$targetFields = array('family', 'sciname', 'scientificNameAuthorship', 
				'identifiedBy', 'dateIdentified', 'identificationReferences', 'identificationRemarks', 'taxonRemarks', 'identificationQualifier', 
				'recordedBy', 'recordNumber', 'associatedCollectors', 'eventDate', 'verbatimEventDate',
				'country', 'stateProvince', 'county', 'locality', 'decimalLatitude', 'decimalLongitude', 'geodeticDatum',
				'coordinateUncertaintyInMeters', 'verbatimCoordinates', 'georeferencedBy', 'georeferenceProtocol', 
				'georeferenceSources', 'georeferenceVerificationStatus', 'georeferenceRemarks', 
				'minimumElevationInMeters', 'maximumElevationInMeters', 'verbatimElevation',
				'habitat', 'substrate', 'occurrenceRemarks', 'associatedTaxa', 'dynamicProperties', 
				'verbatimAttributes','reproductiveCondition', 'cultivationStatus', 'establishmentMeans', 'typeStatus');
			$relArr = array();
			$sql = 'SELECT c.collectionName, c.institutionCode, c.collectionCode, o.occid, o.collid, o.tidinterpreted, '.
				'o.catalogNumber, o.otherCatalogNumbers, '.implode(',',$targetFields).
				' FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid '.
				'WHERE (o.occid IN('.$occidQuery.')) '.
				'ORDER BY recordnumber';
			//echo $sql;
			$result = $this->conn->query($sql);
			while($row = $result->fetch_assoc()) {
				foreach($row as $k => $v){
					$vStr = trim($v);
					$retArr[$row['occid']][$k] = $vStr;
					//Identify relevant fields
					if($vStr) $relArr[$k] = '';
				}
			}
			$result->free();
			//Adjust sort of relevant fields according to 
			foreach($targetFields as $tfVal){
				if(array_key_exists($tfVal,$relArr)) $this->relevantFields[] = $tfVal;
			}
		}
		return $retArr;
	}

	//Used in dupelist.php popup used in "Linked Resource" tab in occurrence editor
	public function getDupeList($lastName, $collNum, $collDate, $catNum, $occid, $currentOccid){
		$retArr = array();
		$queryTerms = array();
		if($lastName) $queryTerms[] = 'recordedby LIKE "%'.$this->cleanInStr($lastName).'%"';
		if($collNum) $queryTerms[] = 'recordnumber = "'.$this->cleanInStr($collNum).'"';
		if($collDate) $queryTerms[] = 'eventdate = "'.$this->cleanInStr($collDate).'"';
		if($catNum) $queryTerms[] = 'catalognumber = "'.$this->cleanInStr($catNum).'"';
		if(is_numeric($occid)) $queryTerms[] = 'occid = '.$occid;
		$sql = 'SELECT c.institutioncode, c.collectioncode, c.collectionname, o.occid, o.catalognumber, '.
			'o.recordedby, o.recordnumber, o.eventdate, o.verbatimeventdate, o.country, o.stateprovince, o.county, o.locality '.
			'FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid '.
			'WHERE o.occid != '.$currentOccid;
		if($queryTerms && is_numeric($currentOccid)){
			$sql .= ' AND ('.implode(') AND (', $queryTerms).') ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->occid]['collname'] = $r->collectionname.' ('.$r->institutioncode.($r->collectioncode?'-'.$r->collectioncode:'').')';
				$retArr[$r->occid]['catalognumber'] = $r->catalognumber;
				$retArr[$r->occid]['recordedby'] = $r->recordedby;
				$retArr[$r->occid]['recordnumber'] = $r->recordnumber;
				$retArr[$r->occid]['eventdate'] = $r->eventdate;
				$retArr[$r->occid]['verbatimeventdate'] = $r->verbatimeventdate;
				$retArr[$r->occid]['country'] = $r->country;
				$retArr[$r->occid]['stateprovince'] = $r->stateprovince;
				$retArr[$r->occid]['county'] = $r->county;
				$retArr[$r->occid]['locality'] = $r->locality;
			}
			$rs->free();
		}
		return $retArr;
	}
	
	//Ranking functions
	private function addConsensusRecord($occArr){
		
		return $occArr;
	}

	private function rankGeneric($occArr){
		//
		
		return $occArr;
	}

	private function rankOcr($occArr){
		//Number of fields with match 
		//recordNumber, verbatimEventDate, associatedCollectors, 
		//stateProvince, county, locality, verbatimCoordinates, verbatimElevation, 
		//habitat, substrate, verbatimAttributes, occurrenceRemarks
		
		return $occArr;
	}

	//Smith-Waterman code... 
	
	
	
	//Action functions
	public function mergeRecords($targetOccid,$sourceOccid){
		$status = true;
		$editorManager = new OccurrenceEditorManager($this->conn);
		if($editorManager->mergeRecords($targetOccid,$sourceOccid)){
			if(!$editorManager->deleteOccurrence($sourceOccid)){
				$this->errorStr = $editorManager->getErrorStr();
			}
		}
		else{
			$this->errorStr = $editorManager->getErrorStr;
			$status = false;
		}
		return $status;
	} 

	//Batch functions
	public function getDuplicateClusterList($collid, $limitToConflicts,$start,$limit){
		$retArr = array();
		if($collid){
			//Grab clusters
			$sqlPrefix = 'SELECT DISTINCT d.duplicateid, d.title, d.description, d.notes ';
			$sqlSuffix = '';
			if($limitToConflicts){
				$sqlSuffix = 'FROM omoccurduplicates d INNER JOIN omoccurduplicatelink dl1 ON d.duplicateid = dl1.duplicateid '.
					'INNER JOIN omoccurrences o ON dl1.occid = o.occid '.
					'INNER JOIN omoccurduplicatelink dl2 ON d.duplicateid = dl2.duplicateid '.
					'INNER JOIN omoccurrences o2 ON dl2.occid = o2.occid '.
					'WHERE o.collid = '.$collid.($this->obsUid?' AND o.observeruid = '.$this->obsUid:'').' AND o.tidinterpreted <> o2.tidinterpreted ';
			}
			else{
				$sqlSuffix = 'FROM omoccurduplicates d INNER JOIN omoccurduplicatelink dl ON d.duplicateid = dl.duplicateid '.
					'INNER JOIN omoccurrences o ON dl.occid = o.occid '.
					'WHERE o.collid = '.$collid.($this->obsUid?' AND o.observeruid = '.$this->obsUid:'').' ';
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

	public function batchLinkDuplicates($collid = 0, $verbose = true){
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
						$lastName = $this->parseLastName($r2->recordedby);
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
								$sqlI1 = 'INSERT INTO omoccurduplicates(title,dupetype) VALUES("'.$this->cleanInStr($dupIdStr).'",1)';
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

	//Misc functions
	public function parseLastName($collName){
		$lastName = '';
		$collName = trim($collName);
		if(!$collName) return '';
		$primaryArr = explode(';',$collName);
		$primaryArr = explode('&',$primaryArr[0]);
		$primaryArr = explode(' and ',$primaryArr[0]);
		$lastNameArr = explode(',',$primaryArr[0]);
		if(count($lastNameArr) > 1){
			//formats: Last, F.I.; Last, First I.; Last, First Initial
			$lastName = array_shift($lastNameArr);
			//$lastName = array_shift(explode(' ',$lastName));
		}
		else{
			//Formats: F.I. Last; First I. Last; First Initial Last
			$tempArr = explode(' ',$lastNameArr[0]);
			$lastName = array_pop($tempArr);
			while($tempArr && (strpos($lastName,'.') || $lastName == 'III' || strlen($lastName)<3)){
				$lastName = array_pop($tempArr);
			}
		}
		return $lastName;
	}

	//Setters and getters
	public function getRelevantFields(){
		return $this->relevantFields;
	}

	public function setObsuid($obsUid){
		if(is_numeric($obsUid)){
			$this->obsUid = $obsUid;
		}
	}

	public function getErrorStr(){
		return $this->errorStr;
	}

	public function getCollMap($collid){
		$returnArr = Array();
		if($collid){
			$sql = 'SELECT c.institutioncode, c.collectioncode, c.collectionname, '.
				'c.icon, c.colltype, c.managementtype '.
				'FROM omcollections c '.
				'WHERE (c.collid = '.$collid.') ';
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

	//Misc functions
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>