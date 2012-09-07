<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TaxonomyEditorManager.php');

$tid = $_REQUEST["tid"];

$taxonEditorObj = new TaxonomyEditorManager();
$taxonEditorObj->setTid($tid);
$verifyArr = $taxonEditorObj->verifyDeleteTaxon();
?>
<script language=javascript>

$(document).ready(function() {

	$("#remaptvalue").autocomplete({ 
			source: "rpc/gettaxasuggest.php",  
			minLength: 3,
			select: function( event, ui ) {
				if(ui.item) {
					var v = ui.item.value;
					var v2 = v.substring(v.indexOf("[")+1);
					document.remaptaxonform.remaptid.value = v2.substring(0,v2.length-1);
				}
			},
			change: function(event, ui){
				if(!ui.item){
					alert("Target taxon not on list. Select a valid taxon!");
					document.remaptaxonform.remaptid.value = "";
				}
			}
		}
	);
});

function validateRemapTaxonForm(f){
	if(f.remaptid.value == ""){
		alert("Target taxon does not appear to be valid. Are you sure it was listed?");
		return false;
	}
	return true;
}

</script>
	<div style="min-height:400px; height:auto !important; height:400px; ">
		Taxon record first needs to be evaluated before it can be deleted from the system. 
		The evaluation ensures that the deletion of this record will not interfer with 
		data integrity.      
		
		<div style="font-weight:bold;font-size:110%;margin-bottom:10px;">Linked Data</div>
		<div style="margin:15px;">
			<b>Images</b>
			<div style="margin:10px"> 
				<?php 
				if(array_key_exists('img',$verifyArr)){
					?>
					<span style="color:red;">Warning: <?php echo $verifyArr['img']; ?> images linked to this taxon</span> 
					<?php 
				}
				else{
					?>
					<span style="color:green;">Approved:</span> no images linked to this taxon
					<?php 
				}
				?>
			</div>
		</div>
		<div style="margin:15px;">
			<b>Vernaculars</b> 
			<div style="margin:10px"> 
				<?php 
				if(array_key_exists('vern',$verifyArr)){
					$displayStr = implode(', ',$verifyArr['vern']);
					?>
					<span style="color:red;">Warning, linked vernacular names:</span> <?php echo $displayStr; ?> 
					<?php 
				}
				else{
					?>
					<span style="color:green;">Approved:</span> no vernacular names linked to this taxon
					<?php 
				}
				?>
			</div>
		</div>
		<div style="margin:15px;">
			<b>Text Descriptions</b> 
			<div style="margin:10px"> 
				<?php 
				if(array_key_exists('tdesc',$verifyArr)){
					?>
					<span style="color:red;">Warning, linked text descriptions exist:</span>
					<ul>
						<?php 
						echo '<li>'.implode('</li><li>',$verifyArr['tdesc']).'</li>';
						?> 
					
					</ul>
					<?php 
				}
				else{
					?>
					<span style="color:green;">Approved:</span> no text descriptions linked to this taxon
					<?php 
				}
				?>
			</div>
		</div>
		<div style="margin:15px;">
			<b>Checklists:</b> 
			<div style="margin:10px"> 
				<?php 
				if(array_key_exists('cl',$verifyArr)){
					$clArr = $verifyArr['cl'];
					?>
					<span style="color:red;">Warning, linked checklists exist:</span>
					<ul>
						<?php 
						foreach($clArr as $k => $v){
							echo '<li><a href="../../checklists/checklist.php?cl='.$k.'" target="_blank">';
							echo $v;
							echo '</a></li>';
						}
						?>
					</ul>
					<?php 
				}
				else{
					?>
					<span style="color:green;">Approved:</span> no checklists linked to this taxon
					<?php 
				}
				?>
			</div>
		</div>
		<div style="margin:15px;">
			<b>Morphological Characters (Key):</b> 
			<div style="margin:10px"> 
				<?php 
				if(array_key_exists('kmdecr',$verifyArr)){
					?>
					<span style="color:red;">Warning: <?php echo $verifyArr['kmdecr']; ?> linked morphological characters</span>
					<?php 
				}
				else{
					?>
					<span style="color:green;">Approved:</span> no morphological characters linked to this taxon
					<?php 
				}
				?>
			</div>
		</div>
		<div style="margin:15px;">
			<b>Linked Resources:</b> 
			<div style="margin:10px"> 
				<?php 
				if(array_key_exists('link',$verifyArr)){
					?>
					<span style="color:red;">Warning: linked resources exists</span>
					<ul>
						<?php 
						echo '<li>'.implode('</li><li>',$verifyArr['link']).'</li>';
						?> 
					
					</ul>
					<?php 
				}
				else{
					?>
					<span style="color:green;">Approved:</span> no resources linked to this taxon
					<?php 
				}
				?>
			</div>
		</div>
		<div style="margin:15px;">
			<fieldset style="padding:15px;">
				<legend><b>Remap Resources to Another Taxon</b></legend>
				<form name="remaptaxonform" method="post" action="taxonomyeditor.php" onsubmit="return validateRemapTaxonForm(this)">
					<div style="margin-bottom:5px;">
						Target taxon: 
						<input id="remaptvalue" name="remaptvalue" type="text" value="" /><br/>
						<input id="remaptid" name="remaptid" type="hidden" value="" />
					</div>
					<div>
						<input name="submitaction" type="submit" value="Remap Taxon" /> 
						<input name="target" type="hidden" value="<?php echo $tid; ?>" /> 
					</div>
				</form>
			</fieldset>
		</div>
		<div style="margin:15px;">
			<fieldset style="padding:15px;">
				<legend><b>Delete Taxon and Existing Resources</b></legend>
				<div style="margin:10px 0px;">
				</div>
				<form name="deletetaxonform" method="post" action="taxonomyeditor.php" onsubmit="return confirm('Are you sure you want to delete this taxon? Action can not be undone!')">
					<div>
						<input name="submitaction" type="submit" value="Delete Taxon" /> 
						<input name="target" type="hidden" value="<?php echo $tid; ?>" /> 
					</div>
				</form>
			</fieldset>
		</div>
	</div>
