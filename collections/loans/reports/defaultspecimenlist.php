<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/SpecLoans.php');
require_once $serverRoot.'/classes/PhpWord/Autoloader.php';

$loanManager = new SpecLoans();
use PhpOffice\PhpWord\Autoloader;
use PhpOffice\PhpWord\Settings;
Autoloader::register();
Settings::loadConfig();

$collId = $_REQUEST['collid'];
$printMode = $_POST['print'];
$language = $_POST['languagedef'];
$loanId = array_key_exists('loanid',$_REQUEST)?$_REQUEST['loanid']:0;
$exchangeId = array_key_exists('exchangeid',$_REQUEST)?$_REQUEST['exchangeid']:0;
$loanType = array_key_exists('loantype',$_REQUEST)?$_REQUEST['loantype']:0;
$international = array_key_exists('international',$_POST)?$_POST['international']:0;
$searchTerm = array_key_exists('searchterm',$_POST)?$_POST['searchterm']:'';
$displayAll = array_key_exists('displayall',$_POST)?$_POST['displayall']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$export = false;
$exportEngine = '';
$exportExtension = '';
if($printMode == 'doc'){
	$export = true;
	$exportEngine = 'Word2007';
	$exportExtension = 'docx';
}

if($collId) $loanManager->setCollId($collId);

$spanish = ($language == 'span'?1:0);

$identifier = 0;
if($loanId){
	$identifier = $loanId;
}
elseif($exchangeId){
	$identifier = $exchangeId;
}

$invoiceArr = $loanManager->getInvoiceInfo($identifier,$loanType);
$addressArr = $loanManager->getFromAddress($collId);
$specTotal = $loanManager->getSpecTotal($loanId);
$specList = $loanManager->getSpecList($loanId);

