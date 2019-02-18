ALTER TABLE `uploadspectemp`
 MODIFY COLUMN `coordinateUncertaintyInMeters` INTEGER UNSIGNED DEFAULT NULL;
ALTER TABLE `omoccurrences`
 MODIFY COLUMN `coordinateUncertaintyInMeters` INTEGER UNSIGNED DEFAULT NULL;


-- =============================================================
-- Script for cleaning new taxa before upload takes place
-- =============================================================

DROP PROCEDURE IF EXISTS uploadtaxa;
DROP PROCEDURE IF EXISTS UploadTaxaClean;
DROP PROCEDURE IF EXISTS UploadTaxaTransfer;

DELIMITER //
CREATE PROCEDURE `UploadTaxaClean`(IN defaultParent INT)
BEGIN

IF defaultParent = 0 THEN
        SELECT tid INTO defaultParent FROM taxa WHERE rankid = 10 LIMIT 1;
END IF;

#Do some cleaning in AcceptedStr column
UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr," ssp. "," subsp. ") WHERE AcceptedStr like "% ssp. %";
UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr," var "," var. ") WHERE AcceptedStr like "% var %";
UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr," subsp "," subsp. ") WHERE AcceptedStr like "% subsp %";
UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr," sp.","") WHERE AcceptedStr like "% sp.";
UPDATE uploadtaxa SET AcceptedStr = trim(AcceptedStr) WHERE AcceptedStr like "% " OR AcceptedStr like " %";
UPDATE uploadtaxa SET AcceptedStr = replace(AcceptedStr,"  "," ") WHERE AcceptedStr like "%  %";

#Update AcceptedStr column for ITIS data
UPDATE uploadtaxa u INNER JOIN uploadtaxa u2 ON u.sourceAcceptedId = u2.sourceId
SET u.AcceptedStr = u2.sciname
WHERE u.AcceptedStr IS NULL;

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
SET unitname2 = NULL
WHERE unitname2 = "";

UPDATE uploadtaxa
SET unitind3 = "f.", rankid = 260
WHERE unitind3 IS NULL AND (scinameinput LIKE "% f. %" OR scinameinput LIKE "% forma %")
AND (rankid IS NULL OR rankid = 260);

UPDATE uploadtaxa
SET unitind3 = "var.", rankid = 240
WHERE unitind3 IS NULL AND scinameinput LIKE "% var. %"
AND (rankid IS NULL OR rankid = 240);

UPDATE uploadtaxa
SET unitind3 = "subsp.", rankid = 230
WHERE unitind3 IS NULL AND (scinameinput LIKE "% subsp. %" OR scinameinput LIKE "% ssp. %")
AND (rankid IS NULL OR rankid = 230);

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

UPDATE IGNORE uploadtaxa
SET sciname = CONCAT_WS(" ",unitind1, unitname1, unitind2, unitname2, unitind3, unitname3)
WHERE sciname IS NULL;

UPDATE uploadtaxa u INNER JOIN taxa t ON u.sciname = t.sciname
SET u.tid = t.tid
WHERE u.tid IS NULL;

#Load vernaculars
INSERT IGNORE INTO taxavernaculars (tid, VernacularName, Language, Source)
SELECT tid, vernacular, vernlang, source FROM uploadtaxa WHERE tid IS NOT NULL AND Vernacular IS NOT NULL;

#Set family for ITIS uploads
UPDATE (uploadtaxa u1 INNER JOIN uploadtaxa u2 ON u1.unitname1 = u2.sciname)
INNER JOIN uploadtaxa u3 ON u2.sourceParentId = u3.sourceId
SET u1.family = u3.sciname
WHERE u1.family is null AND u2.rankid = 180 AND u3.rankid = 140;

UPDATE uploadtaxa u0 INNER JOIN uploadtaxa u1 ON u0.sourceAcceptedId = u1.sourceid
SET u0.family = u1.family
WHERE u0.family IS NULL AND u1.family IS NOT NULL;

#Set parent string
UPDATE uploadtaxa u INNER JOIN uploadtaxa u2 ON u.sourceParentId = u2.sourceId
SET u.parentstr = u2.sciname
WHERE u.parentstr IS NULL;

#Delete taxa in uploadtaxa table that are already in taxa table
DELETE ut.* FROM uploadtaxa ut WHERE tid IS NOT NULL;

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

UPDATE uploadtaxa
SET author = NULL
WHERE author = "";

#Set family
UPDATE uploadtaxa
SET family = NULL
WHERE family = "";

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

END//


-- =============================================
-- Script for loading new taxa
-- =============================================

DELIMITER //
CREATE PROCEDURE `UploadTaxaTransfer`()
BEGIN

#All taxa with a rank of family or higher (rankid <= 140) must have a rank setting
#All taxa with a rank of family or higher (rankid <= 140) must have parentstr or parent will be set as defaultparent tid

DECLARE startLoadCnt INT DEFAULT -1;
DECLARE endLoadCnt INT DEFAULT 0;
DECLARE loopCnt INT DEFAULT 0;

