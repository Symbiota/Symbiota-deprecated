<?php
/*
 * Created by Edward Gilbert 
 * egbot@asu.edu
 * Global Institute of Sustainability - GIOS 
 * on 31 Aug. 2006
 * 
 */
 
 include_once("../util/dbconnection.php");

 class TPData {

 	private $submittedTid;
 	private $submittedSciName;
	private $submittedAuthor;
 	private $tid;
	private $sciName;
 	private $taxAuthId;
	private $author;
	private $parentTid;
	private $family;
	private $familyVern;
	private $rankId;
	private $language;
	private $securityStatus;
	
	private $clName;
	private $clid;
	private $clTitle;
	private $clInfo;
	private $clType;
	private $parentClid;
	private $parentName;
	private $pid;
	private $projName;
	private $googleUrl;
	
	private $vernaculars;				// An array of vernaculars of above language. Array(vernacularName) --Display order is controlled by SQL
	private $synonyms;					// An array of synonyms. Array(synonymName) --Display order is controlled by SQL
	private $acceptedTaxa;				// Array(tid -> SciName) Used if target is not accepted
	private $imageArr; 
	
	//used if taxa rank is at genus or family level
	private $sppArray;
	
	private $con; 

 	public function __construct(){
		global $googleMapKey,$defaultLang;
 		$this->con = MySQLiConnectionFactory::getCon("readonly");
 		//Default settings
 		$this->taxAuthId = 1;			//0 = do not resolve taxonomy (no thesaurus); 1 = default taxonomy; > 1 = other taxonomies
		//$this->projName = "Arizona";
		$this->language = $defaultLang;
		$this->googleUrl = "http://maps.google.com/staticmap?size=256x256&maptype=terrain&sensor=false&key=".$googleMapKey;
 	}

 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}
 	
 	public function setTaxon($t){
		$sql = "SELECT t.TID, ts.family, t.SciName, t.Author, t.RankId, ts.ParentTID, t.SecurityStatus, ts.TidAccepted ". 
			"FROM taxstatus ts INNER JOIN taxa t ON ts.tid = t.TID ".
			"WHERE (ts.taxauthid = ".($this->taxAuthId?$this->taxAuthId:"1").") ";
		if(intval($t)){
			$sql .= "AND t.TID = ".$t." ";
		}
		else{
			$sql .= "AND t.SciName = '".$t."' ";
		}
		//echo $sql;
		$result = $this->con->query($sql);
		if($row = $result->fetch_object()){
			$this->submittedTid = $row->TID;
			$this->submittedSciName = $row->SciName;
			$this->submittedAuthor = $row->Author;
			$this->family = $row->family;
			$this->author = $row->Author;
			$this->rankId = $row->RankId;
			$this->parentTid = $row->ParentTID;
			$this->securityStatus = $row->SecurityStatus;
			
			if($this->submittedTid == $row->TidAccepted){
				$this->tid = $this->submittedTid;
				$this->sciName = $this->submittedSciName;
			}
			else{
				$this->tid = $row->TidAccepted;
				$this->setAccepted();
			}
			
			if($this->rankId >= 140 && $this->rankId < 220){
				//For family and genus hits
				$this->setSppData();
			}
			elseif(count($this->acceptedTaxa) < 2){
				if($this->clid) $this->setChecklistInfo();
				$this->setVernaculars();
	 			$this->setSynonyms();
			}
		}
	    else{
	    	$this->sciName = "unknown";
	    }
		$result->close();
 	}
 	
 	public function setAccepted(){
		$this->acceptedTaxa = Array();
		$sql = "SELECT t.Tid, ts.family, t.SciName, t.Author, t.RankId, ts.ParentTID, t.SecurityStatus ". 
			"FROM taxstatus ts INNER JOIN taxa t ON ts.TidAccepted = t.TID ".
			"WHERE (ts.taxauthid = ".($this->taxAuthId?$this->taxAuthId:"1").") AND (ts.Tid = ".$this->submittedTid.") ORDER BY t.SciName";
		$result = $this->con->query($sql);
		while($row = $result->fetch_object()){
			$this->sciName = $row->SciName;
			$a = $row->Author;
			$this->acceptedTaxa[$row->Tid] = "<i>$this->sciName</i> $a";
			if($this->taxAuthId){
				$this->family = $row->family;
				$this->rankId = $row->RankId;
				$this->author = $a;
				$this->parentTid = $row->ParentTID;
				$this->securityStatus = $row->SecurityStatus;
			}
		}
		$result->close();
 	}

 	private function setChecklistInfo(){
		$sql = "SELECT DISTINCT ctl.Habitat, ctl.Abundance, ctl.Notes ". 
			"FROM fmchklsttaxalink ctl INNER JOIN taxstatus ts ON ctl.tid = ts.tid ".
			"WHERE (ctl.tid = ".$this->tid.") AND (ctl.clid = ".$this->clid.") ";
		//echo $sql;
		$result = $this->con->query($sql);
		if($row = $result->fetch_object()){
			$info = "";
			if($row->Habitat) $info .= "; ".$row->Habitat;
			if($row->Abundance) $info .= "; ".$row->Abundance;
			if($row->Notes) $info .= "; ".$row->Notes;
			$this->clInfo = substr($info,2);
		}
		$result->close();
 	}
 	
 	public function getTid(){
 		return $this->tid;
 	}
 	
 	public function getSciName(){
 		return $this->sciName;
 	}
 	
 	public function getDisplayName(){
 		// If only one accepted name exists & $taxStatusId = 0 (no thesuarus): show unaccepted name & accepted name's images
		// If only one accepted name exists & $taxStatusId > 0: show accepted name & images 
 		if(!$this->taxAuthId){
 			return $this->submittedSciName;
 		}
 		else{
 			return $this->sciName;
 		}
 	}
 	
	public function getAuthor(){
 		if(!$this->taxAuthId){
 			return $this->submittedAuthor;
 		}
 		else{
			return $this->author;
 		}
 	}
 
 	public function getSubmittedTid(){
 		return $this->submittedTid;
 	}
 	
 	public function getSubmittedSciName(){
 		return $this->submittedSciName;
 	}
 	
 	public function setTaxAuthId($id){
 		$this->taxAuthId = $id;
 	}

	public function setSppData(){
		$this->sppArray = Array();
		$sql = "";
		if($this->clid){
			$sql = "SELECT DISTINCT t.tid, t.SciName, t.Author, t.securitystatus ".
				"FROM (taxa t INNER JOIN taxstatus ts ON t.Tid = ts.Tid) ".
				"INNER JOIN fmchklsttaxalink ctl ON ctl.TID = ts.Tid ".
				"WHERE (t.RankId = 220) AND ctl.clid = ".$this->clid." AND ".
				"(ts.Family = '".($this->taxAuthId?$this->sciName:$this->submittedSciName)."' ".
				"OR t.UnitName1 = '".($this->taxAuthId?$this->sciName:$this->submittedSciName)."')";
		}
		elseif($this->pid){
			$sql = "SELECT DISTINCT t.tid, t.SciName, t.Author, t.securitystatus ".
				"FROM ((taxa t INNER JOIN taxstatus ts ON t.Tid = ts.tidaccepted) ".
				"INNER JOIN fmchklsttaxalink ctl ON ts.Tid = ctl.TID) ".
				"INNER JOIN fmchklstprojlink cpl ON ctl.clid = cpl.clid ".
				"WHERE (ts.taxauthid = 1) AND (t.RankId = 220) AND cpl.pid = ".$this->pid." AND ".
				"(ts.Family = '".($this->taxAuthId?$this->sciName:$this->submittedSciName)."' ".
				"OR t.UnitName1 = '".($this->taxAuthId?$this->sciName:$this->submittedSciName)."')";
		}
		else{
			$sql = "SELECT DISTINCT t.tid, t.SciName, t.Author, t.securitystatus ".
				"FROM taxa t INNER JOIN taxstatus ts ON t.Tid = ts.TidAccepted ".
				"WHERE (ts.taxauthid = ".($this->taxAuthId?$this->taxAuthId:"1").") AND (t.RankId = 220) ".
				"AND (ts.Family = '".$this->sciName."' OR t.UnitName1 = '".$this->sciName."') ";
		}
		//echo $sql;
		
		$tids = Array();
		$result = $this->con->query($sql);
		while($row = $result->fetch_object()){
			$sn = ucfirst(strtolower($row->SciName));
			$this->sppArray[$sn]["tid"] = $row->tid;
			$this->sppArray[$sn]["security"] = $row->securitystatus;  
			$tids[] = $row->tid;
		}
		$result->close();
		
		//If no tids exist, grab all species from that taxon, even if a clid or pid exists 
		if(!$tids){
			$sql = "SELECT DISTINCT t.tid, t.SciName, t.Author, t.securitystatus ".
				"FROM taxa t INNER JOIN taxstatus ts ON t.Tid = ts.TidAccepted ".
				"WHERE (ts.Family = '".$this->sciName."' OR t.UnitName1 = '".$this->sciName."') AND (ts.taxauthid = ".($this->taxAuthId?$this->taxAuthId:"1").") AND (t.RankId = 220) ";
			//echo $sql;
			
			$result = $this->con->query($sql);
			while($row = $result->fetch_object()){
				$sn = ucfirst(strtolower($row->SciName));
				$this->sppArray[$sn]["tid"] = $row->tid;
				$this->sppArray[$sn]["security"] = $row->securitystatus;  
				$tids[] = $row->tid;
			}
			$result->close();
		}
		
		//Get Images 
		$sql = "SELECT t.sciname, ti.tid, ti.imagetype, ti.sortsequence, ti.url, ti.thumbnailurl, ".
			"IFNULL(ti.photographer,CONCAT_WS(' ',u.firstname,u.lastname)) AS photographer ".
			"FROM (((images ti LEFT JOIN users u ON ti.photographeruid = u.uid) ".
			"INNER JOIN taxstatus ts1 ON ti.tid = ts1.tid) ".
			"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted) ".
			"INNER JOIN taxa t ON ts2.tid = t.tid ".
			"WHERE t.tid IN(".implode(",",$tids).") AND (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ti.sortsequence = 1) ";
		//echo $sql;
		$result = $this->con->query($sql);
		while($row = $result->fetch_object()){
			$sciName = ucfirst(strtolower($row->sciname));
			if(!array_key_exists($sciName,$this->sppArray)){
				$firstPos = strpos($sciName," ",2)+2;
				$sciName = substr($sciName,0,strpos($sciName," ",$firstPos));
			}
			$this->sppArray[$sciName]["url"] = $row->url;
			$this->sppArray[$sciName]["thumbnailurl"] = $row->thumbnailurl;
		}
		$result->close();
		
		//Get Maps, if rank is genus level or higher
		if($this->rankId > 140){
			foreach($tids as $tid){
				if($mapArr = $this->getMapUrl($tid)){
					foreach($mapArr as $sn => $url){
						$this->sppArray[$sn]["map"] = $url;
					}
				}
			}
		}
	}
 	
	public function getSppArray(){
		return $this->sppArray;
	}
	
	public function setVernaculars(){
		$this->vernaculars = Array();
		$sql = "SELECT DISTINCT v.VernacularName ".
			"FROM taxavernaculars v INNER JOIN taxstatus ts ON v.tid = ts.tidaccepted ".
			"WHERE (ts.TID = $this->tid) AND (v.SortSequence < 90) AND (v.Language = '".$this->language."') ".
			"ORDER BY v.SortSequence";
		$result = $this->con->query($sql);
		while($row = $result->fetch_object()){
			$this->vernaculars[] = $row->VernacularName;
		}
		$result->close();
	}
 	
 	public function getVernaculars(){
 		return $this->vernaculars;
 	}
 	
 	public function getVernacularStr(){
 		$str = "";
 		if($this->vernaculars){
 			$str = array_shift($this->vernaculars);
 		}
 		if($this->vernaculars){
 			$str .= "<span class='verns' onclick=\"javascript: toggle('verns');\" style='cursor:pointer;display:inline;font-size:50%;vertical-align:sub' title='Click here to show more common names'>,&nbsp;&nbsp;more</span>";
 			$str .= "<span class='verns' onclick=\"javascript: toggle('verns');\" style='display:none;'>, ";
 			$str .= implode(", ",$this->vernaculars);
 			$str .= "</span>";
 		}
 		return $str;
 	}
 	
 	public function setSynonyms(){
		$this->synonyms = Array();
		$sql = "SELECT t.tid, t.SciName, t.Author ".
			"FROM taxstatus ts INNER JOIN taxa t ON ts.Tid = t.TID ".
			"WHERE (ts.TidAccepted = ".$this->tid.") AND (ts.taxauthid = ".($this->taxAuthId?$this->taxAuthId:"1").") AND ts.SortSequence < 90 ".
			"ORDER BY ts.SortSequence, t.SciName";
		//echo $sql;
		$result = $this->con->query($sql);
		while($row = $result->fetch_object()){
			$this->synonyms[$row->tid] = "<i>".$row->SciName."</i> ".$row->Author;
		}
		$result->free();
		if(!$this->taxAuthId && ($this->tid != $this->submittedTid)){
			unset($this->synonyms[$this->submittedTid]);
		}
		else{
			unset($this->synonyms[$this->tid]);
		}
 	}
 	
	public function getSynonyms(){
		return $this->synonyms;
	}

 	public function getSynonymStr(){
 		$str = "";
 		$cnt = 0;
 		if($this->synonyms){
			foreach ($this->synonyms as $value){
	 			switch($cnt){
	 				case 0:
	 					$str = $value;
	 					break;
	 				case 1:
	 					$str .= "<span class='syns' onclick=\"javascript: toggle('syns');\" style=\"cursor:pointer;display:inline;font-size:70%;vertical-align:sub\" title='Click here to show more synonyms'>,&nbsp;&nbsp;more</span>";
	 					$str .= "<span class='syns' onclick=\"javascript: toggle('syns');\" style=\"display:none;\">, ".$value;
	 					break;
	 				default:
	 					$str .= ", ".$value;
	 			}
	 			$cnt++;
			}
 		}
		if($str) $str .= "</span>";
 		return $str;
 	}
 	
	private function setTaxaImages(){
		$tidStr = implode(",",array_merge(Array($this->tid,$this->submittedTid),array_keys($this->synonyms)));
		$this->imageArr = Array();
		$sql = "SELECT ti.imgid, ti.url, ti.thumbnailurl, IFNULL(ti.photographer,CONCAT_WS(' ',u.firstname,u.lastname)) AS photographer, ".
			"ti.caption, ti.owner, ti.sourceurl, ti.copyright, ti.locality, ti.specimengui, ti.notes ".
			"FROM ((images ti LEFT JOIN users u ON ti.photographeruid = u.uid) ".
			"INNER JOIN taxa t ON ti.tid = t.TID) INNER JOIN taxstatus ts ON t.tid = ts.tid ".
			"WHERE (ts.taxauthid = 1 AND t.TID IN ($tidStr)) ".
			"ORDER BY ti.sortsequence";
		//echo "<div>".$sql."</div>";
		$result = $this->con->query($sql);
		$imgCnt = 0;
		while($row = $result->fetch_object()){
			$this->imageArr[$imgCnt]["imgid"] = $row->imgid;
			$this->imageArr[$imgCnt]["url"] = $row->url;
			$this->imageArr[$imgCnt]["thumbnailurl"] = $row->thumbnailurl;
			if($row->photographer) $this->imageArr[$imgCnt]["photographer"] = $row->photographer;
			if($row->caption) $this->imageArr[$imgCnt]["caption"] = $row->caption;
			if($row->owner) $this->imageArr[$imgCnt]["owner"] = $row->owner;
			if($row->sourceurl) $this->imageArr[$imgCnt]["sourceurl"] = $row->sourceurl;
			if($row->copyright) $this->imageArr[$imgCnt]["copyright"] = $row->copyright;
			if($row->locality) $this->imageArr[$imgCnt]["locality"] = $row->locality;
			if($row->specimengui) $this->imageArr[$imgCnt]["specimengui"] = $row->specimengui;
			if($row->notes) $this->imageArr[$imgCnt]["notes"] = $row->notes;
			$imgCnt++;
		}
		$result->close();
		if(!$this->imageArr) $this->imageArr = "No images";
 	}
 	
 	public function getTaxaImageCnt(){
 		if(!$this->imageArr){
			$this->setTaxaImages();
		}
 		if(is_array($this->imageArr)){
 			return count($this->imageArr);
 		}
 		else{
 			return 0;
 		}
 	}
 	
 	public function echoImages($start, $length){		//A length of 0 means show all images
 		if(!$this->imageArr){
			$this->setTaxaImages();
		}
		if(is_array($this->imageArr) && count($this->imageArr) >= $start){
			$end = ($length && count($this->imageArr) > $start + $length?$start + $length:count($this->imageArr));
			$spDisplay = $this->getDisplayName();
			for($n = $start;$n < $end;$n++){
				if($start == 0 && $length == 1){
					echo "<div id='centralimage'>";
				}
				else{
					echo "<div class='imgthumb'>";
				}
				$imgUrl = $this->imageArr[$n]["url"];
				if(array_key_exists("imageDomain",$GLOBALS) && substr($imgUrl,0,1)=="/"){
					$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
				}
				echo "<a href='".$imgUrl."'>";
				if($this->imageArr[$n]["thumbnailurl"]){
					list($width, $height) = getimagesize((array_key_exists("imageDomain",$GLOBALS)?$GLOBALS["imageDomain"]:"").$this->imageArr[$n]["thumbnailurl"]);
					if($n > 0 || $width > 190 || $height > 190){
						$imgUrl = $this->imageArr[$n]["thumbnailurl"];
						if(array_key_exists("imageDomain",$GLOBALS) && substr($this->imageArr[$n]["thumbnailurl"],0,1)=="/"){
							$imgUrl = $GLOBALS["imageDomain"].$this->imageArr[$n]["thumbnailurl"];
						}
					}
				}
				echo "<img src='".$imgUrl."' title='".$spDisplay."' alt='".$spDisplay." image loading' />";
				echo "</a>";
				echo "<div class='photographer'>";
				if(array_key_exists("photographer",$this->imageArr[$n])){
					echo $this->imageArr[$n]["photographer"]."&nbsp;&nbsp;";
				}
				echo "<span style='cursor:pointer;' onclick='toggleImgInfo(\"img".$n."\",this);'>";
				echo "<img style='width:10px;height:10px;border:0px;' src='../images/info.jpg'/>";
				echo "</span>";
				echo "</div>";
				echo "</div>\n";
			}
			for($n = $start;$n < $end;$n++){
				//Pane for image details
				echo "<div id='img".$n."' class='imgpopup'>";
				if(array_key_exists("caption",$this->imageArr[$n])) echo "<div><b>Caption:</b> ".$this->imageArr[$n]["caption"]."</div>";
				if(array_key_exists("photographer",$this->imageArr[$n])) echo "<div><b>Photographer:</b> ".$this->imageArr[$n]["photographer"]."</div>";
				if(array_key_exists("owner",$this->imageArr[$n])) echo "<div><b>Manager:</b> ".$this->imageArr[$n]["owner"]."</div>";
				if(array_key_exists("locality",$this->imageArr[$n])) echo "<div><b>Locality:</b> ".$this->imageArr[$n]["locality"]."</div>";
				if(array_key_exists("notes",$this->imageArr[$n])) echo "<div><b>Notes:</b> ".$this->imageArr[$n]["notes"]."</div>";
				if(array_key_exists("copyright",$this->imageArr[$n])) echo "<div><b>Copyright:</b> ".$this->imageArr[$n]["copyright"]."</div>";
				if(array_key_exists("sourceurl",$this->imageArr[$n])) echo "<div><a href='".$this->imageArr[$n]["sourceurl"]."'>Source Image</a></div>";
				if(array_key_exists("specimengui",$this->imageArr[$n])) echo "<div><b>Specimen Info:</b> <a href='../collections/individual/individual.php?gui=".$this->imageArr[$n]["specimengui"]."'>".$this->imageArr[$n]["specimengui"]."</a></div>";
				echo "</div>\n";
			}
			return true;
		}
		else{
			return false;
		}
 	}

	public function getTaxaLinks(){
		$links = Array();
		//Get hierarchy string
		$hierStr = "";
		$sqlHier = "SELECT ts.hierarchystr ".
			"FROM taxstatus ts ".
			"WHERE ts.taxauthid = 1 AND ts.tid = ".$this->tid;
		//echo $sqlHier;
		$resultHier = $this->con->query($sqlHier);
		while($rowHier = $resultHier->fetch_object()){
			$hierStr = $rowHier->hierarchystr;
		}
		$resultHier->close();

		//Get links
		if($hierStr){
			$sql = "SELECT tl.tlid, tl.url, tl.title, tl.owner, tl.notes ".
				"FROM taxalinks tl ".
				"WHERE tl.tid IN($hierStr) ORDER BY tl.sortsequence";
			//echo $sql;
			$result = $this->con->query($sql);
			while($row = $result->fetch_object()){
				$tlid = $row->tlid;
				$links[$tlid]["url"] = $row->url;
				$links[$tlid]["title"] = $row->title;
				$links[$tlid]["owner"] = $row->owner;
				$links[$tlid]["notes"] = $row->notes;
			}
			$result->close();
		}
		return $links;
	}

	public function getMapUrl($tidObj = 0){
		global $occurrenceModIsActive,$isAdmin;
		$urlArr = Array();
 		$tidStr = "";
 		if($tidObj){
	 		if(is_array($tidObj)){
	 			$tidStr = implode(",",$tidObj);
	 		}
	 		else{
	 			$tidStr = $tidObj;
	 		}
 		}
 		else{
 			$tidStr = implode(",",array_merge(Array($this->tid,$this->submittedTid),array_keys($this->synonyms)));
 		}
		
 		$urlArr = $this->getTaxaMap($tidStr);
 		if(!$urlArr && $occurrenceModIsActive && ($this->securityStatus == 1 || $isAdmin)){
			return $this->getGoogleStaticMap($tidStr);
		}
		return $urlArr;
	}
	
 	private function getTaxaMap($tidStr){
		$maps = Array();
		$sql = "SELECT tm.url, t.sciname ".
			"FROM taxamaps tm INNER JOIN taxa t ON tm.tid = t.tid ".
			"WHERE t.tid IN(".$tidStr.")";
		//echo $sql;
		$result = $this->con->query($sql);
		if($row = $result->fetch_object()){
			$imgUrl = $row->url;
			if(array_key_exists("imageDomain",$GLOBALS) && substr($imgUrl,0,1)=="/"){
				$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
			}
			$maps[$row->sciname] = $imgUrl;
		}
		$result->close();
		return $maps;
 	}
 	
 	private function getGoogleStaticMap($tidStr){
 		global $mappingBoundaries;
 		
 		$mapArr = Array();
 		$minLat = 90;
 		$maxLat = -90;
 		$minLong = 180;
 		$maxLong = -180;
 		$latlonArr = explode(";",$mappingBoundaries);

 		$sql = "SELECT DISTINCT t.sciname, gi.DecimalLatitude, gi.DecimalLongitude ".
			"FROM omoccurgeoindex gi INNER JOIN taxa t ON gi.tid = t.tid ".
			"WHERE (gi.tid IN ($tidStr)) ";
		if($latlonArr){
			$sql .= "AND (gi.DecimalLatitude BETWEEN ".$latlonArr[2]." AND ".$latlonArr[0].") ";
				"AND (gi.DecimalLongitude BETWEEN ".$latlonArr[3]." AND ".$latlonArr[1].") ";
		}
		$sql .= "LIMIT 50";
				//echo "<div>".$sql."</div>";
		$result = $this->con->query($sql);
 		$sciName = "";
		while($row = $result->fetch_object()){
			$sciName = ucfirst(strtolower(trim($row->sciname)));
			$lat = round($row->DecimalLatitude,2);
			if($lat < $minLat) $minLat = $lat;
			if($lat > $maxLat) $maxLat = $lat;
 			$long = round($row->DecimalLongitude,2);
			if($long < $minLong) $minLong = $long;
			if($long > $maxLong) $maxLong = $long;
 			$mapArr[] = $lat.",".$long;
		}
		$result->close();
		if(!$mapArr) return 0;
		$latDist = $maxLat - $minLat;
		$longDist = $maxLong - $minLong;
		$googleUrlLocal = $this->googleUrl;
		if($latDist < 3 || $longDist < 3) {
			$googleUrlLocal .= "&zoom=6";
		}
		$coordStr = implode("|",$mapArr);
		if(!$coordStr) return ""; 
		$googleUrlLocal .= "&markers=".$coordStr;
 		return Array($sciName => $googleUrlLocal);
 	}

	public function getDescriptions(){
		$descriptionsStr = "There is no description set for this taxon.";
		$descriptions = Array();
		$sql = "SELECT DISTINCT td.tdid, td.Heading, td.Description, td.DisplayLevel, td.DisplayHeader ".
			"FROM taxadescriptions td INNER JOIN taxstatus ts ON td.tid = ts.TidAccepted ".
			"WHERE (td.TID = $this->tid) AND (ts.taxauthid = 1) AND (td.Language = '".$this->language."') ".
			"ORDER BY td.SortSequence";
		//echo $sql;
		$result = $this->con->query($sql);
		while($row = $result->fetch_object()){
			$descriptions[$row->DisplayLevel][($row->DisplayHeader?$row->Heading:$row->tdid)] = $row->Description;
		}
		$result->free();
		return $descriptions;
	}

 	public function getFamily(){
 		return $this->family;
 	}
 
 	public function getRankId(){
 		return $this->rankId;
 	}
 
 	public function getParentTid(){
 		return $this->parentTid;
 	}
 
 	public function isAccepted(){
 		if($this->tid == $this->submittedTid){
 			return true;
 		}
 		else{
 			return false;
 		}
 	}
 
	public function getSecurityStatus(){
		return $this->securityStatus;
	}
 	
 	public function setClName($clv){
		$sql = "SELECT c.CLID, c.Name, c.Title, c.Type, c.parentclid, cp.name AS parentname ".
			"FROM fmchecklists c LEFT JOIN fmchecklists cp ON cp.clid = c.parentclid ";
		if(intval($clv)){
			$sql .= "WHERE c.CLID = '".$clv."'";
		}
		else{
			$sql .= "WHERE c.Name = '".$clv."' OR c.Title = '".$clv."'";
		}
		//echo $sql;
		$result = $this->con->query($sql);
		if($row = $result->fetch_object()){
			$this->clid = $row->CLID;
			$this->clName = $row->Name;
			$this->clTitle = $row->Title;
			$this->clType = $row->Type;
			$this->parentClid = $row->parentclid;
			$this->parentName = $row->parentname;
		}
		$result->close();
	}
	
	public function getClid(){
		return $this->clid;		
	}

	public function getClName(){
		return $this->clName;		
	}

	public function getClTitle(){
		return $this->clTitle;		
	}
	
	public function getClType(){
		return $this->clType;
	}

	public function getParentClid(){
		return $this->parentClid;
	}
	
	public function getParentName(){
		return $this->parentName;
	}
	
	public function getClInfo(){
		return $this->clInfo;
	}
	
	public function setProj($p){
		if(is_numeric($p)){
			$this->pid = $p;
			$sql = "SELECT p.projname FROM fmprojects p WHERE p.pid = ".$p;
			$rs = $this->con->query($sql);
			if($row = $rs->fetch_object()){
				$this->projName = $row->projname;
			}
			$rs->close();
		}
		else{
			$this->projName = $p;
			$sql = "SELECT p.pid FROM fmprojects p WHERE p.projname = '".$p."'";
			$rs = $this->con->query($sql);
			if($row = $rs->fetch_object()){
				$this->pid = $row->pid;
			}
			$rs->close();
		}
	}
	
	public function getProjName(){
		return $this->projName;
	}
	
	public function setLanguage($lang){
		$this->language = $lang;
	}
	
	public function getLanguage(){
		return $this->language;
	}
 }
?>
