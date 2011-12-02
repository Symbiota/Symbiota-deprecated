<?php
class SpecProcessorImage extends SpecProcessorManager{

	private $sourceGdImg;
	private $sourceImagickImg;
	private $exif;
	private $errArr = array();

	function __construct($logPath){
		parent::__construct($logPath);
	}

 	public function __destruct(){
 		parent::__destruct();
 	}
 	
	public function batchLoadImages(){
		//Create log Files
		if(file_exists($this->logPath)){
			$lPath = $this->logPath;
			if(!file_exists($lPath.'specprocessor/')){
				if(mkdir($lPath.'specprocessor/')){
					$lPath .= 'specprocessor/';
				}
			}
			$logFile = $lPath."log_".date('Ymd').".log";
			$errFile = $lPath."logErr_".date('Ymd').".log";
			$this->logFH = fopen($logFile, 'a');
			$this->logErrFH = fopen($errFile, 'a');
			if($this->logFH) fwrite($this->logFH, "DateTime: ".date('Y-m-d h:i:s A')."\n");
			if($this->logErrFH) fwrite($this->logErrFH, "DateTime: ".date('Y-m-d h:i:s A')."\n");
		}
		//If output is to go out to file, create file for output
		if(!$this->dbMetadata){
			$mdFileName = "output_".time().'.csv';
			$this->mdOutputFH = fopen($mdFileName, 'w');
			fwrite($this->mdOutputFH, '"collid","dbpk","url","thumbnailurl","originalurl"'."\n");
			if($this->mdOutputFH){
				echo "Image Metadata written out to CSV file: '".$mdFileName."' (same folder as script) \n";
			}
			else{
				//If unable to create output file, abort upload procedure
				if($this->logFH){
					fwrite($this->logFH, "Image upload aborted: Unable to establish connection to output file to where image metadata is to be written\n\n");
					fclose($this->logFH);
				}
				if($this->logErrFH){
					fwrite($this->logErrFH, "Image upload aborted: Unable to establish connection to output file to where image metadata is to be written\n\n");
					fclose($this->logErrFH);
				}
				echo "<li>Image upload aborted: Unable to establish connection to output file to where image metadata is to be written</li>\n";
				return;
			}
		}

		if($this->targetPath){
			//Lets start processing folder
			echo "<li>Starting Image Processing</li>\n";
			$this->processFolder();
			echo "<li>Image upload complete</li>\n";
		}

		//Now lets start closing things up
		//First some data cleaning
		if($this->dbMetadata && $this->conn){
			$sql = 'UPDATE images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
				'SET i.tid = o.tidinterpreted '.
				'WHERE i.tid IS NULL and o.tidinterpreted IS NOT NULL';
			$this->conn->query($sql);
		}
		//Close database or MD output file
		if(!$this->dbMetadata){
			fclose($this->mdOutputFH);
		}
		if($this->logFH){
			fwrite($this->logFH, "Image upload complete\n");
			fwrite($this->logFH, "----------------------------\n\n");
			fclose($this->logFH);
		}
		if($this->logErrFH){
			fwrite($this->logErrFH, "----------------------------\n\n");
			fclose($this->logErrFH);
		}
	}

	private function processFolder($pathFrag = ''){
		set_time_limit(2000);
		if(!$this->sourcePath) $this->sourcePath = './';
		//Read file and loop through images
		if($imgFH = opendir($this->sourcePath.$pathFrag)){
			while($fileName = readdir($imgFH)){
				if($fileName != "." && $fileName != ".." && $fileName != ".svn"){
					if(is_file($this->sourcePath.$pathFrag.$fileName)){
						if(stripos($fileName,'_tn.jpg') === false && stripos($fileName,'_lg.jpg') === false){
							$fileExt = strtolower(substr($fileName,strrpos($fileName,'.')));
							if($fileExt == ".tif"){
								//Do something, like convert to jpg
							}
							if($fileExt == ".jpg"){
								
								$this->processImageFile($fileName,$pathFrag);
								
	        				}
							else{
								//echo "<li style='margin-left:10px;'><b>Error:</b> File skipped, not a supported image file: ".$file."</li>";
								if($this->logErrFH) fwrite($this->logErrFH, "\tERROR: File skipped, not a supported image file: ".$fileName." \n");
								//fwrite($this->logFH, "\tERROR: File skipped, not a supported image file: ".$file." \n");
							}
						}
					}
					elseif(is_dir($this->sourcePath.$pathFrag.$fileName)){
						$this->processFolder($pathFrag.$fileName."/");
					}
        		}
			}
		}
   		closedir($imgFH);
	}

