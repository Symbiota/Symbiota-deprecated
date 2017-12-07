<?php
	include_once('../config/symbini.php');
	include_once($SERVER_ROOT.'/classes/ChecklistManager.php');
	header("Content-Type: text/html; charset=".$CHARSET);
	$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:""; 
	$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:0; 
	$clValue = array_key_exists("cl",$_REQUEST)?$_REQUEST["cl"]:0; 
	$dynClid = array_key_exists("dynclid",$_REQUEST)?$_REQUEST["dynclid"]:0;
	$proj = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:"";
	$thesFilter = array_key_exists("thesfilter",$_REQUEST)?$_REQUEST["thesfilter"]:0;
	$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:""; 
	$showAuthors = array_key_exists("showauthors",$_REQUEST)?$_REQUEST["showauthors"]:0; 
	$showCommon = array_key_exists("showcommon",$_REQUEST)?$_REQUEST["showcommon"]:0; 
	$showImages = array_key_exists("showimages",$_REQUEST)?$_REQUEST["showimages"]:0; 
	$showVouchers = array_key_exists("showvouchers",$_REQUEST)?$_REQUEST["showvouchers"]:0; 
	$searchCommon = array_key_exists("searchcommon",$_REQUEST)?$_REQUEST["searchcommon"]:0;
	$searchSynonyms = array_key_exists("searchsynonyms",$_REQUEST)?$_REQUEST["searchsynonyms"]:0;
	
	$clManager = new ChecklistManager();
	if($clValue){
		$clManager->setClValue($clValue);
	}
	elseif($dynClid){
		$clManager->setDynClid($dynClid);
	}
	if($proj) $clManager->setProj($proj);
	if($thesFilter) $clManager->setThesFilter($thesFilter);
	if($taxonFilter) $clManager->setTaxonFilter($taxonFilter);
	if($searchCommon){
		$showCommon = 1;
		$clManager->setSearchCommon();
	}
	if($searchSynonyms) $clManager->setSearchSynonyms();
	if($showAuthors) $clManager->setShowAuthors();
	if($showCommon) $clManager->setShowCommon();
	if($showImages) $clManager->setShowImages();
	if($showVouchers) $clManager->setShowVouchers();

	$clArray = Array();
	if($clValue || $dynClid){
		$clArray = $clManager->getClMetaData();
		$taxaArray = $clManager->getTaxaList(0,99999);
	}
?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<title><?php echo $DEFAULT_TITLE; ?> Research Checklist: <?php echo $clManager->getClName(); ?> print friendly</title>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
</head>
<body>
	<!-- This is inner text! -->
	<div id='innertext'>
		<?php
		if($clValue || $dynClid){
			?>
			<div style="float:left;color:#990000;font-size:20px;font-weight:bold;margin:0px 10px 10px 0px;">
				<?php echo $clManager->getClName(); ?>
			</div>
			<?php
			//Do not show certain fields if Dynamic Checklist ($dynClid)
			if($clValue){
				?>
				<div>
					<span style="font-weight:bold;">
						Authors: 
					</span>
					<?php echo $clArray["authors"]; ?>
				</div>
				<?php 
			}
			?>
			<div>
				<div>
					<h1>Species List</h1>
					<div style="margin:3px;">
						<b>Families:</b> 
						<?php echo $clManager->getFamilyCount(); ?>
					</div>
					<div style="margin:3px;">
						<b>Genera:</b>
						<?php echo $clManager->getGenusCount(); ?>
					</div>
					<div style="margin:3px;">
						<b>Species:</b>
						<?php echo $clManager->getSpeciesCount(); ?>
						(species rank)
					</div>
					<div style="margin:3px;">
						<b>Total Taxa:</b> 
						<?php echo $clManager->getTaxaCount(); ?>
						(including subsp. and var.)
					</div>
					<?php 
					$prevfam = ''; 
					if($showImages){
						foreach($taxaArray as $tid => $sppArr){
							$family = $sppArr['family'];
							if($family != $prevfam){
								?>
								<div class="familydiv" id="<?php echo $family; ?>" style="clear:both;margin-top:10px;">
									<h3><?php echo $family; ?></h3>
								</div>
								<?php
								$prevfam = $family;
							}
							?>
							<div>
								<?php 
								echo "<div style='float:left;text-align:center;width:210px;height:".($showCommon?"260":"240")."px;'>";
								$imgSrc = (array_key_exists('tnurl',$sppArr)&&$sppArr["tnurl"]?$sppArr["tnurl"]:$sppArr["url"]);
								echo "<div class='tnimg' style='".($imgSrc?"":"border:1px solid black;")."'>";
								$spUrl = "../taxa/index.php?taxauthid=1&taxon=$tid&cl=".$clManager->getClid();
								if($imgSrc){
									$imgSrc = (array_key_exists("imageDomain",$GLOBALS)&&substr($imgSrc,0,4)!="http"?$GLOBALS["imageDomain"]:"").$imgSrc;
									echo "<img src='".$imgSrc."' style='height:100%;' />";
								}
								else{
									echo "<div style='margin-top:50px;'><b>Image<br/>not yet<br/>available</b></div>";
								}
								echo "</div>";
								echo "<div><b>".$sppArr["sciname"]."</b></div>";
								echo "</div>\n";
								?>
							</div>
							<?php 
						}
					}
					else{
						foreach($taxaArray as $tid => $sppArr){
							$family = $sppArr['family'];
							if($family != $prevfam){
								?>
								<div class="familydiv" id="<?php echo $family;?>" style="margin-top:30px;">
									<h3><?php echo $family;?></h3>
								</div>
								<?php
								$prevfam = $family;
							}
							echo "<div id='tid-$tid' style='margin-left:10px;'>";
							echo "<div>";
							echo "<b><i>".$sppArr["sciname"]."</b></i> ";
							if(array_key_exists("author",$sppArr)) echo $sppArr["author"];
							echo "</div>\n";
							if(array_key_exists('vern',$sppArr)){
								echo "<div style='margin-left:10px;font-weight:bold;'>".$sppArr["vern"]."</div>";
							}
							if($showVouchers){
								$voucStr = '';
								if(array_key_exists('vouchers',$sppArr)){
									$vArr = $sppArr['vouchers'];
									foreach($vArr as $occid => $collName){
										$voucStr .= ", ".$collName."\n";
									}
									$voucStr = substr($voucStr,2);
								}
								$noteStr = '';
								if(array_key_exists('notes',$sppArr)){
									$noteStr = $sppArr['notes'];
								}
								if($noteStr || $voucStr){
									echo "<div style='margin-left:10px;'>".$noteStr.($noteStr && $voucStr?'; ':'').$voucStr."</div>";
								}
							}
							echo "</div>\n";
						}
					}
					if(!$taxaArray) echo "<h1 style='margin:40px;'>No Taxa Found</h1>";
					?>
				</div>
			</div>
			<?php
		}
		else{
			?>
			<div>
				Checklist identification is null!
			</div>
			<?php 
		}
		?>
	</div>
</body>
</html> 