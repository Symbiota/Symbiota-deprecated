<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceSkeletal.php');

$occid = '';
$skelHandler = new OccurrenceSkeletal();
if(array_key_exists('collid',$_REQUEST) && array_key_exists('catalognumber',$_REQUEST)){
	$skelHandler->setCollid($_REQUEST['collid']);
	if($skelHandler->catalogNumberExists($_REQUEST['catalognumber'])){
		echo 'dupcat:'.$skelHandler->getErrorStr();
	}
	else{
		$occid = $skelHandler->occurrenceAdd($_REQUEST);
		if($occid && is_numeric($occid)){
			echo $occid;
		}
		else{
			echo $skelHandler->getErrorStr();
		}
	}
}
?>