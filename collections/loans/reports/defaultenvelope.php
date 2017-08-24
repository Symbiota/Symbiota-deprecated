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
$loanId = array_key_exists('loanid',$_REQUEST)?$_REQUEST['loanid']:0;
$exchangeId = array_key_exists('exchangeid',$_REQUEST)?$_REQUEST['exchangeid']:0;
$loanType = array_key_exists('loantype',$_REQUEST)?$_REQUEST['loantype']:0;
$institution = array_key_exists('institution',$_POST)?$_POST['institution']:0;
$international = array_key_exists('international',$_POST)?$_POST['international']:0;
$accountNum = array_key_exists('mailaccnum',$_POST)?$_POST['mailaccnum']:0;
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

$identifier = 0;
if($loanId){
	$identifier = $loanId;
}
elseif($exchangeId){
	$identifier = $exchangeId;
}

if($institution){
	$invoiceArr = $loanManager->getToAddress($institution);
}
else{
	$invoiceArr = $loanManager->getInvoiceInfo($identifier,$loanType);
}
$addressArr = $loanManager->getFromAddress($collId);

if($export){
	$phpWord = new \PhpOffice\PhpWord\PhpWord();
	$phpWord->addParagraphStyle('acctnum', array('align'=>'left','indent'=>5.5,'lineHeight'=>1.0,'spaceAfter'=>0,'keepNext'=>true,'keepLines'=>true));
	$phpWord->addFontStyle('acctnumFont', array('size'=>8,'name'=>'Arial'));
	$phpWord->addParagraphStyle('toAddress', array('align'=>'left','indent'=>6,'lineHeight'=>1.0,'spaceAfter'=>0,'keepNext'=>true,'keepLines'=>true));
	$phpWord->addFontStyle('toAddressFont', array('size'=>12,'name'=>'Arial'));
	
	$section = $phpWord->addSection(array('pageSizeW'=>13662.992125984,'pageSizeH'=>5952.755905512,'marginLeft'=>360,'marginRight'=>360,'marginTop'=>360,'marginBottom'=>360,'headerHeight'=>0,'footerHeight'=>0));
	$section->addTextBreak(5);
	if($accountNum){
		$section->addText(htmlspecialchars('Acct. #'.$accountNum),'acctnumFont','acctnum');
	}
	$section->addTextBreak(1);
	$textrun = $section->addTextRun('toAddress');
	$textrun->addText(htmlspecialchars($invoiceArr['contact']),'toAddressFont');
	$textrun->addTextBreak(1);
	$textrun->addText(htmlspecialchars($invoiceArr['institutionname'].' ('.$invoiceArr['institutioncode'].')'),'toAddressFont');
	$textrun->addTextBreak(1);
	if($invoiceArr['institutionname2']){
		$textrun->addText(htmlspecialchars($invoiceArr['institutionname2']),'toAddressFont');
		$textrun->addTextBreak(1);
	}
	if($invoiceArr['address1']){
		$textrun->addText(htmlspecialchars($invoiceArr['address1']),'toAddressFont');
		$textrun->addTextBreak(1);
	}
	if($invoiceArr['address2']){
		$textrun->addText(htmlspecialchars($invoiceArr['address2']),'toAddressFont');
		$textrun->addTextBreak(1);
	}
	$textrun->addText(htmlspecialchars($invoiceArr['city'].', '.$invoiceArr['stateprovince'].' '.$invoiceArr['postalcode']),'toAddressFont');
	if($international){
		$textrun->addTextBreak(1);
		$textrun->addText(htmlspecialchars($invoiceArr['country']),'toAddressFont');
	}
	
	$targetFile = $serverRoot.'/temp/report/'.$paramsArr['un'].'_addressed_envelope.'.$exportExtension;
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
			<title>Addressed Envelope</title>
			<style type="text/css">
				<?php 
					include_once($serverRoot.'/css/main.css');
				?>
				body {font-family:arial,sans-serif;}
				p.printbreak {page-break-after:always;}
				.accnum {margin-left:2.5in;font:8pt arial,sans-serif;}
				.toaddress {margin-left:3in;font:12pt arial,sans-serif;}
			</style>
		</head>
		<body style="background-color:#ffffff;">
			<div>
				<table>
					<tr style="height:1in;">
						<td></td>
					</tr>
					<tr>
						<td></td>
					</tr>
					<tr style="">
						<td>
							<?php 
								if($accountNum){
									echo '<div class="accnum">Acct. #'.$accountNum.'</div>';
								}
							?>
						</td>
					</tr>
					<tr style="height:1.5in;">
						<td>
							<div class="toaddress">
								<?php 
								echo $invoiceArr['contact'].'<br />';
								echo $invoiceArr['institutionname'].' ('.$invoiceArr['institutioncode'].')<br />';
								if($invoiceArr['institutionname2']){
									echo $invoiceArr['institutionname2'].'<br />';
								}
								if($invoiceArr['address1']){
									echo $invoiceArr['address1'].'<br />';
								}
								if($invoiceArr['address2']){
									echo $invoiceArr['address2'].'<br />';
								}
								echo $invoiceArr['city'].', '.$invoiceArr['stateprovince'].' '.$invoiceArr['postalcode'];
								if($international){
									echo '<br />'.$invoiceArr['country'];
								}
								?>
							</div>
						</td>
					</tr>
					<tr>
						<td></td>
					</tr>
				</table>
			</div>
		</body>
	</html>
	<?php
}
?>