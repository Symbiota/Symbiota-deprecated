<?php

include_once($serverRoot.'/config/dbconnection.php');

class OccurrenceIndividualManager {

	private $conn;
	private $occId;
    private $collId;
    private $dbpk;
	private $occArr = array();
	private $metadataArr = array();

 	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon("readonly");
 	}
 	
 	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
   
	public function setOccid($occid){
		if(is_numeric($occid)){
			$this->occId = $occid;
		}
	}

	public function getOccid(){
		return $this->occId;
	}

	public function getCollId($id){
 		if(is_numeric($o)){
			$this->collId = $id;
 		}
	}
	
	public function setDbpk($pk){
		$this->dbpk = $pk;
	}
	
	private function setMetadata(){
    	$sql = 'SELECT institutioncode, collectioncode, collectionname, homepage, individualurl, contact, email, icon, '.
    		'publicedits, rights, rightsholder, accessrights '.
			'FROM omcollections WHERE collid = '.$this->collId;
		$rs = $this->conn->query($sql);
    	if($rs){
			$this->metadataArr = $rs->fetch_assoc();
			$rs->free();
    	}
		else{
			trigger_error('Unable to set collection metadata; '.$this->conn->error,E_USER_ERROR);
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
    	$sql = 'SELECT occid, collid, institutioncode AS secondaryinstcode, collectioncode AS secondarycollcode, '.
    		'catalognumber, occurrenceremarks, tidinterpreted, family, sciname, '.
    		'scientificnameauthorship, identificationqualifier, identificationremarks, identificationreferences, '.
			'identifiedby, dateidentified, recordedby, associatedcollectors, recordnumber, '.
			'DATE_FORMAT(eventDate,"%d %M %Y") AS eventdate, DATE_FORMAT(MAKEDATE(YEAR(eventDate),enddayofyear),"%d %M %Y") AS eventdateend, '.
    		'verbatimeventdate, country, stateprovince, county, locality, '.
    		'minimumelevationinmeters, maximumelevationinmeters, verbatimelevation, localitysecurity, localitysecurityreason, '.
			'decimallatitude, decimallongitude, geodeticdatum, coordinateuncertaintyinmeters, verbatimcoordinates, '.
			'georeferenceremarks, verbatimattributes, '.
			'typestatus, dbpk, habitat, substrate, associatedtaxa, reproductivecondition, cultivationstatus, establishmentmeans, '.
			'ownerinstitutioncode, othercatalognumbers, disposition, duplicateid, modified, observeruid '.
			'FROM omoccurrences ';
		if($this->occId){
			$sql .= 'WHERE (occid = '.$this->occId.')';
		}
		elseif($this->collId && $this->dbpk){
			$sql .= 'WHERE (collid = '.$this->collId.') AND (dbpk = "'.$this->dbpk.'")';
		}
		else{
			trigger_error('Specimen identifier is null or invalid; '.$this->conn->error,E_USER_ERROR);
		}

		$result = $this->conn->query($sql);
		if($result){
			$this->occArr = $result->fetch_assoc();
			if(!$this->occId) $this->occId = $this->occArr['occid'];
			if(!$this->collId) $this->collId = $this->occArr['collid'];
			$this->setMetadata();
			
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
			$result->free();
		}
		else{
			trigger_error('Unable to set occurrence array; '.$this->conn->error,E_USER_ERROR);
		}
    }

    private function setImages(){
    	global $imageDomain;
        $sql = 'SELECT imgid, url, thumbnailurl, originalurl, notes, caption FROM images '.
			'WHERE (occid = '.$this->occId.') ORDER BY sortsequence';
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
			'WHERE (occid = '.$this->occId.') ORDER BY sortsequence';
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
			'WHERE (llink.occid = '.$this->occId.') AND llink.returndate IS NULL';
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

	public function getDuplicateArr($dupeId){
		$retArr = array();
		if($dupeId){
			$sql = 'SELECT duplicateid, projidentifier, projname '.
				'FROM omoccurduplicates '.
				'WHERE duplicateid = '.$dupeId;
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr[$duplicateid] = $r->projname.' ('.$r->projidentifier.')';
				}
			}
			else{
				trigger_error('anable to get duplicate records'.$this->conn->error);
			}
		}
		return $retArr;
	}

	public function getCommentArr($isEditor){
		$retArr = array();
		//return $retArr;
		$sql = 'SELECT c.comid, c.comment, u.username, c.reviewstatus, c.initialtimestamp '.
			'FROM omoccurcomments c INNER JOIN userlogin u ON c.uid = u.uid '.
			'WHERE (c.occid = '.$this->occId.') ';
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
		global $symbUid;
		$statusStr = '';
		$sql = 'INSERT INTO omoccurcomments(occid,comment,uid,reviewstatus) '.
			'VALUES('.$this->occId.',"'.$this->cleanInStr($commentStr).'",'.$symbUid.',1)';
		//echo 'sql: '.$sql;
		$statudStr = $this->conn->query($sql);
		return $statudStr;
	}
	
	public function deleteComment($comId){
		$statusStr = '';
		if(is_numeric($comId)){
			$sql = 'DELETE FROM omoccurcomments WHERE comid = '.$comId;
			$statudStr = $this->conn->query($sql);
		}
		return $statudStr;
	}
	
	public function reportComment($repComId){
		if(array_key_exists('adminEmail',$GLOBALS)){
			//Get comment 
			$sql = 'SELECT c.comment, u.username, c.initialtimestamp '.
				'FROM omoccurcomments c INNER JOIN userlogin u ON c.uid = u.uid '.
				'WHERE c.comid = '.$repComId;
	        $result = $this->conn->query($sql);
			if($result){
				if($row = $result->fetch_object()){
					$retArr['comment'] = $row->comment;
					$retArr['username'] = $row->username;
					$retArr['initialtimestamp'] = $row->initialtimestamp;
				}
				$result->free();
			}
	        else{
	        	trigger_error('Unable to set comments; '.$this->conn->error,E_USER_WARNING);
	        }
			//Set Review status to supress
			$this->conn->query('UPDATE omoccurcomments SET reviewstatus = 0 WHERE comid = '.$repComId);
			
			//Email to portal admin
			$emailAddr = $GLOBALS['adminEmail'];
			$comUrl = 'http://'.$_SERVER['SERVER_NAME'].$GLOBALS['clientRoot'];
			$subject = $GLOBALS['defaultTitle'].' inappropriate comment reported<br/>';
			$bodyStr = 'The following comment has been recorted as inappropriate:<br/> '.
			'<a href="'.$comUrl.'">'.$comUrl.'</a>';
			$headerStr = "MIME-Version: 1.0 \r\n".
				"Content-type: text/html \r\n".
				"To: ".$emailAddr." \r\n";
				$headerStr .= "From: Admin <".$emailAddr."> \r\n";
		}
		mail($emailAddr,$subject,$bodyStr,$headerStr);
	}
	
	public function makeCommentPublic($comId){
		$this->conn->query('UPDATE omoccurcomments SET reviewstatus = 1 WHERE comid = '.$comId);
	}
	
	public function getGeneticArr(){
		$retArr = array();

		return $retArr;
	}

	public function getEditArr(){
		$retArr = array();
		return $retArr;
		$sql = 'SELECT e.ocedid, e.fieldname, e.fieldvalueold, e.fieldvaluenew, e.reviewstatus, e.appliedstatus, '.
			'CONCAT_WS(", ",u.lastname,u.firstname) as editor, e.initialtimestamp '.
			'FROM omoccuredits e INNER JOIN users u ON e.uid = u.uid '.
			'WHERE e.occid = '.$this->occId.' ORDER BY e.initialtimestamp DESC ';
		//echo $sql;
		$result = $this->conn->query($sql);
		if($result){
			while($r = $result->fetch_object()){
				$retArr[$r->ocedid]['fieldname'] = $r->fieldname;
				$retArr[$r->ocedid]['fieldvalueold'] = $r->fieldvalueold;
				$retArr[$r->ocedid]['fieldvaluenew'] = $r->fieldvaluenew;
				$retArr[$r->ocedid]['reviewstatus'] = $r->reviewstatus;
				$retArr[$r->ocedid]['appliedstatus'] = $r->appliedstatus;
				$retArr[$r->ocedid]['editor'] = $r->editor;
				$retArr[$r->ocedid]['initialtimestamp'] = $r->initialtimestamp;
			}
			$result->free();
        }
        else{
        	trigger_error('Unable to get edits; '.$this->conn->error,E_USER_WARNING);
        }
		return $retArr;
	}
	
	public function getVoucherChecklists(){
		$returnArr = Array();
		$sql = 'SELECT c.name, c.clid, v.notes '.
			'FROM fmchecklists c INNER JOIN fmvouchers v ON c.clid = v.clid '.
			'WHERE v.occid = '.$this->occId.' ORDER BY c.name';
		//echo $sql;
		$result = $this->conn->query($sql);
		if($result){
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

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>