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

ALTER TABLE `omoccurrences` 
  ADD INDEX `Index_locality` (`locality`(100) ASC),
  ADD INDEX `Index_otherCatalogNumbers` (`otherCatalogNumbers` ASC);

ALTER TABLE `omoccurrences` 
  ADD COLUMN `eventID` VARCHAR(45) NULL AFTER `fieldnumber`;

ALTER TABLE `omcollectionstats` 
  CHANGE COLUMN `dynamicProperties` `dynamicProperties` TEXT NULL DEFAULT NULL ;

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








