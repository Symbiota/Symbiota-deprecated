<?php
error_reporting(0);
include_once('../../config/symbini.php');
include_once("Image/Barcode.php");
header("Content-type: image/png");         
$bcText = array_key_exists('bctext',$_REQUEST)?$_REQUEST["bctext"]:'';
$bcCode = array_key_exists('bccode',$_REQUEST)?$_REQUEST['bccode']:'Code39';
$imgType = array_key_exists('imgtype',$_REQUEST)?$_REQUEST['imgtype']:'png';

if(class_exists('Image_Barcode') && $bcText){
	$bcText = strtoupper($bcText);
	$bcObj = new Image_Barcode;
	$bc = $bcObj->draw($bcText, "Code39", "png");
	imagepng($bc);
	imagedestroy($bc);
}
?>