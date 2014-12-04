<?php
include_once($serverRoot.'/config/dbconnection.php');

class GlossaryManager{

	private $conn;
	private $glossId = 0;
	private $imageRootPath = '';
	private $imageRootUrl = '';
	private $sourcePath = '';
	private $targetPath = '';
	private $imgExt = '';
	private $targetUrl;
	private $fileName;
	
 	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("write");
		$this->imageRootPath = $GLOBALS["imageRootPath"];
		if(substr($this->imageRootPath,-1) != "/") $this->imageRootPath .= "/";  
		$this->imageRootUrl = $GLOBALS["imageRootUrl"];
		if(substr($this->imageRootUrl,-1) != "/") $this->imageRootUrl .= "/";
 	}
 	
 	public function __destruct(){
		if($this->conn) $this->conn->close();
	}
	
	public function getTermList($keyword,$language){
		$retArr = array();
		$sql = 'SELECT g.glossid, g.term '.
			'FROM glossary AS g ';
		if($keyword || $language){
			$sql .= 'WHERE ';
			if($keyword){
				$sql .= 'g.term LIKE "%'.$keyword.'%" OR g.definition LIKE "%'.$keyword.'%" ';
			}
			if($keyword && $language){
				$sql .= 'AND ';
			} 
			if($language){
				$sql .= 'g.`language` = "'.$language.'" ';
			}
		}
		$sql .= 'ORDER BY g.term ';
		//echo '<div>'.$sql.'</div>';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->glossid]['glossid'] = $r->glossid;
				$retArr[$r->glossid]['term'] = $r->term;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function createTerm($pArr){
		global $SYMB_UID;
		$statusStr = '';
		$sql = 'INSERT INTO glossary(term,definition,`language`,uid) '.
			'VALUES("'.$this->cleanInStr($pArr['term']).'","'.$this->cleanInStr($pArr['definition']).'","'.$this->cleanInStr($pArr['language']).'",'.$SYMB_UID.') ';
		//echo $sql;
		if($this->conn->query($sql)){
			$this->glossId = $this->conn->insert_id;
		}
		else{
			$statusStr = 'ERROR: Creation of new term failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}
	
	public function getTermArr($glossId){
		$retArr = array();
		$sql = 'SELECT g.glossid, g.term, g.definition, g.`language`, g.source, g.notes '.
			'FROM glossary AS g '.
			'WHERE g.glossid = '.$glossId;
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr['glossid'] = $r->glossid;
				$retArr['term'] = $r->term;
				$retArr['definition'] = $r->definition;
				$retArr['language'] = $r->language;
				$retArr['source'] = $r->source;
				$retArr['notes'] = $r->notes;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function getImgArr($glossId){
		$retArr = array();
		$sql = 'SELECT g.glimgid, g.glossid, g.url, g.structures, g.notes '.
			'FROM glossaryimages AS g '.
			'WHERE g.glossid = '.$glossId;
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->glimgid]['glimgid'] = $r->glimgid;
				$retArr[$r->glimgid]['glossid'] = $r->glossid;
				$retArr[$r->glimgid]['url'] = $r->url;
				$retArr[$r->glimgid]['structures'] = $r->structures;
				$retArr[$r->glimgid]['notes'] = $r->notes;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function editTerm($pArr){
		global $SYMB_UID;
		$statusStr = '';
		$glossId = $pArr['glossid'];
		if(is_numeric($glossId)){
			$sql = '';
			foreach($pArr as $k => $v){
				if($k != 'formsubmit' && $k != 'glossid'){
					$sql .= ','.$k.'='.($v?'"'.$this->cleanInStr($v).'"':'NULL');
				}
			}
			$sql = 'UPDATE glossary SET '.substr($sql,1).' WHERE (glossid = '.$glossId.')';
			//echo $sql;
			if($this->conn->query($sql)){
				$statusStr = 'SUCCESS: information saved';
			}
			else{
				$statusStr = 'ERROR: Editing of term failed: '.$this->conn->error.'<br/>';
				$statusStr .= 'SQL: '.$sql;
			}
		}
		return $statusStr;
	}
	
	public function editImageData($pArr){
		$statusStr = '';
		$glimgId = $pArr['glimgid'];
		$oldUrl = $pArr["oldurl"];
		unset($pArr['oldurl']);
		if(is_numeric($glimgId)){
			if(array_key_exists("renameweburl",$pArr)){
				$oldName = str_replace($this->imageRootUrl,$this->imageRootPath,$oldUrl);
				$newWebName = str_replace($this->imageRootUrl,$this->imageRootPath,$pArr['url']);
				if($pArr['url'] != $oldUrl){
					if(file_exists($newWebName)){
						$status = 'ERROR: unable to modify image URL because a file already exists with that name; ';
						$pArr['url'] = $oldUrl;
					}
					else{
						if(!rename($oldName,$newWebName)){
							$pArr['url'] = $oldUrl;
							$status .= "Web URL rename FAILED (possible write permissions issue); ";
						}
					}
				}
				unset($pArr['renameweburl']);
			}
			$sql = '';
			foreach($pArr as $k => $v){
				if($k != 'formsubmit' && $k != 'glossid' && $k != 'glimgid'){
					$sql .= ','.$k.'='.($v?'"'.$this->cleanInStr($v).'"':'NULL');
				}
			}
			$sql = 'UPDATE glossaryimages SET '.substr($sql,1).' WHERE (glimgid = '.$glimgId.')';
			//echo $sql;
			if($this->conn->query($sql)){
				$statusStr = 'SUCCESS: information saved';
			}
			else{
				$statusStr = 'ERROR: Editing of image data failed: '.$this->conn->error.'<br/>';
				$statusStr .= 'SQL: '.$sql;
			}
		}
		return $statusStr;
	}
	
	public function deleteTerm($glossId){
		$statusStr = '';
		$sql = 'DELETE FROM glossary '.
				'WHERE (glossid = '.$glossId.')';
		//echo $sql;
		if($this->conn->query($sql)){
			$statusStr = 'Term deleted.';
		}
		else{
			$statusStr = 'ERROR: Deletion of term failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}
	
	public function deleteImage($imgIdDel,$removeImg){
		$imgUrl = "";
		$status = "Image deleted successfully";
		$sqlQuery = 'SELECT url FROM glossaryimages WHERE (glimgid = '.$imgIdDel.')';
		$result = $this->conn->query($sqlQuery);
		if($row = $result->fetch_object()){
			$imgUrl = $row->url;
		}
		$result->close();
				
		$sql = "DELETE FROM glossaryimages WHERE (glimgid = ".$imgIdDel.')';
		//echo $sql;
		if($this->conn->query($sql)){
			if($removeImg){
				//Remove images only if there are no other references to the image
				$sql = "SELECT glimgid FROM glossaryimages WHERE (url = '".$imgUrl."')";
				$rs = $this->conn->query($sql);
				if(!$rs->num_rows){
					//Delete image from server 
					$imgDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgUrl);
					if(file_exists($imgDelPath)){
						if(!unlink($imgDelPath)){
							$status = "Deleted image record from database successfully but FAILED to delete image from server. The Image will have to be deleted manually.";
						}
					}
				}
			}
		}
		else{
			$status = "deleteImage: ".$this->conn->error."\nSQL: ".$sql;
		}
		return $status;
	}

	public function addImage(){
		$status = '';
		//Set download paths and variables
		set_time_limit(120);
		ini_set("max_input_time",120);
		$this->setTargetPath();
		
		if($_REQUEST["imgurl"]){
			if(array_key_exists('copytoserver',$_REQUEST)){
				if(!$this->copyImageFromUrl($_REQUEST["imgurl"])) return;
				$sourceImgUri = $this->targetUrl.$this->fileName;
				$webUrl = $sourceImgUri;
			}
			else{
				$webUrl = $_REQUEST["imgurl"];
			}
		}
		else{
			if(!$this->loadImage()) return;
			$sourceImgUri = $this->targetUrl.$this->fileName;
			
			//list($width, $height) = getimagesize($sourceImgUri);
			
			$webUrl = $sourceImgUri;
		}
		
		//Load to database
		if($webUrl) $status = $this->databaseImage($webUrl);
		
		return $status;
	}
	
	public function copyImageFromUrl($sourceUri){
		//Returns full path
		if(!$sourceUri){
			$this->errArr[] = 'ERROR: Image source uri NULL in copyImageFromUrl method';
			//trigger_error('Image source uri NULL in copyImageFromUrl method',E_USER_ERROR);
			return false;
		}
		if(!$this->uriExists($sourceUri)){
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
			$this->setFileName($fileName.$this->imgExt);
			//$this->testOrientation();
			return true;
		}
		$this->errArr[] = 'ERROR: Unable to copy image to target ('.$this->targetPath.$fileName.$this->imgExt.')';
		return false;
	}
	
	public function cleanFileName($fPath){
		$fName = $fPath;
		$imgInfo = null;
		if(strtolower(substr($fPath,0,7)) == 'http://' || strtolower(substr($fPath,0,8)) == 'https://'){
			//Image is URL 
			$imgInfo = getimagesize($fPath);
			list($this->sourceWidth, $this->sourceHeight) = $imgInfo;
		
			if($pos = strrpos($fName,'/')){
				$fName = substr($fName,$pos+1);
			}
		}
		//Parse extension
		if($p = strrpos($fName,".")){
			$this->imgExt = strtolower(substr($fName,$p));
			$fName = substr($fName,0,$p);
		}
		
		if(!$this->imgExt && $imgInfo){
			if($imgInfo[2] == IMAGETYPE_GIF){
				$this->imgExt = 'gif';
			}
			elseif($imgInfo[2] == IMAGETYPE_PNG){
				$this->imgExt = 'png';
			}
			elseif($imgInfo[2] == IMAGETYPE_JPEG){
				$this->imgExt = 'jpg';
			}
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
		$fName = trim($fName,' _-');
		
		if(strlen($fName) > 30) {
			$fName = substr($fName,0,30);
		}
		//Test to see if target images exist (can happen batch loading images with similar names)
		if($this->targetPath){
			//Check and see if file already exists, if so, rename filename until it has a unique name
			$tempFileName = $fName;
			$cnt = 0;
			while(file_exists($this->targetPath.$tempFileName)){
				$tempFileName = $fName.'_'.$cnt;
				$cnt++;
			}
			if($cnt) $fName = $tempFileName;
		}
		
		//Returns file name without extension
		return $fName;
 	}
	
	private function loadImage(){
		$imgFile = basename($_FILES['imgfile']['name']);
		$this->setFileName($imgFile);
		if(move_uploaded_file($_FILES['imgfile']['tmp_name'], $this->targetPath.$this->fileName)){
			return true;
		}
		return false;
	}

	private function databaseImage($webUrl){
		global $SYMB_UID;
		if(!$webUrl) return 'ERROR: web url is null ';
		$status = 'File added successfully!';
		$sql = 'INSERT INTO glossaryimages(glossid,url,structures,notes,uid) '.
			'VALUES('.$_REQUEST["glossid"].',"'.$webUrl.'","'.$this->cleanInStr($_REQUEST["structures"]).'","'.$this->cleanInStr($_REQUEST["notes"]).'",'.$SYMB_UID.') ';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$status = "ERROR Loading Data: ".$this->conn->error."<br/>SQL: ".$sql;
		}
		return $status;
	}
	
	public function uriExists($url){
		$exists = false;
		$localUrl = '';
		if(substr($url,0,1) == '/'){
			if(isset($GLOBALS['imageDomain']) && $GLOBALS['imageDomain']){
				$url = $GLOBALS['imageDomain'].$url;
			}
			elseif($GLOBALS['imageRootUrl'] && strpos($url,$GLOBALS['imageRootUrl']) === 0){
				$localUrl = str_replace($GLOBALS['imageRootUrl'],$GLOBALS['imageRootPath'],$url);
			}
			else{
				$urlPrefix = "http://";
				if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) $urlPrefix = "https://";
				$urlPrefix .= $_SERVER["SERVER_NAME"];
				if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $urlPrefix .= ':'.$_SERVER["SERVER_PORT"];
				$url = $urlPrefix.$url;
			}
		}
		
		//First simple check
		if(file_exists($url) || ($localUrl && file_exists($localUrl))){
			return true;
	    }

	    //Second check
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
	     
	    //One last check
	    if(!$exists){
	    	$exists = (@fclose(@fopen($url,"r")));
	    }
	    
	    //Test to see if file is an image 
	    if(function_exists('exif_imagetype')){
			if(!exif_imagetype($url)) $exists = false;
		}
		
	    return $exists;
	}

	private function setFileName($fName){
		$this->fileName = $fName;
		//echo $fName;
	}
 	
	private function setTargetPath(){
 		$folderName = date("Y-m");
		if(!file_exists($this->imageRootPath."glossimg")){
			mkdir($this->imageRootPath."glossimg", 0775);
		}
		if(!file_exists($this->imageRootPath."glossimg/".$folderName)){
			mkdir($this->imageRootPath."glossimg/".$folderName, 0775);
		}
		$path = $this->imageRootPath."glossimg/".$folderName."/";
		$url = $this->imageRootUrl."glossimg/".$folderName."/";
		
		$this->targetPath = $path;
		$this->targetUrl = $url;
	}
	
	public function getTermId(){
		return $this->glossId;
	}
	
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>