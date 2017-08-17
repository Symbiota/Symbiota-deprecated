<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class OccurrenceGeoLocate {

	private $conn;
	private $collid;
	private $collMetadata = array();
	private $filterArr = array();
	private $errorStr;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon('write');
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	//Fucntions for converting TRS data to decimal lat/long
	public function batchConvertTrs($limit = 100){
		$occArr = $this->getTrsOccurrences();
		$this->preCleanTrsOccurrences($occArr);
		$processedArr = $this->submitTrsOccurrencesToGeoLocate($occArr);
		$this->postCleanTrsOccurrences($processedArr);
		return $processedArr;
	}

	private function getTrsOccurrenceCount($limit){
		$cnt = 0;
		$sql = 'SELECT COUNT(*) AS cnt '.
			'FROM omoccurrences '.$this->getTrsSqlWhere();;
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$cnt = $r->cnt;
		}
		$rs->free();
		return $cnt;
	}

	private function getTrsOccurrences($limit){
		$retArr = array();
		if($this->collid){
			$sql = 'SELECT occid, country, stateprovince, county, verbatimCoordinates, '. 
				'if(verbatimCoordinates like "%TRS:%", trim(substr(verbatimCoordinates, instr(verbatimCoordinates, "TRS:")+4, length(verbatimCoordinates))), verbatimCoordinates) AS verbcoords '.
				'FROM omoccurrences '.$this->getTrsSqlWhere();
			if($limit) $sql .= 'LIMIT '.$limit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->occid]['country'] = $r->country;
				$retArr[$r->occid]['state'] = $r->stateprovince;
				$retArr[$r->occid]['county'] = $r->county;
				$retArr[$r->occid]['verbcoords'] = $r->verbcoords;
			}
			$rs->free();
		}
		return $retArr;
	}
	
	private function getTrsSqlWhere(){
		$sql = 'WHERE (collid = '.$this->collid.') AND (county IS NOT NULL) AND (decimalLatitude IS NULL) '.
			'AND (locality regexp "T\\.? ?[0-9]{1,3}?[NS]\\.?,? ?R\\.? ?[0-9]{1,3} ?[EW]\\.?,? ?.*" '.
			'OR verbatimCoordinates regexp "T\\.? ?[0-9]{1,3} ?[NS]\\.?,? ?R\\.? ?[0-9]{1,3} ?[EW]\\.?,? ?.*") ';
		//Allowed filtering fields: country, stateProvince, county, locality
		if(isset($this->filterArr['country']) && $this->filterArr['country']){
			$sql .= 'AND (country = "'.$this->cleanInStr($this->filterArr['country']).'" ';
		}
		if(isset($this->filterArr['stateProvince']) && $this->filterArr['stateProvince']){
			$sql .= 'AND (stateProvince = "'.$this->cleanInStr($this->filterArr['stateProvince']).'" ';
		}
		if(isset($this->filterArr['county']) && $this->filterArr['county']){
			$countyTerm = $this->cleanInStr($this->filterArr['county']);
			$countyTerm = str_replace(array(' county',' parish'),'',$countyTerm);
			$sql .= 'AND (county LIKE "'.$countyTerm.'%" ';
		}
		if(isset($this->filterArr['locality']) && $this->filterArr['locality']){
			$sql .= 'AND (locality LIKE "%'.$this->cleanInStr($this->filterArr['locality']).'%" ';
		}
		return $sql;
	}

	private function preCleanTrsOccurrences(&$occArr){
		if($occArr){
			foreach($occArr as $occid => $oArr){
				//Cleaning tasks
				
				
			}
		}
	}

	private function postCleanTrsOccurrences(&$occArr){
		if($occArr){
			foreach($occArr as $occid => $oArr){
				//Cleaning tasks
				
				
			}
		}
	}

	private function submitTrsOccurrencesToGeoLocate($occArr){
		$retArr = array();
		//Process to submit occurrence array to GeoLocate
		
		
		return $retArr;
	}
	
	//Batch georeference localities using GeoLocate tools
	public function batchGeoLocateLocalities($limit = 100){
		$occArr = $this->getOccurrences();
		$this->preCleanOccurrences($occArr);
		$processedArr = $this->submitOccurrencesToGeoLocate($occArr);
		$this->postCleanOccurrences($processedArr);
		$this->loadOccurrences($processedArr);
	}

	private function getOccurrences($limit){
		$retArr = array();
		if($this->collid){
			$sql = 'SELECT occid, country, stateprovince, county, locality, verbatimcoordinates '.
				'FROM omoccurrences '.
				'WHERE (collid = '.$this->collid.') AND (county IS NOT NULL) AND (locality IS NOT NULL) AND (decimalLatitude IS NULL) ';
			if($limit) $sql .= 'LIMIT '.$limit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->occid]['country'] = $r->country;
				$retArr[$r->occid]['state'] = $r->stateprovince;
				$retArr[$r->occid]['county'] = $r->county;
				$retArr[$r->occid]['locality'] = $r->locality;
				$retArr[$r->occid]['verbcoords'] = $r->verbcoords;
			}
			$rs->free();
		}
		return $retArr;
	}

	private function preCleanOccurrences(&$occArr){
		if($occArr){
			foreach($occArr as $occid => $oArr){
				//Cleaning tasks
				
				
			}
		}
	}

	private function postCleanOccurrences(&$occArr){
		if($occArr){
			foreach($occArr as $occid => $oArr){
				//Cleaning tasks
				
				
			}
		}
	}

	private function submitOccurrencesToGeoLocate($occArr){
		$retArr = array();
		//Process to submit occurrence array to GeoLocate
		
		
		return $retArr;
	}
	
	public function loadOccurrences($postArr){
		$sql = 'UPDATE occurrences ';
		foreach($postArr as $fieldName => $fieldValue){
			//Still need to complete
			$occid = '';
			$decLat = '';
			$decLng = '';
			$coordErr = '';
			if(is_numeric($occid) && is_numeric($decLat) && is_numeric($decLng) && is_numeric($coordErr)){
				$sql .= 'SET decimallatitude = '.$decLat.', decimallongitude = '.$decLng.', coordinateErrorInMeters = '.$coordErr.
					', georeferenceSource = CONCAT("Batch georeferences using GeoLocate services (",curdate(),")") '.
					'WHERE (occid = '.$occid.') AND (decimallatitude IS NULL) AND (decimallongitude IS NULL) ';
				if(!$this->conn->query($sql)){
					$this->errorStr = 'ERROR loading georef data: '.$this->conn->query();
				}
			}
		}
	}

	//Setters and getters
	public function setCollId($cid){
		if(is_numeric($cid)){
			$this->collid = $cid;
			$sql = 'SELECT collectionname, managementtype '.
				'FROM omcollections WHERE collid = '.$cid;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->collArr['name'] = $r->collectionname;
				$this->collArr['mtype'] = $r->managementtype;
			}
			$rs->free();
		}
	}
	
	public function setFilterArr($inArr){
		$this->filterArr = $inArr;
	}

	public function addFilterTerm($term, $value){
		$this->filterArr[$term] = $value;
	}

	//Misc functions
	private function cleanInArr(&$arr){
		$retArr = array();
		foreach($arr as $k => $v){
			$retArr[$k] = $this->cleanInStr($v);
		}
		return $retArr;
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>