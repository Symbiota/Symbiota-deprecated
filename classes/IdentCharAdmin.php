<?php
include_once($serverRoot.'/config/dbconnection.php');

class IdentCharAdmin{

	private $conn;
	private $cid = 0;
	private $lang = 'english';
	private $landId;
	//private $langId;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
 		if($this->conn) $this->conn->close();
	}

	public function getCharacterArr(){
		$retArr = array();
		$headingArr = array();
		if(!$this->langId) $this->setLangId();
		$sql = 'SELECT c.hid, h.headingname, c.cid, IFNULL(cl.charname, c.charname) AS charname '.
			'FROM kmcharacters c LEFT JOIN kmcharheading h ON c.hid = h.hid '.
			'LEFT JOIN (SELECT cid, charname FROM kmcharacterlang WHERE langid = "'.$this->langId.'") cl ON c.cid = cl.cid '.
			'WHERE (h.langid =  "'.$this->langId.'" OR h.langid IS NULL) '.
			'ORDER BY h.sortsequence, h.headingname, c.sortsequence, cl.charname, c.charname';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$hid = $r->hid;
				$hName = $this->cleanOutStr($r->headingname);
				if(!$hid) $hid = 0;
				if(!$hName) $hName = 'Undefined Heading';
				$headingArr[$hid] = $hName;
				$retArr[$hid][$r->cid] = $this->cleanOutStr($r->charname);
			}
			$rs->free();
		}
		$retArr['head'] = $headingArr;
		return $retArr;
	}

	public function getCharDetails(){
		$retArr = array();
		if($this->cid){
			$sql = 'SELECT cid, charname, chartype, defaultlang, difficultyrank, hid, units, '.
				'description, notes, helpurl, enteredby, sortsequence '.
				'FROM kmcharacters '.
				'WHERE cid = '.$this->cid;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr['charname'] = $this->cleanOutStr($r->charname);
					$retArr['chartype'] = $r->chartype;
					$retArr['defaultlang'] = $this->cleanOutStr($r->defaultlang);
					$retArr['difficultyrank'] = $r->difficultyrank;
					$retArr['hid'] = $r->hid;
					$retArr['units'] = $this->cleanOutStr($r->units);
					$retArr['description'] = $this->cleanOutStr($r->description);
					$retArr['notes'] = $this->cleanOutStr($r->notes);
					$retArr['helpurl'] = $r->helpurl;
					$retArr['enteredby'] = $r->enteredby;
					$retArr['sortsequence'] = $r->sortsequence;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function createCharacter($pArr,$un){
		$statusStr = 'SUCCESS: character added to database';
		$dRank = $this->cleanInStr($pArr['difficultyrank']);
		if(!$dRank) $dRank = 1;
		$hid = $this->cleanInStr($pArr['hid']);
		if(!$hid) $hid = 'NULL';
		$sql = 'INSERT INTO kmcharacters(charname,chartype,difficultyrank,hid,enteredby,sortsequence) '.
			'VALUES("'.$this->cleanInStr($pArr['charname']).'","'.$this->cleanInStr($pArr['chartype']).'",'.
			$dRank.','.$hid.',"'.$un.'",'.(is_numeric($pArr['sortsequence'])?$pArr['sortsequence']:1000).') ';
		//echo $sql;
		if($this->conn->query($sql)){
			$this->cid = $this->conn->insert_id;
			if(($pArr['chartype'] == 'IN') || ($pArr['chartype'] == 'RN')){
				//If new character is a numeric type, automatically load character sets with set values 
				$sql2 = 'INSERT INTO kmcs(cid,cs,charstatename) '.
					'VALUES('.$this->cid.',"+High","Upper value of unspecified range (could be µ+s.d., but not known)"),'.
					'('.$this->cid.',"-Low","Lower value of unspecified range (could be µ-s.d., but not known)"),'.
					'('.$this->cid.',"Max","Maximum value"),'.
					'('.$this->cid.',"Mean","Mean (= average)"),'.
					'('.$this->cid.',"Min","Minimum value")';
				if(!$this->conn->query($sql2)){
					trigger_error('unable to load numeric character set values; '.$this->conn->error);
					$statusStr = 'unable to load numeric character set values; '.$this->conn->error;
				}
			}
		}
		else{
			trigger_error('Creation of new character failed; '.$this->conn->error);
			$statusStr = 'ERROR: Creation of new character failed: '.$this->conn->error.'<br/>SQL: '.$sql;
		}
		return $statusStr;
	}

	public function editCharacter($pArr){
		$statusStr = '';
		$targetArr = array('charname','chartype','units','difficultyrank','hid','description','notes','helpurl','sortsequence');
		$sql = '';
		foreach($pArr as $k => $v){
			if(in_array($k,$targetArr)){
				$sql .= ','.$k.'='.($v?'"'.$this->cleanInStr($v).'"':'NULL');
			}
		}
		$sql = 'UPDATE kmcharacters SET '.substr($sql,1).' WHERE (cid = '.$this->cid.')';
		if($this->conn->query($sql)){
			$statusStr = 'SUCCESS: information saved';
		}
		else{
			$statusStr = 'ERROR: Editing of character failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}
	
	public function deleteChar(){
		$status = 0;

		//Delete character taxa links
		$sql = 'DELETE FROM kmchartaxalink WHERE (cid = '.$this->cid.')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$status = 'ERROR deleting character taxa links: '.$this->conn->error.', '.$sql;
		}

		//Delete character dependance links
		$sql = 'DELETE FROM kmchardependance WHERE (cid = '.$this->cid.') OR (ciddependance = '.$this->cid.')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$status = 'ERROR deleting character dependance links: '.$this->conn->error.', '.$sql;
		}

		//Delete language links
		$sql = 'DELETE FROM kmcharacterlang WHERE (cid = '.$this->cid.')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$status = 'ERROR deleting character languages: '.$this->conn->error.', '.$sql;
		}

		//Delete characters
		$sql = 'DELETE FROM kmcharacters WHERE (cid = '.$this->cid.')';
		if(!$this->conn->query($sql)){
			$status = 'ERROR deleting descriptions linked to character: '.$this->conn->error.', '.$sql;
		}

		return $status;
	}

	public function getCharStateArr(){
		$retArr = array();
		$sql = 'SELECT cid, cs, charstatename, implicit, notes, description, illustrationurl, sortsequence, enteredby '.
			'FROM kmcs '.
			'WHERE cid = '.$this->cid.' '.
			'ORDER BY sortsequence';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->cs]['charstatename'] = $this->cleanOutStr($r->charstatename);
				$retArr[$r->cs]['implicit'] = $r->implicit;
				$retArr[$r->cs]['notes'] = $this->cleanOutStr($r->notes);
				$retArr[$r->cs]['description'] = $this->cleanOutStr($r->description);
				$retArr[$r->cs]['illustrationurl'] = $r->illustrationurl;
				$retArr[$r->cs]['sortsequence'] = $this->cleanOutStr($r->sortsequence);
				$retArr[$r->cs]['enteredby'] = $r->enteredby;
			}
			$rs->free();
		}
		else{
			trigger_error('unable to return character state array; '.$this->conn->error);
		}
		return $retArr;
		
	}

	public function createCharState($csName,$illUrl,$desc,$n,$sort,$un){
		$csValue = 1;
		if($this->cid){
			//Get highest character set ID value (CS) and increase by 1
			$sql = 'SELECT cs FROM kmcs WHERE cid = '.$this->cid.' ORDER BY (cs+1) DESC ';
			if($rs = $this->conn->query($sql)){
				if($r = $rs->fetch_object()){
					if(is_numeric($r->cs)){
						$csValue = $r->cs + 1;
					}
				}
				$rs->free();
			}
			//Load new character set
			$illustrationUrl = $this->cleanInStr($illUrl);
			$description = $this->cleanInStr($desc);
			$notes = $this->cleanInStr($n);
			$sortSequence = $this->cleanInStr($sort);
			$sql = 'INSERT INTO kmcs(cid,cs,charstatename,implicit,illustrationurl,description,notes,sortsequence,enteredby) '.
				'VALUES('.$this->cid.',"'.$csValue.'","'.$this->cleanInStr($csName).'",1,'.
				($illustrationUrl?'"'.$illustrationUrl.'"':'NULL').','.
				($description?'"'.$description.'"':'NULL').','.
				($notes?'"'.$notes.'"':'NULL').','.
				($sortSequence?$sortSequence:100).',"'.$un.'") ';
			//echo $sql;
			if(!$this->conn->query($sql)){
				trigger_error('ERROR: Creation of new character failed: '.$this->conn->error);
			}
		}
		return $csValue;
	}
	
	public function editCharState($pArr){
		$statusStr = '';
		$cs = $pArr['cs'];
		$targetArr = array('charstatename','illustrationurl','description','notes','sortsequence');
		$sql = '';
		foreach($pArr as $k => $v){
			if(in_array($k,$targetArr)){
				$sql .= ','.$k.'='.($v?'"'.$this->cleanInStr($v).'"':'NULL');
			}
		}
		$sql = 'UPDATE kmcs SET '.substr($sql,1).' WHERE (cid = '.$this->cid.') AND (cs = '.$cs.')';
		//echo $sql;
		if($this->conn->query($sql)){
			$statusStr = 'SUCCESS: information saved';
		}
		else{
			$statusStr = 'ERROR: Editing of character state failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}
	
	public function deleteCharState($cs){
		$status = '';
		if(is_numeric($cs)){
			//Delete images links
			$sql = 'DELETE FROM kmcsimages WHERE (cid = '.$this->cid.') AND (cs = '.$cs.')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$status = 'ERROR deleting character state images: '.$this->conn->error.', '.$sql;
			}
	
			//Delete language links
			$sql = 'DELETE FROM kmcslang WHERE (cid = '.$this->cid.') AND (cs = '.$cs.')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$status = 'ERROR deleting character state languages: '.$this->conn->error.', '.$sql;
			}
	
			//Delete character dependance links
			$sql = 'DELETE FROM kmchardependance WHERE (ciddependance = '.$this->cid.') AND (csdependance = '.$cs.')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$status = 'ERROR deleting character dependance linked to character state: '.$this->conn->error.', '.$sql;
			}
	
			//Delete description links
			$sql = 'DELETE FROM kmdescr WHERE (cid = '.$this->cid.') AND (cs = '.$cs.')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$status = 'ERROR deleting descriptions linked to character state: '.$this->conn->error.', '.$sql;
			}
	
			//Delete character states
			$sql = 'DELETE FROM kmcs WHERE (cid = '.$this->cid.') AND (cs = '.$cs.')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$status = 'ERROR deleting character state: '.$this->conn->error.', '.$sql;
			}
		}
		return $status;
	}

	public function getTaxonRelevance(){
		$retArr = array();
		$sql = 'SELECT l.tid, l.relation, l.notes, t.sciname '.
			'FROM kmchartaxalink l INNER JOIN taxa t ON l.tid = t.tid '.
			'WHERE l.cid = '.$this->cid;
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->relation][$r->tid]['sciname'] = $r->sciname;
				$retArr[$r->relation][$r->tid]['notes'] = $r->notes;
			}
			$rs->free();
		}
		else{
			trigger_error('unable to get Taxon Links; '.$this->conn->error);
		}
		return $retArr;
	}

	public function saveTaxonRelevance($tid,$rel,$notes){
		$statusStr = '';
		if($this->cid && is_numeric($tid)){
			$sql = 'INSERT INTO kmchartaxalink(cid,tid,relation,notes) '.
				'VALUES('.$this->cid.','.$tid.',"'.$this->cleanInStr($rel).'","'.$this->cleanInStr($notes).'")';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR: unable to add Taxon Relevance; '.$this->conn->error;
				trigger_error('ERROR: unable to add Taxon Relevance; '.$this->conn->error);
			}
		}
		return $statusStr;
	}
	
	public function deleteTaxonRelevance($tid){
		$statusStr = 'SUCCESS: taxon linkage removed';
		if($this->cid && is_numeric($tid)){
			$sql = 'DELETE FROM kmchartaxalink '.
				'WHERE cid = '.$this->cid.' AND tid = '.$tid;
			//echo $sql;
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR: unable to delete Taxon Relevance; '.$this->conn->error;
				trigger_error('ERROR: unable to delete Taxon Relevance; '.$this->conn->error);
			}
		}
		return $statusStr;
	}

	//Get and set functions 
	public function getHeadingArr($hid=0){
		$retArr = array();
		if(!$this->langId) $this->setLangId();
		$sqlWhere = '';
		if($hid) $sqlWhere .= 'AND (h.hid = '.$hid.') ';
		if($this->langId) $sqlWhere = 'AND (langid = '.$this->langId.') ';
		$sql = 'SELECT hid, headingname '.
			'FROM kmcharheading h ';
		if($sqlWhere) $sql .= ' WHERE '.substr($sqlWhere,3);
		$sql .= 'ORDER BY sortsequence,headingname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->hid]['name'] = $this->cleanOutStr($r->headingname);
		}
		$rs->free();
		return $retArr;
	}
	
	public function getTaxonArr(){
		$retArr = array();
		$sql = 'SELECT tid, sciname '. 
			'FROM taxa '.
			'WHERE rankid < 220 '.
			'ORDER BY sciname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->tid] = $r->sciname;
		}
		$rs->free();
		return $retArr;
	}
	
	public function getCid(){
		return $this->cid;
	}
	
	public function setCid($cid){
		if(is_numeric($cid)) $this->cid = $cid;
	}
	
	public function setLanguage($l){
		$this->lang = $l;
	}

	public function setLangId($lang=''){
		if(!$lang){
			if($GLOBALS['defaultLang']){
				$lang = $GLOBALS['defaultLang'];
			}
			else{
				$lang = 'English';
			}
		}
		if(is_numeric($lang)){
			$this->langId = $lang;
		}
		else{
			$sql = 'SELECT langid FROM adminlanguages '.
				'WHERE langname = "'.$lang.'" OR iso639_1 = "'.$lang.'" OR iso639_2 = "'.$lang.'" ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$this->langId = $r->langid;
			}
			$rs->free;
		}
	}

	//General functions
	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>