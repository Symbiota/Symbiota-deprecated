<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class ChecklistVoucherAdmin {

	private $conn;
	private $clid;
	private $childClidArr = array();
	private $clName;
	private $sqlFrag;
	private $missingTaxaCount = 0;
	
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

	public function getDynamicSql(){
		return $this->sqlFrag;
	}
	
	public function parseSql(){
		$retArr = array('country'=>'','state'=>'','county'=>'','locality'=>'','taxon'=>'','collid'=>'','recordedBy'=>'',
			'latn'=>'','lats'=>'','lnge'=>'','lngw'=>'','latLngOr'=>0,'culStatus'=>0,'onlyCoord'=>0);
		if($this->sqlFrag){
			if(preg_match('/country = "([^"]+)"/',$this->sqlFrag,$m)){
				$retArr['country'] = $m[1];
			}
			if(preg_match('/stateprovince = "([^"]+)"/',$this->sqlFrag,$m)){
				$retArr['state'] = $m[1];
			}
			if(preg_match('/county LIKE "([^%"]+)%"/',$this->sqlFrag,$m)){
				$retArr['county'] = trim($m[1],' %');
			}
			if(preg_match('/locality LIKE "%([^%"]+)%"/',$this->sqlFrag,$m)){
				$retArr['locality'] = trim($m[1],' %');
			}
			if(preg_match('/parenttid = (\d+)\)/',$this->sqlFrag,$m)){
				$retArr['taxon'] = $this->getSciname($m[1]);
			} 
			if(preg_match('/decimallatitude BETWEEN ([-\.\d]+) AND ([-\.\d]+)\D+/',$this->sqlFrag,$m)){
				$retArr['lats'] = $m[1];
				$retArr['latn'] = $m[2];
			} 
			if(preg_match('/decimallongitude BETWEEN ([-\.\d]+) AND ([-\.\d]+)\D+/',$this->sqlFrag,$m)){
				$retArr['lngw'] = $m[1];
				$retArr['lnge'] = $m[2];
			} 
			if(preg_match('/collid = (\d+)\D/',$this->sqlFrag,$m)){
				$retArr['collid'] = $m[1];
			}
			if(preg_match('/recordedby LIKE "%([^%"]+)%"/',$this->sqlFrag,$m)){
				$retArr['recordedBy'] = trim($m[1],' %');
			}
			if(preg_match('/ OR \(\(o.decimallatitude/',$this->sqlFrag) || preg_match('/ OR \(\(o.decimallongitude/',$this->sqlFrag)){
				$retArr['latLngOr'] = 1;
			}
			if(preg_match('/cultivationStatus/',$this->sqlFrag)){
				$retArr['culStatus'] = 1;
			}
			if(preg_match('/decimallatitude/',$this->sqlFrag)){
				$retArr['onlyCoord'] = 1;
			}
		}
		return $retArr;
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
		if($sqlFrag) $sqlFrag = trim(substr($sqlFrag,3));
		$sql = "UPDATE fmchecklists c SET c.dynamicsql = ".($sqlFrag?"'".$sqlFrag."'":'NULL')." WHERE (c.clid = ".$this->clid.")";
		//echo $sql;
		if($this->conn->query($sql)){
			$this->sqlFrag = $sqlFrag;
		}
		else{
			$statusStr = 'ERROR: unable to create or modify search statement ('.$this->conn->error.')';
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

	public function getNewVouchers($startLimit = 500,$includeAll = 1){
		$retArr = Array();
		if($this->sqlFrag){
			$clidStr = $this->clid;
			if($this->childClidArr){
				$clidStr .= ','.implode(',',$this->childClidArr);
			}
			if($includeAll == 1 || $includeAll == 2){
				$sql = 'SELECT DISTINCT cl.tid AS cltid, t.sciname AS clsciname, o.occid, '. 
					'IFNULL(CONCAT(c.institutioncode,"-",c.collectioncode,"-",o.catalognumber),"[no catalog number]") AS collcode, '. 
					'o.tidinterpreted, o.sciname, o.recordedby, o.recordnumber, o.eventdate, '.
					'CONCAT_WS("; ",o.country, o.stateprovince, o.county, o.locality) as locality '.
					'FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid '.
					'INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid '.
					'INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tidaccepted '.
					'INNER JOIN fmchklsttaxalink cl ON ts2.tidaccepted = cl.tid '.
					'INNER JOIN taxa t ON cl.tid = t.tid '.
					'WHERE ('.$this->sqlFrag.') AND (cl.clid = '.$this->clid.') AND (ts.taxauthid = 1) AND (ts2.taxauthid = 1) ';
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
					'LEFT JOIN taxstatus AS ts ON t.TID = ts.tid '.
					'WHERE ('.$this->sqlFrag.') AND ((t.RankId < 220)) '.
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

	public function getMissingTaxa(){
		$retArr = Array();
		if($this->sqlFrag){
			$clidStr = $this->clid;
			if($this->childClidArr){
				$clidStr .= ','.implode(',',$this->childClidArr);
			}
			$sql = 'SELECT DISTINCT t.tid, t.sciname '.
				'FROM omoccurrences AS o LEFT JOIN taxstatus AS ts ON o.tidinterpreted = ts.tid '.
				'LEFT JOIN taxa AS t ON ts.tidaccepted = t.tid '.
				'WHERE ('.$this->sqlFrag.') AND (ISNULL(o.cultivationstatus) OR o.cultivationstatus = 0) '.
				'AND (t.rankid IN(220,230,240,260,230)) AND (ts.taxauthid = 1) '.
				'AND (ts.tidaccepted NOT IN(SELECT TID FROM fmchklsttaxalink WHERE clid IN('.$clidStr.'))) ';
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
		if($this->sqlFrag){
			$clidStr = $this->clid;
			if($this->childClidArr){
				$clidStr .= ','.implode(',',$this->childClidArr);
			}
			$sql = 'SELECT DISTINCT o.occid, IFNULL(CONCAT(c.institutioncode,"-",c.collectioncode,"-",o.catalognumber),"[no catalog number]") AS collcode, '.
				'o.tidinterpreted, o.sciname, o.recordedby, o.recordnumber, o.eventdate, '.
				'CONCAT_WS("; ",o.country, o.stateprovince, o.county, o.locality) as locality '.
				'FROM omoccurrences AS o LEFT JOIN omcollections AS c ON o.collid = c.CollID '.
				'LEFT JOIN taxstatus AS ts ON o.tidinterpreted = ts.tid '.
				'LEFT JOIN taxa AS t ON ts.tidaccepted = t.TID '.
				'WHERE ('.$this->sqlFrag.') AND (ISNULL(o.cultivationstatus) OR o.cultivationstatus = 0) '.
				'AND (t.rankid IN(220,230,240,260,230)) AND (ts.taxauthid = 1) '.
				'AND (ts.tidaccepted NOT IN(SELECT TID FROM fmchklsttaxalink WHERE clid IN('.$clidStr.'))) '.
				'LIMIT '.($limitIndex?($limitIndex*400).',':'').'400';
			//echo '<div>'.$sql.'</div>';
			$rs = $this->conn->query($sql);
			$spTidArr = array();
			while($r = $rs->fetch_object()){
				$retArr[$r->sciname][$r->occid]['tid'] = $r->tidinterpreted;
				$retArr[$r->sciname][$r->occid]['collcode'] = $r->collcode;
				$retArr[$r->sciname][$r->occid]['recordedby'] = $r->recordedby;
				$retArr[$r->sciname][$r->occid]['recordnumber'] = $r->recordnumber;
				$retArr[$r->sciname][$r->occid]['eventdate'] = $r->eventdate;
				$retArr[$r->sciname][$r->occid]['locality'] = $r->locality;
			}
			$rs->free();
			$this->setMissingTaxaCount($clidStr);
		}
		return $retArr;
	}

	private function setMissingTaxaCount($clidStr){
		if($this->sqlFrag){
			$sql = 'SELECT COUNT(DISTINCT ts.tidaccepted) as cnt '.
				'FROM omoccurrences AS o LEFT JOIN taxstatus AS ts ON o.tidinterpreted = ts.tid '.
				'LEFT JOIN taxa AS t ON ts.tidaccepted = t.TID '.
				'WHERE ('.$this->sqlFrag.') AND (ISNULL(o.cultivationstatus) OR o.cultivationstatus = 0) '.
				'AND (t.rankid IN(220,230,240,260,230)) AND (ts.taxauthid = 1) '.
				'AND (ts.tidaccepted NOT IN(SELECT TID FROM fmchklsttaxalink WHERE clid IN('.$clidStr.'))) ';
			//echo '<div>'.$sql.'</div>'; exit;
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->missingTaxaCount = $row->cnt;
			}
			$rs->free();
		}
	}
	
	//Export functions used within voucherreporthandler.php
	public function exportMissingOccurCsv(){
		$fileName = 'Missing_'.$this->getExportFileName();

		$fieldArr = $this->getFieldArr();
		$localitySecurityFields = $this->getLocalitySecurityArr();
		
		$sql = 'SELECT '.implode(',',$fieldArr).', o.localitysecurity, o.collid '.
			'FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid '.
			'INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid '.
			'INNER JOIN taxa t ON ts.tidaccepted = t.tid '.
			$this->getMissingTaxaSqlWhere();
			//'ORDER BY o.family, o.sciname, c.institutioncode ';
		//echo $sql;
		$this->exportCsv($fileName,$sql,$localitySecurityFields);
	}

	private function getMissingTaxaSqlWhere(){
		$sqlWhere = '';
		if($this->sqlFrag){
			$sqlWhere = 'WHERE ('.$this->sqlFrag.') AND (o.cultivationstatus IS NULL OR o.cultivationstatus = 0) '.
				'AND (t.rankid IN(220,230,240,260,230)) AND (ts.taxauthid = 1) ';
			$taxaArr = array();
			$clidStr = $this->clid;
			if($this->childClidArr){
				$clidStr .= ','.implode(',',$this->childClidArr);
			}
			$sql = 'SELECT DISTINCT ts.tidaccepted '.
				'FROM taxstatus ts INNER JOIN fmchklsttaxalink ctl ON ts.tid = ctl.tid '. 
				'WHERE (ctl.clid IN('.$clidStr.')) AND ts.taxauthid = 1';
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$taxaArr[] = $row->tidaccepted;
			}
			$rs->free();
			if($taxaArr){
				$sqlWhere .= 'AND ts.tidaccepted NOT IN('.implode(',',$taxaArr).')';
			}
		}
		return $sqlWhere;
	}

	public function getMissingProblemTaxa(){
		$retArr = Array();
		if($this->sqlFrag){
			//Make sure tidinterpreted are valid 
			//$this->conn->query('UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname SET o.tidinterpreted = t.tid WHERE o.tidinterpreted IS NULL');
			//Grab records
			$clidStr = $this->clid;
			if($this->childClidArr){
				$clidStr .= ','.implode(',',$this->childClidArr);
			}
			$sql = 'SELECT DISTINCT o.occid, IFNULL(CONCAT(c.institutioncode,"-",c.collectioncode,"-",o.catalognumber),"[no catalog number]") AS collcode, '.
				'o.sciname, o.recordedby, o.recordnumber, o.eventdate, '.
				'CONCAT_WS("; ",o.country, o.stateprovince, o.county, o.locality) as locality '.
				'FROM omoccurrences AS o LEFT JOIN omcollections AS c ON o.collid = c.CollID '.
				'WHERE ('.$this->sqlFrag.') AND ISNULL(o.tidinterpreted) AND o.sciname IS NOT NULL '.
				'AND (o.occid NOT IN(SELECT occid FROM fmvouchers WHERE clid IN('.$clidStr.'))) ';
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

		$fieldArr = $this->getFieldArr();
		$localitySecurityFields = $this->getLocalitySecurityArr();
		
		$clidStr = $this->clid;
		if($this->childClidArr){
			$clidStr .= ','.implode(',',$this->childClidArr);
		}
		
		$sql = 'SELECT DISTINCT '.implode(',',$fieldArr).', o.localitysecurity, o.collid '.
			'FROM omcollections c INNER JOIN omoccurrences o ON c.collid = o.collid '.
			'WHERE (o.occid NOT IN (SELECT occid FROM fmvouchers WHERE clid IN('.$clidStr.'))) AND ('.$this->sqlFrag.') '.
			'AND o.tidinterpreted IS NULL AND o.sciname IS NOT NULL ';
		//echo '<div>'.$sql.'</div>';return;
		$this->exportCsv($fileName,$sql,$localitySecurityFields);
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
		//echo $sql; exit;
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