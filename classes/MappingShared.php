<?php
include_once($serverRoot.'/config/dbconnection.php');

class MappingShared{
	
	private $iconColors = Array();
	private $googleIconArr = Array();
	private $taxaArr = Array();
	private $fieldArr = Array();
	private $sqlWhere;
	private $searchTerms = 0;

    public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
    	$this->iconColors = array('fc6355','5781fc','fcf357','00e13c','e14f9e','55d7d7','ff9900','7e55fc');
		$this->googleIconArr = array('pushpin/ylw-pushpin','pushpin/blue-pushpin','pushpin/grn-pushpin','pushpin/ltblu-pushpin',
			'pushpin/pink-pushpin','pushpin/purple-pushpin', 'pushpin/red-pushpin','pushpin/wht-pushpin','paddle/blu-blank',
			'paddle/grn-blank','paddle/ltblu-blank','paddle/pink-blank','paddle/wht-blank','paddle/blu-diamond','paddle/grn-diamond',
			'paddle/ltblu-diamond','paddle/pink-diamond','paddle/ylw-diamond','paddle/wht-diamond','paddle/red-diamond','paddle/purple-diamond',
			'paddle/blu-circle','paddle/grn-circle','paddle/ltblu-circle','paddle/pink-circle','paddle/ylw-circle','paddle/wht-circle',
			'paddle/red-circle','paddle/purple-circle','paddle/blu-square','paddle/grn-square','paddle/ltblu-square','paddle/pink-square',
			'paddle/ylw-square','paddle/wht-square','paddle/red-square','paddle/purple-square','paddle/blu-stars','paddle/grn-stars',
			'paddle/ltblu-stars','paddle/pink-stars','paddle/ylw-stars','paddle/wht-stars','paddle/red-stars','paddle/purple-stars');
    }

	public function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
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
	
	public function getGeoCoords($mapWhere,$limit=1000,$includeDescr=false){
		global $userRights;
		$coordArr = Array();
		$sql = '';
		$sql = 'SELECT o.occid, CONCAT_WS(" ",o.recordedby,IFNULL(o.recordnumber,o.eventdate)) AS identifier, '.
			'o.sciname, o.family, o.tidinterpreted, o.DecimalLatitude, o.DecimalLongitude, o.collid, o.catalognumber, '.
			'o.othercatalognumbers, c.institutioncode, c.collectioncode, c.CollectionName ';
		if($includeDescr){
			$sql .= ", CONCAT_WS('; ',CONCAT_WS(' ', o.recordedBy, o.recordNumber), o.eventDate, o.SciName) AS descr ";
		}
		if($this->fieldArr){
			foreach($this->fieldArr as $k => $v){
				$sql .= ", o.".$v." ";
			}
		}
		$sql .= "FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid ";
		if(($this->searchTerms == 1) && (array_key_exists("clid",$this->searchTermsArr))) $sql .= "INNER JOIN fmvouchers v ON o.occid = v.occid ";
		if((array_key_exists("collector",$this->searchTermsArr))) $sql .= "INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ";
		$sql .= $mapWhere;
		$sql .= " AND (o.DecimalLatitude IS NOT NULL AND o.DecimalLongitude IS NOT NULL)";
		if($GLOBALS['IS_ADMIN'] || array_key_exists("CollAdmin",$userRights) || array_key_exists("RareSppAdmin",$userRights) || array_key_exists("RareSppReadAll",$userRights)){
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
			if(($row->DecimalLongitude <= 180 && $row->DecimalLongitude >= -180) && ($row->DecimalLatitude <= 90 && $row->DecimalLatitude >= -90)){
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
				$coordArr[$taxaMapper[$sciName]][$occId]["collid"] = $row->collid;
				$coordArr[$taxaMapper[$sciName]][$occId]["latLngStr"] = $latLngStr;
				$coordArr[$taxaMapper[$sciName]][$occId]["identifier"] = $row->identifier;
				$coordArr[$taxaMapper[$sciName]][$occId]["tidinterpreted"] = $this->xmlentities($row->tidinterpreted);
				$coordArr[$taxaMapper[$sciName]][$occId]["institutioncode"] = $row->institutioncode;
				$coordArr[$taxaMapper[$sciName]][$occId]["collectioncode"] = $row->collectioncode;
				$coordArr[$taxaMapper[$sciName]][$occId]["catalognumber"] = $row->catalognumber;
				$coordArr[$taxaMapper[$sciName]][$occId]["othercatalognumbers"] = $row->othercatalognumbers;
				if($includeDescr){
					$coordArr[$taxaMapper[$sciName]][$occId]["descr"] = $row->descr;
				}
				if($this->fieldArr){
					foreach($this->fieldArr as $k => $v){
						$coordArr[$taxaMapper[$sciName]][$occId][$v] = $this->xmlentities($row->$v);
					}
				}
			}
		}
		if(array_key_exists("undefined",$coordArr)){
			$coordArr["undefined"]["color"] = $this->iconColors[7];
		}
		$result->close();
		
		return $coordArr;
		//return $sql;
	}
	
    public function writeKMLFile($coordArr){
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
		echo "<?xml version='1.0' encoding='".$charset."'?>\n";
        echo "<kml xmlns='http://www.opengis.net/kml/2.2'>\n";
        echo "<Document>\n";
		echo "<Folder>\n<name>".$defaultTitle." Specimens - ".date('j F Y g:ia')."</name>\n";
        
		$cnt = 0;
		foreach($coordArr as $sciName => $contentArr){
			$iconStr = $this->googleIconArr[$cnt%44];
			$cnt++;
			unset($contentArr["color"]);
			
			echo "<Style id='sn_".$iconStr."'>\n";
            echo "<IconStyle><scale>1.1</scale><Icon>";
			echo "<href>http://maps.google.com/mapfiles/kml/".$iconStr.".png</href>";
			echo "</Icon><hotSpot x='20' y='2' xunits='pixels' yunits='pixels'/></IconStyle>\n</Style>\n";
			echo "<Style id='sh_".$iconStr."'>\n";
            echo "<IconStyle><scale>1.3</scale><Icon>";
			echo "<href>http://maps.google.com/mapfiles/kml/".$iconStr.".png</href>";
			echo "</Icon><hotSpot x='20' y='2' xunits='pixels' yunits='pixels'/></IconStyle>\n</Style>\n";
			echo "<StyleMap id='".str_replace(" ","_",$sciName)."'>\n";
            echo "<Pair><key>normal</key><styleUrl>#sn_".$iconStr."</styleUrl></Pair>";
			echo "<Pair><key>highlight</key><styleUrl>#sh_".$iconStr."</styleUrl></Pair>";
			echo "</StyleMap>\n";
			echo "<Folder><name>".$sciName."</name>\n";
			foreach($contentArr as $occId => $pointArr){
				echo "<Placemark>\n";
				echo "<name>".htmlspecialchars($pointArr["identifier"], ENT_QUOTES)."</name>\n";
				echo "<ExtendedData>\n";
				echo "<Data name='institutioncode'>".$this->xmlentities($pointArr["institutioncode"])."</Data>\n";
				echo "<Data name='collectioncode'>".$this->xmlentities($pointArr["collectioncode"])."</Data>\n";
				echo "<Data name='catalognumber'>".$this->xmlentities($pointArr["catalognumber"])."</Data>\n";
				echo "<Data name='othercatalognumbers'>".$this->xmlentities($pointArr["othercatalognumbers"])."</Data>\n";
				if($this->fieldArr){
					foreach($this->fieldArr as $k => $v){
						echo "<Data name='".$v."'>".$pointArr[$v]."</Data>\n";
					}
				}
				echo "<Data name='DataSource'>Data retrieved from ".$defaultTitle." Data Portal</Data>\n";
				$url = "http://".$_SERVER["SERVER_NAME"].$clientRoot."/collections/individual/index.php?occid=".$occId;
				echo "<Data name='RecordURL'>".$url."</Data>\n";
				echo "</ExtendedData>\n";
				echo "<styleUrl>#".str_replace(" ","_",$sciName)."</styleUrl>\n";
				echo "<Point><coordinates>".implode(",",array_reverse(explode(",",$pointArr["latLngStr"]))).",0</coordinates></Point>\n";
				echo "</Placemark>\n";
			}
			echo "</Folder>\n";
		}
		echo "</Folder>\n";
		echo "</Document>\n";
		echo "</kml>\n";
    }
	
	private function xmlentities($string){
		return str_replace(array ('&','"',"'",'<','>','?'),array ('&amp;','&quot;','&apos;','&lt;','&gt;','&apos;'),$string);
	}
	
    //Setters and getters
    public function setTaxaArr($tArr){
    	$this->taxaArr = $tArr;
    }
	
	public function setFieldArr($fArr){
    	$this->fieldArr = $fArr;
    }
	
	public function setSearchTermsArr($stArr){
    	$this->searchTermsArr = $stArr;
		$this->searchTerms = 1;
    }
}
?>