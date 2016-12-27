<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/Manager.php');
include_once($SERVER_ROOT.'/classes/TaxonomyUtilities.php');
include_once($SERVER_ROOT.'/classes/EOLUtilities.php');

class TaxonomyHarvester extends Manager{

	private $taxAuthId = 1;
	private $kingdomName;
	private $activeThesResources = 'eol';
	
	function __construct() {
		parent::__construct(null,'write');
	}
	
	function __destruct(){
		parent::__destruct();
	}

	public function initSourceOrder(){
		if(isset($GLOBALS['taxonThesauri']) && $GLOBALS['taxonThesauri']){
			$this->activeThesResources = explode(',',$GLOBALS['taxonThesauri']);
		}
	}

	public function addSciname($term, $family = ''){
		$newTid = 0;
		$term = trim($term);
		if($term){
			$taxonArr = Array();
			if(!$this->activeThesResources){
				$this->errorMessage = 'ERROR: Taxonomic resources not activated ';
				return false;
			}
			$resourceArr = explode(',',$this->activeThesResources);
			$attemptedResources = '';
			foreach($resourceArr as $resStr){
				if($resStr == 'eol'){
					$this->logOrEcho('Checking EOL...',1);
					$taxonArr = $this->getEolTaxonArr($term);
					$attemptedResources .= ', eol';
					if($this->validateTaxonArr($taxonArr)){
						break;
					}
					else{
						$this->logOrEcho('Taxon not found',1);
						unset($taxonArr);
						$taxonArr = array();
					}
				}
				elseif($resStr == 'col'){
					$taxonArr = $this->getColTaxonArr($term);
					$attemptedResources .= ', col';
				}
				elseif($resStr == 'trop'){
					$taxonArr = $this->getTropicosTaxonArr($term);
					$attemptedResources .= ', trop';
				}
			}
			if($taxonArr){
				$this->logOrEcho('Taxon found',1);
				if(!$this->kingdomName) $this->setDefaultKingdom();
				if(!array_key_exists('parent',$taxonArr) || !$taxonArr['parent']){
					$this->determineParents($taxonArr, $family);
				}
				$newTid = $this->loadNewTaxon($taxonArr);
				if($newTid){
					//Add Synonyms
					if(isset($taxonArr['syns'])){
						foreach($taxonArr['syns'] as $k => $synArr){
							if(strpos($synArr['scientificName'],$term) !== false){
								$taxonArr2 = TaxonomyUtilities::parseScientificName($synArr['scientificName'],$this->conn);
								if($taxonArr2){
									if(isset($taxonArr['source']) && $taxonArr['source']) $taxonArr2['source'] = $taxonArr['source'];
									if(isset($taxonArr['synreason']) && $taxonArr['synreason']) $taxonArr2['synreason'] = $taxonArr['synreason'];
									if(!array_key_exists('parent',$taxonArr2) || !$taxonArr2['parent']){
										$this->determineParents($taxonArr2, $family);
									}
									$this->loadNewTaxon($taxonArr2,$newTid);
								}
								break;
							}
						}
					}				
					//Add common names
					if(isset($taxonArr['verns'])){
						foreach($taxonArr['verns'] as $k => $vernArr){
							$sqlVern = 'INSERT INTO(tid,vernacularname,language) '.
								'VALUES('.$newTid.',"'.$vernArr['vernacularName'].'","'.$vernArr['language'].'")';
							if(!$this->conn->query($sqlVern)){
								$this->errorMessage = 'ERROR loading vernacular '.$taxonArr['sciname'].': '.$this->conn->error;
							}
						}
					}
					$this->logOrEcho('Taxon added to taxonomic thesaurus (#'.$newTid.')',1);
				}
				else{
					if($this->errorMessage) $this->logOrEcho($this->errorMessage,1);
					exit;
				}
			}
			else{
				$this->errorMessage = 'ERROR: unable to obtain taxon object from: '.trim($attemptedResources,' ,');
				return false;
			}
		} 
		return $newTid;
	} 
	
	private function validateTaxonArr($taxonArr){
		if(!isset($taxonArr['sciname']) || !$taxonArr['sciname']){
			//$this->errorMessage = 'ERROR: sciname not defined';
			return false;
		}
		if(!isset($taxonArr['unitname1']) || !$taxonArr['unitname1']){
			//$this->errorMessage = 'ERROR: unitname1 not defined';
			return false;
		}
		if(!isset($taxonArr['rankid']) || !$taxonArr['rankid']){
			//$this->errorMessage = 'ERROR loading '.$taxonArr['sciname'].', rankid not defined';
			return false;
		}
		return true;
	}

