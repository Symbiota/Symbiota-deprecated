ALTER TABLE `users` CHANGE COLUMN `firstname` `firstname` VARCHAR(45) NULL  ; 

ALTER TABLE `omoccurrences` 
  ADD COLUMN `verbatimAttributes` TEXT NULL AFTER `dynamicproperties`; 

ALTER TABLE `uploadspectemp` 
  ADD COLUMN `verbatimAttributes` TEXT NULL AFTER `dynamicproperties`; 

ALTER TABLE `omoccurrences` 
  ADD COLUMN `labelProject` VARCHAR(45) NULL AFTER `duplicateQuantity`; 

ALTER TABLE `uploadspectemp` 
  ADD COLUMN `labelProject` VARCHAR(45) NULL AFTER `duplicateQuantity`; 

ALTER TABLE `images`  DROP INDEX `Index_unique` ;

ALTER TABLE `specprocessorprojects` 
  ADD COLUMN `jpgcompression` INT NULL DEFAULT 70  AFTER `lgPixWidth`;

ALTER TABLE `omoccurrences`  
  DROP INDEX `Index_unique`, 
  ADD INDEX `Index_collid` (`collid` ASC, `dbpk` ASC) ;

ALTER TABLE `omoccurrences` 
  CHANGE COLUMN `dbpk` `dbpk` VARCHAR(45) NULL  ;

