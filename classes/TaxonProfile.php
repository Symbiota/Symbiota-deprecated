<?php
include_once('Manager.php');

class TaxonProfile extends Manager {

	private $tid;
	private $sciName;
	private $author;
	private $taxAuthId = 1;
	private $rankId;
	private $parentTid;
	private $family;
	private $acceptedTaxa = array();
	private $synonymArr = array();
	private $submittedArr = array();

	private $langArr = array();
	private $taxaLinks = array();
	private $imageArr;
	private $sppArray;

	private $displayLocality = 1;

	public function __construct(){
		parent::__construct();
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function setTid($tid){
		if(is_numeric($tid)){
			$this->tid = $tid;
			if($this->setTaxon()){
				if(count($this->acceptedTaxa) < 2){
					$this->setSynonyms();
				}
			}
		}
		//If name was redirected to accepted name, tid returned will be different
		return $this->tid;
	}

	private function setTaxon(){
		$status = false;
		if($this->tid){
			$sql = 'SELECT tid, sciname, author FROM taxa WHERE (tid = '.$this->tid.') ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->submittedArr['tid'] = $r->tid;
				$this->submittedArr['sciname'] = $r->sciname;
				$this->submittedArr['author'] = $r->author;
			}
			$rs->free();

			//Set acceptance, parent, and family
			$sql2 = 'SELECT ts.family, ts.parenttid, t.tid, t.sciname, t.author, t.rankid, t.securitystatus '.
				'FROM taxstatus ts INNER JOIN taxa t ON ts.tidaccepted = t.tid '.
				'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (ts.tid = '.$this->tid.') ';
			//echo $sql;
			$rs2 = $this->conn->query($sql2);
			while($r2 = $rs2->fetch_object()){
				$this->tid = $r2->tid;
				$this->sciName = $r2->sciname;
				$this->author = $r2->author;
				$this->rankId = $r2->rankid;
				$this->family = $r2->family;
				$this->parentTid = $r2->parenttid;
				$this->acceptedTaxa[$r2->tid] = array('sciname'=>$r2->sciname,'author'=>$r2->author);
				if($r2->securitystatus > 0) $this->displayLocality = 0;
				$status = true;
			}
			$rs2->free();
			if(!$this->displayLocality){
				if(isset($GLOBALS['IS_ADMIN']) && $GLOBALS['IS_ADMIN']) $this->displayLocality = 1;
				elseif(isset($GLOBALS['USER_RIGHTS'])){
					if(isset($GLOBALS['USER_RIGHTS']['RareSppReadAll'])) $this->displayLocality = 1;
					if(isset($GLOBALS['USER_RIGHTS']['RareSppAdmin'])) $this->displayLocality = 1;
					if(isset($GLOBALS['USER_RIGHTS']['CollAdmin'])) $this->displayLocality = 1;
				}
			}
		}
		return $status;
	}

