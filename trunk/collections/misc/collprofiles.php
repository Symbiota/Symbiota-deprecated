<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/CollectionProfileManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$showFamilyList = array_key_exists("sfl",$_REQUEST)?$_REQUEST["sfl"]:0;
$familyDist = array_key_exists('family',$_REQUEST)?$_REQUEST['family']:'';
$showGeographicList = array_key_exists("sgl",$_REQUEST)?$_REQUEST["sgl"]:0;
$countryDist = array_key_exists('country',$_REQUEST)?$_REQUEST['country']:'';
$stateDist = array_key_exists('state',$_REQUEST)?$_REQUEST['state']:'';
$newCollRec = array_key_exists("newcoll",$_REQUEST)?1:0;
$eMode = array_key_exists('emode',$_REQUEST)?$_REQUEST['emode']:0;

$collManager = new CollectionProfileManager();
if($collId) $collManager->setCollectionId($collId);

$editCode = 0;		//0 = no permissions; 1 = CollEditor; 2 = CollAdmin; 3 = SuperAdmin 
if($symbUid){
	if($isAdmin){
		$editCode = 3;
	}
	else if($collId){
		if(array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])){
			$editCode = 2;
		}
		elseif(array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"])){
			$editCode = 1;
		}
	}
}

if($newCollRec && $editCode < 3){
	$newCollRec = 0;		//Only Admin should be able to add a new collection profile
}
if($editCode > 1){
	if($action == 'Submit Edits'){
		$collManager->submitCollEdits();
	}
}
if($editCode == 3){
	if($action == "Add New Profile"){
		$collId = $collManager->submitCollAdd();
		$collManager->setCollectionId($collId);
	}
}
$collData = Array();
if($collId) $collData = $collManager->getCollectionData();

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
			return false;
		}

		function openMappingAid() {
		    mapWindow=open("../../tools/mappointaid.php?formname=colleditform&latname=latitudedecimal&longname=longitudedecimal","mappointaid","resizable=0,width=800,height=700,left=20,top=20");
		    if (mapWindow.opener == null) mapWindow.opener = self;
		}

		function verifyCollEditForm(f){
			if(f.institutioncode.value == ''){
				alert("Institution Code must have a value");
				return false;
			}
			else if(f.collectionname.value == ''){
				alert("Collection Name must have a value");
				return false;
			}
			else if(!isNumeric(f.latdec.value) || !isNumeric(f.lngdec.value)){
				alert("Latitdue and longitude values must be in the decimal format (numeric only)");
				return false;
			}
			try{
				if(!isNumeric(f.sortseq.value)){
					alert("Sort sequence must be numeric only");
					return false;
				}
			}
			catch(ex){}
			return true;
		}

		function isNumeric(sText){
		   	var ValidChars = "0123456789-.";
		   	var IsNumber = true;
		   	var Char;
		 
		   	for(var i = 0; i < sText.length && IsNumber == true; i++){ 
			   Char = sText.charAt(i); 
				if(ValidChars.indexOf(Char) == -1){
					IsNumber = false;
					break;
		      	}
		   	}
			return IsNumber;
		}

		function dwcDoc(dcTag){
		    dwcWindow=open("http://rs.tdwg.org/dwc/terms/index.htm#"+dcTag,"dwcaid","width=1250,height=300,left=20,top=20,scrollbars=1");
		    if(dwcWindow.opener == null) dwcWindow.opener = self;
		    return false;
		}

	</script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_misc_collprofilesMenu)?$collections_misc_collprofilesMenu:true);
	include($serverRoot.'/header.php');
	if(isset($collections_misc_collprofilesCrumbs)){
		if($collections_misc_collprofilesCrumbs){
			echo "<div class='navpath'>";
			echo "<a href='../../index.php'>Home</a> &gt;&gt; ";
			echo $collections_misc_collprofilesCrumbs.' &gt;&gt; ';
			echo "<b>".($collData?$collData["collectionname"]:"Collection Profiles")."</b>";
			echo "</div>";
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href='../../index.php'>Home</a> &gt;&gt; 
			<a href='collprofiles.php'>Collections</a> &gt;&gt; 
			<b><?php echo ($collData?$collData['collectionname']:'Collection Profiles'); ?></b>
		</div>
		<?php 
	}
	?>

	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($editCode > 1){
			if($action == 'UpdateStatistics'){
				echo '<h2>Updating statisitcs related to this collection...</h2>';
				echo '<ul>';
				$collManager->updateStatistics();
				echo '</ul><hr/>';
				$collData = $collManager->getCollectionData();
			}
		}
		if($editCode > 0 && $collId){
			?>
			<div style="float:right;margin:3px;cursor:pointer;" onclick="toggleById('controlpanel');" title="Toggle Manager's Control Panel">
				<img style='border:0px;' src='../../images/edit.png' />
			</div>
			<?php 
		}
		?>
		<h1><?php echo ($collId?$collData['collectionname']:'');?></h1>
		<?php
		if($editCode > 0 && $collId){
			?>
			<div id="controlpanel" style="clear:both;display:<?php echo ($eMode?'block':'none'); ?>;">
				<fieldset style="padding:15px;">
					<legend><b><?php echo ($collId?$collData['collectionname']:'');?> Management Control Panel</b></legend>
					<ul>
						<?php
						if(stripos($collData['colltype'],'observation') !== false){ 
							?>
							<li>
								<a href="../editor/observationsubmit.php?collid=<?php echo $collId; ?>">
									Submit an Image Voucher (observation supported by a photo)
								</a>
							</li>
							<?php
						}
						?>
						<li>
							<a href="../editor/occurrenceeditor.php?gotomode=1&collid=<?php echo $collId; ?>">
								Add a New Occurrence Record
							</a>
						</li>
						<li>
							<a href="../editor/occurrenceeditor.php?collid=<?php echo $collId; ?>">
								Edit an Existing Occurrence Record
							</a>
						</li>
						<li>
							<a href="../datasets/index.php?collid=<?php echo $collId; ?>">
								Print Labels
							</a>
						</li>
						<?php if($editCode > 1){ ?>
							<li><b>Administrative Functions</b></li>
							<ul>
								<li>
									<a href="#" onclick="toggleById('colledit');" >
										Edit Metadata and Contact Information
									</a>
								</li>
								<li>
									<a href="collprofiles.php?collid=<?php echo $collId; ?>&action=UpdateStatistics" >
										Update Statistics
									</a>
								</li>
								<li>
									<a href="collpermissions.php?collid=<?php echo $collId; ?>" >
										Manage Permissions
									</a>
								</li>
								<li>
									<a href="#" onclick="newWindow = window.open('collbackup.php?collid=<?php echo $collId; ?>','bucollid','scrollbars=1,toolbar=1,resizable=1,width=400,height=200,left=20,top=20');">
										Download Data Backup File
									</a>
								</li>
								<li>
									<a href="../admin/specimenupload.php?collid=<?php echo $collId; ?>">
										Import/Update Specimen Records
									</a>
								</li>
								<li>
									<a href="../specprocessor/index.php?collid=<?php echo $collId; ?>">
										Batch Load Specimen Images
									</a>
								</li>
								<li>
									<a href="../georef/batchgeoreftool.php?collid=<?php echo $collId; ?>">
										Batch Georeference Specimens
									</a>
								</li>
								<li>
									<a href="../editor/editreviewer.php?collid=<?php echo $collId; ?>">
										Review/Verify General Specimen Edits 
									</a>
								</li>
								<!-- 
								<li>
									<a href="../admin/spectaxcleaner.php?collid=<?php echo $collId; ?>">
										Manage/Clean Scientific Names 
									</a>
								</li>
								 -->
							</ul>
						<?php } ?>
					</ul>
				</fieldset>
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
					<form id="colleditform" name="colleditform" action="collprofiles.php" method="post" onsubmit="return verifyCollEditForm(this)">
						<fieldset style="background-color:#FFF380;">
							<legend><b><?php echo ($newCollRec?'Add New':'Edit'); ?> Collection Information</b></legend>
							<table style="width:100%;">
								<tr>
									<td>
										Institution Code:
									</td>
									<td>
										<input type="text" name="institutioncode" value="<?php echo ($collId?$collData["institutioncode"]:'');?>" style="width:75px;" />
										<a href="#" onclick="return dwcDoc('institutionCode')">
											<img class="dwcimg" src="../../images/qmark.png" style="width:12px;" />
										</a>
									</td>
								</tr>
								<tr>
									<td>
										Collection Code:
									</td>
									<td>
										<input type="text" name="collectioncode" value="<?php echo ($collId?$collData["collectioncode"]:'');?>" style="width:75px;" />
										<a href="#" onclick="return dwcDoc('collectionCode')">
											<img class="dwcimg" src="../../images/qmark.png" style="width:12px;" />
										</a>
									</td>
								</tr>
								<tr>
									<td>
										Collection Name: 
									</td>
									<td>
										<input type="text" name="collectionname" value="<?php echo ($collId?$collData["collectionname"]:'');?>" style="width:350px;" />
									</td>
								</tr>
								<tr>
									<td>
										Mailing Address: 
									</td>
									<td>
										<?php 
										$instArr = $collManager->getInstitutionArr();
										?>
										<select name="iid" style="width:450px;">
											<option value="">Select Institution</option>
											<option value="">-----------------------</option>
											<?php 
											foreach($instArr as $iid => $name){
												echo '<option value="'.$iid.'" '.($collId && $collData["iid"] == $iid?'SELECTED':'').'>'.$name.'</option>';
											}
											?>
										</select>
										<?php
										if($collId && $collData["iid"]){ 
											?>
											<span>
												<a href="../admin/institutioneditor.php?iid=<?php echo $collData["iid"]; ?>" target="_blank" title="Edit institution currently linked to this collection">
													<img src="../../images/edit.png" style="width:15px;" />
												</a>
											</span>
											<?php 
										}
										?>
										<span>
											<a href="../admin/institutioneditor.php?emode=1&instcode=<?php echo ($collId?$collData["collectioncode"]:''); ?>" target="_blank" title="Add a New Institution">
												<img src="../../images/add.png" style="width:15px;" />
											</a>
										</span>
									</td>
								</tr>
								<tr>
									<td>
										Brief Description<br/> 
										(300 character max): 
									</td>
									<td>
										<textarea name="briefdescription" style="width:95%;height:60px;"><?php echo ($collId?$collData["briefdescription"]:'');?></textarea>
									</td>
								</tr>
								<tr>
									<td>
										Full Description<br/>
										(1000 character max): 
									</td>
									<td>
										<textarea name="fulldescription" style="width:95%;height:90px;"><?php echo ($collId?$collData["fulldescription"]:'');?></textarea>
									</td>
								</tr>
								<tr>
									<td>
										Homepage:
									</td>
									<td>
										<input type="text" name="homepage" value="<?php echo ($collId?$collData["homepage"]:'');?>" style="width:350;" />
									</td>
								</tr>
								<tr>
									<td>
									Contact: 
										</td>
									<td>
										<input type="text" name="contact" value="<?php echo ($collId?$collData["contact"]:'');?>" style="width:350;" />
									</td>
								</tr>
								<tr>
									<td>
										Email:
									</td>
									<td>
										<input type="text" name="email" value="<?php echo ($collId?$collData["email"]:'');?>" style="width:350;" />
									</td>
								</tr>
								<tr>
									<td>
										Latitude:
									</td>
									<td>
										<input id="latdec" type="text" name="latitudedecimal" value="<?php echo ($collId?$collData["latitudedecimal"]:'');?>" />
										<span style="cursor:pointer;" onclick="openMappingAid();">
											<img src="../../images/world40.gif" style="width:12px;" />
										</span>
									</td>
								</tr>
								<tr>
									<td>
										Longitude:
									</td>
									<td>
										<input id="lngdec" type="text" name="longitudedecimal" value="<?php echo ($collId?$collData["longitudedecimal"]:'');?>" />
									</td>
								</tr>
								<tr>
									<td>
										Allow Public Edits:
									</td>
									<td>
										<input type="checkbox" name="publicedits" value="1" <?php echo ($collData && $collData['publicedits']?'CHECKED':''); ?> />
									</td>
								</tr>
								<tr>
									<td>
										Rights:
									</td>
									<td>
										<input type="text" name="rights" value="<?php echo ($collId?$collData["rights"]:'');?>" style="width:350px;" />
										<a href="#" onclick="return dwcDoc('dcterms:rights')">
											<img class="dwcimg" src="../../images/qmark.png" style="width:12px;" />
										</a>
									</td>
								</tr>
								<tr>
									<td>
										Rights Holder:
									</td>
									<td>
										<input type="text" name="rightsholder" value="<?php echo ($collId?$collData["rightsholder"]:'');?>" style="width:350px;" />
										<a href="#" onclick="return dwcDoc('dcterms:rightsHolder')">
											<img class="dwcimg" src="../../images/qmark.png" style="width:12px;" />
										</a>
									</td>
								</tr>
								<tr>
									<td>
										Access Rights:
									</td>
									<td>
										<input type="text" name="accessrights" value="<?php echo ($collId?$collData["accessrights"]:'');?>" style="width:350px;" />
										<a href="#" onclick="return dwcDoc('dcterms:accessRights')">
											<img class="dwcimg" src="../../images/qmark.png" style="width:12px;" />
										</a>
									</td>
								</tr>
								<?php 
								if($isAdmin){ 
									?>
									<tr>
										<td>
											Collection Type:
										</td>
										<td>
											<select name="colltype">
												<option>Preserved Specimens</option>
												<option <?php echo ($collId && $collData["colltype"]=='Observations'?'SELECTED':''); ?>>Observations</option>
												<option <?php echo ($collId && $collData["colltype"]=='General Observations'?'SELECTED':''); ?>>General Observations</option>
											</select>
										</td>
									</tr>
									<tr>
										<td>
											Management:
										</td>
										<td>
											<select name="managementtype">
												<option>Snapshot</option>
												<option <?php echo ($collId && $collData["managementtype"]=='Live Data'?'SELECTED':''); ?>>Live Data</option>
											</select>
										</td>
									</tr>
									<tr>
										<td>
											Icon URL:
										</td>
										<td>
											<input type="text" name="icon" style="width:350px;" value="<?php echo ($collId?$collData["icon"]:'');?>" title="Small url representing the collection" />
										</td>
									</tr>
									<tr>
										<td>
											Source Record URL:
										</td>
										<td>
											<input type="text" name="individualurl" style="width:350px;" value="<?php echo ($collId?$collData["individualurl"]:'');?>" title="Dynamic link to source database individual record page" />
										</td>
									</tr>
									<tr>
										<td>
											Sort Sequence:
										</td>
										<td>
											<input type="text" name="sortseq" value="<?php echo ($collId?$collData["sortseq"]:'');?>" />
										</td>
									</tr>
									<?php 
								} 
								?>
								<tr>
									<td colspan="2">
										<div style="margin:20px;">
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
									</td>
								</tr>
							</table>
						</fieldset>
					</form>
				</div>
				<?php
			}
			if(!$newCollRec){
				?>
				<div style='margin:10px;'>
					<div><?php 
					if($collData["fulldescription"]){
						echo $collData["fulldescription"];
					}
					else{
						echo $collData["briefdescription"];
					}	
					?></div>
					<div style='margin-top:5px;'>
						<b>Contact:</b> <?php echo $collData["contact"]." (".str_replace("@","&lt;at&gt;",$collData["email"]);?>)
					</div>
					<?php 
					if($collData["homepage"]){
						?>
						<div style="margin-top:5px;">
							<b>Home Page:</b> 
							<a href="<?php echo $collData["homepage"]; ?>">
								<?php echo $collData["homepage"]; ?>
							</a>
						</div>
						<?php 
					}
					?>
					<div style="margin-top:5px;"> 
						<b>Management: </b> 
						<?php 
						if(stripos($collData['managementtype'],'live') !== false){
							echo 'Live Data managed directly within data portal';
						}
						else{
							echo 'Data snapshot of central database ';
							echo '<div style="margin-top:5px;"><b>Last Update:</b> '.$collData['uploaddate'].'</div>';
						}
						?>
					</div>
	 				<?php 
	 				if($collData["institutionname"]){ 
	 					?>
						<div style="float:left;font-weight:bold;margin-top:5px;">Address:&nbsp;</div>
						<div style="float:left;margin-top:5px;">
							<?php 
							echo "<div>".$collData["institutionname"].($collData["institutioncode"]?" (".$collData["institutioncode"].")":"")."</div>";
							if($collData["address1"]) echo "<div>".$collData["address1"]."</div>";
							if($collData["address2"]) echo "<div>".$collData["address2"]."</div>";
							if($collData["city"]) echo "<div>".$collData["city"].", ".$collData["stateprovince"]."&nbsp;&nbsp;&nbsp;".$collData["postalcode"]."</div>";
							if($collData["country"]) echo "<div>".$collData["country"]."</div>";
							if($collData["phone"]) echo "<div>".$collData["phone"]."</div>";
							?>
						</div>
						<?php 
	 				} 
	 				?>
	 				<div style="clear:both;">&nbsp;</div>
					<div style="clear:both;">
						<div style="font-weight:bold;">Collection Statistics</div>
						<ul>
							<li><?php echo $collData["recordcnt"];?> specimens in database</li>
							<li><?php echo $collData["georefcnt"]." (".(round(100*$collData["georefcnt"]/($collData["recordcnt"]?$collData["recordcnt"]:1)));?>%) georeferenced</li>
							<li><?php echo $collData["familycnt"];?> families</li>
							<li><?php echo $collData["genuscnt"];?> genera</li>
							<li><?php echo $collData["speciescnt"];?> species</li>
						</ul>
					</div>
				</div>
				<div style='margin:20px 0px 20px 20px;width:200px;background-color:#FFFFCC;' class='fieldset'>
					<div class='legend'><b>Extra Statistics</b></div>
					<div>
						<a href='collprofiles.php?collid=<?php echo $collId;?>&sfl=1'>
							Show Family Distribution
						</a>
					</div>
					<div>
						<a href='collprofiles.php?collid=<?php echo $collId;?>&sgl=1'>
							Show Geographic Distribution
						</a>
					</div>
				</div>
				<?php
				if($showFamilyList || $showGeographicList){
					?>
					<fieldset style="margin:20px;width:90%;">
						<legend>
							<b>
								<?php 
								if($showFamilyList){
									echo 'Family Distribution';
									if($familyDist){
										echo ' - '.$familyDist;
									}
								}
								else{
									echo 'Geographic Distribution';
									if($countryDist){
										echo ' - '.$countryDist;
									}
									elseif($stateDist){
										echo ' - '.$stateDist;
									}
								}
								?>
							</b>
						</legend>
						<ul>
							<?php 
							$distArr = array();
							if($showFamilyList){
								$distArr = $collManager->getTaxonCounts();
							}
							else{
								$distArr = $collManager->getGeographicCounts($countryDist,$stateDist);
							}
							foreach($distArr as $term => $cnt){
								$percentage = round(100*$cnt/$collData["recordcnt"]);
								echo '<li>';
								if($showGeographicList && !$stateDist){
									echo '<a href="collprofiles.php?sgl=1&collid='.$collId.($countryDist?'&state=':'&country=').$term.'">';
									echo $term;
									echo '</a>';
								}
								elseif($showFamilyList && !$familyDist){
									//echo '<a href="collprofiles.php?sfl=1&collid='.$collId.'&family='.$term.'">';
									echo $term;
									//echo '</a>';
								}
								else{
									echo $term;
								}
								echo ' ('.$cnt.($percentage > 10?', '.$percentage.'%':'').')';
								echo '</li>';
							}
							?>
						</ul>
						<?php 
							if(!$stateDist && !$familyDist) echo '*Clicking on term in list will display distributions within that term';
						?>
					</fieldset>
					<?php 
				}
			}
		}
		else{
			$collList = $collManager->getCollectionList();
			if($isAdmin){
				?>
				<div style="float:right;">
					<a href="collprofiles.php?newcoll=1">
						<img src="../../images/add.png" title="Add a brand new collection profile to portal" />
					</a>
				</div>
				<?php
			} 
			?>
			<h1><?php echo $defaultTitle; ?> Collections </h1>
			<div style='margin:10px;clear:both;'>
				Select a collection to see full details. 
			</div>
			<table style='margin:10px;'>
				<?php 
				foreach($collList as $cId => $collArr){
					?>
					<tr>
						<td style='text-align:center;vertical-align:top;'>
							<?php 
							$iconStr = $collArr['icon'];
							if(substr($iconStr,0,6) == 'images') $iconStr = '../../'.$iconStr; 
							?>
							<img src='<?php echo $iconStr; ?>' style='border-size:1px;height:30;width:30;' /><br/>
							<?php echo $collArr['institutioncode']; ?>
						</td>
						<td>
							<h3>
								<a href='collprofiles.php?collid=<?php echo $cId;?>'>
									<?php echo $collArr['collectionname']; ?>
								</a>
							</h3>
							<div style='margin:10px;'>
								<div><?php echo $collArr['fulldescription']; ?></div>
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