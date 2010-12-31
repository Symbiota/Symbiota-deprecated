<?php
/*
* Rebuilt on Sept 2010
* Author: E.E. Gilbert
*/

//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TPImageEditorManager.php');
 
$tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:0;
$category = array_key_exists("category",$_REQUEST)?$_REQUEST["category"]:""; 
$lang = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:"";
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";

$imageEditor = new TPImageEditorManager();
if($tid){
	$imageEditor->setTid($tid);
	$imageEditor->setLanguage($lang);
	 
	$editable = false;
	if($isAdmin || array_key_exists("TaxonProfile",$userRights)){
		$editable = true;
	}
	 
	$status = "";
	if($editable){
		if($action == "Submit Image Edits"){
			$status = $imageEditor->editImage();
		}
		elseif($action == "Transfer Image"){
			$imageEditor->changeTaxon($_REQUEST["imgid"],$tid,$_REQUEST["sourcetid"]);
		}
		elseif($action == "Submit Image Sort Edits"){
			$imgSortArr = Array();
			foreach($_REQUEST as $sortKey => $sortValue){
				if($sortValue && substr($sortKey,0,6) == "imgid-"){
					$imgSortArr[substr($sortKey,6)]  = $sortValue;
				}
			}
			$status = $imageEditor->editImageSort($imgSortArr);
		} 
		elseif($action == "Upload Image"){
			$status = $imageEditor->loadImageData();
		}
		elseif($action == "Delete Image"){
			$imgDel = $_REQUEST["imgdel"];
			$removeImg = (array_key_exists("removeimg",$_REQUEST)?$_REQUEST["removeimg"]:0);
			$status = $imageEditor->deleteImage($imgDel, $removeImg);
		}
	}
}
else{
	header('Location: tpeditor.php?category='.$category.'&lang='.$lang.'&action='.$action);
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<title><?php echo $defaultTitle." Taxon Editor: ".$imageEditor->getSciName(); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>" />
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
	<link rel="stylesheet" href="../../css/speciesprofile.css" type="text/css"/>
    <link rel="stylesheet" href="../../css/jqac.css" type="text/css" />
	<script type="text/javascript" src="../../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../../js/jquery.autocomplete-1.4.2.js"></script>
	<script type="text/javascript" src="../../js/taxa.tpimageeditor.js"></script>
</head>
<body>
<?php
$displayLeftMenu = (isset($taxa_admin_tpimageeditorMenu)?$taxa_admin_tpimageeditorMenu:false);
include($serverRoot.'/header.php');
if(isset($taxa_admin_tpimageeditorCrumbs)){
	echo "<div class='navpath'>";
	echo "<a href='../index.php'>Home</a> &gt; ";
	echo $taxa_admin_tpimageeditorCrumbs;
	echo " <b>Taxon Profile Image Editor</b>";
	echo "</div>";
}

if($editable && $tid){
	?>
	<table style="width:100%;">
		<tr><td>
			<div style='float:right;margin:15px;'>
				<a href="tpeditor.php?tid=<?php echo $imageEditor->getTid(); ?>">
					Main Menu
				</a>
			</div>
			<?php 
		 	if($imageEditor->getSubmittedTid()){
		 		?>
		 		<div style='font-size:16px;margin-top:5px;margin-left:10px;font-weight:bold;'>
		 			Redirected from: <i><?php echo $imageEditor->getSubmittedSciName(); ?></i>
		 		</div>
		 		<?php  
		 	}
		 	?>
			<div style='font-size:16px;margin-top:15px;margin-left:10px;'>
				<a href="../index.php?taxon=<?php echo $imageEditor->getTid(); ?>" style="color:#990000;text-decoration:none;">
					<b><i><?php echo $imageEditor->getSciName(); ?></i></b>
				</a> 
				<?php echo $imageEditor->getAuthor(); ?>
				<?php 
				if($imageEditor->getRankId() > 140){
					?>
					<a href='tpeditor.php?tid=<?php echo $imageEditor->getParentTid(); ?>'>
						<img border='0' height='10px' src='../../images/toparent.jpg' title='Go to Parent' />
					</a>
					<?php 
				}
			?>
			</div>
			<div id='family' style='margin-left:20px;margin-top:0.25em;'>
				<b>Family:</b> <?php echo $imageEditor->getFamily();?>
			</div>
			<?php 
			if($status){
				echo "<h3 style='color:red;'>Error: $status<h3>";
			}

	if($category == "imagequicksort"){
		$images = $imageEditor->getImages();
		echo "<div style='clear:both;'><form action='".$_SERVER["PHP_SELF"]."' method='post' target='_self'>\n";
		echo "<table border='0' cellspacing='0'>";
		echo "<tr>";
		$imgCnt = 0;
		foreach($images as $imgArr){
			$webUrl = (array_key_exists("imageDomain",$GLOBALS)&&substr($imgArr["url"],0,1)=="/"?$GLOBALS["imageDomain"]:"").$imgArr["url"]; 
			$tnUrl = (array_key_exists("imageDomain",$GLOBALS)&&substr($imgArr["thumbnailurl"],0,1)=="/"?$GLOBALS["imageDomain"]:"").$imgArr["thumbnailurl"];
			?>
			<td align='center' valign='bottom'>
				<div style='margin:20px 0px 0px 0px;'>
					<a href="<?php echo $webUrl; ?>">
						<img width="150" src="<?php echo $tnUrl;?>" />
					</a>
				</div>
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
		echo "</tr>\n";
		echo "</table>\n";
		echo "<input name='tid' type='hidden' value='".$imageEditor->getTid()."'>\n";
		echo "<input name='category' type='hidden' value='".$category."'>\n";
		if($imgCnt%5 != 0) echo "<div style='margin-top:2px;'><input type='submit' name='action' id='imgsortsubmit' value='Submit Image Sort Edits'/></div>\n";
		echo "</form></div>\n";
	}
	elseif($category == "imageadd"){
		?>
		<form enctype='multipart/form-data' action='tpimageeditor.php' id='imageaddform' method='post' target='_self' onsubmit='return submitAddForm(this);'>
			<fieldset style='margin:15px;width:90%;'>
		    	<legend>Add a New Image</legend>
				<div style='padding:10px;width:550px;border:1px solid yellow;background-color:FFFF99;'>
					<div class="targetdiv" style="display:block;">
						<div style="font-weight:bold;font-size:110%;margin-bottom:5px;">
							Select an image file located on your computer that you want to upload:
						</div>
				    	<!-- following line sets MAX_FILE_SIZE (must precede the file input field)  -->
						<input type='hidden' name='MAX_FILE_SIZE' value='2000000' />
						<div>
							<input name='userfile' type='file' size='70'/>
						</div>
						<div style="margin-left:10px;">Note: upload image size can not be greater than 1MB</div>
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
					<img style="border:0px;cursor:pointer;" src="../../images/add.png" onclick="toggle('photooveridediv');" title="Photographer Override"/>
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
					<span style="cursor:pointer;" onclick="toggle('adoptiondiv');" title="Additional Options">
						<img style="border:0px;" src="../../images/add.png" />
					</span>
				</div>
				<div id="adoptiondiv" style="border:1px dotted blue;margin:10px;padding:10px;display:none;">
					<div style="font-size:120%;font-weight:bold;margin-left:-5px;">Additional Options:</div>
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
						<input id="occidadd" name="occid" type="text" value="" READONLY/>
						<span style="cursor:pointer;color:blue;"  onclick="openOccurrenceSearch('occidadd')">Link to Occurrence Record</span>
					</div>
					<?php if($imageEditor->getRankId() > 220 && !$imageEditor->getSubmittedTid()){ ?>
					<div style='padding:10px;margin:5px;width:475px;border:1px solid yellow;background-color:FFFF99;'>
						<input type='checkbox' name='addtoparent' value='1' /> 
						Add Image to Species Rank 
						<div style='margin-left:10px;'>
							* If scientific name is a subspecies or variety, click this option if you also want image to be displays at the species level
						</div>
					</div>
					<?php }elseif($cArr = $imageEditor->getChildrenArr()){ ?>
					<div style='padding:10px;margin:5px;width:475px;border:1px solid yellow;background-color:FFFF99;'>
						Add Image to a Child Taxon 
						<select name='addtotid'>
							<option value='0'>Child Taxon</option>
							<option value='0'>-----------------------</option>
							<?php 
								foreach($cArr as $t => $sn){
									?><option value="<?php echo $t;?>"><?php echo $sn;?></option><?php 
								}
							?>
						</select> 
					</div>
					<?php } ?>
				</div>
				<input name="tid" type="hidden" value="<?php echo $imageEditor->getTid();?>">
				<input name='category' type='hidden' value='images'>
				<div style='margin-top:2px;'>
					<input type='submit' name='action' id='imgaddsubmit' value='Upload Image'/>
				</div>
			</fieldset>
		</form>
		<?php 
	}
	else{
		//catagory == images or is null
		$images = $imageEditor->getImages();
		foreach($images as $imgArr){
			?>
			<table>
				<tr><td>
					<div style="margin:20px;float:left;text-align:center;">
						<?php 
						$webUrl = (array_key_exists("imageDomain",$GLOBALS)&&substr($imgArr["url"],0,1)=="/"?$GLOBALS["imageDomain"]:"").$imgArr["url"]; 
						$tnUrl = (array_key_exists("imageDomain",$GLOBALS)&&substr($imgArr["thumbnailurl"],0,1)=="/"?$GLOBALS["imageDomain"]:"").$imgArr["thumbnailurl"];
						if(!$tnUrl) $tnUrl = $webUrl;
						?>
						<a href="<?php echo $webUrl;?>">
							<img src="<?php echo $tnUrl;?>"/>
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
				<td valign="middle">
					<div style='float:right;margin-right:10px;cursor:pointer;'>
						<img src="../../images/edit.png" onclick="toggle('image<?php echo $imgArr["imgid"];?>');">
					</div>
					<div style='margin:60px 0px 10px 10px;clear:both;'>
						<?php if($imgArr["caption"]){ ?>
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
							<a href="<?php echo $clientRoot;?>/collections/individual/individual.php?occid=<?php echo $imgArr["occid"]; ?>">
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
					<div class='image<?php  echo $imgArr["imgid"];?>' style='display:none;'>
						<form action='tpimageeditor.php' method='post' target='_self' onsubmit='return submitEditForm(this);'>
							<fieldset style='margin:5px 0px 5px 5px;'>
						    	<legend>Edit Image Details</legend>
						    	<div style='margin-top:2px;'>
						    		<b>Caption:</b>
									<input name='caption' type='text' value='<?php echo $imgArr["caption"];?>' size='25' maxlength='100'>
								</div>
								<div style='margin-top:2px;'>
									<b>Photographer User ID:</b> 
									<select name='photographeruid' name='photographeruid'>
										<option value="">Select Photographer</option>
										<option value="">---------------------------------------</option>
										<?php $imageEditor->echoPhotographerSelect($imgArr["photographeruid"]); ?>
									</select>
									* Users registered within system
								</div>
								<div style='margin-top:2px;'>
									<b>Photographer (override):</b> 
									<input name='photographer' type='text' value='<?php echo $imgArr["photographer"];?>' size='37' maxlength='100'>
									* Will override above selection
								</div>
								<div style='margin-top:2px;'>
									<b>Manager:</b> 
									<input name="owner" type="text" value="<?php echo $imgArr["owner"];?>" size="35" maxlength="100">
								</div>
								<div style='margin-top:2px;'>
									<b>Source URL:</b> 
									<input name="sourceurl" type="text" value="<?php echo $imgArr["sourceurl"];?>" size="70" maxlength="250">
								</div>
								<div style='margin-top:2px;'>
									<b>Copyright:</b> 
									<input name="copyright" type="text" value="<?php echo $imgArr["copyright"];?>" size="70" maxlength="250">
								</div>
								<div style='margin-top:2px;'>
									<b>Locality:</b> 
									<input name="locality" type="text" value="<?php echo $imgArr["locality"];?>" size="70" maxlength="250">
								</div>
								<div style='margin-top:2px;'>
									<b>Occurrence Record #:</b> 
									<input id="occid<?php  echo $imgArr["imgid"];?>" name="occid" type="text" value="<?php  echo $imgArr["occid"];?>" />
									<span style="cursor:pointer;color:blue;"  onclick="openOccurrenceSearch('occid<?php  echo $imgArr["imgid"];?>')">Link to Occurrence Record</span>
								</div>
								<div style='margin-top:2px;'>
									<b>Notes:</b> 
									<input name='notes' type='text' value='<?php echo $imgArr["notes"];?>' size='70' maxlength='250' />
								</div>
								<div style='margin-top:2px;'>
									<b>Sort sequence:</b> 
									<input name='sortsequence' type='text' value='<?php echo $imgArr["sortsequence"];?>' size='5' maxlength='5' />
								</div>
								<div style='margin-top:2px;'>
									<b>Web Image:</b> 
									<input name='url' type='text' value='<?php echo $imgArr["url"];?>' size='70' maxlength='150' />
									<?php if(stripos($imgArr["url"],$imageRootUrl) === 0){ ?>
									<div style="margin-left:70px;">
										<input type="checkbox" name="renameweburl" value="1" />
										Rename web image file on server to match above edit (web server editing privileges requiered)
									</div>
									<input name='oldurl' type='hidden' value='<?php echo $imgArr["url"];?>' />
									<?php } ?>
								</div>
								<div style='margin-top:2px;'>
									<b>Thumbnail:</b> 
									<input name='thumbnailurl' type='text' value='<?php echo $imgArr["thumbnailurl"];?>' size='70' maxlength='150'>
									<?php if(stripos($imgArr["thumbnailurl"],$imageRootUrl) === 0){ ?>
									<div style="margin-left:70px;">
										<input type="checkbox" name="renametnurl" value="1" />
										Rename thumbnail image file on server to match above edit (web server editing privileges requiered)
									</div>
									<input name='oldthumbnailurl' type='hidden' value='<?php echo $imgArr["thumbnailurl"];?>' />
									<?php } ?>
								</div>
								<div style='margin-top:2px;'>
									<b>Large Image:</b> 
									<input name='originalurl' type='text' value='<?php echo $imgArr["originalurl"];?>' size='70' maxlength='150'>
									<?php if(stripos($imgArr["originalurl"],$imageRootUrl) === 0){ ?>
									<div style="margin-left:80px;">
										<input type="checkbox" name="renameorigurl" value="1" />
										Rename large image file on server to match above edit (web server editing privileges requiered)
									</div>
									<input name='oldoriginalurl' type='hidden' value='<?php echo $imgArr["originalurl"];?>' />
									<?php } ?>
								</div>
								<?php if($imageEditor->getRankId() > 220 && !$imageEditor->getSubmittedTid() && !$imageEditor->imageExists($imgArr["url"],$imageEditor->getParentTid())){ ?>
								<div style='padding:10px;margin:5px;width:475px;border:1px solid yellow;background-color:FFFF99;'>
									<input type='checkbox' name='addtoparent' value='1' /> 
									Add Image to Species Rank 
									<div style='margin-left:10px;'>
										* If scientific name is a subspecies or variety, click this option if you also want image to be displays at the species level
									</div>
								</div>
								<?php }elseif($imageEditor->getRankId() == 220 && $cArr = $imageEditor->getChildrenArr($imgArr["url"])){ ?>
								<div style='padding:10px;margin:5px;width:475px;border:1px solid yellow;background-color:FFFF99;'>
									Add Image to a Child Taxon 
									<select name='addtotid'>
										<option value='0'>Child Taxon</option>
										<option value='0'>-----------------------</option>
										<?php 
											foreach($cArr as $t => $sn){
												?><option value="<?php echo $t;?>"><?php echo $sn;?></option><?php 
											}
										?>
									</select> 
								</div>
								<?php } ?>
				
								<input name="tid" type="hidden" value="<?php echo $imageEditor->getTid();?>" />
								<input name="category" type="hidden" value="<?php echo $category; ?>" />
								<input name="imgid" type="hidden" value="<?php echo $imgArr["imgid"]; ?>" />
								<div style='margin-top:2px;'>
									<input type='submit' name='action' id='editsubmit' value='Submit Image Edits' />
								</div>
							</fieldset>
						</form>
						<form id="changetaxonform-<?php echo $imgArr["imgid"]; ?>" action='tpimageeditor.php' method='post' target='_self' onsubmit='return submitChangeTaxonForm(this);'>
							<fieldset style='margin:5px 0px 5px 5px;'>
						    	<legend>Transfer Image to a Different Scientific Name</legend>
								<div style="font-weight:bold;">
									Transfer to Taxon: 
									<input type="text" id="targettaxon" name="targettaxon" size="40" onfocus="initChangeTaxonList(this,<?php echo $imgArr["imgid"]; ?>)" autocomplete="off" />
									<input type="hidden" id="targettid-<?php echo $imgArr["imgid"]; ?>" name="tid" value="" />
	
									<input name="sourcetid" type="hidden" value="<?php echo $imageEditor->getTid();?>" />
									<input name="imgid" type="hidden" value="<?php echo $imgArr["imgid"]; ?>" />
									<input name="category" type="hidden" value="<?php echo $category; ?>" />
									<input name="action" type="hidden" value="Transfer Image" />
									<input name="action2" type="submit" id="changetaxonsubmit" value="Transfer Image" />
								</div>
						    </fieldset>
						</form>
						
						<?php 
						if($symbUid == $imgArr["photographeruid"] || $isAdmin){
							?>
							<form action="tpimageeditor.php" method="post" target="_self" onsubmit="return window.confirm('Are you sure you want to delete this image? Note that the physical image will be deleted from the server if checkbox is selected.');">
								<fieldset style="margin:5px 0px 5px 5px;">
							    	<legend>Authorized to Remove this Image</legend>
									<input name="imgdel" type="hidden" value="<?php echo $imgArr["imgid"]; ?>" />
									<input name="tid" type="hidden" value="<?php echo $imageEditor->getTid(); ?>" />
									<input name="category" type="hidden" value="<?php echo $category; ?>" />
									<input name="removeimg" type="checkbox" value="1" CHECKED /> Remove image from server 
									<div style="margin-left:20px;">
										(Note: leaving unchecked removes image from database w/o removing from server)
									</div>
									<div style='margin-top:2px;'>
										<input type='submit' name='action' id='submit' value='Delete Image'/>
									</div>
						    	</fieldset>
						    </form>
					    	<?php 
						}
						?>
					</div>
				</td></tr>
				<tr><td colspan='2'>
					<div style='margin:10px 0px 0px 0px;clear:both;'>
						<hr />
					</div>
				</td></tr>
			</table>
			<?php 
		}
	}
}
else{
	?>
	<div style="margin:30px;">
		<h2>You must be logged in and authorized to taxon data.</h2>
		<h3>
			Click <a href="<?php echo $clientRoot; ?>/profile/index.php">here</a> to login
		</h3>
	</div>
	<?php 
}
?>
	</td></tr>
</table>
<?php  
include($serverRoot.'/footer.php');
 ?>
	
</body>
</html>

