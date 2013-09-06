<?php
include_once($serverRoot.'/config/dbconnection.php');

class SpecUpload{

	protected $conn;
	protected $collId;
	protected $uspid;
	protected $collMetadataArr = Array();
	
	protected $title = "";
	protected $platform;
	protected $server;
	protected $port;
	protected $username;
	protected $password;
	protected $digirCode;
	protected $digirPath;
	protected $digirPKField;
	protected $schemaName;
	protected $queryStr;
	protected $storedProcedure;
	protected $lastUploadDate;
	protected $uploadType;

	protected $errorArr = Array();

	protected $DIRECTUPLOAD = 1,$DIGIRUPLOAD = 2, $FILEUPLOAD = 3, $STOREDPROCEDURE = 4, $SCRIPTUPLOAD = 5, $DWCAUPLOAD = 6;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	function __destruct(){
 		if($this->conn) $this->conn->close();
	}
	
	public function setCollId($id){
		if($id && is_numeric($id)){
			$this->collId = $id;
			$this->setCollInfo();
		}
	}
	
	public function setUspid($id){
		if($id && is_numeric($id)){
			$this->uspid = $id;
		}
	}

	public function getUploadList(){
		$returnArr = Array();
		if($this->collId){
			$sql = 'SELECT usp.uspid, usp.uploadtype, usp.title '.
				'FROM uploadspecparameters usp '.
				'WHERE (usp.collid = '.$this->collId.') '.
				"ORDER BY usp.uploadtype,usp.title";
			//echo $sql;
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$uploadType = $row->uploadtype;
				$uploadStr = "";
				if($uploadType == $this->DIRECTUPLOAD){
					$uploadStr = "Direct Upload";
				}
				elseif($uploadType == $this->DIGIRUPLOAD){
					$uploadStr = "DiGIR Provider Upload";
				}
				elseif($uploadType == $this->FILEUPLOAD){
					$uploadStr = "File Upload";
				}
				elseif($uploadType == $this->STOREDPROCEDURE){
					$uploadStr = "Stored Procedure";
				}
				elseif($uploadType == $this->DWCAUPLOAD){
					$uploadStr = "Darwin Core Archive Upload";
				}
				$returnArr[$row->uspid]["title"] = $row->title." (".$uploadStr.")";
				$returnArr[$row->uspid]["uploadtype"] = $row->uploadtype;
			}
			$result->close();
		}
		return $returnArr;
	}

	private function setCollInfo(){
		if($this->collId){
			$sql = 'SELECT DISTINCT c.collid, c.collectionname, c.institutioncode, c.collectioncode, c.icon, c.managementtype, cs.uploaddate '.
				'FROM omcollections c LEFT JOIN omcollectionstats cs ON c.collid = cs.collid '.
				'WHERE (c.collid = '.$this->collId.')';
			//echo $sql;
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$this->collMetadataArr["collid"] = $row->collid;
				$this->collMetadataArr["name"] = $row->collectionname;
				$this->collMetadataArr["institutioncode"] = $row->institutioncode;
				$this->collMetadataArr["collectioncode"] = $row->collectioncode;
				$dateStr = ($row->uploaddate?date("d F Y g:i:s", strtotime($row->uploaddate)):"");
				$this->collMetadataArr["uploaddate"] = $dateStr;
				$this->collMetadataArr["managementtype"] = $row->managementtype;
			}
			$result->close();
		}
	}
	
	public function getCollInfo($fieldStr = ""){
		if(!$this->collMetadataArr) $this->setCollInfo();
		if($fieldStr){
			if(array_key_exists($fieldStr,$this->collMetadataArr)){
				return $this->collMetadataArr[$fieldStr];
			}
			return '';			
		}
		return $this->collMetadataArr;
	}

    public function readUploadParameters(){
    	if($this->uspid){
			$sql = 'SELECT usp.title, usp.Platform, usp.server, usp.port, usp.Username, usp.Password, usp.SchemaName, '.
	    		'usp.digircode, usp.digirpath, usp.digirpkfield, usp.querystr, usp.cleanupsp, cs.uploaddate, usp.uploadtype '.
				'FROM uploadspecparameters usp LEFT JOIN omcollectionstats cs ON usp.collid = cs.collid '.
	    		'WHERE (usp.uspid = '.$this->uspid.')';
			//echo $sql;
			$result = $this->conn->query($sql);
	    	if($row = $result->fetch_object()){
	    		$this->title = $row->title;
	    		$this->platform = $row->Platform;
	    		$this->server = $row->server;
	    		$this->port = $row->port;
	    		$this->username = $row->Username;
	    		$this->password = $row->Password;
	    		$this->schemaName = $row->SchemaName;
	    		$this->digirCode = $row->digircode;
	    		if(!$this->digirPath) $this->digirPath = $row->digirpath;
	    		$this->digirPKField = strtolower($row->digirpkfield);
	    		$this->queryStr = $row->querystr;
	    		$this->storedProcedure = $row->cleanupsp;
	    		$this->lastUploadDate = $row->uploaddate;
	    		$this->uploadType = $row->uploadtype;
	    		if(!$this->lastUploadDate) $this->lastUploadDate = date('Y-m-d H:i:s');
	    	}
	    	$result->close();
    	}
    }
    
    public function editUploadProfile(){
		$sql = 'UPDATE uploadspecparameters SET title = "'.$this->cleanInStr($_REQUEST['title']).'"'.
			', platform = '.($_REQUEST['platform']?'"'.$_REQUEST['platform'].'"':'NULL').
			', server = '.($_REQUEST['server']?'"'.$_REQUEST['server'].'"':'NULL').
			', port = '.($_REQUEST['port']?$_REQUEST['port']:'NULL').
			', username = '.($_REQUEST['username']?'"'.$_REQUEST['username'].'"':'NULL').
			', password = '.($_REQUEST['password']?'"'.$_REQUEST['password'].'"':'NULL').
			', schemaname = '.($_REQUEST['schemaname']?'"'.$_REQUEST['schemaname'].'"':'NULL').
			', digircode = '.($_REQUEST['code']?'"'.$_REQUEST['code'].'"':'NULL').
			', digirpath = '.($_REQUEST['path']?'"'.$_REQUEST['path'].'"':'NULL').
			', digirpkfield = '.($_REQUEST['pkfield']?'"'.$_REQUEST['pkfield'].'"':'NULL').
			', querystr = '.($_REQUEST['querystr']?'"'.$this->cleanInStr($_REQUEST['querystr']).'"':'NULL').
			', cleanupsp = '.($_REQUEST['cleanupsp']?'"'.$_REQUEST['cleanupsp'].'"':'NULL').' '.
			'WHERE (uspid = '.$this->uspid.')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			return "<div>Error Editing Upload Parameters: ".$this->conn->error."</div><div>$sql</div>";
		}
		return 'SUCCESS: Upload profile edited successfully';
	}

    public function addUploadProfile(){
		$sql = 'INSERT INTO uploadspecparameters(collid, uploadtype, title, platform, server, port, digircode, digirpath, '.
			'digirpkfield, username, password, schemaname, cleanupsp, querystr) VALUES ('.
			$this->collId.','.$_REQUEST['uploadtype'].',"'.$this->cleanInStr($_REQUEST['title']).'","'.$_REQUEST['platform'].'","'.
			$_REQUEST['server'].'",'.($_REQUEST['port']?$_REQUEST['port']:'NULL').',"'.$_REQUEST['code'].
			'","'.$_REQUEST['path'].'","'.$_REQUEST['pkfield'].'","'.$_REQUEST['username'].
			'","'.$_REQUEST['password'].'","'.$_REQUEST['schemaname'].'","'.$_REQUEST['cleanupsp'].'","'.
			$this->cleanInStr($_REQUEST['querystr']).'")';
		//echo $sql;
		if(!$this->conn->query($sql)){
			return '<div>Error Adding Upload Parameters: '.$this->conn->error.'</div><div style="margin-left:10px;">SQL: '.$sql.'</div>';
		}
		return 'SUCCESS: New upload profile added';
	}

    public function deleteUploadProfile($uspid){
		$sql = 'DELETE FROM uploadspecparameters WHERE (uspid = '.$uspid.')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			return "<div>Error Adding Upload Parameters: ".$this->conn->error."</div><div>$sql</div>";
		}
		return "SUCCESS: Upload Profile Deleted";
	}

	public function getTitle(){
		return $this->title;
	}

	public function getPlatform(){
		return $this->platform;
	}

	public function getServer(){
		return $this->server;
	}
	
	public function getPort(){
		return $this->port;
	}
	
	public function getUsername(){
		return $this->username;
	}
	
	public function getPassword(){
		return $this->password;
	}
	
	public function getDigirCode(){
		return $this->digirCode;
	}
	
	public function getDigirPath(){
		return $this->digirPath;
	}

	public function getDigirPKField(){
		return $this->digirPKField;
	}

	public function getSchemaName(){
		return $this->schemaName;
	}

	public function getQueryStr(){
		return $this->queryStr;
	}

	public function getStoredProcedure(){
		return $this->storedProcedure;
	}
	
	public function getUploadType(){
		return $this->uploadType;
	}

	public function getErrorArr(){
		return $this->errorArr;
	}

	protected function cleanInStr($inStr){
		$retStr = trim($inStr);
		$retStr = str_replace(chr(10),' ',$retStr);
		$retStr = str_replace(chr(11),' ',$retStr);
		$retStr = str_replace(chr(13),' ',$retStr);
		$retStr = str_replace(chr(20),' ',$retStr);
		$retStr = str_replace(chr(30),' ',$retStr);
		$retStr = preg_replace('/\s\s+/', ' ',$retStr);
		$retStr = $this->conn->real_escape_string($retStr);
		return $retStr;
	}
}

?>