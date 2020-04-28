<?php
include_once("../../config/symbini.php");

include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");
include_once("$SERVER_ROOT/classes/Functional.php");
include_once("$SERVER_ROOT/classes/TaxaManager.php");
include_once("{$SERVER_ROOT}/classes/OSUTaxaManager.php");

$result = [];

$CLID_GARDEN_ALL = 54;

function getEmptyTaxon() {
  return [
    "tid" => -1,
    "sciname" => '',
    "description" => '',
    "isGardenTaxa" => false,
    "images" => [],
    "vernacular" => [
      "basename" => '',
      "names" => []
    ],
    "characteristics" => []
  ];
}

function taxaManagerToJSON($taxaObj) {

  $result = getEmptyTaxon();

  if ($taxaObj !== null) {
    $result["tid"] = $taxaObj->getTid();
    $result["sciname"] = $taxaObj->getSciname();
    $result["description"] = $taxaObj->getDescription();
    $result["isGardenTaxa"] = $taxaObj->isGardenTaxa();
    $result["images"] = $taxaObj->getImages();
    $result["vernacular"] = [
      "basename" => $taxaObj->getBasename(),
      "names" => $taxaObj->getVernacularNames()
    ];
    $result["characteristics"] = $taxaObj->getCharacteristics();
    $result["checklists"] = $taxaObj->getChecklists();
      
    $OSUManager = new OSUTaxaManager();
    #if($taxAuthId || $taxAuthId === "0") $OSUManager->setTaxAuthId($taxAuthId);
    #if($clValue) $OSUManager->setClName($clValue);
    #if($projValue) $OSUManager->setProj($projValue);
    #if($lang) $OSUManager->setLanguage($lang);
    #if($taxonValue) {
        $OSUManager->setTaxon($result["tid"]);
        $OSUManager->setAttributes();
        $result["synonyms"] = $OSUManager->getSynonymArr();
        $result["vernaculars"] = $OSUManager->getVernacularStr();
        $result["family"] = $OSUManager->getFamily();
        $result["links"] = $OSUManager->getTaxaLinks();
    #}
    
    
  }
  return $result;
}

function getTaxon($tid) {
  $em = SymbosuEntityManager::getEntityManager();
  $taxaRepo = $em->getRepository("Taxa");
  $taxaModel = $taxaRepo->find($tid);
  $taxa = TaxaManager::fromModel($taxaModel);
  return taxaManagerToJSON($taxa);
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
      $tj = taxaManagerToJSON($tm);
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
      $tj = taxaManagerToJSON($tm);
      array_push($results, $tj);
    }
  }

  return $results;
}

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

// Begin View
header("Content-Type: application/json; charset=utf-8");
echo json_encode($result, JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE);
?>