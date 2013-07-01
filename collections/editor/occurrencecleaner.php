<?php
include_once('../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceCleaner.php');
header("Content-Type: text/html; charset=".$charset);

$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';

if(!$symbUid){
	header('Location: ../../profile/index.php?refurl=../collections/editor/occurrencecleaner.php?'.$_SERVER['QUERY_STRING']);
}

$cleanManager = new OccurrenceCleaner();
if($collId) $cleanManager->setCollId($collId);
$collMap = $cleanManager->getCollMap();

$statusStr = '';
$isEditor = 0; 
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
	$isEditor = 1;
}

$dupArr = array();
if($action == 'listdupscatalog'){
	$dupArr = $cleanManager->getDuplicateCatalogNumber();
	$action == 'listdups';
}
elseif($action == 'listdupsrecordedby'){
	$dupArr = $cleanManager->getDuplicateCollectorNumber();
	$action == 'listdups';
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
		if($collId && $isEditor){
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
						<a href="occurrencecleaner.php?collid=<?php echo $collId; ?>&action=listdupscatalog">
							Search for duplicates based on <b>Catalog Numbers</b>
						</a>
					</div>
					<div style="margin:25px;font-weight:bold;font-size:120%;">
						<a href="occurrencecleaner.php?collid=<?php echo $collId; ?>&action=listdupsrecordedby">
							Search for duplicates based on <b>Collector/Observer and numbers</b>
						</a>
					</div>
				</fieldset>

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

				<fieldset style="padding:20px;">
					<legend><b>Duplicate Linkages</b></legend>
						<div>
						These tools aid collection managers in batch linking their specimen records to duplicate specimens existing 
						within other collections linked within the data portal. The main method of locating duplciates is by matching 
						the collector, collector number, and collection date.       
					</div>
					<div style="margin:25px;font-weight:bold;font-size:120%;">
						<a href="occurrencecleaner.php?collid=<?php echo $collId; ?>&action=listdupes">
							List linked duplicate clusters 
						</a>
					</div>
					<div style="margin:25px;font-weight:bold;font-size:120%;">
						<a href="occurrencecleaner.php?collid=<?php echo $collId; ?>&action=batchlinkdupes">
							Start batch linking Duplicates
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
						<a href="occurrencecleaner.php?collid=<?php echo $collId; ?>&action=listdups">Return to duplicate list</a><br/>
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
				elseif($action == 'listdupes'){
					$clusterArr = $cleanManager->getDuplicateClusters();
					if($clusterArr){
						?>
						<div>Listing Duplicate Clusters</div>
						<ol>
							<?php 
							foreach($clusterArr as $dupId => $dupArr){
								echo '<div><b>'.$dupArr['title'].'</b></div>';
								if(isset($dupArr['desc'])) echo '<div style="margin-left:10px;">'.$dupArr['desc'].'</div>';
								if(isset($dupArr['notes'])) echo '<div style="margin-left:10px;">'.$dupArr['notes'].'</div>';
								unset($dupArr['title']);
								unset($dupArr['desc']);
								unset($dupArr['notes']);
								echo '<div style="margin:7px 10px;">';
								foreach($dupArr as $occid => $idStr){
									echo '<a href="../individual/index.php?occid='.$occid.'" target="_blank">'.$idStr.'</a><br/>';
								}
								echo '</div>';
							}
							?>
						</ol>
						<?php
					}
					else{
						 echo '<div><b>No Duplicate Clusters Exist</b></div>';
					}
				}
				?>
				<div>
					<a href="occurrencecleaner.php?collid=<?php echo $collId; ?>">Return to main menu</a>
				</div> 
				<?php 
			}
		}
		else{
			if(!$collId){
				?>
				<div style="margin:25px;font-weight:bold;font-size:120%;">
					ERROR: collid not defined
				</div>
				<?php 
			}
			elseif(!$isEditor){
				echo '<h2>You are not authorized to add occurrence records</h2>';
				
			}
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