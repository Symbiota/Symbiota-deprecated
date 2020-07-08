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
  
function taxaManagerToJSON($taxaObj) {

	$result = TaxaManager::getEmptyTaxon();
  $taxaRepo = SymbosuEntityManager::getEntityManager()->getRepository("Taxa");

	if ($taxaObj !== null) {
		$result["tid"] = $taxaObj->getTid();
		$result["sciname"] = $taxaObj->getSciname();
		$result["parentTid"] = $taxaObj->getParentTid();   
		$result["rankId"] = $taxaObj->getRankId();  
		$result["author"] = $taxaObj->getAuthor();
		$result["descriptions"] = $taxaObj->getDescriptions();
		$result["gardenDescription"] = $taxaObj->getGardenDescription();
		$result["gardenId"] = $taxaObj->getGardenId();
		$result["images"] = $taxaObj->getImages();
		$result["imagesBasis"] = $taxaObj->getImagesByBasisOfRecord();
		$result["vernacular"] = [
			"basename" => $taxaObj->getBasename(),
			"names" => $taxaObj->getVernacularNames()
		];
		$result["synonyms"] = $taxaObj->getSynonyms();
		$result["origin"] = $taxaObj->getOrigin();
		$result["family"] = $taxaObj->getFamily();
		$result["taxalinks"] = $taxaObj->getTaxalinks();
		$result["rarePlantFactSheet"] = $taxaObj->getRarePlantFactSheet();
		$result["characteristics"] = $taxaObj->getCharacteristics();
		$result["checklists"] = $taxaObj->getChecklists();
		$spp = $taxaObj->getSpp();  					
		foreach($spp as $rowArr){
			$taxaModel = $taxaRepo->find($rowArr['tid']);
			$taxa = TaxaManager::fromModel($taxaModel);
			$tj = taxaManagerToJSON($taxa);
			$result["spp"][] = $tj;
		}
	}
	return $result;
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


/*
  $em = SymbosuEntityManager::getEntityManager();
  $repo = $em->getRepository("Taxadescrblock");
  #$model = $repo->find($id);
  #$taxaenumtree = Taxaenumtree::fromModel($model);
var_dump($repo);
*/
// Begin View
header("Content-Type: application/json; charset=utf-8");
echo json_encode($result, JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE);
?>