<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class GeoreferencingTools {

	private $conn;
	private $collId;
	private $collName;
	private $qryVars = array();

	function __construct($type = 'readonly') {
		$this->conn = MySQLiConnectionFactory::getCon($type);
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}
	
	public function getLocalityArr(){
		$retArr = array();
		$sql = 'SELECT locality, CONCAT_WS("; ",county, verbatimcoordinates, CONCAT(minimumelevationinmeters,"m.")) AS extra, '. 
			'georeferencesources, verbatimcoordinates '. 
			'FROM omoccurrences '. 
			'WHERE (collid = '.$this->collId.') AND (locality IS NOT NULL) ';
		if($this->qryVars){
			if(array_key_exists('vstatus',$this->qryVars)){
				$sql .= 'AND ((decimalLatitude IS NULL) OR (georeferenceVerificationStatus LIKE "'.$this->qryVars['vstatus'].'%")) ';
			}
			else{
				$sql .= 'AND (decimalLatitude IS NULL) AND (georeferenceVerificationStatus IS NULL) ';
			}
			foreach($this->qryVars as $k => $v){
				if($v && $k != 'vstatus'){
					if($k == 'locality'){
						$sql .= 'AND (locality LIKE "%'.$v.'%") ';
					}
					elseif($k == 'county'){
						$sql .= 'AND (county LIKE "'.$v.'%") ';
					}
					else{
						$sql .= 'AND ('.$k.' = "'.$v.'") ';
					}
				}
			}
		}
		$sql .= 'ORDER BY locality,county';
		//echo $sql;
		$totalCnt = 0;
		$locCnt = 1;
		$locStr = '';$extraStr = '';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($locStr != $r->locality || $extraStr != $r->extra){
				$locStr = trim($r->locality);
				$extraStr = trim($r->extra);
				$retArr[$totalCnt]['locality'] = $locStr;
				$retArr[$totalCnt]['extra'] = $extraStr;
				$retArr[$totalCnt]['cnt'] = 1;
				$totalCnt++;
				$locCnt = 1;
			}
			else{
				$locCnt++;
				$retArr[$totalCnt]['cnt'] = $locCnt;
			}
		}
		$rs->close();
		return $retArr;
	}
	
	public function getCoordStatistics(){
		$retArr = array();
		$sql = 'SELECT COUNT(occid) AS cnt '. 
			'FROM omoccurrences '. 
			'WHERE (collid = '.$this->collId.') AND (decimalLatitude IS NULL) AND georeferenceSources IS NULL ';
		if($this->qryVars){
			foreach($this->qryVars as $k => $v){
				$sql .= 'AND '.$k.' = "'.$v.'" ';
			}
		}
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->retArr['limitedcnt'] = $r->cnt;
		}
		$rs->close();
		
		$sql = 'SELECT COUNT(occid) AS cnt '. 
			'FROM omoccurrences '. 
			'WHERE (collid = '.$this->collId.')'; 
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->retArr['totalcnt'] = $r->cnt;
		}
		$rs->close();
		
		return $retArr;
	} 

	public function setCollId($cid){
		$this->collId = $cid;
		$sql = 'SELECT collectionname FROM omcollections WHERE collid = '.$cid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->collName = $r->collectionname;
		}
		$rs->close();
	}
	
	public function setQueryVariables($k,$v){
		$this->qryVars[$k] = $v;
	}

	public function getCollName(){
		return $this->collName;
	}

	public function getCountryArr(){
		$retArr = array();
		$sql = 'SELECT DISTINCT country '.
			'FROM omoccurrences WHERE collid = '.$this->collId.' ORDER BY country';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$cStr = trim($r->country);
			if($cStr) $retArr[] = $cStr;
		}
		$rs->close();
		return $retArr;
	}
	
	public function getStateArr($countryStr = ''){
		$retArr = array();
		$sql = 'SELECT DISTINCT stateprovince '.
			'FROM omoccurrences WHERE collid = '.$this->collId.' ';
		if($countryStr){
			$sql .= 'AND country = "'.$countryStr.'" ';
		}
		$sql .= 'ORDER BY stateprovince';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$sStr = trim($r->stateprovince);
			if($sStr) $retArr[] = $sStr;
		}
		$rs->close();
		return $retArr;
	}
	
	public function getCountyArr($countryStr = '',$stateStr = ''){
		$retArr = array();
		$sql = 'SELECT DISTINCT county '.
			'FROM omoccurrences WHERE collid = '.$this->collId.' ';
		if($countryStr){
			$sql .= 'AND country = "'.$countryStr.'" ';
		}
		if($stateStr){
			$sql .= 'AND stateprovince = "'.$stateStr.'" ';
		}
		$sql .= 'ORDER BY county';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$cStr = trim($r->county);
			if($cStr) $retArr[] = $cStr;
		}
		$rs->close();
		return $retArr;
	}
	
	private function cleanStr($str){
 		$newStr = trim($str);
 		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
 		$newStr = str_replace('"',"'",$newStr);
 		$newStr = $this->clCon->real_escape_string($newStr);
 		return $newStr;
 	}
}
?> 