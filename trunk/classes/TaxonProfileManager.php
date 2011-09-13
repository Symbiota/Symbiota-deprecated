<?php
include_once($serverRoot.'/config/dbconnection.php');

class TaxonProfileManager {

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
		$sql = 'SELECT t.TID, ts.family, t.SciName, t.Author, t.RankId, ts.ParentTID, t.SecurityStatus, ts.TidAccepted '. 
			'FROM taxstatus ts INNER JOIN taxa t ON ts.tid = t.TID '.
			'WHERE (ts.taxauthid = '.($this->taxAuthId?$this->taxAuthId:'1').') ';
		if(is_numeric($t)){
			$sql .= 'AND (t.TID = '.$this->con->real_escape_string($t).') ';
		}
		else{
			$sql .= 'AND (t.SciName = "'.$this->con->real_escape_string($t).'") ';
		}
		//echo $sql;
		$result = $this->con->query($sql);
		if($row = $result->fetch_object()){
			if(strpos($row->SciName," spp.") && $row->TID != $row->ParentTID){
				$this->clid = 0;
				$this->clName = "";
				$this->parentClid = 0;
				$this->parentName = "";
				$this->setTaxon($row->ParentTID);
			}
			else{
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
 		if(is_numeric($id)){
	 		$this->taxAuthId = $this->con->real_escape_string($id);
 		}
 	}

	public function setSppData(){
		$this->sppArray = Array();
		$sql = '';
		if($this->clid){
			/*$sql = "SELECT DISTINCT if(t.rankid=220,t.tid,ts.parenttid) AS tid, ".
				"CONCAT_WS(' ',t.unitind1,t.unitname1,t.unitind2,t.unitname2) AS sciname, t.securitystatus ".
				"FROM (taxa t INNER JOIN taxstatus ts ON t.Tid = ts.tid) ".
				"INNER JOIN fmchklsttaxalink ctl ON ctl.TID = ts.tid ".
				"WHERE (ctl.clid = ".$this->clid.") AND (ts.taxauthid = 1) AND ".
				"((ts.hierarchystr LIKE '%,".$this->tid.",%') OR (ts.hierarchystr LIKE '%,".$this->tid."'))"; */
			$sql = 'SELECT t.sciname, t.tid, t.securitystatus '. 
				'FROM taxa t INNER JOIN (SELECT DISTINCT CONCAT_WS(" ",t.unitind1,t.unitname1,t.unitind2,t.unitname2) AS sciname '.
				'FROM (taxa t INNER JOIN taxstatus ts ON t.Tid = ts.tid) '.
				'INNER JOIN fmchklsttaxalink ctl ON ctl.TID = ts.tid '. 
				'WHERE (ctl.clid = '.$this->clid.') AND (ts.taxauthid = 1) '.
				'AND ((ts.hierarchystr LIKE "%,'.$this->tid.',%") OR (ts.hierarchystr LIKE "%,'.$this->tid.'"))) intab ON t.sciname = intab.sciname';
		}
		elseif($this->pid){
			/*$sql = "SELECT DISTINCT if(t.rankid=220,t.tid,ts.parenttid) AS tid, ".
				"CONCAT_WS(' ',t.unitind1,t.unitname1,t.unitind2,t.unitname2) AS sciname, t.securitystatus ".
				"FROM (((taxa t INNER JOIN taxstatus ts ON t.Tid = ts.tid) ".
				"INNER JOIN taxstatus ts2 ON t.Tid = ts2.tidaccepted) ".
				"INNER JOIN fmchklsttaxalink ctl ON ts.Tid = ctl.TID) ".
				"INNER JOIN fmchklstprojlink cpl ON ctl.clid = cpl.clid ".
				"WHERE (ts.taxauthid = 1) AND (ts2.taxauthid = 1) AND (cpl.pid = ".$this->pid.") AND ".
				"((ts.hierarchystr LIKE '%,".$this->tid.",%') OR (ts.hierarchystr LIKE '%,".$this->tid."'))";*/
			$sql = 'SELECT DISTINCT t.sciname, t.tid, t.securitystatus '.
				'FROM taxa t INNER JOIN (SELECT CONCAT_WS(" ",t.unitind1,t.unitname1,t.unitind2,t.unitname2) AS sciname '.
				'FROM (((taxa t INNER JOIN taxstatus ts ON t.Tid = ts.tid) '.
				'INNER JOIN taxstatus ts2 ON t.Tid = ts2.tidaccepted) '.
				'INNER JOIN fmchklsttaxalink ctl ON ts.Tid = ctl.TID) '.
				'INNER JOIN fmchklstprojlink cpl ON ctl.clid = cpl.clid '.
				'WHERE (ts.taxauthid = 1) AND (ts2.taxauthid = 1) AND (cpl.pid = '.$this->pid.') AND '.
				'((ts.hierarchystr LIKE "%,'.$this->tid.',%") OR (ts.hierarchystr LIKE "%,'.$this->tid.'"))'.
				') intab ON t.sciname = intab.sciname';
		}
		else{
			/*$sql = "SELECT DISTINCT if(t.rankid=220,t.tid,ts.parenttid) AS tid, ".
				"CONCAT_WS(' ',t.unitind1,t.unitname1,t.unitind2,t.unitname2) AS sciname, t.securitystatus ".
				"FROM taxa t INNER JOIN taxstatus ts ON t.Tid = ts.Tid ".
				"WHERE (ts.taxauthid = 1) AND (ts.Tid = ts.TidAccepted) AND ".
				"((ts.hierarchystr LIKE '%,".$this->tid.",%') OR (ts.hierarchystr LIKE '%,".$this->tid."'))";*/
			$sql = 'SELECT DISTINCT t.sciname, t.tid, t.securitystatus '.
				'FROM taxa t INNER JOIN taxstatus ts ON t.Tid = ts.TidAccepted '.
				'WHERE (ts.taxauthid = 1) AND (t.rankid = 220) AND '.
				'((ts.hierarchystr LIKE "%,'.$this->tid.',%") OR (ts.hierarchystr LIKE "%,'.$this->tid.'"))';
		}
		//echo $sql;
		
		$tids = Array();
		$result = $this->con->query($sql);
		while($row = $result->fetch_object()){
			$sn = ucfirst(strtolower($row->sciname));
			$this->sppArray[$sn]["tid"] = $row->tid;
			$this->sppArray[$sn]["security"] = $row->securitystatus;  
			$tids[] = $row->tid;
		}
		$result->close();
		
		//If no tids exist because there are no species in default project, grab all species from that taxon
		if(!$tids){
			$sql = "SELECT DISTINCT t.tid, t.sciname, t.securitystatus ".
				"FROM taxa t INNER JOIN taxstatus ts ON t.Tid = ts.TidAccepted ".
				"WHERE (ts.taxauthid = 1) AND (t.rankid = 220) AND ".
				"((ts.hierarchystr LIKE '%,".$this->tid.",%') OR (ts.hierarchystr LIKE '%,".$this->tid."'))";
			//echo $sql;
			
			$result = $this->con->query($sql);
			while($row = $result->fetch_object()){
				$sn = ucfirst(strtolower($row->sciname));
				$this->sppArray[$sn]["tid"] = $row->tid;
				$this->sppArray[$sn]["security"] = $row->securitystatus;  
				$tids[] = $row->tid;
			}
			$result->close();
		}
		
		if($tids){
			//Get Images 
			$sql = 'SELECT t.sciname, t.tid, i.imgid, i.url, i.thumbnailurl, i.caption, '.
				'IFNULL(i.photographer,CONCAT_WS(" ",u.firstname,u.lastname)) AS photographer '.
				'FROM images i INNER JOIN '.
				'(SELECT ts1.tid, SUBSTR(MIN(CONCAT(LPAD(i.sortsequence,6,"0"),i.imgid)),7) AS imgid '. 
				'FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN images i ON ts2.tid = i.tid '.
				'WHERE ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND (ts1.tid IN('.implode(',',$tids).')) '.
				'GROUP BY ts1.tid) i2 ON i.imgid = i2.imgid '.
				'INNER JOIN taxa t ON i2.tid = t.tid '.
				'LEFT JOIN users u ON i.photographeruid = u.uid ';
			//echo $sql;
			$result = $this->con->query($sql);
			while($row = $result->fetch_object()){
				$sciName = ucfirst(strtolower($row->sciname));
				if(!array_key_exists($sciName,$this->sppArray)){
					$firstPos = strpos($sciName," ",2)+2;
					$sciName = substr($sciName,0,strpos($sciName," ",$firstPos));
				}
				$this->sppArray[$sciName]["imgid"] = $row->imgid;
				$this->sppArray[$sciName]["url"] = $row->url;
				$this->sppArray[$sciName]["thumbnailurl"] = $row->thumbnailurl;
				$this->sppArray[$sciName]["photographer"] = $row->photographer;
				$this->sppArray[$sciName]["caption"] = $row->caption;
			}
			$result->close();
		}
		
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
		$sql = 'SELECT DISTINCT v.VernacularName, v.language '.
			'FROM taxavernaculars v INNER JOIN taxstatus ts ON v.tid = ts.tidaccepted '.
			'WHERE (ts.TID = '.$this->tid.') AND (v.SortSequence < 90) '.
			'ORDER BY v.SortSequence,v.VernacularName';
		//echo $sql;
		$result = $this->con->query($sql);
		$tempVernArr = array();
		while($row = $result->fetch_object()){
			$langStr = ucwords($row->language);
			if($this->language != $langStr){
				$tempVernArr[$langStr][] = $row->VernacularName;
			}
			else{
				$this->vernaculars[] = $row->VernacularName;
			}
		}
		ksort($tempVernArr);
		foreach($tempVernArr as $lang => $vArr){
			$this->vernaculars[] = '('.$lang.': '.implode(', ',$vArr).')';
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
 			$str .= "<span class='verns' onclick=\"javascript: toggle('verns');\" style='cursor:pointer;display:inline;font-size:70%;' title='Click here to show more common names'>,&nbsp;&nbsp;more...</span>";
 			$str .= "<span class='verns' onclick=\"javascript: toggle('verns');\" style='display:none;'>, ";
 			$str .= implode(", ",$this->vernaculars);
 			$str .= "</span>";
 		}
 		return $str;
 	}
 	
 	public function setSynonyms(){
		$this->synonyms = Array();
		$sql = 'SELECT t.tid, t.SciName, t.Author '.
			'FROM taxstatus ts INNER JOIN taxa t ON ts.Tid = t.TID '.
			'WHERE (ts.TidAccepted = '.$this->tid.') AND (ts.taxauthid = '.
			($this->taxAuthId?$this->taxAuthId:'1').') AND ts.SortSequence < 90 '.
			'ORDER BY ts.SortSequence, t.SciName';
		//echo $sql;
		$result = $this->con->query($sql);
		while($row = $result->fetch_object()){
			$this->synonyms[$row->tid] = '<i>'.$row->SciName.'</i> '.$row->Author;
		}
		$result->close();
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
		if($str && $cnt > 1) $str .= "</span>";
 		return $str;
 	}
 	
	private function setTaxaImages(){
		$tidArr = Array($this->tid);
		$sql1 = 'SELECT DISTINCT tid FROM taxstatus '.
			'WHERE taxauthid = 1 AND tid = tidaccepted AND ((hierarchystr LIKE "%,'.$this->tid.',%") OR (hierarchystr LIKE "%,'.$this->tid.'"))';
		$rs1 = $this->con->query($sql1);
		while($r1 = $rs1->fetch_object()){
			$tidArr[] = $r1->tid;
		}
		$rs1->close();
		
		$tidStr = implode(",",$tidArr);
		$this->imageArr = Array();
		$sql = 'SELECT ti.imgid, ti.url, ti.thumbnailurl, ti.caption, '.
			'IFNULL(ti.photographer,CONCAT_WS(" ",u.firstname,u.lastname)) AS photographer '.
			'FROM (images ti LEFT JOIN users u ON ti.photographeruid = u.uid) '.
			'INNER JOIN taxstatus ts ON ti.tid = ts.tid '.
			'WHERE (ts.taxauthid = 1 AND ts.tidaccepted IN ('.$tidStr.')) AND ti.SortSequence < 500 '.
			'ORDER BY ti.sortsequence';
		//echo $sql;
		$result = $this->con->query($sql);
		while($row = $result->fetch_object()){
			$this->imageArr[$row->imgid]["url"] = $row->url;
			$this->imageArr[$row->imgid]["thumbnailurl"] = $row->thumbnailurl;
			$this->imageArr[$row->imgid]["photographer"] = $row->photographer;
			$this->imageArr[$row->imgid]["caption"] = $row->caption;
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
 	
 	public function echoImages($start, $length, $useThumbnail = 1){		//A length of 0 means show all images
 		if(!$this->imageArr){
			$this->setTaxaImages();
		}
		if(is_array($this->imageArr) && count($this->imageArr) >= $start){
			$length = ($length&&count($this->imageArr)>$length+$start?$length:count($this->imageArr)-$start);
			$spDisplay = $this->getDisplayName();
			$iArr = array_slice($this->imageArr,$start,$length,true);
			foreach($iArr as $imgId => $imgObj){
				if($start == 0 && $length == 1){
					echo "<div id='centralimage'>";
				}
				else{
					echo "<div class='imgthumb'>";
				}
				$imgUrl = $imgObj["url"];
				$imgThumbnail = $imgObj["thumbnailurl"];
				if(array_key_exists("imageDomain",$GLOBALS) && substr($imgUrl,0,1)=="/"){
					$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
					$imgThumbnail = $GLOBALS["imageDomain"].$imgThumbnail;
				}
				echo "<a href='../imagelib/imgdetails.php?imgid=".$imgId."'>";
				if($useThumbnail && $imgObj["thumbnailurl"]){
					list($width, $height) = getimagesize((stripos($imgThumbnail,"http")===0?"":"http://".$_SERVER['HTTP_HOST']).$imgThumbnail);
					if(($start != 0 && $length != 1) || $width > 190 || $height > 190){
						$imgUrl = $imgThumbnail;
					}
				}
				echo "<img src='".$imgUrl."' title='".$imgObj["caption"]."' alt='".$spDisplay." image' />";
				echo "</a>";
				echo "<div class='photographer'>";
				if($imgObj["photographer"]){
					echo $imgObj["photographer"]."&nbsp;&nbsp;";
				}
				echo "<a href='../imagelib/imgdetails.php?imgid=".$imgId."'>";
				echo "<img style='width:10px;height:10px;border:0px;' src='../images/info.jpg'/>";
				echo "</a>";
				echo "</div>\n";
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
		$hierStr = '';
		$sqlHier = 'SELECT ts.hierarchystr FROM taxstatus ts '.
			'WHERE ts.taxauthid = 1 AND (ts.tid = '.$this->tid.')';
		//echo $sqlHier;
		$resultHier = $this->con->query($sqlHier);
		while($rowHier = $resultHier->fetch_object()){
			$hierStr = $rowHier->hierarchystr;
		}
		$resultHier->close();

		//Get links
		if($hierStr){
			$sql = 'SELECT tl.tlid, tl.url, tl.title, tl.owner, tl.notes, tl.sortsequence '.
				'FROM taxalinks tl '.
				'WHERE (tl.tid IN('.$this->tid.','.$hierStr.')) '.
				'ORDER BY tl.sortsequence, tl.title';
			//echo $sql;
			$result = $this->con->query($sql);
			while($row = $result->fetch_object()){
				$links[] = array('title'=>$row->title,'url'=>$row->url,'notes'=>$row->notes,'sortseq'=>$row->sortsequence);
			}
			$result->close();
		}
		return $links;
	}

	public function getMapUrl($tidObj = 0){
		global $occurrenceModIsActive,$isAdmin,$userRights;
		$urlArr = Array();
 		$tidStr = '';
 		if($tidObj){
	 		if(is_array($tidObj)){
	 			$tidStr = implode(",",$tidObj);
	 		}
	 		elseif(is_numeric($tidObj)){
	 			$tidStr = $tidObj;
	 		}
 		}
 		else{
			$tidArr = Array($this->tid,$this->submittedTid);
			if($this->synonyms) $tidArr = array_merge($tidArr,array_keys($this->synonyms));
			$tidStr = implode(",",$tidArr);
 		}
		
 		$urlArr = $this->getTaxaMap($tidStr);
 		if(!$urlArr && $occurrenceModIsActive && ($this->securityStatus == 0 || $isAdmin || array_key_exists("CollAdmin",$userRights) || array_key_exists("RareSppAdmin",$userRights) || array_key_exists("RareSppReadAll",$userRights))){
			return $this->getGoogleStaticMap($tidStr);
		}
		return $urlArr;
	}
	
 	private function getTaxaMap($tidStr){
		$maps = Array();
		if($tidStr){
			$sql = 'SELECT tm.url, t.sciname '.
				'FROM taxamaps tm INNER JOIN taxa t ON tm.tid = t.tid '.
				'WHERE (t.tid IN('.$tidStr.'))';
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
		}
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

 		$sqlBase = "SELECT DISTINCT t.sciname, gi.DecimalLatitude, gi.DecimalLongitude ".
			"FROM omoccurgeoindex gi INNER JOIN taxa t ON gi.tid = t.tid ".
			"WHERE (gi.tid IN ($tidStr)) ";
 		$sql = $sqlBase;
		if($latlonArr){
			$sql .= "AND (gi.DecimalLatitude BETWEEN ".$latlonArr[2]." AND ".$latlonArr[0].") ".
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
		if(!$mapArr && $latlonArr){
			$result = $this->con->query($sqlBase."LIMIT 50");
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
		}
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
		$descriptionsStr = '';
		if($this->tid){
			$descriptionsStr = "There is no description set for this taxon.";
			$descriptions = Array();
			$sql = 'SELECT DISTINCT tdb.tdbid, tdb.caption, tdb.source, tdb.sourceurl, '.
				'tds.tdsid, tds.heading, tds.statement, tds.displayheader '.
				'FROM (taxstatus ts INNER JOIN taxadescrblock tdb ON ts.TidAccepted = tdb.tid) '.
				'INNER JOIN taxadescrstmts tds ON tdb.tdbid = tds.tdbid '.
				'WHERE (tdb.tid = '.$this->tid.') AND (ts.taxauthid = 1) AND (tdb.Language = "'.$this->language.'") '.
				'ORDER BY tdb.displaylevel,tds.sortsequence';
			//echo $sql;
			$result = $this->con->query($sql);
			while($row = $result->fetch_object()){
				$tdbId = $row->tdbid;
				if(!array_key_exists($tdbId,$descriptions)){
					$descriptions[$tdbId]["caption"] = $row->caption;
					$descriptions[$tdbId]["source"] = $row->source;
					$descriptions[$tdbId]["url"] = $row->sourceurl;
				}
				$header = $row->displayheader?"<b>".$row->heading."</b>: ":"";
				$descriptions[$tdbId]["desc"][$row->tdsid] = $header.$row->statement;
			}
			$result->close();
			return $descriptions;
		}
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
		$sql = "SELECT c.CLID, c.Name, c.parentclid, cp.name AS parentname ".
			"FROM fmchecklists c LEFT JOIN fmchecklists cp ON cp.clid = c.parentclid ";
		$inValue = $this->con->real_escape_string($clv);
		if($intVal = intval($inValue)){
			$sql .= 'WHERE (c.CLID = '.$intVal.')';
		}
		else{
			$sql .= "WHERE (c.Name = '".$inValue.
				"') OR (c.Title = '".$inValue."')";
		}
		//echo $sql;
		$result = $this->con->query($sql);
		if($row = $result->fetch_object()){
			$this->clid = $row->CLID;
			$this->clName = $row->Name;
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
			$this->pid = $this->con->real_escape_string($p);
			$sql = "SELECT p.projname FROM fmprojects p WHERE (p.pid = ".$this->con->real_escape_string($p).')';
			$rs = $this->con->query($sql);
			if($row = $rs->fetch_object()){
				$this->projName = $row->projname;
			}
			$rs->close();
		}
		else{
			$this->projName = $p;
			$sql = 'SELECT p.pid FROM fmprojects p WHERE (p.projname = "'.$this->con->real_escape_string($p).'")';
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
		$this->language = ucwords($this->con->real_escape_string($lang));
	}
	
	public function getLanguage(){
		return $this->language;
	}
 }
?>
