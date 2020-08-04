<?php

include_once("../config/symbini.php");
include_once($SERVER_ROOT . "/config/SymbosuEntityManager.php");

$RANK_FAMILY = 140;
$RANK_GENUS = 180;

$results = [];

if (array_key_exists("q", $_REQUEST)) {
  $em = SymbosuEntityManager::getEntityManager();

  $sciNameResults = $em->createQueryBuilder()
    ->select("t.sciname as text", "t.tid as taxonId", "t.rankid as rankId")
    ->from("Taxa", "t")
    ->where("t.sciname LIKE :search")
    ->andWhere("t.rankid >= $RANK_FAMILY")
    ->groupBy("t.tid")
    ->setParameter("search", $_REQUEST["q"] . '%')
    ->setMaxResults(15)
    ->getQuery()
    ->getArrayResult();

  $vernacularResults = $em->createQueryBuilder()
    ->select("v.vernacularname as text", "t.tid as taxonId", "t.rankid as rankId")
    ->from("Taxa", "t")
    ->innerJoin("Taxavernaculars", "v", "WITH", "t.tid = v.tid")
    ->where("v.vernacularname LIKE :search")
    ->andWhere("t.rankid >= $RANK_FAMILY")
    ->groupBy("v.vernacularname")
    ->setParameter("search", $_REQUEST["q"] . '%')
    ->orderBy("v.sortsequence")
    ->setMaxResults(15)
    ->getQuery()
    ->getArrayResult();
    
  $duplicates = array_uintersect($sciNameResults, $vernacularResults,'compareTextValues');
  $results = array_merge($sciNameResults, $vernacularResults);
  #var_dump($vernacularResults);exit;
	foreach ($results as $idx => $result) {
  	foreach ($duplicates as $duplicate) {
			if (strcasecmp($result['text'],$duplicate['text']) == 0) {
				#remove all dupes
				unset($results[$idx]);
			}
		}
	}	
	foreach ($duplicates as $duplicate) {#re-add one entry for dupe as generic search
		$results[] = array(
			"text"	=>	$duplicate['text'],
			"taxonId" => null,
			"rankId" => null
		);
	}

  usort($results, function ($a, $b) {
    return strcmp($a["text"], $b["text"]);
  });
}

#https://stackoverflow.com/questions/5653241/using-array-intersect-on-a-multi-dimensional-array#5653507
function compareTextValues($val1,$val2) {
	return strcasecmp($val1['text'],$val2['text']);
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode($results);
?>