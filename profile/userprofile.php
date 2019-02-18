<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
include_once($SERVER_ROOT.'/content/lang//profile/userprofile.'.$LANG_TAG.'.php');

header("Content-Type: text/html; charset=".$CHARSET);

$userId = $_REQUEST["userid"];

//Sanitation
if(!is_numeric($userId)) $userId = 0;

$pHandler = new ProfileManager();
$pHandler->setUid($userId);
$person = $pHandler->getPerson();
$tokenCount = $pHandler->getTokenCnt();
$isSelf = true;
if($userId != $SYMB_UID) $isSelf = false;
?>
<div style="padding:15px;">
	<div>
		<div>
			<b><u><?php echo $LANG['PROFILE_DETAILS'];?></u></b>
		</div>
		<div style="margin:20px;">
			<?php
			echo '<div>'.$person->getFirstName().' '.$person->getLastName().'</div>';
			if($person->getTitle()) echo '<div>'.$person->getTitle().'</div>';
			if($person->getInstitution()) echo '<div>'.$person->getInstitution().'</div>';
			$cityStateStr = trim($person->getCity().', '.$person->getState().' '.$person->getZip(),' ,');
			if($cityStateStr) echo '<div>'.$cityStateStr.'</div>';
			if($person->getCountry()) echo '<div>'.$person->getCountry().'</div>';
			if($person->getEmail()) echo '<div>'.$person->getEmail().'</div>';
			if($person->getUrl()) echo '<div><a href="'.$person->getUrl().'">'.$person->getUrl().'</a></div>';
			if($person->getBiography()) echo '<div style="margin:10px;">'.$person->getBiography().'</div>';
			echo '<div>'.$LANG['LOGIN_NAME'].''.($person->getUserName()?$person->getUserName():'not registered').'</div>';
			echo '<div>'.$LANG['USER_INFO'].' '.($person->getIsPublic()?'public':'Privado').'</div>';
			?>
			<div style="font-weight:bold;margin-top:10px;">
				<div><a href="#" onclick="toggleEditingTools('profileeditdiv');return false;"><?php echo $LANG['EDIT_PROFILE'];?></a></div>
				<div><a href="#" onclick="toggleEditingTools('pwdeditdiv');return false;"><?php echo $LANG['CHANGE_PASS'];?></a></div>
				<div><a href="#" onclick="toggleEditingTools('logineditdiv');return false;"><?php echo $LANG['CHANGE_LOGIN'];?></a></div>
                <div><a href="#" onclick="toggleEditingTools('managetokensdiv');return false;"><?php echo $LANG['MANAGE_ACCESS'];?></a></div>
			</div>
		</div>
	</div>
	<div id="profileeditdiv" style="display:none;margin:15px;">
		<form name="editprofileform" action="viewprofile.php" method="post" onsubmit="return verifyEditProfileForm(this);">
			<fieldset>
				<legend><b><?php echo $LANG['EDIT_USER_PROFILE'];?></b></legend>
				<table cellspacing='1' style="width:100%;">
				    <tr>
				        <td><b><?php echo $LANG['FIRST_MANE'];?>
