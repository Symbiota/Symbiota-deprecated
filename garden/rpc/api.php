<?php
  include_once("../../config/symbini.php");
  include_once($GLOBALS["SERVER_ROOT"] . "/config/dbconnection.php");

  $clidGardenAll = 54;

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

  function get_size_for_tid($tid) {
    $HEIGHT_KEY = 140;
    $WIDTH_KEY = 738;

    $height_sql = "SELECT avg(cs) as avg_height FROM kmdescr WHERE tid = $tid AND cid = $HEIGHT_KEY;";
    $width_sql = "SELECT avg(cs) as avg_width FROM kmdescr WHERE tid = $tid AND cid = $WIDTH_KEY;";

    $height_res = run_query($height_sql);
    $width_res = run_query($width_sql);

    $avg_width = 0;
    $avg_height = 0;

    if (count($height_res) > 0 && key_exists("avg_height", $height_res[0]) && is_numeric($height_res[0]["avg_height"])) {
      $avg_height = floatval($height_res[0]["avg_height"]);
    }

    if (count($width_res) > 0 && key_exists("avg_width", $width_res[0]) && is_numeric($width_res[0]["avg_width"])) {
      $avg_width = floatval($width_res[0]["avg_width"]);
    }

    return [$avg_width, $avg_height];
  }

  /**
   * @return {[]} Canned searches for the garden page
   */
  function get_canned_searches() {
    $sql = "select clid, name, iconurl from fmchecklists where parentclid = " . $GLOBALS["clidGardenAll"] . ";";
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

    # Select all garden taxa that have some sort of name
    $sql = "SELECT t.tid, t.sciname, v.vernacularname FROM taxa as t ";
    $sql .= "LEFT JOIN taxavernaculars AS v ON t.tid = v.tid ";
    $sql .= "RIGHT JOIN fmchklsttaxalink AS chk ON t.tid = chk.tid ";
    $sql .= "WHERE chk.clid = " . $GLOBALS["clidGardenAll"] . " ";
    $sql .= "AND (t.sciname IS NOT NULL OR v.vernacularname IS NOT NULL) ";

    if ($search !== null) {
      $sql .= "AND (t.sciname LIKE '$search%' OR v.vernacularname LIKE '$search%') ";
    }

    $sql .= "GROUP BY t.tid ORDER BY v.vernacularname;";

    $resultsTmp = run_query($sql);
    $results = [];

    // Populate image urls
    foreach ($resultsTmp as $result) {
      $size = get_size_for_tid($result["tid"]);
      $result["avg_width"] = $size[0];
      $result["avg_height"] = $size[1];

      $result["image"] = get_image_for_tid($result["tid"]);
      if ($result["image"] !== "") {
        array_push($results, $result);
      }
    }

    return $results;
  }

  // Begin View
  header("Content-Type", "application/json; charset=utf-8");

  $searchResults = [];
  if (key_exists("canned", $_GET) && $_GET["canned"] === "true") {
    $searchResults = get_canned_searches();
  } else {
    $searchResults = get_garden_taxa($_GET);
  }

  echo json_encode($searchResults, JSON_NUMERIC_CHECK);
?>

