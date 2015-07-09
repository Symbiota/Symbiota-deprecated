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
	private $matchOtherCatalogNumbers = 0;
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
	private $imageTranslationMap = array('accessuri'=>'originalurl','thumbnailaccessuri'=>'thumbnailurl',
		'goodqualityaccessuri'=>'url','creator'=>'owner');

	function __construct() {
		parent::__construct();
		set_time_limit(7200);
	 	ini_set("max_input_time",240);
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
				if(substr($symbField,0,3) == 'ID-'){
					$this->identFieldMap[substr($symbField,3)]["field"] = $sourceField;
				}
				elseif(substr($symbField,0,3) == 'IM-'){
					$this->imageFieldMap[substr($symbField,3)]["field"] = $sourceField;
				}
				else{
					$this->fieldMap[$symbField]["field"] = $sourceField;
				}
			}
			$rs->free();
		}

		//Get uploadspectemp metadata
		$skipOccurFields = array('dbpk','initialtimestamp','occid','collid','tidinterpreted','fieldnotes','coordinateprecision',
			'verbatimcoordinatesystem','institutionid','collectionid','associatedoccurrences','datasetid','associatedreferences',
			'previousidentifications','associatedsequences');
		//Other to deal with/skip later: 'ownerinstitutioncode'
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
		
		if($this->uploadType == $this->FILEUPLOAD || $this->uploadType == $this->SKELETAL || $this->uploadType == $this->DWCAUPLOAD || $this->uploadType == $this->DIRECTUPLOAD){
			//Get identification metadata
			$skipDetFields = array('detid','occid','tidinterpreted','idbyid','appliedstatus','sortsequence','sourceidentifier','initialtimestamp');

			$rs = $this->conn->query('SHOW COLUMNS FROM uploaddetermtemp');
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
			$skipImageFields = array('tid','photographeruid','imagetype','occid','dbpk','specimenguid','collid','username','sortsequence','initialtimestamp');
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
			$sql = "DELETE FROM uploadspecmap WHERE (uspid = ".$this->uspid.") AND symbspecfield = 'dbpk'";
			$this->conn->query($sql);
			if($dbpk){
				$sql2 = "INSERT INTO uploadspecmap(uspid,symbspecfield,sourcefield) ".
					"VALUES (".$this->uspid.",'dbpk','".$dbpk."')";
				$this->conn->query($sql2);
			}
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

 	protected function prepUploadData(){
	 	//First, delete all records in uploadspectemp and uploadimagetemp table associated with this collection
		$this->outputMsg('<li>Clearing staging tables</li>',1);
 		$sqlDel1 = "DELETE FROM uploadspectemp WHERE (collid = ".$this->collId.')';
		$this->conn->query($sqlDel1);
		$sqlDel2 = "DELETE FROM uploaddetermtemp WHERE (collid = ".$this->collId.')';
		$this->conn->query($sqlDel2);
		$sqlDel3 = "DELETE FROM uploadimagetemp WHERE (collid = ".$this->collId.')';
		$this->conn->query($sqlDel3);
 	}
 	
 	public function uploadData($finalTransfer){
 		//Stored Procedure upload; other upload types are controlled by their specific class functions
		$this->outputMsg('<li>Initiating data upload</li>');
 		$this->prepUploadData();
		
	 	if($this->uploadType == $this->STOREDPROCEDURE){
			$this->cleanUpload();
 		}
 		elseif($this->uploadType == $this->SCRIPTUPLOAD){
 			if(system($this->queryStr)){
				$this->outputMsg('<li>Script Upload successful</li>');
				$this->outputMsg('<li>Initializing final transfer steps...</li>');
				$this->cleanUpload();
			}
		}
		ob_flush();
		flush();
		if($finalTransfer){
			$this->finalTransfer();
		}
		else{
			$this->outputMsg('<li>Record upload complete, ready for final transfer and activation</li>');
		}
 	}

	protected function cleanUpload(){

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
		
		//Run custom cleaning Stored Procedure, if one exists
		if($this->storedProcedure){
			$this->outputMsg('<li style="margin-left:10px;">');
			if($this->conn->query('CALL '.$this->storedProcedure)){
				$this->outputMsg('Stored procedure executed: '.$this->storedProcedure);
				if($this->conn->more_results()) $this->conn->next_result();
			}
			else{
				$this->outputMsg('<span style="color:red;">ERROR: Stored Procedure failed ('.$this->storedProcedure.'): '.$this->conn->error.'</span>');
			}
			$this->outputMsg('</li>');
			ob_flush();
			flush();
		}
		
 		//Prefrom general cleaning and parsing tasks
		$this->recordCleaningStage1();
		
		if($this->collMetadataArr["managementtype"] == 'Live Data' || $this->uploadType == $this->SKELETAL){
			if($this->matchCatalogNumber){
				//Match records based on Catalog Number 
				$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON (u.catalogNumber = o.catalogNumber) AND (u.collid = o.collid) '.
					'SET u.occid = o.occid '.
					'WHERE (u.collid = '.$this->collId.') AND (u.occid IS NULL) AND (u.catalogNumber IS NOT NULL) AND (o.catalogNumber IS NOT NULL) ';
				if(!$this->conn->query($sql)){
					$this->outputMsg('<span style="color:red;">Warning: unable to match on catalog number: '.$this->conn->error.'</span>');
				}
			}
			if($this->matchOtherCatalogNumbers){
				//Match records based on other Catalog Numbers fields 
				$sql2 = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON (u.otherCatalogNumbers = o.otherCatalogNumbers) AND (u.collid = o.collid) '.
					'SET u.occid = o.occid '.
					'WHERE (u.collid = '.$this->collId.') AND (u.occid IS NULL) AND (u.othercatalogNumbers IS NOT NULL) AND (o.othercatalogNumbers IS NOT NULL) ';
				if(!$this->conn->query($sql2)){
					$this->outputMsg('<span style="color:red;">Warning: unable to match on other catalog numbers: '.$this->conn->error.'</span>');
				}
			}
		}
		
		//Reset $treansferCnt so that count is accurate since some records may have been deleted due to data integrety issues
		$this->setTransferCount(); 
		$this->setIdentTransferCount();
		$this->setImageTransferCount();
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
		//Determination history and images from DWCA files
		if($this->uploadType == $this->DWCAUPLOAD){
			if($this->includeIdentificationHistory) $reportArr['ident'] = $this->getIdentTransferCount();
			if($this->includeImages) $reportArr['image'] = $this->getImageTransferCount();
		}
		//Append image counts from Associated Media
		$sql = 'SELECT count(*) AS cnt '.
			'FROM uploadspectemp '.
			'WHERE (associatedMedia IS NOT NULL) AND (collid = '.$this->collId.')';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$cnt = (isset($reportArr['image'])?$reportArr['image']:0) + $r->cnt;
			if($cnt) $reportArr['image'] = $cnt;
		}
		$rs->free();

		//Number of new specimen records
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

		if($this->collMetadataArr["managementtype"] == 'Live Data' && !$this->matchCatalogNumber  && !$this->matchOtherCatalogNumbers){
			//Records that can be matched on on Catalog Number, but will be appended 
			$sql = 'SELECT count(o.occid) AS cnt '.
				'FROM uploadspectemp u INNER JOIN omoccurrences o ON u.collid = o.collid '.
				'WHERE (u.collid = '.$this->collId.') AND (u.occid IS NULL) AND (u.catalogNumber = o.catalogNumber OR u.othercatalogNumbers = o.othercatalogNumbers) ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$reportArr['matchappend'] = $r->cnt;
			}
			$rs->free();
		}

		if($this->uploadType != $this->SKELETAL && $this->collMetadataArr["managementtype"] == 'Snapshot'){
			//Match records that were processed via the portal, walked back to collection's central database, and come back to portal 
			$sql = 'SELECT count(o.occid) AS cnt '.
				'FROM uploadspectemp u INNER JOIN omoccurrences o ON (u.catalogNumber = o.catalogNumber) AND (u.collid = o.collid) '.
				'WHERE (u.collid = '.$this->collId.') AND (u.occid IS NULL) AND (u.catalogNumber IS NOT NULL) '.
				'AND (o.catalogNumber IS NOT NULL) AND (o.dbpk IS NULL)';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$reportArr['sync'] = $r->cnt;
			}
			$rs->free();

			//Records already in portal that won't match with an incoming record 
			$sql = 'SELECT count(o.occid) AS cnt '.
				'FROM omoccurrences o LEFT JOIN uploadspectemp u  ON (o.occid = u.occid) '.
				'WHERE (o.collid = '.$this->collId.') AND (u.occid IS NULL)';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$reportArr['exist'] = $r->cnt;
			}
			$rs->free();
		}

		if($this->uploadType != $this->SKELETAL && ($this->collMetadataArr["managementtype"] == 'Snapshot' || $this->collMetadataArr["managementtype"] == 'Aggregate')){
			//Look for null dbpk
			$sql = 'SELECT count(*) AS cnt FROM uploadspectemp '.
				'WHERE (dbpk IS NULL) AND (collid = '.$this->collId.')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$reportArr['nulldbpk'] = $r->cnt;
			}
			$rs->free();

			//Look for duplicate dbpk
			$sql = 'SELECT dbpk FROM uploadspectemp '.
				'GROUP BY dbpk, collid, basisofrecord '.
				'HAVING (Count(*)>1) AND (collid = '.$this->collId.')';
			$rs = $this->conn->query($sql);
			$reportArr['dupdbpk'] = $rs->num_rows;
			$rs->free();
		}
		return $reportArr;
	}

	public function finalTransfer(){
		$this->recordCleaningStage2();
		$this->transferOccurrences();
		$this->prepareAssociatedMedia();
		$this->prepareImages();
		$this->transferIdentificationHistory();
		$this->transferImages();
		$this->finalCleanup();
		$this->outputMsg('<li style="">Upload Procedure Complete ('.date('Y-m-d h:i:s A').')!</li>');
	} 
	
	private function recordCleaningStage2(){
		if($this->collMetadataArr["managementtype"] == 'Snapshot' || $this->uploadType == $this->SKELETAL){
			//Match records that were processed via the portal, walked back to collection's central database, and come back to portal 
			$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON (u.catalogNumber = o.catalogNumber) AND (u.collid = o.collid) '.
				'SET u.occid = o.occid, o.dbpk = u.dbpk '.
				'WHERE (u.collid = '.$this->collId.') AND (u.occid IS NULL) AND (u.catalogNumber IS NOT NULL) AND (o.catalogNumber IS NOT NULL) AND (o.dbpk IS NULL) ';
			$this->conn->query($sql);
		}
		
		if(($this->collMetadataArr["managementtype"] == 'Snapshot' && $this->uploadType != $this->SKELETAL) || $this->collMetadataArr["managementtype"] == 'Aggregate'){
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
				'FROM uploadspectemp u INNER JOIN (SELECT dbpk FROM uploadspectemp '.
				'GROUP BY dbpk, collid HAVING Count(*)>1 AND collid = '.$this->collId.') t2 ON u.dbpk = t2.dbpk '.
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
		$fieldArr = array('basisOfRecord', 'catalogNumber','otherCatalogNumbers','occurrenceid',
			'ownerInstitutionCode','institutionID','collectionID','institutionCode','collectionCode',
			'family','scientificName','sciname','tidinterpreted','genus','specificEpithet','datasetID','taxonRank','infraspecificEpithet',
			'scientificNameAuthorship','identifiedBy','dateIdentified','identificationReferences','identificationRemarks',
			'taxonRemarks','identificationQualifier','typeStatus','recordedBy','recordNumber','associatedCollectors',
			'eventDate','Year','Month','Day','startDayOfYear','endDayOfYear','verbatimEventDate',
			'habitat','substrate','fieldnumber','occurrenceRemarks','informationWithheld','associatedOccurrences',
			'associatedTaxa','dynamicProperties','verbatimAttributes','reproductiveCondition','cultivationStatus','establishmentMeans',
			'lifestage','sex','individualcount','samplingprotocol','preparations',
			'country','stateProvince','county','municipality','locality','localitySecurity','localitySecurityReason',
			'decimalLatitude','decimalLongitude','geodeticDatum','coordinateUncertaintyInMeters','footprintWKT','coordinatePrecision',
			'locationRemarks','verbatimCoordinates','verbatimCoordinateSystem','georeferencedBy','georeferenceProtocol','georeferenceSources',
			'georeferenceVerificationStatus','georeferenceRemarks','minimumElevationInMeters','maximumElevationInMeters','verbatimElevation',
			'previousIdentifications','disposition','modified','language','recordEnteredBy','labelProject','duplicateQuantity','processingStatus');
		//Update matching records
		$sqlFrag = '';
		if($this->uploadType == $this->SKELETAL){
			foreach($fieldArr as $v){
				$sqlFrag .= 'o.'.$v.' = IFNULL(o.'.$v.',u.'.$v.'), ';
			}
		}
		else{
			foreach($fieldArr as $v){
				$sqlFrag .= 'o.'.$v.' = u.'.$v.', ';
			}
		}
		$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON u.occid = o.occid SET '.trim($sqlFrag,' ,').
			' WHERE (u.collid = '.$this->collId.')';
		//echo '<div>'.$sql.'</div>'; exit;
		if($this->conn->query($sql)){
			$this->outputMsg('Done!</li> ');
		}
		else{
			$this->outputMsg('FAILED! ERROR: '.$this->conn->error.'</li> ');
		}
		
		$this->outputMsg('<li>Transferring new records... ');
		ob_flush();
		flush();
		$sql = 'INSERT IGNORE INTO omoccurrences (collid, dbpk, dateentered, '.implode(', ',$fieldArr).' ) '.
			'SELECT u.collid, u.dbpk, "'.date('Y-m-d H:i:s').'", u.'.implode(', u.',$fieldArr).' FROM uploadspectemp u '.
			'WHERE u.occid IS NULL AND u.collid = '.$this->collId.'';
		//echo '<div>'.$sql.'</div>'; exit;
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
		$this->outputMsg('Done!</li> ');

		//Exsiccati transfer
		$rsTest = $this->conn->query('SHOW COLUMNS FROM uploadspectemp WHERE field = "exsiccatiIdentifier"');
		if($rsTest->num_rows){
			//Add any new exsiccati numbers 
			$sqlExs1 = 'INSERT INTO omexsiccatinumbers(ometid, exsnumber) '.
				'SELECT DISTINCT u.exsiccatiIdentifier, u.exsiccatinumber '.
				'FROM uploadspectemp u LEFT JOIN omexsiccatinumbers e ON u.exsiccatiIdentifier = e.ometid AND u.exsiccatinumber = e.exsnumber '.
				'WHERE (u.collid = '.$this->collId.') AND (u.occid IS NOT NULL) '.
				'AND (u.exsiccatiIdentifier IS NOT NULL) AND (u.exsiccatinumber IS NOT NULL) AND (e.exsnumber IS NULL)';
			if(!$this->conn->query($sqlExs1)){
				$this->outputMsg('<div>ERROR adding new exsiccati numbers: '.$this->conn->error.'</div>');
			}
			//Load exsiccati 
			$sqlExs2 = 'INSERT IGNORE INTO omexsiccatiocclink(omenid,occid) '.
				'SELECT e.omenid, u.occid '.
				'FROM uploadspectemp u INNER JOIN omexsiccatinumbers e ON u.exsiccatiIdentifier = e.ometid AND u.exsiccatinumber = e.exsnumber '.
				'WHERE (u.collid = '.$this->collId.') AND (e.omenid IS NOT NULL) AND (u.occid IS NOT NULL)';
			if($this->conn->query($sqlExs2)){
				$this->outputMsg('<li>Specimens linked to exsiccati index </li>');
			}
			else{
				$this->outputMsg('<div>ERROR adding new exsiccati numbers: '.$this->conn->error.'</div>');
			}
		}
		$rsTest->free();
		
		//Setup and add datasets and link datasets to current user
		
		
		ob_flush();
		flush();
	}

	protected function transferIdentificationHistory(){
		$sql = 'SELECT count(*) AS cnt FROM uploaddetermtemp WHERE (collid = '.$this->collId.')';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			if($r->cnt){
				$this->outputMsg('<li>Tranferring Determination History... ');
				ob_flush();
				flush();
	
				//Update occid for determinations of occurrence records already in portal 
				$sql = 'UPDATE uploaddetermtemp ud INNER JOIN uploadspectemp u ON ud.collid = u.collid AND ud.dbpk = u.dbpk '.
					'SET ud.occid = u.occid '.
					'WHERE (ud.occid IS NULL) AND (u.occid IS NOT NULL) AND (ud.collid = '.$this->collId.')';
				if(!$this->conn->query($sql)){
					$this->outputMsg('<div style="margin-left:20px;">WARNING updating occids within uploaddetermtemp: '.$this->conn->error.'</div> ');
				}
				ob_flush();
				flush();
	
				//Delete already existing determinations
				$sqlDel = 'DELETE u.* '.
					'FROM uploaddetermtemp u INNER JOIN omoccurdeterminations d ON u.occid = d.occid '.
					'WHERE (u.collid = '.$this->collId.') '.
					'AND (d.sciname = u.sciname) AND (d.identifiedBy = u.identifiedBy) AND (d.dateIdentified = u.dateIdentified)';
				$this->conn->query($sqlDel);
		
				//Load identification history records
				$sql = 'INSERT IGNORE INTO omoccurdeterminations (occid, sciname, scientificNameAuthorship, identifiedBy, dateIdentified, '.
					'identificationQualifier, iscurrent, identificationReferences, identificationRemarks, sourceIdentifier) '.
					'SELECT u.occid, u.sciname, u.scientificNameAuthorship, u.identifiedBy, u.dateIdentified, '.
					'u.identificationQualifier, u.iscurrent, u.identificationReferences, u.identificationRemarks, sourceIdentifier '.
					'FROM uploaddetermtemp u '.
					'WHERE u.occid IS NOT NULL AND (u.collid = '.$this->collId.')';
				if($this->conn->query($sql)){
					//Delete all determinations
					$sqlDel = 'DELETE * '.
						'FROM uploaddetermtemp '.
						'WHERE (collid = '.$this->collId.')';
					$this->conn->query($sqlDel);
					$this->outputMsg('Done!</li> ');
				}
				else{
					$this->outputMsg('FAILED! ERROR: '.$this->conn->error.'</li> ');
				}
			}
		}
		$rs->free();
	}

	private function prepareAssociatedMedia(){
		//Check to see if we have any images to process
		$sql1 = 'SELECT associatedmedia, tidinterpreted, occid '.
			'FROM uploadspectemp '.
			'WHERE (associatedmedia LIKE "http%") AND (occid IS NOT NULL) AND (collid = '.$this->collId.') LIMIT 5';
		$rs1 = $this->conn->query($sql1);
		if($rs1->num_rows){
			$this->outputMsg('<li>Preparing associatedMedia for image transfer... ');
			ob_flush();
			flush();
			$okToLoad = false; 
			//Test first 5 urls to make sure they are valid. If they are, then load
			while($r1 = $rs1->fetch_object()){
				$mediaFile = trim(str_replace(';',',',$r1->associatedmedia),', ');
				$mediaArr = explode(',',$mediaFile);
				foreach($mediaArr as $mediaUrl){
					$mediaUrl = trim($mediaUrl);
					if(!strpos($mediaUrl,' ') && !strpos($mediaUrl,'"')){
						if($this->urlExists($mediaUrl)){
							$okToLoad = true;
						}
						else{
							echo '<div style="margin-left:20px;">Bad url: '.$mediaUrl.'</div>';
						}
					}
				}
			}
			if($okToLoad){
				//Bulk transfer single URLs
				$sql2 = 'INSERT INTO uploadimagetemp(originalurl,url,occid,tid,collid) '.
					'SELECT trim(associatedmedia), "empty" AS url, occid, tidinterpreted, collid '.
					'FROM uploadspectemp '.
					'WHERE (collid = '.$this->collId.') AND (associatedmedia LIKE "http%") AND (occid IS NOT NULL) '.
					'AND (associatedmedia NOT LIKE "%,%") AND (associatedmedia NOT LIKE "%;%")';
				if($this->conn->query($sql2)){
					$sql2b = 'UPDATE uploadspectemp '.
						'SET associatedmedia = NULL '.
						'WHERE (collid = '.$this->collId.') AND (associatedmedia LIKE "http%") AND (occid IS NOT NULL) '.
						'AND (associatedmedia NOT LIKE "%,%") AND (associatedmedia NOT LIKE "%;%")';
					$this->conn->query($sql2b);
				}
				else{
					echo '<div style="margin-left:20px;">ERROR bulk transferring images: '.$this->conn->error.'</div>';
				}
				
				//Manually transfer multiple URLs
				$sql3 = 'SELECT associatedmedia, tidinterpreted, occid '.
					'FROM uploadspectemp '.
					'WHERE associatedmedia IS NOT NULL AND occid IS NOT NULL AND collid = '.$this->collId;
				$rs3 = $this->conn->query($sql3);
				while($r3 = $rs3->fetch_object()){
					$mediaFile = trim(str_replace(';',',',$r3->associatedmedia),', ');
					$mediaArr = explode(',',$mediaFile);
					foreach($mediaArr as $mediaUrl){
						$mediaUrl = trim($mediaUrl);
						if(!strpos($mediaUrl,' ') && !strpos($mediaUrl,'"')){
							if($this->urlExists($mediaUrl)){
								$sqlInsert = 'INSERT INTO uploadimagetemp(occid,tid,originalurl,url,collid) '.
									'VALUES('.$r3->occid.','.($r3->tidinterpreted?$r3->tidinterpreted:'NULL').',"'.$mediaUrl.'","empty",'.$this->collId.')';
								if(!$this->conn->query($sqlInsert)){
									$this->outputMsg('<div style="margin-left:10px;">ERROR loading image into uploadimagetemp: '.$this->conn->error.'</div>');
									$this->outputMsg('<div style="margin-left:10px;">SQL: '.$sqlInsert.'</div>');
								}
							}
							else{
								echo '<div style="margin-left:20px;">Bad url: '.$mediaUrl.'</div>';
							}
						}
					}
				}
				$rs3->free();
			}
			$this->outputMsg('Done!</li> ');
			ob_flush();
			flush();
		} 
		$rs1->free();
	}

	private function prepareImages(){
		$sql = 'SELECT * FROM uploadimagetemp WHERE collid = '.$this->collId;
		$rs = $this->conn->query($sql);
		if($rs->num_rows){
			$this->outputMsg('<li>Preparing images for transfer... </li>');
			ob_flush();
			flush();
			//Remove images that are not JPGs 
			$sql = 'DELETE FROM uploadimagetemp '.
				'WHERE (originalurl LIKE "%.dng") AND (collid = '.$this->collId.')';
			if($this->conn->query($sql)){
				$this->outputMsg('<li style="margin-left:10px;">step 1 of 4... </li>');
			}
			else{
				$this->outputMsg('<div style="margin-left:20px;">WARNING removing non-jpgs from uploadimagetemp: '.$this->conn->error.'</div> ');
			}
			ob_flush();
			flush();
			
			//Update occid for images of occurrence records already in portal 
			$sql = 'UPDATE uploadimagetemp ui INNER JOIN uploadspectemp u ON ui.collid = u.collid AND ui.dbpk = u.dbpk '.
				'SET ui.occid = u.occid '.
				'WHERE (ui.occid IS NULL) AND (u.occid IS NOT NULL) AND (ui.collid = '.$this->collId.')';
			if($this->conn->query($sql)){
				$this->outputMsg('<li style="margin-left:10px;">step 2 of 4... </li>');
			}
			else{
				$this->outputMsg('<div style="margin-left:20px;">WARNING updating occids within uploadimagetemp: '.$this->conn->error.'</div> ');
			}
			ob_flush();
			flush();
			
			//Remove images that don't have an occurrence record in uploadspectemp table
			$sql = 'DELETE ui.* '.
				'FROM uploadimagetemp ui LEFT JOIN uploadspectemp u ON ui.collid = u.collid AND ui.dbpk = u.dbpk '.
				'WHERE (ui.occid IS NULL) AND (ui.collid = '.$this->collId.') AND (u.collid IS NULL)';
			if($this->conn->query($sql)){
				$this->outputMsg('<li style="margin-left:10px;">step 3 of 4... </li>');
			}
			else{
				$this->outputMsg('<div style="margin-left:20px;">WARNING deleting orphaned uploadimagetemp records: '.$this->conn->error.'</div> ');
			}
			ob_flush();
			flush();
			
			//Remove previously loaded images where urls match exactly
			$sql = 'DELETE u.* FROM uploadimagetemp u INNER JOIN images i ON u.occid = i.occid '.
				'WHERE (u.collid = '.$this->collId.') AND (u.originalurl = i.originalurl)';
			if($this->conn->query($sql)){
				$this->outputMsg('<li style="margin-left:10px;">step 4 of 4... </li>');
			}
			else{
				$this->outputMsg('<div style="margin-left:20px;">ERROR deleting uploadimagetemp records with matching originalurls: '.$this->conn->error.'</div> ');
			}
			$sql = 'DELETE u.* FROM uploadimagetemp u INNER JOIN images i ON u.occid = i.occid '.
				'WHERE (u.collid = '.$this->collId.') AND (u.url = i.url)';
			if(!$this->conn->query($sql)){
				$this->outputMsg('<div style="margin-left:20px;">ERROR deleting uploadimagetemp records with matching originalurls: '.$this->conn->error.'</div> ');
			}
			ob_flush();
			flush();
			
			//Compare image file names to make sure link wasn't previously loaded
			/*
			$sqlTest = 'SELECT i.occid, i.url, u.url as url_new, i.originalurl, u.originalurl as originalurl_new '.
				'FROM images i INNER JOIN uploadimagetemp u ON i.occid = u.occid '.
				'WHERE (u.collid = '.$this->collId.')';
			//echo $sqlTest;
			$rsTest = $this->conn->query($sqlTest);
			while($rTest = $rsTest->fetch_object()){
				if($rTest->originalurl_new){
					$filename = array_pop(explode('/',$rTest->originalurl));
					$filenameNew = array_pop(explode('/',$rTest->originalurl_new));
					if($filename && $filename == $filenameNew){
						if(!$this->conn->query('DELETE FROM uploadimagetemp WHERE (occid = '.$rTest.') AND (originalurl = "'.$rTest->originalurl_new.'")')){
							$this->outputMsg('ERROR deleting uploadimagetemp record with matching file names ('.$filename.' != '.$filenameNew.'): '.$this->conn->error.'</li> ');
						}
					}
				}
			}
			$rsTest->free();
			*/
			//Reset transfer count
			$this->setImageTransferCount();
			$this->outputMsg('<li style="margin-left:10px;">Done! (revised count: '.$this->imageTransferCount.' images)</li> ');
			ob_flush();
			flush();
		}
		$rs->free();
	}

	protected function transferImages(){
		$sql = 'SELECT count(*) AS cnt FROM uploadimagetemp WHERE (collid = '.$this->collId.')';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			if($r->cnt){
				$this->outputMsg('<li>Tranferring images... ');
				ob_flush();
				flush();
				//Update occid for images of new records
				$sql = 'UPDATE uploadimagetemp ui INNER JOIN uploadspectemp u ON ui.collid = u.collid AND ui.dbpk =u.dbpk '.
					'SET ui.occid = u.occid '.
					'WHERE (ui.occid IS NULL) AND (u.occid IS NOT NULL) AND (ui.collid = '.$this->collId.')';
				//echo $sql.'<br/>';
				if(!$this->conn->query($sql)){
					$this->outputMsg('<div style="margin-left:20px;">WARNING updating occids within uploadimagetemp: '.$this->conn->error.'</div> ');
				}
	
				//Set image transfer count
				$this->setImageTransferCount();
	
				//Load images 
				$sql = 'INSERT INTO images(url,thumbnailurl,originalurl,occid,tid,caption,photographer,owner,notes ) '.
					'SELECT url,thumbnailurl,originalurl,occid,tid,caption,photographer,owner,notes '.
					'FROM uploadimagetemp '.
					'WHERE (occid IS NOT NULL) AND (collid = '.$this->collId.')';
				if($this->conn->query($sql)){
					$this->outputMsg('Done! ('.$this->imageTransferCount.' images)</li> ');
				}
				else{
					$this->outputMsg('FAILED! ERROR: '.$this->conn->error.'</li> ');
				}
				ob_flush();
				flush();
			}
		}
		$rs->free();
	}

	protected function finalCleanup(){
		$this->outputMsg('<li>Transfer process complete</li>');

		//Update uploaddate 
		$sql = 'UPDATE omcollectionstats SET uploaddate = CURDATE() WHERE collid = '.$this->collId;
		$this->conn->query($sql);
		
		//Remove records from occurrence temp table (uploadspectemp)
		$sql = 'DELETE FROM uploadspectemp WHERE (collid = '.$this->collId.') OR (initialtimestamp < DATE_SUB(CURDATE(),INTERVAL 3 DAY))';
		$this->conn->query($sql);
		//Optimize table to reset indexes
		$this->conn->query('OPTIMIZE TABLE uploadspectemp');
		
		//Remove records from determination temp table (uploaddetermtemp)
		$sql = 'DELETE FROM uploaddetermtemp WHERE (collid = '.$this->collId.') OR (initialtimestamp < DATE_SUB(CURDATE(),INTERVAL 3 DAY))';
		$this->conn->query($sql);
		//Optimize table to reset indexes
		$this->conn->query('OPTIMIZE TABLE uploaddetermtemp');
		
		//Remove records from image temp table (uploadimagetemp)
		$sql = 'DELETE FROM uploadimagetemp WHERE (collid = '.$this->collId.') OR (initialtimestamp < DATE_SUB(CURDATE(),INTERVAL 3 DAY))';
		$this->conn->query($sql);
		//Optimize table to reset indexes
		$this->conn->query('OPTIMIZE TABLE uploadimagetemp');
		
		//Do some more cleaning of the data after it haas been indexed in the omoccurrences table
		$occurUtil = new OccurrenceUtilities();

		$this->outputMsg('<li>Cleaning house</li>');
		ob_flush();
		flush();
		if(!$occurUtil->generalOccurrenceCleaning()){
			$errorArr = $occurUtil->getErrorArr();
			foreach($errorArr as $errorStr){
				echo '<li style="margin-left:20px;">'.$errorStr.'</li>';
			}
		}
		
		$this->outputMsg('<li style="margin-left:10px;">Protecting sensitive species...</li>');
		ob_flush();
		flush();
		if(!$occurUtil->protectRareSpecies($this->collId)){
			$errorArr = $occurUtil->getErrorArr();
			foreach($errorArr as $errorStr){
				echo '<li style="margin-left:20px;">'.$errorStr.'</li>';
			}
		}
		
		$this->outputMsg('<li style="margin-left:10px;">Updating statistics...</li>');
		ob_flush();
		flush();
		if(!$occurUtil->updateCollectionStats($this->collId)){
			$errorArr = $occurUtil->getErrorArr();
			foreach($errorArr as $errorStr){
				echo '<li style="margin-left:20px;">'.$errorStr.'</li>';
			}
		}

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
			$this->outputMsg('Open <a href="../cleaning/occurrencecleaner.php?collid='.$this->collId.'&action=listdupscatalog" target="_blank">Occurrence Cleaner</a> to resolve this issue');
			$this->outputMsg('</li>');
		}
		else{
			$this->outputMsg('All good!</li>');
		}
		$rs->free();
		*/
		
		$this->outputMsg('<li style="margin-left:10px;">Populating global unique identifiers (GUIDs) for all records... </li>');
		ob_flush();
		flush();
		$uuidManager = new UuidFactory();
		$uuidManager->setSilent(1);
		$uuidManager->populateGuids();

		if($this->imageTransferCount){
			$this->outputMsg('<li style="margin-left:10px;">Building thumbnails for '.$this->imageTransferCount.' specimen images... </li>');
			ob_flush();
			flush();
			//Clean and populate null basic url and thumbnailurl fields
			$imgManager = new ImageCleaner();
			$imgManager->setVerbose(0);
			$imgManager->buildThumbnailImages($this->collId);
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
				if(isset($recMap['month']) && $recMap['month'] && !is_numeric($recMap['month'])){
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
			$sql = 'INSERT INTO uploadspectemp(collid'.$sqlFragments['fieldstr'].') '.
				'VALUES('.$this->collId.$sqlFragments['valuestr'].')';
			//echo "<div>SQL: ".$sql."</div>";
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
		}
	}

	protected function loadIdentificationRecord($recMap){
		if($recMap){
			//coreId should go into dbpk
			if(isset($recMap['coreid']) && !isset($recMap['dbpk'])){
				$recMap['dbpk'] = $recMap['coreid'];
				unset($recMap['coreid']);
			}
				
			//Import record only if required fields have data (coreId and a scientificName)
			if(isset($recMap['dbpk']) && $recMap['dbpk'] && (isset($recMap['sciname']) || isset($recMap['genus']))){
	
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
				
				$sqlFragments = $this->getSqlFragments($recMap,$this->identFieldMap);
				if($recMap['identifiedby'] || $recMap['dateidentified']){
					if(!$recMap['identifiedby']) $recMap['identifiedby'] = 'not specified';
					if(!$recMap['dateidentified']) $recMap['dateidentified'] = 'not specified';
					$sql = 'INSERT INTO uploaddetermtemp(collid'.$sqlFragments['fieldstr'].') '.
						'VALUES('.$this->collId.$sqlFragments['valuestr'].')';
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

	public function getTransferCount(){
		if(!$this->transferCount) $this->setTransferCount();
		return $this->transferCount;
	}
	
	private function setTransferCount(){
		if($this->collId){
			$sql = 'SELECT count(*) AS cnt FROM uploadspectemp WHERE (collid = '.$this->collId.') ';
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->transferCount = $row->cnt;
			}
			$rs->free();
		}
	}

	public function getIdentTransferCount(){
		if(!$this->identTransferCount) $this->setIdentTransferCount();
		return $this->identTransferCount;
	}
	
	private function setIdentTransferCount(){
		if($this->collId){
			$sql = 'SELECT count(*) AS cnt FROM uploaddetermtemp '.
				'WHERE (collid = '.$this->collId.')';
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->identTransferCount = $row->cnt;
			}
			$rs->free();
		}
	}

	private function getImageTransferCount(){
		if(!$this->imageTransferCount) $this->setImageTransferCount();
		return $this->imageTransferCount;
	}
	
	private function setImageTransferCount(){
		if($this->collId){
			$sql = 'SELECT count(*) AS cnt FROM uploadimagetemp WHERE (collid = '.$this->collId.')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$this->imageTransferCount = $r->cnt;
			}
			else{
				$this->outputMsg('<div style="margin-left:20px;">ERROR setting image upload count: '.$this->conn->error.'</div> ');
			}
			$rs->free();
		}		
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
	
	public function setMatchCatalogNumber($match){
		$this->matchCatalogNumber = $match;
	}

	public function setMatchOtherCatalogNumbers($match){
		$this->matchOtherCatalogNumbers = $match;
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
	    	if(function_exists('curl_init')){
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
		$search = array("�", "�", "`", "�", "�"); 
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