</b></td>
				        <td>
							<div>
								<input id="firstname" name="firstname" size="40" value="<?php echo $person->getFirstName();?>">
							</div>
			            </td>
				    </tr>
				    <tr>
				        <td><b><?php echo $LANG['LAST_NAME'];?></b></td>
				        <td>
							<div>
								<input id="lastname" name="lastname" size="40" value="<?php echo $person->getLastName();?>">
							</div>
			            </td>
				    </tr>
				    <tr>
				        <td><b><?php echo $LANG['TITLE'];?></b></td>
				        <td>
							<div>
								<input name="title"  size="40" value="<?php echo $person->getTitle();?>">
							</div>
						</td>
				    </tr>
				    <tr>
				        <td><b><?php echo $LANG['INSTITUTION'];?></b></td>
				        <td>
							<div>
								<input name="institution"  size="40" value="<?php echo $person->getInstitution();?>">
							</div>
						</td>
				    </tr>
				    <tr>
				        <td><b><?php echo $LANG['CITY'];?></b></td>
				        <td>
							<div>
				            	<input id="city" name="city" size="40" value="<?php echo $person->getCity();?>">
							</div>
			            </td>
				    </tr>
				    <tr>
				        <td><b><?php echo $LANG['STATE'];?></b></td>
				        <td>
							<div>
					            <input id="state" name="state" size="40" value="<?php echo $person->getState();?>">
							</div>
			            </td>
				    </tr>
				    <tr>
				        <td><b><?php echo $LANG['ZIP_CODE'];?></b></td>
				        <td>
							<div>
					            <input name="zip" size="40" value="<?php echo $person->getZip();?>">
							</div>
			            </td>
				    </tr>
				    <tr>
				        <td><b><?php echo $LANG['COUNTRY'];?></b></td>
				        <td>
							<div>
								<input id="country" name="country" size="40" value="<?php echo $person->getCountry();?>">
							</div>
						</td>
				    </tr>
				    <tr>
				        <td><b><?php echo $LANG['EMAIL_ADDRESS'];?></b></td>
				        <td>
							<div>
					            <input id="email" name="email" size="40" value="<?php echo $person->getEmail();?>">
							</div>
			            </td>
				    </tr>
				    <tr>
				        <td><b><?php echo $LANG['URL'];?></b></td>
				        <td>
							<div>
								<input name="url"  size="40" value="<?php echo $person->getUrl();?>">
							</div>

						</td>
				    </tr>
				    <tr>
				        <td><b><?php echo $LANG['BIOGRAPHY'];?></b></td>
				        <td>
							<div>
								<textarea name="biography" rows="4" cols="40"><?php echo $person->getBiography();?></textarea>
							</div>
						</td>
				    </tr>
				    <tr>
				        <td colspan="2">
							<div>
								<input type="checkbox" name="ispublic" value="1" <?php if($person->getIsPublic()) echo "CHECKED"; ?> />

									<?php echo $LANG['MK_USER'];?>
			        		</div>
						</td>
				    </tr>
				    <tr>
						<td colspan="2">
							<div style="margin:10px;">
								<input type="hidden" name="userid" value="<?php echo $userId;?>" />
								<input type="submit" name="action" value="Grabar información" />
							</div>
						</td>
					</tr>
				</table>
			</fieldset>
		</form>
		<form name="delprofileform" action="viewprofile.php" method="post" onsubmit="return window.confirm('Are you sure you want to delete profile?');">
			<fieldset style='padding:15px;'>
		    	<legend><b><?php echo $LANG['DEL_PROFILE'];?></b></legend>
				<input type="hidden" name="userid" value="<?php echo $userId;?>" />
	    		<input type="submit" name="action" value="Borrar perfil" />
			</fieldset>
		</form>
	</div>
	<div id="pwdeditdiv" style="display:none;margin:15px;">
		<form name="changepwdform" action="viewprofile.php" method="post" onsubmit="return verifyPwdForm(this);">
			<fieldset style='padding:15px;width:500px;'>
		    	<legend><b><?php echo $LANG['CHANGE_PASS'];?></b></legend>
		    	<table>
					<?php
					if($isSelf){
						?>
			    		<tr>
			    			<td>
				            	<b><?php echo $LANG['CUR_PASS'];?></b>
				            </td>
				            <td>
				            	<input id="oldpwd" name="oldpwd" type="password"/>
			    			</td>
			    		</tr>
						<?php
					}
					?>
		    		<tr>
		    			<td>
			            	<b><?php echo $LANG['NEW_PASS'];?></b>
			            </td>
			            <td>
			            	<input id="newpwd" name="newpwd" type="password"/>
		    			</td>
		    		</tr>
		    		<tr>
		    			<td>
							<b><?php echo $LANG['NEW_PASS_AGAIN'];?></b>
			            </td>
			            <td>
							<input id="newpwd2" name="newpwd2" type="password"/>
			    		</td>
			    	</tr>
		    		<tr>
		    			<td colspan="2">
							<input type="hidden" name="userid" value="<?php echo $userId;?>" />
							<input type="submit" name="action" value="Cambiar contraseña" />
		    			</td>
		    		</tr>
				</table>
			</fieldset>
		</form>
	</div>
	<div id="logineditdiv" style="display:none;margin:15px;">
		<fieldset style='padding:15px;width:500px;'>
	    	<legend><b><?php echo $LANG['CHANGE_LOGIN'];?></b></legend>
			<form name="modifyloginform" action="viewprofile.php" method="post" onsubmit="return verifyModifyLoginForm(this);">
				<div><b>Nuevo usuario:</b> <input name="newlogin" type="text" /></div>
				<?php
				if($isSelf){
					?>
					<div><b><?php echo $LANG['CUR_PASS'];?></b> <input name="newloginpwd" id="newloginpwd" type="password" /></div>
					<?php
				}
				?>
				<div style="margin:10px">
					<input type="hidden" name="userid" value="<?php echo $userId;?>" />
					<input type="submit" name="action" value="Cambiar usuario" />
				</div>
			</form>
		</fieldset>
	</div>
    <div id="managetokensdiv" style="display:none;margin:15px;">
        <fieldset style='padding:15px;width:500px;'>
            <legend><b><?php echo $LANG['MANAGE_ACCESS'];?></b></legend>
            <form name="cleartokenform" action="viewprofile.php" method="post" onsubmit="">
                <div><?php echo $LANG['LEGEND'];?> <b><?php echo ($tokenCount?$tokenCount:0); ?></b>
