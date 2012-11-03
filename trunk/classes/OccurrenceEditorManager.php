<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/OccurrenceEditorDeterminations.php');
include_once($serverRoot.'/classes/OccurrenceEditorImages.php');

class OccurrenceEditorManager {

	protected $conn;
	protected $occid;
	private $collId;
	private $collMap = array();
	private $occurrenceMap = array();
	private $occFieldArr = array();
	private $sqlWhere;
	private $qryArr = array();
	private $crowdSourceMode = 0;
	private $symbUid;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
		$this->occFieldArr = array('catalognumber', 'othercatalognumbers', 'family', 'scientificname', 'sciname', 
			'tidinterpreted', 'scientificnameauthorship', 'taxonremarks', 'identifiedby', 'dateidentified', 'identificationreferences',
			'identificationremarks', 'identificationqualifier', 'typestatus', 'recordedby', 'recordnumber',
			'associatedcollectors', 'eventdate', 'year', 'month', 'day', 'startdayofyear', 'enddayofyear',
			'verbatimeventdate', 'habitat', 'substrate', 'occurrenceremarks', 'associatedtaxa', 'verbatimattributes',
			'dynamicproperties', 'reproductivecondition', 'cultivationstatus', 'establishmentmeans', 'country', 
			'stateprovince', 'county', 'municipality', 'locality', 'localitysecurity', 'localitysecurityreason', 
			'decimallatitude', 'decimallongitude','geodeticdatum', 'coordinateuncertaintyinmeters', 'coordinateprecision', 
			'locationremarks', 'verbatimcoordinates', 'georeferencedby', 'georeferenceprotocol', 'georeferencesources', 
			'georeferenceverificationstatus', 'georeferenceremarks', 'minimumelevationinmeters', 'maximumelevationinmeters',
			'verbatimelevation', 'disposition', 'language', 'duplicatequantity', 'labelproject', 'observeruid', 
			'basisofrecord','ownerinstitutioncode','datelastmodified', 'processingstatus', 'recordenteredby');
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
	
	public function setOccId($id){
		if(is_numeric($id)){
			$this->occid = $this->conn->real_escape_string($id);
		}
	}
	
	public function getOccId(){
		return $this->occid;
	}

	public function setCollId($id){
		if(is_numeric($id)){
			$this->collId = $this->conn->real_escape_string($id);
		}
	}

