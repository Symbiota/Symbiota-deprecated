<?php 
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/InstitutionManager.php');

$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$iid = array_key_exists("iid",$_REQUEST)?$_REQUEST["iid"]:0;
$eMode = array_key_exists("emode",$_REQUEST)?$_REQUEST["emode"]:0;
$formSubmit = array_key_exists("formsubmit",$_REQUEST)?$_REQUEST["formsubmit"]:"";

$instManager = new InstitutionManager();

$isEditable = 0;
$statusStr = '';
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
	$isEditable = 1;
}
if($isEditable){
 	if($formSubmit == "Update Institution"){
 		$statusStr = $instManager->submitInstitutionEdits($_POST);
 	}
	elseif($formSubmit == "Add Institution"){
		$iid = $instManager->submitInstitutionAdd($_POST);
		if(!is_numeric($iid)){
			$statusStr = $iid;
			$iid = 0;
		}
	}
	elseif($formSubmit == "Delete Institution"){
		$delIid = $_POST['deliid'];
		$statusStr = $instManager->deleteInstitution($delIid);
		if($statusStr == 1){
			$statusStr = 'SUCCESS! Institution deleted.';
		}
		else{
			$iid = $delIid;
		}
	}
}

if($iid){
	$instManager->setInstitutionId($iid);
}
elseif($collId){
	$instManager->setCollectionId($collId);
	if(!$iid) $iid = $instManager->getInstitutionId();
}
?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Institution Editor</title>
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

	</script>
</head>
<body>
<?php
$displayLeftMenu = (isset($collections_admin_institutioneditor)?$collections_admin_institutioneditor:true);
include($serverRoot.'/header.php');
?>
<div class='navpath'>
	<a href='../index.php'>Home</a> &gt;&gt; 
	<?php 
	if(isset($collections_admin_institutioneditor)){
		echo $collections_admin_institutioneditor; 
	}
	elseif($collId){
		echo '<a href="../misc/collprofiles.php?collid='.$collId.'&emode=1">Collection Management</a> &gt;&gt;';
	}
	?>
	<b>Institution Editor</b> 
