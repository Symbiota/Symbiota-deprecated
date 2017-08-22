<?php
include_once('../../../config/symbini.php');
include_once('../../../config/dbconnection.php');
include_once($serverRoot.'/classes/MapInterfaceManager.php');
header("Content-Type: text/html; charset=".$charset);

$con = MySQLiConnectionFactory::getCon("readonly");

$occid = array_key_exists("selected",$_REQUEST)?$_REQUEST["selected"]:'';

function cleanOutStr($str){
	$newStr = str_replace('"',"&quot;",$str);
	$newStr = str_replace("'","&apos;",$newStr);
	return $newStr;
}

$sql = 'SELECT o.occid, c.institutioncode, o.catalognumber, CONCAT_WS(" ",o.recordedby,o.recordnumber) AS collector, '.
	'o.eventdate, o.family, o.sciname, CONCAT_WS("; ",o.country, o.stateProvince, o.county) AS locality, o.DecimalLatitude, o.DecimalLongitude, '.
	'IFNULL(o.LocalitySecurity,0) AS LocalitySecurity, o.localitysecurityreason '.
	'FROM omoccurrences o INNER JOIN omcollections c ON o.collid = c.collid ';
$sql .= 'WHERE o.occid = '.$occid.' ';
$sql .= "ORDER BY c.sortseq, c.collectionname ";
$result = $con->query($sql);
while($r = $result->fetch_object()){
	$occId = $r->occid;
	$i = cleanOutStr($r->institutioncode);
	$cat = cleanOutStr($r->catalognumber);
	$c = cleanOutStr($r->collector);
	$e = cleanOutStr($r->eventdate);
	$s = cleanOutStr($r->sciname);
	$l = cleanOutStr($r->locality);
	$lat = cleanOutStr($r->DecimalLatitude);
	$lon = cleanOutStr($r->DecimalLongitude);
}
$result->close();
		
$selectionListHtml = '';
$selectionListHtml .= '<tr id="sel'.$occid.'" >';
$selectionListHtml .= '<td>';
$selectionListHtml .= '<input data-role="none" id="selch'.$occId.'" type="checkbox" name="occid[]" value="'.$occId.'" onchange="findUncheckedSelections(this);" checked />';
$selectionListHtml .= '</td>';
$selectionListHtml .= '<td id="selcat'.$occId.'" >';
$selectionListHtml .= wordwrap($cat, 7, "<br />\n", true);
$selectionListHtml .= '</td>';
$selectionListHtml .= '<td id="sellabel'.$occId.'" >';
$onMouseOver = "openOccidInfoBox('".$c."',".$lat.",".$lon.");";
$selectionListHtml .= '<a href="#" onmouseover="'.$onMouseOver.'" onmouseout="closeOccidInfoBox();" onclick="openIndPopup('.$occId.'); return false;">';
$selectionListHtml .= wordwrap($c, 12, "<br />\n", true);
$selectionListHtml .= '</a>';
$selectionListHtml .= '</td>';
$selectionListHtml .= '<td id="sele'.$occId.'" >';
$selectionListHtml .= wordwrap($e, 10, "<br />\n", true);
$selectionListHtml .= '</td>';
$selectionListHtml .= '<td id="sels'.$occId.'" >';
$selectionListHtml .= wordwrap($s, 12, "<br />\n", true);
$selectionListHtml .= '</td>';
$selectionListHtml .= '</tr>';

//output the response
echo $selectionListHtml;
?>
