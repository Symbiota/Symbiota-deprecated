<?php
include_once("../../config/symbini.php");

include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");
include_once("$SERVER_ROOT/classes/Functional.php");
include_once("$SERVER_ROOT/classes/TaxaManager.php");

$result = [];

$CLID_GARDEN_ALL = 54;


/*
include_once($SERVER_ROOT.'/classes/TaxonProfileManager.php');
$taxonManager = new TaxonProfileManager();
	$taxonManager->setTaxon($_GET["taxon"]);
$taxonManager->setSynonyms();
$syn = $taxonManager->getSynonyms();
var_dump($syn);

var_dump($taxonManager->getAuthor());
*/


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
  
function taxaManagerToJSON($taxaObj,$recursive = true) {

	$result = TaxaManager::getEmptyTaxon();
  $taxaRepo = SymbosuEntityManager::getEntityManager()->getRepository("Taxa");

	if ($taxaObj !== null) {
		$result["tid"] = $taxaObj->getTid();
		$result["sciname"] = $taxaObj->getSciname();
		$result["parentTid"] = $taxaObj->getParentTid();   
		$result["rankId"] = $taxaObj->getRankId();  
		$result["author"] = $taxaObj->getAuthor();
		#var_dump($result["author"]);
		$result["images"] = $taxaObj->getImages();

		if ($recursive === true) {
			$spp = $taxaObj->getSpp();  					
			foreach($spp as $rowArr){
				$taxaModel = $taxaRepo->find($rowArr['tid']);
				$taxa = TaxaManager::fromModel($taxaModel);
				$tj = taxaManagerToJSON($taxa,false);
				$result["spp"][] = $tj;
			}
			$allImages = $taxaObj->getImagesByBasisOfRecord();
			$result["imagesBasis"]['HumanObservation'] = (isset($allImages['HumanObservation']) ? $allImages['HumanObservation'] : []);
			$result["imagesBasis"]['PreservedSpecimen'] = (isset($allImages['PreservedSpecimen']) ? $allImages['PreservedSpecimen'] : []);
			$result["imagesBasis"]['LivingSpecimen'] = (isset($allImages['LivingSpecimen']) ? $allImages['LivingSpecimen'] : []);
			$result["synonyms"] = $taxaObj->getSynonyms();
			$result["origin"] = $taxaObj->getOrigin();
			$result["family"] = $taxaObj->getFamily();
			$result["rarePlantFactSheet"] = $taxaObj->getRarePlantFactSheet();
			$result["characteristics"] = $taxaObj->getCharacteristics();
			$result["checklists"] = $taxaObj->getChecklists();
			$result["descriptions"] = $taxaObj->getDescriptions();
			$result["gardenDescription"] = $taxaObj->getGardenDescription();
			$result["gardenId"] = $taxaObj->getGardenId();
			$result["vernacular"] = [
				"basename" => $taxaObj->getBasename(),
				"names" => $taxaObj->getVernacularNames()
			];
			$result["taxalinks"] = $taxaObj->getTaxalinks();
			foreach ($result["taxalinks"] as $idx => $taxalink) {
				$result["taxalinks"][$idx]['url'] = str_replace("--SCINAME--",$result["sciname"],$taxalink['url']);
			}
		
		}
	}
	return $result;
}
$result = [];
if (array_key_exists("search", $_GET)) {
  $result = searchTaxa(trim($_GET["search"]));
}else if (array_key_exists("taxon", $_GET) && is_numeric($_GET["taxon"])) {
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