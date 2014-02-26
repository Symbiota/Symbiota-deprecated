<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/SpecProcessorAbbyy.php');

class SpecProcessorManager {

	protected $conn;
	protected $collid = 0;
	protected $spprid = 0;
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
	protected $jpgQuality = 80;
	protected $webMaxFileSize = 400000;
	protected $lgMaxFileSize = 3000000;
	protected $createWebImg = 1;
	protected $createTnImg = 1;
	protected $createLgImg = 1;
	
	protected $createNewRec = true;
	protected $copyOverImg = true;
	protected $dbMetadata = 1;			//Only used when run as a standalone script
	protected $processUsingImageMagick = 0;
	
	protected $logPath;
	protected $logFH;
	protected $logErrFH;
	protected $mdOutputFH;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		if(!$this->logPath){
			if(array_key_exists('logPath',$GLOBALS)){
				$this->logPath = $GLOBALS['logPath'];
			}
			elseif(array_key_exists('tempDirRoot',$GLOBALS)){
				$this->logPath = $GLOBALS['tempDirRoot'];
			}
			else{
				$this->logPath = ini_get('upload_tmp_dir');
			}
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

	public function setCollId($id){
		$this->collid = $id;
		if($this->collid && is_numeric($this->collid) && !$this->collectionName){
			$sql = 'SELECT collid, collectionname, managementtype FROM omcollections WHERE (collid = '.$this->collid.')';
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

	protected function getPrimaryKey($str){
		$specPk = '';
		$pkPattern = $this->specKeyPattern;
		if(substr($pkPattern,0,1) != '/' && substr($pkPattern,-1) != '/') $pkPattern = '/'.$pkPattern.'/';
		if(preg_match($pkPattern,$str,$matchArr)){
			if(array_key_exists(1,$matchArr) && $matchArr[1]){
				$specPk = $matchArr[1];
			}
			else{
				$specPk = $matchArr[0];
			}
		}
		return $specPk;
	}
	
	protected function getOccId($specPk){
		$occId = 0;
		//Check to see if record with pk already exists
		$sql = 'SELECT occid FROM omoccurrences WHERE (catalognumber = "'.$specPk.'") AND (collid = '.$this->collid.')';
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$occId = $row->occid;
		}
		$rs->close();
		if(!$occId && $this->createNewRec){
			//Records does not exist, create a new one to which image will be linked
			$sql2 = 'INSERT INTO omoccurrences(collid,catalognumber'.(stripos($this->managementType,'Live')!==false?'':',dbpk').',processingstatus) '.
				'VALUES('.$this->collid.',"'.$specPk.'"'.(stripos($this->managementType,'Live')!==false?'':',"'.$specPk.'"').',"unprocessed")';
			if($this->conn->query($sql2)){
				$occId = $this->conn->insert_id;
				if($this->logFH) fwrite($this->logFH, "\tSpecimen record does not exist; new empty specimen record created and assigned an 'unprocessed' status (occid = ".$occId.") \n");
				echo "<li style='margin-left:10px;'>Specimen record does not exist; new empty specimen record created and assigned an 'unprocessed' status (occid = ".$occId.")</li>\n";
			} 
		}
		if(!$occId){
			if($this->logErrFH) fwrite($this->logErrFH, "\tERROR: File skipped, unable to locate specimen record ".$specPk." (".date('Y-m-d h:i:s A').") \n");
			if($this->logFH) fwrite($this->logFH, "\tFile skipped, unable to locate specimen record ".$specPk." (".date('Y-m-d h:i:s A').") \n");
			echo "<li style='margin-left:10px;'>File skipped, unable to locate specimen record ".$specPk."</li>\n";
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
				if($this->logErrFH) fwrite($this->logErrFH, "\tERROR: Unable to load image record into database: ".$this->conn->error."; SQL: ".$sql1.$sql2."\n");
			}
			if($imgId){
				if($this->logErrFH) fwrite($this->logErrFH, "\tWARNING: Existing image record replaced; occid: $occId \n");
				echo "<li style='margin-left:20px;'>Existing image database record replaced</li>\n";
			}
			else{
				echo "<li style='margin-left:20px;'>Image record loaded into database</li>\n";
				if($this->logFH) fwrite($this->logFH, "\tSUCCESS: Image record loaded into database\n");
			}
		}
		else{
			$status = false;
			if($this->logErrFH) fwrite($this->logErrFH, "ERROR: Missing occid (omoccurrences PK), unable to load record \n");
	        echo "<li style='margin-left:20px;'><b>ERROR:</b> Unable to load image into database. See error log for details</li>\n";
		}
		ob_flush();
		flush();
		return $status;
	}

	private function writeMetadataToFile($specPk,$webUrl,$tnUrl,$oUrl){
		$status = true;
		if($this->mdOutputFH){
			$status = fwrite($this->mdOutputFH, $this->collid.',"'.$specPk.'","'.$this->imgUrlBase.$webUrl.'","'.$this->imgUrlBase.$tnUrl.'","'.$this->imgUrlBase.$oUrl.'"'."\n");
		}
		return $status;
	}

	//OCR and NLP scripts
	//Not yet implimented and may not be. OCR is not a great method for obtaining primary identifier for specimen record.
	//Functions not needed for standalone scripts
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

	protected function loadRawFragment($imgId,$labelBlock){
		//load raw label record
		$status = true;
		$sql = 'INSERT INTO specprocessorrawlabels(imgid,rawstr) VALUES('.$imgId.',"'.$this->cleanInStr($labelBlock).'")';
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

	//Project Functions (create, edit, delete, etc)
	//Functions not needed for standalone scripts
	public function editProject($editArr){
		if($editArr['spprid']){
			$sql = 'UPDATE specprocessorprojects '.
				'SET title = "'.$this->cleanInStr($editArr['title']).'", '.
				'speckeypattern = "'.$this->cleanInStr($editArr['speckeypattern']).
				'", speckeyretrieval = "'.(array_key_exists('speckeyretrieval',$editArr)?$editArr['speckeyretrieval']:'filename').
				'", sourcepath = "'.$this->cleanInStr($editArr['sourcepath']).
				'", targetpath = "'.$this->cleanInStr($editArr['targetpath']).'", imgurl = "'.$editArr['imgurl'].
				'", webpixwidth = '.$editArr['webpixwidth'].', tnpixwidth = '.$editArr['tnpixwidth'].', lgpixwidth = '.$editArr['lgpixwidth'].
				', jpgcompression = '.$editArr['jpgquality'].
				', createtnimg = '.(array_key_exists('createtnimg',$editArr)?'1':'0').
				', createlgimg = '.(array_key_exists('createlgimg',$editArr)?'1':'0').' '.
				'WHERE (spprid = '.$editArr['spprid'].')';
			//echo 'SQL: '.$sql;
			$this->conn->query($sql);
		}
	}

	public function addProject($addArr){
		$sql = 'INSERT INTO specprocessorprojects(collid,title,speckeypattern,speckeyretrieval,sourcepath,targetpath,'.
			'imgurl,webpixwidth,tnpixwidth,lgpixwidth,jpgcompression,createtnimg,createlgimg) '.
			'VALUES('.$this->collid.',"'.$this->cleanInStr($addArr['title']).'","'.
			$this->cleanInStr($addArr['speckeypattern']).'","'.$addArr['speckeyretrieval'].'","'.
			$this->cleanInStr($addArr['sourcepath']).'","'.$this->cleanInStr($addArr['targetpath']).'","'.
			$addArr['imgurl'].'",'.$addArr['webpixwidth'].','.
			$addArr['tnpixwidth'].','.$addArr['lgpixwidth'].','.$addArr['jpgquality'].','.
			(array_key_exists('createtnimg',$addArr)?'1':'0').','.(array_key_exists('createlgimg',$addArr)?'1':'0').')';
		$this->conn->query($sql);
	}

	public function deleteProject($spprid){
		$sql = 'DELETE FROM specprocessorprojects WHERE (spprid = '.$spprid.')';
		$this->conn->query($sql);
	}

	public function setProjVariables(){
		if($this->spprid){
			$sql = 'SELECT p.collid, p.title, p.speckeypattern, p.speckeyretrieval, p.coordx1, p.coordx2, p.coordy1, p.coordy2, '. 
				'p.sourcepath, p.targetpath, p.imgurl, p.webpixwidth, p.tnpixwidth, p.lgpixwidth, p.jpgcompression, p.createtnimg, p.createlgimg '.
				'FROM specprocessorprojects p '.
				'WHERE (p.spprid = '.$this->spprid.')';
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				if(!$this->collid) $this->setCollId($row->collid); 
				$this->title = $row->title;
				$this->specKeyPattern = $row->speckeypattern;
				$this->specKeyRetrieval = $row->speckeyretrieval;
				$this->coordX1 = $row->coordx1;
				$this->coordX2 = $row->coordx2;
				$this->coordY1 = $row->coordy1;
				$this->coordY2 = $row->coordy2;
				$this->sourcePath = $row->sourcepath;
				if(substr($this->sourcePath,-1) != '/' && substr($this->sourcePath,-1) != '\\') $this->sourcePath .= '/'; 
				$this->targetPath = $row->targetpath;
				if(substr($this->targetPath,-1) != '/' && substr($this->targetPath,-1) != '\\') $this->targetPath .= '/'; 
				$this->imgUrlBase = $row->imgurl;
				if(substr($this->imgUrlBase,-1) != '/') $this->imgUrlBase .= '/'; 
				if($row->webpixwidth) $this->webPixWidth = $row->webpixwidth;
				if($row->tnpixwidth) $this->tnPixWidth = $row->tnpixwidth;
				if($row->lgpixwidth) $this->lgPixWidth = $row->lgpixwidth;
				if($row->jpgcompression) $this->jpgQuality = $row->jpgcompression;
				$this->createTnImg = $row->createtnimg;
				$this->createLgImg = $row->createlgimg;
			}
			$rs->close();
		}
	}
	
	public function getProjects(){
		$projArr = array();
		if($this->collid){
			$sql = 'SELECT spprid, title '.
				'FROM specprocessorprojects '.
				'WHERE (collid = '.$this->collid.')';
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$projArr[$row->spprid] = $row->title;
			}
			$rs->close();
		}
		return $projArr;
	}

