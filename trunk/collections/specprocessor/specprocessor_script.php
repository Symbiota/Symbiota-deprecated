<?php

//Pattern matching tern used to locate primary key (PK) of specimen record
$specKeyPattern = '';	
//filename = grab PK from file name; ocr = attempt to retrieve PK from image using OCR
$specKeyRetrieval;		 
//Folder containing unprecessed images; read access needed
$sourcePath = '';		
//Folder where images are to be placed; write access needed
$targetPath = '';		
//Url base needed to build image URL that will be save in DB
$imgUrlBase = '';		
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
$dbMetadata = 1;		

//Variables below needed only if connecting directly with database
//If record matching PK is not found, should a new blank record be created?
$collId = 1;
$createNewRec = 1;

//-------------------------------------------------------------------------------------------//
//End of variable assignment. Don't modify code below.
//Create processor and procede with processing images  
$specManager = new SpecProcessorImage();

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


$specManager->setDbMetadata($dbMetadata);
if($dbMetadata){
	$specManager->setCollId($collId);
}

//Run process
$specManager->batchLoadImages($logPath);

	

class SpecProcessorManager {

	protected $conn;
	protected $collId = 0;
	protected $spprid = 0;

	protected $logPath;
	protected $logFH;
	protected $logErrFH;
	
	protected $projVars = Array();

	function __construct() {
		global $logPath, $tempDirRoot;
		$this->conn = MySQLiConnectionFactory::getCon("write");
		$this->logPath = $logPath;
		if(!$this->logPath) $this->logPath .= $tempDirRoot;
		if(substr($this->logPath,-1) != '/') $this->logPath .= '/'; 
		$this->logPath .= 'logs/';
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function setCollId($id) {
		if($id) $this->collId = $id;
	}

	public function setSpprId($id) {
		if($id) $this->spprid = $id;
	}
	
	protected function getPrimaryKey($str){
		$specPk = '';
		$pkPattern = $this->projVars['speckeypattern'];
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
			fwrite($this->logErrFH, "\tERROR: Unable to load Raw Text Fragment into database specprocessorrawlabels: ");
			fwrite($this->logErrFH, $this->conn->error." \n");
			fwrite($this->logErrFH, "\tSQL: $sql \n");
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
		if(!$occId && $_REQUEST['createnewrec']){
			//Records does not exist, create a new one to which image will be linked
			$sql2 = 'INSERT INTO omoccurrences(collid,catalognumber'.(stripos($this->projVars['managementtype'],'Live')!==false?'':',dbpk').',processingstatus) '.
				'VALUES('.$this->collId.',"'.$specPk.'"'.(stripos($this->projVars['managementtype'],'Live')!==false?'':',"'.$specPk.'"').',"unparsed")';
			if($this->conn->query($sql2)){
				$occId = $this->conn->insert_id;
			} 
		}
		return $occId;
	}
	
	protected function databaseImage($occId,$webUrl,$tnUrl,$oUrl){
		$status = true;
		if($occId){
	        //echo "<li style='margin-left:20px;'>Preparing to load record into database</li>";
			fwrite($this->logFH, "Preparing to load record into database\n");
			$imgUrl = $this->projVars['imgurl'];
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
				fwrite($this->logErrFH, "\tWARNING: Image record already exists with matching url and occid (".$occId."). Data loading skipped\n");
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
				$sql2 .= ',"specimen","'.$this->projVars['collectionname'].'")';
				if(!$this->conn->query($sql1.$sql2)){
					$status = false;
					fwrite($this->logErrFH, "\tERROR: Unable to load image record into database: ".$this->conn->error."; SQL: ".$sql1.$sql2."\n");
				}
			}
		}
		else{
			$status = false;
			fwrite($this->logErrFH, "ERROR: Missing occid (omoccurrences PK), unable to load record \n");
		}
		if($status){
			echo "<li style='margin-left:20px;'>Image record loaded into database</li>";
			fwrite($this->logFH, "\tSUCCESS: Image record loaded into database\n");
		}
		else{
			fwrite($this->logFH, "\tERROR: Unable to load image record into database. See error log for details. \n");
	        echo "<li style='margin-left:20px;'><b>ERROR:</b> Unable to load image record into database. See error log for details</li>";
		}
		return $status;
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

	protected function setProjVariables(){
		if($this->spprid){
			$sql = 'SELECT c.collid, c.collectionname, c.managementtype, p.title, p.speckeypattern, p.speckeyretrieval, p.coordx1, p.coordx2, p.coordy1, p.coordy2, '. 
				'p.sourcepath, p.targetpath, p.imgurl, p.webpixwidth, p.tnpixwidth, p.lgpixwidth, p.jpgcompression, p.createtnimg, p.createlgimg '.
				'FROM specprocessorprojects p INNER JOIN omcollections c ON p.collid = c.collid '.
				'WHERE p.spprid = '.$this->spprid; 
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				if(!$this->collId) $this->collId = $row->collid; 
				$this->projVars['title'] = $row->title;
				$this->projVars['collectionname'] = $row->collectionname;
				$this->projVars['managementtype'] = $row->managementtype;
				$this->projVars['speckeypattern'] = $row->speckeypattern;
				$this->projVars['speckeyretrieval'] = $row->speckeyretrieval;
				$this->projVars['coordx1'] = $row->coordx1;
				$this->projVars['coordx2'] = $row->coordx2;
				$this->projVars['coordy1'] = $row->coordy1;
				$this->projVars['coordy2'] = $row->coordy2;
				$sourcePath = $row->sourcepath;
				if(substr($sourcePath,-1) != '/') $sourcePath .= '/'; 
				$this->projVars['sourcepath'] = $sourcePath;
				$targetPath = $row->targetpath;
				if(substr($targetPath,-1) != '/') $targetPath .= '/'; 
				$this->projVars['targetpath'] = $targetPath;
				$imgUrl = $row->imgurl;
				if(substr($imgUrl,-1) != '/') $imgUrl .= '/'; 
				$this->projVars['imgurl'] = $imgUrl;
				$this->projVars['tnpixwidth'] = $row->tnpixwidth;
				$this->projVars['webpixwidth'] = $row->webpixwidth;
				$this->projVars['lgpixwidth'] = $row->lgpixwidth;
				$this->projVars['jpgcompression'] = $row->jpgcompression;
				$this->projVars['createtnimg'] = $row->createtnimg;
				$this->projVars['createlgimg'] = $row->createlgimg;
			}
			$rs->close();
		}
	}
	
