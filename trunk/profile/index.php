<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ProfileManager.php');
header("Content-Type: text/html; charset=".$charset);
header('Cache-Control: no-cache, no-cache="set-cookie", no-store, must-revalidate');
header('Pragma: no-cache'); // HTTP 1.0.
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$login = array_key_exists('login',$_REQUEST)?htmlspecialchars($_REQUEST['login']):'';
$remMe = array_key_exists("remember",$_POST)?htmlspecialchars($_POST["remember"]):"";
$emailAddr = array_key_exists('emailaddr',$_POST)?htmlspecialchars($_POST['emailaddr']):'';
$resetPwd = ((array_key_exists("resetpwd",$_REQUEST) && is_numeric($_REQUEST["resetpwd"]))?$_REQUEST["resetpwd"]:0);
$action = array_key_exists("action",$_POST)?htmlspecialchars($_POST["action"]):"";
if(!$action && array_key_exists('submit',$_REQUEST)) $action = $_REQUEST['submit'];

$refUrl = "";
if(array_key_exists("refurl",$_REQUEST)){
	$refGetStr = "";
	foreach($_GET as $k => $v){
		if($k != "refurl"){
			if($k == "attr" && is_array($v)){
				foreach($v as $v2){
					$refGetStr .= "&attr[]=".$v2;
				}
			}
			else{
				$refGetStr .= "&".$k."=".$v;
			}
		}
	}
	$refUrl = htmlspecialchars($_REQUEST["refurl"]);
	if(substr($refUrl,-4) == ".php"){
		$refUrl .= "?".substr($refGetStr,1);
	}
	else{
		$refUrl .= $refGetStr;
	}
}

$pHandler = new ProfileManager();

$statusStr = "";

//Sanitation
if($login){
	if(!$pHandler->setUserName($login)){
		$login = '';
		$statusStr = 'Invalid login name';
	}
}
if($emailAddr){
	if(!$pHandler->validateEmailAddress($emailAddr)){
		$emailAddr = '';
		$statusStr = 'Invalid email';
	}
}
if(!is_numeric($resetPwd)) $resetPwd = 0;
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';

if($remMe) $pHandler->setRememberMe(true);

if($action == "logout"){
	$pHandler->reset();
	header("Location: ../index.php");
}
elseif($action == "Login"){
	$password = trim($_POST["password"]);
	if($pHandler->authenticate($password)){
		if($refUrl && (stripos($refUrl,$clientRoot) !== false) && !strpos($refUrl,'newprofile.php')){
			header("Location: ".$refUrl);
		}
		else{
			header("Location: ../index.php");
		}
	}
	else{
		$statusStr = 'Your username or password was incorrect. Please try again.<br/> If you are unable to remember your login credentials,<br/> use the controls below to retrieve your login or reset your password.';
	}
}
elseif($action == "Retrieve Login"){
	if($emailAddr){
		if($pHandler->lookupUserName($emailAddr)){
			$statusStr = "Your login name will be emailed to you.";
		}
		else{
			$statusStr = $pHandler->getErrorStr();
		}
	}
}
elseif($resetPwd){
	$statusStr = $pHandler->resetPassword($login);
}
else{
	$statusStr = $pHandler->getErrorStr();
}
?>

<html>
<head>
	<title><?php echo $defaultTitle; ?> Login</title>
	<meta http-equiv="X-Frame-Options" content="deny">
	<link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
		if(!navigator.cookieEnabled){
			alert("Your browser cookies are disabled. To be able to login and access your profile, they must be enabled for this domain.");
		}
	
		function resetPassword(){
			if(document.getElementById("login").value == ""){
				alert("Enter your login name in the Login field and leave the password blank");
				return false;
			}
			document.getElementById("resetpwd").value = "1";
			document.forms["loginform"].submit();
		}
	</script>
	<script src="../js/symb/shared.js" type="text/javascript"></script>
</head>
<body>

<?php
$displayLeftMenu = (isset($profile_indexMenu)?$profile_indexMenu:"true");
include($serverRoot.'/header.php');
if(isset($profile_indexCrumbs)){
	echo "<div class='navpath'>";
	echo $profile_indexCrumbs;
	echo " <b>Create New Profile</b>";
	echo "</div>";
}
?>
<!-- inner text -->
<div id="innertext" style="padding-left:0px;margin-left:0px;">
	
	<?php
	if($statusStr){
		?>
		<div style='color:#FF0000;margin: 1em 1em 0em 1em;'>
			<?php 
			echo $statusStr;
			?>
		</div>
		<?php 
	}
	?>
	
	<div style="width:300px;margin-right:auto;margin-left:auto;">
		<form id="loginform" name="loginform" action="index.php" method="post">
			<fieldset style='padding:25px;margin:20px;width:300px;background-color:#FFFFCC;border:2px outset #E8EEFA;'>
				<div style="margin: 10px;font-weight:bold;">
					Login:&nbsp;&nbsp;&nbsp;<input id="login" name="login" value="<?php echo $login; ?>" style="border-style:inset;" />
				</div>
				
				<div style="margin:10px;font-weight:bold;">
					Password:&nbsp;&nbsp;<input type="password" name="password"  style="border-style:inset;" autocomplete="off" />
				</div>
				
				<div style="margin:10px">
					<input type="checkbox" value='1' name="remember" >
					Remember me on this computer
				</div>
				
				<div style="margin:20px 0px 10px 140px;">
					<input type="hidden" name="refurl" value="<?php echo $refUrl; ?>" />
					<input type="submit" value="Login" name="action">
				</div>
			</fieldset>
			<div style="width:300px;text-align:center;margin:20px;">
				<div style="font-weight:bold;">
					Don't have an Account?
				</div>
				<div style="">
					<a href="newprofile.php?refurl=<?php echo $refUrl; ?>">Create an account now</a>
				</div>
				<div style="font-weight:bold;margin-top:5px">
					Can't remember your password?
				</div>
				<div style="color:blue;cursor:pointer;" onclick="resetPassword()">Reset Password</div>
				<input type="hidden" id="resetpwd" name="resetpwd" value="">
				<div style="font-weight:bold;margin-top:5px">
					Can't Remember Login Name?
				</div>
				<div>
					<a href="#" onclick="toggle('emaildiv')">Retrieve Login</a>
					<div id="emaildiv" style="display:none;margin:10px 0px 10px 40px;">
						<fieldset style="padding:10px;">
							<div>Your Email: <input type="text" name="emailaddr" /></div>
							<div><input type="submit" name="action" value="Retrieve Login"/></div>
						</fieldset>
					</div>
				</div>
			</div>
		</form>
	</div>
</div>
<?php include($serverRoot.'/footer.php'); ?>
</body>
</html>	