<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecDatasetManager.php');
header("Content-Type: text/html; charset=".$charset);

$collId = $_POST["collid"];
$hPrefix = $_POST['lhprefix'];
$hMid = $_POST['lhmid'];
$hSuffix = $_POST['lhsuffix'];
$lFooter = $_POST['lfooter'];
$occIdArr = $_POST['occid'];
$rowsPerPage = $_POST['rpp'];
$floatingWidth = array_key_exists('fw',$_POST)?$_POST['fw']:0;
$useBarcode = array_key_exists('bc',$_POST)?$_POST['bc']:0;
$useSymbBarcode = array_key_exists('symbbc',$_POST)?$_POST['symbbc']:0;
$barcodeOnly = array_key_exists('bconly',$_POST)?$_POST['bconly']:0;
$action = array_key_exists('submitaction',$_POST)?$_POST['submitaction']:'';

$labelManager = new SpecDatasetManager();
$labelManager->setCollId($collId);

$isEditor = 0;
$occArr = array();
if($symbUid){
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])) || (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))){
		$isEditor = 1;
	}
}
if($action == 'Export Label Data'){
	$labelManager->exportCsvFile();
}
else{
	?>

	<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
	<html>
		<head>
		    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
			<title><?php echo $defaultTitle; ?> Default Labels</title>
		    <link type="text/css" href="../../css/main.css" rel="stylesheet" />
			<style type="text/css">
				body {font-family:arial,sans-serif;<?php echo ($floatingWidth?'':'width:560pt;') ?>}
				table {page-break-before:auto;page-break-inside:avoid;}
				td {font-size:10pt;}
				td.lefttd {width:50%;padding:10px 23px 10px 0px;}
				td.righttd {width:50%;padding:10px 0px 10px 23px;}
				p.printbreak {page-break-after:always;}
				.lheader {width:100%; text-align:center; font:bold 14pt arial,sans-serif; margin-bottom:10px;}
				.family {width:100%;text-align:right;}
				.sciname {font-weight:bold;}
				.scientificnamediv {font-size:11pt;}
				.identifiedbydiv {margin-left:15px;}
				.identificationreferences {margin-left:15px;}
				.identificationremarks {margin-left:15px;}
				.loc1div {font-size:11pt;}
				.country {font-weight:bold;}
				.stateprovince {font-weight:bold;}
				.county {font-weight:bold;}
				.associatedtaxa {font-style:italic;}
				.collectordiv {margin-top:10px;}
				.recordnumber {margin-left:10px;}
				.associatedcollectors {margin-left:15px;clear:both;}
				.cnbarcode {width:100%; text-align:center;}
				.lfooter {width:100%; text-align:center; font:bold 12pt arial,sans-serif; padding-top:10px;clear:both;}
				.barcodeonly {width:220px; height:50px; float:left;padding:10px; text-align:center; }
				.symbbarcode {width:100%; text-align:center;margin-top:10px}
			</style>
		</head>
		<body>
			<div>
				<?php 
				if($isEditor){
					if($action){
						$rs = $labelManager->getLabelRecordSet($occIdArr);
						$labelCnt = 0;
						while($r = $rs->fetch_object()){
							if($barcodeOnly){
								if($r->catalognumber){
									?>
									<div class="barcodeonly">
										<img src="getBarcodeCode39.php?bcheight=30&bctext=<?php echo $r->catalognumber; ?>" /><br/>
										<?php echo strtoupper($r->catalognumber); ?>
									</div>
									<?php 
								}
							}
							else{
								$midStr = '';
								if($hMid == 1){
									$midStr = $r->country;
								}
								elseif($hMid == 2){
									$midStr = $r->stateprovince;
								}
								elseif($hMid == 3){
									$midStr = $r->county;
								}
								elseif($hMid == 4){
									$midStr = $r->family;
								}
								$headerStr = $hPrefix.$midStr.$hSuffix;
								
								$dupCnt = $_POST['q-'.$r->occid];
								for($i = 0;$i < $dupCnt;$i++){
									$labelCnt++;
									if($labelCnt%2) echo '<table><tr>'."\n";
									?>
									<td class="<?php echo (($labelCnt%2)?"lefttd":"righttd"); ?>" valign="top">
										<div class="lheader">
											<?php echo $headerStr; ?>
										</div>
										<?php if($hMid != 4) echo '<div class="family">'.$r->family.'</div>'; ?>
										<div class="scientificnamediv">
											<?php 
											if($r->identificationqualifier) echo '<span class="identificationqualifier">'.$r->identificationqualifier.'</span> ';
											$scinameStr = $r->sciname;
											$scinameStr = str_replace(' subsp. ','</i> subsp. <i>',$scinameStr);
											$scinameStr = str_replace(' ssp. ','</i> ssp. <i>',$scinameStr);
											$scinameStr = str_replace(' var. ','</i> var. <i>',$scinameStr);
											?>
											<span class="sciname">
												<i><?php echo $scinameStr; ?></i>
											</span>&nbsp;&nbsp;
											<span class="scientificnameauthorship"><?php echo $r->scientificnameauthorship; ?></span>
										</div>
										<?php 
										if($r->identifiedby){
											?>
											<div class="identifiedbydiv">
												Det by: 
												<span class="identifiedby"><?php echo $r->identifiedby; ?></span> 
												<span class="dateidentified"><?php echo $r->dateidentified; ?></span>
											</div>
											<?php
											if($r->identificationreferences || $r->identificationremarks){
												?>
												<div class="identificationreferences">
													<?php echo $r->identificationreferences; ?>
												</div>
												<div class="identificationremarks">
													<?php echo $r->identificationremarks; ?>
												</div>
												<?php 
											}
										} 
										?>
										<div class="loc1div" style="margin-top:10px;">
											<span class="country"><?php echo $r->country.($r->country?', ':''); ?></span> 
											<span class="stateprovince"><?php echo $r->stateprovince.($r->stateprovince?', ':''); ?></span>
											<?php 
											$countyStr = trim($r->county);
											if($countyStr){
												if(!stripos($r->county,' County') && !stripos($r->county,' Parish')) $countyStr .= ' County';
												$countyStr .= ', ';
											}
											?> 
											<span class="county"><?php echo $countyStr; ?></span> 
											<span class="municipality"><?php echo $r->municipality.($r->municipality?', ':''); ?></span>
											<span class="locality">
												<?php
												$locStr = trim($r->locality);
												if(substr($locStr,-1) != '.'){
													$locStr .= '.';
												}
												echo $locStr;
												?>
											</span>
										</div>
										<?php
										if($r->decimallatitude || $r->verbatimcoordinates){ 
											?>
											<div class="loc2div">
												<?php 
												if($r->verbatimcoordinates){ 
													?>
													<span class="verbatimcoordinates">
														<?php echo $r->verbatimcoordinates; ?>
													</span>
													<?php
												}
												else{
													echo '<span class="decimallatitude">'.$r->decimallatitude.'</span>'.($r->decimallatitude>0?'N':'S');
													echo '<span class="decimallongitude" style="margin-left:10px;">'.$r->decimallongitude.'</span>'.($r->decimallongitude>0?'E':'W').' ';
												}
												if($r->coordinateuncertaintyinmeters) echo '<span style="margin-left:10px;">+-'.$r->coordinateuncertaintyinmeters.' meters</span>';
												if($r->geodeticdatum) echo '<span style="margin-left:10px;">['.$r->geodeticdatum.']</span>'; 
												?>
											</div>
											<?php
										}
										if($r->minimumelevationinmeters){ 
											?>
											<div class="elevdiv">
												Elev: 
												<?php 
												echo '<span class="minimumelevationinmeters">'.$r->minimumelevationinmeters.'</span>'.
												($r->maximumelevationinmeters?' - <span class="maximumelevationinmeters">'.$r->maximumelevationinmeters.'<span>':''),'m. ';
												if($r->verbatimelevation) echo ' ('.$r->verbatimelevation.')'; 
												?>
											</div>
											<?php
										}
										if($r->habitat){
											?>
											<div class="habitat">
												<?php
												$habStr = trim($r->habitat);
												if(substr($habStr,-1) != '.'){
													$habStr .= '.';
												} 
												echo $habStr; 
												?> 
											</div>
											<?php 
										}
										if($r->verbatimattributes || $r->establishmentmeans){
											?>
											<div>
												<span class="verbatimattributes"><?php echo $r->verbatimattributes; ?></span>
												<?php echo ($r->verbatimattributes && $r->establishmentmeans?'; ':''); ?>
												<span class="establishmentmeans">
													<?php echo $r->establishmentmeans; ?>
												</span>
											</div>
											<?php 
										}
										if($r->associatedtaxa){
											?>
											<div>
												Associated species: 
												<span class="associatedtaxa"><?php echo $r->associatedtaxa; ?></span>
											</div>
											<?php 
										}
										if($r->occurrenceremarks){
											?>
											<div class="occurrenceremarks"><?php echo $r->occurrenceremarks; ?></div>
											<?php 
										}
										?>
										<div class="collectordiv">
											<div class="collectordiv1" style="float:left;">
												<span class="recordedby"><?php echo $r->recordedby; ?></span> 
												<span class="recordnumber"><?php echo $r->recordnumber; ?></span> 
											</div>
											<div class="collectordiv2" style="float:right;">
												<span class="eventdate"><?php echo $r->eventdate; ?></span>
											</div>
											<?php 
											if($r->associatedcollectors){
												?>
												<div class="associatedcollectors" style="clear:both;margin-left:10px;">
													With: <?php echo $r->associatedcollectors; ?>
												</div>
												<?php 
											}
											?>
										</div>
										<?php 
										if($r->othercatalognumbers){
											?>
											<div class="othercatalognumbers" style="clear:both;">
												<?php echo $r->othercatalognumbers; ?>
											</div>
											<?php 
										}
										if($useBarcode && $r->catalognumber){
											?>
											<div class="cnbarcode" style="clear:both;padding-top:15px;">
												<img src="getBarcodeCode39.php?bcheight=30&bctext=<?php echo $r->catalognumber; ?>" /><br/>
												<?php echo strtoupper($r->catalognumber); ?>
											</div>
											<?php 
										}
										?>
										<div class="lfooter"><?php echo $lFooter; ?></div>
										<?php 
										if($useSymbBarcode){
											?>
											<hr style="border:dashed;" />
											<div class="symbbarcode" style="padding:10px;">
												<img src="getBarcodeCode39.php?bcheight=30&bctext=<?php echo $r->occid; ?>" /><br/>
												<?php echo strtoupper($r->occid); ?>
											</div>
											<?php 
										}
										?>
									</td> 
									<?php
									if($labelCnt%2 == 0){
										echo '</tr></table>'."\n";
										if($rowsPerPage && ($labelCnt/2)%$rowsPerPage == 0){
											echo '<p class="printbreak"></p>'."\n";
										}
									}
								}
							}
						}
						if($labelCnt%2){
							echo '<td class="righttd"></td></tr></table>'; //If label count is odd, close final labelrowdiv
						} 
						$rs->close();
					}
				}
				?>
			</div>
		</body>
	</html>
	<?php 
}
?>