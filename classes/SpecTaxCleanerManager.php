<?php
include_once($serverRoot.'/config/dbconnection.php');
  
class SpecTaxCleanerManager{

	private $conn;
	private $collId;
	private $taxAuthId = 1;
	private $testValidity = 1;
	private $testTaxonomy = 1;
	private $checkAuthor = 1;
	private $verificationMode = 0;		//0 = default to internal taxonomy, 1 = adopt target taxonomy
	
	public function __construct(){
 		$this->conn = MySQLiConnectionFactory::getCon('write');
	}

	function __destruct(){
		if($this->conn) $this->conn->close();
	}

	public function linkSciNames($collId){
		//First make sure that all tidinterpreted have been checked 
		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname '.
			'SET o.tidinterpreted = t.tid '.
			'WHERE o.tidinterpreted IS NULL ';
		if($collId && is_numeric($collId)) $sql .= 'AND (o.collid = '.$collId.')';
		$this->conn->query($sql);
	}

	public function verifyCollectionNames($collId){
		//Grab list of taxa, check each one, add valid taxa to taxonomic thesaurus, return number added and number problematic remaining
		$numGood = 0;
		$numBad = 0;
		$sql = 'SELECT DISTINCT o.sciname FROM omoccurrences o '.
			'WHERE o.tidinterpreted IS NULL AND o.sciname IS NOT NULL ';
		if($collId && is_numeric($collId)) $sql .= 'AND (o.collid = '.$collId.') '; 
		$sql .= 'ORDER BY o.sciname LIMIT 1';
		//echo '<div>'.$sql.'</div>';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($r->sciname){
				$externalTaxonObj = $this->getTaxonObjSpecies2000($r->sciname);
				if($externalTaxonObj){
					//Name is good but not in thesuarus, thus add
					$numGood++;
					//External is accepted
						//Default to internal system's taxonomy
							//1. Go through external synonyms
							//2. If one is found and taxonomically tested, add new name linked to the accepted name of that taxon
							//3. Add and link rest of the Synonyms to this name 
						//Default to external system's taxonomy
							//1. Add name as accepted
							//2. Go through synonyms and add linked to new name
							//3. If synonym already exists, link to accetped name 
					//External is not accepted
						//1. Grab and test external accepted name
						//Default to internal system's taxonomy
							//2a. Accepted name does not exist: Go through synonyms and test, 
								//3a. If one exists, map all to this accepted taxon
								//3b. If not, add accepted name and link all to it (including synonyms)
							//2b. Accepted name exists: Link all to it (including synonyms) 
						//Default to external system's taxonomy
							//4a. External accepted does not exist: add name and link all to it (including synonyms that don't exist)
							//4b. External accepted does exists...
								
				}
				else{
					//Name is not good, mark as so
					$numBad++;
					$sql = 'UPDATE omoccurrences SET taxonstatus = 1 WHERE (sciname = "'.$r->sciname.'") AND tidinterpreted IS NULL ';
					$this->conn->query($sql);
				}
			}
		}
		$rs->close();
		$retArr['good'] = $numGood;
		$retArr['bad'] = $numBad;
		return $retArr;
	}

	public function verifyExistingNames(){
		//Check accepted taxa first
		$sql = 'SELECT t.sciname, t.tid, t.author, ts.tidaccepted FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND ts.tid = ts.tidaccepted '; 
		if($this->testValidity){
			$sql .= 'AND t.validitystatus IS NULL ';
		}
		$sql .= 'LIMIT 1';
		//echo '<div>'.$sql.'</div>';
		while($rs = $this->conn->query($sql)){
			while($taxonArr = $rs->fetch_assoc()){
				$externalTaxonObj = $this->getTaxonObjSpecies2000($taxonArr['sciname']);
				$this->verifyTaxonObj($externalTaxonObj,$taxonArr,$taxonArr['tid']);
			}
			$rs->close();
		}
		
		//Check remaining taxa 
		$sql = 'SELECT t.sciname, t.tid, t.author, ts.tidaccepted FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE (ts.taxauthid = '.$this->taxAuthId.') '; 
		if($this->testValidity){
			$sql .= 'AND t.validitystatus IS NULL ';
		}
		$sql .= 'LIMIT 1';
		//echo '<div>'.$sql.'</div>';
		while($rs = $this->conn->query($sql)){
			while($taxonArr = $rs->fetch_assoc()){
				$externalTaxonObj = $this->getTaxonObjSpecies2000($taxonArr['sciname']);
				$this->verifyTaxonObj($externalTaxonObj,$taxonArr,$taxonArr['tid']);
			}
			$rs->close();
		}
	}
	
	private function getTaxonObjSpecies2000($sciName, $resultIndex = 0){
		$resultArr = Array();
		$urlTemplate = "http://www.catalogueoflife.org/annual-checklist/2010/webservice?format=php&response=full&name=";
		$url = $urlTemplate.str_replace(" ","%20",$sciName);
		if($fh = fopen($url, 'r')){
			$content = "";
			while($line = fread($fh, 1024)){
				$content .= trim($line);
			}
			fclose($fh);
			//Process return
			$retArr = unserialize($content);
			$numResults = $retArr['number_of_results_returned'];
			if($numResults){
				if($resultIndex && $resultIndex < $numResults){
					$resultArr = $retArr['results'][$resultIndex];
				}
				else{
					$resultArr = array_shift($retArr['results']);
				}
			}
		}
		return $resultArr;
	}

	private function getTaxonObjTropicos($sciName){
		$urlTemplate = "";
		$url = $urlTemplate.str_replace(" ","%20",$sciName);
		if($fp = fopen($url, 'r')){
			echo "<div>Reading page for ".$sciName." </div>\n";
			$content = "";
			while($line = fread($fp, 1024)){
				$content .= trim($line);
			}
			$regExp = "\<A HREF='florataxon\.aspx\?flora_id=\d+&taxon_id=(\d+)'\s+TITLE='Accepted Name' \>\<b\>".$sciName."\<\/b\>\<\/A\>";
			if($fnaCap = preg_match_all("/".$regExp."/sU", $content, $matches)){
				echo $matches[1][0];

			
			}
		}
		ob_flush();
		flush();
		sleep(5);
	}
	
	private function verifyTaxonObj($externalTaxonObj,$internalTaxonObj, $tidCurrentAccepted){
		//Set validitystatus of name
		if($externalTaxonObj){
			$source = $externalTaxonObj['source_database'];
			if($this->testValidity){
				$sql = 'UPDATE taxa SET validitystatus = 1, validitysource = "'.$source.'" WHERE (tid = '.$internalTaxonObj['tid'].')';
				$this->conn->query($sql);
			}
			//Check author
			if($this->checkAuthor){
				if($externalTaxonObj['author'] && $internalTaxonObj['author'] != $externalTaxonObj['author']){
					$sql = 'UPDATE taxa SET author = '.$externalTaxonObj['author'].' WHERE (tid = '.$internalTaxonObj['tid'].')';
					$this->conn->query($sql);
				}
			}
			//Test taxonomy
			if($this->testTaxonomy){
				$nameStatus = $externalTaxonObj['name_status'];

				if($this->verificationMode === 0){					//Default to system taxonomy
					if($nameStatus == 'accepted'){					//Accepted externally, thus in both locations accepted
						//Go through synonyms and check each. 
						$synArr = $externalTaxonObj['synonyms'];
						foreach($synArr as $synObj){
							$this->evaluateTaxonomy($synObj,$tidCurrentAccepted);
						}
					}
				}
				elseif($this->verificationMode == 1){				//Default to taxonomy of external source
					if($taxonArr['tid'] == $tidCurrentAccepted){	//Is accepted within system
						if($nameStatus == 'accepted'){				//Accepted externally, thus in both locations accepted
							//Go through synonyms and check each
							$synArr = $externalTaxonObj['synonyms'];
							foreach($synArr as $synObj){
								$this->evaluateTaxonomy($synObj,$tidCurrentAccepted);
							}
						}
						elseif($nameStatus == 'synonym'){			//Not Accepted externally
							//Get accepted and evalutate
							$accObj = $externalTaxonObj['accepted_name'];
							$accTid = $this->evaluateTaxonomy($accObj,0);
							//Change to not accepted and link to accepted 
							$sql = 'UPDATE taxstatus SET tidaccetped = '.$accTid.' WHERE (taxauthid = '.$this->taxAuthId.') AND (tid = '.
								$taxonArr['tid'].') AND (tidaccepted = '.$tidCurrentAccepted.')';
							$this->conn->query($sql);
							$this->updateDependentData($taxonArr['tid'],$accTid);
							//Go through synonyms and evaluate
							$synArr = $externalTaxonObj['synonyms'];
							foreach($synArr as $synObj){
								$this->evaluateTaxonomy($synObj,$accTid);
							}
						}
					}
					else{											//Is not accepted within system
						if($nameStatus == 'accepted'){				//Accepted externally
							//Remap to external name
							$this->evaluateTaxonomy($taxonArr,0);
						}
						elseif($nameStatus == 'synonym'){			//Not Accepted in both
							//Get accepted name; compare with system's accepted name; if different, remap
							$sql = 'SELECT sciname FROM taxa WHERE (tid = '.$taxonArr['tidaccepted'].')';
							$rs = $this->conn->query($sql);
							$systemAccName = '';
							if($r = $rs->fetch_object()){
								$systemAccName = $r->sciname;
							}
							$rs->close();
							$accObj = $externalTaxonObj['accepted_name'];
							if($accObj['name'] != $systemAccName){
								//Remap to external name
								$tidToBeAcc = $this->evaluateTaxonomy($accObj,0);
								$sql = 'UPDATE taxstatus SET tidaccetped = '.$tidToBeAcc.' WHERE (taxauthid = '.$this->taxAuthId.') AND (tid = '.
									$taxonArr['tid'].') AND (tidaccepted = '.$taxonArr['tidaccepted'].')';
								$this->conn->query($sql);
							}
						}
					}
				}
			}
		}
		else{
			//Name not found
			if($this->testValidity){
				$sql = 'UPDATE taxa SET validitystatus = 0, validitysource = "Species 2000" WHERE (tid = '.$taxonArr['tid'].')';
				$this->conn->query($sql);
			}
		}
	}
	
	private function evaluateTaxonomy($testObj, $anchorTid){
		$retTid = 0;
		$sql = 'SELECT t.tid, ts.tidaccepted, t.sciname, t.author '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE (ts.taxauthid = '.$this->taxAuthId.')';
		if(array_key_exists('name',$testObj)){
			$sql .= ' AND (t.sciname = "'.$testObj['name'].'")';
		}
		else{
			$sql .= ' AND (t.tid = "'.$testObj['tid'].'")';
		}
		$rs = $this->conn->query($sql);
		if($rs){
			if($this->testValidity){
				$sql = 'UPDATE taxa SET validitystatus = 1, validitysource = "Species 2000" WHERE (t.sciname = "'.$testObj['name'].'")';
				$this->conn->query($sql);
			}
			while($r = $rs->fetch_object()){
				//Taxon exists within symbiota node
				$retTid = $r->tid;
				if(!$anchorTid) $anchorTid = $retTid;	//If $anchorTid = 0, we assume it should be accepted 
				$tidAcc = $r->tidaccepted;
				if($tidAcc == $anchorTid){
					//Do nothing, they match
				}
				else{
					//Adjust taxonomy: point to anchor
					$sql = 'UPDATE taxstatus SET tidaccepted = '.$anchorTid.' WHERE (taxauthid = '.$this->taxAuthId.
						') AND (tid = '.$retTid.') AND (tidaccepted = '.$tidAcc.')';
					$this->conn->query($sql);
					//Point synonyms to anchor tid
					$sql = 'UPDATE taxstatus SET tidaccepted = '.$anchorTid.' WHERE (taxauthid = '.$this->taxAuthId.
						') AND (tidaccepted = '.$retTid.')';
					$this->conn->query($sql);
					if($retTid == $tidAcc){
						//Move descriptions, key morphology, and vernacular over to new accepted
						$this->updateDependentData($tidAcc,$anchorTid);
					}
				}
			}
		}
		else{
			//Test taxon does not exists, thus lets load it 
			$retTid = $this->loadNewTaxon($testObj,$anchorTid);
		}
		return $retTid;
	}

	private function updateDependentData($tid, $tidNew){
		//method to update descr, vernaculars,
		if(is_numeric($tid) && is_numeric($tidNew)){
			$this->conn->query("DELETE FROM kmdescr WHERE inherited IS NOT NULL AND (tid = ".$tid.')');
			$this->conn->query("UPDATE IGNORE kmdescr SET tid = ".$tidNew." WHERE (tid = ".$tid.')');
			$this->conn->query("DELETE FROM kmdescr WHERE (tid = ".$tid.')');
			$this->resetCharStateInheritance($tidNew);
			
			$sqlVerns = "UPDATE taxavernaculars SET tid = ".$tidNew." WHERE (tid = ".$tid.')';
			$this->conn->query($sqlVerns);
			
			$sqltd = 'UPDATE taxadescrblock tb LEFT JOIN (SELECT DISTINCT caption FROM taxadescrblock WHERE (tid = '.$tidNew.')) lj ON tb.caption = lj.caption '.
				'SET tid = '.$tidNew.' WHERE (tid = '.$tid.') AND lj.caption IS NULL';
			$this->conn->query($sqltd);
	
			$sqltl = "UPDATE taxalinks SET tid = ".$tidNew." WHERE (tid = ".$tid.')';
			$this->conn->query($sqltl);
		}
	}
	
	private function resetCharStateInheritance($tid){
		//set inheritance for target only
		$sqlAdd1 = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
			"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
			"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
			"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
			"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
			"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
			"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
			"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
			"WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.tid = ts2.tidaccepted) ".
			"AND (t2.tid = $tid) And (d2.CID Is Null)";
		$this->conn->query($sqlAdd1);

		//Set inheritance for all children of target
		if($this->rankId == 140){
			$sqlAdd2a = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
				"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
				"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
				"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
				"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
				"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
				"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
				"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
				"WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.tid = ts2.tidaccepted) ".
				"AND (t2.RankId = 180) AND (t1.tid = $tid) AND (d2.CID Is Null)";
			//echo $sqlAdd2a;
			$this->conn->query($sqlAdd2a);
			$sqlAdd2b = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
				"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
				"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
				"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
				"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
				"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
				"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
				"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
				"WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.family = '".$this->sciName."') AND (ts2.tid = ts2.tidaccepted) ".
				"AND (t2.RankId = 220) AND (d2.CID Is Null)";
			$this->conn->query($sqlAdd2b);
		}

		if($this->rankId > 140 && $this->rankId < 220){
			$sqlAdd3 = "INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited ) ".
				"SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT, ".
				"d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent ".
				"FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID) ".
				"INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid) ".
				"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID) ".
				"INNER JOIN taxa t2 ON ts2.tid = t2.tid) ".
				"LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID) ".
				"WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.tid = ts2.tidaccepted) ".
				"AND (t2.RankId = 220) AND (t1.tid = $tid) AND (d2.CID Is Null)";
			//echo $sqlAdd2b;
			$this->conn->query($sqlAdd3);
		}
	}
	
	private function loadNewTaxon($newTaxon,$anchorTid = 0){
		//Add taxon
		if(!array_key_exists('rank',$newTaxon)){
			//Grab taxon object from Species2000
			
		}
		if(array_key_exists('rank',$newTaxon)){
			$tid = 0;
			$rankId = 0;
			$parentName = '';
			if($newTaxon['rank'] == 'Species'){
				$rankId = 220;
				$parentName = $newTaxon['genus'];
			}
			elseif($newTaxon['rank'] == 'Infraspecies'){
				if($newTaxon['infraspecies_marker'] == 'ssp.'){
					$rankId = 230;
				}
				if($newTaxon['infraspecies_marker'] == 'var.'){
					$rankId = 240;
				}
				if($newTaxon['infraspecies_marker'] == 'f.'){
					$rankId = 260;
				}
				$parentName = trim($newTaxon['genus'].' '.$newTaxon['species']);
			}
			if($rankId){
				if(!$parentName){
					$classArr = Array();
					if(array_key_exists('classification',$newTaxon)){
						$classArr = $newTaxon['classification'];
					}
					if(!$classArr){
						//grab name object and classification from Species2000
					}
					if($classArr){
						$parArr = array_pop($classArr);
						$parentName = $parArr['name'];
					}
				}
				if($parentName){
					$sqlParent = 'SELECT tid FROM taxa WHERE (sciname = "'.$parentName.'")';
					$rs = $this->conn->query($sqlParent);
					$parTid = $rs->tid;
					if(!$parTid){
						$parTid = $loadNewTaxon(Array('name' => $parentName));
					}
					if($parTid){
						if($r = $rs->fetch_object()){
							//We now have everything, now let's load
							$sciName = trim($newTaxon['genus'].' '.$newTaxon['species'].' '.$newTaxon['infraspecies_marker'].' '.$newTaxon['infraspecies']);
							$sqlInsert = 'INSERT INTO taxa(sciname, unitname1, unitname2, unitind3, unitname3, author, rankid) '.
								'VALUES("'.$sciName.'","'.$newTaxon['genus'].'","'.$newTaxon['species'].'","'.$newTaxon['infraspecies_marker'].'","'.
								$newTaxon['infraspecies'].'","'.$newTaxon['author'].'",'.$rankId.')';
							if($this->conn->query($sqlInsert)){
								$tid = $this->conn->insert_id;
								if(!$anchorTid){
									$anchorTid = $tid;
								}
								$sqlInsert2 = 'INSERT INTO taxstatus(tid,tidaccepted,taxauthid,parenttid) '.
									'VALUES('.$tid.','.$anchorTid.','.$this->taxAuthId.','.$r->tid.')';
								if($this->conn->query($sqlInsert2)){
									//Add common names
									
									
								}
							}
							
						}
					}
					$rs->close();
				}
			}
		}
		return $tid;
	}

	private function getHierarchy($tid){
		$parentArr = Array($tid);
		$parCnt = 0;
		$targetTid = $tid;
		do{
			$sqlParents = "SELECT IFNULL(ts.parenttid,0) AS parenttid FROM taxstatus ts WHERE (ts.tid = ".$targetTid.')';
			//echo "<div>".$sqlParents."</div>";
			$resultParent = $this->conn->query($sqlParents);
			if($rowParent = $resultParent->fetch_object()){
				$parentTid = $rowParent->parenttid;
				if($parentTid) {
					$parentArr[$parentTid] = $parentTid;
				}
			}
			else{
				break;
			}
			$resultParent->close();
			$parCnt++;
			if($targetTid == $parentTid) break;
			$targetTid = $parentTid;
		}while($targetTid && $parCnt < 16);
		
		return implode(",",array_reverse($parentArr));
	}

	public function getCollectionName(){
		$retStr;
		$sql = 'SELECT institutioncode, collectioncode, collectionname '.
			'FROM omcollections WHERE (collid = '.$this->collId.') ';
		if($rs = $this->conn->query($sql)){
			if($row = $rs->fetch_object()){
				$retStr = $row->collectionname;
				if($row->institutioncode) $retStr .= ' ('.$row->institutioncode.($row->collectioncode?':'.$row->collectioncode:'').')';
			}
			$rs->close();
		}
		return $retStr;
	}
	
	public function getTaxaList($index = 0){
		$retArr = array();
		$sql = 'SELECT sciname '.
			'FROM omoccurrences '.
			'WHERE (collid = '.$this->collId.') AND tidinterpreted IS NULL '.
			'ORDER BY sciname '.
			'LIMIT '.$index.',500 ';
		if($rs = $this->conn->query($sql)){
			if($row = $rs->fetch_object()){
				$retArr[] = $row->sciname;
			}
			$rs->close();
		}
		return $retArr;
	}
	
	public function analyzeTaxa($startIndex = 0, $limit = 10){
		$retArr = array();
		$sql = 'SELECT sciname '.
			'FROM omoccurrences '.
			'WHERE (collid = '.$this->collId.') AND tidinterpreted IS NULL '.
			'ORDER BY sciname '.
			'LIMIT '.$index.','.$limit;
		if($rs = $this->conn->query($sql)){
			if($row = $rs->fetch_object()){
				$sn = $row->sciname;
				$sxArr[$sn] = $sn;
				//Check name through Catalog of Life
				
				//Check for near match using SoundEx
				$sxArr = $this->getSoundexMatch($sn);
				if($sxArr) $retArr[$sn]['soundex'] = $sxArr;
				
			}
			$rs->close();
		}

		return $retArr;
	}

	public function getSoundexMatch($taxonStr){
		$retArr = array();
		if($taxonStr){
			$sql = 'SELECT tid, sciname FROM taxa WHERE SOUNDEX(sciname) = SOUNDEX("'.$taxonStr.'")';
			if($rs = $this->conn->query($sql)){
				while($row = $rs->fetch_object()){
					$retArr[$row->tid] = $row->sciname;
				}
				$rs->close();
			}
		}
		return $retArr;
	}

	public function getTaxaCount(){
		$retStr = '';
		$sql = 'SELECT count(DISTINCT sciname) AS taxacnt '.
			'FROM omoccurrences '.
			'WHERE (collid = '.$this->collId.') AND tidinterpreted IS NULL ';
		//echo $sql;
		if($rs = $this->conn->query($sql)){
			if($row = $rs->fetch_object()){
				$retStr = $row->taxacnt;
			}
			$rs->close();
		}
		return $retStr;
	}
	
	public function setCollId($id){
		if(is_numeric($id)){
			$this->collId = $id;
		}
	}
}
?>