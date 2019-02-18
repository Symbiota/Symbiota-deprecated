DROP PROCEDURE if exists updateSymbiotaSchema;

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
  SET requiredVersion = '0.9.1.14';
  SET newVersion = '0.9.1.15';
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

-- (2) ******** Fill in Schema Changes to be applied in this update *********************

--  WARNING: Invokes non-deterministic UUID() function.  Do not use when MySQL replication is in use.
--  Slaves will have different UUID values from Master.

--  Enhancements to data about agents, changing omcollectors into a general agent table.

--  Tables to support picklists.  Using ct as a prefix (for code table, following arctos) for these tables.
--  Use foreign keys with on delete no action on update cascade to prevent deletions
--  from being made in the ct.. tables when their rows are in use in other tables, but
--  to propagate (which needs some care) changes in the ct.. tables to the controled
--  vocabularies that are in use in other tables.
--  e.g.  FOREIGN KEY (relationship) REFERENCES ctrelationshiptypes(relationship) ON DELETE NO ACTION ON UPDATE CASCADE

create table ctrelationshiptypes ( 
   relationship varchar(50) not null primary key, 
   inverse varchar(50),
   collective varchar(50)
)ENGINE=InnoDB DEFAULT CHARSET=latin1;
insert into ctrelationshiptypes (relationship, inverse, collective) values ('Child of', 'Parent of', 'Children');
insert into ctrelationshiptypes (relationship, inverse, collective) values ('Student of', 'Teacher of', 'Students');
insert into ctrelationshiptypes (relationship, inverse, collective) values ('Spouse of', 'Spouse of', 'Married to');
insert into ctrelationshiptypes (relationship, inverse, collective) values ('Could be', 'Confused with', 'Confused with');  -- to accompany notOtherwiseSpecified 

create table ctnametypes (
   type varchar(32) not null primary key
)ENGINE=InnoDB DEFAULT CHARSET=latin1;
insert into ctnametypes (type) values ('Full Name');
insert into ctnametypes (type) values ('Initials Last Name');
insert into ctnametypes (type) values ('Last Name, Initials');
insert into ctnametypes (type) values ('First Initials Last');
insert into ctnametypes (type) values ('First Last');
insert into ctnametypes (type) values ('Standard Abbreviation');
insert into ctnametypes (type) values ('Standard DwC List');
insert into ctnametypes (type) values ('Also Known As');

-- Create a backup snapshot of omcollectors;
create table backup_omcollectors engine=InnoDB DEFAULT CHARSET=latin1 as select * from omcollectors;

-- Begin slow bit, see discussion in commented out alternative below.

-- Rename omcollectors to agents
-- To do so, we'll need to drop and recreate foreign key constraints
alter table omcollectors drop foreign key FK_preferred_recby;
--  1 hour 31 min on 1.4 million records on a test system for this change to omoccurrences
alter table omoccurrences drop foreign key FK_omoccurrences_recbyid;

--  Can't simply rename a primary key auto_increment field with innodb engine.
--  alter table omcollectors rename to agents;   
--  alter table agents change column recordedbyid agentid int not null auto_increment primary key;
create table agents engine=InnoDB DEFAULT CHARSET=latin1 as select * from omcollectors;
alter table agents change column recordedbyid agentid bigint not null auto_increment primary key, modify initialtimestamp timestamp default now(), add index (firstname);
-- add the foreign key constraints back in
alter table agents modify preferredrecbyid bigint, add CONSTRAINT `FK_preferred_recby` FOREIGN KEY (`preferredrecbyid`) REFERENCES `agents` (`agentid`) ON DELETE NO ACTION ON UPDATE CASCADE;

--  Recreate the foreign key on omoccurrences.recordedbyid, but change delete to NO ACTION, so that agents used as collectors can't be deleted.
--  Make sure that there is an index on omoccurrences.recordedby
--  1 hour 26 min on 1.4 million records on a test system for this change to omoccurrences
alter table omoccurrences modify recordedbyid bigint, add CONSTRAINT `FK_omoccurrences_recbyid` FOREIGN KEY (`recordedById`) REFERENCES `agents` (`agentid`) ON DELETE NO ACTION ON UPDATE CASCADE, add INDEX idx_occrecordedby (recordedby);

