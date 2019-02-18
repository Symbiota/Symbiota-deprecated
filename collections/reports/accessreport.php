<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceAccessStats.php');
include_once($SERVER_ROOT.'/content/lang/collections/reports/accessreport.'.$LANG_TAG.'.php');

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/reports/accessstatsreview.php?'.$_SERVER['QUERY_STRING']);
header("Content-Type: text/html; charset=".$CHARSET);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$display = array_key_exists('display',$_REQUEST)?$_REQUEST['display']:'summary';
$duration = array_key_exists('duration',$_REQUEST)?$_REQUEST['duration']:'day';
$startDate = array_key_exists('startdate',$_REQUEST)?$_REQUEST['startdate']:'';
$endDate = array_key_exists('enddate',$_REQUEST)?$_REQUEST['enddate']:'';
$ip = array_key_exists('ip',$_REQUEST)?$_REQUEST['ip']:'';
$accessType = array_key_exists('accesstype',$_REQUEST)?$_REQUEST['accesstype']:'';
$occid = array_key_exists('occid',$_REQUEST)?$_REQUEST['occid']:'';
$pageNum = array_key_exists('pagenum',$_REQUEST)?$_REQUEST['pagenum']:'0';
$limitCnt = array_key_exists('limitcnt',$_REQUEST)?$_REQUEST['limitcnt']:'1000';
$action = array_key_exists('action',$_REQUEST)?$_REQUEST['action']:'';

$statManager = new OccurrenceAccessStats();
$collName = 'All Collections';
if($collid) $collName = $statManager->setCollid($collid);

$statManager->setDuration($duration);
$statManager->setStartDate($startDate);
$statManager->setEndDate($endDate);
$statManager->setIpAddress($ip);
$statManager->setAccessType($accessType);
$statManager->setOccidStr($occid);
$statManager->setPageNum($pageNum);
$statManager->setLimit($limitCnt);

