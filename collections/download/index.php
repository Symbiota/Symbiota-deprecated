<?php
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$CHARSET);

$sourcePage = array_key_exists("sourcepage",$_REQUEST)?$_REQUEST["sourcepage"]:"specimen";
$downloadType = array_key_exists("dltype",$_REQUEST)?$_REQUEST["dltype"]:"specimen";
$taxonFilterCode = array_key_exists("taxonFilterCode",$_REQUEST)?$_REQUEST["taxonFilterCode"]:0;
$displayHeader = array_key_exists("displayheader",$_REQUEST)?$_REQUEST["displayheader"]:0;

$searchVar = $_REQUEST['searchvar'];
?>
<html>
<head>
	<title>Collections Search Download</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
	<script>
		$(document).ready(function() {
			var dialogArr = new Array("schemanative","schemadwc");
			var dialogStr = "";
			for(i=0;i<dialogArr.length;i++){
				dialogStr = dialogArr[i]+"info";
				$( "#"+dialogStr+"dialog" ).dialog({
					autoOpen: false,
					modal: true,
					position: { my: "left top", at: "center", of: "#"+dialogStr }
				});

				$( "#"+dialogStr ).click(function() {
					$( "#"+this.id+"dialog" ).dialog( "open" );
				});
			}

			<?php
			if(!$searchVar){
				?>
				if(sessionStorage.querystr){
					window.location = "index.php?"+sessionStorage.querystr;
				}
				<?php
			}
			?>
		});

		function extensionSelected(obj){
			if(obj.checked == true){
				obj.form.zip.checked = true;
			}
		}

		function zipSelected(obj){
			if(obj.checked == false){
				obj.form.images.checked = false;
				obj.form.identifications.checked = false;
			}
		}

		function validateDownloadForm(f){
			return true;
		}

		function closePage(timeToClose){
			setTimeout(function () {
				window.close();
			}, timeToClose);
		}
	</script>
