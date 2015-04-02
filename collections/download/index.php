<?php
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);

$downloadType = array_key_exists("dltype",$_REQUEST)?$_REQUEST["dltype"]:"specimen"; 
$taxonFilterCode = array_key_exists("taxonFilterCode",$_REQUEST)?$_REQUEST["taxonFilterCode"]:0; 
?>
<html>
<head>
	<title>Collections Search Download</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
	<link href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
	<script language="javascript">
		$(function() {
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
	</script>
</head>
<body>
<?php
	$displayLeftMenu = (isset($collections_download_downloadMenu)?$collections_download_downloadMenu:false);
	include($serverRoot.'/header.php');
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
			<a href="../../index.php">Home</a> &gt; 
			<a href="../index.php">Collections</a> &gt; 
			<a href="../harvestparams.php">Search Criteria</a> &gt; 
			<a href="../list.php">Occurrence Listing</a> &gt; 
			<b>Specimen Download</b>
		</div>
		<?php 
	}
	?>

	<div id="innertext">
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
									$cSet = strtolower($charset);
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
									<input name="taxonfilter" type="hidden" value="<?php echo $taxonFilterCode; ?>" />
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
	include($serverRoot.'/footer.php');
?>
</body>

</html>
