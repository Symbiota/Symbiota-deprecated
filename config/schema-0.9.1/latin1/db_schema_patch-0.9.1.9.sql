ALTER TABLE `omoccurrences` 
  DROP INDEX `Index_collid`, 
  ADD UNIQUE INDEX `Index_collid` (`collid` ASC, `dbpk` ASC) ;

ALTER TABLE `omoccurrences`
  ADD INDEX `Index_catalognumber` (`catalogNumber` ASC) ;

ALTER TABLE `images`    
  ADD CONSTRAINT `FK_photographeruid`   
    FOREIGN KEY (`photographeruid` )   
    REFERENCES `users` (`uid` )   ON DELETE SET NULL   ON UPDATE CASCADE , 
  ADD INDEX `FK_photographeruid` (`photographeruid` ASC) ; 

ALTER TABLE `userpermissions` ADD COLUMN `assignedby` VARCHAR(45) NULL  AFTER `pname` ; 

ALTER TABLE `lkupcountry` CHANGE COLUMN `iso` `iso` VARCHAR(2) NULL; 
ALTER TABLE `lkupstateprovince` CHANGE COLUMN `abbrev` `abbrev` VARCHAR(2) NULL;

ALTER TABLE `omoccurrences` 
  DROP COLUMN `CollectorInitials` , 
  CHANGE COLUMN `CollectorFamilyName` `recordedById` INT UNSIGNED NULL DEFAULT NULL; 

CREATE  TABLE `omcollectors` (
  `recordedById` INT UNSIGNED NOT NULL AUTO_INCREMENT ,
  `familyname` VARCHAR(45) NOT NULL ,
  `firstname` VARCHAR(45) NULL ,
  `middleinitial` VARCHAR(45) NULL ,
  `startyearactive` INT NULL ,
  `endyearactive` INT NULL ,
  `notes` VARCHAR(255) NULL ,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp ,
  PRIMARY KEY (`recordedById`) ,
  INDEX `fullname` (`familyname` ASC, `firstname` ASC) 
) ENGINE = InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `omoccurrences` 
  ADD CONSTRAINT `FK_omoccurrences_recbyid`
  FOREIGN KEY (`recordedById` )
  REFERENCES `omcollectors` (`recordedById` )
  ON DELETE SET NULL
  ON UPDATE CASCADE
, ADD INDEX `FK_recordedbyid` (`recordedById` ASC) ;

ALTER TABLE `taxalinks` 
  ADD COLUMN `sourceIdentifier` VARCHAR(45) NULL  AFTER `title` ;

#Duplicate and exsiccatae linkages
CREATE  TABLE `omoccurduplicates` (   
  `duplicateid` INT NOT NULL AUTO_INCREMENT, 
  `projIdentifier` VARCHAR(30) NOT NULL, 
  `projName` VARCHAR(255) NOT NULL,
  `isExsiccata` INT NOT NULL DEFAULT 0,    
  `exsiccataEditors` VARCHAR(150) NULL ,   
  `notes` VARCHAR(255) NULL ,   
  `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp ,   
  PRIMARY KEY (`duplicateid`) 
) ENGINE = InnoDB DEFAULT CHARSET=latin1; 

ALTER TABLE `omoccurrences` 
  ADD COLUMN `duplicateid` INT NULL  AFTER `language` ; 

ALTER TABLE `omoccurrences`    
  ADD CONSTRAINT `FK_omoccurrences_dupes`   
   FOREIGN KEY (`duplicateid` )   
   REFERENCES `omoccurduplicates` (`duplicateid` )   
   ON DELETE SET NULL   ON UPDATE CASCADE , 
  ADD INDEX `FK_omoccurrences_dupes` (`duplicateid` ASC) ; 


#Occurrence Datasets
CREATE  TABLE `omoccurdatasets` (   
  `datasetid` INT NOT NULL AUTO_INCREMENT ,   
  `name` VARCHAR(100) NOT NULL ,   
  `notes` VARCHAR(250) NULL ,   
  `sortsequence` INT NULL ,   
  `uid` INT NOT NULL ,   
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp ,   
  PRIMARY KEY (`datasetid`) 
) ENGINE = InnoDB DEFAULT CHARSET=latin1;

CREATE  TABLE `omoccurdatasetlink` (   
  `occid` INT NOT NULL ,   
  `datasetid` INT NOT NULL ,   
  `notes` VARCHAR(250) NULL ,   
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp ,   
  PRIMARY KEY (`occid`, `datasetid`) 
) ENGINE = InnoDB DEFAULT CHARSET=latin1; 


ALTER TABLE `images` 
  CHANGE COLUMN `notes` `notes` VARCHAR(350) NULL DEFAULT NULL,
  CHANGE COLUMN `owner` `owner` VARCHAR(250) NULL DEFAULT NULL; 


#Loan tables
CREATE  TABLE `omoccurloansout` (   
  `loanoutid` INT UNSIGNED NOT NULL AUTO_INCREMENT, 
  `loanIdentifier` VARCHAR(30) NOT NULL, 
  `acronym` VARCHAR(20) NULL,
  `iid` INT NULL, 
  `dateSent` DATETIME NOT NULL,
  `dateDue` DATETIME NULL,
  `dateClosed` DATETIME NULL,
  `loanReceived` INT NULL DEFAULT 0,
  `forWhom` VARCHAR(45) NULL,
  `description` VARCHAR(250) NULL,
  `shippingdetails` VARCHAR(250) NULL,
  `notes` VARCHAR(255) NULL ,   
  `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp ,   
  PRIMARY KEY (`loanoutid`) 
) ENGINE = InnoDB DEFAULT CHARSET=latin1; 

CREATE TABLE `omoccurloansoutlink`(
  `loanoutid` INT UNSIGNED NOT NULL, 
  `occid` INT UNSIGNED NOT NULL, 
  `partialReturnDate` DATETIME NULL, 
  `notes` VARCHAR(255) NULL ,   
  `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp ,   
  PRIMARY KEY (`loanoutid`,`occid`) 
) ENGINE = InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `omoccurloansoutlink`    
  ADD CONSTRAINT `FK_loanoutlink_occid`   FOREIGN KEY (`occid` )   
   REFERENCES `omoccurrences` (`occid` )   ON DELETE CASCADE   ON UPDATE CASCADE,    
  ADD CONSTRAINT `FK_loanoutlink_loid`    FOREIGN KEY (`loanoutid` )   
   REFERENCES `omoccurloansout` (`loanoutid` )   ON DELETE CASCADE   ON UPDATE CASCADE , 
  ADD INDEX `FK_loanoutlink_occid` (`occid` ASC), 
  ADD INDEX `FK_loanoutlink_loid` (`loanoutid` ASC) ; 
