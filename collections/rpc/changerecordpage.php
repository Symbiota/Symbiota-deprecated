<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/list.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceListManager.php');
include_once($SERVER_ROOT.'/classes/SOLRManager.php');

$stArrCollJson = $_REQUEST["jsoncollstarr"];
$stArrSearchJson = $_REQUEST["starr"];
$targetTid = $_REQUEST["targettid"];
$pageNumber = $_REQUEST["page"];
$cntPerPage = 100;

$stArrSearchJson = str_replace("%apos;","'",$stArrSearchJson);
$collStArr = json_decode($stArrCollJson, true);
$searchStArr = json_decode($stArrSearchJson, true);
if($collStArr && $searchStArr) $stArr = array_merge($searchStArr,$collStArr);
if(!$collStArr && $searchStArr) $stArr = $searchStArr;
if($collStArr && !$searchStArr) $stArr = $collStArr;

if($SOLR_MODE){
    $collManager = new SOLRManager();
    $collManager->setSearchTermsArr($stArr);
    $solrArr = $collManager->getRecordArr($pageNumber,$cntPerPage);
    $specimenArray = $collManager->translateSOLRRecList($solrArr);
}
else{
    $collManager = new OccurrenceListManager();
    $collManager->setSearchTermsArr($stArr);
    $specimenArray = $collManager->getRecordArr($pageNumber,$cntPerPage);
}

$targetClid = $collManager->getSearchTerm("targetclid");

$recordListHtml = '';
$specOccArr = Array();
$specOccJson = '';

$paginationStr = '<div><div style="clear:both;"><hr/></div><div style="float:left;margin:5px;">';
$lastPage = (int)($collManager->getRecordCnt() / $cntPerPage) + 1;
$startPage = ($pageNumber > 4?$pageNumber - 4:1);
$endPage = ($lastPage > $startPage + 9?$startPage + 9:$lastPage);
$pageBar = '';
if($startPage > 1){
    $pageBar .= "<span class='pagination' style='margin-right:5px;'><a href='' onclick='changeRecordPage(1);return false;'>".$LANG['PAGINATION_FIRST'].'</a></span>';
    $pageBar .= "<span class='pagination' style='margin-right:5px;'><a href='' onclick='changeRecordPage(".(($pageNumber - 10) < 1?1:$pageNumber - 10).");return false;'>&lt;&lt;</a></span>";
}
for($x = $startPage; $x <= $endPage; $x++){
    if($pageNumber != $x){
        $pageBar .= "<span class='pagination' style='margin-right:3px;margin-right:3px;'><a href='' onclick='changeRecordPage(".$x.");return false;'>".$x."</a></span>";
    }
    else{
        $pageBar .= "<span class='pagination' style='margin-right:3px;margin-right:3px;font-weight:bold;'>".$x."</span>";
    }
}
if(($lastPage - $startPage) >= 10){
    $pageBar .= "<span class='pagination' style='margin-left:5px;'><a href='' onclick='changeRecordPage(".(($pageNumber + 10) > $lastPage?$lastPage:($pageNumber + 10)).");return false;'>&gt;&gt;</a></span>";
    $pageBar .= "<span class='pagination' style='margin-left:5px;'><a href='' onclick='changeRecordPage(".$lastPage.");return false;'>Last</a></span>";
}
$pageBar .= '</div><div style="float:right;margin:5px;">';
$beginNum = ($pageNumber - 1)*$cntPerPage + 1;
$endNum = $beginNum + $cntPerPage - 1;
if($endNum > $collManager->getRecordCnt()) $endNum = $collManager->getRecordCnt();
$pageBar .= $LANG['PAGINATION_PAGE'].' '.$pageNumber.', '.$LANG['PAGINATION_RECORDS'].' '.$beginNum.'-'.$endNum.' '.$LANG['PAGINATION_OF'].' '.$collManager->getRecordCnt();
$paginationStr .= $pageBar;
$paginationStr .= '</div><div style="clear:both;"><hr/></div></div>';
$recordListHtml .= $paginationStr;

