<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/UuidFactory.php');

//Used by /collections/misc/collprofiles.php page
class CollectionProfileManager {

	private $conn;
	private $collid;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function setCollectionId($collid){
		if($collid && is_numeric($collid)){
			$this->collid = $this->cleanInStr($collid);
		}
	}

	public function getCollectionList(){
		$returnArr = Array();
		$sql = "SELECT c.collid, c.institutioncode, c.collectioncode, c.collectionname, ".
			"c.fulldescription, c.homepage, c.contact, c.email, c.icon, c.collectionguid ".
			"FROM omcollections c ORDER BY c.SortSeq,c.CollectionName";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->collid]['institutioncode'] = $row->institutioncode;
			$returnArr[$row->collid]['collectioncode'] = $row->collectioncode;
			$returnArr[$row->collid]['collectionname'] = $row->collectionname;
			$returnArr[$row->collid]['fulldescription'] = $row->fulldescription;
			$returnArr[$row->collid]['homepage'] = $row->homepage;
			$returnArr[$row->collid]['contact'] = $row->contact;
			$returnArr[$row->collid]['email'] = $row->email;
			$returnArr[$row->collid]['icon'] = $row->icon;
			$returnArr[$row->collid]['guid'] = $row->collectionguid;
		}
		$rs->close();
		return $returnArr;
	}

	public function getCollectionData($filterForForm = 0){
		$returnArr = Array();
		if($this->collid){
			$sql = "SELECT c.institutioncode, i.iid, i.InstitutionName, ".
				"i.Address1, i.Address2, i.City, i.StateProvince, i.PostalCode, i.Country, i.Phone, ".
				"c.collid, c.CollectionCode, c.CollectionName, ".
				"c.FullDescription, c.Homepage, c.individualurl, c.Contact, c.email, ".
				"c.latitudedecimal, c.longitudedecimal, c.icon, c.colltype, c.managementtype, c.publicedits, ".
				"c.guidtarget, c.rights, c.rightsholder, c.accessrights, c.sortseq, cs.uploaddate, ".
				"IFNULL(cs.recordcnt,0) AS recordcnt, IFNULL(cs.georefcnt,0) AS georefcnt, ".
				"IFNULL(cs.familycnt,0) AS familycnt, IFNULL(cs.genuscnt,0) AS genuscnt, IFNULL(cs.speciescnt,0) AS speciescnt, ".
				"c.securitykey, c.collectionguid ".
				"FROM omcollections c INNER JOIN omcollectionstats cs ON c.collid = cs.collid ".
				"LEFT JOIN institutions i ON c.iid = i.iid ".
				"WHERE (c.collid = ".$this->collid.") ";
			//echo $sql;
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$returnArr['institutioncode'] = $row->institutioncode;
				$returnArr['iid'] = $row->iid;
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
				$returnArr['guidtarget'] = $row->guidtarget;
				$returnArr['rights'] = $row->rights;
				$returnArr['rightsholder'] = $row->rightsholder;
				$returnArr['accessrights'] = $row->accessrights;
				$returnArr['sortseq'] = $row->sortseq;
				$returnArr['skey'] = $row->securitykey;
				$returnArr['guid'] = $row->collectionguid;
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
				$returnArr['georefpercent'] = ($returnArr['recordcnt']?round(($row->georefcnt/$returnArr['recordcnt'])*100):0);
				$returnArr['familycnt'] = $row->familycnt;
				$returnArr['genuscnt'] = $row->genuscnt;
				$returnArr['speciescnt'] = $row->speciescnt;
			}
			$rs->close();
			//Get catagories
			$sql = 'SELECT ccpk '.
				'FROM omcollcatlink '.
				'WHERE (collid = '.$this->collid.') ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$returnArr['ccpk'] = $r->ccpk;
			}
			$rs->close();
			//Get additional statistics
			$sql = 'SELECT count(DISTINCT o.occid) as imgcnt '.
				'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'WHERE (o.collid = '.$this->collid.') ';
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$returnArr['imgpercent'] = ($returnArr['recordcnt']?round(($row->imgcnt/$returnArr['recordcnt'])*100):0);
			}
			$rs->close();
			//BOLD count
			$sql = 'SELECT count(g.occid) as boldcnt '.
				'FROM omoccurrences o INNER JOIN omoccurgenetic g ON o.occid = g.occid '.
				'WHERE (o.collid = '.$this->collid.') AND (g.resourceurl LIKE "http://www.boldsystems%") ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$returnArr['boldcnt'] = $r->boldcnt;
			}
			$rs->close();
			//GenBank count
			$sql = 'SELECT count(g.occid) as gencnt '.
				'FROM omoccurrences o INNER JOIN omoccurgenetic g ON o.occid = g.occid '.
				'WHERE (o.collid = '.$this->collid.') AND (g.resourceurl LIKE "http://www.ncbi%") ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$returnArr['gencnt'] = $r->gencnt;
			}
			$rs->close();
			//Reference count
			$sql = 'SELECT count(r.occid) as refcnt '.
				'FROM omoccurrences o INNER JOIN referenceoccurlink r ON o.occid = r.occid '.
				'WHERE (o.collid = '.$this->collid.') ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$returnArr['refcnt'] = $r->refcnt;
			}
			$rs->close();
			//Check to make sure Security Key and collection GUIDs exist
			if(!$returnArr['guid']){
				$returnArr['guid'] = UuidFactory::getUuidV4();
				$conn = MySQLiConnectionFactory::getCon('write');
				$sql = 'UPDATE omcollections SET collectionguid = "'.$returnArr['guid'].'" '.
					'WHERE collectionguid IS NULL AND collid = '.$this->collid;
				$conn->query($sql);
			}
			if(!$returnArr['skey']){
				$returnArr['skey'] = UuidFactory::getUuidV4();
				$conn = MySQLiConnectionFactory::getCon('write');
				$sql = 'UPDATE omcollections SET securitykey = "'.$returnArr['skey'].'" '.
					'WHERE securitykey IS NULL AND collid = '.$this->collid;
				$conn->query($sql);
			}  
		}
		if($filterForForm){
			$this->cleanOutArr($returnArr);
		}
		return $returnArr;
	}

	public function submitCollEdits(){
		$status = true;
		if($this->collid){
			$instCode = $this->cleanInStr($_POST['institutioncode']);
			$collCode = $this->cleanInStr($_POST['collectioncode']);
			$coleName = $this->cleanInStr($_POST['collectionname']);
			$iid = $_POST['iid'];
			$fullDesc = $this->cleanInStr($_POST['fulldescription']);
			$homepage = $this->cleanInStr($_POST['homepage']);
			$contact = $this->cleanInStr($_POST['contact']);
			$email = $this->cleanInStr($_POST['email']);
			$publicEdits = (array_key_exists('publicedits',$_POST)?$_POST['publicedits']:0);
			$guidTarget = (array_key_exists('guidtarget',$_POST)?$_POST['guidtarget']:'');
			$rights = $this->cleanInStr($_POST['rights']);
			$rightsHolder = $this->cleanInStr($_POST['rightsholder']);
			$accessRights = $this->cleanInStr($_POST['accessrights']);
			
			$conn = MySQLiConnectionFactory::getCon("write");
			$sql = 'UPDATE omcollections '.
				'SET institutioncode = "'.$instCode.'",'.
				'collectioncode = '.($collCode?'"'.$collCode.'"':'NULL').','.
				'collectionname = "'.$coleName.'",'.
				'iid = '.($iid?$iid:'NULL').','.
				'fulldescription = '.($fullDesc?'"'.$fullDesc.'"':'NULL').','.
				'homepage = '.($homepage?'"'.$homepage.'"':'NULL').','.
				'contact = '.($contact?'"'.$contact.'"':'NULL').','.
				'email = '.($email?'"'.$email.'"':'NULL').','.
				'latitudedecimal = '.($_POST['latitudedecimal']?$_POST['latitudedecimal']:'NULL').','.
				'longitudedecimal = '.($_POST['longitudedecimal']?$_POST['longitudedecimal']:'NULL').','.
				'publicedits = '.$publicEdits.','.
				'guidtarget = '.($guidTarget?'"'.$guidTarget.'"':'NULL').','.
				'rights = '.($rights?'"'.$rights.'"':'NULL').','.
				'rightsholder = '.($rightsHolder?'"'.$rightsHolder.'"':'NULL').','.
				'accessrights = '.($accessRights?'"'.$accessRights.'"':'NULL').' ';
			if(array_key_exists('icon',$_POST)){
				$icon = $this->cleanInStr($_POST['icon']);
				$indUrl = $this->cleanInStr($_POST['individualurl']);
				$sql .= ',icon = '.($icon?'"'.$icon.'"':'NULL').','.
					'managementtype = "'.$_POST['managementtype'].'",'.
					'colltype = "'.$_POST['colltype'].'",'.
					'individualurl = '.($indUrl?'"'.$indUrl.'"':'NULL').', '.
					'sortseq = '.($_POST['sortseq']?$_POST['sortseq']:'NULL').' ';
			}
			$sql .= 'WHERE (collid = '.$this->collid.')';
			//echo $sql;
			if(!$conn->query($sql)){
				$status = 'ERROR updating collection: '.$conn->error;
				return $status;
			}
			
			//Modify collection catagory, if needed
			if(isset($_POST['ccpk']) && $_POST['ccpk']){
				$rs = $conn->query('SELECT ccpk FROM omcollcatlink WHERE collid = '.$this->collid);
				if($r = $rs->fetch_object()){
					if($r->ccpk <> $_POST['ccpk']){
						if(!$conn->query('UPDATE omcollcatlink SET ccpk = '.$_POST['ccpk'].' WHERE ccpk = '.$r->ccpk.' AND collid = '.$this->collid)){
							$status = 'ERROR updating collection catagory link: '.$conn->error;
							return $status;
						}
					}
				}
				else{
					if(!$conn->query('INSERT INTO omcollcatlink (ccpk,collid) VALUES('.$_POST['ccpk'].','.$this->collid.')')){
						$status = 'ERROR inserting collection catagory link(1): '.$conn->error;
						return $status;
					}
				}
			}			
			$conn->close();
		}
		return $status;
	}

	public function submitCollAdd(){
		global $symbUid;
		$instCode = $this->cleanInStr($_POST['institutioncode']);
		$collCode = $this->cleanInStr($_POST['collectioncode']);
		$coleName = $this->cleanInStr($_POST['collectionname']);
		$iid = $_POST['iid'];
		$fullDesc = $this->cleanInStr($_POST['fulldescription']);
		$homepage = $this->cleanInStr($_POST['homepage']);
		$contact = $this->cleanInStr($_POST['contact']);
		$email = $this->cleanInStr($_POST['email']);
		$rights = $this->cleanInStr($_POST['rights']);
		$rightsHolder = $this->cleanInStr($_POST['rightsholder']);
		$accessRights = $this->cleanInStr($_POST['accessrights']);
		$publicEdits = (array_key_exists('publicedits',$_POST)?$_POST['publicedits']:0);
		$guidTarget = (array_key_exists('guidtarget',$_POST)?$_POST['guidtarget']:'');
		$icon = array_key_exists('icon',$_POST)?$this->cleanInStr($_POST['icon']):'';
		$managementType = array_key_exists('managementtype',$_POST)?$this->cleanInStr($_POST['managementtype']):'';
		$collType = array_key_exists('colltype',$_POST)?$this->cleanInStr($_POST['colltype']):'';
		$guid = array_key_exists('collectionguid',$_POST)?$this->cleanInStr($_POST['collectionguid']):'';
		if(!$guid) $guid = UuidFactory::getUuidV4();
		$indUrl = array_key_exists('individualurl',$_POST)?$this->cleanInStr($_POST['individualurl']):'';
		$sortSeq = array_key_exists('sortseq',$_POST)?$_POST['sortseq']:'';
		
		$conn = MySQLiConnectionFactory::getCon("write");
		$sql = 'INSERT INTO omcollections(institutioncode,collectioncode,collectionname,iid,fulldescription,homepage,'.
			'contact,email,latitudedecimal,longitudedecimal,publicedits,guidtarget,rights,rightsholder,accessrights,icon,'.
			'managementtype,colltype,collectionguid,individualurl,sortseq) '.
			'VALUES ("'.$instCode.'",'.
			($collCode?'"'.$collCode.'"':'NULL').',"'.
			$coleName.'",'.($iid?$iid:'NULL').','.
			($fullDesc?'"'.$fullDesc.'"':'NULL').','.
			($homepage?'"'.$homepage.'"':'NULL').','.
			($contact?'"'.$contact.'"':'NULL').','.
			($email?'"'.$email.'"':'NULL').','.
			($_POST['latitudedecimal']?$_POST['latitudedecimal']:'NULL').','.
			($_POST['longitudedecimal']?$_POST['longitudedecimal']:'NULL').','.
			$publicEdits.','.($guidTarget?'"'.$guidTarget.'"':'NULL').','.
			($rights?'"'.$rights.'"':'NULL').','.
			($rightsHolder?'"'.$rightsHolder.'"':'NULL').','.
			($accessRights?'"'.$accessRights.'"':'NULL').','.
			($icon?'"'.$icon.'"':'NULL').','.
			($managementType?'"'.$managementType.'"':'snapshot').','.
			($collType?'"'.$collType.'"':'Preserved Specimens').',"'.
			$guid.'",'.($indUrl?'"'.$indUrl.'"':'NULL').','.
			($sortSeq?$sortSeq:'NULL').') ';
		//echo "<div>$sql</div>";
		$cid = 0;
		if($conn->query($sql)){
			$cid = $conn->insert_id;
			$sql = 'INSERT INTO omcollectionstats(collid,recordcnt,uploadedby) '.
				'VALUES('.$cid.',0,"'.$symbUid.'")';
			$conn->query($sql);
			//Add collection to catagory
			if(isset($_POST['ccpk']) && $_POST['ccpk']){
				$sql = 'INSERT INTO omcollcatlink (ccpk,collid) VALUES('.$_POST['ccpk'].','.$cid.')';
				if(!$conn->query($sql)){
					$status = 'ERROR inserting collection catagory link(2): '.$conn->error.'; SQL: '.$sql;
					return $status;
				}
			}
			$this->collid = $cid;
		}
		else{
			$cid = 'ERROR inserting new collection: '.$conn->error;
		}
		$conn->close();
		return $cid;
	}
	
	public function updateStatistics(){
		set_time_limit(200);
		$writeConn = MySQLiConnectionFactory::getCon("write");

		echo '<li>Updating specimen taxon links... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname '.
			'SET o.TidInterpreted = t.tid '.
			'WHERE o.TidInterpreted IS NULL';
		$writeConn->query($sql);

		echo '<li>Update specimen image taxon links ... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
			'SET i.tid = o.tidinterpreted '.
			'WHERE o.tidinterpreted IS NOT NULL AND (i.tid IS NULL OR o.tidinterpreted <> i.tid)';
		$writeConn->query($sql);

		echo '<li>Updating records with null families... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omoccurrences o INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid '.
			'SET o.family = ts.family '.
			'WHERE ts.taxauthid = 1 AND ts.family <> "" AND ts.family IS NOT NULL AND (o.family IS NULL OR o.family = "")';
		$writeConn->query($sql);
		echo $writeConn->affected_rows.' records updated</li>';

		/*
		echo '<li>Updating records with null author... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.tidinterpreted = t.tid '.
			'SET o.scientificNameAuthorship = t.author '.
			'WHERE o.scientificNameAuthorship IS NULL and t.author is not null';
		$writeConn->query($sql);
		echo $writeConn->affected_rows.' records updated</li>';
		*/
		
		echo '<li>Updating total record count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.recordcnt = (SELECT Count(o.occid) FROM omoccurrences o WHERE (o.collid = '.$this->collid.')) '.
			'WHERE cs.collid = '.$this->collid;
		$writeConn->query($sql);
		echo 'Done!</li> ';
		
		echo '<li>Updating family count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.familycnt = (SELECT COUNT(DISTINCT o.family) '.
			'FROM omoccurrences o WHERE (o.collid = '.$this->collid.')) '.
			'WHERE cs.collid = '.$this->collid;
		$writeConn->query($sql);
		echo 'Done!</li> ';
		
		echo '<li>Updating genus count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.genuscnt = (SELECT COUNT(DISTINCT t.unitname1) '.
			'FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '.
			'WHERE (o.collid = '.$this->collid.') AND t.rankid >= 180) '.
			'WHERE cs.collid = '.$this->collid;
		$writeConn->query($sql);
		echo 'Done!</li>';
		
		echo '<li>Updating species count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.speciescnt = (SELECT count(DISTINCT t.unitname1, t.unitname2) AS spcnt '.
			'FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted '.
			'WHERE (o.collid = '.$this->collid.') AND t.rankid >= 220) '.
			'WHERE cs.collid = '.$this->collid;
		$writeConn->query($sql);
		echo 'Done</li>';
		
		echo '<li>Updating georeference count... ';
		ob_flush();
		flush();
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.georefcnt = (SELECT Count(o.occid) FROM omoccurrences o WHERE (o.DecimalLatitude Is Not Null) '.
			'AND (o.DecimalLongitude Is Not Null) AND (o.CollID = '.$this->collid.')) '.
			'WHERE cs.collid = '.$this->collid;
		$writeConn->query($sql);
		echo 'Done!</li>';
		
		/*
		echo '<li>Updating georeference indexing... ';
		ob_flush();
		flush();
		$sql = 'REPLACE INTO omoccurgeoindex(tid,decimallatitude,decimallongitude) '.
			'SELECT DISTINCT o.tidinterpreted, round(o.decimallatitude,3), round(o.decimallongitude,3) '.
			'FROM omoccurrences o '.
			'WHERE o.tidinterpreted IS NOT NULL AND o.decimallatitude IS NOT NULL '.
			'AND o.decimallongitude IS NOT NULL';
		$writeConn->query($sql);
		
		$sql = 'DELETE FROM omoccurgeoindex WHERE InitialTimestamp < DATE_SUB(CURDATE(), INTERVAL 1 DAY)';
		$writeConn->query($sql);
		echo 'Done!</li>';
		*/
		
		echo '<li>Finished updating collection statistics</li>';
	}

	public function getTaxonCounts($f=''){
		$family = $this->cleanInStr($f);
		$returnArr = Array();
		$sql = '';
		if($family){
			$sql = 'SELECT t.unitname1 as taxon, Count(o.occid) AS cnt '.
				'FROM omoccurrences o INNER JOIN taxa t ON o.tidinterpreted = t.tid '.
				'GROUP BY o.CollID, t.unitname1, o.Family '.
				'HAVING (o.CollID = '.$this->collid.') '.
				'AND (o.Family = "'.$family.'") AND (t.unitname1 != "'.$family.'") '.
				'ORDER BY t.unitname1';
		}
		else{
			$sql = 'SELECT o.family as taxon, Count(*) AS cnt '.
				'FROM omoccurrences o '.
				'GROUP BY o.CollID, o.Family '.
				'HAVING (o.CollID = '.$this->collid.') '.
				'AND (o.Family IS NOT NULL) AND (o.Family <> "") '.
				'ORDER BY o.Family';
		}
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$returnArr[$row->taxon] = $row->cnt;
		}
		$rs->close();
		return $returnArr;
	}

	public function getGeographicCounts($c="",$s=""){
		$returnArr = Array();
		$country = $this->cleanInStr($c);
		$state = $this->cleanInStr($s);
		$sql = '';
		if($country){
			$sql = 'SELECT trim(o.stateprovince) as termstr, Count(*) AS cnt '.
				'FROM omoccurrences o '.
				'GROUP BY o.CollID, o.StateProvince, o.country '.
				'HAVING (o.CollID = '.$this->collid.') AND (o.StateProvince IS NOT NULL) AND (o.StateProvince <> "") '.
				'AND (o.country = "'.$country.'") '.
				'ORDER BY trim(o.StateProvince)';
		}
		elseif($state){
			$sql = 'SELECT trim(o.county) as termstr, Count(*) AS cnt '.
				'FROM omoccurrences o '.
				'GROUP BY o.CollID, o.StateProvince, o.county '.
				'HAVING (o.CollID = '.$this->collid.') AND (o.county IS NOT NULL) AND (o.county <> "") '.
				'AND (o.stateprovince = "'.$state.'") '.
				'ORDER BY trim(o.county)';
		}
		else{
			$sql = 'SELECT trim(o.country) as termstr, Count(*) AS cnt '.
				'FROM omoccurrences o '.
				'GROUP BY o.CollID, o.Country '.
				'HAVING (o.CollID = '.$this->collid.') AND o.Country IS NOT NULL AND o.Country <> "" '.
				'ORDER BY trim(o.Country)';
		}
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$t = $row->termstr;
			if($state){
				$t = trim(str_ireplace(array(' county',' co.',' Counties'),'',$t));
			}
			if($t){
				$returnArr[$t] = $row->cnt;
			}
		}
		$rs->close();
		return $returnArr;
	}
	
	public function getInstitutionArr(){
		$retArr = array();
		$sql = 'SELECT iid,institutionname,institutioncode '.
			'FROM institutions '.
			'ORDER BY Institutioncode ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->iid] = $r->institutioncode.' - '.$r->institutionname;
		}
		return $retArr;
	}
	
	public function getCatagoryArr(){
		$retArr = array();
		$sql = 'SELECT ccpk, catagory '.
			'FROM omcollcatagories '.
			'ORDER BY catagory ';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->ccpk] = $r->catagory;
		}
		return $retArr;
	}

	//Used to index specimen records for particular collection
	public function echoOccurrenceListing($s, $l){
		global $clientRoot;
		$start = $this->cleanInStr($s);
		$limit = $this->cleanInStr($l);
		if(substr($clientRoot,-1) != '/') $clientRoot .= '/';
		if($this->collid){
			//Get count
			$occCnt = 0;
			if(!is_numeric($start)){
				$sql = 'SELECT count(*) AS cnt FROM omoccurrences WHERE collid = '.$this->collid.' ';
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$occCnt = $r->cnt;
				}
				$rs->free();
				if($occCnt < $limit) $start = 0;
			}
			
			if(is_numeric($start)){
				$sql = 'SELECT o.occid, o.catalognumber, o.occurrenceid, o.sciname, o.recordedby, o.recordnumber, g.guid '.
					'FROM omoccurrences o INNER JOIN guidoccurrences g ON o.occid = g.occid '.
					'WHERE collid = '.$this->collid.' '.
					'ORDER BY o.catalognumber,o.occid '.
					'LIMIT '.$start.','.$limit;
				//echo $sql;
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					echo '<div style="margin:5px;">';
					echo '<div><b>Collector:</b> '.$r->recordedby.' '.$r->recordnumber.'</div>';
					echo '<div style="margin-left:10px;"><b>Scientific Name:</b> '.$r->sciname.'</div>';
					echo '<div style="margin-left:10px;"><b>Identifiers:</b> '.$r->catalognumber.' '.$r->occurrenceid.'</div>';
					echo '<div style="margin-left:10px;"><b>GUID:</b> '.$r->guid.'</div>';
					echo '<div style="margin-left:10px;"><a href="'.$clientRoot.'/collections/individual/index.php?occid='.$r->occid.'" target="_blank"><b>Full Details</b></a></div>';
					echo '</div>';
				}
				$rs->free();
			}
			else{
				for($j = 0;$j < $occCnt;$j += $limit){
					$endCnt = (($j+$limit)<$occCnt?($j+$limit):$occCnt);
					echo '<div><a href="collectionindex.php?collid='.$this->collid.'&start='.$j.'&limit='.$limit.'">Records '.($j+1).' - '.$endCnt.'</a></div>';
				}
			}
		}
	}

	public function cleanOutArr(&$arr){
		foreach($arr as $k => $v){
			$arr[$k] = $this->cleanOutStr($v);
		}
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