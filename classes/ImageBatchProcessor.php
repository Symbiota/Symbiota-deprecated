<?php
// Used by /trunk/collections/specprocessor/standalone_scripts/ImageBatchHandler.php
// Used by /trunk/collections/specprocessor/imageprocessor.php

if(isset($SERVER_ROOT)){
	//Use Symbiota connection factory
	if(file_exists($SERVER_ROOT.'/config/dbconnection.php')){ 
		include_once($SERVER_ROOT.'/config/dbconnection.php');
	}
	else{
		include_once('ImageBatchConnectionFactory.php');
	}
	if(file_exists($SERVER_ROOT.'/classes/OccurrenceMaintenance.php')){ 
		include_once($SERVER_ROOT.'/classes/OccurrenceMaintenance.php');
	}
	if(file_exists($SERVER_ROOT.'/classes/UuidFactory.php')){ 
		include_once($SERVER_ROOT.'/classes/UuidFactory.php');
	}
}
// Check for the symbiota class files used herein for parsing 
// batch files of xml formatted strucutured data.
// Fail gracefully if they aren't available.
// Note also that class_exists() is checked for before
// invocation of these parsers in processFolder().
if(isset($SERVER_ROOT)){
	//Files reside within Symbiota file structure
	if (file_exists($SERVER_ROOT.'/classes/SpecProcessorGPI.php')) { 
		@require_once($SERVER_ROOT.'/classes/SpecProcessorGPI.php');
	}
	if (file_exists($SERVER_ROOT.'/classes/SpecProcessorNEVP.php')) {  
		@require_once($SERVER_ROOT.'/classes/SpecProcessorNEVP.php');
	}
}
else{
	//Files reside in same folder and script is run from within the folder
	if(file_exists('SpecProcessorGPI.php')) { 
		@require_once('SpecProcessorGPI.php');
	}
	if (file_exists('SpecProcessorNEVP.php')) {  
		@require_once('SpecProcessorNEVP.php');
	}
}

class ImageBatchProcessor {

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

	private $skeletalFileProcessing = true;
	private $createNewRec = true;
	private $imgExists = 0;			// 0 = skip import, 1 = rename image and save both, 2 = copy over image
	private $dbMetadata = 1;
	private $processUsingImageMagick = 0;

