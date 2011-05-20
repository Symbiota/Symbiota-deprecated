<?php
/*
 * Created on 3 May 2009
 * @author  E. Gilbert: egbot@asu.edu
 */
include_once('OccurrenceManager.php');

class OccurrenceChecklistManager extends OccurrenceManager{
	
	private $checklistTaxaCnt = 0;

 	public function __construct(){
 		parent::__construct();
 	}

	public function __destruct(){
 		parent::__destruct();
	}

 	public function getChecklistTaxaCnt(){
		return $this->checklistTaxaCnt;
	}

	public function getChecklist($taxonAuthorityId){
		$returnVec = Array();
		$this->checklistTaxaCnt = 0;
		$sql = "";
        if($taxonAuthorityId){
			$sql = "SELECT DISTINCT ts.family, t.sciname ".
                "FROM ((omoccurrences o INNER JOIN taxstatus ts ON o.TidInterpreted = ts.Tid) INNER JOIN taxa t ON ts.TidAccepted = t.Tid) ";
			if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
			$sql .= str_ireplace("o.sciname","t.sciname",str_ireplace("o.family","ts.family",$this->getSqlWhere()))." AND ts.taxauthid = ".$taxonAuthorityId." AND t.RankId > 140 ORDER BY ts.family, t.SciName ";
        }
        else{
			$sql = 'SELECT DISTINCT IFNULL(ts.family,o.family) AS family, o.sciname '.
				'FROM omoccurrences o LEFT JOIN taxstatus ts ON o.tidinterpreted = ts.tid ';
			if(array_key_exists("surveyid",$this->searchTermsArr)) $sql .= "INNER JOIN omsurveyoccurlink sol ON o.occid = sol.occid ";
			$sql .= $this->getSqlWhere()." AND (ts.taxauthid = 1 OR ts.taxauthid IS NULL) ".
				"ORDER BY IFNULL(ts.family,o.family), o.sciname ";
        }
        //echo "<div>".$sql."</div>";
        $result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$family = strtoupper($row->family);
			$sciName = $row->sciname;
			if($sciName && substr($sciName,-5)!='aceae'){
				if($family){
					if(!array_key_exists($family,$returnVec) || !in_array($sciName,$returnVec[$family])){
						$returnVec[$family][] = $sciName;
					}
				}
				else{
					if(!array_key_exists('undefined',$returnVec) || !in_array($sciName,$returnVec['undefined'])){
						$returnVec['undefined'][] = $sciName;
					}
				}
			}
			$this->checklistTaxaCnt++;
        }
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
		//$searchStr = substr($searchStr,2,250);
		//$nameStr = substr($searchStr,0,35)."-".time();
		$dynClid = 0;
		$sqlCreateCl = "INSERT INTO fmdynamicchecklists ( name, details, uid, type, notes, expiration ) ".
			"VALUES ('Specimen Checklist #".time()."', 'Generated ".date('d-m-Y H:i:s',time())."', '".$userId."', 'Specimen Checklist', '', '".$expirationTime."') ";
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
			setCookie("collvars",$collVarCookie,time()+64800,($clientRoot?$clientRoot:'/'));
			//Delete checklists that are greater than one week old
			$conn->query('DELETE FROM fmdynamicchecklists WHERE expiration < now()'); 
		}
		else{
			echo "ERROR: ".$conn->error;
			echo "insertSql: ".$sqlCreateCl;
		}
		if($conn !== false) $conn->close();
		return $dynClid;
	}
}
?>