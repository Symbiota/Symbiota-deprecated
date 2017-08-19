<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/Manager.php');
include_once($SERVER_ROOT.'/classes/TaxonomyUtilities.php');
include_once($SERVER_ROOT.'/classes/EOLUtilities.php');

class TaxonomyHarvester extends Manager{

	private $taxonomicResources = array();
	private $activeTaxonomicAuthority;
	private $taxAuthId = 1;
	private $defaultFamily;
	private $kingdomName;
	private $kingdomTid;
	private $fullyResolved;

	function __construct() {
		parent::__construct(null,'write');
		if(isset($GLOBALS['TAXONOMIC_AUTHORITIES']) && $GLOBALS['TAXONOMIC_AUTHORITIES']){
			if(!is_array($GLOBALS['TAXONOMIC_AUTHORITIES'])){
				echo 'Taxonomic authority activation list (TAXONOMIC_AUTHORITIES) not configured correctly';
				exit;
			}
			foreach($GLOBALS['TAXONOMIC_AUTHORITIES'] as $name => $apiKey){
				$this->taxonomicResources[trim(strtolower($name))] = trim($apiKey);
			}
		}
	}

	function __destruct(){
		parent::__destruct();
	}

	public function processSciname($term){
		$term = trim($term);
		if($term){
			$this->fullyResolved = true;
			if(!$this->taxonomicResources){
				$this->logOrEcho('ERROR: Taxonomic resources not activated ',1);
				return false;
			}
			foreach($this->taxonomicResources as $authCode=> $apiKey){
				$this->activeTaxonomicAuthority = $authCode;
				$newTid = $this->addSciname($term);
				if($newTid) return $newTid;
			}
		}
	}

	private function addSciname($term){
		if(!$term) return false;
		$newTid = 0;
		if($this->activeTaxonomicAuthority == 'col'){
			$this->logOrEcho('Checking <b>Catalog of Life</b>...',1);
			$newTid= $this->addColTaxon($term);
		}
		elseif($this->activeTaxonomicAuthority == 'worms'){
			$this->logOrEcho('Checking <b>WoRMS</b>...',1);
			$newTid= $this->addWormsTaxon($term);
		}
		elseif($this->activeTaxonomicAuthority == 'tropicos'){
			$this->logOrEcho('Checking <b>TROPICOS</b>...',1);
			$newTid= $this->addTropicosTaxon($term);
		}
		elseif($this->activeTaxonomicAuthority == 'eol'){
			$this->logOrEcho('Checking <b>EOL</b>...',1);
			$newTid= $this->addEolTaxon($term);
		}
		if(!$newTid) return false;
		flush();
		ob_flush();
		return $newTid;
	}

	/*
	 * INPUT: scientific name
	 *   Example: 'Pinus ponderosa var. arizonica'
	 * OUTPUT: tid taxon loaded into thesaurus 
	 *   Example: array('id' => '34554'
	 *   				'sciname' => 'Pinus arizonica', 
	 *   				'scientificName' => 'Pinus arizonica (Engelm.) Shaw', 
	 *        			'unitind1' => '', 'unitname1' => 'Pinus', 'unitind2' => '', 'unitname2' => 'arizonica', 'unitind3'=>'', 'unitname3'=>'',
	 *        			'author' => '(Engelm.) Shaw',
	 *        			'rankid' => '220',
	 *        			'taxonRank' => 'Species',
	 *        			'source' => '',
	 *        			'sourceURL' => '',
	 *  				'verns' => array(array('vernacularName'=>'Arizona Ponderosa Pine','language'=>'en'), array(etc...)),
	 *  				'syns' => array(array('sciname'=>'Pinus ponderosa var. arizonica','acceptanceReason'=>'synonym'...), array(etc...)),
	 *  				'parent' => array(	
	 *  					'id' => '43463',
	 *  					'sciname' => 'Pinus',
	 *  					'taxonRank' => 'Genus',
	 *  					'sourceURL' => 'http://eol.org/pages/1905/hierarchy_entries/43463/overview',
	 *  					'parentID' => array( etc...)
	 *  				)
	 *        	   )
	 */
	private function addColTaxon($sciName, $id = '', $resultIndex= 0){
		$taxonArr= Array();
		if(!is_numeric($resultIndex)) $resultIndex = 0;
		$url = 'http://webservice.catalogueoflife.org/col/webservice?response=full&format=json&';
		if($id){
			$url .= 'id='.$id;
		}
		elseif($sciName){
			$url .= 'name='.str_replace(" ","%20",$sciName);
		}
		else{
			return false;
		}
		if($fh = fopen($url, 'r')){
			$content = '';
			while($line = fread($fh, 1024)){
				$content .= trim($line);
			}
			fclose($fh);
			//Process return
			$resultArr = json_decode($content,true);
			$numResults = $resultArr['number_of_results_returned'];
			if($numResults){
				if($resultIndex > $numResults) $resultIndex = 0;
				$baseArr = $resultArr['results'][$resultIndex];
				$synArr = array();
				if($baseArr['name_status'] == 'synonym' && isset($baseArr['accepted_name'])){
					$synArr = $this->getColNode($baseArr);
					$synArr['acceptanceReason'] = 'synonym';
					$this->addColTaxon('',$baseArr['accepted_name']['id']);
				}
				$taxonArr = $this->getColNode($baseArr);
				if($synArr) $taxonArr['syns'][] = $synArr;
				//Get parent
				if(isset($baseArr['classification'])){
					$taxonArr['parent'] = $this->getColNode(array_pop($baseArr['classification']));
					if(isset($taxonArr['parent']['sciname'])){
						$parentTid = $this->getTid($taxonArr['parent']);
						if(!$parentTid){
							if(isset($taxonArr['parent']['id'])){
								$parentTid = $this->addColTaxon('', $taxonArr['parent']['id']);
							}
						}
						if($parentTid) $taxonArr['parent']['tid'] = $parentTid;
					}
				}
			}
		}
		if($sciName){
			if($taxonArr) $this->logOrEcho('Taxon found within Catalog of Life',1);
			else $this->logOrEcho('Taxon not found within Catalog of Life',1);
		}
		return $this->loadNewTaxon($taxonArr);
	}
	
