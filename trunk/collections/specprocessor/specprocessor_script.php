<?php
//Pattern matching tern used to locate primary key (PK) of specimen record
$specKeyPattern = '/UTC\d{8}/';

//filename = grab PK from file name; ocr = attempt to retrieve PK from image using OCR
$specKeyRetrieval = 'filename';

//Folder containing unprecessed images; read access needed
$sourcePath = 'C:/htdocs/symbiota/trunk/temp/images/toprocess/';
//$sourcePath = '/herbshare/inetpub/wwwroot/USUherbarium/toprocess/';

//Folder where images are to be placed; write access needed
$targetPath = 'C:/htdocs/symbiota/trunk/temp/images/';
//$targetPath = '/herbshare/inetpub/wwwroot/USUherbarium/';

//Url base needed to build image URL that will be save in DB
$imgUrlBase = '/seinet/temp/images/';
//$imgUrlBase = 'http://129.123.92.247/USUherbarium/specimen_images/';

$webPixWidth = 1200;
$tnPixWidth = 130;
$lgPixWidth = 3000;

//Value between 0 and 100
$jpgCompression = 60;	  

//Create thumbnail versions of image
$createTnImg = 1;		

//Create large version of image, given source image is large enough
$createLgImg = 1;		

//Path to where log files will be placed
$logPath = '';

//0 = write image metadata to file; 1 = write metadata to Symbiota database
$dbMetadata = 0;

//Variables below needed only if connecting directly with database
//Symbiota PK for collection; needed if run as a standalone script
$collId = 1;

//If record matching PK is not found, should a new blank record be created?
$createNewRec = 1;

//Weather to copyover images with matching names (includes path) or rename new image and keep both$copyOverImg = 1;		
$copyOverImg = 1;

//-------------------------------------------------------------------------------------------//
//End of variable assignment. Don't modify code below.
//Create processor and procede with processing images
date_default_timezone_set('America/Phoenix');
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
	protected $webPixWidth = 1200;
	protected $tnPixWidth = 130;
	protected $lgPixWidth = 2400;
	protected $jpgCompression= 60;
	protected $createWebImg = 1;
	protected $createTnImg = 1;
	protected $createLgImg = 1;
	
	protected $createNewRec = true;
	protected $copyOverImg = true;
	protected $dbMetadata = 1;			//Only used when run as a standalone script
	
	protected $logPath;
	protected $logFH;
	protected $logErrFH;
	protected $mdOutputFH;
	
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
	
	protected function recordImageMetadata($specID,$webUrl,$tnUrl,$oUrl){
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
		if($occId){
	        //echo "<li style='margin-left:20px;'>Preparing to load record into database</li>\n";
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

	private function writeMetadataToFile($specPk,$webUrl,$tnUrl,$oUrl){
		$status = true;
		if($this->mdOutputFH){
			$status = fwrite($this->mdOutputFH, $this->collId.',"'.$specPk.'","'.$webUrl.'","'.$tnUrl.'","'.$oUrl.'"'."\n");
		}
		return $status;
	}
	
	//Project Functions (create, edit, delete, etc)
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

	//Set and Get functions
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

	//Misc functions
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
		set_time_limit(800);
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
				if($this->logFH) fwrite($this->logFH, "Starting to load: ".$fileName."\n");
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
					if($this->logFH) fwrite($this->logFH, "\tWeb image copied to target folder\n");
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
						if($this->logFH) fwrite($this->logFH, "\tImage processed successfully!\n");
					}
				}
        	}
			else{
				if($this->logErrFH) fwrite($this->logErrFH, "\tERROR: File skipped, unable to locate specimen record \n");
				if($this->logFH) fwrite($this->logFH, "\tFile skipped, unable to locate specimen record \n");
				echo "<li style='margin-left:10px;'>File skipped, unable to locate specimen record</li>\n";
			}
		}
		else{
			if($this->logErrFH) fwrite($this->logErrFH, "\tERROR: File skipped, unable to extract specimen identifier \n");
			if($this->logFH) fwrite($this->logFH, "\tFile skipped, unable to extract specimen identifier \n");
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