	public function getCollMap(){
		if(!$this->collMap){
			if($this->collId || $this->occid){
				$sql = 'SELECT c.collid, c.collectionname, c.institutioncode, c.collectioncode, c.colltype, c.managementtype '.
					'FROM omcollections c ';
				if($this->collId){
					$sql .= 'WHERE (c.collid = '.$this->collId.')';
				}
				elseif($this->occid){
					$sql .= 'INNER JOIN omoccurrences o ON c.collid = o.collid '.
						'WHERE (o.occid = '.$this->occid.')';
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

	public function setCrowdSourceMode($m){
		$this->crowdSourceMode = $m;
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
			if(array_key_exists('q_ocrfrag',$_POST) && $_POST['q_ocrfrag']) $this->qryArr['ocr'] = trim($_POST['q_ocrfrag']); 
			if(array_key_exists('q_imgonly',$_POST) && $_POST['q_imgonly']) $this->qryArr['io'] = 1;
			for($x=1;$x<4;$x++){
				if(array_key_exists('q_customfield'.$x,$_POST) && $_POST['q_customfield'.$x]) $this->qryArr['cf'.$x] = $_POST['q_customfield'.$x];
				if(array_key_exists('q_customtype'.$x,$_POST) && $_POST['q_customtype'.$x]) $this->qryArr['ct'.$x] = $_POST['q_customtype'.$x];
				if(array_key_exists('q_customvalue'.$x,$_POST) && $_POST['q_customvalue'.$x]) $this->qryArr['cv'.$x] = trim($_POST['q_customvalue'.$x]);
			}
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
			$idTerm = $this->qryArr['id'];
			if(strtolower($idTerm) == 'is null'){
				$sqlWhere .= 'AND (o.catalognumber IS NULL) ';
			}
			else{
				$isOccid = false;
				if(substr($idTerm,0,5) == 'occid'){
					$idTerm = trim(substr($idTerm,5));
					$isOccid = true;
				}
				$iArr = explode(',',$idTerm);
				$iBetweenFrag = array();
				$iInFrag = array();
				$searchIsNum = false;
				foreach($iArr as $v){
					if($p = strpos($v,' - ')){
						$term1 = trim(substr($v,0,$p));
						$term2 = trim(substr($v,$p+3));
						if(is_numeric($term1) && is_numeric($term2)){
							$searchIsNum = true;
							if($isOccid){
								$iBetweenFrag[] = '(o.occid BETWEEN '.$term1.' AND '.$term2.')';
							}
							else{
								$iBetweenFrag[] = '(o.catalogNumber BETWEEN '.$term1.' AND '.$term2.')';
							} 
						}
						else{
							$catTerm = 'o.catalogNumber BETWEEN "'.$term1.'" AND "'.$term2.'"';
							if(strlen($term1) == strlen($term2)) $catTerm .= ' AND length(o.catalogNumber) = '.strlen($term2); 
							$iBetweenFrag[] = '('.$catTerm.')';
						}
					}
					else{
						$iInFrag[] = trim($v);
					}
				}
				$iWhere = '';
				if($iBetweenFrag){
					$iWhere .= 'OR '.implode(' OR ',$iBetweenFrag);
				}
				if($iInFrag){
					if($isOccid){
						$iWhere .= 'OR (o.occid IN("'.implode('","',$iInFrag).'")) ';
					}
					else{
						$iWhere .= 'OR (o.catalogNumber IN("'.implode('","',$iInFrag).'")) ';
					} 
				}
				if($searchIsNum){
					if($isOccid){
						$sqlOrderBy .= ',(o.occid+1)';
					}
					else{
						$sqlOrderBy .= ',(o.catalogNumber+1)';
					}
				}
				else{
					$sqlOrderBy .= ',o.catalogNumber';
				}
				$sqlWhere .= 'AND ('.substr($iWhere,3).') ';
			}
		}
		//otherCatalogNumbers
		if(array_key_exists('ocn',$this->qryArr)){
			if(strtolower($this->qryArr['ocn']) == 'is null'){
				$sqlWhere .= 'AND (o.othercatalognumbers IS NULL) ';
			}
			else{
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
		}
		//recordNumber: collector's number
		$rnIsNum = false;
		if(array_key_exists('rn',$this->qryArr)){
			if(strtolower($this->qryArr['rn']) == 'is null'){
				$sqlWhere .= 'AND (o.recordnumber IS NULL) ';
			}
			else{
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
		}
		if(array_key_exists('rb',$this->qryArr)){
			if(strtolower($this->qryArr['rb']) == 'is null'){
				$sqlWhere .= 'AND (o.recordedby IS NULL) ';
			}
			else{
				$sqlWhere .= 'AND (o.recordedby LIKE "'.$this->qryArr['rb'].'%") ';
				$sqlOrderBy .= ',(o.recordnumber+1)';
			}
		}
		if(array_key_exists('ed',$this->qryArr)){
			if(strtolower($this->qryArr['ed']) == 'is null'){
				$sqlWhere .= 'AND (o.eventdate IS NULL) ';
			}
			else{
				if($p = strpos($this->qryArr['ed'],' - ')){
					$sqlWhere .= 'AND (o.eventdate BETWEEN "'.substr($this->qryArr['ed'],0,$p).'" AND "'.substr($this->qryArr['ed'],$p+3).'") ';
				}
				else{
					$sqlWhere .= 'AND (o.eventdate = "'.$this->qryArr['ed'].'") ';
				}
				$sqlOrderBy .= ',o.eventdate';
			}
		}
		if(array_key_exists('eb',$this->qryArr)){
			if(strtolower($this->qryArr['eb']) == 'is null'){
				$sqlWhere .= 'AND (o.recordEnteredBy IS NULL) ';
			}
			else{
				$sqlWhere .= 'AND (o.recordEnteredBy LIKE "'.$this->qryArr['eb'].'%") ';
			}
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
			if(strtolower($this->qryArr['ps']) == 'is null'){
				$sqlWhere .= 'AND (o.processingstatus IS NULL) ';
			}
			else{
				$sqlWhere .= 'AND (o.processingstatus LIKE "'.$this->qryArr['ps'].'%") ';
			}
		}
		if(array_key_exists('ocr',$this->qryArr)){
			//Used when OCR frag comes from set field within queryformcrowdsourcing
			$sqlWhere .= 'AND (ocr.rawstr LIKE "%'.$this->qryArr['ocr'].'%") ';
		}
		for($x=1;$x<4;$x++){
			$cf = (array_key_exists('cf'.$x,$this->qryArr)?$this->qryArr['cf'.$x]:'');
			$ct = (array_key_exists('ct'.$x,$this->qryArr)?$this->qryArr['ct'.$x]:'');
			$cv = (array_key_exists('cv'.$x,$this->qryArr)?$this->qryArr['cv'.$x]:'');
			if($cf && ($cv || $ct == 'IS NULL')){
				if($cf == 'ocrFragment' && !strpos($sqlWhere,'rawstr')){
					//Used when OCR frag comes from custom field search within basic query form 
					if(strpos($cv,'%') !== false){
						$sqlWhere .= 'AND (ocr.rawstr LIKE "'.$cv.'") ';
					}
					else{
						$sqlWhere .= 'AND (ocr.rawstr LIKE "%'.$cv.'%") ';
					}
				}
				elseif($ct=='IS NULL'){
					$sqlWhere .= 'AND (o.'.$cf.' IS NULL) ';
				}
				elseif($ct=='LIKE'){
					if(strpos($cv,'%') !== false){
						$sqlWhere .= 'AND (o.'.$cf.' LIKE "'.$cv.'") ';
					}
					else{
						$sqlWhere .= 'AND (o.'.$cf.' LIKE "%'.$cv.'%") ';
					}
				}
				else{
					$sqlWhere .= 'AND (o.'.$cf.' = "'.$cv.'") ';
				}
			}
		}
		if($this->crowdSourceMode) $sqlWhere .= 'AND q.reviewstatus = 0 ';
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
			$sql = 'SELECT COUNT(o.occid) AS reccnt FROM omoccurrences o ';
			if(strpos($sqlWhere,'ocr.rawstr') !== false){
				$sql .= 'INNER JOIN images i ON o.occid = i.occid INNER JOIN specprocessorrawlabels ocr ON i.imgid = ocr.imgid ';
			}
			elseif(array_key_exists('io',$this->qryArr)){
				$sql .= 'INNER JOIN images i ON o.occid = i.occid ';
			}
			if($this->crowdSourceMode){
				$sql .= 'INNER JOIN omcrowdsourcequeue q ON q.occid = o.occid ';
			}
			$sql .= $sqlWhere;
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
		if(!$this->occurrenceMap && ($this->occid || $this->sqlWhere)){
			$this->setOccurArr();
		}
		return $this->occurrenceMap;
	}

	private function setOccurArr(){
		$retArr = Array();
		$sql = 'SELECT o.occid, o.collid, o.'.implode(',o.',$this->occFieldArr).' FROM omoccurrences o ';
		if($this->occid){
			$sql .= 'WHERE (o.occid = '.$this->occid.')';
		}
		elseif($this->sqlWhere){
			if(strpos($this->sqlWhere,'recordedby')){
				$sql .= 'use index(Index_collector) ';
			}
			if(strpos($this->sqlWhere,'ocr.rawstr') !== false){
				$sql .= 'INNER JOIN images i ON o.occid = i.occid INNER JOIN specprocessorrawlabels ocr ON i.imgid = ocr.imgid ';
			}
			elseif(array_key_exists('io',$this->qryArr)){
				$sql .= 'INNER JOIN images i ON o.occid = i.occid ';
			}
			if($this->crowdSourceMode){
				$sql .= 'INNER JOIN omcrowdsourcequeue q ON q.occid = o.occid ';
			}
			$sql .= $this->sqlWhere;
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
			if(!$this->occid && $retArr && count($retArr) == 1) $this->occid = $occid; 
			$this->occurrenceMap = $retArr;
			//if($this->occid) $this->setLoanData();
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
				(in_array('recordenteredby',$editArr)?'':',recordenteredby').
				',recordenteredby FROM omoccurrences WHERE occid = '.$occArr['occid'];
			$rs = $this->conn->query($sql);
			$oldValues = $rs->fetch_assoc();
			$rs->free();
			//Version edits 
			if($oldValues['processingstatus'] != 'unprocessed' || $oldValues['recordenteredby']){ 
				//Don't version auto-submitted records (old processing status = "unprocessed" and editor Is Null)
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
					if($v && $oldValue != $newValue){
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
					$occArr['recordenteredby'] = $paramsArr['un'];
				}
				if(array_key_exists('autoprocessingstatus',$occArr) && $occArr['autoprocessingstatus']){
					$occArr['processingstatus'] = $occArr['autoprocessingstatus'];
				}
				foreach($occArr as $ok => $ov){
					if(in_array($ok,$this->occFieldArr) && $ok != 'observeruid'){
						$vStr = $this->cleanStr($ov);
						$sql .= ','.$ok.' = '.($vStr!==''?'"'.$vStr.'"':'NULL');
						if(array_key_exists($this->occid,$this->occurrenceMap) && array_key_exists($ok,$this->occurrenceMap[$this->occid])){
							$this->occurrenceMap[$this->occid][$ok] = $vStr;
						}
					}
				}
/*				foreach($editArr as $v){
					if($v && array_key_exists($v,$occArr)){
						$vStr = $this->cleanStr($occArr[$v]);
						$sql .= ','.$v.' = '.($vStr!==''?'"'.$vStr.'"':'NULL');
						if(array_key_exists($this->occid,$this->occurrenceMap) && array_key_exists($v,$this->occurrenceMap[$this->occid])){
							$this->occurrenceMap[$this->occid][$v] = $vStr;
						}
					}
				}
*/
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
			$sql = "INSERT INTO omoccurrences(collid, basisOfRecord, catalogNumber, otherCatalogNumbers, ".
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
			($occArr["catalognumber"]?"\"".$occArr["catalognumber"]."\"":"NULL").",".
			($occArr["othercatalognumbers"]?"\"".$occArr["othercatalognumbers"]."\"":"NULL").",".
			($occArr["ownerinstitutioncode"]?"\"".$occArr["ownerinstitutioncode"]."\"":"NULL").",".
			($occArr["family"]?"\"".$occArr["family"]."\"":"NULL").",".
			"\"".$occArr["sciname"]."\",".
			($occArr["tidtoadd"]?$occArr["tidtoadd"]:"NULL").",".
			($occArr["scientificnameauthorship"]?"\"".$this->cleanStr($occArr["scientificnameauthorship"])."\"":"NULL").",".
			($occArr["identifiedby"]?"\"".$occArr["identifiedby"]."\"":"NULL").",".
			($occArr["dateidentified"]?"\"".$occArr["dateidentified"]."\"":"NULL").",".
			($occArr["identificationreferences"]?"\"".$occArr["identificationreferences"]."\"":"NULL").",".
			($occArr["identificationremarks"]?"\"".$occArr["identificationremarks"]."\"":"NULL").",".
			($occArr["identificationqualifier"]?"\"".$occArr["identificationqualifier"]."\"":"NULL").",".
			($occArr["typestatus"]?"\"".$occArr["typestatus"]."\"":"NULL").",".
			($occArr["recordedby"]?"\"".$occArr["recordedby"]."\"":"NULL").",".
			($occArr["recordnumber"]?"\"".$occArr["recordnumber"]."\"":"NULL").",".
			($occArr["associatedcollectors"]?"\"".$this->cleanStr($occArr["associatedcollectors"])."\"":"NULL").",".
			($occArr["eventdate"]?"'".$occArr["eventdate"]."'":"NULL").",".
			($occArr["year"]?$occArr["year"]:"NULL").",".
			($occArr["month"]?$occArr["month"]:"NULL").",".
			($occArr["day"]?$occArr["day"]:"NULL").",".
			($occArr["startdayofyear"]?$occArr["startdayofyear"]:"NULL").",".
			($occArr["enddayofyear"]?$occArr["enddayofyear"]:"NULL").",".
			($occArr["verbatimeventdate"]?"\"".$this->cleanStr($occArr["verbatimeventdate"])."\"":"NULL").",".
			($occArr["habitat"]?"\"".$this->cleanStr($occArr["habitat"])."\"":"NULL").",".
			($occArr["substrate"]?"\"".$this->cleanStr($occArr["substrate"])."\"":"NULL").",".
			($occArr["occurrenceremarks"]?"\"".$this->cleanStr($occArr["occurrenceremarks"])."\"":"NULL").",".
			($occArr["associatedtaxa"]?"\"".$this->cleanStr($occArr["associatedtaxa"])."\"":"NULL").",".
			($occArr["verbatimattributes"]?"\"".$this->cleanStr($occArr["verbatimattributes"])."\"":"NULL").",".
			($occArr["dynamicproperties"]?"\"".$this->cleanStr($occArr["dynamicproperties"])."\"":"NULL").",".
			($occArr["reproductivecondition"]?"\"".$occArr["reproductivecondition"]."\"":"NULL").",".
			(array_key_exists("cultivationstatus",$occArr)?"1":"0").",".
			($occArr["establishmentmeans"]?"\"".$this->cleanStr($occArr["establishmentmeans"])."\"":"NULL").",".
			($occArr["country"]?"\"".$occArr["country"]."\"":"NULL").",".
			($occArr["stateprovince"]?"\"".$occArr["stateprovince"]."\"":"NULL").",".
			($occArr["county"]?"\"".$occArr["county"]."\"":"NULL").",".
			($occArr["locality"]?"\"".$this->cleanStr($occArr["locality"])."\"":"NULL").",".
			(array_key_exists("localitysecurity",$occArr)?"1":"0").",".
			($occArr["localitysecurityreason"]?$occArr["localitysecurityreason"]:"NULL").",".
			($occArr["decimallatitude"]?$occArr["decimallatitude"]:"NULL").",".
			($occArr["decimallongitude"]?$occArr["decimallongitude"]:"NULL").",".
			($occArr["geodeticdatum"]?"\"".$this->cleanStr($occArr["geodeticdatum"])."\"":"NULL").",".
			($occArr["coordinateuncertaintyinmeters"]?"\"".$occArr["coordinateuncertaintyinmeters"]."\"":"NULL").",".
			($occArr["verbatimcoordinates"]?"\"".$this->cleanStr($occArr["verbatimcoordinates"])."\"":"NULL").",".
			($occArr["georeferencedby"]?"\"".$occArr["georeferencedby"]."\"":"NULL").",".
			($occArr["georeferenceprotocol"]?"\"".$this->cleanStr($occArr["georeferenceprotocol"])."\"":"NULL").",".
			($occArr["georeferencesources"]?"\"".$this->cleanStr($occArr["georeferencesources"])."\"":"NULL").",".
			($occArr["georeferenceverificationstatus"]?"\"".$occArr["georeferenceverificationstatus"]."\"":"NULL").",".
			($occArr["georeferenceremarks"]?"\"".$this->cleanStr($occArr["georeferenceremarks"])."\"":"NULL").",".
			($occArr["minimumelevationinmeters"]?$occArr["minimumelevationinmeters"]:"NULL").",".
			($occArr["maximumelevationinmeters"]?$occArr["maximumelevationinmeters"]:"NULL").",".
			($occArr["verbatimelevation"]?"\"".$this->cleanStr($occArr["verbatimelevation"])."\"":"NULL").",".
			($occArr["disposition"]?"\"".$occArr["disposition"]."\"":"NULL").",".
			($occArr["language"]?"\"".$occArr["language"]."\"":"NULL").",".
			($occArr["duplicatequantity"]?$occArr["duplicatequantity"]:"NULL").",".
			($occArr["labelproject"]?"\"".$occArr["labelproject"]."\"":"NULL").",".
			($occArr["processingstatus"]?"\"".$occArr["processingstatus"]."\"":"NULL").",\"".
			$occArr["userid"]."\",".$occArr["observeruid"].") ";
			//echo "<div>".$sql."</div>";
			if($this->conn->query($sql)){
				$this->occid = $this->conn->insert_id;
			}
			else{
				$status = "ERROR - failed to add occurrence record: ".$this->conn->error;
			}
		}
		return $status;
	}
	
	public function deleteOccurrence($delOccid){
		$status = '';
		if(is_numeric($delOccid)){
			$sql = 'DELETE FROM omoccurrences WHERE (occid = '.$delOccid.')';
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
			'WHERE ll.returndate IS NULL AND l.dateclosed IS NULL AND occid = '.$this->occid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->occurrenceMap[$this->occid]['loan']['id'] = $r->loanid;
			$this->occurrenceMap[$this->occid]['loan']['date'] = $r->datedue;
		}
		$rs->free();
	}
	
	public function getObserverUid(){
		$obsId = 0;
		if($this->occurrenceMap && array_key_exists('observeruid',$this->occurrenceMap[$this->occid])){
			$obsId = $this->occurrenceMap[$this->occid]['observeruid'];
		}
		elseif($this->occid){
			$this->setOccurArr();
			$obsId = $this->occurrenceMap[$this->occid]['observeruid'];
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
			$sql = 'UPDATE omoccurrences o2 INNER JOIN (SELECT o.occid FROM omoccurrences o ';
			//Add raw string fragment if present, yet unlikely
			if(strpos($this->sqlWhere,'ocr.rawstr') !== false){
				$sql .= 'INNER JOIN images i ON o.occid = i.occid INNER JOIN specprocessorrawlabels ocr ON i.imgid = ocr.imgid ';
			}
			elseif(array_key_exists('io',$this->qryArr)){
				$sql .= 'INNER JOIN images i ON o.occid = i.occid ';
			}
			//Strip ORDER BY and/or LIMIT fragments
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
		$sql = 'SELECT COUNT(o.occid) AS retcnt FROM omoccurrences o ';
		//Add raw string fragment if present, yet unlikely
		if(strpos($this->sqlWhere,'ocr.rawstr') !== false){
			$sql .= 'INNER JOIN images i ON o.occid = i.occid INNER JOIN specprocessorrawlabels ocr ON i.imgid = ocr.imgid ';
		}
		elseif(array_key_exists('io',$this->qryArr)){
			$sql .= 'INNER JOIN images i ON o.occid = i.occid ';
		}
		//Strip ORDER BY and/or LIMIT fragments
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

	//Label OCR processing methods
	public function getRawTextFragments(){
		$retArr = array();
		if($this->occid){
			$sql = 'SELECT r.prlid, r.imgid, r.rawstr, r.notes '.
				'FROM specprocessorrawlabels r INNER JOIN images i ON r.imgid = i.imgid '.
				'WHERE i.occid = '.$this->occid;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->imgid][$r->prlid]['raw'] = $r->rawstr;
				$retArr[$r->imgid][$r->prlid]['notes'] = $r->notes;
			}
			$rs->free();
		} 
		return $retArr;
	}

	public function insertTextFragment($imgId,$rawFrag,$notes){
		if($imgId && $rawFrag){
			$statusStr = '';
			$sql = 'INSERT INTO specprocessorrawlabels(imgid,rawstr,notes) '.
				'VALUES ('.$imgId.',"'.$this->cleanRawFragment($rawFrag).'","'.$this->cleanRawFragment($notes).'")';
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

	public function saveTextFragment($prlId, $rawFrag,$notes){
		if($prlId && $rawFrag){
			$statusStr = '';
			$sql = 'UPDATE specprocessorrawlabels SET rawstr = "'.$this->cleanRawFragment($rawFrag).'", notes = "'.$notes.'" '.
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
		if($this->occid){
			$sql = 'SELECT imgid, url, thumbnailurl, originalurl, caption, photographer, photographeruid, '.
				'sourceurl, copyright, notes, occid, sortsequence '.
				'FROM images '.
				'WHERE (occid = '.$this->occid.') ORDER BY sortsequence';
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

	//Edit locking functions (seesion variables)
	public function getLock(){
		$isLocked = false;
		//Check lock
		$delSql = 'DELETE FROM omoccureditlocks WHERE (ts < '.(time()-900).') OR (uid = '.$this->symbUid.')';
		if(!$this->conn->query($delSql)) return false;
		//Try to insert lock for , existing lock is assumed if fails  
		$sql = 'INSERT INTO omoccureditlocks(occid,uid,ts) '.
			'VALUES ('.$this->occid.','.$this->symbUid.','.time().')';
		if(!$this->conn->query($sql)){
			$isLocked = true;
		}
		return $isLocked;
	}
	
	//Misc functions
	protected function cleanStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = str_replace('"',"&quot;",$newStr);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	private function cleanRawFragment($str){
		$newStr = trim($str);
		$newStr = str_replace('"',"&quot;",$newStr);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>