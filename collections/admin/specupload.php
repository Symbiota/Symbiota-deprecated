<?php 
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecUploadDirect.php');
include_once($SERVER_ROOT.'/classes/SpecUploadDigir.php');
include_once($SERVER_ROOT.'/classes/SpecUploadFile.php');
include_once($SERVER_ROOT.'/classes/SpecUploadDwca.php');

header("Content-Type: text/html; charset=".$CHARSET);
ini_set('max_execution_time', 3600);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/admin/specuploadmanagement.php?'.$_SERVER['QUERY_STRING']);

$collid = $_REQUEST["collid"];
$uploadType = $_REQUEST["uploadtype"];
$uspid = array_key_exists("uspid",$_REQUEST)?$_REQUEST["uspid"]:'';
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$autoMap = array_key_exists("automap",$_POST)?true:false;
$ulPath = array_key_exists("ulpath",$_REQUEST)?$_REQUEST["ulpath"]:"";
$importIdent = array_key_exists("importident",$_REQUEST)?true:false;
$importImage = array_key_exists("importimage",$_REQUEST)?true:false;
$matchCatNum = array_key_exists("matchcatnum",$_REQUEST)?true:false;
$matchOtherCatNum = array_key_exists("matchothercatnum",$_REQUEST)?true:false;
$verifyImages = array_key_exists("verifyimages",$_REQUEST)&&$_REQUEST['verifyimages']?true:false;
$processingStatus = array_key_exists("processingstatus",$_REQUEST)?$_REQUEST['processingstatus']:'';
$finalTransfer = array_key_exists("finaltransfer",$_REQUEST)?$_REQUEST["finaltransfer"]:0;
$dbpk = array_key_exists("dbpk",$_REQUEST)?$_REQUEST["dbpk"]:'';
$recStart = array_key_exists("recstart",$_REQUEST)?$_REQUEST["recstart"]:0;
$recLimit = array_key_exists("reclimit",$_REQUEST)?$_REQUEST["reclimit"]:1000;

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($uploadType)) $uploadType = 0;
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';
if($autoMap !== true) $autoMap = false;
if($importIdent !== true) $importIdent = false;
if($matchCatNum !== true) $matchCatNum = false;
if($matchOtherCatNum !== true) $matchOtherCatNum = false;
if($verifyImages !== true) $verifyImages = false;
if(!preg_match('/^[a-zA-Z0-9\s_]+$/',$processingStatus)) $processingStatus = '';
if($autoMap !== true) $autoMap = false;
if(!is_numeric($finalTransfer)) $finalTransfer = 0;
if($dbpk) $dbpk = htmlspecialchars($dbpk);
if(!is_numeric($recStart)) $recStart = 0;
if(!is_numeric($recLimit)) $recLimit = 1000;

$DIRECTUPLOAD = 1; $DIGIRUPLOAD = 2; $FILEUPLOAD = 3; $STOREDPROCEDURE = 4; $SCRIPTUPLOAD = 5; $DWCAUPLOAD = 6; $SKELETAL = 7; $IPTUPLOAD = 8; $NFNUPLOAD = 9;

if(strpos($uspid,'-')){
	$tok = explode('-',$uspid);
	$uspid = $tok[0];
	$uploadType = $tok[1];
}

$duManager = new SpecUploadBase();
if($uploadType == $DIRECTUPLOAD){
	$duManager = new SpecUploadDirect();
}
elseif($uploadType == $DIGIRUPLOAD){
	$duManager = new SpecUploadDigir();
	$duManager->setSearchStart($recStart);
	$duManager->setSearchLimit($recLimit);
}
elseif($uploadType == $FILEUPLOAD || $uploadType == $NFNUPLOAD){
	$duManager = new SpecUploadFile();
	$duManager->setUploadFileName($ulPath);
}
elseif($uploadType == $SKELETAL){
	$duManager = new SpecUploadFile();
	$duManager->setUploadFileName($ulPath);
	$matchCatNum = true;
}
elseif($uploadType == $DWCAUPLOAD || $uploadType == $IPTUPLOAD){
	$duManager = new SpecUploadDwca();
	$duManager->setBaseFolderName($ulPath);
	$duManager->setIncludeIdentificationHistory($importIdent);
	$duManager->setIncludeImages($importImage);
}

$duManager->setCollId($collid);
$duManager->setUspid($uspid);
$duManager->setUploadType($uploadType);
$duManager->setMatchCatalogNumber($matchCatNum);
$duManager->setMatchOtherCatalogNumbers($matchOtherCatNum);
$duManager->setVerifyImageUrls($verifyImages);
$duManager->setProcessingStatus($processingStatus);

