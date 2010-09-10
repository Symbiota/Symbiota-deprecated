ALTER TABLE `uploadtaxa` 
 ADD COLUMN `vernacular` VARCHAR(80) AFTER `notes`,
 ADD COLUMN `vernlang` VARCHAR(15) AFTER `vernacular`,
 ADD COLUMN `SourceId` INTEGER UNSIGNED AFTER `tid`,
 ADD COLUMN `SourceParentId` INTEGER UNSIGNED AFTER `ParentStr`,
 ADD COLUMN `SourceAcceptedId` INTEGER UNSIGNED AFTER `AcceptedStr`;

ALTER TABLE `taxa` 
 MODIFY COLUMN `SecurityStatus` INT(10) UNSIGNED NOT NULL DEFAULT 0 COMMENT '0 = no security; 1 = hidden locality';

ALTER TABLE `omoccurrences` 
 MODIFY COLUMN `localitySecurity` INT(10) DEFAULT 0 COMMENT '0 = no security; 1 = hidden locality';

ALTER TABLE `uploadspectemp` 
 MODIFY COLUMN `localitySecurity` INT(10) DEFAULT 0 COMMENT '0 = display locality, 1 = hide locality';