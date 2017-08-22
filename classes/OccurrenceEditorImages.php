<?php
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
include_once($serverRoot.'/classes/SpecProcessorOcr.php');
include_once($serverRoot.'/classes/ImageShared.php');

class OccurrenceEditorImages extends OccurrenceEditorManager {

	private $photographerArr = Array();
	private $imageRootPath = "";
	private $imageRootUrl = "";
	private $activeImgId = 0;

	public function __construct(){
 		parent::__construct();
	}

	public function __destruct(){
 		parent::__destruct();
	}

    /**
     * Takes parameters from a form submission and modifies an existing image record
     * in the database.
     */
	public function addImageOccurrence($postArr){
		$status = true;
		//Load occurrence record
		if($this->addOccurrence($postArr)){
			//Load images
			if($this->addImage($postArr)){
				if($this->activeImgId){
					//Load OCR
					$rawStr = '';
					$ocrSource = '';
					if($postArr['ocrblock']){
						$rawStr = trim($postArr['ocrblock']);
						if($postArr['ocrsource']) $ocrSource = $postArr['ocrsource'];
						else $ocrSource = 'User submitted';
					}
					elseif(isset($postArr['tessocr']) && $postArr['tessocr']){
						$ocrManager = new SpecProcessorOcr();
						$rawStr = $ocrManager->ocrImageById($this->activeImgId);
						$ocrSource = 'Tesseract';
					}
					if($rawStr){
						if($ocrSource) $ocrSource .= ': '.date('Y-m-d');
						$sql = 'INSERT INTO specprocessorrawlabels(imgid, rawstr, source) '.
							'VALUES('.$this->activeImgId.',"'.$this->cleanInStr($rawStr).'","'.$this->cleanInStr($ocrSource).'")';
						if(!$this->conn->query($sql)){
							$this->errorStr = 'ERROR loading OCR text block: '.$this->conn->error;
						}
					}
				}
			}
		}
		else{
			$status = false;
		}
		return $status;
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
		$sortSeq = (is_numeric($_REQUEST["sortsequence"])?$_REQUEST["sortsequence"]:'');
		$sourceUrl = $this->cleanInStr($_REQUEST["sourceurl"]);

		//If central images are on remote server and new ones stored locally, then we need to use full domain
	    //e.g. this portal is sister portal to central portal
    	if($GLOBALS['imageDomain']){
    		if(substr($url,0,1) == '/'){
	    		$url = 'http://'.$_SERVER['HTTP_HOST'].$url;
    		}
    		if($tnUrl && substr($tnUrl,0,1) == '/'){
	    		$tnUrl = 'http://'.$_SERVER['HTTP_HOST'].$tnUrl;
    		}
    		if($origUrl && substr($origUrl,0,1) == '/'){
	    		$origUrl = 'http://'.$_SERVER['HTTP_HOST'].$origUrl;
    		}
    	}

	    $sql = 'UPDATE images '.
			'SET url = "'.$url.'", thumbnailurl = '.($tnUrl?'"'.$tnUrl.'"':'NULL').
			',originalurl = '.($origUrl?'"'.$origUrl.'"':'NULL').',occid = '.$occId.',caption = '.
			($caption?'"'.$caption.'"':'NULL').
			',photographer = '.($photographer?'"'.$photographer.'"':"NULL").
			',photographeruid = '.($photographerUid?$photographerUid:"NULL").
			',notes = '.($notes?'"'.$notes.'"':'NULL').
			($sortSeq?',sortsequence = '.$sortSeq:'').
			',copyright = '.($copyRight?'"'.$copyRight.'"':'NULL').',imagetype = "specimen",sourceurl = '.
			($sourceUrl?'"'.$sourceUrl.'"':'NULL').
			' WHERE (imgid = '.$imgId.')';
		//echo $sql;
		if($this->conn->query($sql)){
            // update image tags
            $kArr = $this->getImageTagValues();
            foreach($kArr as $key => $description) {
                   // Note: By using check boxes, we can't tell the difference between
                   // an unchecked checkbox and the checkboxes not being present on the 
                   // form, we'll get around this by including the original state of the
                   // tags for each image in a hidden field.
                   $sql = null;
                   if (array_key_exists("ch_$key",$_REQUEST)) {
                      // checkbox is selected for this image
                      $sql = "INSERT IGNORE into imagetag (imgid,keyvalue) values (?,?) ";
                   } else { 
                      if (array_key_exists("hidden_$key",$_REQUEST) && $_REQUEST["hidden_$key"]==1) {
                         // checkbox is not selected and this tag was used for this image
                         $sql = "DELETE from imagetag where imgid = ? and keyvalue = ? ";
                      } 
                   } 
                   if ($sql!=null) { 
                      $stmt = $this->conn->stmt_init();
                      $stmt->prepare($sql);
                      if ($stmt) {
                         $stmt->bind_param('is',$imgId,$key);
                         if (!$stmt->execute()) {
                            $status .= " (Warning: Failed to update image tag [$key] for $imgId.  " . $stmt->error ;
                         }
                         $stmt->close();
                      }
                   }
            }
        } else { 
			$status .= "ERROR: image not changed, ".$this->conn->error."SQL: ".$sql;
		}
		return $status;
	}

