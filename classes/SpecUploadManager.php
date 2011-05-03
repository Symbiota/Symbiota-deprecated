<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/SpecUploadDirect.php');
include_once($serverRoot.'/classes/SpecUploadDigir.php');
include_once($serverRoot.'/classes/SpecUploadFile.php');

class SpecUploadManager{

	protected $conn;
	protected $collId = 0;
	protected $collMetadataArr = Array();
	protected $uploadType;
	protected $uspid = 0;

	protected $title = "";
	protected $platform = 0;
	protected $server;
	protected $port = 0;
	protected $username;
	protected $password;
	protected $digirCode;
	protected $digirPath;
	protected $digirPKField;
	protected $schemaName;
	protected $queryStr;
	protected $cleanupSP;
	protected $lastUploadDate;

	protected $transferCount = 0;
	protected $sourceArr = Array();
	protected $fieldMap = Array();
	protected $symbFields = Array();

	private $DIRECTUPLOAD = 1,$DIGIRUPLOAD = 2, $FILEUPLOAD = 3, $STOREDPROCEDURE = 4, $SCRIPTUPLOAD = 5;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	function __destruct(){
 		if($this->conn) $this->conn->close();
	}
	
	public static function getUploadType($uspid){
		$retStr = "";
		$con = MySQLiConnectionFactory::getCon("readonly");
		$sql = "SELECT uploadtype FROM uploadspecparameters WHERE uspid = ".$uspid;
		$rs = $con->query($sql);
		if($row = $rs->fetch_object()){
			$retStr = $row->uploadtype;
		}
		$con->close();
		return $retStr;
	}
	
	public function setCollId($id){
		$this->collId = $id;
		$this->setCollInfo();
	}
	
	public function setUploadType($t){
		$this->uploadType = $t;
	}
	
	public function setUspid($id){
		$this->uspid = $id;
	}
	
