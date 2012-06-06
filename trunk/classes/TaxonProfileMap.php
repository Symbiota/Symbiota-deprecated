<?php
include_once($serverRoot.'/config/dbconnection.php');

class TaxonProfileMap {

	private $sourceIcon = Array();
	private $conn;
	private $tid;
	private $sciName;
	private $taxaArr = array();
	private $taxaMap = array();
	private $synMap = array();
	private $childLoopCnt = 0;
	
	public function __construct(){
		global $clientRoot;
		$this->sourceIcon[] = $clientRoot."/images/google/smpin_red.png";
		$this->sourceIcon[] = $clientRoot."/images/google/smpin_blue.png";
		$this->sourceIcon[] = $clientRoot."/images/google/smpin_yellow.png";
		$this->sourceIcon[] = $clientRoot."/images/google/smpin_green.png";
		$this->sourceIcon[] = $clientRoot."/images/google/smpin_purple.png";
		$this->sourceIcon[] = $clientRoot."/images/google/smpin_brown.png";
		$this->sourceIcon[] = $clientRoot."/images/google/smpin_gray.png";
		$this->sourceIcon[] = $clientRoot."/images/google/smpin_orange.png";
		$this->sourceIcon[] = $clientRoot."/images/google/smpin_black.png";
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
	}

