<?php
//Enter one to many custom cascading style sheet files
//CSSARR = array('example1.css','example2.css');

//Enter one to many custom java script files
//JSARR = array('example1.js','example2.js');

//Enter one to many custom java script files
//PROCESSINGSTATUS = array('unprocessed','Unprocessed/NLP','Stage 1','Stage 2','Stage 3','Pending Duplicate','Pending Review-NfN','Pending Review','Expert Required','Reviewed','Closed');

//Uncomment to turns catalogNumber duplicate search check on/off (on by default)
//define('CATNUMDUPECHECK',false);

//Uncomment to turns otherCatalogNumbers duplicate search check on/off (off by default)
//define('OTHERCATNUMDUPECHECK',false);

//Uncomment to turn duplicate specimen search function on/off (on by default)
//define('DUPESEARCH',false);

//Uncomment to turn locality event auto-lookup (locality field autocomplete) function on/off (on by default)
//0 = off, permanently deactivated, 1 = activated by default (Default), 2 = deactivated by default
//define('LOCALITYAUTOLOOKUP',1);

//const ACTIVATEASSOCTAXAAID = false;

//Occurrence Editor FieldLabel text: uncomment variables and value to modify field labels 
//define('CATALOGNUMBERLABEL','');
//define('OTHERCATALOGNUMBERSLABEL','');
//define('RECORDEDBYLABEL','');
//define('RECORDNUMBERLABEL','');
//define('EVENTDATELABEL','');
//define('ASSOCIATEDCOLLECTORSLABEL','');
//define('VERBATIMEVENTDATELABEL','');
//define('YYYYMMDDLABEL','');
//define('DAYOFYEARLABEL','');
//define('ENDDATELABEL','');
//define('SCINAMELABEL','');
//define('SCIENTIFICNAMEAUTHORSHIPLABEL','');
//define('FAMILYLABEL','');
//define('IDENTIFICATIONQUALIFIERLABEL','');
//define('IDENTIFIEDBYLABEL','');
//define('DATEIDENTIFIEDLABEL','');
//define('IDENTIFICATIONREFERENCELABEL','');
//define('IDENTIFICATIONREMARKSLABEL','');
//define('TAXONREMARKSLABEL','');
//define('COUNTRYLABEL','');
//define('STATEPROVINCELABEL','');
//define('COUNTYLABEL','');
//define('MUNICIPALITYLABEL','');
//define('LOCALITYLABEL','');
//define('LOCALITYSECURITYLABEL','');
//define('LOCALITYSECURITYREASONLABEL','');
//define('LOCATIONREMARKS','');
//define('DECIMALLATITUDELABEL','');
//define('DECIMALLONGITUDELABEL','');
//define('GEODETICDATUMLABEL','');
//define('COORDINATEUNCERTAINITYINMETERSLABEL','');
//define('ELEVATIONINMETERSLABEL','');
//define('VERBATIMELEVATION','');
//define('DEPTHINMETERSLABEL','');
//define('VERBATIMDEPTH','');
//define('FOOTPRINTWKTLABEL','');
//define('VERBATIMCOORDINATESLABEL','');
//define('GEOREFERENCEBYLABEL','');
//define('GEOREFERENCEPROTOCOLLABEL','');
//define('GEOREFERENCESOURCESLABEL','');
//define('GEOREFERENCEVERIFICATIONSTATUSLABEL','');
//define('GEOREFERENCEREMARKSLABEL','');
//define('HABITATLABEL','');
//define('SUBSTRATELABEL','');
//define('ASSOCIATEDTAXALABEL','');
//define('VERBATIMATTRIBUTESLABEL','');
//define('OCCURRENCEREMARKSLABEL','');
//define('FIELDNOTESLABEL','');
//define('DYNAMICPROPERTIESLABEL','');
//define('LIFESTAGELABEL','');
//define('SEXLABEL','');
//define('INDIVIDUALCOUNTLABEL','');
//define('SAMPLINGPROTOCOLLABEL','');
//define('PREPARATIONSLABEL','');
//define('REPRODUCTIVECONDITIONLABEL','');
//define('ESTABLISHMENTMEANSLABEL','');
//define('CULTIVATIONSTATUSLABEL','');
//define('TYPESTATUSLABEL','');
//define('DISPOSITIONLABEL','');
//define('OCCURRENCEIDLABEL','');
//define('FIELDNUMBERLABEL','');
//define('OWNERINSTITUTIONCODELABEL','');
//define('BASISOFRECORDLABEL','');
//define('LANGUAGELABEL','');
//define('LABELPROJECTLABEL','');
//define('DUPLICATEQUALITYCOUNTLABEL','');
//define('PROCESSINGSTATUSLABEL','');
//define('DATAGENERALIZATIONSLABEL','');

