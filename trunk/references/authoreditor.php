<?php
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);

$refId = array_key_exists('refid',$_REQUEST)?$_REQUEST['refid']:0; 
?>
<!DOCTYPE html >
<html>
<head>
	<title>Collections Search Download</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
	<link href="../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../css/main.css" type="text/css" rel="stylesheet" />
	<link href="../css/jquery-ui.css" type="text/css" rel="stylesheet" />
	<script src="../js/jquery.js" type="text/javascript"></script>
	<script src="../js/jquery-ui.js" type="text/javascript"></script>
	<script type="text/javascript" src="../js/symb/references.index.js"></script>
	<script type="text/javascript">
		var refid = <?php echo $refId; ?>;
	</script>
</head>
<body  style="width:450px;">
	<div id="innertext">
		<div style='padding:8px;'>
			<form name="newauthorform" action="refdetails.php" method="post" onsubmit="return verifyNewRefForm(this.form);">
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
						<button name="formsubmit" type="button" onclick='processNewAuthor(this.form);' >Add Author</button>
					</div>
				</fieldset>
			</form>
		</div>
	</div>
</body>

</html>
