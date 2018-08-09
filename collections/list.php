<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/list.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceListManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:0;
$targetTid = array_key_exists("targettid",$_REQUEST)?$_REQUEST["targettid"]:'';
$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:1;
$cntPerPage = array_key_exists("cntperpage",$_REQUEST)?$_REQUEST["cntperpage"]:100;
$pageNumber = array_key_exists("page",$_REQUEST)?$_REQUEST["page"]:1;

//Sanitation
if(!is_numeric($taxonFilter)) $taxonFilter = 1;
if(!is_numeric($tabIndex)) $tabIndex= 1;
if(!is_numeric($cntPerPage)) $cntPerPage = 100;
if(!is_numeric($pageNumber)) $pageNumber = 1;

$collManager = new OccurrenceListManager();
$searchVar = $collManager->getQueryTermStr();
$occurArr = $collManager->getSpecimenMap($pageNumber,$cntPerPage);
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
	<title><?php echo $DEFAULT_TITLE.' '.$LANG['PAGE_TITLE']; ?></title>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<style type="text/css">
		.ui-tabs .ui-tabs-nav li { width:32%; }
		.ui-tabs .ui-tabs-nav li a { margin-left:10px;}
	</style>
	<link href="../js/jquery-ui-1.12.1/jquery-ui.min.css" type="text/css" rel="Stylesheet" />
	<script src="../js/jquery-3.2.1.min.js" type="text/javascript"></script>
	<script src="../js/jquery-ui-1.12.1/jquery-ui.min.js" type="text/javascript"></script>
	<script type="text/javascript">
		<?php include_once($SERVER_ROOT.'/config/googleanalytics.php'); ?>
	</script>
	<script type="text/javascript">
		var urlQueryStr = "<?php echo $searchVar.'&page='.$pageNumber; ?>";

		$(document).ready(function() {
			<?php
			if($searchVar){
				?>
				sessionStorage.querystr = "<?php echo $searchVar; ?>";
				<?php
			}
			else{
				?>
				if(sessionStorage.querystr){
					window.location = "list.php?"+sessionStorage.querystr+"&tabindex=<?php echo $tabIndex ?>";
				}
				<?php
			}
			?>

			$('#tabs').tabs({
				active: <?php echo $tabIndex; ?>,
				beforeLoad: function( event, ui ) {
					$(ui.panel).html("<p>Loading...</p>");
				}
			});
		});

		function addAllVouchersToCl(clidIn){
			var occJson = document.getElementById("specoccjson").value;

			$.ajax({
				type: "POST",
				url: "rpc/addallvouchers.php",
				data: { clid: clidIn, jsonOccArr: occJson, tid: <?php echo ($targetTid?$targetTid:'0'); ?> }
			}).done(function( msg ) {
				if(msg == "1"){
					alert("Success! All vouchers added to checklist.");
				}
				else{
					alert(msg);
				}
			});
		}
	</script>
	<script src="../js/symb/collections.list.js?ver=7" type="text/javascript"></script>
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
				<a id="taxatablink" href='<?php echo 'checklist.php?'.$searchVar.'&taxonfilter='.$taxonFilter; ?>'>
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
			<div id="queryrecords">
				<div style="float:right;">
					<form action="download/index.php" method="post" style="float:left" onsubmit="targetPopup(this)">
						<button class="ui-button ui-widget ui-corner-all" style="margin:5px;padding:5px;cursor: pointer" title="<?php echo $LANG['DOWNLOAD_SPECIMEN_DATA']; ?>">
							<img src="../images/dl2.png" style="width:15px" />
						</button>
						<input name="searchvar" type="hidden" value="<?php echo $searchVar; ?>" />
						<input name="dltype" type="hidden" value="specimen" />
					</form>
					<button class="ui-button ui-widget ui-corner-all" style="margin:5px;padding:5px;cursor: pointer;" onclick="copyUrl()" title="Copy URL to Clipboard"><img src="../images/link2.png" style="width:15px" /></button>
 					<?php
					if($collManager->getClName() && $targetTid){
						?>
						<button class="ui-button ui-widget ui-corner-all" style="margin:5px;padding:5px;cursor:pointer;" onclick="addAllVouchersToCl(<?php echo $targetTid; ?>)" title="Link All Vouchers on Page"><img src="../images/voucheradd.png" style="border:solid 1px gray;height:13px;margin-right:5px;" /></button>
						<?php
					}
					?>
				</div>
				<div style="margin:5px;">
					<?php
					echo '<div><b>'.$LANG['DATASET'].':</b> '.$collManager->getDatasetSearchStr().'</div>';
					if($taxaSearchStr = $collManager->getTaxaSearchStr()){
						echo '<div><b>'.$LANG['TAXA'].':</b> '.$taxaSearchStr.'</div>';
					}
					if($localSearchStr = $collManager->getLocalSearchStr()){
						echo '<div><b>'.$LANG['SEARCH_CRITERIA'].':</b> '.$localSearchStr.'</div>';
					}
					?>
				</div>
				<div style="clear:both;"></div>
				<?php
				//Add pagination
				$paginationStr = '<div><div style="clear:both;"><hr/></div><div style="float:left;margin:5px;">';
				$lastPage = (int)($collManager->getRecordCnt() / $cntPerPage) + 1;
				$startPage = ($pageNumber > 5?$pageNumber - 5:1);
				$endPage = ($lastPage > $startPage + 10?$startPage + 10:$lastPage);
				$pageBar = '';
				if($startPage > 1){
					$pageBar .= '<span class="pagination" style="margin-right:5px;"><a href="list.php?'.$searchVar.'" >'.$LANG['PAGINATION_FIRST'].'</a></span>';
					$pageBar .= '<span class="pagination" style="margin-right:5px;"><a href="list.php?'.$searchVar.'&page='.(($pageNumber - 10) < 1?1:$pageNumber - 10).'">&lt;&lt;</a></span>';
				}
				for($x = $startPage; $x <= $endPage; $x++){
					if($pageNumber != $x){
						$pageBar .= '<span class="pagination" style="margin-right:3px;margin-right:3px;"><a href="list.php?'.$searchVar.'&page='.$x.'">'.$x.'</a></span>';
					}
					else{
						$pageBar .= '<span class="pagination" style="margin-right:3px;margin-right:3px;font-weight:bold;">'.$x.'</span>';
					}
				}
				if(($lastPage - $startPage) >= 10){
					$pageBar .= '<span class="pagination" style="margin-left:5px;"><a href="list.php?'.$searchVar.'&page='.(($pageNumber + 10) > $lastPage?$lastPage:($pageNumber + 10)).'">&gt;&gt;</a></span>';
					$pageBar .= '<span class="pagination" style="margin-left:5px;"><a href="list.php?'.$searchVar.'&page='.$lastPage.'">Last</a></span>';
				}
				$pageBar .= '</div><div style="float:right;margin:5px;">';
				$beginNum = ($pageNumber - 1)*$cntPerPage + 1;
				$endNum = $beginNum + $cntPerPage - 1;
				if($endNum > $collManager->getRecordCnt()) $endNum = $collManager->getRecordCnt();
				$pageBar .= $LANG['PAGINATION_PAGE'].' '.$pageNumber.', '.$LANG['PAGINATION_RECORDS'].' '.$beginNum.'-'.$endNum.' '.$LANG['PAGINATION_OF'].' '.$collManager->getRecordCnt();
				$paginationStr .= $pageBar;
				$paginationStr .= '</div><div style="clear:both;"><hr/></div></div>';
				echo $paginationStr;

				//Add search return
				if($occurArr){
					echo '<table id="omlisttable">';
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
							$instCode = $fieldArr["instcode"];
							if($fieldArr["collcode"]) $instCode .= ":".$fieldArr["collcode"];
							echo '<tr><td colspan="2"><h2>';
							echo '<a href="misc/collprofiles.php?collid='.$collId.'">'.$fieldArr["collname"].'</a>';
							echo '</h2><hr /></td></tr>';
						}
						echo '<tr><td width="60" valign="top" align="center">';
						echo '<a href="misc/collprofiles.php?collid='.$collId.'&acronym='.$fieldArr["instcode"].'">';
						if($fieldArr["icon"]){
							$icon = (substr($fieldArr["icon"],0,6)=='images'?'../':'').$fieldArr["icon"];
							echo '<img align="bottom" src="'.$icon.'" style="width:35px;border:0px;" />';
						}
						echo '</a>';
						echo '<div style="font-weight:bold;font-size:75%;">';
						echo $instCode;
						echo '</div></td><td>';
						if($isEditor || ($SYMB_UID && $SYMB_UID == $fieldArr['obsuid'])){
							echo '<div style="float:right;" title="'.$LANG['OCCUR_EDIT_TITLE'].'">';
							echo '<a href="editor/occurrenceeditor.php?occid='.$occid.'" target="_blank">';
							echo '<img src="../images/edit.png" style="border:solid 1px gray;height:13px;" /></a></div>';
						}
						$targetClid = $collManager->getSearchTerm("targetclid");
						if($collManager->getClName() && $targetTid){
							echo '<div style="float:right;" >';
							echo '<a href="#" onclick="addVoucherToCl('.$occid.','.$targetClid.','.$targetTid.')" title="'.$LANG['VOUCHER_LINK_TITLE'].' '.$collManager->getClName().';return false;">';
							echo '<img src="../images/voucheradd.png" style="border:solid 1px gray;height:13px;margin-right:5px;" /></a></div>';
						}
						if(isset($fieldArr['img'])){
							echo '<div style="float:right;margin:5px 25px;">';
							echo '<a href="#" onclick="return openIndPU('.$occid.','.($targetClid?$targetClid:"0").');">';
							echo '<img src="'.$fieldArr['img'].'" style="height:70px" /></a></div>';
						}
						echo '<div style="margin:4px;">';
						echo '<a target="_blank" href="../taxa/index.php?taxon='.$fieldArr["sciname"].'">';
						echo '<span style="font-style:italic;">'.$fieldArr["sciname"].'</span></a> '.$fieldArr["author"].'</div>';
						echo '<div style="margin:4px">';
						echo '<span style="width:150px;">'.$fieldArr["catnum"].'</span>';
						echo '<span style="width:200px;margin-left:30px;">'.$fieldArr["collector"].'&nbsp;&nbsp;&nbsp;'.(isset($fieldArr["collnum"])?$fieldArr["collnum"]:'').'</span>';
						if(isset($fieldArr["date"])) echo '<span style="margin-left:30px;">'.$fieldArr["date"].'</span>';
						echo '</div><div style="margin:4px">';
						$localStr = "";
						if($fieldArr["country"]) $localStr .= $fieldArr["country"].", ";
						if($fieldArr["state"]) $localStr .= $fieldArr["state"].", ";
						if($fieldArr["county"]) $localStr .= $fieldArr["county"].", ";
						if($fieldArr["locality"]) $localStr .= $fieldArr["locality"].", ";
						if(isset($fieldArr["elev"]) && $fieldArr["elev"]) $localStr .= $fieldArr["elev"].'m';
						if(strlen($localStr) > 2) $localStr = trim($localStr,' ,');
						echo $localStr;
						echo '</div><div style="margin:4px">';
						echo '<b><a href="#" onclick="return openIndPU('.$occid.','.($targetClid?$targetClid:"0").');">'.$LANG['FULL_DETAILS'].'</a></b>';
						echo '</div></td></tr><tr><td colspan="2"><hr/></td></tr>';
					}
					$specOccJson = json_encode($specOccArr);
					echo "<input id='specoccjson' type='hidden' value='".$specOccJson."' />";
					echo '</table>'.$paginationStr.'<hr/>';
				}
				else{
					echo '<div><h3>'.$LANG['NO_RESULTS'].'</h3>';
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
						echo '<div style="margin: 40px 0px 200px 20px;font-weight:bold;">';
						echo $LANG['PERHAPS_LOOKING_FOR'].' ';
						$outStr = '';
						foreach($closeArr as $v){
							$outStr .= '<a href="harvestparams.php?taxa='.$v.'">'.$v.'</a>, ';
						}
						echo trim($outStr,' ,');
						echo '</div>';
					}
					echo '</div>';
				}
				?>
			</div>
		</div>
		<div id="maps" style="min-height:400px;margin-bottom:10px;">
			<form action="download/index.php" method="post" style="float:right" onsubmit="targetPopup(this)">
				<button class="ui-button ui-widget ui-corner-all" style="margin:5px;padding:5px;cursor: pointer" title="<?php echo $LANG['DOWNLOAD_SPECIMEN_DATA']; ?>">
					<img src="../images/dl2.png" style="width:15px" />
				</button>
				<input name="searchvar" type="hidden" value="<?php echo $searchVar; ?>" />
				<input name="dltype" type="hidden" value="georef" />
			</form>

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
			<form name="kmlform" action="map/googlekml.php" method="post" onsubmit="">
				<div style='margin:10 0 0 20;'>
					<?php echo $LANG['GOOGLE_EARTH_DESCRIPTION'];?>
				</div>
				<div style="margin:20px;">
					<input name="searchvar" type="hidden" value="<?php echo $searchVar; ?>" />
					<button name="formsubmit" type="submit" value="<?php echo $LANG['CREATE_KML']; ?>"><?php echo $LANG['CREATE_KML']; ?></button>
				</div>
				<div style='margin:10 0 0 20;'>
					<a href="#" onclick="toggleFieldBox('fieldBox');">
						<?php echo $LANG['GOOGLE_EARTH_EXTRA']; ?>
					</a>
				</div>
				<div id="fieldBox" style="display:none;">
					<fieldset>
						<div style="width:600px;">
							<?php
							$occFieldArr = Array('occurrenceid','family', 'scientificname', 'sciname',
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
							foreach($occFieldArr as $k => $v){
								echo '<div style="float:left;margin-right:5px;">';
								echo '<input type="checkbox" name="kmlFields[]" value="'.$v.'" />'.$v.'</div>';
							}
							?>
						</div>
					</fieldset>
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