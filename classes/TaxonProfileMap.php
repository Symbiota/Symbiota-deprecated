<?php
include_once($serverRoot.'/config/dbconnection.php');

class TaxonProfileMap {
	
	private $iconColors = Array();
	private $tid;
	private $sciName;
	private $taxaMap = array();
	private $synMap = array();
	private $childLoopCnt = 0;
	private $mapType;
	private $sqlWhere = '';

    public function __construct(){
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
				$this->taxArr[$this->tid] = $this->sciName;
				//Get accepted children 
				$this->taxArr = $this->taxArr + $this->getChildren(array($this->tid));
				//Seed $synMap with accepted names
				$taxaKeys = array_keys($this->taxArr);
				//Add synonyms to $synMap
				$this->synMap = array_combine($taxaKeys,$taxaKeys);
				//Add synonyms to $synMap
				$this->setTaxaSynonyms($taxaKeys);
			}
		}
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

	private function setTaxaSynonyms($inArray){
		if($inArray){
			$sql = 'SELECT s.tid, s.tidaccepted, t.SciName FROM taxa t LEFT JOIN taxstatus s on t.TID = s.tid '.
				'WHERE s.taxauthid = 1 AND s.tidaccepted IN('.implode(',',$inArray).') AND (s.tid <> s.tidaccepted)';
			//echo '<div>SQL: '.$sql.'</div>';
	        $rs = $this->conn->query($sql);
	        while($r = $rs->fetch_object()){
	        	$this->synMap[$r->tid] = $r->tidaccepted;
				$this->taxArr[$r->tid] = $r->SciName;
	        }
			$rs->close();
		}
	}
	
	private function getTaxaWhere(){
		global $userRights, $mappingBoundaries;
		$sql = "";
		$sql .= 'WHERE (o.tidinterpreted IN('.implode(',',array_keys($this->synMap)).')) '.
			'AND (o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL) ';
		if(array_key_exists("SuperAdmin",$userRights) || array_key_exists("CollAdmin",$userRights) || array_key_exists("RareSppAdmin",$userRights) || array_key_exists("RareSppReadAll",$userRights)){
			//Is global rare species reader, thus do nothing to sql and grab all records
		}
		elseif(array_key_exists("RareSppReader",$userRights)){
			$sql .= 'AND ((o.CollId IN ('.implode(',',$userRights["RareSppReader"]).')) OR (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL)) ';
		}
		return $sql;
	}
	
	public function getTaxaMap(){
		//Map scientific names and icons to $taxaMap
		$cnt = 9;
		foreach($this->taxArr as $key => $taxonName){
        	$this->taxaArr[$taxonName] = Array();
			$cnt++;
        }
		return $this->taxaMap;
	}

	public function getSynMap(){
		return $this->synMap;
	}

	public function getTaxaSqlWhere(){
		$this->sqlWhere = $this->getTaxaWhere();
		return $this->sqlWhere;
	}

    //Setters and getters
    public function setMapType($type){
    	$this->mapType = $type;
    }
	
	public function getTaxaArr(){
    	return $this->taxaArr;
    }
}
?>