<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurDatasetManager.php');
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
	
$collId = $_REQUEST["collid"];
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

$datasetManager = new OccurDatasetManager();

if($action == "Print Labels"){
	$hPrefix = $_POST['lhprefix'];
	$hMid = $_POST['lhmid'];
	$hSuffix = $_POST['lhsuffix'];
	$lFooter = $_POST['lfooter'];
	$occIdArr = $_POST['occid[]'];
	header('Location: defaultlabels.php?collid='.$collId.'&lhprefix='.$hPrefix.'&lhmid='.$hMid.'&lhsuffix='.$hSuffix.'&lfooter='.$hFooter.'&occid[]='.$occIdArr);
}
?>