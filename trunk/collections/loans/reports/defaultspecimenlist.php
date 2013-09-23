<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/SpecLoans.php');

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
			header('Content-disposition: attachment; filename='.$identifier.'_specimen_list.doc');
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
			.header {width:100%;text-align:left;font:14pt arial,sans-serif;}
			.loaninfo {width:100%;text-align:left;font:11pt arial,sans-serif;}
			.colheader {text-align:left;font:bold 8pt arial,sans-serif;border-bottom:1px solid black;vertical-align:text-bottom;}
			.specimen {text-align:left;font:8pt arial,sans-serif;}
			<?php 
			if($exportDoc) {
				echo ('@page WordSection1
						{size:8.5in 11.0in;
						margin:.75in .75in .75in .75in;
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
			<div class="header">
				List of specimens loaned to: <?php echo $invoiceArr['institutioncode']; ?>
			</div>
			<br />
			<div class="loaninfo">
				<?php echo $addressArr['institutioncode']; ?> loan ID: <?php echo $invoiceArr['loanidentifierown']; ?><br />
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