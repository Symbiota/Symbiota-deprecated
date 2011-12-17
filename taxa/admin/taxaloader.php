<?php
//error_reporting(E_ALL);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TaxaLoaderManager.php');

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$ulFileName = array_key_exists("ulfilename",$_REQUEST)?$_REQUEST["ulfilename"]:"";

$editable = false;
if($isAdmin || array_key_exists("Taxonomy",$userRights)){
	$editable = true;
}
	 
$loaderManager = new TaxaLoaderManager();

$status = "";
if($editable){
	if($action == "Upload ITIS File"){
		$loaderManager = new TaxaLoaderItisManager();
	}
	else{
		$loaderManager->setUploadFile($ulFileName);
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
	function checkItisUploadForm(f){
		if(f.uploadfile.value == ""){
			alert("Please enter a path value of the file you wish to upload");
			return false;
		}
		return true;
	}

	function checkUploadForm(f){
		var ulObj = document.getElementById("genuploadfile");
		if(ulObj != null){
			valueStr = ulObj.value;
			if(valueStr == ""){
				alert("Please enter a path value of the file you wish to upload");
				return false;
			}
			if(valueStr.indexOf(".csv") == -1 && valueStr.indexOf(".CSV") == -1 && valueStr.indexOf(".zip") == -1){
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
		if($action == "Upload ITIS File" || $action == 'Upload Taxa'){
			echo '<hr /><ul>';
			$loaderManager->uploadFile();
			echo '</ul><hr />';
		}
		elseif($action == "Activate Taxa"){
			echo '<hr /><ul>';
			$loaderManager->transferUpload();
			echo "<li>Taxa upload appears to have been successful.</li>";
			echo "<li>Go to <a href='taxonomydisplay.php'>Taxonomic Tree Search</a> page to query for a loaded name.</li>";
			echo '</ul><hr />';
		}
		elseif($action == "Clean and Transfer Taxa"){
			echo '<hr /><ul>';
			$loaderManager->cleanUpload();
			$loaderManager->transferUpload();
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
			<form name="uploadform" action="taxaloader.php" method="post" enctype="multipart/form-data" onsubmit="return checkUploadForm()">
				<fieldset style="width:450px;">
					<legend style="font-weight:bold;font-size:120%;">Taxa Upload Form</legend>
					<div style="margin:10px;">
						Flat structured, CSV (comma delimited) text files can be uploaded here. 
						To upload an Excel file, save as a CSV file. 
						Scientific name is the only required field. 
					</div>
					<input type="hidden" name="ulfilename" value="<?php echo $loaderManager->getUploadFileName();?>" />
					<?php if(!$loaderManager->getUploadFileName()){ ?>
						<input type='hidden' name='MAX_FILE_SIZE' value='10000000' />
						<div>
							<b>Upload File:</b>
							<div style="margin:10px;">
								<input id="genuploadfile" name="uploadfile" type="file" size="40" />
							</div>
							<div style="margin:10px;">
								<input type="submit" name="action" value="Analyze Input File" />
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
													elseif($selStr !== 0 && $tField==$sField){
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
			<form name="itisuploadform" action="taxaloader.php" method="post" enctype="multipart/form-data" onsubmit="return checkItisUploadForm()">
				<fieldset style="width:450px;">
					<legend style="font-weight:bold;font-size:120%;">ITIS Upload File</legend>
					<div style="margin:10px;">
						ITIS data files downloaded from the <a href="http://www.itis.gov/access.html">ITIS Download Page</a> ca be uploaded
						using this function. If the ITIS download file includes synonyms 
						and vernaculars, this data will also be incorporated into the upload process.
					</div>
					<input type='hidden' name='MAX_FILE_SIZE' value='100000000' />
					<div>
						<b>Upload File:</b>
						<div style="margin:10px;">
							<input id="itisuploadfile" name="uploadfile" type="file" size="40" />
						</div>
					</div>
					<div style="margin:10px;">
						<input type="submit" name="action" value="Upload ITIS File" />
					</div>
				</fieldset>
			</form>
		</div>
		<div>
			<form name="cleantransferform" action="taxaloader.php" method="post">
				<fieldset style="width:450px;">
					<legend style="font-weight:bold;font-size:120%;">Clean and Transfer Taxa To Central Table</legend>
					<div style="margin:10px;">
						If taxa information was loaded into the UploadTaxa table using other means, 
						one can use this form to clean and transfer the taxa names into the taxonomic tables (taxa, tastatus).  
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
