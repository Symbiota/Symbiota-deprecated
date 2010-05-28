<?php 
include_once("../../util/symbini.php");
include_once("util/datauploadmanager.php");
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$uploadType = array_key_exists("uploadtype",$_REQUEST)?$_REQUEST["uploadtype"]:0;
$finalTransfer = array_key_exists("finaltransfer",$_REQUEST)?$_REQUEST["finaltransfer"]:0;
$doFullReplace = array_key_exists("dofullreplace",$_REQUEST)?$_REQUEST["dofullreplace"]:"0";
$statusStr = "";
$DIRECTUPLOAD = 1;$DIGIRUPLOAD = 2; $FILEUPLOAD = 3;

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
if($isEditable && array_key_exists("parameditsubmit",$_REQUEST)){
	$statusStr = $duManager->editUploadParameter();
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
			if(uploadObj.value) == null){
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
	<h1>Data Upload Module</h1>
<?php

if($statusStr){
	echo "<hr />";
	echo "<div>$statusStr</div>";
	echo "<hr />";
}

 if(!$collId){
 	if($uid){
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
 		echo "<div style='font-weight:bold;'>Please <a href='../../profile/index.php?refurl=/seinet/collections/admin/datauploader.php'>login</a>!</div>";
 	}
 }
 else{
 	
 	$collInfo = $duManager->getCollInfo();
 	echo "<h2>".$collInfo["name"]."</h2>";
 	
 	if(!$uploadType){
 		echo "<div class='fieldset' style='width:300px;'>";
	 	echo "<div class='legend'>Available Upload Options:</div>";
		$actionList = $duManager->getUploadList($collId);
		foreach($actionList as $k => $v){
			?>
			<div style='clear:both;height:25px;background-color:lightgray;padding:15px;border:1px solid black;'>
				<div style='font-weight:bold;float:left;font-size:120%;'>
					<?php echo $v;?>:
				</div>
				<div style='float:right;'>
					<form name="uploadform" action="datauploader.php" method="post">
						<input type="hidden" name="collid" value="<?php echo $collId;?>" />
						<input type="hidden" name="uploadtype" value="<?php echo $k;?>" />
						<input type="submit" name="uploadsubmit" value="Perform..." />
					</form>
				</div>
			</div>
			<hr />
			<?php 
		}
		echo "</div>";
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
 		$duManager->readUploadParameters();
		if(!$action || $action == "Analyze Upload"){
			if(!$action && $uploadType != $DIGIRUPLOAD){
				?> 
	 			<form name="analyzeform" action="datauploader.php" enctype="multipart/form-data" method="post" style="margin-top:10px;" onsubmit="return checkAnalyzeForm()">
	 				<fieldset>
	 					<legend>
	 						<?php 
	 						if($uploadType == $DIRECTUPLOAD){
		 						echo "Direct Upload Analyze Panel";
	 						}
	 						elseif($uploadType == $DIGIRUPLOAD){
		 						echo "DiGIR Upload Analyze Panel";
	 						}
	 						elseif($uploadType == $FILEUPLOAD){
		 						echo "File Upload Analyze Panel";
	 						}
	 						?>
	 					</legend>
	 					<input type="hidden" name="collid" value="<?php echo $collId;?>" /> 
	 					<input type="hidden" name="uploadtype" value="<?php echo $uploadType;?>" />
						<?php if($uploadType == $FILEUPLOAD){ ?>
							<input type='hidden' name='MAX_FILE_SIZE' value='10000000' />
							<div>
								<b>Upload File:</b> 
								<input id="uploadfile" name="uploadfile" type="file" size="40" />
							</div>
						<?php } ?>
	 					<div>
							<input type="submit" name="action" value="Analyze Upload" />
						</div>
	 				</fieldset>			
	 			</form>
	 			<?php 
			}
			else{
				?>
	 			<form name="uploadform" action="datauploader.php" method="post" enctype="multipart/form-data" style="margin-top:10px;" onsubmit="return checkUploadForm();">
	 				<fieldset>
	 					<legend>
	 						<?php 
	 						if($uploadType == $DIRECTUPLOAD){
		 						echo "Direct Upload Panel";
	 						}
	 						elseif($uploadType == $DIGIRUPLOAD){
		 						echo "DiGIR Upload Panel";
	 						}
	 						elseif($uploadType == $FILEUPLOAD){
		 						echo "File Upload Panel";
	 						}
	 						?>
	 					</legend>
	 					<input type="hidden" name="collid" value="<?php echo $collId;?>" />
	 					<input type="hidden" name="uploadtype" value="<?php echo $uploadType;?>" />
						<?php if($uploadType == $FILEUPLOAD){ ?>
							<div style="margin:5px 0px 10px 0px;">
								<b>Upload File:</b>
								<input id="uploadfile" name="uploadfile" type="file" size="40" />
								<input type='hidden' name='MAX_FILE_SIZE' value='30000000' />
							</div>
						<?php } ?>
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
	 					<div>
	 						<input type="checkbox" name="finaltransfer" value="1" <?php echo ($finalTransfer?"checked":""); ?> onclick="toogle('dodiv')"/>
	 						Perform Final Transfer (temp table &#61;&gt; specimen table, update stats, clear temp table) 
						</div>
						<div id="dodiv" style="display:none;">
							<div>
								<input name="dofullreplace" type="radio" value="0" /> Append New / Update Modified Records
							</div>
							<div>
								<input name="dofullreplace" type="radio" value="1" /> Replace All Records
							</div>
						</div>
						<div>
							<input type="checkbox" name="savefieldmap" value="1" /> Save Field Mapping
						</div>
	 					<div>
	 						<input type="submit" name="action" value="Upload Data" />
						</div>
	 				</fieldset>			
	 			</form>
	 			<?php 
			}
			?>
			<form name="parameditform" action="datauploader.php" method="post">
				<fieldset>
					<legend>Upload Parameters</legend>
					<div style="float:right;cursor:pointer;" onclick="javascript:toggle('editdiv');" title="Toggle Editing Functions">
						<img style='border:0px;' src='../../images/edit.png'/>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Upload Type: </div>
						<div class="editdiv" style=""><?php echo $uploadType; ?></div>
						<div class="editdiv" style="display:none;">
							<select name="uploadtype">
								<option value="">Select an Upload Type</option>
								<option value="<?php echo $DIGIRUPLOAD; ?>" <?php if($uploadType == $DIGIRUPLOAD) echo "SELECTED";?>>DiGIR Provider</option>
								<option value="<?php echo $DIRECTUPLOAD; ?>" <?php if($uploadType == $DIRECTUPLOAD) echo "SELECTED";?>>Direct Upload</option>
								<option value="<?php echo $FILEUPLOAD; ?>" <?php if($uploadType == $FILEUPLOAD) echo "SELECTED";?>>File Upload</option>
							</select>
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Database Platform: </div>
						<div class="editdiv" style=""><?php echo $duManager->getPlatform(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="platform" type="text" value="<?php echo $duManager->getPlatform(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Server: </div>
						<div class="editdiv" style=""><?php echo $duManager->getServer(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="server" type="text" value="<?php echo $duManager->getServer(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Port: </div>
						<div class="editdiv" style=""><?php echo $duManager->getPort(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="port" type="text" value="<?php echo $duManager->getPort(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Driver: </div>
						<div class="editdiv" style=""><?php echo $duManager->getDriver(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="driver" type="text" value="<?php echo $duManager->getDriver(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">DiGIR Code: </div>
						<div class="editdiv" style=""><?php echo $duManager->getDigirCode(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="digircode" type="text" value="<?php echo $duManager->getDigirCode(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">DiGIR Path: </div>
						<div class="editdiv" style=""><?php echo $duManager->getDigirPath(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="digirpath" type="text" value="<?php echo $duManager->getDigirPath(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Digir Primary Key Field: </div>
						<div class="editdiv" style=""><?php echo $duManager->getDigirPKField(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="digirpkfield" type="text" value="<?php echo $duManager->getDigirPKField(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Username: </div>
						<div class="editdiv" style=""><?php echo $duManager->getUsername(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="username" type="text" value="<?php echo $duManager->getUsername(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Password: </div>
						<div class="editdiv" style="">********</div>
						<div class="editdiv" style="display:none;">
							<input name="password" type="text" value="<?php echo $duManager->getPassword(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Schema Name: </div>
						<div class="editdiv" style=""><?php echo $duManager->getSchemaName(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="schemaname" type="text" value="<?php echo $duManager->getSchemaName(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Cleanup SP: </div>
						<div class="editdiv" style=""><?php echo $duManager->getCleanupSP(); ?></div>
						<div class="editdiv" style="display:none;">
							<input name="cleanupsp" type="text" value="<?php echo $duManager->getCleanupSP(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">DateLastModified Field Is Valid: </div>
						<div class="editdiv" style=""><?php echo ($duManager->getDLMIsValid()?"true":"false"); ?></div>
						<div class="editdiv" style="display:none;">
							<select name="dlmisvalid">
								<option value="0">false</option>
								<option value="1" <?php echo ($duManager->getDLMIsValid()?"SELECTED":""); ?>>true</option>
							</select>
						</div>
					</div>
					<div style="clear:both;">
						<div style="width:200px;font-weight:bold;float:left;">Query String: </div>
						<div class="editdiv" style=""><?php echo htmlentities($duManager->getQueryStr()); ?></div>
						<div class="editdiv" style="display:none;">
							<textarea name="querystr" cols="49" rows="6" >
								<?php echo $duManager->getQueryStr(); ?>
							</textarea>
						</div>
					</div>
					<div>
						<input type="hidden" name="collid" value="<?php echo $collId;?>" />
						<input type="hidden" name="uploadtype" value="<?php echo $uploadType;?>" />
						<div class="editdiv" style="display:none;">
							<input type="submit" name="parameditsubmit" value="Submit Edits" />
						</div>
					</div>
				</fieldset>
			</form>
			<?php
		}
		elseif($action == "Upload Data"){
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
							<input name="dofullreplace" type="radio" value="0" <?php if(!$doFullReplace) echo "SELECTED"; ?> /> Append New / Update Modified Records
						</div>
						<div>
							<input name="dofullreplace" type="radio" value="1" <?php if($doFullReplace) echo "SELECTED"; ?> /> Replace All Records
						</div>
	 					<div style="margin:5px;"> 
	 						<input type="submit" name="action" value="Transfer to Central Specimen Table" />
						</div>
	 				</fieldset>			
	 			</form>
				<?php 							
			}
		}
		elseif($action == "Transfer to Central Specimen Table"){
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

