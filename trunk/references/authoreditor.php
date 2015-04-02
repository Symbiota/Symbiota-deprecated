<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ReferenceManager.php');

$refId = array_key_exists('refid',$_REQUEST)?$_REQUEST['refid']:0;
$authId = array_key_exists('authid',$_REQUEST)?$_REQUEST['authid']:0;
$addAuth = array_key_exists('addauth',$_REQUEST)?$_REQUEST['addauth']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$refManager = new ReferenceManager();
$authArr = '';
$authExist = false;

$statusStr = '';
if($formSubmit){
	if($formSubmit == 'Add Author'){
		$refManager->createAuthor($_POST['firstname'],$_POST['middlename'],$_POST['lastname']);
		$authId = $refManager->getRefAuthId();
	}
	if($formSubmit == 'Edit Author'){
		$statusStr = $refManager->editAuthor($_POST);
	}
	if($formSubmit == 'Delete Author'){
		$statusStr = $refManager->deleteAuthor($authId);
		$authId = 0;
	}
}

if(!$addAuth){
	if($authId){
		$authInfoArr = $refManager->getAuthInfo($authId);
		$authPubArr = $refManager->getAuthPubList($authId);
	}
	else{
		$authArr = $refManager->getAuthList();
		foreach($authArr as $authName => $valueArr){
			if($valueArr["authorName"]){
				$authExist = true;
			}
		}
	}
}

header("Content-Type: text/html; charset=".$charset);
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
	<title><?php echo $defaultTitle; ?> Author Management</title>
    <link href="../css/base.css?<?php echo $CSS_VERSION; ?>" rel="stylesheet" type="text/css" />
    <link href="../css/main.css?<?php echo $CSS_VERSION; ?>" rel="stylesheet" type="text/css" />
	<link href="../css/jquery-ui.css" rel="stylesheet" type="text/css" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../js/symb/references.index.js"></script>
	<script type="text/javascript">
		var refid = <?php echo $refId; ?>;
	</script>
