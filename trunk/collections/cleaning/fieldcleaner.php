<?php
include_once('../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceCleaner.php');
header("Content-Type: text/html; charset=".$charset);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$obsUid = array_key_exists('obsuid',$_REQUEST)?$_REQUEST['obsuid']:'';
$target = array_key_exists('target',$_REQUEST)?$_REQUEST['target']:'country';
$mode = array_key_exists('mode',$_REQUEST)?$_REQUEST['mode']:'';
$action = array_key_exists('action',$_POST)?$_POST['action']:'';

if(!$symbUid) header('Location: ../../profile/index.php?refurl=../collections/cleaning/fieldcleaner.php?'.$_SERVER['QUERY_STRING']);

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($obsUid)) $obsUid = 0;
if($target && !preg_match('/^[a-z]+$/',$target)) $target = '';
if($mode && !preg_match('/^[a-z]+$/',$mode)) $mode = '';
if($action && !preg_match('/^[a-zA-Z\s]+$/',$action)) $action = '';

$cleanManager = new OccurrenceCleaner();
if($collid) $cleanManager->setCollId($collid);
$collMap = $cleanManager->getCollMap();

$statusStr = '';
$isEditor = 0; 
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collid,$userRights["CollAdmin"]))
	|| ($collMap['colltype'] == 'General Observations')){
	$isEditor = 1;
}

//If collection is a general observation project, limit to User
if($collMap['colltype'] == 'General Observations' && $obsUid !== 0){
	$obsUid = $symbUid;
	$cleanManager->setObsUid($obsUid);
}

?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Field Standardization</title>
	<link href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
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
		<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1">Collection Management</a> &gt;&gt;
		<b>Batch Field Cleaning Tools</b>
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
			<div>
				Description...
			</div>
			<?php 
			if($action){
				if($action == 'Replace Country Value'){
					
				}
			}
			?>
			<fieldset style="padding:20px;">
				<legend><b>Geographic Report</b></legend>
				<div style="margin:20px">
					<?php
					if($target == 'country'){ 
						if(!$mode) $cleanManager->echoCountryClean();
						$cleanManager->echoCountryReport();
						if($mode == 'bad'){
							?>
							<div style="margin:20px">
								<div style="margin:5px">
									<b>Questionable Countries:</b><br/> 
									<?php
									$badCountryArr = $cleanManager->getBadCountryArr();
									$goodCountryArr = $cleanManager->getGoodCountryArr();
									foreach($badCountryArr as $cv){
										?>
										<form name="countrycleanform" method="post" action="fieldcleaner.php" onsubmit="return verifyCountryCleanForm(this)">
											<?php echo $cv; ?>
											<select name="country_new">
												<option value="">Replace with...</option>
													<option value="">-------------------------</option>
												<?php 
												foreach($goodCountryArr as $cgv){
													echo '<option>'.$cgv.'</option>';
												}
												?>
											</select>
											<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
											<input name="mode" type="hidden" value="bad" />
											<input name="badvalue" type="hidden" value="<?php echo $cv; ?>" />
											<input name="action" type="submit" value="Replace Country Value" />
										</form>
										<?php 
									}
									?>
								</div>
							</div>
							<?php 
						}
					}
					?>
				</div>
			</fieldset>
			<fieldset style="padding:20px;">
				<legend><b>Geographic Fields</b></legend>
				<div style="margin:20px">
					<div style="margin:5px">
						<b>Limit by country:</b> 
						<select name="state_old">
							<option value="">--------------------------------</option>
							<?php 
							/* 
							$countryLimitArr = $cleanManager->getCountryArr(false);
							foreach($countryLimitArr as $clv){
								echo '<option>'.$clv.'</option>';
							}
							*/
							?>
						</select>
					</div>
					<div style="margin:5px">
						<b>Current State:</b> 
						<select name="state_old">
							<option value="">--------------------------------</option>
							<?php 
							/* 
							$stateArr = $cleanManager->getStateArr();
							foreach($stateArr as $sv){
								echo '<option>'.$sv.'</option>';
							}
							*/
							?>
						</select>
					</div>
					<div style="margin:5px">
						<b>Replacement State:</b> 
						<select name="state_new">
							<option value="">--------------------------------</option>
							<?php 
							/* 
							$goodStateArr = $cleanManager->getGoodStateArr();
							foreach($goodStateArr as $sgv){
								echo '<option>'.$sgv.'</option>';
							}
							*/
							?>
						</select>
					</div>
				</div>
				<div style="margin:20px">
					<div style="margin:5px">
						<b>Current County:</b> 
						<select name="county_old">
							<option value="">--------------------------------</option>
							<?php 
							/* 
							$countyArr = $cleanManager->getCountyArr();
							foreach($countyArr as $co){
								echo '<option>'.$co.'</option>';
							}
							*/
							?>
						</select>
					</div>
					<div style="margin:5px">
						<b>Replacement County:</b> 
						<select name="county_new">
							<option value="">--------------------------------</option>
							<?php 
							/* 
							$goodCountyArr = $cleanManager->getGoodCountyArr();
							foreach($goodCountyArr as $gco){
								echo '<option>'.$gco.'</option>';
							}
							*/
							?>
						</select>
					</div>
				</div>
			</fieldset>
			<fieldset style="padding:20px;">
				<legend><b>All Fields</b></legend>
				<div style="margin:5px">
					<select name="country_old">
						<option value="">Select Target Field</option>
						<option value="">--------------------------------</option>
						<?php 
						
						
						
						
						?>
					</select>
					<select name="country_old">
						<option value="">Select Target Value</option>
						<option value="">--------------------------------</option>
						<?php 
						
						
						
						
						?>
					</select>
				</div>
				<div style="margin:5px">
					<b>Replacement Value:</b> 
					<input name="country_new" type="text" value="" /> 
				</div>
			</fieldset>
			<?php 
		}
		else{
			echo '<h2>You are not authorized to access this page</h2>';
		}
		?>
	</div>
<?php 	
if(!$dupArr){
	include($serverRoot.'/footer.php');
}
?>
</body>
</html>