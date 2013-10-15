<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/IdentCharAdmin.php');

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../ident/admin/headingadmin.php?'.$_SERVER['QUERY_STRING']);

$action = array_key_exists('action',$_GET)?$_GET['action']:'';
$hid = array_key_exists('hid',$_REQUEST)?$_REQUEST['hid']:0;
$lang = array_key_exists('lang',$_REQUEST)?$_REQUEST['lang']:'';

$charManager = new IdentCharAdmin();

$headingArr = array();
if(!$hid) $headingArr = $charManager->getHeadingList();

$isEditor = false;
if($isAdmin || array_key_exists("KeyAdmin",$userRights)){
	$isEditor = true;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
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
		if($isEditor){
			?>
			<div style="float:right;margin:10px;">
				<a href="#" onclick="toggle('addheadingdiv');">
					<img src="../../images/add.png" alt="Create New Heading" />
				</a>
			</div>
			<div id="addheadingdiv" style="display:none;">
				<form name="newheadingform" action="headingadmin.php" method="post" onsubmit="return validateNewHeadingForm(this)">
					<fieldset>
						<legend><b>New Heading</b></legend>
						<div>
							Heading Name<br />
							<input type="text" name="headingname" maxlength="255" style="width:400px;" />
						</div>
						<div style="padding-top:6px;">
							<b>Language</b><br />
							<select name="langid">
								<?php 
								$langArr = $charManager->
								
								?>
							</select>
							<input type="text" name="language" />
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
							<button name="formsubmit" type="submit" value="Create">Create</button>
						</div>
					</fieldset>
				</form>
			</div>
			<div id="headinglist">
				<?php 
				if($charArr){
					?>
					<h3>Characters by Heading</h3>
					<ul>
						<?php 
						foreach($charArr as $k => $charList){
							?>
							<li>
								<a href="#" onclick="toggle('char-<?php echo $k; ?>');"><?php echo $headingArr[$k]; ?></a>
								<div id="char-<?php echo $k; ?>" style="display:block;">
									<ul>
										<?php 
										foreach($charList as $cid => $charName){
											echo '<li>';
											echo '<a href="chardetails.php?cid='.$cid.'">'.$charName.'</a>';
											echo '</li>';
										}
										?>
									</ul>
								</div>
							</li>
							<?php 
						}
						?>
					</ul>
				<?php 
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