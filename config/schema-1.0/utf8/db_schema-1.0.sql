
/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

--
-- Table structure for table `actionrequest`
--

DROP TABLE IF EXISTS `actionrequest`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actionrequest` (
  `actionrequestid` bigint(20) NOT NULL AUTO_INCREMENT,
  `fk` int(11) NOT NULL,
  `tablename` varchar(255) DEFAULT NULL,
  `requesttype` varchar(30) NOT NULL,
  `uid_requestor` int(10) unsigned NOT NULL,
  `requestdate` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `requestremarks` varchar(900) DEFAULT NULL,
  `priority` int(11) DEFAULT NULL,
  `uid_fullfillor` int(10) unsigned NOT NULL,
  `state` varchar(12) DEFAULT NULL,
  `resolution` varchar(12) DEFAULT NULL,
  `statesetdate` datetime DEFAULT NULL,
  `resolutionremarks` varchar(900) DEFAULT NULL,
  PRIMARY KEY (`actionrequestid`),
  KEY `FK_actionreq_uid1_idx` (`uid_requestor`),
  KEY `FK_actionreq_uid2_idx` (`uid_fullfillor`),
  KEY `FK_actionreq_type_idx` (`requesttype`),
  CONSTRAINT `FK_actionreq_uid1` FOREIGN KEY (`uid_requestor`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_actionreq_uid2` FOREIGN KEY (`uid_fullfillor`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_actionreq_type` FOREIGN KEY (`requesttype`) REFERENCES `actionrequesttype` (`requesttype`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `actionrequesttype`
--

DROP TABLE IF EXISTS `actionrequesttype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `actionrequesttype` (
  `requesttype` varchar(30) NOT NULL,
  `context` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`requesttype`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `adminlanguages`
--

DROP TABLE IF EXISTS `adminlanguages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `adminlanguages` (
  `langid` int(11) NOT NULL AUTO_INCREMENT,
  `langname` varchar(45) NOT NULL,
  `iso639_1` varchar(10) DEFAULT NULL,
  `iso639_2` varchar(10) DEFAULT NULL,
  `notes` varchar(45) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`langid`),
  UNIQUE KEY `index_langname_unique` (`langname`)
) ENGINE=InnoDB AUTO_INCREMENT=108 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `adminstats`
--

DROP TABLE IF EXISTS `adminstats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `adminstats` (
  `idadminstats` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(45) NOT NULL,
  `statname` varchar(45) NOT NULL,
  `statvalue` int(11) DEFAULT NULL,
  `statpercentage` int(11) DEFAULT NULL,
  `dynamicProperties` text,
  `groupid` int(11) NOT NULL,
  `collid` int(10) unsigned DEFAULT NULL,
  `uid` int(10) unsigned DEFAULT NULL,
  `note` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idadminstats`),
  KEY `FK_adminstats_collid_idx` (`collid`),
  KEY `FK_adminstats_uid_idx` (`uid`),
  KEY `Index_adminstats_ts` (`initialtimestamp`),
  KEY `Index_category` (`category`),
  CONSTRAINT `FK_adminstats_collid` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_adminstats_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `agentlinks`
--

DROP TABLE IF EXISTS `agentlinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agentlinks` (
  `agentlinksid` bigint(20) NOT NULL AUTO_INCREMENT,
  `agentid` int(11) NOT NULL,
  `type` varchar(50) DEFAULT NULL,
  `link` varchar(900) DEFAULT NULL,
  `isprimarytopicof` tinyint(1) NOT NULL DEFAULT '1',
  `text` varchar(50) DEFAULT NULL,
  `timestampcreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdbyuid` int(11) NOT NULL,
  `datelastmodified` datetime DEFAULT NULL,
  `lastmodifiedbyuid` int(11) DEFAULT NULL,
  PRIMARY KEY (`agentlinksid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `agentnames`
--

DROP TABLE IF EXISTS `agentnames`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agentnames` (
  `agentnamesid` bigint(20) NOT NULL AUTO_INCREMENT,
  `agentid` int(11) NOT NULL,
  `type` varchar(32) NOT NULL DEFAULT 'Full Name',
  `name` varchar(255) DEFAULT NULL,
  `language` varchar(6) DEFAULT 'en_us',
  `timestampcreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdbyuid` int(11) DEFAULT NULL,
  `datelastmodified` datetime DEFAULT NULL,
  `lastmodifiedbyuid` int(11) DEFAULT NULL,
  PRIMARY KEY (`agentnamesid`),
  UNIQUE KEY `agentid` (`agentid`,`type`,`name`),
  KEY `type` (`type`),
  FULLTEXT KEY `ft_collectorname` (`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `agentnumberpattern`
--

DROP TABLE IF EXISTS `agentnumberpattern`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agentnumberpattern` (
  `agentnumberpatternid` bigint(20) NOT NULL AUTO_INCREMENT,
  `agentid` bigint(20) NOT NULL,
  `numbertype` varchar(50) DEFAULT 'Collector number',
  `numberpattern` varchar(255) DEFAULT NULL,
  `numberpatterndescription` varchar(900) DEFAULT NULL,
  `startyear` int(11) DEFAULT NULL,
  `endyear` int(11) DEFAULT NULL,
  `integerincrement` int(11) DEFAULT NULL,
  `notes` text,
  PRIMARY KEY (`agentnumberpatternid`),
  KEY `agentid` (`agentid`),
  CONSTRAINT `agentnumberpattern_ibfk_1` FOREIGN KEY (`agentid`) REFERENCES `agents` (`agentid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `agentrelations`
--

DROP TABLE IF EXISTS `agentrelations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agentrelations` (
  `agentrelationsid` bigint(20) NOT NULL AUTO_INCREMENT,
  `fromagentid` bigint(20) NOT NULL,
  `toagentid` bigint(20) NOT NULL,
  `relationship` varchar(50) NOT NULL,
  `notes` varchar(900) DEFAULT NULL,
  `timestampcreated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdbyuid` int(11) DEFAULT NULL,
  `datelastmodified` datetime DEFAULT NULL,
  `lastmodifiedbyuid` int(11) DEFAULT NULL,
  PRIMARY KEY (`agentrelationsid`),
  KEY `fromagentid` (`fromagentid`),
  KEY `toagentid` (`toagentid`),
  KEY `relationship` (`relationship`),
  CONSTRAINT `agentrelations_ibfk_1` FOREIGN KEY (`fromagentid`) REFERENCES `agents` (`agentid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `agentrelations_ibfk_2` FOREIGN KEY (`toagentid`) REFERENCES `agents` (`agentid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `agentrelations_ibfk_3` FOREIGN KEY (`relationship`) REFERENCES `ctrelationshiptypes` (`relationship`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `agents`
--

DROP TABLE IF EXISTS `agents`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agents` (
  `agentid` bigint(20) NOT NULL AUTO_INCREMENT,
  `familyname` varchar(45) NOT NULL,
  `firstname` varchar(45) DEFAULT NULL,
  `middlename` varchar(45) DEFAULT NULL,
  `startyearactive` int(11) DEFAULT NULL,
  `endyearactive` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `rating` int(11) DEFAULT '10',
  `guid` varchar(900) DEFAULT NULL,
  `preferredrecbyid` bigint(20) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `uuid` char(43) DEFAULT NULL,
  `biography` text,
  `taxonomicgroups` varchar(900) DEFAULT NULL,
  `collectionsat` varchar(900) DEFAULT NULL,
  `curated` tinyint(1) DEFAULT '0',
  `nototherwisespecified` tinyint(1) DEFAULT '0',
  `type` enum('Individual','Team','Organization') DEFAULT NULL,
  `prefix` varchar(32) DEFAULT NULL,
  `suffix` varchar(32) DEFAULT NULL,
  `namestring` text,
  `mbox_sha1sum` char(40) DEFAULT NULL,
  `yearofbirth` int(11) DEFAULT NULL,
  `yearofbirthmodifier` varchar(12) DEFAULT '',
  `yearofdeath` int(11) DEFAULT NULL,
  `yearofdeathmodifier` varchar(12) DEFAULT '',
  `living` enum('Y','N','?') NOT NULL DEFAULT '?',
  `datelastmodified` datetime DEFAULT NULL,
  `lastmodifiedbyuid` int(11) DEFAULT NULL,
  PRIMARY KEY (`agentid`),
  KEY `firstname` (`firstname`),
  KEY `FK_preferred_recby` (`preferredrecbyid`),
  CONSTRAINT `FK_preferred_recby` FOREIGN KEY (`preferredrecbyid`) REFERENCES `agents` (`agentid`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `agentsfulltext`
--

DROP TABLE IF EXISTS `agentsfulltext`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agentsfulltext` (
  `agentsfulltextid` bigint(20) NOT NULL AUTO_INCREMENT,
  `agentid` int(11) NOT NULL,
  `biography` text,
  `taxonomicgroups` text,
  `collectionsat` text,
  `notes` text,
  `name` text,
  PRIMARY KEY (`agentsfulltextid`),
  FULLTEXT KEY `ft_collectorbio` (`biography`,`taxonomicgroups`,`collectionsat`,`notes`,`name`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `agentteams`
--

DROP TABLE IF EXISTS `agentteams`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `agentteams` (
  `agentteamid` bigint(20) NOT NULL AUTO_INCREMENT,
  `teamagentid` bigint(20) NOT NULL,
  `memberagentid` bigint(20) NOT NULL,
  `ordinal` int(11) DEFAULT NULL,
  PRIMARY KEY (`agentteamid`),
  KEY `teamagentid` (`teamagentid`),
  KEY `memberagentid` (`memberagentid`),
  CONSTRAINT `agentteams_ibfk_1` FOREIGN KEY (`teamagentid`) REFERENCES `agents` (`agentid`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `agentteams_ibfk_2` FOREIGN KEY (`memberagentid`) REFERENCES `agents` (`agentid`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `chotomouskey`
--

DROP TABLE IF EXISTS `chotomouskey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `chotomouskey` (
  `stmtid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `statement` varchar(300) NOT NULL,
  `nodeid` int(10) unsigned NOT NULL,
  `parentid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`stmtid`),
  KEY `FK_chotomouskey_taxa` (`tid`),
  CONSTRAINT `FK_chotomouskey_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `configpage`
--

DROP TABLE IF EXISTS `configpage`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configpage` (
  `configpageid` int(11) NOT NULL AUTO_INCREMENT,
  `pagename` varchar(45) NOT NULL,
  `title` varchar(150) NOT NULL,
  `cssname` varchar(45) DEFAULT NULL,
  `language` varchar(45) NOT NULL DEFAULT 'english',
  `displaymode` int(11) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `modifiedUid` int(10) unsigned NOT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`configpageid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `configpageattributes`
--

DROP TABLE IF EXISTS `configpageattributes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `configpageattributes` (
  `attributeid` int(11) NOT NULL AUTO_INCREMENT,
  `configpageid` int(11) NOT NULL,
  `objid` varchar(45) DEFAULT NULL,
  `objname` varchar(45) NOT NULL,
  `value` varchar(45) DEFAULT NULL,
  `type` varchar(45) DEFAULT NULL COMMENT 'text, submit, div',
  `width` int(11) DEFAULT NULL,
  `top` int(11) DEFAULT NULL,
  `left` int(11) DEFAULT NULL,
  `stylestr` varchar(45) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `modifiedUid` int(10) unsigned NOT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`attributeid`),
  KEY `FK_configpageattributes_id_idx` (`configpageid`),
  CONSTRAINT `FK_configpageattributes_id` FOREIGN KEY (`configpageid`) REFERENCES `configpage` (`configpageid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ctnametypes`
--

DROP TABLE IF EXISTS `ctnametypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ctnametypes` (
  `type` varchar(32) NOT NULL,
  PRIMARY KEY (`type`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `ctrelationshiptypes`
--

DROP TABLE IF EXISTS `ctrelationshiptypes`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `ctrelationshiptypes` (
  `relationship` varchar(50) NOT NULL,
  `inverse` varchar(50) DEFAULT NULL,
  `collective` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`relationship`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmchecklists`
--

DROP TABLE IF EXISTS `fmchecklists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmchecklists` (
  `CLID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `Name` varchar(100) NOT NULL,
  `Title` varchar(150) DEFAULT NULL,
  `Locality` varchar(500) DEFAULT NULL,
  `Publication` varchar(500) DEFAULT NULL,
  `Abstract` text,
  `Authors` varchar(250) DEFAULT NULL,
  `Type` varchar(50) DEFAULT 'static',
  `dynamicsql` varchar(500) DEFAULT NULL,
  `Parent` varchar(50) DEFAULT NULL,
  `parentclid` int(10) unsigned DEFAULT NULL,
  `Notes` varchar(500) DEFAULT NULL,
  `LatCentroid` double(9,6) DEFAULT NULL,
  `LongCentroid` double(9,6) DEFAULT NULL,
  `pointradiusmeters` int(10) unsigned DEFAULT NULL,
  `footprintWKT` text,
  `percenteffort` int(11) DEFAULT NULL,
  `Access` varchar(45) DEFAULT 'private',
  `defaultSettings` varchar(250) DEFAULT NULL,
  `uid` int(10) unsigned DEFAULT NULL,
  `SortSequence` int(10) unsigned NOT NULL DEFAULT '50',
  `expiration` int(10) unsigned DEFAULT NULL,
  `DateLastModified` datetime DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CLID`),
  KEY `FK_checklists_uid` (`uid`),
  KEY `name` (`Name`,`Type`) USING BTREE,
  CONSTRAINT `FK_checklists_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmchklstchildren`
--

DROP TABLE IF EXISTS `fmchklstchildren`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmchklstchildren` (
  `clid` int(10) unsigned NOT NULL,
  `clidchild` int(10) unsigned NOT NULL,
  `modifiedUid` int(10) unsigned NOT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`clid`,`clidchild`),
  KEY `FK_fmchklstchild_clid_idx` (`clid`),
  KEY `FK_fmchklstchild_child_idx` (`clidchild`),
  CONSTRAINT `FK_fmchklstchild_clid` FOREIGN KEY (`clid`) REFERENCES `fmchecklists` (`CLID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_fmchklstchild_child` FOREIGN KEY (`clidchild`) REFERENCES `fmchecklists` (`CLID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmchklstcoordinates`
--

DROP TABLE IF EXISTS `fmchklstcoordinates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmchklstcoordinates` (
  `chklstcoordid` int(11) NOT NULL AUTO_INCREMENT,
  `clid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `decimallatitude` double NOT NULL,
  `decimallongitude` double NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`chklstcoordid`),
  UNIQUE KEY `IndexUnique` (`clid`,`tid`,`decimallatitude`,`decimallongitude`),
  KEY `FKchklsttaxalink` (`clid`,`tid`),
  CONSTRAINT `FKchklsttaxalink` FOREIGN KEY (`clid`, `tid`) REFERENCES `fmchklsttaxalink` (`CLID`, `TID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmchklstprojlink`
--

DROP TABLE IF EXISTS `fmchklstprojlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmchklstprojlink` (
  `pid` int(10) unsigned NOT NULL,
  `clid` int(10) unsigned NOT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pid`,`clid`),
  KEY `FK_chklst` (`clid`),
  CONSTRAINT `FK_chklstprojlink_clid` FOREIGN KEY (`clid`) REFERENCES `fmchecklists` (`CLID`),
  CONSTRAINT `FK_chklstprojlink_proj` FOREIGN KEY (`pid`) REFERENCES `fmprojects` (`pid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmchklsttaxalink`
--

DROP TABLE IF EXISTS `fmchklsttaxalink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmchklsttaxalink` (
  `TID` int(10) unsigned NOT NULL DEFAULT '0',
  `CLID` int(10) unsigned NOT NULL DEFAULT '0',
  `morphospecies` varchar(45) NOT NULL DEFAULT '',
  `familyoverride` varchar(50) DEFAULT NULL,
  `Habitat` varchar(250) DEFAULT NULL,
  `Abundance` varchar(50) DEFAULT NULL,
  `Notes` varchar(2000) DEFAULT NULL,
  `explicitExclude` smallint(6) DEFAULT NULL,
  `source` varchar(250) DEFAULT NULL,
  `Nativity` varchar(50) DEFAULT NULL COMMENT 'native, introducted',
  `Endemic` varchar(45) DEFAULT NULL,
  `invasive` varchar(45) DEFAULT NULL,
  `internalnotes` varchar(250) DEFAULT NULL,
  `dynamicProperties` text,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`TID`,`CLID`,`morphospecies`),
  KEY `FK_chklsttaxalink_cid` (`CLID`),
  CONSTRAINT `FK_chklsttaxalink_cid` FOREIGN KEY (`CLID`) REFERENCES `fmchecklists` (`CLID`),
  CONSTRAINT `FK_chklsttaxalink_tid` FOREIGN KEY (`TID`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmchklsttaxastatus`
--

DROP TABLE IF EXISTS `fmchklsttaxastatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmchklsttaxastatus` (
  `clid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `geographicRange` int(11) NOT NULL DEFAULT '0',
  `populationRank` int(11) NOT NULL DEFAULT '0',
  `abundance` int(11) NOT NULL DEFAULT '0',
  `habitatSpecificity` int(11) NOT NULL DEFAULT '0',
  `intrinsicRarity` int(11) NOT NULL DEFAULT '0',
  `threatImminence` int(11) NOT NULL DEFAULT '0',
  `populationTrends` int(11) NOT NULL DEFAULT '0',
  `nativeStatus` varchar(45) DEFAULT NULL,
  `endemicStatus` int(11) NOT NULL DEFAULT '0',
  `protectedStatus` varchar(45) DEFAULT NULL,
  `localitySecurity` int(11) DEFAULT NULL,
  `localitySecurityReason` varchar(45) DEFAULT NULL,
  `invasiveStatus` varchar(45) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `modifiedUid` int(10) unsigned DEFAULT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`clid`,`tid`),
  KEY `FK_fmchklsttaxastatus_clid_idx` (`clid`,`tid`),
  CONSTRAINT `FK_fmchklsttaxastatus_clidtid` FOREIGN KEY (`clid`, `tid`) REFERENCES `fmchklsttaxalink` (`CLID`, `TID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmcltaxacomments`
--

DROP TABLE IF EXISTS `fmcltaxacomments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmcltaxacomments` (
  `cltaxacommentsid` int(11) NOT NULL AUTO_INCREMENT,
  `clid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `comment` text NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `ispublic` int(11) NOT NULL DEFAULT '1',
  `parentid` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cltaxacommentsid`),
  KEY `FK_clcomment_users` (`uid`),
  KEY `FK_clcomment_cltaxa` (`clid`,`tid`),
  CONSTRAINT `FK_clcomment_users` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_clcomment_cltaxa` FOREIGN KEY (`clid`, `tid`) REFERENCES `fmchklsttaxalink` (`CLID`, `TID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmdynamicchecklists`
--

DROP TABLE IF EXISTS `fmdynamicchecklists`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmdynamicchecklists` (
  `dynclid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(50) DEFAULT NULL,
  `details` varchar(250) DEFAULT NULL,
  `uid` varchar(45) DEFAULT NULL,
  `type` varchar(45) NOT NULL DEFAULT 'DynamicList',
  `notes` varchar(250) DEFAULT NULL,
  `expiration` datetime NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`dynclid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmdyncltaxalink`
--

DROP TABLE IF EXISTS `fmdyncltaxalink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmdyncltaxalink` (
  `dynclid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`dynclid`,`tid`),
  KEY `FK_dyncltaxalink_taxa` (`tid`),
  CONSTRAINT `FK_dyncltaxalink_dynclid` FOREIGN KEY (`dynclid`) REFERENCES `fmdynamicchecklists` (`dynclid`) ON DELETE CASCADE,
  CONSTRAINT `FK_dyncltaxalink_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmprojectcategories`
--

DROP TABLE IF EXISTS `fmprojectcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmprojectcategories` (
  `projcatid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `pid` int(10) unsigned NOT NULL,
  `categoryname` varchar(150) NOT NULL,
  `managers` varchar(100) DEFAULT NULL,
  `description` varchar(250) DEFAULT NULL,
  `parentpid` int(11) DEFAULT NULL,
  `occurrencesearch` int(11) DEFAULT '0',
  `ispublic` int(11) DEFAULT '1',
  `notes` varchar(250) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`projcatid`),
  KEY `FK_fmprojcat_pid_idx` (`pid`),
  CONSTRAINT `FK_fmprojcat_pid` FOREIGN KEY (`pid`) REFERENCES `fmprojects` (`pid`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmprojects`
--

DROP TABLE IF EXISTS `fmprojects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmprojects` (
  `pid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `projname` varchar(45) NOT NULL,
  `displayname` varchar(150) DEFAULT NULL,
  `managers` varchar(150) DEFAULT NULL,
  `briefdescription` varchar(300) DEFAULT NULL,
  `fulldescription` varchar(2000) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `occurrencesearch` int(10) unsigned NOT NULL DEFAULT '0',
  `ispublic` int(10) unsigned NOT NULL DEFAULT '0',
  `parentpid` int(10) unsigned DEFAULT NULL,
  `SortSequence` int(10) unsigned NOT NULL DEFAULT '50',
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pid`),
  KEY `FK_parentpid_proj` (`parentpid`),
  CONSTRAINT `FK_parentpid_proj` FOREIGN KEY (`parentpid`) REFERENCES `fmprojects` (`pid`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `fmvouchers`
--

DROP TABLE IF EXISTS `fmvouchers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `fmvouchers` (
  `TID` int(10) unsigned NOT NULL,
  `CLID` int(10) unsigned NOT NULL,
  `occid` int(10) unsigned NOT NULL,
  `Collector` varchar(100) DEFAULT NULL,
  `editornotes` varchar(50) DEFAULT NULL,
  `Notes` varchar(250) DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`occid`,`CLID`,`TID`),
  KEY `chklst_taxavouchers` (`TID`,`CLID`),
  CONSTRAINT `FK_fmvouchers_occ` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_vouchers_cl` FOREIGN KEY (`TID`, `CLID`) REFERENCES `fmchklsttaxalink` (`TID`, `CLID`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geothescontinent`
--

DROP TABLE IF EXISTS `geothescontinent`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `geothescontinent` (
  `gtcid` int(11) NOT NULL AUTO_INCREMENT,
  `continentterm` varchar(45) NOT NULL,
  `abbreviation` varchar(45) DEFAULT NULL,
  `code` varchar(45) DEFAULT NULL,
  `lookupterm` int(11) NOT NULL DEFAULT '1',
  `acceptedid` int(11) DEFAULT NULL,
  `footprintWKT` text,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gtcid`),
  KEY `FK_geothescontinent_accepted_idx` (`acceptedid`),
  CONSTRAINT `FK_geothescontinent_accepted` FOREIGN KEY (`acceptedid`) REFERENCES `geothescontinent` (`gtcid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geothescountry`
--

DROP TABLE IF EXISTS `geothescountry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `geothescountry` (
  `gtcid` int(11) NOT NULL AUTO_INCREMENT,
  `countryterm` varchar(45) NOT NULL,
  `abbreviation` varchar(45) DEFAULT NULL,
  `iso` varchar(2) DEFAULT NULL,
  `iso3` varchar(3) DEFAULT NULL,
  `numcode` int(11) DEFAULT NULL,
  `lookupterm` int(11) NOT NULL DEFAULT '1',
  `acceptedid` int(11) DEFAULT NULL,
  `continentid` int(11) DEFAULT NULL,
  `footprintWKT` text,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gtcid`),
  KEY `FK_geothescountry__idx` (`continentid`),
  KEY `FK_geothescountry_parent_idx` (`acceptedid`),
  CONSTRAINT `FK_geothescountry_accepted` FOREIGN KEY (`acceptedid`) REFERENCES `geothescountry` (`gtcid`),
  CONSTRAINT `FK_geothescountry_gtcid` FOREIGN KEY (`continentid`) REFERENCES `geothescontinent` (`gtcid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geothescounty`
--

DROP TABLE IF EXISTS `geothescounty`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `geothescounty` (
  `gtcoid` int(11) NOT NULL AUTO_INCREMENT,
  `countyterm` varchar(45) NOT NULL,
  `abbreviation` varchar(45) DEFAULT NULL,
  `code` varchar(45) DEFAULT NULL,
  `lookupterm` int(11) NOT NULL DEFAULT '1',
  `acceptedid` int(11) DEFAULT NULL,
  `stateid` int(11) DEFAULT NULL,
  `footprintWKT` text,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gtcoid`),
  KEY `FK_geothescounty_state_idx` (`stateid`),
  KEY `FK_geothescounty_accepted_idx` (`acceptedid`),
  CONSTRAINT `FK_geothescounty_accepted` FOREIGN KEY (`acceptedid`) REFERENCES `geothescounty` (`gtcoid`),
  CONSTRAINT `FK_geothescounty_state` FOREIGN KEY (`stateid`) REFERENCES `geothesstateprovince` (`gtspid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geothesmunicipality`
--

DROP TABLE IF EXISTS `geothesmunicipality`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `geothesmunicipality` (
  `gtmid` int(11) NOT NULL AUTO_INCREMENT,
  `municipalityterm` varchar(45) NOT NULL,
  `abbreviation` varchar(45) DEFAULT NULL,
  `code` varchar(45) DEFAULT NULL,
  `lookupterm` int(11) NOT NULL DEFAULT '1',
  `acceptedid` int(11) DEFAULT NULL,
  `countyid` int(11) DEFAULT NULL,
  `footprintWKT` text,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gtmid`),
  KEY `FK_geothesmunicipality_county_idx` (`countyid`),
  KEY `FK_geothesmunicipality_accepted_idx` (`acceptedid`),
  CONSTRAINT `FK_geothesmunicipality_accepted` FOREIGN KEY (`acceptedid`) REFERENCES `geothesmunicipality` (`gtmid`),
  CONSTRAINT `FK_geothesmunicipality_county` FOREIGN KEY (`countyid`) REFERENCES `geothescounty` (`gtcoid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `geothesstateprovince`
--

DROP TABLE IF EXISTS `geothesstateprovince`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `geothesstateprovince` (
  `gtspid` int(11) NOT NULL AUTO_INCREMENT,
  `stateterm` varchar(45) NOT NULL,
  `abbreviation` varchar(45) DEFAULT NULL,
  `code` varchar(45) DEFAULT NULL,
  `lookupterm` int(11) NOT NULL DEFAULT '1',
  `acceptedid` int(11) DEFAULT NULL,
  `countryid` int(11) DEFAULT NULL,
  `footprintWKT` text,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gtspid`),
  KEY `FK_geothesstate_country_idx` (`countryid`),
  KEY `FK_geothesstate_accepted_idx` (`acceptedid`),
  CONSTRAINT `FK_geothesstate_accepted` FOREIGN KEY (`acceptedid`) REFERENCES `geothesstateprovince` (`gtspid`),
  CONSTRAINT `FK_geothesstate_country` FOREIGN KEY (`countryid`) REFERENCES `geothescountry` (`gtcid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glossary`
--

DROP TABLE IF EXISTS `glossary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `glossary` (
  `glossid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `term` varchar(150) NOT NULL,
  `definition` varchar(600) DEFAULT NULL,
  `language` varchar(45) NOT NULL DEFAULT 'English',
  `source` varchar(45) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `uid` int(10) unsigned DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`glossid`),
  KEY `Index_term` (`term`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glossaryimages`
--

DROP TABLE IF EXISTS `glossaryimages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `glossaryimages` (
  `glimgid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `glossid` int(10) unsigned NOT NULL,
  `url` varchar(255) NOT NULL,
  `thumbnailurl` varchar(255) DEFAULT NULL,
  `structures` varchar(150) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `uid` int(10) unsigned DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`glimgid`),
  KEY `FK_glossaryimages_gloss` (`glossid`),
  CONSTRAINT `FK_glossaryimages_gloss` FOREIGN KEY (`glossid`) REFERENCES `glossary` (`glossid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glossarytaxalink`
--

DROP TABLE IF EXISTS `glossarytaxalink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `glossarytaxalink` (
  `glossgrpid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`glossgrpid`,`tid`),
  KEY `glossarytaxalink_ibfk_1` (`tid`),
  CONSTRAINT `glossarytaxalink_ibfk_1` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `glossarytaxalink_ibfk_2` FOREIGN KEY (`glossgrpid`) REFERENCES `glossarytermlink` (`glossgrpid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `glossarytermlink`
--

DROP TABLE IF EXISTS `glossarytermlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `glossarytermlink` (
  `gltlinkid` int(10) NOT NULL AUTO_INCREMENT,
  `glossgrpid` int(10) unsigned NOT NULL,
  `glossid` int(10) unsigned NOT NULL,
  `relationshipType` varchar(45) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`gltlinkid`),
  UNIQUE KEY `Unique_termkeys` (`glossgrpid`,`glossid`),
  KEY `glossarytermlink_ibfk_1` (`glossid`),
  CONSTRAINT `glossarytermlink_ibfk_1` FOREIGN KEY (`glossid`) REFERENCES `glossary` (`glossid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `guidimages`
--

DROP TABLE IF EXISTS `guidimages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guidimages` (
  `guid` varchar(45) NOT NULL,
  `imgid` int(10) unsigned DEFAULT NULL,
  `archivestatus` int(3) NOT NULL DEFAULT '0',
  `archiveobj` text,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`guid`),
  UNIQUE KEY `guidimages_imgid_unique` (`imgid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `guidoccurdeterminations`
--

DROP TABLE IF EXISTS `guidoccurdeterminations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guidoccurdeterminations` (
  `guid` varchar(45) NOT NULL,
  `detid` int(10) unsigned DEFAULT NULL,
  `archivestatus` int(3) NOT NULL DEFAULT '0',
  `archiveobj` text,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`guid`),
  UNIQUE KEY `guidoccurdet_detid_unique` (`detid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `guidoccurrences`
--

DROP TABLE IF EXISTS `guidoccurrences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `guidoccurrences` (
  `guid` varchar(45) NOT NULL,
  `occid` int(10) unsigned DEFAULT NULL,
  `archivestatus` int(3) NOT NULL DEFAULT '0',
  `archiveobj` text,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`guid`),
  UNIQUE KEY `guidoccurrences_occid_unique` (`occid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imageannotations`
--

DROP TABLE IF EXISTS `imageannotations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imageannotations` (
  `tid` int(10) unsigned DEFAULT NULL,
  `imgid` int(10) unsigned NOT NULL DEFAULT '0',
  `AnnDate` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `Annotator` varchar(100) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`imgid`,`AnnDate`) USING BTREE,
  KEY `TID` (`tid`) USING BTREE,
  CONSTRAINT `FK_resourceannotations_imgid` FOREIGN KEY (`imgid`) REFERENCES `images` (`imgid`),
  CONSTRAINT `FK_resourceannotations_tid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imagekeywords`
--

DROP TABLE IF EXISTS `imagekeywords`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imagekeywords` (
  `imgkeywordid` int(11) NOT NULL AUTO_INCREMENT,
  `imgid` int(10) unsigned NOT NULL,
  `keyword` varchar(45) NOT NULL,
  `uidassignedby` int(10) unsigned DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`imgkeywordid`),
  KEY `FK_imagekeywords_imgid_idx` (`imgid`),
  KEY `FK_imagekeyword_uid_idx` (`uidassignedby`),
  KEY `INDEX_imagekeyword` (`keyword`),
  CONSTRAINT `FK_imagekeywords_imgid` FOREIGN KEY (`imgid`) REFERENCES `images` (`imgid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_imagekeyword_uid` FOREIGN KEY (`uidassignedby`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imageprojectlink`
--

DROP TABLE IF EXISTS `imageprojectlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imageprojectlink` (
  `imgid` int(10) unsigned NOT NULL,
  `imgprojid` int(11) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`imgid`,`imgprojid`),
  KEY `FK_imageprojlink_imgprojid_idx` (`imgprojid`),
  CONSTRAINT `FK_imageprojectlink_imgid` FOREIGN KEY (`imgid`) REFERENCES `images` (`imgid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_imageprojlink_imgprojid` FOREIGN KEY (`imgprojid`) REFERENCES `imageprojects` (`imgprojid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imageprojects`
--

DROP TABLE IF EXISTS `imageprojects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imageprojects` (
  `imgprojid` int(11) NOT NULL AUTO_INCREMENT,
  `projectname` varchar(75) NOT NULL,
  `managers` varchar(150) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `ispublic` int(11) NOT NULL DEFAULT '1',
  `notes` varchar(250) DEFAULT NULL,
  `uidcreated` int(11) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT '50',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`imgprojid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `images`
--

DROP TABLE IF EXISTS `images`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `images` (
  `imgid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `thumbnailurl` varchar(255) DEFAULT NULL,
  `originalurl` varchar(255) DEFAULT NULL,
  `archiveurl` varchar(255) DEFAULT NULL,
  `photographer` varchar(100) DEFAULT NULL,
  `photographeruid` int(10) unsigned DEFAULT NULL,
  `imagetype` varchar(50) DEFAULT NULL,
  `format` varchar(45) DEFAULT NULL,
  `caption` varchar(100) DEFAULT NULL,
  `owner` varchar(250) DEFAULT NULL,
  `sourceurl` varchar(255) DEFAULT NULL,
  `copyright` varchar(255) DEFAULT NULL,
  `rights` varchar(255) DEFAULT NULL,
  `accessrights` varchar(255) DEFAULT NULL,
  `locality` varchar(250) DEFAULT NULL,
  `occid` int(10) unsigned DEFAULT NULL,
  `notes` varchar(350) DEFAULT NULL,
  `anatomy` varchar(100) DEFAULT NULL,
  `username` varchar(45) DEFAULT NULL,
  `sourceIdentifier` varchar(45) DEFAULT NULL,
  `sortsequence` int(10) unsigned NOT NULL DEFAULT '50',
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`imgid`) USING BTREE,
  KEY `Index_tid` (`tid`),
  KEY `FK_images_occ` (`occid`),
  KEY `FK_photographeruid` (`photographeruid`),
  CONSTRAINT `FK_images_occ` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`),
  CONSTRAINT `FK_photographeruid` FOREIGN KEY (`photographeruid`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_taxaimagestid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imagetag`
--

DROP TABLE IF EXISTS `imagetag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imagetag` (
  `imagetagid` bigint(20) NOT NULL AUTO_INCREMENT,
  `imgid` int(10) unsigned NOT NULL,
  `keyvalue` varchar(30) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`imagetagid`),
  UNIQUE KEY `imgid` (`imgid`,`keyvalue`),
  KEY `keyvalue` (`keyvalue`),
  KEY `FK_imagetag_imgid_idx` (`imgid`),
  CONSTRAINT `FK_imagetag_imgid` FOREIGN KEY (`imgid`) REFERENCES `images` (`imgid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_imagetag_tagkey` FOREIGN KEY (`keyvalue`) REFERENCES `imagetagkey` (`tagkey`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `imagetagkey`
--

DROP TABLE IF EXISTS `imagetagkey`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `imagetagkey` (
  `tagkey` varchar(30) NOT NULL,
  `shortlabel` varchar(30) NOT NULL,
  `description_en` varchar(255) NOT NULL,
  `sortorder` int(11) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tagkey`),
  KEY `sortorder` (`sortorder`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `institutions`
--

DROP TABLE IF EXISTS `institutions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `institutions` (
  `iid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `InstitutionCode` varchar(45) NOT NULL,
  `InstitutionName` varchar(150) NOT NULL,
  `InstitutionName2` varchar(150) DEFAULT NULL,
  `Address1` varchar(150) DEFAULT NULL,
  `Address2` varchar(150) DEFAULT NULL,
  `City` varchar(45) DEFAULT NULL,
  `StateProvince` varchar(45) DEFAULT NULL,
  `PostalCode` varchar(45) DEFAULT NULL,
  `Country` varchar(45) DEFAULT NULL,
  `Phone` varchar(45) DEFAULT NULL,
  `Contact` varchar(65) DEFAULT NULL,
  `Email` varchar(45) DEFAULT NULL,
  `Url` varchar(250) DEFAULT NULL,
  `Notes` varchar(250) DEFAULT NULL,
  `modifieduid` int(10) unsigned DEFAULT NULL,
  `modifiedTimeStamp` datetime DEFAULT NULL,
  `IntialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`iid`),
  KEY `FK_inst_uid_idx` (`modifieduid`),
  CONSTRAINT `FK_inst_uid` FOREIGN KEY (`modifieduid`) REFERENCES `users` (`uid`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmcharacterlang`
--

DROP TABLE IF EXISTS `kmcharacterlang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmcharacterlang` (
  `cid` int(10) unsigned NOT NULL,
  `charname` varchar(150) NOT NULL,
  `language` varchar(45) NOT NULL,
  `langid` int(11) NOT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `helpurl` varchar(500) DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cid`,`langid`) USING BTREE,
  CONSTRAINT `FK_characterlang_1` FOREIGN KEY (`cid`) REFERENCES `kmcharacters` (`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmcharacters`
--

DROP TABLE IF EXISTS `kmcharacters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmcharacters` (
  `cid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `charname` varchar(150) NOT NULL,
  `chartype` varchar(2) NOT NULL DEFAULT 'UM',
  `defaultlang` varchar(45) NOT NULL DEFAULT 'English',
  `difficultyrank` smallint(5) unsigned NOT NULL DEFAULT '1',
  `hid` int(10) unsigned DEFAULT NULL,
  `units` varchar(45) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `helpurl` varchar(500) DEFAULT NULL,
  `enteredby` varchar(45) DEFAULT NULL,
  `sortsequence` int(10) unsigned DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cid`),
  KEY `Index_charname` (`charname`),
  KEY `Index_sort` (`sortsequence`),
  KEY `FK_charheading_idx` (`hid`),
  CONSTRAINT `FK_charheading` FOREIGN KEY (`hid`) REFERENCES `kmcharheading` (`hid`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmchardependance`
--

DROP TABLE IF EXISTS `kmchardependance`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmchardependance` (
  `CID` int(10) unsigned NOT NULL,
  `CIDDependance` int(10) unsigned NOT NULL,
  `CSDependance` varchar(16) NOT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CSDependance`,`CIDDependance`,`CID`) USING BTREE,
  KEY `FK_chardependance_cid_idx` (`CID`),
  KEY `FK_chardependance_cs_idx` (`CIDDependance`,`CSDependance`),
  CONSTRAINT `FK_chardependance_cid` FOREIGN KEY (`CID`) REFERENCES `kmcharacters` (`cid`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `FK_chardependance_cs` FOREIGN KEY (`CIDDependance`, `CSDependance`) REFERENCES `kmcs` (`cid`, `cs`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmcharheading`
--

DROP TABLE IF EXISTS `kmcharheading`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmcharheading` (
  `hid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `headingname` varchar(255) NOT NULL,
  `language` varchar(45) NOT NULL DEFAULT 'English',
  `langid` int(11) NOT NULL,
  `notes` longtext,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`hid`,`langid`) USING BTREE,
  UNIQUE KEY `unique_kmcharheading` (`headingname`,`langid`),
  KEY `HeadingName` (`headingname`) USING BTREE,
  KEY `FK_kmcharheading_lang_idx` (`langid`),
  CONSTRAINT `FK_kmcharheading_lang` FOREIGN KEY (`langid`) REFERENCES `adminlanguages` (`langid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmchartaxalink`
--

DROP TABLE IF EXISTS `kmchartaxalink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmchartaxalink` (
  `CID` int(10) unsigned NOT NULL DEFAULT '0',
  `TID` int(10) unsigned NOT NULL DEFAULT '0',
  `Status` varchar(50) DEFAULT NULL,
  `Notes` varchar(255) DEFAULT NULL,
  `Relation` varchar(45) NOT NULL DEFAULT 'include',
  `EditabilityInherited` bit(1) DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CID`,`TID`),
  KEY `FK_CharTaxaLink-TID` (`TID`),
  CONSTRAINT `FK_chartaxalink_cid` FOREIGN KEY (`CID`) REFERENCES `kmcharacters` (`cid`),
  CONSTRAINT `FK_chartaxalink_tid` FOREIGN KEY (`TID`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmcs`
--

DROP TABLE IF EXISTS `kmcs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmcs` (
  `cid` int(10) unsigned NOT NULL DEFAULT '0',
  `cs` varchar(16) NOT NULL,
  `CharStateName` varchar(255) DEFAULT NULL,
  `Implicit` tinyint(1) NOT NULL DEFAULT '0',
  `Notes` longtext,
  `Description` varchar(255) DEFAULT NULL,
  `IllustrationUrl` varchar(250) DEFAULT NULL,
  `StateID` int(10) unsigned DEFAULT NULL,
  `SortSequence` int(10) unsigned DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `EnteredBy` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`cs`,`cid`),
  KEY `FK_cs_chars` (`cid`),
  CONSTRAINT `FK_cs_chars` FOREIGN KEY (`cid`) REFERENCES `kmcharacters` (`cid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmcsimages`
--

DROP TABLE IF EXISTS `kmcsimages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmcsimages` (
  `csimgid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `cid` int(10) unsigned NOT NULL,
  `cs` varchar(16) NOT NULL,
  `url` varchar(255) NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `sortsequence` varchar(45) NOT NULL DEFAULT '50',
  `username` varchar(45) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`csimgid`),
  KEY `FK_kscsimages_kscs_idx` (`cid`,`cs`),
  CONSTRAINT `FK_kscsimages_kscs` FOREIGN KEY (`cid`, `cs`) REFERENCES `kmcs` (`cid`, `cs`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmcslang`
--

DROP TABLE IF EXISTS `kmcslang`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmcslang` (
  `cid` int(10) unsigned NOT NULL,
  `cs` varchar(16) NOT NULL,
  `charstatename` varchar(150) NOT NULL,
  `language` varchar(45) NOT NULL,
  `langid` int(11) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `intialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`cid`,`cs`,`langid`),
  CONSTRAINT `FK_cslang_1` FOREIGN KEY (`cid`, `cs`) REFERENCES `kmcs` (`cid`, `cs`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmdescr`
--

DROP TABLE IF EXISTS `kmdescr`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmdescr` (
  `TID` int(10) unsigned NOT NULL DEFAULT '0',
  `CID` int(10) unsigned NOT NULL DEFAULT '0',
  `Modifier` varchar(255) DEFAULT NULL,
  `CS` varchar(16) NOT NULL,
  `X` double(15,5) DEFAULT NULL,
  `TXT` longtext,
  `PseudoTrait` int(5) unsigned DEFAULT '0',
  `Frequency` int(5) unsigned NOT NULL DEFAULT '5' COMMENT 'Frequency of occurrence; 1 = rare... 5 = common',
  `Inherited` varchar(50) DEFAULT NULL,
  `Source` varchar(100) DEFAULT NULL,
  `Seq` int(10) DEFAULT NULL,
  `Notes` longtext,
  `DateEntered` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`TID`,`CID`,`CS`),
  KEY `CSDescr` (`CID`,`CS`),
  CONSTRAINT `FK_descr_cs` FOREIGN KEY (`CID`, `CS`) REFERENCES `kmcs` (`cid`, `cs`),
  CONSTRAINT `FK_descr_tid` FOREIGN KEY (`TID`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `kmdescrdeletions`
--

DROP TABLE IF EXISTS `kmdescrdeletions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `kmdescrdeletions` (
  `TID` int(10) unsigned NOT NULL,
  `CID` int(10) unsigned NOT NULL,
  `CS` varchar(16) NOT NULL,
  `Modifier` varchar(255) DEFAULT NULL,
  `X` double(15,5) DEFAULT NULL,
  `TXT` longtext,
  `Inherited` varchar(50) DEFAULT NULL,
  `Source` varchar(100) DEFAULT NULL,
  `Seq` int(10) unsigned DEFAULT NULL,
  `Notes` longtext,
  `InitialTimeStamp` datetime DEFAULT NULL,
  `DeletedBy` varchar(100) NOT NULL,
  `DeletedTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `PK` int(10) unsigned NOT NULL AUTO_INCREMENT,
  PRIMARY KEY (`PK`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lkupcountry`
--

DROP TABLE IF EXISTS `lkupcountry`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lkupcountry` (
  `countryId` int(11) NOT NULL AUTO_INCREMENT,
  `countryName` varchar(100) NOT NULL,
  `iso` varchar(2) DEFAULT NULL,
  `iso3` varchar(3) DEFAULT NULL,
  `numcode` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`countryId`),
  UNIQUE KEY `country_unique` (`countryName`),
  KEY `Index_lkupcountry_iso` (`iso`),
  KEY `Index_lkupcountry_iso3` (`iso3`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lkupcounty`
--

DROP TABLE IF EXISTS `lkupcounty`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lkupcounty` (
  `countyId` int(11) NOT NULL AUTO_INCREMENT,
  `stateId` int(11) NOT NULL,
  `countyName` varchar(100) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`countyId`),
  UNIQUE KEY `unique_county` (`stateId`,`countyName`),
  KEY `fk_stateprovince` (`stateId`),
  KEY `index_countyname` (`countyName`),
  CONSTRAINT `fk_stateprovince` FOREIGN KEY (`stateId`) REFERENCES `lkupstateprovince` (`stateId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `lkupstateprovince`
--

DROP TABLE IF EXISTS `lkupstateprovince`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `lkupstateprovince` (
  `stateId` int(11) NOT NULL AUTO_INCREMENT,
  `countryId` int(11) NOT NULL,
  `stateName` varchar(100) NOT NULL,
  `abbrev` varchar(2) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`stateId`),
  UNIQUE KEY `state_index` (`stateName`,`countryId`),
  KEY `fk_country` (`countryId`),
  KEY `index_statename` (`stateName`),
  KEY `Index_lkupstate_abbr` (`abbrev`),
  CONSTRAINT `fk_country` FOREIGN KEY (`countryId`) REFERENCES `lkupcountry` (`countryId`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `media`
--

DROP TABLE IF EXISTS `media`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `media` (
  `mediaid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned DEFAULT NULL,
  `occid` int(10) unsigned DEFAULT NULL,
  `url` varchar(250) NOT NULL,
  `caption` varchar(250) DEFAULT NULL,
  `authoruid` int(10) unsigned DEFAULT NULL,
  `author` varchar(45) DEFAULT NULL,
  `mediatype` varchar(45) DEFAULT NULL,
  `owner` varchar(250) DEFAULT NULL,
  `sourceurl` varchar(250) DEFAULT NULL,
  `locality` varchar(250) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`mediaid`),
  KEY `FK_media_taxa_idx` (`tid`),
  KEY `FK_media_occid_idx` (`occid`),
  KEY `FK_media_uid_idx` (`authoruid`),
  CONSTRAINT `FK_media_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON UPDATE CASCADE,
  CONSTRAINT `FK_media_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_media_uid` FOREIGN KEY (`authoruid`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omcollcategories`
--

DROP TABLE IF EXISTS `omcollcategories`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omcollcategories` (
  `ccpk` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `category` varchar(75) NOT NULL,
  `icon` varchar(250) DEFAULT NULL,
  `acronym` varchar(45) DEFAULT NULL,
  `url` varchar(250) DEFAULT NULL,
  `inclusive` int(2) DEFAULT '1',
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ccpk`) USING BTREE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omcollcatlink`
--

DROP TABLE IF EXISTS `omcollcatlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omcollcatlink` (
  `ccpk` int(10) unsigned NOT NULL,
  `collid` int(10) unsigned NOT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ccpk`,`collid`),
  KEY `FK_collcatlink_coll` (`collid`),
  CONSTRAINT `FK_collcatlink_cat` FOREIGN KEY (`ccpk`) REFERENCES `omcollcategories` (`ccpk`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_collcatlink_coll` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omcollections`
--

DROP TABLE IF EXISTS `omcollections`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omcollections` (
  `CollID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `InstitutionCode` varchar(45) NOT NULL,
  `CollectionCode` varchar(45) DEFAULT NULL,
  `CollectionName` varchar(150) NOT NULL,
  `collectionId` varchar(100) DEFAULT NULL,
  `datasetName` varchar(100) DEFAULT NULL,
  `iid` int(10) unsigned DEFAULT NULL,
  `fulldescription` varchar(2000) DEFAULT NULL,
  `Homepage` varchar(250) DEFAULT NULL,
  `IndividualUrl` varchar(500) DEFAULT NULL,
  `Contact` varchar(45) DEFAULT NULL,
  `email` varchar(45) DEFAULT NULL,
  `latitudedecimal` decimal(8,6) DEFAULT NULL,
  `longitudedecimal` decimal(9,6) DEFAULT NULL,
  `icon` varchar(250) DEFAULT NULL,
  `CollType` varchar(45) NOT NULL DEFAULT 'Preserved Specimens' COMMENT 'Preserved Specimens, General Observations, Observations',
  `ManagementType` varchar(45) DEFAULT 'Snapshot' COMMENT 'Snapshot, Live Data',
  `PublicEdits` int(1) unsigned NOT NULL DEFAULT '1',
  `collectionguid` varchar(45) DEFAULT NULL,
  `securitykey` varchar(45) DEFAULT NULL,
  `guidtarget` varchar(45) DEFAULT NULL,
  `rightsHolder` varchar(250) DEFAULT NULL,
  `rights` varchar(250) DEFAULT NULL,
  `usageTerm` varchar(250) DEFAULT NULL,
  `publishToGbif` int(11) DEFAULT NULL,
  `bibliographicCitation` varchar(1000) DEFAULT NULL,
  `accessrights` varchar(1000) DEFAULT NULL,
  `SortSeq` int(10) unsigned DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`CollID`) USING BTREE,
  UNIQUE KEY `Index_inst` (`InstitutionCode`,`CollectionCode`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omcollectionstats`
--

DROP TABLE IF EXISTS `omcollectionstats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omcollectionstats` (
  `collid` int(10) unsigned NOT NULL,
  `recordcnt` int(10) unsigned NOT NULL DEFAULT '0',
  `georefcnt` int(10) unsigned DEFAULT NULL,
  `familycnt` int(10) unsigned DEFAULT NULL,
  `genuscnt` int(10) unsigned DEFAULT NULL,
  `speciescnt` int(10) unsigned DEFAULT NULL,
  `uploaddate` datetime DEFAULT NULL,
  `datelastmodified` datetime DEFAULT NULL,
  `uploadedby` varchar(45) DEFAULT NULL,
  `dynamicProperties` varchar(500) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`collid`),
  CONSTRAINT `FK_collectionstats_coll` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omcollectors`
--

DROP TABLE IF EXISTS `omcollectors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omcollectors` (
  `recordedById` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `familyname` varchar(45) NOT NULL,
  `firstname` varchar(45) DEFAULT NULL,
  `middlename` varchar(45) DEFAULT NULL,
  `startyearactive` int(11) DEFAULT NULL,
  `endyearactive` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `rating` int(11) DEFAULT '10',
  `guid` varchar(45) DEFAULT NULL,
  `preferredrecbyid` int(10) unsigned DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`recordedById`),
  KEY `fullname` (`familyname`,`firstname`),
  KEY `FK_preferred_recby_idx` (`preferredrecbyid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omcollpublications`
--

DROP TABLE IF EXISTS `omcollpublications`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omcollpublications` (
  `pubid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `collid` int(10) unsigned NOT NULL,
  `targeturl` varchar(250) NOT NULL,
  `securityguid` varchar(45) NOT NULL,
  `criteriajson` varchar(250) DEFAULT NULL,
  `includedeterminations` int(11) DEFAULT '1',
  `includeimages` int(11) DEFAULT '1',
  `autoupdate` int(11) DEFAULT '0',
  `lastdateupdate` datetime DEFAULT NULL,
  `updateinterval` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`pubid`),
  KEY `FK_adminpub_collid_idx` (`collid`),
  CONSTRAINT `FK_adminpub_collid` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omcollsecondary`
--

DROP TABLE IF EXISTS `omcollsecondary`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omcollsecondary` (
  `ocsid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `collid` int(10) unsigned NOT NULL,
  `InstitutionCode` varchar(45) NOT NULL,
  `CollectionCode` varchar(45) DEFAULT NULL,
  `CollectionName` varchar(150) NOT NULL,
  `BriefDescription` varchar(300) DEFAULT NULL,
  `FullDescription` varchar(1000) DEFAULT NULL,
  `Homepage` varchar(250) DEFAULT NULL,
  `IndividualUrl` varchar(500) DEFAULT NULL,
  `Contact` varchar(45) DEFAULT NULL,
  `Email` varchar(45) DEFAULT NULL,
  `LatitudeDecimal` double DEFAULT NULL,
  `LongitudeDecimal` double DEFAULT NULL,
  `icon` varchar(250) DEFAULT NULL,
  `CollType` varchar(45) DEFAULT NULL,
  `SortSeq` int(10) unsigned DEFAULT NULL,
  `InitialTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ocsid`),
  KEY `FK_omcollsecondary_coll` (`collid`),
  CONSTRAINT `FK_omcollsecondary_coll` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omcrowdsourcecentral`
--

DROP TABLE IF EXISTS `omcrowdsourcecentral`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omcrowdsourcecentral` (
  `omcsid` int(11) NOT NULL AUTO_INCREMENT,
  `collid` int(10) unsigned NOT NULL,
  `instructions` text,
  `trainingurl` varchar(500) DEFAULT NULL,
  `editorlevel` int(11) NOT NULL DEFAULT '0' COMMENT '0=public, 1=public limited, 2=private',
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`omcsid`),
  UNIQUE KEY `Index_omcrowdsourcecentral_collid` (`collid`),
  KEY `FK_omcrowdsourcecentral_collid` (`collid`),
  CONSTRAINT `FK_omcrowdsourcecentral_collid` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omcrowdsourcequeue`
--

DROP TABLE IF EXISTS `omcrowdsourcequeue`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omcrowdsourcequeue` (
  `idomcrowdsourcequeue` int(11) NOT NULL AUTO_INCREMENT,
  `omcsid` int(11) NOT NULL,
  `occid` int(10) unsigned NOT NULL,
  `reviewstatus` int(11) NOT NULL DEFAULT '0' COMMENT '0=open,5=pending review, 10=closed',
  `uidprocessor` int(10) unsigned DEFAULT NULL,
  `points` int(11) DEFAULT NULL COMMENT '0=fail, 1=minor edits, 2=no edits <default>, 3=excelled',
  `isvolunteer` int(2) NOT NULL DEFAULT '1',
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idomcrowdsourcequeue`),
  UNIQUE KEY `Index_omcrowdsource_occid` (`occid`),
  KEY `FK_omcrowdsourcequeue_occid` (`occid`),
  KEY `FK_omcrowdsourcequeue_uid` (`uidprocessor`),
  CONSTRAINT `FK_omcrowdsourcequeue_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_omcrowdsourcequeue_uid` FOREIGN KEY (`uidprocessor`) REFERENCES `users` (`uid`) ON DELETE NO ACTION ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omexsiccatinumbers`
--

DROP TABLE IF EXISTS `omexsiccatinumbers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omexsiccatinumbers` (
  `omenid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `exsnumber` varchar(45) NOT NULL,
  `ometid` int(10) unsigned NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`omenid`),
  UNIQUE KEY `Index_omexsiccatinumbers_unique` (`exsnumber`,`ometid`),
  KEY `FK_exsiccatiTitleNumber` (`ometid`),
  CONSTRAINT `FK_exsiccatiTitleNumber` FOREIGN KEY (`ometid`) REFERENCES `omexsiccatititles` (`ometid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omexsiccatiocclink`
--

DROP TABLE IF EXISTS `omexsiccatiocclink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omexsiccatiocclink` (
  `omenid` int(10) unsigned NOT NULL,
  `occid` int(10) unsigned NOT NULL,
  `ranking` int(11) NOT NULL DEFAULT '50',
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`omenid`,`occid`),
  UNIQUE KEY `UniqueOmexsiccatiOccLink` (`occid`),
  KEY `FKExsiccatiNumOccLink1` (`omenid`),
  KEY `FKExsiccatiNumOccLink2` (`occid`),
  CONSTRAINT `FKExsiccatiNumOccLink1` FOREIGN KEY (`omenid`) REFERENCES `omexsiccatinumbers` (`omenid`) ON DELETE CASCADE,
  CONSTRAINT `FKExsiccatiNumOccLink2` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omexsiccatititles`
--

DROP TABLE IF EXISTS `omexsiccatititles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omexsiccatititles` (
  `ometid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `title` varchar(150) NOT NULL,
  `abbreviation` varchar(100) DEFAULT NULL,
  `editor` varchar(150) DEFAULT NULL,
  `exsrange` varchar(45) DEFAULT NULL,
  `startdate` varchar(45) DEFAULT NULL,
  `enddate` varchar(45) DEFAULT NULL,
  `source` varchar(250) DEFAULT NULL,
  `notes` varchar(2000) DEFAULT NULL,
  `lasteditedby` varchar(45) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ometid`),
  KEY `index_exsiccatiTitle` (`title`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurassococcurrences`
--

DROP TABLE IF EXISTS `omoccurassococcurrences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurassococcurrences` (
  `aoid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned NOT NULL,
  `occidassociate` int(10) unsigned DEFAULT NULL,
  `relationship` varchar(150) NOT NULL,
  `identifier` varchar(250) DEFAULT NULL COMMENT 'e.g. GUID',
  `resourceurl` varchar(250) DEFAULT NULL,
  `sciname` varchar(250) DEFAULT NULL,
  `tid` int(11) DEFAULT NULL,
  `locationOnHost` varchar(250) DEFAULT NULL,
  `condition` varchar(250) DEFAULT NULL,
  `dateEmerged` datetime DEFAULT NULL,
  `dynamicProperties` text,
  `notes` varchar(250) DEFAULT NULL,
  `createdby` varchar(45) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`aoid`),
  KEY `omossococcur_occid_idx` (`occid`),
  KEY `omossococcur_occidassoc_idx` (`occidassociate`),
  CONSTRAINT `omossococcur_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `omossococcur_occidassoc` FOREIGN KEY (`occidassociate`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurassoctaxa`
--

DROP TABLE IF EXISTS `omoccurassoctaxa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurassoctaxa` (
  `assoctaxaid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned DEFAULT NULL,
  `verbatimstr` varchar(250) DEFAULT NULL,
  `relationship` varchar(45) DEFAULT NULL,
  `verificationscore` int(11) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`assoctaxaid`),
  KEY `FK_assoctaxa_occid_idx` (`occid`),
  KEY `FK_aooctaxa_tid_idx` (`tid`),
  KEY `INDEX_verbatim_str` (`verbatimstr`),
  CONSTRAINT `FK_assoctaxa_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `FK_aooctaxa_tid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE NO ACTION ON UPDATE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurcomments`
--

DROP TABLE IF EXISTS `omoccurcomments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurcomments` (
  `comid` int(11) NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned NOT NULL,
  `comment` text NOT NULL,
  `uid` int(10) unsigned NOT NULL,
  `reviewstatus` int(10) unsigned NOT NULL DEFAULT '0',
  `parentcomid` int(10) unsigned DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`comid`),
  KEY `fk_omoccurcomments_occid` (`occid`),
  KEY `fk_omoccurcomments_uid` (`uid`),
  CONSTRAINT `fk_omoccurcomments_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_omoccurcomments_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurdatasetlink`
--

DROP TABLE IF EXISTS `omoccurdatasetlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurdatasetlink` (
  `occid` int(10) unsigned NOT NULL,
  `datasetid` int(10) unsigned NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`occid`,`datasetid`),
  KEY `FK_omoccurdatasetlink_datasetid` (`datasetid`),
  KEY `FK_omoccurdatasetlink_occid` (`occid`),
  CONSTRAINT `FK_omoccurdatasetlink_datasetid` FOREIGN KEY (`datasetid`) REFERENCES `omoccurdatasets` (`datasetid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccurdatasetlink_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurdatasets`
--

DROP TABLE IF EXISTS `omoccurdatasets`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurdatasets` (
  `datasetid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `uid` int(11) unsigned DEFAULT NULL,
  `collid` int(10) unsigned DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`datasetid`),
  KEY `FK_omoccurdatasets_uid_idx` (`uid`),
  KEY `FK_omcollections_collid_idx` (`collid`),
  CONSTRAINT `FK_omoccurdatasets_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_omcollections_collid` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurdeterminations`
--

DROP TABLE IF EXISTS `omoccurdeterminations`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurdeterminations` (
  `detid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned NOT NULL,
  `identifiedBy` varchar(60) NOT NULL,
  `idbyid` int(10) unsigned DEFAULT NULL,
  `dateIdentified` varchar(45) NOT NULL,
  `dateIdentifiedInterpreted` date DEFAULT NULL,
  `sciname` varchar(100) NOT NULL,
  `tidinterpreted` int(10) unsigned DEFAULT NULL,
  `scientificNameAuthorship` varchar(100) DEFAULT NULL,
  `identificationQualifier` varchar(45) DEFAULT NULL,
  `iscurrent` int(2) DEFAULT '0',
  `printqueue` int(2) DEFAULT '0',
  `appliedStatus` int(2) DEFAULT '1',
  `detType` varchar(45) DEFAULT NULL,
  `identificationReferences` varchar(255) DEFAULT NULL,
  `identificationRemarks` varchar(255) DEFAULT NULL,
  `sourceIdentifier` varchar(45) DEFAULT NULL,
  `sortsequence` int(10) unsigned DEFAULT '10',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`detid`),
  UNIQUE KEY `Index_unique` (`occid`,`dateIdentified`,`identifiedBy`,`sciname`),
  KEY `FK_omoccurdets_tid` (`tidinterpreted`),
  KEY `FK_omoccurdets_idby_idx` (`idbyid`),
  KEY `Index_dateIdentInterpreted` (`dateIdentifiedInterpreted`),
  CONSTRAINT `FK_omoccurdets_idby` FOREIGN KEY (`idbyid`) REFERENCES `omcollectors` (`recordedById`) ON DELETE SET NULL ON UPDATE SET NULL,
  CONSTRAINT `FK_omoccurdets_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccurdets_tid` FOREIGN KEY (`tidinterpreted`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurduplicatelink`
--

DROP TABLE IF EXISTS `omoccurduplicatelink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurduplicatelink` (
  `occid` int(10) unsigned NOT NULL,
  `duplicateid` int(11) NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `modifiedUid` int(10) unsigned DEFAULT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`occid`,`duplicateid`),
  KEY `FK_omoccurdupelink_occid_idx` (`occid`),
  KEY `FK_omoccurdupelink_dupeid_idx` (`duplicateid`),
  CONSTRAINT `FK_omoccurdupelink_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccurdupelink_dupeid` FOREIGN KEY (`duplicateid`) REFERENCES `omoccurduplicates` (`duplicateid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurduplicates`
--

DROP TABLE IF EXISTS `omoccurduplicates`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurduplicates` (
  `duplicateid` int(11) NOT NULL AUTO_INCREMENT,
  `title` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `dupeType` varchar(45) NOT NULL DEFAULT 'Exact Duplicate',
  `initialTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`duplicateid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccureditlocks`
--

DROP TABLE IF EXISTS `omoccureditlocks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccureditlocks` (
  `occid` int(10) unsigned NOT NULL,
  `uid` int(11) NOT NULL,
  `ts` int(11) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`occid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccuredits`
--

DROP TABLE IF EXISTS `omoccuredits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccuredits` (
  `ocedid` int(11) NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned NOT NULL,
  `FieldName` varchar(45) NOT NULL,
  `FieldValueNew` text NOT NULL,
  `FieldValueOld` text NOT NULL,
  `ReviewStatus` int(1) NOT NULL DEFAULT '1' COMMENT '1=Open;2=Pending;3=Closed',
  `AppliedStatus` int(1) NOT NULL DEFAULT '0' COMMENT '0=Not Applied;1=Applied',
  `uid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ocedid`),
  KEY `fk_omoccuredits_uid` (`uid`),
  KEY `fk_omoccuredits_occid` (`occid`),
  CONSTRAINT `fk_omoccuredits_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_omoccuredits_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurexchange`
--

DROP TABLE IF EXISTS `omoccurexchange`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurexchange` (
  `exchangeid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `identifier` varchar(30) DEFAULT NULL,
  `collid` int(10) unsigned DEFAULT NULL,
  `iid` int(10) unsigned DEFAULT NULL,
  `transactionType` varchar(10) DEFAULT NULL,
  `in_out` varchar(3) DEFAULT NULL,
  `dateSent` date DEFAULT NULL,
  `dateReceived` date DEFAULT NULL,
  `totalBoxes` int(5) DEFAULT NULL,
  `shippingMethod` varchar(50) DEFAULT NULL,
  `totalExMounted` int(5) DEFAULT NULL,
  `totalExUnmounted` int(5) DEFAULT NULL,
  `totalGift` int(5) DEFAULT NULL,
  `totalGiftDet` int(5) DEFAULT NULL,
  `adjustment` int(5) DEFAULT NULL,
  `invoiceBalance` int(6) DEFAULT NULL,
  `invoiceMessage` varchar(500) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `createdBy` varchar(20) DEFAULT NULL,
  `initialTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`exchangeid`),
  KEY `FK_occexch_coll` (`collid`),
  CONSTRAINT `FK_occexch_coll` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurgenetic`
--

DROP TABLE IF EXISTS `omoccurgenetic`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurgenetic` (
  `idoccurgenetic` int(11) NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned NOT NULL,
  `identifier` varchar(150) DEFAULT NULL,
  `resourcename` varchar(150) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `locus` varchar(500) DEFAULT NULL,
  `resourceurl` varchar(500) DEFAULT NULL,
  `notes` varchar(45) DEFAULT NULL,
  `initialtimestamp` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`idoccurgenetic`),
  KEY `FK_omoccurgenetic` (`occid`),
  KEY `INDEX_omoccurgenetic_name` (`resourcename`),
  CONSTRAINT `FK_omoccurgenetic` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurgeoindex`
--

DROP TABLE IF EXISTS `omoccurgeoindex`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurgeoindex` (
  `tid` int(10) unsigned NOT NULL,
  `decimallatitude` double NOT NULL,
  `decimallongitude` double NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tid`,`decimallatitude`,`decimallongitude`),
  CONSTRAINT `FK_specgeoindex_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccuridentifiers`
--

DROP TABLE IF EXISTS `omoccuridentifiers`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccuridentifiers` (
  `idomoccuridentifiers` int(11) NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned NOT NULL,
  `identifiervalue` varchar(45) NOT NULL,
  `identifiername` varchar(45) DEFAULT NULL COMMENT 'barcode, accession number, old catalog number, NPS, etc',
  `notes` varchar(250) DEFAULT NULL,
  `modifiedUid` int(10) unsigned NOT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idomoccuridentifiers`),
  KEY `FK_omoccuridentifiers_occid_idx` (`occid`),
  KEY `Index_value` (`identifiervalue`),
  CONSTRAINT `FK_omoccuridentifiers_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurloans`
--

DROP TABLE IF EXISTS `omoccurloans`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurloans` (
  `loanid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `loanIdentifierOwn` varchar(30) DEFAULT NULL,
  `loanIdentifierBorr` varchar(30) DEFAULT NULL,
  `collidOwn` int(10) unsigned DEFAULT NULL,
  `collidBorr` int(10) unsigned DEFAULT NULL,
  `iidOwner` int(10) unsigned DEFAULT NULL,
  `iidBorrower` int(10) unsigned DEFAULT NULL,
  `dateSent` date DEFAULT NULL,
  `dateSentReturn` date DEFAULT NULL,
  `receivedStatus` varchar(250) DEFAULT NULL,
  `totalBoxes` int(5) DEFAULT NULL,
  `totalBoxesReturned` int(5) DEFAULT NULL,
  `numSpecimens` int(5) DEFAULT NULL,
  `shippingMethod` varchar(50) DEFAULT NULL,
  `shippingMethodReturn` varchar(50) DEFAULT NULL,
  `dateDue` date DEFAULT NULL,
  `dateReceivedOwn` date DEFAULT NULL,
  `dateReceivedBorr` date DEFAULT NULL,
  `dateClosed` date DEFAULT NULL,
  `forWhom` varchar(50) DEFAULT NULL,
  `description` varchar(1000) DEFAULT NULL,
  `invoiceMessageOwn` varchar(500) DEFAULT NULL,
  `invoiceMessageBorr` varchar(500) DEFAULT NULL,
  `notes` varchar(500) DEFAULT NULL,
  `createdByOwn` varchar(30) DEFAULT NULL,
  `createdByBorr` varchar(30) DEFAULT NULL,
  `processingStatus` int(5) unsigned DEFAULT '1',
  `processedByOwn` varchar(30) DEFAULT NULL,
  `processedByBorr` varchar(30) DEFAULT NULL,
  `processedByReturnOwn` varchar(30) DEFAULT NULL,
  `processedByReturnBorr` varchar(30) DEFAULT NULL,
  `initialTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`loanid`),
  KEY `FK_occurloans_owninst` (`iidOwner`),
  KEY `FK_occurloans_borrinst` (`iidBorrower`),
  KEY `FK_occurloans_owncoll` (`collidOwn`),
  KEY `FK_occurloans_borrcoll` (`collidBorr`),
  CONSTRAINT `FK_occurloans_borrcoll` FOREIGN KEY (`collidBorr`) REFERENCES `omcollections` (`CollID`) ON UPDATE CASCADE,
  CONSTRAINT `FK_occurloans_borrinst` FOREIGN KEY (`iidBorrower`) REFERENCES `institutions` (`iid`) ON UPDATE CASCADE,
  CONSTRAINT `FK_occurloans_owncoll` FOREIGN KEY (`collidOwn`) REFERENCES `omcollections` (`CollID`) ON UPDATE CASCADE,
  CONSTRAINT `FK_occurloans_owninst` FOREIGN KEY (`iidOwner`) REFERENCES `institutions` (`iid`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurloanslink`
--

DROP TABLE IF EXISTS `omoccurloanslink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurloanslink` (
  `loanid` int(10) unsigned NOT NULL,
  `occid` int(10) unsigned NOT NULL,
  `returndate` date DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `initialTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`loanid`,`occid`),
  KEY `FK_occurloanlink_occid` (`occid`),
  KEY `FK_occurloanlink_loanid` (`loanid`),
  CONSTRAINT `FK_occurloanlink_loanid` FOREIGN KEY (`loanid`) REFERENCES `omoccurloans` (`loanid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_occurloanlink_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurpoints`
--

DROP TABLE IF EXISTS `omoccurpoints`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurpoints` (
  `geoID` int(11) NOT NULL AUTO_INCREMENT,
  `occid` int(11) NOT NULL,
  `point` point NOT NULL,
  `errradiuspoly` polygon DEFAULT NULL,
  `footprintpoly` polygon DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`geoID`),
  UNIQUE KEY `occid` (`occid`),
  SPATIAL KEY `point` (`point`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurrences`
--

DROP TABLE IF EXISTS `omoccurrences`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurrences` (
  `occid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `collid` int(10) unsigned NOT NULL,
  `dbpk` varchar(150) DEFAULT NULL,
  `basisOfRecord` varchar(32) DEFAULT 'PreservedSpecimen' COMMENT 'PreservedSpecimen, LivingSpecimen, HumanObservation',
  `occurrenceID` varchar(255) DEFAULT NULL COMMENT 'UniqueGlobalIdentifier',
  `catalogNumber` varchar(32) DEFAULT NULL,
  `otherCatalogNumbers` varchar(255) DEFAULT NULL,
  `ownerInstitutionCode` varchar(32) DEFAULT NULL,
  `institutionID` varchar(255) DEFAULT NULL,
  `collectionID` varchar(255) DEFAULT NULL,
  `datasetID` varchar(255) DEFAULT NULL,
  `institutionCode` varchar(64) DEFAULT NULL,
  `collectionCode` varchar(64) DEFAULT NULL,
  `family` varchar(255) DEFAULT NULL,
  `scientificName` varchar(255) DEFAULT NULL,
  `sciname` varchar(255) DEFAULT NULL,
  `tidinterpreted` int(10) unsigned DEFAULT NULL,
  `genus` varchar(255) DEFAULT NULL,
  `specificEpithet` varchar(255) DEFAULT NULL,
  `taxonRank` varchar(32) DEFAULT NULL,
  `infraspecificEpithet` varchar(255) DEFAULT NULL,
  `scientificNameAuthorship` varchar(255) DEFAULT NULL,
  `taxonRemarks` text,
  `identifiedBy` varchar(255) DEFAULT NULL,
  `dateIdentified` varchar(45) DEFAULT NULL,
  `identificationReferences` text,
  `identificationRemarks` text,
  `identificationQualifier` varchar(255) DEFAULT NULL COMMENT 'cf, aff, etc',
  `typeStatus` varchar(255) DEFAULT NULL,
  `recordedBy` varchar(255) DEFAULT NULL COMMENT 'Collector(s)',
  `recordNumber` varchar(45) DEFAULT NULL COMMENT 'Collector Number',
  `recordedbyid` bigint(20) DEFAULT NULL,
  `associatedCollectors` varchar(255) DEFAULT NULL COMMENT 'not DwC',
  `eventDate` date DEFAULT NULL,
  `year` int(10) DEFAULT NULL,
  `month` int(10) DEFAULT NULL,
  `day` int(10) DEFAULT NULL,
  `startDayOfYear` int(10) DEFAULT NULL,
  `endDayOfYear` int(10) DEFAULT NULL,
  `verbatimEventDate` varchar(255) DEFAULT NULL,
  `habitat` text COMMENT 'Habitat, substrait, etc',
  `substrate` varchar(500) DEFAULT NULL,
  `fieldNotes` text,
  `fieldnumber` varchar(45) DEFAULT NULL,
  `occurrenceRemarks` text COMMENT 'General Notes',
  `informationWithheld` varchar(250) DEFAULT NULL,
  `dataGeneralizations` varchar(250) DEFAULT NULL,
  `associatedOccurrences` text,
  `associatedTaxa` text COMMENT 'Associated Species',
  `dynamicProperties` text,
  `verbatimAttributes` text,
  `behavior` varchar(500) DEFAULT NULL,
  `attributes` text COMMENT 'Plant Description?',
  `reproductiveCondition` varchar(255) DEFAULT NULL COMMENT 'Phenology: flowers, fruit, sterile',
  `cultivationStatus` int(10) DEFAULT NULL COMMENT '0 = wild, 1 = cultivated',
  `establishmentMeans` varchar(45) DEFAULT NULL COMMENT 'cultivated, invasive, escaped from captivity, wild, native',
  `lifeStage` varchar(45) DEFAULT NULL,
  `sex` varchar(45) DEFAULT NULL,
  `individualCount` varchar(45) DEFAULT NULL,
  `samplingProtocol` varchar(100) DEFAULT NULL,
  `samplingEffort` varchar(200) DEFAULT NULL,
  `preparations` varchar(100) DEFAULT NULL,
  `country` varchar(64) DEFAULT NULL,
  `stateProvince` varchar(255) DEFAULT NULL,
  `county` varchar(255) DEFAULT NULL,
  `municipality` varchar(255) DEFAULT NULL,
  `locality` text,
  `localitySecurity` int(10) DEFAULT '0' COMMENT '0 = no security; 1 = hidden locality',
  `localitySecurityReason` varchar(100) DEFAULT NULL,
  `decimalLatitude` double DEFAULT NULL,
  `decimalLongitude` double DEFAULT NULL,
  `geodeticDatum` varchar(255) DEFAULT NULL,
  `coordinateUncertaintyInMeters` int(10) unsigned DEFAULT NULL,
  `footprintWKT` text,
  `coordinatePrecision` decimal(9,7) DEFAULT NULL,
  `locationRemarks` text,
  `verbatimCoordinates` varchar(255) DEFAULT NULL,
  `verbatimCoordinateSystem` varchar(255) DEFAULT NULL,
  `georeferencedBy` varchar(255) DEFAULT NULL,
  `georeferenceProtocol` varchar(255) DEFAULT NULL,
  `georeferenceSources` varchar(255) DEFAULT NULL,
  `georeferenceVerificationStatus` varchar(32) DEFAULT NULL,
  `georeferenceRemarks` varchar(255) DEFAULT NULL,
  `minimumElevationInMeters` int(6) DEFAULT NULL,
  `maximumElevationInMeters` int(6) DEFAULT NULL,
  `verbatimElevation` varchar(255) DEFAULT NULL,
  `minimumDepthInMeters` int(11) DEFAULT NULL,
  `maximumDepthInMeters` int(11) DEFAULT NULL,
  `verbatimDepth` varchar(50) DEFAULT NULL,
  `previousIdentifications` text,
  `disposition` varchar(100) DEFAULT NULL,
  `storageLocation` varchar(100) DEFAULT NULL,
  `genericcolumn1` varchar(100) DEFAULT NULL,
  `genericcolumn2` varchar(100) DEFAULT NULL,
  `modified` datetime DEFAULT NULL COMMENT 'DateLastModified',
  `language` varchar(20) DEFAULT NULL,
  `observeruid` int(10) unsigned DEFAULT NULL,
  `processingstatus` varchar(45) DEFAULT NULL,
  `recordEnteredBy` varchar(250) DEFAULT NULL,
  `duplicateQuantity` int(10) unsigned DEFAULT NULL,
  `labelProject` varchar(50) DEFAULT NULL,
  `dateEntered` datetime DEFAULT NULL,
  `dateLastModified` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`occid`) USING BTREE,
  UNIQUE KEY `Index_collid` (`collid`,`dbpk`),
  KEY `Index_sciname` (`sciname`),
  KEY `Index_family` (`family`),
  KEY `Index_country` (`country`),
  KEY `Index_state` (`stateProvince`),
  KEY `Index_county` (`county`),
  KEY `Index_collector` (`recordedBy`),
  KEY `Index_gui` (`occurrenceID`),
  KEY `Index_ownerInst` (`ownerInstitutionCode`),
  KEY `FK_omoccurrences_tid` (`tidinterpreted`),
  KEY `FK_omoccurrences_uid` (`observeruid`),
  KEY `Index_municipality` (`municipality`),
  KEY `Index_collnum` (`recordNumber`),
  KEY `Index_catalognumber` (`catalogNumber`),
  KEY `FK_recordedbyid` (`recordedbyid`),
  KEY `Index_eventDate` (`eventDate`),
  KEY `Index_occurrences_procstatus` (`processingstatus`),
  KEY `occelevmax` (`maximumElevationInMeters`),
  KEY `occelevmin` (`minimumElevationInMeters`),
  KEY `Index_occurrences_cult` (`cultivationStatus`),
  KEY `Index_occurrences_typestatus` (`typeStatus`),
  KEY `idx_occrecordedby` (`recordedBy`),
  KEY `Index_occurDateLastModifed` (`dateLastModified`),
  KEY `Index_occurDateEntered` (`dateEntered`),
  KEY `Index_occurRecordEnteredBy` (`recordEnteredBy`),
  CONSTRAINT `FK_omoccurrences_collid` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccurrences_recbyid` FOREIGN KEY (`recordedbyid`) REFERENCES `agents` (`agentid`) ON DELETE NO ACTION ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccurrences_tid` FOREIGN KEY (`tidinterpreted`) REFERENCES `taxa` (`TID`) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccurrences_uid` FOREIGN KEY (`observeruid`) REFERENCES `users` (`uid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `omoccurrencesfulltext_insert` AFTER INSERT ON `omoccurrences`
FOR EACH ROW BEGIN
  INSERT INTO omoccurrencesfulltext (
    `occid`,
    `recordedby`,
    `locality`
  ) VALUES (
    NEW.`occid`,
    NEW.`recordedby`,
    NEW.`locality`
  );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `omoccurrencesfulltext_update` AFTER UPDATE ON `omoccurrences`
FOR EACH ROW BEGIN
  UPDATE omoccurrencesfulltext SET
    `recordedby` = NEW.`recordedby`,
    `locality` = NEW.`locality`
  WHERE `occid` = NEW.`occid`;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `omoccurrencesfulltext_delete` BEFORE DELETE ON `omoccurrences`
FOR EACH ROW BEGIN
  DELETE FROM omoccurrencesfulltext WHERE `occid` = OLD.`occid`;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `omoccurrencesfulltext`
--

DROP TABLE IF EXISTS `omoccurrencesfulltext`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurrencesfulltext` (
  `occid` int(11) NOT NULL,
  `collid` int(11) NOT NULL,
  `locality` text,
  `recordedby` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`occid`),
  KEY `Index_occurfull_collid` (`collid`),
  FULLTEXT KEY `ft_occur_locality` (`locality`),
  FULLTEXT KEY `ft_occur_recordedby` (`recordedby`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omoccurverification`
--

DROP TABLE IF EXISTS `omoccurverification`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omoccurverification` (
  `ovsid` int(11) NOT NULL AUTO_INCREMENT,
  `occid` int(10) unsigned NOT NULL,
  `category` varchar(45) NOT NULL,
  `ranking` int(11) NOT NULL,
  `protocol` varchar(100) DEFAULT NULL,
  `source` varchar(45) DEFAULT NULL,
  `uid` int(10) unsigned DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ovsid`),
  UNIQUE KEY `UNIQUE_omoccurverification` (`occid`,`category`),
  KEY `FK_omoccurverification_occid_idx` (`occid`),
  KEY `FK_omoccurverification_uid_idx` (`uid`),
  CONSTRAINT `FK_omoccurverification_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_omoccurverification_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omsurveyoccurlink`
--

DROP TABLE IF EXISTS `omsurveyoccurlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omsurveyoccurlink` (
  `occid` int(10) unsigned NOT NULL,
  `surveyid` int(10) unsigned NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`occid`,`surveyid`),
  KEY `FK_omsurveyoccurlink_sur` (`surveyid`),
  CONSTRAINT `FK_omsurveyoccurlink_occ` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`),
  CONSTRAINT `FK_omsurveyoccurlink_sur` FOREIGN KEY (`surveyid`) REFERENCES `omsurveys` (`surveyid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omsurveyprojlink`
--

DROP TABLE IF EXISTS `omsurveyprojlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omsurveyprojlink` (
  `surveyid` int(10) unsigned NOT NULL,
  `pid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`surveyid`,`pid`) USING BTREE,
  KEY `FK_specprojcatlink_cat` (`pid`) USING BTREE,
  CONSTRAINT `FK_omsurveyprojlink_proj` FOREIGN KEY (`pid`) REFERENCES `fmprojects` (`pid`),
  CONSTRAINT `FK_omsurveyprojlink_sur` FOREIGN KEY (`surveyid`) REFERENCES `omsurveys` (`surveyid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `omsurveys`
--

DROP TABLE IF EXISTS `omsurveys`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `omsurveys` (
  `surveyid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `projectname` varchar(75) NOT NULL,
  `locality` varchar(1000) DEFAULT NULL,
  `managers` varchar(150) DEFAULT NULL,
  `latcentroid` double(9,6) DEFAULT NULL,
  `longcentroid` double(9,6) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `ispublic` int(10) unsigned NOT NULL DEFAULT '0',
  `sortsequence` int(10) unsigned NOT NULL DEFAULT '50',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`surveyid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referenceagentlinks`
--

DROP TABLE IF EXISTS `referenceagentlinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referenceagentlinks` (
  `refid` int(11) NOT NULL,
  `agentid` int(11) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `createdbyid` int(11) NOT NULL,
  PRIMARY KEY (`refid`,`agentid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referenceauthorlink`
--

DROP TABLE IF EXISTS `referenceauthorlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referenceauthorlink` (
  `refid` int(11) NOT NULL,
  `refauthid` int(11) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refid`,`refauthid`),
  KEY `FK_refauthlink_refid_idx` (`refid`),
  KEY `FK_refauthlink_refauthid_idx` (`refauthid`),
  CONSTRAINT `FK_refauthlink_refid` FOREIGN KEY (`refid`) REFERENCES `referenceobject` (`refid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_refauthlink_refauthid` FOREIGN KEY (`refauthid`) REFERENCES `referenceauthors` (`refauthorid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referenceauthors`
--

DROP TABLE IF EXISTS `referenceauthors`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referenceauthors` (
  `refauthorid` int(11) NOT NULL AUTO_INCREMENT,
  `lastname` varchar(100) NOT NULL,
  `firstname` varchar(100) DEFAULT NULL,
  `middlename` varchar(100) DEFAULT NULL,
  `modifieduid` int(10) unsigned DEFAULT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refauthorid`),
  KEY `INDEX_refauthlastname` (`lastname`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referencechecklistlink`
--

DROP TABLE IF EXISTS `referencechecklistlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referencechecklistlink` (
  `refid` int(11) NOT NULL,
  `clid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refid`,`clid`),
  KEY `FK_refcheckllistlink_refid_idx` (`refid`),
  KEY `FK_refcheckllistlink_clid_idx` (`clid`),
  CONSTRAINT `FK_refchecklistlink_refid` FOREIGN KEY (`refid`) REFERENCES `referenceobject` (`refid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_refchecklistlink_clid` FOREIGN KEY (`clid`) REFERENCES `fmchecklists` (`CLID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referencechklsttaxalink`
--

DROP TABLE IF EXISTS `referencechklsttaxalink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referencechklsttaxalink` (
  `refid` int(11) NOT NULL,
  `clid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refid`,`clid`,`tid`),
  KEY `FK_refchktaxalink_clidtid_idx` (`clid`,`tid`),
  CONSTRAINT `FK_refchktaxalink_ref` FOREIGN KEY (`refid`) REFERENCES `referenceobject` (`refid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_refchktaxalink_clidtid` FOREIGN KEY (`clid`, `tid`) REFERENCES `fmchklsttaxalink` (`CLID`, `TID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referencecollectionlink`
--

DROP TABLE IF EXISTS `referencecollectionlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referencecollectionlink` (
  `refid` int(11) NOT NULL,
  `collid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refid`,`collid`),
  KEY `FK_refcollectionlink_collid_idx` (`collid`),
  CONSTRAINT `FK_refcollectionlink_refid` FOREIGN KEY (`refid`) REFERENCES `referenceobject` (`refid`) ON DELETE NO ACTION ON UPDATE NO ACTION,
  CONSTRAINT `FK_refcollectionlink_collid` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referenceobject`
--

DROP TABLE IF EXISTS `referenceobject`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referenceobject` (
  `refid` int(11) NOT NULL AUTO_INCREMENT,
  `parentRefId` int(11) DEFAULT NULL,
  `ReferenceTypeId` int(11) DEFAULT NULL,
  `title` varchar(150) NOT NULL,
  `secondarytitle` varchar(250) DEFAULT NULL,
  `shorttitle` varchar(250) DEFAULT NULL,
  `tertiarytitle` varchar(250) DEFAULT NULL,
  `alternativetitle` varchar(250) DEFAULT NULL,
  `typework` varchar(150) DEFAULT NULL,
  `figures` varchar(150) DEFAULT NULL,
  `pubdate` varchar(45) DEFAULT NULL,
  `edition` varchar(45) DEFAULT NULL,
  `volume` varchar(45) DEFAULT NULL,
  `numbervolumnes` varchar(45) DEFAULT NULL,
  `number` varchar(45) DEFAULT NULL,
  `pages` varchar(45) DEFAULT NULL,
  `section` varchar(45) DEFAULT NULL,
  `placeofpublication` varchar(45) DEFAULT NULL,
  `publisher` varchar(150) DEFAULT NULL,
  `isbn_issn` varchar(45) DEFAULT NULL,
  `url` varchar(150) DEFAULT NULL,
  `guid` varchar(45) DEFAULT NULL,
  `ispublished` varchar(45) DEFAULT NULL,
  `notes` varchar(45) DEFAULT NULL,
  `cheatauthors` varchar(250) DEFAULT NULL,
  `cheatcitation` varchar(250) DEFAULT NULL,
  `modifieduid` int(10) unsigned DEFAULT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refid`),
  KEY `INDEX_refobj_title` (`title`),
  KEY `FK_refobj_parentrefid_idx` (`parentRefId`),
  KEY `FK_refobj_typeid_idx` (`ReferenceTypeId`),
  CONSTRAINT `FK_refobj_parentrefid` FOREIGN KEY (`parentRefId`) REFERENCES `referenceobject` (`refid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_refobj_reftypeid` FOREIGN KEY (`ReferenceTypeId`) REFERENCES `referencetype` (`ReferenceTypeId`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referenceoccurlink`
--

DROP TABLE IF EXISTS `referenceoccurlink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referenceoccurlink` (
  `refid` int(11) NOT NULL,
  `occid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refid`,`occid`),
  KEY `FK_refoccurlink_refid_idx` (`refid`),
  KEY `FK_refoccurlink_occid_idx` (`occid`),
  CONSTRAINT `FK_refoccurlink_refid` FOREIGN KEY (`refid`) REFERENCES `referenceobject` (`refid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_refoccurlink_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referencetaxalink`
--

DROP TABLE IF EXISTS `referencetaxalink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referencetaxalink` (
  `refid` int(11) NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`refid`,`tid`),
  KEY `FK_reftaxalink_refid_idx` (`refid`),
  KEY `FK_reftaxalink_tid_idx` (`tid`),
  CONSTRAINT `FK_reftaxalink_refid` FOREIGN KEY (`refid`) REFERENCES `referenceobject` (`refid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_reftaxalink_tid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `referencetype`
--

DROP TABLE IF EXISTS `referencetype`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `referencetype` (
  `ReferenceTypeId` int(11) NOT NULL AUTO_INCREMENT,
  `ReferenceType` varchar(45) NOT NULL,
  `IsParent` int(11) DEFAULT NULL,
  `Title` varchar(45) DEFAULT NULL,
  `SecondaryTitle` varchar(45) DEFAULT NULL,
  `PlacePublished` varchar(45) DEFAULT NULL,
  `Publisher` varchar(45) DEFAULT NULL,
  `Volume` varchar(45) DEFAULT NULL,
  `NumberVolumes` varchar(45) DEFAULT NULL,
  `Number` varchar(45) DEFAULT NULL,
  `Pages` varchar(45) DEFAULT NULL,
  `Section` varchar(45) DEFAULT NULL,
  `TertiaryTitle` varchar(45) DEFAULT NULL,
  `Edition` varchar(45) DEFAULT NULL,
  `Date` varchar(45) DEFAULT NULL,
  `TypeWork` varchar(45) DEFAULT NULL,
  `ShortTitle` varchar(45) DEFAULT NULL,
  `AlternativeTitle` varchar(45) DEFAULT NULL,
  `ISBN_ISSN` varchar(45) DEFAULT NULL,
  `Figures` varchar(45) DEFAULT NULL,
  `addedByUid` int(11) DEFAULT NULL,
  `initialTimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ReferenceTypeId`),
  UNIQUE KEY `ReferenceType_UNIQUE` (`ReferenceType`)
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `salixwordstats`
--

DROP TABLE IF EXISTS `salixwordstats`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `salixwordstats` (
  `swsid` int(11) NOT NULL AUTO_INCREMENT,
  `firstword` varchar(45) NOT NULL,
  `secondword` varchar(45) DEFAULT NULL,
  `locality` int(4) NOT NULL DEFAULT '0',
  `localityFreq` int(4) NOT NULL DEFAULT '0',
  `habitat` int(4) NOT NULL DEFAULT '0',
  `habitatFreq` int(4) NOT NULL DEFAULT '0',
  `substrate` int(4) NOT NULL DEFAULT '0',
  `substrateFreq` int(4) NOT NULL DEFAULT '0',
  `verbatimAttributes` int(4) NOT NULL DEFAULT '0',
  `verbatimAttributesFreq` int(4) NOT NULL DEFAULT '0',
  `occurrenceRemarks` int(4) NOT NULL DEFAULT '0',
  `occurrenceRemarksFreq` int(4) NOT NULL DEFAULT '0',
  `totalcount` int(4) NOT NULL DEFAULT '0',
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`swsid`),
  UNIQUE KEY `INDEX_unique` (`firstword`,`secondword`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `schemaversion`
--

DROP TABLE IF EXISTS `schemaversion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `schemaversion` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `versionnumber` varchar(20) NOT NULL,
  `dateapplied` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `specprocessorprojects`
--

DROP TABLE IF EXISTS `specprocessorprojects`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specprocessorprojects` (
  `spprid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `collid` int(10) unsigned NOT NULL,
  `title` varchar(100) NOT NULL,
  `specKeyPattern` varchar(45) DEFAULT NULL,
  `speckeyretrieval` varchar(45) DEFAULT NULL,
  `coordX1` int(10) unsigned DEFAULT NULL,
  `coordX2` int(10) unsigned DEFAULT NULL,
  `coordY1` int(10) unsigned DEFAULT NULL,
  `coordY2` int(10) unsigned DEFAULT NULL,
  `sourcePath` varchar(250) DEFAULT NULL,
  `targetPath` varchar(250) DEFAULT NULL,
  `imgUrl` varchar(250) DEFAULT NULL,
  `webPixWidth` int(10) unsigned DEFAULT '1200',
  `tnPixWidth` int(10) unsigned DEFAULT '130',
  `lgPixWidth` int(10) unsigned DEFAULT '2400',
  `jpgcompression` int(11) DEFAULT '70',
  `createTnImg` int(10) unsigned DEFAULT '1',
  `createLgImg` int(10) unsigned DEFAULT '1',
  `source` varchar(45) DEFAULT NULL,
  `initialTimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`spprid`),
  KEY `FK_specprocessorprojects_coll` (`collid`),
  CONSTRAINT `FK_specprocessorprojects_coll` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `specprocessorrawlabels`
--

DROP TABLE IF EXISTS `specprocessorrawlabels`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specprocessorrawlabels` (
  `prlid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `imgid` int(10) unsigned DEFAULT NULL,
  `occid` int(10) unsigned DEFAULT NULL,
  `rawstr` text NOT NULL,
  `processingvariables` varchar(250) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `source` varchar(150) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`prlid`),
  KEY `FK_specproc_images` (`imgid`),
  KEY `FK_specproc_occid` (`occid`),
  CONSTRAINT `FK_specproc_occid` FOREIGN KEY (`occid`) REFERENCES `omoccurrences` (`occid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_specproc_images` FOREIGN KEY (`imgid`) REFERENCES `images` (`imgid`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `specprocessorrawlabelsfulltext_insert` AFTER INSERT ON `specprocessorrawlabels`
FOR EACH ROW BEGIN
  INSERT INTO specprocessorrawlabelsfulltext (
    `prlid`,
    `imgid`,
    `rawstr`
  ) VALUES (
    NEW.`prlid`,
    NEW.`imgid`,
    NEW.`rawstr`
  );
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `specprocessorrawlabelsfulltext_update` AFTER UPDATE ON `specprocessorrawlabels`
FOR EACH ROW BEGIN
  UPDATE specprocessorrawlabelsfulltext SET
    `imgid` = NEW.`imgid`,
    `rawstr` = NEW.`rawstr`
  WHERE `prlid` = NEW.`prlid`;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `specprocessorrawlabelsfulltext`
--

DROP TABLE IF EXISTS `specprocessorrawlabelsfulltext`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specprocessorrawlabelsfulltext` (
  `prlid` int(11) NOT NULL,
  `imgid` int(11) NOT NULL,
  `rawstr` text NOT NULL,
  PRIMARY KEY (`prlid`),
  KEY `Index_ocr_imgid` (`imgid`),
  FULLTEXT KEY `Index_ocr_fulltext` (`rawstr`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;
/*!50003 SET @saved_cs_client      = @@character_set_client */ ;
/*!50003 SET @saved_cs_results     = @@character_set_results */ ;
/*!50003 SET @saved_col_connection = @@collation_connection */ ;
/*!50003 SET character_set_client  = utf8 */ ;
/*!50003 SET character_set_results = utf8 */ ;
/*!50003 SET collation_connection  = utf8_general_ci */ ;
/*!50003 SET @saved_sql_mode       = @@sql_mode */ ;
/*!50003 SET sql_mode              = 'STRICT_TRANS_TABLES,NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION' */ ;
DELIMITER ;;
/*!50003 CREATE*/ /*!50017 DEFINER=`root`@`localhost`*/ /*!50003 TRIGGER `specprocessorrawlabelsfulltext_delete` BEFORE DELETE ON `specprocessorrawlabelsfulltext`
FOR EACH ROW BEGIN
  DELETE FROM specprocessorrawlabelsfulltext WHERE `prlid` = OLD.`prlid`;
END */;;
DELIMITER ;
/*!50003 SET sql_mode              = @saved_sql_mode */ ;
/*!50003 SET character_set_client  = @saved_cs_client */ ;
/*!50003 SET character_set_results = @saved_cs_results */ ;
/*!50003 SET collation_connection  = @saved_col_connection */ ;

--
-- Table structure for table `specprocnlp`
--

DROP TABLE IF EXISTS `specprocnlp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specprocnlp` (
  `spnlpid` int(10) NOT NULL AUTO_INCREMENT,
  `title` varchar(45) NOT NULL,
  `sqlfrag` varchar(250) NOT NULL,
  `patternmatch` varchar(250) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `collid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`spnlpid`),
  KEY `FK_specprocnlp_collid` (`collid`),
  CONSTRAINT `FK_specprocnlp_collid` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `specprocnlpfrag`
--

DROP TABLE IF EXISTS `specprocnlpfrag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specprocnlpfrag` (
  `spnlpfragid` int(10) NOT NULL AUTO_INCREMENT,
  `spnlpid` int(10) NOT NULL,
  `fieldname` varchar(45) NOT NULL,
  `patternmatch` varchar(250) NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `sortseq` int(5) DEFAULT '50',
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`spnlpfragid`),
  KEY `FK_specprocnlpfrag_spnlpid` (`spnlpid`),
  CONSTRAINT `FK_specprocnlpfrag_spnlpid` FOREIGN KEY (`spnlpid`) REFERENCES `specprocnlp` (`spnlpid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `specprocnlpversion`
--

DROP TABLE IF EXISTS `specprocnlpversion`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specprocnlpversion` (
  `nlpverid` int(11) NOT NULL AUTO_INCREMENT,
  `prlid` int(10) unsigned NOT NULL,
  `archivestr` text NOT NULL,
  `processingvariables` varchar(250) DEFAULT NULL,
  `score` int(11) DEFAULT NULL,
  `source` varchar(150) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`nlpverid`),
  KEY `FK_specprocnlpver_rawtext_idx` (`prlid`),
  CONSTRAINT `FK_specprocnlpver_rawtext` FOREIGN KEY (`prlid`) REFERENCES `specprocessorrawlabels` (`prlid`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COMMENT='Archives field name - value pairs of NLP results loading into an omoccurrence record. This way, results can be easily redone at a later date without copying over date modifed afterward by another user or process ';
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `specprococrfrag`
--

DROP TABLE IF EXISTS `specprococrfrag`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `specprococrfrag` (
  `ocrfragid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `prlid` int(10) unsigned NOT NULL,
  `firstword` varchar(45) NOT NULL,
  `secondword` varchar(45) DEFAULT NULL,
  `keyterm` varchar(45) DEFAULT NULL,
  `wordorder` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`ocrfragid`),
  KEY `FK_specprococrfrag_prlid_idx` (`prlid`),
  KEY `Index_keyterm` (`keyterm`),
  CONSTRAINT `FK_specprococrfrag_prlid` FOREIGN KEY (`prlid`) REFERENCES `specprocessorrawlabels` (`prlid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxa`
--

DROP TABLE IF EXISTS `taxa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxa` (
  `TID` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `kingdomName` varchar(45) DEFAULT NULL,
  `KingdomID` tinyint(3) unsigned DEFAULT NULL,
  `RankId` smallint(5) unsigned DEFAULT NULL,
  `SciName` varchar(250) NOT NULL,
  `UnitInd1` varchar(1) DEFAULT NULL,
  `UnitName1` varchar(50) NOT NULL,
  `UnitInd2` varchar(1) DEFAULT NULL,
  `UnitName2` varchar(50) DEFAULT NULL,
  `UnitInd3` varchar(7) DEFAULT NULL,
  `UnitName3` varchar(35) DEFAULT NULL,
  `Author` varchar(100) DEFAULT NULL,
  `PhyloSortSequence` tinyint(3) unsigned DEFAULT NULL,
  `Status` varchar(50) DEFAULT NULL,
  `Source` varchar(250) DEFAULT NULL,
  `Notes` varchar(250) DEFAULT NULL,
  `Hybrid` varchar(50) DEFAULT NULL,
  `SecurityStatus` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '0 = no security; 1 = hidden locality',
  `modifiedUid` int(10) unsigned DEFAULT NULL,
  `modifiedTimeStamp` datetime DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`TID`),
  UNIQUE KEY `sciname_unique` (`SciName`),
  KEY `rankid_index` (`RankId`),
  KEY `unitname1_index` (`UnitName1`,`UnitName2`) USING BTREE,
  KEY `FK_taxa_uid_idx` (`modifiedUid`),
  CONSTRAINT `FK_taxa_uid` FOREIGN KEY (`modifiedUid`) REFERENCES `users` (`uid`) ON DELETE NO ACTION
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxadescrblock`
--

DROP TABLE IF EXISTS `taxadescrblock`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxadescrblock` (
  `tdbid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned NOT NULL,
  `caption` varchar(20) DEFAULT NULL,
  `source` varchar(250) DEFAULT NULL,
  `sourceurl` varchar(250) DEFAULT NULL,
  `language` varchar(45) DEFAULT 'English',
  `displaylevel` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '1 = short descr, 2 = intermediate descr',
  `uid` int(10) unsigned NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tdbid`),
  UNIQUE KEY `Index_unique` (`tid`,`displaylevel`,`language`),
  CONSTRAINT `FK_taxadescrblock_tid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxadescrstmts`
--

DROP TABLE IF EXISTS `taxadescrstmts`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxadescrstmts` (
  `tdsid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tdbid` int(10) unsigned NOT NULL,
  `heading` varchar(75) NOT NULL,
  `statement` text NOT NULL,
  `displayheader` int(10) unsigned NOT NULL DEFAULT '1',
  `notes` varchar(250) DEFAULT NULL,
  `sortsequence` int(10) unsigned NOT NULL DEFAULT '89',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tdsid`),
  KEY `FK_taxadescrstmts_tblock` (`tdbid`),
  CONSTRAINT `FK_taxadescrstmts_tblock` FOREIGN KEY (`tdbid`) REFERENCES `taxadescrblock` (`tdbid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxaenumtree`
--

DROP TABLE IF EXISTS `taxaenumtree`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxaenumtree` (
  `tid` int(10) unsigned NOT NULL,
  `taxauthid` int(10) unsigned NOT NULL,
  `parenttid` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tid`,`taxauthid`,`parenttid`),
  KEY `FK_tet_taxa` (`tid`),
  KEY `FK_tet_taxauth` (`taxauthid`),
  KEY `FK_tet_taxa2` (`parenttid`),
  CONSTRAINT `FK_tet_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tet_taxauth` FOREIGN KEY (`taxauthid`) REFERENCES `taxauthority` (`taxauthid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tet_taxa2` FOREIGN KEY (`parenttid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxalinks`
--

DROP TABLE IF EXISTS `taxalinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxalinks` (
  `tlid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned NOT NULL,
  `url` varchar(500) NOT NULL,
  `title` varchar(100) NOT NULL,
  `sourceIdentifier` varchar(45) DEFAULT NULL,
  `owner` varchar(100) DEFAULT NULL,
  `icon` varchar(45) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `sortsequence` int(10) unsigned NOT NULL DEFAULT '50',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tlid`),
  KEY `Index_unique` (`tid`,`url`(255)),
  CONSTRAINT `FK_taxalinks_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxamaps`
--

DROP TABLE IF EXISTS `taxamaps`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxamaps` (
  `mid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned NOT NULL,
  `url` varchar(255) NOT NULL,
  `title` varchar(100) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`mid`),
  KEY `FK_tid_idx` (`tid`),
  CONSTRAINT `FK_taxamaps_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxanestedtree`
--

DROP TABLE IF EXISTS `taxanestedtree`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxanestedtree` (
  `tid` int(10) unsigned NOT NULL,
  `taxauthid` int(10) unsigned NOT NULL,
  `leftindex` int(10) unsigned NOT NULL,
  `rightindex` int(10) unsigned NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tid`,`taxauthid`),
  KEY `leftindex` (`leftindex`),
  KEY `rightindex` (`rightindex`),
  KEY `FK_tnt_taxa` (`tid`),
  KEY `FK_tnt_taxauth` (`taxauthid`),
  CONSTRAINT `FK_tnt_taxa` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tnt_taxauth` FOREIGN KEY (`taxauthid`) REFERENCES `taxauthority` (`taxauthid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxaprofilepubdesclink`
--

DROP TABLE IF EXISTS `taxaprofilepubdesclink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxaprofilepubdesclink` (
  `tdbid` int(10) unsigned NOT NULL,
  `tppid` int(11) NOT NULL,
  `caption` varchar(45) DEFAULT NULL,
  `editornotes` varchar(250) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tdbid`,`tppid`),
  KEY `FK_tppubdesclink_id_idx` (`tppid`),
  CONSTRAINT `FK_tppubdesclink_tdbid` FOREIGN KEY (`tdbid`) REFERENCES `taxadescrblock` (`tdbid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tppubdesclink_id` FOREIGN KEY (`tppid`) REFERENCES `taxaprofilepubs` (`tppid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxaprofilepubimagelink`
--

DROP TABLE IF EXISTS `taxaprofilepubimagelink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxaprofilepubimagelink` (
  `imgid` int(10) unsigned NOT NULL,
  `tppid` int(11) NOT NULL,
  `caption` varchar(45) DEFAULT NULL,
  `editornotes` varchar(250) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`imgid`,`tppid`),
  KEY `FK_tppubimagelink_id_idx` (`tppid`),
  CONSTRAINT `FK_tppubimagelink_imgid` FOREIGN KEY (`imgid`) REFERENCES `images` (`imgid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tppubimagelink_id` FOREIGN KEY (`tppid`) REFERENCES `taxaprofilepubs` (`tppid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxaprofilepubmaplink`
--

DROP TABLE IF EXISTS `taxaprofilepubmaplink`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxaprofilepubmaplink` (
  `mid` int(10) unsigned NOT NULL,
  `tppid` int(11) NOT NULL,
  `caption` varchar(45) DEFAULT NULL,
  `editornotes` varchar(250) DEFAULT NULL,
  `sortsequence` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`mid`,`tppid`),
  KEY `FK_tppubmaplink_id_idx` (`tppid`),
  CONSTRAINT `FK_tppubmaplink_tdbid` FOREIGN KEY (`mid`) REFERENCES `taxamaps` (`mid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_tppubmaplink_id` FOREIGN KEY (`tppid`) REFERENCES `taxaprofilepubs` (`tppid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxaprofilepubs`
--

DROP TABLE IF EXISTS `taxaprofilepubs`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxaprofilepubs` (
  `tppid` int(11) NOT NULL AUTO_INCREMENT,
  `pubtitle` varchar(150) NOT NULL,
  `authors` varchar(150) DEFAULT NULL,
  `description` varchar(500) DEFAULT NULL,
  `abstract` text,
  `uidowner` int(10) unsigned DEFAULT NULL,
  `externalurl` varchar(250) DEFAULT NULL,
  `rights` varchar(250) DEFAULT NULL,
  `usageterm` varchar(250) DEFAULT NULL,
  `accessrights` varchar(250) DEFAULT NULL,
  `ispublic` int(11) DEFAULT NULL,
  `inclusive` int(11) DEFAULT NULL,
  `dynamicProperties` text,
  `initialtimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`tppid`),
  KEY `FK_taxaprofilepubs_uid_idx` (`uidowner`),
  KEY `INDEX_taxaprofilepubs_title` (`pubtitle`),
  CONSTRAINT `FK_taxaprofilepubs_uid` FOREIGN KEY (`uidowner`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxaresourcelinks`
--

DROP TABLE IF EXISTS `taxaresourcelinks`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxaresourcelinks` (
  `taxaresourceid` int(11) NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned NOT NULL,
  `sourcename` varchar(150) NOT NULL,
  `sourceidentifier` varchar(45) DEFAULT NULL,
  `sourceguid` varchar(150) DEFAULT NULL,
  `url` varchar(250) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `ranking` int(11) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`taxaresourceid`),
  KEY `taxaresource_name` (`sourcename`),
  KEY `FK_taxaresource_tid_idx` (`tid`),
  CONSTRAINT `FK_taxaresource_tid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxauthority`
--

DROP TABLE IF EXISTS `taxauthority`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxauthority` (
  `taxauthid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `isprimary` int(1) unsigned NOT NULL DEFAULT '0',
  `name` varchar(45) NOT NULL,
  `description` varchar(250) DEFAULT NULL,
  `editors` varchar(150) DEFAULT NULL,
  `contact` varchar(45) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `url` varchar(150) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `isactive` int(1) unsigned NOT NULL DEFAULT '1',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`taxauthid`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxavernaculars`
--

DROP TABLE IF EXISTS `taxavernaculars`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxavernaculars` (
  `TID` int(10) unsigned NOT NULL DEFAULT '0',
  `VernacularName` varchar(80) NOT NULL,
  `Language` varchar(15) NOT NULL DEFAULT 'English',
  `Source` varchar(50) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `username` varchar(45) DEFAULT NULL,
  `isupperterm` int(2) DEFAULT '0',
  `SortSequence` int(10) DEFAULT '50',
  `VID` int(10) NOT NULL AUTO_INCREMENT,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`VID`),
  UNIQUE KEY `unique-key` (`Language`,`VernacularName`,`TID`),
  KEY `tid1` (`TID`),
  KEY `vernacularsnames` (`VernacularName`),
  CONSTRAINT `FK_vernaculars_tid` FOREIGN KEY (`TID`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxonunits`
--

DROP TABLE IF EXISTS `taxonunits`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxonunits` (
  `taxonunitid` int(11) NOT NULL AUTO_INCREMENT,
  `kingdomid` tinyint(3) unsigned DEFAULT NULL,
  `kingdomName` varchar(45) NOT NULL DEFAULT 'Organism',
  `rankid` smallint(5) unsigned NOT NULL DEFAULT '0',
  `rankname` varchar(15) NOT NULL,
  `suffix` varchar(45) DEFAULT NULL,
  `dirparentrankid` smallint(6) NOT NULL,
  `reqparentrankid` smallint(6) DEFAULT NULL,
  `modifiedby` varchar(45) DEFAULT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`taxonunitid`)
) ENGINE=InnoDB AUTO_INCREMENT=24 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `taxstatus`
--

DROP TABLE IF EXISTS `taxstatus`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `taxstatus` (
  `tid` int(10) unsigned NOT NULL,
  `tidaccepted` int(10) unsigned NOT NULL,
  `taxauthid` int(10) unsigned NOT NULL COMMENT 'taxon authority id',
  `parenttid` int(10) unsigned DEFAULT NULL,
  `hierarchystr` varchar(200) DEFAULT NULL,
  `family` varchar(50) DEFAULT NULL,
  `UnacceptabilityReason` varchar(250) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `SortSequence` int(10) unsigned DEFAULT '50',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`tid`,`tidaccepted`,`taxauthid`) USING BTREE,
  KEY `FK_taxstatus_tidacc` (`tidaccepted`),
  KEY `FK_taxstatus_taid` (`taxauthid`),
  KEY `Index_ts_family` (`family`),
  KEY `Index_parenttid` (`parenttid`),
  KEY `Index_hierarchy` (`hierarchystr`) USING BTREE,
  CONSTRAINT `FK_taxstatus_parent` FOREIGN KEY (`parenttid`) REFERENCES `taxa` (`TID`),
  CONSTRAINT `FK_taxstatus_taid` FOREIGN KEY (`taxauthid`) REFERENCES `taxauthority` (`taxauthid`) ON UPDATE CASCADE,
  CONSTRAINT `FK_taxstatus_tid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`),
  CONSTRAINT `FK_taxstatus_tidacc` FOREIGN KEY (`tidaccepted`) REFERENCES `taxa` (`TID`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `unknowncomments`
--

DROP TABLE IF EXISTS `unknowncomments`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unknowncomments` (
  `unkcomid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `unkid` int(10) unsigned NOT NULL,
  `comment` varchar(500) NOT NULL,
  `username` varchar(45) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`unkcomid`) USING BTREE,
  KEY `FK_unknowncomments` (`unkid`),
  CONSTRAINT `FK_unknowncomments` FOREIGN KEY (`unkid`) REFERENCES `unknowns` (`unkid`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `unknownimages`
--

DROP TABLE IF EXISTS `unknownimages`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unknownimages` (
  `unkimgid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `unkid` int(10) unsigned NOT NULL,
  `url` varchar(255) NOT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`unkimgid`),
  KEY `FK_unknowns` (`unkid`),
  CONSTRAINT `FK_unknowns` FOREIGN KEY (`unkid`) REFERENCES `unknowns` (`unkid`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `unknowns`
--

DROP TABLE IF EXISTS `unknowns`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `unknowns` (
  `unkid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tid` int(10) unsigned DEFAULT NULL,
  `photographer` varchar(100) DEFAULT NULL,
  `owner` varchar(100) DEFAULT NULL,
  `locality` varchar(250) DEFAULT NULL,
  `latdecimal` double DEFAULT NULL,
  `longdecimal` double DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `username` varchar(45) NOT NULL,
  `idstatus` varchar(45) NOT NULL DEFAULT 'ID pending',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`unkid`) USING BTREE,
  KEY `FK_unknowns_username` (`username`),
  KEY `FK_unknowns_tid` (`tid`),
  CONSTRAINT `FK_unknowns_tid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`),
  CONSTRAINT `FK_unknowns_username` FOREIGN KEY (`username`) REFERENCES `userlogin` (`username`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `uploaddetermtemp`
--

DROP TABLE IF EXISTS `uploaddetermtemp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploaddetermtemp` (
  `occid` int(10) unsigned DEFAULT NULL,
  `collid` int(10) unsigned DEFAULT NULL,
  `dbpk` varchar(150) DEFAULT NULL,
  `identifiedBy` varchar(60) NOT NULL,
  `dateIdentified` varchar(45) NOT NULL,
  `dateIdentifiedInterpreted` date DEFAULT NULL,
  `sciname` varchar(100) NOT NULL,
  `scientificNameAuthorship` varchar(100) DEFAULT NULL,
  `identificationQualifier` varchar(45) DEFAULT NULL,
  `iscurrent` int(2) DEFAULT '0',
  `detType` varchar(45) DEFAULT NULL,
  `identificationReferences` varchar(255) DEFAULT NULL,
  `identificationRemarks` varchar(255) DEFAULT NULL,
  `sourceIdentifier` varchar(45) DEFAULT NULL,
  `sortsequence` int(10) unsigned DEFAULT '10',
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `Index_uploaddet_occid` (`occid`),
  KEY `Index_uploaddet_collid` (`collid`),
  KEY `Index_uploaddet_dbpk` (`dbpk`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `uploadimagetemp`
--

DROP TABLE IF EXISTS `uploadimagetemp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploadimagetemp` (
  `tid` int(10) unsigned DEFAULT NULL,
  `url` varchar(255) NOT NULL,
  `thumbnailurl` varchar(255) DEFAULT NULL,
  `originalurl` varchar(255) DEFAULT NULL,
  `archiveurl` varchar(255) DEFAULT NULL,
  `photographer` varchar(100) DEFAULT NULL,
  `photographeruid` int(10) unsigned DEFAULT NULL,
  `imagetype` varchar(50) DEFAULT NULL,
  `format` varchar(45) DEFAULT NULL,
  `caption` varchar(100) DEFAULT NULL,
  `owner` varchar(100) DEFAULT NULL,
  `occid` int(10) unsigned DEFAULT NULL,
  `collid` int(10) unsigned DEFAULT NULL,
  `dbpk` varchar(150) DEFAULT NULL,
  `specimengui` varchar(45) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `username` varchar(45) DEFAULT NULL,
  `sortsequence` int(10) unsigned DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `Index_uploadimg_occid` (`occid`),
  KEY `Index_uploadimg_collid` (`collid`),
  KEY `Index_uploadimg_dbpk` (`dbpk`),
  KEY `Index_uploadimg_ts` (`initialtimestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `uploadspecmap`
--

DROP TABLE IF EXISTS `uploadspecmap`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploadspecmap` (
  `usmid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uspid` int(10) unsigned NOT NULL,
  `sourcefield` varchar(45) NOT NULL,
  `symbdatatype` varchar(45) NOT NULL DEFAULT 'string' COMMENT 'string, numeric, datetime',
  `symbspecfield` varchar(45) NOT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`usmid`),
  UNIQUE KEY `Index_unique` (`uspid`,`symbspecfield`,`sourcefield`),
  CONSTRAINT `FK_uploadspecmap_usp` FOREIGN KEY (`uspid`) REFERENCES `uploadspecparameters` (`uspid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=64 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `uploadspecparameters`
--

DROP TABLE IF EXISTS `uploadspecparameters`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploadspecparameters` (
  `uspid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `CollID` int(10) unsigned NOT NULL,
  `UploadType` int(10) unsigned NOT NULL DEFAULT '1' COMMENT '1 = Direct; 2 = DiGIR; 3 = File',
  `title` varchar(45) NOT NULL,
  `Platform` varchar(45) DEFAULT '1' COMMENT '1 = MySQL; 2 = MSSQL; 3 = ORACLE; 11 = MS Access; 12 = FileMaker',
  `server` varchar(150) DEFAULT NULL,
  `port` int(10) unsigned DEFAULT NULL,
  `driver` varchar(45) DEFAULT NULL,
  `Code` varchar(45) DEFAULT NULL,
  `Path` varchar(150) DEFAULT NULL,
  `PkField` varchar(45) DEFAULT NULL,
  `Username` varchar(45) DEFAULT NULL,
  `Password` varchar(45) DEFAULT NULL,
  `SchemaName` varchar(150) DEFAULT NULL,
  `QueryStr` varchar(2000) DEFAULT NULL,
  `cleanupsp` varchar(45) DEFAULT NULL,
  `dlmisvalid` int(10) unsigned DEFAULT '0',
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`uspid`),
  KEY `FK_uploadspecparameters_coll` (`CollID`),
  CONSTRAINT `FK_uploadspecparameters_coll` FOREIGN KEY (`CollID`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `uploadspectemp`
--

DROP TABLE IF EXISTS `uploadspectemp`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploadspectemp` (
  `collid` int(10) unsigned NOT NULL,
  `dbpk` varchar(150) DEFAULT NULL,
  `occid` int(10) unsigned DEFAULT NULL,
  `basisOfRecord` varchar(32) DEFAULT 'PreservedSpecimen' COMMENT 'PreservedSpecimen, LivingSpecimen, HumanObservation',
  `occurrenceID` varchar(255) DEFAULT NULL COMMENT 'UniqueGlobalIdentifier',
  `catalogNumber` varchar(32) DEFAULT NULL,
  `otherCatalogNumbers` varchar(255) DEFAULT NULL,
  `ownerInstitutionCode` varchar(32) DEFAULT NULL,
  `institutionID` varchar(255) DEFAULT NULL,
  `collectionID` varchar(255) DEFAULT NULL,
  `datasetID` varchar(255) DEFAULT NULL,
  `institutionCode` varchar(64) DEFAULT NULL,
  `collectionCode` varchar(64) DEFAULT NULL,
  `family` varchar(255) DEFAULT NULL,
  `scientificName` varchar(255) DEFAULT NULL,
  `sciname` varchar(255) DEFAULT NULL,
  `tidinterpreted` int(10) unsigned DEFAULT NULL,
  `genus` varchar(255) DEFAULT NULL,
  `specificEpithet` varchar(255) DEFAULT NULL,
  `taxonRank` varchar(32) DEFAULT NULL,
  `infraspecificEpithet` varchar(255) DEFAULT NULL,
  `scientificNameAuthorship` varchar(255) DEFAULT NULL,
  `taxonRemarks` text,
  `identifiedBy` varchar(255) DEFAULT NULL,
  `dateIdentified` varchar(45) DEFAULT NULL,
  `identificationReferences` text,
  `identificationRemarks` text,
  `identificationQualifier` varchar(255) DEFAULT NULL COMMENT 'cf, aff, etc',
  `typeStatus` varchar(255) DEFAULT NULL,
  `recordedBy` varchar(255) DEFAULT NULL COMMENT 'Collector(s)',
  `recordNumberPrefix` varchar(45) DEFAULT NULL,
  `recordNumberSuffix` varchar(45) DEFAULT NULL,
  `recordNumber` varchar(32) DEFAULT NULL COMMENT 'Collector Number',
  `CollectorFamilyName` varchar(255) DEFAULT NULL COMMENT 'not DwC',
  `CollectorInitials` varchar(255) DEFAULT NULL COMMENT 'not DwC',
  `associatedCollectors` varchar(255) DEFAULT NULL COMMENT 'not DwC',
  `eventDate` date DEFAULT NULL,
  `year` int(10) DEFAULT NULL,
  `month` int(10) DEFAULT NULL,
  `day` int(10) DEFAULT NULL,
  `startDayOfYear` int(10) DEFAULT NULL,
  `endDayOfYear` int(10) DEFAULT NULL,
  `LatestDateCollected` date DEFAULT NULL,
  `verbatimEventDate` varchar(255) DEFAULT NULL,
  `habitat` text COMMENT 'Habitat, substrait, etc',
  `substrate` varchar(500) DEFAULT NULL,
  `fieldNotes` text,
  `fieldnumber` varchar(45) DEFAULT NULL,
  `occurrenceRemarks` text COMMENT 'General Notes',
  `informationWithheld` varchar(250) DEFAULT NULL,
  `dataGeneralizations` varchar(250) DEFAULT NULL,
  `associatedOccurrences` text,
  `associatedMedia` text,
  `associatedReferences` text,
  `associatedSequences` text,
  `associatedTaxa` text COMMENT 'Associated Species',
  `dynamicProperties` text COMMENT 'Plant Description?',
  `verbatimAttributes` text,
  `behavior` varchar(500) DEFAULT NULL,
  `attributes` text,
  `reproductiveCondition` varchar(255) DEFAULT NULL COMMENT 'Phenology: flowers, fruit, sterile',
  `cultivationStatus` int(10) DEFAULT NULL COMMENT '0 = wild, 1 = cultivated',
  `establishmentMeans` varchar(32) DEFAULT NULL COMMENT 'cultivated, invasive, escaped from captivity, wild, native',
  `lifeStage` varchar(45) DEFAULT NULL,
  `sex` varchar(45) DEFAULT NULL,
  `individualCount` varchar(45) DEFAULT NULL,
  `samplingProtocol` varchar(100) DEFAULT NULL,
  `samplingEffort` varchar(200) DEFAULT NULL,
  `preparations` varchar(100) DEFAULT NULL,
  `country` varchar(64) DEFAULT NULL,
  `stateProvince` varchar(255) DEFAULT NULL,
  `county` varchar(255) DEFAULT NULL,
  `municipality` varchar(255) DEFAULT NULL,
  `locality` text,
  `localitySecurity` int(10) DEFAULT '0' COMMENT '0 = display locality, 1 = hide locality',
  `localitySecurityReason` varchar(100) DEFAULT NULL,
  `decimalLatitude` double DEFAULT NULL,
  `decimalLongitude` double DEFAULT NULL,
  `geodeticDatum` varchar(255) DEFAULT NULL,
  `coordinateUncertaintyInMeters` int(10) unsigned DEFAULT NULL,
  `footprintWKT` text,
  `coordinatePrecision` decimal(9,7) DEFAULT NULL,
  `locationRemarks` text,
  `verbatimCoordinates` varchar(255) DEFAULT NULL,
  `verbatimCoordinateSystem` varchar(255) DEFAULT NULL,
  `latDeg` int(11) DEFAULT NULL,
  `latMin` double DEFAULT NULL,
  `latSec` double DEFAULT NULL,
  `latNS` varchar(3) DEFAULT NULL,
  `lngDeg` int(11) DEFAULT NULL,
  `lngMin` double DEFAULT NULL,
  `lngSec` double DEFAULT NULL,
  `lngEW` varchar(3) DEFAULT NULL,
  `verbatimLatitude` varchar(45) DEFAULT NULL,
  `verbatimLongitude` varchar(45) DEFAULT NULL,
  `UtmNorthing` varchar(45) DEFAULT NULL,
  `UtmEasting` varchar(45) DEFAULT NULL,
  `UtmZoning` varchar(45) DEFAULT NULL,
  `trsTownship` varchar(45) DEFAULT NULL,
  `trsRange` varchar(45) DEFAULT NULL,
  `trsSection` varchar(45) DEFAULT NULL,
  `trsSectionDetails` varchar(45) DEFAULT NULL,
  `georeferencedBy` varchar(255) DEFAULT NULL,
  `georeferenceProtocol` varchar(255) DEFAULT NULL,
  `georeferenceSources` varchar(255) DEFAULT NULL,
  `georeferenceVerificationStatus` varchar(32) DEFAULT NULL,
  `georeferenceRemarks` varchar(255) DEFAULT NULL,
  `minimumElevationInMeters` int(6) DEFAULT NULL,
  `maximumElevationInMeters` int(6) DEFAULT NULL,
  `elevationNumber` varchar(45) DEFAULT NULL,
  `elevationUnits` varchar(45) DEFAULT NULL,
  `verbatimElevation` varchar(255) DEFAULT NULL,
  `minimumDepthInMeters` int(11) DEFAULT NULL,
  `maximumDepthInMeters` int(11) DEFAULT NULL,
  `verbatimDepth` varchar(50) DEFAULT NULL,
  `previousIdentifications` text,
  `disposition` varchar(32) DEFAULT NULL COMMENT 'Dups to',
  `storageLocation` varchar(100) DEFAULT NULL,
  `genericcolumn1` varchar(100) DEFAULT NULL,
  `genericcolumn2` varchar(100) DEFAULT NULL,
  `modified` datetime DEFAULT NULL COMMENT 'DateLastModified',
  `language` varchar(20) DEFAULT NULL,
  `recordEnteredBy` varchar(250) DEFAULT NULL,
  `duplicateQuantity` int(10) unsigned DEFAULT NULL,
  `labelProject` varchar(45) DEFAULT NULL,
  `processingStatus` varchar(45) DEFAULT NULL,
  `tempfield01` text,
  `tempfield02` text,
  `tempfield03` text,
  `tempfield04` text,
  `tempfield05` text,
  `tempfield06` text,
  `tempfield07` text,
  `tempfield08` text,
  `tempfield09` text,
  `tempfield10` text,
  `tempfield11` text,
  `tempfield12` text,
  `tempfield13` text,
  `tempfield14` text,
  `tempfield15` text,
  `initialTimestamp` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `FK_uploadspectemp_coll` (`collid`),
  KEY `Index_uploadspectemp_occid` (`occid`),
  KEY `Index_uploadspectemp_dbpk` (`dbpk`),
  KEY `Index_uploadspec_sciname` (`sciname`),
  KEY `Index_uploadspec_catalognumber` (`catalogNumber`),
  CONSTRAINT `FK_uploadspectemp_coll` FOREIGN KEY (`collid`) REFERENCES `omcollections` (`CollID`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `uploadtaxa`
--

DROP TABLE IF EXISTS `uploadtaxa`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `uploadtaxa` (
  `TID` int(10) unsigned DEFAULT NULL,
  `SourceId` int(10) unsigned DEFAULT NULL,
  `KingdomID` tinyint(3) unsigned DEFAULT '3',
  `Family` varchar(50) DEFAULT NULL,
  `RankId` smallint(5) DEFAULT NULL,
  `scinameinput` varchar(250) NOT NULL,
  `SciName` varchar(250) DEFAULT NULL,
  `UnitInd1` varchar(1) DEFAULT NULL,
  `UnitName1` varchar(50) DEFAULT NULL,
  `UnitInd2` varchar(1) DEFAULT NULL,
  `UnitName2` varchar(50) DEFAULT NULL,
  `UnitInd3` varchar(7) DEFAULT NULL,
  `UnitName3` varchar(35) DEFAULT NULL,
  `Author` varchar(100) DEFAULT NULL,
  `InfraAuthor` varchar(100) DEFAULT NULL,
  `Acceptance` int(10) unsigned DEFAULT '1' COMMENT '0 = not accepted; 1 = accepted',
  `TidAccepted` int(10) unsigned DEFAULT NULL,
  `AcceptedStr` varchar(250) DEFAULT NULL,
  `SourceAcceptedId` int(10) unsigned DEFAULT NULL,
  `UnacceptabilityReason` varchar(24) DEFAULT NULL,
  `ParentTid` int(10) DEFAULT NULL,
  `ParentStr` varchar(250) DEFAULT NULL,
  `SourceParentId` int(10) unsigned DEFAULT NULL,
  `SecurityStatus` int(10) unsigned NOT NULL DEFAULT '0' COMMENT '0 = no security; 1 = hidden locality',
  `Source` varchar(250) DEFAULT NULL,
  `Notes` varchar(250) DEFAULT NULL,
  `vernacular` varchar(250) DEFAULT NULL,
  `vernlang` varchar(15) DEFAULT NULL,
  `Hybrid` varchar(50) DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY `sourceID_index` (`SourceId`),
  KEY `sourceAcceptedId_index` (`SourceAcceptedId`),
  KEY `sciname_index` (`SciName`),
  KEY `scinameinput_index` (`scinameinput`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `userlogin`
--

DROP TABLE IF EXISTS `userlogin`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `userlogin` (
  `uid` int(10) unsigned NOT NULL,
  `username` varchar(45) NOT NULL,
  `password` varchar(45) NOT NULL,
  `alias` varchar(45) DEFAULT NULL,
  `lastlogindate` datetime DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`username`) USING BTREE,
  UNIQUE KEY `Index_userlogin_unique` (`alias`),
  KEY `FK_login_user` (`uid`),
  CONSTRAINT `FK_login_user` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `userpermissions`
--

DROP TABLE IF EXISTS `userpermissions`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `userpermissions` (
  `uid` int(10) unsigned NOT NULL,
  `pname` varchar(45) NOT NULL COMMENT 'SuperAdmin, TaxonProfile, IdentKey, RareSpecies, coll-1, cl-1, proj-1',
  `assignedby` varchar(45) DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`uid`,`pname`),
  CONSTRAINT `FK_userpermissions_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `userroles`
--

DROP TABLE IF EXISTS `userroles`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `userroles` (
  `userroleid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `role` varchar(45) NOT NULL,
  `tablename` varchar(45) DEFAULT NULL,
  `tablepk` int(11) DEFAULT NULL,
  `secondaryVariable` varchar(45) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `uidassignedby` int(10) unsigned DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`userroleid`),
  KEY `FK_userroles_uid_idx` (`uid`),
  KEY `FK_usrroles_uid2_idx` (`uidassignedby`),
  KEY `Index_userroles_table` (`tablename`,`tablepk`),
  CONSTRAINT `FK_userrole_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_userrole_uid_assigned` FOREIGN KEY (`uidassignedby`) REFERENCES `users` (`uid`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `users`
--

DROP TABLE IF EXISTS `users`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `users` (
  `uid` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `firstname` varchar(45) DEFAULT NULL,
  `lastname` varchar(45) NOT NULL,
  `title` varchar(150) DEFAULT NULL,
  `institution` varchar(200) DEFAULT NULL,
  `department` varchar(200) DEFAULT NULL,
  `address` varchar(255) DEFAULT NULL,
  `city` varchar(100) DEFAULT NULL,
  `state` varchar(50) DEFAULT NULL,
  `zip` varchar(15) DEFAULT NULL,
  `country` varchar(50) DEFAULT NULL,
  `phone` varchar(45) DEFAULT NULL,
  `email` varchar(100) NOT NULL,
  `RegionOfInterest` varchar(45) DEFAULT NULL,
  `url` varchar(400) DEFAULT NULL,
  `Biography` varchar(1500) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  `ispublic` int(10) unsigned NOT NULL DEFAULT '0',
  `defaultrights` varchar(250) DEFAULT NULL,
  `rightsholder` varchar(250) DEFAULT NULL,
  `rights` varchar(250) DEFAULT NULL,
  `accessrights` varchar(250) DEFAULT NULL,
  `validated` varchar(45) NOT NULL DEFAULT '0',
  `usergroups` varchar(100) DEFAULT NULL,
  `InitialTimeStamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`uid`),
  UNIQUE KEY `Index_email` (`email`,`lastname`) USING BTREE
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Table structure for table `usertaxonomy`
--

DROP TABLE IF EXISTS `usertaxonomy`;
/*!40101 SET @saved_cs_client     = @@character_set_client */;
/*!40101 SET character_set_client = utf8 */;
CREATE TABLE `usertaxonomy` (
  `idusertaxonomy` int(11) NOT NULL AUTO_INCREMENT,
  `uid` int(10) unsigned NOT NULL,
  `tid` int(10) unsigned NOT NULL,
  `taxauthid` int(10) unsigned NOT NULL DEFAULT '1',
  `editorstatus` varchar(45) DEFAULT NULL,
  `geographicScope` varchar(250) DEFAULT NULL,
  `notes` varchar(250) DEFAULT NULL,
  `modifiedUid` int(10) unsigned NOT NULL,
  `modifiedtimestamp` datetime DEFAULT NULL,
  `initialtimestamp` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`idusertaxonomy`),
  UNIQUE KEY `usertaxonomy_UNIQUE` (`uid`,`tid`,`taxauthid`,`editorstatus`),
  KEY `FK_usertaxonomy_uid_idx` (`uid`),
  KEY `FK_usertaxonomy_tid_idx` (`tid`),
  KEY `FK_usertaxonomy_taxauthid_idx` (`taxauthid`),
  CONSTRAINT `FK_usertaxonomy_taxauthid` FOREIGN KEY (`taxauthid`) REFERENCES `taxauthority` (`taxauthid`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_usertaxonomy_tid` FOREIGN KEY (`tid`) REFERENCES `taxa` (`TID`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_usertaxonomy_uid` FOREIGN KEY (`uid`) REFERENCES `users` (`uid`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
/*!40101 SET character_set_client = @saved_cs_client */;

--
-- Dumping routines for database 'symbtest'
--
/*!40103 SET TIME_ZONE=@OLD_TIME_ZONE */;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;

-- Dump completed on 2015-02-19 22:35:51