	/*
	 * INPUT: scientific name
	 *   Example: 'Pinus ponderosa var. arizonica'
	 * OUTPUT: array representing taxon object 
	 *   Example: array('id' => '34554'
	 *   				'sciname' => 'Pinus arizonica', 
	 *   				'scientificName' => 'Pinus arizonica (Engelm.) Shaw', 
	 *        			'unitind1' => '', 'unitname1' => 'Pinus', 'unitind2' => '', 'unitname2' => 'arizonica', 'unitind3'=>'', 'unitname3'=>'',
	 *        			'author' => '(Engelm.) Shaw',
	 *        			'rankid' => '220',
	 *        			'taxonRank' => 'Species',
	 *  				'verns' => array(array('vernacularName'=>'Arizona Ponderosa Pine','language'=>'en'), array(etc...)),
	 *  				'syns' => array(array('scientificName'=>'Pinus ponderosa var. arizonica','reason'=>'synonym'), array(etc...)),
	 *  				'parent' => array(	'id' => '43463',
	 *  									'sciname' => 'Pinus',
	 *  									'taxonRank' => 'Genus',
	 *  									'sourceURL' => 'http://eol.org/pages/1905/hierarchy_entries/43463/overview',
	 *  									,'parentID' => array( etc...)
	 *  							)
	 *        	   )
	 */
	private function getEolTaxonArr($term){
		//Returns content for accepted name
		$taxonArr = array();
		$eolManager = new EOLUtilities();
		if($eolManager->pingEOL()){
			$eolTaxonId = 0;
			if(is_numeric($term)) $eolTaxonId = $term;
			if(!$eolTaxonId){
				$searchRet = $eolManager->searchEOL($term);
				if(isset($searchRet['id'])){
					$searchSyns = ((strpos($searchRet['title'],$term) !== false)?false:true);
					$taxonArr = $eolManager->getPage($searchRet['id'],$searchSyns);
					if(isset($taxonArr['taxonConcepts'])){
						$eolTaxonId = key($taxonArr['taxonConcepts']);
					}
				}
				else{
					$this->errorMessage = 'ERROR getting EOL page ID (term: '.$term.')';
					return false;
				}
			}
			if($eolTaxonId){
				//$taxonArr = $eolManager->getHierarchyEntries($eolTaxonId);
			}
			if($taxonArr){
				if(!isset($taxonArr['rankid'])){
					$rankid = $this->getRankId($taxonArr['taxonRank']);
					if($rankid) $taxonArr['rankid'] = $rankid;
				}
				if(!isset($taxonArr['source'])) $taxonArr['source'] = 'EOL - '.date('Y-m-d G:i:s');
			}
		}
		else{
			$this->errorMessage = 'EOL web services are not available ';
			return false;
		}
		return $taxonArr;
	}

