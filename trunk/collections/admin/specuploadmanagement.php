<?php 
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecUpload.php');
header("Content-Type: text/html; charset=".$charset);
if(!$symbUid) header('Location: ../../profile/index.php?refurl=../collections/admin/specuploadmanagement.php?'.$_SERVER['QUERY_STRING']);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$uspid = array_key_exists("uspid",$_REQUEST)?$_REQUEST["uspid"]:0;
$statusStr = "";
$DIRECTUPLOAD = 1;$DIGIRUPLOAD = 2; $FILEUPLOAD = 3; $STOREDPROCEDURE = 4; $SCRIPTUPLOAD = 5;$DWCAUPLOAD = 6;

$duManager = new SpecUpload();

$duManager->setCollId($collId);
$duManager->setUspid($uspid);

$isEditor = 0;
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
	$isEditor = 1;
}
if($isEditor){
	if($action == "Save Edits"){
		$statusStr = $duManager->editUploadProfile();
		$action = "";
	}
	elseif($action == "Create Profile"){
		$statusStr = $duManager->addUploadProfile();
		$action = "";
	}
	elseif($action == "Delete Profile"){
		$statusStr = $duManager->deleteUploadProfile($uspid);
		$action = "";
	}
}
$duManager->readUploadParameters();
?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Specimen Upload Profile Manager</title>
	<link href="../../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css" type="text/css" rel="stylesheet" />
	<script language=javascript>
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

		function checkParameterForm(f){
			if(f.title.value == ""){
				alert("Profile title is required");
				return false;
			}
			else if(f.uploadtype.value == ""){
				alert("Select Upload Type");
				return false;
			}
			return true;
		}
		
		function adjustParameterForm(){
			//Hide all
			document.getElementById("platformDiv").style.display='none';
			document.getElementById("serverDiv").style.display='none';
			document.getElementById("portDiv").style.display='none';
			document.getElementById("codeDiv").style.display='none';
			document.getElementById("pathDiv").style.display='none';
			document.getElementById("pkfieldDiv").style.display='none';
			document.getElementById("usernameDiv").style.display='none';
			document.getElementById("passwordDiv").style.display='none';
			document.getElementById("schemanameDiv").style.display='none';
			document.getElementById("cleanupspDiv").style.display='none';
			document.getElementById("querystrDiv").style.display='none';
			//Then open according to upload type selection
			selValue = document.parameterform.uploadtype.value;
			if(selValue == 1){ //Direct Upload
				document.getElementById("platformDiv").style.display='block';
				document.getElementById("serverDiv").style.display='block';
				document.getElementById("portDiv").style.display='block';
				document.getElementById("usernameDiv").style.display='block';
				document.getElementById("passwordDiv").style.display='block';
				document.getElementById("schemanameDiv").style.display='block';
				document.getElementById("cleanupspDiv").style.display='block';
				document.getElementById("querystrDiv").style.display='block';
			}
			else if(selValue == 2){ //DiGIR
				document.getElementById("serverDiv").style.display='block';
				document.getElementById("portDiv").style.display='block';
				document.getElementById("codeDiv").style.display='block';
				document.getElementById("pathDiv").style.display='block';
				document.getElementById("pkfieldDiv").style.display='block';
				document.getElementById("schemanameDiv").style.display='block';
				document.getElementById("cleanupspDiv").style.display='block';
				document.getElementById("querystrDiv").style.display='block';
			}
			else if(selValue == 3){ //File Upload
				document.getElementById("cleanupspDiv").style.display='block';
			}
			else if(selValue == 4){ //Stored Procedure
				document.getElementById("cleanupspDiv").style.display='block';
				document.getElementById("querystrDiv").style.display='block';
			}
			else if(selValue == 5){ //Script Upload
				document.getElementById("cleanupspDiv").style.display='block';
				document.getElementById("querystrDiv").style.display='block';
			}
			else if(selValue == 6){ //Darwin Core Archive Upload
				document.getElementById("pathDiv").style.display='block';
				document.getElementById("cleanupspDiv").style.display='block';
				//document.getElementById("querystrDiv").style.display='none';
			}
		}
	</script>
