<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/harvestparams.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceManager.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$stArrCollJson = array_key_exists("jsoncollstarr",$_REQUEST)?$_REQUEST["jsoncollstarr"]:'';
$stArrSearchJson = array_key_exists("starr",$_REQUEST)?$_REQUEST["starr"]:'';

$collManager = new OccurrenceManager();
if($stArrSearchJson){
	$stArrSearchJson = str_replace("%apos;","'",$stArrSearchJson);
	$stArr = json_decode($stArrSearchJson, true);
	$collManager->setSearchTermsArr($stArr);
}
$collArray = $collManager->getSearchTerms();
if(!$stArrCollJson){
	$stArrCollJson = json_encode($collArray);
}
$collManager->reset();
?>

<html>
<head>
    <title><?php echo $defaultTitle.' '.$LANG['PAGE_TITLE']; ?></title>
	<link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/jquery-ui.css" type="text/css" rel="Stylesheet" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../js/symb/collections.harvestparams.js?var=1304"></script>
    <script type="text/javascript">
        $(document).ready(function() {
            var starrJson = '<?php echo $stArrSearchJson; ?>';
            sessionStorage.jsoncollstarr = '<?php echo $stArrCollJson; ?>';
            if(!starrJson && sessionStorage.jsonstarr){
                document.resetform.starr.value = sessionStorage.jsonstarr;
                document.resetform.jsoncollstarr.value = sessionStorage.jsoncollstarr;
                document.resetform.submit();
            }
            sessionStorage.jsoncollstarr = '<?php echo $stArrCollJson; ?>';
            //alert(sessionStorage.jsoncollstarr);
        });
    </script>
</head>
<body>

