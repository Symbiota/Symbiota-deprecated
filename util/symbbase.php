<?php

//Check cookie to see if signed in
$paramsArr = Array();				//params => fn, uid, un   cookie(SymbiotaBase) => 'un=egbot&dn=Edward+Gilbert&uid=301'
$userRights = Array();
if((isset($_COOKIE["SymbiotaBase"]) && (!isset($submit) || $submit != "logout"))){
    $userValue = $_COOKIE["SymbiotaBase"];
    $userValues =	explode("&",$userValue);
    foreach($userValues as $val){
        $tok1 = strtok($val, "=");
        $tok2 = strtok("=");
        $paramsArr[$tok1] = $tok2;
    }
	//Check user rights
	if(isset($_COOKIE["SymbiotaRights"])){
        $userRightsStr = $_COOKIE["SymbiotaRights"];
		$userRights = explode("&",$userRightsStr);
	}
}

$userDisplayName = (array_key_exists("dn",$paramsArr)?$paramsArr["dn"]:"");
$symbUid = (array_key_exists("uid",$paramsArr)?$paramsArr["uid"]:0);
$isAdmin = (in_array("SuperAdmin",$userRights)?1:0);
?>