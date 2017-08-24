<?php
	include_once('../../../config/symbini.php');
	include_once($serverRoot.'/classes/OccurrenceActionManager.php');
	
	$occid = array_key_exists('occid',$_REQUEST)             ? $_REQUEST['occid']        : null;
	$requesttype = array_key_exists('requesttype',$_REQUEST) ? $_REQUEST['requesttype']  : null ;
	$remarks = array_key_exists('remarks',$_REQUEST)         ? $_REQUEST['remarks']      : '';
    $uid = $SYMB_UID;	

    if ($uid!=null) { 
	   $actionManager = new OccurrenceActionManager();
       $result = $actionManager->makeOccurrenceActionRequest($uid,$occid,$requesttype,$remarks);
       if ($result==null) { 
          $returnValue = "Failed to add request. " . $actionManager->getErrorMessage();
       } else { 
          $returnValue = "Added Request for $requesttype [$result]";
       }
    } 

	echo $returnValue;
?>
