<?php
include_once($serverRoot.'/classes/SpecUpload.php');
include_once($serverRoot.'/classes/OccurrenceUtilities.php');
include_once($serverRoot.'/classes/ImageCleaner.php');
include_once($serverRoot.'/classes/UuidFactory.php');

class SpecUploadBase extends SpecUpload{

	protected $transferCount = 0;
	protected $identTransferCount = 0;
	protected $imageTransferCount = 0;
	protected $includeIdentificationHistory = true;
	protected $includeImages = true;
	private $matchCatalogNumber = 1;
	protected $uploadTargetPath;

	protected $sourceArr = Array();
	protected $identSourceArr = Array();
	protected $imageSourceArr = Array();
	protected $fieldMap = Array();
	protected $identFieldMap = Array();
	protected $imageFieldMap = Array();
	protected $symbFields = Array();
	protected $identSymbFields = Array();
	protected $imageSymbFields = Array();

	private $translationMap = array('accession'=>'catalognumber','accessionid'=>'catalognumber','accessionnumber'=>'catalognumber',
		'taxonfamilyname'=>'family','scientificname'=>'sciname','species'=>'specificepithet',
		'collector'=>'recordedby','primarycollector'=>'recordedby','field:collector'=>'recordedby',
		'collectornumber'=>'recordnumber','collectionnumber'=>'recordnumber','field:collectorfieldnumber'=>'recordnumber',
		'datecollected'=>'eventdate','date'=>'eventdate','collectiondate'=>'eventdate','observedon'=>'eventdate',
		'cf' => 'identificationqualifier','detby'=>'identifiedby','determinor'=>'identifiedby','determinationdate'=>'dateidentified',
		'placestatename'=>'stateprovince','state'=>'stateprovince','placecountyname'=>'county','field:localitydescription'=>'locality', 
		'latitude'=>'verbatimlatitude','longitude'=>'verbatimlongitude','field:associatedspecies'=>'associatedtaxa',
		'specimennotes'=>'occurrenceremarks','notes'=>'occurrenceremarks','generalnotes'=>'occurrenceremarks',
		'plantdescription'=>'verbatimattributes','description'=>'verbatimattributes','field:habitat'=>'habitat');
	private $identTranslationMap = array('scientificname'=>'sciname','detby'=>'identifiedby','determinor'=>'identifiedby',
		'determinationdate'=>'dateidentified','notes'=>'identificationremarks','cf' => 'identificationqualifier');
	private $imageTranslationMap = array('accessuri'=>'originalurl','identifier'=>'originalurl','creator'=>'owner');

	function __construct() {
		parent::__construct();
	}

	function __destruct(){
 		parent::__destruct();
	}

	public function setFieldMap($fm){
		$this->fieldMap = $fm;
	}

	public function getFieldMap(){
		return $this->fieldMap;
	}

	public function setIdentFieldMap($fm){
		$this->identFieldMap = $fm;
	}

	public function setImageFieldMap($fm){
		$this->imageFieldMap = $fm;
	}

	public function getDbpk(){
		$dbpk = '';
		if(array_key_exists('dbpk',$this->fieldMap)){
			$dbpk = $this->fieldMap['dbpk']['field'];
		}
		return $dbpk;
	}

	public function loadFieldMap($autoBuildFieldMap = false){
		if($this->uploadType == $this->DIGIRUPLOAD) $autoBuildFieldMap = true;
		//Get Field Map for $fieldMap
		if($this->uspid && !$this->fieldMap && $this->uploadType != $this->DIGIRUPLOAD && $this->uploadType != $this->STOREDPROCEDURE){
			$sql = 'SELECT usm.sourcefield, usm.symbspecfield FROM uploadspecmap usm '.
				'WHERE (usm.uspid = '.$this->uspid.')';
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$sourceField = $row->sourcefield;
				$symbField = $row->symbspecfield;
				if(substr($symbField,0,3) == 'id_'){
					$this->identFieldMap[substr($symbField,3)]["field"] = $sourceField;
				}
				elseif(substr($symbField,0,3) == 'im_'){
					$this->imageFieldMap[substr($symbField,3)]["field"] = $sourceField;
				}
				else{
					$this->fieldMap[$symbField]["field"] = $sourceField;
				}
			}
			$rs->free();
		}

		//Get uploadspectemp metadata
		$skipOccurFields = array('dbpk','initialtimestamp','occid','collid','tidinterpreted');
		$sql = "SHOW COLUMNS FROM uploadspectemp";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$field = strtolower($row->Field);
			if(!in_array($field,$skipOccurFields)){
				if($autoBuildFieldMap){
					$this->fieldMap[$field]["field"] = $field;
				}
				$type = $row->Type;
				$this->symbFields[] = $field;
				if(array_key_exists($field,$this->fieldMap)){
					if(strpos($type,"double") !== false || strpos($type,"int") !== false){
						$this->fieldMap[$field]["type"] = "numeric";
					}
					elseif(strpos($type,"decimal") !== false){
						$this->fieldMap[$field]["type"] = "decimal";
						if(preg_match('/\((.*)\)$/', $type, $matches)){
							$this->fieldMap[$field]["size"] = $matches[1];
						}
					}
					elseif(strpos($type,"date") !== false){
						$this->fieldMap[$field]["type"] = "date";
					}
					else{
						$this->fieldMap[$field]["type"] = "string";
						if(preg_match('/\((\d+)\)$/', $type, $matches)){
							$this->fieldMap[$field]["size"] = substr($matches[0],1,strlen($matches[0])-2);
						}
					}
				}
			}
		}
		$rs->free();

