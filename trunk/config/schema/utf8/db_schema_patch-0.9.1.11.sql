ALTER TABLE `institutions`
  ADD COLUMN `InstitutionName2` VARCHAR(150) NULL DEFAULT NULL  AFTER `InstitutionName` ,
  CHANGE COLUMN `Contact` `Contact` VARCHAR(50) NULL DEFAULT NULL  ,
  CHANGE COLUMN `Notes` `Notes` VARCHAR(200) NULL DEFAULT NULL  ;
  
ALTER TABLE `omoccurdeterminations`
  CHANGE COLUMN `identifiedBy` `identifiedBy` VARCHAR(50) NOT NULL  ;
  
#Exchange and Gift table
CREATE  TABLE `omoccurexchange` (   
  `exchangeid` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
  `acronym` VARCHAR(20) NULL ,
  `iid` INT NULL ,
  `in_out` VARCHAR(3) NULL ,
  `dateSent` DATETIME NULL ,
  `dateReceived` DATETIME NULL ,
  `totalBoxes` INT(5) NULL ,
  `shippingMethod` VARCHAR(50) NULL ,
  `totalExMounted` INT(5) NULL ,
  `totalExUnmounted` INT(5) NULL ,
  `totalGift` INT(5) NULL ,
  `totalGiftDet` INT(5) NULL ,
  `adjustment` INT(5) NULL ,
  `invoiceBalance` INT(6) NULL ,
  `invoiceMessage` VARCHAR(500) NULL ,
  `notes` VARCHAR(500) NULL ,
  `createdBy` VARCHAR(20) NULL ,
  `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp ,   
  PRIMARY KEY (`exchangeid`) 
) ENGINE = InnoDB DEFAULT CHARSET=utf8;
  
#Loan-in table
CREATE  TABLE `omoccurloansin` (   
  `loaninid` INT UNSIGNED NOT NULL AUTO_INCREMENT, 
  `loanIdentifier` VARCHAR(30) NOT NULL,
  `ownerLoanIdentifier` VARCHAR(45) NULL,
  `acronym` VARCHAR(20) NULL,
  `iid` INT NULL,
  `dateReceived` DATETIME NULL,
  `dateDue` DATETIME NULL,
  `totalBoxes` INT(5) NULL ,
  `totalSpecimens` INT(5) NULL ,
  `forWhom` VARCHAR(45) NULL,
  `description` VARCHAR(500) NULL,
  `createdBy` VARCHAR(20) NULL ,
  `processedBy` VARCHAR(20) NULL ,
  `dateReturned` DATETIME NULL,
  `ret_totalBoxes` INT(5) NULL ,
  `ret_shippingMethod` VARCHAR(50) NULL ,
  `ret_processedBy` VARCHAR(20) NULL ,
  `returnMessage` VARCHAR(500) NULL ,
  `notes` VARCHAR(500) NULL ,
  `complete` INT(1) NOT NULL DEFAULT '0' ,
  `initialTimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp ,   
  PRIMARY KEY (`loaninid`) 
) ENGINE = InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `omoccurloansout`
  ADD COLUMN `totalSpecimens` INT(5) NULL  AFTER `dateDue` ,
  ADD COLUMN `totalBoxes` INT(5) NULL  AFTER `dateSent` ,
  ADD COLUMN `shippingMethod` VARCHAR(50) NULL  AFTER `totalBoxes` ,
  ADD COLUMN `processedBy` VARCHAR(20) NULL  AFTER `dateSent` ,
  ADD COLUMN `dateReturned` DATETIME NULL  AFTER `shippingdetails` ,
  ADD COLUMN `ret_processedBy` VARCHAR(20) NULL  AFTER `dateReturned` ,
  ADD COLUMN `complete` INT(1) NOT NULL DEFAULT '0'  AFTER `ret_processedBy` ,
  ADD COLUMN `createdBy` VARCHAR(20) NULL  AFTER `description` ,
  CHANGE COLUMN `dateSent` `dateSent` DATETIME NULL ,
  CHANGE COLUMN `notes` `notes` VARCHAR(500) NULL ,
  CHANGE COLUMN `forWhom` `forWhom` VARCHAR(50) NULL ,
  CHANGE COLUMN `description` `description` VARCHAR(1000) NULL ;

ALTER TABLE `omoccurrences`
  ADD COLUMN `specimenNotes` VARCHAR(250) DEFAULT NULL  AFTER `ownerInstitutionCode` ,
  ADD COLUMN `utmZoning` VARCHAR(5) DEFAULT NULL  AFTER `decimalLongitude` ,
  ADD COLUMN `utmEasting` VARCHAR(10) DEFAULT NULL  AFTER `utmZoning` ,
  ADD COLUMN `utmNorthing` VARCHAR(10) DEFAULT NULL  AFTER `utmEasting` ,
  ADD COLUMN `township` VARCHAR(50) DEFAULT NULL  AFTER `utmNorthing` ,
  ADD COLUMN `range` VARCHAR(50) DEFAULT NULL  AFTER `township` ,
  ADD COLUMN `section` VARCHAR(50) DEFAULT NULL  AFTER `range` ,
  ADD COLUMN `secDetails` VARCHAR(50) DEFAULT NULL  AFTER `section` ,
  CHANGE COLUMN `disposition` `disposition` VARCHAR(180) DEFAULT NULL COMMENT 'Dups to' ,
  ADD COLUMN `deaccessioned` INT(1) NOT NULL DEFAULT '0'  AFTER `processingstatus` ,
  ADD COLUMN `deaccessionedReason` VARCHAR(250) DEFAULT NULL  AFTER `processingstatus` ,
  CHANGE COLUMN `labelProject` `labelProject` VARCHAR(50) DEFAULT NULL ;