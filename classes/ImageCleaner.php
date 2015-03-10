<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once("ImageShared.php");

class ImageCleaner{
	
	private $conn;
	private $verbose = 1;
	private $errorStr;

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
		$imgManager->setTargetPath('misc');
		
		$sql = 'SELECT ti.imgid, ti.url, ti.originalurl '.
			'FROM images ti ';
		if($collid) $sql .= 'INNER JOIN omoccurrences o ON ti.occid = o.occid ';
		$sql .= 'WHERE (ti.thumbnailurl IS NULL OR ti.thumbnailurl = "") ';
		if($collid) $sql .= 'AND (o.collid = '.$collid.') ';
		//$sql .= 'LIMIT 100';
		//echo $sql; exit;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$status = true;
			$webIsEmpty = false;
			$imgId = $row->imgid;
			if($this->verbose) echo '<li>Building thumbnail: <a href="../imgdetails.php?imgid='.$imgId.'" target="_blank">'.$imgId.'</a>... ';
			$imgUrl = trim($row->url);
			if((!$imgUrl || $imgUrl == 'empty') && $row->originalurl){
				$imgUrl = trim($row->originalurl);
				$webIsEmpty = true;
			}
			if($imgManager->parseUrl($imgUrl)){
				//Create thumbnail
				$imgTnUrl = '';
				if($imgManager->createNewImage('_tn',$imgManager->getTnPixWidth(),70)){
					$imgTnUrl = $imgManager->getUrlBase().$imgManager->getImgName().'_tn.jpg';
				}
				else{
					$this->errorStr = 'ERROR building thumbnail: '.implode('; ',$imgManager->getErrArr());
					$status = false;
				}
				
				if($status && $imgTnUrl && $imgManager->uriExists($imgTnUrl)){
					$webFullUrl = '';
					$lgFullUrl = '';
					//If web image is too large, transfer to large image and create new web image
					list($sourceWidth, $sourceHeight) = getimagesize($imgManager->getSourcePath());
					if(!$webIsEmpty && !$row->originalurl){
						$fileSize = $imgManager->getSourceFileSize();
						if($fileSize > $imgManager->getWebFileSizeLimit() || $sourceWidth > ($imgManager->getWebPixWidth()*1.2)){
							$lgFullUrl = $imgManager->getSourcePath();
							$webIsEmpty = true;
						}
					}
					if($webIsEmpty){
						if($sourceWidth && $sourceWidth < $imgManager->getWebPixWidth()){
							if(copy($imgManager->getSourcePath(),$imgManager->getTargetPath().$imgManager->getImgName().'_web'.$imgManager->getImgExt())){
								$webFullUrl = $imgManager->getUrlBase().$imgManager->getImgName().'_web'.$imgManager->getImgExt();
							}
						}
						if(!$webFullUrl){
							if($imgManager->createNewImage('_web',$imgManager->getWebPixWidth())){
								$webFullUrl = $imgManager->getUrlBase().$imgManager->getImgName().'_web.jpg';
							}
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
					if(!$this->conn->query($sql)){
						$status = false;
						$this->errorStr = 'ERROR: thumbnail created but failed to update database: '.$this->conn->error;
					}
				}
				$imgManager->reset();
			}
			else{
				$status = false;
				$this->errorStr = 'ERROR: unable to parse source image ('.$imgUrl.')';
			}
			ob_flush();
			flush();
			if($this->verbose){
				if($status){
					echo 'Done!</li>';
				}
				else{
					echo $this->errorStr.'</li>';
				}
			}
		}
		$result->free();
	}

	public function setVerbose($verb){
		$this->verbose = $verb;
	}
	
	public function getErrorStr(){
		return $this->errorStr;
	}
}
?>