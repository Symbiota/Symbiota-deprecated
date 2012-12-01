<?php
include_once($serverRoot.'/config/dbconnection.php');
 
class ChecklistManager {

	private $clCon;
	private $clid;
	private $dynClid;
	private $clName;
	private $pid = '';
	private $projName = '';
	private $taxaList = Array();
	private $clMetaData = Array();
	private $language = "English";
	private $thesFilter = 0;
	private $taxonFilter;
	private $showAuthors;
	private $showCommon;
	private $showImages;
	private $showVouchers;
	private $searchCommon;
	private $searchSynonyms;
	private $filterArr = Array();
	private $imageLimit = 100;
	private $taxaLimit = 500;
	private $speciesCount = 0;
	private $taxaCount = 0;
	private $familyCount = 0;
	private $genusCount = 0;
	private $sqlFrag;
	private $editable = false;
	private $basicSql;

	function __construct() {
		$this->clCon = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->clCon === false)) $this->clCon->close();
	}

	public function echoFilterList(){
		echo "'".implode("','",$this->filterArr)."'";
	}

	public function echoSpeciesAddList(){
		$sql = "SELECT DISTINCT t.tid, t.sciname FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
			"WHERE ts.taxauthid = 1 ";
		if($this->taxonFilter){
			$sql .= "AND t.rankid > 140 AND ((ts.family = '".$this->taxonFilter."') OR (t.sciname LIKE '".$this->taxonFilter."%')) ";
		}
		else{
			$sql .= "AND (t.rankid = 140 OR t.rankid = 180) ";
		}
		$sql .= "ORDER BY t.sciname";
		//echo $sql;
		$result = $this->clCon->query($sql);
		if($result){
	        while($row = $result->fetch_object()){
	        	if($this->taxonFilter){
	        		echo "<option value='".$row->tid."'>".$this->cleanOutStr($row->sciname)."</option>\n";
	        	}
	        	else{
	        		echo "<option>".$this->cleanOutStr($row->sciname)."</option>\n";
	        	}
	       	}
	       	$result->free();
		}
	}

	public function addNewSpecies($dataArr){
		$insertStatus = false;
		$colSql = '';
		$valueSql = '';
		foreach($dataArr as $k =>$v){
			$colSql .= ','.$k;
			if($v){
				$valueSql .= ',"'.$this->cleanInStr($v).'"';
			}
			else{
				$valueSql .= ',NULL';
			}
		}
		$sql = 'INSERT INTO fmchklsttaxalink (clid'.$colSql.') '.
			'VALUES ('.$this->clid.$valueSql.')';
		//echo $sql;
		$con = MySQLiConnectionFactory::getCon("write");
		if($con->query($sql)){
			$insertStatus = true;
		}
		$con->close();
		return $insertStatus;
	}
	
	public function setClValue($clValue){
		$clValue = $this->clCon->real_escape_string($clValue);
		if(is_numeric($clValue)){
			$this->clid = $clValue;
		}
		else{
			$sql = 'SELECT c.clid FROM fmchecklists c WHERE (c.Name = "'.$clValue.'")';
			$rs = $this->clCon->query($sql);
			if($row = $rs->fetch_object()){
				$this->clid = $row->clid;
			}
		}
	}

	public function setDynClid($did){
		if(is_numeric($did)){
			$this->dynClid = $did;
		}
	}
	
	public function getClMetaData($fieldName = ''){
		if(!$this->clMetaData){
			$this->setClMetaData();
		}
		if($fieldName){
			return $this->clMetaData[$fieldName];
		}
		return $this->clMetaData;
	}
	
	private function setClMetaData(){
		$sql = "";
		if($this->clid){
			$sql = "SELECT c.clid, c.name, c.locality, c.publication, ".
				"c.abstract, c.authors, c.parentclid, c.notes, ".
				"c.latcentroid, c.longcentroid, c.pointradiusmeters, c.access, ".
				"c.dynamicsql, c.datelastmodified, c.uid, c.type, c.initialtimestamp ".
				"FROM fmchecklists c WHERE (c.clid = ".$this->clid.')';
		}
		elseif($this->dynClid){
			$sql = "SELECT c.dynclid AS clid, c.name, c.details AS locality, c.notes, c.uid, c.type, c.initialtimestamp ".
				"FROM fmdynamicchecklists c WHERE (c.dynclid = ".$this->dynClid.')';
		}
		if($sql){
	 		$result = $this->clCon->query($sql);
			if($row = $result->fetch_object()){
				$this->clName = $row->name;
				$this->clMetaData["locality"] = $this->cleanOutStr($row->locality); 
				$this->clMetaData["notes"] = $this->cleanOutStr($row->notes);
				$this->clMetaData["type"] = $row->type;
				if($this->clid){
					$this->clMetaData["publication"] = $this->cleanOutStr($row->publication);
					$this->clMetaData["abstract"] = $this->cleanOutStr($row->abstract);
					$this->clMetaData["authors"] = $this->cleanOutStr($row->authors);
					$this->clMetaData["parentclid"] = $row->parentclid;
					$this->clMetaData["uid"] = $row->uid;
					$this->clMetaData["latcentroid"] = $row->latcentroid;
					$this->clMetaData["longcentroid"] = $row->longcentroid;
					$this->clMetaData["pointradiusmeters"] = $row->pointradiusmeters;
					$this->clMetaData["access"] = $row->access;
					$this->clMetaData["dynamicsql"] = $row->dynamicsql;
					$this->clMetaData["datelastmodified"] = $row->datelastmodified;
				}
	    	}
	    	$result->free();
		}
	}
	
	public function editMetaData($editArr){
		$setSql = "";
		foreach($editArr as $key =>$value){
			if($value){
				$setSql .= ', '.$key.' = "'.$this->cleanInStr($value).'"';
			}
			else{
				$setSql .= ', '.$key.' = NULL';
			}
		}
		$sql = 'UPDATE fmchecklists SET '.substr($setSql,2).' WHERE (clid = '.$this->clid.')';
		//echo $sql;
		$con = MySQLiConnectionFactory::getCon("write");
		$con->query($sql);
		$con->close();
	}

	public function getTaxonAuthorityList(){
    	$taxonAuthList = Array();
		$sql = "SELECT ta.taxauthid, ta.name FROM taxauthority ta WHERE (ta.isactive <> 0)";
 		$rs = $this->clCon->query($sql);
		while ($row = $rs->fetch_object()){
			$taxonAuthList[$row->taxauthid] = $this->cleanOutStr($row->name);
		}
		$rs->free();
		return $taxonAuthList;
	}

	//return an array: family => array(TID => sciName)
	public function getTaxaList($pageNumber = 1,$retLimit = 500){
		//Get list that shows which taxa have vouchers; note that dynclid list won't have vouchers
		$voucherArr = Array();
		if($this->showVouchers){
			$vSql = 'SELECT DISTINCT v.tid, v.occid, CONCAT_WS(" ",o.recordedby,CONCAT("(",IFNULL(o.recordnumber,o.catalognumber),")")) AS collector, v.notes '.
				'FROM fmvouchers v INNER JOIN omoccurrences o ON v.occid = o.occid '.
				'WHERE (v.clid = '.$this->clid.')';
	 		$vResult = $this->clCon->query($vSql);
			while ($row = $vResult->fetch_object()){
				$voucherArr[$row->tid][$row->occid] = $this->cleanOutStr($row->collector);
				//$this->voucherArr[$row->tid][] = "<a style='cursor:pointer' onclick=\"openPopup('../collections/individual/index.php?occid=".
				//	$row->occid."','individwindow')\">".$row->collector."</a>\n";
			}
			$vResult->close();
		}
		//Get species list
		$familyPrev="";$genusPrev="";$speciesPrev="";$taxonPrev="";
		$tidReturn = Array();
		if($this->showImages) $retLimit = $this->imageLimit;
		if(!$this->basicSql) $this->setClSql();
		$result = $this->clCon->query($this->basicSql);
		while($row = $result->fetch_object()){
			$this->filterArr[$this->cleanOutStr($row->uppertaxonomy)] = "";
			$family = strtoupper($this->cleanOutStr($row->family));
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
					if($row->habitat) $clStr = ", ".$this->cleanOutStr($row->habitat);
					if($row->abundance) $clStr .= ", ".$this->cleanOutStr($row->abundance);
					if($row->notes) $clStr .= ", ".$this->cleanOutStr($row->notes);
					if($row->source) $clStr .= ", <u>source</u>: ".$this->cleanOutStr($row->source);
					if($clStr) $this->taxaList[$tid]["notes"] = substr($clStr,2);
					if(array_key_exists($tid,$voucherArr)){
						$this->taxaList[$tid]["vouchers"] = $voucherArr[$tid];  
					}
				}
				$this->taxaList[$tid]["sciname"] = $sciName;
				$this->taxaList[$tid]["family"] = $family;
				$tidReturn[] = $tid;
				if($this->showAuthors){
					$this->taxaList[$tid]["author"] = $this->cleanOutStr($row->author);
				}
    		}
    		if($family != $familyPrev) $this->familyCount++;
    		$familyPrev = $family;
    		if($taxonTokens[0] != $genusPrev) $this->genusCount++;
			$this->filterArr[$taxonTokens[0]] = "";
    		$genusPrev = $taxonTokens[0];
    		if(count($taxonTokens) > 1 && $taxonTokens[0]." ".$taxonTokens[1] != $speciesPrev){
    			$this->speciesCount++;
    			$speciesPrev = $taxonTokens[0]." ".$taxonTokens[1];
    		}
    		if(!$taxonPrev || strpos($sciName,$taxonPrev) === false){
    			$this->taxaCount++;
    		}
    		$taxonPrev = implode(" ",$taxonTokens);
		}
		$this->filterArr = array_keys($this->filterArr);
		sort($this->filterArr);
		$result->free();
		if($this->taxaCount < (($pageNumber-1)*$retLimit)){
			$this->taxaCount = 0; $this->genusCount = 0; $this->familyCount = 0;
			unset($this->filterArr);
			return $this->getTaxaList(1,$retLimit);
		}
		if($this->showImages) $this->setImages($tidReturn);
		if($this->showCommon) $this->setVernaculars($tidReturn);
		return $this->taxaList;
	}

	private function setImages($tidReturn){
		if($tidReturn){
			$sql = 'SELECT i2.tid, i.url, i.thumbnailurl FROM images i INNER JOIN '.
				'(SELECT ts1.tid, SUBSTR(MIN(CONCAT(LPAD(i.sortsequence,6,"0"),i.imgid)),7) AS imgid '. 
				'FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN images i ON ts2.tid = i.tid '.
				'WHERE i.sortsequence < 500 AND ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND (ts1.tid IN('.implode(',',$tidReturn).')) '.
				'GROUP BY ts1.tid) i2 ON i.imgid = i2.imgid';
			//echo $sql;
			$rs = $this->clCon->query($sql);
			while($row = $rs->fetch_object()){
				$this->taxaList[$row->tid]["url"] = $row->url;
				$this->taxaList[$row->tid]["tnurl"] = $row->thumbnailurl;
			}
			$rs->free();
		}
	}

	private function setVernaculars($tidReturn){
		if($tidReturn){
			$sql = 'SELECT v2.tid, v.vernacularname FROM taxavernaculars v INNER JOIN '.
				'(SELECT ts1.tid, SUBSTR(MIN(CONCAT(LPAD(v.sortsequence,6,"0"),v.vid)),7) AS vid '. 
				'FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN taxavernaculars v ON ts2.tid = v.tid '.
				'WHERE ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND (ts1.tid IN('.implode(',',$tidReturn).')) '.
				'AND (v.language = "'.$this->language.'") '.
				'GROUP BY ts1.tid) v2 ON v.vid = v2.vid';
			//echo $sql;
			$rs = $this->clCon->query($sql);
			while($row = $rs->fetch_object()){
				$this->taxaList[$row->tid]["vern"] = $this->cleanOutStr($row->vernacularname);
			}
			$rs->free();
		}
	}

	public function getCoordinates($tid = 0,$abbreviated=false){
		$retArr = array();
		if(!$this->basicSql) $this->setClSql();
		if($this->clid){
			$maxLat = -90;
			$minLat = 90;
			$maxLng = -180;
			$minLng = 180;
			$retCnt = 0;
			//Grab general points
			try{
				$sql1 = '';
				if($tid){
					$sql1 = 'SELECT DISTINCT cc.tid, t.sciname, cc.decimallatitude, cc.decimallongitude, cc.notes '. 
						'FROM fmchklstcoordinates cc INNER JOIN taxa t ON cc.tid = t.tid '.
						'WHERE cc.tid = '.$tid.' AND cc.clid = '.$this->clid.' AND cc.decimallatitude IS NOT NULL AND cc.decimallongitude IS NOT NULL ';
				}
				else{
					$sql1 = 'SELECT DISTINCT cc.tid, t.sciname, cc.decimallatitude, cc.decimallongitude, cc.notes '. 
						'FROM fmchklstcoordinates cc INNER JOIN ('.$this->basicSql.') t ON cc.tid = t.tid '.
						'WHERE cc.clid = '.$this->clid.' AND cc.decimallatitude IS NOT NULL AND cc.decimallongitude IS NOT NULL ';
				}
				if($abbreviated){
					$sql1 .= 'LIMIT 50'; 
				}
				//echo $sql1;
				$rs1 = $this->clCon->query($sql1);
				if($rs1){
					while($r1 = $rs1->fetch_object()){
						if($abbreviated){
							$retArr[] = $r1->decimallatitude.','.$r1->decimallongitude;
						}
						else{
							$retArr[$r1->tid][] = array('ll'=>$r1->decimallatitude.','.$r1->decimallongitude,'sciname'=>$this->cleanOutStr($r1->sciname),'notes'=>$this->cleanOutStr($r1->notes));
							if($minLat > $r1->decimallatitude) $minLat = $r1->decimallatitude;
							if($maxLat < $r1->decimallatitude) $maxLat = $r1->decimallatitude;
							if($minLng > $r1->decimallongitude) $minLng = $r1->decimallongitude;
							if($maxLng < $r1->decimallongitude) $maxLng = $r1->decimallongitude;
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
							'CONCAT(o.recordedby," (",IFNULL(o.recordnumber,"s.n."),")") as notes '.
							'FROM omoccurrences o INNER JOIN fmvouchers v ON o.occid = v.occid '.
							'WHERE v.tid = '.$tid.' AND v.clid = '.$this->clid.' AND o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL '.
							'AND o.localitysecurity = 0 ';
					}
					else{
						$sql2 = 'SELECT DISTINCT v.tid, o.occid, o.decimallatitude, o.decimallongitude, '. 
							'CONCAT(o.recordedby," (",IFNULL(o.recordnumber,"s.n."),")") as notes '.
							'FROM omoccurrences o INNER JOIN fmvouchers v ON o.occid = v.occid '.
							'INNER JOIN ('.$this->basicSql.') t ON v.tid = t.tid '.
							'WHERE v.clid = '.$this->clid.' AND o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL '.
							'AND o.localitysecurity = 0 ';
					}
					if($abbreviated){
						$sql2 .= 'LIMIT 50'; 
					}
					//echo $sql2;
					$rs2 = $this->clCon->query($sql2);
					if($rs2){
						while($r2 = $rs2->fetch_object()){
							if($abbreviated){
								$retArr[] = $r2->decimallatitude.','.$r2->decimallongitude;
							}
							else{
								$retArr[$r2->tid][] = array('ll'=>$r2->decimallatitude.','.$r2->decimallongitude,'notes'=>$this->cleanOutStr($r2->notes),'occid'=>$r2->occid);
								if($minLat > $r2->decimallatitude) $minLat = $r2->decimallatitude;
								if($maxLat < $r2->decimallatitude) $maxLat = $r2->decimallatitude;
								if($minLng > $r2->decimallongitude) $minLng = $r2->decimallongitude;
								if($maxLng < $r2->decimallongitude) $maxLng = $r2->decimallongitude;
							}
						}
						$rs2->free();
					}
				}
				catch(Exception $e){
					//echo 'Caught exception getting voucher coordinates: ',  $e->getMessage(), "\n";
				}
			}
			if(!$abbreviated){
				$retArr['sw'] = $minLat.','.$minLng;
				$retArr['ne'] = $maxLat.','.$maxLng;
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
			echo "Family,ScientificName,ScientificNameAuthorship,";
			if($this->showCommon) echo "CommonName,";
			echo "TaxonId\n";
			foreach($taxaArr as $tid => $tArr){
				echo '"'.$tArr['family'].'","'.$tArr['sciname'].'","'.$tArr['author'].'"';
				if($this->showCommon){
					if(array_key_exists('vern',$tArr)){
						echo ',"'.$tArr['vern'].'"';
					}
					else{
						echo ',""';
					}
				}
				echo ',"'.$tid.'"'."\n";
			}
		}
		else{
			echo "Recordset is empty.\n";
		}
    }

	private function setClSql(){
		if($this->clid){
			if($this->thesFilter){
				$this->basicSql = 'SELECT DISTINCT t.tid, ts.uppertaxonomy, ts.family, '. 
					't.sciname, t.author, ctl.habitat, ctl.abundance, ctl.notes, ctl.source '.
					'FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted) '.
					'INNER JOIN fmchklsttaxalink ctl ON ctl.tid = ts.tid '.
	    	  		'WHERE (ctl.clid = '.$this->clid.') AND (ts.taxauthid = '.$this->thesFilter.')';
			} 
			else{
				$this->basicSql = 'SELECT DISTINCT t.tid, ts.uppertaxonomy, IFNULL(ctl.familyoverride,ts.family) AS family, '.
					't.sciname, t.author, ctl.habitat, ctl.abundance, ctl.notes, ctl.source '.
					'FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid) '.
					'INNER JOIN fmchklsttaxalink ctl ON ctl.tid = t.tid '.
	    	  		'WHERE (ts.taxauthid = 1) AND (ctl.clid = '.$this->clid.')';
			}
		}
		else{
			if($this->thesFilter > 1){
				$this->basicSql = 'SELECT t.tid, ts.uppertaxonomy, ts.family, t.sciname, t.author '.
					'FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted) '.
					'INNER JOIN fmdyncltaxalink ctl ON ctl.tid = ts.tid '.
	    	  		'WHERE (ctl.dynclid = '.$this->dynClid.') AND (ts.taxauthid = '.$this->thesFilter.')';
			}
			else{
				$this->basicSql = 'SELECT t.tid, ts.uppertaxonomy, ts.family, t.sciname, t.author '.
					'FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid) '.
					'INNER JOIN fmdyncltaxalink ctl ON ctl.tid = t.tid '.
	    	  		'WHERE (ts.taxauthid = 1) AND (ctl.dynclid = '.$this->dynClid.')';
			}
		}
		if($this->taxonFilter){
			if($this->searchCommon){
				$this->basicSql .= ' AND (t.tid IN(SELECT v.tid FROM taxavernaculars v WHERE (v.vernacularname LIKE "%'.$this->taxonFilter.'%"))) ';
			}
			else{
				$this->basicSql .= " AND ((ts.uppertaxonomy = '".$this->taxonFilter."') ";
				if($this->clid && $this->thesFilter){
					$this->basicSql .= "OR (IFNULL(ctl.familyoverride,ts.family) = '".$this->taxonFilter."') ";
				}
				else{
					$this->basicSql .= "OR (family = '".$this->taxonFilter."') ";
				}
				if($this->searchSynonyms){
					$this->basicSql .= "OR (t.tid IN(SELECT tsb.tid FROM (taxa ta INNER JOIN taxstatus tsa ON ta.tid = tsa.tid) ".
						"INNER JOIN taxstatus tsb ON tsa.tidaccepted = tsb.tidaccepted ".
						"WHERE (tsa.uppertaxonomy = '".$this->taxonFilter."') OR (ta.sciname Like '".$this->taxonFilter."%')))) ";
				}
				else{
					$this->basicSql .= "OR (t.SciName Like '".$this->taxonFilter."%')) ";
				}
			}
		}
		$this->basicSql .= " ORDER BY family, sciname";
		//echo $this->basicSql;
	}

	//Voucher Maintenance functions
	public function getDynamicSql(){
		$this->setDynamicSql();
		return $this->sqlFrag;
	}
	
	private function setDynamicSql(){
		if(!$this->sqlFrag){
			$sql = 'SELECT c.dynamicsql FROM fmchecklists c WHERE (c.clid = '.$this->clid.')';
			//echo $sql;
			$rs = $this->clCon->query($sql);
			while($row = $rs->fetch_object()){
				$this->sqlFrag = $row->dynamicsql;
			}
			$rs->close();
		}
	}
	
	public function saveSql($sqlFragArr){
		$statusStr = false;
		$conn = MySQLiConnectionFactory::getCon("write");
		$sqlFrag = "";
		if($sqlFragArr['country']){
			$sqlFrag = 'AND (o.country = "'.$this->cleanInStr($sqlFragArr['country']).'") ';
		}
		if($sqlFragArr['state']){
			$sqlFrag .= 'AND (o.stateprovince = "'.$this->cleanInStr($sqlFragArr['state']).'") ';
		}
		if($sqlFragArr['county']){
			$sqlFrag .= 'AND (o.county LIKE "%'.$this->cleanInStr($sqlFragArr['county']).'%") ';
		}
		if($sqlFragArr['locality']){
			$sqlFrag .= 'AND (o.locality LIKE "%'.$this->cleanInStr($sqlFragArr['locality']).'%") ';
		}
		$llStr = '';
		if($sqlFragArr['latnorth'] && $sqlFragArr['latsouth']){
			$llStr .= 'AND (o.decimallatitude BETWEEN '.$conn->real_escape_string($sqlFragArr['latsouth']).
			' AND '.$conn->real_escape_string($sqlFragArr['latnorth']).') ';
		}
		if($sqlFragArr['lngwest'] && $sqlFragArr['lngeast']){
			$llStr .= 'AND (o.decimallongitude BETWEEN '.$conn->real_escape_string($sqlFragArr['lngwest']).
			' AND '.$conn->real_escape_string($sqlFragArr['lngeast']).') ';
		}
		if(array_key_exists('latlngor',$sqlFragArr)) $llStr = 'OR ('.trim(substr($llStr,3)).')';
		$sqlFrag .= $llStr;
		if($sqlFrag){
			$sql = "UPDATE fmchecklists c SET c.dynamicsql = '".trim(substr($sqlFrag,3))."' WHERE (c.clid = ".$this->clid.')';
			//echo $sql;
			if($conn->query($sql)) $statusStr = true;
		}
		$conn->close();
		return $statusStr;
	}

	public function deleteSql(){
		$statusStr = '';
		$conn = MySQLiConnectionFactory::getCon("write");
		if(!$conn->query('UPDATE fmchecklists c SET c.dynamicsql = NULL WHERE (c.clid = '.$this->clid.')')){
			$statusStr = 'ERROR: '.$conn->query->error;
		}
		$conn->close();
		return $statusStr;
	}

	public function getVoucherCnt(){
		$vCnt = 0;
		$sql = 'SELECT count(*) AS vcnt FROM fmvouchers WHERE (clid = '.$this->clid.')';
		$rs = $this->clCon->query($sql);
		while($r = $rs->fetch_object()){
			$vCnt = $r->vcnt;
		}
		$rs->close();
		return $vCnt;
	}

	public function getNonVoucheredCnt(){
		$uvCnt = 0;
		$sql = 'SELECT count(t.tid) AS uvcnt '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'INNER JOIN fmchklsttaxalink ctl ON t.tid = ctl.tid '.
			'LEFT JOIN fmvouchers v ON ctl.clid = v.clid AND ctl.tid = v.tid '.
			'WHERE v.clid IS NULL AND (ctl.clid = '.$this->clid.') AND ts.taxauthid = 1 ';
		$rs = $this->clCon->query($sql);
		while($row = $rs->fetch_object()){
			$uvCnt = $row->uvcnt;
		}
		$rs->close();
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
		$rs = $this->clCon->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$this->cleanOutStr($row->family)][$row->tid] = $this->cleanOutStr($row->sciname);
		}
		$rs->close();
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
		$rs = $this->clCon->query($sql);
		while($row = $rs->fetch_object()){
			$retArr[$row->tid]['listid'] = $row->listid;
			$collStr = $this->cleanOutStr($row->recordedby);
			if($row->recordnumber) $collStr .= ' ('.$this->cleanOutStr($row->recordnumber).')';
			$retArr[$row->tid]['recordnumber'] = $collStr;
			$retArr[$row->tid]['specid'] = $this->cleanOutStr($row->sciname);
			$idBy = $this->cleanOutStr($row->identifiedby);
			if($row->dateidentified) $idBy .= ' ('.$this->cleanOutStr($row->dateidentified).')';
			$retArr[$row->tid]['identifiedby'] = $idBy;
		}
		$rs->close();
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
			$rs = $this->clCon->query($sql);
			while($row = $rs->fetch_object()){
				$retArr[$row->tidinterpreted] = $this->cleanOutStr($row->sciname);
			}
			$rs->close();
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
		if($rs = $this->clCon->query($sql)){
			echo '"family","scientificName","institutionCode","catalogNumber","identifiedBy","dateIdentified",'.
 			'"recordedBy","recordNumber","eventDate","country","stateProvince","county","municipality","locality",'.
 			'"decimalLatitude","decimalLongitude","minimumElevationInMeters","habitat","occurrenceRemarks","occid"'."\n";
			
			while($row = $rs->fetch_assoc()){
				echo '"'.$this->cleanOutStr($row["family"]).'","'.$this->cleanOutStr($row["sciname"]).'","'.$row["institutioncode"].'","'.
					$row["catalognumber"].'","'.$this->cleanOutStr($row["identifiedby"]).'","'.
					$this->cleanOutStr($row["dateidentified"]).'","'.$this->cleanOutStr($row["recordedby"]).'","'.
					$this->cleanOutStr($row["recordnumber"]).'","'.$row["eventdate"].'","'.$this->cleanOutStr($row["country"]).'","'.
					$this->cleanOutStr($row["stateprovince"]).'","'.$this->cleanOutStr($row["county"]).'","'.$this->cleanOutStr($row["municipality"]).'",';
				
				$localSecurity = ($row["localitysecurity"]?$row["localitysecurity"]:0); 
				if($canReadRareSpp || $localSecurity != 1 || (array_key_exists("RareSppReader", $userRights) && in_array($row["collid"],$userRights["RareSppReader"]))){
					echo '"'.$this->cleanOutStr($row["locality"]).'",'.$row["decimallatitude"].','.$row["decimallongitude"].','.
					$row["minimumelevationinmeters"].',"'.$this->cleanOutStr($row["habitat"]).'","'.$this->cleanOutStr($row["occurrenceremarks"]).'",';
				}
				else{
					echo '"Value Hidden","Value Hidden","Value Hidden","Value Hidden","Value Hidden","Value Hidden",';
				}
				echo '"'.$row["occid"]."\"\n";
			}
        	$rs->close();
		}
		else{
			echo "Recordset is empty.\n";
		}
	} 

	public function hasChildrenChecklists(){
		$hasChildren = false;
		$sql = 'SELECT count(*) AS clcnt FROM fmchecklists WHERE (parentclid = '.$this->clid.')';
		$rs = $this->clCon->query($sql);
		while($row = $rs->fetch_object()){
			if($row->clcnt > 0) $hasChildren = true;
		}
		$rs->close();
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
		$rs = $this->clCon->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->tid]['sciname'] = $this->cleanOutStr($r->sciname);
			$retArr[$r->tid]['cl'] = $this->cleanOutStr($r->name);
		}
		$rs->close();
		return $retArr;
	}
	
	public function getEditors(){
		$editorArr = array();
		$sql = 'SELECT u.uid, CONCAT_WS(", ",u.lastname,u.firstname) as uname '.
			'FROM userpermissions up INNER JOIN users u ON up.uid = u.uid '.
			'WHERE up.pname = "ClAdmin-'.$this->clid.'" ORDER BY u.lastname,u.firstname';
		if($rs = $this->clCon->query($sql)){
			while($r = $rs->fetch_object()){
				$uName = $this->cleanOutStr($r->uname);
				if(strlen($uName) > 60) $uName = substr($uName,0,60);
				$editorArr[$r->uid] = $uName;
			}
			$rs->close();
		}
		return $editorArr;
	}

	public function addEditor($u){
		$sql = 'INSERT INTO userpermissions(uid,pname) '.
			'VALUES('.$u.',"ClAdmin-'.$this->clid.'")';
		$conn = MySQLiConnectionFactory::getCon("write");
		$conn->query($sql);
		$conn->close();
	}

	public function deleteEditor($u){
		$sql = 'DELETE FROM userpermissions '.
			'WHERE uid = '.$u.' AND pname = "ClAdmin-'.$this->clid.'"';
		$conn = MySQLiConnectionFactory::getCon("write");
		$conn->query($sql);
		$conn->close();
	}

	public function getUserList(){
		$returnArr = Array();
		$sql = 'SELECT u.uid, CONCAT_WS(", ",u.lastname,u.firstname) AS uname '.
			'FROM users u '.
			'ORDER BY u.lastname,u.firstname';
		//echo $sql;
		$rs = $this->clCon->query($sql);
		while($r = $rs->fetch_object()){
			$returnArr[$r->uid] = $this->cleanOutStr($r->uname);
		}
		$rs->close();
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
			if($rs = $this->clCon->query($sql)){
				while($r = $rs->fetch_object()){
					$retArr[$r->collid] = $this->cleanOutStr($r->collectionname);
				}
				$rs->close();
			}
		}
		return $retArr;
	}
	
	//Misc set/get functions
    public function setThesFilter($filt){
		$this->thesFilter = $filt;
	}

	public function getThesFilter(){
		return $this->thesFilter;
	}

	public function setTaxonFilter($tFilter){
		$this->taxonFilter = $tFilter;
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

	public function setSearchCommon($value = 1){
		$this->searchCommon = $value;
	}

	public function setSearchSynonyms($value = 1){
		$this->searchSynonyms = $value;
	}

	public function getClid(){
		return $this->clid;
	}

	public function getClName(){
		return $this->clName;
	}
	
	public function setProj($pValue){
		$sql = 'SELECT pid, projname FROM fmprojects '.
			'WHERE (pid = "'.$pValue.'") OR (projname = "'.$pValue.'")';
		$rs = $this->clCon->query($sql);
		if($rs){
			if($r = $rs->fetch_object()){
				$this->pid = $r->pid;
				$this->projName = $this->cleanOutStr($r->projname);
			}
			$rs->close();
		}
	}

	public function getProjName(){
		return $this->projName;
	}
	
	public function getPid(){
		return $this->pid;
	}
	
	public function setLanguage($l){
		$this->language = $l;
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
	
	public function setEditable($e){
		$this->editable = $e;
	}
	
	public function getEditable(){
		return $this->editable;
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

	public function echoParentSelect(){
		$sql = 'SELECT c.clid, c.name FROM fmchecklists c WHERE type = "static" AND access <> "private" ORDER BY c.name';
		$rs = $this->clCon->query($sql);
		while($row = $rs->fetch_object()){
			echo "<option value='".$row->clid."' ".($this->clMetaData["parentclid"]==$row->clid?" selected":"").">".$this->cleanOutStr($row->name)."</option>";
		}
		$rs->close();
	}

	private function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
	
	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?> 