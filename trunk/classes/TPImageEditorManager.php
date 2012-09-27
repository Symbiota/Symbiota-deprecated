<?php
include_once("TPEditorManager.php");
include_once('PelJpeg.php');

class TPImageEditorManager extends TPEditorManager{

	private $imageRootPath = "";
	private $imageRootUrl = "";

	private $tnPixWidth = 200;
	private $webPixWidth = 1600;
	private $lgPixWidth = 3168;
	private $webFileSizeLimit = 300000;

	private $sourceGdImg;
	private $exif;
		
 	public function __construct(){
 		parent::__construct();
		set_time_limit(120);
		ini_set("max_input_time",120);
 		$this->imageRootPath = $GLOBALS["imageRootPath"];
		if(substr($this->imageRootPath,-1) != "/") $this->imageRootPath .= "/";  
		$this->imageRootUrl = $GLOBALS["imageRootUrl"];
		if(substr($this->imageRootUrl,-1) != "/") $this->imageRootUrl .= "/";
		if(array_key_exists('imgWebWidth',$GLOBALS)){
			$this->webPixWidth = $GLOBALS['imgWebWidth'];
		}
		if(array_key_exists('imgTnWidth',$GLOBALS)){
			$this->tnPixWidth = $GLOBALS['imgTnWidth'];
		}
		if(array_key_exists('imgLgWidth',$GLOBALS)){
			$this->lgPixWidth = $GLOBALS['imgLgWidth'];
		}
		if(array_key_exists('imgFileSizeLimit',$GLOBALS)){
			$this->webFileSizeLimit = $GLOBALS['imgFileSizeLimit'];
		}
 	}
 	
 	public function __destruct(){
 		parent::__destruct();
		if($this->sourceGdImg) imagedestroy($this->sourceGdImg);
 	}
 	
