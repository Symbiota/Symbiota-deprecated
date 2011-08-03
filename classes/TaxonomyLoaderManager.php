<?php
/*
 * Created on 10 Aug 2009
 * E.E. Gilbert
 */

include_once($serverRoot.'/config/dbconnection.php');
  
class TaxonomyLoaderManager{

	private $conn;
	
	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon('write');
	}
	
	function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}
	
	public function echoTaxonRanks(){
		$sql = "SELECT tu.rankid, tu.rankname FROM taxonunits tu ORDER BY tu.rankid";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$rankId = $row->rankid;
			$rankName = $row->rankname;
			echo "<option value='".$rankId."' ".($rankId==220?" SELECTED":"").">".$rankName."</option>\n";
		}
	}
	
	public function loadNewName($dataArr){
		//Load new name into taxa table
		$tid = 0;
		$sqlTaxa = "INSERT INTO taxa(sciname, author, rankid, unitind1, unitname1, unitind2, unitname2, unitind3, unitname3, ".
			"source, notes, securitystatus) ".
			"VALUES (\"".$this->conn->real_escape_string($dataArr["sciname"])."\",".($dataArr["author"]?"\"".$this->conn->real_escape_string($dataArr["author"])."\"":"NULL").
			",".$dataArr["rankid"].",".($dataArr["unitind1"]?"\"".$this->conn->real_escape_string($dataArr["unitind1"])."\"":"NULL").
			",\"".$this->conn->real_escape_string($dataArr["unitname1"])."\",".($dataArr["unitind2"]?"\"".$this->conn->real_escape_string($dataArr["unitind2"])."\"":"NULL").
			",".($dataArr["unitname2"]?"\"".$this->conn->real_escape_string($dataArr["unitname2"])."\"":"NULL").
			",".($dataArr["unitind3"]?"\"".$this->conn->real_escape_string($dataArr["unitind3"])."\"":"NULL").
			",".($dataArr["unitname3"]?"\"".$this->conn->real_escape_string($dataArr["unitname3"])."\"":"NULL").
			",".($dataArr["source"]?"\"".$this->conn->real_escape_string($dataArr["source"])."\"":"NULL").",".
			($dataArr["notes"]?"\"".$this->conn->real_escape_string($dataArr["notes"])."\"":"NULL").
			",".$this->conn->real_escape_string($dataArr["securitystatus"]).")";
		//echo "sqlTaxa: ".$sqlTaxa;
		if($this->conn->query($sqlTaxa)){
			$tid = $this->conn->insert_id;
		 	//Load accepteance status into taxstatus table
			$tidAccepted = ($dataArr["acceptstatus"]?$tid:$dataArr["tidaccepted"]);
			if($dataArr["parenttid"]){ 
				$hierarchy = $this->getHierarchy($dataArr["parenttid"]);
				//Get family from hierarchy
				$family = '';
				$sqlFam = 'SELECT sciname FROM taxa WHERE (tid IN('.$hierarchy.')) AND rankid = 140 ';
				$rsFam = $this->conn->query($sqlFam);
				if($rsFam){
					if($r = $rsFam->fetch_object()){
						$family = $r->sciname;
					}
				}
				
				//Load new record into taxstatus table
				$sqlTaxStatus = "INSERT INTO taxstatus(tid, tidaccepted, taxauthid, family, uppertaxonomy, parenttid, unacceptabilityreason, hierarchystr) ".
					"VALUES (".$tid.",".$tidAccepted.",1,".($family?"\"".$this->conn->real_escape_string($family)."\"":"NULL").",".
					($dataArr["uppertaxonomy"]?"\"".$this->conn->real_escape_string($dataArr["uppertaxonomy"])."\"":"NULL").
					",".($dataArr["parenttid"]?$this->conn->real_escape_string($dataArr["parenttid"]):"NULL").",\"".
					$this->conn->real_escape_string($dataArr["unacceptabilityreason"])."\",\"".$hierarchy."\") ";
				//echo "sqlTaxStatus: ".$sqlTaxStatus;
				if(!$this->conn->query($sqlTaxStatus)){
					return "ERROR: Taxon loaded into taxa, but falied to load taxstatus: sql = ".$sqlTaxa;
				}
			}
		 	
			//Link new name to existing specimens and set locality secirity if needed
			$sql1 = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname SET o.TidInterpreted = t.tid ';
			if($dataArr['securitystatus']) $sql1 .= ',o.localitysecurity = 1 '; 
			$sql1 .= 'WHERE (o.sciname = "'.$this->conn->real_escape_string($dataArr["sciname"]).'") ';
			$this->conn->query($sql1);
			//Link occurrence images to the new name
			$sql2 = 'UPDATE omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'SET i.tid = o.tidinterpreted '.
				'WHERE i.tid is null AND o.tidinterpreted IS NOT NULL';
			$this->conn->query($sql2);
			//Add their geopoints to omoccurgeoindex 
			$sql3 = "INSERT IGNORE INTO omoccurgeoindex(tid,decimallatitude,decimallongitude) ".
				"SELECT DISTINCT o.tidinterpreted, round(o.decimallatitude,3), round(o.decimallongitude,3) ".
				"FROM omoccurrences o ".
				"WHERE (o.tidinterpreted = ".$tid.") AND o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL";
			$this->conn->query($sql3);
			
		}
		else{
			return 'Taxon Insert FAILED: '.$this->conn->error.'; SQL = '.$sqlTaxa;
		}
		return $tid;
	}
	
	private function getHierarchy($tid){
		$parentArr = Array($tid);
		$parCnt = 0;
		$targetTid = $this->conn->real_escape_string($tid);
		do{
			$sqlParents = "SELECT IFNULL(ts.parenttid,0) AS parenttid FROM taxstatus ts WHERE (ts.tid = ".$targetTid.')';
			//echo "<div>".$sqlParents."</div>";
			$resultParent = $this->conn->query($sqlParents);
			if($rowParent = $resultParent->fetch_object()){
				$parentTid = $rowParent->parenttid;
				if($parentTid) {
					$parentArr[$parentTid] = $parentTid;
				}
			}
			else{
				break;
			}
			$resultParent->close();
			$parCnt++;
			if($targetTid == $parentTid) break;
			$targetTid = $parentTid;
		}while($targetTid && $parCnt < 16);
		
		return implode(",",array_reverse($parentArr));
	}
}
?>