if($specimenArray){
    $recordListHtml .= '<table id="omlisttable">';
	$prevCollid = 0;
	foreach($specimenArray as $occId => $fieldArr){
        $collId = $fieldArr["collid"];
        $specOccArr[] = $occId;
        if($collId != $prevCollid){
            $icon = '';
            $prevCollid = $collId;
            $isEditor = false;
            if($SYMB_UID && ($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollAdmin'])) || (array_key_exists('CollEditor',$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollEditor'])))){
                $isEditor = true;
            }
            $instCode = $fieldArr["institutioncode"];
            if($fieldArr["collectioncode"]) $instCode .= ":".$fieldArr["collectioncode"];
            if($fieldArr["collicon"]) $icon = (substr($fieldArr["collicon"],0,6)=='images'?'../':'').$fieldArr["collicon"];
            $recordListHtml .= '<tr><td colspan="2"><h2>';
            $recordListHtml .= '<a href="misc/collprofiles.php?collid='.$collId.'">'.$fieldArr["collectionname"].'</a>';
            $recordListHtml .= '</h2><hr /></td></tr>';
        }
        $recordListHtml .= '<tr><td width="60" valign="top" align="center">';
        $recordListHtml .= '<a href="misc/collprofiles.php?collid='.$collId.'&acronym='.$fieldArr["institutioncode"].'">';
        if($icon) $recordListHtml .= '<img align="bottom" src="'.$icon.'" style="width:35px;border:0px;" />';
        $recordListHtml .= '</a>';
        $recordListHtml .= '<div style="font-weight:bold;font-size:75%;">';
        $recordListHtml .= $instCode;
        $recordListHtml .= '</div></td><td>';
        if($isEditor || ($SYMB_UID && $SYMB_UID == $fieldArr['observeruid'])){
            $recordListHtml .= '<div style="float:right;" title="'.$LANG['OCCUR_EDIT_TITLE'].'">';
            $recordListHtml .= '<a href="editor/occurrenceeditor.php?occid='.$occId.'" target="_blank">';
            $recordListHtml .= '<img src="../images/edit.png" style="border:solid 1px gray;height:13px;" /></a></div>';
        }
        if($collManager->getClName() && $targetTid){
            $recordListHtml .= '<div style="float:right;" >';
            $recordListHtml .= '<a href="#" onclick="addVoucherToCl('.$occId.','.$targetClid.','.$targetTid.')" title="'.$LANG['VOUCHER_LINK_TITLE'].' '.$collManager->getClName().';return false;">';
            $recordListHtml .= '<img src="../images/voucheradd.png" style="border:solid 1px gray;height:13px;margin-right:5px;" /></a></div>';
        }
        if(isset($fieldArr['img'])){
            $recordListHtml .= '<div style="float:right;margin:5px 25px;">';
            $recordListHtml .= '<a href="#" onclick="return openIndPU('.$occId.','.($targetClid?$targetClid:"0").');">';
            $recordListHtml .= '<img src="'.$fieldArr['img'].'" style="height:70px" /></a></div>';
        }
        $recordListHtml .= '<div style="margin:4px;">';
        $recordListHtml .= '<a target="_blank" href="../taxa/index.php?taxon='.$fieldArr["sciname"].'">';
        $recordListHtml .= '<span style="font-style:italic;">'.$fieldArr["sciname"].'</span></a> '.$fieldArr["author"].'</div>';
        $recordListHtml .= '<div style="margin:4px">';
        $recordListHtml .= '<span style="width:150px;">'.$fieldArr["accession"].'</span>';
        $recordListHtml .= '<span style="width:200px;margin-left:30px;">'.$fieldArr["collector"].'&nbsp;&nbsp;&nbsp;'.(isset($fieldArr["collnumber"])?$fieldArr["collnumber"]:'').'</span>';
        if(isset($fieldArr["date"])) $recordListHtml .= '<span style="margin-left:30px;">'.$fieldArr["date"].'</span>';
        $recordListHtml .= '</div><div style="margin:4px">';
        $localStr = "";
        if($fieldArr["country"]) $localStr .= $fieldArr["country"].", ";
        if($fieldArr["state"]) $localStr .= $fieldArr["state"].", ";
        if($fieldArr["county"]) $localStr .= $fieldArr["county"].", ";
        if($fieldArr["locality"]) $localStr .= $fieldArr["locality"].", ";
        if(isset($fieldArr["elev"]) && $fieldArr["elev"]) $localStr .= $fieldArr["elev"].'m';
        if(strlen($localStr) > 2) $localStr = trim($localStr,' ,');
        $recordListHtml .= $localStr;
        $recordListHtml .= '</div><div style="margin:4px">';
        $recordListHtml .= '<b><a href="#" onclick="return openIndPU('.$occId.','.($targetClid?$targetClid:"0").');">'.$LANG['FULL_DETAILS'].'</a></b>';
        $recordListHtml .= '</div></td></tr><tr><td colspan="2"><hr/></td></tr>';
    }
    $specOccJson = json_encode($specOccArr);
	$recordListHtml .= "<input id='specoccjson' type='hidden' value='".$specOccJson."' />";
    $recordListHtml .= '</table>'.$paginationStr.'<hr/>';
}
else{
    $recordListHtml .= '<div><h3>'.$LANG['NO_RESULTS'].'</h3>';
    $tn = $collManager->getTaxaSearchStr();
    if($p = strpos($tn,';')){
        $tn = substr($tn,0,$p);
    }
    if($p = strpos($tn,'=>')){
        $tn = substr($tn,$p+2);
    }
    if($p = strpos($tn,'(')){
        $tn = substr($tn,0,$p);
    }
    if($closeArr = $collManager->getCloseTaxaMatch(trim($tn))){
        $recordListHtml .= '<div style="margin: 40px 0px 200px 20px;font-weight:bold;">';
        $recordListHtml .= $LANG['PERHAPS_LOOKING_FOR'].' ';
        foreach($closeArr as $v){
            $recordListHtml .= '<a href="harvestparams.php?taxa='.$v.'">'.$v.'</a>, ';
        }
        $recordListHtml = substr($recordListHtml,0,-2);
        $recordListHtml .= '</div>';
    }
    $recordListHtml .= '</div>';
}

//output the response
echo json_encode($recordListHtml);
?>
