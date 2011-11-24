<?php
//Base folder containing herbarium folder ; read access needed
$sourcePathBase = '';
//Folder where images are to be placed; write access needed
$targetPathBase = '';
//Url base needed to build image URL that will be save in DB
$imgUrlBase = '';
//Path to where log files will be placed
$logPath = '';

//pmterm = Pattern matching terms used to locate primary key (PK) of specimen record
//ex: '/(ASU\d{7})/'; '/(UTC\d{8})/'
$collArr = array(
	'duke' => array('pmterm' => '/(^\d{7})/', 'collid' => 1),
	'mich' => array('pmterm' => '/(^\d{6})/', 'collid' => 2),
	'ny' => array('pmterm' => '/(NY\d{8})/', 'collid' => 3),
);

//If record matching PK is not found, should a new blank record be created?
$createNewRec = 1;
//Weather to copyover images with matching names (includes path) or rename new image and keep both		
$copyOverImg = 1;

$webPixWidth = 800;
$tnPixWidth = 130;
$lgPixWidth = 2000;

//Whether to use ImageMagick for creating thumbnails and web images. ImageMagick must be installed on server.
// 0 = use GD library (default), 1 = use ImageMagick  
$useImageMagick = 0;
//Value between 0 and 100
$jpgCompression = 80;

//Create thumbnail versions of image
$createTnImg = 1;		
//Create large version of image, given source image is large enough
$createLgImg = 1;		

//0 = write image metadata to file; 1 = write metadata to Symbiota database
$dbMetadata = 1;


//-------------------------------------------------------------------------------------------//
//End of variable assignment. Don't modify code below.
date_default_timezone_set('America/Phoenix');
$specManager = new SpecProcessorManager($dbMetadata);

//Set variables
$specManager->setCollArr($collArr);
$specManager->setDbMetadata($dbMetadata);
$specManager->setSourcePathBase($sourcePathBase);
$specManager->setTargetPathBase($targetPathBase);
$specManager->setImgUrlBase($imgUrlBase);
$specManager->setWebPixWidth($webPixWidth);
$specManager->setTnPixWidth($tnPixWidth);
$specManager->setLgPixWidth($lgPixWidth);
$specManager->setJpgCompression($jpgCompression);
$specManager->setUseImageMagick($useImageMagick);

$specManager->setCreateTnImg($createTnImg);
$specManager->setCreateLgImg($createLgImg);
$specManager->setCreateNewRec($createNewRec);
$specManager->setCopyOverImg($copyOverImg);

$specManager->setLogPath($logPath);

//Run process
$specManager->batchLoadImages();

class SpecProcessorManager {

	private $conn;
	private $collArr = array();
	private $collId = 0;
	private $title;
	private $collectionName;
	private $managementType;
	private $patternMatchingTerm;
	private $sourcePathBase;
	private $targetPathBase;
	private $imgUrlBase;
	private $webPixWidth = 1200;
	private $tnPixWidth = 130;
	private $lgPixWidth = 2400;
	private $jpgCompression= 80;
	private $createWebImg = 1;
	private $createTnImg = 1;
	private $createLgImg = 1;
	
	private $createNewRec = true;
	private $copyOverImg = true;
	private $dbMetadata = 1;
	private $processUsingImageMagick = 0;

	private $logPath;
	private $logFH;
	private $mdOutputFH;
	
	private $sourceGdImg;
	private $sourceImagickImg;
	private $exif;

	function __construct(){
	}

	function __destruct(){
	}

