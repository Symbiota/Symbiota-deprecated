ALTER TABLE `omoccurrences` 
 MODIFY COLUMN `decimalLatitude` DOUBLE DEFAULT NULL,
 MODIFY COLUMN `decimalLongitude` DOUBLE DEFAULT NULL;

ALTER TABLE `uploadspectemp` 
 MODIFY COLUMN `decimalLatitude` DOUBLE DEFAULT NULL,
 MODIFY COLUMN `decimalLongitude` DOUBLE DEFAULT NULL;

ALTER TABLE `images`
 MODIFY COLUMN `tid` INT(10) UNSIGNED DEFAULT NULL,
 DROP INDEX `Index_unique`,
 ADD UNIQUE INDEX `Index_unique` USING BTREE(`tid`, `url`, `occid`);

CREATE TABLE `omcollsecondary` (
  `ocsid` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `collid` INTEGER UNSIGNED NOT NULL,
  `InstitutionCode` VARCHAR(45) NOT NULL,
  `CollectionCode` VARCHAR(45),
  `CollectionName` VARCHAR(150) NOT NULL,
  `BriefDescription` VARCHAR(300),
  `FullDescription` VARCHAR(1000),
  `Homepage` VARCHAR(250),
  `IndividualUrl` VARCHAR(500),
  `Contact` VARCHAR(45),
  `Email` VARCHAR(45),
  `LatitudeDecimal` DOUBLE,
  `LongitudeDecimal` DOUBLE,
  `icon` VARCHAR(250),
  `CollType` VARCHAR(45),
  `SortSeq` INTEGER UNSIGNED,
  `InitialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`ocsid`),
  CONSTRAINT `FK_omcollsecondary_coll` FOREIGN KEY `FK_omcollsecondary_coll` (`collid`)
    REFERENCES `omcollections` (`CollID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
ENGINE = InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `omcollections` 
 MODIFY COLUMN `InstitutionCode` VARCHAR(45) NOT NULL,
 MODIFY COLUMN `CollectionCode` VARCHAR(45) DEFAULT NULL,
 DROP INDEX `unique_index`;

CREATE TABLE `populusrawlabels` (
  `prlid` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `occid` INTEGER UNSIGNED NOT NULL,
  `rawstr` TEXT NOT NULL,
  `notes` VARCHAR(255),
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`prlid`)
)
ENGINE = InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `populusrawlabels` 
  ADD CONSTRAINT `FK_populusrawlabels_occid` FOREIGN KEY `FK_populusrawlabels_occid` (`occid`)
    REFERENCES `omoccurrences` (`occid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

DROP TABLE `omoccurannotations`;
CREATE TABLE `omoccurdeterminations` (
  `detid` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `occid` INTEGER UNSIGNED NOT NULL,
  `identifiedBy` VARCHAR(45) NOT NULL,
  `dateIdentified` VARCHAR(45) NOT NULL,
  `sciname` VARCHAR(100) NOT NULL,
  `scientificNameAuthorship` VARCHAR(100),
  `identificationQualifier` VARCHAR(45),
  `identificationReferences` VARCHAR(255),
  `identificationRemarks` VARCHAR(255),
  `sortsequence` INTEGER UNSIGNED DEFAULT 10,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`detid`),
  CONSTRAINT `FK_omoccurdets_occid` FOREIGN KEY `FK_omoccurdets_occid` (`occid`)
    REFERENCES `omoccurrences` (`occid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
ENGINE = InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `omoccurdeterminations` 
   ADD UNIQUE INDEX `Index_unique`(`occid`, `dateIdentified`, `identifiedBy`);

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

  #Link taxa names to occurrences
  UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname SET o.TidInterpreted = t.tid WHERE o.TidInterpreted IS NULL;
  UPDATE (omoccurrences o INNER JOIN taxa t ON o.genus = t.unitname1 AND o.specificepithet = t.unitname2 AND o.InfraSpecificEpithet = t.unitname3)
    INNER JOIN taxstatus ts ON t.tid = ts.tidaccepted
    SET o.TidInterpreted = t.tid
    WHERE ts.taxauthid = 1 AND o.TidInterpreted IS NULL;
  UPDATE omoccurrences o INNER JOIN taxa t ON o.genus = t.unitname1 AND o.specificepithet = t.unitname2 AND o.InfraSpecificEpithet = t.unitname3
    SET o.TidInterpreted = t.tid WHERE o.TidInterpreted IS NULL;

  #Clean Image/Taxa links 
  UPDATE omoccurrences o INNER JOIN taxa t ON o.sciname = t.sciname
  SET o.tidinterpreted = t.tid
  WHERE o.tidinterpreted <> t.tid;

  UPDATE IGNORE omoccurrences o INNER JOIN images i ON o.occid = i.occid
  SET i.tid = o.tidinterpreted
  WHERE o.tidinterpreted <> i.tid;


  #General Optimizations of central, most active tables
  OPTIMIZE TABLE omoccurrences, images;

END//

DELIMITER ;

