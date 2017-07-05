<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/list.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceListManager.php');
include_once($SERVER_ROOT.'/classes/SOLRManager.php');

$stArrCollJson = $_REQUEST["jsoncollstarr"];
$stArrSearchJson = $_REQUEST["starr"];
$targetTid = $_REQUEST["targettid"];
$occIndex = $_REQUEST['occindex'];
$sortField1 = $_REQUEST['sortfield1'];
$sortField2 = $_REQUEST['sortfield2'];
$sortOrder = $_REQUEST['sortorder'];

$stArrSearchJson = str_replace("%apos;","'",$stArrSearchJson);
$collStArr = json_decode($stArrCollJson, true);
$searchStArr = json_decode($stArrSearchJson, true);
if($collStArr && $searchStArr) $stArr = array_merge($searchStArr,$collStArr);
if(!$collStArr && $searchStArr) $stArr = $searchStArr;
if($collStArr && !$searchStArr) $stArr = $collStArr;

if($SOLR_MODE){
    $collManager = new SOLRManager();
    $collManager->setSearchTermsArr($stArr);
    $collManager->setSorting($sortField1,$sortField2,$sortOrder);
    $solrArr = $collManager->getRecordArr($occIndex,1000);
    $recArr = $collManager->translateSOLRRecList($solrArr);
}
else{
    $collManager = new OccurrenceListManager(false);
    $collManager->setSearchTermsArr($stArr);
    $collManager->setSorting($sortField1,$sortField2,$sortOrder);
    $recArr = $collManager->getRecordArr($occIndex,1000);
}

$targetClid = $collManager->getSearchTerm("targetclid");

$urlPrefix = (isset($_SERVER['HTTPS'])?'https://':'http://').$_SERVER['HTTP_HOST'].$CLIENT_ROOT.'/collections/listtabledisplay.php';

$recordListHtml = '';

$qryCnt = $collManager->getRecordCnt();
$navStr = '<div style="float:right;">';
if($occIndex >= 1000){
    $navStr .= "<a href='' title='Previous 1000 records' onclick='changeTablePage(".($occIndex-1000).");return false;'>&lt;&lt;</a>";
}
$navStr .= ' | ';
$navStr .= ($occIndex+1).'-'.($qryCnt<1000+$occIndex?$qryCnt:1000+$occIndex).' of '.$qryCnt.' records';
$navStr .= ' | ';
if($qryCnt > (1000+$occIndex)){
    $navStr .= "<a href='' title='Next 1000 records' onclick='changeTablePage(".($occIndex+1000).");return false;'>&gt;&gt;</a>";
}
$navStr .= '</div>';

