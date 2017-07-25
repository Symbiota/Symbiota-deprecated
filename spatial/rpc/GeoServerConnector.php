<?php
include_once('../../config/symbini.php');

$pArr = Array();
if(isset($_REQUEST["SERVICE"])) $pArr["SERVICE"] = $_REQUEST["SERVICE"];
if(isset($_REQUEST["VERSION"])) $pArr["VERSION"] = $_REQUEST["VERSION"];
if(isset($_REQUEST["REQUEST"])) $pArr["REQUEST"] = $_REQUEST["REQUEST"];
if(isset($_REQUEST["typename"])) $pArr["typename"] = $_REQUEST["typename"];
if(isset($_REQUEST["FORMAT"])) $pArr["FORMAT"] = $_REQUEST["FORMAT"];
if(isset($_REQUEST["TRANSPARENT"])) $pArr["TRANSPARENT"] = $_REQUEST["TRANSPARENT"];
if(isset($_REQUEST["QUERY_LAYERS"])) $pArr["QUERY_LAYERS"] = $_REQUEST["QUERY_LAYERS"];
if(isset($_REQUEST["LAYERS"])) $pArr["LAYERS"] = $_REQUEST["LAYERS"];
if(isset($_REQUEST["INFO_FORMAT"])) $pArr["INFO_FORMAT"] = $_REQUEST["INFO_FORMAT"];
if(isset($_REQUEST["I"])) $pArr["I"] = $_REQUEST["I"];
if(isset($_REQUEST["J"])) $pArr["J"] = $_REQUEST["J"];
if(isset($_REQUEST["CRS"])) $pArr["CRS"] = $_REQUEST["CRS"];
if(isset($_REQUEST["featureid"])) $pArr["featureid"] = $_REQUEST["featureid"];
if(isset($_REQUEST["outputFormat"])) $pArr["outputFormat"] = $_REQUEST["outputFormat"];
if(isset($_REQUEST["srsname"])) $pArr["srsname"] = $_REQUEST["srsname"];
if(isset($_REQUEST["STYLES"])) $pArr["STYLES"] = $_REQUEST["STYLES"];
if(isset($_REQUEST["FORMAT_OPTIONS"])) $pArr["FORMAT_OPTIONS"] = $_REQUEST["FORMAT_OPTIONS"];
if(isset($_REQUEST["WIDTH"])) $pArr["WIDTH"] = $_REQUEST["WIDTH"];
if(isset($_REQUEST["HEIGHT"])) $pArr["HEIGHT"] = $_REQUEST["HEIGHT"];
if(isset($_REQUEST["BBOX"])) $pArr["BBOX"] = $_REQUEST["BBOX"];

$geoserverURL = ($pArr["SERVICE"] == 'WMS'?$GEOSERVER_URL.'/'.$GEOSERVER_LAYER_WORKSPACE.'/wms':$GEOSERVER_URL.'/'.$GEOSERVER_LAYER_WORKSPACE.'/wfs');
$acceptFormat = ($pArr["REQUEST"] == 'GetMap'?'image/png; text/xml':'application/json');

$headers = array(
    'Content-Type: application/x-www-form-urlencoded',
    'Accept: '.$acceptFormat,
    'Cache-Control: no-cache',
    'Pragma: no-cache',
    'Content-Length: '.strlen(http_build_query($pArr))
);

$ch = curl_init();
$options = array(
    CURLOPT_URL => $geoserverURL,
    CURLOPT_POST => true,
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_TIMEOUT => 90,
    CURLOPT_POSTFIELDS => http_build_query($pArr),
    CURLOPT_RETURNTRANSFER => true
);
curl_setopt_array($ch, $options);
$result = curl_exec($ch);
curl_close($ch);

if($pArr["REQUEST"] == 'GetMap'){
    $im = imagecreatefromstring($result);
    header('Content-Type: image/png');
    imagepng($im);
    imagedestroy($im);
}
else{
    echo $result;
}
?>