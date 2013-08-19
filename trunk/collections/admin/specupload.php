<?php 
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecUploadBase.php');
include_once($serverRoot.'/classes/SpecUploadDirect.php');
include_once($serverRoot.'/classes/SpecUploadDigir.php');
include_once($serverRoot.'/classes/SpecUploadFile.php');
include_once($serverRoot.'/classes/SpecUploadDwca.php');
header("Content-Type: text/html; charset=".$charset);
if(!$symbUid) header('Location: ../../profile/index.php?refurl=../collections/admin/specuploadmanagement.php?'.$_SERVER['QUERY_STRING']);

$collId = $_REQUEST["collid"];
$uploadType = $_REQUEST["uploadtype"];
$uspid = array_key_exists("uspid",$_REQUEST)?$_REQUEST["uspid"]:0;
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$ulFileName = array_key_exists("ulfilename",$_REQUEST)?$_REQUEST["ulfilename"]:"";
$ulfnOverride = array_key_exists("ulfnoverride",$_REQUEST)?$_REQUEST["ulfnoverride"]:"";
$finalTransfer = array_key_exists("finaltransfer",$_REQUEST)?$_REQUEST["finaltransfer"]:0;
$dbpk = array_key_exists("dbpk",$_REQUEST)?$_REQUEST["dbpk"]:0;
$recStart = array_key_exists("recstart",$_REQUEST)?$_REQUEST["recstart"]:0;
$recLimit = array_key_exists("reclimit",$_REQUEST)?$_REQUEST["reclimit"]:1000;

$DIRECTUPLOAD = 1;$DIGIRUPLOAD = 2; $FILEUPLOAD = 3; $STOREDPROCEDURE = 4; $SCRIPTUPLOAD = 5;$DWCAUPLOAD = 6;

$duManager = new SpecUploadBase();
if($uploadType == $DIRECTUPLOAD){
	$duManager = new SpecUploadDirect();
}
elseif($uploadType == $DIGIRUPLOAD){
	$duManager = new SpecUploadDigir();
	$duManager->setSearchStart($recStart);
	$duManager->setSearchLimit($recLimit);
}
elseif($uploadType == $FILEUPLOAD){
	$duManager = new SpecUploadFile();
	if($ulfnOverride) $ulFileName = $ulfnOverride;
	$duManager->setUploadFileName($ulFileName);
}
elseif($uploadType == $DWCAUPLOAD){
	$duManager = new SpecUploadDwca();
	$duManager->setBaseFolderName($ulFileName);
}

$duManager->setCollId($collId);
$duManager->setUspid($uspid);

$statusStr = '';
$isEditor = 0;
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
	$isEditor = 1;
}
if($isEditor){
 	if($action == "Save Primary Key"){
 		$statusStr = $duManager->savePrimaryKey($dbpk);
 	}
}
$duManager->readUploadParameters();

//Grab field mapping, if mapping form was submitted
if(array_key_exists("sf",$_POST)){
	if($action == "Delete Field Mapping"){
		$statusStr = $duManager->deleteFieldMap();
	}
	else{
 		$targetFields = $_POST["tf"];
 		$sourceFields = $_POST["sf"];
 		$fieldMap = Array();
		for($x = 0;$x<count($targetFields);$x++){
			if($targetFields[$x]) $fieldMap[$targetFields[$x]]["field"] = $sourceFields[$x];
		}
		//Set Source PK
		if($dbpk) $fieldMap["dbpk"]["field"] = $dbpk;
 		$duManager->setFieldMap($fieldMap);
	}
	if($action == "Save Mapping"){
		$statusStr = $duManager->saveFieldMap();
	}
}
$duManager->loadFieldMap();
?>

