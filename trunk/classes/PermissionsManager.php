<?php
include_once($serverRoot.'/config/dbconnection.php');

/*
SuperAdmin			Edit all data and assign new permissions

RareSppAdmin		Add or remove species from rare species list
RareSppReadAll		View and map rare species collection data for all collections
RareSppReader-#		View and map rare species collecton data for specific collections
CollAdmin-#			Upload records; modify metadata
CollEditor-#		Edit collection records

ClAdmin-#			Checklist write access
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

	public function getUsers($keyword){
		$returnArr = Array();
		$sql = "SELECT u.uid, u.firstname, u.lastname ".
			"FROM users u LEFT JOIN userlogin ul ON u.uid = ul.uid ";
		if($keyword){
			$sql .= "WHERE (u.lastname LIKE '".$this->conn->real_escape_string($keyword)."%') ";
			if(strlen($keyword) > 1) $sql .= "OR (ul.username LIKE '".$this->conn->real_escape_string($keyword)."%') ";
		}
		$sql .= 'ORDER BY u.lastname, u.firstname';
		//echo "<div>".$sql."</div>";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArr[$row->uid] = $row->lastname.", ".$row->firstname;
		}
		$result->close();
		return $returnArr;
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
			$result->close();
		}
		return $returnArr;
	}
	
	public function getUserPermissions($uid){
		$perArr = Array();
		if(is_numeric($uid)){
			$sql = "SELECT up.pname FROM userpermissions up WHERE (up.uid = ".$this->conn->real_escape_string($uid).')';
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$pName = $row->pname;
				if(strpos($pName,"CollAdmin-") !== false){
					$collId = substr($pName,10);
					$perArr["CollAdmin"][$collId] = $collId;
				}
				elseif(strpos($pName,"CollEditor-") !== false){
					$collId = substr($pName,11);
					$perArr["CollEditor"][$collId] = $collId;
				}
				elseif(strpos($pName,"RareSppReader-") !== false){
					$collId = substr($pName,14);
					$perArr["RareSppReader"][$collId] = $collId;
				}
				elseif(strpos($pName,"ClAdmin-") !== false){
					$clid = substr($pName,8);
					$perArr["ClAdmin"][$clid] = $clid;
				}
				else{
					//RareSppAdmin, RareSppReader, KeyEditor, TaxonProfile, Taxonomy
					$perArr[$pName] = $pName;
				}
			}
			$result->close();
			
			//If there are collections, get names
			if(array_key_exists("CollAdmin",$perArr)){
				$sql = "SELECT c.collid, c.collectionname FROM omcollections c ".
					"WHERE (c.collid IN(".implode(",",$perArr["CollAdmin"])."))";
				$result = $this->conn->query($sql);
				while($row = $result->fetch_object()){
					$perArr["CollAdmin"][$row->collid] = $row->collectionname;
				}
				$result->close();
			}
			if(array_key_exists("CollEditor",$perArr)){
				$sql = "SELECT c.collid, c.collectionname FROM omcollections c ".
					"WHERE (c.collid IN(".implode(",",$perArr["CollEditor"])."))";
				$result = $this->conn->query($sql);
				while($row = $result->fetch_object()){
					$perArr["CollEditor"][$row->collid] = $row->collectionname;
				}
				$result->close();
			}
			if(array_key_exists("RareSppReader",$perArr)){
				$sql = "SELECT c.collid, c.collectionname FROM omcollections c ".
					"WHERE (c.collid IN(".implode(",",$perArr["RareSppReader"])."))";
				$result = $this->conn->query($sql);
				while($row = $result->fetch_object()){
					$perArr["RareSppReader"][$row->collid] = $row->collectionname;
				}
				$result->close();
			}
	
			//If there are checklist, fetch names
			if(array_key_exists("ClAdmin",$perArr)){
				$sql = "SELECT cl.clid, cl.name FROM fmchecklists cl ".
					"WHERE (cl.clid IN(".implode(",",$perArr["ClAdmin"])."))";
				$result = $this->conn->query($sql);
				while($row = $result->fetch_object()){
					$perArr["ClAdmin"][$row->clid] = $row->name;
				}
				$result->close();
			}
		}
		return $perArr;
	}

	public function deletionPermissions($delStr, $id){
		if(is_numeric($id)){
			$sql = "DELETE FROM userpermissions WHERE (uid = ".$id.
				") AND (pname = '".$this->conn->real_escape_string($delStr)."')";
			$this->conn->query($sql);
		}
	}
	
	public function addPermissions($addList,$id){
		if($addList){
			$addStr = "(".$id.",'".implode("'),($id,'",$addList)."')"; 
			$sql = 'INSERT INTO userpermissions(uid,pname) VALUES'.$addStr;
			//echo $sql;
			$this->conn->query($sql);
		}
	}

	public function getCollectionArr($collKey){
		$returnArr = Array();
		$sql = 'SELECT c.collid, c.collectionname FROM omcollections c '.
			'WHERE colltype LIKE "%specimen%" ';
		if($collKey) $sql .= 'AND (c.collid NOT IN('.implode(',',$collKey).')) ';
		$sql .= 'ORDER BY c.collectionname';
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArr[$row->collid] = $row->collectionname;
		}
		return $returnArr;
	} 

	public function getObservationArr($collKey){
		$returnArr = Array();
		$sql = 'SELECT c.collid, c.collectionname FROM omcollections c '.
			'WHERE colltype LIKE "%observation%" ';
		if($collKey) $sql .= 'AND (c.collid NOT IN('.implode(',',$collKey).')) ';
		$sql .= 'ORDER BY c.collectionname';
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArr[$row->collid] = $row->collectionname;
		}
		return $returnArr;
	} 

	public function getChecklistArr($clKeys){
		$returnArr = Array();
		$sql = 'SELECT cl.clid, cl.name FROM fmchecklists cl ';
		if($clKeys) $sql .= 'WHERE (cl.clid NOT IN('.implode(',',$clKeys).')) ';
		$sql .= 'ORDER BY cl.name';
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArr[$row->clid] = $row->name;
		}
		return $returnArr;
	} 
}
?>