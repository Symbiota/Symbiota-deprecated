<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceDownloadManager.php');

$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$action = array_key_exists("formsubmit",$_REQUEST)?$_REQUEST["formsubmit"]:'';
$cSet = array_key_exists("cset",$_REQUEST)?$_REQUEST["cset"]:'';

$dlManager = new OccurrenceDownloadManager();

$editable = 0;

if($isAdmin || array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])){
	$editable = 1;
}
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title>Occurrences download</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
</head>
<body>
<!-- This is inner text! -->
<div id="innertext">
	<?php 
	if($editable){
		if($action == 'Perform Backup'){
			echo '<ul>';
			$dlFile = $dlManager->dlCollectionBackup($collId,$cSet);
			if($dlFile){
				echo '<li style="font-weight:bold;">Backup Complete!</li>';
				echo '<li style="font-weight:bold;">Click on file to download: <a href="'.$dlFile.'">'.$dlFile.'</a></li>';
				echo '</ul>';
			}
			echo '</ul>';
		}
		else{
			?>
			<form name="buform" action="collbackup.php" method="post">
				<fieldset style="padding:15px;">
					<legend>Download Module</legend>
					<div style="float:left;">
						Data Set: 
					</div>
					<div style="float:left;">
						<?php 
						$cSet = str_replace('-','',strtolower($charset));
						?>
						<input type="radio" name="cset" value="latin1" <?php echo ($cSet=='iso88591'?'checked':''); ?> /> ISO-8859-1 (western)<br/>
						<input type="radio" name="cset" value="utf8" <?php echo ($cSet=='utf8'?'checked':''); ?> /> UTF-8 (unicode)
					</div>
					<div style="clear:both;">
						<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
						<input type="submit" name="formsubmit" value="Perform Backup" />
					</div>
				</fieldset>
			</form>
			<?php 
		}
	}
	?>
</div>
</body>
</html>
