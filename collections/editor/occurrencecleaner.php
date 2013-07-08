<?php
include_once('../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceCleaner.php');
header("Content-Type: text/html; charset=".$charset);

$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$obsUid = array_key_exists('obsuid',$_REQUEST)?$_REQUEST['obsuid']:'';
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

if(!$symbUid){
	header('Location: ../../profile/index.php?refurl=../collections/editor/occurrencecleaner.php?'.$_SERVER['QUERY_STRING']);
}

$cleanManager = new OccurrenceCleaner();
if($collId) $cleanManager->setCollId($collId);
$collMap = $cleanManager->getCollMap();

$statusStr = '';
$isEditor = 0; 
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))
	|| ($collMap['colltype'] == 'General Observations')){
	$isEditor = 1;
}

//If collection is a general observation project, limit to User
if($collMap['colltype'] == 'General Observations' && $obsUid !== 0){
	$obsUid = $symbUid;
	$cleanManager->setObsUid($obsUid);
}

$dupArr = array();
if($action == 'listdupscatalog'){
	$dupArr = $cleanManager->getDuplicateCatalogNumber();
	$action = 'listdups';
}
elseif($action == 'listdupsrecordedby'){
	$dupArr = $cleanManager->getDuplicateCollectorNumber();
	$action = 'listdups';
}

