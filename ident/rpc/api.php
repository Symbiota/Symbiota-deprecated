<?php

include_once("../../config/symbini.php");
include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");
include_once("$SERVER_ROOT/classes/IdentManager.php");
include_once("$SERVER_ROOT/classes/ExploreManager.php");
include_once("$SERVER_ROOT/classes/TaxaManager.php");
include_once("$SERVER_ROOT/classes/InventoryManager.php");



function getEmpty() {
  return [
    "clid" => -1,
    "projName" => '',
    "title" => '',
    "intro" => '',
    "iconUrl" => '',
    "authors" => '',
    "abstract" => '',
    "taxa" => [],
    "characteristics" => [],
    "lat" => 0,
    "lng" => 0,
  ];
}


/**
 * Returns all unique taxa 
 * @params $_GET
 */
function get_data($params) {

	$search = null;
	$results = getEmpty();
	
	if (isset($params["clid"])) {
		$em = SymbosuEntityManager::getEntityManager();
		$repo = $em->getRepository("Fmchecklists");
		$model = $repo->find($params["clid"]);
		$checklist = ExploreManager::fromModel($model);
		$checklist->setPid($params["pid"]);
		$results["clid"] = $checklist->getClid();
		$results["title"] = $checklist->getTitle();
		$results["intro"] = ($checklist->getIntro()? $checklist->getIntro() :'') ;
		$results["iconUrl"] = ($checklist->getIconUrl()? $checklist->getIconUrl() :'') ;
		$results["authors"] = ($checklist->getAuthors()? $checklist->getAuthors() :'') ;
		$results["abstract"] = ($checklist->getAbstract()? $checklist->getAbstract() :'') ;
    $results["lat"] = ($checklist->getLat()? $checklist->getLat() :'') ;
    $results["lng"] = ($checklist->getLng()? $checklist->getLng() :'') ;

		$projRepo = SymbosuEntityManager::getEntityManager()->getRepository("Fmprojects");					
		$model = $projRepo->find($params["pid"]);
		$project = InventoryManager::fromModel($model);
		$results["projName"] = $project->getProjname();
	}elseif(isset($params['dynclid'])) {

		$em = SymbosuEntityManager::getEntityManager();
		$repo = $em->getRepository("Fmdynamicchecklists");
		$model = $repo->find($params["dynclid"]);
		if ($model) {
			$dynamic_checklist = ExploreManager::fromModel($model);
			$results["title"] = $dynamic_checklist->getTitle();
		}
	
	}
  	
	$identManager = new IdentManager();
	if (isset($params['clid'])) $identManager->setClid($params['clid']);
	if (isset($params['dynclid'])) $identManager->setDynClid($params['dynclid']);
	if (isset($params['pid'])) $identManager->setPid($params['pid']);
	if (isset($params['taxon'])) $identManager->setTaxonFilter($params['taxon']);
	if (isset($params['rv'])) $identManager->setRelevanceValue($params['rv']);
	if (isset($params['attr'])) {
		$attrs = array();
		foreach ($params['attr'] as $attr) {
			if(strpos($attr,'-') !== false) {
				$fragments = explode("-",$attr);
				$cid = intval($fragments[0]);
				$cs = intval($fragments[1]);
				if (is_numeric($cid) && is_numeric($cs)) {
					$attrs[$cid][] = $cs;
				}
			}
		}
		$identManager->setAttrs($attrs);
	}
	if ( 	 ( array_key_exists("search", $params) && !empty($params["search"]) )
			&& ( array_key_exists("name", $params) && in_array($params['name'],array('sciname','commonname')) )
	) {
		$identManager->setSearchTerm($params["search"]);
		$identManager->setSearchName($params['name']);
	}
	
	$identManager->setTaxa();
	$results['taxa'] = $identManager->getTaxa();
	$results['totals'] = TaxaManager::getTaxaCounts($results['taxa']);
	$results['characteristics'] = $identManager->getCharacteristics();

	#ini_set("memory_limit", $memory_limit);
	#set_time_limit(30);
	return $results;
}



#copied intact from garden/rcp/api.php
function get_characteristics($cid) {#TODO - get rid of this
	$em = SymbosuEntityManager::getEntityManager();
	$charStateRepo = $em->getRepository("Kmcs");
	$csQuery = $charStateRepo->findBy([ "cid" => $cid ], ["sortsequence" => "ASC"]);
	$return = array_map(function($cs) { return $cs->getCharstatename(); }, $csQuery);
	return $return;
}


$result = [];
#$result = get_data($_GET);


if (key_exists("attr", $_GET) && is_numeric($_GET['attr'])) {#get rid of this
	$result = get_characteristics(intval($_GET['attr']));
} elseif (
						(array_key_exists("clid", $_GET) && is_numeric($_GET["clid"])&& array_key_exists("pid", $_GET) && is_numeric($_GET["pid"]))
						|| (array_key_exists("dynclid", $_GET) && is_numeric($_GET["dynclid"]))
				) {
	$result = get_data($_GET);
} else {
	#todo: generate error or redirect
}

//var_dump($result);
// Begin View
header("Content-Type: application/json; charset=UTF-8");
$json =  json_encode($result, JSON_NUMERIC_CHECK);
echo $json;
?>
