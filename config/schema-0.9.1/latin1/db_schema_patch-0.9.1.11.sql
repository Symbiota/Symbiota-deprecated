ALTER TABLE `userlogin`
  ADD COLUMN `alias` VARCHAR(45) NULL  AFTER `password` ;

ALTER TABLE `userlogin`
  ADD UNIQUE INDEX `Index_userlogin_unique` (`alias` ASC) ;

ALTER TABLE `taxavernaculars`
  ADD COLUMN `isupperterm` INT(2) NULL DEFAULT 0  AFTER `username` ;

ALTER TABLE `fmprojects`
  ADD COLUMN `parentpid` INT(10) UNSIGNED NULL  AFTER `ispublic` , 
  ADD CONSTRAINT `FK_parentpid_proj` FOREIGN KEY (`parentpid` ) REFERENCES `fmprojects` (`pid` ) ON DELETE NO ACTION ON UPDATE NO ACTION ,
  ADD INDEX `FK_parentpid_proj` (`parentpid` ASC) ; 

ALTER TABLE `fmprojects`
  CHANGE COLUMN `fulldescription` `fulldescription` VARCHAR(2000) NULL DEFAULT NULL  ; 

ALTER TABLE `omexsiccatititles`
  ADD COLUMN `source` VARCHAR(45) NULL  AFTER `collectorlastname` ;

ALTER TABLE `omexsiccatinumbers`
  DROP FOREIGN KEY `FK_exsiccatiTitleNumber` ; 

ALTER TABLE `omexsiccatititles`
  CHANGE COLUMN `editor` `editor` VARCHAR(150) NULL DEFAULT NULL  ; 

ALTER TABLE `omexsiccatititles`
  CHANGE COLUMN `range` `exsrange` VARCHAR(45) NULL DEFAULT NULL;

ALTER TABLE `omexsiccatititles`
  CHANGE COLUMN `notes` `notes` VARCHAR(2000) NULL DEFAULT NULL;

ALTER TABLE `omexsiccatititles` 
  DROP COLUMN `collectorlastname` , 
  ADD COLUMN `startdate` VARCHAR(45) NULL  AFTER `exsrange` , 
  ADD COLUMN `enddate` VARCHAR(45) NULL  AFTER `startdate`, 
  ADD COLUMN `lasteditedby` VARCHAR(45) NULL  AFTER `notes`;

ALTER TABLE `omexsiccatinumbers`
  CHANGE COLUMN `number` `exsnumber` VARCHAR(45) NOT NULL  ;

ALTER TABLE `omexsiccatinumbers`  
  ADD CONSTRAINT `FK_exsiccatiTitleNumber`  FOREIGN KEY (`ometid` )  REFERENCES `omexsiccatititles` (`ometid` )  ON DELETE RESTRICT; 

ALTER TABLE `omexsiccatinumbers`
  ADD UNIQUE INDEX `Index_omexsiccatinumbers_unique` (`exsnumber` ASC, `ometid` ASC) ;

ALTER TABLE `omexsiccatiocclink`
  ADD UNIQUE INDEX `UniqueOmexsiccatiOccLink` (`occid` ASC) ;


ALTER TABLE `omoccurrences`
  ADD COLUMN `fieldnumber` VARCHAR(45) NULL DEFAULT NULL  AFTER `fieldNotes`,
  ADD COLUMN `genericcolumn1` VARCHAR(100) NULL DEFAULT NULL  AFTER `disposition`,
  ADD COLUMN `genericcolumn2` VARCHAR(100) NULL DEFAULT NULL  AFTER `genericcolumn1`,
  CHANGE COLUMN `disposition` `disposition` VARCHAR(100) DEFAULT NULL ,
  CHANGE COLUMN `labelProject` `labelProject` VARCHAR(50) DEFAULT NULL ,
  ADD COLUMN `footprintWKT` TEXT NULL DEFAULT NULL  AFTER `coordinateUncertaintyInMeters`,
  ADD COLUMN `samplingProtocol` VARCHAR(100) NULL DEFAULT NULL  AFTER `individualCount`,
  ADD COLUMN `preparations` VARCHAR(100) NULL DEFAULT NULL  AFTER `samplingProtocol`;


ALTER TABLE `omoccurrences`
  ADD INDEX `Index_occurrences_procstatus` (`processingstatus` ASC); 

