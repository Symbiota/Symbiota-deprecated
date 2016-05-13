<?php
include_once('../config/symbini.php'); 
include_once($SERVER_ROOT.'/classes/OccurrenceListManager.php');
header("Content-Type: text/html; charset=".$charset);

$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:0;
$occIndex = array_key_exists('occindex',$_REQUEST)?$_REQUEST['occindex']:0;
$sortField1 = array_key_exists('sortfield1',$_REQUEST)?$_REQUEST['sortfield1']:'collection';
$sortField2 = array_key_exists('sortfield2',$_REQUEST)?$_REQUEST['sortfield2']:'';
$sortOrder = array_key_exists('sortorder',$_REQUEST)?$_REQUEST['sortorder']:'';
$stArrCollJson = array_key_exists("jsoncollstarr",$_REQUEST)?$_REQUEST["jsoncollstarr"]:'';
$stArrSearchJson = array_key_exists("starr",$_REQUEST)?$_REQUEST["starr"]:'';

//Sanitation
if(!is_numeric($taxonFilter)) $taxonFilter = 1;
if(!is_numeric($occIndex)) $occIndex = 100;

$collManager = new OccurrenceListManager();
$stArr = array();
$specOccJson = '';
$navStr = '';
$sortFields = array('collection' => 'Collection','o.CatalogNumber' => 'Catalog Number','o.family' => 'Family',
	'o.sciname' => 'Scientific Name','o.recordedBy' => 'Collector','o.recordNumber' => 'Number','o.eventDate' => 'Event Date',
	'o.country'=>'Country','o.StateProvince' => 'State/Province','o.county' => 'County','CAST(elev AS UNSIGNED)' => 'Elevation');

if($stArrCollJson && $stArrSearchJson){
	$stArrSearchJson = str_replace("%apos;","'",$stArrSearchJson);
	$collStArr = json_decode($stArrCollJson, true);
	$searchStArr = json_decode($stArrSearchJson, true);
	$stArr = array_merge($searchStArr,$collStArr);
}
elseif($stArrCollJson && !$stArrSearchJson){
	$collArray = $collManager->getSearchTerms();
	$collStArr = json_decode($stArrCollJson, true);
	$stArr = array_merge($collArray,$collStArr);
	$stArrSearchJson = json_encode($collArray);
}
else{
	$collArray = $collManager->getSearchTerms();
	$collStArr = $collManager->getSearchTerms();
	$stArr = array_merge($collArray,$collStArr);
	$stArrSearchJson = json_encode($collArray);
	$stArrCollJson = json_encode($collArray);
}

