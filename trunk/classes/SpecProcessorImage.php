<?php
/*
 * Built 3 Feb 2011
 * By E.E. Gilbert
 */
 
class SpecProcessorImage extends SpecProcessorManager{

	function __construct() {
 		parent::__construct();
		
	}
	
	public function batchLoadImages($mTn,$mLarge){
		//Create log Files
		if(file_exists($this->logPath)){
			if(!file_exists($this->logPath.'specprocessor/')) mkdir($this->logPath.'specprocessor/');
			if(file_exists($this->logPath.'specprocessor/')){
				$logFile = $this->logPath."specprocessor/log_".date('Ymd').".log";
				$errFile = $this->logPath."specprocessor/logErr_".date('Ymd').".log";
				$this->logFH = fopen($logFile, 'a') 
					or die("Can't open file: ".$logFile);
				$this->logErrFH = fopen($errFile, 'a') 
					or die("Can't open file: ".$errFile);
				fwrite($this->logFH, "DateTime: ".date('Y-m-d h:i:s A')."\n");
				fwrite($this->logErrFH, "DateTime: ".date('Y-m-d h:i:s A')."\n");
			}
		}
		echo "<li>Starting Image Processing</li>";
		$this->processFolder();
		echo "<li>Image upload complete</li>";
		fwrite($this->logFH, "Image upload complete\n");
		fwrite($this->logFH, "----------------------------\n\n");
		fwrite($this->logErrFH, "----------------------------\n\n");
		fclose($this->logFH);
		fclose($this->logErrFH);
	}
	
	private function processFolder($pathFrag = ''){
		global $imageRootPath;
		set_time_limit(800);
		if($imgFH = opendir($this->projVars['sourcepath'].$pathFrag)){
			$targetPath = '';
			while($file = readdir($imgFH)){
        		if($file != "." && $file != ".."){
        			if(is_file($this->projVars['sourcepath'].$pathFrag.$file)){
						if(!$targetPath){
							$targetPath = $this->projVars['targetpath'];
							if(!targetPath){
								$targetpath = $imageRootPath;
							}
							if($pathFrag){
								$targetPath .= $pathFrag;
								if(!file_exists($targetPath)){ 
									mkdir($targetPath);
								}
							}
							else{
								$folderName = '';
								$specPattern = $this->projVars['speckeypattern'];
								if(preg_match($specPattern,$file,$matchArr)){
									if(preg_match('/(\d{3,4,5})\d{3}^\D*/',$matchArr[1],$matchArr2)){
										$folderName = 'spec'.$matchArr[1];
									}
								}
								if(!$folderName){
									$folderName = 'spec'.date('Ym');
								}
								$targetPath .= $folderName.'/';
								if(!file_exists($targetPath)){ 
									mkdir($targetPath);
								}
							}
						}
						$fileExt = substr($file,-4);
						if(($fileExt == ".tif") || ($fileExt == ".TIF")){
							//Do something, like turn into a jpg for ocr processing
						}
        				elseif(($fileExt == ".jpg") || ($fileExt == ".JPG")){
							if(file_exists($targetPath.$file)){
								//If image already exists at target, delete for replacement
	        					unlink($targetPath.$file);
	        					unlink($targetPath.substr($file,0,strlen($file)-4)."tn.jpg");
	        					unlink($targetPath.substr($file,0,strlen($file)-4)."lg.jpg");
							}
							list($width, $height) = getimagesize($this->projVars['sourcepath'].$pathFrag.$file);
							echo "<li>Start loading: ".$file."</li>";
							fwrite($this->logFH, "Start loading: ".$file."\n");
							//Create web image
							$webImgCreated = false;
							$webPixWidth = $GLOBALS['webPixWidth']?$GLOBALS['webPixWidth']:1200;
							$tnPixWidth = $GLOBALS['tnPixWidth']?$GLOBALS['tnPixWidth']:130;
							$lgPixWidth = $GLOBALS['lgPixWidth']?$GLOBALS['lgPixWidth']:2400;
							if($width > ($webPixWidth*1.2)){
								$webImgCreated = $this->resizeImage($file,$targetPath.$file,$webPixWidth,round($webPixWidth*$height/$width),$width,$height);
							}
							else{
								$webImgCreated = copy($this->projVars['sourcepath'].$pathFrag.$file,$targetPath.$file);
							}
							if($webImgCreated){
	        					//echo "<li style='margin-left:10px;'>Web image copied to target folder</li>";
								fwrite($this->logFH, "\tWeb image copied to target folder\n");
								$tnUrl = "";$oUrl = "";
								//Create Large Image
								if($this->mLarge && $width > ($webPixWidth*1.2)){
									if($width < ($largePixWidth*1.2)){
										if(copy($this->projVars['sourcepath'].$pathFrag.$file,$targetPath.substr($file,0,strlen($file)-4)."lg.jpg")){
											$oUrl = substr($file,0,strlen($file)-4)."lg.jpg";
										}
									}
									else{
										if($this->resizeImage($file,$targetPath.substr($file,0,strlen($file)-4)."lg.jpg",$largePixWidth,round($largePixWidth*$height/$width),$width,$height)){
											$oUrl = substr($file,0,strlen($file)-4)."lg.jpg";
										}
									}
								}
								//Create Thumbnail Image
								if($this->mTn){
									if($this->resizeImage($file,$targetPath.substr($file,0,strlen($file)-4)."tn.jpg",$tnPixWidth,round($tnPixWidth*$height/$width),$width,$height)){
										$tnUrl = substr($file,0,strlen($file)-4)."tn.jpg";
									}
								}
								if($this->insertImageInDB($folderName.$file,$folderName.$tnUrl,$folderName.$oUrl)){
									if(file_exists($this->projVars['sourcepath'].$pathFrag.$file)) unlink($this->projVars['sourcepath'].$pathFrag.$file);
									echo "<li>Success!</li>";
									fwrite($this->logFH, "\tSuccess!\n");
								}
							}
						}
						else{
        					echo "<li style='margin-left:10px;'><b>Error:</b> File skipped, not a supported image file: ".$file."</li>";
							fwrite($this->logErrFH, "Error: File skipped, not a supported image file: ".$file." \n");
						}
					}
					elseif(is_dir($this->projVars['sourcepath'].$pathFrag.$file)){
						$this->processFolder($pathFrag.$file."/");
					}
        		}
			}
		}
   		closedir($imgFH);
	}
	
