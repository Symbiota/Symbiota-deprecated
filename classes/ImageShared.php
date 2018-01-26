<?php
include_once($SERVER_ROOT.'/classes/UuidFactory.php');

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
	private $jpgCompression= 70;

	private $mapLargeImg = true;

	//Image metadata
	private $caption;
	private $photographer;
	private $photographerUid;
	private $sourceUrl;
	private $format;
	private $owner;
	private $locality;
	private $occid;
	private $tid;
    private $sourceIdentifier;
    private $rights;
    private $accessRights;
    private $copyright;
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
		if(!($this->conn === null)){
			$this->conn->close();
			$this->conn = null;
		}
	}

 	public function checkSchema() {
        $result = false;

        /*****  Warning: Do not override this check in order to supress error messages.
         *****  If this warning is encountered, this class must be updated, or code that
         *****  invokes it may fail in unpredictable ways. */

        // This class is tightly bound to table images.  If no change was made to that
        // table in a schema update, then the supported schema version may simply be added.
        // If, however, changes were made to the table, they must be reflected in this class.

        //$supportedVersions[] = '0.9.1.13';
        $supportedVersions[] = '0.9.1.14';
        $supportedVersions[] = '0.9.1.15';
        $supportedVersions[] = '0.9.1.16';
        $supportedVersions[] = '1.0';
        $supportedVersions[] = '1.1';

        // Find the most recently applied version number
        $preparesql = "select versionnumber from schemaversion order by dateapplied desc limit 1;";
        if ($statement = $this->conn->prepare($preparesql)) {
            $statement->execute();
            $statement->bind_result($versionnumber);
            $statement->fetch();
            if (in_array($versionnumber,$supportedVersions)) {
               $result = true;
            }
       }
       return $result;
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
		$this->format = '';
		$this->owner = '';
		$this->locality = '';
		$this->occid = '';
		$this->tid = '';
		$this->sourceIdentifier = '';
		$this->rights = '';
		$this->accessRights = '';
		$this->copyright = '';
		$this->notes = '';
		$this->sortSeq = '';

		$this->activeImgId = 0;

		unset($this->errArr);
		$this->errArr = array();

 	}

	public function uploadImage($imgFile = 'imgfile'){
		if($this->targetPath){
			if(file_exists($this->targetPath)){
				$imgFileName = basename($_FILES[$imgFile]['name']);
				$fileName = $this->cleanFileName($imgFileName);
				if(move_uploaded_file($_FILES[$imgFile]['tmp_name'], $this->targetPath.$fileName.$this->imgExt)){
					$this->sourcePath = $this->targetPath.$fileName.$this->imgExt;
					$this->imgName = $fileName;
					//$this->testOrientation();
					return true;
				}
				else{
					$this->errArr[] = 'FATAL ERROR: unable to move image to target ('.$this->targetPath.$fileName.$this->imgExt.')';
				}
			}
			else{
				$this->errArr[] = 'FATAL ERROR: Target path does not exist in uploadImage method ('.$this->targetPath.')';
				//trigger_error('Path does not exist in uploadImage method',E_USER_ERROR);
			}
		}
		else{
			$this->errArr[] = 'FATAL ERROR: Path NULL in uploadImage method';
			//trigger_error('Path NULL in uploadImage method',E_USER_ERROR);
		}
		return false;
	}

	public function copyImageFromUrl($sourceUri){
		//Returns full path
		if(!$sourceUri){
			$this->errArr[] = 'FATAL ERROR: Image source uri NULL in copyImageFromUrl method';
			//trigger_error('Image source uri NULL in copyImageFromUrl method',E_USER_ERROR);
			return false;
		}
		if(!$this->uriExists($sourceUri)){
			$this->errArr[] = 'FATAL ERROR: Image source file ('.$sourceUri.') does not exist in copyImageFromUrl method';
			//trigger_error('Image source file ('.$sourceUri.') does not exist in copyImageFromUrl method',E_USER_ERROR);
			return false;
		}
		if(!$this->targetPath){
			$this->errArr[] = 'FATAL ERROR: Image target url NULL in copyImageFromUrl method';
			//trigger_error('Image target url NULL in copyImageFromUrl method',E_USER_ERROR);
			return false;
		}
		if(!file_exists($this->targetPath)){
			$this->errArr[] = 'FATAL ERROR: Image target file ('.$this->targetPath.') does not exist in copyImageFromUrl method';
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
		$this->errArr[] = 'FATAL ERROR: Unable to copy image to target ('.$this->targetPath.$fileName.$this->imgExt.')';
		return false;
	}

	public function parseUrl($url){
		$status = false;
		$url = str_replace(' ','%20',$url);
		//If image is relative, add proper domain
		if(substr($url,0,1) == '/'){
			if(isset($GLOBALS['imageDomain']) && $GLOBALS['imageDomain']){
				$url = $GLOBALS['imageDomain'].$url;
			}
			else{
				//Use local domain
				$urlPrefix = "http://";
				if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $urlPrefix = "https://";
				$urlPrefix .= $_SERVER["SERVER_NAME"];
				if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $urlPrefix .= ':'.$_SERVER["SERVER_PORT"];
				$url = $urlPrefix.$url;
			}
		}

		$this->sourceUrl = $url;
		if($this->uriExists($url)){
			$this->sourcePath = $url;
	    	$this->imgName = $this->cleanFileName($url);
			//$this->testOrientation();
			$status = true;
		}
		else{
			$this->errArr[] = 'FATAL ERROR: image url does not exist ('.$url.')';
		}
		return $status;
	}

	public function cleanFileName($fPath){
		$fName = $fPath;
		$imgInfo = null;
		if(strtolower(substr($fPath,0,7)) == 'http://' || strtolower(substr($fPath,0,8)) == 'https://'){
			//Image is URL
			$imgInfo = getimagesize(str_replace(' ', '%20', $fPath));
			list($this->sourceWidth, $this->sourceHeight) = $imgInfo;

			if($pos = strrpos($fName,'/')){
				$fName = substr($fName,$pos+1);
			}
		}
		if($imgInfo){
			if($imgInfo[2] == IMAGETYPE_GIF){
				$this->imgExt = '.gif';
				$this->format = 'image/gif';
			}
			elseif($imgInfo[2] == IMAGETYPE_PNG){
				$this->imgExt = '.png';
				$this->format = 'image/png';
			}
			elseif($imgInfo[2] == IMAGETYPE_JPEG){
				$this->imgExt = '.jpg';
				$this->format = 'image/jpeg';
			}
		}

		//Continue cleaning and parsing file name and extension
		if(strpos($fName,'?')) $fName = substr($fName,0,strpos($fName,'?'));
		if($p = strrpos($fName,'.')){
			$this->sourceIdentifier = 'filename: '.$fName;
			if(!$this->imgExt) $this->imgExt = strtolower(substr($fName,$p));
			$fName = substr($fName,0,$p);
		}

		$fName = str_replace(".","",$fName);
		$fName = str_replace(array("%20","%23"," ","__"),"_",$fName);
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
		$fName .= '_'.time();
		//Test to see if target images exist (can happen batch loading images with similar names)
		if($this->targetPath){
			//Check and see if file already exists, if so, rename filename until it has a unique name
			$tempFileName = $fName;
			$cnt = 0;
			while(file_exists($this->targetPath.$tempFileName.'_tn.jpg')){
				$tempFileName = $fName.'_'.$cnt;
				$cnt++;
			}
			if($cnt) $fName = $tempFileName;
		}

		//Returns file name without extension
		return $fName;
	}

	public function setTargetPath($subPath = ''){
		$path = $this->imageRootPath;
		$url = $this->imageRootUrl;
		if(!$path){
			$this->errArr[] = 'FATAL ERROR: Path empty in setTargetPath method';
			trigger_error('Path empty in setTargetPath method',E_USER_ERROR);
			return false;
		}
		if($subPath){
			$badChars = array(' ',':','.','"',"'",'>','<','%','*','|','?');
			$subPath = str_replace($badChars, '', $subPath);
		}
		else{
			$subPath = 'misc/'.date('Ym').'/';
		}
		if(substr($subPath,-1) != '/') $subPath .= '/';

		$path .= $subPath;
		$url .= $subPath;
		if(!file_exists($path)){
			if(!mkdir( $path, 0777, true )){
				$this->errArr[] = 'FATAL ERROR: Unable to create directory: '.$path;
				//trigger_error('Unable to create directory: '.$path,E_USER_ERROR);
				return false;
			}
		}
		$this->targetPath = $path;
		$this->urlBase = $url;
		return true;
	}

	public function processImage(){
        if(!$this->imgName){
			$this->errArr[] = 'FATAL ERROR: Image file name null in processImage function';
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
			list($this->sourceWidth, $this->sourceHeight) = getimagesize(str_replace(' ', '%20', $this->sourcePath));
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
        if(substr($this->sourcePath,0,7)=='http://' || substr($this->sourcePath,0,8)=='https://'){
            $imgWebUrl = $this->sourcePath;
        }
		if(!$imgWebUrl){
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
                $newWidth = ($this->sourceWidth<($this->webPixWidth*1.2)?$this->sourceWidth:$this->webPixWidth);
                $this->createNewImage('',$newWidth);
                $imgWebUrl = $this->imgName.'.jpg';
            }
        }

		$status = true;
		if($imgWebUrl){
			$status = $this->databaseImage($imgWebUrl,$imgTnUrl,$imgLgUrl);
		}
		return $status;
	}

	public function createNewImage($subExt, $targetWidth, $qualityRating = 0, $targetPathOverride = ''){
		global $useImageMagick;
		$status = false;
		if($this->sourcePath){
			if(!$qualityRating) $qualityRating = $this->jpgCompression;

	        if($useImageMagick) {
				// Use ImageMagick to resize images
	        	$status = $this->createNewImageImagick($subExt,$targetWidth,$qualityRating,$targetPathOverride);
			}
			elseif(extension_loaded('gd') && function_exists('gd_info')) {
				// GD is installed and working
				$status = $this->createNewImageGD($subExt,$targetWidth,$qualityRating,$targetPathOverride);
			}
			else{
				// Neither ImageMagick nor GD are installed
				$this->errArr[] = 'ERROR: No appropriate image handler for image conversions';
			}
		}
		else{
			//$this->errArr[] = 'ERROR: Empty sourcePath or failure in uriExist test (sourcePath: '.$this->sourcePath.')';
		}
		return $status;
	}

	private function createNewImageImagick($subExt,$newWidth,$qualityRating,$targetPathOverride){
		$targetPath = $targetPathOverride;
		if(!$targetPath) $targetPath = $this->targetPath.$this->imgName.$subExt.$this->imgExt;
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
		else{
			$this->errArr[] = 'ERROR: Image failed to be created in Imagick function (target path: '.$targetPath.')';
		}
		return false;
	}

	private function createNewImageGD($subExt, $newWidth, $qualityRating, $targetPathOverride){
		$status = false;
		ini_set('memory_limit','512M');

		if(!$this->sourceWidth || !$this->sourceHeight){
			list($this->sourceWidth, $this->sourceHeight) = getimagesize(str_replace(' ', '%20', $this->sourcePath));
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
					if(!$this->format) $this->format = 'image/gif';
				}
				elseif($this->imgExt == '.png'){
			   		$this->sourceGdImg = imagecreatefrompng($this->sourcePath);
					if(!$this->format) $this->format = 'image/png';
				}
				else{
					//JPG assumed
			   		$this->sourceGdImg = imagecreatefromjpeg($this->sourcePath);
					if(!$this->format) $this->format = 'image/jpeg';
				}
			}

			if($this->sourceGdImg){
				$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
				//imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth,$newHeight,$sourceWidth,$sourceHeight);
				imagecopyresized($tmpImg,$this->sourceGdImg,0,0,0,0,$newWidth,$newHeight,$this->sourceWidth,$this->sourceHeight);

				//Irrelevant of import image, output JPG
				$targetPath = $targetPathOverride;
				if(!$targetPath) $targetPath = $this->targetPath.$this->imgName.$subExt.'.jpg';
				if($qualityRating){
					$status = imagejpeg($tmpImg, $targetPath, $qualityRating);
				}
				else{
					$status = imagejpeg($tmpImg, $targetPath);
				}

				if(!$status){
					$this->errArr[] = 'ERROR: failed to create images using target path ('.$targetPath.')';
				}

				imagedestroy($tmpImg);
			}
			else{
				$this->errArr[] = 'ERROR: unable to create image object in createNewImageGD method (sourcePath: '.$this->sourcePath.')';
			}
		}
		else{
			$this->errArr[] = 'ERROR: unable to get source image width ('.$this->sourcePath.')';
		}
		return $status;
	}

	private function databaseImage($imgWebUrl,$imgTnUrl,$imgLgUrl){
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
			if(!$this->tid && $this->occid){
				$sql1 = 'SELECT tidinterpreted FROM omoccurrences WHERE tidinterpreted IS NOT NULL AND occid = '.$this->occid;
				$rs1 = $this->conn->query($sql1);
				if($r1 = $rs1->fetch_object()){
					$this->tid = $r1->tidinterpreted;
				}
				$rs1->free();
			}

			//Save currently loaded record
			$sql = 'INSERT INTO images (tid, url, thumbnailurl, originalurl, photographer, photographeruid, format, caption, '.
				'owner, sourceurl, copyright, locality, occid, notes, username, sortsequence, sourceIdentifier, ' .
                ' rights, accessrights) '.
				'VALUES ('.($this->tid?$this->tid:'NULL').',"'.$imgWebUrl.'",'.
				($imgTnUrl?'"'.$imgTnUrl.'"':'NULL').','.
				($imgLgUrl?'"'.$imgLgUrl.'"':'NULL').','.
				($this->photographer?'"'.$this->photographer.'"':'NULL').','.
				($this->photographerUid?$this->photographerUid:'NULL').','.
				($this->format?'"'.$this->format.'"':'NULL').','.
				($this->caption?'"'.$this->caption.'"':'NULL').','.
				($this->owner?'"'.$this->owner.'"':'NULL').','.
				($this->sourceUrl?'"'.$this->sourceUrl.'"':'NULL').','.
				($this->copyright?'"'.$this->copyright.'"':'NULL').','.
				($this->locality?'"'.$this->locality.'"':'NULL').','.
				($this->occid?$this->occid:'NULL').','.
				($this->notes?'"'.$this->notes.'"':'NULL').',"'.
				$this->cleanInStr($GLOBALS['USERNAME']).'",'.
				($this->sortSeq?$this->sortSeq:'50').','.
				($this->sourceIdentifier?'"'.$this->sourceIdentifier.'"':'NULL').','.
				($this->rights?'"'.$this->rights.'"':'NULL').','.
				($this->accessRights?'"'.$this->accessRights.'"':'NULL').')';
			//echo $sql; exit;
			if($this->conn->query($sql)){
				//Create and insert Symbiota GUID for image(UUID)
				$guid = UuidFactory::getUuidV4();
				$this->activeImgId = $this->conn->insert_id;
				if(!$this->conn->query('INSERT INTO guidimages(guid,imgid) VALUES("'.$guid.'",'.$this->activeImgId.')')) {
					$this->errArr[] = ' Warning: Symbiota GUID mapping failed';
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
				$sql = 'SELECT imgid FROM images WHERE (url = "'.$imgUrl.'") ';
				if($imgUrl2) $sql .= 'OR (url = "'.$imgUrl2.'")';
				$rs = $this->conn->query($sql);
				if($rs->num_rows){
					$this->errArr[] = 'WARNING: Deleted records from database successfully but FAILED to delete image from server because it is being referenced by another record.';
				}
				else{
					//Delete image from server
					$imgDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgUrl);
					if(substr($imgDelPath,0,4) != 'http'){
						if(!unlink($imgDelPath)){
							$this->errArr[] = 'WARNING: Deleted records from database successfully but FAILED to delete image from server (path: '.$imgDelPath.')';
						}
					}

					//Delete thumbnail image
					if($imgThumbnailUrl){
						if(stripos($imgThumbnailUrl,$domain) === 0){
							$imgThumbnailUrl = substr($imgThumbnailUrl,strlen($domain));
						}
						$imgTnDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgThumbnailUrl);
						if(file_exists($imgTnDelPath) && substr($imgTnDelPath,0,4) != 'http') unlink($imgTnDelPath);
					}

					//Delete large version of image
					if($imgOriginalUrl){
						if(stripos($imgOriginalUrl,$domain) === 0){
							$imgOriginalUrl = substr($imgOriginalUrl,strlen($domain));
						}
						$imgOriginalDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgOriginalUrl);
						if(file_exists($imgOriginalDelPath) && substr($imgOriginalDelPath,0,4) != 'http') unlink($imgOriginalDelPath);
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
	public function databaseImageRecord($imgWebUrl,$imgTnUrl,$imgLgUrl,$tid,$caption,$phototrapher,$photographerUid,$sourceUrl,$copyright,$owner,$locality,$occid,$notes,$sortSequence,$imagetype,$anatomy,$sourceIdentifier,$rights,$accessRights){
		$status = "";
		$sql = 'INSERT INTO images (tid, url, thumbnailurl, originalurl, photographer, photographeruid, caption, '.
			'owner, sourceurl, copyright, locality, occid, notes, username, sortsequence, imagetype, anatomy, '.
            'sourceIdentifier, rights, accessrights ) '.
			'VALUES (?,?,?,?,?,?,? ,?,?,?,?,?,?,?,?,?,? ,?,?,?)';
        if ($statement = $this->conn->prepare($sql)) {
		    //If central images are on remote server and new ones stored locally, then we need to use full domain
		    //e.g. this portal is sister portal to central portal
	    	if($GLOBALS['imageDomain']){
				$urlPrefix = "http://";
				if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $urlPrefix = "https://";
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
        	$statement->bind_param("issssisssssississsss",$tid,$imgWebUrl,$imgTnUrl,$imgLgUrl,$photographer,$photographerUid,$caption,$owner,$sourceUrl,$copyright,$locality,$occid,$notes,$username,$sortSequence,$imagetype,$anatomy, $sourceIdentifier, $rights, $accessRights);

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
    public function updateImageRecord($imgid,$imgWebUrl,$imgTnUrl,$imgLgUrl,$tid,$caption,$phototrapher,$photographerUid,$sourceUrl,$copyright,$owner,$locality,$occid,$notes,$sortSequence,$imagetype,$anatomy, $sourceIdentifier, $rights, $accessRights){
        $status = "";
        $sql = 'update images set tid=?, url=?, thumbnailurl=?, originalurl=?, photographer=?, photographeruid=?, caption=?, '.
            'owner=?, sourceurl=?, copyright=?, locality=?, occid=?, notes=?, username=?, sortsequence=?, imagetype=?, anatomy=?, '.
            'sourceIdentifier=?, rights=?, accessrights=? '.
            'where imgid = ? ';
        if ($statement = $this->conn->prepare($sql)) {
		    //If central images are on remote server and new ones stored locally, then we need to use full domain
		    //e.g. this portal is sister portal to central portal
        	if($GLOBALS['imageDomain']){
				$urlPrefix = "http://";
				if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $urlPrefix = "https://";
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

        	$statement->bind_param("issssisssssississsssi",$tid,$imgWebUrl,$imgTnUrl,$imgLgUrl,$photographer,$photographerUid,$caption,$owner,$sourceUrl,$copyright,$locality,$occid,$notes,$username,$sortSequence,$imagetype,$anatomy, $sourceIdentifier, $rights, $accessRights, $imgid);

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
							$this->errArr[] = "Warning: Failed to add image tag [$key] for $this->activeImgId.  " . $stmt->error;
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
    public function getActiveImgId(){
    	return $this->activeImgId;
    }

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
			if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $urlPrefix = "https://";
			$urlPrefix .= $_SERVER["SERVER_NAME"];
			if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $urlPrefix .= ':'.$_SERVER["SERVER_PORT"];
			$urlBase = $urlPrefix.$urlBase;
    	}
		return $urlBase;
	}

	public function getImgName(){
		return $this->imgName;
	}

	public function getImgExt(){
		return $this->imgExt;
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

	public function getTargetPath(){
		return $this->targetPath;
	}

	public function setFormat($v){
		$this->format = $this->cleanInStr($v);
	}

	public function getFormat(){
		return $this->format;
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

	public function setTid($v){
		if(is_numeric($v)){
			$this->tid = $v;
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

	public function getSourceIdentifier(){
		return $this->sourceIdentifier;
	}
	public function setSourceIdentifier($value){
		if($this->sourceIdentifier) $this->sourceIdentifier = '; '.$this->sourceIdentifier;
		$this->sourceIdentifier = $this->cleanInStr($value).$this->sourceIdentifier;
	}

	public function getRights(){
		return $this->rights;
	}
	public function setRights($value){
		$this->rights = $this->cleanInStr($value);
	}

	public function getAccessRights(){
		return $this->accessRights;
	}
	public function setAccessRights($value){
		$this->accessRights = $this->cleanInStr($value);
	}

	public function setCopyright($v){
		$this->copyright = $this->cleanInStr($v);
	}

	public function getErrArr(){
		$retArr = $this->errArr;
		unset($this->errArr);
		$this->errArr = array();
		return $retArr;
	}

	public function getErrStr(){
		$retStr = implode('; ',$this->errArr);
		unset($this->errArr);
		$this->errArr = array();
		return $retStr;
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

	public function getSourceFileSize(){
		$fileSize = 0;
		if($this->sourcePath){
			if(strtolower(substr($this->sourcePath,0,7)) == 'http://' || strtolower(substr($this->sourcePath,0,8)) == 'https://'){
				$x = array_change_key_case(get_headers($this->sourcePath, 1),CASE_LOWER);
				if ( strcasecmp($x[0], 'HTTP/1.1 200 OK') != 0 ) {
					if(isset($x['content-length'][1])) $fileSize = $x['content-length'][1];
					elseif(isset($x['content-length'])) $fileSize = $x['content-length'];
				}
	 			else {
	 				if(isset($x['content-length'])) $fileSize = $x['content-length'];
	 			}
	 			/*
				$ch = curl_init($this->sourcePath);
				curl_setopt($ch, CURLOPT_NOBODY, true);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				curl_setopt($ch, CURLOPT_HEADER, true);
				$data = curl_exec($ch);
				curl_close($ch);
				if($data === false) {
					return 0;
				}
				if(preg_match('/Content-Length: (\d+)/', $data, $matches)) {
				  $fileSize = (int)$matches[1];
				}
				*/
			}
			else{
				$fileSize = filesize($this->sourcePath);
			}
		}
		return $fileSize;
	}

	public function uriExists($uri) {
		$exists = false;

		$secondaryUrl = '';
		if(substr($uri,0,1) == '/'){
			if(isset($GLOBALS['imageDomain']) && $GLOBALS['imageDomain']){
				$secondaryUrl = $GLOBALS['imageDomain'].$uri;
			}
			elseif($GLOBALS['imageRootUrl'] && strpos($uri,$GLOBALS['imageRootUrl']) === 0){
				$secondaryUrl = str_replace($GLOBALS['imageRootUrl'],$GLOBALS['imageRootPath'],$uri);
			}
			else{
				$urlPrefix = "http://";
				if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $urlPrefix = "https://";
				$urlPrefix .= $_SERVER["SERVER_NAME"];
				if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $urlPrefix .= ':'.$_SERVER["SERVER_PORT"];
				$secondaryUrl = $urlPrefix.$uri;
			}
		}

		//First simple check
		if(($secondaryUrl && file_exists($secondaryUrl)) || file_exists($uri) || is_array(@getimagesize(str_replace(' ', '%20', $uri)))){
			return true;
	    }
	    //Second check
	    if(!$exists){
	    	$ch = curl_init($uri);
	    	curl_setopt($ch, CURLOPT_NOBODY, true);
	    	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	    	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
	    	curl_setopt($ch, CURLOPT_USERAGENT,"Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.1) Gecko/20061204 Firefox/2.0.0.1" );
	    	curl_exec($ch);
	    	$retCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	    	// $retcode >= 400 -> not found, $retcode = 200, found.
	    	if($retCode < 400) $exists = true;
	    	curl_close($ch);
	    }

	    //One last check
	    if(!$exists){
	    	$exists = (@fclose(@fopen($uri,"r")));
	    }
	    //Test to see if file is an image
	    if(!@exif_imagetype($uri)) $exists = false;
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