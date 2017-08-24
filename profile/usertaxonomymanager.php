<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/UserTaxonomy.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("action",$_POST)?$_POST["action"]:""; 

$utManager = new UserTaxonomy();

$isEditor = 0;		 
if($SYMB_UID){
	if( $IS_ADMIN ){
		$isEditor = 1;
	}
}
else{
	header('Location: ../profile/index.php?refurl=../profile/usertaxonomymanager.php');
}

$statusStr = '';
if($isEditor){
	if($action == 'Add Taxonomic Relationship'){
		$uid = $_POST['uid'];
		$taxon = $_POST['taxon'];
		$editorStatus = $_POST['editorstatus'];
		$geographicScope = $_POST['geographicscope'];
		$notes = $_POST['notes'];
		$statusStr = $utManager->addUser($uid, $taxon, $editorStatus, $geographicScope, $notes);
	}
	elseif(array_key_exists('delutid',$_GET)){
		$delUid = array_key_exists('deluid',$_GET)?$_GET['deluid']:0;
		$editorStatus = array_key_exists('es',$_GET)?$_GET['es']:'';
		$statusStr = $utManager->deleteUser($_GET['delutid'],$delUid,$editorStatus);
	}
}
$editorArr = $utManager->getTaxonomyEditors();
?>
<html>
<head>
	<title>Taxonomic Interest User permissions</title>
	<meta http-equiv="X-Frame-Options" content="deny">
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link type="text/css" href="../css/jquery-ui.css" rel="stylesheet" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script language=javascript>
		$(document).ready(function() {
			$( "#taxoninput" ).autocomplete({
				source: "rpc/taxasuggest.php",
				minLength: 2,
				autoFocus: true
			});
		});

		function verifyUserAddForm(f){
			if(f.uid.value == ""){
				alert("Select a User");
				return false;
			}
			if(f.editorstatus.value == ""){
				alert("Select the Scope of Relationship");
				return false;
			}
			if(f.taxoninput.value == ""){
				alert("Select the Taxonomic Name");
				return false;
			}
			return true;
		}
	</script>
	<script type="text/javascript" src="../js/symb/shared.js"></script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($profile_usertaxonomymanagerMenu)?$profile_usertaxonomymanagerMenu:true);
	include($serverRoot.'/header.php');
	if(isset($profile_usertaxonomymanagerCrumbs)){
		if($profile_usertaxonomymanagerCrumbs){
			echo "<div class='navpath'>";
			echo "<a href='../index.php'>Home</a> &gt;&gt; ";
			echo $profile_usertaxonomymanagerCrumbs;
			echo " <b>Taxonomic Interest User permissions</b>";
			echo "</div>";
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href='../index.php'>Home</a> &gt;&gt; 
			<b>Taxonomic Interest User permissions</b>
		</div>
		<?php 
	}

	if($statusStr){
		?>
		<hr/>
		<div style="color:<?php echo (strpos($statusStr,'SUCCESS') !== false?'green':'red'); ?>;margin:15px;">
			<?php echo $statusStr; ?>
		</div>
		<hr/>
		<?php 
	}
	if($isEditor){
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<h2>Taxonomic Interest User Permissions</h2>
			<div style="float:right;" title="Add a new taxonomic relationship">
				<a href="#" onclick="toggle('addUserDiv')">
					<img style='border:0px;width:15px;' src='../images/add.png'/>
				</a>
			</div>
			<div id="addUserDiv" style="display:none;">
				<fieldset style="padding:20px;">
					<legend><b>New Taxonomic Relationship</b></legend>
					<form name="adduserform" action="usertaxonomymanager.php" method="post" onsubmit="return verifyUserAddForm(this)">
						<div style="margin:3px;">
							<b>User</b><br/>
							<select name="uid">
								<option value="">-------------------------------</option>
								<?php 
								$userArr = $utManager->getUserArr();
								foreach($userArr as $uid => $displayName){
									echo '<option value="'.$uid.'">'.$displayName.'</option>';
								}
								?>
							</select>
						</div>
						<div style="margin:3px;">
							<b>Taxon</b><br/>
							<input id="taxoninput" name="taxon" type="text" value="" style="width:90%;" />
						</div>
						<div style="margin:3px;">
							<b>Scope of Relationship</b><br/>
							<select name="editorstatus">
								<option value="">----------------------------</option>
								<option value="OccurrenceEditor">Occurrence Identification Editor</option>
								<option value="RegionOfInterest">Region Of Interest</option>
								<option value="TaxonomicThesaurusEditor">Taxonomic Thesaurus Editor</option>
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
						<div style="margin:3px;">
							<input name="action" type="submit" value="Add Taxonomic Relationship" />
						</div>
					</form>
				</fieldset>
			</div>
			<div>
				<?php 
				foreach($editorArr as $editorStatus => $userArr){
					$cat = 'Undefined';
					if($editorStatus == 'RegionOfInterest') $cat = 'Region Of Interest';
					elseif($editorStatus == 'OccurrenceEditor') $cat = 'Occurrence Identification Editor';
					elseif($editorStatus == 'TaxonomicThesaurusEditor') $cat = 'Taxonomic Thesaurus Editor';
					?>
					<div><b><u><?php echo $cat; ?></u></b></div>
					<ul style="margin:10px;">
					<?php 
					foreach($userArr as $uid => $uArr){
						$username = $uArr['username'];
						unset($uArr['username']);
						?>
						<li>
							<?php
							echo '<b>'.$username.'</b>';
							?>
							<a href="usertaxonomymanager.php?delutid=all&deluid=<?php echo $uid.'&es='.$editorStatus; ?>" onclick="return confirm('Are you sure you want to remove all taxonomy links for this user?');" title="Delete all taxonomic relationships for this user">
								<img src="../images/drop.png" style="width:12px;" />
							</a>
							<?php
							foreach($uArr as $utid => $utArr){
								echo '<li style="margin-left:15px;">'.$utArr['sciname'];
								if($utArr['geoscope']) echo ' ('.$utArr['geoscope'].')';
								if($utArr['notes']) echo ': '.$utArr['notes'];
								?>
								<a href="usertaxonomymanager.php?delutid=<?php echo $utid; ?>" onclick="return confirm('Are you sure you want to remove this taxonomy links for this user?');" title="Delete this user taxonomic relationship">
									<img src="../images/drop.png" style="width:12px;" />
								</a>
								<?php
								echo '</li>';
							}
							?>
						</li>
						<?php  
					}
					?>
					</ul>
					<?php 
				}
				?>
			</div>
		</div>
		<?php
	}
	else{
		echo '<div style="color:red;">You are not authorized to access this page</div>';
	}
	include($serverRoot.'/footer.php');
	?>
</body>
