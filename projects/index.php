<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/FloraProjectManager.php');
header("Content-Type: text/html; charset=".$charset);

$proj = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:""; 
$editMode = array_key_exists("emode",$_REQUEST)?$_REQUEST["emode"]:""; 
$newProj = array_key_exists("newproj",$_REQUEST)?1:0;
$projSubmit = array_key_exists("projsubmit",$_REQUEST)?$_REQUEST["projsubmit"]:""; 

$projManager = new FloraProjectManager($proj);

$isEditable = 0;
if($isAdmin){
	$isEditable = 1;
}

if($isEditable && $projSubmit){
	$projEditArr = Array();
	$projEditArr["projname"] = $_REQUEST["projname"];
	$projEditArr["managers"] = $_REQUEST["managers"];
	$projEditArr["briefdescription"] = $_REQUEST["briefdescription"];
	$projEditArr["fulldescription"] = $_REQUEST["fulldescription"];
	$projEditArr["notes"] = $_REQUEST["notes"];
	$projEditArr["occurrencesearch"] = $_REQUEST["occurrencesearch"];
	$projEditArr["ispublic"] = $_REQUEST["ispublic"];
	$projEditArr["sortsequence"] = $_REQUEST["sortsequence"];
	if($projSubmit == 'Submit Edits'){
		$projManager->submitProjEdits($projEditArr);
	}
	else if($projSubmit == 'Add New Project'){
		$projManager->addNewProject($projEditArr);
	}
}
 
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Species Lists</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css" />
	<script type="text/javascript">
		<?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
	</script>
	<script type="text/javascript">
	
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
	</script>
</head>

