<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistAdmin.php');
include_once($SERVER_ROOT.'/content/lang/checklists/checklistadmin.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$charset);

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";

$clManager = new ChecklistAdmin();
$clManager->setClid($clid);

$isEditor = 0;

$clArray = $clManager->getMetaData();
$defaultArr = array();
if(isset($clArray["defaultSettings"]) && $clArray["defaultSettings"]){
	$defaultArr = json_decode($clArray["defaultSettings"], true);
}
?>
<script type="text/javascript">
	function validateChecklistForm(f){
		if(f.name.value == ""){
			alert("Checklist name field must have a value");
			return false;
		}
		if(f.latcentroid.value != ""){
			if(f.longcentroid.value == ""){
				alert("If latitude has a value, longitude must also have a value");
				return false;
			}
			if(!isNumeric(f.latcentroid.value)){
				alert("Latitude must be strictly numeric (decimal format: e.g. 34.2343)");
				return false;
			}
			if(Math.abs(f.latcentroid.value) > 90){
				alert("Latitude values can not be greater than 90 or less than -90.");
				return false;
			} 
		} 
		if(f.longcentroid.value != ""){
			if(f.latcentroid.value == ""){
				alert("If longitude has a value, latitude must also have a value");
				return false;
			}
			if(!isNumeric(f.longcentroid.value)){
				alert("Longitude must be strictly numeric (decimal format: e.g. -112.2343)");
				return false;
			}
			if(Math.abs(f.longcentroid.value) > 180){
				alert("Longitude values can not be greater than 180 or less than -180.");
				return false;
			}
		} 
		if(!isNumeric(f.pointradiusmeters.value)){
			alert("Point radius must be a numeric value only");
			return false;
		}
		if(f.type){ 
			if(f.type.value == "rarespp" && f.locality.value == ""){
				alert("Rare species checklists must have a state value entered into the locality field");
				return false;
			}
		}
		return true;
	}

	function openMappingAid() {
		mapWindow=open("../tools/mappointaid.php?formname=editclmatadata&latname=latcentroid&longname=longcentroid","mapaid","resizable=0,width=800,height=700,left=20,top=20");
	    if(mapWindow.opener == null) mapWindow.opener = self;
	}

	function openMappingPolyAid() {
		mapWindow=open("../tools/mappolyaid.php?formname=editclmatadata&latname=latcentroid&longname=longcentroid","mapaid","resizable=0,width=800,height=700,left=20,top=20");
	    if(mapWindow.opener == null) mapWindow.opener = self;
	}

</script>
<?php
if(!$clid){
	?>
	<div style="float:right;">
		<a href="#" onclick="toggle('checklistDiv')" title="Create a New Checklist"><img src="../images/add.png" /></a>
	</div>
	<?php
}
?>
<div id="checklistDiv" style="display:<?php echo ($clid?'block':'none'); ?>;">
	<form id="checklisteditform" action="<?php echo $CLIENT_ROOT; ?>/checklists/checklistadmin.php" method="post" name="editclmatadata" onsubmit="return validateChecklistForm(this)">
		<fieldset style="margin:15px;padding:10px;">
			<legend><b><?php echo ($clid?$LANG['EDITCHECKDET']:$LANG['CREATECHECKDET']); ?></b></legend>
			<div>
				<b><?php echo $LANG['CHECKNAME'];?></b><br/>
				<input type="text" name="name" style="width:95%" value="<?php echo $clManager->getClName();?>" />
			</div>
			<div>
				<b><?php echo $LANG['AUTHORS'];?></b><br/>
				<input type="text" name="authors" style="width:95%" value="<?php echo ($clArray?$clArray["authors"]:''); ?>" />
			</div>
			<?php
			if(isset($GLOBALS['USER_RIGHTS']['RareSppAdmin']) || $IS_ADMIN){
				?>
				<div>
					<b><?php echo $LANG['CHECKTYPE'];?></b><br/>
					<select name="type">
						<option value="static"><?php echo $LANG['GENCHECK'];?></option>
						<option value="rarespp" <?php echo ($clArray && $clArray["type"]=='rarespp'?'SELECTED':'') ?>><?php echo $LANG['RARETHREAT'];?></option>
					</select>
				</div>
			<?php
			}
			?>
			<div>
				<b><?php echo $LANG['LOC'];?></b><br/>
				<input type="text" name="locality" style="width:95%" value="<?php echo ($clArray?$clArray["locality"]:''); ?>" />
			</div>
			<div>
				<b><?php echo $LANG['CITATION'];?></b><br/>
				<input type="text" name="publication" style="width:95%" value="<?php echo ($clArray?$clArray["publication"]:''); ?>" />
			</div>
			<div>
				<b><?php echo $LANG['ABSTRACT'];?></b><br/>
				<textarea name="abstract" style="width:95%" rows="3"><?php echo ($clArray?$clArray["abstract"]:''); ?></textarea>
			</div>
			<div>
				<b><?php echo $LANG['NOTES'];?></b><br/>
				<input type="text" name="notes" style="width:95%" value="<?php echo ($clArray?$clArray["notes"]:''); ?>" />
			</div>
			<div>
				<b>More Inclusive Reference Checklist:</b><br/>
				<select name="parentclid">
					<option value="">None Selected</option>
					<option value="">----------------------------------</option>
					<?php 
					$refClArr = $clManager->getReferenceChecklists();
					foreach($refClArr as $id => $name){
						echo '<option value="'.$id.'" '.($clArray && $id==$clArray['parentclid']?'SELECTED':'').'>'.$name.'</option>';
					}
					?>
				</select>
			</div>
			<div style="width:100%;">
				<div style="float:left;">
					<b><?php echo $LANG['LATCENT'];?></b><br/>
					<input id="latdec" type="text" name="latcentroid" style="width:110px;" value="<?php echo ($clArray?$clArray["latcentroid"]:''); ?>" />
				</div>
				<div style="float:left;margin-left:15px;">
					<b><?php echo $LANG['LONGCENT'];?></b><br/>
					<input id="lngdec" type="text" name="longcentroid" style="width:110px;" value="<?php echo ($clArray?$clArray["longcentroid"]:''); ?>" />
				</div>
				<div style="float:left;margin:25px 3px;">
					<a href="#" onclick="openMappingAid();return false;"><img src="../images/world.png" style="width:12px;" /></a>
				</div>
				<div style="float:left;margin-left:15px;">
					<b><?php echo $LANG['POINTRAD'];?></b><br/>
					<input type="text" name="pointradiusmeters" style="width:110px;" value="<?php echo ($clArray?$clArray["pointradiusmeters"]:''); ?>" />
				</div>
				<div style="float:left;margin:8px 0px 0px 25px;">
					<fieldset style="width:175px;">
						<legend><b><?php echo $LANG['POLYFOOT'];?></b></legend>
						<?php
						if($clArray&&$clArray["footprintWKT"]){
							?>
							<div id="polyexistsbox" style="display:block;clear:both;">
								<b><?php echo $LANG['POLYFOOTSAVE'];?></b>
							</div>
						<?php
						}
						else{
							?>
							<div id="polycreatebox" style="display:block;clear:both;">
								<b><?php echo $LANG['CREATEPOLYFOOT'];?></b>
							</div>
						<?php
						}
						?>
						<div id="polysavebox" style="display:none;clear:both;">
							<b><?php echo $LANG['POLYFOOTRDYSAVE'];?></b>
						</div>
						<div style="float:right;margin:8px 0px 0px 10px;cursor:pointer;" onclick="openMappingPolyAid();">
							<img src="../images/world.png" style="width:12px;" />
						</div>
					</fieldset>
				</div>
			</div>
			<div style="clear:both;margin-top:5px;">
				<fieldset style="width:300px;">
					<legend><b><?php echo $LANG['DEFAULTDISPLAY'];?></b></legend>
					<div>
						<!-- Display Details: 0 = false, 1 = true  -->
						<input name='ddetails' id='ddetails' type='checkbox' value='1' <?php echo (($defaultArr&&$defaultArr["ddetails"])?"checked":""); ?> /> 
						<?php echo $LANG['SHOWDETAILS'];?>
					</div>
					<div>
						<?php
						//Display Common Names: 0 = false, 1 = true
						if($displayCommonNames) echo "<input id='dcommon' name='dcommon' type='checkbox' value='1' ".(($defaultArr&&$defaultArr["dcommon"])?"checked":"")." /> ".$LANG['COMMON'];
						?>
					</div>
					<div>
						<!-- Display as Images: 0 = false, 1 = true  -->
						<input name='dimages' id='dimages' type='checkbox' value='1' <?php echo (($defaultArr&&$defaultArr["dimages"])?"checked":""); ?> onclick="showImagesDefaultChecked(this.form);" /> 
						<?php echo $LANG['DISPLAYIMG'];?>
					</div>
					<div>
						<!-- Display as Vouchers: 0 = false, 1 = true  -->
						<input name='dvouchers' id='dvouchers' type='checkbox' value='1' <?php echo (($defaultArr&&$defaultArr["dimages"])?"disabled":(($defaultArr&&$defaultArr["dvouchers"])?"checked":"")); ?>/> 
						<?php echo $LANG['NOTESVOUC'];?>
					</div>
					<div>
						<!-- Display Taxon Authors: 0 = false, 1 = true  -->
						<input name='dauthors' id='dauthors' type='checkbox' value='1' <?php echo (($defaultArr&&$defaultArr["dimages"])?"disabled":(($defaultArr&&$defaultArr["dauthors"])?"checked":"")); ?>/> 
						<?php echo $LANG['TAXONAUTHOR'];?>
					</div>
					<div>
						<!-- Display Taxa Alphabetically: 0 = false, 1 = true  -->
						<input name='dalpha' id='dalpha' type='checkbox' value='1' <?php echo ($defaultArr&&$defaultArr["dalpha"]?"checked":""); ?> /> 
						<?php echo $LANG['TAXONABC'];?>
					</div>
					<div>
						<?php 
						// Activate Identification key: 0 = false, 1 = true 
						$activateKey = $KEY_MOD_IS_ACTIVE;
						if(array_key_exists('activatekey', $defaultArr)){
							$activateKey = $defaultArr["activatekey"];
						}
						?>
						<input name='activatekey' type='checkbox' value='1' <?php echo ($activateKey?"checked":""); ?> /> 
						<?php echo $LANG['ACTIVATEKEY'];?>
					</div>
				</fieldset>
			</div>
			<div style="clear:both;margin-top:15px;">
				<b>Access</b><br/>
				<select name="access">
					<option value="private"><?php echo $LANG['PRIVATE'];?></option>
					<option value="public" <?php echo ($clArray && $clArray["access"]=="public"?"selected":""); ?>><?php echo $LANG['PUBLIC'];?></option>
				</select>
			</div>
			<div style="clear:both;float:left;margin-top:15px;">
				<?php 
				if($clid){
					?>
					<input type='submit' name='submit' value='<?php echo $LANG['EDITCHECKLIST'];?>' />
					<input type="hidden" name="submitaction" value="SubmitEdit" />
					<?php
				}
				else{
					?>
					<input type='submit' name='submit' value='<?php echo $LANG['ADDCHECKLIST'];?>' />
					<input type="hidden" name="submitaction" value="SubmitAdd" />
					<?php
				}
				?>
			</div>
			<input type="hidden" id="footprintWKT" name="footprintWKT" value='<?php echo ($clArray?$clArray["footprintWKT"]:''); ?>' />
			<input type="hidden" name="tabindex" value="1" />
			<input type='hidden' name='clid' value='<?php echo $clid; ?>' />
			<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
		</fieldset>
	</form>
</div>

<div>
	<?php
	if(array_key_exists("userid",$_REQUEST)){
		$userId = $_REQUEST["userid"];
		echo '<div style="font-weight:bold;font:bold 14pt;">Checklists assigned to your account</div>';
		$listArr = $clManager->getManagementLists($userId);
		if(array_key_exists('cl',$listArr)){
			$clArr = $listArr['cl'];
			?>
			<ul>
			<?php 
			foreach($clArr as $kClid => $vName){
				?>
				<li>
					<a href="../checklists/checklist.php?cl=<?php echo $kClid; ?>&emode=0">
						<?php echo $vName; ?>
					</a>
					<a href="../checklists/checklistadmin.php?clid=<?php echo $kClid; ?>&emode=1">
						<img src="../images/edit.png" style="width:15px;border:0px;" title="Edit Checklist" />
					</a>
				</li>
				<?php 
			}
			?>
			</ul>
			<?php 
		}
		else{
			?>
			<div style="margin:10px;">
				<div>You have no personal checklists</div>
				<div style="margin-top:5px">
					<a href="#" onclick="toggle('checklistDiv')">Click here to create a new checklist</a>
				</div>
			</div>
			<?php 
		}
	
		echo '<div style="font-weight:bold;font:bold 14pt;margin-top:25px;">Inventory Project Administration</div>'."\n";
		if(array_key_exists('proj',$listArr)){
			$projArr = $listArr['proj'];
			?>
			<ul>
			<?php 
			foreach($projArr as $pid => $projName){
				?>
				<li>
					<a href="../projects/index.php?pid=<?php echo $pid; ?>&emode=0">
						<?php echo $projName; ?>
					</a>
					<a href="../projects/index.php?pid=<?php echo $pid; ?>&emode=1">
						<img src="../images/edit.png" style="width:15px;border:0px;" title="Edit Project" />
					</a>
				</li>
				<?php 
			}
			?>
			</ul>
			<?php 
		}
		else{
			echo '<div style="margin:10px;">There are no Projects for which you have administrative permissions</div>';
		}
	}
	?>
</div>