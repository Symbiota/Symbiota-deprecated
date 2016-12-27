<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/Manager.php');
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
include_once($serverRoot.'/classes/AgentManager.php');
include_once($serverRoot.'/classes/TaxonomyHarvester.php');

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
			$taxonHarvester = new  TaxonomyHarvester();
			$taxonHarvester->setVerboseMode(2);
			$this->setVerboseMode(2);
			$nameCnt = 0;
			while($r = $rs->fetch_object()){
				$this->logOrEcho('Resolving '.$r->sciname.($r->family?' ('.$r->family.')':'').'...');
				$newTid = $taxonHarvester->addSciname($r->sciname, $r->family);
				if(!$newTid){
					//Check for near match using SoundEx
					$this->logOrEcho('Checking close matches in thesaurus...',1);
					$closeArr = $taxonHarvester->getSoundexMatch($r->sciname);
					if(!$closeArr) $closeArr = $taxonHarvester->getCloseMatchEpithet($r->sciname);
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
	public function getDuplicateCatalogNumber($type,$start,$limit = 500){
		//Search is not available for personal specimen management
		$dupArr = array();
		$catArr = array();
		$cnt = 0;
		if($type=='cat'){
			$sql1 = 'SELECT catalognumber '.
				'FROM omoccurrences '.
				'WHERE catalognumber IS NOT NULL AND collid = '.$this->collid;
		}
		else{
			$sql1 = 'SELECT otherCatalogNumbers '.
				'FROM omoccurrences '.
				'WHERE otherCatalogNumbers IS NOT NULL AND collid = '.$this->collid;
		}
		//echo $sql1;
		$rs = $this->conn->query($sql1);
		while($r = $rs->fetch_object()){
			$cn = ($type=='cat'?$r->catalognumber:$r->otherCatalogNumbers);
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
		if($type=='cat'){
			$sql = 'SELECT o.catalognumber AS dupid, o.occid, o.catalognumber, o.othercatalognumbers, o.family, o.sciname, '.
				'o.recordedby, o.recordnumber, o.associatedcollectors, o.eventdate, o.verbatimeventdate, '.
				'o.country, o.stateprovince, o.county, o.municipality, o.locality, o.datelastmodified '.
				'FROM omoccurrences o '.
				'WHERE o.collid = '.$this->collid.' AND o.catalognumber IN("'.implode('","',array_keys($dupArr)).'") '.
				'ORDER BY o.catalognumber';
		}
		else{
			$sql = 'SELECT o.otherCatalogNumbers AS dupid, o.occid, o.catalognumber, o.othercatalognumbers, o.family, o.sciname, '.
				'o.recordedby, o.recordnumber, o.associatedcollectors, o.eventdate, o.verbatimeventdate, '.
				'o.country, o.stateprovince, o.county, o.municipality, o.locality, o.datelastmodified '.
				'FROM omoccurrences o '.
				'WHERE o.collid = '.$this->collid.' AND o.otherCatalogNumbers IN("'.implode('","',array_keys($dupArr)).'") '.
				'ORDER BY o.otherCatalogNumbers';
		}
		//echo $sql;
		
		$retArr = $this->getDuplicates($sql);
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
		$cnt = 0;
		$dupid = '';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_assoc()){
			if($dupid != $row['dupid']) $cnt++;
			$retArr[$cnt][$row['occid']] = array_change_key_case($row);
			$dupid = $row['dupid'];
		}
		$rs->free();
		return $retArr;
	}

	public function mergeDupeArr($occidArr){
		$status = true;
		$this->verboseMode = 2;
		$editorManager = new OccurrenceEditorManager($this->conn);
		foreach($occidArr as $target => $occArr){
			$mergeArr = array($target);
			foreach($occArr as $source){
				if($source != $target){
					if($editorManager->mergeRecords($target,$source)){
						$mergeArr[] = $source;
					}
					else{
						$this->logOrEcho($editorManager->getErrorStr(),1);
						$status = false;
					}
				}
			}
			if(count($mergeArr) > 1){
				$this->logOrEcho('Merged records: '.implode(', ',$mergeArr),1);
			}
		}
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
		$rs->free();
		
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
	public function countryCleanFirstStep(){
		//Country cleaning
		$sqlTrim = 'UPDATE omoccurrences SET country = trim(country) WHERE ((country LIKE " %") OR (country LIKE "% ")) AND collid = '.$this->collid;
		$this->conn->query($sqlTrim);
		
		$sqlEmpty = 'UPDATE omoccurrences SET country = NULL WHERE (country = "")';
		$this->conn->query($sqlEmpty);
		echo '<div style="margin-left:15px;">Countries cleaned!</div>';
		flush();
		ob_flush();
		
		//State cleaning
		$sqlTrim = 'UPDATE omoccurrences SET stateprovince = trim(stateprovince) WHERE ((stateprovince LIKE " %") OR (stateprovince LIKE "% ")) AND collid = '.$this->collid;
		$this->conn->query($sqlTrim);
		
		$sqlEmpty = 'UPDATE omoccurrences SET stateprovince = NULL WHERE (stateprovince = "")';
		$this->conn->query($sqlEmpty);
		echo '<div style="margin-left:15px;">States cleaned!</div>';
		flush();
		ob_flush();
		
		//County cleaning
		$sqlTrim = 'UPDATE omoccurrences SET county = trim(county) WHERE ((county LIKE " %") OR (county LIKE "% ")) AND collid = '.$this->collid;
		$this->conn->query($sqlTrim);
		
		$sqlEmpty = 'UPDATE omoccurrences SET county = NULL WHERE (county = "")';
		$this->conn->query($sqlEmpty);
		echo '<div style="margin-left:15px;">Counties cleaned!</div>';
		flush();
		ob_flush();
	}		

	//Bad countries
	public function getBadCountryCount(){
		$retCnt = 0;
		$sql = 'SELECT COUNT(DISTINCT o.country) AS cnt '.
			'FROM omoccurrences o LEFT JOIN lkupcountry l ON o.country = l.countryname '.
			'WHERE o.country IS NOT NULL AND o.collid = '.$this->collid.' AND l.countryid IS NULL ';
		$rs = $this->conn->query($sql); 
		if($r = $rs->fetch_object()){
			$retCnt = $r->cnt;
		}
		$rs->free();
		return $retCnt;
	}

	public function getBadCountryArr(){
		$retArr = array();
		$sql = 'SELECT country, count(o.occid) as cnt '.
			'FROM omoccurrences o LEFT JOIN lkupcountry l ON o.country = l.countryname '.
			'WHERE o.country IS NOT NULL AND o.collid = '.$this->collid.' AND l.countryid IS NULL '.
			'GROUP BY o.country ';
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$retArr[$r->country] = $r->cnt;
		}
		$rs->free();
		ksort($retArr);
		return $retArr;
	}

	public function getGoodCountryArr($includeStates = false){
		$retArr = array();
		if($includeStates){
			$sql = 'SELECT c.countryname, s.statename FROM lkupcountry c INNER JOIN lkupstateprovince s ON c.countryid = s.countryid ';
			$rs = $this->conn->query($sql); 
			while($r = $rs->fetch_object()){
				$retArr[$r->countryname][] = $r->statename;
			}
			$rs->free();
			ksort($retArr);
		}
		else{
			$sql = 'SELECT countryname FROM lkupcountry';
			$rs = $this->conn->query($sql); 
			while($r = $rs->fetch_object()){
				$retArr[] = $r->countryname;
			}
			$rs->free();
			sort($retArr);
		}
		return $retArr;
	}

	public function getNullCountryNotStateCount(){
		$retCnt = 0;
		$sql = 'SELECT COUNT(DISTINCT stateprovince) AS cnt '.
			'FROM omoccurrences '.
			'WHERE (collid = '.$this->collid.') AND (country IS NULL) AND (stateprovince IS NOT NULL)';
		$rs = $this->conn->query($sql); 
		if($r = $rs->fetch_object()){
			$retCnt = $r->cnt;
		}
		$rs->free();
		return $retCnt;
	}
	
	public function getNullCountryNotStateArr(){
		$retArr = array();
		$sql = 'SELECT stateprovince, COUNT(occid) AS cnt '.
			'FROM omoccurrences '.
			'WHERE (collid = '.$this->collid.') AND (country IS NULL) AND (stateprovince IS NOT NULL) '.
			'GROUP BY stateprovince';
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$retArr[$r->stateprovince] = $r->cnt;
		}
		$rs->free();
		ksort($retArr);
		return $retArr;
	}

	public function updateCountry($oldValue, $newValue, $conditionArr = null){
		if(is_numeric($this->collid) && $oldValue && $newValue){
			$sql = 'UPDATE omoccurrences SET country = "'.$this->cleanInStr($newValue).'" '.
				'WHERE (collid = '.$this->collid.') ';
			if($oldValue == '--ISNULL--'){
				$sql .= 'AND (country IS NULL) ';
			}
			else{
				$sql .= 'AND (country = "'.$this->cleanInStr($oldValue).'") ';
			}
			if($conditionArr){
				foreach($conditionArr as $k => $v){
					if($v == '--ISNULL--'){
						$sql .= ' AND ('.$this->cleanInStr($k).' IS NULL) ';
					}
					else{
						$sql .= ' AND ('.$this->cleanInStr($k).' = "'.$this->cleanInStr($v).'") ';
					}
				}
			}
			//echo $sql; exit;
			if(!$this->conn->query($sql)){
				$this->errorStr = 'ERROR updating country with new value: '.$this->conn->error;
				return false;
			}
		}
		return true;
	}

	//States cleaning functions
	public function getBadStateCount($country = ''){
		$retCnt = array();
		$sql = 'SELECT COUNT(DISTINCT o.stateprovince) as cnt '.
			'FROM omoccurrences o LEFT JOIN lkupstateprovince l ON o.stateprovince = l.statename '.
			'WHERE (o.country IS NOT NULL) AND (o.stateprovince IS NOT NULL) AND (o.collid = '.$this->collid.') AND (l.stateid IS NULL) ';
		if($country) $sql .= 'AND o.country = "'.$this->cleanInStr($country).'" ';
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$retCnt = $r->cnt;
		}
		$rs->free();
		return $retCnt;
	}

	public function getBadStateArr(){
		$retArr = array();
		$sql = 'SELECT o.country, o.stateprovince, count(DISTINCT o.occid) as cnt '.
			'FROM omoccurrences o LEFT JOIN lkupstateprovince l ON o.stateprovince = l.statename '.
			'WHERE (o.country IS NOT NULL) AND (o.stateprovince IS NOT NULL) AND (o.collid = '.$this->collid.') AND (l.stateid IS NULL) '.
			'GROUP BY o.stateprovince ';
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$retArr[$r->country][$r->stateprovince] = $r->cnt;
		}
		$rs->free();
		ksort($retArr);
		return $retArr;
	}

	public function getBadCountryState(){
		$retArr = array();
		/*
		$sql = 'SELECT DISTINCT o.stateprovince, o.country, o2.country, c.countryname '.
			'FROM omoccurrences o INNER JOIN lkupstateprovince s ON o.stateprovince = s.statename '.
			'INNER JOIN lkupcountry c ON s.countryid = c.countryid '. 
			'LEFT JOIN omoccurrences o2 ON c.countryname = o2.country AND o2.occid = o.occid '.
			'WHERE o.collid = '.$this->collid.' AND o.country IS NOT NULL AND o2.occid IS NULL';
		*/
		$stateArr1 = array();
		$sql = 'SELECT DISTINCT o.stateprovince, o.country '.
			'FROM omoccurrences o '.
			'WHERE (o.collid = '.$this->collid.') AND (o.country IS NOT NULL) AND (o.stateprovince IS NOT NULL) ';
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$stateArr1[$r->country][] = $r->stateprovince;
		}
		$rs->free();
		$stateArr2 = array();
		$sql2 = 'SELECT DISTINCT countryname, statename '.
			'FROM lkupcountry c INNER JOIN lkupstateprovince s ON c.countryid = s.countryid ';
		$rs2 = $this->conn->query($sql2); 
		while($r2 = $rs2->fetch_object()){
			$stateArr2[$r2->countryname][] = $r2->statename;
		}
		$rs2->free();
		
		$retArr = array_diff_assoc($stateArr1,$stateArr2);
		print_r($retArr);

		ksort($retArr);
		return $retArr;
	}

	public function getGoodStateArr($includeCounties = false){
		$retArr = array();
		if($includeCounties){
			$sql = 'SELECT c.countryname, s.statename, co.countyname '.
				'FROM lkupstateprovince s INNER JOIN lkupcountry c ON s.countryid = c.countryid '.
				'LEFT JOIN lkupcounty co ON s.stateid = co.stateid ';
			$rs = $this->conn->query($sql); 
			while($r = $rs->fetch_object()){
				$retArr[$r->countryname][$r->statename][] = str_replace(array(' County',' Co.',' Co'),'',$r->countyname);
			}
			$rs->free();
		}
		else{
			$sql = 'SELECT c.countryname, s.statename '.
				'FROM lkupstateprovince s INNER JOIN lkupcountry c ON s.countryid = c.countryid ';
			$rs = $this->conn->query($sql); 
			while($r = $rs->fetch_object()){
				$retArr[$r->countryname][] = $r->statename;
			}
			$rs->free();
		}
		ksort($retArr);
		return $retArr;
	}

	public function getNullStateNotCountyCount(){
		$retCnt = 0;
		$sql = 'SELECT COUNT(DISTINCT county) AS cnt '.
			'FROM omoccurrences '.
			'WHERE (collid = '.$this->collid.') AND (stateprovince IS NULL) AND (county IS NOT NULL) AND (country IS NOT NULL) ';
		$rs = $this->conn->query($sql); 
		if($r = $rs->fetch_object()){
			$retCnt = $r->cnt;
		}
		$rs->free();
		return $retCnt;
	}

	public function getNullStateNotCountyArr(){
		$retArr = array();
		$sql = 'SELECT country, county, COUNT(occid) AS cnt '.
			'FROM omoccurrences '.
			'WHERE (collid = '.$this->collid.') AND (stateprovince IS NULL) AND (county IS NOT NULL) AND (country IS NOT NULL) '.
			'GROUP BY county';
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$retArr[$r->country][$r->county] = $r->cnt;
		}
		$rs->free();
		ksort($retArr);
		return $retArr;
	}

	public function updateState($oldValue, $newValue, $conditionArr = null){
		if(is_numeric($this->collid) && $oldValue && $newValue){
			$sql = 'UPDATE omoccurrences SET stateprovince = "'.$this->cleanInStr($newValue).'" '.
				'WHERE (collid = '.$this->collid.') ';
			if($oldValue == '--ISNULL--'){
				$sql .= 'AND (stateprovince IS NULL) ';
			}
			else{
				$sql .= ' AND (stateprovince = "'.$this->cleanInStr($oldValue).'") ';
			}
			if($conditionArr){
				foreach($conditionArr as $k => $v){
					if($v == '--ISNULL--'){
						$sql .= ' AND ('.$this->cleanInStr($k).' IS NULL) ';
					}
					else{
						$sql .= ' AND ('.$this->cleanInStr($k).' = "'.$this->cleanInStr($v).'") ';
					}
				}
			}
			//echo $sql; exit;
			if(!$this->conn->query($sql)){
				$this->errorStr = 'ERROR updating stateProvince with new value: '.$this->conn->error;
				return false;
			}
		}
		return true;
	}

	//Bad Counties
	public function getBadCountyCount($state = ''){
		$retCnt = array();
		$sql = 'SELECT COUNT(DISTINCT o.county) as cnt '.
			'FROM omoccurrences o LEFT JOIN lkupcounty l ON o.county = l.countyname '.
			'WHERE (o.county IS NOT NULL) AND (o.country IS NOT NULL) AND (o.stateprovince IS NOT NULL) '.
			'AND o.collid = '.$this->collid.' AND (l.countyid IS NULL) ';
		if($state) $sql .= 'AND o.stateprovince = "'.$this->cleanInStr($state).'" ';
		$rs = $this->conn->query($sql); 
		if($r = $rs->fetch_object()){
			$retCnt = $r->cnt;
		}
		$rs->free();
		return $retCnt;
	}

	public function getBadCountyArr(){
		$retArr = array();
		$sql = 'SELECT o.country, o.stateprovince, o.county, count(o.occid) as cnt '.
			'FROM omoccurrences o LEFT JOIN lkupcounty l ON o.county = l.countyname '.
			'WHERE (o.county IS NOT NULL) AND (o.country IS NOT NULL) AND (o.stateprovince IS NOT NULL) '.
			'AND (o.collid = '.$this->collid.') AND (l.countyid IS NULL) '.
			'GROUP BY o.country, o.stateprovince, o.county ';
		//echo $sql;
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$retArr[$r->country][$r->stateprovince][$r->county] = $r->cnt;
		}
		$rs->free();
		ksort($retArr);
		return $retArr;
	}

	public function getGoodCountyArr(){
		$retArr = array();
		$sql = 'SELECT DISTINCT statename, REPLACE(countyname," County","") AS countyname '.
			'FROM lkupcounty c INNER JOIN lkupstateprovince s ON c.stateid = s.stateid ';
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$retArr[$r->statename][] = $r->countyname;
		}
		$rs->free();
		ksort($retArr);
		return $retArr;
	}

	public function getNullCountyNotLocalityCount(){
		$retCnt = 0;
		$sql = 'SELECT COUNT(DISTINCT locality) AS cnt '.
			'FROM omoccurrences '.
			'WHERE (collid = '.$this->collid.') AND (county IS NULL) AND (locality IS NOT NULL) AND country IN("USA","United States") AND (stateprovince IS NOT NULL) ';
		$rs = $this->conn->query($sql); 
		if($r = $rs->fetch_object()){
			$retCnt = $r->cnt;
		}
		$rs->free();
		return $retCnt;
	}

	public function getNullCountyNotLocalityArr(){
		$retArr = array();
		$sql = 'SELECT country, stateprovince, locality, COUNT(occid) AS cnt '.
			'FROM omoccurrences '.
			'WHERE (collid = '.$this->collid.') AND (county IS NULL) AND (locality IS NOT NULL) '.
			'AND country IN("USA","United States") AND (stateprovince IS NOT NULL) '.
			'GROUP BY country, stateprovince, locality';
		$rs = $this->conn->query($sql); 
		while($r = $rs->fetch_object()){
			$locStr = $r->locality;
			if(strlen($locStr) > 40) $locStr = substr($locStr,0,40).'...';
			$retArr[$r->country][$r->stateprovince][$locStr] = $r->cnt;
		}
		$rs->free();
		ksort($retArr);
		return $retArr;
	}

	public function updateCounty($oldValue, $newValue, $conditionArr = null){
		if(is_numeric($this->collid) && $oldValue && $newValue){
			$sql = 'UPDATE omoccurrences SET county = "'.$this->cleanInStr($newValue).'" '.
				'WHERE (collid = '.$this->collid.') ';
			if($oldValue == '--ISNULL--'){
				$sql .= 'AND (county IS NULL) ';
			}
			else{
				$sql .= ' AND (county = "'.$this->cleanInStr($oldValue).'") ';
			}
			if($conditionArr){
				foreach($conditionArr as $k => $v){
					if($v == '--ISNULL--'){
						$sql .= ' AND ('.$this->cleanInStr($k).' IS NULL) ';
					}
					else{
						$sql .= ' AND ('.$this->cleanInStr($k).' = "'.$this->cleanInStr($v).'") ';
					}
				}
			}
			//echo $sql; exit;
			if(!$this->conn->query($sql)){
				$this->errorStr = 'ERROR updating county with new value: '.$this->conn->error;
				return false;
			}
		}
		return true;
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