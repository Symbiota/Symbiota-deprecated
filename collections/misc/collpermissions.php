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
		$editCode = 1;
	}
}

if($isEditor){

}

$collData = $permManager->getCollectionData();

?>
<html>
<head>
	<title><?php echo $defaultTitle." ".($collId?$collData["collectionname"]:"") ; ?> Collection Permissions</title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
	<script language=javascript>
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
			<a href='collprofiles.php'>Collect Management</a> &gt; 
			<b><?php echo $collData['collectionname'].' Permissions'; ?></b>
		</div>
		<?php 
	}
	?>

	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		$collPerms = $permManager->getEditors();
		?>
		<fieldset>
			<legend>Administrators</legend>
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
						<form name="deluser" action="collpermissions" method="post" onclick="">
							<input type="hidden" name="deluser" value="<?php echo $uid; ?>" />
							<input type="image" src="../../images/del.png" name="deluser" />
						</form>
					</li>
					<?php 
				}
				?>
				</ul>
				<?php 
			}
			else{
				echo "<h2>No one has explicitly assigned Administrative Permissions (excluding Super Admins)</h2>";
			}
			?>
		</fieldset>
		<fieldset>
			<legend>Editors</legend>
			<?php 
			if(array_key_exists('editor',$collPerms)){
				echo '<li>';
				$editorArr = $collPerms['editor'];
				
			}
			else{
				echo "<h2>No one has explicitly assigned Editor Permissions</h2>";
			}
			?>
			*Administrators automatically inherit editing rights
		</fieldset>
		<fieldset>
			<legend>Rare Species Readers</legend>
			<?php 
			if(array_key_exists('rarespp',$collPerms)){
				echo '<li>';
				$rareSppArr = $collPerms['rarespp'];
				
			}
			else{
				echo "<h2>No one has explicitly assigned permissions to view locality data for species with a Rare/Threatened/Protected Species status</h2>";
			}
			?>
			*Administrators and Editors automatically inherit protected species viewing rights for that collection
		</fieldset>
	</div>
	<?php
		include($serverRoot.'/footer.php');
	?>

</body>
</html>