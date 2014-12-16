<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/GlossaryManager.php');
header("Content-Type: text/html; charset=".$charset);

$glossId = array_key_exists('glossid',$_REQUEST)?$_REQUEST['glossid']:0;
$glimgId = array_key_exists('glimgid',$_REQUEST)?$_REQUEST['glimgid']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$glosManager = new GlossaryManager();
$termArr = array();
$termImgArr = array();

if($glossId){
	$termArr = $glosManager->getTermArr($glossId);
	$termImgArr = $glosManager->getImgArr($glossId);
}
else{
	header("Location: index.php");
}
?>
<!DOCTYPE html >
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

<body style="overflow-x:hidden;overflow-y:hidden;width:450px;margin:20px;">
	<!-- This is inner text! -->
	<div id="innertext" >
		<fieldset style="background-color:#FFFFFF;width:450px;">
			<div id="terminfo" style="width:450px;padding:10px;">
				<?php
				if($SYMB_UID){
					?>
					<div style="float:right;cursor:pointer;" onclick="" title="Edit Term Data">
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
					<div style='width:580px;' >
						<div style='width:400px;margin-top:8px;' >
							<b>Definition:</b> 
							<?php echo $termArr['definition']; ?>
						</div>
						<div style='width:400px;margin-top:8px;' >
							<b>Language:</b> 
							<?php echo $termArr['language']; ?>
						</div>
						<div style='width:400px;margin-top:8px;' >
							<b>Source:</b> 
							<?php echo $termArr['source']; ?>
						</div>
						<?php
						if($termArr['notes']){
							?>
							<div style='width:400px;margin-top:8px;' >
								<b>Notes:</b> 
								<?php echo $termArr['notes']; ?>
							</div>
							<?php
						}
						if($termImgArr){
							?>
							<div style="clear:both;margin-top:8px;margin-bottom:8px;">
								<fieldset style="width:575px;">
									<legend><b>Images</b></legend>
									<?php 
									foreach($termImgArr as $imgId => $imgArr){
										?>
										<div style='clear:both;width:550px;margin:5px;'>
											<div style='float:left;padding:5px;'>
												<a href='<?php echo $imgArr['url']; ?>' target="_blank">
													<img border=1 width='150' src='<?php echo ($imgArr['thumbnailurl']?$imgArr['thumbnailurl']:$imgArr['url']); ?>' title='<?php echo $imgArr['structures']; ?>'/>
												</a>
											</div>
											<div style='float:right;width:375px;padding:5px;'>
												<?php
												if($imgArr['structures']){
													?>
													<div style='overflow:hidden;width:375px;margin-top:8px;' >
														<b>Structures:</b> 
														<?php echo wordwrap($imgArr["structures"], 370, "<br />\n"); ?>
													</div>
													<?php
												}
												if($imgArr['notes']){
													?>
													<div style='overflow:hidden;width:375px;margin-top:8px;' >
														<b>Notes:</b> 
														<?php echo wordwrap($imgArr["notes"], 370, "<br />\n"); ?>
													</div>
													<?php
												}
												?>
											</div>
										</div>
										<?php
										if(count($termImgArr)>1){
											echo '<hr style="clear:both;margin:10px;" />';
										}
									}
									?>
								</fieldset>
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
		</fieldset>
	</div>
</body>
</html> 
