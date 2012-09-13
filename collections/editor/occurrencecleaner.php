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
if($isEditor){
	if($action == 'Merge Records'){
		$cleanManager->mergeDupeArr($_POST['dupid']);
		$action = 'listdups';
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

			return true;
		}
	</script>
</head>
<body>
	<?php 	
	$displayLeftMenu = false;
	include($serverRoot.'/header.php');
	?>
	<!-- inner text -->
	<div id="innertext">
		<?php 
		if($symbUid && $collId && $isEditor){
			?>
			<fieldset style="padding:20px;">
				<legend>Duplicate Catalog Numbers</legend>
				<?php 
				//Look for duplicate catalognumbers 
				if($action == 'listdups'){
					$dupArr = $cleanManager->getDuplicateRecords();
					if($dupArr){
						//Get fields and remove unactivated fields
						$fieldArr = $dupArr['fields'];
						unset($dupArr['fields']);
						foreach($fieldArr as $k => $v){
							if($v === '') unset($fieldArr[$k]);
						}
						//Build table
						?>
						<form name="mergeform" action="occurrencecleaner.php" method="post" onsubmit="return validateMergeForm(this)">
							<table class="styledtable">
								<tr>
									<th>PK</th>
									<th>&nbsp;</th>
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
												$outStr = htmlentities($occArr[$v]);
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
								<input name="action" type="submit" value="Merge Records" />
							</div>
						</form>
						<?php 
					}
				}
				else{
					echo '<a href="occurrencecleaner.php?collid='.$collId.'&action=listdups">List duplicate Catalog Numbers</a>';
				}
				?>
			</fieldset>
			<?php 
			
			
			

			//Look for bad taxonomic names
			
			
			
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