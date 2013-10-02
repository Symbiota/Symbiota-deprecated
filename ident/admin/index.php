<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/IdentCharAdmin.php');

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../ident/admin/index.php?'.$_SERVER['QUERY_STRING']);

$charManager = new IdentCharAdmin();

$charArr = $charManager->getCharacterArr();
$headingArr = array();
if(isset($charArr['head'])){
	$headingArr = $charArr['head'];
	unset($charArr['head']);
}

$isEditor = false;
if($isAdmin || array_key_exists("KeyAdmin",$userRights)){
	$isEditor = true;
}

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
	<title>Character Admin</title>
    <link type="text/css" href="../../css/main.css" rel="stylesheet" />
	<script type="text/javascript">
		function validateNewCharForm(f){

			return true;
		}

		function toggle(target){
			var objDiv = document.getElementById(target);
			if(objDiv){
				if(objDiv.style.display=="none"){
					objDiv.style.display = "block";
				}
				else{
					objDiv.style.display = "none";
				}
			}
			else{
			  	var divs = document.getElementsByTagName("div");
			  	for (var h = 0; h < divs.length; h++) {
			  	var divObj = divs[h];
					if(divObj.className == target){
						if(divObj.style.display=="none"){
							divObj.style.display="block";
						}
					 	else {
					 		divObj.style.display="none";
					 	}
					}
				}
			}
		}
	</script>
	<style type="text/css">
		input{ autocomplete: off; } 
	</style>
</head>
<body>
<?php
$displayLeftMenu = (isset($ident_admin_indexMenu)?$ident_admin_indexMenu:true);
include($serverRoot."/header.php");
if(isset($collections_loans_indexCrumbs)){
	if($collections_loans_indexCrumbs){
		?>
		<div class='navpath'>
			<?php echo $ident_admin_indexCrumbs; ?>
			<b>Character Management</b>
		</div>
		<?php 
	}
}
else{
	?>
	<div class='navpath'>
		<a href='../../index.php'>Home</a> &gt;&gt; 
		<b>Character Management</b>
	</div>
	<?php 
}
?>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($isEditor){
			?>
			<div id="addeditchar">
				<div style="float:right;margin:10px;">
					<a href="#" onclick="toggle('addchardiv');">
						<img src="../../images/add.png" alt="Create New Character" />
					</a>
				</div>
				<div id="addchardiv" style="display:none;">
					<form name="newcharform" action="chardetails.php" method="post" onsubmit="return validateNewCharForm(this)">
						<fieldset>
							<legend><b>New Character</b></legend>
							<div>
								<span>
									Character Name:
								</span><br />
								<span>
									<input type="text" name="charname" maxlength="255" style="width:400px;" value="" />
								</span>
							</div>
							<div style="padding-top:6px;float:left;">
								<div style="float:left;">
									<span>
										Type:
									</span><br />
									<span>
										<select name="chartype" style="width:180px;">
											<option value="">------------------------</option>
											<option value="UM">Unordered Multi-state</option>
											<option value="IN">Integer</option>
											<option value="RN">Real Number</option>
										</select>
									</span>
								</div>
								<div style="margin-left:30px;float:left;">
									<span>
										Difficulty:
									</span><br />
									<span>
										<select name="difficultyrank" style="width:100px;">
											<option value="">---------------</option>
											<option value="1">Easy</option>
											<option value="2">Intermediate</option>
											<option value="3">Advanced</option>
											<option value="4">Hidden</option>
										</select>
									</span>
								</div>
								<div style="margin-left:30px;float:left;">
									<span>
										Heading:
									</span><br />
									<span>
										<select name="hid" style="width:125px;">
											<option value="">No Heading</option>
											<option value="">---------------------</option>
											<?php 
											foreach($headingArr as $k => $v){
												echo '<option value="'.$k.'">'.$v.'</option>';
											}
											?>
										</select>
									</span>
								</div>
							</div>
							<div style="width:100%;padding-top:6px;float:left;">
								<button name="formsubmit" type="submit" value="Create">Create</button>
							</div>
						</fieldset>
					</form>
				</div>
				<div id="charlist">
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
			</div>
			<?php 
		}
		else{
			echo '<h2>You are not authorized to add characters</h2>';
		}
		?>
	</div>
	<?php 
	include($serverRoot.'/footer.php');
	?>
</body>
</html>