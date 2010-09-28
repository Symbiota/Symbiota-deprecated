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
	elseif(array_key_exists("apsubmit",$_REQUEST)){
		$perToAdd = Array();
		if(array_key_exists("p",$_REQUEST)){
			$perToAdd = $_REQUEST["p"];
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
						<div style="clear:both;margin:10px;" class="fieldset">
							<div class="legend">Current Permissions</div>
							<?php 
								$userPermissions = $userManager->getUserPermissions($userId);
								if($userPermissions){
									?>
									<div style='font-weight:bold;'>
										<ul>
										<?php 
										if(array_key_exists("SuperAdmin",$userPermissions)){ 
											?>
											<li>
												Super Administrator 
												<a href="usermanagement.php?del=SuperAdmin&userid=<?php echo $userId; ?>">
													<img src="../images/del.gif" style="border:0px;width:15px;" title="Delete permission" />
												</a>
											</li>
											<?php 
										}
										if(array_key_exists("Taxonomy",$userPermissions)){ 
											?>
											<li>
												Taxonomy Editor 
												<a href="usermanagement.php?del=Taxonomy&userid=<?php echo $userId; ?>">
													<img src="../images/del.gif" style="border:0px;width:15px;" title="Delete permission" />
												</a>
											</li>
											<?php 
										}
										if(array_key_exists("TaxonProfile",$userPermissions)){ 
											?>
											<li>
												Taxon Profile Editor 
												<a href="usermanagement.php?del=TaxonProfile&userid=<?php echo $userId; ?>">
													<img src="../images/del.gif" style="border:0px;width:15px;" title="Delete permission" />
												</a>
											</li>
											<?php 
										}
										if(array_key_exists("RareSppAdmin",$userPermissions)){ 
											?>
											<li>
												Rare Species List Administrator
												<a href="usermanagement.php?del=RareSppAdmin&userid=<?php echo $userId; ?>">
													<img src="../images/del.gif" style="border:0px;width:15px;" title="Delete permission" />
												</a>
											</li>
											<?php 
										}
										if(array_key_exists("RareSppReadAll",$userPermissions)){ 
											?>
											<li>
												View and Map Specimens of Rare Species from all Collections
												<a href="usermanagement.php?del=RareSppReadAll&userid=<?php echo $userId; ?>">
													<img src="../images/del.gif" style="border:0px;width:15px;" title="Delete permission" />
												</a>
											</li>
											<?php 
										}
										if(array_key_exists("RareSppReader",$userPermissions)){ 
											?>
											<li>
												View and Map Specimens of Rare Species from following Collections
												<ul>
												<?php 
												$rsrArr = $userPermissions["RareSppReader"];
												foreach($rsrArr as $collId => $collName){
													?>
													<li>
														<a href="usermanagement.php?del=RareSppReader-<?php echo $collId?>&userid=<?php echo $userId; ?>">
															<?php echo $collName; ?>
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
										if(array_key_exists("KeyEditor",$userPermissions)){ 
											?>
											<li>
												Identification Keys Editor
												<a href="usermanagement.php?del=KeyEditor&userid=<?php echo $userId; ?>">
													<img src="../images/del.gif" style="border:0px;width:15px;" title="Delete permission" />
												</a>
											</li>
											<?php 
										}
										//Checklists
										if(array_key_exists("ClAdmin",$userPermissions)){
											echo "<div style='font-weight:bold;text-decoration:underline;'>Checklists</div>";
											$clList = $userPermissions["ClAdmin"];
											echo "<ul>";
											foreach($clList as $k => $v){
												echo "<li>$v";
												echo "<a href='usermanagement.php?del=ClAdmin-$k&userid=$userId'>";
												echo "<img src='../images/del.gif' style='border:0px;width:15px;' title='Delete permission' />";
												echo "</a></li>";
											}
											echo "</ul>";
										}
										//Collections
										if(array_key_exists("CollAdmin",$userPermissions)){
											echo "<li>Collection Administrator for following collections</li>";
											$collList = $userPermissions["CollAdmin"];
											echo "<ul>";
											foreach($collList as $k => $v){
												echo "<li>$v ";
												echo "<a href='usermanagement.php?del=CollAdmin-$k&userid=$userId'>";
												echo "<img src='../images/del.gif' style='border:0px;width:15px;' title='Delete permission' />";
												echo "</a></li>";
											}
											echo "</ul>";
										}
										if(array_key_exists("CollEditor",$userPermissions)){
											echo "<li>Collection Editor for following collections</li>";
											$collList = $userPermissions["CollEditor"];
											echo "<ul>";
											foreach($collList as $k => $v){
												echo "<li>$v ";
												echo "<a href='usermanagement.php?del=CollEditor-$k&userid=$userId'>";
												echo "<img src='../images/del.gif' style='border:0px;width:15px;' title='Delete permission' />";
												echo "</a></li>";
											}
											echo "</ul>";
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
							<form name="addpermissions" action="usermanagement.php" method="get"/>
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
											echo "<div><input type='checkbox' name='p[]' value='KeyEditor' /> Identification Key Editor (add/modify to rare species list</div>";
										}
										echo "</div>";
										?>
										<hr/>
										<h3>Collection Management</h3>
										<?php
 										if(!array_key_exists("RareSppReadAll",$userPermissions)){
										?>
										<div style="margin-left:5px;">
											<input type='checkbox' name='p[]' value='RareSppReadAll' />
											Can read Rare Species data for all collections
										</div>
										<?php 
 										}
 										if(!array_key_exists("RareSppAdmin",$userPermissions)){
										?>
										<div style="margin-left:5px;">
											<input type='checkbox' name='p[]' value='RareSppAdmin' />
											Rare Species Administrator
										</div>
										<?php 
 										}
										$collAdminArr = Array();
										if(array_key_exists("CollAdmin",$userPermissions)){
											$collAdminArr = $userPermissions["CollAdmin"];
										}
										$collectionArr = $userManager->getAddCollectionArr($collAdminArr);
										if($collectionArr){
	 										?>
											<div style='margin:10px 0px 0px 3px;'>Admin&nbsp;Reader&nbsp;Rare Species</div>
											<?php
											foreach($collectionArr as $k=>$v){
												?>
												<div style='clear:left;;margin-left:15px;'>
													<div style="float:left">
														<input type='checkbox' name='p[]' value='CollAdmin-<?php echo $k;?>' title='Collection Administrator' />
													</div>
													<div style="float:left;margin-left:15px;">
														<input type='checkbox' name='p[]' value='CollEditor-<?php echo $k;?>' title='Able to add and edit specimen data' />
													</div>
													<div style="float:left;margin-left:15px;">
														<input type='checkbox' name='p[]' value='RareSppReader-<?php echo $k;?>' title='Able to read specimen details for rare species' />
													</div>
													<div style="float:left;margin-left:15px;width:300px;">
														<?php echo $v; ?>
													</div>
												</div>
												<?php 
											}
											echo "<div style='clear:both;'><hr/></div>";
										}
										//Get checklists
										$clArr = Array();
										if(array_key_exists("ClAdmin",$userPermissions)){
											$clArr = $userPermissions["ClAdmin"];
										}
										$checklistArr = $userManager->getAddChecklistArr($clArr);
										if($checklistArr){
											echo "<h3>Checklist Management</h3>";
											foreach($checklistArr as $k=>$v){
												echo "<div style='margin-left:15px;'><input type='checkbox' name='p[]' value='ClAdmin-$k' /> $v</div>";
											}
										}
										echo "<div style='margin:10px;'><input type='submit' name='apsubmit' value='Add Permission' /></div>";
									}
									?>
									<input type="hidden" name="userid" value="<?php echo $userId;?>" />
								</fieldset>
							</form>
						</div>
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
