<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/Manager.php');
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
include_once($serverRoot.'/classes/AgentManager.php');
include_once($serverRoot.'/classes/TaxonomyUtilities.php');

class OccurrenceCleaner extends Manager{

	private $collid;
	private $obsUid;

	public function __construct(){
		parent::__construct(null,'write');
	}

	public function __destruct(){
		parent::__destruct();
	}

	//Taxon name cleaning functions
	public function getBadTaxaCount(){
		$retCnt = 0;
		if($this->collid){
			$sql = 'SELECT COUNT(DISTINCT sciname) AS taxacnt '.
				'FROM omoccurrences '.
				'WHERE (collid = '.$this->collid.') AND (tidinterpreted IS NULL) AND (sciname IS NOT NULL) AND (sciname != "") ';
			//echo $sql;
			if($rs = $this->conn->query($sql)){
				if($row = $rs->fetch_object()){
					$retCnt = $row->taxacnt;
				}
				$rs->free();
			}
		}
		return $retCnt;
	}

	public function analyzeTaxa($startIndex = 0, $limit = 50){
		$status = true;
		$this->logOrEcho("Starting taxa check ");
		$sql = 'SELECT DISTINCT sciname, family '.
			'FROM omoccurrences '.
			'WHERE (collid = '.$this->collid.') AND (tidinterpreted IS NULL) AND (sciname IS NOT NULL) AND (sciname != "") '.
			'ORDER BY sciname '.
			'LIMIT '.$startIndex.','.$limit;
		if($rs = $this->conn->query($sql)){
			//Check name through taxonomic resources
			$taxUtil = new  TaxonomyUtilities();
			$taxUtil->setVerboseMode(2);
			$this->setVerboseMode(2);
			$nameCnt = 0;
			while($r = $rs->fetch_object()){
				$this->logOrEcho('Resolving '.$r->sciname.($r->family?' ('.$r->family.')':'').'...');
				$newTid = $taxUtil->addSciname($r->sciname, $r->family);
				if(!$newTid){
					//Check for near match using SoundEx
					$this->logOrEcho('Checking close matches in thesaurus...',1);
					$closeArr = $taxUtil->getSoundexMatch($r->sciname);
					if(!$closeArr) $closeArr = $taxUtil->getCloseMatchEpithet($r->sciname);
					if($closeArr){
						$cnt = 0;
						foreach($closeArr as $tid => $sciname){
							$echoStr = '<i>'.$sciname.'</i> =&gt;<a href="#" onclick="remappTaxon(\''.$r->sciname.'\','.$tid.',\''.$sciname.'\')"> remap to this taxon</a>';
							$this->logOrEcho($echoStr,2);
							$cnt++;
						}
					}
					else{
						$this->logOrEcho('No close matches found',1);
					}
				}
				$nameCnt++;
			}
			$rs->free();
		}
		$this->linkNewTaxa();

		$this->logOrEcho("Done with taxa check ");
		return $status;
	}
	
	private function linkNewTaxa(){
		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname '.
			'SET o.tidinterpreted = t.tid '.
			'WHERE o.tidinterpreted IS NULL';
		if(!$this->conn->query($sql)){
			$this->logOrEcho('ERROR linking new data to occurrences: '.$this->conn->error);
		}
	}

	public function remapOccurrenceTaxon($collid, $oldSciname, $tid, $newSciname){
		$status = false;
		if(is_numeric($collid) && $oldSciname && is_numeric($tid) && $newSciname){
			$oldSciname = $this->cleanInStr($oldSciname);
			$newSciname = $this->cleanInStr($newSciname);
			//Version edit in edits table 
			$sql1 = 'INSERT INTO omoccuredits(occid, FieldName, FieldValueNew, FieldValueOld, uid, ReviewStatus, AppliedStatus) '.
				'SELECT occid, "sciname", "'.$newSciname.'", sciname, '.$GLOBALS['SYMB_UID'].', 1, 1 '.
				'FROM omoccurrences WHERE collid = '.$collid.' AND sciname = "'.$oldSciname.'"'; 
			if($this->conn->query($sql1)){
				//Update occurrence table
				$sql2 = 'UPDATE omoccurrences '.
					'SET tidinterpreted = '.$tid.', sciname = "'.$newSciname.'" '.
					'WHERE collid = '.$collid.' AND sciname = "'.$oldSciname.'"';
				if($this->conn->query($sql2)){
					$status = true;
				}
				else{
					echo $sql2;
				}
			}
			else{
				echo $sql1;
			}
		}
		return $status;
	}

