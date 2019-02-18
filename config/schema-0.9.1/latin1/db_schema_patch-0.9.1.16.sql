DROP PROCEDURE if exists updateSymbiotaSchema;

DELIMITER |

CREATE PROCEDURE updateSymbiotaSchema ()

BEGIN
  DECLARE requiredVersion varchar(20);  -- version needed for update to fire
  DECLARE currentVersion varchar(20);   -- version present in schema
  DECLARE newVersion varchar(20);       -- version this update will apply
  DECLARE okToUpdate boolean DEFAULT FALSE;
  DECLARE done boolean DEFAULT FALSE;
  DECLARE curVersion CURSOR for select versionnumber from schemaversion order by dateapplied desc limit 1;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  --  (1)  Change the version numbers ****************************
  --  Previous version must be this version for the update to fire
  SET requiredVersion = '0.9.1.15';
  SET newVersion = '0.9.1.16';
  --  ************************************************************

  OPEN curVersion;
  
  verLoop: LOOP
     FETCH curVersion into currentVersion;
     IF done THEN
        LEAVE verLoop;
     END IF;
     IF currentVersion = requiredVersion THEN 
        SET okToUpdate = TRUE;
     END IF;
  END LOOP;

IF okToUpdate THEN 

START TRANSACTION;

INSERT INTO schemaversion (versionnumber) values (newVersion);

#Biotic inventory changes
ALTER TABLE `fmchecklists` 
  CHANGE COLUMN `dynamicsql` `dynamicsql` VARCHAR(500) NULL DEFAULT NULL ;

ALTER TABLE `fmchecklists`
  ADD COLUMN `defaultSettings` varchar(250) NULL AFTER `Access`;

ALTER TABLE `fmchklsttaxalink` 
  CHANGE COLUMN `Endemic` `Endemic` VARCHAR(45) NULL DEFAULT NULL ,
  ADD COLUMN `invasive` VARCHAR(45) NULL AFTER `Endemic`;


#Glossary table changes
ALTER TABLE `glossary`
  CHANGE COLUMN `term` `term` varchar(150) NOT NULL AFTER `glossid`,
  CHANGE COLUMN `definition` `definition` varchar(600) NULL AFTER `term`,
  DROP INDEX `Index_term` ,
  ADD INDEX `Index_term` (`term`) ;

