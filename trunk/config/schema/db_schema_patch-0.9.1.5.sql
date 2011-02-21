ALTER TABLE `omoccurrences` 
  ADD COLUMN `processingstatus` VARCHAR(45) AFTER `observeruid`;

ALTER TABLE `populusrawlabels` 
  RENAME TO `specprocessorrawlabels`;

CREATE TABLE `specprocessorprojects` (
  `spprid` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
  `collid` INTEGER UNSIGNED NOT NULL,
  `title` VARCHAR(100) NOT NULL,
  `specKeyPattern` VARCHAR(45),
  `speckeyretrieval` VARCHAR(45),
  `coordX1` INTEGER UNSIGNED,
  `coordX2` INTEGER UNSIGNED,
  `coordY1` INTEGER UNSIGNED,
  `coordY2` INTEGER UNSIGNED,
  `sourcePath` VARCHAR(250),
  `targetPath` VARCHAR(250),
  `imgUrl` VARCHAR(250),
  `webPixWidth` INTEGER UNSIGNED DEFAULT 1200,
  `tnPixWidth` INTEGER UNSIGNED DEFAULT 130,
  `lgPixWidth` INTEGER UNSIGNED DEFAULT 2400,
  `createTnImg` INTEGER UNSIGNED DEFAULT 1,
  `createLgImg` INTEGER UNSIGNED DEFAULT 1,
  `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`spprid`),
  CONSTRAINT `FK_specprocessorprojects_coll` FOREIGN KEY `FK_specprocessorprojects_coll` (`collid`)
    REFERENCES `omcollections` (`CollID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
)
ENGINE = InnoDB;

ALTER TABLE `omcollections` 
  CHANGE COLUMN `CollType` `CollType` VARCHAR(45) NOT NULL DEFAULT 'Preserved Specimens' COMMENT 'Live Data, Snapshot, Observations '; 

CREATE  TABLE `omoccuredits` (   
  `ocid` INT NOT NULL AUTO_INCREMENT ,   
  `occid` INT UNSIGNED NOT NULL ,   
  `FieldName` VARCHAR(45) NOT NULL ,   
  `FieldValueNew` TEXT NOT NULL ,   
  `FieldValueOld` TEXT NOT NULL ,   
  `ReviewStatus` VARCHAR(45) NOT NULL DEFAULT 'open' ,   
  `AppliedStatus` INT NOT NULL DEFAULT 0 ,   
  `uid` INT NOT NULL ,   
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp ,   
  PRIMARY KEY (`ocid`) 
); 


CREATE  TABLE `omoccurcomments` (   
  `comid` INT NOT NULL AUTO_INCREMENT ,   
  `occid` INT UNSIGNED NOT NULL ,   
  `comment` TEXT NOT NULL ,   
  `uid` INT UNSIGNED NOT NULL ,   
  `reviewstatus` INT UNSIGNED NOT NULL DEFAULT 0 ,   
  `parentcomid` INT UNSIGNED NULL ,   
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp ,   
  PRIMARY KEY (`comid`) 
); 

ALTER TABLE `omoccurrences` 
  ADD COLUMN `localitySecurityReason` VARCHAR(100) NULL  AFTER `localitySecurity`;
