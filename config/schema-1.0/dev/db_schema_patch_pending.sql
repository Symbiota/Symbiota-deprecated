INSERT IGNORE INTO schemaversion (versionnumber) values ("1.1.1");


ALTER TABLE `uploadtaxa` 
  DROP INDEX `UNIQUE_sciname` ,
  ADD UNIQUE INDEX `UNIQUE_sciname` (`SciName` ASC, `RankId` ASC, `Author` ASC, `AcceptedStr` ASC);

ALTER TABLE `uploadspectemp` 
  CHANGE COLUMN `basisOfRecord` `basisOfRecord` VARCHAR(32) NULL DEFAULT NULL COMMENT 'PreservedSpecimen, LivingSpecimen, HumanObservation' ;

ALTER TABLE `images` 
  ADD INDEX `Index_images_datelastmod` (`InitialTimeStamp` ASC);

ALTER TABLE `omoccurrences`
  CHANGE COLUMN `labelProject` `labelProject` varchar(250) DEFAULT NULL,
  DROP INDEX `idx_occrecordedby`;

REPLACE omoccurrencesfulltext(occid,locality,recordedby) 
  SELECT occid, CONCAT_WS("; ", municipality, locality), recordedby
  FROM omoccurrences;

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

