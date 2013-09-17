<?php
include_once("OccurrenceManager.php");

class OccurrenceMapManager extends OccurrenceManager{
	
	private $iconColors = Array();
	private $tid;
	private $sciName;
	private $taxaMap = array();
	private $synMap = array();
	private $childLoopCnt = 0;
	private $mapType;

    public function __construct(){
    	global $clientRoot;
 		parent::__construct();
        $this->iconColors[] = "fc6355";
		$this->iconColors[] = "5781fc";
		$this->iconColors[] = "fcf357";
		$this->iconColors[] = "00e13c";
		$this->iconColors[] = "e14f9e";
		$this->iconColors[] = "55d7d7";
		$this->iconColors[] = "ff9900";
		$this->iconColors[] = "7e55fc";
    }

	public function __destruct(){
 		parent::__destruct();
	}
	
	public function getGenObsInfo(){
		$retVar = '';
		$sql = 'SELECT collid '.
			'FROM omcollections '.
			'WHERE collectionname = "General Observations"';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retVar = $r->collid;
			}
			$rs->close();
		}
		return $retVar;
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
				$this->synMap = array_combine($taxaKeys,$taxaKeys);
				//Add synonyms to $synMap
				$this->setTaxaSynonyms($taxaKeys);
			}
		}
	}

    public function getGeoCoords($limit = 1000, $includeDescr= false){
		global $userRights, $mappingBoundaries;
		$coordArr = Array();
		$querySql = '';
		$sql = 'SELECT o.occid, IFNULL(IFNULL(IFNULL(o.occurrenceid,o.catalognumber),CONCAT(o.recordedby," ",o.recordnumber)),o.occid) AS identifier, '.
			'o.sciname, o.family, o.DecimalLatitude, o.DecimalLongitude, o.collid ';
		if($includeDescr){
			$sql .= ", CONCAT_WS('; ',CONCAT_WS(' ', o.recordedBy, o.recordNumber), o.eventDate, o.SciName) AS descr ";
		}
		$sql .= "FROM omoccurrences o ";
		//if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
		if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN fmvouchers sol ON o.occid = sol.occid ";
		$sql .= $this->getMapWhere();
		$sql .= " AND (o.DecimalLatitude IS NOT NULL AND o.DecimalLongitude IS NOT NULL)";
		if(array_key_exists("SuperAdmin",$userRights) || array_key_exists("CollAdmin",$userRights) || array_key_exists("RareSppAdmin",$userRights) || array_key_exists("RareSppReadAll",$userRights)){
			//Is global rare species reader, thus do nothing to sql and grab all records
		}
		elseif(array_key_exists("RareSppReader",$userRights)){
			$sql .= " AND (o.CollId IN (".implode(",",$userRights["RareSppReader"]).") OR (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL)) ";
		}
		else{
			$sql .= " AND (o.LocalitySecurity = 0 OR o.LocalitySecurity IS NULL) ";
		}
		if($limit){
			//$sql .= " LIMIT 1000";
		}
		$taxaMapper = Array();
		$taxaMapper["undefined"] = "undefined";
		$cnt = 0;
		//echo json_encode($this->taxaArr);
		foreach($this->taxaArr as $key => $valueArr){
			$coordArr[$key] = Array("color" => $this->iconColors[$cnt%7]);
			$cnt++;
			$taxaMapper[$key] = $key;
			if(array_key_exists("scinames",$valueArr)){
				$scinames = $valueArr["scinames"];
				foreach($scinames as $sciname){
					$taxaMapper[$sciname] = $key;
				}
			}
			if(array_key_exists("synonyms",$valueArr)){
				$synonyms = $valueArr["synonyms"];
				foreach($synonyms as $syn){
					$taxaMapper[$syn] = $key;
				}
			}
		}
		//echo "<div>SQL: ".$sql."</div>";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$occId = $row->occid;
			$sciName = $row->sciname;
			$family = $row->family;
			//$latLngStr = round($row->DecimalLatitude,4).",".round($row->DecimalLongitude,4);
			$latLngStr = $row->DecimalLatitude.",".$row->DecimalLongitude;
			if(!array_key_exists($sciName,$taxaMapper)){
				foreach($taxaMapper as $keySciname => $v){
					if(strpos($sciName,$keySciname) === 0){
						$sciName = $keySciname;
						break;
					}
				}
				if(!array_key_exists($sciName,$taxaMapper) && array_key_exists($family,$taxaMapper)){
					$sciName = $family;
				}
			}
			if(!array_key_exists($sciName,$taxaMapper)) $sciName = "undefined"; 
			$coordArr[$taxaMapper[$sciName]][$latLngStr][$occId]["collid"] = $row->collid;
			$coordArr[$taxaMapper[$sciName]][$latLngStr][$occId]["identifier"] = $row->identifier;
			if($includeDescr){
				$coordArr[$taxaMapper[$sciName]][$latLngStr][$occId]["descr"] = $row->descr;
			}
		}
		if(array_key_exists("undefined",$coordArr)){
			$coordArr["undefined"]["color"] = $this->iconColors[7];
		}
		$result->close();
		
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

	private function setTaxaSynonyms($inArray){
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
			//$this->taxaMap[$key]['sciname'] = $taxonName;
        	//$this->taxaMap[$key]['color'] = $this->iconColors[$cnt%9];
			//$this->taxaMap[$key]['icon'] = $this->iconColors[$cnt%9];
        	$cnt++;
        }
		//echo json_encode($this->taxaArr);
		return $this->taxaMap;
	}

	public function getSynMap(){
		return $this->synMap;
	}

	private function getMapWhere(){
		if($this->mapType == 'taxa'){
			return $this->getTaxaWhere();
		}
		else{
			return $this->getSqlWhere();
		}
	}

    public function writeKMLFile(){
    	global $defaultTitle, $userRights, $clientRoot, $charset;
		$fileName = $defaultTitle;
		if($fileName){
			if(strlen($fileName) > 10) $fileName = substr($fileName,0,10);
			$fileName = str_replace(".","",$fileName);
			$fileName = str_replace(" ","_",$fileName);
		}
		else{
			$fileName = "symbiota";
		}
		$fileName .= time().".kml";
    	header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header ('Content-type: application/vnd.google-earth.kml+xml');
		header ("Content-Disposition: attachment; filename=\"$fileName\""); 

		$coordArr = $this->getGeoCoords(0,true);
		
        echo "<?xml version='1.0' encoding='".$charset."'?>\n";
        echo "<kml xmlns='http://www.opengis.net/kml/2.2'>\n";
        echo "<Document>\n";
		echo "<Folder>\n<name>".$defaultTitle." Specimens - ".date('j F Y g:ia')."</name>\n";
        
		foreach($coordArr as $sciName => $contentArr){
			$iconStr = $clientRoot."/images/google/".$contentArr['color']."-dot.png";
			unset($contentArr["color"]);
			echo "<Style id='".str_replace(" ","_",$sciName)."'>\n";
            echo "<IconStyle><Icon>";
			echo "<href>http://".$_SERVER["SERVER_NAME"].$iconStr."</href>";
			echo "</Icon></IconStyle>\n</Style>\n";

			echo "<Folder><name>".$sciName."</name>\n";

			foreach($contentArr as $latLong => $llArr){
				foreach($llArr as $occId => $pointArr){
					echo "<Placemark>\n";
					echo "<name>".htmlspecialchars($pointArr["identifier"], ENT_QUOTES)."</name>\n";
					echo "<description><![CDATA[<p>".$pointArr["descr"]."</p>";
					$url = "http://".$_SERVER["SERVER_NAME"].$clientRoot."/collections/individual/index.php?occid=".$occId;
					echo "<p><b>More Information:</b> <a href='".$url."'>".$url."</a></p>";
					echo "<p><b>Data retrieved from <a href='http://".$_SERVER["SERVER_NAME"]."'>".$defaultTitle." Data Portal</a></b></p>]]></description>\n";
					echo "<styleUrl>#".str_replace(" ","_",$sciName)."</styleUrl>\n";
	                echo "<Point><coordinates>".implode(",",array_reverse(explode(",",$latLong))).",0</coordinates></Point>\n";
					echo "</Placemark>\n";
				}
			}
			echo "</Folder>\n";
		}
		echo "</Folder>\n";
		echo "</Document>\n";
		echo "</kml>\n";
    }
    
    //Setters and getters
    public function setMapType($type){
    	$this->mapType = $type;
    }
}
?>