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
ENGINE = InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `omcollections` 
  CHANGE COLUMN `CollType` `CollType` VARCHAR(45) NOT NULL DEFAULT 'Preserved Specimens' COMMENT 'Live Data, Snapshot, Observations ',
  ADD COLUMN `PublicEdits` INT(1) UNSIGNED NOT NULL DEFAULT 1  AFTER `CollType` ; 


CREATE  TABLE `omoccuredits` (   
  `ocedid` INT NOT NULL AUTO_INCREMENT ,   
  `occid` INT UNSIGNED NOT NULL ,   
  `FieldName` VARCHAR(45) NOT NULL ,   
  `FieldValueNew` TEXT NOT NULL ,   
  `FieldValueOld` TEXT NOT NULL ,   
  `ReviewStatus` INT(1) NOT NULL DEFAULT 1 COMMENT '1=Open;2=Pending;3=Closed' ,   
  `AppliedStatus` INT(1) NOT NULL DEFAULT 0 COMMENT '0=Not Applied;1=Applied' ,   
  `uid` INT(10) UNSIGNED NOT NULL ,   
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp ,   
  PRIMARY KEY (`ocedid`) 
)
ENGINE = InnoDB DEFAULT CHARSET=latin1; 

ALTER TABLE `omoccuredits`    
  ADD CONSTRAINT `fk_omoccuredits_uid`   FOREIGN KEY (`uid` )   
  REFERENCES `users` (`uid` )   ON DELETE CASCADE   ON UPDATE CASCADE , 
  ADD INDEX `fk_omoccuredits_uid` (`uid` ASC) ; 

ALTER TABLE `omoccuredits`    
  ADD CONSTRAINT `fk_omoccuredits_occid`   FOREIGN KEY (`occid` )   
  REFERENCES `omoccurrences` (`occid` )   ON DELETE CASCADE   ON UPDATE CASCADE , 
  ADD INDEX `fk_omoccuredits_occid` (`occid` ASC) ; 

CREATE  TABLE `omoccurcomments` (   
  `comid` INT NOT NULL AUTO_INCREMENT ,   
  `occid` INT UNSIGNED NOT NULL ,   
  `comment` TEXT NOT NULL ,   
  `uid` INT UNSIGNED NOT NULL ,   
  `reviewstatus` INT UNSIGNED NOT NULL DEFAULT 0 ,   
  `parentcomid` INT UNSIGNED NULL ,   
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp ,   
  PRIMARY KEY (`comid`) 
)
ENGINE = InnoDB DEFAULT CHARSET=latin1; 

ALTER TABLE `omoccurcomments`    
  ADD CONSTRAINT `fk_omoccurcomments_occid`   
  FOREIGN KEY (`occid` )   REFERENCES `omoccurrences` (`occid` )   ON DELETE CASCADE   ON UPDATE CASCADE , 
  ADD INDEX `fk_omoccurcomments_occid` (`occid` ASC) ; 

ALTER TABLE `omoccurcomments`    
  ADD CONSTRAINT `fk_omoccurcomments_uid`   
  FOREIGN KEY (`uid` )   REFERENCES `users` (`uid` )   ON DELETE CASCADE   ON UPDATE CASCADE , 
  ADD INDEX `fk_omoccurcomments_uid` (`uid` ASC) ; 

ALTER TABLE `omoccurrences` 
  ADD COLUMN `localitySecurityReason` VARCHAR(100) NULL  AFTER `localitySecurity`;

ALTER TABLE `fmchecklists` 
 CHANGE COLUMN `Name` `Name` VARCHAR(50) NOT NULL,  
 DROP INDEX `name`, 
 ADD INDEX `name` USING BTREE (`Name` ASC, `Type` ASC), 
 DROP INDEX `Index_checklist_title` ;
