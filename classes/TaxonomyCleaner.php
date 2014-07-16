<?php
include_once($serverRoot.'/config/dbconnection.php');
  
class TaxonomyCleaner extends Manager{

	private $taxAuthId = 1;
	private $testValidity = 1;
	private $testTaxonomy = 1;
	private $checkAuthor = 1;
	private $verificationMode = 0;		//0 = default to internal taxonomy, 1 = adopt target taxonomy
	
	public function __construct(){
		parent::__construct(null,'write');
		set_time_limit(500);
		$logFile = $serverRoot.(substr($serverRoot,-1)=='/'?'':'/')."temp/logs/taxonomyVerification_".date('Y-m-d').".log";
		$this->setLogFH($logFile);
		$this->logOrEcho("Taxa Verification process starts (".date('Y-m-d h:i:s A').")");
		$this->logOrEcho("-----------------------------------------------------\n");
	}

	function __destruct(){
		parent::__destruct();
	}

	public function getVerificationCounts(){
		$retArr;
		$sql = 'SELECT IFNULL(t.verificationStatus,0) as verificationStatus, COUNT(t.tid) AS cnt '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE ts.taxauthid = '.$this->taxAuthId.' AND (t.verificationStatus IS NULL OR t.verificationStatus = 0) '.
			'GROUP BY t.verificationStatus';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->verificationStatus] = $r->cnt;
			}
			$rs->close();
		}
		ksort($retArr);
		return $retArr;
	}

	public function verifyTaxa($verSource){
		//Check accepted taxa first
		$this->logOrEcho("Starting accepted taxa verification");
		$sql = 'SELECT t.sciname, t.tid, t.author, ts.tidaccepted '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (ts.tid = ts.tidaccepted) '.
			'AND (t.verificationStatus IS NULL OR t.verificationStatus = 0 OR t.verificationStatus = 2 OR t.verificationStatus = 3)'; 
		$sql .= 'LIMIT 1';
		//echo '<div>'.$sql.'</div>';
		if($rs = $this->conn->query($sql)){
			while($accArr = $rs->fetch_assoc()){
				$externalTaxonObj = array();
				if($verSource == 'col') $externalTaxonObj = $this->getTaxonObjSpecies2000($accArr['sciname']);
				if($externalTaxonObj){
					$this->verifyTaxonObj($externalTaxonObj,$accArr,$accArr['tid']);
				}
				else{
					$this->logOrEcho('Taxon not found', 1);
				}
			}
			$rs->close();
		}
		else{
			$this->logOrEcho('ERROR: unable query accepted taxa',1);
			$this->logOrEcho($sql);
		}
		$this->logOrEcho("Finished accepted taxa verification");
		
		//Check remaining taxa 
		$this->logOrEcho("Starting remaining taxa verification");
		$sql = 'SELECT t.sciname, t.tid, t.author, ts.tidaccepted FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE (ts.taxauthid = '.$this->taxAuthId.') '. 
			'AND (t.verificationStatus IS NULL OR t.verificationStatus = 0 OR t.verificationStatus = 2 OR t.verificationStatus = 3)'; 
		$sql .= 'LIMIT 1';
		//echo '<div>'.$sql.'</div>';
		if($rs = $this->conn->query($sql)){
			while($taxonArr = $rs->fetch_assoc()){
				$externalTaxonObj = array();
				if($verSource == 'col') $externalTaxonObj = $this->getTaxonObjSpecies2000($taxonArr['sciname']);
				if($externalTaxonObj){
					$this->verifyTaxonObj($externalTaxonObj,$taxonArr,$taxonArr['tidaccepted']);
				}
				else{
					$this->logOrEcho('Taxon not found', 1);
				}
			}
			$rs->close();
		}
		else{
			$this->logOrEcho('ERROR: unable query unaccepted taxa',1);
			$this->logOrEcho($sql);
		}
		$this->logOrEcho("Finishing remaining taxa verification");
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
			//Prepare taxon for loading
			$parsedArr = $this->parseSciName($testObj['name']);
			if(!array_key_exists('rank',$newTaxon)){
				//Grab taxon object from EOL or Species2000
				
				//Parent is also needed
			}
			$this->loadNewTaxon($parsedArr);
		}
		return $retTid;
	}

	//Taxonomy API methods 
	private function getTaxonObjEOL($sciName, $resultIndex = 0){
		$taxonArr = Array();
		//Ping EOL
		$pingUrl = 'http://eol.org/api/ping/1.0.json';
		if($fh = fopen($pingUrl, 'r')){
			$content = "";
			while($line = fread($fh, 1024)){
				$content .= trim($line);
			}
			fclose($fh);
			//Ping result
			$pingArr = json_decode($content);
			if(isset($pingArr['response']['message']) && $pingArr['response']['message'] == 'Success'){
				//Get ID
				$idUrl = 'http://eol.org/api/search/1.0.json?q='.str_replace(" ","%20",$sciName).'&page=1&exact=true';
				if($fh = fopen($idUrl, 'r')){
					$content = "";
					while($line = fread($fh, 1024)){
						$content .= trim($line);
					}
					fclose($fh);
					//Process return
					$idArr = json_decode($content);
					if($idArr['totalResults']){
						$idList = $idArr['results'];
						$firstElem = array_shift($idList);
						$id = $idList[0]['id'];
						if($id){
							//Get taxonomy
							$taxonUrl = 'http://eol.org/api/hierarchy_entries/1.0/'.$id.'.json?common_names=true&synonyms=true';
							if($fh = fopen($taxonUrl, 'r')){
								$content = "";
								while($line = fread($fh, 1024)){
									$content .= trim($line);
								}
								fclose($fh);
								
								//Process return
								$eolArr = json_decode($content);
								$scientificName = $eolArr['scientificName'];
								$nameAccordingTo = $eolArr['nameAccordingTo'];
								//Process EOL array
								$taxonArr = array('sciname' => $sciName);
								//Set rankID
								$sqlRank = 'SELECT rankid FROM taxonunits WHERE rankname = "'.$eolArr['taxonRank'].'"';
								$rsRank = $this->conn->query($sqlRank);
								if($rRank = $rsRank->fetch_object()){
									$taxonArr['rankid'] = $rRank->rankid;
								}
								else{
									$this->logOrEcho("Unable to determine rankid for: ".$eolArr['taxonRank']);
									$taxonArr['rankid'] = 0;
								}
								$rsRank->free();
								//Parse scientific name
								$taxonArr = $this->parseSciName($scientificName,$taxonArr['rankid']);
								
								//Add vernaculars
								$vernacularNames = $eolArr['vernacularNames'];
								foreach($vernacularNames as $vernArr){
									if($vernArr['language'] == 'en') $taxonArr['author'] = $vernArr['vernacularName']; 
								}
								//Process ancestors
								$parentID = $eolArr['parentNameUsageID'];
								$ancestors = $eolArr['ancestors'];
								foreach($ancestors as $ancKey => $ancArr){
									$taxonArr['parents'][$ancArr['parentNameUsageID']][$ancKey] = $ancArr['taxonID'];
								}
								//Add synonyms
								//$synonyms = $eolArr['synonyms'];
								//Dont' deal with synonyms, as of yet
							}
							//Add taxon to table
							if($taxonArr) $this->loadNewTaxon($taxonArr);
						}
						else{
							$this->logOrEcho("Unable to get taxon object for: ".$sciName);
						}
					}
				}
				else{
					$this->logOrEcho("Unable to get ID for: ".$sciName);
				}
			}
			else{
				$this->logOrEcho("EOL web service not available");
			}
		}
		return $taxonArr;
	}

	private function getTaxonObjSpecies2000($sciName, $resultIndex = 0){
		$resultArr = Array();
		$urlTemplate = "http://www.catalogueoflife.org/annual-checklist/webservice?format=php&response=full&name=";
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

	//Database functions
	private function loadNewTaxon($newTaxon,$anchorTid = 0){
		$status = false;
		if(!array_key_exists('rank',$newTaxon) || !$newTaxon['rankid']){
			$this->errorMessage = 'ERROR loading taxon: rankid unknown ('.$newTaxon['sciname'].')';
			return false;
		}
		if(!array_key_exists('parents',$newTaxon) || !$newTaxon['parents']){
			$this->errorMessage = 'ERROR loading taxon: parent unknown ('.$newTaxon['sciname'].')';
			return false;
		}
		
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
		//Load taxon
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
		return $tid;
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
			
			//$sqltd = 'UPDATE taxadescrblock tb LEFT JOIN (SELECT DISTINCT caption FROM taxadescrblock WHERE (tid = '.$tidNew.')) lj ON tb.caption = lj.caption '.
			//	'SET tid = '.$tidNew.' WHERE (tid = '.$tid.') AND lj.caption IS NULL';
			//$this->conn->query($sqltd);
	
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

	//Misc Functions 
	private function parseSciName($scientificName, $taxonRank = 0){
		//Converts scinetific name with author embedded into separate fields
		$retArr = array();
		$sciNameArr = explode(' ',$scientificName);
		if(count($sciNameArr)){
			$retArr = array('unitname1'=>'','unitname2'=>'','unitind3'=>'','unitname3'=>'');
			if(strtolower($sciNameArr[0]) == 'x'){
				//Genus level hybrid
				$retArr['unitind1'] = array_shift($sciNameArr);
			}
			//Genus or base name for higher ranked taxon
			$retArr['unitname1'] = ucfirst(strtolower(array_shift($sciNameArr)));
			if(count($sciNameArr)){
				if(!$taxonRank || $taxonRank >= 220){
					if(strtolower($sciNameArr[0]) == 'x'){
						//Species level hybrid
						$retArr['unitind2'] = array_shift($sciNameArr);
						$retArr['unitname2'] = strtolower(array_shift($sciNameArr));
					}
					elseif((strpos($sciNameArr[0],'.') !== false) || (strpos($sciNameArr[0],'(') !== false)){
						//It is assumed that Author has been reached, thus stop process
						$retArr['author'] = implode(' ',$sciNameArr);
						if(!$taxonRank){
							if(strpos($scientificName,'aceae') || strpos($scientificName,'aceae')) $retArr['rankid'] = 140;
							else $retArr['rankid'] = 180;
						}
						return $retArr;
					}
					else{
						//Specific Epithet
						$retArr['unitname2'] = strtolower(array_shift($sciNameArr));
					}
					//Process infraspecific and author strings
					if($sciNameArr){
						//Assume rest is author; if that is not true, author value will be replace in following loop
						$retArr['author'] = implode(' ',$sciNameArr);
						if(preg_match('\s{1}/ssp\.\s{1}|\s{1}/subsp\.\s{1}|\s{1}/var\.\s{1}|\s{1}/f\.\s{1}|\s{1}/fo\.\s{1}|\s{1}/forma\.\s{1}/',$scientificName)){
							//Taxon designator is present
							//cycles through the final terms to extract the last infraspecific data
							while($sciStr = array_shift($sciNameArr)){
								if($sciStr == 'f.' || $sciStr == 'fo.' || $sciStr == 'forma'){
									if($sciNameArr){
										$retArr['unitind3'] = 'f.';
										$retArr['unitname3'] = array_shift($sciNameArr);
										$retArr['author'] = implode(' ',$sciNameArr);
										if(!$taxonRank) $retArr['rankid'] = 260;
									}
								}
								elseif($sciStr == 'var.'){
									if($sciNameArr){
										$retArr['unitind3'] = 'var.';
										$retArr['unitname3'] = array_shift($sciNameArr);
										$retArr['author'] = implode(' ',$sciNameArr);
										if(!$taxonRank) $retArr['rankid'] = 240;
									}
								}
								elseif($sciStr == 'ssp.' || $sciStr == 'subsp.'){
									if($sciNameArr){
										$retArr['unitind3'] = 'ssp.';
										$retArr['unitname3'] = array_shift($sciNameArr);
										$retArr['author'] = implode(' ',$sciNameArr);
										if(!$taxonRank) $retArr['rankid'] = 230;
									}
								}
							}
						}
						elseif($taxonRank > 220){
							//Taxon designor is not present, but is an infraspecific taxon; probably in animalia kingdom  
							$retArr['unitname3'] = array_shift($sciNameArr);
							$retArr['author'] = implode(' ',$sciNameArr);
							if(!$taxonRank) $retArr['rankid'] = 230;
						}
					}
					if(!$taxonRank) $retArr['rankid'] = 220;
				}
			}
		}
		return $retArr;
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

	//Setters and getters
	public function setTaxAuthId($id){
		if(is_numeric($id)) $this->taxAuthId = $id;
	}
}
?>