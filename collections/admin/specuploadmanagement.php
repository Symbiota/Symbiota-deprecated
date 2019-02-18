<?php 
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecUpload.php');
include_once($SERVER_ROOT.'/content/lang/collections/admin/specuploadmanagement.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/admin/specuploadmanagement.php?'.$_SERVER['QUERY_STRING']);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:'';
$collid = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$uspid = array_key_exists("uspid",$_REQUEST)?$_REQUEST["uspid"]:0;

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($uspid)) $uspid = 0;
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';

$DIRECTUPLOAD = 1;$DIGIRUPLOAD = 2; $FILEUPLOAD = 3; $STOREDPROCEDURE = 4; $SCRIPTUPLOAD = 5; $DWCAUPLOAD = 6; $SKELETAL = 7; $IPTUPLOAD = 8; $NFNUPLOAD = 9;

$duManager = new SpecUpload();

$duManager->setCollId($collid);
$duManager->setUspid($uspid);

$statusStr = '';
$isEditor = 0;
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
	$isEditor = 1;
}
if($isEditor){
	if($action == "Save Edits"){
		if($duManager->editUploadProfile($_POST)){
			$statusStr = $LANG['SUCCESS_EDITS_TO_IMPORT_PROFILE'];
		}
		else{
			$statusStr = $duManager->getErrorStr();
		}
		$action = '';
	}
	elseif($action == "Create Profile"){
		if($duManager->createUploadProfile($_POST)){
			$statusStr = $LANG['SUCCESS_NEW_UPLOAD_PROFILE_ADDED'];
		}
		else{
			$statusStr = $duManager->getErrorStr();
		}
		$action = '';
	}
	elseif($action == "Delete Profile"){
		if($duManager->deleteUploadProfile($uspid)){
			$statusStr = $LANG['SUCCESS_UPLOAD_PROFILE_DELETED'];
		}
		else{
			$statusStr = $duManager->getErrorStr();
		}
		$action = '';
	}
}
$duManager->readUploadParameters();
?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE." ".$LANG['SPECIMEN_UPLOAD_PROFILE_MANAGER']; ?></title>
	<link href="../../css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
	<link href="../../css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<script>
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
			alert("<?php echo $LANG['PLEASE_SELECT_AN_UPLOAD_OPTION']; ?>");
			return false;
		}

		function checkParameterForm(f){
			if(f.title.value == ""){
				alert("<?php echo $LANG['PROFILE_TITLE_IS_REQUIRED']; ?>");
				return false;
			}
			else if(f.uploadtype.value == ""){
				alert("<?php echo $LANG['SELECT_UPLOAD_TYPE']; ?>");
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
			else if(selValue == 6){ //Darwin Core Archive Manual Upload
				//document.getElementById("pathDiv").style.display='block';
				document.getElementById("cleanupspDiv").style.display='block';
			}
			else if(selValue == 7){ //Skeletal File Upload
				document.getElementById("cleanupspDiv").style.display='block';
			}
			else if(selValue == 8){ //IPT resource
				document.getElementById("pathDiv").style.display='block';
				document.getElementById("cleanupspDiv").style.display='block';
			}
		}
	</script>
</head>
<body onload="<?php if($uspid && $action) echo 'adjustParameterForm()'; ?>">
<?php
	$displayLeftMenu = (isset($collections_admin_specuploadMenu)?$collections_admin_specuploadMenu:false);
	include($SERVER_ROOT.'/header.php');
	if(isset($collections_admin_specuploadCrumbs)){
		if($collections_admin_specuploadCrumbs){
			?>
			<div class="navpath">
				<a href="../../index.php"><?php echo $LANG['HOME']; ?></a> &gt;&gt;
				<?php echo $collections_admin_specuploadCrumbs; ?>
				<b><?php echo $LANG['SPECIMEN']; ?></b> 
			</div>
			<?php 
		}
	}
	else{
		?>
		<div class="navpath">
			<a href="../../index.php"><?php echo $LANG['HOME'];?></a> &gt;&gt; 
			<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1"><?php echo $LANG['COLLECTION_MANAGEMENT_PANEL']; ?></a> &gt;&gt; 
			<b><?php echo $LANG['SPECIMEN'];?></b> 
		</div>
		<?php 
	}
?> 
<!-- This is inner text! -->
<div id="innertext">
	<h5><?php echo $LANG['DATA'];?></h5>
	<?php
	
	if($statusStr){
		echo "<hr />";
		echo "<div>$statusStr</div>";
		echo "<hr />";
	}

	if($isEditor){
		if($collid){
			echo '<div style="font-weight:bold;font-size:130%;">'.$duManager->getCollInfo('name').'</div>';
			if($duManager->getCollInfo("uploaddate")) {
				echo "<div style='margin:0px 0px 15px 15px;'><b>".$LANG['LAST_UPLOAD_DATE'].":</b> ".$duManager->getCollInfo("uploaddate")."</div>";
			}
			if(!$action){
		 		//Collection has been selected, now display different upload options
			 	$profileList = $duManager->getUploadList();
				?>
				<form name="uploadlistform" action="specupload.php" method="post" onsubmit="return checkUploadListForm(this);">
					<fieldset>
						<legend style="font-weight:bold;font-size:120%;"><?php echo $LANG['UP'];?></legend>
						<div style="float:right;">
							<?php 
							echo '<a href="specuploadmanagement.php?collid='.$collid.'&action=addprofile"><img src="'.$clientRoot.'/images/add.png" style="width:15px;border:0px;" title="'.$LANG['ADD_A_NEW_UPLOAD'].'" /></a>';
							?>
						</div>
						<?php 
						if($profileList){ 
						 	foreach($profileList as $id => $v){
						 		?>
						 		<div style="margin:10px;">
									<input type="radio" name="uspid" value="<?php echo $id.'-'.$v["uploadtype"];?>" />
									<?php echo $v["title"]; ?> 
									<a href="specuploadmanagement.php?action=editprofile&collid=<?php echo $collid.'&uspid='.$id; ?>" title="<?php echo $LANG['VIEW_EDIT_PARAMETERS']; ?>"><img src="../../images/edit.png" /></a>
									<input type="hidden" name="uploadtype" value="<?php echo $v["uploadtype"];?>" />
								</div>
								<?php 
						 	}
							?>
							<input type="hidden" name="collid" value="<?php echo $collid;?>" />
							<div style="margin:10px;">
								<input type="hidden" name="action" value="Initialize Upload..." />
								<input type="submit" value="<?php echo $LANG['INITIALIZE_UPLOAD']; ?>" />
							</div>
							<?php
						} 
					 	else{
					 		?>
							<div style="padding:30px;">
								<?php echo $LANG['THERE'];?> <br />
								<?php echo $LANG['CLICK'];?> <a href="specuploadmanagement.php?collid=<?php echo ($collid);?>&action=addprofile"><?php echo $LANG['HERE'];?></a> <?php echo $LANG['TO_ADD'];?>
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
						<legend><b><?php echo $LANG['UPLOAD'];?></b></legend>
						<div style="float:right;">
							<?php 
							echo '<a href="specuploadmanagement.php?collid='.$collid.'">'.$LANG['VIEW_ALL'].'</a> ';
							?>
						</div>
						<form name="parameterform" action="specuploadmanagement.php" method="post" onsubmit="return checkParameterForm(this)">
							<div id="updatetypeDiv" style="">
								<b><?php echo $LANG['UP_TYPE'];?></b> 
								<select name="uploadtype" onchange="adjustParameterForm()" <?php if($uspid) echo 'DISABLED'; ?>>
									<option value=""><?php echo $LANG['SEL'];?></option>
									<option value="">----------------------------------</option>
									<?php 
									$uploadType = $duManager->getUploadType();
									echo '<option value="'.$DWCAUPLOAD.'" '.($uploadType==$DWCAUPLOAD?'SELECTED':'').'>'.$LANG['DARWIN_CORE_ARCHIV_MANUAL_UPLOAD'].'</option>';
									echo '<option value="'.$IPTUPLOAD.'" '.($uploadType==$IPTUPLOAD?'SELECTED':'').'>'.$LANG['IPT_RESOURCE_DARWIN_CORE_ARCHIVE'].'</option>';
									echo '<option value="'.$FILEUPLOAD.'" '.($uploadType==$FILEUPLOAD?'SELECTED':'').'>'.$LANG['FILE_UPLOAD'].'</option>';
									echo '<option value="'.$SKELETAL.'" '.($uploadType==$SKELETAL?'SELECTED':'').'>'.$LANG['SKELETAL_FILE_UPLOAD'].'</option>';
									echo '<option value="'.$NFNUPLOAD.'" '.($uploadType==$NFNUPLOAD?'SELECTED':'').'>'.$LANG['NFN_FILE_UPLOAD'].'</option>';
									echo '<option value="">......................................</option>';
									echo '<option value="'.$DIGIRUPLOAD.'" '.($uploadType==$DIGIRUPLOAD?'SELECTED':'').'>'.$LANG['DIGIR_PROVIDER'].'</option>';
									echo '<option value="'.$DIRECTUPLOAD.'" '.($uploadType==$DIRECTUPLOAD?'SELECTED':'').'>'.$LANG['DIRECT_DATABASE_MAPPING'].'</option>';
									echo '<option value="'.$STOREDPROCEDURE.'" '.($uploadType==$STOREDPROCEDURE?'SELECTED':'').'>'.$LANG['STORED_PROCEDURE'].'</option>';
									echo '<option value="'.$SCRIPTUPLOAD.'" '.($uploadType==$SCRIPTUPLOAD?'SELECTED':'').'>'.$LANG['SCRIPT_UPLOAD'].'</option>';
									?>
								</select>
							</div>
							<div id="titleDiv" style="">
								<b><?php echo $LANG['TITLE'];?></b> 
								<input name="title" type="text" value="<?php echo $duManager->getTitle(); ?>" style="width:400px;" maxlength="45" />
							</div>
							<div id="platformDiv" style="display:none">
								<b><?php echo $LANG['DATABASE'];?></b>
								<select name="platform">
									<option value=""><?php echo $LANG['NONE'];?></option>
									<option value="">--------------------------------------------</option>
									<option value="mysql" <?php echo ($duManager->getPlatform()=='mysql'?'SELECTED':''); ?>><?php echo $LANG['SQL'];?></option>
								</select> 
							</div>
							<div id="serverDiv" style="display:none">
								<b><?php echo $LANG['SERVER'];?></b> 
								<input name="server" type="text" size="50" value="<?php echo $duManager->getServer(); ?>" style="width:400px;" />
							</div>
							<div id="portDiv" style="display:none">
								<b><?php echo $LANG['PORT'];?></b> 
								<input name="port" type="text" value="<?php echo $duManager->getPort(); ?>" />
							</div>
							<div id="pathDiv" style="display:none">
								<b><?php echo $LANG['PATH'];?></b> 
								<input name="path" type="text" size="50" value="<?php echo $duManager->getPath(); ?>" style="width:400px;" />
							</div>
							<div id="codeDiv" style="display:none">
								<b><?php echo $LANG['CODE'];?></b> 
								<input name="code" type="text" value="<?php echo $duManager->getCode(); ?>" />
							</div>
							<div id="pkfieldDiv" style="display:none">
								<b><?php echo $LANG['PRIMARY'];?></b> 
								<input name="pkfield" type="text" value="<?php echo $duManager->getPKField(); ?>" />
							</div>
							<div id="usernameDiv" style="display:none">
								<b><?php echo $LANG['USER'];?></b> 
								<input name="username" type="text" value="<?php echo $duManager->getUsername(); ?>" />
							</div>
							<div id="passwordDiv" style="display:none">
								<b><?php echo $LANG['PASS'];?></b> 
								<input name="password" type="text" value="<?php echo $duManager->getPassword(); ?>" />
							</div>
							<div id="schemanameDiv" style="display:none">
								<b><?php echo $LANG['SCHEMA'];?></b> 
								<input name="schemaname" type="text" size="65" value="<?php echo $duManager->getSchemaName(); ?>" />
							</div>
							<div id="cleanupspDiv" style="display:none">
								<b><?php echo $LANG['STORED'];?></b> 
								<input name="cleanupsp" type="text" size="40" value="<?php echo $duManager->getStoredProcedure(); ?>" style="width:400px;" />
							</div>
							<div id="querystrDiv" style="display:none">
								<b><?php echo $LANG['QUERY'];?> </b><br/>
								<textarea name="querystr" cols="75" rows="6" ><?php echo $duManager->getQueryStr(); ?></textarea>
							</div>
							<div style="margin:15px">
								<input type="hidden" name="uspid" value="<?php echo $uspid;?>" />
								<input type="hidden" name="collid" value="<?php echo $collid;?>" />
								<?php
								if($uspid){ 
									?>
									<input type="hidden" name="action" value="Save Edits" />
									<input type="submit" value="<?php echo $LANG['SAVE_EDITS']; ?>" />
									<?php
								}
								else{ 
									?>
									<input type="hidden" name="action" value="Create Profile" />
									<input type="submit" value="<?php echo $LANG['CREATE_PROFILE']; ?>" />
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
					<form action="specuploadmanagement.php" method="post" onsubmit="return confirm('<?php echo $LANG['ARE_YOU_SURE_YOU_WANT']; ?>')">
						<fieldset>
							<legend><b><?php echo $LANG['DELET'];?></b></legend>
							<div>
								<input type="hidden" name="uspid" value="<?php echo $uspid; ?>" />
								<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
								<input type="hidden" name="action" value="Delete Profile" />
								<input type="submit" value="<?php echo $LANG['DELETE_PROFILE']; ?>" />
							</div>
						</fieldset>
					</form>
					<?php
				}
		 	}
		}
		else{
			echo '<div style="font-weight:bold;font-size:120%;">'.$LANG['ERROR_COLLECTION_IDENTIFIER_NOT_SET'].'</div>';
		}
	}
	else{
		?>
		<div style="font-weight:bold;font-size:120%;">
			<?php echo $LANG['ERROR'];?>
		</div>
		<?php 
	}
	?>
</div>
<?php 
include($SERVER_ROOT.'/footer.php');
?>
</body>
</html>