$isEditor = false;
if($IS_ADMIN || ($collid && array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
 	$isEditor = true;
}

if($action == 'export'){
	$statManager->exportCsvFile($display);
	exit;
}

$statArr = array();
$recCnt = 0;
$headerStr = '';
if($display == 'full'){
	$statArr = $statManager->getFullReport();
	$recCnt = $statManager->getFullReportCount();
	$headerStr = '<th>'.$LANG['DATE'].'</th><th>'.$LANG['ACCESS'].'</th><th>'.$LANG['RECORD'].' #</th><th>'.$LANG['RECORD_COUNT'].'</th>';
}
else{
	$statArr = $statManager->getSummaryReport();
	$recCnt = $statManager->getSummaryReportCount();
	$periodArr = array('day'=>'Date','week'=>'Year-Week','month'=>'Year-Month','year'=>'Year');
	$headerStr = '<th>'.$periodArr[$duration].'</th><th>'.$LANG['ACCESS'].'</th><th>'.$LANG['RECORD_COUNT'].'</th>';
}
?>
<html>
	<head>
		<title><?php echo $LANG['OCCURRENCE_ACCESS_REPORTING']; ?></title>
		<link href="<?php echo $CLIENT_ROOT; ?>/css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
		<link href="<?php echo $CLIENT_ROOT; ?>/css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $CLIENT_ROOT; ?>/css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $CLIENT_ROOT; ?>/css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery.js" type="text/javascript"></script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.js" type="text/javascript"></script>
		<script>
			function validateFilterForm(f){
				if(f.startdate.value != "" && f.enddate.value != "" && f.startdate.value > f.enddate.value){
					alert("<?php echo $LANG['START_DATE_CANNOT_BE_AFTER_END_DATE']; ?>");
					return false;
				}
				return true
			}

			function printFriendlyMode(status){
				if(status){
					$(".navpath").hide();
					$(".header").hide();
					$(".navbarDiv").hide();
					$(".returnDiv").show();
					$("#filterDiv").hide();
					$(".footer").hide();
				}
				else{
					$(".navpath").show();
					$(".header").show();
					$(".navbarDiv").show();
					$(".returnDiv").hide();
					$("#filterDiv").show();
					$(".footer").show();
				}
			}

			function openIndPU(occid){
				var newWindow = window.open('../individual/index.php?occid='+occid,'indspec' + occid,'scrollbars=1,toolbar=0,resizable=1,width=1000,height=700,left=20,top=20');
				if (newWindow.opener == null) newWindow.opener = self;
			}
		</script>
		<script src="<?php echo $CLIENT_ROOT; ?>/js/symb/shared.js" type="text/javascript" ></script>
	</head>
	<body>
		<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/header.php');
		echo '<div class="navpath">';
		echo '<a href="../../index.php">'.$LANG['HOME'].'</a> &gt;&gt; ';
		echo '<a href="../misc/collprofiles.php?collid='.$collid.'&emode=1">'.$LANG['COLLECTION_MANAGEMENT_PANEL'].'</a> &gt;&gt; ';
		echo '<b>'.$LANG['OCCURRENCE_ACCESS_REPORTS'].'</b>';
		echo '</div>';
		?>
		<!-- This is inner text! -->
		<div id="innertext" style="min-width:1100px">
			<div>
				<div style="float:left;font-size:120%"><b><u><?php echo $LANG['USER'];?></u></b></div>
				<div id="desc_details" style="clear:both;display:none;width:500px;"><?php echo $LANG['DISPLAYS'];?>
				</div>
				<div id="desc_info" style="float:left;margin-left:5px;"><a href="#" onclick="toggle('desc_details');toggle('desc_info');"><img src="../../images/info.png" style="width:12px" /></a></div>
			</div>
			<?php
			if($isEditor){
				//Setup navigation bar
				$subsetCnt = $limitCnt*($pageNum + 1);
				if($subsetCnt > $recCnt) $subsetCnt = $recCnt;
				$navPageBase = 'accessreport.php?collid='.$collid.'&display='.$display.'&duration='.$duration.'&startdate='.$startDate.'&enddate='.$endDate.'&ip='.$ip.'&accesstype='.$accessType;
				$navStr = '<div class="navbarDiv" style="float:right;">';
				if($pageNum){
					$navStr .= '<a href="'.$navPageBase.'&pagenum='.($pageNum-1).'&limitcnt='.$limitCnt.'" title="'.$LANG['PREVIOUS'].' '.$limitCnt.' '.$LANG['RECORDS'].'">&lt;&lt;</a>';
				}
				else{
					$navStr .= '&lt;&lt;';
				}
				$navStr .= ' | ';
				$navStr .= ($pageNum*$limitCnt).'-'.$subsetCnt.' '.$LANG['OF'].' '.$recCnt.' '.$LANG['RECORDS'];
				$navStr .= ' | ';
				if($subsetCnt < $recCnt){
					$navStr .= '<a href="'.$navPageBase.'&pagenum='.($pageNum+1).'&limitcnt='.$limitCnt.'" title="'.$LANG['NEXT'].' '.$limitCnt.' '.$LANG['RECORDS'].'">&gt;&gt;</a>';
				}
				else{
					$navStr .= '&gt;&gt;';
				}
				$navStr .= '</div>';
				$retToMenuStr = '<div class="returnDiv" style="clear:both;display:none"><b><a href="#" onclick="printFriendlyMode(false)">'.$LANG['EXIT_PRINT_MODE'].'</a></b></div>';
				echo $retToMenuStr;
				$accessTypeArr = array('download'=>$LANG['DOWNLOAD'],'view'=>$LANG['FULL_VIEW'],'list'=>$LANG['LIST_VIEW'],'map'=>$LANG['MAP_VIEW'],'downloadJSON'=>$LANG['API_JSON_DOWNLOAD']);
				?>
				<div id="filterDiv" style="clear:both;padding-top:5px;">
					<form name="filter" action="accessreport.php" method="post" onsubmit="return validateFilterForm(this)">
						<fieldset style="width:375px;text-align:left;">
							<legend><b><?php echo $LANG['FILTER'];?></b></legend>
							<div style="margin:3px;">
								<?php echo $LANG['DIS'];?>
								<select name="display">
									<option value="summary"><?php echo $LANG['SUMMARY'];?></option>
									<option value="full" <?php echo ($display=='full'?'SELECTED':''); ?>><?php echo $LANG['FULL'];?></option>
								</select>
							</div>
							<div style="margin:3px;">
								<?php echo $LANG['DURATION'];?>
								<select name="duration">
									<option value="day"><?php echo $LANG['DAILY'];?></option>
									<option value="week" <?php echo ($duration=='week'?'SELECTED':''); ?>><?php echo $LANG['WEEK'];?></option>
									<option value="month" <?php echo ($duration=='month'?'SELECTED':''); ?>><?php echo $LANG['MONTHLY'];?></option>
									<option value="year" <?php echo ($duration=='year'?'SELECTED':''); ?>><?php echo $LANG['YEARLY'];?></option>
								</select>
							</div>
							<div style="margin:3px;">
								<?php echo $LANG['ACCESS'];?>
								<select name="accesstype">
									<option value=""><?php echo $LANG['ALL_ACCESS'];?></option>
									<option value="">---------------------</option>
									<?php
									foreach($accessTypeArr as $k => $v){
										echo '<option value="'.$k.'" '.($accessType==$k?'SELECTED':'').'>'.$v.'</option>';
									}
									?>
								</select>
							</div>
							<div style="margin:3px;">
								<?php echo $LANG['DATE'];?>
								<input name="startdate" type="date" value="<?php echo $startDate; ?>" /> <?php echo $LANG['TO'];?>
								<input name="enddate" type="date" value="<?php echo $endDate; ?>" />
							</div>
							<div style="margin:10px;">
								<button name="submitbutton" type="submit" value="submitfilter"><?php echo $LANG['SUBMIT'];?></button>
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
							</div>
						</fieldset>
					</form>
				</div>
				<div style="font-weight:bold;font-size:130%;">
					<?php echo $collName; ?>
					<a href="<?php echo $navPageBase.'&action=export'; ?>" title="<?php $LANG['DOWNLOAD_RESULTS']; ?>"><img src="../../images/dl.png" style="margin-left:10px;width:14px;" /></a>
				</div>
				<div style="width:400px">
					<div style="clear:both"><?php echo $navStr; ?></div>
					<table class="styledtable">
						<tr>
							<?php
							echo $headerStr;
							?>
						</tr>
						<?php
						if($statArr){
							if($display == 'full'){
								foreach($statArr as $date => $arr1){
									foreach($arr1 as $aType => $arr2){
										foreach($arr2 as $recid => $cnt){
											echo '<tr><td>'.$date.'</td><td>'.(isset($accessTypeArr[$aType])?$accessTypeArr[$aType]:'').'</td><td><a href="#" onclick="openIndPU('.$recid.');return false;">'.$recid.'</a></td><td>'.$cnt.'</td></tr>';
										}
									}
								}
							}
							else{
								foreach($statArr as $date => $arr1){
									foreach($arr1 as $aType => $cnt){
										echo '<tr><td>'.$date.'</td><td>'.(isset($accessTypeArr[$aType])?$accessTypeArr[$aType]:'').'</td><td>'.$cnt.'</td></tr>';
									}
								}
							}
						}
						else{
							?>
							<tr>
								<td colspan="10">
									<div style="font-weight:bold;font-size:90%;margin:20px;"><?php echo $LANG['THERE'];?></div>
								</td>
							</tr>
							<?php
						}
						?>
					</table>
					<div style="clear:both"><?php echo $navStr; ?></div>
				</div>
				<?php
				echo $retToMenuStr;
			}
			else{
				echo '<div>Error!</div>';
			}
			?>
		</div>
		<?php include($SERVER_ROOT.'/footer.php');?>
	</body>
</html>