	private function getColNode($nodeArr){
		$taxonArr = array();
		if(isset($nodeArr['id'])) $taxonArr['id'] = $nodeArr['id'];
		if(isset($nodeArr['name'])) $taxonArr['sciname'] = $nodeArr['name'];
		if(isset($nodeArr['rank'])) $taxonArr['taxonRank'] = $nodeArr['rank'];
		if(isset($nodeArr['genus'])) $taxonArr['unitname1'] = $nodeArr['genus'];
		if(isset($nodeArr['species'])) $taxonArr['unitname2'] = $nodeArr['species'];
		if(isset($nodeArr['infraspecies'])) $taxonArr['unitname3'] = $nodeArr['infraspecies'];
		if(isset($nodeArr['infraspecies_marker'])){
			$taxonArr['unitind3'] = $nodeArr['infraspecies_marker'];
			$taxonArr['sciname'] = trim($taxonArr['unitname1'].' '.$taxonArr['unitname2'].' '.$taxonArr['unitind3'].' '.$taxonArr['unitname3']);
		}
		if(isset($nodeArr['author'])) $taxonArr['author'] = $nodeArr['author'];
		if(isset($nodeArr['source_database'])) $taxonArr['source'] = $nodeArr['source_database'];
		if(isset($nodeArr['source_database_url'])) $taxonArr['sourceURL'] = $nodeArr['source_database_url'];
		return $taxonArr;
	}

	private function addWormsTaxon($sciName){
		$newTid = 0;
		//Get ID 
		$url = 'http://www.marinespecies.org/rest/AphiaIDByName/'.str_replace(" ","%20",$sciName).'?marine_only=false';
		if($fh = fopen($url, 'r')){
			$content = "";
			while($line = fread($fh, 1024)){
				$content .= trim($line);
			}
			fclose($fh);
			$id = $content;
			//Get species content
			if($id){
				$url = 'http://www.marinespecies.org/rest/AphiaRecordByAphiaID/282074';
			}
			else{
				$this->logOrEcho('Taxon not found within WoRMS',1);
			}
		}
		else{
			$this->logOrEcho('ERROR: unable to connect to WoRMS web services ('.$url.')',1);
		}
		return $newTid;
	}

