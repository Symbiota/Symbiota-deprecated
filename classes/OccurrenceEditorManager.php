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
			$sql = '';
			if($this->collId){
				$sql = 'SELECT c.collid, c.collectionname, c.institutioncode, c.collectioncode, c.managementtype '.
					'FROM omcollections c WHERE (c.collid = '.$this->collId.')';
			}
			elseif($this->occId){
				$sql = 'SELECT c.collid, c.collectionname, c.institutioncode, c.collectioncode, c.managementtype '.
					'FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid '.
					'WHERE (o.occid = '.$this->occId.')';
			}
			if($sql){
				$rs = $this->conn->query($sql);
				if($row = $rs->fetch_object()){
					$this->collMap['collid'] = $row->collid;
					!$this->collId = $row->collid;
					$this->collMap['collectionname'] = $row->collectionname;
					$this->collMap['institutioncode'] = $row->institutioncode;
					$this->collMap['collectioncode'] = $row->collectioncode;
					$this->collMap['managementtype'] = $row->managementtype;
				}
				$rs->close();
			}
		}
		return $this->collMap;
	}
	
	public function queryOccurrences($occIndex=0){
		global $clientRoot;
		$recCnt = 0;
		$qryArr = array();
		if(array_key_exists('q_identifier',$_POST)){
			if($_POST['q_identifier']) $qryArr['id'] = $_POST['q_identifier'];
			if($_POST['q_recordedby']) $qryArr['rb'] = $_POST['q_recordedby'];
			if($_POST['q_recordnumber']) $qryArr['rn'] = $_POST['q_recordnumber'];
			if($_POST['q_enteredby']) $qryArr['eb'] = $_POST['q_enteredby'];
			if($_POST['q_processingstatus']) $qryArr['ps'] = $_POST['q_processingstatus'];
			if($_POST['q_datelastmodified']) $qryArr['dm'] = $_POST['q_datelastmodified'];
			setCookie('editorquery','',time()-3600,($clientRoot?$clientRoot:'/'));
		}
		elseif(isset($_COOKIE["editorquery"])){
			$qryArr = json_decode($_COOKIE["editorquery"],true);
			$recCnt = $qryArr['rc'];
		}

		$sqlWhere = '';
		$sqlOrderBy = '';
		if(array_key_exists('id',$qryArr)){
			$iArr = explode(',',$qryArr['id']);
			$iBetweenFrag = array();
			$iInFrag = array();
			foreach($iArr as $v){
				if($p = strpos($v,' - ')){
					$iBetweenFrag[] = 'o.catalogNumber BETWEEN '.substr($v,0,$p).' AND '.substr($v,$p+3);
					$iBetweenFrag[] = 'o.occurrenceId BETWEEN '.substr($v,0,$p).' AND '.substr($v,$p+3);
				}
				else{
					$iInFrag[] = $v;
				}
			}
			$iWhere = '';
			if($iBetweenFrag){
				$iWhere .= 'OR '.implode(' OR ',$iBetweenFrag);
			}
			if($iInFrag){
				$iWhere .= 'OR (o.catalogNumber IN("'.implode('","',$iInFrag).'") OR o.occurrenceId IN("'.implode('","',$iInFrag).'")) ';
			}
			$sqlWhere .= 'AND ('.substr($iWhere,3).') ';
			$sqlOrderBy .= ',o.catalogNumber,o.occurrenceId';
		}
		if(array_key_exists('rb',$qryArr)){
			$sqlWhere .= 'AND (o.recordedby LIKE "%'.$qryArr['rb'].'%") ';
			$sqlOrderBy .= ',o.recordnumber';
		}
		if(array_key_exists('rn',$qryArr)){
			$rnArr = explode(',',$qryArr['rn']);
			$rnBetweenFrag = array();
			$rnInFrag = array();
			foreach($rnArr as $v){
				if($p = strpos($v,' - ')){
					$rnBetweenFrag[] = '(o.recordnumber BETWEEN "'.substr($v,0,$p).'" AND "'.substr($v,$p+3).'")';
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
			if(!$recCnt){
				$sql = 'SELECT COUNT(*) AS reccnt FROM omoccurrences o '.$sqlWhere;
				//echo '<div>'.$sql.'</div>';
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$recCnt = $r->reccnt;
				}
				$rs->close();
				$qryArr['rc'] = (int)$recCnt;
				setCookie('editorquery',json_encode($qryArr),0,($clientRoot?$clientRoot:'/'));
			}
			if($recCnt){
				if($sqlOrderBy) $sqlWhere .= 'ORDER BY '.substr($sqlOrderBy,1).' ';
				$sqlWhere .= 'LIMIT '.($occIndex?$occIndex.',':'').'1';
				$this->setOccurArr($sqlWhere);
			}
		}

		return $qryArr;
	}
	
	public function getOccurMap(){
		if(!$this->occurrenceMap && $this->occId){
			$this->setOccurArr();
		}
		return $this->occurrenceMap;
	}
	
	private function setOccurArr($sqlWhere = ''){
		$retArr = Array();
		$sql = '';
		if($sqlWhere){
			$sql = $this->occSql.$sqlWhere;
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
		if(!$this->occId) $this->occId = $retArr['occid']; 
		$this->occurrenceMap = $retArr;
	}

	public function editOccurrence($occArr,$uid,$autoCommit){
		$status = '';
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
					$sqlEdit = $sqlEditsBase.'"'.$v.'" AS fn,"'.$occArr[$v].'" AS fvn,'.$v.' FROM omoccurrences '.
						'WHERE (occid = '.$occArr['occid'].') AND (trim('.$v.') <> "'.trim($occArr[$v]).'")';
					//echo '<div>'.$sqlEdit.'</div>';
					$this->conn->query($sqlEdit);
				}
			}
			//Edit record only if user is authorized to autoCommit 
			if($autoCommit){
				$status = 'SUCCESS: edits submitted and activated';
				$sql = '';
				foreach($editArr as $v){
					if($v){
						$sql .= ','.$v.' = '.($occArr[$v]!==''?'"'.$occArr[$v].'"':'NULL');
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
			"verbatimElevation, disposition, language, duplicateQuantity, labelProject, processingstatus, recordEnteredBy) ".
			"VALUES (".$occArr["collid"].",".
			($occArr["basisofrecord"]?"\"".$occArr["basisofrecord"]."\"":"NULL").",".
			($occArr["occurrenceid"]?"\"".$occArr["occurrenceid"]."\"":"NULL").",".
			($occArr["catalognumber"]?"\"".$occArr["catalognumber"]."\"":"NULL").",".
			($occArr["othercatalognumbers"]?"\"".$occArr["othercatalognumbers"]."\"":"NULL").",".
			($occArr["ownerinstitutioncode"]?"\"".$occArr["ownerinstitutioncode"]."\"":"NULL").",".
			($occArr["family"]?"\"".$occArr["family"]."\"":"NULL").",".
			"\"".$occArr["sciname"]."\",".
			($occArr["tidtoadd"]?$occArr["tidtoadd"]:"NULL").",".
			($occArr["scientificnameauthorship"]?"\"".$occArr["scientificnameauthorship"]."\"":"NULL").",".
			($occArr["identifiedby"]?"\"".$occArr["identifiedby"]."\"":"NULL").",".
			($occArr["dateidentified"]?"\"".$occArr["dateidentified"]."\"":"NULL").",".
			($occArr["identificationreferences"]?"\"".$occArr["identificationreferences"]."\"":"NULL").",".
			($occArr["identificationremarks"]?"\"".$occArr["identificationremarks"]."\"":"NULL").",".
			($occArr["identificationqualifier"]?"\"".$occArr["identificationqualifier"]."\"":"NULL").",".
			($occArr["typestatus"]?"\"".$occArr["typestatus"]."\"":"NULL").",".
			($occArr["recordedby"]?"\"".$occArr["recordedby"]."\"":"NULL").",".
			($occArr["recordnumber"]?"\"".$occArr["recordnumber"]."\"":"NULL").",".
			($occArr["associatedcollectors"]?"\"".$occArr["associatedcollectors"]."\"":"NULL").",".
			($occArr["eventdate"]?"'".$occArr["eventdate"]."'":"NULL").",".
			($occArr["year"]?$occArr["year"]:"NULL").",".
			($occArr["month"]?$occArr["month"]:"NULL").",".
			($occArr["day"]?$occArr["day"]:"NULL").",".
			($occArr["startdayofyear"]?$occArr["startdayofyear"]:"NULL").",".
			($occArr["enddayofyear"]?$occArr["enddayofyear"]:"NULL").",".
			($occArr["verbatimeventdate"]?"\"".$occArr["verbatimeventdate"]."\"":"NULL").",".
			($occArr["habitat"]?"\"".$occArr["habitat"]."\"":"NULL").",".
			($occArr["occurrenceremarks"]?"\"".$occArr["occurrenceremarks"]."\"":"NULL").",".
			($occArr["associatedtaxa"]?"\"".$occArr["associatedtaxa"]."\"":"NULL").",".
			($occArr["verbatimattributes"]?"\"".$occArr["verbatimattributes"]."\"":"NULL").",".
			($occArr["dynamicproperties"]?"\"".$occArr["dynamicproperties"]."\"":"NULL").",".
			($occArr["reproductivecondition"]?"\"".$occArr["reproductivecondition"]."\"":"NULL").",".
			(array_key_exists("cultivationstatus",$occArr)?"1":"0").",".
			($occArr["establishmentmeans"]?"\"".$occArr["establishmentmeans"]."\"":"NULL").",".
			($occArr["country"]?"\"".$occArr["country"]."\"":"NULL").",".
			($occArr["stateprovince"]?"\"".$occArr["stateprovince"]."\"":"NULL").",".
			($occArr["county"]?"\"".$occArr["county"]."\"":"NULL").",".
			($occArr["locality"]?"\"".$occArr["locality"]."\"":"NULL").",".
			(array_key_exists("localitysecurity",$occArr)?"1":"0").",".
			($occArr["localitysecurityreason"]?$occArr["localitysecurityreason"]:"NULL").",".
			($occArr["decimallatitude"]?$occArr["decimallatitude"]:"NULL").",".
			($occArr["decimallongitude"]?$occArr["decimallongitude"]:"NULL").",".
			($occArr["geodeticdatum"]?"\"".$occArr["geodeticdatum"]."\"":"NULL").",".
			($occArr["coordinateuncertaintyinmeters"]?"\"".$occArr["coordinateuncertaintyinmeters"]."\"":"NULL").",".
			($occArr["verbatimcoordinates"]?"\"".$occArr["verbatimcoordinates"]."\"":"NULL").",".
			($occArr["georeferencedby"]?"\"".$occArr["georeferencedby"]."\"":"NULL").",".
			($occArr["georeferenceprotocol"]?"\"".$occArr["georeferenceprotocol"]."\"":"NULL").",".
			($occArr["georeferencesources"]?"\"".$occArr["georeferencesources"]."\"":"NULL").",".
			($occArr["georeferenceverificationstatus"]?"\"".$occArr["georeferenceverificationstatus"]."\"":"NULL").",".
			($occArr["georeferenceremarks"]?"\"".$occArr["georeferenceremarks"]."\"":"NULL").",".
			($occArr["minimumelevationinmeters"]?$occArr["minimumelevationinmeters"]:"NULL").",".
			($occArr["maximumelevationinmeters"]?$occArr["maximumelevationinmeters"]:"NULL").",".
			($occArr["verbatimelevation"]?"\"".$occArr["verbatimelevation"]."\"":"NULL").",".
			($occArr["disposition"]?"\"".$occArr["disposition"]."\"":"NULL").",".
			($occArr["language"]?"\"".$occArr["language"]."\"":"NULL").",".
			($occArr["duplicatequantity"]?$occArr["duplicatequantity"]:"NULL").",".
			($occArr["labelproject"]?"\"".$occArr["labelproject"]."\"":"NULL").",".
			($occArr["processingstatus"]?"\"".$occArr["processingstatus"]."\"":"NULL").",\"".
			$occArr["userid"]."\") ";
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
		$newStr = str_replace("\"","'",$newStr);
		return $newStr;
	}
	
	public function getObserverUid(){
		$obsId = 0;
		$rs = $this->conn->query('SELECT observeruid FROM omoccurrences WHERE (occid = '.$this->occId.')');
		if($row = $rs->fetch_object()){
			$obsId = $row->observeruid;
		}
		$rs->close();
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
			'georeferencesources','georeferenceverificationstatus','georeferenceremarks','habitat','associatedtaxa','basisofrecord','language');
		return array_intersect_key($fArr,array_flip($locArr)); 
	}

	//Used in dupsearch.php
	public function getDupOccurrences($occidStr){
		$occurrenceMap = Array();
		$sql = 'SELECT c.CollectionName, c.institutioncode, c.collectioncode, o.occid, o.collid AS colliddup, '.
			'o.family, o.sciname, o.tidinterpreted AS tidtoadd, o.scientificNameAuthorship, o.taxonRemarks, o.identifiedBy, o.dateIdentified, '.
			'o.identificationReferences, o.identificationRemarks, o.identificationQualifier, o.typeStatus, o.recordedBy, o.recordNumber, '.
			'o.associatedCollectors, o.eventdate, o.verbatimEventDate, o.habitat, o.occurrenceRemarks, o.associatedTaxa, '.
			'o.dynamicProperties, o.reproductiveCondition, o.cultivationStatus, o.establishmentMeans, '.
			'o.country, o.stateProvince, o.county, o.locality, o.decimalLatitude, o.decimalLongitude, '.
			'o.geodeticDatum, o.coordinateUncertaintyInMeters, o.coordinatePrecision, o.locationRemarks, o.verbatimCoordinates, '.
			'o.georeferencedBy, o.georeferenceProtocol, o.georeferenceSources, o.georeferenceVerificationStatus, o.georeferenceRemarks, '.
			'o.minimumElevationInMeters, o.maximumElevationInMeters, o.verbatimElevation, o.disposition '.
			'FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid '.
			'WHERE (occid IN('.$occidStr.'))';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$occId = $row->occid;
			foreach($row as $k => $v){
				if($k != 'occid'){
					$occurrenceMap[$occId][strtolower($k)] = $v;
				}
			}
		}
		$rs->close();
		return $occurrenceMap;
	}
}
?>