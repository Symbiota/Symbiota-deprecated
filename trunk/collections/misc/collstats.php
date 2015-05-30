<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/CollectionProfileManager.php');
header("Content-Type: text/html; charset=".$charset);
ini_set('max_execution_time', 600); //600 seconds = 10 minutes

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
		$collIdArr = explode(",",$collId);
		$resultsTemp = $collManager->runStatistics($collId);
		$results['FamilyCount'] = $resultsTemp['familycnt'];
		$results['GeneraCount'] = $resultsTemp['genuscnt'];
		$results['SpeciesCount'] = $resultsTemp['speciescnt'];
		$results['TotalTaxaCount'] = $resultsTemp['TotalTaxaCount'];
		unset($resultsTemp['familycnt']);
		unset($resultsTemp['genuscnt']);
		unset($resultsTemp['speciescnt']);
		unset($resultsTemp['TotalTaxaCount']);
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
				
				if(array_key_exists("SpecimensNullFamily",$results)){
					$results['SpecimensNullFamily'] = $results['SpecimensNullFamily'] + $dynPropTempArr['SpecimensNullFamily'];
				}
				else{
					$results['SpecimensNullFamily'] = $dynPropTempArr['SpecimensNullFamily'];
				}
				
				if(array_key_exists("SpecimensNullCountry",$results)){
					$results['SpecimensNullCountry'] = $results['SpecimensNullCountry'] + $dynPropTempArr['SpecimensNullCountry'];
				}
				else{
					$results['SpecimensNullCountry'] = $dynPropTempArr['SpecimensNullCountry'];
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
					ksort($familyArr,SORT_STRING | SORT_FLAG_CASE);
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
					ksort($countryArr,SORT_STRING | SORT_FLAG_CASE);
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
						if(dbElement.checked){
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
			$displayLeftMenu = (isset($collections_misc_collstatsMenu)?$collections_misc_collstatsMenu:true);
			include($serverRoot.'/header.php');
			if(isset($collections_misc_collstatsCrumbs)){
				if($collections_misc_collstatsCrumbs){
					echo "<div class='navpath'>";
					echo "<a href='../../../index.php'>Home</a> &gt;&gt; ";
					echo $collections_misc_collstatsCrumbs.' &gt;&gt; ';
					echo "<b>Collection Statistics</b>";
					echo "</div>";
				}
			}
			else{
				?>
				<div class='navpath'>
					<a href='../../../index.php'>Home</a> &gt;&gt;
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
									Select/Deselect all <a href="<?php echo $clientRoot; ?>/collections/collprofiles.php">Collections</a>
								</div>
								<?php 
								$collArrIndex = 0;
								if($specArr){
									$collCnt = 0;
									if(isset($specArr['cat'])){
										$categoryArr = $specArr['cat'];
										?>
										<div style="float:right;margin-top:20px;">
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
										<div style="float:right;margin-top:20px;">
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
										<div style="float:right;margin-top:20px;">
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
										<div style="float:right;margin-top:20px;">
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
									<h2 style="font-size:105%">
										<?php 
											echo $collStr." - Analysis Statistics gathered";
										?> 
									</h2>
									<fieldset style="float:left;width:450px;margin-bottom:15px;">
										<ul style="margin:0px;padding-left:10px;">
											<?php
											echo "<li>";
											echo ($results['SpecimenCount']?$results['SpecimenCount']:0)." specimens";
											echo "</li>";
											echo "<li>";
											$percGeo = '';
											if($results['SpecimenCount'] && $results['GeorefCount']){
												$percGeo = (100* ($results['GeorefCount'] / $results['SpecimenCount']));
											}
											echo ($results['GeorefCount']?$results['GeorefCount']:0).($percGeo?" (".($percGeo>1?round($percGeo):round($percGeo,2))."%)":'')." georeferenced";
											echo "</li>";
											echo "<li>";
											$percId = '';
											if($results['SpecimenCount'] && $results['SpecimensCountID']){
												$percId = (100* ($results['SpecimensCountID'] / $results['SpecimenCount']));
											}
											echo ($results['SpecimensCountID']?$results['SpecimensCountID']:0).($percId?" (".($percId>1?round($percId):round($percId,2))."%)":'')." identified to species";
											echo "</li>";
											echo "<li>";
											echo ($results['FamilyCount']?$results['FamilyCount']:0)." families";
											echo "</li>";
											echo "<li>";
											echo ($results['GeneraCount']?$results['GeneraCount']:0)." genera";
											echo "</li>";
											echo "<li>";
											echo ($results['SpeciesCount']?$results['SpeciesCount']:0)." species";
											echo "</li>";
											echo "<li>";
											echo ($results['TotalTaxaCount']?$results['TotalTaxaCount']:0)." total taxa (including subsp. and var.)";
											echo "</li>";
											echo "<li>";
											echo ($results['TypeCount']?$results['TypeCount']:0)." type specimens";
											echo "</li>";
											?>
										</ul>
									</fieldset>
									<fieldset style="float:right;width:300px;margin:20px 0px 20px 20px;background-color:#FFFFCC;">
										<form name="statscsv" id="statscsv" action="collstatscsv.php" method="post" onsubmit="">
											<div class='legend'><b>Extra Statistics</b></div>
											<div style="margin-top:8px;">
												<div id="showfamdist" style="float:left;display:block;" >
													<a href="#" onclick="toggleById('famdistbox');toggleById('showfamdist');toggleById('hidefamdist');return false;">Show Family Distribution</a>
												</div>
												<div id="hidefamdist" style="float:left;display:none;" >
													<a href="#" onclick="toggleById('famdistbox');toggleById('showfamdist');toggleById('hidefamdist');return false;">Hide Family Distribution</a>
												</div>
												<div style='float:left;margin-left:6px;width:16px;height:16px;padding:2px;' title="Save CSV">
													<input type="image" name="action" value="Download Family Dist" src="../../images/dl.png" onclick="" />
												</div>
											</div>
											<div style="clear:both;">
												<div id="showgeodist" style="float:left;display:block;" >
													<a href="#" onclick="toggleById('geodistbox');toggleById('showgeodist');toggleById('hidegeodist');return false;">Show Geographic Distribution</a>
												</div>
												<div id="hidegeodist" style="float:left;display:none;" >
													<a href="#" onclick="toggleById('geodistbox');toggleById('showgeodist');toggleById('hidegeodist');return false;">Hide Geographic Distribution</a>
												</div>
												<div style='float:left;margin-left:6px;width:16px;height:16px;padding:2px;' title="Save CSV">
													<input type="image" name="action" value="Download Geo Dist" src="../../images/dl.png" onclick="" />
												</div>
											</div>
											<input type="hidden" name="famarrjson" id="famarrjson" value='<?php echo json_encode($familyArr); ?>' />
											<input type="hidden" name="geoarrjson" id="geoarrjson" value='<?php echo json_encode($countryArr); ?>' />
										</form>
									</fieldset>
									<div style="clear:both;"> </div>
								</div>
								<fieldset id="famdistbox" style="clear:both;margin-top:15px;width:800px;display:none;">
									<legend><b>Family Distribution</b></legend>
									<ul style="float: left">
										<strong>Names</strong>
										<?php
										foreach($familyArr as $name => $data){
											echo '<li>'.$name.'</li>';
										}
										echo "<br />";
										echo "<li><strong>Total Specimens (w/ Family):</strong></li>";
										echo "<li>Specimens w/ No Family:</li>";
										?>
									</ul>
									<ul style="float:left;list-style-type:none;margin-left:-20px;">
										<strong>Specimens</strong>
										<?php
										$total = 0;
										foreach($familyArr as $name => $data){
											echo '<li>';
											if(count($resultsTemp) == 1){
												echo '<a href="../list.php?db[]='.$collId.'&reset=1&taxa='.$name.'" target="_blank">';
											}
											echo $data['SpecimensPerFamily'];
											if(count($resultsTemp) == 1){
												echo '</a>';
											}
											echo '</li>';
											$total = $total + $data['SpecimensPerFamily'];
										}
										echo "<br />";
										echo "<li><strong>".$total."</strong></li>";
										echo "<li>".$results['SpecimensNullFamily']."</li>";
										?>
									</ul>
									<ul style="float:left;list-style-type:none;margin-left:-20px;">
										<strong>Georef</strong>
										<?php
										foreach($familyArr as $name => $data){
											echo '<li>'.($data['GeorefSpecimensPerFamily']?round(100*($data['GeorefSpecimensPerFamily']/$data['SpecimensPerFamily'])):0).'%</li>';
										}
										?>
									</ul>
									<ul style="float:left;list-style-type:none;margin-left:-20px;">
										<strong>Species ID</strong>
										<?php
										foreach($familyArr as $name => $data){
											echo '<li>'.($data['IDSpecimensPerFamily']?round(100*($data['IDSpecimensPerFamily']/$data['SpecimensPerFamily'])):0).'%</li>';
										}
										?>
									</ul>
									<ul style="float:left;list-style-type:none;margin-left:-20px;">
										<strong>Georef and ID</strong>
										<?php
										foreach($familyArr as $name => $data){
											echo '<li>'.($data['IDGeorefSpecimensPerFamily']?round(100*($data['IDGeorefSpecimensPerFamily']/$data['SpecimensPerFamily'])):0).'%</li>';
										}
										?>
									</ul>
								</fieldset>
								<fieldset id="geodistbox" style="margin-top:15px;width:800px;display:none;">
									<legend><b>Geographic Distribution</b></legend>
									<ul style="float: left">
										<strong>Names</strong>
										<?php
										foreach($countryArr as $name => $data){
											echo '<li>'.$name.'</li>';
										}
										echo "<br />";
										echo "<li><strong>Total Specimens (w/ Country):</strong></li>";
										echo "<li>Specimens w/ No Country/Georef:</li>";
										?>
									</ul>
									<ul style="float:left;list-style-type:none;margin-left:-20px;">
										<strong>Specimens</strong>
										<?php
										$total = 0;
										foreach($countryArr as $name => $data){
											echo '<li>';
											if(count($resultsTemp) == 1){
												echo '<a href="../list.php?db[]='.$collId.'&reset=1&country='.$name.'" target="_blank">';
											}
											echo $data['CountryCount'];
											if(count($resultsTemp) == 1){
												echo '</a>';
											}
											echo '</li>';
											$total = $total + $data['CountryCount'];
										}
										echo "<br />";
										echo "<li><strong>".$total."</strong></li>";
										echo "<li>".($results['SpecimensNullCountry']+$results['SpecimensNullLatitude'])."</li>";
										?>
									</ul>
									<ul style="float:left;list-style-type:none;margin-left:-20px;">
										<strong>Georef</strong>
										<?php
										foreach($countryArr as $name => $data){
											echo '<li>'.($data['GeorefSpecimensPerCountry']?round(100*($data['GeorefSpecimensPerCountry']/$data['CountryCount'])):0).'%</li>';
										}
										?>
									</ul>
									<ul style="float:left;list-style-type:none;margin-left:-20px;">
										<strong>Species ID</strong>
										<?php
										foreach($countryArr as $name => $data){
											echo '<li>'.($data['IDSpecimensPerCountry']?round(100*($data['IDSpecimensPerCountry']/$data['CountryCount'])):0).'%</li>';
										}
										?>
									</ul>
									<ul style="float:left;list-style-type:none;margin-left:-20px;">
										<strong>Georef and ID</strong>
										<?php
										foreach($countryArr as $name => $data){
											echo '<li>'.($data['IDGeorefSpecimensPerCountry']?round(100*($data['IDGeorefSpecimensPerCountry']/$data['CountryCount'])):0).'%</li>';
										}
										?>
									</ul>
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