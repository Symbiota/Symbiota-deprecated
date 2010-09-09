<?php
/*
 * Created on 1 May 2009
 * @author  E. Gilbert: egbot@asu.edu
 */
include_once("CollectionManager.php");

class ListManager extends CollectionManager{

	private $cntPerPage = 50;	  //default is 50 - this can be set in the jsp page
	protected $recordCount = 0;
	protected $dynamicClid = 0;
	
 	public function __construct(){
 		parent::__construct();
 	}

	public function getSpecimenMap($pageRequest){
		global $userRights;
		$returnArr = Array();
		$conn = $this->getConnection();
		$sqlWhere = $this->getSqlWhere();
		if(!$this->recordCount){
			$this->setRecordCnt($sqlWhere,$conn);
		}
		$sql = "SELECT o.occid, o.CollID, IFNULL(o.CatalogNumber,'') AS catalognumber, o.family, o.sciname, ".
			"IFNULL(o.scientificNameAuthorship,'') AS author, IFNULL(o.recordedBy,'') AS recordedby, IFNULL(o.recordNumber,'') AS recordnumber, ".
			"IFNULL(DATE_FORMAT(o.eventDate,'%d %M %Y'),'') AS date1, DATE_FORMAT(MAKEDATE(o.year,o.endDayOfYear),'%d %M %Y') AS date2, ".
			"IFNULL(o.country,'') AS country, IFNULL(o.StateProvince,'') AS state, IFNULL(o.county,'') AS county, ".
			"IFNULL(o.locality,'') AS locality, o.dbpk, IFNULL(o.LocalitySecurity,1) AS LocalitySecurity ".
			"FROM omoccurrences o ";
		if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
		$sql .= $sqlWhere;
		$bottomLimit = ($pageRequest - 1)*$this->cntPerPage;
		$sql .= "ORDER BY o.CollID, o.sciname ";
		if(strpos($sqlWhere,"(o.SciName Like") == 0 || strpos($sqlWhere,"o.family =") == 0) $sql .= ", o.recordedBy, o.recordNumber ";
		$sql .= "LIMIT ".$bottomLimit.",".$this->cntPerPage;
		//echo "<div>Spec sql: ".$sql."</div>";
		$result = $conn->query($sql);
		$canReadRareSpp = false;
		if(array_key_exists("SuperAdmin", $userRights) || array_key_exists("CollAdmin", $userRights) || array_key_exists("RareSppAdmin", $userRights) || array_key_exists("RareSppReadAll", $userRights)){
			$canReadRareSpp = true;
		}
		while($row = $result->fetch_object()){
			$collIdStr = $row->CollID;
			$dbpk = $row->dbpk;
			$returnArr[$collIdStr][$dbpk]["occid"] = $row->occid;
			$returnArr[$collIdStr][$dbpk]["accession"] = $row->catalognumber;
			$returnArr[$collIdStr][$dbpk]["family"] = $row->family;
			$returnArr[$collIdStr][$dbpk]["sciname"] = $row->sciname;
			$returnArr[$collIdStr][$dbpk]["author"] = $row->author;
			$returnArr[$collIdStr][$dbpk]["collector"] = $row->recordedby;
			$returnArr[$collIdStr][$dbpk]["collnumber"] = $row->recordnumber;
			$returnArr[$collIdStr][$dbpk]["date1"] = $row->date1;
			$returnArr[$collIdStr][$dbpk]["date2"] = $row->date2;
			$returnArr[$collIdStr][$dbpk]["country"] = $row->country;
			$returnArr[$collIdStr][$dbpk]["state"] = $row->state;
			$returnArr[$collIdStr][$dbpk]["county"] = $row->county;
			$localitySecurity = $row->LocalitySecurity;
			if(!$localitySecurity || $canReadRareSpp || (array_key_exists("RareSppReader", $userRights) && in_array($collIdStr,$userRights["RareSppReader"]))){
				$returnArr[$collIdStr][$dbpk]["locality"] = $row->locality;
			}
			else{
				$returnArr[$collIdStr][$dbpk]["locality"] = "<div style='color:red;'>--detailed locality info. masked due to rare status--</div>";
			}
			$returnArr[$collIdStr][$dbpk]["dbpk"] = $row->dbpk;
		}
		$result->close();
		$conn->close();
		return $returnArr;
	}

	private function setRecordCnt($sqlWhere, $conn){
		global $clientRoot;
		if($sqlWhere){
			$sql = "SELECT COUNT(o.occid) AS cnt FROM omoccurrences o ";
			if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
			$sql .= $sqlWhere;
			//echo "<div>Count sql: ".$sql."</div>";
			$result = $conn->query($sql);
			if($row = $result->fetch_object()){
				$this->recordCount = $row->cnt;
			}
			$result->close();
		}
		setCookie("collvars","reccnt:".$this->recordCount,time()+64800,$clientRoot);
	}

	public function getRecordCnt(){
		return $this->recordCount;
	}
	
	public function resetRecordCount(){
		$this->recordCount = 0;
	}

	public function getCntPerPage(){
		return $this->cntPerPage;
	}
}
?>