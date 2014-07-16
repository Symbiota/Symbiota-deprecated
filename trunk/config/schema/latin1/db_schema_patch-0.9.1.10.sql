ALTER TABLE `omoccurrences`
  ADD INDEX `Index_eventDate` (`eventDate` ASC) ; 

#Loan table adjustments
ALTER TABLE `omoccurloansout`
  ADD COLUMN `collid` INT UNSIGNED NOT NULL  AFTER `loanIdentifier` ; 

ALTER TABLE `omoccurloansout`    
  ADD CONSTRAINT `FK_omoccurloansout`   FOREIGN KEY (`collid` )   
    REFERENCES `omcollections` (`CollID` )   ON DELETE RESTRICT   ON UPDATE RESTRICT , 
  ADD INDEX `FK_omoccurloansout` (`collid` ASC) ;

#Misc
ALTER TABLE `omcollcatlink` 
  ADD COLUMN `sortsequence` INT NULL  AFTER `collid` ; 

ALTER TABLE `uploadspectemp` 
  ADD COLUMN `tempfield01` TEXT NULL  AFTER `labelProject`, 
  ADD COLUMN `tempfield02` TEXT NULL  AFTER `tempfield01`,
  ADD COLUMN `tempfield03` TEXT NULL  AFTER `tempfield02`,
  ADD COLUMN `tempfield04` TEXT NULL  AFTER `tempfield03`,
  ADD COLUMN `tempfield05` TEXT NULL  AFTER `tempfield04`,
  ADD COLUMN `tempfield06` TEXT NULL  AFTER `tempfield05`,
  ADD COLUMN `tempfield07` TEXT NULL  AFTER `tempfield06`,
  ADD COLUMN `tempfield08` TEXT NULL  AFTER `tempfield07`,
  ADD COLUMN `tempfield09` TEXT NULL  AFTER `tempfield08`,
  ADD COLUMN `tempfield10` TEXT NULL  AFTER `tempfield09`,
  ADD COLUMN `tempfield11` TEXT NULL  AFTER `tempfield10`,
  ADD COLUMN `tempfield12` TEXT NULL  AFTER `tempfield11`,
  ADD COLUMN `tempfield13` TEXT NULL  AFTER `tempfield12`,
  ADD COLUMN `tempfield14` TEXT NULL  AFTER `tempfield13`,
  ADD COLUMN `tempfield15` TEXT NULL  AFTER `tempfield14`;

CREATE  TABLE `fmchklstcoordinates` (   
  `chklstcoordid` INT NOT NULL AUTO_INCREMENT ,   
  `clid` INT UNSIGNED NOT NULL ,   
  `tid` INT UNSIGNED NOT NULL ,   
  `decimallatitude` DOUBLE NOT NULL ,   
  `decimallongitude` DOUBLE NOT NULL ,   
  `notes` VARCHAR(250) NULL ,   
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp ,   
  PRIMARY KEY (`chklstcoordid`) ,   
  UNIQUE INDEX `IndexUnique` (`clid` ASC, `tid` ASC, `decimallatitude` ASC, `decimallongitude` ASC) ,   
  INDEX `FKchklsttaxalink` (`clid` ASC, `tid` ASC) ,   
  CONSTRAINT `FKchklsttaxalink`     
    FOREIGN KEY (`clid` , `tid` )     
    REFERENCES `fmchklsttaxalink` (`CLID` , `TID` )     ON DELETE CASCADE     ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1; 

ALTER TABLE `omoccurrences` 
  ADD COLUMN `substrate` VARCHAR(500) NULL DEFAULT NULL  AFTER `habitat` ;

ALTER TABLE `uploadspectemp` 
  ADD COLUMN `substrate` VARCHAR(500) NULL DEFAULT NULL  AFTER `habitat` ;

#Create Exsiccati tables
CREATE  TABLE `omexsiccatititles` (   
  `ometid` INT UNSIGNED NOT NULL AUTO_INCREMENT ,   
  `title` VARCHAR(150) NOT NULL ,   
  `abbreviation` VARCHAR(100) NULL ,   
  `editor` VARCHAR(45) NULL ,   
  `range` VARCHAR(45) NULL ,   
  `collectorlastname` VARCHAR(45) NULL ,   
  `notes` VARCHAR(250) NULL , 
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp ,   
  PRIMARY KEY (`ometid`),   
  INDEX `index_exsiccatiTitle` (`title` ASC) 
) ENGINE=InnoDB DEFAULT CHARSET=latin1; 

CREATE  TABLE `omexsiccatinumbers` ( 
  `omenid` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
  `number` VARCHAR(45) NOT NULL , 
  `ometid` INT UNSIGNED NOT NULL , 
  `notes` VARCHAR(250) NULL , 
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`omenid`)
 ) ENGINE = InnoDB DEFAULT CHARSET=latin1; 

