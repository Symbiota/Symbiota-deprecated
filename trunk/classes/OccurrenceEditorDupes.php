<?php
include_once($serverRoot.'/config/dbconnection.php');

class OccurrenceEditorDupes {

	private $conn;
	private $targetFields;
	private $relevantFields = array();

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
		$this->targetFields = array('family', 'sciname', 'scientificNameAuthorship', 'taxonRemarks', 
			'identifiedBy', 'dateIdentified', 'identificationReferences', 'identificationRemarks', 'identificationQualifier', 
			'recordedBy', 'recordNumber', 'associatedCollectors', 'eventDate', 'verbatimEventDate',
			'country', 'stateProvince', 'county', 'locality', 'decimalLatitude', 'decimalLongitude', 'geodeticDatum',
			'coordinateUncertaintyInMeters', 'verbatimCoordinates', 'georeferencedBy', 'georeferenceProtocol', 
			'georeferenceSources', 'georeferenceVerificationStatus', 'georeferenceRemarks', 
			'minimumElevationInMeters', 'maximumElevationInMeters', 'verbatimElevation',
			'habitat', 'substrate', 'occurrenceRemarks', 'associatedTaxa', 'dynamicProperties', 
			'verbatimAttributes','reproductiveCondition', 'cultivationStatus', 'establishmentMeans', 'typeStatus');
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	//Used in dupesearch.php
	public function getDupes($collName, $collNum, $collDate, $ometid, $exsNumber, $currentOccid){
		$retStr = '';
		//Check exsiccati, exact dupes, and then duplicate events, in that order
		$collName = $this->cleanInStr($collName);
		$collNum = $this->cleanInStr($collNum);
		$collDate = $this->cleanInStr($collDate);
		$exsNumber = $this->cleanInStr($exsNumber);
		//Check exsiccati dupes
		if($ometid && $exsNumber){
			$occArr = $this->getDupesExsiccati($ometid, $exsNumber);
			//Remove current occid
			unset($occArr[$currentOccid]);
			if($occArr){
				$retStr = 'exsic:'.implode(',',$occArr);
			}
		}

		//Check for exact dupes
		if(!$retStr){
			$occArr = $this->getDupesCollector($collName, $collNum, $collDate);
			//Remove current occid
			unset($occArr[$currentOccid]);
			if($occArr){
				$retStr = 'exact:'.implode(',',$occArr);
			}
		}
		
		//Check for duplicate events
		if(!$retStr){
			$occArr = $this->getDupesCollectorEvent($collName, $collNum, $collDate);
			//Remove current occid
			unset($occArr[$currentOccid]);
			if($occArr){
				$retStr = 'event:'.implode(',',$occArr);
			}
		}
		return $retStr;
	}

