<?php
/*
 * Note: See http://www.php.net/manual/en/features.file-upload.common-pitfalls.php 
 * for discussion of PHP server configuration directives that you will probably need
 * to set for file uploads.
 *
 * ****  Input Variables  ********************************************
 *
 * uploadtype (required): $FILEUPLOAD = 3; $DWCAUPLOAD = 6 
 * key (required): security key used to authorize   
 * filepath: URI path to locality where DWCA file was placed for retrieval (file must have read accessible to portal)
 * uploadfile: file streamed in for upload; POST protocol must be used when streaming file
 * importident (default = false): 0 = identification history NOT included for ingestion, 1 = identification history included for ingestion 
 * importimage (default = false): 0 = image URLs NOT included for ingestion, 1 = image URLs included for ingestion 
 */

date_default_timezone_set('America/Phoenix');
include_once('../../config/symbini.php');
require_once($serverRoot.'/classes/SpecUploadBase.php');
require_once($serverRoot.'/classes/SpecUploadFile.php');
require_once($serverRoot.'/classes/SpecUploadDwca.php');


$uploadType = preg_replace("/[^0-9]/","",$_REQUEST["uploadtype"]);
$securityKey = preg_replace("/[^A-Za-z0-9\-]/","",$_REQUEST["key"]);
$filePath = array_key_exists("filepath",$_REQUEST)?$_REQUEST['filepath']:false;
$importIdent = array_key_exists("importident",$_REQUEST)?$_REQUEST['importident']:false;
$importImage = array_key_exists("importimage",$_REQUEST)?$_REQUEST['importimage']:false;
$action = array_key_exists("action",$_REQUEST)?preg_replace("/[^a-z]/","",$_REQUEST['action']):'';

if(!$securityKey){
	exit("ERROR: security key is required and is null ");
}
if(!$uploadType){
	exit("ERROR: uploadtype is required and is null ");
}

$duManager;
$FILEUPLOAD = 3; $DWCAUPLOAD = 6;
if($uploadType == $FILEUPLOAD){
	$duManager = new SpecUploadFile();
	//if($filePath) ;
}
elseif($uploadType == $DWCAUPLOAD){
	$duManager = new SpecUploadDwca();
	$duManager->setIncludeIdentificationHistory($importIdent);
	$duManager->setIncludeImages($importImage);
	if($filePath) $duManager->setPath($filePath); 
}
else{
	exit('ERROR: illegal upload type = '.$uploadType.' (should be 3 = File Upload, 6 = DWCA upload)');
}
if(!$duManager->validateSecurityKey($securityKey)){
	exit('ERROR: security key validation failed!');
}
$duManager->setVerboseMode(2);

$duManager->loadFieldMap(true);
$ulPath = $duManager->uploadFile();
if(!$ulPath){
	exit('ERROR uploading file: '.$this->getErrorStr());
}

if(!$duManager->analyzeUpload()){
	exit('ERROR analyzing upload file: '.$this->getErrorStr());
}
if(!$duManager->uploadData(false)){
	exit('ERROR uploading file: '.$this->getErrorStr());
}
$transferCnt = $duManager->getTransferCount();
$duManager->finalTransfer();

if($transferCnt > 0){
	echo $transferCnt;
}
else{
	echo 'FAILED: 0 records uploaded';
}