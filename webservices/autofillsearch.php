<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', '1');
error_reporting(E_ALL);

include_once("../config/symbini.php");
include_once($SERVER_ROOT . "/config/SymbosuEntityManager.php");

$RANK_FAMILY = 140;
$RANK_GENUS = 180;

$results = [];

if (array_key_exists("q", $_REQUEST)) {
	$query = trim($_REQUEST["q"]);
  $em = SymbosuEntityManager::getEntityManager();

  $sciNameResults = $em->createQueryBuilder()
    ->select("t.sciname as text", "t.tid as taxonId", "t.rankid as rankId", "ts.tidaccepted")
    ->from("Taxa", "t")
    ->innerJoin("Taxstatus", "ts", "WITH", "t.tid = ts.tid")
    ->where("t.sciname LIKE :search")
    ->andWhere("t.rankid >= $RANK_FAMILY")
    ->groupBy("t.tid")
    ->setParameter("search", $query . '%')
    ->setMaxResults(15)
    ->getQuery()
    ->getArrayResult();

  $vernacularResults = $em->createQueryBuilder()
    ->select("v.vernacularname as text", "t.tid as taxonId", "t.rankid as rankId", "ts.tidaccepted")
    ->from("Taxa", "t")
    ->innerJoin("Taxavernaculars", "v", "WITH", "t.tid = v.tid")
    ->innerJoin("Taxstatus", "ts", "WITH", "t.tid = ts.tid")
    ->where("v.vernacularname LIKE :search")
    ->andWhere("t.rankid >= $RANK_FAMILY")
    ->groupBy("v.vernacularname")
    ->setParameter("search", $query . '%')
    ->orderBy("v.sortsequence")
    ->setMaxResults(15)
    ->getQuery()
    ->getArrayResult();
    
  $duplicates = array_uintersect($sciNameResults, $vernacularResults,'compareTextValues');
  $results = array_merge($sciNameResults, $vernacularResults);
	if ($duplicates) {#overlap between sciname and common name 
		foreach ($results as $idx => $result) {
			foreach ($duplicates as $duplicate) {
				if (strcasecmp($result['text'],$duplicate['text']) == 0) {
					#remove all dupes
					unset($results[$idx]);
				}
			}
		}	
		#var_dump($results);exit;
		foreach ($duplicates as $duplicate) {#re-add one entry for dupe as generic search
			$results[] = array(
				"text"	=>	$duplicate['text'],
				"taxonId" => null,
				"rankId" => null
			);
		}
	}elseif (sizeof($vernacularResults) > 1) {#check for overlap within common
		#find the shortest value and remove its taxonId value, so that home/main.jsx treats it as generic text search
		$text_lengths = array_map("strlen",array_column($vernacularResults,"text"));
		$target_length = min($text_lengths);
		foreach ($results as $idx => $result) {
			if (strlen($result['text']) === $target_length ) {
				$results[$idx]['taxonId'] = null;
				$results[$idx]['rankId'] = null;
			}
		}
	}
	
  usort($results, function ($a, $b) {
    return strcasecmp(stripNonAlpha($a["text"]), stripNonAlpha($b["text"]));
  });
}
function stripNonAlpha($str) {
	return preg_replace("/[^A-Za-z]/", '', $str);
}
#https://stackoverflow.com/questions/5653241/using-array-intersect-on-a-multi-dimensional-array#5653507
function compareTextValues($val1,$val2) {
	return strcasecmp($val1['text'],$val2['text']);
}

header("Content-Type: application/json; charset=utf-8");
echo json_encode($results);
?>