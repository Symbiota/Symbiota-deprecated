<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonProfileManager.php');
Header("Content-Type: text/html; charset=".$CHARSET);

$descrDisplayLevel;
$taxonValue = array_key_exists("taxon",$_REQUEST)?$_REQUEST["taxon"]:""; 
$taxAuthId = array_key_exists("taxauthid",$_REQUEST)?$_REQUEST["taxauthid"]:1; 
$clValue = array_key_exists("cl",$_REQUEST)?$_REQUEST["cl"]:0;
$projValue = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:0;
$lang = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:$DEFAULT_LANG;
$descrDisplayLevel = array_key_exists("displaylevel",$_REQUEST)?$_REQUEST["displaylevel"]:"";

//if(!$projValue && !$clValue) $projValue = $defaultProjId;

$taxonManager = new TaxonProfileManager();
if($taxAuthId || $taxAuthId === "0") {
	$taxonManager->setTaxAuthId($taxAuthId);
}
if($clValue) $taxonManager->setClName($clValue);
if($projValue) $taxonManager->setProj($projValue);
if($lang) $taxonManager->setLanguage($lang);
if($taxonValue) {
	$taxonManager->setTaxon($taxonValue);
	$taxonManager->setAttributes();
}
$spDisplay = $taxonManager->getDisplayName();
$taxonRank = $taxonManager->getRankId();
$links = $taxonManager->getTaxaLinks();
if($links){
	foreach($links as $linkKey => $linkUrl){
		if($linkUrl['title'] == 'REDIRECT'){
			$locUrl = str_replace('--SCINAME--',urlencode($taxonManager->getSciName()),$linkUrl['url']);
			header('Location: '.$locUrl);
			exit;
		}
	}
}

$displayLocality = 0;
$isEditor = false;
if($symbUid){
	if($isAdmin || array_key_exists("TaxonProfile",$userRights)){
		$isEditor = true;
	}
	if($isAdmin || array_key_exists("CollAdmin",$userRights) || array_key_exists("RareSppAdmin",$userRights) || array_key_exists("RareSppReadAll",$userRights)){
		$displayLocality = 1;
	}
}
if($taxonManager->getSecurityStatus() == 0){
	$displayLocality = 1;
}
$taxonManager->setDisplayLocality($displayLocality);
$descr = Array();
?>

<html>
<head>
	<title><?php echo $DEFAULT_TITLE." - ".$spDisplay; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
	<meta name='keywords' content='<?php echo $spDisplay; ?>' />
	<link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/speciesprofilebase.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/speciesprofile.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/jquery-ui.css" type="text/css" rel="Stylesheet" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript">
		<?php include_once($SERVER_ROOT.'/config/googleanalytics.php'); ?>
	</script>
	<script type="text/javascript">
		var currentLevel = <?php echo ($descrDisplayLevel?$descrDisplayLevel:"1"); ?>;
		var levelArr = new Array(<?php echo ($descr?"'".implode("','",array_keys($descr))."'":""); ?>);
		var tid = <?php echo $taxonManager->getTid(); ?>
	</script>
	<script type="text/javascript" src="../js/symb/taxa.index.js?ver=20160527"></script>
	<script type="text/javascript" src="../js/symb/taxa.editor.js?ver=20140619"></script>
