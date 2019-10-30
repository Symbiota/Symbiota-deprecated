<?php
  ob_start();
  include_once("../../config/symbini.php");

  global $SERVER_ROOT, $CHARSET;
  include_once($SERVER_ROOT . "/classes/Functional.php");

  $TABLE_FIELDS = json_decode(file_get_contents($SERVER_ROOT . "/meta/tableFields.json"), true);

  $CLID_GARDEN_ALL = 54;

  $CID_SUNLIGHT = 680;
  $CID_MOISTURE = 683;
  $CID_WIDTH = 738;
  $CID_HEIGHT = 140;

  # Plant features
  $CID_FLOWER_COLOR = 612;
  $CID_BLOOM_MONTHS = 165;
  $CID_WILDLIFE_SUPPORT = 685;
  $CID_LIFESPAN = 136;
  $CID_FOLIAGE_TYPE = 100;
  $CID_PLANT_TYPE = 137;

  # Growth & maintenance
  $CID_LANDSCAPE_USES = 679;
  $CID_CULTIVATION_PREFS = 767;
  $CID_BEHAVIOR = 688;
  $CID_PROPAGATION = 670;
  $CID_EASE_GROWTH = 684;

  # Beyond the garden
  $CID_HABITAT = 163;
  $CID_ECOREGION = 19;

  /**
   * @param $cid int Attribute ID in db
   * @return string[] Array of all distinct values for the cid
   */
  function get_all_attrib_vals($cid) {
    global $TABLE_FIELDS;
    $sql = 'SELECT DISTINCT ' . $TABLE_FIELDS['KMCS']['CHAR_STATE_NAME'] . ' FROM kmcs ';
    $sql .= 'WHERE ' . $TABLE_FIELDS['KMCS']['CID'] . " = $cid";

    $results = [];
    $res_tmp = run_query($sql);
    foreach ($res_tmp as $r) {
      array_push($results, $r[$TABLE_FIELDS['KMCS']['CHAR_STATE_NAME']]);
    }

    sort($results);
    return $results;
  }