ALTER TABLE `uploadspectemp`
  ADD COLUMN `genericcolumn1` VARCHAR(100) NULL DEFAULT NULL  AFTER `disposition`,
  ADD COLUMN `genericcolumn2` VARCHAR(100) NULL DEFAULT NULL  AFTER `genericcolumn1`;

ALTER TABLE `uploadspectemp`
  ADD COLUMN `fieldnumber` VARCHAR(45) NULL DEFAULT NULL  AFTER `fieldNotes` ;

ALTER TABLE `uploadspectemp`
  ADD COLUMN `lifeStage` VARCHAR(45) NULL  AFTER `establishmentMeans` ,
  ADD COLUMN `sex` VARCHAR(45) NULL  AFTER `lifeStage` ,
  ADD COLUMN `individualCount` VARCHAR(45) NULL  AFTER `sex` ;

ALTER TABLE `uploadspectemp`
  CHANGE COLUMN `initialTimestamp` `initialTimestamp` TIMESTAMP NULL DEFAULT current_timestamp  ,
  ADD COLUMN `latDeg` INT NULL  AFTER `verbatimCoordinateSystem` ,
  ADD COLUMN `latMin` DOUBLE NULL  AFTER `latDeg` ,
  ADD COLUMN `latSec` DOUBLE NULL  AFTER `latMin` ,
  ADD COLUMN `latNS` VARCHAR(3) NULL  AFTER `latSec` ,
  ADD COLUMN `lngDeg` INT NULL  AFTER `latNS` ,
  ADD COLUMN `lngMin` DOUBLE NULL  AFTER `lngDeg` ,
  ADD COLUMN `lngSec` DOUBLE NULL  AFTER `lngmin` ,
  ADD COLUMN `lngEW` VARCHAR(3) NULL  AFTER `lngsec` ;

ALTER TABLE `uploadspectemp`
  ADD COLUMN `samplingProtocol` VARCHAR(100) NULL DEFAULT NULL  AFTER `individualCount`,
  ADD COLUMN `preparations` VARCHAR(100) NULL DEFAULT NULL  AFTER `samplingProtocol`;

ALTER TABLE `uploadspectemp`
  ADD COLUMN `footprintWKT` TEXT NULL DEFAULT NULL  AFTER `coordinateUncertaintyInMeters`;


ALTER TABLE `omcollectors`
  ADD COLUMN `rating` INT NULL DEFAULT 10  AFTER `notes` ;


CREATE  TABLE `taxanestedtree` ( 
   `tid` INT(10) UNSIGNED NOT NULL ,
   `taxauthid` INT(10) UNSIGNED NOT NULL ,
   `leftindex` INT(10) UNSIGNED NOT NULL ,
   `rightindex` INT(10) UNSIGNED NOT NULL ,
   `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp ,   
   PRIMARY KEY (`tid`, `taxauthid`) ,
   INDEX `leftindex` (`leftindex` ASC) ,
   INDEX `rightindex` (`rightindex` ASC) ,
   INDEX `FK_tnt_taxa` (`tid` ASC) ,
   INDEX `FK_tnt_taxauth` (`taxauthid` ASC) ,
   CONSTRAINT `FK_tnt_taxa`  FOREIGN KEY (`tid` )  REFERENCES `taxa` (`TID` )  ON DELETE CASCADE  ON UPDATE CASCADE,
   CONSTRAINT `FK_tnt_taxauth`  FOREIGN KEY (`taxauthid` )  REFERENCES `taxauthority` (`taxauthid` )  ON DELETE CASCADE  ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=latin1; 

CREATE  TABLE `taxaenumtree` (
   `tid` INT UNSIGNED NOT NULL ,
   `taxauthid` INT(10) UNSIGNED NOT NULL ,
   `parenttid` INT(10) UNSIGNED NOT NULL ,
   `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp ,   
   PRIMARY KEY (`tid`, `taxauthid`, `parenttid`) ,
   INDEX `FK_tet_taxa` (`tid` ASC) ,
   INDEX `FK_tet_taxauth` (`taxauthid` ASC) ,
   INDEX `FK_tet_taxa2` (`parenttid` ASC) ,
   CONSTRAINT `FK_tet_taxa` FOREIGN KEY (`tid` ) REFERENCES `taxa` (`TID` )  ON DELETE CASCADE  ON UPDATE CASCADE,
   CONSTRAINT `FK_tet_taxauth`  FOREIGN KEY (`taxauthid` )  REFERENCES `taxauthority` (`taxauthid` ) ON DELETE CASCADE  ON UPDATE CASCADE,
   CONSTRAINT `FK_tet_taxa2`  FOREIGN KEY (`parenttid` )  REFERENCES `taxa` (`TID` )  ON DELETE CASCADE  ON UPDATE CASCADE
) ENGINE = InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `institutions`
  ADD COLUMN `InstitutionName2` VARCHAR(150) NULL DEFAULT NULL  AFTER `InstitutionName` ,
  CHANGE COLUMN `Contact` `Contact` VARCHAR(65) NULL DEFAULT NULL  ,
  CHANGE COLUMN `Notes` `Notes` VARCHAR(250) NULL DEFAULT NULL  ;
  
