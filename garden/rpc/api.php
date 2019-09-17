<?php
  ob_start();
  include_once("../../config/symbini.php");
  include_once($GLOBALS["SERVER_ROOT"] . "/config/dbconnection.php");

  $CLID_GARDEN_ALL = 54;

  $CID_SUNLIGHT = 680;
  $CID_MOISTURE = 683;
  $CID_WIDTH = 738;
  $CID_HEIGHT = 140;

/**
 * @param $dbPath {string} Path to the image in the Symbiota database
 * @return {string} The path based on $IMAGE_ROOT_URL and $IMAGE_DOMAIN
 */
  function resolve_img_path($dbPath) {
    $result = $dbPath;

    if (substr($dbPath, 0, 4) !== "http") {
      if (key_exists("IMAGE_ROOT_URL", $GLOBALS)
          && $GLOBALS["IMAGE_ROOT_URL"] !== ""
          && strpos($dbPath, $GLOBALS["IMAGE_ROOT_URL"]) !== 0) {

        $result = $GLOBALS["IMAGE_ROOT_URL"] . $result;
      }
      if (key_exists("IMAGE_DOMAIN", $GLOBALS)
          && $GLOBALS["IMAGE_DOMAIN"] !== ""
          && strpos($dbPath, $GLOBALS["IMAGE_DOMAIN"]) !== 0) {

        $result = $GLOBALS["IMAGE_DOMAIN"] . $result;
      }
    }

    return $result;
  }

/**
   * Runs the given query & returns the results as an array of associative arrays
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

  /**
   * Returns the most prominent image for the given taxa ID
   */
  function get_image_for_tid($tid) {
    $sql = "SELECT i.thumbnailurl FROM images AS i WHERE tid = $tid ORDER BY i.sortsequence LIMIT 1;";
    $res = run_query($sql);

    if (count($res) > 0 && key_exists("thumbnailurl", $res[0])) {
      $result = $res[0]["thumbnailurl"];
      return resolve_img_path($result);
    }

    return "";
  }

  function get_attribs_for_tid($tid) {
    $all_attr_sql = 'SELECT kmdescr.cid as attr_key, lower(kmcs.charstatename) as attr_val FROM kmdescr ';
    $all_attr_sql .= 'INNER JOIN kmcs on (kmdescr.cid = kmcs.cid AND kmdescr.cs = kmcs.cs) ';
    $all_attr_sql .= "WHERE kmdescr.tid = $tid;";

    $attr_res = run_query($all_attr_sql);
    $attr_array = [
        "height" => [],
        "width" => [],
        "sunlight" => [],
        "moisture" => []
    ];

    foreach ($attr_res as $attr) {
      $attr_key = intval($attr["attr_key"]);
      $attr_val = $attr["attr_val"];
      switch ($attr_key) {
        case $GLOBALS["CID_HEIGHT"]:
          array_push($attr_array["height"], intval($attr_val));
          break;
        case $GLOBALS["CID_WIDTH"]:
          array_push($attr_array["width"], intval($attr_val));
          break;
        case $GLOBALS["CID_SUNLIGHT"]:
          array_push($attr_array["sunlight"], $attr_val);
          break;
        case $GLOBALS["CID_MOISTURE"]:
          array_push($attr_array["moisture"], $attr_val);
          break;
        default:
          break;
      }
    }

    foreach (["width", "height"] as $k) {
      if (count($attr_array[$k]) > 1) {
        $tmp = [min($attr_array[$k]), max($attr_array[$k])];
        $attr_array[$k] = $tmp;
      }
    }

    return $attr_array;
  }

  /**
   * Returns canned searches for the react page
   */
  function get_canned_searches() {
    $sql = "select clid, name, iconurl from fmchecklists where parentclid = " . $GLOBALS["CLID_GARDEN_ALL"] . ";";
    $resultsTmp = run_query($sql);
    $results = [];

    foreach ($resultsTmp as $result) {
      $result["iconurl"] = resolve_img_path($result["iconurl"]);
      array_push($results, $result);
    }

    return $results;
  }

  /**
   * Returns all unique taxa with thumbnail urls
   */
  function get_garden_taxa($params) {
    $search = null;
    if (key_exists("search", $params) && $params["search"] !== "" && $params["search"] !== null) {
      $search = $params["search"];
    }

    // TODO: Clean params

    # Select all react taxa that have some sort of name
    $sql = "SELECT t.tid, t.sciname, v.vernacularname FROM taxa as t ";
    $sql .= "LEFT JOIN taxavernaculars AS v ON t.tid = v.tid ";
    $sql .= "RIGHT JOIN fmchklsttaxalink AS chk ON t.tid = chk.tid ";
    $sql .= "WHERE chk.clid = " . $GLOBALS["CLID_GARDEN_ALL"] . " ";
    $sql .= "AND (t.sciname IS NOT NULL OR v.vernacularname IS NOT NULL) ";

    if ($search !== null) {
      $sql .= "AND (t.sciname LIKE '$search%' OR v.vernacularname LIKE '$search%') ";
    }

    $sql .= "GROUP BY t.tid ORDER BY v.vernacularname;";

    $resultsTmp = run_query($sql);
    $results = [];

    // Populate image urls
    foreach ($resultsTmp as $result) {
      $result = array_merge($result, get_attribs_for_tid($result["tid"]));
      $result["image"] = get_image_for_tid($result["tid"]);
      array_push($results, $result);
    }

    return $results;
  }

  $searchResults = [];
  if (key_exists("canned", $_GET) && $_GET["canned"] === "true") {
    $searchResults = get_canned_searches();
  } else {
    $searchResults = get_garden_taxa($_GET);
  }

  // Begin View
  error_reporting(E_ALL);
  ini_set("display_errors", true);
  header("Content-Type: application/json; charset=UTF-8");
  echo json_encode($searchResults, JSON_NUMERIC_CHECK);
  ob_end_flush();
?>