</head>
<body>
	<?php
	if($displayHeader){
		$displayLeftMenu = (isset($collections_download_downloadMenu)?$collections_download_downloadMenu:false);
		include($SERVER_ROOT.'/header.php');
		if(isset($collections_download_downloadCrumbs)){
			if($collections_download_downloadCrumbs){
				?>
				<div class='navpath'>
					<?php echo $collections_download_downloadCrumbs; ?>
					<b>Specimen Download</b>
				</div>
				<?php
			}
		}
		else{
			?>
			<div class="navpath">
				<a href="../../index.php">Home</a> &gt;&gt;
				<a href="#" onclick="closePage(0)">Return to Search Page</a> &gt;&gt;
				<b>Occurrence Record Download</b>
			</div>
			<?php
		}
	}
	?>
	<div id="innertext" style="width:660px">
		<h2>Data Usage Guidelines</h2>
	 	 <div style="margin:15px;">
	 	 	By downloading data, the user confirms that he/she has read and agrees with the general
	 	 	<a href="../../misc/usagepolicy.php#images">data usage terms</a>.
	 	 	Note that additional terms of use specific to the individual collections
	 	 	may be distributed with the data download. When present, the terms
	 	 	supplied by the owning institution should take precedence over the
	 	 	general terms posted on the website.
	 	 </div>
		<div style='margin:30px;'>
			<form name="downloadform" action="downloadhandler.php" method="post" onsubmit="return validateDownloadForm(this);">
				<fieldset>
					<?php
					if($downloadType == 'checklist'){
						echo '<legend><b>Download Checklist</b></legend>';
					}
					elseif($downloadType == 'georef'){
						echo '<legend><b>Download Georeference Data</b></legend>';
					}
					else{
						echo '<legend><b>Download Specimen Records</b></legend>';
					}
					?>
					<table>
						<?php
						if($downloadType == 'specimen'){
							?>
							<tr>
								<td valign="top">
									<div style="margin:10px;">
										<b>Structure:</b>
									</div>
								</td>
								<td>
									<div style="margin:10px 0px;">
										<input type="radio" name="schema" value="symbiota" onclick="georefRadioClicked(this)" CHECKED />
										Symbiota Native
										<a id="schemanativeinfo" href="#" onclick="return false" title="More Information">
											<img src="../../images/info.png" style="width:13px;" />
										</a><br/>
										<div id="schemanativeinfodialog">
											Symbiota native is very similar to Darwin Core except with the addtion of a few fields
											such as substrate, associated collectors, verbatim description.
										</div>
										<input type="radio" name="schema" value="dwc" onclick="georefRadioClicked(this)" />
										Darwin Core
										<a id="schemadwcinfo" href="#" target="" title="More Information">
											<img src="../../images/info.png" style="width:13px;" />
										</a><br/>
										<div id="schemadwcinfodialog">
											Darwin Core (DwC) is a TDWG endorsed exchange standard specifically for biodiversity datasets.
											For more information on what data fields are included in DwC, visit the
											<a href="http://rs.tdwg.org/dwc/index.htm"target='_blank'>DwC Quick Reference Guide</a>.
										</div>
										*<a href='http://rs.tdwg.org/dwc/index.htm' class='bodylink' target='_blank'>What is Darwin Core?</a>
									</div>
								</td>
							</tr>
							<tr>
								<td valign="top">
									<div style="margin:10px;">
										<b>Data Extensions:</b>
									</div>
								</td>
								<td>
									<div style="margin:10px 0px;">
										<input type="checkbox" name="identifications" value="1" onchange="extensionSelected(this)" checked /> include Determination History<br/>
										<input type="checkbox" name="images" value="1" onchange="extensionSelected(this)" checked /> include Image Records<br/>
										<!--  <input type="checkbox" name="attributes" value="1" onchange="extensionSelected(this)" checked /> include Occurrence Trait Attributes (MeasurementOrFact extension)<br/>  -->
										*Output must be a compressed archive
									</div>
								</td>
							</tr>
							<?php
						}
						?>
						<tr>
							<td valign="top">
								<div style="margin:10px;">
									<b>File Format:</b>
								</div>
							</td>
							<td>
								<div style="margin:10px 0px;">
									<input type="radio" name="format" value="csv" CHECKED /> Comma Delimited (CSV)<br/>
									<input type="radio" name="format" value="tab" /> Tab Delimited<br/>
								</div>
							</td>
						</tr>
						<tr>
							<td valign="top">
								<div style="margin:10px;">
									<b>Character Set:</b>
								</div>
							</td>
							<td>
								<div style="margin:10px 0px;">
									<?php
									//$cSet = strtolower($CHARSET);
									$cSet = 'iso-8859-1';
									?>
									<input type="radio" name="cset" value="iso-8859-1" <?php echo ($cSet=='iso-8859-1'?'checked':''); ?> /> ISO-8859-1 (western)<br/>
									<input type="radio" name="cset" value="utf-8" <?php echo ($cSet=='utf-8'?'checked':''); ?> /> UTF-8 (unicode)
								</div>
							</td>
						</tr>
						<tr>
							<td valign="top">
								<div style="margin:10px;">
									<b>Compression:</b>
								</div>
							</td>
							<td>
								<div style="margin:10px 0px;">
									<input type="checkbox" name="zip" value="1" onchange="zipSelected(this)" checked />Compressed ZIP file<br/>
								</div>
							</td>
						</tr>
						<tr>
							<td colspan="2">
								<div style="margin:10px;">
									<?php
									if($downloadType == 'checklist'){
										echo '<input name="schema" type="hidden" value="checklist" />';
									}
									elseif($downloadType == 'georef'){
										echo '<input name="schema" type="hidden" value="georef" />';
									}
									?>
									<input name="publicsearch" type="hidden" value="1" />
									<input name="taxonFilterCode" type="hidden" value="<?php echo $taxonFilterCode; ?>" />
									<input name="sourcepage" type="hidden" value="<?php echo $sourcePage; ?>" />
									<input name="searchvar" type="hidden" value="<?php echo str_replace('"','&quot;',$searchVar); ?>" />
									<input type="submit" name="submitaction" value="Download Data" />
								</div>
							</td>
						</tr>
					</table>
				</fieldset>
			</form>
		</div>
	</div>
	<?php
	if($displayHeader) include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>