if($action == 'Automap Fields'){
	$autoMap = true;
}

$statusStr = '';
$isEditor = 0;
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
	$isEditor = 1;
}
$duManager->readUploadParameters();

$isLiveData = false;
if($duManager->getCollInfo("managementtype") == 'Live Data') $isLiveData = true;

//Grab field mapping, if mapping form was submitted
if(array_key_exists("sf",$_POST)){
	if($action == "Reset Field Mapping"){
		$statusStr = $duManager->deleteFieldMap();
	}
	else{
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
		if($dbpk) $fieldMap["dbpk"]["field"] = $dbpk;
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
	if($action == "Save Mapping"){
		$statusStr = $duManager->saveFieldMap(array_key_exists('profiletitle',$_POST)?$_POST['profiletitle']:'');
		if(!$uspid) $uspid = $duManager->getUspid();
	}
}
$duManager->loadFieldMap();
?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE; ?> Specimen Uploader</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
	<script src="../../js/symb/shared.js" type="text/javascript"></script>
	<script>
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
					if(ext == 'csv' || ext == 'CSV') return true;
					else if(ext == 'zip' || ext == 'ZIP') return true;
					else if(ext == 'txt' || ext == 'TXT') return true;
					else if(ext == 'tab' || ext == 'tab') return true;
					else if(fileName.substring(0,4) == 'http') return true;
					else{
						alert("File must be comma separated (.csv), tab delimited (.txt or .tab), ZIP file (.zip), or a URL to an IPT Resource");
						return false;
					}
				}
			}
			return true;
		}

		function verifyMappingForm(f){
			var sfArr = [];
			var idSfArr = [];
			var imSfArr = [];
			var tfArr = [];
			var idTfArr = [];
			var imTfArr = [];
			var lacksCatalogNumber = true;
			var possibleMappingErr = false; 
			for(var i=0;i<f.length;i++){
				var obj = f.elements[i];
				if(obj.name == "sf[]"){
					if(sfArr.indexOf(obj.value) > -1){
						alert("ERROR: Source field names must be unique (duplicate field: "+obj.value+")");
						return false;
					}
					sfArr[sfArr.length] = obj.value;
					//Test value to make sure source file isn't missing the header and making directly to file record
					if(!possibleMappingErr){
						if(isNumeric(obj.value)){
							possibleMappingErr = true;
						} 
						if(obj.value.length > 7){
							if(isNumeric(obj.value.substring(5))){ 
								possibleMappingErr = true;
							}
							else if(obj.value.slice(-5) == "aceae" || obj.value.slice(-4) == "idae"){
								possibleMappingErr = true;
							}
						}
					}
				}
				else if(obj.name == "ID-sf[]"){
					if(f.importident.value == "1"){
						if(idSfArr.indexOf(obj.value) > -1){
							alert("ERROR: Source field names must be unique (Identification: "+obj.value+")");
							return false;
						}
						idSfArr[idSfArr.length] = obj.value;
					}
				}
				else if(obj.name == "IM-sf[]"){
					if(f.importimage.value == "1"){
						if(imSfArr.indexOf(obj.value) > -1){
							alert("ERROR: Source field names must be unique (Image: "+obj.value+")");
							return false;
						}
						imSfArr[imSfArr.length] = obj.value;
					}
				}
				else if(obj.value != "" && obj.value != "unmapped"){
					if(obj.name == "tf[]"){
						if(tfArr.indexOf(obj.value) > -1){
							alert("ERROR: Can't map to the same target field more than once ("+obj.value+")");
							return false;
						}
						tfArr[tfArr.length] = obj.value;
					}
					else if(obj.name == "ID-tf[]"){
						if(f.importident.value == "1"){
							if(idTfArr.indexOf(obj.value) > -1){
								alert("ERROR: Can't map to the same target field more than once (Identification: "+obj.value+")");
								return false;
							}
							idTfArr[idTfArr.length] = obj.value;
						}
					}
					else if(obj.name == "IM-tf[]"){
						if(f.importimage.value == "1"){
							if(imTfArr.indexOf(obj.value) > -1){
								alert("ERROR: Can't map to the same target field more than once (Images: "+obj.value+")");
								return false;
							}
							imTfArr[imTfArr.length] = obj.value;
						}
					}
				}
				if(lacksCatalogNumber && obj.name == "tf[]"){
					//Is skeletal file upload
					if(obj.value == "catalognumber"){
						lacksCatalogNumber = false;
					}
				}
			}
			if(lacksCatalogNumber && f.uploadtype.value == 7){
				//Skeletal records require catalog number to be mapped
				alert("ERROR: Catalog Number is required for Skeletal File Uploads");
				return false;
			}
			if(possibleMappingErr){
				return confirm("Does the first row of the input file contain the column names? It appears that you may be mapping directly to the first row of active data rather than a header row. If so, the first row of data will be lost and some columns might be skipped. Select OK to proceed, or cancel to abort");
			}
			return true;
		}

		function verifySaveMapping(f){
			if(f.uspid.value == "" && f.profiletitle.value == ""){
				$("#newProfileNameDiv").show();
				alert("Enter a profile name and click the Save Map button to create a new Upload Profile");
				return false;
			}
			return true;
		}

		function pkChanged(selObj){
			if(selObj.value){
				$("#mdiv").show();
				//$("#uldiv").show();
			}
			else{
				$("#mdiv").hide();
				//$("#uldiv").show();
			}
		}
	</script>
