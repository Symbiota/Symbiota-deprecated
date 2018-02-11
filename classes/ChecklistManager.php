<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');

class ChecklistManager {

	private $conn;
	private $clid;
	private $dynClid;
	private $clName;
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

	public function setClValue($clValue){
		$retStr = '';
		$clValue = $this->conn->real_escape_string($clValue);
		if(is_numeric($clValue)){
			$this->clid = $clValue;
		}
		else{
			$sql = 'SELECT c.clid FROM fmchecklists c WHERE (c.Name = "'.$clValue.'")';
			$rs = $this->conn->query($sql);
			if($rs){
				if($row = $rs->fetch_object()){
					$this->clid = $row->clid;
				}
				else{
					$retStr = '<h1>ERROR: invalid checklist identifier supplied ('.$clValue.')</h1>';
				}
				$rs->free();
			}
			else{
				trigger_error('ERROR setting checklist ID, SQL: '.$sql, E_USER_ERROR);
			}
		}
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
		return $retStr;
	}

	public function setDynClid($did){
		if(is_numeric($did)){
			$this->dynClid = $did;
		}
	}

	public function getClMetaData(){
		$retArr = array();
		$sql = "";
		if($this->clid){
			$sql = 'SELECT c.clid, c.name, c.locality, c.publication, '.
				'c.abstract, c.authors, c.parentclid, c.notes, '.
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
					$retArr["locality"] = $row->locality;
					$retArr["notes"] = $row->notes;
					$retArr["type"] = $row->type;
					if($this->clid){
						$retArr["publication"] = $row->publication;
						$retArr["abstract"] = $row->abstract;
						$retArr["authors"] = $row->authors;
						$retArr["parentclid"] = $row->parentclid;
						$retArr["uid"] = $row->uid;
						$retArr["latcentroid"] = $row->latcentroid;
						$retArr["longcentroid"] = $row->longcentroid;
						$retArr["pointradiusmeters"] = $row->pointradiusmeters;
						$retArr['footprintwkt'] = $row->footprintwkt;
						$retArr["access"] = $row->access;
						$retArr["defaultSettings"] = $row->defaultSettings;
						$retArr["dynamicsql"] = $row->dynamicsql;
						$retArr["datelastmodified"] = $row->datelastmodified;
					}
		    	}
		    	$result->free();
			}
			else{
				trigger_error('ERROR: unable to set checklist metadata => '.$sql, E_USER_ERROR);
			}
		}
		return $retArr;
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
				$vSql = 'SELECT DISTINCT v.tid, v.occid, c.institutioncode, v.notes, '.
					'o.catalognumber, o.recordedby, o.recordnumber, o.eventdate '.
					'FROM fmvouchers v INNER JOIN omoccurrences o ON v.occid = o.occid '.
					'INNER JOIN omcollections c ON o.collid = c.collid '.
					'WHERE (v.clid IN ('.$clidStr.')) AND v.tid IN('.implode(',',array_keys($this->taxaList)).')';
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
				$vResult->close();
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

	public function getCoordinates($tid = 0,$abbreviated=false){
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
					$sql2 = '';
					if($tid){
						$sql2 = 'SELECT DISTINCT v.tid, o.occid, o.decimallatitude, o.decimallongitude, '.
							'CONCAT(o.recordedby," (",IFNULL(o.recordnumber,o.eventdate),")") as notes '.
							'FROM omoccurrences o INNER JOIN fmvouchers v ON o.occid = v.occid '.
							'WHERE v.tid = '.$tid.' AND v.clid IN ('.$clidStr.') AND o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL '.
							'AND (o.localitysecurity = 0 OR o.localitysecurity IS NULL) ';
					}
					else{
						$sql2 = 'SELECT DISTINCT v.tid, o.occid, o.decimallatitude, o.decimallongitude, '.
							'CONCAT(o.recordedby," (",IFNULL(o.recordnumber,o.eventdate),")") as notes '.
							'FROM omoccurrences o INNER JOIN fmvouchers v ON o.occid = v.occid '.
							'INNER JOIN ('.$this->basicSql.') t ON v.tid = t.tid '.
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
				$outArr = array($tArr['family'],$tArr['sciname'],$tArr['author']);
				if($this->showCommon) $outArr[] = (array_key_exists('vern',$tArr)?$tArr['vern']:'');
				$outArr[] = (array_key_exists('notes',$tArr)?strip_tags($tArr['notes']):'');
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
				$this->basicSql = 'SELECT t.tid, IFNULL(ctl.familyoverride,ts.family) AS family, '.
					't.sciname, t.author, ctl.habitat, ctl.abundance, ctl.notes, ctl.source '.
					'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted '.
					'INNER JOIN fmchklsttaxalink ctl ON ts.tid = ctl.tid '.
			  		'WHERE (ts.taxauthid = '.$this->thesFilter.') AND (ctl.clid IN ('.$clidStr.')) ';
			}
			else{
				$this->basicSql = 'SELECT t.tid, IFNULL(ctl.familyoverride,ts.family) AS family, '.
					't.sciname, t.author, ctl.habitat, ctl.abundance, ctl.notes, ctl.source '.
					'FROM taxa t INNER JOIN fmchklsttaxalink ctl ON t.tid = ctl.tid '.
					'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			  		'WHERE (ts.taxauthid = 1) AND (ctl.clid IN ('.$clidStr.')) ';
			}
		}
		else{
			$this->basicSql = 'SELECT t.tid, ts.family, t.sciname, t.author '.
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
					$sqlWhere .= "OR (ts.tidaccepted IN(SELECT ts2.tidaccepted FROM taxa t2 INNER JOIN taxstatus ts2 ON t2.tid = ts2.tid ".
						"WHERE (t2.sciname Like '".$this->taxonFilter."%'))) ";
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

	public function echoResearchPoints($target){
		$clCluster = '';
		if(isset($GLOBALS['USER_RIGHTS']['ClAdmin'])) {
			$clCluster = $GLOBALS['USER_RIGHTS']['ClAdmin'];
		}
		$sql = 'SELECT c.clid, c.name, c.longcentroid, c.latcentroid '.
			'FROM fmchecklists c INNER JOIN fmchklstprojlink cpl ON c.CLID = cpl.clid '.
			'INNER JOIN fmprojects p ON cpl.pid = p.pid '.
			'WHERE (c.access = "public"'.($clCluster?' OR c.clid IN('.implode(',',$clCluster).')':'').') AND (c.LongCentroid IS NOT NULL) AND (p.pid = '.$this->pid.')';
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$idStr = $row->clid;
			$nameStr = $this->cleanOutStr($row->name);
			echo "var point".$idStr." = new google.maps.LatLng(".$row->latcentroid.", ".$row->longcentroid.");\n";
			echo "points.push( point".$idStr." );\n";
			echo 'var marker'.$idStr.' = new google.maps.Marker({ position: point'.$idStr.', map: map, title: "'.$nameStr.'" });'."\n";
			//Single click event
			echo 'var infoWin'.$idStr.' = new google.maps.InfoWindow({ content: "<div style=\'width:300px;\'><b>'.$nameStr.'</b><br/>Double Click to open</div>" });'."\n";
			echo "infoWins.push( infoWin".$idStr." );\n";
			echo "google.maps.event.addListener(marker".$idStr.", 'click', function(){ closeAllInfoWins(); infoWin".$idStr.".open(map,marker".$idStr."); });\n";
			//Double click event
			if($target == 'keys'){
				echo "var lStr".$idStr." = '../ident/key.php?cl=".$idStr."&proj=".$this->pid."&taxon=All+Species';\n";
			}
			else{
				echo "var lStr".$idStr." = 'checklist.php?cl=".$idStr."&proj=".$this->pid."';\n";
			}
			echo "google.maps.event.addListener(marker".$idStr.", 'dblclick', function(){ closeAllInfoWins(); marker".$idStr.".setAnimation(google.maps.Animation.BOUNCE); window.location.href = lStr".$idStr."; });\n";
		}
		$result->free();
	}

	//Setters and getters
    public function setThesFilter($filt){
		$this->thesFilter = $filt;
	}

	public function getThesFilter(){
		return $this->thesFilter;
	}

	public function setTaxonFilter($tFilter){
		$this->taxonFilter = $this->cleanInStr(strtolower($tFilter));
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

	public function setProj($pValue){
		$sql = 'SELECT pid, projname FROM fmprojects ';
		if(is_numeric($pValue)){
			$sql .= 'WHERE (pid = '.$pValue.')';
		}
		else{
			$sql .= 'WHERE (projname = "'.$this->cleanInStr($pValue).'")';
		}
		$rs = $this->conn->query($sql);
		if($rs){
			if($r = $rs->fetch_object()){
				$this->pid = $r->pid;
				$this->projName = $this->cleanOutStr($r->projname);
			}
			$rs->free();
		}
		else{
			trigger_error('ERROR: Unable to project => SQL: '.$sql, E_USER_WARNING);
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