	private function processImageFile($fileName,$pathFrag = ''){
		echo "<li>Processing image ".$fileName."</li>\n";
		if($this->logFH) fwrite($this->logFH, "Processing image (".date('Y-m-d h:i:s A')."): ".$fileName."\n");
		ob_flush();
		flush();
		//Grab Primary Key
		$specPk = '';
		if($this->specKeyRetrieval == 'ocr'){
        	//OCR process image and grab primary key from OCR return
        	$labelBlock = $this->ocrImage();
        	$specPk = $this->getPrimaryKey($fileName);
        	if($specPk){
        		//Get occid (Symbiota occurrence record primary key)
        	}
        }
		else{
			//Grab Primary Key from filename
			$specPk = $this->getPrimaryKey($fileName);
			if($specPk){
				//Get occid (Symbiota occurrence record primary key)
        	}
		}
		$occId = 0;
		if($this->dbMetadata){
			$occId = $this->getOccId($specPk);
		}
        //If Primary Key is found, continue with processing image
        if($specPk){
        	if($occId || !$this->dbMetadata){
	        	//Setup path and file name in prep for loading image
				$targetFolder = '';
	        	if($pathFrag){
					$targetFolder = $pathFrag;
				}
				else{
					$targetFolder = substr($specPk,0,strlen($specPk)-3).'/';
				}
				$targetPath = $this->targetPath.$targetFolder;
				if(!file_exists($targetPath)){
					mkdir($targetPath);
				}
	        	$targetFileName = $fileName;
				//Check to see if image already exists at target, if so, delete or rename
	        	if(file_exists($targetPath.$targetFileName)){
					if($this->copyOverImg){
	        			unlink($targetPath.$targetFileName);
	        			if(file_exists($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."tn.jpg")){
	        				unlink($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."tn.jpg");
	        			}
	        			if(file_exists($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."_tn.jpg")){
	        				unlink($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."_tn.jpg");
	        			}
	        			if(file_exists($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."lg.jpg")){
	        				unlink($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."lg.jpg");
	        			}
	        			if(file_exists($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."_lg.jpg")){
	        				unlink($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."_lg.jpg");
	        			}
					}
					else{
						//Rename image before saving
						$cnt = 1;
				 		while(file_exists($targetPath.$targetFileName)){
				 			$targetFileName = str_ireplace(".jpg","_".$cnt.".jpg",$fileName);
				 			$cnt++;
				 		}
					}
				}
				//Start the processing procedure
				list($width, $height) = getimagesize($this->sourcePath.$pathFrag.$fileName);
				echo "<li style='margin-left:10px;'>Loading image</li>\n";
				if($this->logFH) fwrite($this->logFH, "\nLoading image (".date('Y-m-d h:i:s A').")\n");
				ob_flush();
				flush();
				
				//Create web image
				$webImgCreated = false;
				$fileSize = 0;
				if($this->createWebImg){
					if($width > $this->webPixWidth){
						$webImgCreated = $this->createNewImage($this->sourcePath.$pathFrag.$fileName,$targetPath.$targetFileName,$this->webPixWidth,round($this->webPixWidth*$height/$width),$width,$height);
					}
					else{
						$fileSize = filesize($this->sourcePath.$pathFrag.$fileName);
						if($fileSize && $fileSize > $this->webMaxFileSize){
							$webImgCreated = $this->createNewImage($this->sourcePath.$pathFrag.$fileName,$targetPath.$targetFileName,$width,$height,$width,$height,80);
						}
						else{
							$webImgCreated = copy($this->sourcePath.$pathFrag.$fileName,$targetPath.$targetFileName);
						}
					}
				}
				else{
					$webImgCreated = copy($this->sourcePath.$pathFrag.$fileName,$targetPath.$targetFileName);
				}
				if($webImgCreated){
	        		//echo "<li style='margin-left:10px;'>Web image copied to target folder</li>";
					if($this->logFH) fwrite($this->logFH, "\tWeb image copied to target folder (".date('Y-m-d h:i:s A').") \n");
					$tnUrl = "";$lgUrl = "";
					//Create Large Image
					$lgTargetFileName = substr($targetFileName,0,strlen($targetFileName)-4)."_lg.jpg";
					if($this->createLgImg){
						if($width > ($this->webPixWidth*1.3)){
							if($width < $this->lgPixWidth){
								if(!$fileSize) $fileSize = filesize($this->sourcePath.$pathFrag.$fileName);
								if($fileSize && $fileSize > $this->lgMaxFileSize){
									if($this->createNewImage($this->sourcePath.$pathFrag.$fileName,$targetPath.$lgTargetFileName,$width,$height,$width,$height,80)){
										$lgUrl = $lgTargetFileName;
									}
								}
								else{
									if(copy($this->sourcePath.$pathFrag.$fileName,$targetPath.$lgTargetFileName)){
										$lgUrl = $lgTargetFileName;
									}
								}
							}
							else{
								if($this->createNewImage($this->sourcePath.$pathFrag.$fileName,$targetPath.$lgTargetFileName,$this->lgPixWidth,round($this->lgPixWidth*$height/$width),$width,$height)){
									$lgUrl = $lgTargetFileName;
								}
							}
						}
					}
					else{
						//If large image was supplied, transfer to storage
						$lgSourceFileName = substr($fileName,0,strlen($fileName)-4).'_lg'.substr($fileName,strlen($fileName)-4);
						if(file_exists($this->sourcePath.$pathFrag.$lgSourceFileName)){
							if(rename($this->sourcePath.$pathFrag.$lgSourceFileName,$targetPath.$lgTargetFileName)){
								$lgUrl = $lgTargetFileName;
							}
						}
					}
					//Create Thumbnail Image
					$tnTargetFileName = substr($targetFileName,0,strlen($targetFileName)-4)."_tn.jpg";
					if($this->createTnImg){
						if($this->createNewImage($this->sourcePath.$pathFrag.$fileName,$targetPath.$tnTargetFileName,$this->tnPixWidth,round($this->tnPixWidth*$height/$width),$width,$height)){
							$tnUrl = $tnTargetFileName;
						}
					}
					else{
						//If thumbnail image was supplied, transfer to storage
						$tnFileName = substr($fileName,0,strlen($fileName)-4).'_tn'.substr($fileName,strlen($fileName)-4);
						if(file_exists($this->sourcePath.$pathFrag.$tnFileName)){
							if(rename($this->sourcePath.$pathFrag.$tnFileName,$targetPath.$tnTargetFileName)){
								$tnUrl = $tnTargetFileName;
							}
						}
					}
					if($tnUrl) $tnUrl = $targetFolder.$tnUrl;
					if($lgUrl) $lgUrl = $targetFolder.$lgUrl;
					if($this->recordImageMetadata(($this->dbMetadata?$occId:$specPk),$targetFolder.$targetFileName,$tnUrl,$lgUrl)){
						if(file_exists($this->sourcePath.$pathFrag.$fileName)) unlink($this->sourcePath.$pathFrag.$fileName);
						echo "<li style='margin-left:20px;'>Image processed successfully!</li>\n";
						if($this->logFH) fwrite($this->logFH, "\tImage processed successfully (".date('Y-m-d h:i:s A').")!\n");
					}
				}

				if($this->sourceGdImg){
					imagedestroy($this->sourceGdImg);
					$this->sourceGdImg = null;
				}
				if($this->sourceImagickImg){
					$this->sourceImagickImg->clear();
					$this->sourceImagickImg = null;
				}
				
        	}
		}
		else{
			if($this->logErrFH) fwrite($this->logErrFH, "\tERROR: File skipped, unable to extract specimen identifier (".date('Y-m-d h:i:s A').") \n");
			if($this->logFH) fwrite($this->logFH, "\tFile skipped, unable to extract specimen identifier (".date('Y-m-d h:i:s A').") \n");
			echo "<li style='margin-left:10px;'>File skipped, unable to extract specimen identifier</li>\n";
		}
		ob_flush();
		flush();
	}

