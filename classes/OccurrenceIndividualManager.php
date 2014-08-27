<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once('Manager.php');

class OccurrenceIndividualManager extends Manager{

	private $occid;
	private $collid;
	private $dbpk;
	private $occArr = array();
	private $metadataArr = array();

	public function __construct() {
		parent::__construct();
	}

	public function __destruct(){
		parent::__destruct();
	}

	public function setOccid($occid){
		if(is_numeric($occid)){
			$this->occid = $occid;
		}
	}

	public function getOccid(){
		return $this->occid;
	}

	public function getCollId($id){
 		if(is_numeric($o)){
			$this->collid = $id;
 		}
	}
	
	public function setDbpk($pk){
		$this->dbpk = $pk;
	}

	private function setMetadata(){
		if($this->collid){
			$sql = 'SELECT institutioncode, collectioncode, collectionname, homepage, individualurl, contact, email, icon, '.
				'publicedits, rights, rightsholder, accessrights, guidtarget '.
				'FROM omcollections WHERE collid = '.$this->collid;
			$rs = $this->conn->query($sql);
			if($rs){
				$this->metadataArr = $rs->fetch_assoc();
				$rs->free();
			}
			else{
				trigger_error('Unable to set collection metadata; '.$this->conn->error,E_USER_ERROR);
			}
		}
	}

	public function getMetadata(){
		return $this->metadataArr;
	}

	public function getOccData($fieldKey = ""){
		if(!$this->occArr) $this->setOccArr();
		if($fieldKey){
			if(array_key_exists($fieldKey,$this->occArr)){
				return $this->occArr($fieldKey);
			}
			return;
		}
		return $this->occArr;
	}

	private function setOccArr(){
		$sql = 'SELECT o.occid, collid, institutioncode AS secondaryinstcode, collectioncode AS secondarycollcode, '.
			'occurrenceid, catalognumber, occurrenceremarks, tidinterpreted, family, sciname, '.
			'scientificnameauthorship, identificationqualifier, identificationremarks, identificationreferences, '.
			'identifiedby, dateidentified, recordedby, associatedcollectors, recordnumber, '.
			'DATE_FORMAT(eventDate,"%d %M %Y") AS eventdate, DATE_FORMAT(MAKEDATE(YEAR(eventDate),enddayofyear),"%d %M %Y") AS eventdateend, '.
			'verbatimeventdate, country, stateprovince, county, locality, '.
			'minimumelevationinmeters, maximumelevationinmeters, verbatimelevation, localitysecurity, localitysecurityreason, '.
			'decimallatitude, decimallongitude, geodeticdatum, coordinateuncertaintyinmeters, verbatimcoordinates, '.
			'georeferenceremarks, verbatimattributes, '.
			'typestatus, dbpk, habitat, substrate, associatedtaxa, reproductivecondition, cultivationstatus, establishmentmeans, '.
			'ownerinstitutioncode, othercatalognumbers, disposition, modified, observeruid, g.guid, municipality '.
			'FROM omoccurrences o LEFT JOIN guidoccurrences g ON o.occid = g.occid ';
		if($this->occid){
			$sql .= 'WHERE (o.occid = '.$this->occid.')';
		}
		elseif($this->collid && $this->dbpk){
			$sql .= 'WHERE (o.collid = '.$this->collid.') AND (o.dbpk = "'.$this->dbpk.'")';
		}
		else{
			trigger_error('Specimen identifier is null or invalid; '.$this->conn->error,E_USER_ERROR);
		}

		if($result = $this->conn->query($sql)){
			if($this->occArr = $result->fetch_assoc()){
				if(!$this->occid) $this->occid = $this->occArr['occid'];
				if(!$this->collid) $this->collid = $this->occArr['collid'];
				$this->setMetadata();
				//Set occurrenceId according to guidsource \
				if($this->metadataArr['guidtarget'] == 'catalogNumber'){
					$this->occArr['occurrenceid'] = $this->occArr['catalognumber'];
				}
				elseif($this->metadataArr['guidtarget'] == 'symbiotaUUID'){
					$this->occArr['occurrenceid'] = $this->occArr['guid'];
				}
	
				if($this->occArr['secondaryinstcode'] && $this->occArr['secondaryinstcode'] != $this->metadataArr['institutioncode']){
					$sqlSec = 'SELECT collectionname, homepage, individualurl, contact, email, icon '.
					'FROM omcollsecondary '.
					'WHERE (collid = '.$this->occArr['collid'].')';
					$rsSec = $this->conn->query($sqlSec);
					if($r = $rsSec->fetch_object()){
						$this->metadataArr['collectionname'] = $r->collectionname;
						$this->metadataArr['homepage'] = $r->homepage;
						$this->metadataArr['individualurl'] = $r->individualurl;
						$this->metadataArr['contact'] = $r->contact;
						$this->metadataArr['email'] = $r->email;
						$this->metadataArr['icon'] = $r->icon;
					}
					$rsSec->close();
				}
				$this->setImages();
				$this->setDeterminations();
				$this->setLoan();
				$this->setExsiccati();
				$result->free();
			}
		}
		else{
			trigger_error('Unable to set occurrence array; '.$this->conn->error,E_USER_ERROR);
		}
	}

