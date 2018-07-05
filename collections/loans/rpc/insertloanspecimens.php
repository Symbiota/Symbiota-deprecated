<?php
	include_once('../../../config/symbini.php');
	include_once($SERVER_ROOT.'/classes/SpecLoans.php');
	$retMsg = 0;
	
	$loanId = $_REQUEST['loanid'];
	$catalogNumber = $_REQUEST['catalognumber'];
	$collId = $_REQUEST['collid'];

	if($loanId && $collId && $catalogNumber && is_numeric($loanId) && is_numeric($collId)){
		if($IS_ADMIN 
		|| ((array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"])) 
		|| (array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollEditor"])))){
			$loanManager = new SpecLoans();
			$retMsg = $loanManager->addSpecimen($loanId,$collId,$catalogNumber);		
		}
	}
	echo $retMsg;
?>