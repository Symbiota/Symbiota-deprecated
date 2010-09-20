ALTER TABLE `uploadtaxa`
 ADD COLUMN `vernacular` VARCHAR(80) AFTER `notes`;
ALTER TABLE `uploadtaxa`
 ADD COLUMN `vernlang` VARCHAR(15) AFTER `vernacular`;
ALTER TABLE `uploadtaxa`
 ADD COLUMN `SourceId` INTEGER UNSIGNED AFTER `tid`;
ALTER TABLE `uploadtaxa`
 ADD COLUMN `SourceParentId` INTEGER UNSIGNED AFTER `ParentStr`;
ALTER TABLE `uploadtaxa`
 ADD COLUMN `SourceAcceptedId` INTEGER UNSIGNED AFTER `AcceptedStr`;
ALTER TABLE `uploadtaxa` DROP COLUMN `SourcePK`;
ALTER TABLE `uploadtaxa` DROP COLUMN `SourceParentPK`;
ALTER TABLE `uploadtaxa` DROP COLUMN `SourceAcceptedPK`;

ALTER TABLE `taxa` 
 MODIFY COLUMN `SecurityStatus` INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = no security; 1 = hidden locality';

ALTER TABLE `uploadtaxa` 
 MODIFY COLUMN `SecurityStatus` INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = no security; 1 = hidden locality';

ALTER TABLE `omoccurrences` 
 MODIFY COLUMN `localitySecurity` INT(10) DEFAULT 0 COMMENT '0 = no security; 1 = hidden locality';

ALTER TABLE `uploadspectemp` 
 MODIFY COLUMN `localitySecurity` INT(10) DEFAULT 0 COMMENT '0 = display locality, 1 = hide locality';

ALTER TABLE `uploadspectemp`
    ADD CONSTRAINT `FK_uploadspectemp_coll` FOREIGN KEY `FK_uploadspectemp_coll` (`collid`)
    REFERENCES `omcollections` (`CollID`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT;