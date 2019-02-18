ALTER TABLE `omoccurrences` 
  ADD COLUMN `informationWithheld` VARCHAR(250) NULL AFTER `occurrenceRemarks`; 

ALTER TABLE `omoccurrences` 
  ADD COLUMN `recordEnteredBy` VARCHAR(250) NULL AFTER `processingStatus`; 

ALTER TABLE `omoccurrences` 
  ADD COLUMN `duplicateQuantity` INT UNSIGNED NULL AFTER `recordEnteredBy`; 

ALTER TABLE `uploadspectemp` 
  ADD COLUMN `informationWithheld` VARCHAR(250) NULL AFTER `occurrenceRemarks`; 

ALTER TABLE `uploadspectemp` 
  ADD COLUMN `localitySecurityReason` VARCHAR(100) NULL  AFTER `localitySecurity`; 

ALTER TABLE `uploadspectemp` 
  ADD COLUMN `recordEnteredBy` VARCHAR(250) NULL AFTER `language`; 

ALTER TABLE `uploadspectemp` 
  ADD COLUMN `duplicateQuantity` INT UNSIGNED NULL AFTER `recordEnteredBy`; 

ALTER TABLE `omcollections` 
  ADD COLUMN `ManagementType` VARCHAR(45) NULL DEFAULT 'Snapshot' COMMENT 'Snapshot, Live Data'  AFTER `CollType` , 
  CHANGE COLUMN `CollType` `CollType` VARCHAR(45) NOT NULL DEFAULT 'Preserved Specimens' COMMENT 'Preserved Specimens, General Observations, Observations';

ALTER TABLE `uploadspectemp` 
  DROP FOREIGN KEY `FK_uploadspectemp_coll`; 

ALTER TABLE `uploadspectemp` 
  CHANGE COLUMN `dbpk` `dbpk` VARCHAR(45) NULL DEFAULT NULL, 
  DROP PRIMARY KEY; 

ALTER TABLE `uploadspectemp`    
  ADD CONSTRAINT `FK_uploadspectemp_coll`  FOREIGN KEY (`collid` ) REFERENCES `omcollections` (`CollID` ) ON DELETE CASCADE  ON UPDATE CASCADE, 
  ADD INDEX `FK_uploadspectemp_coll` (`collid` ASC); 

ALTER TABLE `images` 
  DROP FOREIGN KEY `FK_images_occ` ; 

ALTER TABLE `images`    
  ADD CONSTRAINT `FK_images_occ`   FOREIGN KEY (`occid` )  REFERENCES `omoccurrences` (`occid` )  ON DELETE SET NULL  ON UPDATE SET NULL;

ALTER TABLE `omoccurrences`  
  ADD INDEX `Index_municipality` (`municipality` ASC) ;

ALTER TABLE `omoccurrences` 
  ADD COLUMN `dataGeneralizations` VARCHAR(250) NULL  AFTER `informationWithheld` ; 

ALTER TABLE `uploadspectemp` 
  ADD COLUMN `dataGeneralizations` VARCHAR(250) NULL  AFTER `informationWithheld` ; 

ALTER TABLE `taxonunits` 
  ADD COLUMN `suffix` VARCHAR(45) NULL  AFTER `rankname`, 
  CHANGE COLUMN `reqparentrankid` `reqparentrankid` SMALLINT(6) NULL ; 

-- =============================================
-- Cleanup and transfer scripts used for updating a collection 
-- Used in /collections/admin/datauploader.php
-- =============================================

DROP PROCEDURE IF EXISTS TransferUploads;

DELIMITER //

CREATE PROCEDURE `TransferUploads`(IN targetCollId INT)