/**
 * @param $dbPath string Path to the image in the Symbiota database
 * @return string The path based on $IMAGE_ROOT_URL and $IMAGE_DOMAIN
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
  function get_thumbnail($tid) {
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

  /**
   * @param $tid TID to query
   * @return array Array of vernacular names for the TID
   */
  function get_vernacular_names($tid) {
    global $TABLE_FIELDS;

    $vn_sql = get_select_statement(
        "taxavernaculars",
        [
            $TABLE_FIELDS['TAXA_VERNACULARS']['VERNACULAR_NAME'],
            $TABLE_FIELDS['TAXA_VERNACULARS']['LANGUAGE'],
        ]
    );
    $vn_sql .= ' WHERE ' . $TABLE_FIELDS['TAXA']['TID'] . " = $tid ";
    $vn_sql .= ' ORDER BY ' . $TABLE_FIELDS['TAXA_VERNACULARS']['SORT_SEQ'] . ';';

    return run_query($vn_sql);
  }

  /**
   * @param $tid TID to query
   * @return array Array of garden checklists that the TID is a member of
   */
  function get_checklists($tid) {
    global $TABLE_FIELDS;
    global $CLID_GARDEN_ALL;

    $cl_sql = get_select_statement(
        "fmchklsttaxalink tl",
        [
            'tl.' . $TABLE_FIELDS["CHECKLISTS"]["CLID"]
        ]
    );

    $cl_sql .= 'INNER JOIN taxa t ON ';
    $cl_sql .= 'tl.' . $TABLE_FIELDS["TAXA"]["TID"] . ' = t.' . $TABLE_FIELDS["TAXA"]["TID"] . ' ';
    $cl_sql .= 'INNER JOIN fmchecklists cl ON ';
    $cl_sql .= 'tl.' . $TABLE_FIELDS["CHECKLISTS"]["CLID"] . ' = cl.' . $TABLE_FIELDS["CHECKLISTS"]["CLID"] . ' ';
    $cl_sql .= 'WHERE t.' . $TABLE_FIELDS["TAXA"]["TID"] . " = $tid AND ";
    $cl_sql .= 'cl.' . $TABLE_FIELDS["CHECKLISTS"]["PARENT_CLID"] . " = $CLID_GARDEN_ALL ";
    $cl_sql .= 'GROUP BY ' . $TABLE_FIELDS["CHECKLISTS"]["CLID"];

    return run_query($cl_sql);
  }

  function get_attribs($tid) {
    global $TABLE_FIELDS;
    global $CID_WIDTH, $CID_HEIGHT;
    global $CID_MOISTURE, $CID_SUNLIGHT;
    global $CID_FLOWER_COLOR, $CID_BLOOM_MONTHS, $CID_WILDLIFE_SUPPORT, $CID_LIFESPAN, $CID_FOLIAGE_TYPE, $CID_PLANT_TYPE;
    global $CID_LANDSCAPE_USES, $CID_CULTIVATION_PREFS, $CID_BEHAVIOR, $CID_PROPAGATION, $CID_EASE_GROWTH;
    global $CID_ECOREGION, $CID_HABITAT;

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
      "moisture" => [],
      "features" => [
        "flower_color" => [],
        "bloom_months" => [],
        "wildlife_support" => [],
        "lifespan" => [],
        "foliage_type" => [],
        "plant_type" => []
      ],
      "growth_maintenance" => [
        "landscape_uses" => [],
        "cultivation_prefs" => [],
        "behavior" => [],
        "propagation" => [],
        "ease_growth" => [],
      ],
      "beyond_garden" => [
        "eco_region" => [],
        "habitat" => []
      ]
    ];

    foreach ($attr_res as $attr) {
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
        case $CID_FLOWER_COLOR:
          array_push($attr_array["features"]["flower_color"], $attr_val);
          break;
        case $CID_BLOOM_MONTHS:
          array_push($attr_array["features"]["bloom_months"], $attr_val);
          break;
        case $CID_WILDLIFE_SUPPORT:
          array_push($attr_array["features"]["wildlife_support"], $attr_val);
          break;
        case $CID_LIFESPAN:
          array_push($attr_array["features"]["lifespan"], $attr_val);
          break;
        case $CID_FOLIAGE_TYPE:
          array_push($attr_array["features"]["foliage_type"], $attr_val);
          break;
        case $CID_PLANT_TYPE:
          array_push($attr_array["features"]["plant_type"], $attr_val);
          break;
        case $CID_LANDSCAPE_USES:
          array_push($attr_array["growth_maintenance"]["landscape_uses"], $attr_val);
          break;
        case $CID_CULTIVATION_PREFS:
          array_push($attr_array["growth_maintenance"]["cultivation_prefs"], $attr_val);
          break;
        case $CID_BEHAVIOR:
          array_push($attr_array["growth_maintenance"]["behavior"], $attr_val);
          break;
        case $CID_PROPAGATION:
          array_push($attr_array["growth_maintenance"]["propagation"], $attr_val);
          break;
        case $CID_EASE_GROWTH:
          array_push($attr_array["growth_maintenance"]["ease_growth"], $attr_val);
          break;
        case $CID_ECOREGION:
          array_push($attr_array["beyond_garden"]["eco_region"], $attr_val);
          break;
        case $CID_HABITAT:
          array_push($attr_array["beyond_garden"]["habitat"], $attr_val);
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
            't.' . $TABLE_FIELDS['TAXA']['SCINAME']
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
      $result = array_merge($result, get_attribs($result["tid"]));
      $result["image"] = get_thumbnail($result["tid"]);

      $result["checklists"] = [];
      $clidsTemp = get_checklists($result["tid"]);
      foreach ($clidsTemp as $clid) {
        array_push($result["checklists"], $clid[$TABLE_FIELDS['CHECKLISTS']['CLID']]);
      }

      $result["vernacular"] = [];
      $result["vernacular"]["names"] = [];
      $vernacularsTmp = get_vernacular_names($result["tid"]);
      foreach ($vernacularsTmp as $vn) {
        $basename_is_set = array_key_exists("basename", $result["vernacular"]);

        if (!$basename_is_set && strtolower($vn[$TABLE_FIELDS['TAXA_VERNACULARS']['LANGUAGE']]) === 'basename') {
          $result["vernacular"]["basename"] = $vn[$TABLE_FIELDS['TAXA_VERNACULARS']['VERNACULAR_NAME']];
        } else {
          array_push($result["vernacular"]["names"], $vn[$TABLE_FIELDS['TAXA_VERNACULARS']['VERNACULAR_NAME']]);
        }
      }
      $result['vernacular']['names'] = array_unique($result["vernacular"]["names"]);

      array_push($results, $result);
    }

    return $results;
  }

  $searchResults = [];
  if (key_exists("canned", $_GET) && $_GET["canned"] === "true") {
    $searchResults = get_canned_searches();
  } else if (key_exists("attr", $_GET) && is_numeric($_GET['attr'])) {
    $searchResults = get_all_attrib_vals(intval($_GET['attr']));
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

