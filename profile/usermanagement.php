<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/PermissionsManager.php');
include_once($serverRoot.'/classes/ProfileManager.php');
header("Content-Type: text/html; charset=".$charset);

$loginAs = array_key_exists("loginas",$_REQUEST)?trim($_REQUEST["loginas"]):"";
$searchTerm = array_key_exists("searchterm",$_REQUEST)?trim($_REQUEST["searchterm"]):"";
$userId = array_key_exists("userid",$_REQUEST)?$_REQUEST["userid"]:"";
$del = array_key_exists("del",$_REQUEST)?$_REQUEST["del"]:"";

$userManager = new PermissionsManager();
if($isAdmin){
	if($loginAs){
		$pHandler = new ProfileManager();
		$pHandler->authenticate($loginAs);
		header("Location: ../index.php");
	}
	elseif($del){
		$userManager->deletionPermissions($del,$userId);
	}
	elseif(array_key_exists("apsubmit",$_POST)){
		$perToAdd = Array();
		if(array_key_exists("p",$_POST)){
			$perToAdd = $_POST["p"];
		}
		if($perToAdd){
			$userManager->addPermissions($perToAdd,$userId);
		}
	}
}
?>
<html>
<head>
    <title><?php echo $defaultTitle; ?> User Management</title>
    <link rel="stylesheet" href="../css/main.css" type="text/css" />
</head>

