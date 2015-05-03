<?php
if(!isset($CLIENT_ROOT) && isset($clientRoot)) $CLIENT_ROOT = $clientRoot; 
if(!isset($SERVER_ROOT) && isset($serverRoot)) $SERVER_ROOT = $serverRoot;

set_include_path(get_include_path() . PATH_SEPARATOR . $SERVER_ROOT . PATH_SEPARATOR . $SERVER_ROOT."/config/" . PATH_SEPARATOR . $SERVER_ROOT."/classes/");
date_default_timezone_set('America/Phoenix');

if(substr($CLIENT_ROOT,-1) == '/'){
	$CLIENT_ROOT = substr($CLIENT_ROOT,0,strlen($CLIENT_ROOT)-1);
}
if(substr($SERVER_ROOT,-1) == '/'){
	$SERVER_ROOT = substr($SERVER_ROOT,0,strlen($SERVER_ROOT)-1);
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

$CSS_VERSION = 'ver=20150403';
$USER_DISPLAY_NAME = (array_key_exists("dn",$PARAMS_ARR)?$PARAMS_ARR["dn"]:"");
$USERNAME = (array_key_exists("un",$PARAMS_ARR)?$PARAMS_ARR["un"]:0);
$SYMB_UID = (array_key_exists("uid",$PARAMS_ARR)?$PARAMS_ARR["uid"]:0);
$IS_ADMIN = (array_key_exists("SuperAdmin",$USER_RIGHTS)?1:0);
//Can get rid of following once all parameters are remapped to constants
$paramsArr = $PARAMS_ARR;
$userRights = $USER_RIGHTS;
$userDisplayName = $USER_DISPLAY_NAME;
$symbUid = $SYMB_UID;
$isAdmin = $IS_ADMIN;

//Temporarly needed so that old configuration will still work
if(!isset($DEFAULT_LANG) && isset($defaultLang)) $DEFAULT_LANG = $defaultLang;
if(!isset($DEFAULT_PROJ_ID) && isset($defaultProjId)) $DEFAULT_PROJ_ID = $defaultProjId;
if(!isset($DEFAULT_TITLE) && isset($defaultTitle)) $DEFAULT_TITLE = $defaultTitle;
if(!isset($ADMIN_EMAIL) && isset($adminEmail)) $ADMIN_EMAIL = $adminEmail;
if(!isset($CHARSET) && isset($charset)) $CHARSET = $charset;
if(!isset($TEMP_DIR_ROOT) && isset($tempDirRoot)) $TEMP_DIR_ROOT = $tempDirRoot;  
if(!isset($LOG_PATH) && isset($logPath)) $LOG_PATH = $logPath; 
if(!isset($IMAGE_DOMAIN) && isset($imageDomain)) $IMAGE_DOMAIN = $imageDomain; 
if(!isset($IMAGE_ROOT_URL) && isset($imageRootUrl)) $IMAGE_ROOT_URL = $imageRootUrl;
if(!isset($IMAGE_ROOT_PATH) && isset($imageRootPath)) $IMAGE_ROOT_PATH = $imageRootPath;
if(!isset($IMG_WEB_WIDTH) && isset($imgWebWidth)) $IMG_WEB_WIDTH = $imgWebWidth;
if(!isset($IMG_TN_WIDTH) && isset($imgTnWidth)) $IMG_TN_WIDTH = $imgTnWidth;
if(!isset($IMG_LG_WIDTH) && isset($imgLgWidth)) $IMG_LG_WIDTH = $imgLgWidth;
if(!isset($IMG_FILE_SIZE_LIMIT) && isset($imgFileSizeLimit)) $IMG_FILE_SIZE_LIMIT = $imgFileSizeLimit;  
if(!isset($USE_IMAGE_MAGICK) && isset($useImageMagick)) $USE_IMAGE_MAGICK = $useImageMagick;
if(!isset($TESSERACT_PATH) && isset($tesseractPath)) $TESSERACT_PATH = $tesseractPath;
if(!isset($OCCURRENCE_MOD_IS_ACTIVE) && isset($occurrenceModIsActive)) $OCCURRENCE_MOD_IS_ACTIVE = $occurrenceModIsActive;
if(!isset($FLORA_MOD_IS_ACTIVE) && isset($floraModIsActive)) $FLORA_MOD_IS_ACTIVE = $floraModIsActive;
if(!isset($KEY_MOD_IS_ACTIVE) && isset($keyModIsActive)) $KEY_MOD_IS_ACTIVE = $keyModIsActive;
if(!isset($REQUEST_TRACKING_IS_ACTIVE) && isset($RequestTrackingIsActive)) $REQUEST_TRACKING_IS_ACTIVE = $RequestTrackingIsActive;
if(!isset($FP_ENABLED) && isset($fpEnabled)) $FP_ENABLED = $fpEnabled;
if(!isset($GOOGLE_MAP_KEY) && isset($googleMapKey)) $GOOGLE_MAP_KEY = $googleMapKey; 
if(!isset($MAPPING_BOUNDARIES) && isset($mappingBoundaries)) $MAPPING_BOUNDARIES = $mappingBoundaries;
if(!isset($GOOGLE_ANALYTICS_KEY) && isset($googleAnalyticsKey)) $GOOGLE_ANALYTICS_KEY = $googleAnalyticsKey;
if(!isset($DYN_CHECKLIST_RADIUS) && isset($dynChecklistRadius)) $DYN_CHECKLIST_RADIUS = $dynChecklistRadius;
if(!isset($DISPLAY_COMMON_NAMES) && isset($displayCommonNames)) $DISPLAY_COMMON_NAMES = $displayCommonNames;
if(!isset($RIGHTS_TERMS) && isset($rightsTerms)) $RIGHTS_TERMS = $rightsTerms;
if(!isset($REPRODUCTIVE_CONDITION_TERMS) && isset($reproductiveConditionTerms)) $REPRODUCTIVE_CONDITION_TERMS = $reproductiveConditionTerms;

//temporatly needed until all variables within code are mapped to constants
if(!isset($clientRoot) && isset($CLIENT_ROOT)) $clientRoot = $CLIENT_ROOT; 
if(!isset($serverRoot) && isset($SERVER_ROOT)) $serverRoot = $SERVER_ROOT;
if(!isset($defaultLang) && isset($DEFAULT_LANG)) $defaultLang = $DEFAULT_LANG;
if(!isset($defaultProjId) && isset($DEFAULT_PROJ_ID)) $defaultProjId = $DEFAULT_PROJ_ID;
if(!isset($defaultTitle) && isset($DEFAULT_TITLE)) $defaultTitle = $DEFAULT_TITLE;
if(!isset($adminEmail) && isset($ADMIN_EMAIL)) $adminEmail = $ADMIN_EMAIL;
if(!isset($charset) && isset($CHARSET)) $charset = $CHARSET;
if(!isset($tempDirRoot) && isset($TEMP_DIR_ROOT)) $tempDirRoot = $TEMP_DIR_ROOT;  
if(!isset($logPath) && isset($LOG_PATH)) $logPath = $LOG_PATH; 
if(!isset($imageDomain) && isset($IMAGE_DOMAIN)) $imageDomain = $IMAGE_DOMAIN; 
if(!isset($imageRootUrl) && isset($IMAGE_ROOT_URL)) $imageRootUrl = $IMAGE_ROOT_URL;
if(!isset($imageRootPath) && isset($IMAGE_ROOT_PATH)) $imageRootPath = $IMAGE_ROOT_PATH;
if(!isset($imgWebWidth) && isset($IMG_WEB_WIDTH)) $imgWebWidth = $IMG_WEB_WIDTH;
if(!isset($imgTnWidth) && isset($IMG_TN_WIDTH)) $imgTnWidth = $IMG_TN_WIDTH;
if(!isset($imgLgWidth) && isset($IMG_LG_WIDTH)) $imgLgWidth = $IMG_LG_WIDTH;
if(!isset($imgFileSizeLimit) && isset($IMG_FILE_SIZE_LIMIT)) $imgFileSizeLimit = $IMG_FILE_SIZE_LIMIT;  
if(!isset($useImageMagick) && isset($USE_IMAGE_MAGICK)) $useImageMagick = $USE_IMAGE_MAGICK;
if(!isset($tesseractPath) && isset($TESSERACT_PATH)) $tesseractPath = $TESSERACT_PATH;
if(!isset($occurrenceModIsActive) && isset($OCCURRENCE_MOD_IS_ACTIVE)) $occurrenceModIsActive = $OCCURRENCE_MOD_IS_ACTIVE;
if(!isset($floraModIsActive) && isset($FLORA_MOD_IS_ACTIVE)) $floraModIsActive = $FLORA_MOD_IS_ACTIVE;
if(!isset($keyModIsActive) && isset($KEY_MOD_IS_ACTIVE)) $keyModIsActive = $KEY_MOD_IS_ACTIVE;
if(!isset($RequestTrackingIsActive) && isset($REQUEST_TRACKING_IS_ACTIVE)) $RequestTrackingIsActive = $REQUEST_TRACKING_IS_ACTIVE;
if(!isset($fpEnabled) && isset($FP_ENABLED)) $fpEnabled = $FP_ENABLED;
if(!isset($googleMapKey) && isset($GOOGLE_MAP_KEY)) $googleMapKey = $GOOGLE_MAP_KEY; 
if(!isset($mappingBoundaries) && isset($MAPPING_BOUNDARIES)) $mappingBoundaries = $MAPPING_BOUNDARIES;
if(!isset($googleAnalyticsKey) && isset($GOOGLE_ANALYTICS_KEY)) $googleAnalyticsKey = $GOOGLE_ANALYTICS_KEY;
if(!isset($dynChecklistRadius) && isset($DYN_CHECKLIST_RADIUS)) $dynChecklistRadius = $DYN_CHECKLIST_RADIUS;
if(!isset($displayCommonNames) && isset($DISPLAY_COMMON_NAMES)) $displayCommonNames = $DISPLAY_COMMON_NAMES;
if(!isset($rightsTerms) && isset($RIGHTS_TERMS)) $rightsTerms = $RIGHTS_TERMS;
if(!isset($reproductiveConditionTerms) && isset($REPRODUCTIVE_CONDITION_TERMS)) $reproductiveConditionTerms = $REPRODUCTIVE_CONDITION_TERMS;
?>