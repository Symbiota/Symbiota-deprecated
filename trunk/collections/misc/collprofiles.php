<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/CollectionProfileManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$showFamilyList = array_key_exists("sfl",$_REQUEST)?$_REQUEST["sfl"]:0;
$showCountryList = array_key_exists("scl",$_REQUEST)?$_REQUEST["scl"]:0;
$showStateList = array_key_exists("ssl",$_REQUEST)?$_REQUEST["ssl"]:0;
$newCollRec = array_key_exists("newcoll",$_REQUEST)?1:0;

$collManager = new CollectionProfileManager();
if($collId){
	$collManager->setCollectionId($collId);
}

$editCode = 0;		//0 = no permissions; 1 = CollEditor; 2 = CollAdmin; 3 = SuperAdmin 
if($symbUid){
	if($isAdmin){
		$editCode = 3;
	}
	else if($collId){
		if(array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])){
			$editCode = 2;
		}
		else if(array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"])){
			$editCode = 1;
		}
	}
}

if($newCollRec && $editCode < 3){
	$newCollRec = 0;		//Only Admin should be able to add a new collection profile
}
if($editCode > 1){
	if($action == 'Submit Edits'){
		$collManager->submitCollEdits($_REQUEST);
	}
}
if($editCode == 3){
	if($action == "Add New Profile"){
		$collId = $collManager->submitCollAdd($_REQUEST);
		$collManager->setCollectionId($collId);
	}
}
$collData = Array();
if($collId){
	$collData = $collManager->getCollectionData();
}

