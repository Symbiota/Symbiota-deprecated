<?php
date_default_timezone_set('America/Phoenix');

//Pattern matching tern used to locate primary key (PK) of specimen record
$specKeyPattern = '/ASU\d{7}/';
//filename = grab PK from file name; ocr = attempt to retrieve PK from image using OCR
$specKeyRetrieval = 'filename';
//Folder containing unprecessed images; read access needed
$sourcePath = 'C:/htdocs/symbiota/trunk/temp/images/toprocess/';
//Folder where images are to be placed; write access needed
$targetPath = 'C:/htdocs/symbiota/trunk/temp/images/';
//Url base needed to build image URL that will be save in DB
$imgUrlBase = '/seinet/temp/images/';
$webPixWidth = 1200;
$tnPixWidth = 130;
$lgPixWidth = 3000;
//Value between 0 and 100
$jpgCompression = 60;	  

//Create thumbnail versions of image
$createTnImg = 1;		
//Create large version of image, given source image is large enough
$createLgImg = 1;		
//Weather to copyover images with matching names (includes path) or rename new image and keep both$copyOverImg = 1;		

//Path to where log files will be placed
$logPath = '';

//0 = write image metadata to file; 1 = write metadata to Symbiota database
$dbMetadata = 0;		

//Variables below needed only if connecting directly with database
//If record matching PK is not found, should a new blank record be created?
$collId = 1;
$createNewRec = 1;
$copyOverImg = 1;

//-------------------------------------------------------------------------------------------//
//End of variable assignment. Don't modify code below.
//Create processor and procede with processing images  
$specManager = new SpecProcessorImage($logPath);

//Set variables
$specManager->setSpecKeyPattern($specKeyPattern);	
$specManager->setSpecKeyRetrieval($specKeyRetrieval);		 
$specManager->setSourcePath($sourcePath);		
$specManager->setTargetPath($targetPath);		
$specManager->setImgUrlBase($imgUrlBase);		
$specManager->setWebPixWidth($webPixWidth);
$specManager->setTnPixWidth($tnPixWidth);
$specManager->setLgPixWidth($lgPixWidth);
$specManager->setJpgCompression($jpgCompression);	  

$specManager->setCreateTnImg($createTnImg);		
$specManager->setCreateLgImg($createLgImg);		
$specManager->setCreateNewRec($createNewRec);
$specManager->setCopyOverImg($copyOverImg);


$specManager->setDbMetadata($dbMetadata);
if($dbMetadata){
	$specManager->setCollId($collId);
}

//Run process
$specManager->batchLoadImages();

class SpecProcessorManager {

	protected $conn;
	protected $collId = 0;
	protected $spprId = 0;
	protected $title;
	protected $collectionName;
	protected $managementType;
	protected $specKeyPattern;
	protected $specKeyRetrieval;
	protected $coordX1;
	protected $coordX2;
	protected $coordY1;
	protected $coordY2;
	protected $sourcePath;
	protected $targetPath;
	protected $imgUrlBase;
	protected $webPixWidth;
	protected $tnPixWidth;
	protected $lgPixWidth;
	protected $jpgCompression= 60;
	protected $createTnImg;
	protected $createLgImg;
	
	protected $createNewRec = true;
	protected $copyOverImg = true;
	protected $dbMetadata = 1;
	
	protected $logPath;
	protected $logFH;
	protected $logErrFH;
	
	function __construct($logPath) {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		$this->logPath = $logPath;
		if(!$this->logPath && array_key_exists('tempDirRoot',$GLOBALS)){
			$this->logPath = $GLOBALS['tempDirRoot'];
		}
		if(!$this->logPath && array_key_exists('serverRoot',$GLOBALS)){
			$this->logPath = $GLOBALS['serverRoot'].'/temp/';
		}
		if($this->logPath){
			if(substr($this->logPath,-1) != '/') $this->logPath .= '/'; 
			$this->logPath .= 'logs/';
		}
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function setCollId($id) {
		$this->collId = $id;
		if($this->collId && !$this->collectionName){
			$sql = 'SELECT collid, collectionname, managementtype FROM omcollections WHERE collid = '.$this->collId; 
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->collectionName = $row->collectionname;
				$this->managementType = $row->managementtype;
			}
			$rs->close();
		}
	}

