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
    $result['taxa'] = $checklistObj->getTaxa();
/*    $result["projname"] = $checklistObj->getProjname();  
    $result["managers"] = $checklistObj->getManagers();
    $result["briefDescription"] = $checklistObj->getBriefDescription();
    $result["fullDescription"] = $checklistObj->getFullDescription();
    $result["isPublic"] = $checklistObj->getIsPublic();
    $result["checklists"] = $checklistObj->getChecklists();  
    #$result["parentTid"] = $checklistObj->getParentTid(); 
    */  
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