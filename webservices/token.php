<?php
/*
 * ****  Input Variables  ********************************************
 *
 * occid (optional): symbiota occurrence record PK. Required if guid is null.
 * recordid (optional): recordID GUID (UUID). Required if occid is null.
 * dwcobj (required): occurrence edits as a JSON representation of a DwC object (key/value pairs); data must be UTF-8
 * editor (optional): string representing editor
 * source (optional): string representing source
 * edittype (optional): occurrence, identification, comment
 * timestamp (optional): original timestamp of edit within external application
 * key (optional): security key used to authorize. May be enforced later
 *
 */

include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');

$un = array_key_exists('un',$_POST)?$_POST['un']:'';
$pw = array_key_exists('pw',$_POST)?$_POST['pw']:'';

$pHandler = new ProfileManager();

$tokenPacket = Array();

if($un && $pw){
    $pHandler->setUserName($un);
    if($pHandler->authenticate($pw)){
        $tokenPacket = $pHandler->generateTokenPacket();
        echo json_encode($tokenPacket);
    }
    else{
        echo 'Incorrect username/password.';
    }
}
?>