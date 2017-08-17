<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcessorManager.php');
include_once($serverRoot.'/classes/SpecProcNlpBryophyte.php');
include_once($serverRoot.'/classes/SpecProcNlpLichen.php');
include_once($serverRoot.'/classes/SpecProcNlpSalix.php');
header("Content-Type: text/html; charset=".$charset);
if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/specprocessor/index.php?'.$_SERVER['QUERY_STRING']);

$collid = $_REQUEST['collid'];
$parserTarget = $_REQUEST['parser'];
$action = array_key_exists('formsubmit',$_REQUEST)?$_REQUEST['formsubmit']:'';

$procManager = new SpecProcessorManager();
$procManager->setCollId($collid);

$nlpManager = null;
if($parserTarget == 'lbcc'){
	$nlpManager = new SpecProcNlpLbcc();
}
else{
	$nlpManager = new SpecProcNlpSalix();
}
//$nlpManager->setCollId($collid);

$isEditor = false;
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collid,$userRights["CollAdmin"]))){
 	$isEditor = true;
}

$status = "";
if($isEditor){
	if($action == ''){
		//$status = $nlpManager->addProfile($_REQUEST);
	}
}
?>
<!-- This is inner text! -->
<div id="innertext">
	<h1>NLP Processor</h1>
	<?php 
	if($status){ 
		?>
		<div style='margin:20px 0px 20px 0px;'>
			<hr/>
			<?php echo $status; ?>
			<hr/>
		</div>
		<?php 
	}
	if($isEditor && $collid){
		$unprocessedCnt = $procManager->getProcessingStatusCount('unprocessed');
		?>
		<div style="height:400px;">
			<div style="margin:5px;">
				Unprocessed Specimens: 
				<?php 
				echo $unprocessedCnt; 
				?>
			</div>
			<div style="margin:5px;">
				Unprocessed Specimens without Images: 
				<?php 
				echo $procManager->getUnprocSpecNoImage(); 
				?>
			</div>
			<div style="margin:5px;">
				Unprocessed Specimens without OCR: 
				<?php 
				echo $procManager->getSpecNoOcr(); 
				?>
			</div>
		</div>
		<?php
		if($unprocessedCnt){
			?>
			<div>
				
			</div>
			<?php
		}
		else{
			echo '<div>There are no unprocessed records to </div>';
		}
	}
	else{
		?>
		<div style='font-weight:bold;color:red;'>
			Unidentified Error
		</div>
		<?php
	}
	?>
</div>
