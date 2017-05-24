<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once('ProfileManager.php');

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
				"c.latcentroid, c.longcentroid, c.pointradiusmeters, c.access, c.defaultSettings, ".
				"c.dynamicsql, c.datelastmodified, c.uid, c.type, c.initialtimestamp, c.footprintWKT ".
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
				$retArr["defaultSettings"] = $row->defaultSettings;
				$retArr["dynamicsql"] = $row->dynamicsql;
				$retArr["datelastmodified"] = $row->datelastmodified;
				$retArr["footprintWKT"] = $row->footprintWKT;
			}
			$result->free();
		}
		return $retArr;
	}

	public function createChecklist($postArr){
		$sqlInsert = "";
		$sqlValues = "";
		$defaultViewArr = Array();
		$defaultViewArr["ddetails"] = array_key_exists("ddetails",$postArr)?1:0;
		$defaultViewArr["dcommon"] = array_key_exists("dcommon",$postArr)?1:0;
		$defaultViewArr["dimages"] = array_key_exists("dimages",$postArr)?1:0;
		$defaultViewArr["dvouchers"] = array_key_exists("dvouchers",$postArr)?1:0;
		$defaultViewArr["dauthors"] = array_key_exists("dauthors",$postArr)?1:0;
		$defaultViewArr["dalpha"] = array_key_exists("dalpha",$postArr)?1:0;
		$defaultViewArr["activatekey"] = array_key_exists("activatekey",$postArr)?1:0;
		if($defaultViewArr) $postArr["defaultsettings"] = json_encode($defaultViewArr);
		
		$fieldArr = array('name'=>'s','authors'=>'s','type'=>'s','locality'=>'s','publication'=>'s','abstract'=>'s','notes'=>'s','latcentroid'=>'n',
				'longcentroid'=>'n','pointradiusmeters'=>'n','footprintWKT'=>'s','parentclid'=>'n','access'=>'s','uid'=>'n','defaultsettings'=>'s');
		
		foreach($fieldArr as $fieldName => $fieldType){
			$sqlInsert .= ','.$fieldName;
			$v = $this->cleanInStr($postArr[$fieldName]);
			if($fieldName != 'abstract') $v = strip_tags($v, '<i><u><b><a>');
			if($v){
				if($fieldType == 's'){
					$sqlValues .= ',"'.$v.'"';
				}
				else{
					if(is_numeric($v)){
						$sqlValues .= ','.$v;
					}
					else{
						$sqlValues .= ',NULL';
					}
				}
			}
			else{
				$sqlValues .= ',NULL';
			}
		}
		$sql = "INSERT INTO fmchecklists (".substr($sqlInsert,1).") VALUES (".substr($sqlValues,1).")";
	
		$newClId = 0;
		if($this->conn->query($sql)){
			$newClId = $this->conn->insert_id;
			//Set permissions to allow creater to be an editor
			$this->conn->query('INSERT INTO userroles (uid, role, tablename, tablepk) VALUES('.$GLOBALS["SYMB_UID"].',"ClAdmin","fmchecklists",'.$newClId.') ');
			//$this->conn->query("INSERT INTO userpermissions (uid, pname) VALUES(".$GLOBALS["symbUid"].",'ClAdmin-".$newClId."') ");
			$newPManager = new ProfileManager();
			$newPManager->setUserName($GLOBALS['USERNAME']);
			$newPManager->authenticate();
		}
		return $newClId;
	}

	public function editMetaData($postArr){
		$statusStr = '';
		$setSql = "";
		$defaultViewArr = Array();
		$defaultViewArr["ddetails"] = array_key_exists("ddetails",$postArr)?1:0;
		$defaultViewArr["dcommon"] = array_key_exists("dcommon",$postArr)?1:0;
		$defaultViewArr["dimages"] = array_key_exists("dimages",$postArr)?1:0;
		$defaultViewArr["dvouchers"] = array_key_exists("dvouchers",$postArr)?1:0;
		$defaultViewArr["dauthors"] = array_key_exists("dauthors",$postArr)?1:0;
		$defaultViewArr["dalpha"] = array_key_exists("dalpha",$postArr)?1:0;
		$defaultViewArr["activatekey"] = array_key_exists("activatekey",$postArr)?1:0;
		if($defaultViewArr) $postArr["defaultsettings"] = json_encode($defaultViewArr);
		
		$fieldArr = array('name'=>'s','authors'=>'s','type'=>'s','locality'=>'s','publication'=>'s','abstract'=>'s','notes'=>'s','latcentroid'=>'n',
			'longcentroid'=>'n','pointradiusmeters'=>'n','footprintWKT'=>'s','parentclid'=>'n','access'=>'s','defaultsettings'=>'s');
		foreach($fieldArr as $fieldName => $fieldType){
			$v = $this->cleanInStr($postArr[$fieldName]);
			if($fieldName != 'abstract') $v = strip_tags($v, '<i><u><b><a>');

			if($v){
				if($fieldType == 's'){
					$setSql .= ', '.$fieldName.' = "'.$v.'"';
				}
				elseif($fieldType == 'n' && is_numeric($v)){
					$setSql .= ', '.$fieldName.' = "'.$v.'"';
				}
				else{
					$setSql .= ', '.$fieldName.' = NULL';
				}
			}
			else{
				$setSql .= ', '.$fieldName.' = NULL';
			}
		}
		$sql = 'UPDATE fmchecklists SET '.substr($setSql,2).' WHERE (clid = '.$this->clid.')';
		//echo $sql; exit;
		if($this->conn->query($sql)){
			if($postArr['type'] == 'rarespp'){
				if($postArr['locality']){
					$sql = 'UPDATE omoccurrences o INNER JOIN taxstatus ts1 ON o.tidinterpreted = ts1.tid '.
						'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
						'INNER JOIN fmchklsttaxalink cl ON ts2.tid = cl.tid '.
						'SET o.localitysecurity = 1 '.
						'WHERE (cl.clid = '.$this->clid.') AND (o.stateprovince = "'.$postArr['locality'].'") AND (o.localitySecurityReason IS NULL) '.
						'AND (o.localitysecurity IS NULL OR o.localitysecurity = 0) AND (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) ';
					if(!$this->conn->query($sql)){
						$statusStr = 'Error updating rare state species: '.$this->conn->error;
					}
				}
			}
		}
		else{
			$statusStr = 'Error: unable to update checklist metadata. SQL: '.$this->conn->error;
		}
		return $statusStr;
	}

	public function deleteChecklist($delClid){
		$statusStr = true;
		$sql1 = 'SELECT uid FROM userroles '.
			'WHERE (role = "ClAdmin") AND (tablename = "fmchecklists") AND (tablepk = "'.$delClid.'") AND uid <> '.$GLOBALS['SYMB_UID'];
		$rs1 = $this->conn->query($sql1);
		if($rs1->num_rows == 0){
			$sql2 = "DELETE FROM fmvouchers WHERE (clid = ".$delClid.')';
			if($this->conn->query($sql2)){
				$sql3 = "DELETE FROM fmchklsttaxalink WHERE (clid = ".$delClid.')';
				if($this->conn->query($sql3)){
					$sql4 = "DELETE FROM fmchecklists WHERE (clid = ".$delClid.')';
					if($this->conn->query($sql4)){
						//Delete userpermissions reference once patch is submitted
						$sql5 = 'DELETE FROM userroles WHERE (role = "ClAdmin") AND (tablename = "fmchecklists") AND (tablepk = "'.$delClid.'")';
						$this->conn->query($sql5);
					}
					else{
						$statusStr = 'ERROR attempting to delete checklist: '.$this->conn->error;
					}
				}
				else{
					$statusStr = 'ERROR attempting to delete checklist taxa links: '.$this->conn->error;
				}
			}
			else{
				$statusStr = 'ERROR attempting to delete checklist vouchers: '.$this->conn->error;
			}
		}
		else{
			$statusStr = 'Checklist cannot be deleted until all editors are removed. Remove editors and then try again.';
		}
		$rs1->free();
		return $statusStr;
	}

	//Child checklist functions
	public function getChildrenChecklist(){
		$retArr = Array();
		$targetStr = $this->clid;
		do{
			$sql = 'SELECT c.clid, c.name, child.clid as pclid '.
				'FROM fmchklstchildren child INNER JOIN fmchecklists c ON child.clidchild = c.clid '.
				'WHERE child.clid IN('.trim($targetStr,',').') '.
				'ORDER BY c.name ';
			$rs = $this->conn->query($sql);
			$targetStr = '';
			while($r = $rs->fetch_object()){
				$retArr[$r->clid]['name'] = $r->name;
				$retArr[$r->clid]['pclid'] = $r->pclid;
				$targetStr .= ','.$r->clid;
			}
			$rs->free();
		}while($targetStr);
		asort($retArr);
		return $retArr;
	}
	
	public function getParentChecklists(){
		$retArr = Array();
		$targetStr = $this->clid;
		do{
			$sql = 'SELECT c.clid, c.name, child.clid as pclid '.
				'FROM fmchklstchildren child INNER JOIN fmchecklists c ON child.clid = c.clid '.
				'WHERE child.clidchild IN('.trim($targetStr,',').') ';
			$rs = $this->conn->query($sql);
			$targetStr = '';
			while($r = $rs->fetch_object()){
				$retArr[$r->clid] = $r->name;
				$targetStr .= ','.$r->clid;
			}
			if($targetStr) $targetStr = substr($targetStr,1);
			$rs->free();
		}while($targetStr);
		asort($retArr);
		return $retArr;
	}
	
	public function getChildSelectArr(){
		$retArr = array();
		$clidStr = '';
		if(isset($GLOBALS['USER_RIGHTS']) && $GLOBALS['USER_RIGHTS']['ClAdmin']){
			$clidStr = implode(',',$GLOBALS['USER_RIGHTS']['ClAdmin']);
		}
		if($clidStr){
			$sql = 'SELECT clid, name '.
				'FROM fmchecklists '.
				'WHERE clid <> '.$this->clid.' AND clid IN('.$clidStr.') '.
				'ORDER BY name';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->clid] = $r->name;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function addChildChecklist($clidAdd){
		$statusStr = '';
		$sql = 'INSERT INTO fmchklstchildren(clid, clidchild, modifieduid) '.
			'VALUES('.$this->clid.','.$clidAdd.','.$GLOBALS['SYMB_UID'].') ';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$statusStr = 'ERROR adding child checklist link';
		}
		return $statusStr;
	}

	public function deleteChildChecklist($clidDel){
		$statusStr = '';
		$sql = 'DELETE FROM fmchklstchildren WHERE clid = '.$this->clid.' AND clidchild = '.$clidDel;
		//echo $sql;
		if(!$this->conn->query($sql)){
			$statusStr = 'ERROR deleting child checklist link';
		}
		return $statusStr;
	}

	public function addNewSpecies($dataArr,$setRareSpp = false){
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
		if($this->conn->query($sql)){
			if($setRareSpp){
				$clMeta = $this->getMetaData();
				$state = $clMeta['locality'];
				if($state && $dataArr['tid']){
					$sqlRare = 'UPDATE omoccurrences o INNER JOIN taxstatus ts1 ON o.tidinterpreted = ts1.tid '.
						'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
						'SET o.localitysecurity = 1 '.
						'WHERE (o.localitysecurity IS NULL OR o.localitysecurity = 0) AND (o.localitySecurityReason IS NULL) '.
						'AND (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (o.stateprovince = "'.$state.'") AND (ts2.tid = '.$dataArr['tid'].')';
					//echo $sqlRare; exit;
					$this->conn->query($sqlRare);
				}
			}
		}
		else{
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
		$sql = 'SELECT u.uid, CONCAT(CONCAT_WS(", ",u.lastname,u.firstname)," (",l.username,")") as uname '.
			'FROM userroles ur INNER JOIN users u ON ur.uid = u.uid '.
			'INNER JOIN userlogin l ON u.uid = l.uid '.
			'WHERE (ur.role = "ClAdmin") AND (ur.tablename = "fmchecklists") AND (ur.tablepk = '.$this->clid.') '.
			'ORDER BY u.lastname,u.firstname';
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
		if(is_numeric($u) && $this->clid){
			$sql = 'INSERT INTO userroles(uid,role,tablename,tablepk) '.
				'VALUES('.$u.',"ClAdmin","fmchecklists",'.$this->clid.')';
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR: unable to add editor; SQL: '.$this->conn->error;
			}
		}
		return $statusStr;
	}

	public function deleteEditor($u){
		$statusStr = '';
		$sql = 'DELETE FROM userroles '.
			'WHERE (uid = '.$u.') AND (role = "ClAdmin") AND (tablename = "fmchecklists") AND (tablepk = '.$this->clid.') ';
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

	public function getReferenceChecklists(){
		$retArr = array();
		$sql = 'SELECT clid, name FROM fmchecklists WHERE access = "public" ';
		$clArr = array();
		if(isset($GLOBALS['USER_RIGHTS']['ClAdmin'])){
			$clidStr = implode(',',$GLOBALS['USER_RIGHTS']['ClAdmin']);
			if($clidStr) $sql .= 'OR clid IN('.$clidStr.') ';
		}
		$sql .= 'ORDER BY name';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->clid] = $row->name;
		}
		$rs->close();
		return $retArr;
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
		$sql = 'SELECT u.uid, CONCAT(CONCAT_WS(", ",u.lastname,u.firstname)," (",l.username,")") AS uname '. 
			'FROM users u INNER JOIN userlogin l ON u.uid = l.uid '.
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

	public function getManagementLists($uid){
		$returnArr = Array();
		if(is_numeric($uid)){
			//Get project and checklist IDs from userpermissions
			$clStr = '';
			$projStr = '';
			$sql = 'SELECT role,tablepk FROM userroles '.
				'WHERE (uid = '.$uid.') AND (role = "ClAdmin" OR role = "ProjAdmin") ';
			//$sql = 'SELECT pname FROM userpermissions '.
			//	'WHERE (uid = '.$uid.') AND (pname LIKE "ClAdmin-%" OR pname LIKE "ProjAdmin-%") ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if($r->role == 'ClAdmin') $clStr .= ','.$r->tablepk;
				if($r->role == 'ProjAdmin') $projStr .= ','.$r->tablepk;
			}
			$rs->free();
			if($clStr){
				//Get checklists
				$sql = 'SELECT clid, name FROM fmchecklists '.
						'WHERE (clid IN('.substr($clStr,1).')) '.
						'ORDER BY name';
				$rs = $this->conn->query($sql);
				while($row = $rs->fetch_object()){
					$returnArr['cl'][$row->clid] = $row->name;
				}
				$rs->free();
			}
			if($projStr){
				//Get projects
				$sql = 'SELECT pid, projname '.
						'FROM fmprojects '.
						'WHERE (pid IN('.substr($projStr,1).')) '.
						'ORDER BY projname';
				$rs = $this->conn->query($sql);
				while($row = $rs->fetch_object()){
					$returnArr['proj'][$row->pid] = $row->projname;
				}
				$rs->free();
			}
		}
		return $returnArr;
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