<?php echo $LANG['LEGEND2'];?></div>
                <div style="margin:10px">
                    <input type="hidden" name="userid" value="<?php echo $userId;?>" />
                    <input type="submit" name="action" value="Borrar tokens" />
                </div>
            </form>
        </fieldset>
    </div>
	<div>
		<div>
			<b><u><?php echo $LANG['TAXA_REL'];?></u></b>
			<a href="#" onclick="toggle('addtaxonrelationdiv')" title="Add a new taxonomic relationship">
				<img style='border:0px;width:15px;' src='../images/add.png'/>
			</a>
		</div>
		<div id="addtaxonrelationdiv" style="display:none;">
			<fieldset style="padding:20px;margin:15px;">
				<legend><b><?php echo $LANG['NEW_TAX_INTER'];?></b></legend>
				<div style="margin-bottom:10px;">
					<?php echo $LANG['LEGEND3'];?>
				</div>
				<form name="addtaxonomyform" action="viewprofile.php" method="post" onsubmit="return verifyAddTaxonomyForm(this)">
					<div style="margin:3px;">
						<b><?php echo $LANG['TAXON'];?></b><br/>
						<input id="taxoninput" name="taxon" type="text" value="" style="width:90%;" onfocus="initTaxonAutoComplete()" />
					</div>
					<div style="margin:3px;">
						<b><?php echo $LANG['SCOPE_REL'];?></b><br/>
						<select name="editorstatus">
							<option value="RegionOfInterest"><?php echo $LANG['REG_INT'];?></option>
							<!-- <option value="OccurrenceEditor">Occurrence Editor</option> -->
						</select>

					</div>
					<div style="margin:3px;">
						<b><?php echo $LANG['GEO_SCOPE'];?></b><br/>
						<input name="geographicscope" type="text" value="" style="width:90%;"/>

					</div>
					<div style="margin:3px;">
						<b><?php echo $LANG['NOTES'];?></b><br/>
						<input name="notes" type="text" value="" style="width:90%;" />

					</div>
					<div style="margin:20px 10px;">
						<input name="action" type="submit" value="Añadir relación taxonómica" />
					</div>
				</form>
			</fieldset>
		</div>
		<?php
		$userTaxonomy = $person->getUserTaxonomy();
		if($userTaxonomy){
			ksort($userTaxonomy);
			foreach($userTaxonomy as $cat => $userTaxArr){
				if($cat == 'RegionOfInterest') $cat = 'Region Of Interest';
				elseif($cat == 'OccurrenceEditor') $cat = 'Occurrence Editor';
				elseif($cat == 'TaxonomicThesaurusEditor') $cat = 'Taxonomic Thesaurus Editor';
				echo '<div style="margin:10px;">';
				echo '<div><b>'.$cat.'</b></div>';
				echo '<ul style="margin:10px;">';
				foreach($userTaxArr as $utid => $utArr){
					echo '<li>';
					echo $utArr['sciname'];
					if($utArr['geographicScope']) echo ' - '.$utArr['geographicScope'].' ';
					if($utArr['notes']) echo ', '.$utArr['notes'];
					echo ' <a href="viewprofile.php?action=delusertaxonomy&utid='.$utid.'&userid='.$userId.'"><img src="../images/drop.png" style="width:14px;" /></a>';
					echo '</li>';
				}
				echo '</ul>';
				echo '</div>';
			}
		}
		else{
			echo '<div style="margin:20px;">'.$LANG['NO_REL'].'</div>';
		}
		?>
	</div>
</div>
