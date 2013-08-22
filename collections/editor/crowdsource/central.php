<?php
include_once('../../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceCrowdSource.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';

$csManager = new OccurrenceCrowdSource();

$pArr = array();
if($symbUid){
	if(array_key_exists("CollAdmin",$userRights)) $pArr = $userRights['CollAdmin'];
}

$statusStr = '';
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Crowdsourcing Score Board</title>
    <link type="text/css" href="../../../css/main.css" rel="stylesheet" />
	<script type="text/javascript">

	</script>
</head>
<body>
	<?php 	
	$displayLeftMenu = false;
	include($serverRoot.'/header.php');
	?>
	<div class='navpath'>
		<a href="../../../index.php">Home</a> &gt;&gt;
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
				<?php 
				if($symbUid){
					?>
					<b>Specimens processed:</b> <?php echo $userStats['totalcnt']; ?><br/>
					<b>Approved points:</b> <?php echo $userStats['apoints']; ?><br/>
					<b>Pending points:</b> <a href="crowdsourcestatus.php"><?php echo $userStats['ppoints']; ?></a><br/>
					<b>Approved and pending:</b> <?php echo $userStats['ppoints']+$userStats['apoints']; ?><br/>
					<?php
				}
				else{
					?>
					<div>
						<a href="../../../profile/index.php?refurl=../collections/editor/crowdsourcecentral.php">Login</a> to View Current Stats
					</div>
					<?php 
				}
				?>
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
					$pointArr = $sArr['points'];
					$cntArr = $sArr['cnt'];
					echo '<tr>';
					echo '<td>';
					echo '<b>'.$sArr['name'].'</b>';
					if(in_array($collId, $pArr)) echo ' <a href="controlpanel.php?collid='.$collId.'"><img src="../../../images/edit.png" style="width:14px;" /></a>';
					echo '</td>';
					echo '<td>'.((array_key_exists(5,$cntArr)?$cntArr[5]:0)+(array_key_exists(10,$cntArr)?$cntArr[10]:0)).'</td>';
					echo '<td>'.(array_key_exists(10,$pointArr)?$pointArr[10]:0).'</td>';
					echo '<td>'.(array_key_exists(5,$pointArr)?$pointArr[5]:0).'</td>';
					echo '<td><a href="../occurrencetabledisplay.php?csmode=1&occindex=0&displayquery=1&reset=1&collid='.$collId.'" target="_blank">'.(array_key_exists(0,$cntArr)?$cntArr[0]:0).'</a></td>';
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