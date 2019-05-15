<?php
/*
 * Base URL: <domain plus path to Symbiota root directory>/webservices/dwc/dwcapubhandler.php
 *
 * Variables:
 *   collid (default: 0 [all collections]): PK for targeted collection. More than one codes can be supplied by separating them commas (e.g. collid=2,3,77)
 *   cond: search variable conditions to limit return based on the indexed Darwin Core fields
 *   Format: cond=<field1>-<optional operator>:<value1>;<field2>-<optional operator>:<value2>
 *   Fields allowed: catalognumber, othercatalognumbers, occurrenceid, family, sciname, country, stateprovince, county, municipality, recordedby, recordnumber, eventdate, decimallatitude, decimallongitude, minimumelevationinmeters, maximumelevationinmeters, processingstatus, datelastmodified, dateentered
 *   Optional operators: EQUALS, NULL, NOTNULL, START, LIKE, LESSTHAN, GREATERTHAN
 *   Note the dash separating the field and operator; operator is not case specific
 *   Multiple conditions can be supplied separated by semicolons
 *   Multiple values can be supplied is separated by commas
 *   usethes (0 or 1, default: 0): when searching on a taxonomic name, setting usethes to 1 will run search term through the taxonomic thesaurus to include synonym within the search
 *   Occurrence record return limited to 1,000,000 records
 *
 * Return: Darwin Core Archive of matching occurrences, associated images, identification history, and other associated data extensions
 *
 * Examples:
 *
 *   http://swbiodiversity.org/seinet/webservices/dwc/dwcapubhandler.php?collid=17
 *   http://swbiodiversity.org/seinet/webservices/dwc/dwcapubhandler.php?collid=17&cond=stateProvince:New%20Mexico
 *   http://mycoportal.org/portal/webservices/dwc/dwcapubhandler.php?collid=15&cond=dateentered-greaterthan=2015-08-01
 *   http://lichenportal.org/portal/webservices/dwc/dwcapubhandler.php?collid=22&cond=processingstatus-equals:reviewed;dateentered-greaterthan:2015-04-01
 *   http://swbiodiversity.org/seinet/webservices/dwc/dwcapubhandler.php?cond=sciname:Berberis%20repens
 *   http://swbiodiversity.org/seinet/webservices/dwc/dwcapubhandler.php?cond=sciname:Berberis%20repens&usethes=1
 *
 */

include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');

$collid = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$cond = array_key_exists("cond",$_REQUEST)?$_REQUEST["cond"]:'';
$collType = array_key_exists("colltype",$_REQUEST)?$_REQUEST["colltype"]:'specimens';
$schemaType = array_key_exists('schema',$_REQUEST)?$_REQUEST['schema']:'dwc';
$extended = array_key_exists('extended',$_REQUEST)?$_REQUEST['extended']:0;
$includeDets = array_key_exists("dets",$_REQUEST)?$_REQUEST["dets"]:1;
$includeImgs = array_key_exists("imgs",$_REQUEST)?$_REQUEST["imgs"]:1;
$includeAttributes = array_key_exists("attr",$_REQUEST)?$_REQUEST["attr"]:0;

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
$dwcaHandler->setSchemaType($schemaType);
$dwcaHandler->setExtended($extended);
$dwcaHandler->setIncludeDets($includeDets);
$dwcaHandler->setIncludeImgs($includeImgs);
$dwcaHandler->setIncludeAttributes($includeAttributes);

$archiveFile = $dwcaHandler->createDwcArchive();
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
?>