//		if($autoBuildFieldMap){
//			if($this->uploadType == $this->DWCAUPLOAD){
//				$this->fieldMap['dbpk']['field'] = 'id';
//			}
//			elseif($this->uploadType == $this->DIGIRUPLOAD && $this->pKField){
//				$this->fieldMap['dbpk']['field'] = $recMap[$this->pKField];
//			}
//		}
		
		if($this->uploadType == $this->FILEUPLOAD || $this->uploadType == $this->DWCAUPLOAD || $this->uploadType == $this->DIRECTUPLOAD){
			//Get identification metadata
			$skipDetFields = array('detid','occid','tidinterpreted','idbyid','appliedstatus','iscurrent','sortsequence','sourceidentifier','initialtimestamp');

			$rs = $this->conn->query('SHOW COLUMNS FROM omoccurdeterminations');
			while($r = $rs->fetch_object()){
				$field = strtolower($r->Field);
				if(!in_array($field,$skipDetFields)){
					if($autoBuildFieldMap){
						$this->identFieldMap[$field]["field"] = $field;
					}
					$type = $r->Type;
					$this->identSymbFields[] = $field;
					if(array_key_exists($field,$this->identFieldMap)){
						if(strpos($type,"double") !== false || strpos($type,"int") !== false || strpos($type,"decimal") !== false){
							$this->identFieldMap[$field]["type"] = "numeric";
						}
						elseif(strpos($type,"date") !== false){
							$this->identFieldMap[$field]["type"] = "date";
						}
						else{
							$this->identFieldMap[$field]["type"] = "string";
							if(preg_match('/\(\d+\)$/', $type, $matches)){
								$this->identFieldMap[$field]["size"] = substr($matches[0],1,strlen($matches[0])-2);
							}
						}
					}
				}
			}
			$rs->free();
			
			$this->identSymbFields[] = 'genus';
			$this->identSymbFields[] = 'specificepithet';
			$this->identSymbFields[] = 'taxonrank';
			$this->identSymbFields[] = 'infraspecificepithet';
			$this->identSymbFields[] = 'coreid';
			//$this->identFieldMap['genus']['type'] = 'string';
			//$this->identFieldMap['specificepithet']['type'] = 'string';
			//$this->identFieldMap['taxonrank']['type'] = 'string';
			//$this->identFieldMap['infraspecificepithet']['type'] = 'string';
			//$this->identFieldMap['coreid']['type'] = 'string';

			//Get image metadata
			$skipImageFields = array('tid','photographeruid','imagetype','occid','dbpk','collid','username','sortsequence','initialtimestamp');
			$rs = $this->conn->query('SHOW COLUMNS FROM uploadimagetemp');
			while($r = $rs->fetch_object()){
				$field = strtolower($r->Field);
				if(!in_array($field,$skipImageFields)){
					if($autoBuildFieldMap){
						$this->imageFieldMap[$field]["field"] = $field;
					}
					$type = $r->Type;
					$this->imageSymbFields[] = $field;
					if(array_key_exists($field,$this->imageFieldMap)){
						if(strpos($type,"double") !== false || strpos($type,"int") !== false || strpos($type,"decimal") !== false){
							$this->imageFieldMap[$field]["type"] = "numeric";
						}
						elseif(strpos($type,"date") !== false){
							$this->imageFieldMap[$field]["type"] = "date";
						}
						else{
							$this->imageFieldMap[$field]["type"] = "string";
							if(preg_match('/\(\d+\)$/', $type, $matches)){
								$this->imageFieldMap[$field]["size"] = substr($matches[0],1,strlen($matches[0])-2);
							}
						}
					}
				}
			}
			$rs->free();
		}
	}

	public function echoFieldMapTable($autoMap, $mode){
		$prefix = '';
		$fieldMap = array();
		$symbFields = array();
		$sourceArr = array();
		$translationMap = array();
		if($mode == 'ident'){
			$prefix = 'ID-';
			$fieldMap = $this->identFieldMap;
			$symbFields = $this->identSymbFields;
			$sourceArr = $this->identSourceArr;
			$translationMap = $this->identTranslationMap;
		}
		elseif($mode == 'image'){
			$prefix = 'IM-';
			$fieldMap = $this->imageFieldMap;
			$symbFields = $this->imageSymbFields;
			$sourceArr = $this->imageSourceArr;
			$translationMap = $this->imageTranslationMap;
		}
		else{
			$fieldMap = $this->fieldMap;
			$symbFields = $this->symbFields;
			$sourceArr = $this->sourceArr;
			$translationMap = $this->translationMap;
		}
		
		//Build a Source => Symbiota field Map
		$sourceSymbArr = Array();
		foreach($fieldMap as $symbField => $fArr){
			if($symbField != 'dbpk') $sourceSymbArr[$fArr["field"]] = $symbField;
		}

		//Output table rows for source data
		echo '<table class="styledtable">';
		echo '<tr><th>Source Field</th><th>Target Field</th></tr>'."\n";
		sort($symbFields);
		$autoMapArr = Array();
		foreach($sourceArr as $fieldName){
			if($fieldName != 'coreid'){
				$diplayFieldName = $fieldName;
				$fieldName = strtolower($fieldName);
				$isAutoMapped = false;
				$tranlatedFieldName = str_replace(array('_',' ','.'),'',$fieldName);
				if($autoMap){
					if(array_key_exists($tranlatedFieldName,$translationMap)) $tranlatedFieldName = strtolower($translationMap[$tranlatedFieldName]);
					if(in_array($tranlatedFieldName,$symbFields)){
						$isAutoMapped = true;
						$autoMapArr[$tranlatedFieldName] = $fieldName;
					}
				}
				echo "<tr>\n";
				echo "<td style='padding:2px;'>";
				echo $diplayFieldName;
				echo "<input type='hidden' name='".$prefix."sf[]' value='".$fieldName."' />";
				echo "</td>\n";
				echo "<td>\n";
				echo "<select name='".$prefix."tf[]' style='background:".(!array_key_exists($fieldName,$sourceSymbArr)&&!$isAutoMapped?"yellow":"")."'>";
				echo "<option value=''>Select Target Field</option>\n";
				echo "<option value='unmapped'".(isset($sourceSymbArr[$fieldName]) && substr($sourceSymbArr[$fieldName],0,8)=='unmapped'?"SELECTED":"").">Leave Field Unmapped</option>\n";
				echo "<option value=''>-------------------------</option>\n";
				if(array_key_exists($fieldName,$sourceSymbArr)){
					//Source Field is mapped to Symbiota Field
					foreach($symbFields as $sField){
						echo "<option ".($sourceSymbArr[$fieldName]==$sField?"SELECTED":"").">".$sField."</option>\n";
					}
				}
				elseif($isAutoMapped){
					//Source Field = Symbiota Field
					foreach($symbFields as $sField){
						echo "<option ".($tranlatedFieldName==$sField?"SELECTED":"").">".$sField."</option>\n";
					}
				}
				else{
					foreach($symbFields as $sField){
						echo "<option>".$sField."</option>\n";
					}
				}
				echo "</select></td>\n";
				echo "</tr>\n";
			}
		}
		echo '</table>';

		/*
		if($autoMapArr && $this->uspid){
			//Save mapped automap fields
			$sqlInsert = "INSERT INTO uploadspecmap(uspid,symbspecfield,sourcefield) ";
			$sqlValues = "VALUES (".$this->uspid;
			foreach($autoMapArr as $k => $v){
				$sql = $sqlInsert.$sqlValues.",'".$k."','".$v."')";
				//echo $sql;
				$this->conn->query($sql);
			}
		}
		*/
	}

	public function savePrimaryKey($dbpk){
		if($this->uspid){
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
	}

	public function saveFieldMap(){
		$statusStr = '';
		if($this->uspid){
			$this->deleteFieldMap();
			$sqlInsert = "INSERT INTO uploadspecmap(uspid,symbspecfield,sourcefield) ";
			$sqlValues = "VALUES (".$this->uspid;
			foreach($this->fieldMap as $k => $v){
				if($k != "dbpk"){
					$sourceField = $v["field"];
					$sql = $sqlInsert.$sqlValues.",'".$k."','".$sourceField."')";
					//echo "<div>".$sql."</div>";
					if(!$this->conn->query($sql)){
						$statusStr = 'ERROR saving field map: '.$this->conn->error;
					}
				}
			}
			//Save identification field map
			foreach($this->identFieldMap as $k => $v){
				$sourceField = $v["field"];
				$sql = $sqlInsert.$sqlValues.",'ID-".$k."','".$sourceField."')";
				//echo "<div>".$sql."</div>";
				if(!$this->conn->query($sql)){
					$statusStr = 'ERROR saving identification field map: '.$this->conn->error;
				}
			}

			//Save image field map
			foreach($this->imageFieldMap as $k => $v){
				$sourceField = $v["field"];
				$sql = $sqlInsert.$sqlValues.",'IM-".$k."','".$sourceField."')";
				//echo "<div>".$sql."</div>";
				if(!$this->conn->query($sql)){
					$statusStr = 'ERROR saving image field map: '.$this->conn->error;
				}
			}

		}
		return $statusStr;
	}

	public function deleteFieldMap(){
		$statusStr = '';
		if($this->uspid){
			$sql = "DELETE FROM uploadspecmap WHERE (uspid = ".$this->uspid.") AND symbspecfield <> 'dbpk' ";
			//echo "<div>$sql</div>";
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR deleting field map: '.$this->conn->error;
			}
		}
		return $statusStr;
	}

 	public function analyzeUpload(){
 		return true;
 	}

 	public function uploadData($finalTransfer){
 		//Stored Procedure upload; other upload types are controlled by their specific class functions
		set_time_limit(7200);

	 	//First, delete all records in uploadspectemp table associated with this collection
		$sqlDel = "DELETE FROM uploadspectemp WHERE (collid = ".$this->collId.')';
		$this->conn->query($sqlDel);

	 	if($this->uploadType == $this->STOREDPROCEDURE){
			$this->cleanUpload();
 		}
 		elseif($this->uploadType == $this->SCRIPTUPLOAD){
 			if(system($this->queryStr)){
				$this->outputMsg('<li>Script Upload successful.</li>');
				$this->outputMsg('<li>Initializing final transfer steps...</li>');
				$this->cleanUpload();
			}
		}
		ob_flush();
		flush();
		if($finalTransfer){
			$this->finalTransfer();
		}
	}

	protected function cleanUpload(){
		//Run custom cleaning Stored Procedure, if one exists
		$this->outputMsg('<li>Complete: '.$this->getTransferCount().' records loaded</li>');
		ob_flush();
		flush();

		if($this->collMetadataArr["managementtype"] == 'Snapshot' || $this->collMetadataArr["managementtype"] == 'Aggregate'){
			//If collection is a snapshot, map upload to existing records. These records will be updated rather than appended
			$this->outputMsg('<li>Linking records (e.g. matching Primary Identifier)... ');
			ob_flush();
			flush();
			$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON (u.dbpk = o.dbpk) AND (u.collid = o.collid) '.
				'SET u.occid = o.occid '.
				'WHERE u.collid = '.$this->collId.' AND u.occid IS NULL AND (u.dbpk IS NOT NULL) AND (o.dbpk IS NOT NULL)';
			$this->conn->query($sql);
			$this->outputMsg('Done!</li> ');
		}
		
		if($this->storedProcedure){
			try{
				if($this->conn->query('CALL '.$this->storedProcedure)){
					$this->outputMsg('<li style="margin-left:10px;">');
					$this->outputMsg('Stored procedure executed: '.$this->storedProcedure);
					$this->outputMsg('</li>');
				}
			}
			catch(Exception $e){
				$this->outputMsg('<li style="color:red;margin-left:10px;">ERROR: Record cleaning via custom stroed procedure failed ('.$this->storedProcedure.')</li>');
			}
			ob_flush();
			flush();
		}
		
 		//Prefrom general cleaning and parsing tasks
		$this->recordCleaningStage1();
		
		if($this->collMetadataArr["managementtype"] == 'Live Data' && $this->matchCatalogNumber){
			//Match records based on Catalog Number 
			$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON (u.catalogNumber = o.catalogNumber) AND (u.collid = o.collid) '.
				'SET u.occid = o.occid '.
				'WHERE (u.collid = '.$this->collId.') AND (u.occid IS NULL) AND (u.catalogNumber IS NOT NULL) AND (o.catalogNumber IS NOT NULL) ';
			$this->conn->query($sql);
		}

		//Reset $treansferCnt so that count is accurate since some records may have been deleted due to data integrety issues
		$this->getTransferCount(1); 
	}

	private function recordCleaningStage1(){
		$this->outputMsg('<li>Data cleaning:</li>');
		$this->outputMsg('<li style="margin-left:10px;">Cleaning event dates...');
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
		
		$sql = 'UPDATE IGNORE uploadspectemp u '.
			'SET u.eventDate = CONCAT_WS("-",LPAD(u.year,4,"19"),IFNULL(LPAD(u.month,2,"0"),"00"),IFNULL(LPAD(u.day,2,"0"),"00")) '.
			'WHERE u.eventDate IS NULL AND u.year > 1300 AND u.year < 2020 AND collid = '.$this->collId;
		$this->conn->query($sql);
		$this->outputMsg('Done!</li> ');
		ob_flush();
		flush();
		
		$this->outputMsg('<li style="margin-left:10px;">Cleaning country and state/province ...');
		ob_flush();
		flush();
		//Convert country abbreviations to full spellings
		$sql = 'UPDATE uploadspectemp u INNER JOIN lkupcountry c ON u.country = c.iso3 '.
			'SET u.country = c.countryName '.
			'WHERE u.collid = '.$this->collId;
		$this->conn->query($sql);
		$sql = 'UPDATE uploadspectemp u INNER JOIN lkupcountry c ON u.country = c.iso '.
			'SET u.country = c.countryName '.
			'WHERE u.collid = '.$this->collId;
		$this->conn->query($sql);

		//Convert state abbreviations to full spellings
		$sql = 'UPDATE uploadspectemp u INNER JOIN lkupstateprovince s ON u.stateProvince = s.abbrev '.
			'SET u.stateProvince = s.stateName '.
			'WHERE u.collid = '.$this->collId;
		$this->conn->query($sql);

		//Fill null country with state matches 
		$sql = 'UPDATE uploadspectemp u INNER JOIN lkupstateprovince s ON u.stateprovince = s.statename '.
			'INNER JOIN lkupcountry c ON s.countryid = c.countryid '.
			'SET u.country = c.countryName '.
			'WHERE u.country IS NULL AND c.countryname = "United States" AND u.collid = '.$this->collId;
		$this->conn->query($sql);
		$sql = 'UPDATE uploadspectemp u INNER JOIN lkupstateprovince s ON u.stateprovince = s.statename '.
			'INNER JOIN lkupcountry c ON s.countryid = c.countryid '.
			'SET u.country = c.countryName '.
			'WHERE u.country IS NULL AND u.collid = '.$this->collId;
		$this->conn->query($sql);
		$this->outputMsg('Done!</li> ');

		$this->outputMsg('<li style="margin-left:10px;">Cleaning coordinates...');
		ob_flush();
		flush();
		$sql = 'UPDATE uploadspectemp '.
			'SET DecimalLongitude = -1*DecimalLongitude '.
			'WHERE DecimalLongitude > 0 AND (Country = "USA" OR Country = "United States" OR Country = "U.S.A." OR Country = "Canada" OR Country = "Mexico") AND collid = '.$this->collId;
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET DecimalLatitude = NULL, DecimalLongitude = NULL '.
			'WHERE DecimalLatitude = 0 AND DecimalLongitude = 0 AND collid = '.$this->collId;
		$this->conn->query($sql);

		//Move illegal coordinates to verbatim
		$sql = 'UPDATE uploadspectemp '.
			'SET verbatimcoordinates = CONCAT_WS(" ",DecimalLatitude, DecimalLongitude) '.
			'WHERE verbatimcoordinates IS NULL AND collid = '.$this->collId.
			' AND (DecimalLatitude < -90 OR DecimalLatitude > 90 OR DecimalLongitude < -180 OR DecimalLongitude > 180)';
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET DecimalLatitude = NULL, DecimalLongitude = NULL '.
			'WHERE collid = '.$this->collId.' AND (DecimalLatitude < -90 OR DecimalLatitude > 90 OR DecimalLongitude < -180 OR DecimalLongitude > 180)';
		$this->conn->query($sql);
		$this->outputMsg('Done!</li> ');
		
	}
	
	public function getTransferReport(){
		$reportArr = array();
		$reportArr['occur'] = $this->getTransferCount();
		if($this->uploadType == $this->DWCAUPLOAD){
			if($this->includeIdentificationHistory) $reportArr['ident'] = $this->getIdentTransferCount();
			if($this->includeImages) $reportArr['image'] = $this->getImageTransferCount();
		}

		//Number of new records
		$sql = 'SELECT count(*) AS cnt '.
			'FROM uploadspectemp '.
			'WHERE (occid IS NULL) AND (collid = '.$this->collId.')';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$reportArr['new'] = $r->cnt;
		}
		$rs->free();

		//Number of matching records that will be updated
		$sql = 'SELECT count(*) AS cnt '.
			'FROM uploadspectemp '.
			'WHERE (occid IS NOT NULL) AND (collid = '.$this->collId.')';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$reportArr['update'] = $r->cnt;
		}
		$rs->free();

		if($this->collMetadataArr["managementtype"] == 'Live Data' && !$this->matchCatalogNumber){
			//Records that can be matched on on Catalog Number, but will be appended 
			$sql = 'SELECT count(o.occid) AS cnt '.
				'FROM uploadspectemp u INNER JOIN omoccurrences o ON (u.catalogNumber = o.catalogNumber) AND (u.collid = o.collid) '.
				'WHERE (u.collid = '.$this->collId.') AND (u.occid IS NULL) AND (u.catalogNumber IS NOT NULL) AND (o.catalogNumber IS NOT NULL)';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$reportArr['matchappend'] = $r->cnt;
			}
			$rs->free();
		}

		if($this->collMetadataArr["managementtype"] == 'Snapshot'){
			//Match records that were processed via the portal, walked back to collection's central database, and come back to portal 
			$sql = 'SELECT count(o.occid) AS cnt '.
				'FROM uploadspectemp u INNER JOIN omoccurrences o ON (u.catalogNumber = o.catalogNumber) AND (u.collid = o.collid) '.
				'WHERE (u.collid = '.$this->collId.') AND (u.occid IS NULL) AND (u.catalogNumber IS NOT NULL) AND (o.catalogNumber IS NOT NULL) AND (o.dbpk IS NULL)';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$reportArr['sync'] = $r->cnt;
			}
			$rs->free();

			//Records in already in portal that don't match with an incoming record 
			$sql = 'SELECT count(o.occid) AS cnt '.
				'FROM omoccurrences o LEFT JOIN uploadspectemp u  ON (o.occid = u.occid) '.
				'WHERE (o.collid = '.$this->collId.') AND (u.occid IS NULL)';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$reportArr['exist'] = $r->cnt;
			}
			$rs->free();
		}

		if($this->collMetadataArr["managementtype"] == 'Snapshot' || $this->collMetadataArr["managementtype"] == 'Aggregate'){
			//Look for null dbpk
			$sql = 'SELECT count(*) AS cnt FROM uploadspectemp WHERE dbpk IS NULL AND collid = '.$this->collId;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$reportArr['nulldbpk'] = $r->cnt;
			}
			$rs->free();

			//Look for duplicate dbpk
			$sql = 'SELECT dbpk FROM uploadspectemp GROUP BY dbpk, collid HAVING (Count(*)>1) AND (collid = '.$this->collId.')';
			$rs = $this->conn->query($sql);
			$reportArr['dupdbpk'] = $rs->num_rows;
			$rs->free();
		}
		return $reportArr;
	}

	public function finalTransfer(){
		$this->recordCleaningStage2();
		$this->transferOccurrences();
		$this->transferAssociatedMedia();
		$this->transferIdentificationHistory();
		$this->transferImages();
		$this->finalCleanup();
		$this->outputMsg('<li style="">Upload Procedure Complete ('.date('Y-m-d h:i:s A').')!</li>');
	} 
	
	private function recordCleaningStage2(){
		if($this->collMetadataArr["managementtype"] == 'Snapshot'){
			//Match records that were processed via the portal, walked back to collection's central database, and come back to portal 
			$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON (u.catalogNumber = o.catalogNumber) AND (u.collid = o.collid) '.
				'SET u.occid = o.occid, o.dbpk = u.dbpk '.
				'WHERE (u.collid = '.$this->collId.') AND (u.occid IS NULL) AND (u.catalogNumber IS NOT NULL) AND (o.catalogNumber IS NOT NULL) AND (o.dbpk IS NULL) ';
			$this->conn->query($sql);
		}
		
		if($this->collMetadataArr["managementtype"] == 'Snapshot' || $this->collMetadataArr["managementtype"] == 'Aggregate'){
			$this->outputMsg('<li>Starting Stage 2 cleaning</li>');
			$this->outputMsg('<li style="margin-left:10px;">Remove NULL dbpk values... ');
			ob_flush();
			flush();
			$sql = 'DELETE FROM uploadspectemp WHERE dbpk IS NULL AND collid = '.$this->collId;
			$this->conn->query($sql);
			$this->outputMsg('Done!</li> ');
			
			$this->outputMsg('<li style="margin-left:10px;">Remove duplicate dbpk values... ');
			ob_flush();
			flush();
			$sql = 'DELETE u.* '.
				'FROM uploadspectemp u INNER JOIN (SELECT dbpk FROM uploadspectemp GROUP BY dbpk, collid HAVING Count(*)>1 AND collid = '.$this->collId.') t2 ON u.dbpk = t2.dbpk '.
				'WHERE collid = '.$this->collId;
			if($this->conn->query($sql)){
				$this->outputMsg('Done! ');
			}
			else{
				$this->outputMsg('<span style="color:red;">ERROR</span> ('.$this->conn->error.')');
			}
			$this->outputMsg('</li>');
		}
	}

	protected function transferOccurrences(){
		//Clean and Transfer records from uploadspectemp to specimens
		$this->outputMsg('<li>Updating existing records... ');
		ob_flush();
		flush();
		$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON u.occid = o.occid '.
			'SET o.basisOfRecord = u.basisOfRecord, o.catalogNumber = u.catalogNumber, o.occurrenceid = u.occurrenceid, '.
			'o.otherCatalogNumbers = u.otherCatalogNumbers, o.ownerInstitutionCode = u.ownerInstitutionCode, o.family = u.family, '.
			'o.scientificName = u.scientificName, o.sciname = u.sciname, o.tidinterpreted = u.tidinterpreted, o.genus = u.genus, o.institutionID = u.institutionID, '.
			'o.collectionID = u.collectionID, o.specificEpithet = u.specificEpithet, o.datasetID = u.datasetID, o.taxonRank = u.taxonRank, '.
			'o.infraspecificEpithet = u.infraspecificEpithet, o.institutionCode = u.institutionCode, o.collectionCode = u.collectionCode, '.
			'o.scientificNameAuthorship = u.scientificNameAuthorship, o.taxonRemarks = u.taxonRemarks, o.identifiedBy = u.identifiedBy, '.
			'o.dateIdentified = u.dateIdentified, o.identificationReferences = u.identificationReferences, '.
			'o.identificationRemarks = u.identificationRemarks, o.identificationQualifier = u.identificationQualifier, o.typeStatus = u.typeStatus, '.
			'o.recordedBy = u.recordedBy, o.recordNumber = u.recordNumber, o.fieldnumber = u.fieldnumber, '.
			'o.associatedCollectors = u.associatedCollectors, o.eventDate = u.eventDate, '.
			'o.year = u.year, o.month = u.month, o.day = u.day, o.startDayOfYear = u.startDayOfYear, o.endDayOfYear = u.endDayOfYear, '.
			'o.verbatimEventDate = u.verbatimEventDate, o.habitat = u.habitat, o.substrate = u.substrate, o.fieldNotes = u.fieldNotes, o.occurrenceRemarks = u.occurrenceRemarks, o.informationWithheld = u.informationWithheld, '.
			'o.associatedOccurrences = u.associatedOccurrences, o.associatedTaxa = u.associatedTaxa, '.
			'o.dynamicProperties = u.dynamicProperties, o.verbatimAttributes = u.verbatimAttributes, '.
			'o.reproductiveCondition = u.reproductiveCondition, o.cultivationStatus = u.cultivationStatus, '.
			'o.establishmentMeans = u.establishmentMeans, o.lifestage = u.lifestage, o.sex = u.sex, o.individualcount = u.individualcount, '.
			'o.samplingprotocol = u.samplingprotocol, o.preparations = u.preparations, '.
			'o.country = u.country, o.stateProvince = u.stateProvince, o.county = u.county, o.municipality = u.municipality, o.locality = u.locality, '.
			'o.localitySecurity = u.localitySecurity, o.localitySecurityReason = u.localitySecurityReason, o.decimalLatitude = u.decimalLatitude, o.decimalLongitude = u.decimalLongitude, '.
			'o.geodeticDatum = u.geodeticDatum, o.coordinateUncertaintyInMeters = u.coordinateUncertaintyInMeters, o.footprintWKT = u.footprintWKT, '.
			'o.coordinatePrecision = u.coordinatePrecision, o.locationRemarks = u.locationRemarks, o.verbatimCoordinates = u.verbatimCoordinates, '.
			'o.verbatimCoordinateSystem = u.verbatimCoordinateSystem, o.georeferencedBy = u.georeferencedBy, o.georeferenceProtocol = u.georeferenceProtocol, '.
			'o.georeferenceSources = u.georeferenceSources, o.georeferenceVerificationStatus = u.georeferenceVerificationStatus, '.
			'o.georeferenceRemarks = u.georeferenceRemarks, o.minimumElevationInMeters = u.minimumElevationInMeters, '.
			'o.maximumElevationInMeters = u.maximumElevationInMeters, o.verbatimElevation = u.verbatimElevation, '.
			'o.previousIdentifications = u.previousIdentifications, o.disposition = u.disposition, o.modified = u.modified, '.
			'o.language = u.language, o.recordEnteredBy = u.recordEnteredBy, o.labelProject = u.labelProject, o.duplicateQuantity = u.duplicateQuantity '.
			'WHERE u.collid = '.$this->collId.' AND (u.basisofrecord IS NULL OR u.basisofrecord != "determinationHistory")';
		if($this->conn->query($sql)){
			$this->outputMsg('Done!</li> ');
		}
		else{
			$this->outputMsg('FAILED! ERROR: '.$this->conn->error.'</li> ');
		}
		
		$this->outputMsg('<li>Transferring new records... ');
		ob_flush();
		flush();
		$sql = 'INSERT IGNORE INTO omoccurrences (collid, dbpk, basisOfRecord, catalogNumber, otherCatalogNumbers, occurrenceid, '.
			'ownerInstitutionCode, institutionID, collectionID, institutionCode, collectionCode, '.
			'family, scientificName, sciname, tidinterpreted, genus, specificEpithet, datasetID, taxonRank, infraspecificEpithet, '.
			'scientificNameAuthorship, taxonRemarks, identifiedBy, dateIdentified, identificationReferences, identificationRemarks, '.
			'identificationQualifier, typeStatus, recordedBy, recordNumber, associatedCollectors, '.
			'eventDate, Year, Month, Day, startDayOfYear, endDayOfYear, verbatimEventDate, '.
			'habitat, substrate, fieldNotes, fieldnumber, occurrenceRemarks, informationWithheld, associatedOccurrences, '.
			'associatedTaxa, dynamicProperties, verbatimAttributes, reproductiveCondition, cultivationStatus, establishmentMeans, '.
			'lifestage, sex, individualcount, samplingprotocol, preparations, '.
			'country, stateProvince, county, municipality, locality, localitySecurity, localitySecurityReason, '.
			'decimalLatitude, decimalLongitude, geodeticDatum, coordinateUncertaintyInMeters, footprintWKT, '.
			'coordinatePrecision, locationRemarks, verbatimCoordinates, verbatimCoordinateSystem, georeferencedBy, georeferenceProtocol, '.
			'georeferenceSources, georeferenceVerificationStatus, georeferenceRemarks, minimumElevationInMeters, maximumElevationInMeters, '.
			'verbatimElevation, previousIdentifications, disposition, modified, language, recordEnteredBy, labelProject, duplicateQuantity ) '.
			'SELECT u.collid, u.dbpk, u.basisOfRecord, u.catalogNumber, u.otherCatalogNumbers, u.occurrenceid, '.
			'u.ownerInstitutionCode, u.institutionID, u.collectionID, u.institutionCode, u.collectionCode, '.
			'u.family, u.scientificName, u.sciname, u.tidinterpreted, u.genus, u.specificEpithet, u.datasetID, u.taxonRank, u.infraspecificEpithet, '.
			'u.scientificNameAuthorship, u.taxonRemarks, u.identifiedBy, u.dateIdentified, u.identificationReferences, u.identificationRemarks, '.
			'u.identificationQualifier, u.typeStatus, u.recordedBy, u.recordNumber, u.associatedCollectors, '.
			'u.eventDate, u.Year, u.Month, u.Day, u.startDayOfYear, u.endDayOfYear, u.verbatimEventDate, '.
			'u.habitat, u.substrate, u.fieldNotes, u.fieldnumber, u.occurrenceRemarks, u.informationWithheld, u.associatedOccurrences, '.
			'u.associatedTaxa, u.dynamicProperties, u.verbatimAttributes, u.reproductiveCondition, u.cultivationStatus, u.establishmentMeans, '.
			'u.lifestage, u.sex, u.individualcount, u.samplingprotocol, u.preparations, '.
			'u.country, u.stateProvince, u.county, u.municipality, u.locality, u.localitySecurity, u.localitySecurityReason, '.
			'u.decimalLatitude, u.decimalLongitude, u.geodeticDatum, u.coordinateUncertaintyInMeters, u.footprintWKT, '.
			'u.coordinatePrecision, u.locationRemarks, u.verbatimCoordinates, u.verbatimCoordinateSystem, u.georeferencedBy, u.georeferenceProtocol, '.
			'u.georeferenceSources, u.georeferenceVerificationStatus, u.georeferenceRemarks, u.minimumElevationInMeters, u.maximumElevationInMeters, '.
			'u.verbatimElevation, u.previousIdentifications, u.disposition, u.modified, u.language, u.recordEnteredBy, u.labelProject, u.duplicateQuantity '.
			'FROM uploadspectemp u '.
			'WHERE u.occid IS NULL AND u.collid = '.$this->collId.' AND (u.basisofrecord IS NULL OR u.basisofrecord != "determinationHistory")';
		if($this->conn->query($sql)){
			$this->outputMsg('Done!</li> ');
		}
		else{
			$this->outputMsg('FAILED! ERROR: '.$this->conn->error.'</li> ');
			$this->outputMsg($sql);
		}

		//Link all newly intersted records back to uploadspectemp in prep for loading determiantion history and associatedmedia
		$this->outputMsg('<li>Linking records in prep for loading determination history and associatedmedia... ');
		ob_flush();
		flush();
		//Update occid by matching dbpk 
		$sqlOcc1 = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON (u.dbpk = o.dbpk) AND (u.collid = o.collid) '.
			'SET u.occid = o.occid '.
			'WHERE (u.occid IS NULL) AND (u.dbpk IS NOT NULL) AND (o.dbpk IS NOT NULL) AND (u.collid = '.$this->collId.')';
		if(!$this->conn->query($sqlOcc1)){
			$this->outputMsg('<div>ERROR updating occid after occurrence insert: '.$this->conn->error.'</div>');
		}
		//Update occid by linking catalognumbers
		$sqlOcc2 = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON (u.catalogNumber = o.catalogNumber) AND (u.collid = o.collid) '.
			'SET u.occid = o.occid '.
			'WHERE (u.collid = '.$this->collId.') AND (u.occid IS NULL) AND (u.catalogNumber IS NOT NULL) AND (o.catalogNumber IS NOT NULL) ';
		if(!$this->conn->query($sqlOcc2)){
			$this->outputMsg('<div>ERROR updating occid (2nd step) after occurrence insert: '.$this->conn->error.'</div>');
		}
		
		//Setup and add datasets and link datasets to current user
		
		
		ob_flush();
		flush();
	}

	protected function transferIdentificationHistory(){
		$this->outputMsg('<li>Tranferring Determination History... ');
		ob_flush();
		flush();

		//Delete already existing determinations
		$sqlDel = 'DELETE u.* '.
			'FROM uploadspectemp u INNER JOIN omoccurdeterminations d ON u.occid = d.occid '.
			'WHERE (u.collid = '.$this->collId.') AND (u.basisofrecord = "determinationHistory") '.
			'AND (d.sciname = u.sciname) AND (d.identifiedBy = u.identifiedBy) AND (d.dateIdentified = u.dateIdentified)';
		$this->conn->query($sqlDel);

		//Load identification history records
		$sql = 'INSERT IGNORE INTO omoccurdeterminations (occid, sciname, scientificNameAuthorship, identifiedBy, dateIdentified, '.
			'identificationQualifier, identificationReferences, identificationRemarks) '.
			'SELECT u.occid, u.sciname, u.scientificNameAuthorship, u.identifiedBy, u.dateIdentified, '.
			'u.identificationQualifier, u.identificationReferences, u.identificationRemarks '.
			'FROM uploadspectemp u '.
			'WHERE u.occid IS NOT NULL AND u.collid = '.$this->collId.' AND u.basisofrecord = "determinationHistory"';
		if($this->conn->query($sql)){
			//Delete all determinations
			$sqlDel = 'DELETE * '.
				'FROM uploadspectemp '.
				'WHERE (collid = '.$this->collId.') AND (basisofrecord = "determinationHistory")';
			$this->conn->query($sqlDel);
			$this->outputMsg('Done!</li> ');
		}
		else{
			$this->outputMsg('FAILED! ERROR: '.$this->conn->error.'</li> ');
		}
	}

	private function transferAssociatedMedia(){
		//Check to see if we have any images to process
		$sql = 'SELECT associatedmedia, tidinterpreted, occid '.
			'FROM uploadspectemp '.
			'WHERE associatedmedia IS NOT NULL AND occid IS NOT NULL AND collid = '.$this->collId;
		$rs = $this->conn->query($sql);
		if($rs->num_rows){
			$this->outputMsg('<li>Preparing associatedMedia for image transfer... ');
			ob_flush();
			flush();
			while($r = $rs->fetch_object()){
				$mediaFile = trim(str_replace(';',',',$r->associatedmedia),', ');
				$mediaArr = explode(',',$mediaFile);
				foreach($mediaArr as $mediaUrl){
					$mediaUrl = trim($mediaUrl);
					if(!strpos($mediaUrl,' ') && !strpos($mediaUrl,'"')){
						if($this->urlExists($mediaUrl)){
							$sqlInsert = 'INSERT INTO uploadimagetemp(occid,tid,originalurl,url) '.
								'VALUES('.$r->occid.','.($r->tidinterpreted?$r->tidinterpreted:'NULL').',"'.$mediaUrl.'","empty")';
							if(!$this->conn->query($sqlInsert)){
								$this->outputMsg('<div style="margin-left:10px;">ERROR loading image into uploadimagetemp: '.$this->conn->error.'</div>');
								$this->outputMsg('<div style="margin-left:10px;">SQL: '.$sqlInsert.'</div>');
							}
						}
						else{
							echo 'Bad url: '.$mediaUrl.'<br/>';
						}
					}
				}
			}
			$this->outputMsg('Done!</li> ');
			ob_flush();
			flush();
		}
	}

	protected function transferImages(){
		$this->outputMsg('<li>Tranferring images... ');
		ob_flush();
		flush();
		//Update occid for all images
		$sql = 'UPDATE uploadimagetemp ui INNER JOIN uploadspectemp u ON ui.collid = u.collid AND ui.dbpk =u.dbpk '.
			'SET ui.occid = u.occid '.
			'WHERE (ui.occid IS NULL) AND (u.occid IS NOT NULL) AND (ui.collid = '.$this->collId.')';
		if(!$this->conn->query($sql)){
			$this->outputMsg('WARNING updating occids within uploadimagetemp: '.$this->conn->error.'</li> ');
		}

		//Remove images that failed to link to an occurrence record
		if(!$this->conn->query('DELETE FROM uploadimagetemp WHERE (occid IS NULL) AND (ui.collid = '.$this->collId.')')){
			$this->outputMsg('WARNING deleting uploadimagetemp records with NULL occid: '.$this->conn->error.'</li> ');
		}

		//Grab existing image data to make sure link wasn't previously loaded
		$existingImages = array();
		$sqlTest = 'SELECT i.occid, i.imgid, i.url, i.originalurl '.
			'FROM images i INNER JOIN uploadspectemp u ON i.occid = u.occid '.
			'WHERE associatedmedia IS NOT NULL AND u.collid = '.$this->collId;
		//echo $sqlTest;
		$rsTest = $this->conn->query($sqlTest);
		while($rowTest = $rsTest->fetch_object()){
			$existingImages[$rowTest->occid][$rowTest->imgid]['url'] = $rowTest->url;
			$existingImages[$rowTest->occid][$rowTest->imgid]['orig'] = $rowTest->originalurl;
		}
		$rsTest->free();


		//Grab image data to make sure link wasn't previously loaded
		$existingImages = array();
		$sqlTest = 'SELECT i.occid, i.imgid, i.url, i.originalurl '.
			'FROM images i INNER JOIN uploadspectemp u ON i.occid = u.occid '.
			'WHERE associatedmedia IS NOT NULL AND u.collid = '.$this->collId;
		//echo $sqlTest;
		$rsTest = $this->conn->query($sqlTest);
		while($rowTest = $rsTest->fetch_object()){
			$existingImages[$rowTest->occid][$rowTest->imgid]['url'] = $rowTest->url;
			$existingImages[$rowTest->occid][$rowTest->imgid]['orig'] = $rowTest->originalurl;
		}
		$rsTest->free();
		//Check to see if we have any images to process
		$sql = 'SELECT associatedmedia, tidinterpreted, occid '.
			'FROM uploadspectemp '.
			'WHERE associatedmedia IS NOT NULL AND occid IS NOT NULL AND collid = '.$this->collId;
		$rs = $this->conn->query($sql);
		$this->outputMsg('<li>Tranferring associatedMedia for '.$rs->num_rows.' occurrence records... ');
		ob_flush();
		flush();
		while($r = $rs->fetch_object()){
			$mediaFile = trim(str_replace(';',',',$r->associatedmedia),', ');
			$mediaArr = explode(',',$mediaFile);
			foreach($mediaArr as $mediaUrl){
				$mediaUrl = trim($mediaUrl);
				if(!strpos($mediaUrl,' ') && !strpos($mediaUrl,'"')){
					if($this->urlExists($mediaUrl)){
						//If file doesn't already exists, let's load it
						$loadImage = true;
						if(array_key_exists($r->occid,$existingImages)){
							foreach($existingImages[$r->occid] as $urlArr){
								if($urlArr['url'] == $mediaUrl){
									$loadImage = false;
								}
								elseif(array_key_exists('orig',$urlArr) && $urlArr['orig'] == $mediaUrl){
									$loadImage = false;
								}
							}
						}
						if($loadImage){
							$sqlInsert = 'INSERT INTO images(occid,tid,originalurl,url) '.
								'VALUES('.$r->occid.','.($r->tidinterpreted?$r->tidinterpreted:'NULL').',"'.$mediaUrl.'","empty")';
							if($this->conn->query($sqlInsert)){
								$this->imageTransferCount++;
							}
							else{
								$this->outputMsg('<div style="margin-left:10px;">ERROR loading image: '.$this->conn->error.'</div>');
								$this->outputMsg('<div style="margin-left:10px;">SQL: '.$sqlInsert.'</div>');
							}
						}
					}
					else{
						echo 'Bad url: '.$mediaUrl.'<br/>';
					}
				}
			}
		}
		$this->outputMsg('Done! ('.$this->imageTransferCount.' images)</li> ');
		ob_flush();
		flush();
		
		
		
		
		//if($this->conn->query($sql)){
			$this->outputMsg('Done!</li> ');
		//}
		//else{
		//	$this->outputMsg('FAILED! ERROR: '.$this->conn->error.'</li> ');
		//}
	}
	
	protected function finalCleanup(){
		$this->outputMsg('<li>Transfer process complete</li>');
		$this->outputMsg('<li>House cleaning</li>');

		//Do some more cleaning of the data after it haas been indexed in the omoccurrences table  
		$this->outputMsg('<li style="margin-left:10px;">Establish links to taxonomic thesaurus...');
		ob_flush();
		flush();
		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname '.
			'SET o.TidInterpreted = t.tid WHERE o.TidInterpreted IS NULL AND o.collid = '.$this->collId;
		$this->conn->query($sql);
		$this->outputMsg('Done!</li> ');
		
		$this->outputMsg('<li style="margin-left:10px;">Protecting sensitive species...');
		ob_flush();
		flush();
		$sql = 'UPDATE taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '.
			'SET o.LocalitySecurity = t.SecurityStatus '.
			'WHERE (t.SecurityStatus > 0) AND (o.LocalitySecurity IS NULL)';
		$this->conn->query($sql);
		$sql = 'UPDATE omoccurrences o INNER JOIN taxstatus ts1 ON o.tidinterpreted = ts1.tid '.
			'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
			'INNER JOIN fmchecklists c ON o.stateprovince = c.locality '. 
			'INNER JOIN fmchklsttaxalink cl ON c.clid = cl.clid AND ts2.tid = cl.tid '.
			'SET o.localitysecurity = 1 '.
			'WHERE (o.localitysecurity IS NULL OR o.localitysecurity = 0) AND c.type = "rarespp" '.
			'AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND o.collid ='.$this->collId;
		$this->conn->query($sql);
		$this->outputMsg('Done!</li> ');
		
		$this->outputMsg('<li style="margin-left:10px;">Populating NULL families and authors...');
		ob_flush();
		flush();
		$sql = 'UPDATE omoccurrences o INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid '.
			'SET o.family = ts.family '.
			'WHERE ts.taxauthid = 1 AND ts.family <> "" AND ts.family IS NOT NULL AND (o.family IS NULL OR o.family = "") AND o.collid = '.$this->collId;
		$this->conn->query($sql);

		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.tidinterpreted = t.tid '.
			'SET o.scientificNameAuthorship = t.author '.
			'WHERE (o.scientificNameAuthorship = "" OR o.scientificNameAuthorship IS NULL) AND t.author IS NOT NULL AND o.collid = '.$this->collId;
		$this->conn->query($sql);
		$this->outputMsg('Done!</li> ');
		
		$this->outputMsg('<li style="margin-left:10px;">Updating georeference indexing... ');
		ob_flush();
		flush();
		$sql = 'INSERT IGNORE INTO omoccurgeoindex(tid,decimallatitude,decimallongitude) '.
			'SELECT DISTINCT o.tidinterpreted, round(o.decimallatitude,3), round(o.decimallongitude,3) '.
			'FROM uploadspectemp o '.
			'WHERE o.tidinterpreted IS NOT NULL AND o.decimallatitude IS NOT NULL '.
			'AND o.decimallongitude IS NOT NULL AND collid = '.$this->collId;
		if($this->conn->query($sql)){
			$this->outputMsg('Done!</li> ');
		}
		else{
			$this->outputMsg('FAILED! ERROR: '.$this->conn->error.'</li> ');
		}
		
		//Remove records from occurrence temp table
		$sql = 'DELETE FROM uploadspectemp WHERE collid = '.$this->collId.' OR initialtimestamp < DATE_SUB(CURDATE(),INTERVAL 3 DAY)';
		$this->conn->query($sql);
		
		//Optimize table to reset indexes
		$this->conn->query('OPTIMIZE TABLE uploadspectemp');
		
		//Update collection stats
		$sql = 'UPDATE omcollectionstats SET uploaddate = NOW() WHERE collid = '.$this->collId;
		$this->conn->query($sql);

		$this->outputMsg('<li style="margin-left:10px;">Updating total record count... ');
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.recordcnt = (SELECT Count(o.occid) FROM omoccurrences o WHERE (o.collid = '.$this->collId.')) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		$this->outputMsg('Done!</li> ');
		
		$this->outputMsg('<li style="margin-left:10px;">Updating family count... ');
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.familycnt = (SELECT COUNT(DISTINCT o.family) '.
			'FROM omoccurrences o WHERE (o.collid = '.$this->collId.')) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		$this->outputMsg('Done!</li> ');
		
		$this->outputMsg('<li style="margin-left:10px;">Updating genus count... ');
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.genuscnt = (SELECT COUNT(DISTINCT t.unitname1) '.
			'FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '.
			'WHERE (o.collid = '.$this->collId.') AND t.rankid >= 180) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		$this->outputMsg('Done!</li>');

		$this->outputMsg('<li style="margin-left:10px;">Updating species count... ');
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.speciescnt = (SELECT count(DISTINCT t.unitname1, t.unitname2) AS spcnt '.
			'FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '.
			'WHERE (o.collid = '.$this->collId.') AND t.rankid >= 220) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		$this->outputMsg('Done</li>');
		
		$this->outputMsg('<li style="margin-left:10px;">Updating georeference count... ');
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.georefcnt = (SELECT Count(o.occid) FROM omoccurrences o WHERE (o.DecimalLatitude Is Not Null) '.
			'AND (o.DecimalLongitude Is Not Null) AND (o.CollID = '.$this->collId.')) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		$this->outputMsg('Done!</li>');

		/*
		$this->outputMsg('<li style="margin-left:10px;">Searching for duplicate Catalog Numbers... ');
		ob_flush();
		flush();
		$sql = 'SELECT catalognumber FROM omoccurrences GROUP BY catalognumber, collid '.
			'HAVING Count(*)>1 AND collid = '.$this->collId.' AND catalognumber IS NOT NULL';
		$rs = $this->conn->query($sql);
		if($rs->num_rows){
			$this->outputMsg('<span style="color:red;">Duplicate Catalog Numbers exist</span></li>');
			$this->outputMsg('<li style="margin-left:10px;">');
			$this->outputMsg('Open <a href="../editor/occurrencecleaner.php?collid='.$this->collId.'&action=listdupscatalog" target="_blank">Occurrence Cleaner</a> to resolve this issue');
			$this->outputMsg('</li>');
		}
		else{
			$this->outputMsg('All good!</li>');
		}
		$rs->free();
		*/
		
		$this->outputMsg('<li style="margin-left:10px;">Populating global unique identifiers (GUIDs) for all records... ');
		ob_flush();
		flush();
		$uuidManager = new UuidFactory();
		$uuidManager->setSilent(1);
		$uuidManager->populateGuids($this->collId);
		$this->outputMsg('Done!</li>');

		if($this->imageTransferCount){
			$this->outputMsg('<li style="margin-left:10px;">Building thumbnails for '.$this->imageTransferCount.' specimen images... ');
			ob_flush();
			flush();
			//Clean and populate null basic url and thumbnailurl fields
			$imgManager = new ImageCleaner();
			$imgManager->setVerbose(0);
			$imgManager->buildThumbnailImages($this->collId);
			$this->outputMsg('Done!</li>');
		}
	}
	
	protected function loadRecord($recMap){
		//Only import record if at least one of the minimal fields have data 
		if((array_key_exists('dbpk',$recMap) && $recMap['dbpk'])
			|| (array_key_exists('catalognumber',$recMap) && $recMap['catalognumber'])
			|| (array_key_exists('recordedby',$recMap) && $recMap['recordedby'])
			|| (array_key_exists('eventdate',$recMap) && $recMap['eventdate'])
			|| (array_key_exists('locality',$recMap) && $recMap['locality'])
			|| (array_key_exists('sciname',$recMap) && $recMap['sciname'])
			|| (array_key_exists('scientificname',$recMap) && $recMap['scientificname'])){
			//Trim all field values
			foreach($recMap as $k => $v){
				$recMap[$k] = trim($v);
			}
			//Remove institution and collection codes when they match what is in omcollections
			if(array_key_exists('institutioncode',$recMap) && $recMap['institutioncode'] == $this->collMetadataArr["institutioncode"]){
				unset($recMap['institutioncode']);
			}
			if(array_key_exists('collectioncode',$recMap) && $recMap['collectioncode'] == $this->collMetadataArr["collectioncode"]){
				unset($recMap['collectioncode']);
			}
			//Date cleaning
			if(array_key_exists('eventdate',$recMap) && $recMap['eventdate']){
				if(is_numeric($recMap['eventdate'])){
					if($recMap['eventdate'] > 2100 && $recMap['eventdate'] < 45000){
						//Date field was converted to Excel's numeric format (number of days since 01/01/1900)
						$recMap['eventdate'] = date('Y-m-d', mktime(0,0,0,1,$recMap['eventdate']-1,1900));
					}
					elseif($recMap['eventdate'] > 2200000 && $recMap['eventdate'] < 2500000){
						//Date is in the Gregorian format
						$dArr = explode('/',jdtogregorian($recMap['eventdate']));
						$recMap['eventdate'] = $dArr[2].'-'.$dArr[0].'-'.$dArr[1];
					}
					elseif($recMap['eventdate'] > 19000000){
						//Format: 20120101 = 2012-01-01 
						$recMap['eventdate'] = substr($recMap['eventdate'],0,4).'-'.substr($recMap['eventdate'],4,2).'-'.substr($recMap['eventdate'],6,2);
					}
				}
				else{
					//Make sure event date is a valid format or drop into verbatimEventDate
					$dateStr = OccurrenceUtilities::formatDate($recMap['eventdate']);
					if($dateStr){
						//if(strpos('-00',$dateStr)) $this->outputMsg($recMap['eventdate'].' => '.$dateStr."<br/>"); 
						if($recMap['eventdate'] != $dateStr && (!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate'])){
							$recMap['verbatimeventdate'] = $recMap['eventdate'];
						}
						$recMap['eventdate'] = $dateStr;
					}
					else{
						if(!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate']){
							$recMap['verbatimeventdate'] = $recMap['eventdate'];
						}
						unset($recMap['eventdate']);
					}
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
			//If month, day, or year are text, avoid SQL error by converting to numeric value 
			if(array_key_exists('year',$recMap) || array_key_exists('month',$recMap) || array_key_exists('day',$recMap)){
				$y = (array_key_exists('year',$recMap)?$recMap['year']:'00');
				$m = (array_key_exists('month',$recMap)?$recMap['month']:'00');
				$d = (array_key_exists('day',$recMap)?$recMap['day']:'00');
				$vDate = trim($y.'-'.$m.'-'.$d,'- ');
				if(isset($recMap['day']) && !is_numeric($recMap['day'])){
					if(!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate']){
						$recMap['verbatimeventdate'] = $vDate;
					}
					unset($recMap['day']);
					$d = '00';
				}
				if(isset($recMap['year']) && !is_numeric($recMap['year'])){
					if(!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate']){
						$recMap['verbatimeventdate'] = $vDate;
					}
					unset($recMap['year']);
				}
				if($recMap['month'] && !is_numeric($recMap['month'])){
					if(strlen($recMap['month']) > 2){
						$monAbbr = strtolower(substr($recMap['month'],0,3));
						if(array_key_exists($monAbbr,OccurrenceUtilities::$monthNames)){
							$recMap['month'] = OccurrenceUtilities::$monthNames[$monAbbr];
							$recMap['eventdate'] = OccurrenceUtilities::formatDate(trim($y.'-'.$recMap['month'].'-'.($d?$d:'00'),'- '));
						}
						else{
							if(!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate']){
								$recMap['verbatimeventdate'] = $vDate;
							}
							unset($recMap['month']);
						}
					}
					else{
						if(!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate']) {
							$recMap['verbatimeventdate'] = $vDate;
						}
						unset($recMap['month']);
					}
				}
				if($vDate && (!array_key_exists('eventdate',$recMap) || !$recMap['eventdate'])){
					$recMap['eventdate'] = OccurrenceUtilities::formatDate($vDate);
				}
			}
			//eventDate NULL && verbatimEventDate NOT NULL && year NOT NULL 
			if((!array_key_exists('eventdate',$recMap) || !$recMap['eventdate']) && array_key_exists('verbatimeventdate',$recMap) && $recMap['verbatimeventdate'] && (!array_key_exists('year',$recMap) || !$recMap['year'])){
				$dateStr = OccurrenceUtilities::formatDate($recMap['verbatimeventdate']);
				if($dateStr) $recMap['eventdate'] = $dateStr;
			}
			if((isset($recMap['recordnumberprefix']) && $recMap['recordnumberprefix']) || (isset($recMap['recordnumbersuffix']) && $recMap['recordnumbersuffix'])){
				$recNumber = $recMap['recordnumber'];
				if(isset($recMap['recordnumberprefix']) && $recMap['recordnumberprefix']) $recNumber = $recMap['recordnumberprefix'].'-'.$recNumber;
				if(isset($recMap['recordnumbersuffix']) && $recMap['recordnumbersuffix']){
					if(is_numeric($recMap['recordnumbersuffix']) && $recMap['recordnumber']) $recNumber .= '-';
					$recNumber .= $recMap['recordnumbersuffix'];
				}
				$recMap['recordnumber'] = $recNumber;
			}
			//If lat or long are not numeric, try to make them so
			if(array_key_exists('decimallatitude',$recMap) || array_key_exists('decimallongitude',$recMap)){
				$latValue = (array_key_exists('decimallatitude',$recMap)?$recMap['decimallatitude']:'');
				$lngValue = (array_key_exists('decimallongitude',$recMap)?$recMap['decimallongitude']:'');
				if(($latValue && !is_numeric($latValue)) || ($lngValue && !is_numeric($lngValue))){
					$llArr = OccurrenceUtilities::parseVerbatimCoordinates(trim($latValue.' '.$lngValue),'LL');
					if(array_key_exists('lat',$llArr) && array_key_exists('lng',$llArr)){
						$recMap['decimallatitude'] = $llArr['lat'];
						$recMap['decimallongitude'] = $llArr['lng'];
					}
					else{
						unset($recMap['decimallatitude']);
						unset($recMap['decimallongitude']);
					}
					$vcStr = '';
					if(array_key_exists('verbatimcoordinates',$recMap) && $recMap['verbatimcoordinates']){
						$vcStr .= $recMap['verbatimcoordinates'].'; ';
					}
					$vcStr .= $latValue.' '.$lngValue;
					if(trim($vcStr)) $recMap['verbatimcoordinates'] = trim($vcStr);
				}
			}
			if(array_key_exists('verbatimcoordinates',$recMap) && $recMap['verbatimcoordinates'] && (!isset($recMap['decimallatitude']) || !$recMap['decimallatitude'])){
				$coordArr = OccurrenceUtilities::parseVerbatimCoordinates($recMap['verbatimcoordinates']);
				if($coordArr){
					if(array_key_exists('lat',$coordArr)) $recMap['decimallatitude'] = $coordArr['lat'];
					if(array_key_exists('lng',$coordArr)) $recMap['decimallongitude'] = $coordArr['lng'];
				}
			}
			//Convert UTM to Lat/Long
			if((array_key_exists('utmnorthing',$recMap) && $recMap['utmnorthing']) || (array_key_exists('utmeasting',$recMap) && $recMap['utmeasting'])){
				$no = (array_key_exists('utmnorthing',$recMap)?$recMap['utmnorthing']:'');
				$ea = (array_key_exists('utmeasting',$recMap)?$recMap['utmeasting']:'');
				$zo = (array_key_exists('utmzoning',$recMap)?$recMap['utmzoning']:'');
				$da = (array_key_exists('geodeticdatum',$recMap)?$recMap['geodeticdatum']:'');
				if((!array_key_exists('decimallatitude',$recMap) || !$recMap['decimallatitude'])){
					if($no && $ea && $zo){
						//Northing, easting, and zoning all had values
						$llArr = OccurrenceUtilities::convertUtmToLL($ea,$no,$zo,$da);
						if(isset($llArr['lat'])) $recMap['decimallatitude'] = $llArr['lat'];
						if(isset($llArr['lng'])) $recMap['decimallongitude'] = $llArr['lng'];
					}
					else{
						//UTM was a single field which was placed in UTM northing field within uploadspectemp table
						$coordArr = OccurrenceUtilities::parseVerbatimCoordinates(trim($zo.' '.$ea.' '.$no),'UTM');
						if($coordArr){
							if(array_key_exists('lat',$coordArr)) $recMap['decimallatitude'] = $coordArr['lat'];
							if(array_key_exists('lng',$coordArr)) $recMap['decimallongitude'] = $coordArr['lng'];
						}
					}
				}
				$vCoord = (isset($recMap['verbatimcoordinates'])?$recMap['verbatimcoordinates']:'');
				if(!($no && strpos($vCoord,$no))) $recMap['verbatimcoordinates'] = ($vCoord?$vCoord.'; ':'').$zo.' '.$ea.'E '.$no.'N';
			}
			//Transfer verbatim Lat/Long to verbatim coords
			if((isset($recMap['verbatimlatitude']) && $recMap['verbatimlatitude']) || (isset($recMap['verbatimlongitude']) && $recMap['verbatimlongitude'])){
				//Attempt to extract decimal lat/long
				if(!array_key_exists('decimallatitude',$recMap) || !$recMap['decimallatitude']){
					$coordArr = OccurrenceUtilities::parseVerbatimCoordinates($recMap['verbatimlatitude'].' '.$recMap['verbatimlongitude'],'LL');
					if($coordArr){
						if(array_key_exists('lat',$coordArr)) $recMap['decimallatitude'] = $coordArr['lat'];
						if(array_key_exists('lng',$coordArr)) $recMap['decimallongitude'] = $coordArr['lng'];
					}
				}
				//Place into verbatim coord field
				$vCoord = (isset($recMap['verbatimcoordinates'])?$recMap['verbatimcoordinates']:'');
				if($vCoord) $vCoord .= '; ';
				if(stripos($vCoord,$recMap['verbatimlatitude']) === false && stripos($vCoord,$recMap['verbatimlongitude']) === false){
					$recMap['verbatimcoordinates'] = $vCoord.$recMap['verbatimlatitude'].', '.$recMap['verbatimlongitude'];
				}
			}
			//Transfer DMS to verbatim coords
			if(isset($recMap['latdeg']) && $recMap['latdeg'] && isset($recMap['lngdeg']) && $recMap['lngdeg']){
				//Attempt to create decimal lat/long
				if(is_numeric($recMap['latdeg']) && is_numeric($recMap['lngdeg']) && (!isset($recMap['decimallatitude']) || !$recMap['decimallatitude']) && (!isset($recMap['decimallongitude']) || !$recMap['decimallongitude'])){
					$latDec = $recMap['latdeg'];
					if(isset($recMap['latmin']) && $recMap['latmin'] && is_numeric($recMap['latmin'])) $latDec += $recMap['latmin']/60;
					if(isset($recMap['latsec']) && $recMap['latsec'] && is_numeric($recMap['latsec'])) $latDec += $recMap['latsec']/3600;
					if(stripos($recMap['latns'],'s') !== false) $latDec *= -1;
					$lngDec = $recMap['lngdeg'];
					if(isset($recMap['lngmin']) && $recMap['lngmin'] && is_numeric($recMap['lngmin'])) $lngDec += $recMap['lngmin']/60;
					if(isset($recMap['lngsec']) && $recMap['lngsec'] && is_numeric($recMap['lngsec'])) $lngDec += $recMap['lngsec']/3600;
					if(stripos($recMap['lngew'],'e') === false) $lngDec *= -1;
					$recMap['decimallatitude'] = round($latDec,6);
					$recMap['decimallongitude'] = round($lngDec,6);
				}
				//Place into verbatim coord field
				$vCoord = (isset($recMap['verbatimcoordinates'])?$recMap['verbatimcoordinates']:'');
				if($vCoord) $vCoord .= '; ';
				$vCoord .= $recMap['latdeg'].'d ';
				if(isset($recMap['latmin']) && $recMap['latmin']) $vCoord .= $recMap['latmin'].'m '; 
				if(isset($recMap['latsec']) && $recMap['latsec']) $vCoord .= $recMap['latsec'].'s ';
				if(isset($recMap['latns'])) $vCoord .= $recMap['latns'].'; ';
				$vCoord .= $recMap['lngdeg'].'d ';
				if(isset($recMap['lngmin']) && $recMap['lngmin']) $vCoord .= $recMap['lngmin'].'m '; 
				if(isset($recMap['lngsec']) && $recMap['lngsec']) $vCoord .= $recMap['lngsec'].'s ';
				if(isset($recMap['latew'])) $vCoord .= $recMap['lngew'];
				$recMap['verbatimcoordinates'] = $vCoord;
			}
			//Transfer TRS to verbatim coords
			if(isset($recMap['trstownship']) && $recMap['trstownship'] && isset($recMap['trsrange']) && $recMap['trsrange']){
				$vCoord = (isset($recMap['verbatimcoordinates'])?$recMap['verbatimcoordinates']:'');
				if($vCoord) $vCoord .= '; ';
				$vCoord .= (stripos($recMap['trstownship'],'t') === false?'T':'').$recMap['trstownship'].' ';
				$vCoord .= (stripos($recMap['trsrange'],'r') === false?'R':'').$recMap['trsrange'].' ';
				if(isset($recMap['trssection'])) $vCoord .= (stripos($recMap['trssection'],'s') === false?'sec':'').$recMap['trssection'].' ';
				if(isset($recMap['trssectiondetails'])) $vCoord .= $recMap['trssectiondetails'];
				$recMap['verbatimcoordinates'] = trim($vCoord);
			}
			
			//Check to see if evelation are valid numeric values
			if((isset($recMap['minimumelevationinmeters']) && $recMap['minimumelevationinmeters'] && !is_numeric($recMap['minimumelevationinmeters'])) 
				|| (isset($recMap['maximumelevationinmeters']) && $recMap['maximumelevationinmeters'] && !is_numeric($recMap['maximumelevationinmeters']))){
				$vStr = (isset($recMap['verbatimelevation'])?$recMap['verbatimelevation']:'');
				if(isset($recMap['minimumelevationinmeters']) && $recMap['minimumelevationinmeters']) $vStr .= ($vStr?'; ':'').$recMap['minimumelevationinmeters'];
				if(isset($recMap['maximumelevationinmeters']) && $recMap['maximumelevationinmeters']) $vStr .= '-'.$recMap['maximumelevationinmeters'];
				$recMap['verbatimelevation'] = $vStr;
				$recMap['minimumelevationinmeters'] = '';
				$recMap['maximumelevationinmeters'] = '';
			}
			//Verbatim elevation
			if(array_key_exists('verbatimelevation',$recMap) && $recMap['verbatimelevation'] && (!array_key_exists('minimumelevationinmeters',$recMap) || !$recMap['minimumelevationinmeters'])){
				$eArr = OccurrenceUtilities::parseVerbatimElevation($recMap['verbatimelevation']);
				if($eArr){
					if(array_key_exists('minelev',$eArr)){
						$recMap['minimumelevationinmeters'] = $eArr['minelev'];
						if(array_key_exists('maxelev',$eArr)) $recMap['maximumelevationinmeters'] = $eArr['maxelev'];
					}
				}
			}
			//Deal with elevation when in two fields (number and units)
			if(isset($recMap['elevationnumber']) && $recMap['elevationnumber']){
				$elevStr = $recMap['elevationnumber'].$recMap['elevationunits'];
				//Try to extract meters
				$eArr = OccurrenceUtilities::parseVerbatimElevation($elevStr);
				if($eArr){
					if(array_key_exists('minelev',$eArr)){
						$recMap['minimumelevationinmeters'] = $eArr['minelev'];
						if(array_key_exists('maxelev',$eArr)) $recMap['maximumelevationinmeters'] = $eArr['maxelev'];
					}
				}
				if(!$eArr || !stripos($elevStr,'m')){
					$vElev = (isset($recMap['verbatimelevation'])?$recMap['verbatimelevation']:'');
					if($vElev) $vElev .= '; ';
					$recMap['verbatimelevation'] = $vElev.$elevStr;
				}
			}
			//Concatinate collectorfamilyname and collectorinitials into recordedby
			if(isset($recMap['collectorfamilyname']) && $recMap['collectorfamilyname'] && (!isset($recMap['recordedby']) || !$recMap['recordedby'])){
				$recordedBy = $recMap['collectorfamilyname'];
				if(isset($recMap['collectorinitials']) && $recMap['collectorinitials']) $recordedBy .= ', '.$recMap['collectorinitials'];
				$recMap['recordedby'] = $recordedBy;
				//Need to add code that maps to collector table
				
			}

			if(array_key_exists("specificepithet",$recMap)){
				if($recMap["specificepithet"] == 'sp.' || $recMap["specificepithet"] == 'sp') $recMap["specificepithet"] = '';
			}
			if(array_key_exists("taxonrank",$recMap)){
				$tr = strtolower($recMap["taxonrank"]);
				if($tr == 'species' || !$recMap["specificepithet"]) $recMap["taxonrank"] = '';
				if($tr == 'subspecies') $recMap["taxonrank"] = 'subsp.';
				if($tr == 'variety') $recMap["taxonrank"] = 'var.';
				if($tr == 'forma') $recMap["taxonrank"] = 'f.';
			}
		
			//Populate sciname if null
			if(array_key_exists('sciname',$recMap) && $recMap['sciname']){
				if(substr($recMap['sciname'],-4) == ' sp.') $recMap['sciname'] = substr($recMap['sciname'],0,-4);
				if(substr($recMap['sciname'],-3) == ' sp') $recMap['sciname'] = substr($recMap['sciname'],0,-3);
				
				$recMap['sciname'] = str_replace(array(' ssp. ',' ssp '),' subsp. ',$recMap['sciname']);
				$recMap['sciname'] = str_replace(' var ',' var. ',$recMap['sciname']);
				
				$pattern = '/\b(cf\.|cf|aff\.|aff)\s{1}/';
				if(preg_match($pattern,$recMap['sciname'],$m)){
					$recMap['identificationqualifier'] = $m[1];
					$recMap['sciname'] = preg_replace($pattern,'',$recMap['sciname']);
				} 
			}
			else{
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
					$parsedArr = OccurrenceUtilities::parseScientificName($recMap['scientificname']);
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
			if($this->pKField && array_key_exists($this->pKField,$recMap) && !array_key_exists('dbpk',$recMap)){
				$recMap['dbpk'] = $recMap[$this->pKField];
			}
			
			//Do some cleaning on the dbpk; remove leading and trailing whitespaces and convert multiple spaces to a single space
			if(array_key_exists('dbpk',$recMap)){
				$recMap['dbpk'] = trim(preg_replace('/\s\s+/',' ',$recMap['dbpk']));
			}
			
			$sqlFragments = $this->getSqlFragments($recMap,$this->fieldMap);
			$sql = 'INSERT INTO uploadspectemp(collid'.$sqlFragments['fieldstr'].',dateentered) '.
				'VALUES('.$this->collId.$sqlFragments['valuestr'].','.date('Y-m-d H:i:s').')';
			//echo "<div>SQL: ".$sql."</div>";
			$this->conn->query('SET autocommit=0');
			$this->conn->query('SET unique_checks=0');
			$this->conn->query('SET foreign_key_checks=0');
			if($this->conn->query($sql)){
				$this->transferCount++;
				if($this->transferCount%1000 == 0) $this->outputMsg('<li style="margin-left:10px;">Count: '.$this->transferCount.'</li>');
				ob_flush();
				flush();
				//$this->outputMsg("<li>");
				//$this->outputMsg("Appending/Replacing observation #".$this->transferCount.": SUCCESS");
				//$this->outputMsg("</li>");
			}
			else{
				$this->outputMsg("<li>FAILED adding record #".$this->transferCount."</li>");
				$this->outputMsg("<div style='margin-left:10px;'>Error: ".$this->conn->error."</div>");
				$this->outputMsg("<div style='margin:0px 0px 10px 10px;'>SQL: $sql</div>");
			}
			$this->conn->query('COMMIT');
			$this->conn->query('SET unique_checks=1');
			$this->conn->query('SET foreign_key_checks=1');
		}
	}

	protected function loadIdentificationRecord($recMap){
		if($recMap){
			//Import record only if required fields have data (coreId and a scientificName) 
			if(isset($recMap['coreid']) && $recMap['coreid'] && (isset($recMap['sciname']) || isset($recMap['genus']))){
	
				//Do some cleaning 
				//Populate sciname if null
				if(!array_key_exists('sciname',$recMap) || !$recMap['sciname']){
					if(array_key_exists("genus",$recMap)){
						//Build sciname from individual units supplied by source
						$sciName = $recMap["genus"];
						if(array_key_exists("specificepithet",$recMap) && $recMap["specificepithet"]) $sciName .= " ".$recMap["specificepithet"];
						if(array_key_exists("taxonrank",$recMap) && $recMap["taxonrank"]) $sciName .= " ".$recMap["taxonrank"];
						if(array_key_exists("infraspecificepithet",$recMap) && $recMap["infraspecificepithet"]) $sciName .= " ".$recMap["infraspecificepithet"];
						$recMap['sciname'] = trim($sciName);
					}
				}
				//Try to get author, if it's not there 
				if(!array_key_exists('scientificnameauthorship',$recMap) || !$recMap['scientificnameauthorship']){
					//Parse scientific name to see if it has author imbedded
					$parsedArr = OccurrenceUtilities::parseScientificName($recMap['sciname']);
					if(array_key_exists('author',$parsedArr)){
						$recMap['scientificnameauthorship'] = $parsedArr['author'];
						//Load sciname from parsedArr since if appears that author was embedded
						$recMap['sciname'] = trim($parsedArr['unitname1'].' '.$parsedArr['unitname2'].' '.$parsedArr['unitind3'].' '.$parsedArr['unitname3']);
					}
				}
				
				//coreId should go into dbpk
				$recMap['dbpk'] = $recMap['coreid'];
				unset($recMap['coreid']);
				
				$sqlFragments = $this->getSqlFragments($recMap,$this->identFieldMap);
				if($recMap['identifiedby'] || $recMap['dateidentified']){
					if(!$recMap['identifiedby']) $recMap['identifiedby'] = 'not specified';
					if(!$recMap['dateidentified']) $recMap['dateidentified'] = 'not specified';
					$sql = 'INSERT INTO uploadspectemp(collid,basisofrecord'.$sqlFragments['fieldstr'].') '.
						'VALUES('.$this->collId.',"determinationHistory"'.$sqlFragments['valuestr'].')';
					//echo "<div>SQL: ".$sql."</div>"; exit;
					
					if($this->conn->query($sql)){
						$this->identTransferCount++;
						if($this->identTransferCount%1000 == 0) $this->outputMsg('<li style="margin-left:10px;">Count: '.$this->identTransferCount.'</li>');
						ob_flush();
						flush();
					}
					else{
						$this->outputMsg("<li>FAILED adding indetification history record #".$this->identTransferCount."</li>");
						$this->outputMsg("<div style='margin-left:10px;'>Error: ".$this->conn->error."</div>");
						$this->outputMsg("<div style='margin:0px 0px 10px 10px;'>SQL: $sql</div>");
					}
				}
			}
		}
	}

	protected function loadImageRecord($recMap){
		if($recMap){
			//Import record only if required fields have data 
			if(isset($recMap['dbpk']) && (isset($recMap['originalurl']) || isset($recMap['url']))){
				if(!isset($recMap['url'])) $recMap['url'] = 'empty';

				$sqlFragments = $this->getSqlFragments($recMap,$this->imageFieldMap);
				$sql = 'INSERT INTO uploadimagetemp(collid'.$sqlFragments['fieldstr'].') '.
					'VALUES('.$this->collId.$sqlFragments['valuestr'].')';
				//echo "<div>SQL: ".$sql."</div>"; exit;
				
				if($this->conn->query($sql)){
					$this->imageTransferCount++;
					if($this->imageTransferCount%1000 == 0) $this->outputMsg('<li style="margin-left:10px;">Count: '.$this->imageTransferCount.'</li>');
					ob_flush();
					flush();
				}
				else{
					$this->outputMsg("<li>FAILED adding image record #".$this->imageTransferCount."</li>");
					$this->outputMsg("<div style='margin-left:10px;'>Error: ".$this->conn->error."</div>");
					$this->outputMsg("<div style='margin:0px 0px 10px 10px;'>SQL: $sql</div>");
				}
			}
		}
	}

	private function getSqlFragments($recMap,$fieldMap){
		$sqlFields = '';
		$sqlValues = '';
		foreach($recMap as $symbField => $valueStr){
			if(substr($symbField,0,8) != 'unmapped'){
				$sqlFields .= ','.$symbField;
				$valueStr = $this->encodeString($valueStr);
				$valueStr = $this->cleanInStr($valueStr);
				//Load data
				$type = '';
				$size = 0;
				if(array_key_exists($symbField,$fieldMap)){ 
					if(array_key_exists('type',$fieldMap[$symbField])){
						$type = $fieldMap[$symbField]["type"];
					}
					if(array_key_exists('size',$fieldMap[$symbField])){
						$size = $fieldMap[$symbField]["size"];
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
					case "decimal":
						if(strpos($valueStr,',')){
							$sqlValues = str_replace(',','',$valueStr);
						}
						if($valueStr && $size && strpos($size,',') !== false){
							$tok = explode(',',$size);
							$m = $tok[0];
							$d = $tok[1];
							if($m && $d){
								$dec = substr($valueStr,strpos($valueStr,'.'));
								if(strlen($dec) > $d){
									$valueStr = round($valueStr,$d);
								}
								$rawLen = strlen(str_replace(array('-','.'),'',$valueStr));
								if($rawLen > $m){
									if(strpos($valueStr,'.') !== false){
										$decLen = strlen(substr($valueStr,strpos($valueStr,'.')));
										if($decLen < ($rawLen - $m)){
											$valueStr = '';
										}
										else{
											$valueStr = round($valueStr,$decLen - ($rawLen - $m));
										}
									}
									else{
										$valueStr = '';
									}
								}
							}
						}
						if(is_numeric($valueStr)){
							$sqlValues .= ",".$valueStr;
						}
						else{
							$sqlValues .= ",NULL";
						}
						break;
					case "date":
						$dateStr = OccurrenceUtilities::formatDate($valueStr);
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
						if(substr($valueStr,-1) == "\\"){
							$valueStr = rtrim($valueStr,"\\");
						}
						if($valueStr){
							$sqlValues .= ',"'.$valueStr.'"';
						}
						else{
							$sqlValues .= ",NULL";
						}
				}
			}
		}
		return array('fieldstr' => $sqlFields,'valuestr' => $sqlValues);
	}

	public function getTransferCount($reset = 0){
		if($this->collId && ($reset || !$this->transferCount)){
			$sql = "SELECT count(*) AS cnt FROM uploadspectemp WHERE (collid = ".$this->collId.') ';
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->transferCount = $row->cnt;
			}
			$rs->free();
		}
		return $this->transferCount;
	}
	
	public function getIdentTransferCount(){
		if($this->collId && !$this->identTransferCount){
			$sql = 'SELECT count(*) AS cnt FROM uploadspectemp '.
				'WHERE (collid = '.$this->collId.') AND (basisofrecord = "determinationHistory")';
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->identTransferCount = $row->cnt;
			}
			$rs->free();
		}
		return $this->identTransferCount;
	}
	
	public function getImageTransferCount(){
		return $this->imageTransferCount;
	}
	
	protected function setUploadTargetPath(){
		$tPath = $GLOBALS["tempDirRoot"];
		if(!$tPath){
			$tPath = ini_get('upload_tmp_dir');
		}
		if(!$tPath){
			$tPath = $GLOBALS["serverRoot"]."/temp";
		}
		if(substr($tPath,-1) != '/' && substr($tPath,-1) != '\\'){
			$tPath .= '/';
		}
		if(file_exists($tPath."downloads")){
			$tPath .= "downloads/";
		}
		$this->uploadTargetPath = $tPath;
	}

	public function setIncludeIdentificationHistory($boolIn){
		$this->includeIdentificationHistory = $boolIn;
	}

	public function setIncludeImages($boolIn){
		$this->includeImages = $boolIn;
	}
	
	public function setMatchCatalogNumber($matchCatNum){
		$this->matchCatalogNumber = $matchCatNum;
	}
	
	protected function urlExists($url) {
		$exists = false;
		if(!strstr($url, "http")){
	        $url = "http://".$url;
	    }
	    if(file_exists($url)){
			$exists = true;
	    }

	    if(!$exists){
		    // Version 4.x supported
		    $handle   = curl_init($url);
		    if (false === $handle){
				$exists = false;
		    }
		    curl_setopt($handle, CURLOPT_HEADER, false);
		    curl_setopt($handle, CURLOPT_FAILONERROR, true);  // this works
		    curl_setopt($handle, CURLOPT_HTTPHEADER, Array("User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15") ); // request as if Firefox   
		    curl_setopt($handle, CURLOPT_NOBODY, true);
		    curl_setopt($handle, CURLOPT_RETURNTRANSFER, false);
		    $exists = curl_exec($handle);
		    curl_close($handle);
	    }
	     
		//One more  check
	    if(!$exists){
	    	$exists = (@fclose(@fopen($url,"r")));
	    }
	    return $exists;
	}	

	protected function encodeString($inStr){
		global $charset;
		$retStr = $inStr;
		//Get rid of curly (smart) quotes
		$search = array("’", "‘", "`", "”", "“"); 
		$replace = array("'", "'", "'", '"', '"'); 
		$inStr= str_replace($search, $replace, $inStr);
		//Get rid of UTF-8 curly smart quotes and dashes 
		$badwordchars=array("\xe2\x80\x98", // left single quote
							"\xe2\x80\x99", // right single quote
							"\xe2\x80\x9c", // left double quote
							"\xe2\x80\x9d", // right double quote
							"\xe2\x80\x94", // em dash
							"\xe2\x80\xa6" // elipses
		);
		$fixedwordchars=array("'", "'", '"', '"', '-', '...');
		$inStr = str_replace($badwordchars, $fixedwordchars, $inStr);
		
		if($inStr){
			if(strtolower($charset) == "utf-8" || strtolower($charset) == "utf8"){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
					$retStr = utf8_encode($inStr);
					//$retStr = iconv("ISO-8859-1//TRANSLIT","UTF-8",$inStr);
				}
			}
			elseif(strtolower($charset) == "iso-8859-1"){
				if(mb_detect_encoding($inStr,'UTF-8,ISO-8859-1') == "UTF-8"){
					$retStr = utf8_decode($inStr);
					//$retStr = iconv("UTF-8","ISO-8859-1//TRANSLIT",$inStr);
				}
			}
			//$line = iconv('macintosh', 'UTF-8', $line);
			//mb_detect_encoding($buffer, 'windows-1251, macroman, UTF-8');
 		}
		return $retStr;
	}
}
?>