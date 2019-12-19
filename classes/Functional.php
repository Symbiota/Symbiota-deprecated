<?php
include_once($SERVER_ROOT . "/config/dbconnection.php");

/**
 * @param $dbPath string Path to the image in the Symbiota database
 * @return string The path based on $IMAGE_ROOT_URL and $IMAGE_DOMAIN
 */
function resolve_img_path($dbPath) {
  global  $IMAGE_ROOT_URL;
  global  $IMAGE_DOMAIN;

  $result = $dbPath;

  if (substr($dbPath, 0, 4) !== "http") {
    if (isset($IMAGE_ROOT_URL)
      && $IMAGE_ROOT_URL !== ""
      && strpos($dbPath, $IMAGE_ROOT_URL !== 0)) {
      $result = $IMAGE_ROOT_URL . $result;
    }
    if (isset($IMAGE_DOMAIN)
      && $IMAGE_DOMAIN !== ""
      && strpos($dbPath, $IMAGE_DOMAIN) !== 0) {
      $result = $IMAGE_DOMAIN . $result;
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
 * @param $key Key to search for in $_REQUEST
 * @param $default Default to return if key is not present
 * @return The value for $key if $key exists in $_REQUEST, otherwise $default
 */
function getRequestParam($key, $default=null) {
    return array_key_exists($key, $_REQUEST) ? $_REQUEST[$key] : $default;
}

/**
 * @param $table string Table for SQL SELECT
 * @param $fields string[] Array of fields for SQL select
 * @return string The 'SELECT $fields FROM $table'
 */
function get_select_statement($table, $fields) {
    $sql = 'SELECT ';
    $sql .= implode(', ', $fields) . ' ';
    $sql .= "FROM $table ";
    return $sql;
}
?>