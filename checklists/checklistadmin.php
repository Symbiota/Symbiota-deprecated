<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ChecklistAdmin.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0; 
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$startPos = (array_key_exists('start',$_REQUEST)?(int)$_REQUEST['start']:0);
$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:0; 
$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:""; 

$clManager = new ChecklistAdmin();
if(!$clid && isset($_POST['delclid'])) $clid = $_POST['delclid'];
$clManager->setClid($clid);

$statusStr = "";
$isEditor = 0;
if($isAdmin || (array_key_exists("ClAdmin",$userRights) && in_array($clid,$userRights["ClAdmin"]))){
	$isEditor = 1;

	//Submit checklist MetaData edits
	if($action == "Submit Changes"){
		$editArr = Array();
		foreach($_REQUEST as $k => $v){
			if(substr($k,0,3) == "ecl"){
				$editArr[substr($k,3)] = $_REQUEST[$k];
			}
		}
		$clManager->editMetaData($editArr);
		header('Location: checklist.php?cl='.$clid.'&pid='.$pid);
	}
	elseif($action == 'Delete Checklist'){
		$statusStr = $clManager->deleteChecklist($_POST['delclid']);
		if($statusStr === true) header('Location: ../index.php');
	}
	elseif($action == 'Add Editor'){
		$statusStr = $clManager->addEditor($_POST['editoruid']);
	}
	elseif(array_key_exists('deleteuid',$_REQUEST)){
		$statusStr = $clManager->deleteEditor($_REQUEST['deleteuid']);
	}
	elseif($action == 'Add Point'){
		$statusStr = $clManager->addPoint($_POST['pointtid'],$_POST['pointlat'],$_POST['pointlng'],$_POST['notes']);
	}
}
$clArray = $clManager->getMetaData();

$voucherProjects = $clManager->getVoucherProjects(); 
?>

<!DOCTYPE html >
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
	<title><?php echo $defaultTitle; ?> Checklist Administration</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css"/>
	<link type="text/css" href="../css/jquery-ui.css" rel="Stylesheet" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript">
		var clid = <?php echo $clid; ?>;
		var tabIndex = <?php echo $tabIndex; ?>;
	</script>
	<script type="text/javascript" src="../js/symb/checklists.checklistadmin.js"></script>
</head>

