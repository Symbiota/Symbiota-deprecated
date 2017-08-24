<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceLabel.php');
header("Content-Type: text/html; charset=".$charset);

$collid = $_POST["collid"];
$lHeader = $_POST['lheading'];
$lFooter = $_POST['lfooter'];
$detIdArr = $_POST['detid'];
$speciesAuthors = ((array_key_exists('speciesauthors',$_POST) && $_POST['speciesauthors'])?1:0);
$clearQueue = ((array_key_exists('clearqueue',$_POST) && $_POST['clearqueue'])?1:0);
$action = array_key_exists('submitaction',$_POST)?$_POST['submitaction']:'';
$rowsPerPage = 3;

$labelManager = new OccurrenceLabel();
$labelManager->setCollid($collid);

$isEditor = 0;
if($symbUid){
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collid,$userRights["CollAdmin"])) || (array_key_exists("CollEditor",$userRights) && in_array($collid,$userRights["CollEditor"]))){
		$isEditor = 1;
	}
}
?>
<html>
	<head>
		<title><?php echo $defaultTitle; ?> Default Annotations</title>
		<style type="text/css">
			body {font-family:arial,sans-serif;}
			table.labels {page-break-before:auto;page-break-inside:avoid;border-spacing:5px;}
			table.labels td {width:<?php echo ($rowsPerPage==1?'600px':(100/$rowsPerPage).'%'); ?>;border:1px solid black;padding:8px;}
			p.printbreak {page-break-after:always;}
			.lheader {width:100%;margin-bottom:5px;text-align:center;font:bold 9pt arial,sans-serif;}
			.scientificnamediv {clear:both;font-size:10pt;}
			.identifiedbydiv {float:left;font-size:8pt;margin-top:5px;}
			.dateidentifieddiv {float:left;font-size:8pt;}
			.identificationreferences {clear:both;font-size:8pt;margin-top:5px;}
			.identificationremarks {clear:both;font-size:8pt;margin-top:5px;}
			.lfooter {clear:both;width:100%;text-align:center;font:bold 9pt arial,sans-serif;margin-top:18px;}
		</style>
	</head>
	<body style="background-color:#ffffff;">
		<div>
			<?php 
			if($isEditor){
				if($action){
					$labelArr = $labelManager->getAnnoArray($_POST['detid'], $speciesAuthors);
					if($clearQueue){
						$labelManager->clearAnnoQueue($_POST['detid']);
					}
					$labelCnt = 0;
					foreach($labelArr as $occid => $occArr){
						$headerStr = trim($lHeader);
						$footerStr = trim($lFooter);
						
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
								?>
								<div class="scientificnamediv">
									<?php 
									if($occArr['identificationqualifier']) echo '<span class="identificationqualifier">'.$occArr['identificationqualifier'].'</span> ';
									$scinameStr = $occArr['sciname'];
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
								if($occArr['identificationremarks']){
									?>
									<div class="identificationremarks"><?php echo $occArr['identificationremarks']; ?></div>
									<?php 
								}
								if($occArr['identificationreferences']){
									?>
									<div class="identificationreferences"><?php echo $occArr['identificationreferences']; ?></div>
									<?php 
								}
								if($occArr['identifiedby'] || $occArr['dateidentified']){
									if($occArr['identifiedby']){
										?>
										<div class="identifiedbydiv">
											Determiner: <?php echo $occArr['identifiedby']; ?>
										</div>
										<?php
										if($occArr['dateidentified']){
											echo '<br />';
										}
									}
									if($occArr['dateidentified']){
										?>
										<div class="dateidentifieddiv">
											Date: <?php echo $occArr['dateidentified']; ?>
										</div>
										<?php
									}
								} 
								if($footerStr){
									?>
									<div class="lfooter">
										<?php echo $footerStr; ?>
									</div>
									<?php
								}
								?>
							</td> 
							<?php
							if($labelCnt%$rowsPerPage == 0){
								echo '</tr></table>'."\n";
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