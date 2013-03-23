<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ChecklistVoucherAdmin.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0; 
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$startPos = (array_key_exists('start',$_REQUEST)?(int)$_REQUEST['start']:0);
$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:0; 
$sqlFrag = array_key_exists("sqlfrag",$_REQUEST)?$_REQUEST["sqlfrag"]:"";
$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:""; 

$displayMode = (array_key_exists('displaymode',$_REQUEST)?$_REQUEST['displaymode']:0);

$clManager = new ChecklistVoucherAdmin();
$clManager->setClid($clid);

$statusStr = "";
$isEditor = 0;
if($isAdmin || (array_key_exists("ClAdmin",$userRights) && in_array($clid,$userRights["ClAdmin"]))){
	$isEditor = 1;

	if($action == "Create SQL Fragment"){
		$statusStr = $clManager->saveSql($_POST);
	}
	elseif($action == 'Delete SQL Fragment'){
		$statusStr = $clManager->deleteSql();
	}
	elseif($action == 'Add Vouchers'){
		$clManager->linkVouchers($_POST['occids']);
	}
	elseif($action == 'Add Taxa and Vouchers'){
		$clManager->linkTaxaVouchers($_POST['occids'],(array_key_exists('usecurrent',$_POST)?$_POST['usecurrent']:0));
	}
}
?>

<!DOCTYPE html >
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
	<title><?php echo $defaultTitle; ?> Checklist Administration</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css"/>
	<link type="text/css" href="../css/jquery-ui.css" rel="Stylesheet" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript">
		var clid = <?php echo $clid; ?>;
		var tabIndex = <?php echo $tabIndex; ?>;
	</script>
	<script type="text/javascript" src="../js/symb/checklists.voucheradmin.js"></script>
	<style type="text/css">
		li{margin:5px;}
	</style>
</head>

