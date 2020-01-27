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

  $results = array_merge($sciNameResults, $vernacularResults);
  usort($results, function ($a, $b) {
    return strcmp($a["text"], $b["text"]);
  });
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode($results);
?>