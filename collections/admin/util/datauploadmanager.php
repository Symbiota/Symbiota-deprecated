<?php
include_once($serverRoot."/util/dbconnection.php");
include_once($serverRoot."/collections/admin/util/directupload.php");
include_once($serverRoot."/collections/admin/util/digirupload.php");
include_once($serverRoot."/collections/admin/util/fileupload.php");

class DataUploadManager {

	protected $conn;
	protected $collId = 0;
	protected $uploadType;
	protected $finalTransfer;
	protected $doFullReplace = false;
	
	protected $title = "";
	protected $platform = 0;
	protected $server;
	protected $port = 0;
	protected $username;
	protected $password;
	protected $driver;
	protected $digirCode;
	protected $digirPath;
	protected $digirPKField;
	protected $schemaName;
	protected $queryStr;
	protected $cleanupSP;
	protected $lastUploadDate;
	protected $dlmIsValid = false;	//DateLastModified field is valid. Even if it is in definition, need to make sure it's an updated field

	protected $transferCount = 0;
	protected $fieldMap = Array();
	protected $symbFields = Array();
	
	private $DIRECTUPLOAD = 1,$DIGIRUPLOAD = 2, $FILEUPLOAD = 3, $STOREDPROCEDURE = 4;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	function __destruct(){
 		if($this->conn) $this->conn->close();
	}
	
	public function setCollId($id){
		$this->collId = $id;
	}
	
	public function setUploadType($t){
		$this->uploadType = $t;
	}
	
	public function setFieldMap($fm){
		$this->fieldMap = $fm;
	}
	
	public function setFinalTransfer($ft){
		$this->finalTransfer = $ft;
	}
	
	public function setDoFullReplace($dfr){
		$this->doFullReplace = $dfr;
	}

	public function getCollectionList($uRights){
		global $isAdmin;
		$returnArr = Array();
		$collStr = "";
		foreach($uRights as $right){
			if(substr($right,0,5) == "coll-"){
				$collStr .= ",".substr($right,5);
			}
		}
		if($collStr) $collStr = substr($collStr,1);
		if($collStr || $isAdmin){
			$sql = "SELECT DISTINCT c.CollID, c.CollectionName, c.icon ".
				"FROM omcollections c ";
			if($collStr) $sql .= "WHERE c.collid IN($collStr) "; 
			$sql .= "ORDER BY c.CollectionName";
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

	public function getCollInfo(){
		$returnArr = Array();
		$sql = "SELECT DISTINCT c.CollId, c.CollectionName, c.icon ".
			"FROM omcollections c ".
			"WHERE c.collid = $this->collId ";
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArr["collid"] = $row->CollId;
			$returnArr["name"] = $row->CollectionName;
		}
		$result->close();
		return $returnArr;
	}
	
	public function getUploadList(){
		$returnArr = Array();
		$sql = "SELECT usp.UploadType, usp.title ".
			"FROM uploadspecparameters usp ".
			"WHERE (usp.collid = $this->collId) ORDER BY usp.UploadType";
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$uploadType = $row->UploadType;
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
			$returnArr[$row->UploadType] = $row->title." (".$uploadStr.")";
		}
		$result->close();
		return $returnArr;
	}

    public function readUploadParameters(){
		$sql = "SELECT usp.title, usp.Platform, usp.server, usp.port, usp.Username, usp.Password, usp.SchemaName, usp.driver, ".
    		"usp.digircode, usp.digirpath, usp.digirpkfield, usp.querystr, usp.cleanupsp, usp.dlmisvalid, cs.uploaddate ".
			"FROM uploadspecparameters usp LEFT JOIN omcollectionstats cs ON usp.collid = cs.collid ".
    		"WHERE usp.collid = ".$this->collId." AND usp.UploadType = '".$this->uploadType."'";
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
    		$this->driver = $row->driver;
    		$this->digirCode = $row->digircode;
    		$this->digirPath = $row->digirpath;
    		$this->digirPKField = $row->digirpkfield;
    		$this->queryStr = $row->querystr;
    		$this->cleanupSP = $row->cleanupsp;
			$this->dlmIsValid = $row->dlmisvalid;
    		$this->lastUploadDate = $row->uploaddate;
			if(!$this->lastUploadDate) $this->lastUploadDate = date('Y-m-d H:i:s');
    	}
    	$result->close();

		//Get Field Map for $fieldMap
		if(!$this->fieldMap && $this->uploadType != $this->DIGIRUPLOAD && $this->uploadType != $this->STOREDPROCEDURE){
	    	$sql = "SELECT usm.sourcefield, usm.symbspecfield ".
				"FROM uploadspecmap AS usm ".
	    		"WHERE usm.collid = ".$this->collId." AND usm.uploadtype = ".$this->uploadType;
	    	//echo $sql;
			$rs = $this->conn->query($sql);
	    	while($row = $rs->fetch_object()){
	    		$sourceField = trim($row->sourcefield);
				$symbField = strtolower(trim($row->symbspecfield));
				$this->fieldMap[$symbField]["field"] = $sourceField;
	    	}
	    	$rs->close();
		}
    	
