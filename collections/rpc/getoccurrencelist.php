<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/list.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceListManager.php');
include_once($SERVER_ROOT.'/classes/SOLRManager.php');

$stArrCollJson = isset($_REQUEST["jsoncollstarr"])?$_REQUEST["jsoncollstarr"]:'';
$stArrSearchJson = isset($_REQUEST["starr"])?$_REQUEST["starr"]:'';
$targetTid = $_REQUEST["targettid"];
$pageNumber = $_REQUEST["page"];
$cntPerPage = 100;


$stArrSearchJson = str_replace("%apos;","'",$stArrSearchJson);
$collStArr = json_decode($stArrCollJson, true);
$stArr= json_decode($stArrSearchJson, true);
if($collStArr && $stArr) $stArr = array_merge($stArr,$collStArr);
if($collStArr && !$stArr) $stArr = $collStArr;

$collManager = null;
$occurArr = array();
if(isset($SOLR_MODE) && $SOLR_MODE){
	$collManager = new SOLRManager();
	$collManager->setSearchTermsArr($stArr);
	$solrArr = $collManager->getRecordArr($pageNumber,$cntPerPage);
	$occurArr = $collManager->translateSOLRRecList($solrArr);
}
else{
	$collManager = new OccurrenceListManager(false);
	$collManager->setSearchTermsArr($stArr);
	//$collManager->getSqlWhere();
	$occurArr = $collManager->getRecordArr($pageNumber,$cntPerPage);
}

//Add search details
$htmlStr = '<div style="float:right;">';
$htmlStr .= '<div class="button" style="margin:15px 15px 0px 0px;width:13px;height:13px;" title="'.$LANG['DOWNLOAD_SPECIMEN_DATA'].'">';
$dlLink = 'download/index.php?dltype=specimen&starr='.$stArrSearchJson.'&jsoncollstarr='.$stArrCollJson;
$htmlStr .= "<a href='".$dlLink."'>";
$htmlStr .= '<img src="../images/dl.png"></a></div>';
$targetClid = $collManager->getSearchTerm("targetclid");
if($collManager->getClName() && $targetTid){
	$htmlStr .= '<div style="cursor:pointer;margin:8px 8px 0px 0px;" onclick="addAllVouchersToCl('.$targetTid.')" title="Link All Vouchers on Page">';
	$htmlStr .= '<img src="../images/voucheradd.png" style="border:solid 1px gray;height:13px;margin-right:5px;" /></div>';
}
$htmlStr .= '</div><div style="margin:5px;">';
$htmlStr .= '<div><b>'.$LANG['DATASET'].':</b> '.$collManager->getDatasetSearchStr().'</div>';
if($taxaSearchStr = $collManager->getTaxaSearchStr()){
	$htmlStr .= '<div><b>'.$LANG['TAXA'].':</b> '.$taxaSearchStr.'</div>';
}
if($localSearchStr = $collManager->getLocalSearchStr()){
	$htmlStr .= '<div><b>'.$LANG['SEARCH_CRITERIA'].':</b> '.$localSearchStr.'</div>';
}
$htmlStr .= '<textarea id="urlPrefixBox" style="position:absolute;left:-9999px;top:-9999px">';
$htmlStr .= (isset($_SERVER['HTTPS'])?'https://':'http://').$_SERVER['HTTP_HOST'].$CLIENT_ROOT.'/collections/list.php';
$htmlStr .= $collManager->getSearchResultUrl().'</textarea>';
$htmlStr .= '<textarea id="urlFullBox" style="position:absolute;left:-9999px;top:-9999px"></textarea>';
$htmlStr .= '</div>';
$htmlStr .= '<div style="clear:both;">';
$htmlStr .= '<div style="margin:5px;float:right;"><button type="button" id="copyurl" onclick="copySearchUrl();">Copy URL to These Results</button></div>';
$htmlStr .= '<div style="margin:5px;float:left;">';
$tableLink = 'listtabledisplay.php?starr='.$stArrSearchJson.'&jsoncollstarr='.$stArrCollJson.($targetTid?'&targettid='.$targetTid:'');
$htmlStr .= "<a href='".$tableLink."'>See Results in Table View</a>";
$htmlStr .= '</div></div>';
$htmlStr .= '<div style="clear:both;"></div>';