	private function resizeImage($sourceName, $targetPath, $newWidth, $newHeight, $oldWidth, $oldHeight){
		$status = false;
       	$sourceImg = imagecreatefromjpeg($this->projVars['sourcepath'].$sourceName);
   		$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
		imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth,$newHeight,$oldWidth,$oldHeight);
        if(imagejpeg($tmpImg, $targetPath)){
        	$status = true;
        }
        else{
			fwrite($this->logErrFH, "\tError: Unable to resize and write file: ".$targetPath."\n");
        	echo "<li style='margin-left:20px;'><b>Error:</b> Unable to resize and write file: $targetPath</li>";
        }
		imagedestroy($tmpImg);
		return $status;
	}

	private function insertImageInDB($webFileName,$tnUrl,$oUrl){
		$status = false;
        //echo "<li style='margin-left:20px;'>About to load record into database</li>";
		fwrite($this->logFH, "\tAbout to load record into database\n");
		if(preg_match('/\/(ASU\d{7})[^.]+\.(jpg|JPG)/',$webFileName,$matchArr)){
			$barcode = $matchArr[1];
			//Get dbsn for target record
			$dbsn = 0;
			$imgUrls = Array();
			$sqlStr = "SELECT s.dbsn, p.hyperlink FROM tbl_specimens s LEFT JOIN tbl_photos p ON s.dbsn = p.dbsn ".
				"WHERE s.barcode = '".$barcode."'";
			$rs = $this->conn->query($sqlStr);
			while($row = $rs->fetch_object()){
				$dbsn = $row->dbsn;
				$imgUrls[] = $row->hyperlink;
			}
			if($dbsn){
				if(!in_array($this->projVars['imgurl'].$webFileName,$imgUrls)){
					$sql = "INSERT tbl_photos(dbsn,hyperlink";
					if($tnUrl) $sql .= ",thumbnailurl";
					if($oUrl) $sql .= ",originalurl"; 
					$sql .= ") VALUES (".$dbsn.", '".$this->projVars['imgurl'].$webFileName."' ";
					if($tnUrl) $sql .= ", '".$this->projVars['imgurl'].$tnUrl."' ";
					if($oUrl) $sql .= ", '".$this->projVars['imgurl'].$oUrl."' ";
					$sql .= ")";
					$status = $this->conn->query($sql);
					if($status){
				        //echo "<li style='margin-left:20px;'>Record successfully loaded into database</li>";
						fwrite($this->logFH, "\tRecord successfully loaded into database\n");
					}
					else{
						fwrite($this->logFH, "\tError: unable to load record into database\n");
						fwrite($this->logErrFH, "\tError: Unable to load image record into database. ".$this->conn->error." SQL: ".$sql."\n");
			        	echo "<li style='margin-left:20px;'><b>Error:</b> Unable to load image record into database. ".$this->conn->error."</li>";
					}
				}
				else{
					fwrite($this->logFH, '\Notice: '.$webFileName.' image record already mapped in database\n');
					fwrite($this->logErrFH, '\Notice: '.$webFileName.' image record already mapped in database\n');
			        echo "<li style='margin-left:20px;'><b>Notice:</b> ".$webFileName." image record already mapped in database ".$this->conn->error."</li>";
			        $status = true;
				}
			}
			else{
				fwrite($this->logFH, "\tError: unable to load record. Specimen ".$barcode." not in ASU herbarium database. \n");
				fwrite($this->logErrFH, "\tError: Unable to load image record into database. Specimen ".$barcode." not in ASU herbarium database.\n");
	        	echo "<li style='margin-left:20px;'><b>Error:</b> Unable to load image record into database. Specimen ".$barcode." not in ASU herbarium database. </li>";
			}
		}
		else{
			fwrite($this->logFH, "\tERROR: unable to extract barcode from file name (".$webFileName."). \n");
			fwrite($this->logErrFH, "\tERROR: unable to extract barcode from file name (".$webFileName."). \n");
        	echo "<li style='margin-left:20px;'><b>ERROR:</b> unable to extract barcode from file name (".$webFileName."). </li>";
		}
		return $status;
	}
}
?>
 