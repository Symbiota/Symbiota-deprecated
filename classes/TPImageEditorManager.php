<?php
/* 
 * Rebuilt 7 Sept 2010
 * @author  E. Gilbert: egbot@asu.edu
*/

include_once("TPEditorManager.php");

class TPImageEditorManager extends TPEditorManager{

	private $imageRootPath = "";
	private $imageRootUrl = "";

	private $tnPixWidth = 200;
	private $webPixWidth = 1300;
	private $lgPixWidth = 3168;
	private $webFileSizeLimit = 300000;
	
 	public function __construct(){
 		parent::__construct();
		set_time_limit(120);
		ini_set("max_input_time",120);
 		$this->imageRootPath = $GLOBALS["imageRootPath"];
		if(substr($this->imageRootPath,-1) != "/") $this->imageRootPath .= "/";  
		$this->imageRootUrl = $GLOBALS["imageRootUrl"];
		if(substr($this->imageRootUrl,-1) != "/") $this->imageRootUrl .= "/";
 	}
 	
 	public function __destruct(){
 		parent::__destruct();
 	}
 	
	public function getImages(){
		$imageArr = Array();
		$sql = "SELECT DISTINCT ti.imgid, ti.url, ti.thumbnailurl, ti.originalurl, ti.photographer, ti.photographeruid, ".
			"IFNULL(ti.photographer,CONCAT_WS(' ',u.firstname,u.lastname)) AS photographerdisplay, ti.caption, ti.owner, ".
			"ti.locality, ti.occid, ti.notes, ti.sortsequence, ti.sourceurl, ti.copyright ".
			"FROM ((images ti INNER JOIN taxstatus ts ON ti.tid = ts.tid) ".
			"LEFT JOIN users u ON ti.photographeruid = u.uid) ".
			"INNER JOIN taxa t ON ts.tidaccepted = t.TID ".
			"WHERE (ts.taxauthid = 1) AND (t.tid = ".$this->tid.") ".
			"ORDER BY ti.sortsequence";
		//echo $sql;
		$result = $this->taxonCon->query($sql);
		$imgCnt = 0;
		while($row = $result->fetch_object()){
			$imageArr[$imgCnt]["imgid"] = $row->imgid;
			$imageArr[$imgCnt]["url"] = $row->url;
			$imageArr[$imgCnt]["thumbnailurl"] = $row->thumbnailurl;
			$imageArr[$imgCnt]["originalurl"] = $row->originalurl;
			$imageArr[$imgCnt]["photographer"] = $row->photographer;
			$imageArr[$imgCnt]["photographeruid"] = $row->photographeruid;
			$imageArr[$imgCnt]["photographerdisplay"] = $row->photographerdisplay;
			$imageArr[$imgCnt]["caption"] = $row->caption;
			$imageArr[$imgCnt]["owner"] = $row->owner;
			$imageArr[$imgCnt]["locality"] = $row->locality;
			$imageArr[$imgCnt]["sourceurl"] = $row->sourceurl;
			$imageArr[$imgCnt]["copyright"] = $row->copyright;
			$imageArr[$imgCnt]["occid"] = $row->occid;
			$imageArr[$imgCnt]["notes"] = $row->notes;
			$imageArr[$imgCnt]["sortsequence"] = $row->sortsequence;
			$imgCnt++;
		}
		$result->close();
		return $imageArr;
	}
	
	public function echoPhotographerSelect($userId = 0){
		$sql = "SELECT u.uid, CONCAT_WS(', ',u.lastname,u.firstname) AS fullname ".
			"FROM users u ORDER BY u.lastname, u.firstname ";
		$result = $this->taxonCon->query($sql);
		while($row = $result->fetch_object()){
			echo "<option value='".$row->uid."' ".($row->uid == $userId?"SELECTED":"").">".$row->fullname."</option>\n";
		}
		$result->close();
	}

