--  Index definitions to support OAI/PMH harvesting of occurrences and taxa

create index idx_omocclastmodified on omoccurrences(datelastmodified);
create index idx_taxacreated on taxa(initialtimestamp);
--  TODO: Need to add date last modified to taxa.

--  View definitions to support OAI/PMH harvesting of occurrences and taxa from Symbiota.

--  View for occurrences
CREATE ALGORITHM=UNDEFINED DEFINER='debian-sys-maint'@'localhost' SQL SECURITY DEFINER 
VIEW tbl_occurrence AS
select concat('scan.occurrence.',omoccurrences.occid) AS id,   --  Can't index on this field
omoccurrences.occid as pk,                                     --  Use this field to query 
omoccurrences.dateLastModified AS datestamp,                   --  Needs index in omoccurrences
'dwc' AS metadataPrefix,                                       --  Static for this view, can't index, omit from query
ifnull(omoccurrences.basisOfRecord,'PreservedSpecimen') AS basisOfRecord,
omoccurrences.occurrenceID AS occurrenceID,
omoccurrences.catalogNumber AS catalogNumber,
omoccurrences.otherCatalogNumbers AS otherCatalogNumbers,
omcollections.InstitutionCode AS ownerInstitutionCode,
omoccurrences.institutionID AS institutionID,
omoccurrences.collectionID AS collectionID,
omoccurrences.datasetID AS datasetID,
ifnull(omoccurrences.InstitutionCode,omcollections.InstitutionCode) AS institutionCode,
ifnull(omoccurrences.CollectionCode,omcollections.CollectionCode) AS collectionCode,
omoccurrences.family AS family,
omoccurrences.sciname AS scientificName,
omoccurrences.genus AS genus,
omoccurrences.specificEpithet AS specificEpithet,
omoccurrences.taxonRank AS taxonRank,
omoccurrences.infraspecificEpithet AS infraspecificEpithet,
omoccurrences.scientificNameAuthorship AS scientificNameAuthorship,
omoccurrences.taxonRemarks AS taxonRemarks,
omoccurrences.identifiedBy AS identifiedBy,
omoccurrences.dateIdentified AS dateIdentified,
omoccurrences.identificationReferences AS identificationReferences,
omoccurrences.identificationRemarks AS identificationRemarks,
omoccurrences.identificationQualifier AS identificationQualifier,
omoccurrences.typeStatus AS typeStatus,
omoccurrences.recordedBy AS recordedBy,
omoccurrences.recordNumber AS recordNumber,
omoccurrences.recordedById AS recordedById,
omoccurrences.associatedCollectors AS associatedCollectors,
omoccurrences.eventDate AS eventDate,
omoccurrences.year AS year,
omoccurrences.month AS month,
omoccurrences.day AS day,
omoccurrences.startDayOfYear AS startDayOfYear,
omoccurrences.endDayOfYear AS endDayOfYear,
omoccurrences.verbatimEventDate AS verbatimEventDate,
omoccurrences.habitat AS habitat,
omoccurrences.substrate AS substrate,
omoccurrences.fieldNotes AS fieldNotes,
omoccurrences.occurrenceRemarks AS occurrenceRemarks,
omoccurrences.informationWithheld AS informationwithheld,
omoccurrences.associatedOccurrences AS associatedOccurrences,
omoccurrences.associatedTaxa AS associatedTaxa,
omoccurrences.dynamicProperties AS dynamicProperties,
omoccurrences.verbatimAttributes AS verbatimAttributes,
omoccurrences.attributes AS attributes,
omoccurrences.reproductiveCondition AS reproductiveCondition,
omoccurrences.cultivationStatus AS cultivationStatus,
omoccurrences.establishmentMeans AS establishmentMeans,
omoccurrences.lifeStage AS lifeStage,
omoccurrences.sex AS sex,
omoccurrences.individualCount AS individualCount,
omoccurrences.samplingProtocol AS samplingProtocol,
omoccurrences.samplingEffort AS samplingEffort,
omoccurrences.preparations AS preparations,
omoccurrences.country AS country,
omoccurrences.stateProvince AS stateProvince,
omoccurrences.county AS county,
omoccurrences.municipality AS municipality,
omoccurrences.locality AS locality,
omoccurrences.localitySecurity AS localitySecurity,
omoccurrences.localitySecurityReason AS localitySecurityReason,
omoccurrences.decimalLatitude AS decimalLatitude,
omoccurrences.decimalLongitude AS decimalLongitude,
omoccurrences.geodeticDatum AS geodeticDatum,
omoccurrences.coordinateUncertaintyInMeters AS coordinateUncertaintyInMeters,
omoccurrences.footprintWKT AS footprintWKT,
omoccurrences.coordinatePrecision AS coordinatePrecision,
omoccurrences.locationRemarks AS locationRemarks,
omoccurrences.verbatimCoordinates AS verbatimCoordinates,
omoccurrences.verbatimCoordinateSystem AS verbatimCoordinateSystem,
omoccurrences.georeferencedBy AS georeferencedBy,
omoccurrences.georeferenceProtocol AS georeferenceProtocol,
omoccurrences.georeferenceSources AS georeferenceSources,
omoccurrences.georeferenceVerificationStatus AS georeferenceVerificationStatus,
omoccurrences.georeferenceRemarks AS georeferenceRemarks,
omoccurrences.minimumElevationInMeters AS minimumElevationInMeters,
omoccurrences.maximumElevationInMeters AS maximumElevationInMeters,
omoccurrences.verbatimElevation AS verbatimElevation,
omoccurrences.previousIdentifications AS previousIdentifications,
if(((omoccurrences.localitySecurity is not null) and (omoccurrences.localitySecurity > 0)), 'sensitive', '') AS accessRights,
omoccurrences.language AS language,
'' AS oai_set,
omoccurrences.dateLastModified AS modified 
from omoccurrences join omcollections on omoccurrences.collid = omcollections.CollID;

