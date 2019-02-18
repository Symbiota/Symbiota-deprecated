<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceLabel.php');
header("Content-Type: text/html; charset=".$charset);

$collid = $_POST["collid"];
$hPrefix = $_POST['lhprefix'];
$hMid = $_POST['lhmid'];
$hSuffix = $_POST['lhsuffix'];
$lFooter = $_POST['lfooter'];
$occIdArr = $_POST['occid'];
$rowsPerPage = $_POST['rpp'];
$speciesAuthors = ((array_key_exists('speciesauthors',$_POST) && $_POST['speciesauthors'])?1:0);
$showcatalognumbers = ((array_key_exists('catalognumbers',$_POST) && $_POST['catalognumbers'])?1:0);
$useBarcode = array_key_exists('bc',$_POST)?$_POST['bc']:0;
$useSymbBarcode = array_key_exists('symbbc',$_POST)?$_POST['symbbc']:0;
$barcodeOnly = array_key_exists('bconly',$_POST)?$_POST['bconly']:0;
$action = array_key_exists('submitaction',$_POST)?$_POST['submitaction']:'';

$labelManager = new OccurrenceLabel();
$labelManager->setCollid($collid);

$isEditor = 0;
if($symbUid){
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collid,$userRights["CollAdmin"])) || (array_key_exists("CollEditor",$userRights) && in_array($collid,$userRights["CollEditor"]))){
		$isEditor = 1;
	}
}
if($action == 'Export to CSV'){
	$labelManager->exportCsvFile($_POST, $speciesAuthors);
}
else{
	?>
	<html>
		<head>
			<title><?php echo $defaultTitle; ?> Default Labels</title>
			<style type="text/css">
				body {font-family:arial,sans-serif;}
				table.labels {table-layout:fixed;width:100%;page-break-before:auto;page-break-inside:avoid;}
				table.labels td {width:<?php echo ($rowsPerPage==1?'600px':(100/$rowsPerPage).'%'); ?>;font-size:10pt;}
				<?php
				if($rowsPerPage!=1){
					?>
					table.labels td:first-child {padding:10px 23px 10px 0px;}
					table.labels td:not(:first-child):not(:last-child) {padding:10px 23px 10px 23px;}
					table.labels td:last-child {padding:10px 0px 10px 23px;}
					<?php
				}
				?>
				p.printbreak {page-break-after:always;}
				.lheader {width:100%; text-align:center; font:bold 14pt arial,sans-serif; margin-bottom:10px;}
				.family {width:100%;text-align:right;}
				.scientificnamediv {font-size:11pt;}
				.identifiedbydiv {margin-left:15px;}
				.identificationreferences {margin-left:15px;}
				.identificationremarks {margin-left:15px;}
				.taxonremarks {margin-left:15px;}
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
		<body style="background-color:#ffffff;">
			<div>
				<?php 
				if($isEditor){
					if($action){
						$labelArr = $labelManager->getLabelArray($_POST['occid'], $speciesAuthors);
						$labelCnt = 0;
						foreach($labelArr as $occid => $occArr){
							if($barcodeOnly){
								if($occArr['catalognumber']){
									?>
									<div class="barcodeonly">
										<img src="getBarcode.php?bcheight=40&bctext=<?php echo $occArr['catalognumber']; ?>" />
									</div>
									<?php 
								}
							}
							else{
								$midStr = '';
								if($hMid == 1){
									$midStr = $occArr['country'];
								}
								elseif($hMid == 2){
									$midStr = $occArr['stateprovince'];
								}
								elseif($hMid == 3){
									$midStr = $occArr['county'];
								}
								elseif($hMid == 4){
									$midStr = $occArr['family'];
								}
								$headerStr = '';
								if($hPrefix || $midStr || $hSuffix){
									$headerStrArr = array();
									$headerStrArr[] = trim($hPrefix);
									$headerStrArr[] = trim($midStr);
									$headerStrArr[] = trim($hSuffix);
									$headerStr = implode(" ",$headerStrArr);
								}
								
								$dupCnt = $_POST['q-'.$occid];
								for($i = 0;$i < $dupCnt;$i++){
									$labelCnt++;
									if($rowsPerPage == 1 || $labelCnt%$rowsPerPage == 1) echo '<table class="labels"><tr>'."\n";
									?>
									<td class="" valign="top">
										<?php
										if($headerStr){
											?>
											<div class="lheader">
												<?php echo $headerStr; ?>
											</div>
											<?php
										}
										if($hMid != 4) echo '<div class="family">'.$occArr['family'].'</div>'; ?>
										<div class="scientificnamediv">
											<?php 
											if($occArr['identificationqualifier']) echo '<span class="identificationqualifier">'.$occArr['identificationqualifier'].'</span> ';
											$scinameStr = $occArr['scientificname'];
											$parentAuthor = (array_key_exists('parentauthor',$occArr)?' '.$occArr['parentauthor']:'');
											$scinameStr = str_replace(' sp. ','</i></b>'.$parentAuthor.' <b>sp.</b>',$scinameStr);
											$scinameStr = str_replace(' subsp. ','</i></b>'.$parentAuthor.' <b>subsp. <i>',$scinameStr);
											$scinameStr = str_replace(' ssp. ','</i></b>'.$parentAuthor.' <b>ssp. <i>',$scinameStr);
											$scinameStr = str_replace(' var. ','</i></b>'.$parentAuthor.' <b>var. <i>',$scinameStr);
											$scinameStr = str_replace(' variety ','</i></b>'.$parentAuthor.' <b>var. <i>',$scinameStr);
											$scinameStr = str_replace(' Variety ','</i></b>'.$parentAuthor.' <b>var. <i>',$scinameStr);
											$scinameStr = str_replace(' v. ','</i></b>'.$parentAuthor.' <b>var. <i>',$scinameStr);
											$scinameStr = str_replace(' f. ','</i></b>'.$parentAuthor.' <b>f. <i>',$scinameStr);
											$scinameStr = str_replace(' cf. ','</i></b>'.$parentAuthor.' <b>cf. <i>',$scinameStr);
											$scinameStr = str_replace(' aff. ','</i></b>'.$parentAuthor.' <b>aff. <i>',$scinameStr);
											?>
											<span class="sciname">
												<b><i><?php echo $scinameStr; ?></i></b>
											</span>
											<span class="scientificnameauthorship"><?php echo $occArr['scientificnameauthorship']; ?></span>
										</div>
										<?php 
										if($occArr['identifiedby']){
											?>
											<div class="identifiedbydiv">
												Det by: 
												<span class="identifiedby"><?php echo $occArr['identifiedby']; ?></span> 
												<span class="dateidentified"><?php echo $occArr['dateidentified']; ?></span>
											</div>
											<?php
											if($occArr['identificationreferences'] || $occArr['identificationremarks'] || $occArr['taxonremarks']){
												?>
												<div class="identificationreferences">
													<?php echo $occArr['identificationreferences']; ?>
												</div>
												<div class="identificationremarks">
													<?php echo $occArr['identificationremarks']; ?>
												</div>
												<div class="taxonremarks">
													<?php echo $occArr['taxonremarks']; ?>
												</div>
												<?php 
											}
										} 
										?>
										<div class="loc1div" style="margin-top:10px;">
											<span class="country"><?php echo $occArr['country'].($occArr['country']?', ':''); ?></span> 
											<span class="stateprovince"><?php echo $occArr['stateprovince'].($occArr['stateprovince']?', ':''); ?></span>
											<?php 
											$countyStr = trim($occArr['county']);
											if($countyStr){
												if(!stripos($occArr['county'],' County') && !stripos($occArr['county'],' Parish')) $countyStr .= ' County';
												$countyStr .= ', ';
											}
											?> 
											<span class="county"><?php echo $countyStr; ?></span> 
											<span class="municipality"><?php echo $occArr['municipality'].($occArr['municipality']?', ':''); ?></span>
											<span class="locality">
												<?php
												$locStr = trim($occArr['locality']);
												if(substr($locStr,-1) != '.'){
													$locStr .= '.';
												}
												echo $locStr;
												?>
											</span>
										</div>
										<?php
										if($occArr['decimallatitude'] || $occArr['verbatimcoordinates']){ 
											?>
											<div class="loc2div">
												<?php 
												if($occArr['verbatimcoordinates']){ 
													?>
													<span class="verbatimcoordinates">
														<?php echo $occArr['verbatimcoordinates']; ?>
													</span>
													<?php
												}
												else{
													echo '<span class="decimallatitude">'.$occArr['decimallatitude'].'</span>'.($occArr['decimallatitude']>0?'N':'S');
													echo '<span class="decimallongitude" style="margin-left:10px;">'.$occArr['decimallongitude'].'</span>'.($occArr['decimallongitude']>0?'E':'W').' ';
												}
												if($occArr['coordinateuncertaintyinmeters']) echo '<span style="margin-left:10px;">+-'.$occArr['coordinateuncertaintyinmeters'].' meters</span>';
												if($occArr['geodeticdatum']) echo '<span style="margin-left:10px;">['.$occArr['geodeticdatum'].']</span>'; 
												?>
											</div>
											<?php
										}
										if($occArr['minimumelevationinmeters']){ 
											?>
											<div class="elevdiv">
												Elev: 
												<?php 
												echo '<span class="minimumelevationinmeters">'.$occArr['minimumelevationinmeters'].'</span>'.
												($occArr['maximumelevationinmeters']?' - <span class="maximumelevationinmeters">'.$occArr['maximumelevationinmeters'].'<span>':''),'m. ';
												if($occArr['verbatimelevation']) echo ' ('.$occArr['verbatimelevation'].')'; 
												?>
											</div>
											<?php
										}
										if($occArr['habitat']){
											?>
											<div class="habitat">
												<?php
												$habStr = trim($occArr['habitat']);
												if(substr($habStr,-1) != '.'){
													$habStr .= '.';
												} 
												echo $habStr; 
												?> 
											</div>
											<?php 
										}
										if($occArr['substrate']){
											?>
											<div class="substrate">
												<?php
												$substrateStr = trim($occArr['substrate']);
												if(substr($substrateStr,-1) != '.'){
													$substrateStr .= '.';
												} 
												echo $substrateStr; 
												?> 
											</div>
											<?php 
										}
										if($occArr['verbatimattributes'] || $occArr['establishmentmeans']){
											?>
											<div>
												<span class="verbatimattributes"><?php echo $occArr['verbatimattributes']; ?></span>
												<?php echo ($occArr['verbatimattributes'] && $occArr['establishmentmeans']?'; ':''); ?>
												<span class="establishmentmeans">
													<?php echo $occArr['establishmentmeans']; ?>
												</span>
											</div>
											<?php 
										}
										if($occArr['associatedtaxa']){
											?>
											<div>
												Associated species: 
												<span class="associatedtaxa"><?php echo $occArr['associatedtaxa']; ?></span>
											</div>
											<?php 
										}
										if($occArr['occurrenceremarks']){
											?>
											<div class="occurrenceremarks"><?php echo $occArr['occurrenceremarks']; ?></div>
											<?php 
										}
										if($occArr['typestatus']){
											?>
											<div class="typestatus"><?php echo $occArr['typestatus']; ?></div>
											<?php 
										}
										?>
										<div class="collectordiv">
											<div class="collectordiv1" style="float:left;">
												<span class="recordedby"><?php echo $occArr['recordedby']; ?></span> 
												<span class="recordnumber"><?php echo $occArr['recordnumber']; ?></span> 
											</div>
											<div class="collectordiv2" style="float:right;">
												<span class="eventdate"><?php echo $occArr['eventdate']; ?></span>
											</div>
											<?php 
											if($occArr['associatedcollectors']){
												?>
												<div class="associatedcollectors" style="clear:both;margin-left:10px;">
													With: <?php echo $occArr['associatedcollectors']; ?>
												</div>
												<?php 
											}
											?>
										</div>
										<?php 
										if($useBarcode && $occArr['catalognumber']){
											?>
											<div class="cnbarcode" style="clear:both;padding-top:15px;">
												<img src="getBarcode.php?bcheight=40&bctext=<?php echo $occArr['catalognumber']; ?>" />
											</div>
											<?php 
											if($occArr['othercatalognumbers']){
												?>
												<div class="othercatalognumbers" style="clear:both;text-align:center;">
													<?php echo $occArr['othercatalognumbers']; ?>
												</div>
												<?php 
											}
										}
										elseif($showcatalognumbers){
											if($occArr['catalognumber']){
												?>
												<div class="catalognumber" style="clear:both;text-align:center;">
													<?php echo $occArr['catalognumber']; ?>
												</div>
												<?php
											}
											if($occArr['othercatalognumbers']){
												?>
												<div class="othercatalognumbers" style="clear:both;text-align:center;">
													<?php echo $occArr['othercatalognumbers']; ?>
												</div>
												<?php 
											}
										}
										?>
										<div class="lfooter"><?php echo $lFooter; ?></div>
										<?php 
										if($useSymbBarcode){
											?>
											<hr style="border:dashed;" />
											<div class="symbbarcode" style="padding-top:10px;">
												<img src="getBarcode.php?bcheight=40&bctext=<?php echo $occid; ?>" />
											</div>
											<?php 
											if($occArr['catalognumber']){
												?>
												<div class="catalognumber" style="clear:both;text-align:center;">
													<?php echo $occArr['catalognumber']; ?>
												</div>
												<?php
											}
										}
										?>
									</td> 
									<?php
									if($labelCnt%$rowsPerPage == 0){
										echo '</tr></table>'."\n";
									}
								}
							}
						}
						if($labelCnt%$rowsPerPage){
							$remaining = $rowsPerPage-($labelCnt%$rowsPerPage);
							for($i = 0;$i < $remaining;$i++){
								echo '<td></td>';
							}
							echo '</tr></table>'."\n"; //If label count is odd, close final labelrowdiv
						} 
					}
				}
				?>
			</div>
		</body>
	</html>
	<?php 
}
?>