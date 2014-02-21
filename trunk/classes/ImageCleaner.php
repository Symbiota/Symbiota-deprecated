<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class ImageCleaner{
	
	private $rootPathBase = "";
	private $urlPath = "";
	private $conn;

	private $tnPixWidth = 200;
	private $webPixWidth = 1600;
	private $imgFileSizeLimit = 500000;

	private $verbose = 1;

	function __construct() {
		set_time_limit(2000);
		ini_set('memory_limit', '512M');
		$this->rootPathBase = $GLOBALS["imageRootPath"];
		if(substr($this->rootPathBase,-1) != "/") $this->rootPathBase .= "/";  
		$this->urlPath = $GLOBALS["imageRootUrl"];
		if(!$this->urlPath) exit('FATAL ERROR: imageRootUrl is not set');
		if(substr($this->urlPath,-1) != "/") $this->urlPath .= "/";
		$this->conn = MySQLiConnectionFactory::getCon("write");
		
		if(array_key_exists('imgTnWidth',$GLOBALS)){
			$this->tnPixWidth = $GLOBALS['imgTnWidth'];
		}
		if(array_key_exists('imgWebWidth',$GLOBALS)){
			$this->webPixWidth = $GLOBALS['imgWebWidth'];
		}
		if(array_key_exists('imgFileSizeLimit',$GLOBALS)){
			$this->imgFileSizeLimit = $GLOBALS['imgFileSizeLimit'];
		}
	}

	function __destruct(){
		if($this->conn) $this->conn->close();
	}

	public function getMissingTnCount(){
		$tnCnt = 0;
		$sql = 'SELECT count(ti.imgid) AS tnCnt FROM images ti '.
			'WHERE (ti.thumbnailurl IS NULL OR ti.thumbnailurl = "")';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$tnCnt = $row->tnCnt;
		}
		$result->free();
		return $tnCnt;
	}

	public function buildThumbnailImages($collid = 0){
		$sql = 'SELECT ti.imgid, ti.url, ti.originalurl '.
			'FROM images ti ';
		if($collid) $sql .= 'INNER JOIN omoccurrences o ON ti.occid = o.occid ';
		$sql .= 'WHERE (ti.thumbnailurl IS NULL OR ti.thumbnailurl = "") '; 
		if($collid) $sql .= 'AND (o.collid = '.$collid.') ';
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$statusStr = 'ERROR';
			$webIsEmpty = false;
			$imgId = $row->imgid;
			$url = trim($row->url);
			if((!$url || $url == 'empty') && $row->originalurl){
				$imgUrl = trim($row->originalurl);
				$webIsEmpty = true;
			}
			else{
				$imgUrl = trim($url);
			}
			$origUrl = $row->originalurl;
			if($this->verbose) echo '<li>Building thumbnail: <a href="../imgdetails.php?imgid='.$imgId.'" target="_blank">#'.$imgId.'</a>... ';
			ob_flush();
			flush();
			//Get source path
			$sourcePath = $imgUrl;
			if(substr($imgUrl,0,1) == '/'){
				if(array_key_exists('imageDomain',$GLOBALS) && $GLOBALS['imageDomain']){
					$sourcePath = $GLOBALS['imageDomain'].$imgUrl;
				}
				else{
					if(file_exists(str_replace($this->urlPath,$this->rootPathBase,$imgUrl))){
						$sourcePath = str_replace($this->urlPath,$this->rootPathBase,$imgUrl);
					}
					else{
						$sourcePath = 'http://'.$_SERVER['HTTP_HOST'].$imgUrl;
					}
				}
			}
			//Create target path
			$targetPath = '';
			$targetUrl = '';
			if(substr($sourcePath,0,1) == '/'){
				$targetPath = substr($sourcePath,0,strrpos($sourcePath,'/'));
				$targetUrl = str_replace($this->rootPathBase,$this->urlPath,$targetPath);
			}
			else{
				$targetPath = $this->rootPathBase.'misc/';
				if(!is_dir($targetPath)){
					if(!mkdir($targetPath)){
						if($this->verbose) echo '<li>FATAL ERROR => unable to create target folder: '.$targetPath.'</li>';
						exit("FATAL ERROR => unable to create target folder: ".$targetPath);
					}
				}
				$targetPath .= date("Ym").'/';
				if(!is_dir($targetPath)){
					if(!mkdir($targetPath)){
						if($this->verbose) echo '<li>FATAL ERROR => unable to create target folder: '.$targetPath.'</li>';
						exit("FATAL ERROR => unable to create target folder: ".$targetPath);
					}
				}
				$targetUrl = $this->urlPath.'misc/'.date("Ym").'/';
			}
			
			//Create file names
			$fileName = substr($sourcePath,strrpos($sourcePath,'/')+1);
			
			//Get image statistics
			$tempSourcePath = $sourcePath;
			if(strtolower(substr($sourcePath,0,7)) == 'http://'){
				$tempPath = $this->getTempPath().time().'.jpg';
				if(copy($sourcePath,$tempPath)) $tempSourcePath = $tempPath;
			}
			if($imgSize = getimagesize($tempSourcePath)){
				if(is_dir($targetPath)){
					$sourceWidth = $imgSize[0];
					$sourceHeight = $imgSize[1];

					$sourceImg = imagecreatefromjpeg($tempSourcePath);  

					if($sourceImg){
						//Create thumbnail
						if(strtolower(substr($fileName,-4)) == '.jpg"'){
							$tnFileName = str_ireplace(".jpg","_tn.jpg",$fileName);
							if(strpos($tnFileName,' ')) $tnFileName = str_replace(' ','',$tnFileName);
							if(strpos($tnFileName,'%20')) $tnFileName = str_replace('%20','',$tnFileName);
						}
						else{
							$tnFileName = 'imgid-'.$imgId."_tn.jpg";
						}
						$imgCnt = 1;
						while(file_exists($targetPath.$tnFileName)){
							$tnFileName = substr($tnFileName,0,strrpos($tnFileName,'_')).'_'.$imgCnt.'-tn.jpg';
							$imgCnt++;
						}
						
						$newTnHeight = round($sourceHeight*($this->tnPixWidth/$sourceWidth));
			        	
			    		$tmpTnImg = imagecreatetruecolor($this->tnPixWidth,$newTnHeight);
						imagecopyresampled($tmpTnImg,$sourceImg,0,0,0,0,$this->tnPixWidth, $newTnHeight,$sourceWidth,$sourceHeight);
			        	if(!imagejpeg($tmpTnImg, $targetPath.$tnFileName)){
			        		if($this->verbose) echo "<li style='margin-left:5px;color:red;'>Failed to write JPG: $targetPath.$tnFileName</li>";
			        	}
					    imagedestroy($tmpTnImg);
					    
					    if(file_exists($targetPath.$tnFileName)){
					    	//If web image is too large, transfer to large image and create new web image
						    $lgFileName = '';
						    $webFileName = '';
						    $fileSize = 0;
						    if(!$webIsEmpty){
							    if(strtolower(substr($tempSourcePath,0,7)) == 'http://'){
							    	$fileSize = $this->getRemoteFileSize($tempSourcePath);
							    }
							    else{
							    	$fileSize = filesize($tempSourcePath);
							    }
						    }
						    if($webIsEmpty || (!$origUrl && $fileSize > $this->imgFileSizeLimit)){
					    		$lgFileName = $imgUrl;
								if(strtolower(substr($fileName,-4)) == '.jpg"'){
					    			$webFileName = str_ireplace(".jpg","_web.jpg",$fileName);
									if(strpos($webFileName,' ')) $webFileName = str_replace(' ','',$webFileName);
									if(strpos($webFileName,'%20')) $webFileName = str_replace('%20','',$webFileName);
								}
								else{
									$webFileName = 'imgid-'.$imgId.'_web.jpg';
								}
								$imgCnt = 1;
								while(file_exists($targetPath.$webFileName)){
									$webFileName = substr($webFileName,0,strrpos($webFileName,'_')).'_'.$imgCnt.'-web.jpg';
									$imgCnt++;
								}

					    		$newWebHeight = round($sourceHeight*($this->webPixWidth/$sourceWidth));

					    		$tmpWebImg = imagecreatetruecolor($this->webPixWidth,$newWebHeight);
								imagecopyresampled($tmpWebImg,$sourceImg,0,0,0,0,$this->webPixWidth, $newWebHeight,$sourceWidth,$sourceHeight);
					        	if(!imagejpeg($tmpWebImg, $targetPath.$webFileName)){
					        		if($webIsEmpty){
					        			$webFileName = $imgUrl;
					        		}
					        		else{
										$webFileName = '';
					        		}
					        		$lgFileName = '';
					        		if($this->verbose) echo "<div style='margin-left:10px;color:red;'>Failed to write JPG: $targetPath.$webFileName</div>";
					        	}
							    imagedestroy($tmpWebImg);
						    }

						    //If central images are on remote server and new one stored locally, then we need to use full domain
					    	if($GLOBALS['imageDomain'] && substr($targetUrl,0,1) == '/'){
					    		$targetUrl = 'http://'.$_SERVER['HTTP_HOST'].$targetUrl;
					    	}
						    //Insert urls into database
					    	$webFullUrl = '';
					    	if($webFileName && $webFileName != $fileName){
					    		if(strtolower(substr($webFileName,0,4)) != "http"){
					    			$webFullUrl = $targetUrl;
					    		}
					    		$webFullUrl .= $webFileName;
					    	}
						    $lgFullUrl = '';
						    if($lgFileName){
					    		if(strtolower(substr($lgFileName,0,4)) != "http"){
					    			$lgFullUrl = $targetUrl;
					    		}
						    	$lgFullUrl .= $lgFileName;
						    }
	
					    	$sql = 'UPDATE images ti SET ti.thumbnailurl = "'.$targetUrl.$tnFileName.'" ';
					    	if($webFullUrl){
					    		$sql .= ',url = "'.$webFullUrl.'" ';
					    	}
					    	if($lgFullUrl){
					    		$sql .= ',originalurl = "'.$lgFullUrl.'" ';
					    	}
					    	
					    	$sql .= "WHERE ti.imgid = ".$imgId;
					    	//echo $sql;
						    $this->conn->query($sql);
						    $statusStr = 'Done!';
						    //Final cleanup
						    imagedestroy($sourceImg);
						}
					}
					else{
						if($this->verbose) echo '<div style="margin-left:10px;">ERROR: Unable to create source image object</div>';
					}
					if($tempSourcePath != $sourcePath) unlink($tempSourcePath);
				}
				else{
					if($this->verbose) echo '<div style="margin-left:10px;">ERROR: Bad target path: '.$targetPath.'</div>';
				}
			}
			else{
				if($this->verbose) echo '<div style="margin-left:10px;">ERROR: Bad source path: '.$sourcePath.'</div>';
			}

			if($this->verbose) echo $statusStr.'</li>';
		}
	}

	private function removeSpacesFromThumbnail($imgId, $url){
		$imgUrl = str_replace("%20"," ",$url);
		$filePath = str_replace($this->urlPath,$this->rootPathBase,$imgUrl);
		$newPath = str_replace(" ","_", $filePath);
		$newPath = str_replace(Array("(",")"),"",$newPath);
		$newPath = str_replace("JPG","jpg",$newPath);
		$newUrl = str_replace($this->rootPathBase,$this->urlPath,$newPath);
		if($filePath != $newPath){
			if(!file_exists($newPath)){
				if(rename($filePath, $newPath)){
			    	$sql = "UPDATE images ti SET ti.url = '".$newUrl."' WHERE ti.imgid = ".$imgId;
				    if($this->conn->query($sql)){
						if($this->verbose) echo "<li style='margin-left:5px;'><b>Image file ($imgId) renamed</b> from $imgUrl to $newUrl</li>";
					    return $newUrl;
				    }
				    else{
				    	if($this->verbose) echo "<li style='margin-left:5px;color:red;'><b>ERROR:</b> Image file ($imgId) rename successful but database update failed. Please repair.</li>";
				    }
				}
			    else{
			    	if($this->verbose) echo "<li style='margin-left:5px;color:red;'><b>ERROR:</b> Unable to rename image file $filePath (imgid = $imgId)</li>";
			    }
			}
			else{
			    if($this->verbose) echo "<li style='margin-left:5px;color:red;'><b>ERROR:</b> Unable t rename file. New file already exists: $newPath</li>";
			}
		}
		return "";
	}

	public function setVerbose($verb){
		$this->verbose = $verb;
	}
	
	private function getTempPath(){
		$tPath = $GLOBALS["tempDirRoot"];
		if(!$tPath){
			$tPath = ini_get('upload_tmp_dir');
		}
		if(!$tPath){
			$tPath = $GLOBALS["serverRoot"]."/temp";
		}
		if(substr($tPath,-1) != '/' && substr($tPath,-1) != "\\"){
			$tPath .= '/';
		}
		return $tPath;
	}

	private function getRemoteFileSize($remoteFile){
		$ch = curl_init($remoteFile);
		curl_setopt($ch, CURLOPT_NOBODY, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HEADER, true);
		$data = curl_exec($ch);
		curl_close($ch);
		if($data === false) {
			return 0;
		}
		
		$contentLength = 0;
		if(preg_match('/Content-Length: (\d+)/', $data, $matches)) {
		  $contentLength = (int)$matches[1];
		}
		return $contentLength;
	}
}
?>