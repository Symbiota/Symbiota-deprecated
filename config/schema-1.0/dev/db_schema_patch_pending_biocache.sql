ALTER TABLE `fmvouchers` 
  DROP FOREIGN KEY `FK_vouchers_cl`,
  DROP FOREIGN KEY `FK_fmvouchers_occ`,
  DROP INDEX `chklst_taxavouchers` ;

ALTER TABLE `fmvouchers` 
  DROP PRIMARY KEY;

ALTER TABLE `fmvouchers` 
  ADD COLUMN `vid` INT UNSIGNED NOT NULL AUTO_INCREMENT AFTER `TID`,
  ADD PRIMARY KEY (`vid`),
  ADD UNIQUE INDEX `UNIQUE_voucher` (`CLID` ASC, `occid` ASC);

  
ALTER TABLE `fmchklsttaxalink` 
  DROP FOREIGN KEY `FK_chklsttaxalink_cid`,
  DROP FOREIGN KEY `FK_chklsttaxalink_tid`;

ALTER TABLE `fmchklsttaxastatus` 
  DROP FOREIGN KEY `FK_fmchklsttaxastatus_clidtid`;
ALTER TABLE `fmchklsttaxastatus` 
  DROP INDEX `FK_fmchklsttaxastatus_clid_idx` ;

ALTER TABLE `fmcltaxacomments` 
  DROP FOREIGN KEY `FK_clcomment_cltaxa`;
ALTER TABLE `fmcltaxacomments` 
  DROP INDEX `FK_clcomment_cltaxa` ;

ALTER TABLE `fmchklstcoordinates` 
  DROP FOREIGN KEY `FKchklsttaxalink`;
ALTER TABLE `fmchklstcoordinates` 
  DROP INDEX `IndexUnique` ;

ALTER TABLE `fmchklsttaxalink` 
  DROP PRIMARY KEY;

ALTER TABLE `fmchklsttaxalink` 
  CHANGE COLUMN `TID` `TID` INT(10) UNSIGNED NOT NULL ,
  CHANGE COLUMN `CLID` `CLID` INT(10) UNSIGNED NOT NULL ,
  CHANGE COLUMN `morphospecies` `morphospecies` VARCHAR(45) NULL DEFAULT NULL ,
  ADD COLUMN `cllinkid` INT NOT NULL AUTO_INCREMENT FIRST,
  ADD PRIMARY KEY (`cllinkid`);

ALTER TABLE `fmchklsttaxalink` 
  ADD CONSTRAINT `FK_chklsttaxalink_cid`  FOREIGN KEY (`CLID`)  REFERENCES `fmchecklists` (`CLID`),
  ADD CONSTRAINT `FK_chklsttaxalink_tid`  FOREIGN KEY (`TID`)  REFERENCES `taxa` (`TID`);

