<?php
$defaultLang = "English";			//Default language
$defaultProjId = 1;
$defaultTitle = "SEINet";

$clientRoot = "";					//URL path to project root folder 
$serverRoot = "";

//the root for the image directory
$imageDomain = "";					//Domain path to images, if different from Virtual Flora path (e.g. http://swbiodiversity.org)
$imageRootUrl = "";					//URL path to images
$imageRootPath = "";				//Writable path to images, especially needed for downloading images

//the root for the temp directory
$tempDirRoot = "";
$tempDirUrl = "";

$googleMapKey = "";					//Needed for Google Map; get from Google 
$mappingBoundaries = "";			//Project bounding box; used for setting default map focus 
$googleAnalyticsKey = "";			//Needed for setting up Google Analytics 

//Module activations
$occurrenceModIsActive = 1;
$floraModIsActive = 1;
$keyModIsActive = 1;

//MIsc Options
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