-- Above alterations to omoccurrences are very slow on databases the size of those in production use.
-- Alternative would be to retain omcollectors to associate omoccurrences with agents.

-- Begin commented out description of alternative.

-- alter table omcollectors drop foreign key FK_preferred_recby;
-- create table agents as select * from omcollectors;
-- alter table agents change initialtimestamp initialtimestamp timestamp default CURRENT_TIMESTAMP;
-- alter table agents change column recordedbyid agentid bigint not null auto_increment primary key;

-- alter table omcollectors drop column familyname;
-- alter table omcollectors drop column firstname;
-- alter table omcollectors drop column middlename;
-- alter table omcollectors drop column startyearactive;
-- alter table omcollectors drop column endyearactive;
-- alter table omcollectors drop column notes;
-- alter table omcollectors drop column rating;
-- alter table omcollectors drop column preferredrecbyid;
-- alter table omcollectors drop column guid;
-- alter table omcollectors add column agentid bigint;
-- alter table omcollectors add CONSTRAINT `FK_coll_agentid` FOREIGN KEY (`agentid`) REFERENCES `agents` (`agentid`) on delete no action on update cascade;
-- update omcollectors set agentid = recordedbyid;
-- alter table omcollectors add column etal varchar(255);
-- alter table omcollectors add column uncertainty varchar(50);
-- alter table omcollectors add column timestamplastupdated timestamp;
-- create index idx_omcollectorsagentid on omcollectors(agentid);

--  Remove unused collector records from omcollectors, leaving them as agents.
-- update omcollectors left join omoccurrences on omcollectors.recordedbyid = omoccurrences.recordedbyid set agentid = null where omoccurrences.occid is null;
-- delete from omcollectors where agentid is null;

--  alter table agents change column preferredrecbyid preferredrecbyid bigint;
--  alter table agents add CONSTRAINT `FK_preferred_recby` FOREIGN KEY (`preferredrecbyid`) REFERENCES `agents` (`agentid`) on delete no action on update cascade;

-- End commnted out description of alternative.


--   Adding fields to agents to add more metadata about the agent.
alter table agents change column guid guid varchar(900);  --  owl:sameAs  External GUID 
alter table agents add column uuid char(43);         --  rdf:about   GUID for this record
--    copy any existing guids for agents from guid to uuid.
update agents set uuid = guid where guid is not null and uuid is null and length(guid) > 0;
--  Set uuids on remaining agents.
update agents set uuid = uuid() where uuid is null;

--  TODO: Trigger to set uuid on insert
--  alter table agents modify uuid char(43) not null default uuid();        

alter table agents add column biography text;        
alter table agents add column taxonomicgroups varchar(900);
alter table agents add column collectionsat varchar(900);
alter table agents add column curated boolean default false;
alter table agents add column nototherwisespecified boolean default false;
alter table agents add column type enum ('Individual','Team','Organization');   --  foaf:Person,Group,Organization
update agents set type = 'Individual' where type is null;
alter table agents add column prefix varchar(32);  -- approximates foaf:title or honorificPrefix
alter table agents add column suffix varchar(32);
alter table agents add column namestring text;   --  foaf:name xml:lang=en
alter table agents add column mbox_sha1sum char(40); -- foaf:mbox_sha1sum Note foaf spec, include mailto: prefix, but no trailing whitespace when computing.
alter table agents add column yearofbirth int;
alter table agents add column yearofbirthmodifier varchar(12) default '';
alter table agents add column yearofdeath int;
alter table agents add column yearofdeathmodifier varchar(12) default '';
alter table agents add column living enum('Y','N','?') not null default '?';
update agents set living = 'N' where startyearactive < 1880 or endyearactive < 1910;
update agents set living = 'Y' where startyearactive > 2000 and endyearactive is null;
alter table agents add column datelastmodified datetime;
alter table agents add column lastmodifiedbyuid int;


