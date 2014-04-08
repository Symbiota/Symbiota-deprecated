<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/SalixUtilities.php');
header("Content-Type: text/html; charset=".$charset);

$salixhanlder = new SalixUtilities();
$salixhanlder->batchWordStats('1,3');

?>