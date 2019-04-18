<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/taxa/index.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/TaxonProfile.php');
Header("Content-Type: text/html; charset=".$CHARSET);

$taxonValue = array_key_exists("taxon",$_REQUEST)?$_REQUEST["taxon"]:"";
$tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:"";
$taxAuthId = array_key_exists("taxauthid",$_REQUEST)?$_REQUEST["taxauthid"]:1;
$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:'';
$lang = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:$DEFAULT_LANG;
$taxaLimit = array_key_exists("taxalimit",$_REQUEST)?$_REQUEST["taxalimit"]:50;
$page = array_key_exists("page",$_REQUEST)?$_REQUEST["page"]:0;

//Sanitation
$taxonValue = strip_tags($taxonValue);
$taxonValue = preg_replace('/[^a-zA-Z0-9\-\s.]/', '', $taxonValue);
$taxonValue = htmlspecialchars($taxonValue, ENT_QUOTES, 'UTF-8');
if(!is_numeric($tid)) $tid = 0;
if(!is_numeric($taxAuthId)) $taxAuthId = 1;
if(!is_numeric($clid)) $clid = 0;
if(!is_numeric($pid)) $pid = 1;
if(!is_numeric($taxaLimit)) $taxaLimit = 50;
if(!is_numeric($page)) $page = 0;

$taxonManager = new TaxonProfile();
if($taxAuthId) $taxonManager->setTaxAuthId($taxAuthId);
if(!$tid && $taxonValue){
	$tidArr = $taxonManager->taxonSearch($taxonValue);
	$tid = key($tidArr);
	//Need to add code that allows user to select target taxon when more than one homonym is returned
}

if($pid === '' && isset($DEFAULT_PROJ_ID) && $DEFAULT_PROJ_ID) $pid = $DEFAULT_PROJ_ID;
if($lang) $lang = $taxonManager->setLanguage($lang);
$tidSubmit = $tid;
$tid = $taxonManager->setTid($tidSubmit);

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

$isEditor = false;
if($SYMB_UID){
	if($IS_ADMIN || array_key_exists("TaxonProfile",$USER_RIGHTS)){
		$isEditor = true;
	}
}
$descr = Array();
?>

<html>
<head>
	<title><?php echo $DEFAULT_TITLE." - ".$taxonManager->getSciName(); ?></title>
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
	<script src="../js/symb/taxa.index.js?ver=1810902" type="text/javascript"></script>
	<script src="../js/symb/taxa.editor.js?ver=20140619" type="text/javascript"></script>
