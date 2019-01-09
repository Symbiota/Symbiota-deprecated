<?php
$$DEFAULT_LANG = '$default_lang';			//Default language
$$DEFAULT_PROJ_ID = $proj_id;
$$DEFAULTCATID = $cat_id;
$$DEFAULT_TITLE = '$page_title';
$$TID_FOCUS = '$tid_focus';
$$ADMIN_EMAIL = '$admin_email';
$$CHARSET = 'UTF-8';					//ISO-8859-1 or UTF-8
$$PORTAL_GUID = '$portal_guid';      //Typically a UUID
$$SECURITY_KEY = '$security_key';    //Typically a UUID used to verify access to certain web service

$$CLIENT_ROOT = '$url_path_root';            //URL path to project root folder (relative path w/o domain, e.g. '/seinet')
$$SERVER_ROOT = '$path_symbiota_root';	     //Full path to Symbiota project root folder
$$TEMP_DIR_ROOT = $$SERVER_ROOT.'/temp';     //Must be writable by Apache; will use system default if not specified
$$LOG_PATH = $$SERVER_ROOT.'/content/logs';  //Must be writable by Apache; will use <SYMBIOTA_ROOT>/temp/logs if not specified

//the root for the image directory
$$IMAGE_DOMAIN = '$image_domain';                       //Domain path to images, if different from portal
$$IMAGE_ROOT_URL = '$url_path_images';                  //URL path to images
$$IMAGE_ROOT_PATH = $$SERVER_ROOT . "/content/image";	//Writable path to images, especially needed for downloading images

//Pixel width of web images
$$IMG_WEB_WIDTH = 1400;
$$IMG_TN_WIDTH = 200;
$$IMG_LG_WIDTH = 3200;
$$IMG_FILE_SIZE_LIMIT = 300000;		                                    //Files above this size limit and still within pixel width limits will still be resaved w/ some compression
$$IPLANT_IMAGE_IMPORT_PATH = $$SERVER_ROOT . '/content/image/iplant';   //Path used to map/import images uploaded to the iPlant image server (e.g. /home/shared/project-name/--INSTITUTION_CODE--/, the --INSTITUTION_CODE-- text will be replaced with collection's institution code)

$$USE_IMAGE_MAGICK = 1;		                //1 = ImageMagick resize images, given that it's installed (faster, less memory intensive)
$$TESSERACT_PATH = '/usr/bin/tesseract';     //Needed for OCR function in the occurrence editor page
$$NLP_LBCC_ACTIVATED = $lbcc_activated;
$$NLP_SALIX_ACTIVATED = $salix_activated;

//Module activations
$$OCCURRENCE_MOD_IS_ACTIVE = $mod_occurrence;
$$FLORA_MOD_IS_ACTIVE = $mod_flora;
$$KEY_MOD_IS_ACTIVE = $mod_key;

$$REQUESTED_TRACKING_IS_ACTIVE = $mod_tracking;   // Allow users to request actions such as requests for images to be made for specimens

//Configurations for GeoServer integration
$$GEOSERVER_URL = '$geoserver_url';                     // URL for Geoserver instance serving map data for this portal
$$GEOSERVER_RECORD_LAYER = '$geoserver_record_layer';   // Name of Geoserver layer containing occurrence point data for this portal

//Configurations for Apache SOLR integration
$$SOLR_URL = '$solr_url';                               // URL for SOLR instance indexing data for this portal
$$SOLR_FULL_IMPORT_INTERVAL = '$solr_import_interval';  // Number of hours between full imports of SOLR index.

//Configurations for publishing to GBIF
$$GBIF_USERNAME = '$gbif_username';         //GBIF username which portal will use to publish
$$GBIF_PASSWORD = '$gbif_password';         //GBIF password which portal will use to publish
$$GBIF_ORG_KEY = '$gbif_ocr_key';           //GBIF organization key for organization which is hosting this portal

$$FP_ENABLED = $mod_fp;  //Enable Filtered-Push modules

