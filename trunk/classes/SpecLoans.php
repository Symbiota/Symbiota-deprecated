<?php
include_once($serverRoot.'/config/dbconnection.php');

class SpecLoans{

	private $conn;
	private $collId = 0;
	private $loanOutId = 0;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	function __destruct(){
 		if($this->conn) $this->conn->close();
	}

	public function getLoanList(){
		$retArr = array();
		$sql = 'SELECT loanoutid, loantitle, dateclosed '.
			'FROM omoccurloansout '.
			'WHERE collid = '.$this->collId.' '.
			'ORDER BY loantitle';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->loanoutid]['title'] = $r->loantitle;
				$retArr[$r->loanoutid]['dateclosed'] = $r->dateclosed;
			}
			$rs->close();
		}
		return $retArr;
	} 

	public function getLoanDetails($loanOutId){
		$retArr = array();
		$sql = 'SELECT '.
			'FROM omoccurloansout '.
			'WHERE loanoutid = '.$loanOutId;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr['title'] = $r->loantitle;
				//$retArr[''] = $r->;
			}
			$rs->close();
		}
		return $retArr;
	} 

	public function editLoan(){
		$statusStr = '';
		$sql = '';
		if(!$this->conn->query($sql)){
			$statusStr = 'ERROR: Editing of loan failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
	}
	
	public function createNewLoan(){
		$statusStr = '';
		$sql = '';
		if($this->conn->query($sql)){
			$this->loanOutId = $this->conn->insert_id;
		}
		else{
			$statusStr = 'ERROR: Creation of new loan failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
	}
	
	public function setCollId($c){
		$this->collId = $c;
	}
	
	public function getLoanOutId(){
		return $this->loanOutId;
	}
}
?>