	private function setImages(){
		global $imageDomain;
		$sql = 'SELECT imgid, url, thumbnailurl, originalurl, notes, caption FROM images '.
			'WHERE (occid = '.$this->occid.') ORDER BY sortsequence';
		$result = $this->conn->query($sql);
		if($result){
			while($row = $result->fetch_object()){
				$imgId = $row->imgid;
				$url = $row->url;
				$tnUrl = $row->thumbnailurl;
				$lgUrl = $row->originalurl;
				if($imageDomain && substr($url,0,1)=="/"){
					$url = $imageDomain.$url;
					if($lgUrl) $lgUrl = $imageDomain.$lgUrl;
					if($tnUrl) $tnUrl = $imageDomain.$tnUrl;
				}
				$this->occArr['imgs'][$imgId]['url'] = $url;
				$this->occArr['imgs'][$imgId]['tnurl'] = $tnUrl;
				$this->occArr['imgs'][$imgId]['lgurl'] = $lgUrl;
				$this->occArr['imgs'][$imgId]['caption'] = $row->caption;
			}
			$result->free();
		}
		else{
			trigger_error('Unable to set images; '.$this->conn->error,E_USER_WARNING);
		}
	}

	private function setDeterminations(){
		$sql = 'SELECT detid, dateidentified, identifiedby, sciname, scientificnameauthorship, identificationqualifier, '.
			'identificationreferences, identificationremarks '.
			'FROM omoccurdeterminations '.
			'WHERE (occid = '.$this->occid.') AND appliedstatus = 1 '.
			'ORDER BY sortsequence';
		$result = $this->conn->query($sql);
		if($result){
			while($row = $result->fetch_object()){
				$detId = $row->detid;
				$this->occArr['dets'][$detId]['date'] = $row->dateidentified;
				$this->occArr['dets'][$detId]['identifiedby'] = $row->identifiedby;
				$this->occArr['dets'][$detId]['sciname'] = $row->sciname;
				$this->occArr['dets'][$detId]['author'] = $row->scientificnameauthorship;
				$this->occArr['dets'][$detId]['qualifier'] = $row->identificationqualifier;
				$this->occArr['dets'][$detId]['ref'] = $row->identificationreferences;
				$this->occArr['dets'][$detId]['notes'] = $row->identificationremarks;
			}
			$result->free();
		}
		else{
			trigger_error('Unable to setDeterminations; '.$this->conn->error,E_USER_NOTICE);
		}
	}

	private function setLoan(){
		$sql = 'SELECT l.loanIdentifierOwn, i.institutioncode '.
			'FROM omoccurloanslink llink INNER JOIN omoccurloans l ON llink.loanid = l.loanid '.
			'INNER JOIN institutions i ON l.iidBorrower = i.iid '.
			'WHERE (llink.occid = '.$this->occid.') AND llink.returndate IS NULL';
		$result = $this->conn->query($sql);
		if($result){
			while($row = $result->fetch_object()){
				$this->occArr['loan']['identifier'] = $row->loanIdentifierOwn;
				$this->occArr['loan']['code'] = $row->institutioncode;
			}
			$result->free();
		}
		else{
			trigger_error('Unable to set loan info; '.$this->conn->error,E_USER_WARNING);
		}
	}

