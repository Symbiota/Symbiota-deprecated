<?php
include_once('../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceCrowdSource.php');
header("Content-Type: text/html; charset=".$charset);

$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';

$csManager = new OccurrenceCrowdSource();
if($collId) $csManager->setCollId($collId);

$statusStr = '';
$isEditor = 0; 
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
	$isEditor = 1;
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Crowdsourcing Score Board</title>
    <link type="text/css" href="../../css/main.css" rel="stylesheet" />
	<script type="text/javascript">

	</script>
</head>
<body>
	<?php 	
	$displayLeftMenu = false;
	include($serverRoot.'/header.php');
	?>
	<div class='navpath'>
		<a href="../../index.php">Home</a> &gt;&gt;
		<b>Crowdsourcing Score Board</b>
	</div>
	<!-- inner text -->
	<div id="innertext">
		<h1>Crowdsourcing Score Board</h1>
		
		<h2>Top Scores</h2>
		<table>
			<tr><td><b>User</b></td><td><b>Score</b></td></tr>
		<?php 
		$topScoreArr = $csManager->getTopScores();
		foreach($topScoreArr as $s => $u){
			echo '<tr><td>'.$u.' </td><td>'.$s.' </td></tr>';
		}
		?>
		</table>

		<h2>Current User's Status</h2>
		<table>
			<tr><td>Record</td><td>Status</td><td>Points</td><td>Notes</td><td>Edit Date</td></tr>
			<?php 
			$topScoreArr = $csManager->getTopScores();
			foreach($topScoreArr as $s => $u){
				echo '<tr><td>'.$u.' </td><td>'.$s.' </td></tr>';
			}
			
			
			
			?>
		</table>
	</div>
	<?php 	
	include($serverRoot.'/footer.php');
	?>
</body>
</html>