<?php
include_once($serverRoot.'/config/dbconnection.php');

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
						$retArr["access"] = $row->access;
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
		$familyPrev="";$genusPrev="";$speciesPrev="";$taxonPrev="";
		$tidReturn = Array();
		if($this->showImages && $retLimit) $retLimit = $this->imageLimit;
		if(!$this->basicSql) $this->setClSql();
		$result = $this->conn->query($this->basicSql);
		//echo $this->basicSql; 
		while($row = $result->fetch_object()){
			$this->filterArr[$row->uppertaxonomy] = "";
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
		//Get voucher data; note that dynclid list won't have vouchers
		if($this->taxaList){
			if($this->showVouchers){
				$clidStr = $this->clid;
				if($this->childClidArr){
					$clidStr .= ','.implode(',',$this->childClidArr);
				}
				$vSql = 'SELECT DISTINCT v.tid, v.occid, c.institutioncode, v.notes, '.
					'CONCAT_WS(" ",o.recordedby,IFNULL(o.recordnumber,"s.n.")) AS collector '.
					'FROM fmvouchers v INNER JOIN omoccurrences o ON v.occid = o.occid '.
					'INNER JOIN omcollections c ON o.collid = c.collid '.
					'WHERE (v.clid IN ('.$clidStr.')) AND v.tid IN('.implode(',',array_keys($this->taxaList)).')';
				//echo $vSql; exit;
		 		$vResult = $this->conn->query($vSql);
				while ($row = $vResult->fetch_object()){
					$this->voucherArr[$row->tid][$row->occid] = $row->collector.' ['.$row->institutioncode.']';
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
			$sql = 'SELECT v2.tid, v.vernacularname FROM taxavernaculars v INNER JOIN '.
				'(SELECT ts1.tid, SUBSTR(MIN(CONCAT(LPAD(v.sortsequence,6,"0"),v.vid)),7) AS vid '. 
				'FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted '.
				'INNER JOIN taxavernaculars v ON ts2.tid = v.tid '.
				'WHERE ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND (ts1.tid IN('.implode(',',$tidReturn).')) '.
				'AND (v.language = "'.$this->language.'") '.
				'GROUP BY ts1.tid) v2 ON v.vid = v2.vid';
			//echo $sql;
			$rs = $this->conn->query($sql);
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
			//Add children checklists to query
			$clidStr = $this->clid;
			if($this->childClidArr){
				$clidStr .= ','.implode(',',$this->childClidArr);
			}
			
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
						'WHERE cc.tid = '.$tid.' AND cc.clid IN ('.$clidStr.') AND cc.decimallatitude IS NOT NULL AND cc.decimallongitude IS NOT NULL ';
				}
				else{
					$sql1 = 'SELECT DISTINCT cc.tid, t.sciname, cc.decimallatitude, cc.decimallongitude, cc.notes '. 
						'FROM fmchklstcoordinates cc INNER JOIN ('.$this->basicSql.') t ON cc.tid = t.tid '.
						'WHERE cc.clid IN ('.$clidStr.') AND cc.decimallatitude IS NOT NULL AND cc.decimallongitude IS NOT NULL ';
				}
				if($abbreviated){
					$sql1 .= 'LIMIT 50'; 
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
							'WHERE v.tid = '.$tid.' AND v.clid IN ('.$clidStr.') AND o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL '.
							'AND o.localitysecurity = 0 ';
					}
					else{
						$sql2 = 'SELECT DISTINCT v.tid, o.occid, o.decimallatitude, o.decimallongitude, '. 
							'CONCAT(o.recordedby," (",IFNULL(o.recordnumber,"s.n."),")") as notes '.
							'FROM omoccurrences o INNER JOIN fmvouchers v ON o.occid = v.occid '.
							'INNER JOIN ('.$this->basicSql.') t ON v.tid = t.tid '.
							'WHERE v.clid IN ('.$clidStr.') AND o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL '.
							'AND o.localitysecurity = 0 ';
					}
					if($abbreviated){
						$sql2 .= 'LIMIT 50'; 
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
			$clidStr = $this->clid;
			if($this->childClidArr){
				$clidStr .= ','.implode(',',$this->childClidArr);
			}
			if($this->thesFilter){
				//Filter checklist through thesaurus
				$this->basicSql = 'SELECT DISTINCT t.tid, ctl.clid, ts.uppertaxonomy, ts.family, '. 
					't.sciname, t.author, ctl.habitat, ctl.abundance, ctl.notes, ctl.source '.
					'FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted) '.
					'INNER JOIN fmchklsttaxalink ctl ON ctl.tid = ts.tid '.
					'LEFT JOIN fmchklstchildren ch ON ctl.clid = ch.clid '.
			  		'WHERE (ctl.clid IN ('.$clidStr.')) AND (ts.taxauthid = '.$this->thesFilter.')';
			}
			else{
				//Raw checklist without filtering through checklist
				$this->basicSql = 'SELECT DISTINCT t.tid, ts.uppertaxonomy, IFNULL(ctl.familyoverride,ts.family) AS family, '.
					't.sciname, t.author, ctl.habitat, ctl.abundance, ctl.notes, ctl.source '.
					'FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid) '.
					'INNER JOIN fmchklsttaxalink ctl ON ctl.tid = t.tid '.
				'WHERE (ts.taxauthid = 1) AND (ctl.clid IN ('.$clidStr.'))';
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
			$sql .= 'WHERE (projname = "'.$this->conn->real_escape_string($pValue).'")';
		}
		$rs = $this->conn->query($sql);
		if($rs){
			if($r = $rs->fetch_object()){
				$this->pid = $r->pid;
				$this->projName = $this->cleanOutStr($r->projname);
			}
			$rs->close();
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
}
?>