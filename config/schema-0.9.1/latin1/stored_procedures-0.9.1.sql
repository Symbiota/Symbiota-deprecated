-- =============================================
-- Author: egbot
-- Create date: 30 July 2010
-- Description: Stored procedures for SYMBIOTA Virtual Environment
-- =============================================

-- =============================================
-- Required for Synonym searches within Specimen Search Engine
-- Used in /collections/util/CollectionManager.php
-- =============================================

DELIMITER //
CREATE PROCEDURE `ReturnSynonyms`(IN searchInput varchar(250),IN taxaAuthorityId int)
BEGIN
DECLARE count_var int;
DECLARE taxaAuthId int;
DECLARE targetName VARCHAR(200);

#If taxaAuthorityId is null, set to default
IF taxaAuthorityId > 0 THEN
   SET taxaAuthId = taxaAuthorityId;
ELSE
   SET taxaAuthId = 1;
END IF;

SELECT taxa.SciName INTO targetName FROM taxa WHERE (taxa.SciName = searchInput OR taxa.Tid = searchInput);

DROP TABLE IF EXISTS temp_table1;
DROP TABLE IF EXISTS temp_table2;
CREATE TEMPORARY TABLE temp_table1 (tid int, sciname varchar(250));
CREATE TEMPORARY TABLE temp_table2 (tid int, sciname varchar(250));

#Put accepted taxa into temp table1
INSERT INTO temp_table1 (tid, sciname)
SELECT DISTINCT taxa.Tid, taxa.SciName
   FROM (taxa INNER JOIN taxstatus ON taxa.Tid = taxstatus.TidAccepted)
   INNER JOIN taxa t2 ON taxstatus.Tid = t2.Tid
   WHERE (taxstatus.taxauthid = taxaAuthId) AND ((t2.SciName = searchInput OR t2.Tid = searchInput))
   ORDER BY taxa.RankId;

INSERT INTO temp_table2 (tid, sciname)
SELECT tid, sciname FROM temp_table1;

#Gramb all children
SET count_var = 220;
IF taxaAuthorityId IS NULL THEN
        WHILE count_var < 301 DO
	        INSERT INTO temp_table1 (tid, sciname)
                SELECT DISTINCT t.tid, t.sciname
                FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid)
                INNER JOIN temp_table2 t2 ON t2.tid = ts.parentTID
        	WHERE (t.RankId > count_var);

                DELETE FROM temp_table2;

                INSERT INTO temp_table2 (tid, sciname)
	        SELECT DISTINCT tid, sciname FROM temp_table1;
	        SET count_var = count_var + 10;
        END WHILE;
ELSE
        WHILE count_var < 301 DO
	        INSERT INTO temp_table1 (tid, sciname)
                SELECT DISTINCT t.tid, t.sciname
                FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid)
                INNER JOIN temp_table2 t2 ON ts.parenttid = t2.tid
	        WHERE (ts.taxauthid = taxaAuthId) AND (ts.TidAccepted = ts.tid) AND (t.RankId > count_var);

                DELETE FROM temp_table2;

                INSERT INTO temp_table2 (tid, sciname)
      	        SELECT DISTINCT tid, sciname FROM temp_table1;
	        SET count_var = count_var + 10;
        END WHILE;
END IF;

#Put synonym taxa into temp table1
INSERT INTO temp_table1 (tid, sciname)
SELECT DISTINCT taxa.TID, taxa.SciName
FROM (taxa INNER JOIN taxstatus ON taxa.TID = taxstatus.TID)
INNER JOIN temp_table2 ON taxstatus.TidAccepted = temp_table2.TID
WHERE (taxstatus.taxauthid = taxaAuthId);

#Output final results
SELECT DISTINCT temp_table1.sciname, temp_table1.tid
FROM temp_table1 WHERE temp_table1.sciname NOT LIKE CONCAT(targetName,'%')
ORDER BY temp_table1.sciname;

END//


-- =============================================
-- Required for Dynamic Map Idenitification Key
-- Used in /ident/dynamickeymap.php
-- =============================================

DELIMITER //
CREATE PROCEDURE `DynamicKey`(IN lat DOUBLE,IN lng DOUBLE,IN radius DOUBLE)
BEGIN

DECLARE speccnt DOUBLE DEFAULT 0;
DECLARE sppcnt DOUBLE;

DECLARE latradius DOUBLE;
DECLARE lngradius DOUBLE;
DECLARE lat1 DOUBLE;
DECLARE lat2 DOUBLE;
DECLARE lng1 DOUBLE;
DECLARE lng2 DOUBLE;
DECLARE dynpk INT;
DECLARE loopCnt INT DEFAULT 0;

WHILE speccnt < 2500 AND loopCnt < 10 DO
        SET latradius = radius / 69.1;
        SET lngradius = cos(lat/57.3)*(radius/69.1);
        SET lat1 = lat - latradius;
        SET lat2 = lat + latradius;
        SET lng1 = lng - lngradius;
        SET lng2 = lng + lngradius;

        SELECT count(o.tid) INTO speccnt FROM omoccurgeoindex o
        WHERE (o.DecimalLatitude BETWEEN lat1 AND lat2) AND (o.DecimalLongitude BETWEEN lng1 AND lng2);
        SET radius = radius + 4;
        SET loopCnt = loopCnt + 1;
END WHILE;

INSERT INTO fmdynamicchecklists(name,details,expiration)
SELECT CONCAT(lat,", ",lng,"; within ",radius," miles"), CONCAT(lat,", ",lng,"; within ",radius," miles"),DATE_ADD(CURDATE(),INTERVAL 5 DAY);

SELECT LAST_INSERT_ID() INTO dynpk;

INSERT INTO fmdyncltaxalink (dynclid, tid)
SELECT DISTINCT dynpk, IF(t.rankid=220,t.tid,ts.parenttid) as tid
FROM (omoccurgeoindex o INNER JOIN taxstatus ts ON o.tid = ts.tid)
INNER JOIN taxa t ON ts.tidaccepted = t.tid
WHERE (t.rankid >= 220) AND (ts.taxauthid = 1) AND (o.DecimalLatitude BETWEEN lat1 AND lat2) AND
(o.DecimalLongitude BETWEEN lng1 AND lng2);