	public function editImage(){
		$searchStr = $GLOBALS["imageRootUrl"];
		if(substr($searchStr,-1) != "/") $searchStr .= "/";
		$replaceStr = $GLOBALS["imageRootPath"];
		if(substr($replaceStr,-1) != "/") $replaceStr .= "/";
		$status = "";
		$imgId = $_REQUEST["imgid"];
	 	$url = $_REQUEST["url"];
	 	$tnUrl = $_REQUEST["thumbnailurl"];
	 	$origUrl = $_REQUEST["originalurl"];
	 	if(array_key_exists("renameweburl",$_REQUEST)){
	 		$oldUrl = $_REQUEST["oldurl"];
	 		$oldName = str_replace($searchStr,$replaceStr,$oldUrl);
	 		$newName = str_replace($searchStr,$replaceStr,$url);
	 		if($url != $oldUrl){
	 			if(!rename($oldName,$newName)){
	 				$url = $oldUrl;
		 			$status .= "Web URL rename FAILED; url address unchanged";
	 			}
	 		}
		}
		if(array_key_exists("renametnurl",$_REQUEST)){
	 		$oldTnUrl = $_REQUEST["oldthumbnailurl"];
	 		$oldName = str_replace($searchStr,$replaceStr,$oldTnUrl);
	 		$newName = str_replace($searchStr,$replaceStr,$tnUrl);
	 		if($tnUrl != $oldTnUrl){
	 			if(!rename($oldName,$newName)){
	 				$tnUrl = $oldTnUrl;
		 			$status .= "Thumbnail URL rename FAILED; url address unchanged";
	 			}
	 		}
		}
		if(array_key_exists("renameorigurl",$_REQUEST)){
	 		$oldOrigUrl = $_REQUEST["oldoriginalurl"];
	 		$oldName = str_replace($searchStr,$replaceStr,$oldOrigUrl);
	 		$newName = str_replace($searchStr,$replaceStr,$origUrl);
	 		if($origUrl != $oldOrigUrl){
	 			if(!rename($oldName,$newName)){
	 				$origUrl = $oldOrigUrl;
		 			$status .= "Thumbnail URL rename FAILED; url address unchanged";
	 			}
	 		}
		}
	 	$caption = $this->cleanStr($_REQUEST["caption"]);
		$photographer = $this->cleanStr($_REQUEST["photographer"]);
		$photographerUid = $_REQUEST["photographeruid"];
		$owner = $this->cleanStr($_REQUEST["owner"]);
		$locality = $this->cleanStr($_REQUEST["locality"]);
		$occId = $_REQUEST["occid"];
		$notes = $this->cleanStr($_REQUEST["notes"]);
		$sourceUrl = $this->cleanStr($_REQUEST["sourceurl"]);
		$copyRight = $this->cleanStr($_REQUEST["copyright"]);
		$sortSequence = (array_key_exists("sortsequence",$_REQUEST)?$_REQUEST["sortsequence"]:0);
		$addToTid = (array_key_exists("addtoparent",$_REQUEST)?$this->parentTid:0);
		if(array_key_exists("addtotid",$_REQUEST)){
			$addToTid = $_REQUEST["addtotid"];
		}
		
		$sql = "UPDATE images SET caption = \"".$caption."\", url = \"".$url."\", thumbnailurl = \"".$tnUrl."\", ".
			"originalurl = \"".$origUrl."\", photographer = ".($photographer?"\"".$photographer."\"":"NULL").", ".
			"photographeruid = ".($photographerUid?$photographerUid:"NULL").", owner = \"".$owner."\", sourceurl = \"".$sourceUrl."\", ".
			"copyright = \"".$copyRight."\", locality = \"".$locality."\", occid = ".($occId?$occId:"NULL").", ".
			"notes = \"".$notes."\", sortsequence = ".$sortSequence." ".
			" WHERE imgid = ".$imgId;
		//echo $sql;
		if($this->taxonCon->query($sql)){
			$this->setPrimaryImageSort($this->tid);
			if($addToTid){
				$sql = "INSERT INTO images (tid, url, thumbnailurl, originalurl, photographer, photographeruid, caption, ".
					"owner, sourceurl, copyright, locality, occid, notes) ".
					"VALUES (".$addToTid.",\"".$url."\",\"".$tnUrl."\",\"".$origUrl."\",".
					($photographer?"\"".$photographer."\"":"NULL").",".$photographerUid.",\"".$caption."\",\"".
					$owner."\",\"".$sourceUrl."\",\"".$copyRight."\",\"".$locality."\",".($occId?$occId:"NULL").",\"".$notes."\")";
				//echo $sql;
				if($this->taxonCon->query($sql)){
					$this->setPrimaryImageSort($addToTid);
				}
				else{
					$status = "unable to upload image for related taxon";
					//$status = "Error:editImage:loading the parent data: ".$this->taxonCon->error."<br/>SQL: ".$sql;
				}
			}
		}
		else{
			$status = "Error:editImage: ".$this->taxonCon->error."\nSQL: ".$sql;
		}
		return $status;
	}
	