<body>

	<?php
	$displayLeftMenu = (isset($profile_usermanagementMenu)?$profile_usermanagementMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($profile_usermanagementCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $profile_usermanagementCrumbs;
		echo " <b>User Management</b>"; 
		echo "</div>";
	}
	?> 
        <!-- This is inner text! --> 
        <div class="innertext">
            <div style="float:right;">
				<div style="margin:10px 0px 15px 0px;">
					<fieldset style="background-color:#FFFFCC;padding:0px 10px 10px 10px;">
						<legend style="font-weight:bold;">Search</legend>
						Last Name or Login Name:
						<form name='searchform1' action='usermanagement.php' method='post'>
							<input type='text' name='searchterm' title='Enter Last Name'><br/>
							<input name='submit' type='submit' value='Search'>
						</form>
						Quick Search:
						<div style='margin:2px 0px 0px 10px;'>
							<div><a href='usermanagement.php?searchterm=A'>A</a>|<a href='usermanagement.php?searchterm=B'>B</a>|<a href='usermanagement.php?searchterm=C'>C</a>|<a href='usermanagement.php?searchterm=D'>D</a>|<a href='usermanagement.php?searchterm=E'>E</a>|<a href='usermanagement.php?searchterm=F'>F</a>|<a href='usermanagement.php?searchterm=G'>G</a>|<a href='usermanagement.php?searchterm=H'>H</a></div>
							<div><a href='usermanagement.php?searchterm=I'>I</a>|<a href='usermanagement.php?searchterm=J'>J</a>|<a href='usermanagement.php?searchterm=K'>K</a>|<a href='usermanagement.php?searchterm=L'>L</a>|<a href='usermanagement.php?searchterm=M'>M</a>|<a href='usermanagement.php?searchterm=N'>N</a>|<a href='usermanagement.php?searchterm=O'>O</a>|<a href='usermanagement.php?searchterm=P'>P</a>|<a href='usermanagement.php?searchterm=Q'>Q</a></div>
							<div><a href='usermanagement.php?searchterm=R'>R</a>|<a href='usermanagement.php?searchterm=S'>S</a>|<a href='usermanagement.php?searchterm=T'>T</a>|<a href='usermanagement.php?searchterm=U'>U</a>|<a href='usermanagement.php?searchterm=V'>V</a>|<a href='usermanagement.php?searchterm=W'>W</a>|<a href='usermanagement.php?searchterm=X'>X</a>|<a href='usermanagement.php?searchterm=Y'>Y</a>|<a href='usermanagement.php?searchterm=Z'>Z</a></div>
						</div>
					</fieldset>
				</div>
			</div>
            <?php 
				if($isAdmin){
					if($userId){
						$user = $userManager->getUser($userId);
						?>
						<h1>
							<?php 
								echo $user["firstname"]." ".$user["lastname"]." (#".$user["uid"].") "; 
								echo "<a href='viewprofile.php?emode=1&userid=".$user["uid"]."'><img src='../images/edit.png' style='border:0px;width:15px;' /></a>";
							?>
						</h1>
						<div style="margin-left:10px;">
							<div style="clear:left;">
								<div style="float:left;width:75px;font-weight:bold;">Title: </div>
								<div style="float:left;"><?php echo $user["title"];?></div>
							</div>
							<div style="clear:left;">
								<div style="float:left;width:75px;font-weight:bold;">Institution: </div>
								<div style="float:left;"><?php echo $user["institution"];?></div>
							</div>
							<div style="clear:left;">
								<div style="float:left;width:75px;font-weight:bold;">City: </div>
								<div style="float:left;"><?php echo $user["city"];?></div>
							</div>
							<div style="clear:left;">
								<div style="float:left;width:75px;font-weight:bold;">State: </div>
								<div style="float:left;"><?php echo $user["state"];?></div>
							</div>
							<div style="clear:left;">
								<div style="float:left;width:75px;font-weight:bold;">Zip: </div>
								<div style="float:left;"><?php echo $user["zip"];?></div>
							</div>
							<div style="clear:left;">
								<div style="float:left;width:75px;font-weight:bold;">Country: </div>
								<div style="float:left;"><?php echo $user["country"];?></div>
							</div>
							<div style="clear:left;">
								<div style="float:left;width:75px;font-weight:bold;">Email: </div>
								<div style="float:left;"><?php echo $user["email"];?></div>
							</div>
							<div style="clear:left;">
								<div style="float:left;width:75px;font-weight:bold;">URL: </div>
								<div style="float:left;">
									<a href='<?php echo $user["url"];?>'>
										<?php echo $user["url"];?>
									</a>
								</div>
							</div>
							<div style="clear:left;">
								<div style="float:left;width:75px;font-weight:bold;">Logins: </div>
								<div style="float:left;margin-bottom:30px;">
									<?php
										$loginArr = $user["username"];
										if($loginArr){
											echo implode("; ",$loginArr);
										}
										else{
											echo "No logins are registered";
										}
									?>
								</div>
							</div>
						</div>
						<div style="clear:both;margin:0px 0px 20px 30px;">
							<a href="usermanagement.php?loginas=<?php echo array_shift($loginArr); ?>">Login</a> as this user
						</div>
						<fieldset style="clear:both;margin:10px;">
							<legend><b>Current Permissions</b></legend>
							<?php 
							$userPermissions = $userManager->getUserPermissions($userId);
							if($userPermissions){
								?>
								<div>
									<ul>
									<?php 
									if(array_key_exists("SuperAdmin",$userPermissions)){ 
										?>
										<li>
											<b><?php echo str_replace('SuperAdmin','Super Administrator',$userPermissions["SuperAdmin"]); ?></b> 
											<a href="usermanagement.php?del=SuperAdmin&userid=<?php echo $userId; ?>">
												<img src="../images/del.gif" style="border:0px;width:15px;" title="Delete permission" />
											</a>
										</li>
										<?php 
									}
									if(array_key_exists("Taxonomy",$userPermissions)){ 
										?>
										<li>
											<b><?php echo str_replace('Taxonomy','Taxonomy Editor',$userPermissions["Taxonomy"]); ?></b> 
											<a href="usermanagement.php?del=Taxonomy&userid=<?php echo $userId; ?>">
												<img src="../images/del.gif" style="border:0px;width:15px;" title="Delete permission" />
											</a>
										</li>
										<?php 
									}
									if(array_key_exists("TaxonProfile",$userPermissions)){ 
										?>
										<li>
											<b><?php echo str_replace('TaxonProfile','Taxon Profile Editor',$userPermissions["TaxonProfile"]); ?></b> 
											<a href="usermanagement.php?del=TaxonProfile&userid=<?php echo $userId; ?>">
												<img src="../images/del.gif" style="border:0px;width:15px;" title="Delete permission" />
											</a>
										</li>
										<?php 
									}
									if(array_key_exists("KeyEditor",$userPermissions)){ 
										?>
										<li>
											<b><?php echo str_replace('KeyEditor','Identification Keys Editor',$userPermissions["KeyEditor"]); ?></b>
											<a href="usermanagement.php?del=KeyEditor&userid=<?php echo $userId; ?>">
												<img src="../images/del.gif" style="border:0px;width:15px;" title="Delete permission" />
											</a>
										</li>
										<?php 
									}
									if(array_key_exists("RareSppAdmin",$userPermissions)){ 
										?>
										<li>
											<b><?php echo str_replace('RareSppAdmin','Rare Species List Administrator',$userPermissions["RareSppAdmin"]); ?></b>
											<a href="usermanagement.php?del=RareSppAdmin&userid=<?php echo $userId; ?>">
												<img src="../images/del.gif" style="border:0px;width:15px;" title="Delete permission" />
											</a>
										</li>
										<?php 
									}
									if(array_key_exists("RareSppReadAll",$userPermissions)){ 
										?>
										<li>
											<b><?php echo str_replace('RareSppReadAll','View and Map Specimens of Rare Species from all Collections',$userPermissions["RareSppReadAll"]); ?></b>
											<a href="usermanagement.php?del=RareSppReadAll&userid=<?php echo $userId; ?>">
												<img src="../images/del.gif" style="border:0px;width:15px;" title="Delete permission" />
											</a>
										</li>
										<?php 
									}
									//Collections Admin
									if(array_key_exists("CollAdmin",$userPermissions)){
										echo "<li><b>Collection Administrator for following collections</b></li>";
										$collList = $userPermissions["CollAdmin"];
										asort($collList);
										echo "<ul>";
										foreach($collList as $k => $v){
											echo "<li>$v ";
											echo "<a href='usermanagement.php?del=CollAdmin-$k&userid=$userId'>";
											echo "<img src='../images/del.gif' style='border:0px;width:15px;' title='Delete permission' />";
											echo "</a></li>";
										}
										echo "</ul>";
									}
									//Collections Editor
									if(array_key_exists("CollEditor",$userPermissions)){
										echo "<li><b>Collection Editor for following collections</b></li>";
										$collList = $userPermissions["CollEditor"];
										asort($collList);
										echo "<ul>";
										foreach($collList as $k => $v){
											echo "<li>$v ";
											echo "<a href='usermanagement.php?del=CollEditor-$k&userid=$userId'>";
											echo "<img src='../images/del.gif' style='border:0px;width:15px;' title='Delete permission' />";
											echo "</a></li>";
										}
										echo "</ul>";
									}
									if(array_key_exists("RareSppReader",$userPermissions)){ 
										?>
										<li>
											<b>View and Map Specimens of Rare Species from following Collections</b>
											<ul>
											<?php 
											$rsrArr = $userPermissions["RareSppReader"];
											asort($rsrArr);
											foreach($rsrArr as $collId => $collName){
												?>
												<li>
													<?php echo $collName; ?>
													<a href="usermanagement.php?del=RareSppReader-<?php echo $collId?>&userid=<?php echo $userId; ?>">
														<img src="../images/del.gif" style="border:0px;width:15px;" title="Delete permission" />
													</a>
												</li>
												<?php 
											}
											?>
											</ul>
										</li>
										<?php 
									}
									//Inventory Projects
									if(array_key_exists("ProjAdmin",$userPermissions)){
										?>
										<li>
											<b>Administrator for following inventory projects</b>
											<ul>
												<?php 
												$projList = $userPermissions["ProjAdmin"];
												asort($projList);
												foreach($projList as $k => $v){
													echo "<li>$v";
													echo "<a href='usermanagement.php?del=ProjAdmin-$k&userid=$userId'>";
													echo "<img src='../images/del.gif' style='border:0px;width:15px;' title='Delete permission' />";
													echo "</a></li>";
												}
												?>
											</ul>
										</li>
										<?php 
									}
									//Checklists
									if(array_key_exists("ClAdmin",$userPermissions)){
										?>
										<li>
											<b>Checklist Administrator for following checklists</b>
											<ul>
												<?php 
												$clList = $userPermissions["ClAdmin"];
												asort($clList);
												foreach($clList as $k => $v){
													echo '<li>';
													echo '<a href="../checklists/checklist.php?cl='.$k.'">';
													echo $v;
													echo '</a>';
													echo "<a href='usermanagement.php?del=ClAdmin-$k&userid=$userId'>";
													echo "<img src='../images/del.gif' style='border:0px;width:15px;' title='Delete permission' />";
													echo "</a></li>";
												}
												?>
											</ul>
										</li>
										<?php 
									}
									?>
									</ul>
								</div>
								<?php 									
							}
							else{
								echo "<h3 style='margin:20px;'>No permissions have to been assigned to this user</h3>";
							}
							?>
							<form name="addpermissions" action="usermanagement.php" method="post"/>
								<fieldset style="background-color:#FFFFCC;padding:0px 10px 10px 10px;">
									<legend style="font-weight:bold;">Assign New Permissions</legend>
									<?php 
									if($userPermissions && array_key_exists("SuperAdmin",$userPermissions)){
										echo "<h3>There are no new permissions to be added</h3>";
									}
									else{
										echo "<div style='margin:5px;'>";
										echo "<div><input type='checkbox' name='p[]' value='SuperAdmin' /> Super Administrator</div>";
										if(!array_key_exists("Taxonomy",$userPermissions)){
											echo "<div><input type='checkbox' name='p[]' value='Taxonomy' /> Taxonomy Editor</div>";
										}
										if(!array_key_exists("TaxonProfile",$userPermissions)){
											echo "<div><input type='checkbox' name='p[]' value='TaxonProfile' /> Taxon Profile Editor</div>";
										}
										if(!array_key_exists("KeyEditor",$userPermissions)){
											echo "<div><input type='checkbox' name='p[]' value='KeyEditor' /> Identification Key Editor</div>";
										}
										echo "</div>";
										?>
										<hr/>
										<div style="float:right;">
											<input type='submit' name='apsubmit' value='Add Permission' />
										</div>
										<h2>Occurrence Management</h2>
										<?php
										$showRareSppOption = true;
	 									if(!array_key_exists("RareSppAdmin",$userPermissions)){
	 										$isRareSppDude = false;
											?>
											<div style="margin-left:5px;">
												<input type='checkbox' name='p[]' value='RareSppAdmin' />
												Rare Species Administrator (add/remove species from list)
											</div>
											<?php 
	 									}
	 									else{
											$showRareSppOption = false;
	 									}
	 									if(!array_key_exists("RareSppReadAll",$userPermissions)){
										?>
										<div style="margin-left:5px;">
											<input type='checkbox' name='p[]' value='RareSppReadAll' />
											Can read Rare Species data for all collections
										</div>
										<?php 
	 									}
	 									else{
											$showRareSppOption = false;
	 									}
	 									$rareSppReader = Array();
										if($showRareSppOption && array_key_exists("RareSppReader",$userPermissions)){
											$rareSppReader = array_keys($userPermissions["RareSppReader"]);
										}
										$collEditorArr = Array();
										if(array_key_exists("CollEditor",$userPermissions)){
											$collEditorArr = array_keys($userPermissions["CollEditor"]);
										}
										$collAdminArr = Array();
										if(array_key_exists("CollAdmin",$userPermissions)){
											$collAdminArr = array_keys($userPermissions["CollAdmin"]);
										}
										//Collection projects
										$collectionArr = $userManager->getCollectionArr($collAdminArr);
										if($collectionArr){
	 										?>
	 										<h3>Specimen Collections</h3>
	 										<table>
	 											<tr>
	 												<th>Admin</th>
	 												<th>Editor</th>
	 												<?php if($showRareSppOption) echo '<th>Rare</th>'; ?>
	 												<th>&nbsp;</th>
	 											</tr>
												<?php
												foreach($collectionArr as $k=>$v){
													?>
													<tr>
														<td align="center">
															<input type='checkbox' name='p[]' value='CollAdmin-<?php echo $k;?>' title='Collection Administrator' />
														</td>
														<td align="center">
															<input type='checkbox' name='p[]' value='CollEditor-<?php echo $k;?>' title='Able to add and edit specimen data' <?php if(in_array($k,$collEditorArr)) echo "DISABLED";?> />
														</td>
														<?php if($showRareSppOption){ ?>
															<td align="center">
																<input type='checkbox' name='p[]' value='RareSppReader-<?php echo $k;?>' title='Able to read specimen details for rare species' <?php if(in_array($k,$rareSppReader)) echo "DISABLED";?> />
															</td>
														<?php } ?>
														<td>
															<?php echo $v; ?>
														</td>
													</tr>
													<?php 
												}
												?>
											</table>
											<?php 
										}
										//Observation projects
										$obserArr = $userManager->getObservationArr($collAdminArr);
										if($obserArr){
	 										?>
	 										<h3>Observation Projects</h3>
	 										<table>
	 											<tr>
	 												<th>Admin</th>
	 												<th>Editor</th>
	 												<?php if($showRareSppOption) echo '<th>Rare</th>'; ?>
	 												<th>&nbsp;</th>
	 											</tr>
												<?php
												foreach($obserArr as $k=>$v){
													?>
													<tr>
														<td align="center">
															<input type='checkbox' name='p[]' value='CollAdmin-<?php echo $k;?>' title='Collection Administrator' />
														</td>
														<td align="center">
															<input type='checkbox' name='p[]' value='CollEditor-<?php echo $k;?>' title='Able to add and edit specimen data' <?php if(in_array($k,$collEditorArr)) echo "DISABLED";?> />
														</td>
														<?php if($showRareSppOption){ ?>
															<td align="center">
																<input type='checkbox' name='p[]' value='RareSppReader-<?php echo $k;?>' title='Able to read specimen details for rare species' <?php if(in_array($k,$rareSppReader)) echo "DISABLED";?> />
															</td>
														<?php } ?>
														<td>
															<?php echo $v; ?>
														</td>
													</tr>
													<?php 
												}
												?>
											</table>
											<?php 
										}
										?>
										<div><hr/></div>
										<div style="float:right;">
											<input type='submit' name='apsubmit' value='Add Permission' />
										</div>
										<?php 
										//Get checklists
										$pidArr = Array();
										if(array_key_exists("ProjAdmin",$userPermissions)){
											$pidArr = array_keys($userPermissions["ProjAdmin"]);
										}
										$projectArr = $userManager->getProjectArr($pidArr);
										if($projectArr){
											echo "<h2>Inventory Project Management</h2>";
											foreach($projectArr as $k=>$v){
												?>
												<div style='margin-left:15px;'>
													<?php echo '<input type="checkbox" name="p[]" value="ProjAdmin-'.$k.'" />'.$v; ?>
												</div>
												<?php 
											}
										}
										?>
										<div><hr/></div>
										<div style="float:right;">
											<input type='submit' name='apsubmit' value='Add Permission' />
										</div>
										<?php 
										//Get checklists
										$cidArr = Array();
										if(array_key_exists("ClAdmin",$userPermissions)){
											$cidArr = array_keys($userPermissions["ClAdmin"]);
										}
										$checklistArr = $userManager->getChecklistArr($cidArr);
										if($checklistArr){
											echo "<h2>Checklist Management</h2>";
											foreach($checklistArr as $k=>$v){
												?>
												<div style='margin-left:15px;'>
													<?php echo '<input type="checkbox" name="p[]" value="ClAdmin-'.$k.'" />'.$v; ?>
												</div>
												<?php 
											}
										}
										?>
										<div style='margin:10px;'>
											<input type='submit' name='apsubmit' value='Add Permission' />
										</div>
										<?php 
									}
									?>
									<input type="hidden" name="userid" value="<?php echo $userId;?>" />
								</fieldset>
							</form>
						</fieldset>
						<?php
					}
					else{
		            	$users = $userManager->getUsers($searchTerm);
		            	echo "<h1>Users</h1>";
		            	foreach($users as $id => $name){
		            		echo "<div><a href='usermanagement.php?userid=$id'>$name</a></div>";
		            	}
					}
	            }
	            else{
	            	echo "<h3>You must login and have administrator permissions to manage users</h3>";
	            }
			?>            

		</div>
	<?php
	include($serverRoot.'/footer.php');
	?> 

</body>
</html>
