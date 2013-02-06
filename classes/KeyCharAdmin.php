<?php
include_once($serverRoot.'/config/dbconnection.php');

class KeyAdmin{

	private $conn;
	private $collId = 0;
	private $cid = 0;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	function __destruct(){
 		if($this->conn) $this->conn->close();
	}
	
	public function getCharHeadList(){
		$hidArr = array();
		$sql = 'SELECT DISTINCT c.hid, h.headingname '.
			'FROM kmcharacters AS c INNER JOIN kmcharheading AS h ON c.hid = h.hid '.
			'WHERE h.language = "English" '.
			'ORDER BY h.headingname';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$hidArr[$r->hid]['headingname'] = $this->cleanOutStr($r->headingname);
			}
		}
		return $hidArr;
	}
	
	public function getCharacters($hid){
		$retArr = array();
		$sql = 'SELECT cid, charname '.
			'FROM kmcharacters '.
			'WHERE hid = '.$hid.' '.
			'ORDER BY charname ASC';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->cid]['charname'] = $this->cleanOutStr($r->charname);
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function createCharacter($pArr,$un){
		$statusStr = '';
		$sql = 'INSERT INTO kmcharacters(charname,chartype,difficultyrank,hid,enteredby) '.
			'VALUES("'.$this->cleanInStr($pArr['charname']).'","'.$this->cleanInStr($pArr['chartype']).'",
			"'.$this->cleanInStr($pArr['difficultyrank']).'","'.$this->cleanInStr($pArr['hid']).'",
			"'.$un.'") ';
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
	
	public function createState($csName,$un){
		$csValue = 1;
		if($this->cid){
			//Get highest character set ID value (CS) and increase by 1
			$sql1 = 'SELECT cs FROM kmcs WHERE cid = '.$this->cid.' ORDER BY (cs+1) DESC ';
			if($rs1 = $this->conn->query($sql1)){
				if($r1 = $rs1->fetch_object()){
					$csTemp = $r1->cs + 1;
					if(is_numeric($csTemp)) $csValue = $csTemp;
				}
			}
			//Load new character set
			$sql = 'INSERT INTO kmcs(cid,cs,charstatename,implicit,sortsequence,enteredby) '.
				'VALUES('.$this->cid.',"'.$csValue.'","'.$this->cleanInStr($csName).'",1,'.$csValue.',"'.$un.'") ';
			//echo $sql;
			if(!$this->conn->query($sql)){
				trigger_error('ERROR: Creation of new character failed: '.$this->conn->error);
			}
		}
		return $csValue;
	}
	
	public function editCharacter($pArr){
		$statusStr = '';
		$sql = '';
		foreach($pArr as $k => $v){
			if($k != 'formsubmit' && $k != 'cid'){
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
	
	public function editCharState($pArr){
		$statusStr = '';
		$cs = $pArr['cs'];
		$sql = '';
		foreach($pArr as $k => $v){
			if($k != 'formsubmit' && $k != 'cid' && $k != 'cs'){
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
			$rs->close();
		}
		return $retArr;
	}
	
	public function getCharStateArr(){
		$retArr = array();
		$sql = 'SELECT cid, cs, charstatename, implicit, notes, description, illustrationurl, language, enteredby '.
			'FROM kmcs '.
			'WHERE cid = '.$this->cid;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->cs]['charstatename'] = $this->cleanOutStr($r->charstatename);
				$retArr[$r->cs]['implicit'] = $r->implicit;
				$retArr[$r->cs]['notes'] = $this->cleanOutStr($r->notes);
				$retArr[$r->cs]['description'] = $this->cleanOutStr($r->description);
				$retArr[$r->cs]['illustrationurl'] = $r->illustrationurl;
				$retArr[$r->cs]['language'] = $this->cleanOutStr($r->language);
				$retArr[$r->cs]['enteredby'] = $r->enteredby;
			}
			$rs->close();
		}
		else{
			trigger_error('unable to return character state array; '.$this->conn->error);
		}
		return $retArr;
		
	}

	public function deleteChar(){
		$status = 0;
		$sql = 'DELETE FROM kmcharacters WHERE (cid = '.$this->cid.')';
		if($this->conn->query($sql)){
			$status = 1;
		}
		return $status;
	}
	
	public function deleteCharState($cs){
		$status = 0;
		$sql = 'DELETE FROM kmcs WHERE (cid = '.$this->cid.') AND (cs = '.$cs.')';
		if($this->conn->query($sql)){
			$status = 1;
		}
		return $status;
	}

	public function getTaxonLinks(){
		$retArr = array();
		$sql = 'SELECT l.tid, l.relation, t.sciname '.
			'FROM kmchartaxalink l INNER JOIN taxa t ON l.tid = t.tid '.
			'WHERE l.cid = '.$this->cid;
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->tid]['relation'] = $r->relation;
				$retArr[$r->tid]['sciname'] = $r->sciname;
			}
		}
		else{
			trigger_error('unable to get Taxon Links; '.$this->conn->error);
		}
		return $retArr;
	}

	//Get and set functions 
	public function getHeadingArr(){
		$retArr = array();
		$sql = 'SELECT hid, headingname '. 
			'FROM kmcharheading '.
			'WHERE language = "English" '.
			'ORDER BY hid';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->hid] = $this->cleanOutStr($r->headingname);
			}
		}
		return $retArr;
	}
	
	public function setCollId($c){
		$this->collId = $c;
	}
	
	public function getCid(){
		return $this->cid;
	}
	
	public function setCid($cid){
		if(is_numeric($cid)) $this->cid = $cid;
	}
	
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