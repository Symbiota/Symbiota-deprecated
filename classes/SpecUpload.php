<?php
require_once($serverRoot.'/config/dbconnection.php');

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
	protected $code;
	protected $path;
	protected $pKField;
	protected $schemaName;
	protected $queryStr;
	protected $storedProcedure;
	protected $lastUploadDate;
	protected $uploadType;
	private $securityKey;

	protected $verboseMode = 1;	// 0 = silent, 1 = echo, 2 = log
	private $logFH;
	protected $errorStr;

	protected $DIRECTUPLOAD = 1,$DIGIRUPLOAD = 2, $FILEUPLOAD = 3, $STOREDPROCEDURE = 4, $SCRIPTUPLOAD = 5, $DWCAUPLOAD = 6, $SKELETAL = 7;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	function __destruct(){
 		if($this->conn) $this->conn->close();
		if($this->verboseMode == 2){
			if($this->logFH) fclose($this->logFH);
		}
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
				elseif($uploadType == $this->SKELETAL){
					$uploadStr = "Skeletal File Upload";
				}
				elseif($uploadType == $this->STOREDPROCEDURE){
					$uploadStr = "Stored Procedure";
				}
				elseif($uploadType == $this->DWCAUPLOAD){
					$uploadStr = "Darwin Core Archive Upload";
				}
				$returnArr[$row->uspid]["title"] = $row->title.' ('.$uploadStr.' - #'.$row->uspid.')';
				$returnArr[$row->uspid]["uploadtype"] = $row->uploadtype;
			}
			$result->free();
		}
		return $returnArr;
	}

	private function setCollInfo(){
		if($this->collId){
			$sql = 'SELECT DISTINCT c.collid, c.collectionname, c.institutioncode, c.collectioncode, c.icon, c.managementtype, cs.uploaddate, c.securitykey, c.guidtarget '.
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
				$this->collMetadataArr["securitykey"] = $row->securitykey;
				$this->collMetadataArr["guidtarget"] = $row->guidtarget;
			}
			$result->free();
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

	public function validateSecurityKey($k){
		if(!$this->collId){
			$sql = 'SELECT collid '.
			'FROM omcollections '.
    		'WHERE securitykey = "'.$k.'"';
			//echo $sql;
			$rs = $this->conn->query($sql);
	    	if($r = $rs->fetch_object()){
	    		$this->setCollId($r->collid);
	    	}
	    	else{
	    		return false;
	    	}
			$rs->free();
		}
		elseif(!isset($this->collMetadataArr["securitykey"])){
			$this->setCollInfo();
		}
		if($k == $this->collMetadataArr["securitykey"]){
			return true;
		}
		return false;
	}

	//Upload Review
	public function getUploadMap($start, $limit, $searchVariables = ''){
		$retArr = Array();
		if($limit){
			//CA: Bookmark
			$occFieldArr = array('catalognumber', 'othercatalognumbers', 'occurrenceid','family', 'scientificname', 'sciname',
				'scientificnameauthorship', 'identifiedby', 'dateidentified', 'identificationreferences',
				'identificationremarks', 'taxonremarks', 'identificationqualifier', 'typestatus', 'recordedby', 'recordnumber',
				'associatedcollectors', 'eventdate', 'year', 'month', 'day', 'startdayofyear', 'enddayofyear',
				'verbatimeventdate', 'habitat', 'substrate', 'fieldnumber','occurrenceremarks', 'associatedtaxa', 'verbatimattributes',
				'dynamicproperties', 'reproductivecondition', 'cultivationstatus', 'establishmentmeans',
				'lifestage', 'sex', 'individualcount', 'samplingprotocol', 'preparations',
				'country', 'stateprovince', 'county', 'municipality', 'locality', 'localitysecurity', 'localitysecurityreason',
				'decimallatitude', 'decimallongitude','geodeticdatum', 'coordinateuncertaintyinmeters', 'footprintwkt',
				'locationremarks', 'verbatimcoordinates', 'georeferencedby', 'georeferenceprotocol', 'georeferencesources',
				'georeferenceverificationstatus', 'georeferenceremarks', 'minimumelevationinmeters', 'maximumelevationinmeters',
				'verbatimelevation', 'disposition', 'language', 'duplicatequantity', 'genericcolumn1', 'genericcolumn2',
				'labelproject', 'basisofrecord', 'idCollaboratorIndigenous', 'sexCollaboratorIndigenous', 'dobCollaboratorIndigenous', 'verbatimIndigenous', 'validIndigenous', 'linkLanguageCollaboratorIndigenous', 'familyLanguageCollaboratorIndigenous', 'groupLanguageCollaboratorIndigenous', 'subgroupLanguageCollaboratorIndigenous', 'villageCollaboratorIndigenous', 'municipalityCollaboratorIndigenous', 'stateCollaboratorIndigenous', 'countryCollaboratorIndigenous', 'isoLanguageCollaboratorIndigenous', 'vernacularLexiconIndigenous', 'glossLexiconIndigenous', 'parseLexiconIndigenous', 'parentTaxaLexiconIndigenous', 'siblingTaxaLexiconIndigenous', 'childTaxaLexiconIndigenous', 'otherTaxaUseIndigenous', 'typologyLexiconIndigenous', 'semanticsLexiconIndigenous', 'notesLexiconIndigenous', 'categoryUseIndigenous', 'specificUseIndigenous', 'partUseIndigenous', 'notesUseIndigenous', 'ownerinstitutioncode', 'processingstatus', 'recordenteredby');
			$sql = 'SELECT dbpk, '.implode(',',$occFieldArr).' FROM uploadspectemp '.
				'WHERE collid = '.$this->collId.' ';
			if($searchVariables){
				if($searchVariables == 'matchappend'){
					$sql = 'SELECT DISTINCT u.dbpk, u.'.implode(',u.',$occFieldArr).' '.
						'FROM uploadspectemp u INNER JOIN omoccurrences o ON u.collid = o.collid '.
						'WHERE (u.collid = '.$this->collId.') AND (u.occid IS NULL) AND (u.catalogNumber = o.catalogNumber OR u.othercatalogNumbers = o.othercatalogNumbers) ';
				}
				elseif($searchVariables == 'sync'){
					$sql = 'SELECT DISTINCT u.dbpk, u.'.implode(',u.',$occFieldArr).' '.
						'FROM uploadspectemp u INNER JOIN omoccurrences o ON (u.catalogNumber = o.catalogNumber) AND (u.collid = o.collid) '.
						'WHERE (u.collid = '.$this->collId.') AND (u.occid IS NULL) AND (u.catalogNumber IS NOT NULL) '.
						'AND (o.catalogNumber IS NOT NULL) AND (o.dbpk IS NULL) ';
				}
				elseif($searchVariables == 'exist'){
					$sql = 'SELECT DISTINCT o.dbpk, o.'.implode(',o.',$occFieldArr).' '.
						'FROM omoccurrences o LEFT JOIN uploadspectemp u  ON (o.occid = u.occid) '.
						'WHERE (o.collid = '.$this->collId.') AND (u.occid IS NULL) ';
				}
				elseif($searchVariables == 'dupdbpk'){
					$sql = 'SELECT DISTINCT u.dbpk, u.'.implode(',u.',$occFieldArr).' FROM uploadspectemp u WHERE u.dbpk IN('.
						'SELECT dbpk FROM uploadspectemp '.
						'GROUP BY dbpk, collid, basisofrecord '.
						'HAVING (Count(*)>1) AND (collid = '.$this->collId.')) ';
				}
				else{
					$varArr = explode(';',$searchVariables);
					foreach($varArr as $varStr){
						if(strpos($varStr,':')){
							$vArr = explode(':',$varStr);
							$sql .= 'AND '.$vArr[0];
							switch($vArr[1]){
								case "ISNULL":
									$sql .= ' IS NULL ';
									break;
								case "ISNOTNULL":
									$sql .= ' IS NOT NULL ';
									break;
								default:
									$sql .= ' = "'.$vArr[1].'" ';
							}
						}
					}
				}
			}
			$sql .= 'LIMIT '.$start.','.$limit;
			//echo "<div>".$sql."</div>"; exit;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_assoc()){
				$retArr[] = array_change_key_case($row);
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getUploadCount(){
		$cnt = 0;
		if($this->collId){
			$sql = 'SELECT count(*) AS cnt FROM uploadspectemp WHERE collid = '.$this->collId;
			$rs = $this->conn->query($sql);
			$rs->num_rows;
			$rs->free();
		}
		return $cnt;
	}

	//Profile management
    public function readUploadParameters(){
    	if($this->uspid){
			$sql = 'SELECT usp.collid, usp.title, usp.Platform, usp.server, usp.port, usp.Username, usp.Password, usp.SchemaName, '.
	    		'usp.code, usp.path, usp.pkfield, usp.querystr, usp.cleanupsp, cs.uploaddate, usp.uploadtype '.
				'FROM uploadspecparameters usp LEFT JOIN omcollectionstats cs ON usp.collid = cs.collid '.
	    		'WHERE (usp.uspid = '.$this->uspid.')';
			//echo $sql;
			$result = $this->conn->query($sql);
	    	if($row = $result->fetch_object()){
	    		if(!$this->collId) $this->collId = $row->collid;
	    		$this->title = $row->title;
	    		$this->platform = $row->Platform;
	    		$this->server = $row->server;
	    		$this->port = $row->port;
	    		$this->username = $row->Username;
	    		$this->password = $row->Password;
	    		$this->schemaName = $row->SchemaName;
	    		$this->code = $row->code;
	    		if(!$this->path) $this->path = $row->path;
	    		$this->pKField = strtolower($row->pkfield);
	    		$this->queryStr = $row->querystr;
	    		$this->storedProcedure = $row->cleanupsp;
	    		$this->lastUploadDate = $row->uploaddate;
	    		$this->uploadType = $row->uploadtype;
	    		if(!$this->lastUploadDate) $this->lastUploadDate = date('Y-m-d H:i:s');
	    	}
	    	$result->free();
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
			', code = '.($_REQUEST['code']?'"'.$_REQUEST['code'].'"':'NULL').
			', path = '.($_REQUEST['path']?'"'.$_REQUEST['path'].'"':'NULL').
			', pkfield = '.($_REQUEST['pkfield']?'"'.$_REQUEST['pkfield'].'"':'NULL').
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
		$sql = 'INSERT INTO uploadspecparameters(collid, uploadtype, title, platform, server, port, code, path, '.
			'pkfield, username, password, schemaname, cleanupsp, querystr) VALUES ('.
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

	//Setter and getters
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
	
	public function getCode(){
		return $this->code;
	}
	
	public function getPath(){
		return $this->path;
	}

	public function setPath($p){
		$this->path = $p;
	}

	public function getPKField(){
		return $this->pKField;
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
	
	public function setUploadType($uploadType){
		if(is_numeric($uploadType)){
			$this->uploadType = $uploadType;
		}
	}
	
	public function getErrorStr(){
		return $this->errorStr;
	}
	
	public function setVerboseMode($vMode, $logTitle = ''){
		global $serverRoot;
		if(is_numeric($vMode)){
			$this->verboseMode = $vMode;
			if($this->verboseMode == 2){
				//Create log File
				if($serverRoot){
					$logPath = $serverRoot;
					if(substr($serverRoot,-1) != '/' && substr($serverRoot,-1) != '\\') $logPath .= '/';
					$logPath .= 'temp/logs/';
					if($logTitle){
						$logPath .= $logTitle;
					}
					else{
						$logPath .= 'dataupload';
					}
					$logPath .= '_'.date('Ymd').".log";
					$this->logFH = fopen($logPath, 'a');
					fwrite($this->logFH,"Start time: ".date('Y-m-d h:i:s A')."\n");
				}
			}
		}
	}

	protected function outputMsg($str, $indent = 0){
		if($this->verboseMode == 1){
			echo $str;
		}
		elseif($this->verboseMode == 2){
			if($this->logFH) fwrite($this->logFH,($indent?str_repeat("\t",$indent):'').strip_tags($str)."\n");
		}
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