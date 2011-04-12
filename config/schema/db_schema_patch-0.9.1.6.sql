ALTER TABLE `omoccurrences` 
  ADD COLUMN `verbatimAttributes` TEXT NULL DEFAULT NULL  AFTER `dynamicProperties` ; 

ALTER TABLE `uploadspectemp` 
  ADD COLUMN `verbatimAttributes` TEXT NULL DEFAULT NULL  AFTER `dynamicProperties` ; 
