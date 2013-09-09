<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class ChecklistAdmin {

	private $conn;
	private $clid;
	private $clName;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getMetaData(){
		$sql = "";
		$retArr = array();
		if($this->clid){
			$sql = "SELECT c.clid, c.name, c.locality, c.publication, ".
				"c.abstract, c.authors, c.parentclid, c.notes, ".
				"c.latcentroid, c.longcentroid, c.pointradiusmeters, c.access, ".
				"c.dynamicsql, c.datelastmodified, c.uid, c.type, c.initialtimestamp ".
				"FROM fmchecklists c WHERE (c.clid = ".$this->clid.')';
	 		$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$this->clName = $this->cleanOutStr($row->name);
				$retArr["locality"] = $this->cleanOutStr($row->locality); 
				$retArr["notes"] = $this->cleanOutStr($row->notes);
				$retArr["type"] = $row->type;
				$retArr["publication"] = $this->cleanOutStr($row->publication);
				$retArr["abstract"] = $this->cleanOutStr($row->abstract);
				$retArr["authors"] = $this->cleanOutStr($row->authors);
				$retArr["parentclid"] = $row->parentclid;
				$retArr["uid"] = $row->uid;
				$retArr["latcentroid"] = $row->latcentroid;
				$retArr["longcentroid"] = $row->longcentroid;
				$retArr["pointradiusmeters"] = $row->pointradiusmeters;
				$retArr["access"] = $row->access;
				$retArr["dynamicsql"] = $row->dynamicsql;
				$retArr["datelastmodified"] = $row->datelastmodified;
	    	}
	    	$result->free();
		}
		return $retArr;
	}
	
	public function editMetaData($editArr){
		$statusStr = '';
		$setSql = "";
		foreach($editArr as $key =>$value){
			if($value){
				$setSql .= ', '.$key.' = "'.$this->cleanInStr($value).'"';
			}
			else{
				$setSql .= ', '.$key.' = NULL';
			}
		}
		$sql = 'UPDATE fmchecklists SET '.substr($setSql,2).' WHERE (clid = '.$this->clid.')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$statusStr = 'Error: unable to update checklist metadata. SQL: '.$this->conn->error;
		}
		return $statusStr;
	}

	public function deleteChecklist($delClid){
		global $symbUid;
		$statusStr = true;
		$sql = 'SELECT uid FROM userpermissions WHERE (pname = "ClAdmin-'.$delClid.'") AND uid <> '.$symbUid;
		$rs = $this->conn->query($sql);
		if($rs->num_rows == 0){
			$sql = "DELETE FROM fmchklsttaxalink WHERE (clid = ".$delClid.')';
			$this->conn->query($sql);
			$sql = "DELETE FROM fmchecklists WHERE (clid = ".$delClid.')';
			if($this->conn->query($sql)){
				$sql = 'DELETE FROM userpermissions WHERE (pname = "ClAdmin-'.$delClid.'")';
				$this->conn->query($sql);
			}
			else{
				$statusStr = 'Checklist Deletion failed ('.$this->conn->error.'). Please contact data administrator.';
			}
		}
		else{
			$statusStr = 'Checklist cannot be deleted until all editors are removed. Remove editors and then try again.';
		}
		return $statusStr;
	}
	
	//Species editing functions (called from checklist.php)
	public function echoSpeciesAddList(){
		$sql = "SELECT DISTINCT t.tid, t.sciname FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
			"WHERE ts.taxauthid = 1 ";
		if($this->taxonFilter){
			$sql .= "AND t.rankid > 140 AND ((ts.family = '".$this->taxonFilter."') OR (t.sciname LIKE '".$this->taxonFilter."%')) ";
		}
		else{
			$sql .= "AND (t.rankid = 140 OR t.rankid = 180) ";
		}
		$sql .= "ORDER BY t.sciname";
		//echo $sql;
		$result = $this->clCon->query($sql);
		if($result){
	        while($row = $result->fetch_object()){
	        	if($this->taxonFilter){
	        		echo "<option value='".$row->tid."'>".$this->cleanOutStr($row->sciname)."</option>\n";
	        	}
	        	else{
	        		echo "<option>".$this->cleanOutStr($row->sciname)."</option>\n";
	        	}
	       	}
	       	$result->free();
		}
	}

	public function addNewSpecies($dataArr){
		if(!$this->clid) return 'ERROR adding species: checklist identifier not set';
		$insertStatus = false;
		$colSql = '';
		$valueSql = '';
		foreach($dataArr as $k =>$v){
			$colSql .= ','.$k;
			
			if($v){
				if(is_numeric($v)){
					$valueSql .= ','.$v;
				}
				else{
					$valueSql .= ',"'.$this->cleanInStr($v).'"';
				}
			}
			else{
				$valueSql .= ',NULL';
			}
		}
		$sql = 'INSERT INTO fmchklsttaxalink (clid'.$colSql.') '.
			'VALUES ('.$this->clid.$valueSql.')';
		if(!$this->conn->query($sql)){
			$insertStatus = 'ERROR: unable to add species ('.$this->conn->error;
		}
		return $insertStatus;
	}
	
    //Point functions
    public function addPoint($tid,$lat,$lng,$notes){
    	$statusStr = '';
    	if(is_numeric($tid) && is_numeric($lat) && is_numeric($lng)){
    		$sql = 'INSERT INTO fmchklstcoordinates(clid,tid,decimallatitude,decimallongitude,notes) '.
    			'VALUES('.$this->clid.','.$tid.','.$lat.','.$lng.',"'.$this->cleanInStr($notes).'")';
    		if(!$this->conn->query($sql)){
    			$statusStr = 'ERROR: unable to add point. '.$this->conn->error;
    		}
    	}
    	return $statusStr;
    }
    
    public function removePoint($pointPK){
    	$statusStr = '';
    	if($pointPK && is_numeric($pointPK)){
			if(!$this->conn->query('DELETE FROM fmchklstcoordinates WHERE (chklstcoordid = '.$pointPK.')')){
    			$statusStr = 'ERROR: unable to remove point. '.$this->conn->error;
			}
    	}
    	return $statusStr;
    }
    
	//Editor management
    public function getEditors(){
		$editorArr = array();
		$sql = 'SELECT u.uid, CONCAT_WS(", ",u.lastname,u.firstname) as uname '.
			'FROM userpermissions up INNER JOIN users u ON up.uid = u.uid '.
			'WHERE up.pname = "ClAdmin-'.$this->clid.'" ORDER BY u.lastname,u.firstname';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$uName = $r->uname;
				if(strlen($uName) > 60) $uName = substr($uName,0,60);
				$editorArr[$r->uid] = $r->uname;
			}
			$rs->free();
		}
		return $editorArr;
	}

	public function addEditor($u){
		$statusStr = '';
		$sql = 'INSERT INTO userpermissions(uid,pname) '.
			'VALUES('.$u.',"ClAdmin-'.$this->clid.'")';
		if(!$this->conn->query($sql)){
			$statusStr = 'ERROR: unable to add editor; SQL: '.$this->conn->error;
		}
		return $statusStr;
	}

	public function deleteEditor($u){
		$statusStr = '';
		$sql = 'DELETE FROM userpermissions '.
			'WHERE uid = '.$u.' AND pname = "ClAdmin-'.$this->clid.'"';
		if(!$this->conn->query($sql)){
			$statusStr = 'ERROR: unable to remove editor; SQL: '.$this->conn->error;
		}
		return $statusStr;
	}

	//Misc set/get functions
	public function setClid($clid){
		if(is_numeric($clid)){
			$this->clid = $clid;
		}
	}
	
	public function getClName(){
		return $this->clName;
	}
	
	//Get list data
	public function getPoints($tid){
		$retArr = array();
		$sql = 'SELECT c.chklstcoordid, c.decimallatitude, c.decimallongitude, c.notes '.
			'FROM fmchklstcoordinates c '.
			'WHERE c.clid = '.$this->clid.' AND c.tid = '.$tid;
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->chklstcoordid]['lat'] = $r->decimallatitude;
			$retArr[$r->chklstcoordid]['lng'] = $r->decimallongitude;
			$retArr[$r->chklstcoordid]['notes'] = $r->notes;
		}
		$rs->free();
		return $retArr;
	}
	
	public function getTaxa(){
		$retArr = array();
		$sql = 'SELECT t.tid, t.sciname '.
			'FROM fmchklsttaxalink l INNER JOIN taxa t ON l.tid = t.tid '.
			'WHERE l.clid = '.$this->clid.' ORDER BY t.sciname';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->tid] = $r->sciname;
		}
		$rs->free();
		return $retArr;
	}
	
	public function getUserList(){
		$returnArr = Array();
		$sql = 'SELECT u.uid, CONCAT_WS(", ",u.lastname,u.firstname) AS uname '.
			'FROM users u '.
			'ORDER BY u.lastname,u.firstname';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$returnArr[$r->uid] = $r->uname;
		}
		$rs->free();
		return $returnArr;
	}

	public function getInventoryProjects(){
		$retArr = Array();
		if($this->clid){
			$sql = 'SELECT p.pid, p.projname '.
				'FROM fmprojects p INNER JOIN fmchklstprojlink pl ON p.pid = pl.pid '.
				'WHERE pl.clid = '.$this->clid.' ORDER BY p.projname';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->pid] = $r->projname;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getVoucherProjects(){
		global $userRights;
		$retArr = array();
		$runQuery = true;
		$sql = 'SELECT collid, collectionname '.
			'FROM omcollections WHERE (colltype = "Observations" OR colltype = "General Observations") ';
		if(!array_key_exists('SuperAdmin',$userRights)){
			$collInStr = '';
			foreach($userRights as $k => $v){
				if($k == 'CollAdmin' || $k == 'CollEditor'){
					$collInStr .= ','.implode(',',$v);
				}
			}
			if($collInStr){
				$sql .= 'AND collid IN ('.substr($collInStr,1).') ';
			}
			else{
				$runQuery = false;
			}
		}
		$sql .= 'ORDER BY colltype,collectionname';
		//echo $sql;
		if($runQuery){
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr[$r->collid] = $r->collectionname;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function getParentArr(){
		$retArr = array();
		$sql = 'SELECT c.clid, c.name FROM fmchecklists c WHERE type = "static" AND access <> "private" ORDER BY c.name';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->clid] = $row->name;
		}
		$rs->free();
		return $retArr;
	}

	private function cleanOutStr($str){
		$str = str_replace('"',"&quot;",$str);
		$str = str_replace("'","&apos;",$str);
		return $str;
	}
	
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>