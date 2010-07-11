<?php
/*
 * Created on 26 Feb 2009
 * By E.E. Gilbert
*/
Header('Content-Type: text/html; charset=ISO-8859-1');
include_once("../util/symbini.php");
include_once("util/ProfileHandler.php");

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
$submit = array_key_exists("submit",$_REQUEST)?$_REQUEST["submit"]:""; 
$resetPwd = array_key_exists("resetpwd",$_REQUEST)?$_REQUEST["resetpwd"]:""; 
$login = array_key_exists("login",$_REQUEST)?$_REQUEST["login"]:""; 
$remMe = array_key_exists("remember",$_REQUEST)?$_REQUEST["remember"]:"";
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

$pHandler = new ProfileHandler();
if($remMe) $pHandler->setRememberMe(true);

$statusStr = "";
if($submit == "logout"){
    $pHandler->reset();
}
elseif($action == "Login"){
	$password = $_REQUEST["password"]; 
	$statusStr = $pHandler->authenticate($login, $password);
    if($statusStr == "success"){
    	if($refUrl){
			header("Location: ".$refUrl);
        }
        else{
			header("Location: ../index.php");
        }
    }
}
elseif($action == "Search for Login"){
	$emailAddr = $_REQUEST["emailaddr"];
	if($emailAddr){
		$returnStr = $pHandler->lookupLogin($emailAddr);
		if($returnStr){
			$login = $returnStr;
			$statusStr = "Your login is: ".$login;
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
	<link href="../css/main.css" rel="stylesheet" type="text/css"/>
	<script type="text/javascript">
		function resetPassword(){
			if(document.getElementById("login").value == ""){
				alert("Enter your login name in the User Id field and leave the password blank");
				return false;
			}
			document.getElementById("resetpwd").value = "1";
			document.forms["loginform"].submit();
		}
	</script>
</head>
<body>

<?php
$displayLeftMenu = (isset($profile_indexMenu)?$profile_indexMenu:"true");
include($serverRoot."/util/header.php");
if(isset($profile_indexCrumbs)){
	echo "<div class='navpath'>";
	echo "<a href='../index.php'>Home</a> &gt; ";
	echo $profile_indexCrumbs;
	echo " <b>Create New Profile</b>";
	echo "</div>";
}
?>
<!-- inner text -->
<div id="innertext">
	<h1 style="margin: 1em 0em 0em 0em;">Please Login</h1>
	
	<?php
	if($statusStr){
		echo "<div style='color:#FF0000;margin: 1em 1em 0em 1em;'>";
		if($statusStr == "badUserId"){
		   	echo "We do not have a record of your User ID in the database.";
		}
		elseif($statusStr == "badPassword"){
			echo "Your password was incorrect. Please try again.<br />";
		    echo "Would you like to <a href='index.php?resetpwd=1&login=".$login."'>reset your password?</a>";
		}
		else{
		    echo $statusStr;
		}
		echo "</div>";
	}
	?>
	
	<form id="loginform" name="loginform" action="index.php" method="post">
	  	<fieldset style='margin:20px;width:300px;background-color:#FFFFCC;border:2px outset #E8EEFA;'>
			<div style="margin: 1em 1em 0em 2em;font-weight:bold;">
				Login:&nbsp;&nbsp;&nbsp;<input id="login" name="login" value="<?php echo $login; ?>" style="border-style:inset;" />
			</div>
			
			<div style="margin: 1em 1em 0em 2em;font-weight:bold;">
			    Password:&nbsp;&nbsp;<input type="password" name="password"  style="border-style:inset;"/>
			</div>
			
			<div style="margin: 1em 1em 0em 2em">
			    <input type="checkbox" value='1' name="remember" >
			    Remember me on this computer
			</div>
			
			<div style="margin: 1em 1em 0em 2em;text-align:right;">
				<?php 
					if($refUrl) echo "<input type='hidden' name='refurl' value='".$refUrl."'/>";
				?>
				<input type="submit" value="Login" name="action">
			</div>
	  	</fieldset>
		<div style="width:300px;text-align:center;margin:20px;">
			<div style="font-weight:bold;">
				Don't have an Account?
			</div>
			<div style="">
				<a href="newprofile.php">Create an account now</a>
			</div>
			<div style="font-weight:bold;margin-top:5px">
				Can't remember your password?
			</div>
			<div style="color:blue;cursor:pointer;" onclick="resetPassword()">Reset Password</div>
			<input type="hidden" id="resetpwd" name="resetpwd" value="">
			<div style="font-weight:bold;margin-top:5px">
				Can't Remember Login Name?
			</div>
			<div style="color:blue;cursor:pointer;" onclick="document.getElementById('emaildiv').style.display = 'block'">
				Lookup Login Name
			</div>
			<div class="fieldset" id="emaildiv" style="display:none;margin:5px;">
				<div>Your Email? <input type="text" name="emailaddr" /></div>
				<div><input type="submit" name="action" value="Search for Login"/></div>
			</div>
		</div>
	</form>
</div>

<?php
	include($serverRoot."/util/footer.php");
?>

</body>
</html>	