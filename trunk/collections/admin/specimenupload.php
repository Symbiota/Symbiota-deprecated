<?php 
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecUploadManager.php');
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$uploadType = array_key_exists("uploadtype",$_REQUEST)?$_REQUEST["uploadtype"]:0;
$ulFileName = array_key_exists("ulfilename",$_REQUEST)?$_REQUEST["ulfilename"]:"";
$uspid = array_key_exists("uspid",$_REQUEST)?$_REQUEST["uspid"]:0;
$finalTransfer = array_key_exists("finaltransfer",$_REQUEST)?$_REQUEST["finaltransfer"]:0;
$dbpk = array_key_exists("dbpk",$_REQUEST)?$_REQUEST["dbpk"]:0;
$recStart = array_key_exists("recstart",$_REQUEST)?$_REQUEST["recstart"]:0;
$recLimit = array_key_exists("reclimit",$_REQUEST)?$_REQUEST["reclimit"]:1000;
$statusStr = "";
$DIRECTUPLOAD = 1;$DIGIRUPLOAD = 2; $FILEUPLOAD = 3; $STOREDPROCEDURE = 4; $SCRIPTUPLOAD = 5;

if($uspid && !$uploadType) $uploadType = SpecUploadManager::getUploadType($uspid);
$duManager;
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
	if($ulFileName) $duManager->setUploadFileName($ulFileName);
}
else{
	$duManager = new SpecUploadManager();
}

if($collId) $duManager->setCollId($collId);
if($uploadType) $duManager->setUploadType($uploadType);
if($uspid) $duManager->setUspid($uspid);

