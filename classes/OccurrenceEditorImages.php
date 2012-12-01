<?php
class OccurrenceEditorImages extends OccurrenceEditorManager {

	private $photographerArr = Array();
	private $imageRootPath = "";
	private $imageRootUrl = "";

	private $tnPixWidth = 200;
	private $webPixWidth = 1200;
	private $lgPixWidth = 3168;
	private $webFileSizeLimit = 300000;
	
	private $targetPath;
	private $targetUrl;
	private $fileName;
	private $sourceImg;
	
	
	public function __construct(){
 		parent::__construct();
	}

	public function __destruct(){
 		parent::__destruct();
 		if($this->sourceImg){
 			try{
 				imagedestroy($this->sourceImg);
 			}
 			catch(Exception $e){
 			}
 		}
	}

	public function editImage(){
		$this->setRootpaths();
		$status = "Image editted successfully!";
		$imgId = $_REQUEST["imgid"];
	 	$url = $_REQUEST["url"];
	 	$tnUrl = $_REQUEST["tnurl"];
	 	$origUrl = $_REQUEST["origurl"];
	 	if(array_key_exists("renameweburl",$_REQUEST)){
	 		$oldUrl = $_REQUEST["oldurl"];
	 		$oldName = str_replace($this->imageRootUrl,$this->imageRootPath,$oldUrl);
	 		$newWebName = str_replace($this->imageRootUrl,$this->imageRootPath,$url);
	 		if($url != $oldUrl){
	 			if(file_exists($newWebName)){
 					$status = 'ERROR: unable to modify image URL because a file already exists with that name; ';
		 			$url = $oldUrl;
	 			}
	 			else{
		 			if(!rename($oldName,$newWebName)){
		 				$url = $oldUrl;
			 			$status .= "Web URL rename FAILED (possible write permissions issue); ";
		 			}
	 			}
	 		}
		}
		if(array_key_exists("renametnurl",$_REQUEST)){
	 		$oldTnUrl = $_REQUEST["oldtnurl"];
	 		$oldName = str_replace($this->imageRootUrl,$this->imageRootPath,$oldTnUrl);
	 		$newName = str_replace($this->imageRootUrl,$this->imageRootPath,$tnUrl);
	 		if($tnUrl != $oldTnUrl){
	 			if(file_exists($newName)){
 					$status = 'ERROR: unable to modify image URL because a file already exists with that name; ';
		 			$tnUrl = $oldTnUrl;
	 			}
	 			else{
		 			if(!rename($oldName,$newName)){
		 				$tnUrl = $oldTnUrl;
			 			$status = "Thumbnail URL rename FAILED (possible write permissions issue); ";
		 			}
	 			}
	 		}
		}
		if(array_key_exists("renameorigurl",$_REQUEST)){
	 		$oldOrigUrl = $_REQUEST["oldorigurl"];
	 		$oldName = str_replace($this->imageRootUrl,$this->imageRootPath,$oldOrigUrl);
	 		$newName = str_replace($this->imageRootUrl,$this->imageRootPath,$origUrl);
	 		if($origUrl != $oldOrigUrl){
	 			if(file_exists($newName)){
 					$status = 'ERROR: unable to modify image URL because a file already exists with that name; ';
		 			$tnUrl = $oldTnUrl;
	 			}
	 			else{
		 			if(!rename($oldName,$newName)){
		 				$origUrl = $oldOrigUrl;
			 			$status .= "ERROR: Thumbnail URL rename FAILED (possible write permissions issue); ";
		 			}
	 			}
	 		}
		}
		$occId = $_REQUEST["occid"];
		$caption = $this->cleanInStr($_REQUEST["caption"]);
		$photographer = $this->cleanInStr($_REQUEST["photographer"]);
		$photographerUid = (array_key_exists('photographeruid',$_REQUEST)?$_REQUEST['photographeruid']:'');
		$notes = $this->cleanInStr($_REQUEST["notes"]);
		$copyRight = $this->cleanInStr($_REQUEST["copyright"]);
		$sourceUrl = $this->cleanInStr($_REQUEST["sourceurl"]);

		$sql = "UPDATE images ".
			"SET url = \"".$url."\", thumbnailurl = ".($tnUrl?"\"".$tnUrl."\"":"NULL").
			",originalurl = ".($origUrl?"\"".$origUrl."\"":"NULL").",occid = ".$occId.",caption = ".
			($caption?"\"".$caption."\"":"NULL").
			",photographer = ".($photographer?'"'.$photographer.'"':"NULL").
			",photographeruid = ".($photographerUid?$photographerUid:"NULL").
			",notes = ".($notes?"\"".$notes."\"":"NULL").
			",copyright = ".($copyRight?"\"".$copyRight."\"":"NULL").",imagetype = \"specimen\",sourceurl = ".
			($sourceUrl?"\"".$sourceUrl."\"":"NULL").
			" WHERE (imgid = ".$imgId.')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$status .= "ERROR: image not changed, ".$this->conn->error."SQL: ".$sql;
		}
		return $status;
	}

	public function deleteImage($imgIdDel, $removeImg){
		$imgUrl = ""; $imgThumbnailUrl = ""; $imgOriginalUrl = "";
		$status = "Image deleted successfully";
		$occid = 0;
		$sqlQuery = 'SELECT url, thumbnailurl, originalurl, occid FROM images WHERE (imgid = '.$imgIdDel.')';
		$result = $this->conn->query($sqlQuery);
		if($row = $result->fetch_object()){
			$imgUrl = $row->url;
			$imgThumbnailUrl = $row->thumbnailurl;
			$imgOriginalUrl = $row->originalurl;
		}
		$result->close();
				
		$sql = "DELETE FROM images WHERE (imgid = ".$imgIdDel.')';
		//echo $sql;
		if($this->conn->query($sql)){
			if($removeImg){
				//Remove images only if there are no other references to the image
				$sql = "SELECT imgid FROM images WHERE (url = '".$imgUrl."')";
				$rs = $this->conn->query($sql);
				if(!$rs->num_rows){
					$this->setRootpaths();
					//Delete image from server 
					$imgDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgUrl);
					if(file_exists($imgDelPath)){
						if(!unlink($imgDelPath)){
							$status = "Deleted image record from database successfully but FAILED to delete image from server. The Image will have to be deleted manually.";
						}
					}
					$imgTnDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgThumbnailUrl);
					if(file_exists($imgTnDelPath)){
						unlink($imgTnDelPath);
					}
					$imgOriginalDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgOriginalUrl);
					if(file_exists($imgOriginalDelPath)){
						unlink($imgOriginalDelPath);
					}
				}
			}
		}
		else{
			$status = "deleteImage: ".$this->conn->error."\nSQL: ".$sql;
		}
		return $status;
	}

	public function remapImage($imgId, $occId){
		$statusStr = '';
		$sql = 'UPDATE images SET occid = '.$occId.' WHERE (imgid = '.$imgId.')';
		if($this->conn->query($sql)){
			$imgSql = 'UPDATE images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
				'SET i.tid = o.tidinterpreted WHERE (i.imgid = '.$imgId.')';
			//echo $imgSql;
			$this->conn->query($imgSql);
		}
		else{
			$statusStr = 'ERROR: Unalbe to remap image to another occurrence record. Error msg: '.$this->conn->error;
		}
		return $statusStr;
	}
	
	public function addImage(){
		$status = '';
		//Set download paths and variables
		set_time_limit(120);
		ini_set("max_input_time",120);
		ini_set('memory_limit','512M');
		$this->setRootPaths();
		$this->setTargetPaths();
		if(array_key_exists('imgWebWidth',$GLOBALS)) $this->webPixWidth = $GLOBALS['imgWebWidth'];
		if(array_key_exists('imgTnWidth',$GLOBALS)) $this->tnPixWidth = $GLOBALS['imgTnWidth'];
		if(array_key_exists('imgLgWidth',$GLOBALS)) $this->lgPixWidth = $GLOBALS['imgLgWidth'];
		if(array_key_exists('imgFileSizeLimit',$GLOBALS)) $this->webFileSizeLimit = $GLOBALS['imgFileSizeLimit'];
		
		//Check for image path or download image file
		$imgUrlLink = (array_key_exists("imgurl",$_REQUEST)?$_REQUEST["imgurl"]:"");
		$sourceImgUri = $imgUrlLink;
		$copyToServer = 0;
		if(array_key_exists('copytoserver',$_REQUEST)) $copyToServer = $_REQUEST['copytoserver'];
		$lgUrl = '';
		$tnUrl = '';
		$imgPath = "";
		if($sourceImgUri){
			//URL of image supplied for mapping or upload
			$tnUrl = $_REQUEST["tnurl"];
			$lgUrl = $_REQUEST["lgurl"];
			//Set file name
			$fName = basename($sourceImgUri);
			$this->setFileName($fName);
		}
		else{
			//Source image is an image upload
			if(!$this->loadImage()) return;
			$sourceImgUri = $this->targetPath.$this->fileName;
		}
		if(!$tnUrl){
			//Create local thumbnail no matter what, this way size is guaranteed to be correct 
			$newTnName = str_ireplace("_temp.jpg","_tn.jpg",$this->fileName);
			if($this->createNewImage($sourceImgUri,$this->targetPath.$newTnName,$this->tnPixWidth,70)){
				$tnUrl = $this->targetUrl.$newTnName;
			}
		}

		list($width, $height) = getimagesize($sourceImgUri);
		$fileSize = 0;
		$fileSize = filesize($sourceImgUri);
		//Create large
		$noLargeVersion = (array_key_exists('nolgimage',$_REQUEST)?1:0);
		if(!$noLargeVersion && (!$lgUrl || $copyToServer)){
			if($width > ($this->webPixWidth*1.2)){
				//Image is larger than basic web version
				$newLgName = str_ireplace("_temp.jpg","_lg.jpg",$this->fileName);
				if($width < ($this->lgPixWidth*1.2)){
					if(copy($sourceImgUri,$this->targetPath.$newLgName)){
						$lgUrl = $this->targetUrl.$newLgName;
					}
				}
				else{
					if($this->createNewImage($sourceImgUri,$this->targetPath.$newLgName,$this->lgPixWidth)){
						$lgUrl = $this->targetUrl.$newLgName;
					}
				}
			}
		}
		
		$webUrl = $sourceImgUri;
		if(!$imgUrlLink || $copyToServer){
			//Create web version of image unless url link that is not meant to be loaded to local server 
			$newWebName = str_ireplace("_temp.jpg",".jpg",$this->fileName);
			if($width < ($this->webPixWidth*1.2) && $fileSize < $this->webFileSizeLimit){
				if(copy($sourceImgUri,$this->targetPath.$newWebName)){
					$webUrl = $this->targetUrl.$newWebName;					
				}
			}
			else{
				$newWidth = ($width<($this->webPixWidth*1.1)?$width:$this->webPixWidth);
				if($this->createNewImage($sourceImgUri,$this->targetPath.$newWebName,$newWidth)){
					$webUrl = $this->targetUrl.$newWebName;
				}
			}
			if(strpos($sourceImgUri,$this->targetPath) === 0) unlink($sourceImgUri);
		}

		//Load to database
		if($webUrl) $status = $this->databaseImage($webUrl,$tnUrl,$lgUrl);

		return $status;
	}
	
	private function loadImage(){
		$imgFile = basename($_FILES['imgfile']['name']);
		$this->setFileName($imgFile);
		if(move_uploaded_file($_FILES['imgfile']['tmp_name'], $this->targetPath.$this->fileName)){
			return true;
		}
		return false;
	}

	private function databaseImage($webUrl,$tnUrl,$lgUrl){
		global $paramsArr;
		if(!$webUrl) return 'ERROR: web url is null ';
		$status = 'Image added successfully!';
		$occId = $_REQUEST["occid"];
		$owner = $_REQUEST["institutioncode"];
		$caption = $this->cleanInStr($_REQUEST["caption"]);
		$photographerUid = (array_key_exists('photographeruid',$_REQUEST)?$_REQUEST["photographeruid"]:'');
		$photographer = (array_key_exists('photographer',$_REQUEST)?$this->cleanInStr($_REQUEST["photographer"]):'');
		$sourceUrl = (array_key_exists("sourceurl",$_REQUEST)?trim($_REQUEST["sourceurl"]):"");
		$copyRight = $this->cleanInStr($_REQUEST["copyright"]);
		$notes = (array_key_exists("notes",$_REQUEST)?$this->cleanInStr($_REQUEST["notes"]):"");
		$sql = 'INSERT INTO images (tid, url, thumbnailurl, originalurl, photographer, photographeruid, caption, '.
			'owner, sourceurl, copyright, occid, username, notes) '.
			'VALUES ('.($_REQUEST['tid']?$_REQUEST['tid']:'NULL').',"'.$webUrl.'",'.
			($tnUrl?'"'.$tnUrl.'"':'NULL').','.
			($lgUrl?'"'.$lgUrl.'"':'NULL').','.
			($photographer?'"'.$photographer.'"':'NULL').','.
			($photographerUid?$photographerUid:'NULL').','.
			($caption?'"'.$caption.'"':'NULL').','.
			($owner?'"'.$owner.'"':'NULL').','.
			($sourceUrl?'"'.$sourceUrl.'"':'NULL').','.
			($copyRight?'"'.$copyRight.'"':'NULL').','.
			($occId?$occId:'NULL').','.
			(isset($paramsArr['un'])?'"'.$paramsArr['un'].'"':'NULL').','.
			($notes?'"'.$notes.'"':'NULL').')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$status = "ERROR Loading Image Data: ".$this->conn->error."<br/>SQL: ".$sql;
		}
		return $status;
	}

	private function createNewImage($sourceImg,$targetPath,$targetWidth,$qualityRating = 0){
        $successStatus = false;
		list($sourceWidth, $sourceHeight) = getimagesize($sourceImg);
        $newWidth = $targetWidth;
        $newHeight = round($sourceHeight*($targetWidth/$sourceWidth));
        if($newHeight > $newWidth*2){
        	$newHeight = $targetWidth;
        	$newWidth = round($sourceWidth*($targetWidth/$sourceHeight));
        }

        if(!$this->sourceImg){
        	$this->sourceImg = imagecreatefromjpeg($sourceImg);
        }

    	$tmpImg = imagecreatetruecolor($newWidth,$newHeight);

		imagecopyresampled($tmpImg,$this->sourceImg,0,0,0,0,$newWidth, $newHeight,$sourceWidth,$sourceHeight);

        if($qualityRating){
        	$successStatus = imagejpeg($tmpImg, $targetPath, $qualityRating);
        }
        else{
        	$successStatus = imagejpeg($tmpImg, $targetPath);
        }

        imagedestroy($tmpImg);
	    return $successStatus;
	}

	private function setFileName($fName){
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
 		//Check and see if file already exists, if so, rename filename until it has a unique name
 		$tempFileName = $fName;
 		$cnt = 0;
		while(file_exists($this->targetPath.$tempFileName)){
 			$tempFileName = str_ireplace(".jpg","_".$cnt.".jpg",$fName);
 			$cnt++;
 		}
		
		$this->fileName = str_ireplace(".jpg","_temp.jpg",$tempFileName);
 	}
 	
	private function setRootPaths(){
		$this->imageRootPath = $GLOBALS["imageRootPath"];
		if(substr($this->imageRootPath,-1) != "/") $this->imageRootPath .= "/";  
		$this->imageRootUrl = $GLOBALS["imageRootUrl"];
		if(substr($this->imageRootUrl,-1) != "/") $this->imageRootUrl .= "/";
	}

 	private function setTargetPaths(){
 		if(!file_exists($this->imageRootPath.$_REQUEST["institutioncode"])){
 			mkdir($this->imageRootPath.$_REQUEST["institutioncode"], 0775);
 		}
		$path = $this->imageRootPath.$_REQUEST["institutioncode"]."/";
		$url = $this->imageRootUrl.$_REQUEST["institutioncode"]."/";
		$yearMonthStr = date('Ym');
 		if(!file_exists($path.$yearMonthStr)){
 			mkdir($path.$yearMonthStr, 0775);
 		}
		$path = $path.$yearMonthStr."/";
		$this->targetPath = $path;
		$url = $url.$yearMonthStr."/";
		$this->targetUrl = $url;
 	}

	public function getPhotographerArr(){
		if(!$this->photographerArr){
			$sql = "SELECT u.uid, CONCAT_WS(', ',u.lastname,u.firstname) AS fullname ".
				"FROM users u ORDER BY u.lastname, u.firstname ";
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$this->photographerArr[$row->uid] = $this->cleanOutStr($row->fullname);
			}
			$result->close();
		}
		return $this->photographerArr;
	}

	//Used in imgremapaid.php
 	public function getOccurrenceList($collId, $identifier, $collector, $collNumber){
 		$returnArr = Array();
 		if(!$identifier && !$collector && !$collNumber) return $returnArr;
 		$sql = '';
 		if($collId){
 			$sql .= 'AND (o.collid = '.$collId.') ';
 		}
 		if($identifier){
 			if(strpos($identifier,'%') !== false){
	 			$sql .= 'AND (OR (o.catalognumber LIKE "'.$identifier.'") OR (o.othercatalognumber LIKE "'.$identifier.'"))';
 			}
 			else{
	 			$sql .= 'AND ((o.catalognumber = "'.$identifier.'") OR (o.othercatalognumber = "'.$identifier.'"))';
 			}
 		}
 		if($collector){
 			$sql .= 'AND (o.recordedby LIKE "%'.$collector.'%") ';
 		}
 		if($collNumber){
 			$sql .= 'AND (o.recordnumber LIKE "%'.$collNumber.'%") ';
 		}
 		$sql = 'SELECT o.occid, o.recordedby, o.recordnumber, o.sciname, '.
 			'CONCAT_WS("; ",o.stateprovince, o.county, o.locality) AS locality '.
 			'FROM omoccurrences o WHERE '.substr($sql,4);
 		//echo $sql;
 		$rs = $this->conn->query($sql);
 		while($row = $rs->fetch_object()){
 			$occId = $row->occid;
 			$returnArr[$occId]['sciname'] = $this->cleanOutStr($row->sciname);
 			$returnArr[$occId]['recordedby'] = $this->cleanOutStr($row->recordedby);
 			$returnArr[$occId]['recordnumber'] = $this->cleanOutStr($row->recordnumber);
 			$returnArr[$occId]['locality'] = $this->cleanOutStr($row->locality);
 		}
 		$rs->close();
 		return $returnArr;
 	}
}
?>