<?php
include_once('../../config/symbini.php');

$pArr = Array();
if(isset($_REQUEST["q"])) $pArr["q"] = $_REQUEST["q"];
if(isset($_REQUEST["fq"])) $pArr["fq"] = $_REQUEST["fq"];
if(isset($_REQUEST["pt"])) $pArr["pt"] = $_REQUEST["pt"];
if(isset($_REQUEST["d"])) $pArr["d"] = $_REQUEST["d"];
if(isset($_REQUEST["rows"])) $pArr["rows"] = $_REQUEST["rows"];
if(isset($_REQUEST["start"])) $pArr["start"] = $_REQUEST["start"];
if(isset($_REQUEST["fl"])) $pArr["fl"] = $_REQUEST["fl"];
if(isset($_REQUEST["wt"])) $pArr["wt"] = $_REQUEST["wt"];

if($pArr["wt"] == 'geojson'){
    $pArr["geojson.field"] = 'geo';
    $pArr["omitHeader"] = 'true';
}

$headers = array(
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: application/json',
    'Cache-Control: no-cache',
    'Pragma: no-cache',
    'Content-Length: '.strlen(http_build_query($pArr))
);

$ch = curl_init();
$options = array(
    CURLOPT_URL => $SOLR_URL.'/select',
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 90,
    CURLOPT_POSTFIELDS => http_build_query($pArr),
    CURLOPT_RETURNTRANSFER => true
);
curl_setopt_array($ch, $options);
$result = curl_exec($ch);
curl_close($ch);
echo $result;
?>