?>
<html>
<head>
	<title><?php echo $defaultTitle." ".($collId?$collData["collectionname"]:"") ; ?> Collection Profiles</title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
	<meta name="keywords" content="Natural history collections,<?php echo ($collId?$collData["collectionname"]:""); ?>" />
	<script language=javascript>
		function toggleById(target){
			if(target != null){
			  	var obj = document.getElementById(target);
				if(obj.style.display=="none" || obj.style.display==""){
					obj.style.display="block";
				}
			 	else {
			 		obj.style.display="none";
			 	}
			}
		}

		function openMappingAid(targetForm,targetLat,targetLong) {
		    mapWindow=open("../../tools/mappointaid.php?formname="+targetForm+"&latname="+targetLat+"&longname="+targetLong,"mappointaid","resizable=0,width=800,height=700,left=20,top=20");
		    if (mapWindow.opener == null) mapWindow.opener = self;
		}
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_misc_collprofilesMenu)?$collections_misc_collprofilesMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($collections_misc_collprofilesCrumbs)){
		if($collections_misc_collprofilesCrumbs){
			echo "<div class='navpath'>";
			echo "<a href='../../index.php'>Home</a> &gt; ";
			echo $collections_misc_collprofilesCrumbs;
			echo " <b>".($collData?$collData["collectionname"]:"Collection Profile")."</b>";
			echo "</div>";
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href='../../index.php'>Home</a> &gt; 
			<a href='../index.php'>Collections</a> &gt; 
			<b><?php echo ($collData?$collData['collectionname']:'').' Collection Profile'; ?></b>
		</div>
		<?php 
	}
	?>

	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($editCode > 0){
			?>
			<div style="float:right;margin:3px;cursor:pointer;" onclick="toggleById('controlpanel');" title="Toggle Manager's Control Panel">
				<img style='border:0px;' src='../../images/edit.png' />
			</div>
			<?php 
		}
		?>
		<h1><?php echo ($collId?$collData['collectionname']:'');?></h1>
		<?php
		if($editCode > 0){
			?>
			<div id="controlpanel" style="clear:both;display:none;">
				<fieldset style="padding:15px;">
					<legend><b><?php echo ($collId?$collData['collectionname']:'');?> Management Control Panel</b></legend>
					<ul>
						<li>
							<a href="../editor/occurrenceeditor.php?collid=<?php echo $collId; ?>">
								Add a New Specimen Record
							</a>
						</li>
						<?php if($editCode > 1){ ?>
							<li>
								<a href="#" onclick="toggleById('colledit');" >
									Edit Metadata and Contact Information
								</a>
							</li>
							<li>
								<a href="../admin/specimenupload.php?collid=<?php echo $collId; ?>">
									Upload Data
								</a>
							</li>
							<li>
								<a href="../editor/editreviewer.php?collid=<?php echo $collId; ?>">
									Review/Verify Specimen Edits 
								</a>
							</li>
							<li>
								<a href="../admin/spectaxcleaner.php?collid=<?php echo $collId; ?>">
									Manage/Clean Scientific Names 
								</a>
							</li>
						<?php } ?>
					</ul>
				</fieldset>
				<?php if($editCode == 3){ ?>
					<fieldset style="padding:15px;">
						<legend><b>Admin Control Panel</b></legend>
						<ul>
							<li>
								<a href="collprofiles.php?newcoll=1">
									Add New Collection Profile
								</a>
							</li>
						</ul>
					</fieldset>
				<?php } ?>
			</div>
			<?php 
		}
		if($collId || $newCollRec){
			if($action == "Add New Profile"){
				?>
				<hr />
				<div style="font-weight:bold;margin:20px;">
					New collection added successfully! <br/>
					Click <a href="../admin/specimenupload.php?collid=<?php echo $collId; ?>">here</a> 
					to upload specimen records for this new collection.
				</div>
				<hr />
				<?php 
			}
			if($editCode > 1){
				?>
				<div id="colledit" style="display:<?php echo ($newCollRec?'block':'none'); ?>;">
					<form id="colleditform" name="colleditform" action="collprofiles.php" method="post">
						<fieldset style="background-color:#FFF380;">
							<legend><b><?php echo ($newCollRec?'Add New':'Edit'); ?> Collection Information</b></legend>
							<div>
								Institution Code:
								<input type="text" name="institutioncode" value="<?php echo ($collId?$collData["institutioncode"]:'');?>" style="width:75px;" />
							</div>
							<div>
								Collection Code:
								<input type="text" name="collectioncode" value="<?php echo ($collId?$collData["collectioncode"]:'');?>" style="width:75px;" />
							</div>	
							<div>
								Collection Name: 
								<input type="text" name="collectionname" value="<?php echo ($collId?$collData["collectionname"]:'');?>" style="width:300px;" />
							</div>
							<div>
								Brief Description (300 character max): 
								<textarea rows="2" cols="45" name="briefdescription"><?php echo ($collId?$collData["briefdescription"]:'');?></textarea>
							</div>
							<div>
								Full Description (1000 character max): 
								<textarea rows="3" cols="45" name="fulldescription"><?php echo ($collId?$collData["fulldescription"]:'');?></textarea>
							</div>
							<div>
								Homepage:
								<input type="text" name="homepage" value="<?php echo ($collId?$collData["homepage"]:'');?>" style="width:300;" />
							</div>
							<div>
								Contact: 
								<input type="text" name="contact" value="<?php echo ($collId?$collData["contact"]:'');?>" style="width:200;" />
							</div>
							<div>
								Email:
								<input type="text" name="email" value="<?php echo ($collId?$collData["email"]:'');?>" style="width:200;" />
							</div>
							<div>
								Latitude:
								<input id="latdec" type="text" name="latitudedecimal" value="<?php echo ($collId?$collData["latitudedecimal"]:'');?>" />
								<span style="cursor:pointer;" onclick="openMappingAid('colleditform','latitudedecimal','longitudedecimal');">
									<img src="../../images/world40.gif" style="width:12px;" />
								</span>
							</div>
							<div>
								Longitude:
								<input id="lngdec" type="text" name="longitudedecimal" value="<?php echo ($collId?$collData["longitudedecimal"]:'');?>" />
							</div>
							<?php 
							if($isAdmin){ 
								?>
								<div>
									Management:
									<select name="managementtype">
										<option>Snapshot</option>
										<option <?php echo ($collId && $collData["managementtype"]=='Live Date'?'SELECTED':''); ?>>Live Date</option>
									</select>
								</div>
								<div>
									Icon URL:
									<input type="text" name="icon" style="width:320px;" value="<?php echo ($collId?$collData["icon"]:'');?>" title="Small url usually placed in /images/collicons/ folder" />
								</div>
								<div>
									Source Record URL:
									<input type="text" name="individualurl" style="width:270px;" value="<?php echo ($collId?$collData["individualurl"]:'');?>" title="Dynamic link to source database individual record page" />
								</div>
								<div>
									Sort Sequence:
									<input type="text" name="sortseq" value="<?php echo ($collId?$collData["sortseq"]:'');?>" />
								</div>
								<?php 
							} 
							?>
							<div>
								<?php 
								if($newCollRec){ 
									?>
									<input type="submit" name="action" value="Add New Profile" />
									<?php
								}
								else{
									?>
									<input type="hidden" name="collid" value="<?php echo $collId;?>" />
									<input type="submit" name="action" value="Submit Edits" />
									<?php 
								}
								?>
							</div>
						</fieldset>
					</form>
				</div>
				<?php
			}
			if(!$newCollRec){
				?>
				<div style='margin:10px;'>
					<div><?php echo $collData["briefdescription"];?></div>
					<div style='margin-top:5px;'><b>Contact:</b> <?php echo $collData["contact"]." (".str_replace("@","&lt;at&gt;",$collData["email"]);?>)</div>
					<?php 
						if($collData["homepage"]) echo "<div style='margin-top:5px;'><b>Home Page:</b> <a href='".$collData["homepage"]."'>".$collData["homepage"]."</a></div>";
						echo '<div style="margin-top:5px;"> ';
						echo '<b>Management: </b> ';
						if(stripos($collData['managementtype'],'live') !== false){
							echo 'Live Data managed directly within data portal';
						}
						else{
							echo 'Data snapshot of central database <br/>';
							echo '<b>Last Update:</b> '.$collData['uploaddate'];
						}
						echo '</div>';
					?>
	 				<?php if($collData["institutionname"]){ ?>
						<div style="float:left;font-weight:bold;">Address:&nbsp;</div>
						<div style="float:left;">
							<?php 
							echo "<div>".$collData["institutionname"].($collData["institutioncode"]?" (".$collData["institutioncode"].")":"")."</div>";
							if($collData["address1"]) echo "<div>".$collData["address1"]."</div>";
							if($collData["address2"]) echo "<div>".$collData["address2"]."</div>";
							if($collData["city"]) echo "<div>".$collData["city"].", ".$collData["stateprovince"]."&nbsp;&nbsp;&nbsp;".$collData["postalcode"]."</div>";
							if($collData["country"]) echo "<div>".$collData["country"]."</div>";
							if($collData["phone"]) echo "<div>".$collData["phone"]."</div>";
							?>
						</div>
					<?php } ?>
					<div style="clear:both;">
						<ul style='margin-top:10px;'>
							<li><?php echo $collData["recordcnt"];?> total records</li>
							<li><?php echo $collData["georefcnt"]." (".(round(100*$collData["georefcnt"]/($collData["recordcnt"]?$collData["recordcnt"]:1)));?>%) georeferenced</li>
							<li><?php echo $collData["familycnt"];?> families</li>
							<li><?php echo $collData["genuscnt"];?> genera</li>
							<li><?php echo $collData["speciescnt"];?> species</li>
						</ul>
					</div>
				</div>
				<div style='margin:20px 0px 20px 20px;width:200px;background-color:#FFFFCC;' class='fieldset'>
					<div class='legend'><b>Extra Statistics</b></div>
					<div><a href='collprofiles.php?collid=<?php echo $collId;?>&sfl=1'>Show Family Coverages</a></div>
					<div><a href='collprofiles.php?collid=<?php echo $collId;?>&scl=1'>Show Country Coverages</a></div>
					<div><a href='collprofiles.php?collid=<?php echo $collId;?>&ssl=1'>Show State Coverages</a></div>
				</div>
				<?php
				if($showFamilyList){
					echo "<div style='margin:20px 0px 20px 30px;width:300px;' class='fieldset'>";
					echo "<div class='legend'>Family Coverage</div>";
					$familyCntArr = $collManager->getFamilyRecordCounts();
					echo "<ul>";
					foreach($familyCntArr as $fam=>$cnt){
						$percentage = round(100*$cnt/$collData["recordcnt"]);
						if($percentage > 10){
							$cntStr = $percentage."%";
						}
						else{
							$cntStr = $cnt;
						}
						echo "<li>$fam (".$cntStr.")</li>";
					}
					echo "</ul>";
					echo "<div>* Number in parentheses are record counts. Counts greater than 10% of total are shown as percentages.</div>";
					echo "<div>";
				}
				if($showCountryList){
					echo "<div style='margin:20px 0px 20px 30px;width:300px;' class='fieldset'>";
					echo "<div class='legend'>Country Coverage</div>";
					$countryCntArr = $collManager->getCountryRecordCounts();
					echo "<ul>";
					foreach($countryCntArr as $country => $cnt){
						$percentage = round(100*$cnt/$collData["recordcnt"]);
						if($percentage > 10){
							$cntStr = $percentage."%";
						}
						else{
							$cntStr = $cnt;
						}
						echo "<li>$country (".$cntStr.")</li>";
					}
					echo "</ul>";
					echo "<div>* Number in parentheses are record counts. Counts greater than 10% of total are shown as percentages.</div>";
					echo "<div>";
				}
				if($showStateList){
					echo "<div style='margin:20px 0px 20px 30px;width:300px;' class='fieldset'>";
					echo "<div class='legend'>State (US) Coverage</div>";
					$stateCntArr = $collManager->getStateRecordCounts();
					echo "<ul>";
					foreach($stateCntArr as $state => $cnt){
						$percentage = round(100*$cnt/$collData["recordcnt"]);
						
						if($percentage > 10){
							$cntStr = $percentage."%";
						}
						else{
							$cntStr = $cnt;
						}
						echo "<li>$state (".$cntStr.")</li>";
					}
					echo "</ul>";
					echo "<div>* Number in parentheses are record counts. Counts greater than 10% of total are shown as percentages.</div>";
					echo "<div>";
				}
			}
		}
		else{
			$collList = $collManager->getCollectionList();
			?>
			<h1><?php echo $defaultTitle; ?> Collections </h1>
			<div style='margin:10px;'>Select a collection to see full details. </div>
			<table style='margin:10px;'>
				<?php 
				foreach($collList as $cId => $collArr){
					?>
					<tr>
						<td style='text-align:center;vertical-align:top;'>
							<img src='../../<?php echo $collArr['icon']; ?>' style='border-size:1px;height:30;width:30;' /><br/>
							<?php echo $collArr['collectioncode']; ?>
						</td>
						<td>
							<h3>
								<a href='collprofiles.php?collid=<?php echo $cId;?>'>
									<?php echo $collArr['collectionname']; ?>
								</a>
							</h3>
							<div style='margin:10px;'>
								<div><?php echo $collArr['briefdescription']; ?></div>
								<div style='margin-top:5px;'>
									<b>Contact:</b> 
									<?php echo $collArr['contact'].' ('.str_replace('@','&lt;at&gt;',$collArr['email']).')';?>
								</div>
								<div style='margin-top:5px'>
									<b>Home Page:</b> 
									<a href='<?php echo $collArr['homepage']; ?>'>
										<?php echo $collArr['homepage']; ?>
									</a>
								</div>
							</div>
							<div style='margin:5px 0px 15px 10px;'>
								<a href='collprofiles.php?collid=<?php echo $cId; ?>'>More Information</a>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan='2'><hr/></td>
					</tr>
					<?php 
				}
				?>
			</table>
			<?php 
		}
		?>
	</div>
	<?php
		include($serverRoot.'/footer.php');
	?>

</body>
</html>