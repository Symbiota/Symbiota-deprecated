<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorDeterminations.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorImages.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDuplicate.php');
include_once($SERVER_ROOT.'/classes/UuidFactory.php');

class OccurrenceEditorManager {

	protected $conn;
	protected $occid;
	private $collId = 0;
	protected $collMap = array();
	protected $occurrenceMap = array();
	private $occFieldArr = array();
	private $sqlWhere;
	private $qryArr = array();
	private $crowdSourceMode = 0;
	private $exsiccatiMode = 0;
	private $symbUid;
	protected $errorArr = array();
	protected $isShareConn = false;

	public function __construct($conn = null){
		if($conn){
			$this->conn = $conn;
			$this->isShareConn = true;
		}
		else{
			$this->conn = MySQLiConnectionFactory::getCon("write");
		}
		$this->occFieldArr = array('dbpk', 'catalognumber', 'othercatalognumbers', 'occurrenceid','family', 'scientificname', 'sciname',
			'tidinterpreted', 'scientificnameauthorship', 'identifiedby', 'dateidentified', 'identificationreferences',
			'identificationremarks', 'taxonremarks', 'identificationqualifier', 'typestatus', 'recordedby', 'recordnumber',
			'associatedcollectors', 'eventdate', 'year', 'month', 'day', 'startdayofyear', 'enddayofyear',
			'verbatimeventdate', 'habitat', 'substrate', 'fieldnumber','occurrenceremarks', 'associatedtaxa', 'verbatimattributes',
			'dynamicproperties', 'reproductivecondition', 'cultivationstatus', 'establishmentmeans',
			'lifestage', 'sex', 'individualcount', 'samplingprotocol', 'preparations',
			'country', 'stateprovince', 'county', 'municipality', 'locality', 'localitysecurity', 'localitysecurityreason',
			'decimallatitude', 'decimallongitude','geodeticdatum', 'coordinateuncertaintyinmeters', 'footprintwkt',
			'locationremarks', 'verbatimcoordinates', 'georeferencedby', 'georeferenceprotocol', 'georeferencesources',
			'georeferenceverificationstatus', 'georeferenceremarks', 'minimumelevationinmeters', 'maximumelevationinmeters',
			'verbatimelevation', 'disposition', 'language', 'duplicatequantity', 'genericcolumn1', 'genericcolumn2', 'labelproject', 
			'observeruid','basisofrecord','ownerinstitutioncode','datelastmodified', 'processingstatus', 'recordenteredby', 'dateentered');
	}

