<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/classes/Manager.php');
include_once($SERVER_ROOT.'/classes/TaxonomyUtilities.php');
include_once($SERVER_ROOT.'/classes/EOLUtilities.php');

class TaxonomyHarvester extends Manager{

	private $taxonomicResources = array();
	private $taxAuthId = 1;
	private $defaultFamily;
	private $kingdomName;
	private $kingdomTid;
	private $fullyResolved;

	function __construct() {
		parent::__construct(null,'write');
	}

	function __destruct(){
		parent::__destruct();
	}

	public function processSciname($term){
		$term = trim($term);
		if($term){
			$this->fullyResolved = true;
			if(!$this->taxonomicResources){
				$this->logOrEcho('External taxonomic resource checks not activated ',1);
				return false;
			}
			$newTid = $this->parseCleanCheck($term);
			if($newTid) return $newTid;
			foreach($this->taxonomicResources as $authCode=> $apiKey){
				$newTid = $this->addSciname($term, $authCode);
				if($newTid) return $newTid;
			}
		}
	}

	private function addSciname($term, $resourceKey){
		if(!$term) return false;
		$newTid = 0;
		if($resourceKey== 'col'){
			$this->logOrEcho('Checking <b>Catalog of Life</b>...',1);
			$newTid= $this->addColTaxon($term);
		}
		elseif($resourceKey== 'worms'){
			$this->logOrEcho('Checking <b>WoRMS</b>...',1);
			$newTid= $this->addWormsTaxon($term);
		}
		elseif($resourceKey== 'tropicos'){
			$this->logOrEcho('Checking <b>TROPICOS</b>...',1);
			$newTid= $this->addTropicosTaxon($term);
		}
		elseif($resourceKey== 'eol'){
			$this->logOrEcho('Checking <b>EOL</b>...',1);
			$newTid= $this->addEolTaxon($term);
		}
		if(!$newTid) return false;
		return $newTid;
	}

