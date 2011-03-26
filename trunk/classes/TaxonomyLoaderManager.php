<?php
/*
 * Created on 10 Aug 2009
 * E.E. Gilbert
 */

include_once($serverRoot.'/config/dbconnection.php');
  
class TaxonomyLoaderManager{

	private $conn;
	
	public function __construct(){
		$this->setConnection();
	}
	
	function __destruct(){
		$this->conn->close();
	}
	
	private function setConnection($conType = "write"){
 		$this->conn = MySQLiConnectionFactory::getCon($conType);
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
		$tid;
		if($dataArr["parenttid"]){
			$sqlTaxa = "INSERT INTO taxa(sciname, author, rankid, unitind1, unitname1, unitind2, unitname2, unitind3, unitname3, ".
				"source, notes, securitystatus) ".
				"VALUES (\"".$dataArr["sciname"]."\",".($dataArr["author"]?"\"".$dataArr["author"]."\"":"NULL").",".$dataArr["rankid"].
				",".($dataArr["unitind1"]?"\"".$dataArr["unitind1"]."\"":"NULL").",\"".$dataArr["unitname1"]."\",".
				($dataArr["unitind2"]?"\"".$dataArr["unitind2"]."\"":"NULL").",".($dataArr["unitname2"]?"\"".$dataArr["unitname2"]."\"":"NULL").
				",".($dataArr["unitind3"]?"\"".$dataArr["unitind3"]."\"":"NULL").",".($dataArr["unitname3"]?"\"".$dataArr["unitname3"]."\"":"NULL").
				",".($dataArr["source"]?"\"".$dataArr["source"]."\"":"NULL").",".($dataArr["notes"]?"\"".$dataArr["notes"]."\"":"NULL").
				",".$dataArr["securitystatus"].")";
			//echo "sqlTaxa: ".$sqlTaxa;
			if($this->conn->query($sqlTaxa)){
				$tid = $this->conn->insert_id;
			 	//Load accepteance status into taxstatus table
				$tidAccepted = ($dataArr["acceptstatus"]?$tid:$dataArr["tidaccepted"]);
				$hierarchy = $this->getHierarchy($dataArr["parenttid"]);
				//Get family from hierarchy
				$family = '';
				$sqlFam = 'SELECT t.sciname FROM taxa WHERE tid IN('.$hierarchy.') AND rankid = 140 ';
				$rsFam = $this->conn->query($sqlFam);
				if($rsFam){
					if($r = $rsFam->fetch_object()){
						$family = $r->sciname;
					}
				}
				
				//Load new record into taxstatus table
				$sqlTaxStatus = "INSERT INTO taxstatus(tid, tidaccepted, taxauthid, family, uppertaxonomy, parenttid, unacceptabilityreason, hierarchystr) ".
					"VALUES (".$tid.",".$tidAccepted.",1,".($family?"\"".$family."\"":"NULL").",".
					($dataArr["uppertaxonomy"]?"\"".$dataArr["uppertaxonomy"]."\"":"NULL").",".$dataArr["parenttid"].",\"".$dataArr["unacceptabilityreason"]."\",\"".$hierarchy."\") ";
				//echo "sqlTaxStatus: ".$sqlTaxStatus;
				if(!$this->conn->query($sqlTaxStatus)){
					return "ERROR: Taxon loaded into taxa, but falied to load taxstatus: sql = ".$sqlTaxa;
				}
			 	
				//Link new name to existing specimens and set locality secirity if needed
				$sql1 = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname SET o.TidInterpreted = t.tid ';
				if($dataArr['securitystatus']) $sql1 .= ',localitysecurity = 1 '; 
				$sql1 .= 'WHERE sciname = "'.$dataArr["sciname"].'"';
				$this->conn->query($sql1);
				//Add their geopoints to omoccurgeoindex 
				$sql3 = "INSERT IGNORE INTO omoccurgeoindex(tid,decimallatitude,decimallongitude) ".
					"SELECT DISTINCT o.tidinterpreted, round(o.decimallatitude,3), round(o.decimallongitude,3) ".
					"FROM omoccurrences o ".
					"WHERE o.tidinterpreted = ".$tid." AND o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL";
				$this->conn->query($sql3);
				
			}
			else{
				return 'Taxon Insert FAILED: '.$this->conn->error.'; SQL = '.$sqlTaxa;
			}
		}
		return $tid;
	}
	
	private function getHierarchy($tid){
		$parentArr = Array($tid);
		$parCnt = 0;
		$targetTid = $tid;
		do{
			$sqlParents = "SELECT IFNULL(ts.parenttid,0) AS parenttid FROM taxstatus ts WHERE ts.tid = ".$targetTid;
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