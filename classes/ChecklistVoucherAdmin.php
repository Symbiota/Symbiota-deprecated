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
	
	public function saveSql($postArr){
		$statusStr = false;
		$sqlFrag = "";
		if($postArr['country']){
			$sqlFrag = 'AND (o.country = "'.$this->cleanInStr($postArr['country']).'") ';
		}
		if($postArr['state']){
			$sqlFrag .= 'AND (o.stateprovince = "'.$this->cleanInStr($postArr['state']).'") ';
		}
		if($postArr['county']){
			$sqlFrag .= 'AND (o.county LIKE "'.$this->cleanInStr($postArr['county']).'%") ';
		}
		if($postArr['locality']){
			$sqlFrag .= 'AND (o.locality LIKE "%'.$this->cleanInStr($postArr['locality']).'%") ';
		}
		//taxonomy
		if($postArr['taxon']){
			$tStr = $this->cleanInStr($postArr['taxon']);
			$tidPar = $this->getTid($tStr);
			if($tidPar){
				$sqlFrag .= 'AND (o.tidinterpreted IN (SELECT tid FROM taxaenumtree WHERE taxauthid = 1 AND parenttid = '.$tidPar.')) ';
			}
			/*
			if(strpos($tStr,'aceae') || strpos($tStr,'idae')){
				$sqlFrag .= 'AND (o.family LIKE "'.$tStr.'") '; 
			}
			else{
				$sqlFrag .= 'AND (o.sciname LIKE "'.$tStr.'%") ';
			}
			*/
		}
		//Latitude and longitude
		$llStr = '';
		if($postArr['latnorth'] && $postArr['latsouth'] && is_numeric($postArr['latnorth']) && is_numeric($postArr['latsouth'])){
			$llStr .= 'AND (o.decimallatitude BETWEEN '.$postArr['latsouth'].' AND '.$postArr['latnorth'].') ';
		}
		if($postArr['lngwest'] && $postArr['lngeast'] && is_numeric($postArr['lngwest']) && is_numeric($postArr['lngeast'])){
			$llStr .= 'AND (o.decimallongitude BETWEEN '.$postArr['lngwest'].' AND '.$postArr['lngeast'].') ';
		}
		if($llStr){
			if(array_key_exists('latlngor',$postArr)) $llStr = 'OR ('.trim(substr($llStr,3)).') ';
			$sqlFrag .= $llStr;
		}
		//Use occurrences only with decimallatitude
		if(!$llStr && isset($postArr['onlycoord']) && $postArr['onlycoord']){
			$sqlFrag .= 'AND (o.decimallatitude IS NOT NULL) ';
		}
		//Exclude taxonomy
		if(isset($postArr['excludecult']) && $postArr['excludecult']){
			$sqlFrag .= 'AND (o.cultivationStatus = 0 OR o.cultivationStatus IS NULL) ';
		}
		//Limit by collection
		if($postArr['collid'] && is_numeric($postArr['collid'])){
			$sqlFrag .= 'AND (o.collid = '.$postArr['collid'].') ';
		}

		//Limit by collector
		if($postArr['recordedby']){
			$sqlFrag .= 'AND (o.recordedby LIKE "%'.$this->cleanInStr($postArr['recordedby']).'%") ';
		}

		//Save SQL fragment
		if($sqlFrag){
			$sqlFrag = trim(substr($sqlFrag,3));
			$sql = "UPDATE fmchecklists c SET c.dynamicsql = '".$sqlFrag."' WHERE (c.clid = ".$this->clid.')';
			//echo $sql;
			if($this->conn->query($sql)){
				$this->sqlFrag = $sqlFrag;
			}
			else{
				$statusStr = 'ERROR: unable to create or modify search statement ('.$this->conn->error.')';
			}
		}
		return $statusStr;
	}

	public function deleteSql(){
		$statusStr = '';
		if($this->conn->query('UPDATE fmchecklists c SET c.dynamicsql = NULL WHERE (c.clid = '.$this->clid.')')){
			$this->sqlFrag = '';
		}
		else{
			$statusStr = 'ERROR: '.$this->conn->error;
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

	public function getNewVouchers($startLimit = 500,$includeAll = 0){
		$retArr = Array();
		if($this->sqlFrag){
			$sql = 'SELECT DISTINCT cl.tid AS cltid, t.sciname AS clsciname, o.occid, '. 
				'CONCAT_WS(":",c.institutioncode,c.collectioncode,IFNULL(o.catalognumber,"<no catalog number")) AS collcode, '. 
				'o.tidinterpreted, o.sciname, o.recordedby, o.recordnumber, o.eventdate, '.
				'CONCAT_WS("; ",o.country, o.stateprovince, o.county, o.locality) as locality '.
				'FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid '.
				'INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid '.
				'INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN fmchklsttaxalink cl ON ts2.tidaccepted = cl.tid '.
				'INNER JOIN taxa t ON cl.tid = t.tid '.
				'WHERE ('.$this->sqlFrag.') AND (cl.clid = '.$this->clid.') AND (ts.taxauthid = 1) AND (ts2.taxauthid = 1) ';
			if(!$includeAll){
				$sql .= 'AND cl.tid NOT IN(SELECT tid FROM fmvouchers WHERE clid = '.$this->clid.') ';
			}
			else{
				$sql .= 'AND o.occid NOT IN(SELECT occid FROM fmvouchers WHERE clid = 2) '; 
			}
			$sql .= 'ORDER BY ts.family, o.sciname LIMIT '.$startLimit.', 500';
			//echo '<div>'.$sql.'</div>';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->cltid][$r->occid]['tid'] = $r->tidinterpreted;
				$sciName = $r->clsciname;
				if($r->clsciname <> $r->sciname) $sciName .= '<br/>spec id: '.$r->sciname;
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
		$sql = 'SELECT DISTINCT t.tid, t.sciname AS listid, o.recordedby, o.recordnumber, o.sciname, o.identifiedby, o.dateidentified, o.occid '.
			'FROM taxstatus ts1 INNER JOIN omoccurrences o ON ts1.tid = o.tidinterpreted '. 
			'INNER JOIN fmvouchers v ON o.occid = v.occid '. 
			'INNER JOIN taxstatus ts2 ON v.tid = ts2.tid '. 
			'INNER JOIN taxa t ON v.tid = t.tid '. 
			'INNER JOIN taxstatus ts3 ON ts1.tidaccepted = ts3.tid '. 
			'WHERE (v.clid = '.$this->clid.') AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND ts1.tidaccepted <> ts2.tidaccepted '.
			'AND ts1.parenttid <> ts2.tidaccepted AND v.tid <> o.tidinterpreted AND ts3.parenttid <> v.tid '.
			'ORDER BY t.sciname ';
		//echo $sql;
		$rs = $this->conn->query($sql);
		$cnt = 0;
		while($row = $rs->fetch_object()){
			$clSciname = $row->listid;
			$voucherSciname = $row->sciname;
			//if(str_replace($voucherSciname)) continue;
			$retArr[$cnt]['tid'] = $row->tid;
			$retArr[$cnt]['occid'] = $row->occid;
			$retArr[$cnt]['listid'] = $clSciname;
			$collStr = $row->recordedby;
			if($row->recordnumber) $collStr .= ' ('.$row->recordnumber.')';
			$retArr[$cnt]['recordnumber'] = $this->cleanOutStr($collStr);
			$retArr[$cnt]['specid'] = $this->cleanOutStr($voucherSciname);
			$idBy = $row->identifiedby;
			if($row->dateidentified) $idBy .= ' ('.$this->cleanOutStr($row->dateidentified).')';
			$retArr[$cnt]['identifiedby'] = $this->cleanOutStr($idBy);
			$cnt++;
		}
		$rs->free();
		return $retArr;
	}

	public function getMissingTaxa(){
		$retArr = Array();
		if($this->sqlFrag){
			
			$sql = 'SELECT DISTINCT o.tidinterpreted, o.sciname, IFNULL(o.cultivationstatus,0) as culstat FROM omoccurrences o '. 
				'WHERE ('.$this->sqlFrag.') AND o.tidinterpreted NOT IN(SELECT ts1.tid FROM taxstatus ts1 '.
				'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '. 
				'INNER JOIN fmchklsttaxalink ctl ON ts2.tid = ctl.tid '. 
				'WHERE (ctl.clid = '.$this->clid.') AND ts1.taxauthid = 1 AND ts2.taxauthid = 1)';
			
			//echo '<div>'.$sql.'</div>';
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				if(strpos($row->sciname,' ') && !$row->culstat){
					$retArr[$row->tidinterpreted] = $this->cleanOutStr($row->sciname);
				}
			}
			asort($retArr);
			$rs->free();
		}
		return $retArr;
	}

	public function getMissingTaxaSpecimens(){
		$retArr = Array();
		if($this->sqlFrag){
			$sql = 'SELECT DISTINCT o.occid, CONCAT_WS(":",c.institutioncode,c.collectioncode,IFNULL(o.catalognumber,"<no catalog number>")) AS collcode, '.
				'o.tidinterpreted, o.sciname, o.recordedby, o.recordnumber, o.eventdate, '.
				'CONCAT_WS("; ",o.country, o.stateprovince, o.county, o.locality) as locality, IFNULL(o.cultivationstatus,0) as culstat '.
				'FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid '.
				'INNER JOIN taxa t ON o.tidinterpreted = t.tid '.
				'WHERE t.rankid >= 220 AND ('.$this->sqlFrag.') '.
				'AND o.tidinterpreted NOT IN (SELECT ts1.tid FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN fmchklsttaxalink ctl ON ts2.tid = ctl.tid '.
				'WHERE (ctl.clid = '.$this->clid.') AND ts1.taxauthid = 1 AND ts2.taxauthid = 1)';
			//echo '<div>'.$sql.'</div>';
			$rs = $this->conn->query($sql);
			$spTidArr = array();
			while($r = $rs->fetch_object()){
				if(!$r->culstat){
					$retArr[$r->sciname][$r->occid]['tid'] = $r->tidinterpreted;
					$retArr[$r->sciname][$r->occid]['collcode'] = $r->collcode;
					$retArr[$r->sciname][$r->occid]['recordedby'] = $r->recordedby;
					$retArr[$r->sciname][$r->occid]['recordnumber'] = $r->recordnumber;
					$retArr[$r->sciname][$r->occid]['eventdate'] = $r->eventdate;
					$retArr[$r->sciname][$r->occid]['locality'] = $r->locality;
				}
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getMissingProblemTaxa(){
		$retArr = Array();
		if($this->sqlFrag){
			//Make sure tidinterpreted are valid 
			//$this->conn->query('UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname SET o.tidinterpreted = t.tid WHERE o.tidinterpreted IS NULL');
			//Grab records
			$sql = 'SELECT DISTINCT o.occid, CONCAT_WS(":",c.institutioncode,c.collectioncode,IFNULL(o.catalognumber,"<no catalog number>")) AS collcode, '.
				'o.sciname, o.recordedby, o.recordnumber, o.eventdate, '.
				'CONCAT_WS("; ",o.country, o.stateprovince, o.county, o.locality) as locality '.
				'FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid '.
				'WHERE (o.occid NOT IN (SELECT occid FROM fmvouchers WHERE clid = '.$this->clid.')) AND ('.$this->sqlFrag.') '.
				'AND o.tidinterpreted IS NULL AND o.sciname IS NOT NULL ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$sciname = $r->sciname;
				if($sciname){
					$retArr[$sciname][$r->occid]['collcode'] = $r->collcode;
					$retArr[$sciname][$r->occid]['recordedby'] = $r->recordedby;
					$retArr[$sciname][$r->occid]['recordnumber'] = $r->recordnumber;
					$retArr[$sciname][$r->occid]['eventdate'] = $r->eventdate;
					$retArr[$sciname][$r->occid]['locality'] = $r->locality;
				}
			}
			$rs->free();
		}
		return $retArr;
	}
	
	//Export functions used within voucherreporthandler.php
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
			'FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid '.
			'WHERE o.tidinterpreted NOT IN(SELECT ts1.tid FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
			'INNER JOIN fmchklsttaxalink ctl ON ts2.tid = ctl.tid '.
			'WHERE (ctl.clid = '.$this->clid.') AND ts1.taxauthid = 1 AND ts2.taxauthid = 1) AND ('.$this->sqlFrag.') '.
			'ORDER BY o.family, o.sciname, c.institutioncode ';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			echo $this->arrayToCsv(array("family","scientificName","institutionCode","catalogNumber","identifiedBy","dateIdentified",
 			"recordedBy","recordNumber","eventDate","country","stateProvince","county","municipality","locality",
 			"decimalLatitude","decimalLongitude","minimumElevationInMeters","habitat","occurrenceRemarks","occid"));
			
			while($row = $rs->fetch_assoc()){
				$localSecurity = ($row["localitysecurity"]?$row["localitysecurity"]:0); 
				if(!$canReadRareSpp && $localSecurity != 1 && (!array_key_exists("RareSppReader", $userRights) || !in_array($row["collid"],$userRights["RareSppReader"]))){
					if($row['recordnumber']) $row['recordnumber'] = 'Redacted';
					if($row['eventdate']) $row['eventdate'] = 'Redacted';
					if($row["locality"]) $row["locality"] = "Redacted";
					if($row["decimallatitude"]) $row["decimallatitude"] = "Redacted";
					if($row["decimallongitude"]) $row["decimallongitude"] = "Redacted";
					if($row["minimumelevationinmeters"]) $row["minimumelevationinmeters"] = "Redacted";
					if($row["habitat"]) $row["habitat"] = "Redacted";
					if($row["occurrenceremarks"]) $row["occurrenceremarks"] = "Redacted";
				}
				unset($row['localitysecurity']);
				unset($row['collid']);
				echo $this->arrayToCsv($row);
			}
        	$rs->free();
		}
		else{
			echo "Recordset is empty.\n";
		}
	} 

	public function exportProblemTaxaCsv(){
    	global $defaultTitle;
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
		$fileName .= "ProblemTaxa_".time().".csv";
		header ('Content-Type: text/csv');
		header ("Content-Disposition: attachment; filename=\"$fileName\""); 

		$sql = 'SELECT DISTINCT o.occid, c.institutioncode, c.collectioncode, o.catalognumber, '.
			'o.sciname, o.recordedby, o.recordnumber, o.eventdate, o.country, o.stateprovince, o.county, o.locality, o.localitysecurity, o.collid '.
			'FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid '.
			'WHERE (o.occid NOT IN (SELECT occid FROM fmvouchers WHERE clid = '.$this->clid.')) AND ('.$this->sqlFrag.') '.
			'AND o.tidinterpreted IS NULL AND o.sciname IS NOT NULL ';
		//echo '<div>'.$sql.'</div>';return;
		if($rs = $this->conn->query($sql)){
			echo $this->arrayToCsv(array('occid','institutionCode','collectionCode','catalogNumber','scientificName',
				'recordedBy','recordNumber','eventDate','country','stateProvince','county','locality'));
			while($r = $rs->fetch_assoc()){
				$localSecurity = ($r["localitysecurity"]?$r["localitysecurity"]:0); 
				if(!$canReadRareSpp && $localSecurity != 1 && (!array_key_exists("RareSppReader", $userRights) || !in_array($r["collid"],$userRights["RareSppReader"]))){
					if($r["eventdate"]) $r["eventdate"] = "Redacted";
					if($r["recordnumber"]) $r["recordnumber"] = "Redacted";
					if($r["locality"]) $r["locality"] = "Redacted";
				}
				unset($r['localitysecurity']);
				unset($r['collid']);
				echo $this->arrayToCsv($r);
			}
        	$rs->free();
		}
		else{
			echo "Recordset is empty.\n";
		}
	} 

	public function downloadDatasetCsv($includeDetails = 0){
    	global $defaultTitle, $userRights, $isAdmin;
		$canReadRareSpp = false;
		if($isAdmin || array_key_exists("CollAdmin", $userRights) || array_key_exists("RareSppAdmin", $userRights) || array_key_exists("RareSppReadAll", $userRights)){
			$canReadRareSpp = true;
		}
		if($this->clid){
			//Set SQL
			$sql = 'SELECT DISTINCT t.tid, IFNULL(ctl.familyoverride,ts.family) AS family, t.sciname, t.author, ';
			if($includeDetails){
				$sql .= 'ctl.habitat, ctl.abundance, ctl.notes, ctl.source, v.editornotes, o.occid, o.catalognumber, o.othercatalognumbers, o.sciname AS specsciname, '.
					'o.recordedby, o.recordnumber, o.associatedcollectors, o.eventdate, o.year, o.month, o.day, o.startdayofyear, o.verbatimeventdate, o.country, o.stateprovince, o.county, '.
					'o.locality, o.localitysecurity, o.collid, o.habitat AS spechabitat, o.occurrenceremarks, o.associatedtaxa, o.dynamicproperties, o.reproductivecondition, o.decimallatitude, '.
					'o.decimallongitude, o.geodeticdatum, o.minimumelevationinmeters, o.maximumelevationinmeters, o.verbatimelevation, o.labelproject ';
			}
			$sql .= 'FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid) '.
				'INNER JOIN fmchklsttaxalink ctl ON ctl.tid = t.tid ';
			if($includeDetails) $sql .= 'LEFT JOIN fmvouchers v ON ctl.clid = v.clid AND ctl.tid = v.tid LEFT JOIN omoccurrences o ON v.occid = o.occid ';
			$sql .= 'WHERE (ts.taxauthid = 1) AND (ctl.clid = '.$this->clid.') ';
			//Set header
			$headerArr = array('tid','family','scientificName','scientificNameAuthorship');
			if($includeDetails){
				$headerArr = array_merge($headerArr, array('habitat','abundance','notes','source','editornotes','occid','catalognumber','othercatalognumbers','specimenSciname','collector','collectorNumber','associatedCollectors','collectionDate','year','month','day','startdayofyear','verbatimEventDate','country','stateProvince','county','locality','specHabitat','occurrenceRemarks','associatedTaxa','dynamicProperties','reproductiveCondition','decimalLatitude','decimalLongitude','geodeticDatum','minimumElevationInMeters','maximumElevationInMeters','verbatimElevation','labelProject')); 
			}
			//Output file
			$fileName = $this->clName."_".time().".csv";
	    	header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header ('Content-Type: text/csv');
			header ("Content-Disposition: attachment; filename=\"$fileName\"");
			if($rs = $this->conn->query($sql)){
				echo $this->arrayToCsv($headerArr);
				while($r = $rs->fetch_assoc()){
					if($includeDetails){
						$localSecurity = ($r["localitysecurity"]?$r["localitysecurity"]:0); 
						if(!$canReadRareSpp && $localSecurity != 1 && (!array_key_exists("RareSppReader", $userRights) || !in_array($r["collid"],$userRights["RareSppReader"]))){
							if($r["recordnumber"]) $r["recordnumber"] = "Redacted";
							if($r["eventdate"]) $r["eventdate"] = "Redacted";
							if($r["day"]) $r["day"] = "Redacted";
							if($r["startdayofyear"]) $r["startdayofyear"] = "Redacted";
							if($r["verbatimeventdate"]) $r["verbatimeventdate"] = "Redacted";
							if($r["locality"]) $r["locality"] = "Redacted";
							if($r["decimallatitude"]) $r["decimallatitude"] = "Redacted";
							if($r["decimallongitude"]) $r["decimallongitude"] = "Redacted";
							if($r["minimumelevationinmeters"]) $r["minimumelevationinmeters"] = "Redacted";
							if($r["habitat"]) $r["habitat"] = "Redacted";
							if($r["occurrenceremarks"]) $r["occurrenceremarks"] = "Redacted";
						}
						unset($r['localitysecurity']);
						unset($r['collid']);
					}
					echo $this->arrayToCsv($r);
				}
				$rs->free();
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
		//echo $sql;
		if(!$this->conn->query($sql)){
			trigger_error('Unable to link voucher; '.$this->conn->error,E_USER_WARNING);
		}
		return $retStatus;
	}
    
	public function linkVoucher($taxa,$occid){
		if(!is_numeric($taxa)){
			$rs = $this->conn->query('SELECT tid FROM taxa WHERE (sciname = "'.$this->conn->real_escape_string($taxa).'")');
			if($r = $rs->fetch_object()){
				$taxa = $r->tid;
			}
		}
		$sql = 'INSERT INTO fmvouchers(clid,tid,occid,collector) '.
			'VALUES ('.$this->clid.','.$taxa.','.$occid.',"")';
		if($this->conn->query($sql)){
			return 1;
		}
		else{
			if($this->conn->errno == 1062){
				//trigger_error('Specimen already a voucher for checklist ');
				return 'Specimen already a voucher for checklist';
			}
			else{
				//trigger_error('Attempting to resolve by adding species to checklist; '.$this->conn->error,E_USER_WARNING);
				$sql2 = 'INSERT INTO fmchklsttaxalink(tid,clid) VALUES('.$taxa.','.$this->clid.')';
				if($this->conn->query($sql2)){
					if($this->conn->query($sql)){
						return 1;
					}
					else{
						//echo 'Name added to list, though still unable to link voucher';
						//trigger_error('Name added to checklist, though still unable to link voucher": '.$this->conn->error,E_USER_WARNING);
						return 'Name added to checklist, though unable to link voucher: '.$this->conn->error;
					}
				}
				else{
					//echo 'Unable to link voucher; unknown error';
					//trigger_error('Unable to link voucher; '.$this->conn->error,E_USER_WARNING);
					return 'Unable to link voucher: '.$this->conn->error;
				}
			}
		}
	}

	public function linkTaxaVouchers($occidArr,$useCurrentTaxon = 1){
		$tidsUsed = array();
		foreach($occidArr as $v){
			$vArr = explode('-',$v);
			$tid = $vArr[1];
			$occid = $vArr[0];
			if(count($vArr) == 2 && is_numeric($occid) && is_numeric($tid)){
				if($useCurrentTaxon){
					$sql = 'SELECT tidaccepted FROM taxstatus WHERE taxauthid = 1 AND tid = '.$tid;
					$rs = $this->conn->query($sql);
					if($r = $rs->fetch_object()){
						$tid = $r->tidaccepted;
					}
					$rs->free();
				}
				if(!in_array($tid,$tidsUsed)){
					//Add name to checklist
					$sql = 'INSERT INTO fmchklsttaxalink(clid,tid) VALUES('.$this->clid.','.$tid.')';
					$tidsUsed[] = $tid;
					//echo $sql;
					if(!$this->conn->query($sql)){
						trigger_error('Unable to add taxon; '.$this->conn->error,E_USER_WARNING);
					}
				}
				//Link Vouchers
				$sql = 'INSERT INTO fmvouchers(clid,occid,tid,collector) VALUES ('.$this->clid.','.$occid.','.$tid.',"")';
				if(!$this->conn->query($sql)){
					trigger_error('Unable to link taxon voucher; '.$this->conn->error,E_USER_WARNING);
				}
			}
		}
	}

	//Misc fucntions
	public function getCollectionList(){
		$retArr = array();
		$sql = 'SELECT collid, collectionname FROM omcollections ORDER BY collectionname'; 
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid] = $r->collectionname;
		}
		$rs->free();
		return $retArr;
	}

	public function getSciname($tid){
		$retStr = '';
		if(is_numeric($tid)){
			$sql = 'SELECT sciname FROM taxa WHERE tid = '.$tid; 
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$retStr = $r->sciname;
			}
			$rs->free();
		}
		return $retStr;
	}
	
	private function getTid($sciname){
		$tidRet = 0;
		$sql = 'SELECT tid FROM taxa WHERE sciname = ("'.$sciname.'")';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$tidRet = $r->tid;
		}
		$rs->free();
		return $tidRet;
	}
	
	private function arrayToCsv( $arrIn, $delimiter = ',', $enclosure = '"', $encloseAll = false) {
		$delimiterEsc = preg_quote($delimiter, '/');
		$enclosureEsc = preg_quote($enclosure, '/');
		$output = array();
		foreach ( $arrIn as $field ) {
			$field = str_replace(array("\r", "\r\n", "\n"),'',$field);
			if ( $encloseAll || preg_match( "/(?:${delimiterEsc}|${enclosureEsc}|\s)/", $field ) ) {
				//$field = str_replace($delimiter,'\\'.$delimiter,$field);
				$output[] = $enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure;
			}
			else {
				$output[] = $field;
			}
		}
		return implode( $delimiter, $output )."\n";
	}

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