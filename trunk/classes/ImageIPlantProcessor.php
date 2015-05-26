<?php
// Used by /trunk/collections/specprocessor/standalone_scripts/ImageIPlantHandler.php

if(isset($serverRoot)){
	//Use Symbiota connection factory
	if(file_exists($serverRoot.'/config/dbconnection.php')){ 
		include_once($serverRoot.'/config/dbconnection.php');
	}
}

class ImageIPlantProcessor {

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
	
	private $webPixWidth = '';
	private $tnPixWidth = '';
	private $lgPixWidth = '';
	private $webFileSizeLimit = 300000;
	private $lgFileSizeLimit = 3000000;
	private $jpgQuality= 80;
	private $webImg = 1;			// 1 = evaluate source and import, 2 = import source and use as is, 3 = map to source  
	private $tnImg = 1;				// 1 = create from source, 2 = import source, 3 = map to source, 0 = exclude 
	private $lgImg = 1;				// 1 = import source, 2 = map to source, 3 = import large version (_lg.jpg), 4 = map large version (_lg.jpg), 0 = exclude
	private $webSourceSuffix = '';
	private $tnSourceSuffix = '_tn';
	private $lgSourceSuffix = '_lg';
	private $keepOrig = 0;

	private $createNewRec = true;
	private $imgExists = 0;			// 0 = skip import, 1 = rename image and save both, 2 = copy over image
	private $processUsingImageMagick = 0;

	private $logMode = 0;			//0 = silent, 1 = html, 2 = log file
	private $logFH;
	private $logPath;

	private $sourceGdImg;
	private $sourceImagickImg;

	private $dataLoaded = 0;

	private $monthNames = array('jan'=>'01','ene'=>'01','feb'=>'02','mar'=>'03','abr'=>'04','apr'=>'04',
		'may'=>'05','jun'=>'06','jul'=>'07','ago'=>'08','aug'=>'08','sep'=>'09','oct'=>'10','nov'=>'11','dec'=>'12','dic'=>'12');

    /**  Track the list of xml files that have been processed to avoid
     *   processing the same file more than once when collArr is configured
     *   to contain more than one record for the same path (for image 
     *   uploads from an institution with more than one collection code).
     */
    private $processedFiles = Array();  

	function __construct(){
		ini_set('memory_limit','1024M');
		//Set collection
		if(class_exists('ImageBatchConnectionFactory')){
			$this->conn = ImageBatchConnectionFactory::getCon('write');
		}
		elseif(class_exists('MySQLiConnectionFactory')){
			//Try getting connection through portals central connection factory
			$this->conn = MySQLiConnectionFactory::getCon('write');
		}
		if(!$this->conn){
			$this->logOrEcho("Image upload aborted: Unable to establish connection to ".$collName." database");
			exit("ABORT: Image upload aborted: Unable to establish connection to ".$collName." database");
		}
		//Use deaults located within symbini, if they are available
		//Will be replaced by values within configuration file, if they are set 
		if(isset($GLOBALS['imgTnWidth']) && $GLOBALS['imgTnWidth']) $this->tnPixWidth = $GLOBALS['imgTnWidth'];
	}

	function __destruct(){
		//Close connection or MD output file
		if(!($this->conn === false)) $this->conn->close();

		//Close log file
		if($this->logFH) fclose($this->logFH);
	}

	public function initProcessor($logTitle = ''){
		if($this->logPath && $this->logMode == 2){
			//Create log File
			if(!file_exists($this->logPath)){
				if(!mkdir($this->logPath,0,true)){
					echo("Warning: unable to create log file: ".$this->logPath);
				}
			}
			if(file_exists($this->logPath)){
				$titleStr = str_replace(' ','_',$logTitle);
				if(strlen($titleStr) > 50) $titleStr = substr($titleStr,0,50);
				$logFile = $this->logPath.$titleStr."_".date('Ymd').".log";
				$this->logFH = fopen($logFile, 'a');
				$this->logOrEcho("\nDateTime: ".date('Y-m-d h:i:s A'));
			}
			else{
				echo 'ERROR creating Log file; path not found: '.$this->logPath."\n";
			}
		}
	}

