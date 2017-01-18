<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/list.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceListManager.php');

$stArrCollJson = $_REQUEST["jsoncollstarr"];
$stArrSearchJson = $_REQUEST["starr"];
$targetTid = $_REQUEST["targettid"];

$stArrSearchJson = str_replace("%apos;","'",$stArrSearchJson);
$collStArr = json_decode($stArrCollJson, true);
$searchStArr = json_decode($stArrSearchJson, true);
$stArr = array_merge($searchStArr,$collStArr);

$collManager = new OccurrenceListManager();

$collManager->setSearchTermsArr($stArr);
$collManager->getSqlWhere();

$urlPrefix = (isset($_SERVER['HTTPS'])?'https://':'http://').$_SERVER['HTTP_HOST'].$CLIENT_ROOT.'/collections/list.php';

$recordListHtml = '';

$recordListHtml .= '<div style="float:right;">';
$recordListHtml .= '<div class="button" style="margin:15px 15px 0px 0px;width:13px;height:13px;" title="'.$LANG['DOWNLOAD_SPECIMEN_DATA'].'">';
$dlLink = 'download/index.php?dltype=specimen&starr='.$stArrSearchJson.'&jsoncollstarr='.$stArrCollJson;
$recordListHtml .= "<a href='".$dlLink."'>";
$recordListHtml .= '<img src="../images/dl.png"></a></div>';
$targetClid = $collManager->getSearchTerm("targetclid");
if($collManager->getClName() && $targetTid){
    $recordListHtml .= '<div style="cursor:pointer;margin:8px 8px 0px 0px;" onclick="addAllVouchersToCl('.$targetTid.')" title="Link All Vouchers on Page">';
    $recordListHtml .= '<img src="../images/voucheradd.png" style="border:solid 1px gray;height:13px;margin-right:5px;" /></div>';
}
$recordListHtml .= '</div><div style="margin:5px;">';
$recordListHtml .= '<div><b>'.$LANG['DATASET'].':</b> '.$collManager->getDatasetSearchStr().'</div>';
if($collManager->getTaxaSearchStr()){
    $recordListHtml .= '<div><b>'.$LANG['TAXA'].':</b> '.$collManager->getTaxaSearchStr().'</div>';
}
if($collManager->getLocalSearchStr()){
    $recordListHtml .= '<div><b>'.$LANG['SEARCH_CRITERIA'].':</b> '.$collManager->getLocalSearchStr().'</div>';
}
$recordListHtml .= '<textarea id="urlPrefixBox" style="position:absolute;left:-9999px;top:-9999px">'.$urlPrefix.$collManager->getSearchResultUrl().'</textarea>';
$recordListHtml .= '<textarea id="urlFullBox" style="position:absolute;left:-9999px;top:-9999px"></textarea>';
$recordListHtml .= '</div>';
$recordListHtml .= '<div style="clear:both;">';
$recordListHtml .= '<div style="margin:5px;float:right;"><button type="button" id="copyurl" onclick="copySearchUrl();">Copy URL to These Results</button></div>';
$recordListHtml .= '<div style="margin:5px;float:left;">';
$tableLink = 'listtabledisplay.php?starr='.$stArrSearchJson.'&jsoncollstarr='.$stArrCollJson.($targetTid?'&targettid='.$targetTid:'');
$recordListHtml .= "<a href='".$tableLink."'>See Results in Table View</a>";
$recordListHtml .= '</div></div>';
$recordListHtml .= '<div style="clear:both;"></div>';

$recordListHtml = utf8_encode($recordListHtml);

//output the response
echo json_encode($recordListHtml);
?>
