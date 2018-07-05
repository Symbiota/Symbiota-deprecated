<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class DynamicChecklistManager {

	private $conn;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function createChecklist($lat, $lng, $radius, $radiusUnits, $tidFilter){
		global $SYMB_UID;
		//Radius is a set value 
		if($radiusUnits == "mi") $radius = round($radius*1.6);
		$dynPk = 0;
		//Create checklist
		$sql = 'INSERT INTO fmdynamicchecklists(name,details,expiration,uid) '.
			'VALUES ("'.round($lat,5).' '.round($lng,5).' within '.round($radius,1).' km","'.$lat.' '.$lng.' within '.$radius.' km","'.
			date('Y-m-d',mktime(0, 0, 0, date('m'), date('d') + 7, date('Y'))).'",'.($SYMB_UID?$SYMB_UID:'NULL').')';
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

			//$sql = 'SELECT count(o.tid) AS speccnt FROM omoccurgeoindex o '.
			//	'WHERE (o.DecimalLatitude BETWEEN lat1 AND lat2) AND (o.DecimalLongitude BETWEEN lng1 AND lng2)';
			//$this->conn->query($sql);
			
			$sql = 'INSERT INTO fmdyncltaxalink (dynclid, tid) '.
				'SELECT DISTINCT '.$dynPk.' AS dynpk, IF(t.rankid=220,t.tid,ts2.parenttid) as tid '.
				'FROM omoccurgeoindex o INNER JOIN taxstatus ts ON o.tid = ts.tid '.
				'INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tid '.
				'INNER JOIN taxa t ON ts2.tid = t.tid ';
			if($tidFilter){
				$sql .= 'INNER JOIN taxaenumtree e ON ts2.tid = e.tid '; 
			}
			$sql .= 'WHERE (t.rankid IN(220,230,240,260)) AND (ts.taxauthid = 1) AND (ts2.taxauthid = 1) '.
				'AND (o.DecimalLatitude BETWEEN '.$lat1.' AND '.$lat2.') AND (o.DecimalLongitude BETWEEN '.$lng1.' AND '.$lng2.') ';
			if($tidFilter){
				$sql .= 'and e.parentTid = '.$tidFilter;
			}
			//echo $sql; exit;
			$this->conn->query($sql);
		}

		return $dynPk;
	}
	
	public function createDynamicChecklist($lat, $lng, $radiusUnit, $tidFilter){
		global $SYMB_UID;
		$dynPK = 0;

		$specCnt = 0;
		$radius;
		$latRadius; $lngRadius;
		$lat1; $lat2; $lng1; $lng2;
		$loopCnt = 1;
		while($specCnt < 2500 && $loopCnt < 10){
			$radius = $radiusUnit*$loopCnt;
			$latRadius = $radius / 69.1;
			$lngRadius = cos($lat / 57.3)*($radius / 69.1);
			$lat1 = $lat - $latRadius;
			$lat2 = $lat + $latRadius;
			$lng1 = $lng - $lngRadius;
			$lng2 = $lng + $lngRadius;
		
			$sql1 = 'SELECT count(tid) AS speccnt '.
				'FROM omoccurgeoindex '.
				'WHERE (DecimalLatitude BETWEEN '.$lat1.' AND '.$lat2.') AND (DecimalLongitude BETWEEN '.$lng1.' AND '.$lng2.')';
			$rs1 = $this->conn->query($sql1);
			if($r1 = $rs1->fetch_object()){
				$specCnt = $r1->speccnt;
			}
			$rs1->free();
			$loopCnt++;
		}
		
		$radius = $radius*1.60934;
		$sql2 = 'INSERT INTO fmdynamicchecklists(name,details,expiration,uid) '.
			'VALUES ("'.round($lat,5).' '.round($lng,5).' within '.round($radius,1).' km","'.$lat.' '.$lng.' within '.$radius.' km","'.
			date('Y-m-d',mktime(0, 0, 0, date('m'), date('d') + 7, date('Y'))).'",'.($SYMB_UID?$SYMB_UID:'NULL').')';
		//echo $sql;
		if($this->conn->query($sql2)){
			$dynPK = $this->conn->insert_id;
			$sql3 = 'INSERT INTO fmdyncltaxalink (dynclid, tid) '.
				'SELECT DISTINCT '.$dynPK.', IF(t.rankid=220,t.tid,ts2.parenttid) as tid '.
				'FROM omoccurgeoindex o INNER JOIN taxstatus ts ON o.tid = ts.tid '.
				'INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tid '.
				'INNER JOIN taxa t ON ts2.tid = t.tid ';
			if($tidFilter){
				$sql3 .= 'INNER JOIN taxaenumtree e ON ts2.tid = e.tid '; 
			}
			$sql3 .= 'WHERE (t.rankid >= 220) AND (ts.taxauthid = 1) AND (ts2.taxauthid = 1) '.
				'AND (o.DecimalLatitude BETWEEN '.$lat1.' AND '.$lat2.') AND (o.DecimalLongitude BETWEEN '.$lng1.' AND '.$lng2.')';
			if($tidFilter){
				$sql3 .= 'and e.parentTid = '.$tidFilter;
			}
			//echo $sql3; exit;
			if(!$this->conn->query($sql3)){
				
			}
		}
		return $dynPK;
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
}
?>