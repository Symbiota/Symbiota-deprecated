<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/InventoryProjectManager.php');
header("Content-Type: text/html; charset=".$charset);

$proj = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:""; 
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:""; 
$editMode = array_key_exists("emode",$_REQUEST)?$_REQUEST["emode"]:0; 
$newProj = array_key_exists("newproj",$_REQUEST)?1:0;
$projSubmit = array_key_exists("projsubmit",$_REQUEST)?$_REQUEST["projsubmit"]:""; 
$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:0; 
$statusStr = '';

$projManager = new InventoryProjectManager();
if($pid){
	$projManager->setPid($pid);
}
elseif($proj){
	$projManager->setProj($proj);
	$pid = $projManager->getPid();
}

$isEditable = 0;
if($isAdmin || (array_key_exists("ProjAdmin",$userRights) && in_array($pid,$userRights["ProjAdmin"]))){
	$isEditable = 1;
}

if($isEditable && $projSubmit){
	if($projSubmit == 'Add New Project'){
		$pid = $projManager->addNewProject($_POST);
		if($pid){
			$statusStr = 'Success: Inventory Project created! To add checklists to the project, open the editing pane by '.
				'clicking on editing symbol to the far right of project name and then select the Checklist Management tab.';
		}
	}
	elseif($projSubmit == 'Submit Edits'){
		$projManager->submitProjEdits($_POST);
	}
	elseif($projSubmit == 'deluid'){
		if(!$projManager->deleteManager($_GET['uid'])){
			$statusStr = $projManager->getErrorStr();
		}
	}
	elseif($projSubmit == 'Add to Manager List'){
		if(!$projManager->addManager($_POST['uid'])){
			$statusStr = $projManager->getErrorStr();
		}
	}
	elseif($projSubmit == 'Add Checklist'){
		$projManager->addChecklist($_POST['clid']);
	}
	elseif($projSubmit == 'Delete Checklist'){
		$projManager->deleteChecklist($_POST['clid']);
	}
}
 
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>: Inventory Projects</title>
	<link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link type="text/css" href="../css/jquery-ui.css" rel="Stylesheet" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript">
		<?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
	</script>
	<script type="text/javascript">
	
		var tabIndex = <?php echo $tabIndex; ?>;

		$(document).ready(function() {
			$('#tabs').tabs(
				{ active: tabIndex }
			);
		});

		function toggleById(target){
			var obj = document.getElementById(target);
			if(obj.style.display=="none"){
				obj.style.display="block";
			}
			else {
				obj.style.display="none";
			}
		}

		function toggleResearchInfoBox(anchorObj){
			var obj = document.getElementById("researchlistpopup");
			var pos = findPos(anchorObj);
			var posLeft = pos[0];
			if(posLeft > 550){
				posLeft = 550;
			}
			obj.style.left = posLeft - 40;
			obj.style.top = pos[1] + 25;
			if(obj.style.display=="block"){
				obj.style.display="none";
			}
			else {
				obj.style.display="block";
			}
			var targetStr = "document.getElementById('researchlistpopup').style.display='none'";
			var t=setTimeout(targetStr,25000);
		}

		function toggleSurveyInfoBox(anchorObj){
			var obj = document.getElementById("surveylistpopup");
			var pos = findPos(anchorObj);
			var posLeft = pos[0];
			if(posLeft > 550){
				posLeft = 550;
			}
			obj.style.left = posLeft - 40;
			obj.style.top = pos[1] + 25;
			if(obj.style.display=="block"){
				obj.style.display="none";
			}
			else {
				obj.style.display="block";
			}
			var targetStr = "document.getElementById('surveylistpopup').style.display='none'";
			var t=setTimeout(targetStr,20000);
		}

		function findPos(obj){
			var curleft = 0; 
			var curtop = 0;
			if(obj.offsetParent) {
				do{
					curleft += obj.offsetLeft;
					curtop += obj.offsetTop;
				}while(obj = obj.offsetParent);
			}
			return [curleft,curtop];
		}

		function validateProjectForm(f){
			if(f.projname.value == ""){
				alert("Project name field cannot be empty.");
				return false;
			}
			else if(!isNumeric(f.sortsequence.value)){
				alert("Sort sequence can only be a numeric value.");
				return false;
			}
			else if(f.fulldescription.value.length > 2000){
				alert("Description can only have a maximum of 2000 characters. The description is currently " + f.fulldescription.value.length + " characters long.");
				return false;
			}
			return true;
		}

		function validateChecklistForm(f){
			if(f.clid.value == ""){
				alert("Select a checklist from the pulldown");
				return false;
			}
			return true;
		}

		function validateManagerAddForm(f){
			if(f.uid.value == ""){
				alert("Select a user from the pulldown");
				return false;
			}
			return true;
		}
		
		function isNumeric(sText){
		   	var validChars = "0123456789-.";
		   	var ch;
		 
		   	for(var i = 0; i < sText.length; i++){ 
				ch = sText.charAt(i);
				if(validChars.indexOf(ch) == -1) return false;
		   	}
			return true;
		}
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($projects_indexMenu)?$projects_indexMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($projects_indexCrumbs)){
		?>
		<div class="navpath">
			<?php echo $projects_indexCrumbs;?>
			<b>Inventory Projects</b> 
		</div>
		<?php 
	}
	?>
	
	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="margin:20px;font-weight:bold;color:<?php echo (stripos($statusStr,'success')!==false?'green':'red');?>;">
				<?php echo $statusStr; ?>
			</div>
			<hr/>
			<?php 
		}
		if($pid || $newProj){
			if($isEditable && !$newProj){
				?>
				<div style="float:right;cursor:pointer;" onclick="toggleById('tabs');" title="Toggle Editing Functions">
					<img style="border:0px;" src="../images/edit.png"/>
				</div>
				<?php 
			}
			$projArr = Array();
			$projArr = $projManager->getProjectData();
			if($projArr){
				?>
				<h1><?php echo $projArr["projname"]; ?></h1>
				<div style='margin: 10px;'>
					<div>
						<b>Project Managers:</b> 
						<?php echo $projArr["managers"];?>
					</div>
					<div style='margin-top:10px;'>
						<?php echo $projArr["fulldescription"];?>
					</div>
					<div style='margin-top:10px;'>
						<?php echo $projArr["notes"]; ?>
					</div>
				</div>
				<?php 
			}
			if($isEditable){ 
				?>
				<div id="tabs" style="height:500px;margin:10px;display:<?php echo ($newProj||$editMode?'block':'none'); ?>;">
				    <ul>
				        <li><a href="#mdtab"><span>Metadata</span></a></li>
				        <?php
						if($pid){
							?>
							<li><a href="managertab.php?pid=<?php echo $pid; ?>"><span>Inventory Managers</span></a></li>
							<li><a href="checklisttab.php?pid=<?php echo $pid; ?>"><span>Checklist Management</span></a></li>
							<?php
						}
						?>
				    </ul>
					<div id="mdtab">
						<fieldset style="background-color:#FFF380;">
							<legend><b><?php echo ($newProj?'Add New':'Edit'); ?> Project</b></legend>
							<form name='projeditorform' action='index.php' method='post' onsubmit="return validateProjectForm(this)">
								<table style="width:100%;">
									<tr>
										<td>
											Project Name:
										</td>
										<td>
											<input type="text" name="projname" value="<?php if($projArr) echo $projArr["projname"]; ?>" style="width:95%;"/>
										</td>
									</tr>	
									<tr>
										<td>
											Managers: 
										</td>
										<td>
											<input type="text" name="managers" value="<?php if($projArr) echo $projArr["managers"]; ?>" style="width:95%;"/>
										</td>
									</tr>	
									<tr>
										<td>
											Description: 
										</td>
										<td>
											<textarea rows="8" cols="45" name="fulldescription" maxlength="2000" style="width:95%"><?php if($projArr) echo $projArr["fulldescription"];?></textarea>
										</td>
									</tr>	
									<tr>
										<td>
											Notes:
										</td>
										<td>
											<input type="text" name="notes" value="<?php if($projArr) echo $projArr["notes"];?>" style="width:95%;"/>
										</td>
									</tr>	
									<tr>
										<td>
											Public: 
										</td>
										<td>
											<select name="ispublic">
												<option value="0">Private</option>
												<option value="1" <?php echo ($projArr&&$projArr['ispublic']?'SELECTED':''); ?>>Public</option>
											</select>
										</td>
									</tr>	
									<tr>
										<td>
											Sort Sequence: 
										</td>
										<td>
											<input type="text" name="sortsequence" value="<?php if($projArr) echo $projArr["sortsequence"];?>" style="width:40;"/>
										</td>
									</tr>	
									<tr>
										<td colspan="2">
											<div style="margin:15px;">
												<?php 
												if($newProj){
													?>
													<input type="submit" name="projsubmit" value="Add New Project" />
													<?php
												}
												else{
													?>
													<input type="hidden" name="proj" value="<?php echo $pid;?>">
													<input type="submit" name="projsubmit" value="Submit Edits" />
													<?php 
												}
												?>
											</div>
										</td>
									</tr>
								</table>
							</form>
						</fieldset>
					</div>
				</div>
				<?php 
			}
			if($pid){
				?>
		        <div style="margin:20px;">
		            <?php
		            $researchList = $projManager->getResearchChecklists();
					if($researchList){
					?>
						<div style="font-weight:bold;font-size:130%;">
							Research Checklists
							<span onclick="toggleResearchInfoBox(this);" title="What is a Research Species List?" style="cursor:pointer;">
								<img src="../images/qmark_big.png" style="height:15px;"/>
							</span> 
							<a href="../checklists/clgmap.php?cltype=research&proj=<?php echo $pid;?>" title="Map Checklists">
								<img src='../images/world.png' style='width:14px;border:0' />
							</a>
						</div>
						<div id="researchlistpopup" class="genericpopup" style="display:none;">
							<img src="../images/uptriangle.png" style="position: relative; top: -22px; left: 30px;" />
				            Research checklists are pre-compiled by biologists.
				            This is a very controlled method for building a species list, which allows for  
				            specific specimens to be linked to the species names within the checklist and thus serve as vouchers. 
				            Specimen vouchers are proof that the species actually occurs in the given area. If there is any doubt, one
				            can inspect these specimens for verification or annotate the identification when necessary.
						</div>
						<?php 
						if($keyModIsActive === true || $keyModIsActive === 1){
							?>
							<div style="margin-left:15px;font-size:90%">
								The <img src="../images/key.png" style="width: 12px;" alt="Golden Key Symbol" /> 
								symbol opens the species list as an interactive key.
							</div>
							<?php
						}
						$gMapUrl = $projManager->getGoogleStaticMap("research");
						if($gMapUrl){
							?>
							<div style="float:right;text-align:center;">
								<a href="../checklists/clgmap.php?cltype=research&proj=<?php echo $pid;?>" title="Map Checklists">
									<img src="<?php echo $gMapUrl; ?>" title="Map representation of checklists" alt="Map representation of checklists" />
									<br/>
									Click to Open Map
								</a>
							</div>
							<?php
						} 
						?>
						<div style="float:left;">
							<ul>
							<?php 	
								foreach($researchList as $key=>$value){
				            ?>
								<li>
									<a href='../checklists/checklist.php?cl=<?php echo $key."&pid=".$pid; ?>'>
										<?php echo $value; ?>
									</a> 
									<?php 
									if($keyModIsActive === true || $keyModIsActive === 1){
										?>
										<a href='../ident/key.php?cl=<?php echo $key; ?>&proj=<?php echo $pid; ?>&taxon=All+Species'>
											<img style='width:12px;border:0px;' src='../images/key.png'/>
										</a>
										<?php
									}
									?>
								</li>
								<?php } ?>
							</ul>
						</div>
					<?php }
					$surveyList = $projManager->getSurveyLists();
					if($surveyList){
					?>
						<div style="clear:both;">
							<div style="font-weight:bold;font-size:130%;">
								Survey Species Lists 
								<span onclick="toggleSurveyInfoBox(this);" title="What is a Dynamic Survey Species List?" style="cursor:pointer;">
									<img src="../images/qmark_big.png" style="height:15px;"/>
								</span> 
								<a href="../checklists/clgmap.php?cltype=survey&proj=<?php echo $pid;?>" title="Map checklists">
									<img src="../images/world.png" style="width:14px;border:0" />
								</a>
							</div>
						</div>
						<div id="surveylistpopup" class="genericpopup" style="display:none;">
							<img src="../images/uptriangle.png" style="position: relative; top: -22px; left: 30px;" />
				            Survey Species Lists are defined through the linkage of species occurrences 
				            to a survey project name. This method allows long-term biological surveys to be conducted through group participation. 
				            If a team member comes across a new species, they document the occurrence through a specimen collection or a
				            photo observation. Linking the occurrence to a survey project automatically adds the species to the checklist. 
				            Verification of the occurrence by taxonomic experts ensure that a correct identified has been made. If the occurrence was 
				            misidentified, an annotation of the physical specimen within a collection or of an image stored within the system   
							will automatically adjust the species. Explicit maintenance of the species list is unnecessary since the checklist is 
							dynamically generated from the vouchers on demand. 
						</div>
						<?php 
						$gMapUrl = $projManager->getGoogleStaticMap("survey");
						if($gMapUrl){
							?>
							<div style="float:right;text-align:center;">
								<a href="../checklists/clgmap.php?cltype=survey&proj=<?php echo $pid;?>" title="Map Checklists">
									<img src="<?php echo $gMapUrl; ?>" title="Map representation of checklists" alt="Map representation of checklists" />
									<br/>
									Click to Open Map
								</a>
							</div>
							<?php
						} 
						?>
						<div style="float:left;">
							<ul>
							<?php 	
							foreach($surveyList as $key=>$value){
				            ?>
			            
								<li>
									<a href='../checklists/survey.php?surveyid=<?php echo $key.'&proj='.$pid;?>'><?php echo $value;?></a> 
								</li>
								<?php } ?>
							</ul>
						</div>
					<?php } ?>
				</div>
				<?php
			}
		}
		else{
			echo '<h1>'.$defaultTitle.' Projects</h1>'; 
			$projectArr = $projManager->getProjectList();
			foreach($projectArr as $pid => $projList){
				?>
				<h2><a href="index.php?pid=<?php echo $pid; ?>"><?php echo $projList["projname"]; ?></a></h2>
				<div style="margin:0px 0px 30px 15px;">
					<div><b>Managers:</b> <?php echo ($projList["managers"]?$projList["managers"]:'Not defined'); ?></div>
					<div style='margin-top:10px;'><?php echo $projList["descr"]; ?></div>
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
