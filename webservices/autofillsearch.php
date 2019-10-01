<?php

include_once("../config/symbini.php");
include_once($GLOBALS["SERVER_ROOT"] . "/config/dbconnection.php");

/**
 * Runs the given query & returns the results as an array of associative arrays
 * TODO: This is duplicate code from /garden/rpc/api.php; Move to some sort of functional include file
 */
function run_query($sql) {
  $conn = MySQLiConnectionFactory::getCon("readonly");
  $outResults = [];

  if ($conn !== null) {
    $res = $conn->query($sql);
    if ($res) {
      while($row = $res->fetch_assoc()) {
        array_push($outResults, $row);
      }
      $res->free();
    }

    $conn->close();
  }

  return $outResults;
}

$results = [];
header("Content-Type: application/json; charset=utf-8");

if (array_key_exists("q", $_REQUEST)) {
  $sql_sciname = "SELECT DISTINCT t.sciname as sciname ";
  $sql_sciname .= "FROM taxa t ";
  $sql_sciname .= "WHERE LOWER(t.sciname) LIKE LOWER('" . $_REQUEST['q'] . "%') ";
  $sql_sciname .= "AND t.rankId >= 220 ";
  $sql_sciname .= "ORDER BY t.sciname ";
  $sql_sciname .= "LIMIT 3;";

  $sql_common = "SELECT DISTINCT v.vernacularname as commonname ";
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