<body>

	<?php
	$displayLeftMenu = (isset($projects_indexMenu)?$projects_indexMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($projects_indexCrumbs)){
		?>
		<div class="navpath">
			<a href="../index.php">Home</a> &gt; 
			<?php echo $projects_indexCrumbs;?>
			<b><?php echo $defaultTitle; ?> Project</b> 
		</div>
		<?php 
	}
	?>
	
	<!-- This is inner text! -->
	<div id="innertext">

	<?php
	
	if($proj || $newProj){
		if($isEditable && !$newProj){
			?>
			<div style="float:right;cursor:pointer;" onclick="toggleById('projeditor');" title="Toggle Editing Functions">
				<img style="border:0px;" src="../images/edit.png"/>
			</div>
			<?php 
		}
		$projArr = Array();
		$projArr = $projManager->getProjectData();
		if($projArr){
			?>
			<h1><?php echo $projArr["projname"]; ?></h1>
			<div style='margin:10px;'>
				<b>Project Managers:</b> 
				<?php echo $projArr["managers"];?>
			</div>
			<div style='margin:10px;'>
				<?php echo $projArr["fulldescription"];?>
			</div>
			<div style='margin:10px;'>
				<?php echo $projArr["notes"]; ?>
			</div>
			<?php 
		}

		if($isEditable){ ?>
			<form name='projeditorform' action='index.php' method='post'>
				<fieldset id="projeditor" style="display:<?php echo ($newProj||$editMode?'block':'none'); ?>;background-color:#FFF380;">
					<legend><b><?php echo ($newProj?'Add New':'Edit'); ?> Project</b></legend>
					<table>
						<tr>
							<td>
								Project Name:
							</td>
							<td>
								<input type="text" name="projname" value="<?php if($projArr) echo $projArr["projname"]; ?>" style="width:300px;"/>
							</td>
						</tr>	
						<tr>
							<td>
								Managers: 
							</td>
							<td>
								<input type="text" name="managers" value="<?php if($projArr) echo $projArr["managers"]; ?>" style="width:300px;"/>
							</td>
						</tr>	
						<tr>
							<td>
								Brief Description: 
							</td>
							<td>
								<textarea rows="2" cols="45" name="briefdescription" maxsize="300"><?php if($projArr) echo $projArr["briefdescription"];?></textarea>
							</td>
						</tr>	
						<tr>
							<td>
								Full Description: 
							</td>
							<td>
								<textarea rows="3" cols="45" name="fulldescription" maxsize="1000"><?php if($projArr) echo $projArr["fulldescription"];?></textarea>
							</td>
						</tr>	
						<tr>
							<td>
								Notes:
							</td>
							<td>
								<input type="text" name="notes" value="<?php if($projArr) echo $projArr["notes"];?>" style="width:300;"/>
							</td>
						</tr>	
						<tr>
							<td>
								Occurrence Search: 
							</td>
							<td>
								<select name="occurrencesearch">
									<option value="0">Exclude in Occurrence Search Engine</option>
									<option value="1" <?php echo ($projArr&&$projArr['occurrencesearch']?'SELECTED':''); ?>>Include in Occurrence Search Engine</option>
								</select>
							</td>
						</tr>	
						<tr>
							<td>
								Public: 
							</td>
							<td>
								<select name="ispublic">
									<option value="0">Not Public</option>
									<option value="1" <?php echo ($projArr&&$projArr['ispublic']?'SELECTED':''); ?>>Is Public</option>
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
										<input type="hidden" name="proj" value="<?php echo $projManager->getProjectId();?>">
										<input type="submit" name="projsubmit" value="Submit Edits" />
										<?php 
									}
									?>
								</div>
							</td>
						</tr>
					</table>
				</fieldset>
			</form>
		<?php 
		}
		if($proj){
			?>
	        <div style="margin:20px;">
	            <?php
	            $researchList = $projManager->getResearchChecklists();
				if($researchList){
				?>
					<h3>Research Checklists
						<span onclick="toggleResearchInfoBox(this);" title="What is a Research Species List?" style="cursor:pointer;">
							<img src="../images/qmark.jpg" style="height:15px;"/>
						</span> 
						<a href="../checklists/clgmap.php?cltype=research&proj=<?php echo $projManager->getProjectId();?>" title="Map Checklists">
							<img src='../images/world40.gif' style='width:14px;border:0' />
						</a>
					</h3>
					<div id="researchlistpopup" class="genericpopup" style="display:none;">
						<img src="../images/uptriangle.png" style="position: relative; top: -22px; left: 30px;" />
			            Research checklists are pre-compiled by floristic scientists.
			            This is a very controlled method for building a species list, which allows for  
			            specific specimens to be linked to the species names within the checklist and thus serve as vouchers. 
			            Specimen vouchers are proof that the species actually occurs in the given area. If there is any doubt, one
			            can inspect these specimens for verification or annotate the identification when necessary.
					</div>
					<?php 
					$gMapUrl = $projManager->getGoogleStaticMap("research");
					if($gMapUrl){
						?>
						<div style="float:right;text-align:center;">
							<a href="../checklists/clgmap.php?cltype=research&proj=<?php echo $projManager->getProjectId();?>" title="Map Checklists">
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
								<a href='../checklists/checklist.php?cl=<?php echo $key."&proj=".$projManager->getProjectId(); ?>'>
									<?php echo $value; ?>
								</a> 
								<?php 
								if($keyModIsActive){
									?>
									<a href='../ident/key.php?cl=<?php echo $key; ?>&proj=<?php echo $projManager->getProjectId(); ?>&taxon=All+Species'>
										<img style='width:12px;border:0px;' src='../images/key.jpg'/>
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
						<h3>Dynamic Survey Species Lists 
							<span onclick="toggleSurveyInfoBox(this);" title="What is a Dynamic Survey Species List?" style="cursor:pointer;">
								<img src="../images/qmark.jpg" style="height:15px;"/>
							</span> 
							<a href="../checklists/clgmap.php?cltype=survey&proj=<?php echo $projManager->getProjectId();?>" title="Map checklists">
								<img src="../images/world40.gif" style="width:14px;border:0" />
							</a>
						</h3>
					</div>
					<div id="surveylistpopup" class="genericpopup" style="display:none;">
						<img src="../images/uptriangle.png" style="position: relative; top: -22px; left: 30px;" />
			            Dynamic Survey Species Lists are defined through the linkage of species occurrences 
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
							<a href="../checklists/clgmap.php?cltype=survey&proj=<?php echo $projManager->getProjectId();?>" title="Map Checklists">
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
								<a href='../checklists/survey.php?surveyid=<?php echo $key;?>'><?php echo $value;?></a> 
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
		echo "<h1>".$defaultTitle." Projects</h1>"; 
		$projectArr = $projManager->getProjectList();
		foreach($projectArr as $pid => $projList){
			echo "<h2><a href='index.php?proj=".$pid."'>".$projList["projname"]."</a></h2>\n";
			if($projList["managers"]) echo "<div><b>Managers:</b> ".$projList["managers"]."</div>\n";
			echo "<div style='margin:10px;'>".$projList["descr"]."</div>\n";
		}
	}
	?>
	
	</div>
	<?php
	include($serverRoot.'/footer.php');
	?>
</body>
</html>
