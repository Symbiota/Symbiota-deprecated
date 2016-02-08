<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/GlossaryManager.php');
header("Content-Type: text/html; charset=".$charset);

$glossId = array_key_exists('glossid',$_REQUEST)?$_REQUEST['glossid']:0;
$glossgrpId = array_key_exists('glossgrpid',$_REQUEST)?$_REQUEST['glossgrpid']:0;
$glimgId = array_key_exists('glimgid',$_REQUEST)?$_REQUEST['glimgid']:0;
$tId = array_key_exists('tid',$_REQUEST)?$_REQUEST['tid']:'';
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$isEditor = false;
if($isAdmin || array_key_exists("Taxonomy",$USER_RIGHTS)){
	$isEditor = true;
}

$glosManager = new GlossaryManager();
$termArr = array();
$termImgArr = array();
$termGrpArr = array();
$synonymArr = array();
$translationArr = array();
$sourceArr = array();
$synonymStr = '';
$translationStr = '';

if($glossId){
	$termArr = $glosManager->getTermArr($glossId);
	$sciName = $glosManager->getSciName($tId);
	$glossgrpId = $termArr['glossgrpid'];
	$termGrpArr = $glosManager->getGrpArr($glossId,$glossgrpId,$termArr['language']);
	if(isset($glossary_indexBanner)){
		$sourceArr = $glosManager->getTaxonSources($tId);
	}
	if(array_key_exists('synonym',$termGrpArr)){
		$synonymArr = $termGrpArr['synonym'];
		if($synonymArr){
			$synonymStr = "<div style='margin-top:8px;' ><b>Synonyms:</b>";
			$i = 0;
			$cnt = count($synonymArr);
			foreach($synonymArr as $synId => $synArr){
				$onClick = "leaveTermPopup('termdetails.php?glossid=".$synArr['glossid']."'); return false;";
				$synonymStr .= ' '.$synArr['term'];
				if($isEditor){
					$synonymStr .= ' <a href="#" onclick="'.$onClick.'" target="_blank">';
					$synonymStr .= '<img style="border:0px;width:12px;" src="../images/edit.png" /></a>';
				}
				if($i < ($cnt - 1)){
					$synonymStr .= ',';
				}
				$i++;
			}
			$synonymStr .= "</div>";
		}
	}
	if(array_key_exists('translation',$termGrpArr)){
		$translationArr = $termGrpArr['translation'];
		if($translationArr){
			$translationStr = "<div style='margin-top:8px;' ><b>Translations:</b>";
			$i = 0;
			$cnt = count($translationArr);
			foreach($translationArr as $transId => $transArr){
				$onClick = "leaveTermPopup('termdetails.php?glossid=".$transArr['glossid']."'); return false;";
				$translationStr .= ' '.$transArr['term'];
				$translationStr .= ' ('.$transArr['language'].')';
				if($isEditor){
					$translationStr .= ' <a href="#" onclick="'.$onClick.'" target="_blank">';
					$translationStr .= '<img style="border:0px;width:12px;" src="../images/edit.png" /></a>';
				}
				if($i < ($cnt - 1)){
					$translationStr .= ',';
				}
				$i++;
			}
			$translationStr .= "</div>";
		}
	}
	$termImgArr = $glosManager->getImgArr($glossgrpId);
}
else{
	header("Location: index.php");
}
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Glossary Term Information</title>
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<link href="../css/base.css?<?php echo $CSS_VERSION; ?>" rel="stylesheet" type="text/css" />
    <link href="../css/main.css?<?php echo $CSS_VERSION; ?>" rel="stylesheet" type="text/css" />
	<link href="../css/jquery-ui.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../js/symb/glossary.index.js"></script>
</head>