	public function getColTaxonArr($sciName, $resultIndex = 0){
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

	public function getTropicosTaxonArr($sciName){
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
	private function loadNewTaxon($taxonArr,$tidAccepted = 0){
		/*
		 * Default action: add accepted taxon, parents, synonyms, vernacular
		 * Required fields: sciname, parsed units, rankid, parentTaxon, acceptedTaxon
		 * Additional fields: author, family
		 * Note: Accepted taxon might already be in thesaurus if taxonomic resource returns search taxon as a synonym    
		 * Possible conflicts:
		 * 1) parent of accepted is already in thesaurus as not accepted: parent = 180 then change parent to accepted; for all others link to parent's accepted taxon  
		 * 2) family is not accepted: use accepted family 
		 * 3) accepted taxon is in thesaurus as accepted: link to accepted synonym as not accepted
		 * 4)   
		 * synonym taxon is accepted  
		 * 
		 * INPUT: $taxonArr
		 * OUPUT: $processedTaxonArr [ex: array('newtid' => '3424','taxonarr' => $taxonArr,'warnings' => array('WARNING: name is a homonym','NOTICE: author not returned'))]
		 * 
		 */
		if(!$this->kingdomName){
			//$this->errorMessage = 'ERROR loading '.$taxonArr['sciname'].', kingdomName is not set';
			return false; 
		}
		$newTid = 0;
		$parentTid = $this->getParentTid($taxonArr);
		if(!$parentTid){
			$this->errorMessage = 'ERROR loading '.$taxonArr['sciname'].', unable to get parentTid';
			return false;
		}
		//We now have everything, now let's load
		//Check to see sciname is in taxon table but not linked to current thesaurus
		$sql = 'SELECT tid FROM taxa WHERE sciname = "'.$taxonArr['sciname'].'"';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$newTid = $r->tid;
		}
		$rs->free();
		
		if(!$newTid){
			$sqlInsert = 'INSERT INTO taxa(sciname, unitind1, unitname1, unitind2, unitname2, unitind3, unitname3, author, rankid, source) '.
				'VALUES("'.$taxonArr['sciname'].'",'.
				(isset($taxonArr['unitind1']) && $taxonArr['unitind1']?'"'.$taxonArr['unitind1'].'"':'NULL').',"'.
				$taxonArr['unitname1'].'",'.
				(isset($taxonArr['unitind2']) && $taxonArr['unitind2']?'"'.$taxonArr['unitind2'].'"':'NULL').','.($taxonArr['unitname2']?'"'.$taxonArr['unitname2'].'"':'NULL').','.
				($taxonArr['unitind3']?'"'.$taxonArr['unitind3'].'"':'NULL').','.($taxonArr['unitname3']?'"'.$taxonArr['unitname3'].'"':'NULL').','.
				(isset($taxonArr['author']) && $taxonArr['author']?'"'.$taxonArr['author'].'"':'NULL').','.
				$taxonArr['rankid'].','.($taxonArr['source']?'"'.$taxonArr['source'].'"':'NULL').')';
			if($this->conn->query($sqlInsert)){
				$newTid = $this->conn->insert_id;
			}
			else{
				$this->errorMessage = 'ERROR inserting '.$taxonArr['sciname'].': '.$this->conn->error;
				$this->errorMessage .= 'SQL: '.$sqlInsert;
				return false;
			}
		}
		if($newTid){
			if(!$tidAccepted) $tidAccepted = $newTid;
			$sqlInsert2 = 'INSERT INTO taxstatus(tid,tidAccepted,taxAuthId,parentTid,UnacceptabilityReason) '.
				'VALUES('.$newTid.','.$tidAccepted.','.$this->taxAuthId.','.$parentTid.','.
				(isset($taxonArr['synreason']) && $taxonArr['synreason']?'"'.$taxonArr['synreason'].'"':'NULL').')';
			if($this->conn->query($sqlInsert2)){
				//Add hierarchy index
				$sqlHier = 'INSERT INTO taxaenumtree(tid,parenttid,taxauthid) '.
					'VALUES('.$newTid.','.$parentTid.','.$this->taxAuthId.')';
				if(!$this->conn->query($sqlHier)){
					$this->errorMessage = 'ERROR adding new tid to taxaenumtree (step 1): '.$this->conn->error;
				}
				$sqlHier2 = 'INSERT INTO taxaenumtree(tid,parenttid,taxauthid) '.
					'SELECT '.$newTid.' AS tid, parenttid, taxauthid FROM taxaenumtree WHERE tid = '.$parentTid;
				if(!$this->conn->query($sqlHier2)){
					$this->errorMessage = 'ERROR adding new tid to taxaenumtree (step 2): '.$this->conn->error;
				}
			}
		}
		else{
			$this->errorMessage = 'ERROR laoding taxon: newTid is null ';
			return false;
		}
		return $newTid;
	}

	private function getRankId($rankStr){
		$rankId = '';
		if($rankStr){
			$sqlRank = 'SELECT rankid FROM taxonunits WHERE rankname = "'.$rankStr.'"';
			$rsRank = $this->conn->query($sqlRank);
			$defaultRankId = 0;
			while($rRank = $rsRank->fetch_object()){
				$rankId = $rRank->rankid;
			}
			if(!$rankId && $defaultRankId){
				$rankId = $defaultRankId;
			}
			if(!$rankId){
				$this->warningArr[] = "Unable to determine rankid from: ".$rankStr;
			}
			$rsRank->free();
		}
		return $rankId;
	}

	private function setDefaultKingdom(){
		$sql = 'SELECT t.sciname, COUNT(e.tid) as cnt '. 
			'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.parenttid '.
			'WHERE t.rankid = 10 '.
			'GROUP BY t.sciname '.
			'ORDER BY cnt desc';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$this->kingdomName = $r->sciname;
		}
		$rs->free();
	}

