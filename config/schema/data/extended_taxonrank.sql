INSERT IGNORE INTO `taxonunits`(kingdomid,rankid,rankname, dirparentrankid, reqparentrankid) 
  VALUES (0,10,'Kingdom',10,10),(0,20,'Subkingdom',10,10),(0,30,'Division',20,10),(0,40,'Subdivision',30,30),(0,50,'Superclass',40,30),(0,60,'Class',50,30),(0,70,'Subclass',60,60),(0,100,'Order',70,60),(0,110,'Suborder',100,100),(0,140,'Family',110,100),(0,150,'Subfamily',140,140),(0,160,'Tribe',150,140),(0,170,'Subtribe',160,140),(0,180,'Genus',170,140),(0,190,'Subgenus',180,180),(0,200,'Section',190,180),(0,210,'Subsection',200,180),(0,220,'Species',210,180),(0,230,'Subspecies',220,180),(0,240,'Variety',220,180),(0,250,'Subvariety',240,180),(0,260,'Form',220,180),(0,270,'Subform',260,180),(0,300,'Cultivated',220,220);
INSERT IGNORE INTO `taxonunits`(kingdomid,rankid,rankname, dirparentrankid, reqparentrankid) 
  VALUES (1,10,'Kingdom',10,10),(1,20,'Subkingdom',10,10),(1,30,'Phylum',20,10),(1,40,'Subphylum',30,30),(1,60,'Class',50,30),(1,70,'Subclass',60,60),(1,100,'Order',70,60),(1,110,'Suborder',100,100),(1,140,'Family',110,100),(1,150,'Subfamily',140,140),(1,160,'Tribe',150,140),(1,170,'Subtribe',160,140),(1,180,'Genus',170,140),(1,190,'Subgenus',180,180),(1,220,'Species',210,180),(1,230,'Subspecies',220,180),(1,240,'Morph',220,180);
INSERT IGNORE INTO `taxonunits`(kingdomid,rankid,rankname, dirparentrankid, reqparentrankid) 
  VALUES (2,10,'Kingdom',10,10),(2,20,'Subkingdom',10,10),(2,30,'Phylum',20,10),(2,40,'Subphylum',30,30),(2,60,'Class',50,30),(2,70,'Subclass',60,60),(2,100,'Order',70,60),(2,110,'Suborder',100,100),(2,140,'Family',110,100),(2,150,'Subfamily',140,140),(2,160,'Tribe',150,140),(2,170,'Subtribe',160,140),(2,180,'Genus',170,140),(2,190,'Subgenus',180,180),(2,220,'Species',210,180),(2,230,'Subspecies',220,180),(2,240,'Morph',220,180);
INSERT IGNORE INTO `taxonunits`(kingdomid,rankid,rankname, dirparentrankid, reqparentrankid) 
  VALUES (3,10,'Kingdom',10,10),(3,20,'Subkingdom',10,10),(3,30,'Division',20,10),(3,40,'Subdivision',30,30),(3,50,'Superclass',40,30),(3,60,'Class',50,30),(3,70,'Subclass',60,60),(3,100,'Order',70,60),(3,110,'Suborder',100,100),(3,140,'Family',110,100),(3,150,'Subfamily',140,140),(3,160,'Tribe',150,140),(3,170,'Subtribe',160,140),(3,180,'Genus',170,140),(3,190,'Subgenus',180,180),(3,200,'Section',190,180),(3,210,'Subsection',200,180),(3,220,'Species',210,180),(3,230,'Subspecies',220,180),(3,240,'Variety',220,180),(3,250,'Subvariety',240,180),(3,260,'Form',220,180),(3,270,'Subform',260,180),(3,300,'Cultivated',220,220);
INSERT IGNORE INTO `taxonunits`(kingdomid,rankid,rankname, dirparentrankid, reqparentrankid) 
  VALUES (4,10,'Kingdom',10,10),(4,20,'Subkingdom',10,10),(4,30,'Division',20,10),(4,40,'Subdivision',30,30),(4,50,'Superclass',40,30),(4,60,'Class',50,30),(4,70,'Subclass',60,60),(4,100,'Order',70,60),(4,110,'Suborder',100,100),(4,140,'Family',110,100),(4,150,'Subfamily',140,140),(4,160,'Tribe',150,140),(4,170,'Subtribe',160,140),(4,180,'Genus',170,140),(4,190,'Subgenus',180,180),(4,200,'Section',190,180),(4,210,'Subsection',200,180),(4,220,'Species',210,180),(4,230,'Subspecies',220,180),(4,240,'Variety',220,180),(4,250,'Subvariety',240,180),(4,260,'Form',220,180),(4,270,'Subform',260,180),(4,300,'Cultivated',220,220);
INSERT IGNORE INTO `taxonunits`(kingdomid,rankid,rankname, dirparentrankid, reqparentrankid) 
  VALUES (5,10,'Kingdom',10,10),(5,20,'Subkingdom',10,10),(5,30,'Phylum',20,10),(5,40,'Subphylum',30,30),(5,60,'Class',50,30),(5,70,'Subclass',60,60),(5,100,'Order',70,60),(5,110,'Suborder',100,100),(5,140,'Family',110,100),(5,150,'Subfamily',140,140),(5,160,'Tribe',150,140),(5,170,'Subtribe',160,140),(5,180,'Genus',170,140),(5,190,'Subgenus',180,180),(5,220,'Species',210,180),(5,230,'Subspecies',220,180),(5,240,'Morph',220,180);

INSERT INTO `taxa` (`TID`, `KingdomID`, `RankId`, `SciName`, `UnitName1`) VALUES ('1', '1', '10', 'Monera', 'Monera');
INSERT INTO `taxa` (`TID`, `KingdomID`, `RankId`, `SciName`, `UnitName1`) VALUES ('2', '2', '10', 'Protista', 'Protista');
INSERT INTO `taxa` (`TID`, `KingdomID`, `RankId`, `SciName`, `UnitName1`) VALUES ('3', '3', '10', 'Plantae', 'Plantae');
INSERT INTO `taxa` (`TID`, `KingdomID`, `RankId`, `SciName`, `UnitName1`) VALUES ('4', '4', '10', 'Fungi', 'Fungi');
INSERT INTO `taxa` (`TID`, `KingdomID`, `RankId`, `SciName`, `UnitName1`) VALUES ('5', '5', '10', 'Animalia', 'Animalia');

INSERT INTO `taxstatus` (`tid`, `tidaccepted`, `taxauthid`, `parenttid`) VALUES ('1', '1', '1', '1');
INSERT INTO `taxstatus` (`tid`, `tidaccepted`, `taxauthid`, `parenttid`) VALUES ('2', '2', '1', '2');
INSERT INTO `taxstatus` (`tid`, `tidaccepted`, `taxauthid`, `parenttid`) VALUES ('3', '3', '1', '3');
INSERT INTO `taxstatus` (`tid`, `tidaccepted`, `taxauthid`, `parenttid`) VALUES ('4', '4', '1', '4');
INSERT INTO `taxstatus` (`tid`, `tidaccepted`, `taxauthid`, `parenttid`) VALUES ('5', '5', '1', '5');

