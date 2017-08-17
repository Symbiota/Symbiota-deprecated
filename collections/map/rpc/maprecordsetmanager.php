<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/MapInterfaceManager.php');
include_once($serverRoot.'/classes/OccurrenceDataset.php');
header("Content-Type: text/html; charset=".$charset);

$uid = array_key_exists("uid",$_REQUEST)?$_REQUEST["uid"]:'';
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:'';
$newName = array_key_exists("newname",$_REQUEST)?$_REQUEST["newname"]:'';
$newNotes = array_key_exists("newnotes",$_REQUEST)?$_REQUEST["newnotes"]:'';
$dsId = array_key_exists("dsid",$_REQUEST)?$_REQUEST["dsid"]:'';
$selections = array_key_exists('selections',$_REQUEST)?$_REQUEST['selections']:0;
$selset = array_key_exists('selset',$_REQUEST)?$_REQUEST['selset']:0;

$mapManager = new MapInterfaceManager();
$datasetManager = new OccurrenceDataset();
if($action=="loadlist"){
	$recordsetlist = $mapManager->getPersonalRecordsets($uid);
	$listHtml = '';
	if($recordsetlist){
		foreach($recordsetlist as $recList => $recSet){
			$roleStr = "'".$recSet['role']."'";
			$listHtml .= '<input data-role="none" type="radio" name="dsid" value="'.$recSet['datasetid'].'" onchange="loadRecordset('.$recSet['datasetid'].','.$roleStr.');" '.($recSet['datasetid']==$selset?'checked':'').'> <a href="../datasets/index.php" target="_blank" onclick="">'.$recSet['name'].($recSet['role']=="DatasetReader"?" (read-only)":"").'</a><br />';
		}
	}
	echo $listHtml;
}
if($action=="createset"){
	$newId = '';
	$datasetManager->createDataset($newName,$newNotes,$uid);
	$newId = $datasetManager->getDsId();
	echo $newId;
}
if($action=="loadrecords"){
	$occArr = $mapManager->getOccurrences($dsId);
	if($occArr){
		echo json_encode($occArr);
	}
	else{
		echo "null";
	}
}
if($action=="addrecords"){
	$occAddArr = json_decode($selections, true);
	$datasetManager->addSelectedOccurrences($dsId,$occAddArr);
}
if($action=="clonedataset"){
	$dsidArr = Array();
	$dsidArr[] = $dsId;
	$datasetManager->cloneDatasets($dsidArr,$uid);
	$newId = $datasetManager->getDsId();
	echo $newId;
}
if($action=="deletedataset"){
	$datasetManager->deleteDataset($dsId);
}
if($action=="deleterecords"){
	$occAddArr = json_decode($selections, true);
	$datasetManager->removeSelectedOccurrences($dsId,$occAddArr);
}
?>