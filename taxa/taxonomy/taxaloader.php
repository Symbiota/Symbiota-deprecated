<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyUpload.php');
include_once($SERVER_ROOT.'/content/lang/taxa/taxonomy/taxaloader.'.$LANG_TAG.'.php');

header("Content-Type: text/html; charset=".$CHARSET);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl='.$CLIENT_ROOT.'/taxa/admin/taxaloader.php');
ini_set('max_execution_time', 3600);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$ulFileName = array_key_exists("ulfilename",$_REQUEST)?$_REQUEST["ulfilename"]:"";
$ulOverride = array_key_exists("uloverride",$_REQUEST)?$_REQUEST["uloverride"]:"";
$taxAuthId = (array_key_exists('taxauthid',$_REQUEST)?$_REQUEST['taxauthid']:1);

$isEditor = false;
if($IS_ADMIN || array_key_exists("Taxonomy",$USER_RIGHTS)){
	$isEditor = true;
}

$loaderManager = new TaxonomyUpload();
$loaderManager->setTaxaAuthId($taxAuthId);

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
	<title><?php echo $DEFAULT_TITLE . " " . $LANG['TAXA_LOADER']; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>" />
	<link href="../../css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
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
		<a href="../../index.php"><?php echo $LANG['HOME']; ?></a> &gt;&gt;
		<a href="taxonomydisplay.php"><b><?php echo $LANG['TAXONOMIC_TREE_VIEWER']; ?></b></a> &gt;&gt;
		<a href="taxaloader.php"><b><?php echo $LANG['TAXA_BATCH_LOADER']; ?></b></a>
	</div>
	<?php
}