		//Get metadata
		$sql = "SHOW COLUMNS FROM uploadspectemp";
		$rs = $this->conn->query($sql);
    	while($row = $rs->fetch_object()){
    		$field = strtolower($row->Field);
    		$type = $row->Type;
    		$this->symbFields[] = $field;
			if(array_key_exists($field,$this->fieldMap)){
				if(strpos($type,"double") !== false || strpos($type,"int") !== false || strpos($type,"decimal") !== false){
					$this->fieldMap[$field]["type"] = "numeric";
				}
				elseif(strpos($type,"date") !== false){
					$this->fieldMap[$field]["type"] = "date";
				}
				else{
					$this->fieldMap[$field]["type"] = "string";
					if(preg_match('/\(\d+\)$/', $type, $matches)){
						$this->fieldMap[$field]["size"] = substr($matches[0],1,strlen($matches[0])-2);
					}
				}
			}
    	}

    	$rs->close();
    }
    
    public function editUploadProfile(){
		$sql = "UPDATE uploadspecparameters SET title = \"".$_REQUEST["eultitle"]."\", platform = \"".$_REQUEST["eulplatform"].
			"\", server = \"".$_REQUEST["eulserver"]."\", port = ".$_REQUEST["eulport"].", username = \"".$_REQUEST["eulusername"].
			"\", password = \"".$_REQUEST["eulpassword"]."\", schemaname = \"".$_REQUEST["eulschemaname"].
			"\", driver = \"".$_REQUEST["euldriver"]."\", digircode = \"".$_REQUEST["euldigircode"].
			"\", digirpath = \"".$_REQUEST["euldigirpath"]."\", digirpkfield = \"".$_REQUEST["euldigirpkfield"].
			"\", querystr = \"".$this->cleanField(strtolower($_REQUEST["eulquerystr"]))."\", cleanupsp = \"".$_REQUEST["eulcleanupsp"].
			"\", dlmisvalid = ".$_REQUEST["euldlmisvalid"]." WHERE collid = ".$this->collId." AND UploadType = ".$_REQUEST["euluploadtype"];
		//echo $sql;
		if(!$this->conn->query($sql)){
			return "<div>Error Editing Upload Parameters: ".$this->conn->error."</div><div>$sql</div>";
		}
		return "";
	}
	
    public function addUploadProfile(){
		$sql = "INSERT INTO uploadspecparameters(collid, uploadtype, title, platform, server, port, driver, digircode, digirpath, ".
			"digirpkfield, username, password, schemaname, cleanupsp, querystr, dlmisvalid) VALUES (".
			$this->collId.",".$_REQUEST["aupuploadtype"].",\"".$_REQUEST["auptitle"]."\",\"".$_REQUEST["aupplatform"]."\",\"".$_REQUEST["aupserver"]."\",".
			($_REQUEST["aupport"]?$_REQUEST["aupport"]:"NULL").",\"".$_REQUEST["aupdriver"]."\",\"".$_REQUEST["aupdigircode"].
			"\",\"".$_REQUEST["aupdigirpath"]."\",\"".$_REQUEST["aupdigirpkfield"]."\",\"".$_REQUEST["aupusername"].
			"\",\"".$_REQUEST["auppassword"]."\",\"".$_REQUEST["aupschemaname"]."\",\"".$_REQUEST["aupcleanupsp"]."\",\"".
			$this->cleanField(strtolower($_REQUEST["aupquerystr"]))."\",".$_REQUEST["aupdlmisvalid"].")";
		//echo $sql;
		if(!$this->conn->query($sql)){
			return "<div>Error Adding Upload Parameters: ".$this->conn->error."</div><div>$sql</div>";
		}
		return "SUCCESS: New Upload Profile Added";
	}
	
    public function deleteUploadProfile($collId, $uploadType){
		$sql = "DELETE FROM uploadspecparameters WHERE collid = ".$collId." AND uploadtype = ".$uploadType;
		//echo $sql;
		if(!$this->conn->query($sql)){
			return "<div>Error Adding Upload Parameters: ".$this->conn->error."</div><div>$sql</div>";
		}
		return "SUCCESS: Upload Profile Deleted";
	}
	
	private function cleanField($field){
		$rStr = trim($field);
		$rStr = str_replace("\"","'",$rStr);
		return $rStr;
	}
	
	protected function echoFieldMapTable($sourceArr){
		//Build a Source => Symbiota field Map
		$sourceSymbArr = Array();
		foreach($this->fieldMap as $symbField => $fArr){
			$sField = $fArr["field"];
			if($sField != $symbField){
				$sourceSymbArr[$fArr["field"]] = $symbField;
			} 
		}
		
		//Output table rows for source data
		sort($this->symbFields);
		foreach($sourceArr as $fieldName){
			echo "<tr>\n";
			echo "<td style='padding:2px;'>";
			echo $fieldName;
			echo "<input type='hidden' name='sf[]' value='".$fieldName."' />";
			echo "</td>\n";
			echo "<td>\n";
			echo "<select name='tf[]' style='background:".(in_array($fieldName,$this->symbFields)||array_key_exists($fieldName,$sourceSymbArr)?"":"yellow")."'>";
			echo "<option value=''>Select Target Field</option>\n";
			echo "<option value=''>-------------------------</option>\n";
			if(in_array($fieldName,$this->symbFields)){
				//Source Field = Symbiota Field
				foreach($this->symbFields as $symbField){
					echo "<option ".($fieldName==$symbField?"SELECTED":"").">".$symbField."</option>\n";
				}
			}
			elseif(array_key_exists($fieldName,$sourceSymbArr)){
				//Source Field is mapped to Symbiota Field
				foreach($this->symbFields as $symbField){
					echo "<option ".($sourceSymbArr[$fieldName]==$symbField?"SELECTED":"").">".$symbField."</option>\n";
				}
			}
			else{
				foreach($this->symbFields as $symbField){
					echo "<option>".$symbField."</option>\n";
				}
			}
			echo "</select></td>\n";
			echo "</tr>\n";
		}
	}

	public function saveFieldMap(){
		$this->conn->query("DELETE FROM uploadspecmap WHERE collid = ".$this->collId." AND uploadtype = ".$this->uploadType);
		$sqlInsert = "INSERT INTO uploadspecmap(collid,uploadtype,symbspecfield,sourcefield) ";
		$sqlValues = "VALUES (".$this->collId.",".$this->uploadType;
		foreach($this->fieldMap as $k => $v){
			$sourceField = $v["field"];
			if($sourceField != $k){
				$sql = $sqlInsert.$sqlValues.",'".$k."','".$sourceField."')";
				//echo $sql;
				$this->conn->query($sql);
			}
		}
	}
	
 	public function uploadData(){
 		//Stored Procedure upload
	 	$this->readUploadParameters();
 		if($this->uploadType == $this->STOREDPROCEDURE){
 			$this->finalTransferSteps();
 		}
 	}
	
	public function finalTransferSteps(){
		//Run cleanup Stored Procedure, if one exists 
		if($this->cleanupSP){
			if($this->conn->query("CALL ".$this->cleanupSP.";")){
				echo "<li>";
				echo "Following cleanup stored proceure performed on uploadspectemp table: ".$this->cleanupSP."()";
				echo "</li>";
			}
		}
		if(!$this->transferCount){
			$sql = "SELECT count(*) AS cnt FROM uploadspectemp";
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->transferCount = $row->cnt;
			}
			$rs->close();
		}  
		$this->performFinalTransfer();
	}
	
	public function performFinalTransfer(){
		if($this->finalTransfer){
			//Clean and Transfer records from uploadspectemp to specimens
			if($this->conn->query("CALL TransferUploads(".($this->doFullReplace?"1":"0").");")){
				echo "<li>Upload Procedure Complete: ".($this->transferCount?$this->transferCount:" records transferred to central specimen table")."</li>";
			}
			else{
				echo "<li>Unable to complete transfer. Please contact system administrator</li>";
			}
		}
		else{
			echo "<li>Upload Procedure Complete: ".$this->transferCount."</li>";
			echo "<li>Records transferred only to temporary specimen table, review records and then use controls below to transfer to specimen table</li>";
		}
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
	
	public function getDriver(){
		return $this->driver;
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

	public function getCleanupSP(){
		return $this->cleanupSP;
	}
	
	public function getDLMIsValid(){
		return $this->dlmIsValid;
	}
	
	public function updateIsPermissible(){
		if(!$this->dlmIsValid) return false;
		if($this->uploadType == "File Upload") return true;
		if($this->uploadType == "DiGIR Provider"){
			
		}
		if($this->uploadType == "Direct Upload"){
			
		}
		return false;
	}

	public function getTransferCount(){
		return $this->transferCount;
	}
	
	protected function cleanString($inStr){
		$str = trim($inStr);
		$str = str_replace(chr(10),' ',$str);
		$str = str_replace(chr(11),' ',$str);
		$str = str_replace(chr(13),' ',$str);
		$str = str_replace(chr(20),' ',$str);
		$str = str_replace(chr(30),' ',$str);
		return $str;
	}
}
	
?>