	public function getImages(){
		$imageArr = Array();
		$tidArr = Array($this->tid);
		$sql1 = 'SELECT DISTINCT tid FROM taxstatus '.
			'WHERE taxauthid = 1 AND tid = tidaccepted AND ((hierarchystr LIKE "%,'.$this->tid.',%") OR (hierarchystr LIKE "%,'.$this->tid.'"))';
		$rs1 = $this->taxonCon->query($sql1);
		while($r1 = $rs1->fetch_object()){
			$tidArr[] = $r1->tid;
		}
		$rs1->close();
		
		$tidStr = implode(",",$tidArr);
		$this->imageArr = Array();
		$sql = 'SELECT ti.imgid, ti.url, ti.thumbnailurl, ti.originalurl, ti.caption, ti.photographer, ti.photographeruid, '.
			'IFNULL(ti.photographer,CONCAT_WS(" ",u.firstname,u.lastname)) AS photographerdisplay, ti.owner, '.
			'ti.locality, ti.occid, ti.notes, ti.sortsequence, ti.sourceurl, ti.copyright '.
			'FROM (images ti LEFT JOIN users u ON ti.photographeruid = u.uid) '.
			'INNER JOIN taxstatus ts ON ti.tid = ts.tid '.
			'WHERE ts.taxauthid = 1 AND (ts.tidaccepted IN('.$tidStr.')) AND ti.SortSequence < 500 '.
			'ORDER BY ti.sortsequence'; 
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

	public function editImageSort($imgSortEdits){
		$status = "";
		foreach($imgSortEdits as $editKey => $editValue){
			if(is_numeric($editKey) && is_numeric($editValue)){
				$sql = "UPDATE images SET sortsequence = ".$editValue." WHERE imgid = ".$editKey;
				//echo $sql;
				if(!$this->taxonCon->query($sql)){
					$status .= $this->taxonCon->error."\nSQL: ".$sql."; ";
				}
			}
		}
		if($status) $status = "with editImageSort method: ".$status;
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
			list($width, $height) = getimagesize($imgPath?$imgPath:$imgUrl);
			$fileSize = filesize($imgPath?$imgPath:$imgUrl);
			//Create large image
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
			$sql = "INSERT INTO images (tid, url, thumbnailurl, originalurl, photographer, photographeruid, caption, ".
				"owner, sourceurl, copyright, locality, occid, notes, sortsequence) ".
				"VALUES (".$this->tid.",\"".$imgWebUrl."\",".($imgTnUrl?"\"".$imgTnUrl."\"":"NULL").",".($imgLgUrl?"\"".$imgLgUrl."\"":"NULL").",".
				($photographer?"\"".$this->cleanStr($photographer)."\"":"NULL").",".
				($photographerUid?$photographerUid:"NULL").",\"".
				$this->cleanStr($caption)."\",\"".$this->cleanStr($owner).
				"\",\"".$sourceUrl."\",\"".$this->cleanStr($copyRight).
				"\",\"".$this->cleanStr($locality)."\",".
				($occId?$occId:"NULL").",\"".$this->cleanStr($notes)."\",".
				($sortSequence?$this->cleanStr($sortSequence):"50").")";
			//echo $sql;
			$status = "";
			if($this->taxonCon->query($sql)){
				if($addToTid){
					$sql = "INSERT INTO images (tid, url, thumbnailurl, originalurl, photographer, photographeruid, caption, ".
						"owner, sourceurl, copyright, locality, occid, notes) ". 
						"VALUES (".$addToTid.",\"".$imgWebUrl."\",".($imgTnUrl?"\"".$imgTnUrl."\"":"NULL").",".($imgLgUrl?"\"".$imgLgUrl."\"":"NULL").",".
						($photographer?"\"".$this->cleanStr($photographer)."\"":"NULL").
						",".($photographerUid?$photographerUid:"NULL").",\"".
						$this->cleanStr($imageType)."\",\"".$this->cleanStr($caption).
						"\",\"".$this->cleanStr($owner)."\",\"".$sourceUrl.
						"\",\"".$this->cleanStr($copyRight)."\",\"".$this->cleanStr($locality)."\",".
						($occId?$occId:"NULL").",\"".$this->cleanStr($notes)."\")";
					//echo $sql;
					if(!$this->taxonCon->query($sql)){
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

	private function createNewImage($sourceImg,$targetPath,$targetWidth,$qualityRating = 0){
        $successStatus = false;
		list($sourceWidth, $sourceHeight) = getimagesize($sourceImg);
        $newWidth = $targetWidth;
        $newHeight = round($sourceHeight*($targetWidth/$sourceWidth));
        if($newHeight > $targetWidth*1.2){
        	$newHeight = $targetWidth;
        	$newWidth = round($sourceWidth*($targetWidth/$sourceHeight));
        }

		if(!$this->sourceGdImg){
			$this->sourceGdImg = imagecreatefromjpeg($sourceImg);
			if($newWidth > 300 && class_exists('PelJpeg')){
				$inputJpg = new PelJpeg($this->sourceGdImg);
				$this->exif = $inputJpg->getExif();
			}
		}
    	$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
		imagecopyresampled($tmpImg,$this->sourceGdImg,0,0,0,0,$newWidth, $newHeight,$sourceWidth,$sourceHeight);

		if($qualityRating){
			$successStatus = imagejpeg($tmpImg, $targetPath, $qualityRating);
			if($this->exif && class_exists('PelJpeg')){
				$outputJpg = new PelJpeg($targetPath);
				$outputJpg->setExif($this->exif);
				$outputJpg->saveFile($targetPath);
			}
		}
		else{
			if($this->exif && class_exists('PelJpeg')){
				$outputJpg = new PelJpeg($tmpImg);
				$outputJpg->setExif($this->exif);
				$successStatus = $outputJpg->saveFile($targetPath);
			}
			else{
				$successStatus = imagejpeg($tmpImg, $targetPath);
			}
		}
		
       	imagedestroy($tmpImg);
	    return $successStatus;
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

