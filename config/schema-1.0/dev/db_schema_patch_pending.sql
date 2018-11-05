INSERT IGNORE INTO schemaversion (versionnumber) values ("1.2");

ALTER TABLE `fmprojects` 
  CHANGE COLUMN `fulldescription` `fulldescription` VARCHAR(5000) NULL DEFAULT NULL ;


ALTER TABLE `uploadtaxa` 
  DROP INDEX `UNIQUE_sciname` ,
  ADD UNIQUE INDEX `UNIQUE_sciname` (`SciName` ASC, `RankId` ASC, `Author` ASC, `AcceptedStr` ASC);

ALTER TABLE `uploadspectemp` 
  CHANGE COLUMN `basisOfRecord` `basisOfRecord` VARCHAR(32) NULL DEFAULT NULL COMMENT 'PreservedSpecimen, LivingSpecimen, HumanObservation' ;

ALTER TABLE `uploadspectemp` 
  ADD INDEX `Index_uploadspec_othercatalognumbers` (`otherCatalogNumbers` ASC);


ALTER TABLE `taxstatus` 
  DROP INDEX `Index_hierarchy`;

ALTER TABLE `taxstatus` 
  DROP INDEX `Index_upper` ;

ALTER TABLE `taxstatus` 
  DROP PRIMARY KEY,
  ADD PRIMARY KEY USING BTREE (`tid`, `taxauthid`);

ALTER TABLE `taxstatus` 
ADD INDEX `Index_tid` (`tid` ASC);


ALTER TABLE `images` 
  ADD INDEX `Index_images_datelastmod` (`InitialTimeStamp` ASC);


ALTER TABLE `omcollectioncontacts` 
  DROP FOREIGN KEY `FK_contact_uid`;
  
ALTER TABLE `omcollectioncontacts` 
  DROP FOREIGN KEY `FK_contact_collid`;

ALTER TABLE `omcollectioncontacts` 
  CHANGE COLUMN `uid` `uid` INT(10) UNSIGNED NULL ,
  ADD COLUMN `nameoverride` VARCHAR(100) NULL AFTER `uid`,
  ADD COLUMN `emailoverride` VARCHAR(100) NULL AFTER `nameoverride`,
  ADD COLUMN `collcontid` INT NOT NULL AUTO_INCREMENT FIRST,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`collcontid`);

ALTER TABLE `omcollectioncontacts` 
  ADD CONSTRAINT `FK_contact_uid` FOREIGN KEY (`uid`)  REFERENCES `users` (`uid`)  ON DELETE SET NULL  ON UPDATE CASCADE,
  ADD CONSTRAINT `FK_contact_collid` FOREIGN KEY (`collid`)  REFERENCES `omcollections` (`collid`)  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `omcollectioncontacts` 
  ADD UNIQUE INDEX `UNIQUE_coll_contact` (`collid` ASC, `uid` ASC, `nameoverride` ASC, `emailoverride` ASC);

ALTER TABLE `omcollections` 
  ADD COLUMN `internalnotes` TEXT NULL AFTER `accessrights`;

#Tag all collection admin and editors as non-volunteer crowdsource editors   
UPDATE omcrowdsourcecentral c INNER JOIN omcrowdsourcequeue q ON c.omcsid = q.omcsid
  INNER JOIN userroles r ON c.collid = r.tablepk AND q.uidprocessor = r.uid
  SET q.isvolunteer = 0
  WHERE r.role IN("CollAdmin","CollEditor") AND q.isvolunteer = 1;


ALTER TABLE `omoccurrences`
  CHANGE COLUMN `labelProject` `labelProject` varchar(250) DEFAULT NULL,
  CHANGE COLUMN `georeferenceRemarks` `georeferenceRemarks` VARCHAR(500) NULL DEFAULT NULL,
  DROP INDEX `idx_occrecordedby`;

DELETE FROM omoccurrencesfulltext 
WHERE locality IS NULL AND recordedby IS NULL;