if($export){
	$phpWord = new \PhpOffice\PhpWord\PhpWord();
	$phpWord->addParagraphStyle('header', array('align'=>'left','lineHeight'=>1.0,'spaceAfter'=>0,'keepNext'=>true,'keepLines'=>true));
	$phpWord->addFontStyle('headerFont', array('size'=>14,'name'=>'Arial'));
	$phpWord->addParagraphStyle('info', array('align'=>'left','lineHeight'=>1.0,'spaceAfter'=>0,'keepNext'=>true,'keepLines'=>true));
	$phpWord->addFontStyle('infoFont', array('size'=>11,'name'=>'Arial'));
	$phpWord->addFontStyle('colHeaderFont', array('size'=>8,'bold'=>true,'name'=>'Arial'));
	$phpWord->addFontStyle('colFont', array('size'=>8,'name'=>'Arial'));
	$phpWord->addParagraphStyle('colHeadSpace', array('lineHeight'=>1.5,'spaceAfter'=>0));
	$phpWord->addParagraphStyle('colSpace', array('lineHeight'=>1.3,'spaceAfter'=>0));
	$tableHeadStyle = array('borderBottomSize'=>10,'borderBottomColor'=>'000000','width'=>100);
	$tableStyle = array('width'=>100);
	$colRowStyle = array('cantSplit'=>true);
	$phpWord->addTableStyle('headerTable',$tableHeadStyle,$colRowStyle);
	$phpWord->addTableStyle('listTable',$tableStyle,$colRowStyle);
	$cellStyle = array('valign'=>'bottom');
	
	$section = $phpWord->addSection(array('pageSizeW'=>12240,'pageSizeH'=>15840,'marginLeft'=>1080,'marginRight'=>1080,'marginTop'=>1080,'marginBottom'=>1080,'headerHeight'=>0,'footerHeight'=>0));
	
	$textrun = $section->addTextRun('header');
	$textrun->addText(htmlspecialchars('List of specimens loaned to: '.$invoiceArr['institutioncode']),'headerFont');
	$section->addTextBreak(1);
	$textrun = $section->addTextRun('info');
	$textrun->addText(htmlspecialchars($addressArr['institutioncode'].' Loan ID: '.$invoiceArr['loanidentifierown']),'infoFont');
	$textrun->addTextBreak(1);
	$textrun->addText(htmlspecialchars('Date sent: '.$invoiceArr['datesent']),'infoFont');
	$textrun->addTextBreak(1);
	$textrun->addText(htmlspecialchars('Total specimens: '.($specTotal?$specTotal['speccount']:0)),'infoFont');
	$section->addTextBreak(1);
	$table = $section->addTable('headerTable');
	$table->addRow();
	$table->addCell(2250,$cellStyle)->addText(htmlspecialchars('Catalog #'),'colHeaderFont','colHeadSpace');
	$table->addCell(4500,$cellStyle)->addText(htmlspecialchars('Collector + Number'),'colHeaderFont','colHeadSpace');
	$table->addCell(6000,$cellStyle)->addText(htmlspecialchars('Current Determination'),'colHeaderFont','colHeadSpace');
	$table = $section->addTable('listTable');
	foreach($specList as $k => $specArr){
		$table->addRow();
		$table->addCell(2250,$cellStyle)->addText(htmlspecialchars($specArr['catalognumber']),'colFont','colSpace');
		$table->addCell(4500,$cellStyle)->addText(htmlspecialchars($specArr['collector']),'colFont','colSpace');
		$table->addCell(6000,$cellStyle)->addText(htmlspecialchars($specArr['sciname']),'colFont','colSpace');
	}
	
	$targetFile = $serverRoot.'/temp/report/'.$loanId.'_specimen_list.'.$exportExtension;
	$phpWord->save($targetFile, $exportEngine);

	header('Content-Description: File Transfer');
	header('Content-type: application/force-download');
	header('Content-Disposition: attachment; filename='.basename($targetFile));
	header('Content-Transfer-Encoding: binary');
	header('Content-Length: '.filesize($targetFile));
	readfile($targetFile);
	unlink($targetFile);
}
else{
	?>
	<html>
		<head>
			<title><?php echo $identifier; ?> Specimen List</title>
			<style type="text/css">
				<?php 
					include_once($serverRoot.'/css/main.css');
				?>
				body {font-family:arial,sans-serif;}
				p.printbreak {page-break-after:always;}
				.header {width:100%;text-align:left;font:14pt arial,sans-serif;}
				.loaninfo {width:100%;text-align:left;font:11pt arial,sans-serif;}
				.colheader {text-align:left;font:bold 8pt arial,sans-serif;border-bottom:1px solid black;vertical-align:text-bottom;}
				.specimen {text-align:left;font:8pt arial,sans-serif;}
			</style>
		</head>
		<body style="background-color:#ffffff;">
			<div>
				<div class="header">
					List of specimens loaned to: <?php echo $invoiceArr['institutioncode']; ?>
				</div>
				<br />
				<div class="loaninfo">
					<?php echo $addressArr['institutioncode']; ?> Loan ID: <?php echo $invoiceArr['loanidentifierown']; ?><br />
					Date sent: <?php echo $invoiceArr['datesent']; ?><br />
					Total specimens: <?php echo ($specTotal?$specTotal['speccount']:0);?>
				</div>
				<br />
				<table class="colheader">
					<tr>
						<td style="width:150px;">
							<?php echo $addressArr['institutioncode']; ?><br />
							Catalog &#35;
						</td>
						<td style="width:300px;">
							Collector + Number
						</td>
						<td style="width:400px;">
							Current Determination
						</td>
						<td>  </td>
					</tr>
				</table>
				<table class="specimen">
					<?php
					foreach($specList as $k => $specArr){
						echo '<tr>';
						echo '<td style="width:150px;">'.$specArr['catalognumber'].'</td>';
						echo '<td style="width:300px;">'.$specArr['collector'].'</td>';
						echo '<td style="width:400px;">'.$specArr['sciname'].'</td>';
						echo '<td> </td>';
						echo '</tr>';
					}
					?>
				</table>
			</div>
		</body>
	</html>
	<?php
}
?>