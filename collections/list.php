<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/list.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceListManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:1; 
$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:0;
$cntPerPage = array_key_exists("cntperpage",$_REQUEST)?$_REQUEST["cntperpage"]:100;
$stArrCollJson = array_key_exists("jsoncollstarr",$_REQUEST)?$_REQUEST["jsoncollstarr"]:'';
$stArrSearchJson = array_key_exists("starr",$_REQUEST)?$_REQUEST["starr"]:'';

//Sanitation
if(!is_numeric($taxonFilter)) $taxonFilter = 1;
if(!is_numeric($cntPerPage)) $cntPerPage = 100;

$pageNumber = array_key_exists("page",$_REQUEST)?$_REQUEST["page"]:1; 
$collManager = new OccurrenceListManager();
$stArr = array();
$specOccJson = '';

if($stArrCollJson && $stArrSearchJson){
	$stArrSearchJson = str_replace("%apos;","'",$stArrSearchJson);
	$collStArr = json_decode($stArrCollJson, true);
	$searchStArr = json_decode($stArrSearchJson, true);
	$stArr = array_merge($searchStArr,$collStArr);
}
elseif($stArrCollJson && !$stArrSearchJson){
	$collArray = $collManager->getSearchTerms();
	$collStArr = json_decode($stArrCollJson, true);
	$stArr = array_merge($collArray,$collStArr);
	$stArrSearchJson = json_encode($collArray);
}
else{
	$collArray = $collManager->getSearchTerms();
	$collStArr = $collManager->getSearchTerms();
	$stArr = array_merge($collArray,$collStArr);
	$stArrSearchJson = json_encode($collArray);
	$stArrCollJson = json_encode($collArray);
}

$stArrJson = json_encode($stArr);
$collManager->setSearchTermsArr($stArr);
$specimenArray = $collManager->getSpecimenMap($pageNumber, $cntPerPage);			//Array(IID,Array(fieldName,value))
if($specimenArray){
	$specOccArr = array();
	foreach($specimenArray as $collId => $specData){
		foreach($specData as $occId => $fieldArr){
			$specOccArr[] = $occId;
		}
	}
	$specOccJson = json_encode($specOccArr);
}

$occFieldArr = array('occurrenceid','family', 'scientificname', 'sciname', 
	'tidinterpreted', 'scientificnameauthorship', 'identifiedby', 'dateidentified', 'identificationreferences',
	'identificationremarks', 'taxonremarks', 'identificationqualifier', 'typestatus', 'recordedby', 'recordnumber',
	'associatedcollectors', 'eventdate', 'year', 'month', 'day', 'startdayofyear', 'enddayofyear',
	'verbatimeventdate', 'habitat', 'substrate', 'fieldnumber','occurrenceremarks', 'associatedtaxa', 'verbatimattributes',
	'dynamicproperties', 'reproductivecondition', 'cultivationstatus', 'establishmentmeans', 
	'lifestage', 'sex', 'individualcount', 'samplingprotocol', 'preparations',
	'country', 'stateprovince', 'county', 'municipality', 'locality',
	'decimallatitude', 'decimallongitude','geodeticdatum', 'coordinateuncertaintyinmeters', 
	'locationremarks', 'verbatimcoordinates', 'georeferencedby', 'georeferenceprotocol', 'georeferencesources', 
	'georeferenceverificationstatus', 'georeferenceremarks', 'minimumelevationinmeters', 'maximumelevationinmeters',
	'verbatimelevation','language',
	'labelproject','basisofrecord');
