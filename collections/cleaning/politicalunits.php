<?php
include_once('../../config/symbini.php'); 
include_once($SERVER_ROOT.'/classes/OccurrenceCleaner.php');
include_once($SERVER_ROOT.'/content/lang/collections/cleaning/politicalunits.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$obsUid = array_key_exists('obsuid',$_REQUEST)?$_REQUEST['obsuid']:'';
$target = array_key_exists('target',$_REQUEST)?$_REQUEST['target']:'geolocal';
$mode = array_key_exists('mode',$_REQUEST)?$_REQUEST['mode']:'';
$action = array_key_exists('action',$_POST)?$_POST['action']:'';

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/cleaning/politicalunits.php?'.$_SERVER['QUERY_STRING']);

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
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))
	|| ($collMap['colltype'] == 'General Observations')){
	$isEditor = 1;
}

//If collection is a general observation project, limit to User
if($collMap['colltype'] == 'General Observations' && $obsUid !== 0){
	$obsUid = $SYMB_UID;
	$cleanManager->setObsUid($obsUid);
}

if($action && $isEditor){
	if($action == 'Replace Country'){
		if(!$cleanManager->updateField('country', $_POST['badcountry'], $_POST['newcountry'])){
			$statusStr = $cleanManager->getErrorStr();
		}
	}
	elseif($action == 'Assign Country'){
		if(!$cleanManager->updateField('country', '', $_POST['country'], array('stateprovince' => $_POST['stateprovince']))){
			$statusStr = $cleanManager->getErrorStr();
		}
	}
	elseif($action == 'Replace State'){
		if(!$cleanManager->updateField('stateProvince', $_POST['badstate'], $_POST['newstate'], array('country' => $_POST['country']))){
			$statusStr = $cleanManager->getErrorStr();
		}
	}
	elseif($action == 'Assign State'){
		$condArr = array('county' => $_POST['county'],'country' => $_POST['country']);
		if(!$cleanManager->updateField('stateProvince', '', $_POST['state'], $condArr)){
			$statusStr = $cleanManager->getErrorStr();
		}
	}
	elseif($action == 'Replace County'){
		$condArr = array('country' => $_POST['country'], 'stateprovince' => $_POST['state']);
		if(!$cleanManager->updateField('county', $_POST['badcounty'], $_POST['newcounty'], $condArr)){
			$statusStr = $cleanManager->getErrorStr();
		}
	}
	elseif($action == 'Assign County'){
		$condArr = array('locality' => $_POST['locality'], 'country' => $_POST['country'], 'stateprovince' => $_POST['state']);
		if(!$cleanManager->updateField('county', '', $_POST['county'], $condArr)){
			$statusStr = $cleanManager->getErrorStr();
		}
	}
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE." ".$LANG['POLITICAL_UNITS_STANDARDIZATION']; ?></title>

    <link href="../../css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
		function verifyCountryCleanForm(f){
			if(f.newcountry.value == ""){
				alert("<?php echo $LANG['SELECT_A_COUNTRY_VALUE']; ?>");
				return false
			}
			return true;
		}

		function verifyNullCountryForm(f){
			if(f.country.value == ""){
				alert("<?php echo $LANG['SELECT_A_COUNTRY_VALUE']; ?>");
				return false
			}
			return true;
		}
	
		function verifyStateCleanForm(f){
			if(f.newstate.value == ""){
				alert("<?php echo $LANG['SELECT_A_STATE_VALUE']; ?>");
				return false
			}
			return true;
		}

		function verifyNullStateForm(f){
			if(f.state.value == ""){
				alert("<?php echo $LANG['SELECT_A_STATE_VALUE']; ?>");
				return false
			}
			return true;
		}

		function verifyCountyCleanForm(f){
			if(f.newcounty.value == ""){
				alert("<?php echo $LANG['SELECT_A_COUNTY_VALUE']; ?>");
				return false
			}
			return true;
		}

		function verifyNullCountyForm(f){
			if(f.county.value == ""){
				alert("<?php echo $LANG['SELECT_A_COUNTY_VALUE']; ?>");
				return false
			}
			return true;
		}
	</script>
</head>
<body>
	<?php 	
	$displayLeftMenu = false;
	include($SERVER_ROOT.'/header.php');
	?>
	<div class='navpath'>
		<a href="../../index.php"><?php echo $LANG['HOME']; ?></a> &gt;&gt;
		<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=off"><?php echo $LANG['ADMIN'];?></a> &gt;&gt;
		<a href="index.php?collid=<?php echo $collid; ?>"><?php echo $LANG['IN'];?></a> 
		<?php 
		if($mode) echo '&gt;&gt; <a href="politicalunits.php?collid='.$collid.'"><b>'.$LANG['POLITICAL_GEOGRAPHY_CLEANING_MENU'].'</b></a>';
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
			<fieldset style="padding:20px;position:relative">
				<legend><b><?php echo $LANG['REPOR'];?></b></legend>
				<?php
				if($target == 'geolocal'){ 
					if($mode) echo '<div style="position:absolute;top:5px;right:0px;padding:10px;border:1px solid grey"><a href="politicalunits.php?collid='.$collid.'&mode=0">'.$LANG['MAIN_MENU'].'</a></div>';
					echo '<div style="width:85%;margin-bottom:15px;">'.$LANG['CLICK_ON_LINKS_PROVIDED_BELOW_TO_LIST'].'</div>';
					if($mode == 'badcountry'){
						$badCountryArr = $cleanManager->getBadCountryArr();
						$goodCountryArr = $cleanManager->getGoodCountryArr();
						?>
						<div style="margin:20px">
							<div style="margin:5px">
								<div style="margin-bottom:10px;">
									<b><?php echo $LANG['QUES'];?></b> <?php echo $cleanManager->getFeatureCount(); ?> <?php echo $LANG['POSSI'];?>
								</div> 
								<?php
								foreach($badCountryArr as $countryName => $countryCnt){
									?>
									<div style="margin-left:15px;">
										<form name="countrycleanform" method="post" action="politicalunits.php" onsubmit="return verifyCountryCleanForm(this)">
											<b><?php echo $countryName; ?></b>
											<?php echo ' <span title="'.$LANG['NUMBER_OF_SPECIMENS'].'">('.$countryCnt.')</span>'; ?>
											<a href="../editor/occurrenceeditor.php?q_catalognumber=&occindex=0&q_customfield1=country&q_customtype1=EQUALS&q_customvalue1=<?php echo urlencode($countryName).'&collid='.$collid; ?>" target="_blank"><img src="../../images/edit.png" style="width:13px" /></a>
											<select name="newcountry" style="width:200px;">
												<option value=""><?php echo $LANG['REM'];?></option>
													<option value="">-------------------------</option>
												<?php 
												foreach($goodCountryArr as $cgv){
													echo '<option>'.$cgv.'</option>';
												}
												?>
											</select>
											<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
											<input name="target" type="hidden" value="geolocal" />
											<input name="mode" type="hidden" value="badcountry" />
											<input name="badcountry" type="hidden" value="<?php echo $countryName; ?>" />
											<input type="hidden" name="action" value="Replace Country" />
											<input type="submit" value="<?php echo $LANG['REPLACE_COUNTRY']; ?>" />
										</form>
									</div>
									<?php 
								}
								?>
							</div>
						</div>
						<?php 
					}
					elseif($mode == 'nullcountry'){
						$badCountryArr = $cleanManager->getNullCountryNotStateArr();
						$goodCountryArr = $cleanManager->getGoodCountryArr(true);
						?>
						<div style="margin:20px">
							<div style="margin:5px">
								<div style="margin-bottom:10px;font-size:120%;">
									<span style="text-decoration: underline; font-weight:bold"><?php echo $LANG['NULL_COUNTRIES_AND_NON_NULL_STATE']; ?>:</span> <?php echo $cleanManager->getFeatureCount(); ?> <?php echo $LANG['POSSI'];?>
								</div> 
								<?php
								foreach($badCountryArr as $stateName => $stateCnt){
									?>
									<div style="margin-left:15px;">
										<form name="nullcountryform" method="post" action="politicalunits.php" onsubmit="return verifyNullCountryForm(this)">
											<b><?php echo $stateName; ?></b>
											<?php echo ' <span title="'.$LANG['NUMBER_OF_SPECIMENS'].'">('.$stateCnt.')</span>'; ?>
											<a href="../editor/occurrenceeditor.php?q_catalognumber=&occindex=0&q_customfield1=country&q_customtype1=NULL&q_customfield2=stateProvince&q_customtype2=EQUALS&q_customvalue2=<?php echo urlencode($stateName).'&collid='.$collid; ?>" target="_blank"><img src="../../images/edit.png" style="width:13px" /></a>
											<select name="country" style="width:200px;">
												<option value=""><?php echo $LANG['ASSING'];?></option>
												<option value="">-------------------------</option>
												<?php 
												foreach($goodCountryArr as $gcv => $stateArr){
													echo '<option '.($gcv!='USA'&&in_array($stateName,$stateArr)?'SELECTED':'').'>'.$gcv.'</option>';
												}
												?>
											</select>
											<input name="stateprovince" type="hidden" value="<?php echo $stateName; ?>" />
											<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
											<input name="target" type="hidden" value="geolocal" />
											<input name="mode" type="hidden" value="nullcountry" />
											<input type="hidden" name="action" value="Assign Country" />
											<input type="submit" value="<?php echo $LANG['ASSIGN_COUNTRY']; ?>" />
										</form>
									</div>
									<?php 
								}
								?>
							</div>
						</div>
						<?php 
					}
					elseif($mode == 'badstate'){
						$badStateArr = $cleanManager->getBadStateArr();
						$goodStateArr = $cleanManager->getGoodStateArr();
						?>
						<div style="margin:20px">
							<div style="margin:5px">
								<div style="margin-bottom:10px;">
									<b><?php echo $LANG['QUESTION'];?></b> <?php echo $cleanManager->getFeatureCount(); ?> <?php echo $LANG['POSSI'];?>
								</div> 
								<?php
								foreach($badStateArr as $countryValue => $stateArr){
									echo '<div style="margin-left:0px;"><b><u>'.$countryValue.'</u></b></div>';
									foreach($stateArr as $stateName => $stateCnt){
										?>
										<div style="margin-left:15px;">
											<form name="statecleanform" method="post" action="politicalunits.php" onsubmit="return verifyStateCleanForm(this)">
												<b><?php echo $stateName; ?></b>
												<?php echo ' <span title="'.$LANG['NUMBER_OF_SPECIMENS'].'">('.$stateCnt.')</span>'; ?>
												<a href="../editor/occurrenceeditor.php?q_catalognumber=&occindex=0&q_customfield1=stateProvince&q_customtype1=EQUALS&q_customvalue1=<?php echo urlencode($stateName).'&collid='.$collid; ?>" target="_blank"><img src="../../images/edit.png" style="width:13px" /></a>
												<?php 
												if(array_key_exists($countryValue,$goodStateArr)){
													?>
													<select name="newstate" style="width:200px;">
														<option value=""><?php echo $LANG['REM'];?></option>
														<option value="">-------------------------</option>
														<?php 
														$arr = $goodStateArr[$countryValue];
														foreach($arr as $stateValue){
															echo '<option>'.$stateValue.'</option>';
														}
														?>
													</select>
													<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
													<input name="target" type="hidden" value="geolocal" />
													<input name="mode" type="hidden" value="badstate" />
													<input name="badstate" type="hidden" value="<?php echo $stateName; ?>" />
													<input name="country" type="hidden" value="<?php echo $countryValue; ?>" />
													<input type="hidden" name="action" value="Replace State" />
													<input type="submit" value="<?php echo $LANG['REPLACE_STATE']; ?>" />
													<?php
												} 
												?>
											</form>
										</div>
										<?php 
									}
								}
								?>
							</div>
						</div>
						<?php 
					}
					elseif($mode == 'nullstate'){
						$badStateArr = $cleanManager->getNullStateNotCountyArr();
						$goodStateArr = $cleanManager->getGoodStateArr(true);
						?>
						<div style="margin:20px">
							<div style="margin:5px">
								<div style="margin-bottom:10px;font-size:120%;">
									<span style="text-decoration: underline; font-weight:bold"><?php echo $LANG['NULL'];?></span> <?php echo $cleanManager->getFeatureCount(); ?> <?php echo $LANG['POSSI'];?>
								</div> 
								<?php
								foreach($badStateArr as $countryName => $countyArr){
									echo '<div style="margin-left:0px;"><b><u>'.$countryName.'</u></b></div>';
									foreach($countyArr as $countyName => $countyCnt){
										?>
										<div style="margin-left:15px;">
											<form name="nullstateform" method="post" action="politicalunits.php" onsubmit="return verifyNullStateForm(this)">
												<b><?php echo $countyName; ?></b>
												<?php echo ' <span title="'.$LANG['NUMBER_OF_SPECIMENS'].'">('.$countyCnt.')</span>'; ?>
												<a href="../editor/occurrenceeditor.php?q_catalognumber=&occindex=0&q_customfield1=stateProvince&q_customtype1=NULL&q_customfield2=county&q_customtype2=EQUALS&q_customvalue2=<?php echo urlencode($countyName).'&collid='.$collid; ?>" target="_blank"><img src="../../images/edit.png" style="width:13px" /></a>
												<?php 
												if(array_key_exists($countryName,$goodStateArr)){
													?>
													<select name="state" style="width:200px;">
														<option value=""><?php echo $LANG['ASSING'];?></option>
														<option value="">-------------------------</option>
														<?php 
														$countyTestStr = str_replace(array(' county',' co.',' co'),'',strtolower($countyName));
														$arr = $goodStateArr[$countryName];
														foreach($arr as $gsv => $cArr){
															echo '<option '.(in_array($countyTestStr,$cArr)?'SELECTED':'').'>'.$gsv.'</option>';
														}
														?>
													</select>
													<input name="county" type="hidden" value="<?php echo $countyName; ?>" />
													<input name="country" type="hidden" value="<?php echo $countryName; ?>" />
													<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
													<input name="target" type="hidden" value="geolocal" />
													<input name="mode" type="hidden" value="nullstate" />
													<input type="hidden" name="action" value="Assign State" />
													<input type="submit" value="<?php echo $LANG['ASSIGN_STATE']; ?>" />
													<?php
												} 
												?>
											</form>
										</div>
										<?php 
									}
								}
								?>
							</div>
						</div>
						<?php 
					}
					elseif($mode == 'badcounty'){
						$badCountyArr = $cleanManager->getBadCountyArr();
						$goodCountyArr = $cleanManager->getGoodCountyArr();
						?>
						<div style="margin:20px">
							<div style="margin:5px">
								<div style="margin-bottom:10px;">
									<b><?php echo $LANG['QUESTION'];?></b> <?php echo $cleanManager->getFeatureCount(); ?> <?php echo $LANG['POSSI'];?>
								</div> 
								<?php
								foreach($badCountyArr as $countryName => $stateArr){
									echo '<div style="margin-left:0px;"><b><u>'.$countryName.'</u></b></div>';
									foreach($stateArr as $stateName => $countyArr){
										$stateTestStr = strtolower($stateName);
										echo '<div style="margin-left:15px;"><b><u>'.$stateName.'</u></b></div>';
										foreach($countyArr as $countyName => $countyCnt){
											$countyTestStr = str_replace(array(' county',' co.',' co'), '', strtolower($countyName));
											?>
											<div style="margin-left:30px;">
												<form name="countycleanform" method="post" action="politicalunits.php" onsubmit="return verifyCountyCleanForm(this)">
													<b><?php echo $countyName; ?></b>
													<?php echo ' <span title="'.$LANG['NUMBER_OF_SPECIMENS'].'">('.$countyCnt.')</span>'; ?>
													<a href="../editor/occurrenceeditor.php?q_catalognumber=&occindex=0&q_customfield1=county&q_customtype1=EQUALS&q_customvalue1=<?php echo urlencode($countyName).'&collid='.$collid; ?>" target="_blank"><img src="../../images/edit.png" style="width:13px" /></a>
													<?php 
													if(array_key_exists($stateTestStr,$goodCountyArr)){
														?>
														<select name="newcounty" style="width:200px;">
															<option value=""><?php echo $LANG['REM'];?></option>
																<option value="">-------------------------</option>
															<?php 
															$arr = $goodCountyArr[$stateTestStr];
															foreach($arr as $v){
																echo '<option '.($countyTestStr==strtolower($v)?'selected':'').'>'.$v.'</option>';
															}
															?>
														</select>
														<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
														<input name="target" type="hidden" value="geolocal" />
														<input name="mode" type="hidden" value="badcounty" />
														<input name="badcounty" type="hidden" value="<?php echo $countyName; ?>" />
														<input name="country" type="hidden" value="<?php echo $countryName; ?>" />
														<input name="state" type="hidden" value="<?php echo $stateName; ?>" />
														<input type="submit" name="action" value="Replace County" />
														<input type="submit" value="<?php echo $LANG['REPLACE_COUNTY']; ?>" />
														<?php
													} 
													?>
												</form>
											</div>
											<?php 
										}
									}
								}
								?>
							</div>
						</div>
						<?php 
					}
					elseif($mode == 'nullcounty'){
						$badCountyArr = $cleanManager->getNullCountyNotLocalityArr();
						$goodCountyArr = $cleanManager->getGoodCountyArr();
						?>
						<div style="margin:20px">
							<div style="margin:5px">
								<div style="margin-bottom:10px;font-size:120%;">
									<span style="text-decoration: underline; font-weight:bold"><?php echo $LANG['NULL:STATE'];?></span> <?php echo $cleanManager->getFeatureCount(); ?> <?php echo $LANG['POSSI'];?>
								</div> 
								<?php
								foreach($badCountyArr as $countryName => $stateArr){
									echo '<div style="margin-left:0px;"><b><u>'.$countryName.'</u></b></div>';
									foreach($stateArr as $stateName => $localityArr){
										echo '<div style="margin-left:15px;"><b><u>'.$stateName.'</u></b></div>';
										$stateTestStr = strtolower($stateName);
										foreach($localityArr as $localityName => $localityCnt){
											?>
											<div style="margin-left:30px;">
												<form name="nullstateform" method="post" action="politicalunits.php" onsubmit="return verifyNullCountyForm(this)">
													<b><?php echo $localityName; ?></b>
													<?php echo ' <span title="'.$LANG['NUMBER_OF_SPECIMENS'].'">('.$localityCnt.')</span>'; ?>
													<a href="../editor/occurrenceeditor.php?q_catalognumber=&occindex=0&q_customfield1=county&q_customtype1=NULL&q_customfield2=locality&q_customtype2=EQUALS&q_customvalue2=<?php echo urlencode($localityName).'&collid='.$collid; ?>" target="_blank"><img src="../../images/edit.png" style="width:13px" /></a>
													<?php 
													if(array_key_exists($stateTestStr,$goodCountyArr)){
														?>
														<select name="county" style="width:200px;">
															<option value=""><?php echo $LANG['ASSI:STAT'];?></option>
															<option value="">-------------------------</option>
															<?php 
															$arr = $goodCountyArr[$stateTestStr];
															foreach($arr as $v){
																echo '<option>'.$v.'</option>';
															}
															?>
														</select>
														<input name="locality" type="hidden" value="<?php echo htmlentities($localityName); ?>" />
														<input name="country" type="hidden" value="<?php echo $countryName; ?>" />
														<input name="state" type="hidden" value="<?php echo $stateName; ?>" />
														<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
														<input name="target" type="hidden" value="geolocal" />
														<input name="mode" type="hidden" value="nullcounty" />
														<input type="hidden" name="action" value="Assign County" />
														<input type="submit" value="<?php echo $LANG['ASSIGN_COUNTY']; ?>" />
														<?php
													} 
													?>
												</form>
											</div>
											<?php 
										}
									}
								}
								?>
							</div>
						</div>
						<?php 
					}
					else{
						if($mode === ''){
							echo '<div style="margin-bottom:15px;">';
							echo '<div style="font-weight:bold;">'.$LANG['GENERAL_CLEANING'].'... </div>';
							flush();
							ob_flush();
							$cleanManager->countryCleanFirstStep();
							echo '</div>';
						}
						
						echo '<div style="margin-bottom:2px"><b>'.$LANG['QUESTIONABLE_COUNTRIES'].':</b> ';
						$badCountryCnt = $cleanManager->getBadCountryCount();
						echo $badCountryCnt;
						if($badCountryCnt) echo ' => <a href="politicalunits.php?collid='.$collid.'&target=geolocal&mode=badcountry">'.$LANG['LIST_COUNTRIES'].'...</a>';
						echo '</div>';
						
						//Get Null country and not null state
						echo '<div style="margin-bottom:20px"><b>'.$LANG['NULL_COUNTRY_WITH_NON_NULL_STATE_PROVINCE'].':</b> ';
						$nullCountryCnt = $cleanManager->getNullCountryNotStateCount();
						echo $nullCountryCnt;
						if($nullCountryCnt) echo ' => <a href="politicalunits.php?collid='.$collid.'&target=geolocal&mode=nullcountry">'.$LANG['LIST_RECORDS'].'...</a>';
						echo '</div>';
						
						echo '<div style="margin-bottom:2px"><b>'.$LANG['QUESTIONABLE_STATES_PROVINCES'].':</b> ';
						$badStateCnt = $cleanManager->getBadStateCount();
						echo $badStateCnt;
						if($badStateCnt) echo ' => <a href="politicalunits.php?collid='.$collid.'&target=geolocal&mode=badstate">'.$LANG['LIST_STATES'].'...</a>';
						echo '</div>';
						
						//Get Null state and not null county or municipality
						echo '<div style="margin-bottom:20px"><b>'.$LANG['NULL_STATE_PROVINCE_WITH_NON_NULL_COUNTY'].':</b> ';
						$nullStateCnt = $cleanManager->getNullStateNotCountyCount();
						echo $nullStateCnt;
						if($nullStateCnt) echo ' => <a href="politicalunits.php?collid='.$collid.'&target=geolocal&mode=nullstate">'.$LANG['LIST_RECORDS'].'...</a>';
						echo '</div>';
						
						echo '<div style="margin-bottom:2px"><b>'.$LANG['QUESTIONABLE_COUNTIES'].':</b> ';
						$badCountiesCnt = $cleanManager->getBadCountyCount();
						echo $badCountiesCnt;
						if($badCountiesCnt) echo ' => <a href="politicalunits.php?collid='.$collid.'&target=geolocal&mode=badcounty">'.$LANG['LIST_COUNTIES'].'...</a>';
						echo '</div>';
						
						//Get Null county and not null locality
						echo '<div style="margin-bottom:60px"><b>'.$LANG['NULL_COUNTY_WITH_NON_NULL_LOCALITY_DETAILS'].':</b> ';
						$nullCountyCnt = $cleanManager->getNullCountyNotLocalityCount();
						echo $nullCountyCnt;
						if($nullCountyCnt) echo ' => <a href="politicalunits.php?collid='.$collid.'&target=geolocal&mode=nullcounty">'.$LANG['LIST_RECORDS'].'...</a>';
						echo '</div>';
					}
				}
				?>
			</fieldset>
			<!-- 
			<fieldset style="padding:20px;">
				<legend><b>All Fields</b></legend>
				<div style="margin:5px">
					<b>Field Name:</b> 
					<select name="fieldname">
						<option value="">Select Target Field</option>
						<option value="">--------------------------------</option>
						<?php 
						
						
						
						
						?>
					</select>
				</div>
				<div style="margin:5px">
					<b>Current Value:</b> 
					<select name="value_old">
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
 			-->
 			<?php 
		}
		else{
			echo '<h2>'.$LANG['YOU_ARE_NOT_AUTHORIZED_TO_ACCESS_THIS_PAGE'].'</h2>';
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>