<?php

class TaxaMapper{

	private $con;
	private $dmid;
	private $mapName;
	private $mapType;
	private $baseFilePath;
	private $mapTargetPath;
	private $mapUrlPath;
	private $latNorth;
	private $latSouth;
	private $longWest;
	private $longEast;
	private $pointSize;
	private $red;
	private $green;
	private $blue;
	private $latAdjustFactor;
	
	function __construct($sRoot) {
		set_time_limit(0);
		$this->con = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
		$this->con->close();
	}

	public function createMap($tid, $dmid){
		$this->dmid = $dmid;
		$this->setMapParameters();
		if($this->mapType == "google dynamic"){
			$this->writeGoogleMap($tid);
		}
		else{
			$this->writeImage($tid);
		}
	}

	public function createSelectedMaps($sciName, $dmid){
		$this->dmid = $dmid;
		$this->setMapParameters();
		$sql = "SELECT DISTINCT t.tid, t.sciname ".
			"FROM ((taxa t INNER JOIN taxstatus ts ON t.TID = ts.tid) ".
			"INNER JOIN taxstatus ts2 ON t.TID = ts2.TidAccepted) ".
			"INNER JOIN omoccurrences o ON ts2.tid = o.TidInterpreted ".
			"WHERE (t.RankId > 180) AND (t.SecurityStatus = 1) AND (ts2.taxauthid = 1) ".
			"AND (ts.taxauthid = 1) AND (ts.tid = ts.tidaccepted) ";
		if($this->latNorth){
			$sql .= "AND (o.DecimalLatitude BETWEEN ".$this->latSouth." AND ".$this->latNorth.") ".
				"AND (o.DecimalLongitude BETWEEN ".$this->longWest." AND ".$this->longEast.") ";
		}
        if(substr($sciName,-5) == "aceae"){
	        $sql .= "AND (t.family = '".$sciName."') ";
        }
        else{
	        $sql .= "AND (t.sciname LIKE '".$sciName."%') ";
        }
		if($this->mapType == "google dynamic") $sql .= "LIMIT 950";
		//echo "<div>".$sql."</div>";
		if($result = $this->con->query($sql)){
	        while($row = $result->fetch_object()){
	            $tid = $row->tid;
	            $sciName = $row->sciname;
				if($this->mapType == "google dynamic"){
					$this->writeGoogleMap($tid,$sciName);
				}
				else{
	            	$this->writeImage($tid,$sciName);
				}
	        }
			$result->close(); 		
		}
		else{
			printf("Invalid query in createAllMaps: %s\nWhole query: %s\n", $this->con->error, $sql);
			exit();
		}
 	}
 	
