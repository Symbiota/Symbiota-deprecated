<?php
include_once("../../config/symbini.php");

include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");
include_once("$SERVER_ROOT/classes/Functional.php");
include_once("$SERVER_ROOT/classes/TaxaManager.php");

$result = [];

$CLID_GARDEN_ALL = 54;


function getTaxon($tid) {
  $em = SymbosuEntityManager::getEntityManager();
  $taxaRepo = $em->getRepository("Taxa");
  $taxaModel = $taxaRepo->find($tid);
  $taxa = TaxaManager::fromModel($taxaModel);
  return TaxaManager::taxaManagerToJSON($taxa);
}

function searchTaxa($searchTerm) {
  $results = [];
  $taxaRepo = SymbosuEntityManager::getEntityManager()->getRepository("Taxa");
  $taxaResults = $taxaRepo->createQueryBuilder("t")
    ->innerJoin("Taxavernaculars", "v", "WITH", "t.tid = v.tid")
    ->orWhere("t.sciname LIKE :search")
    ->orWhere("v.vernacularname LIKE :search")
    ->groupBy("t.tid")
    ->setParameter("search", $searchTerm . '%')
    ->getQuery()
    ->getResult();

  if ($taxaResults != null) {
    foreach ($taxaResults as $t) {
      $tm = TaxaManager::fromModel($t);
      $tj = TaxaManager::taxaManagerToJSON($tm);
      array_push($results, $tj);
    }
  }

  return $results;
}

function getSubTaxa($parentTid) {
  $results = [];
  $taxaRepo = SymbosuEntityManager::getEntityManager()->getRepository("Taxa");
  $taxaResults = $taxaRepo->createQueryBuilder("t")
    ->innerJoin("Taxaenumtree", "te", "WITH", "t.tid = te.tid")
    ->where("te.parenttid = :parenttid")
    ->groupBy("t.tid")
    ->setParameter("parenttid", $parentTid)
    ->getQuery()
    ->getResult();

  if ($taxaResults != null) {
    foreach ($taxaResults as $t) {
      $tm = TaxaManager::fromModel($t);
      $tj = TaxaManager::taxaManagerToJSON($tm);
      array_push($results, $tj);
    }
  }

  return $results;
}
/*
$result = [];
if (array_key_exists("search", $_GET)) {
  $result = searchTaxa($_GET["search"]);
}
else if (array_key_exists("taxon", $_GET) && is_numeric($_GET["taxon"])) {
  $result = getTaxon($_GET["taxon"]);
} else if (array_key_exists("family", $_GET) && is_numeric($_GET["family"])) {
  $result = getSubTaxa($_GET["family"]);
} else if (array_key_exists("genus", $_GET) && is_numeric($_GET["genus"])) {
  $result = getSubTaxa($_GET["genus"]);
}
*/

/*
  $em = SymbosuEntityManager::getEntityManager();
  $repo = $em->getRepository("Taxadescrblock");
  #$model = $repo->find($id);
  #$taxaenumtree = Taxaenumtree::fromModel($model);
var_dump($repo);
*/

$con = MySQLiConnectionFactory::getCon("readonly");
$sql = 'SELECT ts.tid, tdb.tdbid, tdb.caption, tdb.source, tdb.sourceurl, '.
				'tds.tdsid, tds.heading, tds.statement, tds.displayheader, tdb.language '.
				'FROM taxstatus ts INNER JOIN taxadescrblock tdb ON ts.tid = tdb.tid '.
				'INNER JOIN taxadescrstmts tds ON tdb.tdbid = tds.tdbid '.
				'WHERE (ts.tidaccepted = ' . 6076 . ') AND (ts.taxauthid = 1) '.
				'ORDER BY tdb.displaylevel,tds.sortsequence';
//echo $sql; exit;
$rs = $con->query($sql);
while($r = $rs->fetch_assoc()){
	var_dump($r);
}
$rs->free();


// Begin View
header("Content-Type: application/json; charset=utf-8");
echo json_encode($result, JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE);
?>