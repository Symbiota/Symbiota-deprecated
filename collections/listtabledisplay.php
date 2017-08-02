<?php
include_once('../config/symbini.php'); 
include_once($SERVER_ROOT.'/classes/OccurrenceListManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$targetTid = array_key_exists("targettid",$_REQUEST)?$_REQUEST["targettid"]:0;
$page = array_key_exists('page',$_REQUEST)?$_REQUEST['page']:1;
$tableCount= array_key_exists('tablecount',$_REQUEST)?$_REQUEST['tablecount']:1000;
$sortField1 = array_key_exists('sortfield1',$_REQUEST)?$_REQUEST['sortfield1']:'collection';
$sortField2 = array_key_exists('sortfield2',$_REQUEST)?$_REQUEST['sortfield2']:'';
$sortOrder = array_key_exists('sortorder',$_REQUEST)?$_REQUEST['sortorder']:'';

//Sanitation
if(!is_numeric($page)) $page = 0;

$collManager = new OccurrenceListManager();
$searchVar = $collManager->getSearchTermStr();
$urlPrefix = (isset($_SERVER['HTTPS'])?'https://':'http://').$_SERVER['HTTP_HOST'].$CLIENT_ROOT.'/collections/listtabledisplay.php';
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE; ?> Collections Search Results Table</title>
	<style type="text/css">
		table.styledtable td {
			white-space: nowrap;
		}
	</style>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<script src="../js/jquery.js" type="text/javascript"></script>
	<script src="../js/jquery-ui.js" type="text/javascript"></script>
	<script type="text/javascript">
		<?php include_once($SERVER_ROOT.'/config/googleanalytics.php'); ?>
	</script>
	<script type="text/javascript">
		function copySearchUrl(){
			var urlFixed = <?php echo $urlPrefix.'&page='.$page.'&sortfield1='.$sortField1.'&sortfield2='.$sortField2.'&sortorder'.$sortOrder; ?>;
			var copyBox = document.getElementById('urlFullBox');
			copyBox.value = urlFixed;
			copyBox.focus();
			copyBox.setSelectionRange(0,copyBox.value.length);
			document.execCommand("copy");
			copyBox.value = '';
		}
	</script>
</head>
<body style="margin-left: 0px; margin-right: 0px;background-color:white;">
	<div id="">
		<div style="width:725px;clear:both;margin-bottom:5px;">
			<div style="float:right;">
				<div class='button' style='margin:15px 15px 0px 0px;width:13px;height:13px;' title='Download specimen data'>
					<a id="dllink" href="download/index.php?dltype=specimen&searchvar=<?php echo urlencode($searchVar); ?>"><img src="../images/dl.png" /></a>
				</div>
			</div>
			<fieldset style="padding:5px;width:650px;">
				<legend><b>Sort Results</b></legend>
				<form name="sortform" action="listtabledisplay.php" method="post">
					<div style="float:left;">
						<b>Sort By:</b> 
						<select name="sortfield1">
							<?php 
							$sortFields = array('collection' => 'Collection', 'o.catalogNumber' => 'Catalog Number', 'o.family' => 'Family', 'o.sciname' => 'Scientific Name', 'o.recordedBy' => 'Collector',
								'o.recordNumber' => 'Number', 'o.eventDate' => 'Event Date', 'o.country' => 'Country', 'o.StateProvince' => 'State/Province', 'o.county' => 'County', 'o.minimumElevationInMeters' => 'Elevation');
							foreach($sortFields as $k => $v){
								echo '<option value="'.$k.'" '.($k==$sortField1?'SELECTED':'').'>'.$v.'</option>';
							}
							?>
						</select>
					</div>
					<div style="float:left;margin-left:10px;">
						<b>Then By:</b> 
						<select name="sortfield2">
							<option value="">Select Field Name</option>
							<?php 
							foreach($sortFields as $k => $v){
								echo '<option value="'.$k.'" '.($k==$sortField2?'SELECTED':'').'>'.$v.'</option>';
							}
							?>
						</select>
					</div>
					<div style="float:left;margin-left:10px;">
						<b>Order:</b> 
						<select name="sortorder">
							<option value="">Ascending</option>
							<option value="desc" <?php echo ($sortOrder=="desc"?'SELECTED':''); ?>>Descending</option>
						</select>
					</div>
					<div style="float:right;margin-right:10px;">
						<input name="searchvar" type="hidden" value="<?php echo $searchVar; ?>" />
						<input name="formsubmit" type="submit" value="Sort" />
					</div>
				</form>
			</fieldset>
		</div>
		<div style="width:790px;clear:both;">
			<?php
			if(isset($collections_listCrumbs)){
				if($collections_listCrumbs){
					echo '<span class="navpath">';
					echo $collections_listCrumbs.' &gt;&gt; ';
					echo ' <b>Specimen Records Table</b>';
					echo '</span>';
				}
			}
			else{
				echo '<span class="navpath">';
				echo '<a href="../index.php">Home</a> &gt;&gt; ';
				echo '<a href="index.php">Collections</a> &gt;&gt; ';
				echo '<a href="harvestparams.php?'.$searchVar.'">Search Criteria</a> &gt;&gt; ';
				echo '<b>Specimen Records Table</b>';
				echo '</span>';
			}
			?>
		</div>
		<div id="tablediv">
			<?php
			$collManager->addSort($_REQUEST['sortfield1'], $_REQUEST['sortorder']);
			if($_REQUEST['sortfield2']) $collManager->addSort($_REQUEST['sortfield2'], $_REQUEST['sortorder']);
			$recArr = $collManager->getSpecimenMap($page,$tableCount);
			
			$targetClid = $collManager->getSearchTerm("targetclid");
			
			$qryCnt = $collManager->getRecordCnt();
			$navStr = '<div style="float:right;">';
			if($page >= $tableCount){
				$navStr .= '<a href="listtabledisplay.php?'.$searchVar.'&page='.($page-$tableCount).'" title="Previous '.$tableCount.' records">&lt;&lt;</a>';
			}
			$navStr .= ' | ';
			$navStr .= ($page+1).'-'.($qryCnt<$tableCount+$page?$qryCnt:$tableCount+$page).' of '.$qryCnt.' records';
			$navStr .= ' | ';
			if($qryCnt > ($tableCount+$page)){
				$navStr .= '<a href="listtabledisplay.php?'.$searchVar.'&page='.($page+$tableCount).'" title="Next '.$tableCount.' records">&gt;&gt;</a>';
			}
			$navStr .= '</div>';
			
			if($recArr){
				?>
				<div style="width:790px;clear:both;margin:5px;">
					<div style="float:left;"><button type="button" id="copyurl" onclick="copySearchUrl();">Copy URL to These Results</button></div>
					<?php 
					$navStr;
					?>
				</div>
				<div style="clear:both;height:5px;"></div>
				<table class="styledtable" style="font-family:Arial;font-size:12px;">
					<tr>
						<th>Symbiota ID</th>
						<th>Collection</th>
						<th>Catalog Number</th>
						<th>Family</th>
						<th>Scientific Name</th>
						<th>Country</th>
						<th>State/Province</th>
						<th>County</th>
						<th>Locality</th>
						<th>Habitat</th>
						<th>Elevation</th>
						<th>Event Date</th>
						<th>Collector</th>
						<th>Number</th>
					</tr>
					<?php 
					$recCnt = 0;
					foreach($recArr as $occid => $occArr){
						$isEditor = false;
						if($SYMB_UID && ($IS_ADMIN
								|| (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($occArr['collid'],$USER_RIGHTS['CollAdmin']))
								|| (array_key_exists('CollEditor',$USER_RIGHTS) && in_array($occArr['collid'],$USER_RIGHTS['CollEditor'])))){
							$isEditor = true;
						}
						$collection = $occArr['instcode'];
						if($occArr['collcode']) $collection .= ':'.$occArr['collcode'];
						if($occArr['sciname']) $occArr['sciname'] = '<i>'.$occArr['sciname'].'</i> ';
						?>
						<tr <?php echo ($recCnt%2?'class="alt"':''); ?>>
							<td>
								<?php 
								echo '<a href="#" onclick="return openIndPU('.$occid.",".($targetClid?$targetClid:"0").');">'.$occid.'</a> ';
								if($isEditor || ($SYMB_UID && $SYMB_UID == $occArr['obsuid'])){
									echo '<a href="editor/occurrenceeditor.php?occid='.$occid.'" target="_blank">';
									echo '<img src="../images/edit.png" style="height:13px;" title="Edit Record" />';
									echo '</a>';
								}
								if(isset($occArr['img'])){
									echo '<img src="../images/image.png" style="height:13px;margin-left:5px;" title="Has Image" />';
								}
								?>
							</td>
							<td><?php echo $collection; ?></td>
							<td><?php echo $occArr['catnum']; ?></td>
							<td><?php echo $occArr['family']; ?></td>
							<td><?php echo $occArr['sciname'].($occArr['author']?" ".$occArr['author']:""); ?></td>
							<td><?php echo $occArr['country']; ?></td>
							<td><?php echo $occArr['state']; ?></td>
							<td><?php echo $occArr['county']; ?></td>
							<td><?php echo ((strlen($occArr['locality'])>80)?substr($occArr['locality'],0,80).'...':$occArr['locality']); ?></td>
							<td><?php echo ((strlen($occArr['habitat'])>80)?substr($occArr['habitat'],0,80).'...':$occArr['habitat']); ?></td>
							<td><?php echo (array_key_exists("elev",$occArr)?$occArr['elev']:""); ?></td>
							<td><?php echo (array_key_exists("date",$occArr)?$occArr['date']:""); ?></td>
							<td><?php echo $occArr['collector']; ?></td>
							<td><?php echo (array_key_exists("collnum",$occArr)?$occArr['collnum']:""); ?></td>
						</tr>
						<?php
						$recCnt++;
					}
					?>
				</table>
				<div style="clear:both;height:5px;"></div>
				<textarea id="urlPrefixBox" style="position:absolute;left:-9999px;top:-9999px"><?php echo $urlPrefix.$collManager->getSearchResultUrl(); ?></textarea>
				<textarea id="urlFullBox" style="position:absolute;left:-9999px;top:-9999px"></textarea>
				<div style="width:790px;"><?php echo $navStr; ?></div>
				*Click on the Symbiota identifier in the first column to see Full Record Details.';
				<?php 
			}
			else{
				echo '<div style="font-weight:bold;font-size:120%;">No records found matching the query</div>';
			}
			?>
		</div>
	</div>
</body>
</html>