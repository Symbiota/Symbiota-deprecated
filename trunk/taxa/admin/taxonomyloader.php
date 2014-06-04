<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TaxonomyEditorManager.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$target = array_key_exists("target",$_REQUEST)?$_REQUEST["target"]:"";
$status = "";

$loaderObj = new TaxonomyEditorManager();
 
$editable = false;
if($isAdmin || array_key_exists("Taxonomy",$userRights)){
	$editable = true;
}
 
if($editable){
	if(array_key_exists('sciname',$_POST)){
		$status = $loaderObj->loadNewName($_POST);
		if(is_int($status)){
		 	header("Location: taxonomyeditor.php?target=".$status);
		}
	}
}
 
?>
<!DOCTYPE HTML>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Taxon Loader: </title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
	<link href="../../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css" type="text/css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />	
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script language="javascript" src="../../js/symb/taxa.taxonomyloader.js"></script>
</head>
<body>
<?php
$displayLeftMenu = (isset($taxa_admin_taxonomyloaderMenu)?$taxa_admin_taxonomyloaderMenu:"true");
include($serverRoot.'/header.php');
if(isset($taxa_admin_taxonomyloaderCrumbs)){
	echo "<div class='navpath'>";
	echo $taxa_admin_taxonomyloaderCrumbs;
	echo " <b>Taxonomy Loader</b>";
	echo "</div>";
}
?>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($status){
			echo "<div style='color:red;font-size:120%;'>".$status."</div>";
		}
		if($editable){
			?>
		
			<form id="loaderform" action="taxonomyloader.php" method="post" onsubmit="return verifyLoadForm(this)">
				<fieldset>
					<legend>New Taxon</legend>
					<div>
						<div style="float:left;width:170px;">Taxon Name:</div>
						<input type="text" id="sciname" name="sciname" style="width:200px;border:inset;" value="<?php echo $target;?>" onchange="parseName(this.form)"/>
					</div>
					<div>
						<div style="float:left;width:170px;">Author:</div>
						<input type='text' id='author' name='author' style='width:200px;border:inset;' />
					</div>
					<div style="margin-top:5px;clear:both;">
						<div style="float:left;width:170px;">Kingdom:</div>
						<select id="kingdomid" name="kingdomid" style="border:inset;">
							<?php 
							$kArr = $loaderObj->getKingdomIds();
							if(array_key_exists(3,$kArr)) $kArr[3] = 'Plantae';
							if(array_key_exists(4,$kArr)) $kArr[4] = 'Fungi';
							if(array_key_exists(5,$kArr)) $kArr[5] = 'Animalia';
							foreach($kArr as $k => $v){
								echo "<option value='".$k."'>".$v."</option>\n";
							}
							?>
						</select>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:170px;">Taxon Rank:</div>
						<select id="rankid" name="rankid" title="Rank ID" onchange="" style="border:inset;">
							<option value="0">Select Taxon Rank</option>
							<option value="">--------------------------------</option>
							<?php 
							$tRankArr = $loaderObj->getTaxonRanks();
							foreach($tRankArr as $rankId => $rankName){
								echo "<option value='".$rankId."' ".($rankId==220?" SELECTED":"").">".$rankName."</option>\n";
							}
							?>
						</select>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:170px;">Unit Name 1:</div>
						<input type='text' id='unitind1' name='unitind1' style='width:20px;border:inset;' title='Genus hybrid indicator'/>
						<input type='text' id='unitname1' name='unitname1' style='width:200px;border:inset;' title='Genus or Base Name'/>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:170px;">Unit Name 2:</div>
						<input type='text' id='unitind2' name='unitind2' style='width:20px;border:inset;' title='Species hybrid indicator'/>
						<input type='text' id='unitname2' name='unitname2' style='width:200px;border:inset;' title='epithet'/>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:170px;">Unit Name 3:</div>
						<input type='text' id='unitind3' name='unitind3' style='width:40px;border:inset;' title='Rank: e.g. ssp., var., f.'/>
						<input type='text' id='unitname3' name='unitname3' style='width:200px;border:inset;' title='infrasp. epithet'/>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:170px;">Parent Taxon:</div>
						<input type="text" id="parentname" name="parentname" style="width:200px;border:inset;" onchange="checkParentExistance(this.form)" />
						<span id="addparentspan" style="display:none;">
							<a id="addparentanchor" href="taxonomyloader.php?target=" target="_blank">Add Parent</a>
						</span>
						<input type="hidden" id="parenttid" name="parenttid" value="" />
					</div>
					<div id="uppertaxondiv" style="clear:both;margin-top:5px;position:relative;overflow:visible">
						<div style="float:left;width:170px;">Upper Taxonomy:</div>
						<input id="uppertaxonomy" name="uppertaxonomy" type="text" style="width:200px;border:inset;" />
					</div>
					<div style="clear:both;">
						<div style="float:left;width:170px;">Notes:</div>
						<input type='text' id='notes' name='notes' style='width:200px;border:inset;' title=''/>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:170px;">Source:</div>
						<input type='text' id='source' name='source' style='width:200px;border:inset;' title=''/>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:170px;">Locality Security Status:</div>
						<select id="securitystatus" name="securitystatus" style='border:inset;'>
							<option value="0">No Security</option>
							<option value="1">Hide Locality Details</option>
						</select>
					</div>
					<div style="clear:both;">
						<fieldset>
							<legend><b>Acceptance Status</b></legend>
							<div>
								<input type="radio" id="isaccepted" name="acceptstatus" value="1" onchange="acceptanceChanged(this.form)" checked> Accepted
								<input type="radio" id="isnotaccepted" name="acceptstatus" value="0" onchange="acceptanceChanged(this.form)"> Not Accepted
							</div>
							<div id="accdiv" style="display:none;margin-top:3px;">
								Accepted Taxon:
								<input type="text" id="acceptedstr" name="acceptedstr" style="width:300px;border:inset;" />
								<input type="hidden" name="tidaccepted" /> 
								<div style="margin-top:3px;">
									UnacceptabilityReason: 
									<input type='text' id='unacceptabilityreason' name='unacceptabilityreason' style='width:350px;' title=''/>
								</div>
							</div>
						</fieldset>
					</div>
					<div style="clear:both;">
						<input type="submit" name="submitaction" value="Submit New Name" />
					</div>
				</fieldset>
			</form>
		</div>
		<?php 
		}
		else{
			?>
			<div style="margin:30px;font-weight:bold;font-size:120%;">
				Please 
				<a href="<?php echo $clientRoot; ?>/profile/index.php?refurl=<?php echo $clientRoot?>/taxa/admin/taxonomyloader.php">
					login
				</a>
			</div>
			<?php 
		}
		include($serverRoot.'/footer.php');
		?>

</body>
</html>

