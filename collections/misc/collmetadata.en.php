<?php 
/*
------------------
Language: English (Ingles)
------------------
*/

include_once('sharedterms.es.php');

$LANG['CREATE'] = 'Create new collection or observation';
$LANG['COD'] = 'Institution code:';
$LANG['THE'] = 'The name (or acronym) in use by the institution having custody of the occurrence records. This field is required.For more details, see';
$LANG['DARWIN'] = 'Darwin Core definition';
$LANG['COD_1'] = 'Collection code:';
$LANG['MORE'] = 'More information about Collection Code';
$LANG['THE_NAME'] = 'The name, acronym, or code identifying the collection or data set from which the record was derived. This field is optional.
										For more details, see';
$LANG['NAME_COL'] = 'Collection name:';
$LANG['DES'] = 'Description<';
$LANG['MAX'] = '(maximum 2000 characters):';
$LANG['PAG'] = 'Web page:';
$LANG['CONTA'] = 'Contact:';
$LANG['MAIL'] = 'E-mail:';
$LANG['LATITUDE'] = 'Latitude:';
$LANG['LONG'] = 'Length:';
$LANG['CATE'] = 'Category:';
$LANG['NO_CATE'] = 'No Category';
$LANG['PERM_EDI'] = 'Allow public editions:';
$LANG['LA'] = ' Verification of public editions will allow any user who has logged in to modify the occurrence records
and solve the errors found within the collection. However, if the user does not have explicit
authorization for the given collection, the editions will not apply until they are
reviewed and approved by the collection administrator.';
$LANG['LICEN'] = 'Licencia:';
$LANG['AL_LEGAL'] = 'A legal document giving official permission to do something with the resource.
										This field can be limited to a set of values by modifying the portal is central configuration file.
										For more details, see';
$LANG['DAR_DEFI'] = 'Darwin Core definition';
$LANG['TIT'] = 'Holder of rights:';
$LANG['THE_ORG'] = 'The organization or person managing or owning the rights of the resource.
										For more details, see ';
$LANG['DER'] = 'Rights of access:';
$LANG['INFOR'] = 'Informations or a URL link to page with details explaining how one can use the data.
										See';
$LANG['FUENT'] = 'Fuente GUID:';
$LANG['NO_DEF'] = 'Not defined';
$LANG['OCURRENCE'] = 'Occurrence Id is generally used for Snapshot datasets when a Global Unique Identifier (GUID) field
										is supplied by the source database (e.g. Specify database) and the GUID is mapped to the';
$LANG['OCURRENCE_ID'] = 'occurrenceId';
$LANG['FILED'] = 'field.The use of the Occurrence Id as the GUID is not recommended for live datasets.
Catalog Number can be used when the value within the catalog number field is globally unique.
The Symbiota Generated GUID (UUID) option will trigger the Symbiota data portal to automatically
generate UUID GUIDs for each record. This option is recommended for many for Live Datasets
but not allowed for Snapshot collections that are managed in local management system.';
$LANG['PUBLISH'] = ' Publish to Aggregators:';
$LANG['URL'] = 'Source registration URL:';
$LANG['ADDING'] = 'Adding a URL template here will dynamically generate and add the occurrence details page a link to the source record. For example, ;http://sweetgum.nybg.org/vh/specimen.php?irn=--DBPK--;
	will generate a url to the NYBG collection with ;--DBPK--; being replaced with the
	NYBG is Primary Key (dbpk data field within the ommoccurrence table). Template pattern --CATALOGNUMBER-- can also be used in place of --DBPK--';
