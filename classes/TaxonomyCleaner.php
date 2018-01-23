<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/Manager.php');
include_once($SERVER_ROOT.'/classes/TaxonomyUtilities.php');
include_once($SERVER_ROOT.'/classes/TaxonomyHarvester.php');

class TaxonomyCleaner extends Manager{

	private $collid;
	private $taxAuthId = 1;
	private $autoClean = 0;
	private $testValidity = 1;
	private $testTaxonomy = 1;
	private $checkAuthor = 1;
	private $verificationMode = 0;		//0 = default to internal taxonomy, 1 = adopt target taxonomy

	public function __construct(){
		parent::__construct(null,'write');
	}

	function __destruct(){
		parent::__destruct();
	}

	public function initiateLog(){
		$logFile = '../../temp/logs/taxonomyVerification_'.date('Y-m-d').'.log';
		$this->setLogFH($logFile);
		$this->logOrEcho("Taxa Verification process starts (".date('Y-m-d h:i:s A').")");
		$this->logOrEcho("-----------------------------------------------------\n");
	}

	//Occurrence taxon name cleaning functions
	public function getBadTaxaCount(){
		$retCnt = 0;
		if($this->collid){
			$sql = 'SELECT COUNT(DISTINCT sciname) AS taxacnt '.$this->getSqlFragment();
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

	public function getBadSpecimenCount(){
		$retCnt = 0;
		if($this->collid){
			$sql = 'SELECT COUNT(*) AS cnt '.$this->getSqlFragment();
			//echo $sql;
			if($rs = $this->conn->query($sql)){
				if($row = $rs->fetch_object()){
					$retCnt = $row->cnt;
				}
				$rs->free();
			}
		}
		return $retCnt;
	}

	public function analyzeTaxa($taxResource, $startIndex, $limit = 50){
		set_time_limit(1800);
		$endIndex = 0;
		$this->logOrEcho("Starting taxa check ");
		$sql = 'SELECT DISTINCT sciname, family, count(*) as cnt '.$this->getSqlFragment();
		if($startIndex) $sql .= 'AND (sciname > "'.$this->cleanInStr($startIndex).'") ';
		$sql .= 'GROUP BY sciname, family ORDER BY sciname LIMIT '.$limit;
		//echo $sql; exit;
		if($rs = $this->conn->query($sql)){
			//Check name through taxonomic resources
			$taxonHarvester = new  TaxonomyHarvester();
			$taxonHarvester->setTaxonomicResources($taxResource);
			$taxonHarvester->setVerboseMode(2);
			$this->setVerboseMode(2);
			$taxaAdded = false;
			$taxaCnt = 1;
			$itemCnt = 0;
			while($r = $rs->fetch_object()){
				$editLink = '[<span onclick="openPopup(\''.$GLOBALS['CLIENT_ROOT'].
					'/collections/editor/occurrenceeditor.php?q_catalognumber=&occindex=0&q_customfield1=sciname&q_customtype1=EQUALS&q_customvalue1='.urlencode($r->sciname).'&collid='.
					$this->collid.'\')" style="cursor:pointer">'.$r->cnt.' specimens <img src="../../images/edit.png" style="width:12px;" /></span>]';
				$this->logOrEcho('Resolving #'.$taxaCnt.': <b><i>'.$r->sciname.'</i></b>'.($r->family?' ('.$r->family.')':'').'</b> '.$editLink);
				if($r->family) $taxonHarvester->setDefaultFamily($r->family);
				$sciname = $r->sciname;
				$tid = 0;
				$manualCheck = true;
				$taxonArr = TaxonomyUtilities::parseScientificName($r->sciname,$this->conn);
				if($taxonArr && $taxonArr['sciname']){
					$sciname= $taxonArr['sciname'];
					if($sciname != $r->sciname){
						$this->logOrEcho('Interpreted base name: <b>'.$sciname.'</b>',1);
					}
					$tid = $taxonHarvester->getTid($taxonArr);
					if($tid && $this->autoClean){
						$this->remapOccurrenceTaxon($this->collid, $r->sciname, $tid, $taxonArr['sciname'], $taxonArr['author'], (isset($taxonArr['identificationqualifier'])?$taxonArr['identificationqualifier']:''));
						$this->logOrEcho('Taxon remapped to <b>'.$sciname.'</b>',1);
						$manualCheck = false;
					}
				}
				if(!$tid){
					if($taxonHarvester->processSciname($sciname)){
						$taxaAdded= true;
						if($taxonHarvester->isFullyResolved()){
							$manualCheck = false;
						}
						else{
							$this->logOrEcho('Taxon not fully resolved...',1);
						}
					}
				}
				if($manualCheck){
					//Check for near match using SoundEx
					$this->logOrEcho('Checking close matches in thesaurus...',1);
					if($matchArr = $taxonHarvester->getCloseMatch($sciname)){
						$strTestArr = array();
						for($x=1; $x <= 3; $x++){
							if(isset($taxonArr['unitname'.$x]) && $taxonArr['unitname'.$x]) $strTestArr[] = $taxonArr['unitname'.$x];
						}
						foreach($matchArr as $tid => $scinameMatch){
							$snTokens = explode(' ',$scinameMatch);
							foreach($snTokens as $k => $v){
								if(in_array($v, $strTestArr)) $snTokens[$k] = '<b>'.$v.'</b>';
							}
							$authorOut = (isset($taxonArr['author'])?str_replace("'", '', $taxonArr['author']):'');
							$idQual = (isset($taxonArr['identificationqualifier'])?str_replace("'", '', $taxonArr['identificationqualifier']):'');
							$echoStr = '<i>'.implode(' ',$snTokens).'</i> =&gt; <span class="hideOnLoad">wait for page to finish loading...</span><span class="displayOnLoad" style="display:none">'.
								'<a href="#" onclick="return remappTaxon(\''.$r->sciname.'\','.$tid.',\''.$scinameMatch.'\',\''.$authorOut.'\',\''.$idQual.'\','.$itemCnt.')" style="color:blue"> remap to this taxon</a>'.
								'<span id="remapSpan-'.$itemCnt.'"></span></span>';
							$this->logOrEcho($echoStr,2);
							$itemCnt++;
						}
					}
					else{
						$this->logOrEcho('No close matches found',2);
					}
				}
				$taxaCnt++;
				$scinameTokens = explode(' ',$r->sciname);
				$endIndex = array_shift($scinameTokens);
				flush();
				ob_flush();
			}
			$rs->free();
			if($taxaAdded) $this->indexOccurrenceTaxa();
		}

		$this->logOrEcho("<b>Done with taxa check </b>");
		return $endIndex;
	}

	private function getSqlFragment(){
		$sql = 'FROM omoccurrences WHERE (collid IN('.$this->collid.')) AND (tidinterpreted IS NULL) AND (sciname IS NOT NULL) AND (sciname NOT LIKE "% x %") AND (sciname NOT LIKE "% × %") ';
		return $sql;
	}

	public function deepIndexTaxa(){
		$this->setVerboseMode(2);
		$this->logOrEcho('Cleaning leading and trialing spaces...');
		$sql = 'UPDATE omoccurrences '.
			'SET sciname = trim(sciname) '.
			'WHERE (collid IN('.$this->collid.')) AND (tidinterpreted is NULL) AND (sciname LIKE " %" OR sciname LIKE "% ")';
		if($this->conn->query($sql)){
			$this->logOrEcho($this->conn->affected_rows.' occurrence records cleaned',1);
		}
		flush();
		ob_flush();

		$this->logOrEcho('Cleaning double spaces inbedded within name...');
		$sql = 'UPDATE omoccurrences '.
			'SET sciname = replace(sciname, "  ", " ") '.
			'WHERE (collid IN('.$this->collid.')) AND (tidinterpreted is NULL) AND (sciname LIKE "%  %") ';
		if($this->conn->query($sql)){
			$this->logOrEcho($this->conn->affected_rows.' occurrence records cleaned',1);
		}
		flush();
		ob_flush();

		$this->indexOccurrenceTaxa();

		$this->logOrEcho('Indexing names based on mathcing trinomials without taxonRank designation...');
		$triCnt = 0;
		$sql = 'SELECT DISTINCT o.sciname, t.tid, t.sciname AS newsciname '.
			'FROM omoccurrences o INNER JOIN taxa t ON o.sciname = CONCAT_WS(" ",t.unitname1,t.unitname2,t.unitname3) '.
			'WHERE (o.collid IN('.$this->collid.')) AND (t.rankid IN(230,240)) AND (o.sciname LIKE "% % %") AND (o.tidinterpreted IS NULL) '.
			'ORDER BY t.rankid';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$triCnt += $this->remapOccurrenceTaxon($this->collid, $r->sciname, $r->tid, $r->newsciname);
		}
		$rs->free();
		$this->logOrEcho($triCnt.' occurrence records remapped',1);
		flush();
		ob_flush();

		$this->logOrEcho('Indexing names ending in &quot;sp.&quot;...');
		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON SUBSTRING(o.sciname,1, CHAR_LENGTH(o.sciname) - 4) = t.sciname '.
			'SET o.tidinterpreted = t.tid '.
			'WHERE (o.collid IN('.$this->collid.')) AND (o.sciname LIKE "% sp.") AND (o.tidinterpreted IS NULL)';
		if($this->conn->query($sql)){
			$this->logOrEcho($this->conn->affected_rows.' occurrence records mapped',1);
		}
		flush();
		ob_flush();

		$this->logOrEcho('Indexing names containing &quot;spp.&quot;...');
		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON REPLACE(o.sciname," spp.","") = t.sciname '.
			'SET o.tidinterpreted = t.tid '.
			'WHERE (o.collid IN('.$this->collid.')) AND (o.sciname LIKE "% spp.%") AND (o.tidinterpreted IS NULL)';
		if($this->conn->query($sql)){
			$this->logOrEcho($this->conn->affected_rows.' occurrence records mapped',1);
		}
		flush();
		ob_flush();

		$this->logOrEcho('Indexing names containing &quot;cf.&quot;...');
		$cnt = 0;
		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON REPLACE(o.sciname," cf. "," ") = t.sciname '.
			'SET o.tidinterpreted = t.tid '.
			'WHERE (o.collid IN('.$this->collid.')) AND (o.sciname LIKE "% cf. %") AND (o.tidinterpreted IS NULL)';
		if($this->conn->query($sql)){
			$cnt = $this->conn->affected_rows;
		}
		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON REPLACE(o.sciname," cf "," ") = t.sciname '.
			'SET o.tidinterpreted = t.tid '.
			'WHERE (o.collid IN('.$this->collid.')) AND (o.sciname LIKE "% cf %") AND (o.tidinterpreted IS NULL)';
		if($this->conn->query($sql)){
			$cnt += $this->conn->affected_rows;
			$this->logOrEcho($cnt.' occurrence records mapped',1);
		}
		flush();
		ob_flush();

		$this->logOrEcho('Indexing names containing &quot;aff.&quot;...');
		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON REPLACE(o.sciname," aff. "," ") = t.sciname '.
			'SET o.tidinterpreted = t.tid '.
			'WHERE (o.collid IN('.$this->collid.')) AND (o.sciname LIKE "% aff. %") AND (o.tidinterpreted IS NULL)';
		if($this->conn->query($sql)){
			$this->logOrEcho($this->conn->affected_rows.' occurrence records mapped',1);
		}
		flush();
		ob_flush();

		$this->logOrEcho('Indexing names containing &quot;group&quot; statements...');
		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON REPLACE(o.sciname," group"," ") = t.sciname '.
			'SET o.tidinterpreted = t.tid '.
			'WHERE (o.collid IN('.$this->collid.')) AND (o.sciname LIKE "% group%") AND (o.tidinterpreted IS NULL)';
		if($this->conn->query($sql)){
			$this->logOrEcho($this->conn->affected_rows.' occurrence records mapped',1);
		}
		flush();
		ob_flush();
	}

	private function indexOccurrenceTaxa(){
		$this->logOrEcho('Indexing names based on exact matches...');
		$sql = 'UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname '.
			'SET o.tidinterpreted = t.tid '.
			'WHERE (o.collid IN('.$this->collid.')) AND (o.tidinterpreted IS NULL)';
		if($this->conn->query($sql)){
			$this->logOrEcho($this->conn->affected_rows.' occurrence records mapped',1);
		}
		else{
			$this->logOrEcho('ERROR linking new data to occurrences: '.$this->conn->error);
		}
		flush();
		ob_flush();

		$this->logOrEcho('Populating null family tags...');
		$sql = 'UPDATE taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
			'INNER JOIN taxa t2 ON e.parenttid = t2.tid '.
			'SET t.kingdomname = t2.sciname '.
			'WHERE e.taxauthid = 1 AND t2.rankid = 10 AND (t.kingdomName IS NULL OR t.kingdomname != t2.sciname)';
		if($this->conn->query($sql)){
			$this->logOrEcho($this->conn->affected_rows.' taxon records updated',1);
		}
		else{
			$this->logOrEcho('ERROR family tags: '.$this->conn->error);
		}
		flush();
		ob_flush();

		$this->logOrEcho('Populating null kingdom name tags...');
		$sql = 'UPDATE taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
			'INNER JOIN taxa t2 ON e.parenttid = t2.tid '.
			'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'SET ts.family = t2.sciname '.
			'WHERE e.taxauthid = 1 AND t2.rankid = 140 AND (ts.family IS NULL OR ts.family != t2.sciname)';
		if($this->conn->query($sql)){
			$this->logOrEcho($this->conn->affected_rows.' taxon records updated',1);
		}
		else{
			$this->logOrEcho('ERROR updating kingdoms: '.$this->conn->error);
		}
		flush();
		ob_flush();
	}

	public function remapOccurrenceTaxon($collid, $oldSciname, $tid, $newSciname, $newAuthor = '', $idQualifier = ''){
		$affectedRows = 0;
		if(is_numeric($collid) && $oldSciname && is_numeric($tid) && $newSciname){
			//Temporary code needed for to test for new schema update
			$hasEditType = false;
			$rsTest = $this->conn->query('SHOW COLUMNS FROM omoccuredits WHERE field = "editType"');
			if($rsTest->num_rows) $hasEditType = true;
			$rsTest->free();

			//Add edits to edit versioning table
			$oldSciname = $this->cleanInStr($oldSciname);
			$newSciname = $this->cleanInStr($newSciname);
			if($newAuthor) $newAuthor= $this->cleanInStr($newAuthor);
			if($idQualifier) $idQualifier = $this->cleanInStr($idQualifier);
			$sqlWhere = 'WHERE (collid IN('.$collid.')) AND (sciname = "'.$oldSciname.'") AND (tidinterpreted IS NULL)';
			//Version edit in edits table
			$sql1 = 'INSERT INTO omoccuredits(occid, FieldName, FieldValueNew, FieldValueOld, uid, ReviewStatus, AppliedStatus'.($hasEditType?',editType ':'').') '.
				'SELECT occid, "sciname", "'.$newSciname.'", sciname, '.$GLOBALS['SYMB_UID'].', 1, 1'.($hasEditType?',1':'').' FROM omoccurrences '.$sqlWhere;
			if($this->conn->query($sql1)){
				if($newAuthor){
					$sql2 = 'INSERT INTO omoccuredits(occid, FieldName, FieldValueNew, FieldValueOld, uid, ReviewStatus, AppliedStatus'.($hasEditType?',editType ':'').') '.
						'SELECT occid, "scientificNameAuthorship" AS fieldname, "'.$newAuthor.'", IFNULL(scientificNameAuthorship,""), '.$GLOBALS['SYMB_UID'].', 1, 1 '.($hasEditType?',1 ':'').
						'FROM omoccurrences '.$sqlWhere;
					if(!$this->conn->query($sql2)){
						$this->logOrEcho('ERROR thrown versioning of remapping of occurrence taxon (author): '.$this->conn->error,1);
					}
				}
				if($idQualifier){
					$sql3 = 'INSERT INTO omoccuredits(occid, FieldName, FieldValueNew, FieldValueOld, uid, ReviewStatus, AppliedStatus'.($hasEditType?',editType ':'').') '.
						'SELECT occid, "identificationQualifier" AS fieldname, CONCAT_WS("; ",identificationQualifier,"'.$idQualifier.'") AS idqual, '.
						'IFNULL(identificationQualifier,""), '.$GLOBALS['SYMB_UID'].', 1, 1 '.($hasEditType?',1 ':'').
						'FROM omoccurrences '.$sqlWhere;
					if(!$this->conn->query($sql3)){
						$this->logOrEcho('ERROR thrown versioning of remapping of occurrence taxon (idQual): '.$this->conn->error,1);
					}
				}
				//Update occurrence table
				$sqlFinal = 'UPDATE omoccurrences '.
					'SET tidinterpreted = '.$tid.', sciname = "'.$newSciname.'" ';
				if($newAuthor){
					$sqlFinal .= ', scientificNameAuthorship = "'.$newAuthor.'" ';
				}
				if($idQualifier){
					$sqlFinal .= ', identificationQualifier = CONCAT_WS("; ",identificationQualifier,"'.$idQualifier.'") ';
				}
				$sqlFinal .= $sqlWhere;
				if($this->conn->query($sqlFinal)){
					$affectedRows = $this->conn->affected_rows;
				}
				else{
					$this->logOrEcho('ERROR thrown remapping occurrence taxon: '.$this->conn->error,1);
				}
			}
			else{
				$this->logOrEcho('ERROR thrown versioning of remapping of occurrence taxon (E1): '.$this->conn->error,1);
			}
		}
		return $affectedRows;
	}

	//Taxonomic thesaurus verifications
	public function getVerificationCounts(){
		$retArr;
		/*
		$sql = 'SELECT IFNULL(t.verificationStatus,0) as verificationStatus, COUNT(t.tid) AS cnt '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE ts.taxauthid = '.$this->taxAuthId.' AND (t.verificationStatus IS NULL OR t.verificationStatus = 0) '.
			'GROUP BY t.verificationStatus';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$retArr[$r->verificationStatus] = $r->cnt;
			}
			$rs->free();
		}
		ksort($retArr);
		*/
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
			$rs->free();
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
			$rs->free();
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
							$rs->free();
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
			$parsedArr = TaxonomyUtilities::parseScientificName($testObj['name'],$this->conn);
			if(!array_key_exists('rank',$newTaxon)){
				//Grab taxon object from EOL or Species2000

				//Parent is also needed
			}
			$this->loadNewTaxon($parsedArr);
		}
		return $retTid;
	}

