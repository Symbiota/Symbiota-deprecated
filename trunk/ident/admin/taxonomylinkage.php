<?php 
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/IdentCharAdmin.php');

$cid = array_key_exists('cid',$_REQUEST)?$_REQUEST['cid']:0;

$keyManager = new IdentCharAdmin();
$keyManager->setCid($cid);
$tLinks = $keyManager->getTaxonRelevance();
?>
<div id="tlinkdiv" style="margin:15px;">
	<div style="float:right;margin:10px;">
		<a href="#" onclick="toggle('taxonAddDiv');">
			<img src="../../images/add.png" alt="Create New Character State" />
		</a>
	</div>
	<div style="margin:10px;">
		<b>Taxonomic relevance of character</b> - 
		Tag taxonomic nodes where character is most relevant. 
		Taxonomic branches can also be excluded. 
	</div>
	<div id="taxonAddDiv" style="display:<?php echo ($tLinks?'none':'block'); ?>;margin:15px;">
		<form name="taxonAddForm" action="chardetails.php" method="post" onsubmit="return validateTaxonAddForm(this)">
			<fieldset style="padding:20px;">
				<legend><b>Add Taxonomic Relevance Definition</b></legend>
				<div style="height:15px;">
					<div style="float:left;margin:3px;">
						<select name="tid">
							<option value="">Select Taxon</option>
							<option value="">--------------------</option>
							<?php 
							$taxonArr = $keyManager->getTaxonArr();
							foreach($taxonArr as $tid => $sciname){
								echo '<option value="'.$tid.'">'.$sciname.'</option>';
							}
							?>
						</select>
					</div>
					<div style="float:left;margin:3px;">
						<select name="relation">
							<option value="include">Relevant</option>
							<option value="exclude">Exclude</option>
						</select>
					</div>
				</div>
				<div style="margin:3px;clear:both;">
					<b>Notes</b><br/> 
					<input name="notes" type="text" value="" style="width:90%" />
				</div>
				<div style="margin:15px;">
					<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
					<button name="formsubmit" type="submit" value="Save Taxonomic Relevance">Save Taxonomic Relevance</button>
				</div>
			</fieldset>
		</form>
	</div>
	<?php 
	if($tLinks){
		if(isset($tLinks['include'])){
			?>
			<fieldset style="padding:20px;">
				<legend><b>Relevant Taxa</b></legend>
				<?php 
				foreach($tLinks['include'] as $tid => $tArr){
					?>
					<div style="margin:3px;clear:both;">
						<?php 
						echo '<div style="float:left;"><b>'.$tArr['sciname'].'</b>'.($tArr['notes']?' - '.$tArr['notes']:'').'</div> ';
						?>
						<form name="delTaxonForm" action="chardetails.php" method="post" style="float:left;margin-left:5px;" onsubmit="return comfirm('Are you sure you want to delete this relationship?')">
							<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
							<input name="tid" type="hidden" value="<?php echo $tid; ?>" />
							<input name="formsubmit" type="hidden" value="deltaxon" />
							<input type="image" src="../../images/del.gif" style="width:15px;" />
						</form>
					</div>
					<?php 
				}
				?>
			</fieldset>
			<?php 
		}
		if(isset($tLinks['exclude'])){
			?>
			<fieldset style="padding:20px;">
				<legend><b>Exclude Taxa</b></legend>
				<?php 
				foreach($tLinks['exclude'] as $tid => $tArr){
					?>
					<div style="margin:3px;">
						<?php 
						echo '<div style="float:left;"><b>'.$tArr['sciname'].'</b>'.($tArr['notes']?' - '.$tArr['notes']:'').'</div> ';
						?>
						<form name="delTaxonForm" action="chardetails.php" method="post" style="float:left;margin-left:5px;" onsubmit="return comfirm('Are you sure you want to delete this relationship?')">
							<input name="cid" type="hidden" value="<?php echo $cid; ?>" />
							<input name="tid" type="hidden" value="<?php echo $tid; ?>" />
							<input name="formsubmit" type="hidden" value="deltaxon" />
							<input type="image" src="../../images/del.gif" style="width:15px;" />
						</form>
					</div>
					<?php 
				}
				?>
			</fieldset>
			<?php 
		}
	}
	?>
</div>