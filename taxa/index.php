<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/taxa/index.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/TaxonProfile.php');
Header("Content-Type: text/html; charset=".$CHARSET);

$taxonValue = array_key_exists("taxon",$_REQUEST)?$_REQUEST["taxon"]:""; 
$tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:"";
$taxAuthId = array_key_exists("taxauthid",$_REQUEST)?$_REQUEST["taxauthid"]:1; 
$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:0;
$lang = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:$DEFAULT_LANG;
$descrDisplayLevel = array_key_exists("displaylevel",$_REQUEST)?$_REQUEST["displaylevel"]:"";

$taxonManager = new TaxonProfile();
if($taxAuthId || $taxAuthId === "0") $taxonManager->setTaxAuthId($taxAuthId);
if($clid) $taxonManager->setClid($clid);
if($pid) $taxonManager->setPid($pid);
if($lang) $taxonManager->setLanguage($lang);
if($taxonValue && !$tid) $tid = $taxonManager->getTaxon($taxonValue);
if($tid) $taxonManager->setTid($tid);

$ambiguous = $taxonManager->getAmbSyn();
$acceptedName = $taxonManager->getAcceptance();
$synonymArr = $taxonManager->getSynonymArr();
$spDisplay = $taxonManager->getDisplayName();
$taxonRank = $taxonManager->getRankId();
$links = $taxonManager->getTaxaLinks();
if($links){
	foreach($links as $linkKey => $linkUrl){
		if($linkUrl['title'] == 'REDIRECT'){
			$locUrl = str_replace('--SCINAME--',rawurlencode($taxonManager->getSciName()),$linkUrl['url']);
			header('Location: '.$locUrl);
			exit;
		}
	}
}

