<?php

$CONFIG_FILE_DIR = "/usr/local/etc/symbiota";
$CONFIG_FILE_MAIN = "symbiota.yml";
$CONFIG_FILE_NAV = "navigation.yml";
$CONFIG_FILE_HEADER = "header.yml";
$CONFIG_FILE_FOOTER = "footer.yml";

$main_config = yaml_parse_file($CONFIG_FILE_DIR . "/" . $CONFIG_FILE_MAIN)["symbini"];
$nav_config = yaml_parse_file($CONFIG_FILE_DIR . "/" . $CONFIG_FILE_NAV)["navigation"];

/****************************
 * Begin app definitions    *
 ***************************/
$DEFAULT_LANG =         $main_config["core"]["default_lang"];   //Default language
$DEFAULT_PROJ_ID =      $main_config["core"]["proj_id"];
$DEFAULTCATID =         $main_config["core"]["cat_id"];
$DEFAULT_TITLE =        $main_config["core"]["page_title"];
$TID_FOCUS =            $main_config["core"]["tid_focus"];
$ADMIN_EMAIL =          $main_config["core"]["admin_email"];
$CHARSET = 'UTF-8';
$PORTAL_GUID =          $main_config["core"]["portal_guid"];    //Typically a UUID
$SECURITY_KEY =         $main_config["core"]["security_key"];   //Typically a UUID used to verify access to certain web service

$CLIENT_ROOT =          $main_config["data"]["url_path_root"];          //URL path to project root folder (relative path w/o domain, e.g. '/seinet')
$SERVER_ROOT =          getenv("SYMBIOTA_ROOT");                        //Full path to Symbiota project root folder
$TEMP_DIR_ROOT =        $SERVER_ROOT . '/temp';                         //Must be writable by Apache; will use system default if not specified
$LOG_PATH =             $SERVER_ROOT . '/content/logs';                 //Must be writable by Apache; will use <SYMBIOTA_ROOT>/temp/logs if not specified

$IMAGE_DOMAIN =         $main_config["data"]["image_domain"];       //Domain path to images, if different from portal
$IMAGE_ROOT_URL =       $main_config["data"]["url_path_images"];    //URL path to images
$IMAGE_ROOT_PATH =      $SERVER_ROOT . "/content/image";            //Writable path to images, especially needed for downloading images

//Pixel width of web images
$IMG_WEB_WIDTH =                1400;
$IMG_TN_WIDTH =                 200;
$IMG_LG_WIDTH =                 3200;
$IMG_FILE_SIZE_LIMIT =          300000;                         //Files above this size limit and still within pixel width limits will still be resaved w/ some compression
$IPLANT_IMAGE_IMPORT_PATH =     $IMAGE_ROOT_PATH . "/iplant";   //Path used to map/import images uploaded to the iPlant image server (e.g. /home/shared/project-name/--INSTITUTION_CODE--/, the --INSTITUTION_CODE-- text will be replaced with collection's institution code)

$USE_IMAGE_MAGICK =             1;                                //1 = ImageMagick resize images, given that it's installed (faster, less memory intensive)
$TESSERACT_PATH =               '/usr/bin/tesseract';           //Needed for OCR function in the occurrence editor page
$NLP_LBCC_ACTIVATED =           $main_config["nlp"]["lbcc_activated"];
$NLP_SALIX_ACTIVATED =          $main_config["nlp"]["salix_activated"];

//Module activations
$OCCURRENCE_MOD_IS_ACTIVE =     $main_config["modules"]["occurrence_active"];
$FLORA_MOD_IS_ACTIVE =          $main_config["modules"]["flora_active"];
$KEY_MOD_IS_ACTIVE =            $main_config["modules"]["key_active"];
$REQUESTED_TRACKING_IS_ACTIVE = $main_config["modules"]["tracking_active"]; // Allow users to request actions such as requests for images to be made for specimens

//Configurations for GeoServer integration
$GEOSERVER_URL =                $main_config["geoserver"]["url"];           // URL for Geoserver instance serving map data for this portal
$GEOSERVER_RECORD_LAYER =       $main_config["geoserver"]["record_layer"];  // Name of Geoserver layer containing occurrence point data for this portal