<body>
	<?php
	$displayLeftMenu = false;
	include($serverRoot.'/header.php');
	?>
	<div class="navpath">
		<a href="../index.php">Home</a> &gt;&gt; 
		<a href="checklist.php?cl=<?php echo $clid.'&pid='.$pid; ?>">Return to Checklist</a> &gt;&gt; 
		<b>Checklist Administrator</b>
	</div>

	<!-- This is inner text! -->
	<div id='innertext'>
		<div style="color:#990000;font-size:20px;font-weight:bold;margin:0px 10px 10px 0px;">
			<a href="checklist.php?cl=<?php echo $clid.'&pid='.$pid; ?>">
				<?php echo $clManager->getClName(); ?>
			</a>
		</div>
		<?php 
		if($statusStr){ 
			?>
			<hr />
			<div style="margin:20px;font-weight:bold;color:red;">
				<?php echo $statusStr; ?>
			</div>
			<hr />
			<?php 
		} 
		
		if($clid && $isEditor){
			?>
			<div id="tabs" style="margin:10px;">
			    <ul>
			        <li><a href="#admintab"><span>Admin</span></a></li>
			        <li><a href="#desctab"><span>Description</span></a></li>
<!-- 			        <li><a href="#pointtab"><span>Non-vouchered Points</span></a></li> -->
					<?php
					if($voucherProjects){
						?>
				        <li><a href="#imgvouchertab">Add Image Voucher</a></li>
				        <?php
					}
				    ?>
			    </ul>
				<div id="admintab">
					<div style="margin:20px;">
						<div style="font-weight:bold;font-size:120%;">Current Editors</div>
						<?php
						$editorArr = $clManager->getEditors();
						if($editorArr){
							?>
							<ul>
							<?php 
							foreach($editorArr as $uid => $uName){
								?>
								<li>
									<?php echo $uName; ?> 
									<a href="checklistadmin.php?clid=<?php echo $clid.'&deleteuid='.$uid.'&pid='.$pid.'&tabindex='.$tabIndex; ?>" onclick="return confirm('Are you sure you want to remove editing rights for this user?');" title="Delete this user">
										<img src="../images/drop.png" style="width:12px;" />
									</a>
								</li>
								<?php 
							}
							?>
							</ul>
							<?php 
						}
						else{
							echo "<div>No one has been explicitly assigned as an editor</div>\n";
						}
						?>
						<fieldset style="margin:40px 5px;padding:15px;">
							<legend><b>Add New User</b></legend>
							<form name="adduser" action="checklistadmin.php" method="post" onsubmit="return verifyAddUser(this)">
								<div>
									<select name="editoruid">
										<option value="">Select User</option>
										<option value="">--------------------</option>
										<?php 
										$userArr = $clManager->getUserList();
										foreach($userArr as $uid => $uName){
											echo '<option value="'.$uid.'">'.$uName.'</option>';
										}
										?>
									</select> 
									<input name="submitaction" type="submit" value="Add Editor" />
									<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
									<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
								</div> 
							</form>
						</fieldset>
					</div>
					<hr/>
					<div style="margin:20px;">
						<div style="font-weight:bold;font-size:120%;">Inventory Project Assignments</div>
						<ul>
							<?php 
							$projArr = $clManager->getInventoryProjects();
							if($projArr){
								foreach($projArr as $pid => $pName){
									echo '<li>';
									echo '<a href="../projects/index.php?proj='.$pid.'">'.$pName.'</a>';
									echo '</li>';
								}
							}
							else{
								echo '<li>Checklist has not been assigned to any inventory projects</li>';
							}
							?>
						</ul>
					</div>
					<hr/>
					<div style="margin:20px;">
						<div style="font-weight:bold;font-size:120%;">Permanently Remove Checklist</div>
						<div style="margin:10px;">
							Before a checklist can be deleted, all editors (except yourself) and inventory project assignments must be removed. 
							Inventory project assignments can only be removed by active managers of the project or a system administrator. <br/> 
							<b>WARNING: Action cannot be undone.</b>
						</div>
						<div style="margin:15px;">  
							<form action="checklistadmin.php" method="post" name="deleteclform" onsubmit="return window.confirm('Are you sure you want to permanently remove checklist? This action cannot be undone!')">
								<input name="delclid" type="hidden" value="<?php echo $clid; ?>" />
								<input name="submitaction" type="submit" value="Delete Checklist" <?php if($projArr || count($editorArr) > 1) echo 'DISABLED'; ?> /> 
							</form>
						</div>
					</div>
				</div>
				<div id="desctab">
					<form id="checklisteditform" action="checklistadmin.php" method="post" name="editclmatadata" onsubmit="return validateMetadataForm(this)">
						<fieldset style="margin:15px;padding:10px;">
							<legend><b>Edit Checklist Details</b></legend>
							<div>
								Checklist Name<br/>
								<input type="text" name="eclname" style="width:95%" value="<?php echo $clManager->getClName();?>" />
							</div>
							<div>
								Authors<br/>
								<input type="text" name="eclauthors" style="width:95%" value="<?php echo $clArray["authors"]; ?>" />
							</div>
							<div>
								Locality<br/>
								<input type="text" name="ecllocality" style="width:95%" value="<?php echo $clArray["locality"]; ?>" />
							</div> 
							<div>
								Publication<br/>
								<input type="text" name="eclpublication" style="width:95%" value="<?php echo str_replace('"',"&quot;",$clArray["publication"]); ?>" />
							</div>
							<div>
								Abstract<br/>
								<textarea name="eclabstract" style="width:95%" rows="3"><?php echo $clArray["abstract"]; ?></textarea>
							</div>
							<div>
								Parent Checklist<br/>
								<select name="eclparentclid">
									<option value="">Select a Parent checklist</option>
									<option value="">----------------------------------</option>
									<?php 
									$parArr = $clManager->getParentArr();
									foreach($parArr as $k => $v){
										echo '<option value="'.$k.'" '.($clArray['parentclid']==$k?' selected':'').'>'.$v.'</option>';
									}
									?>
								</select>
							</div>
							<div>
								Notes:<br/>
								<input type="text" name="eclnotes" style="width:95%" value="<?php echo $clArray["notes"]; ?>" />
							</div>
							<div style="float:left;">
								Latitude Centroid<br/>
								<input id="latdec" type="text" name="ecllatcentroid" style="width:110px;" value="<?php echo $clArray["latcentroid"]; ?>" />
							</div>
							<div style="float:left;margin-left:5px;">
								Longitude Centroid<br/>
								<input id="lngdec" type="text" name="ecllongcentroid" style="width:110px;" value="<?php echo $clArray["longcentroid"]; ?>" />
							</div>
							<div style="float:left;margin-left:5px;">
								Point Radius (meters)<br/>
								<input type="text" name="eclpointradiusmeters" style="width:110px;" value="<?php echo $clArray["pointradiusmeters"]; ?>" />
							</div>
							<div style="float:left;margin:25px 0px 0px 10px;cursor:pointer;" onclick="openMappingAid();">
								<img src="../images/world40.gif" style="width:12px;" />
							</div>
							<div style="clear:both;">
								Access:<br/>
								<select name="eclaccess">
									<option value="private">Private</option>
									<option value="public" <?php echo ($clArray["access"]=="public"?"selected":""); ?>>Public</option>
								</select>
							</div>
							<div>
								<input type='submit' name='submitaction' id='editsubmit' value='Submit Changes' />
							</div>
							<input type="hidden" name="tabindex" value="1" />
							<input type='hidden' name='clid' value='<?php echo $clid; ?>' />
							<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
						</fieldset>
					</form>
				</div>
<!-- 
				<div id="pointtab">
					<fieldset>
						<legend><b>Add New Point</b></legend>
						<form name="pointaddform" target="checklistadmin.php" method="post" onsubmit="return verifyPointAddForm(this)">
							Taxon<br/>
							<select name="pointtid" onchange="togglePoint(this.form);">
								<option value="">Select Taxon</option>
								<option value="">-----------------------</option>
								<?php 
								$taxaArr = $clManager->getTaxa();
								foreach($taxaArr as $tid => $sn){
									echo '<option value="'.$tid.'">'.$sn.'</option>';
								}
								?>
							</select>
							<div id="pointlldiv" style="display:none;"> 
								<div style="float:left;">
									Latitude Centroid<br/>
									<input id="latdec" type="text" name="pointlat" style="width:110px;" value="" />
								</div>
								<div style="float:left;margin-left:5px;">
									Longitude Centroid<br/>
									<input id="lngdec" type="text" name="pointlng" style="width:110px;" value="" />
								</div>
								<div style="float:left;margin:15px 0px 0px 10px;cursor:pointer;" onclick="openPointAid(<?php echo $clArray["latcentroid"].','.$clArray["longcentroid"]?>);">
									<img src="../images/world40.gif" style="width:12px;" />
								</div>
								<div style="clear:both;">
									Notes:<br/>
									<input type="text" name="notes" style="width:95%" value="" />
								</div>
								<div>
									<input name="submitaction" type="submit" value="Add Point" />
									<input type="hidden" name="tabindex" value="2" />
									<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
									<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
								</div>
							</div>
						</form>
					</fieldset>
				</div>
 -->
 				<?php
				if($voucherProjects){
					?>
					<div id="imgvouchertab">
						<form name="addimagevoucher" action="../collections/editor/observationsubmit.php" method="get" target="_blank">
							<fieldset style="margin:15px;padding:25px;">
								<legend><b>Add Image Voucher and Link to Checklist</b></legend>
								This form will allow you to add an image voucher linked to this checklist.<br/>
								If not already present, Scientific name will be added to checklist.<br/>
								Select the voucher project to which you wish to add the voucher. 
								<div style="margin:5px;">
									<select name="collid">
										<?php 
										foreach($voucherProjects as $k => $v){
											echo '<option value="'.$k.'">'.$v.'</option>';
										}
										?>
									</select><br/>
									<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
								</div>
								<div style="margin:5px;">
									<input type="submit" name="submitvoucher" value="Add Image Voucher" /><br/>
								</div> 
							</fieldset>
						</form>
					</div>
					<?php
				} 
				?>
			</div>
			<?php
		}
		else{
			if(!$clid){
				echo '<div><span style="font-weight:bold;font-size:110%;">Error:</span> Checklist identifier not set</div>';
			}
			elseif(!$symbUid){
				?>
				<div style="margin:30px;font-weight:bold;font-size:120%;">
					Please <a href="../profile/index.php?refurl=<?php echo $clientRoot.'/checklists/checklistadmin.php?clid='.$clid.'&pid='.$pid; ?>">login</a>
				</div>
				<?php 
			}
			else{
				echo '<div><span style="font-weight:bold;font-size:110%;">Error:</span> You do not have administrative permission for this checklist</div>';
			}
		}
		?>
	</div>
	<?php
 	include($serverRoot.'/footer.php');
	?>

</body>
</html> 