<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceSkeletal.php');

$collid = array_key_exists('collid',$_POST);
$isEditor = 0;
if($collid){
	if($IS_ADMIN){
		$isEditor = 1;
	}
	elseif(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin'])){
		$isEditor = 1;
	}
	elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollEditor'])){
		$isEditor = 1;
	}
}
if($isEditor){
	$skelHandler = new OccurrenceSkeletal();
	if(array_key_exists('collid',$_POST)){
		$skelHandler->setCollid($_POST['collid']);
		if(array_key_exists('catalognumber',$_POST) && $skelHandler->catalogNumberExists($_POST['catalognumber'])){
			echo 'dupcat:'.$skelHandler->getErrorStr();
		}
		else{
			$occid = $skelHandler->occurrenceAdd($_POST);
			if($occid && is_numeric($occid)){
				echo $occid;
			}
			else{
				echo $skelHandler->getErrorStr();
			}
		}
	}
}
?>