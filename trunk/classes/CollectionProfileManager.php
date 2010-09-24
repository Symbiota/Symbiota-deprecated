<?php
 include_once($serverRoot.'/config/dbconnection.php');

 class CollectionProfileManager {

	private $con;
	private $collId;

 	public function __construct(){
 		$this->con = MySQLiConnectionFactory::getCon("readonly");
 	}
 	
 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}
	
	public function setCollectionId($collId){
		$this->collId = $collId;
	}

	public function getCollectionList(){
		$returnArr = Array();
		$sql = "SELECT c.collid, c.CollectionCode, c.CollectionName, c.BriefDescription, ".
			"c.Homepage, c.Contact, c.email, c.icon ".
			"FROM omcollections c ORDER BY c.SortSeq";
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->collid]["collectioncode"] = $row->CollectionCode;
			$returnArr[$row->collid]["collectionname"] = $row->CollectionName;
			$returnArr[$row->collid]["briefdescription"] = $row->BriefDescription;
			$returnArr[$row->collid]["homepage"] = $row->Homepage;
			$returnArr[$row->collid]["contact"] = $row->Contact;
			$returnArr[$row->collid]["email"] = $row->email;
			$returnArr[$row->collid]["icon"] = $row->icon;
		}
		$rs->close();
		return $returnArr;
	}
	
	public function getCollectionData(){
		$returnArr = Array();
		$sql = "SELECT i.InstitutionCode, i.InstitutionName, i.Address1, i.Address2, i.City, i.StateProvince, ".
			"i.PostalCode, i.Country, i.Phone, c.collid, c.CollectionCode, c.CollectionName, ".
			"c.BriefDescription, c.FullDescription, c.Homepage, c.Contact, c.email, c.icon, ".
			"cs.recordcnt, cs.familycnt, cs.genuscnt, cs.speciescnt, cs.georefcnt, cs.uploaddate ".
			"FROM omcollections c INNER JOIN omcollectionstats cs ON c.collid = cs.collid ".
			"LEFT JOIN institutions i ON c.iid = i.iid ".
			"WHERE c.collid = $this->collId ORDER BY c.SortSeq";
		//echo $sql;
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr["institutioncode"] = $row->InstitutionCode;
			$returnArr["institutionname"] = $row->InstitutionName;
			$returnArr["address2"] = $row->Address1;
			$returnArr["address1"] = $row->Address2;
			$returnArr["city"] = $row->City;
			$returnArr["stateprovince"] = $row->StateProvince;
			$returnArr["postalcode"] = $row->PostalCode;
			$returnArr["country"] = $row->Country;
			$returnArr["phone"] = $row->Phone;
			$returnArr["collectioncode"] = $row->CollectionCode;
			$returnArr["collectionname"] = $row->CollectionName;
			$returnArr["briefdescription"] = $row->BriefDescription;
			$returnArr["fulldescription"] = $row->FullDescription;
			$returnArr["homepage"] = $row->Homepage;
			$returnArr["contact"] = $row->Contact;
			$returnArr["email"] = $row->email;
			$returnArr["icon"] = $row->icon;
			$returnArr["recordcnt"] = $row->recordcnt;
			$returnArr["familycnt"] = $row->familycnt;
			$returnArr["generacnt"] = $row->genuscnt;
			$returnArr["speciescnt"] = $row->speciescnt;
			$returnArr["georefcnt"] = $row->georefcnt;
			$uDate = "";
			if($row->uploaddate){
				$uDate = $row->uploaddate;
				$month = substr($uDate,5,2);
				$day = substr($uDate,8,2);
				$year = substr($uDate,0,4);
				$uDate = date("j F Y",mktime(0,0,0,$month,$day,$year));
			}
			$returnArr["uploaddate"] = $uDate;
		}
		$rs->close();
		return $returnArr;
	}
	
	public function submitCollEdits($editArr){
		$conn = MySQLiConnectionFactory::getCon("write");
		$sql = "";
		foreach($editArr as $field=>$value){
			$sql .= ",$field = \"".$value."\"";
		}
		$sql = "UPDATE omcollections SET ".substr($sql,1)." WHERE collid = ".$this->collId;
		//echo $sql;
		$conn->query($sql);
		$conn->close();
	}
	
	public function getFamilyRecordCounts(){
		$returnArr = Array();
		//Specimen count
		$sql = "SELECT o.Family, Count(*) AS cnt ".
			"FROM omoccurrences o GROUP BY o.CollID, o.Family HAVING (o.CollID = $this->collId) AND (o.Family IS NOT NULL) AND o.Family <> '' ".
			"ORDER BY o.Family";
		$rs = $this->con->query($sql);
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
		$rs = $this->con->query($sql);
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
			"AND (o.country = 'USA' OR o.country = 'United States' OR o.country = 'United States of America') ".
			"ORDER BY o.StateProvince";
		//echo $sql;
		$rs = $this->con->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->StateProvince] = $row->cnt;
		}
		$rs->close();
		return $returnArr;
	}
 }

 ?>