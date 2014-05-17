<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once("ImageShared.php");

class ImageCleaner{
	
	private $conn;
	private $verbose = 1;

	function __construct() {
		set_time_limit(2000);
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
		if($this->conn) $this->conn->close();
	}

	public function getMissingTnCount($collid = 0){
		$tnCnt = 0;
		$sql = 'SELECT count(ti.imgid) AS tnCnt FROM images ti ';
		if($collid) $sql .= 'INNER JOIN omoccurrences o ON ti.occid = o.occid ';
		$sql .= 'WHERE (ti.thumbnailurl IS NULL OR ti.thumbnailurl = "")';
		if($collid) $sql .= 'AND (o.collid = '.$collid.') ';

		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$tnCnt = $row->tnCnt;
		}
		$result->free();
		return $tnCnt;
	}

	public function buildThumbnailImages($collid = 0){
		$imgManager = new ImageShared();
		$imgManager->setTargetPath('thumbnails');
		
		$sql = 'SELECT ti.imgid, ti.url, ti.originalurl '.
			'FROM images ti ';
		if($collid) $sql .= 'INNER JOIN omoccurrences o ON ti.occid = o.occid ';
		$sql .= 'WHERE (ti.thumbnailurl IS NULL OR ti.thumbnailurl = "") ';
		if($collid) $sql .= 'AND (o.collid = '.$collid.') ';
		//$sql .= 'LIMIT 100';
		//echo $sql; exit;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$statusStr = '';
			$webIsEmpty = false;
			$imgId = $row->imgid;
			if($this->verbose) echo '<li>Building thumbnail: <a href="../imgdetails.php?imgid='.$imgId.'" target="_blank">'.$imgId.'</a>... ';
			$imgUrl = trim($row->url);
			if((!$imgUrl || $imgUrl == 'empty') && $row->originalurl){
				$imgUrl = trim($row->originalurl);
				$webIsEmpty = true;
			}
			$imgManager->parseUrl($imgUrl);

			//Create thumbnail 
			$imgTnUrl = '';
			if($imgManager->createNewImage('_tn',$imgManager->getTnPixWidth(),70)){
				$imgTnUrl = $imgManager->getUrlBase().$imgManager->getImgName().'_tn.jpg';
			}

			if($this->urlExists($imgTnUrl)){
				$webFullUrl = '';
				$lgFullUrl = '';
				//If web image is too large, transfer to large image and create new web image
				$fileSize = 0;
				if(!$webIsEmpty && !$row->originalurl){
					$fileSize = $this->getFileSize($imgManager->getSourcePath());
					list($sourceWidth, $sourceHeight) = getimagesize($imgManager->getSourcePath());
					if($fileSize > $imgManager->getWebFileSizeLimit() || $sourceWidth > ($imgManager->getWebPixWidth()*1.2)){
						$lgFullUrl = $imgManager->getSourcePath();
						$webIsEmpty = true;
					}
				}
				if($webIsEmpty){
					if($imgManager->createNewImage('_web',$imgManager->getWebPixWidth())){
						$webFullUrl = $imgManager->getUrlBase().$imgManager->getImgName().'_web.jpg';
					}
				}

				$sql = 'UPDATE images ti SET ti.thumbnailurl = "'.$imgTnUrl.'" ';
				if($webFullUrl){
					$sql .= ',url = "'.$webFullUrl.'" ';
				}
				if($lgFullUrl){
					$sql .= ',originalurl = "'.$lgFullUrl.'" ';
				}
				$sql .= "WHERE ti.imgid = ".$imgId;
				//echo $sql; 
				if($this->conn->query($sql)){
					if($this->verbose) $statusStr = 'Done!';
				}
				else{
					if($this->verbose) $statusStr = 'ERROR: thumbnail created but failed to update database: '.$this->conn->error;
				}
			}
			else{
				if($this->verbose) $statusStr = 'ERROR: failed to create thumbnail';
			}
			if($this->verbose) echo $statusStr.'</li>';
			$imgManager->reset();
			ob_flush();
			flush();
		}
		$result->free();
	}

	public function setVerbose($verb){
		$this->verbose = $verb;
	}
	
	private function getFileSize($remoteFile){
		$fileSize = 0;
		if(strtolower(substr($remoteFile,0,7)) == 'http://' || strtolower(substr($remoteFile,0,8)) == 'https://'){
			$ch = curl_init($remoteFile);
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
		}
		else{
			$fileSize = filesize($remoteFile);
		}
		return $fileSize;
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
}
?>