<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Specimen Uploader</title>
	<link type="text/css" href="../../css/main.css" rel="stylesheet" />
	<script language=javascript>
		
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

		function verifyFileUploadForm(f){
			var fileName = "";
			if(f.uploadfile || f.ulfnoverride){
				if(f.uploadfile && f.uploadfile.value){
					 fileName = f.uploadfile.value;
				}
				else{
					fileName = f.ulfnoverride.value;
				}
				if(fileName == ""){
					alert("File path is empty. Please select the file that is to be loaded.");
					return false;
				}
				else{
					var ext = fileName.split('.').pop();
					if(ext != 'csv' && ext != 'CSV' && ext != 'zip' && ext != 'ZIP' && ext != 'txt' && ext != 'TXT' && ext != 'tab' && ext != 'tab'){
						alert("File must be comma separated (.csv), tab delimited (.txt or .tab), or a ZIP file (.zip)");
						return false;
					}
				}
			}
			return true;
		}

		function checkFinalTransferForm(){
			return confirm('Are you sure you want to transfer records from temporary table to central specimen table?');
		}

		function pkChanged(selObj){
			document.getElementById('pkdiv').style.display='block';
			document.getElementById('mdiv').style.display='none';
			document.getElementById('uldiv').style.display='none';
		}
	</script>
</head>
<body>
<?php
	$displayLeftMenu = (isset($collections_admin_specuploadMenu)?$collections_admin_specuploadMenu:false);
	include($serverRoot.'/header.php');
	if(isset($collections_admin_specuploadCrumbs)){
		if($collections_admin_specuploadCrumbs){
			?>
			<div class="navpath">
				<a href="../../index.php">Home</a> &gt;&gt;
				<?php echo $collections_admin_specuploadCrumbs; ?>
				<b>Specimen Loader</b> 
			</div>
			<?php 
		}
	}
	else{
		?>
		<div class="navpath">
			<a href="../../index.php">Home</a> &gt;&gt; 
			<a href="../misc/collprofiles.php?collid=<?php echo $collId; ?>&emode=1">Collection Management Panel</a> &gt;&gt; 
			<a href="specuploadmanagement.php?collid=<?php echo $collId; ?>">List of Upload Profiles</a> &gt;&gt; 
			<b>Specimen Loader</b> 
		</div>
		<?php 
	}
