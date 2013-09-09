<?php

class BatchImageProcessor {

	private $conn;

	private $collArr = array();
	private $activeCollid = null;
	private $collProcessedArr = array();

	private $sourcePathBase;
	private $targetPathBase;
	private $targetPathFrag;
	private $origPathFrag;
	private $imgUrlBase;
	private $symbiotaClassPath = null;
	private $serverRoot;
	
	private $webPixWidth = 1200;
	private $tnPixWidth = 130;
	private $lgPixWidth = 2400;
	private $jpgCompression= 80;
	private $createWebImg = 1;
	private $createTnImg = 1;
	private $createLgImg = 1;
	private $keepOrig = 1;
	
	private $createNewRec = true;
	private $copyOverImg = true;
	private $dbMetadata = 1;
	private $processUsingImageMagick = 0;

	private $logPath;
	private $silent = 0;
	private $logFH;
	private $mdOutputFH;
	
	private $sourceGdImg;
	private $sourceImagickImg;
	
	private $dataLoaded = 0;

	function __construct(){
		ini_set('memory_limit','512M');
		ini_set('auto_detect_line_endings', true);
		//Create log File
		if($this->logPath && file_exists($this->logPath)){
			$logFile = $this->logPath."log_".date('Ymd').".log";
			$this->logFH = fopen($logFile, 'a');
			$this->logOrEcho("\nDateTime: ".date('Y-m-d h:i:s A'));
		}
		if($this->dbMetadata){
			//Set collection
			$this->conn = BatchImageConnectionFactory::getCon('write');
			if(!$this->conn){
				//Try getting connection through portals central connection factory
				$this->conn = MySQLiConnectionFactory::getCon('write');
			}
			if(!$this->conn){
				$this->logOrEcho("Image upload aborted: Unable to establish connection to ".$collName." database");
				exit("ABORT: Image upload aborted: Unable to establish connection to ".$collName." database");
			}
		}
	}

	function __destruct(){
		//Close connection or MD output file
		if($this->dbMetadata && $this->collProcessedArr){
			//Update Statistics
			$this->updateCollectionStats();

			if(!($this->conn === false)) $this->conn->close();
		}
		else{
			if($this->mdOutputFH) fclose($this->mdOutputFH);
		}
		
		//Close log file
		$this->logOrEcho("\nImage upload process finished! (".date('Y-m-d h:i:s A').") ");
		$this->logOrEcho("----------------------------------------------------------------\n");
		if($this->logFH){
			fclose($this->logFH);
		}
	}

	public function batchLoadImages(){
		//Make sure target path exist 
		if(!file_exists($this->targetPathBase)){
			$this->logOrEcho('ABORT: targetPathBase does not exist ('.$this->targetPathBase.')');
			exit();
		}
		//Make sure source path exists
		if(!file_exists($this->sourcePathBase)){
			$this->logOrEcho('ABORT: sourcePathBase does not exist ('.$this->sourcePathBase.')');
			exit();
		}
		
		//Variable used in path to store original files
		$this->origPathFrag = 'orig/'.date("Ym").'/';

		$projProcessed = array();
		foreach($this->collArr as $collid => $cArr){
			$this->activeCollid = $collid;
			$collStr = str_replace(' ','',$cArr['instcode'].($cArr['collcode']?'_'.$cArr['collcode']:''));

			if(!$this->dbMetadata){
				//Create output file
				$mdFileName = $this->logPath.$collStr.'_urldata_'.time().'.csv';
				$this->mdOutputFH = fopen($mdFileName, 'w');
				//Establish the header
				fwrite($this->mdOutputFH, '"collid","dbpk","url","thumbnailurl","originalurl"'."\n");
				if($this->mdOutputFH){
					$this->logOrEcho("Image Metadata written out to CSV file: '".$mdFileName."' (same folder as script)");
				}
				else{
					//If unable to create output file, abort upload procedure
					$this->logOrEcho("Image upload aborted: Unable to establish connection to output file to where image metadata is to be written");
					exit("ABORT: Image upload aborted: Unable to establish connection to output file to where image metadata is to be written");
				}
			}
			
			//Set target path fragment
			$this->targetPathFrag = ''; 
			if(isset($this->collArr[$collid]['sourcePathFrag'])){
				$this->targetPathFrag .= $this->collArr[$collid]['sourcePathFrag'];
			}
			else{
				$this->targetPathFrag .= $collStr.'/';
			}
			if(!file_exists($this->targetPathBase.$this->targetPathFrag)){
				if(!mkdir($this->targetPathBase.$this->targetPathFrag,0777,true)){
					$this->logOrEcho("ERROR: unable to create new folder (".$this->targetPathBase.$this->targetPathFrag.") ");
					exit("ABORT: unable to create new folder (".$this->targetPathBase.$this->targetPathFrag.")");
				}
			}

			//If originals are to be kept, make sure target folders exist
			if($this->keepOrig){
				if(!file_exists($this->targetPathBase.$this->targetPathFrag.'orig/')){
					if(!mkdir($this->targetPathBase.$this->targetPathFrag.'orig/')){
						$this->logOrEcho("NOTICE: unable to create base folder to store original files (".$this->targetPathBase.$this->targetPathFrag.") ");
					}
				}
				if(file_exists($this->targetPathBase.$this->targetPathFrag.'orig/')){
					if(!file_exists($this->targetPathBase.$this->targetPathFrag.$this->origPathFrag)){
						if(!mkdir($this->targetPathBase.$this->targetPathFrag.$this->origPathFrag)){
							$this->logOrEcho("NOTICE: unable to create folder to store original files (".$this->targetPathBase.$this->targetPathFrag.$this->origPathFrag.") ");
						}
					}
				}
			}
			
			//Lets start processing folder
			$this->logOrEcho('Starting image processing: '.$this->targetPathFrag);
			$this->processFolder($this->targetPathFrag);
			$this->logOrEcho('Image upload complete');
		}
	}