REPLACE INTO omoccurrencesfulltext(occid,locality,recordedby) 
  SELECT occid, CONCAT_WS("; ", municipality, locality), recordedby
  FROM omoccurrences
  WHERE municipality IS NOT NULL OR locality IS NOT NULL OR recordedby IS NOT NULL;

OPTIMIZE table omoccurrencesfulltext;

REPLACE INTO omoccurpoints (occid,point)
SELECT occid, Point(decimalLatitude, decimalLongitude) 
FROM omoccurrences 
WHERE decimalLatitude IS NOT NULL AND decimalLongitude IS NOT NULL;

OPTIMIZE table omoccurpoints;


#Add edittype field and run update query to tag batch updates (edittype = 1)
ALTER TABLE `omoccuredits` 
  ADD COLUMN `editType` INT NULL DEFAULT 0 COMMENT '0 = general edit, 1 = batch edit' AFTER `AppliedStatus`;

UPDATE omoccuredits e INNER JOIN (SELECT initialtimestamp, uid, count(DISTINCT occid) as cnt
FROM omoccuredits
GROUP BY initialtimestamp, uid
HAVING cnt > 2) as inntab ON e.initialtimestamp = inntab.initialtimestamp AND e.uid = inntab.uid
SET edittype = 1;



#Occurrence Trait/Attribute adjustments
	#Add measurementID (GUID) to tmattribute table 
	#Add measurementAccuracy field
	#Add measurementUnitID field
	#Add measurementMethod field
	#Add exportHeader for trait name
	#Add exportHeader for state name



#Review pubprofile (adminpublications)



#Collection GUID issue


DELIMITER //

DROP TRIGGER IF EXISTS `omoccurrences_insert`//
CREATE TRIGGER `omoccurrences_insert` AFTER INSERT ON `omoccurrences`
FOR EACH ROW BEGIN
	IF NEW.`decimalLatitude` IS NOT NULL AND NEW.`decimalLongitude` IS NOT NULL THEN
		INSERT INTO omoccurpoints (`occid`,`point`) 
		VALUES (NEW.`occid`,Point(NEW.`decimalLatitude`, NEW.`decimalLongitude`));
	END IF;
	IF NEW.`recordedby` IS NOT NULL OR NEW.`municipality` IS NOT NULL OR NEW.`locality` IS NOT NULL THEN
		INSERT INTO omoccurrencesfulltext (`occid`,`recordedby`,`locality`) 
		VALUES (NEW.`occid`,NEW.`recordedby`,CONCAT_WS("; ", NEW.`municipality`, NEW.`locality`));
	END IF;
END
//

DROP TRIGGER IF EXISTS `omoccurrences_update`//
CREATE TRIGGER `omoccurrences_update` AFTER UPDATE ON `omoccurrences`
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
	ELSE
		DELETE FROM omoccurpoints WHERE `occid` = NEW.`occid`;
	END IF;

	IF NEW.`recordedby` IS NOT NULL OR NEW.`municipality` IS NOT NULL OR NEW.`locality` IS NOT NULL THEN
		IF EXISTS (SELECT `occid` FROM omoccurrencesfulltext WHERE `occid`=NEW.`occid`) THEN
			UPDATE omoccurrencesfulltext 
			SET `recordedby` = NEW.`recordedby`,`locality` = CONCAT_WS("; ", NEW.`municipality`, NEW.`locality`)
			WHERE `occid` = NEW.`occid`;
		ELSE
			INSERT INTO omoccurrencesfulltext (`occid`,`recordedby`,`locality`) 
			VALUES (NEW.`occid`,NEW.`recordedby`,CONCAT_WS("; ", NEW.`municipality`, NEW.`locality`));
		END IF;
	ELSE 
		DELETE FROM omoccurrencesfulltext WHERE `occid` = NEW.`occid`;
	END IF;
END
//

DELIMITER ;
