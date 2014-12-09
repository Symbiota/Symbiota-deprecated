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

	public function getInstitutionData($iid){
		$returnArr = Array();
		$sql = 'SELECT iid, institutioncode, institutionname, institutionname2, address1, address2, city, '.
			'stateprovince, postalcode, country, phone, contact, email, url, notes '.
			'FROM institutions ';
		if($iid) $sql .= 'WHERE iid = '.$iid.' '; 
		$sql .= 'ORDER BY institutionname,institutioncode';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_assoc()){
			$returnArr[$row['iid']] = $this->cleanOutArr($row);
		}
		$rs->close();
		if($iid){
			return $returnArr[$iid];
		}
		else{
			return $returnArr;
		}
	}

	public function submitInstitutionEdits($postData){
		$statusStr = '';
		if($postData['institutioncode'] && $postData['institutionname']){
			$sql = 'UPDATE institutions SET '.
				'institutioncode = "'.$this->cleanInStr($postData['institutioncode']).'",'.
				'institutionname = "'.$this->cleanInStr($postData['institutionname']).'",'.
				'institutionname2 = '.($postData['institutionname2']?'"'.$this->cleanInStr($postData['institutionname2']).'"':'NULL').','.
				'address1 = '.($postData['address1']?'"'.$this->cleanInStr($postData['address1']).'"':'NULL').','.
				'address2 = '.($postData['address2']?'"'.$this->cleanInStr($postData['address2']).'"':'NULL').','.
				'city = '.($postData['city']?'"'.$this->cleanInStr($postData['city']).'"':'NULL').','.
				'stateprovince = '.($postData['stateprovince']?'"'.$this->cleanInStr($postData['stateprovince']).'"':'NULL').','.
				'postalcode = '.($postData['postalcode']?'"'.$this->cleanInStr($postData['postalcode']).'"':'NULL').','.
				'country = '.($postData['country']?'"'.$this->cleanInStr($postData['country']).'"':'NULL').','.
				'phone = '.($postData['phone']?'"'.$this->cleanInStr($postData['phone']).'"':'NULL').','.
				'contact = '.($postData['contact']?'"'.$this->cleanInStr($postData['contact']).'"':'NULL').','.
				'email = '.($postData['email']?'"'.$this->cleanInStr($postData['email']).'"':'NULL').','.
				'url = '.($postData['url']?'"'.$this->cleanInStr($postData['url']).'"':'NULL').','.
				'notes = '.($postData['notes']?'"'.$this->cleanInStr($postData['notes']).'"':'NULL').' '.
				'WHERE iid = '.$postData['iid'];
			//echo "<div>$sql</div>"; exit;
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
			$this->cleanInStr($postData['institutionname']).'",'.
			($postData['institutionname2']?'"'.$this->cleanInStr($postData['institutionname2']).'"':'NULL').','.
			($postData['address1']?'"'.$this->cleanInStr($postData['address1']).'"':'NULL').','.
			($postData['address2']?'"'.$this->cleanInStr($postData['address2']).'"':'NULL').','.
			($postData['city']?'"'.$this->cleanInStr($postData['city']).'"':'NULL').','.
			($postData['stateprovince']?'"'.$this->cleanInStr($postData['stateprovince']).'"':'NULL').','.
			($postData['postalcode']?'"'.$this->cleanInStr($postData['postalcode']).'"':'NULL').','.
			($postData['country']?'"'.$this->cleanInStr($postData['country']).'"':'NULL').','.
			($postData['phone']?'"'.$this->cleanInStr($postData['phone']).'"':'NULL').','.
			($postData['contact']?'"'.$this->cleanInStr($postData['contact']).'"':'NULL').','.
			($postData['email']?'"'.$this->cleanInStr($postData['email']).'"':'NULL').','.
			($postData['url']?'"'.$postData['url'].'"':'NULL').','.
			($postData['notes']?'"'.$this->cleanInStr($postData['notes']).'"':'NULL').') ';
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
			$sql = 'DELETE FROM institutions WHERE iid = '.$delIid;
			//echo $sql;
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
	
	private function cleanOutArr($inArr){
		$outArr = array();
		foreach($inArr as $k => $v){
			$outArr[$k] = $this->cleanOutStr($v);
		}
		return $outArr;
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