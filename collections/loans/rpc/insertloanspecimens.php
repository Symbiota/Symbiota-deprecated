<?php
	include_once('../../../config/symbini.php');
	include_once($serverRoot.'/classes/SpecLoans.php');
	$retMsg = 0;
	
	$loanId = $_REQUEST['loanid'];
	$catalogNumber = $_REQUEST['catalognumber'];
	$collId = $_REQUEST['collid'];

	if($loanId && $collId && $catalogNumber && is_numeric($loanId) && is_numeric($collId)){
		if($isAdmin 
		|| ((array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])) 
		|| (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"])))){
			$loanManager = new SpecLoans();
			$retMsg = $loanManager->addSpecimen($loanId,$collId,$catalogNumber);		
		}
	}
	echo $retMsg;
?>