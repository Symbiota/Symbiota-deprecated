<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceAttributes.php');
header("Content-Type: text/html; charset=".$CHARSET);

$occid = $_GET['occid'];
$occIndex = $_GET['occindex'];

$attrManager = new OccurrenceAttributes();
$attrManager->setTargetOccid($occid);
?>
<script type="text/javascript">

	function traitChanged(traitID){
		$('input[name="stateid-'+traitID+'[]"]').each(function(){
			if(this.checked == true){
				$("div.child-"+this.value).show();
			}
			else{
				$("div.child-"+this.value).hide();
				$("input:checkbox.child-"+this.value).each(function(){ this.checked = false; });
				$("input:radio.child-"+this.value).each(function(){ this.checked = false; });
			}
		});
		$('input[name="submitform"]').prop('disabled', false);
	}

	function verifySubmitForm(f){

		return true;
	}

</script>
<div id="traitdiv" style="width:795px;">
	<?php
	$traitArr = $attrManager->getTraitArr();
	$codedTraitArr = array();
	foreach($traitArr as $tID => $tArr){
		if(isset($tArr['states'])){
			foreach($tArr['states'] as $sID => $sArr){
				if(isset($sArr['coded'])) $codedTraitArr[$tID] = $tArr;
				break;
			}
		}
	}
	if($codedTraitArr){
		echo '<h2>Traits Coded for this Occurrence</h2>';
		foreach($codedTraitArr as $codedTraitID => $codeTraitArr){
			if(isset($codeTraitArr['dependentTrait'])){
				?>
				<fieldset style="margin-top:20px">
					<legend><b>Action Panel - <?php echo $codeTraitArr['name']; ?></b></legend>
					<form name="submitform" method="post" action="editortraithandler.php" onsubmit="return verifySubmitForm(this)" >
						<div>
							<?php
							$attrManager->echoFormTraits($codedTraitID);
							?>
						</div>
						<div style="margin-left:5;">
							Status:
							<select name="setstatus">
								<option value="0">Not reviewed</option>
								<option value="5">Expert Needed</option>
								<option value="10" selected>Reviewed</option>
							</select>
						</div>
						<div style="margin:20px">
							<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
							<input name="occindex" type="hidden" value="<?php echo $occIndex; ?>" />
							<input name="traitid" type="hidden" value="<?php echo $codedTraitID; ?>" />
							<button name="submitaction" type="submit" value="editTraitCoding" >Save Edits</button>
						</div>
					</form>
				</fieldset>
				<?php
			}
		}
	}
	else{
		echo '<h2>No traits have been coded for this occurrence</h2>';
	}
	$traitArr = array_diff_key($traitArr, $codedTraitArr);
	if($traitArr){
		echo '<h2>Traits Not Yet Coded for this Occurrence</h2>';
		foreach($traitArr as $newTraitID => $newTraitArr){
			if(!isset($newTraitArr['dependentTrait'])){
				?>
				<fieldset style="margin-top:20px">
					<legend><b>Action Panel - <?php echo $newTraitArr['name']; ?></b></legend>
					<form name="submitform" method="post" action="editortraithandler.php" onsubmit="return verifySubmitForm(this)" >
						<div>
							<?php
							$attrManager->echoFormTraits($newTraitID);
							?>
						</div>
						<div style="margin-left:5;">
							Status:
							<select name="setstatus">
								<option value="0">Not reviewed</option>
								<option value="5">Expert Needed</option>
								<option value="10" selected>Reviewed</option>
							</select>
						</div>
						<div style="margin:20px">
							<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
							<input name="occindex" type="hidden" value="<?php echo $occIndex; ?>" />
							<input name="traitid" type="hidden" value="<?php echo $newTraitID; ?>" />
							<button name="submitaction" type="submit" value="addTraitCoding" >Add Trait Coding</button>
						</div>
					</form>
				</fieldset>
				<?php
			}
		}
	}
	?>
</div>