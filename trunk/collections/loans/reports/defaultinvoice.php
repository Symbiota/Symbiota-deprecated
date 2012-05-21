<?php
include_once('../../../config/symbini.php');
include_once($serverRoot.'/classes/SpecLoans.php');

$collId = $_REQUEST['collid'];
$printMode = $_POST['print'];
$languageDef = $_POST['languagedef'];
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
$english = ($languageDef == 0 || $languageDef == 1);
$engspan = ($languageDef == 1);
$spanish = ($languageDef == 1 || $languageDef == 2);

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
			header('Content-disposition: attachment; filename='.$identifier.'_invoice.doc');
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
		<title><?php echo $identifier; ?> Invoice</title>
		<style type="text/css">
			<?php 
				include_once($serverRoot.'/css/main.css');
			?>
			body {font-family:arial,sans-serif;}
			p.printbreak {page-break-after:always;}
			.header {width:100%;text-align:center;font:bold 12pt arial,sans-serif;margin-bottom:30px;}
			.toaddress {float:left;text-align:left;font:10pt arial,sans-serif;margin-top:10px;}
			.identifier {float:right;text-align:right;font:bold 10pt arial,sans-serif;margin-top:10px;}
			.sending {width:100%;text-align:left;font:10pt arial,sans-serif;}
			.forwhom {width:100%;text-align:left;font:10pt arial,sans-serif;}
			.loanreturn {width:100%;text-align:left;font:bold 10pt arial,sans-serif;}
			.exchangeamts {width:100%;text-align:left;font:bold 10pt arial,sans-serif;}
			.duedate {width:100%;text-align:left;font:bold 10pt arial,sans-serif;}
			.exchangebal {width:100%;text-align:left;font:10pt arial,sans-serif;}
			.loanoutinfo {width:100%;text-align:left;font:10pt arial,sans-serif;}
			.description {width:100%;text-align:left;font:10pt arial,sans-serif;}
			.message {width:100%;text-align:left;font:10pt arial,sans-serif;}
			.saludos {width:100%;text-align:left;font:10pt arial,sans-serif;}
			.return {width:100%;text-align:left;font:10pt arial,sans-serif;position:relative;bottom:0;margin-top:20%;}
			<?php 
			if($exportDoc) {
				echo ('@page WordSection1
						{size:8.5in 11.0in;
						margin:.5in .5in .5in .5in;
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
		<table style="height:10in;"><tr><td>
		<div <?php echo ($exportDoc?'class=WordSection1':'') ?>>
			<?php
			$invoiceArr = $loanManager->getInvoiceInfo($identifier,$loanType);
			$addressArr = $loanManager->getFromAddress($collId);
			$specTotal = $loanManager->getSpecTotal($loanId);
			$exchangeValue = $loanManager->getExchangeValue($exchangeId);
			$exchangeTotal = $loanManager->getExchangeTotal($exchangeId);
			
			if($loanType == 'Exchange'){
				$giftTotal = $invoiceArr['totalgift'] + $invoiceArr['totalgiftdet'];
				$transType = 0;
				if(($invoiceArr['totalexunmounted'] || $invoiceArr['totalexmounted']) && (!$invoiceArr['totalgift'] && !$invoiceArr['totalgiftdet'])){
					$transType = 'ex';
				}
				elseif(($invoiceArr['totalexunmounted'] || $invoiceArr['totalexmounted']) && ($invoiceArr['totalgift'] || $invoiceArr['totalgiftdet'])){
					$transType = 'both';
				}
				elseif((!$invoiceArr['totalexunmounted'] || !$invoiceArr['totalexmounted']) && ($invoiceArr['totalgift'] || $invoiceArr['totalgiftdet'])){
					$transType = 'gift';
				}
			}
			?>
			<table class="header" align="center">
				<tr>
					<td><?php echo $addressArr['institutionname']; ?> (<?php echo $addressArr['institutioncode']; ?>)</td>
				</tr>
				<?php if($addressArr['institutionname2']){ ?>
					<tr>
						<td><?php echo $addressArr['institutionname2']; ?></td>
					</tr>
				<?php } ?>
				<tr>
					<td><?php echo $addressArr['address1']; ?></td>
				</tr>
				<?php if($addressArr['address2']){ ?>
					<tr>
						<td><?php echo $addressArr['address2']; ?></td>
					</tr>
				<?php } ?>
				<tr>
					<td><?php echo $addressArr['city'].', '.$addressArr['stateprovince'].' '.$addressArr['postalcode']; ?> <?php if($international){echo $addressArr['country'];} ?></td>
				</tr>
				<tr>
					<td><?php echo $addressArr['phone']; ?></td>
				</tr>
				<tr style="height:10px;">
					<td> </td>
				</tr>
				<tr>
					<?php 
					echo '<td>'.($english?'SHIPPING INVOICE':'').($engspan?' / ':'').($spanish?'FACTURA DE REMESA':'').'</td>' ;
					?>
				</tr>
			</table>
			<br />
			<br />
			<table style="width:100%;">
				<tr>
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
					<td class="identifier">
						<div class="identifier">
							<?php 
							echo date('l').', '.date('F').' '.date('j').', '.date('Y').'<br />';
							if($loanType == 'Out'){
								echo $addressArr['institutioncode'].' Loan ID: '.$invoiceArr['loanidentifierown'];
							}
							elseif($loanType == 'In'){
								echo $addressArr['institutioncode'].' Loan-in ID: '.$invoiceArr['loanidentifierborr'];
							}
							elseif($loanType == 'Exchange'){
								echo $addressArr['institutioncode'].' Transaction ID: '.$invoiceArr['identifier'];
							}
							?>
						</div>
					</td>
				</tr>
			</table>
			<br />
			<div class="sending">
				<?php 
				$numSpecimens = 0;
				if($loanType == 'Exchange'){
					$numSpecimens = $exchangeTotal;
				}
				else{
					if($specTotal){
						if($specTotal['speccount'] == 1){
							$numSpecimens = 1;
						}
						else{
							$numSpecimens = $specTotal['speccount'];
						}
					}
					else{
						if($invoiceArr['numspecimens'] == 1){
							$numSpecimens = 1;
						}
						else{
							$numSpecimens = $invoiceArr['numspecimens'];
						}
					}
				}
				
				if($english){
					echo '<div>We are sending you '.($invoiceArr['totalboxes'] == 1?'1 box':$invoiceArr['totalboxes']).' boxes ';
					echo 'containing '.($numSpecimens == 1?'1 specimen':$numSpecimens).' specimens. ';
					echo ($invoiceArr['totalboxes'] == 1?'This package was ':'These packages were ');
					echo 'delivered via '.$invoiceArr['shippingmethod'].'. Upon arrival of the shipment, kindly verify its contents and acknowledge ';
					echo 'receipt by signing and returning the duplicate invoice to us.</div><br />';
				}
				if($spanish){
					echo '<div>Est&aacute;mos remitiendo a Uds. '.($invoiceArr['totalboxes'] == 1?'1 caja':$invoiceArr['totalboxes']).' cajas ';
					echo 'de '.($numSpecimens == 1?'1 ejemplar':$numSpecimens).' ejemplares. ';
					echo ($invoiceArr['totalboxes'] == 1?'Esta remesa hubiera enviado ':'Estas remesas hubieran enviado ');
					echo 'por '.$invoiceArr['shippingmethod'].'. Al llegar la remesa, por favor verifique los contenidos y s&iacute;rvase acusar ';
					echo 'recibo de esta remesa firmiendo una de las copias y devolvi&eacute;ndo la por correo.</div><br />';
				}
				?>
			</div>
			<?php 
			if($loanType == 'Out'){ ?>
				<?php if($english){ ?>
					<div class="forwhom">This shipment is a LOAN for study by <?php echo $invoiceArr['forwhom']; ?>.</div><br />
				<?php }
				if($spanish){ ?>
					<div class="forwhom">Esta remesa es un PRESTAMO para el estudio de <?php echo $invoiceArr['forwhom']; ?>.</div><br />
				<?php }
				if($english){ ?>
					<div class="duedate">Loans are made for a period of 2 years. This loan will be due <?php echo $invoiceArr['datedue']; ?>.</div><br />
				<?php }
				if($spanish){ ?>
					<div class="duedate">Los pr&eacute;stamos se extienden por un periodo de 2 a&ntilde;os. Este pr&eacute;stamo tiene una fecha l&iacute;mite de <?php echo $invoiceArr['datedue']; ?>.</div><br />
				<?php }
				if($english){ ?>
					<div class="loanoutinfo">When circumstances warrant, the loan period may be extended. Specimens should be returned by 
						insured parcel post or by prepaid express. All material of this loan should be returned at the same time. Notes or 
						changes should be written on annotation labels. Reprints dealing with taxonomic groups will be appreciated.
					</div><br />
				<?php }
				if($spanish){ ?>
					<div class="loanoutinfo">Siempre y cuando las circunstancias se permiten, se puede pedir un pr&oacute;rroga de la fecha l&iacute;mite de este  
						pr&eacute;stamo. Todo material del pr&eacute;stamo debe devolverse en el mismo env&iacute;o. Notas y cambios de identificaci&oacute;n se  
						deben indicar con notas de anotaci&oacute;n. Adem&aacute;s, le pedimos mandar separatas de cualquier publicaci&oacute;n 
						proveniente del uso de este material.
					</div><br />
				<?php }
			}	
			elseif($loanType == 'In'){ ?>
				<?php if($english){ ?>
					<div class="loanreturn">This shipment is a return of <?php echo $invoiceArr['institutioncode']; ?>
						loan <?php echo $invoiceArr['loanidentifierown']; ?>, received <?php echo $invoiceArr['datereceivedborr']; ?>.</div><br />
				<?php }
				if($spanish){ ?>
					<div class="loanreturn">En esta remesa se devuelve el prestamo <?php echo $invoiceArr['loanidentifierown']; ?>
						de <?php echo $invoiceArr['institutioncode']; ?>, recibido <?php echo $invoiceArr['datereceivedborr']; ?>.</div><br />
				<?php } ?>
			<?php }
			elseif($loanType == 'Exchange'){
				if($transType == 'ex' || $transType == 'both'){
					if($english){ ?>
						<div class="exchangeamts">This shipment is an EXCHANGE, consisting of <?php echo ($invoiceArr['totalexunmounted']?$invoiceArr['totalexunmounted'].' unmounted ':''); ?>
							<?php echo (($invoiceArr['totalexunmounted'] && $invoiceArr['totalexmounted'])?'and ':''); ?><?php echo ($invoiceArr['totalexmounted']?$invoiceArr['totalexmounted'].' mounted ':''); ?>
							specimens, for an exchange value of <?php echo $exchangeValue; ?>. Please note that mounted specimens count as two.
						</div><br />
					<?php }
					if($spanish){ ?>
						<div class="exchangeamts">Este env&iacute;o es un INTERCAMBIO, consistiendo en <?php echo ($invoiceArr['totalexunmounted']?$invoiceArr['totalexunmounted'].' ejemplares no montados ':''); ?>
							<?php echo (($invoiceArr['totalexunmounted'] && $invoiceArr['totalexmounted'])?'y ':''); ?><?php echo ($invoiceArr['totalexmounted']?$invoiceArr['totalexmounted'].' ejemplares montados ':''); ?>, 
							con un valor de intercambio de <?php echo $exchangeValue; ?>. Favor de notarse que las ejemplares montados son de valor 2.
						</div><br />
					<?php }
					if($transType == 'both'){
						if($english){ ?>
							<div class="exchangeamts">This shipment also contains <?php echo ($giftTotal == 1?'1 gift specimen.':$giftTotal); ?> gift specimens. 
							</div><br />
						<?php }
						if($spanish){ ?>
							<div class="exchangeamts">Esta remesa tambi&eacute;n contiene <?php echo ($giftTotal == 1?'1 ejemplar de regalo.':$giftTotal); ?> ejemplares de regalo. 
							</div><br />
						<?php }
					}
					if($english){ ?>
						<div class="exchangebal">Our records show a balance of <?php echo abs($invoiceArr['invoicebalance']); ?> specimens  
							in <?php echo ($invoiceArr['invoicebalance']>0?'our':'your'); ?> favor. Please contact us if your records differ significantly. 
						</div><br />
					<?php }
					if($spanish){ ?>
						<div class="exchangebal">Nuestros registros muestran un balance de <?php echo abs($invoiceArr['invoicebalance']); ?> ejemplares  
							a <?php echo ($invoiceArr['invoicebalance']>0?'nuestro':'su'); ?> favor. Favor de contactarnos si sus 
							registros se d&iacute;fieren de una manera apreciable.
						</div><br />
					<?php }
				}
				elseif($transType == 'gift'){
					if($english){ ?>
						<div class="exchangeamts">This shipment is a GIFT. 
						</div><br />
					<?php }
					if($spanish){ ?>
						<div class="exchangeamts">Este env&iacute;o es un REGALO. 
						</div><br />
					<?php }
				}
			}
			?>
			<div class="description">
				<?php
					echo '<b>'.($english?'DESCRIPTION OF THE SPECIMENS':'').($engspan?' / ':'').($spanish?'DESCRIPCI&Oacute;N DE LOS EJEMPLARES':'').':</b><br /><br />' ;
				?>
				
			</div>
			<br />
			<?php 
			if(array_key_exists('invoicemessage',$invoiceArr) || array_key_exists('invoicemessageown',$invoiceArr) || array_key_exists('invoicemessageborr',$invoiceArr)){
				echo '<div class="message">';
				if($loanType == 'Exchange'){
					echo ($invoiceArr['invoicemessage']?$invoiceArr['invoicemessage']:'');
				}
				elseif($loanType == 'Out'){
					echo ($invoiceArr['invoicemessageown']?$invoiceArr['invoicemessageown']:'');
				}
				elseif($loanType == 'In'){
					echo ($invoiceArr['invoicemessageborr']?$invoiceArr['invoicemessageborr']:'');
				}
				echo '</div><br /><br />';
			} ?>
			<div class="saludos">
				<?php
					echo ($english?'Sincerely':'').($engspan?' / ':'').($spanish?'Sinceramente':'').',<br />' ;
				?>
			</div>
			<br />
			<br />
			<br />
			<br />
			<br />
			<br />
			<br />
			<br />
			<div class="return">
				<hr />
				<br />
				<?php 
				if($english){
					echo 'PLEASE SIGN AND RETURN ONE COPY UPON RECEIPT OF THIS SHIPMENT<br />' ;
				}
				if($spanish){
					echo 'POR FAVOR FIRME Y DEVUELVE UNA COPIA AL LLEGAR ESTA REMESA.<br />' ;
				} ?>
				<br />
				<?php
					echo ($english?'The above specimens were received in good condition':'').($engspan?' / ':'').($spanish?'Recibido en buenas condiciones':'').'.<br />' ;
				?>
				<br />
				<?php
					echo ($english?'Signed':'').($engspan?'/':'').($spanish?'Firma':'').':______________________________________  '.($english?'Date':'').($engspan?'/':'').($spanish?'Fecha':'').':______________<br />' ;
				?>
			</div>
		</div>
	</td></tr></table>
	</body>
</html>