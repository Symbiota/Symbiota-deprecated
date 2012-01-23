<?php
//error_reporting(E_ALL);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TaxaLoaderManager.php');

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$ulFileName = array_key_exists("ulfilename",$_REQUEST)?$_REQUEST["ulfilename"]:"";
$ulOverride = array_key_exists("uloverride",$_REQUEST)?$_REQUEST["uloverride"]:"";

$editable = false;
if($isAdmin || array_key_exists("Taxonomy",$userRights)){
	$editable = true;
}

$loaderManager;
if($action == "Upload ITIS File"){
	$loaderManager = new TaxaLoaderItisManager();
}
else{
	$loaderManager = new TaxaLoaderManager();
}

$status = "";
if($editable){
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
 		$fieldMap = Array();
		for($x = 0;$x<count($targetFields);$x++){
			if($targetFields[$x] && $sourceFields[$x]) $fieldMap[$sourceFields[$x]] = $targetFields[$x];
		}
		$loaderManager->setFieldMap($fieldMap);
	}
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<title><?php echo $defaultTitle; ?> Taxa Loader</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>" />
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
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
include($serverRoot.'/header.php');
if(isset($taxa_admin_taxaloaderCrumbs)){
	echo "<div class='navpath'>";
	echo "<a href='../index.php'>Home</a> &gt; ";
	echo $taxa_admin_taxaloaderCrumbs;
	echo " <b>Taxa Loader</b>"; 
	echo "</div>";
}

