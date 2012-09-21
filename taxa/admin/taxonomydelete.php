<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TaxonomyEditorManager.php');

$tid = $_REQUEST["tid"];
$genusStr = array_key_exists('genusstr',$_REQUEST)?$_REQUEST["genusstr"]:'';

$taxonEditorObj = new TaxonomyEditorManager();
$taxonEditorObj->setTid($tid);
$verifyArr = $taxonEditorObj->verifyDeleteTaxon();
?>
<script language=javascript>

$(document).ready(function() {

	$("#remaptvalue").autocomplete({ 
			source: "rpc/gettaxasuggest.php",  
			minLength: 2
		}
	);
});

function submitRemapTaxonForm(f){
	taxonValue = f.remaptvalue.value;
	if(taxonValue == ""){
		alert("Target taxon does not appear to be null. Please submit a taxon to remap the resources");
		return false;
	}
	var xmlHttp=GetXmlHttpObject();
	if(xmlHttp==null){
  		alert ("Your browser does not support AJAX!");
  		return;
  	}
	var url="rpc/gettid.php?sciname="+taxonValue;
	xmlHttp.onreadystatechange=function(){
		if(xmlHttp.readyState==4 && xmlHttp.status==200){
			var remapTid = xmlHttp.responseText;
			if(remapTid){
				f.remaptid.value = remapTid;
				f.submit();
			}
			else{
				alert("ERROR: Remapping taxon not found in thesaurus. Is the name spelled correctly?");
				f.remaptid.value = "";
			}
		}
	}
	xmlHttp.open("POST",url,true);
	xmlHttp.send(null);
}

function GetXmlHttpObject(){
	var xmlHttp=null;
	try{
		// Firefox, Opera 8.0+, Safari, IE 7.x
  		xmlHttp=new XMLHttpRequest();
  	}
	catch (e){
  		// Internet Explorer
  		try{
    		xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
    	}
  		catch(e){
    		xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
    	}
  	}
	return xmlHttp;
}

</script>
	<div style="min-height:400px; height:auto !important; height:400px; ">
		<div style="margin:15px 0px">
			Taxon record first needs to be evaluated before it can be deleted from the system. 
			The evaluation ensures that the deletion of this record will not interfer with 
			data integrity.      
		</div>
		<div style="margin:15px;">
			<b>Images</b>
			<div style="margin:10px"> 
				<?php 
				if($verifyArr['img'] > 0){
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
				<form name="remaptaxonform" method="post" action="taxonomyeditor.php">
					<div style="margin-bottom:5px;">
						Target taxon: 
						<input id="remaptvalue" name="remaptvalue" type="text" value="" /><br/>
						<input name="remaptid" type="hidden" value="" />
					</div>
					<div>
						<input name="submitbutton" type="button" value="Remap Taxon" onclick="submitRemapTaxonForm(this.form)" /> 
						<input name="submitaction" type="hidden" value="Remap Taxon" /> 
						<input name="target" type="hidden" value="<?php echo $tid; ?>" />
						<input name="genusstr" type="hidden" value="<?php echo $genusStr; ?>" /> 
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
						<input name="genusstr" type="hidden" value="<?php echo $genusStr; ?>" /> 
					</div>
				</form>
			</fieldset>
		</div>
	</div>
