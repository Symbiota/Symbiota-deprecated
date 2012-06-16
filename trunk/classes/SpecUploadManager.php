<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/SpecUploadDirect.php');
include_once($serverRoot.'/classes/SpecUploadDigir.php');
include_once($serverRoot.'/classes/SpecUploadFile.php');
include_once($serverRoot.'/classes/GPoint.php');

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
	protected $storedProcedure;
	protected $lastUploadDate;

	protected $transferCount = 0;
	protected $sourceArr = Array();
	protected $fieldMap = Array();
	protected $symbFields = Array();

	private $DIRECTUPLOAD = 1,$DIGIRUPLOAD = 2, $FILEUPLOAD = 3, $STOREDPROCEDURE = 4, $SCRIPTUPLOAD = 5;
	private $monthNames = array('jan'=>'01','ene'=>'01','feb'=>'02','mar'=>'03','abr'=>'04','apr'=>'04',
		'may'=>'05','jun'=>'06','jul'=>'07','ago'=>'08','aug'=>'08','sep'=>'09','oct'=>'10','nov'=>'11','dec'=>'12','dic'=>'12');

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	function __destruct(){
 		if($this->conn) $this->conn->close();
	}
	
	public static function getUploadType($uspid){
		$retStr = "";
		$con = MySQLiConnectionFactory::getCon("readonly");
		$sql = "SELECT uploadtype FROM uploadspecparameters WHERE (uspid = ".$uspid.')';
		$rs = $con->query($sql);
		if($row = $rs->fetch_object()){
			$retStr = $row->uploadtype;
		}
		$con->close();
		return $retStr;
	}
	
	public function setCollId($id){
		if(is_numeric($id)){
			$this->collId = $id;
			$this->setCollInfo();
		}
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
			$sql = 'SELECT DISTINCT c.CollID, c.CollectionName, c.icon FROM omcollections c ';
			if(array_key_exists('CollAdmin',$userRights)){
				$sql .= 'WHERE (c.collid IN('.implode(',',$userRights['CollAdmin']).')) '; 
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
		$sql = 'SELECT usp.uspid, usp.uploadtype, usp.title '.
			'FROM uploadspecparameters usp '.
			'WHERE (usp.collid = '.$this->collId.') ';
		if($uspid){
			$sql .= 'AND (usp.uspid = '.$uspid.') ';
		}
		else{
			$sql .= "ORDER BY usp.uploadtype,usp.title";
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
		$sql = 'SELECT usp.title, usp.Platform, usp.server, usp.port, usp.Username, usp.Password, usp.SchemaName, '.
    		'usp.digircode, usp.digirpath, usp.digirpkfield, usp.querystr, usp.cleanupsp, cs.uploaddate '.
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
    		$this->digirPath = $row->digirpath;
    		$this->digirPKField = strtolower($row->digirpkfield);
    		$this->queryStr = $row->querystr;
    		$this->storedProcedure = $row->cleanupsp;
    		$this->lastUploadDate = $row->uploaddate;
			if(!$this->lastUploadDate) $this->lastUploadDate = date('Y-m-d H:i:s');
    	}
    	$result->close();

		//Get Field Map for $fieldMap
		if(!$this->fieldMap && $this->uploadType != $this->DIGIRUPLOAD && $this->uploadType != $this->STOREDPROCEDURE){
			$sql = 'SELECT usm.sourcefield, usm.symbspecfield FROM uploadspecmap usm '.
				'WHERE (usm.uspid = '.$this->uspid.')';
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
		$sql .= ' WHERE (uspid = '.$this->uspid.')';
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
		$sql = 'DELETE FROM uploadspecparameters WHERE (uspid = '.$uspid.')';
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
			$sql = "DELETE FROM uploadspecmap WHERE (uspid = ".$this->uspid.") AND symbspecfield = 'dbpk'";
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
		$sql = "DELETE FROM uploadspecmap WHERE (uspid = ".$this->uspid.") AND symbspecfield <> 'dbpk' ";
		//echo "<div>$sql</div>";
		$this->conn->query($sql);
	}

 	public function analyzeFile(){
 	}

 	public function uploadData($finalTransfer){
 		//Stored Procedure upload; other upload types are controlled by their specific class functions
	 	$this->readUploadParameters();

	 	//First, delete all records in uploadspectemp table associated with this collection
		$sqlDel = "DELETE FROM uploadspectemp WHERE (collid = ".$this->collId.')';
		$this->conn->query($sqlDel);

	 	if($this->uploadType == $this->STOREDPROCEDURE){
			$this->finalUploadSteps($finalTransfer);
 		}
 		elseif($this->uploadType == $this->SCRIPTUPLOAD){
 			if(system($this->queryStr)){
				echo '<li style="font-weight:bold;">Script Upload successful.</li>';
				echo '<li style="font-weight:bold;">Initializing final transfer steps...</li>';
 				$this->finalUploadSteps($finalTransfer);
 			}
 		}
		ob_flush();
		flush();
 	}

	public function finalUploadSteps($finalTransfer){
 		//Run custom cleaning Stored Procedure, if one exists
		echo '<li style="font-weight:bold;">Records Upload Complete!</li>';
		echo '<li style="font-weight:bold;">Starting custom cleaning scripts...</li>';
		ob_flush();
		flush();
		if($this->storedProcedure){
			try{
				if($this->conn->query('CALL '.$this->storedProcedure)){
					echo '<li style="font-weight:bold;margin-left:10px;">';
					echo 'Stored procedure executed: '.$this->storedProcedure;
					echo '</li>';
				}
			}
			catch(Exception $e){
				echo '<li style="color:red;margin-left:10px;">ERROR: Record cleaning via custom stroed procedure failed ('.$this->storedProcedure.')</li>';
			}
			ob_flush();
			flush();
		}
		
 		//Prefrom general cleaning and parsing tasks
		$this->recordCleaningStage1();
		$this->recordCleaningStage2();
		
		if(!$this->transferCount){
			$sql = "SELECT count(*) AS cnt FROM uploadspectemp WHERE (collid = ".$this->collId.')';
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->transferCount = $row->cnt;
			}
			$rs->close();
		}

		//Remove temp dbpk values, if they exists
		$sql = 'UPDATE uploadspectemp SET dbpk = NULL WHERE (dbpk LIKE "temp-%") AND (collid = '.$this->collId.')';
		$this->conn->query($sql);
		
		if(stripos($this->collMetadataArr["managementtype"],'snapshot') !== false){
			//If collection is a snapshot, map upload to existing records. These records will be updated rather than appended
			echo '<li style="font-weight:bold;">Linking existing record in preparation for updating (matching DBPKs)... ';
			ob_flush();
			flush();
			$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON (u.dbpk = o.dbpk) AND (u.collid = o.collid) '.
				'SET u.occid = o.occid '.
				'WHERE u.collid = '.$this->collId.' AND u.occid IS NULL';
			$this->conn->query($sql);
			echo 'Done!</li> ';
			
			//Match records that were processed via the portal, walked back to collection's central database, and come back to portal 
			echo '<li style="font-weight:bold;">Linking existing record in preparation for updating (matching catalogNumbers on new records only)... ';
			ob_flush();
			flush();
			$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON (u.catalogNumber = o.catalogNumber) AND (u.collid = o.collid) '.
				'SET u.occid = o.occid '.
				'WHERE u.collid = '.$this->collId.' AND u.occid IS NULL AND u.catalogNumber IS NOT NULL AND o.dbpk IS NULL ';
			$this->conn->query($sql);
			echo 'Done!</li> ';
		}
		
		if($finalTransfer){
			$this->performFinalTransfer();
			echo '<li style="font-weight:bold;">Transfer process Complete!</li>';
		}
		else{
			echo '<li style="font-weight:bold;">Upload Procedure Complete';
			echo ': '.$this->transferCount.' records uploaded to temporary table';
			echo '</li>';
			if($this->transferCount){
				echo '<li style="font-weight:bold;">Use controls below to activate records and transfer to specimen table</li>';
			}
		}
	}
	
	private function recordCleaningStage1(){
		echo '<li style="font-weight:bold;">Starting Stage 1 cleaning</li>';
		
		if(stripos($this->collMetadataArr["managementtype"],'snapshot') !== false){
			echo '<li style="font-weight:bold;margin-left:10px;">Remove NULL dbpk values... ';
			ob_flush();
			flush();
			$sql = 'DELETE FROM uploadspectemp WHERE dbpk IS NULL AND collid = '.$this->collId;
			$this->conn->query($sql);
			echo 'Done!</li> ';
			
			echo '<li style="font-weight:bold;margin-left:10px;">Remove duplicate dbpk values... ';
			ob_flush();
			flush();
			$sql = 'DELETE u.* '.
				'FROM uploadspectemp u INNER JOIN (SELECT dbpk FROM uploadspectemp GROUP BY dbpk HAVING Count(*)>1 ) t2 ON u.dbpk = t2.dbpk'.
				'WHERE collid = '.$this->collId;
			$this->conn->query($sql);
			echo 'Done!</li> ';
		}
		
		echo '<li style="font-weight:bold;margin-left:10px;">Updating NULL eventDate with year-month-day... ';
		ob_flush();
		flush();
		$sql = 'UPDATE uploadspectemp u '.
			'SET u.eventDate = CONCAT_WS("-",LPAD(u.year,4,"19"),IFNULL(LPAD(u.month,2,"0"),"00"),IFNULL(LPAD(u.day,2,"0"),"00")) '.
			'WHERE u.eventDate IS NULL AND u.year > 1300 AND u.year < 2020 AND collid = '.$this->collId;
		$this->conn->query($sql);
		echo 'Done!</li> ';
		
		echo '<li style="font-weight:bold;margin-left:10px;">Updating NULL eventDate with verbatimEventDate... ';
		ob_flush();
		flush();
		$sql = 'SELECT u.dbpk, u.verbatimeventdate '.
			'FROM uploadspectemp u '.
			'WHERE u.eventDate IS NULL AND u.verbatimeventdate IS NOT NULL AND collid = '.$this->collId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$recDbpk = $r->dbpk;
			$dateStr = $this->formatDate($r->verbatimeventdate);
			if($dateStr && $recDbpk){
				$sql = 'UPDATE uploadspectemp '.
					'SET eventdate = "'.$dateStr.'" '.
					'WHERE (collid = '.$this->collId.') AND (dbpk = "'.$recDbpk.'")';
				$this->conn->query($sql);
			}
		}
		$rs->close();
		echo 'Done!</li> ';
		
		echo '<li style="font-weight:bold;margin-left:10px;">Attempting to parse coordinates from verbatimCoordinates field... ';
		ob_flush();
		flush();
		//Parse out verbatimCoordinates
		$sql = 'SELECT dbpk, verbatimcoordinates '.
			'FROM uploadspectemp '.
			'WHERE decimallatitude IS NULL AND verbatimcoordinates IS NOT NULL AND collid = '.$this->collId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($r->verbatimcoordinates){
				$recDbpk = $r->dbpk;
				$coordArr = $this->parseVerbatimCoordinates($r->verbatimcoordinates);
				if($coordArr && $recDbpk){
					$sql = 'UPDATE uploadspectemp '.
						'SET decimallatitude = '.$coordArr['lat'].',decimallongitude = '.$coordArr['lng'].
						'WHERE (collid = '.$this->collId.') AND (dbpk = "'.$recDbpk.'")';
					$this->conn->query($sql);
				}
			}
		}
		$rs->close();
		echo 'Done!</li> ';
		
		echo '<li style="font-weight:bold;margin-left:10px;">Attempting to parse elevation from verbatimElevation field... ';
		ob_flush();
		flush();
		//Clean and parse verbatimElevation string
		$sql = 'SELECT dbpk, verbatimelevation '.
			'FROM uploadspectemp '.
			'WHERE minimumelevationinmeters IS NULL AND verbatimelevation IS NOT NULL AND collid = '.$this->collId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$recDbpk = $r->dbpk;
			$eArr = $this->parseVerbatimElevation($r->verbatimelevation);
			if($eArr && $recDbpk){
				$maxElev = 'NULL';
				if(array_key_exists('maxelev',$eArr)) $maxElev = $eArr['maxelev'];
				$sql = 'UPDATE uploadspectemp '.
					'SET minimumelevationinmeters = '.$eArr['minelev'].',maximumelevationinmeters = '.$maxElev.' '.
					'WHERE (collid = '.$this->collId.') AND (dbpk = "'.$recDbpk.'")';
				$this->conn->query($sql);
			}
		}
		$rs->close();
		echo 'Done!</li> ';
	}

	private function recordCleaningStage2(){
		echo '<li style="font-weight:bold;">Starting Stage 2 cleaning!</li>';
		echo '<li style="font-weight:bold;margin-left:10px;">Further updates on event date fields...';
		ob_flush();
		flush();
		$sql = 'UPDATE uploadspectemp u '.
			'SET u.year = YEAR(u.eventDate) '.
			'WHERE u.collid = '.$this->collId.' AND u.eventDate IS NOT NULL AND u.year IS NULL';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp u '.
			'SET u.month = MONTH(u.eventDate) '.
			'WHERE u.collid = '.$this->collId.' AND u.month IS NULL AND u.eventDate IS NOT NULL';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp u '.
			'SET u.day = DAY(u.eventDate) '.
			'WHERE u.collid = '.$this->collId.' AND u.day IS NULL AND u.eventDate IS NOT NULL';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp u '.
			'SET u.startDayOfYear = DAYOFYEAR(u.eventDate) '.
			'WHERE u.collid = '.$this->collId.' AND u.startDayOfYear IS NULL AND u.eventDate IS NOT NULL';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp u '.
			'SET u.endDayOfYear = DAYOFYEAR(u.LatestDateCollected) '.
			'WHERE u.collid = '.$this->collId.' AND u.endDayOfYear IS NULL AND u.LatestDateCollected IS NOT NULL';
		$this->conn->query($sql);
		echo 'Done!</li> ';

		echo '<li style="font-weight:bold;margin-left:10px;">Cleaning taxonomy...';
		ob_flush();
		flush();

		$taxonRank = 'ssp.';
		$sql = 'SELECT distinct unitind3 FROM taxa '.
			'WHERE unitind3 = "ssp." OR unitind3 = "subsp."';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$taxonRank = $r->unitind3;
		}
		$rs->close();
		
		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = replace(sciname," '.($taxonRank=='subsp.'?'ssp.':'subsp.').' "," '.$taxonRank.' ") '.
			'WHERE sciname like "% '.($taxonRank=='subsp.'?'ssp.':'subsp.').' %" AND collid = '.$this->collId;
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp SET sciname = replace(sciname," var "," var. ") WHERE sciname like "% var %" AND collid = '.$this->collId;
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = replace(sciname," cf. "," "), identificationQualifier = CONCAT_WS("; ","cf.",identificationQualifier), tidinterpreted = null '.
			'WHERE sciname like "% cf. %" AND collid = '.$this->collId;
		$this->conn->query($sql);
		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = replace(sciname," cf "," "), identificationQualifier = CONCAT_WS("; ","cf.",identificationQualifier), tidinterpreted = null '.
			'WHERE sciname like "% cf %" AND collid = '.$this->collId;
		$this->conn->query($sql);
		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = REPLACE(sciname," aff. "," "), identificationQualifier = CONCAT_WS("; ","aff.",identificationQualifier), tidinterpreted = null '.
			'WHERE sciname like "% aff. %" AND collid = '.$this->collId;
		$this->conn->query($sql);
		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = REPLACE(sciname," aff "," "), identificationQualifier = CONCAT_WS("; ","aff.",identificationQualifier), tidinterpreted = null '.
			'WHERE sciname like "% aff %" AND collid = '.$this->collId;
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = trim(sciname), tidinterpreted = null '.
			'WHERE sciname like "% " OR sciname like " %" AND collid = '.$this->collId;
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = replace(sciname,"   "," ") '.
			'WHERE sciname like "%   %" AND collid = '.$this->collId;
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = replace(sciname,"  "," ") '.
			'WHERE sciname like "%  %" AND collid = '.$this->collId;
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = replace(sciname," sp.","") '.
			'WHERE sciname like "% sp." AND collid = '.$this->collId;
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = replace(sciname," sp","") '.
			'WHERE sciname like "% sp" AND collid = '.$this->collId;
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET specificepithet = NULL '.
			'WHERE specificepithet = "sp." OR specificepithet = "sp" AND collid = '.$this->collId;
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp SET taxonrank = "f." '.
			'WHERE taxonrank IS NULL AND InfraSpecificEpithet IS NOT NULL AND scientificname LIKE "% f. %" AND collid = '.$this->collId;
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp SET taxonrank = "f." '.
			'WHERE taxonrank IS NULL AND InfraSpecificEpithet IS NOT NULL AND scientificname LIKE "% forma %" AND collid = '.$this->collId;
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp SET taxonrank = "var." '.
			'WHERE taxonrank IS NULL AND InfraSpecificEpithet IS NOT NULL AND scientificname LIKE "% var. %" AND collid = '.$this->collId;
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp SET taxonrank = "'.$taxonRank.'" '.
			'WHERE taxonrank IS NULL AND InfraSpecificEpithet IS NOT NULL AND scientificname LIKE "% ssp. %" AND collid = '.$this->collId;
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp SET taxonrank = "'.$taxonRank.'" '.
			'WHERE taxonrank IS NULL AND InfraSpecificEpithet IS NOT NULL AND scientificname LIKE "% subsp. %" AND collid = '.$this->collId;
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp SET taxonrank = "'.$taxonRank.'" '.
			'WHERE taxonrank IS NULL AND InfraSpecificEpithet IS NOT NULL AND scientificname LIKE "% ssp %" AND collid = '.$this->collId;
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp SET taxonrank = "'.$taxonRank.'" '.
			'WHERE taxonrank IS NULL AND InfraSpecificEpithet IS NOT NULL AND scientificname LIKE "% subsp %" AND collid = '.$this->collId;
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp SET sciname = trim(CONCAT_WS(" ",Genus,SpecificEpithet,taxonrank,InfraSpecificEpithet)) '.
			'WHERE sciname IS NULL AND Genus IS NOT NULL AND collid = '.$this->collId;
		$this->conn->query($sql);
		echo 'Done!</li> ';
		
		echo '<li style="font-weight:bold;margin-left:10px;">Linking to taxonomic thesaurus...';
		ob_flush();
		flush();

		$sql = 'UPDATE uploadspectemp u INNER JOIN taxa t ON u.sciname = t.sciname '.
			'SET u.TidInterpreted = t.tid WHERE u.TidInterpreted IS NULL AND collid = '.$this->collId;
		$this->conn->query($sql);

		$sql = 'UPDATE taxa t INNER JOIN uploadspectemp u ON t.tid = u.tidinterpreted '.
			'SET u.LocalitySecurity = t.SecurityStatus '.
			'WHERE u.collid = '.$this->collId.' AND (t.SecurityStatus > 0) AND (u.LocalitySecurity = 0 OR u.LocalitySecurity IS NULL)';
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp u INNER JOIN taxstatus ts ON u.tidinterpreted = ts.tid '.
			'SET u.family = ts.family '.
			'WHERE ts.taxauthid = 1 AND ts.family <> "" AND ts.family IS NOT NULL AND (u.family IS NULL OR u.family = "") AND collid = '.$this->collId;
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp u INNER JOIN taxa t ON u.genus = t.unitname1 '.
			'INNER JOIN taxstatus ts on t.tid = ts.tid '.
			'SET u.family = ts.family '.
			'WHERE t.rankid = 180 and ts.taxauthid = 1 AND ts.family IS NOT NULL AND (u.family IS NULL OR u.family = "") AND collid = '.$this->collId;
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp u INNER JOIN taxa t ON u.tidinterpreted = t.tid '.
			'SET u.scientificNameAuthorship = t.author '.
			'WHERE (u.scientificNameAuthorship = "" OR u.scientificNameAuthorship IS NULL) AND t.author IS NOT NULL AND collid = '.$this->collId;
		$this->conn->query($sql);
		echo 'Done!</li> ';

		echo '<li style="font-weight:bold;margin-left:10px;">Cleaning illegal and errored coordinates...';
		ob_flush();
		flush();
		$sql = 'UPDATE uploadspectemp SET DecimalLongitude = -1*DecimalLongitude '.
			'WHERE DecimalLongitude > 0 AND (Country = "USA" OR Country = "United States" OR Country = "U.S.A." OR Country = "Canada" OR Country = "Mexico") AND collid = '.$this->collId;
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET DecimalLatitude = NULL, DecimalLongitude = NULL '.
			'WHERE DecimalLatitude = 0 AND DecimalLongitude = 0 AND collid = '.$this->collId;
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET verbatimcoordinates = CONCAT_WS(" ",DecimalLatitude, DecimalLongitude), DecimalLatitude = NULL, DecimalLongitude = NULL '.
			'WHERE DecimalLatitude < -90 OR DecimalLatitude > 90 OR DecimalLongitude < -180 OR DecimalLongitude > 180 AND collid = '.$this->collId;
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET verbatimCoordinates = CONCAT_WS("; ",verbatimCoordinates,CONCAT_WS(" ","UTM:",CONCAT(UtmZoning," "),CONCAT(UtmNorthing,"N"),CONCAT(UtmEasting,"E"))) '.
			'WHERE UtmNorthing IS NOT NULL AND collid = '.$this->collId;
		$this->conn->query($sql);
		echo 'Done!</li> ';
	}
	
	public function performFinalTransfer(){
		//Clean and Transfer records from uploadspectemp to specimens
		set_time_limit(1000);

		echo '<li style="font-weight:bold;">Updating existing occurrence records... ';
		ob_flush();
		flush();
		$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON u.occid = o.occid '.
			'SET o.basisOfRecord = u.basisOfRecord, o.occurrenceID = u.occurrenceID, o.catalogNumber = u.catalogNumber, '.
			'o.otherCatalogNumbers = u.otherCatalogNumbers, o.ownerInstitutionCode = u.ownerInstitutionCode, o.family = u.family, '.
			'o.scientificName = u.scientificName, o.sciname = u.sciname, o.tidinterpreted = u.tidinterpreted, o.genus = u.genus, o.institutionID = u.institutionID, '.
			'o.collectionID = u.collectionID, o.specificEpithet = u.specificEpithet, o.datasetID = u.datasetID, o.taxonRank = u.taxonRank, '.
			'o.infraspecificEpithet = u.infraspecificEpithet, o.institutionCode = u.institutionCode, o.collectionCode = u.collectionCode, '.
			'o.scientificNameAuthorship = u.scientificNameAuthorship, o.taxonRemarks = u.taxonRemarks, o.identifiedBy = u.identifiedBy, '.
			'o.dateIdentified = u.dateIdentified, o.identificationReferences = u.identificationReferences, '.
			'o.identificationRemarks = u.identificationRemarks, o.identificationQualifier = u.identificationQualifier, o.typeStatus = u.typeStatus, '.
			'o.recordedBy = u.recordedBy, o.recordNumber = u.recordNumber, '.
			'o.associatedCollectors = u.associatedCollectors, o.eventDate = u.eventDate, '.
			'o.year = u.year, o.month = u.month, o.day = u.day, o.startDayOfYear = u.startDayOfYear, o.endDayOfYear = u.endDayOfYear, '.
			'o.verbatimEventDate = u.verbatimEventDate, o.habitat = u.habitat, o.substrate = u.substrate, o.fieldNotes = u.fieldNotes, o.occurrenceRemarks = u.occurrenceRemarks, o.informationWithheld = u.informationWithheld, '.
			'o.associatedOccurrences = u.associatedOccurrences, o.associatedTaxa = u.associatedTaxa, '.
			'o.dynamicProperties = u.dynamicProperties, o.verbatimAttributes = u.verbatimAttributes, '.
			'o.reproductiveCondition = u.reproductiveCondition, o.cultivationStatus = u.cultivationStatus, o.establishmentMeans = u.establishmentMeans, '.
			'o.country = u.country, o.stateProvince = u.stateProvince, o.county = u.county, o.municipality = u.municipality, o.locality = u.locality, '.
			'o.localitySecurity = u.localitySecurity, o.localitySecurityReason = u.localitySecurityReason, o.decimalLatitude = u.decimalLatitude, o.decimalLongitude = u.decimalLongitude, '.
			'o.geodeticDatum = u.geodeticDatum, o.coordinateUncertaintyInMeters = u.coordinateUncertaintyInMeters, '.
			'o.coordinatePrecision = u.coordinatePrecision, o.locationRemarks = u.locationRemarks, o.verbatimCoordinates = u.verbatimCoordinates, '.
			'o.verbatimCoordinateSystem = u.verbatimCoordinateSystem, o.georeferencedBy = u.georeferencedBy, o.georeferenceProtocol = u.georeferenceProtocol, '.
			'o.georeferenceSources = u.georeferenceSources, o.georeferenceVerificationStatus = u.georeferenceVerificationStatus, '.
			'o.georeferenceRemarks = u.georeferenceRemarks, o.minimumElevationInMeters = u.minimumElevationInMeters, '.
			'o.maximumElevationInMeters = u.maximumElevationInMeters, o.verbatimElevation = u.verbatimElevation, '.
			'o.previousIdentifications = u.previousIdentifications, o.disposition = u.disposition, o.modified = u.modified, '.
			'o.language = u.language, o.recordEnteredBy = u.recordEnteredBy, o.duplicateQuantity = u.duplicateQuantity '.
			'WHERE u.collid = '.$this->collId;
		if($this->conn->query($sql)){
			echo 'Done!</li> ';
		}
		else{
			echo 'FAILED! ERROR: '.$this->conn->error.'</li> ';
		}
		
		if(stripos($this->collMetadataArr["managementtype"],'snapshot') !== false){
			//Update DBPKs for records that were processed via the portal, walked back to collection's central database, and now come back to portal with assigned DBPKs 
			echo '<li style="font-weight:bold;">Updating DBPKs for records originally processed in portal, walked back to central database, and now return to portal with assigned DBPKs... ';
			ob_flush();
			flush();
			$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON u.occid = o.occid '.
				'SET o.dbpk = u.dbpk '.
				'WHERE u.collid = '.$this->collId.' AND o.dbpk IS NULL AND u.dbpk IS NOT NULL';
			if($this->conn->query($sql)){
				echo 'Done!</li> ';
			}
			else{
				echo 'FAILED! ERROR: '.$this->conn->error.'</li> ';
			}
		}
		
		echo '<li style="font-weight:bold;">Inserting new records into active occurrence table... ';
		ob_flush();
		flush();
		$sql = 'INSERT IGNORE INTO omoccurrences (collid, dbpk, basisOfRecord, occurrenceID, catalogNumber, otherCatalogNumbers, ownerInstitutionCode, family, scientificName, '.
			'sciname, tidinterpreted, genus, institutionID, collectionID, specificEpithet, datasetID, taxonRank, infraspecificEpithet, institutionCode, collectionCode, '.
			'scientificNameAuthorship, taxonRemarks, identifiedBy, dateIdentified, identificationReferences, identificationRemarks, '.
			'identificationQualifier, typeStatus, recordedBy, recordNumber, associatedCollectors, '.
			'eventDate, Year, Month, Day, startDayOfYear, endDayOfYear, verbatimEventDate, habitat, substrate, fieldNotes, occurrenceRemarks, informationWithheld, '.
			'associatedOccurrences, associatedTaxa, dynamicProperties, verbatimAttributes, reproductiveCondition, cultivationStatus, establishmentMeans, country, stateProvince, '.
			'county, municipality, locality, localitySecurity, localitySecurityReason, decimalLatitude, decimalLongitude, geodeticDatum, coordinateUncertaintyInMeters, '.
			'coordinatePrecision, locationRemarks, verbatimCoordinates, verbatimCoordinateSystem, georeferencedBy, georeferenceProtocol, '.
			'georeferenceSources, georeferenceVerificationStatus, georeferenceRemarks, minimumElevationInMeters, maximumElevationInMeters, '.
			'verbatimElevation, previousIdentifications, disposition, modified, language, recordEnteredBy, duplicateQuantity ) '.
			'SELECT u.collid, u.dbpk, u.basisOfRecord, u.occurrenceID, u.catalogNumber, u.otherCatalogNumbers, u.ownerInstitutionCode, '.
			'u.family, u.scientificName, '.
			'u.sciname, u.tidinterpreted, u.genus, u.institutionID, u.collectionID, u.specificEpithet, u.datasetID, u.taxonRank, u.infraspecificEpithet, '.
			'u.institutionCode, u.collectionCode, u.scientificNameAuthorship, u.taxonRemarks, u.identifiedBy, u.dateIdentified, '.
			'u.identificationReferences, u.identificationRemarks, u.identificationQualifier, u.typeStatus, u.recordedBy, u.recordNumber, '.
			'u.associatedCollectors, u.eventDate, u.Year, u.Month, u.Day, u.startDayOfYear, '.
			'u.endDayOfYear, u.verbatimEventDate, u.habitat, u.substrate, u.fieldNotes, u.occurrenceRemarks, u.informationWithheld, u.associatedOccurrences, u.associatedTaxa, '.
			'u.dynamicProperties, u.verbatimAttributes, u.reproductiveCondition, u.cultivationStatus, u.establishmentMeans, u.country, u.stateProvince, u.county, '.
			'u.municipality, u.locality, u.localitySecurity, u.localitySecurityReason, u.decimalLatitude, u.decimalLongitude, u.geodeticDatum, u.coordinateUncertaintyInMeters, '.
			'u.coordinatePrecision, u.locationRemarks, u.verbatimCoordinates, u.verbatimCoordinateSystem, u.georeferencedBy, u.georeferenceProtocol, '.
			'u.georeferenceSources, u.georeferenceVerificationStatus, u.georeferenceRemarks, u.minimumElevationInMeters, u.maximumElevationInMeters, '.
			'u.verbatimElevation, u.previousIdentifications, u.disposition, u.modified, u.language, u.recordEnteredBy, u.duplicateQuantity '.
			'FROM uploadspectemp u '.
			'WHERE u.occid IS NULL AND u.collid = '.$this->collId;
		if($this->conn->query($sql)){
			echo 'Done!</li> ';
		}
		else{
			echo 'FAILED! ERROR: '.$this->conn->error.'</li> ';
		}
		
		echo '<li style="font-weight:bold;">Updating georeference indexing... ';
		ob_flush();
		flush();
		$sql = 'INSERT IGNORE INTO omoccurgeoindex(tid,decimallatitude,decimallongitude) '.
			'SELECT DISTINCT u.tidinterpreted, round(u.decimallatitude,3), round(u.decimallongitude,3) '.
			'FROM uploadspectemp u '.
			'WHERE u.tidinterpreted IS NOT NULL AND u.decimallatitude IS NOT NULL '.
			'AND u.decimallongitude IS NOT NULL';
		if($this->conn->query($sql)){
			echo 'Done!</li> ';
		}
		else{
			echo 'FAILED! ERROR: '.$this->conn->error.'</li> ';
		}
		
		$sql = 'DELETE FROM uploadspectemp WHERE collid = '.$this->collId;
		$this->conn->query($sql);
		echo '<li style="font-weight:bold;">Collection transfer process finished</li>';
		ob_flush();
		flush();
		
		//Update collection stats
		$sql = 'UPDATE omcollectionstats SET uploaddate = NOW() WHERE collid = '.$this->collId;
		$this->conn->query($sql);

		echo '<li style="font-weight:bold;">Updating total record count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.recordcnt = (SELECT Count(o.occid) FROM omoccurrences o WHERE (o.collid = '.$this->collId.')) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		echo 'Done!</li> ';
		
		echo '<li style="font-weight:bold;">Updating family count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.familycnt = (SELECT COUNT(DISTINCT o.family) '.
			'FROM omoccurrences o WHERE (o.collid = '.$this->collId.')) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		echo 'Done!</li> ';
		
		echo '<li style="font-weight:bold;">Updating genus count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.genuscnt = (SELECT COUNT(DISTINCT t.unitname1) '.
			'FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '.
			'WHERE (o.collid = '.$this->collId.') AND t.rankid >= 180) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		echo 'Done!</li>';

		echo '<li style="font-weight:bold;">Updating species count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.speciescnt = (SELECT count(DISTINCT t.unitname1, t.unitname2) AS spcnt '.
			'FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '.
			'WHERE (o.collid = '.$this->collId.') AND t.rankid >= 220) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		echo 'Done</li>';
		
		echo '<li style="font-weight:bold;">Updating georeference count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.georefcnt = (SELECT Count(o.occid) FROM omoccurrences o WHERE (o.DecimalLatitude Is Not Null) '.
			'AND (o.DecimalLongitude Is Not Null) AND (o.CollID = '.$this->collId.')) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		echo 'Done!</li>';
	}

	protected function loadRecord($recMap){
		//Only import record if at least one of the minimal fields have data 
		if((array_key_exists('catalognumber',$recMap) && $recMap['catalognumber'])
			|| (array_key_exists('recordedby',$recMap) && $recMap['recordedby'])
			|| (array_key_exists('eventdate',$recMap) && $recMap['eventdate'])
			|| (array_key_exists('locality',$recMap) && $recMap['locality'])
			|| (array_key_exists('sciname',$recMap) && $recMap['sciname'])
			|| (array_key_exists('scientificname',$recMap) && $recMap['scientificname'])){
			if(array_key_exists('eventdate',$recMap) && $recMap['eventdate'] && is_numeric($recMap['eventdate'])){
				if($recMap['eventdate'] > 2100 && $recMap['eventdate'] < 45000){
					//Date field was converted to Excel's numeric format (number of days since 01/01/1900)
					$recMap['eventdate'] = date('Y-m-d', mktime(0,0,0,1,$recMap['eventdate']-1,1900));
				}
				elseif($recMap['eventdate'] > 2200000 && $recMap['eventdate'] < 2500000){
					$dArr = explode('/',jdtogregorian($recMap['eventdate']));
					$recMap['eventdate'] = $dArr[2].'-'.$dArr[0].'-'.$dArr[1];
				}
				elseif($recMap['eventdate'] > 19000000){
					$recMap['eventdate'] = substr($recMap['eventdate'],0,4).'-'.substr($recMap['eventdate'],4,2).'-'.substr($recMap['eventdate'],6,2);
				}
			}
			if(array_key_exists('latestdatecollected',$recMap) && $recMap['latestdatecollected'] && is_numeric($recMap['latestdatecollected'])){
				if($recMap['latestdatecollected'] > 2100 && $recMap['latestdatecollected'] < 45000){
					//Date field was converted to Excel's numeric format (number of days since 01/01/1900)
					$recMap['latestdatecollected'] = date('Y-m-d', mktime(0,0,0,1,$recMap['latestdatecollected']-1,1900));
				}
				elseif($recMap['latestdatecollected'] > 2200000 && $recMap['latestdatecollected'] < 2500000){
					$dArr = explode('/',jdtogregorian($recMap['latestdatecollected']));
					$recMap['latestdatecollected'] = $dArr[2].'-'.$dArr[0].'-'.$dArr[1];
				}
				elseif($recMap['latestdatecollected'] > 19000000){
					$recMap['latestdatecollected'] = substr($recMap['latestdatecollected'],0,4).'-'.substr($recMap['latestdatecollected'],4,2).'-'.substr($recMap['latestdatecollected'],6,2);
				}
			}
			if(array_key_exists('verbatimeventdate',$recMap) && $recMap['verbatimeventdate'] && is_numeric($recMap['verbatimeventdate']) 
				&& $recMap['verbatimeventdate'] > 2100 && $recMap['verbatimeventdate'] < 45000){
					//Date field was converted to Excel's numeric format (number of days since 01/01/1900)
					$recMap['verbatimeventdate'] = date('Y-m-d', mktime(0,0,0,1,$recMap['verbatimeventdate']-1,1900));
			}
			if(array_key_exists('dateidentified',$recMap) && $recMap['dateidentified'] && is_numeric($recMap['dateidentified']) 
				&& $recMap['dateidentified'] > 2100 && $recMap['dateidentified'] < 45000){
					//Date field was converted to Excel's numeric format (number of days since 01/01/1900)
					$recMap['dateidentified'] = date('Y-m-d', mktime(0,0,0,1,$recMap['dateidentified']-1,1900));
			}
			//If month is text, avoid SQL error by converting to numeric value 
			if(array_key_exists('month',$recMap)){
				if(!is_numeric($recMap['month'])){
					if(strlen($recMap['month']) > 2){
						$monAbbr = strtolower(substr($recMap['month'],0,3));
						if(array_key_exists('month',$recMap)){
							$recMap['month'] = $this->monthNames[$monAbbr];
						}
						else{
							if(!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate']){
								$recMap['verbatimeventdate'] = $recMap['day'].' '.$recMap['month'].' '.$recMap['year'];
							}
							$recMap['month'] = '';
						}
					}
					else{
						$recMap['month'] = '';
					}
				}
			}
			//Convert UTM to Lat/Long
			if(array_key_exists('utmnorthing',$recMap) && array_key_exists('utmeasting',$recMap) && array_key_exists('utmzoning',$recMap) 
				&& (!array_key_exists('decimallatitude',$recMap) || !$recMap['decimallatitude']) 
				&& (!array_key_exists('decimallongitude',$recMap) || !$recMap['decimallongitude'])){
				$n = $recMap['utmnorthing'];
				$e = $recMap['utmeasting'];
				$z = $recMap['utmzoning'];
				$d = (array_key_exists('geodeticdatum',$recMap)?$recMap['geodeticdatum']:'');
				if($n && $e && $z){
					$gPoint = new GPoint($d);
					$gPoint->setUTM($e,$n,$z);
					$gPoint->convertTMtoLL();
					$lat = $gPoint->Lat();
					$lng = $gPoint->Long();
					if($lat && $lng){
						$recMap['decimallatitude'] = round($lat,6);
						$recMap['decimallongitude'] = round($lng,6);
					}
				}
			}
			//Populate sciname if null
			if(!array_key_exists('sciname',$recMap) || !$recMap['sciname']){
				if(array_key_exists("genus",$recMap)){
					//Build sciname from individual units supplied by source
					$sciName = $recMap["genus"];
					if(array_key_exists("specificepithet",$recMap)) $sciName .= " ".$recMap["specificepithet"];
					if(array_key_exists("taxonrank",$recMap)) $sciName .= " ".$recMap["taxonrank"];
					if(array_key_exists("infraspecificepithet",$recMap)) $sciName .= " ".$recMap["infraspecificepithet"];
					$recMap['sciname'] = trim($sciName);
				}
				elseif(array_key_exists('scientificname',$recMap)){
					//Clean and parse scientific name
					$parsedArr = $this->parseScientificName($recMap['scientificname']);
					$scinameStr = '';
					if(array_key_exists('unitname1',$parsedArr)){
						$scinameStr = $parsedArr['unitname1'];
						if(!array_key_exists('genus',$recMap) || $recMap['genus']){
							$recMap['genus'] = $parsedArr['unitname1'];
						}
					} 
					if(array_key_exists('unitname2',$parsedArr)){
						$scinameStr .= ' '.$parsedArr['unitname2'];
						if(!array_key_exists('specificepithet',$recMap) || !$recMap['specificepithet']){
							$recMap['specificepithet'] = $parsedArr['unitname2'];
						}
					} 
					if(array_key_exists('unitind3',$parsedArr)){
						$scinameStr .= ' '.$parsedArr['unitind3'];
						if((!array_key_exists('taxonrank',$recMap) || !$recMap['taxonrank'])){
							$recMap['taxonrank'] = $parsedArr['unitind3'];
						}
					}
					if(array_key_exists('unitname3',$parsedArr)){
						$scinameStr .= ' '.$parsedArr['unitname3'];
						if(!array_key_exists('infraspecificepithet',$recMap) || !$recMap['infraspecificepithet']){
							$recMap['infraspecificepithet'] = $parsedArr['unitname3'];
						}
					}
					if(array_key_exists('author',$parsedArr)){
						if(!array_key_exists('scientificnameauthorship',$recMap) || !$recMap['scientificnameauthorship']){
							$recMap['scientificnameauthorship'] = $parsedArr['author'];
						}
					}
					$recMap['sciname'] = trim($scinameStr);
				}
			}
			
			//If a DiGIR load, set dbpk value
			if($this->digirPKField && array_key_exists($this->digirPKField,$recMap) && !array_key_exists('dbpk',$recMap)){
				$recMap['dbpk'] = $recMap[$this->digirPKField];
			}
			
			//If there is no dbpk set, set a temp value to aid in locating record in uploadspectemp during cleaning stage 
			if(!array_key_exists('dbpk',$recMap) || !$recMap['dbpk']){
				$recMap['dbpk'] = 'temp-'.$this->transferCount;
			}
			
			//Create update str 
			$sqlFields = '';
			$sqlValues = '';
			foreach($recMap as $symbField => $valueStr){
				$sqlFields .= ','.$symbField;
				$valueStr = $this->encodeString($valueStr);
				$valueStr = $this->cleanString($valueStr);
				//Load data
				$type = '';
				$size = 0;
				if(array_key_exists($symbField,$this->fieldMap)){ 
					if(array_key_exists('type',$this->fieldMap[$symbField])){
						$type = $this->fieldMap[$symbField]["type"];
					}
					if(array_key_exists('size',$this->fieldMap[$symbField])){
						$size = $this->fieldMap[$symbField]["size"];
					}
				}
				switch($type){
					case "numeric":
						if(is_numeric($valueStr)){
							$sqlValues .= ",".$valueStr;
						}
						elseif(is_numeric(str_replace(',',"",$valueStr))){
							$sqlValues .= ",".str_replace(',',"",$valueStr);
						}
						else{
							$sqlValues .= ",NULL";
						}
						break;
					case "date":
						$dateStr = $this->formatDate($valueStr);
						if($dateStr){
							$sqlValues .= ',"'.$dateStr.'"';
						}
						else{
							$sqlValues .= ",NULL";
						}
						break;
					default:	//string
						if($size && strlen($valueStr) > $size){
							$valueStr = substr($valueStr,0,$size);
						}
						if($valueStr){
							$sqlValues .= ',"'.$valueStr.'"';
						}
						else{
							$sqlValues .= ",NULL";
						}
				}
			}
			
			$sql = "INSERT INTO uploadspectemp(collid".$sqlFields.") ".
				"VALUES(".$this->collId.$sqlValues.")";
			//echo "<div>SQL: ".$sql."</div>";
			
			if($this->conn->query($sql)){
				$this->transferCount++;
				if($this->transferCount%1000 == 0) echo '<li style="font-weight:bold;">Upload count: '.$this->transferCount.'</li>';
				ob_flush();
				flush();
				//echo "<li>";
				//echo "Appending/Replacing observation #".$this->transferCount.": SUCCESS";
				//echo "</li>";
			}
			else{
				echo "<li>FAILED adding record #".$this->transferCount."</li>";
				echo "<div style='margin-left:10px;'>Error: ".$this->conn->error."</div>";
				echo "<div style='margin:0px 0px 10px 10px;'>SQL: $sql</div>";
			}
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

	public function getStoredProcedure(){
		return $this->storedProcedure;
	}
	
	public function getTransferCount(){
		return $this->transferCount;
	}
	
	private function formatDate($inStr){
		$dateStr = trim($inStr);
    	if(!$dateStr) return;
    	$t = '';
		$y = '';
		$m = '00';
		$d = '00';
		if(preg_match('/\d{2}:\d{2}:\d{2}/',$dateStr,$match)){
			$t = $match[0];
		}
		if(preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})\D*/',$dateStr,$match)){
			//Format: yyyy-mm-dd, yyyy-m-d
			$y = $match[1];
			$m = $match[2];
			$d = $match[3];
		}
		elseif(preg_match('/^(\d{1,2})[\s\/-]{1}(\D{3,})\.*[\s\/-]{1}(\d{2,4})/',$dateStr,$match)){
			//Format: dd mmm yyyy, d mmm yy, dd-mmm-yyyy, dd-mmm-yy
			$d = $match[1];
			$mStr = $match[2];
			$y = $match[3];
			$mStr = strtolower(substr($mStr,0,3));
			if(array_key_exists($mStr,$this->monthNames)){
				$m = $this->monthNames[$mStr];
			}
		}
		elseif(preg_match('/^(\d{1,2})-(\D{3,})-(\d{2,4})/',$dateStr,$match)){
			//Format: dd-mmm-yyyy
			$d = $match[1];
			$mStr = $match[2];
			$y = $match[3];
			$mStr = strtolower(substr($mStr,0,3));
			$m = $this->monthNames[$mStr];
		}
		elseif(preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{2,4})/',$dateStr,$match)){
			//Format: mm/dd/yyyy, m/d/yy
			$m = $match[1];
			$d = $match[2];
			$y = $match[3];
		}
		elseif(preg_match('/^(\D{3,})\.*\s{1}(\d{1,2}),{0,1}\s{1}(\d{2,4})/',$dateStr,$match)){
			//Format: mmm dd, yyyy
			$mStr = $match[1];
			$d = $match[2];
			$y = $match[3];
			$mStr = strtolower(substr($mStr,0,3));
			$m = $this->monthNames[$mStr];
		}
		elseif(preg_match('/^(\d{1,2})-(\d{1,2})-(\d{2,4})/',$dateStr,$match)){
			//Format: mm-dd-yyyy, mm-dd-yy
			$m = $match[1];
			$d = $match[2];
			$y = $match[3];
		}
		elseif(preg_match('/^(\D{3,})\.*\s([1,2]{1}[0,5-9]{1}\d{2})/',$dateStr,$match)){
			//Format: mmm yyyy
			$mStr = strtolower(substr($match[1],0,3));
			if(array_key_exists($mStr,$this->monthNames)){
				$m = $this->monthNames[$mStr];
			}
			else{
				$m = '00';
			}
			$y = $match[2];
		}
		elseif(preg_match('/([1,2]{1}[0,5-9]{1}\d{2})/',$dateStr,$match)){
			//Format: yyyy
			$y = $match[1];
		}
		$retDate = '';
		if($y){
			//check to set if day is valid for month
			if($d == 30 && $m == 2){
				//Bad feb date
				return '';
			}
			if($d == 31 && ($m == 4 || $m == 6 || $m == 9 || $m == 11)){
				//Bad date, month w/o 31 days
				return '';
			}
			//Do some cleaning
			if(strlen($y) == 2){ 
				if($y < 20) $y = '20'.$y;
				else $y = '19'.$y;
			}
			if(strlen($m) == 1) $m = '0'.$m;
			if(strlen($d) == 1) $d = '0'.$d;
			//Build
			$retDate = $y.'-'.$m.'-'.$d;
		}
		elseif(($timestamp = strtotime($retDate)) !== false){
			$retDate = date('Y-m-d', $timestamp);
		}
		if($t){
			$retDate .= ' '.$t;
		}
		return $retDate;
	}

	private function parseScientificName($inStr){
		//Converts scinetific name with author embedded into separate fields
		$retArr = array();
		$sciNameArr = explode(' ',$inStr);
		if(count($sciNameArr)){
			if(strtolower($sciNameArr[0]) == 'x'){
				//Genus level hybrid
				$retArr['unitind1'] = array_shift($sciNameArr);
			}
			//Genus
			$retArr['unitname1'] = array_shift($sciNameArr);
			if(count($sciNameArr)){
				if(strtolower($sciNameArr[0]) == 'x'){
					//Species level hybrid
					$retArr['unitind2'] = array_shift($sciNameArr);
				}
				elseif((strpos($sciNameArr[0],'.') !== false) || ord($sciNameArr[0]) < 97 || ord($sciNameArr[0]) > 122){
					//It is assumed that Author has been reached, thus stop process 
					unset($sciNameArr);
				}
				else{
					//Specific Epithet
					$retArr['unitname2'] = array_shift($sciNameArr);
				}
			}
		}
		if(isset($sciNameArr) && $sciNameArr){
			//Assume rest is author; if that is not true, author value will be replace in following loop
			$retArr['author'] = implode(' ',$sciNameArr);
			//cycles through the final terms to extract the last infraspecific data
			while($sciStr = array_shift($sciNameArr)){
				if($sciStr == 'f.' || $sciStr == 'fo.' || $sciStr == 'fo' || $sciStr == 'forma'){
					if($sciNameArr){
						$retArr['unitind3'] = 'f.';
						$retArr['unitname3'] = array_shift($sciNameArr);
						$retArr['author'] = implode(' ',$sciNameArr);
					}
				}
				elseif($sciStr == 'var.' || $sciStr == 'var'){
					if($sciNameArr){
						$retArr['unitind3'] = 'var.';
						$retArr['unitname3'] = array_shift($sciNameArr);
						$retArr['author'] = implode(' ',$sciNameArr);
					}
				}
				elseif($sciStr == 'ssp.' || $sciStr == 'ssp' || $sciStr == 'subsp.' || $sciStr == 'subsp'){
					if($sciNameArr){
						$retArr['unitind3'] = 'ssp.';
						$retArr['unitname3'] = array_shift($sciNameArr);
						$retArr['author'] = implode(' ',$sciNameArr);
					}
				}
			}
		}
		if(array_key_exists('unitind1',$retArr)){
			$retArr['unitname1'] = $retArr['unitind1'].' '.$retArr['unitname1'];
			unset($retArr['unitind1']); 
		}
		if(array_key_exists('unitind2',$retArr)){
			$retArr['unitname2'] = $retArr['unitind2'].' '.$retArr['unitname2'];
			unset($retArr['unitind2']); 
		}
		return $retArr;
	}

	private function parseVerbatimCoordinates($inStr){
		$retArr = array();
		//Try to parse lat/lng
		$latDeg = 'null';$latMin = 'null';$latSec = 'null';$latNS = 'N';
		$lngDeg = 'null';$lngMin = 'null';$lngSec = 'null';$lngEW = 'W';
		//Grab lat deg and min
		if(preg_match('/(\d{1,2})[°d*]{1}\s*(\d{1,2}\.{0,1}\d*)[\'m]{1}(.*[NS]+.*)/i',$inStr,$m)){
			$latDeg = $m[1];
			$latMin = $m[2];
			$leftOver = trim($m[3]);
			//Grab lat sec
			if(preg_match('/(\d{0,2}\.{0,1}\d*)["s]{1}(.*[NS]+.*)/i',$leftOver,$m)){
				$latSec = $m[1];
				if(count($m)>2){
					$leftOver = trim($m[2]);
				}
			}
			//Grab lat NS
			if(preg_match('/([NS]+)(.*[EW]+.*)/i',$leftOver,$m)){
				$latNS = $m[1];
				$leftOver = trim($m[2]);
			}
			//Grab lng deg and min
			if(preg_match('/(\d{1,3})[°d*]{1}\s*(\d{1,2}\.{0,1}\d*)[\'m]{1}(.*[EW]+.*)/i',$leftOver,$m)){
				$lngDeg = $m[1];
				$lngMin = $m[2];
				$leftOver = trim($m[3]);
				//Grab lng sec
				if(preg_match('/(\d{0,2}\.{0,1}\d*)["s]{1}(.*[EW]+.*)/i',$leftOver,$m)){
					$lngSec = $m[1];
					if(count($m)>2){
						$leftOver = trim($m[2]);
					}
				}
				//Grab lng EW
				if(preg_match('/([EW]+)/i',$leftOver,$m)){
					$latEW = $m[1];
				}
				if(is_numeric($latDeg) && is_numeric($latMin) && is_numeric($lngDeg) && is_numeric($lngMin)){
					if($latDeg < 90 && $latMin < 60 && $lngDeg < 180 && $lngMin < 60){
						$latDec = $latDeg + ($latMin/60) + ($latSec/3600);
						$lngDec = $lngDeg + ($lngMin/60) + ($lngSec/3600);
						if($latNS == 'S'){
							$latDec = -$latDec;
						}
						if($lngEW == 'W'){
							$lngDec = -$lngDec;
						}
						$retArr['lat'] = round($latDec,6);
						$retArr['lng'] = round($lngDec,6);
					}
				}
			}
		}
		elseif(preg_match('/\D*(\d{1,2})\D{0,1}\s*(\d{6,7})E\s*(\d{7})N/i',$inStr,$m)){
			$z = $m[1];
			$e = $m[2];
			$n = $m[3];
			$d = '';
			if(preg_match('/NAD\s*27/i',$inStr)) $d = 'NAD27';
			if($n && $e && $z){
				$gPoint = new GPoint($d);
				$gPoint->setUTM($e,$n,$z);
				$gPoint->convertTMtoLL();
				$lat = $gPoint->Lat();
				$lng = $gPoint->Long();
				if($lat && $lng){
					$retArr['lat'] = round($lat,6);
					$retArr['lng'] = round($lng,6);
				}
			}
			
		}
		//Try to parse UTM <Code still to be added>

		return $retArr;
	}
	
	private function parseVerbatimElevation($inStr){
		$retArr = array();
		if(preg_match('/(\d+)\s*-\s*(\d+)\s*meter/i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
			$retArr['maxelev'] = $m[2];
		}
		elseif(preg_match('/(\d+)\s*-\s*(\d+)\s*m./i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
			$retArr['maxelev'] = $m[2];
		}
		elseif(preg_match('/(\d+)\s*-\s*(\d+)\s*m$/i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
			$retArr['maxelev'] = $m[2];
		}
		elseif(preg_match('/(\d+)\s*meter/i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
		}
		elseif(preg_match('/(\d+)\s*m./i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
		}
		elseif(preg_match('/(\d+)\s*m$/i',$inStr,$m)){
			$retArr['minelev'] = $m[1];
		}
		elseif(preg_match('/(\d+)\s*-\s*(\d+)\s*feet/i',$inStr,$m)){
			$retArr['minelev'] = (round($m[1]*.3048));
			$retArr['maxelev'] = (round($m[2]*.3048));
		}
		elseif(preg_match('/(\d+)\s*-\s*(\d+)\s*ft./i',$inStr,$m)){
			$retArr['minelev'] = (round($m[1]*.3048));
			$retArr['maxelev'] = (round($m[2]*.3048));
		}
		elseif(preg_match('/(\d+)\s*-\s*(\d+)\s*ft$/i',$inStr,$m)){
			$retArr['minelev'] = (round($m[1]*.3048));
			$retArr['maxelev'] = (round($m[2]*.3048));
		}
		elseif(preg_match('/(\d+)\s*feet/i',$inStr,$m)){
			$retArr['minelev'] = (round($m[1]*.3048));
		}
		elseif(preg_match('/(\d+)\s*ft./i',$inStr,$m)){
			$retArr['minelev'] = (round($m[1]*.3048));
		}
		elseif(preg_match('/(\d+)\s*ft/i',$inStr,$m)){
			$retArr['minelev'] = (round($m[1]*.3048));
		}
		return $retArr;
	}

	protected function cleanString($inStr){
		$retStr = trim($inStr);
		$retStr = str_replace(chr(10),' ',$retStr);
		$retStr = str_replace(chr(11),' ',$retStr);
		$retStr = str_replace(chr(13),' ',$retStr);
		$retStr = str_replace(chr(20),' ',$retStr);
		$retStr = str_replace(chr(30),' ',$retStr);
		$retStr = $this->conn->real_escape_string($retStr);
		return $retStr;
	}

	protected function encodeString($inStr){
 		global $charset;
 		$retStr = $inStr;
		if(strtolower($charset) == "utf-8" || strtolower($charset) == "utf8"){
			if(mb_detect_encoding($inStr,'ISO-8859-1,UTF-8') == "ISO-8859-1"){
				//$value = utf8_encode($value);
				$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
			}
		}
		elseif(strtolower($charset) == "iso-8859-1"){
			if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
				//$value = utf8_decode($value);
				$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
			}
		}
		return $retStr;
	}
}

?>