?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<title><?php echo $defaultTitle.' '.$LANG['PAGE_TITLE']; ?></title>
	<link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link type="text/css" href="../css/jquery-ui.css" rel="Stylesheet" />
	<style type="text/css">
		.ui-tabs .ui-tabs-nav li { width:32%; }
		.ui-tabs .ui-tabs-nav li a { margin-left:10px;}
	</style>
	
	<script type="text/javascript" src="../js/jquery.js?ver=20130917"></script>
	<script type="text/javascript" src="../js/jquery-ui.js?ver=20130917"></script>
	<script type="text/javascript">
		<?php include_once($SERVER_ROOT.'/config/googleanalytics.php'); ?>
	</script>
	<script type="text/javascript">
		$('html').hide();
		$(document).ready(function() {
			$('html').show();
		});

		$(document).ready(function() {
			$('#tabs').tabs({
				active: <?php echo $tabIndex; ?>,
				beforeLoad: function( event, ui ) {
					$(ui.panel).html("<p>Loading...</p>");
				}
			});
			var crumbs = document.getElementsByClassName('navpath')[0].getElementsByTagName('a');
			for(var i = 0; i < crumbs.length; i++){
				if (crumbs[i].getAttribute("href") == "harvestparams.php"){
					crumbs[i].setAttribute('href','harvestparams.php?usecookies=false&starr=<?php echo $stArrSearchJson; ?>&jsoncollstarr=<?php echo $stArrCollJson; ?>');
				}
			}
		});
		
		function addVoucherToCl(occidIn,clidIn,tidIn){
			$.ajax({
				type: "POST",
				url: "rpc/addvoucher.php",
				data: { occid: occidIn, clid: clidIn, tid: tidIn }
			}).done(function( msg ) {
				if(msg == "1"){
					alert("Success! Voucher added to checklist.");
				}
				else{
					alert(msg);
				}
			});
		}
		
		<?php
		if($collManager->getClName() && array_key_exists('targettid',$_REQUEST)){
			?>
			function addAllVouchersToCl(clidIn){
				$.ajax({
					type: "POST",
					url: "rpc/addallvouchers.php",
					data: { clid: clidIn, jsonOccArr: <?php echo (isset($specOccJson)&&$specOccJson?$specOccJson:'0'); ?>, tid: <?php echo (isset($_REQUEST["targettid"])?$_REQUEST["targettid"]:'0'); ?> }
				}).done(function( msg ) {
					if(msg == "1"){
						alert("Success! All vouchers added to checklist.");
					}
					else{
						alert(msg);
					}
				});
			}
			<?php
		}
		?>
		
		function toggle(target){
			var objDiv = document.getElementById(target);
			if(objDiv){
				if(objDiv.style.display=="none"){
					objDiv.style.display = "block";
				}
				else{
					objDiv.style.display = "none";
				}
			}
			else{
				var divs = document.getElementsByTagName("div");
				for (var h = 0; h < divs.length; h++) {
				var divObj = divs[h];
					if(divObj.className == target){
						if(divObj.style.display=="none"){
							divObj.style.display="block";
						}
						else {
							divObj.style.display="none";
						}
					}
				}
			}
		}
		
		function openMapPU(){
			window.open('../map/googlemap.php?usecookies=false&starr=<?php echo $stArrSearchJson; ?>&jsoncollstarr=<?php echo $stArrCollJson; ?>&maptype=occquery','gmap','toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=1,resizable=1,width=1150,height=900,left=20,top=20');
		}

		function openIndPU(occId,clid){
			var wWidth = 900;
			if(document.getElementById('maintable').offsetWidth){
				wWidth = document.getElementById('maintable').offsetWidth*1.05;
			}
			else if(document.body.offsetWidth){
				wWidth = document.body.offsetWidth*0.9;
			}
			if(wWidth > 1000) wWidth = 1000;
			newWindow = window.open('individual/index.php?occid='+occId+'&clid='+clid,'indspec' + occId,'scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=700,left=20,top=20');
			if (newWindow.opener == null) newWindow.opener = self;
			return false;
		}
	</script>
</head>
<body>
<?php
	$displayLeftMenu = (isset($collections_listMenu)?$collections_listMenu:false);
	include($SERVER_ROOT.'/header.php');
	if(isset($collections_listCrumbs)){
		if($collections_listCrumbs){
			echo '<div class="navpath">';
			echo $collections_listCrumbs.' &gt;&gt; ';
			echo ' <b>'.$LANG['NAV_SPECIMEN_LIST'].'</b>';
			echo '</div>';
		}
	}
	else{
		echo '<div class="navpath">';
		echo '<a href="../index.php">'.$LANG['NAV_HOME'].'</a> &gt;&gt; ';
		echo '<a href="index.php">'.$LANG['NAV_COLLECTIONS'].'</a> &gt;&gt; ';
		echo '<a href="harvestparams.php">'.$LANG['NAV_SEARCH'].'</a> &gt;&gt; ';
		echo '<b>'.$LANG['NAV_SPECIMEN_LIST'].'</b>';
		echo '</div>';
	}
	?>
