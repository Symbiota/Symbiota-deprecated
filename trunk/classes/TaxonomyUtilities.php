<?php
include_once($serverRoot.'/config/dbconnection.php');
include_once($serverRoot.'/classes/OccurrenceUtilities.php');

class TaxonomyUtilities{

	private $conn;
	private $taxonThesOrder = array('eol','col');
	private $errorStr;
	private $warningArr = array();

	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}
	
	function __destruct(){
		if($this->conn) $this->conn->close();
	}

	//Taxonomy API methods
	/*
	 * Output: Array with keys ('sciname', 'scientificName', 'rankid', 'unitname1', 'unitname2', 'unitind3', 'unitname3', 'author') 
	 * 
	 */ 
	public function initSourceOrder(){
		if(isset($GLOBALS['taxonThesaurusOrder']) && $GLOBALS['taxonThesaurusOrder']){
			$this->taxonThesOrder = explode(',',$taxonThesOrder);
		}
	}

	public function getEolTaxonArr($sciName){
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
								//Set rankID
								$sqlRank = 'SELECT rankid FROM taxonunits WHERE rankname = "'.$eolArr['taxonRank'].'"';
								$rsRank = $this->conn->query($sqlRank);
								$rankId = 0;
								if($rRank = $rsRank->fetch_object()){
									$rankId = $rRank->rankid;
								}
								else{
									$this->warningArr[] = "Unable to determine rankid for: ".$eolArr['taxonRank'];
								}
								$rsRank->free();
								//Parse scientific name
								$taxonArr = $this->parseSciName($scientificName,$rankId);
								if($scientificName != $taxonArr['sciname']) $taxonArr['scientificName'] = $scientificName;

								//Add vernaculars
								$vernacularNames = $eolArr['vernacularNames'];
								foreach($vernacularNames as $vernArr){
									if($vernArr['language'] == 'en') $taxonArr['vern'][] = $vernArr['vernacularName']; 
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
							//if($taxonArr) $this->loadNewTaxon($taxonArr);
						}
						else{
							$this->errorStr = "Unable to get taxon object for: ".$sciName;
							return false;
						}
					}
				}
				else{
					$this->errorStr = "Unable to get ID for: ".$sciName;
					return false;
				}
			}
			else{
				$this->errorStr = "EOL web service not available";
				return false;
			}
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
	private function loadNewTaxon($taxonArr,$anchorTid = 0){
		$status = false;
		if(!isset($taxonArr['sciname']) || !$taxonArr['sciname']){
			$this->errorStr = 'ERROR: sciname not defined';
			return false;
		}
		//Get tid 
		
		//
		if(!isset($taxonArr['rankid']) || !$taxonArr['rankid']){
			$this->errorStr = 'ERROR loading '.$taxonArr['sciname'].', rankid not defined ('.$newTaxon['rankid'].')';
			return false;
		}
		if(!array_key_exists('parent',$newTaxon) || !$newTaxon['parent']){
			$this->errorStr = 'ERROR loading '.$taxonArr['sciname'].', parent not defined';
			return false;
		}
		$parentName = $newTaxon['parent'];
		//Get parent tid
		$sqlParent = 'SELECT tid FROM taxa WHERE (sciname = "'.$parentName.'")';
		$rs = $this->conn->query($sqlParent);
		$parTid = $rs->tid;
		if(!$parTid){
			$parTid = $loadNewTaxon(Array('name' => $parentName));
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

	//Misc functions
	public static function parseSciName($scientificName,$rankId = 0){
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
	
	public function getErrorStr(){
		return $this->errorStr;
	}
}
?>