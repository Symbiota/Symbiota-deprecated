<?php
/*
------------------
Language: English
------------------
*/
$LANG['A'] = 'Image Processor';
$LANG['B'] = 'These tools are designed to aid collection managers in batch processing specimen images. 
				Contact portal manager for helping in setting up a new workflow. 
				Once a profile is established, the collection manager can use this form to manually trigger image processing.
				For more information, see the Symbiota documentation for ';
$LANG['A12'] = 'for integrating images.';				
$LANG['C'] = 'recommended practices';
$LANG['D'] = 'integrating images.';
$LANG['E'] = 'Image File Upload Mapping';
$LANG['F'] = 'Target Field';
$LANG['G'] = 'Source Field<';
$LANG['H'] = 'Saved Image Processing Profiles';
$LANG['I'] = 'Profile';
$LANG['J'] = 'Image Mapping Type:';
$LANG['K'] = 'Local Image Mapping';
$LANG['L'] = 'Upload Image Mapping File';
$LANG['M'] = 'iDigBio Media Ingestion Report';
$LANG['N'] = 'iPlant Image Harvest';
$LANG['O'] = 'Title:';
$LANG['P'] = 'Pattern match term:';
$LANG['Q'] = 'Regular expression needed to extract the unique identifier from source text. For example, regular expression /^(WIS-L-\d{7})\D*/ will extract catalog number WIS-L-0001234 
			from image file named WIS-L-0001234_a.jpg. For more information on creating regular expressions,';
$LANG['R'] = 'Replacement term:';
$LANG['S'] = 'Optional regular expression for match on Catalog Number to be replaced with replacement term.
			Example 1: expression replace term =';
$LANG['G'] = 'combined with replace string =';
$LANG['I_1'] = 'will convert 0001234 => barcode-0001234. Example 2: expression replace term =';
$LANG['J_1'] = 'combined with empty replace string will convert XYZ-0001234 => 0001234.';
$LANG['T'] = 'Replacement string:';
$LANG['U'] = 'Optional replacement string to apply for Expression replacement term matches on catalogNumber.';
$LANG['V'] = 'Image source path:';
$LANG['W'] = 'iPlant server path to source images. The path should be accessible to the iPlant Data Service API.Scripts will crawl through all child directories within the target. Instances of --INSTITUTION_CODE-- and --COLLECTION_CODE-- will be dynamically replaced with the institution and collection codes stored within collections metadata setup. For instance, /home/shared/sernec/--INSTITUTION_CODE--/ would target /home/shared/sernec/xyc/ for the XYZ collection. Contact portal manager for more details. Leave blank to use default path: ';
$LANG['X'] = 'Server path or URL to source image location. Server paths should be absolute and writable to web server (e.g. apache). If a URL (e.g. http://) is supplied, the web server needs to be configured to publically list all files within the directory, or the html output can simply list all images within anchor tags.In all cases, scripts will attempt to crawl through all child directories.';
$LANG['Y'] = 'Image target path:';
$LANG['Z'] = 'Web server path to where the image derivatives will be depositied. The web server (e.g. apache user) must have read/write access to this directory. If this field is left blank, the portal default image url will be used ($imageRootUrl).';
$LANG['A1'] = 'Central pixel width:';
$LANG['A2'] = 'Width of the standard web image. If the source image is smaller than this width, the file will simply be copied over without resizing.';
$LANG['A3'] = 'Thumbnail pixel width:';
$LANG['A4'] = 'Width of the image thumbnail. Width should be greater than image sizing within the thumbnail display pages.';
$LANG['A5'] = 'Large pixel width:';
$LANG['A6'] = 'Width of the large version of the image. If the source image is smaller than this width, the file will simply be copied over without resizing. Note that resizing large images may be limited by the PHP configuration settings (e.g. memory_limit).If this is a problem, having this value greater than the maximum width of your source images will avoid errors related to resampling large images.';
$LANG['A7'] = 'JPG quality:';
$LANG['A8'] = 'JPG quality refers to amount of compression applied. Value should be numeric and range from 0 (worst quality, smaller file) to 100 (best quality, biggest file).If null, 75 is used as the default.';
$LANG['A9'] = 'Thumbnail:';
$LANG['A10'] = 'Create new thumbnail from source image';
$LANG['B1'] = 'Import thumbnail from source location (source name with _tn.jpg suffix)';
$LANG['B2'] = 'Map to thumbnail at source location (source name with _tn.jpg suffix)';
$LANG['B3'] = 'Large Image:';
$LANG['B4'] = 'Import source image as large version';
$LANG['B5'] = ' Map to source image as large version';
$LANG['B6'] = 'Import large version from source location (source name with _lg.jpg suffix)';
$LANG['B7'] = 'Map to large version at source location (source name with _lg.jpg suffix)';
$LANG['B8'] = 'Exclude large version';
$LANG['B9'] = 'Select image mapping file:';
$LANG['B10'] = 'Delete Project';
$LANG['C1'] = 'Select iDigBio Image Appliance output file';
$LANG['C2'] = 'Last Run Date:';
$LANG['C3'] = 'Processing start date:';
$LANG['C4'] = 'Pattern match term:';
$LANG['C5'] = 'Match term on:';
$LANG['C6'] = 'Catalog Number';
$LANG['C7'] = 'Other Catalog Numbers';
$LANG['C8'] = 'Replacement term:';
$LANG['C9'] = 'Replacement string:';
$LANG['D1'] = 'Source path:';
$LANG['D2'] = 'Target folder:';
$LANG['D3'] = 'URL prefix:';
$LANG['D4'] = 'Web image width:';
$LANG['D5'] = 'Thumbnail width:';
$LANG['D6'] = 'Large image width:';
$LANG['D7'] = 'JPG quality (1-100):';
$LANG['D8'] = 'Web Image:';
$LANG['D9'] = 'Evaluate and import source image';
$LANG['E1'] = 'Import source image as is without resizing';
$LANG['E2'] = 'to source image without importing';
$LANG['E3'] = 'Thumbnail:';
$LANG['E4'] = 'Create new from source image';
$LANG['E5'] = 'Import existing source thumbnail (source name with _tn.jpg suffix)';
$LANG['E6'] = 'Map to existing source thumbnail (source name with _tn.jpg suffix)';
$LANG['E7'] = 'Exclude thumbnail';
$LANG['E8'] = 'Large Image:';
$LANG['E9'] = 'Import source image as large version';
$LANG['F1'] = 'Map to source image as large version';
$LANG['F2'] = 'Import existing large version (source name with _lg.jpg suffix)';
$LANG['F3'] = 'Map to existing large version (source name with _lg.jpg suffix)';
$LANG['F4'] = 'Exclude large version';
$LANG['F5'] = 'Missing record:';
$LANG['F6'] = 'Skip image import and go to next';
$LANG['F7'] = 'Create empty record and link image';
$LANG['F8'] = 'Image already exists:';
$LANG['F9'] = 'Skip import';
$LANG['F10'] = 'Rename image and save both';
$LANG['F11'] = 'Replace existing image';
$LANG['F12'] = 'Look for and process skeletal files (allowed extensions: csv, txt, tab, dat):';
$LANG['F13'] = 'Skip skeletal files';
$LANG['F14'] = 'Process skeletal files';
$LANG['F15'] = 'Log Files';
?>
