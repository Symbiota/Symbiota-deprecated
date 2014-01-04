<?php
$defaultLang = 'English';		//Default language
$defaultProjId = 1;
$DEFAULTCATID = 2;
$defaultTitle = '';
$adminEmail = '';
$charset = '';					//ISO-8859-1 or UTF-8
$PORTAL_GUID = '';

$clientRoot = '';				//URL path to project root folder (relative path w/o domain, e.g. '/seinet') 
$serverRoot = '';				//Full path to Symbiota project root folder
$tempDirRoot = '';				//Must be writable by Apache; will use system default if not specified  
$logPath = '';					//Must be writable by Apache; will use <SYMBIOTA_ROOT>/temp/logs if not specified 

//the root for the image directory
$imageDomain = '';				//Domain path to images, if different from Virtual Flora portal 
$imageRootUrl = '';				//URL path to images
$imageRootPath = '';			//Writable path to images, especially needed for downloading images

//Pixel witdth of web images
$imgWebWidth = 1600;
$imgTnWidth = 200;
$imgLgWidth = 3200;
$imgFileSizeLimit = 300000;		//Files above this size limit and still within pixel width limits will still be resaved w/ some compression  

//Specimen Label and Batch Image Processor variables
//$useImageMagick = 0;		//1 = ImageMagick resize images, given that it's installed (faster, less memory intensive)
$tesseractPath = ''; 			//Needed for OCR function in the occurrence editor page
$NLP_LBCC_ACTIVATED = 0;
//$SALIX_PATH = 'http://symbiota2.acis.ufl.edu/symbiota/dlafferty/salixhandler.php';

//Module activations
$occurrenceModIsActive = 1;
$floraModIsActive = 1;
$keyModIsActive = 1;

$fpEnabled = 0;				//Enable Filtered-Push modules

//Misc variables
$googleMapKey = '';					//Needed for Google Map; get from Google 
$mappingBoundaries = '';			//Project bounding box; default map centering; (e.g. 42.3;-100.5;18.0;-127)
$googleAnalyticsKey = '';			//Needed for setting up Google Analytics 
$dynChecklistRadius = 10;			//Controls size of concentric rings that are sampled when building Dynamic Checklist
$displayCommonNames = 1;

$rightsTerms = array(
	'CC0 1.0 (Public-domain)' => 'http://creativecommons.org/publicdomain/zero/1.0/',
	'CC BY (Attribution)' => 'http://creativecommons.org/licenses/by/3.0/',
	'CC BY-SA (Attribution-ShareAlike)' => 'http://creativecommons.org/licenses/by-sa/3.0/',
	'CC BY-NC (Attribution-Non-Commercial)' => 'http://creativecommons.org/licenses/by-nc/3.0/',
	'CC BY-NC-SA (Attribution-NonCommercial-ShareAlike)' => 'http://creativecommons.org/licenses/by-nc-sa/3.0/'
);

//set terms for reproductiveCondition drop-down menu
//$reproductiveConditionTerms = array("term1", "term2", "term3", "term4", "term5", "etc..");

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
		
//Base code shared by all pages; leave as is
include_once("symbbase.php");
/* --DO NOT ADD ANY EXTRA SPACES BELOW THIS LINE-- */?>