SELECT dynpk;

END//


-- =============================================
-- Cleanup and transfer scripts used for updating a collection 
-- Used in /collections/admin/datauploader.php
-- =============================================

DELIMITER //
CREATE PROCEDURE `TransferUploads`(IN targetCollId INT, IN doFullReplace boolean)
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

         UPDATE uploadspectemp s SET s.stateprovince = "Arizona" WHERE s.stateprovince = "AZ";
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
         UPDATE (uploadspectemp s INNER JOIN taxa t ON s.genus = t.unitname1 AND s.specificepithet = t.unitname2 AND s.InfraSpecificEpithet = t.unitname3)
             INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted
             SET s.TidInterpreted = t.tid
             WHERE ts.taxauthid = 1 AND s.TidInterpreted IS NULL;
         UPDATE uploadspectemp s INNER JOIN taxa t ON s.genus = t.unitname1 AND s.specificepithet = t.unitname2 AND s.InfraSpecificEpithet = t.unitname3
             SET s.TidInterpreted = t.tid WHERE s.TidInterpreted IS NULL;

         #Filling missing families
         UPDATE uploadspectemp u INNER JOIN taxstatus ts ON u.tidinterpreted = ts.tid
             SET u.family = ts.family
             WHERE ts.taxauthid = 1 AND ts.family <> "" AND ts.family IS NOT NULL AND (u.family IS NULL OR u.family = "");

         UPDATE ((uploadspectemp u INNER JOIN taxa t ON u.family = t.sciname)
             INNER JOIN taxstatus ts ON t.tid = ts.tid)
             INNER JOIN taxa t2 ON ts.tidaccepted = t2.tid
             SET u.family = t2.sciname
             WHERE ts.taxauthid = 1 AND t.rankid = 140 AND ts.tid <> ts.tidaccepted;

         UPDATE uploadspectemp u INNER JOIN taxa t ON u.genus = t.unitname1
             inner join taxstatus ts on t.tid = ts.tid
             SET u.family = ts.family
             WHERE t.rankid = 180 and ts.taxauthid = 1 AND ts.family <> "" AND ts.family IS NOT NULL AND (u.family IS NULL OR u.family = "");

         #Filling missing Authors
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

         #Arizona lat/longs
         UPDATE uploadspectemp SET DecimalLatitude = \N, DecimalLongitude = \N WHERE (StateProvince = "Arizona" OR StateProvince = "AZ")
            AND (DecimalLatitude > 37.2 OR DecimalLatitude < 31.2 OR DecimalLongitude > -108.9 OR DecimalLongitude < -115.2);

         #NM lat/longs
         UPDATE uploadspectemp SET DecimalLatitude = \N, DecimalLongitude = \N WHERE (StateProvince = "New Mexico" OR StateProvince = "NM")
            AND (DecimalLatitude > 37.2 OR DecimalLatitude < 31.2 OR DecimalLongitude > -102.9 OR DecimalLongitude < -109.2);

         #California lat/longs
         UPDATE uploadspectemp SET DecimalLatitude = \N, DecimalLongitude = \N WHERE (StateProvince = "California" OR StateProvince = "CA")
            AND (DecimalLatitude > 42.1 OR DecimalLatitude < 32.4 OR DecimalLongitude > -114.0 OR DecimalLongitude < -124.5);

         #UT lat/longs
         UPDATE uploadspectemp SET DecimalLatitude = \N, DecimalLongitude = \N WHERE (StateProvince = "Utah" OR StateProvince = "UT")
            AND (DecimalLatitude > 42.1 OR DecimalLatitude < 36.9 OR DecimalLongitude > -108.9 OR DecimalLongitude < -114.2);

         #Mexico lat/longs
         UPDATE uploadspectemp SET DecimalLatitude = \N, DecimalLongitude = \N WHERE (country = "Mexico" OR StateProvince = "Sonora")
            AND ((DecimalLatitude NOT BETWEEN 12 AND 33) OR (DecimalLongitude NOT BETWEEN -121 AND -86));

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
        o.verbatimEventDate = u.verbatimEventDate, o.habitat = u.habitat, o.fieldNotes = u.fieldNotes, o.occurrenceRemarks = u.occurrenceRemarks,
        o.associatedOccurrences = u.associatedOccurrences, o.associatedTaxa = u.associatedTaxa, o.dynamicProperties = u.dynamicProperties,
        o.reproductiveCondition = u.reproductiveCondition, o.cultivationStatus = u.cultivationStatus, o.establishmentMeans = u.establishmentMeans,
        o.country = u.country, o.stateProvince = u.stateProvince, o.county = u.county, o.municipality = u.municipality, o.locality = u.locality,
        o.localitySecurity = u.localitySecurity, o.decimalLatitude = u.decimalLatitude, o.decimalLongitude = u.decimalLongitude,
        o.geodeticDatum = u.geodeticDatum, o.coordinateUncertaintyInMeters = u.coordinateUncertaintyInMeters,
        o.coordinatePrecision = u.coordinatePrecision, o.locationRemarks = u.locationRemarks, o.verbatimCoordinates = u.verbatimCoordinates,
        o.verbatimCoordinateSystem = u.verbatimCoordinateSystem, o.georeferencedBy = u.georeferencedBy, o.georeferenceProtocol = u.georeferenceProtocol,
        o.georeferenceSources = u.georeferenceSources, o.georeferenceVerificationStatus = u.georeferenceVerificationStatus,
        o.georeferenceRemarks = u.georeferenceRemarks, o.minimumElevationInMeters = u.minimumElevationInMeters,
        o.maximumElevationInMeters = u.maximumElevationInMeters, o.verbatimElevation = u.verbatimElevation,
        o.previousIdentifications = u.previousIdentifications, o.disposition = u.disposition, o.modified = u.modified, o.language = u.language
        WHERE u.collid = targetCollId;


        #Insert new records
        INSERT INTO omoccurrences (collid, dbpk, basisOfRecord, occurrenceID, catalogNumber, otherCatalogNumbers, ownerInstitutionCode, family, scientificName,
        sciname, genus, institutionID, collectionID, specificEpithet, datasetID, taxonRank, infraspecificEpithet, institutionCode, collectionCode,
        scientificNameAuthorship, taxonRemarks, identifiedBy, dateIdentified, identificationReferences, identificationRemarks,
        identificationQualifier, typeStatus, recordedBy, recordNumber, CollectorFamilyName, CollectorInitials, associatedCollectors,
        eventDate, Year, Month, Day, startDayOfYear, endDayOfYear, verbatimEventDate, habitat, fieldNotes, occurrenceRemarks,
        associatedOccurrences, associatedTaxa, dynamicProperties, reproductiveCondition, cultivationStatus, establishmentMeans, country, stateProvince,
        county, municipality, locality, localitySecurity, decimalLatitude, decimalLongitude, geodeticDatum, coordinateUncertaintyInMeters,
        coordinatePrecision, locationRemarks, verbatimCoordinates, verbatimCoordinateSystem, georeferencedBy, georeferenceProtocol,
        georeferenceSources, georeferenceVerificationStatus, georeferenceRemarks, minimumElevationInMeters, maximumElevationInMeters,
        verbatimElevation, previousIdentifications, disposition, modified, language )
        SELECT u.collid, u.dbpk, u.basisOfRecord, u.occurrenceID, u.catalogNumber, u.otherCatalogNumbers, u.ownerInstitutionCode,
        u.family, u.scientificName,
        u.sciname, u.genus, u.institutionID, u.collectionID, u.specificEpithet, u.datasetID, u.taxonRank, u.infraspecificEpithet,
        u.institutionCode, u.collectionCode, u.scientificNameAuthorship, u.taxonRemarks, u.identifiedBy, u.dateIdentified,
        u.identificationReferences, u.identificationRemarks, u.identificationQualifier, u.typeStatus, u.recordedBy, u.recordNumber,
        u.CollectorFamilyName, u.CollectorInitials, u.associatedCollectors, u.eventDate, u.Year, u.Month, u.Day, u.startDayOfYear,
        u.endDayOfYear, u.verbatimEventDate, u.habitat, u.fieldNotes, u.occurrenceRemarks, u.associatedOccurrences, u.associatedTaxa,
        u.dynamicProperties, u.reproductiveCondition, u.cultivationStatus, u.establishmentMeans, u.country, u.stateProvince, u.county,
        u.municipality, u.locality, u.localitySecurity, u.decimalLatitude, u.decimalLongitude, u.geodeticDatum, u.coordinateUncertaintyInMeters,
        u.coordinatePrecision, u.locationRemarks, u.verbatimCoordinates, u.verbatimCoordinateSystem, u.georeferencedBy, u.georeferenceProtocol,
        u.georeferenceSources, u.georeferenceVerificationStatus, u.georeferenceRemarks, u.minimumElevationInMeters, u.maximumElevationInMeters,
        u.verbatimElevation, u.previousIdentifications, u.disposition, u.modified, u.language
        FROM uploadspectemp u
        WHERE u.occid Is Null AND u.collid = targetCollId;


        #if replace all, delete record where the datelastmodified has not been changed
        IF doFullReplace = 1 THEN
           DELETE IGNORE FROM omoccurrences
           WHERE collid = targetCollId AND DateLastModified < DATE_SUB(CURDATE(), INTERVAL 1 DAY);
        END IF;

        DELETE FROM uploadspectemp WHERE collid = targetCollId;

        #Update stats
        UPDATE omcollectionstats SET uploaddate = NOW() WHERE collid = targetCollId;
        CALL UpdateCollectionStats(targetCollId);


       #transfer images
       UPDATE uploadimagetemp u INNER JOIN omoccurrences o ON u.collid = o.collid AND u.dbpk = o.dbpk
          SET u.occid = o.occid
          WHERE u.collid = targetCollId AND u.occid IS NULL;

       UPDATE uploadimagetemp u INNER JOIN omoccurrences o ON u.specimengui = o.occurrenceid
          SET u.occid = o.occid
          WHERE u.collid = targetCollId AND u.occid = null;

       UPDATE uploadimagetemp u INNER JOIN omoccurrences o ON u.occid = o.occid
          SET u.tid = o.tidinterpreted
          WHERE u.collid = targetCollId AND u.tid IS NULL AND o.tidinterpreted IS NOT NULL;

       UPDATE (uploadimagetemp u INNER JOIN omoccurrences o ON u.occid = o.occid)
          INNER JOIN taxa t ON o.genus = t.unitname1 AND o.specificepithet = t.unitname2
          SET u.tid = t.tid
          WHERE u.collid = targetCollId AND o.genus IS NOT NULL AND o.specificepithet IS NOT NULL AND t.rankid = 220 AND u.tid IS NULL;

       UPDATE (uploadimagetemp u INNER JOIN omoccurrences o ON u.occid = o.occid)
          INNER JOIN taxa t ON o.genus = t.sciname
          SET u.tid = t.tid
          WHERE u.collid = targetCollId AND u.tid IS NULL AND o.genus IS NOT NULL AND t.rankid = 180;

       UPDATE (uploadimagetemp u INNER JOIN omoccurrences o ON u.occid = o.occid)
          INNER JOIN taxa t ON o.family = t.sciname
          SET u.tid = t.tid
          WHERE u.collid = targetCollId AND u.tid IS NULL AND o.family IS NOT NULL AND t.rankid = 140;

       UPDATE uploadimagetemp ui
          SET ui.tid = 5571
          WHERE ui.collid = targetCollId AND ui.tid IS NULL;

       INSERT IGNORE INTO images(tid, url, thumbnailurl, originalurl, photographer,
         photographeruid, imagetype, caption, owner, occid, notes, sortsequence)
         SELECT tid, url, thumbnailurl, originalurl, photographer, photographeruid,
         imagetype, caption, owner, occid, notes, IFNULL(sortsequence,50) as sortseq
         FROM uploadimagetemp ui
         WHERE ui.collid = targetCollId AND ui.tid IS NOT NULL AND ui.url IS NOT NULL AND ui.occid IS NOT NULL;

        DELETE FROM uploadimagetemp WHERE collid = targetCollId;

    END IF;

