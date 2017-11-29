<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/harvestparams.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceManager.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$collManager = new OccurrenceManager();
$collArr = Array();
$stArr = Array();
$stArrCollJson = '';
$stArrSearchJson = '';

if(isset($_REQUEST['taxa']) || isset($_REQUEST['country']) || isset($_REQUEST['state']) || isset($_REQUEST['county']) || isset($_REQUEST['local']) || isset($_REQUEST['elevlow']) || isset($_REQUEST['elevhigh']) || isset($_REQUEST['upperlat']) || isset($_REQUEST['pointlat']) || isset($_REQUEST['collector']) || isset($_REQUEST['collnum']) || isset($_REQUEST['eventdate1']) || isset($_REQUEST['eventdate2']) || isset($_REQUEST['catnum']) || isset($_REQUEST['typestatus']) || isset($_REQUEST['hasimages'])){
    $stArr = $collManager->getSearchTerms();
    $stArrSearchJson = json_encode($stArr);
}

if(isset($_REQUEST['db'])){
    $collArr['db'] = $collManager->getSearchTerm('db');
    $stArrCollJson = json_encode($collArr);
}
?>

<html>
<head>
    <title><?php echo $defaultTitle.' '.$LANG['PAGE_TITLE']; ?></title>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link href="../css/jquery-ui.css" type="text/css" rel="Stylesheet" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
    <script type="text/javascript" src="../js/symb/collections.harvestparams.js?ver=9"></script>
    <script type="text/javascript">
        var starrJson = '';

        $(document).ready(function() {
            <?php
            if($stArrCollJson){
                echo "sessionStorage.jsoncollstarr = '".$stArrCollJson."';\n";
            }

            if($stArrSearchJson){
                ?>
                starrJson = '<?php echo $stArrSearchJson; ?>';
                sessionStorage.jsonstarr = starrJson;
                setHarvestParamsForm();
                <?php
            }
            else{
                ?>
                if(sessionStorage.jsonstarr){
                    starrJson = sessionStorage.jsonstarr;
                    setHarvestParamsForm();
                }
                <?php
            }
            ?>
        });

        function checkHarvestparamsForm(frm){
            <?php
            if(!$SOLR_MODE){
                ?>
                //make sure they have filled out at least one field.
                if ((frm.taxa.value == '') && (frm.country.value == '') && (frm.state.value == '') && (frm.county.value == '') &&
                    (frm.locality.value == '') && (frm.upperlat.value == '') && (frm.pointlat.value == '') && (frm.catnum.value == '') &&
                    (frm.elevhigh.value == '') && (frm.eventdate2.value == '') && (frm.typestatus.checked == false) && (frm.hasimages.checked == false) && (frm.hasgenetic.checked == false) &&
                    (frm.collector.value == '') && (frm.collnum.value == '') && (frm.eventdate1.value == '') && (frm.elevlow.value == '')) {
                    alert("Please fill in at least one search parameter!");
                    return false;
                }
                <?php
            }
            ?>

            if(frm.upperlat.value != '' || frm.bottomlat.value != '' || frm.leftlong.value != '' || frm.rightlong.value != ''){
                // if Lat/Long field is filled in, they all should have a value!
                if(frm.upperlat.value == '' || frm.bottomlat.value == '' || frm.leftlong.value == '' || frm.rightlong.value == ''){
                    alert("Error: Please make all Lat/Long bounding box values contain a value or all are empty");
                    return false;
                }

                // Check to make sure lat/longs are valid.
                if(Math.abs(frm.upperlat.value) > 90 || Math.abs(frm.bottomlat.value) > 90 || Math.abs(frm.pointlat.value) > 90){
                    alert("Latitude values can not be greater than 90 or less than -90.");
                    return false;
                }
                if(Math.abs(frm.leftlong.value) > 180 || Math.abs(frm.rightlong.value) > 180 || Math.abs(frm.pointlong.value) > 180){
                    alert("Longitude values can not be greater than 180 or less than -180.");
                    return false;
                }
                if(parseFloat(frm.upperlat.value) < parseFloat(frm.bottomlat.value)){
                    alert("Your northern latitude value is less then your southern latitude value. Please correct this.");
                    return false;
                }
                if(parseFloat(frm.leftlong.value) > parseFloat(frm.rightlong.value)){
                    alert("Your western longitude value is greater then your eastern longitude value. Please correct this. Note that western hemisphere longitudes in the decimal format are negitive.");
                    return false;
                }
            }

            //Same with point radius fields
            if(frm.pointlat.value != '' || frm.pointlong.value != '' || frm.radius.value != ''){
                if(frm.pointlat.value == '' || frm.pointlong.value == '' || frm.radius.value == ''){
                    alert("Error: Please make all Lat/Long point-radius values contain a value or all are empty");
                    return false;
                }
            }

            if(frm.elevlow.value || frm.elevhigh.value){
                if(isNaN(frm.elevlow.value) || isNaN(frm.elevhigh.value)){
                    alert("Error: Please enter only numbers for elevation values");
                    return false;
                }
            }

            return true;
        }
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
		<form name="harvestparams" id="harvestparams" action="list.php" method="post" onsubmit="return checkHarvestparamsForm(this);">
			<div style="margin:10 0 10 0;"><hr></div>
			<div style='float:right;margin:5px 10px;'>
				<div style="margin-bottom:10px"><input type="submit" class="nextbtn" value="<?php echo isset($LANG['BUTTON_NEXT'])?$LANG['BUTTON_NEXT']:'Next >'; ?>" /></div>
				<div><button type="button" class="resetbtn" onclick='resetHarvestParamsForm(this.form);'><?php echo isset($LANG['BUTTON_RESET'])?$LANG['BUTTON_RESET']:'Reset Form'; ?></button></div>
			</div>
			<div>
				<h1><?php echo $LANG['TAXON_HEADER']; ?></h1>
				<span style="margin-left:5px;"><input type='checkbox' name='thes' value='1' CHECKED /><?php echo $LANG['GENERAL_TEXT_2']; ?></SPAN>
			</div>
			<div id="taxonSearch0">
				<div>
					<select id="taxontype" name="type">
						<option value='1'><?php echo $LANG['SELECT_1-1']; ?></option>
						<option value='2'><?php echo $LANG['SELECT_1-2']; ?></option>
						<option value='3'><?php echo $LANG['SELECT_1-3']; ?></option>
						<option value='4'><?php echo $LANG['SELECT_1-4']; ?></option>
						<option value='5'><?php echo $LANG['SELECT_1-5']; ?></option>
					</select>:
					<input id="taxa" type="text" size="60" name="taxa" value="" title="<?php echo $LANG['TITLE_TEXT_1']; ?>" />
				</div>
			</div>
			<div style="margin:10 0 10 0;"><hr></div>
			<div>
				<h1><?php echo $LANG['LOCALITY_HEADER']; ?></h1>
			</div>
			<div>
				<?php echo $LANG['COUNTRY_INPUT']; ?> <input type="text" id="country" size="43" name="country" value="" title="<?php echo $LANG['TITLE_TEXT_1']; ?>" />
			</div>
			<div>
				<?php echo $LANG['STATE_INPUT']; ?> <input type="text" id="state" size="37" name="state" value="" title="<?php echo $LANG['TITLE_TEXT_1']; ?>" />
			</div>
			<div>
				<?php echo $LANG['COUNTY_INPUT']; ?> <input type="text" id="county" size="37"  name="county" value="" title="<?php echo $LANG['TITLE_TEXT_1']; ?>" />
			</div>
			<div>
				<?php echo $LANG['LOCALITY_INPUT']; ?> <input type="text" id="locality" size="43" name="local" value="" />
			</div>
			<div>
				<?php echo $LANG['ELEV_INPUT_1']; ?> <input type="text" id="elevlow" size="10" name="elevlow" value="" /> <?php echo $LANG['ELEV_INPUT_2']; ?>
				<input type="text" id="elevhigh" size="10" name="elevhigh" value="" />
			</div>
            <?php
            if($QUICK_HOST_ENTRY_IS_ACTIVE) {
                ?>
                <div>
                    <?php echo $LANG['ASSOC_HOST_INPUT']; ?> <input type="text" id="assochost" size="43" name="assochost" value="" title="<?php echo $LANG['TITLE_TEXT_1']; ?>" />
                </div>
                <?php
            }
            ?>
			<div style="margin:10 0 10 0;">
				<hr>
				<h1><?php echo $LANG['LAT_LNG_HEADER']; ?></h1>
			</div>
			<div style="width:300px;float:left;border:2px solid brown;padding:10px;margin-bottom:10px;">
				<div style="font-weight:bold;">
					<?php echo $LANG['LL_BOUND_TEXT']; ?>
				</div>
				<div>
					<?php echo $LANG['LL_BOUND_NLAT']; ?> <input type="text" id="upperlat" name="upperlat" size="7" value="" onchange="checkUpperLat();" style="margin-left:9px;">
					<select id="upperlat_NS" name="upperlat_NS" onchange="checkUpperLat();">
						<option id="nlN" value="N"><?php echo $LANG['LL_N_SYMB']; ?></option>
						<option id="nlS" value="S"><?php echo $LANG['LL_S_SYMB']; ?></option>
					</select>
				</div>
				<div>
					<?php echo $LANG['LL_BOUND_SLAT']; ?> <input type="text" id="bottomlat" name="bottomlat" size="7" value="" onchange="javascript:checkBottomLat();" style="margin-left:7px;">
					<select id="bottomlat_NS" name="bottomlat_NS" onchange="checkBottomLat();">
						<option id="blN" value="N"><?php echo $LANG['LL_N_SYMB']; ?></option>
						<option id="blS" value="S"><?php echo $LANG['LL_S_SYMB']; ?></option>
					</select>
				</div>
				<div>
					<?php echo $LANG['LL_BOUND_WLNG']; ?> <input type="text" id="leftlong" name="leftlong" size="7" value="" onchange="javascript:checkLeftLong();">
					<select id="leftlong_EW" name="leftlong_EW" onchange="checkLeftLong();">
						<option id="llW" value="W"><?php echo $LANG['LL_W_SYMB']; ?></option>
						<option id="llE" value="E"><?php echo $LANG['LL_E_SYMB']; ?></option>
					</select>
				</div>
				<div>
					<?php echo $LANG['LL_BOUND_ELNG']; ?> <input type="text" id="rightlong" name="rightlong" size="7" value="" onchange="javascript:checkRightLong();" style="margin-left:3px;">
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
				<div>
					<?php echo $LANG['LL_P-RADIUS_LAT']; ?> <input type="text" id="pointlat" name="pointlat" size="7" value="" onchange="javascript:checkPointLat();" style="margin-left:11px;">
					<select id="pointlat_NS" name="pointlat_NS" onchange="checkPointLat();">
						<option id="N" value="N"><?php echo $LANG['LL_N_SYMB']; ?></option>
						<option id="S" value="S"><?php echo $LANG['LL_S_SYMB']; ?></option>
					</select>
				</div>
				<div>
					<?php echo $LANG['LL_P-RADIUS_LNG']; ?> <input type="text" id="pointlong" name="pointlong" size="7" value="" onchange="javascript:checkPointLong();">
					<select id="pointlong_EW" name="pointlong_EW" onchange="checkPointLong();">
						<option id="W" value="W"><?php echo $LANG['LL_W_SYMB']; ?></option>
						<option id="E" value="E"><?php echo $LANG['LL_E_SYMB']; ?></option>
					</select>
				</div>
				<div>
					<?php echo $LANG['LL_P-RADIUS_RADIUS']; ?> <input type="text" id="radiustemp" name="radiustemp" size="5" value="" style="margin-left:15px;" onchange="updateRadius();">
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
				<input type="text" id="collector" size="32" name="collector" value="" title="<?php echo $LANG['TITLE_TEXT_1']; ?>" />
			</div>
			<div>
				<?php echo $LANG['COLLECTOR_NUMBER']; ?>
				<input type="text" id="collnum" size="31" name="collnum" value="" title="<?php echo $LANG['TITLE_TEXT_2']; ?>" />
			</div>
			<div>
				<?php echo $LANG['COLLECTOR_DATE']; ?>
				<input type="text" id="eventdate1" size="32" name="eventdate1" style="width:100px;" value="" title="<?php echo $LANG['TITLE_TEXT_3']; ?>" /> -
				<input type="text" id="eventdate2" size="32" name="eventdate2" style="width:100px;" value="" title="<?php echo $LANG['TITLE_TEXT_4']; ?>" />
			</div>
			<div style="float:right;">
				<input type="submit" class="nextbtn" value="<?php echo isset($LANG['BUTTON_NEXT'])?$LANG['BUTTON_NEXT']:'Next >'; ?>" />
			</div>
			<div>
				<h1><?php echo $LANG['SPECIMEN_HEADER']; ?></h1>
			</div>
			<div>
				<?php echo $LANG['CATALOG_NUMBER']; ?>
                <input type="text" id="catnum" size="32" name="catnum" value="" title="<?php echo $LANG['TITLE_TEXT_1']; ?>" />
                <input name="includeothercatnum" type="checkbox" value="1" checked /> <?php echo $LANG['INCLUDE_OTHER_CATNUM']?>
			</div>
			<div>
				<input type='checkbox' name='typestatus' value='1' /> <?php echo $LANG['TYPE']; ?>
			</div>
			<div>
				<input type='checkbox' name='hasimages' value='1' /> <?php echo $LANG['HAS_IMAGE']; ?>
			</div>
            <div>
                <input type='checkbox' name='hasgenetic' value='1' /> <?php echo $LANG['HAS_GENETIC']; ?>
            </div>
			<input type="hidden" name="reset" value="1" />
		</form>
    </div>
	<?php
	include($serverRoot.'/footer.php');
	?>
</body>
</html>