//Configurations for Apache SOLR integration
$SOLR_URL =                     $main_config["solr"]["url"];                    // URL for SOLR instance indexing data for this portal
$SOLR_FULL_IMPORT_INTERVAL =    $main_config["solr"]["full_import_interval"];   // Number of hours between full imports of SOLR index.

//Configurations for publishing to GBIF
$GBIF_USERNAME =    $main_config["gbif"]["username"];   //GBIF username which portal will use to publish
$GBIF_PASSWORD =    $main_config["gbif"]["password"];   //GBIF password which portal will use to publish
$GBIF_ORG_KEY =     $main_config["gbif"]["ocr_key"];    //GBIF organization key for organization which is hosting this portal

$FP_ENABLED =       $main_config["modules"]["filtered_push_active"];    //Enable Filtered-Push modules

//Misc variables
$GOOGLE_ANALYTICS_KEY =     $main_config["misc"]["google"]["analytics_key"];    //Needed for setting up Google Analytics
$GOOGLE_MAP_KEY =           $main_config["misc"]["google"]["map_key"];          //Needed for Google Map; get from Google
$GOOGLE_MAP_ZOOM =          $main_config["misc"]["google"]["map_zoom"];         // Set the map zoom level
$MAPPING_BOUNDARIES =       $main_config["misc"]["google"]["boundaries"];       //Project bounding box; default map centering; (e.g. 42.3;-100.5;18.0;-127)

$SPATIAL_INITIAL_CENTER =   $main_config["misc"]["spacial"]["initial_center"];  //Initial map center for Spatial Module. Default: '[-110.90713, 32.21976]'
$SPATIAL_INITIAL_ZOOM =     $main_config["misc"]["spacial"]["initial_zoom"];    //Initial zoom for Spatial Module. Default: 7

$TAXON_PROFILE_MAP_CENTER = $main_config["misc"]["taxon_profile"]["map_center"];    //Center for taxon profile maps
$TAXON_PROFILE_MAP_ZOOM =   $main_config["misc"]["taxon_profile"]["map_zoom"];      //Zoom for taxon profile maps

$ACTIVATE_GEOLOCATION =             true;                                                       //Activates HTML5 geolocation services in Map Search
$GEOREFERENCE_POLITICAL_DIVISIONS = $main_config["misc"]["georeference_political_divisions"];    //Allow Batch Georeference module to georeference records without locality description, but with county

$RECAPTCHA_PUBLIC_KEY =             $main_config["misc"]["recaptcha"]["public_key"];
$RECAPTCHA_PRIVATE_KEY =            $main_config["misc"]["recaptcha"]["private_key"];           //Now called secret key

$EOL_KEY = $main_config["misc"]["eol_key"];                         //Not required, but good to add a key if you plan to do a lot of EOL mapping
$TAXONOMIC_AUTHORITIES = array(                                     //List of taxonomic authority APIs to use in data cleaning and thesaurus building tools, concatenated with commas and order by preference; E.g.: array('COL'=>'','WoRMS'=>'','TROPICOS'=>'','EOL'=>'')
    'COL'=> $main_config["misc"]["taxonomic_authorities"]["COL"],
    'WoRMS'=> $main_config["misc"]["taxonomic_authorities"]["WoRMS"]
);

$QUICK_HOST_ENTRY_IS_ACTIVE =       $main_config["misc"]["quick_host_entry_active"];    // Allows quick entry for host taxa in occurrence editor
$PORTAL_TAXA_DESC =                 $main_config["misc"]["portal_taxa_desc"];           //Preferred taxa descriptions for the portal.
$GLOSSARY_EXPORT_BANNER =           $main_config["misc"]["glossary_export_banner"];     //Banner image for glossary exports. Place in images/layout folder.
$DYN_CHECKLIST_RADIUS =             $main_config["misc"]["dyn_checklist_radius"];        //Controls size of concentric rings that are sampled when building Dynamic Checklist
$DISPLAY_COMMON_NAMES =             $main_config["misc"]["display_common_names"];        //Display common names in species profile page and checklists displays

$ACTIVATE_EXSICCATI =               $main_config["misc"]["exsiccati_active"];                //Activates exsiccati fields within data entry pages; adding link to exsiccati search tools to portal menu is recommended
$ACTIVATE_CHECKLIST_FG_EXPORT =     $main_config["misc"]["checklist_fg_export_active"];     //Activates checklist fieldguide export tool