if($recArr){
    $recordListHtml .= '<div style="width:790px;clear:both;margin:5px;">';
    $recordListHtml .= '<div style="float:left;"><button type="button" id="copyurl" onclick="copySearchUrl();">Copy URL to These Results</button></div>';
    $recordListHtml .= $navStr;
    $recordListHtml .= '</div>';
    $recordListHtml .= '<div style="clear:both;height:5px;"></div>';
    $recordListHtml .= '<table class="styledtable" style="font-family:Arial;font-size:12px;"><tr>';
    $recordListHtml .= '<th>Symbiota ID</th>';
    $recordListHtml .= '<th>Collection</th>';
    $recordListHtml .= '<th>Catalog Number</th>';
    $recordListHtml .= '<th>Family</th>';
    $recordListHtml .= '<th>Scientific Name</th>';
    $recordListHtml .= '<th>Country</th>';
    $recordListHtml .= '<th>State/Province</th>';
    $recordListHtml .= '<th>County</th>';
    $recordListHtml .= '<th>Locality</th>';
    $recordListHtml .= '<th>Habitat</th>';
    $recordListHtml .= '<th>Elevation</th>';
    $recordListHtml .= '<th>Event Date</th>';
    $recordListHtml .= '<th>Collector</th>';
    $recordListHtml .= '<th>Number</th>';
    $recordListHtml .= '</tr>';
    $recCnt = 0;
    foreach($recArr as $id => $occArr){
        $isEditor = false;
        if($SYMB_UID && ($IS_ADMIN
                || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($occArr['collid'],$USER_RIGHTS['CollAdmin']))
                || (array_key_exists('CollEditor',$USER_RIGHTS) && in_array($occArr['collid'],$USER_RIGHTS['CollEditor'])))){
            $isEditor = true;
        }
        $collection = $occArr['institutioncode'];
        if($occArr['collectioncode']) $collection .= ':'.$occArr['collectioncode'];
        if($occArr['sciname']) $occArr['sciname'] = '<i>'.$occArr['sciname'].'</i> ';
        $recordListHtml .= "<tr ".($recCnt%2?'class="alt"':'').">\n";
        $recordListHtml .= '<td>';
        $recordListHtml .= '<a href="#" onclick="return openIndPU('.$id.",".($targetClid?$targetClid:"0").');">'.$id.'</a> ';
        if($isEditor || ($SYMB_UID && $SYMB_UID == $fieldArr['observeruid'])){
            $recordListHtml .= '<a href="editor/occurrenceeditor.php?occid='.$id.'" target="_blank">';
            $recordListHtml .= '<img src="../images/edit.png" style="height:13px;" title="Edit Record" />';
            $recordListHtml .= '</a>';
        }
        if(isset($occArr['img'])){
            $recordListHtml .= '<img src="../images/image.png" style="height:13px;margin-left:5px;" title="Has Image" />';
        }
        $recordListHtml .= '</td>'."\n";
        $recordListHtml .= '<td>'.$collection.'</td>'."\n";
        $recordListHtml .= '<td>'.$occArr['accession'].'</td>'."\n";
        $recordListHtml .= '<td>'.$occArr['family'].'</td>'."\n";
        $recordListHtml .= '<td>'.$occArr['sciname'].($occArr['author']?" ".$occArr['author']:"").'</td>'."\n";
        $recordListHtml .= '<td>'.$occArr['country'].'</td>'."\n";
        $recordListHtml .= '<td>'.$occArr['state'].'</td>'."\n";
        $recordListHtml .= '<td>'.$occArr['county'].'</td>'."\n";
        $recordListHtml .= '<td>'.((strlen($occArr['locality'])>80)?substr($occArr['locality'],0,80).'...':$occArr['locality']).'</td>'."\n";
        $recordListHtml .= '<td>'.((strlen($occArr['habitat'])>80)?substr($occArr['habitat'],0,80).'...':$occArr['habitat']).'</td>'."\n";
        $recordListHtml .= '<td>'.(array_key_exists("elev",$occArr)?$occArr['elev']:"").'</td>'."\n";
        $recordListHtml .= '<td>'.(array_key_exists("date",$occArr)?$occArr['date']:"").'</td>'."\n";
        $recordListHtml .= '<td>'.$occArr['collector'].'</td>'."\n";
        $recordListHtml .= '<td>'.(array_key_exists("collnumber",$occArr)?$occArr['collnumber']:"").'</td>'."\n";
        $recordListHtml .= "</tr>\n";
        $recCnt++;
    }
    $recordListHtml .= '</table>';
    $recordListHtml .= '<div style="clear:both;height:5px;"></div>';
    $recordListHtml .= '<textarea id="urlPrefixBox" style="position:absolute;left:-9999px;top:-9999px">'.$urlPrefix.$collManager->getSearchResultUrl().'</textarea>';
    $recordListHtml .= '<textarea id="urlFullBox" style="position:absolute;left:-9999px;top:-9999px"></textarea>';
    $recordListHtml .= '<div style="width:790px;">'.$navStr.'</div>';
    $recordListHtml .= '*Click on the Symbiota identifier in the first column to see Full Record Details.';
}
else{
    $recordListHtml .= '<div style="font-weight:bold;font-size:120%;">No records found matching the query</div>';
}
//output the response
echo $recordListHtml;
//echo json_encode($recordListHtml);
?>