</div>
<!-- This is inner text! -->
<div id="innertext">
	<?php
	
	if($statusStr){
		?>
		<hr />
		<div style="margin:20px;color:red;">
			<?php echo $statusStr; ?>
		</div>
		<hr />
		<?php 
	}
	
	if($iid){
		$instArr = array_shift($instManager->getInstitutionData());
		if($instArr){
			?>
			<div style="float:right;">
				<a href="institutioneditor.php">
					<img src="<?php echo $clientRoot;?>/images/toparent.jpg" style="width:15px;border:0px;" title="Return to Institution List" />
				</a>
				<a href="#" onclick="toggle('editdiv');">
					<img src="<?php echo $clientRoot;?>/images/edit.png" style="width:15px;border:0px;" title="Edit Institution" />
				</a>
			</div>
			<div style="clear:both;">
				<form name="insteditform" action="institutioneditor.php" method="post">
					<fieldset style="padding:20px;">
						<legend>
							<b><?php echo $instArr['institutionname'].' ('.$instArr['institutioncode'].')'; ?></b>
						</legend>
						<div style="position:relative;">
							<div style="float:left;width:110px;font-weight:bold;">
								Institution Code:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['institutioncode']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="institutioncode" type="text" value="<?php echo $instArr['institutioncode']; ?>" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Institution Name:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['institutionname']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="institutionname" type="text" value="<?php echo $instArr['institutionname']; ?>" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Institution Name2:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['institutionname2']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="institutionname2" type="text" value="<?php echo $instArr['institutionname2']; ?>" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Address:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['address1']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="address1" type="text" value="<?php echo $instArr['address1']; ?>" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Address 2:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['address2']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="address2" type="text" value="<?php echo $instArr['address2']; ?>" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								City:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['city']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="city" type="text" value="<?php echo $instArr['city']; ?>" style="width:100px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								State/Province:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['stateprovince']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="stateprovince" type="text" value="<?php echo $instArr['stateprovince']; ?>" style="width:100px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Postal Code:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['postalcode']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="postalcode" type="text" value="<?php echo $instArr['postalcode']; ?>" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Country:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['country']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="country" type="text" value="<?php echo $instArr['country']; ?>" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Phone:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['phone']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="phone" type="text" value="<?php echo $instArr['phone']; ?>" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Contact:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['contact']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="contact" type="text" value="<?php echo $instArr['contact']; ?>" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Email:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['email']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="email" type="text" value="<?php echo $instArr['email']; ?>" style="width:150px" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								URL:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<a href="<?php echo $instArr['url']; ?>">
									<?php echo $instArr['url']; ?>
								</a>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="url" type="text" value="<?php echo $instArr['url']; ?>" style="width:400px" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Notes:
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'none':'block'; ?>;">
								<?php echo $instArr['notes']; ?>
							</div>
							<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
								<input name="notes" type="text" value="<?php echo $instArr['notes']; ?>" style="width:400px" />
							</div>
						</div>
						<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;clear:both;margin:30px 0px 0px 20px;">
							<input name="formsubmit" type="submit" value="Update Institution" />
							<input name="iid" type="hidden" value="<?php echo $iid; ?>" />
							<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						</div>
					</fieldset>
				</form>
				<div class="editdiv" style="display:<?php echo $eMode?'block':'none'; ?>;clear:both;">
					<form name="instdelform" action="institutioneditor.php" method="post" onsubmit="return confirm('Are you sure you want to delete this institution?')">
						<fieldset style="padding:20px;">
							<legend><b>Delete Institution</b></legend>
							<div style="position:relative;clear:both;">
								<input name="formsubmit" type="submit" value="Delete Institution" />
								<input name="deliid" type="hidden" value="<?php echo $iid; ?>" />
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
							</div>
						</fieldset>
					</form>
				</div>
			</div>		
			<?php
		}
	}
	else{
		if($symbUid){
			?>
			<div style="float:right;">
				<a href="#" onclick="toggle('instadddiv');">
					<img src="<?php echo $clientRoot;?>/images/add.png" style="width:15px;border:0px;" title="Add a New Institution" />
				</a>
			</div>
			<div id="instadddiv" style="display:<?php echo $eMode?'block':'none'; ?>;">
				<form name="instaddform" action="institutioneditor.php" method="post">
					<fieldset style="padding:20px;">
						<legend><b>Add New Institution</b></legend>
						<div style="position:relative;">
							<div style="float:left;width:110px;font-weight:bold;">
								Institution Code:
							</div>
							<div>
								<input name="institutioncode" type="text" value="" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Institution Name:
							</div>
							<div>
								<input name="institutionname" type="text" value="" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Institution Name2:
							</div>
							<div>
								<input name="institutionname2" type="text" value="" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Address:
							</div>
							<div>
								<input name="address1" type="text" value="" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Address 2:
							</div>
							<div>
								<input name="address2" type="text" value="" style="width:400px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								City:
							</div>
							<div>
								<input name="city" type="text" value="" style="width:100px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								State/Province:
							</div>
							<div>
								<input name="stateprovince" type="text" value="" style="width:100px;" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Postal Code:
							</div>
							<div>
								<input name="postalcode" type="text" value="" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Country:
							</div>
							<div>
								<input name="country" type="text" value="" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Phone:
							</div>
							<div>
								<input name="phone" type="text" value="" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Contact:
							</div>
							<div>
								<input name="contact" type="text" value="" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Email:
							</div>
							<div>
								<input name="email" type="text" value="" style="width:150px" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								URL:
							</div>
							<div>
								<input name="url" type="text" value="" style="width:400px" />
							</div>
						</div>
						<div style="position:relative;clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">
								Notes:
							</div>
							<div>
								<input name="notes" type="text" value="" style="width:400px" />
							</div>
						</div>
						<div style="margin:20px;clear:both;">
							<input name="formsubmit" type="submit" value="Add Institution" />
						</div>
						
					</fieldset>
				</form>
			</div>
			<h2>Select an Institution from the list</h2>
		 	<ul>
			 	<?php 
			 	$instList = $instManager->getInstitutionData();
			 	if($instList){
				 	foreach($instList as $iid => $iArr){
				 		?>
				 		<li>
				 			<a href="institutioneditor.php?iid=<?php echo $iid; ?>">
				 				<?php echo $iArr['institutionname'].' ('.$iArr['institutioncode'].')'; ?>
				 			</a>
				 		</li>
				 		<?php
				 	}
			 	}
			 	else{
		 			echo "<div>There are no institution in the database</div>";
			 	}
			 	?>
		 	</ul>
		 	<?php 
		}
		else{
			?>
			<div style="font-weight:bold;">
				Please <a href="../../profile/index.php?refurl=<?php echo $clientRoot; ?>/collections/admin/institutioneditor.php">login</a>!
			</div>
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