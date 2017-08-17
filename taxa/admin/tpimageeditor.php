<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TPImageEditorManager.php');
header("Content-Type: text/html; charset=".$charset);

$tid = $_REQUEST["tid"];
$category = array_key_exists("cat",$_REQUEST)?$_REQUEST["cat"]:""; 
$lang = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:"";

$imageEditor = new TPImageEditorManager();
$editable = false;

if($tid){
	$imageEditor->setTid($tid);
	$imageEditor->setLanguage($lang);

	if($isAdmin || array_key_exists("TaxonProfile",$userRights)){
		$editable = true;
	}
	 
}
?>
<!-- This is inner text! --> 
<div id="innertext" style="background-color:white;">
	<?php 
	if($editable && $tid){
		if($category == "imagequicksort"){
			if($images = $imageEditor->getImages()){
				?>
				<div style='clear:both;'>
					<form action='tpeditor.php' method='post' target='_self'>
						<table border='0' cellspacing='0'>
							<tr>
								<?php 
								$imgCnt = 0;
								foreach($images as $imgArr){
									$webUrl = $imgArr["url"]; 
									$tnUrl = $imgArr["thumbnailurl"];
									if($GLOBALS["imageDomain"]){
										if(substr($imgArr["url"],0,1)=="/") $webUrl = $GLOBALS["imageDomain"].$imgArr["url"];
										if(substr($imgArr["thumbnailurl"],0,1)=="/") $tnUrl = $GLOBALS["imageDomain"].$imgArr["thumbnailurl"];
									}
									if(!$tnUrl) $tnUrl = $webUrl;
									?>
									<td align='center' valign='bottom'>
										<div style='margin:20px 0px 0px 0px;'>
											<a href="<?php echo $webUrl; ?>" target="_blank">
												<img width="150" src="<?php echo $tnUrl;?>" />
											</a>
											
										</div>
										<?php 
										if($imgArr["photographerdisplay"]){ 
											?>
											<div>
												<?php echo $imgArr["photographerdisplay"];?>
											</div>
											<?php 
										}
										if($imgArr["tid"] != $tid){ 
											?>
											<div>
												<a href="tpeditor.php?tid=<?php echo $imgArr["tid"];?>" target="" title="Linked from"><?php echo $imgArr["sciname"];?></a>
											</div>
											<?php 
										}
										?>
										<div style='margin-top:2px;'>
											Sort sequence: 
											<b><?php echo $imgArr["sortsequence"];?></b>
										</div>
										<div>
											New Value: 
											<input name="imgid-<?php echo $imgArr["imgid"];?>" type="text" size="5" maxlength="5" />
										</div>
									</td>
									<?php 
									$imgCnt++;
									if($imgCnt%5 == 0){
										?>
										</tr>
										<tr>
											<td colspan='5'>
												<hr>
												<div style='margin-top:2px;'>
													<input type='submit' name='action' id='submit' value='Submit Image Sort Edits' />
												</div>
											</td>
										</tr>
										<tr>
										<?php 
									}
								}
								for($i = (5 - $imgCnt%5);$i > 0; $i--){
									echo "<td>&nbsp;</td>";
								}
								?>
							</tr>
						</table>
						<input name='tid' type='hidden' value='<?php echo $imageEditor->getTid(); ?>'>
						<input type="hidden" name="tabindex" value="2" />
						<?php 
						if($imgCnt%5 != 0) echo "<div style='margin-top:2px;'><input type='submit' name='action' id='imgsortsubmit' value='Submit Image Sort Edits'/></div>\n";
						?>
					</form>
				</div>
				<?php 
			}
			else{
				echo '<h2>No images available.</h2>';
			}
		}
		elseif($category == "imageadd"){
			?>
			<div style='clear:both;'>
				<form enctype='multipart/form-data' action='tpeditor.php' id='imageaddform' method='post' target='_self' onsubmit='return submitAddImageForm(this);'>
					<fieldset style='margin:15px;padding:15px;width:90%;'>
				    	<legend><b>Add a New Image</b></legend>
						<div style='padding:10px;width:550px;border:1px solid yellow;background-color:FFFF99;'>
							<div class="targetdiv" style="display:block;">
								<div style="font-weight:bold;font-size:110%;margin-bottom:5px;">
									Select an image file located on your computer that you want to upload:
								</div>
						    	<!-- following line sets MAX_FILE_SIZE (must precede the file input field)  -->
								<input type='hidden' name='MAX_FILE_SIZE' value='3000000' />
								<div>
									<input name='imgfile' id='imgfile' type='file' size='70'/>
								</div>
								<div style="margin-left:10px;">
									<input type="checkbox" name="createlargeimg" value="1" /> Keep a large version of image, when applicable
								</div>
								<div style="margin-left:10px;">Note: upload image size can not be greater than 2MB</div>
								<div style="margin:10px 0px 0px 350px;cursor:pointer;text-decoration:underline;font-weight:bold;" onclick="toggle('targetdiv')">
									Link to External Image
								</div>
							</div>
							<div class="targetdiv" style="display:none;">
								<div style="font-weight:bold;font-size:110%;margin-bottom:5px;">
									Enter a URL to an image already located on a web server:
								</div>
								<div>
									<b>URL:</b> 
									<input type='text' name='filepath' size='70'/>
								</div>
								<div style="margin-left:10px;">
									<input type="checkbox" name="importurl" value="1" /> Import image to local server
								</div>
								<div style="margin:10px 0px 0px 350px;cursor:pointer;text-decoration:underline;font-weight:bold;" onclick="toggle('targetdiv')">
									Upload Local Image
								</div>
							</div>
						</div>
						
						<!-- Image metadata -->
				    	<div style='margin-top:2px;'>
				    		<b>Caption:</b> 
							<input name='caption' type='text' value='' size='25' maxlength='100'>
						</div>
						<div style='margin-top:2px;'>
							<b>Photographer:</b> 
							<select name='photographeruid' name='photographeruid'>
								<option value="">Select Photographer</option>
								<option value="">---------------------------------------</option>
								<?php $imageEditor->echoPhotographerSelect($paramsArr["uid"]); ?>
							</select>
							<a href="#" onclick="toggle('photooveridediv');return false;" title="Display photographer override field">
								<img src="../../images/editplus.png" style="border:0px;width:12px;" />
							</a>
						</div>
						<div id="photooveridediv" style='margin:2px 0px 5px 10px;display:none;'>
							<b>Photographer Override:</b> 
							<input name='photographer' type='text' value='' size='37' maxlength='100'><br/> 
							* Use only when photographer is not found in above pulldown
						</div>
						<div style="margin-top:2px;" title="Use if manager is different than photographer">
							<b>Manager:</b> 
							<input name='owner' type='text' value='' size='35' maxlength='100'>
						</div>
						<div style='margin-top:2px;' title="URL to source project. Use when linking to an external image.">
							<b>Source URL:</b> 
							<input name='sourceurl' type='text' value='' size='70' maxlength='250'>
						</div>
						<div style='margin-top:2px;'>
							<b>Copyright:</b> 
							<input name='copyright' type='text' value='' size='70' maxlength='250'>
						</div>
						<div style='margin-top:2px;'>
							<b>Occurrence Record #:</b> 
							<input id="occidadd" name="occid" type="text" value=""/>
							<span style="cursor:pointer;color:blue;"  onclick="openOccurrenceSearch('occidadd')">Link to Occurrence Record</span>
						</div>
						<div style='margin-top:2px;'>
							<b>Locality:</b> 
							<input name='locality' type='text' value='' size='70' maxlength='250'>
						</div>
						<div style='margin-top:2px;'>
							<b>Notes:</b> 
							<input name='notes' type='text' value='' size='70' maxlength='250'>
						</div>
						<div style='margin-top:2px;'>
							<b>Sort sequence:</b> 
							<input name='sortsequence' type='text' value='' size='5' maxlength='5'>
						</div>
						<input name="tid" type="hidden" value="<?php echo $imageEditor->getTid();?>">
						<input type="hidden" name="tabindex" value="1" />
						<div style='margin-top:2px;'>
							<input type='submit' name='action' id='imgaddsubmit' value='Upload Image'/>
						</div>
					</fieldset>
				</form>
			</div>
			<?php 
		}
		else{
			if($images = $imageEditor->getImages()){
				?>
				<div style='clear:both;'>
					<table>
						<?php 
						foreach($images as $imgArr){
							?>
							<tr><td>
								<div style="margin:20px;float:left;text-align:center;">
									<?php 
									$webUrl = $imgArr["url"]; 
									$tnUrl = $imgArr["thumbnailurl"];
									if($GLOBALS['imageDomain']){
										if(substr($imgArr["url"],0,1) == "/") $webUrl = $GLOBALS["imageDomain"].$imgArr["url"]; 
										if(substr($imgArr["thumbnailurl"],0,1) == "/") $tnUrl = $GLOBALS["imageDomain"].$imgArr["thumbnailurl"];
									}
									if(!$tnUrl) $tnUrl = $webUrl;
									?>
									<a href="../../imagelib/imgdetails.php?imgid=<?php echo $imgArr['imgid']; ?>">
										<img src="<?php echo $tnUrl;?>" style="width:200px;"/>
									</a>
									<?php 
									if($imgArr["originalurl"]){
										$origUrl = (array_key_exists("imageDomain",$GLOBALS)&&substr($imgArr["originalurl"],0,1)=="/"?$GLOBALS["imageDomain"]:"").$imgArr["originalurl"];
										?>
										<br /><a href="<?php echo $origUrl;?>">Open Large Image</a>
										<?php 
									}
									?>
								</div>
							</td>
							<td valign="middle" style="width:90%">
								<?php
								if($imgArr['occid']){
									?>
									<div style="float:right;margin-right:10px;" title="Must have editing privileges for this collection managing image">
										<a href="../../collections/editor/occurrenceeditor.php?occid=<?php echo $imgArr['occid']; ?>&tabtarget=2" target="_blank">
											<img src="../../images/edit.png" style="border:0px;"/>
										</a>
									</div>
									<?php
								}
								else{
									?>
									<div style='float:right;margin-right:10px;'>
										<a href="../../imagelib/imgdetails.php?imgid=<?php echo $imgArr["imgid"];?>&emode=1" target="_blank">
											<img src="../../images/edit.png" style="border:0px;" />
										</a>
									</div>
									<?php 
								} 
								?>
								<div style='margin:60px 0px 10px 10px;clear:both;'>
									<?php if($imgArr["tid"] != $tid){ ?>
									<div>
										<b>Image linked from:</b> 
										<a href="tpeditor.php?tid=<?php echo $imgArr["tid"];?>" target=""><?php echo $imgArr["sciname"];?></a>
									</div>
									<?php 
									}
									if($imgArr["caption"]){ ?>
									<div>
										<b>Caption:</b> 
										<?php echo $imgArr["caption"];?>
									</div>
									<?php 
									}
									?>
									<div>
										<b>Photographer:</b> 
										<?php echo $imgArr["photographerdisplay"];?>
									</div>
									<?php 
									if($imgArr["owner"]){
									?>
									<div>
										<b>Manager:</b> 
										<?php echo $imgArr["owner"];?>
									</div>
									<?php
									} 
									if($imgArr["sourceurl"]){
									?>
									<div>
										<b>Source URL:</b> 
										<?php echo $imgArr["sourceurl"];?>
									</div>
									<?php
									} 
									if($imgArr["copyright"]){
									?>
									<div>
										<b>Copyright:</b> 
										<?php echo $imgArr["copyright"];?>
									</div>
									<?php
									} 
									if($imgArr["locality"]){
									?>
									<div>
										<b>Locality:</b> 
										<?php echo $imgArr["locality"];?>
									</div>
									<?php
									} 
									if($imgArr["occid"]){
									?>
									<div>
										<b>Occurrence Record #:</b> 
										<a href="<?php echo $clientRoot;?>/collections/individual/index.php?occid=<?php echo $imgArr["occid"]; ?>">
											<?php echo $imgArr["occid"];?>
										</a>
									</div>
									<?php
									}
									if($imgArr["notes"]){
									?>
									<div>
										<b>Notes:</b> 
										<?php echo $imgArr["notes"];?>
									</div>
									<?php
									} 
									?>
									<div>
										<b>Sort sequence:</b> 
										<?php echo $imgArr["sortsequence"];?>
									</div>
								</div>
							
							</td></tr>
							<tr><td colspan='2'>
								<div style='margin:10px 0px 0px 0px;clear:both;'>
									<hr />
								</div>
							</td></tr>
							<?php 
						}
						?>
					</table>
				</div>
				<?php
			} 
			else{
				echo '<h2>No images available.</h2>';
			}
		}
	}
	?>
</div>
