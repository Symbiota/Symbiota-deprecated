<?php 
$fieldArr = Array(
	"GlobalUniqueIdentifier" => "Unique Identifier:",
	"CatalogNumber" => "Accession #:",
	"Remarks" => "Notes",
	"SciName" => "SciName",
	"" => "",
	"" => "",
	"" => "",
	"" => "",
	"" => "",
	"" => "",
	"" => "",
	"" => "",
	"" => "",
);
?>

s.globaluniqueidentifier,IFNULL(s.catalognumber,s.catalognumbernumeric) AS catalognumber,
s.family,s.sciname,s.authoryearofscientificname,
s.identificationqualifier,s.taxonnotes,s.identifiedby,s.dateidentified,s.country,
s.stateprovince,s.county,s.locality,s.decimallatitude,s.decimallongitude,s.geodeticdatum,
s.coordinateuncertaintyinmeters,s.georeferenceremarks,s.verbatimcoordinates,
IFNULL(s.minimumelevationinmeters,s.verbatimelevation) AS minimumelevationinmeters
s.maximumelevationinmeters,s.collector,s.othercollectors,s.collectornumber,
IFNULL(s.earliestdatecollected,s.verbatimcollectingdate) AS earliestdatecollected,
s.latestdatecollected,s.fieldnotes,s.attributes,s.habitat,
s.assocspp,s.remarks,s.cultivationstatus,s.herbariumacronym,s.duplicatecount,
s.typestatus,s.localitysecurity,s.dbpk

<div>

Extra fields to edit:
<div id="Kingdom-label" class="labeldiv">Kingdom<div>
<div id="HigherTaxon-label" class="labeldiv">HigherTaxon<div>
<div id="TidInterpreted-label" class="labeldiv">TidInterpreted<div>
<div id="Genus-label" class="labeldiv">Genus<div>
<div id="SpecificEpithet-label" class="labeldiv">SpecificEpithet<div>
<div id="InfraspecificRank-label" class="labeldiv">InfraspecificRank<div>
<div id="InfraSpecificEpithet-label" class="labeldiv">InfraSpecificEpithet<div>
<div id="NomenclaturalCode-label" class="labeldiv">NomenclaturalCode<div>
<div id="CatalogNumberNumeric-label" class="labeldiv">CatalogNumberNumeric<div>
<div id="InformationWithheld-label" class="labeldiv">InformationWithheld<div>
<div id="HigherGeography-label" class="labeldiv">HigherGeography<div>
<div id="VerbatimElevation-label" class="labeldiv">VerbatimElevation<div>
<div id="CollectingMethod-label" class="labeldiv">CollectingMethod<div>
<div id="VerbatimCoordinates-label" class="labeldiv">VerbatimCoordinates<div>
<div id="VerbatimLatitude-label" class="labeldiv">VerbatimLatitude<div>
<div id="VerbatimLongitude-label" class="labeldiv">VerbatimLongitude<div>
<div id="VerbatimCoordinateSystem-label" class="labeldiv">VerbatimCoordinateSystem<div>
<div id="UtmNorthing-label" class="labeldiv">UtmNorthing<div>
<div id="UtmEasting-label" class="labeldiv">UtmEasting<div>
<div id="UtmZoning-label" class="labeldiv">UtmZoning<div>
<div id="FieldNumber-label" class="labeldiv">FieldNumber<div>
<div id="VerbatimCollectingDate-label" class="labeldiv">VerbatimCollectingDate<div>
<div id="RelatedCatalogItems-label" class="labeldiv">RelatedCatalogItems<div>
<div id="LocalitySecurity-label" class="labeldiv">LocalitySecurity<div>
<div id="BasisOfRecord-label" class="labeldiv">BasisOfRecord<div>
<div id="DBPK-label" class="labeldiv">DBPK<div>
<div id="GenBankNumber-label" class="labeldiv">GenBankNumber<div>
<div id="OtherCalaogNumbers-label" class="labeldiv">OtherCalaogNumbers<div>
<div id="RelatedInformation-label" class="labeldiv">RelatedInformation<div>
<div id="GeoreferenceProtocol-label" class="labeldiv">GeoreferenceProtocol<div>
<div id="GeoreferenceSources-label" class="labeldiv">GeoreferenceSources<div>
<div id="GeoreferenceVerificationStatus-label" class="labeldiv">GeoreferenceVerificationStatus<div>
IndividualCount

Display but not editable
<div id="DateLastModified-label" class="labeldiv">DateLastModified<div>

Get Rid of:
<div id="ChromosomeNumber-label" class="labeldiv">ChromosomeNumber<div>
<div id="Phenology-label" class="labeldiv">Phenology<div>
<div id="ScientificName-label" class="labeldiv">ScientificName<div>
