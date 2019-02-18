<?php
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$CHARSET);

$clid = $_REQUEST['clid'];
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
	<div id="innertext">
		<h2>Data Usage Guidelines</h2>
	 	 <div style="margin:15px;">
	 	 	By downloading data, the user confirms that he/she has read and agrees with the general
	 	 	<a href="../../misc/usagepolicy.php#images">data usage terms</a>.
	 	 	Note that additional terms of use specific to the individual collections
	 	 	may be distributed with the data download. When present, the terms
	 	 	supplied by the owning institution should take precedence over the
	 	 	general terms posted in the above link.
	 	 </div>
		<div style='margin:30px;'>
			<form name="downloadform" action="downloadhandler.php" method="post" onsubmit="return validateDownloadForm(this);">
				<fieldset>
					<?php
					echo '<legend><b>Download Checklist Specimen Vouchers</b></legend>';
					?>
					<table>
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
									<input name="clid" type="hidden" value="<?php echo $clid; ?>" />
									<input type="submit" name="submitaction" value="Download Data" />
								</div>
							</td>
						</tr>
					</table>
				</fieldset>
			</form>
		</div>
	</div>
</body>
</html>