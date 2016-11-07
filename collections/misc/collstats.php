<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/CollectionProfileManager.php');
header("Content-Type: text/html; charset=".$charset);
ini_set('max_execution_time', 1200); //1200 seconds = 20 minutes

$catId = array_key_exists("catid",$_REQUEST)?$_REQUEST["catid"]:0;
if(!$catId && isset($DEFAULTCATID) && $DEFAULTCATID) $catId = $DEFAULTCATID;
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

$collManager = new CollectionProfileManager();

//if($collId) $collManager->setCollectionId($collId);
$collList = $collManager->getStatCollectionList($catId);
$specArr = (isset($collList['spec'])?$collList['spec']:null);
$obsArr = (isset($collList['obs'])?$collList['obs']:null);

$collIdArr = array();
$collectionArr = array();
$familyArr = array();
$countryArr = array();
$results = array();
$collStr = '';
if($collId){
	$collIdArr = explode(",",$collId);
	if($action == "Run Statistics"){
		$resultsTemp = $collManager->runStatistics($collId);
		$results['FamilyCount'] = $resultsTemp['familycnt'];
		$results['GeneraCount'] = $resultsTemp['genuscnt'];
		$results['SpeciesCount'] = $resultsTemp['speciescnt'];
		$results['TotalTaxaCount'] = $resultsTemp['TotalTaxaCount'];
		$results['TotalImageCount'] = $resultsTemp['TotalImageCount'];
		unset($resultsTemp['familycnt']);
		unset($resultsTemp['genuscnt']);
		unset($resultsTemp['speciescnt']);
		unset($resultsTemp['TotalTaxaCount']);
		unset($resultsTemp['TotalImageCount']);
		ksort($resultsTemp);
		$c = 0;
		foreach($resultsTemp as $k => $collArr){
			$dynPropTempArr = array();
			$familyTempArr = array();
			$countryTempArr = array();
			if($c>0) $collStr .= ", ";
			$collStr .= $collArr['CollectionName'];
			if(array_key_exists("SpecimenCount",$results)){
				$results['SpecimenCount'] = $results['SpecimenCount'] + $collArr['recordcnt'];
			}
			else{
				$results['SpecimenCount'] = $collArr['recordcnt'];
			}

			if(array_key_exists("GeorefCount",$results)){
				$results['GeorefCount'] = $results['GeorefCount'] + $collArr['georefcnt'];
			}
			else{
				$results['GeorefCount'] = $collArr['georefcnt'];
			}

			if($collArr['dynamicProperties']){
				$dynPropTempArr = json_decode($collArr['dynamicProperties'],true);

				if(is_array($dynPropTempArr)){
					$resultsTemp[$k]['speciesID'] = $dynPropTempArr['SpecimensCountID'];
					$resultsTemp[$k]['types'] = $dynPropTempArr['TypeCount'];

					if(array_key_exists("SpecimensCountID",$results)){
						$results['SpecimensCountID'] = $results['SpecimensCountID'] + $dynPropTempArr['SpecimensCountID'];
					}
					else{
						$results['SpecimensCountID'] = $dynPropTempArr['SpecimensCountID'];
					}

					if(array_key_exists("TypeCount",$results)){
						$results['TypeCount'] = $results['TypeCount'] + $dynPropTempArr['TypeCount'];
					}
					else{
						$results['TypeCount'] = $dynPropTempArr['TypeCount'];
					}

					if(array_key_exists("families",$dynPropTempArr)){
						$familyTempArr = $dynPropTempArr['families'];
						foreach($familyTempArr as $k => $famArr){
							if(array_key_exists($k,$familyArr)){
								$familyArr[$k]['SpecimensPerFamily'] = $familyArr[$k]['SpecimensPerFamily'] + $famArr['SpecimensPerFamily'];
								$familyArr[$k]['GeorefSpecimensPerFamily'] = $familyArr[$k]['GeorefSpecimensPerFamily'] + $famArr['GeorefSpecimensPerFamily'];
								$familyArr[$k]['IDSpecimensPerFamily'] = $familyArr[$k]['IDSpecimensPerFamily'] + $famArr['IDSpecimensPerFamily'];
								$familyArr[$k]['IDGeorefSpecimensPerFamily'] = $familyArr[$k]['IDGeorefSpecimensPerFamily'] + $famArr['IDGeorefSpecimensPerFamily'];
							}
							else{
								$familyArr[$k]['SpecimensPerFamily'] = $famArr['SpecimensPerFamily'];
								$familyArr[$k]['GeorefSpecimensPerFamily'] = $famArr['GeorefSpecimensPerFamily'];
								$familyArr[$k]['IDSpecimensPerFamily'] = $famArr['IDSpecimensPerFamily'];
								$familyArr[$k]['IDGeorefSpecimensPerFamily'] = $famArr['IDGeorefSpecimensPerFamily'];
							}
						}
						ksort($familyArr, SORT_STRING | SORT_FLAG_CASE);
					}

					if(array_key_exists("countries",$dynPropTempArr)){
						$countryTempArr = $dynPropTempArr['countries'];
						foreach($countryTempArr as $k => $countArr){
							if(array_key_exists($k,$countryArr)){
								$countryArr[$k]['CountryCount'] = $countryArr[$k]['CountryCount'] + $countArr['CountryCount'];
								$countryArr[$k]['GeorefSpecimensPerCountry'] = $countryArr[$k]['GeorefSpecimensPerCountry'] + $countArr['GeorefSpecimensPerCountry'];
								$countryArr[$k]['IDSpecimensPerCountry'] = $countryArr[$k]['IDSpecimensPerCountry'] + $countArr['IDSpecimensPerCountry'];
								$countryArr[$k]['IDGeorefSpecimensPerCountry'] = $countryArr[$k]['IDGeorefSpecimensPerCountry'] + $countArr['IDGeorefSpecimensPerCountry'];
							}
							else{
								$countryArr[$k]['CountryCount'] = $countArr['CountryCount'];
								$countryArr[$k]['GeorefSpecimensPerCountry'] = $countArr['GeorefSpecimensPerCountry'];
								$countryArr[$k]['IDSpecimensPerCountry'] = $countArr['IDSpecimensPerCountry'];
								$countryArr[$k]['IDGeorefSpecimensPerCountry'] = $countArr['IDGeorefSpecimensPerCountry'];
							}
						}
						ksort($countryArr, SORT_STRING | SORT_FLAG_CASE);
					}
				}
			}
			$c++;
		}
		$results['SpecimensNullLatitude'] = $results['SpecimenCount'] - $results['GeorefCount'];
	}
	if($action == "Update Statistics"){
		$collManager->batchUpdateStatistics($collId);
		echo '<script type="text/javascript">window.location="collstats.php?collid='.$collId.'"</script>';
	}
}
if($action != "Update Statistics"){
	?>
	<html>
		<head>
			<meta name="keywords" content="Natural history collections statistics" />
			<title><?php echo $defaultTitle; ?> Collection Statistics</title>
			<link rel="stylesheet" href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" />
			<link rel="stylesheet" href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" />
			<link href="../../css/jquery-ui.css" type="text/css" rel="Stylesheet" />
			<script type="text/javascript" src="../../js/jquery.js"></script>
			<script type="text/javascript" src="../../js/jquery-ui.js"></script>
			<script type="text/javascript" src="../../js/symb/collections.index.js"></script>
			<script type="text/javascript">
				$(document).ready(function() {
					if(!navigator.cookieEnabled){
						alert("Your browser cookies are disabled. To be able to login and access your profile, they must be enabled for this domain.");
					}
					$("#tabs").tabs({<?php echo ($action == "Run Statistics"?'active: 1':''); ?>});
				});

				function toggleStatsPerColl(){
					toggleById("statspercollbox");
					toggleById("showstatspercoll");
					toggleById("hidestatspercoll");

					document.getElementById("geodistbox").style.display="none";
					document.getElementById("showgeodist").style.display="block";
					document.getElementById("hidegeodist").style.display="none";
					document.getElementById("famdistbox").style.display="none";
					document.getElementById("showfamdist").style.display="block";
					document.getElementById("hidefamdist").style.display="none";
					return false;
				}

				function toggleFamilyDist(){
					toggleById("famdistbox");
					toggleById("showfamdist");
					toggleById("hidefamdist");

					document.getElementById("geodistbox").style.display="none";
					document.getElementById("showgeodist").style.display="block";
					document.getElementById("hidegeodist").style.display="none";
					document.getElementById("statspercollbox").style.display="none";
					document.getElementById("showstatspercoll").style.display="block";
					document.getElementById("hidestatspercoll").style.display="none";
					return false;
				}

				function toggleGeoDist(){
					toggleById("geodistbox");
					toggleById("showgeodist");
					toggleById("hidegeodist");

					document.getElementById("famdistbox").style.display="none";
					document.getElementById("showfamdist").style.display="block";
					document.getElementById("hidefamdist").style.display="none";
					document.getElementById("statspercollbox").style.display="none";
					document.getElementById("showstatspercoll").style.display="block";
					document.getElementById("hidestatspercoll").style.display="none";
					return false;
				}

				function toggleById(target){
					if(target != null){
						var obj = document.getElementById(target);
						if(obj.style.display=="none" || obj.style.display==""){
							obj.style.display="block";
						}
						else {
							obj.style.display="none";
						}
					}
					return false;
				}

				function changeCollForm(f){
					var dbElements = document.getElementsByName("db[]");
					var c = false;
					var collid = "";
					for(i = 0; i < dbElements.length; i++){
						var dbElement = dbElements[i];
						if(dbElement.checked && !isNaN(dbElement.value)){
							if(c == true) collid = collid+",";
							collid = collid + dbElement.value;
							c = true;
						}
					}
					if(c == true){
						var collobj = document.getElementById("colltxt");
						collobj.value = collid;
						document.getElementById("collform").submit();
					}
					else{
						alert("Please choose at least one collection!");
						return false;
					}
				}
			</script>
		</head>
		<body>
			<?php
			$displayLeftMenu = (isset($collections_misc_collstatsMenu)?$collections_misc_collstatsMenu:false);
			include($serverRoot.'/header.php');
			if(isset($collections_misc_collstatsCrumbs)){
				if($collections_misc_collstatsCrumbs){
					echo "<div class='navpath'>";
					echo "<a href='../../index.php'>Home</a> &gt;&gt; ";
					echo $collections_misc_collstatsCrumbs.' &gt;&gt; ';
					echo "<b>Collection Statistics</b>";
					echo "</div>";
				}
			}
			else{
				?>
				<div class='navpath'>
					<a href='../../index.php'>Home</a> &gt;&gt;
					<a href='collprofiles.php'>Collections</a> &gt;&gt;
					<b>Collection Statistics</b>
				</div>
				<?php
			}
			?>
			<!-- This is inner text! -->
			<div id="innertext">
				<h1>Select Collections to be Analyzed</h1>
				<div id="tabs" style="margin:0px;">
					<ul>
						<li><a href="#specobsdiv">Collections</a></li>
						<?php
						if($action == "Run Statistics"){
							echo '<li><a href="#statsdiv">Statistics</a></li>';
						}
						?>
					</ul>

					<div id="specobsdiv">
						<?php
						if($specArr || $obsArr){
							?>
							<form name="collections" id="collform" action="collstats.php" method="post" onsubmit="return changeCollForm(this);">
								<div style="margin:0px 0px 10px 20px;">
									<input id="dballcb" name="db[]" class="specobs" value='all' type="checkbox" onclick="selectAll(this);" />
									Select/Deselect all <a href="collprofiles.php">Collections</a>
								</div>
								<?php
								$collArrIndex = 0;
								if($specArr){
									$collCnt = 0;
									if(isset($specArr['cat'])){
										$categoryArr = $specArr['cat'];
										?>
										<div style="float:right;margin-top:20px;margin-bottom:10px;">
											<div>
												<input type="submit" name="submitaction" value="Run Statistics" />
											</div>
											<?php
											if($SYMB_UID && $IS_ADMIN){
												?>
												<div style="clear:both;margin-top:8px;">
													<input type="submit" name="submitaction" value="Update Statistics" />
												</div>
												<?php
											}
											?>
										</div>
										<table style="float:left;width:80%;">
											<?php
											$cnt = 0;
											foreach($categoryArr as $catid => $catArr){
												$name = $catArr['name'];
												if($catArr['acronym']) $name .= ' ('.$catArr['acronym'].')';
												$catIcon = $catArr['icon'];
												unset($catArr['name']);
												unset($catArr['acronym']);
												unset($catArr['icon']);
												$idStr = $collArrIndex.'-'.$catid;
												?>
												<tr>
													<td style="padding:6px;width:25px;">
														<input id="cat-<?php echo $idStr; ?>-Input" name="cat[]" value="<?php echo $catid; ?>" type="checkbox" onclick="selectAllCat(this,'cat-<?php echo $idStr; ?>')" <?php echo ($collIdArr&&($collIdArr==array_keys($catArr))?'checked':''); ?> />
													</td>
													<td style="padding:9px 5px;width:10px;">
														<a href="#" onclick="toggleCat('<?php echo $idStr; ?>');return false;">
															<img id="plus-<?php echo $idStr; ?>" src="../../images/plus_sm.png" style="<?php echo ($collIdArr&&array_intersect($collIdArr,array_keys($catArr))?'display:none;':($cnt||$collIdArr?'':'display:none;')); ?>" /><img id="minus-<?php echo $idStr; ?>" src="../../images/minus_sm.png" style="<?php echo ($collIdArr&&array_intersect($collIdArr,array_keys($catArr))?'':($cnt||$collIdArr?'display:none;':'')); ?>" />
														</a>
													</td>
													<td style="padding-top:8px;">
														<div class="categorytitle">
															<a href="#" onclick="toggleCat('<?php echo $idStr; ?>');return false;">
																<?php echo $name; ?>
															</a>
														</div>
													</td>
												</tr>
												<tr>
													<td colspan="4">
														<div id="cat-<?php echo $idStr; ?>" style="<?php echo ($collIdArr&&array_intersect($collIdArr,array_keys($catArr))?'':($cnt||$collIdArr?'display:none;':'')); ?>margin:10px;padding:10px 20px;border:inset">
															<table>
																<?php
																foreach($catArr as $collid => $collName2){
																	?>
																	<tr>
																		<td style="padding:6px;width:25px;">
																			<input name="db[]" value="<?php echo $collid; ?>" type="checkbox" class="cat-<?php echo $idStr; ?>" onclick="unselectCat('cat-<?php echo $idStr; ?>-Input')" <?php echo ($collIdArr&&in_array($collid,$collIdArr)?'checked':''); ?> />
																		</td>
																		<td style="padding:6px">
																			<div class="collectiontitle">
																				<a href='collprofiles.php?collid=<?php echo $collid; ?>'>
																					<?php
																					$codeStr = ' ('.$collName2['instcode'];
																					if($collName2['collcode']) $codeStr .= '-'.$collName2['collcode'];
																					$codeStr .= ')';
																					echo $collName2["collname"].$codeStr;
																					?>
																				</a>
																				<a href='collprofiles.php?collid=<?php echo $collid; ?>' style='font-size:75%;'>
																					more info
																				</a>
																			</div>
																		</td>
																	</tr>
																	<?php
																	$collCnt++;
																}
																?>
															</table>
														</div>
													</td>
												</tr>
												<?php
												$cnt++;
											}
											?>
										</table>
										<?php
									}
									if(isset($specArr['coll'])){
										$collArr = $specArr['coll'];
										?>
										<table style="float:left;width:80%;">
											<?php
											foreach($collArr as $collid => $cArr){
												?>
												<tr>
													<td style="padding:6px;width:25px;">
														<input name="db[]" value="<?php echo $collid; ?>" type="checkbox" onclick="uncheckAll();" <?php echo ($collIdArr&&in_array($collid,$collIdArr)?'checked':''); ?> />
													</td>
													<td style="padding:6px">
														<div class="collectiontitle">
															<a href='collprofiles.php?collid=<?php echo $collid; ?>'>
																<?php
																$codeStr = ' ('.$cArr['instcode'];
																if($cArr['collcode']) $codeStr .= '-'.$cArr['collcode'];
																$codeStr .= ')';
																echo $cArr["collname"].$codeStr;
																?>
															</a>
															<a href='collprofiles.php?collid=<?php echo $collid; ?>' style='font-size:75%;'>
																more info
															</a>
														</div>
													</td>
												</tr>
												<?php
												$collCnt++;
											}
											?>
										</table>
										<div style="float:right;margin-top:20px;margin-bottom:10px;">
											<div>
												<input type="submit" name="submitaction" value="Run Statistics" />
											</div>
											<?php
											if($SYMB_UID && $IS_ADMIN){
												?>
												<div style="clear:both;margin-top:8px;">
													<input type="submit" name="submitaction" value="Update Statistics" />
												</div>
												<?php
											}
											?>
										</div>
										<?php
									}
									$collArrIndex++;
								}
								if($specArr && $obsArr) echo '<hr style="clear:both;margin:20px 0px;"/>';
								if($obsArr){
									$collCnt = 0;
									if(isset($obsArr['cat'])){
										$categoryArr = $obsArr['cat'];
										?>
										<div style="float:right;margin-top:20px;margin-bottom:10px;">
											<div>
												<input type="submit" name="submitaction" value="Run Statistics" />
											</div>
											<?php
											if($SYMB_UID && $IS_ADMIN){
												?>
												<div style="clear:both;margin-top:8px;">
													<input type="submit" name="submitaction" value="Update Statistics" />
												</div>
												<?php
											}
											?>
										</div>
										<table style="float:left;width:80%;">
											<?php
											$cnt = 0;
											foreach($categoryArr as $catid => $catArr){
												$name = $catArr['name'];
												if($catArr['acronym']) $name .= ' ('.$catArr['acronym'].')';
												$catIcon = $catArr['icon'];
												unset($catArr['name']);
												unset($catArr['acronym']);
												unset($catArr['icon']);
												$idStr = $collArrIndex.'-'.$catid;
												?>
												<tr>
													<td style="padding:6px;width:25px;">
														<input id="cat-<?php echo $idStr; ?>-Input" name="cat[]" value="<?php echo $catid; ?>" type="checkbox" onclick="selectAllCat(this,'cat-<?php echo $idStr; ?>')" <?php echo ($collIdArr&&($collIdArr==array_keys($catArr))?'checked':''); ?> />
													</td>
													<td style="padding:9px 5px;width:10px;">
														<a href="#" onclick="toggleCat('<?php echo $idStr; ?>');return false;">
															<img id="plus-<?php echo $idStr; ?>" src="../../images/plus_sm.png" style="<?php echo ($collIdArr&&array_intersect($collIdArr,array_keys($catArr))?'display:none;':($cnt||$collIdArr?'':'display:none;')); ?>" /><img id="minus-<?php echo $idStr; ?>" src="../../images/minus_sm.png" style="<?php echo ($collIdArr&&array_intersect($collIdArr,array_keys($catArr))?'':($cnt||$collIdArr?'display:none;':'')); ?>" />
														</a>
													</td>
													<td style="padding-top:8px;">
														<div class="categorytitle">
															<a href="#" onclick="toggleCat('<?php echo $idStr; ?>');return false;">
																<?php echo $name; ?>
															</a>
														</div>
													</td>
												</tr>
												<tr>
													<td colspan="4">
														<div id="cat-<?php echo $idStr; ?>" style="<?php echo ($collIdArr&&array_intersect($collIdArr,array_keys($catArr))?'':($cnt||$collIdArr?'display:none;':'')); ?>margin:10px;padding:10px 20px;border:inset">
															<table>
																<?php
																foreach($catArr as $collid => $collName2){
																	?>
																	<tr>
																		<td style="padding:6px;width:25px;">
																			<input name="db[]" value="<?php echo $collid; ?>" type="checkbox" class="cat-<?php echo $idStr; ?>" onclick="unselectCat('cat-<?php echo $idStr; ?>-Input')" <?php echo ($collIdArr&&in_array($collid,$collIdArr)?'checked':''); ?> />
																		</td>
																		<td style="padding:6px">
																			<div class="collectiontitle">
																				<a href = 'collprofiles.php?collid=<?php echo $collid; ?>'>
																					<?php
																					$codeStr = ' ('.$collName2['instcode'];
																					if($collName2['collcode']) $codeStr .= '-'.$collName2['collcode'];
																					$codeStr .= ')';
																					echo $collName2["collname"].$codeStr;
																					?>
																				</a>
																				<a href = 'collprofiles.php?collid=<?php echo $collid; ?>' style='font-size:75%;'>
																					more info
																				</a>
																			</div>
																		</td>
																	</tr>
																	<?php
																	$collCnt++;
																}
																?>
															</table>
														</div>
													</td>
												</tr>
												<?php
												$cnt++;
											}
											?>
										</table>
										<?php
									}
									if(isset($obsArr['coll'])){
										$collArr = $obsArr['coll'];
										?>
										<table style="float:left;width:80%;">
											<?php
											foreach($collArr as $collid => $cArr){
												?>
												<tr>
													<td style="padding:6px;width:25px;">
														<input name="db[]" value="<?php echo $collid; ?>" type="checkbox" onclick="uncheckAll();" <?php echo ($collIdArr&&in_array($collid,$collIdArr)?'checked':''); ?> />
													</td>
													<td style="padding:6px">
														<div class="collectiontitle">
															<a href = 'collprofiles.php?collid=<?php echo $collid; ?>'>
																<?php
																$codeStr = ' ('.$cArr['instcode'];
																if($cArr['collcode']) $codeStr .= '-'.$cArr['collcode'];
																$codeStr .= ')';
																echo $cArr["collname"].$codeStr;
																?>
															</a>
															<a href = 'collprofiles.php?collid=<?php echo $collid; ?>' style='font-size:75%;'>
																more info
															</a>
														</div>
													</td>
												</tr>
												<?php
												$collCnt++;
											}
											?>
										</table>
										<div style="float:right;margin-top:20px;margin-bottom:10px;">
											<div>
												<input type="submit" name="submitaction" value="Run Statistics" />
											</div>
											<?php
											if($SYMB_UID && $IS_ADMIN){
												?>
												<div style="clear:both;margin-top:8px;">
													<input type="submit" name="submitaction" value="Update Statistics" />
												</div>
												<?php
											}
											?>
										</div>
										<?php
									}
									$collArrIndex++;
								}
								?>
								<div style="clear:both;">&nbsp;</div>
								<input type="hidden" name="collid" id="colltxt" value="" />
							</form>
							<?php
						}
						else{
							echo '<div style="margin-top:10px;"><div style="font-weight:bold;font-size:120%;">There are currently no collections to analyze.</div></div>';
						}
						?>
					</div>

					<?php
					if($action == "Run Statistics"){
						?>
						<div id="statsdiv">
							<div style="min-height:300px;">
								<div style="height:100%;">
									<h1>Selected Collection Statistics</h1>
									<div style="font-weight:bold;font-size:105%;margin:10px;">
										<div id="colllistlabel"><a href="#" onclick="toggle('colllist');toggle('colllistlabel');">Display List of Collections Analyzed</a></div>
										<div id="colllist" style="display:none">
											<?php echo $collStr; ?>
										</div>
									</div>
									<fieldset style="float:left;width:400px;margin-bottom:15px;margin-right:15px;">
										<ul style="margin:0px;padding-left:10px;">
											<?php
											echo "<li>";
											echo ($results['SpecimenCount']?number_format($results['SpecimenCount']):0)." specimen records";
											echo "</li>";
											echo "<li>";
											$percGeo = '';
											if($results['SpecimenCount'] && $results['GeorefCount']){
												$percGeo = (100* ($results['GeorefCount'] / $results['SpecimenCount']));
											}
											echo ($results['GeorefCount']?number_format($results['GeorefCount']):0).($percGeo?" (".($percGeo>1?round($percGeo):round($percGeo,2))."%)":'')." georeferenced";
											echo "</li>";
											echo "<li>";
											$percImg = '';
											if($results['SpecimenCount'] && $results['TotalImageCount']){
												$percImg = (100* ($results['TotalImageCount'] / $results['SpecimenCount']));
											}
											echo ($results['TotalImageCount']?number_format($results['TotalImageCount']):0).($percImg?" (".($percImg>1?round($percImg):round($percImg,2))."%)":'')." imaged";
											echo "</li>";
											echo "<li>";
											$percId = '';
											if($results['SpecimenCount'] && $results['SpecimensCountID']){
												$percId = (100* ($results['SpecimensCountID'] / $results['SpecimenCount']));
											}
											echo ($results['SpecimensCountID']?number_format($results['SpecimensCountID']):0).($percId?" (".($percId>1?round($percId):round($percId,2))."%)":'')." identified to species";
											echo "</li>";
											echo "<li>";
											echo ($results['FamilyCount']?number_format($results['FamilyCount']):0)." families";
											echo "</li>";
											echo "<li>";
											echo ($results['GeneraCount']?number_format($results['GeneraCount']):0)." genera";
											echo "</li>";
											echo "<li>";
											echo ($results['SpeciesCount']?number_format($results['SpeciesCount']):0)." species";
											echo "</li>";
											echo "<li>";
											echo ($results['TotalTaxaCount']?number_format($results['TotalTaxaCount']):0)." total taxa (including subsp. and var.)";
											echo "</li>";
											/*echo "<li>";
											echo ($results['TypeCount']?number_format($results['TypeCount']):0)." type specimens";
											echo "</li>";*/
											?>
										</ul>
										<form name="statscsv" id="statscsv" action="collstatscsv.php" method="post" onsubmit="">
											<div style="margin-top:8px;">
												<div id="showstatspercoll" style="float:left;display:block;" >
													<a href="#" onclick="return toggleStatsPerColl()">Show Statistics per Collection</a>
												</div>
												<div id="hidestatspercoll" style="float:left;display:none;" >
													<a href="#" onclick="return toggleStatsPerColl()">Hide Statistics per Collection</a>
												</div>
												<div style='float:left;margin-left:6px;width:16px;height:16px;padding:2px;' title="Save CSV">
													<input type="hidden" name="collids" id="collids" value='<?php echo $collId; ?>' />
													<input type="hidden" name="action" id="action" value='Download Stats per Coll' />
													<input type="image" name="action" src="../../images/dl.png" onclick="" />
													<!--input type="submit" name="action" value="Download Stats per Coll" src="../../images/dl.png" / -->
												</div>
											</div>
										</form>
									</fieldset>
									<div style="">
										<fieldset style="width:275px;margin:20px 0px 10px 20px;background-color:#FFFFCC;">
											<form name="famstatscsv" id="famstatscsv" action="collstatscsv.php" method="post" onsubmit="">
												<div class='legend'><b>Extra Statistics</b></div>
												<div style="margin-top:8px;">
													<div id="showfamdist" style="float:left;display:block;" >
														<a href="#" onclick="return toggleFamilyDist()">Show Family Distribution</a>
													</div>
													<div id="hidefamdist" style="float:left;display:none;" >
														<a href="#" onclick="return toggleFamilyDist()">Hide Family Distribution</a>
													</div>
													<div style='float:left;margin-left:6px;width:16px;height:16px;padding:2px;' title="Save CSV">
														<input type="hidden" name="action" value='Download Family Dist' />
														<input type="image" name="action" src="../../images/dl.png" onclick="" />
													</div>
												</div>
												<input type="hidden" name="famarrjson" id="famarrjson" value='<?php echo json_encode($familyArr); ?>' />
											</form>
											<form name="geostatscsv" id="geostatscsv" action="collstatscsv.php" method="post" onsubmit="">
												<div style="clear:both;">
													<div id="showgeodist" style="float:left;display:block;" >
														<a href="#" onclick="return toggleGeoDist()">Show Geographic Distribution</a>
													</div>
													<div id="hidegeodist" style="float:left;display:none;" >
														<a href="#" onclick="return toggleGeoDist();">Hide Geographic Distribution</a>
													</div>
													<div style='float:left;margin-left:6px;width:16px;height:16px;padding:2px;' title="Save CSV">
														<input type="hidden" name="action" value='Download Geo Dist' />
														<input type="image" name="action" src="../../images/dl.png" onclick="" />
													</div>
												</div>
												<input type="hidden" name="geoarrjson" id="geoarrjson" value='<?php echo json_encode($countryArr); ?>' />
											</form>
										</fieldset>
										<?php
										if($SYMB_UID && ($IS_ADMIN || array_key_exists("CollAdmin",$USER_RIGHTS))){
											?>
											<div style="">
												<form name="yearstats" style="margin-bottom:0px" action="collyearstats.php" method="post" target="_blank" onsubmit="">
													<input type="hidden" name="collid" id="collid" value='<?php echo $collId; ?>' />
													<input type="submit" name="action" value="Load Stats for Past Year" />
												</form>
											</div>
											<div style="margin-top:10px;">
												<form name="orderstats" style="margin-bottom:0px" action="collorderstats.php" method="post" target="_blank" onsubmit="">
													<input type="hidden" name="collid" id="collid" value='<?php echo $collId; ?>' />
													<input type="hidden" name="totalcnt" id="totalcnt" value='<?php echo $results['SpecimenCount']; ?>' />
													<input type="submit" name="action" value="Load Order Distribution" />
												</form>
											</div>
											<?php
										}
										?>
									</div>
									<div style="clear:both;"> </div>
								</div>

								<fieldset id="statspercollbox" style="clear:both;margin-top:15px;width:90%;display:none;">
									<legend><b>Statistics per Collection</b></legend>
									<table class="styledtable" style="font-family:Arial;font-size:12px;">
										<tr>
											<th style="text-align:center;">Collection</th>
											<th style="text-align:center;">Specimens</th>
											<th style="text-align:center;">Georeferenced</th>
											<th style="text-align:center;">Imaged</th>
											<th style="text-align:center;">Species ID</th>
											<th style="text-align:center;">Families</th>
											<th style="text-align:center;">Genera</th>
											<th style="text-align:center;">Species</th>
											<th style="text-align:center;">Total Taxa</th>
											<!-- <th style="text-align:center;">Types</th> -->
										</tr>
										<?php
										foreach($resultsTemp as $name => $data){
											echo '<tr>';
											echo '<td>'.wordwrap($name,40,"<br />\n",true).'</td>';
											echo '<td>'.(array_key_exists('recordcnt',$data)?$data['recordcnt']:0).'</td>';
											echo '<td>'.(array_key_exists('georefcnt',$data)?$data['georefcnt']:0).'</td>';
											echo '<td>'.(array_key_exists('OccurrenceImageCount',$data)?$data['OccurrenceImageCount']:0).'</td>';
											echo '<td>'.(array_key_exists('speciesID',$data)?$data['speciesID']:0).'</td>';
											echo '<td>'.(array_key_exists('familycnt',$data)?$data['familycnt']:0).'</td>';
											echo '<td>'.(array_key_exists('genuscnt',$data)?$data['genuscnt']:0).'</td>';
											echo '<td>'.(array_key_exists('speciescnt',$data)?$data['speciescnt']:0).'</td>';
											echo '<td>'.(array_key_exists('TotalTaxaCount',$data)?$data['TotalTaxaCount']:0).'</td>';
											//echo '<td>'.(array_key_exists('types',$data)?$data['types']:0).'</td>';
											echo '</tr>';
										}
										?>
									</table>
								</fieldset>
								<fieldset id="famdistbox" style="clear:both;margin-top:15px;width:800px;display:none;">
									<legend><b>Family Distribution</b></legend>
									<table class="styledtable" style="font-family:Arial;font-size:12px;width:780px;">
										<tr>
											<th style="text-align:center;">Family</th>
											<th style="text-align:center;">Specimens</th>
											<th style="text-align:center;">Georeferenced</th>
											<th style="text-align:center;">Species ID</th>
											<th style="text-align:center;">Georeferenced<br />and<br />Species ID</th>
										</tr>
										<?php
										$total = 0;
										foreach($familyArr as $name => $data){
											echo '<tr>';
											echo '<td>'.wordwrap($name,52,"<br />\n",true).'</td>';
											echo '<td>';
											if(count($resultsTemp) == 1){
												echo '<a href="../list.php?db[]='.$collId.'&reset=1&taxa='.$name.'" target="_blank">';
											}
											echo number_format($data['SpecimensPerFamily']);
											if(count($resultsTemp) == 1){
												echo '</a>';
											}
											echo '</td>';
											echo '<td>'.($data['GeorefSpecimensPerFamily']?round(100*($data['GeorefSpecimensPerFamily']/$data['SpecimensPerFamily'])):0).'%</td>';
											echo '<td>'.($data['IDSpecimensPerFamily']?round(100*($data['IDSpecimensPerFamily']/$data['SpecimensPerFamily'])):0).'%</td>';
											echo '<td>'.($data['IDGeorefSpecimensPerFamily']?round(100*($data['IDGeorefSpecimensPerFamily']/$data['SpecimensPerFamily'])):0).'%</td>';
											echo '</tr>';
											$total = $total + $data['SpecimensPerFamily'];
										}
										?>
									</table>
									<div style="margin-top:10px;">
										<b>Total Specimens with Family:</b> <?php echo number_format($total); ?><br />
										Specimens without Family: <?php echo number_format($results['SpecimenCount']-$total); ?><br />
									</div>
								</fieldset>
								<fieldset id="geodistbox" style="margin-top:15px;width:800px;display:none;">
									<legend><b>Geographic Distribution</b></legend>
									<table class="styledtable" style="font-family:Arial;font-size:12px;width:780px;">
										<tr>
											<th style="text-align:center;">Country</th>
											<th style="text-align:center;">Specimens</th>
											<th style="text-align:center;">Georeferenced</th>
											<th style="text-align:center;">Species ID</th>
											<th style="text-align:center;">Georeferenced<br />and<br />Species ID</th>
										</tr>
										<?php
										$total = 0;
										foreach($countryArr as $name => $data){
											echo '<tr>';
											echo '<td>'.wordwrap($name,52,"<br />\n",true).'</td>';
											echo '<td>';
											if(count($resultsTemp) == 1){
												echo '<a href="../list.php?db[]='.$collId.'&reset=1&country='.$name.'" target="_blank">';
											}
											echo number_format($data['CountryCount']);
											if(count($resultsTemp) == 1){
												echo '</a>';
											}
											echo '</td>';
											echo '<td>'.($data['GeorefSpecimensPerCountry']?round(100*($data['GeorefSpecimensPerCountry']/$data['CountryCount'])):0).'%</td>';
											echo '<td>'.($data['IDSpecimensPerCountry']?round(100*($data['IDSpecimensPerCountry']/$data['CountryCount'])):0).'%</td>';
											echo '<td>'.($data['IDGeorefSpecimensPerCountry']?round(100*($data['IDGeorefSpecimensPerCountry']/$data['CountryCount'])):0).'%</td>';
											echo '</tr>';
											$total = $total + $data['CountryCount'];
										}
										?>
									</table>
									<div style="margin-top:10px;">
										<b>Total Specimens with Country:</b> <?php echo number_format($total); ?><br />
										Specimens without Country or Georeferencing: <?php echo number_format(($results['SpecimenCount']-$total)+$results['SpecimensNullLatitude']); ?><br />
									</div>
								</fieldset>
							</div>
						</div>
						<?php
					}
					?>
				</div>
			</div>
			<!-- end inner text -->
			<?php
				include($serverRoot.'/footer.php');
			?>
		</body>
	</html>
	<?php
}
?>