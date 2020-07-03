<?php

include_once("../../config/symbini.php");
include_once("$SERVER_ROOT/config/SymbosuEntityManager.php");
include_once("$SERVER_ROOT/classes/IdentManager.php");

/**
 * Returns all unique taxa 
 * @params $_GET
 */
function get_data($params) {

	$search = null;
	$results = [];

	if (key_exists("search", $params) && $params["search"] !== "" && $params["search"] !== null) {
		$search = strtolower(preg_replace("/[;()-]/", '', $params["search"]));
	}
	
/*	
  $em = SymbosuEntityManager::getEntityManager();
  $repo = $em->getRepository("Kmcharacters");
  #$model = $repo->find($id);
  #$taxaenumtree = Taxaenumtree::fromModel($model);
var_dump($repo);
*/

	$identManager = new IdentManager();
	if (isset($params['cl'])) $identManager->setClid($params['cl']);
	if (isset($params['proj'])) $identManager->setPid($params['proj']);
	if (isset($params['taxon'])) $identManager->setTaxonFilter($params['taxon']);
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
	#$results = $identManager->getTaxa();
	$results = $identManager->getCharacteristics();

	#ini_set("memory_limit", $memory_limit);
	#set_time_limit(30);
	return $results;
}

$result = [];
$result = get_data($_GET);

// Begin View
header("Content-Type: application/json; charset=UTF-8");
echo json_encode($result, JSON_NUMERIC_CHECK);
?>
