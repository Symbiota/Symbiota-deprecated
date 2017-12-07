<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDataset.php');
header("Content-Type: text/html; charset=".$charset);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/datasets/datasetmanager.php?'.$_SERVER['QUERY_STRING']);

$datasetId = $_REQUEST['datasetid'];
$tabIndex = array_key_exists('tabindex',$_REQUEST)?$_REQUEST['tabindex']:0;
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

//Sanitation
if(!is_numeric($datasetId)) $datasetId = 0;
if(!is_numeric($tabIndex)) $tabIndex = 0;
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';

$datasetManager = new OccurrenceDataset();
$datasetManager->setSymbUid($SYMB_UID);

$mdArr = $datasetManager->getDatasetMetadata($datasetId);
$role = '';
$roleLabel = '';
$isEditor = 0;
if($SYMB_UID == $mdArr['uid']){
	$isEditor = 1;
	$role = 'owner';
}
elseif(isset($mdArr['roles'])){
	if(in_array('DatasetAdmin',$mdArr['roles'])){
		$isEditor = 1;
		$role = 'administrator';
	}
	elseif(in_array('DatasetEditor',$mdArr['roles'])){
		$isEditor = 2;
		$role = 'editor';
		$roleLabel = 'Can add and remove occurrences only';
	}
	elseif(in_array('DatasetReader',$mdArr['roles'])){
		$isEditor = 3;
		$role = 'read access only';
	}
}

$statusStr = '';
if($isEditor){
	if($action == 'Export Selected Occurrences'){
		if($datasetManager->exportDataset($datasetId, $_POST['occid'], $schema, $format, $cset)){
			$datasetId = 0;
		}
	}
	if($isEditor < 3){
		if($action == 'Remove Selected Occurrences'){
			if($datasetManager->removeSelectedOccurrences($datasetId,$_POST['occid'])){
				//$statusStr = 'Selected occurrences removed successfully';
			}
			else{
				$statusStr = implode(',',$datasetManager->getErrorArr());
			}
		}
	}
	if($isEditor == 1){
		if($action == 'Save Edits'){
			if($datasetManager->editDataset($_POST['datasetid'],$_POST['name'],$_POST['notes'])){
				$mdArr = $datasetManager->getDatasetMetadata($datasetId);
			}
			else{
				$statusStr = implode(',',$datasetManager->getErrorArr());
			}
		}
		elseif($action == 'Merge'){
			if($datasetManager->mergeDatasets($_POST['dsids[]'])){
				$statusStr = 'Datasets merged successfully';
			}
			else{
				$statusStr = implode(',',$datasetManager->getErrorArr());
			}
		}
		elseif($action == 'Clone (make copy)'){
			if($datasetManager->cloneDatasets($_POST['dsids[]'])){
				$statusStr = 'Datasets cloned successfully';
			}
			else{
				$statusStr = implode(',',$datasetManager->getErrorArr());
			}
		}
		elseif($action == 'Delete Dataset'){
			if($datasetManager->deleteDataset($_POST['datasetid'])){
				header("Location: index.php");
			}
			else{
				$statusStr = implode(',',$datasetManager->getErrorArr());
			}
		}
		elseif(array_key_exists('adduser',$_POST)){
			if($datasetManager->addUser($datasetId,$_POST['adduser'],$_POST['role'])){
				$statusStr = 'User added successfully';
			}
			else{
				$statusStr = implode(',',$datasetManager->getErrorArr());
			}
		}
		elseif($action == 'DelUser'){
			if($datasetManager->deleteUser($datasetId,$_POST['uid'],$_POST['role'])){
				$statusStr = 'User removed successfully';
			}
			else{
				$statusStr = implode(',',$datasetManager->getErrorArr());
			}
		}
	}
}

