<?php
include_once('../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceCrowdSource.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';

$csManager = new OccurrenceCrowdSource();

$statusStr = '';
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
		<?php 
		$userStats = $csManager->getUserStats($symbUid);
		?>
		<div>
			<b>Specimen Count:</b> <?php echo $userStats['totalcnt']; ?>
			<b>Approved points:</b> <?php echo $userStats['apoints']; ?>
			<b>Pending points:</b> <a href="crowdsourcestatus.php"><?php echo $userStats['ppoints']; ?></a>
			<b>Approved and pending:</b> <?php echo $userStats['ppoints']+$userStats['apoints']; ?>
		</div>
		<div>
			<table>
				<tr>
					<td><b>Collection</b></td>
					<td><b>Specimen<br/>Count</b></td>
					<td><b>Approved<br/>Points</b></td>
					<td><b>Pending<br/>Points</b></td>
				</tr>
				<?php 
				unset($userStats['totalcnt']);
				unset($userStats['astat']);
				unset($userStats['pstat']);
				foreach($userStats as $collName => $sArr){
					$pArr = $sArr['points'];
					$cArr = $sArr['cnt'];
					echo '<tr>';
					echo '<td><b>'.$collName.'</b></td>';
					echo '<td>'.($cArr[5]+$cArr[10]).'</td>';
					echo '<td>'.$pArr[10].'</td>';
					echo '<td>'.$pArr[5].'</td>';
					echo '</tr>';
				}
				?>
			</table>
		</div>
	</div>
	<?php 	
	include($serverRoot.'/footer.php');
	?>
</body>
</html>