	public function getProjVariables(){
		if(!$this->projVars) $this->setProjVariables();
		return $this->projVars;
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

	public function getErrLogPath(){
		return $this->logErrPath;
	}

	protected function cleanStr($str){
		$str = str_replace('"','',$str);
		return $str;
	}
	
	public function getSourcePath(){
		return $this->sourcePath;
	}
	
	public function getTargetBase(){
		return $this->targetBasePath;
	}
}

class SpecProcessorImage extends SpecProcessorManager{

	function __construct() {
 		parent::__construct();
	}

	public function batchLoadImages(){
		$this->setProjVariables();
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
		$sourcePath = $this->projVars['sourcepath'];
		$webPixWidth = $this->projVars['webpixwidth']?$this->projVars['webpixwidth']:1200;
		$tnPixWidth = $this->projVars['tnpixwidth']?$this->projVars['tnpixwidth']:130;
		$lgPixWidth = $this->projVars['lgpixwidth']?$this->projVars['lgpixwidth']:2400;
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
							$occId = 0;
							if($this->projVars['speckeyretrieval'] == 'filename'){
								//Grab Primary Key from filename
								$specPk = $this->getPrimaryKey($file);
	        					if($specPk){
									//Get occid (Symbiota occurrence record primary key)
									$occId = $this->getOccId($specPk);
	        					}
							}
	        				elseif($this->projVars['speckeyretrieval'] == 'ocr'){
	        					//OCR process image and grab primary key from OCR return
	        					$labelBlock = $this->ocrImage();
	        					$specPk = $this->getPrimaryKey($file);
	        					if($specPk){
		        					//Get occid (Symbiota occurrence record primary key)
									$occId = $this->getOccId($specPk);
	        						$this->loadRawFragment($occId,$labelBlock);
	        					}
	        				}
	        				//If Primary Key is found, continue with processing image
	        				if($occId){
	        					//Setup path and file name in prep for loading image
		        				$targetPath = $this->projVars['targetpath'];
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
									if($_REQUEST['copyoverimg']){
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
								fwrite($this->logFH, "Starting to load: ".$file."\n");
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
									fwrite($this->logFH, "\tWeb image copied to target folder\n");
									$tnUrl = "";$lgUrl = "";
									//Create Large Image
									if(array_key_exists('maplarge',$_REQUEST) && $width > ($webPixWidth*1.2)){
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
									if(array_key_exists('maptn',$_REQUEST)){
										$tnTargetFileName = substr($targetFileName,0,strlen($targetFileName)-4)."tn.jpg";
										if($this->createNewImage($sourcePath.$pathFrag.$file,$targetPath.$tnTargetFileName,$tnPixWidth,round($tnPixWidth*$height/$width),$width,$height)){
											$tnUrl = $tnTargetFileName;
										}
									}
									if($tnUrl) $tnUrl = $targetFolder.$tnUrl;
									if($lgUrl) $lgUrl = $targetFolder.$lgUrl;
									if($this->databaseImage($occId,$targetFolder.$targetFileName,$tnUrl,$lgUrl)){
										if(file_exists($sourcePath.$pathFrag.$file)) unlink($sourcePath.$pathFrag.$file);
										echo "<li style='margin-left:20px;'>Image processed successfully!</li>";
										fwrite($this->logFH, "\tImage processed successfully!\n");
									}
								}
							}
							else{
								fwrite($this->logErrFH, "\tERROR: File skipped, unable to locate specimen record \n");
								fwrite($this->logFH, "\tERROR: File skipped, unable to locate specimen record \n");
								echo "<li style='margin-left:10px;'>File skipped, unable to locate specimen record</li>";
							}
        				}
						else{
							//echo "<li style='margin-left:10px;'><b>Error:</b> File skipped, not a supported image file: ".$file."</li>";
							fwrite($this->logErrFH, "\tERROR: File skipped, not a supported image file: ".$file." \n");
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
			fwrite($this->logErrFH, "\tError: Unable to resize and write file: ".$targetPath."\n");
        	echo "<li style='margin-left:20px;'><b>Error:</b> Unable to resize and write file: $targetPath</li>";
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
			'host' => 'sod84.asu.edu',
			'username' => '',
			'password' => '',
			'database' => 'symbiotaseinet'
		),
		array(
			'type' => 'write',
			'host' => 'sod84.asu.edu',
			'username' => '',
			'password' => '',
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