</head>
<body>
<?php
$displayLeftMenu = (isset($taxa_indexMenu)?$taxa_indexMenu:false);
include($SERVER_ROOT.'/header.php');
if(isset($taxa_indexCrumbs)){
	echo "<div class='navpath'>";
	echo $taxa_indexCrumbs;
	echo " <b>$spDisplay</b>";
	echo "</div>";
}
?>
<table id="innertable">
<?php 
if($taxonManager->getSciName() != "unknown"){
	if($taxonRank > 180){
		?>
		<tr>
			<td colspan="2" valign="bottom" height="35px">
			<div style='float:left;font-size:16px;margin-left:10px;'>
				<span style='font-weight:bold;color:#990000;'>
					<i><?php echo $spDisplay; ?></i>
				</span> 
				<?php echo $taxonManager->getAuthor(); ?>
				<?php 
				$parentLink = "index.php?taxon=".$taxonManager->getParentTid()."&cl=".$taxonManager->getClid()."&proj=".$projValue."&taxauthid=".$taxAuthId;
				echo "&nbsp;<a href='".$parentLink."'><img border='0' height='10px' src='../images/toparent.png' title='Go to Parent' /></a>";
			 	//If submitted tid does not equal accepted tid, state that user will be redirected to accepted
			 	if(($taxonManager->getTid() != $taxonManager->getSubmittedTid()) && $taxAuthId){
			 		echo "<span style='font-size:90%;margin-left:25px;'> (redirected from: <i>".$taxonManager->getSubmittedSciName()."</i>)</span>"; 
			 	}
			 	?>
			</div>
			<?php 
			if($isEditor){
				?>
				<div style="float:right;">
					<a href='admin/tpeditor.php?tid=<?php echo $taxonManager->getTid(); ?>' title='Edit Taxon Data'>
						<img style='border:0px;' src='../images/edit.png'/>
					</a>
				</div>
				<?php 
			}	
			if($links && $links[0]['sortseq'] == 1){
				$uStr = str_replace('--SCINAME--',urlencode($taxonManager->getSciName()),$links[0]['url']);
				?>
				<div style="margin-left:25px;clear:both;">
					<?php 

					?>
					Go to <a href="<?php echo $uStr; ?>" target="_blank"><?php echo $links[0]['title']; ?></a>...
				</div>
				<?php 
			}
			?>
			</td>
		</tr>
		<tr>
			<td width='300' valign='top'>
				<div id='family' style='margin-left:25px;'>
					<b>Family:</b> 
					<?php echo $taxonManager->getFamily(); ?>
				</div>
		<?php 
		$vernStr = $taxonManager->getVernacularStr();
		if($vernStr){
			?>
			<div id='vernaculars' style='margin-left:10px;margin-top:0.5em;font-size:130%;' title='Common Names'>
				<?php echo $vernStr; ?>
			</div>
			<?php 
		}
		
		$synStr = $taxonManager->getSynonymStr();
		if($synStr){
			echo "\t<div id='synonyms' style='margin-left:20px;margin-top:0.5em;' title='Synonyms'>[";
			echo $synStr;
			echo"]</div>\n";
		}
		
		if(!$taxonManager->echoImages(0,1,0)){
			echo "<div class='image' style='width:260px;height:260px;border-style:solid;margin-top:5px;margin-left:20px;text-align:center;'>";
			if($isEditor){
				echo "<a href='admin/tpeditor.php?category=imageadd&tid=".$taxonManager->getTid()."'><b>Add an Image</b></a>";
			}
			else{
				echo "<br/><br/><br/><br/><br/><br/>Images<br/>not yet<br/>available";
			}
			echo "</div>";
		}
		?>
			</td>
			</td>
			<td class="desc">
				<?php 
				//Middle Right Section (Description section)
				if($descArr = $taxonManager->getDescriptions()){
					?>
					<div id='desctabs'>
						<ul>
							<?php 
							$capCnt = 1;
							foreach($descArr as $dArr){
								foreach($dArr as $id => $vArr){
									$cap = $vArr["caption"];
									if(!$cap){
										$cap = "Description #".$capCnt;
										$capCnt++;
									}
									echo "<li><a href='#tab".$id."' class='selected'>".$cap."</a></li>\n";
								}
							}
							?>
						</ul>
						<?php 
						foreach($descArr as $dArr){
							foreach($dArr as $id => $vArr){
								?>
								<div id='tab<?php echo $id; ?>' class="sptab" style="width:94%;">
									<?php 
									if($vArr["source"]){
										echo "<div id='descsource' style='float:right;'>";
										if($vArr["url"]){
											echo "<a href='".$vArr["url"]."' target='_blank'>";
										}
										echo $vArr["source"];
										if($vArr["url"]){
											echo "</a>";
										}
										echo "</div>\n";
									}
									$descArr = $vArr["desc"];
									?>
									<div style='clear:both;'>
										<?php 
										foreach($descArr as $tdsId => $stmt){
											echo $stmt." ";
										}
										//if($this->clInfo){
											//echo "<div id='clinfo'><b>Local Notes:</b> ".$clInfo."</div>";
										//}
										?>
									</div>
								</div>
								<?php 
							}
						}
						?>
					</div>
					<?php
				}
				else{
					echo "<div style='margin:70px 0px 20px 50px '>Description Not Yet Available</div>";
				}
				?>
			</td>
		</tr>
		<tr>
			<td colspan="2">
				<div id="img-div" style="height:300px;overflow:hidden;">
					<?php 
					//Map
					$url = ''; $aUrl = ''; $gAnchor = '';
					if($occurrenceModIsActive && $displayLocality){
						$gAnchor = "openMapPopup('".$taxonManager->getTid()."',".($taxonManager->getClid()?$taxonManager->getClid():0).")";
					}
					if($mapSrc = $taxonManager->getMapArr()){
						$url = array_shift($mapSrc);
						$aUrl = $url;
					}
					elseif($gAnchor){
						$url = $taxonManager->getGoogleStaticMap();
					}
					if($url){
						echo '<div class="mapthumb" style="float:right;height:250px">';
						if($gAnchor){
							echo '<a href="#" onclick="'.$gAnchor.';return false">';
						}
						elseif($aUrl){
							echo '<a href="'.$aUrl.'">';
						}
						echo '<img src="'.$url.'" title="'.$spDisplay.' dot map" alt="'.$spDisplay.' dot map" />';
						if($aUrl || $gAnchor) echo '</a>';
						if($gAnchor) echo '<br /><a href="#" onclick="'.$gAnchor.';return false">Open Interactive Map</a>';
						echo '</div>';
					}
					$taxonManager->echoImages(1);
					?>
				</div>
				<div id="img-tab-div" style="display:none;border-top:2px solid gray;margin-top:2px;">
					<a href="#" onclick="expandExtraImages();return false;">
						<div style="background:#eee;padding:10px;border: 1px solid #ccc;width:100px;margin:auto;text-align:center">
							More Images
						</div>
					</a>
				</div>
			</td>
		</tr>
		<?php 
	}
	else{
		?>
		<tr>
			<td style="width:250px;vertical-align:top;">
				<?php 
				$displayName = $spDisplay;
				if($taxonRank == 180) $displayName = '<i>'.$displayName.'</i> spp. ';
				if($taxonRank > 140){
					$parentLink = "index.php?taxon=".$taxonManager->getParentTid()."&cl=".$taxonManager->getClid()."&proj=".$projValue."&taxauthid=".$taxAuthId;
					$displayName .= ' <a href="'.$parentLink.'">';
					$displayName .= '<img border="0" height="10px" src="../images/toparent.png" title="Go to Parent" />';
					$displayName .= '</a>';
				}
				echo "<div style='font-size:16px;margin-top:15px;margin-left:10px;font-weight:bold;'>$displayName</div>\n";
				if($taxonRank > 140) echo "<div id='family' style='margin-top:3px;margin-left:20px;'><b>Family:</b> ".$taxonManager->getFamily()."</div>\n";
				if($projValue) echo "<div style='margin-top:3px;margin-left:20px;'><b>Project:</b> ".$taxonManager->getProjName()."</div>\n";
				if(!$taxonManager->echoImages(0,1,0)){
					echo "<div class='image' style='width:260px;height:260px;border-style:solid;margin-top:5px;margin-left:20px;text-align:center;'>";
					if($isEditor){
						echo "<a href='admin/tpeditor.php?category=imageadd&tid=".$taxonManager->getTid()."'><b>Add an Image</b></a>";
					}
					else{
						echo "<br/><br/><br/><br/><br/><br/>Images<br/>not yet<br/>available";
					}
					echo "</div>";
				}
				?>
			</td>
			<td>
				<?php 
				if($isEditor){
					?>
					<div style='float:right;'>
						<a href="admin/tpeditor.php?tid=<?php echo $taxonManager->getTid(); ?>" title="Edit Taxon Data">
							<img style='border:0px;' src='../images/edit.png'/>
						</a>
					</div>
					<?php 
				}
				//Display description
				if($descriptionArr = $taxonManager->getDescriptions()){
					?>
					<div id="desctabs" style="margin:10px;clear:both;">
						<ul>
							<?php 
							$capCnt = 1;
							foreach($descriptionArr as $dArr){
								foreach($dArr as $k => $vArr){
									$cap = $vArr["caption"];
									if(!$cap) $cap = "Description #".$capCnt;
									echo "<li><a href='#tab".$k."'>".$cap."</a></li>\n";
									$capCnt++;
								}
							}
							?>
						</ul>
						<?php 
						foreach($descriptionArr as $dArr){
							foreach($dArr as $k => $vArr){
								?>
								<div id='tab<?php echo $k; ?>' class='spptab' style='width:94%;'>
									<?php 
									if($vArr["source"]){
										echo "<div id='descsource' style='float:right;'>";
										if($vArr["url"]){
											echo "<a href='".$vArr["url"]."' target='_blank'>";
										}
										echo $vArr["source"];
										if($vArr["url"]){
											echo "</a>";
										}
										echo "</div>\n";
									}
									$descArr = $vArr["desc"];
									?>
									<div style='clear:both;'>
										<?php 
										foreach($descArr as $tdsId => $stmt){
											echo $stmt." ";
										}
										//if($this->clInfo){
											//echo "<div id='clinfo'><b>Local Notes:</b> ".$clInfo."</div>";
										//}
										?>
									</div>
								</div>
								<?php 
							}
						}
						?>
					</div>
					<?php
				}
				else{
					echo "<div style='margin:70px 0px 20px 50px '>Description Not Yet Available</div>";
				}
				?>
			</td>
		</tr>

		<tr>
			<td colspan="2">
				<fieldset style="padding:10px 2px 10px 2px;">
					<?php 
					if($clValue){
						echo "<legend>";
						echo "Species within <b>".$taxonManager->getClName()."</b>&nbsp;&nbsp;";
						if($taxonManager->getParentClid()){
							echo "<a href='index.php?taxon=$taxonValue&cl=".$taxonManager->getParentClid()."&taxauthid=".$taxAuthId."' title='Go to ".$taxonManager->getParentName()." checklist'><img style='border:0px;width:10px;' src='../images/toparent.png'/></a>";
						}
						echo "</legend>";
					}
					?>
					<div>
					<?php 
					if($sppArr = $taxonManager->getSppArray()){
						$cnt = 0;
						ksort($sppArr);
						foreach($sppArr as $sciNameKey => $subArr){
							if($cnt%5 == 0 && $cnt > 0){
								echo "<div style='clear:both;'><hr></div>";
							}
							echo "<div class='spptaxon'>";
							echo "<div style='margin-top:10px;'>";
							echo "<a href='index.php?taxon=".$subArr["tid"]."&taxauthid=".$taxAuthId.($clValue?"&cl=".$clValue:"")."'>";
							echo "<i>".$sciNameKey."</i>";
							echo "</a></div>\n";
							echo "<div class='sppimg' style='overflow:hidden;'>";
		
							if(array_key_exists("url",$subArr)){
								$imgUrl = $subArr["url"];
								if(array_key_exists("imageDomain",$GLOBALS) && substr($imgUrl,0,1)=="/"){
									$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
								}
								echo "<a href='index.php?taxon=".$subArr["tid"]."&taxauthid=".$taxAuthId.($clValue?"&cl=".$clValue:"")."'>";
		
								if($subArr["thumbnailurl"]){
									$imgUrl = $subArr["thumbnailurl"];
									if(array_key_exists("imageDomain",$GLOBALS) && substr($subArr["thumbnailurl"],0,1)=="/"){
										$imgUrl = $GLOBALS["imageDomain"].$subArr["thumbnailurl"];
									}
								}
								echo "<img src='".$imgUrl."' title='".$subArr["caption"]."' alt='Image of ".$sciNameKey."' style='z-index:-1' />";
								echo "</a>\n";
								echo "<div style='text-align:right;position:relative;top:-26px;left:5px;' title='Photographer: ".$subArr["photographer"]."'>";
								echo "</div>";
							}
							elseif($isEditor){
								echo "<div class='spptext'><a href='admin/tpeditor.php?category=imageadd&tid=".$subArr["tid"]."'>Add an Image!</a></div>";
							}
							else{
								echo "<div class='spptext'>Image<br/>Not Available</div>";
							}
							echo "</div>\n";

							//Display thumbnail map
							if(array_key_exists("map",$subArr) && $subArr["map"]){
								echo "<div class='sppmap'>";
								echo "<a href='index.php?taxon=".$subArr["tid"]."&taxauthid=".$taxAuthId.($clValue?"&cl=".$clValue:"")."'>";
								echo "<img src='".$subArr["map"]."' title='".$spDisplay." dot map' alt='".$spDisplay." dot map'/>";
								echo '</a>';
								echo "</div>";
							}							
							elseif($taxonManager->getRankId()>140){
								echo "<div class='sppmap'><div class='spptext'>Map<br />not<br />Available</div></div>\n";
							}
							echo "</div>";
							$cnt++;
						}
					}
					?>
						<div style='clear:both;'><hr> </div>
					</div>
				</fieldset>
			</td>
		</tr>
			<?php 
	}
	?>
		<tr>
		<td colspan="2"> 
		<?php 
				$searchParam = $taxonManager->getSciName();
				$break = strpos($searchParam, " ");
				$genus = substr($searchParam, 0, $break);
				$species = substr($searchParam, $break);
				echo $genus . " " . $species;
				echo "<div style='font-size:14px;margin-left:10px'><a href='../imagelib/search.php?taxon='". $genus . "%20" . $species .">Search All Images For ". $taxonManager->getSciName() . "</a></div>"; 
		?>
	<?php 
	//Bottom line listing options
	echo "<div style='margin-top:15px;text-align:center;'>";
	if($taxonRank > 180){
		if($taxonRank > 180 && $links){
			echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href=\"javascript:toggle('links')\">Web Links</a>";
		}
	}

	if($taxonRank > 140){
		$parentLink = "index.php?taxon=".$taxonManager->getParentTid()."&taxauthid=".$taxAuthId;
		if($clValue) $parentLink .= "&cl=".$taxonManager->getClid();
		if($projValue) $parentLink .= "&proj=".$projValue;
		echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='".$parentLink."'>View Parent Taxon</a>";
	}
	echo "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<a href='javascript: self.close();'>Close window</a>";
	echo "</div>";
	
	//List Web Links as a list
	if($taxonRank > 180 && $links){
		echo "<div class='links' style='display:none;'>\n<h1 style='margin-left:20px;'>Web Links</h1>\n<ul style='margin-left:30px;'>\n";
		foreach($links as $l){
			$urlStr = str_replace('--SCINAME--',urlencode($taxonManager->getSciName()),$l['url']);
			echo '<li><a href="'.$urlStr.'" target="_blank">'.$l['title'].'</a></li>';
			if($l['notes']) echo ' '.$l['notes'];
		}
		echo "</ul>\n</div>";
	}
	echo "</td></tr>\n";
}
elseif($taxonValue){
	?>
	<tr><td>
		<div style="margin-top:45px;margin-left:20px">
			<?php 
			if(is_numeric($taxonValue)){
				echo '<h1>Illegal identifier submitted: '.$taxonValue.'</h1>';
			}
			else{
				?>
				<h1>Sorry, we do not have <i><?php echo $taxonValue; ?></i> in our system.</h1>
				<?php 
				if($matchArr = $taxonManager->getCloseTaxaMatches($taxonValue)){
					?>
					<div style="margin-left: 15px;font-weight:bold;font-size:120%;">
						Did you mean?
						<div style=margin-left:25px;>
							<?php
							foreach($matchArr as $t => $n){ 
								echo '<a href="index.php?taxon='.$t.'">'.$n.'</a><br/>';
							} 
							?>
						</div>
					</div>
					<?php 
				}
				?>
				<h3>Links below may provide some useful information:</h3>
				<ul>
					<li>
						<a target="_blank" href="http://www.google.com/search?hl=en&btnG=Google+Search&q=<?php echo $taxonValue; ?>">Google</a>
					</li>
					<li>
						<a target="_blank" href="http://www.itis.gov/servlet/SingleRpt/SingleRpt?search_topic=all&search_value=<?php echo $taxonValue; ?>&search_kingdom=every&search_span=exactly_for&categories=All&source=html&search_credRating=All">
							ITIS: Integrated Taxonomic Information System
						</a>
					</li>
					<li>
						<a target="_blank" href='http://images.google.com/images?q="<?php echo $taxonValue; ?>"'>
							Google Images
						</a>
					</li>
				</ul>
				<?php
			} 
			?>
		</div>
	</td></tr>
	<?php 
}
else{
	echo "<tr><td>";
	echo "Scientific name (eg: taxon=Pinus+ponderosa) not submitted. Please submit a taxon value.";
	echo "</td></tr>";
}
?>
</table>
<?php 
include($SERVER_ROOT.'/footer.php');
?>
</body>
</html>