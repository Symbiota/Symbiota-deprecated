<?php
$defaultLang = 'English';			//Default language
$defaultProjId = 1;
$defaultTitle = '';
$adminEmail = '';
$charset = 'ISO-8859-1';			//ISO-8859-1 or UTF-8

$clientRoot = '';					//URL path to project root folder 
$serverRoot = '';					//Root path to Symbiota project root folder

//the root for the image directory
$imageDomain = '';					//Domain path to images, if different from Virtual Flora path (e.g. http://swbiodiversity.org)
$imageRootUrl = '';					//URL path to images
$imageRootPath = '';				//Writable path to images, especially needed for downloading images

//the root for the temp directory
$tempDirRoot = '';

$googleMapKey = '';					//Needed for Google Map; get from Google 
$mappingBoundaries = '';			//Project bounding box; default map centering; (e.g. 42.3;-100.5;18.0;-127)
$googleAnalyticsKey = '';			//Needed for setting up Google Analytics 

//Module activations
$occurrenceModIsActive = 1;
$floraModIsActive = 1;
$keyModIsActive = 1;

//Misc Options
$dynChecklistRadius = 10;			//Controls size of concentric rings that are sampled when building Dynamic Checklist
$displayCommonNames = 1;			//0 = false, 1 = true

//Individual page menu and navigation crumbs
	//checklists/
		$checklists_checklistMenu = false;
		$checklists_checklistCrumbs = "<a href='index.php'>Checklists</a> &gt; ";
	
	//collections/
		$collections_indexMenu = false;
		$collections_indexCrumbs = "&nbsp;";
		$collections_harvestparamsMenu = false;
		$collections_harvestparamsCrumbs = "<a href='index.php'>Collections</a>";
		$collections_listMenu = false;
		$collections_listCrumbs = "<a href='index.php'>Collections</a> &gt; <a href='harvestparams.php'>Search Criteria</a>";
		$collections_checklistMenu = false;
		$collections_checklistCrumbs = "<a href='index.php'>Collections</a> &gt; <a href='harvestparams.php'>Search Criteria</a>";
		$collections_download_downloadMenu = false;
		$collections_download_downloadCrumbs = "<a href='../index.php'>Collections</a> &gt; <a href='../harvestparams.php'>Search Criteria</a>";
		$collections_maps_indexMenu = false;
		$collections_maps_indexCrumbs = "<a href='../index.php'>Collections</a> &gt; <a href='../harvestparams.php'>Search Criteria</a>";

	//ident/
		$ident_keyMenu = false;
		$ident_keyCrumbs = "<a href='../ident/index.php'>Identification Keys</a> &gt; ";
		$ident_loadingclMenu = false;
		$ident_tools_chardeficitMenu = false;
		$ident_tools_chardeficitCrumbs = "<a href='javascript: self.close();'> Back to Key</a> ";
		$ident_tools_massupdateMenu = false;
		$ident_tools_massupdateCrumbs = "<a href='javascript: self.close();'> Back to Key</a> ";
		$ident_tools_editorMenu = false;
		$ident_tools_editorCrumbs = "<a href='javascript: self.close();'> Back to Key</a> ";
		
	//taxa/
		$taxa_indexMenu = false;
		$taxa_admin_tpeditorMenu = false;
		
//Base code shared by all pages; leave as is
include_once("symbbase.php");
?>