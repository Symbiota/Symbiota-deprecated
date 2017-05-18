<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/SOLRManager.php');

class OccurrenceMaintenance {

	private $conn;
	private $destructConn = true;
	private $verbose = false;	// 0 = silent, 1 = echo as list item
	private $errorArr = array();

	public function __construct($con = null){
		if($con){
			//Inherits connection from another class
			$this->conn = $con;
			$this->destructConn = false;
		}
		else{
			$this->conn = MySQLiConnectionFactory::getCon("write");
		}
	}

	public function __destruct(){
		if($this->destructConn && !($this->conn === null)){
			$this->conn->close();
			$this->conn = null;
		}
 	}

	//General cleaning functions 
	public function generalOccurrenceCleaning($collId){
		set_time_limit(600);
		$status = true;

		if($this->verbose) $this->outputMsg('Updating null families of family rank identifications... ',1);
		$sql1 = 'SELECT occid FROM omoccurrences WHERE (family IS NULL) AND (sciname LIKE "%aceae" OR sciname LIKE "%idae")';
		$rs1 = $this->conn->query($sql1);
		$occidArr1 = array();
		while($r1 = $rs1->fetch_object()){
			$occidArr1[] = $r1->occid;
		}
		$rs1->free();
		if($occidArr1){
			$sql = 'UPDATE omoccurrences '.
				'SET family = sciname '.
				'WHERE occid IN('.implode(',',$occidArr1).')';
			if(!$this->conn->query($sql)){
				$errStr = 'WARNING: unable to update family; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				if($this->verbose) $this->outputMsg($errStr,2);
				$status = false;
			}
		}
		unset($occidArr1);
		
		if($this->verbose) $this->outputMsg('Updating null scientific names of family rank identifications... ',1);
		$sql1 = 'SELECT occid FROM omoccurrences WHERE family IS NOT NULL AND sciname IS NULL';
		$rs1 = $this->conn->query($sql1);
		$occidArr2 = array();
		while($r1 = $rs1->fetch_object()){
			$occidArr2[] = $r1->occid;
		}
		$rs1->free();
		if($occidArr2){
			$sql = 'UPDATE omoccurrences SET sciname = family WHERE occid IN('.implode(',',$occidArr2).') ';
			if(!$this->conn->query($sql)){
				$errStr = 'WARNING: unable to update sciname using family; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				if($this->verbose) $this->outputMsg($errStr,2);
				$status = false;
			}
		}
		unset($occidArr2);
		
		if($this->verbose) $this->outputMsg('Indexing valid scientific names (e.g. populating tidinterpreted)... ',1);
		$sql1 = 'SELECT o.occid FROM omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname '.
			'WHERE o.collid IN('.$collId.') AND o.TidInterpreted IS NULL';
		$rs1 = $this->conn->query($sql1);
		$occidArr3 = array();
		while($r1 = $rs1->fetch_object()){
			$occidArr3[] = $r1->occid;
		}
		$rs1->free();
		if($occidArr3){
			$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname '.
				'SET o.TidInterpreted = t.tid '. 
				'WHERE o.occid IN('.implode(',',$occidArr3).') ';
			if(!$this->conn->query($sql)){
				$errStr = 'WARNING: unable to update tidinterpreted; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				if($this->verbose) $this->outputMsg($errStr,2);
				$status = false;
			}
		}
		unset($occidArr3);
		
		if($this->verbose) $this->outputMsg('Updating and indexing occurrence images... ',1);
		$sql1 = 'SELECT o.occid FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
			'WHERE o.collid IN('.$collId.') AND (i.tid IS NULL) AND (o.tidinterpreted IS NOT NULL)';
		$rs1 = $this->conn->query($sql1);
		$occidArr4 = array();
		while($r1 = $rs1->fetch_object()){
			$occidArr4[] = $r1->occid;
		}
		$rs1->free();
		if($occidArr4){
			$sql = 'UPDATE omoccurrences o INNER JOIN images i ON o.occid = i.occid '. 
				'SET i.tid = o.tidinterpreted '. 
				'WHERE o.occid IN('.implode(',',$occidArr4).')';
			if(!$this->conn->query($sql)){
				$errStr = 'WARNING: unable to update image tid field; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				if($this->verbose) $this->outputMsg($errStr,2);
				$status = false;
			}
		}
		unset($occidArr4);
		
		if($this->verbose) $this->outputMsg('Updating null families using taxonomic thesaurus... ',1);
		$sql1 = 'SELECT o.occid FROM omoccurrences o INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid '.
			'WHERE o.collid IN('.$collId.') AND (ts.taxauthid = 1) AND (ts.family IS NOT NULL) AND (o.family IS NULL)';
		$rs1 = $this->conn->query($sql1);
		$occidArr5 = array();
		while($r1 = $rs1->fetch_object()){
			$occidArr5[] = $r1->occid;
		}
		$rs1->free();
		if($occidArr5){
			$sql = 'UPDATE omoccurrences o INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid '. 
				'SET o.family = ts.family '. 
				'WHERE o.occid IN('.implode(',',$occidArr5).')';
			if(!$this->conn->query($sql)){
				$errStr = 'WARNING: unable to update family in omoccurrence table; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				if($this->verbose) $this->outputMsg($errStr,2);
				$status = false;
			}
		}
		unset($occidArr5);

		#Updating records with null author
		if($this->verbose) $this->outputMsg('Updating null scientific authors using taxonomic thesaurus... ',1);
		$sql1 = 'SELECT o.occid FROM omoccurrences o INNER JOIN taxa t ON o.tidinterpreted = t.tid '.
			'WHERE o.scientificNameAuthorship IS NULL AND t.author IS NOT NULL LIMIT 5000 ';
		$rs1 = $this->conn->query($sql1);
		$occidArr6 = array();
		while($r1 = $rs1->fetch_object()){
			$occidArr6[] = $r1->occid;
		}
		$rs1->free();
		if($occidArr6){
			$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.tidinterpreted = t.tid '. 
				'SET o.scientificNameAuthorship = t.author '. 
				'WHERE (o.occid IN('.implode(',',$occidArr6).'))';
			if(!$this->conn->query($sql)){
				$errStr = 'WARNING: unable to update author; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				if($this->verbose) $this->outputMsg($errStr,2);
				$status = false;
			}
		}
		unset($occidArr6);
		
		/*
		if($this->verbose) $this->outputMsg('Updating georeference index... ',1);
		$sql = 'INSERT IGNORE INTO omoccurgeoindex(tid,decimallatitude,decimallongitude) '.
			'SELECT DISTINCT o.tidinterpreted, round(o.decimallatitude,3), round(o.decimallongitude,3) '.
			'FROM omoccurrences o '.
			'WHERE o.tidinterpreted IS NOT NULL AND o.decimallatitude IS NOT NULL '.
			'AND o.decimallongitude IS NOT NULL ';
		if(!$this->conn->query($sql)){
			$errStr = 'WARNING: unable to update georeference index; '.$this->conn->error;
			$this->errorArr[] = $errStr;
			if($this->verbose) $this->outputMsg($errStr,2);
			$status = false;
		}
		*/
		
		return $status;
	}
	
