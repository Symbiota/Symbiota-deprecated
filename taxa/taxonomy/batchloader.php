<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyUpload.php');
header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl='.$CLIENT_ROOT.'/taxa/taxonomy/batchloader.php');
ini_set('max_execution_time', 3600);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:'';
$ulFileName = array_key_exists("ulfilename",$_REQUEST)?$_REQUEST["ulfilename"]:'';
$ulOverride = array_key_exists("uloverride",$_REQUEST)?$_REQUEST["uloverride"]:'';
$taxAuthId = (array_key_exists('taxauthid',$_REQUEST)?$_REQUEST['taxauthid']:1);
$kingdomName = (array_key_exists('kingdomname',$_REQUEST)?$_REQUEST['kingdomname']:'');

$isEditor = false;
if($IS_ADMIN || array_key_exists("Taxonomy",$USER_RIGHTS)){
	$isEditor = true;
}

$loaderManager = new TaxonomyUpload();
$loaderManager->setTaxaAuthId($taxAuthId);
$loaderManager->setKingdomName($kingdomName);

$status = "";
$fieldMap = Array();
if($isEditor){
	if($ulFileName){
		$loaderManager->setFileName($ulFileName);
	}
	else{
		$loaderManager->setUploadFile($ulOverride);
	}

	if(array_key_exists("sf",$_REQUEST)){
		//Grab field mapping, if mapping form was submitted
 		$targetFields = $_REQUEST["tf"];
 		$sourceFields = $_REQUEST["sf"];
		for($x = 0;$x<count($targetFields);$x++){
			if($targetFields[$x] && $sourceFields[$x]) $fieldMap[$sourceFields[$x]] = $targetFields[$x];
		}
	}

	if($action == 'downloadcsv'){
		$loaderManager->exportUploadTaxa();
		exit;
	}
}
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Taxa Loader</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
		function toggle(target){
			var tDiv = document.getElementById(target);
			if(tDiv != null){
				if(tDiv.style.display=="none"){
					tDiv.style.display="block";
				}
			 	else {
			 		tDiv.style.display="none";
			 	}
			}
			else{
			  	var divs = document.getElementsByTagName("div");
			  	for (var i = 0; i < divs.length; i++) {
			  	var divObj = divs[i];
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

		function verifyItisUploadForm(f){
			if(f.uploadfile.value == "" && f.uloverride.value == ""){
				alert("Please enter a path value of the file you wish to upload");
				return false;
			}
			return true;
		}

		function verifyUploadForm(f){
			var inputValue = f.uploadfile.value;
			if(inputValue == "") inputValue = f.uloverride.value;
			if(inputValue == ""){
				alert("Please enter a path value of the file you wish to upload");
				return false;
			}
			else{
				if(inputValue.indexOf(".csv") == -1 && inputValue.indexOf(".CSV") == -1 && inputValue.indexOf(".zip") == -1){
					alert("Upload file must be a CSV or ZIP file");
					return false;
				}
			}
			if(f.kingdomname.value == ""){
				alert("Select a Target Kingdom");
				return false;
			}
			return true;
		}

		function verifyMapForm(f){
			var sfArr = [];
			var tfArr = [];
			for(var i=0;i<f.length;i++){
				var obj = f.elements[i];
				if(obj.name == "sf[]"){
					if(sfArr.indexOf(obj.value) > -1){
						alert("ERROR: Source field names must be unique (duplicate field: "+obj.value+")");
						return false;
					}
					sfArr[sfArr.length] = obj.value;
				}
				else if(obj.value != "" && obj.value != "unmapped"){
					if(obj.name == "tf[]"){
						if(tfArr.indexOf(obj.value) > -1){
							alert("ERROR: Can't map to the same target field more than once ("+obj.value+")");
							return false;
						}
						tfArr[tfArr.length] = obj.value;
					}
				}
			}
			return true;
		}

		function checkTransferForm(f){
			return true;
		}
	</script>
</head>
<body>
<?php
$displayLeftMenu = (isset($taxa_admin_taxaloaderMenu)?$taxa_admin_taxaloaderMenu:false);
include($SERVER_ROOT.'/header.php');
if(isset($taxa_admin_taxaloaderCrumbs)){
	if($taxa_admin_taxaloaderCrumbs){
		echo '<div class="navpath">';
		echo $taxa_admin_taxaloaderCrumbs;
		echo ' <b>Taxa Batch Loader</b>';
		echo '</div>';
	}
}
else{
	?>
	<div class="navpath">
		<a href="../../index.php">Home</a> &gt;&gt;
		<a href="taxonomydisplay.php"><b>Taxonomic Tree Viewer</b></a> &gt;&gt;
		<a href="batchloader.php"><b>Taxa Batch Loader</b></a>
	</div>
	<?php
}

if($isEditor){
	?>
	<div id="innertext">
		<h1>Taxonomic Name Batch Loader</h1>
		<div style="margin:30px;">
			<div style="margin-bottom:30px;">
				This page allows a Taxonomic Administrator to batch upload taxonomic data files.
				See <a href="http://symbiota.org/docs/loading-taxonomic-data/">Symbiota Documentation</a>
				pages for more details on the Taxonomic Thesaurus layout.
			</div>
			<?php
			if($action == 'Map Input File' || $action == 'Verify Mapping'){
				?>
				<form name="mapform" action="batchloader.php" method="post" onsubmit="return verifyMapForm(this)">
					<fieldset style="width:90%;">
						<legend style="font-weight:bold;font-size:120%;">Taxa Upload Form</legend>
						<div style="margin:10px;">
						</div>
						<table class="styledtable" style="width:450px">
							<tr>
								<th>
									Source Field
								</th>
								<th>
									Target Field
								</th>
							</tr>
							<?php
							$translationMap = array('phylum'=>'division','division'=>'phylum','sciname'=>'scinameinput','scientificname'=>'scinameinput',
								'scientificnameauthorship'=>'author','acceptedname'=>'acceptedstr','vernacularname'=>'vernacular');
							$sArr = $loaderManager->getSourceArr();
							$tArr = $loaderManager->getTargetArr();
							asort($tArr);
							foreach($sArr as $sField){
								?>
								<tr>
									<td style='padding:2px;'>
										<?php echo $sField; ?>
										<input type="hidden" name="sf[]" value="<?php echo $sField; ?>" />
									</td>
									<td>
										<select name="tf[]" style="background:<?php echo (array_key_exists($sField,$fieldMap)?"":"yellow");?>">
											<option value="">Field Unmapped</option>
											<option value="">-------------------------</option>
											<?php
											$mappedTarget = (array_key_exists($sField,$fieldMap)?$fieldMap[$sField]:"");
											$selStr = "";
											if($mappedTarget=="unmapped") $selStr = "SELECTED";
											echo "<option value='unmapped' ".$selStr.">Leave Field Unmapped</option>";
											if($selStr){
												$selStr = 0;
											}
											foreach($tArr as $k => $tField){
												if($selStr !== 0){
													$sTestField = str_replace(array(' ','_'), '', $sField);
													if($mappedTarget && $mappedTarget == $tField){
														$selStr = "SELECTED";
													}
													elseif($tField==$sTestField && $tField != "sciname"){
														$selStr = "SELECTED";
													}
													elseif(isset($translationMap[strtolower($sTestField)]) && $translationMap[strtolower($sTestField)] == $tField){
														$selStr = "SELECTED";
													}
												}
												echo '<option value="'.$k.'" '.($selStr?$selStr:'').'>'.$tField."</option>\n";
												if($selStr){
													$selStr = 0;
												}
											}
											?>
										</select>
									</td>
								</tr>
								<?php
							}
							?>
						</table>
						<div>
							* Fields in yellow have not yet been verified
						</div>
						<div style="margin-top:10px">
							<b>Target Kingdom:</b> <?php echo $kingdomName; ?><br/>
							<b>Target Thesaurus:</b> <?php echo $loaderManager->getTaxAuthorityName(); ?>
						</div>
						<div style="margin:10px;">
							<input type="submit" name="action" value="Verify Mapping" />
							<input type="submit" name="action" value="Upload Taxa" />
							<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId;?>" />
							<input type="hidden" name="ulfilename" value="<?php echo $loaderManager->getFileName();?>" />
							<input type="hidden" name="kingdomname" value="<?php echo $kingdomName; ?>" />
						</div>
					</fieldset>
				</form>
				<?php
			}
			elseif(substr($action,0,6) == 'Upload' || $action == 'Analyze Taxa'){
				echo '<ul>';
				if($action == 'Upload Taxa'){
					$loaderManager->loadFile($fieldMap);
					$loaderManager->cleanUpload();
				}
				elseif($action == "Upload ITIS File"){
					$loaderManager->loadItisFile($fieldMap);
					$loaderManager->cleanUpload();
				}
				elseif($action == 'Analyze Taxa'){
					$loaderManager->cleanUpload();
				}
				$reportArr = $loaderManager->analysisUpload();
				echo '</ul>';
				?>
				<form name="transferform" action="batchloader.php" method="post" onsubmit="return checkTransferForm(this)">
					<fieldset style="width:450px;">
						<legend style="font-weight:bold;font-size:120%;">Transfer Taxa To Central Table</legend>
						<div style="margin:10px;">
							Review upload statistics below before activating. Use the download option to review and/or adjust for reload if necessary.
						</div>
						<div style="margin:10px">
							Target Kingdom: <b><?php echo $kingdomName; ?></b><br/>
							Target Thesaurus: <b><?php echo $loaderManager->getTaxAuthorityName(); ?></b>
						</div>
						<div style="margin:10px;">
							<?php
							$statArr = $loaderManager->getStatArr();
							if($statArr){
								if(isset($statArr['upload'])) echo 'Taxa uploaded: <b>'.$statArr['upload'].'</b><br/>';
								echo 'Total taxa: <b>'.$statArr['total'].'</b> (includes new parent taxa)<br/>';
								echo 'Taxa already in thesaurus: <b>'.(isset($statArr['exist'])?$statArr['exist']:0).'</b><br/>';
								echo 'New taxa: <b>'.(isset($statArr['new'])?$statArr['new']:0).'</b><br/>';
								echo 'Accepted taxa: <b>'.(isset($statArr['accepted'])?$statArr['accepted']:0).'</b><br/>';
								echo 'Non-accepted taxa: <b>'.(isset($statArr['nonaccepted'])?$statArr['nonaccepted']:0).'</b><br/>';
								if(isset($statArr['bad'])){
									?>
									<fieldset style="margin:15px;padding:15px;">
										<legend><b>Problematic taxa</b></legend>
										<div style="margin-bottom:10px">
											These taxa are marked as FAILED and will not load until problems have been resolved.
											You may want to download the data (link below), fix the bad relationships, and then reload.
										</div>
										<?php
										foreach($statArr['bad'] as $msg => $cnt){
											echo '<div style="margin-left:10px">'.$msg.': <b>'.$cnt.'</b></div>';
										}
										?>
									</fieldset>
									<?php
								}
							}
							else{
								echo 'Upload statistics are unavailable';
							}
							?>
						</div>
						<!--
						<div style="margin:10px;">
							Target Thesaurus:
							<select name="taxauthid">
								<?php
								$taxonAuthArr = $loaderManager->getTaxAuthorityArr();
								foreach($taxonAuthArr as $k => $v){
									echo '<option value="'.$k.'" '.($k==$taxAuthId?'SELECTED':'').'>'.$v.'</option>'."\n";
								}
								?>
							</select>
						</div>
						-->
						<div style="margin:10px;">
							<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId;?>" />
							<input name="kingdomname" type="hidden" value="<?php echo $kingdomName; ?>" />
							<input type="submit" name="action" value="Activate Taxa" />
						</div>
						<div style="float:right;margin:10px;">
							<a href="batchloader.php?action=downloadcsv" target="_blank">Download CSV Taxa File</a>
						</div>
					</fieldset>
				</form>
				<?php
			}
			elseif($action == "Activate Taxa"){
				echo '<ul>';
				$loaderManager->transferUpload($taxAuthId);
				echo "<li>Taxa upload appears to have been successful.</li>";
				echo "<li>Go to <a href='taxonomydisplay.php'>Taxonomic Tree Search</a> page to query for a loaded name.</li>";
				echo '</ul>';
			}
			else{
				?>
				<div>
					<form name="uploadform" action="batchloader.php" method="post" enctype="multipart/form-data" onsubmit="return verifyUploadForm(this)">
						<fieldset style="width:90%;">
							<legend style="font-weight:bold;font-size:120%;">Taxa Upload Form</legend>
							<div style="margin:10px;">
								Flat structured, CSV (comma delimited) text files can be uploaded here.
								Scientific name is the only required field below genus rank.
								However, family, author, and rankid (as defined in taxonunits table) are always advised.
								For upper level taxa, parents and rankids need to be included in order to build the taxonomic hierarchy.
								Large data files can be compressed as a ZIP file before import.
								If the file upload step fails without displaying an error message, it is possible that the
								file size exceeds the file upload limits set within your PHP installation (see your php configuration file).
							</div>
							<input type='hidden' name='MAX_FILE_SIZE' value='100000000' />
							<div>
								<div class="overrideopt">
									<div style="margin:10px;">
										<input id="genuploadfile" name="uploadfile" type="file" size="40" />
									</div>
								</div>
								<div class="overrideopt" style="display:none;">
									<b>Full File Path:</b>
									<div style="margin:10px;">
										<input name="uloverride" type="text" size="50" /><br/>
										* This option is for manual upload of a data file. Enter full path to data file located on working server.
									</div>
								</div>
								<div style="margin:10px;">
									<b>Target Thesaurus:</b>
									<select name="taxauthid">
										<?php
										$taxonAuthArr = $loaderManager->getTaxAuthorityArr();
										foreach($taxonAuthArr as $k => $v){
											echo '<option value="'.$k.'" '.($k==$taxAuthId?'SELECTED':'').'>'.$v.'</option>'."\n";
										}
										?>
									</select>
								</div>
								<div style="margin:10px;">
									<b>Target Kingdom:</b>
									<?php
									$kingdomArr = $loaderManager->getKingdomArr();
									if(count($kingdomArr) == 1){
										echo '<input name="kingdomname" type="hidden" value="'.array_shift($kingdomArr).'" />';
									}
									else{
										echo '<select name="kingdomname">';
										echo '<option value="">Select Kingdom</option>';
										echo '<option value="">----------------------</option>';
										foreach($kingdomArr as $k => $kingdomName){
											echo '<option>'.$kingdomName.'</option>';
										}
										echo '</select>';
									}
									?>
								</div>
								<div style="margin:10px;">
									<input type="submit" name="action" value="Map Input File" />
								</div>
								<div style="float:right;" >
									<a href="#" onclick="toggle('overrideopt');return false;">Toggle Manual Upload Option</a>
								</div>
							</div>
						</fieldset>
					</form>
				</div>
				<!--
				<div>
					<form name="itisuploadform" action="batchloader.php" method="post" enctype="multipart/form-data" onsubmit="return verifyItisUploadForm(this)">
						<fieldset style="width:90%;">
							<legend style="font-weight:bold;font-size:120%;">ITIS Upload File</legend>
							<div style="margin:10px;">
								ITIS data extract from the <a href="http://www.itis.gov/access.html" target="_blank">ITIS Download Page</a> can be uploaded
								using this function. Note that the file needs to be in their single file pipe-delimited format
								(example: <a href="CyprinidaeItisExample.bin">CyprinidaeItisExample.bin</a>).
								File might have .csv extension, even though it is NOT comma delimited.
								This upload option is not guaranteed to work if the ITIS download format change often.
								Large data files can be compressed as a ZIP file before import.
								If the file upload step fails without displaying an error message, it is possible that the
								file size exceeds the file upload limits set within your PHP installation (see your php configuration file).
								If synonyms and vernaculars are included, these data will also be incorporated into the upload process.
							</div>
							<input type='hidden' name='MAX_FILE_SIZE' value='100000000' />
							<div class="itisoverrideopt">
								<b>Upload File:</b>
								<div style="margin:10px;">
									<input id="itisuploadfile" name="uploadfile" type="file" size="40" />
								</div>
							</div>
							<div class="itisoverrideopt" style="display:none;">
								<b>Full File Path:</b>
								<div style="margin:10px;">
									<input name="uloverride" type="text" size="50" /><br/>
									* This option is for manual upload of a data file.
									Enter full path to data file located on working server.
								</div>
							</div>
							<div style="margin:10px;">
								<input type="submit" name="action" value="Upload ITIS File" />
							</div>
							<div style="float:right;">
								<a href="#" onclick="toggle('itisoverrideopt');return false;">Toggle Manual Upload Option</a>
							</div>
						</fieldset>
					</form>
				</div>
				-->
				<div>
					<form name="analyzeform" action="batchloader.php" method="post">
						<fieldset style="width:90%;">
							<legend style="font-weight:bold;font-size:120%;">Clean and Analyze</legend>
							<div style="margin:10px;">
								If taxa information was loaded into the UploadTaxa table using other means,
								one can use this form to clean and analyze taxa names in preparation to loading into the taxonomic tables (taxa, taxstatus).
							</div>
							<div style="margin:10px;">
								Target Thesaurus:
								<select name="taxauthid">
									<?php
									$taxonAuthArr = $loaderManager->getTaxAuthorityArr();
									foreach($taxonAuthArr as $k => $v){
										echo '<option value="'.$k.'" '.($k==$taxAuthId?'SELECTED':'').'>'.$v.'</option>'."\n";
									}
									?>
								</select>
							</div>
							<div style="margin:10px;">
								<b>Kingdom Target:</b>
								<?php
								$kingdomArr = $loaderManager->getKingdomArr();
								if(count($kingdomArr) == 1){
									echo '<input name="kingdomname" type="hidden" value="'.array_shift($kingdomArr).'" />';
								}
								else{
									echo '<select name="kingdomname">';
									foreach($kingdomArr as $k => $kingdomName){
										echo '<option>'.$kingdomName.'</option>';
									}
									echo '</select>';
								}
								?>
							</div>
							<div style="margin:10px;">
								<input type="submit" name="action" value="Analyze Taxa" />
							</div>
						</fieldset>
					</form>
				</div>
				<?php
			}
			?>
		</div>
	</div>
	<?php
}
else{
	?>
	<div style='font-weight:bold;margin:30px;'>
		You do not have permissions to batch upload taxonomic data
	</div>
	<?php
}
include($SERVER_ROOT.'/footer.php');
?>
</body>
</html>