	private function addTropicosTaxon($sciName){
		$newTid = 0;
		if(!$this->taxonomicResources['tropicos']){
			$this->logOrEcho('Error: TROPICOS API key required! Contact portal manager to establish key for portal',1);
			return false;
		}
		//Clean input string
		$sciName = str_replace(array(' subsp.',' ssp.',' var.',' f.'), '', $sciName);
		$sciName = str_replace('.','', $sciName);
		$sciName = str_replace(' ','%20', $sciName);
		//Start search
		$url = 'http://services.tropicos.org/Name/Search?type=exact&format=json&name='.$sciName.'&apikey='.$this->taxonomicResources['tropicos'];
		if($fh = fopen($url, 'r')){
			$content = "";
			while($line = fread($fh, 1024)){
				$content .= trim($line);
			}
			fclose($fh);
			$resultArr = json_decode($content,true);
			$id = 0;
			foreach($resultArr as $k => $arr){
				if(array_key_exists('Error', $arr)){
					$this->logOrEcho('Taxon not found within TROPICOS',1);
					return;
				}
				if(!array_key_exists('NomenclatureStatusID', $arr) || $arr['NomenclatureStatusID'] == 1){
					$id = $arr['NameId'];
					break;
				}
			}
			if($id){
				$this->logOrEcho('Taxon found within TROPICOS',1);
				$newTid = $this->addTropicosTaxonByID($id);
			}
			else{
				$this->logOrEcho('Taxon not found within TROPICOS (code:2)',1);
			}
		}
		else{
			$this->logOrEcho('ERROR: unable to connect to TROPICOS web services ('.$url.')',1);
		}
		return $newTid;
	}

	private function addTropicosTaxonByID($id){
		$taxonArr= Array();
		$url = 'http://services.tropicos.org/Name/'.$id.'?apikey='.$this->taxonomicResources['tropicos'].'&format=json';
		if($fh = fopen($url, 'r')){
			$content = "";
			while($line = fread($fh, 1024)){
				$content .= trim($line);
			}
			fclose($fh);
			$resultArr = json_decode($content,true);
			$taxonArr = $this->getTropicosNode($resultArr);
			
			//Get parent
			$url = 'http://services.tropicos.org/Name/'.$id.'/HigherTaxa?apikey='.$this->taxonomicResources['tropicos'].'&format=json';
			if($fh = fopen($url, 'r')){
				$content = '';
				while($line = fread($fh, 1024)){
					$content .= trim($line);
				}
				fclose($fh);
				$parentArr = json_decode($content,true);
				$parentNode = $this->getTropicosNode(array_pop($parentArr));
				if(isset($parentNode['sciname']) && $parentNode['sciname']){
					$parentTid = $this->getTid($parentNode);
					if(!$parentTid){
						if(isset($parentNode['id'])){
							$parentTid= $this->addTropicosTaxonByID($parentNode['id']);
						}
					}
					if($parentTid) $parentNode['tid'] = $parentTid;
					$taxonArr['parent'] = $parentNode;
				}
			}
			//Get accepted name
			$acceptedTid = 0;
			if($taxonArr['acceptedNameCount'] > 0 && $taxonArr['synonymCount'] == 0){
				$url = 'http://services.tropicos.org/Name/'.$id.'/AcceptedNames?apikey='.$this->taxonomicResources['tropicos'].'&format=json';
				if($fh = fopen($url, 'r')){
					$content = '';
					while($line = fread($fh, 1024)){
						$content .= trim($line);
					}
					fclose($fh);
					$resultArr = json_decode($content,true);
					if(isset($resultArr['Synonyms']['Synonym']['AcceptedName'])){
						$acceptedNode = $this->getTropicosNode($resultArr['Synonyms']['Synonym']['AcceptedName']);
						$this->buildTaxonArr($acceptedNode);
						$acceptedTid = $this->getTid($acceptedNode);
						if(!$acceptedTid){
							if(isset($acceptedNode['id'])){
								$acceptedTid= $this->addTropicosTaxonByID($acceptedNode['id']);
							}
						}
					}
				}
			}
		}
		return $this->loadNewTaxon($taxonArr);
	}
	
