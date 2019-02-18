<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyEditorManager.php');
include_once($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxonomyloader.'.$LANG_TAG.'.php');

header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../taxa/taxonomy/taxonomyloader.php?'.$_SERVER['QUERY_STRING']);

$tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:"";
$status = "";

$loaderObj = new TaxonomyEditorManager();

$isEditor = false;
if($IS_ADMIN || array_key_exists("Taxonomy",$USER_RIGHTS)){
	$isEditor = true;
}

if($isEditor){
	if(array_key_exists('sciname',$_POST)){
		$status = $loaderObj->loadNewName($_POST);
		if(is_int($status)){
		 	header("Location: taxoneditor.php?tid=".$status);
		}
	}
}
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Taxon Loader: </title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link type="text/css" href="../../css/bootstrap.min.css" rel="Stylesheet" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script src="../../js/symb/taxa.taxonomyloader.js?ver=18"></script>
</head>
<body>
<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/header.php');
	?>
	<div class="navpath">
		<a href="../../index.php">Home</a> &gt;&gt;
		<a href="taxonomydisplay.php">Taxonomy Tree Viewer</a> &gt;&gt;
		<b>Taxonomy Loader</b>
	</div>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($status){
			echo "<div style='color:red;font-size:120%;'>".$status."</div>";
		}
		if($isEditor){
			?>
			<form id="loaderform" name="loaderform" action="taxonomyloader.php" method="post" onsubmit="return verifyLoadForm(this)">
				<fieldset>
					<legend><b><?php echo $LANG['ADD_TAXON'];?></b></legend>
					<div>
						<div style="float:left;width:170px;"><?php echo $LANG['TAX_NAME'];?></div>
						<input type="text" id="sciname" name="sciname" style="width:300px;border:inset;" value="" onchange="parseName(this.form)"/>
					</div>
					<div>
						<div style="float:left;width:170px;"><?php echo $LANG['AUTHOR'];?></div>
						<input type='text' id='author' name='author' style='width:300px;border:inset;' />
					</div>
					<div style="clear:both;">
						<div style="float:left;width:170px;"><?php echo $LANG['TAX_RANK'];?></div>
						<select id="rankid" name="rankid" title="Rank ID" style="border:inset;">
							<option value="">Select Taxon Rank</option>
							<option value="0">Non-Ranked Node</option>
							<option value="">--------------------------------</option>
							<?php
							$tRankArr = $loaderObj->getRankArr();
							foreach($tRankArr as $rankId => $rankName){
								echo "<option value='".$rankId."' ".($rankId==220?" SELECTED":"").">".$rankName."</option>\n";
							}
							?>
						</select>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:170px;"><?php echo $LANG['UNIT_1'];?></div>
						<input type='text' id='unitind1' name='unitind1' style='width:20px;border:inset;' title='Genus hybrid indicator'/>
						<input type='text' id='unitname1' name='unitname1' style='width:200px;border:inset;' title='Genus or Base Name'/>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:170px;"><?php echo $LANG['UNIT_2'];?></div>
						<input type='text' id='unitind2' name='unitind2' style='width:20px;border:inset;' title='Species hybrid indicator'/>
						<input type='text' id='unitname2' name='unitname2' style='width:200px;border:inset;' title='epithet'/>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:170px;"><?php echo $LANG['UNIT_3'];?></div>
						<input type='text' id='unitind3' name='unitind3' style='width:50px;border:inset;' title='Rank: e.g. subsp., var., f.'/>
						<input type='text' id='unitname3' name='unitname3' style='width:200px;border:inset;' title='infrasp. epithet'/>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:170px;"><?php echo $LANG['TAX_PARENT'];?></div>
						<input type="text" id="parentname" name="parentname" style="width:300px;border:inset;" />
						<span id="addparentspan" style="display:none;">
							<a id="addparentanchor" href="taxonomyloader.php?target=" target="_blank"><?php echo $LANG['ADD_PARENT'];?></a>
						</span>
						<input type="hidden" id="parenttid" name="parenttid" value="" />
					</div>
					<div style="clear:both;">
						<div style="float:left;width:170px;"><?php echo $LANG['NOTES'];?></div>
						<input type='text' id='notes' name='notes' style='width:400px;border:inset;' title=''/>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:170px;"><?php echo $LANG['SOURCE'];?></div>
						<input type='text' id='source' name='source' style='width:400px;border:inset;' title=''/>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:170px;"><?php echo $LANG['LOC_SEC'];?></div>
						<select id="securitystatus" name="securitystatus" style='border:inset;'>
							<option value="0">No Security</option>
							<option value="1">Hide Locality Details</option>
						</select>
					</div>
					<div style="clear:both;">
						<fieldset>
							<legend><b><?php echo $LANG['ACC_STATUS'];?></b></legend>
							<div>
								<input type="radio" id="isaccepted" name="acceptstatus" value="1" onchange="acceptanceChanged(this.form)" checked> <?php echo $LANG['ACCEPTED'];?>
								<input type="radio" id="isnotaccepted" name="acceptstatus" value="0" onchange="acceptanceChanged(this.form)"> <?php echo $LANG['NO_ACCEPTED'];?>
							</div>
							<div id="accdiv" style="display:none;margin-top:3px;">
								<?php echo $LANG['ACC_TAX'];?>
								<input id="acceptedstr" name="acceptedstr" type="text" style="width:400px;border:inset;" onchange="checkAcceptedExistance(this.form)" />
								<input type="hidden" name="tidaccepted" />
								<div style="margin-top:3px;">
									<?php echo $LANG['UN_REASON'];?>Unacceptability Reason:
									<input type='text' id='unacceptabilityreason' name='unacceptabilityreason' style='width:350px;border:inset;' />
								</div>
							</div>
						</fieldset>
					</div>
					<div style="clear:both;">
						<input type="submit" name="submitaction" value="Submit New Name" />
					</div>
				</fieldset>
			</form>
			<?php
		}
		else{
			?>
			<div style="margin:30px;font-weight:bold;font-size:120%;">
				<?php echo $LANG['LEGEND'];?>
			</div>
			<?php
		}
		include($SERVER_ROOT.'/footer.php');
		?>
	</div>
</body>
</html>