ALTER TABLE `omexsiccatinumbers` 
  ADD CONSTRAINT `FK_exsiccatiTitleNumber` 
  FOREIGN KEY (`ometid` ) 
  REFERENCES `omexsiccatititles` (`ometid` )  ON DELETE CASCADE   ON UPDATE RESTRICT ,
  ADD INDEX `FK_exsiccatiTitleNumber` (`ometid` ASC) ;

CREATE  TABLE `omexsiccatiocclink` ( 
  `omenid` INT UNSIGNED NOT NULL , 
  `occid` INT UNSIGNED NOT NULL , 
  `ranking` INT NOT NULL DEFAULT 50,
  `notes` VARCHAR(250) NULL , 
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`omenid`, `occid`) , 
  INDEX `FKExsiccatiNumOccLink1` (`omenid` ASC) , 
  INDEX `FKExsiccatiNumOccLink2` (`occid` ASC) , 
  CONSTRAINT `FKExsiccatiNumOccLink1`   FOREIGN KEY (`omenid` )   REFERENCES `omexsiccatinumbers` (`omenid` )   ON DELETE CASCADE   ON UPDATE RESTRICT, 
  CONSTRAINT `FKExsiccatiNumOccLink2`   FOREIGN KEY (`occid` )   REFERENCES `omoccurrences` (`occid` )   ON DELETE CASCADE   ON UPDATE RESTRICT
) ENGINE = InnoDB DEFAULT CHARSET=latin1; 


CREATE  TABLE `fmcltaxacomments` ( 
  `cltaxacommentsid` INT NOT NULL AUTO_INCREMENT , 
  `clid` INT UNSIGNED NOT NULL , 
  `tid` INT UNSIGNED NOT NULL , 
  `comment` TEXT NOT NULL , 
  `uid` INT UNSIGNED NOT NULL , 
  `ispublic` INT NOT NULL DEFAULT 1 , 
  `parentid` INT NULL , 
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`cltaxacommentsid`) , 
  INDEX `FK_clcomment_users` (`uid` ASC) , 
  INDEX `FK_clcomment_cltaxa` (`clid` ASC, `tid` ASC) , 
  CONSTRAINT `FK_clcomment_users`   FOREIGN KEY (`uid` )  REFERENCES `users` (`uid` )  ON DELETE CASCADE  ON UPDATE CASCADE, 
  CONSTRAINT `FK_clcomment_cltaxa`   FOREIGN KEY (`clid` , `tid` )  REFERENCES `fmchklsttaxalink` (`CLID` , `TID` )  ON DELETE CASCADE  ON UPDATE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=latin1; 

ALTER TABLE `fmchklsttaxalink`
  ADD COLUMN `explicitExclude` SMALLINT NULL  AFTER `Notes` ; 


#UploadTaxa adjustments
ALTER TABLE `uploadtaxa`
  ADD INDEX `sourceID_index` (`SourceID` ASC) ; 

ALTER TABLE `uploadtaxa`
  ADD INDEX `sourceAcceptedId_index` (`sourceAcceptedId` ASC) ;


#Modify specimen processor tables
ALTER TABLE `specprocessorrawlabels`
  DROP FOREIGN KEY `FK_populusrawlabels_occid`,
  DROP INDEX `FK_populusrawlabels_occid` ; 

ALTER TABLE `specprocessorrawlabels`
  CHANGE COLUMN `occid` `imgid` INT(10) UNSIGNED NOT NULL  , 
  ADD CONSTRAINT `FK_specproc_images`   FOREIGN KEY (`imgid` )
   REFERENCES `images` (`imgid` )   ON DELETE RESTRICT   ON UPDATE CASCADE ,
  ADD INDEX `FK_specproc_images` (`imgid` ASC) ;

ALTER TABLE `specprocessorrawlabels`
  ADD COLUMN `processingvariables` VARCHAR(250) NULL  AFTER `rawstr`; 


#Usage rights
ALTER TABLE `omcollections`
  ADD COLUMN `rightsHolder` VARCHAR(250) NULL  AFTER `PublicEdits` ,
  ADD COLUMN `rights` VARCHAR(250) NULL  AFTER `rightsHolder` ,
  ADD COLUMN `accessrights` VARCHAR(250) NULL  AFTER `rights` ;


#Extra columns for zoological collections
ALTER TABLE `omoccurrences`
  ADD COLUMN `lifeStage` VARCHAR(45) NULL  AFTER `establishmentMeans` ,
  ADD COLUMN `sex` VARCHAR(45) NULL  AFTER `lifeStage` ,
  ADD COLUMN `individualCount` VARCHAR(45) NULL  AFTER `sex` ;


#More adjustments to character heading table for ideentification keys
ALTER TABLE `kmcharheadinglink` 
  DROP FOREIGN KEY `FK_charheadinglink_hid`,
  DROP FOREIGN KEY `FK_charheadinglink_cid`;

ALTER TABLE `kmcharheading`
  CHANGE COLUMN `hid` `hid` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT  ; 