	public function batchLoadImages(){
		//Create log File
		if($this->logPath && file_exists($this->logPath)){
			if(substr($this->logPath,-1) != '/') $this->logPath .= '/'; 

			$logFile = $this->logPath."log_".date('Ymd').".log";
			$this->logFH = fopen($logFile, 'a');
			if($this->logFH) fwrite($this->logFH, "\nDateTime: ".date('Y-m-d h:i:s A')."\n");
		}

		$cycleArr = array('bryophytes','lichens');
		foreach($this->collArr as $acro => $termArr){
			foreach($cycleArr as $collName){

				//Connect to database or create output file
				if($this->dbMetadata){
					if($collName == 'bryophytes'){
						$this->conn = MySQLiConnectionFactory::getCon("symbiotabryophytes");
					}
					else{
						$this->conn = MySQLiConnectionFactory::getCon("symbiotalichens");
					}
				}
				else{
					$mdFileName = $this->logPath.$acro.'-'.$collName."_urldata_".time().'.csv';
					$this->mdOutputFH = fopen($mdFileName, 'w');
					fwrite($this->mdOutputFH, '"collid","dbpk","url","thumbnailurl","originalurl"'."\n");
					if($this->mdOutputFH){
						if($this->logFH) fwrite($this->logFH, "\tImage Metadata written out to CSV file: '".$mdFileName."' (same folder as script)\n");
						echo "Image Metadata written out to CSV file: '".$mdFileName."' (same folder as script)\n";
					}
					else{
						//If unable to create output file, abort upload procedure
						if($this->logFH){
							fwrite($this->logFH, "ABORTED: Unable to establish connection to output file to where image metadata is to be written\n\n");
							fclose($this->logFH);
						}
						echo "Image upload aborted: Unable to establish connection to output file to where image metadata is to be written\n";
						exit;
					}
				}
				
				//Set variables
				if($this->dbMetadata){
					if(array_key_exists('collid',$termArr)){
						$this->setCollId($termArr['collid']);
					}
					else{
						exit("ABORTED: 'collid' variable has not been set");
					}
				}
				$this->patternMatchingTerm = $termArr['pmterm'];
				
				//Lets start processing folder
				echo 'Starting image processing: '.$acro.'/'.$collName."\n";
				if($this->logFH) fwrite($this->logFH, 'Starting image processing: '.$acro.'/'.$collName."\n");
				
				if(substr($this->targetPathBase,-1) != "/"){
					$this->targetPathBase .= "/";
				}
				if(!file_exists($this->targetPathBase)){
					if($this->logFH) fwrite($this->logFH, "ABORT: targetPathBase does not exist \n");
					exit;
				}
				if(!file_exists($this->targetPathBase.$acro)){
					if(!mkdir($this->targetPathBase.$acro)){
						if($this->logFH) fwrite($this->logFH, "ERROR: unable to create new folder (".$this->targetPathBase.$acro.") \n");
					}
				}
				if(!file_exists($this->targetPathBase.$acro.'/'.$collName)){
					if(!mkdir($this->targetPathBase.$acro.'/'.$collName)){
						if($this->logFH) fwrite($this->logFH, "ERROR: unable to create new folder (".$this->targetPathBase.$acro.'/'.$collName.") \n");
					}
				}
				if(file_exists($this->targetPathBase.$acro.'/'.$collName)) $this->processFolder($acro.'/'.$collName.'/');
				echo 'Image upload complete for '.$acro.'/'.$collName."\n";
				if($this->logFH) fwrite($this->logFH, "\tImage upload complete for ".$acro."/".$collName."\n");
				if($this->logFH) fwrite($this->logFH, "-----------------------------------------------------\n\n");
				
				//Now lets start closing things up
				//First some data maintenance
				if($this->dbMetadata && $this->conn){
					$sql = 'UPDATE images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
						'SET i.tid = o.tidinterpreted '.
						'WHERE i.tid IS NULL and o.tidinterpreted IS NOT NULL';
					$this->conn->query($sql);
				}
				//Close connection or MD output file
				if($this->dbMetadata){
			 		if(!($this->conn === false)) $this->conn->close();
				}
				else{
					fclose($this->mdOutputFH);
				}
			}
		}
		//Close log file
		if($this->logFH){
			fwrite($this->logFH, 'Image upload complete for '.$acro.'/'.$collName."\n");
			fwrite($this->logFH, "----------------------------\n\n");
			fclose($this->logFH);
		}
	}

	private function processFolder($pathFrag = ''){
		set_time_limit(2000);
		if(!$this->sourcePathBase) $this->sourcePathBase = './';
		//Read file and loop through images
		if($imgFH = opendir($this->sourcePathBase.$pathFrag)){
			while($fileName = readdir($imgFH)){
				if($fileName != "." && $fileName != ".." && $fileName != ".svn"){
					if(is_file($this->sourcePathBase.$pathFrag.$fileName)){
						if(stripos($fileName,'_tn.jpg') === false && stripos($fileName,'_lg.jpg') === false){
							$fileExt = strtolower(substr($fileName,strrpos($fileName,'.')));
							if($fileExt == ".jpg"){
								$this->processImageFile($fileName,$pathFrag);
	        				}
							elseif($fileExt == ".tif"){
								//Do something, like convert to jpg???
								//but for now do nothing
							}
							elseif(($fileExt == ".csv" || $fileExt == ".txt" || $fileExt == ".tab" || $fileExt == ".dat") && stripos($fileName,'metadata') !== false ){
								//Is metadata file. Append data to database records
								$this->processMetadataFile($this->sourcePathBase.$pathFrag.$fileName);
							}
	        				else{
								//echo "<li style='margin-left:10px;'><b>Error:</b> File skipped, not a supported image file: ".$file."</li>";
								if($this->logFH) fwrite($this->logFH, "\tERROR: File skipped, not a supported image file: ".$fileName." \n");
							}
						}
					}
					elseif(is_dir($this->sourcePathBase.$pathFrag.$fileName)){
						$this->processFolder($pathFrag.$fileName."/");
					}
        		}
			}
		}
		else{
			if($this->logFH) fwrite($this->logFH, "\tERROR: unable to access source directory: ".$this->sourcePathBase.$pathFrag." \n");
		}
   		closedir($imgFH);
	}

