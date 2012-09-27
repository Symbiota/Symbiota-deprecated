<?php
include_once($serverRoot.'/config/dbconnection.php');

//Used by /collections/misc/collprofiles.php page
class InstitutionManager {

	private $conn;
	private $iid;
	private $collId;
	
	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function getInstitutionData(){
		$returnArr = Array();
		$sql = 'SELECT iid, institutioncode, institutionname, institutionname2, address1, address2, city, '.
			'stateprovince, postalcode, country, phone, contact, email, url, notes '.
			'FROM institutions ';
		if($this->iid) $sql .= 'WHERE iid = '.$this->iid.' '; 
		$sql .= 'ORDER BY institutionname,institutioncode';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_assoc()){
			$returnArr[$row['iid']] = $row;
		}
		$rs->close();
		return $returnArr;
	}

	public function submitInstitutionEdits($postData){
		$statusStr = '';
		if($postData['institutioncode'] && $postData['institutionname']){
			$sql = 'UPDATE institutions SET '.
				'institutioncode = "'.$this->cleanStr($postData['institutioncode']).'",'.
				'institutionname = "'.$this->cleanStr($postData['institutionname']).'",'.
				'institutionname2 = '.($postData['institutionname2']?'"'.$this->cleanStr($postData['institutionname2']).'"':'NULL').','.
				'address1 = '.($postData['address1']?'"'.$this->cleanStr($postData['address1']).'"':'NULL').','.
				'address2 = '.($postData['address2']?'"'.$this->cleanStr($postData['address2']).'"':'NULL').','.
				'city = '.($postData['city']?'"'.$this->cleanStr($postData['city']).'"':'NULL').','.
				'stateprovince = '.($postData['stateprovince']?'"'.$this->cleanStr($postData['stateprovince']).'"':'NULL').','.
				'postalcode = '.($postData['postalcode']?'"'.$this->cleanStr($postData['postalcode']).'"':'NULL').','.
				'country = '.($postData['country']?'"'.$this->cleanStr($postData['country']).'"':'NULL').','.
				'phone = '.($postData['phone']?'"'.$this->cleanStr($postData['phone']).'"':'NULL').','.
				'contact = '.($postData['contact']?'"'.$this->cleanStr($postData['contact']).'"':'NULL').','.
				'email = '.($postData['email']?'"'.$this->cleanStr($postData['email']).'"':'NULL').','.
				'url = '.($postData['url']?'"'.$this->cleanStr($postData['url']).'"':'NULL').','.
				'notes = '.($postData['notes']?'"'.$this->cleanStr($postData['notes']).'"':'NULL').' '.
				'WHERE iid = '.$postData['iid'];
			//echo "<div>$sql</div>";
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR: unable edit institution -> '.$this->conn->error;
			}
		}
		return $statusStr;
	}

	public function submitInstitutionAdd($postData){
		$newIid = 0;
		$sql = 'INSERT INTO institutions (institutioncode, institutionname, institutionname2, address1, address2, city, '.
			'stateprovince, postalcode, country, phone, contact, email, url, notes) '.
			'VALUES ("'.$postData['institutioncode'].'","'.
			$postData['institutionname'].'",'.
			($postData['institutionname2']?'"'.$postData['institutionname2'].'"':'NULL').','.
			($postData['address1']?'"'.$postData['address1'].'"':'NULL').','.
			($postData['address2']?'"'.$postData['address2'].'"':'NULL').','.
			($postData['city']?'"'.$postData['city'].'"':'NULL').','.
			($postData['stateprovince']?'"'.$postData['stateprovince'].'"':'NULL').','.
			($postData['postalcode']?'"'.$postData['postalcode'].'"':'NULL').','.
			($postData['country']?'"'.$postData['country'].'"':'NULL').','.
			($postData['phone']?'"'.$postData['phone'].'"':'NULL').','.
			($postData['contact']?'"'.$postData['contact'].'"':'NULL').','.
			($postData['email']?'"'.$postData['email'].'"':'NULL').','.
			($postData['url']?'"'.$postData['url'].'"':'NULL').','.
			($postData['notes']?'"'.$postData['notes'].'"':'NULL').') ';
		//echo "<div>$sql</div>";
		if($this->conn->query($sql)){
			$newIid = $this->conn->insert_id;
		}
		else{
			$newIid = 'ERROR: unable to create institution: '.$this->conn->error;
		}
		return $newIid;
	}

	public function deleteInstitution($delIid){
		$status = '';
		//Check to see if record is linked to collections
		$sql = 'SELECT collid, CONCAT_WS(" ",CollectionName,CONCAT(InstitutionCode,IFNULL(CONCAT(":",CollectionCode),""))) AS name '.
			'FROM omcollections WHERE iid = '.$delIid.' ORDER BY CollectionName,InstitutionCode,CollectionCode';
		//echo $sql;
		$rs = $this->conn->query($sql);
		if($rs->num_rows){
			$status = '<div style="font-weight:bold;">ERROR: Unable to delete due to links to following collections</div>';
			$status .= '<ul>';
			while($r = $rs->fetch_object()){
				$status .= '<li>'.$r->name.'</li>';
			}
			$status .= '</ul><br/>';
		}
		$rs->close();
		
		//Check outgoing and incoming loans
		$sql = 'SELECT loanid '.
			'FROM omoccurloans '.
			'WHERE iidOwner = '.$delIid.' OR iidBorrower = '.$delIid;
		$rs = $this->conn->query($sql);
		if($rs->num_rows){
			$status .= '<div style="font-weight:bold;">ERROR: Unable to delete due to links to '.$rs->num_rows.' loan records</div>';
		}
		$rs->close();

		if(!$status){
			//If record is not linked to other data, OK to delete
			$sql = 'DELETE dd FROM institutions WHERE iid = '.$delIid;
			if($this->conn->query($sql)){
				$status = 1;
			}
		}
		return $status;
	}
	
	public function setCollectionId($collId){
		if(is_numeric($collId)){
			$this->collId = $collId;
			//Set iid
			$sql ='SELECT iid '.
				'FROM omcollections '.
				'WHERE collid = '.$collId;
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->iid = $row->iid;
			}
			$rs->close();
		}
	}

	public function setInstitutionId($id){
		if(is_numeric($id)){
			$this->iid = $id;
		}
	}
	
	public function getInstitutionId(){
		return $this->iid;
	}
	
	private function cleanStr($inStr){
		$outStr = trim($inStr);
		$outStr = $this->conn->real_escape_string(htmlspecialchars($outStr));
		return $outStr;
	}
}
?>