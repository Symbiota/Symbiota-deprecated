<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/GlossaryManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$glossId = array_key_exists('glossid',$_REQUEST)?$_REQUEST['glossid']:0;
$glimgId = array_key_exists('glimgid',$_REQUEST)?$_REQUEST['glimgid']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$isEditor = false;
if($IS_ADMIN || array_key_exists("Taxonomy",$USER_RIGHTS)){
	$isEditor = true;
}

$glosManager = new GlossaryManager();
$termArr = array();
$termImgArr = array();
$redirectStr = '';
if($glossId){
	$glosManager->setGlossId($glossId);
	$termArr = $glosManager->getTermArr();
	$synonymArr = $glosManager->getSynonyms();
	if(!$termArr['definition'] && $synonymArr){
		$newID = '';
		foreach($synonymArr as $sID => $sArr){
			$newID = $sID;
			if($sArr['definition']) break;
		}
		if($newID){
			$redirectStr = 'redirected from '.$termArr['term'];
			$glossId = $newID;
			$glosManager->setGlossId($newID);
			$termArr = $glosManager->getTermArr();
			$synonymArr = $glosManager->getSynonyms();
		}
	}
	$termImgArr = $glosManager->getImgArr();
}
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Glossary Term Information</title>
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" rel="stylesheet" type="text/css" />
    <link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" rel="stylesheet" type="text/css" />
	<link href="../css/jquery-ui.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../js/symb/glossary.index.js"></script>
</head>