	//Search and resolve duplicate specimen records 
	public function getDuplicateCatalogNumber($start, $limit = 500){
		//Search is not available for personal specimen management
		$dupArr = array();
		$catArr = array();
		$cnt = 0;
		$sql1 = 'SELECT catalognumber '.
			'FROM omoccurrences '.
			'WHERE catalognumber IS NOT NULL AND collid = '.$this->collid;
		//echo $sql1;
		$rs = $this->conn->query($sql1);
		while($r = $rs->fetch_object()){
			$cn = $r->catalognumber;
			if(array_key_exists($cn,$catArr)){
				//Dupe found
				$cnt++;
				if($start < $cnt && !array_key_exists($cn,$dupArr)){
					//Add dupe to array
					$dupArr[$cn] = '';
					if(count($dupArr) > $limit) break;
				}
			}
			else{
				$catArr[$cn] = '';
			}
		}
		$rs->free();
		
		$retArr = array();
		$sql = 'SELECT o.catalognumber AS dupid, o.occid, o.catalognumber, o.othercatalognumbers, o.family, o.sciname, '.
			'o.recordedby, o.recordnumber, o.associatedcollectors, o.eventdate, o.verbatimeventdate, '.
			'o.country, o.stateprovince, o.county, o.municipality, o.locality, o.datelastmodified '.
			'FROM omoccurrences o '.
			'WHERE o.collid = '.$this->collid.' AND o.catalognumber IN("'.implode('","',array_keys($dupArr)).'") '.
			'ORDER BY o.catalognumber';
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_assoc()){
			$retArr[(string)$row['dupid']][$row['occid']] = array_change_key_case($row);
		}
		ksort($retArr);
		