BEGIN

   IF targetCollId > 0 THEN

        #Before transfer, Update misc fields when needed
        UPDATE uploadspectemp u
        SET u.year = YEAR(u.eventDate)
        WHERE u.collid = targetCollId AND u.eventDate IS NOT NULL AND (u.year IS NULL OR u.year = 0);

        UPDATE uploadspectemp u
        SET u.month = MONTH(u.eventDate)
        WHERE u.collid = targetCollId AND (u.month IS NULL OR u.month = 0) AND u.eventDate IS NOT NULL;

        UPDATE uploadspectemp u
        SET u.day = DAY(u.eventDate)
        WHERE u.collid = targetCollId AND (u.day IS NULL OR u.day = 0) AND u.eventDate IS NOT NULL;

        UPDATE uploadspectemp u
        SET u.startDayOfYear = DAYOFYEAR(u.eventDate)
        WHERE u.collid = targetCollId AND u.startDayOfYear IS NULL AND u.eventDate IS NOT NULL;

        UPDATE uploadspectemp u
        SET u.endDayOfYear = DAYOFYEAR(u.LatestDateCollected)
        WHERE u.collid = targetCollId AND u.endDayOfYear IS NULL AND u.LatestDateCollected IS NOT NULL;

         #Update LocalitySecurity
         UPDATE taxa t INNER JOIN uploadspectemp u ON t.SciName = u.SciName
         SET u.LocalitySecurity = t.SecurityStatus
         WHERE u.collid = targetCollId AND (t.SecurityStatus > 0) AND (u.LocalitySecurity = 0 OR u.LocalitySecurity IS NULL);

         #standardize some of the fields
         UPDATE uploadspectemp s SET s.sciname = replace(s.sciname," ssp. "," subsp. ") WHERE s.sciname like "% ssp. %";
         UPDATE uploadspectemp s SET s.sciname = replace(s.sciname," var "," var. ") WHERE s.sciname like "% var %";
         UPDATE uploadspectemp s SET s.sciname = replace(s.sciname," subsp "," subsp. ") WHERE s.sciname like "% subsp %";
         UPDATE uploadspectemp s SET s.sciname = replace(s.sciname," cf. "," ") WHERE s.sciname like "% cf. %";
         UPDATE uploadspectemp s SET s.sciname = replace(s.sciname," cf "," ") WHERE s.sciname like "% cf %";
         UPDATE uploadspectemp s SET s.sciname = REPLACE(s.sciname," aff. "," "), tidinterpreted = null WHERE sciname like "% aff. %";
         UPDATE uploadspectemp s SET s.sciname = REPLACE(s.sciname," aff "," "), tidinterpreted = null WHERE sciname like "% aff %";

         UPDATE uploadspectemp s SET s.country = "USA" WHERE s.country = "U.S.A.";
         UPDATE uploadspectemp s SET s.country = "USA" WHERE s.country = "U.S.";
         UPDATE uploadspectemp s SET s.country = "USA" WHERE s.country = "US";
         UPDATE uploadspectemp s SET s.country = "USA" WHERE s.country = "United States";
         UPDATE uploadspectemp s SET s.country = "USA" WHERE s.country = "United States of America";
         UPDATE uploadspectemp s SET s.country = "USA" WHERE s.country = "United States America";

         UPDATE uploadspectemp s
         SET s.sciname = trim(s.sciname), tidinterpreted = null
         WHERE sciname like "% " OR sciname like " %";

         UPDATE uploadspectemp s
         SET s.sciname = replace(s.sciname,"   "," ")
         WHERE sciname like "%   %";

         UPDATE uploadspectemp s
         SET s.sciname = replace(s.sciname,"  "," ")
         WHERE sciname like "%  %";

         UPDATE uploadspectemp s
         SET s.sciname = replace(s.sciname," sp.","")
         WHERE sciname like "% sp.";

         UPDATE uploadspectemp s
         SET s.sciname = replace(s.sciname," sp","")
         WHERE sciname like "% sp";

         UPDATE uploadspectemp
         SET specificepithet = NULL
         WHERE specificepithet = "sp." OR specificepithet = "sp";

         #Link specimens to taxa
         UPDATE uploadspectemp s INNER JOIN taxa t ON s.sciname = t.sciname SET s.TidInterpreted = t.tid WHERE s.TidInterpreted IS NULL;


         #Filling missing families
         UPDATE uploadspectemp u INNER JOIN taxstatus ts ON u.tidinterpreted = ts.tid
             SET u.family = ts.family
             WHERE ts.taxauthid = 1 AND ts.family <> "" AND ts.family IS NOT NULL AND (u.family IS NULL OR u.family = "");

         UPDATE uploadspectemp u INNER JOIN taxa t ON u.genus = t.unitname1
             INNER JOIN taxstatus ts on t.tid = ts.tid
             SET u.family = ts.family
             WHERE t.rankid = 180 and ts.taxauthid = 1 AND ts.family IS NOT NULL AND (u.family IS NULL OR u.family = "");

         #Fill in missing Authors
         UPDATE uploadspectemp u INNER JOIN taxa t ON u.tidinterpreted = t.tid
             SET u.scientificNameAuthorship = t.author
             WHERE (u.scientificNameAuthorship = "" OR u.scientificNameAuthorship) IS NULL AND t.author IS NOT NULL;

         #Convert positive longs to negative
         UPDATE uploadspectemp SET DecimalLongitude = -1*DecimalLongitude
         WHERE DecimalLongitude > 0 AND (Country = "USA" OR Country = "Mexico");

         #Clean illegal lat/longs that are out of range
         UPDATE uploadspectemp SET DecimalLatitude = \N, DecimalLongitude = \N WHERE DecimalLatitude = 0 AND DecimalLongitude = 0;
         UPDATE uploadspectemp SET DecimalLatitude = \N, DecimalLongitude = \N WHERE DecimalLatitude < -90 OR DecimalLatitude > 90;
         UPDATE uploadspectemp SET DecimalLatitude = \N, DecimalLongitude = \N WHERE DecimalLongitude < -180 OR DecimalLongitude > 180;

         #Deal with UTM
         UPDATE uploadspectemp
         SET  verbatimCoordinates = CONCAT_WS("; ",verbatimCoordinates,CONCAT("UTM: ",UtmNorthing,UtmEasting,UtmZoning))
         WHERE UtmNorthing IS NOT NULL;

     #transfer Occurrence records
        #Link existing records
        UPDATE uploadspectemp u INNER JOIN omoccurrences o ON (u.dbpk = o.dbpk) AND (u.collid = o.collid)
        SET u.occid = o.occid
        WHERE u.collid = targetCollId AND u.occid IS NULL;

        #Update existing records
        UPDATE uploadspectemp u INNER JOIN omoccurrences o ON u.occid = o.occid
        SET o.basisOfRecord = u.basisOfRecord, o.occurrenceID = u.occurrenceID, o.catalogNumber = u.catalogNumber,
        o.otherCatalogNumbers = u.otherCatalogNumbers, o.ownerInstitutionCode = u.ownerInstitutionCode, o.family = u.family,
        o.scientificName = u.scientificName, o.sciname = u.sciname, o.genus = u.genus, o.institutionID = u.institutionID,
        o.collectionID = u.collectionID, o.specificEpithet = u.specificEpithet, o.datasetID = u.datasetID, o.taxonRank = u.taxonRank,
        o.infraspecificEpithet = u.infraspecificEpithet, o.institutionCode = u.institutionCode, o.collectionCode = u.collectionCode,
        o.scientificNameAuthorship = u.scientificNameAuthorship, o.taxonRemarks = u.taxonRemarks, o.identifiedBy = u.identifiedBy,
        o.dateIdentified = u.dateIdentified, o.identificationReferences = u.identificationReferences,
        o.identificationRemarks = u.identificationRemarks, o.identificationQualifier = u.identificationQualifier, o.typeStatus = u.typeStatus,
        o.recordedBy = u.recordedBy, o.recordNumber = u.recordNumber, o.CollectorFamilyName = u.CollectorFamilyName,
        o.CollectorInitials = u.CollectorInitials, o.associatedCollectors = u.associatedCollectors, o.eventDate = u.eventDate,
        o.year = u.year, o.month = u.month, o.day = u.day, o.startDayOfYear = u.startDayOfYear, o.endDayOfYear = u.endDayOfYear,
        o.verbatimEventDate = u.verbatimEventDate, o.habitat = u.habitat, o.fieldNotes = u.fieldNotes, o.occurrenceRemarks = u.occurrenceRemarks, o.informationWithheld = u.informationWithheld,
        o.associatedOccurrences = u.associatedOccurrences, o.associatedTaxa = u.associatedTaxa, o.dynamicProperties = u.dynamicProperties,
        o.reproductiveCondition = u.reproductiveCondition, o.cultivationStatus = u.cultivationStatus, o.establishmentMeans = u.establishmentMeans,
        o.country = u.country, o.stateProvince = u.stateProvince, o.county = u.county, o.municipality = u.municipality, o.locality = u.locality,
        o.localitySecurity = u.localitySecurity, o.localitySecurityReason = u.localitySecurityReason, o.decimalLatitude = u.decimalLatitude, o.decimalLongitude = u.decimalLongitude,
        o.geodeticDatum = u.geodeticDatum, o.coordinateUncertaintyInMeters = u.coordinateUncertaintyInMeters,
        o.coordinatePrecision = u.coordinatePrecision, o.locationRemarks = u.locationRemarks, o.verbatimCoordinates = u.verbatimCoordinates,
        o.verbatimCoordinateSystem = u.verbatimCoordinateSystem, o.georeferencedBy = u.georeferencedBy, o.georeferenceProtocol = u.georeferenceProtocol,
        o.georeferenceSources = u.georeferenceSources, o.georeferenceVerificationStatus = u.georeferenceVerificationStatus,
        o.georeferenceRemarks = u.georeferenceRemarks, o.minimumElevationInMeters = u.minimumElevationInMeters,
        o.maximumElevationInMeters = u.maximumElevationInMeters, o.verbatimElevation = u.verbatimElevation,
        o.previousIdentifications = u.previousIdentifications, o.disposition = u.disposition, o.modified = u.modified, o.language = u.language, o.recordEnteredBy = u.recordEnteredBy, o.duplicateQuantity = u.duplicateQuantity
        WHERE u.collid = targetCollId;


        #Insert new records
        INSERT IGNORE INTO omoccurrences (collid, dbpk, basisOfRecord, occurrenceID, catalogNumber, otherCatalogNumbers, ownerInstitutionCode, family, scientificName,
        sciname, genus, institutionID, collectionID, specificEpithet, datasetID, taxonRank, infraspecificEpithet, institutionCode, collectionCode,
        scientificNameAuthorship, taxonRemarks, identifiedBy, dateIdentified, identificationReferences, identificationRemarks,
        identificationQualifier, typeStatus, recordedBy, recordNumber, CollectorFamilyName, CollectorInitials, associatedCollectors,
        eventDate, Year, Month, Day, startDayOfYear, endDayOfYear, verbatimEventDate, habitat, fieldNotes, occurrenceRemarks, informationWithheld,
        associatedOccurrences, associatedTaxa, dynamicProperties, reproductiveCondition, cultivationStatus, establishmentMeans, country, stateProvince,
        county, municipality, locality, localitySecurity, localitySecurityReason, decimalLatitude, decimalLongitude, geodeticDatum, coordinateUncertaintyInMeters,
        coordinatePrecision, locationRemarks, verbatimCoordinates, verbatimCoordinateSystem, georeferencedBy, georeferenceProtocol,
        georeferenceSources, georeferenceVerificationStatus, georeferenceRemarks, minimumElevationInMeters, maximumElevationInMeters,
        verbatimElevation, previousIdentifications, disposition, modified, language, recordEnteredBy, duplicateQuantity )
        SELECT u.collid, u.dbpk, u.basisOfRecord, u.occurrenceID, u.catalogNumber, u.otherCatalogNumbers, u.ownerInstitutionCode,
        u.family, u.scientificName,
        u.sciname, u.genus, u.institutionID, u.collectionID, u.specificEpithet, u.datasetID, u.taxonRank, u.infraspecificEpithet,
        u.institutionCode, u.collectionCode, u.scientificNameAuthorship, u.taxonRemarks, u.identifiedBy, u.dateIdentified,
        u.identificationReferences, u.identificationRemarks, u.identificationQualifier, u.typeStatus, u.recordedBy, u.recordNumber,
        u.CollectorFamilyName, u.CollectorInitials, u.associatedCollectors, u.eventDate, u.Year, u.Month, u.Day, u.startDayOfYear,
        u.endDayOfYear, u.verbatimEventDate, u.habitat, u.fieldNotes, u.occurrenceRemarks, u.informationWithheld, u.associatedOccurrences, u.associatedTaxa,
        u.dynamicProperties, u.reproductiveCondition, u.cultivationStatus, u.establishmentMeans, u.country, u.stateProvince, u.county,
        u.municipality, u.locality, u.localitySecurity, u.localitySecurityReason, u.decimalLatitude, u.decimalLongitude, u.geodeticDatum, u.coordinateUncertaintyInMeters,
        u.coordinatePrecision, u.locationRemarks, u.verbatimCoordinates, u.verbatimCoordinateSystem, u.georeferencedBy, u.georeferenceProtocol,
        u.georeferenceSources, u.georeferenceVerificationStatus, u.georeferenceRemarks, u.minimumElevationInMeters, u.maximumElevationInMeters,
        u.verbatimElevation, u.previousIdentifications, u.disposition, u.modified, u.language, u.recordEnteredBy, u.duplicateQuantity
        FROM uploadspectemp u
        WHERE u.occid Is Null AND u.collid = targetCollId;


        DELETE FROM uploadspectemp WHERE collid = targetCollId;

        #Update stats
        UPDATE omcollectionstats SET uploaddate = NOW() WHERE collid = targetCollId;
        CALL UpdateCollectionStats(targetCollId);



    END IF;

