<?php
/*
 * Created on 24 Feb 2011
 * E.E. Gilbert
 */

include_once($serverRoot.'/config/dbconnection.php');
  
class SpecTaxCleanerManager{

	private $conn;
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
		if($collId) $sql .= 'AND o.collid = '.$collId;
		$this->conn->query($sql);
	}

	public function verifyCollNames($collId){
		//Grab list of taxa, check each one, add valid taxa to taxonomic thesaurus, return number added and number problematic remaining
		$this->verifySpecies2000("Berberis repens");
		return;
		
		$numGood = 0;
		$numBad = 0;
		$sql = 'SELECT DISTINCT o.sciname FROM omoccurrences o '.
			'WHERE o.tidinterpreted IS NULL AND o.sciname IS NOT NULL ';
		if($collId) $sql .= 'AND o.collid = '.$collId.' '; 
		$sql .= 'ORDER BY o.sciname LIMIT 1';
		//echo '<div>'.$sql.'</div>';
		$rs = $this->conn->query($sql);
		while($r = $rs->fetch_object()){
			if($r->sciname){
				if($this->verifySpecies2000($r->sciname)){
					$numGood++;
				}
				else{
					$numBad++;
				}
			}
		}
		$rs->close();
		$retArr['good'] = $numGood;
		$retArr['bad'] = $numBad;
		return $retArr;
	}

	public function verifyExistingNames(){
		$urlTemplate = "http://www.catalogueoflife.org/annual-checklist/2010/webservice?format=php&response=full&name=";
		//Check accepted taxa first
		$sql = 'SELECT t.sciname, t.tid, t.author FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
			'WHERE ts.taxauthid = '.$taxAuthId.' AND ts.tid = ts.tidaccepted '; 
		if($this->testValidity){
			$sql .= 'AND t.validitystatus IS NULL ';
		}
		$sql .= 'LIMIT 1';
		//echo '<div>'.$sql.'</div>';
		while($rs = $this->conn->query($sql)){
			while($taxonArr = $rs->fetch_assoc()){
				
				$this->verifySpecies2000($taxonArr,$taxonArr['tid']);
				
			}
			$rs->close();
		}
	}
	
	private function verifySpecies2000($taxonArr, $tidAnchor){
		$urlTemplate = "http://www.catalogueoflife.org/annual-checklist/2010/webservice?format=php&response=full&name=";
		$url = $urlTemplate.str_replace(" ","%20",$taxonArr['sciName']);
		if($fh = fopen($url, 'r')){
			echo "<div>Reading page for ".$taxonArr['sciName']." </div>\n";
			$content = "";
			while($line = fread($fh, 1024)){
				$content .= trim($line);
			}
			//Process return
			$retArr = unserialize($content);
			$numResults = $retArr['number_of_results_returned'];
			$resultArr = array_shift($retArr['results']);
			$source = $resultArr['source_database'];
			//Set validitystatus of name
			if($this->testValidity){
				$sql = 'UPDATE taxa SET validitystatus = '.($numResults?'1':'0').', validitysource = "'.$source.'" WHERE tid = '.$taxonArr['tid'];
				$this->conn->query($sql);
			}
			//Check author
			if($this->checkAuthor){
				if($resultArr['author'] && $taxonArr['author'] != $resultArr['author']){
					$sql = 'UPDATE taxa SET author = '.$resultArr['author'].' WHERE tid = '.$taxonArr['tid'];
					$this->conn->query($sql);
				}
			}
			//Test taxonomy
			if($this->testTaxonomy){
				$nameStatus = $resultArr['name_status'];
				if($taxonArr['tid'] == $tidAnchor){		//Is accepted within system
					if($this->verificationMode === 0){		//default to system taxonomy
						if($nameStatus == 'accepted'){
							//Go through synonyms and check each
							
						}
						elseif($nameStatus == 'synonym'){
							//
							
						}
	
					}
					elseif($this->verificationMode == 1){	//default to taxonomy of external site
						if($nameStatus == 'accepted'){
							//Go through synonyms and check each
							
						}
						elseif($nameStatus == 'synonym'){
							//Get accepted name 
							//change to accepted
						}
	
					}
				}
				else{			//Is not accepted within system
					if($this->verificationMode === 0){		//default to system taxonomy
						if($nameStatus == 'accepted'){
							//Go through synonyms and check each
							
						}
						elseif($nameStatus == 'synonym'){
							//
							
						}
	
					}
					elseif($this->verificationMode == 1){	//default to taxonomy of external site
						if($nameStatus == 'accepted'){
							//Go through synonyms and check each
							
						}
						elseif($nameStatus == 'synonym'){
							//Get accepted name 
							//change to accepted
						}
	
					}
				}
			}
			fclose($fh);
		}
	}

	private function verifyTropicos($sciName){
		$urlTemplate = "http://www.catalogueoflife.org/annual-checklist/2010/webservice?format=php&response=full&name=";
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
				$sql = "UPDATE t_fna SET fnaid = ".$matches[1][0]." WHERE fnaid IS NULL AND pk = ".$pk;
				$this->conn->query($sql);
			}
		}
		flush();
		sleep(5);
	}
	
	private function getHierarchy($tid){
		$parentArr = Array($tid);
		$parCnt = 0;
		$targetTid = $tid;
		do{
			$sqlParents = "SELECT IFNULL(ts.parenttid,0) AS parenttid FROM taxstatus ts WHERE ts.tid = ".$targetTid;
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

	public function getCollectionList($collId,$userRights){
		$returnArr = Array();
		$isAdmin = array_key_exists("SuperAdmin",$userRights);
		$targetIds = Array();
		if(!$isAdmin){
			if(array_key_exists("CollAdmin",$userRights)){
				$targetIds = $userRights["CollAdmin"];
			}
			if(array_key_exists("CollEditor",$userRights)){
				$targetIds = array_merge($targetIds,$userRights["CollEditor"]);
			}
			if(!$targetIds) return;
			if($collId && !in_array($collId,$targetIds)) return; 
		}
		$sql = 'SELECT c.collid, c.institutioncode, c.collectioncode, c.collectionname '.
			'FROM omcollections c ';
		if($collId && $collId <> 'all'){
			$sql .= 'WHERE collid = '.$collId.' ';
		}
		elseif($targetIds){
			$sql .= 'WHERE collid IN('.implode(',',$targetIds).') ';
		}
		$sql .= 'ORDER BY c.SortSeq,c.CollectionName';
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$cName = $row->collectionname;
			if($row->institutioncode) $cName .= ' ('.$row->institutioncode.($row->collectioncode?':'.$row->collectioncode:'').')'; 
			$returnArr[$row->collid] = $cName;
		}
		$rs->close();
		return $returnArr;
	}
}
?>