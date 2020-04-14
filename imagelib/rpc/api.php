<?php

include_once("../../config/symbini.php");
include_once($SERVER_ROOT.'/classes/ImageDetailManager.php');
include_once($SERVER_ROOT . '/classes/ImageExplorer.php');

#returns metadata for one image
function get_image($imgId) {
	#$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:"";
	#$eMode = array_key_exists("emode",$_REQUEST)?$_REQUEST["emode"]:0;

	$imgManager = new ImageDetailManager($imgId,'readonly');

	$imgArr = $imgManager->getImageMetadata($imgId);
	
	return $imgArr;
}

#returns images for one taxa
function get_taxa_images($tid) {
	$imageExplorer = new ImageExplorer();
	$searchCriteria['taxa'] = intval($tid);
	$res = $imageExplorer->getImages(json_encode($searchCriteria));
	
}

$result = [];
if (key_exists("imgid", $_GET) && is_numeric($_GET['imgid'])) {
	$result = get_image(intval($_GET['imgid']));
}elseif (key_exists("tid", $_GET) && is_numeric($_GET['tid'])) {
	$result = get_taxa_images(intval($_GET['tid']));
}

// Begin View
header("Content-Type: application/json; charset=UTF-8");
echo json_encode($result, JSON_NUMERIC_CHECK);
?>