?>
<html>
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
		<title><?php echo $defaultTitle; ?> Occurrence Dataset Manager</title>
		<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/jquery-ui.css" type="text/css" rel="Stylesheet" />
		<script type="text/javascript" src="../../js/jquery.js"></script>
		<script type="text/javascript" src="../../js/jquery-ui.js"></script>
		<script type="text/javascript" src="../../js/symb/shared.js"></script>
		<script language="javascript" type="text/javascript">
			$(document).ready(function() {
				var dialogArr = new Array("schemanative","schemadwc");
				var dialogStr = "";
				for(i=0;i<dialogArr.length;i++){
					dialogStr = dialogArr[i]+"info";
					$( "#"+dialogStr+"dialog" ).dialog({
						autoOpen: false,
						modal: true,
						position: { my: "left top", at: "center", of: "#"+dialogStr }
					});
		
					$( "#"+dialogStr ).click(function() {
						$( "#"+this.id+"dialog" ).dialog( "open" );
					});
				}

				$('#tabs').tabs({
					active: <?php echo $tabIndex; ?>,
					beforeLoad: function( event, ui ) {
						$(ui.panel).html("<p>Loading...</p>");
					}
				});

				$( "#userinput" ).autocomplete({
					source: "rpc/getuserlist.php",
					minLength: 3,
					autoFocus: true
				});
				
			});

			function selectAll(cb){
				boxesChecked = true;
				if(!cb.checked){
					boxesChecked = false;
				}
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					dbElement.checked = boxesChecked;
				}
			}

			function validateDataSetForm(f){
				var dbElements = document.getElementsByName("dsids[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					if(dbElement.checked) return true;
				}
				alert("Please select at least one dataset!");

				var confirmStr = '';
				if(f.submitaction.value == "Merge"){
					confirmStr = 'Are you sure you want to merge selected datasets?';
				}
				else if(f.submitaction.value == "Clone (make copy)"){
					confirmStr = 'Are you sure you want to clone selected datasets?';
				}
				else if(f.submitaction.value == "Delete"){
					confirmStr = 'Are you sure you want to delete selected datasets?';
				}
				if(confirmStr == '') return true;
				return confirm(confirmStr);
			}

			function validateEditForm(f){
				if(f.name.value == ''){
					alert("Dataset name cannot be null");
					return false;
				}
				return true;
			}

			function validateOccurForm(f){
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					if(dbElement.checked) return true;
				}
			   	alert("Please select at least one specimen!");
			  	return false;
			}

			function openIndPopup(occid){
				openPopup("../individual/index.php?occid="+occid);
			}

			function openPopup(urlStr){
				var wWidth = 900;
				if(document.getElementById('maintable').offsetWidth){
					wWidth = document.getElementById('maintable').offsetWidth*1.05;
				}
				else if(document.body.offsetWidth){
					wWidth = document.body.offsetWidth*0.9;
				}
				newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
				if (newWindow.opener == null) newWindow.opener = self;
				newWindow.focus();
				return false;
			}
		</script>
	</head>
	<body>
	<?php
	$displayLeftMenu = (isset($collections_datasets_indexMenu)?$collections_datasets_indexMenu:false);
	include($SERVER_ROOT."/header.php");
	?>
	<div class='navpath'>
		<a href='../../index.php'>Home</a> &gt;&gt; 
		<?php
		if(isset($collections_datasets_indexCrumbs)){
			echo $collections_datasets_indexCrumbs;
		}
		else{
			echo '<a href="../../profile/viewprofile.php?tabindex=1">My Profile</a> &gt;&gt; ';
		}
		?>
		<a href="index.php">
			Return to Dataset Listing 
		</a> &gt;&gt;
		<b>Dataset Manager</b> 
	</div>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($statusStr){
			$color = 'green';
			if(strpos($statusStr,'ERROR') !== false) $color = 'red';
			elseif(strpos($statusStr,'WARNING') !== false) $color = 'orange';
			elseif(strpos($statusStr,'NOTICE') !== false) $color = 'yellow';
			echo '<div style="margin:15px;color:'.$color.';">';
			echo $statusStr;
			echo '</div>';
		}
		if($datasetId){
			echo '<div style="margin:10px 0px 5px 20px;font-weight:bold;font-size:130%;">'.$mdArr['name'].'</div>';
			echo '<div style="margin-left:20px" title="'.$roleLabel.'">Role: '.$role.'</div>';
			if($isEditor){
				?>
				<div id="tabs" style="margin:10px;">
					<ul>
						<li><a href="#occurtab"><span>Occurrence List</span></a></li>
						<?php
						if($isEditor == 1){ 
							?>
							<li><a href="#admintab"><span>General Management</span></a></li>
							<li><a href="#accesstab"><span>User Access</span></a></li>
							<?php
						}
						?>
					</ul>
					<div id="occurtab">
						<?php 
						$occArr = $datasetManager->getOccurrences($datasetId);
						?>
						<form name="occurform" action="datasetmanager.php" method="post" onsubmit="return validateOccurForm(this)">
							<div style="float:right;margin-right:10px">
								<b>Count: <?php echo count($occArr); ?> records</b>
							</div>
							<table class="styledtable" style="font-family:Arial;font-size:12px;">
								<tr>
									<th><input name="" value="" type="checkbox" onclick="selectAll(this);" title="Select/Deselect all Specimens" /></th>
									<th>catalog #</th>
									<th>Collector</th>
									<th>Scientific Name</th>
									<th>Locality</th>
								</tr>
								<?php 
								$trCnt = 0;
								foreach($occArr as $occid => $recArr){
									$trCnt++;
									?>
									<tr <?php echo ($trCnt%2?'class="alt"':''); ?>>
										<td>
											<input type="checkbox" name="occid[]" value="<?php echo $occid; ?>" />
										</td>
										<td>
											<?php echo $recArr['catnum']; ?>
											<a href="#" onclick="openIndPopup(<?php echo $occid; ?>); return false;">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
										</td>
										<td>
											<?php echo $recArr['coll']; ?>
										</td>
										<td>
											<?php echo $recArr['sciname']; ?>
										</td>
										<td>
											<?php echo $recArr['loc']; ?>
										</td>
									</tr>
									<?php 
								}
								?>
							</table>
							<div style="margin: 15px 50px;">
								<input name="datasetid" type="hidden" value="<?php echo $datasetId; ?>" />
								<?php 
								if($isEditor < 3){
									?>
									<div style="margin:5px"><input type="submit" name="submitaction" value="Remove Selected Occurrences" /></div>
									<?php
								} 
								?>
								<div style="margin:5px"><input type="submit" name="submitaction" value="Export Selected Occurrences" /></div>
								<div id='showoptdiv'><a href="#" onclick="toggle('optdiv');toggle('showoptdiv');return false;">Show Options</a></div>
								<div id="optdiv" style="display:none;">
									<fieldset>
										<legend><b>Options</b></legend>
										<table>
											<tr>
												<td valign="top">
													<div style="margin:10px;">
														<b>Structure:</b>
													</div> 
												</td>
												<td>
													<div style="margin:10px 0px;">
														<input type="radio" name="schema" value="symbiota" onclick="georefRadioClicked(this)" CHECKED /> 
														Symbiota Native
														<a id="schemanativeinfo" href="#" onclick="return false" title="More Information">
															<img src="../../images/info.png" style="width:13px;" />
														</a><br/>
														<div id="schemanativeinfodialog">
															Symbiota native is very similar to Darwin Core except with the addtion of a few fields
															such as substrate, associated collectors, verbatim description.
														</div>
														<input type="radio" name="schema" value="dwc" onclick="georefRadioClicked(this)" /> 
														Darwin Core
														<a id="schemadwcinfo" href="#" target="" title="More Information">
															<img src="../../images/info.png" style="width:13px;" />
														</a><br/>
														<div id="schemadwcinfodialog">
															Darwin Core (DwC) is a TDWG endorsed exchange standard specifically for biodiversity datasets. 
															For more information on what data fields are included in DwC, visit the 
															<a href="http://rs.tdwg.org/dwc/index.htm"target='_blank'>DwC Quick Reference Guide</a>.
														</div>
														*<a href='http://rs.tdwg.org/dwc/index.htm' class='bodylink' target='_blank'>What is Darwin Core?</a>
													</div>
												</td>
											</tr>
											<tr>
												<td valign="top">
													<div style="margin:10px;">
														<b>File Format:</b>
													</div> 
												</td>
												<td>
													<div style="margin:10px 0px;">
														<input type="radio" name="format" value="csv" CHECKED /> Comma Delimited (CSV)<br/>
														<input type="radio" name="format" value="tab" /> Tab Delimited<br/>
													</div> 
												</td>
											</tr>
											<tr>
												<td valign="top">
													<div style="margin:10px;">
														<b>Character Set:</b>
													</div> 
												</td>
												<td>
													<div style="margin:10px 0px;">
														<?php 
														$cSet = strtolower($charset);
														?>
														<input type="radio" name="cset" value="iso-8859-1" <?php echo ($cSet=='iso-8859-1'?'checked':''); ?> /> ISO-8859-1 (western)<br/>
														<input type="radio" name="cset" value="utf-8" <?php echo ($cSet=='utf-8'?'checked':''); ?> /> UTF-8 (unicode)
													</div>
												</td>
											</tr>
										</table>							
									</fieldset>
								</div>
							</div>
						</form>
					</div>
					<?php
					if($isEditor == 1){ 
						?>
						<div id="admintab">
							<fieldset style="padding:15px;margin:15px;">
								<legend><b>Editor</b></legend>
								<form name="editform" action="datasetmanager.php" method="post" onsubmit="return validateEditForm(this)">
									<div>
										<b>Name</b><br />
										<input name="name" type="text" value="<?php echo $mdArr['name']; ?>" style="width:250px" />
									</div>
									<div>
										<b>Notes</b><br />
										<input name="notes" type="text" value="<?php echo $mdArr['notes']; ?>" style="width:90%" />
									</div>
									<div style="margin:15px;">
										<input name="datasetid" type="hidden" value="<?php echo $datasetId; ?>" />
										<input name="submitaction" type="submit" value="Save Edits" />
									</div>
								</form>
							</fieldset>
							<fieldset style="padding:15px;margin:15px;">
								<legend><b>Delete Dataset</b></legend>
								<form name="editform" action="datasetmanager.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this dataset?')">
									<div style="margin:15px;">
										<input name="datasetid" type="hidden" value="<?php echo $datasetId; ?>" />
										<input name="tabindex" type="hidden" value="1" />
										<input name="submitaction" type="submit" value="Delete Dataset" />
									</div>
								</form>
							</fieldset>
						</div>
						<div id="accesstab">
							<div style="margin:25px 10px;">
								<?php 
								$userArr = $datasetManager->getUsers($datasetId);
								$roleArr = array('DatasetAdmin' => 'Full Access Users','DatasetEditor' => 'Read/Write Users','DatasetReader' => 'Read Only Users');
								foreach($roleArr as $roleStr => $labelStr){
									?>
									<div style="margin:0px 15px;"><b><u><?php echo $labelStr; ?></u></b></div>
									<div style="margin:15px;">
										<?php 
										if(array_key_exists($roleStr,$userArr)){
											echo '<ul>';
											$uArr = $userArr[$roleStr];
											foreach($uArr as $uid => $name){
												?>
												<li>
													<?php echo $name; ?>
													<form name="deluserform" method="post" action="datasetmanager.php" style="display:inline;" onsubmit="return confirm('Are you sure you want to remove <?php echo $name; ?>')">
														<input name="submitaction" type="hidden" value="DelUser" />
														<input name="role" type="hidden" value="<?php echo $roleStr; ?>" />
														<input name="uid" type="hidden" value="<?php echo $uid; ?>" />
														<input name="datasetid" type="hidden" value="<?php echo $datasetId; ?>" />
														<input name="tabindex" type="hidden" value="2" />
														<input name="submitimage" type="image" src="../../images/drop.png" /> 
													</form>
												</li>
												<?php 
											}
											echo '</ul>';
										}
										else{
											echo '<div style="margin:15px;">None Assigned</div>';
										}
										?>
									</div>
									<?php
								} 
								?>
							</div>
							<div style="margin:15px;">
								<fieldset>
									<legend><b>Add User</b></legend>
									<form name="addform" action="datasetmanager.php" method="post" onsubmit="return validateAddForm(this)">
										Login: 
										<input id="userinput" name="adduser" type="text" style="width:250px;" /><br />
										Role: 
										<select name="role">
											<option value="DatasetAdmin">Full Access</option>
											<option value="DatasetEditor">Read/Write Access</option>
											<option value="DatasetReader">Read-Only Access</option>
										</select>
										<div style="margin:10px;">
											<input name="tabindex" type="hidden" value="2" />
											<input name="datasetid" type="hidden" value="<?php echo $datasetId; ?>" />
											<input name="submitaction" type="submit" value="Add User" />
										</div>
									</form>
								</fieldset>
							</div>
						</div>
						<?php
					} 
					?>
				</div>
				<?php
			}
			else{
				echo '<div><b>You are not authorized to view this dataset</b></div>';
			}
		}
		else{
			echo '<div><b>ERROR: dataset id not identified</b></div>';
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT."/footer.php");
	?>
	</body>
</html>