</head>
<body>
<?php
$displayLeftMenu = false;
include($SERVER_ROOT.'/header.php');
?>
<div id="popup-innertext">
	<table id="innertable">
	<?php
	if($taxonManager->getSciName()){
		$taxonRank = $taxonManager->getRankId();
		if($taxonRank > 180){
			?>
			<tr>
				<td colspan="2" valign="bottom" height="35px">
				<div style='float:left;font-size:16px;margin-left:10px;'>
					<span style='font-weight:bold;color:#990000;'>
						<i><?php echo $taxonManager->getSciName(); ?></i>
					</span>
					<?php echo $taxonManager->getAuthor(); ?>
					<?php
					$parentLink = "index.php?tid=".$taxonManager->getParentTid()."&clid=".$clid."&pid=".$pid."&taxauthid=".$taxAuthId;
					echo "&nbsp;<a href='".$parentLink."'><img border='0' height='10px' src='../images/toparent.png' title='Go to Parent' /></a>";
				 	//If submitted tid does not equal accepted tid, state that user will be redirected to accepted
				 	if(!$taxonManager->isAccepted()){
				 		echo '<span style="font-size:90%;margin-left:25px;"> ('.(isset($LANG['REDIRECT'])?$LANG['REDIRECT']:'redirected from').': <i>'.$taxonManager->getSubmittedValue('sciname').'</i> '.$taxonManager->getSubmittedValue('author').')</span>';
				 	}
				 	?>
				</div>
				<?php
				/*
				if($taxonManager->getAmbSyn()){
					$synLinkStr = '';
					$explanationStr = '';
					$synonymArr = $taxonManager->getSynonymArr();
					foreach($synonymArr as $synTid => $sName){
						$synLinkStr .= '<a href="index.php?tid='.$synTid.'&taxauthid='.$taxAuthId.'&clid='.$clid.'&pid='.$pid.'&lang='.$lang.'">'.$sName.'</a>, ';
					}
					$synLinkStr = substr($synLinkStr,0,-2);
					if($taxonManager->getAcceptance()){
						$explanationStr = $LANG['AMB_ACCEPTED'];
					}
					else{
						$explanationStr = $LANG['AMB_UNACCEPTED'];
					}
					echo "<div style='float:left;font-weight:bold;margin:10px;clear:both;'>";
					echo $explanationStr.$synLinkStr;
					echo '</div>';
				}
				*/
				if($isEditor){
					?>
					<div style="float:right;">
						<a href="profile/tpeditor.php?tid=<?php echo $taxonManager->getTid(); ?>" <?php echo 'title="'.(isset($LANG['EDIT_TAXON_DATA'])?$LANG['EDIT_TAXON_DATA']:'Edit Taxon Data').'"'; ?>>
							<img style='border:0px;' src='../images/edit.png'/>
						</a>
					</div>
					<?php
				}
				if($links && $links[0]['sortseq'] == 1){
					$uStr = str_replace('--SCINAME--',rawurlencode($taxonManager->getSciName()),$links[0]['url']);
					?>
					<div style="margin-left:25px;clear:both;">
						<?php echo (isset($LANG['GO_TO'])?$LANG['GO_TO']:'Go to'); ?> <a href="<?php echo $uStr; ?>" target="_blank"><?php echo $links[0]['title']; ?></a>...
					</div>
					<?php
				}
				?>
				</td>
			</tr>
			<tr>
				<td width="300" valign="top">
					<div id="family" style="margin-left:25px;">
						<?php echo '<b>'.(isset($LANG['FAMILY'])?$LANG['FAMILY']:'Family').':</b> '.$taxonManager->getFamily(); ?>
					</div>
					<?php
					if($vernArr = $taxonManager->getVernaculars()){
						$primerArr = array();
						if(array_key_exists($DEFAULT_LANG, $vernArr)){
							$primerArr = $vernArr[$DEFAULT_LANG];
							unset($vernArr[$DEFAULT_LANG]);
						}
						else{
							$primerArr = array_shift($vernArr);
						}
						$vernStr = array_shift($primerArr);
						if($primerArr || $vernArr){
							$vernStr.= '<a href="#" class="verns" onclick="toggle(\'verns\')" style="font-size:70%" title="Click here to show more common names">,&nbsp;&nbsp;more...</a>';
							$vernStr.= '<span class="verns" onclick="toggle(\'verns\');" style="display:none;">';
							$vernStr.= implode(', ',$primerArr);
							foreach($vernArr as $langName => $vArr){
								$vernStr.= ', ('.$langName.': '.implode(', ',$vArr).')';
							}
							$vernStr.= '</span>';
						}
						?>
						<div id="vernaculars" style="margin-left:10px;margin-top:0.5em;font-size:130%;">
							<?php echo $vernStr; ?>
						</div>
						<?php
					}

					if($synArr = $taxonManager->getSynonymArr()){
						$primerArr = array_shift($synArr);
						$synStr = '<i>'.$primerArr['sciname'].'</i>'.(isset($primerArr['author']) && $primerArr['author']?' '.$primerArr['author']:'');
						if($synArr){
							$synStr .= '<a href="#" class="syns" onclick="toggle(\'syns\')" style="font-size:70%;vertical-align:sub" title="Click here to show more synonyms">,&nbsp;&nbsp;more</a>';
							$synStr .= '<span class="syns" onclick="toggle(\'syns\')" style="display:none">';
							foreach($synArr as $synKey => $sArr){
								$synStr .= ', <i>'.$sArr['sciname'].'</i> '.$sArr['author'];
							}
							$synStr .= '</span>';
						}
						echo '<div id="synonyms" style="margin-left:20px;margin-top:0.5em;" title="'.(isset($LANG['SYNONYMS'])?$LANG['SYNONYMS']:'Synonyms').'">[';
						echo $synStr;
						echo ']</div>';
					}

					if(!$taxonManager->echoImages(0,1,0)){
						echo '<div class="image" style="width:260px;height:260px;border-style:solid;margin-top:5px;margin-left:20px;text-align:center;">';
						if($isEditor){
							echo '<a href="profile/tpeditor.php?category=imageadd&tid='.$taxonManager->getTid().'"><b>'.(isset($LANG['ADD_IMAGE'])?$LANG['ADD_IMAGE']:'Add an Image').'</b></a>';
						}
						else{
							echo (isset($LANG['IMAGE_NOT_AVAILABLE'])?$LANG['IMAGE_NOT_AVAILABLE']:'Images<br/>not available');
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
						$aUrl = ''; $gAnchor = '';
						//$url = $taxonManager->getGoogleStaticMap();
						$url = $CLIENT_ROOT.'/images/mappoint.png';
						if($occurrenceModIsActive && $taxonManager->getDisplayLocality()){
							$gAnchor = "openMapPopup('".$taxonManager->getTid()."',".$clid.")";
						}
						if($mapSrc = $taxonManager->getMapArr()){
							$url = array_shift($mapSrc);
							$aUrl = $url;
						}
						if($url){
							echo '<div class="mapthumb">';
							if($gAnchor){
								echo '<a href="#" onclick="'.$gAnchor.';return false">';
							}
							elseif($aUrl){
								echo '<a href="'.$aUrl.'">';
							}
							echo '<img src="'.$url.'" title="'.$taxonManager->getSciName().'" alt="'.$taxonManager->getSciName().'" />';
							if($aUrl || $gAnchor) echo '</a>';
							if($gAnchor) echo '<br /><a href="#" onclick="'.$gAnchor.';return false">'.(isset($LANG['OPEN_MAP'])?$LANG['OPEN_MAP']:'Open Interactive Map').'</a>';
							echo "</div>";
						}
						$taxonManager->echoImages(1);
						?>
					</div>
					<?php
					$imgCnt = $taxonManager->getImageCount();
					$tabText = (isset($LANG['TOTAL_IMAGES'])?$LANG['TOTAL_IMAGES']:'Total Images');
					if($imgCnt == 100){
						$tabText = (isset($LANG['INITIAL_IMAGES'])?$LANG['INITIAL_IMAGES']:'Initial Images').'<br/>- - - - -<br/>';
						$tabText .= '<a href="'.$CLIENT_ROOT.'/imagelib/search.php?submitaction=search&taxa='.$tid.'">'.(isset($LANG['VIEW_ALL_IMAGES'])?$LANG['VIEW_ALL_IMAGES']:'View All Images').'</a>';
					}
					?>
					<div id="img-tab-div" style="display:<?php echo $imgCnt > 6?'block':'none';?>;border-top:2px solid gray;margin-top:2px;">
						<div style="background:#eee;padding:10px;border: 1px solid #ccc;width:110px;margin:auto;text-align:center">
							<a href="#" onclick="expandExtraImages();return false;">
								<?php echo (isset($LANG['CLICK_TO_DISPLAY'])?$LANG['CLICK_TO_DISPLAY']:'Click to Display').'<br/>'.$imgCnt.' '.$tabText; ?>
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
					$displayName = $taxonManager->getSciName();
					if($taxonRank == 180) $displayName = '<i>'.$displayName.'</i> ';
					if($taxonRank > 140){
						$parentLink = "index.php?tid=".$taxonManager->getParentTid()."&clid=".$clid."&pid=".$pid."&taxauthid=".$taxAuthId;
						$displayName .= ' <a href="'.$parentLink.'">';
						$displayName .= '<img border="0" height="10px" src="../images/toparent.png" title="'.(isset($LANG['GO_TO_PARENT'])?$LANG['GO_TO_PARENT']:'Go to Parent Taxon').'" />';
						$displayName .= '</a>';
					}
					echo "<div style='font-size:16px;margin-top:15px;margin-left:10px;font-weight:bold;'>$displayName</div>\n";
					if($taxonRank > 140) echo "<div id='family' style='margin-top:3px;margin-left:20px;'><b>Family:</b> ".$taxonManager->getFamily()."</div>\n";
					if(!$taxonManager->echoImages(0,1,0)){
						echo "<div class='image' style='width:260px;height:260px;border-style:solid;margin-top:5px;margin-left:20px;text-align:center;'>";
						if($isEditor){
							echo '<a href="profile/tpeditor.php?category=imageadd&tid='.$taxonManager->getTid().'"><b>'.(isset($LANG['ADD_IMAGE'])?$LANG['ADD_IMAGE']:'Add an Image').'</b></a>';
						}
						else{
							echo (isset($LANG['IMAGE_NOT_AVAILABLE'])?$LANG['IMAGE_NOT_AVAILABLE']:'Images<br/>not available');
						}
						echo '</div>';
					}
					?>
				</td>
				<td>
					<?php
					if($isEditor){
						?>
						<div style='float:right;margin-top:-20px;'>
							<a href="profile/tpeditor.php?tid=<?php echo $taxonManager->getTid(); ?>" title="<?php echo (isset($LANG['EDIT_TAXON_DATA'])?$LANG['EDIT_TAXON_DATA']:'Edit Taxon Data'); ?>">
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
					<?php
					if($sppArr = $taxonManager->getSppArray($page, $taxaLimit, $pid, $clid)){
						?>
						<fieldset style="padding:10px 2px 10px 2px;">
							<?php
							$legendStr = '';
							if($clid){
								if($checklistName = $taxonManager->getClName($clid)){
									$legendStr .= (isset($LANG['SPECIES_CHECKLIST'])?$LANG['SPECIES_CHECKLIST']:'Species within checklist').': <b>'.$checklistName.'</b>';
								}
								if($parentChecklistArr = $taxonManager->getParentChecklist($clid)){
									$titleStr = (isset($LANG['GO_TO_PARENT_CHECKLIST'])?$LANG['GO_TO_PARENT_CHECKLIST']:'Include species within checklist').': '.current($parentChecklistArr);
									$legendStr .= ' <a href="index.php?tid='.$tid.'&clid='.key($parentChecklistArr).'&pid='.$pid.'&taxauthid='.$taxAuthId.'" title="'.$titleStr.'">';
									$legendStr .= '<img style="border:0px;width:10px;" src="../images/toparent.png"/>';
									$legendStr .= '</a>';
								}
								elseif($pid){
									$titleStr = (isset($LANG['WITHIN_INVENTORY'])?$LANG['WITHIN_INVENTORY']:'Species within inventory project').': '.$taxonManager->getProjName($pid);
									$legendStr .= ' <a href="index.php?tid='.$tid.'&clid=0&pid='.$pid.'&taxauthid='.$taxAuthId.'" title="'.$titleStr.'">';
									$legendStr .= '<img style="border:0px;width:10px;" src="../images/toparent.png"/>';
									$legendStr .= '</a>';
								}
							}
							elseif($pid){
								$legendStr .= (isset($LANG['WITHIN_INVENTORY'])?$LANG['WITHIN_INVENTORY']:'Species within inventory project').': <b>'.$taxonManager->getProjName($pid).'</b>';
								$titleStr = (isset($LANG['SHOW_ALL_TAXA'])?$LANG['SHOW_ALL_TAXA']:'Show all taxa');
								$legendStr .= ' <a href="index.php?tid='.$tid.'&clid=0&pid=0&taxauthid='.$taxAuthId.'" title="'.$titleStr.'">';
								$legendStr .= '<img style="border:0px;width:10px;" src="../images/toparent.png"/>';
								$legendStr .= '</a>';
							}
							if($legendStr){
								$legendStr = '<span style="margin:0px 10px">'.$legendStr.'</span>';
							}

							$taxonCnt = count($sppArr);
							if($taxonCnt > $taxaLimit || $page){
								$navStr = '<span style="margin:0px 10px">';
								$dynLink = 'tid='.$tid.'&taxauthid='.$taxAuthId.'&clid='.$clid.'&pid='.$pid.'&lang='.$lang.'&taxalimit='.$taxaLimit;
								if($page) $navStr .= '<a href="index.php?'.$dynLink.'&page='.($page-1).'">&lt;&lt;</a>';
								else $navStr .= '&lt;&lt;';
								$upperCnt = ($page+1)*$taxaLimit;
								if($taxonCnt < $taxaLimit) $upperCnt = ($page*$taxaLimit)+$taxonCnt;
								$navStr .= ' '.(($page*$taxaLimit)+1).' - '.$upperCnt.' taxa ';
								if($taxonCnt > $taxaLimit) $navStr .= '<a href="index.php?'.$dynLink.'&page='.($page+1).'">&gt;&gt;</a>';
								else $navStr .= '&gt;&gt;';
								$navStr .= '</span>';
								if($legendStr) $legendStr .= ' || ';
								$legendStr .= ' '.$navStr.' ';
							}

							if($legendStr) echo '<legend>'.$legendStr.'</legend>';
							?>
							<div>
							<?php
								$cnt = 1;
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
										echo '<div style="text-align:right;position:relative;top:-26px;left:5px;" title="'.(isset($LANG['PHOTOGRAPHER'])?$LANG['PHOTOGRAPHER']:'Photographer').': '.$subArr['photographer'].'">';
										echo '</div>';
									}
									elseif($isEditor){
										echo '<div class="spptext"><a href="profile/tpeditor.php?category=imageadd&tid='.$subArr['tid'].'">'.(isset($LANG['ADD_IMAGE'])?$LANG['ADD_IMAGE']:'Add an Image').'!</a></div>';
									}
									else{
										echo '<div class="spptext">'.(isset($LANG['IMAGE_NOT_AVAILABLE'])?$LANG['IMAGE_NOT_AVAILABLE']:'Images<br/>not available').'</div>';
									}
									echo "</div>\n";

									//Display thumbnail map
									/* Deactivating display of static map to reduce Google charges
									if($taxonManager->getRankId() > 140){
										echo '<div class="sppmap">';
										if(array_key_exists("map",$subArr) && $subArr["map"]){
											echo '<img src="'.$subArr['map'].'" title="'.$taxonManager->getSciName().'" alt="'.$taxonManager->getSciName().'" />';
										}
										else{
											echo '<div class="spptext">'.(isset($LANG['MAP_NOT_AVAILABLE'])?$LANG['MAP_NOT_AVAILABLE']:'Map not<br />Available').'</div>';
										}
										echo '</div>';
									}
									*/
									echo "</div>";
									$cnt++;
									if($cnt > $taxaLimit) break;
								}
								?>
							</div>
						</fieldset>
						<?php
					}
				?>
				</td>
			</tr>
				<?php
		}
	}
	else{
		?>
		<tr><td>
			<div style="margin-top:45px;margin-left:20px">
				<h1><?php echo '<i>'.htmlspecialchars($taxonValue, ENT_QUOTES, 'UTF-8').'</i> '.(isset($LANG['NOT_FOUND'])?$LANG['NOT_FOUND']:'not found'); ?></h1>
				<?php
				if($matchArr = $taxonManager->getCloseTaxaMatches($taxonValue)){
					?>
					<div style="margin-left: 15px;font-weight:bold;font-size:120%;">
						<?php echo (isset($LANG['DID_YOU_MEAN'])?$LANG['DID_YOU_MEAN']:'Did you mean?');?>
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
</div>
<?php
include($SERVER_ROOT.'/footer.php');
?>
</body>
</html>