ALTER TABLE `omoccurdeterminations`
  CHANGE COLUMN `identifiedBy` `identifiedBy` VARCHAR(60) NOT NULL  ;

ALTER TABLE `omoccurdeterminations`
  ADD COLUMN `iscurrent` INT(2) NULL DEFAULT 0  AFTER `identificationQualifier`,
  ADD COLUMN `tidinterpreted` INT UNSIGNED NULL  AFTER `sciname`; 

ALTER TABLE `omoccurdeterminations` 
  ADD CONSTRAINT `FK_omoccurdets_tid` FOREIGN KEY (`tidinterpreted` ) REFERENCES `taxa` (`TID` ) ON DELETE RESTRICT ON UPDATE RESTRICT , 
  ADD INDEX `FK_omoccurdets_tid` (`tidinterpreted` ASC) ; 

ALTER TABLE `omoccurdeterminations`
  ADD COLUMN `idbyid` INT UNSIGNED NULL  AFTER `identifiedBy` ;

ALTER TABLE `omoccurdeterminations`  
  ADD CONSTRAINT `FK_omoccurdets_idby`   FOREIGN KEY (`idbyid` )   REFERENCES `omcollectors` (`recordedById` )  ON DELETE SET NULL  ON UPDATE SET NULL ,
  ADD INDEX `FK_omoccurdets_idby_idx` (`idbyid` ASC) ;


#Loans 
DROP TABLE IF EXISTS `omoccurloansoutlink`;
DROP TABLE IF EXISTS `omoccurloansout`;

