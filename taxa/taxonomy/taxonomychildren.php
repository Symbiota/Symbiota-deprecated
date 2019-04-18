<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyEditorManager.php');

$tid = $_REQUEST["tid"];
$taxAuthId = array_key_exists('taxauthid', $_REQUEST)?$_REQUEST["taxauthid"]:1;

$taxonEditorObj = new TaxonomyEditorManager();
$taxonEditorObj->setTid($tid);
$taxonEditorObj->setTaxAuthId($taxAuthId);

$childrenArr = $taxonEditorObj->getChildren();
?>
<script>
</script>
<div style="min-height:400px; height:auto !important; height:400px; ">
	<div style="margin:15px;">
		<b>Children Taxa</b>
		<div style="margin:10px">
			<?php
			if($childrenArr){
				foreach($childrenArr as $childTid => $childArr){
					echo '<div style="margin:3px 10px;"><a href="taxoneditor.php?tid='.$childTid.'"><i>'.$childArr['name'].'</i></a></div>';
				}
				echo '<div style="margin-top:20px;">* Showing direct children only</div>';
			}
			else{
				echo '<div style="margin:15px">No children taxa exist for this taxon</div>';
			}
			?>
		</div>
	</div>
</div>
