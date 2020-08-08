<?php

include_once("../../config/symbini.php");
include_once($SERVER_ROOT . "/config/SymbosuEntityManager.php");

$RANK_GENUS = 180;
$results = [];

function searchSciNames() {
  $em = SymbosuEntityManager::getEntityManager();

  $sciNameResults = $em->createQueryBuilder()
    ->select("t.sciname as text, t.tid as value")
    ->from("Taxa", "t")
    ->innerJoin("Fmchklsttaxalink", "tl", "WITH", "t.tid = tl.tid")
    ->where("tl.clid = :clid")
    ->andWhere("t.sciname LIKE :search")
    //->andWhere("t.rankid > $RANK_GENUS")
    ->groupBy("t.tid")
    ->setParameter("search", $_REQUEST["q"] . '%')
    ->setParameter("clid", $_REQUEST["clid"])
    ->setMaxResults(3)
    ->getQuery()
    ->getArrayResult();
  return $sciNameResults;
}
function searchCommonNames() {
  $em = SymbosuEntityManager::getEntityManager();
  $vernacularResults = $em->createQueryBuilder()
    ->select("v.vernacularname as text", "t.tid as value")
    ->from("Taxa", "t")
    ->innerJoin("Taxavernaculars", "v", "WITH", "t.tid = v.tid")
    ->innerJoin("Fmchklsttaxalink", "tl", "WITH", "t.tid = tl.tid")
    ->where("tl.clid = :clid")
    ->andWhere("v.vernacularname LIKE :search")
    //->andWhere("t.rankid > $RANK_GENUS")
    ->groupBy("v.vernacularname")
    ->setParameter("search", $_REQUEST["q"] . '%')
    ->setParameter("clid", $_REQUEST["clid"])
    ->orderBy("v.sortsequence")
    ->setMaxResults(3)
    ->getQuery()
    ->getArrayResult();
  return $vernacularResults;
}

$results = [];
if (array_key_exists("q", $_REQUEST) && array_key_exists("clid", $_GET) && is_numeric($_GET["clid"])&& array_key_exists("name", $_GET)) {
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
