<?php
//error_reporting(E_ALL);
include_once('../../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
header("Content-Type: text/html; charset=".$charset);

$occId = $_GET['occid'];
$occIndex = $_GET['occindex'];
$identBy = $_GET['identby'];
$dateIdent = $_GET['dateident'];
$sciName = $_GET['sciname'];

$annotatorname = $_GET['annotatorname'];
$annotatoremail = $_GET['annotatoremail'];
$catalognumber = $_GET['catalognumber'];
$institutioncode = $_GET['institutioncode'];

$occManager = new OccurrenceEditorDeterminations();

$occManager->setOccId($occId); 
$detArr = $occManager->getDetMap($identBy, $dateIdent, $sciName);
?>
<div id="determdiv" style="width:795px;">
	<div style="text-align:right;width:100%;">
		<img style="border:0px;width:12px;cursor:pointer;" src="../../images/add.png" onclick="toggle('newdetdiv');" title="Add New Determination" />
	</div>
	<div id="newdetdiv" style="display:none;">
		<form name="detaddform" action="occurrenceeditor.php" method="get" onsubmit="return verifyDetAddForm(this)">
			<fieldset>
				<legend><b>Add a New Determination</b></legend>
				<div style='margin:3px;'>
					<b>Identification Qualifier:</b>
					<input type="text" name="identificationqualifier" title="e.g. cf, aff, etc" />
				</div>
				<div style='margin:3px;'>
					<b>Scientific Name:</b> 
					<input type="text" id="dafsciname" name="sciname" style="background-color:lightyellow;width:350px;" onfocus="initDetAddAutocomplete()" />
					<input type="hidden" id="daftidtoadd" name="tidtoadd" value="" />
					<input type="hidden" name="family" value="" />
				</div>
				<div style='margin:3px;'>
					<b>Author:</b> 
					<input type="text" name="scientificnameauthorship" style="width:200px;" />
				</div>
				<div style='margin:3px;'>
					<b>Determiner:</b> 
					<input type="text" name="identifiedby" style="background-color:lightyellow;width:200px;" />
				</div>
				<div style='margin:3px;'>
					<b>Date:</b> 
					<input type="text" name="dateidentified" style="background-color:lightyellow;" onchange="detDateChanged(this.form);" />
				</div>
				<div style='margin:3px;'>
					<b>Reference:</b> 
					<input type="text" name="identificationreferences" style="width:350px;" />
				</div>
				<div style='margin:3px;'>
					<b>Notes:</b> 
					<input type="text" name="identificationremarks" style="width:350px;" />
				</div>
				<div style='margin:15px;'>
					<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
					<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
					<div style="float:left;">
						<input type="submit" name="submitaction" value="Add New Determination" />
					</div>
					<div style="float:left;margin-left:30px;">
						<input type="checkbox" name="makecurrent" value="1" /> Make this the current determination <br/>
						<input type="checkbox" name="remapimages" value="1" /> Remap images to new taxonomic name
					</div>
				</div>
			</fieldset>
		</form>
	</div>
	<div class="fieldset">
		<div class="legend"><b>Determination History</b></div>
		<?php
		if($detArr){
			foreach($detArr as $detId => $detRec){
				if(!array_key_exists('iscurrent',$detRec)){
					?>
					<div style="float:right;cursor:pointer;margin:10px;" onclick="toggle('editdetdiv-<?php echo $detId;?>');" title="Edit Determination">
						<img style="border:0px;width:12px;" src="../../images/edit.png" />
					</div>
					<?php 
				} 
				?>
				<div id="detdiv-<?php echo $detId;?>">
					<div>
						<?php 
						if($detRec['identificationqualifier']) echo $detRec['identificationqualifier'].' ';
						echo '<b><i>'.$detRec['sciname'].'</i></b> '.$detRec['scientificnameauthorship'];
						if(array_key_exists('iscurrent',$detRec)){
							echo '<span style="margin-left:10px;color:red;">CURRENT DETERMINATION</span>';	
						}
						?>
					</div>
					<div style='margin:3px 0px 0px 15px;'>
						<b>Determiner:</b> <?php echo $detRec['identifiedby']; ?>
						<span style="margin-left:40px;">
							<b>Date:</b> <?php echo $detRec['dateidentified']; ?>
						</span>
					</div>
					<?php 
					if($detRec['identificationreferences']){
						?>
						<div style='margin:3px 0px 0px 15px;'>
							<b>Reference:</b> <?php echo $detRec['identificationreferences']; ?>
						</div>
						<?php 
					}
					if($detRec['identificationremarks']){
						?>
						<div style='margin:3px 0px 0px 15px;'>
							<b>Notes:</b> <?php echo $detRec['identificationremarks']; ?>
						</div>
						<?php 
					}
					?>
				</div>
				<?php if(!array_key_exists('iscurrent',$detRec)){ ?>
				<div id="editdetdiv-<?php echo $detId;?>" style="display:none;">
					<fieldset>
						<legend><b>Edit Determination</b></legend>
						<form name="deteditform" action="occurrenceeditor.php" method="post" onsubmit="return verifyDetEditForm(this);">
							<div style='margin:3px;'>
								<b>Identification Qualifier:</b>
								<input type="text" name="identificationqualifier" value="<?php echo $detRec['identificationqualifier']; ?>" title="e.g. cf, aff, etc" />
							</div>
							<div style='margin:3px;'>
								<b>Scientific Name:</b> 
								<input type="text" id="defsciname-<?php echo $detId;?>" name="sciname" value="<?php echo $detRec['sciname']; ?>" style="background-color:lightyellow;width:350;" onfocus="initDetEditAutocomplete(this.id)" />
								<input type="hidden" id="deftidtoadd" name="tidtoadd" value="" />
							</div>
							<div style='margin:3px;'>
								<b>Author:</b> 
								<input type="text" name="scientificnameauthorship" value="<?php echo $detRec['scientificnameauthorship']; ?>" style="width:200;" />
							</div>
							<div style='margin:3px;'>
								<b>Determiner:</b> 
								<input type="text" name="identifiedby" value="<?php echo $detRec['identifiedby']; ?>" style="background-color:lightyellow;width:200;" />
							</div>
							<div style='margin:3px;'>
								<b>Date:</b> 
								<input type="text" name="dateidentified" value="<?php echo $detRec['dateidentified']; ?>" style="background-color:lightyellow;" />
							</div>
							<div style='margin:3px;'>
								<b>Reference:</b> 
								<input type="text" name="identificationreferences" value="<?php echo $detRec['identificationreferences']; ?>" style="width:350;" />
							</div>
							<div style='margin:3px;'>
								<b>Notes:</b> 
								<input type="text" name="identificationremarks" value="<?php echo $detRec['identificationremarks']; ?>" style="width:350;" />
							</div>
							<div style='margin:3px;'>
								<b>Sort Sequence:</b> 
								<input type="text" name="sortsequence" value="<?php echo $detRec['sortsequence']; ?>" style="width:40px;" />
							</div>
							<div style='margin:3px;margin:15px;'>
								<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
								<input type="hidden" name="detid" value="<?php echo $detId; ?>" />
								<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
								<input type="submit" name="submitaction" value="Submit Determination Edits" />
							</div>
						</form>
						<form name="detdelform" action="occurrenceeditor.php" method="post" onsubmit="return window.confirm('Are you sure you want to delete this specimen determination?');">
							<div style="padding:15px;background-color:lightblue;width:155px;margin:15px;">
								<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
								<input type="hidden" name="detid" value="<?php echo $detId; ?>" />
								<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
								<input type="submit" name="submitaction" value="Delete Determination" />
							</div>
						</form>
						<form name="detdelform" action="occurrenceeditor.php" method="post" onsubmit="return window.confirm('Are you sure you want to make this the most current determination?');">
							<div style="padding:15px;background-color:lightgreen;width:280px;margin:15px;">
								<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
								<input type="hidden" name="detid" value="<?php echo $detId; ?>" />
								<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
								<input type="submit" name="submitaction" value="Make Determination Current" /><br/>
								<input type="checkbox" name="remapimages" value="1" CHECKED /> Remap images to this taxonomic name
							</div>
						</form>
					</fieldset>
				</div>
				<?php } ?>
				<hr style='margin:10px 0px 10px 0px;' />
				<?php 
			}
		}
		else{
			?>
			<div style="font-weight:bold;margin:10px;font-size:120%;">
				There are no historic annotations for this specimen
			</div>
			<div style="margin:20px;">
				Click plus sign to the right to add a new annotation
			</div>
			<?php 
		}
		?>
	</div>
</div>