	public function batchLoadImages(){
		//Set target base path
		if(!$this->targetPathBase){
			//Assume that portal's default image root path is what needs to be used  
			$this->targetPathBase = $GLOBALS['imageRootPath'];
		}
		if(!$this->targetPathBase){
			//Assume that we should use the portal's default image root path   
			$this->targetPathBase = $GLOBALS['imageRootPath'];
		}
		if($this->targetPathBase && substr($this->targetPathBase,-1) != '/' && substr($this->targetPathBase,-1) != "\\"){
			$this->targetPathBase .= '/';
		}
		
		//Set image base URL
		if(!$this->imgUrlBase){
			//Assume that we should use the portal's default image url prefix 
			$this->imgUrlBase = $GLOBALS['imageRootUrl'];
		}
		if(isset($GLOBALS['imageDomain']) && $GLOBALS['imageDomain']){
			//Since imageDomain is set, portal is not central portal thus add portals domain to url base
			if(substr($this->imgUrlBase,0,7) != 'http://' && substr($this->imgUrlBase,0,8) != 'https://'){
				$urlPrefix = "http://";
				if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) $urlPrefix = "https://";
				$urlPrefix .= $_SERVER["SERVER_NAME"];
				if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $urlPrefix .= ':'.$_SERVER["SERVER_PORT"];
				$this->imgUrlBase = $urlPrefix.$this->imgUrlBase;
			}
		}
		if($this->imgUrlBase && substr($this->imgUrlBase,-1) != '/' && substr($this->imgUrlBase,-1) != "\\"){
			$this->imgUrlBase .= '/';
		}

		//Lets start processing folder
		if($this->logMode == 1){
			echo '<ul>';
		}
		$projProcessed = array();
		foreach($this->collArr as $collid => $cArr){
			$this->activeCollid = $collid;
			$collStr = str_replace(' ','',$cArr['instcode'].($cArr['collcode']?'_'.$cArr['collcode']:''));

			//Set source and target path fragments
			$this->targetPathFrag = $collStr;
			if(substr($this->targetPathFrag,-1) != "/" && substr($this->targetPathFrag,-1) != "\\"){
				$this->targetPathFrag .= '/';
			}
			if(!file_exists($this->targetPathBase.$this->targetPathFrag)){
				if(!mkdir($this->targetPathBase.$this->targetPathFrag,0777,true)){
					$this->logOrEcho("ERROR: unable to create new folder (".$this->targetPathBase.$this->targetPathFrag.") ");
					exit("ABORT: unable to create new folder (".$this->targetPathBase.$this->targetPathFrag.")");
				}
			}

			$this->logOrEcho('Starting image processing: '.$sourcePathFrag);

			
			
			

			$this->logOrEcho('Done uploading '.$sourcePathFrag.' ('.date('Y-m-d h:i:s A').')');
		}
		if($this->collProcessedArr){
			//Update Statistics
			$this->updateCollectionStats();
		}
		