	public function __destruct(){
		if(!$this->isShareConn && $this->conn !== false) $this->conn->close();
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
		elseif(array_key_exists('q_catalognumber',$_REQUEST) || array_key_exists('q_identifier',$_REQUEST)){
			//Need to keep q_identifier in code until LBCC croudsourcing Drupal site is no longer active 
			if(array_key_exists('q_identifier',$_REQUEST) && $_REQUEST['q_identifier']) $this->qryArr['cn'] = trim($_REQUEST['q_identifier']);
			if($_REQUEST['q_catalognumber']) $this->qryArr['cn'] = trim($_REQUEST['q_catalognumber']);
			if(array_key_exists('q_othercatalognumbers',$_REQUEST) && $_REQUEST['q_othercatalognumbers']) $this->qryArr['ocn'] = trim($_REQUEST['q_othercatalognumbers']);
			if(array_key_exists('q_recordedby',$_REQUEST) && $_REQUEST['q_recordedby']) $this->qryArr['rb'] = trim($_REQUEST['q_recordedby']);
			if(array_key_exists('q_recordnumber',$_REQUEST) && $_REQUEST['q_recordnumber']) $this->qryArr['rn'] = trim($_REQUEST['q_recordnumber']);
			if(array_key_exists('q_eventdate',$_REQUEST) && $_REQUEST['q_eventdate']) $this->qryArr['ed'] = trim($_REQUEST['q_eventdate']);
			if(array_key_exists('q_recordenteredby',$_REQUEST) && $_REQUEST['q_recordenteredby']) $this->qryArr['eb'] = trim($_REQUEST['q_recordenteredby']);
			if(array_key_exists('q_observeruid',$_REQUEST) && $_REQUEST['q_observeruid']) $this->qryArr['ouid'] = $_REQUEST['q_observeruid'];
			if(array_key_exists('q_processingstatus',$_REQUEST) && $_REQUEST['q_processingstatus']) $this->qryArr['ps'] = trim($_REQUEST['q_processingstatus']);
			if(array_key_exists('q_datelastmodified',$_REQUEST) && $_REQUEST['q_datelastmodified']) $this->qryArr['dm'] = trim($_REQUEST['q_datelastmodified']);
			if(array_key_exists('q_exsiccatiid',$_REQUEST) && $_REQUEST['q_exsiccatiid']) $this->qryArr['exid'] = trim($_REQUEST['q_exsiccatiid']);
			if(array_key_exists('q_dateentered',$_REQUEST) && $_REQUEST['q_dateentered']) $this->qryArr['de'] = trim($_REQUEST['q_dateentered']);
			if(array_key_exists('q_ocrfrag',$_REQUEST) && $_REQUEST['q_ocrfrag']) $this->qryArr['ocr'] = trim($_REQUEST['q_ocrfrag']);
			if(array_key_exists('q_imgonly',$_REQUEST) && $_REQUEST['q_imgonly']) $this->qryArr['io'] = 1;
			if(array_key_exists('q_withoutimg',$_REQUEST) && $_REQUEST['q_withoutimg']) $this->qryArr['woi'] = 1;
			for($x=1;$x<4;$x++){
				if(array_key_exists('q_customfield'.$x,$_REQUEST) && $_REQUEST['q_customfield'.$x]) $this->qryArr['cf'.$x] = $_REQUEST['q_customfield'.$x];
				if(array_key_exists('q_customtype'.$x,$_REQUEST) && $_REQUEST['q_customtype'.$x]) $this->qryArr['ct'.$x] = $_REQUEST['q_customtype'.$x];
				if(array_key_exists('q_customvalue'.$x,$_REQUEST) && $_REQUEST['q_customvalue'.$x]) $this->qryArr['cv'.$x] = trim($_REQUEST['q_customvalue'.$x]);
			}
			if(array_key_exists('orderby',$_REQUEST)) $this->qryArr['orderby'] = trim($_REQUEST['orderby']);
			if(array_key_exists('orderbydir',$_REQUEST)) $this->qryArr['orderbydir'] = trim($_REQUEST['orderbydir']);
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
		if ($this->qryArr==null) {
			// supress warnings on array_key_exists(key,null) calls below
			$this->qryArr=array();
		}
		$catNumIsNum = false;
		if(array_key_exists('cn',$this->qryArr)){
			$idTerm = $this->qryArr['cn'];
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
							$catNumIsNum = true;
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
								$catNumIsNum = true;
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
			}
		}
		//otherCatalogNumbers
		$otherCatNumIsNum = false;
		if(array_key_exists('ocn',$this->qryArr)){
			if(strtolower($this->qryArr['ocn']) == 'is null'){
				$sqlWhere .= 'AND (o.othercatalognumbers IS NULL) ';
			}
			else{
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
							$otherCatNumIsNum = true;
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
							$otherCatNumIsNum = true;
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
				$sqlWhere .= 'AND ('.substr($ocnWhere,3).') ';
			}
		}
		//recordNumber: collector's number
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
			elseif(substr($this->qryArr['rb'],0,1) == '%'){
				$sqlWhere .= 'AND (MATCH(f.recordedby) AGAINST("'.substr($this->qryArr['rb'],1).'")) ';
			}
			else{
				$sqlWhere .= 'AND (o.recordedby LIKE "'.$this->qryArr['rb'].'%") ';
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
				$edv = str_replace(' to ',' - ',$edv);
				if($p = strpos($edv,' - ')){
					$sqlWhere .= 'AND (o.eventdate BETWEEN "'.trim(substr($edv,0,$p)).'" AND "'.trim(substr($edv,$p+3)).'") ';
				}
				elseif(substr($edv,0,1) == '<' || substr($edv,0,1) == '>'){
					$sqlWhere .= 'AND (o.eventdate '.substr($edv,0,1).' "'.trim(substr($edv,1)).'") ';
				}
				else{
					$sqlWhere .= 'AND (o.eventdate = "'.$edv.'") ';
				}
			}
		}
		if(array_key_exists('eb',$this->qryArr)){
			if(strtolower($this->qryArr['eb']) == 'is null'){
				$sqlWhere .= 'AND (o.recordEnteredBy IS NULL) ';
			}
			else{
				$sqlWhere .= 'AND (o.recordEnteredBy = "'.$this->qryArr['eb'].'") ';
			}
		}
		if(array_key_exists('ouid',$this->qryArr)){
			$sqlWhere .= 'AND (o.observeruid = '.$this->qryArr['ouid'].') ';
		}
		if(array_key_exists('de',$this->qryArr)){
			$de = trim($this->qryArr['de']);
			if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$de)){
				//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
				$de = str_ireplace(array('>',' and ','<'),array('',' - ',''),$de);
			}
			$de = str_replace(' to ',' - ',$de);
			if($p = strpos($de,' - ')){
				$sqlWhere .= 'AND (DATE(o.dateentered) BETWEEN "'.trim(substr($de,0,$p)).'" AND "'.trim(substr($de,$p+3)).'") ';
			}
			elseif(substr($de,0,1) == '<' || substr($de,0,1) == '>'){
				$sqlWhere .= 'AND (o.dateentered '.substr($de,0,1).' "'.trim(substr($de,1)).'") ';
			}
			else{
				$sqlWhere .= 'AND (DATE(o.dateentered) = "'.$de.'") ';
			}
		}
		if(array_key_exists('dm',$this->qryArr)){
			$dm = trim($this->qryArr['dm']);
			if(preg_match('/^>{1}.*\s{1,3}AND\s{1,3}<{1}.*/i',$dm)){
				//convert ">xxxxx and <xxxxx" format to "xxxxx - xxxxx"
				$dm = str_ireplace(array('>',' and ','<'),array('',' - ',''),$dm);
			}
			$dm = str_replace(' to ',' - ',$dm);
			if($p = strpos($dm,' - ')){
				$sqlWhere .= 'AND (DATE(o.datelastmodified) BETWEEN "'.trim(substr($dm,0,$p)).'" AND "'.trim(substr($dm,$p+3)).'") ';
			}
			elseif(substr($dm,0,1) == '<' || substr($dm,0,1) == '>'){
				$sqlWhere .= 'AND (o.datelastmodified '.substr($dm,0,1).' "'.trim(substr($dm,1)).'") ';
			}
			else{
				$sqlWhere .= 'AND (DATE(o.datelastmodified) = "'.$dm.'") ';
			}
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
		//Exsiccati ID
		if(array_key_exists('exid',$this->qryArr)){
			//Used to find records linked to a specific exsiccati
			if(is_numeric($this->qryArr['exid'])){
				$sqlWhere .= 'AND (exn.ometid = '.$this->qryArr['exid'].') ';
			}
			else{
				$sqlWhere .= 'AND (exn.ometid = "null") ';
			}
		}
		//Custom search fields
		for($x=1;$x<4;$x++){
			$cf = (array_key_exists('cf'.$x,$this->qryArr)?$this->qryArr['cf'.$x]:'');
			$ct = (array_key_exists('ct'.$x,$this->qryArr)?$this->qryArr['ct'.$x]:'');
			$cv = (array_key_exists('cv'.$x,$this->qryArr)?$this->qryArr['cv'.$x]:'');
			if($cf){
				if($cf == 'ocrFragment'){
					//Used when OCR frag comes from custom field search within basic query form
					$cf = 'ocr.rawstr';
				}
				elseif($cf == 'username'){
					//Used when Modified By comes from custom field search within basic query form
					$cf = 'ul.username';
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
				elseif($ct=='NOT EQUALS' && $cv){
					if(!is_numeric($cv)) $cv = '"'.$cv.'"';
					$sqlWhere .= 'AND ('.$cf.' <> '.$cv.') ';
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
			$sqlWhere .= 'AND (q.reviewstatus = 0) ';
		}
		if($this->collId) $sqlWhere .= 'AND (o.collid = '.$this->collId.') ';
		if($sqlWhere) $sqlWhere = 'WHERE '.substr($sqlWhere,4);

		if(isset($this->qryArr['orderby'])){
			$sqlOrderBy = '';
			$orderBy = $this->qryArr['orderby'];
			if($orderBy == "catalognumber"){
				if($catNumIsNum){
					$sqlOrderBy = 'catalogNumber+1';
				}
				else{
					$sqlOrderBy = 'catalogNumber';
				}
			}
			if($orderBy == "othercatalognumbers"){
				if($otherCatNumIsNum){
					$sqlOrderBy = 'othercatalognumbers+1';
				}
				else{
					$sqlOrderBy = 'othercatalognumbers';
				}
			}
			elseif($orderBy == "recordnumber"){
				$sqlOrderBy = 'recordnumber+1';
			}
			else{
				$sqlOrderBy = $orderBy;
			}
			if($sqlOrderBy) $sqlWhere .= 'ORDER BY (o.'.$sqlOrderBy.') '.$this->qryArr['orderbydir'].' ';
		}
		
		$sqlWhere .= 'LIMIT '.($occIndex>0?$occIndex.',':'').$recLimit;
		//echo $sqlWhere; exit;
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

	protected function setOccurArr(){
		$retArr = Array();
		$sql = 'SELECT DISTINCT o.occid, o.collid, o.'.implode(',o.',$this->occFieldArr).' FROM omoccurrences o ';
		if($this->occid){
			$sql .= 'WHERE (o.occid = '.$this->occid.')';
		}
		elseif($this->sqlWhere){
			/*
			if(strpos($this->sqlWhere,'recordedby')){
				$sql .= 'use index(Index_collector) ';
			}
			*/
			$this->addTableJoins($sql);
			$sql .= $this->sqlWhere;
		}
		if($sql){
			//echo "<div>".$sql."</div>";
			$occid = 0;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_assoc()){
				if($occid != $row['occid']){
					$occid = $row['occid'];
					$retArr[$occid] = array_change_key_case($row);
				}
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
		elseif(strpos($this->sqlWhere,'ul.username')){
			$sql .= 'LEFT JOIN omoccuredits ome ON o.occid = ome.occid LEFT JOIN userlogin ul ON ome.uid = ul.uid ';
		}
		elseif(strpos($this->sqlWhere,'exn.ometid')){
			$sql .= 'LEFT JOIN omexsiccatiocclink exocc ON o.occid = exocc.occid LEFT JOIN omexsiccatinumbers exn ON exocc.omenid = exn.omenid ';
		}
		elseif(array_key_exists('io',$this->qryArr)){
			$sql .= 'INNER JOIN images i ON o.occid = i.occid ';
		}
		elseif(array_key_exists('woi',$this->qryArr)){
			$sql .= 'LEFT JOIN images i ON o.occid = i.occid ';
		}
		if(strpos($this->sqlWhere,'MATCH(f.recordedby)')){
			$sql .= 'INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ';
		}
		if($this->crowdSourceMode){
			$sql .= 'INNER JOIN omcrowdsourcequeue q ON q.occid = o.occid ';
		}
	}

	public function editOccurrence($occArr,$autoCommit){
		$status = '';
		$quickHostEntered = false;
		if(!$autoCommit && $this->getObserverUid() == $GLOBALS['SYMB_UID']){
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
			if(trim($fName) == 'host' || trim($fName) == 'hostassocid'){
				$quickHostEntered = true;
				unset($editArr[$k]);
			}
			if(!trim($fName)){
				unset($editArr[$k]);
			}
			else if(strcasecmp($fName, 'exstitle') == 0) {
				unset($editArr[$k]);
				$editArr[$k] = 'title';
			}
		}
		if($editArr || $quickHostEntered){
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
					'VALUES ('.$occArr['occid'].',1,'.($autoCommit?'1':'0').','.$GLOBALS['SYMB_UID'].',';
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
			}
			//Edit record only if user is authorized to autoCommit
			if($autoCommit){
				$status = 'SUCCESS: edits submitted and activated ';
				//If processing status was "unprocessed" and recordEnteredBy is null, populate with user login
				$sql = '';
				//Apply autoprocessing status if set
				if(array_key_exists('autoprocessingstatus',$occArr) && $occArr['autoprocessingstatus']){
					$occArr['processingstatus'] = $occArr['autoprocessingstatus'];
				}
				if($editArr){
					if($oldValues['processingstatus'] == 'unprocessed' && !$oldValues['recordenteredby']){
						$occArr['recordenteredby'] = $GLOBALS['USERNAME'];
					}
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
				//If host was entered in quickhost field, update record 
				if($quickHostEntered){
					if($occArr['hostassocid']){
						if($occArr['host']){
							$sqlHost = 'UPDATE omoccurassociations SET verbatimsciname = "'.$occArr['host'].'" '.
								'WHERE associd = '.$occArr['hostassocid'].' ';
						}
						else{
							$sqlHost = 'DELETE FROM omoccurassociations WHERE associd = '.$occArr['hostassocid'].' ';
						}
					}
					else{
						$sqlHost = 'INSERT INTO omoccurassociations(occid,relationship,verbatimsciname) '.
							'VALUES('.$occArr['occid'].',"host","'.$occArr['host'].'")';
					}
					$this->conn->query($sqlHost);
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
							$status = 'ERROR tagging user as the crowdsourcer (#'.$occArr['occid'].'): '.$this->conn->error.' ';
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
									$status = 'ERROR adding exsiccati number: '.$this->conn->error.' ';
								}
							}
							//Exsiccati was editted
							if($exsNumberId){
								//Use REPLACE rather than INSERT so that if record with occid already exists, it will be removed before insert
								$sql1 = 'REPLACE INTO omexsiccatiocclink(omenid, occid) '.
									'VALUES('.$exsNumberId.','.$occArr['occid'].')';
								//echo $sql1;
								if(!$this->conn->query($sql1)){
									$status = 'ERROR adding exsiccati: '.$this->conn->error.' ';
								}
							}
						}
						else{
							//No exsiccati title or number values, thus need to remove
							$sql = 'DELETE FROM omexsiccatiocclink WHERE occid = '.$occArr['occid'];
							$this->conn->query($sql);
						}
					}
					//Deal with duplicate clusters
					if(isset($occArr['linkdupe']) && $occArr['linkdupe']){
						$dupTitle = $occArr['recordedby'].' '.$occArr['recordnumber'].' '.$occArr['eventdate'];
						$status .= $this->linkDuplicates($occArr['linkdupe'],$dupTitle);
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
		$status = "SUCCESS: new occurrence record submitted successfully ";
		if($occArr){
			$fieldArr = array('basisOfRecord' => 's', 'catalogNumber' => 's', 'otherCatalogNumbers' => 's', 'occurrenceid' => 's', 'ownerInstitutionCode' => 's', 
				'family' => 's', 'sciname' => 's', 'tidinterpreted' => 'n', 'scientificNameAuthorship' => 's', 'identifiedBy' => 's', 'dateIdentified' => 's', 
				'identificationReferences' => 's', 'identificationremarks' => 's', 'taxonRemarks' => 's', 'identificationQualifier' => 's', 'typeStatus' => 's',  
				'recordedBy' => 's', 'recordNumber' => 's', 'associatedCollectors' => 's', 'eventDate' => 'd', 'year' => 'n', 'month' => 'n', 'day' => 'n', 'startDayOfYear' => 'n', 'endDayOfYear' => 'n', 
				'verbatimEventDate' => 's', 'habitat' => 's', 'substrate' => 's', 'fieldnumber' => 's', 'occurrenceRemarks' => 's', 'associatedTaxa' => 's', 'verbatimattributes' => 's', 
				'dynamicProperties' => 's', 'reproductiveCondition' => 's', 'cultivationStatus' => 's', 'establishmentMeans' => 's', 
				'lifestage' => 's', 'sex' => 's', 'individualcount' => 's', 'samplingprotocol' => 's', 'preparations' => 's', 
				'country' => 's', 'stateProvince' => 's', 'county' => 's', 'municipality' => 's', 'locality' => 's', 'localitySecurity' => 'n', 'localitysecurityreason' => 's',  
				'decimalLatitude' => 'n', 'decimalLongitude' => 'n', 'geodeticDatum' => 's', 'coordinateUncertaintyInMeters' => 'n', 'verbatimCoordinates' => 's', 'footprintwkt' => 's', 
				'georeferencedBy' => 's', 'georeferenceProtocol' => 's', 'georeferenceSources' => 's', 
				'georeferenceVerificationStatus' => 's', 'georeferenceRemarks' => 's', 'minimumElevationInMeters' => 'n', 'maximumElevationInMeters' => 'n', 
				'verbatimElevation' => 's', 'disposition' => 's', 'language' => 's', 'duplicateQuantity' => 'n', 'labelProject' => 's', 
				'processingstatus' => 's', 'recordEnteredBy' => 's', 'observeruid' => 'n', 'dateentered' => 'd', 'genericcolumn2' => 's');
			$sql = 'INSERT INTO omoccurrences(collid, '.implode(array_keys($fieldArr),',').') '.
				'VALUES ('.$occArr["collid"];
			$fieldArr = array_change_key_case($fieldArr);
			//if(array_key_exists('cultivationstatus',$occArr) && $occArr['cultivationstatus']) $occArr['cultivationstatus'] = $occArr['cultivationstatus'];
			//if(array_key_exists('localitysecurity',$occArr) && $occArr['localitysecurity']) $occArr['localitysecurity'] = $occArr['localitysecurity'];
			if(!isset($occArr['dateentered']) || !$occArr['dateentered']) $occArr['dateentered'] = date('Y-m-d H:i:s');
			if(!isset($occArr['basisofrecord']) || !$occArr['basisofrecord']) $occArr['basisofrecord'] = (strpos($this->collMap['colltype'],'Observations') !== false?'HumanObservation':'PreservedSpecimen');
			
			foreach($fieldArr as $fieldStr => $fieldType){
				$fieldValue = '';
				if(array_key_exists($fieldStr,$occArr)) $fieldValue = $occArr[$fieldStr];
				if($fieldValue){
					if($fieldType == 'n'){
						if(is_numeric($fieldValue)){
							$sql .= ', '.$fieldValue;
						}
						else{
							$sql .= ', NULL';
						} 
					}
					else{
						//Is string or date
						$sql .= ', "'.$this->cleanInStr($fieldValue).'"';
					}
				}
				else{
					$sql .= ', NULL';
				}
			}
			$sql .= ')';
			//echo "<div>".$sql."</div>";
			if($this->conn->query($sql)){
				$this->occid = $this->conn->insert_id;
				//Update collection stats
				$this->conn->query('UPDATE omcollectionstats SET recordcnt = recordcnt + 1 WHERE collid = '.$this->collId);
				
				//Create and insert Symbiota GUID (UUID)
				$guid = UuidFactory::getUuidV4();
				if(!$this->conn->query('INSERT INTO guidoccurrences(guid,occid) VALUES("'.$guid.'",'.$this->occid.')')){
					$status .= '(WARNING: Symbiota GUID mapping failed) ';
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
								$status .= '(WARNING adding exsiccati number: '.$this->conn->error.') ';
							}
						}
						if($exsNumberId){
							//Add exsiccati
							$sql1 = 'INSERT INTO omexsiccatiocclink(omenid, occid) '.
								'VALUES('.$exsNumberId.','.$this->occid.')';
							if(!$this->conn->query($sql1)){
								$status .= '(WARNING adding exsiccati: '.$this->conn->error.') ';
							}
						}
					}
				}
				//Deal with host data
				if(array_key_exists('host',$occArr)){
					$sql1 = 'INSERT INTO omoccurassociations(occid,relationship,verbatimsciname) '.
						'VALUES('.$this->occid.',"host","'.$this->cleanInStr($occArr['host']).'")';
					if(!$this->conn->query($sql1)){
						$status .= '(WARNING adding host: '.$this->conn->error.') ';
					}
				}
				
				if(isset($occArr['confidenceranking']) && $occArr['confidenceranking']){
					$this->editIdentificationRanking($occArr['confidenceranking'],'');
				}
				//Deal with checklist voucher
				if(isset($occArr['clidvoucher']) && isset($occArr['tidinterpreted'])){
					$status .= $this->linkChecklistVoucher($occArr['clidvoucher'],$occArr['tidinterpreted']);
				}
				//Deal with duplicate clustering
				if(isset($occArr['linkdupe']) && $occArr['linkdupe']){
					$dupTitle = $occArr['recordedby'].' '.$occArr['recordnumber'].' '.$occArr['eventdate'];
					$status .= $this->linkDuplicates($occArr['linkdupe'],$dupTitle);
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
		$status = true;
		if(is_numeric($delOccid)){
			//Archive data, first grab occurrence data
			$archiveArr = array();
			$sql = 'SELECT * FROM omoccurrences WHERE occid = '.$delOccid;
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_assoc()){
				foreach($r as $k => $v){
					if($v) $archiveArr[$k] = $this->encodeStrTargeted($v,$charset,'utf8');
				}
			}
			$rs->free();
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
				$rs->free();
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
				$rs->free();
				$archiveArr['imgs'] = $imgArr;
				//Delete images
				if($imgArr){
					$imgidStr = implode(',',array_keys($imgArr));
					//Remove any OCR text blocks linked to the image
					if(!$this->conn->query('DELETE FROM specprocessorrawlabels WHERE (imgid IN('.$imgidStr.'))')){
						$this->errorArr[] = 'ERROR removing OCR blocks linked to images: '.$this->conn->error;
					}
					//Remove image tags
					if(!$this->conn->query('DELETE FROM imagetag WHERE (imgid IN('.$imgidStr.'))')){
						$this->errorArr[] = 'ERROR removing imageTags linked to images: '.$this->conn->error;
					}
					//Remove images
					if(!$this->conn->query('DELETE FROM images WHERE (imgid IN('.$imgidStr.'))')){
						$this->errorArr[] = 'ERROR removing image links: '.$this->conn->error;
					}
				}

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
				$rs->free();
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
			}
			else{
				$this->errorArr[] = 'ERROR trying to delete occurrence record: '.$this->conn->error;
				$status = false;
			}
		}
		return $status;
	}
	
	public function mergeRecords($targetOccid,$sourceOccid){
		global $QUICK_HOST_ENTRY_IS_ACTIVE;
		$status = true;
		if(!$targetOccid || !$sourceOccid){
			$this->errorArr[] = 'ERROR: target or source is null';
			return false;
		}
		if($targetOccid == $sourceOccid){
			$this->errorArr[] = 'ERROR: target and source are equal';
			return false;
		}

		$oArr = array();
		//Merge records
		$sql = 'SELECT * FROM omoccurrences WHERE occid = '.$targetOccid.' OR occid = '.$sourceOccid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_assoc()){
			$tempArr = array_change_key_case($r);
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
				$sqlFrag .= ','.$k.'="'.$this->cleanInStr($v).'"';
			}
		}
		if($sqlFrag){
			//Remap source to target
			$sqlIns = 'UPDATE omoccurrences SET '.substr($sqlFrag,1).' WHERE occid = '.$targetOccid;
			//echo $sqlIns;
			if(!$this->conn->query($sqlIns)){
				$this->errorArr[] = 'ABORT due to error merging records: '.$this->conn->error;
				return false;
			}
		}

		//Remap determinations
		$sql = 'UPDATE omoccurdeterminations SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			$this->errorArr[] .= '; ERROR remapping determinations: '.$this->conn->error;
			$status = false;
		}

		//Delete occurrence edits
		$sql = 'DELETE FROM omoccuredits WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			$this->errorArr[] .= '; ERROR remapping occurrence edits: '.$this->conn->error;
			$status = false;
		}

		//Remap images
		$sql = 'UPDATE images SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			$this->errorArr[] .= '; ERROR remapping images: '.$this->conn->error;
			$status = false;
		}
		
		//Remap associations
		if($QUICK_HOST_ENTRY_IS_ACTIVE){
			$sql = 'UPDATE omoccurassociations SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
			if(!$this->conn->query($sql)){
				$this->errorArr[] .= '; ERROR remapping associations: '.$this->conn->error;
				$status = false;
			}
		}
		
		//Remap comments
		$sql = 'UPDATE omoccurcomments SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			$this->errorArr[] .= '; ERROR remapping comments: '.$this->conn->error;
			$status = false;
		}

		//Remap genetic resources
		$sql = 'UPDATE omoccurgenetic SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			$this->errorArr[] .= '; ERROR remapping genetic resources: '.$this->conn->error;
			$status = false;
		}
		
		//Remap identifiers 
		$sql = 'UPDATE omoccuridentifiers SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			$this->errorArr[] .= '; ERROR remapping occurrence identifiers: '.$this->conn->error;
			$status = false;
		}

		//Remap exsiccati
		$sql = 'UPDATE omexsiccatiocclink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			if(strpos($this->conn->error,'Duplicate') !== false){
				$this->conn->query('DELETE FROM omexsiccatiocclink WHERE occid = '.$sourceOccid);
			}
			else{
				$this->errorArr[] .= '; ERROR remapping exsiccati: '.$this->conn->error;
				$status = false;
			}
		}

		//Remap occurrence dataset links
		$sql = 'UPDATE omoccurdatasetlink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			if(strpos($this->conn->error,'Duplicate') !== false){
				$this->conn->query('DELETE FROM omoccurdatasetlink WHERE occid = '.$sourceOccid);
			}
			else{
				$this->errorArr[] .= '; ERROR remapping dataset links: '.$this->conn->error;
				$status = false;
			}
		}

		//Remap loans
		$sql = 'UPDATE omoccurloanslink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			if(strpos($this->conn->error,'Duplicate') !== false){
				$this->conn->query('DELETE FROM omoccurloanslink WHERE occid = '.$sourceOccid);
			}
			else{
				$this->errorArr[] .= '; ERROR remapping loans: '.$this->conn->error;
				$status = false;
			}
		}

		//Remap checklists voucher links
		$sql = 'UPDATE fmvouchers SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		if(!$this->conn->query($sql)){
			if(strpos($this->conn->error,'Duplicate') !== false){
				$this->conn->query('DELETE FROM fmvouchers WHERE occid = '.$sourceOccid);
			}
			else{
				$this->errorArr[] .= '; ERROR remapping voucher links: '.$this->conn->error;
				$status = false;
			}
		}

		if(!$this->deleteOccurrence($sourceOccid)){
			$status = false;
		}
		return $status;
	}

	public function transferOccurrence($targetOccid,$transferCollid){
		$status = true;
		if(is_numeric($targetOccid) && is_numeric($transferCollid)){
			$sql = 'UPDATE omoccurrences SET collid = '.$transferCollid.' WHERE occid = '.$targetOccid;
			if(!$this->conn->query($sql)){
				$this->errorArr[] = 'ERROR trying to delete occurrence record: '.$this->conn->error;
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
		$sql = 'SELECT ometid, title, abbreviation FROM omexsiccatititles ORDER BY title ';
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

	public function batchUpdateField($fieldName,$oldValue,$newValue,$buMatch){
		$statusStr = '';
		$fn = $this->cleanInStr($fieldName);
		$ov = $this->cleanInStr($oldValue);
		$nv = $this->cleanInStr($newValue);
		if($fn && ($ov || $nv)){
			//Get occids (where statement can't be part of UPDATE query without error being thrown)
			$occidArr = array();
			$sqlOccid = 'SELECT DISTINCT o.occid FROM omoccurrences o ';
			$this->addTableJoins($sqlOccid);
			$sqlOccid .= $this->getBatchUpdateWhere($fn,$ov,$buMatch);
			$rs = $this->conn->query($sqlOccid);
			while($r = $rs->fetch_object()){
				$occidArr[] = $r->occid;
			}
			$rs->free();
			$sqlWhere = 'WHERE o.occid IN('.implode(',',$occidArr).')';
			
			//Strip ORDER BY and/or LIMIT fragments
			$nvSqlFrag = '';
			if(!$buMatch || $ov===''){
				$nvSqlFrag = ($nv===''?'NULL':'"'.$nv.'"');
			}
			else{
				//Selected "Match any part of field"
				$nvSqlFrag = 'REPLACE(o.'.$fn.',"'.$ov.'","'.$nv.'")';
			}
			
			//Add edits to the omoccuredit table
			$sql2 = 'INSERT INTO omoccuredits(occid,fieldName,fieldValueOld,fieldValueNew,appliedStatus,uid) '.
				'SELECT o.occid, "'.$fn.'" AS fieldName, IFNULL(o.'.$fn.',"") AS oldValue, '.
				'IFNULL('.$nvSqlFrag.',"") AS newValue, 1 AS appliedStatus, '.$GLOBALS['SYMB_UID'].' AS uid '.
				'FROM omoccurrences o ';
			//$this->addTableJoins($sql2);
			$sql2 .= $sqlWhere;
			if(!$this->conn->query($sql2)){
				$statusStr = 'ERROR adding update to omoccuredits: '.$this->conn->error;
			}

			//Run update and apply edits
			$sql = 'UPDATE omoccurrences o ';
			//$this->addTableJoins($sql);
			$sql .= ' SET o.'.$fn.' = '.$nvSqlFrag.' '.$sqlWhere;
			//echo $sql; exit;
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR applying batch update: '.$this->conn->error;
			}
		}
		return $statusStr;
	}

	public function getBatchUpdateCount($fieldName,$oldValue,$buMatch){
		$retCnt = 0;
		
		$fn = $this->cleanInStr($fieldName);
		$ov = $this->cleanInStr($oldValue);

		$sql = 'SELECT COUNT(o.occid) AS retcnt '.
			'FROM omoccurrences o ';
		$this->addTableJoins($sql);
		$sql .= $this->getBatchUpdateWhere($fn,$ov,$buMatch);

		$result = $this->conn->query($sql);
		while ($row = $result->fetch_object()) {
			$retCnt = $row->retcnt;
		}
		$result->free();
		return $retCnt;
	}

	private function getBatchUpdateWhere($fn,$ov,$buMatch){
		$sql = '';
		//Add where and strip ORDER BY and/or LIMIT fragments
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
			$sql .= ' AND (o.'.$fn.' '.($ov===''?'IS NULL':'= "'.$ov.'"').') ';
		}
		else{
			//Selected "Match any part of field"
			$sql .= ' AND (o.'.$fn.' LIKE "%'.$ov.'%") ';
		}
		return $sql;
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
	
	//Verification functions
	public function getIdentificationRanking(){
		//Get Identification ranking
		$retArr = array();
		$sql = 'SELECT v.ovsid, v.ranking, v.notes, l.username '.
				'FROM omoccurverification v LEFT JOIN userlogin l ON v.uid = l.uid '.
				'WHERE v.category = "identification" AND v.occid = '.$this->occid;
		//echo "<div>".$sql."</div>";
		$rs = $this->conn->query($sql);
		//There can only be one identification ranking per specimen
		if($r = $rs->fetch_object()){
			$retArr['ovsid'] = $r->ovsid;
			$retArr['ranking'] = $r->ranking;
			$retArr['notes'] = $r->notes;
			$retArr['username'] = $r->username;
		}
		$rs->free();
		return $retArr;
	}

	public function editIdentificationRanking($ranking,$notes=''){
		$statusStr = '';
		if(is_numeric($ranking)){
			//Will be replaced if an identification ranking already exists for occurrence record
			$sql = 'REPLACE INTO omoccurverification(occid,category,ranking,notes,uid) '.
					'VALUES('.$this->occid.',"identification",'.$ranking.','.($notes?'"'.$this->cleanInStr($notes).'"':'NULL').','.$GLOBALS['SYMB_UID'].')';
			if(!$this->conn->query($sql)){
				$statusStr .= 'WARNING editing/add confidence ranking failed ('.$this->conn->error.') ';
				//echo $sql;
			}
		}
		return $statusStr;
	}

	//Checklist voucher functions
	public function getVoucherChecklists(){
		$retArr = array();
		$sql = 'SELECT c.clid, c.name '.
			'FROM fmchecklists c INNER JOIN fmvouchers v ON c.clid = v.clid '.
			'WHERE v.occid = '.$this->occid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->clid] = $r->name;
		}
		$rs->free();
		asort($retArr);
		return $retArr;
	}

	public function linkChecklistVoucher($clid,$tid){
		$status = '';
		if(is_numeric($clid) && is_numeric($tid)){
			//Check to see it the name is in the list, if not, add it
			$clTid = 0;
			$sqlCl = 'SELECT cl.tid '.
					'FROM fmchklsttaxalink cl INNER JOIN taxstatus ts1 ON cl.tid = ts1.tidaccepted '.
					'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
					'WHERE ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND ts2.tid = '.$tid.' AND cl.clid = '.$clid;
			$rsCl = $this->conn->query($sqlCl);
			//echo $sqlCl;
			if($rowCl = $rsCl->fetch_object()){
				$clTid = $rowCl->tid;
			}
			$rsCl->free();
			if(!$clTid){
				$sqlCl1 = 'INSERT INTO fmchklsttaxalink(clid, tid) VALUES('.$clid.','.$tid.') ';
				if($this->conn->query($sqlCl1)){
					$clTid = $tid;
				}
				else{
					$status .= '(WARNING adding scientific name to checklist: '.$this->conn->error.'); ';
				}
			}
			//Add voucher
			if($clTid){
				$sqlCl2 = 'INSERT INTO fmvouchers(occid,clid,tid) '.
						'values('.$this->occid.','.$clid.','.$clTid.')';
				//echo $sqlCl2;
				if(!$this->conn->query($sqlCl2)){
					$status .= '(WARNING adding voucher link: '.$this->conn->error.'); ';
				}
			}
		}
		return $status;
	}

	public function deleteChecklistVoucher($clid){
		$status = '';
		if(is_numeric($clid)){
			$sql = 'DELETE FROM fmvouchers WHERE clid = '.$clid.' AND occid = '.$this->occid;
			if(!$this->conn->query($sql)){
				$status = 'ERROR deleting voucher from checklist: '.$this->conn->error;
			}
		}
		return $status;
	}

	public function getUserChecklists(){
		// Return list of checklists to which user has editing writes 
		$retArr = Array();
		if(ISSET($GLOBALS['USER_RIGHTS']['ClAdmin'])){
			$sql = 'SELECT clid, name, access '.
					'FROM fmchecklists '.
					'WHERE (clid IN('.implode(',',$GLOBALS['USER_RIGHTS']['ClAdmin']).')) ';
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->clid] = $r->name.($r->access == 'private'?' (private)':'');
			}
			$rs->free();
			asort($retArr);
		}
		return $retArr;
	}

	//Duplicate functions
	private function linkDuplicates($occidStr,$dupTitle){
		$status = '';
		$dupManager = new OccurrenceDuplicate();
		$dupManager->linkDuplicates($this->occid,$occidStr,$dupTitle);
		return $status;
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
			return 'ERROR editing genetic resource #'.$genArr['genid'].': '.$this->conn->error;
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
			while($r = $result->fetch_object()){
				$k = substr($r->initialtimestamp,0,16);
				if(!isset($retArr[$k]['editor'])){
					$retArr[$k]['editor'] = $r->editor;
					$retArr[$k]['ts'] = $r->initialtimestamp;
					$retArr[$k]['reviewstatus'] = $r->reviewstatus;
					$retArr[$k]['appliedstatus'] = $r->appliedstatus;
				}
				$retArr[$k]['edits'][$r->ocedid]['fieldname'] = $r->fieldname;
				$retArr[$k]['edits'][$r->ocedid]['old'] = $r->fieldvalueold;
				$retArr[$k]['edits'][$r->ocedid]['new'] = $r->fieldvaluenew;
			}
			$result->free();
		}
		else{
			trigger_error('Unable to get edits; '.$this->conn->error,E_USER_WARNING);
		}
		return $retArr;
	}

	public function getExternalEditArr(){
		$retArr = Array();
		$sql = 'SELECT r.orid, r.oldvalues, r.newvalues, r.externalsource, r.externaleditor, r.reviewstatus, r.appliedstatus, '.
				'CONCAT_WS(", ",u.lastname,u.firstname) AS username, r.externaltimestamp, r.initialtimestamp '.
				'FROM omoccurrevisions r LEFT JOIN users u ON r.uid = u.uid '.
				'WHERE (r.occid = '.$this->occid.') ORDER BY r.initialtimestamp DESC ';
		//echo '<div>'.$sql.'</div>';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$editor = $r->externaleditor;
			if($r->username) $editor .= ' ('.$r->username.')';
			$ts = $r->initialtimestamp;
			$retArr[$ts]['editor'] = $editor;
			$retArr[$ts]['source'] = $r->externalsource;
			$retArr[$ts]['reviewstatus'] = $r->reviewstatus;
			$retArr[$ts]['appliedstatus'] = $r->appliedstatus;
	
			$oldValues = json_decode($r->oldvalues,true);
			$newValues = json_decode($r->newvalues,true);
			foreach($oldValues as $fieldName => $value){
				$retArr[$ts]['edits'][$r->orid]['fieldname'] = $fieldName;
				$retArr[$ts]['edits'][$r->orid]['old'] = $value;
				$retArr[$ts]['edits'][$r->orid]['new'] = (isset($newValues[$fieldName])?$newValues[$fieldName]:'ERROR');
			}
		}
		$rs->free();
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
				$rs2 = $this->conn->query('SELECT parenttid FROM taxaenumtree WHERE (taxauthid = 1) AND (tid = '.$tid.')');
				while($r2 = $rs2->fetch_object()){
					$occTidArr[] = $r2->parenttid;
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
					$sql2 = 'SELECT e.parenttid '.
						'FROM taxaenumtree e INNER JOIN taxa t ON e.tid = t.tid '.
						'WHERE e.taxauthid = 1 AND ('.$sqlWhere.')';
					//echo $sql2;
					$rs2 = $this->conn->query($sql2);
					while($r2 = $rs2->fetch_object()){
						$occTidArr[] = $r2->parenttid;
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
		if($this->errorArr) return implode('; ',$this->errorArr);
		else return '';	
	}

	public function getCollectionList(){
		$retArr = array();
		$collArr = array('0');
		if(isset($GLOBALS['USER_RIGHTS']['CollAdmin'])) $collArr = $GLOBALS['USER_RIGHTS']['CollAdmin'];
		$sql = 'SELECT collid, collectionname FROM omcollections '.
			'WHERE (collid IN('.implode(',',$collArr).')) ';
		$collEditorArr = $GLOBALS['USER_RIGHTS']['CollEditor'];
		if($collEditorArr){
			$sql .= 'OR (collid IN('.implode(',',$collEditorArr).') AND colltype = "General Observations")';
		}
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid] = $r->collectionname;
		}
		$rs->free();
		asort($retArr);
		return $retArr;
	}

	public function getQuickHost($occId){
		$retArr = Array();
		$sql = 'SELECT associd, verbatimsciname '.
			'FROM omoccurassociations '.
			'WHERE relationship = "host" AND occid = '.$occId.' ';
		//echo $sql; exit;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr['associd'] = $r->associd;
			$retArr['verbatimsciname'] = $r->verbatimsciname;
		}
		$rs->free();
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
		//Get rid of Windows curly (smart) quotes
		$search = array(chr(145),chr(146),chr(147),chr(148),chr(149),chr(150),chr(151));
		$replace = array("'","'",'"','"','*','-','-');
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