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

$statusStr = '';
if($formSubmit){
	if($formSubmit == 'Create Term'){
		$statusStr = $glosManager->createTerm($_POST,1);
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
	elseif($formSubmit == 'Add Relation'){
		$statusStr = $glosManager->addRelation($_POST);
	}
	elseif($formSubmit == 'Remove Relation'){
		$glosManager->removeRelation($_POST);
	}
}

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
				<a href='index.php?language=<?php echo $termArr['language']; ?>&tid=<?php echo $termArr['tid']; ?>'> <b>Glossary Management</b></a>
			</div>
			<?php 
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href='../index.php'>Home</a> &gt;&gt; 
			<a href='index.php?language=<?php echo $termArr['language']; ?>&tid=<?php echo $termArr['tid']; ?>'> <b>Glossary Management</b></a>
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
					<li><a href="#termdetaildiv">Details</a></li>
					<li><a href="#termsyndiv">Synonyms</a></li>
					<li><a href="#termtransdiv">Translations</a></li>
					<li><a href="#termimagediv">Images</a></li>
					<li><a href="#termadmindiv">Admin</a></li>
				</ul>
				
				<div id="termdetaildiv" style="">
					<div id="termdetails" style="overflow:auto;">
						<form name="termeditform" id="termeditform" action="termdetails.php" method="post" onsubmit="return verifyNewTermForm(this.form);">
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b>Term: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<input type="text" name="term" id="term" maxlength="45" style="width:400px;" value="<?php echo $termArr['term']; ?>" onchange="" title="" />
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b>Definition: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<textarea name="definition" id="definition" rows="10" style="width:380px;height:70px;resize:vertical;" ><?php echo $termArr['definition']; ?></textarea>
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b>Language: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<input type="text" name="language" id="language" maxlength="45" style="width:200px;" value="<?php echo $termArr['language']; ?>" onchange="" title="" />
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b>Source: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<input type="text" name="source" id="source" maxlength="45" style="width:200px;" value="<?php echo $termArr['source']; ?>" onchange="" title="" />
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b>Notes: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<textarea name="notes" id="notes" rows="10" style="width:380px;height:40px;resize:vertical;" ><?php echo $termArr['notes']; ?></textarea>
								</div>
							</div>
							<div style="clear:both;padding-top:4px;float:left;">
								<div style="float:left;">
									<b>Taxonomic Group: </b>
								</div>
								<div style="float:left;margin-left:10px;">
									<input type="text" name="taxagroup" id="taxagroup" maxlength="45" style="width:250px;" value="<?php echo $termArr['SciName']; ?>" onchange="" title="" />
									<input name="tid" id="tid" type="hidden" value="<?php echo $termArr['tid']; ?>" />
								</div>
							</div>
							<div style="clear:both;padding-top:8px;float:right;">
								<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
								<input name="glossgrpid" type="hidden" value="<?php echo $glossgrpId; ?>" />
								<input id="origterm" type="hidden" value="<?php echo $termArr['term']; ?>" />
								<input id="origlang" type="hidden" value="<?php echo $termArr['language']; ?>" />
								<input name="origtid" id="origtid" type="hidden" value="<?php echo $termArr['tid']; ?>" />
								<button name="formsubmit" type="submit" value="Edit Term">Save Edits</button>
							</div>
						</form>
					</div>
				</div>
				
				<div id="termsyndiv" style="">
					<div style="margin:10px;float:right;cursor:pointer;<?php echo (!$synonymArr?'display:none;':''); ?>" onclick="toggle('addsyndiv');" title="Add a New Synonym">
						<img style="border:0px;width:12px;" src="../images/add.png" />
					</div>
					<div id="addsyndiv" style="margin-bottom:10px;<?php echo ($synonymArr?'display:none;':''); ?>;">
						<form name="synnewform" action="termdetails.php#termsyndiv" method="post" onsubmit="return verifyNewSynForm(this.form);">
							<fieldset style="padding:15px">
								<legend><b>Add a New Synonym</b></legend>
								<div style="clear:both;padding-top:4px;float:left;">
									<div style="float:left;">
										<b>Term: </b>
									</div>
									<div style="float:left;margin-left:10px;">
										<input type="text" name="term" id="newsynterm" maxlength="45" style="width:400px;" value="" onchange="lookupNewsynonym(this.form);" title="" />
									</div>
								</div>
								<div style="clear:both;padding-top:4px;float:left;">
									<div style="float:left;">
										<b>Definition: </b>
									</div>
									<div style="float:left;margin-left:10px;">
										<textarea name="definition" id="newsyndefinition" rows="10" style="width:380px;height:70px;resize:vertical;" DISABLED></textarea>
									</div>
								</div>
								<div style="clear:both;padding-top:4px;float:left;">
									<div style="float:left;">
										<b>Source: </b>
									</div>
									<div style="float:left;margin-left:10px;">
										<input type="text" name="source" id="newsynsource" maxlength="45" style="width:200px;" value="" onchange="" title="" DISABLED />
									</div>
								</div>
								<div style="clear:both;padding-top:4px;float:left;">
									<div style="float:left;">
										<b>Notes: </b>
									</div>
									<div style="float:left;margin-left:10px;">
										<textarea name="notes" id="newsynnotes" rows="10" style="width:380px;height:40px;resize:vertical;" DISABLED></textarea>
									</div>
								</div>
								<div style="clear:both;padding-top:8px;float:right;">
									<input name="glossid" type="hidden" value="<?php echo $glossgrpId; ?>" />
									<input name="relglossid" id="synglossid" type="hidden" value="" />
									<input name="glossgrpid" type="hidden" value="<?php echo $glossgrpId; ?>" />
									<input name="relglossgrpid" id="newsynglossgrpid" type="hidden" value="" />
									<input name="language" id="newsynlanguage" type="hidden" value="<?php echo $termArr['language']; ?>" />
									<input name="tid" id="newsyntid" type="hidden" value="<?php echo $termArr['tid']; ?>" />
									<input id="synorigterm" type="hidden" value="<?php echo $termArr['term']; ?>" />
									<button name="formsubmit" type="submit" value="Add Relation">Add Synonym</button>
								</div>
							</fieldset>
						</form>
					</div>
					<?php
					if($synonymArr){
						foreach($synonymArr as $synId => $synArr){
							?>
							<fieldset style='clear:both;padding:8px;margin-bottom:10px;'>
								<div style="float:right;margin-right:15px;cursor:pointer;" onclick="" title="Remove Synonym">
									<form name="syndelform" action="termdetails.php#termsyndiv" method="post" onsubmit="">
										<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
										<input name="glossgrpid" type="hidden" value="<?php echo $glossgrpId; ?>" />
										<input name="gltlinkid" type="hidden" value="<?php echo $synArr['gltlinkid']; ?>" />
										<input name="tid" type="hidden" value="<?php echo $termArr['tid']; ?>" />
										<input name="relglossid" type="hidden" value="<?php echo $synArr['glossid']; ?>" />
										<input type="image" name="formsubmit" src='../images/del.png'  value="Remove Relation" title="Remove Synonym">
									</form>
								</div>
								<div style="float:right;margin-right:10px;cursor:pointer;" onclick="" title="Edit Term">
									<a href="termdetails.php?glossid=<?php echo $synArr['glossid']; ?>" onclick="">
										<img style="border:0px;width:12px;" src="../images/edit.png" />
									</a>
								</div>
								<div style='' >
									<b>Term:</b> 
									<?php echo $synArr['term']; ?>
								</div>
								<div style='margin-top:8px;' >
									<b>Definition:</b> 
									<?php echo $synArr['definition']; ?>
								</div>
								<div style='margin-top:8px;' >
									<b>Language:</b> 
									<?php echo $synArr['language']; ?>
								</div>
								<div style='margin-top:8px;' >
									<b>Source:</b> 
									<?php echo $synArr['source']; ?>
								</div>
							</fieldset>
							<?php
						}
					}
					else{
						echo '<div style="margin-top:10px;"><div style="font-weight:bold;font-size:120%;">There are no synonyms for this term.</div></div>';
					}
					?>
				</div>
				
				<div id="termtransdiv" style="">
					<div style="margin:10px;float:right;cursor:pointer;<?php echo (!$translationArr?'display:none;':''); ?>" onclick="toggle('addtransdiv');" title="Add a New Translation">
						<img style="border:0px;width:12px;" src="../images/add.png" />
					</div>
					<div id="addtransdiv" style="margin-bottom:10px;<?php echo ($translationArr?'display:none;':''); ?>;">
						<form name="transnewform" action="termdetails.php#termtransdiv" method="post" onsubmit="return verifyNewTransForm(this.form);">
							<fieldset style="padding:15px">
								<legend><b>Add a New Translation</b></legend>
								<div style="clear:both;padding-top:4px;float:left;">
									<div style="float:left;">
										<b>Language: </b>
									</div>
									<div style="float:left;margin-left:10px;">
										<input type="text" name="language" id="newtranslanguage" maxlength="45" style="width:200px;" value="" onchange="lookupNewtranslation(this.form);" title="" />
									</div>
								</div>
								<div style="clear:both;padding-top:4px;float:left;">
									<div style="float:left;">
										<b>Term: </b>
									</div>
									<div style="float:left;margin-left:10px;">
										<input type="text" name="term" id="newtransterm" maxlength="45" style="width:400px;" value="" onchange="lookupNewtranslation(this.form);" title="" />
									</div>
								</div>
								<div style="clear:both;padding-top:4px;float:left;">
									<div style="float:left;">
										<b>Definition: </b>
									</div>
									<div style="float:left;margin-left:10px;">
										<textarea name="definition" id="newtransdefinition" rows="10" style="width:380px;height:70px;resize:vertical;" DISABLED></textarea>
									</div>
								</div>
								<div style="clear:both;padding-top:4px;float:left;">
									<div style="float:left;">
										<b>Source: </b>
									</div>
									<div style="float:left;margin-left:10px;">
										<input type="text" name="source" id="newtranssource" maxlength="45" style="width:200px;" value="" onchange="" title="" DISABLED />
									</div>
								</div>
								<div style="clear:both;padding-top:4px;float:left;">
									<div style="float:left;">
										<b>Notes: </b>
									</div>
									<div style="float:left;margin-left:10px;">
										<textarea name="notes" id="newtransnotes" rows="10" style="width:380px;height:40px;resize:vertical;" DISABLED></textarea>
									</div>
								</div>
								<div style="clear:both;padding-top:8px;float:right;">
									<input name="glossid" type="hidden" value="<?php echo $glossgrpId; ?>" />
									<input name="relglossid" id="transglossid" type="hidden" value="" />
									<input name="glossgrpid" type="hidden" value="<?php echo $glossgrpId; ?>" />
									<input name="relglossgrpid" id="newtransglossgrpid" type="hidden" value="" />
									<input name="tid" id="newtranstid" type="hidden" value="<?php echo $termArr['tid']; ?>" />
									<input id="transoriglanguage" type="hidden" value="<?php echo $termArr['language']; ?>" />
									<button name="formsubmit" type="submit" value="Add Relation">Add Translation</button>
								</div>
							</fieldset>
						</form>
					</div>
					<?php
					if($translationArr){
						foreach($translationArr as $transId => $transArr){
							?>
							<fieldset style='clear:both;padding:8px;margin-bottom:10px;'>
								<div style="float:right;margin-right:15px;cursor:pointer;" onclick="" title="Remove Translation">
									<form name="transdelform" action="termdetails.php#termtransdiv" method="post" onsubmit="">
										<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
										<input name="glossgrpid" type="hidden" value="<?php echo $glossgrpId; ?>" />
										<input name="gltlinkid" type="hidden" value="<?php echo $transArr['gltlinkid']; ?>" />
										<input name="tid" type="hidden" value="<?php echo $termArr['tid']; ?>" />
										<input name="relglossid" type="hidden" value="<?php echo $transArr['glossid']; ?>" />
										<input type="image" name="formsubmit" src='../images/del.png'  value="Remove Relation" title="Remove Translation">
									</form>
								</div>
								<div style="float:right;margin-right:10px;cursor:pointer;" onclick="" title="Edit Term Data">
									<a href="termdetails.php?glossid=<?php echo $transArr['glossid']; ?>" onclick="">
										<img style="border:0px;width:12px;" src="../images/edit.png" />
									</a>
								</div>
								<div style='' >
									<b>Term:</b> 
									<?php echo $transArr['term']; ?>
								</div>
								<div style='margin-top:8px;' >
									<b>Definition:</b> 
									<?php echo $transArr['definition']; ?>
								</div>
								<div style='margin-top:8px;' >
									<b>Language:</b> 
									<?php echo $transArr['language']; ?>
								</div>
								<div style='margin-top:8px;' >
									<b>Source:</b> 
									<?php echo $transArr['source']; ?>
								</div>
							</fieldset>
							<?php
						}
					}
					else{
						echo '<div style="margin-top:10px;"><div style="font-weight:bold;font-size:120%;">There are no translations for this term.</div></div>';
					}
					?>
				</div>
				
				<div id="termimagediv" style="min-height:300px;">
					<div id="imagediv" style="">
						<div style="margin:10px;float:right;cursor:pointer;<?php echo (!$termImgArr?'display:none;':''); ?>" onclick="toggle('addimgdiv');" title="Add a New Image">
							<img style="border:0px;width:12px;" src="../images/add.png" />
						</div>
						<div id="addimgdiv" style="<?php echo ($termImgArr?'display:none;':''); ?>;">
							<form name="imgnewform" action="termdetails.php#termimagediv" method="post" enctype="multipart/form-data" onsubmit="return verifyNewImageForm(this.form);">
								<fieldset style="padding:15px">
									<legend><b>Add a New Image</b></legend>
									<div style='padding:15px;border:1px solid yellow;background-color:FFFF99;'>
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
									<div style="clear:both;padding-top:4px;float:left;">
										<div style="float:left;">
											<b>Structures:</b>
										</div>
										<div style="float:left;margin-left:10px;">
											<textarea name="structures" id="structures" rows="10" style="width:380px;height:50px;resize:vertical;" ></textarea>
										</div>
									</div>
									<div style="clear:both;padding-top:4px;float:left;">
										<div style="float:left;">
											<b>Notes:</b> 
										</div>
										<div style="float:left;margin-left:10px;">
											<textarea name="notes" id="notes" rows="10" style="width:380px;height:70px;resize:vertical;" ></textarea>
										</div>
									</div>
									<div style="clear:both;padding-top:8px;float:right;">
										<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
										<input name="glossgrpid" type="hidden" value="<?php echo $glossgrpId; ?>" />
										<input type="submit" name="formsubmit" value="Submit New Image" />
									</div>
								</fieldset>
							</form>
						</div>
						<div style="clear:both;">
							<?php
							if($termImgArr){
								$hasImages = false;
								foreach($termImgArr as $imgId => $imgArr){
									$termImage = false;
									if($imgArr["glossid"] == $glossId){
										$termImage = true;
										$hasImages = true;
									}
									?>
									<fieldset style="margin-top:10px;">
										<div style="float:right;cursor:pointer;" onclick="toggle('img<?php echo $imgId; ?>editdiv');" title="Edit Image MetaData">
											<img style="border:0px;width:12px;" src="../images/edit.png" />
										</div>
										<div style="float:left;">
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
										</div>
										<div style="float:left;margin-left:10px;padding:10px;">
											<div style="">
												<?php
												if($imgArr["structures"]){
													?>
													<div style="overflow:hidden;">
														<b>Structures:</b> 
														<?php echo wordwrap($imgArr["structures"], 420, "<br />\n"); ?>
													</div>
													<?php
												}
												if($imgArr["notes"]){
													?>
													<div style="overflow:hidden;margin-top:8px;">
														<b>Notes:</b> 
														<?php echo wordwrap($imgArr["notes"], 420, "<br />\n"); ?>
													</div>
													<?php
												}
												?>
											</div>
										</div>
										<div id="img<?php echo $imgId; ?>editdiv" style="display:none;clear:both;">
											<form name="img<?php echo $imgId; ?>editform" action="termdetails.php" method="post" onsubmit="return verifyImageEditForm(this.form);">
												<fieldset style="">
													<legend><b>Edit Image Data</b></legend>
													<div style="clear:both;">
														<div style="float:left;">
															<b>Structures:</b>
														</div>
														<div style="float:left;margin-left:10px;">
															<textarea name="structures" id="structures" rows="10" style="width:380px;height:50px;resize:vertical;" ><?php echo $imgArr["structures"]; ?></textarea>
														</div>
													</div>
													<div style="clear:both;padding-top:10px;">
														<div style="float:left;">
															<b>Notes:</b> 
														</div>
														<div style="float:left;margin-left:10px;">
															<textarea name="notes" id="notes" rows="10" style="width:380px;height:70px;resize:vertical;" ><?php echo $imgArr["notes"]; ?></textarea>
														</div>
													</div>
													<div style="clear:both;">
														<?php
														if($termImage){
															?>
															<div style="padding-top:8px;float:left;">
																<input type="submit" name="formsubmit" onclick="return confirm('Are you sure you want to permanently delete this image?');" value="Delete Image" />
															</div>
															<?php
														}
														?>
														<div style="padding-top:8px;float:right;">
															<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
															<input name="glossgrpid" type="hidden" value="<?php echo $glossgrpId; ?>" />
															<input type="hidden" name="glimgid" value="<?php echo $imgId; ?>" />
															<input type="submit" name="formsubmit" value="Save Image Edits" />
														</div>
													</div>
												</fieldset>
											</form>
										</div>
									</fieldset>
									<?php 
								}
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
							if($hasImages){
								echo '<div style="font-weight:bold;margin-bottom:15px;">';
								echo 'Term cannot be deleted until all linked images are deleted.';
								echo '</div>';
							}
							?>
							<input name="formsubmit" type="submit" value="Delete Term" <?php if($hasImages) echo 'DISABLED'; ?> />
							<input name="glossid" type="hidden" value="<?php echo $glossId; ?>" />
							<input name="glossgrpid" type="hidden" value="<?php echo $glossgrpId; ?>" />
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