<?php
include_once($SERVER_ROOT.'/config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/harvestparams.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');

abstract class TaxaSearchType {
	const  SCIENTIFIC_NAME		= 0;
	const  ANY_NAME				= 1;
	const  SPECIES_NAME_ONLY	= 2;
	const  HIGHER_TAXONOMY		= 3;
	const  COMMON_NAME			= 4;

	public static $_list		   = array(0,1,2,3,4);

	private static function getLangStr ( $keyPrefix, $idx ) {
		global $LANG;
		$key = $keyPrefix.$idx;
		if (array_key_exists($key,$LANG)) {
			return $LANG[$key];
		}
		return "Unsupported";

	}
	public static function selectionPrompt ( $taxaSearchType ) {
		return TaxaSearchType::getLangStr('SELECT_1-',$taxaSearchType);
	}
	public static function anyNameSearchTag ( $taxaSearchType ) {
		return TaxaSearchType::getLangStr('TSTYPE_1-',$taxaSearchType);
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

class TaxonSearchManager {

	protected $conn	= null;
	protected $taxaArr = array();
	protected $searchTermArr = Array();

	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
	}
	public function __destruct(){
		if ((!($this->conn === false)) && (!($this->conn === null))) {
			$this->conn->close();
			$this->conn = null;
		}
	}

	protected function getTaxonWhereFrag(){
		$sqlRet = "";
		if(array_key_exists("taxa",$this->searchTermArr)){
			$this->setTaxaArr();
			$sqlWhereTaxa = '';
			foreach($this->taxaArr as $searchTaxon => $valueArray){
				$tempTaxonType = $this->searchTermArr['taxontype'];
				if($tempTaxonType != TaxaSearchType::COMMON_NAME){
					$sql = 'SELECT sciname, tid, rankid FROM taxa WHERE sciname IN("'.$this->cleanInStr($searchTaxon).'")';
					$rs = $this->conn->query($sql);
					while($r = $rs->fetch_object()){
						$this->taxaArr[$r->sciname]['tid'][] = $r->tid;
						$this->taxaArr[$r->sciname]['rankid'] = $r->rankid;
						if($tempTaxonType == TaxaSearchType::SCIENTIFIC_NAME){
							if($r->rankid > 179){
								$tempTaxonType= TaxaSearchType::SPECIES_NAME_ONLY;
							}elseif($r->rankid < 180){
								$tempTaxonType= TaxaSearchType::HIGHER_TAXONOMY;
							}
						}
					}
					$rs->free();
				}
				if($tempTaxonType == TaxaSearchType::HIGHER_TAXONOMY){
					//Class, order, or other higher rank
					if(isset($valueArray['tid'])){
						$rs1 = $this->conn->query('SELECT DISTINCT tidaccepted FROM taxstatus WHERE (taxauthid = 1) AND (tid IN('.implode(',',$valueArray['tid']).'))');
						$tidStr = '';
						while($r1 = $rs1->fetch_object()){
							$tidStr = $r1->tidaccepted.',';
						}
						$sqlWhereTaxa = 'OR (o.tidinterpreted IN(SELECT DISTINCT tid FROM taxaenumtree '.
							'WHERE taxauthid = 1 AND (parenttid IN('.trim($tidStr,',').') OR (tid = '.trim($tidStr,',').')))) ';
					}
				}
				else{
					if($tempTaxonType == TaxaSearchType::COMMON_NAME){
						//Common name search
						$famArr = array();
						if(array_key_exists("families",$valueArray)){
							$famArr = $valueArray["families"];
						}
						if(array_key_exists("tid",$valueArray)){
							$tidArr = $valueArray['tid'];
							$sql = 'SELECT DISTINCT t.sciname '.
	   							'FROM taxa t INNER JOIN taxaenumtree e ON t.tid = e.tid '.
	   							'WHERE t.rankid = 140 AND e.taxauthid = 1 AND e.parenttid IN('.implode(',',$tidArr).')';
							$rs = $this->conn->query($sql);
							while($r = $rs->fetch_object()){
								$famArr[] = $r->sciname;
							}
						}
						if($famArr){
							$famArr = array_unique($famArr);
							$sqlWhereTaxa .= 'OR (o.family IN("'.implode('","',$famArr).'")) ';
						}
						if(array_key_exists("scinames",$valueArray)){
							foreach($valueArray["scinames"] as $sciName){
								$sqlWhereTaxa .= "OR (o.sciname Like '".$sciName."%') ";
							}
						}
					}
					else{
						$sqlWhereTaxa .= "OR (o.sciname LIKE '".$this->cleanInStr($searchTaxon)."%') ";
						if(array_key_exists("tid",$valueArray)){
							$sqlWhereTaxa .= "OR (o.tidinterpreted IN(".implode(',',$valueArray['tid']).")) ";
						}
					}
					if(array_key_exists("synonyms",$valueArray)){
						$synArr = $valueArray["synonyms"];
						if($synArr){
							if($tempTaxonType == TaxaSearchType::SCIENTIFIC_NAME || $tempTaxonType == TaxaSearchType::COMMON_NAME){
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
			$sqlRet .= "AND (".substr($sqlWhereTaxa,3).") ";
		}
		return $sqlRet;
	}

	public function setTaxaArr() {
		$taxaSearchTerms = explode(";",$this->searchTermArr["taxa"]);
		foreach ($taxaSearchTerms as $taxaSearchTerm) {
			$taxaSearchType = $this->searchTermArr["taxontype"];
			if ($taxaSearchType == TaxaSearchType::ANY_NAME) {
				$n = explode(': ',$taxaSearchTerm);
				if (count($n) > 1) {
					$taxaSearchType = TaxaSearchType::taxaSearchTypeFromAnyNameSearchTag($n[0]);
					$taxaSearchTerm = $n[1];
				} else {
					// All terms for ANY_NAME should have a search type tag, but if not assume something.
					$taxaSearchType = TaxaSearchType::SCIENTIFIC_NAME;
				}
			}
			$this->taxaArr[$taxaSearchTerm]['taxontype'] = $taxaSearchType;
			if ($taxaSearchType == TaxaSearchType::COMMON_NAME) {
				$this->setSciNamesByVerns();
			}
			else{
				$sql = 'SELECT sciname, tid, rankid FROM taxa WHERE sciname IN("'.$this->cleanInStr($taxaSearchTerm).'")';
				$rs = $this->conn->query($sql);
				while($r = $rs->fetch_object()){
					$this->taxaArr[$r->sciname]['tid'][] = $r->tid;
					$this->taxaArr[$r->sciname]['rankid'] = $r->rankid;
					if($taxaSearchType == TaxaSearchType::SCIENTIFIC_NAME) {
						if($r->rankid > 179){
							$this->taxaArr[$r->sciname]['taxontype'] = TaxaSearchType::SPECIES_NAME_ONLY;
						}elseif($r->rankid < 180){
							$this->taxaArr[$r->sciname]['taxontype'] = TaxaSearchType::HIGHER_TAXONOMY;
						}
					}
				}
				$rs->free();
			}
			if(array_key_exists("usethes",$this->searchTermArr) && $this->searchTermArr["usethes"]) $this->setSynonymsFor($taxaSearchTerm);
		}
	}

	private function setSciNamesByVerns () {
		$sql = 'SELECT DISTINCT v.VernacularName, t.tid, t.sciname, ts.family, t.rankid '.
			'FROM (taxstatus ts INNER JOIN taxavernaculars v ON ts.TID = v.TID) '.
			'INNER JOIN taxa t ON t.TID = ts.tidaccepted ';
		$whereStr = "";
		foreach($this->taxaArr as $key => $value){
			if ($value['taxontype'] == TaxaSearchType::COMMON_NAME) {
				$whereStr .= 'OR v.VernacularName = "'.$this->cleanInStr($key).'" ';
			}
		}
		if($whereStr != ""){
			$sql .= "WHERE (ts.taxauthid = 1) AND (".substr($whereStr,3).") ORDER BY t.rankid LIMIT 20";
			//echo "<div>sql: ".$sql."</div>";
			$result = $this->conn->query($sql);
			if($result->num_rows){
				while($row = $result->fetch_object()){
				  //$vernName = strtolower($row->VernacularName);
					$vernName = $row->VernacularName;
					if($row->rankid < 140){
						$this->taxaArr[$vernName]["tid"][] = $row->tid;
					}
					elseif($row->rankid == 140){
						$this->taxaArr[$vernName]["families"][] = $row->sciname;
					}
					else{
						$this->taxaArr[$vernName]["scinames"][] = $row->sciname;
					}
				}
			}
			else{
				$this->taxaArr["no records"]["scinames"][] = "no records";
				$this->taxaArr["no records"]["taxontype"][] = TaxaSearchType::COMMON_NAME;
			}
			$result->free();
		}
	}

	private function setSynonymsFor ( $taxaArrKey ){
		$taxaVal = $this->taxaArr[$taxaArrKey];
		$synArr  = null;
		if(array_key_exists("scinames",$taxaVal)){
			if(!in_array("no records",$taxaVal["scinames"])){
				$synArr = $this->getSynonyms($taxaVal["scinames"]);
			}
		}
		else{
			$synArr = $this->getSynonyms($taxaArrKey);
		}
		if($synArr) $this->taxaArr[$taxaArrKey]["synonyms"] = $synArr;
	}

	private function getSynonyms($searchTarget,$taxAuthId = 1){
		$synArr = array();
		$targetTidArr = array();
		$searchStr = '';
		if(is_array($searchTarget)){
			if(is_numeric(current($searchTarget))){
				$targetTidArr = $searchTarget;
			}
			else{
				$searchStr = implode('","',$searchTarget);
			}
		}
		else{
			if(is_numeric($searchTarget)){
				$targetTidArr[] = $searchTarget;
			}
			else{
				$searchStr = $searchTarget;
			}
		}
		if($searchStr){
			//Input is a string, thus get tids
			$sql1 = 'SELECT tid FROM taxa WHERE sciname IN("'.$this->cleanInStr($searchStr).'")';
			$rs1 = $this->conn->query($sql1);
			while($r1 = $rs1->fetch_object()){
				$targetTidArr[] = $r1->tid;
			}
			$rs1->free();
		}

		if($targetTidArr){
			//Get acceptd names
			$accArr = array();
			$rankId = 0;
			$sql2 = 'SELECT DISTINCT t.tid, t.sciname, t.rankid '.
				'FROM taxa t INNER JOIN taxstatus ts ON t.Tid = ts.TidAccepted '.
				'WHERE (ts.taxauthid = '.$taxAuthId.') AND (ts.tid IN('.implode(',',$targetTidArr).')) ';
			$rs2 = $this->conn->query($sql2);
			while($r2 = $rs2->fetch_object()){
				$accArr[] = $r2->tid;
				$rankId = $r2->rankid;
				//Put in synonym array if not target
				if(!in_array($r2->tid,$targetTidArr)) $synArr[$r2->tid] = $r2->sciname;
			}
			$rs2->free();

			if($accArr){
				//Get synonym that are different than target
				$sql3 = 'SELECT DISTINCT t.tid, t.sciname ' .
					'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ' .
					'WHERE (ts.taxauthid = ' . $taxAuthId . ') AND (ts.tidaccepted IN(' . implode('', $accArr) . ')) ';
				$rs3 = $this->conn->query($sql3);
				while ($r3 = $rs3->fetch_object()) {
					if (!in_array($r3->tid, $targetTidArr)) $synArr[$r3->tid] = $r3->sciname;
				}
				$rs3->free();

				//If rank is 220, get synonyms of accepted children
				if ($rankId == 220) {
					$sql4 = 'SELECT DISTINCT t.tid, t.sciname ' .
						'FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ' .
						'WHERE (ts.parenttid IN(' . implode('', $accArr) . ')) AND (ts.taxauthid = ' . $taxAuthId . ') ' .
						'AND (ts.TidAccepted = ts.tid)';
					$rs4 = $this->conn->query($sql4);
					while ($r4 = $rs4->fetch_object()) {
						$synArr[$r4->tid] = $r4->sciname;
					}
					$rs4->free();
				}
			}
		}
		return $synArr;
	}

	protected function cleanOutStr($str){
		$newStr = str_replace('"',"&quot;",$str);
		$newStr = str_replace("'","&apos;",$newStr);
		//$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}

	protected function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>