</head>
<body onload="<?php if($uspid && $action) echo 'adjustParameterForm()'; ?>">
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
			<b>Specimen Loader</b> 
		</div>
		<?php 
	}
?> 
<!-- This is inner text! -->
<div id="innertext">
	<h1>Data Upload Management</h1>
	<?php
	
	if($statusStr){
		echo "<hr />";
		echo "<div>$statusStr</div>";
		echo "<hr />";
	}

	if($isEditor){
		if($collId){
			echo '<div style="font-weight:bold;font-size:130%;">'.$duManager->getCollInfo('name').'</div>';
			if($duManager->getCollInfo("uploaddate")) {
				echo "<div style='margin:0px 0px 15px 15px;'><b>Last Upload Date:</b> ".$duManager->getCollInfo("uploaddate")."</div>";
			}
			if(!$action){
		 		//Collection has been selected, now display different upload options
			 	$profileList = $duManager->getUploadList();
				?>
				<form name="uploadlistform" action="specupload.php" method="post" onsubmit="return checkUploadListForm(this);">
					<fieldset style="width:450px;">
						<legend style="font-weight:bold;font-size:120%;">Upload Options</legend>
						<div style="float:right;">
							<?php 
							echo '<a href="specuploadmanagement.php?collid='.$collId.'&action=addprofile"><img src="'.$clientRoot.'/images/add.png" style="width:15px;border:0px;" title="Add a New Upload Profile" /></a>';
							?>
						</div>
						<?php 
						if($profileList){ 
						 	foreach($profileList as $id => $v){
						 		?>
						 		<div style="margin:10px;">
									<input type="radio" name="uspid" value="<?php echo $id.'-'.$v["uploadtype"];?>" />
									<?php echo $v["title"]; ?> 
									<a href="specuploadmanagement.php?action=editprofile&collid=<?php echo $collId.'&uspid='.$id; ?>" title="View/Edit Parameters"><img src="../../images/edit.png" /></a>
									<input type="hidden" name="uploadtype" value="<?php echo $v["uploadtype"];?>" />
								</div>
								<?php 
						 	}
							?>
							<input type="hidden" name="collid" value="<?php echo $collId;?>" />
							<div style="margin:10px;">
								<input type="submit" name="action" value="Initialize Upload..." />
							</div>
							<?php
						} 
					 	else{
					 		?>
							<div style="padding:30px;">
								There are no Upload Profiles associated with this collection. <br />
								Click <a href="specuploadmanagement.php?collid=<?php echo ($collId);?>&action=addprofile">here</a> to add a new profile.
							</div>
							<?php 
					 	}
					 	 ?>
					</fieldset>
				</form>
				<hr />
				<?php 
			}
			else{
		 		?>
				<div style="clear:both;">
					<fieldset>
						<legend><b>Upload Parameters</b></legend>
						<div style="float:right;">
							<?php 
							echo '<a href="specuploadmanagement.php?collid='.$collId.'">View All</a> ';
							?>
						</div>
						<form name="parameterform" action="specuploadmanagement.php" method="post" onsubmit="return checkParameterForm(this)">
							<div id="updatetypeDiv" style="">
								<b>Upload Type:</b> 
								<select name="uploadtype" onchange="adjustParameterForm()" <?php if($uspid) echo 'DISABLED'; ?>>
									<option value="">Select an Upload Type</option>
									<option value="">------------------------</option>
									<?php 
									$uploadType = $duManager->getUploadType();
									echo '<option value="'.$DIRECTUPLOAD.'" '.($uploadType==$DIRECTUPLOAD?'SELECTED':'').'>Direct Database Upload</option>';
									echo '<option value="'.$DWCAUPLOAD.'" '.($uploadType==$DWCAUPLOAD?'SELECTED':'').'>Darwin Core Archive Provider</option>';
									echo '<option value="'.$DIGIRUPLOAD.'" '.($uploadType==$DIGIRUPLOAD?'SELECTED':'').'>DiGIR Provider</option>';
									echo '<option value="'.$FILEUPLOAD.'" '.($uploadType==$FILEUPLOAD?'SELECTED':'').'>File Upload</option>';
									echo '<option value="'.$STOREDPROCEDURE.'" '.($uploadType==$STOREDPROCEDURE?'SELECTED':'').'>Stored Procedure</option>';
									echo '<option value="'.$SCRIPTUPLOAD.'" '.($uploadType==$SCRIPTUPLOAD?'SELECTED':'').'>Script Upload</option>';
									?>
								</select>
							</div>
							<div id="titleDiv" style="">
								<b>Title:</b> 
								<input name="title" type="text" value="<?php echo $duManager->getTitle(); ?>" style="width:400px;" />
							</div>
							<div id="platformDiv" style="display:none">
								<b>Database Platform:</b>
								<select name="platform">
									<option value="">None Selected</option>
									<option value="">--------------------------------------------</option>
									<option value="mysql" <?php echo ($duManager->getPlatform()=='mysql'?'SELECTED':''); ?>>MySQL Database</option>
								</select> 
							</div>
							<div id="serverDiv" style="display:none">
								<b>Server:</b> 
								<input name="server" type="text" size="50" value="<?php echo $duManager->getServer(); ?>" style="width:400px;" />
							</div>
							<div id="portDiv" style="display:none">
								<b>Port:</b> 
								<input name="port" type="text" value="<?php echo $duManager->getPort(); ?>" />
							</div>
							<div id="pathDiv" style="display:none">
								<b>Path:</b> 
								<input name="path" type="text" size="50" value="<?php echo $duManager->getPath(); ?>" style="width:400px;" />
							</div>
							<div id="codeDiv" style="display:none">
								<b>Code:</b> 
								<input name="code" type="text" value="<?php echo $duManager->getCode(); ?>" />
							</div>
							<div id="pkfieldDiv" style="display:none">
								<b>Primary Key Field:</b> 
								<input name="pkfield" type="text" value="<?php echo $duManager->getPKField(); ?>" />
							</div>
							<div id="usernameDiv" style="display:none">
								<b>Username:</b> 
								<input name="username" type="text" value="<?php echo $duManager->getUsername(); ?>" />
							</div>
							<div id="passwordDiv" style="display:none">
								<b>Password:</b> 
								<input name="password" type="text" value="<?php echo $duManager->getPassword(); ?>" />
							</div>
							<div id="schemanameDiv" style="display:none">
								<b>Schema Name:</b> 
								<input name="schemaname" type="text" size="65" value="<?php echo $duManager->getSchemaName(); ?>" />
							</div>
							<div id="cleanupspDiv" style="display:none">
								<b>Stored Procedure (clean/transfer):</b> 
								<input name="cleanupsp" type="text" size="40" value="<?php echo $duManager->getStoredProcedure(); ?>" style="width:400px;" />
							</div>
							<div id="querystrDiv" style="display:none">
								<b>Query/Command String: </b><br/>
								<textarea name="querystr" cols="75" rows="6" ><?php echo $duManager->getQueryStr(); ?></textarea>
							</div>
							<div style="">
								<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
								<input type="hidden" name="collid" value="<?php echo $collId;?>" />
								<?php
								if($uspid){ 
									?>
									<input type="submit" name="action" value="Save Edits" />
									<?php
								}
								else{ 
									?>
									<input type="submit" name="action" value="Create Profile" />
									<?php 
								}
								?>
							</div>
						</form>
					</fieldset>
				</div>
				<?php 
				if($uspid){
					?>
					<form action="specuploadmanagement.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this profile?')">
						<fieldset>
							<legend><b>Delete this Profile</b></legend>
							<div>
								<input type="hidden" name="uspid" value="<?php echo $uspid; ?>" />
								<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
								<input type="submit" name="action" value="Delete Profile" />
							</div>
						</fieldset>
					</form>
					<?php
				}
		 	}
		}
		else{
			echo '<div style="font-weight:bold;font-size:120%;">ERROR: collection identifier not set</div>';
		}
	}
	else{
		?>
		<div style="font-weight:bold;font-size:120%;">
			ERROR: you are not authorized to upload to this collection
		</div>
		<?php 
	}
	?>
</div>
<?php 
include($serverRoot.'/footer.php');
?>

</body>
</html>