	public function setFieldMap($fm){
		$this->fieldMap = $fm;
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

	private function setCollInfo(){
		$sql = "SELECT DISTINCT c.collid, c.collectionname, c.institutioncode, c.collectioncode, c.icon, c.managementtype, cs.uploaddate ".
			"FROM omcollections c LEFT JOIN omcollectionstats cs ON c.collid = cs.collid ".
			"WHERE c.collid = $this->collId ";
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
	
	public function getCollInfo($fieldStr = ""){
		if(!$this->collMetadataArr) $this->setCollInfo();
		if($fieldStr){
			if(array_key_exists($fieldStr,$this->collMetadataArr)){
				return $this->collMetadataArr[$fieldStr];
			}
			return;			
		}
		return $this->collMetadataArr;
	}
	
	public function getUploadList($uspid = 0){
		$returnArr = Array();
		$sql = "SELECT usp.uspid, usp.uploadtype, usp.title ".
			"FROM uploadspecparameters usp ".
			"WHERE (usp.collid = $this->collId) ";
		if($uspid){
			$sql .= "AND usp.uspid = ".$uspid;
		}
		else{
			$sql .= "ORDER BY usp.uploadtype";
		}
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
			$returnArr[$row->uspid]["title"] = $row->title." (".$uploadStr.")";
			$returnArr[$row->uspid]["uploadtype"] = $row->uploadtype;
		}
		$result->close();
		return $returnArr;
	}

    public function readUploadParameters(){
		$sql = "SELECT usp.title, usp.Platform, usp.server, usp.port, usp.Username, usp.Password, usp.SchemaName, ".
    		"usp.digircode, usp.digirpath, usp.digirpkfield, usp.querystr, usp.cleanupsp, cs.uploaddate ".
			"FROM uploadspecparameters usp LEFT JOIN omcollectionstats cs ON usp.collid = cs.collid ".
    		"WHERE usp.uspid = ".$this->uspid;
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
    		$this->digirPath = $row->digirpath;
    		$this->digirPKField = $row->digirpkfield;
    		$this->queryStr = $row->querystr;
    		$this->cleanupSP = $row->cleanupsp;
    		$this->lastUploadDate = $row->uploaddate;
			if(!$this->lastUploadDate) $this->lastUploadDate = date('Y-m-d H:i:s');
    	}
    	$result->close();

		//Get Field Map for $fieldMap
		if(!$this->fieldMap && $this->uploadType != $this->DIGIRUPLOAD && $this->uploadType != $this->STOREDPROCEDURE){
			$sql = "SELECT usm.sourcefield, usm.symbspecfield ".
				"FROM uploadspecmap usm ".
				"WHERE usm.uspid = ".$this->uspid;
	    	//echo $sql;
			$rs = $this->conn->query($sql);
	    	while($row = $rs->fetch_object()){
	    		$sourceField = $row->sourcefield;
				$symbField = $row->symbspecfield;
				$this->fieldMap[$symbField]["field"] = $sourceField;
	    	}
	    	$rs->close();
		}
    	
		//Get metadata
		$sql = "SHOW COLUMNS FROM uploadspectemp";
		$rs = $this->conn->query($sql);
    	while($row = $rs->fetch_object()){
    		$field = strtolower($row->Field);
    		if($field != "dbpk" && $field != "initialTimestamp" && $field != "occid" && $field != "collid"){
	    		if($this->uploadType == $this->DIGIRUPLOAD){
					$this->fieldMap[$field]["field"] = $field;
	    		} 
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
    	}

    	$rs->close();
    }
    
    public function editUploadProfile(){
		$sql = "UPDATE uploadspecparameters SET title = \"".$_REQUEST["euptitle"]."\"";
		if(array_key_exists("eupplatform",$_REQUEST)) $sql .= ", platform = \"".$_REQUEST["eupplatform"]."\"";
		if(array_key_exists("eupserver",$_REQUEST)) $sql .= ", server = \"".$_REQUEST["eupserver"]."\"";
		if(array_key_exists("eupport",$_REQUEST)) $sql .= ", port = ".($_REQUEST["eupport"]?$_REQUEST["eupport"]:"NULL");
		if(array_key_exists("eupusername",$_REQUEST)) $sql .= ", username = \"".$_REQUEST["eupusername"]."\"";
		if(array_key_exists("euppassword",$_REQUEST)) $sql .= ", password = \"".$_REQUEST["euppassword"]."\"";
		if(array_key_exists("eupschemaname",$_REQUEST)) $sql .= ", schemaname = \"".$_REQUEST["eupschemaname"]."\"";
		if(array_key_exists("eupdigircode",$_REQUEST)) $sql .= ", digircode = \"".$_REQUEST["eupdigircode"]."\"";
		if(array_key_exists("eupdigirpath",$_REQUEST)) $sql .= ", digirpath = \"".$_REQUEST["eupdigirpath"]."\"";
		if(array_key_exists("eupdigirpkfield",$_REQUEST)) $sql .= ", digirpkfield = \"".$_REQUEST["eupdigirpkfield"]."\"";
		if(array_key_exists("eupquerystr",$_REQUEST)) $sql .= ", querystr = \"".$this->cleanString($_REQUEST["eupquerystr"])."\"";
		if(array_key_exists("eupcleanupsp",$_REQUEST)) $sql .= ", cleanupsp = \"".$_REQUEST["eupcleanupsp"]."\"";
		$sql .= " WHERE uspid = ".$this->uspid;
		//echo $sql;
		if(!$this->conn->query($sql)){
			return "<div>Error Editing Upload Parameters: ".$this->conn->error."</div><div>$sql</div>";
		}
		return "";
	}

    public function addUploadProfile(){
		$sql = "INSERT INTO uploadspecparameters(collid, uploadtype, title, platform, server, port, digircode, digirpath, ".
			"digirpkfield, username, password, schemaname, cleanupsp, querystr) VALUES (".
			$this->collId.",".$_REQUEST["aupuploadtype"].",\"".$_REQUEST["auptitle"]."\",\"".$_REQUEST["aupplatform"]."\",\"".$_REQUEST["aupserver"]."\",".
			($_REQUEST["aupport"]?$_REQUEST["aupport"]:"NULL").",\"".$_REQUEST["aupdigircode"].
			"\",\"".$_REQUEST["aupdigirpath"]."\",\"".$_REQUEST["aupdigirpkfield"]."\",\"".$_REQUEST["aupusername"].
			"\",\"".$_REQUEST["auppassword"]."\",\"".$_REQUEST["aupschemaname"]."\",\"".$_REQUEST["aupcleanupsp"]."\",\"".
			$this->cleanString($_REQUEST["aupquerystr"])."\")";
		//echo $sql;
		if(!$this->conn->query($sql)){
			return "<div>Error Adding Upload Parameters: ".$this->conn->error."</div><div>$sql</div>";
		}
		return "SUCCESS: New Upload Profile Added";
	}
	
    public function deleteUploadProfile($uspid){
		$sql = "DELETE FROM uploadspecparameters WHERE uspid = ".$uspid;
		//echo $sql;
		if(!$this->conn->query($sql)){
			return "<div>Error Adding Upload Parameters: ".$this->conn->error."</div><div>$sql</div>";
		}
		return "SUCCESS: Upload Profile Deleted";
	}

	public function echoFieldMapTable($autoMap = 0){
		//Build a Source => Symbiota field Map
		$sourceSymbArr = Array();
		foreach($this->fieldMap as $symbField => $fArr){
			$sourceSymbArr[$fArr["field"]] = $symbField;
		}

		//Output table rows for source data
		sort($this->symbFields);
		$dbpk = (array_key_exists("dbpk",$this->fieldMap)?$this->fieldMap["dbpk"]["field"]:"");
		$autoMapArr = Array();
		foreach($this->sourceArr as $fieldName){
			if($dbpk != $fieldName){
				$isAutoMapped = false;
				if($autoMap && in_array(strtolower($fieldName),$this->symbFields)){
					$isAutoMapped = true;
					$autoMapArr[] = $fieldName;
				}
				echo "<tr>\n";
				echo "<td style='padding:2px;'>";
				echo $fieldName;
				echo "<input type='hidden' name='sf[]' value='".$fieldName."' />";
				echo "</td>\n";
				echo "<td>\n";
				echo "<select name='tf[]' style='background:".(!array_key_exists($fieldName,$sourceSymbArr)&&!$isAutoMapped?"yellow":"")."'>";
				echo "<option value=''>Select Target Field</option>\n";
				echo "<option value=''>Leave Field Unmapped</option>\n";
				echo "<option value=''>-------------------------</option>\n";
				if($isAutoMapped){
					//Source Field = Symbiota Field
					foreach($this->symbFields as $sField){
						echo "<option ".(strtolower($fieldName)==$sField?"SELECTED":"").">".$sField."</option>\n";
					}
				}
				elseif(array_key_exists($fieldName,$sourceSymbArr)){
					//Source Field is mapped to Symbiota Field
					foreach($this->symbFields as $sField){
						echo "<option ".($sourceSymbArr[$fieldName]==$sField?"SELECTED":"").">".$sField."</option>\n";
					}
				}
				else{
					foreach($this->symbFields as $sField){
						echo "<option>".$sField."</option>\n";
					}
				}
				echo "</select></td>\n";
				echo "</tr>\n";
			}
		}
		
		if($autoMapArr){
			$sqlInsert = "INSERT INTO uploadspecmap(uspid,symbspecfield,sourcefield) ";
			$sqlValues = "VALUES (".$this->uspid;
			foreach($autoMapArr as $v){
				if($v != "dbpk"){
					$sql = $sqlInsert.$sqlValues.",'".$v."','".$v."')";
					//echo $sql;
					$this->conn->query($sql);
				}
			}
		}
	}

	public function savePrimaryKey($dbpk){
		$sql = "";
		if($dbpk){
			$sql = "REPLACE INTO uploadspecmap(uspid,symbspecfield,sourcefield) ".
				"VALUES (".$this->uspid.",'dbpk','".$dbpk."')";
		}
		else{
			$sql = "DELETE FROM uploadspecmap WHERE uspid = ".$this->uspid." AND symbspecfield = 'dbpk'";
		}
		$this->conn->query($sql);
	}

	public function saveFieldMap(){
		$this->deleteFieldMap();
		$sqlInsert = "REPLACE INTO uploadspecmap(uspid,symbspecfield,sourcefield) ";
		$sqlValues = "VALUES (".$this->uspid;
		foreach($this->fieldMap as $k => $v){
			if($k != "dbpk"){
				$sourceField = $v["field"];
				$sql = $sqlInsert.$sqlValues.",'".$k."','".$sourceField."')";
				//echo "<div>".$sql."</div>";
				$this->conn->query($sql);
			}
		}
	}

	public function deleteFieldMap(){
		$sql = "DELETE FROM uploadspecmap WHERE uspid = ".$this->uspid." AND symbspecfield <> 'dbpk' ";
		//echo "<div>$sql</div>";
		$this->conn->query($sql);
	}

 	public function analyzeFile(){
 	}

 	public function uploadData($finalTransfer){
 		//Stored Procedure upload; other upload types are controlled by their specific class functions
	 	$this->readUploadParameters();
	 	if($this->queryStr){
	 		if($this->uploadType == $this->STOREDPROCEDURE){
				if($this->conn->query("CALL ".$this->queryStr)){
					echo "<li style='font-weight:bold;'>Stored Procedure executed.</li>";
					echo "<li style='font-weight:bold;'>Initializing final transfer steps...</li>";
					$this->finalUploadSteps($finalTransfer);
				}
	 		}
	 		elseif($this->uploadType == $this->SCRIPTUPLOAD){
	 			if(system($this->queryStr)){
					echo "<li style='font-weight:bold;'>Script Upload successful.</li>";
					echo "<li style='font-weight:bold;'>Initializing final transfer steps...</li>";
	 				$this->finalUploadSteps($finalTransfer);
	 			}
	 		}
	 	}
	}

	public function finalUploadSteps($finalTransfer){
		//Run cleanup Stored Procedure, if one exists 
		if($this->cleanupSP){
			try{
				if($this->conn->query("CALL ".$this->cleanupSP.";")){
					echo "<li>";
					echo "Records cleaned: ".$this->cleanupSP;
					echo "</li>";
				}
			}
			catch(Exception $e){
				echo '<li>ERROR: Record cleaning failed ('.$this->cleanupSP.')</li>';
			}
		}
		if(!$this->transferCount){
			$sql = "SELECT count(*) AS cnt FROM uploadspectemp WHERE collid = ".$this->collId ;
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->transferCount = $row->cnt;
			}
			$rs->close();
		}
		if($finalTransfer){
			$this->performFinalTransfer();
		}
		else{
			echo "<li>Upload Procedure Complete: ".$this->transferCount." records</li>";
			echo "<li>Records transferred only to temporary specimen table; use controls below to transfer to specimen table</li>";
		}
	}

