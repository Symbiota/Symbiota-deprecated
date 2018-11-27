<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecUploadDwca.php');

header("Content-Type: text/html; charset=".$CHARSET);
ini_set('max_execution_time', 3600);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/admin/reloadbackup.php?'.$_SERVER['QUERY_STRING']);

$collid = $_REQUEST["collid"];
$action = array_key_exists("action",$_REQUEST)?$_POST["action"]:"";
$includeIdentificationHistory = array_key_exists("includeidentificationhistory",$_REQUEST)?$_POST["includeidentificationhistory"]:"";
$includeImages = array_key_exists("includeimages",$_REQUEST)?$_POST["includeimages"]:"";
$ulPath = array_key_exists("ulpath",$_REQUEST)?$_POST["ulpath"]:"";

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';

$duManager = new SpecUploadDwca();
$duManager->setCollId($collid);
$duManager->setUploadType(10);
$duManager->setTargetPath($ulPath);
$duManager->setIncludeIdentificationHistory($includeIdentificationHistory);
$duManager->setIncludeImages($includeImages);
$duManager->setMatchCatalogNumber(false);
$duManager->setMatchOtherCatalogNumbers(false);
$duManager->setVerifyImageUrls(false);

$isEditor = 0;
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
	$isEditor = 1;
}

$isLiveData = false;
if($duManager->getCollInfo("managementtype") == 'Live Data') $isLiveData = true;

//Grab field mapping, if mapping form was submitted
if(array_key_exists("sf",$_POST)){
	//Set field map for occurrences using mapping form
	$targetFields = $_POST["tf"];
	$sourceFields = $_POST["sf"];
	$fieldMap = Array();
	for($x = 0;$x<count($targetFields);$x++){
		if($targetFields[$x]){
			$tField = $targetFields[$x];
			if($tField == 'unmapped') $tField .= '-'.$x;
			$fieldMap[$tField]["field"] = $sourceFields[$x];
		}
	}
	//Set Source PK
	$duManager->setFieldMap($fieldMap);

	//Set field map for identification history
	if(array_key_exists("ID-sf",$_POST)){
		$targetIdFields = $_POST["ID-tf"];
		$sourceIdFields = $_POST["ID-sf"];
		$fieldIdMap = Array();
		for($x = 0;$x<count($targetIdFields);$x++){
			if($targetIdFields[$x]){
				$tIdField = $targetIdFields[$x];
				if($tIdField == 'unmapped') $tIdField .= '-'.$x;
				$fieldIdMap[$tIdField]["field"] = $sourceIdFields[$x];
			}
		}
		$duManager->setIdentFieldMap($fieldIdMap);
	}
	//Set field map for image history
	if(array_key_exists("IM-sf",$_POST)){
		$targetImFields = $_POST["IM-tf"];
		$sourceImFields = $_POST["IM-sf"];
		$fieldImMap = Array();
		for($x = 0;$x<count($targetImFields);$x++){
			if($targetImFields[$x]){
				$tImField = $targetImFields[$x];
				if($tImField == 'unmapped') $tImField .= '-'.$x;
				$fieldImMap[$tImField]["field"] = $sourceImFields[$x];
			}
		}
		$duManager->setImageFieldMap($fieldImMap);
	}
}
$duManager->loadFieldMap(true);
?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE; ?> Restore Backup</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
	<script src="../../js/symb/shared.js" type="text/javascript"></script>
	<script>

		function verifyFileUploadForm(f){
			var fileName = "";
			if(f.uploadfile && f.uploadfile.value){
				 fileName = f.uploadfile.value;
			}
			else{
				fileName = f.ulfnoverride.value;
			}
			if(fileName == ""){
				alert("File path is empty. Please select the file that is to be restored.");
				return false;
			}
			else{
				var ext = fileName.split('.').pop();
				if(ext == 'zip' || ext == 'ZIP') return true;
				else{
					alert("File must be a ZIP file (.zip) downloaded as a Symbiota backup");
					return false;
				}
			}
			return true;
		}

		function verifyFileSize(inputObj){
			inputObj.form.ulfnoverride.value = ''
			if (!window.FileReader) {
				//alert("The file API isn't supported on this browser yet.");
				return;
			}
			<?php
			$maxUpload = ini_get('upload_max_filesize');
			$maxUpload = str_replace("M", "000000", $maxUpload);
			if($maxUpload > 100000000) $maxUpload = 100000000;
			echo 'var maxUpload = '.$maxUpload.";\n";
			?>
			var file = inputObj.files[0];
			if(file.size > maxUpload){
				var msg = "Import file "+file.name+" ("+Math.round(file.size/100000)/10+"MB) is larger than is allowed (current limit: "+(maxUpload/1000000)+"MB).";
				if(file.name.slice(-3) != "zip") msg = msg + " Note that import file size can be reduced by compressing within a zip file. ";
				alert(msg);
		    }
		}
	</script>
