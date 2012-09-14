<?php
	include_once('../config/symbini.php');
	include_once($serverRoot.'/classes/ChecklistManager.php');
	header("Content-Type: text/html; charset=".$charset);
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:""; 
	$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:0; 
	$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0; 
	$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
	$sqlFrag = array_key_exists("sqlfrag",$_REQUEST)?$_REQUEST["sqlfrag"]:"";
	$startPos = (array_key_exists('start',$_REQUEST)?(int)$_REQUEST['start']:0);
	
	$clManager = new ChecklistManager();
	$clManager->setClValue($clid);
	if($pid) $clManager->setProj($pid);

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
	 	}
		elseif($action == "Create SQL Fragment"){
			$statusStr = $clManager->saveSql($_POST);
	 	}
		elseif($action == 'Delete SQL Fragment'){
			$statusStr = $clManager->deleteSql();
		}
	}
	$clArray = $clManager->getClMetaData();

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
		var taxonArr = new Array(<?php $clManager->echoFilterList();?>);
		var clid = <?php echo $clid; ?>;
		var tabIndex = <?php echo $tabIndex; ?>;
	</script>
	<script type="text/javascript" src="../js/symb/checklists.checklist.js"></script>
	<style type="text/css">
		#sddm{margin:0;padding:0;z-index:30;}
		#sddm:hover {background-color:#EAEBD8;}
		#sddm img{padding:3px;}
		#sddm:hover img{background-color:#EAEBD8;}
		#sddm li{margin:0px;padding: 0;list-style: none;float: left;font: bold 11px arial}
		#sddm li a{display: block;margin: 0 1px 0 0;padding: 4px 10px;width: 60px;background: #5970B2;color: #FFF;text-align: center;text-decoration: none}
		#sddm li a:hover{background: #49A3FF}
		#sddm div{position: absolute;visibility:hidden;margin:0;padding:0;background:#EAEBD8;border:1px solid #5970B2}
		#sddm div a	{position: relative;display:block;margin:0;padding:5px 10px;width:auto;white-space:nowrap;text-align:left;text-decoration:none;background:#EAEBD8;color:#2875DE;font-weight:bold;}
		#sddm div a:hover{background:#49A3FF;color:#FFF}
	</style>
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
			<?php echo $clManager->getClName(); ?>
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
			        <li><a href="#mdtab"><span>Metadata</span></a></li>
					<?php
					if($voucherProjects){
						?>
				        <li><a href="#imgvouchertab">Add Image Voucher</a></li>
				        <?php
					}
				    ?>
			        <li><a href="chvoucheradmin.php?clid=<?php echo $clid.'&pid='.$pid.($startPos?'&start='.$startPos:'').'&submitaction='.$action; ?>">Voucher Admin</a></li>
			        <li><a href="#editortab"><span>Editors</span></a></li>
			    </ul>
				<div id="mdtab">
					<form id="checklisteditform" action="checklistadmin.php" method="post" name="editclmatadata" onsubmit="return validateMetadataForm(this)">
						<fieldset style="margin:15px;padding:10px;">
							<legend><b>Edit Checklist Details</b></legend>
							<div>
								<span>Checklist Name: </span>
								<input type="text" name="eclname" size="80" value="<?php echo $clManager->getClName();?>" />
							</div>
							<div>
								<span>Authors: </span>
								<input type="text" name="eclauthors" size="70" value="<?php echo $clArray["authors"]; ?>" />
							</div>
							<div>
								<span>Locality: </span>
								<input type="text" name="ecllocality" size="80" value="<?php echo $clArray["locality"]; ?>" />
							</div> 
							<div>
								<span>Publication: </span>
								<input type="text" name="eclpublication" size="80" value="<?php echo $clArray["publication"]; ?>" />
							</div>
							<div>
								<span>Abstract: </span>
								<textarea name="eclabstract" cols="70" rows="3"><?php echo $clArray["abstract"]; ?></textarea>
							</div>
							<div>
								<span>Parent Checklist: </span>
								<select name="eclparentclid">
									<option value="">Select a Parent checklist</option>
									<option value="">----------------------------------</option>
									<?php $clManager->echoParentSelect(); ?>
								</select>
							</div>
							<div>
								<span>Notes: </span>
								<input type="text" name="eclnotes" size="80" value="<?php echo $clArray["notes"]; ?>" />
							</div>
							<div>
								<span>Latitude Centroid: </span>
								<input id="latdec" type="text" name="ecllatcentroid" value="<?php echo $clArray["latcentroid"]; ?>" />
								<span style="cursor:pointer;" onclick="openMappingAid();">
									<img src="../images/world40.gif" style="width:12px;" />
								</span>
							</div>
							<div>
								<span>Longitude Centroid: </span>
								<input id="lngdec" type="text" name="ecllongcentroid" value="<?php echo $clArray["longcentroid"]; ?>" />
							</div>
							<div>
								<span>Point Radius (meters): </span>
								<input type="text" name="eclpointradiusmeters" value="<?php echo $clArray["pointradiusmeters"]; ?>" />
							</div>
							<div>
								<span>Public Access: </span>
								<select name="eclaccess">
									<option value="private">Private</option>
									<option value="public limited" <?php echo ($clArray["access"]=="public limited"?"selected":""); ?>>Public Limited</option>
									<option value="public" <?php echo ($clArray["access"]=="public"?"selected":""); ?>>Public Research Grade</option>
								</select>
							</div>
							<div>
								<input type='submit' name='submitaction' id='editsubmit' value='Submit Changes' />
							</div>
							<input type='hidden' name='clid' value='<?php echo $clid; ?>' />
							<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
						</fieldset>
					</form>
				</div>
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
									<input type="hidden" name="clid" value="<?php echo $clManager->getClid(); ?>" />
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
				<div id="editortab">
					<div style="margin:30px 0px 30px 15px;">
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
											<a href="checklistadmin.php?clid=<?php echo $clid.'&deleteuid='.$uid.'&pid='.$pid; ?>" onclick="return confirm('Are you sure you want to remove editing rights for this user?');" title="Delete this user">
												<img src="../../images/drop.png" style="width:12px;" />
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
				</div>
			</div>
			<?php
		}
		else{
			if(!$clid){
				echo '<div><span style="font-weight:bold;font-size:110%;">Error:</span> Checklist identifier not set</div>';
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