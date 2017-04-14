<?php
include_once('../../config/symbini.php'); 
include_once($SERVER_ROOT.'/classes/OccurrenceCleaner.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$obsUid = array_key_exists('obsuid',$_REQUEST)?$_REQUEST['obsuid']:'';
$queryCountry = array_key_exists('q_country',$_REQUEST)?$_REQUEST['q_country']:'';
$action = array_key_exists('action',$_POST)?$_POST['action']:'';

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/cleaning/coordinatevalidator.php?'.$_SERVER['QUERY_STRING']);

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($obsUid)) $obsUid = 0;
if($action && !preg_match('/^[a-zA-Z\s]+$/',$action)) $action = '';

$cleanManager = new OccurrenceCleaner();
if($collid) $cleanManager->setCollId($collid);
$collMap = $cleanManager->getCollMap();

$statusStr = '';
$isEditor = 0; 
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))
	|| ($collMap['colltype'] == 'General Observations')){
	$isEditor = 1;
}

//If collection is a general observation project, limit to User
if($collMap['colltype'] == 'General Observations' && $obsUid !== 0){
	$obsUid = $SYMB_UID;
	$cleanManager->setObsUid($obsUid);
}

?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE; ?> Coordinate Validator</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
	</script>
	<style type="text/css">
		table.styledtable {  width: 300px }
		table.styledtable td { white-space: nowrap; }
	</style>
</head>
<body>
	<?php 	
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/header.php');
	?>
	<div class='navpath'>
		<a href="../../index.php">Home</a> &gt;&gt; 
		<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1">Collection Management</a> &gt;&gt; 
		<a href="index.php?collid=<?php echo $collid; ?>">Cleaning Tools Index</a> &gt;&gt; 
		<b>Coordinate Political Units Validator</b> 
		<?php 
		//echo '&gt;&gt; <a href="coordinatevalidator.php?collid='.$collid.'"><b>Coordinate Validator Main Menu</b></a>';
		?>
	</div>

	<!-- inner text -->
	<div id="innertext">
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="margin:20px;color:<?php echo (substr($statusStr,0,5)=='ERROR'?'red':'green');?>">
				<?php echo $statusStr; ?>
			</div>
			<hr/>
			<?php 
		} 
		echo '<h2>'.$collMap['collectionname'].' ('.$collMap['code'].')</h2>';
		if($isEditor){
			?>
			<div style="margin:15px">
				This tool will loop through all unvalidated georeferenced specimens and verify that the coordinates actually fall within the defined political units.
			</div>
			<div style="margin:15px">
				<?php 
				if($action){
					echo '<fieldset>';
					echo '<legend><b>Action Panel</b></legend>';
					if($action == 'Validate Coordinates'){
						$cleanManager->verifyCoordAgainstPolitical($queryCountry);
					}
					echo '</fieldset>';
				}
				?>
			</div>
			<div style="margin:10px 0px">
				<div style="font-weight:bold">Ranking Statistics</div>
				<?php 
				$coordRankingArr = $cleanManager->getRankingStats('coordinate');
				$rankArr = current($coordRankingArr);
				echo '<table class="styledtable">';
				echo '<tr><th>Ranking</th><th>Protocol</th><th>Count</th></tr>';
				foreach($rankArr as $rank => $protocolArr){
					foreach($protocolArr as $protocolStr => $cnt){
						echo '<tr>';
						echo '<td>'.$rank.'</td>';
						echo '<td>'.$protocolStr.'</td>';
						echo '<td>'.$cnt.'</td>';
						echo '</tr>';
					}
				}
				echo '</table>';
				?>
			</div>
			<div style="margin:10px 0px">
				<div style="font-weight:bold">Non-verified by State/Province</div>
				<?php 
				$countryArr = $cleanManager->getUnverifiedByCountry();
				echo '<table class="styledtable">';
				echo '<tr><th>Country</th><th>Count</th><th>Action</th></tr>';
				foreach($countryArr as $country => $cnt){
					echo '<tr>';
					echo '<td>'.$country.'</td>';
					echo '<td>'.$cnt.'</td>';
					echo '<td>';
					?>
					<form action="coordinatevalidator.php" method="post" style="margin:10px">
						<input name="collid" type="hidden" value="<?php echo $collid; ?>" /> 
						<input name="obsuid" type="hidden" value="<?php echo $obsUid; ?>" /> 
						<input name="q_country" type="hidden" value="<?php echo $country; ?>" /> 
						<input name="action" type="submit" value="Validate Coordinates" />
					</form>
					<?php 
					echo '</td>';
					echo '</tr>';
				}
				echo '</table>';
				?>
			</div>
 			<?php 
		}
		else{
			echo '<h2>You are not authorized to access this page</h2>';
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>