	public function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
	}

	public function setTaxon($tValue){
		if($tValue){
			$taxonValue = $this->conn->real_escape_string($tValue);
			$sql = 'SELECT t.tid, t.sciname FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted ';
			if(is_numeric($taxonValue)){
				$sql .= 'WHERE (ts.tid = '.$taxonValue.') AND (ts.taxauthid = 1)';
			}
			else{
				$sql .= 'INNER JOIN taxa t2 ON ts.tid = t2.tid WHERE (t2.sciname = "'.$taxonValue.'") AND (ts.taxauthid = 1)';
			}
			//echo '<div>'.$sql.'</div>';
			$result = $this->conn->query($sql);
			while($r = $result->fetch_object()){
				$this->tid = $r->tid;
				$this->sciName = $r->sciname;
			}
			$result->close();
			//Add subject
			if($this->tid){
				$this->taxaArr[$this->tid] = $this->sciName;
				//Get accepted children 
				$this->taxaArr = $this->taxaArr + $this->getChildren(array($this->tid));
				//Seed $synMap with accepted names
				$taxaKeys = array_keys($this->taxaArr);
				$this->synMap = array_combine($taxaKeys,$taxaKeys);
				//Add synonyms to $synMap
				$this->setSynonyms($taxaKeys);
			}
		}
	}
	
    public function getGeoCoords($limit = 1000){
		global $userRights, $mappingBoundaries;
		
		$coordArr = Array();
		if($this->synMap){
			$useBoundingBox = false;
			$boundArr = array();
			if(isset($mappingBoundaries)){
				$boundArr = explode(";",$mappingBoundaries);
			}
			
	        $querySql = '';
	        $sql = 'SELECT o.occid, o.tidinterpreted, o.decimallatitude, o.decimallongitude, o.collid, ';
	       	$sql .= 'CONCAT_WS(" ", o.recordedBy, o.recordNumber, CONCAT(" [",c.institutioncode,"]")) AS descr ';
	        $sql .= 'FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid ';
	        $sql .= 'WHERE (o.tidinterpreted IN('.implode(',',array_keys($this->synMap)).')) '.
	        	'AND (o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL) ';
			if(array_key_exists("SuperAdmin",$userRights) || array_key_exists("CollAdmin",$userRights) || array_key_exists("RareSppAdmin",$userRights) || array_key_exists("RareSppReadAll",$userRights)){
				//Is global rare species reader, thus do nothing to sql and grab all records
			}
			elseif(array_key_exists("RareSppReader",$userRights)){
				$sql .= 'AND ((o.CollId IN ('.implode(',',$userRights["RareSppReader"]).')) OR (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL)) ';
			}
			else{
				$sql .= 'AND (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL) ';
			}
			if($limit && is_numeric($limit)){
				$sql .= 'LIMIT '.$limit;
			}
			else{
				$sql .= 'LIMIT 1000';
			}
			//echo "<div>SQL: ".$sql."</div>";
			$latMin = 90; $latMax = -90; $lngMin = 180; $lngMax = -180; 
	        $result = $this->conn->query($sql);
	        while($row = $result->fetch_object()){
	        	$lat = round($row->decimallatitude,5);
	        	$lng = round($row->decimallongitude,5);
	        	if(!$useBoundingBox && $boundArr && $lat < $boundArr[0] && $lat > $boundArr[2] && $lng < $boundArr[1] && $lng > $boundArr[3]){
					$useBoundingBox = true;
	        	}
				if($lat < $latMin) $latMin = $lat;
				if($lat > $latMax) $latMax = $lat;  
				if($lng < $lngMin) $lngMin = $lng;
				if($lng > $lngMax) $lngMax = $lng;
	        	$llStr = $lat.','.$lng; 
				$coordArr[$llStr][$row->occid]['d'] = $row->descr;
				$coordArr[$llStr][$row->occid]['tid'] = $row->tidinterpreted;
				//$this->taxaMap[$row->tidinterpreted] = '';
			}
			$result->close();
	
			//Add map boundaries
			if(!$boundArr 
				|| ($latMax < $boundArr[0] && $lngMax < $boundArr[1] && $latMin > $boundArr[2] && $lngMin > $boundArr[3])
				|| ($latMin > $boundArr[0] || $latMax < $boundArr[2] || $lngMin > $boundArr[1] || $lngMax < $boundArr[3])){
				$useBoundingBox = false;
			}
			
			$coordArr['latmax'] = ($useBoundingBox?$boundArr[0]:$latMax);
			$coordArr['lngmax'] = ($useBoundingBox?$boundArr[1]:$lngMax);
			$coordArr['latmin'] = ($useBoundingBox?$boundArr[2]:$latMin);
			$coordArr['lngmin'] = ($useBoundingBox?$boundArr[3]:$lngMin);
		}
		return $coordArr;
	}

	private function getChildren($inArr){
		$retArr = array();
		if($inArr){
			$sql = 'SELECT t.tid, t.sciname FROM taxstatus ts INNER JOIN taxa t ON ts.tid = t.tid '.
				'WHERE ts.taxauthid = 1 AND ts.parenttid IN('.implode(',',$inArr).') AND (ts.tid = ts.tidaccepted)';
			//echo '<div>SQL: '.$sql.'</div>';
	        $rs = $this->conn->query($sql);
	        while($r = $rs->fetch_object()){
	        	$retArr[$r->tid] = $r->sciname;
	        }
			$rs->close();
			if($retArr && count(array_intersect($retArr,$inArr)) < count($retArr) && $this->childLoopCnt < 5){
				$retArr = $retArr + $this->getChildren(array_keys($retArr));
			}
			$this->childLoopCnt++;
		}
		return $retArr;
	}

	private function setSynonyms($inArray){
		if($inArray){
			$sql = 'SELECT tid, tidaccepted FROM taxstatus '.
				'WHERE taxauthid = 1 AND tidaccepted IN('.implode('',$inArray).') AND (tid <> tidaccepted)';
			//echo '<div>SQL: '.$sql.'</div>';
	        $rs = $this->conn->query($sql);
	        while($r = $rs->fetch_object()){
	        	$this->synMap[$r->tid] = $r->tidaccepted;
	        }
			$rs->close();
		}
	}
	
	public function getTaxaMap(){
		//Map scientific names and icons to $taxaMap
		$cnt = 9;
		foreach($this->taxaArr as $key => $taxonName){
        	$this->taxaMap[$key]['sciname'] = $taxonName;
        	$this->taxaMap[$key]['icon'] = $this->sourceIcon[$cnt%9];
        	$cnt++;
        }
		return $this->taxaMap;
	}

	public function getSynMap(){
		return $this->synMap;
	}
}
?>