	private function processImageFile($fileName,$pathFrag = ''){
		echo "Processing image ".$fileName."\n";
		if($this->logFH) fwrite($this->logFH, "Processing image (".date('Y-m-d h:i:s A')."): ".$fileName."\n");
		//ob_flush();
		flush();
		//Grab Primary Key from filename
		$specPk = $this->getPrimaryKey($fileName);
		if($specPk){
			//Get occid (Symbiota occurrence record primary key)
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
				$targetPath = $this->targetPathBase.$targetFolder;
				if(!file_exists($targetPath)){
					if(!mkdir($targetPath)){
						if($this->logFH) fwrite($this->logFH, "ERROR: unable to create new folder (".$targetPath.") \n");
					}
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
				list($width, $height) = getimagesize($this->sourcePathBase.$pathFrag.$fileName);
				echo "Loading image\n";
				if($this->logFH) fwrite($this->logFH, "\tLoading image (".date('Y-m-d h:i:s A').")\n");
				//ob_flush();
				flush();
				
				//Create web image
				$webImgCreated = false;
				if($this->createWebImg && $width > $this->webPixWidth){
					$webImgCreated = $this->createNewImage($this->sourcePathBase.$pathFrag.$fileName,$targetPath.$targetFileName,$this->webPixWidth,round($this->webPixWidth*$height/$width),$width,$height);
				}
				else{
					$webImgCreated = copy($this->sourcePathBase.$pathFrag.$fileName,$targetPath.$targetFileName);
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
								if(copy($this->sourcePathBase.$pathFrag.$fileName,$targetPath.$lgTargetFileName)){
									$lgUrl = $lgTargetFileName;
								}
							}
							else{
								if($this->createNewImage($this->sourcePathBase.$pathFrag.$fileName,$targetPath.$lgTargetFileName,$this->lgPixWidth,round($this->lgPixWidth*$height/$width),$width,$height)){
									$lgUrl = $lgTargetFileName;
								}
							}
						}
					}
					else{
						$lgSourceFileName = substr($fileName,0,strlen($fileName)-4).'_lg'.substr($fileName,strlen($fileName)-4);
						if(file_exists($this->sourcePathBase.$pathFrag.$lgSourceFileName)){
							rename($this->sourcePathBase.$pathFrag.$lgSourceFileName,$targetPath.$lgTargetFileName);
						}
					}
					//Create Thumbnail Image
					$tnTargetFileName = substr($targetFileName,0,strlen($targetFileName)-4)."_tn.jpg";
					if($this->createTnImg){
						if($this->createNewImage($this->sourcePathBase.$pathFrag.$fileName,$targetPath.$tnTargetFileName,$this->tnPixWidth,round($this->tnPixWidth*$height/$width),$width,$height)){
							$tnUrl = $tnTargetFileName;
						}
					}
					else{
						$tnFileName = substr($fileName,0,strlen($fileName)-4).'_tn'.substr($fileName,strlen($fileName)-4);
						if(file_exists($this->sourcePathBase.$pathFrag.$tnFileName)){
							rename($this->sourcePathBase.$pathFrag.$tnFileName,$targetPath.$tnTargetFileName);
						}
					}
					if($tnUrl) $tnUrl = $targetFolder.$tnUrl;
					if($lgUrl) $lgUrl = $targetFolder.$lgUrl;
					if($this->recordImageMetadata(($this->dbMetadata?$occId:$specPk),$targetFolder.$targetFileName,$tnUrl,$lgUrl)){
						if(file_exists($this->sourcePathBase.$pathFrag.$fileName)) unlink($this->sourcePathBase.$pathFrag.$fileName);
						echo "Image processed successfully!\n";
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
			if($this->logFH) fwrite($this->logFH, "\tERROR: File skipped, unable to extract specimen identifier (".date('Y-m-d h:i:s A').") \n");
			echo "File skipped, unable to extract specimen identifier\n";
		}
		//ob_flush();
		flush();
	}

