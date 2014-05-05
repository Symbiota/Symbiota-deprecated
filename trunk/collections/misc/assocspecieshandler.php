<?php
date_default_timezone_set('America/Phoenix');
require_once('../../config/symbini.php');
require_once($serverRoot.'/classes/OccurrenceUtilities.php');

$assocHandler = new OccurrenceUtilities();
$assocHandler->buildAssociatedTaxaIndex(1);

?>