	public function deleteImage($imgIdDel, $removeImg){
		$status = true; 
		$imgManager = new ImageShared();
		if(!$imgManager->deleteImage($imgIdDel, $removeImg)){
			$this->errorStr = implode('',$imgManager->getErrArr());
			$status = false;
		}
		return $status;
	}

	public function remapImage($imgId, $targetOccid = 0){
		$status = true;
		if(!is_numeric($imgId) || !is_numeric($targetOccid)){
			return false;
		}
		if($targetOccid){
			$sql = 'UPDATE images SET occid = '.$targetOccid.' WHERE (imgid = '.$imgId.')';
			if($this->conn->query($sql)){
				$imgSql = 'UPDATE images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
					'SET i.tid = o.tidinterpreted WHERE (i.imgid = '.$imgId.')';
				//echo $imgSql;
				$this->conn->query($imgSql);
			}
			else{
				$this->errorArr[] = 'ERROR: Unalbe to remap image to another occurrence record. Error msg: '.$this->conn->error;
				$status = false;
			}
		}
		else{
			$sql = 'UPDATE images SET occid = NULL WHERE (imgid = '.$imgId.')';
			if(!$this->conn->query($sql)){
				$this->errorArr[] = 'ERROR: Unalbe to disassociate from occurrence record. Error msg: '.$this->conn->error;
				$status = false;
			}
		}
		return $status;
	}
	
	public function addImage($postArr){
		$status = true;
		$imgManager = new ImageShared();
		
		//Set target path
		$subTargetPath = $this->collMap['institutioncode'];
		if($this->collMap['collectioncode']) $subTargetPath .= '_'.$this->collMap['collectioncode'];
		$subTargetPath .= '/';
		if(!$this->occurrenceMap) $this->setOccurArr();
		$catNum = $this->occurrenceMap[$this->occid]['catalognumber'];
		if($catNum){
			$catNum = str_replace(array('/','\\',' '), '', $catNum);
			if(preg_match('/^(\D{0,8}\d{4,})/', $catNum, $m)){
				$catPath = substr($m[1], 0, -3);
				if(is_numeric($catPath) && strlen($catPath)<5) $catPath = str_pad($catPath, 5, "0", STR_PAD_LEFT);
				$subTargetPath .= $catPath.'/';
			}
			else{
				$subTargetPath .= '00000/';
			}
		}
		else{
			$subTargetPath .= date('Ym').'/';
		}
		$imgManager->setTargetPath($subTargetPath);

		//Import large image or not
		if(array_key_exists('nolgimage',$postArr) && $postArr['nolgimage']==1){
			$imgManager->setMapLargeImg(false);
		}
		else{
			$imgManager->setMapLargeImg(true);
		}
		
		//Set image metadata variables
		if(array_key_exists('caption',$postArr)) $imgManager->setCaption($postArr['caption']);
		if(array_key_exists('photographeruid',$postArr)) $imgManager->setPhotographerUid($postArr['photographeruid']);
		if(array_key_exists('photographer',$postArr)) $imgManager->setPhotographer($postArr['photographer']);
		if(array_key_exists('sourceurl',$postArr)) $imgManager->setSourceUrl($postArr['sourceurl']);
		if(array_key_exists('copyright',$postArr)) $imgManager->setCopyright($postArr['copyright']);
		if(array_key_exists("notes",$postArr)) $imgManager->setNotes($postArr['notes']);
		if(array_key_exists("sortsequence",$postArr)) $imgManager->setSortSeq($postArr['sortsequence']);

		$sourceImgUri = $postArr['imgurl'];
		if($sourceImgUri){
			//Source image is a URI supplied by user
			if(array_key_exists('copytoserver',$postArr) && $postArr['copytoserver']){
				if(!$imgManager->copyImageFromUrl($sourceImgUri)){
					$status = false;
				}
			}
			else{
				$imgManager->parseUrl($sourceImgUri);
			}
		}
		else{
			//Image is a file upload
			if(!$imgManager->uploadImage()){
				$status = false;
			}
		}
		$imgManager->setOccid($this->occid);
		if(isset($this->occurrenceMap[$this->occid]['tidinterpreted'])) $imgManager->setTid($this->occurrenceMap[$this->occid]['tidinterpreted']);
		if($imgManager->processImage()){
			$this->activeImgId = $imgManager->getActiveImgId();
		}
		
		//Load tags
		$status = $imgManager->insertImageTags($postArr);
		
		//Get errors and warnings
		$this->errorStr = $imgManager->getErrStr();
		return $status;
	}
	
