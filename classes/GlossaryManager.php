<?php
include_once($serverRoot.'/config/dbconnection.php');

class GlossaryManager{

	private $conn;
	private $glossId = 0;
	private $sourceGdImg;
	
	private $imageRootPath = '';
	private $imageRootUrl = '';
	
	private $sourcePath = '';
	private $targetPath = '';
	private $urlBase = '';
	private $imgName = '';
	private $imgExt = '';
	
	private $sourceWidth = 0;
	private $sourceHeight = 0;

	private $tnPixWidth = 200;
	private $webPixWidth = 1600;
	private $lgPixWidth = 3168;
	private $webFileSizeLimit = 300000;
	private $jpgCompression= 80;

	private $mapLargeImg = false;
	
	private $targetUrl;
	private $fileName;
	
 	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("write");
		$this->imageRootPath = $GLOBALS["imageRootPath"];
		if(substr($this->imageRootPath,-1) != "/") $this->imageRootPath .= "/";  
		$this->imageRootUrl = $GLOBALS["imageRootUrl"];
		if(substr($this->imageRootUrl,-1) != "/") $this->imageRootUrl .= "/";
		if(array_key_exists('imgTnWidth',$GLOBALS)){
			$this->tnPixWidth = $GLOBALS['imgTnWidth'];
		}
		if(array_key_exists('imgWebWidth',$GLOBALS)){
			$this->webPixWidth = $GLOBALS['imgWebWidth'];
		}
		if(array_key_exists('imgFileSizeLimit',$GLOBALS)){
			$this->webFileSizeLimit = $GLOBALS['imgFileSizeLimit'];
		}
 	}
 	
 	public function __destruct(){
		if($this->conn) $this->conn->close();
	}
	
	public function getTermList($keyword,$language,$tid){
		$retArr = array();
		$sql = 'SELECT g.glossid, g.term '.
			'FROM (glossary AS g LEFT JOIN glossarytermlink AS tl ON g.glossid = tl.glossid) '.
			'LEFT JOIN glossarytaxalink AS t ON tl.glossgrpid = t.glossgrpid ';
		if($keyword || $language){
			$sql .= 'WHERE ';
			if($keyword){
				$sql .= '(g.term LIKE "%'.$keyword.'%" OR g.definition LIKE "%'.$keyword.'%") ';
			}
			if(($keyword) && ($language || $tid)){
				$sql .= 'AND ';
			} 
			if($language){
				$sql .= 'g.`language` = "'.$language.'" ';
			}
			if($language && $tid){
				$sql .= 'AND ';
			}
			if($tid){
				$sql .= 't.tid = '.$tid.' ';
			}
		}
		$sql .= 'ORDER BY g.term ';
		//echo '<div>'.$sql.'</div>';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->glossid]['glossid'] = $r->glossid;
				$retArr[$r->glossid]['term'] = $r->term;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function createTerm($pArr,$setLinks){
		global $SYMB_UID;
		$statusStr = '';
		if(!array_key_exists('source',$pArr)){
			$pArr['source'] = '';
		}
		if(!array_key_exists('notes',$pArr)){
			$pArr['notes'] = '';
		}
		if(!array_key_exists('resourceurl',$pArr)){
			$pArr['resourceurl'] = '';
		}
		$sql = 'INSERT INTO glossary(term,definition,`language`,source,author,translator,notes,resourceurl,uid) '.
			'VALUES("'.$this->cleanInStr($pArr['term']).'","'.$this->cleanInStr($pArr['definition']).'","'.$this->cleanInStr($pArr['language']).'",'.
			'"'.$this->cleanInStr($pArr['source']).'","'.$this->cleanInStr($pArr['author']).'","'.$this->cleanInStr($pArr['translator']).'",'.
			'"'.$this->cleanInStr($pArr['notes']).'","'.$this->cleanInStr($pArr['resourceurl']).'",'.$SYMB_UID.') ';
		//echo $sql;
		if($this->conn->query($sql)){
			$this->glossId = $this->conn->insert_id;
			if($setLinks){
				$sql2 = 'INSERT INTO glossarytermlink(glossgrpid,glossid) '.
					'VALUES('.$this->glossId.','.$this->glossId.') ';
				if($this->conn->query($sql2)){
					$sql3 = 'INSERT INTO glossarytaxalink(glossgrpid,tid) '.
						'VALUES('.$this->glossId.','.$pArr['tid'].') ';
					if($this->conn->query($sql3)){
						$statusStr = '';
					}
				}
			}
		}
		else{
			$statusStr = 'ERROR: Creation of new term failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}
	
	
	
	public function getTermArr($glossId){
		$retArr = array();
		$sql = 'SELECT g.glossid, g.term, g.definition, g.`language`, g.source, g.notes, g.resourceurl, g.author, g.translator, '.
			't.glossgrpid '.
			'FROM glossary AS g LEFT JOIN glossarytermlink AS t ON g.glossid = t.glossid '.
			'WHERE (ISNULL(t.relationshipType) OR t.relationshipType <> "synonym") AND g.glossid = '.$glossId;
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr['glossid'] = $r->glossid;
				$retArr['term'] = $r->term;
				$retArr['definition'] = $r->definition;
				$retArr['language'] = $r->language;
				$retArr['author'] = $r->author;
				$retArr['translator'] = $r->translator;
				$retArr['source'] = $r->source;
				$retArr['notes'] = $r->notes;
				$retArr['resourceurl'] = $r->resourceurl;
				$retArr['glossgrpid'] = $r->glossgrpid;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function getSciName($tId){
		$sciName = '';
		$commonName = '';
		$name = '';
		$sql = 'SELECT tx.SciName, v.VernacularName '.
			'FROM taxa AS tx LEFT JOIN taxavernaculars AS v ON tx.TID = v.TID '.
			'WHERE tx.TID = '.$tId;
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$sciName = $r->SciName;
				$commonName = $r->VernacularName;
				if($commonName){
					$name = $commonName.' ('.$sciName.')';
				}
				else{
					$name = $sciName;
				}
			}
			$rs->close();
		}
		return $name;
	}
	
	public function getTaxonSources($tId){
		$retArr = array();
		$sql = 'SELECT contributorTerm, contributorImage, translator, additionalSources '.
			'FROM glossarysources '.
			'WHERE tid = '.$tId;
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr['contributorTerm'] = $r->contributorTerm;
				$retArr['contributorImage'] = $r->contributorImage;
				$retArr['translator'] = $r->translator;
				$retArr['additionalSources'] = $r->additionalSources;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function getTermTaxaArr($glossgrpId){
		$retArr = array();
		$sql = 'SELECT gt.tid, tx.SciName '.
			'FROM glossarytaxalink AS gt LEFT JOIN taxa AS tx ON gt.tid = tx.TID '.
			'WHERE gt.glossgrpid = '.$glossgrpId.' '.
			'ORDER BY tx.SciName ';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->tid]['tid'] = $r->tid;
				$retArr[$r->tid]['SciName'] = $r->SciName;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function getImgArr($glossgrpId){
		$retArr = array();
		$sql = 'SELECT g.glimgid, g.glossid, g.url, g.thumbnailurl, g.structures, g.notes, g.createdBy '.
			'FROM glossarytermlink AS t LEFT JOIN glossaryimages AS g ON t.glossid = g.glossid '.
			'WHERE t.glossgrpid = '.$glossgrpId.' AND g.glimgid IS NOT NULL';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->glimgid]['glimgid'] = $r->glimgid;
				$retArr[$r->glimgid]['glossid'] = $r->glossid;
				$retArr[$r->glimgid]['url'] = $r->url;
				$retArr[$r->glimgid]['thumbnailurl'] = $r->thumbnailurl;
				$retArr[$r->glimgid]['structures'] = $r->structures;
				$retArr[$r->glimgid]['notes'] = $r->notes;
				$retArr[$r->glimgid]['createdBy'] = $r->createdBy;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function getGrpArr($glossId,$glossgrpId,$language){
		$retArr = array();
		$sql = 'SELECT g.glossid, g.term, g.definition, g.`language`, g.source, g.notes, t.gltlinkid '.
			'FROM glossary AS g LEFT JOIN glossarytermlink AS t ON g.glossid = t.glossid '.
			'WHERE t.glossgrpid = '.$glossgrpId.' '.
			'ORDER BY g.`language` ';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				if($r->language == $language && $r->glossid != $glossId){
					$retArr['synonym'][$r->glossid]['glossid'] = $r->glossid;
					$retArr['synonym'][$r->glossid]['gltlinkid'] = $r->gltlinkid;
					$retArr['synonym'][$r->glossid]['term'] = $r->term;
					$retArr['synonym'][$r->glossid]['definition'] = $r->definition;
					$retArr['synonym'][$r->glossid]['language'] = $r->language;
					$retArr['synonym'][$r->glossid]['source'] = $r->source;
					$retArr['synonym'][$r->glossid]['notes'] = $r->notes;
				}
				if($r->language != $language){
					$retArr['translation'][$r->glossid]['glossid'] = $r->glossid;
					$retArr['translation'][$r->glossid]['gltlinkid'] = $r->gltlinkid;
					$retArr['translation'][$r->glossid]['term'] = $r->term;
					$retArr['translation'][$r->glossid]['definition'] = $r->definition;
					$retArr['translation'][$r->glossid]['language'] = $r->language;
					$retArr['translation'][$r->glossid]['source'] = $r->source;
					$retArr['translation'][$r->glossid]['notes'] = $r->notes;
				}
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function setGrpTermLink($glossId,$glossgrpId,$syn = ''){
		$sql2 = 'INSERT INTO glossarytermlink(glossgrpid,glossid,relationshipType) '.
			'VALUES('.$glossgrpId.','.$glossId.',"'.$syn.'") ';
		if($this->conn->query($sql2)){
			$statusStr = 'SUCCESS: information saved';
		}
	}
	
	public function updateGrpTermLink($oldglossgrpId,$gltlinkId,$glossgrpId){
		$sql = '';
		if($oldglossgrpId){
			$sql = 'UPDATE glossarytermlink SET glossgrpid='.$glossgrpId.' WHERE glossgrpid = '.$oldglossgrpId;
			if($this->conn->query($sql)){
				$statusStr = 'SUCCESS: information saved';
			}
		}
		if($gltlinkId){
			$sql = 'UPDATE glossarytermlink SET glossgrpid='.$glossgrpId.' WHERE gltlinkid = '.$gltlinkId;
			if($this->conn->query($sql)){
				$statusStr = 'SUCCESS: information saved';
			}
		}
	}
	
	public function setGrpTaxaLink($tId,$glossgrpId){
		$sql3 = 'INSERT INTO glossarytaxalink(glossgrpid,tid) '.
			'VALUES('.$glossgrpId.','.$tId.') ';
		if($this->conn->query($sql3)){
			$statusStr = 'SUCCESS: information saved';
		}
	}
	
	public function updateGrpTaxaLink($tId,$glossgrpId){
		$sql = '';
		$sql = 'UPDATE glossarytaxalink SET tid='.$tId.' WHERE glossgrpid = '.$glossgrpId;
		if($this->conn->query($sql)){
			$statusStr = 'SUCCESS: information saved';
		}
	}
	
	public function deleteGrpTaxaLink($glossgrpId,$tidStr){
		$sql = '';
		$sql = 'DELETE FROM glossarytaxalink WHERE glossgrpid = '.$glossgrpId.' AND tid IN('.$tidStr.') ';
		if($this->conn->query($sql)){
			$statusStr = 'SUCCESS: information saved';
		}
	}
	
	public function saveEditTerm($pArr){
		$sql = '';
		$statusStr = '';
		foreach($pArr as $k => $v){
			if($k != 'formsubmit' && $k != 'glossid' && $k != 'taxagroup' && $k != 'tid' && $k != 'glossgrpid' && $k != 'origtid'){
				$sql .= ','.$k.'='.($v?'"'.$this->cleanInStr($v).'"':'NULL');
			}
		}
		$sql = 'UPDATE glossary SET '.substr($sql,1).' WHERE (glossid = '.$pArr['glossid'].')';
		//echo $sql;
		if($this->conn->query($sql)){
			$statusStr = 'SUCCESS: information saved';
		}
		else{
			$statusStr = 'ERROR: Editing of term failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}
	
	public function editTerm($pArr){
		$statusStr = '';
		$glossId = $pArr['glossid'];
		$glossgrpId = $pArr['glossgrpid'];
		if(!$glossgrpId){
			$this->setGrpTermLink($glossId,$glossId);
		}
		if(is_numeric($glossId)){
			$statusStr = $this->saveEditTerm($pArr);
		}
		return $statusStr;
	}
	
	public function findCommonTidString($glossgrpId1,$glossgrpId2){
		$tidArr1 = array();
		$tidArr2 = array();
		$tid1Str = '';
		$tid2Str = '';
		$sql = '';
		$sql = 'SELECT tid FROM glossarytaxalink WHERE glossgrpid = '.$glossgrpId1;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$tidArr1[] = $r->tid;
			}
			$tid1Str = implode(',',$tidArr1);
			$sql = 'SELECT tid FROM glossarytaxalink WHERE glossgrpid = '.$glossgrpId2.' AND tid IN('.$tid1Str.') ';
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$tidArr2[] = $r->tid;
				}
				$tid2Str = implode(',',$tidArr2);
			}
		}
		$rs->close();
		return $tid2Str;
	}
	
	public function addRelation($pArr){
		$statusStr = '';
		$glossId = $pArr['relglossid'];
		$glossgrpId = $pArr['glossgrpid'];
		$relglossgrpId = $pArr['relglossgrpid'];
		unset($pArr['relglossgrpid']);
		if(!$glossId){
			$this->createTerm($pArr,0);
			$glossId = $this->getTermId();
			$statusStr = $this->setGrpTermLink($glossId,$glossgrpId);
		}
		else{
			$this->saveEditTerm($pArr);
			$commonTids = $this->findCommonTidString($relglossgrpId,$glossgrpId);
			$this->deleteGrpTaxaLink($relglossgrpId,$commonTids);
			$statusStr = $this->updateGrpTermLink($relglossgrpId,0,$glossgrpId);
		}
		return $statusStr;
	}
	
	public function addSynRelation($pArr){
		$statusStr = '';
		$glossId = $pArr['glossid'];
		$sglossId = $pArr['sglossid'];
		$glossgrpId = $pArr['glossgrpid'];
		$relglossgrpId = $pArr['relglossgrpid'];
		unset($pArr['relglossgrpid']);
		unset($pArr['sglossid']);
		if(!$glossId){
			$this->createTerm($pArr,0);
			$newglossId = $this->getTermId();
			$tidArr = $this->getTermTaxaArr($glossgrpId);
			$this->setGrpTermLink($newglossId,$newglossId);
			foreach($tidArr as $taxId => $tArr){
				$this->setGrpTaxaLink($tArr['tid'],$newglossId);
			}
			$this->setGrpTermLink($sglossId,$newglossId,'synonym');
			$statusStr = $this->setGrpTermLink($newglossId,$glossgrpId,'synonym');
		}
		else{
			$this->saveEditTerm($pArr);
			$this->setGrpTermLink($sglossId,$relglossgrpId,'synonym');
			$statusStr = $this->setGrpTermLink($glossId,$glossgrpId,'synonym');
		}
		return $statusStr;
	}
	
	public function removeRelation($pArr){
		$gltlinkId = $pArr['gltlinkid'];
		$glossgrpId = $pArr['glossgrpid'];
		$tidArr = $this->getTermTaxaArr($glossgrpId);
		$relglossId = $pArr['relglossid'];
		$this->updateGrpTermLink(0,$gltlinkId,$relglossId);
		foreach($tidArr as $taxId => $tArr){
			$this->setGrpTaxaLink($tArr['tid'],$relglossId);
		}
	}
	
	public function removeSynRelation($pArr){
		$grpCnt = 0;
		$gltlinkId = $pArr['gltlinkid'];
		$glossId = $pArr['glossid'];
		$glossgrpId = $pArr['glossgrpid'];
		$relglossId = $pArr['relglossid'];
		$grpCnt = $this->checkGrpCnt($relglossId);
		$this->deleteSynonym($relglossId,$glossgrpId);
		$this->deleteSynonym($glossId,$relglossId);
		if(!$grpCnt){
			$tidArr = $this->getTermTaxaArr($glossgrpId);
			$this->setGrpTermLink($relglossId,$relglossId);
			foreach($tidArr as $taxId => $tArr){
				$this->setGrpTaxaLink($tArr['tid'],$relglossId);
			}
		}
	}
	
	public function deleteSynonym($glossId,$glossgrpId){
		$sql3 = 'DELETE FROM glossarytermlink '.
			'WHERE (glossgrpid = '.$glossgrpId.') AND (glossid = '.$glossId.') ';
		//echo $sql;
		if($this->conn->query($sql3)){
			$statusStr = '';
		}
	}
	
	public function editImageData($pArr){
		$statusStr = '';
		$glimgId = $pArr['glimgid'];
		unset($pArr['oldurl']);
		if(is_numeric($glimgId)){
			$sql = '';
			foreach($pArr as $k => $v){
				if($k != 'formsubmit' && $k != 'glossid' && $k != 'glimgid' && $k != 'glossgrpid'){
					$sql .= ','.$k.'='.($v?'"'.$this->cleanInStr($v).'"':'NULL');
				}
			}
			$sql = 'UPDATE glossaryimages SET '.substr($sql,1).' WHERE (glimgid = '.$glimgId.')';
			//echo $sql;
			if($this->conn->query($sql)){
				$statusStr = 'SUCCESS: information saved';
			}
			else{
				$statusStr = 'ERROR: Editing of image data failed: '.$this->conn->error.'<br/>';
				$statusStr .= 'SQL: '.$sql;
			}
		}
		return $statusStr;
	}
	
	public function checkGrpCnt($glossgrpId){
		$grpCnt = 0;
		$sql1 = 'SELECT glossgrpid '.
			'FROM glossarytermlink '.
			'WHERE glossgrpid = '.$glossgrpId.' ';
		if($rs = $this->conn->query($sql1)){
			while($r = $rs->fetch_object()){
				$grpCnt = $rs->num_rows;
			}
		}
		return $grpCnt;
	}
	
	public function deleteTermGrp($glossgrpId){
		$sql3 = 'DELETE FROM glossarytermlink '.
			'WHERE (glossgrpid = '.$glossgrpId.')';
		//echo $sql;
		if($this->conn->query($sql3)){
			$statusStr = '';
		}
	}
	
	public function deleteTaxaGrp($glossgrpId){
		$sql2 = 'DELETE FROM glossarytaxalink '.
			'WHERE (glossgrpid = '.$glossgrpId.')';
		//echo $sql;
		if($this->conn->query($sql2)){
			$statusStr = '';
		}
	}
	
	public function deleteTerm($pArr){
		$statusStr = '';
		$grpCnt = 0;
		$glossId = $pArr['glossid'];
		$glossgrpId = $pArr['glossgrpid'];
		$language = $pArr['language'];
		$synArr = $this->getTermSynDelArr($glossId,$glossgrpId,$language);
		foreach($synArr as $k){
			$this->deleteSynonym($k,$glossgrpId);
		}
		$grpArr = $this->getTermGrpArr($glossId);
		foreach($grpArr as $k){
			$grpCnt = $this->checkGrpCnt($k);
			if($grpCnt < 2){
				$this->deleteTaxaGrp($k);
				$this->deleteTermGrp($k);
			}
			else{
				$this->deleteSynonym($glossId,$k);
			}
		}
		$sql = 'DELETE FROM glossary '.
			'WHERE (glossid = '.$glossId.')';
		//echo $sql;
		if($this->conn->query($sql)){
			$statusStr = 'Term deleted.';
		}
		else{
			$statusStr = 'ERROR: Deletion of term failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}
	
	public function getTermGrpArr($glossId){
		$retArr = array();
		$sql = 'SELECT glossgrpid '.
			'FROM glossarytermlink AS gt '.
			'WHERE glossid = '.$glossId.' ';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[] = $r->glossgrpid;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function getTermSynDelArr($glossId,$glossgrpId,$language){
		$retArr = array();
		$sql = 'SELECT g.glossid '.
			'FROM glossarytermlink AS gt LEFT JOIN glossary AS g ON gt.glossid = g.glossid '.
			'WHERE gt.glossgrpid = '.$glossgrpId.' AND g.`language` = "'.$language.'" AND g.glossid <> '.$glossId.' ';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[] = $r->glossid;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function deleteImage($imgIdDel,$removeImg){
		$imgUrl = "";
		$imgTnUrl = "";
		$status = "Image deleted successfully";
		$sqlQuery = 'SELECT url, thumbnailurl FROM glossaryimages WHERE (glimgid = '.$imgIdDel.')';
		$result = $this->conn->query($sqlQuery);
		if($row = $result->fetch_object()){
			$imgUrl = $row->url;
			$imgTnUrl = $row->thumbnailurl;
		}
		$result->close();
				
		$sql = "DELETE FROM glossaryimages WHERE (glimgid = ".$imgIdDel.')';
		//echo $sql;
		if($this->conn->query($sql)){
			if($removeImg){
				$imgUrl2 = '';
				$domain = "http://";
				if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $domain = "https://";
				$domain .= $_SERVER["SERVER_NAME"];
				if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $domain .= ':'.$_SERVER["SERVER_PORT"];
				if(stripos($imgUrl,$domain) === 0){
					$imgUrl2 = $imgUrl;
					$imgUrl = substr($imgUrl,strlen($domain));
				}
				elseif(stripos($imgUrl,$this->imageRootUrl) === 0){
					$imgUrl2 = $domain.$imgUrl;
				}
				
				//Remove images only if there are no other references to the image
				$sql = "SELECT glimgid FROM glossaryimages WHERE (url = '".$imgUrl."') ";
				if($imgUrl2) $sql .= 'OR (url = "'.$imgUrl2.'")';
				$rs = $this->conn->query($sql);
				if(!$rs->num_rows){
					//Delete image from server
					$imgDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgUrl);
					if(substr($imgDelPath,0,4) != 'http'){
						if(!unlink($imgDelPath)){
							$this->errArr[] = 'WARNING: Deleted records from database successfully but FAILED to delete image from server (path: '.$imgDelPath.')';
							//$status .= '<br/>Return to <a href="../taxa/admin/tpeditor.php?tid='.$tid.'&tabindex=1">Taxon Editor</a>';
						}
					}
					
					//Delete thumbnail image
					if($imgTnUrl){
						if(stripos($imgTnUrl,$domain) === 0){
							$imgTnUrl = substr($imgTnUrl,strlen($domain));
						}				
						$imgTnDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgTnUrl);
						if(file_exists($imgTnDelPath) && substr($imgTnDelPath,0,4) != 'http') unlink($imgTnDelPath);
					}
				}
			}
		}
		else{
			$status = "deleteImage: ".$this->conn->error."\nSQL: ".$sql;
		}
		return $status;
	}

	public function addImage(){
		$status = '';
		//Set download paths and variables
		set_time_limit(120);
		ini_set("max_input_time",120);
		$this->setTargetPath();
		
		if($_REQUEST["imgurl"]){
			if(!$this->copyImageFromUrl($_REQUEST["imgurl"])) return;
		}
		else{
			if(!$this->loadImage()) return;
		}
		
		$status = $this->processImage();
		
		return $status;
	}
	
	public function processImage(){
		global $paramsArr;

		if(!$this->imgName){
			//trigger_error('Image file name null in processImage function',E_USER_ERROR);
			return false;
		}
		$imgPath = $this->targetPath.$this->imgName.$this->imgExt;

		//Create thumbnail
		$imgTnUrl = '';
		if($this->createNewImage('_tn',$this->tnPixWidth,70)){
			$imgTnUrl = $this->imgName.'_tn.jpg';
		}

		//Get image dimensions
		if(!$this->sourceWidth || !$this->sourceHeight){
			list($this->sourceWidth, $this->sourceHeight) = getimagesize($this->sourcePath);
		}
		//Get image file size
		$fileSize = $this->getSourceFileSize();

		//Create large image
		$imgLgUrl = "";
		if($this->mapLargeImg){
			if($this->sourceWidth > ($this->webPixWidth*1.2) || $fileSize > $this->webFileSizeLimit){
				//Source image is wide enough can serve as large image, or it's too large to serve as basic web image
				if(substr($this->sourcePath,0,7)=='http://' || substr($this->sourcePath,0,8)=='https://') {
					$imgLgUrl = $this->sourcePath;
				}
				else{
					if($this->sourceWidth < ($this->lgPixWidth*1.2)){
						//Image width is small enough to serve as large image 
						if(copy($this->sourcePath,$this->targetPath.$this->imgName.'_lg'.$this->imgExt)){
							$imgLgUrl = $this->imgName.'_lg'.$this->imgExt;
						}
					}
					else{
						if($this->createNewImage('_lg',$this->lgPixWidth)){
							$imgLgUrl = $this->imgName.'_lg.jpg';
						}
					}
				}
			}
		}

		//Create web url
		$imgWebUrl = '';
		if($this->sourceWidth < ($this->webPixWidth*1.2) && $fileSize < $this->webFileSizeLimit){
			//Image width and file size is small enough to serve as web image
			if(strtolower(substr($this->sourcePath,0,7)) == 'http://' || strtolower(substr($this->sourcePath,0,8)) == 'https://'){
				if(copy($this->sourcePath, $this->targetPath.$this->imgName.$this->imgExt)){
					$imgWebUrl = $this->imgName.$this->imgExt;
				}
			}
			else{
				$imgWebUrl = $this->imgName.$this->imgExt;
			}
		}
		else{
			//Image width or file size is too large
			//$newWidth = ($this->sourceWidth<($this->webPixWidth*1.2)?$this->sourceWidth:$this->webPixWidth);
			$this->createNewImage('',$this->sourceWidth);
			$imgWebUrl = $this->imgName.'.jpg';
		}

		$status = true;
		if($imgWebUrl){
			$status = $this->databaseImage($imgWebUrl,$imgTnUrl,$imgLgUrl);
		}
		return $status;
	}
	
	public function copyImageFromUrl($sourceUri){
		//Returns full path
		if(!$sourceUri){
			$this->errArr[] = 'ERROR: Image source uri NULL in copyImageFromUrl method';
			//trigger_error('Image source uri NULL in copyImageFromUrl method',E_USER_ERROR);
			return false;
		}
		if(!$this->uriExists($sourceUri)){
			$this->errArr[] = 'ERROR: Image source file ('.$sourceUri.') does not exist in copyImageFromUrl method';
			//trigger_error('Image source file ('.$sourceUri.') does not exist in copyImageFromUrl method',E_USER_ERROR);
			return false;
		}
		if(!$this->targetPath){
			$this->errArr[] = 'ERROR: Image target url NULL in copyImageFromUrl method';
			//trigger_error('Image target url NULL in copyImageFromUrl method',E_USER_ERROR);
			return false;
		}
		if(!file_exists($this->targetPath)){
			$this->errArr[] = 'ERROR: Image target file ('.$this->targetPath.') does not exist in copyImageFromUrl method';
			//trigger_error('Image target file ('.$this->targetPath.') does not exist in copyImageFromUrl method',E_USER_ERROR);
			return false;
		}
		//Clean and copy file
		$fileName = $this->cleanFileName($sourceUri);
		if(copy($sourceUri, $this->targetPath.$fileName.$this->imgExt)){
			$this->sourcePath = $this->targetPath.$fileName.$this->imgExt;
			$this->imgName = $fileName;
			//$this->testOrientation();
			return true;
		}
		$this->errArr[] = 'ERROR: Unable to copy image to target ('.$this->targetPath.$fileName.$this->imgExt.')';
		return false;
	}
	
	public function cleanFileName($fPath){
		$fName = $fPath;
		$imgInfo = null;
		if(strtolower(substr($fPath,0,7)) == 'http://' || strtolower(substr($fPath,0,8)) == 'https://'){
			//Image is URL 
			$imgInfo = getimagesize($fPath);
			list($this->sourceWidth, $this->sourceHeight) = $imgInfo;
		
			if($pos = strrpos($fName,'/')){
				$fName = substr($fName,$pos+1);
			}
		}
		//Parse extension
		if($p = strrpos($fName,".")){
			$this->imgExt = strtolower(substr($fName,$p));
			$fName = substr($fName,0,$p);
		}
		
		if(!$this->imgExt && $imgInfo){
			if($imgInfo[2] == IMAGETYPE_GIF){
				$this->imgExt = 'gif';
			}
			elseif($imgInfo[2] == IMAGETYPE_PNG){
				$this->imgExt = 'png';
			}
			elseif($imgInfo[2] == IMAGETYPE_JPEG){
				$this->imgExt = 'jpg';
			}
		}

		$fName = str_replace("%20","_",$fName);
		$fName = str_replace("%23","_",$fName);
		$fName = str_replace(" ","_",$fName);
		$fName = str_replace("__","_",$fName);
		$fName = str_replace(array(chr(231),chr(232),chr(233),chr(234),chr(260)),"a",$fName);
		$fName = str_replace(array(chr(230),chr(236),chr(237),chr(238)),"e",$fName);
		$fName = str_replace(array(chr(239),chr(240),chr(241),chr(261)),"i",$fName);
		$fName = str_replace(array(chr(247),chr(248),chr(249),chr(262)),"o",$fName);
		$fName = str_replace(array(chr(250),chr(251),chr(263)),"u",$fName);
		$fName = str_replace(array(chr(264),chr(265)),"n",$fName);
		$fName = preg_replace("/[^a-zA-Z0-9\-_]/", "", $fName);
		$fName = trim($fName,' _-');
		
		if(strlen($fName) > 30) {
			$fName = substr($fName,0,30);
		}
		//Test to see if target images exist (can happen batch loading images with similar names)
		if($this->targetPath){
			//Check and see if file already exists, if so, rename filename until it has a unique name
			$tempFileName = $fName;
			$cnt = 0;
			while(file_exists($this->targetPath.$tempFileName)){
				$tempFileName = $fName.'_'.$cnt;
				$cnt++;
			}
			if($cnt) $fName = $tempFileName;
		}
		
		//Returns file name without extension
		return $fName;
 	}
	
	private function loadImage(){
		$imgFile = basename($_FILES['imgfile']['name']);
		$fileName = $this->cleanFileName($imgFile);
		if(move_uploaded_file($_FILES['imgfile']['tmp_name'], $this->targetPath.$fileName.$this->imgExt)){
			$this->sourcePath = $this->targetPath.$fileName.$this->imgExt;
			$this->imgName = $fileName;
			//$this->testOrientation();
			return true;
		}
		return false;
	}

	private function databaseImage($imgWebUrl,$imgTnUrl,$imgLgUrl){
		global $SYMB_UID;
		if(!$imgWebUrl) return 'ERROR: web url is null ';
		$urlBase = $this->getUrlBase();
		if(strtolower(substr($imgWebUrl,0,7)) != 'http://' && strtolower(substr($imgWebUrl,0,8)) != 'https://'){ 
			$imgWebUrl = $urlBase.$imgWebUrl;
		}
		if($imgTnUrl && strtolower(substr($imgTnUrl,0,7)) != 'http://' && strtolower(substr($imgTnUrl,0,8)) != 'https://'){
			$imgTnUrl = $urlBase.$imgTnUrl;
		}
		$glossId = $_REQUEST['glossid'];
		$glossgrpId = $_REQUEST['glossgrpid'];
		$status = 'File added successfully!';
		$sql = 'INSERT INTO glossaryimages(glossid,url,thumbnailurl,structures,notes,createdBy,uid) '.
			'VALUES('.$glossId.',"'.$imgWebUrl.'","'.$imgTnUrl.'","'.$this->cleanInStr($_REQUEST["structures"]).'","'.$this->cleanInStr($_REQUEST["notes"]).'","'.$this->cleanInStr($_REQUEST["createdBy"]).'",'.$SYMB_UID.') ';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$status = "ERROR Loading Data: ".$this->conn->error."<br/>SQL: ".$sql;
		}
		return $status;
	}
	
	public function uriExists($url){
		$exists = false;
		$localUrl = '';
		if(substr($url,0,1) == '/'){
			if(isset($GLOBALS['imageDomain']) && $GLOBALS['imageDomain']){
				$url = $GLOBALS['imageDomain'].$url;
			}
			elseif($GLOBALS['imageRootUrl'] && strpos($url,$GLOBALS['imageRootUrl']) === 0){
				$localUrl = str_replace($GLOBALS['imageRootUrl'],$GLOBALS['imageRootPath'],$url);
			}
			else{
				$urlPrefix = "http://";
				if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $urlPrefix = "https://";
				$urlPrefix .= $_SERVER["SERVER_NAME"];
				if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $urlPrefix .= ':'.$_SERVER["SERVER_PORT"];
				$url = $urlPrefix.$url;
			}
		}
		
		//First simple check
		if(file_exists($url) || ($localUrl && file_exists($localUrl))){
			return true;
	    }

	    //Second check
	    if(!$exists){
		    // Version 4.x supported
		    $handle   = curl_init($url);
		    if (false === $handle){
				$exists = false;
		    }
		    curl_setopt($handle, CURLOPT_HEADER, false);
		    curl_setopt($handle, CURLOPT_FAILONERROR, true);  // this works
		    curl_setopt($handle, CURLOPT_HTTPHEADER, Array("User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15") ); // request as if Firefox   
		    curl_setopt($handle, CURLOPT_NOBODY, true);
		    curl_setopt($handle, CURLOPT_RETURNTRANSFER, false);
		    $exists = curl_exec($handle);
		    curl_close($handle);
	    }
	     
	    //One last check
	    if(!$exists){
	    	$exists = (@fclose(@fopen($url,"r")));
	    }
	    
	    //Test to see if file is an image 
	    if(function_exists('exif_imagetype')){
			if(!exif_imagetype($url)) $exists = false;
		}
		
	    return $exists;
	}
	
	public function createNewImage($subExt, $targetWidth, $qualityRating = 0){
		global $useImageMagick;
		$status = false;
		if($this->sourcePath && $this->uriExists($this->sourcePath)){
			if(!$qualityRating) $qualityRating = $this->jpgCompression;
			
	        if($useImageMagick) {
				// Use ImageMagick to resize images 
				$status = $this->createNewImageImagick($subExt,$targetWidth,$qualityRating);
			} 
			elseif(extension_loaded('gd') && function_exists('gd_info')) {
				// GD is installed and working 
				$status = $this->createNewImageGD($subExt,$targetWidth,$qualityRating);
			}
			else{
				// Neither ImageMagick nor GD are installed 
				$this->errArr[] = 'ERROR: No appropriate image handler for image conversions';
			}
		}
		return $status;
	}
	
	private function createNewImageImagick($subExt,$newWidth,$qualityRating = 0){
		$targetPath = $this->targetPath.$this->imgName.$subExt.$this->imgExt;
		$ct;
		if($newWidth < 300){
			$ct = system('convert '.$this->sourcePath.' -thumbnail '.$newWidth.'x'.($newWidth*1.5).' '.$targetPath, $retval);
		}
		else{
			$ct = system('convert '.$this->sourcePath.' -resize '.$newWidth.'x'.($newWidth*1.5).($qualityRating?' -quality '.$qualityRating:'').' '.$targetPath, $retval);
		}
		if(file_exists($targetPath)){
			return true;
		}
		return false;
	}

	private function createNewImageGD($subExt, $newWidth, $qualityRating = 0){
		$status = false;
		ini_set('memory_limit','512M');

		if(!$this->sourceWidth || !$this->sourceHeight){
			list($this->sourceWidth, $this->sourceHeight) = getimagesize($this->sourcePath);
		}
		if($this->sourceWidth){
			$newHeight = round($this->sourceHeight*($newWidth/$this->sourceWidth));
			if($newWidth > $this->sourceWidth){
				$newWidth = $this->sourceWidth;
				$newHeight = $this->sourceHeight;
			}
			if(!$this->sourceGdImg){
				if($this->imgExt == '.gif'){
			   		$this->sourceGdImg = imagecreatefromgif($this->sourcePath);
				}
				elseif($this->imgExt == '.png'){
			   		$this->sourceGdImg = imagecreatefrompng($this->sourcePath);
				}
				else{
					//JPG assumed
			   		$this->sourceGdImg = imagecreatefromjpeg($this->sourcePath);
				}
			}
			
			$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
			//imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth,$newHeight,$sourceWidth,$sourceHeight);
			imagecopyresized($tmpImg,$this->sourceGdImg,0,0,0,0,$newWidth,$newHeight,$this->sourceWidth,$this->sourceHeight);
	
			//Irrelavent of import image, output JPG 
			$targetPath = $this->targetPath.$this->imgName.$subExt.'.jpg';
			if($qualityRating){
				$status = imagejpeg($tmpImg, $targetPath, $qualityRating);
			}
			else{
				$status = imagejpeg($tmpImg, $targetPath);
			}
				
			if(!$status){
				$this->errArr[] = 'ERROR: failed to create images in target path ('.$targetPath.')';
			}
	
			imagedestroy($tmpImg);
		}
		else{
			$this->errArr[] = 'ERROR: unable to get source image width ('.$this->sourcePath.')';
		}
		return $status;
	}
	
	public function getUrlBase(){
		$urlBase = $this->urlBase;
		//If central images are on remote server and new ones stored locally, then we need to use full domain
	    //e.g. this portal is sister portal to central portal
	 	if($GLOBALS['imageDomain']){
			$urlPrefix = "http://";
			if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $urlPrefix = "https://";
			$urlPrefix .= $_SERVER["SERVER_NAME"];
			if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $urlPrefix .= ':'.$_SERVER["SERVER_PORT"];
			$urlBase = $urlPrefix.$urlBase;
    	}
		return $urlBase;
	}
	
	public function getSourceFileSize(){
		$fileSize = 0;
		if($this->sourcePath){
			if(strtolower(substr($this->sourcePath,0,7)) == 'http://' || strtolower(substr($this->sourcePath,0,8)) == 'https://'){
				$x = array_change_key_case(get_headers($this->sourcePath, 1),CASE_LOWER); 
				if ( strcasecmp($x[0], 'HTTP/1.1 200 OK') != 0 ) { 
					$fileSize = $x['content-length'][1]; 
				}
	 			else { 
	 				$fileSize = $x['content-length']; 
	 			}
	 		}
			else{
				$fileSize = filesize($this->sourcePath);
			}
		}
		return $fileSize;
	}

	private function setFileName($fName){
		$this->fileName = $fName;
		//echo $fName;
	}
 	
	private function setTargetPath(){
 		$folderName = date("Y-m");
		if(!file_exists($this->imageRootPath."glossimg")){
			mkdir($this->imageRootPath."glossimg", 0775);
		}
		if(!file_exists($this->imageRootPath."glossimg/".$folderName)){
			mkdir($this->imageRootPath."glossimg/".$folderName, 0775);
		}
		$path = $this->imageRootPath."glossimg/".$folderName."/";
		$url = $this->imageRootUrl."glossimg/".$folderName."/";
		
		$this->targetPath = $path;
		$this->urlBase = $url;
	}
	
	public function getLanguageArr(){
		$retArr = array();
		$sql = 'SELECT DISTINCT `language` '. 
			'FROM glossary '.
			'ORDER BY `language` ';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->language] = $r->language;
			}
		}
		return $retArr;
	}
	
	public function getTaxaGroupArr(){
		$retArr = array();
		$sciName = '';
		$commonName = '';
		$sql = 'SELECT DISTINCT t.TID, t.SciName, v.VernacularName '. 
			'FROM glossarytaxalink AS g LEFT JOIN taxa AS t ON g.tid = t.TID '.
			'LEFT JOIN taxavernaculars AS v ON t.TID = v.TID '.
			'ORDER BY IFNULL(v.VernacularName,t.SciName) ';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$sciName = $r->SciName;
				$commonName = $r->VernacularName;
				if($commonName){
					$retArr[$r->TID] = $commonName.' ('.$sciName.')';
				}
				else{
					$retArr[$r->TID] = $sciName;
				}
			}
		}
		return $retArr;
	}
	
	public function getTransExportArr($language,$taxon,$translations,$definitions){
		$retArr = array();
		$referencesArr = array();
		$contributorsArr = array();
		$imgcontributorsArr = array();
		$transStr = '"';
		$transStr .= implode('","',$translations);
		$transStr .= '"';
		$sciName = '';
		$commonName = '';
		$source = '';
		$translator = '';
		$author = '';
		$sql = 'SELECT t.SciName, v.VernacularName, gt.glossgrpid, g.source, g.translator, g.author, g.term, g.`language`, g.definition '.
			'FROM (((glossarytermlink AS gt LEFT JOIN glossarytaxalink AS gx ON gt.glossgrpid = gx.glossgrpid) '.
			'LEFT JOIN taxa AS t ON gx.tid = t.TID) '.
			'LEFT JOIN glossary AS g ON gt.glossid = g.glossid) '.
			'LEFT JOIN taxavernaculars AS v ON t.TID = v.TID '.
			'WHERE (ISNULL(gt.relationshipType) OR gt.relationshipType <> "synonym") AND '.
			'gx.tid = '.$taxon.' AND (g.`language` = "'.$language.'" OR g.`language` IN('.$transStr.')) ';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$sciName = $r->SciName;
				$commonName = $r->VernacularName;
				$source = $r->source;
				$translator = $r->translator;
				$author = $r->author;
				if($commonName){
					$retArr['glossSciName'] = $commonName.' ('.$sciName.')';
				}
				else{
					$retArr['glossSciName'] = $sciName;
				}
				if($source && !in_array($source,$referencesArr)){
					$referencesArr[] = $source;
				}
				if($translator && !in_array($translator,$contributorsArr)){
					$contributorsArr[] = $translator;
				}
				if($author && !in_array($author,$contributorsArr)){
					$contributorsArr[] = $author;
				}
				if($r->language == $language){
					$retArr[$r->term]['term'] = $r->term;
					$retArr[$r->term]['glossgrpid'] = $r->glossgrpid;
					if($definitions != 'nodef'){
						$retArr[$r->term]['definition'] = $r->definition;
					}
				}
				else{
					$retArr['glossTranslations'][$r->glossgrpid][$r->language]['term'] = $r->term;
					if($definitions == 'alldef'){
						$retArr['glossTranslations'][$r->glossgrpid][$r->language]['definition'] = $r->definition;
					}
				}
			}
			$rs->close();
		}
		$sql = 'SELECT contributorTerm, contributorImage, translator, additionalSources '.
			'FROM glossarysources '.
			'WHERE tid = '.$taxon.' ';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$source = $r->additionalSources;
				$translator = $r->translator;
				$author = $r->contributorTerm;
				$imgSource = $r->contributorImage;
				if($source && !in_array($source,$referencesArr)){
					$referencesArr[] = $source;
				}
				if($translator && !in_array($translator,$contributorsArr)){
					$contributorsArr[] = $translator;
				}
				if($author && !in_array($author,$contributorsArr)){
					$contributorsArr[] = $author;
				}
				if($imgSource && !in_array($imgSource,$imgcontributorsArr)){
					$imgcontributorsArr[] = $imgSource;
				}
			}
			$rs->close();
			$retArr['references'] = $referencesArr;
			$retArr['contributors'] = $contributorsArr;
			$retArr['imgcontributors'] = $imgcontributorsArr;
		}
		return $retArr;
	}
	
	public function getSingleExportArr($language,$taxon,$images){
		$retArr = array();
		$referencesArr = array();
		$contributorsArr = array();
		$imgcontributorsArr = array();
		$sciName = '';
		$commonName = '';
		$source = '';
		$translator = '';
		$author = '';
		$sql = 'SELECT t.SciName, v.VernacularName, g.term, g.definition, g.source, g.translator, g.author, gt.glossgrpid '.
			'FROM (((glossarytermlink AS gt LEFT JOIN glossarytaxalink AS gx ON gt.glossgrpid = gx.glossgrpid) '.
			'LEFT JOIN taxa AS t ON gx.tid = t.TID) '.
			'LEFT JOIN glossary AS g ON gt.glossid = g.glossid) '.
			'LEFT JOIN taxavernaculars AS v ON t.TID = v.TID '.
			'WHERE (ISNULL(gt.relationshipType) OR gt.relationshipType <> "synonym") AND '.
			'gx.tid = '.$taxon.' AND g.`language` = "'.$language.'" ';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$sciName = $r->SciName;
				$commonName = $r->VernacularName;
				$term = $r->term;
				$glossgrpId = $r->glossgrpid;
				$source = $r->source;
				$translator = $r->translator;
				$author = $r->author;
				if($commonName){
					$retArr['glossSciName'] = $commonName.' ('.$sciName.')';
				}
				else{
					$retArr['glossSciName'] = $sciName;
				}
				if($source && !in_array($source,$referencesArr)){
					$referencesArr[] = $source;
				}
				if($translator && !in_array($translator,$contributorsArr)){
					$contributorsArr[] = $translator;
				}
				if($author && !in_array($author,$contributorsArr)){
					$contributorsArr[] = $author;
				}
				$retArr[$term]['term'] = $r->term;
				$retArr[$term]['definition'] = $r->definition;
				if($images){
					$sql2 = 'SELECT gi.glimgid, gi.url, gi.createdBy, gi.structures, gi.notes '.
						'FROM glossarytermlink AS gt LEFT JOIN glossary AS g ON gt.glossid = g.glossid '.
						'LEFT JOIN glossaryimages AS gi ON g.glossid = gi.glossid '.
						'WHERE gt.glossgrpid = '.$glossgrpId.' ';
					//echo $sql2;
					if($rs2 = $this->conn->query($sql2)){
						while($r2 = $rs2->fetch_object()){
							if($r2->url != ''){
								$retArr[$term]['images'][$r2->glimgid]['url'] = $r2->url;
								$retArr[$term]['images'][$r2->glimgid]['createdBy'] = $r2->createdBy;
								$retArr[$term]['images'][$r2->glimgid]['structures'] = $r2->structures;
								$retArr[$term]['images'][$r2->glimgid]['notes'] = $r2->notes;
							}
						}
						$rs2->close();
					}
				}
			}
			$rs->close();
		}
		$sql = 'SELECT contributorTerm, contributorImage, translator, additionalSources '.
			'FROM glossarysources '.
			'WHERE tid = '.$taxon.' ';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$source = $r->additionalSources;
				$translator = $r->translator;
				$author = $r->contributorTerm;
				$imgSource = $r->contributorImage;
				if($source && !in_array($source,$referencesArr)){
					$referencesArr[] = $source;
				}
				if($translator && !in_array($translator,$contributorsArr)){
					$contributorsArr[] = $translator;
				}
				if($author && !in_array($author,$contributorsArr)){
					$contributorsArr[] = $author;
				}
				if($imgSource && !in_array($imgSource,$imgcontributorsArr)){
					$imgcontributorsArr[] = $imgSource;
				}
			}
			$rs->close();
			$retArr['references'] = $referencesArr;
			$retArr['contributors'] = $contributorsArr;
			$retArr['imgcontributors'] = $imgcontributorsArr;
		}
		return $retArr;
	}
	
	public function editSources($pArr){
		$sql = '';
		$statusStr = '';
		$tId = $pArr['tid'];
		$new = $pArr['newsources'];
		if($new){
			$sql = 'INSERT INTO glossarysources(tid,contributorTerm,contributorImage,translator,additionalSources) '.
				'VALUES('.$tId.',"'.$this->cleanInStr($_REQUEST["contributorTerm"]).'","'.$this->cleanInStr($_REQUEST["contributorImage"]).'",'.
				'"'.$this->cleanInStr($_REQUEST["translator"]).'","'.$this->cleanInStr($_REQUEST["additionalSources"]).'") ';
		}
		elseif($pArr['contributorTerm'] || $pArr['contributorImage'] || $pArr['translator'] || $pArr['additionalSources']){
			foreach($pArr as $k => $v){
				if($k != 'formsubmit' && $k != 'tid' && $k != 'searchkeyword' && $k != 'searchlanguage' && $k != 'searchtaxa' && $k != 'newsources'){
					$sql .= ','.$k.'='.($v?'"'.$this->cleanInStr($v).'"':'NULL');
				}
			}
			$sql = 'UPDATE glossarysources SET '.substr($sql,1).' WHERE (tid = '.$tId.')';
		}
		else{
			$sql = 'DELETE FROM glossarysources WHERE (tid = '.$tId.')';
		}
		//echo $sql;
		if($this->conn->query($sql)){
			$statusStr = 'SUCCESS: information saved';
		}
		else{
			$statusStr = 'ERROR: Editing of sources failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}
	
	public function getTermId(){
		return $this->glossId;
	}
	
	public function getStats(){
		/*
		SELECT language, count(*)
		FROM glossary
		GROUP BY language;
		
		SELECT t.sciname, count(g.glossid) as cnt
		FROM glossary g INNER JOIN glossarytermlink gp ON g.glossid = gp.glossid
		INNER JOIN glossarytaxalink tl ON gp.glossgrpid = tl.glossgrpid
		INNER JOIN taxa t ON tl.tid = t.tid
		GROUP BY tl.tid;
		
		SELECT t.sciname, g.language, count(g.glossid) as cnt
		FROM glossary g INNER JOIN glossarytermlink gp ON g.glossid = gp.glossid
		INNER JOIN glossarytaxalink tl ON gp.glossgrpid = tl.glossgrpid
		INNER JOIN taxa t ON tl.tid = t.tid
		GROUP BY t.sciname, g.language;
		
		SELECT t.sciname, count(g.glossid) as cnt
		FROM glossary g INNER JOIN glossarytermlink gp ON g.glossid = gp.glossid
		INNER JOIN glossarytaxalink tl ON gp.glossgrpid = tl.glossgrpid
		INNER JOIN taxa t ON tl.tid = t.tid
		WHERE g.language = "English"
		GROUP BY t.sciname;
		
		SELECT count(g.glossid)
		FROM glossary g INNER JOIN glossaryimages i ON g.glossid = i.glossid;
		*/
	}
	
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>