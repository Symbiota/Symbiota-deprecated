--  DROP PROCEDURE updateSymbiotaSchema;

DELIMITER |

CREATE PROCEDURE updateSymbiotaSchema ()

BEGIN
  DECLARE requiredVersion varchar(20);  -- version needed for update to fire
  DECLARE currentVersion varchar(20);   -- version present in schema
  DECLARE newVersion varchar(20);       -- version this update will apply
  DECLARE okToUpdate boolean DEFAULT FALSE;
  DECLARE done boolean DEFAULT FALSE;
  DECLARE curVersion CURSOR for select versionnumber from schemaversion order by dateapplied desc limit 1;
  DECLARE CONTINUE HANDLER FOR NOT FOUND SET done = TRUE;

  --  (1)  Change the version numbers ****************************
  --  Previous version must be this version for the update to fire
  SET requiredVersion = '0.9.1.12';
  SET newVersion = '0.9.1.13';
  --  ************************************************************

  OPEN curVersion;
  
  verLoop: LOOP
     FETCH curVersion into currentVersion;
     IF done THEN
        LEAVE verLoop;
     END IF;
     IF currentVersion = requiredVersion THEN 
        SET okToUpdate = TRUE;
     END IF;
  END LOOP;

IF okToUpdate THEN 

START TRANSACTION;

INSERT INTO schemaversion (versionnumber) values (newVersion);

-- (2) ******** Schema Changes to be applied in this update *********************