END//


-- =============================================
-- Update collection statistics after a collection has been updated
-- Used in /collections/admin/datauploader.php
-- =============================================

DELIMITER //
CREATE PROCEDURE `UpdateCollectionStats`(IN collectionid INT)
BEGIN

UPDATE omoccurrences o SET o.genus = LEFT(o.sciname,INSTR(o.sciname, " "))
WHERE o.genus IS NULL AND o.sciname LIKE "% %";

UPDATE omoccurrences o SET o.genus = o.sciname
WHERE o.genus IS NULL AND o.sciname NOT LIKE "%aceae" and o.sciname <> "Plantae";

UPDATE omoccurrences o
SET o.specificepithet = SUBSTRING(o.sciname, LENGTH(o.genus)+1, LOCATE(" ",o.sciname,LENGTH(o.genus)+1)-LENGTH(o.genus)-1)
WHERE o.sciname LIKE "% % %" AND o.sciname NOT LIKE "% x %" AND o.specificepithet IS NULL;

UPDATE omoccurrences o SET o.specificepithet = SUBSTRING(o.sciname, LENGTH(o.genus)+1)
WHERE o.sciname LIKE "% %" AND o.sciname NOT LIKE "% x %" AND o.specificepithet IS NULL;

UPDATE omoccurrences o SET o.family = o.sciname
WHERE o.family IS NULL AND o.sciname LIKE "%aceae";

