<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class ChecklistAdmin {

	private $conn;
	private $clid;
	private $clName;
	private $pid = '';
	private $sqlFrag;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getMetaData(){
		$sql = "";
		$retArr = array();
		if($this->clid){
			$sql = "SELECT c.clid, c.name, c.locality, c.publication, ".
				"c.abstract, c.authors, c.parentclid, c.notes, ".
				"c.latcentroid, c.longcentroid, c.pointradiusmeters, c.access, ".
				"c.dynamicsql, c.datelastmodified, c.uid, c.type, c.initialtimestamp ".
				"FROM fmchecklists c WHERE (c.clid = ".$this->clid.')';
	 		$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$this->clName = $row->name;
				$retArr["locality"] = $row->locality; 
				$retArr["notes"] = $row->notes;
				$retArr["type"] = $row->type;
				$retArr["publication"] = $row->publication;
				$retArr["abstract"] = $row->abstract;
				$retArr["authors"] = $row->authors;
				$retArr["parentclid"] = $row->parentclid;
				$retArr["uid"] = $row->uid;
				$retArr["latcentroid"] = $row->latcentroid;
				$retArr["longcentroid"] = $row->longcentroid;
				$retArr["pointradiusmeters"] = $row->pointradiusmeters;
				$retArr["access"] = $row->access;
				$retArr["dynamicsql"] = $row->dynamicsql;
				$retArr["datelastmodified"] = $row->datelastmodified;
	    	}
	    	$result->free();
		}
		return $retArr;
	}
	
	public function editMetaData($editArr){
		$statusStr = '';
		$setSql = "";
		foreach($editArr as $key =>$value){
			if($value){
				$setSql .= ', '.$key.' = "'.$this->cleanStr($value).'"';
			}
			else{
				$setSql .= ', '.$key.' = NULL';
			}
		}
		$sql = 'UPDATE fmchecklists SET '.substr($setSql,2).' WHERE (clid = '.$this->clid.')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$statusStr = 'Error: unable to update checklist metadata. SQL: '.$this->conn->error;
		}
		return $statusStr;
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
					echo '"'.$tArr['family'].'","'.$tArr['sciname'].'","'.$tArr['author'].'"';
					echo ',"'.$tid.'"'."\n";
				}
			}
			else{
				echo "Recordset is empty.\n";
			}
		}
    }

    //Point functions
    public function addPoint($tid,$lat,$lng,$notes){
    	$statusStr = '';
    	if(is_numeric($tid) && is_numeric($lat) && is_numeric($lng)){
    		$sql = 'INSERT INTO fmchklstcoordinates(clid,tid,decimallatitude,decimallongitude,notes) '.
    			'VALUES('.$this->clid.','.$tid.','.$lat.','.$lng.',"'.$this->cleanStr($notes).'")';
    		if(!$this->conn->query($sql)){
    			$statusStr = 'ERROR: unable to add point. '.$this->conn->error;
    		}
    	}
    	return $statusStr;
    }
    
    public function removePoint($pointPK){
    	$statusStr = '';
    	if($pointPK && is_numeric($pointPK)){
			if(!$this->conn->query('DELETE FROM fmchklstcoordinates WHERE (chklstcoordid = '.$pointPK.')')){
    			$statusStr = 'ERROR: unable to remove point. '.$this->conn->error;
			}
    	}
    	return $statusStr;
    }
    
	//Voucher Maintenance functions
	public function getDynamicSql(){
		if(!$this->sqlFrag){
			$sql = 'SELECT c.dynamicsql FROM fmchecklists c WHERE (c.clid = '.$this->clid.')';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$this->sqlFrag = $row->dynamicsql;
			}
			$rs->free();
		}
		return $this->sqlFrag;
	}
	
	public function saveSql($sqlFragArr){
		$statusStr = false;
		$sqlFrag = "";
		if($sqlFragArr['country']){
			$sqlFrag = 'AND (o.country = "'.$this->cleanStr($sqlFragArr['country']).'") ';
		}
		if($sqlFragArr['state']){
			$sqlFrag .= 'AND (o.stateprovince = "'.$this->cleanStr($sqlFragArr['state']).'") ';
		}
		if($sqlFragArr['county']){
			$sqlFrag .= 'AND (o.county LIKE "%'.$this->cleanStr($sqlFragArr['county']).'%") ';
		}
		if($sqlFragArr['locality']){
			$sqlFrag .= 'AND (o.locality LIKE "%'.$this->cleanStr($sqlFragArr['locality']).'%") ';
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
			if($this->conn->query($sql)) $statusStr = true;
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

	public function getNonVoucheredTaxa($startLimit){
		$retArr = Array();
		$sql = 'SELECT t.tid, ts.family, t.sciname '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'INNER JOIN fmchklsttaxalink ctl ON t.tid = ctl.tid '.
			'LEFT JOIN fmvouchers v ON ctl.clid = v.clid AND ctl.tid = v.tid '.
			'WHERE v.clid IS NULL AND (ctl.clid = '.$this->clid.') AND ts.taxauthid = 1 '.
			'ORDER BY ts.family, t.sciname '.
			'LIMIT '.($startLimit?$startLimit.',':'').'100';
		//echo '<div>'.$sql.'</div>';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->family][$row->tid] = $row->sciname;
		}
		$rs->free();
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
			$retArr[$row->tid]['recordnumber'] = $collStr;
			$retArr[$row->tid]['specid'] = $row->sciname;
			$idBy = $row->identifiedby;
			if($row->dateidentified) $idBy .= ' ('.$row->dateidentified.')';
			$retArr[$row->tid]['identifiedby'] = $idBy;
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
				$retArr[$row->tidinterpreted] = $row->sciname;
			}
			$rs->free();
		}
		return $retArr;
	}

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

		$this->setDynamicSql();
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
					$row["catalognumber"].'","'.$row["identifiedby"].'","'.$row["dateidentified"].'","'.$row["recordedby"].'","'.
					$row["recordnumber"].'","'.$row["eventdate"].'","'.$row["country"].'","'.$row["stateprovince"].'","'.
					$row["county"].'","'.$row["municipality"].'",';
				
				$localSecurity = ($row["localitysecurity"]?$row["localitysecurity"]:0); 
				if($canReadRareSpp || $localSecurity != 1 || (array_key_exists("RareSppReader", $userRights) && in_array($row["collid"],$userRights["RareSppReader"]))){
					echo '"'.$this->cleanStr($row["locality"]).'",'.$row["decimallatitude"].','.$row["decimallongitude"].','.
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
	
	public function getEditors(){
		$editorArr = array();
		$sql = 'SELECT u.uid, CONCAT_WS(", ",u.lastname,u.firstname) as uname '.
			'FROM userpermissions up INNER JOIN users u ON up.uid = u.uid '.
			'WHERE up.pname = "ClAdmin-'.$this->clid.'" ORDER BY u.lastname,u.firstname';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$uName = $r->uname;
				if(strlen($uName) > 60) $uName = substr($uName,0,60);
				$editorArr[$r->uid] = $r->uname;
			}
			$rs->free();
		}
		return $editorArr;
	}

	public function addEditor($u){
		$statusStr = '';
		$sql = 'INSERT INTO userpermissions(uid,pname) '.
			'VALUES('.$u.',"ClAdmin-'.$this->clid.'")';
		if(!$this->conn->query($sql)){
			$statusStr = 'ERROR: unable to add editor; SQL: '.$this->conn->error;
		}
		return $statusStr;
	}

	public function deleteEditor($u){
		$statusStr = '';
		$sql = 'DELETE FROM userpermissions '.
			'WHERE uid = '.$u.' AND pname = "ClAdmin-'.$this->clid.'"';
		if(!$this->conn->query($sql)){
			$statusStr = 'ERROR: unable to remove editor; SQL: '.$this->conn->error;
		}
		return $statusStr;
	}

	//Misc set/get functions
	public function setClid($clid){
		if(is_numeric($clid)){
			$this->clid = $clid;
		}
	}
	
	public function getClName(){
		return $this->clName;
	}
	
	public function setPid($id){
		if(is_numeric($id)){
			$this->pid = $id;
		}
	}

	//Get list data
	public function getPoints($tid){
		$retArr = array();
		$sql = 'SELECT c.chklstcoordid, c.decimallatitude, c.decimallongitude, c.notes '.
			'FROM fmchklstcoordinates c '.
			'WHERE c.clid = '.$this->clid.' AND c.tid = '.$tid;
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->chklstcoordid]['lat'] = $r->decimallatitude;
			$retArr[$r->chklstcoordid]['lng'] = $r->decimallongitude;
			$retArr[$r->chklstcoordid]['notes'] = $r->notes;
		}
		$rs->free();
		return $retArr;
	}
	
	public function getTaxa(){
		$retArr = array();
		$sql = 'SELECT t.tid, t.sciname '.
			'FROM fmchklsttaxalink l INNER JOIN taxa t ON l.tid = t.tid '.
			'WHERE l.clid = '.$this->clid.' ORDER BY t.sciname';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->tid] = $r->sciname;
		}
		$rs->free();
		return $retArr;
	}
	
	public function getUserList(){
		$returnArr = Array();
		$sql = 'SELECT u.uid, CONCAT_WS(", ",u.lastname,u.firstname) AS uname '.
			'FROM users u '.
			'ORDER BY u.lastname,u.firstname';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$returnArr[$r->uid] = $r->uname;
		}
		$rs->free();
		return $returnArr;
	}

	public function getVoucherProjects(){
		global $userRights;
		$retArr = array();
		$runQuery = true;
		$sql = 'SELECT collid, collectionname '.
			'FROM omcollections WHERE (colltype = "Observations" OR colltype = "General Observations") ';
		if(!array_key_exists('SuperAdmin',$userRights)){
			$collInStr = '';
			foreach($userRights as $k => $v){
				if($k == 'CollAdmin' || $k == 'CollEditor'){
					$collInStr .= ','.implode(',',$v);
				}
			}
			if($collInStr){
				$sql .= 'AND collid IN ('.substr($collInStr,1).') ';
			}
			else{
				$runQuery = false;
			}
		}
		$sql .= 'ORDER BY colltype,collectionname';
		//echo $sql;
		if($runQuery){
			if($rs = $this->conn->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr[$r->collid] = $r->collectionname;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function getParentArr(){
		$retArr = array();
		$sql = 'SELECT c.clid, c.name FROM fmchecklists c WHERE type = "static" AND access <> "private" ORDER BY c.name';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->clid] = $row->name;
		}
		$rs->free();
		return $retArr;
	}

	private function cleanStr($str){
 		$newStr = trim($str);
 		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = str_replace('"',"&quot;",$newStr);
		$newStr = str_replace("'","&apos;",$newStr);
 		$newStr = $this->conn->real_escape_string($newStr);
 		return $newStr;
 	}
}
?>