 	public function createAllMaps($dmid){
		$this->dmid = $dmid;
		$this->setMapParameters();
 		
		if($this->mapType == "google dynamic"){
			$sql = "SELECT DISTINCT t.tid, t.sciname ".
				"FROM (((taxa t INNER JOIN taxstatus ts ON t.TID = ts.tid) ".
				"INNER JOIN taxstatus ts2 ON t.TID = ts2.TidAccepted) ".
				"INNER JOIN omoccurrences o ON ts2.tid = o.TidInterpreted) ".
				"LEFT JOIN taxamaps tm ON t.tid = tm.tid ".
				"WHERE (t.RankId > 180) AND (t.SecurityStatus = 1) AND (ts2.taxauthid = 1) ".
				"AND (ts.taxauthid = 1) AND (ts.tid = ts.tidaccepted) ".
				"AND (tm.mid IS NULL OR tm.initialtimestamp < DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) ";
			if($this->latNorth){
				$sql .= "AND (o.DecimalLatitude BETWEEN ".$this->latSouth." AND ".$this->latNorth.") ".
					"AND (o.DecimalLongitude BETWEEN ".$this->longWest." AND ".$this->longEast.") ";
			}
	 		$sql .= "LIMIT 950";
	 		//echo "<div>".$sql."</div>";
			if($result = $this->con->query($sql)){
				while($row = $result->fetch_object()){
					$tid = $row->tid;
		            $sciName = $row->sciname;
		            $this->writeGoogleMap($tid,$sciName);
		            sleep(3);
		        }
				$result->close(); 		
			}
			else{
				printf("Invalid query in createAllMaps: %s\nWhole query: %s\n", $this->con->error, $sql);
				exit();
			}
		}
		else{
			$familyArr = Array();
			$sqlFamily = "SELECT DISTINCT ts.family ".
				"FROM ((taxa t INNER JOIN taxstatus ts ON t.TID = ts.tid) ".
				"INNER JOIN taxstatus ts2 ON t.TID = ts2.TidAccepted) ".
				"INNER JOIN omoccurrence o ON ts2.tid = o.TidInterpreted ".
				"WHERE (t.RankId > 180) AND (t.SecurityStatus = 1) AND (ts2.taxauthid = 1) ".
				"AND (ts.taxauthid = 1) AND (ts.tid = ts.tidaccepted) ".
				"AND (o.DecimalLatitude > ".$this->latSouth.") AND (o.DecimalLatitude < ".$this->latNorth.") ".
				"AND (o.DecimalLongitude > ".$this->longWest.") AND (o.DecimalLongitude < ".$this->longEast.") ";
			$resultFamily = $this->con->query($sqlFamily);
			while($row = $resultFamily->fetch_object()){
				$familyArr[] = $row->family;
			}
			sort($familyArr);
			foreach($familyArr as $fam){
				$sql = "SELECT DISTINCT t.tid, t.sciname ".
					"FROM ((taxa t INNER JOIN taxstatus ts ON t.TID = ts.tid) ".
					"INNER JOIN taxstatus ts2 ON t.TID = ts2.TidAccepted) ".
					"INNER JOIN omoccurrences o ON ts2.tid = o.TidInterpreted ".
					"WHERE (ts.family = '".$fam."') AND (t.RankId > 180) AND (t.SecurityStatus = 1) AND (ts2.taxauthid = 1) ".
					"AND (ts.taxauthid = 1) AND (ts.tid = ts.tidaccepted) ".
					"AND (o.DecimalLatitude > ".$this->latSouth.") AND (o.DecimalLatitude < ".$this->latNorth.") ".
					"AND (o.DecimalLongitude > ".$this->longWest.") AND (o.DecimalLongitude < ".$this->longEast.") ";
				//echo "<div>".$sql."</div>";
				if($result = $this->con->query($sql)){
					while($row = $result->fetch_object()){
						$tid = $row->tid;
			            $sciName = $row->sciname;
						$this->writeImage($tid,$sciName);
			        }
					$result->close(); 		
				}
				else{
					printf("Invalid query in createAllMaps: %s\nWhole query: %s\n", $this->con->error, $sql);
					exit();
				}
			}
		}
 	}
 	
	private function writeGoogleMap($tid, $sciName = ""){
 		global $googleMapKey;
        $synArr = $this->getSynonyms($tid);
        $synStr = ($synArr?",".implode(",",$synArr):"");
        if(!$sciName) $sciName = $this->getSciName($tid); 
 		$mapArr = Array();
 		$minLat = 90;
 		$maxLat = -90;
 		$minLong = 180;
 		$maxLong = -180;
 		$sql = "SELECT DISTINCT o.DecimalLatitude, o.DecimalLongitude ".
			"FROM omoccurrences o ".
			"WHERE ((o.tidinterpreted IN (".$tid.$synStr.")) OR (o.sciname LIKE '".$sciName."%')) ";
 		if($this->latNorth){
			$sql .= "AND (o.DecimalLatitude BETWEEN ".$this->latSouth." AND ".$this->latNorth.") ".
				"AND (o.DecimalLongitude BETWEEN ".$this->longWest." AND ".$this->longEast.") ";
		}
		$sql .= "LIMIT 50";
		//echo "<div>".$sql."</div>";
		$result = $this->con->query($sql);
 		while($row = $result->fetch_object()){
 			$lat = round($row->DecimalLatitude,2);
			if($lat < $minLat) $minLat = $lat;
			if($lat > $maxLat) $maxLat = $lat;
 			$long = round($row->DecimalLongitude,2);
			if($long < $minLong) $minLong = $long;
			if($long > $maxLong) $maxLong = $long;
 			$mapArr[] = $lat.",".$long;
		}
		$result->close();
		
		//Get thumbnail
		if($mapArr){
			$coordStr = implode("|",$mapArr);
			$url = "http://maps.google.com/staticmap";
			//$data = "size=256x300&maptype=terrain&sensor=false&key=".$googleMapKey;
			$data = "size=256x300&maptype=terrain&sensor=false&key=";
			$latDist = $maxLat - $minLat;
			$longDist = $maxLong - $minLong;
			if($latDist < 3 || $longDist < 3) {
				$data .= "&zoom=6";
			}
			$data .= "&markers=".$coordStr;
			//echo "url: ".$url."?".$data;
			$content = file_get_contents($url."?".$data);
			if ($content !== false) {
				if(substr($this->mapTargetPath,-1) != "/") $this->mapTargetPath .= "/";
				$sciNameStr = str_replace(" ","_",$sciName);
 				$sciNameStr = str_replace(".","",$sciNameStr);
				$fh = fopen($this->mapTargetPath.$sciNameStr.".gif", 'wb');
				fwrite($fh, $content);
				fclose($fh);
				//write to map file info to database
				$sql = "REPLACE INTO taxamaps(tid, url, dmid) values (".$tid.",'".$this->mapUrlPath.$sciNameStr.".gif',".$this->dmid.") ";
				if(!$this->con->query($sql)){
					printf("Invalid query in writeImage: %s\nsql: %s\n", $this->con->error, $sql);
				}
			} else {
				echo "Error reading file";
			}
		}
	} 	
	
