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

$headerMapBase = array('catalognumber' => $LANG['CATALOG_NUMBER'],
						'occurrenceid' => $LANG['OCCURRENCE_ID'],
						'othercatalognumbers' => $LANG['OTHER_CATALOG'],
						'family' => $LANG['FAMILY'],
						'identificationqualifier' => $LANG['ID_QUALIFIER'],
						'sciname' => $LANG['SCIENTIFIC_NAME'],
						'scientificnameauthorship'=>$LANG['AUTHOR'],
						'recordedby' => $LANG['COLLECTOR'],
						'recordnumber' => $LANG['NUMBER'],
						'associatedcollectors' => $LANG['ASSICIATED_COLLECTORS'],
						'eventdate' => $LANG['EVENT_DATE'],
						'verbatimeventdate' => $LANG['VERBATIM_DATE'],
						'identificationremarks' => $LANG['IDENTIFICATION_REMARKS'],
						'taxonremarks' => $LANG['TAXON_REMARKS'],
						'identifiedby' => $LANG['IDENTIFIED_BY'],
						'dateidentified' => $LANG['DATE_IDENTIFIED'], 
						'identificationreferences' => $LANG['IDENTIFICATION_REFERENCES'],
						'country' => $LANG['COUNTRY'],
						'stateprovince' => $LANG['STATE_PROVINCE'],
						'county' => $LANG['COUNTY'],
						'municipality' => $LANG['MUNICIPALITY'],
						'locality' => $LANG['LOCALITY'],
						'decimallatitude' => $LANG['LATITUDE'], 
						'decimallongitude' => $LANG['LONGITUDE'],
						'geodeticdatum' => $LANG['DATUM'],
						'coordinateuncertaintyinmeters' => $LANG['UNCERTAINTY_IN_METERS'],
						'verbatimcoordinates' => $LANG['VERBATIM_COORDINATES'],
						'georeferencedby' => $LANG['GEOREFERENCED_BY'],
						'georeferenceprotocol' => $LANG['GEOREFERENCE_PROTOCOL'],
						'georeferencesources' => $LANG['GEOREFERENCE_SOURCES'],
						'georeferenceverificationstatus' => $LANG['GEOREF_VERIFICATION_STATUS'],
						'georeferenceremarks' => $LANG['GEOREF_REMARKS'],
						'minimumelevationinmeters' => $LANG['MIN_ELEV_M'],
						'maximumelevationinmeters' => $LANG['MAX_ELEV_M'],
						'verbatimelevation' => $LANG['VERBATIM_ELEV'],
						'habitat' => $LANG['HABITAT'],
						'substrate' => $LANG['SUBSTRATE'],
						'occurrenceremarks' => $LANG['NOTES'],
						'associatedtaxa' => $LANG['ASSOCIATED_TAXA'],
						'verbatimattributes' => $LANG['DESCRIPTION'],
						'lifestage' => $LANG['LIFE_STAGE'], 
						'sex' => $LANG['SEX'], 
						'individualcount' => $LANG['INDIVIDUAL_COUNT'], 
						'samplingprotocol' => $LANG['SAMPLING_PROTOCOL'], 
						'preparations' => $LANG['PREPARATIONS'], 
						'reproductivecondition' => $LANG['REPRODUCTIVE_CONDITION'],
						'typestatus' => $LANG['TYPE_STATUS'],
						'cultivationstatus' => $LANG['CULTIVATION_STATUS'],
						'establishmentmeans' => $LANG['ESTABLISHMENT_MEANS'],
						'disposition' => $LANG['DISPOSITION'],
						'duplicatequantity' => $LANG['DUPLICATE_QTY'],
						'datelastmodified' => $LANG['DATELAST_MODIFIED'],
						'processingstatus' => $LANG['PROCESSING_STATUS'],
						'recordenteredby' => $LANG['ENTERED_BY'],
						'basisofrecord' => $LANG['BASIS_OF_RECORD'],
						'occid' => $LANG['TARGETRECORD_OCCID']);
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
	<title><?php echo $LANG['RECORD_UPLOAD_PREVIEW']; ?></title>
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
					<?php echo $LANG['NO_RECORDS_HAVE_BEEN_UPLOADED']; ?>
				</div>
				<?php 
			}
		}
		else{
			echo '<h2>'.$LANG['YOU_ARE_NOT_AUTHORIZED'].'</h2>';
		}
		?>
	</div>
</body>
</html>