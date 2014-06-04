<?php
include_once($serverRoot.'/config/dbconnection.php');

class TaxonomyMaintenance{

	private $conn;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	function __destruct(){
		if($this->conn) $this->conn->close();
	}
	
	public function buildHierarchyEnumTree($taxAuthId = 1){
		set_time_limit(600);
		$sql = 'SELECT tid, hierarchystr '.
			'FROM taxstatus '.
			'WHERE tid NOT IN(SELECT DISTINCT tid FROM taxaenumtree WHERE taxauthid = '.$taxAuthId.') '.
			'AND taxauthid = '.$taxAuthId.' AND hierarchystr IS NOT NULL AND hierarchystr <> tid';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$tid = $r->tid;
				$hArr = explode(',',$r->hierarchystr);
				$sql = '';
				foreach($hArr as $v){
					$sql .= ',('.$tid.','.$taxAuthId.','.$v.')';
				}
				$sql = 'INSERT INTO taxaenumtree(tid,taxauthid,parenttid) VALUES '.substr($sql,1);
				$this->conn->query($sql);
			}
			$rs->close();
		}
	}
	
	public function buildHierarchyNestedTree($taxAuthId = 1){
		set_time_limit(1200);
		//Get root and then build down
		$startIndex = 1;
		$rankId = 0;
		$sql = 'SELECT ts.tid, t.rankid '.
			'FROM taxstatus ts INNER JOIN taxa t ON ts.tid = t.tid '.
			'WHERE ts.taxauthid = '.$taxAuthId.' AND (ts.parenttid IS NULL OR ts.parenttid = ts.tid) '.
			'ORDER BY t.rankid ';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				if($rankId && $rankId <> $r->rankid) break;
				$rankId = $r->rankid;
				$startIndex = $this->loadTaxonIntoNestedTree($r->tid, $taxAuthId, $startIndex);
			}
			$rs->close();
		}
	}
	
	private function loadTaxonIntoNestedTree($tid, $taxAuthId, $startIndex){
		$endIndex = $startIndex + 1;
		$sql = 'SELECT tid '.
			'FROM taxstatus '.
			'WHERE taxauthid = '.$taxAuthId.' AND parenttid = '.$tid;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$endIndex = $this->loadTaxonIntoNestedTree($r->tid, $taxAuthId, $endIndex);
			}
			$rs->close();
		}
		//Load into taxanestedtree
		$sqlInsert = 'REPLACE INTO taxanestedtree(tid,taxauthid,leftindex,rightindex) '.
			'VALUES ('.$tid.','.$taxAuthId.','.$startIndex.','.$endIndex.')';
		$this->conn->query($sqlInsert);
		//Return endIndex plus one
		$endIndex++;
		return $endIndex;
	}
	
}
?>