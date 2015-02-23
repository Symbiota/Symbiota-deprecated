ALTER TABLE `uploadspectemp` 
 MODIFY COLUMN `UtmNorthing` VARCHAR(45) DEFAULT NULL,
 MODIFY COLUMN `UtmEasting` VARCHAR(45) DEFAULT NULL,
 MODIFY COLUMN `UtmZoning` VARCHAR(45) DEFAULT NULL;

ALTER TABLE `omoccurrences` 
 MODIFY COLUMN `language` VARCHAR(20) DEFAULT NULL;


DROP PROCEDURE IF EXISTS DynamicKey;

DELIMITER //
CREATE PROCEDURE `DynamicChecklist`(IN lat DOUBLE,IN lng DOUBLE,IN radiusUnit DOUBLE,IN tidinput INT,IN uidinput VARCHAR(10))
BEGIN

DECLARE speccnt DOUBLE DEFAULT 0;
DECLARE sppcnt DOUBLE;

DECLARE latradius DOUBLE;
DECLARE lngradius DOUBLE;
DECLARE lat1 DOUBLE;
DECLARE lat2 DOUBLE;
DECLARE lng1 DOUBLE;
DECLARE lng2 DOUBLE;
DECLARE dynpk INT;
DECLARE loopCnt INT DEFAULT 1;
DECLARE radius INT;

#Delete all expired checklists
DELETE l.* 
FROM fmdynamicchecklists cl INNER JOIN fmdyncltaxalink l ON cl.dynclid = l.dynclid
WHERE cl.expiration < NOW();

DELETE IGNORE FROM fmdynamicchecklists WHERE expiration < NOW();

WHILE speccnt < 2500 AND loopCnt < 10 DO
        SET radius = radiusUnit*loopCnt;
        SET latradius = radius / 69.1;
        SET lngradius = cos(lat / 57.3)*(radius / 69.1);
        SET lat1 = lat - latradius;
        SET lat2 = lat + latradius;
        SET lng1 = lng - lngradius;
        SET lng2 = lng + lngradius;

        SELECT count(o.tid) INTO speccnt FROM omoccurgeoindex o
        WHERE (o.DecimalLatitude BETWEEN lat1 AND lat2) AND (o.DecimalLongitude BETWEEN lng1 AND lng2);
        SET loopCnt = loopCnt + 1;
END WHILE;

INSERT INTO fmdynamicchecklists(name,details,expiration,uid)
SELECT CONCAT(lat,", ",lng,"; within ",radius," miles"), CONCAT(lat,", ",lng,"; within ",radius," miles"),DATE_ADD(CURDATE(),INTERVAL 5 DAY),uidinput;

SELECT LAST_INSERT_ID() INTO dynpk;

IF tidinput > 0 THEN
        INSERT INTO fmdyncltaxalink (dynclid, tid)
        SELECT DISTINCT dynpk, IF(t.rankid=220,t.tid,ts2.parenttid) as tid
        FROM ((omoccurgeoindex o INNER JOIN taxstatus ts ON o.tid = ts.tid)
        INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tid)
        INNER JOIN taxa t ON ts2.tid = t.tid
        WHERE (ts2.hierarchystr LIKE CONCAT("%,",tidinput,",%")) AND (t.rankid >= 220)
        AND (ts.taxauthid = 1) AND (ts2.taxauthid = 1) AND (o.DecimalLatitude BETWEEN lat1 AND lat2)
        AND (o.DecimalLongitude BETWEEN lng1 AND lng2);

ELSE
        INSERT INTO fmdyncltaxalink (dynclid, tid)
        SELECT DISTINCT dynpk, IF(t.rankid=220,t.tid,ts2.parenttid) as tid
        FROM ((omoccurgeoindex o INNER JOIN taxstatus ts ON o.tid = ts.tid)
        INNER JOIN taxstatus ts2 ON ts.tidaccepted = ts2.tid)
        INNER JOIN taxa t ON ts2.tid = t.tid
        WHERE (t.rankid >= 220) AND (ts.taxauthid = 1) AND (ts2.taxauthid = 1)
        AND (o.DecimalLatitude BETWEEN lat1 AND lat2) AND (o.DecimalLongitude BETWEEN lng1 AND lng2);
END IF;

SELECT dynpk;

END//

DELIMITER ;