$ACTIVATE_FIELDGUIDE =              $main_config["misc"]["fieldguide"]["active"];           //Activates FieldGuide Batch Processing module
$FIELDGUIDE_API_KEY =               $main_config["misc"]["fieldguide"]["api_key"];          //API Key for FieldGuide Batch Processing module

$GENBANK_SUB_TOOL_PATH =            $main_config["misc"]["genbank_tool_path"];          //Path to GenBank Submission tool installation
$ACTIVATE_GEOLOCATE_TOOLKIT =       $main_config["misc"]["geolocate_toolkit_active"];    //Activates GeoLocate Toolkit located within the Processing Toolkit menu items

$RIGHTS_TERMS = array(
    'CC0 1.0 (Public-domain)' => 'http://creativecommons.org/publicdomain/zero/1.0/',
    'CC BY (Attribution)' => 'http://creativecommons.org/licenses/by/4.0/',
    'CC BY-NC (Attribution-Non-Commercial)' => 'http://creativecommons.org/licenses/by-nc/4.0/',
    'CC BY-NC-ND 4.0 (Attribution-NonCommercial-NoDerivatives 4.0 International)' => 'https://creativecommons.org/licenses/by-nc-nd/4.0/'
);
$CSS_VERSION_LOCAL = '20170414';        //Changing this variable will force a refresh of main.css styles within users browser cache for all pages

//set terms for reproductiveCondition drop-down menu
//$REPRODUCTIVE_CONDITION_TERMS = array("term1", "term2", "term3", "term4", "term5", "etc..");

//Individual page menu and navigation crumbs
//Menu variables turn on and off the display of left menu
//Crumb variables allow the customization of the bread crumbs. A crumb variable with an empty value will cause crumbs to disappear
//Variable name should include path to file separated by underscores and then the file name ending with "Menu" or "Crumbs"

//checklists/
    $checklists_checklistMenu =             $nav_config["show_checklists_menu"];
    //$checklists_checklistCrumbs = "<a href='../index.php'>Home</a> &gt;&gt; <a href='index.php'>Checklists</a> &gt;&gt; ";

//glossary/
    $glossary_indexBanner =                 $nav_config["show_glossary_index_banner"];

//collections/
    $collections_indexMenu =                $nav_config["collections"]["show_index_menu"];
    $collections_harvestparamsMenu =        $nav_config["collections"]["show_harvest_params_menu"];
    //$collections_harvestparamsCrumbs = "<a href='index.php'>Collections</a> &gt;&gt; ";
    $collections_listMenu =                 $nav_config["collections"]["show_list_menu"];
    $collections_checklistMenu =            $nav_config["collections"]["show_checklist_menu"];
    $collections_download_downloadMenu =    $nav_config["collections"]["show_download_menu"];
    $collections_maps_indexMenu =           $nav_config["collections"]["show_maps_menu"];

//loans/
    $collections_loans_indexMenu =          $nav_config["collections"]["show_loans_index_menu"];

//ident/
    $ident_keyMenu =                        $nav_config["ident"]["show_key_menu"];
    $ident_tools_chardeficitMenu =          $nav_config["ident"]["show_chardeficit_menu"];
    $ident_tools_massupdateMenu =           $nav_config["ident"]["show_mass_update_menu"];
    $ident_tools_editorMenu =               $nav_config["ident"]["show_editor_menu"];

//taxa/
    $taxa_indexMenu =                       $nav_config["taxa"]["show_index_menu"];
    $taxa_admin_tpeditorMenu =              $nav_config["taxa"]["show_admin_tpeditor_menu"];

//agents/
    $agents_indexMenu =                     $nav_config["agents"]["show_index_menu"];
    $agent_indexCrumbs =                    $nav_config["agents"]["index_crumbs"];

    array_push($agent_indexCrumbs,"<a href='$CLIENT_ROOT/index.php'>Home</a>");
    array_push($agent_indexCrumbs,"<a href='$CLIENT_ROOT/agents/index.php'>Agents</a>");

//Base code shared by all pages; leave as is
include_once("symbbase.php");

?>