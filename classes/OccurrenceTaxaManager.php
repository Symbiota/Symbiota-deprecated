<?php
include_once($SERVER_ROOT.'/content/lang/collections/harvestparams.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');

abstract class TaxaSearchType {
	const  ANY_NAME				= 1;
	const  SCIENTIFIC_NAME		= 2;
	const  FAMILY_ONLY			= 3;
	const  TAXONOMIC_GROUP		= 4;
	const  COMMON_NAME			= 5;

	public static $_list		   = array(1,2,3,4,5);

	public static function anyNameSearchTag ( $taxaSearchType ) {
		global $LANG;
		$key = 'SELECT_1-'.$taxaSearchType;
		if (array_key_exists($key,$LANG)) {
			return $LANG[$key];
		}
		return "Unsupported";
	}

	public static function taxaSearchTypeFromAnyNameSearchTag ( $searchTag ) {
		foreach (TaxaSearchType::$_list as $taxaSearchType) {
			if (TaxaSearchType::anyNameSearchTag($taxaSearchType) == $searchTag) {
				return $taxaSearchType;
			}
		}
		return 3;
	}
}

class OccurrenceTaxaManager {

	protected $conn	= null;
	protected $taxaArr = array();
	protected $taxAuthId = 1;

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
	}
	public function __destruct(){
		if ((!($this->conn === false)) && (!($this->conn === null))) {
			$this->conn->close();
			$this->conn = null;
		}
	}

	protected function setTaxonRequestVariable($inputArr = null){
		//Set usage of taxonomic thesaurus
		$this->taxaArr['usethes'] = 0;
		if(isset($inputArr['usethes']) && $inputArr['usethes']){
			$this->taxaArr['usethes'] = 1;
		}
		elseif(array_key_exists('usethes',$_REQUEST) && $_REQUEST['usethes']){
			$this->taxaArr['usethes'] = 1;
		}
		//Set default taxa type
		$defaultTaxaType = TaxaSearchType::SCIENTIFIC_NAME;
		if(isset($inputArr['taxontype']) && is_numeric($inputArr['taxontype'])){
			$defaultTaxaType = $inputArr['taxontype'];
		}
		elseif(array_key_exists('taxontype',$_REQUEST) && is_numeric($_REQUEST['taxontype'])){
			$defaultTaxaType = $_REQUEST['taxontype'];
		}
		$this->taxaArr['taxontype'] = $defaultTaxaType;
		//Set taxa search terms
		if(isset($inputArr['taxa']) && $inputArr['taxa']){
			$taxaStr = $this->cleanInputStr($inputArr["taxa"]);
		}
		else{
			$taxaStr = str_replace(",",";",$this->cleanInputStr($_REQUEST["taxa"]));
		}
		$this->taxaArr['search'] = $taxaStr;
		//Initerate through taxa and process
		$taxaArr = explode(";",$taxaStr);
		foreach($taxaArr as $term){
			$searchTerm = trim($term);
			$taxaType = $defaultTaxaType;
			if($defaultTaxaType == TaxaSearchType::ANY_NAME) {
				$n = explode(': ',$searchTerm);
				if (count($n) > 1) {
					$taxaType = TaxaSearchType::taxaSearchTypeFromAnyNameSearchTag($n[0]);
					$searchTerm = $n[1];
				}
				else{
					$taxaType = TaxaSearchType::SCIENTIFIC_NAME;
				}
			}
			if($taxaType == TaxaSearchType::COMMON_NAME){
				$searchTerm = ucfirst($searchTerm);
				$this->setSciNamesByVerns($searchTerm);
			}
			else{
				$sql = 'SELECT t.sciname, t.tid, t.rankid FROM taxa t ';
				if(is_numeric($searchTerm)){
					if($this->taxaArr['usethes']){
						$sql .= 'INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (ts.tid = '.$searchTerm.')';
					}
					else{
						$sql .= 'WHERE (t.tid = '.$searchTerm.')';
					}
				}
				else{
					if($this->taxaArr['usethes']){
						$sql .= 'INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted '.
							'INNER JOIN taxa t2 ON ts.tid = t2.tid '.
							'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (t2.sciname IN("'.$this->cleanInStr($searchTerm).'"))';
					}
					else{
						$sql .= 'WHERE t.sciname IN("'.$this->cleanInStr($searchTerm).'")';
					}
				}
				$rs = $this->conn->query($sql);
				if($rs->num_rows){
					while($r = $rs->fetch_object()){
						$this->taxaArr['taxa'][$r->sciname]['tid'][$r->tid] = $r->rankid;
						if($r->rankid == 140){
							$taxaType = TaxaSearchType::FAMILY_ONLY;
						}
						elseif($r->rankid < 180){
							$taxaType = TaxaSearchType::TAXONOMIC_GROUP;
						}
						else{
							$taxaType = TaxaSearchType::SCIENTIFIC_NAME;
						}
						$this->taxaArr['taxa'][$r->sciname]['taxontype'] = $taxaType;
					}
				}
				else{
					$this->taxaArr['taxa'][$searchTerm]['taxontype'] = $taxaType;
				}
				$rs->free();
			}
		}
		if($this->taxaArr['usethes']){
			$this->setSynonyms();
		}
	}

	private function setSciNamesByVerns($termStr) {
		$sql = 'SELECT DISTINCT v.VernacularName, t.tid, t.sciname, t.rankid '.
			'FROM taxstatus ts INNER JOIN taxavernaculars v ON ts.TID = v.TID '.
			'INNER JOIN taxa t ON t.TID = ts.tidaccepted '.
			'WHERE (ts.taxauthid = '.$this->taxAuthId.') AND (v.VernacularName IN("'.$termStr.'")) ORDER BY t.rankid LIMIT 20';
		//echo "<div>sql: ".$sql."</div>";
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			//$vernName = strtolower($row->VernacularName);
			$vernName = $row->VernacularName;
			if($row->rankid == 140){
				$this->taxaArr['taxa'][$vernName]["families"][] = $row->sciname;
			}
			else{
				$this->taxaArr['taxa'][$vernName]["scinames"][] = $row->sciname;
			}
			$this->taxaArr['taxa'][$vernName]["tid"][$row->tid] = $row->rankid;
		}
		$rs->free();
	}

	private function setSynonyms(){
		if(isset($this->taxaArr['taxa'])){
			foreach($this->taxaArr['taxa'] as $searchStr => $searchArr){
				if(isset($searchArr['tid']) && $searchArr['tid']){
					foreach($searchArr['tid'] as $tid => $rankid){
						$accArr[] = $tid;
						if($rankid == 220){
							//Get accepted children
							$sql1 = 'SELECT DISTINCT t.tid, t.sciname, t.rankid '.
								'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
								'WHERE (ts.parenttid IN('.$tid.')) AND (ts.TidAccepted = ts.tid) AND (ts.taxauthid = ' . $this->taxAuthId . ') ' ;
							$rs1 = $this->conn->query($sql1);
							while($r1 = $rs1->fetch_object()){
								$accArr[] = $r1->tid;
								if(!isset($this->taxaArr['taxa'][$r1->sciname])) $this->taxaArr['taxa'][$r1->sciname]['tid'][$r1->tid] = $r1->rankid;
							}
							$rs1->free();
						}
						//Get synonyms of all accepted taxa
						$sql2 = 'SELECT DISTINCT t.tid, t.sciname, t2.sciname as accepted '.
							'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid '.
							'INNER JOIN taxa t2 ON ts.tidaccepted = t2.tid '.
							'WHERE (ts.TidAccepted != ts.tid) AND (ts.taxauthid = '.$this->taxAuthId.') AND (ts.tidaccepted IN('.implode(',',$accArr).')) ';
						$rs2 = $this->conn->query($sql2);
						while($r2 = $rs2->fetch_object()) {
							$this->taxaArr['taxa'][$r2->accepted]['synonyms'][$r2->tid] = $r2->sciname;
						}
						$rs2->free();
					}
				}
			}
		}
	}

	protected function getTaxonWhereFrag(){
		$sqlWhereTaxa = '';
		if(isset($this->taxaArr['taxa'])){
			foreach($this->taxaArr['taxa'] as $searchTaxon => $searchArr){
				$taxonType = $this->taxaArr['taxontype'];
				if(isset($searchArr['taxontype'])) $taxonType = $searchArr['taxontype'];
				if($taxonType == TaxaSearchType::TAXONOMIC_GROUP){
					//Class, order, or other higher rank
					if(isset($searchArr['tid'])){
						$tidArr = array_keys($searchArr['tid']);
						//$sqlWhereTaxa .= 'OR (o.tidinterpreted IN(SELECT DISTINCT tid FROM taxaenumtree WHERE (taxauthid = '.$this->taxAuthId.') AND (parenttid IN('.trim($tidStr,',').') OR (tid = '.trim($tidStr,',').')))) ';
						$sqlWhereTaxa .= 'OR ((e.taxauthid = '.$this->taxAuthId.') AND (e.parenttid IN('.implode(',', $tidArr).') OR (o.tidinterpreted = '.implode(',', $tidArr).'))) ';
					}
				}
				elseif($taxonType == TaxaSearchType::FAMILY_ONLY){
					//$sqlWhereTaxa .= 'OR ((o.family = "'.$searchTaxon.'") OR (o.sciname = "'.$searchTaxon.'")) ';
					$sqlWhereTaxa .= 'OR (((ts.family = "'.$searchTaxon.'") AND (ts.taxauthid = '.$this->taxAuthId.')) OR (o.family = "'.$searchTaxon.'") OR (o.sciname = "'.$searchTaxon.'")) ';
				}
				else{
					if($taxonType == TaxaSearchType::COMMON_NAME){
						//Common name search
						$famArr = array();
						if(array_key_exists("families",$searchArr)){
							$famArr = $searchArr["families"];
						}
						if(array_key_exists("tid",$searchArr)){
							$tidArr = array_keys($searchArr['tid']);
							$sql = 'SELECT DISTINCT t.sciname '.
	   							'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
	   							'WHERE (t.rankid = 140) AND (e.taxauthid = '.$this->taxAuthId.') AND (e.parenttid IN('.implode(',',$tidArr).'))';
							$rs = $this->conn->query($sql);
							while($r = $rs->fetch_object()){
								$famArr[] = $r->sciname;
							}
							$rs->free();
							$sqlWhereTaxa .= 'OR (o.tidinterpreted IN('.implode(',',$tidArr).')) ';
						}
						if($famArr){
							$famArr = array_unique($famArr);
							$sqlWhereTaxa .= 'OR (o.family IN("'.implode('","',$famArr).'")) ';
						}
					}
					else{
						$term = $searchTaxon;
						if(stripos($term,'x') || !mb_check_encoding($term, 'ASCII')) $term = preg_replace('/\s{1}\D{1,2}\s{1}/', ' _ ', $term);
						if(array_key_exists("tid",$searchArr)){
							$rankid = current($searchArr['tid']);
							$tidArr = array_keys($searchArr['tid']);
							$sqlWhereTaxa .= "OR (o.tidinterpreted IN(".implode(',',$tidArr).")) ";
							//Return matches that are not linked to thesaurus
							if($rankid == 180) $sqlWhereTaxa .= "OR (o.sciname LIKE '".$this->cleanInStr($term)."%') ";
						}
						else{
							$term = $this->cleanInStr(trim($term,'%'));
							//Protect against someone trying to download big pieces of the occurrence table through the user interface
							if(strlen($term) < 4) $term .= ' ';
							//Return matches for "Pinus a"
							$sqlWhereTaxa .= "OR (o.sciname LIKE '".$term."%') ";
						}
					}
					if(array_key_exists("synonyms",$searchArr)){
						$synArr = $searchArr["synonyms"];
						if($synArr){
							if($taxonType == TaxaSearchType::SCIENTIFIC_NAME || $taxonType == TaxaSearchType::COMMON_NAME){
								foreach($synArr as $synTid => $sciName){
									if(strpos($sciName,'aceae') || strpos($sciName,'idae')){
										$sqlWhereTaxa .= "OR (o.family = '".$sciName."') ";
									}
								}
							}
							$sqlWhereTaxa .= 'OR (o.tidinterpreted IN('.implode(',',array_keys($synArr)).')) ';
						}
					}
				}
			}
		}
		if($sqlWhereTaxa) return "AND (".substr($sqlWhereTaxa,3).") ";
		else return false;
	}

	//setters and getters
	public function setTaxAuthId($id){
		if(is_numeric($id)) $this->taxAuthId = $id;
	}

	//Misc functions
	public function getTaxaSearchStr(){
		$returnArr = Array();
		if(isset($this->taxaArr['taxa'])){
			foreach($this->taxaArr['taxa'] as $taxonName => $taxonArr){
				$str = '';
				if($this->taxaArr["taxontype"] == TaxaSearchType::ANY_NAME) $str .= TaxaSearchType::anyNameSearchTag($taxonArr["taxontype"]).": ";
				$str .= $taxonName;
				if(array_key_exists("scinames",$taxonArr)){
					$str .= " => ".implode(",",$taxonArr["scinames"]);
				}
				if(array_key_exists("synonyms",$taxonArr)){
					$str .= " (".implode(", ",$taxonArr["synonyms"]).")";
				}
				$returnArr[] = $str;
			}
		}
		return implode(", ", $returnArr);
	}

	protected function cleanOutStr($str){
		return htmlspecialchars($str);
	}

	protected function cleanInputStr($str){
		if(stripos($str, 'sleep(') !== false) return '';
		return strip_tags(trim($str));
	}

	protected function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>