//Misc variables
$$GOOGLE_MAP_KEY = '$google_map_key';                       //Needed for Google Map; get from Google
$$GOOGLE_MAP_ZOOM = $google_map_zoom;                       //Set the map zoom level
$$MAPPING_BOUNDARIES = '$google_map_boundaries';            //Project bounding box; default map centering; (e.g. 42.3;-100.5;18.0;-127)
$$SPATIAL_INITIAL_CENTER = '$spacial_center';               //Initial map center for Spatial Module. Default: '[-110.90713, 32.21976]'
$$SPATIAL_INITIAL_ZOOM = '$spacial_zoom';                   //Initial zoom for Spatial Module. Default: 7
$$TAXON_PROFILE_MAP_CENTER = '$taxon_center';               //Center for taxon profile maps
$$TAXON_PROFILE_MAP_ZOOM = '$taxon_zoom';                   //Zoom for taxon profile maps
$$ACTIVATE_GEOLOCATION = true;			                    //Activates HTML5 geolocation services in Map Search
$$GEOREFERENCE_POLITICAL_DIVISIONS = $georef_divisions;     //Allow Batch Georeference module to georeference records without locality description, but with county
$$GOOGLE_ANALYTICS_KEY = '$google_anaylytics_key';          //Needed for setting up Google Analytics
$$RECAPTCHA_PUBLIC_KEY = '$recaptcha_public_key';           //Now called site key
$$RECAPTCHA_PRIVATE_KEY = '$recaptcha_private_key';         //Now called secret key
$$EOL_KEY = '$eol_key'; 			                        //Not required, but good to add a key if you plan to do a lot of EOL mapping
$$TAXONOMIC_AUTHORITIES = array(                            //List of taxonomic authority APIs to use in data cleaning and thesaurus building tools, concatenated with commas and order by preference; E.g.: array('COL'=>'','WoRMS'=>'','TROPICOS'=>'','EOL'=>'')
    'COL'=>'$taxon_auth_cols',
    'WoRMS'=>'$taxon_auth_worms'
);
$$QUICK_HOST_ENTRY_IS_ACTIVE = $quick_host_entry_active;    //Allows quick entry for host taxa in occurrence editor
$$PORTAL_TAXA_DESC = '$portal_taxa_desc';                   //Preferred taxa descriptions for the portal.
$$GLOSSARY_EXPORT_BANNER = '$glossary_export_banner';       //Banner image for glossary exports. Place in images/layout folder.
$$DYN_CHECKLIST_RADIUS = $dyn_checklist_radius;             //Controls size of concentric rings that are sampled when building Dynamic Checklist
$$DISPLAY_COMMON_NAMES = $display_common_names;             //Display common names in species profile page and checklists displays
$$ACTIVATE_EXSICCATI = $exsiccati;                          //Activates exsiccati fields within data entry pages; adding link to exsiccati search tools to portal menu is recommended
$$ACTIVATE_CHECKLIST_FG_EXPORT = $checklist_fg_export;      //Activates checklist fieldguide export tool
$$ACTIVATE_FIELDGUIDE = $fieldguide_active;                 //Activates FieldGuide Batch Processing module
$$FIELDGUIDE_API_KEY = '$fieldguide_api_key';               //API Key for FieldGuide Batch Processing module
$$GENBANK_SUB_TOOL_PATH = '$genbank_tool_path';             //Path to GenBank Submission tool installation
$$ACTIVATE_GEOLOCATE_TOOLKIT = $geolocate_toolkit;          //Activates GeoLocate Toolkit located within the Processing Toolkit menu items

$$RIGHTS_TERMS = array(
    'CC0 1.0 (Public-domain)' => 'http://creativecommons.org/publicdomain/zero/1.0/',
    'CC BY (Attribution)' => 'http://creativecommons.org/licenses/by/4.0/',
    'CC BY-NC (Attribution-Non-Commercial)' => 'http://creativecommons.org/licenses/by-nc/4.0/',
    'CC BY-NC-ND 4.0 (Attribution-NonCommercial-NoDerivatives 4.0 International)' => 'https://creativecommons.org/licenses/by-nc-nd/4.0/'
);
$$CSS_VERSION_LOCAL = '$css_version_date';		//Changing this variable will force a refresh of main.css styles within users browser cache for all pages

//set terms for reproductiveCondition drop-down menu
//$$REPRODUCTIVE_CONDITION_TERMS = array("term1", "term2", "term3", "term4", "term5", "etc..");

//Individual page menu and navigation crumbs
//Menu variables turn on and off the display of left menu
//Crumb variables allow the customization of the bread crumbs. A crumb variable with an empty value will cause crumbs to disappear
//Variable name should include path to file separated by underscores and then the file name ending with "Menu" or "Crumbs"
//checklists/
	$$checklists_checklistMenu = $checklists_menu;
	//$$checklists_checklistCrumbs = "<a href='../index.php'>Home</a> &gt;&gt; <a href='index.php'>Checklists</a> &gt;&gt; ";
//collections/
	$$collections_indexMenu = $collections_index_menu;
	$$collections_harvestparamsMenu = $collections_harvest_params_menu;
	//$$collections_harvestparamsCrumbs = "<a href='index.php'>Collections</a> &gt;&gt; ";
	$$collections_listMenu = $collections_list_menu;
	$$collections_checklistMenu = $collections_checklist_menu;
	$$collections_download_downloadMenu = $collections_download_menu;
	$$collections_maps_indexMenu = $collections_maps_menu;

//ident/
	$$ident_keyMenu = $ident_key_menu;
	$$ident_tools_chardeficitMenu = $ident_chardeficit_menu;
	$$ident_tools_massupdateMenu = $ident_mass_update_menu;
	$$ident_tools_editorMenu = $ident_editor_menu;

//taxa/
	$$taxa_indexMenu = $taxa_index_menu;
	$$taxa_admin_tpeditorMenu = $taxa_admin_tpeditor_menu;

//glossary/
	$$glossary_indexBanner = $glossary_index_banner;

//loans/
	$$collections_loans_indexMenu = $collections_loans_index_menu;

//agents/
    $$agents_indexMenu = $agents_index_menu;
    $$agent_indexCrumbs = $agent_index_crumbs;
    array_push($$agent_indexCrumbs,"<a href='$$CLIENT_ROOT/index.php'>Home</a>");
    array_push($$agent_indexCrumbs,"<a href='$$CLIENT_ROOT/agents/index.php'>Agents</a>");

//Base code shared by all pages; leave as is
include_once("symbbase.php");
/* --DO NOT ADD ANY EXTRA SPACES BELOW THIS LINE-- */?>