##Load data
#Loop through until no more names are added to taxa from uploadtaxa
WHILE (endLoadCnt > 0 OR startLoadCnt <> endLoadCnt) AND loopCnt < 30 DO

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

        #Load vernaculars
        INSERT IGNORE INTO taxavernaculars (tid, VernacularName, Language, Source)
        SELECT tid, vernacular, vernlang, source FROM uploadtaxa WHERE tid IS NOT NULL AND Vernacular IS NOT NULL;

        #Delete taxa added from UploadTaxa table
        DELETE FROM uploadtaxa
        WHERE tid is not null AND tidaccepted IS NOT NULL;

        UPDATE uploadtaxa up INNER JOIN taxa t ON up.parentstr = t.sciname
        SET parenttid = t.tid
        WHERE parenttid IS NULL;

        SELECT COUNT(*) INTO endLoadCnt FROM uploadtaxa;

        Set loopCnt = loopCnt + 1;

END WHILE;

#Link specimens to taxa
UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname SET o.TidInterpreted = t.tid WHERE o.TidInterpreted IS NULL;
UPDATE (omoccurrences o INNER JOIN taxa t ON o.genus = t.unitname1 AND o.specificepithet = t.unitname2 AND o.InfraSpecificEpithet = t.unitname3)
    INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted
    SET o.TidInterpreted = t.tid
    WHERE ts.taxauthid = 1 AND o.TidInterpreted IS NULL;
UPDATE omoccurrences o INNER JOIN taxa t ON o.genus = t.unitname1 AND o.specificepithet = t.unitname2 AND o.InfraSpecificEpithet = t.unitname3
    SET o.TidInterpreted = t.tid WHERE o.TidInterpreted IS NULL;

INSERT IGNORE INTO omoccurgeoindex(tid,decimallatitude,decimallongitude) 
  SELECT DISTINCT o.tidinterpreted, round(o.decimallatitude,3), round(o.decimallongitude,3) 
  FROM omoccurrences o LEFT JOIN omoccurgeoindex g ON o.tidinterpreted = g.tid
  WHERE g.tid IS NULL AND o.tidinterpreted IS NOT NULL AND o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL;

END//

DELIMITER ;

-- =============================================================
-- Redefine GeneralMaintenance Procedure
-- =============================================================

DROP PROCEDURE IF EXISTS GeneralMaintenance;

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


  #Rebuild omoccurgeoindex
  REPLACE INTO omoccurgeoindex(tid,decimallatitude,decimallongitude)
     SELECT DISTINCT o.tidinterpreted, round(o.decimallatitude,3), round(o.decimallongitude,3)
     FROM omoccurrences o
     WHERE o.tidinterpreted IS NOT NULL AND o.decimallatitude IS NOT NULL AND o.decimallongitude IS NOT NULL;

  DELETE FROM omoccurgeoindex WHERE InitialTimestamp < DATE_SUB(CURDATE(), INTERVAL 1 DAY);


  #Add to taxonomic hierarachy taxa that are in taxa but not taxstatus table
  INSERT INTO taxstatus ( TID, TidAccepted, taxauthid, ParentTid )
  SELECT t.tid, t.tid, 1 as taxaAuthId, t2.tid
  FROM (taxa t LEFT JOIN taxstatus ts ON t.tid = ts.tid)
  INNER JOIN taxa t2 ON t.unitname1 = t2.unitname1 AND t.unitname2 = t2.unitname2
  where ts.tid is null AND t2.rankid = 220 and t.rankid > 220;

  INSERT INTO taxstatus ( TID, TidAccepted, taxauthid, ParentTid )
  SELECT t.tid, t.tid, 1 as taxaAuthId, t2.tid
  FROM (taxa t LEFT JOIN taxstatus ts ON t.tid = ts.tid)
  INNER JOIN taxa t2 ON t.unitname1 = t2.unitname1
  where ts.tid is null AND t2.rankid = 180 and t.rankid = 220;


  UPDATE (taxstatus ts INNER JOIN taxstatus ts2 ON ts.parenttid = ts2.tid)
  INNER JOIN taxa t ON ts2.tid = t.tid
  SET ts.family = t.sciname
  WHERE ts.family IS NULL AND t.rankid = 140;

  UPDATE ((taxstatus ts INNER JOIN taxstatus ts2 ON ts.parenttid = ts2.tid)
  INNER JOIN taxstatus ts3 ON ts2.parenttid = ts3.tid)
  INNER JOIN taxa t ON ts3.tid = t.tid
  SET ts.family = t.sciname
  WHERE ts.family IS NULL AND t.rankid = 140;

  UPDATE (((taxstatus ts INNER JOIN taxstatus ts2 ON ts.parenttid = ts2.tid)
  INNER JOIN taxstatus ts3 ON ts2.parenttid = ts3.tid)
  INNER JOIN taxstatus ts4 ON ts3.parenttid = ts4.tid)
  INNER JOIN taxa t ON ts4.tid = t.tid
  SET ts.family = t.sciname
  WHERE ts.family IS NULL AND t.rankid = 140;


  #General Optimizations of central, most active tables
  OPTIMIZE TABLE omoccurrences, images;

END//

DELIMITER ;