	private function createNewImage($sourcePath, $targetPath, $newWidth, $newHeight, $sourceWidth, $sourceHeight, $c = 0){
		global $useImageMagick;
		$status = false;
		
		if($this->processUsingImageMagick) {
			// Use ImageMagick to resize images 
			$status = $this->createNewImageImagick($sourcePath,$targetPath,$newWidth,$newHeight,$sourceWidth,$sourceHeight,$c);
		} 
		elseif(extension_loaded('gd') && function_exists('gd_info')) {
			// GD is installed and working 
			$status = $this->createNewImageGD($sourcePath,$targetPath,$newWidth,$newHeight,$sourceWidth,$sourceHeight,$c);
		}
		else{
			// Neither ImageMagick nor GD are installed 
			$this->errArr[] = 'No appropriate image handler for image conversions';
		}
		return $status;
	}
	
	private function createNewImageImagick($sourceImg,$targetPath,$newWidth, $c = 0){
		$status = false;
		if(!$c) $c = $this->jpgCompression;
		$ct;
		if($newWidth < 300){
			$ct = system('convert '.$sourceImg.' -thumbnail '.$newWidth.'x'.($newWidth*1.5).' '.$targetPath, $retval);
		}
		else{
			$ct = system('convert '.$sourceImg.' -resize '.$newWidth.'x'.($newWidth*1.5).($c?' -quality '.$c:'').' '.$targetPath, $retval);
		}
		if(file_exists($targetPath)){
			$status = true;
		}
		return $status;
	}
	