	//Report functions
	public function getProcessingStats(){
		$retArr = array();
		$retArr['total'] = $this->getTotalCount();
		$retArr['ps'] = $this->getProcessingStatusCounts();
		$retArr['noimg'] = $this->getSpecNoImageCount();
		$retArr['unprocnoimg'] = $this->getUnprocSpecNoImage();
		$retArr['noskel'] = $this->getSpecNoSkel();
		return $retArr;
	}

	public function getTotalCount(){
		$totalCnt = 0;
		if($this->collid){
			//Get processing status counts
			$psArr = array();
			$sql = 'SELECT count(*) AS cnt '.
				'FROM omoccurrences '.
				'WHERE collid = '.$this->collid;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$totalCnt = $r->cnt;
			}
			$rs->free();
		}
		return $totalCnt;
	}

	public function getProcessingStatusCount($ps){
		$cnt = 0;
		if($this->collid){
			//Get processing status counts
			$psArr = array();
			$sql = 'SELECT count(*) AS cnt '.
				'FROM omoccurrences '.
				'WHERE collid = '.$this->collid.' AND processingstatus = "'.$this->cleanInStr($ps).'"';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}
	
	public function getProcessingStatusCounts(){
		$retArr = array();
		if($this->collid){
			//Get processing status counts
			$psArr = array();
			$sql = 'SELECT processingstatus, count(*) AS cnt '.
				'FROM omoccurrences '.
				'WHERE collid = '.$this->collid.' GROUP BY processingstatus';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$psArr[strtolower($r->processingstatus)] = $r->cnt;
			}
			$rs->free();
			//Load into $retArr in a specific order
			$statusArr = array('unprocessed','stage 1','stage 2','stage 3','pending duplicate','pending review','expert required','reviewed','closed','empty status');
			foreach($statusArr as $v){
				if(array_key_exists($v,$psArr)){
					$retArr[$v] = $psArr[$v];
					unset($psArr[$v]);
				}
			}
			//Grab untraditional processing statuses 
			foreach($psArr as $k => $cnt){
				$retArr[$k] = $cnt;
			}
		}
		return $retArr;
	}
	
	public function getSpecNoImageCount(){
		$cnt = 0;
		if($this->collid){
			//Count specimens without images
			$sql = 'SELECT count(o.occid) AS cnt '.
				'FROM omoccurrences o LEFT JOIN images i ON o.occid = i.occid '.
				'WHERE o.collid = '.$this->collid.' AND i.imgid IS NULL ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	public function getUnprocSpecNoImage(){
		$cnt = 0;
		if($this->collid){
			//Count unprocessed specimens without images
			$sql = 'SELECT count(o.occid) AS cnt '.
				'FROM omoccurrences o LEFT JOIN images i ON o.occid = i.occid '.
				'WHERE (o.collid = '.$this->collid.') AND (i.imgid IS NULL) AND (o.processingstatus = "unprocessed") ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	public function getSpecNoSkel(){
		$cnt = 0;
		if($this->collid){
			//Count specimens without skeletal data
			$sql = 'SELECT count(o.occid) AS cnt '.
				'FROM omoccurrences o '.
				'WHERE (o.collid = '.$this->collid.') AND (o.processingstatus = "unprocessed") '.
				'AND (o.sciname IS NULL) AND (o.stateprovince IS NULL)';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	public function getSpecNoOcr(){
		$cnt = 0;
		if($this->collid){
			//Count specimens with images but without OCR
			$sql = 'SELECT count(o.occid) AS cnt '.
				'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'INNER JOIN specprocessorrawlabels r ON i.imgid = r.imgid '.
				'WHERE o.collid = '.$this->collid.' AND r.imgid IS NULL ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	public function downloadReportData($target){
		$fileName = 'SymbSpecNoImages_'.time().'.csv';
		header ('Content-Type: text/csv; charset='.$GLOBALS['charset']);
		header ('Content-Disposition: attachment; filename="'.$fileName.'"');
		$headerArr = array('occid','catalogNumber','sciname','recordedBy','recordNumber','eventDate','country','stateProvince','county','processingstatus');
		$sql = 'SELECT o.'.implode(',',$headerArr).' ';
		if($target == 'dlnoimg'){
			$sql .= 'FROM omoccurrences o LEFT JOIN images i ON o.occid = i.occid '.
				'WHERE o.collid = '.$this->collid.' AND i.imgid IS NULL ';
		}
		elseif($target == 'unprocnoimg'){
			$sql .= 'FROM omoccurrences o LEFT JOIN images i ON o.occid = i.occid '.
				'WHERE (o.collid = '.$this->collid.') AND (i.imgid IS NULL) AND (o.processingstatus = "unprocessed") ';
		}
		elseif($target == 'noskel'){
			$sql .= 'FROM omoccurrences o '.
				'WHERE (o.collid = '.$this->collid.') AND (o.processingstatus = "unprocessed") '.
				'AND (o.sciname IS NULL) AND (o.stateprovince IS NULL)';
		}
		//echo $sql;
		$result = $this->conn->query($sql);
		//Write column names out to file
		if($result){
    		$outstream = fopen("php://output", "w");
			fputcsv($outstream, $headerArr);
			while($row = $result->fetch_assoc()){
				fputcsv($outstream, $row);
			}
			fclose($outstream);
		}
		else{
			echo "Recordset is empty.\n";
		}
        if($result) $result->close();
	}

	public function getUserStats(){
		$retArr = array();
		if($this->collid){
			//Processing scores by user
			$sql = 'SELECT recordenteredby, processingstatus, COUNT(occid) as cnt '.
				'FROM omoccurrences '.
				'WHERE recordenteredby IS NOT NULL AND collid = '.$this->collid.' '.
				'GROUP BY recordenteredby, processingstatus ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->recordenteredby][strtolower($r->processingstatus)] = $r->cnt;
			}
			$rs->free();
		}
		return $retArr;
	}
	
	public function getIssues(){
		$retArr = array();
		if($this->collid){
			$sql = 'SELECT count(*) AS cnt '.
				'FROM omoccurrences '.
				'WHERE processingstatus = "unprocessed" AND stateProvince IS NOT NULL AND locality IS NOT NULL AND collid = '.$this->collid;
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['loc'] = $r->cnt;
			}
			$rs->free();
		}
		return $retArr;
	}
	
	//Set and Get functions
	public function setSpprId($id) {
		if($id && is_numeric($id)){
			$this->spprid = $id;
		}
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

	public function setJpgQuality($jc){
		$this->jpgQuality = $jc;
	}

	public function getJpgQuality(){
		return $this->jpgQuality;
	}

	public function setWebMaxFileSize($s){
		$this->webMaxFileSize = $s;
	}

	public function getWebMaxFileSize(){
		return $this->webMaxFileSize;
	}

	public function setLgMaxFileSize($s){
		$this->lgMaxFileSize = $s;
	}
	
	public function getLgMaxFileSize(){
		return $this->lgMaxFileSize;
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

 	public function getUseImageMagick(){
 		return $this->processUsingImageMagick;
 	}

 	//Misc functions
	protected function cleanInStr($str){
		$newStr = trim($str);
		//$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?> 