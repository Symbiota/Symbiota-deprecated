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
	private $symbUid;
	
	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
		$this->occSql = 'SELECT o.occid, o.collid, o.basisOfRecord, o.occurrenceID, o.catalogNumber, o.otherCatalogNumbers, '.
		'o.ownerInstitutionCode, o.family, o.scientificName, o.sciname, o.tidinterpreted, o.genus, o.institutionID, o.collectionID, '.
		'o.specificEpithet, o.taxonRank, o.infraspecificEpithet, '.
		'o.scientificNameAuthorship, o.taxonRemarks, o.identifiedBy, o.dateIdentified, o.identificationReferences, '.
		'o.identificationRemarks, o.identificationQualifier, o.typeStatus, o.recordedBy, o.recordNumber, '.
		'o.associatedCollectors, o.eventdate, o.year, o.month, o.day, o.startDayOfYear, o.endDayOfYear, '.
		'o.verbatimEventDate, o.habitat, o.occurrenceRemarks, o.associatedTaxa, o.verbatimAttributes, '.
		'o.dynamicProperties, o.reproductiveCondition, o.cultivationStatus, o.establishmentMeans, o.country, '.
		'o.stateProvince, o.county, o.locality, o.localitySecurity, o.localitySecurityreason, o.decimalLatitude, o.decimalLongitude, '.
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
				$rs->close();
			}
		}
		return $this->collMap;
	}
	
	public function setSymbUid($id){
		$this->symbUid = $id;
	}
	
	public function getQueryVariables(){
		global $clientRoot;
		$qryArr = array();
		if(array_key_exists('q_identifier',$_POST)){
			if($_POST['q_identifier']) $qryArr['id'] = trim($_POST['q_identifier']);
			if($_POST['q_recordedby']) $qryArr['rb'] = trim($_POST['q_recordedby']);
			if($_POST['q_recordnumber']) $qryArr['rn'] = trim($_POST['q_recordnumber']);
			if($_POST['q_enteredby']) $qryArr['eb'] = trim($_POST['q_enteredby']);
			if($_POST['q_processingstatus']) $qryArr['ps'] = trim($_POST['q_processingstatus']); 
			if($_POST['q_datelastmodified']) $qryArr['dm'] = trim($_POST['q_datelastmodified']);
			setCookie('editorquery','',time()-3600,($clientRoot?$clientRoot:'/'));
		}
		elseif(isset($_COOKIE["editorquery"])){
			$qryArr = json_decode($_COOKIE["editorquery"],true);
		}
		return $qryArr;
	}
	
	public function getQueryWhere($qryArr, $occIndex=0, $isAdmin=0){
		$sqlWhere = '';
		$sqlOrderBy = '';
		if(array_key_exists('id',$qryArr)){
			$iArr = explode(',',$qryArr['id']);
			$iBetweenFrag = array();
			$iInFrag = array();
			foreach($iArr as $v){
				if($p = strpos($v,' - ')){
					$iBetweenFrag[] = 'o.catalogNumber BETWEEN "'.substr($v,0,$p).'" AND "'.substr($v,$p+3).'" ';
					$iBetweenFrag[] = 'o.occurrenceId BETWEEN "'.substr($v,0,$p).'" AND "'.substr($v,$p+3).'" ';
					$iBetweenFrag[] = 'o.othercatalognumbers BETWEEN "'.substr($v,0,$p).'" AND "'.substr($v,$p+3).'" ';
				}
				else{
					$iInFrag[] = $v;
				}
			}
			$iWhere = '';
			if($iBetweenFrag){
				$iWhere .= 'OR '.implode(' OR ',$iBetweenFrag);
				$sqlOrderBy .= ',o.catalogNumber';
			}
			if($iInFrag){
				$iWhere .= 'OR (o.catalogNumber IN("'.implode('","',$iInFrag).'") OR o.occurrenceId IN("'.implode('","',$iInFrag).'") OR o.othercatalognumbers IN("'.implode('","',$iInFrag).'")) ';
			}
			$sqlWhere .= 'AND ('.substr($iWhere,3).') ';
		}
		if(array_key_exists('rb',$qryArr)){
			$sqlWhere .= 'AND (o.recordedby LIKE "'.$qryArr['rb'].'%") ';
			$sqlOrderBy .= ',(o.recordnumber+1)';
		}
		if(array_key_exists('rn',$qryArr)){
			$rnArr = explode(',',$qryArr['rn']);
			$rnBetweenFrag = array();
			$rnInFrag = array();
			foreach($rnArr as $v){
				if($p = strpos($v,' - ')){
					$term1 = substr($v,0,$p);
					$term2 = substr($v,$p+3);
					if(is_numeric(substr($term1,0,1)) && is_numeric(substr($term2,0,1))){
						$rnBetweenFrag[] = '(CAST(o.recordnumber AS SIGNED) BETWEEN "'.$term1.'" AND "'.$term2.'")';
					}
					else{
						$rnBetweenFrag[] = '(o.recordnumber BETWEEN "'.$term1.'" AND "'.$term2.'")';
					}
				}
				else{
					$rnInFrag[] = $v;
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
		if(array_key_exists('eb',$qryArr)){
			$sqlWhere .= 'AND (o.recordEnteredBy LIKE "'.$qryArr['eb'].'%") ';
		}
		if(array_key_exists('dm',$qryArr)){
			if($p = strpos($qryArr['dm'],' - ')){
				$sqlWhere .= 'AND (DATE(o.datelastmodified) BETWEEN "'.substr($qryArr['dm'],0,$p).'" AND "'.substr($qryArr['dm'],$p+3).'") ';
			}
			else{
				$sqlWhere .= 'AND (DATE(o.datelastmodified) = "'.$qryArr['dm'].'") ';
			}
			
			$sqlOrderBy .= ',o.datelastmodified';
		}
		if(array_key_exists('ps',$qryArr)){
			$sqlWhere .= 'AND (o.processingstatus LIKE "'.$qryArr['ps'].'%") ';
		}
		if($sqlWhere){
			$sqlWhere = 'WHERE (o.collid = '.$this->collId.') '.$sqlWhere;
			if(!$isAdmin && $this->collMap['colltype'] == 'General Observations') $sqlWhere .= 'AND observeruid = '.$this->symbUid.' ';
			if($sqlOrderBy) $sqlWhere .= 'ORDER BY '.substr($sqlOrderBy,1).' ';
			$sqlWhere .= 'LIMIT '.($occIndex>0?$occIndex.',':'').'1';
		}
		//echo $sqlWhere;
		return $sqlWhere;
	}
	
	public function getQueryRecordCount($qryArr,$sqlWhere){
		global $clientRoot;
		$recCnt = 0;
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
		$rs->close();
		$qryArr['rc'] = (int)$recCnt;
		setCookie('editorquery',json_encode($qryArr),0,($clientRoot?$clientRoot:'/'));
		return $recCnt;
	}

	public function getOccurMap(){
		if(!$this->occurrenceMap && $this->occId){
			$this->setOccurArr();
		}
		return $this->occurrenceMap;
	}

	public function setOccurArr($sqlWhere = ''){
		$retArr = Array();
		$sql = '';
		if($sqlWhere){
			$sql = $this->occSql.(strpos($sqlWhere,'recordedby')?'use index(Index_collector) ':'').$sqlWhere;
		}
		else{
			$sql = $this->occSql.'WHERE (o.occid = '.$this->occId.')';
		}
		//echo "<div>".$sql."</div>";
		$rs = $this->conn->query($sql);
		if($row = $rs->fetch_object()){
			foreach($row as $k => $v){
				$retArr[strtolower($k)] = $v;
			}
		}
		$rs->close();
		if($retArr && !$this->occId) $this->occId = $retArr['occid']; 
		$this->occurrenceMap = $retArr;
	}

	public function editOccurrence($occArr,$uid,$autoCommit){
		$status = '';
		if(!$autoCommit && $this->getObserverUid() == $uid) $autoCommit = 1;
		$editedFields = trim($occArr['editedfields']);
		if($editedFields){
			//Add edits to omoccuredits
			$sqlEditsBase = 'INSERT INTO omoccuredits(occid,reviewstatus,appliedstatus,uid,fieldname,fieldvaluenew,fieldvalueold) '.
				'SELECT '.$occArr['occid'].' AS occid, 1 AS rs,'.($autoCommit?'1':'0').' AS astat,'.$uid.' AS uid,';
			$editArr = array_unique(explode(';',$editedFields));
			foreach($editArr as $k => $v){
				if($v){
					if(!array_key_exists($v,$occArr)){
						//Field is a checkbox that is unchecked
						$occArr[$v] = 0;
					}
					$sqlEdit = $sqlEditsBase.'"'.$v.'" AS fn,"'.$this->cleanStr($occArr[$v]).
						'" AS fvn,'.$this->cleanStr($v).' AS fvo FROM omoccurrences '.
						'WHERE (occid = '.$occArr['occid'].') AND (trim('.$v.') <> "'.trim($occArr[$v]).'")';
					//echo '<div>'.$sqlEdit.'</div>';
					$this->conn->query($sqlEdit);
				}
			}
			//Edit record only if user is authorized to autoCommit 
			if($autoCommit){
				$status = 'SUCCESS: edits submitted and activated';
				$sql = '';
				if(array_key_exists('autoprocessingstatus',$occArr) && $occArr['autoprocessingstatus']){
					$sql .= ',processingstatus = "'.$occArr['autoprocessingstatus'].'"';
					$k2d = array_search('processingstatus',$editArr);
					if($k2d) unset($editArr[$k2d]);
				}
				foreach($editArr as $v){
					if($v && array_key_exists($v,$occArr)){
						$sql .= ','.$v.' = '.($occArr[$v]!==''?'"'.$this->cleanStr($occArr[$v]).'"':'NULL');
					}
				}
				if(in_array('sciname',$editArr)){
					$sqlTid = 'SELECT tid FROM taxa WHERE (sciname = "'.$occArr['sciname'].'")';
					$rsTid = $this->conn->query($sqlTid);
					if($r = $rsTid->fetch_object()){
						$sql .= ',tidinterpreted = '.$r->tid;
					}
					else{
						$sql .= ',tidinterpreted = NULL';
					}
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
			"verbatimEventDate, habitat, occurrenceRemarks, associatedTaxa, verbatimattributes, ".
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

	protected function cleanStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = str_replace('"',"''",$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	public function getObserverUid(){
		$obsId = 0;
		if($this->occurrenceMap && array_key_exists('observeruid',$this->occurrenceMap)){
			return $this->occurrenceMap['observeruid'];
		}
		if($this->occId){
			$this->setOccurArr();
			$obsId = $this->occurrenceMap['observeruid'];
		}
		return $obsId;
	}
	
	public function getCollectionList($collArr){
		$collList = Array();
		$sql = 'SELECT collid, collectionname, institutioncode, collectioncode FROM omcollections ';
		if($collArr){
			$sql .= 'WHERE (collid IN ('.implode(',',$collArr).')) ';
		}
		$sql .= 'ORDER BY collectionname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$collName = $r->collectionname;
			if($r->institutioncode){
				$collName .= ' ('.$r->institutioncode;
				if($r->collectioncode) $collName .= ':'.$r->collectioncode;
				$collName .= ')';
			}
			$collList[$r->collid] = $collName;
		}
		return $collList;
	}

	public function echoCountryList(){
		$retArr = Array();
		$sql = 'SELECT countryname FROM lkupcountry ORDER BY countryname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[] = $r->countryname;
		}
		$rs->close();
		echo '"'.implode('","',$retArr).'"';
	}

	public function carryOverValues($fArr){
		$locArr = Array('recordedby','associatedcollectors','eventdate','verbatimeventdate','month','day','year',
			'startdayofyear','enddayofyear','country','stateprovince','county','locality','decimallatitude','decimallongitude',
			'verbatimcoordinates','coordinateuncertaintyinmeters','geodeticdatum','minimumelevationinmeters',
			'maximumelevationinmeters','verbatimelevation','verbatimcoordinates','georeferencedby','georeferenceprotocol',
			'georeferencesources','georeferenceverificationstatus','georeferenceremarks','habitat','associatedtaxa','basisofrecord',
			'language','labelproject');
		return array_intersect_key($fArr,array_flip($locArr)); 
	}

	//Used in dupesearch.php
	public function getDupOccurrences($collName, $collNum, $collDate, $oid, $runCnt = 0){
		$collNum = $this->conn->real_escape_string($collNum);
		$collDate = $this->conn->real_escape_string($collDate);
		$retArr = array();
		if($collName && ($collNum || $collDate)){
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
				$sqlBase = 'SELECT c.CollectionName, c.institutioncode, c.collectioncode, o.occid, o.collid AS colliddup, '.
					'o.family, o.sciname, o.tidinterpreted AS tidtoadd, o.scientificNameAuthorship, o.taxonRemarks, o.identifiedBy, o.dateIdentified, '.
					'o.identificationReferences, o.identificationRemarks, o.identificationQualifier, o.typeStatus, o.recordedBy, o.recordNumber, '.
					'o.associatedCollectors, o.eventdate, o.verbatimEventDate, o.habitat, o.occurrenceRemarks, o.associatedTaxa, '.
					'o.dynamicProperties, o.reproductiveCondition, o.cultivationStatus, o.establishmentMeans, '.
					'o.country, o.stateProvince, o.county, o.locality, o.decimalLatitude, o.decimalLongitude, '.
					'o.geodeticDatum, o.coordinateUncertaintyInMeters, o.coordinatePrecision, o.locationRemarks, o.verbatimCoordinates, '.
					'o.georeferencedBy, o.georeferenceProtocol, o.georeferenceSources, o.georeferenceVerificationStatus, o.georeferenceRemarks, '.
					'o.minimumElevationInMeters, o.maximumElevationInMeters, o.verbatimElevation, o.disposition '.
					'FROM omcollections c INNER JOIN omoccurrences o USE INDEX(Index_collnum) ON c.collid = o.collid WHERE (o.recordedby LIKE "%'.$lastName.'%") ';
				$sql = $sqlBase;
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
					$rs->close();
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
						$result->close();
						
					}
				}
			}			
		}
		return $retArr;
	}
	
	public function mergeRecords($o1,$o2){
		$status = '';
		$targetOccid = '';
		$sourceOccid = '';
		if($o1 < $o2){
			$targetOccid = $o1;
			$sourceOccid = $o2;
		}
		else{
			$targetOccid = $o2;
			$sourceOccid = $o1;
		}
		
		$oArr = array();
		//Merge records
		$sql = 'SELECT * FROM omoccurrences WHERE occid = '.$o1.' OR occid = '.$o2;
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
		$rs->close();

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

		//Remap OCR raw text blocks
		$sql = 'UPDATE specprocessorrawlabels SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
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
			$status .= 'ERROR: unable to delete source occurrence: '.$this->conn->error;
		}
		return $status;
	}

	//Label processing methods
	public function getRawTextFragments(){
		$retArr = array();
		if($this->occId){
			$sql = 'SELECT prlid, rawstr, notes FROM specprocessorrawlabels WHERE occid = '.$this->occId;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->prlid] = $r->rawstr;
			}
			$rs->close();
		} 
		return $retArr;
	}
	
	public function getImageMap(){
		$imageMap = Array();
		$sql = 'SELECT imgid, url, thumbnailurl, originalurl, caption, photographer, photographeruid, '.
			'sourceurl, copyright, notes, occid, sortsequence '.
			'FROM images '.
			'WHERE (occid = '.$this->occId.') ORDER BY sortsequence';
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
		$result->close();
		return $imageMap;
	}
}
?>