		/*
		$sql = 'SELECT o.catalognumber AS dupid, o.occid, o.catalognumber, o.othercatalognumbers, o.family, o.sciname, '.
			'o.recordedby, o.recordnumber, o.associatedcollectors, o.eventdate, o.verbatimeventdate, '.
			'o.country, o.stateprovince, o.county, o.municipality, o.locality, o.datelastmodified '.
			'FROM omoccurrences o INNER JOIN (SELECT catalognumber FROM omoccurrences '.
			'GROUP BY catalognumber, collid '. 
			'HAVING Count(occid)>1 AND collid = '.$this->collid.
			' AND catalognumber IS NOT NULL) rt ON o.catalognumber = rt.catalognumber '.
			'WHERE o.collid = '.$this->collid.' '.
			'ORDER BY o.catalognumber, o.datelastmodified DESC LIMIT '.$start.', 505';
		//echo $sql;
		$retArr = $this->getDuplicates($sql);
		*/ 
		return $retArr;
	}
	
	public function getDuplicateCollectorNumber($start){
		$retArr = array();
		$sql = '';
		if($this->obsUid){
			$sql = 'SELECT o.occid, o.eventdate, recordedby, o.recordnumber '.
				'FROM omoccurrences o INNER JOIN '. 
				'(SELECT eventdate, recordnumber FROM omoccurrences GROUP BY eventdate, recordnumber, collid, observeruid '.
				'HAVING Count(*)>1 AND collid = '.$this->collid.' AND observeruid = '.$this->obsUid.
				' AND eventdate IS NOT NULL AND recordnumber IS NOT NULL '.
				'AND recordnumber NOT IN("sn","s.n.","Not Provided","unknown")) intab '.
				'ON o.eventdate = intab.eventdate AND o.recordnumber = intab.recordnumber '.
				'WHERE collid = '.$this->collid.' AND observeruid = '.$this->obsUid.' ';
		}
		else{
			$sql = 'SELECT o.occid, o.eventdate, recordedby, o.recordnumber '.
				'FROM omoccurrences o INNER JOIN '. 
				'(SELECT eventdate, recordnumber FROM omoccurrences GROUP BY eventdate, recordnumber, collid '.
				'HAVING Count(*)>1 AND collid = '.$this->collid.
				' AND eventdate IS NOT NULL AND recordnumber IS NOT NULL '.
				'AND recordnumber NOT IN("sn","s.n.","Not Provided","unknown")) intab '.
				'ON o.eventdate = intab.eventdate AND o.recordnumber = intab.recordnumber '.
				'WHERE collid = '.$this->collid.' ';
		}
		//echo $sql;
		$rs = $this->conn->query($sql);
		$collArr = array();
		while($r = $rs->fetch_object()){
			$nameArr = Agent::parseLeadingNameInList($r->recordedby);
			if(isset($nameArr['last']) && $nameArr['last'] && strlen($nameArr['last']) > 2){
				$lastName = $nameArr['last'];
				$collArr[$r->eventdate][$r->recordnumber][$lastName][] = $r->occid;
			}
		}
		$rs->free();
		
		//Collection duplicate clusters
		$occidArr = array();
		$cnt = 0;
		foreach($collArr as $ed => $arr1){
			foreach($arr1 as $rn => $arr2){
				foreach($arr2 as $ln => $dupArr){
					if(count($dupArr) > 1){
						//Skip records until start is reached 
						if($cnt >= $start){
							$sql = 'SELECT '.$cnt.' AS dupid, o.occid, o.catalognumber, o.othercatalognumbers, o.othercatalognumbers, o.family, o.sciname, o.recordedby, o.recordnumber, '.
								'o.associatedcollectors, o.eventdate, o.verbatimeventdate, o.country, o.stateprovince, o.county, o.municipality, o.locality, datelastmodified '. 
								'FROM omoccurrences o '.
								'WHERE occid IN('.implode(',',$dupArr).') ';
							//echo $sql;
							$retArr = array_merge($retArr,$this->getDuplicates($sql)); 
						}
						if($cnt > ($start+200)) break 3;
						$cnt++;
					}
				}
			}
		}
		return $retArr;
	}

	private function getDuplicates($sql){
		$retArr = array();
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_assoc()){
			$retArr[$row['dupid']][$row['occid']] = array_change_key_case($row);
		}
		$rs->free();
		return $retArr;
	}
	
	public function mergeDupeArr($occidArr){
		$dupArr = array();
		foreach($occidArr as $v){
			$vArr = explode(':',$v);
			$k = strtoupper(trim($vArr[0]));
			if($k !== '') $dupArr[$k][] = $vArr[1];
		}
		foreach($dupArr as $catNum => $occArr){
			if(count($occArr) > 1){
				$targetOccid = array_shift($occArr);
				$statusStr = $targetOccid;
				foreach($occArr as $sourceOccid){
					$this->mergeRecords($targetOccid,$sourceOccid);
					$statusStr .= ', '.$sourceOccid;
				}
				//$this->logOrEcho('Merging records: '.$statusStr);
				echo '<li>Merging records: '.$statusStr.'</li>';
			}
			else{
				//$this->logOrEcho('Record # '.array_shift($occArr).' skipped because only one record was selected');
				echo '<li>Record # '.array_shift($occArr).' skipped because only one record was selected</li>';
			}
		}
	}
	
	public function mergeRecords($targetOccid,$sourceOccid){
		global $charset;
		if(!$targetOccid || !$sourceOccid) return 'ERROR: target or source is null';
		if($targetOccid == $sourceOccid) return 'ERROR: target and source are equal';
		$status = true;

		$oArr = array();
		//Merge records
		$sql = 'SELECT * FROM omoccurrences WHERE occid = '.$targetOccid.' OR occid = '.$sourceOccid;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_assoc()){
			$tempArr = array_change_key_case($r);
			$id = $tempArr['occid'];
			unset($tempArr['occid']);
			unset($tempArr['collid']);
			unset($tempArr['dbpk']);
			unset($tempArr['datelastmodified']);
			$oArr[$id] = $tempArr;
		}
		$rs->free();

		$tArr = $oArr[$targetOccid];
		$sArr = $oArr[$sourceOccid];
		$sqlFrag = '';
		foreach($sArr as $k => $v){
			if(($v != '') && $tArr[$k] == ''){
				$sqlFrag .= ','.$k.'="'.$v.'"';
			} 
		}
		if($sqlFrag){
			//Remap source to target
			$sqlIns = 'UPDATE omoccurrences SET '.substr($sqlFrag,1).' WHERE occid = '.$targetOccid;
			//echo $sqlIns;
			$this->conn->query($sqlIns);
		}

		//Remap determinations
		$sql = 'UPDATE omoccurdeterminations SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Delete occurrence edits
		$sql = 'DELETE FROM omoccuredits WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap images
		$sql = 'UPDATE images SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap comments
		$sql = 'UPDATE omoccurcomments SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap exsiccati
		$sql = 'UPDATE omexsiccatiocclink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap occurrence dataset links
		$sql = 'UPDATE omoccurdatasetlink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap loans
		$sql = 'UPDATE omoccurloanslink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap checklists voucher links
		$sql = 'UPDATE fmvouchers SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Remap survey lists
		$sql = 'UPDATE omsurveyoccurlink SET occid = '.$targetOccid.' WHERE occid = '.$sourceOccid;
		$this->conn->query($sql);

		//Delete source record data through the Editor class so that record is properly archived
		$editorManager = new OccurrenceEditorManager();
		$status = $editorManager->deleteOccurrence($sourceOccid);
		if(strpos($status,'ERROR') === 0) $status = '';
		
		return $status;
	}

    /** Populate omoccurrences.recordedbyid using data from omoccurrences.recordedby.
     */
	public function indexCollectors(){
		//Try to populate using already linked names 
		$sql = 'UPDATE omoccurrences o1 INNER JOIN (SELECT DISTINCT recordedbyid, recordedby FROM omoccurrences WHERE recordedbyid IS NOT NULL) o2 ON o1.recordedby = o2.recordedby '.
			'SET o1.recordedbyid = o2.recordedbyid '.
			'WHERE o1.recordedbyid IS NULL';
		$this->conn->query($sql); 
		
		//Query unlinked specimens and try to parse each collector
		$collArr = array();
		$sql = 'SELECT occid, recordedby '.
			'FROM omoccurrences '.
			'WHERE recordedbyid IS NULL';
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$collArr[$r->recordedby][] = $r->occid;
		}
		$rs->close();
		
		foreach($collArr as $collStr => $occidArr){
            // check to see if collector is listed in agents table.
            $sql = "select distinct agentid from agentname where name = ? ";
            if ($stmt = $this->conn->prepare($sql)) { 
               $stmt->bind_param('s',$collStr);
               $stmt->execute();
               $stmt->bind_result($agentid);
               $stmt->store_result();
               $matches = $stmt->num_rows;
               $stmt->fetch();  
               $stmt->close();
               if ($matches>0) { 
                  $recById= $agentid;
               } 
               else { 
                  // no matches found to collector, add to agent table.
                  $am = new AgentManager();
                  $agent = $am->constructAgentDetType($collStr);
                  if ($agent!=null) { 
                     $am->saveNewAgent($agent);
                     $agentid = $agent->getagentid();
                     $recById= $agentid;
                  }
               }
            } 
            else { 
               throw new Exception("Error preparing query $sql " . $this->conn->error);
            }

			//Add recordedbyid to omoccurrence table
			if($recById){
				$sql = 'UPDATE omoccurrences '.
					'SET recordedbyid = '.$recById.
					' WHERE occid IN('.implode(',',$occidArr).') AND recordedbyid IS NULL ';
				$this->conn->query($sql);
			}
		}
	}

	//Geographic functions
	public function echoCountryClean(){
		//Trim fields
		echo '<div>General cleaning... ';
		flush();
		ob_flush();
		$sqlTrim = 'UPDATE omoccurrences SET country = trim(country) WHERE ((country LIKE " %") OR (country LIKE "% ")) AND collid = '.$this->collid;
		$this->conn->query($sqlTrim);
		
		//Trim fields
		$sqlEmpty = 'UPDATE omoccurrences SET country = NULL WHERE (country = "")';
		$this->conn->query($sqlEmpty);
		echo 'Done!</div>';
		flush();
		ob_flush();
	}		

	public function echoCountryReport(){
		//Get bad country count
		echo '<div>Questionable countries: ';
		flush();
		ob_flush();
		$sql = 'SELECT COUNT(o.occid) AS cnt '.
			'FROM omoccurrences o LEFT JOIN lkupcountry l ON o.country = l.countryname '.
			'WHERE o.country IS NOT NULL AND o.collid = '.$this->collid.' AND l.countryid IS NULL ';
		$rs = $this->conn->query($sql); 
		if($r = $rs->fetch_object()){
			echo $r->cnt;
			if($r->cnt) echo ' => <a href="fieldcleaner.php?collid='.$this->collid.'&target=country&mode=bad"><b>Fix Values</b></a>';
			echo '</div>';
			flush();
			ob_flush();
		}
		$rs->free();
		//Get Null country and not null state county
		echo '<div>Null countries: ';
		flush();
		ob_flush();
		$sql = 'SELECT COUNT(o.occid) AS cnt '.
			'FROM omoccurrences o '.
			'WHERE o.country IS NULL AND o.stateprovince IS NOT NULL AND o.collid = '.$this->collid;
		$rs = $this->conn->query($sql); 
		if($r = $rs->fetch_object()){
			echo $r->cnt.'</div>';
			flush();
			ob_flush();
		}
		$rs->free();
		
		
		//Get null country count 
	}

	public function getBadCountryArr(){
		$retArr = array();
		$sql = 'SELECT country, count(*) as cnt '.
			'FROM omoccurrences o LEFT JOIN lkupcountry l ON o.country = l.countryname '.
			'WHERE o.country IS NOT NULL AND o.collid = '.$this->collid.' AND l.countryid IS NULL '.
			'GROUP BY o.country ';
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$cStr = $r->country.' ('.$r->cnt.')';
			$retArr[] = $cStr;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}
	
	public function getGoodCountryArr(){
		$retArr = array();
		$sql = 'SELECT country '.
			'FROM lkupcountry ';
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$retArr[] = $r->country;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}
	
	public function getStateArr($country, $limitToBad = true){
		$retArr = array();
		$sql = '';
		if($limitToBad){
			$sql = 'SELECT o.stateprovince, count(*) as cnt '.
				'FROM omoccurrences o LEFT JOIN lkupstateprovince l ON o.stateprovince = l.statename '.
				'WHERE o.stateprovince IS NOT NULL AND o.collid = '.$this->collid.' AND l.stateid IS NULL ';
			if($country) $sql .= 'AND o.country = "'.$country.'" ';
			$sql .= 'GROUP BY o.stateprovince ';
		}
		else{
			$sql = 'SELECT stateprovince '.
				'FROM omoccurrences '.
				'WHERE o.collid = '.$this->collid.' AND stateprovince IS NOT NULL ';
			if($country) $sql .= 'AND o.country = "'.$country.'" ';
		}
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$cStr = $r->stateprovince;
			if($limitToBad) $cStr .= ' ('.$r->cnt.')';
			$retArr[] = $cStr;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}
	
	public function getGoodStateArr($country = ''){
		$retArr = array();
		$sql = 'SELECT s.statename '.
			'FROM lkupstateprovince s ';
		if($country) $sql .= 'INNER JOIN lkupcountry c ON s.countryid = c.countryid '.
			'WHERE c.country = "'.$country.'"';
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$retArr[] = $r->statename;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}
	
	
	public function getCountyArr($state, $limitToBad = true){
		$retArr = array();
		$sql = '';
		if($limitToBad){
			$sql = 'SELECT o.county, count(o.occid) as cnt '.
				'FROM omoccurrences o LEFT JOIN lkupcounty l ON o.county = l.countyname '.
				'WHERE o.county IS NOT NULL AND o.collid = '.$this->collid.' AND l.countyid IS NULL ';
			if($state) $sql .= 'AND o.stateprovince = "'.$state.'" ';
			$sql .= 'GROUP BY o.county ';
		}
		else{
			$sql = 'SELECT county '.
				'FROM omoccurrences '.
				'WHERE county IS NOT NULL AND o.collid = '.$this->collid;
		}
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$cStr = $r->county;
			if($limitToBad) $cStr .= ' ('.$r->cnt.')';
			$retArr[] = $cStr;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}
	
	public function getGoodCountyArr($state){
		$retArr = array();
		$sql = 'SELECT county '.
			'FROM lkupcounty c ';
		if($state){
			$sql .= ' INNER JOIN lkupstateprovince s ON c.stateid = s.stateid '.
				'WHERE s.statename = "'.$state.'"';
		}
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$retArr[] = $r->county;
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}

	public function getBadCountryState(){
		$retArr = array();
		$sql = 'SELECT DISTINCT o.country, o.stateprovince '.
			'FROM omoccurrences o INNER JOIN lkupcountry c ON o.country = c.countryname '.
			'LEFT JOIN lkupstateprovince s ON c.countryid = s.countryid AND s.statename = o.stateprovince '.
			'WHERE s.countryid IS NULL AND o.country IS NOT NULL AND o.stateprovince IS NOT NULL';
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$retArr[] = '';
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}

	public function getBadStateCounty(){
		$retArr = array();
		$sql = 'SELECT DISTINCT o.country, o.stateprovince '.
			'FROM omoccurrences o INNER JOIN lkupstateprovince s ON o.stateprovince = s.statename '.
			'LEFT JOIN lkupcounty c ON s.stateid = c.stateid AND c.countyname = o.county '.
			'WHERE c.countyid IS NULL AND o.stateprovince IS NOT NULL AND o.county IS NOT NULL';
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$retArr[] = '';
		}
		$rs->free();
		sort($retArr);
		return $retArr;
	}

	//Setters and getters
	public function setCollId($collid){
		if(is_numeric($collid)){
			$this->collid = $collid;
		}
	}

	public function setObsuid($obsUid){
		if(is_numeric($obsUid)){
			$this->obsUid = $obsUid;
		}
	}
	
	//Misc fucntions
	public function getCollMap(){
		$retArr = Array();
		if($this->collid){
			$sql = 'SELECT CONCAT_WS("-",c.institutioncode, c.collectioncode) AS code, c.collectionname, '.
				'c.icon, c.colltype, c.managementtype '.
				'FROM omcollections c '.
				'WHERE (c.collid = '.$this->collid.') ';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$retArr['code'] = $row->code;
				$retArr['collectionname'] = $row->collectionname;
				$retArr['icon'] = $row->icon;
				$retArr['colltype'] = $row->colltype;
				$retArr['managementtype'] = $row->managementtype;
			}
			$rs->free();
		}
		return $retArr;
	}
}
?>