$LANG['AGRE'] = 'Add icon:';
$LANG['ENTER'] = 'Enter URL';
$LANG['UPLOAD'] = 'Upload Local Image';
$LANG['UP_ICON'] = 'Upload an icon image file or enter the URL of an image icon that represents the collection. If entering the URL of an image already located on a server, click on;Enter URL;. The URL path can be absolute or relative. The use of icons are optional.';
$LANG['TIPO'] = 'Collection type:';
$LANG['SPEIMEN'] = 'Preserved specimens';
$LANG['OBSER'] = 'Observations in general';
$LANG['PRESER'] = 'Preserve Specimens means that physical samples exist and can be inspected by researchers.
Use Observations when the record is not based on a physical specimen. General Observations are used for setting up group projects where registered users can independently manage their own dataset directly within the single collection. General Observation collections are typically used by field researchers to manage their collection data and print labels prior to depositing the physical material within a collection. Even though personal collections are represented by a physical sample, they are classified as;observations; until the physical material is deposited within a publicly available collection with active curation.';
$LANG['ADMIN'] = 'Administration';
$LANG['INST'] = 'Snapshot';
$LANG['ADD'] = 'Add';
$LANG['USE'] = 'Use Snapshot when there is a separate in-house database maintained in the collection and the dataset within the Symbiota portal is only a periodically updated snapshot of the central database.
	A Live dataset is when the data is managed directly within the portal and the central database is the portal data.';
$LANG['ORDER'] = 'Sort sequence:';
$LANG['LEAVE'] = 'Leave this field empty if you want the collections to sort alphabetically (default)';
$LANG['ID_GLOBAL'] = 'Unique global ID:';
$LANG['GLOBAL_UNIQUE'] = 'Global Unique Identifier for this collection. If your collection already has a GUID (e.g. previously assigned by a collection management application such as Specify), that identifier should be represented here. If you need to change this value, contact your portal manager.';
$LANG['SECURITY'] = 'Security Key:';
$LANG['ID_GLO'] = 'Unique global ID:';
$LANG['IDENTY'] = 'Global unique identifier for this collection.
If your collection already has a GUID (for example, previously assigned by a
collection management application, such as Specify), that identifier must be entered here.
If you leave it blank, the portal automatically
generate a UUID for this collection (recommended if you do not know which GUID already exists).';
$LANG['INSTI'] = 'Associated institution';
$LANG['NO_EXIS'] = 'There is no associated institution';
$LANG['SEL'] = 'Select Institution';
$LANG['ADD_IN'] = 'Add institution';
$LANG['ESP_VIVA'] = 'Living species';
$LANG['ID_OC'] = 'Ocurrence ID';
$LANG['CAT_NUMBER'] = 'Catalog Number';
$LANG['SYM'] = 'Symbiota Generated GUID (UUID)';
$LANG['OB'] = 'Observations';

$LANG['COLLECTION_PROFILES'] = 'Collection Profiles';
$LANG['ALERT_INSTITUTION_CODE_MUST'] = '"Institution Code must have a value"';
$LANG['ALERT_COLLECTION_NAME_MUST_HAVE'] = '"Collection Name must have a value"';
$LANG['ALERT_THE_SYMBIOTA_GENERATED_GUID'] = '"The Symbiota Generated GUID option cannot be selected for a collection that is managed locally outside of the data portal (e.g. Snapshot management type). In this case, the GUID must be generated within the source collection database and delivered to the data portal as part of the upload process."';
$LANG['ALERT_LATITUDE_AND_LONGITUDE'] = '"Latitude and longitude values must be in the decimal format (numeric only)"';
$LANG['ALERT_RIGHTS_FIELD'] = '"Rights field (e.g. Creative Commons license) must have a selection"';
$LANG['ALERT_SORT_SEQUENCE_MUST'] = '"Sort sequence must be numeric only"';
$LANG['ALERT_THE_SYMBIOTA_GENERATED'] = '"The Symbiota Generated GUID option cannot be selected for a collection that is managed locally outside of the data portal (e.g. Snapshot management type). In this case, the GUID must be generated within the source collection database and delivered to the data portal as part of the upload process."';
$LANG['ALERT_AND_AGGREGATE_DATASET'] = '"An Aggregate dataset (e.g. occurrences coming from multiple collections) can only have occurrenceID selected for the GUID source"';
$LANG['ALERT_YOU_MUST_SELECT_A_GUID'] = '"You must select a GUID source in order to publish to data aggregators."';
$LANG['ALERT_SELECT_AN_INSTITUTION_TO_BE'] = '"Select an institution to be linked"';
$LANG['ALERT_THE_FILE_YOU_HAVE_UPLOADED'] = '"The file you have uploaded is not a supported image file. Please upload a jpg, png, or gif file."';
$LANG['ALERT_THE_IMAGE_FILE_MUST'] = '"The image file must be less than 350 pixels in both width and height."';
$LANG['ALERT_THE_URL_YOU_HAVE_ENTERED'] = '"The url you have entered is not for a supported image file. Please enter a url for a jpg, png, or gif file."';
$LANG['HOME'] = 'Home';
$LANG['COLLECTION_MANAGER'] = 'Collection Management';
$LANG['METADATA_EDITOR'] = 'Metadata Editor';
$LANG['CREATE_NEW_COLLECTION_PROFILE'] = 'Create New Collection Profile';
$LANG['MORE_INFORMATION_ABOUT_INSTITUTION_CODE'] = 'More information about Institution Code';
$LANG['REQUIRED_FIELD'] = 'Required field';
$LANG['MORE_INFORMATION_ABOUT_PUBLIC_EDITS'] = 'More information about Public Edits';
$LANG['ORPHANED_TERM'] = 'orphaned term';
$LANG['MORE_INFORMATION_ABOUT_RIGHTS'] = 'More information about Rights';
$LANG['MORE_INFORMATION_ABOUT_RIGHTS_HOLDER'] = 'More information about Rights Holder';
$LANG['MORE_INFORMATION_ABOUT_ACCESS_RIGHTS'] = 'More information about Access Rights';
$LANG['SOURCE_OF_GLOBAL_UNIQUE_IDENTIFIER'] = 'Source of Global Unique Identifier';
$LANG['MORE_INFORMATION_ABOUT_GLOBAL_UNIQUE_IDENTIFIER'] = 'More information about Global Unique Identifier';
$LANG['MORE_INFORMATION_ABOUT_PUBLISHING_TO_AGGREGATORS'] = 'More information about Publishing to Aggregators';
$LANG['OCURRENCE_ID_O'] = 'Occurrence Id';

