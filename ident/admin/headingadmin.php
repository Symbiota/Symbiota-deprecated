<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/IdentCharAdmin.php');

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../ident/admin/headingadmin.php?'.$_SERVER['QUERY_STRING']);

$hid = array_key_exists('hid',$_REQUEST)?$_REQUEST['hid']:0;
$action = array_key_exists('action',$_GET)?$_GET['action']:'';
$langId = array_key_exists('langid',$_REQUEST)?$_REQUEST['langid']:'';

$charManager = new IdentCharAdmin();
$charManager->setLangId($langId);
$headingArr = $charManager->getHeadingArr();

$isEditor = false;
if($isAdmin || array_key_exists("KeyAdmin",$userRights)){
	$isEditor = true;
}

$statusStr = '';
if($isEditor && $action){
	if($action == 'Create'){
		$statusStr = $charManager->addingHeading($_POST['headingname'],$_POST['notes'],$_POST['sortsequence']);
	}
	elseif($action == 'Save'){
		$statusStr = $charManager->editHeading($hid,$_POST['headingname'],$_POST['notes'],$_POST['sortsequence']);
	}
	elseif($action == 'Delete'){
		$statusStr = $charManager->deleteHeading($hid);
	}
}
?>
<!DOCTYPE HTML>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
	<title>Heading Administration</title>
	<link type="text/css" href="../../css/main.css" rel="stylesheet" />
	<script type="text/javascript" src="../../js/symb/shared.js"></script>
	<script type="text/javascript">
		function validateNewHeadingForm(f){

		}
	</script>
	<style type="text/css">
		input{ autocomplete: off; } 
	</style>
</head>
<body>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($statusStr){
			?>
			<hr/>
			<div style="margin:15px;color:<?php echo (strpos($statusStr,'SUCCESS')===0?'green':'red'); ?>;">
				<?php echo $statusStr; ?>
			</div>
			<hr/>
			<?php 
		}
		if($isEditor){
			?>
			<div style="float:right;margin:10px;">
				<a href="#" onclick="toggle('addheadingdiv');">
					<img src="../../images/add.png" alt="Create New Heading" />
				</a>
			</div>
			<div id="addheadingdiv" style="display:<?php echo ($hid?'none':'block'); ?>;">
				<form name="newheadingform" action="headingadmin.php" method="post" onsubmit="return validateNewHeadingForm(this)">
					<fieldset>
						<legend><b>New Heading</b></legend>
						<div>
							Heading Name<br />
							<input type="text" name="headingname" maxlength="255" style="width:400px;" />
						</div>
						<div style="padding-top:6px;">
							<b>Notes</b><br />
							<input type="text" name="notes" />
						</div>
						<div style="padding-top:6px;">
							<b>Sort Sequence</b><br />
							<input type="text" name="sortsequence" />
						</div>
						<div style="width:100%;padding-top:6px;">
							<button name="action" type="submit" value="Create">Create Heading</button>
						</div>
					</fieldset>
				</form>
			</div>
			<div id="headinglist">
				<?php 
				if($charArr){
					?>
					<h3>Characters by Heading</h3>
					<?php 
					foreach($charArr as $headingId => $charList){
						?>
						<div>
							<a href="#" onclick="toggle('heading-<?php echo $headingId; ?>');"><?php echo $headingArr[$headingId]['name']; ?></a>
							<a href="#" onclick="toggle('headingedit-<?php echo $headingId; ?>');"><img src="../../images/edit.png" /></a>
							<div id="headingedit-<?php echo $headingId; ?>">
								<fieldset>
									<legend>Heading Editor</legend>
									<form name="headingeditform" action="headingadmin.php" method="post">
										<div style="margin:2px;">
											<input name="headingname" type="text" value="<?php echo $headingArr[$headingId]['name']; ?>" />
										</div>
										<div style="margin:2px;">
											<input name="notes" type="text" value="<?php echo $headingArr[$headingId]['notes']; ?>" />
										</div>
										<div style="margin:2px;">
											<input name="sortsequence" type="text" value="<?php echo $headingArr[$headingId]['sortsequence']; ?>" />
										</div>
										<div>
											<input name="hid" type="hidden" value="<?php echo $headingId; ?>" />
											<button name="action" type="submit" value="Save">Save Edits</button>
										</div>
									</form>
								</fieldset>
								<fieldset>
									<legend>Delete Heading</legend>
									<form name="headingdeleteform" action="headingadmin.php" method="post">
										<input name="hid" type="hidden" value="<?php echo $headingId; ?>" />
										<button name="action" type="submit" value="Delete">Delete Heading</button>
									</form>
								</fieldset>
							</div>
							<div id="heading-<?php echo $headingId; ?>" style="display:none;">
								<?php 
								foreach($charList as $cid => $charName){
									?>
									<ul>
										<li style="margin-left:10px;">
											<?php echo '<a href="chardetails.php?cid='.$cid.'" target="_blank">'.$charName.'</a>'; ?>
										</li>
									</ul>
									<?php 
								}
								?>
							</div>
						</div>
						<?php 
					}
				}
				else{
					echo '<div style="font-weight:bold;font-size:120%;">There are no existing characters</div>';
				}
				?>
			</div>
			<?php 
		}
		else{
			echo '<h2>You are not authorized to add characters</h2>';
		}
		?>
	</div>
</body>
</html>