	private function createNewImageGD($sourcePath, $targetPath, $newWidth, $newHeight, $sourceWidth, $sourceHeight, $c = 0){
		$status = false;
		if(!$c) $c = $this->jpgCompression;
		
	   	if(!$this->sourceGdImg){
	   		$this->sourceGdImg = imagecreatefromjpeg($sourcePath);
			if(class_exists('PelJpeg')){
				$inputJpg = new PelJpeg($sourcePath);
				$this->exif = $inputJpg->getExif();
			}

	   	}
		
		ini_set('memory_limit','512M');
		$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
		//imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth,$newHeight,$sourceWidth,$sourceHeight);
		imagecopyresized($tmpImg,$this->sourceGdImg,0,0,0,0,$newWidth,$newHeight,$sourceWidth,$sourceHeight);

		if($c){
			$status = imagejpeg($tmpImg, $targetPath, $c);
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
				$status = $outputJpg->saveFile($targetPath);
			}
			else{
				$status = imagejpeg($tmpImg, $targetPath);
			}
		}
		
		if(!$status){
			if($this->logErrFH) fwrite($this->logErrFH, "\tError: Unable to resize and write file: ".$targetPath."\n");
			echo "<li style='margin-left:20px;'><b>Error:</b> Unable to resize and write file: $targetPath</li>\n";
		}
		
		imagedestroy($tmpImg);
		return $status;
	}
}
?>
 