CREATE TABLE `glossarytermlink` (
  `gltlinkid` int(10) NOT NULL AUTO_INCREMENT,
  `glossgrpid` int(10) unsigned NOT NULL,
  `glossid` int(10) unsigned NOT NULL,
  `relationshipType` varchar(45) NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gltlinkid`),
  UNIQUE `Unique_termkeys` (`glossgrpid`,`glossid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1; 


ALTER TABLE `glossarytermlink` 
  ADD CONSTRAINT `glossarytermlink_ibfk_1` FOREIGN KEY (`glossid`) REFERENCES `glossary` (`glossid`) ON DELETE CASCADE ON UPDATE CASCADE;

INSERT INTO glossarytermlink(glossgrpid,glossid)
  SELECT glossid,glossid
  FROM glossary; 

CREATE TABLE `glossarytaxalink` (
  `glossgrpid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`glossgrpid`,`tid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `glossarytaxalink` 
  ADD CONSTRAINT `glossarytaxalink_ibfk_1` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE ON UPDATE CASCADE,
  ADD CONSTRAINT `glossarytaxalink_ibfk_2` FOREIGN KEY (`glossgrpid`) REFERENCES `glossarytermlink` (`glossgrpid`) ON DELETE CASCADE ON UPDATE CASCADE;
  
ALTER TABLE `glossaryimages`
  CHANGE COLUMN `url` `url` varchar(255) NOT NULL AFTER `glossid`,
  ADD COLUMN `thumbnailurl` varchar(255) NULL AFTER `url` ;

DROP TABLE `glossarycatlink`;
DROP TABLE `glossarycatagories`;

#Reference tables
ALTER TABLE `referenceobject`
  DROP COLUMN `libraryNumber`,
  ADD COLUMN `tertiarytitle`  varchar(250) NULL AFTER `shorttitle`,
  ADD COLUMN `alternativetitle`  varchar(250) NULL AFTER `tertiarytitle`,
  ADD COLUMN `typework`  varchar(150) NULL AFTER `alternativetitle`,
  ADD COLUMN `figures`  varchar(150) NULL AFTER `typework`;

ALTER TABLE `referencetype`
  DROP COLUMN `IsPublished`,
  DROP COLUMN `Year`,
  DROP COLUMN `OriginalPublication`,
  DROP COLUMN `ReprintEdition`,
  DROP COLUMN `ReviewedItem`;

SET FOREIGN_KEY_CHECKS=0;

TRUNCATE TABLE `referencetype`;

INSERT INTO `referencetype` VALUES ('1', 'Generic', null, 'Title', 'SecondaryTitle', 'PlacePublished', 'Publisher', 'Volume', 'NumberVolumes', 'Number', 'Pages', 'Section', 'TertiaryTitle', 'Edition', 'Date', 'TypeWork', 'ShortTitle', 'AlternativeTitle', 'Isbn_Issn', 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('2', 'Journal Article', null, 'Title', 'Periodical Title', null, null, 'Volume', null, 'Issue', 'Pages', null, null, null, 'Date', null, 'Short Title', 'Alt. Jour.', null, 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('3', 'Book', '1', 'Title', 'Series Title', 'City', 'Publisher', 'Volume', 'No. Vols.', 'Number', 'Pages', null, null, 'Edition', 'Date', null, 'Short Title', null, 'ISBN', 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('4', 'Book Section', null, 'Title', 'Book Title', 'City', 'Publisher', 'Volume', 'No. Vols.', 'Number', 'Pages', null, 'Ser. Title', 'Edition', 'Date', null, 'Short Title', null, 'ISBN', 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('5', 'Manuscript', null, 'Title', 'Collection Title', 'City', null, null, null, 'Number', 'Pages', null, null, 'Edition', 'Date', 'Type Work', 'Short Title', null, null, 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('6', 'Edited Book', '1', 'Title', 'Series Title', 'City', 'Publisher', 'Volume', 'No. Vols.', 'Number', 'Pages', null, null, 'Edition', 'Date', null, 'Short Title', null, 'ISBN', 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('7', 'Magazine Article', null, 'Title', 'Periodical Title', null, null, 'Volume', null, 'Issue', 'Pages', null, null, null, 'Date', null, 'Short Title', null, null, 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('8', 'Newspaper Article', null, 'Title', 'Periodical Title', 'City', null, null, null, null, 'Pages', 'Section', null, 'Edition', 'Date', 'Type Art.', 'Short Title', null, null, 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('9', 'Conference Proceedings', null, 'Title', 'Conf. Name', 'Conf. Loc.', 'Publisher', 'Volume', 'No. Vols.', null, 'Pages', null, 'Ser. Title', 'Edition', 'Date', null, 'Short Title', null, 'ISBN', 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('10', 'Thesis', null, 'Title', 'Academic Dept.', 'City', 'University', null, null, null, 'Pages', null, null, null, 'Date', 'Thesis Type', 'Short Title', null, null, 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('11', 'Report', null, 'Title', null, 'City', 'Institution', null, null, null, 'Pages', null, null, null, 'Date', 'Type Work', 'Short Title', null, 'Rpt. No.', 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('12', 'Personal Communication', null, 'Title', null, 'City', 'Publisher', null, null, null, null, null, null, null, 'Date', 'Type Work', 'Short Title', null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('13', 'Computer Program', null, 'Title', null, 'City', 'Publisher', 'Version', null, null, null, null, null, 'Platform', 'Date', 'Type Work', 'Short Title', null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('14', 'Electronic Source', null, 'Title', null, null, 'Publisher', 'Access Year', 'Extent', 'Acc. Date', null, null, null, 'Edition', 'Date', 'Medium', 'Short Title', null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('15', 'Audiovisual Material', null, 'Title', 'Collection Title', 'City', 'Publisher', null, null, 'Number', null, null, null, null, 'Date', 'Type Work', 'Short Title', null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('16', 'Film or Broadcast', null, 'Title', 'Series Title', 'City', 'Distributor', null, null, null, 'Length', null, null, null, 'Date', 'Medium', 'Short Title', null, 'ISBN', null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('17', 'Artwork', null, 'Title', null, 'City', 'Publisher', null, null, null, null, null, null, null, 'Date', 'Type Work', 'Short Title', null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('18', 'Map', null, 'Title', null, 'City', 'Publisher', null, null, null, 'Scale', null, null, 'Edition', 'Date', 'Type Work', 'Short Title', null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('19', 'Patent', null, 'Title', 'Published Source', 'Country', 'Assignee', 'Volume', 'No. Vols.', 'Issue', 'Pages', null, null, null, 'Date', null, 'Short Title', null, 'Pat. No.', 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('20', 'Hearing', null, 'Title', 'Committee', 'City', 'Publisher', null, null, 'Doc. No.', 'Pages', null, 'Leg. Boby', 'Session', 'Date', null, 'Short Title', null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('21', 'Bill', null, 'Title', 'Code', null, null, 'Code Volume', null, 'Bill No.', 'Pages', 'Section', 'Leg. Boby', 'Session', 'Date', null, 'Short Title', null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('22', 'Statute', null, 'Title', 'Code', null, null, 'Code Number', null, 'Law No.', '1st Pg.', 'Section', null, 'Session', 'Date', null, 'Short Title', null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('23', 'Case', null, 'Title', null, null, 'Court', 'Reporter Vol.', null, null, null, null, null, null, 'Date', null, null, null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('24', 'Figure', null, 'Title', 'Source Program', null, null, null, '-', null, null, null, null, null, 'Date', null, null, null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('25', 'Chart or Table', null, 'Title', 'Source Program', null, null, null, null, null, null, null, null, null, 'Date', null, null, null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('26', 'Equation', null, 'Title', 'Source Program', null, null, 'Volume', null, 'Number', null, null, null, null, 'Date', null, null, null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('27', 'Book Series', '1', 'Title', null, 'City', 'Publisher', null, 'No. Vols.', null, 'Pages', null, null, 'Edition', 'Date', null, null, null, 'ISBN', 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('28', 'Determination', null, 'Title', null, null, 'Institution', null, null, null, null, null, null, null, 'Date', null, null, null, null, null, null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('29', 'Sub-Reference', null, 'Title', null, null, null, null, null, null, 'Pages', null, null, null, 'Date', null, null, null, null, 'Figures', null, '2014-06-17 00:27:12');
INSERT INTO `referencetype` VALUES ('30', 'Periodical', '1', 'Title', null, 'City', null, 'Volume', null, 'Issue', null, null, null, 'Edition', 'Date', null, 'Short Title', 'Alt. Jour.', null, null, null, '2014-10-30 21:34:44');
INSERT INTO `referencetype` VALUES ('31', 'Web Page', null, 'Title', null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, null, '2014-10-30 21:37:12');

SET FOREIGN_KEY_CHECKS=1;

CREATE TABLE `referencechklsttaxalink` (
  `refid` INT NOT NULL,
  `clid` INT UNSIGNED NOT NULL,
  `tid` INT UNSIGNED NOT NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`refid`, `clid`, `tid`),
  INDEX `FK_refchktaxalink_clidtid_idx` (`clid` ASC, `tid` ASC),
  CONSTRAINT `FK_refchktaxalink_ref`
    FOREIGN KEY (`refid`)
    REFERENCES `referenceobject` (`refid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `FK_refchktaxalink_clidtid`
    FOREIGN KEY (`clid` , `tid`)
    REFERENCES `fmchklsttaxalink` (`CLID` , `TID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Geographic thesaurus
ALTER TABLE `geothescontinent` 
  ADD COLUMN `footprintWKT` TEXT NULL AFTER `acceptedid`;

ALTER TABLE `geothescountry` 
  ADD COLUMN `footprintWKT` TEXT NULL AFTER `continentid`;

ALTER TABLE `geothescounty` 
  ADD COLUMN `footprintWKT` TEXT NULL AFTER `stateid`;

ALTER TABLE `geothesmunicipality` 
  ADD COLUMN `footprintWKT` TEXT NULL AFTER `countyid`;

ALTER TABLE `geothesstateprovince` 
  ADD COLUMN `footprintWKT` TEXT NULL AFTER `countryid`;

ALTER TABLE `geothesmunicipality` 
  DROP FOREIGN KEY `FK_geothesmunicipality_accepted`;
ALTER TABLE `geothesmunicipality` 
  ADD INDEX `FK_geothesmunicipality_accepted_idx` (`acceptedid` ASC),
  DROP INDEX `FK_geothesmunicipality_accepted_idx` ;
ALTER TABLE `geothesmunicipality` 
  ADD CONSTRAINT `FK_geothesmunicipality_accepted`
    FOREIGN KEY (`acceptedid`)
    REFERENCES `geothesmunicipality` (`gtmid`);


# Collection statistics
CREATE TABLE `adminstats` (
  `idadminstats` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `category` VARCHAR(45) NOT NULL,
  `statname` VARCHAR(45) NOT NULL,
  `statvalue` INT NULL,
  `statpercentage` INT NULL,
  `dynamicProperties` TEXT NULL,
  `groupid` INT NOT NULL,
  `collid` INT UNSIGNED NULL,
  `uid` INT UNSIGNED NULL,
  `note` VARCHAR(250) NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`idadminstats`),
  INDEX `FK_adminstats_collid_idx` (`collid` ASC),
  INDEX `FK_adminstats_uid_idx` (`uid` ASC),
  INDEX `Index_adminstats_ts` (`initialtimestamp` ASC),
  INDEX `Index_category` (`category` ASC),
  CONSTRAINT `FK_adminstats_collid`
    FOREIGN KEY (`collid`)
    REFERENCES `omcollections` (`CollID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `FK_adminstats_uid`
    FOREIGN KEY (`uid`)
    REFERENCES `users` (`uid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Taxa table changes
# Add dateLastModified, modifiedUid to taxa and taxstatus
ALTER TABLE `taxa` 
  ADD COLUMN `modifiedUid` INT UNSIGNED NULL AFTER `SecurityStatus`,
  ADD COLUMN `modifiedTimeStamp` DATETIME NULL AFTER `modifiedUid`,
  CHANGE COLUMN `InitialTimeStamp` `InitialTimeStamp` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `modifiedTimeStamp`,
  ADD INDEX `FK_taxa_uid_idx` (`modifiedUid` ASC);

ALTER TABLE `taxa` 
  ADD CONSTRAINT `FK_taxa_uid` FOREIGN KEY (`modifiedUid`) REFERENCES `users` (`uid`) ON DELETE NO ACTION ON UPDATE RESTRICT;

ALTER TABLE `taxauthority` 
  ADD COLUMN `url` VARCHAR(150) NULL AFTER `email`;

ALTER TABLE `taxa` 
  DROP FOREIGN KEY `FK_taxa_taxonunit`;

ALTER TABLE `taxa` 
  DROP INDEX `FK_taxa_taxonunit` ;

ALTER TABLE `taxa` 
  CHANGE COLUMN `KingdomID` `KingdomID` TINYINT(3) UNSIGNED NULL ,
  CHANGE COLUMN `RankId` `RankId` SMALLINT(5) UNSIGNED NULL DEFAULT NULL ;

ALTER TABLE `taxa` 
  ADD COLUMN `kingdomName` VARCHAR(45) NULL AFTER `TID`;

UPDATE taxa SET kingdomName = "Monera" WHERE kingdomid = 1 AND kingdomName IS NULL;
UPDATE taxa SET kingdomName = "Protista" WHERE kingdomid = 2 AND kingdomName IS NULL;
UPDATE taxa SET kingdomName = "Plantae" WHERE kingdomid = 3 AND kingdomName IS NULL;
UPDATE taxa SET kingdomName = "Fungi" WHERE kingdomid = 4 AND kingdomName IS NULL;
UPDATE taxa SET kingdomName = "Animalia" WHERE kingdomid = 5 AND kingdomName IS NULL;

ALTER TABLE `taxonunits` 
  CHANGE COLUMN `kingdomid` `kingdomid` TINYINT(3) UNSIGNED NULL ,
  CHANGE COLUMN `rankid` `rankid` SMALLINT(5) UNSIGNED NOT NULL DEFAULT 0 ,
  ADD COLUMN `taxonunitid` INT NOT NULL AUTO_INCREMENT FIRST,
  ADD COLUMN `kingdomName` VARCHAR(45) NOT NULL DEFAULT 'Organism' AFTER `kingdomid`,
  ADD COLUMN `modifiedby` VARCHAR(45) NULL AFTER `reqparentrankid`,
  ADD COLUMN `modifiedtimestamp` DATETIME NULL AFTER `modifiedby`,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`taxonunitid`);

UPDATE taxonunits SET kingdomName = "Monera" WHERE kingdomid = 1 AND kingdomName = "Organism";
UPDATE taxonunits SET kingdomName = "Protista" WHERE kingdomid = 2 AND kingdomName = "Organism";
UPDATE taxonunits SET kingdomName = "Plantae" WHERE kingdomid = 3 AND kingdomName = "Organism";
UPDATE taxonunits SET kingdomName = "Fungi" WHERE kingdomid = 4 AND kingdomName = "Organism";
UPDATE taxonunits SET kingdomName = "Animalia" WHERE kingdomid = 5 AND kingdomName = "Organism";

CREATE TABLE `taxaresourcelinks` (
  `taxaresourceid` INT NOT NULL AUTO_INCREMENT,
  `tid` INT UNSIGNED NOT NULL,
  `sourcename` VARCHAR(150) NOT NULL,
  `sourceidentifier` VARCHAR(45) NULL,
  `sourceguid` VARCHAR(150) NULL,
  `url` VARCHAR(250) NULL,
  `notes` VARCHAR(250) NULL,
  `ranking` INT NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`taxaresourceid`),
  INDEX `taxaresource_name` (`sourcename` ASC),
  INDEX `FK_taxaresource_tid_idx` (`tid` ASC),
  CONSTRAINT `FK_taxaresource_tid`
    FOREIGN KEY (`tid`)
    REFERENCES `taxa` (`TID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE);

INSERT INTO taxaresourcelinks(tid,sourcename,sourceidentifier)
  SELECT tid, "USDA PLANTS", UsdaSymbol
  FROM taxa 
  WHERE UsdaSymbol IS NOT NULL AND UsdaSymbol <> "";

INSERT INTO taxaresourcelinks(tid,sourcename,sourceidentifier)
  SELECT tid, "Flora of North America", fnaprikey
  FROM taxa 
  WHERE fnaprikey IS NOT NULL AND fnaprikey <> "";

ALTER TABLE `taxa` 
  DROP COLUMN `UsdaSymbol`,
  DROP COLUMN `fnaprikey`,
  DROP COLUMN `verificationSource`,
  DROP COLUMN `verificationStatus`;

ALTER TABLE `taxstatus` 
  DROP COLUMN `uppertaxonomy`,
  DROP INDEX `Index_ts_upper` ;

ALTER TABLE `uploadtaxa` 
  DROP COLUMN `UpperTaxonomy`;


#Upload occurrrence temporary tables 
ALTER TABLE `uploadspectemp` 
  ADD COLUMN `minimumDepthInMeters` INT NULL AFTER `verbatimElevation`,
  ADD COLUMN `maximumDepthInMeters` INT NULL AFTER `minimumDepthInMeters`,
  ADD COLUMN `verbatimDepth` VARCHAR(50) NULL AFTER `maximumDepthInMeters`,
  ADD COLUMN `storageLocation` VARCHAR(100) NULL AFTER `disposition`,
  ADD COLUMN `behavior` VARCHAR(500) NULL AFTER `verbatimAttributes`;

ALTER TABLE `uploadspectemp` 
  DROP COLUMN `attributes`;

ALTER TABLE `uploadspectemp` 
  CHANGE COLUMN `language` `language` VARCHAR(20) NULL DEFAULT NULL ;

ALTER TABLE `uploadspecmap` 
  DROP INDEX `Index_unique` ,
  ADD UNIQUE INDEX `Index_unique` (`uspid` ASC, `symbspecfield` ASC, `sourcefield` ASC);


CREATE TABLE `uploaddetermtemp` (
  `occid` int(10) unsigned NULL,
  `collid` int(10) unsigned NULL,
  `dbpk` varchar(150) NULL,
  `identifiedBy` varchar(60) NOT NULL,
  `dateIdentified` varchar(45) NOT NULL,
  `dateIdentifiedInterpreted` date DEFAULT NULL,
  `sciname` varchar(100) NOT NULL,
  `scientificNameAuthorship` varchar(100) DEFAULT NULL,
  `identificationQualifier` varchar(45) DEFAULT NULL,
  `iscurrent` int(2) DEFAULT '0',
  `detType` varchar(45) DEFAULT NULL,
  `identificationReferences` varchar(255) DEFAULT NULL,
  `identificationRemarks` varchar(255) DEFAULT NULL,
  `sourceIdentifier` varchar(45) DEFAULT NULL,
  `sortsequence` int(10) unsigned DEFAULT '10',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `uploaddetermtemp` 
  ADD INDEX `Index_uploaddet_occid` (`occid` ASC),
  ADD INDEX `Index_uploaddet_collid` (`collid` ASC),
  ADD INDEX `Index_uploaddet_dbpk` (`dbpk` ASC); 


#Image upload temp table adjustments
DELETE FROM `uploadimagetemp`;
OPTIMIZE TABLE `uploadimagetemp`;

ALTER TABLE `uploadimagetemp` 
  CHANGE COLUMN `dbpk` `dbpk` VARCHAR(150) NULL ;

ALTER TABLE `uploadimagetemp` 
  ADD COLUMN `format` VARCHAR(45) NULL AFTER `imagetype`,
  ADD COLUMN `archiveurl` VARCHAR(255) NULL AFTER `originalurl`;

ALTER TABLE `uploadimagetemp` 
  ADD INDEX `Index_uploadimg_occid` (`occid` ASC),
  ADD INDEX `Index_uploadimg_collid` (`collid` ASC),
  ADD INDEX `Index_uploadimg_dbpk` (`dbpk` ASC),
  ADD INDEX `Index_uploadimg_ts` (`initialtimestamp` ASC);

ALTER TABLE `images` 
  ADD COLUMN `format` VARCHAR(45) NULL AFTER `imagetype`,
  ADD COLUMN `archiveurl` VARCHAR(255) NULL AFTER `originalurl`;


#GUID tables
DELETE FROM guidoccurrences WHERE occid IS NULL AND archiveobj IS NULL;

DELETE FROM guidoccurdeterminations WHERE detid IS NULL AND archiveobj IS NULL;

DELETE FROM guidimages WHERE imgid IS NULL AND archiveobj IS NULL;

ALTER TABLE `guidoccurrences` 
  DROP INDEX `FK_guidoccur_occid_idx` ;
ALTER TABLE `guidoccurdeterminations` 
  DROP INDEX `FK_guidoccurdet_detid_idx` ;
ALTER TABLE `guidimages` 
  DROP INDEX `FK_guidimages_imgid_idx` ;


#Specimen processing changes
ALTER TABLE `specprocessorprojects` 
  ADD COLUMN `source` VARCHAR(45) NULL AFTER `createLgImg`;

ALTER TABLE `omcrowdsourcequeue` 
  ADD COLUMN `isvolunteer` INT(2) NOT NULL DEFAULT 1 AFTER `points`;

DELETE q.* FROM omcrowdsourcequeue q LEFT JOIN images i ON q.occid = i.occid
  WHERE i.occid IS NULL AND q.uidprocessor IS NULL;


#OCR processing tables
CREATE TABLE `specprococrfrag` (
  `ocrfragid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `prlid` INT UNSIGNED NOT NULL,
  `firstword` VARCHAR(45) NOT NULL,
  `secondword` VARCHAR(45) NULL,
  `keyterm` VARCHAR(45) NULL,
  `wordorder` INT NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`ocrfragid`),
  INDEX `FK_specprococrfrag_prlid_idx` (`prlid` ASC),
  INDEX `Index_keyterm` (`keyterm` ASC),
  CONSTRAINT `FK_specprococrfrag_prlid`
    FOREIGN KEY (`prlid`)
    REFERENCES `specprocessorrawlabels` (`prlid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `specprocessorrawlabelsfulltext` (
  `prlid` INT NOT NULL,
  `imgid` INT NOT NULL,
  `rawstr` TEXT NOT NULL,
  PRIMARY KEY (`prlid`),
  INDEX `Index_ocr_imgid` (`imgid` ASC)
)ENGINE = MyISAM DEFAULT CHARSET=latin1;

INSERT INTO specprocessorrawlabelsfulltext(prlid, imgid, rawstr)
  SELECT prlid, imgid, rawstr
  FROM specprocessorrawlabels;

ALTER TABLE `specprocessorrawlabelsfulltext` 
  ADD FULLTEXT INDEX `Index_ocr_fulltext` (`rawstr` ASC);


#Salix word stat changes 
TRUNCATE TABLE salixwordstats;

ALTER TABLE `salixwordstats` 
  DROP FOREIGN KEY `FK_salixws_collid`;

ALTER TABLE `salixwordstats` 
  DROP COLUMN `collid`,
  DROP INDEX `INDEX_unique` ,
  ADD UNIQUE INDEX `INDEX_unique` (`firstword` ASC, `secondword` ASC),
  DROP INDEX `FK_salixws_collid_idx` ;

ALTER TABLE `salixwordstats` 
  CHANGE COLUMN `locality` `locality` INT(4) NOT NULL DEFAULT 0 ,
  CHANGE COLUMN `localityFreq` `localityFreq` INT(4) NOT NULL DEFAULT 0 ,
  CHANGE COLUMN `habitat` `habitat` INT(4) NOT NULL DEFAULT 0 ,
  CHANGE COLUMN `habitatFreq` `habitatFreq` INT(4) NOT NULL DEFAULT 0 ,
  CHANGE COLUMN `substrate` `substrate` INT(4) NOT NULL DEFAULT 0 ,
  CHANGE COLUMN `substrateFreq` `substrateFreq` INT(4) NOT NULL DEFAULT 0 ,
  CHANGE COLUMN `verbatimAttributes` `verbatimAttributes` INT(4) NOT NULL DEFAULT 0 ,
  CHANGE COLUMN `verbatimAttributesFreq` `verbatimAttributesFreq` INT(4) NOT NULL DEFAULT 0 ,
  CHANGE COLUMN `occurrenceRemarks` `occurrenceRemarks` INT(4) NOT NULL DEFAULT 0 ,
  CHANGE COLUMN `occurrenceRemarksFreq` `occurrenceRemarksFreq` INT(4) NOT NULL DEFAULT 0 ,
  CHANGE COLUMN `totalcount` `totalcount` INT(4) NOT NULL DEFAULT 0 ;

ALTER TABLE `salixwordstats` 
  CHANGE COLUMN `datelastmodified` `initialtimestamp` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ;

#Institution table changes
ALTER TABLE `institutions` 
  ADD COLUMN `modifieduid` INT UNSIGNED NULL AFTER `Notes`,
  ADD COLUMN `modifiedTimeStamp` DATETIME NULL AFTER `modifieduid`,
  ADD INDEX `FK_inst_uid_idx` (`modifieduid` ASC);

ALTER TABLE `institutions` 
  ADD CONSTRAINT `FK_inst_uid`
    FOREIGN KEY (`modifieduid`)
    REFERENCES `users` (`uid`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION;

ALTER TABLE `institutions` 
  DROP INDEX `Index_instcode` ;


#occurrence datasets table modifications
ALTER TABLE `omoccurdatasets` 
  CHANGE COLUMN `uid` `uid` INT(11) UNSIGNED NULL ,
  ADD COLUMN `collid` INT UNSIGNED NULL AFTER `uid`,
  ADD INDEX `FK_omoccurdatasets_uid_idx` (`uid` ASC),
  ADD INDEX `FK_omoccurdatasets_collid_idx` (`collid` ASC);

ALTER TABLE `omoccurdatasets` 
  ADD CONSTRAINT `FK_omoccurdatasets_uid`
    FOREIGN KEY (`uid`)
    REFERENCES `users` (`uid`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_omcollections_collid`
    FOREIGN KEY (`collid`)
    REFERENCES `omcollections` (`CollID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;


#Misc Occurrence Model changes
ALTER TABLE `omcollcatagories` 
  RENAME TO `omcollcategories` ;

ALTER TABLE `omcollcategories` 
  CHANGE COLUMN `catagory` `category` VARCHAR(75) NOT NULL;

ALTER TABLE `omcollcategories` 
  ADD COLUMN `url` VARCHAR(250) NULL AFTER `acronym`,
  CHANGE COLUMN `inclusive` `inclusive` INT(2) NULL DEFAULT 1 ;

UPDATE `omcollcategories` SET `inclusive` = 2 WHERE `inclusive` = 0;
UPDATE `omcollcategories` SET `inclusive` = 0 WHERE `inclusive` = 1;
UPDATE `omcollcategories` SET `inclusive` = 1 WHERE `inclusive` = 2;

ALTER TABLE `omcollectionstats` 
  DROP COLUMN `dbpassword`,
  DROP COLUMN `dblogin`,
  DROP COLUMN `dbport`,
  DROP COLUMN `dburl`,
  DROP COLUMN `dbtype`;

ALTER TABLE `omcollectionstats` 
  ADD COLUMN `dynamicProperties` VARCHAR(500) NULL AFTER `uploadedby`;


ALTER TABLE `omoccurdeterminations` 
  ADD COLUMN `dateIdentifiedInterpreted` DATE NULL AFTER `dateIdentified`,
  ADD INDEX `Index_dateIdentInterpreted` (`dateIdentifiedInterpreted` ASC);

ALTER TABLE `omoccurdeterminations` 
  ADD COLUMN `printqueue` INT(2) NULL DEFAULT 0 AFTER `iscurrent`,
  ADD COLUMN `detType` VARCHAR(45) NULL AFTER `appliedStatus`;


#Occurrence tables changes
ALTER TABLE `omoccurrences` 
  DROP COLUMN `attributes`,
  ADD COLUMN `behavior` VARCHAR(500) NULL AFTER `verbatimAttributes`,
  CHANGE COLUMN `dbpk` `dbpk` VARCHAR(150) NULL DEFAULT NULL ;

ALTER TABLE `omoccurrences` 
  ADD INDEX `Index_occurDateLastModifed` (`dateLastModified` ASC),
  ADD INDEX `Index_occurDateEntered` (`dateEntered` ASC),
  ADD INDEX `Index_occurRecordEnteredBy` (`recordEnteredBy` ASC);


CREATE TABLE `omoccurrencesfulltext` (
  `occid` INT NOT NULL,
  `locality` TEXT NULL,
  `recordedby` VARCHAR(255) NULL,
  PRIMARY KEY (`occid`)
)ENGINE = MyISAM DEFAULT CHARSET=latin1;

INSERT INTO omoccurrencesfulltext(occid,collid,recordedby,locality) 
  SELECT occid,collid,recordedby,locality 
  FROM omoccurrences;

ALTER TABLE `omoccurrencesfulltext` 
  ADD FULLTEXT INDEX `ft_occur_locality` (`locality` ASC),
  ADD FULLTEXT INDEX `ft_occur_recordedby` (`recordedby` ASC);

ALTER TABLE `omoccuridentifiers` 
  CHANGE COLUMN `identifiername` `identifiername` VARCHAR(45) NULL COMMENT 'barcode, accession number, old catalog number, NPS, etc' ;


--  ******* End of Schema Changes to be applied in this update 

COMMIT;

--  if in MySQL/MARIADB 5.2+ where SIGNAL is supported, can return an error condition
--  ELSE
   -- SIGNAL SQLSTATE VALUE '99999'
   --   SET MESSAGE_TEXT = 'Prerequisite schema version not found ' ;
END IF; 

END|

DELIMITER ;

CALL updateSymbiotaSchema();


DELIMITER //
DROP TRIGGER IF EXISTS `omoccurrencesfulltext_insert`//
CREATE TRIGGER `omoccurrencesfulltext_insert` AFTER INSERT ON `omoccurrences`
FOR EACH ROW BEGIN
  INSERT INTO omoccurrencesfulltext (
    `occid`,
    `recordedby`,
    `locality`
  ) VALUES (
    NEW.`occid`,
    NEW.`recordedby`,
    NEW.`locality`
  );
END
//


DROP TRIGGER IF EXISTS `omoccurrencesfulltext_update`//
CREATE TRIGGER `omoccurrencesfulltext_update` AFTER UPDATE ON `omoccurrences`
FOR EACH ROW BEGIN
  UPDATE omoccurrencesfulltext SET
    `recordedby` = NEW.`recordedby`,
    `locality` = NEW.`locality`
  WHERE `occid` = NEW.`occid`;
END
//


DROP TRIGGER IF EXISTS `omoccurrencesfulltext_delete`//
CREATE TRIGGER `omoccurrencesfulltext_delete` BEFORE DELETE ON `omoccurrences`
FOR EACH ROW BEGIN
  DELETE FROM omoccurrencesfulltext WHERE `occid` = OLD.`occid`;
END
//

DROP TRIGGER IF EXISTS `specprocessorrawlabelsfulltext_insert`//
CREATE TRIGGER `specprocessorrawlabelsfulltext_insert` AFTER INSERT ON `specprocessorrawlabels`
FOR EACH ROW BEGIN
  INSERT INTO specprocessorrawlabelsfulltext (
    `prlid`,
    `imgid`,
    `rawstr`
  ) VALUES (
    NEW.`prlid`,
    NEW.`imgid`,
    NEW.`rawstr`
  );
END
//

DROP TRIGGER IF EXISTS `specprocessorrawlabelsfulltext_update`//
CREATE TRIGGER `specprocessorrawlabelsfulltext_update` AFTER UPDATE ON `specprocessorrawlabels`
FOR EACH ROW BEGIN
  UPDATE specprocessorrawlabelsfulltext SET
    `imgid` = NEW.`imgid`,
    `rawstr` = NEW.`rawstr`
  WHERE `prlid` = NEW.`prlid`;
END
//

DROP TRIGGER IF EXISTS `specprocessorrawlabelsfulltext_delete`//
CREATE TRIGGER `specprocessorrawlabelsfulltext_delete` BEFORE DELETE ON `specprocessorrawlabelsfulltext`
FOR EACH ROW BEGIN
  DELETE FROM specprocessorrawlabelsfulltext WHERE `prlid` = OLD.`prlid`;
END
//

DELIMITER ;

