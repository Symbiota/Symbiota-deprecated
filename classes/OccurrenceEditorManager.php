<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/OccurrenceEditorDeterminations.php');
include_once($serverRoot.'/classes/OccurrenceEditorImages.php');
include_once($serverRoot.'/classes/UuidFactory.php');

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
		$this->occFieldArr = array('catalognumber', 'othercatalognumbers', 'occurrenceid','family', 'scientificname', 'sciname', 
			'tidinterpreted', 'scientificnameauthorship', 'taxonremarks', 'identifiedby', 'dateidentified', 'identificationreferences',
			'identificationremarks', 'identificationqualifier', 'typestatus', 'recordedby', 'recordnumber',
			'associatedcollectors', 'eventdate', 'year', 'month', 'day', 'startdayofyear', 'enddayofyear',
			'verbatimeventdate', 'habitat', 'substrate', 'fieldnumber','occurrenceremarks', 'associatedtaxa', 'verbatimattributes',
			'dynamicproperties', 'reproductivecondition', 'cultivationstatus', 'establishmentmeans', 
			'lifestage', 'sex', 'individualcount', 'samplingprotocol', 'preparations',
			'country', 'stateprovince', 'county', 'municipality', 'locality', 'localitysecurity', 'localitysecurityreason', 
			'decimallatitude', 'decimallongitude','geodeticdatum', 'coordinateuncertaintyinmeters', 'footprintwkt', 'coordinateprecision', 
			'locationremarks', 'verbatimcoordinates', 'georeferencedby', 'georeferenceprotocol', 'georeferencesources', 
			'georeferenceverificationstatus', 'georeferenceremarks', 'minimumelevationinmeters', 'maximumelevationinmeters',
			'verbatimelevation', 'disposition', 'language', 'duplicatequantity', 'genericcolumn1', 'genericcolumn2', 
			'labelproject', 'observeruid','basisofrecord','ownerinstitutioncode','datelastmodified', 'processingstatus', 'recordenteredby');
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
		if($id && is_numeric($id)){
			$this->collId = $this->conn->real_escape_string($id);
		}
	}

	public function getCollMap(){
		if(!$this->collMap){
			$sqlWhere = '';
			if($this->collId){
				$sqlWhere .= 'WHERE (c.collid = '.$this->collId.')';
			}
			elseif($this->occid){
				$sqlWhere .= 'INNER JOIN omoccurrences o ON c.collid = o.collid '.
					'WHERE (o.occid = '.$this->occid.')';
			}
			if($sqlWhere){
				$sql = 'SELECT c.collid, c.collectionname, c.institutioncode, c.collectioncode, c.colltype, c.managementtype '.
					'FROM omcollections c '.$sqlWhere;
				$rs = $this->conn->query($sql);
				if($row = $rs->fetch_object()){
					$this->collMap['collid'] = $row->collid;
					$this->collMap['collectionname'] = $this->cleanOutStr($row->collectionname);
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
	
	public function setQueryVariables($overrideQry = false){
		global $clientRoot;
		if($overrideQry){
			$this->qryArr = $overrideQry;
			setCookie('editorquery','',time()-3600,($clientRoot?$clientRoot:'/'));
		}
		elseif(array_key_exists('q_identifier',$_REQUEST)){
			if($_REQUEST['q_identifier']) $this->qryArr['id'] = trim($_REQUEST['q_identifier']);
			if(array_key_exists('q_othercatalognumbers',$_REQUEST) && $_REQUEST['q_othercatalognumbers']) $this->qryArr['ocn'] = trim($_REQUEST['q_othercatalognumbers']);
			if(array_key_exists('q_recordedby',$_REQUEST) && $_REQUEST['q_recordedby']) $this->qryArr['rb'] = trim($_REQUEST['q_recordedby']);
			if(array_key_exists('q_recordnumber',$_REQUEST) && $_REQUEST['q_recordnumber']) $this->qryArr['rn'] = trim($_REQUEST['q_recordnumber']);
			if(array_key_exists('q_eventdate',$_REQUEST) && $_REQUEST['q_eventdate']) $this->qryArr['ed'] = trim($_REQUEST['q_eventdate']);
			if(array_key_exists('q_enteredby',$_REQUEST) && $_REQUEST['q_enteredby']) $this->qryArr['eb'] = trim($_REQUEST['q_enteredby']);
			if(array_key_exists('q_observeruid',$_REQUEST) && $_REQUEST['q_observeruid']) $this->qryArr['ouid'] = $_REQUEST['q_observeruid'];
			if(array_key_exists('q_processingstatus',$_REQUEST) && $_REQUEST['q_processingstatus']) $this->qryArr['ps'] = trim($_REQUEST['q_processingstatus']); 
			if(array_key_exists('q_datelastmodified',$_REQUEST) && $_REQUEST['q_datelastmodified']) $this->qryArr['dm'] = trim($_REQUEST['q_datelastmodified']);
			if(array_key_exists('q_ocrfrag',$_REQUEST) && $_REQUEST['q_ocrfrag']) $this->qryArr['ocr'] = trim($_REQUEST['q_ocrfrag']); 
			if(array_key_exists('q_imgonly',$_REQUEST) && $_REQUEST['q_imgonly']) $this->qryArr['io'] = 1;
			for($x=1;$x<4;$x++){
				if(array_key_exists('q_customfield'.$x,$_REQUEST) && $_REQUEST['q_customfield'.$x]) $this->qryArr['cf'.$x] = $_REQUEST['q_customfield'.$x];
				if(array_key_exists('q_customtype'.$x,$_REQUEST) && $_REQUEST['q_customtype'.$x]) $this->qryArr['ct'.$x] = $_REQUEST['q_customtype'.$x];
				if(array_key_exists('q_customvalue'.$x,$_REQUEST) && $_REQUEST['q_customvalue'.$x]) $this->qryArr['cv'.$x] = trim($_REQUEST['q_customvalue'.$x]);
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
						$vStr = trim($v);
						$iInFrag[] = $vStr;
						if(is_numeric($vStr) && substr($vStr,0,1) == '0'){
							$iInFrag[] = ltrim($vStr,0);
						}
					}
				}
				$iWhere = '';
				if($iBetweenFrag){
					$iWhere .= 'OR '.implode(' OR ',$iBetweenFrag);
				}
				if($iInFrag){
					if($isOccid){
						$iWhere .= 'OR (o.occid IN('.implode(',',$iInFrag).')) ';
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
						$v = trim($v);
						if(is_numeric($v)){
							$rnInFrag[] = $v;
						}
						else{
							$rnInFrag[] = '"'.$v.'"';
						}
					}
				}
				$rnWhere = '';
				if($rnBetweenFrag){
					$rnWhere .= 'OR '.implode(' OR ',$rnBetweenFrag);
				}
				if($rnInFrag){
					$rnWhere .= 'OR (o.recordnumber IN('.implode(',',$rnInFrag).')) ';
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
			if($cf){
				if($cf == 'ocrFragment' && !strpos($sqlWhere,'rawstr') && $cv){
					//Used when OCR frag comes from custom field search within basic query form 
					if(strpos($cv,'%') !== false){
						$sqlWhere .= 'AND (ocr.rawstr LIKE "'.$cv.'") ';
					}
					else{
						$sqlWhere .= 'AND (ocr.rawstr LIKE "%'.$cv.'%") ';
					}
				}
				elseif($ct=='NULL'){
					$sqlWhere .= 'AND (o.'.$cf.' IS NULL) ';
				}
				elseif($ct=='NOTNULL'){
					$sqlWhere .= 'AND (o.'.$cf.' IS NOT NULL) ';
				}
				elseif($ct=='LIKE' && $cv){
					if(strpos($cv,'%') !== false){
						$sqlWhere .= 'AND (o.'.$cf.' LIKE "'.$cv.'") ';
					}
					else{
						$sqlWhere .= 'AND (o.'.$cf.' LIKE "%'.$cv.'%") ';
					}
				}
				elseif($ct=='STARTS' && $cv){
					if(strpos($cv,'%') !== false){
						$sqlWhere .= 'AND (o.'.$cf.' LIKE "'.$cv.'") ';
					}
					else{
						$sqlWhere .= 'AND (o.'.$cf.' LIKE "'.$cv.'%") ';
					}
				}
				elseif($cv){
					$sqlWhere .= 'AND (o.'.$cf.' = "'.$cv.'") ';
				}
			}
		}
		if($this->crowdSourceMode){
			$sqlWhere .= 'AND (q.reviewstatus = 0) AND (o.processingstatus = "unprocessed") ';
		}
		if($this->collId) $sqlWhere .= 'AND (o.collid = '.$this->collId.') ';
		if($sqlWhere) $sqlWhere = 'WHERE '.substr($sqlWhere,4);
		if($sqlOrderBy) $sqlWhere .= 'ORDER BY '.substr($sqlOrderBy,1).' ';
		$sqlWhere .= 'LIMIT '.($occIndex>0?$occIndex.',':'').$recLimit;
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
			$sql = 'SELECT COUNT(DISTINCT o.occid) AS reccnt FROM omoccurrences o ';
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
		$sql = 'SELECT DISTINCT o.occid, o.collid, o.'.implode(',o.',$this->occFieldArr).' FROM omoccurrences o ';
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
			$this->occurrenceMap = $this->cleanOutArr($retArr);
			if($this->occid) $this->setLoanData();
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
					$newValue = $this->cleanInStr($occArr[$v]);
					$oldValue = $this->cleanInStr($oldValues[$v]);
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
				//Apply autoprocessing status if set
				if(array_key_exists('autoprocessingstatus',$occArr) && $occArr['autoprocessingstatus']){
					$occArr['processingstatus'] = $occArr['autoprocessingstatus'];
				}
				foreach($occArr as $ok => $ov){
					if(in_array($ok,$this->occFieldArr) && $ok != 'observeruid'){
						$vStr = $this->cleanInStr($ov);
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
				if(in_array('sciname',$editArr) && in_array('sciname',$occArr) && $occArr['sciname']){
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
			$sql = "INSERT INTO omoccurrences(collid, basisOfRecord, catalogNumber, otherCatalogNumbers, occurrenceid, ".
			"ownerInstitutionCode, family, sciname, tidinterpreted, scientificNameAuthorship, identifiedBy, dateIdentified, ".
			"identificationReferences, identificationremarks, identificationQualifier, typeStatus, recordedBy, recordNumber, ".
			"associatedCollectors, eventDate, year, month, day, startDayOfYear, endDayOfYear, ".
			"verbatimEventDate, habitat, substrate, fieldnumber, occurrenceRemarks, associatedTaxa, verbatimattributes, ".
			"dynamicProperties, reproductiveCondition, cultivationStatus, establishmentMeans, ".
			"lifestage, sex, individualcount, samplingprotocol, preparations, ".
			"country, stateProvince, county, municipality, locality, localitySecurity, localitysecurityreason, decimalLatitude, decimalLongitude, ".
			"geodeticDatum, coordinateUncertaintyInMeters, verbatimCoordinates, footprintwkt, ".
			"georeferencedBy, georeferenceProtocol, georeferenceSources, ".
			"georeferenceVerificationStatus, georeferenceRemarks, minimumElevationInMeters, maximumElevationInMeters, ".
			"verbatimElevation, disposition, language, duplicateQuantity, labelProject, processingstatus, recordEnteredBy, observeruid) ".
			"VALUES (".$occArr["collid"].",".
			($occArr["basisofrecord"]?'"'.$this->cleanInStr($occArr["basisofrecord"]).'"':"NULL").','.
			($occArr["catalognumber"]?'"'.$this->cleanInStr($occArr["catalognumber"]).'"':"NULL").','.
			($occArr['othercatalognumbers']?'"'.$this->cleanInStr($occArr['othercatalognumbers']).'"':'NULL').','.
			($occArr['occurrenceid']?'"'.$this->cleanInStr($occArr['occurrenceid']).'"':'NULL').','.
			($occArr["ownerinstitutioncode"]?'"'.$this->cleanInStr($occArr["ownerinstitutioncode"]).'"':"NULL").','.
			($occArr["family"]?'"'.$this->cleanInStr($occArr["family"]).'"':"NULL").','.
			'"'.$this->cleanInStr($occArr["sciname"]).'",'.
			($occArr["tidtoadd"]?$occArr["tidtoadd"]:"NULL").','.
			($occArr["scientificnameauthorship"]?'"'.$this->cleanInStr($occArr["scientificnameauthorship"]).'"':"NULL").','.
			($occArr["identifiedby"]?'"'.$this->cleanInStr($occArr["identifiedby"]).'"':"NULL").','.
			($occArr["dateidentified"]?'"'.$this->cleanInStr($occArr["dateidentified"]).'"':"NULL").','.
			($occArr["identificationreferences"]?'"'.$this->cleanInStr($occArr["identificationreferences"]).'"':"NULL").','.
			($occArr["identificationremarks"]?'"'.$this->cleanInStr($occArr["identificationremarks"]).'"':"NULL").','.
			($occArr["identificationqualifier"]?'"'.$this->cleanInStr($occArr["identificationqualifier"]).'"':"NULL").','.
			($occArr["typestatus"]?'"'.$this->cleanInStr($occArr["typestatus"]).'"':"NULL").','.
			($occArr["recordedby"]?'"'.$this->cleanInStr($occArr["recordedby"]).'"':"NULL").','.
			($occArr["recordnumber"]?'"'.$this->cleanInStr($occArr["recordnumber"]).'"':"NULL").','.
			($occArr["associatedcollectors"]?'"'.$this->cleanInStr($occArr["associatedcollectors"]).'"':"NULL").','.
			($occArr["eventdate"]?'"'.$occArr["eventdate"].'"':"NULL").','.
			($occArr["year"]?$occArr["year"]:"NULL").','.
			($occArr["month"] && is_numeric($occArr["month"])?$occArr["month"]:"NULL").','.
			($occArr["day"]?$occArr["day"]:"NULL").','.
			($occArr["startdayofyear"]?$occArr["startdayofyear"]:"NULL").','.
			($occArr["enddayofyear"]?$occArr["enddayofyear"]:"NULL").','.
			($occArr["verbatimeventdate"]?'"'.$this->cleanInStr($occArr["verbatimeventdate"]).'"':"NULL").','.
			($occArr["habitat"]?'"'.$this->cleanInStr($occArr["habitat"]).'"':"NULL").','.
			($occArr["substrate"]?'"'.$this->cleanInStr($occArr["substrate"]).'"':"NULL").','.
			($occArr['fieldnumber']?'"'.$this->cleanInStr($occArr['fieldnumber']).'"':'NULL').','.
			($occArr["occurrenceremarks"]?'"'.$this->cleanInStr($occArr["occurrenceremarks"]).'"':"NULL").','.
			($occArr["associatedtaxa"]?'"'.$this->cleanInStr($occArr["associatedtaxa"]).'"':"NULL").','.
			($occArr["verbatimattributes"]?'"'.$this->cleanInStr($occArr["verbatimattributes"]).'"':"NULL").','.
			($occArr["dynamicproperties"]?'"'.$this->cleanInStr($occArr["dynamicproperties"]).'"':"NULL").','.
			($occArr["reproductivecondition"]?'"'.$this->cleanInStr($occArr["reproductivecondition"]).'"':"NULL").','.
			(array_key_exists("cultivationstatus",$occArr)?"1":"0").",".
			($occArr["establishmentmeans"]?'"'.$this->cleanInStr($occArr["establishmentmeans"]).'"':"NULL").','.
			($occArr['lifestage']?'"'.$this->cleanInStr($occArr['lifestage']).'"':'NULL').','.
			($occArr['sex']?'"'.$this->cleanInStr($occArr['sex']).'"':'NULL').','.
			($occArr['individualcount']?'"'.$this->cleanInStr($occArr['individualcount']).'"':'NULL').','.
			($occArr['samplingprotocol']?'"'.$this->cleanInStr($occArr['samplingprotocol']).'"':'NULL').','.
			($occArr['preparations']?'"'.$this->cleanInStr($occArr['preparations']).'"':'NULL').','.
			($occArr['country']?'"'.$this->cleanInStr($occArr['country']).'"':'NULL').','.
			($occArr["stateprovince"]?'"'.$this->cleanInStr($occArr["stateprovince"]).'"':"NULL").','.
			($occArr["county"]?'"'.$this->cleanInStr($occArr["county"]).'"':"NULL").','.
			($occArr["municipality"]?'"'.$this->cleanInStr($occArr["municipality"]).'"':"NULL").','.
			($occArr["locality"]?'"'.$this->cleanInStr($occArr["locality"]).'"':"NULL").','.
			(array_key_exists("localitysecurity",$occArr)?"1":"0").','.
			($occArr["localitysecurityreason"]?'"'.$this->cleanInStr($occArr["localitysecurityreason"]).'"':"NULL").','.
			($occArr["decimallatitude"]?$occArr["decimallatitude"]:"NULL").','.
			($occArr["decimallongitude"]?$occArr["decimallongitude"]:"NULL").','.
			($occArr["geodeticdatum"]?'"'.$this->cleanInStr($occArr["geodeticdatum"]).'"':"NULL").','.
			($occArr['coordinateuncertaintyinmeters']?$occArr['coordinateuncertaintyinmeters']:'NULL').','.
			($occArr["verbatimcoordinates"]?'"'.$this->cleanInStr($occArr["verbatimcoordinates"]).'"':"NULL").','.
			($occArr['footprintwkt']?'"'.$this->cleanInStr($occArr['footprintwkt']).'"':'NULL').','.
			($occArr["georeferencedby"]?'"'.$this->cleanInStr($occArr["georeferencedby"]).'"':"NULL").','.
			($occArr["georeferenceprotocol"]?'"'.$this->cleanInStr($occArr["georeferenceprotocol"]).'"':"NULL").','.
			($occArr["georeferencesources"]?'"'.$this->cleanInStr($occArr["georeferencesources"]).'"':"NULL").','.
			($occArr["georeferenceverificationstatus"]?'"'.$this->cleanInStr($occArr["georeferenceverificationstatus"]).'"':"NULL").','.
			($occArr["georeferenceremarks"]?'"'.$this->cleanInStr($occArr["georeferenceremarks"]).'"':"NULL").','.
			($occArr["minimumelevationinmeters"]?$occArr["minimumelevationinmeters"]:"NULL").','.
			($occArr["maximumelevationinmeters"]?$occArr["maximumelevationinmeters"]:"NULL").','.
			($occArr["verbatimelevation"]?'"'.$this->cleanInStr($occArr["verbatimelevation"]).'"':"NULL").','.
			($occArr['disposition']?'"'.$this->cleanInStr($occArr["disposition"]).'"':'NULL').','.
			($occArr["language"]?'"'.$this->cleanInStr($occArr["language"]).'"':"NULL").','.
			($occArr["duplicatequantity"]?$occArr["duplicatequantity"]:"NULL").','.
			($occArr["labelproject"]?'"'.$this->cleanInStr($occArr["labelproject"]).'"':"NULL").','.
			($occArr["processingstatus"]?'"'.$occArr["processingstatus"].'"':"NULL").',"'.
			$occArr["userid"].'",'.$occArr["observeruid"].') ';
			//echo "<div>".$sql."</div>";
			if($this->conn->query($sql)){
				$this->occid = $this->conn->insert_id;
				//Create and insert Symbiota GUID (UUID)
				$guid = UuidFactory::getUuidV4();
				if(!$this->conn->query('INSERT INTO guidoccurrences(guid,occid) VALUES("'.$guid.'",'.$this->occid.')')){
					$status .= ' (Warning: Symbiota GUID mapping failed)';
				}
			}
			else{
				$status = "ERROR - failed to add occurrence record: ".$this->conn->error.'<br/>SQL: '.$sql;
			}
		}
		return $status;
	}
	
	public function deleteOccurrence($delOccid){
		global $charset, $userDisplayName;
		$status = '';
		if(is_numeric($delOccid)){
			//Archive data, first grab occurrence data
			$archiveArr = array();
			$sql = 'SELECT * FROM omoccurrences WHERE occid = '.$delOccid;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_assoc()){
				foreach($r as $k => $v){
					if($v) $archiveArr[$k] = $this->encodeStrTargeted($v,$charset,'utf8');
				}
			}
			$rs->close();
			if($archiveArr){
				//Archive determinations history
				$detArr = array();
				$sql = 'SELECT * FROM omoccurdeterminations WHERE occid = '.$delOccid;
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_assoc()){
					$detId = $r['detid'];
					foreach($r as $k => $v){
						if($v) $detArr[$detId][$k] = $this->encodeStrTargeted($v,$charset,'utf8');
					}
					//Archive determinations
					$detObj = json_encode($detArr[$detId]);
					$sqlArchive = 'UPDATE guidoccurdeterminations '.
					'SET archivestatus = 1, archiveobj = "'.$this->cleanInStr($this->encodeStrTargeted($detObj,'utf8',$charset)).'" '.
					'WHERE (detid = '.$detId.')';
					$this->conn->query($sqlArchive);
				}
				$rs->close();
				$archiveArr['dets'] = $detArr;
				
				//Archive image history
				$imgArr = array();
				$sql = 'SELECT * FROM images WHERE occid = '.$delOccid;
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_assoc()){
					$imgId = $r['imgid'];
					foreach($r as $k => $v){
						if($v) $imgArr[$imgId][$k] = $this->encodeStrTargeted($v,$charset,'utf8');
					}
					//Archive determinations
					$imgObj = json_encode($imgArr[$imgId]);
					$sqlArchive = 'UPDATE guidimages '.
					'SET archivestatus = 1, archiveobj = "'.$this->cleanInStr($this->encodeStrTargeted($imgObj,'utf8',$charset)).'" '.
					'WHERE (imgid = '.$imgId.')';
					$this->conn->query($sqlArchive);
				}
				$rs->close();
				$archiveArr['imgs'] = $imgArr;

				//Archive complete occurrence record
				$archiveArr['dateDeleted'] = date('r').' by '.$userDisplayName;
				$archiveObj = json_encode($archiveArr);
				$sqlArchive = 'UPDATE guidoccurrences '.
				'SET archivestatus = 1, archiveobj = "'.$this->cleanInStr($this->encodeStrTargeted($archiveObj,'utf8',$charset)).'" '.
				'WHERE (occid = '.$delOccid.')';
				//echo $sqlArchive;
				$this->conn->query($sqlArchive);
			}

			//Go ahead and delete 
			$sqlDel = 'DELETE FROM omoccurrences WHERE (occid = '.$delOccid.')';
			if($this->conn->query($sqlDel)){
				$status = 'SUCCESS: Occurrence Record Deleted!';
			}
			else{
				$status = 'ERROR trying to delete occurrence record: '.$this->conn->error;
			}
		}
		return $status;
	}
	
	public function setLoanData(){
		$sql = 'SELECT l.loanid, l.datedue, i.institutioncode '.
			'FROM omoccurloanslink ll INNER JOIN omoccurloans l ON ll.loanid = l.loanid '.
			'INNER JOIN institutions i ON l.iidBorrower = i.iid '.
			'WHERE ll.returndate IS NULL AND l.dateclosed IS NULL AND occid = '.$this->occid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$this->occurrenceMap[$this->occid]['loan']['id'] = $r->loanid;
			$this->occurrenceMap[$this->occid]['loan']['date'] = $r->datedue;
			$this->occurrenceMap[$this->occid]['loan']['code'] = $r->institutioncode;
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
		$ov = $this->cleanInStr($oldValue);
		$nv = $this->cleanInStr($newValue);
		if($fn && ($ov || $nv)){
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
			if(!$buMatch || $ov===''){
				$sql .= 'AND (o.'.$fn.' '.($ov===''?'IS NULL':'= "'.$ov.'"').')'.
					') rt ON o2.occid = rt.occid SET o2.'.$fn.' = '.($nv===''?'NULL':'"'.$nv.'"').' ';
			}
			else{
				//Selected "Match any part of field"
				$sql .= 'AND (o.'.$fn.' LIKE "%'.$ov.'%")'.
					') rt ON o2.occid = rt.occid SET o2.'.$fn.' = REPLACE(o2.'.$fn.',"'.$ov.'","'.$nv.'") ';
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
		$ov = $this->cleanInStr($oldValue);
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
		if(!$buMatch || $ov===""){
			$sql .= ' AND (o.'.$fn.' '.($ov===''?'IS NULL':'= "'.$ov.'"').')';
		}
		else{
			$sql .= ' AND (o.'.$fn.' LIKE "%'.$ov.'%")';
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
			'verbatimcoordinates','coordinateuncertaintyinmeters','footprintwkt','geodeticdatum','minimumelevationinmeters',
			'maximumelevationinmeters','verbatimelevation','verbatimcoordinates','georeferencedby','georeferenceprotocol',
			'georeferencesources','georeferenceverificationstatus','georeferenceremarks','habitat','substrate',
			'lifestage', 'sex', 'individualcount', 'samplingprotocol', 'preparations',
			'associatedtaxa','basisofrecord','language','labelproject');
		return array_intersect_key($fArr,array_flip($locArr)); 
	}

	//Genetic links
	public function getGeneticArr(){
		$retArr = array();
		if($this->occid){
			$sql = 'SELECT idoccurgenetic, identifier, resourcename, locus, resourceurl, notes '.
				'FROM omoccurgenetic '.
				'WHERE occid = '.$this->occid;
			$result = $this->conn->query($sql);
			if($result){
				while($r = $result->fetch_object()){
					$retArr[$r->idoccurgenetic]['id'] = $r->identifier;
					$retArr[$r->idoccurgenetic]['name'] = $r->resourcename;
					$retArr[$r->idoccurgenetic]['locus'] = $r->locus;
					$retArr[$r->idoccurgenetic]['resourceurl'] = $r->resourceurl;
					$retArr[$r->idoccurgenetic]['notes'] = $r->notes;
				}
				$result->free();
	        }
	        else{
	        	trigger_error('Unable to get genetic data; '.$this->conn->error,E_USER_WARNING);
	        }
		}
		return $retArr;
	}
	
	public function editGeneticResource($genArr){
		$sql = 'UPDATE omoccurgenetic SET '.
			'identifier = "'.$this->cleanInStr($genArr['identifier']).'", '.
			'resourcename = "'.$this->cleanInStr($genArr['resourcename']).'", '.
			'locus = '.($genArr['locus']?'"'.$this->cleanInStr($genArr['locus']).'"':'NULL').', '.
			'resourceurl = '.($genArr['resourceurl']?'"'.$genArr['resourceurl'].'"':'').', '.
			'notes = '.($genArr['notes']?'"'.$this->cleanInStr($genArr['notes']).'"':'NULL').' '.
			'WHERE idoccurgenetic = '.$genArr['genid'];
		if(!$this->conn->query($sql)){
			return 'ERROR editing genetic resource #'.$id.': '.$this->conn->error;
		}
		return 'Genetic resource editted successfully';
	}
	
	public function deleteGeneticResource($id){
		$sql = 'DELETE FROM omoccurgenetic WHERE idoccurgenetic = '.$id;
		if(!$this->conn->query($sql)){
			return 'ERROR deleting genetic resource #'.$id.': '.$this->conn->error;
		}
		return 'Genetic resource deleted successfully!';
	}
	
	public function addGeneticResource($genArr){
		$sql = 'INSERT INTO omoccurgenetic(occid, identifier, resourcename, locus, resourceurl, notes) '.
			'VALUES('.$this->cleanInStr($genArr['occid']).',"'.$this->cleanInStr($genArr['identifier']).'","'.
			$this->cleanInStr($genArr['resourcename']).'",'.
			($genArr['locus']?'"'.$this->cleanInStr($genArr['locus']).'"':'NULL').','.
			($genArr['resourceurl']?'"'.$this->cleanInStr($genArr['resourceurl']).'"':'NULL').','.
			($genArr['notes']?'"'.$this->cleanInStr($genArr['notes']).'"':'NULL').')';
		if(!$this->conn->query($sql)){
			return 'ERROR Adding new genetic resource: '.$this->conn->error;
		}
		return 'Genetic resource added successfully!';
	}

	//Label OCR processing methods
	public function getRawTextFragments(){
		$retArr = array();
		if($this->occid){
			$sql = 'SELECT r.prlid, r.imgid, r.rawstr, r.notes, r.source '.
				'FROM specprocessorrawlabels r INNER JOIN images i ON r.imgid = i.imgid '.
				'WHERE i.occid = '.$this->occid;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->imgid][$r->prlid]['raw'] = $this->cleanOutStr($r->rawstr);
				$retArr[$r->imgid][$r->prlid]['notes'] = $this->cleanOutStr($r->notes);
				$retArr[$r->imgid][$r->prlid]['source'] = $this->cleanOutStr($r->source);
			}
			$rs->free();
		} 
		return $retArr;
	}

	public function insertTextFragment($imgId,$rawFrag,$notes){
		if($imgId && $rawFrag){
			$statusStr = '';
			$sql = 'INSERT INTO specprocessorrawlabels(imgid,rawstr,notes) '.
				'VALUES ('.$imgId.',"'.$this->cleanRawFragment($rawFrag).'","'.$this->cleanInStr($notes).'")';
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
			$sql = 'UPDATE specprocessorrawlabels SET rawstr = "'.$this->cleanRawFragment($rawFrag).'", notes = "'.$this->cleanInStr($notes).'" '.
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
				'sourceurl, copyright, notes, occid, username, sortsequence '. 
				'FROM images '.
				'WHERE (occid = '.$this->occid.') ORDER BY sortsequence';
			//echo $sql;
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$imgId = $row->imgid;
				$imageMap[$imgId]["url"] = $row->url;
				$imageMap[$imgId]["tnurl"] = $row->thumbnailurl;
				$imageMap[$imgId]["origurl"] = $row->originalurl;
				$imageMap[$imgId]["caption"] = $this->cleanOutStr($row->caption);
				$imageMap[$imgId]["photographer"] = $this->cleanOutStr($row->photographer);
				$imageMap[$imgId]["photographeruid"] = $row->photographeruid;
				$imageMap[$imgId]["sourceurl"] = $row->sourceurl;
				$imageMap[$imgId]["copyright"] = $this->cleanOutStr($row->copyright);
				$imageMap[$imgId]["notes"] = $this->cleanOutStr($row->notes);
				$imageMap[$imgId]["occid"] = $row->occid;
				$imageMap[$imgId]["username"] = $this->cleanOutStr($row->username);
				$imageMap[$imgId]["sortseq"] = $row->sortsequence;
			}
			$result->free();
		}
		return $imageMap;
	}

	public function getEditArr(){
		$retArr = array();
		$sql = 'SELECT e.ocedid, e.fieldname, e.fieldvalueold, e.fieldvaluenew, e.reviewstatus, e.appliedstatus, '.
			'CONCAT_WS(", ",u.lastname,u.firstname) as editor, e.initialtimestamp '.
			'FROM omoccuredits e INNER JOIN users u ON e.uid = u.uid '.
			'WHERE e.occid = '.$this->occid.' ORDER BY e.initialtimestamp DESC ';
		//echo $sql;
		$result = $this->conn->query($sql);
		if($result){
			$cnt = 0;
			while($r = $result->fetch_object()){
				$k = substr($r->initialtimestamp,0,16);
				if(!isset($retArr[$k]['editor'])){
					$retArr[$k]['editor'] = $r->editor;
					$retArr[$k]['ts'] = $r->initialtimestamp;
				}
				$retArr[$k][$cnt]['fieldname'] = $r->fieldname;
				$retArr[$k][$cnt]['old'] = $r->fieldvalueold;
				$retArr[$k][$cnt]['new'] = $r->fieldvaluenew;
				$retArr[$k][$cnt]['reviewstatus'] = $r->reviewstatus;
				$retArr[$k][$cnt]['appliedstatus'] = $r->appliedstatus;
				$cnt++;
			}
			$result->free();
        }
        else{
        	trigger_error('Unable to get edits; '.$this->conn->error,E_USER_WARNING);
        }
		return $retArr;
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
	private function encodeStrTargeted($inStr,$inCharset,$outCharset){
		if($inCharset == $outCharset) return $inStr;
		$retStr = $inStr;
		if($inCharset == "latin" && $outCharset == 'utf8'){
			if(mb_detect_encoding($retStr,'UTF-8,ISO-8859-1',true) == "ISO-8859-1"){
				$retStr = utf8_encode($retStr);
			}
		}
		elseif($inCharset == "utf8" && $outCharset == 'latin'){
			if(mb_detect_encoding($retStr,'UTF-8,ISO-8859-1') == "UTF-8"){
				$retStr = utf8_decode($retStr);
			}
		}
		return $retStr;
	}
	
	protected function encodeStr($inStr){
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
	
	protected function cleanOutArr($inArr){
		$outArr = array();
		foreach($inArr as $k => $v){
			$outArr[$k] = $this->cleanOutStr($v);
		}
		return $outArr;
	}
	
	protected function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	protected function cleanInStr($str){
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