<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/DynamicChecklistManager.php');
header("Content-Type: text/html; charset=".$charset);
 
$lat = $_POST["lat"];
$lng = $_POST["lng"];
$radius = $_POST["radius"];
$radiusunits = $_POST["radiusunits"];
$dynamicRadius = (isset($dynChecklistRadius)?$dynChecklistRadius:(isset($dynKeyRadius)?$dynKeyRadius:5));
$tid = $_POST["tid"];
$interface = $_POST["interface"];

$dynClManager = new DynamicChecklistManager();

if(is_numeric($radius)){
	$dynClid = $dynClManager->createChecklist($lat, $lng, $radius, $radiusunits, $tid);
}
else{
	$dynClid = $dynClManager->createDynamicChecklist($lat, $lng, $dynamicRadius, $tid);
}

if($interface == "key"){
	$url = $clientRoot."/ident/key.php?dynclid=".$dynClid."&taxon=All Species";
	echo $url;exit;
	header("Location: ". $url);
}
else{
	header("Location: ".$clientRoot."/checklists/checklist.php?dynclid=".$dynClid);
}
ob_flush();
flush();
$dynClManager->removeOldChecklists();
?>