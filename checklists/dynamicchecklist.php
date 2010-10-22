<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/DynamicChecklistManager.php');
header("Content-Type: text/html; charset=".$charset);
 
$lat = array_key_exists("lat",$_REQUEST)?$_REQUEST["lat"]:0;
$lng = array_key_exists("lng",$_REQUEST)?$_REQUEST["lng"]:0;
$radius = (isset($dynKeyRadius)?$dynKeyRadius:5);
$tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:0;
$interface = array_key_exists("interface",$_REQUEST)&&$_REQUEST["interface"]?$_REQUEST["interface"]:"checklist";

$dynClManager = new DynamicChecklistManager();
$dynClid = $dynClManager->createChecklist($lat, $lng, $radius, $tid);
if($interface == "key"){
	header("Location: ".$clientRoot."/ident/key.php?dynclid=".$dynClid."&taxon=All Species");
}
else{
	header("Location: ".$clientRoot."/checklists/checklist.php?dynclid=".$dynClid);
}

 ?>