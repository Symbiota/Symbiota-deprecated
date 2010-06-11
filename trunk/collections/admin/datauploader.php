<?php 
include_once("../../util/symbini.php");
include_once("util/datauploadmanager.php");
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$uploadType = array_key_exists("uploadtype",$_REQUEST)?$_REQUEST["uploadtype"]:0;
$finalTransfer = array_key_exists("finaltransfer",$_REQUEST)?$_REQUEST["finaltransfer"]:0;
$doFullReplace = array_key_exists("dofullreplace",$_REQUEST)?$_REQUEST["dofullreplace"]:"0";
$statusStr = "";
$DIRECTUPLOAD = 1;$DIGIRUPLOAD = 2; $FILEUPLOAD = 3; $STOREDPROCEDURE = 4;

$duManager;
if($uploadType == $DIRECTUPLOAD){
	$duManager = new DirectUpload();
}
elseif($uploadType == $DIGIRUPLOAD){
	$duManager = new DigirUpload();
}
elseif($uploadType == $FILEUPLOAD){
	$duManager = new FileUpload();
}
else{
	$duManager = new DataUploadManager();
}

if($collId) $duManager->setCollId($collId);
if($uploadType) $duManager->setUploadType($uploadType);
if($finalTransfer) $duManager->setFinalTransfer($finalTransfer);
if($doFullReplace) $duManager->setDoFullReplace($doFullReplace);