//Occurrence Editor Tooltip text: uncomment variables and value to modify tooltips
//define('CATALOGNUMBERTIP','');
//define('OTHERCATALOGNUMBERSTIP','');
//define('RECORDEDBYTIP','');
//define('RECORDNUMBERTIP','');
//define('EVENTDATETIP','');
//define('ASSOCIATEDCOLLECTORSTIP','');
//define('VERBATIMEVENTDATETIP','');
//define('YYYYMMDDTIP','');
//define('DAYOFYEARTIP','');
//define('ENDDATETIP','');
//define('SCINAMETIP','');
//define('SCIENTIFICNAMEAUTHORSHIPTIP','');
//define('FAMILYTIP','');
//define('IDENTIFICATIONQUALIFIERTIP','');
//define('IDENTIFIEDBYTIP','');
//define('DATEIDENTIFIEDTIP','');
//define('IDENTIFICATIONREFERENCETIP','');
//define('IDENTIFICATIONREMARKSTIP','');
//define('TAXONREMARKSTIP','');
//define('COUNTRYTIP','');
//define('STATEPROVINCETIP','');
//define('COUNTYTIP','');
//define('MUNICIPALITYTIP','');
//define('LOCALITYTIP','');
//define('LOCALITYSECURITYTIP','');
//define('LOCALITYSECURITYREASONTIP','');
//define('LOCATIONREMARKS','');
//define('DECIMALLATITUDETIP','');
//define('DECIMALLONGITUDETIP','');
//define('GEODETICDATIMTIP','');
//define('COORDINATEUNCERTAINITYINMETERSTIP','');
//define('ELEVATIONINMETERSTIP','');
//define('VERBATIMELEVATION','');
//define('DEPTHINMETERSTIP','');
//define('VERBATIMDEPTH','');
//define('FOOTPRINTWKTTIP','');
//define('VERBATIMCOORDINATESTIP','');
//define('GEOREFERENCEBYTIP','');
//define('GEOREFERENCEPROTOCOLTIP','');
//define('GEOREFERENCESOURCESTIP','');
//define('GEOREFERENCEVERIFICATIONSTATUSTIP','');
//define('GEOREFERENCEREMARKSTIP','');
//define('HABITATTIP','');
//define('SUBSTRATETIP','');
//define('ASSOCIATEDTAXATIP','');
//define('VERBATIMATTRIBUTESTIP','');
//define('OCCURRENCEREMARKSTIP','');
//define('FIELDNOTESTIP','');
//define('DYNAMICPROPERTIESTIP','');
//define('LIFESTAGETIP','');
//define('SEXTIP','');
//define('INDIVIDUALCOUNTTIP','');
//define('SAMPLINGPROTOCOLTIP','');
//define('PREPARATIONSTIP','');
//define('REPRODUCTIVECONDITIONTIP','');
//define('ESTABLISHMENTMEANSTIP','');
//define('CULTIVATIONSTATUSTIP','');
//define('TYPESTATUSTIP','');
//define('DISPOSITIONTIP','');
//define('OCCURRENCEIDTIP','');
//define('FIELDNUMBERTIP','');
//define('OWNERINSTITUTIONCODETIP','');
//define('BASISOFRECORDTIP','');
//define('LANGUAGETIP','');
//define('TIPPROJECTTIP','');
//define('DUPLICATEQUALITYCOUNTTIP','');
//define('PROCESSINGSTATUSTIP','');
//define('DATAGENERALIZATIONSTIP','');

?>