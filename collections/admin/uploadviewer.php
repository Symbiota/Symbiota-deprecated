<?php
include_once('../../config/symbini.php'); 
include_once($serverRoot.'/classes/SpecUpload.php');
header("Content-Type: text/html; charset=".$charset);

$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$recLimit = array_key_exists('reclimit',$_REQUEST)?$_REQUEST['reclimit']:1000;
$pageIndex = array_key_exists('pageindex',$_REQUEST)?$_REQUEST['pageindex']:0;
$searchVar = array_key_exists('searchvar',$_REQUEST)?$_REQUEST['searchvar']:'';

$uploadManager = new SpecUpload();
$uploadManager->setCollId($collid);
$collMap = $uploadManager->getCollInfo();

$headerMapBase = array('catalognumber' => 'Catalog Number','occurrenceid' => 'Occurrence ID',
	'othercatalognumbers' => 'Other Catalog #','family' => 'Family','identificationqualifier' => 'ID Qualifier',
	'sciname' => 'Scientific name','scientificnameauthorship'=>'Author','recordedby' => 'Collector','recordnumber' => 'Number',
	'associatedcollectors' => 'Associated Collectors','eventdate' => 'Event Date','verbatimeventdate' => 'Verbatim Date',
	'identificationremarks' => 'Identification Remarks','taxonremarks' => 'Taxon Remarks','identifiedby' => 'Identified By',
	'dateidentified' => 'Date Identified', 'identificationreferences' => 'Identification References',
	'country' => 'Country','stateprovince' => 'State/Province','county' => 'county','municipality' => 'municipality',
	'locality' => 'locality','decimallatitude' => 'Latitude', 'decimallongitude' => 'Longitude','geodeticdatum' => 'Datum',
	'coordinateuncertaintyinmeters' => 'Uncertainty In Meters','verbatimcoordinates' => 'Verbatim Coordinates',
	'georeferencedby' => 'Georeferenced By','georeferenceprotocol' => 'Georeference Protocol','georeferencesources' => 'Georeference Sources',
	'georeferenceverificationstatus' => 'Georef Verification Status','georeferenceremarks' => 'Georef Remarks',
	'minimumelevationinmeters' => 'Min. Elev. (m)','maximumelevationinmeters' => 'Max. Elev. (m)','verbatimelevation' => 'Verbatim Elev.',
	'habitat' => 'Habitat','substrate' => 'Substrate','occurrenceremarks' => 'Notes','associatedtaxa' => 'Associated Taxa',
	'verbatimattributes' => 'Description','lifestage' => 'Life Stage', 'sex' => 'Sex', 'individualcount' => 'Individual Count', 
	'samplingprotocol' => 'Sampling Protocol', 'preparations' => 'Preparations', 'reproductivecondition' => 'Reproductive Condition',
	'typestatus' => 'Type Status','cultivationstatus' => 'Cultivation Status','establishmentmeans' => 'Establishment Means',
	'disposition' => 'disposition','duplicatequantity' => 'Duplicate Qty','datelastmodified' => 'Date Last Modified',
	'processingstatus' => 'Processing Status','recordenteredby' => 'Entered By','basisofrecord' => 'Basis Of Record','occid' => 'targetRecord (occid)');
if($collMap['managementtype'] == 'Snapshot'){
	$headerMapBase['dbpk'] = 'Source Identifier';
}

//$recCnt = $uploadManager->getUploadCount();
$isEditor = 0;
//$navStr = '<div style="float:right;">';
if($SYMB_UID){
	//Set variables
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collid,$userRights["CollAdmin"]))){
		$isEditor = 1;
	}
/*
	if(($pageIndex) >= $recLimit){
		$navStr .= '<a href="uploadviewer.php?collid='.$collid.'&reclimit='.$reclimit.'&pageindex=0" title="First page">|&lt;&lt;</a> | ';
		$navStr .= '<a href="uploadviewer.php?collid='.$collid.'&reclimit='.$reclimit.'&pageindex='.($pageIndex-1).'" title="Previous '.$recLimit.' record">&lt;&lt;</a>';
	}
	else{
		$navStr .= '|&lt;&lt;</a> | &lt;&lt;';
	}
	$navStr .= ' | ';
	$highRange = ($pageIndex*$recLimit)+$recLimit;
	$navStr .= (($pageIndex*$recLimit)+1).'-'.($recCnt<$highRange?$recCnt:$highRange).' of '.$recCnt.' records';
	$navStr .= ' | ';
	if($recCnt > $highRange){
		$navStr .= '<a href="uploadviewer.php?collid='.$collid.'&reclimit='.$reclimit.'&pageindex='.($pageIndex+1).'" title="Next '.$recLimit.' records">&gt;&gt;</a> | ';
		$navStr .= '<a href="uploadviewer.php?collid='.$collid.'&reclimit='.$reclimit.'&pageindex='.($recCnt/$recLimit).'" title="Last page">&gt;&gt;|</a>';
	}
	else{
		$navStr .= '&gt;&gt; | &gt;&gt;|';
	}
	$navStr .= '</div>';
*/
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title>Record Upload Preview</title>
    <style type="text/css">
		table.styledtable td {
		    white-space: nowrap;
		}
    </style>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
</head>
<body style="margin-left: 0px; margin-right: 0px;background-color:white;">
	<!-- inner text -->
	<div id="">
		<?php 
		if($isEditor){
			if($collMap){
				echo '<h2>'.$collMap['name'].' ('.$collMap['institutioncode'].($collMap['collectioncode']?':'.$collMap['collectioncode']:'').')</h2>';
			}
			//Setup header map
			$recArr = $uploadManager->getPendingImportData(($recLimit*$pageIndex),$recLimit,$searchVar);
			if($recArr){
				//Check to see which headers have values
				$headerArr = array();
				foreach($recArr as $occurArr){
					foreach($occurArr as $k => $v){
						if(trim($v) && !array_key_exists($k,$headerArr)){
							$headerArr[$k] = $k;
						}
					}
				}
				$headerMap = array_intersect_key($headerMapBase, $headerArr);
				?>
				<table class="styledtable" style="font-family:Arial;font-size:12px;">
					<tr>
						<?php 
						foreach($headerMap as $k => $v){
							echo '<th>'.$v.'</th>';
						}
						?>
					</tr>
					<?php 
					$cnt = 0;
					foreach($recArr as $id => $occArr){
						if($occArr['sciname']) $occArr['sciname'] = '<i>'.$occArr['sciname'].'</i> ';
						echo "<tr ".($cnt%2?'class="alt"':'').">\n";
						foreach($headerMap as $k => $v){
							$displayStr = $occArr[$k];
							if(strlen($displayStr) > 60){
								$displayStr = substr($displayStr,0,60).'...';
							}
							if($displayStr) {
								if($k == 'occid') $displayStr = '<a href="../editor/occurrenceeditor.php?occid='.$displayStr.'" target="_blank">'.$displayStr.'</a>';
							}
							else{
								$displayStr = '&nbsp;';
							}
							echo '<td>'.$displayStr.'</td>'."\n";
						}
						echo "</tr>\n";
						$cnt++;
					}
					?>
				</table>
				<div style="width:790px;">
					<?php //echo $navStr; ?>
				</div>
				<?php 
			}
			else{
				?>
				<div style="font-weight:bold;font-size:120%;margin:25px;">
					No records have been uploaded
				</div>
				<?php 
			}
		}
		else{
			echo '<h2>You are not authorized to access this page</h2>';
		}
		?>
	</div>
</body>
</html>