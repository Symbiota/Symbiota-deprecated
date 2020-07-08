<?php
include_once("../../config/symbini.php");

include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");
include_once("$SERVER_ROOT/classes/Functional.php");
include_once("$SERVER_ROOT/classes/ExploreManager.php");

$result = [];

function getEmpty() {
  return [
    "clid" => -1,
    #"projname" => '',
    #"managers" => '',
    #"briefDescription" => '',
    #"fullDescription" => '',
    #"isPublic" => -1,
    "taxa" => [],
    #"parentTid" => -1,
  ];
}

function managerToJSON($checklistObj) {

  $result = getEmpty();

  if ($checklistObj !== null) {
    $result["clid"] = $checklistObj->getClid();
    $result["authors"] = $checklistObj->getAuthors();
    $result["abstract"] = $checklistObj->getAbstract();
    $taxa = $checklistObj->getTaxa(); 
    if (sizeof($taxa)) {
			$taxaRepo = SymbosuEntityManager::getEntityManager()->getRepository("Taxa");					
			$vouchers = $checklistObj->getVouchers();
			foreach($taxa as $rowArr){
				$taxaModel = $taxaRepo->find($rowArr['tid']);
				$taxa = TaxaManager::fromModel($taxaModel);
				$tjresult = [];
				$tjresult['tid'] = $taxa->getTid();
				$tjresult['sciname'] = $taxa->getSciname();
				$tjresult['family'] = $taxa->getFamily();
				$tjresult['author'] = $taxa->getAuthor();
				$tjresult['thumbnail'] = $taxa->getThumbnail();
				$tjresult['vernacular'] = $taxa->getVernacularNames();
				$tjresult['synonyms'] = $taxa->getSynonyms();
				#var_dump($vouchers);
				$tjresult['vouchers'] = $vouchers[$rowArr['tid']];
				$result["taxa"][] = $tjresult;
			}
		}
  }
  return $result;
}

function getChecklist($clid) {
  $em = SymbosuEntityManager::getEntityManager();
  $repo = $em->getRepository("Fmchecklists");
  $model = $repo->find($clid);
  $checklist = ExploreManager::fromModel($model);
  return managerToJSON($checklist);
}


$result = [];
/*if (array_key_exists("search", $_GET)) {
  $result = searchTaxa($_GET["search"]);
} else*/ if (array_key_exists("clid", $_GET) && is_numeric($_GET["clid"])) {
  $result = getChecklist($_GET["clid"]);
}else{
	#todo: generate error or redirect
}

// Begin View
header("Content-Type: application/json; charset=utf-8");
echo json_encode($result, JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE);
?>