<body style="overflow-x:hidden;overflow-y:auto;width:625px;margin-left:auto;margin-right:auto;">
	<!-- This is inner text! -->
	<div id="innertext" style="width:625px;margin-left:0px;margin-right:0px;">
		<div id="tabs" style="padding:10px;width:600px;margin:0px;">
			<div style="clear:both;width:600px;margin-bottom:5px;font-size:12px;">
				<?php echo $sciName; ?>
			</div>
			<div style="clear:both;width:600px;">
				<?php
				if($isEditor){
					?>
					<div style="float:right;margin-right:15px;cursor:pointer;" onclick="" title="Edit Term Data">
						<a href="#" onclick="leaveTermPopup('termdetails.php?glossid=<?php echo $glossId;?>'); return false;" target="_blank">
							<img style="border:0px;width:12px;" src="../images/edit.png" />
						</a>
					</div>
					<?php
				}
				?>
				<div style="float:left;">
					<span style="font-size:18px;font-weight:bold;">
						<?php echo $termArr['term']; ?>
					</span>
				</div>
			</div>
			<div style="clear:both;width:600px;">
				<div id="terminfo" style="float:left;<?php echo ($termImgArr?'width:300px;':''); ?>padding:10px;">
					<div style="clear:both;">
						<div style='' >
							<div style='margin-top:8px;' >
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
							if($synonymStr){
								echo $synonymStr;
							}
							if($translationStr){
								echo $translationStr;
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
						
						<?php
						if(isset($glossary_indexBanner)){
							if($sourceArr){
								?>
								<div id="sourcetoggle" style="float:right;clear:both;margin-top:8px;margin-bottom:8px;">
									<div style="font-size:12px;" onclick="toggle('sourcesdiv');return false;">
										<a href="#">Show Sources for <?php echo $sciName; ?></a>
									</div>
								</div>
								<div id="sourcesdiv" style="display:none;clear:both;">
									<?php
									if($sourceArr['contributorTerm']){
										?>
										<div style="">
											<b>Term and Definition contributed by:</b> <?php echo $sourceArr['contributorTerm']; ?>
										</div>
										<?php
									}
									if($sourceArr['contributorImage'] && $termImgArr){
										?>
										<div style="margin-top:8px;">
											<b>Image contributed by:</b> <?php echo $sourceArr['contributorImage']; ?>
										</div>
										<?php
									}
									if($sourceArr['translator'] && $translationStr){
										?>
										<div style="margin-top:8px;">
											<b>Translation by:</b> <?php echo $sourceArr['translator']; ?>
										</div>
										<?php
									}
									if($sourceArr['additionalSources'] && ($translationStr || $termImgArr)){
										?>
										<div style="margin-top:8px;">
											<b>Translation and/or image were also sourced from the following references:</b> <?php echo $sourceArr['additionalSources']; ?>
										</div>
										<?php
									}
									?>
								</div>
								<?php
							}
						}
						if(!$isEditor){
							?>
							<div style="clear:both;margin-bottom:10px;margin-top:12px;font-size:12px;">
								<a href="mailto:<?php echo $adminEmail; ?>" target="_top">Comment or suggest an addition, correction, or change</a>
							</div>
							<?php
						}
						?>
					</div>
				</div>
				
				<?php
				if($termImgArr){
					?>
					<div id="termimagediv" style="float:right;width:250px;padding:10px;">
						<?php
						foreach($termImgArr as $imgId => $imgArr){
							$imgUrl = $imgArr["url"];
							if(array_key_exists("imageDomain",$GLOBALS)){
								if(substr($imgUrl,0,1)=="/"){
									$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
								}
							}			
							$displayUrl = $imgUrl;
							?>
							<fieldset style='clear:both;border:0px;padding:0px;margin-top:10px;'>
								<div style='width:250px;'>
									<a href='<?php echo $imgArr['url']; ?>' target="_blank">
										<img border=1 style="width:250px;" src='<?php echo $displayUrl; ?>' title='<?php echo $imgArr['structures']; ?>'/>
									</a>
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
				?>
			</div>
			<div style="clear:both"></div>
		</div>
	</div>
</body>
</html>