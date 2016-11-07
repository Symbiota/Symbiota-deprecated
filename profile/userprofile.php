<?php 
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ProfileManager.php');
header("Content-Type: text/html; charset=".$charset);

$userId = $_REQUEST["userid"];

//Sanitation
if(!is_numeric($userId)) $userId = 0;

$pHandler = new ProfileManager();
$pHandler->setUid($userId);
$person = $pHandler->getPerson();
$isSelf = true;
if($userId != $SYMB_UID) $isSelf = false;
?>
<div style="padding:15px;">
	<div>
		<div>
			<b><u>Profile Details</u></b>
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
			echo '<div>Login name: '.($person->getUserName()?$person->getUserName():'not registered').'</div>';
			echo '<div>User information: '.($person->getIsPublic()?'public':'private').'</div>';
			?>
			<div style="font-weight:bold;margin-top:10px;">
				<div><a href="#" onclick="toggleEditingTools('profileeditdiv')">Edit Profile</a></div>
				<div><a href="#" onclick="toggleEditingTools('pwdeditdiv')">Change Password</a></div>
				<div><a href="#" onclick="toggleEditingTools('logineditdiv')">Change Login</a></div>
			</div>
		</div>	
	</div>
	<div id="profileeditdiv" style="display:none;margin:15px;">
		<form name="editprofileform" action="viewprofile.php" method="post" onsubmit="return verifyEditProfileForm(this);">
			<fieldset>
				<legend><b>Edit User Profile</b></legend>
				<table cellspacing='1' style="width:100%;">
				    <tr>
				        <td><b>First Name:</b></td>
				        <td>
							<div>
								<input id="firstname" name="firstname" size="40" value="<?php echo $person->getFirstName();?>">
							</div>
			            </td>
				    </tr>
				    <tr>
				        <td><b>Last Name:</b></td>
				        <td>
							<div>
								<input id="lastname" name="lastname" size="40" value="<?php echo $person->getLastName();?>">
							</div>
			            </td>
				    </tr>
				    <tr>
				        <td><b>Title:</b></td>
				        <td>
							<div>
								<input name="title"  size="40" value="<?php echo $person->getTitle();?>">
							</div>
						</td>
				    </tr>
				    <tr>
				        <td><b>Institution:</b></td>
				        <td>
							<div>
								<input name="institution"  size="40" value="<?php echo $person->getInstitution();?>">
							</div>
						</td>
				    </tr>
				    <tr>
				        <td><b>City:</b></td>
				        <td>
							<div>
				            	<input id="city" name="city" size="40" value="<?php echo $person->getCity();?>">
							</div>
			            </td>
				    </tr>
				    <tr>
				        <td><b>State:</b></td>
				        <td>
							<div>
					            <input id="state" name="state" size="40" value="<?php echo $person->getState();?>">
							</div>
			            </td>
				    </tr>
				    <tr>
				        <td><b>Zip Code:</b></td>
				        <td>
							<div>
					            <input name="zip" size="40" value="<?php echo $person->getZip();?>">
							</div>
			            </td>
				    </tr>
				    <tr>
				        <td><b>Country:</b></td>
				        <td>
							<div>
								<input id="country" name="country" size="40" value="<?php echo $person->getCountry();?>">
							</div>
						</td>
				    </tr>
				    <tr>
				        <td><b>Email Address:</b></td>
				        <td>
							<div>
					            <input id="email" name="email" size="40" value="<?php echo $person->getEmail();?>">
							</div>
			            </td>
				    </tr>
				    <tr>
				        <td><b>Url:</b></td>
				        <td>
							<div>
								<input name="url"  size="40" value="<?php echo $person->getUrl();?>">
							</div>
	
						</td>
				    </tr>
				    <tr>
				        <td><b>Biography:</b></td>
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
								Make user information displayable to public  
			        		</div>
						</td>
				    </tr>
				    <tr>
						<td colspan="2">
							<div style="margin:10px;">
								<input type="hidden" name="userid" value="<?php echo $userId;?>" />
								<input type="submit" name="action" value="Submit Edits" />
							</div>
						</td>
					</tr>
				</table>
			</fieldset>
		</form>
		<form name="delprofileform" action="viewprofile.php" method="post" onsubmit="return window.confirm('Are you sure you want to delete profile?');">
			<fieldset style='padding:15px;width:200px;'>
		    	<legend><b>Delete Profile</b></legend>
				<input type="hidden" name="userid" value="<?php echo $userId;?>" />
	    		<input type="submit" name="action" value="Delete Profile" />
			</fieldset>
		</form>
	</div>
	<div id="pwdeditdiv" style="display:none;margin:15px;">
		<form name="changepwdform" action="viewprofile.php" method="post" onsubmit="return verifyPwdForm(this);">
			<fieldset style='padding:15px;width:500px;'>
		    	<legend><b>Change Password</b></legend>
		    	<table>
					<?php 
					if($isSelf){ 
						?>
			    		<tr>
			    			<td>
				            	<b>Current Password:</b>
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
			            	<b>New Password:</b> 
			            </td>
			            <td> 
			            	<input id="newpwd" name="newpwd" type="password"/>
		    			</td>
		    		</tr>
		    		<tr>
		    			<td>
							<b>New Password Again:</b> 
			            </td>
			            <td> 
							<input id="newpwd2" name="newpwd2" type="password"/>
			    		</td>
			    	</tr>
		    		<tr>
		    			<td colspan="2">
							<input type="hidden" name="userid" value="<?php echo $userId;?>" />
							<input type="submit" name="action" value="Change Password" />
		    			</td>
		    		</tr>
				</table>
			</fieldset>
		</form>
	</div>
	<div id="logineditdiv" style="display:none;margin:15px;">
		<fieldset style='padding:15px;width:500px;'>
	    	<legend><b>Change Login Name</b></legend>
			<form name="modifyloginform" action="viewprofile.php" method="post" onsubmit="return verifyModifyLoginForm(this);">
				<div><b>New Login Name:</b> <input name="newlogin" type="text" /></div>
				<?php 
				if($isSelf){ 
					?>
					<div><b>Current Password:</b> <input name="newloginpwd" type="password" /></div>
					<?php
				}
				?>
				<div style="margin:10px">
					<input type="hidden" name="userid" value="<?php echo $userId;?>" />
					<input type="submit" name="action" value="Change Login" />
				</div>
			</form>
		</fieldset>
	</div>
	<div>
		<div>
			<b><u>Taxonomic Relationships</u></b> 
			<a href="#" onclick="toggle('addtaxonrelationdiv')" title="Add a new taxonomic relationship">
				<img style='border:0px;width:15px;' src='../images/add.png'/>
			</a>
		</div>
		<div id="addtaxonrelationdiv" style="display:none;">
			<fieldset style="padding:20px;margin:15px;">
				<legend><b>New Taxonomic Region of Interest</b></legend>
				<div style="margin-bottom:10px;">
					Use this form to define a new taxon-based region of interest. 
					Contact portal administrators for assignment of new 
					taxon specific Occurrence Identification and Taxonomic Thesaurus editing status.  
				</div>
				<form name="addtaxonomyform" action="viewprofile.php" method="post" onsubmit="return verifyAddTaxonomyForm(this)">
					<div style="margin:3px;">
						<b>Taxon</b><br/>
						<input id="taxoninput" name="taxon" type="text" value="" style="width:90%;" onfocus="initTaxonAutoComplete()" />
					</div>
					<div style="margin:3px;">
						<b>Scope of Relationship</b><br/>
						<select name="editorstatus">
							<option value="RegionOfInterest">Region Of Interest</option>
							<!-- <option value="OccurrenceEditor">Occurrence Editor</option> -->
						</select>
					
					</div>
					<div style="margin:3px;">
						<b>Geographic Scope Limits</b><br/>
						<input name="geographicscope" type="text" value="" style="width:90%;"/>
					
					</div>
					<div style="margin:3px;">
						<b>Notes</b><br/>
						<input name="notes" type="text" value="" style="width:90%;" />
					
					</div>
					<div style="margin:20px 10px;">
						<input name="action" type="submit" value="Add Taxonomic Relationship" />
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
			echo '<div style="margin:20px;">No relationships defined</div>';
		}
		?>
	</div>
</div>