	private function createNewImage($sourcePathBase, $targetPath, $newWidth, $newHeight, $sourceWidth, $sourceHeight){
		global $useImageMagick;
		$status = false;
		
		if($this->processUsingImageMagick) {
			// Use ImageMagick to resize images 
			$status = $this->createNewImageImagick($sourcePathBase,$targetPath,$newWidth,$newHeight,$sourceWidth,$sourceHeight);
		} 
		elseif(extension_loaded('gd') && function_exists('gd_info')) {
			// GD is installed and working 
			$status = $this->createNewImageGD($sourcePathBase,$targetPath,$newWidth,$newHeight,$sourceWidth,$sourceHeight);
		}
		else{
			// Neither ImageMagick nor GD are installed
			if($this->logFH) fwrite($this->logFH, "\tFATAL ERROR: No appropriate image handler for image conversions\n");
			exit;
		}
		return $status;
	}
	
	private function createNewImageImagick($sourceImg,$targetPath,$newWidth){
		$status = false;
		$ct;
		if($newWidth < 300){
			$ct = system('convert '.$sourceImg.' -thumbnail '.$newWidth.'x'.($newWidth*1.5).' '.$targetPath, $retval);
		}
		else{
			$ct = system('convert '.$sourceImg.' -resize '.$newWidth.'x'.($newWidth*1.5).($this->jpgCompression?' -quality '.$this->jpgCompression:'').' '.$targetPath, $retval);
		}
		if(file_exists($targetPath)){
			$status = true;
		}
		return $status;
	}
	
	private function createNewImageGD($sourcePathBase, $targetPath, $newWidth, $newHeight, $sourceWidth, $sourceHeight){
		$status = false;
	   	if(!$this->sourceGdImg){
	   		$this->sourceGdImg = imagecreatefromjpeg($sourcePathBase);
			if(class_exists('PelJpeg')){
				$inputJpg = new PelJpeg($sourcePathBase);
				$this->exif = $inputJpg->getExif();
			}

	   	}

		ini_set('memory_limit','512M');
		$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
		//imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth,$newHeight,$sourceWidth,$sourceHeight);
		imagecopyresized($tmpImg,$this->sourceGdImg,0,0,0,0,$newWidth,$newHeight,$sourceWidth,$sourceHeight);

		if($this->jpgCompression){
			$status = imagejpeg($tmpImg, $targetPath, $this->jpgCompression);
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
			if($this->logFH) fwrite($this->logFH, "\tERROR: Unable to resize and write file: ".$targetPath."\n");
			echo "ERROR: Unable to resize and write file: ".$targetPath."\n";
		}
		
		imagedestroy($tmpImg);
		return $status;
	}
	
	public function setCollId($id){
		$this->collId = $id;
		if($this->collId && is_numeric($this->collId) && !$this->collectionName){
			$sql = 'SELECT collid, collectionname, managementtype FROM omcollections WHERE (collid = '.$this->collId.')';
			if($rs = $this->conn->query($sql)){
				if($row = $rs->fetch_object()){
					$this->collectionName = $row->collectionname;
					$this->managementType = $row->managementtype;
				}
				else{
					exit('ABORTED: unable to locate collection in data');
				}
				$rs->close();
			}
			else{
				exit('ABORTED: unable run SQL to obtain collectionName');
			}
		}
	}

	private function getPrimaryKey($str){
		$specPk = '';
		if(preg_match($this->patternMatchingTerm,$str,$matchArr)){
			if(array_key_exists(1,$matchArr) && $matchArr[1]){
				$specPk = $matchArr[1];
			}
			else{
				$specPk = $matchArr[0];
			}
		}
		return $specPk;
	}

