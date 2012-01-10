<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/CollectionPermissions.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;

$permManager = new CollectionPermissions();
$permManager->setCollectionId($collId);

$isEditor = 0;		 
if($symbUid){
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
		$isEditor = 1;
	}
}

if($isEditor){
	if(array_key_exists('deladmin',$_GET)){
		$uid = $_GET['deladmin'];
		$permManager->deletePermission($uid,'admin');
	}
	elseif(array_key_exists('deleditor',$_GET)){
		$uid = $_GET['deleditor'];
		$permManager->deletePermission($uid,'editor');
	}
	elseif(array_key_exists('delrare',$_GET)){
		$uid = $_GET['delrare'];
		$permManager->deletePermission($uid,'rare');
	}
	elseif($action == 'Add Permissions for User'){
		$uid = $_POST['uid'];
		$right = $_POST['righttype'];
		$permManager->addUser($uid,$right);
	}
}

$collData = $permManager->getCollectionData();

?>
<html>
<head>
	<title><?php echo $defaultTitle." ".($collId?$collData["collectionname"]:"") ; ?> Collection Permissions</title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
	<script language=javascript>
		function verifyAddRights(f){
			if(f.uid.value == ""){
				alert("Please select a user from list");
				return false;
			}
			else if(f.righttype.value == ""){
				alert("Please select the permissions you wish to assign this user");
				return false;
			}
			return true;
		}
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_misc_collpermissionsMenu)?$collections_misc_collpermissionsMenu:true);
	include($serverRoot.'/header.php');
	if(isset($collections_misc_collpermissionsCrumbs)){
		if($collections_misc_collpermissionsCrumbs){
			echo "<div class='navpath'>";
			echo "<a href='../../index.php'>Home</a> &gt; ";
			echo $collections_misc_collpermissionsCrumbs;
			echo " <b>".($collData?$collData["collectionname"]:"Collection Profiles")."</b>";
			echo "</div>";
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href='../../index.php'>Home</a> &gt; 
			<a href='../index.php'>Collections</a> &gt; 
			<a href='collprofiles.php?emode=1&collid=<?php echo $collId; ?>'>Collection Management</a> &gt; 
			<b><?php echo $collData['collectionname'].' Permissions'; ?></b>
		</div>
		<?php 
	}
	?>

	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($isEditor){
			$collPerms = $permManager->getEditors();
			?>
			<fieldset style="margin:15px;padding:15px;">
				<legend><b>Administrators</b></legend>
				<?php 
				if(array_key_exists('admin',$collPerms)){
					?>
					<ul>
					<?php 
					$adminArr = $collPerms['admin'];
					foreach($adminArr as $uid => $uName){
						?>
						<li>
							<?php echo $uName; ?> 
							<a href="collpermissions.php?collid=<?php echo $collId.'&deladmin='.$uid; ?>" onclick="return confirm('Are you sure you want to remove administrative rights for this user?');" title="Delete permissions for this user">
								<img src="../../images/drop.png" style="width:12px;" />
							</a>
						</li>
						<?php 
					}
					?>
					</ul>
					<?php 
				}
				else{
					echo '<div style="font-weight:bold;font-size:120%;">';
					echo 'No one has explicitly assigned Administrative Permissions (excluding Super Admins)';
					echo '</div>';
				}
				?>
			</fieldset>
			<fieldset style="margin:15px;padding:15px;">
				<legend><b>Editors</b></legend>
				<?php 
				if(array_key_exists('editor',$collPerms)){
					?>
					<ul>
					<?php 
					$editorArr = $collPerms['editor'];
					foreach($editorArr as $uid => $uName){
						?>
						<li>
							<?php echo $uName; ?> 
							<a href="collpermissions.php?collid=<?php echo $collId.'&deleditor='.$uid; ?>" onclick="return confirm('Are you sure you want to remove editing rights for this user?');" title="Delete permissions for this user">
								<img src="../../images/drop.png" style="width:12px;" />
							</a>
						</li>
						<?php 
					}
					?>
					</ul>
					<?php 
				}
				else{
					echo '<div style="font-weight:bold;font-size:120%;">';
					echo 'No one has explicitly assigned Editor Permissions';
					echo '</div>';
				}
				?>
				<div style="margin:10px">
					*Administrators automatically inherit editing rights
				</div>
			</fieldset>
			<fieldset style="margin:15px;padding:15px;">
				<legend><b>Rare Species Readers</b></legend>
				<?php 
				if(array_key_exists('rarespp',$collPerms)){
					?>
					<ul>
					<?php 
					$rareArr = $collPerms['rarespp'];
					foreach($rareArr as $uid => $uName){
						?>
						<li>
							<?php echo $uName; ?> 
							<a href="collpermissions.php?collid=<?php echo $collId.'&delrare='.$uid; ?>" onclick="return confirm('Are you sure you want to remove user rights to view locality details for rare species?');" title="Delete permissions for this user">
								<img src="../../images/drop.png" style="width:12px;" />
							</a>
						</li>
						<?php 
					}
					?>
					</ul>
					<?php 
				}
				else{
					echo '<div style="font-weight:bold;font-size:110%;">';
					echo 'No one has explicitly assigned permissions to view locality data for species with a Rare/Threatened/Protected Species status';
					echo '</div>';
				}
				?>
				<div style="margin:10px">
					*Administrators and Editors automatically inherit protected species viewing rights
				</div>
			</fieldset>
			<fieldset style="margin:15px;padding:15px;">
				<legend><b>Add a User</b></legend>
				<form name="addrights" action="collpermissions.php" method="post" onsubmit="return verifyAddRights(this)">
					<div>
						User: 
						<select name="uid">
							<option value="">Select User</option>
							<?php 
							$userArr = $permManager->getUsers();
							foreach($userArr as $uid => $uName){
								echo '<option value="'.$uid.'">'.$uName.'</option>';
							}
							?>
						</select> 
					</div>
					<div style="margin:5px 0px 5px 0px;">
						<input name="righttype" type="radio" value="admin" /> Administrator <br/> 
						<input name="righttype" type="radio" value="editor" /> Editor <br/>
						<input name="righttype" type="radio" value="rare" /> Rare Species Reader<br/>
					</div>
					<div>
						<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
						<input name="action" type="submit" value="Add Permissions for User" />
					</div> 
				</form>
			</fieldset>
			<?php
		}
		else{
			echo '<div style="font-weight:bold;font-size:120%;">';
			echo 'Unauthorized to view this page. You must have administrative right for this collection.';
			echo '</div>';
		} 
		?>
	</div>
	<?php
		include($serverRoot.'/footer.php');
	?>

</body>
</html>