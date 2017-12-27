<?php
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/content/lang/collections/harvestparams.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceTaxaManager.php');

class TaxonSearchSupport{

	private $conn;

 	public function __construct(){
		$this->conn = MySQLiConnectionFactory::getCon('readonly');
 	}

	public function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getTaxaSuggest($queryString, $taxonType=1){
		$retArr = Array();
		$queryString = $this->cleanInStr($queryString);
		if(!is_numeric($taxonType)) $taxonType = 1;
		if($queryString) {
			$sql = "";
			if($taxonType == TaxaSearchType::ANY_NAME){
			    global $LANG;
			    $sql =
			    "SELECT DISTINCT CONCAT('".$LANG['SELECT_1-5'].": ',v.vernacularname) AS sciname ".
			    "FROM taxavernaculars v ".
			    "WHERE v.vernacularname LIKE '%".$queryString."%' ".

			    "UNION ".

			    "SELECT DISTINCT CONCAT('".$LANG['SELECT_1-2'].": ',sciname         ) AS sciname ".
			    "FROM taxa ".
			    "WHERE sciname LIKE '%".$queryString."%' AND rankid > 179 ".

			    "UNION ".

			    "SELECT DISTINCT CONCAT('".$LANG['SELECT_1-3'].": ',sciname         ) AS sciname ".
			    "FROM taxa ".
			    "WHERE sciname LIKE '".$queryString."%' AND rankid = 140 ".

			    "UNION ".

			    "SELECT          CONCAT('".$LANG['SELECT_1-4'].": ',sciname         ) AS sciname ".
			    "FROM taxa ".
			    "WHERE sciname LIKE '".$queryString."%' AND rankid > 20 AND rankid < 180 AND rankid != 140 ";

			}
			elseif($taxonType == TaxaSearchType::SCIENTIFIC_NAME){
				$sql = 'SELECT sciname FROM taxa WHERE sciname LIKE "'.$queryString.'%" LIMIT 30';
			}
			elseif($taxonType == TaxaSearchType::FAMILY_ONLY){
				$sql = 'SELECT sciname FROM taxa WHERE rankid = 140 AND sciname LIKE "'.$queryString.'%" LIMIT 30';
			}
			elseif($taxonType == TaxaSearchType::TAXONOMIC_GROUP){
				$sql = 'SELECT sciname FROM taxa WHERE rankid > 20 AND rankid < 180 AND sciname LIKE "'.$queryString.'%" LIMIT 30';
			}
			elseif($taxonType == TaxaSearchType::COMMON_NAME){
				$sql = 'SELECT DISTINCT v.vernacularname AS sciname FROM taxavernaculars v WHERE v.vernacularname LIKE "%'.$queryString.'%" LIMIT 50 ';
			}
			else{
				$sql = 'SELECT sciname FROM taxa WHERE sciname LIKE "'.$queryString.'%" LIMIT 20';
			}
			$rs = $this->conn->query($sql);
			while ($r = $rs->fetch_object()) {
				$retArr[] = $r->sciname;
			}
			$rs->free();
		}
		return $retArr;
	}

	private function cleanInStr($str){
		$newStr = trim($str);
		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = $this->conn->real_escape_string($newStr);
		return $newStr;
	}
}
?>