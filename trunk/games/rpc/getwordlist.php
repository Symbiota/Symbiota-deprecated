<?php
$clid=$_REQUEST['clid'];
include_once('../../config/symbini.php');
include_once($serverRoot.'/config/dbconnection.php');

$con = MySQLiConnectionFactory::getCon("readonly");

$linkQuery = 'SELECT ts.family, t.sciname '.
	'FROM fmchklsttaxalink cl INNER JOIN taxa t ON cl.tid = t.tid '.
	'INNER JOIN taxstatus ts ON t.tid = ts.tid '.
	'WHERE cl.clid = '.$clid.' AND ts.taxauthid = 1 ORDER BY RAND() LIMIT 25';

$linkResult = $con->query($linkQuery);
	
$retStr = "";
while($linkArray = $linkResult->fetch_assoc()){
	$retStr .= ",\n[\"".$linkArray['sciname']."\",\"".$linkArray['family']."\"]";
}
echo "mainList=[".substr($retStr,1)."\n]";

?>