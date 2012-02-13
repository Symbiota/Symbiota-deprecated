<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceDownloadManager.php');

$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;

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
	<ul>
	<?php 
		if($editable){
			$dlFile = $dlManager->dlCollectionBackup($collId);
			if($dlFile){
				echo '<li style="font-weight:bold;">Backup Complete!</li>';
				echo '<li style="font-weight:bold;">Click on file to download: <a href="'.$dlFile.'">'.$dlFile.'</a></li>';
			}
		}
		?>
	</ul>
</div>
</body>
</html>
