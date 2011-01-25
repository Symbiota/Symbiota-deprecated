<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');

$clid = array_key_exists('clid',$_REQUEST)?$_REQUEST['clid']:"";
$dynClid = array_key_exists('dynclid',$_REQUEST)?$_REQUEST['dynclid']:"";

$con = MySQLiConnectionFactory::getCon("readonly");

$linkQuery = '';
if($clid){
	$linkQuery = 'SELECT IFNULL(cl.familyoverride,ts.family) AS family, t.sciname '.
		'FROM fmchklsttaxalink cl INNER JOIN taxa t ON cl.tid = t.tid '.
		'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
		'WHERE cl.clid = '.$clid.' AND ts.taxauthid = 1 ORDER BY RAND() LIMIT 25';
}
elseif($dynClid){
	$linkQuery = 'SELECT ts.family, t.sciname '.
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
	echo "mainList=[".substr($retStr,1)."\n]";
}
?>