	private function getTropicosNode($nodeArr){
		$taxonArr = array();
		if(isset($nodeArr['NameId'])) $taxonArr['id'] = $nodeArr['NameId'];
		if(isset($nodeArr['ScientificName'])) $taxonArr['sciname'] = $nodeArr['ScientificName'];
		if(isset($nodeArr['ScientificNameWithAuthors'])) $taxonArr['scientificName'] = $nodeArr['ScientificNameWithAuthors'];
		if(isset($nodeArr['Author'])) $taxonArr['author'] = $nodeArr['Author'];
		if(isset($nodeArr['Family'])) $taxonArr['family'] = $nodeArr['Family'];
		if(isset($nodeArr['SynonymCount'])) $taxonArr['synonymCount'] = $nodeArr['SynonymCount'];
		if(isset($nodeArr['AcceptedNameCount'])) $taxonArr['acceptedNameCount'] = $nodeArr['AcceptedNameCount'];
		if(isset($nodeArr['Rank'])){
			$taxonArr['taxonRank'] = $nodeArr['Rank'];
		}
		elseif(isset($nodeArr['RankAbbreviation'])){
			$taxonArr['taxonRank'] = $nodeArr['RankAbbreviation'];
		}
		if(isset($taxonArr['taxonRank'])) $taxonArr['rankid'] = $this->getRankId($taxonArr);
		if(isset($nodeArr['Genus'])) $taxonArr['unitname1'] = $nodeArr['Genus'];
		if(isset($nodeArr['SpeciesEpithet'])) $taxonArr['unitname2'] = $nodeArr['SpeciesEpithet'];
		if(isset($taxonArr['unitname2']) && isset($nodeArr['OtherEpithet'])){
			$taxonArr['unitname3'] = $nodeArr['OtherEpithet'];
			if($this->kingdomName != 'Animalia'){
				if($taxonArr['rankid'] == 230) $taxonArr['unitind3'] = 'subsp.';
				elseif($taxonArr['rankid'] == 240) $taxonArr['unitind3'] = 'var.';
				elseif($taxonArr['rankid'] == 260) $taxonArr['unitind3'] = 'f.';
			}
		}
		if(isset($nodeArr['source'])) $taxonArr['source'] = $nodeArr['source'];
		if(!isset($taxonArr['unitname1']) && !strpos($taxonArr['sciname'],' ')) $taxonArr['unitname1'] = $taxonArr['sciname'];
		return $taxonArr;
	}

	private function addEolTaxon($term){
		//Returns content for accepted name
		$taxonArr = array();
		$eolManager = new EOLUtilities();
		if($eolManager->pingEOL()){
			$eolTaxonId = 0;
			$searchRet = $eolManager->searchEOL($term);
			if(isset($searchRet['id'])){
				//Id of EOL preferred name is returned
				$eolTaxonId = $searchRet['id'];
				$searchSyns = ((strpos($searchRet['title'],$term) !== false)?false:true);
				$taxonArr = $eolManager->getPage($searchRet['id'],$searchSyns);
				if($searchSyns && isset($taxonArr['syns'])){
					//Only add synonym that was original target taxon; remove all others
					foreach($taxonArr['syns']as $k => $synArr){
						if(strpos($synArr['scientificName'],$term) !== 0) unset($taxonArr['syns'][$k]);
					}
				}
				if(isset($taxonArr['taxonConcepts'])){
					if($taxonConceptId = key($taxonArr['taxonConcepts'])){
						$conceptArr = $eolManager->getHierarchyEntries($taxonConceptId);
						if(isset($conceptArr['parent'])) $taxonArr['parent'] = $conceptArr['parent'];
					}
				}
				if(!isset($taxonArr['source'])) $taxonArr['source'] = 'EOL - '.date('Y-m-d G:i:s');
			}
			else{
				$this->logOrEcho('ERROR: taxon not found within EOL (term: '.$term.')',1);
				return false;
			}
		}
		else{
			//$this->logOrEcho('EOL web services are not available ',1);
			return false;
		}
		//Process taxonomic name
		if($taxonArr) $this->logOrEcho('Taxon found within Encyclopedia of Life',1);
		else $this->logOrEcho('Taxon not found within Encyclopedia of Life',1);
		return $this->loadNewTaxon($taxonArr);
	}