if($isEditor && $formSubmit){
	if($formSubmit == 'clusteredit'){
		$statusStr = $cleanManager->editDuplicateCluster($_POST['dupid'],$_POST['title'],$_POST['description'],$_POST['notes']);
	}
	elseif($formSubmit == 'clusterdelete'){
		$statusStr = $cleanManager->deleteDuplicateCluster($_POST['deldupid']);
	}
	elseif($formSubmit == 'occdelete'){
		$statusStr = $cleanManager->deleteOccurFromCluster($_POST['dupid'],$_POST['occid']);
	}
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Occurrence Cleaner</title>
    <link type="text/css" href="../../css/main.css" rel="stylesheet" />
    <style type="text/css">
		table.styledtable td { white-space: nowrap; }
    </style>
	<script type="text/javascript">
		function validateMergeForm(f){
			var dbElements = document.getElementsByName("dupid[]");
			for(i = 0; i < dbElements.length; i++){
				var dbElement = dbElements[i];
				if(dbElement.checked) return true;
			}
		   	alert("Please select specimens to be merged!");
	      	return false;
		}

		function selectAllDuplicates(f){
			var boxesChecked = true;
			if(!f.selectalldupes.checked){
				boxesChecked = false;
			}
			var dbElements = document.getElementsByName("dupid[]");
			for(i = 0; i < dbElements.length; i++){
				dbElements[i].checked = boxesChecked;
			}

		}

		function verifyEditForm(f){
			if(f.title == ""){
				alert("Title field must have a value");
				return false;
			}
			return true;
		}

		function openOccurPopup(occid) {
			occWindow=open("../individual/index.php?occid="+occid,"occwin"+occid,"resizable=1,scrollbars=1,toolbar=1,width=750,height=600,left=20,top=20");
			if(occWindow.opener == null) occWindow.opener = self;
		}

		function toggle(target){
			var ele = document.getElementById(target);
			if(ele){
				if(ele.style.display=="block"){
					ele.style.display="none";
		  		}
			 	else {
			 		ele.style.display="block";
			 	}
			}
			else{
				var divObjs = document.getElementsByTagName("div");
			  	for (i = 0; i < divObjs.length; i++) {
			  		var divObj = divObjs[i];
			  		if(divObj.getAttribute("class") == target || divObj.getAttribute("className") == target){
						if(divObj.style.display=="none"){
							divObj.style.display="inline";
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
<body <?php echo (($dupArr)?'style="margin-left: 0px; margin-right: 0px;"':'');?>>
	<?php 	
	$displayLeftMenu = false;
	if(!$dupArr){
		include($serverRoot.'/header.php');
	}
	if(isset($collections_editor_occurrencecleanerCrumbs)){
		if($collections_editor_occurrencecleanerCrumbs){
			?>
			<div class="navpath">
				<a href='../../index.php'>Home</a> &gt;&gt; 
				<?php echo $collections_editor_occurrencecleanerCrumbs; ?>
				<b>Data Cleaning Module</b>
			</div>
			<?php 
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href="../../index.php">Home</a> &gt;&gt;
			<a href="../misc/collprofiles.php?collid=<?php echo $collId; ?>&emode=1">Collection Management</a> &gt;&gt;
			<a href="occurrencecleaner.php?collid=<?php echo $collId; ?>">Data Cleaning Module</a>
		</div>
		<?php
	}
	?>
	<!-- inner text -->
	<div id="innertext">
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="margin:20px;color:<?php echo (substr($statusStr,0,5)=='ERROR'?'red':'green');?>">
				<?php echo $statusStr; ?>
			</div>
			<hr/>
			<?php 
		} 
		if($isEditor){
			if(!$action){
				?>
				<fieldset style="padding:20px;">
					<legend><b>Duplicate Records</b></legend>
					<div>
						This function will query the collection for records with duplicate records within a collection. 
						Duplicates can be searched based on catalog numbers or collector/observer name and number. 
						Results will be listed in a table grouped by the catlog number or collector. Clicking on the number in the 
						left most column will open the editor for that record. Selecting the checkboxes for two or more 
						records within the groups and submitting the form will merge selected records. Select link below to 
						query database for possible duplicate records. Note that a maximun of 500 records will 
						be returned at a time.
					</div>
					<div style="margin:25px;font-weight:bold;font-size:120%;">
						<a href="occurrencecleaner.php?collid=<?php echo $collId.'&obsuid='.$obsUid; ?>&action=listdupscatalog">
							Search for duplicates based on <b>Catalog Numbers</b>
						</a>
					</div>
					<div style="margin:25px;font-weight:bold;font-size:120%;">
						<a href="occurrencecleaner.php?collid=<?php echo $collId.'&obsuid='.$obsUid; ?>&action=listdupsrecordedby">
							Search for duplicates based on <b>Collector/Observer and numbers</b>
						</a>
					</div>
				</fieldset>
<!-- 
				<fieldset style="padding:20px;">
					<legend><b>Taxonomic Names</b></legend>
						<div>
							This function will query the collection for specimen records with unmapped, possibly problematic taxonomic names.      
						</div>
						<div style="margin:25px;font-weight:bold;font-size:120%;">
							In development
						</div>
				</fieldset>

				<fieldset style="padding:20px;">
					<legend><b>Primary Collector</b></legend>
						<div>
							These tools are designed to aid collection manager in indexing primary collectors 
							to the collector/observer tables.      
						</div>
						<div style="margin:25px;font-weight:bold;font-size:120%;">
							In development
						</div>
				</fieldset>
 -->
				<fieldset style="padding:20px;">
					<legend><b>Duplicate Linkages</b></legend>
						<div>
						These tools aid collection managers in batch linking their specimen records to duplicate specimens existing 
						within other collections linked within the data portal. The main method of locating duplciates is by matching 
						the collector, collector number, and collection date.       
					</div>
					<div style="margin:25px;font-weight:bold;font-size:120%;">
						<a href="occurrencecleaner.php?collid=<?php echo $collId.'&obsuid='.$obsUid; ?>&action=listdupes">
							List linked duplicate clusters 
						</a>
					</div>
					<div style="margin:25px;font-weight:bold;font-size:120%;">
						<a href="occurrencecleaner.php?collid=<?php echo $collId.'&obsuid='.$obsUid; ?>&action=listdupeconflicts">
							List linked duplicate clusters with conflicted identifications 
						</a>
					</div>
					<div style="margin:25px;font-weight:bold;font-size:120%;">
						<a href="occurrencecleaner.php?collid=<?php echo $collId.'&obsuid='.$obsUid; ?>&action=batchlinkdupes">
							Start batch linking duplicates
						</a>
					</div>
				</fieldset>
				<?php
			}
			else{
				if($action == 'listdups'){
					//Look for duplicate catalognumbers 
					if($dupArr){
						$recCnt = count($dupArr);
						//Build table
						?>
						<form name="mergeform" action="occurrencecleaner.php" method="post" onsubmit="return validateMergeForm(this)">
							<div><b>Duplicate Record Count: <?php echo ($recCnt>500?'> 500':$recCnt); ?></b></div>
							<table class="styledtable">
								<tr>
									<th style="width:40px;">ID</th>
									<th style="width:20px;"><input name="selectalldupes" type="checkbox" title="Select/Deselect All" onclick="selectAllDuplicates(this.form)" /></th>
									<th style="width:40px;">Catalog Number</th>
									<th>Scientific Name</th>
									<th>Collector</th>
									<th>Collection Number</th>
									<th>Associated Collectors</th>
									<th>Collection Date</th>
									<th>Verbatim Date</th>
									<th>Country</th>
									<th>State</th>
									<th>County</th>
									<th>Locality</th>
								</tr>
								<?php 
								$setCnt = 0;
								foreach($dupArr as $k => $occArr){
									$setCnt++;
									echo '<tr '.(($setCnt % 2) == 1?'class="alt"':'').'>';
									echo '<td><a href="occurrenceeditor.php?occid='.$occArr['occid'].'" target="_blank">'.$occArr['occid'].'</a></td>'."\n";
									echo '<td><input name="dupid[]" type="checkbox" value="'.$occArr['catalognumber'].':'.$occArr['occid'].'" /></td>'."\n";
									echo '<td>'.$occArr['catalognumber'].'</td>'."\n";
									echo '<td>'.$occArr['sciname'].'</td>'."\n";
									echo '<td>'.$occArr['recordedBy'].'</td>'."\n";
									echo '<td>'.$occArr['recordNumber'].'</td>'."\n";
									echo '<td>'.$occArr['associatedCollectors'].'</td>'."\n";
									echo '<td>'.$occArr['eventDate'].'</td>'."\n";
									echo '<td>'.$occArr['verbatimEventDate'].'</td>'."\n";
									echo '<td>'.$occArr['country'].'</td>'."\n";
									echo '<td>'.$occArr['stateProvince'].'</td>'."\n";
									echo '<td>'.$occArr['county'].'</td>'."\n";
									echo '<td>'.$occArr['locality'].'</td>'."\n";
									echo '</tr>';
								}
								?>
							</table>
							<div style="margin:15px;">
								<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
								<input name="obsuid" type="hidden" value="<?php echo $obsUid; ?>" />
								<input name="action" type="submit" value="Merge Duplicate Records" />
							</div>
						</form>
						<?php 
					}
					else{
						?>
						<div style="margin:25px;font-weight:bold;font-size:120%;">
							There are no duplicate catalog numbers!
						</div>
						<?php 
					}
				}
				elseif($action == 'Merge Duplicate Records'){
					?>
					<ul>
						<li>Duplicate merging process started</li>
						<?php 
						$cleanManager->mergeDupeArr($_POST['dupid']);
						?>
						<li>Done!</li>
					</ul>
					<div>
						<a href="occurrencecleaner.php?collid=<?php echo $collId.'&obsuid='.$obsuid; ?>&action=listdups">Return to duplicate list</a><br/>
					</div> 
					<?php 
				}
				elseif($action == 'batchlinkdupes'){
					?>
					<ul>
						<?php 
						$cleanManager->linkDuplicates($collId,true);
						?>
					</ul>
					<?php 
				}
				elseif($action == 'listdupes' || $action == 'listdupeconflicts'){
					$clusterArr = $cleanManager->getDuplicateClusters($action == 'listdupes'?0:1);
					if($clusterArr){
						?>
						<div style="font-weight:bold;font-size:120%;"><?php echo $collMap['collectionname']; ?></div>
						<div style="font-weight:bold;font-size:110%;"><?php echo count($clusterArr).' Duplicate Clusters '.($action == 'listdupeconflicts'?'with Identification Differences':''); ?></div>
						<div style="margin:10px 0px;clear:both;">
							<?php 
							foreach($clusterArr as $dupId => $dupArr){
								?>
								<div style="clear:both;margin:10px 0px;">
									<div>
										<b><?php echo $dupArr['title']; ?></b> 
										<span onclick="toggle('editdiv-<?php echo $dupId; ?>')"><img src="../../images/edit.png" style="width:13px;" /></span> 
									</div>
									<?php 
									if(isset($dupArr['desc'])) echo '<div style="margin-left:10px;">'.$dupArr['desc'].'</div>';
									if(isset($dupArr['notes'])) echo '<div style="margin-left:10px;">'.$dupArr['notes'].'</div>';
									?>
									<div class="editdiv-<?php echo $dupId; ?>" style="display:none;">
										<fieldset style="margin:20px;padding:15px;">
											<legend><b>Edit Cluster</b></legend>
											<form name="dupeditform-<?php echo $dupId; ?>" method="post" action="occurrencecleaner.php" onsubmit="return verifyEditForm(this);">
												<b>Title:</b> <input name="title" type="text" value="<?php echo $dupArr['title']; ?>" style="width:300px;" /><br/>
												<b>Description:</b> <input name="description" type="text" value="<?php echo $dupArr['desc']; ?>" style="width:400px;" /><br/>
												<b>Notes:</b> <input name="notes" type="text" value="<?php echo $dupArr['notes']; ?>" style="width:400px;" /><br/>
												<input name="dupid" type="hidden" value="<?php echo $dupId; ?>" />
												<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
												<input name="action" type="hidden" value="<?php echo $action; ?>" />
												<input name="formsubmit" type="hidden" value="clusteredit" />
												<input name="submit" type="submit" value="Save Edits" />
											</form>
											<form name="dupdelform-<?php echo $dupId; ?>" method="post" action="occurrencecleaner.php" onsubmit="return confirm('Are you sure you want to delete this duplicate cluster?');">
												<input name="deldupid" type="hidden" value="<?php echo $dupId; ?>" />
												<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
												<input name="obsuid" type="hidden" value="<?php echo $obsUid; ?>" />
												<input name="action" type="hidden" value="<?php echo $action; ?>" />
												<input name="formsubmit" type="hidden" value="clusterdelete" />
												<input name="submit" type="submit" value="Delete Cluster" />
											</form>
										</fieldset>
									</div>
									<div style="margin:7px 10px;">
										<?php 
										unset($dupArr['title']);
										unset($dupArr['desc']);
										unset($dupArr['notes']);
										foreach($dupArr as $occid => $oArr){
											?>
											<div>
												<a href="#" onclick="openOccurPopup(<?php echo $occid; ?>); return false;"><b><?php echo $oArr['id']; ?></b></a> =&gt; 
												<?php echo '<b>'.$oArr['sciname'].'</b>: '.$oArr['recby'].' '; ?>
												<div class="editdiv-<?php echo $dupId; ?>" style="display:none;">
													<form name="dupdelform-<?php echo $dupId.'-'.$occid; ?>" method="post" action="occurrencecleaner.php" onsubmit="return confirm('Are you sure you want to remove this occurrence record from this cluster?');" style="display:inline;">
														<input name="dupid" type="hidden" value="<?php echo $dupId; ?>" />
														<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
														<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
														<input name="obsuid" type="hidden" value="<?php echo $obsUid; ?>" />
														<input name="action" type="hidden" value="<?php echo $action; ?>" />
														<input name="formsubmit" type="hidden" value="occdelete" />
														<input name="submit" type="image" src="../../images/del.gif" style="width:15px;" />
													</form>
												</div>
											</div>
											<?php 
										}
										?>
									</div>
								</div>
								<?php 
							}
							?>
						</div>
						<?php
					}
					else{
						 echo '<div><b>No Duplicate Clusters Exist</b></div>';
					}
				}
				
				?>
				<div>
					<a href="occurrencecleaner.php?collid=<?php echo $collId.'&obsuid='.$obsUid; ?>">Return to main menu</a>
				</div> 
				<?php 
			}
		}
		else{
			echo '<h2>You are not authorized to access this page</h2>';
		}
		?>
	</div>
<?php 	
if(!$dupArr){
	include($serverRoot.'/footer.php');
}
?>

</body>
</html>