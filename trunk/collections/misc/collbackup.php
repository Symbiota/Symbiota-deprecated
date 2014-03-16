<?php
include_once('../../config/symbini.php');

$collid = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$action = array_key_exists("formsubmit",$_REQUEST)?$_REQUEST["formsubmit"]:'';
$cSet = array_key_exists("cset",$_REQUEST)?$_REQUEST["cset"]:'';

$isEditor = 0;
if($isAdmin || array_key_exists("CollAdmin",$userRights) && in_array($collid,$userRights["CollAdmin"])){
	$isEditor = 1;
}
?>
<!DOCTYPE html >
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>" />
	<title>Occurrences download</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
    <script language="javascript">
    	function submitBuForm(f){
			f.formsubmit.disabled = true;
			document.getElementById("workingdiv").style.display = "block";
			return true;
    	}
    </script>
</head>
<body>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($isEditor){
			?>
			<form name="buform" action="../download/downloadhandler.php" method="post" onsubmit="return submitBuForm(this);">
				<fieldset style="padding:15px;">
					<legend>Download Module</legend>
					<div style="float:left;">
						Data Set: 
					</div>
					<div style="float:left;">
						<?php 
						$cSet = str_replace('-','',strtolower($charset));
						?>
						<input type="radio" name="cset" value="iso-8859-1" <?php echo ($cSet=='iso88591'?'checked':''); ?> /> ISO-8859-1 (western)<br/>
						<input type="radio" name="cset" value="utf-8" <?php echo ($cSet=='utf8'?'checked':''); ?> /> UTF-8 (unicode)
					</div>
					<div id="workingdiv" style="clear:both;margin:20px;display:<?php echo ($action == 'Perform Backup'?'block':'none'); ?>;">
						<b>Downloading backup file...</b> <img src="../../images/workingcircle.gif" />
					</div>
					<div style="clear:both;">
						<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
						<input type="hidden" name="schema" value="backup" />
						<input type="submit" name="formsubmit" value="Perform Backup" />
					</div>
				</fieldset>
			</form>
			<?php 
		}
		?>
	</div>
</body>
</html>
