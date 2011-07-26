<?php
include_once($serverRoot.'/config/dbconnection.php');

//Used by /collections/misc/collprofiles.php page
class CollectionProfileManager {

	private $conn;
	private $collId;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function setCollectionId($collId){
		if(is_numeric($collId)){
			$this->collId = $this->conn->real_escape_string($collId);
		}
	}

	public function getCollectionList(){
		$returnArr = Array();
		$sql = "SELECT c.collid, c.institutioncode, c.collectioncode, c.CollectionName, c.briefdescription, ".
			"c.Homepage, c.Contact, c.email, c.icon ".
			"FROM omcollections c ORDER BY c.SortSeq,c.CollectionName";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->collid]['institutioncode'] = $row->institutioncode;
			$returnArr[$row->collid]['collectioncode'] = $row->collectioncode;
			$returnArr[$row->collid]['collectionname'] = $row->CollectionName;
			$returnArr[$row->collid]['briefdescription'] = $row->briefdescription;
			$returnArr[$row->collid]['homepage'] = $row->Homepage;
			$returnArr[$row->collid]['contact'] = $row->Contact;
			$returnArr[$row->collid]['email'] = $row->email;
			$returnArr[$row->collid]['icon'] = $row->icon;
		}
		$rs->close();
		return $returnArr;
	}

	public function getCollectionData(){
		$returnArr = Array();
		if($this->collId){
			$sql = "SELECT IFNULL(i.InstitutionCode,c.InstitutionCode) AS institutioncode, i.InstitutionName, ".
				"i.Address1, i.Address2, i.City, i.StateProvince, i.PostalCode, i.Country, i.Phone, ".
				"c.collid, c.CollectionCode, c.CollectionName, ".
				"c.BriefDescription, c.FullDescription, c.Homepage, c.individualurl, c.Contact, c.email, c.latitudedecimal, ".
				"c.longitudedecimal, c.icon, c.colltype, c.managementtype, c.publicedits, c.sortseq, cs.uploaddate, ".
				"IFNULL(cs.recordcnt,0) AS recordcnt, IFNULL(cs.georefcnt,0) AS georefcnt, ".
				"IFNULL(cs.familycnt,0) AS familycnt, IFNULL(cs.genuscnt,0) AS genuscnt, IFNULL(cs.speciescnt,0) AS speciescnt ".
				"FROM omcollections c INNER JOIN omcollectionstats cs ON c.collid = cs.collid ".
				"LEFT JOIN institutions i ON c.iid = i.iid ".
				"WHERE c.collid = ".$this->collId." ORDER BY c.SortSeq";
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$returnArr['institutioncode'] = $row->institutioncode;
				$returnArr['institutionname'] = $row->InstitutionName;
				$returnArr['address2'] = $row->Address1;
				$returnArr['address1'] = $row->Address2;
				$returnArr['city'] = $row->City;
				$returnArr['stateprovince'] = $row->StateProvince;
				$returnArr['postalcode'] = $row->PostalCode;
				$returnArr['country'] = $row->Country;
				$returnArr['phone'] = $row->Phone;
				$returnArr['collectioncode'] = $row->CollectionCode;
				$returnArr['collectionname'] = $row->CollectionName;
				$returnArr['briefdescription'] = $row->BriefDescription;
				$returnArr['fulldescription'] = $row->FullDescription;
				$returnArr['homepage'] = $row->Homepage;
				$returnArr['individualurl'] = $row->individualurl;
				$returnArr['contact'] = $row->Contact;
				$returnArr['email'] = $row->email;
				$returnArr['latitudedecimal'] = $row->latitudedecimal;
				$returnArr['longitudedecimal'] = $row->longitudedecimal;
				$returnArr['icon'] = $row->icon;
				$returnArr['colltype'] = $row->colltype;
				$returnArr['managementtype'] = $row->managementtype;
				$returnArr['publicedits'] = $row->publicedits;
				$returnArr['sortseq'] = $row->sortseq;
				$uDate = "";
				if($row->uploaddate){
					$uDate = $row->uploaddate;
					$month = substr($uDate,5,2);
					$day = substr($uDate,8,2);
					$year = substr($uDate,0,4);
					$uDate = date("j F Y",mktime(0,0,0,$month,$day,$year));
				}
				$returnArr['uploaddate'] = $uDate;
				$returnArr['recordcnt'] = $row->recordcnt;
				$returnArr['georefcnt'] = $row->georefcnt;
				$returnArr['familycnt'] = $row->familycnt;
				$returnArr['genuscnt'] = $row->genuscnt;
				$returnArr['speciescnt'] = $row->speciescnt;
			}
			$rs->close();
		}
		return $returnArr;
	}

	public function submitCollEdits(){
		if($this->collId){
			$instCode = $this->cleanStr($_POST['institutioncode']);
			$collCode = $this->cleanStr($_POST['collectioncode']);
			$coleName = $this->cleanStr($_POST['collectionname']);
			$briefDesc = $this->cleanStr($_POST['briefdescription']);
			$fullDesc = $this->cleanStr($_POST['fulldescription']);
			$homepage = $this->cleanStr($_POST['homepage']);
			$contact = $this->cleanStr($_POST['contact']);
			$email = $this->cleanStr($_POST['email']);
			$publicEdits = (array_key_exists('publicedits',$_POST)?$_POST['publicedits']:0);
			
			$conn = MySQLiConnectionFactory::getCon("write");
			$sql = 'UPDATE omcollections '.
				'SET institutioncode = "'.$instCode.'",'.
				'collectioncode = '.($collCode?'"'.$collCode.'"':'NULL').','.
				'collectionname = "'.$coleName.'",'.
				'briefdescription = '.($briefDesc?'"'.$briefDesc.'"':'NULL').','.
				'fulldescription = '.($fullDesc?'"'.$fullDesc.'"':'NULL').','.
				'homepage = '.($homepage?'"'.$homepage.'"':'NULL').','.
				'contact = '.($contact?'"'.$contact.'"':'NULL').','.
				'email = '.($email?'"'.$email.'"':'NULL').','.
				'latitudedecimal = '.($_POST['latitudedecimal']?$_POST['latitudedecimal']:'NULL').','.
				'longitudedecimal = '.($_POST['longitudedecimal']?$_POST['longitudedecimal']:'NULL').','.
				'publicedits = '.$publicEdits.' ';
			if(array_key_exists('icon',$_POST)){
				$icon = $this->cleanStr($_POST['icon']);
				$indUrl = $this->cleanStr($_POST['individualurl']);
				$sql .= ',icon = '.($icon?'"'.$icon.'"':'NULL').','.
					'managementtype = "'.$_POST['managementtype'].'",'.
					'colltype = "'.$_POST['colltype'].'",'.
					'individualurl = '.($indUrl?'"'.$indUrl.'"':'NULL').' '.
					($_POST['sortseq']?',sortseq = '.$_POST['sortseq']:'').' ';
			}
			$sql .= 'WHERE collid = '.$this->collId;
			//echo $sql;
			$conn->query($sql);
			$conn->close();
		}
	}

	public function submitCollAdd(){
		global $symbUid;
		$instCode = $this->cleanStr($_POST['institutioncode']);
		$collCode = $this->cleanStr($_POST['collectioncode']);
		$coleName = $this->cleanStr($_POST['collectionname']);
		$briefDesc = $this->cleanStr($_POST['briefdescription']);
		$fullDesc = $this->cleanStr($_POST['fulldescription']);
		$homepage = $this->cleanStr($_POST['homepage']);
		$contact = $this->cleanStr($_POST['contact']);
		$email = $this->cleanStr($_POST['email']);
		$publicEdits = (array_key_exists('publicedits',$_POST)?$_POST['publicedits']:0);
		$icon = array_key_exists('icon',$_POST)?$this->cleanStr($_POST['icon']):'';
		$managementType = array_key_exists('managementtype',$_POST)?$this->cleanStr($_POST['managementtype']):'';
		$collType = array_key_exists('colltype',$_POST)?$this->cleanStr($_POST['colltype']):'';
		$indUrl = array_key_exists('individualurl',$_POST)?$this->cleanStr($_POST['individualurl']):'';
		$sortSeq = array_key_exists('sortseq',$_POST)?$_POST['sortseq']:'';
		
		$conn = MySQLiConnectionFactory::getCon("write");
		$sql = 'INSERT INTO omcollections(institutioncode,collectioncode,collectionname,briefdescription,fulldescription,homepage,'.
			'contact,email,latitudedecimal,longitudedecimal,publicedits,icon,managementtype,colltype,individualurl,sortseq) '.
			'VALUES ("'.$instCode.'",'.
			($collCode?'"'.$collCode.'"':'NULL').',"'.$coleName.'",'.
			($briefDesc?'"'.$briefDesc.'"':'NULL').','.
			($fullDesc?'"'.$fullDesc.'"':'NULL').','.
			($homepage?'"'.$homepage.'"':'NULL').','.
			($contact?'"'.$contact.'"':'NULL').','.
			($email?'"'.$email.'"':'NULL').','.
			($_POST['latitudedecimal']?$_POST['latitudedecimal']:'NULL').','.
			($_POST['longitudedecimal']?$_POST['longitudedecimal']:'NULL').','.
			$publicEdits.','.
			($icon?'"'.$icon.'"':'NULL').','.
			($managementType?'"'.$managementType.'"':'snapshot').','.
			($collType?'"'.$collType.'"':'Preserved Specimens').','.
			($indUrl?'"'.$indUrl.'"':'NULL').','.
			($sortSeq?$sortSeq:'NULL').') ';
		//echo "<div>$sql</div>";
		$conn->query($sql);
		$cid = $conn->insert_id;
		$sql = 'INSERT INTO omcollectionstats(collid,recordcnt,uploadedby) '.
			'VALUES('.$cid.',0,"'.$symbUid.'")';
		$conn->query($sql);
		$conn->close();
		return $cid;
	}

	public function getFamilyRecordCounts(){
		$returnArr = Array();
		//Specimen count
		$sql = 'SELECT o.Family, Count(*) AS cnt '.
			'FROM omoccurrences o GROUP BY o.CollID, o.Family HAVING (o.CollID = '.$this->collId.') AND (o.Family IS NOT NULL) AND o.Family <> "" '.
			'ORDER BY o.Family';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->Family] = $row->cnt;
		}
		$rs->close();
		return $returnArr;
	}

	public function getCountryRecordCounts(){
		$returnArr = Array();
		//Specimen count
		$sql = "SELECT o.Country, Count(*) AS cnt ".
			"FROM omoccurrences o GROUP BY o.CollID, o.Country HAVING (o.CollID = $this->collId) AND o.Country IS NOT NULL AND o.Country <> '' ".
			"ORDER BY o.Country";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->Country] = $row->cnt;
		}
		$rs->close();
		return $returnArr;
	}

	public function getStateRecordCounts(){
		$returnArr = Array();
		//Specimen count
		$sql = "SELECT o.StateProvince, Count(*) AS cnt ".
			"FROM omoccurrences o GROUP BY o.CollID, o.StateProvince, o.country ".
			"HAVING (o.CollID = $this->collId) AND (o.StateProvince IS NOT NULL) AND (o.StateProvince <> '') ".
			"AND o.country IN('USA','United States','United States of America','US') ".
			"ORDER BY o.StateProvince";
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->StateProvince] = $row->cnt;
		}
		$rs->close();
		return $returnArr;
	}

	private function cleanStr($inStr){
		$outStr = trim($inStr);
		$outStr = str_replace('"',"'",$inStr);
		$outStr = $this->conn->real_escape_string($outStr);
		return $outStr;
	}
}

 ?>