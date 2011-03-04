<?php
set_include_path(get_include_path() . PATH_SEPARATOR . $serverRoot . PATH_SEPARATOR . $serverRoot."/config/" . PATH_SEPARATOR . $serverRoot."/classes/");
date_default_timezone_set('America/Phoenix');

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
		$uRights = explode("&",$userRightsStr);
		foreach($uRights as $v){
			$tArr = explode("-",$v);
			if(count($tArr) > 1){
				$userRights[$tArr[0]][] = $tArr[1];
			}
			else{
				$userRights[$tArr[0]] = "";
			}
		}
	}
}

$userDisplayName = (array_key_exists("dn",$paramsArr)?$paramsArr["dn"]:"");
$symbUid = (array_key_exists("uid",$paramsArr)?$paramsArr["uid"]:0);
$isAdmin = (array_key_exists("SuperAdmin",$userRights)?1:0);
?>