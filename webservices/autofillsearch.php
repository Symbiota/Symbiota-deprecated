<?php

include_once("../config/symbini.php");
include_once($SERVER_ROOT . "/config/dbconnection.php");
include_once($SERVER_ROOT . "/classes/Functional.php");

$results = [];
header("Content-Type: application/json; charset=utf-8");

if (array_key_exists("q", $_REQUEST)) {
  $sql_sciname = "SELECT DISTINCT t.sciname as sciname, t.tid as tid ";
  $sql_sciname .= "FROM taxa t ";

  $sql_sciname .= "WHERE LOWER(t.sciname) LIKE LOWER('" . $_REQUEST['q'] . "%') ";
  $sql_sciname .= "AND t.rankId >= 220 ";
  $sql_sciname .= "ORDER BY t.sciname ";
  $sql_sciname .= "LIMIT 3;";

  $sql_common = "SELECT DISTINCT v.vernacularname as commonname, t.tid as tid ";
  $sql_common .= "FROM taxavernaculars v ";
  $sql_common .= "INNER JOIN taxa t on v.tid = t.tid ";
  $sql_common .= "WHERE LOWER(v.vernacularname) LIKE LOWER('" . $_REQUEST['q'] . "%') ";
  $sql_common .= "AND t.rankId >= 220 ";
  $sql_common .= "ORDER BY v.sortsequence ";
  $sql_common .= "LIMIT 3;";

  $res_sci = run_query($sql_sciname);
  $res_common = run_query($sql_common);

  for ($i = 0; $i < count($res_sci); $i++) {
    array_push($results, $res_sci[$i]["sciname"]);
  }

  for ($i = 0; $i < count($res_common); $i++) {
    array_push($results, $res_common[$i]["commonname"]);
  }

  sort($results);
}

echo json_encode($results);
?>