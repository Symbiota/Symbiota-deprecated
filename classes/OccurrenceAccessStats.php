<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once('Manager.php');

class OccurrenceAccessStats {

	private $conn;
	private $collid;
	private $duration;
	private $startDate;
	private $endDate;
	private $ip;
	private $accessType;
	private $occidStr;
	private $pageNum = 0;
	private $limit = 1000;
	private $logFH = null;
	private $errorMessage;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	public function __destruct(){
		if(!($this->conn === null)) $this->conn->close();
	}

	public function recordAccessEventByArr($occidArr, $accessType){
		$status = true;
		foreach($occidArr as $occid){
			if(!$this->recordAccessEvent($occid, $accessType)){
				$status = false;
			}
		}
		return $status;
	}

	public function recordAccessEvent($occid,$accessType){
		$status = false;
		if(is_numeric($occid)){
			$sql = 'INSERT INTO omoccuraccessstats '.
				'SET occid='.$occid.', accessdate="'.date('Y-m-d').'", ipaddress="'.$this->cleanInStr($_SERVER['REMOTE_ADDR']).'", '.
				'cnt=1, accesstype="'.$this->cleanInStr($accessType).'" ON DUPLICATE KEY UPDATE cnt=cnt+1';
			//echo $sql.'<br/>';
			if($this->conn->query($sql)){
				$status = true;
			}
			else{
				$this->errorMessage = date('Y-m-d H:i:s').' - ERROR recording access event: '.$this->conn->error;
				$this->logError($sql);
			}
		}
		return $status;
	}

	public function batchRecordEventsBySql($sqlFrag,$accessType){
		$status = true;
		/*
		$sql = 'INSERT INTO omoccuraccessstats(occid,accessdate,ipaddress,cnt,accesstype) '.
			'SELECT o.occid, "'.date('Y-m-d').'", "'.$this->cleanInStr($_SERVER['REMOTE_ADDR']).'", 1, "'.$this->cleanInStr($accessType).'" ';
		$sql .= $sqlFrag;
		$sql .= 'ON DUPLICATE KEY UPDATE cnt=cnt+1';
		if(!$this->conn->query($sql)){
			$this->errorMessage = date('Y-m-d H:i:s').' - ERROR batch recording access event by SQL: '.$this->conn->error;
			$this->logError($sql);
		}
		*/
		return $status;
	}

	private function logError($sqlStr){
		$logFH = fopen($GLOBALS['SERVER_ROOT'].'/content/logs/statsError_'.date('Y-m-d').'.log', 'a');
		fwrite($logFH,$this->errorMessage."\n");
		fwrite($logFH,$sqlStr."\n");
		fclose($logFH);
	}

