<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:'';
$collid = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$cond = array_key_exists("cond",$_REQUEST)?$_REQUEST["cond"]:'';
$collType = array_key_exists("colltype",$_REQUEST)?$_REQUEST["colltype"]:'specimens';
$includeDets = array_key_exists("dets",$_REQUEST)?$_REQUEST["dets"]:1;
$includeImgs = array_key_exists("imgs",$_REQUEST)?$_REQUEST["imgs"]:1;
$includeAttributes = array_key_exists("attr",$_REQUEST)?$_REQUEST["attr"]:1;

if($collid){
	$dwcaHandler = new DwcArchiverCore();
	
	$dwcaHandler->setVerboseMode(0);
	$dwcaHandler->setCollArr($collid,$collType);
	if($cond){
		//String of cond-key/value pairs (e.g. country:USA,United States;stateprovince:Arizona,New Mexico;county-start:Pima,Eddy
		$cArr = explode(';',$cond);
		foreach($cArr as $rawV){
			$tok = explode(':',$rawV);
			if($tok){
				$field = $tok[0];
				$cond = 'EQUALS';
				$valueArr = array();
				if($p = strpos($tok[0],'-')){
					$field = substr($tok[0],0,$p);
					$cond = substr($tok[0],$p+1);
				}
				if(isset($tok[1]) && $tok[1]){
					$valueArr = explode(',',$tok[1]);
				}
				if($valueArr){
					foreach($valueArr as $v){
						$dwcaHandler->addCondition($field, $cond, $v);
					}
				}
				else{
					$dwcaHandler->addCondition($field, $cond);
				}
			}
		}
	}
	$dwcaHandler->setIncludeDets($includeDets);
	$dwcaHandler->setIncludeImgs($includeImgs);
	$dwcaHandler->setIncludeAttributes($includeAttributes);

	$archiveFile = $dwcaHandler->createDwcArchive('webreq');
	if($archiveFile){
		//ob_start();
		header('Content-Description: DwC-A File Transfer');
		header('Content-Type: application/zip');
		header('Content-Disposition: attachment; filename='.basename($archiveFile));
		header('Content-Transfer-Encoding: binary');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		header('Content-Length: ' . filesize($archiveFile));
		ob_clean();
		flush();
		//od_end_clean();
		readfile($archiveFile);
		unlink($archiveFile);
		exit;
	}
	else{
		header('Content-Description: DwC-A File Transfer Error');
		header('Content-Type: text/plain');
		header('Expires: 0');
		header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
		header('Pragma: public');
		echo 'Error: unable to create archive';
	}
}
else{
	header('Content-Description: DwC-A File Transfer Error');
	header('Content-Type: text/plain');
	header('Expires: 0');
	header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
	header('Pragma: public');
	echo 'Error: collectoin identifier is not defined';
}
?>