$displayLocality = 0;
$isEditor = false;
if($SYMB_UID){
	if($IS_ADMIN || array_key_exists("TaxonProfile",$USER_RIGHTS)){
		$isEditor = true;
	}
	if($IS_ADMIN || array_key_exists("CollAdmin",$USER_RIGHTS) || array_key_exists("RareSppAdmin",$USER_RIGHTS) || array_key_exists("RareSppReadAll",$userRights)){
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
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
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
	<script src="../js/symb/taxa.index.js?ver=20170302" type="text/javascript"></script>
	<script src="../js/symb/taxa.editor.js?ver=20140619" type="text/javascript"></script>
</head>
<body>
<?php
$displayLeftMenu = false;
include($SERVER_ROOT.'/header.php');
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
				$parentLink = "index.php?tid=".$taxonManager->getParentTid()."&clid=".$clid."&pid=".$pid."&taxauthid=".$taxAuthId;
				echo "&nbsp;<a href='".$parentLink."'><img border='0' height='10px' src='../images/toparent.png' title='Go to Parent' /></a>";
			 	//If submitted tid does not equal accepted tid, state that user will be redirected to accepted
			 	if(($taxonManager->getTid() != $taxonManager->getSubmittedTid()) && $taxAuthId){
			 		echo '<span style="font-size:90%;margin-left:25px;"> ('.$LANG['REDIRECT'].': <i>'.$taxonManager->getSubmittedSciName().'</i>)</span>'; 
			 	}
			 	?>
			</div>
			<?php
			if($ambiguous){
				$synLinkStr = '';
				$explanationStr = '';
				foreach($synonymArr as $synTid => $sName){
					$synLinkStr .= '<a href="index.php?tid='.$synTid.'&taxauthid='.$taxAuthId.'&clid='.$clid.'&pid='.$pid.'&lang='.$lang.'">'.$sName.'</a>, ';
				}
				$synLinkStr = substr($synLinkStr,0,-2);
				if($acceptedName){
					$explanationStr = $LANG['AMB_ACCEPTED'];
				}
				else{
					$explanationStr = $LANG['AMB_UNACCEPTED'];
				}
				echo "<div style='float:left;font-weight:bold;margin:10px;clear:both;'>";
				echo $explanationStr.$synLinkStr;
				echo '</div>';
			}
			if($isEditor){
				?>
				<div style="float:right;">
					<a href="profile/tpeditor.php?tid=<?php echo $taxonManager->getTid(); ?>" <?php echo 'title="'.$LANG['EDIT_TAXON_DATA'].'"'; ?>>
						<img style='border:0px;' src='../images/edit.png'/>
					</a>
				</div>
				<?php 
			}
			if($links && $links[0]['sortseq'] == 1){
				$uStr = str_replace('--SCINAME--',rawurlencode($taxonManager->getSciName()),$links[0]['url']);
				?>
				<div style="margin-left:25px;clear:both;">
					<?php echo $LANG['GO_TO']; ?> <a href="<?php echo $uStr; ?>" target="_blank"><?php echo $links[0]['title']; ?></a>...
				</div>
				<?php 
			}
			?>
			</td>
		</tr>
		<tr>
			<td width="300" valign="top">
				<div id="family" style="margin-left:25px;">
					<?php echo '<b>'.$LANG['FAMILY'].':</b> '.$taxonManager->getFamily(); ?> 
				</div>
				<?php 
				$vernStr = $taxonManager->getVernacularStr();
				if($vernStr){
					?>
					<div id="vernaculars" style="margin-left:10px;margin-top:0.5em;font-size:130%;">
						<?php echo $vernStr; ?>
					</div>
					<?php 
				}
				
				$synStr = $taxonManager->getSynonymStr();
				if($synStr){
					echo "\t<div id='synonyms' style='margin-left:20px;margin-top:0.5em;' title='".$LANG['SYNONYMS']."'>[";
					echo $synStr;
					echo"]</div>\n";
				}
				
				if(!$taxonManager->echoImages(0,1,0)){
					echo '<div class="image" style="width:260px;height:260px;border-style:solid;margin-top:5px;margin-left:20px;text-align:center;">';
					if($isEditor){
						echo '<a href="profile/tpeditor.php?category=imageadd&tid='.$taxonManager->getTid().'"><b>'.$LANG['ADD_IMAGE'].'</b></a>';
					}
					else{
						echo $LANG['IMAGE_NOT_AVAILABLE'];
					}
					echo '</div>';
				}
				?>
			</td>
			<td class="desc">
				<?php 
				echo $taxonManager->getDescriptionStr();
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
						$gAnchor = "openMapPopup('".$taxonManager->getTid()."',".$clid.")";
					}
					if($mapSrc = $taxonManager->getMapArr()){
						$url = array_shift($mapSrc);
						$aUrl = $url;
					}
					elseif($gAnchor){
						$url = $taxonManager->getGoogleStaticMap();
					}
					if($url){
						echo '<div class="mapthumb">';
						if($gAnchor){
							echo '<a href="#" onclick="'.$gAnchor.';return false">';
						}
						elseif($aUrl){
							echo '<a href="'.$aUrl.'">';
						}
						echo '<img src="'.$url.'" title="'.$spDisplay.'" alt="'.$spDisplay.'" />';
						if($aUrl || $gAnchor) echo '</a>';
						if($gAnchor) echo '<br /><a href="#" onclick="'.$gAnchor.';return false">'.$LANG['OPEN_MAP'].'</a>';
						echo "</div>";
					}
					$taxonManager->echoImages(1);
					?>
				</div>
				<div id="img-tab-div" style="display:<?php echo $taxonManager->getImageCount()> 6?'block':'none';?>;border-top:2px solid gray;margin-top:2px;">
					<div style="background:#eee;padding:10px;border: 1px solid #ccc;width:100px;margin:auto;text-align:center">
						<a href="#" onclick="expandExtraImages();return false;">
							<?php echo $LANG['CLICK_TO_DISPLAY'].'<br/>'.$taxonManager->getImageCount().' '.$LANG['TOTAL_IMAGES']; ?>
						</a>
					</div>
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
					$parentLink = "index.php?tid=".$taxonManager->getParentTid()."&clid=".$clid."&pid=".$pid."&taxauthid=".$taxAuthId;
					$displayName .= ' <a href="'.$parentLink.'">';
					$displayName .= '<img border="0" height="10px" src="../images/toparent.png" title="'.$LANG['GO_TO_PARENT'].'" />';
					$displayName .= '</a>';
				}
				echo "<div style='font-size:16px;margin-top:15px;margin-left:10px;font-weight:bold;'>$displayName</div>\n";
				if($taxonRank > 140) echo "<div id='family' style='margin-top:3px;margin-left:20px;'><b>Family:</b> ".$taxonManager->getFamily()."</div>\n";
				if($pid) echo "<div style='margin-top:3px;margin-left:20px;'><b>Project:</b> ".$taxonManager->getProjName()."</div>\n";
				if(!$taxonManager->echoImages(0,1,0)){
					echo "<div class='image' style='width:260px;height:260px;border-style:solid;margin-top:5px;margin-left:20px;text-align:center;'>";
					if($isEditor){
						echo '<a href="profile/tpeditor.php?category=imageadd&tid='.$taxonManager->getTid().'"><b>'.$LANG['ADD_IMAGE'].'</b></a>';
					}
					else{
						echo $LANG['IMAGE_NOT_AVAILABLE'];
					}
					echo '</div>';
				}
				?>
			</td>
			<td>
				<?php 
				if($isEditor){
					?>
					<div style='float:right;'>
						<a href="profile/tpeditor.php?tid=<?php echo $taxonManager->getTid(); ?>" title="<?php echo $LANG['EDIT_TAXON_DATA']; ?>">
							<img style='border:0px;' src='../images/edit.png'/>
						</a>
					</div>
					<?php 
				}
				echo $taxonManager->getDescriptionStr();
				?>
			</td>
		</tr>

		<tr>
			<td colspan="2">
				<fieldset style="padding:10px 2px 10px 2px;">
					<?php 
					if($clid){
						echo "<legend>";
						echo $LANG['SPECIES_WITHIN'].' <b>'.$taxonManager->getClName().'</b>&nbsp;&nbsp;';
						if($parentChecklistArr = $taxonManager->getParentChecklist()){
							echo '<a href="index.php?tid='.$tid.'&clid='.key($parentChecklistArr).'&taxauthid='.$taxAuthId.'" title="'.$LANG['GO_TO'].' '.current($parentChecklistArr).' '.$LANG['CHECKLIST'].'"><img style="border:0px;width:10px;" src="../images/toparent.png"/></a>';
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
							echo "<div class='spptaxon'>";
							echo "<div style='margin-top:10px;'>";
							echo "<a href='index.php?tid=".$subArr["tid"]."&taxauthid=".$taxAuthId."&clid=".$clid."'>";
							echo "<i>".$sciNameKey."</i>";
							echo "</a></div>\n";
							echo "<div class='sppimg' style='overflow:hidden;'>";
		
							if(array_key_exists("url",$subArr)){
								$imgUrl = $subArr["url"];
								if(array_key_exists("imageDomain",$GLOBALS) && substr($imgUrl,0,1)=="/"){
									$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
								}
								echo "<a href='index.php?tid=".$subArr["tid"]."&taxauthid=".$taxAuthId."&clid=".$clid."'>";
		
								if($subArr["thumbnailurl"]){
									$imgUrl = $subArr["thumbnailurl"];
									if(array_key_exists("imageDomain",$GLOBALS) && substr($subArr["thumbnailurl"],0,1)=="/"){
										$imgUrl = $GLOBALS["imageDomain"].$subArr["thumbnailurl"];
									}
								}
								echo '<img src="'.$imgUrl.'" title="'.$subArr['caption'].'" alt="Image of '.$sciNameKey.'" style="z-index:-1" />';
								echo '</a>';
								echo '<div style="text-align:right;position:relative;top:-26px;left:5px;" title="'.$LANG['PHOTOGRAPHER'].': '.$subArr['photographer'].'">';
								echo '</div>';
							}
							elseif($isEditor){
								echo '<div class="spptext" style="margin-top:7px"><a href="profile/tpeditor.php?category=imageadd&tid='.$subArr['tid'].'">'.$LANG['ADD_IMAGE'].'!</a></div>';
							}
							else{
								echo '<div class="spptext">'.$LANG['IMAGE_NOT_AVAILABLE'].'</div>';
							}
							echo "</div>\n";

							//Display thumbnail map
							echo '<div class="sppmap">';
							if(array_key_exists("map",$subArr) && $subArr["map"]){
								echo '<img src="'.$subArr['map'].'" title="'.$spDisplay.'" alt="'.$spDisplay.'" />';
							}							
							elseif($taxonManager->getRankId()>140){
								echo '<div class="spptext">'.$LANG['MAP_NOT_AVAILABLE'].'</div>';
							}
							echo '</div>';
							
							echo "</div>";
							$cnt++;
						}
					}
					?>
						<div style='clear:both;'><hr></div>
					</div>
				</fieldset>
			</td>
		</tr>
			<?php 
	}
}
else{
	?>
	<tr><td>
		<div style="margin-top:45px;margin-left:20px">
			<h1><?php echo '<i>'.$taxonValue.'</i> '.$LANG['NOT_FOUND']; ?></h1>
			<?php 
			if($matchArr = $taxonManager->getCloseTaxaMatches($taxonValue)){
				?>
				<div style="margin-left: 15px;font-weight:bold;font-size:120%;">
					<?php echo $LANG['DID_YOU_MEAN'];?>
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
		</div>
	</td></tr>
	<?php 
}
?>
</table>
<?php 
include($SERVER_ROOT.'/footer.php');
?>
</body>
</html>