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
	protected $storedProcedure;
	protected $lastUploadDate;

	protected $transferCount = 0;
	protected $sourceArr = Array();
	protected $fieldMap = Array();
	protected $symbFields = Array();

	private $DIRECTUPLOAD = 1,$DIGIRUPLOAD = 2, $FILEUPLOAD = 3, $STOREDPROCEDURE = 4, $SCRIPTUPLOAD = 5;
	private $monthNames = array('jan'=>'01','ene'=>'01','feb'=>'02','mar'=>'03','abr'=>'04','apr'=>'04',
		'may'=>'05','jun'=>'06','jul'=>'07','ago'=>'08','aug'=>'08','sep'=>'09','oct'=>'10','nov'=>'11','dec'=>'12');

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
    		$this->digirPKField = $row->digirpkfield;
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
		//Run cleanup Stored Procedure, if one exists 
		if($this->storedProcedure){
			try{
				if($this->conn->query('CALL '.$this->storedProcedure)){
					echo '<li style="font-weight:bold;">';
					echo 'Stored procedure executed: '.$this->storedProcedure;
					echo '</li>';
				}
			}
			catch(Exception $e){
				echo '<li style="color:red;">ERROR: Record cleaning failed ('.$this->storedProcedure.')</li>';
			}
		}
		if(!$this->transferCount){
			$sql = "SELECT count(*) AS cnt FROM uploadspectemp WHERE (collid = ".$this->collId.')';
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->transferCount = $row->cnt;
			}
			$rs->close();
		}
		ob_flush();
		flush();

		echo '<li style="font-weight:bold;">Records Upload Complete!</li>';
		echo '<li style="font-weight:bold;">Updating event date fields...';
		ob_flush();
		flush();
		$sql = 'UPDATE uploadspectemp u '.
			'SET u.year = YEAR(u.eventDate) '.
			'WHERE u.collid = '.$this->collId.' AND u.eventDate IS NOT NULL AND (u.year IS NULL OR u.year = 0)';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp u '.
			'SET u.month = MONTH(u.eventDate) '.
			'WHERE u.collid = '.$this->collId.' AND (u.month IS NULL OR u.month = 0) AND u.eventDate IS NOT NULL';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp u '.
			'SET u.day = DAY(u.eventDate) '.
			'WHERE u.collid = '.$this->collId.' AND (u.day IS NULL OR u.day = 0) AND u.eventDate IS NOT NULL';
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

		echo '<li style="font-weight:bold;">Cleaning taxonomy...';
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
			'WHERE sciname like "% '.($taxonRank=='subsp.'?'ssp.':'subsp.').' %"';
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp SET sciname = replace(sciname," var "," var. ") WHERE sciname like "% var %"';
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = replace(sciname," cf. "," "), identificationQualifier = CONCAT_WS("; ","cf.",identificationQualifier), tidinterpreted = null '.
			'WHERE sciname like "% cf. %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = replace(sciname," cf "," "), identificationQualifier = CONCAT_WS("; ","cf.",identificationQualifier), tidinterpreted = null '.
			'WHERE sciname like "% cf %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = REPLACE(sciname," aff. "," "), identificationQualifier = CONCAT_WS("; ","aff.",identificationQualifier), tidinterpreted = null '.
			'WHERE sciname like "% aff. %"';
		$this->conn->query($sql);
		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = REPLACE(sciname," aff "," "), identificationQualifier = CONCAT_WS("; ","aff.",identificationQualifier), tidinterpreted = null '.
			'WHERE sciname like "% aff %"';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = trim(sciname), tidinterpreted = null '.
			'WHERE sciname like "% " OR sciname like " %"';
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = replace(sciname,"   "," ") '.
			'WHERE sciname like "%   %"';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = replace(sciname,"  "," ") '.
			'WHERE sciname like "%  %"';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = replace(sciname," sp.","") '.
			'WHERE sciname like "% sp."';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET sciname = replace(sciname," sp","") '.
			'WHERE sciname like "% sp"';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET specificepithet = NULL '.
			'WHERE specificepithet = "sp." OR specificepithet = "sp"';
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp SET taxonrank = "f." '.
			'WHERE taxonrank IS NULL AND InfraSpecificEpithet IS NOT NULL AND scientificname LIKE "% f. %"';
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp SET taxonrank = "f." '.
			'WHERE taxonrank IS NULL AND InfraSpecificEpithet IS NOT NULL AND scientificname LIKE "% forma %"';
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp SET taxonrank = "var." '.
			'WHERE taxonrank IS NULL AND InfraSpecificEpithet IS NOT NULL AND scientificname LIKE "% var. %"';
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp SET taxonrank = "'.$taxonRank.'" '.
			'WHERE taxonrank IS NULL AND InfraSpecificEpithet IS NOT NULL AND scientificname LIKE "% ssp. %"';
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp SET taxonrank = "'.$taxonRank.'" '.
			'WHERE taxonrank IS NULL AND InfraSpecificEpithet IS NOT NULL AND scientificname LIKE "% subsp. %"';
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp SET taxonrank = "'.$taxonRank.'" '.
			'WHERE taxonrank IS NULL AND InfraSpecificEpithet IS NOT NULL AND scientificname LIKE "% ssp %"';
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp SET taxonrank = "'.$taxonRank.'" '.
			'WHERE taxonrank IS NULL AND InfraSpecificEpithet IS NOT NULL AND scientificname LIKE "% subsp %"';
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp SET sciname = CONCAT_WS(" ",Genus,SpecificEpithet,taxonrank,InfraSpecificEpithet) '.
			'WHERE sciname IS NULL';
		$this->conn->query($sql);
		echo 'Done!</li> ';
		
		echo '<li style="font-weight:bold;">Linking to taxonomic thesaurus...';
		ob_flush();
		flush();

		$sql = 'UPDATE uploadspectemp u INNER JOIN taxa t ON u.sciname = t.sciname '.
			'SET u.TidInterpreted = t.tid WHERE u.TidInterpreted IS NULL';
		$this->conn->query($sql);

		$sql = 'UPDATE taxa t INNER JOIN uploadspectemp u ON t.tid = u.tidinterpreted '.
			'SET u.LocalitySecurity = t.SecurityStatus '.
			'WHERE u.collid = '.$this->collId.' AND (t.SecurityStatus > 0) AND (u.LocalitySecurity = 0 OR u.LocalitySecurity IS NULL)';
		$this->conn->query($sql);
		
		$sql = 'UPDATE uploadspectemp u INNER JOIN taxstatus ts ON u.tidinterpreted = ts.tid '.
			'SET u.family = ts.family '.
			'WHERE ts.taxauthid = 1 AND ts.family <> "" AND ts.family IS NOT NULL AND (u.family IS NULL OR u.family = "")';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp u INNER JOIN taxa t ON u.genus = t.unitname1 '.
			'INNER JOIN taxstatus ts on t.tid = ts.tid '.
			'SET u.family = ts.family '.
			'WHERE t.rankid = 180 and ts.taxauthid = 1 AND ts.family IS NOT NULL AND (u.family IS NULL OR u.family = "")';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp u INNER JOIN taxa t ON u.tidinterpreted = t.tid '.
			'SET u.scientificNameAuthorship = t.author '.
			'WHERE (u.scientificNameAuthorship = "" OR u.scientificNameAuthorship IS NULL) AND t.author IS NOT NULL';
		$this->conn->query($sql);
		echo 'Done!</li> ';

		echo '<li style="font-weight:bold;">Cleaning illegal and errored coordinates...';
		ob_flush();
		flush();
		$sql = 'UPDATE uploadspectemp SET DecimalLongitude = -1*DecimalLongitude '.
			'WHERE DecimalLongitude > 0 AND (Country = "USA" OR Country = "United States" OR Country = "U.S.A." OR Country = "Canada" OR Country = "Mexico")';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET DecimalLatitude = NULL, DecimalLongitude = NULL '.
			'WHERE DecimalLatitude = 0 AND DecimalLongitude = 0';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET verbatimcoordinates = CONCAT_WS(" ",DecimalLatitude, DecimalLongitude), DecimalLatitude = NULL, DecimalLongitude = NULL '.
			'WHERE DecimalLatitude < -90 OR DecimalLatitude > 90';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET verbatimcoordinates = CONCAT_WS(" ",DecimalLatitude, DecimalLongitude), DecimalLatitude = NULL, DecimalLongitude = NULL '.
			'WHERE DecimalLongitude < -180 OR DecimalLongitude > 180';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET verbatimCoordinates = CONCAT_WS("; ",verbatimCoordinates,CONCAT("UTM: ",UtmZoning," ",UtmNorthing,"N ",UtmEasting,"E")) '.
			'WHERE UtmNorthing IS NOT NULL';
		$this->conn->query($sql);
		echo 'Done!</li> ';

		echo '<li style="font-weight:bold;">Linking existing record in preparation for updating... ';
		ob_flush();
		flush();
		$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON (u.dbpk = o.dbpk) AND (u.collid = o.collid) '.
			'SET u.occid = o.occid '.
			'WHERE u.collid = '.$this->collId.' AND u.occid IS NULL';
		$this->conn->query($sql);
		echo 'Done!</li> ';
		
		if($finalTransfer){
			$this->performFinalTransfer();
			echo '<li style="font-weight:bold;">Transfer process Complete!</li>';
		}
		else{
			echo '<li style="font-weight:bold;">Upload Procedure Complete';
			if($this->transferCount) echo ': '.$this->transferCount.' records';
			echo '</li>';
			if($this->transferCount) echo '<li style="font-weight:bold;">Records transferred only to temporary specimen table. Use controls below to transfer to specimen table</li>';
		}
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
			'o.verbatimEventDate = u.verbatimEventDate, o.habitat = u.habitat, o.fieldNotes = u.fieldNotes, o.occurrenceRemarks = u.occurrenceRemarks, o.informationWithheld = u.informationWithheld, '.
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
		
		echo '<li style="font-weight:bold;">Inserting new records into active occurrence table... ';
		ob_flush();
		flush();
		$sql = 'INSERT IGNORE INTO omoccurrences (collid, dbpk, basisOfRecord, occurrenceID, catalogNumber, otherCatalogNumbers, ownerInstitutionCode, family, scientificName, '.
			'sciname, tidinterpreted, genus, institutionID, collectionID, specificEpithet, datasetID, taxonRank, infraspecificEpithet, institutionCode, collectionCode, '.
			'scientificNameAuthorship, taxonRemarks, identifiedBy, dateIdentified, identificationReferences, identificationRemarks, '.
			'identificationQualifier, typeStatus, recordedBy, recordNumber, associatedCollectors, '.
			'eventDate, Year, Month, Day, startDayOfYear, endDayOfYear, verbatimEventDate, habitat, fieldNotes, occurrenceRemarks, informationWithheld, '.
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
			'u.endDayOfYear, u.verbatimEventDate, u.habitat, u.fieldNotes, u.occurrenceRemarks, u.informationWithheld, u.associatedOccurrences, u.associatedTaxa, '.
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
		
		//Update collection stats
		echo '<li style="font-weight:bold;">Updating Collection Statistics</li>';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats SET uploaddate = NOW() WHERE collid = '.$this->collId;
		$this->conn->query($sql);

		echo '<li style="margin-left:10px;">Updating total record count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.recordcnt = (SELECT Count(o.occid) FROM omoccurrences o WHERE (o.collid = '.$this->collId.')) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		echo 'Done!</li> ';
		
		echo '<li style="margin-left:10px;">Updating family count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.familycnt = (SELECT COUNT(DISTINCT o.family) '.
			'FROM omoccurrences o WHERE (o.collid = '.$this->collId.')) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		echo 'Done!</li> ';
		
		echo '<li style="margin-left:10px;">Updating genus count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.genuscnt = (SELECT COUNT(DISTINCT t.unitname1) '.
			'FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '.
			'WHERE (o.collid = '.$this->collId.') AND t.rankid >= 180) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		echo 'Done!</li>';

		echo '<li style="margin-left:10px;">Updating species count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.speciescnt = (SELECT count(DISTINCT t.unitname1, t.unitname2) AS spcnt '.
			'FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '.
			'WHERE (o.collid = '.$this->collId.') AND t.rankid >= 220) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		echo 'Done</li>';
		
		echo '<li style="margin-left:10px;">Updating georeference count... ';
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
		//Date cleaning
		if(array_key_exists('month',$recMap) && !is_numeric($recMap['month'])){
			if(strlen($recMap['month']) > 2){
				$monAbbr = strtolower(substr($recMap['month'],3));
				if($monAbbr == 'jan') $recMap['month'] = '01';
				elseif($monAbbr == 'feb') $recMap['month'] = '02';
				elseif($monAbbr == 'mar') $recMap['month'] = '03';
				elseif($monAbbr == 'apr') $recMap['month'] = '04';
				elseif($monAbbr == 'may') $recMap['month'] = '05';
				elseif($monAbbr == 'jun') $recMap['month'] = '06';
				elseif($monAbbr == 'jul') $recMap['month'] = '07';
				elseif($monAbbr == 'aug') $recMap['month'] = '08';
				elseif($monAbbr == 'sep') $recMap['month'] = '09';
				elseif($monAbbr == 'oct') $recMap['month'] = '10';
				elseif($monAbbr == 'nov') $recMap['month'] = '11';
				elseif($monAbbr == 'dec') $recMap['month'] = '12';
			}
		}
		if(!array_key_exists('eventdate',$recMap) || !$recMap['eventdate']){
			if(array_key_exists('year',$recMap) && $recMap['year'] && is_numeric($recMap['year']) && strlen($recMap['year'])==4){
				$y = $recMap['year'];
				$m = "00";
				$d = "00";
				if(array_key_exists('month',$recMap) && $recMap['month'] && is_numeric($recMap['month'])){
					$m = $recMap['month'];
					if(strlen($m) == 1) $m = '0'.$m;
					if(array_key_exists('day',$recMap) && $recMap['day'] && is_numeric($recMap['day'])){
						$d = $recMap['day'];
						if(strlen($d) == 1) $d = '0'.$d;
					}
				}
				$recMap['eventdate'] = $y.'-'.$m.'-'.$d;
			}
			elseif(array_key_exists('verbatimeventdate',$recMap) && $recMap['verbatimeventdate']){
				$dateStr = $this->formatDate($recMap['verbatimeventdate']);
				if($dateStr) $recMap['eventdate'] = $dateStr;
			}
		}
		
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
	
	public function formatDate($dateStr){
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
		elseif(preg_match('/^(\d{1,2})\s{1}(\D{3,})\.*\s{1}(\d{2,4})/',$dateStr,$match)){
			//Format: dd mmm yyyy, d mmm yy
			$d = $match[1];
			$mStr = $match[2];
			$y = $match[3];
			$mStr = strtolower(substr($mStr,0,3));
			$m = $this->monthNames[$mStr];
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
			$m = $this->monthNames[$mStr];
			$y = $match[2];
		}
		elseif(preg_match('/([1,2]{1}[0,5-9]{1}\d{2})/',$dateStr,$match)){
			//Format: yyyy
			$y = $match[1];
		}
		if($y){
			if(strlen($y) == 2){ 
				if($y < 20) $y = '20'.$y;
				else $y = '19'.$y;
			}
			if(strlen($m) == 1) $m = '0'.$m;
			if(strlen($d) == 1) $d = '0'.$d;
			$dateStr = $y.'-'.$m.'-'.$d;
		}
		if($t){
			$dateStr .= ' '.$t;
		}
		return $dateStr;
	}

	protected function cleanString($inStr){
		$retStr = trim($inStr);
		$retStr = str_replace('"',"'",$retStr);
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