UPDATE omoccurrences o SET o.family = o.sciname
WHERE o.family IS NULL AND o.sciname LIKE "%aceae";

#Update tidinterpreted field
UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname
SET o.TidInterpreted = t.tid WHERE o.TidInterpreted IS NULL;

#Fill in family where family is null
UPDATE omoccurrences o INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid
   SET o.family = ts.family
   WHERE ts.taxauthid = 1 AND ts.family <> "" AND ts.family IS NOT NULL AND (o.family IS NULL OR o.family = "");

UPDATE (omoccurrences o INNER JOIN taxa t ON o.genus = t.sciname)
INNER JOIN taxstatus ts ON t.tid = ts.tid
SET o.family = ts.family
WHERE o.family IS NULL AND t.rankid = 180 AND ts.taxauthid = 1;

#Fill in author where author is null
UPDATE omoccurrences o INNER JOIN taxa t ON o.tidinterpreted = t.tid
SET o.scientificNameAuthorship = t.author
WHERE o.scientificNameAuthorship IS NULL and t.author is not null;

UPDATE omcollectionstats cs
SET cs.recordcnt = (SELECT Count(o.occid) FROM omoccurrences o GROUP BY o.collid HAVING (o.collid = collectionid))
WHERE cs.collid = collectionid;

#family count
UPDATE omcollectionstats cs
SET cs.familycnt = (SELECT count(innert.Family) FROM (SELECT DISTINCT o.Family FROM omoccurrences o WHERE (o.collid = collectionid) AND (o.Family Is Not Null)) innert)
WHERE cs.collid = collectionid;

#genus count
UPDATE omcollectionstats cs
SET cs.genuscnt = (SELECT count(innert.genus) FROM (SELECT DISTINCT o.genus FROM omoccurrences o WHERE (o.collid = collectionid) AND (o.genus Is Not Null)) innert)
WHERE cs.collid = collectionid;

#species count
UPDATE omcollectionstats cs
SET cs.speciescnt = (SELECT COUNT(innert.sciname) FROM (SELECT DISTINCT CONCAT_WS(" ", o.genus, o.specificepithet) AS sciname FROM omoccurrences o WHERE (o.collid = collectionid) AND (o.specificepithet Is Not Null)) innert)
WHERE cs.collid = collectionid;

#georeference count
UPDATE omcollectionstats cs
SET cs.georefcnt = (SELECT Count(o.occid) FROM omoccurrences o WHERE (o.DecimalLatitude Is Not Null) AND (o.DecimalLongitude Is Not Null) AND (o.CollID = collectionid))
WHERE cs.collid = collectionid;

END//


-- =============================================
-- Character State Inheritance: Walks down character data to child taxa
-- =============================================

DELIMITER //
CREATE PROCEDURE `BuildInheritance`()
BEGIN

INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited )
SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT,
d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent
FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID)
INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid)
INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID)
INNER JOIN taxa t2 ON ts2.tid = t2.tid)
LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID)
WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.tid = ts2.tidaccepted)
AND (t2.RankId = 180) And (d2.CID Is Null);

INSERT INTO kmdescr ( TID, CID, CS, Modifier, X, TXT, Seq, Notes, Inherited )
SELECT DISTINCT t2.TID, d1.CID, d1.CS, d1.Modifier, d1.X, d1.TXT,
d1.Seq, d1.Notes, IFNULL(d1.Inherited,t1.SciName) AS parent
FROM ((((taxa AS t1 INNER JOIN kmdescr d1 ON t1.TID = d1.TID)
INNER JOIN taxstatus ts1 ON d1.TID = ts1.tid)
INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.ParentTID)
INNER JOIN taxa t2 ON ts2.tid = t2.tid)
LEFT JOIN kmdescr d2 ON (d1.CID = d2.CID) AND (t2.TID = d2.TID)
WHERE (ts1.taxauthid = 1) AND (ts2.taxauthid = 1) AND (ts2.tid = ts2.tidaccepted)
AND (t2.RankId = 220) And (d2.CID Is Null);

END//


-- =============================================
-- Used to batch update new taxa and build taxonomic hierarchy
-- =============================================

DELIMITER //
CREATE PROCEDURE `UploadTaxa`(IN defaultParent INT)
BEGIN

#All taxa with a rank of family or higher (rankid <= 140) must have a rank setting
#All taxa with a rank of family or higher (rankid <= 140) must have parentstr or parent will be set as defaultparent tid

DECLARE startLoadCnt INT DEFAULT -1;
DECLARE endLoadCnt INT DEFAULT 0;

