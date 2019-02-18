#Add table to track schema patch history
CREATE TABLE schemaversion ( 
   id int not null primary key auto_increment,
   versionnumber varchar(20) not null,
   dateapplied timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT INTO schemaversion (versionnumber) values ('0.9.1.12');


#GUID tables
CREATE TABLE `guidoccurrences` (
  `guid` varchar(45) NOT NULL,
  `occid` int unsigned DEFAULT NULL,
  `archivestatus` int(3) NOT NULL DEFAULT '0',
  `archiveobj` text,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`guid`),
  UNIQUE KEY `guidoccurrences_occid_unique` (`occid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `guidoccurrences` 
   ADD CONSTRAINT `FK_guidoccurrences_occid`   FOREIGN KEY (`occid` )   REFERENCES `omoccurrences` (`occid` )  ON DELETE SET NULL  ON UPDATE CASCADE ,
   ADD INDEX `FK_guidoccur_occid_idx` (`occid` ASC) ; 

CREATE TABLE `guidoccurdeterminations` (
  `guid` varchar(45) NOT NULL,
  `detid` int unsigned DEFAULT NULL,
  `archivestatus` int(3) NOT NULL DEFAULT '0',
  `archiveobj` text,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`guid`),
  UNIQUE KEY `guidoccurdet_detid_unique` (`detid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `guidoccurdeterminations` 
   ADD CONSTRAINT `FK_guidoccurdet_detid`   FOREIGN KEY (`detid` )   REFERENCES `omoccurdeterminations` (`detid` )  ON DELETE SET NULL  ON UPDATE CASCADE ,
   ADD INDEX `FK_guidoccurdet_detid_idx` (`detid` ASC) ; 

CREATE TABLE `guidimages` (
  `guid` varchar(45) NOT NULL,
  `imgid` int unsigned DEFAULT NULL,
  `archivestatus` int(3) NOT NULL DEFAULT '0',
  `archiveobj` text,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`guid`),
  UNIQUE KEY `guidimages_imgid_unique` (`imgid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `guidimages` 
   ADD CONSTRAINT `FK_guidimages_imgid`   FOREIGN KEY (`imgid` )   REFERENCES `images` (`imgid` )  ON DELETE SET NULL  ON UPDATE CASCADE ,
   ADD INDEX `FK_guidimages_imgid_idx` (`imgid` ASC) ; 

ALTER TABLE `omcollections`
  ADD COLUMN `collectionguid` VARCHAR(45) NULL  AFTER `PublicEdits`,
  ADD COLUMN `securitykey` VARCHAR(45) NULL  AFTER `collectionguid`,
  ADD COLUMN `usageTerm` VARCHAR(250) NULL  AFTER `rights` ; 

ALTER TABLE `omcollections`
  ADD COLUMN `publishToGbif` int NULL  AFTER `usageTerm`; 

#OCR - NLP processing
CREATE  TABLE `specprocnlpversion` ( 
  `nlpverid` INT NOT NULL AUTO_INCREMENT , 
  `prlid` INT UNSIGNED NOT NULL , 
  `archivestr` TEXT NOT NULL , 
  `processingvariables` VARCHAR(250) NULL , 
  `score` INT NULL , 
  `source` VARCHAR(150) NULL , 
  `notes` VARCHAR(250) NULL , 
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`nlpverid`) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8,
COMMENT = 'Archives field name - value pairs of NLP results loading into an omoccurrence record. This way, results can be easily redone at a later date without copying over date modifed afterward by another user or process '; 

ALTER TABLE `specprocnlpversion` 
  ADD CONSTRAINT `FK_specprocnlpver_rawtext`  FOREIGN KEY (`prlid` )  REFERENCES `specprocessorrawlabels` (`prlid` )  ON DELETE RESTRICT   ON UPDATE CASCADE ,
  ADD INDEX `FK_specprocnlpver_rawtext_idx` (`prlid` ASC) ; 


#Duplicates
ALTER TABLE `omoccurduplicates`
  DROP COLUMN `exsiccataEditors` ,
  DROP COLUMN `isExsiccata` ; 

ALTER TABLE `omoccurduplicates`
  CHANGE COLUMN `projIdentifier` `projIdentifier` VARCHAR(50) NOT NULL  ,
  CHANGE COLUMN `projName` `projDescription` VARCHAR(255) NULL  ,
  ADD COLUMN `exactdupe` INT NOT NULL DEFAULT 1  AFTER `notes` ; 

ALTER TABLE `omoccurrences`
  DROP FOREIGN KEY `FK_omoccurrences_dupes` ; 

ALTER TABLE `omoccurrences`
  DROP COLUMN `duplicateid`  ,
  DROP INDEX `FK_omoccurrences_dupes` ; 

CREATE  TABLE `omoccurduplicatelink` ( 
  `occid` INT UNSIGNED NOT NULL , 
  `duplicateid` INT NOT NULL , 
  `notes` VARCHAR(250) NULL , 
  `modifiedUid` INT UNSIGNED NULL , 
  `modifiedtimestamp` DATETIME NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`occid`, `duplicateid`) 
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `omoccurduplicatelink` 
  ADD CONSTRAINT `FK_omoccurdupelink_occid`  FOREIGN KEY (`occid` )  REFERENCES `omoccurrences` (`occid` )  ON DELETE CASCADE  ON UPDATE CASCADE, 
  ADD CONSTRAINT `FK_omoccurdupelink_dupeid`   FOREIGN KEY (`duplicateid` )  REFERENCES `omoccurduplicates` (`duplicateid` )  ON DELETE CASCADE  ON UPDATE CASCADE ,
  ADD INDEX `FK_omoccurdupelink_occid_idx` (`occid` ASC)  ,
  ADD INDEX `FK_omoccurdupelink_dupeid_idx` (`duplicateid` ASC) ;


#Misc alterations
ALTER TABLE `omoccurgenetic`
  CHANGE COLUMN `idoccurgenetic` `idoccurgenetic` INT NOT NULL AUTO_INCREMENT  ,
  CHANGE COLUMN `locus` `locus` VARCHAR(500) NULL DEFAULT NULL  ;

ALTER TABLE `omoccurrences`
  ADD COLUMN `dateEntered` DATETIME NULL  AFTER `labelProject` ;

ALTER TABLE `omoccurrences` 
  ADD COLUMN `samplingEffort` VARCHAR(200) NULL  AFTER `samplingProtocol` ;

ALTER TABLE `uploadspectemp` 
  ADD COLUMN `samplingEffort` VARCHAR(200) NULL  AFTER `samplingProtocol`,
  ADD COLUMN `associatedMedia` TEXT NULL  AFTER `associatedOccurrences` ,
  ADD COLUMN `associatedSequences` TEXT NULL  AFTER `associatedMedia` ;

ALTER TABLE `uploadspectemp`
  ADD COLUMN `verbatimLatitude` VARCHAR(45) NULL  AFTER `lngEW` ,
  ADD COLUMN `verbatimLongitude` VARCHAR(45) NULL  AFTER `verbatimLatitude` ,
  ADD COLUMN `trsTownship` VARCHAR(45) NULL  AFTER `UtmZoning` ,
  ADD COLUMN `trsRange` VARCHAR(45) NULL  AFTER `trsTownship` ,
  ADD COLUMN `trsSection` VARCHAR(45) NULL  AFTER `trsRange` ,
  ADD COLUMN `trsSectionDetails` VARCHAR(45) NULL  AFTER `trsSection` ,
  ADD COLUMN `elevationNumber` VARCHAR(45) NULL  AFTER `maximumElevationInMeters` ,
  ADD COLUMN `elevationUnits` VARCHAR(45) NULL  AFTER `elevationNumber` ; 

ALTER TABLE `uploadspectemp`
  ADD COLUMN `recordNumberPrefix` VARCHAR(45) NULL  AFTER `recordedBy` ,
  ADD COLUMN `recordNumberSuffix` VARCHAR(45) NULL  AFTER `recordNumberPrefix` ; 

ALTER TABLE `fmchklsttaxalink`
  CHANGE COLUMN `morphospecies` `morphospecies` VARCHAR(45) NULL DEFAULT NULL; 

ALTER TABLE `fmvouchers`
  CHANGE COLUMN `Collector` `Collector` VARCHAR(100) NULL; 

ALTER TABLE `fmvouchers` DROP FOREIGN KEY `FK_fmvouchers_occ` ;
ALTER TABLE `fmvouchers` 
  ADD CONSTRAINT `FK_fmvouchers_occ`
  FOREIGN KEY (`occid` )
  REFERENCES `omoccurrences` (`occid` )  ON DELETE CASCADE  ON UPDATE CASCADE;

ALTER TABLE `omexsiccatititles`
  CHANGE COLUMN `source` `source` VARCHAR(250) NULL DEFAULT NULL  ; 

ALTER TABLE `omcollcatagories`
  ADD COLUMN `inclusive` INT NULL DEFAULT 0  AFTER `catagory` ,
  ADD COLUMN `notes` VARCHAR(250) NULL  AFTER `inclusive` ;


#Taxa cleaning 
ALTER TABLE `taxa`
  ADD COLUMN `verificationStatus` INT NULL DEFAULT 0  AFTER `Source` ,
  ADD COLUMN `verificationSource` VARCHAR(45) NULL  AFTER `verificationStatus` ; 


#Configuration tables
CREATE  TABLE `configpage` ( 
  `configpageid` INT NOT NULL AUTO_INCREMENT , 
  `pagename` VARCHAR(45) NOT NULL , 
  `title` VARCHAR(150) NOT NULL , 
  `cssname` VARCHAR(45) NULL , 
  `language` VARCHAR(45) NOT NULL DEFAULT 'english' , 
  `displaymode` INT NULL , 
  `notes` VARCHAR(250) NULL , 
  `modifiedUid` INT UNSIGNED NOT NULL , 
  `modifiedtimestamp` DATETIME NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`configpageid`) 
) ENGINE = InnoDB DEFAULT CHARSET=utf8; 

CREATE  TABLE `configpageattributes` ( 
  `attributeid` INT NOT NULL AUTO_INCREMENT , 
  `configpageid` INT NOT NULL , 
  `objid` VARCHAR(45) NULL , 
  `objname` VARCHAR(45) NOT NULL , 
  `value` VARCHAR(45) NULL , 
  `type` VARCHAR(45) NULL COMMENT 'text, submit, div' ,
  `width` INT NULL , 
  `top` INT NULL , 
  `left` INT NULL , 
  `stylestr` VARCHAR(45) NULL , 
  `notes` VARCHAR(250) NULL , 
  `modifiedUid` INT UNSIGNED NOT NULL , 
  `modifiedtimestamp` DATETIME NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`attributeid`) 
) ENGINE = InnoDB DEFAULT CHARSET=utf8; 

ALTER TABLE `configpageattributes`  
  ADD CONSTRAINT `FK_configpageattributes_id`  FOREIGN KEY (`configpageid` )  REFERENCES `configpage` (`configpageid` )  ON DELETE CASCADE  ON UPDATE CASCADE ,
  ADD INDEX `FK_configpageattributes_id_idx` (`configpageid` ASC) ; 


#Table for multiple identifiers 
CREATE  TABLE `omoccuridentifiers` ( 
  `idomoccuridentifiers` INT NOT NULL AUTO_INCREMENT , 
  `occid` INT UNSIGNED NOT NULL , 
  `identifiervalue` VARCHAR(45) NOT NULL , 
  `identifiername` VARCHAR(45) NOT NULL COMMENT 'barcode, accession number, old catalog number, NPS, etc' , 
  `notes` VARCHAR(250) NULL , 
  `modifiedUid` INT UNSIGNED NOT NULL , 
  `modifiedtimestamp` DATETIME NULL ,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`idomoccuridentifiers`) 
) ENGINE = InnoDB DEFAULT CHARSET=utf8; 

ALTER TABLE `omoccuridentifiers`  
  ADD CONSTRAINT `FK_omoccuridentifiers_occid` FOREIGN KEY (`occid` )  REFERENCES `omoccurrences` (`occid` )  ON DELETE CASCADE  ON UPDATE CASCADE ,
  ADD INDEX `FK_omoccuridentifiers_occid_idx` (`occid` ASC) ;


#User taxonomy link for taxonomy and identification management
CREATE  TABLE `usertaxonomy` ( 
  `idusertaxonomy` INT NOT NULL AUTO_INCREMENT , 
  `uid` INT UNSIGNED NOT NULL , 
  `tid` INT UNSIGNED NOT NULL , 
  `taxauthid` INT UNSIGNED NOT NULL DEFAULT 1 , 
  `editorstatus` VARCHAR(45) NULL , 
  `notes` VARCHAR(250) NULL , 
  `modifiedUid` INT UNSIGNED NOT NULL , 
  `modifiedtimestamp` DATETIME NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`idusertaxonomy`) 
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `usertaxonomy` 
  ADD CONSTRAINT `FK_usertaxonomy_uid`   FOREIGN KEY (`uid` )   REFERENCES `users` (`uid` )   ON DELETE CASCADE   ON UPDATE CASCADE,    
  ADD CONSTRAINT `FK_usertaxonomy_tid`   FOREIGN KEY (`tid` )   REFERENCES `taxa` (`TID` )   ON DELETE CASCADE   ON UPDATE CASCADE ,
  ADD INDEX `FK_usertaxonomy_uid_idx` (`uid` ASC)  ,
  ADD INDEX `FK_usertaxonomy_tid_idx` (`tid` ASC) ; 

ALTER TABLE `usertaxonomy`  
  ADD CONSTRAINT `FK_usertaxonomy_taxauthid`  FOREIGN KEY (`taxauthid` )  REFERENCES `taxauthority` (`taxauthid` )   ON DELETE CASCADE   ON UPDATE CASCADE , 
  ADD INDEX `FK_usertaxonomy_taxauthid_idx` (`taxauthid` ASC) ; 

ALTER TABLE `usertaxonomy`
  ADD UNIQUE INDEX `usertaxonomy_UNIQUE` (`uid` ASC, `tid` ASC, `taxauthid` ASC) ; 


#Checklist parent/child relationship
CREATE  TABLE `fmchklstchildren` ( 
  `clid` INT UNSIGNED NOT NULL , 
  `clidchild` INT UNSIGNED NOT NULL , 
  `modifiedUid` INT UNSIGNED NOT NULL , 
  `modifiedtimestamp` DATETIME NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`clid`, `clidchild`) 
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `fmchklstchildren`  
  ADD CONSTRAINT `FK_fmchklstchild_clid`  FOREIGN KEY (`clid` )  REFERENCES `fmchecklists` (`CLID` )  ON DELETE CASCADE  ON UPDATE CASCADE,  
  ADD CONSTRAINT `FK_fmchklstchild_child`  FOREIGN KEY (`clidchild` )  REFERENCES `fmchecklists` (`CLID` )  ON DELETE CASCADE  ON UPDATE CASCADE ,
  ADD INDEX `FK_fmchklstchild_clid_idx` (`clid` ASC)  ,
  ADD INDEX `FK_fmchklstchild_child_idx` (`clidchild` ASC) ;


#Rare species info
CREATE  TABLE `fmchklsttaxastatus` ( 
  `clid` INT UNSIGNED NOT NULL , 
  `tid` INT UNSIGNED NOT NULL , 
  `geographicRange` INT NOT NULL DEFAULT 0 , 
  `populationRank` INT NOT NULL DEFAULT 0 , 
  `abundance` INT NOT NULL DEFAULT 0 , 
  `habitatSpecificity` INT NOT NULL DEFAULT 0 , 
  `intrinsicRarity` INT NOT NULL DEFAULT 0 , 
  `threatImminence` INT NOT NULL DEFAULT 0 , 
  `populationTrends` INT NOT NULL DEFAULT 0 , 
  `nativeStatus` VARCHAR(45) NULL , 
  `endemicStatus` INT NOT NULL DEFAULT 0 , 
  `notes` VARCHAR(250) NULL , 
  `modifiedUid` INT UNSIGNED NULL , 
  `modifiedtimestamp` DATETIME NULL , 
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`clid`, `tid`) 
) ENGINE = InnoDB DEFAULT CHARSET=utf8; 

ALTER TABLE `fmchklsttaxastatus`  
  ADD CONSTRAINT `FK_fmchklsttaxastatus_clidtid`
   FOREIGN KEY (`clid` , `tid` )
   REFERENCES `fmchklsttaxalink` (`CLID` , `TID` )
   ON DELETE CASCADE   ON UPDATE CASCADE ,
  ADD INDEX `FK_fmchklsttaxastatus_clid_idx` (`clid` ASC, `tid` ASC) ; 


CREATE  TABLE `referenceobject` (
  `refid` INT NOT NULL AUTO_INCREMENT , 
  `authors` VARCHAR(250) NOT NULL , 
  `title` VARCHAR(150) NOT NULL , 
  `pubdate` VARCHAR(45) NULL , 
  `volume` VARCHAR(45) NULL , 
  `number` VARCHAR(45) NULL , 
  `pages` VARCHAR(45) NULL , 
  `placeofpublication` VARCHAR(45) NULL , 
  `publisher` VARCHAR(150) NULL , 
  `reftype` VARCHAR(45) NULL , 
  `isbn` VARCHAR(45) NULL , 
  `url` VARCHAR(150) NULL , 
  `libraryNumber` VARCHAR(45) NULL , 
  `guid` VARCHAR(45) NULL , 
  `ispublished` VARCHAR(45) NULL , 
  `journalid` INT NULL , 
  `notes` VARCHAR(45) NULL , 
  `modifieduid` INT UNSIGNED NULL , 
  `modifiedtimestamp` DATETIME NULL , 
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`refid`) 
) ENGINE = InnoDB DEFAULT CHARSET=utf8; 

ALTER TABLE `referenceobject`
  ADD INDEX `INDEX_refobj_title` (`title` ASC) ; 

CREATE  TABLE `referencejournal` ( 
  `refjournalid` INT NOT NULL AUTO_INCREMENT , 
  `journalname` VARCHAR(250) NOT NULL , 
  `journalabbr` VARCHAR(100) NULL , 
  `issn` VARCHAR(45) NULL , 
  `guid` VARCHAR(45) NULL , 
  `notes` VARCHAR(45) NULL , 
  `modifieduid` INT UNSIGNED NULL , 
  `modifiedtimestamp` DATETIME NULL , 
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`refjournalid`) 
) ENGINE = InnoDB DEFAULT CHARSET=utf8;


ALTER TABLE `referenceobject` 
  ADD CONSTRAINT `FK_refobj_journalid` 
   FOREIGN KEY (`journalid` )
   REFERENCES `referencejournal` (`refjournalid` )
   ON DELETE CASCADE
   ON UPDATE CASCADE ,
  ADD INDEX `FK_refobj_journalid_idx` (`journalid` ASC) ; 

CREATE  TABLE `referenceauthors` ( 
  `refauthorid` INT NOT NULL AUTO_INCREMENT , 
  `lastname` VARCHAR(100) NOT NULL , 
  `firstname` VARCHAR(100) NULL , 
  `middlename` VARCHAR(100) NULL , 
  `modifieduid` INT UNSIGNED NULL , 
  `modifiedtimestamp` DATETIME NULL , 
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`refauthorid`) , 
  INDEX `INDEX_refauthlastname` (`lastname` ASC)
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

CREATE  TABLE `referenceauthorlink` ( 
  `refid` INT NOT NULL , 
  `refauthid` INT NOT NULL , 
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`refid`, `refauthid`) , 
  INDEX `FK_refauthlink_refid_idx` (`refid` ASC) , 
  INDEX `FK_refauthlink_refauthid_idx` (`refauthid` ASC) , 
  CONSTRAINT `FK_refauthlink_refid`
   FOREIGN KEY (`refid` )
   REFERENCES `referenceobject` (`refid` ) 
   ON DELETE CASCADE
   ON UPDATE CASCADE, 
  CONSTRAINT `FK_refauthlink_refauthid` 
   FOREIGN KEY (`refauthid` ) 
   REFERENCES `referenceauthors` (`refauthorid` ) 
   ON DELETE CASCADE  
   ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8; 

CREATE  TABLE `referencetaxalink` ( 
  `refid` INT NOT NULL , 
  `tid` INT UNSIGNED NOT NULL , 
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`refid`, `tid`) , 
  INDEX `FK_reftaxalink_refid_idx` (`refid` ASC) , 
  INDEX `FK_reftaxalink_tid_idx` (`tid` ASC) , 
  CONSTRAINT `FK_reftaxalink_refid` 
   FOREIGN KEY (`refid` )  
   REFERENCES `referenceobject` (`refid` )  
   ON DELETE CASCADE  
   ON UPDATE CASCADE,
  CONSTRAINT `FK_reftaxalink_tid`  
   FOREIGN KEY (`tid` )  
   REFERENCES `taxa` (`TID` )  
   ON DELETE CASCADE  
   ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8; 

CREATE  TABLE `referenceoccurlink` ( 
  `refid` INT NOT NULL , 
  `occid` INT UNSIGNED NOT NULL , 
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`refid`, `occid`) , 
  INDEX `FK_refoccurlink_refid_idx` (`refid` ASC) , 
  INDEX `FK_refoccurlink_occid_idx` (`occid` ASC) , 
  CONSTRAINT `FK_refoccurlink_refid` 
   FOREIGN KEY (`refid` )  
   REFERENCES `referenceobject` (`refid` )  
   ON DELETE CASCADE  
   ON UPDATE CASCADE,
  CONSTRAINT `FK_refoccurlink_occid`  
   FOREIGN KEY (`occid` )  
   REFERENCES `omoccurrences` (`occid` )  
   ON DELETE CASCADE  
   ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8; 

CREATE  TABLE `referencechecklistlink` ( 
  `refid` INT NOT NULL , 
  `clid` INT UNSIGNED NOT NULL , 
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`refid`, `clid`) , 
  INDEX `FK_refcheckllistlink_refid_idx` (`refid` ASC) , 
  INDEX `FK_refcheckllistlink_clid_idx` (`clid` ASC) , 
  CONSTRAINT `FK_refchecklistlink_refid` 
   FOREIGN KEY (`refid` )  
   REFERENCES `referenceobject` (`refid` )  
   ON DELETE CASCADE  
   ON UPDATE CASCADE,
  CONSTRAINT `FK_refchecklistlink_clid`  
   FOREIGN KEY (`clid` )  
   REFERENCES `fmchecklists` (`clid` )  
   ON DELETE CASCADE  
   ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=utf8; 



#storageLocation schema



#create a spatial table