	public function changeTaxon($imgId,$targetTid,$sourceTid){
		$sql = "UPDATE images SET tid = $targetTid, sortsequence = 50 WHERE imgid = $imgId";
		if($this->taxonCon->query($sql)){
			//$sql2 = "DELETE FROM images WHERE tid = $sourceTid AND url = '".."'";
			//$this->taxonCon->query($sql2);
		}
		$this->setPrimaryImageSort($this->tid);
	}
	
	public function imageExists($url, $targetTid){
		if($url && $targetTid){
			$sql = "SELECT ti.imgid FROM images ti WHERE ti.tid = ".$targetTid." AND ti.url = '".$url."'";
			$result = $this->taxonCon->query($sql);
			if($result->num_rows > 0) return true;
		}
		return false;
	}
	
	public function editImageSort($imgSortEdits){
		$status = "";
		foreach($imgSortEdits as $editKey => $editValue){
			$sql = "UPDATE images SET sortsequence = ".$editValue." WHERE imgid = ".$editKey.";";
			//echo $sql;
			if(!$this->taxonCon->query($sql)){
				$status .= $this->taxonCon->error."\nSQL: ".$sql."; ";
			}
		}
		$this->setPrimaryImageSort($this->tid);
		if($status) $status = "with editImageSort method: ".$status;
		return $status;
	}
	
