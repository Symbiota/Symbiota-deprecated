<?php
include_once($serverRoot.'/classes/UuidFactory.php');

class ImageShared{

	private $conn;
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

	private $mapLargeImg = true;
	
	//Image metadata
	private $caption;
	private $photographer;
	private $photographerUid;
	private $sourceUrl;
	private $copyright;
	private $owner;
	private $locality;
	private $occid;
	private $tid;
	private $notes;
	private $sortSeq;
	
	private $activeImgId = 0;
	
    private $errArr = array();
	
    // No implementation in Symbiota
    public $documentGuid;  // Guid for transfer document containing image record.
    public $documentDate;  // Creation date for transfer document containing image record.       

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
 	
 	public function reset(){
 		if($this->sourceGdImg) imagedestroy($this->sourceGdImg);
 		$this->sourceGdImg = null;
 		
 		$this->sourcePath = '';
		$this->imgName = '';
		$this->imgExt = '';
	
		$this->sourceWidth = 0;
		$this->sourceHeight = 0;

		//Image metadata
		$this->caption = '';
		$this->photographer = '';
		$this->photographerUid = '';
		$this->sourceUrl = '';
		$this->copyright = '';
		$this->owner = '';
		$this->locality = '';
		$this->occid = '';
		$this->tid = '';
		$this->notes = '';
		$this->sortSeq = '';
	
		$this->activeImgId = 0;
	
		unset($this->errArr);
		$this->errArr = array();
 		
 	}

	public function uploadImage(){
		if($this->targetPath){
			if(file_exists($this->targetPath)){
				$imgFile = basename($_FILES['imgfile']['name']);
				$fileName = $this->cleanFileName($imgFile,$this->targetPath);
				if(move_uploaded_file($_FILES['imgfile']['tmp_name'], $this->targetPath.$fileName.$this->imgExt)){
					$this->sourcePath = $this->targetPath.$fileName.$this->imgExt;
					$this->imgName = $fileName;
					$this->testOrientation();
					return true;
				}
				else{
					$this->errArr[] = 'ERROR: unable to move image to target ('.$this->targetPath.$fileName.$this->imgExt.')';
				}
			}
			else{
				$this->errArr[] = 'ERROR: Target path does not exist in uploadImage method ('.$this->targetPath.')';
				//trigger_error('Path does not exist in uploadImage method',E_USER_ERROR);
			}
		}
		else{
			$this->errArr[] = 'ERROR: Path NULL in uploadImage method';
			//trigger_error('Path NULL in uploadImage method',E_USER_ERROR);
		}
		return false;
	}

	public function copyImageFromUrl($sourceUri){
		//Returns full path
		if(!$sourceUri){
			$this->errArr[] = 'ERROR: Image source uri NULL in copyImageFromUrl method';
			//trigger_error('Image source uri NULL in copyImageFromUrl method',E_USER_ERROR);
			return false;
		}
		if(!$this->urlExists($sourceUri)){
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
			$this->testOrientation();
			return true;
		}
		$this->errArr[] = 'ERROR: Unable to copy image to target ('.$this->targetPath.$fileName.$this->imgExt.')';
		return false;
	}

	public function parseUrl($url){
		if($GLOBALS['imageDomain'] && substr($url,0,1) == '/'){
			$url = $GLOBALS['imageDomain'].$url;
    	}
		$this->sourcePath = $url;
    	$this->imgName = $this->cleanFileName($url);
		$this->testOrientation();
	}

