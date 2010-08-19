<?php
//error_reporting(E_ALL);
include_once("../util/dbconnection.php");
include_once("../util/symbini.php");
include_once("util/ProfileHandler.php");

header("Content-Type: text/html; charset=".$charset);
$loginAs = array_key_exists("loginas",$_REQUEST)?trim($_REQUEST["loginas"]):"";
$searchTerm = array_key_exists("searchterm",$_REQUEST)?trim($_REQUEST["searchterm"]):"";
$userId = array_key_exists("userid",$_REQUEST)?$_REQUEST["userid"]:"";
$del = array_key_exists("del",$_REQUEST)?$_REQUEST["del"]:"";

$userManager = new UserManager();
if($isAdmin){
	if($loginAs){
		$pHandler = new ProfileHandler();
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
	include($serverRoot."/util/header.php");
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
							<div class="legend">Permissions</div>
							<?php 
								$userPermissions = $userManager->getUserPermissions($userId);
								if($userPermissions){
									?>
									<div style='font-weight:bold;'>
										<div style='font-weight:bold;text-decoration:underline;margin-left:5px;'>
											General
										</div>
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
										if(array_key_exists("RareSpecies",$userPermissions)){ 
											?>
											<li>
												Rare Species List
												<a href="usermanagement.php?del=RareSpecies&userid=<?php echo $userId; ?>">
													<img src="../images/del.gif" style="border:0px;width:15px;" title="Delete permission" />
												</a>
											</li>
											<?php 
										}
										?>
										</ul>
									</div>
									<?php 									
									//Collections
									if(array_key_exists("coll",$userPermissions)){
										echo "<div style='font-weight:bold;text-decoration:underline;margin-left:5px;'>Collections</div>";
										$collList = $userPermissions["coll"];
										echo "<ul>";
										foreach($collList as $k => $v){
											echo "<li>$v ";
											echo "<a href='usermanagement.php?del=coll-$k&userid=$userId'>";
											echo "<img src='../images/del.gif' style='border:0px;width:15px;' title='Delete permission' />";
											echo "</a></li>";
										}
										echo "</ul>";
									}
									//Checklists
									if(array_key_exists("cl",$userPermissions)){
										echo "<div style='font-weight:bold;text-decoration:underline;'>Checklists</div>";
										$clList = $userPermissions["cl"];
										echo "<ul>";
										foreach($clList as $k => $v){
											echo "<li>$v";
											echo "<a href='usermanagement.php?del=cl-$k&userid=$userId'>";
											echo "<img src='../images/del.gif' style='border:0px;width:15px;' title='Delete permission' />";
											echo "</a></li>";
										}
										echo "</ul>";
									}
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
											echo "<div><input type='checkbox' name='p[]' value='Taxonomy' /> Taxonomy</div>";
										}
										if(!array_key_exists("TaxonProfile",$userPermissions)){
											echo "<div><input type='checkbox' name='p[]' value='TaxonProfile' /> Taxon Profile</div>";
										}
										if(!array_key_exists("RareSpecies",$userPermissions)){
											echo "<div><input type='checkbox' name='p[]' value='RareSpecies' /> Rare Species</div>";
										}
										echo "</div>";
										//Get collections
										$collArr = Array();
										if(array_key_exists("coll",$userPermissions)){
											$collArr = $userPermissions["coll"];
										}
										$collectionArr = $userManager->getAddCollections($collArr);
										if($collectionArr){
											?>
											<hr/>
											<h3>Collection Management</h3>
											<div style="margin-left:5px;">
												<input type='checkbox' name='p[]' value='coll-reader' />
												Can read Rare Species data for all collections
											</div>
											<div style="margin-left:5px;">
												<input type='checkbox' name='p[]' value='coll-admin' />
												Can add and modify specimen data for all collections
											</div>
											<div style='margin:10px 0px 0px 3px;'>Reader&nbsp;Admin</div>
											<?php
											foreach($collectionArr as $k=>$v){
												?>
												<div style='clear:left;;margin-left:15px;'>
													<div style="float:left">
														<input type='checkbox' name='p[]' value='coll-<?php echo $k;?>-reader' title='Able to read Rare Species data' />
													</div>
													<div style="float:left;margin-left:15px;">
														<input type='checkbox' name='p[]' value='coll-<?php echo $k;?>-admin' title='Able to add and modify specimen data' />
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
										if(array_key_exists("cl",$userPermissions)){
											$clArr = $userPermissions["cl"];
										}
										$checklistArr = $userManager->getAddChecklists($clArr);
										if($checklistArr){
											echo "<h3>Checklist Management</h3>";
											foreach($checklistArr as $k=>$v){
												echo "<div style='margin-left:15px;'><input type='checkbox' name='p[]' value='cl-$k' /> $v</div>";
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
	include($serverRoot."/util/footer.php");
	?> 

</body>
</html>

<?php 
class UserManager{
	
	private $conn;
	
	function __construct() {
		$this->conn = MySQLiConnectionFactory::getCon("write");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function getUsers($keyword){
		$returnArr = Array();
		$sql = "SELECT u.uid, u.firstname, u.lastname ".
			"FROM users u LEFT JOIN userlogin ul ON u.uid = ul.uid ";
		if($keyword){
			$sql .= "WHERE u.lastname LIKE '".$keyword."%' ";
			if(strlen($keyword) > 1) $sql .= "OR ul.username LIKE '".$keyword."%' ";
		}
		$sql .= "ORDER BY u.lastname, u.firstname";
		//echo "<div>".$sql."</div>";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArr[$row->uid] = $row->lastname.", ".$row->firstname;
		}
		$result->close();
		return $returnArr;
	}
	
	public function getUser($uid){
		$returnArr = Array();
		$sql = "SELECT u.uid, u.firstname, u.lastname, u.title, u.institution, u.city, u.state, ".
			"u.zip, u.country, u.email, u.url, u.notes, ul.username ".
			"FROM users u LEFT JOIN userlogin ul ON u.uid = ul.uid ".
			"WHERE u.uid = ".$uid;
		//echo "<div>$sql</div>";
		$result = $this->conn->query($sql);
		if($row = $result->fetch_object()){
			$returnArr["uid"] = $row->uid;
			$returnArr["firstname"] = $row->firstname;
			$returnArr["lastname"] = $row->lastname;
			$returnArr["title"] = $row->title;
			$returnArr["institution"] = $row->institution;
			$returnArr["city"] = $row->city;
			$returnArr["state"] = $row->state;
			$returnArr["zip"] = $row->zip;
			$returnArr["country"] = $row->country;
			$returnArr["email"] = $row->email;
			$returnArr["url"] = $row->url;
			$returnArr["notes"] = $row->notes;
			$returnArr["username"][] = $row->username;
			while($row = $result->fetch_object()){
				$returnArr["username"][] = $row->username;
			}
		}
		$result->close();
		return $returnArr;
	}
	
	public function getUserPermissions($uid){
		$perArr = Array();
		$sql = "SELECT up.pname FROM userpermissions up WHERE up.uid = ".$uid;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$pName = $row->pname;
			if(substr($pName,0,5) == "coll-"){
				$collId = substr($pName,5);
				$perArr["coll"][$collId] = $collId;
			}
			elseif(substr($pName,0,3) == "cl-"){
				$clid = substr($pName,3);
				$perArr["cl"][$clid] = $clid;
			}
			else{
				$perArr[$pName] = $pName;
			}
		}
		$result->close();
		
		//If there are collections, get names
		if(array_key_exists("coll",$perArr)){
			$sql = "SELECT c.collid, c.collectionname FROM omcollections c WHERE c.collid IN(".implode(",",$perArr["coll"]).")";
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$perArr["coll"][$row->collid] = $row->collectionname;
			}
			$result->close();
		}
		
		//If there are checklist, fetch names
		if(array_key_exists("cl",$perArr)){
			$sql = "SELECT cl.clid, cl.name FROM fmchecklists cl WHERE cl.clid IN(".implode(",",$perArr["cl"]).")";
			$result = $this->conn->query($sql);
			while($row = $result->fetch_object()){
				$perArr["cl"][$row->clid] = $row->name;
			}
			$result->close();
		}
		
		return $perArr;
	}
	
	public function deletionPermissions($delStr, $id){
		$sql = "DELETE FROM userpermissions WHERE uid = $id AND pname = '".$delStr."'";
		$this->conn->query($sql);
	}
	
	public function addPermissions($addList,$id){
		if($addList){
			$addStr = "(".$id.",'".implode("'),($id,'",$addList)."')"; 
			$sql = "INSERT INTO userpermissions(uid,pname) VALUES$addStr";
			//echo $sql;
			$this->conn->query($sql);
		}
	}
	
	public function getAddCollections($currentColl){
		$returnArr = Array();
		$collKey = str_replace("coll-","",array_keys($currentColl));
		$sql = "SELECT c.collid, c.collectionname FROM omcollections c ";
		if($currentColl) $sql .= "WHERE c.collid NOT IN(".implode(",",$collKey).") ORDER BY c.collectionname";
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArr[$row->collid] = $row->collectionname;
		}
		return $returnArr;
	} 

	public function getAddChecklists($currentCl){
		$returnArr = Array();
		$clKeys = str_replace("cl-","",array_keys($currentCl));
		$sql = "SELECT cl.clid, cl.name FROM fmchecklists cl ";
		if($currentCl) $sql .= "WHERE cl.clid NOT IN(".implode(",",$clKeys).") ORDER BY cl.name";
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$returnArr[$row->clid] = $row->name;
		}
		return $returnArr;
	} 
}
?>