	//Protect Rare species data
	public function protectRareSpecies($collid = 0){
		$this->protectGloballyRareSpecies($collid);
		$this->protectStateRareSpecies($collid);
	}
	
	public function protectGloballyRareSpecies($collid = 0){
		$status = true;
		//protect globally rare species
		if($this->verbose) $this->outputMsg('Protecting globally rare species... ',1);
		$sensitiveArr = array();
		//Only protect names on list and synonym of accepted names 
		//Get names on list
		$sql = 'SELECT DISTINCT tid FROM taxa WHERE (SecurityStatus > 0)';
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$sensitiveArr[] = $r->tid; 
		}
		$rs->free();
		//Get synonyms of names on list
		$sql2 = 'SELECT DISTINCT ts.tid '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted '.
			'WHERE (ts.taxauthid = 1) AND (t.SecurityStatus > 0) AND (t.tid != ts.tid)';
		$rs2 = $this->conn->query($sql2);
		while($r2 = $rs2->fetch_object()){
			$sensitiveArr[] = $r2->tid;
		}
		$rs2->free();
		
		if($sensitiveArr){
			$sql2 = 'UPDATE omoccurrences o '.
				'SET o.LocalitySecurity = 1 '.
				'WHERE (o.LocalitySecurity IS NULL OR o.LocalitySecurity = 0) AND (o.localitySecurityReason IS NULL) AND (o.tidinterpreted IN('.implode(',',$sensitiveArr).'))';
			if(!$this->conn->query($sql2)){
				$errStr = 'WARNING: unable to protect globally rare species; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				if($this->verbose) $this->outputMsg($errStr,2);
				$status = false;
			}
		}
		return $status;
	}

	public function protectStateRareSpecies($collid = 0){
		$status = true;
		//Protect state level rare species
		if($this->verbose) $this->outputMsg('Protecting state level rare species... ',1);
		$sql = 'SELECT o.occid FROM omoccurrences o INNER JOIN taxstatus ts1 ON o.tidinterpreted = ts1.tid '.
			'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
			'INNER JOIN fmchecklists c ON o.stateprovince = c.locality '. 
			'INNER JOIN fmchklsttaxalink cl ON c.clid = cl.clid AND ts2.tid = cl.tid '.
			'WHERE (o.localitysecurity IS NULL OR o.localitysecurity = 0) AND (o.localitySecurityReason IS NULL) AND (c.type = "rarespp") '.
			'AND (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) ';
		if($collid) $sql .= ' AND o.collid IN('.$collid.') ';
		$rs = $this->conn->query($sql);
		$occArr = array();
		while($r = $rs->fetch_object()){
			$occArr[] = $r->occid;
		}
		$rs->free();
		
		if($occArr){
			$sql2 = 'UPDATE omoccurrences '.
				'SET localitysecurity = 1 '.
				'WHERE occid IN('.implode(',',$occArr).')';
			if(!$this->conn->query($sql2)){
				$errStr = 'WARNING: unable to protect state level rare species; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				if($this->verbose) $this->outputMsg($errStr,2);
				$status = false;
			}
		}
		return $status;
	}

	//Update statistics
	public function updateCollectionStats($collid, $full = false){
        global $SOLR_MODE;
	    set_time_limit(600);
		
		$recordCnt = 0;
		$georefCnt = 0;
		$familyCnt = 0;
		$genusCnt = 0;
		$speciesCnt = 0;
		if($full){
			$statsArr = Array();
			if($this->verbose) $this->outputMsg('Calculating specimen, georeference, family, genera, and species counts... ',1);
			$sql = 'SELECT COUNT(o.occid) AS SpecimenCount, COUNT(o.decimalLatitude) AS GeorefCount, '.
				'COUNT(DISTINCT o.family) AS FamilyCount, COUNT(o.typeStatus) AS TypeCount, '.
				'COUNT(DISTINCT CASE WHEN t.RankId >= 180 THEN t.UnitName1 ELSE NULL END) AS GeneraCount, '.
				'COUNT(CASE WHEN t.RankId >= 220 THEN o.occid ELSE NULL END) AS SpecimensCountID, '.
				'COUNT(DISTINCT CASE WHEN t.RankId = 220 THEN t.SciName ELSE NULL END) AS SpeciesCount, '.
				'COUNT(DISTINCT CASE WHEN t.RankId >= 220 THEN t.SciName ELSE NULL END) AS TotalTaxaCount '.
				'FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.TID '.
				'WHERE (o.collid IN('.$collid.')) ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$recordCnt = $r->SpecimenCount;
				$georefCnt = $r->GeorefCount;
				$familyCnt = $r->FamilyCount;
				$genusCnt = $r->GeneraCount;
				$speciesCnt = $r->SpeciesCount;
				$statsArr['SpecimensCountID'] = $r->SpecimensCountID;
				$statsArr['TotalTaxaCount'] = $r->TotalTaxaCount;
				$statsArr['TypeCount'] = $r->TypeCount;
			}
			$rs->free();

			if($this->verbose) $this->outputMsg('Calculating number of specimens imaged... ',1);
			$sql = 'SELECT count(DISTINCT o.occid) as imgcnt '.
				'FROM omoccurrences o INNER JOIN images i ON o.occid = i.occid '.
				'WHERE (o.collid IN('.$collid.')) ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$statsArr['imgcnt'] = $r->imgcnt;
			}
			$rs->free();

			if($this->verbose) $this->outputMsg('Calculating genetic resources counts... ',1);
			$sql = 'SELECT COUNT(CASE WHEN g.resourceurl LIKE "http://www.boldsystems%" THEN o.occid ELSE NULL END) AS boldcnt, '.
				'COUNT(CASE WHEN g.resourceurl LIKE "http://www.ncbi%" THEN o.occid ELSE NULL END) AS gencnt '.
				'FROM omoccurrences o INNER JOIN omoccurgenetic g ON o.occid = g.occid '.
				'WHERE (o.collid IN('.$collid.')) ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$statsArr['boldcnt'] = $r->boldcnt;
				$statsArr['gencnt'] = $r->gencnt;
			}
			$rs->free();

			if($this->verbose) $this->outputMsg('Calculating reference counts... ',1);
			$sql = 'SELECT count(r.occid) as refcnt '.
				'FROM omoccurrences o INNER JOIN referenceoccurlink r ON o.occid = r.occid '.
				'WHERE (o.collid IN('.$collid.')) ';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				$statsArr['refcnt'] = $r->refcnt;
			}
			$rs->free();

			if($this->verbose) $this->outputMsg('Calculating counts per family... ',1);
			$sql = 'SELECT o.family, COUNT(o.occid) AS SpecimensPerFamily, COUNT(o.decimalLatitude) AS GeorefSpecimensPerFamily, '.
				'COUNT(CASE WHEN t.RankId >= 220 THEN o.occid ELSE NULL END) AS IDSpecimensPerFamily, '.
				'COUNT(CASE WHEN t.RankId >= 220 AND o.decimalLatitude IS NOT NULL THEN o.occid ELSE NULL END) AS IDGeorefSpecimensPerFamily '.
				'FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.TID '.
				'WHERE (o.collid IN('.$collid.')) '.
				'GROUP BY o.family ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$family = str_replace(array('"',"'"),"",$r->family);
				if($family){
					$statsArr['families'][$family]['SpecimensPerFamily'] = $r->SpecimensPerFamily;
					$statsArr['families'][$family]['GeorefSpecimensPerFamily'] = $r->GeorefSpecimensPerFamily;
					$statsArr['families'][$family]['IDSpecimensPerFamily'] = $r->IDSpecimensPerFamily;
					$statsArr['families'][$family]['IDGeorefSpecimensPerFamily'] = $r->IDGeorefSpecimensPerFamily;
				}
			}
			$rs->free();
			
			if($this->verbose) $this->outputMsg('Calculating counts per country... ',1);
			$sql = 'SELECT o.country, COUNT(o.occid) AS CountryCount, COUNT(o.decimalLatitude) AS GeorefSpecimensPerCountry, '.
				'COUNT(CASE WHEN t.RankId >= 220 THEN o.occid ELSE NULL END) AS IDSpecimensPerCountry, '.
				'COUNT(CASE WHEN t.RankId >= 220 AND o.decimalLatitude IS NOT NULL THEN o.occid ELSE NULL END) AS IDGeorefSpecimensPerCountry '.
				'FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.TID '.
				'WHERE (o.collid IN('.$collid.')) '.
				'GROUP BY o.country ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$country = str_replace(array('"',"'"),"",$r->country);
				if($country){
					$statsArr['countries'][$country]['CountryCount'] = $r->CountryCount;
					$statsArr['countries'][$country]['GeorefSpecimensPerCountry'] = $r->GeorefSpecimensPerCountry;
					$statsArr['countries'][$country]['IDSpecimensPerCountry'] = $r->IDSpecimensPerCountry;
					$statsArr['countries'][$country]['IDGeorefSpecimensPerCountry'] = $r->IDGeorefSpecimensPerCountry;
				}
			}
			$rs->free();

			$returnArrJson = json_encode($statsArr);
			$sql = 'UPDATE omcollectionstats '.
				"SET dynamicProperties = '".$this->cleanInStr($returnArrJson)."' ".
				'WHERE collid IN('.$collid.') ';
			if(!$this->conn->query($sql)){
				$errStr = 'WARNING: unable to update collection stats table [1]; '.$this->conn->error;
				$this->errorArr[] = $errStr;
				if($this->verbose) $this->outputMsg($errStr,2);
			}
		}
		else{
			if($this->verbose) $this->outputMsg('Calculating specimen, georeference, family, genera, and species counts... ',1);
			$sql = 'SELECT COUNT(o.occid) AS SpecimenCount, COUNT(o.decimalLatitude) AS GeorefCount, COUNT(DISTINCT o.family) AS FamilyCount, '.
				'COUNT(DISTINCT CASE WHEN t.RankId >= 180 THEN t.UnitName1 ELSE NULL END) AS GeneraCount, '.
				'COUNT(DISTINCT CASE WHEN t.RankId = 220 THEN t.SciName ELSE NULL END) AS SpeciesCount '.
				'FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.TID '.
				'WHERE (o.collid IN('.$collid.')) ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$recordCnt = $r->SpecimenCount;
				$georefCnt = $r->GeorefCount;
				$familyCnt = $r->FamilyCount;
				$genusCnt = $r->GeneraCount;
				$speciesCnt = $r->SpeciesCount;
			}
		}
		
		$sql = 'UPDATE omcollectionstats cs '.
			'SET cs.recordcnt = '.$recordCnt.',cs.georefcnt = '.$georefCnt.',cs.familycnt = '.$familyCnt.',cs.genuscnt = '.$genusCnt.
			',cs.speciescnt = '.$speciesCnt.', cs.datelastmodified = CURDATE() '.
			'WHERE cs.collid IN('.$collid.')';
		if(!$this->conn->query($sql)){
			$errStr = 'WARNING: unable to update collection stats table [2]; '.$this->conn->error;
			$this->errorArr[] = $errStr;
			if($this->verbose) $this->outputMsg($errStr,2);
		}
		if($SOLR_MODE){
            $solrManager = new SOLRManager();
            $solrManager->updateSOLR();
        }
	}
	
	//Misc support functions
	public function getCollectionMetadata($collid){
		$retArr = array();
		if(is_numeric($collid)){
			$sql = 'SELECT institutioncode, collectioncode, collectionname, colltype, managementtype '.
				'FROM omcollections '.
				'WHERE collid = '.$collid;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr['instcode'] = $r->institutioncode;
				$retArr['collcode'] = $r->collectioncode;
				$retArr['collname'] = $r->collectionname;
				$retArr['colltype'] = $r->colltype;
				$retArr['mantype'] = $r->managementtype;
			}
			$rs->free();
		}
		return $retArr;
	}
	
	public function setVerbose($v){
		if($v){
			$this->verbose = true;
		}
		else{
			$this->verbose = false;
		}
	}

	public function getErrorArr(){
		return $this->errorArr;
	}

	private function outputMsg($str, $indent = 0){
		if($this->verbose){
			echo '<li style="margin-left:'.($indent*10).'px;">'.$str.'</li>';
		}
		ob_flush();
		flush();
	}

	private function cleanInStr($inStr){
		$retStr = trim($inStr);
		$retStr = preg_replace('/\s\s+/', ' ',$retStr);
		$retStr = $this->conn->real_escape_string($retStr);
		return $retStr;
	}
}
?>