	//Database functions
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

	//Misc fucntions
	public function getCollMap(){
		$retArr = Array();
		$sql = 'SELECT collid, CONCAT_WS("-",institutioncode, collectioncode) AS code, collectionname, icon, colltype, managementtype FROM omcollections ';
		if($this->collid){
			$sql .= 'WHERE (collid IN('.$this->collid.')) ';
		}
		else{
			$sql .= 'ORDER BY collectionname, collectioncode ';
		}
		//echo $sql;
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retArr[$r->collid]['code'] = $r->code;
			$retArr[$r->collid]['collectionname'] = $r->collectionname;
			$retArr[$r->collid]['icon'] = $r->icon;
			$retArr[$r->collid]['colltype'] = $r->colltype;
			$retArr[$r->collid]['managementtype'] = $r->managementtype;
		}
		$rs->free();
		return $retArr;
	}

	public function getTaxonomicResourceList(){
		$taArr = array('col'=>'Catalog of Life','worms'=>'World Register of Marine Species','tropicos'=>'TROPICOS','eol'=>'Encyclopedia of Life');
		if(!isset($GLOBALS['TAXONOMIC_AUTHORITIES'])) return array('col'=>'Catalog of Life','worms'=>'World Register of Marine Species');
		return array_intersect_key($taArr,array_change_key_case($GLOBALS['TAXONOMIC_AUTHORITIES']));
	}

	//Setters and getters
	public function setTaxAuthId($id){
		if(is_numeric($id)) $this->taxAuthId = $id;
	}

	public function setAutoClean($v){
		if(is_numeric($v)) $this->autoClean = $v;
	}

	public function setCollId($collid){
		if(preg_match('/^[\d,]+$/',$collid)){
			$this->collid = $collid;
		}
	}
}
?>