 	private function getSynonyms($tid){
		$synArr = Array();
 		$sql = "CALL ReturnSynonyms('".$tid."',1)";
		if($result = $this->con->query($sql)){
			while($row = $result->fetch_object()){
				$synArr[]=$row->tid;
			}
			$result->close();
			//print_r($synArr);
			while($this->con->more_results()){
				if($this->con->next_result()){
					$result = $this->con->store_result();
					if($result) $result->close();
				}
			} 		
		}
		else{
			printf("Invalid getSymonyms query: %s\nWhole query: %s\n", $this->con->error, $sql);
			exit();
		}
		return $synArr;
 	}
 	
 	private function getSciName($tid){
		$sciName = "";
 		if($tid){
	 		$sql = "SELECT t.TID, t.SciName ".
				"FROM taxa t WHERE (t.TID = $tid) ";
	        //echo $sql;
			if($result = $this->con->query($sql)){
				if($row = $result->fetch_object()){
		            $sciName = $row->SciName;
		        }
		        $result->close();
			}
			else {
				printf("Invalid query in getSciName: %s\n", $this->con->error, $sql);
			}
        }
        return $sciName;
 	}
 	
	private function setMapParameters(){
		$sql = "SELECT tmp.name, tmp.maptype, tmp.basefilepath, tmp.maptargetpath, tmp.mapurlpath, tmp.latnorth, tmp.latsouth, ".
			"tmp.longwest, tmp.longeast, tmp.pointsize, tmp.red, tmp.green, tmp.blue, tmp.latadjustfactor ". 
			"FROM taxamapparams tmp WHERE tmp.dmid = $this->dmid";
		//echo "<div>SQL: ".$sql."</div>";
		$result = $this->con->query($sql);
		if($row = $result->fetch_object()){
			$this->mapName = $row->name;
			$this->mapType = $row->maptype;
			$this->baseFilePath = $row->basefilepath;
			$this->mapTargetPath = $row->maptargetpath;
			if(substr($this->mapTargetPath,-1) != "/") $this->mapTargetPath .= "/";
			$this->mapUrlPath = $row->mapurlpath;
			if(substr($this->mapUrlPath,-1) != "/") $this->mapUrlPath .= "/";
			$this->latNorth = $row->latnorth;
			$this->latSouth = $row->latsouth; 
			$this->longWest = $row->longwest;
			$this->longEast = $row->longeast;
			$this->pointSize = $row->pointsize;
			$this->red = $row->red;
			$this->green = $row->green;
			$this->blue = $row->blue;
			$this->latAdjustFactor = $row->latadjustfactor;
		}
		$result->close();
	} 
	