END//


DELIMITER ;


DROP procedure IF EXISTS `UpdateCollectionStats`;  

DELIMITER $$ 

CREATE PROCEDURE `UpdateCollectionStats`(IN collectionid INT) 

BEGIN  

UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname SET o.TidInterpreted = t.tid WHERE o.TidInterpreted IS NULL;  

UPDATE omcollectionstats cs SET cs.recordcnt = (SELECT count(*) FROM omoccurrences WHERE collid = collectionid) WHERE cs.collid = collectionid;  

UPDATE omcollectionstats cs SET cs.familycnt = (SELECT count(DISTINCT ts1.family) FROM taxstatus ts1 INNER JOIN taxstatus ts2 ON ts1.tid = ts2.tidaccepted INNER JOIN omoccurrences o ON ts2.tid = o.tidinterpreted WHERE ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND o.collid = collectionid) WHERE cs.collid = collectionid;  

UPDATE omcollectionstats cs SET cs.genuscnt = (SELECT count(DISTINCT t.unitname1) as genuscnt FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted WHERE o.collid = collectionid) WHERE cs.collid = collectionid;  

UPDATE omcollectionstats cs SET cs.speciescnt = (SELECT count(DISTINCT t.unitname1,t.unitname2) as sppcnt FROM taxa t INNER JOIN omoccurrences o ON t.tid = o.tidinterpreted WHERE o.collid = collectionid) WHERE cs.collid = collectionid;  

UPDATE omcollectionstats cs SET cs.georefcnt = (SELECT Count(o.occid) FROM omoccurrences o  WHERE (o.CollID = collectionid) AND (o.DecimalLatitude Is Not Null) AND (o.DecimalLongitude Is Not Null)) WHERE cs.collid = collectionid; 

END$$  

DELIMITER ; 