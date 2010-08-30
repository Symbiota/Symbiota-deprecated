<?php
$defaultLang = 'English';		//Default language
$defaultProjId = 1;				//Default project: Arizona Flora
$defaultTitle = 'SEINet';
$adminEmail = 'seinetadmin@asu.edu';
$charset = 'ISO-8859-1';

$clientRoot = '/seinet';
if(strpos($_SERVER["PHP_SELF"],"seinet2") === 0){
	$clientRoot = '/seinet2';
}
$serverRoot = 'C:/htdocs/symbiota/trunk';

//the root for the image directory
$imageDomain = "http://swbiodiversity.org";
$imageRootUrl = "/seinet/temp/images/";
$imageRootPath = "C:/htdocs/symbiota/trunk/temp/images";

//the root for the temp directory
$tempDirRoot = "C:/htdocs/symbiota/trunk/temp";
$tempDirUrl = "/seinet/temp";

$googleMapKey = "ABQIAAAAUEnNuyeWuQZvwMvdI1l8LhSRSg8ycg-h0xIF0FwpYbqxCu8yTRSh-hXLll-FBncXI5Bv5SRGHQ2zOQ";
$mappingBoundaries = "42.3;-100.5;18.0;-127";
$googleAnalyticsKey = "";//"UA-561868-8";

//Module activations
$occurrenceModIsActive = 1;
$floraModIsActive = 1;
$keyModIsActive = 1;

//Options
$dynKeyRadius = 10;				//Controls size of concentric rings that are sampled when building dynamic map key
$displayCommonNames = 1;		//Whether to offer the option to display common names or not: 0 = false, 1 = true

//Individual Page Parameter Settings
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
		
//Header management
$hideHeader = 0;
if(array_key_exists("asinsert",$_REQUEST)){
	if($_REQUEST["asinsert"] == "1"){
		$hideHeader = 1;
		//Set cookie if does not exists
		setcookie("SymbiotaAsInsert", "1", 0, $clientRoot);
	}
	else{
		setcookie("SymbiotaAsInsert", "", time() - 3600, $clientRoot);
	}
}
elseif(array_key_exists("SymbiotaAsInsert",$_COOKIE)){
	$hideHeader = 1;
}	

//Base code shared by all pages; leave as is
include_once("symbbase.php");
?>