#Do some cleaning in AcceptedStr column
UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr," ssp. "," subsp. ") WHERE AcceptedStr like "% ssp. %";
UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr," var "," var. ") WHERE AcceptedStr like "% var %";
UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr," subsp "," subsp. ") WHERE AcceptedStr like "% subsp %";
UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr," sp.","") WHERE AcceptedStr like "% sp.";
UPDATE uploadtaxa SET AcceptedStr = trim(AcceptedStr) WHERE AcceptedStr like "% " OR AcceptedStr like " %";
UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr,"  "," ") WHERE AcceptedStr like "%  %";

#Insert into UploadTaxa all accepted taxa (AcceptedStr) that are not all ready in scinameinput
INSERT INTO uploadtaxa(scinameinput)
SELECT DISTINCT u.AcceptedStr
FROM uploadtaxa u LEFT JOIN uploadtaxa ul2 ON u.AcceptedStr = ul2.scinameinput
WHERE u.AcceptedStr IS NOT NULL AND ul2.scinameinput IS NULL;

#Do some cleaning
UPDATE uploadtaxa SET sciname = replace(sciname," ssp. "," subsp. ") WHERE sciname like "% ssp. %";
UPDATE uploadtaxa SET sciname = replace(sciname," var "," var. ") WHERE sciname like "% var %";
UPDATE uploadtaxa SET sciname = replace(sciname," subsp "," subsp. ") WHERE sciname like "% subsp %";
UPDATE uploadtaxa SET sciname = replace(sciname," cf. "," ") WHERE sciname like "% cf. %";
UPDATE uploadtaxa SET sciname = replace(sciname," cf "," ") WHERE sciname like "% cf %";
UPDATE uploadtaxa SET sciname = REPLACE(sciname," aff. "," ") WHERE sciname like "% aff. %";
UPDATE uploadtaxa SET sciname = REPLACE(sciname," aff "," ") WHERE sciname like "% aff %";
UPDATE uploadtaxa SET sciname = replace(sciname," sp.","") WHERE sciname like "% sp.";
UPDATE uploadtaxa SET sciname = replace(sciname," sp","") WHERE sciname like "% sp";
UPDATE uploadtaxa SET sciname = trim(sciname) WHERE sciname like "% " OR sciname like " %";
UPDATE uploadtaxa SET sciname = replace(sciname,"  "," ") WHERE sciname like "%  %";

UPDATE uploadtaxa SET scinameinput = replace(scinameinput," ssp. "," subsp. ") WHERE scinameinput like "% ssp. %";
UPDATE uploadtaxa SET scinameinput = replace(scinameinput," var "," var. ") WHERE scinameinput like "% var %";
UPDATE uploadtaxa SET scinameinput = replace(scinameinput," subsp "," subsp. ") WHERE scinameinput like "% subsp %";
UPDATE uploadtaxa SET scinameinput = replace(scinameinput," cf. "," ") WHERE scinameinput like "% cf. %";
UPDATE uploadtaxa SET scinameinput = replace(scinameinput," cf "," ") WHERE scinameinput like "% cf %";
UPDATE uploadtaxa SET scinameinput = REPLACE(scinameinput," aff. "," ") WHERE scinameinput like "% aff. %";
UPDATE uploadtaxa SET scinameinput = REPLACE(scinameinput," aff "," ") WHERE scinameinput like "% aff %";
UPDATE uploadtaxa SET scinameinput = replace(scinameinput," sp.","") WHERE scinameinput like "% sp.";
UPDATE uploadtaxa SET scinameinput = replace(scinameinput," sp","") WHERE scinameinput like "% sp";
UPDATE uploadtaxa SET scinameinput = trim(scinameinput) WHERE scinameinput like "% " OR scinameinput like " %";
UPDATE uploadtaxa SET scinameinput = replace(scinameinput,"  "," ") WHERE scinameinput like "%  %";

#Parse name
UPDATE uploadtaxa
SET unitind1 = "x"
WHERE unitind1 IS NULL AND scinameinput LIKE "x %";

UPDATE uploadtaxa
SET unitind2 = "x"
WHERE unitind2 IS NULL AND scinameinput LIKE "% x %" AND scinameinput NOT LIKE "% % x %";

UPDATE uploadtaxa
SET unitname1 = TRIM(substring(scinameinput,3,LOCATE(" ",scinameinput,3)-3))
WHERE unitname1 IS NULL and scinameinput LIKE "x %";

UPDATE uploadtaxa
SET unitname1 = TRIM(substring(scinameinput,1,LOCATE(" ",CONCAT(scinameinput," "))))
WHERE unitname1 IS NULL;

UPDATE uploadtaxa
SET unitname2 = TRIM(substring(scinameinput,LENGTH(CONCAT_WS(" ",unitind1, unitname1, unitind2))+2,LOCATE(" ",CONCAT(scinameinput," "),LENGTH(CONCAT_WS(" ",unitind1, unitname1, unitind2))+2)-(LENGTH(CONCAT_WS(" ",unitind1, unitname1, unitind2))+2)))
WHERE unitname2 IS NULL;

UPDATE uploadtaxa
SET unitind3 = "f.", rankid = 260
WHERE unitind3 IS NULL AND (scinameinput LIKE "% f. %" OR scinameinput LIKE "% forma %");

UPDATE uploadtaxa
SET unitind3 = "var.", rankid = 240
WHERE unitind3 IS NULL AND scinameinput LIKE "% var. %";

UPDATE uploadtaxa
SET unitind3 = "subsp.", rankid = 230
WHERE unitind3 IS NULL AND (scinameinput LIKE "% subsp. %" OR scinameinput LIKE "% ssp. %");

UPDATE uploadtaxa
SET sciname = replace(sciname," ssp. "," subsp. ")
WHERE sciname LIKE "% ssp. %";

UPDATE uploadtaxa
SET unitname3 = TRIM(SUBSTRING(scinameinput,LENGTH(CONCAT_WS(" ",unitind1, unitname1, unitind2, unitname2, unitind3))+2,
LOCATE(" ",CONCAT(scinameinput," "),LENGTH(CONCAT_WS(" ",unitind1, unitname1, unitind2, unitname2, unitind3))+2)-LENGTH(CONCAT_WS(" ",unitind1, unitname1, unitind2, unitname2, unitind3))-2))
WHERE unitname3 IS NULL AND rankid > 220 AND scinameinput NOT LIKE "% subsp. %" AND scinameinput NOT LIKE "% forma %";