?> 
<!-- This is inner text! -->
<div id="innertext">
	<h1>Data Upload Module</h1>
	<?php
	if($statusStr){
		echo "<hr />";
		echo "<div>$statusStr</div>";
		echo "<hr />";
	}
	
	if($isEditor && $collId){
		//Grab collection name and last upload date and display for all
		echo '<div style="font-weight:bold;font-size:130%;">'.$duManager->getCollInfo('name').'</div>';
		echo '<div style="margin:0px 0px 15px 15px;"><b>Last Upload Date:</b> '.($duManager->getCollInfo('uploaddate')?$duManager->getCollInfo('uploaddate'):'not recorded').'</div>';

		if(($action == "Start Upload") || ($uploadType == $STOREDPROCEDURE) || ($uploadType == $SCRIPTUPLOAD)){
			//Upload records
	 		echo "<div style='font-weight:bold;font-size:120%'>Upload Status:</div>";
	 		echo "<ol style='margin:10px;font-weight:bold;'>";
	 		echo "<li style='font-weight:bold;'>Starting Data Upload</li>";
	 		$duManager->uploadData($finalTransfer);
			echo "</ol>";
	 		if($duManager->getTransferCount() && !$finalTransfer){
				?>
	 			<form name="finaltransferform" action="specupload.php" method="post" style="margin-top:10px;" onsubmit="return checkFinalTransferForm();">
	 				<fieldset>
	 					<legend><b>Final transfer</b></legend>
	 					<input type="hidden" name="collid" value="<?php echo $collId;?>" /> 
	 					<input type="hidden" name="uploadtype" value="<?php echo $uploadType;?>" />
	 					<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
	 					<div style="font-weight:bold;margin:5px;"> 
	 						Number of records uploaded to temporary table (uploadspectemp): <?php echo $duManager->getTransferCount();?>
						</div>
	 					<div style="margin:5px;"> 
	 						If upload number sounds correct, transfer to central specimen table using this form.  
						</div>
	 					<div style="margin:5px;"> 
	 						<input type="submit" name="action" value="Transfer Records to Central Specimen Table" />
						</div>
	 				</fieldset>			
	 			</form>
				<?php
			}
	 	}
		elseif(stripos($action,"transfer") !== false || $finalTransfer){
			echo '<ol>';
			$duManager->performFinalTransfer();
			echo '<li style="font-weight:bold;">Upload Procedure Complete';
			echo '</ol>';
		}
	 	else{
			if(($action == 'Analyze File' || $uploadType == $DWCAUPLOAD) && !$ulFileName) {
				$ulFileName = $duManager->uploadFile();
			}
			if($uploadType == $FILEUPLOAD && !$ulFileName){
				?>
				<form name="fileuploadform" action="specupload.php" method="post" enctype="multipart/form-data" onsubmit="return verifyFileUploadForm(this)">
					<fieldset style="width:95%;">
						<legend style="font-weight:bold;font-size:120%;"><?php echo $duManager->getTitle();?> (Step 1)</legend>
						<div>
							<div style="margin:10px;">
								<div class="ulfnoptions">
									<b>Upload File:</b> 
									<input name="uploadfile" type="file" size="50" onchange="this.form.ulfnoverride.value = ''" />
								</div>
								<div class="ulfnoptions" style="display:none;">
									<b>Full File Path:</b> 
									<input name="ulfnoverride" type="text" size="50" /><br/>
									* This option is for manual upload of a data file. 
									Enter full path to data file located on working server.   
								</div>
							</div>
							<div style="margin:10px;">
								<input type="submit" name="action" value="Analyze File" />
								<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
								<input type="hidden" name="collid" value="<?php echo $collId;?>" />
								<input type="hidden" name="uploadtype" value="<?php echo $FILEUPLOAD;?>" />
								<input type='hidden' name='MAX_FILE_SIZE' value='100000000' />
							</div>
							<div style="float:right;">
								<a href="#" onclick="toggle('ulfnoptions');return false;">Toggle Manual Upload Option</a>
							</div>
						</div>
					</fieldset>
				</form>
				<?php 
			}
			elseif($uploadType == $DIGIRUPLOAD){
				?>
				<form name="initform" action="specupload.php" method="post" onsubmit="">
					<fieldset style="width:95%;">
						<legend><b><?php echo $duManager->getTitle;?></b></legend>
						<div>
							Record Start: 
							<input type="text" name="recstart" size="5" value="<?php echo $duManager->getSearchStart(); ?>" />
						</div>
						<div>
							Record Limit: 
							<input type="text" name="reclimit" size="5" value="<?php echo $duManager->getSearchLimit(); ?>" />
						</div>
						<div style="margin:10px;">
							<input type="submit" name="action" value="Start Upload" />
							<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
							<input type="hidden" name="collid" value="<?php echo $collId;?>" />
							<input type="hidden" name="uploadtype" value="<?php echo $uploadType;?>" />
						</div>
					</fieldset>
				</form>
				<?php
			}
			elseif($uploadType == $DWCAUPLOAD){
				$duManager->analyzeUpload();
				$metaArr = $duManager->getMetaArr();
				//print_r($metaArr);
				if(isset($metaArr['occur'])){
					?>
					<form name="initform" action="specupload.php" method="post" onsubmit="">
						<fieldset style="width:95%;">
							<legend style="font-weight:bold;font-size:120%;"><?php echo $duManager->getTitle();?></legend>
							<div style="margin:20px;">
								<b>Source Unique Identifier / Primary Key (required): </b>
								<?php
								$dbpk = $duManager->getDbpk();
								?>
								<select name="dbpk" onchange="pkChanged(this);">
									<option value="coreid">coreId</option>
									<option value="catalognumber" <?php if($dbpk == 'catalognumber') echo 'SELECTED'; ?>>catalogNumber</option>
									<option value="occurrenceid" <?php if($dbpk == 'occurrenceid') echo 'SELECTED'; ?>>occurrenceId</option>
								</select>
								*The CoreId is the default primary key. Change ONLY if you are sure catalog number of occurrence id will server better as the primary specimen identifier 
								<div id="pkdiv" style="margin:5px 0px 0px 20px;display:none";>
									<input type="submit" name="action" value="Save Primary Key" />
								</div>
							</div>
							<div>
								<div>
									<input name="importspec" value="1" type="checkbox" checked /> 
									Import Specimen Records (<a href="#" onclick="toggle('dwcaSpec');return false;">view mapping</a>)
								</div>
								<fieldset id="dwcaSpec" style="display:none;">
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
										$autoMap = ($action == 'Automap Fields'?true:false);
										$duManager->echoFieldMapTable($autoMap); 
										?>
									</table>
									<div>
										* Mappings that are not yet saved are displayed in Yellow
									</div>
									<div style="margin:10px;">
										<input type="submit" name="action" value="Save Mapping" />
									</div>
								</fieldset>
								<div>
									<input name="importident" value="1" type="checkbox" <?php echo (isset($metaArr['ident'])?'checked':'disabled') ?> /> 
									Import Identification History 
									<?php 
									if(isset($metaArr['ident'])){
										echo '(<a href="#" onclick="toggle(\'dwcaIdent\');return false;">view mapping</a>)';
									}
									else{
										echo '(not present in DwC-Archive)';
									}
									?>
									
								</div>
								<?php 
								if(isset($metaArr['ident'])){
									?>
									<div id="dwcaDetDiv" style="display:none;">
										
									</div>
									<?php 
								}
								if(isset($metaArr['image'])){
									?>
									<div id="dwcaImgDiv" style="display:none;">
										
									</div>
									<?php
								}
								?>
								<div>
									<div style="margin:10px;">
										<input type="submit" name="action" value="Start Upload" />
										<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
										<input type="hidden" name="collid" value="<?php echo $collId;?>" />
										<input type="hidden" name="uploadtype" value="<?php echo $uploadType;?>" />
										<input type="hidden" name="ulfilename" value="<?php echo $ulFileName;?>" />
									</div>
								</div>
							</div>
						</fieldset>
					</form>
					<?php
				}
			}
			elseif($uploadType == $DIRECTUPLOAD || $uploadType == $FILEUPLOAD){
				$isSnapshot = ($duManager->getCollInfo("managementtype") == 'Snapshot'?true:false);
				$duManager->analyzeUpload();
				?>
				<form name="initform" action="specupload.php" method="post" onsubmit="">
					<fieldset style="width:95%;">
						<legend style="font-weight:bold;font-size:120%;"><?php echo $duManager->getTitle();?></legend>
						<?php 
						if($isSnapshot){
							//Primary key field is required and must be mapped 
							?>
							<div style="margin:20px;">
								<b>Source Unique Identifier / Primary Key (required): </b>
								<?php
								$dbpk = $duManager->getDbpk();
								$dbpkOptions = $duManager->getDbpkOptions();
								?>
								<select name="dbpk" style="background:<?php echo ($dbpk?"":"red");?>" onchange="pkChanged(this);">
									<option value="">Select Source Primary Key</option>
									<option value="">Delete Primary Key</option>
									<option value="">----------------------------------</option>
									<?php 
									foreach($dbpkOptions as $f){
										echo '<option '.($dbpk==$f?'SELECTED':'').'>'.$f.'</option>';
									}
									?>
								</select>
								<div id="pkdiv" style="margin:5px 0px 0px 20px;display:<?php echo ($dbpk?"none":"block");?>";>
									<input type="submit" name="action" value="Save Primary Key" />
								</div>
							</div>
							<?php 
						}
						if($dbpk || !$isSnapshot){
							?>
							<div id="mdiv">
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
									$autoMap = ($action == 'Automap Fields'?true:false);
									$duManager->echoFieldMapTable($autoMap); 
									?>
								</table>
								<div>
									* Mappings that are not yet saved are displayed in Yellow
								</div>
								<div style="margin:10px;">
									<input type="submit" name="action" value="Delete Field Mapping" />
									<input type="submit" name="action" value="Automap Fields" />
									<input type="submit" name="action" value="Save Mapping" />
								</div>
								<hr />
								<div id="uldiv">
									<div style="margin:10px;">
										<input type="submit" name="action" value="Start Upload" />
									</div>
								</div>
							</div>
							<?php 
						} 
						?>
					</fieldset>
					<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
					<input type="hidden" name="collid" value="<?php echo $collId;?>" />
					<input type="hidden" name="uploadtype" value="<?php echo $uploadType;?>" />
					<input type="hidden" name="ulfilename" value="<?php echo $ulFileName;?>" />
				</form>
				<?php
			} 
		}
	}
	else{
		if($collId){
			echo '<div style="font-weight:bold;font-size:120%;">ERROR: collection identifier not set</div>';
		}
		else{
			echo '<div style="font-weight:bold;font-size:120%;">ERROR: you are not authorized to upload to this collection</div>';
		}
	}
	?>
</div>
<?php 
include($serverRoot.'/footer.php');
?>
</body>
</html>