$stArrJson = json_encode($stArr);
$collManager->setSearchTermsArr($stArr);
$collManager->setSorting($sortField1,$sortField2,$sortOrder);
$recArr = $collManager->getTableSpecimenMap($occIndex,1000);			//Array(IID,Array(fieldName,value))
$targetClid = $collManager->getSearchTerm("targetclid");
if($recArr){
	$qryCnt = $collManager->getRecordCnt();
	$hrefPrefix = 'listtabledisplay.php?usecookies=false&starr='.$stArrSearchJson.'&jsoncollstarr='.$stArrCollJson.(array_key_exists('targettid',$_REQUEST)?'&targettid='.$_REQUEST["targettid"]:'').'&sortfield1='.$sortField1.'&sortfield2='.$sortField2.'&sortorder='.$sortOrder.'&occindex=';
	$navStr = '<div style="float:right;">';
	if($occIndex >= 1000){
		$navStr .= "<a href='".$hrefPrefix.($occIndex-1000)."' title='Previous 1000 records'>&lt;&lt;</a>";
	}
	$navStr .= ' | ';
	$navStr .= ($occIndex+1).'-'.($qryCnt<1000+$occIndex?$qryCnt:1000+$occIndex).' of '.$qryCnt.' records';
	$navStr .= ' | ';
	if($qryCnt > (1000+$occIndex)){
		$navStr .= "<a href='".$hrefPrefix.($occIndex+1000)."' title='Next 1000 records'>&gt;&gt;</a>";
	}
	$navStr .= '</div>';
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Collections Search Results Table</title>
    <style type="text/css">
		table.styledtable td {
		    white-space: nowrap;
		}
    </style>
	<link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<script src="../js/jquery.js" type="text/javascript"></script>
	<script src="../js/jquery-ui.js" type="text/javascript"></script>
	<script type="text/javascript">
		<?php include_once($SERVER_ROOT.'/config/googleanalytics.php'); ?>
	</script>
	<script type="text/javascript">
		function openIndPU(occId,clid){
			newWindow = window.open('individual/index.php?occid='+occId+'&clid='+clid,'indspec' + occId,'scrollbars=1,toolbar=1,resizable=1,width=800,height=700,left=20,top=20');
			if (newWindow.opener == null) newWindow.opener = self;
			return false;
		}
	</script>
</head>
<body style="margin-left: 0px; margin-right: 0px;background-color:white;">
	<!-- inner text -->
	<div id="">
		<div style="width:725px;clear:both;margin-bottom:5px;">
			<div style="float:right;">
				<div class='button' style='margin:15px 15px 0px 0px;width:13px;height:13px;' title='Download specimen data'>
					<a href='download/index.php?usecookies=false&dltype=specimen&starr=<?php echo $stArrSearchJson; ?>&jsoncollstarr=<?php echo $stArrCollJson; ?>'>
						<img src='../images/dl.png'/>
					</a>
				</div>
			</div>
			<fieldset style="padding:5px;width:650px;">
				<legend><b>Sort Results</b></legend>
				<form name="sortform" action="listtabledisplay.php" method="post">
					<div style="float:left;">
						<b>Sort By:</b> 
						<select name="sortfield1">
							<?php 
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
							<option value="ASC" <?php echo ($sortOrder=="ASC"?'SELECTED':''); ?>>Ascending</option>
							<option value="DESC" <?php echo ($sortOrder=="DESC"?'SELECTED':''); ?>>Descending</option>
						</select>
					</div>
					<div style="float:right;margin-right:10px;">
						<input name="jsoncollstarr" type="hidden" value='<?php echo $stArrCollJson; ?>' />
						<input name="starr" type="hidden" value='<?php echo $stArrSearchJson; ?>' />
						<input name="taxonfilter" type="hidden" value='<?php echo $taxonFilter; ?>' />
						<input name="occindex" type="hidden" value='<?php echo $occIndex; ?>' />
						<button name="formsubmit" type="submit" value="sortresults">Sort</button>
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
				echo '<a href="harvestparams.php">Search Criteria</a> &gt;&gt; ';
				echo '<b>Specimen Records Table</b>';
				echo '</span>';
			}
			echo $navStr; ?>
		</div>
		<?php 
		if($recArr){
			?>
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
				foreach($recArr as $id => $occArr){
					$isEditor = false;
					if($SYMB_UID && ($IS_ADMIN
					|| (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($occArr['collid'],$USER_RIGHTS['CollAdmin']))
					|| (array_key_exists('CollEditor',$USER_RIGHTS) && in_array($occArr['collid'],$USER_RIGHTS['CollEditor'])))){
						$isEditor = true;
					}
					if($occArr['sciname']){
						$occArr['sciname'] = '<i>'.$occArr['sciname'].'</i> ';
					}							
					echo "<tr ".($recCnt%2?'class="alt"':'').">\n";
					echo '<td>';
					echo '<a href="#" onclick="return openIndPU('.$id.",".($targetClid?$targetClid:"0").');">'.$id.'</a> ';
					if($isEditor || ($SYMB_UID && $SYMB_UID == $fieldArr['observeruid'])){
						echo '<a href="editor/occurrenceeditor.php?occid='.$id.'" target="_blank">';
						echo '<img src="../images/edit.png" style="height:13px;" title="Edit Record" />';
						echo '</a>';
					}
					if($occArr['hasImage']){
						echo '<img src="../images/image.png" style="height:13px;margin-left:5px;" title="Has Image" />';
					}
					echo '</td>'."\n";
					echo '<td>'.$occArr['collection'].'</td>'."\n";
					echo '<td>'.$occArr['accession'].'</td>'."\n";
					echo '<td>'.$occArr['family'].'</td>'."\n";
					echo '<td>'.$occArr['sciname'].($occArr['author']?" ".$occArr['author']:"").'</td>'."\n";
					echo '<td>'.$occArr['country'].'</td>'."\n";
					echo '<td>'.$occArr['state'].'</td>'."\n";
					echo '<td>'.$occArr['county'].'</td>'."\n";
					echo '<td>'.((strlen($occArr['locality'])>80)?substr($occArr['locality'],0,80).'...':$occArr['locality']).'</td>'."\n";
					echo '<td>'.(array_key_exists("habitat",$occArr)?((strlen($occArr['habitat'])>80)?substr($occArr['habitat'],0,80).'...':$occArr['habitat']):"").'</td>'."\n";
					echo '<td>'.(array_key_exists("elev",$occArr)?$occArr['elev']:"").'</td>'."\n";
					echo '<td>'.(array_key_exists("date",$occArr)?$occArr['date']:"").'</td>'."\n";
					echo '<td>'.$occArr['collector'].'</td>'."\n";
					echo '<td>'.(array_key_exists("collnumber",$occArr)?$occArr['collnumber']:"").'</td>'."\n";
					echo "</tr>\n";
					$recCnt++;
				}
				?>
			</table>
			<div style="width:790px;">
				<?php echo $navStr; ?>
			</div>
			*Click on the Symbiota identifier in the first column to see Full Record Details.    
			<?php 
		}
		else{
			?>
			<div style="font-weight:bold;font-size:120%;">
				No records found matching the query
			</div>
			<?php 
		}
		?>
	</div>
</body>
</html>