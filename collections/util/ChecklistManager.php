<?php
/*
 * Created on 3 May 2009
 * @author  E. Gilbert: egbot@asu.edu
 */
include_once('CollectionManager.php');

class ChecklistManager extends CollectionManager{
	
	private $checklistTaxaCnt = 0;

 	public function __construct(){
 		parent::__construct();
 	}

	public function getChecklistTaxaCnt(){
		return $this->checklistTaxaCnt;
	}

	public function getChecklist($taxonAuthorityId){
		$returnVec = Array();
		$this->checklistTaxaCnt = 0;
		$conn = $this->getConnection();
		$sql = "";
        if($taxonAuthorityId){
			$sql = "SELECT DISTINCT ts.Family, t.SciName ".
                "FROM ((omoccurrences o INNER JOIN taxstatus ts ON o.TidInterpreted = ts.Tid) INNER JOIN taxa t ON ts.TidAccepted = t.Tid) ";
			if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
			$sql .= str_ireplace("o.sciname","t.sciname",str_ireplace("o.family","ts.family",$this->getSqlWhere()))." AND ts.taxauthid = ".$taxonAuthorityId." AND t.RankId > 140 ORDER BY ts.family, t.SciName ";
        }
        else{
			$sql = "SELECT DISTINCT o.Family, o.SciName FROM omoccurrences o ";
			if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
			$sql .= $this->getSqlWhere()." AND o.SciName NOT LIKE '%aceae' AND o.SciName NOT IN ('Plantae','Polypodiophyta') ".
				"ORDER BY o.family, o.SciName ";
        }
        //echo "<div>".$sql."</div>";
        $result = $conn->query($sql);
		while($row = $result->fetch_object()){
			$family = strtoupper($row->Family);
			$sciName = $row->SciName;
			if($family){
				$returnVec[$family][] = $sciName;
			}
			else{
				$returnVec["undefined"][] = $sciName;
			}
			$this->checklistTaxaCnt++;
        }
        $conn->close();
        return $returnVec;
	}

	public function buildSymbiotaChecklist($taxonAuthorityId){
		global $clientRoot,$userId;
		if($this->dynamicClid) return $this->dynamicClid;
		$conn = $this->getConnection("write");
		$sqlCreateCl = "";
		$expirationTime = date('Y-m-d H:i:s',time()+259200);
		$searchStr = "";
		if($this->getTaxaSearchStr()) $searchStr .= "; ".$this->getTaxaSearchStr();
		if($this->getLocalSearchStr()) $searchStr .= "; ".$this->getLocalSearchStr();
		if($this->getDatasetSearchStr()) $searchStr .= "; ".$this->getDatasetSearchStr();
		$searchStr = substr($searchStr,2,250);
		$nameStr = substr($searchStr,0,35)."-".time();
		$dynClid = 0;
		$sqlCreateCl = "INSERT INTO fmdynamicchecklists ( name, details, uid, type, notes, expiration ) ".
			"VALUES ('Specimen Key #".date('Y-m-d H:i:s',time())."', 'Specimen Key #".date('Y-m-d H:i:s',time())."', '".$userId."', 'dynamic checklist', '', '".$expirationTime."') ";
		if($conn->query($sqlCreateCl)){
			$dynClid = $conn->insert_id;
			//Get checklist and append to dyncltaxalink
			$sqlTaxaInsert = "INSERT IGNORE INTO fmdyncltaxalink ( tid, dynclid ) ";
			if(!$taxonAuthorityId){
				$sqlTaxaInsert .= "SELECT DISTINCT t.tid, ".$dynClid." FROM (omoccurrences o INNER JOIN taxa t ON o.TidInterpreted = t.tid) ";
				if(array_key_exists("surveyid",$this->searchTermsArr)) $sqlTaxaInsert .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
				$sqlTaxaInsert .= $this->getSqlWhere()." AND t.RankId > 180";
			}
			else{
				$sqlTaxaInsert .= "SELECT DISTINCT t.tid, ".$dynClid." ".
                "FROM ((omoccurrences o INNER JOIN taxstatus ts ON o.TidInterpreted = ts.Tid) INNER JOIN taxa t ON ts.TidAccepted = t.Tid) ";
				if(array_key_exists("surveyid",$this->searchTermsArr)) $sqlTaxaInsert .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
				$sqlTaxaInsert .= str_ireplace("o.sciname","t.sciname",str_ireplace("o.family","ts.family",$this->getSqlWhere()))."AND ts.taxauthid = ".$taxonAuthorityId." AND t.RankId > 180";
			}
			//echo "sqlTaxaInsert: ".$sqlTaxaInsert;
			$conn->query($sqlTaxaInsert);
			$this->dynamicClid = $dynClid;
			$collVarCookie = "dynclid:".$dynClid; 
			if(array_key_exists("collvars",$_COOKIE)) $collVarCookie .= "&".$_COOKIE["collvars"];
			setCookie("collvars",$collVarCookie,time()+64800,$clientRoot);
		}
		else{
			echo "ERROR: ".$conn->error;
			echo "insertSql: ".$sqlCreateCl;
		}
		$conn;
		return $dynClid;
	}
}
?>