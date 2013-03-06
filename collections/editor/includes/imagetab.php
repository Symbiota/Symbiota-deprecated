<?php
include_once('../../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
header("Content-Type: text/html; charset=".$charset);

$occId = $_GET['occid'];
$occIndex = $_GET['occindex'];
$tid = $_GET['tid'];
$instCode = $_GET['instcode'];

$occManager = new OccurrenceEditorImages();

$occManager->setOccId($occId); 
$imageArr = $occManager->getImageMap();

?>
<div id="imagediv" style="width:795px;">
	<div style="float:right;cursor:pointer;" onclick="toggle('addimgdiv');" title="Add a New Image">
		<img style="border:0px;width:12px;" src="../../images/add.png" />
	</div>
	<div id="addimgdiv" style="display:<?php echo ($imageArr?'none':''); ?>;">
		<form name="imgnewform" action="occurrenceeditor.php" method="post" enctype="multipart/form-data" onsubmit="return verifyImgAddForm(this);">
			<fieldset>
				<legend><b>Add a New Image</b></legend>
				<div style='padding:10px;width:90%;border:1px solid yellow;background-color:FFFF99;'>
					<div class="targetdiv" style="display:block;">
						<div style="font-weight:bold;font-size:110%;margin-bottom:5px;">
							Select an image file located on your computer that you want to upload:
						</div>
				    	<!-- following line sets MAX_FILE_SIZE (must precede the file input field)  -->
						<input type='hidden' name='MAX_FILE_SIZE' value='10000000' />
						<div>
							<input name='imgfile' type='file' size='70'/>
						</div>
						<div style="margin:10px 0px 0px 350px;cursor:pointer;text-decoration:underline;font-weight:bold;" onclick="toggle('targetdiv')">
							Link to External Image
						</div>
					</div>
					<div class="targetdiv" style="display:none;">
						<div style="margin-bottom:10px;">
							Enter a URL to an image already located on a local or remote web server. 
							If a thumbanil or large version also exists in addition to central image, 
							enter those urls in the appropriate fields. The central and thumbnail urls 
							must be JPGs, though the large image can be a dynamic resource (e.g. Zoomify resource).  
						</div>
						<div>
							<b>Central Image URL:</b><br/> 
							<input type='text' name='imgurl' size='70'/>
						</div>
						<div>
							<b>Thumbnail URL:</b><br/> 
							<input type='text' name='tnurl' size='70'/>
						</div>
						<div>
							<b>Large Image URL:</b><br/>
							<input type='text' name='lgurl' size='70'/>
						</div>
						<div>
							<input type="checkbox" name="copytoserver" value="1" /> Copy to Server
						</div>
						<div style="margin:10px 0px 0px 350px;cursor:pointer;text-decoration:underline;font-weight:bold;" onclick="toggle('targetdiv')">
							Upload Local Image
						</div>
					</div>
				</div>
				<div style="clear:both;margin:20px 0px 5px 10px;">
					<b>Caption:</b> 
					<input name="caption" type="text" size="40" value="" />
				</div>
				<div style='margin:0px 0px 5px 10px;'>
					<b>Photographer:</b> 
					<select name='photographeruid' name='photographeruid'>
						<option value="">Select Photographer</option>
						<option value="">---------------------------------------</option>
						<?php
							$pArr = $occManager->getPhotographerArr();
							foreach($pArr as $id => $uname){
								echo '<option value="'.$id.'" >';
								echo $uname;
								echo '</option>';
							}
						?>
					</select>
					<a href="#" onclick="toggle('imgaddoverride');return false;" title="Display photographer override field">
						<img src="../../images/editplus.png" style="border:0px;width:13px;" />
					</a>
				</div>
				<div id="imgaddoverride" style="margin:0px 0px 5px 10px;display:none;">
					<b>Photographer (override):</b> 
					<input name='photographer' type='text' style="width:300px;" maxlength='100'>
					* Will override above selection
				</div>
				<div style="margin:0px 0px 5px 10px;">
					<b>Notes:</b> 
					<input name="notes" type="text" size="40" value="" />
				</div>
				<div style="margin:0px 0px 5px 10px;">
					<b>Copyright:</b>
					<input name="copyright" type="text" size="40" value="" />
				</div>
				<div style="margin:0px 0px 5px 10px;">
					<b>Source Webpage:</b>
					<input name="sourceurl" type="text" size="40" value="" />
				</div>
				<div style="margin-left:10px;">
					<input type="checkbox" name="nolgimage" value="1" /> Do not keep large version of image, when applicable 
				</div>
				<div style="margin:10px 0px 10px 20px;">
					<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
					<input type="hidden" name="tid" value="<?php echo $tid; ?>" />
					<input type="hidden" name="institutioncode" value="<?php echo $instCode; ?>" />
					<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
					<input type="submit" name="submitaction" value="Submit New Image" />
				</div>
			</fieldset>
		</form>
		<hr style="margin:30px 0px;" />
	</div>
	<div style="clear:both;margin:15px;">
		<?php
		if($imageArr){
			?>
			<table>
			<?php 
			foreach($imageArr as $imgId => $imgArr){
				?>
				<tr>
					<td style="width:300px;text-align:center;padding:20px;">
						<?php
						$imgUrl = $imgArr["url"];
						$origUrl = $imgArr["origurl"];
						$tnUrl = $imgArr["tnurl"];
						if(array_key_exists("imageDomain",$GLOBALS)){
							if(substr($imgUrl,0,1)=="/"){
								$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
							}
							if($origUrl && substr($origUrl,0,1)=="/"){
								$origUrl = $GLOBALS["imageDomain"].$origUrl;
							}
							if($tnUrl && substr($tnUrl,0,1)=="/"){
								$tnUrl = $GLOBALS["imageDomain"].$tnUrl;
							}
						}
						$displayUrl = $imgUrl;
						if(strtolower(substr($displayUrl,-4)) != '.jpg' && $tnUrl){
							$displayUrl = $tnUrl;
						}
						?>
						<a href="<?php echo $imgUrl;?>" target="_blank">
							<img src="<?php echo $displayUrl;?>" style="width:250px;" title="<?php echo $imgArr["caption"]; ?>" />
						</a>
						<?php 
						if($origUrl){
							echo "<div><a href='".$origUrl."'>Click on Image to Enlarge</a></div>";
						}
						?>
					</td>
					<td style="text-align:left;padding:10px;">
						<div style="float:right;cursor:pointer;" onclick="toggle('img<?php echo $imgId; ?>editdiv');" title="Edit Image MetaData">
							<img style="border:0px;width:12px;" src="../../images/edit.png" />
						</div>
						<div style="margin-top:30px;">
							<div>
								<b>Caption:</b> 
								<?php echo $imgArr["caption"]; ?>
							</div>
							<div>
								<b>Photographer:</b> 
								<?php
								if($imgArr["photographer"]){
									echo $imgArr["photographer"];
								}
								else if($imgArr["photographeruid"]){
									$pArr = $occManager->getPhotographerArr();
									echo $pArr[$imgArr["photographeruid"]];
								} 
								?>
							</div>
							<div>
								<b>Notes:</b> 
								<?php echo $imgArr["notes"]; ?>
							</div>
							<div>
								<b>Copyright:</b>
								<?php echo $imgArr["copyright"]; ?>
							</div>
							<div>
								<b>Source Webpage:</b>
								<a href="<?php echo $imgArr["sourceurl"]; ?>">
									<?php echo $imgArr["sourceurl"]; ?>
								</a>
							</div>
							<div>
								<b>Web URL: </b>
								<a href="<?php echo $imgArr["url"]; ?>">
									<?php echo $imgArr["url"]; ?>
								</a>
							</div>
							<div>
								<b>Large Image URL: </b>
								<a href="<?php echo $imgArr["origurl"]; ?>">
									<?php echo $imgArr["origurl"]; ?>
								</a>
							</div>
							<div>
								<b>Thumbnail URL: </b>
								<a href="<?php echo $imgArr["tnurl"]; ?>">
									<?php echo $imgArr["tnurl"]; ?>
								</a>
							</div>
						</div>
					</td>
				</tr>
				<tr>
					<td colspan="2">
						<div id="img<?php echo $imgId; ?>editdiv" style="display:none;clear:both;">
							<form name="img<?php echo $imgId; ?>editform" action="occurrenceeditor.php" method="post" onsubmit="return verifyImgEditForm(this);">
								<fieldset>
									<legend><b>Edit Image Data</b></legend>
									<div>
										<b>Caption:</b><br/> 
										<input name="caption" type="text" value="<?php echo $imgArr["caption"]; ?>" style="width:300px;" />
									</div>
									<div>
										<b>Photographer:</b><br/> 
										<select name='photographeruid' name='photographeruid'>
											<option value="">Select Photographer</option>
											<option value="">---------------------------------------</option>
											<?php
											$pArr = $occManager->getPhotographerArr();
											foreach($pArr as $id => $uname){
												echo "<option value='".$id."' ".($id == $imgArr["photographeruid"]?"SELECTED":"").">";
												echo $uname;
												echo "</option>\n";
											}
											?>
										</select>
										<a href="#" onclick="toggle('imgeditoverride<?php echo $imgId; ?>');return false;" title="Display photographer override field">
											<img src="../../images/editplus.png" style="border:0px;width:13px;" />
										</a>
									</div>
									<div id="imgeditoverride<?php echo $imgId; ?>" style="display:<?php echo ($imgArr["photographer"]?'block':'none'); ?>;">
										<b>Photographer (override):</b><br/> 
										<input name='photographer' type='text' value="<?php echo $imgArr["photographer"]; ?>" style="width:300px;" maxlength='100'>
										* Warning: value will override above selection
									</div>
									<div>
										<b>Notes:</b><br/>
										<input name="notes" type="text" value="<?php echo $imgArr["notes"]; ?>" style="width:90%;" />
									</div>
									<div>
										<b>Copyright:</b><br/>
										<input name="copyright" type="text" value="<?php echo $imgArr["copyright"]; ?>" style="width:90%;" />
									</div>
									<div>
										<b>Source Webpage:</b><br/>
										<input name="sourceurl" type="text" value="<?php echo $imgArr["sourceurl"]; ?>" style="width:90%;" />
									</div>
									<div>
										<b>Web URL: </b><br/>
										<input name="url" type="text" value="<?php echo $imgArr["url"]; ?>" style="width:90%;" />
										<?php if(stripos($imgArr["url"],$imageRootUrl) === 0){ ?>
											<div style="margin-left:10px;">
												<input type="checkbox" name="renameweburl" value="1" />
												Rename web image file on server to match above edit
											</div>
											<input name='oldurl' type='hidden' value='<?php echo $imgArr["url"];?>' />
										<?php } ?>
									</div>
									<div>
										<b>Large Image URL: </b><br/>
										<input name="origurl" type="text" value="<?php echo $imgArr["origurl"]; ?>" style="width:90%;" />
										<?php if(stripos($imgArr["origurl"],$imageRootUrl) === 0){ ?>
											<div style="margin-left:10px;">
												<input type="checkbox" name="renameorigurl" value="1" />
												Rename large image file on server to match above edit
											</div>
											<input name='oldorigurl' type='hidden' value='<?php echo $imgArr["origurl"];?>' />
										<?php } ?>
									</div>
									<div>
										<b>Thumbnail URL: </b><br/>
										<input name="tnurl" type="text" value="<?php echo $imgArr["tnurl"]; ?>" style="width:90%;" />
										<?php if(stripos($imgArr["tnurl"],$imageRootUrl) === 0){ ?>
											<div style="margin-left:10px;">
												<input type="checkbox" name="renametnurl" value="1" />
												Rename thumbnail file on server to match above edit
											</div>
											<input name='oldtnurl' type='hidden' value='<?php echo $imgArr["tnurl"];?>' />
										<?php } ?>
									</div>
									<div style="margin-top:10px;">
										<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
										<input type="hidden" name="imgid" value="<?php echo $imgId; ?>" />
										<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
										<input type="submit" name="submitaction" value="Submit Image Edits" />
									</div>
								</fieldset>
							</form>
							<?php 
							if($_REQUEST['em'] == 1 || $paramsArr['un'] == $imgArr["username"]){
								?>
								<form name="img<?php echo $imgId; ?>delform" action="occurrenceeditor.php" method="post" onsubmit="return verifyImgDelForm(this);">
									<fieldset>
										<legend><b>Delete Image</b></legend>
										<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
										<input type="hidden" name="imgid" value="<?php echo $imgId; ?>" />
										<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
										<input name="removeimg" type="checkbox" value="1" /> Remove image from server 
										<div style="margin-left:20px;">
											(Note: leaving unchecked removes image from database w/o removing from server)
										</div>
										<input type="submit" name="submitaction" value="Delete Image" />
									</fieldset>
								</form>
								<form name="img<?php echo $imgId; ?>remapform" action="occurrenceeditor.php" method="post" onsubmit="return verifyImgRemapForm(this);">
									<fieldset>
										<legend><b>Remap to Another Specimen</b></legend>
										<div>
											<b>Occurrence Record #:</b> 
											<input id="imgoccid-<?php echo $imgId; ?>" name="occid" type="text" value="" />
											<span style="cursor:pointer;color:blue;"  onclick="openOccurrenceSearch('imgoccid-<?php echo $imgId; ?>')">
												Open Occurrence Linking Aid
											</span>
										</div>
										<div style="margin-left:20px;">
											* Leave Occurrence Record Number blank to completely remove mapping to a specimen record <br/>
											<input type="hidden" name="imgid" value="<?php echo $imgId; ?>" />
											<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
											<input type="submit" name="submitaction" value="Remap Image" />
										</div>
									</fieldset>
								</form>
								<?php
							}
							?>
						</div>
						<hr/>
					</td>
				</tr>
				<?php 
			}
			?>
			</table>
			<?php 
		}
		else{
			?>
			<h2>No images linked to this collection record.</h2>
			<div style="margin-left:15px;">Click symbol to right to add an image</div>
			<?php 
		}
		?>
	</div>
</div>