if($editable){
?>
<h1>Taxonomic Name Batch Loader</h1>
<div style="margin:30px;">
	<div style="margin-bottom:30px;">
		This page allows a Taxonomic Administrator to batch upload taxonomic data files. 
		See <a href="">Symbiota Documentation</a> pages for more details on the Taxonomic Thesaurus layout.
	</div> 
	<?php 
	if($editable){
		$taxAuthId = (array_key_exists('taxauthid',$_REQUEST)?$_REQUEST['taxauthid']:1);
		if($action == "Upload ITIS File" || $action == 'Upload Taxa'){
			echo '<hr /><ul>';
			$loaderManager->loadFile();
			echo '</ul><hr />';
		}
		elseif($action == "Activate Taxa"){
			echo '<hr /><ul>';
			$loaderManager->transferUpload($taxAuthId);
			echo "<li>Taxa upload appears to have been successful.</li>";
			echo "<li>Go to <a href='taxonomydisplay.php'>Taxonomic Tree Search</a> page to query for a loaded name.</li>";
			echo '</ul><hr />';
		}
		elseif($action == "Clean and Transfer Taxa"){
			echo '<hr /><ul>';
			$loaderManager->cleanUpload();
			$loaderManager->transferUpload($taxAuthId);
			echo "Taxa apparently cleaned and loaded into the taxonomic hierarchy";
			echo '</ul><hr />';
		}
	}
		
	if(strpos($action,"Upload") !== false){
		?>
		<div>
			<form name="transferform" action="taxaloader.php" method="post" onsubmit="return checkTransferForm(this)">
				<fieldset style="width:450px;">
					<legend style="font-weight:bold;font-size:120%;">Transfer Taxa To Central Table</legend>
					<div style="margin:10px;">
						It appears that the taxa successfully inserted into the temporary 
						taxon upload table (&quot;uploadtaxa&quot;). 
						You may want to inspect these records before activating by 
						transferring to the central taxonomic tables  
						(&quot;taxa&quot;,&quot;taxstatus&quot;) and building the hierarchy. 
						If you feel confident of the data integrity, click the transfer button below.  
					</div>
					<div style="margin:10px;">
						Target Thesaurus: 
						<select name="taxauthid">
							<?php 
							$taxonAuthArr = $loaderManager->getTaxAuthorityArr(); 
							foreach($taxonAuthArr as $k => $v){
								echo '<option value="'.$k.'">'.$v.'</option>'."\n";
							}
							?>
						</select>
					</div>
					<div style="margin:10px;">
						<input type="submit" name="action" value="Activate Taxa" />
					</div>
				</fieldset>
			</form>
		</div>
		<?php 
	}
	else{
		?>
		<div>
			<form name="uploadform" action="taxaloader.php" method="post" enctype="multipart/form-data" onsubmit="return verifyUploadForm(this)">
				<fieldset style="width:90%;">
					<legend style="font-weight:bold;font-size:120%;">Taxa Upload Form</legend>
					<div style="margin:10px;">
						Flat structured, CSV (comma delimited) text files can be uploaded here. 
						Scientific name is the only required field below genus rank. 
						However, family, author, and rankid (as defined in taxonunits table) are always advised. 
						For upper level taxa, parents and rankids need to be included in order to build the taxonomic hierarchy.
						Large data files can be compressed as a ZIP file before import. 
						If the file upload step fails without displaying an error message, it is possible that the 
						file size excedes the file upload limits set within your PHP installation (see your php configuraton file).
					</div>
					<input type="hidden" name="ulfilename" value="<?php echo $loaderManager->getFileName();?>" />
					<?php if(!$loaderManager->getFileName()){ ?>
						<input type='hidden' name='MAX_FILE_SIZE' value='10000000' />
						<div>
							<div class="overrideopt">
								<b>Upload File:</b>
								<div style="margin:10px;">
									<input id="genuploadfile" name="uploadfile" type="file" size="40" />
								</div>
							</div>
							<div class="overrideopt" style="display:none;">
								<b>Full File Path:</b> 
								<div style="margin:10px;">
									<input name="uloverride" type="text" size="50" /><br/>
									* This option is for manual upload of a data file. 
									Enter full path to data file located on working server.
								</div>   
							</div>
							<div style="margin:10px;">
								<input type="submit" name="action" value="Analyze Input File" />
							</div>
							<div style="float:right;" >
								<a href="#" onclick="toggle('overrideopt');return false;">Toggle Manual Upload Option</a>
							</div>
						</div>
					<?php }else{ ?>
						<div id="mdiv" style="margin:15px;">
							<table border="1" cellpadding="2" style="border:1px solid black">
								<tr>
									<th>
										Source Field
									</th>
									<th>
										Target Field
									</th>
								</tr>
								<?php
								$sArr = $loaderManager->getSourceArr();
								$tArr = $loaderManager->getTargetArr();
								sort($tArr);
								$fMap = $loaderManager->getFieldMap();
								foreach($sArr as $sField){
									?>
									<tr>
										<td style='padding:2px;'>
											<?php echo $sField; ?>
											<input type="hidden" name="sf[]" value="<?php echo $sField; ?>" />
										</td>
										<td>
											<select name="tf[]" style="background:<?php echo (array_key_exists($sField,$fMap)?"":"yellow");?>">
												<option value="">Field Unmapped</option>
												<option value="">-------------------------</option>
												<?php 
												$mappedTarget = (array_key_exists($sField,$fMap)?$fMap[$sField]:"");
												$selStr = "";
												if($mappedTarget=="unmapped") $selStr = "SELECTED";
												echo "<option value='unmapped' ".$selStr.">Leave Field Unmapped</option>";
												if($selStr){
													$selStr = 0;
												}
												foreach($tArr as $tField){
													if($selStr !== 0 && $tField == "scinameinput" && (strtolower($sField == "sciname") || strtolower($sField) == "scientific name")){
														$selStr = "SELECTED";
													}
													elseif($selStr !== 0 && $mappedTarget && $mappedTarget == $tField){
														$selStr = "SELECTED";
													}
													elseif($selStr !== 0 && $tField==$sField && $tField != "sciname"){
														$selStr = "SELECTED";
													}
													echo '<option '.($selStr?$selStr:'').'>'.$tField."</option>\n";
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
							<div style="margin:10px;">
								<input type="submit" name="action" value="Verify Mapping" />
								<input type="submit" name="action" value="Upload Taxa" />
							</div>
						</div>
					<?php } ?>
				</fieldset>
			</form>
		</div>
		<div>
			<form name="itisuploadform" action="taxaloader.php" method="post" enctype="multipart/form-data" onsubmit="return verifyItisUploadForm(this)">
				<fieldset style="width:90%;">
					<legend style="font-weight:bold;font-size:120%;">ITIS Upload File</legend>
					<div style="margin:10px;">
						ITIS data extract from the <a href="http://www.itis.gov/access.html" target="_blank">ITIS Download Page</a> can be uploaded
						using this function. Note that the file needs to be in their single file format (.bin).
						If you are looking for a full kingdom extraction, follow the "Download a specific taxonomic group" link and
						a link to a data extract will be emailed to you.   
						Large data files can be compressed as a ZIP file before import. 
						If the file upload step fails without displaying an error message, it is possible that the 
						file size excedes the file upload limits set within your PHP installation (see your php configuraton file).
						Note that if synonyms and vernaculars are included, these data will also be incorporated into the upload process.
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
		<div>
			<form name="cleantransferform" action="taxaloader.php" method="post">
				<fieldset style="width:90%;">
					<legend style="font-weight:bold;font-size:120%;">Clean and Transfer Taxa To Central Table</legend>
					<div style="margin:10px;">
						If taxa information was loaded into the UploadTaxa table using other means, 
						one can use this form to clean and transfer the taxa names into the taxonomic tables (taxa, tastatus).  
					</div>
					<div style="margin:10px;">
						Target Thesaurus: 
						<select name="taxauthid">
							<?php 
							$taxonAuthArr = $loaderManager->getTaxAuthorityArr(); 
							foreach($taxonAuthArr as $k => $v){
								echo '<option value="'.$k.'">'.$v.'</option>'."\n";
							}
							?>
						</select>
					</div>
					<div style="margin:10px;">
						<input type="submit" name="action" value="Clean and Transfer Taxa" />
					</div>
				</fieldset>
			</form>
		</div>
		<?php 
	}
	?>
</div>
<?php  
}
else{
	?>
	<div style='font-weight:bold;margin:30px;'>
		You must login and have the correct permissions to upload taxonomic data.<br />
		Please 
		<a href="<?php echo $clientRoot; ?>/profile/index.php?refurl=<?php echo $clientRoot; ?>/taxa/admin/taxaloader.php">
			login
		</a>!
	</div>
	<?php 
}


include($serverRoot.'/footer.php');
?>

</body>
</html>
