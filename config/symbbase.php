<?php
if(!isset($CLIENT_ROOT) && isset($clientRoot)) $CLIENT_ROOT = $clientRoot; 
if(!isset($SERVER_ROOT) && isset($serverRoot)) $SERVER_ROOT = $serverRoot;
include_once($SERVER_ROOT.'/classes/Encryption.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
ini_set('session.gc_maxlifetime',3600);
session_start();
header('Cache-control: private'); // IE 6 FIX

set_include_path(get_include_path() . PATH_SEPARATOR . $SERVER_ROOT . PATH_SEPARATOR . $SERVER_ROOT."/config/" . PATH_SEPARATOR . $SERVER_ROOT."/classes/");
date_default_timezone_set('America/Phoenix');

if(substr($CLIENT_ROOT,-1) == '/'){
	$CLIENT_ROOT = substr($CLIENT_ROOT,0,strlen($CLIENT_ROOT)-1);
}
if(substr($SERVER_ROOT,-1) == '/'){
	$SERVER_ROOT = substr($SERVER_ROOT,0,strlen($SERVER_ROOT)-1);
}

//Check cookie to see if signed in
$PARAMS_ARR = Array();				//params => 'un=egbot&dn=Edward+Gilbert&uid=301'
$USER_RIGHTS = Array();
if((isset($_COOKIE["SymbiotaCrumb"]) && (!isset($_REQUEST['submit']) || $_REQUEST['submit'] != "logout"))){
    $tokenArr = json_decode(Encryption::decrypt($_COOKIE["SymbiotaCrumb"]), true);
    if($tokenArr){
        $pHandler = new ProfileManager();
        if($pHandler->setUserName($tokenArr[0])){
            $pHandler->setRememberMe(true);
            $pHandler->setToken($tokenArr[1]);
            $pHandler->setTokenAuthSql();
            if(!$pHandler->authenticate()){
                $pHandler->reset();
            }
        }
        $pHandler->__destruct();
    }
}

if((isset($_COOKIE["SymbiotaCrumb"]) && ((isset($_REQUEST['submit']) && $_REQUEST['submit'] == "logout") || isset($_REQUEST['loginas'])))){
    $tokenArr = json_decode(Encryption::decrypt($_COOKIE["SymbiotaCrumb"]), true);
    if($tokenArr){
        $pHandler = new ProfileManager();
        $uid = $pHandler->getUid($tokenArr[0]);
        $pHandler->deleteToken($uid,$tokenArr[1]);
        $pHandler->__destruct();
    }
}

if(isset($_SESSION['userparams'])){
    $PARAMS_ARR = $_SESSION['userparams'];
}

if(isset($_SESSION['userrights'])){
    $USER_RIGHTS = $_SESSION['userrights'];
}

$CSS_VERSION = '5';
if(!isset($CSS_VERSION_LOCAL)) $CSS_VERSION_LOCAL = $CSS_VERSION;
$USER_DISPLAY_NAME = (array_key_exists("dn",$PARAMS_ARR)?$PARAMS_ARR["dn"]:"");
$USERNAME = (array_key_exists("un",$PARAMS_ARR)?$PARAMS_ARR["un"]:0);
$SYMB_UID = (array_key_exists("uid",$PARAMS_ARR)?$PARAMS_ARR["uid"]:0);
$IS_ADMIN = (array_key_exists("SuperAdmin",$USER_RIGHTS)?1:0);
$SOLR_MODE = ((isset($SOLR_URL) && $SOLR_URL)?true:false);
$CHECKLIST_FG_EXPORT = ((isset($ACTIVATE_CHECKLIST_FG_EXPORT) && $ACTIVATE_CHECKLIST_FG_EXPORT)?true:false);
$FIELDGUIDE_ACTIVE = ((isset($ACTIVATE_FIELDGUIDE) && $ACTIVATE_FIELDGUIDE)?true:false);
$GEOLOCATION = ((isset($ACTIVATE_GEOLOCATION) && $ACTIVATE_GEOLOCATION)?true:false);
$BROADGEOREFERENCE = ((isset($GEOREFERENCE_POLITICAL_DIVISIONS) && $GEOREFERENCE_POLITICAL_DIVISIONS)?true:false);
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
if(!isset($QUICK_HOST_ENTRY_IS_ACTIVE) && isset($QuickHostEntryIsActive)) $QUICK_HOST_ENTRY_IS_ACTIVE = $QuickHostEntryIsActive;
if(!isset($FP_ENABLED) && isset($fpEnabled)) $FP_ENABLED = $fpEnabled;
if(!isset($GOOGLE_MAP_KEY) && isset($googleMapKey)) $GOOGLE_MAP_KEY = $googleMapKey; 
if(!isset($MAPPING_BOUNDARIES) && isset($mappingBoundaries)) $MAPPING_BOUNDARIES = $mappingBoundaries;
if(!isset($GOOGLE_ANALYTICS_KEY) && isset($googleAnalyticsKey)) $GOOGLE_ANALYTICS_KEY = $googleAnalyticsKey;
if(!isset($DYN_CHECKLIST_RADIUS) && isset($dynChecklistRadius)) $DYN_CHECKLIST_RADIUS = $dynChecklistRadius;
if(!isset($DISPLAY_COMMON_NAMES) && isset($displayCommonNames)) $DISPLAY_COMMON_NAMES = $displayCommonNames;
if(!isset($RIGHTS_TERMS) && isset($rightsTerms)) $RIGHTS_TERMS = $rightsTerms;
if(!isset($REPRODUCTIVE_CONDITION_TERMS) && isset($reproductiveConditionTerms)) $REPRODUCTIVE_CONDITION_TERMS = $reproductiveConditionTerms;
if(!isset($GLOSSARY_EXPORT_BANNER) && isset($glossaryExportBanner)) $GLOSSARY_EXPORT_BANNER = $glossaryExportBanner;

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
if(!isset($QuickHostEntryIsActive) && isset($QUICK_HOST_ENTRY_IS_ACTIVE)) $QuickHostEntryIsActive = $QUICK_HOST_ENTRY_IS_ACTIVE;
if(!isset($fpEnabled) && isset($FP_ENABLED)) $fpEnabled = $FP_ENABLED;
if(!isset($googleMapKey) && isset($GOOGLE_MAP_KEY)) $googleMapKey = $GOOGLE_MAP_KEY; 
if(!isset($mappingBoundaries) && isset($MAPPING_BOUNDARIES)) $mappingBoundaries = $MAPPING_BOUNDARIES;
if(!isset($googleAnalyticsKey) && isset($GOOGLE_ANALYTICS_KEY)) $googleAnalyticsKey = $GOOGLE_ANALYTICS_KEY;
if(!isset($dynChecklistRadius) && isset($DYN_CHECKLIST_RADIUS)) $dynChecklistRadius = $DYN_CHECKLIST_RADIUS;
if(!isset($displayCommonNames) && isset($DISPLAY_COMMON_NAMES)) $displayCommonNames = $DISPLAY_COMMON_NAMES;
if(!isset($rightsTerms) && isset($RIGHTS_TERMS)) $rightsTerms = $RIGHTS_TERMS;
if(!isset($reproductiveConditionTerms) && isset($REPRODUCTIVE_CONDITION_TERMS)) $reproductiveConditionTerms = $REPRODUCTIVE_CONDITION_TERMS;
if(!isset($glossaryExportBanner) && isset($GLOSSARY_EXPORT_BANNER)) $glossaryExportBanner = $GLOSSARY_EXPORT_BANNER;

//Multi-langauge support
$LANG_TAG = 'en';
if(isset($_REQUEST['lang']) && $_REQUEST['lang']){
	$LANG_TAG = $_REQUEST['lang'];

	// register the session and set the cookie
	$_SESSION['lang'] = $LANG_TAG;
	setcookie('lang', $LANG_TAG, time() + (3600 * 24 * 30));
}
else if(isset($_SESSION['lang']) && $_SESSION['lang']){
	$LANG_TAG = $_SESSION['lang'];
}
else if(isset($_COOKIE['lang']) && $_COOKIE['lang']){
	$LANG_TAG = $_COOKIE['lang'];
}
else{
	if(strlen($DEFAULT_LANG) == 2) $LANG_TAG = $DEFAULT_LANG;
}
if(!$LANG_TAG || strlen($LANG_TAG) != 2) $LANG_TAG = 'en';

$RIGHTS_TERMS_DEFS = array(
    'http://creativecommons.org/publicdomain/zero/1.0/' => array(
        'title' => 'CC0 1.0 (Public-domain)',
        'url' => 'https://creativecommons.org/publicdomain/zero/1.0/legalcode',
        'def' => 'Users can copy, modify, distribute and perform the work, even for commercial purposes, all without asking permission.'
    ),
    'http://creativecommons.org/licenses/by/3.0/' => array(
        'title' => 'CC BY (Attribution)',
        'url' => 'http://creativecommons.org/licenses/by/3.0/legalcode',
        'def' => 'Users can copy, redistribute the material in any medium or format, remix, transform, and build upon the material for any purpose, even commercially. The licensor cannot revoke these freedoms as long as you follow the license terms.'
    ),
	'http://creativecommons.org/licenses/by-nc/3.0/' => array(
        'title' => 'CC BY-NC (Attribution-Non-Commercial)',
        'url' => 'http://creativecommons.org/licenses/by-nc/3.0/legalcode',
        'def' => 'Users can copy, redistribute the material in any medium or format, remix, transform, and build upon the material. The licensor cannot revoke these freedoms as long as you follow the license terms.'
    ),
	'http://creativecommons.org/licenses/by/4.0/' => array(
        'title' => 'CC BY (Attribution)',
        'url' => 'http://creativecommons.org/licenses/by/4.0/legalcode',
        'def' => 'Users can copy, redistribute the material in any medium or format, remix, transform, and build upon the material for any purpose, even commercially. The licensor cannot revoke these freedoms as long as you follow the license terms.'
    ),
	'http://creativecommons.org/licenses/by-nc/4.0/' => array(
        'title' => 'CC BY-NC (Attribution-Non-Commercial)',
        'url' => 'http://creativecommons.org/licenses/by-nc/4.0/legalcode',
        'def' => 'Users can copy, redistribute the material in any medium or format, remix, transform, and build upon the material. The licensor cannot revoke these freedoms as long as you follow the license terms.'
    )
);
?>