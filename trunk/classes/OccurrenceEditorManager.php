<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/OccurrenceEditorDeterminations.php');
include_once($serverRoot.'/classes/OccurrenceEditorImages.php');

class OccurrenceEditorManager {

	protected $conn;
	protected $occId;
	private $collId;
	private $collMap = Array();
	private $occurrenceMap = Array();
	private $occSql;
	private $sqlWhere;
	private $qryArr = Array();
	private $symbUid;
	
	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
		$this->occSql = 'SELECT o.occid, o.collid, o.basisOfRecord, o.occurrenceID, o.catalogNumber, o.otherCatalogNumbers, '.
		'o.ownerInstitutionCode, o.family, o.scientificName, o.sciname, o.tidinterpreted, o.genus, o.institutionID, o.collectionID, '.
		'o.specificEpithet, o.taxonRank, o.infraspecificEpithet, '.
		'o.scientificNameAuthorship, o.taxonRemarks, o.identifiedBy, o.dateIdentified, o.identificationReferences, '.
		'o.identificationRemarks, o.identificationQualifier, o.typeStatus, o.recordedBy, o.recordNumber, '.
		'o.associatedCollectors, o.eventdate, o.year, o.month, o.day, o.startDayOfYear, o.endDayOfYear, '.
		'o.verbatimEventDate, o.habitat, o.substrate, o.occurrenceRemarks, o.associatedTaxa, o.verbatimAttributes, '.
		'o.dynamicProperties, o.reproductiveCondition, o.cultivationStatus, o.establishmentMeans, o.country, '.
		'o.stateProvince, o.county, o.municipality, o.locality, o.localitySecurity, o.localitySecurityreason, o.decimalLatitude, o.decimalLongitude, '.
		'o.geodeticDatum, o.coordinateUncertaintyInMeters, o.coordinatePrecision, o.locationRemarks, o.verbatimCoordinates, '.
		'o.georeferencedBy, o.georeferenceProtocol, o.georeferenceSources, '.
		'o.georeferenceVerificationStatus, o.georeferenceRemarks, o.minimumElevationInMeters, o.maximumElevationInMeters, '.
		'o.verbatimElevation, o.disposition, o.modified, o.language, o.duplicateQuantity, o.labelProject, o.observeruid, '.
		'o.dateLastModified, o.processingstatus, o.recordEnteredBy '.
		'FROM omoccurrences o ';
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
	
	public function setOccId($id){
		if(is_numeric($id)){
			$this->occId = $this->conn->real_escape_string($id);
		}
	}
	
	public function getOccId(){
		return $this->occId;
	}

	public function setCollId($id){
		if(is_numeric($id)){
			$this->collId = $this->conn->real_escape_string($id);
		}
	}

	public function getCollMap(){
		if(!$this->collMap){
			if($this->collId || $this->occId){
				$sql = 'SELECT c.collid, c.collectionname, c.institutioncode, c.collectioncode, c.colltype, c.managementtype '.
					'FROM omcollections c ';
				if($this->collId){
					$sql .= 'WHERE (c.collid = '.$this->collId.')';
				}
				elseif($this->occId){
					$sql .= 'INNER JOIN omoccurrences o ON c.collid = o.collid '.
						'WHERE (o.occid = '.$this->occId.')';
				}
				$rs = $this->conn->query($sql);
				if($row = $rs->fetch_object()){
					$this->collMap['collid'] = $row->collid;
					!$this->collId = $row->collid;
					$this->collMap['collectionname'] = $row->collectionname;
					$this->collMap['institutioncode'] = $row->institutioncode;
					$this->collMap['collectioncode'] = $row->collectioncode;
					$this->collMap['colltype'] = $row->colltype;
					$this->collMap['managementtype'] = $row->managementtype;
				}
				$rs->free();
			}
		}
		return $this->collMap;
	}
	
	public function setSymbUid($id){
		$this->symbUid = $id;
	}
	
	public function setQueryVariables($overrideQry = ''){
		global $clientRoot;
		if($overrideQry){
			$this->qryArr = $overrideQry;
			setCookie('editorquery','',time()-3600,($clientRoot?$clientRoot:'/'));
		}
		elseif(array_key_exists('q_identifier',$_REQUEST)){
			if($_REQUEST['q_identifier']) $this->qryArr['id'] = trim($_REQUEST['q_identifier']);
			if(array_key_exists('q_othercatalognumbers',$_POST) && $_POST['q_othercatalognumbers']) $this->qryArr['ocn'] = trim($_POST['q_othercatalognumbers']);
			if(array_key_exists('q_recordedby',$_POST) && $_POST['q_recordedby']) $this->qryArr['rb'] = trim($_POST['q_recordedby']);
			if(array_key_exists('q_recordnumber',$_POST) && $_POST['q_recordnumber']) $this->qryArr['rn'] = trim($_POST['q_recordnumber']);
			if(array_key_exists('q_eventdate',$_POST) && $_POST['q_eventdate']) $this->qryArr['ed'] = trim($_POST['q_eventdate']);
			if(array_key_exists('q_enteredby',$_POST) && $_POST['q_enteredby']) $this->qryArr['eb'] = trim($_POST['q_enteredby']);
			if(array_key_exists('q_observeruid',$_POST) && $_POST['q_observeruid']) $this->qryArr['ouid'] = $_POST['q_observeruid'];
			if(array_key_exists('q_processingstatus',$_POST) && $_POST['q_processingstatus']) $this->qryArr['ps'] = trim($_POST['q_processingstatus']); 
			if(array_key_exists('q_datelastmodified',$_POST) && $_POST['q_datelastmodified']) $this->qryArr['dm'] = trim($_POST['q_datelastmodified']);
			if(array_key_exists('q_customfield1',$_POST) && $_POST['q_customfield1']) $this->qryArr['cf1'] = $_POST['q_customfield1'];
			if(array_key_exists('q_customtype1',$_POST) && $_POST['q_customtype1']) $this->qryArr['ct1'] = $_POST['q_customtype1'];
			if(array_key_exists('q_customvalue1',$_POST) && $_POST['q_customvalue1']) $this->qryArr['cv1'] = trim($_POST['q_customvalue1']);
			setCookie('editorquery','',time()-3600,($clientRoot?$clientRoot:'/'));
		}
		elseif(isset($_COOKIE["editorquery"])){
			$this->qryArr = json_decode($_COOKIE["editorquery"],true);
		}
	}
	
