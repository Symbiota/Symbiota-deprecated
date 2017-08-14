<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/content/lang/collections/harvestparams.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/SearchManager.php');

class OccurrenceSearchSupport{

	protected $conn;

 	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
 	}

	public function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getTaxaSuggest($queryString, $taxonType){
		$retArr = Array();
		$queryString = $this->cleanInStr($queryString);
		if(!is_numeric($taxonType)) $taxonType = 0;
		if($queryString) {
			$sql = "";
			if($taxonType == TaxaSearchType::ANY_NAME){
			    global $LANG;
			    $sql =
			    "SELECT DISTINCT CONCAT('".$LANG['TSTYPE_1-5'].": ',v.vernacularname) AS sciname, ".
			    "                CONCAT('A:'                       ,v.vernacularname) AS snorder ".
			    "FROM taxavernaculars v ".
			    "WHERE v.vernacularname LIKE '%".$queryString."%' ".
			    
			    "UNION ".
			    
			    "SELECT          CONCAT('".$LANG['TSTYPE_1-4'].": ',sciname         ) AS sciname, ".
			    "                CONCAT('E:'                       ,sciname         ) AS snorder ".
			    "FROM taxa ".
			    "WHERE sciname LIKE '%".$queryString."%' AND rankid > 20 AND rankid < 140 ".
			    
			    "UNION ".
			    
			    "SELECT DISTINCT CONCAT('".$LANG['TSTYPE_1-3'].": ',sciname         ) AS sciname, ".
			    "                CONCAT('B:'                       ,sciname         ) AS snorder ".
			    "FROM taxa ".
			    "WHERE sciname LIKE '%".$queryString."%' AND rankid > 140 ".
			    
			    "UNION ".
			    
			    "SELECT DISTINCT CONCAT('".$LANG['TSTYPE_1-2'].": ',family          ) AS sciname, ".
			    "                CONCAT('C:'                       ,family          ) AS snorder ".
			    "FROM taxstatus ".
			    "WHERE family LIKE '%".$queryString."%' ".
			    
			    "UNION ".
			    
			    "SELECT DISTINCT CONCAT('".$LANG['TSTYPE_1-2'].": ',sciname         ) AS sciname, ".
			    "                CONCAT('C:'                       ,sciname         ) AS snorder ".
			    "FROM taxa ".
			    "WHERE sciname LIKE '%".$queryString."%' AND rankid = 140 ".
			    
			    "ORDER BY snorder LIMIT 30";
			}
			elseif($taxonType == TaxaSearchType::FAMILY_GENUS_OR_SPECIES){
				// Family or species name
				$sql = 'SELECT sciname FROM taxa WHERE sciname LIKE "'.$queryString.'%" AND rankid > 139 LIMIT 30';
			}
			elseif($taxonType == TaxaSearchType::FAMILY_ONLY){
				// Family only
				$sql = 'SELECT sciname FROM taxa WHERE sciname LIKE "'.$queryString.'%" LIMIT 30';
			}
			elseif($taxonType == TaxaSearchType::SPECIES_NAME_ONLY){
				// Species name only
				$sql = 'SELECT sciname FROM taxa WHERE sciname LIKE "'.$queryString.'%" AND rankid > 179 LIMIT 30';
			}
			elseif($taxonType == TaxaSearchType::HIGHER_TAXONOMY){
				// Higher taxon
				$sql = 'SELECT sciname FROM taxa WHERE rankid > 20 AND rankid < 140 AND sciname LIKE "'.$queryString.'%" LIMIT 30';
			}
			elseif($taxonType == TaxaSearchType::COMMON_NAME){
				// Common name
				$sql = 'SELECT DISTINCT v.vernacularname AS sciname FROM taxavernaculars v WHERE v.vernacularname LIKE "%'.$queryString.'%" limit 50 ';
			}
			else{
				$sql = 'SELECT sciname FROM taxa WHERE sciname LIKE "'.$queryString.'%" LIMIT 20';
			}
			$rs = $this->conn->query($sql);
			while ($r = $rs->fetch_object()) {
				$retArr[] = htmlentities($r->sciname);
			}
			$rs->free();
		}
		return $retArr;
	}

	protected function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>