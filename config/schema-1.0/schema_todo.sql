#Specimen attribute (traits) model
CREATE TABLE `tmtraits` (
  `traitid` INT NOT NULL AUTO_INCREMENT,
  `traitname` VARCHAR(100) NOT NULL,
  `traittype` VARCHAR(2) NOT NULL DEFAULT 'UM',
  `units` VARCHAR(45) NULL,
  `description` VARCHAR(250) NULL,
  `refurl` VARCHAR(250) NULL,
  `notes` VARCHAR(250) NULL,
  `dynamicProperties` TEXT NULL,
  `modifieduid` INT UNSIGNED NULL,
  `datelastmodified` DATETIME NULL,
  `createduid` INT UNSIGNED NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`traitid`),
  INDEX `traitsname` (`traitname` ASC),
  INDEX `FK_traits_uidcreated_idx` (`createduid` ASC),
  INDEX `FK_traits_uidmodified_idx` (`modifieduid` ASC),
  CONSTRAINT `FK_traits_uidcreated`
    FOREIGN KEY (`createduid`)   REFERENCES `users` (`uid`)   ON DELETE SET NULL   ON UPDATE CASCADE,
  CONSTRAINT `FK_traits_uidmodified`
    FOREIGN KEY (`modifieduid`)   REFERENCES `users` (`uid`)   ON DELETE SET NULL   ON UPDATE CASCADE);

CREATE TABLE `tmstates` (
  `stateid` INT NOT NULL AUTO_INCREMENT,
  `traitid` INT NOT NULL,
  `statecode` VARCHAR(2) NOT NULL,
  `statename` VARCHAR(75) NOT NULL,
  `description` VARCHAR(250) NULL,
  `notes` VARCHAR(250) NULL,
  `sortseq` INT NULL,
  `modifieduid` INT UNSIGNED NULL,
  `datelastmodified` DATETIME NULL,
  `createduid` INT UNSIGNED NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`stateid`),
  UNIQUE INDEX `traitid_code_UNIQUE` (`traitid` ASC, `statecode` ASC),
  INDEX `FK_tmstate_uidcreated_idx` (`createduid` ASC),
  INDEX `FK_tmstate_uidmodified_idx` (`modifieduid` ASC),
  CONSTRAINT `FK_tmstates_uidcreated`
    FOREIGN KEY (`createduid`)   REFERENCES `users` (`uid`)   ON DELETE SET NULL   ON UPDATE CASCADE,
  CONSTRAINT `FK_tmstates_uidmodified`
    FOREIGN KEY (`modifieduid`)   REFERENCES `users` (`uid`)   ON DELETE SET NULL   ON UPDATE CASCADE,
  CONSTRAINT `FK_tmstates_traits`
    FOREIGN KEY (`traitid`)   REFERENCES `tmtraits` (`traitid`)   ON DELETE RESTRICT   ON UPDATE CASCADE);

