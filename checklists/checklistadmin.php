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
		elseif($action == 'Add Editor'){
			$statusStr = $clManager->addEditor($_POST['editoruid']);
		}
		elseif(array_key_exists('deleteuid',$_REQUEST)){
			$statusStr = $clManager->deleteEditor($_REQUEST['deleteuid']);
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
		var clid = <?php echo $clid; ?>;
		var tabIndex = <?php echo $tabIndex; ?>;
	</script>
	<script type="text/javascript" src="../js/symb/checklists.checklistadmin.js"></script>
	<style type="text/css">
		li{margin:5px;}
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
			        <li><a href="#vadmintab"><span>Voucher Admin</span></a></li>
			        <li><a href="#editortab"><span>Editors</span></a></li>
			    </ul>
				<div id="mdtab">
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
								<input type="text" name="eclpublication" style="width:95%" value="<?php echo $clArray["publication"]; ?>" />
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
									<?php $clManager->echoParentSelect(); ?>
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
							<input type='hidden' name='cl' value='<?php echo $clid; ?>' />
							<input type="hidden" name="proj" value="<?php echo $pid; ?>" />
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
				<div id="vadmintab">
					<ul>
						<li>
							<a href="#" onclick="return toggle('sqlbuilderdiv');"><b>Modify SQL Fragment</b></a>
							<div style="margin-left:5px;"> 
								The SQL fragment defines geographically boundaries of the research area in order to  
								aid researchers in locating specimen vouchers that will serve as proof that a species occurs within the area.  
								See the Flora Voucher Mapping Tutorial for more details. 
							</div>
							<fieldset style="margin:10px;padding:10px;">
								<legend><b>Current Dynamic SQL Fragment</b></legend>
								<div style="margin:10px;font-weight:bold;font-size:120%;color:red;">
									<?php
									$dynSql = $clManager->getDynamicSql();
									echo ($dynSql?$dynSql:'SQL fragment needs to be set');
									?>
								</div>
								<div style="margin:10px;">
									<a href="#" onclick="return toggle('sqlbuilderdiv');"><b>Click here to create SQL fragment</b></a>
								</div>
								<div id="sqlbuilderdiv" style="display:none;margin-top:15px;">
									<hr/>
									<form name="sqlbuilderform" action="checklist.php" method="post" onsubmit="return validateSqlFragForm(this);">
										<div style="margin:10px;">
											Use this form to build an SQL fragment that will be used by the tools below to filter occurrence records 
											to those collected within the vacinity of the research area.  
											Click the 'Create SQL Fragment' button to build and save the SQL using the terms supplied in the form. 
											Your data administrator can aid you in establishing more complex SQL fragments than can be created within this form.  
										</div>
										<table style="margin:15px;">
											<tr>
												<td>
													<div style="margin:3px;">
														<b>Country:</b>
														<input type="text" name="country" onchange="" />
													</div>
													<div style="margin:3px;">
														<b>State:</b>
														<input type="text" name="state" onchange="" />
													</div>
													<div style="margin:3px;">
														<b>County:</b>
														<input type="text" name="county" onchange="" />
													</div>
													<div style="margin:3px;">
														<b>Locality:</b>
														<input type="text" name="locality" onchange="" />
													</div>
												</td>
												<td style="padding-left:20px;">
													<div>
														<b>Lat North:</b>
														<input type="text" name="latnorth" style="width:70px;" onchange="" title="Latitude North" />
													</div>
													<div>
														<b>Lat South:</b>
														<input type="text" name="latsouth" style="width:70px;" onchange="" title="Latitude South" />
													</div>
													<div>
														<b>Long East:</b>
														<input type="text" name="lngeast" style="width:70px;" onchange="" title="Longitude East" />
													</div>
													<div>
														<b>Long West:</b>
														<input type="text" name="lngwest" style="width:70px;" onchange="" title="Longitude West" />
													</div>
													<div>
														<input type="checkbox" name="latlngor" value="1" />
														Include Lat/Long as an "OR" condition
													</div>
													<div style="float:right;margin:20px 20px 0px 0px;">
														<input type="submit" name="submitaction" value="Create SQL Fragment" />
														<input type="hidden" name="tabindex" value="2" />
														<input type="hidden" name="emode" value="2" />
														<input type='hidden' name='cl' value='<?php echo $clid; ?>' />
														<input type='hidden' name='proj' value='<?php echo $proj; ?>' />
													</div>
												</td>
											</tr>
										</table>
									</form>
									<?php 
									if($dynSql){
										?>
										<form name="sqldeleteform" action="checklist.php" method="post" onsubmit="return confirm('Are you sure you want to delete current SQL statement?');">
											<div style="float:right;margin:10px 120px 20px 0px;">
												<input type="submit" name="submitaction" value="Delete SQL Fragment" />
											</div>
											<input type="hidden" name="tabindex" value="2" />
											<input type="hidden" name="emode" value="2" />
											<input type='hidden' name='cl' value='<?php echo $clid; ?>' />
											<input type='hidden' name='proj' value='<?php echo $proj; ?>' />
										</form>
										<?php
									}
									?>
								</div>
							</fieldset>
						</li>
						<li>
							<a href="checklist.php?cl=<?php echo $clid.'&proj='.$proj; ?>&submitaction=ListNonVouchered&tabindex=2&emode=2">
								<b>List Non-vouchered Taxa</b>
							</a> 
							<div style="margin-left:5px;">
								<?php 
								$nonVoucherCnt = $clManager->getNonVoucheredCnt();
								echo $nonVoucherCnt;
								?> 
								taxa without voucher links
							</div>
						</li>
						<li>
							<?php
							if($clManager->getVoucherCnt()){
								echo '<a href="checklist.php?cl='.$clid.'&proj='.$proj.'&submitaction=VoucherConflicts&tabindex=2&emode=2">';
							}
							else{
								echo '<a href="#" onclick="alert(\'There are no conflicts because no vouchers have yet been linked to this checklist\')">';
							} 
							?>
								<b>Check for Voucher Conflicts</b>
							</a> 
							<div style="margin-left:5px;">
								List vouchers where the current 
								identifications conflict with the scientific name to which they are linked. 
								This is usually due to recent annotations.
							</div>
						</li>
						<li>
							<?php 
							if($dynSql){
								echo '<a href="checklist.php?cl='.$clid.'&proj='.$proj.'&submitaction=ListMissingTaxa&tabindex=2&emode=2">';
							}
							else{
								echo '<a href="#" onclick="alert(\'SQL Fragment needs to be established before this function can be used\');toggle(\'sqlfragdiv\');">';
							}
							?>
							<b>Search for Missing Taxa</b>
							</a>
							<div style="margin-left:5px;">
								Look for specimens collected within the research area that represent taxa not yet added to list. 
								Be patient, this list may take a minute or so to render even though it might not seem like anything is happening.
							</div>
						</li>
						<?php 
						if($clManager->hasChildrenChecklists()){
							?>
							<li>
								<a href="checklist.php?cl=<?php echo $clid.'&proj='.$proj; ?>&submitaction=ListChildTaxa&tabindex=2&emode=2">
									<b>List New Taxa from Children Lists</b>
								</a> 
								<div style="margin-left:5px;">
									Display taxa that have been added to a child checklist but has not yet been added to this list
								</div> 
							</li>
							<?php
						} 
						?>
						<!-- 
						<li>
							<b>Reports</b>
							<div style="margin-left:5px;">
								<b>Print Voucher Pick List</b>
								<a href="voucherreports.php?cl=<?php echo $clid; ?>" target="_blank">
								</a> 
								Display in print mode a pick list of species with full voucher citations.  
							</div> 
						</li>
						-->
					</ul>
				
				</div>
				<div id="editortab">
					<div style="margin:30px 0px 30px 15px;">
						<div style="font-weight:bold;font-size:120%;">Current Editors</div>
						<?php 
						$editorArr = $clManager->getEditors();
						if($editorArr){
							echo "<ul>\n";
							foreach($editorArr as $uid => $uName){
								?>
								<li>
									<?php echo $uName; ?> 
									<a href="checklist.php?cl=<?php echo $clid.'&deleteuid='.$uid.'&proj='.$pid; ?>" onclick="return confirm('Are you sure you want to remove editing rights for this user?');" title="Delete this user">
										<img src="../images/drop.png" style="width:12px;" />
									</a>
								</li>
								<?php 
							}
							echo "</ul>\n";
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