	//Synonyms
	public function setSynonyms(){
		if($this->tid){
			$sql = 'SELECT t.tid, t.sciname, t.author '.
				'FROM taxstatus ts INNER JOIN taxa t ON ts.tid = t.tid '.
				'WHERE (ts.tidaccepted = '.$this->tid.') AND (ts.taxauthid = '.$this->taxAuthId.') AND (ts.tidaccepted != t.tid) AND (ts.SortSequence < 90) '.
				'ORDER BY ts.SortSequence, t.SciName';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$this->synonymArr[$r->tid]['sciname'] = $r->sciname;
				$this->synonymArr[$r->tid]['author'] = $r->author;
			}
			$rs->free();
		}
	}

	//Vernaculars
	public function getVernaculars(){
		$retArr = array();
		if($this->tid){
			$sql = 'SELECT v.vid, v.vernacularname, l.langname '.
				'FROM taxavernaculars v INNER JOIN adminlanguages l ON v.langid = l.langid '.
				'WHERE (v.TID IN('.$this->tid.($this->synonymArr?','.implode(',',array_keys($this->synonymArr)):'').')) AND (v.SortSequence < 90) '.
				'ORDER BY v.SortSequence,v.VernacularName';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->langname][$r->vid] = $r->vernacularname;
			}
			$rs->free();
		}
		return $retArr;
	}

	//Images functions
	public function echoImages($start, $length = 0, $useThumbnail = 1){		//length=0 => means show all images
		$status = false;
		if(!isset($this->imageArr)){
			$this->setTaxaImages();
		}
		if(!$this->imageArr || count($this->imageArr) < $start) return false;
		$trueLength = ($length&&count($this->imageArr)>$length+$start?$length:count($this->imageArr)-$start);
		$iArr = array_slice($this->imageArr,$start,$trueLength,true);
		foreach($iArr as $imgId => $imgObj){
			if($start == 0 && $trueLength == 1){
				echo "<div id='centralimage'>";
			}
			else{
				echo "<div class='imgthumb'>";
			}
			$imgUrl = $imgObj["url"];
			$imgAnchor = '../imagelib/imgdetails.php?imgid='.$imgId;
			$imgThumbnail = $imgObj["thumbnailurl"];
			if(array_key_exists("IMAGE_DOMAIN",$GLOBALS)){
				//Images with relative paths are on another server
				if(substr($imgUrl,0,1)=="/") $imgUrl = $GLOBALS["IMAGE_DOMAIN"].$imgUrl;
				if(substr($imgThumbnail,0,1)=="/") $imgThumbnail = $GLOBALS["IMAGE_DOMAIN"].$imgThumbnail;
			}
			if($imgObj['occid']){
				$imgAnchor = '../collections/individual/index.php?occid='.$imgObj['occid'];
			}
			if($useThumbnail){
				if($imgObj['thumbnailurl']){
					$imgUrl = $imgThumbnail;
				}
			}
			echo '<div class="tptnimg"><a href="'.$imgAnchor.'">';
			$titleStr = $imgObj['caption'];
			if($imgObj['sciname'] != $this->sciName) $titleStr .= ' (linked from '.$imgObj['sciname'].')';
			echo '<img src="'.$imgUrl.'" title="'.$titleStr.'" alt="'.$this->sciName.' image" />';
			/*
			 if($length){
			 echo '<img src="'.$imgUrl.'" title="'.$imgObj['caption'].'" alt="'.$spDisplay.' image" />';
			 }
			 else{
			 //echo '<img class="delayedimg" src="" delayedsrc="'.$imgUrl.'" />';
			 }
			 */
			echo '</a></div>';
			echo '<div class="photographer">';
			if($imgObj['photographer']){
				echo $imgObj['photographer'].'&nbsp;&nbsp;';
			}
			echo '</div>';
			echo '</div>';
			$status = true;
		}
		return $status;
	}

	private function setTaxaImages(){
		$this->imageArr = array();
		if($this->tid){
			$tidArr = Array($this->tid);
			$sql1 = 'SELECT DISTINCT ts.tid '.
				'FROM taxstatus ts INNER JOIN taxaenumtree tn ON ts.tid = tn.tid '.
				'WHERE tn.taxauthid = 1 AND ts.taxauthid = 1 AND ts.tid = ts.tidaccepted '.
				'AND tn.parenttid = '.$this->tid;
			$rs1 = $this->conn->query($sql1);
			while($r1 = $rs1->fetch_object()){
				$tidArr[] = $r1->tid;
			}
			$rs1->free();

			$tidStr = implode(",",$tidArr);
			$sql = 'SELECT t.sciname, ti.imgid, ti.url, ti.thumbnailurl, ti.originalurl, ti.caption, ti.occid, '.
				'IFNULL(ti.photographer,CONCAT_WS(" ",u.firstname,u.lastname)) AS photographer '.
				'FROM images ti LEFT JOIN users u ON ti.photographeruid = u.uid '.
				'INNER JOIN taxstatus ts ON ti.tid = ts.tid '.
				'INNER JOIN taxa t ON ti.tid = t.tid '.
				'WHERE (ts.taxauthid = 1 AND ts.tidaccepted IN ('.$tidStr.')) AND ti.SortSequence < 500 AND ti.thumbnailurl IS NOT NULL ';
			if(!$this->displayLocality) $sql .= 'AND ti.occid IS NULL ';
			$sql .= 'ORDER BY ti.sortsequence LIMIT 100';
			//echo $sql;
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$imgUrl = $row->url;
				if($imgUrl == 'empty' && $row->originalurl) $imgUrl = $row->originalurl;
				$this->imageArr[$row->imgid]["url"] = $imgUrl;
				$this->imageArr[$row->imgid]["thumbnailurl"] = $row->thumbnailurl;
				$this->imageArr[$row->imgid]["photographer"] = $row->photographer;
				$this->imageArr[$row->imgid]["caption"] = $row->caption;
				$this->imageArr[$row->imgid]["occid"] = $row->occid;
				$this->imageArr[$row->imgid]["sciname"] = $row->sciname;
			}
			$result->free();
		}
	}

	public function getImageCount(){
		if(!isset($this->imageArr)) return 0;
		return count($this->imageArr);
	}

	//Map functions
	public function getMapArr($tidStr = 0){
		$maps = Array();
		if(!$tidStr){
			$tidArr = Array($this->tid,$this->submittedArr['tid']);
			if($this->synonymArr) $tidArr = array_merge($tidArr,array_keys($this->synonymArr));
			$tidStr = trim(implode(",",$tidArr),' ,');
		}
		if($tidStr){
			$sql = 'SELECT tm.url, t.sciname '.
					'FROM taxamaps tm INNER JOIN taxa t ON tm.tid = t.tid '.
					'WHERE (t.tid IN('.$tidStr.'))';
			//echo $sql;
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$imgUrl = $row->url;
				if(array_key_exists("IMAGE_DOMAIN",$GLOBALS) && substr($imgUrl,0,1)=="/"){
					$imgUrl = $GLOBALS["IMAGE_DOMAIN"].$imgUrl;
				}
				$maps[] = $imgUrl;
			}
			$result->free();
		}
		return $maps;
	}

	public function getGoogleStaticMap($tidStr = 0){
		if(!$tidStr){
			$tidArr = Array($this->tid,$this->submittedArr['tid']);
			if($this->synonymArr) $tidArr = array_merge($tidArr,array_keys($this->synonymArr));
			$tidStr = trim(implode(",",$tidArr),' ,');
		}

		$mapArr = Array();
		if($tidStr){
			$minLat = 90;
			$maxLat = -90;
			$minLong = 180;
			$maxLong = -180;
			$latlonArr = array();
			if(isset($GLOBALS['MAPPING_BOUNDARIES'])){
				$latlonArr = explode(";",$GLOBALS['MAPPING_BOUNDARIES']);
			}

			$sqlBase = 'SELECT DecimalLatitude AS declat, DecimalLongitude AS declng FROM omoccurgeoindex WHERE (tid IN ('.$tidStr.')) ';
			/*
			$sqlBase = 'SELECT DISTINCT tidinterpreted, round(decimallatitude,2) AS declat, round(decimallongitude,2) AS declng '.
				'FROM omoccurrences '.
				'WHERE (tidinterpreted IN ('.$tidStr.')) AND (cultivationStatus IS NULL OR cultivationStatus = 0) AND (coordinateUncertaintyInMeters IS NULL OR coordinateUncertaintyInMeters < 10000) ';
			*/
			$sql = $sqlBase;
			if(count($latlonArr)==4){
				$sql .= 'AND (DecimalLatitude BETWEEN '.$latlonArr[2].' AND '.$latlonArr[0].') AND (DecimalLongitude BETWEEN '.$latlonArr[3].' AND '.$latlonArr[1].') ';
			}
			/*
			 else{
				$sql .= 'AND (DecimalLatitude BETWEEN -80 AND 80) AND (DecimalLongitude BETWEEN -160 AND 160) ';
			}
			*/
			$sql .= 'ORDER BY RAND() LIMIT 50';
			//echo "<div>".$sql."</div>"; exit;
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$lat = round($row->declat,2);
				if($lat < $minLat) $minLat = $lat;
				if($lat > $maxLat) $maxLat = $lat;
				$long = round($row->declng,2);
				if($long < $minLong) $minLong = $long;
				if($long > $maxLong) $maxLong = $long;
				$mapArr[] = $lat.",".$long;
			}
			$result->free();
			if(count($mapArr) < 50 && $latlonArr){
				$result = $this->conn->query($sqlBase.' LIMIT 50');
				//$result = $this->conn->query($sqlBase.'AND (DecimalLatitude BETWEEN -80 AND 80) AND (DecimalLongitude BETWEEN -160 AND 160) LIMIT 50');
				while($row = $result->fetch_object()){
					$lat = round($row->declat,2);
					if($lat < $minLat) $minLat = $lat;
					if($lat > $maxLat) $maxLat = $lat;
					$long = round($row->declng,2);
					if($long < $minLong) $minLong = $long;
					if($long > $maxLong) $maxLong = $long;
					$mapArr[] = $lat.','.$long;
				}
				$result->free();
			}
			if(!$mapArr) return 0;
			$latDist = $maxLat - $minLat;
			$longDist = $maxLong - $minLong;

			$googleUrl = '//maps.googleapis.com/maps/api/staticmap?size=256x256&maptype=terrain';
			if(array_key_exists('GOOGLE_MAP_KEY',$GLOBALS) && $GLOBALS['GOOGLE_MAP_KEY']) $googleUrl .= '&key='.$GLOBALS['GOOGLE_MAP_KEY'];
			if($latDist < 3 || $longDist < 3) {
				$googleUrl .= '&zoom=6';
			}
		}
		$coordStr = implode('|',$mapArr);
		if(!$coordStr) return '';
		$googleUrl .= '&markers='.$coordStr;
		return $googleUrl;
	}

	//Taxon Descriptions
	public function getDescriptionStr(){
		global $LANG;
		$retStr = '';
		$descArr = $this->getDescriptions();
		if($descArr || $this->taxaLinks){
			$retStr .= '<div id="desctabs" class="ui-tabs" style="display:none">';
			$retStr .= '<ul class="ui-tabs-nav">';
			$capCnt = 1;
			foreach($descArr as $dArr){
				foreach($dArr as $id => $vArr){
					$cap = $vArr["caption"];
					if(!$cap){
						$cap = $LANG['DESCRIPTION'].' #'.$capCnt;
						$capCnt++;
					}
					$retStr .= '<li><a href="#tab'.$id.'" class="selected">'.$cap.'</a></li>';
				}
			}
			if($this->taxaLinks){
				$retStr .= '<li><a href="#tab-links" class="selected">'.($LANG['WEB_LINKS']?$LANG['WEB_LINKS']:'Web Links').'</a></li>';
			}
			$retStr .= '</ul>';
			foreach($descArr as $dArr){
				foreach($dArr as $id => $vArr){
					$retStr .= '<div id="tab'.$id.'" class="sptab" style="width:94%;">';
					if($vArr["source"]){
						$retStr .= '<div id="descsource" style="float:right;">';
						if($vArr["url"]){
							$retStr .= '<a href="'.$vArr['url'].'" target="_blank">';
						}
						$retStr .= $vArr["source"];
						if($vArr["url"]){
							$retStr .= '</a>';
						}
						$retStr .= '</div>';
					}
					$descArr = $vArr["desc"];
					$retStr .= '<div style="clear:both;">';
					foreach($descArr as $tdsId => $stmt){
						$retStr .= $stmt.' ';
					}
					$retStr .= '</div>';
					$retStr .= '</div>';
				}
			}
			if($this->taxaLinks){
				$retStr .= '<div id="tab-links" class="sptab" style="width:94%;">';
				$retStr .= '<ul style="margin-top: 50px">';
				foreach($this->taxaLinks as $l){
					$urlStr = str_replace('--SCINAME--',urlencode($this->sciName),$l['url']);
					$retStr .= '<li><a href="'.$urlStr.'" target="_blank">'.$l['title'].'</a></li>';
					if($l['notes']) $retStr .= ' '.$l['notes'];
				}
				$retStr .= '</ul>';
				$retStr .= '</div>';
			}
			$retStr .= '</div>';
		}
		else{
			$retStr = '<div style="margin:70px 0px 20px 50px">'.$LANG['DESCRIPTION_NOT_AVAILABLE'].'</div>';
		}
		return $retStr;
	}

	private function getDescriptions(){
		$retArr = Array();
		if($this->tid){
			$rsArr = array();
			$sql = 'SELECT ts.tid, tdb.tdbid, tdb.caption, tdb.source, tdb.sourceurl, '.
				'tds.tdsid, tds.heading, tds.statement, tds.displayheader, tdb.language '.
				'FROM taxstatus ts INNER JOIN taxadescrblock tdb ON ts.tid = tdb.tid '.
				'INNER JOIN taxadescrstmts tds ON tdb.tdbid = tds.tdbid '.
				'WHERE (ts.tidaccepted = '.$this->tid.') AND (ts.taxauthid = 1) '.
				'ORDER BY tdb.displaylevel,tds.sortsequence';
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_assoc()){
				$rsArr[] = $r;
			}
			$rs->free();

			//Get descriptions associated with accepted name only
			$usedCaptionArr = array();
			foreach($rsArr as $n => $rowArr){
				if($rowArr['tid'] == $this->tid){
					$retArr = $this->loadDescriptionArr($rowArr, $retArr);
					$usedCaptionArr[] = $rowArr['caption'];
				}
			}
			//Then add description linked to synonyms ONLY if one doesn't exist with same caption
			reset($rsArr);
			foreach($rsArr as $n => $rowArr){
				if($rowArr['tid'] != $this->tid && !in_array($rowArr['caption'], $usedCaptionArr)){
					$retArr = $this->loadDescriptionArr($rowArr, $retArr);
				}
			}

			ksort($retArr);
		}
		return $retArr;
	}

	private function loadDescriptionArr($rowArr,$retArr){
		$indexKey = 0;
		if(!in_array(strtolower($rowArr['language']), $this->langArr)){
			$indexKey = 1;
		}
		if(!isset($retArr[$indexKey]) || !array_key_exists($rowArr['tdbid'],$retArr[$indexKey])){
			$retArr[$indexKey][$rowArr['tdbid']]["caption"] = $rowArr['caption'];
			$retArr[$indexKey][$rowArr['tdbid']]["source"] = $rowArr['source'];
			$retArr[$indexKey][$rowArr['tdbid']]["url"] = $rowArr['sourceurl'];
		}
		$retArr[$indexKey][$rowArr['tdbid']]["desc"][$rowArr['tdsid']] = ($rowArr['displayheader'] && $rowArr['heading']?"<b>".$rowArr['heading']."</b>: ":"").$rowArr['statement'];
		return $retArr;
	}

	//Taxon Link functions
	public function getTaxaLinks(){
		if($this->taxaLinks) return $this->taxaLinks;
		if($this->tid){
			$parArr = array($this->tid);
			$rsPar = $this->conn->query('SELECT parenttid FROM taxaenumtree WHERE tid = '.$this->tid.' AND taxauthid = 1');
			while($rPar = $rsPar->fetch_object()){
				$parArr[] = $rPar->parenttid;
			}
			$rsPar->free();

			$sql = 'SELECT DISTINCT tlid, url, icon, title, notes, sortsequence '.
					'FROM taxalinks '.
					'WHERE (tid IN('.implode(',',$parArr).')) ';
			//echo $sql; exit;
			$result = $this->conn->query($sql);
			while($r = $result->fetch_object()){
				$this->taxaLinks[] = array('title' => $r->title, 'url' => $r->url, 'icon' => $r->icon, 'notes' => $r->notes, 'sortseq' => $r->sortsequence);
			}
			$result->free();
			usort($this->taxaLinks, function($a, $b) {
				if($a['sortseq'] == $b['sortseq']){
					return (strtolower($a['title']) < strtolower($b['title'])) ? -1 : 1;
				}
				else{
					return $a['sortseq'] - $b['sortseq'];
				}
			});
		}
		return $this->taxaLinks;
	}

	//Set children data for taxon higher than species level
	private function setSppData($page, $taxaLimit, $pid, $clid){
		if(!is_numeric($page)) $page = 0;
		if(!is_numeric($taxaLimit)) $taxaLimit = 50;
		if($this->tid){
			$this->sppArray = Array();
			$start = ($page*$taxaLimit);
			$sql = '';
			if($clid && is_numeric($clid)){
				$sql = 'SELECT DISTINCT t.tid, t.sciname, t.securitystatus '.
					'FROM taxa t INNER JOIN taxaenumtree te ON t.tid = te.tid '.
					'INNER JOIN fmchklsttaxalink ctl ON ctl.TID = t.tid '.
					'WHERE (ctl.clid IN('.$this->getChildrenClid($clid).')) AND t.rankid = 220 AND (te.taxauthid = 1) AND (te.parenttid = '.$this->tid.') ';
			}
			elseif($pid && is_numeric($pid)){
				$sql = 'SELECT DISTINCT t.tid, t.sciname, t.securitystatus '.
					'FROM taxa t INNER JOIN taxaenumtree te ON t.tid = te.tid '.
					'INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted '.
					'INNER JOIN fmchklsttaxalink ctl ON ts.Tid = ctl.TID '.
					'INNER JOIN fmchklstprojlink cpl ON ctl.clid = cpl.clid '.
					'WHERE (ts.taxauthid = 1) AND (te.taxauthid = 1) AND (cpl.pid = '.$pid.') '.
					'AND (te.parenttid = '.$this->tid.') AND (t.rankid = 220) ';
			}
			else{
				$sql = 'SELECT DISTINCT t.sciname, t.tid, t.securitystatus '.
					'FROM taxa t INNER JOIN taxaenumtree te ON t.tid = te.tid '.
					'INNER JOIN taxstatus ts ON t.Tid = ts.tidaccepted '.
					'WHERE (te.taxauthid = 1) AND (ts.taxauthid = 1) AND (t.rankid = 220) AND (te.parenttid = '.$this->tid.') ';
			}
			$sql .= 'ORDER BY t.sciname LIMIT '.$start.','.($taxaLimit+1);
			//echo $sql; exit;

			$tids = Array();
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$sn = ucfirst(strtolower($row->sciname));
				$this->sppArray[$sn]["tid"] = $row->tid;
				$this->sppArray[$sn]["security"] = $row->securitystatus;
				$tids[] = $row->tid;
			}
			$result->free();

			//If no tids exist because there are no species in default project, grab all species from that taxon
			if(!$tids){
				$sql = 'SELECT DISTINCT t.sciname, t.tid, t.securitystatus '.
					'FROM taxa t INNER JOIN taxstatus ts ON t.Tid = ts.tidaccepted '.
					'INNER JOIN taxaenumtree te ON ts.tid = te.tid '.
					'WHERE (te.taxauthid = 1) AND (ts.taxauthid = 1) AND (t.rankid = 220) AND (te.parenttid = '.$this->tid.') '.
					'ORDER BY t.sciname LIMIT '.$start.','.($taxaLimit+1);
				//echo $sql;

				$result = $this->conn->query($sql);
				while($row = $result->fetch_object()){
					$sn = ucfirst(strtolower($row->sciname));
					$this->sppArray[$sn]["tid"] = $row->tid;
					$this->sppArray[$sn]["security"] = $row->securitystatus;
					$tids[] = $row->tid;
				}
				$result->free();
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
				$result = $this->conn->query($sql);
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
				$result->free();
			}

			//Get Maps, if rank is genus level or higher
			if($this->rankId > 140){
				foreach($this->sppArray as $sn => $snArr){
					$tid = $snArr['tid'];
					if($mapArr = $this->getMapArr($tid)){
						$this->sppArray[$sn]["map"] = array_shift($mapArr);
					}
					else{
						$this->sppArray[$sn]["map"] = $this->getGoogleStaticMap($tid);
					}
				}
			}
		}
	}

	public function getSppArray($page, $taxaLimit, $pid = 0, $clid = 0){
		if(!$this->sppArray) $this->setSppData($page, $taxaLimit, $pid, $clid);
		return $this->sppArray;
	}

	//Misc functions
	private function getChildrenClid($clid){
		$clidArr = array($clid);
		$sqlBase = 'SELECT clidchild FROM fmchklstchildren WHERE clid IN(';
		$sql = $sqlBase.$clid.')';
		do{
			$childStr = '';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$clidArr[] = $r->clidchild;
				$childStr .= ','.$r->clidchild;
			}
			$sql = $sqlBase.substr($childStr,1).')';
		}while($childStr);
		return implode(',',$clidArr);
	}

	public function taxonSearch($searchStr){
		$retArr = array();
		$sql = 'SELECT t.tid, ts.family, t.sciname, t.author, t.rankid, ts.parenttid '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE (ts.taxauthid = ?) ';
		if(is_numeric($searchStr)){
			$sql .= 'AND (t.TID = ?) ';
		}
		else{
			$sql .= 'AND (t.SciName = ?) ';
		}
		$stmt = $this->conn->prepare($sql);
		if(is_numeric($searchStr)){
			$stmt->bind_param('is', $this->taxAuthId, $searchStr);
		}
		else{
			$stmt->bind_param('is', $this->taxAuthId, $searchStr);
		}
		$stmt->execute();
		$stmt->bind_result($tid, $family, $sciname, $author, $rankid, $parentTid);

		while($stmt->fetch()){
			$retArr[$tid]['sciname'] = $sciname;
			$retArr[$tid]['family'] = $family;
			$retArr[$tid]['author'] = $author;
			$retArr[$tid]['rankid'] = $rankid;
			$retArr[$tid]['parenttid'] = $parentTid;
		}
		$stmt->close();

		if(count($retArr) > 1){
			//Get parents so that user can determine which taxon they are looking for
			$sql2 = 'SELECT e.tid, t.tid AS parenttid, t.sciname, t.rankid, ts.parenttid AS directparenttid '.
				'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.parenttid '.
				'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
				'WHERE (e.taxauthid = '.$this->taxAuthId.') AND (ts.taxauthid = '.$this->taxAuthId.') AND (e.tid IN('.implode(array_keys($retArr),',').'))';
			$rs2 = $this->conn->query($sql2);
			while($r2 = $rs2->fetch_object()){
				$retArr[$tid]['parent'][$parenttid] = array('sciname' => $r2->sciname, 'rankid' => $r2->rankid, 'directparenttid' => $r2->directparenttid);
			}
			$rs2->free();
		}
		return $retArr;
	}

	public function getCloseTaxaMatches($testValue){
		$retArr = array();
		$sql = 'SELECT tid, sciname FROM taxa WHERE soundex(sciname) = soundex(?)';
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param('s', $testValue);
		$stmt->execute();
		$stmt->bind_result($tid, $sciname);
		while($stmt->fetch()){
			if($testValue != $sciname) $retArr[$tid] = $sciname;
		}
		$stmt->close();
		return $retArr;
	}

	//Setters and getters
	public function getTid(){
		return $this->tid;
	}

	public function getSciName(){
		return $this->sciName;
	}

	public function getAuthor(){
		return $this->author;
	}

	public function getSubmittedValue($k=0){
		return $this->submittedArr[$k];
	}

	public function setTaxAuthId($id){
		if(is_numeric($id)){
			$this->taxAuthId = $id;
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

	public function getSynonymArr(){
		return $this->synonymArr;
	}

	public function isAccepted(){
		return ($this->tid == $this->submittedArr['tid']);
	}

	public function getDisplayLocality(){
		return $this->displayLocality;
	}

	public function getClName($clid){
		$clName = '';
		if(is_numeric($clid)){
			$sql = 'SELECT name FROM fmchecklists WHERE (clid = '.$clid.')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$clName = $r->name;
			}
			$rs->free();
		}
		return $clName;
	}

	public function getParentChecklist($clid){
		$retArr = array();
		if(is_numeric($clid)){
			//Direct parent checklist
			$sql = 'SELECT c.clid, c.name '.
				'FROM fmchecklists c INNER JOIN fmchklstchildren cp ON c.clid = cp.clid '.
				'WHERE (cp.clidchild = '.$clid.')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retArr[$r->clid] = $r->name;
			}
			$rs->free();
			if(!$retArr){
				//Most Inclusive Reference Checklist
				$sql = 'SELECT c.parentclid, cp.name '.
					'FROM fmchecklists c INNER JOIN fmchecklists cp ON cp.clid = c.parentclid '.
					'WHERE (c.CLID = '.$clid.')';
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$retArr[$r->parentclid] = $r->name;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function getProjName($pid){
		$projName = '';
		if(is_numeric($pid)){
			$sql = "SELECT projname FROM fmprojects WHERE (pid = ".$pid.')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$projName = $r->projname;
			}
			$rs->free();
		}
		return $projName;
	}

	public function setLanguage($lang){
		$lang = strtolower($lang);
		if($lang == 'en' || $lang == 'english') $this->langArr = array('en','english');
		elseif($lang == 'es' || $lang == 'spanish') $this->langArr = array('es','spanish','espanol');
		elseif($lang == 'fr' || $lang == 'french') $this->langArr =  array('fr','french');
	}
}
?>