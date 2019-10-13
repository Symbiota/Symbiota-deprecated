<?php
  ob_start();
  include_once("../../config/symbini.php");

  global $SERVER_ROOT, $CHARSET;
  include_once($SERVER_ROOT . "/classes/Functional.php");

  $TABLE_FIELDS =  json_decode(file_get_contents($SERVER_ROOT . "/meta/tableFields.json"), true);

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
   * Returns the most prominent image for the given taxa ID
   * @param tid int The tid for the image
   * @return string The first thumbnail for $tid, else "" if one does not exist
   */
  function get_thumbnail_for_tid($tid) {
    global $TABLE_FIELDS;

    $sql = get_select_statement("images", [ $TABLE_FIELDS["IMAGES"]["THUMBNAIL_URL"] ]);
    $sql .= 'WHERE ' . $TABLE_FIELDS["IMAGES"]["TID"] . " = $tid ";
    $sql .= 'ORDER BY ' . $TABLE_FIELDS["IMAGES"]["SORT_SEQUENCE"] . ' LIMIT 1;';
    $res = run_query($sql);

    if (count($res) > 0 && key_exists($TABLE_FIELDS["IMAGES"]["THUMBNAIL_URL"], $res[0])) {
      $result = $res[0][$TABLE_FIELDS["IMAGES"]["THUMBNAIL_URL"]];
      return resolve_img_path($result);
    }

    return "";
  }

  function get_attribs_for_tid($tid) {
    global $TABLE_FIELDS;
    $all_attr_sql = get_select_statement(
        "kmdescr",
        [
            'kmdescr.' . $TABLE_FIELDS['KMDESCR']['CID'] . ' as attr_key',
            'lower(kmcs.' . $TABLE_FIELDS['KMCS']['CHAR_STATE_NAME'] . ') as attr_val'
        ]
    );
    $all_attr_sql .= 'INNER JOIN kmcs on ';
    $all_attr_sql .= '(kmdescr.' . $TABLE_FIELDS['KMDESCR']['CID'] . ' = kmcs.' . $TABLE_FIELDS['KMCS']['CID'] . ' ';
    $all_attr_sql .= 'AND kmdescr.' . $TABLE_FIELDS['KMDESCR']['CS'] . ' = kmcs.' . $TABLE_FIELDS['KMCS']['CS'] . ') ';
    $all_attr_sql .= 'WHERE ' . $TABLE_FIELDS['KMDESCR']['TID'] . " = $tid";

    $attr_res = run_query($all_attr_sql);
    $attr_array = [
        "height" => [],
        "width" => [],
        "sunlight" => [],
        "moisture" => []
    ];

    foreach ($attr_res as $attr) {
      global $CID_WIDTH, $CID_HEIGHT, $CID_MOISTURE, $CID_SUNLIGHT;

      $attr_key = intval($attr["attr_key"]);
      $attr_val = $attr["attr_val"];
      switch ($attr_key) {
        case $CID_HEIGHT:
          array_push($attr_array["height"], intval($attr_val));
          break;
        case $CID_WIDTH:
          array_push($attr_array["width"], intval($attr_val));
          break;
        case $CID_SUNLIGHT:
          array_push($attr_array["sunlight"], $attr_val);
          break;
        case $CID_MOISTURE:
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
    global $TABLE_FIELDS, $CLID_GARDEN_ALL;

    $sql = get_select_statement(
        "fmchecklists",
        [
          $TABLE_FIELDS['CHECKLISTS']['CLID'],
          $TABLE_FIELDS['CHECKLISTS']['NAME'],
          $TABLE_FIELDS['CHECKLISTS']['ICON_URL'],
          $TABLE_FIELDS['CHECKLISTS']['TITLE'] . ' as description',
        ]
    );
    $sql .= 'WHERE ' . $TABLE_FIELDS['CHECKLISTS']['PARENT_CLID'] . ' = ' . $CLID_GARDEN_ALL;
    return run_query($sql);
  }

  /**
   * Returns all unique taxa with thumbnail urls
   * @params $_GET
   */
  function get_garden_taxa($params) {
    global $TABLE_FIELDS, $CLID_GARDEN_ALL;

    $search = null;
    if (key_exists("search", $params) && $params["search"] !== "" && $params["search"] !== null) {
      $search = strtolower(preg_replace("/[;()-]/", '', $params["search"]));
    }

    # Select all garden taxa that have some sort of name
    $sql = get_select_statement(
        "taxa",
        [
            't.' . $TABLE_FIELDS['TAXA']['TID'],
            't.' . $TABLE_FIELDS['TAXA']['SCINAME'],
            'v.' . $TABLE_FIELDS['TAXA_VERNACULARS']['VERNACULAR_NAME']
        ]
    );
    // Abbreviation for 'taxa' table name
    $sql .= 't ';

    $sql .= 'LEFT JOIN taxavernaculars v ON t.tid = v.tid ';
    $sql .= 'RIGHT JOIN fmchklsttaxalink chk ON t.tid = chk.tid ';
    $sql .= "WHERE chk.clid = $CLID_GARDEN_ALL ";

    if ($search === null) {
      $sql .= 'AND (t.' . $TABLE_FIELDS['TAXA']['SCINAME']. ' IS NOT NULL ';
      $sql .= 'OR v.' . $TABLE_FIELDS['TAXA_VERNACULARS']['VERNACULAR_NAME'] . ' IS NOT NULL) ';
    }
    else {
      $sql .= 'AND (lower(t.' . $TABLE_FIELDS['TAXA']['SCINAME'] . ") LIKE \"$search%\" ";
      $sql .= 'OR lower(v. ' . $TABLE_FIELDS['TAXA_VERNACULARS']['VERNACULAR_NAME'] . ") LIKE \"$search%\") ";
    }

    $sql .= 'GROUP BY t.' . $TABLE_FIELDS['TAXA']['TID'] . ' ';
    $sql .= 'ORDER BY v.' . $TABLE_FIELDS['TAXA_VERNACULARS']['VERNACULAR_NAME'];

    $resultsTmp = run_query($sql);
    $results = [];

    // Populate image urls
    foreach ($resultsTmp as $result) {
      $result = array_merge($result, get_attribs_for_tid($result["tid"]));
      $result["image"] = get_thumbnail_for_tid($result["tid"]);
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

