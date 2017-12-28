<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/config/dbconnection.php');
include_once($SERVER_ROOT.'/content/lang/collections/harvestparams.'.$LANG_TAG.'.php');
$con = MySQLiConnectionFactory::getCon("readonly");
$retArr = Array();
$q = $con->real_escape_string($_REQUEST['term']);

$sql =
// Common Name
"SELECT DISTINCT CONCAT('".$LANG['SELECT_1-5'].": ',v.vernacularname) AS sciname, ".
"                CONCAT('A:'                       ,v.vernacularname) AS snorder, ".
"                GROUP_CONCAT(t.tid SEPARATOR ',')                    AS tid, ".
"                COUNT(t.tid)                                         AS ct ".
"FROM taxavernaculars v ".
"JOIN taxa            t ON (t.tid = v.tid) ".
"JOIN images          i ON (i.tid = t.tid) ".
"WHERE v.vernacularname LIKE '%".$q."%' ".
"GROUP BY v.vernacularname ".

"UNION ".

// Scientific Name
"SELECT          CONCAT('".$LANG['SELECT_1-3'].": ',t.sciname       ) AS sciname, ".
"                CONCAT('B:'                       ,t.sciname       ) AS snorder, ".
"                GROUP_CONCAT(t.tid SEPARATOR ',')                    AS tid, ".
"                COUNT(t.tid)                                         AS ct ".
"FROM taxa            t ".
"JOIN images          i ON (i.tid = t.tid) ".
"WHERE t.sciname LIKE '%".$q."%' ".
"GROUP BY t.sciname ".

"UNION ".

// Family
"SELECT DISTINCT CONCAT('".$LANG['SELECT_1-2'].": ',s.family        ) AS sciname, ".
"                CONCAT('C:'                       ,s.family        ) AS snorder, ".
"                GROUP_CONCAT(t.tid SEPARATOR ',')                    AS tid, ".
"                COUNT(t.tid)                                         AS ct ".
"FROM taxstatus       s ".
"JOIN taxa            t ON (t.tid = s.tid) ".
"JOIN images          i ON (i.tid = t.tid) ".
"WHERE t.rankid < 220 AND s.family LIKE '%".$q."%' ".
"GROUP BY s.family ".

"ORDER BY snorder ";

$sql .= 'LIMIT 30';
$result = $con->query($sql);
if ($result) {
    while ($r = $result->fetch_object()) {
        $label = $r->sciname;
        if ($r->ct > 1) {
            $label .= ' ('.$r->ct.' separate taxa)';
        }
        $retArr[] = (object)array(
            'id' => $r->sciname,
            'value' => $r->tid,
            'label' => $label);
    }
}

$con->close();
echo json_encode($retArr);

?>