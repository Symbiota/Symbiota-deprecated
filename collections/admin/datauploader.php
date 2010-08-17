<?php 
include_once("../../util/symbini.php");
include_once("util/datauploadmanager.php");
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$uploadType = array_key_exists("uploadtype",$_REQUEST)?$_REQUEST["uploadtype"]:0;
$uspid = array_key_exists("uspid",$_REQUEST)?$_REQUEST["uspid"]:0;
$finalTransfer = array_key_exists("finaltransfer",$_REQUEST)?$_REQUEST["finaltransfer"]:0;
$doFullReplace = array_key_exists("dofullreplace",$_REQUEST)?$_REQUEST["dofullreplace"]:0;
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
if($uspid) $duManager->setUspid($uspid);
if($doFullReplace) $duManager->setDoFullReplace($doFullReplace);

$isEditable = 0;
if($isAdmin || in_array("coll-".$collId,$userRights)){
	$isEditable = 1;
}
if($isEditable){
	if($action == "Submit Parameter Edits"){
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

		function checkInitForm(){
			var ufObj = document.getElementById("uploadfile");
			if(ufObj != null){
				if(ufObj.value == null){
					alert("Please select the file that is to be analyzed");
					return false;
				}
			}
			return true;
		}

		function checkParameterForm(){
			var formCheck = true;

			return formCheck;
		}
		
		function checkFinalTransferForm(){
			return confirm('Are you sure you want to transfer records from temporary table to central specimen table?');
		}

		function checkParamAddForm(){
			var formCheck = true;

			return formCheck;
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
 		if(stripos($action,"delete") !== False){
 			$duManager->deleteFieldMap();
 		}
 		else{
	 		$targetFields = $_REQUEST["tf"];
	 		$sourceFields = $_REQUEST["sf"];
	 		$fieldMap = Array();
			for($x = 0;$x<count($targetFields);$x++){
				if($targetFields[$x]) $fieldMap[$targetFields[$x]]["field"] = $sourceFields[$x];
			}
	 		$duManager->setFieldMap($fieldMap);
 		}
 		if(stripos($action,"save") !== False){
 			$duManager->saveFieldMap();
 		}
 	}
 	
 	$collInfo = $duManager->getCollInfo();
 	echo "<h2>".$collInfo["name"]."</h2>";
 	
 	if(!$action){
	 	$actionList = $duManager->getUploadList();
		?>
		<form name="uploadlistform" action="datauploader.php" method="post">
			<fieldset style="width:450px;">
				<legend style="font-weight:bold;font-size:120%;">Upload Options</legend>
				<?php 
			 	foreach($actionList as $id => $v){
			 		?>
			 		<div style="margin:10px;">
						<input type="radio" name="uspid" value="<?php echo $id;?>" />
					<?php echo $v["title"];?>
					</div>
					<input type="hidden" name="collid" value="<?php echo $collId;?>" />
					<input type="hidden" name="uploadtype" value="<?php echo $v["uploadtype"];?>" />
					<div style="margin:10px;">
						<input type="submit" name="action" value="Initialize Upload..." />
					</div>
				<?php 
			 	}
			 	if(!$actionList){
			 		?>
					<div>
						There are no Upload Profiles associated with this collection. <br />
						Click <a href="datauploader.php?collid=<?php echo ($collId);?>&action=addprofile">here</a> to add a new profile.
					</div>
					<?php 
			 	}
			 	 ?>
			</fieldset>
		</form>
		<hr />
	<?php 
 	}
 	elseif(stripos($action,"initialize") !== false || stripos($action,"analysis") !== false || stripos($action,"map") !== false){
	 	$ulList = $duManager->getUploadList($uspid);
	 	$ulArr = array_pop($ulList); 
		?>
		<form name="initform" action="datauploader.php" method="post" <?php echo ($uploadType==$FILEUPLOAD?"enctype='multipart/form-data'":"")?> onsubmit="return checkInitForm()">
			<fieldset style="width:450px;">
				<legend style="font-weight:bold;font-size:120%;"><?php echo $ulArr["title"];?></legend>
				<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
				<input type="hidden" name="collid" value="<?php echo $collId;?>" />
				<input type="hidden" name="uploadtype" value="<?php echo $uploadType;?>" />
				<?php if($uploadType == $FILEUPLOAD){ ?>
					<input type='hidden' name='MAX_FILE_SIZE' value='10000000' />
					<div>
						<b>Upload File:</b>
						<div style="margin:10px;">
							<input id="uploadfile" name="uploadfile" type="file" size="40" />
						</div>
						<div style="margin:10px;">
							<input type="submit" name="action" value="View Parameters" />
							<input type="submit" name="action" value="Analyze Upload File" />
						</div>
					</div>
				<?php } ?>
				<?php if($uploadType == $DIRECTUPLOAD || ($uploadType == $FILEUPLOAD && stripos($action,"analyze") !== false)){ ?>
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
							$duManager->analyzeFile($autoMap); 
						?>
					</table>
					<div>
						* Mappings that are not yet save are displayed in Yellow
					</div>
					<div style="margin:10px;">
						<input type="submit" name="action" value="Delete Field Mapping" />
						<input type="submit" name="action" value="Automap Fields" />
						<input type="submit" name="action" value="Save Mapping" />
					</div>
					<hr />
				<?php } ?>
				<?php if(($uploadType != $FILEUPLOAD || stripos($action,"analyze") !== false)){ ?>
					<div>
						<div style="margin:10px;">
							<input type="submit" name="action" value="View Parameters" />
							<input type="submit" name="action" value="Start Upload" />
						</div>
		 				<div style="margin:10px 0px 0px 10px;">
		 					<input type="checkbox" name="finaltransfer" value="1" <?php echo ($finalTransfer?"checked":""); ?> onclick="toggle('dodiv')"/>
		 					Perform Final Transfer and Make Public
						</div>
						<div id="dodiv" style="display:none;margin-left:30px;">
							<div>
								<input name="dofullreplace" type="radio" value="0" checked /> Append New / Update Modified Records
							</div>
							<div>
								<input name="dofullreplace" type="radio" value="1" /> Replace All Records
							</div>
						</div>
					</div>
				<?php } ?>
			</fieldset>
		</form>
		<?php 
 	}
 	elseif(stripos($action,"parameter") !== false){
	 	$actionList = $duManager->getUploadList();
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
		<form name="parameterform" action="datauploader.php" method="post" onsubmit="return checkParameterForm()">
			<fieldset>
				<legend><?php echo $editTitle; ?> Upload Parameters</legend>
				<div style="float:right;cursor:pointer;" onclick="javascript:toggle('editdiv');" title="Toggle Editing Functions">
					<img style='border:0px;' src='../../images/edit.png'/>
				</div>
				<div style="clear:both;">
					<div style="width:200px;font-weight:bold;float:left;">Upload Type: </div>
					<div class="editdiv" style=""><?php echo $editTitle; ?> Upload</div>
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
					<div style="width:200px;font-weight:bold;float:left;">Title: </div>
					<div class="editdiv" style=""><?php echo $duManager->getTitle(); ?></div>
					<div class="editdiv" style="display:none;">
						<input name="euptitle" type="text" value="<?php echo $duManager->getTitle(); ?>" />
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
					<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
					<input type="hidden" name="collid" value="<?php echo $collId;?>" />
					<div class="editdiv" style="display:none;">
						<input type="submit" name="action" value="Submit Parameter Edits" />
					</div>
					<div class="editdiv" style="display:block;">
						<input type="submit" name="action" value="Initialize Upload..." />
					</div>
				</div>
			</fieldset>
		</form>
		<div class="editdiv" style="display:none;">
			<form action="datauploader.php" method="get">
				<fieldset>
					<legend>Delete this Profile</legend>
					<div>
						<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
						<input type="submit" name="action" value="Delete Profile" />
					</div>
				</fieldset>
			</form>
		</div>
		<?php 
 	}
 	elseif(stripos($action,"upload") !== false){
		//Upload records
 		echo "<div style='font-weight:bold;font-size:120%'>Starting Data Upload: </div>";
 		echo "<ol style='margin:10px;font-weight:bold;'>";
 		$duManager->uploadData($finalTransfer);
		echo "</ol>";
 		if($duManager->getTransferCount() && !$finalTransfer){
			?>
 			<form name="finaltransferform" action="datauploader.php" method="get" style="margin-top:10px;" onsubmit="return checkFinalTransferForm();">
 				<fieldset>
 					<legend>Final transfer</legend>
 					<input type="hidden" name="collid" value="<?php echo $collId;?>" /> 
 					<input type="hidden" name="uploadtype" value="<?php echo $uploadType;?>" />
 					<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
 					<div style="font-weight:bold;margin:5px;"> 
 						Number of records uploaded to temporary table (uploadspectemp): <?php echo $duManager->getTransferCount();?>
					</div>
 					<div style="margin:5px;"> 
 						If this sounds correct, transfer to central specimen table using this form. Note that your old specimens 
 						records will be replaced. You may want to inspect uploadspectemp records to verify initial upload. 
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
 						<input type="submit" name="action" value="Transfer Records to Central Specimen Table" />
					</div>
 				</fieldset>			
 			</form>
			<?php 							
		}
 	}
	elseif(stripos($action,"transfer") !== false || $finalTransfer){
		$duManager->performFinalTransfer();
	}
	elseif($action == "addprofile"){
		?>
		<form name="paramaddform" action="datauploader.php" method="post" onsubmit="return checkParamAddForm()">
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
 }
?>
	</div>
<?php 
include($serverRoot."/util/footer.php");
?>

</body>
</html>

