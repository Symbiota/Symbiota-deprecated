<?php

include_once("../../config/symbini.php");
include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");
include_once("$SERVER_ROOT/classes/IdentManager.php");
include_once("$SERVER_ROOT/classes/ExploreManager.php");
include_once("$SERVER_ROOT/classes/TaxaManager.php");

/**
 * Returns all unique taxa 
 * @params $_GET
 */
function get_data($params) {

	$search = null;
	$results = [];
	
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

	if (key_exists("search", $params) && $params["search"] !== "" && $params["search"] !== null) {
		$search = strtolower(preg_replace("/[;()-]/", '', $params["search"]));
	}
	$identManager = new IdentManager();
	if (isset($params['clid'])) $identManager->setClid($params['clid']);
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
	$identManager->setTaxa();
	$results['taxa'] = $identManager->getTaxa();
	$results['totals'] = TaxaManager::getTaxaCounts($results['taxa']);
	$results['characteristics'] = $identManager->getCharacteristics();

	#ini_set("memory_limit", $memory_limit);
	#set_time_limit(30);
	return $results;
}



#copied intact from garden/rcp/api.php
function get_characteristics($cid) {
	$em = SymbosuEntityManager::getEntityManager();
	$charStateRepo = $em->getRepository("Kmcs");
	$csQuery = $charStateRepo->findBy([ "cid" => $cid ], ["sortsequence" => "ASC"]);
	$return = array_map(function($cs) { return $cs->getCharstatename(); }, $csQuery);
	return $return;
}


$result = [];
#$result = get_data($_GET);


if (key_exists("attr", $_GET) && is_numeric($_GET['attr'])) {
	$result = get_characteristics(intval($_GET['attr']));
} elseif (array_key_exists("clid", $_GET) && is_numeric($_GET["clid"])&& array_key_exists("pid", $_GET) && is_numeric($_GET["pid"])) {
	$result = get_data($_GET);
} else {
	#todo: generate error or redirect
}


// Begin View
header("Content-Type: application/json; charset=UTF-8");
echo json_encode($result, JSON_NUMERIC_CHECK);
?>
