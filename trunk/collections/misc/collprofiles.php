<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/CollectionProfileManager.php');
header("Content-Type: text/html; charset=".$charset);

$collid = ((array_key_exists("collid",$_REQUEST) && is_numeric($_REQUEST["collid"]))?$_REQUEST["collid"]:0);
$action = array_key_exists("action",$_REQUEST)?htmlspecialchars($_REQUEST["action"]):"";
$eMode = array_key_exists('emode',$_REQUEST)?htmlspecialchars($_REQUEST['emode']):0;

if($eMode && !$SYMB_UID){
	header('Location: ../../profile/index.php?refurl=../collections/misc/collprofiles.php?'.$_SERVER['QUERY_STRING']);
}

$countryDist = array_key_exists('country',$_REQUEST)?htmlspecialchars($_REQUEST['country']):'';
$stateDist = array_key_exists('state',$_REQUEST)?htmlspecialchars($_REQUEST['state']):'';

$collManager = new CollectionProfileManager();
if(!$collManager->setCollid($collid)) $collid = '';

$collData = $collManager->getCollectionData();

$editCode = 0;		//0 = no permissions; 1 = CollEditor; 2 = CollAdmin; 3 = SuperAdmin
if($SYMB_UID){
	if($IS_ADMIN){
		$editCode = 3;
	}
	else if($collid){
		if(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])){
			$editCode = 2;
		}
		elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollEditor"])){
			$editCode = 1;
		}
	}
}

