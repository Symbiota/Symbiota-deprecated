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

	public function getLoanOutList($searchTerm,$displayAll){
		$retArr = array();
		$sql = 'SELECT loanid, loanidentifierown, dateclosed '.
			'FROM omoccurloans '.
			'WHERE collidown = '.$this->collId.' ';
		if($searchTerm){
			$sql .= 'AND loanidentifierown LIKE "%'.$searchTerm.'%" ';
		}
		if(!$displayAll){
			$sql .= 'AND ISNULL(dateclosed) ';
		}
		$sql .= 'ORDER BY loanidentifierown';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->loanid]['loanidentifierown'] = $r->loanidentifierown;
				$retArr[$r->loanid]['dateclosed'] = $r->dateclosed;
			}
			$rs->close();
		}
		return $retArr;
	} 
	
	public function getLoanInList($searchTerm,$displayAll){
		$retArr = array();
		$sql = 'SELECT loanid, loanidentifierborr, dateclosed '.
			'FROM omoccurloans '.
			'WHERE collidborr = '.$this->collId.' ';
		if($searchTerm){
			$sql .= 'AND loanidentifierborr LIKE "%'.$searchTerm.'%" ';
		}
		if(!$displayAll){
			$sql .= 'AND ISNULL(dateclosed) ';
		}
		$sql .= 'ORDER BY loanidentifierborr';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->loanid]['loanidentifierborr'] = $r->loanidentifierborr;
				$retArr[$r->loanid]['dateclosed'] = $r->dateclosed;
			}
			$rs->close();
		}
		return $retArr;
	} 
	
	//Ed's version
	/*public function getLoansIn(){
		$retArr = array();
		$sql = 'SELECT loanid, IFNULL(loanIdentifierReceiver, loanIdentifier) AS loanidentifier, datesent, dateclosed, '. 
			'forwhom, description, datedue '.
			'FROM omoccurloans l INNER JOIN institutions i ON l.iidreceiving = i.iid '.
			'WHERE (i.collidborr = '.$this->collId.')';
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
	}*/

	public function getLoanOutDetails($loanId){
		$retArr = array();
		$sql = 'SELECT loanid, loanidentifierown, iidborrower, datesent, totalboxes, '.
			'shippingmethod, datedue, datereceivedown, dateclosed, forwhom, description, '.
			'notes, createdbyown, processedbyown, processedbyreturnown '.
			'FROM omoccurloans '.
			'WHERE loanid = '.$loanId;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr['loanidentifierown'] = $r->loanidentifierown;
				$retArr['iidborrower'] = $r->iidborrower;
				$retArr['datesent'] = $r->datesent;
				$retArr['totalboxes'] = $r->totalboxes;
				$retArr['shippingmethod'] = $r->shippingmethod;
				$retArr['datedue'] = $r->datedue;
				$retArr['datereceivedown'] = $r->datereceivedown;
				$retArr['dateclosed'] = $r->dateclosed;
				$retArr['forwhom'] = $r->forwhom;
				$retArr['description'] = $r->description;
				$retArr['notes'] = $r->notes;
				$retArr['createdbyown'] = $r->createdbyown;
				$retArr['processedbyown'] = $r->processedbyown;
				$retArr['processedbyreturnown'] = $r->processedbyreturnown;
			}
			$rs->close();
		}
		return $retArr;
	} 
	
	public function getLoanInDetails($loanId){
		$retArr = array();
		$sql = 'SELECT loanid, loanidentifierborr, iidborrower, datesent, totalboxes, '.
			'shippingmethod, datedue, datereceivedown, dateclosed, forwhom, description, '.
			'notes, createdbyown, processedbyown, processedbyreturnown '.
			'FROM omoccurloans '.
			'WHERE loanid = '.$loanId;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr['loanidentifierborr'] = $r->loanidentifierborr;
				$retArr['iidborrower'] = $r->iidborrower;
				$retArr['datesent'] = $r->datesent;
				$retArr['totalboxes'] = $r->totalboxes;
				$retArr['shippingmethod'] = $r->shippingmethod;
				$retArr['datedue'] = $r->datedue;
				$retArr['datereceivedown'] = $r->datereceivedown;
				$retArr['dateclosed'] = $r->dateclosed;
				$retArr['forwhom'] = $r->forwhom;
				$retArr['description'] = $r->description;
				$retArr['notes'] = $r->notes;
				$retArr['createdbyown'] = $r->createdbyown;
				$retArr['processedbyown'] = $r->processedbyown;
				$retArr['processedbyreturnown'] = $r->processedbyreturnown;
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
	
	public function createNewLoanOut($pArr){
		$statusStr = '';
		$sql = 'INSERT INTO omoccurloans(collidown,loanidentifierown,iidborrower,createdbyown) '.
			'VALUES('.$this->collId.',"'.$this->cleanString($pArr['loanidentifierown']).'","'.$this->cleanString($pArr['reqinstitution']).'",
			"'.$this->cleanString($pArr['createdbyown']).'")';
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
	
	//
	public function getloanIdentifierBorr($pArr){
		$statusStr = '';
		$sql = 'INSERT INTO omoccurloans(collidborr,loanidentifierborr,iidowner,createdbyborr) '.
			'VALUES('.$this->collId.',"'.$this->cleanString($pArr['loanidentifierborr']).'","'.$this->cleanString($pArr['iidowner']).'",
			"'.$this->cleanString($pArr['createdbyborr']).'")';
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
	
	public function createNewLoanIn($pArr){
		$statusStr = '';
		$sql = 'INSERT INTO omoccurloans(collidborr,loanidentifierborr,iidowner,createdbyborr) '.
			'VALUES('.$this->collId.',"'.$this->cleanString($pArr['loanidentifierborr']).'","'.$this->cleanString($pArr['iidowner']).'",
			"'.$this->cleanString($pArr['createdbyborr']).'")';
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
	
	public function getSpecTotal($loanId){
		$retArr = array();
		$sql = 'SELECT loanid, COUNT(loanid) AS speccount '.
			'FROM omoccurloanslink '.
			'WHERE loanid = '.$loanId.' '.
			'GROUP BY loanid';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr['speccount'] = $r->speccount;
			}
			$rs->close();
		}
		return $retArr;
	} 
	
	public function getSpecList($loanId){
		$retArr = array();
		$sql = 'SELECT l.loanid, l.occid, o.catalognumber, o.sciname '.
			'FROM omoccurloanslink AS l LEFT OUTER JOIN omoccurrences AS o ON l.occid = o.occid '.
			'WHERE l.loanid = '.$loanId.' '.
			'ORDER BY o.catalognumber';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->occid]['catalognumber'] = $r->catalognumber;
				$retArr[$r->occid]['sciname'] = $r->sciname;
			}
			$rs->close();
		}
		return $retArr;
	} 
	
	public function addSpecimen($pArr){
		$statusStr = '';
		$loanId = $this->cleanString($pArr['loanid']);
		$collId = $this->cleanString($pArr['collid']);
		$catNum = $this->cleanString($pArr['catalognumber']);
		//$occId = $this->getOccID($collId,$catNum);
		$sql = 'INSERT INTO omoccurloanslink(loanid,occid) '.
			'VALUES('.$loanId.',(SELECT occid FROM omoccurrences WHERE collid = '.$collId.' AND catalognumber = "'.$catNum.'")) ';
		//echo $sql;
		if($this->conn->query($sql)){
			$statusStr = 'SUCCESS: Specimen Added';
		}
		else{
			$statusStr = 'ERROR: Adding of specimen failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
	}
	
	public function AjaxAddSpecimen($catalogNumber){
		$statusStr = '';
		$sql = 'INSERT INTO omoccurloanslink(loanid,occid) '.
			'SELECT '.$loanId.',occid FROM omoccurrences WHERE catalognumber = "'.$catalogNumber.'"';
		//echo $sql;
		if($this->conn->query($sql)){
			$statusStr = 'SUCCESS: Specimen Added';
		}
		else{
			$statusStr = 'ERROR: Adding of specimen failed: '.$this->conn->error.'<br/>';
			$statusStr .= 'SQL: '.$sql;
		}
		return $statusStr;
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