--  authority table for allowed values for image classification tags
CREATE TABLE imagetagkey ( 
   tagkey varchar(30) not null primary key, -- magic value that will be used by code to determine workflow behavior
   shortlabel varchar(30) not null,  --  Label to display on picklists
   description_en  varchar(255) not null,  --  longer description to display with e.g. checkboxes
   sortorder int not null,  -- sort order that can be used to order picklists or checkboxes on page layouts.
   initialtimestamp TIMESTAMP NOT NULL DEFAULT current_timestamp,
   INDEX (sortorder) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('HasOrganism','Image shows an organism.','Organism',0);
INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('HasLabel','Image shows label data.','Label',10);
INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('HasIDLabel','Image shows an annotation/identification label.','Annotation',20);
INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('TypedText','Image has typed or printed text.','Typed/Printed',30);
INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('Handwriting','Image has handwritten label text.','Handwritten',40);
INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('ShowsHabitat','Field image of habitat.','Habitat',50);
INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('HasProblem','There is a problem with this image.','QC Problem',60);
INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('ImageOfAdult','Image contains the adult organism.','Adult',80);
INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('Diagnostic','Image contains a diagnostic character.','Diagnostic',70);
INSERT into imagetagkey (tagkey,description_en,shortlabel,sortorder) values ('ImageOfImmature','Image contains the immature organism.','Immature',90);


--  table applying an image classification tag to an image
CREATE TABLE imagetag (
   imagetagid bigint primary key not null auto_increment,
   imgid int UNSIGNED NOT NULL,
   keyvalue varchar(30) NOT NULL,
   initialtimestamp TIMESTAMP NOT NULL DEFAULT current_timestamp,
   INDEX (keyvalue), 
   CONSTRAINT UNIQUE INDEX (imgid,keyvalue) 
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `imagetag`
  ADD CONSTRAINT `FK_imagetag_imgid`
   FOREIGN KEY (`imgid` )  REFERENCES `images` (`imgid` )  ON DELETE CASCADE  ON UPDATE CASCADE, 
  ADD CONSTRAINT `FK_imagetag_tagkey`
   FOREIGN KEY (`keyvalue` )  REFERENCES `imagetagkey` (`tagkey` )  ON DELETE NO ACTION  ON UPDATE CASCADE,
  ADD INDEX `FK_imagetag_imgid_idx` (`imgid` ASC) ;


-- Add field to allow pending determinations
ALTER TABLE `omoccurdeterminations`
  ADD COLUMN `appliedStatus` INT(2) NULL DEFAULT 1  AFTER `iscurrent` ;


-- Add field to store geographic scope context for user taxonomy 
ALTER TABLE `usertaxonomy`
  ADD COLUMN `geographicScope` VARCHAR(250) NULL  AFTER `editorstatus` ;

ALTER TABLE `usertaxonomy` 
  DROP INDEX `usertaxonomy_UNIQUE` ,
  ADD UNIQUE INDEX `usertaxonomy_UNIQUE` (`uid` ASC, `tid` ASC, `taxauthid` ASC, `editorstatus` ASC);


-- add Unique index to omoccurverification
ALTER TABLE `omoccurverification` 
   ADD UNIQUE INDEX `UNIQUE_omoccurverification` (`occid` ASC, `category` ASC) ;

-- Tag all images needing to be verified
INSERT IGNORE INTO omoccurverification (occid,category,ranking)
  SELECT o.occid, "identification", 0
   FROM omoccurrences o LEFT JOIN taxa t ON o.tidinterpreted = t.tid
   WHERE t.rankid < 220 or o.tidinterpreted is null ; 

--  Add indexes for searching on elevation
create index occelevmax on omoccurrences(maximumelevationinmeters) ;
create index occelevmin on omoccurrences(minimumelevationinmeters) ;

--  Add table for tracking action requests on occurrences and other arbitrry tables.
CREATE TABLE actionrequesttype ( 
   requesttype varchar(30) not null primary key,
   context varchar(255),  --  if not null, the table for actionrequest.tablename
   description varchar(255),
   initialtimestamp TIMESTAMP NOT NULL DEFAULT current_timestamp
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE actionrequest (
   actionrequestid bigint primary key not null auto_increment,
   fk int not null,             --  foreign key to the row in {tablename} to which this request applies
   tablename varchar(255),      --  table to which this request applies
   requesttype varchar(30) not null,  -- kind of request 
   uid_requestor int UNSIGNED NOT NULL,  --  uid of the person making the request
   requestdate timestamp,       --  timestamp of creation of request 
   requestremarks varchar(900), --  description of request by requestor 
   priority int,                --  BOM priority, P1, P2, P3, P4, P5.
   uid_fullfillor int UNSIGNED NOT NULL,   --  uid of the person acting on the request
   state varchar(12),           --  wf:state, BOM states (New,Assigned,Resolved, etc.)
   resolution varchar(12),      --  BOM resolutions (WontFix,Duplicate,Fix, etc.) for state resolved.
   statesetdate datetime,       --  date/time the state was changed
   resolutionremarks varchar(900)  --  notes about the resolution ~ BOM comment
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

ALTER TABLE `actionrequest`
  ADD CONSTRAINT `FK_actionreq_uid1`
   FOREIGN KEY (`uid_requestor` )
   REFERENCES `users` (`uid` )  ON DELETE CASCADE  ON UPDATE CASCADE, 
  ADD CONSTRAINT `FK_actionreq_uid2`
   FOREIGN KEY (`uid_fullfillor` )
   REFERENCES `users` (`uid` )  ON DELETE CASCADE  ON UPDATE CASCADE, 
  ADD CONSTRAINT `FK_actionreq_type`
   FOREIGN KEY (`requesttype` )
   REFERENCES `actionrequesttype` (`requesttype` )  ON DELETE CASCADE  ON UPDATE CASCADE,
  ADD INDEX `FK_actionreq_uid1_idx` (`uid_requestor` ASC),
  ADD INDEX `FK_actionreq_uid2_idx` (`uid_fullfillor` ASC) ,
  ADD INDEX `FK_actionreq_type_idx` (`requesttype` ASC) ;



INSERT INTO actionrequesttype (requesttype,description,context)
  VALUES ('Imaging','Create high resolution images of this specimen for identification.','');
INSERT INTO actionrequesttype (requesttype,description,context)
  VALUES ('ReplaceImage','Image Has a QC Problem, Replace it.','images');
INSERT INTO actionrequesttype (requesttype,description,context)
  VALUES ('ReproductiveState','Code the Reproductive State for this specimen.','omoccurrences');


--  ******* End of Schema Changes to be applied in this update 

COMMIT;

--  if in MySQL/MARIADB 5.2+ where SIGNAL is supported, can return an error condition
--  ELSE
   -- SIGNAL SQLSTATE VALUE '99999'
   --   SET MESSAGE_TEXT = 'Prerequisite schema version not found ' ;
END IF; 

END|

DELIMITER ;

CALL updateSymbiotaSchema();

