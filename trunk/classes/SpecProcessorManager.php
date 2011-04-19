<?php
/*
 * Built 26 Jan 2011
 * By E.E. Gilbert
 */
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/SpecProcessorAbbyy.php');
include_once($serverRoot.'/classes/SpecProcessorImage.php');

class SpecProcessorManager {

	protected $conn;
	protected $collId;
	protected $spprId;

	protected $logPath;
	protected $logFH;
	protected $logErrFH;
	
	protected $projVars = Array();

	function __construct() {
		global $logPath;
		$this->conn = MySQLiConnectionFactory::getCon("write");
		$this->logPath = $logPath;
		if(substr($this->logPath,1) != '/') $this->logPath .= '/';

	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function setCollId($id) {
		if($id) {
			$this->collId = $id;
		}
	}

	protected function loadRecord($labelBlock){
		$status = '';
		if(preg_match($this->pkPattern, $labelBlock, $matches)){
			$pkStr = $matches[1];
			//Check to see if record with pk already exists
			$occId = 0;
			$sql = 'SELECT occid FROM omoccurrences WHERE dbpk = "'.$this->conn->real_escape_string($pkStr).'"';
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$occId = $row->occid;
			}
			$rs->close();
			//load new, empty occurrence record
			if(!$occId){
				if($this->conn->query('INSERT INTO omoccurrences(collid,dbpk,catalognumber,processingstatus) VALUES('.$this->collId.',"'.$pkStr.'","'.$pkStr.'","unparsed")')){
					$occId = $this->conn->insert_id;
				} 
			}
			if($occId){
				//load raw label record
				$sql = 'INSERT INTO specprocessorrawlabels(occid,rawstr) VALUES('.$occId.',"'.$this->cleanStr($labelBlock).'")';
				if(!$this->conn->query($sql)){
					$status = 'ERROR: unable to insert raw label record #'.$occId.'; SQL ERR: '.$this->conn->error;
					$status .= 'SQL: '.$sql;
				}
			}
			else{
				$status = 'ERROR: unable identify or create occurrence primary key (occid)';
			}
		}
		else{
			$status = 'ERROR: pkPattern not found, unable to extract primary key';
		}
		return $status;
	}
	
	public function editProject($editArr){
		$sql = 'UPDATE specprocessorprojects '.
			'SET title = "'.$editArr['title'].'", speckeypattern = "'.$editArr['speckeypattern'].'", speckeyretrieval = "'.$editArr['speckeyretrieval'].
			'", sourcepath = "'.$editArr['sourcepath'].'", targetpath = "'.$editArr['targetpath'].'", imgurl = "'.$editArr['imgurl'].
			'", webpixwidth = "'.$editArr['webpixwidth'].'", tnpixwidth = "'.$editArr['tnpixwidth'].'", lgpixwidth = "'.$editArr['lgpixwidth'].
			'", createtnimg = "'.$editArr['createtnimg'].'", createlgimg = "'.$editArr['createlgimg'].'" '.
			'WHERE spprid = '.$editArr['spprid'];
		$this->conn->query($sql);
	}

	public function addProject($addArr){
		$sql = 'INSERT INTO specprocessorprojects(title,speckeypattern,speckeyretrieval,sourcepath,targetpath,'.
			'imgurl,webpixwidth,tnpixwidth,lgpixwidth,createtnimg,createlgimg) '.
			'VALUES("'.$addArr['title'].'","'.$addArr['speckeypattern'].'","'.$addArr['speckeyretrieval'].'","'.
			$addArr['sourcepath'].'","'.$addArr['targetpath'].'","'.$addArr['imgurl'].'",'.$addArr['webpixwidth'].','.
			$addArr['tnpixwidth'].','.$addArr['lgpixwidth'].',"'.$addArr['createtnimg'].'","'.$addArr['createlgimg'].'")';
		$this->conn->query($sql);
	}
	
	public function deleteProject($spprId){
		$sql = 'DELETE FROM specprocessorprojects WHERE spprid = '.$spprId;
		$this->conn->query($sql);
	}

	protected function loadProjVariables(){
		$sql = 'SELECT spprid, title, speckeypattern, speckeyretrieval, coordx1, coordx2, coordy1, '. 
			'coordy2, sourcepath, targetpath, imgurl, webpixwidth, tnpixwidth, lgpixwidth, createtnimg, createlgimg '.
			'FROM specprocessorprojects '.
			'WHERE collid = '.$this->collId;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$spprId = $row->spprid;
			if(!$this->spprId) $this->spprId = $spprId; 
			$this->projVars[$spprId]['title'] = $row->title;
			$this->projVars[$spprId]['speckeypattern'] = $row->speckeypattern;
			$this->projVars[$spprId]['speckeyretrieval'] = $row->speckeyretrieval;
			$this->projVars[$spprId]['coordx1'] = $row->coordx1;
			$this->projVars[$spprId]['coordx2'] = $row->coordx2;
			$this->projVars[$spprId]['coordy1'] = $row->coordy1;
			$this->projVars[$spprId]['coordy2'] = $row->coordy2;
			$sourcePath = $row->sourcepath;
			if(substr($sourcePath,-1) != '/') $sourcePath .= '/'; 
			$this->projVars[$spprId]['sourcepath'] = $sourcePath;
			$targetPath = $row->targetpath;
			if(substr($targetPath,-1) != '/') $targetPath .= '/'; 
			$this->projVars[$spprId]['targetpath'] = $targetPath;
			$imgUrl = $row->imgurl;
			if(substr($imgUrl,-1) != '/') $imgUrl .= '/'; 
			$this->projVars[$spprId]['imgurl'] = $imgUrl;
			$this->projVars[$spprId]['tnpixwidth'] = $row->tnpixwidth;
			$this->projVars[$spprId]['webpixwidth'] = $row->webpixwidth;
			$this->projVars[$spprId]['lgpixwidth'] = $row->lgpixwidth;
			$this->projVars[$spprId]['createtnimg'] = $row->createtnimg;
			$this->projVars[$spprId]['createlgimg'] = $row->createlgimg;
		}
		$rs->close();
	}
	
	public function getProjects($spprId = 0){
		if($spprId){
			$this->spprId = $spprId;
		}
		if(!$this->projVars) $this->loadProjVariables();
		return $this->projVars;
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
?>
 