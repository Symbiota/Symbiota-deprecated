<?php
/*
 * This is only meant to be a general template. 
 * Code, JS, and jQuery links are only suggestions might not be needed for all pages. 
 * Version number following JS links are relative date of a JS code modification 
 * and are only meant to force browsers to refresh the code sotred in their cache  
 */

include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/GeneralClassTemplate.php');
header("Content-Type: text/html; charset=".$CHARSET);

//Use following ONLY if login is required
if(!$SYMB_UID){
	header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../misc/generaltemplate.php?'.$_SERVER['QUERY_STRING']);
}

$generalVariable = $_REQUEST['var1'];
$formVariable = $_POST['formvar'];
$optionalVariable = array_key_exists('optvar',$_REQUEST)?$_REQUEST['optvar']:'';
$collid = $_REQUEST['collid'];
$formSubmit = array_key_exists('formsubmit',$_REQUEST)?$_REQUEST['formsubmit']:'';

//Sanitation
if(!is_numeric($collid)) $collid = 0;

//General convention used in this project is to centralize data access, business rules, logic, functions, etc within one to several classes    
//class should be placed in /classes/ with the central class name matching the file name  
$classManager = new GeneralClassTemplate();

$classManager->setGeneralVariable($generalVariable);
$classManager->setNumericVariable($formVariable);

$isEditor = 0; 
if($SYMB_UID){
	if($IS_ADMIN){
		$isEditor = 1;
	}
	elseif($collid){
		//If a page related to collections, one maight want to... 
		if(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])){
			$isEditor = 1;
		}
		elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollEditor"])){
			$isEditor = 1;
		}
	}
}

if($isEditor){
	if($formSubmit == 'Save Data'){
		$classManager->saveData($_POST);
	}
	elseif($formSubmit == 'Delete Record'){
		$classManager->deleteRecord($_POST['recordid']);
	}
}

?>
<html>
	<head>
		<title>Page Title</title>
		<link href="<?php echo $CLIENT_ROOT; ?>/css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $CLIENT_ROOT; ?>/css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $CLIENT_ROOT; ?>/css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery.js" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.js" type="text/javascript"></script>
		<script type="text/javascript">

		</script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/shared.js?ver=140310" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/misc.generaltemplate.js?ver=140310" type="text/javascript"></script>
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($SERVER_ROOT.'/header.php');
		?>
		<div class="navpath">
			<a href="<?php echo $CLIENT_ROOT; ?>/index.php">Home</a> &gt;&gt; 
			<a href="othersupportpage.php">Previous Relevent Page</a> &gt;&gt; 
			<b>New Page</b>
		</div>
		<!-- This is inner text! -->
		<div id="innertext">

			Add static, dynamic and form content here.<br/>
			
		</div>
		<?php
			include($SERVER_ROOT.'/footer.php');
		?>
	</body>
</html>