//Add pagination
$paginationStr = '<div><div style="clear:both;"><hr/></div><div style="float:left;margin:5px;">';
$lastPage = (int)($collManager->getRecordCnt() / $cntPerPage) + 1;
$startPage = ($pageNumber > 4?$pageNumber - 4:1);
$endPage = ($lastPage > $startPage + 9?$startPage + 9:$lastPage);
$pageBar = '';
if($startPage > 1){
	$pageBar .= "<span class='pagination' style='margin-right:5px;'><a href='' onclick='setOccurrenceList(1);return false;'>".$LANG['PAGINATION_FIRST'].'</a></span>';
	$pageBar .= "<span class='pagination' style='margin-right:5px;'><a href='' onclick='setOccurrenceList(".(($pageNumber - 10) < 1?1:$pageNumber - 10).");return false;'>&lt;&lt;</a></span>";
}
for($x = $startPage; $x <= $endPage; $x++){
	if($pageNumber != $x){
		$pageBar .= "<span class='pagination' style='margin-right:3px;margin-right:3px;'><a href='' onclick='setOccurrenceList(".$x.");return false;'>".$x."</a></span>";
	}
	else{
		$pageBar .= "<span class='pagination' style='margin-right:3px;margin-right:3px;font-weight:bold;'>".$x."</span>";
	}
}
if(($lastPage - $startPage) >= 10){
	$pageBar .= "<span class='pagination' style='margin-left:5px;'><a href='' onclick='setOccurrenceList(".(($pageNumber + 10) > $lastPage?$lastPage:($pageNumber + 10)).");return false;'>&gt;&gt;</a></span>";
	$pageBar .= "<span class='pagination' style='margin-left:5px;'><a href='' onclick='setOccurrenceList(".$lastPage.");return false;'>Last</a></span>";
}
$pageBar .= '</div><div style="float:right;margin:5px;">';
$beginNum = ($pageNumber - 1)*$cntPerPage + 1;
$endNum = $beginNum + $cntPerPage - 1;
if($endNum > $collManager->getRecordCnt()) $endNum = $collManager->getRecordCnt();
$pageBar .= $LANG['PAGINATION_PAGE'].' '.$pageNumber.', '.$LANG['PAGINATION_RECORDS'].' '.$beginNum.'-'.$endNum.' '.$LANG['PAGINATION_OF'].' '.$collManager->getRecordCnt();
$paginationStr .= $pageBar;
$paginationStr .= '</div><div style="clear:both;"><hr/></div></div>';
$htmlStr .= $paginationStr;

