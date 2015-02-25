<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/GlossaryManager.php');
header("Content-Type: text/html; charset=".$charset);

$glossId = array_key_exists('glossid',$_REQUEST)?$_REQUEST['glossid']:0;
$glossgrpId = array_key_exists('glossgrpid',$_REQUEST)?$_REQUEST['glossgrpid']:0;
$glimgId = array_key_exists('glimgid',$_REQUEST)?$_REQUEST['glimgid']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$glosManager = new GlossaryManager();
$termArr = array();
$termImgArr = array();
$termGrpArr = array();
$synonymArr = array();
$translationArr = array();

if($glossId){
	$termArr = $glosManager->getTermArr($glossId);
	$glossgrpId = $termArr['glossgrpid'];
	$termGrpArr = $glosManager->getGrpArr($glossId,$glossgrpId,$termArr['language']);
	if(array_key_exists('synonym',$termGrpArr)){
		$synonymArr = $termGrpArr['synonym'];
	}
	if(array_key_exists('translation',$termGrpArr)){
		$translationArr = $termGrpArr['translation'];
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
	<link href="../css/base.css" rel="stylesheet" type="text/css" />
    <link href="../css/main.css" rel="stylesheet" type="text/css" />
	<link href="../css/jquery-ui.css" rel="stylesheet" type="text/css" />
	<style type="text/css">
		#tabs a{
			outline-color: transparent;
			font-size: 12px;
			font-weight: normal;
		}
	</style>
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../js/symb/glossary.index.js"></script>
</head>

<body style="overflow-x:hidden;overflow-y:auto;width:550px;margin-left:auto;margin-right:auto;">
	<!-- This is inner text! -->
	<div id="innertext" >
		<div id="tabs" style="width:550px;padding:0px;margin:0px;">
			<ul>
				<li><a href="#terminfo">Details</a></li>
				<?php
				if($synonymArr){
					?>
					<li><a href="#termsyndiv">Synonyms</a></li>
					<?php
				}
				if($translationArr){
					?>
					<li><a href="#termtransdiv">Translations</a></li>
					<?php
				}
				if($termImgArr){
					?>
					<li><a href="#termimagediv">Images</a></li>
					<?php
				}
				?>
			</ul>
			<div id="terminfo" style="width:540px;padding:10px;">
				<?php
				if($SYMB_UID){
					?>
					<div style="float:right;margin-right:15px;cursor:pointer;" onclick="" title="Edit Term Data">
						<a href="#" onclick="leaveTermPopup('termdetails.php?glossid=<?php echo $glossId;?>'); return false;">
							<img style="border:0px;width:12px;" src="../images/edit.png" />
						</a>
					</div>
					<?php
				}
				?>
				<div style="float:left;">
					<span style="font-size:18px;font-weight:bold;vertical-align:60%;">
						<?php echo $termArr['term']; ?>
					</span>
				</div>
				<div style="clear:both;">
					<div style='width:540px;' >
						<div style='width:530px;margin-top:8px;' >
							<b>Definition:</b> 
							<?php echo $termArr['definition']; ?>
						</div>
						<div style='width:530px;margin-top:8px;' >
							<b>Language:</b> 
							<?php echo $termArr['language']; ?>
						</div>
						<div style='width:530px;margin-top:8px;' >
							<b>Source:</b> 
							<?php echo $termArr['source']; ?>
						</div>
						<?php
						if($termArr['notes']){
							?>
							<div style='width:530px;margin-top:8px;' >
								<b>Notes:</b> 
								<?php echo $termArr['notes']; ?>
							</div>
							<?php
						}
						if($termArr['SciName']){
							?>
							<div style='width:530px;margin-top:8px;' >
								<b>Taxonomic Group:</b> 
								<?php echo $termArr['SciName']; ?>
							</div>
							<?php
						}
						?>
					</div>
					
					<?php
					if(!$SYMB_UID){
						?>
						<div style="margin-bottom:10px;margin-top:8px;">
							See an error? <a href="#" onclick="leaveTermPopup('../profile/index.php?refurl=../glossary/termdetails.php?glossid=<?php echo $glossId; ?>'); return false;">Login</a> to edit data
						</div>
						<?php
					}
					?>
				</div>
			</div>
			
			<?php
			if($synonymArr){
				?>
				<div id="termsyndiv" style="width:540px;padding:10px;">
					<?php
					foreach($synonymArr as $synId => $synArr){
						?>
						<fieldset style='clear:both;width:505px;padding:8px;margin-bottom:10px;'>
							<?php
							if($SYMB_UID){
								?>
								<div style="float:right;margin-right:15px;cursor:pointer;" onclick="" title="Edit Term Data">
									<a href="#" onclick="leaveTermPopup('termdetails.php?glossid=<?php echo $synArr['glossid'];?>'); return false;">
										<img style="border:0px;width:12px;" src="../images/edit.png" />
									</a>
								</div>
								<?php
							}
							?>
							<div style='width:490px;' >
								<b>Term:</b> 
								<?php echo $synArr['term']; ?>
							</div>
							<div style='width:490px;margin-top:8px;' >
								<b>Definition:</b> 
								<?php echo $synArr['definition']; ?>
							</div>
							<div style='width:490px;margin-top:8px;' >
								<b>Language:</b> 
								<?php echo $synArr['language']; ?>
							</div>
							<div style='width:490px;margin-top:8px;' >
								<b>Source:</b> 
								<?php echo $synArr['source']; ?>
							</div>
						</fieldset>
						<?php
					}
					?>
				</div>
				<?php
			}
			
			if($translationArr){
				?>
				<div id="termtransdiv" style="width:540px;padding:10px;">
					<?php
					foreach($translationArr as $transId => $transArr){
						?>
						<fieldset style='clear:both;width:505px;padding:8px;margin-bottom:10px;'>
							<?php
							if($SYMB_UID){
								?>
								<div style="float:right;margin-right:15px;cursor:pointer;" onclick="" title="Edit Term Data">
									<a href="#" onclick="leaveTermPopup('termdetails.php?glossid=<?php echo $transArr['glossid'];?>'); return false;">
										<img style="border:0px;width:12px;" src="../images/edit.png" />
									</a>
								</div>
								<?php
							}
							?>
							<div style='width:490px;' >
								<b>Term:</b> 
								<?php echo $transArr['term']; ?>
							</div>
							<div style='width:490px;margin-top:8px;' >
								<b>Definition:</b> 
								<?php echo $transArr['definition']; ?>
							</div>
							<div style='width:490px;margin-top:8px;' >
								<b>Language:</b> 
								<?php echo $transArr['language']; ?>
							</div>
							<div style='width:490px;margin-top:8px;' >
								<b>Source:</b> 
								<?php echo $transArr['source']; ?>
							</div>
						</fieldset>
						<?php
					}
					?>
				</div>
				<?php
			}
			
			if($termImgArr){
				?>
				<div id="termimagediv" style="width:540px;padding:10px;">
					<?php
					foreach($termImgArr as $imgId => $imgArr){
						?>
						<fieldset style='clear:both;width:505px;border:0px;padding:0px;'>
							<div style='float:left;width:200px;padding:5px;'>
								<a href='<?php echo $imgArr['url']; ?>' target="_blank">
									<img border=1 style="width:200px;" src='<?php echo ($imgArr['thumbnailurl']?$imgArr['thumbnailurl']:$imgArr['url']); ?>' title='<?php echo $imgArr['structures']; ?>'/>
								</a>
							</div>
							<div style='float:right;width:290px;'>
								<?php
								if($imgArr['structures']){
									?>
									<div style='float:right;overflow:hidden;width:290px;margin-top:8px;' >
										<b>Structures:</b> 
										<?php echo wordwrap($imgArr["structures"], 370, "<br />\n"); ?>
									</div>
									<?php
								}
								if($imgArr['notes']){
									?>
									<div style='float:right;overflow:hidden;width:290px;margin-top:8px;' >
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
	</div>
</body>
</html> 
