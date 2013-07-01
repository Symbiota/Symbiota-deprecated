<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/PersonalSpecimenManager.php');
@header("Content-Type: text/html; charset=".$charset);

$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$formSubmit = array_key_exists("formsubmit",$_REQUEST)?$_REQUEST["formsubmit"]:"";

$specHandler = new PersonalSpecimenManager();

$occArr = array();
if($symbUid){
	$specHandler->setUid($symbUid);
	$occArr = $specHandler->getOccurrenceArr();
}

$statusStr = '';
?>
<div style="margin:10px;">
<?php 
if($symbUid){
	//Collection is defined and User is logged-in and have permissions
	if($statusStr){
		?>
		<hr/>
		<div style="margin:15px;color:red;">
			<?php echo $statusStr; ?>
		</div>
		<hr/>
		<?php 
	}
	$pendingIdents = array();
	$pendingIdents = $specHandler->getSpecimensPendingIdent();
	if (count($pendingIdents)==0) { 
		echo "<h2>No specimens in your registered speciality need identification to the species level</h2>";
	} else { 
		echo "<h2>Specimens needing identification in your area of expertise.</h2>";
	foreach ($pendingIdents as $key => $value) {
		echo "<a href='../collections/editor/occurrenceeditor.php?occid=$key'>$value->sciname</a> $value->collectionCode $value->institutionCode $value->stateProvince " . $value->getImageLink() . "<BR>";
	}	
	}
	
}
else{
	echo '<h2>Please <a href="../profile/index.php?&refurl='.$clientRoot.'/profile/personalspec.php?collid='.$collId.'">login</a></h2>';
}
?>	
</div>