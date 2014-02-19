<?php
set_include_path(get_include_path() . PATH_SEPARATOR . $serverRoot . PATH_SEPARATOR . $serverRoot."/config/" . PATH_SEPARATOR . $serverRoot."/classes/");
date_default_timezone_set('America/Phoenix');

if(substr($clientRoot,-1) == '/'){
	$clientRoot = substr($clientRoot,0,strlen($clientRoot)-1);
}
if(substr($serverRoot,-1) == '/'){
	$serverRoot = substr($serverRoot,0,strlen($serverRoot)-1);
}

//Check cookie to see if signed in
$PARAMS_ARR = Array();				//params => fn, uid, un   cookie(SymbiotaBase) => 'un=egbot&dn=Edward+Gilbert&uid=301'
$USER_RIGHTS = Array();
if((isset($_COOKIE["SymbiotaBase"]) && (!isset($submit) || $submit != "logout"))){
    $userValue = $_COOKIE["SymbiotaBase"];
    $userValues =	explode("&",$userValue);
    foreach($userValues as $val){
        $tok1 = strtok($val, "=");
        $tok2 = strtok("=");
        $PARAMS_ARR[$tok1] = $tok2;
    }
	//Check user rights
	if(isset($_COOKIE["SymbiotaRights"])){
        $userRightsStr = $_COOKIE["SymbiotaRights"];
		$uRights = explode("&",$userRightsStr);
		foreach($uRights as $v){
			$tArr = explode("-",$v);
			if(count($tArr) > 1){
				if(strpos($tArr[1],',')){
					$USER_RIGHTS[$tArr[0]] = explode(',',$tArr[1]);
				}
				else{
					$USER_RIGHTS[$tArr[0]][] = $tArr[1];
				}
			}
			else{
				$USER_RIGHTS[$tArr[0]] = "";
			}
		}
	}
}

$USER_DISPLAY_NAME = (array_key_exists("dn",$PARAMS_ARR)?$PARAMS_ARR["dn"]:"");
$USERNAME = (array_key_exists("un",$PARAMS_ARR)?$PARAMS_ARR["un"]:0);
$SYMB_UID = (array_key_exists("uid",$PARAMS_ARR)?$PARAMS_ARR["uid"]:0);
$IS_ADMIN = (array_key_exists("SuperAdmin",$USER_RIGHTS)?1:0);
//Need to get rid of following once all parameters are remapped to constants
$paramsArr = $PARAMS_ARR;
$userRights = $USER_RIGHTS;
$userDisplayName = $USER_DISPLAY_NAME;
$symbUid = $SYMB_UID;
$isAdmin = $IS_ADMIN;
?>