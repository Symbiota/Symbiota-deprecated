<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');

$collid = $_REQUEST["collid"];

if($collid && is_numeric($collid)){
	$dwcaManager = new DwcArchiverCore();
	$dwcaManager->setCollArr($collid);
	$collArr = $dwcaManager->getCollArr();
	
	header('Content-Description: '.$collArr[$collid]['collname'].' EML');
	header('Content-Type: text/xml; charset=utf-8');
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Cache-Control: no-cache");
	header("Pragma: no-cache");

	$xmlDom = $dwcaManager->getEmlDom();
	echo $xmlDom->saveXML();
}
?>