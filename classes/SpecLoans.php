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
			$sql .= 'AND dateclosed IS NOT NULL ';
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
		$sql = 'SELECT '.
			'FROM omoccurloans '.
			'WHERE loanid = '.$loanId;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr['loanidentifier'] = $r->loanidentifier;
				//$retArr[''] = $r->;
			}
			$rs->close();
		}
		return $retArr;
	} 

	public function editLoan($pArr){
		$statusStr = '';
		$sql = '';
		foreach($pArr as $k => $v){
			$sql .= ','.$k.'="'.$v.'"';
		}
		$sql = 'UPDATE omoccurloans SET '.substr($sql,1).' WHERE loanid = '.$loanId;
		if(!$this->conn->query($sql)){
			$statusStr = 'ERROR: Editing of loan failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
	}
	
	public function createNewLoan($pArr){
		$statusStr = '';
		$sql = 'INSERT INTO omoccurloans() VALUES()';
		if($this->conn->query($sql)){
			$this->loanOutId = $this->conn->insert_id;
		}
		else{
			$statusStr = 'ERROR: Creation of new loan failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
	}
	
	public function getLoansIn(){
		$retArr = array();
		$sql = 'SELECT loanid, IFNULL(loanIdentifierReceiver, loanIdentifier) AS loanidentifier, datesent, dateclosed, '. 
			'forwhom, description, datedue '.
			'FROM omoccurloans l INNER JOIN institutions i ON l.iidreceiving = i.iid '.
			'WHERE i.collid = '.$this->collId;
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
	
	public function setCollId($c){
		$this->collId = $c;
	}
	
	public function getLoanId(){
		return $this->loanId;
	}
}
?>