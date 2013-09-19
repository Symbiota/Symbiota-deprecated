<?php
include_once($serverRoot.'/classes/SpecUpload.php');
include_once($serverRoot.'/classes/GPoint.php');
include_once($serverRoot.'/classes/ImageCleaner.php');
include_once($serverRoot.'/classes/UuidFactory.php');

class SpecUploadBase extends SpecUpload{

	protected $transferCount = 0;
	protected $identTransferCount = 0;
	protected $imageTransferCount = 0;
	protected $includeIdentificationHistory = true;
	protected $includeImages = true;
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
	
	private $monthNames = array('jan'=>'01','ene'=>'01','feb'=>'02','mar'=>'03','abr'=>'04','apr'=>'04',
		'may'=>'05','jun'=>'06','jul'=>'07','ago'=>'08','aug'=>'08','sep'=>'09','oct'=>'10','nov'=>'11','dec'=>'12','dic'=>'12');
	private $translationMap = array('accession'=>'catalognumber','accessionid'=>'catalognumber','accessionnumber'=>'catalognumber',
		'collector'=>'recordedby','primarycollector'=>'recordedby','collectornumber'=>'recordnumber',
		'collectionnumber'=>'recordnumber','date'=>'eventdate','collectiondate'=>'eventdate','cf' => 'identificationqualifier',
		'detby'=>'identifiedby','determinor'=>'identifiedby','determinationdate'=>'dateidentified',
		'state'=>'stateprovince','latitude'=>'verbatimlatitude','longitude'=>'verbatimlongitude','specimennotes'=>'occurrenceremarks',
		'notes'=>'occurrenceremarks','generalnotes'=>'occurrenceremarks','plantdescription'=>'verbatimattributes');
	private $identTranslationMap = array('scientificname'=>'sciname','detby'=>'identifiedby','determinor'=>'identifiedby',
		'determinationdate'=>'dateidentified','notes'=>'identificationremarks','cf' => 'identificationqualifier');
	private $imageTranslationMap = array('accessuri'=>'url');
	
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
			//echo $sql;
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
			$rs->close();
		}

		//Get uploadspectemp metadata
		$sql = "SHOW COLUMNS FROM uploadspectemp";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$field = strtolower($row->Field);
			if($field != "dbpk" && $field != "initialtimestamp" && $field != "occid" && $field != "collid" && $field != "tidinterpreted"){
				if($autoBuildFieldMap){
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

//		if($autoBuildFieldMap){
//			if($this->uploadType == $this->DWCAUPLOAD){
//				$this->fieldMap['dbpk']['field'] = 'id';
//			}
//			elseif($this->uploadType == $this->DIGIRUPLOAD && $this->digirPKField){
//				$this->fieldMap['dbpk']['field'] = $recMap[$this->digirPKField];
//			}
//		}
		
		if($this->uploadType == $this->FILEUPLOAD || $this->uploadType == $this->DWCAUPLOAD || $this->uploadType == $this->DIRECTUPLOAD){
			if($this->includeIdentificationHistory){
				//Get identification metadata
				$rs = $this->conn->query('SHOW COLUMNS FROM omoccurdeterminations');
				while($r = $rs->fetch_object()){
					$field = strtolower($r->Field);
					if($field != "detid" && $field != "initialtimestamp" && $field != "occid" && $field != "tidinterpreted" && $field != "idbyid"){
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
				$rs->close();
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
			}

			if($this->includeImages){
				//Get image metadata
				$rs = $this->conn->query('SHOW COLUMNS FROM images');
				while($r = $rs->fetch_object()){
					$field = strtolower($r->Field);
					if($field != "imgid" && $field != "initialtimestamp" && $field != "occid" && $field != "tid" && $field != "photographeruid"){
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
				$rs->close();
			}
		}
	}

	public function echoFieldMapTable($autoMap, $mode){
		$prefix = '';
		$fieldMap = array();
		$symbFields = array();
		$sourceArr = array();
		$translationMap = array();
		if($mode == 'ident'){
			$prefix = 'id';
			$fieldMap = $this->identFieldMap;
			$symbFields = $this->identSymbFields;
			$sourceArr = $this->identSourceArr;
			$translationMap = $this->identTranslationMap;
		}
		elseif($mode == 'image'){
			$prefix = 'im';
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
		if($mode == 'ident') print_r($sourceSymbArr);

		//Output table rows for source data
		echo '<table class="styledtable">';
		echo '<tr><th>Source Field</th><th>Target Field</th></tr>'."\n";
		sort($symbFields);
		$autoMapArr = Array();
		foreach($sourceArr as $fieldName){
			$diplayFieldName = $fieldName;
			$fieldName = strtolower($fieldName);
			$isAutoMapped = false;
			$tranlatedFieldName = str_replace(array('_',' ','.'),'',$fieldName);
			if($autoMap){
				if(array_key_exists($tranlatedFieldName,$translationMap)) $tranlatedFieldName = strtolower($translationMap[$tranlatedFieldName]);
				if(in_array($tranlatedFieldName,$this->symbFields)){
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
			echo "<option value='unmapped'".($sourceSymbArr[$fieldName]=='unmapped'?"SELECTED":"").">Leave Field Unmapped</option>\n";
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
		echo '</table>';
		
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
			$sqlInsert = "REPLACE INTO uploadspecmap(uspid,symbspecfield,sourcefield) ";
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
 	}

 	public function uploadData($finalTransfer){
 		//Stored Procedure upload; other upload types are controlled by their specific class functions
		set_time_limit(7200);

	 	//First, delete all records in uploadspectemp table associated with this collection
		$sqlDel = "DELETE FROM uploadspectemp WHERE (collid = ".$this->collId.')';
		$this->conn->query($sqlDel);

	 	if($this->uploadType == $this->STOREDPROCEDURE){
			$this->finalUploadSteps($finalTransfer);
 		}
 		elseif($this->uploadType == $this->SCRIPTUPLOAD){
 			if(system($this->queryStr)){
				$this->outputMsg('<li style="font-weight:bold;">Script Upload successful.</li>');
				$this->outputMsg('<li style="font-weight:bold;">Initializing final transfer steps...</li>');
				$this->finalUploadSteps($finalTransfer);
			}
		}
		ob_flush();
		flush();
	}

	public function finalUploadSteps($finalTransfer){
		//Run custom cleaning Stored Procedure, if one exists
		$this->outputMsg('<li style="font-weight:bold;">Records Upload Complete!</li>');
		$this->outputMsg('<li style="font-weight:bold;">Starting custom cleaning scripts...</li>');
		ob_flush();
		flush();

		if(stripos($this->collMetadataArr["managementtype"],'snapshot') !== false){
			//If collection is a snapshot, map upload to existing records. These records will be updated rather than appended
			$this->outputMsg('<li style="font-weight:bold;">Linking existing record in preparation for updating (matching DBPKs)... ');
			ob_flush();
			flush();
			$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON (u.dbpk = o.dbpk) AND (u.collid = o.collid) '.
				'SET u.occid = o.occid '.
				'WHERE u.collid = '.$this->collId.' AND u.occid IS NULL';
			$this->conn->query($sql);
			$this->outputMsg('Done!</li> ');
		}
		
		if($this->storedProcedure){
			try{
				if($this->conn->query('CALL '.$this->storedProcedure)){
					$this->outputMsg('<li style="font-weight:bold;margin-left:10px;">');
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
		$this->recordCleaningStage2();
		
		if(stripos($this->collMetadataArr["managementtype"],'snapshot') !== false){
			//Match records that were processed via the portal, walked back to collection's central database, and come back to portal 
			$this->outputMsg('<li style="font-weight:bold;">Linking existing record in preparation for updating (matching catalogNumbers on new records only)... ');
			ob_flush();
			flush();
			$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON (u.catalogNumber = o.catalogNumber) AND (u.collid = o.collid) '.
				'SET u.occid = o.occid, o.dbpk = u.dbpk '.
				'WHERE u.collid = '.$this->collId.' AND u.occid IS NULL AND u.catalogNumber IS NOT NULL AND o.dbpk IS NULL ';
			$this->conn->query($sql);
			$this->outputMsg('Done!</li> ');
		}

		$sql = "SELECT count(*) AS cnt FROM uploadspectemp WHERE (collid = ".$this->collId.')';
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			$this->transferCount = $row->cnt;
		}
		$rs->close();

		if($finalTransfer){
			$this->performFinalTransfer();
			$this->outputMsg('<li style="font-weight:bold;">Transfer process Complete! Click <a href="../misc/collprofile.php?collid='.$this->collId.'">here</a> to view stats page</li>');
		}
		else{
			$this->outputMsg('<li style="font-weight:bold;">Upload Procedure Complete');
			$this->outputMsg(': '.$this->transferCount.' records uploaded to temporary table');
			$this->outputMsg('</li>');
			if($this->transferCount){
				$this->outputMsg('<li style="font-weight:bold;">Use controls below to activate records and transfer to specimen table</li>');
			}
		}
	}
	
	private function recordCleaningStage1(){
		$this->outputMsg('<li style="font-weight:bold;">Starting Stage 1 cleaning</li>');
		
		if(stripos($this->collMetadataArr["managementtype"],'snapshot') !== false){
			$this->outputMsg('<li style="font-weight:bold;margin-left:10px;">Remove NULL dbpk values... ');
			ob_flush();
			flush();
			$sql = 'DELETE FROM uploadspectemp WHERE dbpk IS NULL AND collid = '.$this->collId;
			$this->conn->query($sql);
			$this->outputMsg('Done!</li> ');
			
			$this->outputMsg('<li style="font-weight:bold;margin-left:10px;">Remove duplicate dbpk values... ');
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
		
		$this->outputMsg('<li style="font-weight:bold;margin-left:10px;">Updating NULL eventDate with year-month-day... ');
		ob_flush();
		flush();
		$sql = 'UPDATE IGNORE uploadspectemp u '.
			'SET u.eventDate = CONCAT_WS("-",LPAD(u.year,4,"19"),IFNULL(LPAD(u.month,2,"0"),"00"),IFNULL(LPAD(u.day,2,"0"),"00")) '.
			'WHERE u.eventDate IS NULL AND u.year > 1300 AND u.year < 2020 AND collid = '.$this->collId;
		if($this->conn->query($sql)){
			$this->outputMsg('Done! ');
		}
		else{
			$this->outputMsg('<span style="color:red;">ERROR</span> ('.$this->conn->error.')');
		}
		$this->outputMsg('</li>');
		ob_flush();
		flush();
	}

	private function recordCleaningStage2(){
		$this->outputMsg('<li style="font-weight:bold;">Starting Stage 2 cleaning!</li>');
		$this->outputMsg('<li style="font-weight:bold;margin-left:10px;">Further updates on event date fields...');
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
		$this->outputMsg('Done!</li> ');

		$this->outputMsg('<li style="font-weight:bold;margin-left:10px;">Cleaning taxonomy...');
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
		$this->outputMsg('Done!</li> ');
		
		$this->outputMsg('<li style="font-weight:bold;margin-left:10px;">Linking to taxonomic thesaurus...');
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
		$this->outputMsg('Done!</li> ');

		$this->outputMsg('<li style="font-weight:bold;margin-left:10px;">Cleaning illegal and errored coordinates...');
		ob_flush();
		flush();
		$sql = 'UPDATE uploadspectemp SET DecimalLongitude = -1*DecimalLongitude '.
			'WHERE DecimalLongitude > 0 AND (Country = "USA" OR Country = "United States" OR Country = "U.S.A." OR Country = "Canada" OR Country = "Mexico") AND collid = '.$this->collId;
		$this->conn->query($sql);

		$sql = 'UPDATE uploadspectemp '.
			'SET DecimalLatitude = NULL, DecimalLongitude = NULL '.
			'WHERE DecimalLatitude = 0 AND DecimalLongitude = 0 AND collid = '.$this->collId;
		$this->conn->query($sql);

		//Move illegal coordinates to verbatim
		$sql = 'UPDATE uploadspectemp '.
			'SET verbatimcoordinates = CONCAT_WS(" ",DecimalLatitude, DecimalLongitude), DecimalLatitude = NULL, DecimalLongitude = NULL '.
			'WHERE DecimalLatitude < -90 OR DecimalLatitude > 90 OR DecimalLongitude < -180 OR DecimalLongitude > 180 AND collid = '.$this->collId;
		$this->conn->query($sql);
		$this->outputMsg('Done!</li> ');
	}
	
	public function performFinalTransfer(){
		//Clean and Transfer records from uploadspectemp to specimens
		
		$this->outputMsg('<li style="font-weight:bold;">Updating existing occurrence records... ');
		ob_flush();
		flush();
		$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON u.occid = o.occid '.
			'SET o.basisOfRecord = u.basisOfRecord, o.catalogNumber = u.catalogNumber, '.
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
			'o.language = u.language, o.recordEnteredBy = u.recordEnteredBy, o.duplicateQuantity = u.duplicateQuantity '.
			'WHERE u.collid = '.$this->collId.' AND u.basisofrecord != "determinationHistory"';
		if($this->conn->query($sql)){
			$this->outputMsg('Done!</li> ');
		}
		else{
			$this->outputMsg('FAILED! ERROR: '.$this->conn->error.'</li> ');
		}
		
		if(stripos($this->collMetadataArr["managementtype"],'snapshot') !== false){
			//Update DBPKs for records that were processed via the portal, walked back to collection's central database, and now come back to portal with assigned DBPKs 
			$this->outputMsg('<li style="font-weight:bold;">Updating DBPKs for records originally processed in portal, walked back to central database, and now return to portal with assigned DBPKs... ');
			ob_flush();
			flush();
			$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON u.occid = o.occid '.
				'SET o.dbpk = u.dbpk '.
				'WHERE u.collid = '.$this->collId.' AND o.dbpk IS NULL AND u.dbpk IS NOT NULL';
			if($this->conn->query($sql)){
				$this->outputMsg('Done!</li> ');
			}
			else{
				$this->outputMsg('FAILED! ERROR: '.$this->conn->error.'</li> ');
			}
		}
		
		$this->outputMsg('<li style="font-weight:bold;">Inserting new records into active occurrence table... ');
		ob_flush();
		flush();
		$sql = 'INSERT IGNORE INTO omoccurrences (collid, dbpk, basisOfRecord, catalogNumber, otherCatalogNumbers, ownerInstitutionCode, '.
			'institutionID, collectionID, institutionCode, collectionCode, '.
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
			'verbatimElevation, previousIdentifications, disposition, modified, language, recordEnteredBy, duplicateQuantity ) '.
			'SELECT u.collid, u.dbpk, u.basisOfRecord, u.catalogNumber, u.otherCatalogNumbers, u.ownerInstitutionCode, '.
			'u.institutionID, u.collectionID, u.institutionCode, u.collectionCode, '.
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
			'u.verbatimElevation, u.previousIdentifications, u.disposition, u.modified, u.language, u.recordEnteredBy, u.duplicateQuantity '.
			'FROM uploadspectemp u '.
			'WHERE u.occid IS NULL AND u.collid = '.$this->collId.' AND u.basisofrecord != "determinationHistory"';
		if($this->conn->query($sql)){
			$this->outputMsg('Done!</li> ');
		}
		else{
			$this->outputMsg('FAILED! ERROR: '.$this->conn->error.'</li> ');
		}
		
		$this->outputMsg('<li style="font-weight:bold;">Updating georeference indexing... ');
		ob_flush();
		flush();
		$sql = 'INSERT IGNORE INTO omoccurgeoindex(tid,decimallatitude,decimallongitude) '.
			'SELECT DISTINCT u.tidinterpreted, round(u.decimallatitude,3), round(u.decimallongitude,3) '.
			'FROM uploadspectemp u '.
			'WHERE u.tidinterpreted IS NOT NULL AND u.decimallatitude IS NOT NULL '.
			'AND u.decimallongitude IS NOT NULL';
		if($this->conn->query($sql)){
			$this->outputMsg('Done!</li> ');
		}
		else{
			$this->outputMsg('FAILED! ERROR: '.$this->conn->error.'</li> ');
		}

		//Process indentification history, if given
		$this->transferIdentificationHistory();
		//Process images, if they exist
		$this->transferAssociatedMedia();

		//Time to do some cleanup
		$sql = 'DELETE FROM uploadspectemp WHERE collid = '.$this->collId;
		$this->conn->query($sql);
		$this->outputMsg('<li style="font-weight:bold;">Collection transfer process finished</li>');
		ob_flush();
		flush();
		
		//Update collection stats
		$sql = 'UPDATE omcollectionstats SET uploaddate = NOW() WHERE collid = '.$this->collId;
		$this->conn->query($sql);

		$this->outputMsg('<li style="font-weight:bold;">Updating total record count... ');
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.recordcnt = (SELECT Count(o.occid) FROM omoccurrences o WHERE (o.collid = '.$this->collId.')) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		$this->outputMsg('Done!</li> ');
		
		$this->outputMsg('<li style="font-weight:bold;">Updating family count... ');
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.familycnt = (SELECT COUNT(DISTINCT o.family) '.
			'FROM omoccurrences o WHERE (o.collid = '.$this->collId.')) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		$this->outputMsg('Done!</li> ');
		
		$this->outputMsg('<li style="font-weight:bold;">Updating genus count... ');
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.genuscnt = (SELECT COUNT(DISTINCT t.unitname1) '.
			'FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '.
			'WHERE (o.collid = '.$this->collId.') AND t.rankid >= 180) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		$this->outputMsg('Done!</li>');

		$this->outputMsg('<li style="font-weight:bold;">Updating species count... ');
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.speciescnt = (SELECT count(DISTINCT t.unitname1, t.unitname2) AS spcnt '.
			'FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '.
			'WHERE (o.collid = '.$this->collId.') AND t.rankid >= 220) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		$this->outputMsg('Done</li>');
		
		$this->outputMsg('<li style="font-weight:bold;">Updating georeference count... ');
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.georefcnt = (SELECT Count(o.occid) FROM omoccurrences o WHERE (o.DecimalLatitude Is Not Null) '.
			'AND (o.DecimalLongitude Is Not Null) AND (o.CollID = '.$this->collId.')) '.
			'WHERE cs.collid = '.$this->collId;
		$this->conn->query($sql);
		$this->outputMsg('Done!</li>');
		
		$this->outputMsg('<li style="font-weight:bold;">Searching for duplicate Catalog Numbers... ');
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

		$this->outputMsg('<li style="font-weight:bold;">Populating global unique identifiers (GUIDs) for all records... ');
		ob_flush();
		flush();
		$uuidManager = new UuidFactory();
		if($this->verboseMode != 1) $uuidManager->setSilent(1);
		$uuidManager->populateGuids($this->collId);
		$this->outputMsg('Done!</li>');
		
	}
	
	private function transferIdentificationHistory(){
		$this->outputMsg('<li style="font-weight:bold;">Tranferring and activating Determination History... ');
		ob_flush();
		flush();
		$sql = 'INSERT IGNORE INTO omoccurdeterminations (occid, sciname, scientificNameAuthorship, identifiedBy, dateIdentified, '.
			'identificationQualifier, identificationReferences, identificationRemarks) '.
			'SELECT u.occid, u.sciname, u.scientificNameAuthorship, u.identifiedBy, u.dateIdentified, '.
			'u.identificationQualifier, u.identificationReferences, u.identificationRemarks '.
			'FROM uploadspectemp u '.
			'WHERE u.occid IS NOT NULL AND u.collid = '.$this->collId.' AND u.basisofrecord = "determinationHistory"';
		if($this->conn->query($sql)){
			$this->outputMsg('Done!</li> ');
		}
		else{
			$this->outputMsg('FAILED! ERROR: '.$this->conn->error.'</li> ');
		}
	}
	
	private function transferImages(){
		$this->outputMsg('<li style="font-weight:bold;">Tranferring and activating images... ');
		ob_flush();
		flush();
		$sql = 'INSERT IGNORE INTO images () '.
			'WHERE u.occid IS NOT NULL AND u.collid = '.$this->collId.' AND u.basisofrecord = "determinationHistory"';
		if($this->conn->query($sql)){
			$this->outputMsg('Done!</li> ');
		}
		else{
			$this->outputMsg('FAILED! ERROR: '.$this->conn->error.'</li> ');
		}
	}
	
	private function transferAssociatedMedia(){
		//Check to see if we have any images to process
		$imgCnt = 0;
		$sql = 'SELECT count(*) AS cnt '.
			'FROM uploadspectemp '.
			'WHERE associatedmedia LIKE "%.jpg" AND collid = '.$this->collId;
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$imgCnt = $r->cnt;
		}
		if($imgCnt){
			$this->outputMsg('<li style="font-weight:bold;">Tranferring '.$imgCnt.' image URLs... ');
			ob_flush();
			flush();
			//Update occid by linking catalognumbers
			$sql = 'UPDATE uploadspectemp u INNER JOIN omoccurrences o ON (u.catalogNumber = o.catalogNumber) AND (u.collid = o.collid) '.
				'SET u.occid = o.occid '.
				'WHERE u.collid = '.$this->collId.' AND u.occid IS NULL AND u.catalogNumber IS NOT NULL ';
			$this->conn->query($sql);
			//Load images
			$sql = 'INSERT INTO images(occid,tid,urloriginal) '.
				'SELECT occid, tidinterpreted, associatedmedia FROM uploadspectemp '.
				'WHERE associatedmedia LIKE "%.jpg" AND collid = '.$this->collId.' AND occid IS NOT NULL ';
			$this->conn->query($sql);
			//Clean and populate url and thumbnailurl
			$imgManager = new ImageCleaner();
			$imgManager->setVerbose(0);
			$imgManager->buildThumbnailImages($this->collId);
			$this->outputMsg('Done!</li> ');
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
					$dateStr = $this->formatDate($recMap['eventdate']);
					if($dateStr){
						//if(strpos('-00',$dateStr)) $this->outputMsg($recMap['eventdate'].' => '.$dateStr."<br/>"); 
						if(strpos('-00',$dateStr) && (!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate'])){
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
				if(!is_numeric($recMap['day'])){
					if(!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate']){
						$recMap['verbatimeventdate'] = $vDate;
					}
					unset($recMap['day']);
					$d = '00';
				}
				if(!is_numeric($recMap['year'])){
					if(!array_key_exists('verbatimeventdate',$recMap) || !$recMap['verbatimeventdate']){
						$recMap['verbatimeventdate'] = $vDate;
					}
					unset($recMap['year']);
				}
				if(!is_numeric($recMap['month'])){
					if(strlen($recMap['month']) > 2){
						$monAbbr = strtolower(substr($recMap['month'],0,3));
						if(array_key_exists($monAbbr,$this->monthNames)){
							$recMap['month'] = $this->monthNames[$monAbbr];
							$recMap['eventdate'] = $this->formatDate(trim($y.'-'.$recMap['month'].'-'.($d?$d:'00'),'- '));
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
					$recMap['eventdate'] = $this->formatDate($vDate);
				}
			}
			//eventDate NULL && verbatimEventDate NOT NULL && year NOT NULL 
			if((!array_key_exists('eventdate',$recMap) || !$recMap['eventdate']) && array_key_exists('verbatimeventdate',$recMap) && $recMap['verbatimeventdate'] && (!array_key_exists('year',$recMap) || !$recMap['year'])){
				$dateStr = $this->formatDate($recMap['verbatimeventdate']);
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
					$llArr = $this->parseVerbatimCoordinates(trim($latValue.' '.$lngValue),'LL');
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
			elseif(array_key_exists('verbatimcoordinates',$recMap) && $recMap['verbatimcoordinates'] && (!isset($recMap['decimallatitude']) || !$recMap['decimallatitude'])){
				$coordArr = $this->parseVerbatimCoordinates($recMap['verbatimcoordinates']);
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
						$gPoint = new GPoint($da);
						$gPoint->setUTM($ea,$no,$zo);
						$gPoint->convertTMtoLL();
						$lat = $gPoint->Lat();
						$lng = $gPoint->Long();
						if($lat && $lng){
							$recMap['decimallatitude'] = round($lat,6);
							$recMap['decimallongitude'] = round($lng,6);
						}
					}
					else{
						//UTM was a single field which was placed in UTM northing field within uploadspectemp table
						$coordArr = $this->parseVerbatimCoordinates(trim($zo.' '.$ea.' '.$no),'UTM');
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
					$coordArr = $this->parseVerbatimCoordinates($recMap['verbatimlatitude'].' '.$recMap['verbatimlongitude'],'LL');
					if($coordArr){
						if(array_key_exists('lat',$coordArr)) $recMap['decimallatitude'] = $coordArr['lat'];
						if(array_key_exists('lng',$coordArr)) $recMap['decimallongitude'] = $coordArr['lng'];
					}
				}
				//Place into verbatim coord field
				$vCoord = (isset($recMap['verbatimcoordinates'])?$recMap['verbatimcoordinates']:'');
				if($vCoord) $vCoord .= '; ';
				$recMap['verbatimcoordinates'] = $vCoord.$recMap['verbatimlatitude'].', '.$recMap['verbatimlongitude'];
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
				$vCoord .= $recMap['latns'].'; ';
				$vCoord .= $recMap['lngdeg'].'d ';
				if(isset($recMap['lngmin']) && $recMap['lngmin']) $vCoord .= $recMap['lngmin'].'m '; 
				if(isset($recMap['lngsec']) && $recMap['lngsec']) $vCoord .= $recMap['lngsec'].'s ';
				$vCoord .= $recMap['lngew'];
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
				$eArr = $this->parseVerbatimElevation($recMap['verbatimelevation']);
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
				$eArr = $this->parseVerbatimElevation($elevStr);
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
			
			//Do some cleaning on the dbpk; remove leading and trailing whitespaces and convert multiple spaces to a single space
			if(array_key_exists('dbpk',$recMap)){
				$recMap['dbpk'] = trim(preg_replace('/\s\s+/',' ',$recMap['dbpk']));
			}
			
			$sqlFragments = $this->getSqlFragments($recMap,$this->fieldMap);
			$sql = "INSERT INTO uploadspectemp(collid".$sqlFragments['fieldstr'].") ".
				"VALUES(".$this->collId.$sqlFragments['valuestr'].")";
			//echo "<div>SQL: ".$sql."</div>";
			
			if($this->conn->query($sql)){
				$this->transferCount++;
				if($this->transferCount%1000 == 0) $this->outputMsg('<li style="font-weight:bold;">Upload count: '.$this->transferCount.'</li>');
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

	public function loadIdentificationRecord($recMap){
		//Import record only if required fields have data 
		if((array_key_exists('dbpk',$recMap) && $recMap['dbpk'])
			&& (array_key_exists('sciname',$recMap) && $recMap['sciname'])){
			//Trim all field values
			foreach($recMap as $k => $v){
				$recMap[$k] = trim($v);
			}

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
				$parsedArr = $this->parseScientificName($recMap['sciname']);
				if(array_key_exists('author',$parsedArr)){
					$recMap['scientificnameauthorship'] = $parsedArr['author'];
					//Load sciname from parsedArr since if appears that author was embedded
					$recMap['sciname'] = trim($parsedArr['unitname1'].' '.$parsedArr['unitname2'].' '.$parsedArr['unitind3'].' '.$parsedArr['unitname3']);
				}
			}
			
			$sqlFragments = $this->getSqlFragments($recMap,$this->identFieldMap);
			$sql = 'INSERT INTO uploadspectemp(collid,basisofrecord'.$sqlFragments['fieldstr'].') '.
				'VALUES('.$this->collId.',"determinationHistory"'.$sqlFragments['valuestr'].')';
			//echo "<div>SQL: ".$sql."</div>";
			
			if($this->conn->query($sql)){
				$this->identTransferCount++;
				if($this->identTransferCount%1000 == 0) $this->outputMsg('<li style="font-weight:bold;">Identification History upload count: '.$this->identTransferCount.'</li>');
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
	
	public function loadImageRecord($recMap){
		//Import record only if required fields have data 
		if((array_key_exists('dbpk',$recMap) && $recMap['dbpk'])
			&& (array_key_exists('sciname',$recMap) && $recMap['sciname'])){
			//Trim all field values
			foreach($recMap as $k => $v){
				$recMap[$k] = trim($v);
			}
		
			
		}
	}

	private function getSqlFragments($recMap,$fieldMap){
		$sqlFields = '';
		$sqlValues = '';
		foreach($recMap as $symbField => $valueStr){
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
					if(trim($valueStr)){
						$sqlValues .= ',"'.$valueStr.'"';
					}
					else{
						$sqlValues .= ",NULL";
					}
			}
		}
		return array('fieldstr' => $sqlFields,'valuestr' => $sqlValues);
	}

	public function getTransferCount(){
		return $this->transferCount;
	}
	
	private function formatDate($inStr){
		$retDate = '';
		$dateStr = trim($inStr);
		if(!$dateStr) return;
		$t = '';
		$y = '';
		$m = '00';
		$d = '00';
		//Remove time portion if it exists
		if(preg_match('/\d{2}:\d{2}:\d{2}/',$dateStr,$match)){
			$t = $match[0];
		}
		if(preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})/',$dateStr,$match)){
			//Format: yyyy-m-d or yyyy-mm-dd
			$y = $match[1];
			$m = $match[2];
			$d = $match[3];
		}
		elseif(preg_match('/^(\d{4})-(\d{1,2})/',$dateStr,$match)){
			//Format: yyyy-m or yyyy-mm
			$y = $match[1];
			$m = $match[2];
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
		elseif(preg_match('/^(\D{3,})\.*\s{0,1}(\d{1,2}),{0,1}\s{0,1}(\d{2,4})/',$dateStr,$match)){
			//Format: mmm dd, yyyy
			$mStr = $match[1];
			$d = $match[2];
			$y = $match[3];
			$mStr = strtolower(substr($mStr,0,3));
			if(array_key_exists($mStr,$this->monthNames)) $m = $this->monthNames[$mStr];
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
		//Clean, configure, return
		if($y){
			if(strlen($m) == 1) $m = '0'.$m;
			if(strlen($d) == 1) $d = '0'.$d;
			//Check to see if month is valid
			if($m > 12){
				$m = '00';
				$d = '00';
			}
			//check to see if day is valid for month
			if($d > 31){
				//Bad day for any month
				$d = '00';
			}
			elseif($d == 30 && $m == 2){
				//Bad day for feb
				$d = '00';
			}
			elseif($d == 31 && ($m == 4 || $m == 6 || $m == 9 || $m == 11)){
				//Bad date, month w/o 31 days
				$d = '00';
			}
			//Do some cleaning
			if(strlen($y) == 2){ 
				if($y < 20) $y = '20'.$y;
				else $y = '19'.$y;
			}
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
		//Remove underscores, common in NPS data
		$inStr = preg_replace('/_+/',' ',$inStr);
		if(stripos($inStr,'cf. ') !== false){
			$retArr['identificationqualifier'] = 'cf. ';
			$inStr = str_ireplace('cf. ','',$inStr);
		}
		elseif(stripos($inStr,'aff. ') !== false){
			$retArr['identificationqualifier'] = 'aff. ';
			$inStr = str_ireplace('aff. ','',$inStr);
		}
		//Remove extra species
		$inStr = preg_replace('/\s\s+/',' ',$inStr);
		
		$sciNameArr = explode(' ',$inStr);
		if(count($sciNameArr)){
			if(strtolower($sciNameArr[0]) == 'x'){
				//Genus level hybrid
				$retArr['unitind1'] = array_shift($sciNameArr);
			}
			//Genus
			$retArr['unitname1'] = ucfirst(strtolower(array_shift($sciNameArr)));
			if(count($sciNameArr)){
				if(strtolower($sciNameArr[0]) == 'x'){
					//Species level hybrid
					$retArr['unitind2'] = array_shift($sciNameArr);
					$retArr['unitname2'] = strtolower(array_shift($sciNameArr));
				}
				elseif((strpos($sciNameArr[0],'.') !== false) || (strpos($sciNameArr[0],'(') !== false)){
					//It is assumed that Author has been reached, thus stop process 
					unset($sciNameArr);
				}
				else{
					//Specific Epithet
					$retArr['unitname2'] = strtolower(array_shift($sciNameArr));
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
			//Double check to see if infraSpecificEpithet is still embedded in author due initial lack of taxonRank indicator
			if(!array_key_exists('unitname3',$retArr)){
				if(preg_match('/\s+([a-z]{4,})([\sA-Z]*.*)/',$retArr['author'],$m) || preg_match('/^([a-z]{4,})([\sA-Z]*.*)/',$retArr['author'],$m)){
					$sql = 'SELECT unitind3 FROM taxa '.
						'WHERE unitname1 = "'.$retArr['unitname1'].'" AND unitname2 = "'.$retArr['unitname2'].'" AND unitname3 = "'.$m[1].'" '.
						'ORDER BY unitind3 DESC';
					$rs = $this->conn->query($sql);
					if($r = $rs->fetch_object()){
						$retArr['unitname3'] = $m[1];
						$retArr['unitind3'] = $r->unitind3;
						$retArr['author'] = $m[2];
					}
					$rs->close();
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

	private function parseVerbatimCoordinates($inStr,$target=''){
		$retArr = array();
		//Get rid of curly quotes
		$search = array("’", "‘", "`", "”", "“"); 
		$replace = array("'", "'", "'", '"', '"'); 
		$inStr= str_replace($search, $replace, $inStr);

		//Try to parse lat/lng
		$latDeg = 'null';$latMin = 0;$latSec = 0;$latNS = 'N';
		$lngDeg = 'null';$lngMin = 0;$lngSec = 0;$lngEW = 'W';
		//Grab lat deg and min
		if(!$target || $target == 'LL'){
			if(preg_match('/(-*\d+\.+\d+)([NSns]{0,1})\s*(-*\d+\.+\d+)([EWew]{0,1})/i',$inStr,$m)){
				//Decimal degree format
				$retArr['lat'] = $m[1];
				$retArr['lng'] = $m[3];
				$latDir = $m[2];
				if($retArr['lat'] > 0 && $latDir && ($latDir = 'S' || $latDir = 's')) $retArr['lat'] = -1*$retArr['lat'];
				$lngDir = $m[4];
				if($retArr['lng'] > 0 && $latDir && ($lngDir = 'W' || $lngDir = 'w')) $retArr['lng'] = -1*$retArr['lng'];
			}
			elseif(preg_match('/(\d{1,2})\D{1,3}\s*(\d{1,2}\.{0,1}\d*)[\'m]{1}(.*)/i',$inStr,$m)){
				//DMS format
				$latDeg = $m[1];
				$latMin = $m[2];
				$leftOver = str_replace("''",'"',trim($m[3]));
				//Grab lat NS and lng EW
				if(stripos($inStr,'N') === false && strpos($inStr,'S') !== false){
					$latNS = 'S';
				}
				if(stripos($inStr,'W') === false && stripos($inStr,'E') !== false){
					$lngEW = 'E';
				}
				//Grab lat sec
				if(preg_match('/^(\d{1,2}\.{0,1}\d*)["s]{1}(.*)/i',$leftOver,$m)){
					$latSec = $m[1];
					if(count($m)>2){
						$leftOver = trim($m[2]);
					}
				}
				//Grab lng deg and min
				if(preg_match('/(\d{1,3})\s*\D{1,3}\s*(\d{1,2}\.{0,1}\d*)\s{0,1}[\'m]{1}(.*)/i',$leftOver,$m)){
					$lngDeg = $m[1];
					$lngMin = $m[2];
					$leftOver = trim($m[3]);
					//Grab lng sec
					if(preg_match('/^(\d{1,2}\.{0,1}\d*)["s]{1}(.*)/i',$leftOver,$m)){
						$lngSec = $m[1];
						if(count($m)>2){
							$leftOver = trim($m[2]);
						}
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
		}
		if((!$target && !$retArr) || $target == 'UTM'){
			//UTM parsing 
			if(preg_match('/\D*(\d{1,2}\D{0,1})\s*(\d{6,7})E\s*(\d{7})N/i',$inStr,$m)){
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
			elseif(preg_match('/UTM/',$inStr) || preg_match('/\d{1,2}[\D\s]+\d{6,7}[\D\s]+\d{6,7}/',$inStr)){
				//UTM
				$z = ''; $e = ''; $n = ''; $d = '';
				if(preg_match('/[\s\D]*(\d{1,2}\D{0,1})[\s\D]*/',$inStr,$m)) $z = $m[1];
				if($z){
					if(preg_match('/(\d{6,7})E{1}[\D\s]+(\d{7})N{1}/i',$inStr,$m)){
						$e = $m[1];
						$n = $m[2];
					} 
					elseif(preg_match('/E{1}(\d{6,7})[\D\s]+N{1}(\d{7})/i',$inStr,$m)){
						$e = $m[1];
						$n = $m[2];
					} 
					elseif(preg_match('/(\d{7})N{1}[\D\s]+(\d{6,7})E{1}/i',$inStr,$m)){
						$e = $m[2];
						$n = $m[1];
					} 
					elseif(preg_match('/N{1}(\d{7})[\D\s]+E{1}(\d{6,7})/i',$inStr,$m)){
						$e = $m[2];
						$n = $m[1];
					} 
					elseif(preg_match('/(\d{6})[\D\s]+(\d{7})/',$inStr,$m)){
						$e = $m[1];
						$n = $m[2];
					} 
					elseif(preg_match('/(\d{7})[\D\s]+(\d{6})/',$inStr,$m)){
						$e = $m[2];
						$n = $m[1];
					} 
					if(preg_match('/NAD\s*27/i',$inStr)) $d = 'NAD27';
					if($n && $e){
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
			}
		}
		//Clean
		if($retArr){
			if($retArr['lat'] < -90 || $retArr['lat'] > 90) return;
			if($retArr['lng'] < -180 || $retArr['lng'] > 180) return;
		}
		return $retArr;
	}

	private function parseVerbatimElevation($inStr){
		$retArr = array();
		//Get rid of curly quotes
		$search = array("’", "‘", "`", "”", "“"); 
		$replace = array("'", "'", "'", '"', '"'); 
		$inStr= str_replace($search, $replace, $inStr);
		//Start parsing
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
		elseif(preg_match('/(\d+)[fet\']{,4}\s*-\s*(\d+)\s{,1}[f\']{1}/i',$inStr,$m)){
			$retArr['minelev'] = (round($m[1]*.3048));
			$retArr['maxelev'] = (round($m[2]*.3048));
		}
		elseif(preg_match('/(\d+)\s*[f\']{1}/i',$inStr,$m)){
			$retArr['minelev'] = (round($m[1]*.3048));
		}
		//Clean
		if($retArr){
			if(array_key_exists('minelev',$retArr) && ($retArr['minelev'] > 8000 || $retArr['minelev'] < 0)) unset($retArr['minelev']);
			if(array_key_exists('maxelev',$retArr) && ($retArr['maxelev'] > 8000 || $retArr['maxelev'] < 0)) unset($retArr['maxelev']);
		}
		return $retArr;
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
		if(is_numeric($boolIn)) $this->includeIdentificationHistory = $boolIn;
	}

	public function setIncludeImages($boolIn){
		if(is_numeric($boolIn)) $this->includeImages = $boolIn;
	}
	
	protected function encodeString($inStr){
		global $charset;
		$retStr = $inStr;
		//Get rid of curly quotes
		$search = array("’", "‘", "`", "”", "“"); 
		$replace = array("'", "'", "'", '"', '"'); 
		$inStr= str_replace($search, $replace, $inStr);
		
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
 		}
		return $retStr;
	}
	
}

?>