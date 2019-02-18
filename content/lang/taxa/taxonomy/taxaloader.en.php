<?php
/*
------------------
Language: English
------------------
*/
$LANG['TAXA_NAME'] = 'Taxonomic Name Batch Loader';
$LANG['TAXA_ADMIN'] = 'This page allows a Taxonomic Administrator to batch upload taxonomic data files. See';
$LANG['SYM_DOC'] = 'Symbiota Documentation';
$LANG['PAGES_DETAILS'] = 'pages for more details on the Taxonomic Thesaurus layout.';

$LANG['TAXA_UP'] = 'Taxa Upload Form';
$LANG['FLAT_STRUCT'] = 'Flat structured, CSV (comma delimited) text files can be uploaded here. Scientific name is the only required field below genus rank. However, family, author, and rankid (as defined in taxonunits table) are always advised. For upper level taxa, parents and rankids need to be included in order to build the taxonomic hierarchy. Large data files can be compressed as a ZIP file before import. If the file upload step fails without displaying an error message, it is possible that the file size exceeds the file upload limits set within your PHP installation (see your php configuration file).';

$LANG['UPLOAD_FILE'] = 'Upload File:';
$LANG['TOGGLE_MAN'] = 'Toggle Manual Upload Option';

$LANG['UPLOAD_ITIS'] = 'ITIS Upload File';
$LANG['ITIS_DATA'] = 'ITIS data extract from the';
$LANG['ITIS_DOWNLOAD'] = 'ITIS Download Page';
$LANG['CAN_UPLOADED'] = 'can be uploaded using this function. Note that the file needs to be in their single file pipe-delimited format (example:';
$LANG['FILE_BIN'] = 'CyprinidaeItisExample.bin';
$LANG['LEGEND'] = 'File might have .csv extension, even though it is NOT comma delimited.
This upload option is not guaranteed to work if the ITIS download format change often.
Large data files can be compressed as a ZIP file before import.
If the file upload step fails without displaying an error message, it is possible that the
file size exceeds the file upload limits set within your PHP installation (see your php configuration file).
If synonyms and vernaculars are included, these data will also be incorporated into the upload process.';
$LANG['OPTION_MAN'] = '* This option is for manual upload of a data file. Enter full path to data file located on working server.';

$LANG['FILE_NOT'] = 'A file was not chosen';
$LANG['TARGET_THES'] = 'Target Thesaurus:';

$LANG['CLEAN_ANA'] = 'Clean and Analyze';
$LANG['LEGEND2'] = 'If taxa information was loaded into the UploadTaxa table using other means, one can use this form to clean and analyze taxa names in preparation to loading into the taxonomic tables (taxa, taxstatus).';

/* Agregado por jt */
$LANG['TAXA_LOADER'] = 'Taxa Loader';
$LANG['HOME'] = 'Home';
$LANG['TAXONOMIC_TREE_VIEWER'] = 'Taxonomic Tree Viewer';
$LANG['TAXA_BATCH_LOADER'] = 'Taxa Batch Loader';
$LANG['FIELD_UNMAPPED'] = 'Field Unmapped';
$LANG['LEAVE_FIELD_UNMAPPED'] = 'Leave Field Unmapped';
$LANG['TRANSFER_TAXA_TO_CENTRAL_TABLE'] = 'Transfer Taxa To Central Table';
$LANG['REVIEW_UPLOAD_STATISTICS'] = 'Review upload statistics below before activating. Use the download option to review and/or adjust for reload if necessary.';
$LANG['TAXA_UPLOADED'] = 'Taxa uploaded';
$LANG['TOTAL_TAXA'] = 'Total taxa';
$LANG['INCLUDES_NEW_PARENT_TAXA'] = 'includes new parent taxa';
$LANG['TAXA_ALREADY'] = 'Taxa already in thesaurus';
$LANG['NEW_TAXA'] = 'New taxa';
$LANG['ACCEPTED_TAXA'] = 'Accepted taxa';
$LANG['NON_ACCEPTED'] = 'Non-accepted taxa';
$LANG['PROBLEMATIC_TAXA'] = 'Problematic taxa';
$LANG['THESE_TAXA_ARE_MARKED_AS_FAILED'] = 'These taxa are marked as FAILED within the notes field and will not load until problems have been resolved. You may want to download the data (link below), fix the bad relationships, and then reload.';
$LANG['UPLOAD_STATISTICS_ARE_UNAVAILABLE'] = 'Upload statistics are unavailable';
$LANG['DOWNLOAD_CSV_TAXA_FILE'] = 'Download CSV Taxa File';
$LANG['MAP_INPUT_FILE'] = 'Map Input File';
$LANG['UPLOAD_ITIS_FILE'] = 'Upload ITIS File';
$LANG['ANALYZE_TAXA'] = 'Analyze Taxa';

?>
