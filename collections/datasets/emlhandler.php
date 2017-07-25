<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/DwcArchiverCore.php');

$collId = $_REQUEST["collid"];

if($collId && is_numeric($collId)){
	$dwcaManager = new DwcArchiverCore();
	$dwcaManager->setCollArr($collId);
	$collArr = $dwcaManager->getCollArr();
	
	header('Content-Description: '.$collArr[$collId]['collname'].' EML');
	header('Content-Type: text/xml');
	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	header("Cache-Control: no-cache");
	header("Pragma: no-cache");

	$xmlDom = $dwcaManager->getEmlDom();
	echo $xmlDom->saveXML();
}
?>