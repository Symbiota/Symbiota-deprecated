<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/PersonalSpecimenManager.php');

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$userId = array_key_exists("userid",$_REQUEST)?$_REQUEST["userid"]:0;

$specHandler = new PersonalSpecimenManager();

?>
<div>
	

<?php 
//Display number of specimens linked to your login
//Add a new specimen
	//standard
	//Collection note format
//Submit image voucher observation
//Simple editor
//List specimens 30 at a time with link to editor for each

//Download records as csv

//Quick link to label maker

//Record Importer

//Mass updater


?>	
</div>