$isEditable = 0;
if($isAdmin || in_array("coll-".$collId,$userRights)){
	$isEditable = 1;
}
if($isEditable){
	if($action == "Submit Edits"){
		$statusStr = $duManager->editUploadProfile();
	}
	elseif($action == "Add New Profile"){
		$statusStr = $duManager->addUploadProfile();
	}
	elseif($action == "Delete Profile"){
		$statusStr = $duManager->deleteUploadProfile($collId, $_REQUEST["dupuploadtype"]);
	}
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
	<title><?php echo $defaultTitle; ?>Data Uploader</title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
	<script language=javascript>
		
		function toggle(target){
			var div = document.getElementById(target);
			if(div != null){
				if(div.style.display=="none"){
					div.style.display="block";
				}
			 	else {
			 		div.style.display="none";
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

		function checkAnalyzeForm(){
			uploadObj = getElementById("uploadfile");
			if(uploadObj.value = null){
				alert("Please select the file that is to be analyzed");
				return false;
			}
			return true;
		}

		function checkUploadForm(){
			var submitForm = false;
			submitForm = confirm('Are you sure you want to upload new specimens records?');
			uploadObj = getElementById("uploadfile");
			if(uploadObj.value = null){
				alert("Please select the file that is to be uploaded");
				submitForm = false;
			}
			return submitForm;
		}
	</script>
</head>
<body>
<?php
	$displayLeftMenu = (isset($collections_admin_datauploaderMenu)?$collections_admin_datauploaderMenu:"true");
	include($serverRoot."/util/header.php");
	if(isset($collections_admin_datauploaderCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $collections_admin_datauploaderCrumbs;
		echo " <b>Specimen Loader</b>"; 
		echo "</div>";
	}
?> 
<!-- This is inner text! -->
<div id="innertext">
	<div style="float:right;">
		<?php if($collId){ ?>
		<a href="datauploader.php">
			<img src="<?php echo $clientRoot;?>/images/toparent.jpg" style="width:15px;border:0px;" title="Return to upload listing" />
		</a>
		<a href="datauploader.php?collid=<?php echo ($collId);?>&action=addprofile">
			<img src="<?php echo $clientRoot;?>/images/add.png" style="width:15px;border:0px;" title="Add a New Upload Profile" />
		</a>
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
	 	$collList = $duManager->getCollectionList($userRights);
		echo "<h2>Select Collection to Update</h2>";
	 	echo "<ul>";
	 	foreach($collList as $k => $v){
	 		echo "<li><a href='datauploader.php?collid=".$k."'>$v</a></li>";
	 	}
	 	echo "</ul>";
	 	if(!$collList) echo "<div>There are no Database for which you have authority to update</div>";
 	}
 	else{
 		echo "<div style='font-weight:bold;'>Please <a href='../../profile/index.php?refurl=".$clientRoot."/collections/admin/datauploader.php'>login</a>!</div>";
 	}
 }
 else{
 	
 	if(array_key_exists("sf",$_REQUEST)){
 		$targetFields = $_REQUEST["tf"];
 		$sourceFields = $_REQUEST["sf"];
 		$fieldMap = Array();
		for($x = 0;$x<count($targetFields);$x++){
			if($targetFields[$x]) $fieldMap[$targetFields[$x]]["field"] = $sourceFields[$x];
		}
 		$duManager->setFieldMap($fieldMap);
 		if(array_key_exists("savefieldmap",$_REQUEST) && $_REQUEST["savefieldmap"]){
 			$duManager->saveFieldMap();
 		}
 	}
 	
 	$collInfo = $duManager->getCollInfo();
 	echo "<h2>".$collInfo["name"]."</h2>";
 	
	if($action == "addprofile"){
		?>
		<form name="parameditform" action="datauploader.php" method="post">
			<fieldset>
				<legend>Add New Upload Profile</legend>
				<div style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Upload Type: </div>
					<div>
						<select name="aupuploadtype">
							<option value="">Select an Upload Type</option>
							<option value="">------------------------</option>
							<option value="<?php echo $DIGIRUPLOAD; ?>">DiGIR Provider</option>
							<option value="<?php echo $DIRECTUPLOAD; ?>">Direct Upload</option>
							<option value="<?php echo $FILEUPLOAD; ?>">File Upload</option>
							<option value="<?php echo $STOREDPROCEDURE; ?>">Stored Procedure</option>
						</select>
					</div>
				</div>
				<div style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Title: </div>
					<div>
						<input name="auptitle" type="text" value="" />
					</div>
				</div>
				<div style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Database Platform: </div>
					<div>
						<select name="aupplatform">
							<option value="">Select the Database Platform</option>
							<option value="">-----------------------</option>
							<option value="MySQL">MySQL</option>
							<option value="MS Access">MS Access</option>
							<option value="Oracle">Oracle</option>
						</select>
					</div>
				</div>
				<div style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Server (host): </div>
					<div>
						<input name="aupserver" type="text" value="" />
					</div>
				</div>
				<div style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Port: </div>
					<div>
						<input name="aupport" type="text" value="" />
					</div>
				</div>
				<div style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Driver: </div>
					<div>
						<input name="aupdriver" type="text" value="" />
					</div>
				</div>
				<div style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">DiGIR Code: </div>
					<div>
						<input name="aupdigircode" type="text" value="" />
					</div>
				</div>
				<div style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">DiGIR Path: </div>
					<div>
						<input name="aupdigirpath" type="text" value="" />
					</div>
				</div>
				<div style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Digir Primary Key Field: </div>
					<div>
						<input name="aupdigirpkfield" type="text" value="" />
					</div>
				</div>
				<div style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Username: </div>
					<div>
						<input name="aupusername" type="text" value="" />
					</div>
				</div>
				<div style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Password: </div>
					<div>
						<input name="auppassword" type="text" value="" />
					</div>
				</div>
				<div style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Schema Name: </div>
					<div>
						<input name="aupschemaname" type="text" value="" />
					</div>
				</div>
				<div style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Cleanup SP: </div>
					<div>
						<input name="aupcleanupsp" type="text" value="" />
					</div>
				</div>
				<div style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">DateLastModified Field Is Valid: </div>
					<div>
						<select name="aupdlmisvalid">
							<option value="0">false</option>
							<option value="1">true</option>
						</select>
					</div>
				</div>
				<div style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Query String: </div>
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
 	elseif(stripos($action,"upload") === false && stripos($action,"transfer") === false){
	 	$actionList = $duManager->getUploadList($collId);
		foreach($actionList as $k => $v){
			?>
			<form name="uploadform" action="datauploader.php" method="post" <?php echo ($k==$FILEUPLOAD?"enctype='multipart/form-data'":"")?> onsubmit="return checkAnalyzeForm()">
				<fieldset style="width:450px;">
					<legend style="font-weight:bold;font-size:120%;"><?php echo $v;?></legend>
					<input type="hidden" name="collid" value="<?php echo $collId;?>" />
					<input type="hidden" name="uploadtype" value="<?php echo $k;?>" />
					<?php if(stripos($action,"Analyze") !== false && $uploadType == $k){ ?>
						<table border="1" cellpadding="2" style="border:1px solid black">
							<tr>
								<th>
									Source Field
								</th>
								<th>
									Target Field
								</th>
							</tr>
							<?php $duManager->analyzeFile(); ?>
						</table>
					<?php } ?>
					<?php if($k == $FILEUPLOAD){ ?>
						<input type='hidden' name='MAX_FILE_SIZE' value='10000000' />
						<div>
							<b>Upload File:</b> 
							<input id="uploadfile" name="uploadfile" type="file" size="40" />
						</div>
					<?php } ?>
	
						<input type="submit" name="action" value="View/Edit Parameters..." />
					<?php if($k != $STOREDPROCEDURE && $k != $DIGIRUPLOAD && stripos($action,"Analyze") === false){ ?>
						<input type="submit" name="action" value="Analyze..." />
					<?php } ?>
					<?php if($k == $STOREDPROCEDURE || $k == $DIGIRUPLOAD || stripos($action,"Analyze")!==false) { ?>
						<input type="submit" name="action" value="Start Upload..." />
					<?php if(stripos($action,"Analyze")!==false){ ?>
						<div>
							<input type="checkbox" name="savefieldmap" value="1" /> Save Field Mapping
						</div>
					<?php } ?>
		 				<div>
		 					<input type="checkbox" name="finaltransfer" value="1" <?php echo ($finalTransfer?"checked":""); ?> onclick="toggle('dodiv')"/>
		 					Perform Final Transfer and Make Public 
						</div>
						<div id="dodiv" style="display:none;">
							<div>
								<input name="dofullreplace" type="radio" value="0" /> Append New / Update Modified Records
							</div>
							<div>
								<input name="dofullreplace" type="radio" value="1" /> Replace All Records
							</div>
						</div>
					<?php } ?>
				</fieldset>
			</form>
			<hr />
			<?php 
		}
	 	
		//Edit Parameters
		if(stripos($action,"Edit") !== false){
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
			<form name="parameditform" action="datauploader.php" method="post">
				<fieldset>
					<legend><?php echo $editTitle; ?> Upload Parameters</legend>
					<div style="float:right;cursor:pointer;" onclick="javascript:toggle('editdiv');" title="Toggle Editing Functions">
						<img style='border:0px;' src='../../images/edit.png'/>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Upload Type: </div>
						<div class="editdiv" style=""><?php echo $uploadType; ?></div>
						<div class="editdiv" style="display:none;">
							<select name="eupuploadtype">
								<option value="">Select an Upload Type</option>
								<option value="<?php echo $DIGIRUPLOAD; ?>" <?php if($uploadType == $DIGIRUPLOAD) echo "SELECTED";?>>DiGIR Provider</option>
								<option value="<?php echo $DIRECTUPLOAD; ?>" <?php if($uploadType == $DIRECTUPLOAD) echo "SELECTED";?>>Direct Upload</option>
								<option value="<?php echo $FILEUPLOAD; ?>" <?php if($uploadType == $FILEUPLOAD) echo "SELECTED";?>>File Upload</option>
								<option value="<?php echo $STOREDPROCEDURE; ?>" <?php if($uploadType == $STOREDPROCEDURE) echo "SELECTED";?>>Stored Procedure</option>
							</select>
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Database Platform: </div>
						<div class="editdiv" style=""><?php echo $duManager->getPlatform(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupplatform" type="text" value="<?php echo $duManager->getPlatform(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Server: </div>
						<div class="editdiv" style=""><?php echo $duManager->getServer(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupserver" type="text" value="<?php echo $duManager->getServer(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Port: </div>
						<div class="editdiv" style=""><?php echo $duManager->getPort(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupport" type="text" value="<?php echo $duManager->getPort(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Driver: </div>
						<div class="editdiv" style=""><?php echo $duManager->getDriver(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupdriver" type="text" value="<?php echo $duManager->getDriver(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">DiGIR Code: </div>
						<div class="editdiv" style=""><?php echo $duManager->getDigirCode(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupdigircode" type="text" value="<?php echo $duManager->getDigirCode(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">DiGIR Path: </div>
						<div class="editdiv" style=""><?php echo $duManager->getDigirPath(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupdigirpath" type="text" value="<?php echo $duManager->getDigirPath(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Digir Primary Key Field: </div>
						<div class="editdiv" style=""><?php echo $duManager->getDigirPKField(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupdigirpkfield" type="text" value="<?php echo $duManager->getDigirPKField(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Username: </div>
						<div class="editdiv" style=""><?php echo $duManager->getUsername(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupusername" type="text" value="<?php echo $duManager->getUsername(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Password: </div>
						<div class="editdiv" style="">********</div>
						<div class="editdiv" style="display:none;">
							<input name="euppassword" type="text" value="<?php echo $duManager->getPassword(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Schema Name: </div>
						<div class="editdiv" style=""><?php echo $duManager->getSchemaName(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupschemaname" type="text" value="<?php echo $duManager->getSchemaName(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Cleanup SP: </div>
						<div class="editdiv" style=""><?php echo $duManager->getCleanupSP(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="eupcleanupsp" type="text" value="<?php echo $duManager->getCleanupSP(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">DateLastModified Field Is Valid: </div>
						<div class="editdiv" style=""><?php echo ($duManager->getDLMIsValid()?"true":"false"); ?></div>
						<div class="editdiv" style="display:none;">
							<select name="eupdlmisvalid">
								<option value="0">false</option>
								<option value="1" <?php echo ($duManager->getDLMIsValid()?"SELECTED":""); ?>>true</option>
							</select>
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Query String: </div>
						<div class="editdiv" style=""><?php echo htmlentities($duManager->getQueryStr()); ?></div>
						<div class="editdiv" style="display:none;">
							<textarea name="eupquerystr" cols="49" rows="6" ><?php echo $duManager->getQueryStr(); ?></textarea>
						</div>
					</div>
					<div>
						<input type="hidden" name="collid" value="<?php echo $collId;?>" />
						<div class="editdiv" style="display:none;">
							<input type="submit" name="action" value="Submit Edits" />
						</div>
					</div>
				</fieldset>
			</form>
			<div class="editdiv" style="display:none;">
				<form action="datauploader.php" method="get">
					<fieldset>
						<legend>Delete this Profile</legend>
						<div>
							<input type="hidden" name="collid" value="<?php echo $collId;?>" />
							<input type="hidden" name="dupuploadtype" value="<?php echo $uploadType;?>" />
							<input type="submit" name="action" value="Delete Profile" />
						</div>
					</fieldset>
				</form>
			</div>
			<?php 
		}
 	}
 	else{
		//Upload records
		if(stripos($action,"upload") !== false){
	 		echo "<div style='font-weight:bold;font-size:120%'>Starting Data Upload: </div>";
	 		echo "<ol style='margin:10px;font-weight:bold;'>";
	 		$duManager->uploadData();
			echo "</ol>";
	 		if($duManager->getTransferCount() && !$finalTransfer){
				?>
	 			<form name="finaltransferform" action="datauploader.php" method="get" style="margin-top:10px;" onsubmit="return confirm('Are you sure you want to transfer records from temporary table to central specimen table?');">
	 				<fieldset>
	 					<legend>Direct Upload: Final transfer</legend>
	 					<input type="hidden" name="collid" value="<?php echo $collId;?>" /> 
	 					<input type="hidden" name="uploadtype" value="<?php echo $uploadType;?>" />
	 					<div style="font-weight:bold;margin:5px;"> 
	 						Number of records uploaded to temporary table (uploadspectemp): <?php echo $duManager->getTransferCount();?>
						</div>
	 					<div style="margin:5px;"> 
	 						If this sounds correct, transfer to central specimen table using this form. Note that your old specimens 
	 						records will be replaced. You may want to inspect uploadspectemp records to verify initial upload. 
						</div>
	 					<div> 
	 						<input type="checkbox" name="finaltransfer" value="1" CHECKED READONLY />
	 						Perform Final Transfer (temp table &#61;&gt; specimen table, update stats, clear temp table) 
						</div>
						<div>
							<input name="dofullreplace" type="radio" value="0" <?php if(!$doFullReplace) echo "SELECTED"; ?> /> 
							Append New / Update Modified Records
						</div>
						<div>
							<input name="dofullreplace" type="radio" value="1" <?php if($doFullReplace) echo "SELECTED"; ?> /> 
							Replace All Records
						</div>
	 					<div style="margin:5px;"> 
	 						<input type="submit" name="action" value="Transfer to Central Specimen Table" />
						</div>
	 				</fieldset>			
	 			</form>
				<?php 							
			}
		}
		else{
	 		$duManager->finalTransfer = 1;
			$duManager->performFinalTransfer();
		}
 	}
 }
?>
	</div>
<?php 
include($serverRoot."/util/footer.php");
?>

</body>
</html>

