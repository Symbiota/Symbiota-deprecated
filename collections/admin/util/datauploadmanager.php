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
	protected $doFullReplace = true;
	
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
	
	private $DIRECTUPLOAD = 1,$DIGIRUPLOAD = 2, $FILEUPLOAD = 3;
	
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
				"FROM uploadspecparameters usp INNER JOIN omcollections c ON usp.CollID = c.CollID ";
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
		$sql = "SELECT usp.UploadType ".
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
			$returnArr[$row->UploadType] = $uploadStr;
		}
		$result->close();
		return $returnArr;
	}

    public function readUploadParameters(){
		$sql = "SELECT usp.Platform, usp.server, usp.port, usp.Username, usp.Password, usp.SchemaName, usp.driver, ".
    		"usp.digircode, usp.digirpath, usp.digirpkfield, usp.querystr, usp.cleanupsp, usp.dlmisvalid, cs.uploaddate ".
			"FROM uploadspecparameters usp LEFT JOIN omcollectionstats cs ON usp.collid = cs.collid ".
    		"WHERE usp.collid = ".$this->collId." AND usp.UploadType = '".$this->uploadType."'";
		//echo $sql;
		$result = $this->conn->query($sql);
    	if($row = $result->fetch_object()){
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
		if(!$this->fieldMap && $this->uploadType != $this->DIGIRUPLOAD){
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
				if(strpos($type,"double") !== false || strpos($type,"int") !== false){
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
    
    public function editUploadParameter(){
		$sql = "UPDATE uploadspecparameters SET ";
		if(array_key_exists("platform",$_REQUEST)) $sql .= "platform = \"".$_REQUEST["platform"]."\", ";
    	if(array_key_exists("server",$_REQUEST)) $sql .= "server = \"".$_REQUEST["server"]."\", ";
    	$port = $_REQUEST["port"];
    	if(!$port) $port = "NULL";
    	$sql .= "port = ".$port.", ";
    	if(array_key_exists("username",$_REQUEST)) $sql .= "username = \"".$_REQUEST["username"]."\", ";
    	if(array_key_exists("password",$_REQUEST)) $sql .= "password = \"".$_REQUEST["password"]."\", ";
    	$sql .= "schemaname = \"".$_REQUEST["schemaname"]."\", ";
    	$sql .= "driver = \"".$_REQUEST["driver"]."\", ";
    	if(array_key_exists("digircode",$_REQUEST)) $sql .= "digircode = \"".$_REQUEST["digircode"]."\", ";
    	if(array_key_exists("digirpath",$_REQUEST)) $sql .= "digirpath = \"".$_REQUEST["digirpath"]."\", ";
    	if(array_key_exists("digirpkfield",$_REQUEST)) $sql .= "digirpkfield = \"".$_REQUEST["digirpkfield"]."\", ";
    	$sql .= "querystr = \"".$_REQUEST["querystr"]."\", ";
    	$sql .= "cleanupsp = \"".$_REQUEST["cleanupsp"]."\", ";
    	$sql .= "dlmisvalid = ".$_REQUEST["dlmisvalid"]." ";
    	$sql .= "WHERE collid = ".$this->collId." AND UploadType = ".$this->uploadType;
		//echo $sql;
		if(!$this->conn->query($sql)){
			return "<div>Error Editing Upload Parameters: ".$this->conn->error."</div><div>$sql</div>";
		}
		return "";
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
			echo "<td bgcolor='".(in_array($fieldName,$this->symbFields)||array_key_exists($fieldName,$sourceSymbArr)?"":"yellow")."'>\n";
			echo "<select name='tf[]'><option value=''>Select Target Field</option>\n";
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
	
	public function finalTransferSteps(){
		//Run cleanup Stored Procedure, if one exists 
		if($this->cleanupSP){
			if($this->conn->query("CALL ".$this->cleanupSP.";")){
				echo "<li>";
				echo "Following cleanup stored proceure performed on uploadspectemp table: ".$this->cleanupSP;
				echo "</li>";
			}
		}
		$this->performFinalTransfer();
	}
	
	public function performFinalTransfer(){
		if($this->finalTransfer){
			//Transfer records from uploadspectemp to specimens
			$uploadArr = Array();
			$uploadRS = $this->conn->query("SHOW COLUMNS FROM uploadspectemp");
			while($row = $uploadRS->fetch_objects()){
    			$uploadArr[] = strtolower($row->Field);
			}
			$uploadRS->close();

			$specArr = Array();
			$specRS = $this->conn->query("SHOW COLUMNS FROM omoccurrences");
			while($row = $specRS->fetch_objects()){
    			$specArr[] = strtolower($row->Field);
			}
			$specRS->close();
			$specArr = array_intersect($specArr,$uploadArr);
			unset($specArr[array_search("initialtimestamp")]);

			$sql = "REPLACE INTO omoccurrences ( ".implode(",",$specArr)." ) ".
				"SELECT us.".implode(",us.",$specArr)." ".
				"FROM uploadspectemp AS us";
			if($this->conn->query($sql)){
				echo "<li>Data transferred from temporary upload table to central specimen table</li>";

				if($this->doFullReplace){
					//Delete all records in specimens table that have not been updated (old records that are not in new recordset)
					if($this->conn->query("DELETE FROM omoccurrences WHERE collid = ".$this->collId." AND InitialTimeStamp < DATE_SUB(CURDATE(), INTERVAL 1 DAY)")){
						echo "<li>All old records deleted</li>";
					}
				}
				
				//Update Collection Stats
				if($this->conn->query("CALL UpdateCollectionStats(".$this->collId.");")){
					echo "<li>Collection Statistics have been updated</li>";
				}

				//Delete all records in uploadspectemp table
				if($this->conn->query("DELETE FROM uploadspectemp")){
					echo "<li>Records in temporary upload table has been delete</li>";
				}
				
				echo "<li>Upload Procedure Complete: ".($this->transferCount)." records transferred to central specimen table</li>";
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
