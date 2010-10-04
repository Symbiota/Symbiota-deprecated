<?php
 include_once($serverRoot.'/config/dbconnection.php');

 class OccurrenceEditorManager {

	private $con;
	private $tid;
	private $gui;
    private $uRights = Array();
    private $checklistRights = Array();
    private $isAdmin = false;
    private $localityFields = Array("locality","MinimumElevationInMeters","MaximumElevationInMeters","VerbatimElevation",
    	"DecimalLatitude","DecimalLongitude","GeodeticDatum","CoordinateUncertaintyInMeters","VerbatimCoordinates",  
    	"VerbatimLatitude","VerbatimLongitude","VerbatimCoordinateSystem","UtmNorthing","UtmEasting","UtmZoning", 
    	"GeoreferenceProtocol","GeoreferenceSources","GeoreferenceVerificationStatus","GeoreferenceRemarks");

    
 	public function __construct(){
 		$this->con = MySQLiConnectionFactory::getCon("readonly");
 	}
 	
 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}
	
    public function getOccurArr($occid){
    	//Get Map to specimen table
    	$specimenMap = Array();
    	$metaSql = "SHOW COLUMNS FROM omspecimens";
    	$metaRs = $this->con->query($metaSql);
    	while($metaRow = $metaRs->fetch_object()){
    		$specimenMap[strtolower($metaRow->Field)]["field"] = $metaRow->Field;
    		$specimenMap[strtolower($metaRow->Field)]["type"] = $metaRow->Type;
    	}
    	$metaRs->close();
    	
    	//Get Specimen record
		$sql = "SELECT c.collid, IFNULL(s.collectioncode,c.collectioncode) AS collcode, ".
			"c.collectionname, c.homepage, c.individualurl, c.contact, c.email, c.icon, ".
			"s.* ".
			"FROM fmcollections AS c INNER JOIN omspecimens AS s ON c.CollID = s.CollID ";
		if($gui){
			$sql .= "WHERE s.GlobalUniqueIdentifier = '".$gui."'";
		}
		elseif($collId && $dbpk){
			$sql .= "WHERE s.DBPK = '".$dbpk."' AND c.CollID = ".$collId;
		}
		else{
            echo "<div id='errdiv'>ERROR: record not found</div>";
			return;
		}
		//echo "SQL: ".$sql;
		
		$result = $this->con->query($sql);
		if($row = $result->fetch_assoc()){
			$this->gui = $row[$specimenMap["globaluniqueidentifier"]["field"]];
			$this->tid = $row[$specimenMap["tidinterpreted"]["field"]];
			echo "<div id='collicon'><img border='1' height='50' width='50' src='../../".$row["icon"]."'/></div>";
			echo "<div id='collcode'>".$row["collcode"]."</div>";
			echo "<div id='collname'>".$row["collcode"]."</div>";

			if($row["individualurl"]){
				$indUrl = str_replace("--PK--",$row[$specimenMap["dbpk"]["field"]],$row["individualurl"]);
				echo "<div id='collsource'>".$row["collectionname"]." <a href='".$indUrl."'> display page</a></div>";
			}
			if($row["email"] && $row["contact"]){
				echo "<div id='collemail'>For more information on this specimen, please contact <a class='bodylink' href='mailto:".$row["email"]."'>".$row["contact"]." (".$row["contact"].")</a></div>";
			}
			if($row["homepage"]){
				echo "<div id='collhomepage'><a class='bodylink' href='".$row["homepage"]."'>".$row["collectionname"]." Homepage</a></div>";
			}
			
			foreach($specimenMap as $k => $v){
            	if(!in_array($k,$this->localityFields) || $row[$specimenMap["localitysecurity"]["field"]] < 2 || $this->isAdmin || in_array($row[$specimenMap["collid"]["field"]],$this->uRights)){
					$value = $row[$v["field"]];
					$typeStr = $v["type"];
					if($typeStr == "date"){
						if($t == strtotime($value)){
							$value = date("j F Y",$t);
						}
					}
					elseif($typeStr == "datetime"){
						if($t = strtotime($value)){
							$value = date("j F Y H:i:s",$t);
						}
					}
					echo "<div id='".$k."'>".$value."</div>\n";
            	}
			}
	        if($row[$specimenMap["localitysecurity"]["field"]] < 2 || $this->isAdmin || in_array($row[$specimenMap["collid"]["field"]],$this->uRights)){
				echo "<div id='locsecdiv'><div style='color:red;'>This species has a sensitive status.</div>";
				echo "<div>For more information, please contact collection manager (see email below).</div></div>";
	        }
	        if($gui = $row[$specimenMap["globaluniqueidentifier"]["field"]]){
				$this->addImages($gui);
	        }
		}
        $result->close();
    }
        
    private function addImages($gui){
        $imgSql = "SELECT ti.url, ti.notes FROM images ti ".
			"WHERE (ti.specimengui = '".$gui."') ORDER BY ti.sortsequence";
        $cnt = 0;
        $result = $this->con->query($imgSql);
		$rowCnt = $result->num_rows;
		if($rowCnt) echo "<div id='imagediv' style='margin:15px;position:relative;'><div><hr/></div>"; 
		while($row = $result->fetch_object()){
			$imgUrl = $row->url;
			if(array_key_exists("imageDomain",$GLOBALS) && substr($imgUrl,0,1)=="/"){
				$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
			}
            if($imgUrl){
            	$cnt++;
              	echo "<div id='image' style='float:left;'>";
            	echo "<a href='".$imgUrl."'><img border=1 width='150' src='".$imgUrl."'></a>";
              	echo "</div>";
            }
        }
		if($rowCnt) echo "</div>"; 
        $result->close();
    }
    
 	public function getChecklists($uid){
 		$returnArr = Array();
		if($this->isAdmin){
			//Get all public checklist names
			$sql = "SELECT DISTINCT c.Name, c.CLID ".
				"FROM (fmchecklists c INNER JOIN fmchklstprojlink cpl ON c.CLID = cpl.clid) ".
				"INNER JOIN fmprojects p ON cpl.pid = p.pid ".
				"WHERE c.clid < 500 AND (c.Access = 'public' or c.uid = ".$uid.") ORDER BY c.Name";
			$result = $this->con->query($sql);
			while($row = $result->fetch_object()){
				$returnArr[$row->CLID] = $row->Name;
			}
			$result->close();
		}
		elseif($this->checklistRights){
			$sql = "SELECT DISTINCT c.Name, c.CLID ".
				"FROM fmchecklists c WHERE c.clid IN(".implode(",",$this->checklistRights).") OR c.uid = ".$uid." ORDER BY c.Name";
			$result = $this->con->query($sql);
			while($row = $result->fetch_object()){
				$returnArr[$row->CLID] = $row->Name;
			}
			$result->close();
		}
		return $returnArr;
 	}
 	
 	private function setUserRights(){
 		global $userRights;
 		$this->uRights = $userRights;
 		if($isAdmin) $this->isAdmin = true;
 		foreach($this->uRights as $value){
 			if(strpos($value, "CL") === 0 && strpos($value, "-admin")){
 				$replaceTxt = array("CL","-admin");
 				$this->checklistRights[] = str_replace($replaceTxt,"",$value);
 			}
 		}
 	}
 	
 	public function getTid(){
 		return $this->tid;
 	}
 	
 	public function getGui(){
 		return $this->gui;
 	}

	function LatLonPointUTMtoLL($northing, $easting, $zone=12) {
		$d = 0.99960000000000004; // scale along long0
		$d1 = 6378137; // Polar Radius
		$d2 = 0.0066943799999999998;
		
		$d4 = (1 - sqrt(1 - $d2)) / (1 + sqrt(1 - $d2));
		$d15 = $easting - 500000;
		$d16 = $northing;
		$d11 = (($zone - 1) * 6 - 180) + 3;
		$d3 = $d2 / (1 - $d2);
		$d10 = $d16 / $d;
		$d12 = $d10 / ($d1 * (1 - $d2 / 4 - (3 * $d2 * $d2) / 64 - (5 * pow($d2,3) ) / 256));
		$d14 = $d12 + ((3 * $d4) / 2 - (27 * pow($d4,3) ) / 32) * sin(2 * $d12) + ((21 * $d4 * $d4) / 16 - (55 * pow($d4,4) ) / 32) * sin(4 * $d12) + ((151 * pow($d4,3) ) / 96) * sin(6 * $d12);
		$d13 = rad2deg($d14);
		$d5 = $d1 / sqrt(1 - $d2 * sin($d14) * sin($d14));
		$d6 = tan($d14) * tan($d14);
		$d7 = $d3 * cos($d14) * cos($d14);
		$d8 = ($d1 * (1 - $d2)) / pow(1 - $d2 * sin($d14) * sin($d14), 1.5);
		$d9 = $d15 / ($d5 * $d);
		$d17 = $d14 - (($d5 * tan($d14)) / $d8) * ((($d9 * $d9) / 2 - (((5 + 3 * $d6 + 10 * $d7) - 4 * $d7 * $d7 - 9 * $d3) * pow($d9,4) ) / 24) + (((61 + 90 * $d6 + 298 * $d7 + 45 * $d6 * $d6) - 252 * $d3 - 3 * $d7 * $d7) * pow($d9,6) ) / 720);
		$d17 = rad2deg($d17); // Breddegrad (N)
		$d18 = (($d9 - ((1 + 2 * $d6 + $d7) * pow($d9,3) ) / 6) + (((((5 - 2 * $d7) + 28 * $d6) - 3 * $d7 * $d7) + 8 * $d3 + 24 * $d6 * $d6) * pow($d9,5) ) / 120) / cos($d14);
		$d18 = $d11 + rad2deg($d18); // Længdegrad (Ø)
		return array('lat'=>$d17,'lng'=>$d18);
	}

 	public function printDefaultLabelDivs(){
    	$specimenMap = Array();
    	$metaSql = "SHOW COLUMNS FROM omspecimens";
    	$metaRs = $this->con->query($metaSql);
    	while($metaRow = $metaRs->fetch_object()){
    		echo "<div id=\"".$metaRow->Field."-label\" class=\"labeldiv\">$metaRow->Field<div>\n";
    	}
 		$metaRs->close();
 	}

 	public function printCss(){
    	$specimenMap = Array();
    	$metaSql = "SHOW COLUMNS FROM omspecimens";
    	$metaRs = $this->con->query($metaSql);
    	while($metaRow = $metaRs->fetch_object()){
    		echo "#".$metaRow->Field."{\n";
    		echo "\tdisplay:\tblock;\n";
    		echo "}\n";
    	}
 		$metaRs->close();
 	}
 }

?>