	public function setSpprId($id) {
		if($id) $this->spprid = $id;
	}
	
	protected function getPrimaryKey($str){
		$specPk = '';
		$pkPattern = $this->specKeyPattern;
		if(preg_match($pkPattern,$str,$matchArr)){
			$specPk = $matchArr[0];
		}
		return $specPk;
	}
	
	protected function ocrImage(){
		$labelBlock = '';
		//Process image to aid OCR
			//Convert to TIF
			//contrast, brightness, B/W ???
		
		$output = array();
		exec('tesseract', $output);
		
		//Obtain text from tesseract output file

		
		return $labelBlock;
	}

	protected function loadRawFragment($occId,$labelBlock){
		//load raw label record
		$status = true; 
		$sql = 'INSERT INTO specprocessorrawlabels(occid,rawstr) VALUES('.$occId.',"'.$this->cleanStr($labelBlock).'")';
		if(!$this->conn->query($sql)){
			if($this->logErrFH){
				fwrite($this->logErrFH, "\tERROR: Unable to load Raw Text Fragment into database specprocessorrawlabels: ");
				fwrite($this->logErrFH, $this->conn->error." \n");
				fwrite($this->logErrFH, "\tSQL: $sql \n");
			}
			$status = false;
		}
		return $status;
	}

	protected function getOccId($specPk){
		$occId = 0;
		//Check to see if record with pk already exists
		$sql = 'SELECT occid FROM omoccurrences WHERE catalognumber = "'.$specPk.'" AND collid = '.$this->collId;
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$occId = $row->occid;
		}
		$rs->close();
		if(!$occId && $this->createNewRec){
			//Records does not exist, create a new one to which image will be linked
			$sql2 = 'INSERT INTO omoccurrences(collid,catalognumber'.(stripos($this->managementType,'Live')!==false?'':',dbpk').',processingstatus) '.
				'VALUES('.$this->collId.',"'.$specPk.'"'.(stripos($this->managementType,'Live')!==false?'':',"'.$specPk.'"').',"unparsed")';
			if($this->conn->query($sql2)){
				$occId = $this->conn->insert_id;
			} 
		}
		return $occId;
	}
	
	protected function recordImageMetadata($specPk,$webUrl,$tnUrl,$oUrl){
		$status = false;
		if($this->dbMetadata){
			$status = $this->databaseImage($specPk,$webUrl,$tnUrl,$oUrl);
		}
		else{
			$status = $this->writeToFile($specPk,$webUrl,$tnUrl,$oUrl);
		}
		return $status;
	}
	
	private function databaseImage($specPk,$webUrl,$tnUrl,$oUrl){
		$status = true;
		if($specPk){
			$occId = $this->getOccId($specPk);
		
	        //echo "<li style='margin-left:20px;'>Preparing to load record into database</li>";
			if($this->logFH) fwrite($this->logFH, "Preparing to load record into database\n");
			$imgUrl = $this->imgUrlBase;
			if(substr($imgUrl,-1) != '/') $imgUrl = '/';
			//Check to see if image url already exists for that occid
			$recCnt = 0;
			$sql = 'SELECT imgid FROM images WHERE occid = '.$occId.' AND url = "'.$imgUrl.$webUrl.'"';
			$rs = $this->conn->query($sql);
			if($rs){
				$recCnt = $rs->num_rows;
				$rs->close();
			}
			if($recCnt){
				if($this->logErrFH) fwrite($this->logErrFH, "\tWARNING: Image record already exists with matching url and occid (".$occId."). Data loading skipped\n");
			}
			else{
				$sql1 = 'INSERT images(occid,url';
				$sql2 = 'VALUES ('.$occId.',"'.$imgUrl.$webUrl.'"';
				if($tnUrl){
					$sql1 .= ',thumbnailurl';
					$sql2 .= ',"'.$imgUrl.$tnUrl.'"';
				}
				if($oUrl){
					$sql1 .= ',originalurl';
					$sql2 .= ',"'.$imgUrl.$oUrl.'"'; 
				}
				$sql1 .= ',imagetype,owner) ';
				$sql2 .= ',"specimen","'.$this->collectionName.'")';
				if(!$this->conn->query($sql1.$sql2)){
					$status = false;
					if($this->logErrFH) fwrite($this->logErrFH, "\tERROR: Unable to load image record into database: ".$this->conn->error."; SQL: ".$sql1.$sql2."\n");
				}
			}
		}
		else{
			$status = false;
			if($this->logErrFH) fwrite($this->logErrFH, "ERROR: Missing occid (omoccurrences PK), unable to load record \n");
		}
		if($status){
			echo "<li style='margin-left:20px;'>Image record loaded into database</li>\n";
			if($this->logFH) fwrite($this->logFH, "\tSUCCESS: Image record loaded into database\n");
		}
		else{
			if($this->logFH) fwrite($this->logFH, "\tERROR: Unable to load image record into database. See error log for details. \n");
	        echo "<li style='margin-left:20px;'><b>ERROR:</b> Unable to load image record into database. See error log for details</li>\n";
		}
		return $status;
	}

	private function writeToFile($specPk,$webUrl,$tnUrl,$oUrl){
		
		
	}
	
	public function editProject($editArr){
		if($editArr['spprid']){
			$sql = 'UPDATE specprocessorprojects '.
				'SET title = "'.$editArr['title'].'", speckeypattern = "'.str_replace('\\','\\\\',$editArr['speckeypattern']).
				'", speckeyretrieval = "'.(array_key_exists('speckeyretrieval',$editArr)?$editArr['speckeyretrieval']:'filename').
				'", sourcepath = "'.$editArr['sourcepath'].'", targetpath = "'.$editArr['targetpath'].'", imgurl = "'.$editArr['imgurl'].
				'", webpixwidth = '.$editArr['webpixwidth'].', tnpixwidth = '.$editArr['tnpixwidth'].', lgpixwidth = '.$editArr['lgpixwidth'].
				', jpgcompression = '.$editArr['jpgcompression'].
				', createtnimg = '.(array_key_exists('createtnimg',$editArr)?'1':'0').
				', createlgimg = '.(array_key_exists('createlgimg',$editArr)?'1':'0').' '.
				'WHERE spprid = '.$editArr['spprid'];
			//echo 'SQL: '.$sql;
			$this->conn->query($sql);
		}
	}

	public function addProject($addArr){
		$sql = 'INSERT INTO specprocessorprojects(collid,title,speckeypattern,speckeyretrieval,sourcepath,targetpath,'.
			'imgurl,webpixwidth,tnpixwidth,lgpixwidth,jpgcompression,createtnimg,createlgimg) '.
			'VALUES('.$this->collId.',"'.$addArr['title'].'","'.$addArr['speckeypattern'].'","'.$addArr['speckeyretrieval'].'","'.
			$addArr['sourcepath'].'","'.$addArr['targetpath'].'","'.$addArr['imgurl'].'",'.$addArr['webpixwidth'].','.
			$addArr['tnpixwidth'].','.$addArr['lgpixwidth'].','.$addArr['jpgcompression'].','.
			(array_key_exists('createtnimg',$addArr)?'1':'0').','.(array_key_exists('createlgimg',$addArr)?'1':'0').')';
		$this->conn->query($sql);
	}

	public function deleteProject($spprId){
		$sql = 'DELETE FROM specprocessorprojects WHERE spprid = '.$spprId;
		$this->conn->query($sql);
	}

	public function setProjVariables(){
		if($this->spprid){
			$sql = 'SELECT p.collid, p.title, p.speckeypattern, p.speckeyretrieval, p.coordx1, p.coordx2, p.coordy1, p.coordy2, '. 
				'p.sourcepath, p.targetpath, p.imgurl, p.webpixwidth, p.tnpixwidth, p.lgpixwidth, p.jpgcompression, p.createtnimg, p.createlgimg '.
				'FROM specprocessorprojects p '.
				'WHERE p.spprid = '.$this->spprid; 
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				if(!$this->collId) $this->setCollId($row->collid); 
				$this->title = $row->title;
				$this->specKeyPattern = $row->speckeypattern;
				$this->specKeyRetrieval = $row->speckeyretrieval;
				$this->coordX1 = $row->coordx1;
				$this->coordX2 = $row->coordx2;
				$this->coordY1 = $row->coordy1;
				$this->coordY2 = $row->coordy2;
				$this->sourcePath = $row->sourcepath;
				if(substr($this->sourcePath,-1) != '/') $this->sourcePath .= '/'; 
				$this->targetPath = $row->targetpath;
				if(substr($this->targetPath,-1) != '/') $this->targetPath .= '/'; 
				$this->imgUrlBase = $row->imgurl;
				if(substr($this->imgUrlBase,-1) != '/') $this->imgUrlBase .= '/'; 
				$this->webPixWidth = $row->webpixwidth;
				$this->tnPixWidth = $row->tnpixwidth;
				$this->lgPixWidth = $row->lgpixwidth;
				$this->jpgCompression = $row->jpgcompression;
				$this->createTnImg = $row->createtnimg;
				$this->createLgImg = $row->createlgimg;
			}
			$rs->close();
		}
	}
	
	public function getProjects(){
		$projArr = array();
		if($this->collId){
			$sql = 'SELECT spprid, title '.
				'FROM specprocessorprojects '.
				'WHERE collid = '.$this->collId;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$projArr[$row->spprid] = $row->title;
			}
			$rs->close();
		}
		return $projArr;
	}

	public function getCollectionList(){
		global $isAdmin, $userRights;
		$returnArr = Array();
		if($isAdmin || array_key_exists("CollAdmin",$userRights)){
			$sql = 'SELECT DISTINCT c.CollID, c.CollectionName, c.icon '.
				'FROM omcollections c ';
			if(array_key_exists('CollAdmin',$userRights)){
				$sql .= 'WHERE c.collid IN('.implode(',',$userRights['CollAdmin']).') '; 
			}
			$sql .= 'ORDER BY c.CollectionName';
			//echo $sql;
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$collId = $row->CollID;
				$returnArr[$collId] = $row->CollectionName;
			}
			$result->close();
		}
		return $returnArr;
	}

	public function getLogPath(){
		return $this->logPath;
	}

	public function getLogFH(){
		return $this->logFH;
	}

	public function getLogErrFH(){
		return $this->logErrFH;
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

	public function setSpecKeyPattern($p){
		$this->specKeyPattern = $p;
	}

	public function getSpecKeyPattern(){
		return $this->specKeyPattern;
	}

	public function setSpecKeyRetrieval($p){
		$this->specKeyRetrieval = $p;
	}

	public function getSpecKeyRetrieval(){
		return $this->specKeyRetrieval;
	}

	public function setCoordX1($x){
		$this->coordX1 = $x;
	}

	public function getCoordX1(){
		return $this->coordX1;
	}
	
	public function setCoordX2($x){
		$this->coordX2 = $x;
	}

	public function getCoordX2(){
		return $this->coordX2;
	}

	public function setCoordY1($y){
		$this->coordY1 = $y;
	}

	public function getCoordY1(){
		return $this->coordY1;
	}

	public function setCoordY2($y){
		$this->coordY2 = $y;
	}

	public function getCoordY2(){
		return $this->coordY2;
	}

	public function setSourcePath($p){
		$this->sourcePath = $p;
	}

	public function getSourcePath(){
		return $this->sourcePath;
	}

	public function setTargetPath($p){
		$this->targetPath = $p;
	}

	public function getTargetPath(){
		return $this->targetPath;
	}

	public function setImgUrlBase($u){
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

	protected function cleanStr($str){
		$str = str_replace('"','',$str);
		return $str;
	}
}

class SpecProcessorImage extends SpecProcessorManager{

	function __construct($logPath){
 		parent::__construct($logPath);
	}

	public function batchLoadImages(){
		//Create log Files
		if(!file_exists($this->logPath.'specprocessor/')) mkdir($this->logPath.'specprocessor/');
		if(file_exists($this->logPath.'specprocessor/')){
			$logFile = $this->logPath."specprocessor/log_".date('Ymd').".log";
			$errFile = $this->logPath."specprocessor/logErr_".date('Ymd').".log";
			$this->logFH = fopen($logFile, 'a');
			$this->logErrFH = fopen($errFile, 'a');
			if($this->logFH) fwrite($this->logFH, "DateTime: ".date('Y-m-d h:i:s A')."\n");
			if($this->logErrFH) fwrite($this->logErrFH, "DateTime: ".date('Y-m-d h:i:s A')."\n");
		}
		echo "<li>Starting Image Processing</li>\n";
		$this->processFolder();
		echo "<li>Image upload complete</li>\n";
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
								echo "<li>Starting to load: ".$file."</li>\n";
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
									if($this->createLgImg && $width > ($webPixWidth*1.2)){
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
									if($this->createTnImg){
										$tnTargetFileName = substr($targetFileName,0,strlen($targetFileName)-4)."tn.jpg";
										if($this->createNewImage($sourcePath.$pathFrag.$file,$targetPath.$tnTargetFileName,$tnPixWidth,round($tnPixWidth*$height/$width),$width,$height)){
											$tnUrl = $tnTargetFileName;
										}
									}
									if($tnUrl) $tnUrl = $targetFolder.$tnUrl;
									if($lgUrl) $lgUrl = $targetFolder.$lgUrl;
									if($this->recordImageMetadata($specPk,$targetFolder.$targetFileName,$tnUrl,$lgUrl)){
										if(file_exists($sourcePath.$pathFrag.$file)) unlink($sourcePath.$pathFrag.$file);
										echo "<li style='margin-left:20px;'>Image processed successfully!</li>\n";
										if($this->logFH) fwrite($this->logFH, "\tImage processed successfully!\n");
									}
								}
							}
							else{
								if($this->logErrFH) fwrite($this->logErrFH, "\tERROR: File skipped, unable to locate specimen record \n");
								if($this->logFH) fwrite($this->logFH, "\tERROR: File skipped, unable to locate specimen record \n");
								echo "<li style='margin-left:10px;'>File skipped, unable to locate specimen record</li>\n";
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
		ini_set('memory_limit','512M');
		$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
		imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth,$newHeight,$oldWidth,$oldHeight);
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

class MySQLiConnectionFactory {
	static $SERVERS = array(
		array(
			'type' => 'readonly',
			'host' => 'localhost',
			'username' => 'root',
			'password' => 'bolivia15',
			'database' => 'symbiotaseinet'
		),
		array(
			'type' => 'write',
			'host' => 'localhost',
			'username' => 'root',
			'password' => 'bolivia15',
			'database' => 'symbiotaseinet'
		)
	);

	public static function getCon($type) {
        // Figure out which connections are open, automatically opening any connections
        // which are failed or not yet opened but can be (re)established.
        for ($i = 0, $n = count(MySQLiConnectionFactory::$SERVERS); $i < $n; $i++) {
            $server = MySQLiConnectionFactory::$SERVERS[$i];
            if($server['type'] == $type){
				$connection = new mysqli($server['host'], $server['username'], $server['password'], $server['database']);
                if(mysqli_connect_errno()){
        			//throw new Exception('Could not connect to any databases! Please try again later.');
                }
                return $connection;
            }
        }
    }
}
?>