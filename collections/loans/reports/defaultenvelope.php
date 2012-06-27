<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'../classes/SpecLoans.php');

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

$loanManager = new SpecLoans();
if($collId) $loanManager->setCollId($collId);

$exportDoc = ($printMode == 'doc'?1:0);

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
			header('Content-disposition: attachment; filename=addressed_envelope.doc');
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
		<title>Addressed Envelope</title>
		<style type="text/css">
			<?php 
				include_once($serverRoot.'/css/main.css');
			?>
			body {font-family:arial,sans-serif;}
			p.printbreak {page-break-after:always;}
			.accnum {margin-left:2.5in;font:8pt arial,sans-serif;}
			.toaddress {margin-left:3in;font:12pt arial,sans-serif;}
			<?php 
			if($exportDoc) {
				echo ('@page WordSection1
						{size:9.5in 4.13in;
						mso-page-orientation:landscape;
						margin:.4in .2in .2in .2in;
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
			if($institution){
				$invoiceArr = $loanManager->getToAddress($institution);
			}else{
				$invoiceArr = $loanManager->getInvoiceInfo($identifier,$loanType);
			}
			$addressArr = $loanManager->getFromAddress($collId);
			?>
			<table>
				<tr style="height:1in;">
					<td></td>
				</tr>
				<tr style=" ">
					<td></td>
				</tr>
				<tr style=" ">
					<td><?php if($accountNum){echo '<div class="accnum">Acct. #'.$accountNum.'</div>';} ?></td>
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
				<tr style=" ">
					<td></td>
				</tr>
			</table>
		</div>
	</body>
</html>