	private function getParentTid($taxonArr){
		$parentTid = 0;
		if($taxonArr && isset($taxonArr['parent'])){
			$parentArr = $taxonArr['parent'];
			if($parentArr && $parentArr['sciname'] != $taxonArr['sciname']){
				$abort = true;
				if(isset($parentArr['tid']) && is_numeric($parentArr['tid'])){
					return $parentArr['tid'];
				}
				$sql = 'SELECT t.tid, t.rankid '.
					'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
					'WHERE (t.sciname = "'.$this->cleanInStr($parentArr['sciname']).'") AND (ts.taxauthid = '.$this->taxAuthId.')';
				$rs = $this->conn->query($sql);
				if($r = $rs->fetch_object()){
					$parentTid = $r->tid;
					if(isset($taxonArr['rankid']) && $r->rankid >= $taxonArr['rankid']){
						$parentTid = 0;
						$abort = true; 
					}
				}
				$rs->free();
				if(!$parentTid && !$abort){
					$this->logOrEcho('Adding parent ('.$parentArr['sciname'].')...',1);
					$parentTid = $this->addSciname($parentArr['sciname']);
				}
			}
		}
		return $parentTid;
	}

	private function determineParents(&$taxonArr, $family){
		$parArr = array();
		if($taxonArr['sciname']){
			$parsedArr = array();
			if(!isset($taxonArr['rankid']) || !$taxonArr['rankid']){
				$parsedArr = $this->parseSciName($taxonArr['sciname']);
				if($parsedArr['rankid']) $taxonArr['rankid'] = $parsedArr['rankid'];
			}
			if(!isset($taxonArr['unitname1']) || !$taxonArr['unitname1']){
				if(!$parsedArr) $parsedArr = $this->parseSciName($taxonArr['sciname']);
				if($parsedArr['unitname1']){
					$taxonArr['unitname1'] = $parsedArr['unitname1'];
					$taxonArr['unitname2'] = $parsedArr['unitname2'];
				}
			}
			if($taxonArr['rankid']){
				if(!$this->kingdomName) $this->setDefaultKingdom();
				if($this->kingdomName){
					$parArr = array(
						'sciname' => $this->kingdomName, 
						'taxonRank' => 'kingdom', 
						'rankid' => '10' 
					);
				}
				if($taxonArr['rankid'] > 140 && $family){
					$sqlFam = 'SELECT tid FROM taxa '.
						'WHERE (sciname = "'.$this->cleanInStr($family).'") AND (rankid = 140)';
					//echo $sqlFam;
					$rs = $this->conn->query($sqlFam);
					if($r = $rs->fetch_object()){
						$newParArr = array(
							'sciname' => $family,
							'tid' => $r->tid, 
							'taxonRank' => 'family', 
							'rankid' => '140',
							'parent' => $parArr
						);
						$parArr = $newParArr;
					}
					$rs->free();
				}
				if($taxonArr['rankid'] > 180){
					$newParArr = array(
						'sciname' => $taxonArr['unitname1'], 
						'taxonRank' => 'genus', 
						'rankid' => '180',
						'parent' => $parArr
					);
					$parArr = $newParArr;
				}
				if($taxonArr['rankid'] > 220){
					$newParArr = array(
						'sciname' => $taxonArr['unitname1'].' '.$taxonArr['unitname2'], 
						'taxonRank' => 'species', 
						'rankid' => '220',
						'parent' => $parArr
					);
					$parArr = $newParArr;
				}
			}
		}
		$taxonArr['parent'] = $parArr;
	}

	//Misc functions
	public function parseSciName($scientificName,$rankId = 0){
		//Converts scinetific name with author embedded into separate fields
		return TaxonomyUtilities::parseScientificName($scientificName,$this->conn,$rankId);
	}

	public function getSoundexMatch($taxonStr){
		$retArr = array();
		if($taxonStr){
			$sql = 'SELECT tid, sciname FROM taxa WHERE SOUNDEX(sciname) = SOUNDEX("'.$taxonStr.'")';
			//echo $sql;
			if($rs = $this->conn->query($sql)){
				while($row = $rs->fetch_object()){
					$retArr[$row->tid] = $row->sciname;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	public function getCloseMatchEpithet($taxonStr){
		$retArr = array();
		if($taxonStr){
			$firstInitial = substr($taxonStr,0,1);
			$secondWord = substr($taxonStr,strpos($taxonStr,' '));
			$sql = 'SELECT tid, sciname FROM taxa WHERE (sciname LIKE "'.$firstInitial.'%'.$secondWord.'")';
			//echo $sql;
			if($rs = $this->conn->query($sql)){
				while($row = $rs->fetch_object()){
					$retArr[$row->tid] = $row->sciname;
				}
				$rs->free();
			}
		}
		return $retArr;
	}

	//Setters and getters
	public function setTaxAuthId($id){
		if($id && is_numeric($id)){
			$this->taxAuthId = $id;
		}
	}

	public function setActiveThesResources($t){
		$this->activeThesResources = $t;
	}

	public function getErrorMessage(){
		return $this->errorMessage;
	}
}
?>