	private $logMode = 0;			//0 = silent, 1 = html, 2 = log file
	private $logFH;
	private $mdOutputFH;
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
		ini_set('auto_detect_line_endings', true);
		if($this->dbMetadata){
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
		}
		//Use deaults located within symbini, if they are available
		//Will be replaced by values within configuration file, if they are set 
		if(isset($GLOBALS['imgWebWidth']) && $GLOBALS['imgWebWidth']) $this->webPixWidth = $GLOBALS['imgWebWidth'];
		if(isset($GLOBALS['imgTnWidth']) && $GLOBALS['imgTnWidth']) $this->tnPixWidth = $GLOBALS['imgTnWidth'];
		if(isset($GLOBALS['imgLgWidth']) && $GLOBALS['imgLgWidth']) $this->lgPixWidth = $GLOBALS['imgLgWidth'];
		if(isset($GLOBALS['imgFileSizeLimit']) && $GLOBALS['imgFileSizeLimit']) $this->webFileSizeLimit = $GLOBALS['imgFileSizeLimit'];
	}

	function __destruct(){
		//Close connection or MD output file
		if($this->dbMetadata){
			if(!($this->conn === false)) $this->conn->close();
		}
		else{
			if($this->mdOutputFH) fclose($this->mdOutputFH);
		}
		
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
		if(substr($this->sourcePathBase,0,4) == 'http'){
			//http protocol, thus test for a valid page
			$headerArr = get_headers($this->sourcePathBase);
			if(!$headerArr){
				$this->logOrEcho('ABORT: sourcePathBase returned bad headers ('.$this->sourcePathBase.')');
				exit();
			} 
			preg_match('/http.+\s{1}(\d{3})\s{1}/i',$headerArr[0],$codeArr);
			if($codeArr[1] == '403'){ 
				$this->logOrEcho('ABORT: sourcePathBase returned Forbidden ('.$this->sourcePathBase.')');
				exit();
			}
			if($codeArr[1] == '404'){ 
				$this->logOrEcho('ABORT: sourcePathBase returned a page Not Found error ('.$this->sourcePathBase.')');
				exit();
			}
			if($codeArr[1] != '200'){ 
				$this->logOrEcho('ABORT: sourcePathBase returned error code '.$codeArr[1].' ('.$this->sourcePathBase.')');
				exit();
			}
		}
		elseif(!file_exists($this->sourcePathBase)){
			//Make sure source path exists
			$this->logOrEcho('ABORT: sourcePathBase does not exist ('.$this->sourcePathBase.')');
			exit();
		}
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
			
			//Set source and target path fragments
			$sourcePathFrag = '';
			$this->targetPathFrag = '';
			if(isset($cArr['sourcePathFrag'])){
				$sourcePathFrag = $cArr['sourcePathFrag'];
				$this->targetPathFrag = $cArr['sourcePathFrag'];
			}
			else{
				$this->targetPathFrag .= $collStr;
			}
			if(substr($this->targetPathFrag,-1) != "/" && substr($this->targetPathFrag,-1) != "\\"){
				$this->targetPathFrag .= '/';
			}
			if($sourcePathFrag && substr($sourcePathFrag,-1) != "/" && substr($sourcePathFrag,-1) != "\\"){
				$sourcePathFrag .= '/';
			}
			if(!file_exists($this->targetPathBase.$this->targetPathFrag)){
				if(!mkdir($this->targetPathBase.$this->targetPathFrag,0777,true)){
					$this->logOrEcho("ERROR: unable to create new folder (".$this->targetPathBase.$this->targetPathFrag.") ");
					exit("ABORT: unable to create new folder (".$this->targetPathBase.$this->targetPathFrag.")");
				}
			}

			//If originals are to be kept, make sure target folders exist
			if($this->keepOrig){
				//Variable used in path to store original files
				$this->origPathFrag = 'orig/'.date("Ym").'/';
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

			$this->logOrEcho('Starting image processing: '.$sourcePathFrag);
			if(substr($this->sourcePathBase,0,4) == 'http'){
				//http protocol, thus test for a valid page
				$this->processHtml($sourcePathFrag);
			}
			else{
				$this->processFolder($sourcePathFrag);
			}
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

	private function processFolder($pathFrag = ''){
		set_time_limit(3600);
		//$this->logOrEcho("Processing: ".$this->sourcePathBase.$pathFrag);
		//Read file and loop through images
		if(file_exists($this->sourcePathBase.$pathFrag)){
			if($dirFH = opendir($this->sourcePathBase.$pathFrag)){
				while($fileName = readdir($dirFH)){
					if($fileName != "." && $fileName != ".." && $fileName != ".svn"){
						if(is_file($this->sourcePathBase.$pathFrag.$fileName)){
							if(!stripos($fileName,$this->tnSourceSuffix.'.jpg') && !stripos($fileName,$this->lgSourceSuffix.'.jpg')){
								$this->logOrEcho("Processing File (".date('Y-m-d h:i:s A')."): ".$fileName);
								$fileExt = strtolower(substr($fileName,strrpos($fileName,'.')));
								if($fileExt == ".jpg"){
									if($this->processImageFile($fileName,$pathFrag)){
										if(!in_array($this->activeCollid,$this->collProcessedArr)) $this->collProcessedArr[] = $this->activeCollid;
									}
								}
								elseif($fileExt == ".tif"){
									$this->logOrEcho("ERROR: File skipped, TIFFs image files are not a supported: ".$fileName,1);
									//Do something, like convert to jpg???
									//but for now do nothing
								}
								elseif(($fileExt == ".csv" || $fileExt == ".txt" || $fileExt == ".tab" || $fileExt == ".dat")){
									if($this->skeletalFileProcessing){
										//Is skeletal file exists. Append data to database records
										$this->processSkeletalFile($this->sourcePathBase.$pathFrag.$fileName); 
										if(!in_array($this->activeCollid,$this->collProcessedArr)) $this->collProcessedArr[] = $this->activeCollid;
									}
								}
								elseif($fileExt==".xml") {
                                    // The loop through collArr can result in same file being processed more than
                                    // once if the same pathFrag is associated with more than one collection.
                                    if (!in_array("$pathFrag$fileName",$this->processedFiles)) { 
									$this->processXMLFile($fileName,$pathFrag);
                                         $this->processedFiles[] = "$pathFrag$fileName";
                                         // TODO: It would seem that adding the collection to collProcessedArr 
                                         // should accomplish what processedFiles[] is being added above to
                                         // do, need to investigate further and perhaps use it as a fix.
									if(!in_array($this->activeCollid,$this->collProcessedArr)) $this->collProcessedArr[] = $this->activeCollid;
								}
								}
								elseif($fileExt==".ds_store" || strtolower($fileName)=='thumbs.db'){
									unlink($this->sourcePathBase.$pathFrag.$fileName);
								}
								else{
									$this->logOrEcho("ERROR: File skipped, not a supported image file: ".$fileName,1);
								}
							}
						}
						elseif(is_dir($this->sourcePathBase.$pathFrag.$fileName)){
							//if(strpos($fileName,'.')) $fileName = str_replace('.','\.',$fileName);
							$this->processFolder($pathFrag.$fileName."/");
						}
					}
				}
				if($dirFH) closedir($dirFH);
			}
			else{
				$this->logOrEcho("ERROR: unable to access source directory: ".$this->sourcePathBase.$pathFrag,1);
			}
		}
		else{
			$this->logOrEcho("Source path does not exist: ".$this->sourcePathBase.$pathFrag,1);
			//exit("ABORT: Source path does not exist: ".$this->sourcePathBase.$pathFrag);
		}
	}

	private function processHtml($pathFrag = ''){
		set_time_limit(3600);
		//$this->logOrEcho("Processing: ".$this->sourcePathBase.$pathFrag);
		//Check  to make sure page is readable
		$headerArr = get_headers($this->sourcePathBase.$pathFrag);
		preg_match('/http.+\s{1}(\d{3})\s{1}/i',$headerArr[0],$codeArr);
		if($codeArr[1] == '200'){
			$dom = new DOMDocument();
			$dom->loadHTMLFile($this->sourcePathBase.$pathFrag);
			$aNodes= $dom->getElementsByTagName('a');
			$skipAnchors = array('Name','Last modified','Size','Description','Parent Directory');
			foreach( $aNodes as $aNode ) {
				//$fileName = $aNode->getAttribute('href');
				$fileName = trim($aNode->nodeValue);
				if(!in_array($fileName,$skipAnchors)){
					$fileExt = '';
					if(strrpos($fileName,'.')) $fileExt = strtolower(substr($fileName,strrpos($fileName,'.')+1));
					if($fileExt){
						if(!stripos($fileName,$this->tnSourceSuffix.'.jpg') && !stripos($fileName,$this->lgSourceSuffix.'.jpg')){
							$this->logOrEcho("Processing File (".date('Y-m-d h:i:s A')."): ".$fileName);
							if($fileExt == "jpg"){
								if($this->processImageFile($fileName,$pathFrag)){
									if(!in_array($this->activeCollid,$this->collProcessedArr)) $this->collProcessedArr[] = $this->activeCollid;
								}
							}
							elseif($fileExt == "tif"){
								$this->logOrEcho("ERROR: File skipped, TIFFs image files are not a supported: ".$fileName,1);
								//Do something, like convert to jpg???
								//but for now do nothing
							}
							elseif(($fileExt == "csv" || $fileExt == "txt" || $fileExt == "tab" || $fileExt == "dat")){
								if($this->skeletalFileProcessing){
									//Is skeletal file. Process and append data to database records
									$this->processSkeletalFile($this->sourcePathBase.$pathFrag.$fileName); 
									if(!in_array($this->activeCollid,$this->collProcessedArr)) $this->collProcessedArr[] = $this->activeCollid;
								}
							}
							elseif($fileExt=="xml") {
								$this->processXMLFile($fileName,$pathFrag);
								if(!in_array($this->activeCollid,$this->collProcessedArr)) $this->collProcessedArr[] = $this->activeCollid;
							}
							else{
								$this->logOrEcho("ERROR: File skipped, not a supported image file: ".$fileName,1);
							}
						}
					}
					elseif(stripos($fileName,'Parent Dir') === false){
						$this->logOrEcho('New dir path: '.$this->sourcePathBase.$pathFrag.$fileName.'<br/>');
						if(substr($fileName,-1) != '/') $fileName .= '/';
						$this->processHtml($pathFrag.$fileName);
					}
				}
			}
		}
		else{
			$this->logOrEcho("Source directory skipped (code ".$codeArr[0].") : ".$this->sourcePathBase.$pathFrag,1);
			//exit("ABORT: Source path does not exist: ".$this->sourcePathBase.$pathFrag);
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
		//$this->logOrEcho("Processing image (".date('Y-m-d h:i:s A')."): ".$fileName);
		//ob_flush();
		flush();
		//Grab Primary Key from filename
		if($specPk = $this->getPrimaryKey($fileName)){
			$occId = 0;
			if($this->dbMetadata){
				$occId = $this->getOccId($specPk);
			}
			$fileNameExt = '.jpg';
			$fileNameBase = $fileName;
			if($p = strrpos($fileName,'.')){
				$fileNameExt = substr($fileName,$p);
				$fileNameBase = substr($fileName,0,$p);
				if($this->webSourceSuffix){
					$fileNameBase = substr($fileNameBase,0,-1*strlen($this->webSourceSuffix));
				}
			}
			if($occId || !$this->dbMetadata){
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
				$targetFileName = $fileName;
				if($this->webImg == 1 || $this->webImg == 2){
					//Check to see if image already exists at target, if so, delete or rename target
					if(file_exists($targetPath.$targetFileName)){
						if($this->imgExists == 2){
							//Copy over image (ie remove old images)
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
						elseif($this->imgExists == 1){
							//Rename image before saving
							$cnt = 1;
							while(file_exists($targetPath.$targetFileName)){
								$targetFileName = str_ireplace(".jpg","_".$cnt.".jpg",$fileName);
								$cnt++;
							}
						}
						else{
							// skip import of image ($this->imgExists === 0)
							$this->logOrEcho("NOTICE: image import skipped because image file already exists ",1);
							return false;
						}
					}
				}
				elseif($this->webImg == 3){
					if(!$this->imgExists){
						//Check to see if database record already exists, and if so skip import
						$recExists = 0; 
						$sql = 'SELECT url '.
							'FROM images WHERE (occid = '.$occId.') ';
						$rs = $this->conn->query($sql);
						while($r = $rs->fetch_object()){
							if(stripos($r->url,$fileName)){
								$recExists = 1;
							}
						}
						$rs->free();
						if($recExists){
							$this->logOrEcho("NOTICE: image import skipped because image file already exists ",1);
							return false;
						}
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
					
					//$this->logOrEcho("Loading image (".date('Y-m-d h:i:s A').")",1);
					//ob_flush();
					//flush();
					
					//Create web image
					$webUrlFrag = '';
					if($this->webImg){
						if($this->webImg == 1){
							// 1 = evaluate source and import
							if($fileSize < $this->webFileSizeLimit && $width < ($this->webPixWidth*2)){
								if(copy($sourcePath.$fileName,$targetPath.$targetFileName)){
									$webUrlFrag = $this->imgUrlBase.$targetFrag.$targetFileName;
									$this->logOrEcho("Source image imported as web image (".date('Y-m-d h:i:s A').") ",1);
								}
							}
							else{
								if($this->createNewImage($sourcePath.$fileName,$targetPath.$targetFileName,$this->webPixWidth,round($this->webPixWidth*$height/$width),$width,$height)){
									$webUrlFrag = $this->imgUrlBase.$targetFrag.$targetFileName;
									$this->logOrEcho("Web image created from source image (".date('Y-m-d h:i:s A').") ",1);
								}
							}
						}
						elseif($this->webImg == 2){
							// 2 = import source and use as is
							$webFileName = $fileNameBase.$this->webSourceSuffix.$fileNameExt;
							if(copy($sourcePath.$webFileName,$targetPath.$targetFileName)){
								$webUrlFrag = $this->imgUrlBase.$targetFrag.$targetFileName;
								$this->logOrEcho("Source image imported as web image (".date('Y-m-d h:i:s A').") ",1);
							}
						}
						elseif($this->webImg == 3){
							// 3 = map to source as the web image
							$webImgCreated = true;
							$webFileName = $fileNameBase.$this->webSourceSuffix.$fileNameExt;
							$webUrlFrag = $sourcePath.$webFileName;
							$this->logOrEcho("Source used as web image (".date('Y-m-d h:i:s A').") ",1);
						}
					}
					if(!$webUrlFrag){
						$this->logOrEcho("Failed to create web image ",1);
					}
					//Create Large Image
					$lgUrlFrag = "";
					if($this->lgImg){
						//Large version of image should not be excluded 
						$lgTargetFileName = substr($targetFileName,0,-4)."_lg.jpg";
						if($this->lgImg == 1){
							// 1 = import source
							if($width > ($this->webPixWidth*1.3)){
								//Source image is big enough to serve as large version 
								if($width > $this->lgPixWidth || ($fileSize && $fileSize > $this->lgFileSizeLimit)){
									//Image is too width or file size is too big, thus let's resize and import
									if($this->createNewImage($sourcePath.$fileName,$targetPath.$lgTargetFileName,$this->lgPixWidth,round($this->lgPixWidth*$height/$width),$width,$height)){
										$lgUrlFrag = $this->imgUrlBase.$targetFrag.$lgTargetFileName;
										$this->logOrEcho("Resized source as large derivative (".date('Y-m-d h:i:s A').") ",1);
									}
								}
								else{
									//Source can serve as large version, thus just import as is
									if(copy($sourcePath.$fileName,$targetPath.$lgTargetFileName)){
										$lgUrlFrag = $this->imgUrlBase.$targetFrag.$lgTargetFileName;
										$this->logOrEcho("Imported source as large derivative (".date('Y-m-d h:i:s A').") ",1);
									}
									else{
										$this->logOrEcho("WARNING: unable to import large derivative (".$sourcePath.$lgSourceFileName.") ",1);
									}
								}
							}
						}
						elseif($this->lgImg == 2){
							// 2 = map to source
							$lgUrlFrag = $sourcePath.$fileName;
							$this->logOrEcho("Used source as large derivative (".date('Y-m-d h:i:s A').") ",1);
						}
						elseif($this->lgImg == 3){
							// 3 = import large version (_lg.jpg or $this->lgSourceSuffix.'.jpg'), if it exists
							$lgSourceFileName = $fileNameBase.$this->lgSourceSuffix.$fileNameExt;
							if($this->uriExists($sourcePath.$lgSourceFileName)){
								if(copy($sourcePath.$lgSourceFileName,$targetPath.$lgTargetFileName)){
									if(substr($sourcePath,0,4) != 'http') unlink($sourcePath.$lgSourceFileName);
									$lgUrlFrag = $this->imgUrlBase.$targetFrag.$lgTargetFileName;
									$this->logOrEcho("Imported large derivative of source for large version(".date('Y-m-d h:i:s A').") ",1);
								}
							}
							else{
								$this->logOrEcho("WARNING: unable to import large derivative (".$sourcePath.$lgSourceFileName.") ",1);
							}
						}
						elseif($this->lgImg == 4){
							// 4 = map to large version (_lg.jpg or $this->lgSourceSuffix.'.jpg'), if it exist
							$lgSourceFileName = $fileNameBase.$this->lgSourceSuffix.$fileNameExt;
							if($this->uriExists($sourcePath.$lgSourceFileName)){
								$lgUrlFrag = $sourcePath.$lgSourceFileName;
								$this->logOrEcho("Large version mapped to large derivative of source (".date('Y-m-d h:i:s A').") ",1);
							}
							else{
								$this->logOrEcho("WARNING: unable to map to large derivative (".$sourcePath.$lgSourceFileName.") ",1);
							}
						}
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
					$this->recordImageMetadata(($this->dbMetadata?$occId:$specPk),$webUrlFrag,$tnUrlFrag,$lgUrlFrag);
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
			$this->logOrEcho("File skipped (".$sourcePathFrag.$fileName."), unable to extract specimen identifier",1);
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
			if(substr($pmTerm,0,1) != '/' || stripos(substr($pmTerm,-3),'/') === false){
				$this->logOrEcho("PROCESS ABORTED: Regular Expression term illegal due to missing forward slashes delimiting the term: ".$pmTerm);
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
	
	private function writeMetadataToFile($specPk,$webUrl,$tnUrl,$oUrl){
		$status = true;
		if($this->mdOutputFH){
			$status = fwrite($this->mdOutputFH, $this->activeCollid.',"'.$specPk.'","'.$webUrl.'","'.$tnUrl.'","'.$oUrl.'"'."\n");
		}
		return $status;
	}
	
	private function processSkeletalFile($filePath){
		$this->logOrEcho("Preparing to load Skeletal file into database",1);
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
					$this->logOrEcho("ERROR: Unable to identify delimiter for metadata file ",1);
					return false;
				}
			}
			else{
				$this->logOrEcho("ERROR: Skeletal file skipped: unable to determine file type ",1);
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
								$valueStr = '';
								if(array_key_exists($k,$recordArr)) $valueStr = $recordArr[$k];
								if($valueStr){
									//If value is enclosed by quotes, remove quotes
									if(substr($valueStr,0,1) == '"' && substr($valueStr,-1) == '"'){
										$valueStr = substr($valueStr,1,strlen($valueStr)-2);
									}
									$valueStr = trim($valueStr);
									if($valueStr) $recMap[$hStr] = $valueStr;
								}
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
								$numStr = trim($this->conn->real_escape_string($recMap['exsiccatinumber'])," #num");
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
						//If exsiccati info is there, but we can't link to an indexed exsiccati, then lets keep that info and put in occurrenceRemarks 
						if(array_key_exists('exsiccatititle',$recMap) && $recMap['exsiccatititle'] && (!array_key_exists('ometid',$recMap) || !$recMap['ometid'])){
							$exsStr = $recMap['exsiccatititle'];
							if(array_key_exists('exsiccatinumber',$recMap) && $recMap['exsiccatinumber']){
								$exsStr .= ', '.$recMap['exsiccatinumber'].'; ';
							}
							$occRemarks = $recMap['occurrenceremarks'];
							if($occRemarks) $occRemarks .= '; ';
							$recMap['occurrenceremarks'] = $occRemarks.$exsStr;
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
								'FROM omoccurrences '.
								'WHERE collid = '.$this->activeCollid.' AND (catalognumber IN("'.$catNum.'"'.(substr($catNum,0,1)=='0'?',"'.ltrim($catNum,"0 ").'"':'').')) ';
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
													//$occRemarkArr[$activeField] = $activeValue;
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
														//$occRemarkArr[$activeField] = $activeValue;
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
											//$occRemarkArr[$activeField] = $activeValue;
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
											$this->logOrEcho("SQL : $sqlUpdate ",1);
										}
									}
								}
							}
							else{
								//Insert new record
								if($activeFields){
									$sqlIns1 = 'INSERT INTO omoccurrences(collid,catalogNumber,processingstatus,dateentered';
									$sqlIns2 = 'VALUES ('.$this->activeCollid.',"'.$catNum.'","unprocessed","'.date('Y-m-d H:i:s').'"';
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
										$this->logOrEcho('ERROR trying to load new skeletal record: '.$this->conn->error);
										//$this->logOrEcho("SQL : $sqlIns ",1);
									}
								}
							}
							$rs->free();
							//Load Exsiccati if it exists
							if(isset($recMap['omenid']) && $occid){
								$sqlExs ='INSERT INTO omexsiccatiocclink(omenid,occid) VALUES('.$recMap['omenid'].','.$occid.')';
								if(!$this->conn->query($sqlExs)){
									$this->logOrEcho('ERROR linking record to exsiccati ('.$recMap['omenid'].'-'.$occid.'): '.$this->conn->error);
									//$this->logOrEcho('SQL : '.$sqlExs,1);
								}
							}
						}
						unset($recMap);
					}
				}
				else{
					$this->logOrEcho("ERROR: Failed to locate catalognumber MD within file (".$filePath."),  ",1);
					return false;
				}
			}
			$this->logOrEcho("Skeletal file loaded ",1);
			fclose($fh);
			//if($this->keepOrig){
			//Skeletal data files are small, thus let's keep them by default 
			if(true){
				$fileName = substr($filePath,strrpos($filePath,'/')).'.orig_'.time();
				if(!file_exists($this->targetPathBase.$this->targetPathFrag.'orig_skeletal')){
					mkdir($this->targetPathBase.$this->targetPathFrag.'orig_skeletal');
				}
				if(!rename($filePath,$this->targetPathBase.$this->targetPathFrag.'orig_skeletal'.$fileName)){
					$this->logOrEcho("ERROR: unable to move (".$filePath.") ",1);
				}
			} 
			else{
				if(!unlink($filePath)){
					$this->logOrEcho("ERROR: unable to delete file (".$filePath.") ",1);
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
		//Do some more cleaning of the data after it haas been indexed in the omoccurrences table
			$occurMain = new OccurrenceMaintenance($this->conn);
	
			$this->logOrEcho('Cleaning house...');
			$collString = implode(',',$this->collProcessedArr);
			if(!$occurMain->generalOccurrenceCleaning($collString)){
				$errorArr = $occurMain->getErrorArr();
				foreach($errorArr as $errorStr){
					$this->logOrEcho($errorStr,1);
				}
			}
			
			$this->logOrEcho('Protecting sensitive species...');
			if(!$occurMain->protectRareSpecies()){
				$errorArr = $occurMain->getErrorArr();
				foreach($errorArr as $errorStr){
					$this->logOrEcho($errorStr,1);
				}
			}
			
			$this->logOrEcho('Updating statistics...');
			foreach($this->collProcessedArr as $collid){
				if(!$occurMain->updateCollectionStats($collid)){
					$errorArr = $occurMain->getErrorArr();
					foreach($errorArr as $errorStr){
						$this->logOrEcho($errorStr,1);
					}
				}
			}
			
			$this->logOrEcho('Populating global unique identifiers (GUIDs) for all records...');
			$uuidManager = new UuidFactory();
			$uuidManager->setSilent(1);
			$uuidManager->populateGuids();
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

	public function setWebFileSizeLimit($size){
		$this->webFileSizeLimit = $size;
	}

	public function getWebFileSizeLimit(){
		return $this->webFileSizeLimit;
	}

	public function setLgFileSizeLimit($size){
		$this->lgFileSizeLimit = $size;
	}

	public function getLgFileSizeLimit(){
		return $this->lgFileSizeLimit;
	}

	public function setJpgQuality($q){
		$this->jpgQuality = $q;
	}

	public function getJpgQuality(){
		return $this->jpgQuality;
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
	public function setCreateWebImg($c){
		$this->webImg = $c;
	}
	public function setCreateTnImg($c){
		$this->tnImg = $c;
	}
	public function setCreateLgImg($c){
		$this->lgImg = $c;
	} 

	public function setWebSourceSuffix($s){
		$this->webSourceSuffix = $s;
	} 

	public function setTnSourceSuffix($s){
		$this->tnSourceSuffix = $s;
	} 

	public function setLgSourceSuffix($s){
		$this->lgSourceSuffix = $s;
	} 

	public function setKeepOrig($c){
		$this->keepOrig = $c;
	}

	public function getKeepOrig(){
		return $this->keepOrig;
	}

	public function setSkeletalFileProcessing($c){
		$this->skeletalFileProcessing = $c;
	}

	public function getSkeletalFileProcessing(){
		return $this->skeletalFileProcessing;
	}
	
	public function setCreateNewRec($c){
		$this->createNewRec = $c;
	}

	public function getCreateNewRec(){
		return $this->createNewRec;
	}
	
	public function setCopyOverImg($c){
		if($c == 1){
			$this->imgExists = 2;
		}
		else{
			$this->imgExists = 1;
		}
	}

	public function getImgExists(){
		return $this->imgExists;
	}
	
	public function setImgExists($c){
		$this->imgExists = $c;
	}

	public function setDbMetadata($v){
		$this->dbMetadata = $v;
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
	
	private function encodeString($inStr){
		global $charset;
		$retStr = trim($inStr);
		//Get rid of annoying curly quotes
		$search = array("�", "�", "`", "�", "�"); 
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

	private function logOrEcho($str,$indent = 0){
		if($this->logMode == 2){
			if($this->logFH){
				if($indent) $str = "\t".$str;
				fwrite($this->logFH,$str."\n");
			}
		}
		elseif($this->logMode == 1){
			echo '<li '.($indent?'style="margin-left:'.($indent*15).'px"':'').'>'.$str."</li>\n";
			ob_flush();
			flush();
		}
	}
}
?>