	private function parseCleanCheck($term){
		$tid = 0;
		$taxonArr = array('sciname' => $term);
		$this->buildTaxonArr($taxonArr);
		$tid = $this->getTid($taxonArr);
		if(!$tid){
			if(isset($taxonArr['rankid']) && $taxonArr['rankid'] > 220 && $taxonArr['unitname2'] == $taxonArr['unitname3']){
				//Taxon is an infraspecific tautonym
				if($parentArr = $this->getParentArr($taxonArr)){
					if($parentTid = $this->getTid($parentArr)){
						$taxonArr['parent']['tid'] = $parentTid;
						$parentTidAccepted = $this->getTidAccepted($parentTid);
						if($parentTidAccepted == $parentTid){
							$tid = $this->loadNewTaxon($taxonArr);
						}
						else{
							$tid = $this->loadNewTaxon($taxonArr,$parentTidAccepted);
						}
					}
				}
			}
		}
		return $tid;
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
	 *  					'tid' => 12345,
	 *  					'sciname' => 'Pinus',
	 *  					'taxonRank' => 'Genus',
	 *  					'sourceURL' => 'http://eol.org/pages/1905/hierarchy_entries/43463/overview',
	 *  					'parentID' => array( etc...)
	 *  				)
	 *        	   )
	 */
	private function addColTaxon($sciName){
		$tid = 0;
		if($sciName){
			$url = 'http://webservice.catalogueoflife.org/col/webservice?response=full&format=json&name='.str_replace(" ","%20",$sciName);
			//echo $url.'<br/>';
			$retArr = $this->getContentString($url);
			$content = $retArr['str'];
			$resultArr = json_decode($content,true);
			$numResults = $resultArr['number_of_results_returned'];
			if($numResults){
				$this->logOrEcho('Taxon found within Catalog of Life',2);
				$tid = $this->addColTaxonByResult($resultArr, $sciName);
			}
			else{
				$this->logOrEcho('Taxon not found',2);
			}
		}
		else{
			$this->logOrEcho('ERROR harvesting COL name: null input name',1);
		}
		return $tid;
	}

	private function addColTaxonById($id){
		$tid = 0;
		if($id){
			$url = 'http://webservice.catalogueoflife.org/col/webservice?response=full&format=json&id='.$id;
			//echo $url.'<br/>';
			$retArr = $this->getContentString($url);
			$content = $retArr['str'];
			$resultArr = json_decode($content,true);
			$tid = $this->addColTaxonByResult($resultArr);
		}
		else{
			$this->logOrEcho('ERROR harvesting COL name: null input identifier',1);
		}
		return $tid;
	}

	private function addColTaxonByResult($resultArr, $originalSearchStr = ''){
		$taxonArr = array();
		if($resultArr){
			$baseArr = $resultArr['results'][0];
			if(!$baseArr){
				return 0;
			}
			$tidAccepted = 0;
			if($baseArr['name_status'] == 'synonym' && isset($baseArr['accepted_name'])){
				$tidAccepted = $this->addColTaxonById($baseArr['accepted_name']['id']);
			}
			$taxonArr = $this->getColNode($baseArr);
			if($originalSearchStr && $originalSearchStr != $taxonArr['sciname']){
				//Abort because search return contains a child of the search term
				return false;
			}
			//Get parent
			if($taxonArr['rankid'] == 10){
				$taxonArr['parent']['tid'] = 'self';
			}
			else{
				$parentTid = 0;
				if(isset($baseArr['classification'])){
					$parentArr = array();
					do{
						$parentArr = $this->getColNode(array_pop($baseArr['classification']));
						if(isset($parentArr['sciname'])){
							$parentTid = $this->getTid($parentArr);
							if(!$parentTid){
								if(isset($parentArr['id'])){
									$parentTid = $this->addColTaxonById($parentArr['id']);
								}
							}
						}
					}while(!$parentTid && $baseArr['classification']);
					if($parentArr) $taxonArr['parent'] = $parentArr;
				}
				else{
					$parentArr = $this->getParentArr($taxonArr);
					$parentTid = $this->addColTaxon($parentArr['sciname']);
					if(!$parentTid){
						//Bad return from COL, thus lets just add as accepted for now
						$taxonArr['family'] = $this->getColFamily($baseArr);
						$this->buildTaxonArr($parentArr);
						$parentTid = $this->loadNewTaxon($parentArr);
					}
				}
				if($parentTid) $taxonArr['parent']['tid'] = $parentTid;
			}
		}
		else{
			$this->logOrEcho('ERROR harvesting COL name: null result',1);
		}
		return $this->loadNewTaxon($taxonArr, $tidAccepted);
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
		$this->setRankId($taxonArr);
		if(!isset($taxonArr['unitname1']) && $taxonArr['rankid'] < 220) $taxonArr['unitname1'] = $taxonArr['sciname'];
		//$this->buildTaxonArr($taxonArr);
		return $taxonArr;
	}

	private function getColFamily($baseArr){
		//Returns family obtained from accepted taxon
		$familyStr = '';
		$classArr = array();
		if(array_key_exists('classification', $baseArr)){
			$classArr = $baseArr['classification'];
		}
		elseif(isset($baseArr['accepted_name']['classification'])){
			$classArr = $baseArr['accepted_name']['classification'];
		}

		foreach($classArr as $classNode){
			if($classNode['rank'] == 'Family'){
				$familyStr = $classNode['name'];
			}
		}
		return $familyStr;
	}

	private function addWormsTaxon($sciName){
		$tid = 0;
		$url = 'http://www.marinespecies.org/rest/AphiaIDByName/'.rawurlencode($sciName).'?marine_only=false';
		$retArr = $this->getContentString($url);
		$id = $retArr['str'];
		if(is_numeric($id)){
			$this->logOrEcho('Taxon found within WoRMS',2);
			$tid = $this->addWormsTaxonByID($id);
		}
		else{
			$this->logOrEcho('Taxon not found',2);
		}
		return $tid;
	}

	private function addWormsTaxonByID($id){
		if(!is_numeric($id)){
			$this->logOrEcho('ERROR harvesting from worms: illegal identifier: '.$id,1);
			return 0;
		}
		$taxonArr= Array();
		$acceptedTid = 0;
		$url = 'http://www.marinespecies.org/rest/AphiaRecordByAphiaID/'.$id;
		if($resultStr = $this->getWormsReturnStr($this->getContentString($url),$url)){
			$taxonArr= $this->getWormsNode(json_decode($resultStr,true));
			if($taxonArr['acceptance'] == 'unaccepted' && isset($taxonArr['validID'])){
				//Get and set accepted taxon
				$acceptedTid = $this->addWormsTaxonByID($taxonArr['validID']);
			}
			//Get parent
			if($taxonArr['rankid'] == 10){
				$taxonArr['parent']['tid'] = 'self';
			}
			else{
				$url = 'http://www.marinespecies.org/rest/AphiaClassificationByAphiaID/'.$id;
				if($parentStr = $this->getWormsReturnStr($this->getContentString($url),$url)){
					$parentArr = json_decode($parentStr,true);
					if($parentID = $this->getWormParentID($parentArr, $id)){
						if($parentTid = $this->addWormsTaxonByID($parentID)){
							$taxonArr['parent'] = array('tid' => $parentTid);
						}
					}
				}
			}
		}
		return $this->loadNewTaxon($taxonArr, $acceptedTid);
	}

	private function getWormsReturnStr($retArr,$url){
		$resultStr = '';
		if($retArr['code'] == 200){
			$resultStr = $retArr['str'];
		}
		elseif($retArr['code'] == 204){
			$this->logOrEcho('Identifier not found within WoRMS: '.$url,2);
		}
		else{
			$this->logOrEcho('ERROR returning WoRMS object (code: '.$retArr['code'].'): '.$url,1);
		}
		return $resultStr;
	}

	private function getWormsNode($nodeArr){
		$taxonArr = array();
		if(isset($nodeArr['AphiaID'])) $taxonArr['id'] = $nodeArr['AphiaID'];
		if(isset($nodeArr['scientificname'])) $taxonArr['sciname'] = $nodeArr['scientificname'];
		if(isset($nodeArr['authority'])) $taxonArr['author'] = $nodeArr['authority'];
		if(isset($nodeArr['family'])) $taxonArr['family'] = $nodeArr['family'];
		if(isset($nodeArr['genus'])) $taxonArr['unitname1'] = $nodeArr['genus'];
		if(isset($nodeArr['status'])) $taxonArr['acceptance'] = $nodeArr['status'];
		if(isset($nodeArr['unacceptreason'])) $taxonArr['unacceptanceReason'] = $nodeArr['unacceptreason'];
		if(isset($nodeArr['valid_AphiaID'])) $taxonArr['validID'] = $nodeArr['valid_AphiaID'];
		if(isset($nodeArr['lsid'])) $taxonArr['guid'] = $nodeArr['lsid'];
		if(isset($nodeArr['rank'])) $taxonArr['taxonRank'] = $nodeArr['rank'];
		$this->setRankId($taxonArr);
		$this->buildTaxonArr($taxonArr);
		return $taxonArr;
	}

	private function getWormParentID($wormNode, $stopID, $previousID = 0){
		$parentID = 0;
		if(array_key_exists('AphiaID', $wormNode)){
			$parentID = $wormNode['AphiaID'];
			if($stopID == $parentID) return $previousID;
			if(array_key_exists('child', $wormNode)){
				$parentID = $this->getWormParentID($wormNode['child'], $stopID, $parentID);
			}
		}
		return $parentID;
	}

	private function addTropicosTaxon($sciName){
		$newTid = 0;
		if(!$this->taxonomicResources['tropicos']){
			$this->logOrEcho('Error: TROPICOS API key required! Contact portal manager to establish key for portal',1);
			return false;
		}
		//Clean input string
		$searchType = 'exact';
		if(substr_count($sciName,' ') > 1) $searchType = 'wildcard';
		$sciName = str_replace(array(' subsp.',' ssp.',' var.',' f.'), '', $sciName);
		$sciName = str_replace('.','', $sciName);
		$sciName = str_replace(' ','%20', $sciName);
		$url = 'http://services.tropicos.org/Name/Search?type='.$searchType.'&format=json&name='.$sciName.'&apikey='.$this->taxonomicResources['tropicos'];
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
					$this->logOrEcho('Taxon not found (code:1)',2);
					return;
				}
				if(!array_key_exists('NomenclatureStatusID', $arr) || $arr['NomenclatureStatusID'] == 1){
					$id = $arr['NameId'];
					break;
				}
			}
			if($id){
				$this->logOrEcho('Taxon found within TROPICOS',2);
				$newTid = $this->addTropicosTaxonByID($id);
			}
			else{
				$this->logOrEcho('Taxon not found (code:2)',2);
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
			if($taxonArr['rankid'] == 10){
				$taxonArr['parent']['tid'] = 'self';
			}
			else{
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
		if(isset($nodeArr['Genus'])) $taxonArr['unitname1'] = $nodeArr['Genus'];
		if(isset($nodeArr['SpeciesEpithet'])) $taxonArr['unitname2'] = $nodeArr['SpeciesEpithet'];
		if(isset($nodeArr['source'])) $taxonArr['source'] = $nodeArr['source'];
		if(!isset($taxonArr['unitname1']) && !strpos($taxonArr['sciname'],' ')) $taxonArr['unitname1'] = $taxonArr['sciname'];
		$this->setRankId($taxonArr);
		if(isset($taxonArr['unitname2']) && isset($nodeArr['OtherEpithet'])){
			$taxonArr['unitname3'] = $nodeArr['OtherEpithet'];
			if($this->kingdomName != 'Animalia'){
				if($taxonArr['rankid'] == 230) $taxonArr['unitind3'] = 'subsp.';
				elseif($taxonArr['rankid'] == 240) $taxonArr['unitind3'] = 'var.';
				elseif($taxonArr['rankid'] == 260) $taxonArr['unitind3'] = 'f.';
			}
		}
		return $taxonArr;
	}

	private function addEolTaxon($term){
		//Returns content for accepted name
		$tid = 0;
		$eolManager = new EOLUtilities();
		if($eolManager->pingEOL()){
			$eolTaxonId = 0;
			$searchRet = $eolManager->searchEOL($term);
			if(isset($searchRet['id'])){
				//Id of EOL preferred name is returned
				$eolTaxonId = $searchRet['id'];
				$searchSyns = ((strpos($searchRet['title'],$term) !== false)?false:true);
				$tid = $this->addEolTaxonById($searchRet['id'], $searchSyns, $term);
			}
			else{
				$this->logOrEcho('Taxon not found',2);
			}
		}
		else{
			$this->logOrEcho('EOL web services are not available ',1);
			return false;
		}
		return $tid;
	}

	private function addEolTaxonById($eolTaxonId, $searchSyns = false, $term = ''){
		//Returns content for accepted name
		$taxonArr = array();
		$eolManager = new EOLUtilities();
		if($eolManager->pingEOL()){
			$taxonArr = $eolManager->getPage($eolTaxonId, false);
			if($searchSyns && isset($taxonArr['syns'])){
				//Only add synonym that was original target taxon; remove all others
				foreach($taxonArr['syns']as $k => $synArr){
					if(strpos($synArr['scientificName'],$term) !== 0) unset($taxonArr['syns'][$k]);
				}
			}
			if(isset($taxonArr['taxonConcepts'])){
				if($taxonConceptId = key($taxonArr['taxonConcepts'])){
					$conceptArr = $eolManager->getHierarchyEntries($taxonConceptId);
					if(isset($conceptArr['parent'])){
						$parentTid = $this->getTid($conceptArr['parent']);
						if(!$parentTid && isset($conceptArr['parent']['taxonConceptID'])){
							$parentTid = $this->addEolTaxonById($conceptArr['parent']['taxonConceptID']);
						}
						if($parentTid){
							$conceptArr['parent']['tid'] = $parentTid;
							$taxonArr['parent'] = $conceptArr['parent'];
						}
					}
				}
			}
			if(!isset($taxonArr['source'])) $taxonArr['source'] = 'EOL - '.date('Y-m-d G:i:s');
		}
		else{
			$this->logOrEcho('EOL web services are not available ',1);
			return false;
		}
		//Process taxonomic name
		if($taxonArr) $this->logOrEcho('Taxon found within EOL',2);
		else{
			$this->logOrEcho('Taxon ID not found ('.$eolTaxonId.')',2);
			return false;
		}
		return $this->loadNewTaxon($taxonArr);
	}

	private function getContentString($url){
		$retArr = array();
		if($url){
			if($fh = fopen($url, 'r')){
				stream_set_timeout($fh, 10);
				$contentStr = '';
				while($line = fread($fh, 1024)){
					$contentStr .= trim($line);
				}
				fclose($fh);
				$retArr['str'] = $contentStr;
				//Get code
				$statusStr = $http_response_header[0];
				if(preg_match( "#HTTP/[0-9\.]+\s+([0-9]+)#",$statusStr, $out)){
					$retArr['code'] = intval($out[1]);
				}
			}
		}
		return $retArr;
	}

	//Database functions
	private function loadNewTaxon($taxonArr, $tidAccepted = 0){
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
				//echo $sqlInsert.'<br/>';
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
				if(isset($taxonArr['parent']['tid'])){
					if($taxonArr['parent']['tid'] == 'self') $parentTid = $newTid;
					elseif(is_numeric($taxonArr['parent']['tid'])) $parentTid = $taxonArr['parent']['tid'];
				}
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
				//echo $sqlInsert2.'<br/><br/>';
				if($this->conn->query($sqlInsert2)){
					//Add hierarchy index
					$sqlHier = 'INSERT INTO taxaenumtree(tid,parenttid,taxauthid) '.
						'VALUES('.$newTid.','.$parentTid.','.$this->taxAuthId.')';
					if(!$this->conn->query($sqlHier)){
						$this->logOrEcho('ERROR adding new tid to taxaenumtree (step 1): '.$this->conn->error,1);
					}
					$sqlHier2 = 'INSERT IGNORE INTO taxaenumtree(tid,parenttid,taxauthid) '.
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
					$acceptanceReason = '';
					if(isset($taxonArr['acceptanceReason']) && $taxonArr['acceptanceReason']) $acceptanceReason = $taxonArr['acceptanceReason'];
					if(isset($synArr['synreason']) && $synArr['synreason']) $acceptanceReason = $synArr['synreason'];
					if($acceptanceReason == 'misspelling'){
						$this->logOrEcho('Name not added because it is marked as a misspelling',1);
						$this->fullyResolved = false;
					}
					else{
						if($acceptanceReason && (!isset($synArr['acceptanceReason']) || !$synArr['acceptanceReason'])) $synArr['acceptanceReason'] = $acceptanceReason;
						$this->loadNewTaxon($synArr,$newTid);
					}
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
		return $newTid;
	}

	private function validateTaxonArr(&$taxonArr){
		if(!is_array($taxonArr)) return;
		if(!isset($taxonArr['rankid']) || !$taxonArr['rankid']){
			if(isset($taxonArr['taxonRank']) && $taxonArr['taxonRank']){
				$this->setRankId($taxonArr);
			}
		}
		if(!$this->kingdomTid) $this->setDefaultKingdom();
		if(!array_key_exists('parent',$taxonArr) || !$taxonArr['parent']){
			$taxonArr['parent'] = $this->getParentArr($taxonArr);
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

	private function setRankId(&$taxonArr){
		$rankid = 0;
		$rankArr = array('biota' => 1, 'organism' => 1, 'kingdom' => 10, 'subkingdom' => 20, 'division' => 30, 'phylum' => 30, 'subdivision' => 40, 'subphylum' => 40, 'superclass' => 50, 'supercl.' => 50,
			'class' => 60, 'cl.' => 60, 'subclass' => 70, 'subcl.' => 70, 'infraclass' => 80, 'superorder' => 90, 'superord.' => 90, 'order' => 100, 'ord.' => 100, 'suborder' => 110, 'subord.' => 110,
			'superfamily' => 130, 'family' => 140, 'fam.' => 140, 'subfamily' => 150, 'tribe' => 160, 'subtribe' => 170, 'genus' => 180, 'gen.' => 180,
			'subgenus' => 190, 'section' => 200, 'subsection' => 210, 'species' => 220, 'sp.' => 220, 'subspecies' => 230, 'ssp.' => 230, 'subsp.' => 230, 'infraspecies' => 230,
			'variety' => 240, 'var.' => 240, 'morph' => 240, 'subvariety' => 250, 'form' => 260, 'f.' => 260, 'subform' => 270, 'cultivated' => 300);
		if(isset($taxonArr['taxonRank']) && $taxonArr['taxonRank']){
			$taxonRank = strtolower($taxonArr['taxonRank']);
			if(array_key_exists($taxonRank, $rankArr)){
				$rankid = $rankArr[$taxonRank];
			}
		}
		if(!$rankid && isset($taxonArr['unitind3']) && $taxonArr['unitind3']){
			$unitInd3 = strtolower($taxonArr['unitind3']);
			if(array_key_exists($unitInd3, $rankArr)){
				$rankid = $rankArr[$unitInd3];
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
		$taxonArr['rankid'] = $rankid;
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

	private function getParentArr($taxonArr){
		if(!is_array($taxonArr)) return;
		$parArr = array();
		if($taxonArr['sciname']){
			if(isset($taxonArr['rankid']) && $taxonArr['rankid']){
				if(!$this->kingdomTid) $this->setDefaultKingdom();
				if($this->kingdomName){
					//Set this as default parent
					$parArr = array(
						'tid' => $this->kingdomTid,
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
							$parArr = array(
								'tid' => $r->tid,
								'sciname' => $this->defaultFamily,
								'taxonRank' => 'family',
								'rankid' => '140'
							);
						}
						$rs->free();
					}
				}
				if($taxonArr['rankid'] > 180){
					$parArr = array(
						'sciname' => $taxonArr['unitname1'],
						'taxonRank' => 'genus',
						'rankid' => '180'
					);
				}
				if($taxonArr['rankid'] > 220){
					$parArr = array(
						'sciname' => $taxonArr['unitname1'].' '.$taxonArr['unitname2'],
						'taxonRank' => 'species',
						'rankid' => '220'
					);
				}
			}
		}
		return $parArr;
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
				//echo $sql.'<br/>';
				$rs = $this->conn->query($sql);
				while($row = $rs->fetch_object()){
					$retArr[$row->tid] = $row->sciname;
				}
				$rs->free();
			}

			if($unitname2){
				if(!$retArr){
					//Look for match where
					$searchStr = substr($unitname1,0,4).'%';
					$searchStr .= ' '.substr($unitname2,0,4).'%';
					if(count($unitname3) > 2) $searchStr .= ' '.substr($unitname3,0,5).'%';
					$sql = 'SELECT tid, sciname FROM taxa WHERE (sciname LIKE "'.$searchStr.'") ORDER BY sciname LIMIT 15';
					//echo $sql.'<br/>';
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
					//echo $sql.'<br/>';
					$rs = $this->conn->query($sql);
					while($row = $rs->fetch_object()){
						$retArr[$row->tid] = $row->sciname;
					}
					$rs->free();
				}
			}
			//Get soundex matches
			$sql = 'SELECT tid, sciname FROM taxa WHERE SOUNDEX(sciname) = SOUNDEX("'.$taxonStr.'") ORDER BY sciname LIMIT 5';
			//echo $sql;
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				if(!strpos($taxonStr,' ') || strpos($row->sciname,' ')){
					$retArr[$row->tid] = $row->sciname;
				}
			}
			$rs->free();
		}
		return $retArr;
	}

	public function getTid($taxonArr){
		$tid = 0;
		if(isset($taxonArr['sciname']) && $taxonArr['sciname']){
			$sciname = $taxonArr['sciname'];
			$tidArr = array();
			//Get tid, author, and rankid
			$sql = 'SELECT DISTINCT t.tid, t.author, t.rankid '.
				'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
				'WHERE (e.taxauthid = '.$this->taxAuthId.') AND (t.sciname = "'.$this->cleanInStr($sciname).'") ';
			$rs = $this->conn->query($sql);
			while($r = $rs->fetch_object()){
				$tidArr[$r->tid]['author'] = $r->author;
				$tidArr[$r->tid]['rankid'] = $r->rankid;
			}
			$rs->free();
			if(!$tidArr) return 0;
			//Check if homonyms are returned
			if(count($tidArr) == 1){
				$tid = key($tidArr);
			}
			elseif(count($tidArr) > 1){
				//Get parents to determine which is best
				$sqlPar = 'SELECT DISTINCT e.tid, t.tid AS parenttid, t.sciname, t.rankid '.
					'FROM taxaenumtree e INNER JOIN taxa t ON e.parenttid = t.tid '.
					'WHERE (e.taxauthid = '.$this->taxAuthId.') AND (e.tid IN('.implode(',',array_keys($tidArr)).')) AND (t.rankid IN (10,140)) ';
				$rsPar = $this->conn->query($sqlPar);
				while($rPar = $rsPar->fetch_object()){
					if($r->rankid == 10) $tidArr[$rPar->tid]['kingdom'] = $rPar->sciname;
					elseif($r->rankid == 140) $tidArr[$rPar->tid]['family'] = $rPar->sciname;
				}
				$rsPar->free();

				//Rate each name
				$goodArr = array();
				//If rankid is same, then it gets a plus
				foreach($tidArr as $t => $tArr){
					$goodArr[$t] = 0;
					if(isset($taxonArr['rankid']) && $taxonArr['rankid']){
						if($tArr['rankid'] == $taxonArr['rankid']){
							$goodArr[$t] = 1;
						}
					}
					//Gets a 2 points if family is the same
					if(isset($tArr['family']) && $tArr['family']){
						if(isset($taxonArr['family']) && $taxonArr['family']){
							if(strtolower($tArr['family']) == strtolower($taxonArr['family'])){
								$goodArr[$t] += 2;
							}
						}
						elseif($this->defaultFamily){
							if(strtolower($tArr['family']) == strtolower($this->defaultFamily)){
								$goodArr[$t] += 2;
							}
						}
					}
					//Gets a 2 points if kingdom is the same
					if($this->kingdomName && isset($tArr['kingdom']) && $tArr['kingdom']){
						if(strtolower($tArr['kingdom']) == strtolower($this->kingdomName)){
							$goodArr[$t] += 2;
						}
					}
					//Gets a 2 points if author is the same, 1 point if 80% similar
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

	private function getTidAccepted($tid){
		$retTid = 0;
		$sql = 'SELECT tidaccepted FROM taxstatus WHERE (taxauthid = '.$this->taxAuthId.') AND (tid = '.$tid.')';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			$retTid = $r->tidaccepted;
		}
		$rs->free();
		return $retTid;
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

	public function setTaxonomicResources($resourceArr){
		if(!$resourceArr){
			$this->logOrEcho('ERROR: Taxonomic Authority list not defined');
			return false;
		}
		if(!isset($GLOBALS['TAXONOMIC_AUTHORITIES']) || !$GLOBALS['TAXONOMIC_AUTHORITIES'] || !is_array($GLOBALS['TAXONOMIC_AUTHORITIES'])){
			$this->logOrEcho('ERROR activating Taxonomic Authority list (TAXONOMIC_AUTHORITIES) not configured correctly');
			return false;
		}
		$this->taxonomicResources = array_intersect_key(array_change_key_case($GLOBALS['TAXONOMIC_AUTHORITIES']),array_flip($resourceArr));
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