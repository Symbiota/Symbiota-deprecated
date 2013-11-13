<?php
class ImageShared{

	private $conn;
	private $sourceGdImg;

	private $imageRootPath = "";
	private $imageRootUrl = "";

	private $tnPixWidth = 200;
	private $webPixWidth = 1600;
	private $lgPixWidth = 3168;
	private $webFileSizeLimit = 300000;
	private $jpgCompression= 80;

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
		if(array_key_exists('imgLgWidth',$GLOBALS)){
			$this->lgPixWidth = $GLOBALS['imgLgWidth'];
		}
		if(array_key_exists('imgFileSizeLimit',$GLOBALS)){
			$this->webFileSizeLimit = $GLOBALS['imgFileSizeLimit'];
		}
 	}
 	
 	public function __destruct(){
		if($this->sourceGdImg) imagedestroy($this->sourceGdImg);
		if(!($this->conn === null)) $this->conn->close();
 	}

	public function loadImage($subPath = ''){
		$imgPath = '';
		//filepath is given if linking to an external image (no download)
		if(array_key_exists("filepath",$_REQUEST)) $imgPath = $_REQUEST["filepath"];
		if(!$imgPath){
			//Image is to be downloaded
			$userFile = basename($_FILES['userfile']['name']);
			$fileName = $this->getFileName($userFile);
			$downloadPath = $this->getDownloadPath($fileName,$subPath); 
			if(move_uploaded_file($_FILES['userfile']['tmp_name'], $downloadPath)){
				$imgPath = $downloadPath;
			}
		}
		return $imgPath;
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
 	
	private function getDownloadPath($fileName,$subPath){
		$path = $this->imageRootPath;
		if($subPath) $path .= $subPath."/";
 		if(!file_exists($path)){
 			mkdir($path, 0775);
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

	public function uploadImage($imgPath,$tid=0){
		global $paramsArr;

		if(strpos($imgPath,$this->imageRootPath) === 0){
			$imgUrl = str_replace($this->imageRootPath,$this->imageRootUrl,$imgPath);
		}
		else{
			$imgUrl = $imgPath;
		}

		$imgTnUrl = $this->createImageThumbnail($imgUrl);

		$imgWebUrl = $imgUrl;
		$imgLgUrl = "";
		if(strpos($imgUrl,"http://") === false || strpos($imgUrl,$this->imageRootUrl) !== false){
			//Is an imported image, thus resize and place
			list($width, $height) = getimagesize($imgPath?$imgPath:$imgUrl);
			$fileSize = filesize($imgPath?$imgPath:$imgUrl);
			//Create large image
			$createlargeimg = (array_key_exists('createlargeimg',$_REQUEST)&&$_REQUEST['createlargeimg']==1?true:false);
			if($createlargeimg && ($width > ($this->webPixWidth*1.2) || $fileSize > $this->webFileSizeLimit)){
				$lgWebUrlTemp = str_ireplace("_temp.jpg","_lg.jpg",$imgPath); 
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
		$status = '';
		if($imgWebUrl){
			$status = $this->databaseImage($imgWebUrl,$imgTnUrl,$imgLgUrl,$tid);
		}
		return $status;
	}
	
	public function databaseImage($imgWebUrl,$imgTnUrl,$imgLgUrl,$tid){
		global $paramsArr;
		$caption = $this->cleanInStr($_REQUEST["caption"]);
		$photographer = (array_key_exists("photographer",$_REQUEST)?$this->cleanInStr($_REQUEST["photographer"]):"");
		$photographerUid = $_REQUEST["photographeruid"];
		$sourceUrl = (array_key_exists("sourceurl",$_REQUEST)?trim($_REQUEST["sourceurl"]):"");
		$copyRight = $this->cleanInStr($_REQUEST["copyright"]);
		$owner = $this->cleanInStr($_REQUEST["owner"]);
		$locality = (array_key_exists("locality",$_REQUEST)?$this->cleanInStr($_REQUEST["locality"]):"");
		$occId = $_REQUEST["occid"];
		$notes = (array_key_exists("notes",$_REQUEST)?$this->cleanInStr($_REQUEST["notes"]):"");
		$sortSequence = $_REQUEST["sortsequence"];
		//$addToTid = (array_key_exists("addtoparent",$_REQUEST)?$this->parentTid:0);
		//if(array_key_exists("addtotid",$_REQUEST)){
			//$addToTid = $_REQUEST["addtotid"];
		//}
		$sql = 'INSERT INTO images (tid, url, thumbnailurl, originalurl, photographer, photographeruid, caption, '.
			'owner, sourceurl, copyright, locality, occid, notes, username, sortsequence) '.
			'VALUES ('.($tid?$tid:'NULL').',"'.$imgWebUrl.'",'.($imgTnUrl?'"'.$imgTnUrl.'"':'NULL').','.($imgLgUrl?'"'.$imgLgUrl.'"':'NULL').','.
			($photographer?'"'.$this->cleanInStr($photographer).'"':'NULL').','.
			($photographerUid?$photographerUid:'NULL').',"'.
			$this->cleanInStr($caption).'","'.$this->cleanInStr($owner).'","'.
			$sourceUrl.'","'.$this->cleanInStr($copyRight).'","'.
			$this->cleanInStr($locality).'",'.
			($occId?$occId:'NULL').',"'.$this->cleanInStr($notes).'","'.
			$paramsArr['un'].'",'.($sortSequence?$this->cleanInStr($sortSequence):'50').')';
		//echo $sql;
		$status = "";
		if(!$this->conn->query($sql)){
			$status = "loadImageData: ".$this->conn->error."<br/>SQL: ".$sql;
		}
		return $status;
	} 

    /**
     * Insert a record into the image table.
     * @author Paul J. Morris
     *
     * @return an empty string on success, otherwise a string containing an error message.
     */
	public function databaseImageRecord($imgWebUrl,$imgTnUrl,$imgLgUrl,$tid,$caption,$phototrapher,$photographerUid,$sourceUrl,$copyright,$owner,$locality,$occid,$notes,$sortSequence,$imagetype,$anatomy){
		$status = "";
		$sql = 'INSERT INTO images (tid, url, thumbnailurl, originalurl, photographer, photographeruid, caption, '.
			'owner, sourceurl, copyright, locality, occid, notes, username, sortsequence, imagetype, anatomy) '.
			'VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)';
        if ($statement = $this->conn->prepare($sql)) {
           $statement->bind_param("issssisssssississ",$tid,$imgWebUrl,$imgTnUrl,$imgLgUrl,$photographer,$photographerUid,$caption,$owner,$sourceUrl,$copyright,$locality,$occid,$notes,$username,$sortSequence,$imagetype,$anatomy);

           $statement->execute();
           $rows = $statement->affected_rows;
           if ($rows!==1) {
               $status = $statement->error;
           }
           $statement->close();
        } else {
            $this->error = mysqli_error($this->conn);
            // Likely case for error conditions if schema changes affect field names
            // or if updates to field list produce incorrect sql.
            $status = $this->error;
            echo $status;
        }
		if($status!=""){
			$status = "loadImageData: $status<br/>SQL: ".$sql;
		}
		return $status;
	} 

    /**
     * Update an existing record into the image table.
     * @author Paul J. Morris
     *
     * @return an empty string on success, otherwise a string containing an error message.
     */
    public function updateImageRecord($imgid,$imgWebUrl,$imgTnUrl,$imgLgUrl,$tid,$caption,$phototrapher,$photographerUid,$sourceUrl,$copyright,$owner,$locality,$occid,$notes,$sortSequence,$imagetype,$anatomy){
        $status = "";
        $sql = 'update images set tid=?, url=?, thumbnailurl=?, originalurl=?, photographer=?, photographeruid=?, caption=?, '.
            'owner=?, sourceurl=?, copyright=?, locality=?, occid=?, notes=?, username=?, sortsequence=?, imagetype=?, anatomy=? '.
            'where imgid = ? ';
        if ($statement = $this->conn->prepare($sql)) {
           $statement->bind_param("issssisssssississi",$tid,$imgWebUrl,$imgTnUrl,$imgLgUrl,$photographer,$photographerUid,$caption,$owner,$sourceUrl,$copyright,$locality,$occid,$notes,$username,$sortSequence,$imagetype,$anatomy,$imgid);

           $statement->execute();
           $rows = $statement->affected_rows;
           if ($rows!==1) {
               $status = $statement->error;
           }
           $statement->close();
        } else {
            $this->error = mysqli_error($this->conn);
            // Likely case for error conditions if schema changes affect field names
            // or if updates to field list produce incorrect sql.
            $status = $this->error;
            echo $status;
        }
        if($status!=""){
            $status = "loadImageData: $status<br/>SQL: ".$sql;
        }
        return $status;
    }


    /**
     * Given a sourceURI, return the first imgid if at least one images record exists with
     * that sourceURI.  
     * @author Paul J. Morris
     * @param sourceUrl the images.sourceurl to query for.
     * @returns an empty string if no images records have the provided sourceURL, otherwise, 
     * the images.imgid for the first encountered images record with that sourceURL.
     */
    public function getImgIDForSourceURL($sourceUrl) { 
        $result = "";
        $sql = "select imgid from images where sourceurl = ? order by imgid limit 1 ";
        if ($statement = $this->conn->prepare($sql)) {
           $statement->bind_param("s",$sourceUrl);
           $statement->execute();
           $statement->bind_result($result);
           $statement->fetch();
           $statement->close();
        }
        return $result;
    }

	public function createImageThumbnail($imgUrl){
		$newThumbnailUrl = "";
		if($imgUrl){
			$imgPath = "";
			$newFullPath = "";
			if(strpos($imgUrl,"http://") === 0 && strpos($imgUrl,$this->imageRootUrl) === false){
				//External image being mapped to portal, 
				$imgPath = $imgUrl;
				//Set path fragment
				$pathFrag = 'thumbnails/';
				if(!is_dir($this->imageRootPath.$pathFrag)){
					if(!mkdir($this->imageRootPath.$pathFrag, 0775)) return false;
				}
				$ydStr = date('Ym').'/';
				if(!file_exists($this->imageRootPath.$pathFrag.$ydStr)){
		 			mkdir($this->imageRootPath.$pathFrag.$ydStr, 0775);
		 		}
				if(file_exists($this->imageRootPath.$pathFrag.$ydStr)) $pathFrag .= $ydStr;
		 		//Get file name and set full path
				$fileName = str_ireplace(".jpg","_tn.jpg",substr($imgUrl,strrpos($imgUrl,"/")));
				$newFullPath = $this->imageRootPath.$pathFrag.$fileName;
				//While file already exists of that name, change name
				$cnt = 1;
				$fileNameBase = str_ireplace("_tn.jpg","",$fileName);
				while(file_exists($newFullPath)){
					$fileName = $fileNameBase."_tn".$cnt.".jpg";
					$newFullPath = $this->imageRootPath.$pathFrag.$fileName;
					$cnt++; 
				}
				$newThumbnailUrl = $this->imageRootUrl.$pathFrag.$fileName;
			}
			elseif(strpos($imgUrl,$this->imageRootUrl) === 0){
				//Is internally stored image
				$imgPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgUrl);
				$newThumbnailUrl = str_ireplace("_temp.jpg","_tn.jpg",$imgUrl);
				$newFullPath = str_replace($this->imageRootUrl,$this->imageRootPath,$newThumbnailUrl);
			}
			if(!$newThumbnailUrl) return false;
			if(!$this->createNewImage($imgPath,$newFullPath,$this->tnPixWidth,70)){
				return false;
			}
		}
		return $newThumbnailUrl;
	}

	public function createNewImage($sourcePath, $targetPath, $targetWidth, $qualityRating = 0){
		global $useImageMagick;
		$status = false;
		
		if(!$qualityRating) $qualityRating = $this->jpgCompression;
		
		list($sourceWidth, $sourceHeight) = getimagesize($sourcePath);
        $newWidth = $targetWidth;
        $newHeight = round($sourceHeight*($targetWidth/$sourceWidth));
        if($newHeight > $targetWidth*1.2){
        	$newHeight = $targetWidth;
        	$newWidth = round($sourceWidth*($targetWidth/$sourceHeight));
        }
		
        if($useImageMagick) {
			// Use ImageMagick to resize images 
			$status = $this->createNewImageImagick($sourcePath,$targetPath,$newWidth,$qualityRating);
		} 
		elseif(extension_loaded('gd') && function_exists('gd_info')) {
			// GD is installed and working 
			$status = $this->createNewImageGD($sourcePath,$targetPath,$newWidth,$newHeight,$sourceWidth,$sourceHeight,$qualityRating);
		}
		else{
			// Neither ImageMagick nor GD are installed 
			$this->errArr[] = 'No appropriate image handler for image conversions';
		}
		return $status;
	}
	
	private function createNewImageImagick($sourceImg,$targetPath,$newWidth,$qualityRating = 0){
		$status = false;
		$ct;
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

	private function createNewImageGD($sourcePath, $targetPath, $newWidth, $newHeight, $sourceWidth, $sourceHeight, $qualityRating = 0){
		$status = false;
		
	   	if(!$this->sourceGdImg){
	   		$this->sourceGdImg = imagecreatefromjpeg($sourcePath);
	   	}
		
		ini_set('memory_limit','512M');
		$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
		//imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth,$newHeight,$sourceWidth,$sourceHeight);
		imagecopyresized($tmpImg,$this->sourceGdImg,0,0,0,0,$newWidth,$newHeight,$sourceWidth,$sourceHeight);

		if($qualityRating){
			$status = imagejpeg($tmpImg, $targetPath, $qualityRating);
		}
		else{
			$status = imagejpeg($tmpImg, $targetPath);
		}
		
		if(!$status){
			if($this->logErrFH) fwrite($this->logErrFH, "\tError: Unable to resize and write file: ".$targetPath."\n");
			echo "<li style='margin-left:20px;'><b>Error:</b> Unable to resize and write file: $targetPath</li>\n";
		}
		
		imagedestroy($tmpImg);
		return $status;
	}
	
	//Getter and setter
	public function getImageRootPath(){
		return $this->imageRootPath;
	}

	public function getImageRootUrl(){
		return $this->imageRootUrl;
	}

	//Misc functions
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	private function urlExists($url) {
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