	public function getQueryVariables(){
		return $this->qryArr;
	}
	
	public function setSqlWhere($occIndex=0, $recLimit = 1){
		$sqlWhere = '';
		$sqlOrderBy = '';
		if(array_key_exists('id',$this->qryArr)){
			$iArr = explode(',',$this->qryArr['id']);
			$iBetweenFrag = array();
			$iInFrag = array();
			$searchIsNum = false;
			foreach($iArr as $v){
				if($p = strpos($v,' - ')){
					$term1 = trim(substr($v,0,$p));
					$term2 = trim(substr($v,$p+3));
					if(is_numeric($term1) && is_numeric($term2)){
						$searchIsNum = true; 
						$iBetweenFrag[] = '(o.catalogNumber BETWEEN '.$term1.' AND '.$term2.')';
						$iBetweenFrag[] = '(o.occurrenceId BETWEEN '.$term1.' AND '.$term2.')';
						$iBetweenFrag[] = '(o.occid BETWEEN '.$term1.' AND '.$term2.')';
					}
					else{
						$catTerm = 'o.catalogNumber BETWEEN "'.$term1.'" AND "'.$term2.'"';
						if(strlen($term1) == strlen($term2)) $catTerm .= ' AND length(o.catalogNumber) = '.strlen($term2); 
						$iBetweenFrag[] = '('.$catTerm.')';
						$iBetweenFrag[] = '(o.occurrenceId BETWEEN "'.$term1.'" AND "'.$term2.'")';
					}
				}
				else{
					$iInFrag[] = trim($v);
				}
			}
			$iWhere = '';
			if($iBetweenFrag){
				$iWhere .= 'OR '.implode(' OR ',$iBetweenFrag);
				if($searchIsNum){
					$sqlOrderBy .= ',(o.catalogNumber+1)';
				}
				else{
					$sqlOrderBy .= ',o.catalogNumber';
				}
			}
			if($iInFrag){
				$iWhere .= 'OR (o.catalogNumber IN("'.implode('","',$iInFrag).'") OR o.occurrenceId IN("'.implode('","',$iInFrag).'") OR o.occid IN("'.implode('","',$iInFrag).'")) ';
			}
			$sqlWhere .= 'AND ('.substr($iWhere,3).') ';
		}
		//otherCatalogNumbers
		if(array_key_exists('ocn',$this->qryArr)){
			$ocnIsNum = false;
			$ocnArr = explode(',',$this->qryArr['ocn']);
			$ocnBetweenFrag = array();
			$ocnInFrag = array();
			foreach($ocnArr as $v){
				if(strpos('%',$v) !== false){
					$ocnBetweenFrag[] = '(o.othercatalognumbers LIKE "'.$term1.'")';
				}
				elseif($p = strpos($v,' - ')){
					$term1 = trim(substr($v,0,$p));
					$term2 = trim(substr($v,$p+3));
					if(is_numeric($term1) && is_numeric($term2)){
						$ocnIsNum = true;
						$ocnBetweenFrag[] = '(o.othercatalognumbers BETWEEN '.$term1.' AND '.$term2.')';
					}
					else{
						$ocnTerm = 'o.othercatalognumbers BETWEEN "'.$term1.'" AND "'.$term2.'"';
						if(strlen($term1) == strlen($term2)) $ocnTerm .= ' AND length(o.othercatalognumbers) = '.strlen($term2); 
						$ocnBetweenFrag[] = '('.$ocnTerm.')';
					}
				}
				else{
					if(is_numeric($v)) $ocnIsNum = true;
					$ocnInFrag[] = trim($v);
				}
			}
			$ocnWhere = '';
			if($ocnBetweenFrag){
				$ocnWhere .= 'OR '.implode(' OR ',$ocnBetweenFrag);
			}
			if($ocnInFrag){
				$ocnWhere .= 'OR (o.othercatalognumbers IN("'.implode('","',$ocnInFrag).'")) ';
			}
			$sqlOrderBy .= ',(o.othercatalognumbers'.($ocnIsNum?'+1':'').')';
			$sqlWhere .= 'AND ('.substr($ocnWhere,3).') ';
		}
		//recordNumber: collector's number
		$rnIsNum = false;
		if(array_key_exists('rn',$this->qryArr)){
			$rnArr = explode(',',$this->qryArr['rn']);
			$rnBetweenFrag = array();
			$rnInFrag = array();
			foreach($rnArr as $v){
				if($p = strpos($v,' - ')){
					$term1 = trim(substr($v,0,$p));
					$term2 = trim(substr($v,$p+3));
					if(is_numeric($term1) && is_numeric($term2)){
						$rnIsNum = true;
						$rnBetweenFrag[] = '(o.recordnumber BETWEEN '.$term1.' AND '.$term2.')';
					}
					else{
						$catTerm = 'o.recordnumber BETWEEN "'.$term1.'" AND "'.$term2.'"';
						if(strlen($term1) == strlen($term2)) $catTerm .= ' AND length(o.recordnumber) = '.strlen($term2); 
						$rnBetweenFrag[] = '('.$catTerm.')';
					}
				}
				else{
					$rnInFrag[] = trim($v);
				}
			}
			$rnWhere = '';
			if($rnBetweenFrag){
				$rnWhere .= 'OR '.implode(' OR ',$rnBetweenFrag);
			}
			if($rnInFrag){
				$rnWhere .= 'OR (o.recordnumber IN("'.implode('","',$rnInFrag).'")) ';
			}
			$sqlWhere .= 'AND ('.substr($rnWhere,3).') ';
		}
		if(array_key_exists('rb',$this->qryArr)){
			$sqlWhere .= 'AND (o.recordedby LIKE "'.$this->qryArr['rb'].'%") ';
			$sqlOrderBy .= ',(o.recordnumber+1)';
		}
		if(array_key_exists('ed',$this->qryArr)){
			if($p = strpos($this->qryArr['ed'],' - ')){
				$sqlWhere .= 'AND (o.eventdate BETWEEN "'.substr($this->qryArr['ed'],0,$p).'" AND "'.substr($this->qryArr['ed'],$p+3).'") ';
			}
			else{
				$sqlWhere .= 'AND (o.eventdate = "'.$this->qryArr['ed'].'") ';
			}
			$sqlOrderBy .= ',o.eventdate';
		}
		if(array_key_exists('eb',$this->qryArr)){
			$sqlWhere .= 'AND (o.recordEnteredBy LIKE "'.$this->qryArr['eb'].'%") ';
		}
		if(array_key_exists('ouid',$this->qryArr)){
			$sqlWhere .= 'AND (o.observeruid = '.$this->qryArr['ouid'].') ';
		}
		if(array_key_exists('dm',$this->qryArr)){
			if($p = strpos($this->qryArr['dm'],' - ')){
				$sqlWhere .= 'AND (DATE(o.datelastmodified) BETWEEN "'.substr($this->qryArr['dm'],0,$p).'" AND "'.substr($this->qryArr['dm'],$p+3).'") ';
			}
			else{
				$sqlWhere .= 'AND (DATE(o.datelastmodified) = "'.$this->qryArr['dm'].'") ';
			}
			
			$sqlOrderBy .= ',o.datelastmodified';
		}
		if(array_key_exists('ps',$this->qryArr)){
			$sqlWhere .= 'AND (o.processingstatus LIKE "'.$this->qryArr['ps'].'%") ';
		}
		if(array_key_exists('cv1',$this->qryArr) && array_key_exists('cf1',$this->qryArr)){
			$cf1 = $this->qryArr['cf1'];
			$ct1 = $this->qryArr['ct1'];
			$cv1 = $this->qryArr['cv1'];
			if($cf1 && $cv1){
				if($ct1=='IS NULL'){
					$sqlWhere .= 'AND (o.'.$cf1.' IS NULL) ';
				}
				elseif($ct1=='LIKE'){
					if(strpos($cv1,'%') !== false){
						$sqlWhere .= 'AND (o.'.$cf1.' LIKE "'.$cv1.'") ';
					}
					else{
						$sqlWhere .= 'AND (o.'.$cf1.' LIKE "%'.$cv1.'%") ';
					}
				}
				else{
					$sqlWhere .= 'AND (o.'.$cf1.' = "'.$cv1.'") ';
				}
			}
		}
		if(array_key_exists('cv2',$this->qryArr) && array_key_exists('cf2',$this->qryArr)){
			$cf2 = $this->qryArr['cf2'];
			$ct2 = $this->qryArr['ct2'];
			$cv2 = $this->qryArr['cv2'];
			if($cf2 && $cv2){
				if($ct1=='IS NULL'){
					$sqlWhere .= 'AND (o.'.$cf2.' IS NULL) ';
				}
				elseif($ct2=='LIKE'){
					if(strpos($cv2,'%') !== false){
						$sqlWhere .= 'AND (o.'.$cf2.' LIKE "'.$cv2.'") ';
					}
					else{
						$sqlWhere .= 'AND (o.'.$cf2.' LIKE "%'.$cv2.'%") ';
					}
				}
				else{
					$sqlWhere .= 'AND (o.'.$cf2.' = "'.$cv2.'") ';
				}
			}
		}
		if(array_key_exists('cv3',$this->qryArr) && array_key_exists('cf3',$this->qryArr)){
			$cf3 = $this->qryArr['cf3'];
			$ct3 = $this->qryArr['ct3'];
			$cv3 = $this->qryArr['cv3'];
			if($cf3 && $cv3){
				if($ct3=='IS NULL'){
					$sqlWhere .= 'AND (o.'.$cf3.' IS NULL) ';
				}
				elseif($ct3=='LIKE'){
					if(strpos($cv3,'%') !== false){
						$sqlWhere .= 'AND (o.'.$cf3.' LIKE "'.$cv3.'") ';
					}
					else{
						$sqlWhere .= 'AND (o.'.$cf3.' LIKE "%'.$cv3.'%") ';
					}
				}
				else{
					$sqlWhere .= 'AND (o.'.$cf3.' = "'.$cv3.'") ';
				}
			}
		}
		if($sqlWhere){
			$sqlWhere = 'WHERE (o.collid = '.$this->collId.') '.$sqlWhere;
			if($sqlOrderBy) $sqlWhere .= 'ORDER BY '.substr($sqlOrderBy,1).' ';
			$sqlWhere .= 'LIMIT '.($occIndex>0?$occIndex.',':'').$recLimit;
		}
		//echo $sqlWhere;
		$this->sqlWhere = $sqlWhere;
	}
	
