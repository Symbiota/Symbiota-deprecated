<?php
 
class SpecProcessorImage extends SpecProcessorManager{

	function __construct($logPath){
 		parent::__construct($logPath);
	}

	public function batchLoadImages(){
		//Create log Files
		if(file_exists($this->logPath)){
			if(!file_exists($this->logPath.'specprocessor/')) mkdir($this->logPath.'specprocessor/');
			if(file_exists($this->logPath.'specprocessor/')){
				$logFile = $this->logPath."specprocessor/log_".date('Ymd').".log";
				$errFile = $this->logPath."specprocessor/logErr_".date('Ymd').".log";
				$this->logFH = fopen($logFile, 'a');
				$this->logErrFH = fopen($errFile, 'a');
				if($this->logFH) fwrite($this->logFH, "DateTime: ".date('Y-m-d h:i:s A')."\n");
				if($this->logErrFH) fwrite($this->logErrFH, "DateTime: ".date('Y-m-d h:i:s A')."\n");
			}
		}
		echo "<li>Starting Image Processing</li>";
		$this->processFolder();
		echo "<li>Image upload complete</li>";
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
		set_time_limit(800);
		$sourcePath = $this->sourcePath;
		$webPixWidth = $this->webPixWidth?$this->webPixWidth:1200;
		$tnPixWidth = $this->tnPixWidth?$this->tnPixWidth:130;
		$lgPixWidth = $this->lgPixWidth?$this->lgPixWidth:2400;
		if($imgFH = opendir($sourcePath.$pathFrag)){
			while($file = readdir($imgFH)){
        		if($file != "." && $file != ".." && $file != ".svn"){
        			if(is_file($sourcePath.$pathFrag.$file)){
						$fileExt = strtolower(substr($file,strrpos($file,'.')));
        				if($fileExt == ".tif"){
							//Do something, like convert to jpg 
						}
						if($fileExt == ".jpg"){
							//Grab Primary Key
							$specPk = '';
							if($this->specKeyRetrieval == 'filename'){
								//Grab Primary Key from filename
								$specPk = $this->getPrimaryKey($file);
	        					if($specPk){
									//Get occid (Symbiota occurrence record primary key)
	        					}
							}
	        				elseif($this->specKeyRetrieval == 'ocr'){
	        					//OCR process image and grab primary key from OCR return
	        					$labelBlock = $this->ocrImage();
	        					$specPk = $this->getPrimaryKey($file);
	        					if($specPk){
		        					//Get occid (Symbiota occurrence record primary key)
	        					}
	        				}
	        				//If Primary Key is found, continue with processing image
	        				if($specPk){
	        					//Setup path and file name in prep for loading image
		        				$targetPath = $this->targetPath;
								$targetFolder = '';
		        				if($pathFrag){
									$targetFolder = $pathFrag;
								}
								else{
									$targetFolder = substr($specPk,0,strlen($specPk)-3).'/';
								}
								$targetPath .= $targetFolder;
								if(!file_exists($targetPath)){
									mkdir($targetPath);
								}
	        					$targetFileName = $file;
								if(file_exists($targetPath.$targetFileName)){
									//Image already exists at target
									if($this->copyOverImg){
			        					unlink($targetPath.$targetFileName);
			        					if(file_exists($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."tn.jpg")){
				        					unlink($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."tn.jpg");
			        					}
			        					if(file_exists($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."lg.jpg")){
			        						unlink($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."lg.jpg");
			        					}
									}
									else{
										//Rename image before saving
										$cnt = 1;
								 		while(file_exists($targetPath.$targetFileName)){
								 			$targetFileName = str_ireplace(".jpg","_".$cnt.".jpg",$file);
								 			$cnt++;
								 		}
									}
								}
								list($width, $height) = getimagesize($sourcePath.$pathFrag.$file);
								echo "<li>Starting to load: ".$file."</li>";
								if($this->logFH) fwrite($this->logFH, "Starting to load: ".$file."\n");
								//Create web image
								$webImgCreated = false;
								if($width > $webPixWidth){
									$webImgCreated = $this->createNewImage($sourcePath.$pathFrag.$file,$targetPath.$targetFileName,$webPixWidth,round($webPixWidth*$height/$width),$width,$height);
								}
								else{
									$webImgCreated = copy($sourcePath.$pathFrag.$file,$targetPath.$targetFileName);
								}
								if($webImgCreated){
		        					//echo "<li style='margin-left:10px;'>Web image copied to target folder</li>";
									if($this->logFH) fwrite($this->logFH, "\tWeb image copied to target folder\n");
									$tnUrl = "";$lgUrl = "";
									//Create Large Image
									if(array_key_exists($mapLarge) && $width > ($webPixWidth*1.2)){
										$lgTargetFileName = substr($targetFileName,0,strlen($targetFileName)-4)."lg.jpg";
										if($width < $lgPixWidth){
											if(copy($sourcePath.$pathFrag.$file,$targetPath.$lgTargetFileName)){
												$lgUrl = $lgTargetFileName;
											}
										}
										else{
											if($this->createNewImage($sourcePath.$pathFrag.$file,$targetPath.$lgTargetFileName,$lgPixWidth,round($lgPixWidth*$height/$width),$width,$height)){
												$lgUrl = $lgTargetFileName;
											}
										}
									}
									//Create Thumbnail Image
									if(array_key_exists($mapTn)){
										$tnTargetFileName = substr($targetFileName,0,strlen($targetFileName)-4)."tn.jpg";
										if($this->createNewImage($sourcePath.$pathFrag.$file,$targetPath.$tnTargetFileName,$tnPixWidth,round($tnPixWidth*$height/$width),$width,$height)){
											$tnUrl = $tnTargetFileName;
										}
									}
									if($tnUrl) $tnUrl = $targetFolder.$tnUrl;
									if($lgUrl) $lgUrl = $targetFolder.$lgUrl;
									if($this->recordImageMetadata($specPk,$targetFolder.$targetFileName,$tnUrl,$lgUrl)){
										if(file_exists($sourcePath.$pathFrag.$file)) unlink($sourcePath.$pathFrag.$file);
										echo "<li style='margin-left:20px;'>Image processed successfully!</li>";
										if($this->logFH) fwrite($this->logFH, "\tImage processed successfully!\n");
									}
								}
							}
							else{
								if($this->logErrFH) fwrite($this->logErrFH, "\tERROR: File skipped, unable to locate specimen record \n");
								if($this->logFH) fwrite($this->logFH, "\tERROR: File skipped, unable to locate specimen record \n");
								echo "<li style='margin-left:10px;'>File skipped, unable to locate specimen record</li>";
							}
        				}
						else{
							//echo "<li style='margin-left:10px;'><b>Error:</b> File skipped, not a supported image file: ".$file."</li>";
							if($this->logErrFH) fwrite($this->logErrFH, "\tERROR: File skipped, not a supported image file: ".$file." \n");
							//fwrite($this->logFH, "\tERROR: File skipped, not a supported image file: ".$file." \n");
						}
					}
					elseif(is_dir($sourcePath.$pathFrag.$file)){
						$this->processFolder($pathFrag.$file."/");
					}
        		}
			}
		}
   		closedir($imgFH);
	}
	
	private function createNewImage($sourcePath, $targetPath, $newWidth, $newHeight, $oldWidth, $oldHeight){
		$status = false;
       	$sourceImg = imagecreatefromjpeg($sourcePath);
   		$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
		imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth,$newHeight,$oldWidth,$oldHeight);
        if(imagejpeg($tmpImg, $targetPath)){
        	$status = true;
        }
        else{
			if($this->logErrFH) fwrite($this->logErrFH, "\tError: Unable to resize and write file: ".$targetPath."\n");
        	echo "<li style='margin-left:20px;'><b>Error:</b> Unable to resize and write file: $targetPath</li>";
        }
		imagedestroy($sourceImg);
		imagedestroy($tmpImg);
		return $status;
	}

}
?>
 