--  View for taxon tree 1
CREATE ALGORITHM=UNDEFINED DEFINER=debian-sys-maint@localhost SQL SECURITY DEFINER 
VIEW tbl_taxa_default AS 
select concat('scan.taxon.default.', taxa.TID) AS id,
taxa.tid as pk,
'dwc' AS metadataPrefix,
taxstatus.initialtimestamp AS modified,
taxa.SciName AS scientificName, 
if(isnull(taxstatus.parenttid), NULL, concat('scan.taxon.default.', taxstatus.parenttid)) AS parentNameUsageID,
(case taxa.kingdomid when 3 then 'Plantae' when 5 then 'Animalia' else NULL end) AS kingdom,
taxstatus.family AS family,
taxonunits.rankname AS taxonRank,
if((taxa.RankId >= 220),
parent.SciName,
NULL) AS genus,
taxa.Notes AS taxonRemarks,
taxa.Author AS scientificNameAuthorship, 
if(isnull(taxstatus.tidaccepted), NULL, concat('scan.taxon.default.', taxstatus.tidaccepted)) AS acceptedNameUsageID,
taxonunits.rankid AS rankid 
from (((taxa left join taxstatus on((taxa.TID = taxstatus.tid))) left join taxonunits on(((taxa.RankId = taxonunits.rankid) and (taxa.kingdomid = taxonunits.kingdomid)))) left join taxa parent on((taxstatus.parenttid = parent.TID))) 
where (taxstatus.taxauthid = 1);

--  View for taxon tree 2
CREATE ALGORITHM=UNDEFINED DEFINER=debian-sys-maint@localhost SQL SECURITY DEFINER 
VIEW tbl_taxa_itis AS 
select concat('scan.taxon.itis.', taxa.TID) AS id,
taxa.tid as pk,
'dwc' AS metadataPrefix,
taxstatus.initialtimestamp AS modified,
taxa.SciName AS scientificName,
if(isnull(taxstatus.parenttid), NULL, concat('scan.taxon.itis.', taxstatus.parenttid)) AS parentNameUsageID,
(case taxa.kingdomid when 3 then 'Plantae' when 5 then 'Animalia' else NULL end) AS kingdom,
taxstatus.family AS family,
taxonunits.rankname AS taxonRank, 
if((taxa.RankId >= 220), parent.SciName, NULL) AS genus,
taxa.Notes AS taxonRemarks,
taxa.Author AS scientificNameAuthorship,
if(isnull(taxstatus.tidaccepted), NULL, concat('scan.taxon.itis.', taxstatus.tidaccepted)) AS acceptedNameUsageID,
taxonunits.rankid AS rankid 
from (((taxa left join taxstatus on((taxa.TID = taxstatus.tid))) left join taxonunits on(((taxa.RankId = taxonunits.rankid) and (taxa.kingdomid = taxonunits.kingdomid)))) left join taxa parent on((taxstatus.parenttid = parent.TID))) 
where (taxstatus.taxauthid = 2)