<body>
	<?php
	$displayLeftMenu = false;
	include($serverRoot.'/header.php');
	?>
	<div class="navpath">
		<a href="../index.php">Home</a> &gt;&gt; 
		<a href="checklist.php?cl=<?php echo $clid.'&pid='.$pid; ?>">Return to Checklist</a> &gt;&gt; 
		<b>Checklist Administrator</b>
	</div>

	<!-- This is inner text! -->
	<div id='innertext'>
		<div style="color:#990000;font-size:20px;font-weight:bold;margin:0px 10px 10px 0px;">
			<a href="checklist.php?cl=<?php echo $clid.'&pid='.$pid; ?>">
				<?php echo $clManager->getClName(); ?>
			</a>
		</div>
		<?php 
		if($statusStr){ 
			?>
			<hr />
			<div style="margin:20px;font-weight:bold;color:red;">
				<?php echo $statusStr; ?>
			</div>
			<hr />
			<?php 
		}
		
		if($clid && $isEditor){
			$dynSql = $clManager->getDynamicSql();
			if($dynSql){
				?>
				<div style="margin:10px 0px;">
					<b>Search statement:</b> <?php echo $dynSql; ?>
					<span style="margin-left:10px;"><a href="#" onclick="toggle('sqlbuilderdiv');return false;" title="Edit Search Statement"><img src="../images/edit.png" style="width:15px;border:0px;"/></a></span>
				</div>
				<?php
			}
			else{
				?>
				<div style="margin-left:5px;"> 
					To use the voucher administration functions, it is first necessary to define a search statement (SQL fragment) 
					that will be used to limit occurrence records to those collected within the vacinity of the research area. 
					Click the 'Create SQL Fragment' button to build the search statement using the terms supplied in the form. 
					If needed, your data <a href="mailto:<?php echo $adminEmail; ?>">administrator</a> can aid in 
					establishing more complex searches than can be created within this form.
				</div>
				<?php
			}
			?> 
			<div id="sqlbuilderdiv" style="display:<?php echo ($dynSql?'none':'block'); ?>;margin-top:15px;">
				<fieldset>
					<legend><b>Edit Search Statement</b></legend>
					<form name="sqlbuilderform" action="voucheradmin.php" method="post" onsubmit="return validateSqlFragForm(this);">
						<div style="margin:10px;">
							Use this form to build an SQL fragment that will be used by the voucher management tools to limit occurrence records 
							to those collected within the vacinity of the research area. 
							Click the 'Create SQL Fragment' button to build and save the SQL using the terms supplied in the form. 
							If needed, your data administrator can aid you in establishing more complex SQL fragments than can be 
							created within this form.
						</div>
						<table style="margin:15px;">
							<tr>
								<td>
									<div style="margin:3px;">
										<b>Country:</b>
										<input type="text" name="country" onchange="" />
									</div>
									<div style="margin:3px;">
										<b>State:</b>
										<input type="text" name="state" onchange="" />
									</div>
									<div style="margin:3px;">
										<b>County:</b>
										<input type="text" name="county" onchange="" />
									</div>
									<div style="margin:3px;">
										<b>Locality:</b>
										<input type="text" name="locality" onchange="" />
									</div>
									<div style="margin:3px;">
										<input name="excludecult" value="1" type="checkbox" checked /> 
										Exclude cultivated species
									</div>
									<div style="margin:20px;">
										<input type="submit" name="submitaction" value="Create SQL Fragment" />
										<input type='hidden' name='clid' value='<?php echo $clid; ?>' />
										<input type='hidden' name='pid' value='<?php echo $pid; ?>' />
									</div>
								</td>
								<td style="padding-left:20px;">
									<div style="float:left;">
										<div>
											<b>Lat North:</b>
											<input id="upperlat" type="text" name="latnorth" style="width:70px;" onchange="" title="Latitude North" /> 
											<a href="#" onclick="openPopup('../collections/mapboundingbox.php','boundingbox')"><img src="../images/world40.gif" width="15px" title="Find Coordinate" /></a>
										</div>
										<div>
											<b>Lat South:</b>
											<input id="bottomlat" type="text" name="latsouth" style="width:70px;" onchange="" title="Latitude South" />
										</div>
										<div>
											<b>Long East:</b>
											<input id="rightlong" type="text" name="lngeast" style="width:70px;" onchange="" title="Longitude East" />
										</div>
										<div>
											<b>Long West:</b>
											<input id="leftlong" type="text" name="lngwest" style="width:70px;" onchange="" title="Longitude West" />
										</div>
										<div>
											<input type="checkbox" name="latlngor" value="1" />
											Include Lat/Long as an "OR" condition
										</div>
									</div>
								</td>
							</tr>
						</table>
					</form>
				</fieldset>
				<?php 
				if($dynSql){
					?>
					<fieldset>
						<legend><b>Remove Search Statement</b></legend>
						<form name="sqldeleteform" action="voucheradmin.php" method="post" onsubmit="return confirm('Are you sure you want to delete current SQL statement?');">
							<div style="margin:20px">
								<input type="submit" name="submitaction" value="Delete SQL Fragment" />
							</div>
							<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
							<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
						</form>
					</fieldset>
					<?php
				}
				?>
			</div>
			<?php 
			if($dynSql){
				?>
				<div id="tabs" style="margin-top:25px;">
				    <ul>
				        <li><a href="#nonVoucheredDiv"><span>Non-Vouchered Taxa</span></a></li>
				        <li><a href="vamissingtaxa.php?clid=<?php echo $clid.'&pid='.$pid.'&start='.$startPos.'&displaymode='.($tabIndex==1?$displayMode:0); ?>"><span>Missing Taxa</span></a></li>
				        <li><a href="vaconflicts.php?clid=<?php echo $clid.'&pid='.$pid.'&start='.$startPos; ?>"><span>Voucher Conflicts</span></a></li>
				        <li><a href="vachildvouchers.php?clid=<?php echo $clid.'&pid='.$pid.'&start='.$startPos; ?>"><span>Children Vouchers</span></a></li>
				        <li><a href="#reportDiv"><span>Reports</span></a></li>
				    </ul>
					<div id="nonVoucheredDiv">
						<div style="margin:10px;">
							<?php 
							$nonVoucherCnt = $clManager->getNonVoucheredCnt();
							?>
							<div style="float:right;">
								<form name="displaymodeform" method="post" action="voucheradmin.php">
									<b>Display Mode:</b> 
									<select name="displaymode" onchange="this.form.submit()">
										<option value="0">Species List</option>
										<option value="1" <?php echo ($displayMode?'SELECTED':''); ?>>Batch Linking</option>
									</select>
									<input name="clid" type="hidden" value="<?php echo $clid; ?>" />
									<input name="pid" type="hidden" value="<?php echo $pid; ?>" />
									<input name="tabindex" type="hidden" value="0" />
								</form>
							</div> 
							<div style='float:left;font-weight:bold;margin-top:3px;height:30px;'>
								Taxa without Vouchers: <?php echo $nonVoucherCnt; ?>
							</div>
							<div style='float:left;'>
								<a href="voucheradmin.php?clid=<?php echo $clid.'&pid='.$pid; ?>"><img src="../images/refresh.jpg" style="border:0px;" title="Refresh List" /></a>
							</div>
							<?php 
							if($displayMode){
								?>
								<div style="clear:both;">
									<div style="margin:20px;">
										
						
									</div>
									<div>
										<?php 
										if($specArr = $clManager->getNewVouchers($startPos,0)){
											?>
											<form name="batchnonvoucherform" method="post" action="voucheradmin.php" onsubmit="return validateBatchNonVoucherForm(this)">
												<table class="styledtable">
													<tr>
														<th>
															<span title="Select All">
											         			<input name="occids[]" type="checkbox" onclick="selectAll(this);" value="0-0" />
											         		</span>
														</th>
														<th>Checklist ID</th>
														<th>Collector</th>
														<th>Locality</th>
													</tr>
													<?php 
													foreach($specArr as $cltid => $occArr){
														foreach($occArr as $occid => $oArr){
															echo '<tr>';
															echo '<td><input name="occids[]" type="checkbox" value="'.$occid.'-'.$cltid.'" /></td>';
															echo '<td><a href="../taxa/index.php?taxon='.$oArr['tid'].'" target="_blank">'.$oArr['sciname'].'</a></td>';
															echo '<td>';
															echo $oArr['recordedby'].' ('.($oArr['recordnumber']?$oArr['recordnumber']:'s.n.').')<br/>';
															if($oArr['eventdate']) echo $oArr['eventdate'].'<br/>';
															echo '<a href="../collections/individual/index.php?occid='.$occid.'" target="_blank">';
															echo $oArr['collcode'];
															echo '</a>';
															echo '</td>';
															echo '<td>'.$oArr['locality'].'</td>';
															echo '</tr>';
														}
													}
													?>
												</table>
												<input name="tabindex" value="0" type="hidden" /> 
												<input name="clid" value="<?php echo $clid; ?>" type="hidden" /> 
												<input name="pid" value="<?php echo $pid; ?>" type="hidden" />
												<input name="displaymode" value="1" type="hidden" />
												<input name="usecurrent" value="1" type="checkbox" checked />
												<input name="submitaction" value="Add Vouchers" type="submit" />
											</form>
											<?php 
										}
										else{
											echo '<div style="font-weight:bold;font-size:120%;">No vouchers located</div>';
										}
										?>
									</div>
								</div>
								
								<?php 
							}
							else{
								?>
								<div style="clear:both;">
									<div style="margin:20px;">
										Taxa are listed 100 at a time. Use navigation controls located at the bottom of the list to advance to the next group of taxa. 
										Clicking on a taxon name will use the search statemtn to dynamically query the system for possible voucher specimens.
									</div>
									<div style="margin:20px;">
										<?php 
										if($nonVoucherArr = $clManager->getNonVoucheredTaxa($startPos)){
											foreach($nonVoucherArr as $family => $tArr){
												echo '<div style="font-weight:bold;">'.strtoupper($family).'</div>';
												echo '<div style="margin:10px;text-decoration:italic;">';
												foreach($tArr as $tid => $sciname){
													?>
													<div>
														<a href="#" onclick="openPopup('../taxa/index.php?taxauthid=1&taxon=<?php echo $tid.'&cl='.$clid; ?>','taxawindow');return false;"><?php echo $sciname; ?></a>
														<a href="#" onclick="openPopup('../collections/list.php?db=all&thes=1&reset=1&taxa=<?php echo $tid.'&clid='.$clid.'&targettid='.$tid;?>','editorwindow');return false;">
															<img src="../images/link.png" style="width:13px;" title="Link Voucher Specimens" />
														</a>
													</div>
													<?php 
												}
												echo '</div>';
											}
											$arrCnt = $nonVoucherArr;
											if($startPos || $nonVoucherCnt > 100){
												echo '<div style="text-weight:bold;">';
												if($startPos > 0) echo '<a href="voucheradmin.php?clid='.$clid.'&pid='.$pid.'&start='.($startPos-100).'">';
												echo '&lt;&lt; Previous';
												if($startPos > 0) echo '</a>';
												echo ' || <b>'.$startPos.'-'.($startPos+($arrCnt<100?$arrCnt:100)).' Records</b> || ';
												if(($startPos + 100) <= $nonVoucherCnt) echo '<a href="voucheradmin.php?clid='.$clid.'&pid='.$pid.'&start='.($startPos+100).'">';
												echo 'Next &gt;&gt;';
												if(($startPos + 100) <= $nonVoucherCnt) echo '</a>';
												echo '</div>';
											}
										}
										else{
											echo '<h2>All taxa contain voucher links</h2>';
										}
										?>
									</div>
								</div>
								<?php
							}	
							?>
						</div>
					</div>
				    <div id="reportDiv">
				    	Soon to be a activated!
				    </div>
				</div>
				<?php
			}
		}
		else{
			if(!$clid){
				echo '<div><span style="font-weight:bold;font-size:110%;">Error:</span> Checklist identifier not set</div>';
			}
			elseif(!$symbUid){
				?>
				<div style="margin:30px;font-weight:bold;font-size:120%;">
					Please <a href="../profile/index.php?refurl=<?php echo $clientRoot.'/checklists/voucheradmin.php?clid='.$clid.'&pid='.$pid; ?>">login</a>
				</div>
				<?php 
			}
			else{
				echo '<div><span style="font-weight:bold;font-size:110%;">Error:</span> You do not have administrative permission for this checklist</div>';
			}
		}
		?>
	</div>
	<?php
	include($serverRoot.'/footer.php');
	?>
</body>
</html> 