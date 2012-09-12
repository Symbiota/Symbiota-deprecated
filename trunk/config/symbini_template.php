<?php
$defaultLang = 'English';		//Default language
$defaultProjId = 1;
$defaultTitle = '';
$adminEmail = '';
$charset = 'ISO-8859-1';		//ISO-8859-1 or UTF-8

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
//$useImageMagick = 0;			//Set to 1 to have ImageMagick resize images, given that it's installed (faster, less memory intensive)

$tesseractPath = ''; //Needed for OCR function in the occurrence editor page

//Module activations
$occurrenceModIsActive = 1;
$floraModIsActive = 1;
$keyModIsActive = 1;

//Misc variables
$googleMapKey = '';					//Needed for Google Map; get from Google 
$mappingBoundaries = '';			//Project bounding box; default map centering; (e.g. 42.3;-100.5;18.0;-127)
$googleAnalyticsKey = '';			//Needed for setting up Google Analytics 
$dynChecklistRadius = 10;			//Controls size of concentric rings that are sampled when building Dynamic Checklist
$displayCommonNames = 1;			//0 = false, 1 = true

//Individual page menu and navigation crumbs
//set terms for reproductiveCondition drop-down menu
//$reproductiveConditionTerms = array("term1", "term2", "term3", "term4", "term5", "etc..");

//checklists/
	$checklists_checklistMenu = false;

//collections/
	$collections_indexMenu = false;
	$collections_harvestparamsMenu = false;
	$collections_listMenu = false;
	$collections_checklistMenu = false;
	$collections_download_downloadMenu = false;
	$collections_maps_indexMenu = false;
	
//ident/
	$ident_keyMenu = false;
	$ident_admin_indexCrumbs = false;
	$ident_tools_chardeficitMenu = false;
	$ident_tools_massupdateMenu = false;
	$ident_tools_editorMenu = false;
	
//taxa/
	$taxa_indexMenu = false;
	$taxa_admin_tpeditorMenu = false;
	
//loans/
	$collections_loans_indexCrumbs = false;
		
//Base code shared by all pages; leave as is
include_once("symbbase.php");
?>