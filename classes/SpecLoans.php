<?php
include_once($serverRoot.'/config/dbconnection.php');

class SpecLoans{

	private $conn;
	private $collId = 0;
	private $loanId = 0;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	function __destruct(){
 		if($this->conn) $this->conn->close();
	}

	public function getLoanList($searchTerm,$displayAll){
		$retArr = array();
		$sql = 'SELECT loanid, loanidentifier, dateclosed '.
			'FROM omoccurloans '.
			'WHERE collid = '.$this->collId.' ';
		if($searchTerm){
			$sql .= 'AND loanidentifier LIKE "%'.$searchTerm.'%" ';
		}
		if(!$displayAll){
			$sql .= 'AND ISNULL(dateclosed) ';
		}
		$sql .= 'ORDER BY loanIdentifier';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->loanid]['loanidentifier'] = $r->loanidentifier;
				$retArr[$r->loanid]['dateclosed'] = $r->dateclosed;
			}
			$rs->close();
		}
		return $retArr;
	} 

	public function getLoanDetails($loanId){
		$retArr = array();
		$sql = 'SELECT loanid, loanidentifier, iidreceiver, datesent, totalboxes, '.
			'shippingmethod, datedue, datereturned, dateclosed, forwhom, description, '.
			'notes, createdby, processedby, processedbyreturn '.
			'FROM omoccurloans '.
			'WHERE loanid = '.$loanId;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr['loanidentifier'] = $r->loanidentifier;
				$retArr['iidreceiver'] = $r->iidreceiver;
				$retArr['datesent'] = $r->datesent;
				$retArr['totalboxes'] = $r->totalboxes;
				$retArr['shippingmethod'] = $r->shippingmethod;
				$retArr['datedue'] = $r->datedue;
				$retArr['datereturned'] = $r->datereturned;
				$retArr['dateclosed'] = $r->dateclosed;
				$retArr['forwhom'] = $r->forwhom;
				$retArr['description'] = $r->description;
				$retArr['notes'] = $r->notes;
				$retArr['createdby'] = $r->createdby;
				$retArr['processedby'] = $r->processedby;
				$retArr['processedbyreturn'] = $r->processedbyreturn;
			}
			$rs->close();
		}
		return $retArr;
	} 

	public function editLoan($pArr){
		$statusStr = '';
		$loanId = $pArr['loanid'];
		if(is_numeric($loanId)){
			$sql = '';
			foreach($pArr as $k => $v){
				if($k != 'formsubmit' && $k != 'loanid' && $k != 'collid'){
					$sql .= ','.$k.'='.($v?'"'.$this->cleanString($v).'"':'NULL');
				}
			}
			$sql = 'UPDATE omoccurloans SET '.substr($sql,1).' WHERE (loanid = '.$loanId.')';
			if($this->conn->query($sql)){
				$statusStr = 'SUCCESS: information saved';
			}
			else{
				$statusStr = 'ERROR: Editing of loan failed: '.$this->conn->error.'<br/>';
				$statusStr .= 'SQL: '.$sql;
			}
		}
		return $statusStr;
	}
	
	public function createNewLoan($pArr){
		$statusStr = '';
		$sql = 'INSERT INTO omoccurloans(collid,loanidentifier,iidreceiver,createdby) '.
			'VALUES('.$this->collId.',"'.$this->cleanString($pArr['loanidentifier']).'","'.$this->cleanString($pArr['reqinstitution']).'",
			"'.$this->cleanString($pArr['createdby']).'")';
		//echo $sql;
		if($this->conn->query($sql)){
			$this->loanId = $this->conn->insert_id;
		}
		else{
			$statusStr = 'ERROR: Creation of new loan failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}
	
	public function getSpecList($loanId){
		$retArr = array();
		$sql = 'SELECT o.catalognumber, o.sciname '.
			'FROM omoccurloanslink AS l LEFT OUTER JOIN omoccurrences AS o ON l.occid = o.occid '.
			'WHERE l.loanid = '.$loanId;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->loanid]['catalognumber'] = $r->catalognumber;
				$retArr[$r->loanid]['sciname'] = $r->sciname;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function getLoansIn(){
		$retArr = array();
		$sql = 'SELECT loanid, IFNULL(loanIdentifierReceiver, loanIdentifier) AS loanidentifier, datesent, dateclosed, '. 
			'forwhom, description, datedue '.
			'FROM omoccurloans l INNER JOIN institutions i ON l.iidreceiving = i.iid '.
			'WHERE (i.collid = '.$this->collId.')';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr['loanid']['loanidentifier'] = $r->loanidentifier;
				$retArr['loanid']['datesent'] = $r->datesent;
				$retArr['loanid']['dateclosed'] = $r->dateclosed;
				$retArr['loanid']['forwhom'] = $r->forwhom;
				$retArr['loanid']['description'] = $r->description;
				$retArr['loanid']['datedue'] = $r->datedue;
			}
			$rs->close();
		}
		return $retArr;
	}

	//General look up functions
	public function getInstitutionArr(){
		$retArr = array();
		$sql = 'SELECT i.iid, IFNULL(c.institutioncode,i.institutioncode) as institutioncode, '. 
			'i.institutionname '. 
			'FROM institutions i LEFT JOIN (SELECT iid, institutioncode, collectioncode, collectionname '. 
			'FROM omcollections WHERE colltype = "Preserved Specimens") c ON i.iid = c.iid '. 
			'ORDER BY i.institutioncode,c.institutioncode,c.collectionname,i.institutionname';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->iid] = $r->institutioncode.' - '.$r->institutionname;
			}
		}
		return $retArr;
	} 
	
	//Get and set functions 
	public function setCollId($c){
		$this->collId = $c;
	}
	
	public function getLoanId(){
		return $this->loanId;
	}
	
	protected function cleanString($inStr){
		$retStr = trim($inStr);
		$retStr = $this->conn->real_escape_string($retStr);
		return $retStr;
	}
}
?>