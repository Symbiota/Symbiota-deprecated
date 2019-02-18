<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TPDescEditorManager.php');
include_once($SERVER_ROOT.'/content/lang/taxa/admin/tpdesceditor.'.$LANG_TAG.'.php');

header("Content-Type: text/html; charset=".$charset);

$tid = array_key_exists("tid",$_REQUEST)?$_REQUEST["tid"]:0;
$lang = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:'';
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";

$descEditor = new TPDescEditorManager();
if($tid) $descEditor->setTid($tid);
if($lang) $descEditor->setLanguage($lang);

$statusStr = '';
$editable = false;
if($isAdmin || array_key_exists("TaxonProfile",$userRights)){
	$editable = true;
}

if($editable){
	?>
	<script type="text/javascript">
		tinyMCE.init({
			mode : "textareas",
			theme_advanced_buttons1 : "bold,italic,underline,charmap,hr,outdent,indent,link,unlink,code",
			theme_advanced_buttons2 : "",
			theme_advanced_buttons3 : ""
		});
	</script>
	<div style="float:right;" onclick="toggle('adddescrblock');" title="Add a New Description">
		<img style='border:0px;width:15px;' src='../../images/add.png'/>
	</div>
	<div id='adddescrblock' style='display:none;'>
		<form name='adddescrblockform' action="tpeditor.php" method="get">
			<fieldset style='width:90%;margin:10px;padding:10px;'>
    			<legend><b><?php echo $LANG['NEW_DESCRIPTION'];?></b></legend>
				<div style=''>
					<?php echo $LANG['LANGUAJE'];?> <input id="language" name="language" type="text" value="<?php echo $defaultLang; ?>" />
				</div>
				<div style=''>
					<?php echo $LANG['CAPTION'];?> <input id='caption' name='caption' style='width:300px;' type='text' />
				</div>
				<div style=''>
					<?php echo $LANG['SOURCE'];?> <input id='source' name='source' style='width:450px;' type='text' />
				</div>
				<div style=''>
					<?php echo $LANG['SOURCE_URL'];?> <input id='sourceurl' name='sourceurl' style='width:450px;' type='text' />
				</div>
				<div style=''>
					<?php echo $LANG['NOTES'];?> <input id='notes' name='notes' style='width:450px;' type='text' />
				</div>
				<div style="float:right;">
					<input name='action' style='margin-top:5px;' type='submit' value='Add Description Block' />
					<input type='hidden' name='tid' value='<?php echo $descEditor->getTid();?>' />
					<input type="hidden" name="tabindex" value="4" />
				</div>
				<div style=''>
					<?php echo $LANG['SORT_ORDER'];?> <input name='displaylevel' style='width:40px;' type='text' />
				</div>
			</fieldset>
		</form>
	</div>
	<?php 
	if($descList = $descEditor->getDescriptions(true)){
		foreach($descList as $tdbid => $dArr){
    		?>
    		<fieldset style='width:90%;margin:10px 5px 5px 5px;padding:10px;'>
				<legend><b><?php echo ($dArr["caption"]?$dArr["caption"]:"Description ".$dArr["displaylevel"]); ?></b></legend>
				<div style="float:right;" onclick="toggle('dblock-<?php echo $tdbid;?>');" title="Edit Description Block">
					<img style='border:0px;width:12px;' src='../../images/edit.png'/>
				</div>
				<?php 
				if($descEditor->getTid() != $dArr['tid']){
					?>
					<div style="margin:4px 0px;">
						<b><?php echo $LANG['LINKED_TO'];?></b> <?php echo $dArr['sciname']; ?> 
						(<a href="tpeditor.php?action=remap&tdbid=<?php echo $tdbid.'&tid='.$descEditor->getTid(); ?>"><?php echo $LANG['RELINK'];?></a>)
					</div>
					<?php 
				}
				?>
				<div><b><?php echo $LANG['CAPTION_1'];?></b> <?php echo $dArr["caption"]; ?></div>
				<div><b><?php echo $LANG['SOURCE_1'];?></b> <?php echo $dArr["source"]; ?></div>
				<div><b><?php echo $LANG['SOURCE_URL_3'];?></b> <a href='<?php echo $dArr["sourceurl"]; ?>'><?php echo $dArr["sourceurl"]; ?></a></div>
				<div><b><?php echo $LANG['NOTES'];?></b> <?php echo $dArr["notes"]; ?></div>
				<div id="dblock-<?php echo $tdbid;?>" style="display:none;margin-top:10px;">
					<fieldset style="padding:10px;">
						<legend><b><?php echo $LANG['DESCR_BLOCK'];?></b></legend>
						<form id='updatedescrblock' name='updatedescrblock' action="tpeditor.php" method="post">
							<div>
								<?php echo $LANG['LANGUAJE_1'];?>
								<input name='language' type='text' value='<?php echo $dArr['language']; ?>' />
							</div>
							<div>
								<?php echo $LANG['CAPTION_3'];?> 
								<input id='caption' name='caption' style='width:450px;' type='text' value='<?php echo $dArr["caption"];?>' />
							</div>
							<div>
								<?php echo $LANG['SOURCE_3'];?> 
								<input id='source' name='source' style='width:450px;' type='text' value='<?php echo $dArr["source"];?>' />
							</div>
							<div>
								<?php echo $LANG['SOURCE_URL_3'];?> 
								<input id='sourceurl' name='sourceurl' style='width:500px;' type='text' value='<?php echo $dArr["sourceurl"];?>' />
							</div>
							<div>
								<?php echo $LANG['NOTES_4'];?> 
								<input name='notes' style='width:450px;' type='text' value='<?php echo $dArr["notes"];?>' />
							</div>
							<div>
								<?php echo $LANG['DIPLAY_LEVEL'];?> 
								<input name='displaylevel' style='width:40px;' type='text' value='<?php echo $dArr['displaylevel'];?>' />
							</div>
							<div style="margin:10px;">
								<input type='hidden' name='tdbid' value='<?php echo $tdbid;?>' />
								<input type='hidden' name='tid' value='<?php echo $descEditor->getTid();?>' />
								<input type="hidden" name="tabindex" value="4" />
								<input type='submit' name='action' value='Edit Description Block' /> 
							</div> 
						</form>
						<hr/>
						<div style='margin:10px;border:2px solid red;padding:2px;'>
							<form name='delstmt' action='tpeditor.php' method='post' onsubmit="return window.confirm('Are you sure you want to delete this Description?');">
								<input type='hidden' name='tdbid' value='<?php echo $tdbid;?>' />
								<input type='hidden' name='tid' value='<?php echo $descEditor->getTid();?>' />
								<input type="hidden" name="tabindex" value="4" />
								<input type='hidden' name='action' value='Delete Description Block'>
								<input name='submitaction' value='Delete Description Block' style='margin:10px 0px 0px 20px;height:12px;' type='image' src='../../images/del.png'/> 
								<?php echo $LANG['DELETE_DESCRIP'];?> 
							</form>
						</div>
					</fieldset>
				</div>
    			<div style="margin-top:10px;">
					<fieldset style="padding:10px;">
						<legend><b><?php echo $LANG['STATEMENTS'];?></b></legend>
						<div onclick="toggle('addstmt-<?php echo $tdbid;?>');" style="float:right;" title="Add a New Statement">
							<img style='border:0px;width:15px;' src='../../images/add.png'/>
						</div>
						<div id='addstmt-<?php echo $tdbid;?>' style='display:<?php echo ($action == 'Add Description Block'?'block':'none'); ?>'>
							<form name='adddescrstmtform' action="tpeditor.php" method="post">
								<fieldset style='margin:5px 0px 0px 15px;'>
					    			<legend><b><?php echo $LANG['NEW_DESCRIP'];?></b></legend>
									<div style='margin:3px;'>
										<?php echo $LANG['HEADING'];?> <input name='heading' style='margin-top:5px;' type='text' />&nbsp;&nbsp;&nbsp;&nbsp;
										<input name='displayheader' type='checkbox' value='1' CHECKED /> <?php echo $LANG['DISPLAY_HEADING'];?>
									</div>
									<div style='margin:3px;'>
										<textarea name='statement' style="width:99%;height:200px;"></textarea>
									</div>
									<div style='margin:3px;'>
										<?php echo $LANG['SORT_SEQUENCE'];?> 
										<input name='sortsequence' style='margin-top:5px;width:40px;' type='text' value='' />
									</div>
									<div style="margin:10px;">
										<input type='hidden' name='tid' value='<?php echo $descEditor->getTid();?>' />
										<input type='hidden' name='tdbid' value='<?php echo $tdbid;?>' />
										<input type="hidden" name="tabindex" value="4" />
										<input name='action' type='submit' value='Add Statement' />
									</div>
								</fieldset>
							</form>
						</div>
						<?php
						if(array_key_exists("stmts",$dArr)){
							$sArr = $dArr["stmts"];
							foreach($sArr as $tdsid => $stmtArr){
								?>
								<div style="margin-top:3px;clear:both;">
									<b><?php echo $stmtArr["heading"];?></b>: 
									<?php echo $stmtArr["statement"];?>
									<span onclick="toggle('edstmt-<?php echo $tdsid;?>');" title="Edit Statement"><img style='border:0px;width:12px;' src='../../images/edit.png'/></span>
								</div>
								<div class="edstmt-<?php echo $tdsid;?>" style="clear:both;display:none;">
									<div style='margin:5px 0px 5px 20px;border:2px solid cyan;padding:5px;'>
										<form id='updatedescr' name='updatedescr' action="tpeditor.php" method="post">
											<div>
												<b><?php echo $LANG['HEADING_2'];?> </b> <input name='heading' style='margin:3px;' type='text' value='<?php echo $stmtArr["heading"];?>' /> 
												<input name='displayheader' type='checkbox' value='1' <?php echo ($stmtArr["displayheader"]?"CHECKED":"");?> /> <?php echo $LANG['DIS_HEAD'];?>
											</div>
											<div>
												<textarea name='statement'  style="width:99%;height:200px;margin:3px;"><?php echo $stmtArr["statement"];?></textarea>
											</div>
											<div>
												<b><?php echo $LANG['SORT_SEQUENCE_4'];?></b> 
												<input name='sortsequence' style='width:40px;' type='text' value='<?php echo $stmtArr["sortsequence"];?>' />
											</div>
											<div style="margin:10px;">
												<input name='action' type='submit' value='Edit Statement' />
												<input type='hidden' name='tdsid' value='<?php echo $tdsid;?>'>
												<input type='hidden' name='tid' value='<?php echo $descEditor->getTid();?>' />
												<input type="hidden" name="tabindex" value="4" />
											</div>
										</form>
									</div>
									<div style='margin:5px 0px 5px 20px;border:2px solid red;padding:2px;'>
										<form name='delstmt' action='tpeditor.php' method='post' onsubmit="return window.confirm('Are you sure you want to delete this Description?');">
											<input type='hidden' name='tdsid' value='<?php echo $tdsid;?>' />
											<input type='hidden' name='tid' value='<?php echo $descEditor->getTid();?>' />
											<input type="hidden" name="tabindex" value="4" />
											<input type='hidden' name='action' value='Delete Statement'>
											<input name='submitaction' value='Delete Statement' style='margin:10px 0px 0px 20px;height:12px;' type='image' src='../../images/del.png'/> 
											<?php echo $LANG['DELETE_STATEMENT'];?> 
										</form>
									</div>
								</div>
								<?php
							}
						}
						?>
					</fieldset>
				</div>
			</fieldset>
			<?php 
		}
	}
	else{
		echo '<h5>No descriptions available.</h5>';
	}
}
?>