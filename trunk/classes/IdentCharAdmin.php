<?php
include_once($serverRoot.'/config/dbconnection.php');

class IdentCharAdmin{

	private $conn;
	private $cid = 0;
	private $lang = 'english';
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
		$sql = 'SELECT c.hid, h.headingname, c.cid, IFNULL(cl.charname, c.charname) AS charname '.
			'FROM kmcharacters c LEFT JOIN kmcharheading h ON c.hid = h.hid '.
			'LEFT JOIN (SELECT cid, charname FROM kmcharacterlang WHERE language = "'.$this->lang.'") cl ON c.cid = cl.cid '.
			'WHERE h.language =  "'.$this->lang.'" '.
			'ORDER BY h.sortsequence, h.headingname, c.sortsequence, cl.charname, c.charname';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$headingArr[$r->hid] = $this->cleanOutStr($r->headingname);
				$retArr[$r->hid][$r->cid] = $this->cleanOutStr($r->charname);
			}
			$rs->free();
		}
		$retArr['head'] = $headingArr;
		return $retArr;
	}

	public function getCharDetails(){
		$retArr = array();
		$sql = 'SELECT cid, charname, chartype, defaultlang, difficultyrank, hid, units, '.
			'description, notes, helpurl, enteredby '.
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
			}
			$rs->free();
		}
		return $retArr;
	}

	public function createCharacter($pArr,$un){
		$statusStr = '';
		$sql = 'INSERT INTO kmcharacters(charname,chartype,difficultyrank,hid,enteredby) '.
			'VALUES("'.$this->cleanInStr($pArr['charname']).'","'.$this->cleanInStr($pArr['chartype']).'",'.
			$this->cleanInStr($pArr['difficultyrank']).','.$this->cleanInStr($pArr['hid']).','.
			'"'.$un.'") ';
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
			$statusStr = 'ERROR: Creation of new character failed: '.$this->conn->error.'<br/>';
		}
		return $statusStr;
	}
	
	public function editCharacter($pArr){
		$statusStr = '';
		$targetArr = array('charname','chartype','units','difficultyrank','hid','description','notes','helpurl');
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
		$sql = 'DELETE FROM kmcharacters WHERE (cid = '.$this->cid.')';
		if($this->conn->query($sql)){
			$status = 1;
		}
		return $status;
	}
	
	public function getCharStateArr(){
		$retArr = array();
		$sql = 'SELECT cid, cs, charstatename, implicit, notes, description, illustrationurl, language, sortsequence, enteredby '.
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
				$retArr[$r->cs]['language'] = $this->cleanOutStr($r->language);
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

	public function createCharState($csName,$un){
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
			$sql = 'INSERT INTO kmcs(cid,cs,charstatename,implicit,enteredby) '.
				'VALUES('.$this->cid.',"'.$csValue.'","'.$this->cleanInStr($csName).'",1,"'.$un.'") ';
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
		$status = 0;
		$sql = 'DELETE FROM kmcs WHERE (cid = '.$this->cid.') AND (cs = '.$cs.')';
		if($this->conn->query($sql)){
			$status = 1;
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
		$statusStr = '';
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
	public function getHeadingArr(){
		$retArr = array();
		$sql = 'SELECT hid, headingname '. 
			'FROM kmcharheading '.
			'WHERE language = "English" '.
			'ORDER BY hid';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->hid] = $this->cleanOutStr($r->headingname);
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