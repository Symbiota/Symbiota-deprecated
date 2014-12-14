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

$statusStr = '';
if($formSubmit){
	if($formSubmit == 'Create Term'){
		$statusStr = $glosManager->createTerm($_POST);
		$glossId = $glosManager->getTermId();
	}
	elseif($formSubmit == 'Edit Term'){
		$statusStr = $glosManager->editTerm($_POST);
	}
	elseif($formSubmit == 'Submit New Image'){
		$statusStr = $glosManager->addImage($_POST);
	}
	elseif($formSubmit == 'Save Image Edits'){
		$statusStr = $glosManager->editImageData($_POST);
	}
	elseif($formSubmit == 'Delete Image'){
		$statusStr = $glosManager->deleteImage($glimgId,1);
	}
}

if($glossId){
	$termArr = $glosManager->getTermArr($glossId);
	$termImgArr = $glosManager->getImgArr($glossId);
}
else{
	header("Location: index.php");
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <title><?php echo $defaultTitle; ?> Glossary Management</title>
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
<body>
	<?php
	$displayLeftMenu = (isset($glossary_indexMenu)?$glossary_indexMenu:false);
	include($serverRoot."/header.php");
	if(isset($glossary_indexCrumbs)){
		if($glossary_indexCrumbs){
			?>
			<div class='navpath'>
				<a href='../index.php'>Home</a> &gt;&gt; 
				<?php echo $glossary_indexCrumbs; ?>
				<a href='index.php'> <b>Glossary Management</b></a>
			</div>
			<?php 
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href='../index.php'>Home</a> &gt;&gt; 
			<a href='index.php'> <b>Glossary Management</b></a>
		</div>
		<?php 
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($symbUid){
			if($statusStr){
				?>
				<div style="margin:15px;color:red;">
					<?php echo $statusStr; ?>
				</div>
				<?php 
			}
			?>
			<div id="tabs" style="margin:0px;">
				<ul>
					<li><a href="#termdetaildiv">Term Details</a></li>
					<li><a href="#termimagediv">Images</a></li>
					<li><a href="#termadmindiv">Admin</a></li>
				</ul>
				
				<div id="termdetaildiv" style="">
					<div id="termdetails" style="overflow:auto;">
						<form name="termeditform" id="termeditform" action="termdetails.php" method="post" onsubmit="return verifyNewTermForm(this.form);">
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="">
									<b>Term: </b>
								</div>
								<div style="margin-left:40px;margin-top:-14px;">
									<input type="text" name="term" id="term" maxlength="45" style="width:200px;" value="<?php echo $termArr['term']; ?>" onchange="" title="" />
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="">
									<b>Definition: </b>
								</div>
								<div style="margin-left:70px;margin-top:-14px;">
									<textarea name="definition" id="definition" rows="10" style="width:380px;height:70px;resize:vertical;" ><?php echo $termArr['definition']; ?></textarea>
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="">
									<b>Language: </b>
								</div>
								<div style="margin-left:70px;margin-top:-14px;">
									<input type="text" name="language" id="language" maxlength="45" style="width:200px;" value="<?php echo $termArr['language']; ?>" onchange="" title="" />
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="">
									<b>Source: </b>
								</div>
								<div style="margin-left:70px;margin-top:-14px;">
									<input type="text" name="source" id="source" maxlength="45" style="width:200px;" value="<?php echo $termArr['source']; ?>" onchange="" title="" />
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="">
									<b>Notes: </b>
								</div>
								<div style="margin-left:70px;margin-top:-14px;">
									<textarea name="notes" id="notes" rows="10" style="width:380px;height:40px;resize:vertical;" ><?php echo $termArr['notes']; ?></textarea>
								</div>
							</div>
							<div style="clear:both;padding-top:8px;float:right;">
								<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
								<button name="formsubmit" type="submit" value="Edit Term">Save Edits</button>
							</div>
						</form>
					</div>
				</div>
				
				<div id="termimagediv" style="width:725px;">
					<div id="imagediv" style="width:725px;">
						<div style="float:right;cursor:pointer;<?php echo (!$termImgArr?'display:none;':''); ?>" onclick="toggle('addimgdiv');" title="Add a New Image">
							<img style="border:0px;width:12px;" src="../images/add.png" />
						</div>
						<div id="addimgdiv" style="display:<?php echo ($termImgArr?'none':''); ?>;">
							<form name="imgnewform" action="termdetails.php" method="post" enctype="multipart/form-data" onsubmit="return verifyNewImageForm(this.form);">
								<fieldset style="padding:15px">
									<legend><b>Add a New Image</b></legend>
									<div style='padding:15px;width:90%;border:1px solid yellow;background-color:FFFF99;'>
										<div class="targetdiv" style="display:block;">
											<div style="font-weight:bold;font-size:110%;margin-bottom:5px;">
												Select an image file located on your computer that you want to upload:
											</div>
											<!-- following line sets MAX_FILE_SIZE (must precede the file input field)  -->
											<div style="height:10px;float:right;text-decoration:underline;font-weight:bold;">
												<a href="#" onclick="toggle('targetdiv');return false;">Enter URL</a>
											</div>
											<input type='hidden' name='MAX_FILE_SIZE' value='20000000' />
											<div>
												<input name='imgfile' id='imgfile' type='file' size='70'/>
											</div>
										</div>
										<div class="targetdiv" style="display:none;">
											<div style="float:right;text-decoration:underline;font-weight:bold;">
												<a href="#" onclick="toggle('targetdiv');return false;">
													Upload Local Image
												</a>
											</div>
											<div style="margin-bottom:10px;">
												Enter a URL to an image already located on a web server. 
											</div>
											<div>
												<b>Image URL:</b><br/> 
												<input type='text' name='imgurl' id='imgurl' size='70'/>
											</div>
										</div>
									</div>
									<div style="clear:both;margin:20px 0px 5px 10px;">
										<div style="">
											<b>Structures:</b>
										</div>
										<div style="margin-left:70px;margin-top:-14px;">
											<textarea name="structures" id="structures" rows="10" style="width:380px;height:50px;resize:vertical;" ></textarea>
										</div>
									</div>
									<div style="clear:both;margin:20px 0px 5px 10px;">
										<div style="">
											<b>Notes:</b> 
										</div>
										<div style="margin-left:45px;margin-top:-14px;">
											<textarea name="notes" id="notes" rows="10" style="width:380px;height:70px;resize:vertical;" ></textarea>
										</div>
									</div>
									<div style="clear:both;padding-top:8px;float:right;">
										<input type="hidden" name="glossid" value="<?php echo $glossId; ?>" />
										<input type="submit" name="formsubmit" value="Submit New Image" />
									</div>
								</fieldset>
							</form>
							<?php
							if($termImgArr){
								echo '<hr style="margin:30px 0px;" />';
							}
							?>
						</div>
						<div style="clear:both;margin:15px;">
							<?php
							if($termImgArr){
								?>
								<table>
									<?php 
									foreach($termImgArr as $imgId => $imgArr){
										?>
										<tr>
											<td style="width:300px;text-align:center;padding:20px;">
												<?php
												$imgUrl = $imgArr["url"];
												if(array_key_exists("imageDomain",$GLOBALS)){
													if(substr($imgUrl,0,1)=="/"){
														$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
													}
												}			
												$displayUrl = $imgUrl;
												?>
												<a href="<?php echo $imgUrl;?>" target="_blank">
													<img src="<?php echo $displayUrl;?>" style="width:250px;" title="<?php echo $imgArr["structures"]; ?>" />
												</a>
											</td>
											<td style="text-align:left;padding:10px;">
												<div style="float:right;cursor:pointer;" onclick="toggle('img<?php echo $imgId; ?>editdiv');" title="Edit Image MetaData">
													<img style="border:0px;width:12px;" src="../images/edit.png" />
												</div>
												<div style="width:425px;margin-top:10px;">
													<?php
													if($imgArr["structures"]){
														?>
														<div style="overflow:hidden;width:425px;margin-top:8px;">
															<b>Structures:</b> 
															<?php echo wordwrap($imgArr["structures"], 420, "<br />\n"); ?>
														</div>
														<?php
													}
													if($imgArr["notes"]){
														?>
														<div style="overflow:hidden;width:425px;margin-top:8px;">
															<b>Notes:</b> 
															<?php echo wordwrap($imgArr["notes"], 420, "<br />\n"); ?>
														</div>
														<?php
													}
													?>
													<div style="overflow:hidden;width:425px;margin-top:8px;">
														<b>URL: </b>
														<a href="<?php echo $imgArr["url"]; ?>" target="_blank">
															<?php echo wordwrap($imgArr["url"], 420, "<br />\n"); ?>
														</a>
													</div>
												</div>
											</td>
										</tr>
										<tr>
											<td colspan="2">
												<div id="img<?php echo $imgId; ?>editdiv" style="display:none;clear:both;">
													<form name="img<?php echo $imgId; ?>editform" action="termdetails.php" method="post" onsubmit="return verifyImageEditForm(this.form);">
														<fieldset style="padding:15px">
															<legend><b>Edit Image Data</b></legend>
															<div style="clear:both;margin:20px 0px 5px 10px;">
																<div style="">
																	<b>Structures:</b>
																</div>
																<div style="margin-left:70px;margin-top:-14px;">
																	<textarea name="structures" id="structures" rows="10" style="width:380px;height:50px;resize:vertical;" ><?php echo $imgArr["structures"]; ?></textarea>
																</div>
															</div>
															<div style="clear:both;margin:20px 0px 5px 10px;">
																<div style="">
																	<b>Notes:</b> 
																</div>
																<div style="margin-left:45px;margin-top:-14px;">
																	<textarea name="notes" id="notes" rows="10" style="width:380px;height:70px;resize:vertical;" ><?php echo $imgArr["notes"]; ?></textarea>
																</div>
															</div>
															<div style="clear:both;">
																<div style="padding-top:8px;float:left;">
																	<input type="submit" name="formsubmit" onclick="return confirm('Are you sure you want to permanently delete this image?');" value="Delete Image" />
																</div>
																<div style="padding-top:8px;float:right;">
																	<input type="hidden" name="glossid" value="<?php echo $glossId; ?>" />
																	<input type="hidden" name="glimgid" value="<?php echo $imgId; ?>" />
																	<input type="submit" name="formsubmit" value="Save Image Edits" />
																</div>
															</div>
														</fieldset>
													</form>
												</div>
												<?php
												if(count($termImgArr)>1){
													?>
													<hr/>
													<?php
												}
												?>
											</td>
										</tr>
										<?php 
									}
									?>
								</table>
								<?php 
							}
							?>
						</div>
					</div>
				</div>
				
				<div id="termadmindiv" style="">
					<form name="deltermform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this term?')">
						<fieldset style="width:350px;margin:20px;padding:20px;">
							<legend><b>Delete Term</b></legend>
							<?php
							if($termImgArr){
								echo '<div style="font-weight:bold;margin-bottom:15px;">';
								echo 'Term cannot be deleted until all linked images are deleted.';
								echo '</div>';
							}
							?>
							<input name="formsubmit" type="submit" value="Delete Term" <?php if($termImgArr) echo 'DISABLED'; ?> />
							<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
						</fieldset>
					</form>
				</div>
			</div>
			<?php 
		}
		else{
			if(!$symbUid){
				header("Location: ../profile/index.php?refurl=../glossary/termdetails.php?glossid=".$glossId);
			}
			else{
				echo '<h2>ERROR: unknown error, please contact system administrator</h2>';
			}
		}
		?>
	</div>
	<?php
	include($serverRoot."/footer.php");
	?>
</body>
</html>