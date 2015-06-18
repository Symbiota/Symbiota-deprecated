<?php
include_once('../../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
header("Content-Type: text/html; charset=".$charset);

$occid = $_GET['occid'];
$occIndex = $_GET['occindex'];
$crowdSourceMode = $_GET['csmode'];

$occManager = new OccurrenceEditorManager();

$occManager->setOccId($occid); 
$genticArr = $occManager->getGeneticArr();
?>
<script type="text/javascript">

	function submitEditGeneticResource(f){
		if(f.resourcename.value == ""){
			alert("Genetic resource name must not be blank");
		}
		else{
			f.submit();
		}
	}
	
	function submitDeleteGeneticResource(f){
		if(confirm("Are you sure you want to premently remove this resource?")){
			f.submit();
		}
	}
	
	function submitAddGeneticResource(f){
		if(f.resourcename.value == ""){
			alert("Genetic resource name must not be blank");
		}
		else{
			f.submit();
		}
	}
</script>

<div id="geneticdiv"  style="width:795px;">
	<div style="float:right;">
		<a href="#" onclick="toggle('genadddiv');return false;" title="Add a new genetic resource" ><img src="../../images/add.png" /></a>
	</div>
	<div id="genadddiv" style="display:<?php echo ($genticArr?'none':'block'); ?>;">
		<fieldset>
			<legend><b>Add Genetic Resource</b></legend>
			<form name="addgeneticform" method="post" action="occurrenceeditor.php">
				<div style="margin:2px;">
					<b>Name:</b><br/>
					<input name="resourcename" type="text" value="" style="width:50%" />
				</div>
				<div style="margin:2px;">
					<b>Identifier:</b><br/>
					<input name="identifier" type="text" value="" style="width:50%" />
				</div>
				<div style="margin:2px;">
					<b>Locus:</b><br/>
					<input name="locus" type="text" value="" style="width:95%" />
				</div>
				<div style="margin:2px;">
					<b>URL:</b><br/>
					<input name="resourceurl" type="text" value="" style="width:95%" />
				</div>
				<div style="margin:2px;">
					<b>Notes:</b><br/>
					<input name="notes" type="text" value="" style="width:95%" />
				</div>
				<div style="margin:2px;">
					<input name="submitaction" type="hidden" value="addgeneticsubmit" />
					<input name="csmode" type="hidden" value="<?php echo $crowdSourceMode; ?>" />
					<input name="subbut" type="button" value="Add New Genetic Resource" onclick="submitAddGeneticResource(this.form)" />
					<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
				</div>
			</form>
		</fieldset>
	</div>
	<div style="clear:both;">
		<?php 
		foreach($genticArr as $genId => $gArr){
			?>
			<div style="float:right;">
				<a href="#" onclick="toggle('genedit-<?php echo $genId; ?>');return false;"><img src="../../images/edit.png" /></a>
			</div>
			<div style="margin:15px;">
				<div style="font-weight:bold;margin-bottom:5px;"><?php echo $gArr['name']; ?></div>
				<div style="margin-left:15px;"><b>Identifier:</b> <?php echo $gArr['id']; ?></div>
				<div style="margin-left:15px;"><b>Locus:</b> <?php echo $gArr['locus']; ?></div>
				<div style="margin-left:15px;">
					<b>URL:</b> <a href="<?php echo $gArr['resourceurl']; ?>" target="_blank"><?php echo $gArr['resourceurl']; ?></a>
				</div>
				<div style="margin-left:15px;"><b>Notes:</b> <?php echo $gArr['notes']; ?></div>
			</div>
			<div id="genedit-<?php echo $genId; ?>" style="display:none;margin-left:25px;">
				<fieldset>
					<legend><b>Genetic Resource Editor</b></legend>
					<form name="editgeneticform" method="post" action="occurrenceeditor.php">
						<div style="margin:2px;">
							<b>Name:</b><br/>
							<input name="resourcename" type="text" value="<?php echo $gArr['name']; ?>" style="width:50%" />
						</div>
						<div style="margin:2px;">
							<b>Identifier:</b><br/>
							<input name="identifier" type="text" value="<?php echo $gArr['id']; ?>" style="width:50%" />
						</div>
						<div style="margin:2px;">
							<b>Locus:</b><br/>
							<input name="locus" type="text" value="<?php echo $gArr['locus']; ?>" style="width:95%" />
						</div>
						<div style="margin:2px;">
							<b>URL:</b><br/>
							<input name="resourceurl" type="text" value="<?php echo $gArr['resourceurl']; ?>" style="width:95%" />
						</div>
						<div style="margin:2px;">
							<b>Notes:</b><br/>
							<input name="notes" type="text" value="<?php echo $gArr['notes']; ?>" style="width:95%" />
						</div>
						<div style="margin:2px;">
							<input name="submitaction" type="hidden" value="editgeneticsubmit" />
							<input name="subbut" type="button" value="Save Edits" onclick="submitEditGeneticResource(this.form)" />
							<input name="genid" type="hidden" value="<?php echo $genId; ?>" />
							<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
							<input name="csmode" type="hidden" value="<?php echo $crowdSourceMode; ?>" />
						</div>								
					</form>
				</fieldset>
				<fieldset>
					<legend><b>Delete Genetic Resource</b></legend>
					<form name="delgeneticform" method="post" action="occurrenceeditor.php">
						<div style="margin:2px;">
							<input name="submitaction" type="hidden" value="deletegeneticsubmit" />
							<input name="subbut" type="button" value="Delete Resource" onclick="submitDeleteGeneticResource(this.form)" />
							<input name="genid" type="hidden" value="<?php echo $genId; ?>" />
							<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
							<input name="csmode" type="hidden" value="<?php echo $crowdSourceMode; ?>" />
						</div>								
					</form>
				</fieldset>
			</div>
			<?php
		}
		if(!$genticArr) echo '<div style="font-weight:bold;font-size:120%;margin:15px 0px;">No Genetic Resources linked to this record</div>';
		?>
	</div>
</div>
