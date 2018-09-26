<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherAdmin.php');

class ChecklistManager {

	private $conn;
	private $clid;
	private $dynClid;
	private $clName;
	private $clMetadata;
	private $childClidArr = array();
	private $voucherArr = array();
	private $pid = '';
	private $projName = '';
	private $taxaList = Array();
	private $language = "English";
	private $thesFilter = 0;
	private $taxonFilter;
	private $showAuthors;
	private $showCommon;
	private $showImages;
	private $showVouchers;
	private $showAlphaTaxa;
	private $searchCommon;
	private $searchSynonyms;
	private $filterArr = Array();
	private $imageLimit = 100;
	private $taxaLimit = 500;
	private $speciesCount = 0;
	private $taxaCount = 0;
	private $familyCount = 0;
	private $genusCount = 0;
	private $basicSql;

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function setClid($clid){
		if(is_numeric($clid)){
			$this->clid = $clid;
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

	public function setDynClid($did){
		if(is_numeric($did)){
			$this->dynClid = $did;
		}
	}

	public function getClMetaData(){
		$sql = "";
		if($this->clid){
			$sql = 'SELECT c.clid, c.name, c.locality, c.publication, c.abstract, c.authors, c.parentclid, c.notes, '.
				'c.latcentroid, c.longcentroid, c.pointradiusmeters, c.footprintwkt, c.access, c.defaultSettings, '.
				'c.dynamicsql, c.datelastmodified, c.uid, c.type, c.initialtimestamp '.
				'FROM fmchecklists c WHERE (c.clid = '.$this->clid.')';
		}
		elseif($this->dynClid){
			$sql = "SELECT c.dynclid AS clid, c.name, c.details AS locality, c.notes, c.uid, c.type, c.initialtimestamp ".
				"FROM fmdynamicchecklists c WHERE (c.dynclid = ".$this->dynClid.')';
		}
		if($sql){
		 	$result = $this->conn->query($sql);
			if($result){
		 		if($row = $result->fetch_object()){
					$this->clName = $row->name;
					$this->clMetadata["locality"] = $row->locality;
					//clMetadata
					$this->clMetadata["notes"] = $row->notes;
					$this->clMetadata["type"] = $row->type;
					if($this->clid){
						$this->clMetadata["publication"] = $row->publication;
						$this->clMetadata["abstract"] = $row->abstract;
						$this->clMetadata["authors"] = $row->authors;
						$this->clMetadata["parentclid"] = $row->parentclid;
						$this->clMetadata["uid"] = $row->uid;
						$this->clMetadata["latcentroid"] = $row->latcentroid;
						$this->clMetadata["longcentroid"] = $row->longcentroid;
						$this->clMetadata["pointradiusmeters"] = $row->pointradiusmeters;
						$this->clMetadata['footprintwkt'] = $row->footprintwkt;
						$this->clMetadata["access"] = $row->access;
						$this->clMetadata["defaultSettings"] = $row->defaultSettings;
						$this->clMetadata["dynamicsql"] = $row->dynamicsql;
						$this->clMetadata["datelastmodified"] = $row->datelastmodified;
					}
		    	}
		    	$result->free();
			}
			else{
				trigger_error('ERROR: unable to set checklist metadata => '.$sql, E_USER_ERROR);
			}
		}
		return $this->clMetadata;
	}

	public function echoFilterList(){
		echo "'".implode("','",$this->filterArr)."'";
	}

	public function getTaxonAuthorityList(){
    	$taxonAuthList = Array();
		$sql = "SELECT ta.taxauthid, ta.name FROM taxauthority ta WHERE (ta.isactive <> 0)";
 		$rs = $this->conn->query($sql);
		while ($row = $rs->fetch_object()){
			$taxonAuthList[$row->taxauthid] = $row->name;
		}
		$rs->free();
		return $taxonAuthList;
	}

	//return an array: family => array(TID => sciName)
	public function getTaxaList($pageNumber = 1,$retLimit = 500){
		if(!$this->clid && !$this->dynClid) return;
		//Get species list
		$speciesPrev="";$taxonPrev="";
		$tidReturn = Array();
        $genusCntArr = Array();
        $familyCntArr = Array();
		if($this->showImages && $retLimit) $retLimit = $this->imageLimit;
		if(!$this->basicSql) $this->setClSql();
		$result = $this->conn->query($this->basicSql);
		while($row = $result->fetch_object()){
			$family = strtoupper($row->family);
			if(!$family) $family = 'Family Incertae Sedis';
			$this->filterArr[$family] = '';
			$tid = $row->tid;
			$sciName = $this->cleanOutStr($row->sciname);
			$taxonTokens = explode(" ",$sciName);
			if(in_array("x",$taxonTokens) || in_array("X",$taxonTokens)){
				if(in_array("x",$taxonTokens)) unset($taxonTokens[array_search("x",$taxonTokens)]);
				if(in_array("X",$taxonTokens)) unset($taxonTokens[array_search("X",$taxonTokens)]);
				$newArr = array();
				foreach($taxonTokens as $v){
					$newArr[] = $v;
				}
				$taxonTokens = $newArr;
			}
			if(!$retLimit || ($this->taxaCount >= (($pageNumber-1)*$retLimit) && $this->taxaCount <= ($pageNumber)*$retLimit)){
				if(count($taxonTokens) == 1) $sciName .= " sp.";
				if($this->showVouchers){
					$clStr = "";
					if($row->habitat) $clStr = ", ".$row->habitat;
					if($row->abundance) $clStr .= ", ".$row->abundance;
					if($row->notes) $clStr .= ", ".$row->notes;
					if($row->source) $clStr .= ", <u>source</u>: ".$row->source;
					if($clStr) $this->taxaList[$tid]["notes"] = substr($clStr,2);
				}
				$this->taxaList[$tid]["sciname"] = $sciName;
				$this->taxaList[$tid]["family"] = $family;
				if(isset($this->taxaList[$tid]['clid'])) $this->taxaList[$tid]['clid'] = $this->taxaList[$tid]['clid'].','.$row->clid;
				else $this->taxaList[$tid]['clid'] = $row->clid;
				$tidReturn[] = $tid;
				if($this->showAuthors){
					$this->taxaList[$tid]["author"] = $this->cleanOutStr($row->author);
				}
    		}
            if(!in_array($family,$familyCntArr)){
                $familyCntArr[] = $family;
            }
            if(!in_array($taxonTokens[0],$genusCntArr)){
                $genusCntArr[] = $taxonTokens[0];
            }
			$this->filterArr[$taxonTokens[0]] = "";
    		if(count($taxonTokens) > 1 && $taxonTokens[0]." ".$taxonTokens[1] != $speciesPrev){
    			$this->speciesCount++;
    			$speciesPrev = $taxonTokens[0]." ".$taxonTokens[1];
    		}
    		if(!$taxonPrev || strpos($sciName,$taxonPrev) === false){
    			$this->taxaCount++;
    		}
    		$taxonPrev = implode(" ",$taxonTokens);
		}
        $this->familyCount = count($familyCntArr);
        $this->genusCount = count($genusCntArr);
		$this->filterArr = array_keys($this->filterArr);
		sort($this->filterArr);
		$result->free();
		if($this->taxaCount < (($pageNumber-1)*$retLimit)){
			$this->taxaCount = 0; $this->genusCount = 0; $this->familyCount = 0;
			unset($this->filterArr);
			return $this->getTaxaList(1,$retLimit);
		}
		//Get voucher data; note that dynclid list won't have vouchers
		if($this->taxaList){
			if($this->showVouchers){
				$clidStr = $this->clid;
				if($this->childClidArr){
					$clidStr .= ','.implode(',',$this->childClidArr);
				}
				$vSql = 'SELECT DISTINCT v.tid, v.occid, c.institutioncode, v.notes, o.catalognumber, o.recordedby, o.recordnumber, o.eventdate '.
					'FROM fmvouchers v INNER JOIN omoccurrences o ON v.occid = o.occid '.
					'INNER JOIN omcollections c ON o.collid = c.collid '.
					'WHERE (v.clid IN ('.$clidStr.')) AND v.tid IN('.implode(',',array_keys($this->taxaList)).') '.
					'ORDER BY o.collid';
				if($this->thesFilter){
					$vSql = 'SELECT DISTINCT ts.tidaccepted AS tid, v.occid, c.institutioncode, v.notes, o.catalognumber, o.recordedby, o.recordnumber, o.eventdate '.
						'FROM fmvouchers v INNER JOIN omoccurrences o ON v.occid = o.occid '.
						'INNER JOIN omcollections c ON o.collid = c.collid '.
						'INNER JOIN taxstatus ts ON v.tid = ts.tid '.
						'WHERE (ts.taxauthid = '.$this->thesFilter.') AND (v.clid IN ('.$clidStr.')) '.
						'AND (ts.tidaccepted IN('.implode(',',array_keys($this->taxaList)).')) '.
						'ORDER BY o.collid';
				}
				//echo $vSql; exit;
		 		$vResult = $this->conn->query($vSql);
				while ($row = $vResult->fetch_object()){
					$collector = ($row->recordedby?$row->recordedby:$row->catalognumber);
					if(strlen($collector) > 25){
						//Collector string is too big, thus reduce
						$strPos = strpos($collector,';');
						if(!$strPos) $strPos = strpos($collector,',');
						if(!$strPos) $strPos = strpos($collector,' ',10);
						if($strPos) $collector = substr($collector,0,$strPos).'...';
					}
					if($row->recordnumber) $collector .= ' '.$row->recordnumber;
					else $collector .= ' '.$row->eventdate;
					$collector .= ' ['.$row->institutioncode.']';
					$this->voucherArr[$row->tid][$row->occid] = $collector;
				}
				$vResult->free();
			}
			if($this->showImages) $this->setImages($tidReturn);
			if($this->showCommon) $this->setVernaculars($tidReturn);
		}
		return $this->taxaList;
	}

	private function setImages($tidReturn){
		if($tidReturn){
			$sql = 'SELECT i2.tid, i.url, i.thumbnailurl FROM images i INNER JOIN '.
				'(SELECT ts1.tid, SUBSTR(MIN(CONCAT(LPAD(i.sortsequence,6,"0"),i.imgid)),7) AS imgid '.
				'FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN images i ON ts2.tid = i.tid '.
				'WHERE i.sortsequence < 500 AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 '.
				'AND (ts1.tid IN('.implode(',',$tidReturn).')) '.
				'GROUP BY ts1.tid) i2 ON i.imgid = i2.imgid';
			//echo $sql;
			$rs = $this->conn->query($sql);
			$matchedArr = array();
			while($row = $rs->fetch_object()){
				$this->taxaList[$row->tid]["url"] = $row->url;
				$this->taxaList[$row->tid]["tnurl"] = $row->thumbnailurl;
				$matchedArr[] = $row->tid;
			}
			$rs->free();
			$missingArr = array_diff(array_keys($this->taxaList),$matchedArr);
			if($missingArr){
				//Get children images
				$sql2 = 'SELECT i2.tid, i.url, i.thumbnailurl FROM images i INNER JOIN '.
					'(SELECT ts1.parenttid AS tid, SUBSTR(MIN(CONCAT(LPAD(i.sortsequence,6,"0"),i.imgid)),7) AS imgid '.
					'FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
					'INNER JOIN images i ON ts2.tid = i.tid '.
					'WHERE i.sortsequence < 500 AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 '.
					'AND (ts1.parenttid IN('.implode(',',$missingArr).')) '.
					'GROUP BY ts1.tid) i2 ON i.imgid = i2.imgid';
				//echo $sql;
				$rs2 = $this->conn->query($sql2);
				while($row2 = $rs2->fetch_object()){
					$this->taxaList[$row2->tid]["url"] = $row2->url;
					$this->taxaList[$row2->tid]["tnurl"] = $row2->thumbnailurl;
				}
				$rs2->free();
			}
		}
	}

	private function setVernaculars($tidReturn){
		if($tidReturn){
			$tempVernArr = array();
			$sql = 'SELECT ts1.tid, v.vernacularname '.
				'FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN taxavernaculars v ON ts2.tid = v.tid '.
				'WHERE ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND (ts1.tid IN('.implode(',',$tidReturn).')) ';
			if($this->language) $sql .= 'AND v.language = "'.$this->language.'" ';
			$sql .= 'ORDER BY v.sortsequence DESC ';
			//echo $sql; exit;
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				if($r->vernacularname) $this->taxaList[$r->tid]['vern'] = $this->cleanOutStr($r->vernacularname);
			}
			$rs->free();
		}
	}

	public function getVoucherCoordinates($tid = 0,$abbreviated=false){
		$retArr = array();
		if(!$this->basicSql) $this->setClSql();
		if($this->clid){
			//Add children checklists to query
			$clidStr = $this->clid;
			if($this->childClidArr){
				$clidStr .= ','.implode(',',$this->childClidArr);
			}

			$retCnt = 0;
			//Grab general points
			try{
				$sql1 = '';
				if($tid){
					$sql1 = 'SELECT DISTINCT cc.tid, t.sciname, cc.decimallatitude, cc.decimallongitude, cc.notes '.
						'FROM fmchklstcoordinates cc INNER JOIN taxa t ON cc.tid = t.tid '.
						'WHERE cc.tid = '.$tid.' AND cc.clid IN ('.$clidStr.') AND cc.decimallatitude IS NOT NULL AND cc.decimallongitude IS NOT NULL ';
				}
				else{
					$sql1 = 'SELECT DISTINCT cc.tid, t.sciname, cc.decimallatitude, cc.decimallongitude, cc.notes '.
						'FROM fmchklstcoordinates cc INNER JOIN ('.$this->basicSql.') t ON cc.tid = t.tid '.
						'WHERE cc.clid IN ('.$clidStr.') AND cc.decimallatitude IS NOT NULL AND cc.decimallongitude IS NOT NULL ';
				}
				if($abbreviated){
					$sql1 .= 'ORDER BY RAND() LIMIT 50';
				}
				//echo $sql1;
				$rs1 = $this->conn->query($sql1);
				if($rs1){
					while($r1 = $rs1->fetch_object()){
						if($abbreviated){
							$retArr[] = $r1->decimallatitude.','.$r1->decimallongitude;
						}
						else{
							$retArr[$r1->tid][] = array('ll'=>$r1->decimallatitude.','.$r1->decimallongitude,'sciname'=>$this->cleanOutStr($r1->sciname),'notes'=>$this->cleanOutStr($r1->notes));
						}
						$retCnt++;
					}
					$rs1->free();
				}
			}
			catch(Exception $e){
				echo 'Caught exception getting general coordinates: ',  $e->getMessage(), "\n";
			}

			if(!$abbreviated || $retCnt < 50){
				try{
					//Grab voucher points
					$sql2 = 'SELECT DISTINCT v.tid, o.occid, o.decimallatitude, o.decimallongitude, '.
							'CONCAT(o.recordedby," (",IFNULL(o.recordnumber,o.eventdate),")") as notes '.
							'FROM omoccurrences o INNER JOIN fmvouchers v ON o.occid = v.occid ';
					if($tid){
						$sql2 .= 'WHERE v.tid = '.$tid.' AND v.clid IN ('.$clidStr.') AND o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL '.
							'AND (o.localitysecurity = 0 OR o.localitysecurity IS NULL) ';
					}
					else{
						$sql2 .= 'INNER JOIN ('.$this->basicSql.') t ON v.tid = t.tid '.
							'WHERE v.clid IN ('.$clidStr.') AND o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL '.
							'AND (o.localitysecurity = 0 OR o.localitysecurity IS NULL) ';
					}
					if($abbreviated){
						$sql2 .= 'ORDER BY RAND() LIMIT 50';
					}
					//echo $sql2;
					$rs2 = $this->conn->query($sql2);
					if($rs2){
						while($r2 = $rs2->fetch_object()){
							if($abbreviated){
								$retArr[] = $r2->decimallatitude.','.$r2->decimallongitude;
							}
							else{
								$retArr[$r2->tid][] = array('ll'=>$r2->decimallatitude.','.$r2->decimallongitude,'notes'=>$this->cleanOutStr($r2->notes),'occid'=>$r2->occid);
							}
							$retCnt++;
						}
						$rs2->free();
					}
				}
				catch(Exception $e){
					//echo 'Caught exception getting voucher coordinates: ',  $e->getMessage(), "\n";
				}
			}
		}
		return $retArr;
	}

	public function getPolygonCoordinates(){
		$retArr = array();
		if($this->clid){
			if($this->clMetadata['dynamicsql']){
				$sql = 'SELECT o.decimallatitude, o.decimallongitude FROM omoccurrences o ';
				if($this->clMetadata['footprintwkt'] && substr($this->clMetadata['footprintwkt'],0,7) == 'POLYGON'){
					$sql .= 'INNER JOIN omoccurpoints p ON o.occid = p.occid WHERE (ST_Within(p.point,GeomFromText("'.$this->clMetadata['footprintwkt'].'"))) ';
				}
				else{
					$this->voucherManager = new ChecklistVoucherAdmin($this->conn);
					$this->voucherManager->setClid($this->clid);
					$this->voucherManager->setCollectionVariables();
					$sql .= 'WHERE ('.$this->voucherManager->getSqlFrag().') ';
				}
				$sql .= 'LIMIT 50';
				//echo $sql; exit;
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$retArr[] = $r->decimallatitude.','.$r->decimallongitude;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function downloadChecklistCsv(){
    	if(!$this->basicSql) $this->setClSql();
		//Output checklist
    	$fileName = $this->clName."_".time().".csv";
    	header ('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header ('Content-Type: text/csv');
		header ("Content-Disposition: attachment; filename=\"$fileName\"");
		$this->showAuthors = 1;
		if($taxaArr = $this->getTaxaList(1,0)){
			$fh = fopen('php://output', 'w');
			$headerArr = array('Family','ScientificName','ScientificNameAuthorship');
			if($this->showCommon) $headerArr[] = 'CommonName';
			$headerArr[] = 'Notes';
			$headerArr[] = 'TaxonId';
			fputcsv($fh,$headerArr);
			foreach($taxaArr as $tid => $tArr){
				unset($outArr);
				$outArr = array($tArr['family'],html_entity_decode($tArr['sciname'],ENT_QUOTES|ENT_XML1),html_entity_decode($tArr['author'],ENT_QUOTES|ENT_XML1));
				if($this->showCommon) $outArr[] = (array_key_exists('vern',$tArr)?html_entity_decode($tArr['vern'],ENT_QUOTES|ENT_XML1):'');
				$outArr[] = (array_key_exists('notes',$tArr)?strip_tags(html_entity_decode($tArr['notes'],ENT_QUOTES|ENT_XML1)):'');
				$outArr[] = $tid;
				fputcsv($fh,$outArr);
			}
			fclose($fh);
		}
		else{
			echo "Recordset is empty.\n";
		}
    }

	private function setClSql(){
		if($this->clid){
			$clidStr = $this->clid;
			if($this->childClidArr){
				$clidStr .= ','.implode(',',$this->childClidArr);
			}
			if($this->thesFilter){
				$this->basicSql = 'SELECT t.tid, ctl.clid, IFNULL(ctl.familyoverride,ts.family) AS family, t.sciname, t.author, ctl.habitat, ctl.abundance, ctl.notes, ctl.source '.
					'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted '.
					'INNER JOIN fmchklsttaxalink ctl ON ts.tid = ctl.tid '.
			  		'WHERE (ts.taxauthid = '.$this->thesFilter.') AND (ctl.clid IN ('.$clidStr.')) ';
			}
			else{
				$this->basicSql = 'SELECT t.tid, ctl.clid, IFNULL(ctl.familyoverride,ts.family) AS family, t.sciname, t.author, ctl.habitat, ctl.abundance, ctl.notes, ctl.source '.
					'FROM taxa t INNER JOIN fmchklsttaxalink ctl ON t.tid = ctl.tid '.
					'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			  		'WHERE (ts.taxauthid = 1) AND (ctl.clid IN ('.$clidStr.')) ';
			}
		}
		else{
			$this->basicSql = 'SELECT t.tid, ctl.dynclid as clid, ts.family, t.sciname, t.author '.
				'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
				'INNER JOIN fmdyncltaxalink ctl ON t.tid = ctl.tid '.
    	  		'WHERE (ts.taxauthid = '.($this->thesFilter?$this->thesFilter:'1').') AND (ctl.dynclid = '.$this->dynClid.') ';
		}
		if($this->taxonFilter){
			if($this->searchCommon){
				$this->basicSql .= 'AND ts.tidaccepted IN(SELECT ts2.tidaccepted FROM taxavernaculars v INNER JOIN taxstatus ts2 ON v.tid = ts2.tid WHERE (v.vernacularname LIKE "%'.$this->taxonFilter.'%")) ';
			}
			else{
				//Search direct name, which is particularly good for a genera term
				$sqlWhere = 'OR (t.SciName Like "'.$this->taxonFilter.'%") ';
				if($this->clid && (substr($this->taxonFilter,-5) == 'aceae' || substr($this->taxonFilter,-4) == 'idae')){
					//Include taxn filter in familyoverride
					$sqlWhere .= "OR (ctl.familyoverride = '".$this->taxonFilter."') ";
				}
				if($this->searchSynonyms){
					$sqlWhere .= "OR (ts.tidaccepted IN(SELECT ts2.tidaccepted FROM taxa t2 INNER JOIN taxstatus ts2 ON t2.tid = ts2.tid WHERE (t2.sciname Like '".$this->taxonFilter."%') ";
					//if(substr_count($this->taxonFilter,' ') > 1) $sqlWhere .= 'AND (t2.rankid = 220 OR ts2.tid = ts2.tidaccepted) ';
					$sqlWhere .= ")) ";
				}
				//Include parents
				$sqlWhere .= 'OR (t.tid IN(SELECT e.tid '.
					'FROM taxa t3 INNER JOIN taxaenumtree e ON t3.tid = e.parenttid '.
					'WHERE (e.taxauthid = '.($this->thesFilter?$this->thesFilter:'1').') AND (t3.sciname = "'.$this->taxonFilter.'")))';
				if($sqlWhere) $this->basicSql .= 'AND ('.substr($sqlWhere,2).') ';
			}
		}
		if($this->showAlphaTaxa){
			$this->basicSql .= " ORDER BY sciname";
		}
		else{
			$this->basicSql .= " ORDER BY family, sciname";
		}
		//echo $this->basicSql; exit;
	}

	//Checklist editing functions
	public function addNewSpecies($dataArr){
		if(!$this->clid) return 'ERROR adding species: checklist identifier not set';
		$insertStatus = false;
		$colSql = '';
		$valueSql = '';
		foreach($dataArr as $k =>$v){
			$colSql .= ','.$k;
			if($v){
				if(is_numeric($v)){
					$valueSql .= ','.$v;
				}
				else{
					$valueSql .= ',"'.$this->cleanInStr($v).'"';
				}
			}
			else{
				$valueSql .= ',NULL';
			}
		}
		$conn = MySQLiConnectionFactory::getCon('write');
		$sql = 'INSERT INTO fmchklsttaxalink (clid'.$colSql.') VALUES ('.$this->clid.$valueSql.')';
		if($conn->query($sql)){
			if($this->clMetadata['type'] == 'rarespp' && $this->clMetadata['locality'] && is_numeric($dataArr['tid'])){
				$sqlRare = 'UPDATE omoccurrences o INNER JOIN taxstatus ts1 ON o.tidinterpreted = ts1.tid '.
					'INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
					'SET o.localitysecurity = 1 '.
					'WHERE (o.localitysecurity IS NULL OR o.localitysecurity = 0) AND (o.localitySecurityReason IS NULL) '.
					'AND (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (o.stateprovince = "'.$this->clMetadata['locality'].'") AND (ts2.tid = '.$dataArr['tid'].')';
				//echo $sqlRare; exit;
				$conn->query($sqlRare);
			}
		}
		else{
			$mysqlErr = $conn->error;
			$insertStatus = 'ERROR adding species: ';
			if(strpos($mysqlErr,'Duplicate') !== false){
				$insertStatus .= 'Species already exists within checklist';
			}
			else{
				$insertStatus .= $conn->error;
			}
		}
		$conn->close();
		return $insertStatus;
	}

	//Checklist index page fucntions
	public function getChecklists(){
		$retArr = Array();
		$sql = 'SELECT p.pid, p.projname, p.ispublic, c.clid, c.name, c.access '.
			'FROM fmchecklists c LEFT JOIN fmchklstprojlink cpl ON c.clid = cpl.clid '.
			'LEFT JOIN fmprojects p ON cpl.pid = p.pid '.
			'WHERE ((c.access LIKE "public%") ';
		if(isset($GLOBALS['USER_RIGHTS']['ClAdmin']) && $GLOBALS['USER_RIGHTS']['ClAdmin']) $sql .= 'OR (c.clid IN('.implode(',',$GLOBALS['USER_RIGHTS']['ClAdmin']).'))';
		$sql .= ') AND ((p.pid IS NULL) OR (p.ispublic = 1) ';
		if(isset($GLOBALS['USER_RIGHTS']['ProjAdmin']) && $GLOBALS['USER_RIGHTS']['ProjAdmin']) $sql .= 'OR (p.pid IN('.implode(',',$GLOBALS['USER_RIGHTS']['ProjAdmin']).'))';
		$sql .= ') ';
		if($this->pid) $sql .= 'AND (p.pid = '.$this->pid.') ';
		$sql .= 'ORDER BY p.projname, c.Name';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			if($row->pid){
				$pid = $row->pid;
				$projName = $row->projname.(!$row->ispublic?' (Private)':'');
			}
			else{
				$pid = 0;
				$projName = 'Undefinded Inventory Project';
			}
			$retArr[$pid]['name'] = $this->cleanOutStr($projName);
			$retArr[$pid]['clid'][$row->clid] = $this->cleanOutStr($row->name).($row->access=='private'?' (Private)':'');
		}
		$rs->free();
		if(isset($retArr[0])){
			$tempArr = $retArr[0];
			unset($retArr[0]);
			$retArr[0] = $tempArr;
		}
		return $retArr;
	}

	public function getResearchPoints(){
		$retArr = array();
		$sql = 'SELECT c.clid, c.name, c.latcentroid, c.longcentroid '.
			'FROM fmchecklists c LEFT JOIN fmchklstprojlink cpl ON c.CLID = cpl.clid '.
			'LEFT JOIN fmprojects p ON cpl.pid = p.pid '.
			'WHERE (c.latcentroid IS NOT NULL) AND (c.longcentroid IS NOT NULL) ';
		if($this->pid) $sql .= 'AND (p.pid = '.$this->pid.') ';
		else $sql .= 'AND (p.pid IS NULL) ';
		$sql .= 'AND ((c.access LIKE "public%") ';
		if(isset($GLOBALS['USER_RIGHTS']['ClAdmin']) && $GLOBALS['USER_RIGHTS']['ClAdmin']) $sql .= 'OR (c.clid IN('.implode(',',$GLOBALS['USER_RIGHTS']['ClAdmin']).'))';
		$sql .= ') ';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->clid]['name'] = $this->cleanOutStr($row->name);
			$retArr[$row->clid]['lat'] = $row->latcentroid;
			$retArr[$row->clid]['lng'] = $row->longcentroid;
		}
		$rs->free();
		return $retArr;
	}

	//Taxon suggest functions
	public function getTaxonSearch($term, $clid, $deep=0){
		$retArr = array();
		$term = preg_replace('/\s{1}[\D]{1}\s{1}/i', ' _ ', trim($term));
		$term = preg_replace('/[^a-zA-Z_\-\. ]+/', '', $term);
		if(!is_numeric($clid)) $clid = 0;
		if($term && $clid){
			$sql = '(SELECT t.sciname '.
				'FROM taxa t INNER JOIN fmchklsttaxalink cl ON t.tid = cl.tid '.
				'WHERE t.sciname LIKE "'.$term.'%" AND cl.clid = '.$clid.') ';
			if($deep){
				$sql .= 'UNION DISTINCT '.
					'(SELECT DISTINCT t.sciname '.
					'FROM fmchklsttaxalink cl INNER JOIN taxaenumtree e ON cl.tid = e.tid '.
					'INNER JOIN taxa t ON e.parenttid = t.tid '.
					'WHERE e.taxauthid = 1 AND t.sciname LIKE "'.$term.'%" AND cl.clid = '.$clid.')';
			}
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[] = $r->sciname;
			}
			$rs->free();
			sort($retArr);
		}
		return $retArr;
	}

	public function getSpeciesSearch($term){
		$retArr = array();
		$term = preg_replace('/[^a-zA-Z\-\. ]+/', '', $term);
		$term = preg_replace('/\s{1}x{1}\s{0,1}$/i', ' _ ', $term);
		$term = preg_replace('/\s{1}[\D]{1}\s{1}/i', ' _ ', $term);
		if($term){
			$sql = 'SELECT tid, sciname FROM taxa WHERE (rankid > 179) AND (sciname LIKE "'.$term.'%") ORDER BY sciname';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$retArr[$r->tid]['id'] = $r->tid;
				$retArr[$r->tid]['value'] = $r->sciname;
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getUpperTaxa($term){
		$retArr = array();
		$param = "{$term}%";
		$sql = 'SELECT tid, sciname FROM taxa WHERE (rankid < 180) AND (sciname LIKE ?) ORDER BY sciname';
		$stmt = $this->conn->prepare($sql);
		$stmt->bind_param('s', $param);
		$stmt->execute();
		$stmt->bind_result($tid,$sciname);
		while ($stmt->fetch()) {
			$retArr[$tid]['id'] = $tid;
			$retArr[$tid]['value'] = $sciname;
		}
		$stmt->close();
		return $retArr;
	}

	//Setters and getters
    public function setThesFilter($filt){
		$this->thesFilter = $filt;
	}

	public function getThesFilter(){
		return $this->thesFilter;
	}

	public function setTaxonFilter($tFilter){
		$term = preg_replace('/[^a-zA-Z\-\. ]+/', '', $tFilter);
		$this->taxonFilter = preg_replace('/\s{1}[\D]{1}\s{1}/i', ' _ ', $term);
	}

	public function setShowAuthors($value = 1){
		$this->showAuthors = $value;
	}

	public function setShowCommon($value = 1){
		$this->showCommon = $value;
	}

	public function setShowImages($value = 1){
		$this->showImages = $value;
	}

	public function setShowVouchers($value = 1){
		$this->showVouchers = $value;
	}

	public function setShowAlphaTaxa($value = 1){
		$this->showAlphaTaxa = $value;
	}

	public function setSearchCommon($value = 1){
		$this->searchCommon = $value;
	}

	public function setSearchSynonyms($value = 1){
		$this->searchSynonyms = $value;
	}

	public function getClid(){
		return $this->clid;
	}

	public function getChildClidArr(){
		return $this->childClidArr;
	}

	public function getVoucherArr(){
		return $this->voucherArr;
	}

	public function getClName(){
		return $this->clName;
	}

	public function setProj($pid){
		if(is_numeric($pid)){
			$sql = 'SELECT pid, projname FROM fmprojects WHERE (pid = '.$pid.')';
			if($rs = $this->conn->query($sql)){
				if($r = $rs->fetch_object()){
					$this->pid = $r->pid;
					$this->projName = $this->cleanOutStr($r->projname);
				}
				$rs->free();
			}
			else{
				trigger_error('ERROR: Unable to project => SQL: '.$sql, E_USER_WARNING);
			}
		}
		return $this->pid;
	}

	public function getProjName(){
		return $this->projName;
	}

	public function getPid(){
		return $this->pid;
	}

	public function setLanguage($l){
		$l = strtolower($l);
		if($l == "en"){
			$this->language = 'English';
		}
		elseif($l == "es"){
			$this->language = 'Spanish';
		}
		else{
			$this->language = $l;
		}
	}

	public function setImageLimit($cnt){
		$this->imageLimit = $cnt;
	}

	public function getImageLimit(){
		return $this->imageLimit;
	}

	public function setTaxaLimit($cnt){
		$this->taxaLimit = $cnt;
	}

	public function getTaxaLimit(){
		return $this->taxaLimit;
	}

	public function getTaxaCount(){
		return $this->taxaCount;
	}

	public function getFamilyCount(){
		return $this->familyCount;
	}

	public function getGenusCount(){
		return $this->genusCount;
	}

	public function getSpeciesCount(){
		return $this->speciesCount;
	}

	//Misc functions
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