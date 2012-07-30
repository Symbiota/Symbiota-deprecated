<?php
include_once($serverRoot.'/config/dbconnection.php');

class OccurrenceEditorDupes {

	private $conn;
	private $sql = '';

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
		$this->sql = 'SELECT c.CollectionName, c.institutioncode, c.collectioncode, '.
			'o.occid, o.collid AS colliddup, o.catalognumber, o.occurrenceid, o.othercatalognumbers, '.
			'o.family, o.sciname, o.tidinterpreted AS tidtoadd, o.scientificNameAuthorship, o.taxonRemarks, o.identifiedBy, o.dateIdentified, '.
			'o.identificationReferences, o.identificationRemarks, o.identificationQualifier, o.typeStatus, o.recordedBy, o.recordNumber, '.
			'o.associatedCollectors, o.eventdate, o.verbatimEventDate, o.habitat, o.substrate, o.occurrenceRemarks, o.associatedTaxa, '.
			'o.dynamicProperties, o.reproductiveCondition, o.cultivationStatus, o.establishmentMeans, '.
			'o.country, o.stateProvince, o.county, o.locality, o.decimalLatitude, o.decimalLongitude, '.
			'o.geodeticDatum, o.coordinateUncertaintyInMeters, o.coordinatePrecision, o.locationRemarks, o.verbatimCoordinates, '.
			'o.georeferencedBy, o.georeferenceProtocol, o.georeferenceSources, o.georeferenceVerificationStatus, o.georeferenceRemarks, '.
			'o.minimumElevationInMeters, o.maximumElevationInMeters, o.verbatimElevation, o.disposition ';
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	//Used in dupesearch.php
	public function getDupesCollector($collName, $collNum, $collDate, $currentOccid){
		$collNum = $this->conn->real_escape_string($collNum);
		$collDate = $this->conn->real_escape_string($collDate);
		$retArr = array();
		$lastName = "";
		//Parse last name from collector's name 
		$lastNameArr = explode(',',$this->conn->real_escape_string($collName));
		$lastNameArr = explode(';',$lastNameArr[0]);
		$lastNameArr = explode('&',$lastNameArr[0]);
		$lastNameArr = explode(' and ',$lastNameArr[0]);
		$lastNameArr = preg_match_all('/[A-Za-z]{3,}/',$lastNameArr[0],$match);
		if($match){
			if(count($match[0]) == 1){
				$lastName = $match[0][0];
			}
			elseif(count($match[0]) > 1){
				$lastName = $match[0][1];
			}
		}
		if($lastName && $collNum){
			$sql = 'SELECT occid FROM omoccurrences ';

			$sql .= 'WHERE (recordedby LIKE "%'.$lastName.'%") '.
				'AND (recordnumber LIKE "'.$collNum.'") ';
			if($currentOccid) $sql .= 'AND (occid != '.$currentOccid.') ';
	
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$retArr[] = $row->occid;
			}
			$rs->free();
		}
		if(!$retArr && $collDate){
			$retArr = $this->getDupesCollectorEvent($lastName, $collNum, $collDate, $currentOccid);
		}
		return $retArr;
	}
	
	private function getDupesCollectorEvent($lastName, $collNum, $collDate, $currentOccid){
		$retArr = array();
		if($lastName){
			$sql = 'SELECT occid FROM omoccurrences '.
				'WHERE (recordedby LIKE "%'.$lastName.'%") ';
			if($currentOccid) $sql .= 'AND (occid != '.$currentOccid.') ';
			$runQry = true;
			if($collNum){
				if(is_numeric($collNum)){
					$nStart = $collNum - 4;
					if($nStart < 1) $nStart = 1;
					$nEnd = $collNum + 4;
					$sql .= 'AND (CAST(recordnumber AS SIGNED) BETWEEN '.$nStart.' AND '.$nEnd.') ';
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
				while ($row = $result->fetch_object()) {
					$retArr[] = $row->occid;
				}
				$result->free();
			}
		}
		return $retArr;
	}
	
	public function getDupesExsiccati($exsTitle, $exsNumber, $oid){
		$retArr = array();
		if($exsTitle && $exsNumber){
			$this->sql .= 'FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid '. 
				'INNER JOIN omexsiccatiocclink el ON o.occid = el.occid '.
				'INNER JOIN omexsiccatinumbers en ON el.omenid = en.omenid '.
				'INNER JOIN omexsiccatititles et ON en.ometid = et.ometid '.
				'WHERE (et.title = "'.$exsTitle.'" OR et.abbreviation = "'.$exsTitle.'") AND en.exsnumber = "'.$exsNumber.'" ';
			if($oid) $this->sql .= 'AND (o.occid != '.$oid.') ';
			//First run

			//echo $this->sql;
			$rs = $this->conn->query($this->sql);
			while($row = $rs->fetch_assoc()){
				$retArr[$row['occid']] = array_change_key_case($row);
			}
			$rs->free();
		}
		return $retArr;
	}
	
	public function getDupesOccid($occidQuery){
		$retArr = array();
		if($occidQuery){
			$this->sql .= 'FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid ';
			if(strpos($occidQuery,',')){
				$this->sql .= 'WHERE (o.occid IN('.$occidQuery.')) ';
			}
			else{
				$this->sql .= 'WHERE (o.occid = '.$occidQuery.') ';
			}
			$this->sql .= 'ORDER BY recordnumber';
			//echo $sql;
			$result = $this->conn->query($this->sql);
			while ($row = $result->fetch_assoc()) {
				$retArr[$row['occid']] = array_change_key_case($row);
			}
			$result->free();
		}
		return $retArr;
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
			$tempArr = array();
			foreach($r as $k => $v){
				$tempArr[strtolower($k)] = $v;
			}
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

		//Remap occurrence edits
		$sql = 'UPDATE omoccuredits SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
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
			$status .= 'ERROR: unable to delete source occurrence (yet may have merged records): '.$this->conn->error;
		}
		return $status;
	}

	//Misc functions
	private function cleanStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>