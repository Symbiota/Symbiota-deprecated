<?php
include_once('../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceCleaner.php');
header("Content-Type: text/html; charset=".$charset);

$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';

$cleanManager = new OccurrenceCleaner();
if($collId) $cleanManager->setCollId($collId);
$collMap = $cleanManager->getCollMap();

$statusStr = '';
$isEditor = 0; 
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
	$isEditor = 1;
}

if($action == 'listdups'){
	$dupArr = $cleanManager->getDuplicateRecords();
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
		if($symbUid && $collId && $isEditor){
			if(!$action || $action == 'listdups' || $action == 'Merge Duplicate Records'){
				if(!$dupArr){
					?>
					<fieldset style="padding:20px;">
						<legend><b>Duplicate Catalog Numbers</b></legend>
						<?php
				}
				?>
					<?php 
					//Look for duplicate catalognumbers 
					if($action == 'listdups'){
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
							<div style="font-weight:bold;font-size:120%;margin:25px;">There are no duplicate catalog numbers!</div>
							<a href="occurrencecleaner.php?collid=<?php echo $collId; ?>">Return to Data Cleaning Menu</a>
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
							<a href="occurrencecleaner.php?collid=<?php echo $collId; ?>&action=listdups">Return to duplicate list</a>
						</div> 
						<?php 
					}
					else{
						?>
						<div>
							This function will query the collection for records with duplicate catalog numbers. 
							Results will be listed in a table grouped by the catlog number. Clicking on the number in the 
							left most column will open the editor for that record. Selecting the checkboxes for two or more 
							records within the groups and submitting the form will merge selected records. Select link below to 
							query database for possible duplicate records. Note that a maximun of around 500 records will 
							be returned at a time.
						</div>
						<div style="margin:25px;font-weight:bold;font-size:120%;">
							<a href="occurrencecleaner.php?collid=<?php echo $collId; ?>&action=listdups">
								Check here to list duplicate Catalog Numbers
							</a>
						</div>
						<?php 
					}
				if(!$dupArr){
					?>
					</fieldset>
				<?php
				}
				?>
				<hr style="margin:15px 0px;"/>
				<?php
			} 
			
			//Look for bad taxonomic names
			if(!$action || $action == "taxoncleaner"){
				?>
				<fieldset style="padding:20px;">
					<legend><b>Taxonomic Names</b></legend>
						<div>
							This function will query the collection for specimen records with unmapped, possibly problematic taxonomic names.      
						</div>
						<div style="margin:25px;font-weight:bold;font-size:120%;">
							In development
						</div>
				</fieldset>
				
				<?php
			} 
		}
		else{
			if(!$symbUid){
				?>
				<div style="font-weight:bold;font-size:120%;margin:30px;">
					Please 
					<a href="../../profile/index.php?refurl=<?php echo $clientRoot.'/collections/editor/occurrenceeditor.php&collid='.$collId; ?>">
						LOGIN
					</a> 
				</div>
				<?php 
			}
			elseif(!$collId){
				
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