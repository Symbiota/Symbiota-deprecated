<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCrowdSource.php');
header("Content-Type: text/html; charset=".$CHARSET);

$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';
$catid = array_key_exists('catid',$_REQUEST)?$_REQUEST['catid']:'';

if(isset($DEFAULTCATID) && $DEFAULTCATID && $catid === '') $catid = $DEFAULTCATID;

$csManager = new OccurrenceCrowdSource();

$pArr = array();
if($SYMB_UID){
	if(array_key_exists("CollAdmin",$USER_RIGHTS)){
		$pArr = $USER_RIGHTS['CollAdmin'];
	}
}

$statusStr = '';
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE; ?> Crowdsourcing Score Board</title>
    <link href="../../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" rel="stylesheet" type="text/css" />
    <link href="../../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" rel="stylesheet" type="text/css" />
	<script type="text/javascript">

	</script>
</head>
<body>
	<?php
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/header.php');
	if(isset($crowdsourcecentral_listCrumbs)){
		if($crowdsourcecentral_listCrumbs){
			echo $crowdsourcecentral_listCrumbs;
		}
	}
	else{
		echo "<div class='navpath'>";
		echo "<a href='../../../index.php'>Home</a> &gt;&gt; ";
		echo "<b>Crowdsourcing Score Board</b>";
		echo "</div>";
	}
	?>

	<!-- inner text -->
	<div id="innertext">
		<h1>Crowdsourcing Score Board</h1>

		<div style="margin-left:20px;float:left;">
			<h2>Top Scores</h2>
			<table class="styledtable" style="font-family:Arial;font-size:12px;width:300px;">
				<tr><th><b>User</b></th><th><b>Score</b></th></tr>
			<?php
			$topScoreArr = $csManager->getTopScores($catid);
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

		<div style="margin-right:20px;float:right;">
			<h2>Current User's Status</h2>
			<?php
			$userStats = $csManager->getUserStats($catid);
			?>
			<fieldset style="background-color:white;margin-bottom:15px;width:250px;padding:15px;">
				<legend><b>Current Standing</b></legend>
				<?php
				if($SYMB_UID){
					?>
					<b>Specimens processed:</b> <?php echo $userStats['totalcnt']; ?><br/>
					<b>Pending points:</b> <?php echo $userStats['ppoints']; ?>
					<?php if($userStats['ppoints']) echo '(<a href="review.php?rstatus=5&uid='.$SYMB_UID.'">view records</a>)'; ?> <br/>
					<b>Approved points:</b> <?php echo $userStats['apoints']; ?>
					<?php if($userStats['apoints']) echo '(<a href="review.php?rstatus=10&uid='.$SYMB_UID.'">view records</a>)'; ?> <br/>
					<b>Total Possible Score:</b> <?php echo $userStats['ppoints']+$userStats['apoints']; ?><br/>
					<?php
				}
				else{
					?>
					<div>
						<a href="../../../profile/index.php?refurl=../collections/specprocessor/crowdsource/index.php">Login</a> to View Current Stats
					</div>
					<?php
				}
				?>
			</fieldset>
		</div>
		<?php
		if(isset($USER_RIGHTS['CollAdmin']) || isset($USER_RIGHTS['CollEditor'])){
			?>
			<div style="clear:both;margin:30px;">
				<b>Note:</b> You have been identified as an official editor for one or more collections.
				Your points will not be counted in the Top Score table for specimens
				that belong to collection to which you have edit rights.
				Top scores are posted only for specimens entered as a volunteer user.
			</div>
			<?php
		}
		?>
		<div style="margin:20px;clear:both;">
			<h2>User Stats by Collections</h2>
			<table class="styledtable" style="font-family:Arial;font-size:12px;">
				<tr>
					<th><b>Collection</b></th>
					<th><b>Specimen<br/>Count</b></th>
					<th><b>Pending<br/>Points</b></th>
					<th><b>Approved<br/>Points</b></th>
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
					if($IS_ADMIN || in_array($collId, $pArr)) echo ' <a href="../index.php?tabindex=2&collid='.$collId.'"><img src="../../../images/edit.png" style="width:14px;" /></a>';
					echo '</td>';
					echo '<td>'.((array_key_exists(5,$cntArr)?$cntArr[5]:0)+(array_key_exists(10,$cntArr)?$cntArr[10]:0)).'</td>';
					echo '<td>'.(array_key_exists(5,$pointArr)?$pointArr[5]:0).'</td>';
					echo '<td>'.(array_key_exists(10,$pointArr)?$pointArr[10]:0).'</td>';
					echo '<td><a href="../../editor/occurrencetabledisplay.php?csmode=1&occindex=0&displayquery=1&reset=1&collid='.$collId.'" target="_blank">'.(array_key_exists(0,$cntArr)?$cntArr[0]:0).'</a></td>';
					echo '</tr>';
				}
				?>
			</table>
		</div>
	</div>
	<?php
	include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>