</head>
<body>
<?php
$displayLeftMenu = false;
include($SERVER_ROOT.'/header.php');
?>
<div class="navpath">
	<a href="../../index.php">Home</a> &gt;&gt;
	<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1">Collection Management Panel</a> &gt;&gt;
	<b>Backup Restore Module</b>
</div>
<!-- This is inner text! -->
<div id="innertext">
	<?php
	$recReplaceMsg = '<span style="color:orange"><b>Caution:</b></span> Matching records will be replaced with incoming records';
	if($isEditor){
		if($collid){
			echo '<div style="font-weight:bold;font-size:130%;margin-bottom:20px">'.$duManager->getCollInfo('name').'</div>';
			if(!$action){
				?>
				<form name="fileuploadform" action="restorebackup.php" method="post" enctype="multipart/form-data" onsubmit="return verifyFileUploadForm(this)">
					<fieldset style="padding:25px;width:95%;">
						<legend style="font-weight:bold;">Select Backup File to Restore</legend>
						<div>
							<div>
								<input name="uploadfile" type="file" size="50" onchange="verifyFileSize(this)" />
							</div>
							<div class="ulfnoptions" style="display:none;margin:15px 0px">
								<b>Resource Path or URL:</b>
								<input name="ulfnoverride" type="text" size="70" /><br/>
								<div>
									* This option is for pointing to a data file that was manually uploaded to a server
									This option offers a workaround for importing files that are larger than what is allowed by
									server upload limitations (e.g. PHP configuration limits)
								</div>
							</div>
						</div>
						<div style="margin:10px 0px;">
							<input name="includeidentificationhistory" type="checkbox" value="1" checked /> Restore Determination History<br/>
							<input name="includeimages" type="checkbox" value="1" checked /> Restore Images<br/>
						</div>
						<div style="margin:10px 0px;">
							<button name="action" type="submit" value="AnalyzeFile">Analyze File</button>
							<input name="collid" type="hidden" value="<?php echo $collid;?>" />
							<input name="MAX_FILE_SIZE" type="hidden" value="100000000" />
						</div>
						<div class="ulfnoptions">
							<a href="#" onclick="toggle('ulfnoptions');return false;">Manual File Upload Option</a>
						</div>
					</fieldset>
				</form>
				<?php
			}
			elseif($action == 'AnalyzeFile' || $action == 'Continue with Restore'){
				$uploadData = false;
				if($action == 'AnalyzeFile'){
					if($ulPath = $duManager->uploadFile()){
						if($verificationResult = $duManager->verifyBackupFile()){
							if($verificationResult === true){
								$uploadData = true;
							}
							elseif(is_array($verificationResult)){
								?>
								<form name="filemappingform" action="restorebackup.php" method="post" onsubmit="return verifyMappingForm(this)">
									<fieldset style="width:95%;padding:15px">
										<legend style="font-weight:bold;font-size:120%;">Backup Restore Module</legend>
										<div style="margin:15px">
											<div style="color:orange;font-weight:bold">Warnings exist:</div>
											<div style="margin:10px">
												<?php
												foreach($verificationResult as $warningStr){
													echo '<div>'.$warningStr.'</div>';
												}
												?>
												<div style="margin-top: 10px">If you think the warnings are in error, you may process with the database upload at your own risk</div>
											</div>
										</div>
										<div style="margin:20px;">
											<input type="submit" name="action" value="Continue with Restore" />
										</div>
									</fieldset>
									<input name="includeidentificationhistory" type="hidden" value="<?php echo $includeIdentificationHistory; ?>" />
									<input name="includeimages" type="hidden" value="<?php echo $includeImages; ?>" />
									<input name="collid" type="hidden" value="<?php echo $collid;?>" />
									<input name="ulpath" type="hidden" value="<?php echo $ulPath;?>" />
								</form>
								<?php
							}
						}
						else{
							echo '<div><span style="color:red">FATAL ERROR:</span> '.$duManager->getErrorStr().'</div>';
						}
					}
				}
				if($action == 'Continue with Restore' || $uploadData){
					echo "<div style='font-weight:bold;font-size:120%'>Upload Status:</div>";
					echo "<ul style='margin:10px;font-weight:bold;'>";
					$duManager->uploadData(false);
					$duManager->cleanBackupReload();
					echo "</ul>";
					if($duManager->getTransferCount()){
						?>
						<fieldset style="margin:15px;">
							<legend style=""><b>Final transfer</b></legend>
							<div style="margin:5px;">
								<?php
								$reportArr = $duManager->getTransferReport();
								echo '<div>Occurrences pending transfer: '.$reportArr['occur'];
								if($reportArr['occur']){
									echo ' <a href="uploadreviewer.php?collid='.$collid.'" target="_blank" title="Preview 1st 1000 Records"><img src="../../images/list.png" style="width:12px;" /></a>';
									echo ' <a href="uploadreviewer.php?action=export&collid='.$collid.'" target="_self" title="Download Records"><img src="../../images/dl.png" style="width:12px;" /></a>';
								}
								echo '</div>';
								echo '<div style="margin-left:15px;">';
								echo '<div>Records to be updated: ';
								echo $reportArr['update'];
								if($reportArr['update']){
									echo ' <a href="uploadreviewer.php?collid='.$collid.'&searchvar=occid:ISNOTNULL" target="_blank" title="Preview 1st 1000 Records"><img src="../../images/list.png" style="width:12px;" /></a>';
									echo ' <a href="uploadreviewer.php?action=export&collid='.$collid.'&searchvar=occid:ISNOTNULL" target="_self" title="Download Records"><img src="../../images/dl.png" style="width:12px;" /></a>';
								}
								echo '</div>';
								if($reportArr['new']){
									echo '<div>New records: ';
									echo $reportArr['new'];
									if($reportArr['new']){
										echo ' <a href="uploadreviewer.php?collid='.$collid.'&searchvar=new" target="_blank" title="Preview 1st 1000 Records"><img src="../../images/list.png" style="width:12px;" /></a>';
										echo ' <a href="uploadreviewer.php?action=export&collid='.$collid.'&searchvar=new" target="_self" title="Download Records"><img src="../../images/dl.png" style="width:12px;" /></a>';
									}
									echo '</div>';
								}
								if(isset($reportArr['exist']) && $reportArr['exist']){
									echo '<div>Previous loaded records not matching incoming records: ';
									echo $reportArr['exist'];
									if($reportArr['exist']){
										echo ' <a href="uploadreviewer.php?collid='.$collid.'&searchvar=exist" target="_blank" title="Preview 1st 1000 Records"><img src="../../images/list.png" style="width:12px;" /></a>';
										echo ' <a href="uploadreviewer.php?action=export&collid='.$collid.'&searchvar=exist" target="_self" title="Download Records"><img src="../../images/dl.png" style="width:12px;" /></a>';
									}
									echo '</div>';
									echo '<div style="margin-left:15px;">';
									echo 'Note: These are records that were added after the backup was downloaded. You can delete these records one-by-one using preview link above, ';
									echo 'or contact your portal manager if you would rather delete these records in batch. ';
									echo '</div>';
								}
								echo '</div>';
								//Extensions
								if(isset($reportArr['ident'])){
									echo '<div>Identification histories pending transfer: '.$reportArr['ident'].'</div>';
								}
								if(isset($reportArr['image'])){
									echo '<div>Records with images: '.$reportArr['image'].'</div>';
								}

								?>
							</div>
							<form name="finaltransferform" action="restorebackup.php" method="post" style="margin-top:10px;" onsubmit="return confirm('Are you sure you want to transfer records from temporary table to central specimen table?');">
								<input name="includeidentificationhistory" type="hidden" value="<?php echo $includeIdentificationHistory; ?>" />
								<input name="includeimages" type="hidden" value="<?php echo $includeImages; ?>" />
								<input type="hidden" name="collid" value="<?php echo $collid;?>" />
								<div style="margin:5px;">
									<button name="action" type="submit" value="TransferRecords">Transfer Records to Central Specimen Table</button>
								</div>
							</form>
						</fieldset>
						<?php
					}
				}
			}
			elseif($action == 'TransferRecords'){
				echo '<ul>';
				$duManager->finalTransfer();
				echo '</ul>';
			}
		}
		else{
			?>
			<div style="font-weight:bold;font-size:120%;">
				ERROR: Either you have tried to reach this page without going through the collection management menu
				or you have tried to upload a file that is too large.
				You may want to breaking the upload file into smaller files or compressing the file into a zip archive (.zip extension).
				You may want to contact portal administrator to request assistance in uploading the file (hint to admin: increasing PHP upload limits may help,
				current upload_max_filesize = <?php echo ini_get("upload_max_filesize").'; post_max_size = '.ini_get("post_max_size"); ?>)
				Use the back arrows to get back to the file upload page.
			</div>
			<?php
		}
	}
	else{
		echo '<div style="font-weight:bold;font-size:120%;">ERROR: you are not authorized to upload to this collection</div>';
	}
	?>
</div>
<?php
include($SERVER_ROOT.'/footer.php');
?>
</body>
</html>