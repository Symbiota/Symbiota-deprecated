<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ProfileManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("action",$_POST)?$_POST["action"]:""; 
$submit = array_key_exists("submit",$_REQUEST)?$_REQUEST["submit"]:"";
$resetPwd = array_key_exists("resetpwd",$_REQUEST)?$_REQUEST["resetpwd"]:"";
$login = array_key_exists("login",$_REQUEST)?trim($_REQUEST["login"]):"";
$remMe = array_key_exists("remember",$_POST)?$_POST["remember"]:"";
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
	$refUrl = $_REQUEST["refurl"];
	if(substr($refUrl,-4) == ".php"){
		$refUrl .= "?".substr($refGetStr,1);
	}
	else{
		$refUrl .= $refGetStr;
	}
}

$pHandler = new ProfileManager();
if($remMe) $pHandler->setRememberMe(true);

$statusStr = "";
if($submit == "logout"){
	$pHandler->reset();
	header("Location: ../index.php");
}
elseif($action == "Login"){
	$password = trim($_REQUEST["password"]);
	if(!$password) $password = "emptypwd"; 
	$statusStr = $pHandler->authenticate($login, $password);
    if($statusStr == "success"){
    	if($refUrl && !strpos($refUrl,'newprofile.php')){
			header("Location: ".$refUrl);
        }
        else{
			header("Location: ../index.php");
        }
    }
}
elseif($action == "Retrieve Login"){
	$emailAddr = $_REQUEST["emailaddr"];
	if($emailAddr){
		if($pHandler->lookupLogin($emailAddr)){
			$statusStr = "Your login name will be emailed to you.";
		}
		else{
			$statusStr = "There are no users registered to email address: ".$emailAddr;
		}
	}
}
elseif($resetPwd){
	$statusStr = $pHandler->resetPassword($login);
}
else{
	$statusStr = $action;
}

?>

<html>
<head>
	<title><?php echo $defaultTitle; ?> Login</title>
	<link href="../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../css/main.css" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
		function init(){
			if(!navigator.cookieEnabled){
				alert("Your browser cookies are disabled. To be able to login and access your profile, they must be enabled for this domain.");
			}
		}
	
		function resetPassword(){
			if(document.getElementById("login").value == ""){
				alert("Enter your login name in the User Id field and leave the password blank");
				return false;
			}
			document.getElementById("resetpwd").value = "1";
			document.forms["loginform"].submit();
		}
	</script>
	<script src="../js/symb/shared.js" type="text/javascript"></script>
</head>
<body onload="init()">

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
<div id="innertext">
	
	<?php
	if($statusStr){
		?>
		<div style='color:#FF0000;margin: 1em 1em 0em 1em;'>
			<?php 
			if($statusStr == "badUserId"){
				echo "Your username or password was incorrect. Please try again.<br />";
				//echo "We do not have a record of your User ID in the database.";
			}
			elseif($statusStr == "badPassword"){
				echo "Your username or password was incorrect. Please try again.<br />";
			    echo "Click <a href='index.php?resetpwd=1&login=".$login."'><b>here</b></a> to reset your password.";
			}
			else{
			    echo $statusStr;
			}
			?>
		</div>
		<?php 
	}
	?>
	
	<form id="loginform" name="loginform" action="index.php" method="post">
	  	<fieldset style='padding:25px;margin:20px;width:300px;background-color:#FFFFCC;border:2px outset #E8EEFA;'>
			<div style="margin: 10px;font-weight:bold;">
				Login:&nbsp;&nbsp;&nbsp;<input id="login" name="login" value="<?php echo $login; ?>" style="border-style:inset;" />
			</div>
			
			<div style="margin:10px;font-weight:bold;">
			    Password:&nbsp;&nbsp;<input type="password" name="password"  style="border-style:inset;"/>
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
<?php include($serverRoot.'/footer.php'); ?>
</body>
</html>	