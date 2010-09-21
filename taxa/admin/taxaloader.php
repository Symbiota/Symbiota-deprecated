<?php
/*
* Author: E.E. Gilbert
* Sept 2010
*/

//error_reporting(E_ALL);
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
		$status = $loaderManager->uploadFile();
	}
	else{
		$loaderManager = new TaxaLoaderManager();
		if($ulFileName) $loaderManager->setUploadFile($ulFileName);
	}
	if($action == "Analyze Input File"){
		$loaderManager->setUploadFile();
	}
	elseif($action == "Reverify Mapping"){
		
	}
	elseif($action == "Activate Taxa"){
		$loaderManager->transferUpload();
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

		if($action == "Upload Taxa"){
			$status = $loaderManager->uploadFile();
		}
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
			alert("Plase enter a path value of the file you wish to upload");
			return false;
		}
		return true;
	}

	function checkUploadForm(f){
		var ulObj = document.getElementById("genuploadfile");
		if(ulObj != null && ulObj.value == ""){
			alert("Plase enter a path value of the file you wish to upload");
			return false;
		}
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
<div style="margin:30px;">
	<?php 
	if($status){
		echo '<div><ul>';
		echo $status;
		echo '</ul></div><hr/>';
	}
	if(strpos($action,"Upload") !== false){
		?>
		<div>
			<form name="transferform" action="taxaloader.php" method="post" onsubmit="return checkTransferForm()">
				<fieldset style="width:450px;">
					<legend style="font-weight:bold;font-size:120%;">Transfer Taxa To Central Table</legend>
					<div>
						It appears that the taxa successfully inserted into the temporary 
						taxon upload table (&quot;uploadtaxa&quot;). 
						You may want to inspect these records before activating by 
						transferring to the central taxonomic tables  
						(&quot;taxa&quot;,&quot;taxstatus&quot;) and building the hierarchy. 
						If you feel confident of the data integrity, click the transfer button below.  
					</div>
					<div>
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
						<div id="mdiv">
							<div>
								The following rules must be followed: 
							</div>
							<ul>
								<li>Scientific name, with or without author, is the only required field and must be mapped to &quot;scinameinput&quot;</li>
								<li>Following fields are not required, yet recommended: author, family, name of parent taxon (parentStr) or hierarchical structure, accepted name. See documentation for more details.</li>
								<li>If null, family will be determined from hierarchy or related taxa already in thesaurus</li>
								<li>If acceptance is not defined, all taxa will be assumed to be accepted</li>
								<li>Taxonomic Hierarchy</li>
								<ul>
									<li>In the absence of a hierarchical definition, family will be used to build hierarchy up to family rank, given that it is filled or can be determined from existant taxa</li>
									<li>If family does not yet exist in thesaurus and hierarchy is not defined in upload field, family will be linked directly to kingdom</li>
								</ul>
								<li>Do not map more than one source columns to the same target</li>
								<li>Having more than one source columns with the same name should be avoided</li>
							</ul>
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
								<input type="submit" name="action" value="Upload Taxa" />
								<input type="submit" name="action" value="Reverify Mapping" />
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