UPDATE uploadtaxa
SET unitname3 = TRIM(SUBSTRING(scinameinput,LENGTH(CONCAT_WS(" ",unitind1, unitname1, unitind2, unitname2, unitind3))+4,
LOCATE(" ",CONCAT(scinameinput," "),LENGTH(CONCAT_WS(" ",unitind1, unitname1, unitind2, unitname2, unitind3))+4)-LENGTH(CONCAT_WS(" ",unitind1, unitname1, unitind2, unitname2, unitind3))-4))
WHERE unitname3 IS NULL AND rankid > 220 AND (scinameinput LIKE "% subsp. %" OR scinameinput LIKE "% forma %");

UPDATE uploadtaxa
SET sciname = CONCAT_WS(" ",unitind1, unitname1, unitind2, unitname2, unitind3, unitname3)
WHERE sciname IS NULL;

#Delete taxa in uploadtaxa table that are already in taxa table
DELETE ut.* FROM uploadtaxa ut INNER JOIN taxa t ON ut.sciname = t.sciname;

#Set rankid
UPDATE uploadtaxa
SET rankid = 140
WHERE rankid IS NULL AND (sciname like "%aceae" || sciname like "%idae");

UPDATE uploadtaxa
SET rankid = 220
WHERE rankid IS NULL AND unitname1 is not null AND unitname2 is not null;

UPDATE uploadtaxa
SET rankid = 180
WHERE rankid IS NULL AND unitname1 is not null AND unitname2 is null;

#Author
UPDATE uploadtaxa
SET author = TRIM(SUBSTRING(scinameinput,LENGTH(sciname)+1))
WHERE (author IS NULL) AND rankid <= 220;

UPDATE uploadtaxa
SET author = TRIM(SUBSTRING(scinameinput,LOCATE(unitname3,scinameinput)+LENGTH(unitname3)))
WHERE (author IS NULL) AND rankid > 220;

#Set family
UPDATE (uploadtaxa ut INNER JOIN taxa t ON ut.unitname1 = t.sciname)
INNER JOIN taxstatus ts ON t.tid = ts.tid
SET ut.family = ts.family
WHERE ts.taxauthid = 1 AND t.rankid = 180 AND ts.family is not null AND (ut.family IS NULL OR ts.family <> ut.family);

UPDATE ((uploadtaxa ut INNER JOIN taxa t ON ut.family = t.sciname)
INNER JOIN taxstatus ts ON t.tid = ts.tid)
INNER JOIN taxa t2 ON ts.tidaccepted = t2.tid
SET ut.family = t2.sciname
WHERE ts.taxauthid = 1 AND t.rankid = 140 AND ts.tid <> ts.tidaccepted;

#Set upper taxonomy
UPDATE (uploadtaxa ut INNER JOIN taxa t ON ut.unitname1 = t.sciname)
INNER JOIN taxstatus ts ON t.tid = ts.tid
SET ut.uppertaxonomy = ts.uppertaxonomy
WHERE ts.taxauthid = 1 AND t.rankid = 180 AND ts.uppertaxonomy is not null AND (ut.uppertaxonomy IS NULL);

UPDATE (uploadtaxa ut INNER JOIN taxa t ON ut.family = t.sciname)
INNER JOIN taxstatus ts ON t.tid = ts.tid
SET ut.uppertaxonomy = ts.uppertaxonomy
WHERE ts.taxauthid = 1 AND t.rankid = 140 AND ts.uppertaxonomy is not null AND (ut.uppertaxonomy IS NULL);

#Set kingdom
UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.unitname1 = t.sciname
SET ut.kingdomid = t.kingdomid
WHERE t.rankid = 180 AND t.kingdomid is not null AND ut.kingdomid IS NULL;

UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.family = t.sciname
SET ut.kingdomid = t.kingdomid
WHERE t.rankid = 140 AND t.kingdomid is not null AND ut.kingdomid IS NULL;

#ITIS default kingdom id is 3 for plants
UPDATE uploadtaxa ut SET ut.kingdomid = 3 WHERE ut.kingdomid IS NULL;

#Set parent string
UPDATE uploadtaxa
SET parentstr = CONCAT_WS(" ", unitname1, unitname2)
WHERE parentstr IS NULL AND rankid > 220;

UPDATE uploadtaxa
SET parentstr = unitname1
WHERE parentstr IS NULL AND rankid = 220;

UPDATE uploadtaxa
SET parentstr = family
WHERE parentstr IS NULL AND parentstr <> "" AND rankid = 180;

UPDATE uploadtaxa
SET parenttid = defaultParent
WHERE parentstr IS NULL OR parentstr = "" OR (rankid <= 140 AND parenttid IS NULL);

UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname
SET parenttid = t.tid
WHERE parenttid IS NULL;

#Insert into uploadtaxa parents that are not in taxa table
##Load parents (species) of infraspecific taxa that are not already in uploadtaxa
INSERT IGNORE INTO uploadtaxa (scinameinput, SciName, KingdomID, uppertaxonomy, family, RankId, UnitName1, UnitName2, parentstr, Source)
SELECT DISTINCT ut.parentstr, ut.parentstr, ut.kingdomid, ut.uppertaxonomy, ut.family, 220 as r, ut.unitname1, ut.unitname2, ut.unitname1, ut.source
FROM uploadtaxa ut LEFT JOIN uploadtaxa ut2 ON ut.parentstr = ut2.sciname
WHERE ut.parentstr IS NOT NULL AND ut.parenttid IS NULL AND ut.rankid > 220 AND ut2.sciname IS NULL;

UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname
SET parenttid = t.tid
WHERE parenttid IS NULL;