		$this->logOrEcho("Image upload process finished! (".date('Y-m-d h:i:s A').") \n");
		if($this->logMode == 1){
			echo '</ul>';
		}
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
		if ($this->serverRoot) {
			$foundSchema = false;
			$xml = XMLReader::open($this->sourcePathBase.$pathFrag.$fileName);
			if($xml->read()) {
				// $this->logOrEcho($fileName." first node: ". $xml->name);
				if ($xml->name=="DataSet") {
					$xml = XMLReader::open($this->sourcePathBase.$pathFrag.$fileName);
					$lapischema = $this->serverRoot . "/collections/admin/schemas/lapi_schema_v2.xsd";
					$xml->setParserProperty(XMLReader::VALIDATE, true);
					if (file_exists($lapischema)) { 
						$isLapi = $xml->setSchema($lapischema);
					} 
					else { 
						$this->logOrEcho("ERROR: Can't find $lapischema",1);
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
								$this->logOrEcho("ERROR: Errors processing $fileName: $result->errors.",1);
							}
						} 
						else { 
							// fail gracefully if this instalation isn't configured with this parser.
							$this->logOrEcho("ERROR: SpecProcessorGPI.php not available.",1);
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
								$this->logOrEcho("ERROR: Errors processing $fileName: $result->errors.",1);
							}
						}
						else { 
							// fail gracefully if this instalation isn't configured with this parser.
							$this->logOrEcho("ERROR: SpecProcessorNEVP.php not available.",1);
						}
					}
				}
				$xml->close();
				if ($foundSchema>0) { 
					$this->logOrEcho("Proccessed $pathFrag$fileName, records: $result->recordcount, success: $result->successcount, failures: $result->failurecount, inserts: $result->insertcount, updates: $result->updatecount.");
					if ($result->imagefailurecount>0) {
						$this->logOrEcho("ERROR: not moving (".$fileName."), image failure count " . $result->imagefailurecount . " greater than zero.",1);
					}
					else {
						$oldFile = $this->sourcePathBase.$pathFrag.$fileName;
						if($this->keepOrig){
							$newFileName = substr($pathFrag,strrpos($pathFrag,'/')).'orig_'.time().'.'.$fileName;
							if(!file_exists($this->targetPathBase.$this->targetPathFrag.'orig_xml')){
								mkdir($this->targetPathBase.$this->targetPathFrag.'orig_xml');
							}
							if(!rename($oldFile,$this->targetPathBase.$this->targetPathFrag.'orig_xml/'.$newFileName)){
								$this->logOrEcho("ERROR: unable to move (".$oldFile." =>".$newFileName.") ",1);
							}
						 } 
						 else {
							if(!unlink($oldFile)){
								$this->logOrEcho("ERROR: unable to delete file (".$oldFile.") ",1);
							}
						}
					}
				} 
				else { 
					$this->logOrEcho("ERROR: Unable to match ".$pathFrag.$fileName." to a known schema.",1);
				}
			} 
			else { 
				$this->logOrEcho("ERROR: XMLReader couldn't read ".$pathFrag.$fileName,1);
			}
		}
	}

	private function processImageFile($fileName,$sourcePathFrag = ''){
		$lgUrlFrag = "";
		$webUrlFrag = '';
		$targetFileName = $fileName;
		//$this->logOrEcho("Processing image (".date('Y-m-d h:i:s A')."): ".$fileName);
		//ob_flush();
		//flush();
		//Grab Primary Key from filename
		if($specPk = $this->getPrimaryKey($fileName)){
			$occId = $this->getOccId($specPk);
			$fileNameExt = '.jpg';
			$fileNameBase = $fileName;
			if($p = strrpos($fileName,'.')){
				$fileNameExt = substr($fileName,$p);
				$fileNameBase = substr($fileName,0,$p);
				if($this->webSourceSuffix){
					$fileNameBase = substr($fileNameBase,0,-1*strlen($this->webSourceSuffix));
				}
			}
			if($occId){
				$sourcePath = $this->sourcePathBase.$sourcePathFrag;
				//Setup target path and file name in prep for loading image
				$targetFolder = '';
				if(strlen($specPk) > 3){
					$folderName = $specPk;
					if(preg_match('/^(\D*\d+)\D+/',$folderName,$m)){
						$folderName = $m[1];
					}
					$targetFolder = substr($folderName,0,strlen($folderName)-3);
					$targetFolder = str_replace(array('.','\\','/','#',' '),'',$targetFolder).'/';
					if($targetFolder && strlen($targetFolder) < 6 && is_numeric(substr($targetFolder,0,1))){
						$targetFolder = str_repeat('0',6-strlen($targetFolder)).$targetFolder;
					}
				}
				if(!$targetFolder) $targetFolder = date('Ym').'/';
				$targetFrag = $this->targetPathFrag.$targetFolder;
				$targetPath = $this->targetPathBase.$targetFrag;
				if(!file_exists($targetPath)){
					if(!mkdir($targetPath)){
						$this->logOrEcho("ERROR: unable to create new folder (".$targetPath.") ");
					}
				}
				//Start the processing procedure
				list($width, $height) = getimagesize($sourcePath.$fileName);
				if($width && $height){
					//Get File size
					$fileSize = 0;
					if(substr($sourcePath,0,7)=='http://' || substr($sourcePath,0,8)=='https://') { 
						$x = array_change_key_case(get_headers($sourcePath.$fileName, 1),CASE_LOWER); 
						if ( strcasecmp($x[0], 'HTTP/1.1 200 OK') != 0 ) { 
							$fileSize = $x['content-length'][1]; 
						}
 						else { 
 							$fileSize = $x['content-length']; 
 						}
					} 
					else { 
						$fileSize = @filesize($sourcePath.$fileName);
					} 

					//Create Thumbnail Image
					// 1 = create from source, 2 = import source, 3 = map to source, 0 = exclude
					$tnUrlFrag = "";
					if($this->tnImg){
						// Don't exclude thumbnails (0 != exclude)
						$tnTargetFileName = substr($targetFileName,0,-4)."_tn.jpg";
						if($this->tnImg == 1){
							// 1 = create from source, 0 = exclude
							if($this->createNewImage($sourcePath.$fileName,$targetPath.$tnTargetFileName,$this->tnPixWidth,round($this->tnPixWidth*$height/$width),$width,$height)){
								$tnUrlFrag = $this->imgUrlBase.$targetFrag.$tnTargetFileName;
								$this->logOrEcho("Created thumbnail from source (".date('Y-m-d h:i:s A').") ",1);
							}
						}
						elseif($this->tnImg == 2){
							// 2 = import source (source name with _tn.jpg (or $this->tnSourceSuffix.'.jpg') suffix)
							$tnFileName = $fileNameBase.$this->tnSourceSuffix.$fileNameExt;
							if($this->uriExists($sourcePath.$tnFileName)){
								rename($sourcePath.$tnFileName,$targetPath.$tnTargetFileName);
							}
							$tnUrlFrag = $this->imgUrlBase.$targetFrag.$tnTargetFileName;
							$this->logOrEcho("Imported source as thumbnail (".date('Y-m-d h:i:s A').") ",1);
						}
						elseif($this->tnImg == 3){
							// 3 = map to source (source name with _tn.jpg (or $this->tnSourceSuffix.'.jpg') suffix)
							$tnFileName = $fileNameBase.$this->tnSourceSuffix.$fileNameExt;
							if($this->uriExists($sourcePath.$tnFileName)){
								$tnUrlFrag = $sourcePath.$tnFileName;
								$this->logOrEcho("Thumbnail is map of source thumbnail (".date('Y-m-d h:i:s A').") ",1);
							}
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
					$this->databaseImage($occId,$webUrlFrag,$tnUrlFrag,$lgUrlFrag);
					//Final cleaning stage
					if(file_exists($sourcePath.$fileName)){ 
						if($this->keepOrig){
							if(file_exists($this->targetPathBase.$this->targetPathFrag.$this->origPathFrag)){
								rename($sourcePath.$fileName,$this->targetPathBase.$this->targetPathFrag.$this->origPathFrag.$fileName.".orig");
							}
						} else {
							unlink($sourcePath.$fileName);
						}
					}
					$this->logOrEcho("Image processed successfully (".date('Y-m-d h:i:s A').")!",1);
				}
				else{
					$this->logOrEcho("File skipped (".$sourcePathFrag.$fileName."), unable to obtain dimentions of original image");
					return false;
				}
			}
		}
		else{
			$this->logOrEcho("File skipped (".$sourcePathFrag.$fileName."), unable to extract specimen identifier");
			return false;
		}
		//ob_flush();
		flush();
		return true;
	}

	private function createNewImage($sourcePathBase, $targetPath, $newWidth, $newHeight, $sourceWidth, $sourceHeight){
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
			$this->logOrEcho("FATAL ERROR: No appropriate image handler for image conversions",1);
			exit("ABORT: No appropriate image handler for image conversions");
		}
		return $status;
	}
	
	private function createNewImageImagick($sourceImg,$targetPath,$newWidth){
		$status = false;
		$ct;
		$retval;
		if($newWidth < 300){
			$ct = system('convert '.$sourceImg.' -thumbnail '.$newWidth.'x'.($newWidth*1.5).' '.$targetPath, $retval);
		}
		else{
			$ct = system('convert '.$sourceImg.' -resize '.$newWidth.'x'.($newWidth*1.5).($this->jpgQuality?' -quality '.$this->jpgQuality:'').' '.$targetPath, $retval);
		}
		if(file_exists($targetPath)){
			$status = true;
		}
		else{
			echo $ct;
			echo $retval;
		}
		return $status;
	}
	
	private function createNewImageGD($sourcePathBase, $targetPath, $newWidth, $newHeight, $sourceWidth, $sourceHeight){
		$status = false;
		if(!$this->sourceGdImg){
			$this->sourceGdImg = imagecreatefromjpeg($sourcePathBase);
		}
		if(!$newWidth || !$newHeight){
			$this->logOrEcho("ERROR: Unable to create image because new width or height is not set (w:".$newWidth.' h:'.$newHeight.')');
			return $status;
		}
		$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
		//imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth,$newHeight,$sourceWidth,$sourceHeight);
		imagecopyresized($tmpImg,$this->sourceGdImg,0,0,0,0,$newWidth,$newHeight,$sourceWidth,$sourceHeight);

		if($this->jpgQuality){
			$status = imagejpeg($tmpImg, $targetPath, $this->jpgQuality);
		}
		else{
			$status = imagejpeg($tmpImg, $targetPath);
		}
		
		if(!$status){
			$this->logOrEcho("ERROR: Unable to resize and write file: ".$targetPath,1);
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
		if(isset($this->collArr[$this->activeCollid]['pmterm'])){
			$pmTerm = $this->collArr[$this->activeCollid]['pmterm'];
			if(substr($pmTerm,0,1) != '/' || substr($pmTerm,-1) != '/'){
				$this->logOrEcho("PROCESS ABORTED: Regular Expression term illegal due to missing forward slashes: ".$pmTerm);
				exit;
			}
			if(!strpos($pmTerm,'(') || !strpos($pmTerm,')')){
				$this->logOrEcho("PROCESS ABORTED: Regular Expression term illegal due to missing capture term: ".$pmTerm);
				exit;
			}
			if(preg_match($pmTerm,$str,$matchArr)){
				if(array_key_exists(1,$matchArr) && $matchArr[1]){
					$specPk = $matchArr[1];
				}
				if (isset($this->collArr[$this->activeCollid]['prpatt'])) { 				
					$specPk = preg_replace($this->collArr[$this->activeCollid]['prpatt'],$this->collArr[$this->activeCollid]['prrepl'],$specPk);
				}
				if(isset($matchArr[2])){
					$this->webSourceSuffix = $matchArr[2];
				}
			}
		}
		return $specPk;
	}

	private function getOccId($specPk){
		$occId = 0;
		//Check to see if record with pk already exists
		$sql = 'SELECT occid FROM omoccurrences '.
			'WHERE (catalognumber IN("'.$specPk.'"'.(substr($specPk,0,1)=='0'?',"'.ltrim($specPk,'0 ').'"':'').')) '.
			'AND (collid = '.$this->activeCollid.')';
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$occId = $row->occid;
		}
		$rs->free();
		if(!$occId && $this->createNewRec){
			//Records does not exist, create a new one to which image will be linked
			$sql2 = 'INSERT INTO omoccurrences(collid,catalognumber,processingstatus,dateentered) '.
				'VALUES('.$this->activeCollid.',"'.$specPk.'","unprocessed","'.date('Y-m-d H:i:s').'")';
			if($this->conn->query($sql2)){
				$occId = $this->conn->insert_id;
				$this->logOrEcho("Specimen record does not exist; new empty specimen record created and assigned an 'unprocessed' status (occid = ".$occId.") ",1);
			}
			else{
				$this->logOrEcho("ERROR creating new occurrence record: ".$this->conn->error,1);
			}
		}
		if(!$occId){
			$this->logOrEcho("ERROR: File skipped, unable to locate specimen record ".$specPk." (".date('Y-m-d h:i:s A').") ",1);
		}
		return $occId;
	}

	private function databaseImage($occId,$webUrl,$tnUrl,$oUrl){
		$status = true;
		if($occId && is_numeric($occId)){
			$this->logOrEcho("Preparing to load record into database",1);
			//Check to see if image url already exists for that occid
			$imgId = 0;
			$sql = 'SELECT imgid, url, thumbnailurl, originalurl '.
				'FROM images WHERE (occid = '.$occId.') ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if(strcasecmp($r->url,$webUrl) == 0){
					//exact match, thus reset record data with current image urls (thumbnail or original image might be in different locality) 
					if(!$this->conn->query('DELETE FROM specprocessorrawlabels WHERE imgid = '.$r->imgid)){
						$this->logOrEcho('ERROR deleting OCR for image record #'.$r->imgid.' (equal URLs): '.$this->conn->error,1);
					}
					if(!$this->conn->query('DELETE FROM images WHERE imgid = '.$r->imgid)){
						$this->logOrEcho('ERROR deleting image record #'.$r->imgid.' (equal URLs): '.$this->conn->error,1);
					}
				}
				elseif($this->imgExists == 2 && strcasecmp(basename($r->url),basename($webUrl)) == 0){
					//Copy-over-image is set to true and basenames equal, thus delete image PLUS delete old images 
					if(!$this->conn->query('DELETE FROM specprocessorrawlabels WHERE imgid = '.$r->imgid)){
						$this->logOrEcho('ERROR deleting OCR for image record #'.$r->imgid.' (equal basename): '.$this->conn->error,1);
					}
					if($this->conn->query('DELETE FROM images WHERE imgid = '.$r->imgid)){
						//Remove images
						if(substr($r->url,0,1) == '/'){
							$wFile = str_replace($this->imgUrlBase,$this->targetPathBase,$r->url);
							if(file_exists($wFile)){
								unlink($wFile);
							}
						}
						if($tnUrl != $r->thumbnailurl && substr($r->thumbnailurl,0,1) == '/'){
							$tnFile = str_replace($this->imgUrlBase,$this->targetPathBase,$r->thumbnailurl);
							if(file_exists($tnFile)){
								unlink($tnFile);
							}
						} 
						if($oUrl != $r->originalurl && substr($r->originalurl,0,1) == '/'){
							$oFile = str_replace($this->imgUrlBase,$this->targetPathBase,$r->originalurl);
							if(file_exists($oFile)){
								unlink($oFile);
							}
						}
					}
					else{
						$this->logOrEcho('ERROR: Unable to delete image record #'.$r->imgid.' (equal basename): '.$this->conn->error,1);
					}
				}
			}
			$rs->free();

			$sql1 = 'INSERT images(occid,url';
			$sql2 = 'VALUES ('.$occId.',"'.$webUrl.'"';
			if($tnUrl){
				$sql1 .= ',thumbnailurl';
				$sql2 .= ',"'.$tnUrl.'"';
			}
			if($oUrl){
				$sql1 .= ',originalurl';
				$sql2 .= ',"'.$oUrl.'"';
			}
			$sql1 .= ',imagetype,owner) ';
			$sql2 .= ',"specimen","'.$this->collArr[$this->activeCollid]['collname'].'")';
			$sql = $sql1.$sql2;
			if($sql){
				if($this->conn->query($sql)){
					$this->dataLoaded = 1;
				}
				else{
					$status = false;
					$this->logOrEcho("ERROR: Unable to load image record into database: ".$this->conn->error."; SQL: ".$sql,1);
				}
				if($imgId){
					$this->logOrEcho("WARNING: Existing image record replaced; occid: $occId ",1);
				}
				else{
					$this->logOrEcho("SUCCESS: Image record loaded into database",1);
				}
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

	private function updateCollectionStats(){
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
				'WHERE (o.collid = '.$collid.') AND t.rankid IN(180,220,230,240,260)) '. 
				'WHERE cs.collid = '.$collid.'';
			if(!$this->conn->query($sql)){
				$this->logOrEcho('ERROR: unable to update genus counts; '.$this->conn->error);
			}
			
			#Updating species count
			$sql = 'UPDATE omcollectionstats cs '. 
				'SET cs.speciescnt = (SELECT count(DISTINCT t.unitname1, t.unitname2) AS spcnt '. 
				'FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '. 
				'WHERE (o.collid = '.$collid.') AND t.rankid IN(220,230,240,260)) '. 
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
		$this->logOrEcho("Stats update completed");
	}

	//Set and Get functions
	public function setCollArr($cArr){
		if($cArr){
			if(is_array($cArr)){
				$this->collArr = $cArr;
				//Set additional collection info
				//Get Metadata
				$sql = 'SELECT collid, institutioncode, collectioncode, collectionname, managementtype FROM omcollections '.
					'WHERE (collid IN('.implode(',',array_keys($cArr)).'))';
				if($rs = $this->conn->query($sql)){
					if($rs->num_rows){
						while($r = $rs->fetch_object()){
							$this->collArr[$r->collid]['instcode'] = $r->institutioncode;
							$this->collArr[$r->collid]['collcode'] = $r->collectioncode;
							$this->collArr[$r->collid]['collname'] = $r->collectionname;
							$this->collArr[$r->collid]['managementtype'] = $r->managementtype;
						}
					}
					else{
						$this->logOrEcho('ABORT: unable to get collection metadata from database (collids might be wrong) ');
						exit('ABORT: unable to get collection metadata from database');
					}
					$rs->free();
				}
				else{
					$this->logOrEcho('ABORT: unable run SQL to obtain additional collection metadata: '.$this->conn->error);
					exit('ABORT: unable run SQL to obtain additional collection metadata'.$this->conn->error);
					}
			}
		}
		else{
			$this->logOrEcho("Error: collection array does not exist");
			exit("ABORT: collection array does not exist");
		}
	}
	
	public function setSourcePathBase($p){
		if($p && substr($p,-1) != '/' && substr($p,-1) != "\\") $p .= '/';
		$this->sourcePathBase = $p;
	}

	public function getSourcePathBase(){
		return $this->sourcePathBase;
	}

	public function setTargetPathBase($p){
		if($p && substr($p,-1) != '/' && substr($p,-1) != "\\") $p .= '/';
		$this->targetPathBase = $p;
	}

	public function getTargetPathBase(){
		return $this->targetPathBase;
	}

	public function setImgUrlBase($u){
		if($u && substr($u,-1) != '/') $u .= '/';
		$this->imgUrlBase = $u;
	}

	public function getImgUrlBase(){
		return $this->imgUrlBase;
	}

	public function setServerRoot($path) { 
		$this->serverRoot = $path;
	}

	public function setTnPixWidth($tn){
		$this->tnPixWidth = $tn;
	}

	public function getTnPixWidth(){
		return $this->tnPixWidth;
	}

	public function setWebImg($c){
		$this->webImg = $c;
	}

	public function getWebImg(){
		return $this->webImg;
	}

	public function setTnImg($c){
		$this->tnImg = $c;
	}

	public function getTnImg(){
		return $this->tnImg;
	}

	public function setLgImg($c){
		$this->lgImg = $c;
	}

	public function getLgImg(){
		return $this->lgImg;
	}
	
	//Temporarly keep the following three setters to support deprecated functions
	public function setCreateTnImg($c){
		$this->tnImg = $c;
	}
	public function setTnSourceSuffix($s){
		$this->tnSourceSuffix = $s;
	} 

	public function setCreateNewRec($c){
		$this->createNewRec = $c;
	}

	public function getCreateNewRec(){
		return $this->createNewRec;
	}
	
	public function setUseImageMagick($useIM){
		$this->processUsingImageMagick = $useIM;
	}

	public function setLogMode($c){
		$this->logMode = $c;
	}

	public function getLogMode(){
		return $this->logMode;
	}

	public function setLogPath($path){
		if($path && substr($path,-1) != '/' && substr($path,-1) != "\\") $path .= '/';
		$this->logPath = $path;
	}

	private function uriExists($url) {
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
	    if(!@exif_imagetype($url)) $exists = false;

	    return $exists;
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

	private function logOrEcho($str,$indent = 0){
		if($this->logMode == 2){
			if($this->logFH){
				if($indent) $str = "\t".$str;
				fwrite($this->logFH,$str."\n");
			}
		}
		elseif($this->logMode == 1){
			echo '<li '.($indent?'style="margin-left:'.($indent*15).'px"':'').'>'.$str."</li>\n";
		}
	}
}
?>