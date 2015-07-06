//Multi-language support
CREATE TABLE `adminlangpage` (
  `langpageid` INT NOT NULL AUTO_INCREMENT,
  `pagename` VARCHAR(45) NOT NULL,
  `pagepath` VARCHAR(150) NOT NULL,
  `username` VARCHAR(45) NOT NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`langpageid`),
  INDEX `index_pagename` (`pagename` ASC)
);

ALTER TABLE `adminlangpage` 
  ADD UNIQUE INDEX `pagename_UNIQUE` (`pagename` ASC),
  ADD UNIQUE INDEX `pagepath_UNIQUE` (`pagepath` ASC);

CREATE TABLE `adminlangvariables` (
  `pagevarid` INT NOT NULL AUTO_INCREMENT,
  `langpageid` INT NOT NULL,
  `variablename` VARCHAR(45) NOT NULL,
  `section` VARCHAR(45) NULL,
  `username` VARCHAR(45) NOT NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`pagevarid`),
  UNIQUE INDEX `langpageid_UNIQUE` (`langpageid` ASC),
  UNIQUE INDEX `variablename_UNIQUE` (`variablename` ASC));

ALTER TABLE `adminlangvariables` 
  ADD CONSTRAINT `FK_langpageid`
    FOREIGN KEY (`langpageid`)
    REFERENCES `adminlangpage` (`langpageid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

CREATE TABLE `adminlangtranslation` (
  `translationid` INT NOT NULL AUTO_INCREMENT,
  `pagevarid` INT NOT NULL,
  `langid` INT NOT NULL,
  `translation` TEXT NOT NULL,
  `notes` VARCHAR(250) NULL,
  `uid` INT UNSIGNED NULL,
  `uidmodified` INT UNSIGNED NULL,
  `datelastmodified` DATETIME NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`translationid`),
  UNIQUE INDEX `pagevarid_UNIQUE` (`pagevarid` ASC),
  UNIQUE INDEX `langid_UNIQUE` (`langid` ASC),
  INDEX `FK_uid_idx` (`uid` ASC),
    CONSTRAINT `FK_pagevariableid`
      FOREIGN KEY (`pagevarid`) REFERENCES `adminlangvariables` (`pagevarid`)
      ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT `FK_langid`
      FOREIGN KEY (`langid`) REFERENCES `adminlanguages` (`langid`)
      ON DELETE RESTRICT  ON UPDATE RESTRICT,
    CONSTRAINT `FK_uid`
      FOREIGN KEY (`uid`) REFERENCES `users` (`uid`)
      ON DELETE SET NULL  ON UPDATE SET NULL,
    CONSTRAINT `FK_uidmodified`
      FOREIGN KEY (`uid`) REFERENCES `users` (`uid`)
      ON DELETE SET NULL  ON UPDATE SET NULL
);


ALTER TABLE `uploadtaxa` 
  ADD COLUMN `uploadStatus` VARCHAR(45) NULL AFTER `Hybrid`,
  ADD COLUMN `RankName` VARCHAR(45) NULL AFTER `RankId`,
  DROP COLUMN `KingdomID`;

ALTER TABLE `uploadtaxa` 
  ADD INDEX `parentStr_index` (`ParentStr` ASC),
  ADD INDEX `acceptedStr_index` (`AcceptedStr` ASC),
  ADD INDEX `unitname1_index` (`UnitName1` ASC),
  ADD INDEX `sourceParentId_index` (`SourceParentId` ASC),
  ADD INDEX `acceptance_index` (`Acceptance` ASC);

ALTER TABLE `taxa` 
  DROP COLUMN `KingdomID`,
  DROP COLUMN `kingdomName`;

ALTER TABLE `taxa` 
  DROP INDEX `sciname_unique` ,
  ADD UNIQUE INDEX `sciname_unique` (`SciName` ASC, `RankId` ASC);

ALTER TABLE `taxa` 
  ADD INDEX `sciname_index` (`SciName` ASC);

ALTER TABLE `taxonunits` 
  DROP COLUMN `kingdomid`,
  ADD UNIQUE INDEX `UNIQUE_taxonunits` (`kingdomName` ASC, `rankid` ASC);

ALTER TABLE `specprocessorprojects` 
  ADD COLUMN `projecttype` VARCHAR(45) NULL AFTER `title`,
  ADD COLUMN `lastrundate` DATE NULL AFTER `source`,
  ADD COLUMN `patternReplace` VARCHAR(45) NULL AFTER `specKeyPattern`,
  ADD COLUMN `replaceStr` VARCHAR(45) NULL AFTER `pattReplace`;

ALTER TABLE `images` 
  CHANGE COLUMN `sourceIdentifier` `sourceIdentifier` VARCHAR(100) NULL DEFAULT NULL ;

ALTER TABLE `omcollections` 
  ADD COLUMN `dwcaUrl` VARCHAR(75) NULL AFTER `publishToGbif`;

ALTER TABLE `omcollectionstats` 
  CHANGE COLUMN `dynamicProperties` `dynamicProperties` TEXT NULL DEFAULT NULL ;

ALTER TABLE `omoccurrences` 
  ADD INDEX `Index_locality` (`locality`(100) ASC),
  ADD INDEX `Index_otherCatalogNumbers` (`otherCatalogNumbers` ASC);

ALTER TABLE `omoccurrences` 
  ADD COLUMN `eventID` VARCHAR(45) NULL AFTER `fieldnumber`;


DROP TABLE `userpermissions`;


# Deal with state and country definitions with the rare species state lists



# Event date range within omoccurrence table


#Need to add condition to run only if collid exists
ALTER TABLE `omoccurrencesfulltext` 
  DROP COLUMN `collid`,
  DROP INDEX `Index_occurfull_collid` ;


# Add one to many relationship between collections and institutions
# Add one to many relationship between collection to agent



#Create an occurrence type table



#Add one to many relationship between collections and institutions



#Add one to many relationship between collection to agent



#Review pubprofile (adminpublications)


#Collection GUID issue






SET FOREIGN_KEY_CHECKS=0;

TRUNCATE TABLE `omoccurpoints`;

SET FOREIGN_KEY_CHECKS=1;

INSERT INTO omoccurpoints (occid,point)
SELECT occid,Point(decimalLatitude, decimalLongitude) FROM omoccurrences WHERE decimalLatitude IS NOT NULL AND decimalLongitude IS NOT NULL;

DELIMITER //
DROP TRIGGER IF EXISTS `omoccurrencesfulltext_insert`//
CREATE TRIGGER `omoccurrencesfulltextpoint_insert` AFTER INSERT ON `omoccurrences`
FOR EACH ROW BEGIN
	IF NEW.`decimalLatitude` IS NOT NULL AND NEW.`decimalLongitude` IS NOT NULL THEN
		INSERT INTO omoccurpoints (`occid`,`point`) 
		VALUES (NEW.`occid`,Point(NEW.`decimalLatitude`, NEW.`decimalLongitude`));
	END IF;
	INSERT INTO omoccurrencesfulltext (`occid`,`recordedby`,`locality`) 
	VALUES (NEW.`occid`,NEW.`recordedby`,NEW.`locality`);
END
//

DROP TRIGGER IF EXISTS `omoccurrencesfulltext_update`//
CREATE TRIGGER `omoccurrencesfulltextpoint_update` AFTER UPDATE ON `omoccurrences`
FOR EACH ROW BEGIN
	IF NEW.`decimalLatitude` IS NOT NULL AND NEW.`decimalLongitude` IS NOT NULL THEN
		IF EXISTS (SELECT `occid` FROM omoccurpoints WHERE `occid`=NEW.`occid`) THEN
			UPDATE omoccurpoints 
			SET `point` = Point(NEW.`decimalLatitude`, NEW.`decimalLongitude`)
			WHERE `occid` = NEW.`occid`;
		ELSE 
			INSERT INTO omoccurpoints (`occid`,`point`) 
			VALUES (NEW.`occid`,Point(NEW.`decimalLatitude`, NEW.`decimalLongitude`));
		END IF;
	END IF;
	UPDATE omoccurrencesfulltext 
	SET `recordedby` = NEW.`recordedby`,`locality` = NEW.`locality`
	WHERE `occid` = NEW.`occid`;
END
//

DROP TRIGGER IF EXISTS `omoccurrencesfulltext_delete`//
CREATE TRIGGER `omoccurrencesfulltextpoint_delete` BEFORE DELETE ON `omoccurrences`
FOR EACH ROW BEGIN
	DELETE FROM omoccurpoints WHERE `occid` = OLD.`occid`;
	DELETE FROM omoccurrencesfulltext WHERE `occid` = OLD.`occid`;
END
//

DELIMITER ;