	//Reports
	public function getSummaryReport(){
		$retArr = array();
		$sql = 'SELECT '.$this->getDurationSql().' AS timeperiod, a.accesstype, count(a.occid) as speccnt '.
			$this->getSqlBase().
			'GROUP BY timeperiod, a.accesstype ';
		if($this->limit) $sql .= 'LIMIT '.($this->pageNum*$this->limit).','.$this->limit;
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->timeperiod][$r->accesstype] = $r->speccnt;
		}
		$rs->free();
		return $retArr;
	}

	public function getSummaryReportCount(){
		$cnt = 0;
		$sql = 'SELECT COUNT(DISTINCT '.$this->getDurationSql().', a.accesstype) as cnt '.$this->getSqlBase();
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$cnt = $r->cnt;
		}
		$rs->free();
		return $cnt;
	}

	public function getFullReport(){
		$retArr = array();
		$sql = 'SELECT '.$this->getDurationSql().' AS accessdate, a.accesstype, a.occid, SUM(a.cnt) as cnt '.
			$this->getSqlBase().
			'GROUP BY accessdate, a.accesstype, a.occid ';
			if($this->limit) $sql .= 'LIMIT '.($this->pageNum*$this->limit).','.$this->limit;
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->accessdate][$r->accesstype][$r->occid] = $r->cnt;
		}
		$rs->free();
		return $retArr;
	}

	public function getFullReportCount(){
		$cnt = 0;
		$sql = 'SELECT COUNT(DISTINCT '.$this->getDurationSql().', a.accesstype, a.occid) as cnt '.$this->getSqlBase();
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$cnt = $r->cnt;
		}
		$rs->free();
		return $cnt;
	}

	private function getDurationSql(){
		$durationStr = 'accessdate';
		if($this->duration == 'week'){
			$durationStr = 'DATE_FORMAT(a.accessdate,"%Y-%V")';
		}
		elseif($this->duration == 'month'){
			$durationStr = 'DATE_FORMAT(a.accessdate,"%Y-%m")';
		}
		elseif($this->duration == 'year'){
			$durationStr = 'year(a.accessdate)';
		}
		return $durationStr;
	}

	private function getSqlBase(){
		$sqlWhere = '';
		if($this->startDate && $this->endDate){
			$sqlWhere .= 'AND (a.accessdate BETWEEN "'.$this->startDate.'" AND "'.$this->endDate.'") ';
		}
		elseif($this->startDate){
			$sqlWhere .= 'AND (a.accessdate >= "'.$this->startDate.'") ';
		}
		elseif($this->endDate){
			$sqlWhere .= 'AND (a.accessdate <= "'.$this->endDate.'") ';
		}
		if($this->ip) $sqlWhere .= 'AND (a.ipaddress = "'.$this->ip.'") ';
		if($this->accessType) $sqlWhere .= 'AND (a.accesstype = "'.$this->accessType.'") ';
		if($this->occidStr) $sqlWhere .= 'AND (a.occid IN("'.$this->occidStr.'")) ';
		$sql = 'FROM omoccuraccessstats a ';
		if($this->collid) $sql .= 'INNER JOIN omoccurrences o ON a.occid = o.occid WHERE (o.collid = '.$this->collid.') '.$sqlWhere;
		elseif($sqlWhere) $sql .= 'WHERE '.substr($sqlWhere,3);
		return $sql;
	}

	public function exportCsvFile($display){
		$status = true;
		$headerArr = array();
		$recArr = array();
		$this->limit = 0;
		if($display == 'full'){
			$headerArr = array('Date','Access Type','Record #','Record Count');
			$recArr = $this->getFullReport();
		}
		else{
			$periodArr = array('day'=>'Date','week'=>'Year-Week','month'=>'Year-Month','year'=>'Year');
			$headerArr = array($periodArr[$this->duration],'Access Type','Record Count');
			$recArr = $this->getSummaryReport();
		}
		if($recArr){
			$fileName = 'AccessStats_'.time().".csv";
			header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header ('Content-Type: text/csv');
			header ("Content-Disposition: attachment; filename=\"$fileName\"");
			$outFH = fopen('php://output', 'w');
			fputcsv($outFH, $headerArr);
			//Output records
			$accessTypeArr = array('view'=>'Full View','map'=>'Map View','list'=>'List View','download'=>'Record Download','downloadJSON'=>'API JSON Download');
			if($display == 'full'){
				foreach($recArr as $date => $arr1){
					foreach($arr1 as $aType => $arr2){
						foreach($arr2 as $recid => $cnt){
							$outArr = array($date,(isset($accessTypeArr[$aType])?$accessTypeArr[$aType]:''),$recid,$cnt);
							fputcsv($outFH, $outArr);
						}
					}
				}
			}
			else{
				foreach($recArr as $date => $arr1){
					foreach($arr1 as $aType => $cnt){
						$outArr = array($date,(isset($accessTypeArr[$aType])?$accessTypeArr[$aType]:''),$cnt);
						fputcsv($outFH, $outArr);
					}
				}
			}
			fclose($outFH);
		}
		else{
			$status = false;
			$this->errorMessage = "Recordset is empty";
		}
		return $status;
	}

	//Setters and getters
	public function setCollid($id){
		$collName = '';
		if($id && is_numeric($id)){
			$this->collid = $id;
			$sql = 'SELECT collectionname FROM omcollections WHERE (collid = '.$id.')';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$collName = $r->collectionname;
			}
			$rs->free();
		}
		return $collName;
	}

	public function setDuration($durStr){
		if(in_array($durStr, array('day','week','month','year'))) $this->duration = $durStr;
	}

	public function setStartDate($startDate){
		if(preg_match('/^[\d-]+$/', $startDate)) $this->startDate = $startDate;
	}

	public function setEndDate($endDate){
		if(preg_match('/^[\d-]+$/', $endDate)) $this->endDate = $endDate;
	}

	public function setIpAddress($ip){
		if(filter_var($ip, FILTER_VALIDATE_IP)) $this->ip = $ip;
	}

	public function setAccessType($accessType){
		if(preg_match('/^[a-z,A-Z]+$/', $accessType)) $this->accessType = $accessType;
	}

	public function setOccidStr($occidStr){
		if(preg_match('/^[\d,]+$/', $occidStr)) $this->occidStr = $occidStr;
	}

	public function setPageNum($num){
		if(is_numeric($num)) $this->pageNum = $num;
	}

	public function setLimit($l){
		if(is_numeric($l)) $this->limit = $l;
	}

	public function getErrorStr(){
		return $this->errorMessage;
	}

	//Misc fucntions
	private function cleanInStr($str){
		$newStr = trim($str);
		if($newStr){
			$newStr = preg_replace('/\s\s+/', ' ',$newStr);
			$newStr = $this->conn->real_escape_string($newStr);
		}
		return $newStr;
	}
}
?>