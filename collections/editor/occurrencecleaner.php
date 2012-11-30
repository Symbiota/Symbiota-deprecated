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
<body>
	<?php 	
	$displayLeftMenu = false;
	include($serverRoot.'/header.php');
	?>
	<div class='navpath'>
		<a href="../../index.php">Home</a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo $collId; ?>&emode=1">Collection Management</a> &gt;&gt;
		<a href="occurrencecleaner.php?collid=<?php echo $collId; ?>">Data Cleaning Module</a>
	</div>
	<!-- inner text -->
	<div id="innertext">
		<?php 
		if($symbUid && $collId && $isEditor){
			if(!$action || $action == 'listdups' || $action == 'Merge Duplicate Records'){
				?>
				<fieldset style="padding:20px;">
					<legend><b>Duplicate Catalog Numbers</b></legend>
					<?php 
					//Look for duplicate catalognumbers 
					if($action == 'listdups'){
						$dupArr = $cleanManager->getDuplicateRecords();
						if($dupArr){
							//Get fields and remove unactivated fields
							$fieldArr = $dupArr['fields'];
							unset($dupArr['fields']);
							$recCnt = count($dupArr);
							//Build table
							?>
							<form name="mergeform" action="occurrencecleaner.php" method="post" onsubmit="return validateMergeForm(this)">
								<div><b>Duplicate Record Count: <?php echo ($recCnt>500?'> 500':$recCnt); ?></b></div>
								<table class="styledtable">
									<tr>
										<th>PK</th>
										<th><input name="selectalldupes" type="checkbox" title="Select/Deselect All" onclick="selectAllDuplicates(this.form)" /></th>
										<th>Catalog Number</th>
										<?php 
										foreach($fieldArr as $v){
											echo '<th>'.$v.'</th>';
										}
										?>
									</tr>
									<?php 
									$setCnt = 0;
									foreach($dupArr as $catNum => $setArr){
										$setCnt++;
										foreach($setArr as $occid => $occArr){
											echo '<tr '.(($setCnt % 2) == 1?'class="alt"':'').'>';
											echo '<td><a href="occurrenceeditor.php?occid='.$occid.'" target="_blank">'.$occid.'</a></td>'."\n";
											echo '<td><input name="dupid[]" type="checkbox" value="'.$catNum.':'.$occid.'" /></td>';
											echo '<td>'.$catNum.'</td>'."\n";
											foreach($fieldArr as $v){
												if(array_key_exists($v,$occArr)){
													$outStr = $occArr[$v];
													$titleStr = '';
													if(strlen($outStr) > 150){
														$titleStr = $outStr;
														$outStr = substr($outStr,150).'...';
													} 
													echo '<td'.($titleStr?' title="'.$occArr[$v].'"':'').'>'.$outStr.'</td>'."\n";
												}
												else{
													echo '<td>&nbsp;</td>';
												}
											}
											echo '</tr>';
										}
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
					?>
				</fieldset>
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
include($serverRoot.'/footer.php');
?>

</body>
</html>