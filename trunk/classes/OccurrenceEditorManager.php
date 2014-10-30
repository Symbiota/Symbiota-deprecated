<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/OccurrenceEditorDeterminations.php');
include_once($serverRoot.'/classes/OccurrenceEditorImages.php');
include_once($serverRoot.'/classes/UuidFactory.php');

class OccurrenceEditorManager {

	protected $conn;
	protected $occid;
	private $collId;
	protected $collMap = array();
	private $occurrenceMap = array();
	private $occFieldArr = array();
	private $sqlWhere;
	private $qryArr = array();
	private $crowdSourceMode = 0;
	private $exsiccatiMode = 0;
	private $symbUid;
	protected $errorStr = '';


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
			$this->occid = $this->cleanInStr($id);
		}
	}

	public function getOccId(){
		return $this->occid;
	}

	public function setCollId($id){
		if($id && is_numeric($id)){
			if($id != $this->collId){
				unset($this->collMap);
				$this->collMap = array();
			}
			$this->collId = $this->cleanInStr($id);
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
		if(!$this->collId) $this->collId = $this->collMap['collid'];
		return $this->collMap;
	}
	
	public function getCollId(){
		if(!$this->collId){
			$this->getCollMap();
		}
		return $this->collId;
	}

	public function setSymbUid($id){
		$this->symbUid = $id;
	}

	public function setCrowdSourceMode($m){
		$this->crowdSourceMode = $m;
	}

	public function setExsiccatiMode($exsMode){
		$this->exsiccatiMode = $exsMode;
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
			if(array_key_exists('q_withoutimg',$_REQUEST) && $_REQUEST['q_withoutimg']) $this->qryArr['woi'] = 1;
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
        if ($this->qryArr==null) {
            // supress warnings on array_key_exists(key,null) calls below
            $this->qryArr=array();
        }
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
					$v = trim($v);
					if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$v)){
						//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
						$v = str_ireplace(array('>',' and ','<'),array('',' - ',''),$v);
					}
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
						if(is_numeric($vStr)){
							if($iInFrag){
								//Only tag as numeric if there are more than one term (if not, it doesn't match what the sort order is)
								$ocnIsNum = true;
							}
							if(substr($vStr,0,1) == '0'){
								//Add value with left padded zeros removed
								$iInFrag[] = ltrim($vStr,0);
							}
						}
						$iInFrag[] = $vStr;
					}
				}
				$iWhere = '';
				if($iBetweenFrag){
					$iWhere .= 'OR '.implode(' OR ',$iBetweenFrag);
				}
				if($iInFrag){
					if($isOccid){
						foreach($iInFrag as $term){
							if(substr($term,0,1) == '<' || substr($term,0,1) == '>'){
								$iWhere .= 'OR (o.occid '.substr($term,0,1).' '.trim(substr($term,1)).') ';
							}
							else{
								$iWhere .= 'OR (o.occid = '.$term.') ';
							}
						}
					}
					else{
						foreach($iInFrag as $term){
							if(substr($term,0,1) == '<' || substr($term,0,1) == '>'){
								$tStr = trim(substr($term,1));
								if(!is_numeric($tStr)) $tStr = '"'.$tStr.'"';
								$iWhere .= 'OR (o.catalognumber '.substr($term,0,1).' '.$tStr.') ';
							}
							else{
								$iWhere .= 'OR (o.catalognumber = "'.$term.'") ';
							}
						}
					}
				}
				$sqlWhere .= 'AND ('.substr($iWhere,3).') ';
				if(!$isOccid){
					if($searchIsNum){
						$sqlOrderBy .= ',(o.catalogNumber+1)';
					}
					else{
						$sqlOrderBy .= ',o.catalogNumber';
					}
				}
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
					$v = trim($v);
					if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$v)){
						//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
						$v = str_ireplace(array('>',' and ','<'),array('',' - ',''),$v);
					}
					if(strpos('%',$v) !== false){
						$ocnBetweenFrag[] = '(o.othercatalognumbers LIKE "'.$v.'")';
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
						$ocnInFrag[] = $v;
						if(is_numeric($v)){
							$ocnIsNum = true;
							if(substr($v,0,1) == '0'){
								//Add value with left padded zeros removed
								$ocnInFrag[] = ltrim($vStr,0);
							}
						}
					}
				}
				$ocnWhere = '';
				if($ocnBetweenFrag){
					$ocnWhere .= 'OR '.implode(' OR ',$ocnBetweenFrag);
				}
				if($ocnInFrag){
					foreach($ocnInFrag as $term){
						if(substr($term,0,1) == '<' || substr($term,0,1) == '>'){
							$tStr = trim(substr($term,1));
							if(!is_numeric($tStr)) $tStr = '"'.$tStr.'"';
							$ocnWhere .= 'OR (o.othercatalognumbers '.substr($term,0,1).' '.$tStr.') ';
						}
						else{
							$ocnWhere .= 'OR (o.othercatalognumbers = "'.$term.'") ';
						}
					}
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
					$v = trim($v);
					if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$v)){
						//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
						$v = str_ireplace(array('>',' and ','<'),array('',' - ',''),$v);
					}
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
						$condStr = '=';
						if(substr($v,0,1) == '<' || substr($v,0,1) == '>'){
							$condStr = substr($v,0,1);
							$v = trim(substr($v,1));
						}
						if(is_numeric($v)){
							$rnInFrag[] = $condStr.' '.$v;
						}
						else{
							$rnInFrag[] = $condStr.' "'.$v.'"';
						}
					}
				}
				$rnWhere = '';
				if($rnBetweenFrag){
					$rnWhere .= 'OR '.implode(' OR ',$rnBetweenFrag);
				}
				if($rnInFrag){
					foreach($rnInFrag as $term){
						$rnWhere .= 'OR (o.recordnumber '.$term.') ';
					}
				}
				$sqlWhere .= 'AND ('.substr($rnWhere,3).') ';
			}
		}
		//recordedBy: collector
		if(array_key_exists('rb',$this->qryArr)){
			if(strtolower($this->qryArr['rb']) == 'is null'){
				$sqlWhere .= 'AND (o.recordedby IS NULL) ';
			}
			else{
				$sqlWhere .= 'AND (o.recordedby LIKE "'.$this->qryArr['rb'].'%") ';
				$sqlOrderBy .= ',(o.recordnumber+1)';
			}
		}
		//eventDate: collection date
		if(array_key_exists('ed',$this->qryArr)){
			if(strtolower($this->qryArr['ed']) == 'is null'){
				$sqlWhere .= 'AND (o.eventdate IS NULL) ';
			}
			else{
				$edv = trim($this->qryArr['ed']);
				if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$edv)){
					//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
					$edv = str_ireplace(array('>',' and ','<'),array('',' - ',''),$edv);
				}
				if($p = strpos($edv,' - ')){
					$sqlWhere .= 'AND (o.eventdate BETWEEN "'.trim(substr($edv,0,$p)).'" AND "'.trim(substr($edv,$p+3)).'") ';
				}
				elseif(substr($edv,0,1) == '<' || substr($edv,0,1) == '>'){
					$sqlWhere .= 'AND (o.eventdate '.substr($edv,0,1).' "'.trim(substr($edv,1)).'") ';
				}
				else{
					$sqlWhere .= 'AND (o.eventdate = "'.$edv.'") ';
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
			$dmv = trim($this->qryArr['dm']);
			if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$dmv)){
				//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
				$dmv = str_ireplace(array('>',' and ','<'),array('',' - ',''),$dmv);
			}
			if($p = strpos($dmv,' - ')){
				$sqlWhere .= 'AND (DATE(o.datelastmodified) BETWEEN "'.trim(substr($dmv,0,$p)).'" AND "'.trim(substr($dmv,$p+3)).'") ';
			}
			elseif(substr($dmv,0,1) == '<' || substr($dmv,0,1) == '>'){
				$sqlWhere .= 'AND (o.datelastmodified '.substr($dmv,0,1).' "'.trim(substr($dmv,1)).'") ';
			}
			else{
				$sqlWhere .= 'AND (DATE(o.datelastmodified) = "'.$dmv.'") ';
			}
			$sqlOrderBy .= ',o.datelastmodified';
		}
		//Processing status
		if(array_key_exists('ps',$this->qryArr)){
			if($this->qryArr['ps'] == 'isnull'){
				$sqlWhere .= 'AND (o.processingstatus IS NULL) ';
			}
			else{
				$sqlWhere .= 'AND (o.processingstatus LIKE "'.$this->qryArr['ps'].'%") ';
			}
		}
		//Without images
		if(array_key_exists('woi',$this->qryArr)){
			$sqlWhere .= 'AND (i.imgid IS NULL) ';
		}
		//OCR
		if(array_key_exists('ocr',$this->qryArr)){
			//Used when OCR frag comes from set field within queryformcrowdsourcing
			$sqlWhere .= 'AND (ocr.rawstr LIKE "%'.$this->qryArr['ocr'].'%") ';
		}
		//Custom search fields
		for($x=1;$x<4;$x++){
			$cf = (array_key_exists('cf'.$x,$this->qryArr)?$this->qryArr['cf'.$x]:'');
			$ct = (array_key_exists('ct'.$x,$this->qryArr)?$this->qryArr['ct'.$x]:'');
			$cv = (array_key_exists('cv'.$x,$this->qryArr)?$this->qryArr['cv'.$x]:'');
			if($cf){
				if($cf == 'ocrFragment' && !strpos($sqlWhere,'rawstr')){
					//Used when OCR frag comes from custom field search within basic query form
					$cf = 'ocr.rawstr';
				}
				else{
					$cf = 'o.'.$cf;
				}
				if($ct=='NULL'){
					$sqlWhere .= 'AND ('.$cf.' IS NULL) ';
				}
				elseif($ct=='NOTNULL'){
					$sqlWhere .= 'AND ('.$cf.' IS NOT NULL) ';
				}
				elseif($ct=='GREATER' && $cv){
					if(!is_numeric($cv)) $cv = '"'.$cv.'"';
					$sqlWhere .= 'AND ('.$cf.' > '.$cv.') ';
				}
				elseif($ct=='LESS' && $cv){
					if(!is_numeric($cv)) $cv = '"'.$cv.'"';
					$sqlWhere .= 'AND ('.$cf.' < '.$cv.') ';
				}
				elseif($ct=='LIKE' && $cv){
					if(strpos($cv,'%') !== false){
						$sqlWhere .= 'AND ('.$cf.' LIKE "'.$cv.'") ';
					}
					else{
						$sqlWhere .= 'AND ('.$cf.' LIKE "%'.$cv.'%") ';
					}
				}
				elseif($ct=='STARTS' && $cv){
					if(strpos($cv,'%') !== false){
						$sqlWhere .= 'AND ('.$cf.' LIKE "'.$cv.'") ';
					}
					else{
						$sqlWhere .= 'AND ('.$cf.' LIKE "'.$cv.'%") ';
					}
				}
				elseif($cv){
					$sqlWhere .= 'AND ('.$cf.' = "'.$cv.'") ';
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
		$recCnt = false;
		if($this->sqlWhere){
			$sql = 'SELECT COUNT(DISTINCT o.occid) AS reccnt FROM omoccurrences o ';
			$this->addTableJoins($sql);
			/*
			if(strpos($sqlWhere,'ocr.rawstr')){
				if(strpos($sqlWhere,'ocr.rawstr IS NULL')){
					$sql .= 'LEFT JOIN images i ON o.occid = i.occid LEFT JOIN specprocessorrawlabels ocr ON i.imgid = ocr.imgid ';
				}
				else{
					$sql .= 'INNER JOIN images i ON o.occid = i.occid INNER JOIN specprocessorrawlabels ocr ON i.imgid = ocr.imgid ';
				}
			}
			elseif(array_key_exists('io',$this->qryArr)){
				$sql .= 'INNER JOIN images i ON o.occid = i.occid ';
			}
			elseif(array_key_exists('woi',$this->qryArr)){
				$sql .= 'LEFT JOIN images i ON o.occid = i.occid ';
			}
			if($this->crowdSourceMode){
				$sql .= 'INNER JOIN omcrowdsourcequeue q ON q.occid = o.occid ';
			}
			*/
			$sqlWhere = $this->sqlWhere;
			if($obPos = strpos($sqlWhere,' ORDER BY')){
				$sqlWhere = substr($sqlWhere,0,$obPos);
			}
			if($obPos = strpos($sqlWhere,' LIMIT ')){
				$sqlWhere = substr($sqlWhere,0,$obPos);
			}
			$sql .= $sqlWhere;
			//echo '<div>'.$sql.'</div>'; exit;
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
			$this->addTableJoins($sql);
			/*
			if(strpos($this->sqlWhere,'ocr.rawstr')){
				if(strpos($this->sqlWhere,'ocr.rawstr IS NULL')){
					$sql .= 'LEFT JOIN images i ON o.occid = i.occid LEFT JOIN specprocessorrawlabels ocr ON i.imgid = ocr.imgid ';
				}
				else{
					$sql .= 'INNER JOIN images i ON o.occid = i.occid INNER JOIN specprocessorrawlabels ocr ON i.imgid = ocr.imgid ';
				}
			}
			elseif(array_key_exists('io',$this->qryArr)){
				$sql .= 'INNER JOIN images i ON o.occid = i.occid ';
			}
			elseif(array_key_exists('woi',$this->qryArr)){
				$sql .= 'LEFT JOIN images i ON o.occid = i.occid ';
			}
			if($this->crowdSourceMode){
				$sql .= 'INNER JOIN omcrowdsourcequeue q ON q.occid = o.occid ';
			}
			*/
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
			if(!$this->occid && $retArr && count($retArr) == 1){
				$this->occid = $occid;
			}
			$this->occurrenceMap = $this->cleanOutArr($retArr);
			if($this->occid){
				$this->setLoanData();
				if($this->exsiccatiMode) $this->setExsiccati();
			}
		}
	}
	
	private function addTableJoins(&$sql){
		if(strpos($this->sqlWhere,'ocr.rawstr')){
			if(strpos($this->sqlWhere,'ocr.rawstr IS NULL')){
				$sql .= 'LEFT JOIN images i ON o.occid = i.occid LEFT JOIN specprocessorrawlabels ocr ON i.imgid = ocr.imgid ';
			}
			else{
				$sql .= 'INNER JOIN images i ON o.occid = i.occid INNER JOIN specprocessorrawlabels ocr ON i.imgid = ocr.imgid ';
			}
		}
		elseif(array_key_exists('io',$this->qryArr)){
			$sql .= 'INNER JOIN images i ON o.occid = i.occid ';
		}
		elseif(array_key_exists('woi',$this->qryArr)){
			$sql .= 'LEFT JOIN images i ON o.occid = i.occid ';
		}
		if($this->crowdSourceMode){
			$sql .= 'INNER JOIN omcrowdsourcequeue q ON q.occid = o.occid ';
		}
	}

	public function editOccurrence($occArr,$autoCommit){
		global $paramsArr;
		$status = '';
		if(!$autoCommit && $this->getObserverUid() == $paramsArr['uid']){
			//Specimen is owned by editor
			$autoCommit = 1;
		}
		if($autoCommit == 3){
			//Is a Taxon Editor, but without explicit rights to edit this occurrence
			$autoCommit = 0;
		}

		//Processing edit
		$editedFields = trim($occArr['editedfields']);
		$editArr = array_unique(explode(';',$editedFields));
		foreach($editArr as $k => $fName){
			if(!trim($fName)){
				unset($editArr[$k]);
			} else if(strcasecmp($fName, 'exstitle') == 0) {
				unset($editArr[$k]);
				$editArr[$k] = 'title';
			}
		}
		if($editArr){
			//Deal with scientific name changes, which isn't allows handled correctly with AJAX code
			if(in_array('sciname',$editArr) && $occArr['sciname'] && !$occArr['tidinterpreted']){
				$sql2 = 'SELECT t.tid, t.author, ts.family '.
					'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
					'WHERE ts.taxauthid = 1 AND sciname = "'.$occArr['sciname'].'"';
				$rs2 = $this->conn->query($sql2);
				while($r2 = $rs2->fetch_object()){
					$occArr['tidinterpreted'] = $r2->tid;
					if(!$occArr['scientificnameauthorship']) $occArr['scientificnameauthorship'] = $r2->author;
					if(!$occArr['family']) $occArr['family'] = $r2->family;
				}
				$rs2->free();
			}
			//Add edits to omoccuredits
			//Get old values before they are changed
			$sql = '';
			if(in_array('ometid',$editArr) || in_array('exsnumber',$editArr)){
				//Exsiccati edit has been submitted
				$sql = 'SELECT '.str_replace(array('ometid','exstitle'),array('et.ometid','et.title'),($editArr?implode(',',$editArr):'')).',et.title'.
				(in_array('processingstatus',$editArr)?'':',processingstatus').(in_array('recordenteredby',$editArr)?'':',recordenteredby').
				' FROM omoccurrences o LEFT JOIN omexsiccatiocclink el ON o.occid = el.occid '.
				'LEFT JOIN omexsiccatinumbers en ON el.omenid = en.omenid '.
				'LEFT JOIN omexsiccatititles et ON en.ometid = et.ometid '.
				'WHERE o.occid = '.$occArr['occid'];
			}
			else{
				$sql = 'SELECT '.($editArr?implode(',',$editArr):'').(in_array('processingstatus',$editArr)?'':',processingstatus').
				(in_array('recordenteredby',$editArr)?'':',recordenteredby').
				' FROM omoccurrences WHERE occid = '.$occArr['occid'];
			}
			//echo $sql;
			$rs = $this->conn->query($sql);
			$oldValues = $rs->fetch_assoc();
			$rs->free();

			//Version edits
			$sqlEditsBase = 'INSERT INTO omoccuredits(occid,reviewstatus,appliedstatus,uid,fieldname,fieldvaluenew,fieldvalueold) '.
				'VALUES ('.$occArr['occid'].',1,'.($autoCommit?'1':'0').','.$paramsArr['uid'].',';
			foreach($editArr as $fieldName){
				if(!array_key_exists($fieldName,$occArr)){
					//Field is a checkbox that is unchecked: cultivationstatus, localitysecurity
					$occArr[$fieldName] = 0;
				}
				$newValue = $this->cleanInStr($occArr[$fieldName]);
				$oldValue = $this->cleanInStr($oldValues[$fieldName]);
				//Version edits only if value has changed
				if($oldValue != $newValue){
					if($fieldName != 'tidinterpreted'){
						if($fieldName == 'ometid'){
							//Exsiccati title has been changed, thus grab title string
							$exsTitleStr = '';
							$sql = 'SELECT title FROM omexsiccatititles WHERE ometid = '.$occArr['ometid'];
							$rs = $this->conn->query($sql);
							if($r = $rs->fetch_object()){
								$exsTitleStr = $r->title;
							}
							$rs->free();
							//Setup old and new strings
							if($newValue) $newValue = $exsTitleStr.' (ometid: '.$occArr['ometid'].')';
							if($oldValue) $oldValue = $oldValues['title'].' (ometid: '.$oldValues['ometid'].')';
						}
						$sqlEdit = $sqlEditsBase.'"'.$fieldName.'","'.$newValue.'","'.$oldValue.'")';
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
				foreach($occArr as $oField => $ov){
					if(in_array($oField,$this->occFieldArr) && $oField != 'observeruid'){
						$vStr = $this->cleanInStr($ov);
						$sql .= ','.$oField.' = '.($vStr!==''?'"'.$vStr.'"':'NULL');
						//Adjust occurrenceMap which was generated but edit was submitted and will not be re-harvested afterwards
						if(array_key_exists($this->occid,$this->occurrenceMap) && array_key_exists($oField,$this->occurrenceMap[$this->occid])){
							$this->occurrenceMap[$this->occid][$oField] = $vStr;
						}
					}
				}
				//If sciname was changed, update image tid link 
				if(in_array('tidinterpreted',$editArr)){
					//Remap images
					$sqlImgTid = 'UPDATE images SET tid = '.($occArr['tidinterpreted']?$occArr['tidinterpreted']:'NULL').' '.
						'WHERE occid = ('.$occArr['occid'].')';
					$this->conn->query($sqlImgTid);
				}
				//Update occurrence record
				$sql = 'UPDATE omoccurrences SET '.substr($sql,1).' WHERE (occid = '.$occArr['occid'].')';
				//echo $sql;
				if($this->conn->query($sql)){
					if(strtolower($occArr['processingstatus']) != 'unprocessed'){
						//UPDATE uid within omcrowdsourcequeue, only if not yet processed
						$sql = 'UPDATE omcrowdsourcequeue SET uidprocessor = '.$this->symbUid.', reviewstatus = 5 '.
							'WHERE (uidprocessor IS NULL) AND (occid = '.$occArr['occid'].')';
						if(!$this->conn->query($sql)){
							$status = 'ERROR tagging user as the crowdsourcer (#'.$occArr['occid'].'): '.$this->conn->error.'; '.$sql;
						}
					}
					//Deal with exsiccati
					if(in_array('ometid',$editArr) || in_array('exsnumber',$editArr)){
						$ometid = $this->cleanInStr($occArr['ometid']);
						$exsNumber = $this->cleanInStr($occArr['exsnumber']);
						if($ometid && $exsNumber){
							//Values have been submitted, thus try to add ometid and omenid
							//Get exsiccati number id
							$exsNumberId = '';
							$sql = 'SELECT omenid FROM omexsiccatinumbers WHERE ometid = '.$ometid.' AND exsnumber = "'.$exsNumber.'"';
							$rs = $this->conn->query($sql);
							if($r = $rs->fetch_object()){
								$exsNumberId = $r->omenid;
							}
							$rs->free();
							if(!$exsNumberId){
								//There is no exsnumber for that title, thus lets add it and grab new omenid
								$sqlNum = 'INSERT INTO omexsiccatinumbers(ometid,exsnumber) '.
									'VALUES('.$ometid.',"'.$exsNumber.'")';
								if($this->conn->query($sqlNum)){
									$exsNumberId = $this->conn->insert_id;
								}
								else{
									$status = 'ERROR adding exsiccati number: '.$this->conn->error.'; '.$sqlNum;
								}
							}
							//Exsiccati was editted
							if($exsNumberId){
								//Use REPLACE rather than INSERT so that if record with occid already exists, it will be removed before insert
								$sql1 = 'REPLACE INTO omexsiccatiocclink(omenid, occid) '.
									'VALUES('.$exsNumberId.','.$occArr['occid'].')';
								//echo $sql1;
								if(!$this->conn->query($sql1)){
									$status = 'ERROR adding exsiccati: '.$this->conn->error.'; '.$sql1;
								}
							}
						}
						else{
							//No exsiccati title or number values, thus need to remove
							$sql = 'DELETE FROM omexsiccatiocclink WHERE occid = '.$occArr['occid'];
							$this->conn->query($sql);
						}
					}
				}
				else{
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
			($occArr["tidinterpreted"]?$occArr["tidinterpreted"]:"NULL").','.
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
				//Update collection stats
				$this->conn->query('UPDATE omcollectionstats SET recordcnt = recordcnt + 1 WHERE collid = '.$this->collId);
				
				//Create and insert Symbiota GUID (UUID)
				$guid = UuidFactory::getUuidV4();
				if(!$this->conn->query('INSERT INTO guidoccurrences(guid,occid) VALUES("'.$guid.'",'.$this->occid.')')){
					$status .= ' (Warning: Symbiota GUID mapping failed)';
				}
				//deal with Exsiccati, if applicable
				if(isset($occArr['ometid']) && isset($occArr['exsnumber'])){
					//If exsiccati titie is submitted, trim off first character that was used to force Google Chrom to sort correctly
					$ometid = $this->cleanInStr($occArr['ometid']);
					$exsNumber = $this->cleanInStr($occArr['exsnumber']);
					if($ometid && $exsNumber){
						$exsNumberId = '';
						$sql = 'SELECT omenid FROM omexsiccatinumbers WHERE ometid = '.$ometid.' AND exsnumber = "'.$exsNumber.'"';
						$rs = $this->conn->query($sql);
						if($r = $rs->fetch_object()){
							$exsNumberId = $r->omenid;
						}
						$rs->free();
						if(!$exsNumberId){
							//There is no exsnumber for that title, thus lets add it and record exsomenid
							$sqlNum = 'INSERT INTO omexsiccatinumbers(ometid,exsnumber) '.
								'VALUES('.$ometid.',"'.$exsNumber.'")';
							if($this->conn->query($sqlNum)){
								$exsNumberId = $this->conn->insert_id;
							}
							else{
								$status = 'ERROR adding exsiccati number: '.$this->conn->error.'; '.$sqlNum;
							}
						}
						if($exsNumberId){
							//Add exsiccati
							$sql1 = 'INSERT INTO omexsiccatiocclink(omenid, occid) '.
								'VALUES('.$exsNumberId.','.$this->occid.')';
							if(!$this->conn->query($sql1)){
								$status = 'ERROR adding exsiccati: '.$this->conn->error.'; '.$sql1;
							}
						}
					}
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

				//Archive Exsiccati info
				$exsArr = array();
				$sql = 'SELECT t.ometid, t.title, t.abbreviation, t.editor, t.exsrange, t.startdate, t.enddate, t.source, t.notes as titlenotes, '.
					'n.omenid, n.exsnumber, n.notes AS numnotes, l.notes, l.ranking '.
					'FROM omexsiccatiocclink l INNER JOIN omexsiccatinumbers n ON l.omenid = n.omenid '.
					'INNER JOIN omexsiccatititles t ON n.ometid = t.ometid '.
					'WHERE l.occid = '.$delOccid;
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_assoc()){
					foreach($r as $k => $v){
						if($v) $exsArr[$k] = $this->encodeStrTargeted($v,$charset,'utf8');
					}
				}
				$rs->close();
				$archiveArr['exsiccati'] = $exsArr;

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
			//Associated records will be deleted from: omexsiccatiocclink, omoccurdeterminations, fmvouchers
			$sqlDel = 'DELETE FROM omoccurrences WHERE (occid = '.$delOccid.')';
			if($this->conn->query($sqlDel)){
				//Update collection stats
				$this->conn->query('UPDATE omcollectionstats SET recordcnt = recordcnt - 1 WHERE collid = '.$this->collId);
				$status = 'SUCCESS: Occurrence Record Deleted!';
			}
			else{
				$status = 'ERROR trying to delete occurrence record: '.$this->conn->error;
			}
		}
		return $status;
	}
	
	public function transferOccurrence($targetOccid,$transferCollid){
		$status = true;
		if(is_numeric($targetOccid) && is_numeric($transferCollid)){
			$sql = 'UPDATE omoccurrences SET collid = '.$transferCollid.' WHERE occid = '.$targetOccid;
			if(!$this->conn->query($sql)){
				$this->errorStr = 'ERROR trying to delete occurrence record: '.$this->conn->error;
				$status = false;
			}
		}
		return $status;
	}

	private function setLoanData(){
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

	private function setExsiccati(){
		$sql = 'SELECT l.notes, l.ranking, l.omenid, n.exsnumber, t.ometid, t.title, t.abbreviation, t.editor '.
			'FROM omexsiccatiocclink l INNER JOIN omexsiccatinumbers n ON l.omenid = n.omenid '.
			'INNER JOIN omexsiccatititles t ON n.ometid = t.ometid '.
			'WHERE l.occid = '.$this->occid;
		//echo $sql;
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$this->occurrenceMap[$this->occid]['ometid'] = $r->ometid;
			$this->occurrenceMap[$this->occid]['exstitle'] = $r->title.($r->abbreviation?' ['.$r->abbreviation.']':'');
			$this->occurrenceMap[$this->occid]['exsnumber'] = $r->exsnumber;
		}
		$rs->free();
	}

	public function getExsiccatiTitleArr(){
		$retArr = array();
		$sql = 'SELECT DISTINCT ometid, title, abbreviation FROM omexsiccatititles ORDER BY title ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while ($r = $rs->fetch_object()) {
			$retArr[$r->ometid] = $this->cleanOutStr($r->title.($r->abbreviation?' ['.$r->abbreviation.']':''));
		}
		return $retArr;
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
		$fn = $this->cleanInStr($fieldName);
		$ov = $this->cleanInStr($oldValue);
		$nv = $this->cleanInStr($newValue);
		if($fn && ($ov || $nv)){
			$sql = 'UPDATE omoccurrences o2 INNER JOIN (SELECT o.occid FROM omoccurrences o ';
			$this->addTableJoins($sql);
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
		$fn = $this->cleanInStr($fieldName);
		$ov = $this->cleanInStr($oldValue);
		$sql = 'SELECT COUNT(o.occid) AS retcnt FROM omoccurrences o ';
		$this->addTableJoins($sql);
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
			'startdayofyear','enddayofyear','country','stateprovince','county','municipality','locality','decimallatitude','decimallongitude',
			'verbatimcoordinates','coordinateuncertaintyinmeters','footprintwkt','geodeticdatum','minimumelevationinmeters',
			'maximumelevationinmeters','verbatimelevation','verbatimcoordinates','georeferencedby','georeferenceprotocol',
			'georeferencesources','georeferenceverificationstatus','georeferenceremarks','habitat','substrate',
			'lifestage', 'sex', 'individualcount', 'samplingprotocol', 'preparations',
			'associatedtaxa','basisofrecord','language','labelproject');
		$retArr = $this->cleanOutArr(array_intersect_key($fArr,array_flip($locArr)));
		return $retArr;
	}

	//Genetic link functions
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

	//OCR label processing methods
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

	public function insertTextFragment($imgId,$rawFrag,$notes,$source){
		if($imgId && $rawFrag){
			$statusStr = '';
			//$rawFrag = preg_replace('/[^(\x20-\x7F)]*/','', $rawFrag);
			$sql = 'INSERT INTO specprocessorrawlabels(imgid,rawstr,notes,source) '.
				'VALUES ('.$imgId.',"'.$this->cleanRawFragment($rawFrag).'",'.
				($notes?'"'.$this->cleanInStr($notes).'"':'NULL').','.
				($source?'"'.$this->cleanInStr($source).'"':'NULL').')';
			//echo $sql;
			if($this->conn->query($sql)){
				$statusStr = $this->conn->insert_id;
			}
			else{
				$statusStr = 'ERROR: unable to INSERT text fragment; '.$this->conn->error;
				$statusStr .= '; SQL = '.$sql;
			}
			return $statusStr;
		}
	}

	public function saveTextFragment($prlId,$rawFrag,$notes,$source){
		if($prlId && $rawFrag){
			$statusStr = '';
			//$rawFrag = preg_replace('/[^(\x20-\x7F)]*/','', $rawFrag);
			$sql = 'UPDATE specprocessorrawlabels '.
				'SET rawstr = "'.$this->cleanRawFragment($rawFrag).'", '.
				'notes = '.($notes?'"'.$this->cleanInStr($notes).'"':'NULL').', '.
				'source = '.($source?'"'.$this->cleanInStr($source).'"':'NULL').' '.
				'WHERE (prlid = '.$prlId.')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR: unable to UPDATE text fragment; '.$this->conn->error;
				$statusStr .= '; SQL = '.$sql;
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

	//Edit locking functions (session variables)
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

	/*
	 * Return: 0 = false, 2 = full editor, 3 = taxon editor, but not for this collection
	 */
	public function isTaxonomicEditor(){
		global $USER_RIGHTS;
		$isEditor = 0;

		//Get list of userTaxonomyIds that user has been aproved for this collection
		$udIdArr = array();
		if(array_key_exists('CollTaxon',$USER_RIGHTS)){
			foreach($USER_RIGHTS['CollTaxon'] as $vStr){
				$tok = explode(':',$vStr);
				if($tok[0] == $this->collId){
					//Collect only userTaxonomyIds that are relevant to current collid
					$udIdArr[] = $tok[1];
				}
			}
		}
		//Grab taxonomic node id and geographic scopes
		$editTidArr = array();
		$sqlut = 'SELECT idusertaxonomy, tid, geographicscope '.
			'FROM usertaxonomy '.
			'WHERE editorstatus = "OccurrenceEditor" AND uid = '.$GLOBALS['SYMB_UID'];
		//echo $sqlut;
		$rsut = $this->conn->query($sqlut);
		while($rut = $rsut->fetch_object()){
			if(in_array('all',$udIdArr) || in_array($rut->idusertaxonomy,$udIdArr)){
				//Is an approved editor for given collection
				$editTidArr[2][$rut->tid] = $rut->geographicscope;
			}
			else{
				//Is a taxonomic editor, but not explicitly approved for this collection
				$editTidArr[3][$rut->tid] = $rut->geographicscope;
			}
		}
		$rsut->free();
		//Get relevant tids for active occurrence
		if($editTidArr){
			$occTidArr = array();
			$tid = 0;
			$sciname = '';
			$family = '';
			if($this->occurrenceMap && $this->occurrenceMap['tidinterpreted']){
				$tid = $this->occurrenceMap['tidinterpreted'];
				$sciname = $this->occurrenceMap['sciname'];
				$family = $this->occurrenceMap['family'];
			}
			if(!$tid && !$sciname && !$family){
				$sql = 'SELECT tidinterpreted, sciname, family '.
					'FROM omoccurrences '.
					'WHERE occid = '.$this->occid;
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$tid = $r->tidinterpreted;
					$sciname = $r->sciname;
					$family = $r->family;
				}
				$rs->free();
			}
			//Get relevant tids
			if($tid){
				$occTidArr[] = $tid;
				$sql2 = 'SELECT hierarchystr, parenttid '.
					'FROM taxstatus '.
					'WHERE taxauthid = 1 AND (tid = '.$tid.')';
				$rs2 = $this->conn->query($sql2);
				while($r2 = $rs2->fetch_object()){
					$occTidArr[] = $r2->parenttid;
					$occTidArr = array_merge($occTidArr,explode(',',$r2->hierarchystr));
				}
				$rs2->free();
			}
			elseif($sciname || $family){
				//Get all relevant tids within the taxonomy hierarchy
				$sqlWhere = '';
				if($sciname){
					//Try to isolate genus
					$taxon = $sciname;
					$tok = explode(' ',$sciname);
					if(count($tok) > 1){
						if(strlen($tok[0]) > 2) $taxon = $tok[0];
					}
					$sqlWhere .= '(t.sciname = "'.$this->cleanInStr($taxon).'") ';
				}
				elseif($family){
					$sqlWhere .= '(t.sciname = "'.$this->cleanInStr($family).'") ';
				}
				if($sqlWhere){
					$sql2 = 'SELECT DISTINCT ts.hierarchystr, ts.parenttid '.
						'FROM taxstatus ts INNER JOIN taxa t ON ts.tid = t.tid '.
						'WHERE ts.taxauthid = 1 AND ('.$sqlWhere.')';
					//echo $sql2;
					$rs2 = $this->conn->query($sql2);
					while($r2 = $rs2->fetch_object()){
						$occTidArr[] = $r2->parenttid;
						$occTidArr = array_merge($occTidArr,explode(',',$r2->hierarchystr));
					}
					$rs2->free();
				}
			}
			if($occTidArr){
				//Check to see if approved tids have overlap
				if(array_key_exists(2,$editTidArr) && array_intersect(array_keys($editTidArr[2]),$occTidArr)){
					$isEditor = 2;
					//TODO: check to see if specimen is within geographic scope
				}
				//If not, check to see if unapproved tids have overlap (e.g. taxon editor, but w/o explicit rights
				if(!$isEditor){
					if(array_key_exists(3,$editTidArr) && array_intersect(array_keys($editTidArr[3]),$occTidArr)){
						$isEditor = 3;
						//TODO: check to see if specimen is within geographic scope
					}
				}
			}
		}
		return $isEditor;
	}

	//Setters and getters
	public function getErrorStr(){
		return $this->errorStr;	
	}

	public function getCollectionList(){
		$retArr = array();
		if(isset($GLOBALS['USER_RIGHTS']['CollAdmin'])){
			$collArr = $GLOBALS['USER_RIGHTS']['CollAdmin'];
			if($collArr){
				$sql = 'SELECT collid, collectionname FROM omcollections WHERE collid IN('.implode(',',$collArr).')';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$retArr[$r->collid] = $r->collectionname;
				}
				$rs->free();
			}
		}
		asort($retArr);
		return $retArr;
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
		$search = array("�", "�", "`", "�", "�");
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
		$newStr = $this->encodeStr($newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>