?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE." ".($collid?$collData["collectionname"]:"") ; ?> Collection Profiles</title>
	<link href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<meta name="keywords" content="Natural history collections,<?php echo ($collid?$collData["collectionname"]:""); ?>" />
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

		function toggleFamilyDist(){
			toggleById("famdistbox");
			toggleById("showfamdist");
			toggleById("hidefamdist");

			document.getElementById("geodistbox").style.display="none";
			document.getElementById("showgeodist").style.display="block";
			document.getElementById("hidegeodist").style.display="none";
			return false;
		}

		function toggleGeoDist(){
			toggleById("geodistbox");
			toggleById("showgeodist");
			toggleById("hidegeodist");

			document.getElementById("famdistbox").style.display="none";
			document.getElementById("showfamdist").style.display="block";
			document.getElementById("hidefamdist").style.display="none";
			return false;
		}
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_misc_collprofilesMenu)?$collections_misc_collprofilesMenu:true);
	include($SERVER_ROOT.'/header.php');
	echo "<div class='navpath'>";
	if(isset($collections_misc_collprofilesCrumbs)){
		if($collections_misc_collprofilesCrumbs){
			echo "<a href='../../index.php'>Home</a> &gt;&gt; ";
			echo $collections_misc_collprofilesCrumbs.' &gt;&gt; ';
			echo "<b>".($collData?$collData["collectionname"]:"Collection Profiles")." Details</b>";
		}
	}
	else{
		echo "<a href='../../index.php'>Home</a> &gt;&gt; ";
		echo "<a href='../index.php'>Collection Search Page</a> &gt;&gt; ";
		echo "<b>".($collData?$collData["collectionname"]:"Collection Profiles")." Details</b>";
	}
	echo "</div>";
	?>

	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($editCode > 1){
			if($action == 'UpdateStatistics'){
				echo '<h2>Updating statistics related to this collection...</h2>';
				$collManager->updateStatistics(true);
				echo '<hr/>';
				$collData = $collManager->getCollectionData();
			}
		}
		if($editCode > 0 && $collid){
			?>
			<div style="float:right;margin:3px;cursor:pointer;" onclick="toggleById('controlpanel');" title="Toggle Manager's Control Panel">
				<img style='border:0px;' src='../../images/edit.png' />
			</div>
			<?php
		}
		if($collid){
			$extrastatsArr = Array();
			$famArr = Array();
			$countryArr = Array();
			$georefPerc = 0;
			$spidPerc = 0;
			$imgPerc = 0;
			if($collData['georefcnt']&&$collData['recordcnt']){
				$georefPerc = (100*($collData['georefcnt']/$collData['recordcnt']));
			}
			else{
				$georefPerc = 0;
			}
			if($collData['dynamicProperties']){
				$extrastatsArr = json_decode($collData['dynamicProperties'],true);
				if(is_array($extrastatsArr)){
					if(array_key_exists("families",$extrastatsArr)){
						$famArr = $extrastatsArr['families'];
					}
					if(array_key_exists("countries",$extrastatsArr)){
						$countryArr = $extrastatsArr['countries'];
					}
					if($extrastatsArr['SpecimensCountID']){
						$spidPerc = (100*($extrastatsArr['SpecimensCountID']/$collData['recordcnt']));
					}
					if($extrastatsArr['imgcnt']){
						$imgPerc = (100*($extrastatsArr['imgcnt']/$collData['recordcnt']));
					}
				}
			}
			$codeStr = ' ('.$collData['institutioncode'];
			if($collData['collectioncode']) $codeStr .= '-'.$collData['collectioncode'];
			$codeStr .= ')';
			echo '<h1>'.$collData['collectionname'].$codeStr.'</h1>';
			if($editCode > 0){
				?>
				<div id="controlpanel" style="clear:both;display:<?php echo ($eMode?'block':'none'); ?>;">
					<fieldset style="padding:10px;padding-left:25px;">
						<legend><b>Data Editor Control Panel</b></legend>
						<ul>
							<?php
							if(stripos($collData['colltype'],'observation') !== false){
								?>
								<li>
									<a href="../editor/observationsubmit.php?collid=<?php echo $collid; ?>">
										Submit an Image Voucher (observation supported by a photo)
									</a>
								</li>
								<?php
							}
							?>
							<li>
								<a href="../editor/occurrenceeditor.php?gotomode=1&collid=<?php echo $collid; ?>">
									Add New Occurrence Record
								</a>
							</li>
							<?php
							if($collData['colltype'] == 'Preserved Specimens'){
								?>
								<li style="margin-left:10px">
									<a href="../editor/imageoccursubmit.php?collid=<?php echo $collid; ?>">
										Create New Records Using Image
									</a>
								</li>
								<li style="margin-left:10px">
									<a href="../editor/skeletalsubmit.php?collid=<?php echo $collid; ?>">
										Add Skeletal Records
									</a>
								</li>
								<?php
							}
							?>
							<li>
								<a href="../editor/occurrenceeditor.php?collid=<?php echo $collid; ?>">
									Edit Existing Occurrence Records
								</a>
							</li>
							<li>
								<a href="../editor/batchdeterminations.php?collid=<?php echo $collid; ?>">
									Add Batch Determinations/Nomenclatural Adjustments
								</a>
							</li>
							<?php
							if($collData['colltype'] == 'Preserved Specimens'){
								?>
								<li>
									<a href="../reports/labelmanager.php?collid=<?php echo $collid; ?>">
										Print Labels/Annotations
									</a>
								</li>
								<?php
							}
							?>
							<li>
								<a href="../georef/batchgeoreftool.php?collid=<?php echo $collid; ?>">
									Batch Georeference Specimens
								</a>
							</li>
							<?php
							if($collData['colltype'] == 'Preserved Specimens'){
								?>
								<li>
									<a href="../loans/index.php?collid=<?php echo $collid; ?>">
										Loan Management
									</a>
								</li>
								<?php
							}
							?>
						</ul>
					</fieldset>
					<?php
					if($editCode > 1){
						?>
						<fieldset style="padding:10px;padding-left:25px;">
							<legend><b>Administration Control Panel</b></legend>
							<ul>
								<li>
									<a href="collmetadata.php?collid=<?php echo $collid; ?>" >
										Edit Metadata and Contact Information
									</a>
								</li>
								<li>
									<a href="collprofiles.php?collid=<?php echo $collid; ?>&action=UpdateStatistics" >
										Update Statistics
									</a>
								</li>
								<li>
									<a href="collpermissions.php?collid=<?php echo $collid; ?>" >
										Manage Permissions
									</a>
								</li>
								<li>
									<a href="../admin/specuploadmanagement.php?collid=<?php echo $collid; ?>">
										Import/Update Specimen Records
									</a>
								</li>
								<?php
								if($collData['managementtype'] == 'Live Data'){
									?>
									<li style="margin-left:10px;">
										<a href="../admin/specupload.php?uploadtype=3&collid=<?php echo $collid; ?>">
											Quick File Upload
										</a>
									</li>
									<?php
								}
								?>
								<li style="margin-left:10px;">
									<a href="../admin/specupload.php?uploadtype=7&collid=<?php echo $collid; ?>">
										Skeletal File Upload
									</a>
								</li>
								<?php 
								if($collData['managementtype'] != 'Aggregate'){
									?>
									<li>
										<a href="../specprocessor/index.php?collid=<?php echo $collid; ?>">
											Processing Toolbox
										</a>
									</li>
									<li>
										<a href="../datasets/datapublisher.php?collid=<?php echo $collid; ?>">
											Darwin Core Archive Publishing
										</a>
									</li>
									<?php
								}
								?>
								<li>
									<a href="../editor/editreviewer.php?collid=<?php echo $collid; ?>">
										Review/Verify General Specimen Edits
									</a>
								</li>
								<li>
									<a href="../cleaning/occurrencecleaner.php?obsuid=0&collid=<?php echo $collid; ?>">
										Data Cleaning Tools
									</a>
								</li>
							<li>
								<a href="../datasets/duplicatemanager.php?collid=<?php echo $collid; ?>">
									Duplicate Clustering
								</a>
							</li>
							<li>
								<a href="#" onclick="newWindow = window.open('collbackup.php?collid=<?php echo $collid; ?>','bucollid','scrollbars=1,toolbar=1,resizable=1,width=400,height=200,left=20,top=20');">
									Download Backup Data File
								</a>
							</li>
							</ul>
						</fieldset>
						<?php
					}
					?>
				</div>
				<?php
			}
			?>
			<div style='margin:10px;'>
				<div>
					<?php echo $collData["fulldescription"]; ?>
				</div>
				<div style='margin-top:5px;'>
					<b>Contact:</b> <?php echo $collData["contact"].($collData["email"]?" (".str_replace("@","&#64;",$collData["email"]).")":"");?>
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
					<b>Collection Type: </b>
					<?php
					if($collData['colltype']){
						echo $collData['colltype'];
					}
					?>
				</div>
				<div style="margin-top:5px;">
					<b>Management: </b>
					<?php
					if($collData['managementtype'] == 'Live Data'){
						echo 'Live Data managed directly within data portal';
					}
					else{
						if($collData['managementtype'] == 'Aggregate'){
							echo 'Data harvested from a data aggregator';
						}
						else{
							echo 'Data snapshot of local collection database ';
						}
						echo '<div style="margin-top:5px;"><b>Last Update:</b> '.$collData['uploaddate'].'</div>';
					}
					?>
				</div>
				<?php
				if(stripos($collData['managementtype'],'live') !== false){
					?>
					<div style="margin-top:5px;">
						<b>Global Unique Identifier: </b>
						<?php
						echo ($collid?$collData['guid']:'');
						?>
					</div>
					<?php
				}
				?>
				<div style="margin-top:5px;">
					<b>Usage Rights:</b>
					<?php
					if($collid && $collData['rights']){
						$rights = $collData['rights'];
						$rightsUrl = '';
						if(substr($rights,0,4) == 'http'){
							$rightsUrl = $rights;
							if($rightsTerms){
								if($rightsArr = array_keys($rightsTerms,$rights)){
									$rights = current($rightsArr);
								}
							}
						}
						if($rightsUrl) echo '<a href="'.$rightsUrl.'" target="_blank">';
						echo $rights;
						if($rightsUrl) echo '</a>';
					}
					elseif(file_exists('../../misc/usagepolicy.php')){
						echo '<a href="../../misc/usagepolicy.php" target="_blank">default policy</a>';
					}
					?>
				</div>
 				<?php
 				if($collid && $collData['rightsholder']){
 					?>
					<div style="margin-top:5px;">
						<b>Rights Holder:</b>
						<?php
						echo $collData['rightsholder'];
						?>
					</div>
 					<?php
 				}
 				if($collid && $collData['accessrights']){
 					?>
					<div style="margin-top:5px;">
						<b>Access Rights:</b>
						<?php
						echo $collData['accessrights'];
						?>
					</div>
 					<?php
 				}
 				$addrArresses = $collManager->getAddresses();
 				if($addrArresses){
 					foreach($addrArresses as $iid => $addrArr){
	 					?>
						<div style="font-weight:bold;margin-top:5px;">Address:</div>
						<div style="margin-top:5px;">
							<?php
							echo "<div>".$addrArr["institutionname"];
							if($editCode > 1) echo ' <a href="../admin/institutioneditor.php?emode=1&targetcollid='.$collid.'&iid='.$iid.'" title="Edit institution information"><img src="../../images/edit.png" style="width:13px;" /></a>';
							echo '</div>';
							if($addrArr["address1"]) echo "<div>".$addrArr["address1"]."</div>";
							if($addrArr["address2"]) echo "<div>".$addrArr["address2"]."</div>";
							if($addrArr["city"]) echo "<div>".$addrArr["city"].", ".$addrArr["stateprovince"]."&nbsp;&nbsp;&nbsp;".$addrArr["postalcode"]."</div>";
							if($addrArr["country"]) echo "<div>".$addrArr["country"]."</div>";
							if($addrArr["phone"]) echo "<div>".$addrArr["phone"]."</div>";
							if($addrArr["url"]) echo '<div><a href="'.$addrArr['url'].'">'.$addrArr['url'].'</a></div>';
							if($addrArr["notes"]) echo "<div>".$addrArr["notes"]."</div>";
							?>
						</div>
						<?php
 					}
 				}
 				?>
				<div style="clear:both;margin-top:5px;">
					<div style="font-weight:bold;">Collection Statistics:</div>
					<ul style="margin-top:5px;">
						<li><?php echo $collData["recordcnt"];?> specimen records</li>
						<li><?php echo ($collData['georefcnt']?$collData['georefcnt']:0).($georefPerc?" (".($georefPerc>1?round($georefPerc):round($georefPerc,2))."%)":'');?> georeferenced</li>
						<?php
						if($extrastatsArr&&$extrastatsArr['imgcnt']) echo '<li>'.($extrastatsArr['imgcnt']?$extrastatsArr['imgcnt']:0).($imgPerc?" (".($imgPerc>1?round($imgPerc):round($imgPerc,2))."%)":'').' with images</li>';
						if($extrastatsArr&&$extrastatsArr['gencnt']) echo '<li>'.$extrastatsArr['gencnt'].' GenBank references</li>';
						if($extrastatsArr&&$extrastatsArr['boldcnt']) echo '<li>'.$extrastatsArr['boldcnt'].' BOLD references</li>';
						if($extrastatsArr&&$extrastatsArr['refcnt']) echo '<li>'.$extrastatsArr['refcnt'].' publication references</li>';
						if($extrastatsArr&&$extrastatsArr['SpecimensCountID']) echo '<li>'.($extrastatsArr['SpecimensCountID']?$extrastatsArr['SpecimensCountID']:0).($spidPerc?" (".($spidPerc>1?round($spidPerc):round($spidPerc,2))."%)":'').' identified to species</li>';
						?>
						<li><?php echo $collData["familycnt"];?> families</li>
						<li><?php echo $collData["genuscnt"];?> genera</li>
						<li><?php echo $collData["speciescnt"];?> species</li>
						<?php
						if($extrastatsArr&&$extrastatsArr['TotalTaxaCount']) echo '<li>'.$extrastatsArr['TotalTaxaCount'].' total taxa (including subsp. and var.)</li>';
						if($extrastatsArr&&$extrastatsArr['TypeCount']) echo '<li>'.$extrastatsArr['TypeCount'].' type specimens</li>';
						?>
					</ul>
				</div>
			</div>
			<?php
			if($extrastatsArr){
				?>
				<fieldset style='margin:20px;width:300px;background-color:#FFFFCC;'>
					<legend><b>Extra Statistics</b></legend>
					<form name="statscsv" id="statscsv" action="collstatscsv.php" method="post" onsubmit="">
						<div style="">
							<div id="showfamdist" style="float:left;display:block;" >
								<a href="#" onclick="return toggleFamilyDist()">Show Family Distribution</a>
							</div>
							<div id="hidefamdist" style="float:left;display:none;" >
								<a href="#" onclick="return toggleFamilyDist()">Hide Family Distribution</a>
							</div>
							<div style='float:left;margin-left:6px;width:16px;height:16px;padding:2px;' title="Save CSV">
								<input type="image" name="action" value="Download Family Dist" src="../../images/dl.png" onclick="" />
							</div>
						</div>
						<div style="clear:both;">
							<div id="showgeodist" style="float:left;display:block;" >
								<a href="#" onclick="return toggleGeoDist()">Show Geographic Distribution</a>
							</div>
							<div id="hidegeodist" style="float:left;display:none;" >
								<a href="#" onclick="return toggleGeoDist()">Hide Geographic Distribution</a>
							</div>
							<div style='float:left;margin-left:6px;width:16px;height:16px;padding:2px;' title="Save CSV">
								<input type="image" name="action" value="Download Geo Dist" src="../../images/dl.png" onclick="" />
							</div>
						</div>
						<input type="hidden" name="famarrjson" id="famarrjson" value='<?php echo json_encode($famArr); ?>' />
						<input type="hidden" name="geoarrjson" id="geoarrjson" value='<?php echo json_encode($countryArr); ?>' />
					</form>
				</fieldset>
				<div style="clear:both;"> </div>
				<?php
			}
			if($countryDist || $stateDist){
				?>
				<fieldset style="margin:20px;width:90%;">
					<legend>
						<b>
							<?php
							echo 'Geographic Distribution';
							if($countryDist){
								echo ' - '.$countryDist;
							}
							elseif($stateDist){
								echo ' - '.$stateDist;
							}
							?>
						</b>
					</legend>
					<div style="margin:15px;">Click on the specimen record counts within the parenthesis to return the records for that term</div>
					<ul>
						<?php
						$distArr = array();
						$distArr = $collManager->getGeographicCounts($countryDist,$stateDist);
						foreach($distArr as $term => $cnt){
							echo '<li>';
							$colTarget = 'county';
							if(!$stateDist){
								echo '<a href="collprofiles.php?sgl=1&collid='.$collid.($countryDist?'&state=':'&country=').$term.'">';
								echo $term;
								echo '</a>';
								$colTarget = 'country';
								if($countryDist) $colTarget = 'state';
								echo ' (<a href="../list.php?db[]='.$collid.'&reset=1&'.$colTarget.'='.$term.'" target="_blank">'.$cnt.'</a>)';
							}
							else{
								echo $term;
								echo ' (<a href="../list.php?db[]='.$collid.'&reset=1&'.$colTarget.'='.$term.'" target="_blank">'.$cnt.'</a>)';
							}
							echo '</li>';
						}
						?>
					</ul>
					<?php
						echo '*Clicking on name in list will display distributions within';
					?>
				</fieldset>
				<?php
			}
			?>
			<fieldset id="famdistbox" style="clear:both;margin-top:15px;width:800px;display:none;">
				<legend><b>Family Distribution</b></legend>
				<div style="margin:15px;">Click on the specimen record counts within the parenthesis to return the records for that family</div>
				<ul>
					<?php
					foreach($famArr as $name => $data){
						echo '<li>';
						echo $name;
						echo ' (<a href="../list.php?usecookies=false&db[]='.$collid.'&type=1&reset=1&taxa='.$name.'" target="_blank">'.$data['SpecimensPerFamily'].'</a>)';
						echo '</li>';
					}
					?>
				</ul>
			</fieldset>
			<fieldset id="geodistbox" style="margin-top:15px;width:800px;display:none;">
				<legend><b>Geographic Distribution - Countries</b></legend>
				<div style="margin:15px;">Click on the specimen record counts within the parenthesis to return the records for that country</div>
				<ul>
					<?php
					foreach($countryArr as $name => $data){
						echo '<li>';
						echo '<a href="collprofiles.php?sgl=1&collid='.$collid.'&country='.$name.'">';
						echo $name;
						echo '</a>';
						echo ' (<a href="../list.php?usecookies=false&db[]='.$collid.'&reset=1&country='.$name.'" target="_blank">'.$data['CountryCount'].'</a>)';
						echo '</li>';
					}
					?>
				</ul>
			</fieldset>
			<?php
		}
		else{
			$collList = $collManager->getCollectionList();
			?>
			<h1><?php echo $DEFAULT_TITLE; ?> Collections </h1>
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
							if($iconStr){
								if(substr($iconStr,0,6) == 'images') $iconStr = '../../'.$iconStr;
								?>
								<img src='<?php echo $iconStr; ?>' style='border-size:1px;height:30;width:30;' /><br/>
								<?php
								echo $collArr['institutioncode'];
								if($collArr['collectioncode']) echo '-'.$collArr['collectioncode'];
							}
							?>
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
									<?php echo $collArr['contact'].' ('.str_replace('@','&#64;',$collArr['email']).')';?>
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
	include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>