</head>
<body <?php echo ($addAuth?'style="width:400px;"':'') ?>>
	<?php
	if(!$addAuth){
		$displayLeftMenu = (isset($reference_indexMenu)?$reference_indexMenu:false);
		include($serverRoot."/header.php");
		if(isset($reference_indexCrumbs)){
			if($reference_indexCrumbs){
				?>
				<div class='navpath'>
					<a href='../index.php'>Home</a> &gt;&gt; 
					<?php echo $reference_indexCrumbs; ?>
					<a href='authoreditor.php'> <b>Author Management</b></a>
				</div>
				<?php 
			}
		}
		else{
			?>
			<div class='navpath'>
				<a href='../index.php'>Home</a> &gt;&gt; 
				<a href='authoreditor.php'> <b>Author Management</b></a>
			</div>
			<?php 
		}
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($symbUid){
			if($statusStr){
				?>
				<div style="margin:15px;color:red;">
					<?php echo $statusStr; ?>
				</div>
				<?php 
			}
			?>
			<div id="authlistdiv" style="min-height:200px;">
				<?php
				if(!$addAuth){
					?>
					<div style="float:right;margin:10px;">
						<a href="#" onclick="toggle('newauthordiv');">
							<img src="../images/add.png" alt="Create New Author" />
						</a>
					</div>
					<?php
				}
				if(!$authId){
					?>
					<div id="newauthordiv" style="<?php echo ($addAuth?'display:block;width:400px;':'display:none;') ?>">
						<form name="newauthorform" action="<?php echo ($addAuth?'':'authoreditor.php') ?>" method="post" onsubmit="return verifyNewAuthForm(this.form);">
							<fieldset>
								<legend><b>New Author</b></legend>
								<div style="clear:both;padding-top:4px;float:left;">
									<div style="">
										<b>First Name: </b> <input type="text" name="firstname" id="firstname" tabindex="100" maxlength="32" style="width:200px;" value="" onchange="" title="" />
									</div>
								</div>
								<div style="clear:both;padding-top:4px;float:left;">
									<div style="">
										<b>Middle Name: </b> <input type="text" name="middlename" id="middlename" tabindex="100" maxlength="32" style="width:200px;" value="" onchange="" title="" />
									</div>
								</div>
								<div style="clear:both;padding-top:4px;float:left;">
									<div style="">
										<b>Last Name: </b> <input type="text" name="lastname" id="lastname" tabindex="100" maxlength="32" style="width:200px;" value="" onchange="" title="" />
									</div>
								</div>
								<div style="clear:both;padding-top:8px;float:right;">
									<button name="formsubmit" type="<?php echo ($addAuth?'button':'submit') ?>" value="Add Author" onclick='<?php echo ($addAuth?'processNewAuthor(this.form);':'') ?>' >Add Author</button>
								</div>
							</fieldset>
						</form>
					</div>
					<?php
					if(!$addAuth){
						if($authExist){
							echo '<div style="font-weight:bold;font-size:120%;">Authors</div>';
							echo '<div><ul>';
							foreach($authArr as $authId => $recArr){
								echo '<li>';
								echo '<a href="authoreditor.php?authid='.$authId.'"><b>'.$recArr["authorName"].'</b></a>';
								echo '</li>';
							}
							echo '</ul></div>';
						}
						else{
							echo '<div style="margin-top:10px;"><div style="font-weight:bold;font-size:120%;">There are currently no authors in the database.</div></div>';
						}
					}
				}
				else{
					?>
					<div id="tabs" style="margin:0px;">
						<ul>
							<li><a href="#authdetaildiv">Author Details</a></li>
							<li><a href="#authlinksdiv">Publications</a></li>
							<li><a href="#authadmindiv">Admin</a></li>
						</ul>
						
						<div id="authdetaildiv" style="">
							<div id="authdetails" style="overflow:auto;">
								<form name="authoreditform" id="authoreditform" action="authoreditor.php" method="post" onsubmit="return verifyNewAuthForm(this.form);">
									<div style="clear:both;padding-top:4px;float:left;">
										<div style="">
											<b>First Name: </b> <input type="text" name="firstname" id="firstname" maxlength="32" style="width:200px;" value="<?php echo $authInfoArr['firstname']; ?>" title="" />
										</div>
									</div>
									<div style="clear:both;padding-top:4px;float:left;">
										<div style="">
											<b>Middle Name: </b> <input type="text" name="middlename" id="middlename" maxlength="32" style="width:200px;" value="<?php echo $authInfoArr['middlename']; ?>" title="" />
										</div>
									</div>
									<div style="clear:both;padding-top:4px;float:left;">
										<div style="">
											<b>Last Name: </b> <input type="text" name="lastname" id="lastname" maxlength="32" style="width:200px;" value="<?php echo $authInfoArr['lastname']; ?>" title="" />
										</div>
									</div>
									<div style="clear:both;padding-top:8px;float:right;">
										<input name="authid" type="hidden" value="<?php echo $authId; ?>" />
										<button name="formsubmit" type="submit" value="Edit Author">Save Edits</button>
									</div>
								</form>
							</div>
						</div>
						
						<div id="authlinksdiv" style="">
							<div style="width:600px;">
								<?php
								if($authPubArr){
									echo '<div style="font-weight:bold;font-size:120%;">Publications</div>';
									echo '<div><ul>';
									foreach($authPubArr as $refId => $recArr){
										echo '<li>';
										echo '<a href="refdetails.php?refid='.$refId.'" target="_blank"><b>'.$recArr["title"].'</b></a>';
										echo ($recArr["secondarytitle"]?', '.$recArr["secondarytitle"].'.':'');
										echo ($recArr["shorttitle"]?', '.$recArr["shorttitle"].'.':'');
										echo ($recArr["pubdate"]?$recArr["pubdate"].'.':'');
										echo '</li>';
									}
									echo '</ul></div>';
								}
								else{
									echo '<h2>There are no publications linked with this author</h2>';
								}
								?>
							</div>
						</div>
						
						<div id="authadmindiv" style="">
							<form name="delauthform" action="authoreditor.php" method="post" onsubmit="return confirm('Are you sure you want to permanently delete this author?')">
								<fieldset style="width:350px;margin:20px;padding:20px;">
									<legend><b>Delete Author</b></legend>
									<?php 
									if($authPubArr){
										echo '<div style="font-weight:bold;margin-bottom:15px;">';
										echo 'Author cannot be deleted until all linked publications are removed';
										echo '</div>';
									}
									?>
									<input name="formsubmit" type="submit" value="Delete Author" <?php if($authPubArr) echo 'DISABLED'; ?> />
									<input name="authid" type="hidden" value="<?php echo $authId; ?>" />
								</fieldset>
							</form>
						</div>
					</div>
					<?php
				}
				?>
			</div>
			<?php 
		}
		else{
			if(!$symbUid){
				echo 'Please <a href="../profile/index.php?refurl=../references/authoreditor.php">login</a>';
			}
			else{
				echo '<h2>ERROR: unknown error, please contact system administrator</h2>';
			}
		}
		?>
	</div>
	<?php
	if(!$addAuth){
		include($serverRoot."/footer.php");
	}
	?>
</body>
</html>