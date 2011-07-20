<?php
class OccurrenceEditorImages extends OccurrenceEditorManager {

	private $imageMap = Array();
	
	private $photographerArr = Array();
	private $imageRootPath = "";
	private $imageRootUrl = "";

	private $tnPixWidth = 200;
	private $webPixWidth = 2000;
	private $lgPixWidth = 3168;
	private $webFileSizeLimit = 300000;
	
	public function __construct(){
 		parent::__construct();
	}

	public function __destruct(){
 		parent::__destruct();
	}

	public function getImageMap(){
		if(!$this->imageMap && $this->occId){
			$this->setImages();
		}
		return $this->imageMap;
	}

	private function setImages(){
		$sql = 'SELECT imgid, url, thumbnailurl, originalurl, caption, photographer, photographeruid, '.
			'sourceurl, copyright, notes, occid, sortsequence '.
			'FROM images '.
			'WHERE occid = '.$this->occId.' ORDER BY sortsequence';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$imgId = $row->imgid;
			$this->imageMap[$imgId]["url"] = $row->url;
			$this->imageMap[$imgId]["tnurl"] = $row->thumbnailurl;
			$this->imageMap[$imgId]["origurl"] = $row->originalurl;
			$this->imageMap[$imgId]["caption"] = $row->caption;
			$this->imageMap[$imgId]["photographer"] = $row->photographer;
			$this->imageMap[$imgId]["photographeruid"] = $row->photographeruid;
			$this->imageMap[$imgId]["sourceurl"] = $row->sourceurl;
			$this->imageMap[$imgId]["copyright"] = $row->copyright;
			$this->imageMap[$imgId]["notes"] = $row->notes;
			$this->imageMap[$imgId]["occid"] = $row->occid;
			$this->imageMap[$imgId]["sortseq"] = $row->sortsequence;
		}
		$result->close();
	}

	public function editImage(){
		$rootUrl = $GLOBALS["imageRootUrl"];
		if(substr($rootUrl,-1) != "/") $rootUrl .= "/";
		$rootPath = $GLOBALS["imageRootPath"];
		if(substr($rootPath,-1) != "/") $rootPath .= "/";
		$status = "Image editted successfully!";
		$imgId = $_REQUEST["imgid"];
	 	$url = $_REQUEST["url"];
	 	$tnUrl = $_REQUEST["tnurl"];
	 	$origUrl = $_REQUEST["origurl"];
	 	if(array_key_exists("renameweburl",$_REQUEST)){
	 		$oldUrl = $_REQUEST["oldurl"];
	 		$oldName = str_replace($rootUrl,$rootPath,$oldUrl);
	 		$newName = str_replace($rootUrl,$rootPath,$url);
	 		if($url != $oldUrl){
	 			if(!rename($oldName,$newName)){
	 				$url = $oldUrl;
		 			$status .= "Web URL rename FAILED; ";
	 			}
	 		}
		}
		if(array_key_exists("renametnurl",$_REQUEST)){
	 		$oldTnUrl = $_REQUEST["oldtnurl"];
	 		$oldName = str_replace($rootUrl,$rootPath,$oldTnUrl);
	 		$newName = str_replace($rootUrl,$rootPath,$tnUrl);
	 		if($tnUrl != $oldTnUrl){
	 			if(!rename($oldName,$newName)){
	 				$tnUrl = $oldTnUrl;
		 			$status .= "Thumbnail URL rename FAILED; ";
	 			}
	 		}
		}
		if(array_key_exists("renameorigurl",$_REQUEST)){
	 		$oldOrigUrl = $_REQUEST["oldorigurl"];
	 		$oldName = str_replace($rootUrl,$rootPath,$oldOrigUrl);
	 		$newName = str_replace($rootUrl,$rootPath,$origUrl);
	 		if($origUrl != $oldOrigUrl){
	 			if(!rename($oldName,$newName)){
	 				$origUrl = $oldOrigUrl;
		 			$status .= "Thumbnail URL rename FAILED; ";
	 			}
	 		}
		}
		$occId = $_REQUEST["occid"];
		$caption = $this->cleanStr($_REQUEST["caption"]);
		$photographer = $_REQUEST["photographer"];
		$photographerUid = (array_key_exists('photographeruid',$_REQUEST)?$_REQUEST['photographeruid']:'');
		$notes = $this->cleanStr($_REQUEST["notes"]);
		$copyRight = $this->cleanStr($_REQUEST["copyright"]);
		$sourceUrl = $this->cleanStr($_REQUEST["sourceurl"]);

		$sql = "UPDATE images ".
			"SET url = \"".$url."\", thumbnailurl = ".($tnUrl?"\"".$tnUrl."\"":"NULL").
			",originalurl = ".($origUrl?"\"".$origUrl."\"":"NULL").",occid = ".$occId.",caption = ".
			($caption?"\"".$caption."\"":"NULL").
			",photographer = ".($photographer?'"'.$photographer.'"':"NULL").
			",photographeruid = ".($photographerUid?$photographerUid:"NULL").
			",notes = ".($notes?"\"".$notes."\"":"NULL").
			",copyright = ".($copyRight?"\"".$copyRight."\"":"NULL").",imagetype = \"specimen\",sourceurl = ".
			($sourceUrl?"\"".$sourceUrl."\"":"NULL").
			" WHERE imgid = ".$imgId;
		//echo $sql;
		if(!$this->conn->query($sql)){
			$status .= "ERROR: image not changed, ".$this->conn->error."SQL: ".$sql;
		}
		return $status;
	}

	public function addImage(){
		$status = "Image added successfully!";
		//Set download paths and variables
		set_time_limit(120);
		ini_set("max_input_time",120);
 		$this->imageRootPath = $GLOBALS["imageRootPath"];
		if(substr($this->imageRootPath,-1) != "/") $this->imageRootPath .= "/";  
		$this->imageRootUrl = $GLOBALS["imageRootUrl"];
		if(substr($this->imageRootUrl,-1) != "/") $this->imageRootUrl .= "/";
		//Check for image path or download image file
		$imgUrl = (array_key_exists("imgurl",$_REQUEST)?$_REQUEST["imgurl"]:"");
		$imgPath = "";
		if(!$imgUrl){
			$imgPath = $this->loadImage();
			$imgUrl = str_replace($this->imageRootPath,$this->imageRootUrl,$imgPath);
		}
		if(!$imgUrl) return;
		
		$imgTnUrl = $this->createImageThumbnail($imgUrl);

		$imgWebUrl = $imgUrl;
		$imgLgUrl = "";
		if(strpos($imgUrl,"http://") === false || strpos($imgUrl,$this->imageRootUrl) !== false){
			//Create Large Image
			list($width, $height) = getimagesize($imgPath?$imgPath:$imgUrl);
			$fileSize = filesize($imgPath?$imgPath:$imgUrl);
			$createlargeimg = (array_key_exists('createlargeimg',$_REQUEST)&&$_REQUEST['createlargeimg']==1?true:false);
			if($createlargeimg && ($width > ($this->webPixWidth*1.2) || $fileSize > $this->webFileSizeLimit)){
				$lgWebUrlTemp = str_ireplace("_temp.jpg","lg.jpg",$imgPath); 
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
			}

			//Create web url
			$imgTargetPath = str_ireplace("_temp.jpg",".jpg",$imgPath);
			if($width < ($this->webPixWidth*1.2) && $fileSize < $this->webFileSizeLimit){
				rename($imgPath,$imgTargetPath);
			}
			else{
				$newWidth = ($width<($this->webPixWidth*1.2)?$width:$this->webPixWidth);
				$this->createNewImage($imgPath,$imgTargetPath,$newWidth);
			}
			$imgWebUrl = str_ireplace($this->imageRootPath,$this->imageRootUrl,$imgTargetPath);
			if(file_exists($imgPath)) unlink($imgPath);
		}
			
		if($imgWebUrl){
			$occId = $_REQUEST["occid"];
			$owner = $_REQUEST["institutioncode"];
			$caption = $this->cleanStr($_REQUEST["caption"]);
			$photographerUid = (array_key_exists('photographeruid',$_REQUEST)?$_REQUEST["photographeruid"]:'');
			$photographer = (array_key_exists('photographer',$_REQUEST)?$_REQUEST["photographer"]:'');
			$sourceUrl = (array_key_exists("sourceurl",$_REQUEST)?trim($_REQUEST["sourceurl"]):"");
			$copyRight = $this->cleanStr($_REQUEST["copyright"]);
			$notes = (array_key_exists("notes",$_REQUEST)?$this->cleanStr($_REQUEST["notes"]):"");
			$sql = 'INSERT INTO images (tid, url, thumbnailurl, originalurl, photographer, photographeruid, caption, '.
				'owner, sourceurl, copyright, occid, notes) '.
				'VALUES ('.($_REQUEST['tid']?$_REQUEST['tid']:'NULL').',"'.$imgWebUrl.'",'.
				($imgTnUrl?'"'.$imgTnUrl.'"':'NULL').','.
				($imgLgUrl?'"'.$imgLgUrl.'"':'NULL').','.
				($photographer?'"'.$photographer.'"':'NULL').','.
				($photographerUid?$photographerUid:'NULL').','.
				($caption?'"'.$caption.'"':'NULL').','.
				($owner?'"'.$owner.'"':'NULL').','.($sourceUrl?'"'.$sourceUrl.'"':'NULL').','.
				($copyRight?'"'.$copyRight.'"':'NULL').','.($occId?$occId:'NULL').','.($notes?'"'.$notes.'"':'NULL').')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$status = "ERROR Loading Image Data: ".$this->conn->error."<br/>SQL: ".$sql;
			}
		}
		return $status;
	}

	private function loadImage(){
	 	$imgFile = basename($_FILES['imgfile']['name']);
		$fileName = $this->getFileName($imgFile);
	 	$downloadPath = $this->getDownloadPath($fileName); 
	 	if(move_uploaded_file($_FILES['imgfile']['tmp_name'], $downloadPath)){
			return $downloadPath;
	 	}
	 	return;
	}

	private function getFileName($fName){
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
 		if(!file_exists($this->imageRootPath.$_REQUEST["institutioncode"])){
 			mkdir($this->imageRootPath.$_REQUEST["institutioncode"], 0775);
 		}
		$path = $this->imageRootPath.$_REQUEST["institutioncode"]."/";
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
	
	public function deleteImage($imgIdDel, $removeImg){
		$imgUrl = ""; $imgThumbnailUrl = ""; $imgOriginalUrl = "";
		$status = "Image deleted successfully";
		$occid = 0;
		$sqlQuery = "SELECT url, thumbnailurl, originalurl, occid ".
			"FROM images WHERE imgid = ".$imgIdDel;
		$result = $this->conn->query($sqlQuery);
		if($row = $result->fetch_object()){
			$imgUrl = $row->url;
			$imgThumbnailUrl = $row->thumbnailurl;
			$imgOriginalUrl = $row->originalurl;
		}
		$result->close();
				
		$sql = "DELETE FROM images WHERE imgid = ".$imgIdDel;
		//echo $sql;
		if($this->conn->query($sql)){
			if($removeImg){
				//Remove images only if there are no other references to the image
				$sql = "SELECT imgid FROM images WHERE url = '".$imgUrl."'";
				$rs = $this->conn->query($sql);
				if(!$rs->num_rows){
					$imageRootUrl = $GLOBALS["imageRootUrl"];
					if(substr($imageRootUrl,-1)!='/') $imageRootUrl .= "/";
					$imageRootPath = $GLOBALS["imageRootPath"];
					if(substr($imageRootPath,-1)!='/') $imageRootPath .= "/";
					//Delete image from server 
					$imgDelPath = str_replace($imageRootUrl,$imageRootPath,$imgUrl);
					if(file_exists($imgDelPath)){
						if(!unlink($imgDelPath)){
							$status = "Deleted image record from database successfully but FAILED to delete image from server. The Image will have to be deleted manually.";
						}
					}
					$imgTnDelPath = str_replace($imageRootUrl,$imageRootPath,$imgThumbnailUrl);
					if(file_exists($imgTnDelPath)){
						unlink($imgTnDelPath);
					}
					$imgOriginalDelPath = str_replace($imageRootUrl,$imageRootPath,$imgOriginalUrl);
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
		$sql = 'UPDATE images SET occid = '.$occId.' WHERE imgid = '.$imgId;
		if($this->conn->query($sql)){
			$imgSql = 'UPDATE images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
				'SET i.tid = o.tidinterpreted WHERE i.imgid = '.$imgId;
			//echo $imgSql;
			$this->conn->query($imgSql);
		}
		else{
			$statusStr = 'ERROR: Unalbe to remap image to another occurrence record. Error msg: '.$this->conn->error;
		}
		return $statusStr;
	}

	public function getPhotographerArr(){
		if(!$this->photographerArr){
			$sql = "SELECT u.uid, CONCAT_WS(', ',u.lastname,u.firstname) AS fullname ".
				"FROM users u ORDER BY u.lastname, u.firstname ";
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$this->photographerArr[$row->uid] = $row->fullname;
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
 			$sql .= 'AND o.collid = '.$collId.' ';
 		}
 		if($identifier){
 			if(strpos($identifier,'%') !== false){
	 			$sql .= 'AND (o.occurrenceId LIKE "'.$identifier.'" OR o.catalognumber LIKE "'.$identifier.'" OR o.othercatalognumber LIKE "'.$identifier.'")';
 			}
 			else{
	 			$sql .= 'AND (o.occurrenceId = "'.$identifier.'" OR o.catalognumber = "'.$identifier.'" OR o.othercatalognumber = "'.$identifier.'")';
 			}
 		}
 		if($collector){
 			$sql .= 'AND o.recordedby LIKE "%'.$collector.'%" ';
 		}
 		if($collNumber){
 			$sql .= 'AND o.recordnumber LIKE "%'.$collNumber.'%" ';
 		}
 		$sql = 'SELECT o.occid, o.occurrenceid, o.recordedby, o.recordnumber, o.sciname, '.
 			'CONCAT_WS("; ",o.stateprovince, o.county, o.locality) AS locality '.
 			'FROM omoccurrences o WHERE '.substr($sql,4);
 		//echo $sql;
 		$rs = $this->conn->query($sql);
 		while($row = $rs->fetch_object()){
 			$occId = $row->occid;
 			$returnArr[$occId]['occurrenceid'] = $row->occurrenceid;
 			$returnArr[$occId]['sciname'] = $row->sciname;
 			$returnArr[$occId]['recordedby'] = $row->recordedby;
 			$returnArr[$occId]['recordnumber'] = $row->recordnumber;
 			$returnArr[$occId]['locality'] = $row->locality;
 		}
 		$rs->close();
 		return $returnArr;
 	}
}
?>