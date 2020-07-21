<?php
include_once("../../config/symbini.php");

include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");
include_once("$SERVER_ROOT/classes/Functional.php");
include_once("$SERVER_ROOT/classes/InventoryManager.php");

$result = [];

function getEmptyInventory() {
  return [
    "pid" => -1,
    "projname" => '',
    "managers" => '',
    "briefDescription" => '',
    "fullDescription" => '',
    "isPublic" => -1,
    "checklists" => [],
    "parentTid" => -1,
  ];
}

function inventoryManagerToJSON($inventoryObj) {

  $result = getEmptyInventory();

  if ($inventoryObj !== null) {
    $result["pid"] = $inventoryObj->getPid();
    $result["projname"] = $inventoryObj->getProjname();  
    $result["managers"] = $inventoryObj->getManagers();
    $result["briefDescription"] = $inventoryObj->getBriefDescription();
    $result["fullDescription"] = $inventoryObj->getFullDescription();
    $result["isPublic"] = $inventoryObj->getIsPublic();
    $result["checklists"] = $inventoryObj->getChecklists();  
    #$result["parentTid"] = $inventoryObj->getParentTid();   
  }
  return $result;
}

function getInventory($pid) {
  $em = SymbosuEntityManager::getEntityManager();
  $repo = $em->getRepository("Fmprojects");
  $model = $repo->find($pid);
  $inventory = InventoryManager::fromModel($model);
  return inventoryManagerToJSON($inventory);
}

function getProjectList(){

	$em = SymbosuEntityManager::getEntityManager();
	$projects = $em->createQueryBuilder()
		->select(["p.pid, p.projname, p.managers, p.briefdescription, p.fulldescription"])
		->from("Fmprojects", "p")
    ->where("p.ispublic = 1")
		->getQuery()
		->execute();
	return $projects;
}


$result = [];
if (array_key_exists("search", $_GET)) {
  $result = searchTaxa($_GET["search"]);
} else if (array_key_exists("pid", $_GET) && is_numeric($_GET["pid"])) {
  $result = getInventory($_GET["pid"]);
}else{
	$result = getProjectList();
}

// Begin View
header("Content-Type: application/json; charset=utf-8");
echo json_encode($result, JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE);
?>