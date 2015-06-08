<?php
$DEFAULT_LANG = 'English';		//Default language
$DEFAULT_PROJ_ID = 1;
$DEFAULTCATID = 2;
$DEFAULT_TITLE = '';
$ADMIN_EMAIL = '';
$CHARSET = '';					//ISO-8859-1 or UTF-8
$PORTAL_GUID = '';

$CLIENT_ROOT = '';				//URL path to project root folder (relative path w/o domain, e.g. '/seinet') 
$SERVER_ROOT = '';				//Full path to Symbiota project root folder
$TEMP_DIR_ROOT = '';				//Must be writable by Apache; will use system default if not specified  
$LOG_PATH = '';					//Must be writable by Apache; will use <SYMBIOTA_ROOT>/temp/logs if not specified 

//the root for the image directory
$IMAGE_DOMAIN = '';				//Domain path to images, if different from Virtual Flora portal 
$IMAGE_ROOT_URL = '';				//URL path to images
$IMAGE_ROOT_PATH = '';			//Writable path to images, especially needed for downloading images

//Pixel witdth of web images
$IMG_WEB_WIDTH = 1400;
$IMG_TN_WIDTH = 200;
$IMG_LG_WIDTH = 3200;
$IMG_FILE_SIZE_LIMIT = 300000;		//Files above this size limit and still within pixel width limits will still be resaved w/ some compression  

//Specimen Label and Batch Image Processor variables
//$USE_IMAGE_MAGICK = 0;		//1 = ImageMagick resize images, given that it's installed (faster, less memory intensive)
$TESSERACT_PATH = ''; 			//Needed for OCR function in the occurrence editor page
$NLP_LBCC_ACTIVATED = 0;
$NLP_SALIX_ACTIVATED = 0;

//Module activations
$OCCURRENCE_MOD_IS_ACTIVE = 1;
$FLORA_MOD_IS_ACTIVE = 1;
$KEY_MOD_IS_ACTIVE = 1;
$REQUESTED_TRACKING_IS_ACTIVE = 1;   // Allow users to request actions such as requests for images to be made for specimens

$FP_ENABLED = 0;				//Enable Filtered-Push modules

//Misc variables
$GOOGLE_MAP_KEY = '';					//Needed for Google Map; get from Google 
$MAPPING_BOUNDARIES = '';			//Project bounding box; default map centering; (e.g. 42.3;-100.5;18.0;-127)
$GOOGLE_ANALYTICS_KEY = '';			//Needed for setting up Google Analytics
$RECAPTCHA_PUBLIC_KEY = '';	
$RECAPTCHA_PRIVATE_KEY = '';
$DYN_CHECKLIST_RADIUS = 10;			//Controls size of concentric rings that are sampled when building Dynamic Checklist
$DISPLAY_COMMON_NAMES = 1;
$ACTIVATE_EXSICCATI = 0;

$RIGHTS_TERMS = array(
	'CC0 1.0 (Public-domain)' => 'http://creativecommons.org/publicdomain/zero/1.0/',
	'CC BY (Attribution)' => 'http://creativecommons.org/licenses/by/3.0/',
	'CC BY-NC (Attribution-Non-Commercial)' => 'http://creativecommons.org/licenses/by-nc/3.0/'
);

//set terms for reproductiveCondition drop-down menu
//$REPRODUCTIVE_CONDITION_TERMS = array("term1", "term2", "term3", "term4", "term5", "etc..");

//Individual page menu and navigation crumbs
//Menu variables turn on and off the display of left menu 
//Crumb variables allow the customization of the bread crumbs. A crumb variable with an empty value will cause crumbs to disappear
//Variable name should include path to file separated by underscores and then the file name ending with "Menu" or "Crumbs"
//checklists/
	$checklists_checklistMenu = 0;
	//$checklists_checklistCrumbs = "<a href='../index.php'>Home</a> &gt;&gt; <a href='index.php'>Checklists</a> &gt;&gt; ";	
//collections/
	$collections_indexMenu = 0;
	$collections_harvestparamsMenu = 0;
	//$collections_harvestparamsCrumbs = "<a href='index.php'>Collections</a> &gt;&gt; ";
	$collections_listMenu = 0;
	$collections_checklistMenu = 0;
	$collections_download_downloadMenu = 0;
	$collections_maps_indexMenu = 0;
	
//ident/
	$ident_keyMenu = 0;
	$ident_tools_chardeficitMenu = 0;
	$ident_tools_massupdateMenu = 0;
	$ident_tools_editorMenu = 0;
	
//taxa/
	$taxa_indexMenu = 0;
	$taxa_admin_tpeditorMenu = 0;
	
//loans/
	$collections_loans_indexCrumbs = 0;

//agents/
    $agents_indexMenu = TRUE;
    $agent_indexCrumbs = array();
    array_push($agent_indexCrumbs,"<a href='$CLIENT_ROOT/index.php'>Home</a>");
    array_push($agent_indexCrumbs,"<a href='$CLIENT_ROOT/agents/index.php'>Agents</a>");
		
//Base code shared by all pages; leave as is
include_once("symbbase.php");
/* --DO NOT ADD ANY EXTRA SPACES BELOW THIS LINE-- */?>