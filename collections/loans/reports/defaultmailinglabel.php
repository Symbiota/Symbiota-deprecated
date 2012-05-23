<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'../classes/SpecLoans.php');

$collId = $_REQUEST['collid'];
$printMode = $_POST['print'];
$language = $_POST['languagedef'];
$loanId = array_key_exists('loanid',$_REQUEST)?$_REQUEST['loanid']:0;
$exchangeId = array_key_exists('exchangeid',$_REQUEST)?$_REQUEST['exchangeid']:0;
$loanType = array_key_exists('loantype',$_REQUEST)?$_REQUEST['loantype']:0;
$international = array_key_exists('international',$_POST)?$_POST['international']:0;
$accountNum = array_key_exists('mailaccnum',$_POST)?$_POST['mailaccnum']:0;
$searchTerm = array_key_exists('searchterm',$_POST)?$_POST['searchterm']:'';
$displayAll = array_key_exists('displayall',$_POST)?$_POST['displayall']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$loanManager = new SpecLoans();
if($collId) $loanManager->setCollId($collId);

$exportDoc = ($printMode == 'doc'?1:0);
$spanish = ($language == 'span'?1:0);

$identifier = 0;
if($loanId){
	$identifier = $loanId;
}
elseif($exchangeId){
	$identifier = $exchangeId;
}

?>

<!DOCTYPE HTML>
<html <?php echo ($exportDoc?'xmlns:w="urn:schemas-microsoft-com:office:word"':'') ?>>
	<head>
		<?php 
		if($exportDoc){
			header('Content-Type: application/msword');
			header('Content-disposition: attachment; filename='.$identifier.'_mailing_label.doc');
		?>
		<meta charset="<?php echo $charset; ?>">
		<xml>
			<w:WordDocument>
			<w:View>Print</w:View>
			<w:Pages>1</w:Pages>
			</w:WordDocument>
		</xml>
		<?php
		}
		?>
		<title><?php echo $identifier; ?> Specimen List</title>
		<style type="text/css">
			<?php 
				include_once($serverRoot.'/css/main.css');
			?>
			body {font-family:arial,sans-serif;}
			p.printbreak {page-break-after:always;}
			.fromaddress {font:10pt arial,sans-serif;}
			.toaddress {margin-left:1in;font:14pt arial,sans-serif;}
			<?php 
			if($exportDoc) {
				echo ('@page WordSection1
						{size:8.5in 11.0in;
						margin:.25in .25in .25in .25in;
						mso-header-margin:0;
						mso-footer-margin:0;
						mso-paper-source:0;}
					div.WordSection1
					{page:WordSection1;}');
			}
			?>
		</style>
	</head>
	<body>
		<div <?php echo ($exportDoc?'class=WordSection1':'') ?>>
			<?php
			$invoiceArr = $loanManager->getInvoiceInfo($identifier,$loanType);
			$addressArr = $loanManager->getFromAddress($collId);
			$specTotal = $loanManager->getSpecTotal($loanId);
			$specList = $loanManager->getSpecList($loanId);
			?>
			<table style="width:8in;">
				<tr>
					<td></td>
					<td> 
						<div class="fromaddress">
							<?php 
							echo $addressArr['institutionname'].' ('.$addressArr['institutioncode'].')<br />';
							if($addressArr['institutionname2']){
								echo $addressArr['institutionname2'].'<br />';
							}
							echo $addressArr['address1'].'<br />';
							if($addressArr['address2']){
								echo $addressArr['address2'].'<br />';
							}
							echo $addressArr['city'].', '.$addressArr['stateprovince'].' '.$addressArr['postalcode'].'<br />';
							if($international){
								echo $addressArr['country'].'<br />';
							}
							if($accountNum){
								echo '(Acct. #'.$accountNum.')<br />';
							}
							echo '<br />';
							?>
						</div>
						<div class="toaddress">
							<?php 
							echo $invoiceArr['contact'].'<br />';
							echo $invoiceArr['institutionname'].' ('.$invoiceArr['institutioncode'].')<br />';
							if($invoiceArr['institutionname2']){
								echo $invoiceArr['institutionname2'].'<br />';
							}
							echo $invoiceArr['address1'].'<br />';
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
			</table>
		</div>
	</body>
</html>