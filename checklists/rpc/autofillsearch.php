<?php

include_once("../../config/symbini.php");
include_once($SERVER_ROOT . "/config/SymbosuEntityManager.php");

$RANK_GENUS = 180;
$results = [];

function searchSciNames() {
  $em = SymbosuEntityManager::getEntityManager();
  
  $innerjoins = [];
  $wheres = [];
  $params = [];
  $groupBy = [];
  
  $wheres[] = "t.sciname LIKE :search";
  //->andWhere("t.rankid > $RANK_GENUS")
  $params[] = array("search", "%" . $_REQUEST["q"] . '%');
  $groupBy[] = "t.tid";
  
  if ($_REQUEST["clid"] > 0) {
		$innerJoins[] = array("Fmchklsttaxalink", "tl", "WITH", "t.tid = tl.tid");
  	$wheres[] = "tl.clid = :clid";
  	$params[] = array("clid", $_REQUEST["clid"]);
  }elseif($_REQUEST["dynclid"] > 0) {
		$innerJoins[] = array("Fmdyncltaxalink", "clk", "WITH", "t.tid = clk.tid");
  	$wheres[] = "clk.dynclid = :dynclid";
  	$params[] = array("dynclid", $_REQUEST["dynclid"]);
  }
  
  $sciName = $em->createQueryBuilder()
    ->select("t.sciname as text, t.tid as value")
    ->from("Taxa", "t");
    
		foreach ($innerJoins as $innerJoin) {
			$sciName->innerJoin(...$innerJoin);
		}		
		if (sizeof($wheres)) {
			foreach ($wheres as $where) {
				$sciName->andWhere($where);
			}
		}
		foreach ($params as $param) {
			$sciName->setParameter(...$param);
		}		
		$sciName->groupBy(join(", ",$groupBy));
    $sciName->setMaxResults(3);
    $squery = $sciName->getQuery();
    $sciNameResults = $squery->getArrayResult();
  return $sciNameResults;
}
function searchCommonNames() {
  $em = SymbosuEntityManager::getEntityManager();
    
  $innerjoins = [];
  $wheres = [];
  $params = [];
  $groupBy = [];
  
	$innerJoins[] = array("Taxavernaculars", "v", "WITH", "t.tid = v.tid");
  $wheres[] = "v.vernacularname LIKE :search";
  //->andWhere("t.rankid > $RANK_GENUS")
  $params[] = array("search", "%" . $_REQUEST["q"] . '%');
  $groupBy[] = "v.vernacularname";
  
  if ($_REQUEST["clid"]) {
		$innerJoins[] = array("Fmchklsttaxalink", "tl", "WITH", "t.tid = tl.tid");
  	$wheres[] = "tl.clid = :clid";
  	$params[] = array("clid", $_REQUEST["clid"]);
  }elseif($_REQUEST["dynclid"]) {
		$innerJoins[] = array("Fmdyncltaxalink", "clk", "WITH", "t.tid = clk.tid");
  	$wheres[] = "clk.dynclid = :dynclid";
  	$params[] = array("dynclid", $_REQUEST["dynclid"]);
  }
  
  $vernacular = $em->createQueryBuilder()
    ->select("v.vernacularname as text", "t.tid as value")
    ->from("Taxa", "t");
	foreach ($innerJoins as $innerJoin) {
		$vernacular->innerJoin(...$innerJoin);
	}		
	if (sizeof($wheres)) {
		foreach ($wheres as $where) {
			$vernacular->andWhere($where);
		}
	}
	foreach ($params as $param) {
		$vernacular->setParameter(...$param);
	}		
	$vernacular->groupBy(join(", ",$groupBy));
	
	$vernacular->orderBy("v.sortsequence");
	
	
	$vernacular->setMaxResults(3);
	$vquery = $vernacular->getQuery();
	$vernacularResults = $vquery->getArrayResult();
  return $vernacularResults;
}

$results = [];
if (
			array_key_exists("q", $_REQUEST) 
			&& array_key_exists("name", $_GET)
			&& (
			 	(array_key_exists("clid", $_GET) && intval($_GET["clid"]) > 0)
				|| (array_key_exists("dynclid", $_GET) && intval($_GET["dynclid"]) > 0)
			)
					
	) {
	switch($_GET['name']) {
		case 'commonname':
			$results = searchCommonNames();
			break;
		default:
			$results = searchSciNames();
			break;
	}
	/*
  usort($results, function ($a, $b) {
    return strcmp($a["text"], $b["text"]);
  });	
	*/
}else{
	#todo: generate error or redirect
	return $results;
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode($results);
?>
