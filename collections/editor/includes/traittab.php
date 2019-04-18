<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceAttributes.php');
header("Content-Type: text/html; charset=".$CHARSET);

$occid = $_GET['occid'];
$occIndex = $_GET['occindex'];
$action = isset($_GET['submitaction'])?$_GET['submitaction']:'';

$attrManager = new OccurrenceAttributes();
$attrManager->setOccid($occid);

$isEditor = 0;
if($IS_ADMIN || ($collId && array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"]))){
	$isEditor = 1;
}
elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollEditor"])){
	$isEditor = 2;
}
elseif($attrManager->getObserverUid() == $SYMB_UID){
	//Users can edit their own records
	$isEditor = 2;
}

if($isEditor){
	if(isset($_GET['delstates']) && $_GET['delstates']){
		$attrManager->deleteAttributes($_GET['delstates']);
	}
}
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

	function submitEditForm(butElem){
		var f = butElem.form;
		var action = butElem.value;
		var hasStates = false;
		var stateJson = {};
		$('input[name^="stateid"]').each(function(index,data) {
			if($(this).prop('checked')){
				if($(this).attr('name') in stateJson){
					stateJson[$(this).attr('name')].push($(this).val());
				}
				else{
					stateJson[$(this).attr('name')] = [$(this).val()];
				}
				hasStates = true;
			}
		});
		if(hasStates){
			var traitIdStr = f.traitid.value;
			$("#msgDiv-"+traitIdStr).text('saving data...');
			$("#msgDiv-"+traitIdStr).css('color', 'orange');
			$.ajax({
				type: "POST",
				url: "rpc/editorTraitHandler.php",
				data: { occid: f.occid.value, traitID: traitIdStr, submitAction: action, source: f.source.value, notes: f.notes.value, setStatus: f.setstatus.value, stateData: JSON.stringify(stateJson) }
			}).done(function( retStatus ) {
				if(retStatus == 1){
					$("#msgDiv-"+traitIdStr).css('color', 'green');
					$("#msgDiv-"+traitIdStr).text('data saved!');
				}
				else{
					$("#msgDiv-"+traitIdStr).css('color', 'red');
					$("#msgDiv-"+traitIdStr).text('ERROR saving data: '+retStatus);
				}
			});
		}
		else{
			alert("No traits have been selected");
		}
	}

</script>
<div id="traitdiv" style="width:795px;">
	<?php
	$traitArr = $attrManager->getTraitArr();
	$codedTraitArr = array();
	foreach($traitArr as $tID => $tArr){
		if(isset($tArr['states'])){
			foreach($tArr['states'] as $sID => $sArr){
				if(isset($sArr['coded'])){
					$codedTraitArr[$tID] = $tArr;
					break;
				}
			}
		}
	}
	if($codedTraitArr){
		foreach($codedTraitArr as $codedTraitID => $codeTraitArr){
			if(!isset($codeTraitArr['dependentTrait'])){
				$statusCode = 0;
				$notes = '';
				$source = '';
				foreach($codeTraitArr['states'] as $id => $stArr){
					if(isset($stArr['statuscode']) && $stArr['statuscode']) $statusCode = $stArr['statuscode'];
					if(isset($stArr['notes']) && $stArr['notes']) $notes = $stArr['notes'];
					if(isset($stArr['source']) && $stArr['source']) $source = $stArr['source'];
				}
				?>
				<fieldset style="margin-top:20px">
					<legend><b>Coded Trait: <?php echo $codeTraitArr['name']; ?></b></legend>
					<div style="float:right" title="Hard refresh of page">
						<form name="refreshform" method="post" action="occurrenceeditor.php" >
							<div style="margin:20px">
								<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
								<?php
								if($occIndex) echo '<input name="occindex" type="hidden" value="'.$occIndex.'" />';
								?>
								<input name="tabtarget" type="hidden" value="3" />
								<input type="image" src="../../images/refresh.png" />
							</div>
						</form>
					</div>
					<form name="submitform" method="post" action="occurrenceeditor.php" onsubmit="">
						<div>
							<?php
							$attrManager->echoFormTraits($codedTraitID);
							?>
						</div>
						<div style="margin:10px 5px;">
							Notes:
							<input name="notes" type="text" style="width:300px" value="<?php echo $notes; ?>" />
						</div>
						<div style="margin:10px 5px;">
							Source:
							<input name="source" type="text" style="width:300px" value="<?php echo $source; ?>" />
						</div>
						<div style="margin-left:5;">
							Status:
							<select name="setstatus">
								<option value="0">Not reviewed</option>
								<option value="5" <?php echo ($statusCode=='5'?'selected':''); ?>>Expert Needed</option>
								<option value="10" <?php echo ($statusCode=='10'?'selected':''); ?>>Reviewed</option>
							</select>
						</div>
						<div style="margin:20px;float:left">
							<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
							<input name="occindex" type="hidden" value="<?php echo $occIndex; ?>" />
							<input name="traitid" type="hidden" value="<?php echo $codedTraitID; ?>" />
							<input name="delstates" type="hidden" value="<?php echo $attrManager->getStateCodedStr(); ?>" />
							<input name="tabtarget" type="hidden" value="3" />
							<button name="submitbutton" type="submit" value="editTraitCoding" onclick="submitEditForm(this); return false">Save Edits</button>
							<span id="msgDiv-<?php echo $codedTraitID; ?>"></span>
						</div>
						<div style="margin:20px;float:right;">
							<button name="submitaction" type="submit" value="deleteCoding" style="border:1px solid red;" onclick="return confirm('Are you sure you want to delete this trait coding?')">Delete Coding</button>
						</div>
					</form>
				</fieldset>
				<?php
			}
		}
	}
	$traitArr = array_diff_key($traitArr, $codedTraitArr);
	if($traitArr){
		foreach($traitArr as $newTraitID => $newTraitArr){
			if(!isset($newTraitArr['dependentTrait'])){
				?>
				<fieldset style="margin-top:20px">
					<legend><b>New Trait: <?php echo $newTraitArr['name']; ?></b></legend>
					<div style="float:right" title="Hard refresh of page">
						<form name="refreshform" method="post" action="occurrenceeditor.php" >
							<div style="margin:20px">
								<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
								<?php
								if($occIndex) echo '<input name="occindex" type="hidden" value="'.$occIndex.'" />';
								?>
								<input name="tabtarget" type="hidden" value="3" />
								<input type="image" src="../../images/refresh.png" />
							</div>
						</form>
					</div>
					<form name="submitform" method="post" action="editortraithandler.php" onsubmit="" >
						<div>
							<?php
							$attrManager->echoFormTraits($newTraitID);
							?>
						</div>
						<div style="margin:10px 5px;">
							Notes:
							<input name="notes" type="text" style="width:300px" value="" />
						</div>
						<div style="margin:10px 5px;">
							Source:
							<input name="source" type="text" style="width:300px" value="viewingSpecimenImage" />
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
							<button name="submitbutton" type="submit" value="addTraitCoding" onclick="submitEditForm(this); return false">Save Edits</button>
							<span id="msgDiv-<?php echo $newTraitID; ?>"></span>
						</div>
					</form>
				</fieldset>
				<?php
			}
		}
	}
	?>
</div>