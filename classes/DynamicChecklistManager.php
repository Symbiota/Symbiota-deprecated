<?php
include_once($serverRoot.'/config/dbconnection.php');

class DynamicChecklistManager {

	private $conn;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function createChecklist($lat, $lng, $radius, $radiusUnits, $tidFilter){
		global $symbUid;
		
		if($radiusUnits == "mi") $radius = round($radius*1.6);
		$dynPk = 0;
		//Create checklist
		$sql = 'INSERT INTO fmdynamicchecklists(name,details,expiration,uid) '.
			'VALUES ("'.$lat.' '.$lng.' within '.$radius.' kilometers","'.$lat.' '.$lng.' within '.$radius.' kilometers","'.
			date('Y-m-d',mktime(0, 0, 0, date('m'), date('d') + 7, date('Y'))).'",'.($symbUid?$symbUid:'NULL').')';
		//echo $sql;
		if($this->conn->query($sql)){
			$dynPk = $this->conn->insert_id;
			//Add species to checklist
			$latRadius = $radius / 111;
			$lngRadius = cos($lat / 57.3)*($radius / 111);
			$lat1 = $lat - $latRadius;
			$lat2 = $lat + $latRadius;
			$lng1 = $lng - $lngRadius;
			$lng2 = $lng + $lngRadius;

			$sql = 'SELECT count(o.tid) AS speccnt FROM omoccurgeoindex o '.
				'WHERE (o.DecimalLatitude BETWEEN lat1 AND lat2) AND (o.DecimalLongitude BETWEEN lng1 AND lng2)';
			$this->conn->query($sql);
			
			$sql = 'INSERT INTO fmdyncltaxalink (dynclid, tid) '.
				'SELECT DISTINCT '.$dynPk.' AS dynpk, IF(t.rankid=220,t.tid,ts2.parenttid) as tid '.
				'FROM ((omoccurgeoindex o INNER JOIN taxstatus ts ON o.tid = ts.tid) '.
				'INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tid) '.
				'INNER JOIN taxa t ON ts2.tid = t.tid ';
			if($tidFilter){
				$sql .= 'INNER JOIN taxaenumtree e ON ts2.tid = e.tid '; 
			}
			$sql .= 'WHERE (t.rankid IN(220,230,240,260)) AND (ts.taxauthid = 1) AND (ts2.taxauthid = 1) '.
				'AND (o.DecimalLatitude BETWEEN '.$lat1.' AND '.$lat2.') AND (o.DecimalLongitude BETWEEN '.$lng1.' AND '.$lng2.') ';
			if($tidFilter){
				$sql .= 'and e.parentTid = '.$tidFilter;
			}
			//echo $sql; Exit;
			$this->conn->query($sql);
		}

		return $dynPk;
	}
	
	public function createDynamicChecklist($lat, $lng, $dynamicRadius, $tid){
		global $symbUid;
		$dynPk = 0;
		//set_time_limit(120);
		$sql = "Call DynamicChecklist(".$lat.",".$lng.",".$dynamicRadius.",".$tid.",".($symbUid?$symbUid:"NULL").")";
		//echo $sql;
		if($result = $this->conn->query($sql)){
			if($row = $result->fetch_row()){
				$dynPk = $row[0];
			}
			$result->close();
		}
		else{
			echo 'ERROR building checklist: DynamicChecklist Stored Procedure is probablhy not defined ';
			exit;
		}
		return $dynPk;
	}
	
	public function getFilterTaxa(){
		$retArr = Array();
		$sql = "SELECT t.tid, t.sciname FROM taxa t WHERE t.rankid <= 140 ORDER BY t.sciname ";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->tid] = $this->cleanOutStr($row->sciname);
		}
		return $retArr;
	}
	
	public function removeOldChecklists(){
		//Remove any old checklists
		$sql1 = 'DELETE dcl.* '.
			'FROM fmdyncltaxalink dcl INNER JOIN fmdynamicchecklists dc ON dcl.dynclid = dc.dynclid '.
			'WHERE dc.expiration < NOW()';
		$this->conn->query($sql1);
		$sql2 = 'DELETE FROM fmdynamicchecklists WHERE expiration < NOW()';
		$this->conn->query($sql2);
	} 

	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>