<body style="overflow-x:hidden;overflow-y:auto;width:700px;margin-left:auto;margin-right:auto;">
	<script type="text/javascript">
		<?php include_once($SERVER_ROOT.'/config/googleanalytics.php'); ?>
	</script>
	<!-- This is inner text! -->
	<div id="innertext" style="width:680px;">
		<div id="tabs" style="padding:10px">
			<div style="clear:both;">
				<?php
				if($isEditor){
					?>
					<div style="float:right;margin-right:15px;" title="Edit Term Data">
						<a href="termdetails.php?glossid=<?php echo $glossId;?>" onclick="self.resizeTo(1250, 900);">
							<img style="border:0px;width:12px;" src="../images/edit.png" />
						</a>
					</div>
					<?php
				}
				?>
				<div style="float:left;">
						<?php echo '<span style="font-size:18px;font-weight:bold;">'.$termArr['term'].'</span> '.$redirectStr; ?>
				</div>
			</div>
			<div style="clear:both;width:670px;">
				<div id="terminfo" style="float:left;width:<?php echo ($termImgArr?'380':'670'); ?>px;padding:10px;">
					<div style="clear:both;">
						<div style='' >
							<div style='margin-top:8px;width:95%' >
								<b>Definition:</b>
								<?php echo $termArr['definition']; ?>
							</div>
							<?php
							if($termArr['author']){
								?>
								<div style='margin-top:8px;' >
									<b>Author:</b>
									<?php echo $termArr['author']; ?>
								</div>
								<?php
							}
							if($termArr['translator']){
								?>
								<div style='margin-top:8px;' >
									<b>Translator:</b>
									<?php echo $termArr['translator']; ?>
								</div>
								<?php
							}
							if($synonymArr){
								echo '<div style="margin-top:8px;" ><b>Synonyms:</b> ';
								$i = 0;
								foreach($synonymArr as $synGlossId => $synArr){
									if($i) echo ', ';
									echo '<a href="individual.php?glossid='.$synGlossId.'">'.$synArr['term'].'</a>';
									$i++;
								}
								echo '</div>';
							}
							$translationArr = $glosManager->getTranslations();
							if($translationArr){
								echo '<div style="margin-top:8px;" ><b>Translations:</b> ';
								$i = 0;
								foreach($translationArr as $transGlossId => $transArr){
									if($i) echo ', ';
									echo '<a href="individual.php?glossid='.$transGlossId.'">'.$transArr['term'].'</a> ('.$transArr['language'].')';
									$i++;
								}
								echo '</div>';
							}
							$otherRelationshipsArr = $glosManager->getOtherRelatedTerms();
							if($otherRelationshipsArr){
								echo '<div style="margin-top:8px;" ><b>Other Related Terms:</b> ';
								$delimter = '';
								foreach($otherRelationshipsArr as $relType => $relTypeArr){
									$relStr = '';
									if($relType == 'partOf') $relStr = 'has part';
									elseif($relType == 'hasPart') $relStr = 'part of';
									elseif($relType == 'subClassOf') $relStr = 'superclass or parent term';
									elseif($relType == 'superClassOf') $relStr = 'subclass or child term';
									foreach($relTypeArr as $relGlossId => $relArr){
										echo $delimter.'<a href="individual.php?glossid='.$relGlossId.'">'.$relArr['term'].'</a> ('.$relStr.')';
										$delimter = ', ';
									}
								}
								echo '</div>';
							}
							if($termArr['notes']){
								?>
								<div style='margin-top:8px;' >
									<b>Notes:</b>
									<?php echo $termArr['notes']; ?>
								</div>
								<?php
							}
							if($termArr['resourceurl']){
								$resource = '';
								if(substr($termArr['resourceurl'],0,4)=="http" || substr($termArr['resourceurl'],0,4)=="www."){
									$resource = "<a href='".$termArr['resourceurl']."' target='_blank'>".wordwrap($termArr['resourceurl'],($termImgArr?37:70),'<br />\n',true)."</a>";
								}
								else{
									$resource = $termArr['resourceurl'];
								}
								?>
								<div style='margin-top:8px;' >
									<b>Resource URL:</b>
									<?php echo $resource; ?>
								</div>
								<?php
							}
							if($termArr['source']){
								?>
								<div style='margin-top:8px;' >
									<b>Source:</b>
									<?php echo $termArr['source']; ?>
								</div>
								<?php
							}
							?>
						</div>
						<div style="clear:both;margin:15px 0px;">
							<b>Relevant Taxa:</b>
							<?php
							$sourceArr = $glosManager->getTaxonSources();
							foreach($sourceArr as $tid => $arr){
								echo '<div style="margin-left:20px">';
								echo $arr['sciname'].' [<a href="#" onclick="toggle(\''.$tid.'-sourcesdiv\');return false;"><span style="font-size:90%">show sources</span></a>]';
								echo '</div>';
							}
							?>
						</div>
					</div>
				</div>
				<?php
				if($termImgArr){
					?>
					<div id="termimagediv" style="float:right;width:250px;padding:10px;">
						<?php
						foreach($termImgArr as $imgId => $imgArr){
							$imgUrl = $imgArr["url"];
							if(substr($imgUrl,0,1)=="/"){
								if(array_key_exists("imageDomain",$GLOBALS) && $GLOBALS["imageDomain"]){
									$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
								}
								else{
									$urlPrefix = "http://";
									if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $urlPrefix = "https://";
									$urlPrefix .= $_SERVER["SERVER_NAME"];
									if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $urlPrefix .= ':'.$_SERVER["SERVER_PORT"];
									$imgUrl = $urlPrefix.$imgUrl;
								}
							}
							?>
							<fieldset style='clear:both;border:0px;padding:0px;margin-top:10px;'>
								<div style='width:250px;'>
									<?php
									$imgWidth = 0;
									$imgHeight = 0;
									$size = getimagesize(str_replace(' ', '%20', $imgUrl));
									if($size[0] > 240){
										$imgWidth = 240;
										$imgHeight = 0;
									}
									if($size[0] < 245 && $size[1] > 500){
										$imgWidth = 0;
										$imgHeight = 500;
									}
									?>
									<img src='<?php echo $imgUrl; ?>' style="margin:auto;display:block;border:1px;<?php echo ($imgWidth?'width:'.$imgWidth.'px;':'').($imgHeight?'height:'.$imgHeight.'px;':''); ?>" title='<?php echo $imgArr['structures']; ?>'/>
								</div>
								<div style='width:250px;'>
									<?php
									if($imgArr['createdBy']){
										?>
										<div style='overflow:hidden;width:250px;margin-top:2px;font-size:12px;' >
											Image courtesy of: <?php echo wordwrap($imgArr['createdBy'], 370, "<br />\n"); ?>
										</div>
										<?php
									}
									if($imgArr['structures']){
										?>
										<div style='overflow:hidden;width:250px;margin-top:8px;' >
											<b>Structures:</b>
											<?php echo wordwrap($imgArr["structures"], 370, "<br />\n"); ?>
										</div>
										<?php
									}
									if($imgArr['notes']){
										?>
										<div style='overflow:hidden;width:250px;margin-top:8px;' >
											<b>Notes:</b>
											<?php echo wordwrap($imgArr["notes"], 370, "<br />\n"); ?>
										</div>
										<?php
									}
									?>
								</div>
							</fieldset>
							<?php
						}
						?>
					</div>
					<?php
				}
				foreach($sourceArr as $tid => $arr){
					?>
					<div id="<?php echo $tid; ?>-sourcesdiv" style="display:none;margin-top:20px">
						<fieldset style="margin:10px; padding:10px;background-color:white;">
							<legend><b>Contributors for <?php echo $arr['sciname']; ?></b></legend>
							<?php
							if($arr['contributorTerm']){
								?>
								<div style="">
									<b>Term and Definition contributed by:</b> <?php echo $arr['contributorTerm']; ?>
								</div>
								<?php
							}
							if($arr['contributorImage'] && $termImgArr){
								?>
								<div style="margin-top:8px;">
									<b>Image contributed by:</b> <?php echo $arr['contributorImage']; ?>
								</div>
								<?php
							}
							if($arr['translator'] && $translationArr){
								?>
								<div style="margin-top:8px;">
									<b>Translation by:</b> <?php echo $arr['translator']; ?>
								</div>
								<?php
							}
							if($arr['additionalSources'] && ($translationArr || $termImgArr)){
								?>
								<div style="margin-top:8px;">
									<b>Translation and/or image were also sourced from the following references:</b> <?php echo $arr['additionalSources']; ?>
								</div>
								<?php
							}
							?>
						</fieldset>
						<div style="clear:both">&nbsp;</div>
					</div>
					<?php
				}
				?>
			</div>
			<div style="clear:both"></div>
		</div>
	</div>
</body>
</html>