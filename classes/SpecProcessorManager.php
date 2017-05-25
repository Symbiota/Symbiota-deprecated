<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class SpecProcessorManager {

	protected $conn;
	protected $collid = 0;
	protected $title;
	protected $collectionName;
	protected $institutionCode;
	protected $collectionCode;
	protected $projectType;
	protected $managementType;
	protected $specKeyPattern;
	protected $patternReplace;
	protected $replaceStr;
	protected $coordX1;
	protected $coordX2;
	protected $coordY1;
	protected $coordY2;
	protected $sourcePath;
	protected $targetPath;
	protected $imgUrlBase;
	protected $webPixWidth = '';
	protected $tnPixWidth = '';
	protected $lgPixWidth = '';
	protected $jpgQuality = 80;
	protected $webMaxFileSize = 300000;
	protected $lgMaxFileSize = 3000000;
	protected $webImg = 1;
	protected $createTnImg = 1;
	protected $createLgImg = 1;
	protected $lastRunDate = '';
	
	protected $dbMetadata = 1;			//Only used when run as a standalone script
	protected $processUsingImageMagick = 0;
	
	protected $logPath;
	protected $logFH;
	protected $logErrFH;
	protected $mdOutputFH;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
		$this->logPath = $GLOBALS['SERVER_ROOT'].(substr($GLOBALS['SERVER_ROOT'],-1) == '/'?'':'/').'content/logs/';
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function setCollId($id){
		$this->collid = $id;
		if($this->collid && is_numeric($this->collid) && !$this->collectionName){
			$sql = 'SELECT collid, collectionname, institutioncode, collectioncode, managementtype '.
				'FROM omcollections WHERE (collid = '.$this->collid.')';
			if($rs = $this->conn->query($sql)){
				if($row = $rs->fetch_object()){
					$this->collectionName = $row->collectionname;
					$this->institutionCode = $row->institutioncode;
					$this->collectionCode = $row->collectioncode;
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

	//Project Functions (create, edit, delete, etc)
	//Functions not needed for standalone scripts
	public function editProject($editArr){
		if(is_numeric($editArr['spprid'])){
			$sqlFrag = '';
			$targetFields = array('title','projecttype','speckeypattern','patternreplace','replacestr','sourcepath','targetpath','imgurl',
				'webpixwidth','tnpixwidth','lgpixwidth','jpgcompression','createtnimg','createlgimg','source');
			if(!isset($editArr['createtnimg'])) $editArr['createtnimg'] = 0;
			if(!isset($editArr['createlgimg'])) $editArr['createlgimg'] = 0;
			foreach($editArr as $k => $v){
				if(in_array($k,$targetFields)){
					if(is_numeric($v)){
						$sqlFrag .= ','.$k.' = '.$this->cleanInStr($v);
					}
					elseif($k == 'replacestr'){
						$sqlFrag .= ','.$k.' = "'.$this->conn->real_escape_string($v).'"';
					}
					elseif($v){
						$sqlFrag .= ','.$k.' = "'.$this->cleanInStr($v).'"';
					}
					else{
						$sqlFrag .= ','.$k.' = NULL';
					}
				}
			}
			$sql = 'UPDATE specprocessorprojects SET '.trim($sqlFrag,' ,').' WHERE (spprid = '.$editArr['spprid'].')';
			//echo '<br/>SQL: '.$sql; exit;
			if(!$this->conn->query($sql)){
				echo 'ERROR saving project: '.$this->conn->error;
				//echo '<br/>SQL: '.$sql;
			}
		}
	}

	public function addProject($addArr){
		$this->conn->query('DELETE FROM specprocessorprojects WHERE (title = "OCR Harvest") AND (collid = '.$this->collid.')');
		$sql = '';
		if(isset($addArr['projecttype'])){
			$sourcePath = $addArr['sourcepath'];
			if($sourcePath == '-- Use Default Path --') $sourcePath = '';
			if($addArr['projecttype'] == 'idigbio'){
				$sql = 'INSERT INTO specprocessorprojects(collid,title,speckeypattern,patternreplace,replacestr,projecttype,sourcepath) '.
					'VALUES('.$this->collid.',"iDigBio CSV upload","'.$this->cleanInStr($addArr['speckeypattern']).'",'.
					($addArr['patternreplace']?'"'.$this->cleanInStr($addArr['patternreplace']).'"':'NULL').','.
					($addArr['replacestr']?'"'.$this->conn->real_escape_string($addArr['replacestr']).'"':'NULL').','.
					($addArr['projecttype']?'"'.$this->cleanInStr($addArr['projecttype']).'"':'NULL').','.
					($sourcePath?'"'.$this->cleanInStr($sourcePath).'"':'NULL').')';
			}
			elseif($addArr['projecttype'] == 'iplant'){
				$sql = 'INSERT INTO specprocessorprojects(collid,title,speckeypattern,patternreplace,replacestr,projecttype,sourcepath) '.
					'VALUES('.$this->collid.',"IPlant Image Processing","'.$this->cleanInStr($addArr['speckeypattern']).'",'.
					($addArr['patternreplace']?'"'.$this->cleanInStr($addArr['patternreplace']).'"':'NULL').','.
					($addArr['replacestr']?'"'.$this->conn->real_escape_string($addArr['replacestr']).'"':'NULL').','.
					($addArr['projecttype']?'"'.$this->cleanInStr($addArr['projecttype']).'"':'NULL').','.
					($sourcePath?'"'.$this->cleanInStr($sourcePath).'"':'NULL').')';
			}
			elseif($addArr['projecttype'] == 'local'){
				$sql = 'INSERT INTO specprocessorprojects(collid,title,speckeypattern,patternreplace,replacestr,projecttype,sourcepath,targetpath,'.
					'imgurl,webpixwidth,tnpixwidth,lgpixwidth,jpgcompression,createtnimg,createlgimg) '.
					'VALUES('.$this->collid.',"'.$this->cleanInStr($addArr['title']).'","'.
					$this->cleanInStr($addArr['speckeypattern']).'",'.
					($addArr['patternreplace']?'"'.$this->cleanInStr($addArr['patternreplace']).'"':'NULL').','.
					($addArr['replacestr']?'"'.$this->conn->real_escape_string($addArr['replacestr']).'"':'NULL').','.
					($addArr['projecttype']?'"'.$this->cleanInStr($addArr['projecttype']).'"':'NULL').','.
					($sourcePath?'"'.$this->cleanInStr($sourcePath).'"':'NULL').','.
					(isset($addArr['targetpath'])&&$addArr['targetpath']?'"'.$this->cleanInStr($addArr['targetpath']).'"':'NULL').','.
					(isset($addArr['imgurl'])&&$addArr['imgurl']?'"'.$addArr['imgurl'].'"':'NULL').','.
					(isset($addArr['webpixwidth'])&&$addArr['webpixwidth']?$addArr['webpixwidth']:'NULL').','.
					(isset($addArr['tnpixwidth'])&&$addArr['tnpixwidth']?$addArr['tnpixwidth']:'NULL').','.
					(isset($addArr['lgpixwidth'])&&$addArr['lgpixwidth']?$addArr['lgpixwidth']:'NULL').','.
					(isset($addArr['jpgquality'])&&$addArr['jpgquality']?$addArr['jpgquality']:'NULL').','.
					(isset($addArr['createTnImg'])&&$addArr['createTnImg']?$addArr['createTnImg']:'NULL').','.
					(isset($addArr['createLgImg'])&&$addArr['createLgImg']?$addArr['createLgImg']:'NULL').')';
			}
		}
		elseif($addArr['title'] == 'OCR Harvest' && $addArr['newprofile']){
			$sql = 'INSERT INTO specprocessorprojects(collid,title,speckeypattern) '.
				'VALUES('.$this->collid.',"'.$this->cleanInStr($addArr['title']).'","'.
				$this->cleanInStr($addArr['speckeypattern']).'")';
		}
		if($sql){
			if(!$this->conn->query($sql)){
				echo 'ERROR saving project: '.$this->conn->error;
				//echo '<br/>SQL: '.$sql;
			}
		}
	}

	public function deleteProject($spprid){
		$sql = 'DELETE FROM specprocessorprojects WHERE (spprid = '.$spprid.')';
		$this->conn->query($sql);
	}

	public function setProjVariables($crit){
		$sqlWhere = '';
		if(is_numeric($crit)){
			$sqlWhere .= 'WHERE (spprid = '.$crit.')';
		}
		elseif($crit == 'OCR Harvest' && $this->collid){
			$sqlWhere .= 'WHERE (collid = '.$this->collid.') ';
		}
		if($sqlWhere){
			$sql = 'SELECT collid, title, speckeypattern, patternreplace, replacestr,coordx1, coordx2, coordy1, coordy2, sourcepath, targetpath, '.
				'imgurl, webpixwidth, tnpixwidth, lgpixwidth, jpgcompression, createtnimg, createlgimg, source '.
				'FROM specprocessorprojects '.$sqlWhere;
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				if(!$this->collid) $this->setCollId($row->collid); 
				$this->title = $row->title;
				$this->specKeyPattern = $row->speckeypattern;
				$this->patternReplace = $row->patternreplace;
				$this->replaceStr = $row->replacestr;
				$this->coordX1 = $row->coordx1;
				$this->coordX2 = $row->coordx2;
				$this->coordY1 = $row->coordy1;
				$this->coordY2 = $row->coordy2;
				$this->sourcePath = $row->sourcepath;
				$this->targetPath = $row->targetpath;
				$this->imgUrlBase = $row->imgurl;
				if($row->webpixwidth) $this->webPixWidth = $row->webpixwidth;
				if($row->tnpixwidth) $this->tnPixWidth = $row->tnpixwidth;
				if($row->lgpixwidth) $this->lgPixWidth = $row->lgpixwidth;
				if($row->jpgcompression) $this->jpgQuality = $row->jpgcompression;
				$this->createTnImg = $row->createtnimg;
				$this->createLgImg = $row->createlgimg;
				//Temporary code for setting projectType until proectType field is added to specprocessorprojects table
				$this->lastRunDate = $row->source;
				//$this->lastRunDate = $row->lastrundate;
				if($this->title == 'iDigBio CSV upload'){
					$this->projectType = 'idigbio';
				}
				elseif($this->title == 'IPlant Image Processing'){
					$this->projectType = 'iplant';
				}
				elseif($this->title == 'OCR Harvest'){
					break;
				}
				else{
					$this->projectType = 'local';
				}
			}
			$rs->free();
			
			//if(!$this->targetPath) $this->targetPath = $GLOBALS['imageRootPath'];
			//if(!$this->imgUrlBase) $this->imgUrlBase = $GLOBALS['imageRootUrl'];
			if($this->sourcePath && substr($this->sourcePath,-1) != '/' && substr($this->sourcePath,-1) != '\\') $this->sourcePath .= '/'; 
			if($this->targetPath && substr($this->targetPath,-1) != '/' && substr($this->targetPath,-1) != '\\') $this->targetPath .= '/'; 
			if($this->imgUrlBase && substr($this->imgUrlBase,-1) != '/') $this->imgUrlBase .= '/';
		} 
	}
	
	public function getProjects(){
		$projArr = array();
		if($this->collid){
			$sql = 'SELECT spprid, title '.
				'FROM specprocessorprojects '.
				'WHERE (collid = '.$this->collid.') AND title != "OCR Harvest"';
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$projArr[$row->spprid] = $row->title;
			}
			$rs->free();
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
			$statusArr = array('unprocessed','stage 1','stage 2','stage 3','pending duplicate','pending review-nfn','pending review','expert required','reviewed','closed','empty status');
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
		//Count specimens without images
		$cnt = 0;
		if($this->collid){
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
		//Count unprocessed specimens without images (e.g. generated from skeletal file)
		$cnt = 0;
		if($this->collid){
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
		//Count unprocessed specimens without skeletal data
		$cnt = 0;
		if($this->collid){
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

	//OCR related counts
	public function getSpecWithImage($procStatus = ''){
		//Count of specimens with images but no OCR
		$cnt = 0;
		if($this->collid){
			$sql = 'SELECT COUNT(DISTINCT o.occid) AS cnt '.
				'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'WHERE (o.collid = '.$this->collid.') ';
			if($procStatus){
				if($procStatus == 'null'){
					$sql .= 'AND processingstatus IS NULL';
				}
				else{
					$sql .= 'AND processingstatus = "'.$this->cleanInStr($procStatus).'"';
				}
			}
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

	public function getSpecNoOcr($procStatus = ''){
		//Count of specimens with images but no OCR
		$cnt = 0;
		if($this->collid){
			$sql = 'SELECT COUNT(DISTINCT o.occid) AS cnt '.
				'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'LEFT JOIN specprocessorrawlabels r ON i.imgid = r.imgid '.
				'WHERE o.collid = '.$this->collid.' AND r.imgid IS NULL ';
			if($procStatus){
				if($procStatus == 'null'){
					$sql .= 'AND processingstatus IS NULL';
				}
				else{
					$sql .= 'AND processingstatus = "'.$this->cleanInStr($procStatus).'"';
				}
			}
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$cnt = $r->cnt;
			}
			$rs->free();
		}
		return $cnt;
	}

 	public function getProcessingStatusList(){
		$retArr = array();
		if($this->collid){
			$sql = 'SELECT DISTINCT o.processingstatus '.
				'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'WHERE o.collid = '.$this->collid;
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if($r->processingstatus) $retArr[] = $r->processingstatus;
			}
			$rs->free();
			sort($retArr);
		}
		return $retArr;
 	}

 	//Misc stats
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

	public function getLogListing(){
		$retArr = array();
		if($this->collid){
			$logPathFrag = ($this->projectType == 'local'?'imgProccessing':$this->projectType).'/';
			if($fh = opendir($this->logPath.$logPathFrag)){
				while($fileName = readdir($fh)){
					if(strpos($fileName,$this->collid.'_') === 0){
						$retArr[] = $fileName;
					}
				}
			}
		}
		rsort($retArr);
		return $retArr;
	}

	//Set and Get functions
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

	public function getInstitutionCode(){
		return $this->institutionCode;
	}

	public function getCollectionCode(){
		return $this->collectionCode;
	}

	public function setProjectType($t){
		$this->projectType = $t;
	}

	public function getProjectType(){
		return $this->projectType;
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

	public function setPatternReplace($str){
		$this->patternReplace = $str;
	}

	public function getPatternReplace(){
		return $this->patternReplace;
	}

	public function setReplaceStr($str){
		$this->replaceStr = $str;
	}

	public function getReplaceStr(){
		return $this->replaceStr;
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

	public function getSourcePathDefault(){
		$sourcePath = $this->sourcePath;
		if(!$sourcePath && $this->projectType == 'iplant' && $GLOBALS['IPLANT_IMAGE_IMPORT_PATH']){
			$sourcePath = $GLOBALS['IPLANT_IMAGE_IMPORT_PATH'];
			if(strpos($sourcePath, '--INSTITUTION_CODE--')) $sourcePath = str_replace('--INSTITUTION_CODE--', $this->institutionCode, $sourcePath);
			if(strpos($sourcePath, '--COLLECTION_CODE--')) $sourcePath = str_replace('--COLLECTION_CODE--', $this->collectionCode, $sourcePath);
		}
		return $sourcePath;
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
	
	public function setWebImg($c){
		$this->webImg = $c;
	}

	public function getWebImg(){
		return $this->webImg;
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
	
	public function setLastRunDate($date){
		$this->lastRunDate = $date;
	}
	
	public function getLastRunDate(){
		return $this->lastRunDate;
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
 	
 	public function getConn(){
 		return $this->conn;
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