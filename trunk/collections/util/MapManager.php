<?php
/*
 * Created on 3 May 2009
 * @author  E. Gilbert: egbot@asu.edu
 */
include_once("CollectionManager.php");

class MapManager extends CollectionManager{
	
	private $iconUrls = Array();

    public function __construct(){
    	global $clientRoot;
 		parent::__construct();
        $this->iconUrls[] = $clientRoot."/images/google/red-dot.png";
		$this->iconUrls[] = $clientRoot."/images/google/blue-dot.png";
		$this->iconUrls[] = $clientRoot."/images/google/yellow-dot.png";
		$this->iconUrls[] = $clientRoot."/images/google/green-dot.png";
		$this->iconUrls[] = $clientRoot."/images/google/pink-dot.png";
		$this->iconUrls[] = $clientRoot."/images/google/ltblue-dot.png";
		$this->iconUrls[] = $clientRoot."/images/google/orange-dot.png";
		$this->iconUrls[] = $clientRoot."/images/google/purple-dot.png";
    }

	public function getGeoCoords($limit = 1000, $includeDescr= false){
		global $userRights,$isAdmin;
		$conn = $this->getConnection();
        $querySql = "";
        $sql = "SELECT o.occid, o.sciname, o.family, o.DecimalLatitude, o.DecimalLongitude, o.collid, o.dbpk, o.occurrenceID ";
        if($includeDescr){
        	$sql .= ", CONCAT_WS('; ',CONCAT_WS(' ', o.recordedBy, o.recordNumber), o.eventDate, o.SciName) AS descr ";
        }
        $sql .= "FROM omoccurrences o ";
		if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
        $sql .= $this->getSqlWhere();
        $sql .= " AND (o.DecimalLatitude IS NOT NULL AND o.DecimalLongitude IS NOT NULL)";
		if($isAdmin || array_key_exists("CollAdmin",$userRights) || array_key_exists("RareSppAdmin",$userRights) || array_key_exists("RareSppReadAll",$userRights)){
			//Add nothing to sql; grab all records   
		}
		elseif(array_key_exists("RareSppReader",$userRights)){
			$sql .= " AND (o.CollId IN (".implode(",",$userRights["RareSppReader"])."))";
		}
		else{
			$sql .= " AND (o.LocalitySecurity = 1 OR o.LocalitySecurity IS NULL) ";
		}
		if($limit){
			$sql .= " LIMIT 1000";
		}
        $coordArr = Array();
		$taxaMapper = Array();
		$taxaMapper["undefined"] = "undefined";
		$cnt = 0;
        foreach($this->taxaArr as $key => $valueArr){
        	$coordArr[$key] = Array("icon" => $this->iconUrls[$cnt%7]);
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
        $result = $conn->query($sql);
        while($row = $result->fetch_object()){
			$occId = $row->occid;
			$sciName = $row->sciname;
			$family = $row->family;
			$latLngStr = round($row->DecimalLatitude,4).",".round($row->DecimalLongitude,4);
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
			$coordArr[$taxaMapper[$sciName]][$latLngStr][$occId]["dbpk"] = $row->dbpk;
			$coordArr[$taxaMapper[$sciName]][$latLngStr][$occId]["gui"] = $row->occurrenceID;
			if($includeDescr){
				$coordArr[$taxaMapper[$sciName]][$latLngStr][$occId]["descr"] = $row->descr;
			}
		}
		if(array_key_exists("undefined",$coordArr)){
			$coordArr["undefined"]["icon"] = $this->iconUrls[7];
		}
		$result->close();
		$conn->close();
		return $coordArr;
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
			$iconStr = $contentArr["icon"];
			unset($contentArr["icon"]);
            echo "<Style id='".str_replace(" ","_",$sciName)."'>\n";
            echo "<IconStyle><Icon>";
			echo "<href>http://".$_SERVER["SERVER_NAME"].$iconStr."</href>";
			echo "</Icon></IconStyle>\n</Style>\n";

			echo "<Folder><name>".$sciName."</name>\n";

			foreach($contentArr as $latLong => $llArr){
				foreach($llArr as $occId => $pointArr){
					echo "<Placemark>\n";
					echo "<name>".$pointArr["gui"]."</name>\n";
					echo "<description><![CDATA[<p>".$pointArr["descr"]."</p>";
					$url = "http://".$_SERVER["SERVER_NAME"].$clientRoot."/collections/individual/individual.php?occid=".$occId;
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
}
?>