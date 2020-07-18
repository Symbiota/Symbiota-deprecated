<?php
include_once("../../config/symbini.php");

include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");
include_once("$SERVER_ROOT/classes/Functional.php");
include_once("$SERVER_ROOT/classes/ArticlesManager.php");

$result = [];

function getEmpty() {
  return [
    "articles" => []
  ];
}

function articlesManagerToJSON($inventoryObj) {

  $result = getEmpty();

  if ($inventoryObj !== null) {
    $result["pid"] = $inventoryObj->getPid();
    $result["projname"] = $inventoryObj->getProjname();  
    $result["managers"] = $inventoryObj->getManagers();
    $result["briefDescription"] = $inventoryObj->getBriefDescription();
    $result["fullDescription"] = $inventoryObj->getFullDescription();
    $result["isPublic"] = $inventoryObj->getIsPublic();
    $result["articles"] = $inventoryObj->getChecklists();  
    #$result["parentTid"] = $inventoryObj->getParentTid();   
  }
  return $result;
}



function getIssueList(){
  $manager = new ArticlesManager();
  return $manager->getArticles();
}


$result = [];
$result = getIssueList();


// Begin View
header("Content-Type: application/json; charset=utf-8");
echo json_encode($result, JSON_NUMERIC_CHECK | JSON_INVALID_UTF8_SUBSTITUTE);
?>