	private function processFolder($pathFrag = ''){
		set_time_limit(2000);
		//$this->logOrEcho("Processing: ".$this->sourcePathBase.$pathFrag);
		//Read file and loop through images
		if(!file_exists($this->sourcePathBase.$pathFrag)){
			$this->logOrEcho("\tSource path does not exist: ".$this->sourcePathBase.$pathFrag);
			exit("ABORT: Source path does not exist: ".$this->sourcePathBase.$pathFrag);
		}
		if($dirFH = opendir($this->sourcePathBase.$pathFrag)){
			while($fileName = readdir($dirFH)){
				if($fileName != "." && $fileName != ".." && $fileName != ".svn"){
					if(is_file($this->sourcePathBase.$pathFrag.$fileName)){
						if(!stripos($fileName,'_tn.jpg') && !stripos($fileName,'_lg.jpg')){
							$this->logOrEcho("Processing File: ".$fileName);
							$fileExt = strtolower(substr($fileName,strrpos($fileName,'.')));
							if($fileExt == ".jpg"){
								$this->processImageFile($fileName,$pathFrag);
								if(!in_array($this->activeCollid,$this->collProcessedArr)) $this->collProcessedArr[] = $this->activeCollid;
							}
							elseif($fileExt == ".tif"){
								$this->logOrEcho("\tERROR: File skipped, TIFFs image files are not a supported: ".$fileName);
								//Do something, like convert to jpg???
								//but for now do nothing
							}
							elseif(($fileExt == ".csv" || $fileExt == ".txt" || $fileExt == ".tab" || $fileExt == ".dat")){
								//Is skeletal file exists. Append data to database records
								$this->processSkeletalFile($this->sourcePathBase.$pathFrag.$fileName); 
								if(!in_array($this->activeCollid,$this->collProcessedArr)) $this->collProcessedArr[] = $this->activeCollid;
							}
							elseif($fileExt==".xml") {
								$this->processXMLFile($fileName,$pathFrag);
								if(!in_array($this->activeCollid,$this->collProcessedArr)) $this->collProcessedArr[] = $this->activeCollid;
							}
							elseif($fileExt==".ds_store"){
								unlink($this->sourcePathBase.$pathFrag.$fileName);
							}
							else{
								$this->logOrEcho("\tERROR: File skipped, not a supported image file: ".$fileName);
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
			$this->logOrEcho("\tERROR: unable to access source directory: ".$this->sourcePathBase.$pathFrag);
		}
		if($dirFH) closedir($dirFH);
	}

	/**
	 * Examine an xml file, and if it conforms to supported expectations, 
	 * add the data it contains to the Symbiota database.
	 * Currently supported expectations are: (1) the GPI/ALUKA/LAPI schema
	 * and (2) RDF/XML containing oa/oad annotations asserting new occurrence
	 * records in dwcFP, supporting the NEVP TCN.
	 *  
	 * @param fileName the name of the xml file to process.
	 * @param pathFrag the path from sourcePathBase to the file to process. 
	 */
	private function processXMLFile($fileName,$pathFrag='') { 
		if ($this->symbiotaClassPath!=null) {
			$foundSchema = false;
			$xml = XMLReader::open($this->sourcePathBase.$pathFrag.$fileName);
			if($xml->read())  {
				// $this->logOrEcho($fileName." first node: ". $xml->name);
				if ($xml->name=="DataSet") {	 
					$xml = XMLReader::open($this->sourcePathBase.$pathFrag.$fileName);
					$lapischema = $this->serverRoot . "/collections/admin/schemas/lapi_schema_v2.xsd";
					$xml->setParserProperty(XMLReader::VALIDATE, true);
					if (file_exists($lapischema)) { 
						$isLapi = $xml->setSchema($lapischema);
					} 
					else { 
						$this->logOrEcho("\tERROR: Can't find $lapischema");
					}
					// $this->logOrEcho($fileName." valid lapi xml:" . $xml->isValid() . " [" . $isLapi .  "]");
					if ($xml->isValid() && $isLapi) {
						// File complies with the Aluka/LAPI/GPI schema
						$this->logOrEcho('Processing GPI batch file: '.$pathFrag.$fileName);
						if (class_exists('GPIProcessor')) { 
							$processor = new GPIProcessor();
							$result = $processor->process($this->sourcePathBase.$pathFrag.$fileName);
							$foundSchema = $result->couldparse;
							if (!$foundSchema || $result->failurecount>0) {
								$this->logOrEcho("\tERROR: Errors processing $fileName: $result->errors.");
							}
						} 
						else { 
							// fail gracefully if this instalation isn't configured with this parser.
							$this->logOrEcho("\tERROR: SpecProcessorGPI.php not available.");
						}
					}
				}
				elseif ($xml->name=="rdf:RDF") { 
					// $this->logOrEcho($fileName." has oa:" . $xml->lookupNamespace("oa"));
					// $this->logOrEcho($fileName." has oad:" . $xml->lookupNamespace("oad"));
					// $this->logOrEcho($fileName." has dwcFP:" . $xml->lookupNamespace("dwcFP"));
					$hasAnnotation = $xml->lookupNamespace("oa");
					$hasDataAnnotation = $xml->lookupNamespace("oad");
					$hasdwcFP = $xml->lookupNamespace("dwcFP");
					// Note: contra the PHP xmlreader documentation, lookupNamespace
					// returns the namespace string not a boolean.
					if ($hasAnnotation && $hasDataAnnotation && $hasdwcFP) {
						// File is likely an annotation containing DarwinCore data.
						$this->logOrEcho('Processing RDF/XML annotation file: '.$pathFrag.$fileName);
						if (class_exists('NEVPProcessor')) { 
							$processor = new NEVPProcessor();
							$result = $processor->process($this->sourcePathBase.$pathFrag.$fileName);
							$foundSchema = $result->couldparse;
							if (!$foundSchema || $result->failurecount>0) {
								$this->logOrEcho("\tERROR: Errors processing $fileName: $result->errors.");
							}
						}
						else { 
							// fail gracefully if this instalation isn't configured with this parser.
							$this->logOrEcho("\tERROR: SpecProcessorNEVP.php not available.");
						}
					}
				}
				if ($foundSchema>0) { 
					$this->logOrEcho("Proccessed $pathFrag$fileName, records: $result->recordcount, success: $result->successcount, failures: $result->failurecount, inserts: $result->insertcount, updates: $result->updatecount.");
					if($this->keepOrig){
						$oldFile = $this->sourcePathBase.$pathFrag.$fileName;
						$newFileName = substr($pathFrag,strrpos($pathFrag,'/')).'orig_'.time().'.'.$fileName;
						if(!file_exists($this->targetPathBase.$this->targetPathFrag.'orig_xml')){
							mkdir($this->targetPathBase.$this->targetPathFrag.'orig_xml');
						}
						if(!rename($oldFile,$this->targetPathBase.$this->targetPathFrag.'orig_xml/'.$newFileName)){
							$this->logOrEcho("\tERROR: unable to move (".$fileName.") ");
						}
					 } 
					 else {
						if(!unlink($oldFile)){
							$this->logOrEcho("\tERROR: unable to delete file (".$fileName.") ");
						}
					}
				} 
				else { 
					$this->logOrEcho("\tERROR: Unable to match ".$pathFrag.$fileName." to a known schema.");
				}
			} 
			else { 
				$this->logOrEcho("\tERROR: XMLReader couldn't read ".$pathFrag.$fileName);
			}
		}
	}

	private function processImageFile($fileName,$pathFrag = ''){
		$this->logOrEcho("Processing image (".date('Y-m-d h:i:s A')."): ".$fileName);
		//ob_flush();
		flush();
		//Grab Primary Key from filename
		if($specPk = $this->getPrimaryKey($fileName)){
			$occId = 0;
			if($this->dbMetadata){
				$occId = $this->getOccId($specPk);
			}
			if($occId || !$this->dbMetadata){
				//Setup path and file name in prep for loading image
				$targetFolder = '00001/';
				if(strlen($specPk) > 3){
					$targetFolder = substr($specPk,0,strlen($specPk)-3).'/';
					if(strlen($targetFolder) < 6) $targetFolder = str_repeat('0',6-strlen($targetFolder)).$targetFolder;
				}
				$targetPath = $this->targetPathBase.$this->targetPathFrag.$targetFolder;
				if(!file_exists($targetPath)){
					if(!mkdir($targetPath)){
						$this->logOrEcho("ERROR: unable to create new folder (".$targetPath.") ");
					}
				}
				$targetFileName = $fileName;
				//Check to see if image already exists at target, if so, delete or rename target
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
				$this->logOrEcho("\tLoading image (".date('Y-m-d h:i:s A').")");
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
					$this->logOrEcho("\tWeb image copied to target folder (".date('Y-m-d h:i:s A').") ");
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
					//Start clean up
					if($this->sourceGdImg){
						imagedestroy($this->sourceGdImg);
						$this->sourceGdImg = null;
					}
					if($this->sourceImagickImg){
						$this->sourceImagickImg->clear();
						$this->sourceImagickImg = null;
					}
					//Database urls and metadata for images
					if($tnUrl) $tnUrl = $targetFolder.$tnUrl;
					if($lgUrl) $lgUrl = $targetFolder.$lgUrl;
					if($this->recordImageMetadata(($this->dbMetadata?$occId:$specPk),$targetFolder.$targetFileName,$tnUrl,$lgUrl)){
						//Final cleaning stage
						if(file_exists($this->sourcePathBase.$pathFrag.$fileName)){ 
							if($this->keepOrig){
								if(file_exists($this->targetPathBase.$this->targetPathFrag.$this->origPathFrag)){
									rename($this->sourcePathBase.$pathFrag.$fileName,$this->targetPathBase.$this->targetPathFrag.$this->origPathFrag.$fileName.".orig");
								}
							} else {
								unlink($this->sourcePathBase.$pathFrag.$fileName);
							}
						}
						$this->logOrEcho("\tImage processed successfully (".date('Y-m-d h:i:s A').")!");
					}
				}
			}
		}
		else{
			$this->logOrEcho("File skipped (".$pathFrag.$fileName."), unable to extract specimen identifier");
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
			$this->logOrEcho("\tFATAL ERROR: No appropriate image handler for image conversions");
			exit("ABORT: No appropriate image handler for image conversions");
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
		}

		$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
		//imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth,$newHeight,$sourceWidth,$sourceHeight);
		imagecopyresized($tmpImg,$this->sourceGdImg,0,0,0,0,$newWidth,$newHeight,$sourceWidth,$sourceHeight);

		if($this->jpgCompression){
			$status = imagejpeg($tmpImg, $targetPath, $this->jpgCompression);
		}
		else{
			$status = imagejpeg($tmpImg, $targetPath);
		}
		
		if(!$status){
			$this->logOrEcho("\tERROR: Unable to resize and write file: ".$targetPath);
		}
		
		imagedestroy($tmpImg);
		return $status;
	}
	
	/**
	 * Extract a primary key (catalog number) from a string (e.g file name, catalogNumber field), 
	 * applying patternMatchingTerm, and, if they apply, patternReplacingTerm, and 
	 * replacement.  If patternMatchingTerm contains a backreference, 
	 * and there is a match, the return value is the backreference.  If 
	 * patternReplacingTerm and replacement are modified, they are applied 
	 * before the result is returned. 
	 * 
	 * @param str  String from which to extract the catalogNumber
	 * @return an empty string if there is no match of patternMatchingTerm on
	 *        str, otherwise the match as described above. 
	 */ 
	private function getPrimaryKey($str){
		$specPk = '';
		if(preg_match($this->collArr[$this->activeCollid]['pmterm'],$str,$matchArr)){
			if(array_key_exists(1,$matchArr) && $matchArr[1]){
				$specPk = $matchArr[1];
			}
			else{
				$specPk = $matchArr[0];
			}
			if (isset($this->collArr[$this->activeCollid]['prpatt'])) { 				
				$specPk = preg_replace($this->collArr[$this->activeCollid]['prpatt'],$this->collArr[$this->activeCollid]['prrepl'],$specPk);
			}
		}
		return $specPk;
	}

	private function getOccId($specPk){
		$occId = 0;
		//Check to see if record with pk already exists
		$sql = 'SELECT occid FROM omoccurrences '.
			'WHERE (catalognumber = '.(is_numeric($specPk)?$specPk:'"'.$specPk.'"').') AND (collid = '.$this->activeCollid.')';
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$occId = $row->occid;
		}
		$rs->free();
		if(!$occId && $this->createNewRec){
			//Records does not exist, create a new one to which image will be linked
			$sql2 = 'INSERT INTO omoccurrences(collid,catalognumber,processingstatus) '.
				'VALUES('.$this->activeCollid.',"'.$specPk.'","unprocessed")';
			if($this->conn->query($sql2)){
				$occId = $this->conn->insert_id;
				$this->logOrEcho("\tSpecimen record does not exist; new empty specimen record created and assigned an 'unprocessed' status (occid = ".$occId.") ");
			} 
		}
		if(!$occId){
			$this->logOrEcho("\tERROR: File skipped, unable to locate specimen record ".$specPk." (".date('Y-m-d h:i:s A').") ");
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
			$this->logOrEcho("\tPreparing to load record into database");
			//Check to see if image url already exists for that occid
			$imgId = 0;$exTnUrl = '';$exLgUrl = '';
			$sql = 'SELECT imgid, thumbnailurl, originalurl '.
				'FROM images WHERE (occid = '.$occId.') AND (url = "'.$this->imgUrlBase.$this->targetPathFrag.$webUrl.'")';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$imgId = $r->imgid;
				$exTnUrl = $r->thumbnailurl;
				$exLgUrl = $r->originalurl;
			}
			$rs->free();
			$sql = '';
			if($imgId && $exTnUrl <> $tnUrl && $exLgUrl <> $oUrl){
				$sql = 'UPDATE images SET url = "'.$this->imgUrlBase.$this->targetPathFrag.$webUrl.'",'.
					'thumbnailurl = "'.$this->imgUrlBase.$this->targetPathFrag.$tnUrl.'",'.
					'originalurl = "'.$this->imgUrlBase.$this->targetPathFrag.$oUrl.'" '.
					'WHERE imgid = '.$imgId;
			}
			else{
				$sql1 = 'INSERT images(occid,url';
				$sql2 = 'VALUES ('.$occId.',"'.$this->imgUrlBase.$this->targetPathFrag.$webUrl.'"';
				if($tnUrl){
					$sql1 .= ',thumbnailurl';
					$sql2 .= ',"'.$this->imgUrlBase.$this->targetPathFrag.$tnUrl.'"';
				}
				if($oUrl){
					$sql1 .= ',originalurl';
					$sql2 .= ',"'.$this->imgUrlBase.$this->targetPathFrag.$oUrl.'"';
				}
				$sql1 .= ',imagetype,owner) ';
				$sql2 .= ',"specimen","'.$this->collArr[$this->activeCollid]['collname'].'")';
				$sql = $sql1.$sql2;
			}
			if($this->conn->query($sql)){
				$this->dataLoaded = 1;
			}
			else{
				$status = false;
				$this->logOrEcho("\tERROR: Unable to load image record into database: ".$this->conn->error."; SQL: ".$sql);
			}
			if($imgId){
				$this->logOrEcho("\tWARNING: Existing image record replaced; occid: $occId ");
			}
			else{
				$this->logOrEcho("\tSUCCESS: Image record loaded into database");
			}
		}
		else{
			$status = false;
			$this->logOrEcho("ERROR: Missing occid (omoccurrences PK), unable to load record ");
		}
		//ob_flush();
		flush();
		return $status;
	}

	private function writeMetadataToFile($specPk,$webUrl,$tnUrl,$oUrl){
		$status = true;
		if($this->mdOutputFH){
			$status = fwrite($this->mdOutputFH, $this->activeCollid.',"'.$specPk.'","'.$this->imgUrlBase.$webUrl.'","'.$this->imgUrlBase.$tnUrl.'","'.$this->imgUrlBase.$oUrl.'"'."\n");
		}
		return $status;
	}
	
	private function processSkeletalFile($filePath){
		$this->logOrEcho("\tPreparing to load Skeletal file into database");
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
					$this->logOrEcho("\tERROR: Unable to identify delimiter for metadata file ");
					return false;
				}
			}
			else{
				$this->logOrEcho("\tERROR: Skeletal file skipped: unable to determine file type ");
				return false;
			}
			if($hArr){
				//Clean and finalize header array
				$headerArr = array();
				foreach($hArr as $field){
					$fieldStr = strtolower(trim($field));
					if($fieldStr == 'exsnumber') $fieldStr = 'exsiccatinumber'; 
					if($fieldStr){
						$headerArr[] = $fieldStr;
					}
					else{
						break;
					}
				}

				//Read and database each record, only if field for catalognumber was supplied
				$symbMap = array();
				if(in_array('catalognumber',$headerArr)){
					//Get map of value Symbiota occurrence fields
					$sqlMap = "SHOW COLUMNS FROM omoccurrences";
					$rsMap = $this->conn->query($sqlMap);
					while($rMap = $rsMap->fetch_object()){
						$field = strtolower($rMap->Field);
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
					//Remove field that shouldn't be loaded
					unset($symbMap['datelastmodified']);
					unset($symbMap['occid']);
					unset($symbMap['collid']);
					unset($symbMap['catalognumber']);
					unset($symbMap['institutioncode']);
					unset($symbMap['collectioncode']);
					unset($symbMap['dbpk']);
					unset($symbMap['processingstatus']);
					unset($symbMap['observeruid']);
					unset($symbMap['tidinterpreted']);
					
					//Add exsiccati titles and numbers to $symbMap
					$symbMap['ometid']['type'] = "numeric";
					$symbMap['exsiccatititle']['type'] = "string";
					$symbMap['exsiccatititle']['size'] = 150;
					$symbMap['exsiccatinumber']['type'] = "string";
					$symbMap['exsiccatinumber']['size'] = 45;
					$exsiccatiTitleMap = array();

					//Fetch each record within file and process accordingly
					while($recordArr = $this->getRecordArr($fh,$delimiter)){
						//Clean record and creaet map array
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

						//If sciname does not exist but genus or scientificname does, create sciname
						if((!array_key_exists('sciname',$recMap) || !$recMap['sciname'])){
							if(array_key_exists('genus',$recMap) && $recMap['genus']){
								$sn = $recMap['genus'];
								if(array_key_exists('specificepithet',$recMap) && $recMap['specificepithet']) $sn .= ' '.$recMap['specificepithet'];
								if(array_key_exists('taxonrank',$recMap) && $recMap['taxonrank']) $sn .= ' '.$recMap['taxonrank']; 
								if(array_key_exists('infraspecificepithet',$recMap) && $recMap['infraspecificepithet']) $sn .= ' '.$recMap['infraspecificepithet'];
								$recMap['sciname'] = $sn;
							}
							elseif(array_key_exists('scientificname',$recMap) && $recMap['scientificname']){
								$recMap['sciname'] = $this->formatScientificName($recMap['scientificname']);
							}
							if(array_key_exists('sciname',$recMap)){
								$symbMap['sciname']['type'] = 'string';
								$symbMap['sciname']['size'] = 255;
							}
						}
						
						//If verbatimEventDate exists and eventDate doesn't, try to convert 
						if(!array_key_exists('eventdate',$recMap) || !$recMap['eventdate']){
							if(array_key_exists('verbatimeventdate',$recMap) && $recMap['verbatimeventdate']){
								$dateStr = $this->formatDate($recMap['verbatimeventdate']); 
								if($dateStr){
									$recMap['eventdate'] = $dateStr;
									if($dateStr == $recMap['verbatimeventdate']) unset($recMap['verbatimeventdate']);
									if(!array_key_exists('eventdate',$symbMap)){
										$symbMap['eventdate']['type'] = 'date';
									}
								}
							}
						}
						
						//If exsiccatiTitle and exsiccatiNumber exists but ometid (title number) does not
						if(array_key_exists('exsiccatinumber',$recMap) && $recMap['exsiccatinumber']){
							if(array_key_exists('exsiccatititle',$recMap) && $recMap['exsiccatititle'] && (!array_key_exists('ometid',$recMap) || !$recMap['ometid'])){
								//Get ometid
								if(array_key_exists($recMap['exsiccatititle'],$exsiccatiTitleMap)){
									//ometid was already harvested for that title 
									$recMap['ometid'] = $exsiccatiTitleMap[$recMap['exsiccatititle']];
								}
								else{
									$titleStr = trim($this->conn->real_escape_string($recMap['exsiccatititle']));
									$sql = 'SELECT ometid FROM omexsiccatititles '.
										'WHERE (title = "'.$titleStr.'") OR (abbreviation = "'.$titleStr.'")';
									$rs = $this->conn->query($sql);
									if($r = $rs->fetch_object()){
										$recMap['ometid'] = $r->ometid;
										$exsiccatiTitleMap[$recMap['exsiccatititle']] = $r->ometid;
									}
									$rs->free();
								}
							}
							//Get exsiccati number id (omenid)
							if(array_key_exists('ometid',$recMap) && $recMap['ometid']){
								$numStr = trim($this->conn->real_escape_string($recMap['exsiccatinumber']));
								$sql = 'SELECT omenid FROM omexsiccatinumbers '.
									'WHERE ometid = ('.$recMap['ometid'].') AND (exsnumber = "'.$numStr.'")';
								$rs = $this->conn->query($sql);
								if($r = $rs->fetch_object()){
									$recMap['omenid'] = $r->omenid;
								}
								$rs->free();
								if(!array_key_exists('omenid',$recMap)){
									//Exsiccati number needs to be added
									$sql = 'INSERT INTO omexsiccatinumbers(ometid,exsnumber) '.
										'VALUES('.$recMap['ometid'].',"'.$numStr.'")';
									if($this->conn->query($sql)) $recMap['omenid'] = $this->conn->insert_id;
								}
							}
						}

						//Load record
						if($catNum){
							$occid = 0;
							//Check to see if regular expression term is needed to extract correct part of catalogNumber
							$deltaCatNum = $this->getPrimaryKey($catNum);
							if ($deltaCatNum!='') { $catNum = $deltaCatNum; } 
		
							//Remove exsiccati fields 
							$activeFields = array_keys($recMap);
							if(array_search('ometid',$activeFields) !== false) unset($activeFields[array_search('ometid',$activeFields)]);
							if(array_search('omenid',$activeFields) !== false) unset($activeFields[array_search('omenid',$activeFields)]);
							if(array_search('exsiccatititle',$activeFields) !== false) unset($activeFields[array_search('exsiccatititle',$activeFields)]);
							if(array_search('exsiccatinumber',$activeFields) !== false) unset($activeFields[array_search('exsiccatinumber',$activeFields)]);
							
							//Check to see if matching record already exists in database
							$sql = 'SELECT occid'.(!array_key_exists('occurrenceremarks',$recMap)?',occurrenceremarks':'').
								($activeFields?','.implode(',',$activeFields):'').' '.
								'FROM omoccurrences WHERE collid = '.$this->activeCollid.' AND (catalognumber = '.(is_numeric($catNum)?$catNum:'"'.$catNum.'"').') ';
							//echo $sql;
							$rs = $this->conn->query($sql);
							if($r = $rs->fetch_assoc()){
								//Record already exists, thus just append values to record
								$occid = $r['occid'];
								if($activeFields){
									$updateValueArr = array();
									$occRemarkArr = array();
									foreach($activeFields as $activeField){
										$activeValue = $this->cleanString($recMap[$activeField]);
										if(!trim($r[$activeField])){
											//Field is empty for existing record, thus load new data 
											$type = (array_key_exists('type',$symbMap[$activeField])?$symbMap[$activeField]['type']:'string');
											$size = (array_key_exists('size',$symbMap[$activeField])?$symbMap[$activeField]['size']:0);
											if($type == 'numeric'){
												if(is_numeric($activeValue)){
													$updateValueArr[$activeField] = $activeValue;
												}
												else{
													//Not numeric, thus load into occRemarks 
													$occRemarkArr[$activeField] = $activeValue;
												}
											}
											elseif($type == 'date'){
												$dateStr = $this->formatDate($activeValue); 
												if($dateStr){
													$updateValueArr[$activeField] = $activeValue;
												} 
												else{
													//Not valid date, thus load into verbatiumEventDate or occRemarks
													if($activeField == 'eventdate'){
														if(!array_key_exists('verbatimeventdate',$updateValueArr) || $updateValueArr['verbatimeventdate']){
															$updateValueArr['verbatimeventdate'] = $activeValue;
														}
													}
													else{
														$occRemarkArr[$activeField] = $activeValue;
													}
												}
											}
											else{
												//Type assumed to be a string
												if($size && strlen($activeValue) > $size){
													$activeValue = substr($activeValue,0,$size);
												}
												$updateValueArr[$activeField] = $activeValue;
											}
										}
										elseif($activeValue != $r[$activeField]){
											//Target field is not empty and values not equal, thus add value into occurrenceRemarks
											$occRemarkArr[$activeField] = $activeValue;
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
										$sqlUpdate = 'UPDATE omoccurrences SET '.substr($updateFrag,1).' WHERE occid = '.$occid;
										if($this->conn->query($sqlUpdate)){
											$this->dataLoaded = 1;
										}
										else{
											$this->logOrEcho("ERROR: Unable to update existing record with new skeletal record ");
											$this->logOrEcho("\tSQL : $sqlUpdate ");
										}
									}
								}
							}
							else{
								//Insert new record
								if($activeFields){
									$sqlIns1 = 'INSERT INTO omoccurrences(collid,catalogNumber,processingstatus';
									$sqlIns2 = 'VALUES ('.$this->activeCollid.',"'.$catNum.'","unprocessed"';
									foreach($activeFields as $aField){
										$sqlIns1 .= ','.$aField;
										$value = $this->cleanString($recMap[$aField]);
										$type = (array_key_exists('type',$symbMap[$aField])?$symbMap[$aField]['type']:'string');
										$size = (array_key_exists('size',$symbMap[$aField])?$symbMap[$aField]['size']:0);
										if($type == 'numeric'){
											if(is_numeric($value)){
												$sqlIns2 .= ",".$value;
											}
											else{
												$sqlIns2 .= ",NULL";
											}
										}
										elseif($type == 'date'){
											$dateStr = $this->formatDate($value); 
											if($dateStr){
												$sqlIns2 .= ',"'.$dateStr.'"';
											}
											else{
												$sqlIns2 .= ",NULL";
												//Not valid date, thus load into verbatiumEventDate if it's the eventDate field 
												if($aField == 'eventdate' && !array_key_exists('verbatimeventdate',$symbMap)){
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
									$sqlIns = $sqlIns1.') '.$sqlIns2.')';
									if($this->conn->query($sqlIns)){
										$this->dataLoaded = 1;
										$occid = $this->conn->insert_id;
									}
									else{
										if($this->logFH){
											$this->logOrEcho("ERROR: Unable to load new skeletal record ");
											$this->logOrEcho("\tSQL : $sqlIns ");
										}
									}
								}
							}
							$rs->free();
							//Load Exsiccati if it exists
							if(isset($recMap['omenid']) && $occid){
								$sqlExs ='INSERT INTO omexsiccatiocclink(omenid,occid) VALUES('.$recMap['omenid'].','.$occid.')';
								if(!$this->conn->query($sqlExs)){
									if($this->logFH){
										$this->logOrEcho("ERROR: Unable to link record to exsiccati (".$recMap['omenid'].'-'.$occid.") ");
										$this->logOrEcho("\tSQL : $sqlExs ");
									}
								}
							}
						}
						unset($recMap);
					}
				}
				else{
					$this->logOrEcho("\tERROR: Failed to locate catalognumber MD within file (".$filePath."),  ");
					return false;
				}
			}
			$this->logOrEcho("\tSkeletal file loaded ");
			fclose($fh);
			if($this->keepOrig){
				$fileName = substr($filePath,strrpos($filePath,'/')).'.orig_'.time();
				if(!file_exists($this->targetPathBase.$this->targetPathFrag.'orig_skeletal')){
					mkdir($this->targetPathBase.$this->targetPathFrag.'orig_skeletal');
				}
				if(!rename($filePath,$this->targetPathBase.$this->targetPathFrag.'orig_skeletal'.$fileName)){
					$this->logOrEcho("\tERROR: unable to move (".$filePath.") ");
				}
			} else {
				if(!unlink($filePath)){
					$this->logOrEcho("\tERROR: unable to delete file (".$filePath.") ");
				}
			}
		}
		else{
			$this->logOrEcho("ERROR: Can't open skeletal file ".$filePath." ");
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
	
	private function updateCollectionStats(){
		if($this->dbMetadata){
			$this->logOrEcho('Updating collection statistics...');
			//General cleaning
			//populate image ids
			$sql = 'UPDATE images i INNER JOIN omoccurrences o ON i.occid = o.occid '.
				'SET i.tid = o.tidinterpreted '.
				'WHERE i.tid IS NULL and o.tidinterpreted IS NOT NULL';
			if(!$this->conn->query($sql)){
				$this->logOrEcho('ERROR: unable to update image tids; '.$this->conn->error);
			}

			#Update family
			$sql = 'UPDATE omoccurrences o '.
				'SET o.family = o.sciname '.
				'WHERE o.family IS NULL AND (o.sciname LIKE "%aceae" OR o.sciname LIKE "%idae")';
			if(!$this->conn->query($sql)){
				$this->logOrEcho('ERROR: unable to update family; '.$this->conn->error);
			}
	
			$sql = 'UPDATE omoccurrences o '. 
				'SET o.sciname = o.genus '.
				'WHERE o.genus IS NOT NULL AND o.sciname IS NULL';
			if(!$this->conn->query($sql)){
				$this->logOrEcho('ERROR: unable to update sciname using genus; '.$this->conn->error);
			}
			
			$sql = 'UPDATE omoccurrences o '. 
				'SET o.sciname = o.family '.
				'WHERE o.family IS NOT NULL AND o.sciname IS NULL';
			if(!$this->conn->query($sql)){
				$this->logOrEcho('ERROR: unable to update sciname using family; '.$this->conn->error);
			}
			
			#Link new occurrence records to taxon table
			$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname '. 
				'SET o.TidInterpreted = t.tid '. 
				'WHERE o.TidInterpreted IS NULL';
			if(!$this->conn->query($sql)){
				$this->logOrEcho('ERROR: unable to update tidinterpreted; '.$this->conn->error);
			}
	
			#Update specimen image taxon links
			$sql = 'UPDATE omoccurrences o INNER JOIN images i ON o.occid = i.occid '. 
				'SET i.tid = o.tidinterpreted '. 
				'WHERE o.tidinterpreted IS NOT NULL AND (i.tid IS NULL OR o.tidinterpreted <> i.tid)';
			if(!$this->conn->query($sql)){
				$this->logOrEcho('ERROR: unable to update image tid field; '.$this->conn->error);
			}
			
			#Updating records with null families
			$sql = 'UPDATE omoccurrences o INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid '. 
				'SET o.family = ts.family '. 
				'WHERE ts.taxauthid = 1 AND ts.family <> "" AND ts.family IS NOT NULL AND (o.family IS NULL OR o.family = "")';
			if(!$this->conn->query($sql)){
				$this->logOrEcho('ERROR: unable to update family in omoccurrence table; '.$this->conn->error);
			}
	
			#Updating records with null author
			$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.tidinterpreted = t.tid '. 
				'SET o.scientificNameAuthorship = t.author '. 
				'WHERE o.scientificNameAuthorship IS NULL and t.author is not null';
			if(!$this->conn->query($sql)){
				$this->logOrEcho('ERROR: unable to update author; '.$this->conn->error);
			}
			
			foreach($this->collProcessedArr as $collid){
				#Updating total record count
				$sql = 'UPDATE omcollectionstats cs '. 
					'SET cs.recordcnt = (SELECT Count(o.occid) FROM omoccurrences o WHERE (o.collid = '.$collid.')) '. 
					'WHERE cs.collid = '.$collid.'';
				if(!$this->conn->query($sql)){
					$this->logOrEcho('ERROR: unable to update record counts; '.$this->conn->error);
				}
				
				#Updating family count
				$sql = 'UPDATE omcollectionstats cs '. 
					'SET cs.familycnt = (SELECT COUNT(DISTINCT o.family) '. 
					'FROM omoccurrences o WHERE (o.collid = '.$collid.')) '. 
					'WHERE cs.collid = '.$collid.'';
				if(!$this->conn->query($sql)){
					$this->logOrEcho('ERROR: unable to update family counts; '.$this->conn->error);
				}
				
				#Updating genus count
				$sql = 'UPDATE omcollectionstats cs '. 
					'SET cs.genuscnt = (SELECT COUNT(DISTINCT t.unitname1) '. 
					'FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '. 
					'WHERE (o.collid = '.$collid.') AND t.rankid >= 180) '. 
					'WHERE cs.collid = '.$collid.'';
				if(!$this->conn->query($sql)){
					$this->logOrEcho('ERROR: unable to update genus counts; '.$this->conn->error);
				}
				
				#Updating species count
				$sql = 'UPDATE omcollectionstats cs '. 
					'SET cs.speciescnt = (SELECT count(DISTINCT t.unitname1, t.unitname2) AS spcnt '. 
					'FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '. 
					'WHERE (o.collid = '.$collid.') AND t.rankid >= 220) '. 
					'WHERE cs.collid = '.$collid.'';
				if(!$this->conn->query($sql)){
					$this->logOrEcho('ERROR: unable to update species count; '.$this->conn->error);
				}
				
				#Updating georeference count
				$sql = 'UPDATE omcollectionstats cs '. 
					'SET cs.georefcnt = (SELECT Count(o.occid) FROM omoccurrences o WHERE (o.DecimalLatitude Is Not Null) '. 
					'AND (o.DecimalLongitude Is Not Null) AND (o.CollID = '.$collid.')) '. 
					'WHERE cs.collid = '.$collid.'';
				if(!$this->conn->query($sql)){
					$this->logOrEcho('ERROR: unable to update georeference count; '.$this->conn->error);
				}
			}
		}
		$this->logOrEcho("Stats update completed");
	}
	

	//Set and Get functions
	public function setCollArr($cArr){
		if($cArr){
			if(is_array($cArr)){
				$this->collArr = $cArr;
				//Set additional collection info
				if($this->dbMetadata){
					$sql = 'SELECT collid, institutioncode, collectioncode, collectionname, managementtype FROM omcollections '.
						'WHERE (collid IN('.implode(',',array_keys($this->collArr)).'))';
					if($rs = $this->conn->query($sql)){
						while($r = $rs->fetch_object()){
							$this->collArr[$r->collid]['instcode'] = $r->institutioncode;
							$this->collArr[$r->collid]['collcode'] = $r->collectioncode;
							$this->collArr[$r->collid]['collname'] = $r->collectionname;
							$this->collArr[$r->collid]['managementtype'] = $r->managementtype;
						}
						$rs->free();
					}
					else{
						$this->logOrEcho('ABORT: unable run SQL to obtain additional collection metadata: '.$this->error);
						exit('ABORT: unable run SQL to obtain additional collection metadata'.$this->error);
					}
				}
			}
		}
		else{
			$this->logOrEcho("Error: collection array does not exist");
			exit("ABORT: collection array does not exist");
		}
	}
	
	public function setSourcePathBase($p){
		if(substr($p,-1) != '/' && substr($p,-1) != "\\") $p .= '/';
		$this->sourcePathBase = $p;
	}

	public function getSourcePathBase(){
		return $this->sourcePathBase;
	}

	public function setTargetPathBase($p){
		if(substr($p,-1) != '/' && substr($p,-1) != "\\") $p .= '/';
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

	public function setSymbiotaClassPath($path) { 
		$this->symbiotaClassPath = $path;
	}

	public function setServerRoot($path) { 
		$this->serverRoot = $path;
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

	public function setKeepOrig($c){
		$this->keepOrig = $c;
	}

	public function getKeepOrig(){
		return $this->keepOrig;
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
		if(substr($p,-1) != '/' && substr($p,-1) != "\\") $p .= '/'; 
		$this->logPath = $p;
	}

	public function setSilent($c){
		$this->silent = $c;
	}

	public function getSilent(){
		return $this->silent;
	}

	//Misc functions
	private function formatDate($inStr){
		$dateStr = trim($inStr);
		if(!$dateStr) return;
		$t = '';
		$y = '';
		$m = '00';
		$d = '00';
		if(preg_match('/\d{2}:\d{2}:\d{2}/',$dateStr,$match)){
			//Extract time
			$t = $match[0];
		}
		if(preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})\D*/',$dateStr,$match)){
			//Format: yyyy-mm-dd, yyyy-m-d
			$y = $match[1];
			$m = $match[2];
			$d = $match[3];
		}
		elseif(preg_match('/^(\d{1,2})\s{1}(\D{3,})\.*\s{1}(\d{2,4})/',$dateStr,$match)){
			//Format: dd mmm yyyy, d mmm yy
			$d = $match[1];
			$mStr = $match[2];
			$y = $match[3];
			$mStr = strtolower(substr($mStr,0,3));
			$m = $this->monthNames[$mStr];
		}
		elseif(preg_match('/^(\d{1,2})-(\D{3,})-(\d{2,4})/',$dateStr,$match)){
			//Format: dd-mmm-yyyy
			$d = $match[1];
			$mStr = $match[2];
			$y = $match[3];
			$mStr = strtolower(substr($mStr,0,3));
			$m = $this->monthNames[$mStr];
		}
		elseif(preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})/',$dateStr,$match)){
			//Format: mm/dd/yyyy, m/d/yy
			$m = $match[1];
			$d = $match[2];
			$y = $match[3];
		}
		elseif(preg_match('/^(\D{3,})\.*\s{1}(\d{1,2}),{0,1}\s{1}(\d{2,4})/',$dateStr,$match)){
			//Format: mmm dd, yyyy
			$mStr = $match[1];
			$d = $match[2];
			$y = $match[3];
			$mStr = strtolower(substr($mStr,0,3));
			$m = $this->monthNames[$mStr];
		}
		elseif(preg_match('/^(\d{1,2})-(\d{1,2})-(\d{2,4})/',$dateStr,$match)){
			//Format: mm-dd-yyyy, mm-dd-yy
			$m = $match[1];
			$d = $match[2];
			$y = $match[3];
		}
		elseif(preg_match('/^(\D{3,})\.*\s([1,2]{1}[0,5-9]{1}\d{2})/',$dateStr,$match)){
			//Format: mmm yyyy
			$mStr = strtolower(substr($match[1],0,3));
			$m = $this->monthNames[$mStr];
			$y = $match[2];
		}
		elseif(preg_match('/([1,2]{1}[0,5-9]{1}\d{2})/',$dateStr,$match)){
			//Format: yyyy
			$y = $match[1];
		}
		if($y){
			if(strlen($y) == 2){ 
				if($y < 20) $y = '20'.$y;
				else $y = '19'.$y;
			}
			if(strlen($m) == 1) $m = '0'.$m;
			if(strlen($d) == 1) $d = '0'.$d;
			$dateStr = $y.'-'.$m.'-'.$d;
		}
		else{
			$timeStr = strtotime($dateStr);
			if($timeStr) $dateStr = date('Y-m-d H:i:s', $timeStr);
		}
		if($t){
			$dateStr .= ' '.$t;
		}
		return $dateStr;
	}
	
	private function formatScientificName($inStr){
		$sciNameStr = trim($inStr);
		$sciNameStr = preg_replace('/\s\s+/', ' ',$sciNameStr);
		$tokens = explode(' ',$sciNameStr);
		if($tokens){
			$sciNameStr = array_shift($tokens);
			if(strlen($sciNameStr) < 2) $sciNameStr = ' '.array_shift($tokens);
			if($tokens){
				$term = array_shift($tokens);
				$sciNameStr .= ' '.$term;
				if($term == 'x') $sciNameStr .= ' '.array_shift($tokens);
			}
			$tRank = '';
			$infraSp = '';
			foreach($tokens as $c => $v){
				switch($v) {
					case 'subsp.':
					case 'subsp':
					case 'ssp.':
					case 'ssp':
					case 'subspecies':
					case 'var.':
					case 'var':
					case 'variety':
					case 'forma':
					case 'form':
					case 'f.':
					case 'fo.':
						if(array_key_exists($c+1,$tokens) && ctype_lower($tokens[$c+1])){
							$tRank = $v;
							if(substr($tRank,-1) != '.' && ($tRank == 'ssp' || $tRank == 'subsp' || $tRank == 'var')) $tRank .= '.';
							$infraSp = $tokens[$c+1];
						}
				}
			}
			if($infraSp){
				$sciNameStr .= ' '.$tRank.' '.$infraSp;
			}
		}
		return $sciNameStr;
	}
	
	private function encodeString($inStr){
		global $charset;
		$retStr = trim($inStr);
		//Get rid of annoying curly quotes
		$search = array("’", "‘", "`", "”", "“"); 
		$replace = array("'", "'", "'", '"', '"'); 
		$inStr= str_replace($search, $replace, $inStr);
		
		if($inStr){
			if(strtolower($charset) == "utf-8" || strtolower($charset) == "utf8"){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
					$retStr = utf8_encode($inStr);
					//$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
				}
			}
			elseif(strtolower($charset) == "iso-8859-1"){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
					$retStr = utf8_decode($inStr);
					//$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
				}
			}
		}
		return $retStr;
	}

	private function cleanString($inStr){
		$retStr = trim($inStr);
		$retStr = str_replace(chr(10),' ',$retStr);
		$retStr = str_replace(chr(11),' ',$retStr);
		$retStr = str_replace(chr(13),' ',$retStr);
		$retStr = str_replace(chr(20),' ',$retStr);
		$retStr = str_replace(chr(30),' ',$retStr);
		$retStr = $this->conn->real_escape_string($retStr);
		return $retStr;
	}

	private function logOrEcho($str){
		if(!$this->silent){
			if($this->logFH){
				fwrite($this->logFH,$str."\n");
			} else {
				echo $str."\n";
			}	
		}
	}
}
?>