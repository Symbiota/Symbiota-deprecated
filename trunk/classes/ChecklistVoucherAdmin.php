<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class ChecklistVoucherAdmin {

	private $conn;
	private $clid;
	private $clName;
	private $sqlFrag;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}
	
	public function setClid($clid){
		if(is_numeric($clid)){
			$this->clid = $clid;
			$sql = 'SELECT name, dynamicsql FROM fmchecklists WHERE (clid = '.$this->clid.')';
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$this->clName = $this->cleanOutStr($row->name);
				$this->sqlFrag = $row->dynamicsql;
			}
			else{
				$this->clName = 'Unknown';
			}
			$result->free();
		}
	}
	
	public function getClName(){
		return $this->clName;
	}

	public function getDynamicSql(){
		return $this->sqlFrag;
	}
	
	public function saveSql($sqlFragArr){
		$statusStr = false;
		$sqlFrag = "";
		if($sqlFragArr['country']){
			$sqlFrag = 'AND (o.country = "'.$this->cleanInStr($sqlFragArr['country']).'") ';
		}
		if($sqlFragArr['state']){
			$sqlFrag .= 'AND (o.stateprovince = "'.$this->cleanInStr($sqlFragArr['state']).'") ';
		}
		if($sqlFragArr['county']){
			$sqlFrag .= 'AND (o.county LIKE "'.$this->cleanInStr($sqlFragArr['county']).'%") ';
		}
		if($sqlFragArr['locality']){
			$sqlFrag .= 'AND (o.locality LIKE "%'.$this->cleanInStr($sqlFragArr['locality']).'%") ';
		}
		$llStr = '';
		if($sqlFragArr['latnorth'] && $sqlFragArr['latsouth'] && is_numeric($sqlFragArr['latnorth']) && is_numeric($sqlFragArr['latsouth'])){
			$llStr .= 'AND (o.decimallatitude BETWEEN '.$sqlFragArr['latsouth'].' AND '.$sqlFragArr['latnorth'].') ';
		}
		if($sqlFragArr['lngwest'] && $sqlFragArr['lngeast'] && is_numeric($sqlFragArr['lngwest']) && is_numeric($sqlFragArr['lngeast'])){
			$llStr .= 'AND (o.decimallongitude BETWEEN '.$sqlFragArr['lngwest'].
			' AND '.$sqlFragArr['lngeast'].') ';
		}
		if(array_key_exists('latlngor',$sqlFragArr)) $llStr = 'OR ('.trim(substr($llStr,3)).')';
		$sqlFrag .= $llStr;
		if($sqlFrag){
			$sql = "UPDATE fmchecklists c SET c.dynamicsql = '".trim(substr($sqlFrag,3))."' WHERE (c.clid = ".$this->clid.')';
			//echo $sql;
			if(!$this->conn->query($sql)){
				$statusStr = 'ERROR: unable to create or modify search statement ('.$this->error.')';
			}
		}
		return $statusStr;
	}

	public function deleteSql(){
		$statusStr = '';
		if(!$this->conn->query('UPDATE fmchecklists c SET c.dynamicsql = NULL WHERE (c.clid = '.$this->clid.')')){
			$statusStr = 'ERROR: '.$this->conn->query->error;
		}
		return $statusStr;
	}

	//Listing function for tabs
	public function getVoucherCnt(){
		$vCnt = 0;
		$sql = 'SELECT count(*) AS vcnt FROM fmvouchers WHERE (clid = '.$this->clid.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$vCnt = $r->vcnt;
		}
		$rs->free();
		return $vCnt;
	}

	public function getNonVoucheredCnt(){
		$uvCnt = 0;
		$sql = 'SELECT count(t.tid) AS uvcnt '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'INNER JOIN fmchklsttaxalink ctl ON t.tid = ctl.tid '.
			'LEFT JOIN fmvouchers v ON ctl.clid = v.clid AND ctl.tid = v.tid '.
			'WHERE v.clid IS NULL AND (ctl.clid = '.$this->clid.') AND ts.taxauthid = 1 ';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$uvCnt = $row->uvcnt;
		}
		$rs->free();
		return $uvCnt;
	}

	public function getNonVoucheredTaxa($startLimit,$limit = 100){
		$retArr = Array();
		$sql = 'SELECT t.tid, ts.family, t.sciname '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'INNER JOIN fmchklsttaxalink ctl ON t.tid = ctl.tid '.
			'LEFT JOIN fmvouchers v ON ctl.clid = v.clid AND ctl.tid = v.tid '.
			'WHERE v.clid IS NULL AND (ctl.clid = '.$this->clid.') AND ts.taxauthid = 1 '.
			'ORDER BY ts.family, t.sciname '.
			'LIMIT '.($startLimit?$startLimit.',':'').$limit;
		//echo '<div>'.$sql.'</div>';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->family][$row->tid] = $this->cleanOutStr($row->sciname);
		}
		$rs->free();
		return $retArr;
	}

	public function getNonVoucheredSpecimens($startLimit){
		$retArr = Array();
		$taxaArr = $this->getNonVoucheredTaxa($startLimit,50);
		$tidArr = array();
		foreach($taxaArr as $vArr){
			foreach($vArr as $tid => $sciName){
				$tidArr[$tid] = $sciName;
			}
		}
		flush();
		ob_flush();
		if($tidArr){
			$sql = 'SELECT ts2.tid AS cltid, o.occid, CONCAT_WS(":",c.institutioncode,c.collectioncode,o.catalognumber) AS collcode, '. 
				'o.tidinterpreted, o.sciname, o.recordedby, o.recordnumber, o.eventdate, '.
				'CONCAT_WS("; ",o.country, o.stateprovince, o.county, o.locality) as locality '.
				'FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid '.
				'INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid '.
				'INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tidaccepted '.
				'WHERE ('.$this->sqlFrag.') AND (o.occid NOT IN (SELECT occid FROM fmvouchers WHERE clid = '.$this->clid.')) '.
				'AND (ts.taxauthid = 1) AND (ts2.taxauthid = 1) AND ts2.tid IN ('.implode(',',array_keys($tidArr)).') '.
				'ORDER BY ts.family, o.sciname ';
			//echo '<div>'.$sql.'</div>';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->cltid][$r->occid]['tid'] = $r->tidinterpreted;
				$sciName = '<b>'.$tidArr[$r->cltid].'</b>';
				if($tidArr[$r->cltid] <> $r->sciname) $sciName .= '<br/>spec id: '.$r->sciname;
				$retArr[$r->cltid][$r->occid]['sciname'] = $sciName;
				$retArr[$r->cltid][$r->occid]['collcode'] = $r->collcode;
				$retArr[$r->cltid][$r->occid]['recordedby'] = $r->recordedby;
				$retArr[$r->cltid][$r->occid]['recordnumber'] = $r->recordnumber;
				$retArr[$r->cltid][$r->occid]['eventdate'] = $r->eventdate;
				$retArr[$r->cltid][$r->occid]['locality'] = $r->locality;
			}
			$rs->free();
		}
		
		return $retArr;
	}

	public function getConflictVouchers(){
		$retArr = Array();
		$sql = 'SELECT t.tid, t.sciname AS listid, o.recordedby, o.recordnumber, o.sciname, o.identifiedby, o.dateidentified '.
			'FROM taxstatus ts1 INNER JOIN omoccurrences o ON ts1.tid = o.tidinterpreted '.
			'INNER JOIN fmvouchers v ON o.occid = v.occid '.
			'INNER JOIN taxstatus ts2 ON v.tid = ts2.tid '.
			'INNER JOIN taxa t ON v.tid = t.tid '.
			'WHERE (v.clid = '.$this->clid.') AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND ts1.tidaccepted <> ts2.tidaccepted '.
			'ORDER BY t.sciname ';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->tid]['listid'] = $row->listid;
			$collStr = $row->recordedby;
			if($row->recordnumber) $collStr .= ' ('.$row->recordnumber.')';
			$retArr[$row->tid]['recordnumber'] = $this->cleanOutStr($collStr);
			$retArr[$row->tid]['specid'] = $this->cleanOutStr($row->sciname);
			$idBy = $row->identifiedby;
			if($row->dateidentified) $idBy .= ' ('.$this->cleanOutStr($row->dateidentified).')';
			$retArr[$row->tid]['identifiedby'] = $this->cleanOutStr($idBy);
		}
		$rs->free();
		return $retArr;
	}

	public function getMissingTaxa($startLimit){
		$retArr = Array();
		if($this->sqlFrag){
			$sql = 'SELECT DISTINCT o.tidinterpreted, o.sciname FROM omoccurrences o LEFT JOIN '.
				'(SELECT ts1.tid FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN fmchklsttaxalink ctl ON ts2.tid = ctl.tid '.
				'WHERE (ctl.clid = '.$this->clid.') AND ts1.taxauthid = 1 AND ts2.taxauthid = 1) intab ON o.tidinterpreted = intab.tid '.
				'INNER JOIN taxa t ON o.tidinterpreted = t.tid '.
				'WHERE t.rankid >= 220 AND intab.tid IS NULL AND '.
				'('.$this->sqlFrag.') '.
				'ORDER BY o.sciname '.
				'LIMIT '.($startLimit?$startLimit.',':'').'105';
			//echo '<div>'.$sql.'</div>';
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$retArr[$row->tidinterpreted] = $this->cleanOutStr($row->sciname);
			}
			$rs->free();
		}
		return $retArr;
	}

	public function hasChildrenChecklists(){
		$hasChildren = false;
		$sql = 'SELECT count(*) AS clcnt FROM fmchecklists WHERE (parentclid = '.$this->clid.')';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			if($row->clcnt > 0) $hasChildren = true;
		}
		$rs->free();
		return $hasChildren;
	}

	public function getChildTaxa(){
		$retArr = Array();
		$sql = 'SELECT DISTINCT t.tid, t.sciname, c.name '.
			'FROM taxa t INNER JOIN fmchklsttaxalink ctl1 ON t.tid = ctl1.tid '.
			'INNER JOIN fmchecklists c ON ctl1.clid = c.clid '.
			'LEFT JOIN (SELECT ts1.tid FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
			'INNER JOIN fmchklsttaxalink ctl ON ts2.tid = ctl.tid WHERE (ctl.clid = '.$this->clid.')) intab ON ctl1.tid = intab.tid '.
			'WHERE (c.parentclid = '.$this->clid.') AND intab.tid IS NULL '.
			'ORDER BY t.sciname';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->tid]['sciname'] = $r->sciname;
			$retArr[$r->tid]['cl'] = $r->name;
		}
		$rs->free();
		return $retArr;
	}
	
	//Export functions
	public function exportMissingOccurCsv(){
    	global $defaultTitle, $userRights, $isAdmin;
		$canReadRareSpp = false;
		if($isAdmin || array_key_exists("CollAdmin", $userRights) || array_key_exists("RareSppAdmin", $userRights) || array_key_exists("RareSppReadAll", $userRights)){
			$canReadRareSpp = true;
		}
    	$fileName = $defaultTitle;
		if($fileName){
			if(strlen($fileName) > 10){
				$nameArr = explode(" ",$fileName);
				$fileName = $nameArr[0];
			}
			$fileName = str_replace(Array("."," ",":"),"",$fileName);
		}
		else{
			$fileName = "symbiota";
		}
		$fileName .= "_voucher_".time().".csv";
		header ('Content-Type: text/csv');
		header ("Content-Disposition: attachment; filename=\"$fileName\""); 

		$sql = 'SELECT o.family, o.sciname, c.institutioncode, o.catalognumber, o.identifiedby, o.dateidentified, '.
			'o.recordedby, o.recordnumber, o.eventdate, o.country, o.stateprovince, o.county, o.municipality, o.locality, '.
			'o.decimallatitude, o.decimallongitude, o.minimumelevationinmeters, o.habitat, o.occurrenceremarks, o.occid, '.
			'o.localitysecurity, o.collid '.
			'FROM omoccurrences o LEFT JOIN '.
			'(SELECT ts1.tid FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
			'INNER JOIN fmchklsttaxalink ctl ON ts2.tid = ctl.tid '.
			'WHERE (ctl.clid = '.$this->clid.') AND ts1.taxauthid = 1 AND ts2.taxauthid = 1) intab ON o.tidinterpreted = intab.tid '.
			'INNER JOIN omcollections c ON o.collid = c.collid '.
			'WHERE intab.tid IS NULL AND ('.$this->sqlFrag.') '.
			'ORDER BY o.family, o.sciname, c.institutioncode ';
		//echo '<div>'.$sql.'</div>';
		if($rs = $this->conn->query($sql)){
			echo '"family","scientificName","institutionCode","catalogNumber","identifiedBy","dateIdentified",'.
 			'"recordedBy","recordNumber","eventDate","country","stateProvince","county","municipality","locality",'.
 			'"decimalLatitude","decimalLongitude","minimumElevationInMeters","habitat","occurrenceRemarks","occid"'."\n";
			
			while($row = $rs->fetch_assoc()){
				echo '"'.$row["family"].'","'.$row["sciname"].'","'.$row["institutioncode"].'","'.
					$row["catalognumber"].'","'.$row["identifiedby"].'","'.
					$row["dateidentified"].'","'.$row["recordedby"].'","'.
					$row["recordnumber"].'","'.$row["eventdate"].'","'.$row["country"].'","'.$row["stateprovince"].'","'.
					$row["county"].'","'.$row["municipality"].'",';
				
				$localSecurity = ($row["localitysecurity"]?$row["localitysecurity"]:0); 
				if($canReadRareSpp || $localSecurity != 1 || (array_key_exists("RareSppReader", $userRights) && in_array($row["collid"],$userRights["RareSppReader"]))){
					echo '"'.$row["locality"].'",'.$row["decimallatitude"].','.$row["decimallongitude"].','.
					$row["minimumelevationinmeters"].',"'.$row["habitat"].'","'.$row["occurrenceremarks"].'",';
				}
				else{
					echo '"Value Hidden","Value Hidden","Value Hidden","Value Hidden","Value Hidden","Value Hidden",';
				}
				echo '"'.$row["occid"]."\"\n";
			}
        	$rs->free();
		}
		else{
			echo "Recordset is empty.\n";
		}
	} 

	public function downloadDatasetCsv(){
		if($this->clid){
			$sql = 'SELECT DISTINCT t.tid, ts.uppertaxonomy, IFNULL(ctl.familyoverride,ts.family) AS family, '.
				't.sciname, t.author, ctl.habitat, ctl.abundance, ctl.notes, ctl.source '.
				'FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid) '.
				'INNER JOIN fmchklsttaxalink ctl ON ctl.tid = t.tid '.
	      		'WHERE (ts.taxauthid = 1) AND (ctl.clid = '.$this->clid.')';
	    	$fileName = $this->clName."_".time().".csv";
	    	header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header ('Content-Type: text/csv');
			header ("Content-Disposition: attachment; filename=\"$fileName\"");
			if($taxaArr = $this->getTaxaList(1,0)){
				echo "Family,ScientificName,ScientificNameAuthorship,";
				echo "TaxonId\n";
				foreach($taxaArr as $tid => $tArr){
					echo '"'.$this->cleanOutStr($tArr['family']).'","'.$this->cleanOutStr($tArr['sciname']).'","'.$this->cleanOutStr($tArr['author']).'"';
					echo ',"'.$tid.'"'."\n";
				}
			}
			else{
				echo "Recordset is empty.\n";
			}
		}
    }

    //Voucher loading functions
	public function linkVouchers($occidArr){
		$retStatus = '';
		$sqlFrag = '';
		foreach($occidArr as $v){
			$vArr = explode('-',$v);
			if(count($vArr) == 2 && $vArr[0] && $vArr[1]) $sqlFrag .= ',('.$this->clid.','.$vArr[0].','.$vArr[1].')';
		}
		$sql = 'INSERT INTO fmvouchers(clid,occid,tid) VALUES '.substr($sqlFrag,1);
		if(!$this->conn->query($sql)){
			//Error
			trigger_error('Unable to link voucher; '.$this->conn->error,E_USER_WARNING);
		}
		return $retStatus;
	}
    
	public function linkVoucher($tid,$occid,$addNewNameToCl = 1){
		$sql = 'INSERT INTO fmvouchers(clid,tid,occid,collector) '.
			'VALUES ('.$this->clid.','.$tid.','.$occid.',"")';
		if($this->conn->query($sql)){
			return true;
		}
		else{
			if($this->conn->errno == 1062){
				echo 'Specimen already a voucher for checklist ';
			}
			else{
				//trigger_error('Attempting to resolve by adding species to checklist; '.$this->conn->error,E_USER_WARNING);
				$sql2 = 'INSERT INTO fmchklsttaxalink(tid,clid) VALUES('.$tid.','.$this->clid.')';
				if($this->conn->query($sql2)){
					if($this->conn->query($sql)){
						return true;
					}
					else{
						//echo 'Name added to list, though still unable to link voucher';
						trigger_error('Name added to checklist, though still unable to link voucher": '.$this->conn->error,E_USER_WARNING);
					}
				}
				else{
					//echo 'Unable to link voucher; unknown error';
					trigger_error('Unable to link voucher; '.$this->conn->error,E_USER_WARNING);
				}
			}
		}
	}

	//Misc fucntions
	private function cleanOutStr($str){
		$str = str_replace('"',"&quot;",$str);
		$str = str_replace("'","&apos;",$str);
		return $str;
	}
	
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>