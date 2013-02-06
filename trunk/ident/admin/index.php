<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/KeyCharAdmin.php');

$keyManager = new KeyAdmin();
//$keyManager->setCollId($collId);

$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';
$cid = array_key_exists('cid',$_REQUEST)?$_REQUEST['cid']:0;
$tabIndex = array_key_exists('tabindex',$_REQUEST)?$_REQUEST['tabindex']:0;

if($cid) $keyManager->setCid($cid);

$statusStr = '';
if($formSubmit){
	if($formSubmit == 'Create'){
		$statusStr = $keyManager->createCharacter($_POST,$paramsArr['un']);
		$cid = $keyManager->getcId();
	}
	elseif($formSubmit == 'Save Char'){
		$statusStr = $keyManager->editCharacter($_POST);
	}
	elseif($formSubmit == 'Add State'){
		$keyManager->createState($_POST['charstatename'],$paramsArr['un']);
		$tabIndex = 2;
	}
	elseif($formSubmit == 'Save State'){
		$statusStr = $keyManager->editCharState($_POST);
		$tabIndex = 2;
	}
	elseif($formSubmit == 'Delete Char'){
		$status = $keyManager->deleteChar();
		if($status) $cid = 0;
	}
	elseif($formSubmit == 'Delete State'){
		$status = $keyManager->deleteCharState($_POST['cs']);
		$tabIndex = 2;
	}
}
 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
	<title>Character Admin</title>
    <link type="text/css" href="../../css/main.css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />	
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script type="text/javascript">
		var tabIndex = <?php echo $tabIndex; ?>;
	</script>
	<script type="text/javascript" src="../../js/symb/ident.admin.js"></script>
</head>
<body>
<?php
$displayLeftMenu = (isset($ident_admin_indexMenu)?$ident_admin_indexMenu:true);
include($serverRoot."/header.php");
if(isset($collections_loans_indexCrumbs)){
	if($collections_loans_indexCrumbs){
		?>
		<div class='navpath'>
			<a href='../../index.php'>Home</a> &gt;&gt; 
			<?php echo $ident_admin_indexCrumbs; ?>
			<a href='index.php'> <b>Character Management</b></a>
		</div>
		<?php 
	}
}
else{
	?>
	<div class='navpath'>
		<a href='../../index.php'>Home</a> &gt;&gt; 
		<a href='index.php'> <b>Character Management</b></a>
	</div>
	<?php 
}
?>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($symbUid){
			if($statusStr){
				?>
				<hr/>
				<div style="margin:15px;color:red;">
					<?php echo $statusStr; ?>
				</div>
				<hr/>
				<?php 
			}
			if(!$cid){
				include_once('charadmin.php');
			}
			elseif($cid){
				include_once('chardetails.php');
			}
		}
		else{
			if(!$symbUid){
				echo 'Please <a href="../../profile/index.php?refurl=../ident/admin/index.php">login</a>';
			}
			elseif(!$isEditor){
				echo '<h2>You are not authorized to add characters</h2>';
			}
			else{
				echo '<h2>ERROR: unknown error, please contact system administrator</h2>';
			}
		}
		?>
	</div>
	<?php 
	include($serverRoot.'/footer.php');
	?>
</body>
</html>

