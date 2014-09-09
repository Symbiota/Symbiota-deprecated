<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/SalixUtilities.php');
header("Content-Type: text/html; charset=".$charset);

$collStr = (isset($_REQUEST['collstr'])?$_REQUEST['collstr']:'');

if($collStr){
	$salixhanlder = new SalixUtilities();
	echo '<ul>';
	$salixhanlder->batchWordStats($collStr);
	echo '</ul>';
}
?>