<!-- This is inner text! -->
<div id="innertext">
	<div id="tabs" style="width:95%;">
		<ul>
			<li>
				<a href='checklist.php?usecookies=false&starr=<?php echo $stArrSearchJson; ?>&jsoncollstarr=<?php echo $stArrCollJson; ?>&taxonfilter=<?php echo $taxonFilter; ?>'>
					<span><?php echo $LANG['TAB_CHECKLIST']; ?></span>
				</a>
			</li>
			<li>
				<a href="#speclist">
					<span><?php echo $LANG['TAB_OCCURRENCES']; ?></span>
				</a>
			</li>
			<li>
				<a href="#maps">
					<span><?php echo $LANG['TAB_MAP']; ?></span>
				</a>
			</li>
		</ul>
		<div id="speclist">
			<div style="float:right;">
				<div class='button' style='margin:15px 15px 0px 0px;width:13px;height:13px;' title='<?php echo $LANG['DOWNLOAD_SPECIMEN_DATA']; ?>'>
					<a href='download/index.php?usecookies=false&dltype=specimen&starr=<?php echo $stArrSearchJson; ?>&jsoncollstarr=<?php echo $stArrCollJson; ?>'>
						<img src='../images/dl.png'/>
					</a>
				</div>
				<?php
				$targetClid = $collManager->getSearchTerm("targetclid");
				if($collManager->getClName() && array_key_exists('targettid',$_REQUEST)){
					?>
					<div style="cursor:pointer;margin:8px 8px 0px 0px;" onclick="addAllVouchersToCl(<?php echo $targetTid; ?>)" title="Link All Vouchers on Page">
						<img src="../images/voucheradd.png" style="border:solid 1px gray;height:13px;margin-right:5px;" />
					</div>
					<?php
				}
				?>
			</div>
			<div style='margin:10px;'>
				<div><b><?php echo $LANG['DATASET']; ?>:</b> <?php echo $collManager->getDatasetSearchStr(); ?></div>
				<?php 
				if($collManager->getTaxaSearchStr()){
					echo '<div><b>'.$LANG['TAXA'].':</b> '.$collManager->getTaxaSearchStr().'</div>';
				}
				if($collManager->getLocalSearchStr()){
					echo '<div><b>'.$LANG['SEARCH_CRITERIA'].':</b> '.$collManager->getLocalSearchStr().'</div>';
				}
				?>
			</div>
			<div style='margin:10px;'>
				<?php
					$tableLink = 'listtabledisplay.php?usecookies=false&starr='.$stArrSearchJson.'&jsoncollstarr='.$stArrCollJson.(array_key_exists('targettid',$_REQUEST)?'&targettid='.$_REQUEST["targettid"]:'');
					echo "<a href='".$tableLink."'>See Results in Table View</a>";
				?>
			</div>
			<?php 
			$paginationStr = '<div><div style="clear:both;"><hr/></div><div style="float:left;margin:5px;">';
			$lastPage = (int)($collManager->getRecordCnt() / $cntPerPage) + 1;
			$startPage = ($pageNumber > 4?$pageNumber - 4:1);
			$endPage = ($lastPage > $startPage + 9?$startPage + 9:$lastPage);
			$hrefPrefix = 'list.php?usecookies=false&starr='.$stArrSearchJson.'&jsoncollstarr='.$stArrCollJson.(array_key_exists('targettid',$_REQUEST)?'&targettid='.$_REQUEST["targettid"]:'').'&page=';
			$pageBar = '';
			if($startPage > 1){
				$pageBar .= "<span class='pagination' style='margin-right:5px;'><a href='".$hrefPrefix."1'>".$LANG['PAGINATION_FIRST'].'</a></span>';
				$pageBar .= "<span class='pagination' style='margin-right:5px;'><a href='".$hrefPrefix.(($pageNumber - 10) < 1 ?1:$pageNumber - 10)."'>&lt;&lt;</a></span>";
			}
			for($x = $startPage; $x <= $endPage; $x++){
				if($pageNumber != $x){
					$pageBar .= "<span class='pagination' style='margin-right:3px;margin-right:3px;'><a href='".$hrefPrefix.$x."'>".$x."</a></span>";
				}
				else{
					$pageBar .= "<span class='pagination' style='margin-right:3px;margin-right:3px;font-weight:bold;'>".$x."</span>";
				}
			}
			if(($lastPage - $startPage) >= 10){
				$pageBar .= "<span class='pagination' style='margin-left:5px;'><a href='".$hrefPrefix.(($pageNumber + 10) > $lastPage?$lastPage:($pageNumber + 10))."'>&gt;&gt;</a></span>";
				$pageBar .= "<span class='pagination' style='margin-left:5px;'><a href='".$hrefPrefix.$lastPage."'>Last</a></span>";
			}
			$pageBar .= '</div><div style="float:right;margin:5px;">';
			$beginNum = ($pageNumber - 1)*$cntPerPage + 1;
			$endNum = $beginNum + $cntPerPage - 1;
			if($endNum > $collManager->getRecordCnt()) $endNum = $collManager->getRecordCnt();
			$pageBar .= $LANG['PAGINATION_PAGE'].' '.$pageNumber.', '.$LANG['PAGINATION_RECORDS'].' '.$beginNum.'-'.$endNum.' '.$LANG['PAGINATION_OF'].' '.$collManager->getRecordCnt();
			$paginationStr .= $pageBar;
			$paginationStr .= '</div><div style="clear:both;"><hr/></div></div>';
			echo $paginationStr;
	
			//Display specimen records
			if(array_key_exists("error",$specimenArray)){
				echo "<h3>".$specimenArray["error"]."</h3>";
				$collManager->reset();
			}
			elseif($specimenArray){
				$collectionArr = $collManager->getCollectionList(array_keys($specimenArray));
				?>
				<table id="omlisttable">
				<?php 
				foreach($specimenArray as $collId => $specData){
					$isEditor = false;
					if($SYMB_UID && ($IS_ADMIN
					|| (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollAdmin']))
					|| (array_key_exists('CollEditor',$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollEditor'])))){
						$isEditor = true;
					}
					$instCode1 = $collectionArr[$collId]['instcode'];
					if($collectionArr[$collId]['collcode']) $instCode1 .= ":".$collectionArr[$collId]['collcode'];
		
					$icon = (substr($collectionArr[$collId]['icon'],0,6)=='images'?'../':'').$collectionArr[$collId]['icon']; 
					?>
					<tr>
						<td colspan='2'>
							<h2>
								<a href="misc/collprofiles.php?collid=<?php echo $collId; ?>">
									<?php echo $collectionArr[$collId]['name']; ?>
								</a>
							</h2>
							<hr />
						</td>
					</tr>
					<?php 
					foreach($specData as $occId => $fieldArr){
						$instCode2 = "";
						if($fieldArr["institutioncode"] && $fieldArr["institutioncode"] != $collectionArr[$collId]['instcode']){
							$instCode2 = $fieldArr["institutioncode"];
							if($fieldArr["collectioncode"]) $instCode2 .= ":".$fieldArr["collectioncode"];
						}
						?>
						<tr>
							<td width='60' valign='top' align='center'>
								<a href="misc/collprofiles.php?collid=<?php echo $collId."&acronym=".$fieldArr["institutioncode"]; ?>">
									<img align='bottom' width='35px' src='<?php echo $icon; ?>' style="border:0px;" />
								</a>
								<div style='font-weight:bold;font-size:75%;'>
									<?php 
									echo $instCode1;
									if($instCode2) echo "<br/>".$instCode2;
									?>
								</div>
							</td>
							<td>
								<?php 
								if($isEditor || ($SYMB_UID && $SYMB_UID == $fieldArr['observeruid'])){ 
									?>
									<div style="float:right;" title="<?php echo $LANG['OCCUR_EDIT_TITLE']; ?>">
										<a href="editor/occurrenceeditor.php?occid=<?php echo $occId; ?>" target="_blank">
											<img src="../images/edit.png" style="border:solid 1px gray;height:13px;" />
										</a>
									</div>
									<?php 
								} 
								if($collManager->getClName() && array_key_exists('targettid',$_REQUEST)){
									?>
									<div style="float:right;" >
										<a href="#" onclick="addVoucherToCl(<?php echo $occId.",".$targetClid.",".$_REQUEST["targettid"];?>)" title="<?php echo $LANG['VOUCHER_LINK_TITLE'].' '.$collManager->getClName(); ?>;return false;">
											<img src="../images/voucheradd.png" style="border:solid 1px gray;height:13px;margin-right:5px;" />
										</a>
									</div>
									<?php 
								}
								if(isset($fieldArr['img'])){
									?>
									<div style="float:right;margin:5px 25px">
										<a href="#" onclick="return openIndPU(<?php echo $occId.",".($targetClid?$targetClid:"0"); ?>);">
											<img src="<?php echo $fieldArr['img']; ?>" style="height:70px" />
										</a>
									</div>
									<?php 
								}
								?>
								<div style="margin:4px">
									<a target='_blank' href='../taxa/index.php?taxon=<?php echo $fieldArr["sciname"];?>'>
										<span style="font-style:italic;">
											<?php echo $fieldArr["sciname"];?>
										</span>
									</a> 
									<?php echo $fieldArr["author"]; ?>
								</div>
								<div style="margin:4px">
									<?php 
									echo '<span style="width:150px;">'.$fieldArr["accession"].'</span>';
									echo '<span style="width:200px;margin-left:30px;">'.$fieldArr["collector"].'&nbsp;&nbsp;&nbsp;'.$fieldArr["collnumber"].'</span>';
									if(isset($fieldArr["date"])) echo '<span style="margin-left:30px;">'.$fieldArr["date"].'</span>'; 
									?>
								</div>
								<div style="margin:4px">
									<?php 
									$localStr = "";
									if($fieldArr["country"]) $localStr .= $fieldArr["country"].", ";
									if($fieldArr["state"]) $localStr .= $fieldArr["state"].", ";
									if($fieldArr["county"]) $localStr .= $fieldArr["county"].", ";
									if($fieldArr["locality"]) $localStr .= $fieldArr["locality"].", ";
									if(isset($fieldArr["elev"]) && $fieldArr["elev"]) $localStr .= $fieldArr["elev"].'m';
									if(strlen($localStr) > 2) $localStr = trim($localStr,' ,');
									echo $localStr; 
									?>
								</div>
								<div style="margin:4px">
									<b>
										<a href="#" onclick="return openIndPU(<?php echo $occId.",".($targetClid?$targetClid:"0"); ?>);">
											<?php echo $LANG['FULL_DETAILS']; ?>
										</a>
									</b>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan='2'>
								<hr/>
							</td>
						</tr>
						<?php 
					}
				}
				?>
				</table>
				<?php 
				echo $paginationStr;
				echo "<hr/>";
			}
			else{
				?>
				<div>
					<h3><?php echo $LANG['NO_RESULTS']; ?></h3>
					<?php
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
						?>
						<div style="margin: 40px 0px 200px 20px;font-weight:bold;font-size:140%;">
							<?php
							echo $LANG['PERHAPS_LOOKING_FOR'];
							$delimiter = '';
							foreach($closeArr as $v){
								echo $delimiter.'<a href="harvestparams.php?taxa='.$v.'">'.$v.'</a>';
								$delimiter = ', ';
							}
							?>
						</div>
						<?php 
					}
					?>
				</div>
				<?php 
			}
			?>
		</div>
		<div id="maps" style="min-height:400px;margin-bottom:10px;">
			<div class="button" style="margin-top:20px;float:right;width:13px;height:13px;" title="<?php echo $LANG['MAP_DOWNLOAD']; ?>">
				<a href='download/index.php?usecookies=false&starr=<?php echo $stArrSearchJson; ?>&jsoncollstarr=<?php echo $stArrCollJson; ?>&dltype=georef'><img src="../images/dl.png"/></a>
			</div>
			<div style='margin-top:10px;'>
				<h2><?php echo $LANG['GOOGLE_MAP_HEADER']; ?></h2>
			</div>
			<div style='margin:10 0 0 20;'>
				<a href="#" onclick="openMapPU();" >
					<?php echo $LANG['GOOGLE_MAP_DISPLAY']; ?>
				</a>
			</div>
			<div style='margin:10 0 0 20;'>
				<?php echo $LANG['GOOGLE_MAP_DESCRIPTION'];?>
			</div>
	
			<div style='margin-top:10px;'>
				<h2><?php echo $LANG['GOOGLE_EARTH_HEADER']; ?></h2>
			</div>
			<form name="kmlform" action="../map/googlekml.php" method="post" onsubmit="">
				<div style='margin:10 0 0 20;'>
					<?php echo $LANG['GOOGLE_EARTH_DESCRIPTION'];?>
				</div>
				<div style='margin:10 0 0 20;'>
					<a href="#" onclick="toggle('fieldBox');">
						<?php echo $LANG['GOOGLE_EARTH_EXTRA']; ?>
					</a>
				</div>
				<div id="fieldBox" style="display:none;">
					<fieldset>
						<div style="width:600px;">
							<?php 
							foreach($occFieldArr as $k => $v){
								echo '<div style="float:left;margin-right:5px;">';
								echo '<input type="checkbox" name="kmlFields[]" value="'.$v.'" />'.$v.'</div>';
							}
							?>
						</div>
					</fieldset>
				</div>
				<div style="margin-top:8px;float:right;">
					<input name="jsoncollstarr" type="hidden" value='<?php echo $stArrCollJson; ?>' />
					<input name="starr" type="hidden" value='<?php echo $stArrSearchJson; ?>' />
					<button name="formsubmit" type="submit" value="<?php echo $LANG['CREATE_KML']; ?>"><?php echo $LANG['CREATE_KML']; ?></button>
				</div>
			</form>
		</div>
	</div>
</div>
<?php 
include($SERVER_ROOT."/footer.php");
?>
</body>
</html>