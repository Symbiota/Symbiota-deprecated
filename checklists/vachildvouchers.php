<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ChecklistVoucherAdmin.php');

$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:""; 
$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$startPos = (array_key_exists('start',$_REQUEST)?(int)$_REQUEST['start']:0);

$vManager = new ChecklistVoucherAdmin();
$vManager->setClid($clid);

$isEditor = 0;
if($isAdmin || (array_key_exists("ClAdmin",$userRights) && in_array($clid,$userRights["ClAdmin"]))){
	$isEditor = 1;
}
?>
<!-- inner text -->
<div id="innertext">
	<?php 
	$childArr = $vManager->getChildTaxa();
	?>
	<div style="margin:20px;">
		Taxa within child checklists that are not in current list
	</div>
	<div style="margin:20px;">
		<?php 
		if($childArr){
			?>
			<table class="styledtable">
				<tr><th><b>Taxon</b></th><th><b>Source Checklist</b></th></tr>
				<?php
				foreach($childArr as $tid => $sArr){
					?>
					<tr>
						<td>
							<?php echo $sArr['sciname'] ?>
						</td>
						<td>
							<?php echo $sArr['cl'] ?>
						</td>
					</tr>
					<?php 
				}
				?>
			</table>
			<?php
		} 
		else{
			echo '<h2>No new taxa to inherit from a child checklist</h2>';
		}
		?>
	</div>
</div>