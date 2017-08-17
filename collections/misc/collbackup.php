<?php
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collid = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$action = array_key_exists("formsubmit",$_REQUEST)?$_REQUEST["formsubmit"]:'';
$cSet = array_key_exists("cset",$_REQUEST)?$_REQUEST["cset"]:'';

$isEditor = 0;
if($IS_ADMIN || array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])){
	$isEditor = 1;
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>" />
	<title>Occurrences download</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
    <script>
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
				<fieldset style="padding:15px;width:350px">
					<legend>Download Module</legend>
					<div style="float:left;">
						Data Set: 
					</div>
					<div style="float:left;height:50px">
						<?php 
						//$cSet = str_replace('-','',strtolower($CHARSET));
						?>
						<input type="radio" name="cset" value="iso-8859-1" <?php echo (!$cSet || $cSet=='iso88591'?'checked':''); ?> /> ISO-8859-1 (western)<br/>
						<input type="radio" name="cset" value="utf-8" <?php echo ($cSet=='utf8'?'checked':''); ?> /> UTF-8 (unicode)
					</div>
					<div style="clear:both;">
						<div style="float:left">
							<input type="hidden" name="collid" value="<?php echo $collid; ?>" />
							<input type="hidden" name="schema" value="backup" />
							<input type="submit" name="formsubmit" value="Perform Backup" />
						</div>
						<div id="workingdiv" style="float:left;margin-left:15px;display:<?php echo ($action == 'Perform Backup'?'block':'none'); ?>;">
							<b>Downloading backup file...</b> 
						</div>
					</div>
				</fieldset>
			</form>
			<?php 
		}
		?>
	</div>
</body>
</html>