</head>
<body>
<?php
	$displayLeftMenu = (isset($collections_admin_specuploadMenu)?$collections_admin_specuploadMenu:false);
	include($SERVER_ROOT.'/header.php');
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
			<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1">Collection Management Panel</a> &gt;&gt; 
			<a href="specuploadmanagement.php?collid=<?php echo $collid; ?>">List of Upload Profiles</a> &gt;&gt; 
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
	$recReplaceMsg = '<span style="color:orange"><b>Caution:</b></span> Matching records will be replaced with incoming records';
	if($isEditor && $collid){
		//Grab collection name and last upload date and display for all
		echo '<div style="font-weight:bold;font-size:130%;">'.$duManager->getCollInfo('name').'</div>';
		echo '<div style="margin:0px 0px 15px 15px;"><b>Last Upload Date:</b> '.($duManager->getCollInfo('uploaddate')?$duManager->getCollInfo('uploaddate'):'not recorded').'</div>';
		if(($action == "Start Upload") || (!$action && ($uploadType == $STOREDPROCEDURE || $uploadType == $SCRIPTUPLOAD))){
			//Upload records
	 		echo "<div style='font-weight:bold;font-size:120%'>Upload Status:</div>";
	 		echo "<ul style='margin:10px;font-weight:bold;'>";
	 		$duManager->uploadData($finalTransfer);
			echo "</ul>";
			if($duManager->getTransferCount() && !$finalTransfer){
				?>
 				<fieldset style="margin:15px;">
 					<legend style="<?php if($uploadType == $SKELETAL) echo 'background-color:lightgreen'; ?>"><b>Final transfer</b></legend>
 					<div style="margin:5px;">
 						<?php 
 						$reportArr = $duManager->getTransferReport();
						echo '<div>Occurrences pending transfer: '.$reportArr['occur'];
						if($reportArr['occur']){
							echo ' <a href="uploadviewer.php?collid='.$collid.'" target="_blank" title="Preview 1st 1000 Records"><img src="../../images/list.png" style="width:12px;" /></a>';
							echo ' <a href="uploadcsv.php?collid='.$collid.'" target="_self" title="Download Records"><img src="../../images/dl.png" style="width:12px;" /></a>';
						}
						echo '</div>';
						echo '<div style="margin-left:15px;">';
						echo '<div>Records to be updated: ';
						echo $reportArr['update'];
						if($reportArr['update']){
							echo ' <a href="uploadviewer.php?collid='.$collid.'&searchvar=occid:ISNOTNULL" target="_blank" title="Preview 1st 1000 Records"><img src="../../images/list.png" style="width:12px;" /></a>';
							echo ' <a href="uploadcsv.php?collid='.$collid.'&searchvar=occid:ISNOTNULL" target="_self" title="Download Records"><img src="../../images/dl.png" style="width:12px;" /></a>';
							if($uploadType != $SKELETAL && $uploadType != $NFNUPLOAD) 
								echo '&nbsp;&nbsp;&nbsp;<span style="color:orange"><b>Caution:</b></span> incoming records will replace existing records';
						}
						echo '</div>';
						if($uploadType != $NFNUPLOAD || $reportArr['new']){
							if($uploadType == $NFNUPLOAD) echo '<div>Mismatched records: ';
							else echo '<div>New records: ';
							echo $reportArr['new'];
							if($reportArr['new']){ 
								echo ' <a href="uploadviewer.php?collid='.$collid.'&searchvar=occid:ISNULL" target="_blank" title="Preview 1st 1000 Records"><img src="../../images/list.png" style="width:12px;" /></a>';
								echo ' <a href="uploadcsv.php?collid='.$collid.'&searchvar=occid:ISNULL" target="_self" title="Download Records"><img src="../../images/dl.png" style="width:12px;" /></a>';
								if($uploadType == $NFNUPLOAD) echo '<span style="margin-left:15px;color:orange">&gt;&gt; Records failed to link to records within this collection and will not be imported</span>';
							}
							echo '</div>';
						}
						if(isset($reportArr['matchappend']) && $reportArr['matchappend']){
							echo '<div>Records matching on catalog number that will be appended : ';
							echo $reportArr['matchappend'];
							if($reportArr['matchappend']){ 
								echo ' <a href="uploadviewer.php?collid='.$collid.'&searchvar=matchappend" target="_blank" title="Preview 1st 1000 Records"><img src="../../images/list.png" style="width:12px;" /></a>';
								echo ' <a href="uploadcsv.php?collid='.$collid.'&searchvar=matchappend" target="_self" title="Download Records"><img src="../../images/dl.png" style="width:12px;" /></a>';
							}
							echo '</div>';
							echo '<div style="margin-left:15px;"><span style="color:orange;">WARNING:</span> This will result in records with duplicate catalog numbers</div>';
						}
						if($uploadType != $NFNUPLOAD && $uploadType != $SKELETAL){
							if(isset($reportArr['sync']) && $reportArr['sync']){
								echo '<div>Records that will be syncronized with central database: ';
								echo $reportArr['sync'];
								if($reportArr['sync']){  
									echo ' <a href="uploadviewer.php?collid='.$collid.'&searchvar=sync" target="_blank" title="Preview 1st 1000 Records"><img src="../../images/list.png" style="width:12px;" /></a>';
									echo ' <a href="uploadcsv.php?collid='.$collid.'&searchvar=sync" target="_self" title="Download Records"><img src="../../images/dl.png" style="width:12px;" /></a>';
								}
								echo '</div>';
								echo '<div style="margin-left:15px;">These are typically records that have been originally processed within the portal, exported and integrated into a local management database, and then reimported and synchronized with the portal records by matching on catalog number.</div>';
								echo '<div style="margin-left:15px;"><span style="color:orange;">WARNING:</span> Incoming records will replace portal records by matching on catalog numbers. Make sure incoming records are the most up to date!</div>';
							}
							if(isset($reportArr['exist']) && $reportArr['exist']){
								echo '<div>Previous loaded records not matching incoming records: ';
								echo $reportArr['exist'];
								if($reportArr['exist']){  
									echo ' <a href="uploadviewer.php?collid='.$collid.'&searchvar=exist" target="_blank" title="Preview 1st 1000 Records"><img src="../../images/list.png" style="width:12px;" /></a>';
									echo ' <a href="uploadcsv.php?collid='.$collid.'&searchvar=exist" target="_self" title="Download Records"><img src="../../images/dl.png" style="width:12px;" /></a>';
								}
								echo '</div>';
								echo '<div style="margin-left:15px;">';
								echo 'Note: If you are doing a partial upload, this is expected. ';
								echo 'If you are doing a full data refresh, these may be records that were deleted within your local database but not within the portal.';
								echo '</div>';
							}
							if(isset($reportArr['nulldbpk']) && $reportArr['nulldbpk']){
								echo '<div style="color:red;">Records that will be removed due to NULL Primary Identifier: ';
								echo $reportArr['nulldbpk'];
								if($reportArr['nulldbpk']){ 
									echo ' <a href="uploadviewer.php?collid='.$collid.'&searchvar=dbpk:ISNULL" target="_blank" title="Preview 1st 1000 Records"><img src="../../images/list.png" style="width:12px;" /></a>';
									echo ' <a href="uploadcsv.php?collid='.$collid.'&searchvar=dbpk:ISNULL" target="_self" title="Download Records"><img src="../../images/dl.png" style="width:12px;" /></a>';
								}
								echo '</div>';
							}
							if(isset($reportArr['dupdbpk']) && $reportArr['dupdbpk']){
								echo '<div style="color:red;">Records that will be removed due to DUPLICATE Primary Identifier: ';
								echo $reportArr['dupdbpk'];
								if($reportArr['dupdbpk']){  
									echo ' <a href="uploadviewer.php?collid='.$collid.'&searchvar=dupdbpk" target="_blank" title="Preview 1st 1000 Records"><img src="../../images/list.png" style="width:12px;" /></a>';
									echo ' <a href="uploadcsv.php?collid='.$collid.'&searchvar=dupdbpk" target="_self" title="Download Records"><img src="../../images/dl.png" style="width:12px;" /></a>';
								}
								echo '</div>';
							}
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
					<form name="finaltransferform" action="specupload.php" method="post" style="margin-top:10px;" onsubmit="return confirm('Are you sure you want to transfer records from temporary table to central specimen table?');">
						<input type="hidden" name="collid" value="<?php echo $collid;?>" /> 
						<input type="hidden" name="uploadtype" value="<?php echo $uploadType; ?>" />
						<input type="hidden" name="verifyimages" value="<?php echo ($verifyImages?'1':'0'); ?>" />
						<input type="hidden" name="processingstatus" value="<?php echo $processingStatus;?>" /> 
						<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
						<div style="margin:5px;"> 
							<input type="submit" name="action" value="Transfer Records to Central Specimen Table" />
						</div>
					</form>
				</fieldset>			
				<?php
			}
	 	}
		elseif($action == 'Transfer Records to Central Specimen Table' || $finalTransfer){
			echo '<ul>';
			$duManager->finalTransfer();
			echo '</ul>';
		}
		else{
			if($uploadType == $DIGIRUPLOAD){
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
						<?php 
						if($isLiveData){
							?>
							<div style="margin:10px 0px;">
								<input name="matchcatnum" type="checkbox" value="1" checked /> 
								Match on Catalog Number 
							</div>
							<div style="margin:10px 0px;">
								<input name="matchothercatnum" type="checkbox" value="1" /> 
								Match on Other Catalog Numbers  
							</div>
							<ul style="margin:10px 0px;">
								<li><?php echo $recReplaceMsg; ?></li>
								<li>If both checkboxes are selected, matches will first be made on catalog numbers and secondarily on other catalog numbers</li>
							</ul>
							<?php 
						}
						?>
						<div style="margin:10px;">
							<input type="submit" name="action" value="Start Upload" />
							<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
							<input type="hidden" name="collid" value="<?php echo $collid;?>" />
							<input type="hidden" name="uploadtype" value="<?php echo $uploadType;?>" />
						</div>
					</fieldset>
				</form>
				<?php
			}
			else{
				$uploadTitle = $duManager->getTitle();
				if(!$uploadTitle){
					if($uploadType == $DWCAUPLOAD) $uploadTitle = 'Manual DwC-Archive Import';
					elseif($uploadType == $IPTUPLOAD) $uploadTitle = 'IPT/DwC-A Provider Import';
					elseif($uploadType == $SKELETAL) $uploadTitle = 'Skeletal File Import';
					elseif($uploadType == $FILEUPLOAD) $uploadTitle = 'Delimited Text File Import';
					elseif($uploadType == $NFNUPLOAD) $uploadTitle = 'Notes from Natural Import';
				}
				//Upload type is direct, file, or DWCA 
				if(!$ulPath && ($uploadType == $FILEUPLOAD || $uploadType == $SKELETAL || $uploadType == $NFNUPLOAD || $uploadType == $DWCAUPLOAD || $uploadType == $IPTUPLOAD)){
					//Need to upload data for file and DWCA uploads
					$ulPath = $duManager->uploadFile();
					if(!$ulPath){
						//Still null, thus we have to upload file
						?>
						<form name="fileuploadform" action="specupload.php" method="post" enctype="multipart/form-data" onsubmit="return verifyFileUploadForm(this)">
							<fieldset style="width:95%;">
								<legend style="font-weight:bold;font-size:120%;<?php if($uploadType == $SKELETAL) echo 'background-color:lightgreen'; ?>"><?php echo $uploadTitle;?>: Identify Data Source</legend>
								<div>
									<div style="margin:10px">
										<?php
										$pathLabel = 'IPT Resource URL';
										if($uploadType != $IPTUPLOAD){
											$pathLabel = 'Resource Path or URL';
											?>
											<div>
												<input name="uploadfile" type="file" size="50" onchange="this.form.ulfnoverride.value = ''" />
											</div>
											<?php 
										}
										?>
										<div class="ulfnoptions" style="display:<?php echo ($uploadType!=$IPTUPLOAD?'none':''); ?>;margin:15px 0px">
											<b><?php echo $pathLabel; ?>:</b> 
											<input name="ulfnoverride" type="text" size="70" /><br/>
											<?php 
											if($uploadType != $IPTUPLOAD){
												echo '* This option is for pointing to a data file that was manually uploaded to a server. '.
													'This option offers a workaround for importing files that are larger than what is allowed by '.
													'server upload limitations (e.g. PHP configuration limits).';
											}
											?>
										</div>
										<?php 
										if($uploadType != $IPTUPLOAD){
											?>
											<div class="ulfnoptions">
												<a href="#" onclick="toggle('ulfnoptions');return false;">Display Additional Options</a>
											</div>
											<?php
										}
										?>
									</div>
									<div style="margin:10px;">
										<?php 
										if(!$uspid && $uploadType != $NFNUPLOAD) 
											echo '<input name="automap" type="checkbox" value="1" CHECKED /> <b>Automap fields</b><br/>';
										?>
									</div>
									<div style="margin:10px;">
										<input name="action" type="submit" value="Analyze File" />
										<input name="uspid" type="hidden" value="<?php echo $uspid;?>" />
										<input name="collid" type="hidden" value="<?php echo $collid;?>" />
										<input name="uploadtype" type="hidden" value="<?php echo $uploadType;?>" />
										<input name="MAX_FILE_SIZE" type="hidden" value="100000000" />
									</div>
								</div>
							</fieldset>
						</form>
						<?php
					}
				}
				$processingList = array("unprocessed"=>"Unprocessed","stage 1"=>"Stage 1","stage 2"=>"Stage 2","stage 3"=>"Stage 3",
					"pending review"=>"Pending Review","expert required"=>"Expert Required","pending review-nfn"=>"Pending Review-NfN",
					"reviewed"=>"Reviewed","closed"=>"Closed");
				if($ulPath && ($uploadType == $DWCAUPLOAD || $uploadType == $IPTUPLOAD)){
					//Data has been uploaded and it's a DWCA upload type
					if($duManager->analyzeUpload()){
						$metaArr = $duManager->getMetaArr();
						if(isset($metaArr['occur'])){
							?>
							<form name="dwcauploadform" action="specupload.php" method="post" onsubmit="return verifyMappingForm(this)">
								<fieldset style="width:95%;">
									<legend style="font-weight:bold;font-size:120%;"><?php echo $uploadTitle.': Field Mapping';?></legend>
									<div style="margin:10px;">
										<b>Source Unique Identifier / Primary Key (<span style="color:red">required</span>): </b>
										<?php
										$dbpk = $duManager->getDbpk();
										$dbpkTitle = 'Core ID';
										if($dbpk == 'catalognumber') $dbpkTitle = 'Catalog Number';
										elseif($dbpk == 'occurrenceid') $dbpkTitle = 'Occurrence ID';
										echo $dbpkTitle;
										?>
										<div style="margin:10px;">
											<div>
												<input name="importspec" value="1" type="checkbox" checked /> 
												Import Occurrence Records (<a href="#" onclick="toggle('dwcaOccurDiv');return false;">view mapping</a>)
											</div>
											<div id="dwcaOccurDiv" style="display:none;margin:20px;">
												<?php $duManager->echoFieldMapTable(true,'occur'); ?>
												<div>
													* Unverified mappings are displayed in yellow
												</div>
											</div>
											<div>
												<input name="importident" value="1" type="checkbox" <?php echo (isset($metaArr['ident'])?'checked':'disabled') ?> /> 
												Import Identification History 
												<?php 
												if(isset($metaArr['ident'])){
													echo '(<a href="#" onclick="toggle(\'dwcaIdentDiv\');return false;">view mapping</a>)';
													?>
													<div id="dwcaIdentDiv" style="display:none;margin:20px;">
														<?php $duManager->echoFieldMapTable(true,'ident'); ?>
														<div>
															* Unverified mappings are displayed in yellow
														</div>
													</div>
													<?php 
												}
												else{
													echo '(not present in DwC-Archive)';
												}
												?>
												
											</div>
											<div>
												<input name="importimage" value="1" type="checkbox" <?php echo (isset($metaArr['image'])?'checked':'disabled') ?> /> 
												Import Images 
												<?php 
												if(isset($metaArr['image'])){
													echo '(<a href="#" onclick="toggle(\'dwcaImgDiv\');return false;">view mapping</a>)';
													?>
													<div id="dwcaImgDiv" style="display:none;margin:20px;">
														<?php $duManager->echoFieldMapTable(true,'image'); ?>
														<div>
															* Unverified mappings are displayed in yellow
														</div>
													</div>
													<?php 
												}
												else{
													echo '(not present in DwC-Archive)';
												}
												?>
											</div>
											<div style="margin:10px 0px;">
												<?php 
												if($uspid) echo '<input type="submit" name="action" value="Reset Field Mapping" />';
												echo '<input type="submit" name="action" value="Save Mapping" onclick="return verifySaveMapping(this.form)" style="margin-left:5px" />';
												if(!$uspid) echo ' <span id="newProfileNameDiv" style="margin-left:15px;color:orange;display:none">New profile title: <input type="text" name="profiletitle" style="width:300px" /></span>';
												?>
												
											</div>
											<div style="margin-top:30px;">
												<?php 
												if($isLiveData){
													?>
													<div>
														<input name="matchcatnum" type="checkbox" value="1" checked /> 
														Match on Catalog Number
													</div>
													<div>
														<input name="matchothercatnum" type="checkbox" value="1" /> 
														Match on Other Catalog Numbers  
													</div>
													<ul style="margin-top:2px">
														<li><?php echo $recReplaceMsg; ?></li>
														<li>If both checkboxes are selected, matches will first be made on catalog numbers and secondarily on other catalog numbers</li>
													</ul>
													<?php 
												}
												?>
												<div style="margin:10px 0px;">
													<input name="verifyimages" type="checkbox" value="1" /> 
													Verify image links 
												</div>
												<div style="margin:10px 0px;">
													Processing Status:
													<select name="processingstatus">
														<option value="">Leave as is / Do not set</option>
														<option value="">--------------------------</option>
														<?php 
														foreach($processingList as $ps){
															echo '<option value="'.$ps.'">'.ucwords($ps).'</option>';
														}
														?>
													</select>  
												</div>
												<div style="margin:10px;">
													<input type="submit" name="action" value="Start Upload" />
													<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
													<input type="hidden" name="collid" value="<?php echo $collid;?>" />
													<input type="hidden" name="uploadtype" value="<?php echo $uploadType;?>" />
													<input type="hidden" name="ulpath" value="<?php echo $ulPath;?>" />
												</div>
											</div>
										</div>
									</div>
								</fieldset>
							</form>
							<?php
						}
					}
					else{
						if($duManager->getErrorStr()){
							echo '<div style="font-weight:bold;">'.$duManager->getErrorStr().'</div>';
						}
						else{
							echo '<div style="font-weight:bold;">Unknown error analyzing upload</div>';
						}
					}
				}
				elseif($uploadType == $NFNUPLOAD && $ulPath){
					$duManager->analyzeUpload();
					?>
					<form name="filemappingform" action="specupload.php" method="post" onsubmit="return verifyMappingForm(this)">
						<fieldset style="width:95%;padding:15px">
							<legend style="font-weight:bold;font-size:120%;">Notes from Nature File Import</legend>
							<?php 
							$duManager->echoFieldMapTable(true, 'spec')
							?>
							<div style="margin:10px 0px;">
								Processing Status:
								<select name="processingstatus">
									<option value="">Leave as is / Do not set</option>
									<option value="">--------------------------</option>
									<?php 
									foreach($processingList as $ps){
										echo '<option value="'.$ps.'">'.ucwords($ps).'</option>';
									}
									?>
								</select>  
							</div>
							<div style="margin:20px;">
								<input type="submit" name="action" value="Start Upload" />
							</div>
						</fieldset>
						<input name="matchcatnum" type="hidden" value="0" /> 
						<input name="matchothercatnum" type="hidden" value="0" /> 
						<input name="uspid" type="hidden" value="<?php echo $uspid;?>" />
						<input name="collid" type="hidden" value="<?php echo $collid;?>" />
						<input name="uploadtype" type="hidden" value="<?php echo $uploadType;?>" />
						<input name="ulpath" type="hidden" value="<?php echo $ulPath;?>" />
					</form>
					<?php
				}
				elseif($uploadType == $DIRECTUPLOAD || (($uploadType == $FILEUPLOAD || $uploadType == $SKELETAL) && $ulPath)){
					$duManager->analyzeUpload();
					?>
					<form name="filemappingform" action="specupload.php" method="post" onsubmit="return verifyMappingForm(this)">
						<fieldset style="width:95%;">
							<legend style="font-weight:bold;font-size:120%;<?php if($uploadType == $SKELETAL) echo 'background-color:lightgreen'; ?>"><?php echo $uploadTitle; ?></legend>
							<?php 
							if(!$isLiveData && $uploadType != $SKELETAL){
								//Primary key field is required and must be mapped 
								?>
								<div style="margin:20px;">
									<b>Source Unique Identifier / Primary Key (<span style="color:red">required</span>): </b>
									<?php
									$dbpk = $duManager->getDbpk();
									$dbpkOptions = $duManager->getDbpkOptions();
									?>
									<select name="dbpk" onchange="pkChanged(this);">
										<option value="">Select Source Primary Key</option>
										<option value="">----------------------------------</option>
										<?php 
										foreach($dbpkOptions as $f){
											echo '<option '.($dbpk==$f?'SELECTED':'').'>'.$f.'</option>';
										}
										?>
									</select>
								</div>
								<?php 
							}
							$displayStr = 'block';
							if(!$isLiveData) $displayStr = 'none';
							if($uploadType == $SKELETAL) $displayStr = 'block';
							if($dbpk) $displayStr = 'block';
							?>
							<div id="mdiv" style="display:<?php echo $displayStr; ?>">
								<?php $duManager->echoFieldMapTable($autoMap,'spec'); ?>
								<div>
									* Unverified mappings are displayed in yellow<br/>
									* To learn more about mapping to Symbiota fields (and Darwin Core): 
									<div style="margin-left:15px;">
										<a href="http://symbiota.org/docs/wp-content/uploads/SymbiotaOccurrenceFields.pdf" target="_blank">SymbiotaOccurrenceFields.pdf</a><br/>
										<a href="http://symbiota.org/docs/symbiota-introduction/loading-specimen-data/" target="_blank">Loading Data into Symbiota</a>
									</div>
								</div>
								<div style="margin:10px;">
									<?php 
									if($uspid){
										?>
										<input type="submit" name="action" value="Reset Field Mapping" />
										<?php 
									}
									?>
									<input type="submit" name="action" value="Automap Fields" />
									<input type="submit" name="action" value="Verify Mapping" />
									<input type="submit" name="action" value="Save Mapping" onclick="return verifySaveMapping(this.form)" />
									<span id="newProfileNameDiv" style="margin-left:15px;color:red;display:none">
										New profile title: <input type="text" name="profiletitle" style="width:300px" />
									</span>
								</div>
								<hr />
								<div id="uldiv" style="margin-top:30px;">
									<?php 
									if($isLiveData || $uploadType == $SKELETAL){
										?>
										<div>
											<input name="matchcatnum" type="checkbox" value="1" checked <?php echo ($uploadType == $SKELETAL?'DISABLED':''); ?> /> 
											Match on Catalog Number
										</div>
										<div>
											<input name="matchothercatnum" type="checkbox" value="1" /> 
											Match on Other Catalog Numbers  
										</div>
										<ul style="margin-top:2px">
											<?php 
											if($uploadType == $SKELETAL){
												echo '<li>Incoming skeletal data will be appended only if targeted field is empty</li>';
											}
											else{
												echo '<li>'.$recReplaceMsg.'</li>';
											}
											?>
											<li>If both checkboxes are selected, matches will first be made on catalog numbers and secondarily on other catalog numbers</li>
										</ul>
										<?php 
									}
									?>
									<div style="margin:10px 0px;">
										<input name="verifyimages" type="checkbox" value="1" /> 
										Verify image links from associatedMedia field
									</div>
									<div style="margin:10px 0px;">
										Processing Status:
										<select name="processingstatus">
											<option value="">Leave as is / Do not set</option>
											<option value="">--------------------------</option>
											<?php 
											foreach($processingList as $ps){
												echo '<option value="'.$ps.'">'.ucwords($ps).'</option>';
											}
											?>
										</select>  
									</div>
									<div style="margin:20px;">
										<input type="submit" name="action" value="Start Upload" />
									</div>
								</div>
								<?php 
								if($uploadType == $SKELETAL){
									?>
									<div style="margin:15px;background-color:lightgreen;">
										Skeletal Files consist of stub data that is easy to capture in bulk during the imaging process. 
										This data is used to seed new records to which images are linked. 
										Skeletal fields typically collected include filed by or current scientific name, country, state/province, and sometimes county, though any supported field can be included. 
										Skeletal file uploads are similar to regular uploads though differ in several ways.
										<ul>
											<li>General file uploads typically consist of full records, while skeletal uploads will almost always be an annotated record with data for only a few selected fields</li>
											<li>The catalog number field is required for skeletal file uploads since this field is used to find matches on images or existing records</li>
											<li>In cases where a record already exists, a general file upload will completely replace the existing record with the data in the new record. 
											On the other hand, a skeletal upload will augment the existing record only with new field data. 
											Fields are only added if data does not already exist within the target field.</li>
											<li>If a record DOES NOT already exist, a new record will be created in both cases, but only the skeletal record will be tagged as unprocessed</li>
										</ul>
									</div>
									<?php 
								}
								?>
							</div>
						</fieldset>
						<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
						<input type="hidden" name="collid" value="<?php echo $collid;?>" />
						<input type="hidden" name="uploadtype" value="<?php echo $uploadType;?>" />
						<input type="hidden" name="ulpath" value="<?php echo $ulPath;?>" />
					</form>
					<?php
				}
			}
		}
	}
	else{
		if(!$isEditor){
			echo '<div style="font-weight:bold;font-size:120%;">ERROR: you are not authorized to upload to this collection</div>';
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
	?>
</div>
<?php 
include($SERVER_ROOT.'/footer.php');
?>
</body>
</html>