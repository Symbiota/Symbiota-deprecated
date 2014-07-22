INSERT INTO schemaversion (versionnumber) values ("0.9.1.14");

#Maintain source GUIDs from source databases for snapshot collection (e.g. Specify)
ALTER TABLE `omoccurdeterminations`
  ADD COLUMN `sourceIdentifier` VARCHAR(45) NULL  AFTER `identificationRemarks` ;
ALTER TABLE `images`
  ADD COLUMN `sourceIdentifier` VARCHAR(45) NULL  AFTER `username` ;


#Collection adjustments
ALTER TABLE `omcollections`
  ADD COLUMN `collectionId` VARCHAR(100) NULL  AFTER `CollectionName`,
  ADD COLUMN `datasetName` VARCHAR(100) NULL  AFTER `collectionId` ;

UPDATE omcollections
  SET collectionId = collectionguid 
  WHERE collectionId IS NULL AND collectionguid IS NOT NULL;

UPDATE omcollections
  SET fulldescription = briefdescription 
  WHERE fulldescription IS NULL AND briefdescription IS NOT NULL;

ALTER TABLE `omcollections`
  DROP COLUMN `briefdescription` ;

ALTER TABLE `omcollections` 
CHANGE COLUMN `accessrights` `accessrights` VARCHAR(1000) NULL DEFAULT NULL ;


ALTER TABLE `omcollcatagories`
  ADD COLUMN `icon` VARCHAR(250) NULL  AFTER `catagory`,
  ADD COLUMN `acronym` VARCHAR(45) NULL  AFTER `icon`, 
  CHANGE COLUMN `catagory` `catagory` VARCHAR(75) NOT NULL ; 

ALTER TABLE `uploadspectemp`
  CHANGE COLUMN `dbpk` `dbpk` VARCHAR(150) NULL DEFAULT NULL,
  ADD COLUMN `associatedReferences` TEXT NULL  AFTER `associatedMedia`; 

ALTER TABLE `uploadspectemp` 
  ADD COLUMN `processingStatus` VARCHAR(45) NULL AFTER `labelProject`;

ALTER TABLE `uploadspectemp` 
  ADD INDEX `Index_uploadspec_sciname` (`sciname` ASC),
  ADD INDEX `Index_uploadspec_catalognumber` (`catalogNumber` ASC);

ALTER TABLE `uploadtaxa`
  ADD COLUMN `InfraAuthor` VARCHAR(100) NULL  AFTER `Author` ,
  CHANGE COLUMN `vernacular` `vernacular` VARCHAR(250) NULL DEFAULT NULL  ;

ALTER TABLE `uploadtaxa` 
  DROP PRIMARY KEY;

ALTER TABLE `uploadtaxa` 
  DROP INDEX `sciname_index` ,
  ADD INDEX `sciname_index` (`SciName` ASC),
  ADD INDEX `scinameinput_index` (`scinameinput` ASC);


ALTER TABLE `taxstatus`
  CHANGE COLUMN `UnacceptabilityReason` `UnacceptabilityReason` VARCHAR(250) NULL DEFAULT NULL ;


#Genetic Resource adjustments
ALTER TABLE `omoccurgenetic`
  ADD COLUMN `title` VARCHAR(150) NULL  AFTER `resourcename`,
  ADD INDEX `INDEX_omoccurgenetic_name` (`resourcename` ASC) ;

UPDATE omoccurgenetic 
SET title = resourcename
WHERE title IS NULL;

UPDATE omoccurgenetic 
SET resourcename = "BoldSystems"
WHERE resourcename LIKE "%bold%";

UPDATE omoccurgenetic 
SET resourcename = "GenBank"
WHERE resourcename LIKE "%GenBank%";



#Duplicate linkages
ALTER TABLE `omoccurduplicates`
  CHANGE COLUMN `projIdentifier` `title` VARCHAR(50) NOT NULL  ,
  CHANGE COLUMN `projDescription` `description` VARCHAR(255) NULL  ,
  CHANGE COLUMN `notes` `notes` VARCHAR(255) NULL  ,
  CHANGE COLUMN `exactdupe` `dupeType` VARCHAR(45) NOT NULL DEFAULT 'Exact Duplicate';


#Adjustments to data right 
ALTER TABLE `images`
  ADD COLUMN `rights` VARCHAR(255) NULL  AFTER `copyright` ,
  ADD COLUMN `accessrights` VARCHAR(255) NULL  AFTER `rights` ;

ALTER TABLE `users`
  ADD COLUMN `rights` VARCHAR(250) NULL  AFTER `rightsholder` ,
  ADD COLUMN `accessrights` VARCHAR(250) NULL  AFTER `rights` ;

ALTER TABLE `users`
  CHANGE COLUMN `state` `state` VARCHAR(50) NULL ,
  CHANGE COLUMN `country` `country` VARCHAR(50) NULL  ;


ALTER TABLE `uploadspecparameters`
  CHANGE COLUMN `DigirCode` `Code` VARCHAR(45) NULL DEFAULT NULL,
  CHANGE COLUMN `DigirPath` `Path` VARCHAR(150) NULL DEFAULT NULL,
  CHANGE COLUMN `DigirPKField` `PkField` VARCHAR(45) NULL DEFAULT NULL  ;