##Load parents (genera) of species level taxa that are not already in uploadtaxa
INSERT IGNORE INTO uploadtaxa (scinameinput, SciName, KingdomID, uppertaxonomy, family, RankId, UnitName1, parentstr, Source)
SELECT DISTINCT ut.parentstr, ut.parentstr, ut.kingdomid, ut.uppertaxonomy, ut.family, 180 as r, ut.unitname1, ut.family, ut.source
FROM uploadtaxa ut LEFT JOIN uploadtaxa ut2 ON ut.parentstr = ut2.sciname
WHERE ut.parentstr IS NOT NULL AND ut.parenttid IS NULL AND ut.family IS NOT NULL AND ut.rankid = 220 AND ut2.sciname IS NULL;

UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname
SET parenttid = t.tid
WHERE parenttid IS NULL;

##Load parents (family) of genera level taxa that are not already in uploadtaxa; use defaultparent
INSERT IGNORE INTO uploadtaxa (scinameinput, SciName, KingdomID, uppertaxonomy, family, RankId, UnitName1, parentstr, parenttid, Source)
SELECT DISTINCT ut.parentstr, ut.parentstr, ut.kingdomid, ut.uppertaxonomy, ut.family, 140 as r, ut.parentstr, defaultParent, defaultParent, ut.source
FROM uploadtaxa ut LEFT JOIN uploadtaxa ut2 ON ut.parentstr = ut2.sciname
WHERE ut.parentstr IS NOT NULL AND ut.parenttid IS NULL AND ut.rankid = 180 AND ut2.sciname IS NULL;

UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname
SET parenttid = t.tid
WHERE parenttid IS NULL;


##Load data
#Loop through until no more names are added to taxa from uploadtaxa
WHILE endLoadCnt > 0 OR startLoadCnt <> endLoadCnt DO

        SELECT COUNT(*) INTO startLoadCnt FROM uploadtaxa;

        #Load taxa that have parent tids
        INSERT INTO taxa ( SciName, KingdomID, RankId, UnitInd1, UnitName1, UnitInd2, UnitName2, UnitInd3, UnitName3, Author, Source, Notes )
        SELECT DISTINCT ut.SciName, ut.KingdomID, ut.RankId, ut.UnitInd1, ut.UnitName1, ut.UnitInd2, ut.UnitName2, ut.UnitInd3,
        ut.UnitName3, ut.Author, ut.Source, ut.Notes
        FROM uploadtaxa AS ut
        WHERE (ut.TID Is Null AND parenttid IS NOT NULL );

        #Grab tids for newly loaded taxa
        UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.sciname = t.sciname
        SET ut.tid = t.tid
        WHERE ut.tid IS NULL;

        #Load info for new taxa into taxstatus table
        UPDATE uploadtaxa
        SET tidaccepted = tid
        WHERE (acceptance = 1 OR acceptance IS NULL) AND tid IS NOT NULL;

        UPDATE uploadtaxa ut INNER JOIN taxa t ON ut.acceptedstr = t.sciname
        SET ut.tidaccepted = t.tid
        WHERE ut.acceptance = 0 AND ut.tidaccepted IS NULL AND ut.acceptedstr IS NOT NULL;

        INSERT INTO taxstatus ( TID, TidAccepted, taxauthid, ParentTid, Family, UpperTaxonomy, UnacceptabilityReason )
        SELECT DISTINCT ut.TID, ut.TidAccepted, 1 AS taxauthid, ut.ParentTid, ut.Family, ut.UpperTaxonomy, ut.UnacceptabilityReason
        FROM uploadtaxa AS ut
        WHERE (ut.TID Is Not Null AND ut.TidAccepted IS NOT NULL);

        #Delete taxa added from UploadTaxa table
        DELETE FROM uploadtaxa
        WHERE tid is not null AND tidaccepted IS NOT NULL;

        UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname
        SET parenttid = t.tid
        WHERE parenttid IS NULL;

        SELECT COUNT(*) INTO endLoadCnt FROM uploadtaxa;

END WHILE;
END//


-- =============================================
-- General Stored Procedures
-- =============================================

DELIMITER //
CREATE PROCEDURE `GeneralMaintenance`()
BEGIN
  #Make sure that all vernacular groups (tid, language) have at least one record with a sort order of 1
  UPDATE taxavernaculars vern INNER JOIN (SELECT v.TID, v.Language,
  SUBSTRING_INDEX(GROUP_CONCAT(v.VernacularName ORDER BY v.SortSequence),",",1) as VernacularName
  FROM taxavernaculars v
  GROUP BY v.TID, v.Language)
  inntab ON vern.TID = inntab.tid AND vern.VernacularName = inntab.VernacularName AND vern.Language = inntab.Language
  SET vern.sortsequence = 1
  WHERE vern.sortsequence > 1;

  #Make sure all image groups (tid, url) have at least one record with a sort order of 1
  UPDATE images timages INNER JOIN (SELECT ti.tid,
  SUBSTRING_INDEX(GROUP_CONCAT(ti.url ORDER BY ti.SortSequence),",",1) as innerurl
  FROM images ti
  GROUP BY ti.TID) inti ON timages.TID = inti.tid AND timages.url = inti.innerurl
  SET timages.sortsequence = 1
  WHERE timages.sortsequence > 1;

END//


-- =============================================
-- General Occurrence Cleanup
-- =============================================