<?php
	$displayLeftMenu = (isset($collections_harvestparamsMenu)?$collections_harvestparamsMenu:false);
	include($serverRoot.'/header.php');
	if(isset($collections_harvestparamsCrumbs)){
		if($collections_harvestparamsCrumbs){
			echo '<div class="navpath">';
			echo $collections_harvestparamsCrumbs.' &gt;&gt; ';
			echo '<b>'.$LANG['NAV_SEARCH'].'</b>';
			echo '</div>';
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href="../index.php"><?php echo $LANG['NAV_HOME']; ?></a> &gt;&gt;
			<a href="index.php"><?php echo $LANG['NAV_COLLECTIONS']; ?></a> &gt;&gt;
			<b><?php echo $LANG['NAV_SEARCH']; ?></b>
		</div>
		<?php
	}
	?>

	<div id="innertext">
		<h1><?php echo $LANG['PAGE_HEADER']; ?></h1>
		<?php echo $LANG['GENERAL_TEXT_1']; ?>
		<div style="margin:5px;">
			<input type='checkbox' name='showtable' id='showtable' value='1' onchange="changeTableDisplay();" /> Show results in table view
		</div>
		<form name="harvestparams" id="harvestparams" action="list.php" method="post" onsubmit="return checkForm()">
			<div style="margin:10 0 10 0;"><hr></div>
			<div style='float:right;margin:10px;'>
				<input type="submit" class="harvestparamtbtn" value="" title="" />
			</div>
			<div>
				<h1><?php echo $LANG['TAXON_HEADER']; ?></h1>
				<span style="margin-left:5px;"><input type='checkbox' name='thes' value='1' CHECKED /><?php echo $LANG['GENERAL_TEXT_2']; ?></SPAN>
			</div>
			<div id="taxonSearch0">
				<div>
					<select id="taxontype" name="type">
						<option id='familysciname' value='1' <?php if(!array_key_exists("taxontype",$collArray) || $collArray["taxontype"] == "1") echo "SELECTED"; ?> ><?php echo $LANG['SELECT_1-1']; ?></option>
						<option id='family' value='2' <?php if(array_key_exists("taxontype",$collArray) && $collArray["taxontype"] == "2") echo "SELECTED"; ?> ><?php echo $LANG['SELECT_1-2']; ?></option>
						<option id='sciname' value='3' <?php if(array_key_exists("taxontype",$collArray) && $collArray["taxontype"] == "3") echo "SELECTED"; ?> ><?php echo $LANG['SELECT_1-3']; ?></option>
						<option id='highertaxon' value='4' <?php if(array_key_exists("taxontype",$collArray) && $collArray["taxontype"] == "4") echo "SELECTED"; ?> ><?php echo $LANG['SELECT_1-4']; ?></option>
						<option id='commonname' value='5' <?php if(array_key_exists("taxontype",$collArray) && $collArray["taxontype"] == "5") echo "SELECTED"; ?> ><?php echo $LANG['SELECT_1-5']; ?></option>
					</select>:
					<input id="taxa" type="text" size="60" name="taxa" value="<?php if(array_key_exists("taxa",$collArray)) echo $collArray["taxa"]; ?>" title="<?php echo $LANG['TITLE_TEXT_1']; ?>" />
				</div>
			</div>
			<div style="margin:10 0 10 0;"><hr></div>
			<div>
				<h1><?php echo $LANG['LOCALITY_HEADER']; ?></h1>
			</div>
			<div>
				<?php echo $LANG['COUNTRY_INPUT']; ?> <input type="text" id="country" size="43" name="country" value="<?php if(array_key_exists("country",$collArray)) echo $collArray["country"]; ?>" title="<?php echo $LANG['TITLE_TEXT_1']; ?>" />
			</div>
			<div>
				<?php echo $LANG['STATE_INPUT']; ?> <input type="text" id="state" size="37" name="state" value="<?php if(array_key_exists("state",$collArray)) echo $collArray["state"]; ?>" title="<?php echo $LANG['TITLE_TEXT_1']; ?>" />
			</div>
			<div>
				<?php echo $LANG['COUNTY_INPUT']; ?> <input type="text" id="county" size="37"  name="county" value="<?php if(array_key_exists("county",$collArray)) echo $collArray["county"]; ?>" title="<?php echo $LANG['TITLE_TEXT_1']; ?>" />
			</div>
			<div>
				<?php echo $LANG['LOCALITY_INPUT']; ?> <input type="text" id="locality" size="43" name="local" value="<?php if(array_key_exists("local",$collArray)) echo $collArray["local"]; ?>" />
			</div>
			<div>
				<?php echo $LANG['ELEV_INPUT_1']; ?> <input type="text" id="elevlow" size="10" name="elevlow" value="<?php if(array_key_exists("elevlow",$collArray)) echo $collArray["elevlow"]; ?>" /> <?php echo $LANG['ELEV_INPUT_2']; ?>
				<input type="text" id="elevhigh" size="10" name="elevhigh" value="<?php if(array_key_exists("elevhigh",$collArray)) echo $collArray["elevhigh"]; ?>" />
			</div>
			<div style="margin:10 0 10 0;">
				<hr>
				<h1><?php echo $LANG['LAT_LNG_HEADER']; ?></h1>
			</div>
			<div style="width:300px;float:left;border:2px solid brown;padding:10px;margin-bottom:10px;">
				<div style="font-weight:bold;">
					<?php echo $LANG['LL_BOUND_TEXT']; ?>
				</div>
				<?php
					$upperLat = "";$bottomLat = "";$leftLong = "";$rightLong = "";
					if(array_key_exists("llbound",$collArray)){
						$llBoundArr = explode(";",$collArray["llbound"]);
						$upperLat = $llBoundArr[0];
						$bottomLat = $llBoundArr[1];
						$leftLong = $llBoundArr[2];
						$rightLong = $llBoundArr[3];
					}

				?>
				<div>
					<?php echo $LANG['LL_BOUND_NLAT']; ?> <input type="text" id="upperlat" name="upperlat" size="7" value="<?php echo $upperLat; ?>" onchange="checkUpperLat();" style="margin-left:9px;">
					<select id="upperlat_NS" name="upperlat_NS" onchange="checkUpperLat();">
						<option id="nlN" value="N"><?php echo $LANG['LL_N_SYMB']; ?></option>
						<option id="nlS" value="S"><?php echo $LANG['LL_S_SYMB']; ?></option>
					</select>
				</div>
				<div>
					<?php echo $LANG['LL_BOUND_SLAT']; ?> <input type="text" id="bottomlat" name="bottomlat" size="7" value="<?php echo $bottomLat; ?>" onchange="javascript:checkBottomLat();" style="margin-left:7px;">
					<select id="bottomlat_NS" name="bottomlat_NS" onchange="checkBottomLat();">
						<option id="blN" value="N"><?php echo $LANG['LL_N_SYMB']; ?></option>
						<option id="blS" value="S"><?php echo $LANG['LL_S_SYMB']; ?></option>
					</select>
				</div>
				<div>
					<?php echo $LANG['LL_BOUND_WLNG']; ?> <input type="text" id="leftlong" name="leftlong" size="7" value="<?php echo $leftLong; ?>" onchange="javascript:checkLeftLong();">
					<select id="leftlong_EW" name="leftlong_EW" onchange="checkLeftLong();">
						<option id="llW" value="W"><?php echo $LANG['LL_W_SYMB']; ?></option>
						<option id="llE" value="E"><?php echo $LANG['LL_E_SYMB']; ?></option>
					</select>
				</div>
				<div>
					<?php echo $LANG['LL_BOUND_ELNG']; ?> <input type="text" id="rightlong" name="rightlong" size="7" value="<?php echo $rightLong; ?>" onchange="javascript:checkRightLong();" style="margin-left:3px;">
					<select id="rightlong_EW" name="rightlong_EW" onchange="checkRightLong();">
						<option id="rlW" value="W"><?php echo $LANG['LL_W_SYMB']; ?></option>
						<option id="rlE" value="E"><?php echo $LANG['LL_E_SYMB']; ?></option>
					</select>
				</div>
				<div style="clear:both;float:right;margin-top:8px;cursor:pointer;" onclick="openBoundingBoxMap();">
					<img src="../images/world.png" width="15px" title="<?php echo $LANG['LL_P-RADIUS_TITLE_1']; ?>" />
				</div>
			</div>
			<div style="width:260px; float:left;border:2px solid brown;padding:10px;margin-left:10px;">
				<div style="font-weight:bold;">
					<?php echo $LANG['LL_P-RADIUS_TEXT']; ?>
				</div>
				<?php
					$pointLat = "";$pointLong = "";$radius = "";
					if(array_key_exists("llpoint",$collArray)){
						$llPointArr = explode(";",$collArray["llpoint"]);
						$pointLat = $llPointArr[0];
						$pointLong = $llPointArr[1];
						$radius = $llPointArr[2];
					}
				?>
				<div>
					<?php echo $LANG['LL_P-RADIUS_LAT']; ?> <input type="text" id="pointlat" name="pointlat" size="7" value="<?php echo $pointLat; ?>" onchange="javascript:checkPointLat();" style="margin-left:11px;">
					<select id="pointlat_NS" name="pointlat_NS" onchange="checkPointLat();">
						<option id="N" value="N"><?php echo $LANG['LL_N_SYMB']; ?></option>
						<option id="S" value="S"><?php echo $LANG['LL_S_SYMB']; ?></option>
					</select>
				</div>
				<div>
					<?php echo $LANG['LL_P-RADIUS_LNG']; ?> <input type="text" id="pointlong" name="pointlong" size="7" value="<?php echo $pointLong; ?>" onchange="javascript:checkPointLong();">
					<select id="pointlong_EW" name="pointlong_EW" onchange="checkPointLong();">
						<option id="W" value="W"><?php echo $LANG['LL_W_SYMB']; ?></option>
						<option id="E" value="E"><?php echo $LANG['LL_E_SYMB']; ?></option>
					</select>
				</div>
				<div>
					<?php echo $LANG['LL_P-RADIUS_RADIUS']; ?> <input type="text" id="radiustemp" name="radiustemp" size="5" value="<?php echo $radius; ?>" style="margin-left:15px;" onchange="updateRadius();">
					<select id="radiusunits" name="radiusunits" onchange="updateRadius();">
						<option value="km"><?php echo $LANG['LL_P-RADIUS_KM']; ?></option>
						<option value="mi"><?php echo $LANG['LL_P-RADIUS_MI']; ?></option>
					</select>
					<input type="hidden" id="radius" name="radius" value="" />
				</div>
				<div style="clear:both;float:right;margin-top:8px;cursor:pointer;" onclick="openPointRadiusMap();">
					<img src="../images/world.png" width="15px" title="<?php echo $LANG['LL_P-RADIUS_TITLE_1']; ?>" />
				</div>
			</div>
			<div style=";clear:both;"><hr/></div>
			<div>
				<h1><?php echo $LANG['COLLECTOR_HEADER']; ?></h1>
			</div>
			<div>
				<?php echo $LANG['COLLECTOR_LASTNAME']; ?>
				<input type="text" id="collector" size="32" name="collector" value="<?php if(array_key_exists("collector",$collArray)) echo $collArray["collector"]; ?>" title="<?php echo $LANG['TITLE_TEXT_1']; ?>" />
			</div>
			<div>
				<?php echo $LANG['COLLECTOR_NUMBER']; ?>
				<input type="text" id="collnum" size="31" name="collnum" value="<?php if(array_key_exists("collnum",$collArray)) echo $collArray["collnum"]; ?>" title="<?php echo $LANG['TITLE_TEXT_2']; ?>" />
			</div>
			<div>
				<?php echo $LANG['COLLECTOR_DATE']; ?>
				<input type="text" id="eventdate1" size="32" name="eventdate1" style="width:100px;" value="<?php if(array_key_exists("eventdate1",$collArray)) echo $collArray["eventdate1"]; ?>" title="<?php echo $LANG['TITLE_TEXT_3']; ?>" /> -
				<input type="text" id="eventdate2" size="32" name="eventdate2" style="width:100px;" value="<?php if(array_key_exists("eventdate2",$collArray)) echo $collArray["eventdate2"]; ?>" title="<?php echo $LANG['TITLE_TEXT_4']; ?>" />
			</div>
			<div style="float:right;">
				<input type="submit" class="harvestparamtbtn" value="" title="" />
			</div>
			<div>
				<h1><?php echo $LANG['SPECIMEN_HEADER']; ?></h1>
			</div>
			<div>
				<?php echo $LANG['CATALOG_NUMBER']; ?>
                <input type="text" id="catnum" size="32" name="catnum" value="<?php if(array_key_exists("catnum",$collArray)) echo $collArray["catnum"]; ?>" title="<?php echo $LANG['TITLE_TEXT_1']; ?>" /> 
                <input name="includeothercatnum" type="checkbox" value="1" checked /> <?php echo $LANG['INCLUDE_OTHER_CATNUM']?>
			</div>
			<div>
				<input type='checkbox' name='typestatus' value='1' <?php if(array_key_exists("typestatus",$collArray) && $collArray["typestatus"]) echo "CHECKED"; ?> /> <?php echo $LANG['TYPE']; ?>
			</div>
			<div>
				<input type='checkbox' name='hasimages' value='1' <?php if(array_key_exists("hasimages",$collArray) && $collArray["hasimages"]) echo "CHECKED"; ?> /> <?php echo $LANG['HAS_IMAGE']; ?>
			</div>
			<input type="hidden" name="reset" value="1" />
			<input type="hidden" name="usecookies" value="false" />
			<input type="hidden" name="jsoncollstarr" value='<?php echo ($stArrCollJson?$stArrCollJson:''); ?>' />
		</form>

        <div style="display:none;">
            <form name="resetform" id="resetform" action="harvestparams.php" method="post" onsubmit="">
                <input type="hidden" name="jsoncollstarr" value='' />
                <input type="hidden" name="starr" value='' />
            </form>
        </div>
	</div>
	<?php
	include($serverRoot.'/footer.php');
	?>
</body>
</html>