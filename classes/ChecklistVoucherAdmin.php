<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
 
class ChecklistVoucherAdmin {

	private $conn;
	private $clid;
	private $childClidArr = array();
	private $clName;
	private $queryVariablesArr = array();
	private $missingTaxaCount = 0;
	private $closeConnOnDestroy = true;

	function __construct($con = null) {
		if($con) {
			$this->conn = $con;
			$this->closeConnOnDestroy = false;
		}
		else{
			$this->conn = MySQLiConnectionFactory::getCon("write");
		}
	}

	function __destruct(){
 		if($this->closeConnOnDestroy && !($this->conn === false)) $this->conn->close();
	}

	public function setClid($clid){
		if(is_numeric($clid)) $this->clid = $clid;
	}

	public function setCollectionVariables(){
		if($this->clid){
			$sql = 'SELECT name, dynamicsql FROM fmchecklists WHERE (clid = '.$this->clid.')';
			$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$this->clName = $this->cleanOutStr($row->name);
				$sqlFrag = $row->dynamicsql;
				$varArr = json_decode($sqlFrag,true);
				if(json_last_error() != JSON_ERROR_NONE){
					$varArr = $this->parseSqlFrag($sqlFrag);
					$this->saveQueryVariables($varArr);
				}
				$this->queryVariablesArr = $varArr;
			}
			else{
				$this->clName = 'Unknown';
			}
			$result->free();
			//Get children checklists
			$sqlChildBase = 'SELECT clidchild FROM fmchklstchildren WHERE clid IN(';
			$sqlChild = $sqlChildBase.$this->clid.')';
			do{
				$childStr = "";
				$rsChild = $this->conn->query($sqlChild);
				while($rChild = $rsChild->fetch_object()){
					$this->childClidArr[] = $rChild->clidchild;
					$childStr .= ','.$rChild->clidchild;
				}
				$sqlChild = $sqlChildBase.substr($childStr,1).')';
			}while($childStr);
		}
	}

	public function getClName(){
		return $this->clName;
	}

	public function saveQueryVariables($postArr){
		$fieldArr = array('country','state','county','locality','taxon','collid','recordedby',
			'latnorth','latsouth','lngeast','lngwest','latlngor','excludecult','onlycoord');
		$jsonArr = array();
		foreach($fieldArr as $fieldName){
			if(isset($postArr[$fieldName]) && $postArr[$fieldName]) $jsonArr[$fieldName] = $postArr[$fieldName];
		}
		$sql = 'UPDATE fmchecklists c SET c.dynamicsql = '.($jsonArr?'"'.$this->cleanInStr(json_encode($jsonArr)).'"':'NULL').' WHERE (c.clid = '.$this->clid.')';
		//echo $sql; exit;
		if(!$this->conn->query($sql)){
			$statusStr = 'ERROR: unable to create or modify search statement ('.$this->conn->error.')';
		}
	}

	private function parseSqlFrag($sqlFrag){
		$retArr = array();
		if($sqlFrag){
			if(preg_match('/country = "([^"]+)"/',$sqlFrag,$m)){
				$retArr['country'] = $m[1];
			}
			if(preg_match('/stateprovince = "([^"]+)"/',$sqlFrag,$m)){
				$retArr['state'] = $m[1];
			}
			if(preg_match('/county LIKE "([^%"]+)%"/',$sqlFrag,$m)){
				$retArr['county'] = trim($m[1],' %');
			}
			if(preg_match('/locality LIKE "%([^%"]+)%"/',$sqlFrag,$m)){
				$retArr['locality'] = trim($m[1],' %');
			}
			if(preg_match('/parenttid = (\d+)\)/',$sqlFrag,$m)){
				$retArr['taxon'] = $this->getSciname($m[1]);
			} 
			if(preg_match_all('/AGAINST\("([^()"]+)"\)/',$sqlFrag,$m)){
				$retArr['recordedby'] = implode(',',$m[1]);
			}
			if(preg_match('/decimallatitude BETWEEN ([-\.\d]+) AND ([-\.\d]+)\D+/',$sqlFrag,$m)){
				$retArr['latsouth'] = $m[1];
				$retArr['latnorth'] = $m[2];
			} 
			if(preg_match('/decimallongitude BETWEEN ([-\.\d]+) AND ([-\.\d]+)\D+/',$sqlFrag,$m)){
				$retArr['lngwest'] = $m[1];
				$retArr['lngeast'] = $m[2];
			} 
			if(preg_match('/collid = (\d+)\D/',$sqlFrag,$m)){
				$retArr['collid'] = $m[1];
			}
			if(preg_match('/ OR \(\(o.decimallatitude/',$sqlFrag) || preg_match('/ OR \(\(o.decimallongitude/',$sqlFrag)){
				$retArr['latlngor'] = 1;
			}
			if(preg_match('/cultivationStatus/',$sqlFrag)){
				$retArr['excludecult'] = 1;
			}
			if(preg_match('/decimallatitude/',$sqlFrag)){
				$retArr['onlycoord'] = 1;
			}
		}
		return $retArr;
	}

	public function getSqlFrag(){
		$sqlFrag = '';
		if(isset($this->queryVariablesArr['country']) && $this->queryVariablesArr['country']){
			$countryStr = str_replace(';',',',$this->cleanInStr($this->queryVariablesArr['country']));
			$sqlFrag = 'AND (o.country IN("'.$countryStr.'")) ';
		}
		if(isset($this->queryVariablesArr['state']) && $this->queryVariablesArr['state']){
			$stateStr = str_replace(';',',',$this->cleanInStr($this->queryVariablesArr['state']));
			$sqlFrag .= 'AND (o.stateprovince = "'.$stateStr.'") ';
		}
		if(isset($this->queryVariablesArr['county']) && $this->queryVariablesArr['county']){
			$countyStr = str_replace(';',',',$this->queryVariablesArr['county']);
			$cArr = explode(',', $countyStr);
			$cStr = '';
			foreach($cArr as $str){
				$cStr .= 'OR (o.county LIKE "'.$this->cleanInStr($str).'%") ';
			}
			$sqlFrag .= 'AND ('.substr($cStr, 2).') ';
		}
		if(isset($this->queryVariablesArr['locality']) && $this->queryVariablesArr['locality']){
			$localityStr = str_replace(';',',',$this->queryVariablesArr['locality']);
			$locArr = explode(',', $localityStr);
			$locStr = '';
			foreach($locArr as $str){
				$locStr .= 'OR (o.locality LIKE "%'.$this->cleanInStr($str).'%") ';
			}
			$sqlFrag .= 'AND ('.substr($locStr, 2).') ';
		}
		//taxonomy
		if(isset($this->queryVariablesArr['taxon']) && $this->queryVariablesArr['taxon']){
			$tStr = $this->cleanInStr($this->queryVariablesArr['taxon']);
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
		if(isset($this->queryVariablesArr['latnorth']) && isset($this->queryVariablesArr['latsouth']) && is_numeric($this->queryVariablesArr['latnorth']) && is_numeric($this->queryVariablesArr['latsouth'])){
			$llStr .= 'AND (o.decimallatitude BETWEEN '.$this->queryVariablesArr['latsouth'].' AND '.$this->queryVariablesArr['latnorth'].') ';
		}
		if(isset($this->queryVariablesArr['lngwest']) && isset($this->queryVariablesArr['lngeast']) && is_numeric($this->queryVariablesArr['lngwest']) && is_numeric($this->queryVariablesArr['lngeast'])){
			$llStr .= 'AND (o.decimallongitude BETWEEN '.$this->queryVariablesArr['lngwest'].' AND '.$this->queryVariablesArr['lngeast'].') ';
		}
		if($llStr){
			if(array_key_exists('latlngor',$this->queryVariablesArr)) $llStr = 'OR ('.trim(substr($llStr,3)).') ';
			$sqlFrag .= $llStr;
		}
		//Use occurrences only with decimallatitude
		if(!$llStr && isset($this->queryVariablesArr['onlycoord']) && $this->queryVariablesArr['onlycoord']){
			$sqlFrag .= 'AND (o.decimallatitude IS NOT NULL) ';
		}
		//Exclude taxonomy
		if(isset($this->queryVariablesArr['excludecult']) && $this->queryVariablesArr['excludecult']){
			$sqlFrag .= 'AND (o.cultivationStatus = 0 OR o.cultivationStatus IS NULL) ';
		}
		//Limit by collection
		if(isset($this->queryVariablesArr['collid']) && is_numeric($this->queryVariablesArr['collid'])){
			$sqlFrag .= 'AND (o.collid = '.$this->queryVariablesArr['collid'].') ';
		}

		//Limit by collector
		if(isset($this->queryVariablesArr['recordedby']) && $this->queryVariablesArr['recordedby']){
			$collStr = str_replace(',', ';', $this->queryVariablesArr['recordedby']);
			$collArr = explode(';',$collStr);
			$tempArr = array();
			foreach($collArr as $str){
				if(strlen($str) < 4 || strtolower($str) == 'best'){
					//Need to avoid FULLTEXT stopwords interfering with return
					$tempArr[] = '(o.recordedby LIKE "%'.$this->cleanInStr($postArr['recordedby']).'%")';
				}
				else{
					$tempArr[] = '(MATCH(f.recordedby) AGAINST("'.$this->cleanInStr($str).'"))';
				}
			}
			$sqlFrag .= 'AND ('.implode(' OR ', $tempArr).') ';
		}

		//Save SQL fragment
		if($sqlFrag) $sqlFrag = trim(substr($sqlFrag,3));
		return $sqlFrag;
	}

	public function deleteQueryVariables(){
		$statusStr = '';
		if($this->conn->query('UPDATE fmchecklists c SET c.dynamicsql = NULL WHERE (c.clid = '.$this->clid.')')){
			unset($this->queryVariablesArr);
			$this->queryVariablesArr = array();
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

	public function getNewVouchers($startLimit = 500,$includeAll = 1){
		$retArr = Array();
		if($sqlFrag = $this->getSqlFrag()){
			$clidStr = $this->clid;
			if($this->childClidArr){
				$clidStr .= ','.implode(',',$this->childClidArr);
			}
			if($includeAll == 1 || $includeAll == 2){
				$sql = 'SELECT DISTINCT cl.tid AS cltid, t.sciname AS clsciname, o.occid, '. 
					'IFNULL(CONCAT(c.institutioncode,"-",c.collectioncode,"-",o.catalognumber),"[no catalog number]") AS collcode, '. 
					'o.tidinterpreted, o.sciname, o.recordedby, o.recordnumber, o.eventdate, '.
					'CONCAT_WS("; ",o.country, o.stateprovince, o.county, o.locality) as locality '.
					'FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid '.
					'INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid '.
					'INNER JOIN fmchklsttaxalink cl ON ts.tidaccepted = cl.tid '.
					'INNER JOIN taxa t ON cl.tid = t.tid ';
				if(strpos($sqlFrag,'MATCH(f.recordedby)')) $sql .= 'INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ';
				$sql .= 'WHERE ('.$sqlFrag.') AND (cl.clid = '.$this->clid.') AND (ts.taxauthid = 1) ';
				if($includeAll == 1){
					$sql .= 'AND cl.tid NOT IN(SELECT tid FROM fmvouchers WHERE clid IN('.$clidStr.')) ';
				}
				elseif($includeAll == 2){
					$sql .= 'AND o.occid NOT IN(SELECT occid FROM fmvouchers WHERE clid IN('.$clidStr.')) '; 
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
			}
			elseif($includeAll == 3){
				$sql = 'SELECT DISTINCT t.tid AS cltid, t.sciname AS clsciname, o.occid, '. 
					'IFNULL(CONCAT(c.institutioncode,"-",c.collectioncode,"-",o.catalognumber),"[no catalog number]") AS collcode, '. 
					'o.tidinterpreted, o.sciname, o.recordedby, o.recordnumber, o.eventdate, '.
					'CONCAT_WS("; ",o.country, o.stateprovince, o.county, o.locality) as locality '.
					'FROM omcollections AS c INNER JOIN omoccurrences AS o ON c.collid = o.collid '.
					'LEFT JOIN taxa AS t ON o.tidinterpreted = t.TID '.
					'LEFT JOIN taxstatus AS ts ON t.TID = ts.tid ';
				if(strpos($sqlFrag,'MATCH(f.recordedby)')) $sql .= 'LEFT JOIN omoccurrencesfulltext f ON o.occid = f.occid ';
				$sql .= 'WHERE ('.$sqlFrag.') AND ((t.RankId < 220)) '.
					'AND (o.occid NOT IN(SELECT occid FROM fmvouchers WHERE CLID IN('.$clidStr.'))) ';
				$sql .= 'ORDER BY o.family, o.sciname LIMIT '.$startLimit.', 500';
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
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getMissingTaxa(){
		$retArr = Array();
		if($sqlFrag = $this->getSqlFrag()){
			$sql = 'SELECT DISTINCT t.tid, t.sciname '.$this->getMissingTaxaBaseSql($sqlFrag);
			//echo '<div>'.$sql.'</div>'; 
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$retArr[$row->tid] = $this->cleanOutStr($row->sciname);
			}
			asort($retArr);
			$rs->free();
		}
		$this->missingTaxaCount = count($retArr);
		return $retArr;
	}

	public function getMissingTaxaSpecimens($limitIndex){
		$retArr = Array();
		if($sqlFrag = $this->getSqlFrag()){
			$sqlBase = $this->getMissingTaxaBaseSql($sqlFrag);
			$sql = 'SELECT DISTINCT o.occid, IFNULL(CONCAT(c.institutioncode,"-",c.collectioncode,"-",o.catalognumber),"[no catalog number]") AS collcode, '.
				'o.tidinterpreted, o.sciname, o.recordedby, o.recordnumber, o.eventdate, '.
				'CONCAT_WS("; ",o.country, o.stateprovince, o.county, o.locality) as locality '.
				$sqlBase.' LIMIT '.($limitIndex?($limitIndex*400).',':'').'400';
			//echo '<div>'.$sql.'</div>';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->sciname][$r->occid]['tid'] = $r->tidinterpreted;
				$retArr[$r->sciname][$r->occid]['collcode'] = $r->collcode;
				$retArr[$r->sciname][$r->occid]['recordedby'] = $r->recordedby;
				$retArr[$r->sciname][$r->occid]['recordnumber'] = $r->recordnumber;
				$retArr[$r->sciname][$r->occid]['eventdate'] = $r->eventdate;
				$retArr[$r->sciname][$r->occid]['locality'] = $r->locality;
			}
			$rs->free();

			//Set missing taxa count
			$sqlB = 'SELECT COUNT(DISTINCT ts.tidaccepted) as cnt '.
				$sqlBase;
			//echo '<div>'.$sql.'</div>';
			$rsB = $this->conn->query($sqlB);
			if($r = $rsB->fetch_object()){
				$this->missingTaxaCount = $r->cnt;
			}
			$rsB->free();
		}
		return $retArr;
	}

	public function getConflictVouchers(){
		$retArr = Array();
		$clidStr = $this->clid;
		if($this->childClidArr){
			$clidStr .= ','.implode(',',$this->childClidArr);
		}
		$sql = 'SELECT DISTINCT t.tid, v.clid, t.sciname AS listid, o.recordedby, o.recordnumber, o.sciname, o.identifiedby, o.dateidentified, o.occid '.
				'FROM taxstatus ts1 INNER JOIN omoccurrences o ON ts1.tid = o.tidinterpreted '.
				'INNER JOIN fmvouchers v ON o.occid = v.occid '.
				'INNER JOIN taxstatus ts2 ON v.tid = ts2.tid '.
				'INNER JOIN taxa t ON v.tid = t.tid '.
				'INNER JOIN taxstatus ts3 ON ts1.tidaccepted = ts3.tid '.
				'WHERE (v.clid IN('.$clidStr.')) AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND ts1.tidaccepted <> ts2.tidaccepted '.
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
			$retArr[$cnt]['clid'] = $row->clid;
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

	public function batchAdjustChecklist($postArr){
		$occidArr = $postArr['occid'];
		foreach($occidArr as $occid){
			//Get checklist tid
			$tidChecklist = 0;
			$sql = 'SELECT tid FROM fmvouchers WHERE (clid = '.$this->clid.') AND (occid = '.$occid.')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$tidChecklist = $r->tid;
			}
			$rs->free();
			//Get voucher tid 
			$tidVoucher = 0;
			$sql1 = 'SELECT tidinterpreted FROM omoccurrences WHERE (occid = '.$occid.')';
			$rs1 = $this->conn->query($sql1);
			if($r1 = $rs1->fetch_object()){
				$tidVoucher = $r1->tidinterpreted;
			}
			$rs1->free();
			//Make sure 
			$sql2 = 'INSERT IGNORE INTO fmchklsttaxalink(tid, clid, morphospecies, familyoverride, habitat, abundance, notes, explicitExclude, source, internalnotes, dynamicProperties) '.
				'SELECT '.$tidVoucher.' as tid, c.clid, c.morphospecies, c.familyoverride, c.habitat, c.abundance, c.notes, c.explicitExclude, c.source, c.internalnotes, c.dynamicProperties '.
				'FROM fmchklsttaxalink c INNER JOIN fmvouchers v ON c.tid = v.tid AND c.clid = v.clid '.
				'WHERE (c.clid = '.$this->clid.') AND (v.occid = '.$occid.')';
			$this->conn->query($sql2);
			//Transfer voucher to new name
			$sql3 = 'UPDATE fmvouchers SET tid = '.$tidVoucher.' WHERE (clid = '.$this->clid.') AND (occid = '.$occid.')';
			$this->conn->query($sql3);
			if(array_key_exists('removeOldIn',$postArr)){
				$sql4 = 'DELETE c.* FROM fmchklsttaxalink c LEFT JOIN fmvouchers v ON c.clid = v.clid AND c.tid = v.tid '.
					'WHERE (c.clid = '.$this->clid.') AND (c.tid = '.$tidChecklist.') AND (v.clid IS NULL)';
				$this->conn->query($sql4);
			}
		}
	}

	//Export functions used within voucherreporthandler.php
	public function exportMissingOccurCsv(){
		if($sqlFrag = $this->getSqlFrag()){
			$fileName = 'Missing_'.$this->getExportFileName();
	
			$fieldArr = $this->getFieldArr();
			$localitySecurityFields = $this->getLocalitySecurityArr();
			
			$exportSql = 'SELECT '.implode(',',$fieldArr).', o.localitysecurity, o.collid '.
				$this->getMissingTaxaBaseSql($sqlFrag);
			//echo $exportSql;
			$this->exportCsv($fileName,$exportSql,$localitySecurityFields);
		}
	}
	
	private function getMissingTaxaBaseSql($sqlFrag){
		$clidStr = $this->clid;
		if($this->childClidArr) $clidStr .= ','.implode(',',$this->childClidArr);
		$retSql = 'FROM omoccurrences o LEFT JOIN omcollections c ON o.collid = c.collid '.
			'INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid '.
			'INNER JOIN taxa t ON ts.tidaccepted = t.tid ';
		if(strpos($sqlFrag,'MATCH(f.recordedby)')) $retSql .= 'INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ';
		$retSql .= 'WHERE ('.$sqlFrag.') '.
			'AND (t.rankid IN(220,230,240,260,230)) AND (ts.taxauthid = 1) '.
			'AND (o.occid NOT IN(SELECT occid FROM fmvouchers WHERE clid IN('.$clidStr.'))) '.
			'AND (ts.tidaccepted NOT IN(SELECT ts.tidaccepted FROM fmchklsttaxalink cl INNER JOIN taxstatus ts ON cl.tid = ts.tid WHERE ts.taxauthid = 1 AND cl.clid IN('.$clidStr.'))) ';
		return $retSql;
	}

	public function getMissingProblemTaxa(){
		$retArr = Array();
		if($sqlFrag = $this->getSqlFrag()){
			//Make sure tidinterpreted are valid 
			//$this->conn->query('UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname SET o.tidinterpreted = t.tid WHERE o.tidinterpreted IS NULL');
			//Grab records
			$sql = 'SELECT DISTINCT o.occid, IFNULL(CONCAT(c.institutioncode,"-",c.collectioncode,"-",o.catalognumber),"[no catalog number]") AS collcode, '.
				'o.sciname, o.recordedby, o.recordnumber, o.eventdate, '.
				'CONCAT_WS("; ",o.country, o.stateprovince, o.county, o.locality) as locality '.
				$this->getProblemTaxaSql($sqlFrag);
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
		$this->missingTaxaCount = count($retArr);
		return $retArr;
	}
	
	public function exportProblemTaxaCsv(){
		$fileName = 'ProblemTaxa_'.$this->getExportFileName();

		if($sqlFrag = $this->getSqlFrag()){
			$fieldArr = $this->getFieldArr();
			$localitySecurityFields = $this->getLocalitySecurityArr();
			$sql = 'SELECT DISTINCT '.implode(',',$fieldArr).', o.localitysecurity, o.collid '.
				$this->getProblemTaxaSql($sqlFrag);
			$this->exportCsv($fileName,$sql,$localitySecurityFields);
		}
	}

	private function getProblemTaxaSql($sqlFrag){
		$clidStr = $this->clid;
		if($this->childClidArr) $clidStr .= ','.implode(',',$this->childClidArr);
		$retSql = 'FROM omoccurrences o LEFT JOIN omcollections c ON o.collid = c.CollID ';
		if(strpos($sqlFrag,'MATCH(f.recordedby)')) $retSql .= 'INNER JOIN omoccurrencesfulltext f ON o.occid = f.occid ';
		$retSql .= 'WHERE ('.$sqlFrag.') AND (o.tidinterpreted IS NULL) AND (o.sciname IS NOT NULL) '.
			'AND (o.occid NOT IN(SELECT occid FROM fmvouchers WHERE clid IN('.$clidStr.'))) ';
		return $retSql;
	}

	public function downloadDatasetCsv(){
		if($this->clid){
			$fileName = $this->getExportFileName();
			
			$fieldArr = array('tid'=>'t.tid', 'family'=>'IFNULL(ctl.familyoverride,ts.family) AS family', 'scientificName'=>'t.sciname', 'author'=>'t.author');
			$fieldArr['clhabitat'] = 'ctl.habitat AS cl_habitat';
			$fieldArr['clabundance'] = 'ctl.abundance';
			$fieldArr['clNotes'] = 'ctl.notes';
			$fieldArr['clSource'] = 'ctl.source';
			$fieldArr['editorNotes'] = 'ctl.internalnotes';
			$fieldArr = array_merge($fieldArr,$this->getFieldArr());
			$fieldArr['family'] = 'ts.family';
			$fieldArr['scientificName'] = 't.sciName';
			
			$localitySecurityFields = $this->getLocalitySecurityArr();
			
			$clidStr = $this->clid;
			if($this->childClidArr){
				$clidStr .= ','.implode(',',$this->childClidArr);
			}
			
			$sql = 'SELECT DISTINCT '.implode(',',$fieldArr).', o.localitysecurity, o.collid '.
				'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
				'INNER JOIN fmchklsttaxalink ctl ON ctl.tid = t.tid '.
				'LEFT JOIN fmvouchers v ON ctl.clid = v.clid AND ctl.tid = v.tid '.
				'LEFT JOIN omoccurrences o ON v.occid = o.occid '.
				'LEFT JOIN omcollections c ON o.collid = c.collid '.
				'WHERE (ts.taxauthid = 1) AND (ctl.clid IN('.$clidStr.')) ';
			$this->exportCsv($fileName,$sql,$localitySecurityFields);
		}
	}

	private function exportCsv($fileName,$sql,$localitySecurityFields){
		header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header ('Content-Type: text/csv');
		header ('Content-Disposition: attachment; filename="'.$fileName.'"'); 
		$rs = $this->conn->query($sql);
		if($rs->num_rows){
			$headerArr = array();
			$fields = mysqli_fetch_fields($rs);
			foreach ($fields as $val) {
				$headerArr[] = $val->name;
			}
			$rareSpeciesReader = $this->isRareSpeciesReader();
			$out = fopen('php://output', 'w');
			fputcsv($out, $headerArr);
			while($row = $rs->fetch_assoc()){
				$localSecurity = ($row["localitysecurity"]?$row["localitysecurity"]:0); 
				if(!$rareSpeciesReader && $localSecurity != 1 && (!array_key_exists('RareSppReader', $GLOBALS['USER_RIGHTS']) || !in_array($row['collid'],$GLOBALS['USER_RIGHTS']['RareSppReader']))){
					$redactStr = '';
					foreach($localitySecurityFields as $fieldName){
						if($row[$fieldName]) $redactStr .= ','.$fieldName;
					}
					if($redactStr) $row['informationWithheld'] = 'Fields with redacted values (e.g. rare species localities):'.trim($redactStr,', ');
				}
				fputcsv($out, $row);
			}
			$rs->free();
			fclose($out);
		}
		else{
			echo "Recordset is empty.\n";
		}
	}

	private function getExportFileName(){
		$fileName = $this->clName;
		if($fileName){
			if(strlen($fileName) > 20){
				$nameArr = explode(' ',$fileName);
				foreach($nameArr as $k => $w){
					if(strlen($w) > 3) $nameArr[$k] = substr($w,0,4);
				}
				$fileName = implode('',$nameArr);
			}
		}
		else{
			$fileName = 'symbiota';
		}
		$fileName = str_replace(Array('.',' ',':'),'',$fileName);
		$fileName .= '_'.time().'.csv';
		return $fileName;
	}
	
	private function getFieldArr(){
		return array('family'=>'o.family','scientificName'=>'o.sciName','institutionCode'=>'IFNULL(o.institutionCode,c.institutionCode) AS institutionCode',
			'collectionCode'=>'IFNULL(o.collectionCode,c.collectionCode) AS collectionCode',
			'catalogNumber'=>'o.catalogNumber','identifiedBy'=>'o.identifiedBy','dateIdentified'=>'o.dateIdentified',
 			'recordedBy'=>'o.recordedBy','recordNumber'=>'o.recordNumber','eventDate'=>'o.eventDate','country'=>'o.country',
 			'stateProvince'=>'o.stateProvince','county'=>'o.county','municipality'=>'o.municipality','locality'=>'o.locality',
 			'decimalLatitude'=>'o.decimalLatitude','decimalLongitude'=>'o.decimalLongitude','minimumElevationInMeters'=>'o.minimumElevationInMeters',
 			'maximumElevationInMeters'=>'o.maximumelevationinmeters','verbatimElevation'=>'o.verbatimelevation',
 			'habitat'=>'o.habitat','occurrenceRemarks'=>'o.occurrenceRemarks','associatedTaxa'=>'o.associatedTaxa',
 			'reproductivecondition'=>'o.reproductivecondition','informationWithheld'=>'o.informationWithheld','occid'=>'o.occid');
	}
	
	private function getLocalitySecurityArr(){
		return array('recordNumber','eventDate','locality','decimalLatitude','decimalLongitude','minimumElevationInMeters',
			'minimumElevationInMeters','habitat','occurrenceRemarks');
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
		$sql = 'INSERT INTO fmvouchers(clid,tid,occid) '.
			'VALUES ('.$this->clid.','.$taxa.','.$occid.')';
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
				$sql = 'INSERT INTO fmvouchers(clid,occid,tid) VALUES ('.$this->clid.','.$occid.','.$tid.')';
				if(!$this->conn->query($sql)){
					trigger_error('Unable to link taxon voucher; '.$this->conn->error,E_USER_WARNING);
				}
			}
		}
	}

	//Misc fucntions
	public function getQueryVariablesArr(){
		return $this->queryVariablesArr;
	}
	
	public function getQueryVariableStr(){
		$retStr = '';
		if(isset($this->queryVariablesArr['collid'])){
			$collArr = $this->getCollectionList($this->queryVariablesArr['collid']);
			$retStr .= current($collArr).'; ';
		}
		if(isset($this->queryVariablesArr['country'])) $retStr .= $this->queryVariablesArr['country'].'; ';
		if(isset($this->queryVariablesArr['state'])) $retStr .= $this->queryVariablesArr['state'].'; ';
		if(isset($this->queryVariablesArr['county'])) $retStr .= $this->queryVariablesArr['county'].'; ';
		if(isset($this->queryVariablesArr['locality'])) $retStr .= $this->queryVariablesArr['locality'].'; ';
		if(isset($this->queryVariablesArr['taxon'])) $retStr .= $this->queryVariablesArr['taxon'].'; ';
		if(isset($this->queryVariablesArr['recordedby'])) $retStr .= $this->queryVariablesArr['recordedby'].'; ';
		if(isset($this->queryVariablesArr['latsouth']) && isset($this->queryVariablesArr['latnorth'])) $retStr .= 'Lat between '.$this->queryVariablesArr['latsouth'].' and '.$this->queryVariablesArr['latnorth'].'; ';
		if(isset($this->queryVariablesArr['lngwest']) && isset($this->queryVariablesArr['lngeast'])) $retStr .= 'Long between '.$this->queryVariablesArr['lngwest'].' and '.$this->queryVariablesArr['lngeast'].'; ';
		if(isset($this->queryVariablesArr['latlngor'])) $retStr .= 'Include Lat/Long and locality as an "OR" condition; ';
		if(isset($this->queryVariablesArr['excludecult'])) $retStr .= 'Exclude cultivated species; ';
		if(isset($this->queryVariablesArr['onlycoord'])) $retStr .= 'Only include occurrences with coordinates; ';
		return trim($retStr,' ;');
	}

	public function getMissingTaxaCount(){
		return $this->missingTaxaCount;
	}
		
	private function isRareSpeciesReader(){
		$canReadRareSpp = false;
		if($GLOBALS['IS_ADMIN'] 
			|| array_key_exists("CollAdmin", $GLOBALS['USER_RIGHTS']) 
			|| array_key_exists("RareSppAdmin", $GLOBALS['USER_RIGHTS']) || array_key_exists("RareSppReadAll", $GLOBALS['USER_RIGHTS'])){
			$canReadRareSpp = true;
		}
		return $canReadRareSpp;
	}

	public function getCollectionList($collId = 0){
		$retArr = array();
		$sql = 'SELECT collid, collectionname FROM omcollections ';
		if($collId) $sql .= 'WHERE collid = '.$collId;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid] = $r->collectionname;
		}
		$rs->free();
		asort($retArr);
		return $retArr;
	}

	private function getSciname($tid){
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
	
	public function getChildClidArr(){
		return $this->childClidArr;
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