	private function setRootPaths(){
		$this->imageRootPath = $GLOBALS["imageRootPath"];
		if(substr($this->imageRootPath,-1) != "/") $this->imageRootPath .= "/";  
		$this->imageRootUrl = $GLOBALS["imageRootUrl"];
		if(substr($this->imageRootUrl,-1) != "/") $this->imageRootUrl .= "/";
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

    /**
     * Obtain an array of the keys used for tagging images by content type.
     *
     * @param lang language for the description, only en currently supported.
     * @return an array of keys for image type tagging along with their descriptions.
     */
    public function getImageTagValues($lang='en') { 
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

    /**
     * Obtain an array of the keys used for tagging images by content type.
     *
     * @param imgid the images.imgid for which to return presence/absence values for each key
     * @param lang language for the description, only en currently supported.
     * @return an ImagTagUse object containing the keys for image type tagging along with their
     * presence/absence for the provided image and descriptions.
     */
    public function getImageTagUsage($imgid,$lang='en') {
       $resultArr = Array();
       switch ($lang) {
          case 'en':
          default:
            $sql = "select * from ( " .
                   "  select tagkey, description_en, shortlabel, sortorder, not isnull(imgid) from imagetagkey k " .
                   "     left join imagetag i on k.tagkey = i.keyvalue " . 
                   "     where (i.imgid is null or i.imgid = ? ) " .
                   "  union " .
                   "  select tagkey, description_en, shortlabel, sortorder, 0 from imagetagkey k " .
                   "     left join imagetag i on k.tagkey = i.keyvalue " . 
                   "     where (i.imgid is not null and i.imgid <> ? ) " .
                   " ) a order by sortorder ";
       }
       $stmt = $this->conn->stmt_init();
       $stmt->prepare($sql);
       if ($stmt) {
          $stmt->bind_param('ii',$imgid,$imgid);
          $stmt->bind_result($key,$desc,$lab,$sort,$value);
          $stmt->execute();
          $i = 0;
          while ($stmt->fetch()) {
             $result = new ImageTagUse();
             $result->tagkey = $key;
             $result->shortlabel = $lab;
             $result->description = $desc;
             $result->sortorder = $sort;
             $result->value = $value;
             $resultArr[$i] = $result;
             $i++;
          }
          $stmt->close();
       }
       return $resultArr;
    }
}

class ImageTagUse { 
   public $tagkey;  // magic value
   public $shortlabel;  // short human readable value
   public $description; // human readable description
   public $sortorder;
   public $value;  // 0 or 1
}
?>