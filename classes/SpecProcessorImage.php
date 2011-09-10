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
		//If output is to go out to file, create file for output
		if(!$this->dbMetadata){
			$this->mdOutputFH = fopen("output_".time().'.csv', 'w');
			fwrite($this->mdOutputFH, '"collid","dbpk","url","thumbnailurl","originalurl"'."\n");
			//If unable to create output file, abort upload procedure
			if(!$this->mdOutputFH){
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
		//Lets start processing folder
		echo "<li>Starting Image Processing</li>\n";
		$this->processFolder();
		echo "<li>Image upload complete</li>\n";
		//Now lets start closing things up
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
			$this->conn->query('CALL UpdateCollectionStats('.$this->collId.')');
		}
   		closedir($imgFH);
	}

	public function processImageFile($fileName,$pathFrag = ''){
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
        	if(!$this->dbMetadata || $this->createNewRec || $occId){
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
	        			if(file_exists($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."lg.jpg")){
	        				unlink($targetPath.substr($targetFileName,0,strlen($targetFileName)-4)."lg.jpg");
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
				echo "<li>Starting to load: ".$fileName."</li>\n";
				if($this->logFH) fwrite($this->logFH, "Starting to load (".date('Y-m-d h:i:s A')."): ".$fileName."\n");
				//Create web image
				$webImgCreated = false;
				if($this->createWebImg && $width > $this->webPixWidth){
					$webImgCreated = $this->createNewImage($this->sourcePath.$pathFrag.$fileName,$targetPath.$targetFileName,$this->webPixWidth,round($this->webPixWidth*$height/$width),$width,$height);
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
								if(copy($this->sourcePath.$pathFrag.$fileName,$targetPath.$lgTargetFileName)){
									$lgUrl = $lgTargetFileName;
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
						$lgSourceFileName = substr($fileName,0,strlen($fileName)-4).'_lg'.substr($fileName,strlen($fileName)-4);
						if(file_exists($this->sourcePath.$pathFrag.$lgSourceFileName)){
							rename($this->sourcePath.$pathFrag.$lgSourceFileName,$targetPath.$lgTargetFileName);
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
						$tnFileName = substr($fileName,0,strlen($fileName)-4).'_tn'.substr($fileName,strlen($fileName)-4);
						if(file_exists($this->sourcePath.$pathFrag.$tnFileName)){
							rename($this->sourcePath.$pathFrag.$tnFileName,$targetPath.$tnTargetFileName);
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
        	}
			else{
				if($this->logErrFH) fwrite($this->logErrFH, "\tERROR: File skipped, unable to locate specimen record (".date('Y-m-d h:i:s A').") \n");
				if($this->logFH) fwrite($this->logFH, "\tFile skipped, unable to locate specimen record (".date('Y-m-d h:i:s A').") \n");
				echo "<li style='margin-left:10px;'>File skipped, unable to locate specimen record</li>\n";
			}
		}
		else{
			if($this->logErrFH) fwrite($this->logErrFH, "\tERROR: File skipped, unable to extract specimen identifier (".date('Y-m-d h:i:s A').") \n");
			if($this->logFH) fwrite($this->logFH, "\tFile skipped, unable to extract specimen identifier (".date('Y-m-d h:i:s A').") \n");
			echo "<li style='margin-left:10px;'>File skipped, unable to extract specimen identifier</li>\n";
		}
	}

	private function createNewImage($sourcePath, $targetPath, $newWidth, $newHeight, $oldWidth, $oldHeight){
		$status = false;
		$sourceImg = imagecreatefromjpeg($sourcePath);
		ini_set('memory_limit','512M');
		$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
		//imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth,$newHeight,$oldWidth,$oldHeight);
		imagecopyresized($tmpImg,$sourceImg,0,0,0,0,$newWidth,$newHeight,$oldWidth,$oldHeight);
		if(imagejpeg($tmpImg, $targetPath, $this->jpgCompression)){
			$status = true;
		}
		else{
			if($this->logErrFH) fwrite($this->logErrFH, "\tError: Unable to resize and write file: ".$targetPath."\n");
			echo "<li style='margin-left:20px;'><b>Error:</b> Unable to resize and write file: $targetPath</li>\n";
		}
		imagedestroy($sourceImg);
		imagedestroy($tmpImg);
		return $status;
	}
}
?>
 