CREATE TABLE `omoccurloans` (
  `loanid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `loanIdentifierOwn` varchar(30) DEFAULT NULL,
  `loanIdentifierBorr` varchar(30) DEFAULT NULL,
  `collidOwn` int(10) unsigned DEFAULT NULL,
  `collidBorr` int(10) unsigned DEFAULT NULL,
  `iidOwner` int(10) unsigned DEFAULT NULL,
  `iidBorrower` int(10) unsigned DEFAULT NULL,
  `dateSent` date DEFAULT NULL,
  `dateSentReturn` date DEFAULT NULL,
  `receivedStatus` varchar(250) DEFAULT NULL,
  `totalBoxes` int(5) DEFAULT NULL,
  `totalBoxesReturned` int(5) DEFAULT NULL,
  `numSpecimens` int(5) DEFAULT NULL,
  `shippingMethod` varchar(50) DEFAULT NULL,
  `shippingMethodReturn` varchar(50) DEFAULT NULL,
  `dateDue` date DEFAULT NULL,
  `dateReceivedOwn` date DEFAULT NULL,
  `dateReceivedBorr` date DEFAULT NULL,
  `dateClosed` date DEFAULT NULL,
  `forWhom` varchar(50) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `invoiceMessageOwn` varchar(500) DEFAULT NULL,
  `invoiceMessageBorr` varchar(500) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `createdByOwn` varchar(30) DEFAULT NULL,
  `createdByBorr` varchar(30) DEFAULT NULL,
  `processingStatus` int(5) unsigned DEFAULT '1',
  `processedByOwn` varchar(30) DEFAULT NULL,
  `processedByBorr` varchar(30) DEFAULT NULL,
  `processedByReturnOwn` varchar(30) DEFAULT NULL,
  `processedByReturnBorr` varchar(30) DEFAULT NULL,
  `initialTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`loanid`),
  KEY `FK_occurloans_owninst` (`iidOwner`),
  KEY `FK_occurloans_borrinst` (`iidBorrower`),
  KEY `FK_occurloans_owncoll` (`collidOwn`),
  KEY `FK_occurloans_borrcoll` (`collidBorr`),
  CONSTRAINT `FK_occurloans_borrcoll` FOREIGN KEY (`collidBorr`) REFERENCES `omcollections` (`CollID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `FK_occurloans_borrinst` FOREIGN KEY (`iidBorrower`) REFERENCES `institutions` (`iid`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `FK_occurloans_owncoll` FOREIGN KEY (`collidOwn`) REFERENCES `omcollections` (`CollID`) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT `FK_occurloans_owninst` FOREIGN KEY (`iidOwner`) REFERENCES `institutions` (`iid`) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `omoccurloanslink` (
  `loanid` int(10) unsigned NOT NULL,
  `occid` int(10) unsigned NOT NULL,
  `returndate` date DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `initialTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`loanid`,`occid`),
  KEY `FK_occurloanlink_occid` (`occid`),
  KEY `FK_occurloanlink_loanid` (`loanid`),
  CONSTRAINT `FK_occurloanlink_loanid` FOREIGN KEY (`loanid`) REFERENCES `omoccurloans` (`loanid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_occurloanlink_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


#Exchange and Gift table
CREATE TABLE `omoccurexchange` (
  `exchangeid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(30) DEFAULT NULL,
  `collid` int(10) unsigned DEFAULT NULL,
  `iid` int(10) unsigned DEFAULT NULL,
  `transactionType` varchar(10) DEFAULT NULL,
  `in_out` varchar(3) DEFAULT NULL,
  `dateSent` date DEFAULT NULL,
  `dateReceived` date DEFAULT NULL,
  `totalBoxes` int(5) DEFAULT NULL,
  `shippingMethod` varchar(50) DEFAULT NULL,
  `totalExMounted` int(5) DEFAULT NULL,
  `totalExUnmounted` int(5) DEFAULT NULL,
  `totalGift` int(5) DEFAULT NULL,
  `totalGiftDet` int(5) DEFAULT NULL,
  `adjustment` int(5) DEFAULT NULL,
  `invoiceBalance` int(6) DEFAULT NULL,
  `invoiceMessage` varchar(500) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `createdBy` varchar(20) DEFAULT NULL,
  `initialTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`exchangeid`),
  KEY `FK_occexch_coll` (`collid`),
  CONSTRAINT `FK_occexch_coll` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `omoccurdatasets`
  CHANGE COLUMN `datasetid` `datasetid` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT  ; 

ALTER TABLE `omoccurdatasetlink`
  CHANGE COLUMN `occid` `occid` INT(10) UNSIGNED NOT NULL  , 
  CHANGE COLUMN `datasetid` `datasetid` INT(10) UNSIGNED NOT NULL  ; 

ALTER TABLE `omoccurdatasetlink` 
  ADD CONSTRAINT `FK_omoccurdatasetlink_datasetid`
    FOREIGN KEY (`datasetid` )
    REFERENCES `omoccurdatasets` (`datasetid` ) ON DELETE CASCADE  ON UPDATE CASCADE, 
  ADD CONSTRAINT `FK_omoccurdatasetlink_occid` 
    FOREIGN KEY (`occid` )  
    REFERENCES `omoccurrences` (`occid` )  ON DELETE CASCADE  ON UPDATE CASCADE ,
  ADD INDEX `FK_omoccurdatasetlink_datasetid` (`datasetid` ASC)  ,
  ADD INDEX `FK_omoccurdatasetlink_occid` (`occid` ASC) ; 


#OCR and NLP structures
CREATE  TABLE `specprocnlp` ( 
  `spnlpid` INT(10) NOT NULL AUTO_INCREMENT , 
  `title` VARCHAR(45) NOT NULL , 
  `sqlfrag` VARCHAR(250) NOT NULL , 
  `patternmatch` VARCHAR(250) NULL , 
  `notes` VARCHAR(250) NULL ,
  `collid` INT(10) UNSIGNED NOT NULL , 
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`spnlpid`) 
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE  TABLE `specprocnlpfrag` ( 
  `spnlpfragid` INT(10) NOT NULL AUTO_INCREMENT , 
  `spnlpid` INT(10) NOT NULL , 
  `fieldname` VARCHAR(45) NOT NULL , 
  `patternmatch` VARCHAR(250) NOT NULL , 
  `notes` VARCHAR(250) NULL ,
  `sortseq` INT(5) NULL DEFAULT 50 , 
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`spnlpfragid`) 
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `specprocnlp` 
  ADD CONSTRAINT `FK_specprocnlp_collid` FOREIGN KEY (`collid` )  REFERENCES `omcollections` (`CollID` )  ON DELETE CASCADE  ON UPDATE CASCADE ,
  ADD INDEX `FK_specprocnlp_collid` (`collid` ASC) ;

ALTER TABLE `specprocnlpfrag` 
  ADD CONSTRAINT `FK_specprocnlpfrag_spnlpid`  FOREIGN KEY (`spnlpid` )  REFERENCES `specprocnlp` (`spnlpid` )  ON DELETE CASCADE  ON UPDATE CASCADE ,
  ADD INDEX `FK_specprocnlpfrag_spnlpid` (`spnlpid` ASC) ; 


ALTER TABLE `taxadescrstmts`
  CHANGE COLUMN `statement` `statement` TEXT NOT NULL  ;


ALTER TABLE `taxalinks`
  ADD COLUMN `icon` VARCHAR(45) NULL  AFTER `owner` ; 


#Identification Key
ALTER TABLE `kmcharheading`
  ADD COLUMN `sortsequence` INT NULL  AFTER `notes` ;


ALTER TABLE `images`
 DROP FOREIGN KEY `FK_images_occ` ; 

ALTER TABLE `images` 
   ADD CONSTRAINT `FK_images_occ`   FOREIGN KEY (`occid` )   REFERENCES `omoccurrences` (`occid` )   ON DELETE RESTRICT   ON UPDATE RESTRICT;


ALTER TABLE `specprocessorrawlabels`
  ADD COLUMN `source` VARCHAR(150) NULL  AFTER `notes` ,
  ADD COLUMN `sortsequence` INT NULL  AFTER `source` ,
  ADD COLUMN `score` INT NULL  AFTER `processingvariables` ; 

ALTER TABLE `specprocessorrawlabels`
  DROP FOREIGN KEY `FK_specproc_images` ;

ALTER TABLE `specprocessorrawlabels`
  ADD COLUMN `occid` INT UNSIGNED NULL  AFTER `imgid` ,
  CHANGE COLUMN `imgid` `imgid` INT(10) UNSIGNED NULL  , 
  ADD CONSTRAINT `FK_specproc_images`  FOREIGN KEY (`imgid` )  REFERENCES `images` (`imgid` )   ON UPDATE CASCADE;

ALTER TABLE `specprocessorrawlabels` 
  ADD CONSTRAINT `FK_specproc_occid` FOREIGN KEY (`occid` ) REFERENCES `omoccurrences` (`occid` ) ON DELETE CASCADE  ON UPDATE CASCADE ,
  ADD INDEX `FK_specproc_occid` (`occid` ASC) ; 


#Effort rating
ALTER TABLE `fmchecklists`
  DROP COLUMN `maptaxa` ,
  DROP COLUMN `speciescount` ,
  DROP COLUMN `genuscount` ,
  DROP COLUMN `familycount` ,
  DROP COLUMN `taxacount` ,
  ADD COLUMN `footprintWKT` TEXT NULL  AFTER `pointradiusmeters` ,
  ADD COLUMN `percenteffort` INT NULL  AFTER `footprintWKT` ; 

#checklist adjustments
ALTER TABLE `fmchecklists`
  CHANGE COLUMN `Name` `Name` VARCHAR(100) NOT NULL  ; 

ALTER TABLE `fmchklsttaxalink`
  ADD COLUMN `morphospecies` VARCHAR(45) NULL DEFAULT NULL AFTER `CLID`  ,
  DROP PRIMARY KEY  ,
  ADD PRIMARY KEY (`TID`, `CLID`, `morphospecies`) ; 


#Table for Genbank number
CREATE  TABLE `omoccurgenetic` ( 
  `idoccurgenetic` INT NOT NULL , 
  `occid` INT UNSIGNED NOT NULL , 
  `identifier` VARCHAR(150) NULL , 
  `resourcename` VARCHAR(150) NOT NULL , 
  `locus` VARCHAR(45) NULL , 
  `resourceurl` VARCHAR(500) NULL , 
  `notes` VARCHAR(45) NULL , 
  `initialtimestamp` VARCHAR(45) NULL , 
  PRIMARY KEY (`idoccurgenetic`) 
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `omoccurgenetic` 
  ADD CONSTRAINT `FK_omoccurgenetic`  FOREIGN KEY (`occid` )
   REFERENCES `omoccurrences` (`occid` )  ON DELETE CASCADE  ON UPDATE CASCADE ,
  ADD INDEX `FK_omoccurgenetic` (`occid` ASC) ; 


#Occurrence table record locking for editing
CREATE TABLE `omoccureditlocks` ( 
  `occid` INT UNSIGNED NOT NULL , 
  `uid` INT NOT NULL , 
  `ts` INT NOT NULL , 
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`occid`) 
) ENGINE=InnoDB DEFAULT CHARSET=latin1; 


#Tables for crowdsourcing
CREATE  TABLE `omcrowdsourcecentral` ( 
  `omcsid` INT NOT NULL AUTO_INCREMENT , 
  `collid` INT UNSIGNED NOT NULL , 
  `instructions` TEXT NULL , 
  `trainingurl` VARCHAR(500) NULL , 
  `editorlevel` INT NOT NULL DEFAULT 0 COMMENT '0=public, 1=public limited, 2=private', 
  `notes` VARCHAR(250) NULL , 
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`omcsid`) 
) ENGINE=InnoDB DEFAULT CHARSET=latin1; 

ALTER TABLE `omcrowdsourcecentral`  
  ADD CONSTRAINT `FK_omcrowdsourcecentral_collid`  FOREIGN KEY (`collid`)  REFERENCES `omcollections` (`CollID`)  ON DELETE RESTRICT  ON UPDATE RESTRICT ,
  ADD INDEX `FK_omcrowdsourcecentral_collid` (`collid` ASC) ; 

CREATE  TABLE `omcrowdsourcequeue` ( 
  `idomcrowdsourcequeue` INT NOT NULL AUTO_INCREMENT, 
  `omcsid` INT NOT NULL , 
  `occid` INT UNSIGNED NOT NULL , 
  `reviewstatus` INT NOT NULL DEFAULT 0 COMMENT '0=open,5=pending review, 10=closed' , 
  `uidprocessor` INT UNSIGNED NULL , 
  `points` INT NULL COMMENT '0=fail, 1=minor edits, 2=no edits <default>, 3=excelled' , 
  `notes` VARCHAR(250) NULL , 
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`idomcrowdsourcequeue`) 
) ENGINE=InnoDB DEFAULT CHARSET=latin1; 

ALTER TABLE `omcrowdsourcecentral`
  ADD UNIQUE INDEX `Index_omcrowdsourcecentral_collid` (`collid` ASC) ;

ALTER TABLE `omcrowdsourcequeue`  
  ADD CONSTRAINT `FK_omcrowdsourcequeue_occid`  FOREIGN KEY (`occid` )  REFERENCES `omoccurrences` (`occid` )  ON DELETE CASCADE  ON UPDATE CASCADE,  
  ADD CONSTRAINT `FK_omcrowdsourcequeue_uid`   FOREIGN KEY (`uidprocessor` )   REFERENCES `users` (`uid` )   ON DELETE NO ACTION  ON UPDATE CASCADE ,
  ADD INDEX `FK_omcrowdsourcequeue_occid` (`occid` ASC)  ,
  ADD INDEX `FK_omcrowdsourcequeue_uid` (`uidprocessor` ASC) ;

ALTER TABLE `omcrowdsourcequeue`
  ADD UNIQUE INDEX `Index_omcrowdsource_occid` (`occid` ASC) ;


#geographic thesaurus
CREATE  TABLE `geothescontinent` (
  `gtcid` INT NOT NULL AUTO_INCREMENT , 
  `continentterm` VARCHAR(45) NOT NULL , 
  `abbreviation` VARCHAR(45) NULL , 
  `code` VARCHAR(45) NULL , 
  `lookupterm` INT NOT NULL DEFAULT 1 , 
  `acceptedid` INT NULL , 
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`gtcid`) 
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


ALTER TABLE `geothescontinent`  
  ADD CONSTRAINT `FK_geothescontinent_accepted`  FOREIGN KEY (`acceptedid` )
   REFERENCES `geothescontinent` (`gtcid` )  ON DELETE RESTRICT  ON UPDATE RESTRICT ,
  ADD INDEX `FK_geothescontinent_accepted_idx` (`acceptedid` ASC) ; 

CREATE  TABLE `geothescountry` ( 
  `gtcid` INT NOT NULL AUTO_INCREMENT , 
  `countryterm` VARCHAR(45) NOT NULL , 
  `abbreviation` VARCHAR(45) NULL , 
  `iso` VARCHAR(2) NULL , 
  `iso3` VARCHAR(3) NULL , 
  `numcode` INT NULL , 
  `lookupterm` INT NOT NULL DEFAULT 1 , 
  `acceptedid` INT NULL , 
  `continentid` INT NULL , 
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`gtcid`) 
) ENGINE=InnoDB DEFAULT CHARSET=latin1; 

ALTER TABLE `geothescountry`  
  ADD CONSTRAINT `FK_geothescountry_gtcid`  FOREIGN KEY (`continentid` )
   REFERENCES `geothescontinent` (`gtcid` )  ON DELETE RESTRICT  ON UPDATE RESTRICT ,
  ADD INDEX `FK_geothescountry__idx` (`continentid` ASC) ;

ALTER TABLE `geothescountry` 
  ADD CONSTRAINT `FK_geothescountry_accepted`  FOREIGN KEY (`acceptedid` )
   REFERENCES `geothescountry` (`gtcid` )  ON DELETE RESTRICT  ON UPDATE RESTRICT ,
  ADD INDEX `FK_geothescountry_parent_idx` (`acceptedid` ASC) ;

CREATE  TABLE `geothesstateprovince` ( 
  `gtspid` INT NOT NULL AUTO_INCREMENT , 
  `stateterm` VARCHAR(45) NOT NULL , 
  `abbreviation` VARCHAR(45) NULL , 
  `code` VARCHAR(45) NULL , 
  `lookupterm` INT NOT NULL DEFAULT 1 , 
  `acceptedid` INT NULL , 
  `countryid` INT NULL , 
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`gtspid`) 
) ENGINE=InnoDB DEFAULT CHARSET=latin1; 

ALTER TABLE `geothesstateprovince` 
  ADD CONSTRAINT `FK_geothesstate_country`  FOREIGN KEY (`countryid` )
   REFERENCES `geothescountry` (`gtcid` )   ON DELETE RESTRICT  ON UPDATE RESTRICT ,
  ADD INDEX `FK_geothesstate_country_idx` (`countryid` ASC) ;

ALTER TABLE `geothesstateprovince` 
  ADD CONSTRAINT `FK_geothesstate_accepted`  FOREIGN KEY (`acceptedid` )
   REFERENCES `geothesstateprovince` (`gtspid` )  ON DELETE RESTRICT   ON UPDATE RESTRICT ,
  ADD INDEX `FK_geothesstate_accepted_idx` (`acceptedid` ASC) ; 

CREATE  TABLE `geothescounty` ( 
  `gtcoid` INT NOT NULL AUTO_INCREMENT , 
  `countyterm` VARCHAR(45) NOT NULL , 
  `abbreviation` VARCHAR(45) NULL , 
  `code` VARCHAR(45) NULL , 
  `lookupterm` INT NOT NULL DEFAULT 1 , 
  `acceptedid` INT NULL , 
  `stateid` INT NULL , 
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`gtcoid`) 
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `geothescounty` CHANGE COLUMN `acceptedid` `acceptedid` INT(11) NULL  , 
  ADD CONSTRAINT `FK_geothescounty_state`   FOREIGN KEY (`stateid` )
   REFERENCES `geothesstateprovince` (`gtspid` )  ON DELETE RESTRICT  ON UPDATE RESTRICT ,
  ADD INDEX `FK_geothescounty_state_idx` (`stateid` ASC) ; 

ALTER TABLE `geothescounty` 
  ADD CONSTRAINT `FK_geothescounty_accepted`   FOREIGN KEY (`acceptedid` )
   REFERENCES `geothescounty` (`gtcoid` )   ON DELETE RESTRICT   ON UPDATE RESTRICT ,
  ADD INDEX `FK_geothescounty_accepted_idx` (`acceptedid` ASC) ;

CREATE  TABLE `geothesmunicipality` ( 
  `gtmid` INT NOT NULL AUTO_INCREMENT , 
  `municipalityterm` VARCHAR(45) NOT NULL , 
  `abbreviation` VARCHAR(45) NULL , 
  `code` VARCHAR(45) NULL , 
  `lookupterm` INT NOT NULL DEFAULT 1 , 
  `acceptedid` INT NULL , 
  `countyid` INT NULL , 
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`gtmid`) 
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `geothesmunicipality` 
  ADD CONSTRAINT `FK_geothesmunicipality_county`  FOREIGN KEY (`countyid` )
   REFERENCES `geothescounty` (`gtcoid` )  ON DELETE RESTRICT  ON UPDATE RESTRICT ,
  ADD INDEX `FK_geothesmunicipality_county_idx` (`countyid` ASC) ; 

ALTER TABLE `geothesmunicipality`  
  ADD CONSTRAINT `FK_geothesmunicipality_accepted`  FOREIGN KEY (`acceptedid` )
   REFERENCES `geothescounty` (`gtcoid` )   ON DELETE RESTRICT   ON UPDATE RESTRICT ,
  ADD INDEX `FK_geothesmunicipality_accepted_idx` (`acceptedid` ASC) ; 


ALTER TABLE `omcollections`
  ADD COLUMN `bibliographicCitation` VARCHAR(1000) NULL  AFTER `rights`,
  CHANGE COLUMN `fulldescription` `fulldescription` VARCHAR(2000) NULL DEFAULT NULL ; 


#Table for host association and related occurrences
CREATE  TABLE `omassociatedoccurrences` ( 
  `aoid` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
  `occid` INT UNSIGNED NOT NULL , 
  `occidassociate` INT UNSIGNED NULL , 
  `relationship` VARCHAR(150) NOT NULL , 
  `identifier` VARCHAR(250) NULL COMMENT 'e.g. GUID' , 
  `resourceurl` VARCHAR(250) NULL , 
  `notes` VARCHAR(250) NULL , 
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`aoid`) 
) ENGINE=InnoDB DEFAULT CHARSET=latin1; 


CREATE  TABLE `omoccurverification` ( 
  `ovsid` INT NOT NULL AUTO_INCREMENT , 
  `occid` INT UNSIGNED NOT NULL , 
  `category` VARCHAR(45) NOT NULL , 
  `ranking` INT NOT NULL , 
  `protocol` VARCHAR(100) NULL , 
  `source` VARCHAR(45) NULL, 
  `uid` INT UNSIGNED NULL ,
  `notes` VARCHAR(250) NULL , 
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp , 
  PRIMARY KEY (`ovsid`) 
) ENGINE=InnoDB DEFAULT CHARSET=latin1; 

ALTER TABLE `omoccurverification` 
  ADD CONSTRAINT `FK_omoccurverification_occid`  FOREIGN KEY (`occid` )  REFERENCES `omoccurrences` (`occid` )  ON DELETE CASCADE  ON UPDATE CASCADE ,
  ADD INDEX `FK_omoccurverification_occid_idx` (`occid` ASC) ;

ALTER TABLE `omoccurverification` 
  ADD CONSTRAINT `FK_omoccurverification_uid`  FOREIGN KEY (`uid` )  REFERENCES `users` (`uid` )  ON DELETE CASCADE  ON UPDATE CASCADE ,
  ADD INDEX `FK_omoccurverification_uid_idx` (`uid` ASC) ; 


ALTER TABLE `users`
  ADD COLUMN `defaultrights` VARCHAR(250) NULL  AFTER `ispublic` ,
  ADD COLUMN `rightsholder` VARCHAR(250) NULL  AFTER `defaultrights` ; 


ALTER TABLE `omcollections`
  ADD COLUMN `guidtarget` VARCHAR(45) NULL  AFTER `PublicEdits` ; 


#briefdescription field is being depricated
UPDATE omcollections
SET fulldescription = briefdescription
WHERE briefdescription IS NOT NULL AND briefdescription <> "" AND (fulldescription IS NULL OR fulldescription = "");

UPDATE fmprojects
SET fulldescription = briefdescription
WHERE briefdescription IS NOT NULL AND briefdescription <> "" AND (fulldescription IS NULL OR fulldescription = "");


#transferring locality lookup data to new tables
INSERT INTO geothescountry(countryterm,iso,iso3,numcode)
SELECT DISTINCT countryName, iso, iso3, numcode
FROM lkupcountry;

INSERT INTO geothesstateprovince(countryid, stateterm, abbreviation)
SELECT DISTINCT gtc.gtcid, s.stateName, s.abbrev
FROM geothescountry gtc INNER JOIN lkupcountry c ON gtc.countryterm = c.countryname
INNER JOIN lkupstateprovince s ON c.countryid = s.countryid;

INSERT INTO geothescounty(stateid, countyterm)
SELECT DISTINCT gts.gtspid, trim(replace(c.countyName," County","")) as cn
FROM geothesstateprovince gts INNER JOIN lkupstateprovince s ON gts.stateterm = s.statename
INNER JOIN lkupcounty c ON s.stateid = c.stateid;