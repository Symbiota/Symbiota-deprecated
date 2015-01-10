<?php
include_once('OccurrenceEditorManager.php');
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
		$status = false;
		//Load occurrence record
		$occid = $this->addOccurrence($postArr);
		if($occid){
			$status = true;
			$this->occid = $occid;
			//Load images
			if($this->addImage($postArr)){
				if($this->activeImgId){
					//Load OCR
					if($postArr['ocrblock']){
						$sql = 'INSERT INTO specprocessorrawlabels(imgid, rawstr) '.
							'VALUES('.$this->activeImgId.',"'.$this->cleanInStr($postArr['ocrblock']).'")';
						if(!$this->conn->query($sql)){
							$this->errorStr = 'Error loading OCR text block: '.$this->conn->error;
						}
					}
				}
			}
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
	
	public function addImage($postArr){
		$status = true;
		$imgManager = new ImageShared();
		
		//Set target path
		$subTargetPath = $this->collMap['institutioncode'];
		if($this->collMap['collectioncode']) $subTargetPath .= '_'.$this->collMap['collectioncode'];
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
		if($imgManager->processImage()){
			$this->activeImgId = $imgManager->getActiveImgId();
		}
		
		//Load tags
		$status = $imgManager->insertImageTags($postArr);
		
		//Get errors and warnings
		if($imgManager->getErrArr()) {
			$this->errorStr = implode('; ',$imgManager->getErrArr());
		}
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
		$sql = 'SELECT o.occid, o.recordedby, o.recordnumber, o.eventdate, o.sciname, '.
			'CONCAT_WS("; ",o.stateprovince, o.county, o.locality) AS locality '.
			'FROM omoccurrences o WHERE '.substr($sql,4);
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$occId = $row->occid;
			$returnArr[$occId]['sciname'] = $this->cleanOutStr($row->sciname);
			$returnArr[$occId]['recordedby'] = $this->cleanOutStr($row->recordedby);
			$returnArr[$occId]['recordnumber'] = $this->cleanOutStr($row->recordnumber);
			$returnArr[$occId]['eventdate'] = $this->cleanOutStr($row->eventdate);
			$returnArr[$occId]['locality'] = $this->cleanOutStr($row->locality);
		}
		$rs->close();
		return $returnArr;
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