	public function deleteImage($imgIdDel, $removeImg){
		$imgUrl = ""; $imgThumbnailUrl = ""; $imgOriginalUrl = "";
		$sqlQuery = "SELECT ti.url, ti.thumbnailurl, ti.originalurl FROM images ti WHERE ti.imgid = ".$imgIdDel;
		$result = $this->taxonCon->Query($sqlQuery);
		if($row = $result->fetch_object()){
			$imgUrl = $row->url;
			$imgThumbnailUrl = $row->thumbnailurl;
			$imgOriginalUrl = $row->originalurl;
		}
		$result->close();
				
		$sql = "DELETE FROM images WHERE imgid = ".$imgIdDel;
		//echo $sql;
		$status = "";
		if($this->taxonCon->query($sql)){
			if($removeImg){
				//Remove images only if there are no other references to the image
				$sql = "SELECT imgid FROM images WHERE url = '".$imgUrl."'";
				$rs = $this->taxonCon->query($sql);
				if(!$rs->num_rows){
					//Delete image from server
					$imgDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgUrl);
					if(file_exists($imgDelPath)){
						if(!unlink($imgDelPath)){
							$status = "Deleted records from database successfully but FAILED to delete image from server. The Image will have to be deleted manually.";
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
			$status = "deleteImage: ".$this->taxonCon->error."\nSQL: ".$sql;
		}
		$this->setPrimaryImageSort($this->tid);
		return $status;
	}
	
	public function loadImageData(){
		global $paramsArr;
		$imgUrl = (array_key_exists("filepath",$_REQUEST)?$_REQUEST["filepath"]:"");
		$imgPath = "";
		if(!$imgUrl){
			$imgPath = $this->loadImage();
			$imgUrl = str_replace($this->imageRootPath,$this->imageRootUrl,$imgPath);
		}
		if(!$imgUrl) return;
		$caption = $this->cleanStr($_REQUEST["caption"]);
		$photographer = (array_key_exists("photographer",$_REQUEST)?$this->cleanStr($_REQUEST["photographer"]):"");
		$photographerUid = $_REQUEST["photographeruid"];
		$sourceUrl = (array_key_exists("sourceurl",$_REQUEST)?trim($_REQUEST["sourceurl"]):"");
		$copyRight = $this->cleanStr($_REQUEST["copyright"]);
		$owner = $this->cleanStr($_REQUEST["owner"]);
		$locality = (array_key_exists("locality",$_REQUEST)?$this->cleanStr($_REQUEST["locality"]):"");
		$occId = $_REQUEST["occid"];
		$notes = (array_key_exists("notes",$_REQUEST)?$this->cleanStr($_REQUEST["notes"]):"");
		$sortSequence = $_REQUEST["sortsequence"];
		$addToTid = (array_key_exists("addtoparent",$_REQUEST)?$this->parentTid:0);
		if(array_key_exists("addtotid",$_REQUEST)){
			$addToTid = $_REQUEST["addtotid"];
		}
		
		$imgTnUrl = $this->createImageThumbnail($imgUrl);

		$imgWebUrl = $imgUrl;
		$imgLgUrl = "";
		if(strpos($imgUrl,"http://") === false || strpos($imgUrl,$this->imageRootUrl) !== false){
			//Create Large Image
			list($width, $height) = getimagesize($imgPath?$imgPath:$imgUrl);
			$fileSize = filesize($imgPath?$imgPath:$imgUrl);
			if($width > ($this->webPixWidth*1.2) || $fileSize > $this->webFileSizeLimit){
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
			$sql = "INSERT INTO images (tid, url, thumbnailurl, originalurl, photographer, photographeruid, caption, ".
				"owner, sourceurl, copyright, locality, occid, notes, sortsequence) ".
				"VALUES (".$this->tid.",\"".$imgWebUrl."\",".($imgTnUrl?"\"".$imgTnUrl."\"":"NULL").",".($imgLgUrl?"\"".$imgLgUrl."\"":"NULL").",".
				($photographer?"\"".$photographer."\"":"NULL").",".($photographerUid?$photographerUid:"NULL").",\"".
				$caption."\",\"".$owner."\",\"".$sourceUrl."\",\"".$copyRight."\",\"".$locality."\",".
				($occId?$occId:"NULL").",\"".$notes."\",".($sortSequence?$sortSequence:"50").")";
			//echo $sql;
			$status = "";
			if($this->taxonCon->query($sql)){
				$this->setPrimaryImageSort($this->tid);
				if($addToTid){
					$sql = "INSERT INTO images (tid, url, thumbnailurl, originalurl, photographer, photographeruid, caption, ".
						"owner, sourceurl, copyright, locality, occid, notes) ". 
						"VALUES (".$addToTid.",\"".$imgWebUrl."\",".($imgTnUrl?"\"".$imgTnUrl."\"":"NULL").",".($imgLgUrl?"\"".$imgLgUrl."\"":"NULL").",".
						($photographer?"\"".$photographer."\"":"NULL").",".($photographerUid?$photographerUid:"NULL").",\"".
						$imageType."\",\"".$caption."\",\"".$owner."\",\"".$sourceUrl."\",\"".$copyRight."\",\"".$locality."\",".
						($occId?$occId:"NULL").",\"".$notes."\")";
					//echo $sql;
					if($this->taxonCon->query($sql)){
						$this->setPrimaryImageSort($addToTid);
					}
					else{
						$status = "Error: unable to upload image for related taxon";
						//$status = "Error:loadImageData:loading the parent data: ".$this->taxonCon->error."<br/>SQL: ".$sql;
					}
				}
			}
			else{
				$status = "loadImageData: ".$this->taxonCon->error."<br/>SQL: ".$sql;
			}
		}
		return $status;
	}
	
	private function loadImage(){
	 	$userFile = basename($_FILES['userfile']['name']);
		$fileName = $this->getFileName($userFile);
	 	$downloadPath = $this->getDownloadPath($fileName); 
	 	if(move_uploaded_file($_FILES['userfile']['tmp_name'], $downloadPath)){
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
		if(substr($this->imageRootPath,-1,1) != "/") $this->imageRootPath .= "/";
		$path = $this->imageRootPath.$this->family."/";
 		if(!file_exists($this->imageRootPath.$this->family)){
 			mkdir($this->imageRootPath.$this->family, 0775);
 		}
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
	
	private function createNewImage($sourceImg,$targetPath,$targetWidth,$qualityRating = 60){
        $successStatus = false;
		list($sourceWidth, $sourceHeight, $imageType) = getimagesize($sourceImg);
        $newWidth = $targetWidth;
        $newHeight = round($sourceHeight*($targetWidth/$sourceWidth));
        if($newHeight > $targetWidth*1.2){
        	$newHeight = $targetWidth;
        	$newWidth = round($sourceWidth*($targetWidth/$sourceHeight));
        }
        
	    switch ($imageType){
	        case 1: 
	        	$newImg = imagecreatefromgif($sourceImg);
	        	break;
	        case 3: 
	        	$newImg = imagecreatefrompng($sourceImg);
	        	break;
	        default: 
	        	$newImg = imagecreatefromjpeg($sourceImg);  
	        	break;
	    }
        
    	$tmpImg = imagecreatetruecolor($newWidth,$newHeight);

	    /* Check if this image is PNG or GIF to preserve its transparency */
	    if(($imageType == 1) || ($imageType==3)){
	        imagealphablending($tmpImg, false);
	        imagesavealpha($tmpImg,true);
	        $transparent = imagecolorallocatealpha($tmpImg, 255, 255, 255, 127);
	        imagefilledrectangle($tmpImg, 0, 0, $newWidth, $newHeight, $transparent);
	    }
		imagecopyresampled($tmpImg,$newImg,0,0,0,0,$newWidth, $newHeight,$sourceWidth,$sourceHeight);

		switch ($imageType){
	        case 1: 
	        	$successStatus = imagegif($tmpImg,$targetPath);
	        	break;
	        case 2: 
	        	$successStatus = imagejpeg($tmpImg, $targetPath, $qualityRating);
	        	break; // best quality
	        case 3: 
	        	$successStatus = imagepng($tmpImg, $targetPath, 0);
	        	break; // no compression
	    }
	    imagedestroy($tmpImg);
	    return $successStatus;
	}

	private function setPrimaryImageSort($subjectTid){
		$sql = "UPDATE images ti INNER JOIN ".
			"(SELECT ti.imgid FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted ".
			"INNER JOIN images ti ON ts2.tid = ti.tid WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) ".
			"AND (ts1.tidaccepted=".$subjectTid.") ORDER BY ti.SortSequence LIMIT 1) innertab ON ti.imgid = innertab.imgid ".
			"SET ti.SortSequence = 1";
		//echo $sql2;
		$this->taxonCon->query($sql);
	}
	
 	private function url_exists($url) {
	    if(!strstr($url, "http://")){
	        $url = "http://".$url;
	    }

	    $fp = @fsockopen($url, 80);

    	if($fp === false){
	        return false;   
    	}
    	return true;
    	
 		// Version 4.x supported
	    $handle   = curl_init($url);
	    if (false === $handle)
	    {
	        return false;
	    }
	    curl_setopt($handle, CURLOPT_HEADER, false);
	    curl_setopt($handle, CURLOPT_FAILONERROR, true);  // this works
	    curl_setopt($handle, CURLOPT_HTTPHEADER, Array("User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15") ); // request as if Firefox   
	    curl_setopt($handle, CURLOPT_NOBODY, true);
	    curl_setopt($handle, CURLOPT_RETURNTRANSFER, false);
	    $connectable = curl_exec($handle);
	    curl_close($handle);  
	    return $connectable;
	}	
 }
?>

