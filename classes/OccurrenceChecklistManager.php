<?php
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
        if($taxonAuthorityId && is_numeric($taxonAuthorityId)){
			$sql = 'SELECT DISTINCT ts.family, t.sciname '.
                'FROM ((omoccurrences o INNER JOIN taxstatus ts1 ON o.TidInterpreted = ts1.Tid) '.
                'INNER JOIN taxa t ON ts1.TidAccepted = t.Tid) '.
				'INNER JOIN taxstatus ts ON t.tid = ts.tid ';
			if(array_key_exists("clid",$this->searchTermsArr)) $sql .= "INNER JOIN fmvouchers v ON o.occid = v.occid ";
			if(array_key_exists("collector",$this->searchTermsArr)) $sql .= "INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ";
			$sql .= str_ireplace("o.sciname","t.sciname",str_ireplace("o.family","ts.family",$this->getSqlWhere())).
				" AND ts1.taxauthid = ".$taxonAuthorityId." AND ts.taxauthid = ".$taxonAuthorityId." AND t.RankId > 140 ";
        }
        else{
			$sql = 'SELECT DISTINCT IFNULL(ts.family,o.family) AS family, o.sciname '.
				'FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.tid '.
				'LEFT JOIN taxstatus ts ON t.tid = ts.tid ';
			if(array_key_exists("clid",$this->searchTermsArr)) $sql .= "INNER JOIN fmvouchers v ON o.occid = v.occid ";
			if(array_key_exists("collector",$this->searchTermsArr)) $sql .= "INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ";
			$sql .= $this->getSqlWhere()." AND (t.rankid > 140) AND (ts.taxauthid = 1) ";
        }
		//echo "<div>".$sql."</div>"; 
        $result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$family = strtoupper($row->family);
			if(!$family) $family = 'undefined';
			$sciName = $row->sciname;
			if($sciName && substr($sciName,-5)!='aceae'){
				$returnVec[$family][] = $sciName;
				$this->checklistTaxaCnt++;
			}
        }
        return $returnVec;
	}

    public function getTidChecklist($tidArr,$taxonFilter){
        $returnVec = Array();
        $tidStr = implode(',',$tidArr);
        $this->checklistTaxaCnt = 0;
        $sql = "";
        $sql = 'SELECT DISTINCT ts.family, t.sciname '.
            'FROM (taxstatus AS ts1 INNER JOIN taxa AS t ON ts1.TidAccepted = t.Tid) '.
            'INNER JOIN taxstatus AS ts ON t.tid = ts.tid '.
            'WHERE ts1.tid IN('.$tidStr.') '.
            'AND ts1.taxauthid = '.$taxonFilter.' AND ts.taxauthid = '.$taxonFilter.' AND t.RankId > 140 ';
        //echo "<div>".$sql."</div>";
        $result = $this->conn->query($sql);
        while($row = $result->fetch_object()){
            $family = strtoupper($row->family);
            if(!$family) $family = 'undefined';
            $sciName = $row->sciname;
            if($sciName && substr($sciName,-5)!='aceae'){
                $returnVec[$family][] = $sciName;
                $this->checklistTaxaCnt++;
            }
        }
        return $returnVec;
    }

	public function buildSymbiotaChecklist($taxonAuthorityId,$tidArr = ''){
		global $clientRoot,$userId;
		$conn = $this->getConnection("write");
		$sqlCreateCl = "";
		$expirationTime = date('Y-m-d H:i:s',time()+259200);
		$searchStr = "";
        $tidStr = "";
		if($tidArr){
            $tidStr = implode(',',$tidArr);
        }
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
			if($tidStr){
                if(is_numeric($taxonAuthorityId)){
                    $sqlTaxaInsert .= 'SELECT DISTINCT t.tid, '.$dynClid.' '.
                        'FROM taxstatus AS ts INNER JOIN taxa AS t ON ts.TidAccepted = t.Tid '.
                        'WHERE ts.tid IN('.$tidStr.') AND ts.taxauthid = '.$taxonAuthorityId.' AND t.RankId > 180';
                }
                else{
                    $sqlTaxaInsert .= 'SELECT DISTINCT t.tid, '.$dynClid.' FROM taxa AS t '.
                        'WHERE t.tid IN('.$tidStr.') AND t.RankId > 180 ';
                }
            }
            else{
                if(is_numeric($taxonAuthorityId)){
                    $sqlTaxaInsert .= "SELECT DISTINCT t.tid, ".$dynClid." ".
                        "FROM ((omoccurrences o INNER JOIN taxstatus ts ON o.TidInterpreted = ts.Tid) INNER JOIN taxa t ON ts.TidAccepted = t.Tid) ";
                    if(array_key_exists("clid",$this->searchTermsArr)) $sqlTaxaInsert .= "INNER JOIN fmvouchers v ON o.occid = v.occid ";
                    if(array_key_exists("collector",$this->searchTermsArr)) $sqlTaxaInsert .= "INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ";
                    $sqlTaxaInsert .= str_ireplace("o.sciname","t.sciname",str_ireplace("o.family","ts.family",$this->getSqlWhere()))."AND ts.taxauthid = ".$taxonAuthorityId." AND t.RankId > 180";
                }
                else{
                    $sqlTaxaInsert .= "SELECT DISTINCT t.tid, ".$dynClid." FROM (omoccurrences o INNER JOIN taxa t ON o.TidInterpreted = t.tid) ";
                    if(array_key_exists("clid",$this->searchTermsArr)) $sqlTaxaInsert .= "INNER JOIN fmvouchers v ON o.occid = v.occid ";
                    if(array_key_exists("collector",$this->searchTermsArr)) $sqlTaxaInsert .= "INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ";
                    $sqlTaxaInsert .= $this->getSqlWhere()." AND t.RankId > 180";
                }
            }
			//echo "sqlTaxaInsert: ".$sqlTaxaInsert;
			$conn->query($sqlTaxaInsert);
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