    private function writeImage($tid, $sciName = ""){
 		if(!$sciName) $sciName = $this->getSciName($tid);
 		$sciName = str_replace(" ","_",$sciName);
 		$sciName = str_replace(".","",$sciName);
 		$fileExt = substr($this->baseFilePath,(stripos($this->baseFilePath,".") - strlen($this->baseFilePath)));
 		$targetPath = $this->mapTargetPath.$sciName.$fileExt;
		$fullCoords = $this->getCoordinates($tid, $sciName);
		if($fullCoords){
			if($fileExt == ".jpg" || $fileExt == ".jpeg"){
		 		$baseImg = imagecreatefromjpeg($this->baseFilePath);
			}
			elseif($fileExt == ".gif"){
				$baseImg = imagecreatefromgif($this->baseFilePath);
			}
	        $width = imagesx($baseImg);
			$height = imagesy($baseImg);
			$pointColor = imagecolorallocate($baseImg, $this->red, $this->green, $this->blue);
	 		foreach($fullCoords as $pointArr){
	 			$newY = ($height*(1-$this->latAdjustFactor) + $pointArr["y"]*$height*$this->latAdjustFactor);
	 			imagefilledellipse($baseImg, round($width*$pointArr["x"],4), round($newY,4), $this->pointSize, $this->pointSize, $pointColor);
	 		}
			if($fileExt == ".jpg" || $fileExt == ".jpeg"){
		 		imagejpeg($baseImg,$targetPath,90);
			}
			elseif($fileExt == ".gif"){
				imagegif($baseImg,$targetPath);
			}
			//echo "<div>Map image written to: ".$targetPath."</div>";
			imagedestroy($baseImg);
			//write to map file info to database
			$sql = "REPLACE INTO taxamaps(tid, url, dmid) values (".$tid.",'".$this->mapUrlPath.$sciName.$fileExt."',".$this->dmid.") ";
			if(!$this->con->query($sql)){
				printf("Invalid query in writeImage: %s\nsql: %s\n", $this->con->error, $sql);
			}
		}
	}

	private function getCoordinates($tid, $sciName){
        $returnArray = Array();
        $synArr = $this->getSynonyms($tid);
        $sql = "SELECT o.DecimalLatitude, o.DecimalLongitude ".
			"FROM omoccurrences o ". 
			"WHERE ((o.DecimalLatitude BETWEEN ".$this->latSouth." AND ".$this->latNorth.") ".
			"AND (o.DecimalLongitude BETWEEN ".$this->longWest." AND ".$this->longEast.")) ";
        if($synArr){
	        $sql .= "AND ((o.TidInterpreted In (".implode(",",$synArr).")) OR (o.sciname LIKE '".$sciName."%'))";
        }
        else{
	        $sql .= "AND (o.sciname LIKE '".$sciName."%')";
        }
        //echo $sql;
		if($result = $this->con->query($sql)){
	        while($row = $result->fetch_object()){
	            $lat = $row->DecimalLatitude;
	            $lon = $row->DecimalLongitude;
	            $x = (($lon + (-1 * $this->longWest)) / ($this->longEast - $this->longWest));
	   			$y = ((($lat * -1) + $this->latNorth) / ($this->latNorth - $this->latSouth));
	            $returnArray[] = array("x"=>$x,"y"=>$y);
	        }
			$result->close();
		}
		else{
			printf("Invalid query in getCoordinates: %s\nWhole query: %s\n", $this->con->error, $sql);
			exit();
		}
        return $returnArray;
    }
    
    public function getTaxaMapList(){
    	$returnArr = Array();
    	$sql = "SELECT tmp.dmid, tmp.name, tmp.regionofinterest FROM taxamapparams tmp ORDER BY tmp.dmid";
    	$result = $this->con->query($sql);
    	while($row = $result->fetch_object()){
    		$taxaMapName = $row->name;
    		if($row->regionofinterest) $taxaMapName .= " (".$row->regionofinterest.")";
    		$returnArr[$row->dmid] = $taxaMapName;
    	}
    	$result->close();
    	return $returnArr;
    }
    
 	public function getTaxaList(){
    	$returnArr = Array();
    	$sql = "SELECT DISTINCT t.family ".
    		"FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted ".
    		"INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted ".
    		"WHERE ts.taxauthid = 1 ORDER BY t.family";
    	$result = $this->con->query($sql);
    	while($row = $result->fetch_object()){
    		$returnArr[] = $row->family;
    	}
    	$result->close();
    	$sql = "SELECT DISTINCT t.unitname1 ".
    		"FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted ".
    		"INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted ".
    		"WHERE ts.taxauthid = 1 ORDER BY t.unitname1";
    	$result = $this->con->query($sql);
    	while($row = $result->fetch_object()){
    		$returnArr[] = $row->unitname1;
    	}
    	$result->close();
    	return $returnArr;
    }

    public function getSpeciesList(){
    	$returnArr = Array();
    	$sql = "SELECT t.tid, t.sciname ".
    		"FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted) ".
    		"INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted ".
    		"WHERE t.rankid >= 220 ".
    		"ORDER BY t.rankid, t.sciname";
    	$result = $this->con->query($sql);
    	while($row = $result->fetch_object()){
    		$returnArr[$row->tid] = $row->sciname;
    	}
    	$result->close();
    	return $returnArr;
    }
}
?>