if($isEditor){
	?>
	<div id="innertext">
		<h1><?php echo $LANG['TAXA_NAME'];?></h1>
		<div style="margin:30px;">
			<div style="margin-bottom:30px;">
				<?php echo $LANG['TAXA_ADMIN'];?> <a href="http://symbiota.org/docs/loading-taxonomic-data/"> <?php echo $LANG['SYM_DOC'];?></a>
				<?php echo $LANG['PAGES_DETAILS'];?>
			</div>
			<?php
			if($action == 'Map Input File' || $action == 'Verify Mapping'){
				?>
				<form name="mapform" action="taxaloader.php" method="post">
					<fieldset style="width:90%;">
						<legend style="font-weight:bold;font-size:120%;"><?php echo $LANG['TAXA_UP'];?></legend>
						<div style="margin:10px;">
						</div>
						<table style="border:1px solid black">
							<tr>
								<th>
									<?php echo $LANG['SOURCE_FIELD'];?>
								</th>
								<th>
									<?php echo $LANG['TARGET_FIELD'];?>
								</th>
							</tr>
							<?php
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
											<option value=""><?php echo $LANG['FIELD_UNMAPPED']; ?></option>
											<option value="">-------------------------</option>
											<?php
											$mappedTarget = (array_key_exists($sField,$fieldMap)?$fieldMap[$sField]:"");
											$selStr = "";
											if($mappedTarget=="unmapped") $selStr = "SELECTED";
											echo "<option value='unmapped' ".$selStr.">" . $LANG['LEAVE_FIELD_UNMAPPED'] . "</option>";
											if($selStr){
												$selStr = 0;
											}
											foreach($tArr as $k => $tField){
												if($selStr !== 0 && $tField == "scinameinput" && (strtolower($sField == "sciname") || strtolower($sField) == "scientific name")){
													$selStr = "SELECTED";
												}
												elseif($selStr !== 0 && $mappedTarget && $mappedTarget == $tField){
													$selStr = "SELECTED";
												}
												elseif($selStr !== 0 && $tField==$sField && $tField != "sciname"){
													$selStr = "SELECTED";
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
							<?php echo $LANG['VERIFY_FIELD'];?>
						</div>
						<div style="margin:10px;">
							<input type="submit" name="action" value="Verify Mapping" />
							<input type="submit" name="action" value="Upload Taxa" />
							<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId;?>" />
							<input type="hidden" name="ulfilename" value="<?php echo $loaderManager->getFileName();?>" />
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
				<form name="transferform" action="taxaloader.php" method="post" onsubmit="return checkTransferForm(this)">
					<fieldset style="width:450px;">
						<legend style="font-weight:bold;font-size:120%;"><?php echo $LANG['TRANSFER_TAXA_TO_CENTRAL_TABLE']; ?></legend>
						<div style="margin:10px;">
							<?php echo $LANG['REVIEW_UPLOAD_STATISTICS']; ?>
						</div>
						<div style="margin:10px;">
							<?php
							$statArr = $loaderManager->getStatArr();
							if($statArr){
								if(isset($statArr['upload'])) echo '<u>' . $LANG['TAXA_UPLOADED'] . '</u>: <b>'.$statArr['upload'].'</b><br/>';
								echo '<u>'.$LANG['TOTAL_TAXA'].'</u>: <b>'.$statArr['total'].'</b> ('.$LANG['INCLUDES_NEW_PARENT_TAXA'].')<br/>';
								echo '<u>'.$LANG['TAXA_ALREADY'].'</u>: <b>'.(isset($statArr['exist'])?$statArr['exist']:0).'</b><br/>';
								echo '<u>'.$LANG['NEW_TAXA'].'</u>: <b>'.(isset($statArr['new'])?$statArr['new']:0).'</b><br/>';
								echo '<u>'.$LANG['ACCEPTED_TAXA'].'</u>: <b>'.(isset($statArr['accepted'])?$statArr['accepted']:0).'</b><br/>';
								echo '<u>'.$LANG['NON_ACCEPTED'].'</u>: <b>'.(isset($statArr['nonaccepted'])?$statArr['nonaccepted']:0).'</b><br/>';
								if(isset($statArr['bad'])){
									?>
									<fieldset style="margin:15px;padding:15px;">
										<legend><b><?php echo $LANG['PROBLEMATIC_TAXA']; ?></b></legend>
										<div style="margin-bottom:10px">
											<?php echo $LANG['THESE_TAXA_ARE_MARKED_AS_FAILED']; ?>
										</div>
										<?php
										foreach($statArr['bad'] as $msg => $cnt){
											echo '<div style="margin-left:10px"><u>'.$msg.'</u>: <b>'.$cnt.'</b></div>';
										}
										?>
									</fieldset>
									<?php
								}
							}
							else{
								echo $LANG['UPLOAD_STATISTICS_ARE_UNAVAILABLE'];
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
							<input type="submit" name="action" value="Activate Taxa" />
						</div>
						<div style="float:right;margin:10px;">
							<a href="taxaloader.php?action=downloadcsv" target="_blank"><?php echo $LANG['DOWNLOAD_CSV_TAXA_FILE']; ?></a>
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
					<form name="uploadform" action="taxaloader.php" method="post" enctype="multipart/form-data" onsubmit="return verifyUploadForm(this)">
						<fieldset style="width:90%;">
							<legend style="font-weight:bold;font-size:120%;"> <?php echo $LANG['TAXA_UP'];?> </legend>
							<div style="margin:10px;">
								<?php echo $LANG['FLAT_STRUCT']; ?>

							</div>
							<input type='hidden' name='MAX_FILE_SIZE' value='100000000' />
							<div>
								<div class="overrideopt">
									<b><?php echo $LANG['UPLOAD_FILE'];?></b>
									<div style="margin:10px;">
										<input id="genuploadfile" name="uploadfile" type="file" size="40" />
									</div>
								</div>
								<div class="overrideopt" style="display:none;">
									<b><?php echo $LANG['FULL_FILE'];?></b>
									<div style="margin:10px;">
										<input name="uloverride" type="text" size="50" /><br/>
										<?php echo $LANG['OPTION_MAN'];?>
									</div>
								</div>
								<div style="margin:10px;">
									<?php echo $LANG['TARGET_THES'];?>
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
									<input type="hidden" name="action" value="Map Input File" /> 
									<input type="submit" value="<?php echo $LANG['MAP_INPUT_FILE']; ?>" />
								</div>
								<div style="float:right;" >
									<a href="#" onclick="toggle('overrideopt');return false;"><?php echo $LANG['TOGGLE_MAN'];?></a>
								</div>
							</div>
						</fieldset>
					</form>
				</div>
				<div>
					<form name="itisuploadform" action="taxaloader.php" method="post" enctype="multipart/form-data" onsubmit="return verifyItisUploadForm(this)">
						<fieldset style="width:90%;">
							<legend style="font-weight:bold;font-size:120%;"><?php echo $LANG['UPLOAD_ITIS'];?></legend>
							<div style="margin:10px;">
								<?php echo $LANG['ITIS_DATA'];?> <a href="http://www.itis.gov/access.html" target="_blank"> <?php echo $LANG['ITIS_DOWNLOAD'];?></a> <?php echo $LANG['CAN_UPLOADED'];?> <a href="CyprinidaeItisExample.bin"> <?php echo $LANG['FILE_BIN'];?></a>).
								<?php echo $LANG['LEGEND'];?>
							</div>
							<input type='hidden' name='MAX_FILE_SIZE' value='100000000' />
							<div class="itisoverrideopt">
								<b><?php echo $LANG['UPLOAD_FILE'];?></b>
								<div style="margin:10px;">
									<input id="itisuploadfile" name="uploadfile" type="file" size="40" />
								</div>
							</div>
							<div class="itisoverrideopt" style="display:none;">
								<b><?php echo $LANG['FULL_FILE'];?>Full File Path:</b>
								<div style="margin:10px;">
									<input name="uloverride" type="text" size="50" /><br/>
									<?php echo $LANG['OPTION_MAN'];?>
								</div>
							</div>
							<div style="margin:10px;">
								<input type="hidden" name="action" value="Upload ITIS File" />
								<input type="submit" value="<?php echo $LANG['UPLOAD_ITIS_FILE']; ?>" />
							</div>
							<div style="float:right;">
								<a href="#" onclick="toggle('itisoverrideopt');return false;"><?php echo $LANG['TOGGLE_MAN'];?></a>
							</div>
						</fieldset>
					</form>
				</div>
				<div>
					<form name="analyzeform" action="taxaloader.php" method="post">
						<fieldset style="width:90%;">
							<legend style="font-weight:bold;font-size:120%;"><?php echo $LANG['CLEAN_ANA'];?></legend>
							<div style="margin:10px;">
								<?php echo $LANG['LEGEND2'];?>
							</div>
							<div style="margin:10px;">
								<?php echo $LANG['TARGET_THES'];?>
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
								<input type="hidden" name="action" value="Analyze Taxa" />
								<input type="submit" value="<?php echo $LANG['ANALYZE_TAXA']; ?>" />
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