	public function cleanFileName($fName){
		if(strtolower(substr($fName,0,7)) == 'http://' || strtolower(substr($fName,0,8)) == 'https://'){
			//Image is URL that will be imported
			if($pos = strrpos($fName,'/')){
				$fName = substr($fName,$pos+1);
			}
		}
		//Parse extension
		if($p = strrpos($fName,".")){
			$this->imgExt = strtolower(substr($fName,$p));
			$fName = substr($fName,0,$p);
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
		if(strlen($fName) > 30) {
			$fName = substr($fName,0,30);
		}
		
		/*
		if($targetPath){
			//Check and see if file already exists, if so, rename filename until it has a unique name
			$tempFileName = $fName;
			$cnt = 0;
			while(file_exists($targetPath.$tempFileName.$ext)){
				$tempFileName = $fName.'_'.$cnt;
				$cnt++;
			}
			if($cnt) $fName = $tempFileName;
		}
		*/
		$fName .= '_'.time();
		//Returns file name without extension
		return $fName;
 	}
 	
	public function setTargetPath($subPath = ''){
		$path = $this->imageRootPath;
		$url = $this->imageRootUrl;
		if(!$path){
			$this->errArr[] = 'Path empty in setTargetPath method';
			trigger_error('Path empty in setTargetPath method',E_USER_ERROR);
			return false;
		}
		//if(!$url){
			//$this->errArr[] = 'URL empty in setTargetPath method';
			//trigger_error('URL empty in setTargetPath method',E_USER_ERROR);
			//return false;
		//}
		if($subPath){
			if(substr($subPath,-1) != "/") $subPath .= "/";  
		}
		else{
			$subPath = 'misc/';
		}
		$subPath .= date('Ym').'/';
		$path .= $subPath;
		$url .= $subPath;
		if(!file_exists($path)){
			if(!mkdir( $path, 0777, true )){
				$this->errArr[] = 'Unable to create directory: '.$path;
				//trigger_error('Unable to create directory: '.$path,E_USER_ERROR);
				return false;
			}
		}
		$this->targetPath = $path;
		$this->urlBase = $url;
		return true;
	}
	
	public function processImage($tid=0){
		global $paramsArr;

		if(!$this->imgName){
			$this->errArr[] = 'Image file name null in processImage fucntion';
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
		$fileSize = 0;
		if(substr($this->sourcePath,0,7)=='http://' || substr($this->sourcePath,0,8)=='https://') { 
			$x = array_change_key_case(get_headers($this->sourcePath, 1),CASE_LOWER); 
			if ( strcasecmp($x[0], 'HTTP/1.1 200 OK') != 0 ) { 
				$fileSize = $x['content-length'][1]; 
			}
 			else { 
 				$fileSize = $x['content-length']; 
 			}
		} 
		else { 
			$fileSize = @filesize($this->sourcePath);
		}

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
			$status = $this->databaseImage($imgWebUrl,$imgTnUrl,$imgLgUrl,$tid);
		}
		return $status;
	}

	public function createNewImage($subExt, $targetWidth, $qualityRating = 0){
		global $useImageMagick;
		$status = false;
		if($this->sourcePath && $this->urlExists($this->sourcePath)){
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
		return $status;
	}
	
	public function databaseImage($imgWebUrl,$imgTnUrl,$imgLgUrl,$tid){
		global $paramsArr;
		$status = true;
		if($imgWebUrl){
			$urlBase = $this->getUrlBase();
			if(strtolower(substr($imgWebUrl,0,7)) != 'http://' && strtolower(substr($imgWebUrl,0,8)) != 'https://'){ 
				$imgWebUrl = $urlBase.$imgWebUrl;
			}
			if($imgTnUrl && strtolower(substr($imgTnUrl,0,7)) != 'http://' && strtolower(substr($imgTnUrl,0,8)) != 'https://'){
				$imgTnUrl = $urlBase.$imgTnUrl;
			}
			if($imgLgUrl && strtolower(substr($imgLgUrl,0,7)) != 'http://' && strtolower(substr($imgLgUrl,0,8)) != 'https://'){
				$imgLgUrl = $urlBase.$imgLgUrl;
			}
			
			//If is an occurrence image, get tid from occurrence  
			if(!$tid && $this->occid){
				$sql1 = 'SELECT tidinterpreted FROM omoccurrences WHERE tidinterpreted IS NOT NULL AND occid = '.$this->occid;
				$rs1 = $this->conn->query($sql1);
				if($r1 = $rs1->fetch_object()){
					$tid = $r1->tidinterpreted;
				}
				$rs1->free();
			}
			
			//Load record
			$sql = 'INSERT INTO images (tid, url, thumbnailurl, originalurl, photographer, photographeruid, caption, '.
				'owner, sourceurl, copyright, locality, occid, notes, username, sortsequence) '.
				'VALUES ('.($tid?$tid:'NULL').',"'.$imgWebUrl.'",'.
				($imgTnUrl?'"'.$imgTnUrl.'"':'NULL').','.
				($imgLgUrl?'"'.$imgLgUrl.'"':'NULL').','.
				($this->photographer?'"'.$this->photographer.'"':'NULL').','.
				($this->photographerUid?$this->photographerUid:'NULL').','.
				($this->caption?'"'.$this->caption.'"':'NULL').','.
				($this->owner?'"'.$this->owner.'"':'NULL').','.
				($this->sourceUrl?'"'.$this->sourceUrl.'"':'NULL').','.
				($this->copyright?'"'.$this->copyright.'"':'NULL').','.
				($this->locality?'"'.$this->locality.'"':'NULL').','.
				($this->occid?$this->occid:'NULL').','.
				($this->notes?'"'.$this->notes.'"':'NULL').',"'.
				$this->cleanInStr($paramsArr['un']).'",'.
				($this->sortSeq?$this->sortSeq:'50').')';
			//echo $sql; exit;
			if($this->conn->query($sql)){
				//Create and insert Symbiota GUID for image(UUID)
				$guid = UuidFactory::getUuidV4();
				$this->activeImgId = $this->conn->insert_id;
				if(!$this->conn->query('INSERT INTO guidimages(guid,imgid) VALUES("'.$guid.'",'.$this->activeImgId.')')) {
					$this->errArr[] = ' (Warning: Symbiota GUID mapping failed)';
				}
			}
			else{
				$this->errArr[] = 'ERROR loading data: '.$this->conn->error;
				$status = false;
			}
		}
		return $status;
	}
	
	public function deleteImage($imgIdDel, $removeImg){
		$imgUrl = ""; $imgThumbnailUrl = ""; $imgOriginalUrl = ""; $occid = 0;
		$sqlQuery = "SELECT * FROM images WHERE (imgid = ".$imgIdDel.')';
		$rs = $this->conn->query($sqlQuery);
		if($r = $rs->fetch_object()){
			$imgUrl = $r->url;
			$imgThumbnailUrl = $r->thumbnailurl;
			$imgOriginalUrl = $r->originalurl;
			$this->tid = $r->tid;
			$occid = $r->occid;
			//Archive image 
			$imgArr = array();
			$imgObj = '';
			foreach($r as $k => $v){
				if($v) $imgArr[$k] = $v;
				$imgObj .= '"'.$k.'":"'.$this->cleanInStr($v).'",';
			}
			$imgObj = json_encode($imgArr);
			$sqlArchive = 'UPDATE guidimages '.
			"SET archivestatus = 1, archiveobj = '{".trim($imgObj,',')."}' ".
			'WHERE (imgid = '.$imgIdDel.')';
			$this->conn->query($sqlArchive);
		}
		$rs->close();

		if($occid){
			//Remove any OCR text blocks linked to the image
			$this->conn->query('DELETE FROM specprocessorrawlabels WHERE (imgid = '.$imgIdDel.')');
		}
		//Remove image tags
		$this->conn->query('DELETE FROM imagetag WHERE (imgid = '.$imgIdDel.')');
				
		$sql = "DELETE FROM images WHERE (imgid = ".$imgIdDel.')';
		//echo $sql;
		if($this->conn->query($sql)){
			if($removeImg){
				//Search url with and without local domain name  
				$imgUrl2 = '';
				$domain = "http://";
				if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) $domain = "https://";
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
				$sql = 'SELECT imgid FROM images WHERE (url = "'.$imgUrl.'") ';
				if($imgUrl2) $sql .= 'OR (url = "'.$imgUrl2.'")';
				$rs = $this->conn->query($sql);
				if($rs->num_rows){
					$this->errArr[] = 'WARNING: Deleted records from database successfully but FAILED to delete image from server because it is being referenced by another record.';
				}
				else{
					//Delete image from server
					$imgDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgUrl);
					if(!unlink($imgDelPath)){
						$this->errArr[] = 'WARNING: Deleted records from database successfully but FAILED to delete image from server (path: '.$imgDelPath.')';
						//$status .= '<br/>Return to <a href="../taxa/admin/tpeditor.php?tid='.$tid.'&tabindex=1">Taxon Editor</a>';
					}
					
					//Delete thumbnail image
					if($imgThumbnailUrl){
						if(stripos($imgThumbnailUrl,$domain) === 0){
							$imgThumbnailUrl = substr($imgThumbnailUrl,strlen($domain));
						}				
						$imgTnDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgThumbnailUrl);
						if(file_exists($imgTnDelPath)) unlink($imgTnDelPath);
					}
					
					//Delete large version of image
					if($imgOriginalUrl){
						if(stripos($imgOriginalUrl,$domain) === 0){
							$imgOriginalUrl = substr($imgOriginalUrl,strlen($domain));
						}				
						$imgOriginalDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgOriginalUrl);
						if(file_exists($imgOriginalDelPath)) unlink($imgOriginalDelPath);
					}
				}
			}
		}
		else{
			$this->errArr[] = 'ERROR: Unable to delete image record: '.$this->conn->error;
			return false;
			//echo 'SQL: '.$sql;
		}
		return true;
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
		    //If central images are on remote server and new ones stored locally, then we need to use full domain
		    //e.g. this portal is sister portal to central portal
	    	if($GLOBALS['imageDomain']){
				$urlPrefix = "http://";
				if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) $urlPrefix = "https://";
				$urlPrefix .= $_SERVER["SERVER_NAME"];
				if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $urlPrefix .= ':'.$_SERVER["SERVER_PORT"];
	    		if(substr($imgWebUrl,0,1) == '/'){
		    		$imgWebUrl = $urlPrefix.$imgWebUrl;
	    		}
	    		if(substr($imgTnUrl,0,1) == '/'){
		    		$imgTnUrl = $urlPrefix.$imgTnUrl;
	    		}
	    		if(substr($imgLgUrl,0,1) == '/'){
		    		$imgLgUrl = $urlPrefix.$imgLgUrl;
	    		}
	    	}
        	$statement->bind_param("issssisssssississ",$tid,$imgWebUrl,$imgTnUrl,$imgLgUrl,$photographer,$photographerUid,$caption,$owner,$sourceUrl,$copyright,$locality,$occid,$notes,$username,$sortSequence,$imagetype,$anatomy);

           $statement->execute();
           $rows = $statement->affected_rows;
           if ($rows!==1) {
               $status = $statement->error;
           }
           $statement->close();
        } else {
            $status = mysqli_error($this->conn);
            // Likely case for error conditions if schema changes affect field names
            // or if updates to field list produce incorrect sql.
            $this->errArr[] = $status;
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
		    //If central images are on remote server and new ones stored locally, then we need to use full domain
		    //e.g. this portal is sister portal to central portal
        	if($GLOBALS['imageDomain']){
				$urlPrefix = "http://";
				if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) $urlPrefix = "https://";
				$urlPrefix .= $_SERVER["SERVER_NAME"];
				if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $urlPrefix .= ':'.$_SERVER["SERVER_PORT"];
        		if(substr($imgWebUrl,0,1) == '/'){
		    		$imgWebUrl = $urlPrefix.$imgWebUrl;
	    		}
	    		if(substr($imgTnUrl,0,1) == '/'){
		    		$imgTnUrl = $urlPrefix.$imgTnUrl;
	    		}
	    		if(substr($imgLgUrl,0,1) == '/'){
		    		$imgLgUrl = $urlPrefix.$imgLgUrl;
	    		}
	    	}
        	
        	$statement->bind_param("issssisssssississi",$tid,$imgWebUrl,$imgTnUrl,$imgLgUrl,$photographer,$photographerUid,$caption,$owner,$sourceUrl,$copyright,$locality,$occid,$notes,$username,$sortSequence,$imagetype,$anatomy,$imgid);

           $statement->execute();
           $rows = $statement->affected_rows;
           if ($rows!==1) {
               $status = $statement->error;
           }
           $statement->close();
        } else {
            $status = mysqli_error($this->conn);
            // Likely case for error conditions if schema changes affect field names
            // or if updates to field list produce incorrect sql.
            $this->errArr[] = $status;
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
	
    public function insertImageTags($reqArr){
    	$status = true;
    	if($this->activeImgId){
			// Find any tags providing classification of the image and insert them
			$kArr = $this->getImageTagValues();
			foreach($kArr as $key => $description) { 
				if(array_key_exists("ch_$key",$reqArr)) {
					$sql = "INSERT INTO imagetag (imgid,keyvalue) VALUES (?,?) ";
					$stmt = $this->conn->stmt_init();
					$stmt->prepare($sql);
					if($stmt){ 
						$stmt->bind_param('is',$this->activeImgId,$key);
						if(!$stmt->execute()){ 
							$status = false;
							$this->errArr[] = " (Warning: Failed to add image tag [$key] for $this->activeImgId.  " . $stmt->error;
						} 
						$stmt->close();
					}
				} 
			}
    	}
		return $status;
	}
    
	private function getImageTagValues($lang='en') { 
       $returnArr = Array();
       switch ($lang) { 
          case 'en':
          default: 
           $sql = "select tagkey, description_en from imagetagkey order by sortorder";
       } 
       $stmt = $this->conn->stmt_init();
       $stmt->prepare($sql);
       if ($stmt) { 
          $stmt->bind_result($key,$desc);
          $stmt->execute();
          while ($stmt->fetch()) { 
             $returnArr[$key]=$desc;
          } 
          $stmt->close(); 
       }
       return $returnArr;
    } 
    
    //Getter and setter
	public function getImageRootPath(){
		return $this->imageRootPath;
	}

	public function getImageRootUrl(){
		return $this->imageRootUrl;
	}
	
	public function getSourcePath(){
		return $this->sourcePath;
	}

	public function getUrlBase(){
		$urlBase = $this->urlBase;
		//If central images are on remote server and new ones stored locally, then we need to use full domain
	    //e.g. this portal is sister portal to central portal
	 	if($GLOBALS['imageDomain']){
			$urlPrefix = "http://";
			if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) $urlPrefix = "https://";
			$urlPrefix .= $_SERVER["SERVER_NAME"];
			if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $urlPrefix .= ':'.$_SERVER["SERVER_PORT"];
			$urlBase = $urlPrefix.$urlBase;
    	}
		return $urlBase;
	}

	public function getImgName(){
		return $this->imgName;
	}
	
	public function getTnPixWidth(){
		return $this->tnPixWidth;
	}
	
	public function getWebPixWidth(){
		return $this->webPixWidth;
	}
	
	public function getLgPixWidth(){
		return $this->lgPixWidth;
	}
	
	public function getWebFileSizeLimit(){
		return $this->webFileSizeLimit;
	}
	
	public function setMapLargeImg($t){
		$this->mapLargeImg = $t;
	}
	
	public function setCaption($v){
		$this->caption = $this->cleanInStr($v);
	}

	public function setPhotographer($v){
		$this->photographer = $this->cleanInStr($v);
	}

	public function setPhotographerUid($v){
		if(is_numeric($v)){
			$this->photographerUid = $v;
		}
	}

	public function setSourceUrl($v){
		$this->sourceUrl = $this->cleanInStr($v);
	}

	public function setCopyright($v){
		$this->copyright = $this->cleanInStr($v);
	}

	public function setOwner($v){
		$this->owner = $this->cleanInStr($v);
	}
	
	public function setLocality($v){
		$this->locality = $this->cleanInStr($v);
	}
	
	public function setOccid($v){
		if(is_numeric($v)){
			$this->occid = $v;
		}
	}
	
	public function getTid(){
		return $this->tid;
	}
	
	public function setNotes($v){
		$this->notes = $this->cleanInStr($v);
	}
	
	public function setSortSeq($v){
		if(is_numeric($v)){
			$this->sortSeq = $v;
		}
	}
	
	public function getErrArr(){
		return $this->errArr;
	}
	
	//Misc functions
	private function testOrientation(){
		if($this->sourcePath){
			$exif = exif_read_data($this->sourcePath);
			$ort = '';
			if(isset($exif['Orientation'])) $ort = $exif['Orientation'];
			elseif(isset($exif['IFD0']['Orientation'])) $ort = $exif['IFD0']['Orientation'];
			elseif(isset($exif['COMPUTED']['Orientation'])) $ort = $exif['COMPUTED']['Orientation'];
			
			if($ort && $ort > 1){
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
				if($this->sourceGdImg){
					switch($ort){
						case 2: // horizontal flip
							//$image->flipImage($public,1);
						break;
			
						case 3: // 180 rotate left
							$this->sourceGdImg = imagerotate($this->sourceGdImg,180,0); 
						break;
			
						case 4: // vertical flip
							//$image->flipImage($public,2);
						break;
			
						case 5: // vertical flip + 90 rotate right
							//$image->flipImage($public, 2);
							//$image->rotateImage($public, -90);
						break;
			
						case 6: // 90 rotate right
							$this->sourceGdImg = imagerotate($this->sourceGdImg,-90,0); 
						break;
			
						case 7: // horizontal flip + 90 rotate right
							//$image->flipImage($public,1);
							//$image->rotateImage($public, -90);
						break;
			
						case 8:    // 90 rotate left
							$this->sourceGdImg = imagerotate($this->sourceGdImg,90,0); 
						break;
					}
					$this->sourceWidth = imagesx($this->sourceGdImg);
					$this->sourceHeight = imagesy($this->sourceGdImg);
				}
			}
		}
	}

	private function urlExists($url) {
		$exists = false;
	    if(file_exists($url)){
			return true;
	    }

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
	     
		//One more  check
	    if(!$exists){
	    	$exists = (@fclose(@fopen($url,"r")));
	    }
	    return $exists;
	}	

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>