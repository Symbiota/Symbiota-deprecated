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
  `refurl` VARCHAR(250) NULL,
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
  `statuscode` TINYINT NULL,
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
  INDEX `FK_traittaxalink_traitid_idx` (`traitid` ASC),
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
  DROP COLUMN `kingdomName`,
  ADD COLUMN `TSN` int(10) NULL AFTER `SecurityStatus`,
  DROP INDEX `sciname_unique`,
  ADD UNIQUE INDEX `sciname_unique` (`SciName` ASC, `RankId` ASC),
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
  ADD COLUMN `eventID` VARCHAR(45) NULL AFTER `fieldnumber`,
  ADD COLUMN `waterBody`  varchar(255) NULL AFTER `municipality`;


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
	
ALTER TABLE `taxadescrblock`
	MODIFY COLUMN `caption`  varchar(40) NULL DEFAULT NULL AFTER `tid`;
  
ALTER TABLE `glossary`
	ADD COLUMN `resourceurl`  varchar(600) NULL AFTER `notes`,
	MODIFY COLUMN `definition`  varchar(1000) NULL DEFAULT NULL AFTER `term`,
	MODIFY COLUMN `source`  varchar(1000) NULL DEFAULT NULL AFTER `language`,
	ADD COLUMN `translator`  varchar(250) NULL AFTER `source`,
	ADD COLUMN `author`  varchar(250) NULL AFTER `translator`;
	
ALTER TABLE `glossaryimages`
	ADD COLUMN `createdBy`  varchar(250) NULL AFTER `notes`;

ALTER TABLE `glossarytaxalink` 
	DROP FOREIGN KEY `glossarytaxalink_ibfk_1`,
	DROP FOREIGN KEY `glossarytaxalink_ibfk_2`;
	
ALTER TABLE `glossarytermlink` DROP FOREIGN KEY `glossarytermlink_ibfk_1`;

CREATE TABLE `uploadglossary` (
  `term` varchar(150) DEFAULT NULL,
  `definition` varchar(1000) DEFAULT NULL,
  `language` varchar(45) DEFAULT NULL,
  `source` varchar(1000) DEFAULT NULL,
  `author` varchar(250) DEFAULT NULL,
  `translator` varchar(250) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `resourceurl` varchar(600) DEFAULT NULL,
  `tidStr` varchar(100) DEFAULT NULL,
  `synonym` tinyint(1) DEFAULT NULL,
  `newGroupId` int(10) DEFAULT NULL,
  `currentGroupId` int(10) DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `term_index` (`term`),
  KEY `relatedterm_index` (`newGroupId`)
);

