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
		$this->collId = $collId;
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
		$sql = "SELECT IFNULL(i.InstitutionCode,c.InstitutionCode) AS institutioncode, i.InstitutionName, ".
			"i.Address1, i.Address2, i.City, i.StateProvince, i.PostalCode, i.Country, i.Phone, ".
			"c.collid, c.CollectionCode, c.CollectionName, ".
			"c.BriefDescription, c.FullDescription, c.Homepage, c.individualurl, c.Contact, c.email, c.latitudedecimal, ".
			"c.longitudedecimal, c.icon, c.colltype, c.managementtype, c.publicedits, c.sortseq, cs.uploaddate, ".
			"IFNULL(cs.recordcnt,0) AS recordcnt, IFNULL(cs.georefcnt,0) AS georefcnt, ".
			"IFNULL(cs.familycnt,0) AS familycnt, IFNULL(cs.genuscnt,0) AS genuscnt, IFNULL(cs.speciescnt,0) AS speciescnt ".
			"FROM omcollections c INNER JOIN omcollectionstats cs ON c.collid = cs.collid ".
			"LEFT JOIN institutions i ON c.iid = i.iid ".
			"WHERE c.collid = $this->collId ORDER BY c.SortSeq";
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
		return $returnArr;
	}

	public function submitCollEdits($editArr){
		$instCode = trim($editArr['institutioncode']);
		$collCode = trim($editArr['collectioncode']);
		$coleName = trim($editArr['collectionname']);
		$briefDesc = trim($editArr['briefdescription']);
		$fullDesc = trim($editArr['fulldescription']);
		$homepage = trim($editArr['homepage']);
		$contact = trim($editArr['contact']);
		$email = trim($editArr['email']);
		$publicEdits = (array_key_exists('publicedits',$editArr)?$editArr['publicedits']:0);
		$conn = MySQLiConnectionFactory::getCon("write");
		$sql = 'UPDATE omcollections '.
			'SET institutioncode = '.($instCode?'"'.$instCode.'"':'NULL').','.
			'collectioncode = '.($collCode?'"'.$collCode.'"':'NULL').','.
			'collectionname = '.($coleName?'"'.$coleName.'"':'NULL').','.
			'briefdescription = '.($briefDesc?'"'.$briefDesc.'"':'NULL').','.
			'fulldescription = '.($fullDesc?'"'.$fullDesc.'"':'NULL').','.
			'homepage = '.($homepage?'"'.$homepage.'"':'NULL').','.
			'contact = '.($contact?'"'.$contact.'"':'NULL').','.
			'email = '.($email?'"'.$email.'"':'NULL').','.
			'latitudedecimal = '.($editArr['latitudedecimal']?$editArr['latitudedecimal']:'NULL').','.
			'longitudedecimal = '.($editArr['longitudedecimal']?$editArr['longitudedecimal']:'NULL').','.
			'publicedits = '.$publicEdits.' ';
		if(array_key_exists('icon',$editArr)){
			$icon = trim($editArr['icon']);
			$indUrl = trim($editArr['individualurl']);
			$sql .= ',icon = '.($icon?'"'.$icon.'"':'NULL').','.
				'managementtype = "'.$editArr['managementtype'].'",'.
				'colltype = "'.$editArr['colltype'].'",'.
				'individualurl = '.($indUrl?'"'.$indUrl.'"':'NULL').' '.
				($editArr['sortseq']?',sortseq = '.$editArr['sortseq']:'').' ';
		}
		$sql .= 'WHERE collid = '.$this->collId;
		//echo $sql;
		$conn->query($sql);
		$conn->close();
	}

	public function submitCollAdd($addArr){
		global $symbUid;
		$instCode = trim($addArr['institutioncode']);
		$collCode = trim($addArr['collectioncode']);
		$coleName = trim($addArr['collectionname']);
		$briefDesc = trim($addArr['briefdescription']);
		$fullDesc = trim($addArr['fulldescription']);
		$homepage = trim($addArr['homepage']);
		$contact = trim($addArr['contact']);
		$email = trim($addArr['email']);
		$publicEdits = (array_key_exists('publicedits',$addArr)?$addArr['publicedits']:0);
		$icon = trim($addArr['icon']);
		$indUrl = trim($addArr['individualurl']);
		$conn = MySQLiConnectionFactory::getCon("write");
		$sql = 'INSERT INTO omcollections(institutioncode,collectioncode,collectionname,briefdescription,fulldescription,homepage,'.
			'contact,email,latitudedecimal,longitudedecimal,publicedits,icon,managementtype,colltype,individualurl,sortseq) '.
			'VALUES ('.(array_key_exists('individualurl',$addArr)?'"'.$instCode.'"':'NULL').
			',"'.$addArr['collectioncode'].'","'.$addArr['collectionname'].'",'.
			($addArr['briefdescription']?'"'.$addArr['briefdescription'].'"':'NULL').','.
			($addArr['fulldescription']?'"'.$addArr['fulldescription'].'"':'NULL').','.
			($addArr['homepage']?'"'.$addArr['homepage'].'"':'NULL').','.
			($addArr['contact']?'"'.$addArr['contact'].'"':'NULL').','.
			($addArr['email']?'"'.$addArr['email'].'"':'NULL').','.
			($addArr['latitudedecimal']?$addArr['latitudedecimal']:'NULL').','.
			($addArr['longitudedecimal']?$addArr['longitudedecimal']:'NULL').','.
			$publicEdits.','.
			(array_key_exists("icon",$addArr)&&$addArr['icon']?'"'.$addArr['icon'].'"':'NULL').','.
			(array_key_exists('managementtype',$addArr)?'"'.$addArr['managementtype'].'"':'snapshot').','.
			(array_key_exists('colltype',$addArr)?'"'.$addArr['colltype'].'"':'Preserved Specimens').','.
			(array_key_exists('individualurl',$addArr)&&$addArr['individualurl']?'"'.$addArr['individualurl'].'"':'NULL').','.
			(array_key_exists('sortseq',$addArr)&&$addArr['sortseq']?$addArr['sortseq']:'NULL').') ';
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
}

 ?>