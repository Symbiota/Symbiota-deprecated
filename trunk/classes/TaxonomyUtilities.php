<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/OccurrenceUtilities.php');
include_once($serverRoot.'/classes/EOLUtilities.php');

class TaxonomyUtilities{

	private $conn;
	private $taxAuthId = 1;
	private $kingdomId;
	private $activeThesResources = 'eol,col,trop';
	
	private $errorStr = '';
	private $warningArr = array();

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	function __destruct(){
		if($this->conn) $this->conn->close();
	}

	public function initSourceOrder(){
		if(isset($GLOBALS['taxonThesauri']) && $GLOBALS['taxonThesauri']){
			$this->activeThesResources = explode(',',$GLOBALS['taxonThesauri']);
		}
	}

	public function addSciname($term){
		//Get taxon object
		$taxonArr = Array();
		if(!$this->activeThesResources){
			$this->errorStr = 'ERROR: Taxonomic resources not activated ';
			return false;
		}
		$resourceArr = explode(',',$this->activeThesResources);
		$attemptedResources = '';
		foreach($resourceArr as $resStr){
			if($resStr == 'eol'){
				$taxonArr = $this->getEolTaxonArr($term);
				$attemptedResources .= ', eol';
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
			$processedTaxonArr = $this->loadNewTaxon($taxonArr);
		}
		else{
			$this->errorStr = 'ERROR: unable to obtain taxon object from: '.trim($attemptedResources,' ,');
			return false;
		} 
		return $processedTaxonArr;
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
	public function getEolTaxonArr($term){
		$taxonArr = array();
		$eolManager = new EOLUtilities();
		if($eolManager->pingEOL()){
			$eolTaxonId = 0;
			if(is_numeric($term)) $eolTaxonId = $term;
			if(!$eolTaxonId){
				$searchRet = $eolManager->searchEOL($term);
				if(isset($searchRet['id'])){
					$eolTaxonId = $searchRet['id'];
				}
			}
			if($eolTaxonId){
				$taxonArr = $eolManager->getHierarchyEntries($searchRet['id']);
				if($taxonArr && !isset($taxonArr['rankid'])){
					$rankid = $this->getRankid($taxonArr['taxonRank']);
					if($rankid){
						$taxonArr['rankid'] = $rankid;
						if(!$this->kingdomId){
							if(isset($taxonArr['parents'])){
								foreach($taxonArr['parents'] as $parArr){
									if(isset($parArr['taxonRank']) && $parArr['taxonRank'] == 'kingdom'){
										switch($parArr['scientificName']){
											case 'plantae':
												$this->kingdomId = 3;
												break;
											case 'fungi':
												$this->kingdomId = 4;
												break;
											case 'animalia':
												$this->kingdomId = 5;
												break;
										}
									}
								}
							}
						}
					}
				}
			}
			else{
				$this->errorStr = 'ERROR getting EOL taxon object ID (term: '.$term.')';
				return false;
			}
		}
		else{
			$this->errorStr = 'EOL web services are not available ';
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
		//
		if(!$this->kingdomId){
			$this->errorStr = 'ERROR loading '.$taxonArr['sciname'].', kingdomId is not set';
			return false; 
		}
		if(!isset($taxonArr['sciname']) || !$taxonArr['sciname']){
			$this->errorStr = 'ERROR: sciname not defined';
			return false;
		}
		if(!isset($taxonArr['rankid']) || !$taxonArr['rankid']){
			$this->errorStr = 'ERROR loading '.$taxonArr['sciname'].', rankid not defined';
			return false;
		}
		if(!array_key_exists('parent',$taxonArr) || !$taxonArr['parent']){
			$this->errorStr = 'ERROR loading '.$taxonArr['sciname'].', parent not defined';
			return false;
		}
		$newTid = 0;

		$parentTid = getParentTid($taxonArr['parent']);
		if(!$parentTid){
			$this->errorStr = 'ERROR loading '.$taxonArr['sciname'].', unable to get parentTid';
			return false;
		}
		
		//We now have everything, now let's load
		//Check to see sciname is in taxon table but not under selected thesaurus
		$sql = 'SELECT tid FROM taxa WHERE sciname = "'.$taxonArr.'"';
		$rs = $this->conn->query($sql);
		if($r = $rs->fetch_object()){
			$newTid = $r->tid;
		}
		$rs->free();
		
		if(!$newTid){
			$sqlInsert = 'INSERT INTO taxa(sciname, unitind1, unitname1, unitind2, unitname2, unitind3, unitname3, author, rankid, kingdomid, source) '.
				'VALUES("'.$taxonArr['sciname'].'","'.$taxonArr['genus'].'","'.$newTaxon['species'].'","'.$newTaxon['infraspecies_marker'].'","'.
				$newTaxon['infraspecies'].'","'.$newTaxon['author'].'",'.$rankId.')';
			if($this->conn->query($sqlInsert)){
				$newTid = $this->conn->insert_id;
			}
			else{
				$this->errorStr = 'ERROR inserting '.$taxonArr['sciname'].': '.$this->conn->error;
				return false;
			}
		}
		if($newTid){
			if(!$tidAccepted) $tidAccepted = $newTid;
			$sqlInsert2 = 'INSERT INTO taxstatus(tid,tidaccepted,taxauthid,parenttid) '.
				'VALUES('.$newTid.','.$tidAccepted.','.$this->taxAuthId.','.$parentTid.')';
			if($this->conn->query($sqlInsert2)){
				//Add common names

				
				//Add Synonyms

				
			}
			
		}
		return $newTid;
	}
	
	private function getRankid($rankStr){
		if($rankStr){
			$sqlRank = 'SELECT rankid FROM taxonunits WHERE rankname = "'.$rankStr.'"';
			$rsRank = $this->conn->query($sqlRank);
			$rankId = 0;
			if($rRank = $rsRank->fetch_object()){
				$rankId = $rRank->rankid;
			}
			else{
				$this->warningArr[] = "Unable to determine rankid from: ".$rankStr;
			}
			$rsRank->free();
		}
	}
	
	private function getParentTid($parentArr){
		$sciname = $parentArr['scientificName'];
		$sql = 'SELECT t.tid '.
			'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE (t.sciname = "'.$this->cleanInStr($parentArr['scientificName']).'") AND (ts.taxauthid = '.$this->taxAuthId.')';
		$rs = $this->conn->query($sql);
		$tid = 0;
		if($row = $rs->fetch_object()){
			$tid = $row->tid;
		}
		$rs->free();
		if(!$tid){
			$parentTaxonArr = $this->addSciname($parentName);
			$parentTid = $loadNewTaxon(Array('name' => $parentName));
		}

		$parentStr = $parentArr['sciname'];
		
	}

	//Misc functions
	public function parseSciName($scientificName,$rankId = 0){
		//Converts scinetific name with author embedded into separate fields
		$retArr = array();
		$retArr = OccurrenceUtilities::parseScientificName($scientificName,$rankId);		
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

	//Taxonomic indexing functions
	public function buildHierarchyEnumTree($taxAuthId = 1){
		set_time_limit(600);
		$status = true;
		//Seed taxaenumtree table
		$sql = 'INSERT INTO taxaenumtree(tid,parenttid,taxauthid) '.
			'SELECT DISTINCT ts.tid, ts.parenttid, ts.taxauthid '. 
			'FROM taxstatus ts '. 
			'WHERE (ts.taxauthid = '.$taxAuthId.') AND ts.tid NOT IN(SELECT tid FROM taxaenumtree WHERE taxauthid = '.$taxAuthId.')';
		//echo $sql;
		if(!$this->conn->query($sql)){
			$status = false;
			$this->errorStr = 'ERROR seeding taxaenumtree: '.$this->conn->error;
		}
		if($status){
			//Continue building taxaenumtree  
			$sql2 = 'SELECT DISTINCT e.tid, ts.parenttid, ts.taxauthid '. 
				'FROM taxaenumtree e INNER JOIN taxstatus ts ON e.parenttid = ts.tid AND e.taxauthid = ts.taxauthid '.
				'LEFT JOIN taxaenumtree e2 ON e.tid = e2.tid AND ts.parenttid = e2.parenttid AND e.taxauthid = e2.taxauthid '.
				'WHERE (ts.taxauthid = '.$taxAuthId.') AND e2.tid IS NULL';
			//echo $sql;
			$cnt = 0;
			$targetCnt = 0;
			do{
				if(!$this->conn->query('INSERT INTO taxaenumtree(tid,parenttid,taxauthid) '.$sql2)){
					$status = false;
					$this->errorStr = 'ERROR building taxaenumtree: '.$this->conn->error;
				}
				$rs = $this->conn->query($sql2);
				$targetCnt = $rs->num_rows;
				$cnt++;
			}while($status && $targetCnt && $cnt < 30);
		}
		return $status;
	}
	
	public function buildHierarchyNestedTree($taxAuthId = 1){
		set_time_limit(1200);
		//Get root and then build down
		$startIndex = 1;
		$rankId = 0;
		$sql = 'SELECT ts.tid, t.rankid '.
			'FROM taxstatus ts INNER JOIN taxa t ON ts.tid = t.tid '.
			'WHERE ts.taxauthid = '.$taxAuthId.' AND (ts.parenttid IS NULL OR ts.parenttid = ts.tid) '.
			'ORDER BY t.rankid ';
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				if($rankId && $rankId <> $r->rankid) break;
				$rankId = $r->rankid;
				$startIndex = $this->loadTaxonIntoNestedTree($r->tid, $taxAuthId, $startIndex);
			}
			$rs->close();
		}
	}
	
	private function loadTaxonIntoNestedTree($tid, $taxAuthId, $startIndex){
		$endIndex = $startIndex + 1;
		$sql = 'SELECT tid '.
			'FROM taxstatus '.
			'WHERE taxauthid = '.$taxAuthId.' AND parenttid = '.$tid;
		if($rs = $this->conn->query($sql)){
			while($r = $rs->fetch_object()){
				$endIndex = $this->loadTaxonIntoNestedTree($r->tid, $taxAuthId, $endIndex);
			}
			$rs->close();
		}
		//Load into taxanestedtree
		$sqlInsert = 'REPLACE INTO taxanestedtree(tid,taxauthid,leftindex,rightindex) '.
			'VALUES ('.$tid.','.$taxAuthId.','.$startIndex.','.$endIndex.')';
		$this->conn->query($sqlInsert);
		//Return endIndex plus one
		$endIndex++;
		return $endIndex;
	}

	//Setters and getters
	public function setTaxAuthId($id){
		if($id && is_numeric($id)){
			$this->taxAuthId = $id;
		}
	}

	public function setKingdomId($id){
		if($id && is_numeric($id)){
			$this->kingdomId = $id;
		}
	}

	public function setActiveThesResources($t){
		$this->activeThesResources = $t;
	}

	public function getErrorStr(){
		return $this->errorStr;
	}
}
?>