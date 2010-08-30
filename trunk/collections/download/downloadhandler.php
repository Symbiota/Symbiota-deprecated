<?php
include_once('../../config/symbini.php');
include_once('../util/DownloadManager.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate");
header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
	
	$downloadType = array_key_exists("dltype",$_REQUEST)?$_REQUEST["dltype"]:"specimen"; 
	$taxonFilterCode = array_key_exists("taxonFilterCode",$_REQUEST)?$_REQUEST["taxonFilterCode"]:0; 
	
	$dlManager = new DownloadManager();
 
    if($downloadType == "checklist"){
		$dlManager->downloadChecklistText($taxonFilterCode);
    }
    elseif($downloadType == "georef"){
		$dlManager->downloadGeorefText();
    }
    elseif($downloadType == "darwincore_text"){
		$dlManager->downloadDarwinCoreText();  
    }
    elseif($downloadType == "darwincore_xml"){
		//$dlManager->downloadSpecimenDarwinCoreXml();  
    }
    else{
        $dlManager->downloadSymbiotaText();  
    }
?>