	private function getOccId($specPk){
		$occId = 0;
		//Check to see if record with pk already exists
		$sql = 'SELECT occid FROM omoccurrences WHERE (catalognumber = "'.$specPk.'") AND (collid = '.$this->collId.')';
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$occId = $row->occid;
		}
		$rs->close();
		if(!$occId && $this->createNewRec){
			//Records does not exist, create a new one to which image will be linked
			$sql2 = 'INSERT INTO omoccurrences(collid,catalognumber'.(stripos($this->managementType,'Live')!==false?'':',dbpk').',processingstatus) '.
				'VALUES('.$this->collId.',"'.$specPk.'"'.(stripos($this->managementType,'Live')!==false?'':',"'.$specPk.'"').',"unprocessed")';
			if($this->conn->query($sql2)){
				$occId = $this->conn->insert_id;
				if($this->logFH) fwrite($this->logFH, "\tSpecimen record does not exist; new empty specimen record created and assigned an 'unprocessed' status (occid = ".$occId.") \n");
				echo "Specimen record does not exist; new empty specimen record created and assigned an 'unprocessed' status (occid = ".$occId.")\n";
			} 
		}
		if(!$occId){
			if($this->logFH) fwrite($this->logFH, "\tERROR: File skipped, unable to locate specimen record ".$specPk." (".date('Y-m-d h:i:s A').") \n");
			echo "File skipped, unable to locate specimen record ".$specPk."\n";
		}
		return $occId;
	}
	
	private function recordImageMetadata($specID,$webUrl,$tnUrl,$oUrl){
		$status = false;
		if($this->dbMetadata){
			$status = $this->databaseImage($specID,$webUrl,$tnUrl,$oUrl);
		}
		else{
			$status = $this->writeMetadataToFile($specID,$webUrl,$tnUrl,$oUrl);
		}
		return $status;
	}
	
	private function databaseImage($occId,$webUrl,$tnUrl,$oUrl){
		$status = true;
		if($occId && is_numeric($occId)){
	        //echo "<li style='margin-left:20px;'>Preparing to load record into database</li>\n";
			if($this->logFH) fwrite($this->logFH, "\tPreparing to load record into database\n");
			//Check to see if image url already exists for that occid
			$imgId = 0;
			$sql = 'SELECT imgid '.
				'FROM images WHERE (occid = '.$occId.') AND (url = "'.$this->imgUrlBase.$webUrl.'")';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$imgId = $r->imgid;
			}
			$rs->close();
			$sql1 = 'INSERT images(occid,url';
			$sql2 = 'VALUES ('.$occId.',"'.$this->imgUrlBase.$webUrl.'"';
			if($imgId){
				$sql1 = 'REPLACE images(imgid,occid,url';
				$sql2 = 'VALUES ('.$imgId.','.$occId.',"'.$this->imgUrlBase.$webUrl.'"';
			}
			if($tnUrl){
				$sql1 .= ',thumbnailurl';
				$sql2 .= ',"'.$this->imgUrlBase.$tnUrl.'"';
			}
			if($oUrl){
				$sql1 .= ',originalurl';
				$sql2 .= ',"'.$this->imgUrlBase.$oUrl.'"';
			}
			$sql1 .= ',imagetype,owner) ';
			$sql2 .= ',"specimen","'.$this->collectionName.'")';
			if(!$this->conn->query($sql1.$sql2)){
				$status = false;
				if($this->logFH) fwrite($this->logFH, "\tERROR: Unable to load image record into database: ".$this->conn->error."; SQL: ".$sql1.$sql2."\n");
			}
			if($imgId){
				if($this->logFH) fwrite($this->logFH, "\tWARNING: Existing image record replaced; occid: $occId \n");
				echo "Existing image database record replaced\n";
			}
			else{
				echo "Image record loaded into database\n";
				if($this->logFH) fwrite($this->logFH, "\tSUCCESS: Image record loaded into database\n");
			}
		}
		else{
			$status = false;
			if($this->logFH) fwrite($this->logFH, "ERROR: Missing occid (omoccurrences PK), unable to load record \n");
	        echo "ERROR: Unable to load image into database. See error log for details\n";
		}
		//ob_flush();
		flush();
		return $status;
	}

	private function writeMetadataToFile($specPk,$webUrl,$tnUrl,$oUrl){
		$status = true;
		if($this->mdOutputFH){
			$status = fwrite($this->mdOutputFH, $this->collId.',"'.$specPk.'","'.$this->imgUrlBase.$webUrl.'","'.$this->imgUrlBase.$tnUrl.'","'.$this->imgUrlBase.$oUrl.'"'."\n");
		}
		return $status;
	}
	
	private function processMetadataFile($filePath){
		if($this->logFH) fwrite($this->logFH, "\tPreparing to load Metadata file into database\n");
		$fh = fopen($filePath,'r');
		$hArr = array();
		if($fh){
			$fileExt = substr($filePath,-4);
			$delimiter = '';
			if($fileExt == '.csv'){
				//Comma delimited
				$hArr = fgetcsv($fh);
				$delimiter = 'csv';
			}
			elseif($fileExt == '.tab'){
				//Tab delimited assumed
				$headerStr = fgets($fh);
				$hArr = explode("\t",$headerStr);
				$delimiter = "\t";
			}
			elseif($fileExt == '.dat' || $fileExt == '.txt'){
				//Test to see if comma, tab delimited, or pipe delimited
				$headerStr = fgets($fh);
				if(strpos($headerStr,"\t") !== false){
					$hArr = explode("\t",$headerStr);
					$delimiter = "\t";
				}
				elseif(strpos($headerStr,"|") !== false){
					$hArr = explode("|",$headerStr);
					$delimiter = "|";
				}
				elseif(strpos($headerStr,",") !== false){
					rewind($fh);
					$hArr = fgetcsv($fh);
					$delimiter = "csv";
				}
				else{
					if($this->logFH) fwrite($this->logFH, "\tERROR: Unable to identify delimiter for metadata file \n");
				}
			}
			else{
				if($this->logFH) fwrite($this->logFH, "\tERROR: Metadata file skipped: unable to determine file type \n");
			}
			if($hArr){
				//Clean and finalize header array
				$headerArr = array();
				foreach($hArr as $field){
					$fieldStr = strtolower(trim($field));
					if($fieldStr){
						if($fieldStr == 'scientificname'){
							$headerArr[] = 'sciname';
						}
						else{
							$headerArr[] = $fieldStr;
						}
					}
					else{
						break;
					}
				}

				//Read and database each record, only if the catalognumber was supplied
				$symbMap = array();
				if(in_array('catalognumber',$headerArr)){
					//Get map of value Symbiota occurrence fields
					$sqlMap = "SHOW COLUMNS FROM omoccurrences";
					$rsMap = $this->conn->query($sqlMap);
			    	while($rMap = $rsMap->fetch_object()){
			    		$field = strtolower($rMap->Field);
			    		if($field != "dbpk" && $field != "initialTimestamp" && $field != "occid" && $field != "collid" && $field != 'catalognumber'){
							if(in_array($field,$headerArr)){
				    			$type = $rMap->Type;
								if(strpos($type,"double") !== false || strpos($type,"int") !== false || strpos($type,"decimal") !== false){
									$symbMap[$field]["type"] = "numeric";
								}
								elseif(strpos($type,"date") !== false){
									$symbMap[$field]["type"] = "date";
								}
								else{
									$symbMap[$field]["type"] = "string";
									if(preg_match('/\(\d+\)$/', $type, $matches)){
										$symbMap[$field]["size"] = substr($matches[0],1,strlen($matches[0])-2);
									}
								}
							}
			    		}
			    	}
					
			    	//Fetch each record within file and process accordingly  
					while($recordArr = $this->getRecordArr($fh,$delimiter)){
						//Clean record and map fields
						$catNum = 0;
						$recMap = Array();
						foreach($headerArr as $k => $hStr){
							if($hStr == 'catalognumber') $catNum = $recordArr[$k];
							if(array_key_exists($hStr,$symbMap)){
								$valueStr = $recordArr[$k];
								//If value is enclosed by quotes, remove quotes
								if(substr($valueStr,0,1) == '"' && substr($valueStr,-1) == '"'){
									$valueStr = substr($valueStr,1,strlen($valueStr)-2);
								}
								$valueStr = trim($valueStr);
								if($valueStr) $recMap[$hStr] = $valueStr;
							}
						}

						//Load record
						if($catNum){
							$sql = 'SELECT occid,'.(!array_key_exists('occurrenceremarks',$symbMap)?'occurrenceremarks,':'').implode(',',array_keys($symbMap)).' '.
								'FROM omoccurrences WHERE collid = '.$this->collId.' AND (catalognumber = "'.$catNum.'" OR dbpk = "'.$catNum.'") ';
							$rs = $this->conn->query($sql);
							if($r = $rs->fetch_assoc()){
								//Record already exists, thus just append values to record
								$occId = $r['occid'];
								$updateValueArr = array();
								$occRemarkArr = array();  
								foreach($recMap as $k => $v){
									if(!trim($r[$k])){
										//Field is empty for existing record, thus load new data 
										$type = (array_key_exists('type',$symbMap[$k])?$symbMap[$k]['type']:'string');
										$size = (array_key_exists('size',$symbMap[$k])?$symbMap[$k]['size']:0);
										if($type == 'numeric'){
											if(is_numeric($v)){
												$updateValueArr[$k] = $v;
											}
											else{
												//Not numeric, thus load into occRemarks 
												$occRemarkArr[$k] = $v;
											}
										}
										elseif($type == 'date'){
											if(preg_match('/^\d{4}-\d{2}-\d{2}$/', $v)){
												$updateValueArr[$k] = $v;
											} 
											elseif(($dateStr = strtotime($v))){
												$updateValueArr[$k] = date('Y-m-d H:i:s', $dateStr);
											} 
											else{
												//Not valid date, thus load into verbatiumEventDate or occRemarks
												if($k == 'eventdate' && !array_key_exists('verbatimeventdate',$updateValueArr)){
													$updateValueArr['verbatimeventdate'] = $v;
												}
												else{
													$occRemarkArr[$k] = $v;
												}
											}
										}
										else{
											//Type assumed to be a string
											if($size && strlen($v) > $size){
												$v = substr($v,0,$size);
											}
											$updateValueArr[$k] = $v;
										}
									}
									elseif($v != $r[$k]){
										//Target field is not empty and values not equal, thus add value into occurrenceRemarks
										$occRemarkArr[$k] = $v;
									}
								}
								$updateFrag = '';
								foreach($updateValueArr as $k => $uv){
									$updateFrag .= ','.$k.'="'.$this->encodeString($uv).'"';
								}
								if($occRemarkArr){
									$occStr = '';
									foreach($occRemarkArr as $k => $orv){
										$occStr .= ','.$k.': '.$this->encodeString($orv);
									} 
									$updateFrag .= ',occurrenceremarks="'.($r['occurrenceremarks']?$r['occurrenceremarks'].'; ':'').substr($occStr,1).'"';
								}
								if($updateFrag){
									$sqlUpdate = 'UPDATE omoccurrences SET '.substr($updateFrag,1).' WHERE occid = '.$occId;
									if(!$this->conn->query($sqlUpdate)){
										if($this->logFH){
											fwrite($this->logFH, "ERROR: Unable to update existing record with new metadata \n");
											fwrite($this->logFH, "\tSQL : $sqlUpdate \n");
										}
									}
								}
							}
							else{
								//Insert new record
								$sqlIns1 = 'INSERT INTO omoccurrences(collid,dbpk,catalogNumber';
								$sqlIns2 = 'VALUES ('.$this->collId.',"'.$catNum.'","'.$catNum.'"';
								foreach($symbMap as $symbKey => $fMap){
									if(array_key_exists($symbKey,$recMap)){
										$sqlIns1 .= ','.$symbKey;
										$value = $recMap[$symbKey];
										$type = (array_key_exists('type',$fMap)?$fMap['type']:'string');
										$size = (array_key_exists('size',$fMap)?$fMap['size']:0);
										if($type == 'numeric'){
											if(is_numeric($value)){
												$sqlIns2 .= ",".$value;
											}
											else{
												$sqlIns2 .= ",NULL";
											}
										}
										elseif($type == 'date'){
											if(preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)){
												$sqlIns2 .= ',"'.$value.'"';
											}
											elseif(($dateStr = strtotime($value))){
												$sqlIns2 .= ',"'.date('Y-m-d H:i:s', $dateStr).'"';
											}
											else{
												$sqlIns2 .= ",NULL";
												//Not valid date, thus load into verbatiumEventDate 
												if($symbKey == 'eventdate' && !array_key_exists('verbatimeventdate',$symbMap)){
													$sqlIns1 .= ',verbatimeventdate';
													$sqlIns2 .= ',"'.$value.'"';
												}
											}
										}
										else{
											if($size && strlen($value) > $size){
												$value = substr($value,0,$size);
											}
											if($value){
												$sqlIns2 .= ',"'.$this->encodeString($value).'"';
											}
											else{
												$sqlIns2 .= ',NULL';
											}
										}
									}
								}
								$sqlIns = $sqlIns1.') '.$sqlIns2.')';
								if(!$this->conn->query($sqlIns)){
									if($this->logFH){
										fwrite($this->logFH, "ERROR: Unable to load new metadata record \n");
										fwrite($this->logFH, "\tSQL : $sqlIns \n");
									}
								}
							}
							$rs->close();
						}
						unset($recMap);
					}
				}
				else{
					fwrite($this->logFH, "\tERROR: Failed to locate catalognumber MD within file (".$filePath."),  \n");
				}
			}
			if($this->logFH) fwrite($this->logFH, "\tMetadata file loaded \n");
			if(!unlink($filePath)){
				if($this->logFH) fwrite($this->logFH, "\tERROR: unable to delete file (".$filePath.") \n");
			}
		}
		else{
			fwrite($this->logFH, "ERROR: Can't open metadata file ".$filePath." \n");
		}
	}

    private function getRecordArr($fh, $delimiter){
    	if(!$delimiter) return;
    	$recordArr = Array();
    	if($delimiter == 'csv'){
			$recordArr = fgetcsv($fh);
    	}
    	else{
	    	$recordStr = fgets($fh);
			if($recordStr) $recordArr = explode($delimiter,$recordStr);
    	}
    	return $recordArr;
    }

	//Set and Get functions
	public function setCollArr($cArr){
		$this->collArr = $cArr;
	}

	public function setTitle($t){
		$this->title = $t;
	}

	public function getTitle(){
		return $this->title;
	}

	public function setCollectionName($cn){
		$this->collectionName = $cn;
	}

	public function getCollectionName(){
		return $this->collectionName;
	}

	public function setManagementType($t){
		$this->managementType = $t;
	}

	public function getManagementType(){
		return $this->managementType;
	}

	public function setSourcePathBase($p){
		$this->sourcePathBase = $p;
	}

	public function getSourcePathBase(){
		return $this->sourcePathBase;
	}

	public function setTargetPathBase($p){
		$this->targetPathBase = $p;
	}

	public function getTargetPathBase(){
		return $this->targetPathBase;
	}

	public function setImgUrlBase($u){
		if(substr($u,-1) != '/') $u = '/';
		$this->imgUrlBase = $u;
	}

	public function getImgUrlBase(){
		return $this->imgUrlBase;
	}

	public function setWebPixWidth($w){
		$this->webPixWidth = $w;
	}

	public function getWebPixWidth(){
		return $this->webPixWidth;
	}

	public function setTnPixWidth($tn){
		$this->tnPixWidth = $tn;
	}

	public function getTnPixWidth(){
		return $this->tnPixWidth;
	}

	public function setLgPixWidth($lg){
		$this->lgPixWidth = $lg;
	}

	public function getLgPixWidth(){
		return $this->lgPixWidth;
	}

	public function setJpgCompression($jc){
		$this->jpgCompression = $jc;
	}

	public function getJpgCompression(){
		return $this->jpgCompression;
	}

	public function setCreateWebImg($c){
		$this->createWebImg = $c;
	}

	public function getCreateWebImg(){
		return $this->createWebImg;
	}

	public function setCreateTnImg($c){
		$this->createTnImg = $c;
	}

	public function getCreateTnImg(){
		return $this->createTnImg;
	}

	public function setCreateLgImg($c){
		$this->createLgImg = $c;
	}

	public function getCreateLgImg(){
		return $this->createLgImg;
	}
	
	public function setCreateNewRec($c){
		$this->createNewRec = $c;
	}

	public function getCreateNewRec(){
		return $this->createNewRec;
	}
	
	public function setCopyOverImg($c){
		$this->copyOverImg = $c;
	}

	public function getCopyOverImg(){
		return $this->copyOverImg;
	}
	
	public function setDbMetadata($v){
		$this->dbMetadata = $v;
	}

 	public function setUseImageMagick($useIM){
 		$this->processUsingImageMagick = $useIM;
 	}
 	
 	public function setLogPath($p){
 		$this->logPath = $p;
 	}

	//Misc functions
	private function encodeString($inStr){
 		$retStr = trim(str_replace('"',"",$inStr));
 		if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
			//$value = utf8_decode($value);
			$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
		}
		return $retStr;
	}
}

class MySQLiConnectionFactory {
	static $SERVERS = array(
		array(
			'type' => 'write',
			'host' => 'sod84.asu.edu',
			'username' => 'lbtcnwriter',
			'password' => '',
			'database' => 'symbiotalichens'
		),
		array(
			'type' => 'write',
			'host' => 'sod84.asu.edu',
			'username' => 'lbtcnwriter',
			'password' => '',
			'database' => 'symbiotabryophytes'
		)
	);

	public static function getCon($db) {
		// Figure out which connections are open, automatically opening any connections
		// which are failed or not yet opened but can be (re)established.
		for($i = 0, $n = count(MySQLiConnectionFactory::$SERVERS); $i < $n; $i++) {
			$server = MySQLiConnectionFactory::$SERVERS[$i];
			if($server['database'] == $db){
				$connection = new mysqli($server['host'], $server['username'], $server['password'], $server['database']);
				if(mysqli_connect_errno()){
					//throw new Exception('Could not connect to any databases! Please try again later.');
					exit('ABORTED: could not connect to database');
				}
				return $connection;
			}
		}
	}
}
?>