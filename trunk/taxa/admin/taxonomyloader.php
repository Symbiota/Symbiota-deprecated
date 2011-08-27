<?php
/*
 * Created on 10 Aug 2009
 * E.E. Gilbert
 */

//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TaxonomyLoaderManager.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$target = array_key_exists("target",$_REQUEST)?$_REQUEST["target"]:"";
$submitAction = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:"";
$status = "";

$loaderObj = new TaxonomyLoaderManager();
 
$editable = false;
if($isAdmin || array_key_exists("",$userRights)){
	$editable = true;
}
 
if($submitAction == 'loadnewtaxon' && $editable){
	$status = $loaderObj->loadNewName($_REQUEST);
	if(is_int($status)){
	 	header("Location: taxonomyeditor.php?target=".$status);
	}
}
 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<title><?php echo $defaultTitle; ?> Taxon Loader: </title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
	<link type="text/css" href="../../css/main.css" rel="stylesheet" />
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
	echo "<a href='../index.php'>Home</a> &gt; ";
	echo $taxa_admin_taxonomyloaderCrumbs;
	echo " <b>Taxonomy Loader</b>";
	echo "</div>";
}
?>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($editable){
			if($status){
				echo "<div style='color:red;font-size:120%;'>".$status."</div>";
			}
		?>
		
			<form id="loaderform" action="taxonomyloader.php" method="get">
				<fieldset>
					<legend>New Taxon</legend>
					<div>
						<div style="float:left;width:140px;">Taxon Name:</div>
						<input type="text" id="sciname" name="sciname" style="width:200px;border:inset;" value="<?php echo $target;?>" onchange="parseName(this.form)"/>
					</div>
					<div>
						<div style="float:left;width:140px;">Author:</div>
						<input type='text' id='author' name='author' style='width:200px;border:inset;' />
					</div>
					<div style="margin-top:5px;">
						<div style="float:left;width:140px;">Taxon Rank:</div>
						<select id="rankid" name="rankid" title="Rank ID" onchange="" style="border:inset;">
							<option value="0">Select Taxon Rank</option>
							<option value="">--------------------------------</option>
							<?php 
								$loaderObj->echoTaxonRanks();
							?>
						</select>
					</div>
					<div>
						<div style="float:left;width:140px;">Base Name (eg genus):</div>
						<input type='text' id='unitind1' name='unitind1' style='width:20px;border:inset;' title='Genus hybrid indicator'/>
						<input type='text' id='unitname1' name='unitname1' style='width:200px;border:inset;' title='Genus or Base Name'/>
					</div>
					<div>
						<div style="float:left;width:140px;">Epithet:</div>
						<input type='text' id='unitind2' name='unitind2' style='width:20px;border:inset;' title='Species hybrid indicator'/>
						<input type='text' id='unitname2' name='unitname2' style='width:200px;border:inset;' title='epithet'/>
					</div>
					<div>
						<div style="float:left;width:140px;">Infrasp:</div>
						<input type='text' id='unitind3' name='unitind3' style='width:40px;border:inset;' title='Rank: e.g. ssp., var., f.'/>
						<input type='text' id='unitname3' name='unitname3' style='width:200px;border:inset;' title='infrasp. epithet'/>
					</div>
					<div>
						<div style="float:left;width:140px;">Parent Taxon:</div>
						<input type="text" id="parentname" name="parentname" style="width:200px;border:inset;" onchange="checkParentExistance(this.form)" />
						<span id="addparentspan" style="display:none;">
							<a id="addparentanchor" href="taxonomyloader.php?target=" target="_blank">Add Parent</a>
						</span>
						<input type="hidden" id="parenttid" name="parenttid" value="" />
					</div>
					<div id="uppertaxondiv" name="uppertaxondiv" style="margin-top:5px;position:relative;overflow:visible">
						<div style="float:left;width:140px;">Upper Taxon Grouping:</div>
						<input id="uppertaxonomy" name="uppertaxonomy" type="text" style="width:200px;border:inset;" />
					</div>
					<div>
						<div style="float:left;width:140px;">Notes:</div>
						<input type='text' id='notes' name='notes' style='width:200px;border:inset;' title=''/>
					</div>
					<div>
						<div style="float:left;width:140px;">Source:</div>
						<input type='text' id='source' name='source' style='width:200px;border:inset;' title=''/>
					</div>
					<div>
						<div style="float:left;width:140px;">Locality Security Status:</div>
						<select id="securitystatus" name="securitystatus" style='border:inset;'>
							<option value="0">No Security</option>
							<option value="1">Hide Locality Details</option>
						</select>
					</div>
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
					<div>
						<input type="hidden" name="submitaction" value="loadnewtaxon" />
						<input type="button" name="taxonsubmit" value="Submit New Name" onclick="submitLoadForm(this.form)" />
					</div>
				</fieldset>
			</form>
		</div>
		<?php 
		}
		else{
			echo "<div>You must be logged in and authorized to view this page. Please login.</div>";
		}
		include($serverRoot.'/footer.php');
		?>

</body>
</html>