--  TODO:Create a fulltext search table using the MyISAM engine load it from agents.
--       and set triggers to keep it up to date with inserts, updates, and deletes in agents.
-- 
-- use the MyISAM engine so that we can put a fulltext index on the biography etc. fields.
create table agentsfulltext (
   agentsfulltextid bigint not null auto_increment primary key,
   agentid int not null,
   biography text, 
   taxonomicgroups text,
   collectionsat text,
   notes text,
   name text
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
insert into agentsfulltext(agentid, biography,taxonomicgroups,collectionsat,notes,name) select agentid, biography, taxonomicgroups, collectionsat, notes, concat (ifnull(namestring,''), ifnull(firstname,''), ' ', ifnull(middlename,''), ' ' , ifnull(familyname,'')) from agents;
create fulltext index ft_collectorbio on agentsfulltext(biography, taxonomicgroups, collectionsat, notes, name);

--  TODO: Migrate data from other existing tables where appropriate.
--   referenceauthors is a candidate for replacement, with referenceauthorlink pointing to agent. 
--   institutions is a candidate for replacement
--   user is a candidate for replacement

create table agentteams (
   --  To allow agents to represent teams of individuals.
   agentteamid bigint not null primary key auto_increment,
   teamagentid bigint not null, 
   memberagentid bigint not null, 
   ordinal int,
   FOREIGN KEY (teamagentid) REFERENCES agents(agentid) ON DELETE NO ACTION ON UPDATE CASCADE,
   FOREIGN KEY (memberagentid) REFERENCES agents(agentid) ON DELETE NO ACTION ON UPDATE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=latin1;

create table agentnumberpattern (
   --  Machine and human redable descriptions of collector number patterns
   agentnumberpatternid bigint not null primary key auto_increment,
   agentid bigint not null,
   numbertype varchar(50) default 'Collector number',
   numberpattern varchar(255),  --  regular expression for numbers that conform with this pattern
   numberpatterndescription varchar(900),  -- human readable description of the number pattern
   startyear int, --  year for first known occurrence of this number pattern
   endyear int,   --  year for last knon occurrenc of this number pattern
   integerincrement int, -- does number have an integer increment 
   notes text, 
   FOREIGN KEY (agentid) REFERENCES agents(agentid) ON DELETE CASCADE ON UPDATE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=latin1;

create table referenceagentlinks (
   --  Alowing links to references about collectors/agents (e.g. obituaries).
   refid int not null, 
   agentid int not null, 
   initialtimestamp timestamp DEFAULT CURRENT_TIMESTAMP, 
   createdbyid int not null,
   primary key (refid, agentid)
)ENGINE=InnoDB DEFAULT CHARSET=latin1;

create table agentlinks (
   --  Supporting hyperlinks out to external sources of information about collectors/agents.
   agentlinksid bigint primary key not null auto_increment, 
   agentid int not null, 
   type varchar(50), 
   link varchar(900), 
   isprimarytopicof boolean not null default true,  --  link can be represented as foaf:primaryTopicOf
   text varchar(50), 
   timestampcreated timestamp DEFAULT CURRENT_TIMESTAMP, 
   createdbyuid int not null, 
   datelastmodified datetime, 
   lastmodifiedbyuid int
)ENGINE=InnoDB DEFAULT CHARSET=latin1;


create table agentnames (
   --  Supporting multiple variant forms of names and names for a collector/agent
   agentnamesid bigint primary key not null auto_increment, 
   agentid int not null,  
   type varchar(32) not null default 'Full Name', 
   name  varchar(255), 
   language varchar(6) default 'en_us', 
   timestampcreated timestamp DEFAULT CURRENT_TIMESTAMP, 
   createdbyuid int null, 
   datelastmodified datetime, 
   lastmodifiedbyuid int,
   FOREIGN KEY (type) REFERENCES ctnametypes(type) ON DELETE NO ACTION ON UPDATE CASCADE,
   FOREIGN KEY (agentid)  REFERENCES agents(agentid)  ON DELETE CASCADE  ON UPDATE CASCADE,
   CONSTRAINT UNIQUE INDEX (agentid,type,name) --  Combination of recordedbyid, name, and type must be unique.
) ENGINE=MyISAM DEFAULT CHARSET=latin1;  -- to ensure support for fulltext index
create fulltext index ft_collectorname on agentnames(name);

--  Populate agent names from any existing agents
update agents set middlename = '' where middlename is null;
update agents set firstname = '' where firstname is null;
update agents set familyname = '' where familyname is null;


insert into agentnames(agentid, type, name) select agentid, 'Full Name',  concat(firstname, ' ', middlename, ' ',  familyname) from agents where firstname not like '%.%' and middlename not like '%.%' and length(firstname)>1 and length(middlename) > 1;
insert into agentnames(agentid, type, name) select agentid, 'Initials Last Name',  concat(left(firstname,1), '. ', left(middlename,1), '. ',  familyname) from agents where firstname not like '%.%' and middlename not like '%.%' and length(firstname)>1 and length(middlename) > 1;
insert into agentnames(agentid, type, name) select agentid, 'Last Name, Initials',  concat(familyname, ', ' , left(firstname,1), '. ', left(middlename,1), '.') from agents where firstname not like '%.%' and middlename not like '%.%' and length(firstname)>1 and length(middlename) > 1;

insert into agentnames(agentid, type, name) select agentid, 'First Last',  concat(firstname, ' ',  familyname) from agents where firstname not like '%.%' and middlename = '' and length(firstname)>1;

insert into agentnames(agentid, type, name) select agentid, 'Initials Last Name',  concat(firstname, ' ', middlename, ' ' ,  familyname) from agents where firstname like '%.' and middlename like '%.' and length(firstname)<3 and length(middlename) < 3;
insert into agentnames(agentid, type, name) select agentid, 'Last Name, Initials',  concat(familyname, ', ', firstname, ' ' ,  middlename) from agents where firstname like '%.' and middlename like '%.' and length(firstname)<3 and length(middlename) < 3;

insert into agentnames(agentid, type, name) select agentid, 'First Initials Last',  concat(firstname, ' ', middlename, ' ',  familyname) from agents where firstname not like '%.%' and ((middlename like '%.' and length(middlename)<3) or length(middlename)=1) and length(firstname)>1;
insert into agentnames(agentid, type, name) select agentid, 'Initials Last Name',  concat(left(firstname,1), '. ', left(middlename,1), '. ',  familyname) from agents where firstname not like '%.%' and ((middlename like '%.' and length(middlename)<3) or length(middlename)=1) and length(firstname)>1 ;
insert into agentnames(agentid, type, name) select agentid, 'Last Name, Initials',  concat(familyname, ', ' , left(firstname,1), '. ', left(middlename,1), '.') from agents where firstname not like '%.%' and ((middlename like '%.' and length(middlename)<3) or length(middlename)=1) and length(firstname)>1;

--  Put all leftovers into AKA.
insert into agentnames(agentid, name, type) select a.agentid, trim(replace(concat(firstname, ' ', middlename, ' ', familyname),'  ',' ')), 'Also Known As' from agents a left join agentnames n on a.agentid = n.agentid where n.agentid is null;


create table agentrelations (
   --  Representing relationships (family,marrage,mentorship) amongst agents.
   agentrelationsid bigint not null primary key auto_increment, 
   fromagentid bigint not null,  --  parent agent in this relationship 
   toagentid bigint not null,    --  child agent in this relationship 
   relationship varchar(50) not null,  -- nature of relationship from ctrelationshiptypes 
   notes varchar(900),
   timestampcreated timestamp DEFAULT CURRENT_TIMESTAMP, 
   createdbyuid int, 
   datelastmodified datetime, 
   lastmodifiedbyuid int,
   FOREIGN KEY (fromagentid) REFERENCES agents(agentid) ON DELETE CASCADE ON UPDATE CASCADE,
   FOREIGN KEY (toagentid) REFERENCES agents(agentid) ON DELETE CASCADE ON UPDATE CASCADE,
   FOREIGN KEY (relationship) REFERENCES ctrelationshiptypes(relationship) ON DELETE NO ACTION ON UPDATE CASCADE
)ENGINE=InnoDB DEFAULT CHARSET=latin1;


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