DELIMITER //
CREATE PROCEDURE `OccurrenceCleanup`()
BEGIN
        UPDATE omoccurrences u
        SET u.year = YEAR(u.eventDate)
        WHERE u.eventDate IS NOT NULL AND (u.year IS NULL OR u.year = 0);

        UPDATE omoccurrences u
        SET u.month = MONTH(u.eventDate)
        WHERE (u.month IS NULL OR u.month = 0) AND u.eventDate IS NOT NULL;

        UPDATE omoccurrences u
        SET u.day = DAY(u.eventDate)
        WHERE (u.day IS NULL OR u.day = 0) AND u.eventDate IS NOT NULL;

        UPDATE omoccurrences u
        SET u.startDayOfYear = DAYOFYEAR(u.eventDate)
        WHERE u.startDayOfYear IS NULL AND u.eventDate IS NOT NULL;

         #Update LocalitySecurity
         UPDATE taxa t INNER JOIN omoccurrences u ON t.SciName = u.SciName
         SET u.LocalitySecurity = t.SecurityStatus
         WHERE (t.SecurityStatus > 0) AND (u.LocalitySecurity = 0 OR u.LocalitySecurity IS NULL);

         #standardize some of the fields
         UPDATE omoccurrences s SET s.sciname = replace(s.sciname," ssp. "," subsp. ") WHERE s.sciname like "% ssp. %";
         UPDATE omoccurrences s SET s.sciname = replace(s.sciname," var "," var. ") WHERE s.sciname like "% var %";
         UPDATE omoccurrences s SET s.sciname = replace(s.sciname," subsp "," subsp. ") WHERE s.sciname like "% subsp %";
         UPDATE omoccurrences s SET s.sciname = replace(s.sciname," cf. "," ") WHERE s.sciname like "% cf. %";
         UPDATE omoccurrences s SET s.sciname = replace(s.sciname," cf "," ") WHERE s.sciname like "% cf %";
         UPDATE omoccurrences s SET s.sciname = REPLACE(s.sciname," aff. "," "), tidinterpreted = null WHERE sciname like "% aff. %";
         UPDATE omoccurrences s SET s.sciname = REPLACE(s.sciname," aff "," "), tidinterpreted = null WHERE sciname like "% aff %";

         UPDATE omoccurrences s SET s.country = "USA" WHERE s.country = "U.S.A.";
         UPDATE omoccurrences s SET s.country = "USA" WHERE s.country = "U.S.";
         UPDATE omoccurrences s SET s.country = "USA" WHERE s.country = "US";
         UPDATE omoccurrences s SET s.country = "USA" WHERE s.country = "United States";
         UPDATE omoccurrences s SET s.country = "USA" WHERE s.country = "United States of America";
         UPDATE omoccurrences s SET s.country = "USA" WHERE s.country = "United States America";

         UPDATE omoccurrences s
         SET s.sciname = trim(s.sciname), tidinterpreted = null
         WHERE sciname like "% " OR sciname like " %";

         UPDATE omoccurrences s
         SET s.sciname = replace(s.sciname,"   "," ")
         WHERE sciname like "%   %";

         UPDATE omoccurrences s
         SET s.sciname = replace(s.sciname,"  "," ")
         WHERE sciname like "%  %";

         UPDATE omoccurrences s
         SET s.sciname = replace(s.sciname," sp.","")
         WHERE sciname like "% sp.";

         UPDATE omoccurrences s
         SET s.sciname = replace(s.sciname," sp","")
         WHERE sciname like "% sp";

         UPDATE omoccurrences
         SET specificepithet = NULL
         WHERE specificepithet = "sp." OR specificepithet = "sp";

         #Link specimens to taxa
         UPDATE omoccurrences s INNER JOIN taxa t ON s.sciname = t.sciname SET s.TidInterpreted = t.tid WHERE s.TidInterpreted IS NULL;
         UPDATE (omoccurrences s INNER JOIN taxa t ON s.genus = t.unitname1 AND s.specificepithet = t.unitname2 AND s.InfraSpecificEpithet = t.unitname3)
             INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted
             SET s.TidInterpreted = t.tid
             WHERE ts.taxauthid = 1 AND s.TidInterpreted IS NULL;
         UPDATE omoccurrences s INNER JOIN taxa t ON s.genus = t.unitname1 AND s.specificepithet = t.unitname2 AND s.InfraSpecificEpithet = t.unitname3
             SET s.TidInterpreted = t.tid WHERE s.TidInterpreted IS NULL;

         #Filling missing families
         UPDATE omoccurrences u INNER JOIN taxstatus ts ON u.tidinterpreted = ts.tid
             SET u.family = ts.family
             WHERE ts.taxauthid = 1 AND ts.family <> "" AND ts.family IS NOT NULL AND (u.family IS NULL OR u.family = "");

         UPDATE ((omoccurrences u INNER JOIN taxa t ON u.family = t.sciname)
             INNER JOIN taxstatus ts ON t.tid = ts.tid)
             INNER JOIN taxa t2 ON ts.tidaccepted = t2.tid
             SET u.family = t2.sciname
             WHERE ts.taxauthid = 1 AND t.rankid = 140 AND ts.tid <> ts.tidaccepted;

         UPDATE omoccurrences u INNER JOIN taxa t ON u.genus = t.unitname1
             inner join taxstatus ts on t.tid = ts.tid
             SET u.family = ts.family
             WHERE t.rankid = 180 and ts.taxauthid = 1 AND ts.family <> "" AND ts.family IS NOT NULL AND (u.family IS NULL OR u.family = "");

         #Filling missing Authors
         UPDATE omoccurrences u INNER JOIN taxa t ON u.tidinterpreted = t.tid
             SET u.scientificNameAuthorship = t.author
             WHERE (u.scientificNameAuthorship = "" OR u.scientificNameAuthorship) IS NULL AND t.author IS NOT NULL;

         #Convert positive longs to negative
         UPDATE omoccurrences SET DecimalLongitude = -1*DecimalLongitude
         WHERE DecimalLongitude > 0 AND (Country = "USA" OR Country = "Mexico");

         #Clean illegal lat/longs that are out of range
         UPDATE omoccurrences SET DecimalLatitude = \N, DecimalLongitude = \N WHERE DecimalLatitude = 0 AND DecimalLongitude = 0;
         UPDATE omoccurrences SET DecimalLatitude = \N, DecimalLongitude = \N WHERE DecimalLatitude < -90 OR DecimalLatitude > 90;
         UPDATE omoccurrences SET DecimalLatitude = \N, DecimalLongitude = \N WHERE DecimalLongitude < -180 OR DecimalLongitude > 180;


         #Add statements to clean regional coordinates, see example below
         #UPDATE omoccurrences SET DecimalLatitude = \N, DecimalLongitude = \N WHERE (StateProvince = "Arizona" OR StateProvince = "AZ")
         #   AND (DecimalLatitude > 37.2 OR DecimalLatitude < 31.2 OR DecimalLongitude > -108.9 OR DecimalLongitude < -115.2);


END//
DELIMITER ;