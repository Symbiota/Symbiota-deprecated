<?php
include_once($serverRoot.'/config/dbconnection.php');

class KeyAdmin{

	private $conn;
	private $collId = 0;
	private $cId = 0;

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
	
	public function createCharacter($pArr){
		if (($pArr['chartype'] == 'IN') || ($pArr['chartype'] == 'RN')){
			$statusStr = '';
			$sql = 'INSERT INTO kmcharacters(charname,chartype,difficultyrank,hid,enteredby) '.
				'VALUES("'.$this->cleanInStr($pArr['charname']).'","'.$this->cleanInStr($pArr['chartype']).'",
				"'.$this->cleanInStr($pArr['difficultyrank']).'","'.$this->cleanInStr($pArr['hid']).'",
				"'.$this->cleanInStr($pArr['enteredby']).'") ';
			//echo $sql;
			if($this->conn->query($sql)){
				$this->cId = $this->conn->insert_id;
			}
			else{
				$statusStr = 'ERROR: Creation of new character failed: '.$this->conn->error.'<br/>';
				$statusStr .= 'SQL: '.$sql;
			}
			$sql2 = 'INSERT INTO kmcs(cid,cs,charstatename) '.
				'VALUES('.$this->cId.',"+High","Upper value of unspecified range (could be µ+s.d., but not known)"),'.
				'('.$this->cId.',"-Low","Lower value of unspecified range (could be µ-s.d., but not known)"),'.
				'('.$this->cId.',"Max","Maximum value"),'.
				'('.$this->cId.',"Mean","Mean (= average)"),'.
				'('.$this->cId.',"Min","Minimum value")';
			if($this->conn->query($sql2)){
				
			}
			return $statusStr;
		}
		else{
			$statusStr = '';
			$sql = 'INSERT INTO kmcharacters(charname,chartype,difficultyrank,hid,enteredby) '.
				'VALUES("'.$this->cleanInStr($pArr['charname']).'","'.$this->cleanInStr($pArr['chartype']).'",
				"'.$this->cleanInStr($pArr['difficultyrank']).'","'.$this->cleanInStr($pArr['hid']).'",
				"'.$this->cleanInStr($pArr['enteredby']).'") ';
			//echo $sql;
			if($this->conn->query($sql)){
				$this->cId = $this->conn->insert_id;
			}
			else{
				$statusStr = 'ERROR: Creation of new character failed: '.$this->conn->error.'<br/>';
				$statusStr .= 'SQL: '.$sql;
			}
			return $statusStr;
		}
	}
	
	public function createState($pArr){
		$statusStr = '';
		$sql = 'INSERT INTO kmcs(cid,charstatename,implicit,enteredby) '.
			'VALUES("'.$this->cleanInStr($pArr['cid']).'","'.$this->cleanInStr($pArr['charstatename']).'",1,
			"'.$this->cleanInStr($pArr['enteredby']).'") ';
		//echo $sql;
		if($this->conn->query($sql)){
			$this->cs = $this->conn->insert_id;
		}
		else{
			$statusStr = 'ERROR: Creation of new character failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}
	
	public function editCharacter($pArr){
		$statusStr = '';
		$cId = $pArr['cid'];
		if(is_numeric($cId)){
			$sql = '';
			foreach($pArr as $k => $v){
				if($k != 'formsubmit' && $k != 'cid'){
					$sql .= ','.$k.'='.($v?'"'.$this->cleanInStr($v).'"':'NULL');
				}
			}
			$sql = 'UPDATE kmcharacters SET '.substr($sql,1).' WHERE (cid = '.$cId.')';
			if($this->conn->query($sql)){
				$statusStr = 'SUCCESS: information saved';
			}
			else{
				$statusStr = 'ERROR: Editing of character failed: '.$this->conn->error.'<br/>';
				$statusStr .= 'SQL: '.$sql;
			}
		}
		return $statusStr;
	}
	
	public function editCharState($pArr){
		$statusStr = '';
		$cId = $pArr['cid'];
		$cs = $pArr['cs'];
		if(is_numeric($cId)){
			$sql = '';
			foreach($pArr as $k => $v){
				if($k != 'formsubmit' && $k != 'cid' && $k != 'cs'){
					$sql .= ','.$k.'='.($v?'"'.$this->cleanInStr($v).'"':'NULL');
				}
			}
			$sql = 'UPDATE kmcs SET '.substr($sql,1).' WHERE (cid = '.$cId.') AND (cs = '.$cs.')';
			if($this->conn->query($sql)){
				$statusStr = 'SUCCESS: information saved';
			}
			else{
				$statusStr = 'ERROR: Editing of character state failed: '.$this->conn->error.'<br/>';
				$statusStr .= 'SQL: '.$sql;
			}
		}
		return $statusStr;
	}
	
	public function getCharDetails($cId){
		$retArr = array();
		$sql = 'SELECT cid, charname, chartype, defaultlang, difficultyrank, hid, units, '.
			'description, notes, helpurl, enteredby '.
			'FROM kmcharacters '.
			'WHERE cid = '.$cId;
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
	
	public function getCharStateDetails($cId,$cs){
		$retArr = array();
		$sql = 'SELECT cid, cs, charstatename, implicit, notes, description, illustrationurl, '.
			'language, enteredby '.
			'FROM kmcs '.
			'WHERE cid = '.$cId.' AND cs = "'.$cs.'"';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr['charstatename'] = $this->cleanOutStr($r->charstatename);
				$retArr['implicit'] = $r->implicit;
				$retArr['notes'] = $this->cleanOutStr($r->notes);
				$retArr['description'] = $this->cleanOutStr($r->description);
				$retArr['illustrationurl'] = $r->illustrationurl;
				$retArr['language'] = $this->cleanOutStr($r->language);
				$retArr['enteredby'] = $r->enteredby;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function deleteChar($cId){
		$status = 0;
		if(is_numeric($cId)){
			$sql = 'DELETE FROM kmcharacters WHERE (cid = '.$cId.')';
			if($this->conn->query($sql)){
				$status = 1;
			}
		}
		return $status;
	}
	
	public function deleteCharState($cId,$cs){
		$status = 0;
		if(is_numeric($cId)){
			$sql = 'DELETE FROM kmcs WHERE (cid = '.$cId.') AND (cs = '.$cs.')';
			if($this->conn->query($sql)){
				$status = 1;
			}
		}
		return $status;
	}
	
	public function getCharStateList($cId){
		$retArr = array();
		$sql = 'SELECT cs, charstatename '.
			'FROM kmcs '.
			'WHERE cid = '.$cId.' '.
			'ORDER BY charstatename ASC';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->cs]['cs'] = $r->cs;
				$retArr[$r->cs]['charstatename'] = $this->cleanOutStr($r->charstatename);
			}
			$rs->close();
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
	
	public function getcId(){
		return $this->cId;
	}
	
	public function getcs(){
		return $this->cs;
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