<?php
include_once($serverRoot.'/config/dbconnection.php');

class ObservationSubmitManager {

	private $conn;
	private $collId;
	private $collMap = Array();

	private $imageRootPath = "";
	private $imageRootUrl = "";

	private $tnPixWidth = 200;
	private $webPixWidth = 1400;
	private $lgPixWidth = 3168;
	private $webFileSizeLimit = 250000;
	private $processUsingImageMagick = 0;

	private $sourceGdImg;
	//private $sourceImagickImg;
	private $exif;
	private $errArr = array();

	public function __construct($collId = 0){
		$this->collId = $collId;
		$this->conn = MySQLiConnectionFactory::getCon("write");
		$sql = 'SELECT collid, institutioncode, collectioncode, collectionname, colltype FROM omcollections ';
		if($collId && is_numeric($collId)){
			$sql .= 'WHERE (collid = '.$collId.')';
		}
		else{
			$sql .= 'WHERE (colltype = "General Observations")';
		}
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$this->collMap['collid'] = $r->collid;
			$this->collMap['institutioncode'] = $r->institutioncode;
			$this->collMap['collectioncode'] = $r->collectioncode;
			$this->collMap['collectionname'] = $this->cleanOutStr($r->collectionname);
			$this->collMap['colltype'] = $r->colltype;
			if(!$this->collId){
				$this->collId = $r->collid;
			}
		}
		$rs->close();
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
		if($this->sourceGdImg) imagedestroy($this->sourceGdImg);
		//if($this->sourceImagickImg) $this->sourceImagickImg->clear();
	}

	public function addObservation($occArr, $obsUid){
		$newOccId = '';
		if($occArr){
			//Load Image, abort if unsuccessful
			$nameArr = $this->loadImages();
			if($nameArr){
				//Setup Event Date fields
				$eventYear = 'NULL'; $eventMonth = 'NULL'; $eventDay = 'NULL'; $startDay = 'NULL';
				if($dateObj = strtotime($occArr['eventdate'])){
					$eventYear = date('Y',$dateObj);
					$eventMonth = date('m',$dateObj);
					$eventDay = date('d',$dateObj);
					$startDay = date('z',$dateObj)+1;
				}
				//Get tid for scinetific name
				$tid = 0;
				$localitySecurity = (array_key_exists('localitysecurity',$occArr)?1:0);
				$result = $this->conn->query('SELECT tid, securitystatus FROM taxa WHERE (sciname = "'.$occArr['sciname'].'")');
				if($row = $result->fetch_object()){
					$tid = $row->tid;
					if($row->securitystatus > 0) $localitySecurity = $row->securitystatus;
					if(!$localitySecurity){
						//Check to see if species is rare or sensitive within a state
						$sql = 'SELECT cl.tid '.
							'FROM fmchecklists c INNER JOIN fmchklsttaxalink cl ON c.clid = cl.clid '. 
							'WHERE c.type = "rarespp" AND c.locality = "'.$occArr['stateprovince'].'" AND cl.tid = '.$tid;
						$rs = $this->conn->query($sql);
						if($rs->num_rows){
							$localitySecurity = 1;
						}
					}
				}
				else{
					//Abort process
					$this->errArr[] = 'ERROR: scientific name failed, contact admin to add name to thesaurus';
					return;
				}
				
				
				//Get PK for that collection
				//$dbpk = 1;
				//$rs = $this->conn->query('SELECT MAX(dbpk+1) as maxpk FROM omoccurrences o WHERE o.collid = '.$collId);
				//if($rs && $row = $rs->fetch_object()){
				//	if($row->maxpk) $dbpk = $row->maxpk;
				//}

				$sql = 'INSERT INTO omoccurrences(collid, basisofrecord, family, sciname, scientificname, '.
					'scientificNameAuthorship, tidinterpreted, taxonRemarks, identifiedBy, dateIdentified, '.
					'identificationReferences, recordedBy, recordNumber, '.
					'associatedCollectors, eventDate, year, month, day, startDayOfYear, habitat, substrate, occurrenceRemarks, associatedTaxa, '.
					'verbatimattributes, reproductiveCondition, cultivationStatus, establishmentMeans, country, '.
					'stateProvince, county, locality, localitySecurity, decimalLatitude, decimalLongitude, '.
					'geodeticDatum, coordinateUncertaintyInMeters, georeferenceRemarks, minimumElevationInMeters, observeruid) '.
	
				'VALUES ('.$this->collId.',"Observation",'.($occArr['family']?'"'.$this->cleanInStr($occArr['family']).'"':'NULL').','.
				'"'.$this->cleanInStr($occArr['sciname']).'","'.
				$this->cleanInStr($occArr['sciname'].' '.$occArr['scientificnameauthorship']).'",'.
				($occArr['scientificnameauthorship']?'"'.$this->cleanInStr($occArr['scientificnameauthorship']).'"':'NULL').','.
				$tid.",".($occArr['taxonremarks']?'"'.$this->cleanInStr($occArr['taxonremarks']).'"':'NULL').','.
				($occArr['identifiedby']?'"'.$this->cleanInStr($occArr['identifiedby']).'"':'NULL').','.
				($occArr['dateidentified']?'"'.$this->cleanInStr($occArr['dateidentified']).'"':'NULL').','.
				($occArr['identificationreferences']?'"'.$this->cleanInStr($occArr['identificationreferences']).'"':'NULL').','.
				'"'.$this->cleanInStr($occArr['recordedby']).'",'.
				($occArr['recordnumber']?'"'.$this->cleanInStr($occArr['recordnumber']).'"':'NULL').','.
				($occArr['associatedcollectors']?'"'.$this->cleanInStr($occArr['associatedcollectors']).'"':'NULL').','.
				'"'.$occArr['eventdate'].'",'.$eventYear.','.$eventMonth.','.$eventDay.','.$startDay.','.
				($occArr['habitat']?'"'.$this->cleanInStr($occArr['habitat']).'"':'NULL').','.
				($occArr['substrate']?'"'.$this->cleanInStr($occArr['substrate']).'"':'NULL').','.
				($occArr['occurrenceremarks']?'"'.$this->cleanInStr($occArr['occurrenceremarks']).'"':'NULL').','.
				($occArr['associatedtaxa']?'"'.$this->cleanInStr($occArr['associatedtaxa']).'"':'NULL').','.
				($occArr['verbatimattributes']?'"'.$this->cleanInStr($occArr['verbatimattributes']).'"':'NULL').','.
				($occArr['reproductivecondition']?'"'.$this->cleanInStr($occArr['reproductivecondition']).'"':'NULL').','.
				(array_key_exists('cultivationstatus',$occArr)?'1':'0').','.
				($occArr['establishmentmeans']?'"'.$this->cleanInStr($occArr['establishmentmeans']).'"':'NULL').','.
				'"'.$this->cleanInStr($occArr['country']).'",'.
				($occArr['stateprovince']?'"'.$this->cleanInStr($occArr['stateprovince']).'"':'NULL').','.
				($occArr['county']?'"'.$this->cleanInStr($occArr['county']).'"':'NULL').','.
				'"'.$this->cleanInStr($occArr['locality']).'",'.$localitySecurity.','.
				$occArr['decimallatitude'].','.$occArr['decimallongitude'].','.
				($occArr['geodeticdatum']?'"'.$this->cleanInStr($occArr['geodeticdatum']).'"':'NULL').','.
				($occArr['coordinateuncertaintyinmeters']?'"'.$occArr['coordinateuncertaintyinmeters'].'"':'NULL').','.
				($occArr['georeferenceremarks']?'"'.$this->cleanInStr($occArr['georeferenceremarks']).'"':'NULL').','.
				($occArr['minimumelevationinmeters']?$occArr['minimumelevationinmeters']:'NULL').','.
				$obsUid.') ';
				//echo $sql;
				if($this->conn->query($sql)){
					$occArr['phuid'] = $obsUid;
					$newOccId = $this->conn->insert_id;
					//Link observation to checklist
					if(array_key_exists('clid',$occArr)){
						if($clid = $occArr['clid']){
							$sql = '';
							$targetClid = substr($clid,2);
							if(substr($clid,0,2) == 'cl'){
								$sql = 'SELECT cltl.tid '.
									'FROM fmchklsttaxalink cltl INNER JOIN taxstatus ts1 ON cltl.tid = ts1.tid '.
									'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
									'WHERE ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND cltl.clid = '.$targetClid.' AND ts2.tid = '.$tid;
								$rs = $this->conn->query($sql);
								$clTid = 0;
								while($r = $rs->fetch_object()){
									$clTid = $r->tid;
									if($clTid == $tid) break; 
								}
								$rs->close();
								if(!$clTid){
									$sql = 'INSERT INTO fmchklsttaxalink(tid,clid) '.
										'VALUES('.$tid.','.$targetClid.')';
									$this->conn->query($sql);
									$clTid = $tid;
								}
								$sql = 'INSERT INTO fmvouchers(tid,clid,occid,collector) '.
									'VALUES('.$clTid.','.$targetClid.','.$newOccId.',"") ';
								$this->conn->query($sql);
							}
							else{
								$sql = 'INSERT INTO omsurveyoccurlink(occid,surveyid) '.
									'VALUES('.$newOccId.','.$targetClid.')';
								$this->conn->query($sql);
							}
						}
					}
					//Load images
					$imgStatus = $this->dbImages($nameArr,$occArr,$newOccId,$tid);
					if($imgStatus){
						$this->errArr[] = 'Observation added successfully, but images did not upload successful';
					}
					else{
						$sql = 'INSERT INTO omoccurgeoindex(tid,decimallatitude,decimallongitude) '.
							'VALUE('.$tid.','.round($occArr['decimallatitude'],2).','.round($occArr['decimallongitude'],2).')';
						$this->conn->query($sql);
					}
				}
				else{
					$this->errArr[] = 'ERROR: Failed to load observation record.<br/> Err Descr: '.$this->conn->error;
				}
			}
			else{
				$this->errArr[] = 'ERROR: Failed to load images ';
			}
		}
		return $newOccId;
	}

	private function loadImages(){
		$statusStr = '';
		//Set download paths and variables
		set_time_limit(240);
 		$this->imageRootPath = $GLOBALS['imageRootPath'];
		if(substr($this->imageRootPath,-1) != '/') $this->imageRootPath .= '/';  
		$this->imageRootUrl = $GLOBALS['imageRootUrl'];
		if(substr($this->imageRootUrl,-1) != '/') $this->imageRootUrl .= '/';

		$retArr = Array();
		for($i=1;$i<=3;$i++){
			$imgFileName = 'imgfile'.$i;
			if(!array_key_exists($imgFileName,$_FILES) || !$_FILES[$imgFileName]['name']) break;
			$fileName = $this->cleanFileName(basename($_FILES[$imgFileName]['name']));
		 	$fullPath = $this->getDownloadPath($fileName); 
		 	if(move_uploaded_file($_FILES[$imgFileName]['tmp_name'], $fullPath)){
				$retArr[] = $fullPath;
		 	}
		}
		return $retArr;
	}
	
	private function dbImages($imgNamesArr,$occArr,$occId,$tid){
		$status = '';
		$imgCnt = 1;
		foreach($imgNamesArr as $imgPath){
			$imgUrl = str_replace($this->imageRootPath,$this->imageRootUrl,$imgPath);
			
			$imgTnUrl = $this->createImageThumbnail($imgUrl);
	
			$imgWebUrl = $imgUrl;
			list($width, $height) = getimagesize($imgPath);
			$fileSize = filesize($imgPath);
 			$extStr = substr($imgUrl,strrpos($imgUrl,'.'));
			//Create Large Image
			$imgLgUrl = '';
			/* Deactivate, at least for now
			if($width > ($this->webPixWidth*1.2) || $fileSize > $this->webFileSizeLimit){
				$lgWebUrlTemp = str_ireplace('_temp'.$extStr,'lg'.$extStr,$imgPath); 
				if($width < ($this->lgPixWidth*1.2)){
					if(copy($imgPath,$lgWebUrlTemp)){
						$imgLgUrl = str_ireplace($this->imageRootPath,$this->imageRootUrl,$lgWebUrlTemp);
					}
				}
				else{
					if($this->createNewImage($imgPath,$lgWebUrlTemp,$this->lgPixWidth)){
						$imgLgUrl = str_ireplace($this->imageRootPath,$this->imageRootUrl,$lgWebUrlTemp);
					}
				}
			}*/

			//Create web url
			$imgTargetPath = str_ireplace('_temp'.$extStr,$extStr,$imgPath);
			if($width < ($this->webPixWidth*1.2) && $fileSize < $this->webFileSizeLimit){
				rename($imgPath,$imgTargetPath);
			}
			else{
				$newWidth = ($width<($this->webPixWidth*1.2)?$width:$this->webPixWidth);
				$this->createNewImage($imgPath,$imgTargetPath,$newWidth);
			}
			$imgWebUrl = str_ireplace($this->imageRootPath,$this->imageRootUrl,$imgTargetPath);
			if(file_exists($imgPath)) unlink($imgPath);
				
			if($imgWebUrl){
				//If central images are on remote server and new ones stored locally, then we need to use full domain
			    //e.g. this portal is sister portal to central portal
		    	if($GLOBALS['imageDomain']){
					if(substr($imgWebUrl,0,1) == '/'){
						$imgWebUrl = 'http://'.$_SERVER['HTTP_HOST'].$imgWebUrl;
					}
					if($imgTnUrl && substr($imgTnUrl,0,1) == '/'){
						$imgTnUrl = 'http://'.$_SERVER['HTTP_HOST'].$imgTnUrl;
		    		}
		    		if($imgLgUrl && substr($imgLgUrl,0,1) == '/'){
					$imgLgUrl = 'http://'.$_SERVER['HTTP_HOST'].$imgLgUrl;
		    		}
		    	}
				
				$caption = $this->cleanInStr($occArr['caption'.$imgCnt]);
				$notes = $this->cleanInStr($occArr["notes"].$imgCnt);
				$sql = 'INSERT INTO images (tid, url, thumbnailurl, originalurl, photographeruid, imagetype, caption, occid, notes, sortsequence) '.
					'VALUES ('.$tid.',"'.$imgWebUrl.'",'.($imgTnUrl?'"'.$imgTnUrl.'"':'NULL').','.($imgLgUrl?'"'.$imgLgUrl.'"':'NULL').
					','.$occArr['phuid'].',"Observation",'.($caption?'"'.$this->cleanInStr($caption).'"':'NULL').','.$occId.','.
					($notes?'"'.$this->cleanInStr($notes).'"':'NULL').',50)';
				//echo $sql;
				if(!$this->conn->query($sql)){
					$status = 'ERROR loadImageData: '.$this->conn->error;
					//$status .= '<br/>SQL: '.$sql;
				}
			}
			$imgCnt++;
			//Reset image
			imagedestroy($this->sourceGdImg);
			unset($this->sourceGdImg);
			//if($this->processUsingImageMagick) {
				//imagedestroy($this->sourceImagickImg);
				//unset($this->sourceImagickImg);
			//}
		}
		return $status;
	}

	private function cleanFileName($fName){
		$fName = str_replace(" ","_",$fName);
		$fName = str_replace(array(chr(231),chr(232),chr(233),chr(234),chr(260)),"a",$fName);
		$fName = str_replace(array(chr(230),chr(236),chr(237),chr(238)),"e",$fName);
		$fName = str_replace(array(chr(239),chr(240),chr(241),chr(261)),"i",$fName);
		$fName = str_replace(array(chr(247),chr(248),chr(249),chr(262)),"o",$fName);
		$fName = str_replace(array(chr(250),chr(251),chr(263)),"u",$fName);
		$fName = str_replace(array(chr(264),chr(265)),"n",$fName);
		$fName = preg_replace("/[^a-zA-Z0-9\-_\.]/", "", $fName);
		if(strlen($fName) > 30) {
			$fName = substr($fName,0,25).substr($fName,strrpos($fName,"."));
		}
 		return $fName;
 	}
 	
	private function getDownloadPath($fileName){
 		if(!file_exists($this->imageRootPath.$this->collMap['institutioncode'])){
 			mkdir($this->imageRootPath.$this->collMap['institutioncode'], 0775);
 		}
		$path = $this->imageRootPath.$this->collMap['institutioncode']."/";
		$yearMonthStr = date('Ym');
 		if(!file_exists($path.$yearMonthStr)){
 			mkdir($path.$yearMonthStr, 0775);
 		}
		$path = $path.$yearMonthStr."/";
 		//Check and see if file already exists, if so, rename filename until it has a unique name
 		$tempFileName = $fileName;
 		$cnt = 0;
 		$extStr = substr($fileName,strrpos($fileName,'.'));
 		//Check to see if file with exact name already exists, if so than rename
 		while(file_exists($path.$tempFileName)){
 			$tempFileName = str_ireplace($extStr,"_".$cnt.$extStr,$fileName);
 			$cnt++;
 		}
 		$fileName = str_ireplace($extStr,"_temp".$extStr,$tempFileName);
 		return $path.$fileName;
 	}

	private function createImageThumbnail($imgUrl){
		$newThumbnailUrl = "";
		if($imgUrl){
			$imgPath = "";
			$newThumbnailPath = "";
			if(strpos($imgUrl,"http://") === 0 && strpos($imgUrl,$this->imageRootUrl) === false){
				$imgPath = $imgUrl;
				if(!is_dir($this->imageRootPath."misc_thumbnails/")){
					if(!mkdir($this->imageRootPath."misc_thumbnails/", 0775)) return "";
				}
				$fileName = "";
 				$extStr = substr($imgUrl,strrpos($imgUrl,'.'));
				if(stripos($imgUrl,"_temp".$extStr)){
					$fileName = str_ireplace("_temp".$extStr,"_tn".$extStr,substr($imgUrl,strrpos($imgUrl,"/")));
				}
				else{
					$fileName = str_ireplace($extStr,"_tn".$extStr,substr($imgUrl,strrpos($imgUrl,"/")));
				}
				$newThumbnailPath = $this->imageRootPath."misc_thumbnails/".$fileName;
				$cnt = 1;
				$fileNameBase = str_ireplace("_tn".$extStr,"",$fileName);
				while(file_exists($newThumbnailPath)){
					$fileName = $fileNameBase."_tn".$cnt.$extStr;
					$newThumbnailPath = $this->imageRootPath."misc_thumbnails/".$fileName;
					$cnt++; 
				}
				$newThumbnailUrl = $this->imageRootUrl."misc_thumbnails/".$fileName;
			}
			elseif(strpos($imgUrl,$this->imageRootUrl) === 0){
				$imgPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgUrl);
				$newThumbnailUrl = str_ireplace("_temp".$extStr,"_tn".$extStr,$imgUrl);
				$newThumbnailPath = str_replace($this->imageRootUrl,$this->imageRootPath,$newThumbnailUrl);
			}
			if(!$newThumbnailUrl) return "";
			if(!$this->createNewImage($imgPath,$newThumbnailPath,$this->tnPixWidth,70)){
				return false;
			}
		}
		return $newThumbnailUrl;
	}
	
	private function createNewImage($sourceImg,$targetPath,$targetWidth,$qualityRating = 0){
		$successStatus = 0;
		list($sourceWidth, $sourceHeight) = getimagesize($sourceImg);
		$newWidth = $targetWidth;
		$newHeight = round($sourceHeight*($targetWidth/$sourceWidth));
		if($newHeight > $targetWidth*1.2){
			$newHeight = $targetWidth;
			$newWidth = round($sourceWidth*($targetWidth/$sourceHeight));
		}
		/*
		if($this->processUsingImageMagick) {
			// Usa ImageMagick to resize images 
			$this->createNewImageMagick($sourceImg,$targetPath,$newWidth,$qualityRating);
		} 
		else
		*/ 
		if(extension_loaded('gd') && function_exists('gd_info')) {
			// GD is installed and working 
			$successStatus = $this->createNewImageGD($sourceImg,$targetPath,$newWidth,$newHeight,$sourceWidth,$sourceHeight,$qualityRating);
		}
		else{
			// Neither ImageMagick nor GD are installed 
			$this->errArr[] = 'No appropriate image handler to remove EXIF data';
		}
		return $successStatus;
	}

	private function createNewImageGD($sourceImg,$targetPath,$newWidth,$newHeight,$sourceWidth,$sourceHeight,$qualityRating){
		$successStatus = false;

	   	if(!$this->sourceGdImg){
	   		$this->sourceGdImg = imagecreatefromjpeg($sourceImg);
	   	}
		$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
		imagecopyresampled($tmpImg,$this->sourceGdImg,0,0,0,0,$newWidth, $newHeight,$sourceWidth,$sourceHeight);
		
		if($qualityRating){
			$successStatus = imagejpeg($tmpImg, $targetPath, $qualityRating);
		}
		else{
			$successStatus = imagejpeg($tmpImg, $targetPath);
		}

		imagedestroy($tmpImg);
		return $successStatus;
	}
	
	private function createNewImageMagick($sourceImg,$targetPath,$newWidth,$qualityRating){
		$status = false;
		$ct;
		$retval;
		if($newWidth < 300){
			$ct = system('convert '.$sourceImg.' -thumbnail '.$newWidth.'x'.($newWidth*1.5).' '.$targetPath, $retval);
		}
		else{
			$ct = system('convert '.$sourceImg.' -resize '.$newWidth.'x'.($newWidth*1.5).($qualityRating?' -quality '.$qualityRating:'').' '.$targetPath, $retval);
		}
		if(file_exists($targetPath)){
			$status = true;
		}
		return $status;
	}

	public function getChecklists($userRights){
		$retArr = Array();
		$clStr = '';
		if(array_key_exists('ClAdmin',$userRights)){
			$clStr = implode(',',$userRights['ClAdmin']);
		}
		if(array_key_exists("SuperAdmin",$userRights) || $clStr){
			$sql = 'SELECT clid,name FROM fmchecklists WHERE access != "private" ';
			if(!array_key_exists("SuperAdmin",$userRights) && $clStr){
				$sql .= 'AND clid IN('.$clStr.') ';
			}
			$sql .= 'ORDER BY name';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$retArr['cl'.$row->clid] = $row->name;
			}
	
			//Add biodiversity survey projects
			$sql = 'SELECT surveyid, projectname FROM omsurveys WHERE ispublic = 1 ';
			if(!array_key_exists("SuperAdmin",$userRights) && $clStr){
				$sql .= 'AND surveyid IN('.$clStr.') ';
			}
			$sql .= 'ORDER BY projectname';
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$retArr['sv'.$row->surveyid] = $row->projectname;
			}
			asort($retArr);
		}
		return $retArr;
	}
 	
	public function setUseImageMagick($useIM){
 		$this->processUsingImageMagick = $useIM;
 	}
	
	public function getCollMap(){
		return $this->collMap;
	}
	
	public function getErrorArr(){
		return $this->errArr;
	}

	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>