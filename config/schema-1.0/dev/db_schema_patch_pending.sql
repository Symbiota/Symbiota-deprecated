INSERT IGNORE INTO schemaversion (versionnumber) values ("1.2");

ALTER TABLE `fmprojects` 
  CHANGE COLUMN `fulldescription` `fulldescription` VARCHAR(5000) NULL DEFAULT NULL ;


ALTER TABLE `uploadtaxa` 
  DROP INDEX `UNIQUE_sciname` ,
  ADD UNIQUE INDEX `UNIQUE_sciname` (`SciName` ASC, `RankId` ASC, `Author` ASC, `AcceptedStr` ASC);

ALTER TABLE `uploadspectemp` 
  CHANGE COLUMN `basisOfRecord` `basisOfRecord` VARCHAR(32) NULL DEFAULT NULL COMMENT 'PreservedSpecimen, LivingSpecimen, HumanObservation' ;

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

ALTER TABLE `omoccurrences`
  CHANGE COLUMN `labelProject` `labelProject` varchar(250) DEFAULT NULL,
  DROP INDEX `idx_occrecordedby`;

REPLACE omoccurrencesfulltext(occid,locality,recordedby) 
  SELECT occid, CONCAT_WS("; ", municipality, locality), recordedby
  FROM omoccurrences;

ALTER TABLE `omoccuredits` 
  ADD COLUMN `editType` INT NULL DEFAULT 0 COMMENT '0 = general edit, 1 = batch edit' AFTER `AppliedStatus`;

INSERT INTO omoccurpoints (occid,point)
SELECT o.occid,Point(o.decimalLatitude, o.decimalLongitude) 
FROM omoccurrences o LEFT JOIN omoccurpoints p ON o.occid = p.occid
WHERE decimalLatitude IS NOT NULL AND decimalLongitude IS NOT NULL AND p.occid IS NULL;



#Occurrence Trait/Attribute adjustments
	#Add measurementID (GUID) to tmattribute table 
	#Add measurementAccuracy field
	#Add measurementUnitID field
	#Add measurementMethod field
	#Add exportHeader for trait name
	#Add exportHeader for state name



#Review pubprofile (adminpublications)



#Collection GUID issue

