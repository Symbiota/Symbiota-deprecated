<?php
class SpecProcNlpProfiles{

	private $conn;
	private $collid;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
		if(!($this->conn === false)) $this->conn->close();
	}

	public function getProfileArr($spNlpId=0){
		$retArr = array();
		$sql = 'SELECT spnlpid, title, sqlfrag, patternmatch, notes '.
			'FROM specprocnlp ';
		if($spNlpId) $sql .= 'WHERE spnlpid = '.$spNlpId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->spnlpid]['title'] = $r->title;
			$retArr[$r->spnlpid]['sqlfrag'] = $r->sqlfrag;
			$retArr[$r->spnlpid]['patternmatch'] = $r->patternmatch;
			$retArr[$r->spnlpid]['notes'] = $r->notes;
		}
		$rs->close();
		return $retArr;
	}
	
	public function getProfileFragments($spNlpId){
		$retArr = array();
		$sql = 'SELECT spnlpfragid, fieldname, patternmatch, notes, sortseq '.
			'FROM specprocnlpfrag '.
			'WHERE spnlpid = '.$spNlpId.' '.
			'ORDER BY sortseq';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->spnlpfragid]['fieldname'] = $r->fieldname;
			$retArr[$r->spnlpfragid]['patternmatch'] = $r->patternmatch;
			$retArr[$r->spnlpfragid]['notes'] = $r->notes;
		}
		$rs->close();
		return $retArr;
	}

	//Manage profiles
	public function addProfile($postArr){
		$status = '';
		$sql = 'INSERT INTO specprocnlp(title,sqlfrag,patternmatch,notes,collid) '.
			'VALUES("'.$this->cleanInStr($postArr['title']).'","'.$this->cleanInStr($postArr['sqlfrag']).'","'.
			$this->cleanInStr($postArr['patternmatch']).'","'.$this->cleanInStr($postArr['notes']).'",'.$postArr['collid'].')';
		if(!$this->conn->query($sql)){
			$status = 'ERROR: unable to add NLP profile; ERR: '.$this->conn->error;
		}
		return $status;
	}
	
	public function editProfile($postArr){
		$status = '';
		$sql = 'UPDATE specprocnlp SET title = "'.$this->cleanInStr($postArr['title']).'",sqlfrag = "'.$this->cleanInStr($postArr['sqlfrag']).
		'",patternmatch = "'.$this->cleanInStr($postArr['patternmatch']).'",notes = "'.$this->cleanInStr($postArr['notes']).'" '.
			'WHERE spnlpid = '.$postArr['spnlpid'].'  ';
		if(!$this->conn->query($sql)){
			$status = 'ERROR: unable to edit NLP profile; ERR: '.$this->conn->error;
		}
		return $status;
	}
	
	public function deleteProfile($spnlpid){
		$status = '';
		$sql = 'DELETE FROM specprocnlp WHERE spnlpid = '.$spnlpid.'  ';
		if(!$this->conn->query($sql)){
			$status = 'ERROR: unable to delete NLP profile; ERR: '.$this->conn->error;
		}
		return $status;
	}

	public function addProfileFrag($postArr){
		$status = '';
		$sql = 'INSERT INTO specprocnlpfrag(spnlpid,fieldname,patternmatch,notes,sortseq) '.
			'VALUES("'.$this->cleanInStr($postArr['spnlpid']).'","'.$this->cleanInStr($postArr['fieldname']).'","'.
			$this->cleanInStr($postArr['patternmatch']).'","'.$this->cleanInStr($postArr['notes']).'",'.$postArr['sortseq'].')';
		if(!$this->conn->query($sql)){
			$status = 'ERROR: unable to add NLP fragment; ERR: '.$this->conn->error;
		}
		return $status;
	}
	
	public function editProfileFrag($postArr){
		$status = '';
		$sql = 'UPDATE specprocnlpfrag SET fieldname = "'.$postArr['fieldname'].'",patternmatch = "'.$this->cleanInStr($postArr['patternmatch']).
			'",notes = "'.$this->cleanInStr($postArr['notes']).'",sortseq = '.$postArr['sortseq'].' '.
			'WHERE spnlpfragid = '.$postArr['spnlpfragid'].'  ';
		if(!$this->conn->query($sql)){
			$status = 'ERROR: unable to edit NLP fragment; ERR: '.$this->conn->error;
		}
		return $status;
	}
	
	public function deleteProfileFrag($spnlpfragid){
		$status = '';
		$sql = 'DELETE FROM specprocnlpfrag WHERE spnlpfragid = '.$spnlpfragid.'  ';
		if(!$this->conn->query($sql)){
			$status = 'ERROR: unable to delete NLP fragment; ERR: '.$this->conn->error;
		}
		return $status;
	}
}
?> 