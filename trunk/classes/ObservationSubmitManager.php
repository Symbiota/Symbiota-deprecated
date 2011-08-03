<?php
include_once($serverRoot.'/config/dbconnection.php');

class ObservationSubmitManager {

	private $conn;
	private $occId;
	private $uid;
	private $institutionCode;

	private $occurrenceMap = Array();

	private $imageRootPath = "";
	private $imageRootUrl = "";

	private $tnPixWidth = 200;
	private $webPixWidth = 1400;
	private $lgPixWidth = 3168;
	private $webFileSizeLimit = 250000;
	
	public function __construct($uid){
		$this->uid = $uid;
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function addObservation($occArr, $obsUid){
		$statusStr = '';
		if($occArr){
			$collId = $occArr['collid'];
			//Get Institutional code for storage
			$owner = '';
			$ownerSql = 'SELECT institutioncode, collectionname FROM omcollections c WHERE (collid = '.$collId.')';
			$rs = $this->conn->query($ownerSql);
			if($row = $rs->fetch_object()){
				$this->institutionCode = $row->institutioncode;
				$owner = "";//$row->collectionname;
			}
			//Load Image, abort if unsuccessful
			$nameArr = $this->loadImages();
			if($nameArr){
				//Setup Event Date fields
				if($dateObj = strtotime($occArr['eventdate'])){
					$eventDate = date('Y-m-d',$dateObj);
					$eventYear = date('Y',$dateObj);
					$eventMonth = date('m',$dateObj);
					$eventDay = date('d',$dateObj);
					$startDay = date('z',$dateObj)+1;
				}
				//Get tid for scinetific name
				$tid = 0;
				$result = $this->conn->query('SELECT tid FROM taxa WHERE (sciname = "'.$occArr['sciname'].'")');
				if($row = $result->fetch_object()){
					$tid = $row->tid;
				}
				if(!$tid){
					//Abort process
					return 'ERROR: scientific name failed, contact admin to add name to thesaurus';
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
					'associatedCollectors, eventDate, year, month, day, startDayOfYear, habitat, occurrenceRemarks, associatedTaxa, '.
					'dynamicProperties, reproductiveCondition, cultivationStatus, establishmentMeans, country, '.
					'stateProvince, county, locality, localitySecurity, decimalLatitude, decimalLongitude, '.
					'geodeticDatum, coordinateUncertaintyInMeters, georeferenceRemarks, minimumElevationInMeters, observeruid) '.
	
				'VALUES ('.$collId.',"Observation",'.($occArr['family']?'"'.$occArr['family'].'"':'NULL').','.
				'"'.$occArr['sciname'].'","'.$occArr['sciname'].' '.$occArr['scientificnameauthorship'].'",'.
				($occArr['scientificnameauthorship']?'"'.$occArr['scientificnameauthorship'].'"':'NULL').','.
				$tid.",".($occArr['taxonremarks']?'"'.$occArr['taxonremarks'].'"':'NULL').','.
				($occArr['identifiedby']?'"'.$occArr['identifiedby'].'"':'NULL').','.
				($occArr['dateidentified']?'"'.$occArr['dateidentified'].'"':'NULL').','.
				($occArr['identificationreferences']?'"'.$occArr['identificationreferences'].'"':'NULL').','.
				'"'.$occArr['recordedby'].'",'.($occArr['recordnumber']?'"'.$occArr['recordnumber'].'"':'NULL').','.
				($occArr['associatedcollectors']?'"'.$occArr['associatedcollectors'].'"':'NULL').','.
				'"'.$eventDate.'",'.$eventYear.','.$eventMonth.','.$eventDay.','.$startDay.','.
				($occArr['habitat']?'"'.$occArr['habitat'].'"':'NULL').','.
				($occArr['occurrenceremarks']?'"'.$occArr['occurrenceremarks'].'"':'NULL').','.
				($occArr['associatedtaxa']?'"'.$occArr['associatedtaxa'].'"':'NULL').','.
				($occArr['dynamicproperties']?'"'.$occArr['dynamicproperties'].'"':'NULL').','.
				($occArr['reproductivecondition']?'"'.$occArr['reproductivecondition'].'"':'NULL').','.
				(array_key_exists('cultivationstatus',$occArr)?'1':'0').','.
				($occArr['establishmentmeans']?'"'.$occArr['establishmentmeans'].'"':'NULL').','.
				'"'.$occArr['country'].'",'.($occArr['stateprovince']?'"'.$occArr['stateprovince'].'"':'NULL').','.
				($occArr['county']?'"'.$occArr['county'].'"':'NULL').','.
				'"'.$occArr['locality'].'",'.(array_key_exists('localitysecurity',$occArr)?'1':'0').','.
				$occArr['decimallatitude'].','.$occArr['decimallongitude'].','.
				($occArr['geodeticdatum']?'"'.$occArr['geodeticdatum'].'"':'NULL').','.
				($occArr['coordinateuncertaintyinmeters']?'"'.$occArr['coordinateuncertaintyinmeters'].'"':'NULL').','.
				($occArr['georeferenceremarks']?'"'.$occArr['georeferenceremarks'].'"':'NULL').','.
				($occArr['minimumelevationinmeters']?$occArr['minimumelevationinmeters']:'NULL').','.
				$obsUid.') ';
				//echo $sql;
				if($this->conn->query($sql)){
					if(!$occArr['phuid']) $occArr['phuid'] = $obsUid;
					$statusStr = $this->conn->insert_id;
					$imgStatus = $this->dbImages($nameArr,$occArr,$this->conn->insert_id,$tid);
					if($imgStatus){
						$statusStr = 'Observation added successfully, but images did not upload successful.<br/>'.$imgStatus;
					}
					else{
						$sql = 'INSERT INTO omoccurgeoindex(tid,decimallatitude,decimallongitude) '.
							'VALUE('.$tid.','.round($occArr['decimallatitude'],2).','.round($occArr['decimallongitude'],2).')';
						$this->conn->query($sql);
					}
				}
				else{
					$statusStr = 'ERROR: Failed to load observation record.<br/> Err Descr: '.$this->conn->error;
				}
			}
		}
		return $statusStr;
	}

	private function loadImages(){
		$status = '';
		//Set download paths and variables
		set_time_limit(120);
		ini_set('max_input_time',120);
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
		//Get owner
		foreach($imgNamesArr as $imgPath){
			$imgUrl = str_replace($this->imageRootPath,$this->imageRootUrl,$imgPath);
			
			$imgTnUrl = $this->createImageThumbnail($imgUrl);
	
			$imgWebUrl = $imgUrl;
			list($width, $height) = getimagesize($imgPath);
			$fileSize = filesize($imgPath);
			//Create Large Image
			$imgLgUrl = '';
			/* Deactivate, at least for now
			if($width > ($this->webPixWidth*1.2) || $fileSize > $this->webFileSizeLimit){
				$lgWebUrlTemp = str_ireplace('_temp.jpg','lg.jpg',$imgPath); 
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
			$imgTargetPath = str_ireplace('_temp.jpg','.jpg',$imgPath);
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
				$caption = $this->cleanStr($occArr['caption']);
				$notes = (array_key_exists("notes",$occArr)?$this->cleanStr($occArr["notes"]):"");
				$sql = 'INSERT INTO images (tid, url, thumbnailurl, originalurl, photographeruid, imagetype, caption, occid, notes, sortsequence) '.
					'VALUES ('.$tid.',"'.$imgWebUrl.'",'.($imgTnUrl?'"'.$imgTnUrl.'"':'NULL').','.($imgLgUrl?'"'.$imgLgUrl.'"':'NULL').
					','.$occArr['phuid'].',"Observation",'.($caption?'"'.$caption.'"':'NULL').','.$occId.','.($notes?'"'.$notes.'"':'NULL').',50)';
				//echo $sql;
				if(!$this->conn->query($sql)){
					$status = 'ERROR loadImageData: '.$this->conn->error;
					//$status .= '<br/>SQL: '.$sql;
				}
			}
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
 		if(!file_exists($this->imageRootPath.$this->institutionCode)){
 			mkdir($this->imageRootPath.$this->institutionCode, 0775);
 		}
		$path = $this->imageRootPath.$this->institutionCode."/";
		$yearMonthStr = date('Ym');
 		if(!file_exists($path.$yearMonthStr)){
 			mkdir($path.$yearMonthStr, 0775);
 		}
		$path = $path.$yearMonthStr."/";
 		//Check and see if file already exists, if so, rename filename until it has a unique name
 		$tempFileName = $fileName;
 		$cnt = 0;
 		while(file_exists($path.$tempFileName)){
 			$tempFileName = str_ireplace(".jpg","_".$cnt.".jpg",$fileName);
 			$cnt++;
 		}
 		$fileName = str_ireplace(".jpg","_temp.jpg",$tempFileName);
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
				if(stripos($imgUrl,"_temp.jpg")){
					$fileName = str_ireplace("_temp.jpg","tn.jpg",substr($imgUrl,strrpos($imgUrl,"/")));
				}
				else{
					$fileName = str_ireplace(".jpg","tn.jpg",substr($imgUrl,strrpos($imgUrl,"/")));
				}
				$newThumbnailPath = $this->imageRootPath."misc_thumbnails/".$fileName;
				$cnt = 1;
				$fileNameBase = str_ireplace("tn.jpg","",$fileName);
				while(file_exists($newThumbnailPath)){
					$fileName = $fileNameBase."tn".$cnt.".jpg";
					$newThumbnailPath = $this->imageRootPath."misc_thumbnails/".$fileName;
					$cnt++; 
				}
				$newThumbnailUrl = $this->imageRootUrl."misc_thumbnails/".$fileName;
			}
			elseif(strpos($imgUrl,$this->imageRootUrl) === 0){
				$imgPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgUrl);
				$newThumbnailUrl = str_ireplace("_temp.jpg","tn.jpg",$imgUrl);
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
        $successStatus = false;
		list($sourceWidth, $sourceHeight) = getimagesize($sourceImg);
        $newWidth = $targetWidth;
        $newHeight = round($sourceHeight*($targetWidth/$sourceWidth));
        if($newHeight > $targetWidth*1.2){
        	$newHeight = $targetWidth;
        	$newWidth = round($sourceWidth*($targetWidth/$sourceHeight));
        }

       	$newImg = imagecreatefromjpeg($sourceImg);  

    	$tmpImg = imagecreatetruecolor($newWidth,$newHeight);

		imagecopyresampled($tmpImg,$newImg,0,0,0,0,$newWidth, $newHeight,$sourceWidth,$sourceHeight);

        if($qualityRating){
        	$successStatus = imagejpeg($tmpImg, $targetPath, $qualityRating);
        }
        else{
        	$successStatus = imagejpeg($tmpImg, $targetPath);
        }

        imagedestroy($tmpImg);
	    return $successStatus;
	}
	
	public function getUserArr(){
		$retArr = array();
		$sql = 'SELECT u.uid, CONCAT_WS(", ",u.lastname,u.firstname) AS fullname '.
			'FROM users u ORDER BY u.lastname, u.firstname ';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$retArr[$row->uid] = $row->fullname;
		}
		$result->close();
		return $retArr;
	}

	private function cleanStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = str_replace("\"","'",$newStr);
		return $newStr;
	}

	public function getCollArr($collArr){
		$retArr = Array();
		$sql = "SELECT collid,collectionname,colltype FROM omcollections WHERE colltype LIKE '%observation%' ";
		if(!in_array("all",$collArr)){
			if($collArr){
				$sql .= "AND collid IN(".implode(",",$collArr).") ";
			}
		}
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$cName = $row->collectionname;
			if($row->colltype == "General Observations"){
				$cName .= " [default]";
			}
			$retArr[$row->collid] = $cName;
		}
		return $retArr;
	}
}

?>