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
		
		<div style="margin:20px;float:left;">
			<h2>Top Scores</h2>
			<table class="styledtable" style="width:300px;">
				<tr><th><b>User</b></th><th><b>Score</b></th></tr>
			<?php 
			$topScoreArr = $csManager->getTopScores();
			if($topScoreArr){
				foreach($topScoreArr as $s => $u){
					echo '<tr><td>'.$u.' </td><td>'.$s.' </td></tr>';
				}
			}
			else{
				echo '<tr><td>Top scores not yet available</td><td>------</td></tr>';
			}
			?>
			</table>
		</div>

		<div style="margin:20px;float:left;">
			<h2>Current User's Status</h2>
			<?php 
			$userStats = $csManager->getUserStats($symbUid);
			?>
			<fieldset style="margin-bottom:30px;width:250px;padding:15px;">
				<legend><b>Current Standing</b></legend>
				<b>Specimens processed:</b> <?php echo $userStats['totalcnt']; ?><br/>
				<b>Approved points:</b> <?php echo $userStats['apoints']; ?><br/>
				<b>Pending points:</b> <a href="crowdsourcestatus.php"><?php echo $userStats['ppoints']; ?></a><br/>
				<b>Approved and pending:</b> <?php echo $userStats['ppoints']+$userStats['apoints']; ?><br/>
			</fieldset>
		</div>
		<div style="margin:20px;clear:both;">
			<h2>Collections</h2>
			<table class="styledtable">
				<tr>
					<th><b>Collection</b></th>
					<th><b>Specimen<br/>Count</b></th>
					<th><b>Approved<br/>Points</b></th>
					<th><b>Pending<br/>Points</b></th>
					<th><b>Open<br/>Records</b></th>
				</tr>
				<?php 
				unset($userStats['totalcnt']);
				unset($userStats['apoints']);
				unset($userStats['ppoints']);
				foreach($userStats as $collId => $sArr){
					$pArr = $sArr['points'];
					$cArr = $sArr['cnt'];
					echo '<tr>';
					echo '<td><b>'.$sArr['name'].'</b></td>';
					echo '<td>'.((array_key_exists(5,$cArr)?$cArr[5]:0)+(array_key_exists(10,$cArr)?$cArr[10]:0)).'</td>';
					echo '<td>'.(array_key_exists(10,$pArr)?$pArr[10]:0).'</td>';
					echo '<td>'.(array_key_exists(5,$pArr)?$pArr[5]:0).'</td>';
					echo '<td><a href="occurrencetabledisplay.php?csmode=1&occindex=0&displayquery=1&collid='.$collId.'" target="_blank">'.(array_key_exists(0,$cArr)?$cArr[0]:0).'</a></td>';
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