	public function performFinalTransfer(){
		//
		if(stripos($this->collMetadataArr["managementtype"],'live') !== false){
			$sql = 'SELECT count(*) AS reccnt FROM uploadspectemp WHERE dbpk IS NULL AND collid = '.$this->collId;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$recCnt = $r->reccnt;
				if($recCnt > 0){
					$newPk = 1;
					$sqlMax = 'SELECT MAX(dbpk+1) AS maxpk FROM omoccurrences WHERE collid = '.$this->collId;
					$rsMax = $this->conn->query($sqlMax);
					if($rMax = $rsMax->fetch_object()){
						if($rMax->maxpk){
							$newPk = $rMax->maxpk;
						}
					}
					$rsMax->close();
					for($x = 0;$x < $recCnt; $x++){
						$sqlI = 'UPDATE uploadspectemp SET dbpk = '.$newPk.' WHERE dbpk IS NULL LIMIT 1';
						$this->conn->query($sqlI);
						$newPk++;
					}
				}
			}
			$rs->close();
			//Verify that dbpk is unique; should be but let's make sure 
			$sql = 'SELECT count(u.dbpk) AS mcnt '.
				'FROM omoccurrences o INNER JOIN uploadspectemp u ON o.dbpk = u.dbpk '.
				'WHERE o.collid = '.$this->collId.' AND u.collid = '.$this->collId;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				if($r->mcnt > 0){
					echo 'ERROR: DBPKs are not unique; necessary for importing into a Live dataset ';
					return;
				}
			}
			$rs->close();
		}
		
		//Clean and Transfer records from uploadspectemp to specimens
		set_time_limit(800);
		$spCallStr = "CALL TransferUploads(".$this->collId.",0)";
		if($this->conn->query($spCallStr)){
			echo "<li>Upload Procedure Complete: ".($this->transferCount?$this->transferCount." ":"")."</li>";
		}
		else{
			echo "<li>Unable to complete transfer. Please contact system administrator</li>";
		}
	}

	public function getFieldMap(){
		return $this->fieldMap;
	}
	
	public function getSourceArr(){
		return $this->sourceArr;
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

	public function getCleanupSP(){
		return $this->cleanupSP;
	}
	
	public function getTransferCount(){
		return $this->transferCount;
	}

	protected function cleanString($inStr){
		$retStr = trim($inStr);

		$retStr = str_replace("\"","'",$retStr);
		$retStr = str_replace(chr(10),' ',$retStr);
		$retStr = str_replace(chr(11),' ',$retStr);
		$retStr = str_replace(chr(13),' ',$retStr);
		$retStr = str_replace(chr(20),' ',$retStr);
		$retStr = str_replace(chr(30),' ',$retStr);
		return $retStr;
	}

	protected function encodeString($inStr){
 		global $charset;
 		$retStr = $inStr;
		if(strtolower($charset) == "utf-8" || strtolower($charset) == "utf8"){
			if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "ISO-8859-1"){
				//$value = utf8_encode($value);
				$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
			}
		}
		elseif(strtolower($charset) == "ISO-8859-1"){
			if(mb_detect_encoding($inStr,'ISO-8859-1,UTF-8') == "UTF-8"){
				//$value = utf8_decode($value);
				$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
			}
		}
		return $retStr;
	}
}
	
?>