$isEditable = 0;
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
	$isEditable = 1;
}
if($isEditable){
 	if($action == "Save Primary Key"){
 		$duManager->savePrimaryKey($dbpk);
 	}
	elseif($action == "Submit Parameter Edits"){
		$statusStr = $duManager->editUploadProfile();
	}
	elseif($action == "Add New Profile"){
		$statusStr = $duManager->addUploadProfile();
		$action = "";
	}
	elseif($action == "Delete Profile"){
		$statusStr = $duManager->deleteUploadProfile($uspid);
	}
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
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

		function checkUploadListForm(f){
			if(f.uspid.length == null){
				if(f.uspid.checked) return true;
			}
			else{
				var radioCnt = f.uspid.length;
				for(var counter = 0; counter < radioCnt; counter++){
					if (f.uspid[counter].checked) return true; 
				}
			}
			alert("Please select an Upload Option");
			return false;
		}

		function checkInitForm(){
			var ufObj = document.getElementById("uploadfile");
			if(ufObj){
				if(ufObj.value == ""){
					alert("Upload file path is empty. Please select the file that is to be uploaded.");
					return false;
				}
			}
			return true;
		}

		function checkParameterForm(f){
			var formCheck = true;
			if(f.euptitle.value == ""){
				alert("Profile title is required");
				return false;
			}
			return formCheck;
		}
		
		function checkFinalTransferForm(){
			return confirm('Are you sure you want to transfer records from temporary table to central specimen table?');
		}

		function checkParamAddForm(f){
			var formCheck = true;
			if(f.auptitle.value == ""){
				alert("Profile title is required");
				return false;
			}
			return formCheck;
		}

		function pkChanged(selObj){
			document.getElementById('pkdiv').style.display='block';
			document.getElementById('mdiv').style.display='none';
			document.getElementById('uldiv').style.display='none';
		}

		function adjustAddForm(selectObj){
			//Show all
			document.getElementById('aupplatform').style.display='block';
			document.getElementById('aupserver').style.display='block';
			document.getElementById('aupport').style.display='block';
			document.getElementById('aupdigircode').style.display='block';
			document.getElementById('aupdigirpath').style.display='block';
			document.getElementById('aupdigirpkfield').style.display='block';
			document.getElementById('aupusername').style.display='block';
			document.getElementById('auppassword').style.display='block';
			document.getElementById('aupschemaname').style.display='block';
			document.getElementById('aupcleanupsp').style.display='block';
			document.getElementById('aupquerystr').style.display='block';
			//Then close according to upload type selection
			selValue = selectObj.value;
			if(selValue == 1){ //Direct Upload
				document.getElementById('aupdigircode').style.display='none';
				document.getElementById('aupdigirpath').style.display='none';
				document.getElementById('aupdigirpkfield').style.display='none';
			}
			if(selValue == 2){ //DiGIR
				document.getElementById('aupplatform').style.display='none';
				document.getElementById('aupusername').style.display='none';
				document.getElementById('auppassword').style.display='none';
			}
			if(selValue == 3){ //File Upload
				document.getElementById('aupplatform').style.display='none';
				document.getElementById('aupserver').style.display='none';
				document.getElementById('aupport').style.display='none';
				document.getElementById('aupdigircode').style.display='none';
				document.getElementById('aupdigirpath').style.display='none';
				document.getElementById('aupdigirpkfield').style.display='none';
				document.getElementById('aupusername').style.display='none';
				document.getElementById('auppassword').style.display='none';
				document.getElementById('aupschemaname').style.display='none';
				document.getElementById('aupquerystr').style.display='none';
			}
			if(selValue == 4){ //Stored Procedure
				document.getElementById('aupplatform').style.display='none';
				document.getElementById('aupserver').style.display='none';
				document.getElementById('aupport').style.display='none';
				document.getElementById('aupdigircode').style.display='none';
				document.getElementById('aupdigirpath').style.display='none';
				document.getElementById('aupdigirpkfield').style.display='none';
				document.getElementById('aupusername').style.display='none';
				document.getElementById('auppassword').style.display='none';
				document.getElementById('aupschemaname').style.display='none';
			}
			if(selValue == 5){ //Script Upload
				document.getElementById('aupplatform').style.display='none';
				document.getElementById('aupserver').style.display='none';
				document.getElementById('aupport').style.display='none';
				document.getElementById('aupdigircode').style.display='none';
				document.getElementById('aupdigirpath').style.display='none';
				document.getElementById('aupdigirpkfield').style.display='none';
				document.getElementById('aupusername').style.display='none';
				document.getElementById('auppassword').style.display='none';
				document.getElementById('aupschemaname').style.display='none';
			}
		}
	</script>
</head>
<body>
<?php
	$displayLeftMenu = (isset($collections_admin_specimenuploadMenu)?$collections_admin_specimenuploadMenu:true);
	include($serverRoot.'/header.php');
	if(isset($collections_admin_specimenuploadCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $collections_admin_specimenuploadCrumbs;
		echo " <b>Specimen Loader</b>"; 
		echo "</div>";
	}
?> 
<!-- This is inner text! -->
<div id="innertext">
	<div style="float:right;">
		<?php if($collId){ ?>
		<a href="specimenupload.php"><img src="<?php echo $clientRoot;?>/images/toparent.jpg" style="width:15px;border:0px;" title="Return to upload listing" /></a>
		<a href="specimenupload.php?collid=<?php echo ($collId);?>&action=addprofile"><img src="<?php echo $clientRoot;?>/images/add.png" style="width:15px;border:0px;" title="Add a New Upload Profile" /></a>
		<?php } ?>
	</div>
	<h1>Data Upload Module</h1>
<?php

if($statusStr){
	echo "<hr />";
	echo "<div>$statusStr</div>";
	echo "<hr />";
}

if(!$collId){
	if($symbUid){
	 	$collList = $duManager->getCollectionList();
		echo "<h2>Select Collection to Update</h2>";
	 	echo "<ul>";
	 	foreach($collList as $k => $v){
	 		echo "<li><a href='specimenupload.php?collid=".$k."'>$v</a></li>";
	 	}
	 	echo "</ul>";
	 	if(!$collList) echo "<div>There are no Database for which you have authority to update</div>";
	}
	else{
		echo "<div style='font-weight:bold;'>Please <a href='../../profile/index.php?refurl=".$clientRoot."/collections/admin/specimenupload.php'>login</a>!</div>";
	}
}
else{
	//Grab field mapping, if mapping form was submitted
	if(array_key_exists("sf",$_REQUEST)){
		if($action == "Delete Field Mapping"){
			$duManager->deleteFieldMap();
		}
		else{
	 		$targetFields = $_REQUEST["tf"];
	 		$sourceFields = $_REQUEST["sf"];
	 		$fieldMap = Array();
			for($x = 0;$x<count($targetFields);$x++){
				if($targetFields[$x]) $fieldMap[$targetFields[$x]]["field"] = $sourceFields[$x];
			}
			//Set Source PK
			if($dbpk) $fieldMap["dbpk"]["field"] = $dbpk;
	 		$duManager->setFieldMap($fieldMap);
		}
		if($action == "Save Mapping"){
			$duManager->saveFieldMap();
		}
	}

	//Grab collection name and last upload date and display for all
	echo "<h2>".$duManager->getCollInfo("name")."</h2>";
	if($duManager->getCollInfo("uploaddate")) {
		echo "<div style='margin:15px;'><b>Last Upload Date:</b> ".$duManager->getCollInfo("uploaddate")."</div>";
	}
	echo "<div style='margin:15px;'><a href='".$clientRoot."/collections/misc/collprofiles.php?collid=".$collId."'><b>View/Edit Metadata</b></a></div>";
 	if(!$action){
 		//Collection has been selected, now display different upload options
	 	$actionList = $duManager->getUploadList();
		?>
		<form name="uploadlistform" action="specimenupload.php" method="post" onsubmit="return checkUploadListForm(this);">
			<fieldset style="width:450px;">
				<legend style="font-weight:bold;font-size:120%;">Upload Options</legend>
				<?php 
				if($actionList){ 
				 	foreach($actionList as $id => $v){
				 		?>
				 		<div style="margin:10px;">
							<input type="radio" name="uspid" value="<?php echo $id;?>" />
						<?php echo $v["title"];?>
						</div>
						<?php 
				 	}	
					?>
					<input type="hidden" name="collid" value="<?php echo $collId;?>" />
					<div style="margin:10px;">
						<input type="submit" name="action" value="View Parameters" />
						<input type="submit" name="action" value="Initialize Upload..." />
					</div>
					<?php
				} 
			 	else{
			 		?>
					<div style="padding:30px;">
						There are no Upload Profiles associated with this collection. <br />
						Click <a href="specimenupload.php?collid=<?php echo ($collId);?>&action=addprofile">here</a> to add a new profile.
					</div>
					<?php 
			 	}
			 	 ?>
			</fieldset>
		</form>
		<hr />
	<?php 
 	}
 	elseif(stripos($action,"initialize") !== false || stripos($action,"analyze") !== false || stripos($action,"map") !== false || stripos($action,"save") !== false){
	 	$ulList = $duManager->getUploadList($uspid);
	 	$uploadType = $ulList[$uspid]["uploadtype"];
	 	$isSnapshot = ($duManager->getCollInfo("managementtype") == 'Snapshot'?true:false);
	 	$ulArr = array_pop($ulList);
	 	if($ulFileName || array_key_exists("uploadfile",$_FILES) || $uploadType == $DIRECTUPLOAD){
			$duManager->analyzeFile();
			if(array_key_exists("uploadfile",$_FILES)) $ulFileName = $duManager->getUploadFileName();
	 	} 
	 	?>
		<form name="initform" action="specimenupload.php" method="post" <?php echo ($uploadType==$FILEUPLOAD?"enctype='multipart/form-data'":"")?> onsubmit="return checkInitForm()">
			<fieldset style="width:450px;">
				<legend style="font-weight:bold;font-size:120%;"><?php echo $ulArr["title"];?></legend>
				<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
				<input type="hidden" name="collid" value="<?php echo $collId;?>" />
				<input type="hidden" name="uploadtype" value="<?php echo $uploadType;?>" />
				<input type="hidden" name="ulfilename" value="<?php echo $ulFileName;?>" />
				<?php if($uploadType == $FILEUPLOAD && stripos($action,"initialize") !== false){ ?>
					<input type='hidden' name='MAX_FILE_SIZE' value='10000000' />
					<div>
						<b>Upload File:</b>
						<div style="margin:10px;">
							<input id="uploadfile" name="uploadfile" type="file" size="50" accept="text/csv" />
						</div>
						<div style="margin:10px;">
							<input type="submit" name="action" value="View Parameters" />
							<input type="submit" name="action" value="Analyze Upload File" />
						</div>
					</div>
				<?php 
				}
				if($isSnapshot && ($uploadType == $DIRECTUPLOAD || $ulFileName || array_key_exists("uploadfile",$_FILES))){ 
					?>
					<div style="margin:20px;">
						<b>Source Unique Identifier / Primary Key (required): </b>
						<?php
						$fm = $duManager->getFieldMap();
						$dbpk = (array_key_exists("dbpk",$fm)?$fm["dbpk"]["field"]:"");
						$sFields = $duManager->getSourceArr();
						?>
						<select name="dbpk" style="background:<?php echo ($dbpk?"":"red");?>" onchange="pkChanged(this);">
							<option value=''>Select Source Primary Key</option>
							<option value=''>Delete Primary Key</option>
							<option value=''>----------------------------------</option>
							<?php 
							sort($sFields);
							foreach($sFields as $f){
								echo "<option ".($dbpk==$f?"SELECTED":"").">".$f."</option>\n";
							}
							?>
						</select>
						<div id="pkdiv" style="margin:5px 0px 0px 20px;display:<?php echo ($dbpk?"none":"block");?>";>
							<input type="submit" name="action" value="Save Primary Key" />
						</div>
					</div>
					<?php 
				} 
				if($dbpk && (($uploadType == $DIRECTUPLOAD) || ($uploadType == $FILEUPLOAD && $ulFileName))){ 
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
								$autoMap = (stripos($action,"auto")!==false?1:0);
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
					</div>
				<?php } ?>
				<?php if(($uploadType == $DIRECTUPLOAD && $dbpk) || ($uploadType == $FILEUPLOAD && $ulFileName && $dbpk) 
					|| ($uploadType == $DIGIRUPLOAD) || ($uploadType == $STOREDPROCEDURE) || ($uploadType == $SCRIPTUPLOAD)){ ?>
					<div id="uldiv">
						<?php 
						if($uploadType == $DIGIRUPLOAD){
							?>
							<div>
								Record Start: 
								<input type="text" name="recstart" size="5" value="<?php echo $duManager->getSearchStart(); ?>" />
							</div>
							<div>
								Record Limit: 
								<input type="text" name="reclimit" size="5" value="<?php echo $duManager->getSearchLimit(); ?>" />
							</div>
							<?php 
						}
						?>
						<div style="margin:10px;">
							<input type="submit" name="action" value="Start Upload" />
						</div>
					</div>
				<?php } ?>
			</fieldset>
		</form>
		<?php 
 	}
 	elseif(stripos($action,"parameter") !== false){
	 	$actionList = $duManager->getUploadList($uspid);
	 	$uploadType = $actionList[$uspid]["uploadtype"];
	 	$duManager->readUploadParameters();
		$editTitle = "";
 		if($uploadType == $DIRECTUPLOAD){
 			$editTitle = "Direct";
 		}
 		elseif($uploadType == $DIGIRUPLOAD){
 			$editTitle = "DiGIR";
 		}
 		elseif($uploadType == $FILEUPLOAD){
 			$editTitle = "File";
 		}
 		elseif($uploadType == $STOREDPROCEDURE){
 			$editTitle = "Stored Procedure";
 		}
 		?>
 		<div>
			<form name="inituploadform" action="specimenupload.php" method="post">
				<div style="float:right;">
					<input type="submit" name="action" value="Initialize Upload..." />
					<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
					<input type="hidden" name="collid" value="<?php echo $collId;?>" />
					<input type="hidden" name="uploadtype" value="<?php echo $uploadType;?>" />
				</div>
			</form>
		</div>
		<div style="clear:both;">
			<form name="parameterform" action="specimenupload.php" method="post" onsubmit="return checkParameterForm(this)">
				<fieldset>
					<legend><b><?php echo $editTitle; ?> Upload Parameters</b></legend>
					<div style="float:right;cursor:pointer;" onclick="toggle('editdiv');" title="Toggle Editing Functions">
						<img style='border:0px;' src='../../images/edit.png'/>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Title: </div>
						<div class="editdiv" style=""><?php echo $duManager->getTitle(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="euptitle" type="text" value="<?php echo $duManager->getTitle(); ?>" />
						</div>
					</div>
					<?php if($uploadType == 1){ ?>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Database Platform: </div>
						<div class="editdiv" style=""><?php echo $duManager->getPlatform(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupplatform" type="text" value="<?php echo $duManager->getPlatform(); ?>" />
						</div>
					</div>
					<?php } ?>
					<?php if($uploadType == 1 || $uploadType == 2){ ?>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Server: </div>
						<div class="editdiv" style=""><?php echo $duManager->getServer(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupserver" type="text" size="50" value="<?php echo $duManager->getServer(); ?>" />
						</div>
					</div>
					<?php } ?>
					<?php if($uploadType == 1 || $uploadType == 2){ ?>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Port: </div>
						<div class="editdiv" style=""><?php echo $duManager->getPort(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupport" type="text" value="<?php echo $duManager->getPort(); ?>" />
						</div>
					</div>
					<?php } ?>
					<?php if($uploadType == 2){ ?>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">DiGIR Code: </div>
						<div class="editdiv" style=""><?php echo $duManager->getDigirCode(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupdigircode" type="text" value="<?php echo $duManager->getDigirCode(); ?>" />
						</div>
					</div>
					<?php } ?>
					<?php if($uploadType == 2){ ?>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">DiGIR Path: </div>
						<div class="editdiv" style=""><?php echo $duManager->getDigirPath(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupdigirpath" type="text" size="50" value="<?php echo $duManager->getDigirPath(); ?>" />
						</div>
					</div>
					<?php } ?>
					<?php if($uploadType == 2){ ?>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Digir Primary Key Field: </div>
						<div class="editdiv" style=""><?php echo $duManager->getDigirPKField(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupdigirpkfield" type="text" value="<?php echo $duManager->getDigirPKField(); ?>" />
						</div>
					</div>
					<?php } ?>
					<?php if($uploadType == 1){ ?>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Username: </div>
						<div class="editdiv" style=""><?php echo $duManager->getUsername(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupusername" type="text" value="<?php echo $duManager->getUsername(); ?>" />
						</div>
					</div>
					<?php } ?>
					<?php if($uploadType == 1){ ?>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Password: </div>
						<div class="editdiv" style="">********</div>
						<div class="editdiv" style="display:none;">
							<input name="euppassword" type="text" value="<?php echo $duManager->getPassword(); ?>" />
						</div>
					</div>
					<?php } ?>
					<?php if($uploadType == 1 || $uploadType == 2){ ?>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Schema Name: </div>
						<div class="editdiv" style=""><?php echo $duManager->getSchemaName(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupschemaname" type="text" size="65" value="<?php echo $duManager->getSchemaName(); ?>" />
						</div>
					</div>
					<?php } ?>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Stored Procedure (clean/transfer): </div>
						<div class="editdiv" style=""><?php echo $duManager->getStoredProcedure(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupcleanupsp" type="text" size="40" value="<?php echo $duManager->getStoredProcedure(); ?>" />
						</div>
					</div>
					<?php if($uploadType == 1 || $uploadType == 2 || $uploadType == 4 || $uploadType == 5){ ?>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Query/Command String: </div>
						<div class="editdiv" style=""><?php echo htmlentities($duManager->getQueryStr()); ?></div>
						<div class="editdiv" style="display:none;">
							<textarea name="eupquerystr" cols="49" rows="6" ><?php echo $duManager->getQueryStr(); ?></textarea>
						</div>
					</div>
					<?php } ?>
					<div style="clear:both;">
						<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
						<input type="hidden" name="collid" value="<?php echo $collId;?>" />
						<input type="hidden" name="uploadtype" value="<?php echo $uploadType;?>" />
						<div class="editdiv" style="display:none;">
							<input type="submit" name="action" value="Submit Parameter Edits" />
						</div>
					</div>
				</fieldset>
			</form>
		</div>
		<div class="editdiv" style="display:none;">
			<form action="specimenupload.php" method="post">
				<fieldset>
					<legend>Delete this Profile</legend>
					<div>
						<input type="hidden" name="uspid" value="<?php echo $uspid; ?>" />
						<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
						<input type="submit" name="action" value="Delete Profile" />
					</div>
				</fieldset>
			</form>
		</div>
		<?php 
 	}
 	elseif($action == "Start Upload"){
		//Upload records
 		echo "<div style='font-weight:bold;font-size:120%'>Upload Status:</div>";
 		echo "<ol style='margin:10px;font-weight:bold;'>";
 		echo "<li style='font-weight:bold;'>Starting Data Upload</li>";
 		$duManager->uploadData($finalTransfer);
		echo "</ol>";
 		if($duManager->getTransferCount() && !$finalTransfer){
			?>
 			<form name="finaltransferform" action="specimenupload.php" method="post" style="margin-top:10px;" onsubmit="return checkFinalTransferForm();">
 				<fieldset>
 					<legend>Final transfer</legend>
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
	elseif($action == "addprofile"){
		?>
		<form name="paramaddform" action="specimenupload.php" method="post" onsubmit="return checkParamAddForm(this)">
			<fieldset>
				<legend>Add New Upload Profile</legend>
				<div style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Upload Type: </div>
					<div>
						<select name="aupuploadtype" onchange="adjustAddForm(this)">
							<option value="">Select an Upload Type</option>
							<option value="">------------------------</option>
							<option value="<?php echo $DIRECTUPLOAD; ?>">Direct Upload</option>
							<option value="<?php echo $DIGIRUPLOAD; ?>">DiGIR Provider</option>
							<option value="<?php echo $FILEUPLOAD; ?>">File Upload</option>
							<option value="<?php echo $STOREDPROCEDURE; ?>">Stored Procedure</option>
							<option value="<?php echo $SCRIPTUPLOAD; ?>">Script Upload</option>
						</select>
					</div>
				</div>
				<div style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Title: </div>
					<div>
						<input name="auptitle" type="text" value="" />
					</div>
				</div>
				<div id="aupplatform" style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Database Platform: </div>
					<div>
						<select name="aupplatform">
							<option value="">Select the Database Platform</option>
							<option value="">-----------------------</option>
							<option value="MySQL">MySQL</option>
							<!-- <option value="MS Access">MS Access</option>  -->
							<!-- <option value="Oracle">Oracle</option> -->
						</select>
					</div>
				</div>
				<div id="aupserver" style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Server (host): </div>
					<div>
						<input name="aupserver" type="text" size="50" value="" />
					</div>
				</div>
				<div id="aupport" style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Port: </div>
					<div>
						<input name="aupport" type="text" value="" />
					</div>
				</div>
				<div id="aupdigircode" style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">DiGIR Code: </div>
					<div>
						<input name="aupdigircode" type="text" value="" />
					</div>
				</div>
				<div id="aupdigirpath" style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">DiGIR Path: </div>
					<div>
						<input name="aupdigirpath" type="text" size="50" value="" />
					</div>
				</div>
				<div id="aupdigirpkfield" style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Digir Primary Key Field: </div>
					<div>
						<input name="aupdigirpkfield" type="text" value="" />
					</div>
				</div>
				<div id="aupusername" style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Username: </div>
					<div>
						<input name="aupusername" type="text" value="" />
					</div>
				</div>
				<div id="auppassword" style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Password: </div>
					<div>
						<input name="auppassword" type="text" value="" />
					</div>
				</div>
				<div id="aupschemaname" style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Schema Name: </div>
					<div>
						<input name="aupschemaname" type="text" size="65" value="" />
					</div>
				</div>
				<div id="aupcleanupsp" style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Stored Procedure (clean/transfer): </div>
					<div>
						<input name="aupcleanupsp" type="text" size="40" value="" />
					</div>
				</div>
				<div id="aupquerystr" style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Query/Command String: </div>
					<div>
						<textarea name="aupquerystr" cols="49" rows="6" ></textarea>
					</div>
				</div>
				<div>
					<input type="hidden" name="collid" value="<?php echo $collId;?>" />
					<div>
						<input type="submit" name="action" value="Add New Profile" />
					</div>
				</div>
			</fieldset>
		</form>
		
		<?php 
	}
 }
?>
	</div>
<?php 
include($serverRoot.'/footer.php');
?>

</body>
</html>

