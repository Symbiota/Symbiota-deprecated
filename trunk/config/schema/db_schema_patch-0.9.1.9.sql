ALTER TABLE `omoccurrences` 
  DROP INDEX `Index_collid`, 
  ADD UNIQUE INDEX `Index_collid` (`collid` ASC, `dbpk` ASC) ;

ALTER TABLE `omoccurrences`
  ADD INDEX `Index_catalognumber` (`catalogNumber` ASC) ;


#tables for duplicate projects => one-to-many
	omoccurdupeproj => dupprojid (pk), projidentifier (text 30), projname (text 255), ExsiccataEditors (text 150), notes (text 255)


#tables for general specimen projects for management => many-to-many


#Loan project tables
	omoccurloansout => 


#Lookup table for recordedby field 

