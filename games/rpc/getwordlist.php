<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');

$con = MySQLiConnectionFactory::getCon("readonly");

$clid = array_key_exists('clid',$_REQUEST)?$_REQUEST['clid']:"";
if($clid && !is_numeric($clid)) $clid = 0;
$dynClid = array_key_exists('dynclid',$_REQUEST)?$_REQUEST['dynclid']:"";
if($dynClid && !is_numeric($dynClid)) $dynClid = 0;

$linkQuery = '';
if($clid){
	$linkQuery = 'SELECT DISTINCT IFNULL(cl.familyoverride,ts.family) AS family, CONCAT_WS(" ",t.unitind1,t.unitname1,t.unitind2,t.unitname2) AS sciname '.
		'FROM fmchklsttaxalink cl INNER JOIN taxa t ON cl.tid = t.tid '.
		'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
		'WHERE cl.clid = '.$clid.' AND ts.taxauthid = 1 ORDER BY RAND() LIMIT 25';
}
elseif($dynClid){
	$linkQuery = 'SELECT DISTINCT ts.family, CONCAT_WS(" ",t.unitind1,t.unitname1,t.unitind2,t.unitname2) AS sciname '.
		'FROM fmdyncltaxalink cl INNER JOIN taxa t ON cl.tid = t.tid '.
		'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
		'WHERE cl.dynclid = '.$dynClid.' AND ts.taxauthid = 1 ORDER BY RAND() LIMIT 25';
}

if($linkQuery){
	$linkResult = $con->query($linkQuery);
	$retStr = "";
	while($linkArray = $linkResult->fetch_assoc()){
		$retStr .= ",\n[\"".$linkArray['sciname']."\",\"".$linkArray['family']."\"]";
	}
	$linkResult->free();
	echo "mainList=[".substr($retStr,1)."\n]";
}
$con->close();
?>