	private function getDupesExsiccati($ometid, $exsNumber){
		$retArr = array();
		if($ometid && is_numeric($ometid) && $exsNumber){
			$sql = 'SELECT el.occid '.
				'FROM omexsiccatiocclink el INNER JOIN omexsiccatinumbers en ON el.omenid = en.omenid '.
				'WHERE (en.ometid = '.$ometid.') AND (en.exsnumber = "'.$exsNumber.'") ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->occid] = $r->occid;
			}
			$rs->free();
		}
		return $retArr;
	}

	private function getDupesCollector($collName, $collNum, $collDate){
		$retArr = array();
		$lastName = $this->parseLastName($collName);
		if($lastName && $collNum){
			$sql = 'SELECT occid, processingstatus FROM omoccurrences '.
				'WHERE (recordedby LIKE "%'.$lastName.'%") AND (recordnumber = "'.trim($collNum).'") ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if(!$r->processingstatus || $r->processingstatus != 'unprocessed'){
					$retArr[$r->occid] = $r->occid;
				}
			}
			$rs->free();
		}
		return $retArr;
	}

	private function getDupesCollectorEvent($collName, $collNum, $collDate){
		$retArr = array();
		$lastName = $this->parseLastName($collName);
		if($lastName){
			$sql = 'SELECT occid '.
				'FROM omoccurrences '.
				'WHERE (processingstatus IS NULL OR processingstatus != "unprocessed") AND (recordedby LIKE "%'.$lastName.'%") ';
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
			$relArr = array();
			$sql = 'SELECT c.collectionName, c.institutionCode, c.collectionCode, o.occid, o.collid, o.tidinterpreted, '.
				'o.catalogNumber, o.otherCatalogNumbers, '.implode(',',$this->targetFields).
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
			foreach($this->targetFields as $tfVal){
				if(array_key_exists($tfVal,$relArr)) $this->relevantFields[] = $tfVal;
			}
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
		//stateProvince, county, locality, verbatimCoordiantes, verbatimElevation, 
		//habitat, substrate, verbatimAttributes, occurrenceRemarks
		
		return $occArr;
	}

	//Smith-Waterman code... 
	
	
	
	//Action functions
	public function mergeRecords($targetOccid,$sourceOccid){
		if(!$targetOccid || !$sourceOccid) return 'ERROR: target or source is null';
		if($targetOccid == $sourceOccid) return 'ERROR: target and source are equal';
		$status = true;
		
		$connWrite = MySQLiConnectionFactory::getCon("write");
		
		
		//Merge records
		$tArr = array();
		$sArr = array();
		$sql = 'SELECT * FROM omoccurrences WHERE occid = '.$targetOccid.' OR occid = '.$sourceOccid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_assoc()){
			$tempArr = array_change_key_case($r);
			$id = $tempArr['occid'];
			unset($tempArr['occid']);
			unset($tempArr['collid']);
			unset($tempArr['dbpk']);
			unset($tempArr['datelastmodified']);
			if($id == $targetOccid){
				$tArr = $tempArr;
			}
			else{
				$sArr = $tempArr;
			}
			$oArr[$id] = $tempArr;
		}
		$rs->free();

		$sqlFrag = '';
		foreach($sArr as $k => $v){
			if(($v != '') && $tArr[$k] == ''){
				$sqlFrag .= ','.$k.'="'.str_replace('"','\"',$v).'"';
			}
		}
		if($sqlFrag){
			//Remap source to target
			$sqlIns = 'UPDATE omoccurrences SET '.substr($sqlFrag,1).' WHERE occid = '.$targetOccid;
			//echo $sqlIns;
			$connWrite->query($sqlIns);
		}

		//Remap determinations
		$sql = 'UPDATE omoccurdeterminations SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$connWrite->query($sql);

		//Remap occurrence edits
		$sql = 'UPDATE omoccuredits SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$connWrite->query($sql);

		//Remap images
		$sql = 'UPDATE images SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$connWrite->query($sql);

		//Remap comments
		$sql = 'UPDATE omoccurcomments SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$connWrite->query($sql);

		//Remap genetic resources
		$sql = 'UPDATE omoccurgenetic SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$connWrite->query($sql);
		
		//Remap identifiers 
		$sql = 'UPDATE omoccuridentifiers SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$connWrite->query($sql);

		//Remap exsiccati
		$sql = 'UPDATE omexsiccatiocclink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$connWrite->query($sql);

		//Remap occurrence dataset links
		$sql = 'UPDATE omoccurdatasetlink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$connWrite->query($sql);

		//Remap loans
		$sql = 'UPDATE omoccurloanslink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$connWrite->query($sql);

		//Remap checklists voucher links
		$sql = 'UPDATE fmvouchers SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$connWrite->query($sql);

		//Remap survey lists
		$sql = 'UPDATE omsurveyoccurlink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$connWrite->query($sql);

		//Delete source
		$sql = 'DELETE FROM omoccurrences WHERE occid = '.$sourceOccid;
		if(!$connWrite->query($sql)){
			$status .= 'ERROR: unable to delete source occurrence (yet may have merged records): '.$connWrite->error;
		}
		return $status;
	}

	//Setter and getters
	public function getRelevantFields(){
		return $this->relevantFields;
	}

	//Misc functions
	private function parseLastName($collName){
		//Parse last name from collector's full name 
		$lastNameArr = explode(',',$collName);
		$lastNameArr = explode(';',$lastNameArr[0]);
		$lastNameArr = explode('&',$lastNameArr[0]);
		$lastNameArr = explode(' and ',$lastNameArr[0]);
		preg_match_all('/[A-Za-z]{3,}/',$lastNameArr[0],$match);
		$lastName = '';
		if($match){
			if(count($match[0]) == 1){
				$lastName = $match[0][0];
			}
			elseif(count($match[0]) > 1){
				$lastName = $match[0][1];
			}
		}
		return $lastName;
	}

	private function cleanOutArr($inArr){
		$outArr = array();
		foreach($inArr as $k => $v){
			$outArr[$k] = $this->cleanOutStr($v);
		}
		return $outArr;
	}

	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		$newStr = str_replace(array("\t","\n","\r"),"",$newStr);
		return $newStr;
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>