	public function getQueryRecordCount($reset = 0){
		global $clientRoot;
		if(!$reset && array_key_exists('rc',$this->qryArr)) return $this->qryArr['rc'];
		$sqlWhere = $this->sqlWhere;
		$recCnt = false;
		if($sqlWhere){
			if($obPos = strpos($sqlWhere,' ORDER BY')){
				$sqlWhere = substr($sqlWhere,0,$obPos);
			}
			if($obPos = strpos($sqlWhere,' LIMIT ')){
				$sqlWhere = substr($sqlWhere,0,$obPos);
			}
			$sql = 'SELECT COUNT(*) AS reccnt FROM omoccurrences o '.$sqlWhere;
			//echo '<div>'.$sql.'</div>';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$recCnt = $r->reccnt;
			}
			$rs->free();
			$this->qryArr['rc'] = (int)$recCnt;
			setCookie('editorquery',json_encode($this->qryArr),0,($clientRoot?$clientRoot:'/'));
		}
		return $recCnt;
	}

	public function getOccurMap(){
		if(!$this->occurrenceMap && ($this->occId || $this->sqlWhere)){
			$this->setOccurArr();
		}
		return $this->occurrenceMap;
	}

	private function setOccurArr(){
		$retArr = Array();
		$sql = '';
		if($this->occId){
			$sql = $this->occSql.'WHERE (o.occid = '.$this->occId.')';
		}
		elseif($this->sqlWhere){
			$sql = $this->occSql.(strpos($this->sqlWhere,'recordedby')?'use index(Index_collector) ':'').$this->sqlWhere;
		}
		if($sql){
			//echo "<div>".$sql."</div>";
			$occid = 0;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_assoc()){
				$occid = $row['occid'];
				$retArr[$occid] = array_change_key_case($row);
			}
			$rs->free();
			if(!$this->occId && $retArr && count($retArr) == 1) $this->occId = $occid; 
			$this->occurrenceMap = $retArr;
			//if($this->occId) $this->setLoanData();
		}
	}

	public function editOccurrence($occArr,$autoCommit){
		global $paramsArr;
		$status = '';
		if(!$autoCommit && $this->getObserverUid() == $paramsArr['uid']) $autoCommit = 1;
		$editedFields = trim($occArr['editedfields']);
		$editArr = array_unique(explode(';',$editedFields));
		foreach($editArr as $k => $v){
			if(!trim($v)) unset($editArr[$k]);
		}
		if($editArr){
			//Add edits to omoccuredits
			//Get old values before they are changed
			$sql = 'SELECT '.implode(',',$editArr).(in_array('processingstatus',$editArr)?'':',processingstatus').
				',recordenteredby FROM omoccurrences WHERE occid = '.$occArr['occid'];
			$rs = $this->conn->query($sql);
			$oldValues = $rs->fetch_assoc();
			$rs->free();
			//Version edits only if old processing status does not equal "unprocessed"
			if($oldValues['processingstatus'] != 'unprocessed'){ 
				$sqlEditsBase = 'INSERT INTO omoccuredits(occid,reviewstatus,appliedstatus,uid,fieldname,fieldvaluenew,fieldvalueold) '.
					'VALUES ('.$occArr['occid'].',1,'.($autoCommit?'1':'0').','.$paramsArr['uid'].',';
				foreach($editArr as $v){
					$v = trim($v);
					if(!array_key_exists($v,$occArr)){
						//Field is a checkbox that is unchecked
						$occArr[$v] = 0;
					}
					$newValue = $this->cleanStr($occArr[$v]);
					$oldValue = $this->cleanStr($oldValues[$v]);
					//Version edits only if value has changed and old value was not null 
					if($v && $oldValue && $oldValue != $newValue){
						$sqlEdit = $sqlEditsBase.'"'.$v.'","'.$newValue.'","'.$oldValue.'")';
						//echo '<div>'.$sqlEdit.'</div>';
						$this->conn->query($sqlEdit);
					}
				}
			}
			//Edit record only if user is authorized to autoCommit 
			if($autoCommit){
				$status = 'SUCCESS: edits submitted and activated';
				//If processing status was "unprocessed" and recordEnteredBy is null, populate with user login
				$sql = '';
				if($oldValues['processingstatus'] == 'unprocessed' && !$oldValues['recordenteredby']){
					$sql = ',recordenteredby = "'.$paramsArr['un'].'"';
				}
				if(array_key_exists('autoprocessingstatus',$occArr) && $occArr['autoprocessingstatus']){
					$sql .= ',processingstatus = "'.$occArr['autoprocessingstatus'].'"';
					$k2d = array_search('processingstatus',$editArr);
					if($k2d) unset($editArr[$k2d]);
				}
				foreach($editArr as $v){
					if($v && array_key_exists($v,$occArr)){
						$vStr = $this->cleanStr($occArr[$v]);
						$sql .= ','.$v.' = '.($vStr!==''?'"'.$vStr.'"':'NULL');
						if(array_key_exists($this->occId,$this->occurrenceMap) && array_key_exists($v,$this->occurrenceMap[$this->occId])){
							$this->occurrenceMap[$this->occId][$v] = $vStr;
						}
					}
				}
				if(in_array('sciname',$editArr)){
					$sqlTid = 'SELECT tid FROM taxa WHERE (sciname = "'.$occArr['sciname'].'")';
					$rsTid = $this->conn->query($sqlTid);
					$newTid = '';
					if($r = $rsTid->fetch_object()){
						$newTid = $r->tid;
						$sql .= ',tidinterpreted = '.$newTid;
					}
					else{
						$sql .= ',tidinterpreted = NULL';
					}
					//Remap images
					$sqlImgTid = 'UPDATE images SET tid = '.($newTid?$newTid:'NULL').' WHERE occid = ('.$occArr['occid'].')';
					$this->conn->query($sqlImgTid);
				}
				$sql = 'UPDATE omoccurrences SET '.substr($sql,1).' WHERE (occid = '.$occArr['occid'].')';
				//echo $sql;
				if(!$this->conn->query($sql)){
					$status = 'ERROR: failed to edit occurrence record (#'.$occArr['occid'].'): '.$this->conn->error;
				}
			}
			else{
				$status = 'Edits submitted, but not activated.<br/> '.
					'Once edits are reviewed and approved by a data manager, they will be activated.<br/> '.
					'Thank you for aiding us in improving the data. ';
			}
		}
		else{
			$status = 'ERROR: edits empty for occid #'.$occArr['occid'].': '.$this->conn->error;
		}
		return $status;
	}

	public function addOccurrence($occArr){
		$status = "SUCCESS: new occurrence record submitted successfully";

		if($occArr){
			$sql = "INSERT INTO omoccurrences(collid, basisOfRecord, occurrenceID, catalogNumber, otherCatalogNumbers, ".
			"ownerInstitutionCode, family, sciname, tidinterpreted, scientificNameAuthorship, identifiedBy, dateIdentified, ".
			"identificationReferences, identificationremarks, identificationQualifier, typeStatus, recordedBy, recordNumber, ".
			"associatedCollectors, eventDate, year, month, day, startDayOfYear, endDayOfYear, ".
			"verbatimEventDate, habitat, substrate, occurrenceRemarks, associatedTaxa, verbatimattributes, ".
			"dynamicProperties, reproductiveCondition, cultivationStatus, establishmentMeans, country, ".
			"stateProvince, county, locality, localitySecurity, localitysecurityreason, decimalLatitude, decimalLongitude, ".
			"geodeticDatum, coordinateUncertaintyInMeters, verbatimCoordinates, ".
			"georeferencedBy, georeferenceProtocol, georeferenceSources, ".
			"georeferenceVerificationStatus, georeferenceRemarks, minimumElevationInMeters, maximumElevationInMeters, ".
			"verbatimElevation, disposition, language, duplicateQuantity, labelProject, processingstatus, recordEnteredBy, observeruid) ".
			"VALUES (".$occArr["collid"].",".
			($occArr["basisofrecord"]?"\"".$occArr["basisofrecord"]."\"":"NULL").",".
			($occArr["occurrenceid"]?"\"".$occArr["occurrenceid"]."\"":"NULL").",".
			($occArr["catalognumber"]?"\"".$occArr["catalognumber"]."\"":"NULL").",".
			($occArr["othercatalognumbers"]?"\"".$occArr["othercatalognumbers"]."\"":"NULL").",".
			($occArr["ownerinstitutioncode"]?"\"".$occArr["ownerinstitutioncode"]."\"":"NULL").",".
			($occArr["family"]?"\"".$occArr["family"]."\"":"NULL").",".
			"\"".$occArr["sciname"]."\",".
			($occArr["tidtoadd"]?$occArr["tidtoadd"]:"NULL").",".
			($occArr["scientificnameauthorship"]?"\"".$this->conn->real_escape_string($occArr["scientificnameauthorship"])."\"":"NULL").",".
			($occArr["identifiedby"]?"\"".$occArr["identifiedby"]."\"":"NULL").",".
			($occArr["dateidentified"]?"\"".$occArr["dateidentified"]."\"":"NULL").",".
			($occArr["identificationreferences"]?"\"".$occArr["identificationreferences"]."\"":"NULL").",".
			($occArr["identificationremarks"]?"\"".$occArr["identificationremarks"]."\"":"NULL").",".
			($occArr["identificationqualifier"]?"\"".$occArr["identificationqualifier"]."\"":"NULL").",".
			($occArr["typestatus"]?"\"".$occArr["typestatus"]."\"":"NULL").",".
			($occArr["recordedby"]?"\"".$occArr["recordedby"]."\"":"NULL").",".
			($occArr["recordnumber"]?"\"".$occArr["recordnumber"]."\"":"NULL").",".
			($occArr["associatedcollectors"]?"\"".$this->conn->real_escape_string($occArr["associatedcollectors"])."\"":"NULL").",".
			($occArr["eventdate"]?"'".$occArr["eventdate"]."'":"NULL").",".
			($occArr["year"]?$occArr["year"]:"NULL").",".
			($occArr["month"]?$occArr["month"]:"NULL").",".
			($occArr["day"]?$occArr["day"]:"NULL").",".
			($occArr["startdayofyear"]?$occArr["startdayofyear"]:"NULL").",".
			($occArr["enddayofyear"]?$occArr["enddayofyear"]:"NULL").",".
			($occArr["verbatimeventdate"]?"\"".$this->conn->real_escape_string($occArr["verbatimeventdate"])."\"":"NULL").",".
			($occArr["habitat"]?"\"".$this->conn->real_escape_string($occArr["habitat"])."\"":"NULL").",".
			($occArr["substrate"]?"\"".$this->conn->real_escape_string($occArr["substrate"])."\"":"NULL").",".
			($occArr["occurrenceremarks"]?"\"".$this->conn->real_escape_string($occArr["occurrenceremarks"])."\"":"NULL").",".
			($occArr["associatedtaxa"]?"\"".$this->conn->real_escape_string($occArr["associatedtaxa"])."\"":"NULL").",".
			($occArr["verbatimattributes"]?"\"".$this->conn->real_escape_string($occArr["verbatimattributes"])."\"":"NULL").",".
			($occArr["dynamicproperties"]?"\"".$this->conn->real_escape_string($occArr["dynamicproperties"])."\"":"NULL").",".
			($occArr["reproductivecondition"]?"\"".$occArr["reproductivecondition"]."\"":"NULL").",".
			(array_key_exists("cultivationstatus",$occArr)?"1":"0").",".
			($occArr["establishmentmeans"]?"\"".$this->conn->real_escape_string($occArr["establishmentmeans"])."\"":"NULL").",".
			($occArr["country"]?"\"".$occArr["country"]."\"":"NULL").",".
			($occArr["stateprovince"]?"\"".$occArr["stateprovince"]."\"":"NULL").",".
			($occArr["county"]?"\"".$occArr["county"]."\"":"NULL").",".
			($occArr["locality"]?"\"".$this->conn->real_escape_string($occArr["locality"])."\"":"NULL").",".
			(array_key_exists("localitysecurity",$occArr)?"1":"0").",".
			($occArr["localitysecurityreason"]?$occArr["localitysecurityreason"]:"NULL").",".
			($occArr["decimallatitude"]?$occArr["decimallatitude"]:"NULL").",".
			($occArr["decimallongitude"]?$occArr["decimallongitude"]:"NULL").",".
			($occArr["geodeticdatum"]?"\"".$this->conn->real_escape_string($occArr["geodeticdatum"])."\"":"NULL").",".
			($occArr["coordinateuncertaintyinmeters"]?"\"".$occArr["coordinateuncertaintyinmeters"]."\"":"NULL").",".
			($occArr["verbatimcoordinates"]?"\"".$this->cleanStr($occArr["verbatimcoordinates"])."\"":"NULL").",".
			($occArr["georeferencedby"]?"\"".$occArr["georeferencedby"]."\"":"NULL").",".
			($occArr["georeferenceprotocol"]?"\"".$this->conn->real_escape_string($occArr["georeferenceprotocol"])."\"":"NULL").",".
			($occArr["georeferencesources"]?"\"".$this->conn->real_escape_string($occArr["georeferencesources"])."\"":"NULL").",".
			($occArr["georeferenceverificationstatus"]?"\"".$occArr["georeferenceverificationstatus"]."\"":"NULL").",".
			($occArr["georeferenceremarks"]?"\"".$this->conn->real_escape_string($occArr["georeferenceremarks"])."\"":"NULL").",".
			($occArr["minimumelevationinmeters"]?$occArr["minimumelevationinmeters"]:"NULL").",".
			($occArr["maximumelevationinmeters"]?$occArr["maximumelevationinmeters"]:"NULL").",".
			($occArr["verbatimelevation"]?"\"".$this->conn->real_escape_string($occArr["verbatimelevation"])."\"":"NULL").",".
			($occArr["disposition"]?"\"".$occArr["disposition"]."\"":"NULL").",".
			($occArr["language"]?"\"".$occArr["language"]."\"":"NULL").",".
			($occArr["duplicatequantity"]?$occArr["duplicatequantity"]:"NULL").",".
			($occArr["labelproject"]?"\"".$occArr["labelproject"]."\"":"NULL").",".
			($occArr["processingstatus"]?"\"".$occArr["processingstatus"]."\"":"NULL").",\"".
			$occArr["userid"]."\",".$occArr["observeruid"].") ";
			//echo "<div>".$sql."</div>";
			if($this->conn->query($sql)){
				$this->occId = $this->conn->insert_id;
			}
			else{
				$status = "ERROR - failed to add occurrence record: ".$this->conn->error;
			}
		}
		return $status;
	}
	
	public function deleteOccurrence($occId){
		$status = '';
		if(is_numeric($occId)){
			$sql = 'DELETE FROM omoccurrences WHERE (occid = '.$occId.')';
			if($this->conn->query($sql)){
				$status = 'SUCCESS: Occurrence Record Deleted!';
			}
			else{
				$status = 'FAILED: unable to delete occurrence record';
			}
		}
		return $status;
	}
	
	public function setLoanData(){
		$sql = 'SELECT l.loanid, l.datedue '.
			'FROM omoccurloanslink ll INNER JOIN omoccurloans l ON ll.loanid = l.loanid '.
			'WHERE ll.returndate IS NULL AND l.dateclosed IS NULL AND occid = '.$this->occId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->occurrenceMap[$this->occId]['loan']['id'] = $r->loanid;
			$this->occurrenceMap[$this->occId]['loan']['date'] = $r->datedue;
		}
		$rs->free();
	}
	
	public function getObserverUid(){
		$obsId = 0;
		if($this->occurrenceMap && array_key_exists('observeruid',$this->occurrenceMap[$this->occId])){
			$obsId = $this->occurrenceMap[$this->occId]['observeruid'];
		}
		elseif($this->occId){
			$this->setOccurArr();
			$obsId = $this->occurrenceMap[$this->occId]['observeruid'];
		}
		return $obsId;
	}
	
	public function echoCountryList(){
		$retArr = Array();
		$sql = 'SELECT countryname FROM lkupcountry ORDER BY countryname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[] = $r->countryname;
		}
		$rs->free();
		echo '"'.implode('","',$retArr).'"';
	}

	public function batchUpdateField($fieldName,$oldValue,$newValue,$buMatch){
		$statusStr = '';
		$fn = $this->conn->real_escape_string($fieldName);
		$ov = $this->conn->real_escape_string($oldValue);
		$nv = $this->conn->real_escape_string($newValue);
		if($fn && $ov && $nv){
			$sql = 'UPDATE omoccurrences o2 INNER JOIN (SELECT occid FROM omoccurrences o ';
			if(strpos($this->sqlWhere,'ORDER BY')){
				$sql .= substr($this->sqlWhere,0,strpos($this->sqlWhere,'ORDER BY'));
			}
			elseif(strpos($this->sqlWhere,'LIMIT')){
				$sql .= substr($this->sqlWhere,0,strpos($this->sqlWhere,'LIMIT'));
			}
			else{
				$sql .= $this->sqlWhere;
			}
			if($buMatch){
				$sql .= 'AND (o.'.$fn.' LIKE "%'.$ov.'%")'.
					') rt ON o2.occid = rt.occid SET o2.'.$fn.' = REPLACE(o2.'.$fn.',"'.$ov.'","'.$nv.'") ';
			}
			else{
				$sql .= 'AND (o.'.$fn.' = "'.$ov.'")'.
					') rt ON o2.occid = rt.occid SET o2.'.$fn.' = "'.$nv.'" ';
			}
			//echo $sql;
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR: unable to run batch update => '.$this->conn->error;
			}
		}
		return $statusStr;
	}
	
	public function getBatchUpdateCount($fieldName,$oldValue,$buMatch){
		$fn = $this->conn->real_escape_string($fieldName);
		$ov = $this->conn->real_escape_string($oldValue);
		$sql = 'SELECT COUNT(*) AS retcnt FROM omoccurrences o ';
		if(strpos($this->sqlWhere,'ORDER BY')){
			$sql .= substr($this->sqlWhere,0,strpos($this->sqlWhere,'ORDER BY'));
		}
		elseif(strpos($this->sqlWhere,'LIMIT')){
			$sql .= substr($this->sqlWhere,0,strpos($this->sqlWhere,'LIMIT'));
		}
		else{
			$sql .= $this->sqlWhere;
		}
		if($buMatch){
			$sql .= ' AND (o.'.$fn.' LIKE "%'.$ov.'%")';
		}
		else{
			$sql .= ' AND (o.'.$fn.' = "'.$ov.'")';
		}
		$result = $this->conn->query($sql);
		$retCnt = '';
		while ($row = $result->fetch_object()) {
			$retCnt = $row->retcnt;
		}
		$result->free();
		return $retCnt;
	}

	public function carryOverValues($fArr){
		$locArr = Array('recordedby','associatedcollectors','eventdate','verbatimeventdate','month','day','year',
			'startdayofyear','enddayofyear','country','stateprovince','county','locality','decimallatitude','decimallongitude',
			'verbatimcoordinates','coordinateuncertaintyinmeters','geodeticdatum','minimumelevationinmeters',
			'maximumelevationinmeters','verbatimelevation','verbatimcoordinates','georeferencedby','georeferenceprotocol',
			'georeferencesources','georeferenceverificationstatus','georeferenceremarks','habitat','substrate',
			'associatedtaxa','basisofrecord','language','labelproject');
		return array_intersect_key($fArr,array_flip($locArr)); 
	}

	//Used in dupesearch.php
	public function getDupOccurrences($collName, $collNum, $collDate, $occidQuery, $oid, $runCnt = 0, $exsTitle = "", $exsNumber = ""){
		$collNum = $this->conn->real_escape_string($collNum);
		$collDate = $this->conn->real_escape_string($collDate);
		$retArr = array();
		$sql = 'SELECT c.CollectionName, c.institutioncode, c.collectioncode, '.
			'o.occid, o.collid AS colliddup, o.catalognumber, o.occurrenceid, o.othercatalognumbers, '.
			'o.family, o.sciname, o.tidinterpreted AS tidtoadd, o.scientificNameAuthorship, o.taxonRemarks, o.identifiedBy, o.dateIdentified, '.
			'o.identificationReferences, o.identificationRemarks, o.identificationQualifier, o.typeStatus, o.recordedBy, o.recordNumber, '.
			'o.associatedCollectors, o.eventdate, o.verbatimEventDate, o.habitat, o.substrate, o.occurrenceRemarks, o.associatedTaxa, '.
			'o.dynamicProperties, o.reproductiveCondition, o.cultivationStatus, o.establishmentMeans, '.
			'o.country, o.stateProvince, o.county, o.locality, o.decimalLatitude, o.decimalLongitude, '.
			'o.geodeticDatum, o.coordinateUncertaintyInMeters, o.coordinatePrecision, o.locationRemarks, o.verbatimCoordinates, '.
			'o.georeferencedBy, o.georeferenceProtocol, o.georeferenceSources, o.georeferenceVerificationStatus, o.georeferenceRemarks, '.
			'o.minimumElevationInMeters, o.maximumElevationInMeters, o.verbatimElevation, o.disposition ';
		if($occidQuery){
			$sql .= 'FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid ';
			if(strpos($occidQuery,',')){
				$sql .= 'WHERE (o.occid IN('.$occidQuery.')) ';
			}
			else{
				$sql .= 'WHERE (o.occid = '.$occidQuery.') ';
			}
			//echo $sql;
			$result = $this->conn->query($sql);
			while ($row = $result->fetch_assoc()) {
				$retArr[$row['occid']] = array_change_key_case($row);
			}
			$result->free();
		}
		elseif($collName && ($collNum || $collDate)){
			//Parse last name from collector's name 
			$lastName = "";
			$lastNameArr = explode(',',$this->conn->real_escape_string($collName));
			$lastNameArr = explode(';',$lastNameArr[0]);
			$lastNameArr = explode('&',$lastNameArr[0]);
			$lastNameArr = explode(' and ',$lastNameArr[0]);
			$lastNameArr = preg_match_all('/[A-Za-z]{3,}/',$lastNameArr[0],$match);
			if($match){
				if(count($match[0]) == 1){
					$lastName = $match[0][0];
				}
				elseif(count($match[0]) > 1){
					$lastName = $match[0][1];
				}
			}

			if($lastName){
				$sql .= 'FROM omcollections c INNER JOIN omoccurrences o USE INDEX(Index_collnum) ON c.collid = o.collid '.
					'WHERE (o.recordedby LIKE "%'.$lastName.'%") ';
				if($oid) $sql .= 'AND (o.occid != '.$oid.') ';
				if($runCnt == 0){
					//First run
					if($collNum){
						$sql .= 'AND (o.recordnumber LIKE "'.$collNum.'") ';
						//if($collDate) $sql .= 'AND (eventdate = "'.$collDate.'") ';
					}
					else{
						$sql .= 'AND (o.eventdate = "'.$collDate.'") '.
							'ORDER BY o.recordnumber ';
					}
		
					//echo $sql;
					$rs = $this->conn->query($sql);
					while($row = $rs->fetch_assoc()){
						$retArr[$row['occid']] = array_change_key_case($row);
					}
					$rs->free();
				}
				else{
					$runQry = true;
					if($collNum){
						if(is_numeric($collNum)){
							$nStart = $collNum - 4;
							if($nStart < 1) $nStart = 1;
							$nEnd = $collNum + 4;
							$sql .= 'AND (CAST(o.recordnumber AS SIGNED) BETWEEN '.$nStart.' AND '.$nEnd.') ';
						}
						elseif(preg_match('/^(\d+)-{0,1}[a-zA-Z]{1,2}$/',$collNum,$m)){
							//ex: 123a, 123b, 123-a
							$cNum = $m[1];
							$nStart = $cNum - 4;
							if($nStart < 1) $nStart = 1;
							$nEnd = $cNum + 4;
							$sql .= 'AND (CAST(o.recordnumber AS SIGNED) BETWEEN '.$nStart.' AND '.$nEnd.') ';
						}
						elseif(preg_match('/^(\D+-?)(\d+)-{0,1}[a-zA-Z]{0,2}$/',$collNum,$m)){
							//RM-123, RM123
							$prefix = $m[1];
							$num = $m[2];
							$nStart = $num - 5;
							if($nStart < 1) $nStart = 1;
							$rangeArr = array();
							for($x=1;$x<11;$x++){
								$rangeArr[] = $prefix.($nStart+$x);
							}
							$sql .= 'AND o.recordnumber IN("'.implode('","',$rangeArr).'") ';
						}
						elseif(preg_match('/^(\d{2,4}-{1})(\d+)-{0,1}[a-zA-Z]{0,2}$/',$collNum,$m)){
							//95-123, 1995-123
							$prefix = $m[1];
							$num = $m[2];
							$nStart = $num - 5;
							if($nStart < 1) $nStart = 1;
							$rangeArr = array();
							for($x=1;$x<11;$x++){
								$rangeArr[] = $prefix.($nStart+$x);
							}
							$sql .= 'AND o.recordnumber IN("'.implode('","',$rangeArr).'") ';
						}
						else{
							$runQry = false;
						}
						if($collDate) $sql .= 'AND (o.eventdate = "'.$collDate.'") ';
						//$sql .= 'ORDER BY o.recordnumber'; 
					}
					elseif($collDate){
						$sql .= 'AND (o.eventdate = "'.$collDate.'") ';
						$sql .= 'LIMIT 10';
					}
					else{
						$runQry = false;
					}
					//echo $sql;
					if($runQry){
						$result = $this->conn->query($sql);
						while ($row = $result->fetch_assoc()) {
							$retArr[$row['occid']] = array_change_key_case($row);
						}
						$result->free();
						
					}
				}
			}			
		}
		elseif($exsTitle && $exsNumber){
			$sql .= 'FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid '. 
				'INNER JOIN omexsiccatiocclink el ON o.occid = el.occid '.
				'INNER JOIN omexsiccatinumbers en ON el.omenid = en.omenid '.
				'INNER JOIN omexsiccatititles et ON en.ometid = et.ometid '.
				'WHERE (et.title = "'.$exsTitle.'" OR et.abbreviation = "'.$exsTitle.'") AND en.exsnumber = "'.$exsNumber.'" ';
			if($oid) $sql .= 'AND (o.occid != '.$oid.') ';
			//First run

			echo $sql;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_assoc()){
				$retArr[$row['occid']] = array_change_key_case($row);
			}
			$rs->free();
		}
		return $retArr;
	}
	
	public function mergeRecords($targetOccid,$sourceOccid){
		if(!$targetOccid || !$sourceOccid) return 'ERROR: target or source is null';
		if($targetOccid == $sourceOccid) return 'ERROR: target and source are equal';
		$status = true;
		
		$oArr = array();
		//Merge records
		$sql = 'SELECT * FROM omoccurrences WHERE occid = '.$targetOccid.' OR occid = '.$sourceOccid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_assoc()){
			$tempArr = array();
			foreach($r as $k => $v){
				$tempArr[strtolower($k)] = $v;
			}
			$id = $tempArr['occid'];
			unset($tempArr['occid']);
			unset($tempArr['collid']);
			unset($tempArr['dbpk']);
			unset($tempArr['datelastmodified']);
			$oArr[$id] = $tempArr;
		}
		$rs->free();

		$tArr = $oArr[$targetOccid];
		$sArr = $oArr[$sourceOccid];
		$sqlFrag = '';
		foreach($sArr as $k => $v){
			if(($v != '') && $tArr[$k] == ''){
				$sqlFrag .= ','.$k.'="'.$v.'"';
			} 
		}
		if($sqlFrag){
			//Remap source to target
			$sqlIns = 'UPDATE omoccurrences SET '.substr($sqlFrag,1).' WHERE occid = '.$targetOccid;
			//echo $sqlIns;
			$this->conn->query($sqlIns);
		}

		//Remap determinations
		$sql = 'UPDATE omoccurdeterminations SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap occurrence edits
		$sql = 'UPDATE omoccuredits SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap images
		$sql = 'UPDATE images SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap comments
		$sql = 'UPDATE omoccurcomments SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap exsiccati
		$sql = 'UPDATE omexsiccatiocclink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap occurrence dataset links
		$sql = 'UPDATE omoccurdatasetlink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap loans
		$sql = 'UPDATE omoccurloanslink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap checklists voucher links
		$sql = 'UPDATE fmvouchers SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap survey lists
		$sql = 'UPDATE omsurveyoccurlink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Delete source
		$sql = 'DELETE FROM omoccurrences WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			$status .= 'ERROR: unable to delete source occurrence (yet may have merged records): '.$this->conn->error;
		}
		return $status;
	}

	//Label OCR processing methods
	public function getRawTextFragments(){
		$retArr = array();
		if($this->occId){
			$sql = 'SELECT r.prlid, r.imgid, r.rawstr, r.notes '.
				'FROM specprocessorrawlabels r INNER JOIN images i ON r.imgid = i.imgid '.
				'WHERE i.occid = '.$this->occId;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->imgid][$r->prlid] = $r->rawstr;
			}
			$rs->free();
		} 
		return $retArr;
	}

	public function insertTextFragment($imgId, $rawFrag){
		if($imgId && $rawFrag){
			$statusStr = '';
			$sql = 'INSERT INTO specprocessorrawlabels(imgid,rawstr) '.
				'VALUES ('.$imgId.',"'.$this->cleanRawFragment($rawFrag).'")';
			//echo $sql;
			if($this->conn->query($sql)){
				$statusStr = $this->conn->insert_id;
			}
			else{
				$statusStr = 'ERROR: unable to INSERT text fragment; '.$this->conn->error;
			}
			return $statusStr;
		}
	}

	public function saveTextFragment($prlId, $rawFrag){
		if($prlId && $rawFrag){
			$statusStr = '';
			$sql = 'UPDATE specprocessorrawlabels SET rawstr = "'.$this->cleanRawFragment($rawFrag).'" '.
				'WHERE (prlid = '.$prlId.')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR: unable to UPDATE text fragment; '.$this->conn->error;
			}
			return $statusStr;
		}
	}

	public function deleteTextFragment($prlId){
		if($prlId){
			$statusStr = '';
			$sql = 'DELETE FROM specprocessorrawlabels '.
				'WHERE (prlid = '.$prlId.')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR: unable DELETE text fragment; '.$this->conn->error;
			}
			return $statusStr;
		}
	}

	public function getImageMap(){
		$imageMap = Array();
		if($this->occId){
			$sql = 'SELECT imgid, url, thumbnailurl, originalurl, caption, photographer, photographeruid, '.
				'sourceurl, copyright, notes, occid, sortsequence '.
				'FROM images '.
				'WHERE (occid = '.$this->occId.') ORDER BY sortsequence';
			//echo $sql;
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$imgId = $row->imgid;
				$imageMap[$imgId]["url"] = $row->url;
				$imageMap[$imgId]["tnurl"] = $row->thumbnailurl;
				$imageMap[$imgId]["origurl"] = $row->originalurl;
				$imageMap[$imgId]["caption"] = $row->caption;
				$imageMap[$imgId]["photographer"] = $row->photographer;
				$imageMap[$imgId]["photographeruid"] = $row->photographeruid;
				$imageMap[$imgId]["sourceurl"] = $row->sourceurl;
				$imageMap[$imgId]["copyright"] = $row->copyright;
				$imageMap[$imgId]["notes"] = $row->notes;
				$imageMap[$imgId]["occid"] = $row->occid;
				$imageMap[$imgId]["sortseq"] = $row->sortsequence;
			}
			$result->free();
		}
		return $imageMap;
	}

	//Misc functions
	protected function cleanStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	private function cleanRawFragment($str){
		$newStr = trim($str);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>