	//Database functions
	private function loadNewTaxon($taxonArr,$tidAccepted = 0){
		$newTid = 0;
		if(!$taxonArr) return false;
		if((!isset($taxonArr['sciname']) || !$taxonArr['sciname']) && isset($taxonArr['scientificName']) && $taxonArr['scientificName']){
			$this->buildTaxonArr($taxonArr);
		}
		//Check to see sciname is in taxon table, but perhaps not linked to current thesaurus
		$sql = 'SELECT tid FROM taxa WHERE sciname = "'.$taxonArr['sciname'].'"';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$newTid = $r->tid;
		}
		$rs->free();
		$loadTaxon = true;
		if($newTid){
			//Name already exists within taxa table, but need to check if it's part if target thesaurus which name is accepted
			$sql = 'SELECT tidaccepted FROM taxstatus WHERE (taxauthid = '.$this->taxAuthId.') AND (tid = '.$newTid.')';
			$rs = $this->conn->query($sql);
			if($r = $rs->fetch_object()){
				//Taxon is already in this thesaurus, thus link synonyms to accepted name of this taxon 
				$tidAccepted = $r->tidaccepted;
				$loadTaxon= false;
			}
			$rs->free();
		}
		if($loadTaxon){
			if(!$this->validateTaxonArr($taxonArr)) return false;
			if(!$newTid){
				//Name doesn't exist in taxa table, and thus needs to be added
				$sqlInsert = 'INSERT INTO taxa(sciname, unitind1, unitname1, unitind2, unitname2, unitind3, unitname3, author, rankid, source) '.
					'VALUES("'.$taxonArr['sciname'].'",'.
					(isset($taxonArr['unitind1']) && $taxonArr['unitind1']?'"'.$taxonArr['unitind1'].'"':'NULL').',"'.
					$taxonArr['unitname1'].'",'.
					(isset($taxonArr['unitind2']) && $taxonArr['unitind2']?'"'.$taxonArr['unitind2'].'"':'NULL').','.
					(isset($taxonArr['unitname2']) && $taxonArr['unitname2']?'"'.$taxonArr['unitname2'].'"':'NULL').','.
					(isset($taxonArr['unitind3']) && $taxonArr['unitind3']?'"'.$taxonArr['unitind3'].'"':'NULL').','.
					(isset($taxonArr['unitname3']) && $taxonArr['unitname3']?'"'.$taxonArr['unitname3'].'"':'NULL').','.
					(isset($taxonArr['author']) && $taxonArr['author']?'"'.$taxonArr['author'].'"':'NULL').','.
					$taxonArr['rankid'].','.
					(isset($taxonArr['source']) && $taxonArr['source']?'"'.$taxonArr['source'].'"':'NULL').')';
				//echo $sqlInsert;
				if($this->conn->query($sqlInsert)){
					$newTid = $this->conn->insert_id;
				}
				else{
					$this->logOrEcho('ERROR inserting '.$taxonArr['sciname'].': '.$this->conn->error,1);
					return false;
				}
			}
			if($newTid){
				//Get parent identifier
				$parentTid = 0;
				if(isset($taxonArr['parent']['tid'])) $parentTid = $taxonArr['parent']['tid'];
				if(!$parentTid && isset($taxonArr['parent']['sciname'])){
					$parentTid = $this->getTid($taxonArr['parent']);
					if(!$parentTid){
						//$parentTid = $this->addSciname($taxonArr['parent']['sciname']);
					}
				}

				if(!$parentTid){
					$this->logOrEcho('ERROR loading '.$taxonArr['sciname'].': unable to get parentTid',1);
					return false;
				}

				//Establish acceptance 
				if(!$tidAccepted) $tidAccepted = $newTid;
				$sqlInsert2 = 'INSERT INTO taxstatus(tid,tidAccepted,taxAuthId,parentTid,UnacceptabilityReason) '.
					'VALUES('.$newTid.','.$tidAccepted.','.$this->taxAuthId.','.$parentTid.','.
					(isset($taxonArr['acceptanceReason']) && $taxonArr['acceptanceReason']?'"'.$taxonArr['acceptanceReason'].'"':'NULL').')';
				//echo $sqlInsert2; 
				if($this->conn->query($sqlInsert2)){
					//Add hierarchy index
					$sqlHier = 'INSERT INTO taxaenumtree(tid,parenttid,taxauthid) '.
						'VALUES('.$newTid.','.$parentTid.','.$this->taxAuthId.')';
					if(!$this->conn->query($sqlHier)){
						$this->logOrEcho('ERROR adding new tid to taxaenumtree (step 1): '.$this->conn->error,1);
					}
					$sqlHier2 = 'INSERT INTO taxaenumtree(tid,parenttid,taxauthid) '.
						'SELECT '.$newTid.' AS tid, parenttid, taxauthid FROM taxaenumtree WHERE tid = '.$parentTid;
					if(!$this->conn->query($sqlHier2)){
						$this->logOrEcho('ERROR adding new tid to taxaenumtree (step 2): '.$this->conn->error,1);
					}
					//Display action message
					$taxonDisplay = $taxonArr['sciname'];
					if(isset($GLOBALS['USER_RIGHTS']['Taxonomy'])){
						$taxonDisplay = '<a href="'.$GLOBALS['CLIENT_ROOT'].'/taxa/admin/taxonomyeditor.php?tid='.$newTid.'" target="_blank">'.$taxonArr['sciname'].'</a>';
					}
					$accStr = 'accepted';
					if($tidAccepted != $newTid){
						if(isset($GLOBALS['USER_RIGHTS']['Taxonomy'])){
							$accStr = 'synonym of taxon <a href="'.$GLOBALS['CLIENT_ROOT'].'/taxa/admin/taxonomyeditor.php?tid='.$tidAccepted.'" target="_blank">#'.$tidAccepted.'</a>';
						}
						else{
							$accStr = 'synonym of taxon #'.$tidAccepted;
						}
					}
					$this->logOrEcho('Taxon <b>'.$taxonDisplay.'</b> added to thesaurus as '.$accStr,1);
				}
			}
		}
		//Add Synonyms
		if(isset($taxonArr['syns'])){
			foreach($taxonArr['syns'] as $k => $synArr){
				if($synArr){
					if(isset($taxonArr['source']) && $taxonArr['source'] && (!isset($synArr['source']) || !$synArr['source'])) $synArr['source'] = $taxonArr['source'];
					if(isset($taxonArr['acceptanceReason']) && $taxonArr['acceptanceReason'] && (!isset($synArr['acceptanceReason']) || !$synArr['acceptanceReason'])) $synArr['acceptanceReason'] = $taxonArr['acceptanceReason'];
					$this->loadNewTaxon($synArr,$newTid);
				}
			}
		}
		//Add common names
		if(isset($taxonArr['verns'])){
			foreach($taxonArr['verns'] as $k => $vernArr){
				$sqlVern = 'INSERT INTO(tid,vernacularname,language) '.
					'VALUES('.$newTid.',"'.$vernArr['vernacularName'].'","'.$vernArr['language'].'")';
				if(!$this->conn->query($sqlVern)){
					$this->logOrEcho('ERROR loading vernacular '.$taxonArr['sciname'].': '.$this->conn->error,1);
				}
			}
		}
		if(isset($taxonArr['unitname3']) && $taxonArr['unitname3']) $this->fullyResolved = false;
		return $newTid;
	}

	private function validateTaxonArr(&$taxonArr){
		if(!is_array($taxonArr)) return;
		if(!isset($taxonArr['rankid']) || !$taxonArr['rankid']){
			if(isset($taxonArr['taxonRank']) && $taxonArr['taxonRank']){
				if($rankid = $this->getRankId($taxonArr)){
					$taxonArr['rankid'] = $rankid;
				}
			}
		}
		if(!$this->kingdomTid) $this->setDefaultKingdom();
		if(!array_key_exists('parent',$taxonArr) || !$taxonArr['parent']){
			$this->determineParents($taxonArr);
		}
		//Check to make sure required fields are present
		if(!isset($taxonArr['sciname']) || !$taxonArr['sciname']){
			$this->logOrEcho('ERROR loading '.$taxonArr['sciname'].': Input scientific name not defined',1);
			return false;
		}
		if(!isset($taxonArr['parent']) || !$taxonArr['parent']){
			$this->logOrEcho('ERROR loading '.$taxonArr['sciname'].': Parent name not definable',1);
			return false;
		}
		if(!isset($taxonArr['unitname1']) || !$taxonArr['unitname1']){
			$this->logOrEcho('ERROR loading '.$taxonArr['sciname'].': unitname1 not defined',1);
			return false;
		}
		if(!isset($taxonArr['rankid']) || !$taxonArr['rankid']){
			$this->logOrEcho('ERROR loading '.$taxonArr['sciname'].': rankid not defined',1);
			return false;
		}
		return true;
	}

	private function getRankId($taxonArr){
		$rankid = 0;
		if(isset($taxonArr['taxonRank']) && $taxonArr['taxonRank']){
			$rankArr = array('organism' => 1, 'kingdom' => 10, 'subkingdom' => 20, 'division' => 30, 'phylum' => 30, 'subdivision' => 40, 'subphylum' => 40, 'superclass' => 50, 'supercl.' => 50,
				'class' => 60, 'cl.' => 60, 'subclass' => 70, 'subcl.' => 70, 'superorder' => 90, 'superord.' => 90, 'order' => 100, 'ord.' => 100, 'suborder' => 110, 'subord.' => 110, 
				'family' => 140, 'fam.' => 140, 'subfamily' => 150, 'tribe' => 160, 'subtribe' => 170, 'genus' => 180, 'gen.' => 180,
				'subgenus' => 190, 'section' => 200, 'subsection' => 210, 'species' => 220, 'sp.' => 220, 'subspecies' => 230, 'ssp.' => 230, 'subsp.' => 230,
				'variety' => 240, 'var.' => 240, 'morph' => 240, 'subvariety' => 250, 'form' => 260, 'f.' => 260, 'subform' => 270, 'cultivated' => 300);
			if(array_key_exists($taxonArr['taxonRank'], $rankArr)){
				$rankid = $rankArr[$taxonArr['taxonRank']];
			}
		}
		if(!$rankid && isset($taxonArr['unitind3']) && $taxonArr['unitind3']){
			if(array_key_exists($taxonArr['unitind3'], $rankArr)){
				$rankid = $rankArr[$taxonArr['unitind3']];
			}
		}
		if(!$rankid && isset($taxonArr['taxonRank']) && $taxonArr['taxonRank']){
			//Check database
			$sqlRank = 'SELECT rankid FROM taxonunits WHERE rankname = "'.$taxonArr['taxonRank'].'"';
			$rsRank = $this->conn->query($sqlRank);
			while($rRank = $rsRank->fetch_object()){
				$rankid = $rRank->rankid;
			}
			$rsRank->free();
		}
		return $rankid;
	}

	private function setDefaultKingdom(){
		$sql = 'SELECT t.sciname, t.tid, COUNT(e.tid) as cnt '. 
			'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.parenttid '.
			'WHERE t.rankid = 10 '.
			'GROUP BY t.sciname '.
			'ORDER BY cnt desc';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$this->kingdomName = $r->sciname;
			$this->kingdomTid = $r->tid;
		}
		$rs->free();
	}

	private function determineParents(&$taxonArr){
		if(!is_array($taxonArr)) return;
		$parArr = array();
		if($taxonArr['sciname']){
			if(!isset($taxonArr['rankid']) || !$taxonArr['rankid']){
				$this->buildTaxonArr($taxonArr);
			}
			elseif(!isset($taxonArr['unitname1']) || !$taxonArr['unitname1']){
				$this->buildTaxonArr($taxonArr);
			}
			if($taxonArr['rankid']){
				if(!$this->kingdomTid) $this->setDefaultKingdom();
				if($this->kingdomName){
					$parArr = array(
						'sciname' => $this->kingdomName, 
						'taxonRank' => 'kingdom', 
						'rankid' => '10' 
					);
				}
				if($taxonArr['rankid'] > 140){
					$familyStr = $this->defaultFamily;
					if(isset($taxonArr['family']) && $taxonArr['family']) $familyStr = $taxonArr['family'];
					if($familyStr){
						$sqlFam = 'SELECT tid FROM taxa '.
							'WHERE (sciname = "'.$this->defaultFamily.'") AND (rankid = 140)';
						//echo $sqlFam;
						$rs = $this->conn->query($sqlFam);
						if($r = $rs->fetch_object()){
							$newParArr = array(
								'sciname' => $this->defaultFamily,
								'tid' => $r->tid, 
								'taxonRank' => 'family', 
								'rankid' => '140',
								'parent' => $parArr
							);
							$parArr = $newParArr;
						}
						$rs->free();
					}
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
	public function buildTaxonArr(&$taxonArr){
		if(is_array($taxonArr)){
			$rankid = array_key_exists('rankid', $taxonArr)?$taxonArr['rankid']:0;
			$sciname = array_key_exists('sciname', $taxonArr)?$taxonArr['sciname']:'';
			if(!$sciname && array_key_exists('scientificName', $taxonArr)) $sciname = $taxonArr['scientificName'];
			if($sciname){
				$taxonArr = array_merge(TaxonomyUtilities::parseScientificName($sciname,$this->conn,$rankid),$taxonArr);
			}
		}
	}

	public function getCloseMatch($taxonStr){
		$retArr = array();
		$taxonStr = $this->cleanInStr($taxonStr);
		if($taxonStr){
			$infraArr = array('subsp','ssp','var','f');
			$taxonStringArr = explode(' ',$taxonStr);
			$unitname1 = array_shift($taxonStringArr);
			if(strlen($unitname1) == 1) $unitname1 = array_shift($taxonStringArr);
			$unitname2 = array_shift($taxonStringArr);
			if(strlen($unitname2) == 1) $unitname2 = array_shift($taxonStringArr);
			$unitname3= array_shift($taxonStringArr);
			if($taxonStringArr){
				while($val = array_shift($taxonStringArr)){
					if(in_array(str_replace('.', '', $val),$infraArr)) $unitname3= array_shift($taxonStringArr);
				}
			}
			if($unitname3){
				//Look for infraspecific species with different rank indicators
				$sql = 'SELECT tid, sciname FROM taxa '.
					'WHERE (unitname1 = "'.$unitname1.'") AND (unitname2 = "'.$unitname2.'") AND (unitname3 = "'.$unitname3.'") '.
					'ORDER BY sciname';
				//echo $sql;
				$rs = $this->conn->query($sql);
				while($row = $rs->fetch_object()){
					$retArr[$row->tid] = $row->sciname;
				}
				$rs->free();
			}
			
			/*
			//Get soundex matches
			$sql = 'SELECT tid, sciname FROM taxa WHERE SOUNDEX(sciname) = SOUNDEX("'.$taxonStr.'") ORDER BY sciname LIMIT 10';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				if(!strpos($taxonStr,' ') || strpos($row->sciname,' ')){
					$retArr[$row->tid] = $row->sciname;
				}
			}
			$rs->free();
			*/

			if($unitname2){
				if(!$retArr){
					//Look for match where
					$searchStr = substr($unitname1,0,4).'%';
					$searchStr .= ' '.substr($unitname2,0,4).'%';
					if(count($unitname3) > 2) $searchStr .= ' '.substr($unitname3,0,5).'%';
					$sql = 'SELECT tid, sciname FROM taxa WHERE (sciname LIKE "'.$searchStr.'") ORDER BY sciname LIMIT 15';
					//echo $sql;
					$rs = $this->conn->query($sql);
					while($row = $rs->fetch_object()){
						similar_text($taxonStr,$row->sciname,$percent);
						if($percent > 70) $retArr[$row->tid] = $row->sciname;
					}
					$rs->free();
				}
				
				if(!$retArr){
					//Look for matches based on same edithet but different genus
					$sql = 'SELECT tid, sciname FROM taxa WHERE (sciname LIKE "'.substr($unitname1,0,2).'% '.$unitname2.'") ORDER BY sciname';
					//echo $sql;
					$rs = $this->conn->query($sql);
					while($row = $rs->fetch_object()){
						$retArr[$row->tid] = $row->sciname;
					}
					$rs->free();
				}
	
			}
		}
		return $retArr;
	}

	private function getTid($taxonArr){
		$tid = 0;
		if($taxonArr['sciname']){
			$sciname = $taxonArr['sciname'];
			$sql = 'SELECT DISTINCT t.tid, t.author, t.rankid '.
				'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
				'WHERE (e.taxauthid = '.$this->taxAuthId.') AND (t.sciname = "'.$this->cleanInStr($sciname).'") ';
			if($this->kingdomTid) $sql .= 'AND (e.parenttid = '.$this->kingdomTid.')';
			$tidArr = array();
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$tidArr[$r->tid]['author'] = $r->author;
				$tidArr[$r->tid]['rankid'] = $r->rankid;
			}
			$rs->free();
			if(count($tidArr) == 1){
				$tid = key($tidArr);
			}
			else{
				$goodArr = array();
				//If rankid is same, then it gets a plus
				foreach($tidArr as $t => $tArr){
					if(isset($taxonArr['rankid']) && $taxonArr['rankid']){
						if($tArr['rankid'] == $taxonArr['rankid']){
							$goodArr[$t] = 1;
						}
						else{
							$goodArr[$t] = 0;
						}
					}
					//Gets a plus if author is the same
					if(isset($taxonArr['author']) && $taxonArr['author']){
						$author1 = str_replace(array(' ','.'), '', $taxonArr['author']);
						$author2 = str_replace(array(' ','.'), '', $tArr['author']);
						similar_text($author1, $author2, $percent);
						if($author1 == $author2) $goodArr[$t] += 2;
						elseif($percent > 80) $goodArr[$t] += 1;
					}
				}
				asort($goodArr);
				end($goodArr);
				$tid = key($goodArr);
			}
		}
		return $tid;
	}

	//Setters and getters
	public function setTaxAuthId($id){
		if(is_numeric($id)){
			$this->taxAuthId = $id;
		}
	}
	
	public function setKingdom($kingdom){
		$sql = 'SELECT sciname, tid FROM taxa t WHERE ';
		if(is_numeric($kingdom)){
			$sql .= '(tid = '.$kingdom.') ';
		}
		else{
			$sql .= '(sciname = "'.$this->cleanInStr($kingdom).'") ';
		}
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$this->kingdomName = $r->sciname;
			$this->kingdomTid = $r->tid;
		}
		$rs->free();
	}

	public function setActiveTaxonomicAuthority($taxAuth){
		if(!array_key_exists($taxAuth, $this->taxonomicResources)) return false;
		$this->activeTaxonomicAuthority = $taxAuth;
		return true;
	}

	public function getTaxonomicResources(){
		return $this->taxonomicResources;
	}

	public function setDefaultFamily($familyStr){
		$this->defaultFamily = $this->cleanInStr($familyStr);
	}
	
	public function isFullyResolved(){
		return $this->fullyResolved;
	}
}
?>