//Add search return
if($occurArr){
	$htmlStr .= '<table id="omlisttable">';
	$prevCollid = 0;
	$specOccArr = Array();
	foreach($occurArr as $occid => $fieldArr){
		$collId = $fieldArr["collid"];
		$specOccArr[] = $occid;
		if($collId != $prevCollid){
			$prevCollid = $collId;
			$isEditor = false;
			if($SYMB_UID && ($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollAdmin'])) || (array_key_exists('CollEditor',$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollEditor'])))){
				$isEditor = true;
			}
			$instCode = $fieldArr["institutioncode"];
			if($fieldArr["collectioncode"]) $instCode .= ":".$fieldArr["collectioncode"];
			$htmlStr .= '<tr><td colspan="2"><h2>';
			$htmlStr .= '<a href="misc/collprofiles.php?collid='.$collId.'">'.$fieldArr["collectionname"].'</a>';
			$htmlStr .= '</h2><hr /></td></tr>';
		}
		$htmlStr .= '<tr><td width="60" valign="top" align="center">';
		$htmlStr .= '<a href="misc/collprofiles.php?collid='.$collId.'&acronym='.$fieldArr["institutioncode"].'">';
		if($fieldArr["collicon"]){
			$icon = (substr($fieldArr["collicon"],0,6)=='images'?'../':'').$fieldArr["collicon"];
			$htmlStr .= '<img align="bottom" src="'.$icon.'" style="width:35px;border:0px;" />';
		}
		$htmlStr .= '</a>';
		$htmlStr .= '<div style="font-weight:bold;font-size:75%;">';
		$htmlStr .= $instCode;
		$htmlStr .= '</div></td><td>';
		if($isEditor || ($SYMB_UID && $SYMB_UID == $fieldArr['observeruid'])){
			$htmlStr .= '<div style="float:right;" title="'.$LANG['OCCUR_EDIT_TITLE'].'">';
			$htmlStr .= '<a href="editor/occurrenceeditor.php?occid='.$occid.'" target="_blank">';
			$htmlStr .= '<img src="../images/edit.png" style="border:solid 1px gray;height:13px;" /></a></div>';
		}
		if($collManager->getClName() && $targetTid){
			$htmlStr .= '<div style="float:right;" >';
			$htmlStr .= '<a href="#" onclick="addVoucherToCl('.$occid.','.$targetClid.','.$targetTid.')" title="'.$LANG['VOUCHER_LINK_TITLE'].' '.$collManager->getClName().';return false;">';
			$htmlStr .= '<img src="../images/voucheradd.png" style="border:solid 1px gray;height:13px;margin-right:5px;" /></a></div>';
		}
		if(isset($fieldArr['img'])){
			$htmlStr .= '<div style="float:right;margin:5px 25px;">';
			$htmlStr .= '<a href="#" onclick="return openIndPU('.$occid.','.($targetClid?$targetClid:"0").');">';
			$htmlStr .= '<img src="'.$fieldArr['img'].'" style="height:70px" /></a></div>';
		}
		$htmlStr .= '<div style="margin:4px;">';
		$htmlStr .= '<a target="_blank" href="../taxa/index.php?taxon='.$fieldArr["sciname"].'">';
		$htmlStr .= '<span style="font-style:italic;">'.$fieldArr["sciname"].'</span></a> '.$fieldArr["author"].'</div>';
		$htmlStr .= '<div style="margin:4px">';
		$htmlStr .= '<span style="width:150px;">'.$fieldArr["accession"].'</span>';
		$htmlStr .= '<span style="width:200px;margin-left:30px;">'.$fieldArr["collector"].'&nbsp;&nbsp;&nbsp;'.(isset($fieldArr["collnumber"])?$fieldArr["collnumber"]:'').'</span>';
		if(isset($fieldArr["date"])) $htmlStr .= '<span style="margin-left:30px;">'.$fieldArr["date"].'</span>';
		$htmlStr .= '</div><div style="margin:4px">';
		$localStr = "";
		if($fieldArr["country"]) $localStr .= $fieldArr["country"].", ";
		if($fieldArr["state"]) $localStr .= $fieldArr["state"].", ";
		if($fieldArr["county"]) $localStr .= $fieldArr["county"].", ";
		if($fieldArr["locality"]) $localStr .= $fieldArr["locality"].", ";
        if(isset($fieldArr["assochost"]) && $fieldArr["assochost"]) $localStr .= $fieldArr["assochost"].", ";
		if(isset($fieldArr["elev"]) && $fieldArr["elev"]) $localStr .= $fieldArr["elev"].'m';
		if(strlen($localStr) > 2) $localStr = trim($localStr,' ,');
		$htmlStr .= $localStr;
		$htmlStr .= '</div><div style="margin:4px">';
		$htmlStr .= '<b><a href="#" onclick="return openIndPU('.$occid.','.($targetClid?$targetClid:"0").');">'.$LANG['FULL_DETAILS'].'</a></b>';
		$htmlStr .= '</div></td></tr><tr><td colspan="2"><hr/></td></tr>';
	}
	$specOccJson = json_encode($specOccArr);
	$htmlStr .= "<input id='specoccjson' type='hidden' value='".$specOccJson."' />";
	$htmlStr .= '</table>'.$paginationStr.'<hr/>';
}
else{
	$htmlStr .= '<div><h3>'.$LANG['NO_RESULTS'].'</h3>';
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
		$htmlStr .= '<div style="margin: 40px 0px 200px 20px;font-weight:bold;">';
		$htmlStr .= $LANG['PERHAPS_LOOKING_FOR'].' ';
		foreach($closeArr as $v){
			$htmlStr .= '<a href="harvestparams.php?taxa='.$v.'">'.$v.'</a>, ';
		}
		$htmlStr = substr($htmlStr,0,-2);
		$htmlStr .= '</div>';
	}
	$htmlStr .= '</div>';
}

echo $htmlStr;
?>