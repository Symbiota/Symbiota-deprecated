<?php
include_once($serverRoot.'/config/dbconnection.php');

/*
SuperAdmin			Edit all data and assign new permissions

RareSppAdmin		Add or remove species from rare species list
RareSppReadAll		View and map rare species collection data for all collections
RareSppReader-#		View and map rare species collecton data for specific collections
CollAdmin-#			Upload records; modify metadata
CollEditor-#		Edit collection records
CollTaxon-#:#		Edit collection records within taxonomic speciality 

ClAdmin-#			Checklist write access
ProjAdmin-#			Project admin access
KeyAdmin			Edit identification key characters and character states
KeyEditor			Edit identification key data
TaxonProfile		Modify decriptions; add images; 
Taxonomy			Add names; edit name; change taxonomy
*/

class PermissionsManager{
	
	private $conn;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getUser($uid){
		$returnArr = Array();
		if(is_numeric($uid)){
			$sql = "SELECT u.uid, u.firstname, u.lastname, u.title, u.institution, u.city, u.state, ".
				"u.zip, u.country, u.email, u.url, u.notes, ul.username ".
				"FROM users u LEFT JOIN userlogin ul ON u.uid = ul.uid ".
				"WHERE (u.uid = ".$uid.')';
			//echo "<div>$sql</div>";
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$returnArr["uid"] = $row->uid;
				$returnArr["firstname"] = $row->firstname;
				$returnArr["lastname"] = $row->lastname;
				$returnArr["title"] = $row->title;
				$returnArr["institution"] = $row->institution;
				$returnArr["city"] = $row->city;
				$returnArr["state"] = $row->state;
				$returnArr["zip"] = $row->zip;
				$returnArr["country"] = $row->country;
				$returnArr["email"] = $row->email;
				$returnArr["url"] = $row->url;
				$returnArr["notes"] = $row->notes;
				$returnArr["username"][] = $row->username;
				while($row = $result->fetch_object()){
					$returnArr["username"][] = $row->username;
				}
			}
			$result->free();
		}
		return $returnArr;
	}
	
	public function getUserPermissions($uid){
		$perArr = Array();
		if(is_numeric($uid)){
			$sql = 'SELECT up.pname, up.assignedby, up.initialtimestamp '.
				'FROM userpermissions up WHERE (up.uid = '.$this->conn->real_escape_string($uid).')';
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$pName = $row->pname;
				$assignedBy = 'assigned by: '.($row->assignedby?$row->assignedby.' ('.$row->initialtimestamp.')':'unknown');
				if(strpos($pName,"CollAdmin-") !== false){
					$collId = substr($pName,10);
					$perArr["CollAdmin"][$collId] = $assignedBy;
				}
				elseif(strpos($pName,"CollEditor-") !== false){
					$collId = substr($pName,11);
					$perArr["CollEditor"][$collId] = $assignedBy;
				}
				elseif(strpos($pName,"RareSppReader-") !== false){
					$collId = substr($pName,14);
					$perArr["RareSppReader"][$collId] = $assignedBy;
				}
				elseif(strpos($pName,"ClAdmin-") !== false){
					$clid = substr($pName,8);
					$perArr["ClAdmin"][$clid] = $assignedBy;
				}
				elseif(strpos($pName,"ProjAdmin-") !== false){
					$pid = substr($pName,10);
					$perArr["ProjAdmin"][$pid] = $assignedBy;
				}
				else{
					//RareSppAdmin, RareSppReader, KeyEditor, TaxonProfile, Taxonomy
					$perArr[$pName] = '<span title="'.$assignedBy.'">'.$pName.'</span>';
				}
			}
			$result->free();
			
			//If there are collections, get names
			if(array_key_exists("CollAdmin",$perArr)){
				$sql = "SELECT c.collid, c.collectionname FROM omcollections c ".
					"WHERE (c.collid IN(".implode(",",array_keys($perArr["CollAdmin"]))."))";
				$result = $this->conn->query($sql);
				while($row = $result->fetch_object()){
					$cName = '<span title="'.$perArr["CollAdmin"][$row->collid].'">'.$row->collectionname.'</span>';
					$perArr["CollAdmin"][$row->collid] = $cName;
				}
				$result->free();
			}
			if(array_key_exists("CollEditor",$perArr)){
				$sql = "SELECT c.collid, c.collectionname FROM omcollections c ".
					"WHERE (c.collid IN(".implode(",",array_keys($perArr["CollEditor"]))."))";
				$result = $this->conn->query($sql);
				while($row = $result->fetch_object()){
					$cName = '<span title="'.$perArr["CollEditor"][$row->collid].'">'.$row->collectionname.'</span>';
					$perArr["CollEditor"][$row->collid] = $cName;
				}
				$result->free();
			}
			if(array_key_exists("RareSppReader",$perArr)){
				$sql = "SELECT c.collid, c.collectionname FROM omcollections c ".
					"WHERE (c.collid IN(".implode(",",array_keys($perArr["RareSppReader"]))."))";
				$result = $this->conn->query($sql);
				while($row = $result->fetch_object()){
					$cName = '<span title="'.$perArr["RareSppReader"][$row->collid].'">'.$row->collectionname.'</span>';
					$perArr["RareSppReader"][$row->collid] = $cName;
				}
				$result->free();
			}
	
			//If there are checklist, fetch names
			if(array_key_exists("ClAdmin",$perArr)){
				$sql = "SELECT cl.clid, cl.name FROM fmchecklists cl ".
					"WHERE (cl.clid IN(".implode(",",array_keys($perArr["ClAdmin"]))."))";
				$result = $this->conn->query($sql);
				while($row = $result->fetch_object()){
					$clName = '<span title="'.$perArr["ClAdmin"][$row->clid].'">'.$row->name.'</span>';
					$perArr["ClAdmin"][$row->clid] = $clName;
				}
				$result->free();
			}

			//If there are project admins, fetch project names
			if(array_key_exists("ProjAdmin",$perArr)){
				$sql = "SELECT pid, projname FROM fmprojects ".
					"WHERE (pid IN(".implode(",",array_keys($perArr["ProjAdmin"]))."))";
				$result = $this->conn->query($sql);
				while($row = $result->fetch_object()){
					$pName = '<span title="'.$perArr["ProjAdmin"][$row->pid].'">'.$row->projname.'</span>';
					$perArr["ProjAdmin"][$row->pid] = $pName;
				}
				$result->free();
			}
		}
		return $perArr;
	}

	public function deletePermission($id, $delStr){
		$statusStr = '';
		if(is_numeric($id)){
			$sql = 'DELETE FROM userpermissions '.
				'WHERE (uid = '.$id.') AND (pname = "'.$this->cleanInStr($delStr).'")';
			$this->conn->query($sql);
		}
		return $statusStr;
	}
	
	public function addPermission($uid, $pname){
		global $paramsArr;
		$statusStr = '';
		if(is_numeric($uid)){
			$sql = 'INSERT INTO userpermissions(uid,pname,assignedby) '.
				'VALUES('.$uid.',"'.$pname.'","'.$paramsArr['un'].'")';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR adding user permission: '.$this->conn->error;
			}
		}
		return $statusStr;
	}

	public function getTaxonEditorArr($collid, $limitByColl = 0){
		//grab the current permissions
		$pArr = array();
		$sql2 = 'SELECT uid, pname FROM userpermissions WHERE pname LIKE "CollTaxon-'.$collid.':%" ';
		//echo $sql2;
		$rs2 = $this->conn->query($sql2);
		while($r2 = $rs2->fetch_object()){
			if($r2->pname == 'CollTaxon-'.$collid.':all'){
				$pArr[$r2->uid]['all'] = 1;
			}
			else{
				$utId = substr($r2->pname,strrpos($r2->pname,':')+1);
				$pArr[$r2->uid]['utid'][] = $utId;
			}
		}
		$rs2->free();
		//Get editors
		$retArr = array();
		$sql = 'SELECT ut.idusertaxonomy, u.uid, CONCAT_WS(", ", lastname, firstname) as fullname, t.sciname, l.username '.
			'FROM usertaxonomy ut INNER JOIN users u ON ut.uid = u.uid '.
			'INNER JOIN taxa t ON ut.tid = t.tid '.
			'INNER JOIN userlogin l ON u.uid = l.uid '.
			'WHERE ut.editorstatus = "OccurrenceEditor" ';
		if($limitByColl && $pArr){
			$sql .= 'AND ut.uid IN('.implode(',',array_keys($pArr)).') ';
		}
		$sql .= 'ORDER BY u.lastname, u.firstname, t.sciname';
		//echo '<div>'.$sql.'</div>';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($limitByColl){
				//Add all that have permissions within given collection
				if(isset($pArr[$r->uid])){
					if(isset($pArr[$r->uid]['all']) || in_array($r->idusertaxonomy,$pArr[$r->uid]['utid'])){
						$retArr[$r->uid]['username'] = $r->fullname.' ('.$r->username.')';
						$retArr[$r->uid][$r->idusertaxonomy] = $r->sciname;
					}
				}
			}
			else{
				if(!isset($pArr[$r->uid]) || !isset($pArr[$r->uid]['utid']) || !in_array($r->idusertaxonomy,$pArr[$r->uid]['utid'])){
					$retArr[$r->uid]['username'] = $r->fullname.' ('.$r->username.')';
					$retArr[$r->uid][$r->idusertaxonomy] = $r->sciname;
				}
			}
		}
		$rs->free();
		//Add permissions for those with all
		foreach($pArr as $uid => $pArr){
			if(array_key_exists('all',$pArr)) $retArr[$uid]['all'] = 1;
		}

		return $retArr;
	}
	
	//General get list functions
	public function getCollectionMetadata($targetCollid = 0, $collTypeLimit = ''){
		$retArr = Array();
		$sql = 'SELECT collid, collectionname, institutioncode, collectioncode, colltype '.
			'FROM omcollections ';
		$sqlWhere = '';
		if($collTypeLimit == 'specimens'){
			$sqlWhere .= 'AND (colltype = "Preserved Specimens") ';
		}
		elseif($collTypeLimit == 'observations'){
			$sqlWhere .= 'AND (colltype = "Observations" OR colltype = "General Observations") ';
		}
		if($targetCollid){
			$sqlWhere .= 'AND (collid = '.$targetCollid.') ';
		}
		if($sqlWhere) $sql .= 'WHERE '.substr($sqlWhere,4);
		$sql .= 'ORDER BY collectionname';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid]['collectionname'] = $this->cleanOutStr($r->collectionname);
			$retArr[$r->collid]['institutioncode'] = $r->institutioncode;
			$retArr[$r->collid]['collectioncode'] = $r->collectioncode;
			$retArr[$r->collid]['colltype'] = $r->colltype;
		}
		$rs->free();
		return $retArr;
	} 

	public function getCollectionEditors($collid){
		$returnArr = Array();
		if($collid){
			$sql = 'SELECT up.uid, up.pname, CONCAT_WS(", ",u.lastname,u.firstname) AS uname, up.assignedby, up.initialtimestamp '.
				'FROM userpermissions up INNER JOIN users u ON up.uid = u.uid '.
				'WHERE up.pname = "CollAdmin-'.$collid.'" OR up.pname = "CollEditor-'.$collid.'" '. 
				'OR up.pname = "RareSppReader-'.$collid.'" '.
				'ORDER BY u.lastname,u.firstname';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$pGroup = 'rarespp';
				if(substr($r->pname,0,9) == 'CollAdmin') $pGroup = 'admin';
				elseif(substr($r->pname,0,10) == 'CollEditor') $pGroup = 'editor';
				$outStr = '<span title="assigned by: '.($r->assignedby?$r->assignedby.' ('.$r->initialtimestamp.')':'unknown').'">'.$this->cleanOutStr($r->uname).'</span>';
				$returnArr[$pGroup][$r->uid] = $outStr;
			}
			$rs->free();
		}
		return $returnArr;
	}

	public function getUsers($searchTerm){
		$retArr = Array();
		$sql = 'SELECT u.uid, CONCAT_WS(", ",u.lastname,u.firstname) AS uname '.
			'FROM users u ';
		if($searchTerm){
			$searchTerm = $this->cleanInStr($searchTerm);
			$sql .= 'LEFT JOIN userlogin ul ON u.uid = ul.uid '.
				'WHERE (u.lastname LIKE "'.$searchTerm.'%") ';
			if(strlen($searchTerm) > 1) $sql .= "OR (ul.username LIKE '".$searchTerm."%') ";
		}
		$sql .= 'ORDER BY u.lastname, u.firstname';
		//echo "<div>".$sql."</div>";
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->uid] = $this->cleanOutStr($r->uname);
		}
		$rs->free();
		return $retArr;
	}

	public function getProjectArr($pidKeys){
		$returnArr = Array();
		$sql = 'SELECT pid, projname FROM fmprojects ';
		if($pidKeys) $sql .= 'WHERE (pid NOT IN('.implode(',',$pidKeys).')) ';
		$sql .= 'ORDER BY projname';
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArr[$row->pid] = $row->projname;
		}
		$result->free();
		return $returnArr;
	} 

	public function getChecklistArr($clKeys){
		$returnArr = Array();
		$sql = 'SELECT cl.clid, cl.name FROM fmchecklists cl ';
		if($clKeys) $sql .= 'WHERE (cl.access <> "private") AND (cl.clid NOT IN('.implode(',',$clKeys).')) ';
		$sql .= 'ORDER BY cl.name';
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArr[$row->clid] = $row->name;
		}
		$result->free();
		return $returnArr;
	}

	//Misc fucntions
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