$LANG['DYNAMIC_LINK_TO_SOURCE_DATABASE_INDIVIDUAL'] = 'Dynamic link to source database individual record page';
$LANG['MORE_INFORMATION_ABOUT_SOURCE_RECORDS_URL'] = 'More information about Source Records URL';
$LANG['ADDING_A_URL_TEMPLATE_HERE_WILL'] = 'Adding a URL template here will dynamically generate and add the occurrence details page a link to the source record. For example, &quot;http://sweetgum.nybg.org/vh/specimen.php?irn=--DBPK--&quot; will generate a url to the NYBG collection with &quot;--DBPK--&quot; being replaced with the NYBGs Primary Key (dbpk data field within the ommoccurrence table). Template pattern --CATALOGNUMBER-- can also be used in place of --DBPK--';
$LANG['ENTER_URL'] = 'Enter URL';
$LANG['WHAT_IS_A_ICON'] = 'What is an Icon?';
$LANG['PRESERVED_SPECIMENS'] = 'Preserved Specimens';
$LANG['OBSERVATIONS'] = 'Observations';
$LANG['GENERAL_OBSERVATIONS'] = 'General Observations';
$LANG['MORE_INFORMATION_ABOUT_COLLECTION_TYPE'] = 'More information about Collection Type';
$LANG['MORE_INFORMATION_ABOUT_MANAGEMENT_TYPE'] = 'More information about Management Type';
$LANG['MORE_INFORMATION_ABOUT_SORTING'] = 'More information about Sorting';
$LANG['MORE_INFORMATION'] = 'More information';
$LANG['CREATE_NEW_COLLECTION'] = 'Create New Collection';
$LANG['EDIT_INSTITUTION_ADDRESS'] = 'Edit institution address';
$LANG['UNLINK_INSTITUTION_ADDRESS'] = 'Unlink institution address';
$LANG['SELECT_INSTITUTION_ADDRESS'] = 'Select Institution Address';
$LANG['LINK_ADDRESSS'] = 'Link Address';
$LANG['ADD_A_NEW_ADDRESS_NOT_ON_THE_LIST'] = 'Add a new address not on the list';
$LANG['LIVE_DATA'] = 'Live Data';
$LANG['AGGREGATE'] = 'Aggregate';
$LANG['SAVE_EDITS'] = 'Save Edits';

?>