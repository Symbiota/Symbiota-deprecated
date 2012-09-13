<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcessorOcr.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
?>
<div style="margin:15px;">
	<?php 
	
	?>
	<fieldset style="padding:20px;">
		<legend><b>Statistics</b></legend> 
		<ul>
			<li>Total "unprocessed" specimens:</li> 
			<li>Number with OCR fragments:</li>
			<li>Number without OCR fragments:</li>
			
		</ul>
	</fieldset>



	<!-- 
	<div style="">
		<form name="abbyyloaderform" action="index.php" enctype="multipart/form-data" method="post" onsubmit="return validateAbbyyForm(this);">
			<fieldset>
				<legend><b>ABBYY OCR File Loader</b></legend>
				<input type="hidden" name="MAX_FILE_SIZE" value="1000000" />
				<div style="font-weight:bold;margin:10px;">
					File: 
					<input id="abbyyfile" name="abbyyfile" type="file" size="45" />
				</div>
				<div style="margin:10px;">
					<input type="hidden" name="spprid" value="<?php echo $spprId; ?>" />
					<input type="hidden" name="collid" value="<?php echo $collId; ?>" >
					<input type="submit" name="action" value="Upload ABBYY File" />
				</div>
			</fieldset>
		</form>
	</div>
	 -->
</div>

<?php
$tempDirRoot = $SERVER['PHP_SELF'];
$tesseractPath = 'C:\Program Files (x86)\Tesseract-OCR\tesseract.exe';
include_once('dbconnection.php');
include_once('SpecProcessorOcr.php');

$ocrManager = new SpecProcessorOcr();

$collArr = array(28);
//$collArr = array(2,22,28,31,32);
$ocrManager->batchOcrUnprocessed($collArr,1);

?>