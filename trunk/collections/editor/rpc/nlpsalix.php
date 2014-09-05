<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcNlpUtilities.php');
include_once($serverRoot.'/classes/SpecProcNlpSalix.php');
header("Content-Type: text/html; charset=UTF-8");

$rawOcr = $_REQUEST['rawocr'];

$nlpManager = new SpecProcNlpSalix();
$dwcArr = array();
if($rawOcr){
	$dwcArr = $nlpManager->parse($rawOcr);
	$dwcArr = SpecProcNlpUtilities::cleanDwcArr($dwcArr);
}

echo json_encode($dwcArr);
?>