-- Paleo Tables
CREATE TABLE `paleochronostratigraphy` (
  `chronoId` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Eon` varchar(255) DEFAULT NULL,
  `Era` varchar(255) DEFAULT NULL,
  `Period` varchar(255) DEFAULT NULL,
  `Epoch` varchar(255) DEFAULT NULL,
  `Stage` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`chronoId`),
  INDEX `Eon` (`Eon`),
  INDEX `Era` (`Era`),
  INDEX `Period` (`Period`),
  INDEX `Epoch` (`Epoch`),
  INDEX `Stage` (`Stage`)
);

INSERT INTO `paleochronostratigraphy` VALUES ('1', 'Hadean', null, null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('2', 'Archean', null, null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('3', 'Archean', 'Eoarchean', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('4', 'Archean', 'Paleoarchean', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('5', 'Archean', 'Mesoarchean', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('6', 'Archean', 'Neoarchean', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('7', 'Proterozoic', null, null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('8', 'Proterozoic', 'Paleoproterozoic', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('9', 'Proterozoic', 'Paleoproterozoic', 'Siderian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('10', 'Proterozoic', 'Paleoproterozoic', 'Rhyacian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('11', 'Proterozoic', 'Paleoproterozoic', 'Orosirian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('12', 'Proterozoic', 'Paleoproterozoic', 'Statherian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('13', 'Proterozoic', 'Mesoproterozoic', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('14', 'Proterozoic', 'Mesoproterozoic', 'Calymmian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('15', 'Proterozoic', 'Mesoproterozoic', 'Ectasian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('16', 'Proterozoic', 'Mesoproterozoic', 'Stenian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('17', 'Proterozoic', 'Neoproterozoic', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('18', 'Proterozoic', 'Neoproterozoic', 'Tonian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('19', 'Proterozoic', 'Neoproterozoic', 'Gryogenian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('20', 'Proterozoic', 'Neoproterozoic', 'Ediacaran', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('21', 'Phanerozoic', null, null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('22', 'Phanerozoic', 'Paleozoic', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('23', 'Phanerozoic', 'Paleozoic', 'Cambrian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('24', 'Phanerozoic', 'Paleozoic', 'Cambrian', 'Lower Cambrian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('25', 'Phanerozoic', 'Paleozoic', 'Cambrian', 'Middle Cambrian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('26', 'Phanerozoic', 'Paleozoic', 'Cambrian', 'Upper Cambrian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('27', 'Phanerozoic', 'Paleozoic', 'Ordovician', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('28', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Lower Ordovician', null);
INSERT INTO `paleochronostratigraphy` VALUES ('29', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Lower Ordovician', 'Tremadocian');
INSERT INTO `paleochronostratigraphy` VALUES ('30', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Lower Ordovician', 'Floian');
INSERT INTO `paleochronostratigraphy` VALUES ('31', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Middle Ordovician', null);
INSERT INTO `paleochronostratigraphy` VALUES ('32', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Middle Ordovician', 'Dapingian');
INSERT INTO `paleochronostratigraphy` VALUES ('33', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Middle Ordovician', 'Darriwilian');
INSERT INTO `paleochronostratigraphy` VALUES ('34', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Upper Ordivician', null);
INSERT INTO `paleochronostratigraphy` VALUES ('35', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Upper Ordivician', 'Sandbian');
INSERT INTO `paleochronostratigraphy` VALUES ('36', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Upper Ordivician', 'Katian');
INSERT INTO `paleochronostratigraphy` VALUES ('37', 'Phanerozoic', 'Paleozoic', 'Ordovician', 'Upper Ordivician', 'Hirnantian');
INSERT INTO `paleochronostratigraphy` VALUES ('38', 'Phanerozoic', 'Paleozoic', 'Silurian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('39', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Llandovery', null);
INSERT INTO `paleochronostratigraphy` VALUES ('40', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Llandovery', 'Rhuddanian');
INSERT INTO `paleochronostratigraphy` VALUES ('41', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Llandovery', 'Aeronian');
INSERT INTO `paleochronostratigraphy` VALUES ('42', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Llandovery', 'Telychian');
INSERT INTO `paleochronostratigraphy` VALUES ('43', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Wenlock', null);
INSERT INTO `paleochronostratigraphy` VALUES ('44', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Wenlock', 'Sheinwoodian');
INSERT INTO `paleochronostratigraphy` VALUES ('45', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Wenlock', 'Homerian');
INSERT INTO `paleochronostratigraphy` VALUES ('46', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Ludlow', null);
INSERT INTO `paleochronostratigraphy` VALUES ('47', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Ludlow', 'Gorstian');
INSERT INTO `paleochronostratigraphy` VALUES ('48', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Ludlow', 'Ludfordian');
INSERT INTO `paleochronostratigraphy` VALUES ('49', 'Phanerozoic', 'Paleozoic', 'Silurian', 'Pridoli', null);
INSERT INTO `paleochronostratigraphy` VALUES ('50', 'Phanerozoic', 'Paleozoic', 'Devonian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('51', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Lower Devonian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('52', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Lower Devonian', 'Lochkovian');
INSERT INTO `paleochronostratigraphy` VALUES ('53', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Lower Devonian', 'Pragian');
INSERT INTO `paleochronostratigraphy` VALUES ('54', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Lower Devonian', 'Emsian');
INSERT INTO `paleochronostratigraphy` VALUES ('55', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Middle Devonian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('56', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Middle Devonian', 'Eifelian');
INSERT INTO `paleochronostratigraphy` VALUES ('57', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Middle Devonian', 'Givetian');
INSERT INTO `paleochronostratigraphy` VALUES ('58', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Upper Devonian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('59', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Upper Devonian', 'Frasnian');
INSERT INTO `paleochronostratigraphy` VALUES ('60', 'Phanerozoic', 'Paleozoic', 'Devonian', 'Upper Devonian', 'Famennian');
INSERT INTO `paleochronostratigraphy` VALUES ('61', 'Phanerozoic', 'Paleozoic', 'Carboniferous', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('62', 'Phanerozoic', 'Paleozoic', 'Carboniferous', 'Mississippian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('63', 'Phanerozoic', 'Paleozoic', 'Carboniferous', 'Mississippian', 'Lower Mississippian');
INSERT INTO `paleochronostratigraphy` VALUES ('64', 'Phanerozoic', 'Paleozoic', 'Carboniferous', 'Mississippian', 'Middle Mississippian');
INSERT INTO `paleochronostratigraphy` VALUES ('65', 'Phanerozoic', 'Paleozoic', 'Carboniferous', 'Mississippian', 'Upper Mississippian');
INSERT INTO `paleochronostratigraphy` VALUES ('66', 'Phanerozoic', 'Paleozoic', 'Carboniferous', 'Pennsylvanian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('67', 'Phanerozoic', 'Paleozoic', 'Carboniferous', 'Pennsylvanian', 'Lower Pennsylvanian');
INSERT INTO `paleochronostratigraphy` VALUES ('68', 'Phanerozoic', 'Paleozoic', 'Carboniferous', 'Pennsylvanian', 'Middle Pennsylvanian');
INSERT INTO `paleochronostratigraphy` VALUES ('69', 'Phanerozoic', 'Paleozoic', 'Carboniferous', 'Pennsylvanian', 'Upper Pennsylvanian');
INSERT INTO `paleochronostratigraphy` VALUES ('70', 'Phanerozoic', 'Paleozoic', 'Permian', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('71', 'Phanerozoic', 'Paleozoic', 'Permian', 'Cisuralian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('72', 'Phanerozoic', 'Paleozoic', 'Permian', 'Cisuralian', 'Asselian');
INSERT INTO `paleochronostratigraphy` VALUES ('73', 'Phanerozoic', 'Paleozoic', 'Permian', 'Cisuralian', 'Sakmarian');
INSERT INTO `paleochronostratigraphy` VALUES ('74', 'Phanerozoic', 'Paleozoic', 'Permian', 'Cisuralian', 'Artinskian');
INSERT INTO `paleochronostratigraphy` VALUES ('75', 'Phanerozoic', 'Paleozoic', 'Permian', 'Cisuralian', 'Kungurian');
INSERT INTO `paleochronostratigraphy` VALUES ('76', 'Phanerozoic', 'Paleozoic', 'Permian', 'Guadalupian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('77', 'Phanerozoic', 'Paleozoic', 'Permian', 'Guadalupian', 'Roadian');
INSERT INTO `paleochronostratigraphy` VALUES ('78', 'Phanerozoic', 'Paleozoic', 'Permian', 'Guadalupian', 'Wordian');
INSERT INTO `paleochronostratigraphy` VALUES ('79', 'Phanerozoic', 'Paleozoic', 'Permian', 'Guadalupian', 'Capitanian');
INSERT INTO `paleochronostratigraphy` VALUES ('80', 'Phanerozoic', 'Paleozoic', 'Permian', 'Lopingian', null);
INSERT INTO `paleochronostratigraphy` VALUES ('81', 'Phanerozoic', 'Paleozoic', 'Permian', 'Lopingian', 'Wuchiapingian');
INSERT INTO `paleochronostratigraphy` VALUES ('82', 'Phanerozoic', 'Paleozoic', 'Permian', 'Lopingian', 'Changhsingian');
INSERT INTO `paleochronostratigraphy` VALUES ('83', 'Phanerozoic', 'Mesozoic', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('84', 'Phanerozoic', 'Mesozoic', 'Triassic', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('85', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Lower Triassic', null);
INSERT INTO `paleochronostratigraphy` VALUES ('86', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Lower Triassic', 'Induan');
INSERT INTO `paleochronostratigraphy` VALUES ('87', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Lower Triassic', 'Olenekian');
INSERT INTO `paleochronostratigraphy` VALUES ('88', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Middle Triassic', null);
INSERT INTO `paleochronostratigraphy` VALUES ('89', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Middle Triassic', 'Anisian');
INSERT INTO `paleochronostratigraphy` VALUES ('90', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Middle Triassic', 'Ladinian');
INSERT INTO `paleochronostratigraphy` VALUES ('91', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Upper Triassic', null);
INSERT INTO `paleochronostratigraphy` VALUES ('92', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Upper Triassic', 'Carnian');
INSERT INTO `paleochronostratigraphy` VALUES ('93', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Upper Triassic', 'Norian');
INSERT INTO `paleochronostratigraphy` VALUES ('94', 'Phanerozoic', 'Mesozoic', 'Triassic', 'Upper Triassic', 'Rhaetian');
INSERT INTO `paleochronostratigraphy` VALUES ('95', 'Phanerozoic', 'Mesozoic', 'Jurassic', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('96', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Lower Jurassic', null);
INSERT INTO `paleochronostratigraphy` VALUES ('97', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Lower Jurassic', 'Hettangian');
INSERT INTO `paleochronostratigraphy` VALUES ('98', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Lower Jurassic', 'Sinemurian');
INSERT INTO `paleochronostratigraphy` VALUES ('99', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Lower Jurassic', 'Pliensbachian');
INSERT INTO `paleochronostratigraphy` VALUES ('100', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Lower Jurassic', 'Toarcian');
INSERT INTO `paleochronostratigraphy` VALUES ('101', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Middle Jurassic', null);
INSERT INTO `paleochronostratigraphy` VALUES ('102', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Middle Jurassic', 'Aalenian');
INSERT INTO `paleochronostratigraphy` VALUES ('103', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Middle Jurassic', 'Bajocian');
INSERT INTO `paleochronostratigraphy` VALUES ('104', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Middle Jurassic', 'Bathonian');
INSERT INTO `paleochronostratigraphy` VALUES ('105', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Middle Jurassic', 'Callovian');
INSERT INTO `paleochronostratigraphy` VALUES ('106', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Upper Jurassic', null);
INSERT INTO `paleochronostratigraphy` VALUES ('107', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Upper Jurassic', 'Oxfordian');
INSERT INTO `paleochronostratigraphy` VALUES ('108', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Upper Jurassic', 'Kimmeridgian');
INSERT INTO `paleochronostratigraphy` VALUES ('109', 'Phanerozoic', 'Mesozoic', 'Jurassic', 'Upper Jurassic', 'Tithonian');
INSERT INTO `paleochronostratigraphy` VALUES ('110', 'Phanerozoic', 'Mesozoic', 'Cretaceous', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('111', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Lower Cretaceous', null);
INSERT INTO `paleochronostratigraphy` VALUES ('112', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Lower Cretaceous', 'Berriasian');
INSERT INTO `paleochronostratigraphy` VALUES ('113', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Lower Cretaceous', 'Valanginian');
INSERT INTO `paleochronostratigraphy` VALUES ('114', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Lower Cretaceous', 'Hauterivian');
INSERT INTO `paleochronostratigraphy` VALUES ('115', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Lower Cretaceous', 'Barremian');
INSERT INTO `paleochronostratigraphy` VALUES ('116', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Lower Cretaceous', 'Aptian');
INSERT INTO `paleochronostratigraphy` VALUES ('117', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Lower Cretaceous', 'Albian');
INSERT INTO `paleochronostratigraphy` VALUES ('118', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Upper Cretaceous', null);
INSERT INTO `paleochronostratigraphy` VALUES ('119', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Upper Cretaceous', 'Cenomanian');
INSERT INTO `paleochronostratigraphy` VALUES ('120', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Upper Cretaceous', 'Turonian');
INSERT INTO `paleochronostratigraphy` VALUES ('121', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Upper Cretaceous', 'Coniacian');
INSERT INTO `paleochronostratigraphy` VALUES ('122', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Upper Cretaceous', 'Santonian');
INSERT INTO `paleochronostratigraphy` VALUES ('123', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Upper Cretaceous', 'Campanian');
INSERT INTO `paleochronostratigraphy` VALUES ('124', 'Phanerozoic', 'Mesozoic', 'Cretaceous', 'Upper Cretaceous', 'Maastrichtian');
INSERT INTO `paleochronostratigraphy` VALUES ('125', 'Phanerozoic', 'Cenozoic', null, null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('126', 'Phanerozoic', 'Cenozoic', 'Paleogene', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('127', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Paleocene', null);
INSERT INTO `paleochronostratigraphy` VALUES ('128', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Paleocene', 'Danian');
INSERT INTO `paleochronostratigraphy` VALUES ('129', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Paleocene', 'Selandian');
INSERT INTO `paleochronostratigraphy` VALUES ('130', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Paleocene', 'Thanetian');
INSERT INTO `paleochronostratigraphy` VALUES ('131', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Eocene', null);
INSERT INTO `paleochronostratigraphy` VALUES ('132', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Eocene', 'Ypresian');
INSERT INTO `paleochronostratigraphy` VALUES ('133', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Eocene', 'Lutetian');
INSERT INTO `paleochronostratigraphy` VALUES ('134', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Eocene', 'Bartonian');
INSERT INTO `paleochronostratigraphy` VALUES ('135', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Eocene', 'Priabonian');
INSERT INTO `paleochronostratigraphy` VALUES ('136', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Oligocene', null);
INSERT INTO `paleochronostratigraphy` VALUES ('137', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Oligocene', 'Rupelian');
INSERT INTO `paleochronostratigraphy` VALUES ('138', 'Phanerozoic', 'Cenozoic', 'Paleogene', 'Oligocene', 'Chattian');
INSERT INTO `paleochronostratigraphy` VALUES ('139', 'Phanerozoic', 'Cenozoic', 'Neogene', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('140', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Miocene', null);
INSERT INTO `paleochronostratigraphy` VALUES ('141', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Miocene', 'Aquitanian');
INSERT INTO `paleochronostratigraphy` VALUES ('142', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Miocene', 'Burdigalian');
INSERT INTO `paleochronostratigraphy` VALUES ('143', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Miocene', 'Langhian');
INSERT INTO `paleochronostratigraphy` VALUES ('144', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Miocene', 'Serravallian');
INSERT INTO `paleochronostratigraphy` VALUES ('145', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Miocene', 'Tortonian');
INSERT INTO `paleochronostratigraphy` VALUES ('146', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Miocene', 'Messinian');
INSERT INTO `paleochronostratigraphy` VALUES ('147', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Pliocene', null);
INSERT INTO `paleochronostratigraphy` VALUES ('148', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Pliocene', 'Zanclean');
INSERT INTO `paleochronostratigraphy` VALUES ('149', 'Phanerozoic', 'Cenozoic', 'Neogene', 'Pliocene', 'Piacenzian');
INSERT INTO `paleochronostratigraphy` VALUES ('150', 'Phanerozoic', 'Cenozoic', 'Quaternary', null, null);
INSERT INTO `paleochronostratigraphy` VALUES ('151', 'Phanerozoic', 'Cenozoic', 'Quaternary', 'Pleistocene', null);
INSERT INTO `paleochronostratigraphy` VALUES ('152', 'Phanerozoic', 'Cenozoic', 'Quaternary', 'Pleistocene', 'Gelasian');
INSERT INTO `paleochronostratigraphy` VALUES ('153', 'Phanerozoic', 'Cenozoic', 'Quaternary', 'Pleistocene', 'Calabrian');
INSERT INTO `paleochronostratigraphy` VALUES ('154', 'Phanerozoic', 'Cenozoic', 'Quaternary', 'Pleistocene', 'Middle Pleistocene');
INSERT INTO `paleochronostratigraphy` VALUES ('155', 'Phanerozoic', 'Cenozoic', 'Quaternary', 'Pleistocene', 'Upper Pleistocene');
INSERT INTO `paleochronostratigraphy` VALUES ('156', 'Phanerozoic', 'Cenozoic', 'Quaternary', 'Holocene', null);

CREATE TABLE `omoccurlithostratigraphy` (
  `occid` int(10) unsigned NOT NULL,
  `chronoId` int(10) unsigned NOT NULL,
  `Group` varchar(255) DEFAULT NULL,
  `Formation` varchar(255) DEFAULT NULL,
  `Member` varchar(255) DEFAULT NULL,
  `Bed` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`occid`,`chronoId`),
  INDEX `FK_occurlitho_chronoid` (`chronoId`),
  INDEX `FK_occurlitho_occid` (`occid`),
  INDEX `Group` (`Group`),
  INDEX `Formation` (`Formation`),
  INDEX `Member` (`Member`),
  CONSTRAINT `FK_occurlitho_chronoid` FOREIGN KEY (`chronoId`) REFERENCES `paleochronostratigraphy` (`chronoId`) ON UPDATE CASCADE,
  CONSTRAINT `FK_occurlitho_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON UPDATE CASCADE
);