#Image table 
CREATE TABLE `imagekeywords` (
  `imgkeywordid` INT NOT NULL AUTO_INCREMENT,
  `imgid` INT UNSIGNED NOT NULL,
  `keyword` VARCHAR(45) NOT NULL,
  `uidassignedby` INT UNSIGNED NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`imgkeywordid`),
  INDEX `FK_imagekeywords_imgid_idx` (`imgid` ASC),
  INDEX `FK_imagekeyword_uid_idx` (`uidassignedby` ASC),
  INDEX `INDEX_imagekeyword` (`keyword` ASC),
  CONSTRAINT `FK_imagekeywords_imgid`
    FOREIGN KEY (`imgid`)
    REFERENCES `images` (`imgid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `FK_imagekeyword_uid`
    FOREIGN KEY (`uidassignedby`)
    REFERENCES `users` (`uid`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `imageprojects` (
  `imgprojid` INT NOT NULL AUTO_INCREMENT,
  `projectname` VARCHAR(75) NOT NULL,
  `managers` VARCHAR(150) NULL,
  `description` VARCHAR(1000) NULL,
  `ispublic` INT NOT NULL DEFAULT 1,
  `notes` VARCHAR(250) NULL,
  `uidcreated` INT NULL,
  `sortsequence` INT NULL DEFAULT 50,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`imgprojid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `imageprojectlink` (
  `imgid` INT UNSIGNED NOT NULL,
  `imgprojid` INT NOT NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`imgid`, `imgprojid`),
  INDEX `FK_imageprojlink_imgprojid_idx` (`imgprojid` ASC),
  CONSTRAINT `FK_imageprojectlink_imgid`
    FOREIGN KEY (`imgid`)
    REFERENCES `images` (`imgid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `FK_imageprojlink_imgprojid`
    FOREIGN KEY (`imgprojid`)
    REFERENCES `imageprojects` (`imgprojid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


# Add multi-language support
CREATE  TABLE `adminlanguages` (
  `langid` INT NOT NULL AUTO_INCREMENT ,
  `langname` VARCHAR(45) NOT NULL ,
  `iso639_1` VARCHAR(10) NULL ,
  `iso639_2` VARCHAR(10) NULL ,
  `notes` VARCHAR(45) NULL ,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp ,
  PRIMARY KEY (`langid`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE `adminlanguages` 
  ADD UNIQUE INDEX `index_langname_unique` (`langname` ASC) ;

INSERT IGNORE INTO `adminlanguages`(langid,langname,iso639_1) 
VALUES ('1', 'English', 'en'), ('2', 'German', 'de'), ('3', 'French', 'fr'), ('4', 'Dutch', 'nl'), ('5', 'Italian', 'it'), ('6', 'Spanish', 'es'), ('7', 'Polish', 'pl'), ('8', 'Russian', 'ru'), ('9', 'Japanese', 'ja'), ('10', 'Portuguese', 'pt'), ('11', 'Swedish', 'sv'), ('12', 'Chinese', 'zh'), ('13', 'Catalan', 'ca'), ('14', 'Ukrainian', 'uk'), ('15', 'Norwegian (Bokmål)', 'no'), ('16', 'Finnish', 'fi'), ('17', 'Vietnamese', 'vi'), ('18', 'Czech', 'cs'), ('19', 'Hungarian', 'hu'), ('20', 'Korean', 'ko'), ('21', 'Indonesian', 'id'), ('22', 'Turkish', 'tr'), ('23', 'Romanian', 'ro'), ('24', 'Persian', 'fa'), ('25', 'Arabic', 'ar'), ('26', 'Danish', 'da'), ('27', 'Esperanto', 'eo'), ('28', 'Serbian', 'sr'), ('29', 'Lithuanian', 'lt'), ('30', 'Slovak', 'sk'), ('31', 'Malay', 'ms'), ('32', 'Hebrew', 'he'), ('33', 'Bulgarian', 'bg'), ('34', 'Slovenian', 'sl'), ('35', 'Volapük', 'vo'), ('36', 'Kazakh', 'kk'), ('37', 'Waray-Waray', 'war'), ('38', 'Basque', 'eu'), ('39', 'Croatian', 'hr'), ('40', 'Hindi', 'hi'), ('41', 'Estonian', 'et'), ('42', 'Azerbaijani', 'az'), ('43', 'Galician', 'gl'), ('44', 'Simple English', 'simple'), ('45', 'Norwegian (Nynorsk)', 'nn'), ('46', 'Thai', 'th'), ('47', 'Newar / Nepal Bhasa', 'new'), ('48', 'Greek', 'el'), ('49', 'Aromanian', 'roa-rup'), ('50', 'Latin', 'la'), ('51', 'Occitan', 'oc'), ('52', 'Tagalog', 'tl'), ('53', 'Haitian', 'ht'), ('54', 'Macedonian', 'mk'), ('55', 'Georgian', 'ka'), ('56', 'Serbo-Croatian', 'sh'), ('57', 'Telugu', 'te'), ('58', 'Piedmontese', 'pms'), ('59', 'Cebuano', 'ceb'), ('60', 'Tamil', 'ta'), ('61', 'Belarusian (Taraškievica)', 'be-x-old'), ('62', 'Breton', 'br'), ('63', 'Latvian', 'lv'), ('64', 'Javanese', 'jv'), ('65', 'Albanian', 'sq'), ('66', 'Belarusian', 'be'), ('67', 'Marathi', 'mr'), ('68', 'Welsh', 'cy'), ('69', 'Luxembourgish', 'lb'), ('70', 'Icelandic', 'is'), ('71', 'Bosnian', 'bs'), ('72', 'Yoruba', 'yo'), ('73', 'Malagasy', 'mg'), ('74', 'Aragonese', 'an'), ('75', 'Bishnupriya Manipuri', 'bpy'), ('76', 'Lombard', 'lmo'), ('77', 'West Frisian', 'fy'), ('78', 'Bengali', 'bn'), ('79', 'Ido', 'io'), ('80', 'Swahili', 'sw'), ('81', 'Gujarati', 'gu'), ('82', 'Malayalam', 'ml'), ('83', 'Western Panjabi', 'pnb'), ('84', 'Afrikaans', 'af'), ('85', 'Low Saxon', 'nds'), ('86', 'Sicilian', 'scn'), ('87', 'Urdu', 'ur'), ('88', 'Kurdish', 'ku'), ('89', 'Cantonese', 'zh-yue'), ('90', 'Armenian', 'hy'), ('91', 'Quechua', 'qu'), ('92', 'Sundanese', 'su'), ('93', 'Nepali', 'ne'), ('94', 'Zazaki', 'diq'), ('95', 'Asturian', 'ast'), ('96', 'Tatar', 'tt'), ('97', 'Neapolitan', 'nap'), ('98', 'Irish', 'ga'), ('99', 'Chuvash', 'cv'), ('100', 'Samogitian', 'bat-smg'), ('101', 'Walloon', 'wa'), ('102', 'Amharic', 'am'), ('103', 'Kannada', 'kn'), ('104', 'Alemannic', 'als'), ('105', 'Buginese', 'bug'), ('106', 'Burmese', 'my'), ('107', 'Interlingua', 'ia');


#changes for the Identification Key module
#Will need to make changes to code to remap to langid, then we can delete language column for KM tables at next schema update
ALTER TABLE `kmcharacters`
  CHANGE COLUMN `hid` `hid` INT(10) UNSIGNED NULL;

UPDATE kmcharacters SET hid = NULL WHERE hid = 0;

ALTER TABLE `kmcharacters` 
  ADD CONSTRAINT `FK_charheading` FOREIGN KEY (`hid` )
   REFERENCES `kmcharheading` (`hid` )  ON DELETE RESTRICT  ON UPDATE CASCADE,
  ADD INDEX `FK_charheading_idx` (`hid` ASC) ;

DROP TABLE `kmcharheadinglink`;

ALTER TABLE `kmcharheading`
  ADD COLUMN `langid` INT NULL  AFTER `language`, 
  ADD UNIQUE INDEX `unique_kmcharheading` (`headingname` ASC, `langid` ASC) ;

UPDATE `kmcharheading` h INNER JOIN `adminlanguages` a ON h.language = a.langname
  SET h.langid = a.langid 
  WHERE h.langid IS NULL;
ALTER TABLE `kmcharheading`
  CHANGE COLUMN `langid` `langid` INT(11) NOT NULL,  
  DROP PRIMARY KEY, 
  ADD PRIMARY KEY USING BTREE (`hid`, `langid`) ;

ALTER TABLE `kmcharheading` 
  ADD CONSTRAINT `FK_kmcharheading_lang`  FOREIGN KEY (`langid` )  REFERENCES `adminlanguages` (`langid` )  ON DELETE RESTRICT  ON UPDATE RESTRICT, 
  ADD INDEX `FK_kmcharheading_lang_idx` (`langid` ASC) ;

ALTER TABLE `kmchardependance`
  DROP FOREIGN KEY `FK_chardependance_cid` ;
ALTER TABLE `kmchardependance`
  DROP FOREIGN KEY `FK_chardependance_2` ;
ALTER TABLE `kmchardependance` 
  DROP INDEX `FK_chardependance_2`,
  DROP INDEX `FK_chardependance_cid` ;

ALTER TABLE `kmchardependance` 
  ADD CONSTRAINT `FK_chardependance_cid` FOREIGN KEY (`CID` )  REFERENCES `kmcharacters` (`cid` )  ON DELETE NO ACTION  ON UPDATE NO ACTION, 
  ADD CONSTRAINT `FK_chardependance_cs`  FOREIGN KEY (`CIDDependance` , `CSDependance` ) REFERENCES `kmcs` (`cid` , `cs` ) ON DELETE NO ACTION  ON UPDATE NO ACTION,
  ADD INDEX `FK_chardependance_cid_idx` (`CID` ASC),
  ADD INDEX `FK_chardependance_cs_idx` (`CIDDependance` ASC, `CSDependance` ASC) ;


ALTER TABLE `kmcharacterlang`
  ADD COLUMN `langid` INT NULL  AFTER `language`;
UPDATE `kmcharacterlang` cl INNER JOIN `adminlanguages` a ON cl.language = a.langname
  SET cl.langid = a.langid 
  WHERE cl.langid IS NULL;
ALTER TABLE `kmcharacterlang`
  CHANGE COLUMN `langid` `langid` INT(11) NOT NULL, 
  DROP PRIMARY KEY,
  ADD PRIMARY KEY USING BTREE (`cid`, `langid`) ;

ALTER TABLE `kmcs`
  DROP COLUMN `Language` ;
ALTER TABLE `kmcslang`
  ADD COLUMN `langid` INT NULL  AFTER `language`;
UPDATE `kmcslang` cl INNER JOIN `adminlanguages` a ON cl.language = a.langname
  SET cl.langid = a.langid 
  WHERE cl.langid IS NULL;
ALTER TABLE `kmcslang` CHANGE COLUMN `langid` `langid` INT(11) NOT NULL,
  DROP PRIMARY KEY,
  ADD PRIMARY KEY (`cid`, `cs`, `langid`) ;

ALTER TABLE `kmcsimages` 
  ADD CONSTRAINT `FK_kscsimages_kscs` FOREIGN KEY (`cid` , `cs` ) REFERENCES `kmcs` (`cid` , `cs` )  ON DELETE RESTRICT  ON UPDATE RESTRICT,
  ADD INDEX `FK_kscsimages_kscs_idx` (`cid` ASC, `cs` ASC) ;

ALTER TABLE `kmcsimages`
  CHANGE COLUMN `url` `url` VARCHAR(255) NOT NULL;

ALTER TABLE `omoccuridentifiers` 
  ADD INDEX `Index_value` (`identifiervalue` ASC) ;


#Transfer label projects dataset tables
INSERT INTO omoccurdatasets(name,uid)
SELECT DISTINCT labelproject, observeruid
FROM omoccurrences 
WHERE observeruid IS NOT NULL AND labelproject IS NOT NULL;

INSERT INTO omoccurdatasetlink(occid,datasetid)
SELECT o.occid, d.datasetid
FROM omoccurrences o INNER JOIN omoccurdatasets d ON o.labelproject = d.name
WHERE o.observeruid IS NOT NULL AND o.labelproject IS NOT NULL;


#Occurrence geospatial indexing
CREATE TABLE `omoccurpoints` (
   `geoID` int NOT NULL AUTO_INCREMENT,
   `occid` int NOT NULL,
   `point` point NOT NULL,
   `errradiuspoly` polygon DEFAULT NULL,
   `footprintpoly` polygon DEFAULT NULL,
   `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
   PRIMARY KEY (`geoID`),
   UNIQUE KEY `occid` (`occid`),
   SPATIAL KEY `point` (`point`)
) ENGINE=MyISAM;

INSERT INTO omoccurpoints (occid,point)
SELECT occid,Point(decimalLatitude, decimalLongitude) FROM omoccurrences WHERE decimalLatitude IS NOT NULL AND decimalLongitude IS NOT NULL;


ALTER TABLE `lkupcountry` 
  ADD INDEX `Index_lkupcountry_iso` (`iso` ASC), 
  ADD INDEX `Index_lkupcountry_iso3` (`iso3` ASC) ;

ALTER TABLE `lkupstateprovince` 
ADD INDEX `Index_lkupstate_abbr` (`abbrev` ASC) ;


CREATE  TABLE `omoccurassococcurrences` ( 
  `aoid` INT UNSIGNED NOT NULL AUTO_INCREMENT , 
  `occid` INT UNSIGNED NOT NULL , 
  `occidassociate` INT UNSIGNED NULL , 
  `relationship` VARCHAR(150) NOT NULL , 
  `identifier` VARCHAR(250) NULL COMMENT 'e.g. GUID' , 
  `resourceurl` VARCHAR(250) NULL , 
  `sciname` VARCHAR(250) NULL , 
  `tid` INT NULL , 
  `locationOnHost` VARCHAR(250) NULL , 
  `condition` VARCHAR(250) NULL , 
  `dateEmerged` DATETIME NULL , 
  `dynamicProperties` TEXT NULL ,
  `notes` VARCHAR(250) NULL , 
  `createdby` VARCHAR(45) NULL, 
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp , 
  CONSTRAINT `omossococcur_occid`  FOREIGN KEY (`occid` )  REFERENCES `omoccurrences` (`occid` )  ON DELETE CASCADE  ON UPDATE CASCADE, 
  CONSTRAINT `omossococcur_occidassoc`  FOREIGN KEY (`occidassociate` )  REFERENCES `omoccurrences` (`occid` )  ON DELETE CASCADE  ON UPDATE CASCADE,
  INDEX `omossococcur_occid_idx` (`occid` ASC),
  INDEX `omossococcur_occidassoc_idx` (`occidassociate` ASC), 
  PRIMARY KEY (`aoid`) 
)  ENGINE=InnoDB DEFAULT CHARSET=latin1; 

INSERT INTO omoccurassococcurrences(occid,occidassociate,relationship,identifier,resourceurl,notes) 
  SELECT occid,occidassociate,relationship,identifier,resourceurl,notes FROM omassociatedoccurrences;

DROP TABLE omassociatedoccurrences; 


CREATE TABLE `omoccurassoctaxa` (
  `assoctaxaid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `occid` INT UNSIGNED NOT NULL,
  `tid` INT UNSIGNED NULL,
  `verbatimstr` VARCHAR(250) NULL,
  `relationship` VARCHAR(45) NULL,
  `verificationscore` INT NULL,
  `notes` VARCHAR(250) NULL,
  `initialtimestamp` TIMESTAMP NULL,
  PRIMARY KEY (`assoctaxaid`),
  INDEX `FK_assoctaxa_occid_idx` (`occid` ASC),
  INDEX `FK_aooctaxa_tid_idx` (`tid` ASC),
  INDEX `INDEX_verbatim_str` (`verbatimstr` ASC),
  CONSTRAINT `FK_assoctaxa_occid`
    FOREIGN KEY (`occid`)
    REFERENCES `omoccurrences` (`occid`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_aooctaxa_tid`
    FOREIGN KEY (`tid`)
    REFERENCES `taxa` (`TID`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE  TABLE `salixwordstats` (
  `swsid` INT NOT NULL AUTO_INCREMENT ,
  `collid` INT UNSIGNED NOT NULL ,
  `firstword` VARCHAR(45) NOT NULL ,
  `secondword` VARCHAR(45) NULL ,
  `locality` INT(4) NOT NULL ,
  `localityFreq` INT(4) NOT NULL ,
  `habitat` INT(4) NOT NULL ,
  `habitatFreq` INT(4) NOT NULL ,
  `substrate` INT(4) NOT NULL ,
  `substrateFreq` INT(4) NOT NULL ,
  `verbatimAttributes` INT(4) NOT NULL ,
  `verbatimAttributesFreq` INT(4) NOT NULL ,
  `occurrenceRemarks` INT(4) NOT NULL ,
  `occurrenceRemarksFreq` INT(4) NOT NULL ,
  `totalcount` INT(4) NOT NULL ,
  `datelastmodified` TIMESTAMP NULL DEFAULT current_timestamp ,
  PRIMARY KEY (`swsid`) ,
  UNIQUE INDEX `INDEX_unique` (`firstword` ASC, `secondword` ASC, `collid` ASC) ,
  INDEX `FK_salixws_collid_idx` (`collid` ASC) ,
  CONSTRAINT `FK_salixws_collid`
    FOREIGN KEY (`collid` )
    REFERENCES `omcollections` (`CollID` )
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


ALTER TABLE `omcollectors`
  CHANGE COLUMN `middleinitial` `middlename` VARCHAR(45) NULL DEFAULT NULL  ,
  ADD COLUMN `guid` VARCHAR(45) NULL  AFTER `rating` ,
  ADD COLUMN `preferredrecbyid` INT UNSIGNED NULL  AFTER `guid`, 
  ADD CONSTRAINT `FK_preferred_recby`  FOREIGN KEY (`preferredrecbyid` )
    REFERENCES `omcollectors` (`recordedById` )
    ON DELETE RESTRICT
    ON UPDATE RESTRICT,
  ADD INDEX `FK_preferred_recby_idx` (`preferredrecbyid` ASC) ;


ALTER TABLE `omcollectionstats`
  ADD COLUMN `datelastmodified` DATETIME NULL AFTER `uploaddate`;


#Create a user roles table
CREATE TABLE `userroles` (
  `userroleid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `uid` INT UNSIGNED NOT NULL,
  `role` VARCHAR(45) NOT NULL,
  `tablename` VARCHAR(45) NULL,
  `tablepk` INT NULL,
  `secondaryVariable` VARCHAR(45) NULL,
  `notes` VARCHAR(250) NULL,
  `uidassignedby` INT UNSIGNED NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`userroleid`),
  INDEX `FK_userroles_uid_idx` (`uid` ASC),
  INDEX `FK_usrroles_uid2_idx` (`uidassignedby` ASC),
  INDEX `Index_userroles_table` (`tablename` ASC, `tablepk` ASC),
  CONSTRAINT `FK_userrole_uid`
    FOREIGN KEY (`uid`)
    REFERENCES `users` (`uid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `FK_userrole_uid_assigned`
    FOREIGN KEY (`uidassignedby`)
    REFERENCES `users` (`uid`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT IGNORE INTO userroles(uid,role)
SELECT uid, pname FROM userpermissions 
WHERE pname IN ("SuperAdmin","Taxonomy","KeyAdmin","KeyEditor","RareSppAdmin","RareSppReadAll","TaxonProfile");

INSERT IGNORE INTO userroles(uid,role,tablename,tablepk)
SELECT uid, "ClAdmin", "fmchecklists", substring(pname,9) FROM userpermissions 
WHERE pname LIKE "ClAdmin-%";

INSERT IGNORE INTO userroles(uid,role,tablename,tablepk)
SELECT uid, "CollAdmin", "omcollections", substring(pname,11) FROM userpermissions 
WHERE pname LIKE "CollAdmin-%";

INSERT IGNORE INTO userroles(uid,role,tablename,tablepk)
SELECT uid, "CollEditor", "omcollections", substring(pname,12) FROM userpermissions 
WHERE pname LIKE "CollEditor-%";

INSERT IGNORE INTO userroles(uid,role,tablename,tablepk)
SELECT uid, "ProjAdmin", "fmproject", substring(pname,11) FROM userpermissions 
WHERE pname LIKE "ProjAdmin-%";

INSERT IGNORE INTO userroles(uid,role,tablename,tablepk)
SELECT uid, "RareSppReader", "omcollections", substring(pname,15) FROM userpermissions 
WHERE pname LIKE "RareSppReader-%";

INSERT IGNORE INTO userroles(uid,role,tablename,tablepk,secondaryVariable)
SELECT uid, "CollTaxon", "omcollections", substring(pname,11,LOCATE(":",pname)-11) as pk,
substring(pname,LOCATE(":",pname)+1) as secVar
FROM userpermissions 
WHERE pname LIKE "CollTaxon-%";


#Inventory table modifications
CREATE TABLE `fmprojectcategories` (
  `projcatid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `pid` INT UNSIGNED NOT NULL,
  `categoryname` VARCHAR(150) NOT NULL,
  `managers` VARCHAR(100) NULL,
  `description` VARCHAR(250) NULL,
  `parentpid` INT NULL,
  `occurrencesearch` INT NULL DEFAULT 0,
  `ispublic` INT NULL DEFAULT 1,
  `notes` VARCHAR(250) NULL,
  `sortsequence` INT NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`projcatid`),
  INDEX `FK_fmprojcat_pid_idx` (`pid` ASC),
  CONSTRAINT `FK_fmprojcat_pid`
    FOREIGN KEY (`pid`)
    REFERENCES `fmprojects` (`pid`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


#Checklist
ALTER TABLE `fmchklsttaxalink` 
  ADD COLUMN `dynamicProperties` TEXT NULL AFTER `internalnotes`;

#rare and sensitive species modifications
ALTER TABLE `fmchklsttaxastatus` 
  ADD COLUMN `protectedStatus` VARCHAR(45) NULL AFTER `endemicStatus`,
  ADD COLUMN `invasiveStatus` VARCHAR(45) NULL AFTER `protectedStatus`,
  ADD COLUMN `localitySecurity` INT NULL AFTER `protectedStatus`,
  ADD COLUMN `localitySecurityReason` VARCHAR(45) NULL AFTER `localitySecurity`;


#reference tables
CREATE TABLE `referencetype` (
  `ReferenceTypeId` INT NOT NULL AUTO_INCREMENT,
  `ReferenceType` VARCHAR(45) NOT NULL,
  `IsPublished` INT NULL,
  `IsParent` INT NULL,
  `Year` VARCHAR(45) NULL,
  `Title` VARCHAR(45) NULL,
  `SecondaryTitle` VARCHAR(45) NULL,
  `PlacePublished` VARCHAR(45) NULL,
  `Publisher` VARCHAR(45) NULL,
  `Volume` VARCHAR(45) NULL,
  `NumberVolumes` VARCHAR(45) NULL,
  `Number` VARCHAR(45) NULL,
  `Pages` VARCHAR(45) NULL,
  `Section` VARCHAR(45) NULL,
  `TertiaryTitle` VARCHAR(45) NULL,
  `Edition` VARCHAR(45) NULL,
  `Date` VARCHAR(45) NULL,
  `TypeWork` VARCHAR(45) NULL,
  `ShortTitle` VARCHAR(45) NULL,
  `AlternativeTitle` VARCHAR(45) NULL,
  `ISBN_ISSN` VARCHAR(45) NULL,
  `OriginalPublication` VARCHAR(45) NULL,
  `ReprintEdition` VARCHAR(45) NULL,
  `ReviewedItem` VARCHAR(45) NULL,
  `Figures` VARCHAR(45) NULL,
  `addedByUid` INT NULL,
  `initialTimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`ReferenceTypeId`),
  UNIQUE INDEX `ReferenceType_UNIQUE` (`ReferenceType` ASC)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

INSERT INTO referencetype(ReferenceType,Year,Title,SecondaryTitle,PlacePublished,Publisher,Volume,NumberVolumes,Number,Pages,Section,TertiaryTitle,Edition,Date,TypeWork,ShortTitle,AlternativeTitle,Isbn_Issn,Figures)
VALUES("Generic","Year","Title","SecondaryTitle","PlacePublished","Publisher","Volume","NumberVolumes","Number","Pages","Section","TertiaryTitle","Edition","Date","TypeWork","ShortTitle","AlternativeTitle","Isbn_Issn","Figures"),
("Journal Article","Year","Title",NULL,NULL,NULL,"Volume",NULL,"Issue","Pages",NULL,NULL,NULL,"Date",NULL,"Short Title","Alt. Jour.",NULL,"Figures"),
("Book","Year","Title","Series Title","City","Publisher","Volume","No. Vols.","Number","Pages",NULL,NULL,"Edition","Date",NULL,"Short Title",NULL,"ISBN","Figures"),
("Book Section","Year","Title","Book Title","City","Publisher","Volume","No. Vols.","Number","Pages",NULL,"Ser. Title","Edition","Date",NULL,"Short Title",NULL,"ISBN","Figures"),
("Manuscript","Year","Title","Collection Title","City",NULL,NULL,NULL,"Number","Pages",NULL,NULL,"Edition","Date","Type Work","Short Title",NULL,NULL,"Figures"),
("Edited Book","Year","Title","Series Title","City","Publisher","Volume","No. Vols.","Number","Pages",NULL,NULL,"Edition","Date",NULL,"Short Title",NULL,"ISBN","Figures"),
("Magazine Article","Year","Title",NULL,NULL,NULL,"Volume",NULL,"Issue","Pages",NULL,NULL,NULL,"Date",NULL,"Short Title",NULL,NULL,"Figures"),
("Newspaper Article","Year","Title",NULL,"City",NULL,NULL,NULL,NULL,"Pages","Section",NULL,"Edition","Date","Type Art.","Short Title",NULL,NULL,"Figures"),
("Conference Proceedings","Year","Title","Conf. Name","Conf. Loc.","Publisher","Volume","No. Vols.",NULL,"Pages",NULL,"Ser. Title","Edition","Date",NULL,"Short Title",NULL,"ISBN","Figures"),
("Thesis","Year","Title","Academic Dept.","City","University",NULL,NULL,NULL,"Pages",NULL,NULL,NULL,"Date","Thesis Type","Short Title",NULL,NULL,"Figures"),
("Report","Year","Title",NULL,"City","Institution",NULL,NULL,NULL,"Pages",NULL,NULL,NULL,"Date","Type Work","Short Title",NULL,"Rpt. No.","Figures"),
("Personal Communication","Year","Title",NULL,"City","Publisher",NULL,NULL,NULL,NULL,NULL,NULL,NULL,"Date","Type Work","Short Title",NULL,NULL,NULL),
("Computer Program","Year","Title",NULL,"City","Publisher","Version",NULL,NULL,NULL,NULL,NULL,"Platform","Date","Type Work","Short Title",NULL,NULL,NULL),
("Electronic Source","Year","Title",NULL,NULL,"Publisher","Access Year","Extent","Acc. Date",NULL,NULL,NULL,"Edition","Date","Medium","Short Title",NULL,NULL,NULL),
("Audiovisual Material","Year","Title","Collection Title","City","Publisher",NULL,NULL,"Number",NULL,NULL,NULL,NULL,"Date","Type Work","Short Title",NULL,NULL,NULL),
("Film or Broadcast","Year","Title","Series Title","City","Distributor",NULL,NULL,NULL,"Length",NULL,NULL,NULL,"Date","Medium","Short Title",NULL,"ISBN",NULL),
("Artwork","Year","Title",NULL,"City","Publisher",NULL,NULL,NULL,NULL,NULL,NULL,NULL,"Date","Type Work","Short Title",NULL,NULL,NULL),
("Map","Year","Title",NULL,"City","Publisher",NULL,NULL,NULL,"Scale",NULL,NULL,"Edition","Date","Type Work","Short Title",NULL,NULL,NULL),
("Patent","Year","Title","Published Source","Country","Assignee","Volume","No. Vols.","Issue","Pages",NULL,NULL,NULL,"Date",NULL,"Short Title",NULL,"Pat. No.","Figures"),
("Hearing","Year","Title","Committee","City","Publisher",NULL,NULL,"Doc. No.","Pages",NULL,"Leg. Boby","Session","Date",NULL,"Short Title",NULL,NULL,NULL),
("Bill","Year","Title","Code",NULL,NULL,"Code Volume",NULL,"Bill No.","Pages","Section","Leg. Boby","Session","Date",NULL,"Short Title",NULL,NULL,NULL),
("Statute","Year","Title","Code",NULL,NULL,"Code Number",NULL,"Law No.","1st Pg.","Section",NULL,"Session","Date",NULL,"Short Title",NULL,NULL,NULL),
("Case","Year","Title",NULL,NULL,"Court","Reporter Vol.",NULL,NULL,NULL,NULL,NULL,NULL,"Date",NULL,NULL,NULL,NULL,NULL),
("Figure","Year","Title","Source Program",NULL,NULL,NULL,"-",NULL,NULL,NULL,NULL,NULL,"Date",NULL,NULL,NULL,NULL,NULL),
("Chart or Table","Year","Title","Source Program",NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,NULL,"Date",NULL,NULL,NULL,NULL,NULL),
("Equation","Year","Title","Source Program",NULL,NULL,"Volume",NULL,"Number",NULL,NULL,NULL,NULL,"Date",NULL,NULL,NULL,NULL,NULL),
("Book Series","Year","Title",NULL,"City","Publisher",NULL,"No. Vols.",NULL,"Pages",NULL,NULL,"Edition","Date",NULL,NULL,NULL,"ISBN","Figures"),
("Determination","Year","Title",NULL,NULL,"Institution",NULL,NULL,NULL,NULL,NULL,NULL,NULL,"Date",NULL,NULL,NULL,NULL,NULL),
("Sub-Reference","Year","Title",NULL,NULL,NULL,NULL,NULL,NULL,"Pages",NULL,NULL,NULL,"Date",NULL,NULL,NULL,NULL,"Figures");

ALTER TABLE `referenceobject` 
  CHANGE COLUMN `authors` `cheatauthors` VARCHAR(250) NULL AFTER `notes`,
  CHANGE COLUMN `isbn` `isbn_issn` VARCHAR(45) NULL DEFAULT NULL,
  ADD COLUMN `secondarytitle` VARCHAR(250) NULL AFTER `title`,
  ADD COLUMN `shorttitle` VARCHAR(250) NULL AFTER `secondarytitle`,
  ADD COLUMN `edition` VARCHAR(45) NULL AFTER `pubdate`,
  ADD COLUMN `numbervolumnes` VARCHAR(45) NULL AFTER `volume`,
  ADD COLUMN `section` VARCHAR(45) NULL AFTER `pages`,
  ADD COLUMN `cheatcitation` VARCHAR(250) NULL AFTER `cheatauthors`,
  DROP COLUMN `reftype`;

ALTER TABLE `referenceobject` 
DROP FOREIGN KEY `FK_refobj_journalid`;

ALTER TABLE `referenceobject` 
  ADD COLUMN `parentRefId` INT NULL AFTER `refid`,
  ADD COLUMN `ReferenceTypeId` INT NULL AFTER `parentRefId`;

ALTER TABLE `referenceobject` 
  DROP COLUMN `journalid`,
  ADD INDEX `FK_refobj_parentrefid_idx` (`parentRefId` ASC),
  DROP INDEX `FK_refobj_journalid_idx` ;

ALTER TABLE `referenceobject` 
  ADD CONSTRAINT `FK_refobj_parentrefid`
    FOREIGN KEY (`parentRefId`)
    REFERENCES `referenceobject` (`refid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE;

ALTER TABLE `referenceobject` 
  ADD INDEX `FK_refobj_typeid_idx` (`ReferenceTypeId` ASC);

ALTER TABLE `referenceobject` 
  ADD CONSTRAINT `FK_refobj_reftypeid`
    FOREIGN KEY (`ReferenceTypeId`)
    REFERENCES `referencetype` (`ReferenceTypeId`)
    ON DELETE RESTRICT
    ON UPDATE RESTRICT;

INSERT INTO referenceobject(title,shorttitle,isbn_issn,guid,notes,modifieduid,modifiedtimestamp)
SELECT journalname, journalabbr, issn, guid, notes, modifieduid, modifiedtimestamp
FROM referencejournal;

DROP TABLE `referencejournal`;


CREATE TABLE `referencecollectionlink` (
  `refid` INT NOT NULL,
  `collid` INT UNSIGNED NOT NULL,
  `initialtimestamp` TIMESTAMP NOT NULL DEFAULT current_timestamp,
  PRIMARY KEY (`refid`, `collid`),
  INDEX `FK_refcollectionlink_collid_idx` (`collid` ASC),
  CONSTRAINT `FK_refcollectionlink_refid`
    FOREIGN KEY (`refid`)
    REFERENCES `referenceobject` (`refid`)
    ON DELETE NO ACTION
    ON UPDATE NO ACTION,
  CONSTRAINT `FK_refcollectionlink_collid`
    FOREIGN KEY (`collid`)
    REFERENCES `omcollections` (`CollID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


#admin pages
CREATE TABLE `omcollpublications` (
  `pubid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `collid` INT UNSIGNED NOT NULL,
  `targeturl` VARCHAR(250) NOT NULL,
  `securityguid` VARCHAR(45) NOT NULL,
  `criteriajson` VARCHAR(250) NULL,
  `includedeterminations` INT NULL DEFAULT 1,
  `includeimages` INT NULL DEFAULT 1,
  `autoupdate` INT NULL DEFAULT 0,
  `lastdateupdate` DATETIME NULL,
  `updateinterval` INT NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`pubid`),
  INDEX `FK_adminpub_collid_idx` (`collid` ASC),
  CONSTRAINT `FK_adminpub_collid`
    FOREIGN KEY (`collid`)
    REFERENCES `omcollections` (`CollID`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


#remove dotmapper table (deprecated)
ALTER TABLE `taxamaps` 
  DROP FOREIGN KEY `FK_taxamaps_dmid`;

ALTER TABLE `taxamaps` 
  DROP COLUMN `dmid`,
  DROP INDEX `Index_unique` ,
  ADD INDEX `FK_tid_idx` (`tid` ASC),
  DROP INDEX `FK_taxamaps_dmid` ;

DROP TABLE `taxamapparams`;

#create media
CREATE TABLE `media` (
  `mediaid` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `tid` INT UNSIGNED NULL,
  `occid` INT UNSIGNED NULL,
  `url` VARCHAR(250) NOT NULL,
  `caption` VARCHAR(250) NULL,
  `authoruid` INT UNSIGNED NULL,
  `author` VARCHAR(45) NULL,
  `mediatype` VARCHAR(45) NULL,
  `owner` VARCHAR(250) NULL,
  `sourceurl` VARCHAR(250) NULL,
  `locality` VARCHAR(250) NULL,
  `description` VARCHAR(1000) NULL,
  `notes` VARCHAR(250) NULL,
  `sortsequence` INT NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`mediaid`),
  INDEX `FK_media_taxa_idx` (`tid` ASC),
  INDEX `FK_media_occid_idx` (`occid` ASC),
  INDEX `FK_media_uid_idx` (`authoruid` ASC),
  CONSTRAINT `FK_media_taxa`
    FOREIGN KEY (`tid`)
    REFERENCES `taxa` (`TID`)
    ON DELETE RESTRICT
    ON UPDATE CASCADE,
  CONSTRAINT `FK_media_occid`
    FOREIGN KEY (`occid`)
    REFERENCES `omoccurrences` (`occid`)
    ON DELETE SET NULL
    ON UPDATE CASCADE,
  CONSTRAINT `FK_media_uid`
    FOREIGN KEY (`authoruid`)
    REFERENCES `users` (`uid`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


#Taxon Profile Publications
CREATE TABLE `taxaprofilepubs` (
  `tppid` INT NOT NULL AUTO_INCREMENT,
  `pubtitle` VARCHAR(150) NOT NULL,
  `authors` VARCHAR(150) NULL,
  `description` VARCHAR(500) NULL,
  `abstract` TEXT NULL,
  `uidowner` INT UNSIGNED NULL,
  `externalurl` VARCHAR(250) NULL,
  `rights` VARCHAR(250) NULL,
  `usageterm` VARCHAR(250) NULL,
  `accessrights` VARCHAR(250) NULL,
  `ispublic` INT NULL,
  `inclusive` INT NULL,
  `dynamicProperties` TEXT NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`tppid`),
  INDEX `FK_taxaprofilepubs_uid_idx` (`uidowner` ASC),
  INDEX `INDEX_taxaprofilepubs_title` (`pubtitle` ASC),
  CONSTRAINT `FK_taxaprofilepubs_uid`
    FOREIGN KEY (`uidowner`)
    REFERENCES `users` (`uid`)
    ON DELETE SET NULL
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `taxaprofilepubimagelink` (
  `imgid` INT UNSIGNED NOT NULL,
  `tppid` INT NOT NULL,
  `caption` VARCHAR(45) NULL,
  `editornotes` VARCHAR(250) NULL,
  `sortsequence` INT NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`imgid`, `tppid`),
  INDEX `FK_tppubimagelink_id_idx` (`tppid` ASC),
  CONSTRAINT `FK_tppubimagelink_imgid`
    FOREIGN KEY (`imgid`)
    REFERENCES `images` (`imgid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `FK_tppubimagelink_id`
    FOREIGN KEY (`tppid`)
    REFERENCES `taxaprofilepubs` (`tppid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `taxaprofilepubdesclink` (
  `tdbid` INT UNSIGNED NOT NULL,
  `tppid` INT NOT NULL,
  `caption` VARCHAR(45) NULL,
  `editornotes` VARCHAR(250) NULL,
  `sortsequence` INT NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`tdbid`, `tppid`),
  INDEX `FK_tppubdesclink_id_idx` (`tppid` ASC),
  CONSTRAINT `FK_tppubdesclink_tdbid`
    FOREIGN KEY (`tdbid`)
    REFERENCES `taxadescrblock` (`tdbid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `FK_tppubdesclink_id`
    FOREIGN KEY (`tppid`)
    REFERENCES `taxaprofilepubs` (`tppid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

CREATE TABLE `taxaprofilepubmaplink` (
  `mid` INT UNSIGNED NOT NULL,
  `tppid` INT NOT NULL,
  `caption` VARCHAR(45) NULL,
  `editornotes` VARCHAR(250) NULL,
  `sortsequence` INT NULL,
  `initialtimestamp` TIMESTAMP NULL DEFAULT current_timestamp,
  PRIMARY KEY (`mid`, `tppid`),
  INDEX `FK_tppubmaplink_id_idx` (`tppid` ASC),
  CONSTRAINT `FK_tppubmaplink_tdbid`
    FOREIGN KEY (`mid`)
    REFERENCES `taxamaps` (`mid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE,
  CONSTRAINT `FK_tppubmaplink_id`
    FOREIGN KEY (`tppid`)
    REFERENCES `taxaprofilepubs` (`tppid`)
    ON DELETE CASCADE
    ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1;


#GUID adjustments
ALTER TABLE `guidoccurrences`
  DROP FOREIGN KEY `FK_guidoccurrences_occid` ;
ALTER TABLE `guidoccurdeterminations`
  DROP FOREIGN KEY `FK_guidoccurdet_detid` ;
ALTER TABLE `guidimages`
  DROP FOREIGN KEY `FK_guidimages_imgid` ;


ALTER TABLE `omoccurrences` 
  ADD COLUMN `minimumDepthInMeters` INT NULL AFTER `verbatimElevation`,
  ADD COLUMN `maximumDepthInMeters` INT NULL AFTER `minimumDepthInMeters`,
  ADD COLUMN `verbatimDepth` VARCHAR(50) NULL AFTER `maximumDepthInMeters`,
  ADD COLUMN `storageLocation` VARCHAR(100) NULL AFTER `disposition`;

ALTER TABLE `omoccurrences` 
  ADD INDEX `Index_occurrences_cult` (`cultivationStatus` ASC), 
  ADD INDEX `Index_occurrences_typestatus` (`typeStatus` ASC) ;


#determination index adjustments
ALTER TABLE `omoccurdeterminations` 
DROP INDEX `Index_unique` ,
ADD UNIQUE INDEX `Index_unique` (`occid` ASC, `dateIdentified` ASC, `identifiedBy` ASC, `sciname` ASC);

#Collection index adjustments
ALTER TABLE `omcollections` 
DROP INDEX `Index_inst` ,
ADD UNIQUE INDEX `Index_inst` (`InstitutionCode` ASC, `CollectionCode` ASC);


#Taxa table index adjustments
ALTER TABLE `taxa`
  DROP FOREIGN KEY `FK_taxa_taxonunit` ;