CREATE TABLE `tmattributes` (
  `stateid` INT NOT NULL,
  `occid` INT UNSIGNED NOT NULL,
  `modifier` VARCHAR(100) NULL,
  `xvalue` DOUBLE(15,5) NULL,
  `imgid` INT UNSIGNED NULL,
  `imagecoordinates` VARCHAR(45) NULL,
  `source` VARCHAR(250) NULL,
  `notes` VARCHAR(250) NULL,
  `modifieduid` INT UNSIGNED NULL,
  `datelastmodified` DATETIME NULL,
  `createduid` INT UNSIGNED NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`stateid`,`occid`),
  INDEX `FK_tmattr_stateid_idx` (`stateid` ASC),
  INDEX `FK_tmattr_occid_idx` (`occid` ASC),
  INDEX `FK_tmattr_imgid_idx` (`imgid` ASC),
  INDEX `FK_attr_uidcreate_idx` (`createduid` ASC),
  INDEX `FK_tmattr_uidmodified_idx` (`modifieduid` ASC),
  CONSTRAINT `FK_tmattr_stateid`
    FOREIGN KEY (`stateid`)   REFERENCES `tmstates` (`stateid`)   ON DELETE CASCADE   ON UPDATE CASCADE,
  CONSTRAINT `FK_tmattr_occid`
    FOREIGN KEY (`occid`)   REFERENCES `omoccurrences` (`occid`)   ON DELETE CASCADE   ON UPDATE CASCADE,
  CONSTRAINT `FK_tmattr_imgid`
    FOREIGN KEY (`imgid`)  REFERENCES `images` (`imgid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  CONSTRAINT `FK_tmattr_uidcreate`
    FOREIGN KEY (`createduid`)   REFERENCES `users` (`uid`)   ON DELETE SET NULL   ON UPDATE CASCADE,
  CONSTRAINT `FK_tmattr_uidmodified`
    FOREIGN KEY (`modifieduid`)   REFERENCES `users` (`uid`)   ON DELETE SET NULL   ON UPDATE CASCADE
);

CREATE TABLE `tmtraittaxalink` (
  `traitid` INT NOT NULL,
  `tid` INT UNSIGNED NOT NULL,
  `relation` VARCHAR(45) NOT NULL DEFAULT 'include',
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`traitid`, `tid`),
  INDEX `FK_traittaxalink_traitid_idx` (`tid` ASC),
  INDEX `FK_traittaxalink_tid_idx` (`tid` ASC),
  CONSTRAINT `FK_traittaxalink_traitid`
    FOREIGN KEY (`traitid`)  REFERENCES `tmtraits` (`traitid`)  ON DELETE CASCADE  ON UPDATE CASCADE,
  CONSTRAINT `FK_traittaxalink_tid`
    FOREIGN KEY (`tid`)  REFERENCES `taxa` (`TID`)  ON DELETE CASCADE  ON UPDATE CASCADE
);

#Occurrence associations
ALTER TABLE `omoccurassococcurrences` 
  DROP FOREIGN KEY `omossococcur_occid`,
  DROP FOREIGN KEY `omossococcur_occidassoc`;

ALTER TABLE `omoccurassococcurrences` 
  DROP COLUMN `createdby`,
  CHANGE COLUMN `aoid` `associd` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT ,
  CHANGE COLUMN `sciname` `verbatimsciname` VARCHAR(250) NULL DEFAULT NULL ,
  CHANGE COLUMN `tid` `tid` INT(11) UNSIGNED NULL DEFAULT NULL ,
  ADD COLUMN `createduid` INT UNSIGNED NULL AFTER `notes`,
  ADD COLUMN `datelastmodified` DATETIME NULL AFTER `createduid`,
  ADD COLUMN `modifieduid` INT UNSIGNED NULL AFTER `datelastmodified`,
  ADD INDEX `FK_occurassoc_tid_idx` (`tid` ASC),
  ADD INDEX `FK_occurassoc_uidmodified_idx` (`modifieduid` ASC),
  ADD INDEX `FK_occurassoc_uidcreated_idx` (`createduid` ASC);

ALTER TABLE `omoccurassococcurrences` 
  ADD CONSTRAINT `FK_occurassoc_occid`
    FOREIGN KEY (`occid`)   REFERENCES `omoccurrences` (`occid`)    ON DELETE CASCADE    ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_occurassoc_occidassoc`
    FOREIGN KEY (`occidassociate`)    REFERENCES `omoccurrences` (`occid`)    ON DELETE CASCADE    ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_occurassoc_tid`
    FOREIGN KEY (`tid`)  REFERENCES `taxa` (`TID`)  ON DELETE RESTRICT  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_occurassoc_uidmodified`
    FOREIGN KEY (`modifieduid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_occurassoc_uidcreated`
    FOREIGN KEY (`createduid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE;

ALTER TABLE `omoccurassococcurrences` 
  RENAME TO  `omoccurassociations` ;

ALTER TABLE `omoccurassociations` 
  ADD INDEX `INDEX_verbatimSciname` (`verbatimsciname` ASC);


#Checklist changes
ALTER TABLE `fmvouchers` 
  DROP COLUMN `Collector`;


#Misc
ALTER TABLE `uploadspectemp` 
  ADD COLUMN `exsiccatiIdentifier` INT NULL AFTER `genericcolumn2`,
  ADD COLUMN `exsiccatiNumber` VARCHAR(45) NULL AFTER `exsiccatiIdentifier`;

ALTER TABLE `uploadtaxa` 
  ADD COLUMN `ErrorStatus` VARCHAR(150) NULL AFTER `Hybrid`,
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
  CHANGE COLUMN `sourceIdentifier` `sourceIdentifier` VARCHAR(150) NULL DEFAULT NULL,
  ADD COLUMN `referenceUrl` VARCHAR(255) NULL AFTER `sourceurl`;


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

#Needed for FP functions
CREATE INDEX idx_taxacreated ON taxa(initialtimestamp);

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


#identification key activator field





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

ALTER TABLE `omcollectionstats`
	MODIFY COLUMN `dynamicProperties` longtext NULL AFTER `uploadedby`;
  
ALTER TABLE `glossary`
	ADD COLUMN `resourceurl`  varchar(600) NULL AFTER `notes`;
	MODIFY COLUMN `definition`  varchar(1000) NULL DEFAULT NULL AFTER `term`;

ALTER TABLE `taxadescrblock`
	MODIFY COLUMN `caption`  varchar(40) NULL DEFAULT NULL AFTER `tid`;