	private function setExsiccati(){
		$sql = 'SELECT t.title, t.editor, n.omenid, n.exsnumber '.
			'FROM omexsiccatititles t INNER JOIN omexsiccatinumbers n ON t.ometid = n.ometid '.
			'INNER JOIN omexsiccatiocclink l ON n.omenid = l.omenid '.
			'WHERE (l.occid = '.$this->occid.')';
		$rs = $this->conn->query($sql);
		if($rs){
			while($r = $rs->fetch_object()){
				$this->occArr['exs']['title'] = $r->title;
				$this->occArr['exs']['omenid'] = $r->omenid;
				$this->occArr['exs']['exsnumber'] = $r->exsnumber;
			}
			$rs->free();
		}
		else{
			trigger_error('Unable to set exsiccati info; '.$this->conn->error,E_USER_WARNING);
		}
	}

	public function getDuplicateArr(){
		$retArr = array();
		 $sql = 'SELECT d.occid, c.institutioncode, c.collectioncode, c.collectionname, o.catalognumber, '.
			'o.occurrenceid, o.sciname, o.identifiedby, o.dateidentified, d.notes '.
			'FROM omoccurduplicatelink d INNER JOIN omoccurrences o ON d.occid = o.occid '.
			'INNER JOIN omcollections c ON o.collid = c.collid '.
			'INNER JOIN omoccurduplicatelink d2 ON d.duplicateid = d2.duplicateid '.
			'WHERE (d2.occid = '.$this->occid.') AND (o.occid <> '.$this->occid.') ';
		/*
		$sql = 'SELECT d.occid, c.institutioncode, c.collectioncode, c.collectionname, o.catalognumber, o.occurrenceid, o.sciname, '.
			'o.identifiedby, o.dateidentified, d.notes '.
			'FROM omoccurduplicatelink d INNER JOIN omoccurrences o ON d.occid = o.occid '.
			'INNER JOIN omcollections c ON o.collid = c.collid '.
			'WHERE d.duplicateid IN(SELECT duplicateid FROM omoccurduplicatelink WHERE occid = '.$this->occid.') '.
			'AND (o.occid <> '.$this->occid.')';
		*/
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->occid]['instcode'] = $r->institutioncode;
				$retArr[$r->occid]['collcode'] = $r->collectioncode;
				$retArr[$r->occid]['collname'] = $r->collectionname;
				$retArr[$r->occid]['catnum'] = $r->catalognumber;
				$retArr[$r->occid]['occurrenceid'] = $r->occurrenceid;
				$retArr[$r->occid]['sciname'] = $r->sciname;
				$retArr[$r->occid]['identifiedby'] = $r->identifiedby;
				$retArr[$r->occid]['dateidentified'] = $r->dateidentified;
				$retArr[$r->occid]['notes'] = $r->notes;
			}
		}
		else{
			trigger_error('Unable to get duplicate records'.$this->conn->error);
		}
		return $retArr;
	}

	public function getCommentArr($isEditor){
		$retArr = array();
		//return $retArr;
		$sql = 'SELECT c.comid, c.comment, u.username, c.reviewstatus, c.initialtimestamp '.
			'FROM omoccurcomments c INNER JOIN userlogin u ON c.uid = u.uid '.
			'WHERE (c.occid = '.$this->occid.') ';
		if(!$isEditor) $sql .= 'AND c.reviewstatus = 1 ';
		$sql .= 'ORDER BY c.initialtimestamp';
		//echo $sql.'<br/><br/>';
		$result = $this->conn->query($sql);
		if($result){
			while($row = $result->fetch_object()){
				$comId = $row->comid;
				$retArr[$comId]['comment'] = $row->comment;
				$retArr[$comId]['reviewstatus'] = $row->reviewstatus;
				$retArr[$comId]['username'] = $row->username;
				$retArr[$comId]['initialtimestamp'] = $row->initialtimestamp;
			}
			$result->free();
		}
		else{
			trigger_error('Unable to set comments; '.$this->conn->error,E_USER_WARNING);
		}
		return $retArr;
	}

	public function addComment($commentStr){
		$status = false;
		if(isset($GLOBALS['SYMB_UID']) && $GLOBALS['SYMB_UID']){
	 		$con = MySQLiConnectionFactory::getCon("write");
			$sql = 'INSERT INTO omoccurcomments(occid,comment,uid,reviewstatus) '.
				'VALUES('.$this->occid.',"'.$this->cleanInStr($commentStr).'",'.$GLOBALS['SYMB_UID'].',1)';
			//echo 'sql: '.$sql;
			if($con->query($sql)){
				$status = true;
			}
			else{
				$status = false;
				$this->errorMessage = 'ERROR adding comment: '.$con->error;
			}
			$con->close();
		}
		return $status;
	}
	
	public function deleteComment($comId){
		$status = true;
		$con = MySQLiConnectionFactory::getCon("write");
		if(is_numeric($comId)){
			$sql = 'DELETE FROM omoccurcomments WHERE comid = '.$comId;
			if(!$con->query($sql)){
				$status = false;
				$this->errorMessage = 'ERROR deleting comment: '.$con->error;
			}
		}
		$con->close();
		return $status;
	}

	public function reportComment($repComId){
		$status = true;
		if(array_key_exists('adminEmail',$GLOBALS)){
			//Set Review status to supress
 			$con = MySQLiConnectionFactory::getCon("write");
			if(!$con->query('UPDATE omoccurcomments SET reviewstatus = 0 WHERE comid = '.$repComId)){
				$this->errorMessage = 'ERROR changing comment status to needing review, Err msg: '.$this->conn->error;
				$status = false;
			}
			$con->close();
			
			//Email to portal admin
			$emailAddr = $GLOBALS['adminEmail'];
			$comUrl = 'http://'.$_SERVER['SERVER_NAME'].$GLOBALS['clientRoot'].'/collections/individual/index.php?tabindex=2&occid='.$this->occid;
			$subject = $GLOBALS['defaultTitle'].' inappropriate comment reported<br/>';
			$bodyStr = 'The following comment has been recorted as inappropriate:<br/> '.
			'<a href="'.$comUrl.'">'.$comUrl.'</a>';
			$headerStr = "MIME-Version: 1.0 \r\n".
				"Content-type: text/html \r\n".
				"To: ".$emailAddr." \r\n";
				$headerStr .= "From: Admin <".$emailAddr."> \r\n";
			if(!mail($emailAddr,$subject,$bodyStr,$headerStr)){
				$this->errorMessage = 'ERROR sending email to portal manager, error unknown';
				$status = false;
			}
		}
		else{
			$this->errorMessage = 'ERROR: Portal admin email not defined in central configuration file ';
			$status = false;
		}
		return $status;
	}
	
	public function makeCommentPublic($comId){
		$status = true;
		$con = MySQLiConnectionFactory::getCon("write");
		if(!$con->query('UPDATE omoccurcomments SET reviewstatus = 1 WHERE comid = '.$comId)){
			$this->errorMessage = 'ERROR making comment public, err msg: '.$con->error;
			$status = false;
		}
		$con->close();
		return $status;
	}

	public function getGeneticArr(){
		$retArr = array();
		if($this->occid){
 			$con = MySQLiConnectionFactory::getCon("write");
			$sql = 'SELECT idoccurgenetic, identifier, resourcename, locus, resourceurl, notes '.
				'FROM omoccurgenetic '.
				'WHERE occid = '.$this->occid;
			$result = $this->conn->query($sql);
			if($result){
				while($r = $result->fetch_object()){
					$retArr[$r->idoccurgenetic]['id'] = $r->identifier;
					$retArr[$r->idoccurgenetic]['name'] = $r->resourcename;
					$retArr[$r->idoccurgenetic]['locus'] = $r->locus;
					$retArr[$r->idoccurgenetic]['resourceurl'] = $r->resourceurl;
					$retArr[$r->idoccurgenetic]['notes'] = $r->notes;
				}
				$result->free();
			}
			else{
				trigger_error('Unable to get genetic data; '.$this->conn->error,E_USER_WARNING);
			}
		}
		return $retArr;
	}
	
	public function getEditArr(){
		$retArr = array();
		$sql = 'SELECT e.ocedid, e.fieldname, e.fieldvalueold, e.fieldvaluenew, e.reviewstatus, e.appliedstatus, '.
			'CONCAT_WS(", ",u.lastname,u.firstname) as editor, e.initialtimestamp '.
			'FROM omoccuredits e INNER JOIN users u ON e.uid = u.uid '.
			'WHERE e.occid = '.$this->occid.' ORDER BY e.initialtimestamp DESC ';
		//echo $sql;
		$result = $this->conn->query($sql);
		if($result){
			$cnt = 0;
			while($r = $result->fetch_object()){
				$k = substr($r->initialtimestamp,0,16);
				if(!isset($retArr[$k]['editor'])){
					$retArr[$k]['editor'] = $r->editor;
					$retArr[$k]['ts'] = $r->initialtimestamp;
				}
				$retArr[$k][$cnt]['fieldname'] = $r->fieldname;
				$retArr[$k][$cnt]['old'] = $r->fieldvalueold;
				$retArr[$k][$cnt]['new'] = $r->fieldvaluenew;
				$retArr[$k][$cnt]['reviewstatus'] = $r->reviewstatus;
				$retArr[$k][$cnt]['appliedstatus'] = $r->appliedstatus;
				$cnt++;
			}
			$result->free();
		}
		else{
			trigger_error('Unable to get edits; '.$this->conn->error,E_USER_WARNING);
		}
		return $retArr;
	}
	
	public function getVoucherChecklists(){
		global $IS_ADMIN, $userRights;
		$returnArr = Array();
		$sql = 'SELECT c.name, c.clid, c.access, v.notes '.
			'FROM fmchecklists c INNER JOIN fmvouchers v ON c.clid = v.clid '.
			'WHERE v.occid = '.$this->occid.' ';
		if(array_key_exists("ClAdmin",$userRights)){
			$sql .= 'AND (c.access = "public" OR c.clid IN('.implode(',',$userRights['ClAdmin']).')) ';
		}
		else{
			$sql .= 'AND (c.access = "public") ';
		}
		$sql .= 'ORDER BY c.name';
		//echo $sql;
		$result = $this->conn->query($sql);
		if($result){
			while($row = $result->fetch_object()){
				$nameStr = $row->name;
				if($row->access == 'private') $nameStr .= ' (private status)';
				$returnArr[$row->clid] = $nameStr;
			}
			$result->free();
		}
		else{
			trigger_error('Unable to get checklist data; '.$this->conn->error,E_USER_WARNING);
		}
		return $returnArr;
	}

	public function deleteVoucher($occid,$clid){
		$status = true;
		if(is_numeric($occid) && is_numeric($clid)){
			$sql = 'DELETE FROM fmvouchers WHERE (occid = '.$occid.') AND (clid = '.$clid.') ';
 			$con = MySQLiConnectionFactory::getCon("write");
			if(!$con->query($sql)){
				$this->errorMessage = 'ERROR loading '.$con->error;
				$status = false;
			}
			if(!($con === null)) $con->close();
		}
		return $status;
	}

	public function getChecklists($clidExcludeArr){
		global $userRights;
		if(!array_key_exists("ClAdmin",$userRights)) return null;
		$returnArr = Array();
		$sql = 'SELECT name, clid '.
			'FROM fmchecklists WHERE clid IN('.implode(",",array_diff($userRights["ClAdmin"],$clidExcludeArr)).') '.
			'ORDER BY Name';
		//echo $sql;
		if($result = $this->conn->query($sql)){
			while($row = $result->fetch_object()){
				$returnArr[$row->clid] = $row->name;
			}
			$result->free();
		}
		else{
			trigger_error('Unable to get checklist data; '.$this->conn->error,E_USER_WARNING);
		}
		return $returnArr;
	}

	public function checkArchive(){
		$retArr = array();
		$sql = 'SELECT archiveobj, notes '.
			'FROM guidoccurrences '.
			'WHERE occid = '.$this->occid.' AND archiveobj IS NOT NULL ';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			if($r = $rs->fetch_object()){
				$retArr['obj'] = json_decode($r->archiveobj,true); 
				$retArr['notes'] = $r->notes;
			}
			$rs->free();
		}
		else{
			trigger_error('ERROR checking archive: '.$this->conn->error,E_USER_WARNING);
		}
		if(!$retArr){
			$sql = 'SELECT archiveobj, notes '.
				'FROM guidoccurrences '.
				'WHERE occid IS NULL AND archiveobj LIKE \'%"occid":"'.$this->occid.'"%\'';
			//echo $sql;
			if($rs = $this->conn->query($sql)){
				if($r = $rs->fetch_object()){
					$retArr['obj'] = json_decode($r->archiveobj,true); 
					$retArr['notes'] = $r->notes;
				}
				$rs->free();
			}
			else{
				trigger_error('ERROR checking archive (step2): '.$this->conn->error,E_USER_WARNING);
			}
		}
		return $retArr;
	}

	/*
	 * Return: 0 = false, 2 = full editor, 3 = taxon editor, but not for this collection
	 */
	public function isTaxonomicEditor(){
		$isEditor = 0;
		
		//Grab taxonomic node id and geographic scopes
		$editTidArr = array();
		$sqlut = 'SELECT idusertaxonomy, tid, geographicscope '.
			'FROM usertaxonomy '.
			'WHERE editorstatus = "OccurrenceEditor" AND uid = '.$GLOBALS['SYMB_UID'];
		//echo $sqlut;
		$rsut = $this->conn->query($sqlut);
		while($rut = $rsut->fetch_object()){
			//Is a taxonomic editor, but not explicitly approved for this collection
			$editTidArr[$rut->tid] = $rut->geographicscope;
		}
		$rsut->free();
		
		//Get relevant tids for active occurrence
		if($editTidArr){
			$occTidArr = array();
			$sql = '';
			if($this->occArr['tidinterpreted']){
				$occTidArr[] = $this->occArr['tidinterpreted'];
				$sql = 'SELECT hierarchystr, parenttid '.
					'FROM taxstatus '.
					'WHERE taxauthid = 1 AND (tid = '.$this->occArr['tidinterpreted'].')';
			}
			elseif($this->occArr['sciname'] || $this->occArr['family']){
				//Get all relevant tids within the taxonomy hierarchy
				$sql = 'SELECT DISTINCT ts.hierarchystr, ts.parenttid '.
					'FROM taxstatus ts INNER JOIN taxa t ON ts.tid = t.tid '.
					'WHERE ts.taxauthid = 1 ';
				if($this->occArr['sciname']){
					//Try to isolate genus
					$taxon = $this->occArr['sciname'];
					$tok = explode(' ',$this->occArr['sciname']);
					if(count($tok) > 1){
						if(strlen($tok[0]) > 2) $taxon = $tok[0];
					}
					$sql .= 'AND (t.sciname = "'.$this->cleanInStr($taxon).'") ';
				}
				elseif($this->occArr['family']){
					$sql .= 'AND (t.sciname = "'.$this->cleanInStr($this->occArr['family']).'") ';
				}
			}
			if($sql){
				$rs2 = $this->conn->query($sql);
				while($r2 = $rs2->fetch_object()){
					$occTidArr[] = $r2->parenttid;
					$occTidArr = array_merge($occTidArr,explode(',',$r2->hierarchystr));
				}
				$rs2->free();
			}
			if($occTidArr){
				if(array_intersect(array_keys($editTidArr),$occTidArr)){
					$isEditor = 3;
					//TODO: check to see if specimen is within geographic scope
				}					
			}
		}
		return $isEditor;
	}
}
?>