<?php
include_once('../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
header("Content-Type: text/html; charset=".$charset);

$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$recLimit = array_key_exists('reclimit',$_REQUEST)?$_REQUEST['reclimit']:100;
$occIndex = array_key_exists('occindex',$_REQUEST)?$_REQUEST['occindex']:0;
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

$occManager = new OccurrenceEditorManager();

$isEditor = 0;		//If not editor, edits will be submitted to omoccuredits table but not applied to omoccurrences 
$collMap = Array();
$recArr = array();
$qryCnt = 0;
$statusStr = '';

if($symbUid){
	//Set variables
	$occManager->setSymbUid($symbUid); 
	$occManager->setCollId($collId);
	$collMap = $occManager->getCollMap();
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
		$isEditor = 1;
	}

	$isGenObs = ($collMap['colltype']=='General Observations'?1:0);
	if(!$isEditor){
		if($isGenObs){ 
			if(!$occId && array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"])){
				//Approved General Observation editors can add records
				$isEditor = 2;
			}
			elseif($action){
				//Lets assume that Edits where submitted and they remain on same specimen, user is still approved
				 $isEditor = 2;
			}
			elseif($occManager->getObserverUid() == $symbUid){
				//User can only edit their own records
				$isEditor = 2;
			}
		}
		elseif(array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"])){
			$isEditor = 2;
		}
	}

	if($occIndex !== false){
		//Query Form has been activated 
		$occManager->setQueryVariables();
		$occManager->setSqlWhere($occIndex,($isEditor==1?1:0),$recLimit);
		$qryCnt = $occManager->getQueryRecordCount();
	}
	elseif(isset($_COOKIE["editorquery"])){
		//Make sure query is null
		setCookie('editorquery','',time()-3600,($clientRoot?$clientRoot:'/'));
	}
	
	$recArr = $occManager->getOccurMap();

	$navStr = '<div style="float:right;">';
	if($occIndex >= $recLimit){
		$navStr .= '<a href="#" onclick="return submitQueryForm('.($occIndex-$recLimit).');" title="Previous '.$recLimit.' record">&lt;&lt;</a>';
	}
	else{
		$navStr .= '&lt;&lt;';
	}
	$navStr .= ' | ';
	$navStr .= ($occIndex+1).'-'.($qryCnt<$recLimit?$qryCnt:$recLimit+$occIndex).' of '.$qryCnt.' records';
	$navStr .= ' | ';
	if($qryCnt > ($recLimit+$occIndex)){
		$navStr .= '<a href="#" onclick="return submitQueryForm('.($occIndex+$recLimit).');" title="Next '.$recLimit.' records">&gt;&gt;</a>';
	}
	else{
		$navStr .= '&gt;&gt;';
	}
	$navStr .= '</div>';
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Occurrence Table View</title>
    <style type="text/css">
		table.styledtable td {
		    white-space: nowrap;
		}
    </style>
    <link type="text/css" href="../../css/main.css" rel="stylesheet" />
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
	<script type="text/javascript">
		function toggle(target){
			var ele = document.getElementById(target);
			if(ele){
				if(ele.style.display=="none"){
					ele.style.display="block";
		  		}
			 	else {
			 		ele.style.display="none";
			 	}
			}
			else{
				var divObjs = document.getElementsByTagName("div");
			  	for (i = 0; i < divObjs.length; i++) {
			  		var divObj = divObjs[i];
			  		if(divObj.getAttribute("class") == target || divObj.getAttribute("className") == target){
						if(divObj.style.display=="none"){
							divObj.style.display="block";
						}
					 	else {
					 		divObj.style.display="none";
					 	}
					}
				}
			}
		}
	</script>	
	<script type="text/javascript" src="../../js/symb/collections.occureditorquery.js?cacherefresh=<?php echo time(); ?>"></script>
</head>
<body>
	<!-- inner text -->
	<div id="">
		<?php 
		if(!$symbUid){
			?>
			<div style="font-weight:bold;font-size:120%;margin:30px;">
				Please 
				<a href="../../profile/index.php?refurl=<?php echo $clientRoot.'/collections/editor/occurrencetabledisplay.php&collid='.$collId; ?>">
					LOGIN
				</a> 
			</div>
			<?php 
		}
		else{
			if($collMap){
				echo '<div>';
				echo '<h2>'.$collMap['collectionname'].' ('.$collMap['institutioncode'].($collMap['collectioncode']?':'.$collMap['collectioncode']:'').')</h2>';
				echo '</div>';
			}
			if($isEditor && $collId){
				$displayQueryForm = 0;
				if(!$recArr) $displayQueryForm = 1;
				include 'includes/queryform.php';
				?>
				<div style="width:790px;clear:both;">
					<span class='navpath'>
						<a href="../../index.php">Home</a> &gt;&gt;
						<a href="../misc/collprofiles.php?collid=<?php echo $collId; ?>&emode=1">Collection Management Panel</a> &gt;&gt;
						<b>Editor</b>
					</span>
					<?php echo $navStr; ?>
				</div>
				<?php 
				if($recArr){
					$headerMap = array('catalognumber' => 'Catalog Number','occurrenceid' => 'Global Unique Identifier',
						'othercatalognumbers' => 'Other Catalog Number','family' => 'Family','identificationqualifier' => 'ID Qualifier',
						'sciname' => 'Scientific name','recordedby' => 'Recorded By','recordnumber' => 'Number',
						'associatedcollectors' => 'Associated Collectors','eventdate' => 'Event Date',
						'verbatimeventdate' => 'Verbatim Date','country' => 'Country','stateprovince' => 'State/Province',
						'county' => 'county','municipality' => 'municipality','locality' => 'locality','decimallatitude' => 'Latitude',
						'decimallongitude' => 'Longitude','geodeticdatum' => 'Datum',
						'coordinateuncertaintyinmeters' => 'Uncertainty In Meters','verbatimcoordinates' => 'Verbatim Coordinates',
						'georeferencedby' => 'Georeferenced By','georeferenceprotocol' => 'Georeference Protocol','georeferencesources' => 'Georeference Sources',
						'georeferenceverificationstatus' => 'Georef Verification Status','georeferenceremarks' => 'Georef Remarks',
						'minimumelevationinmeters' => 'Min. Elev. (m)','maximumelevationinmeters' => 'Max. Elev. (m)','verbatimelevation' => 'Verbatim Elev.',
						'habitat' => 'Habitat','substrate' => 'Substrate','occurrenceremarks' => 'Notes','associatedtaxa' => 'Associated Taxa',
						'verbatimattributes' => 'Description','reproductivecondition' => 'Reproductive Condition',
						'identificationremarks' => 'Identification Remarks','identifiedby' => 'Identified By',
						'dateidentified' => 'Date Identified', 'identificationreferences' => 'Identification References',
						'typestatus' => 'Type Status','cultivationstatus' => 'Cultivation Status','establishmentmeans' => 'Establishment Means',
						'disposition' => 'disposition','duplicatequantity' => 'Duplicate Qty','dateLastModified' => 'Date Last Modified',
						'processingstatus' => 'Processing Status','recordenteredby' => 'Entered By','basisofrecord' => 'Basis Of Record');
					
					$headerArr = array();
					foreach($recArr as $id => $occArr){
						foreach($occArr as $k => $v){
							if(trim($v) && !array_key_exists($k,$headerArr)){
								$headerArr[$k] = $k;
							}
						}
					}
					if($qCustomField1 && !array_key_exists(strtolower($qCustomField1),$headerArr)){
						$headerArr[strtolower($qCustomField1)] = strtolower($qCustomField1); 
					}
					if($qCustomField2 && !array_key_exists(strtolower($qCustomField2),$headerArr)){
						$headerArr[strtolower($qCustomField2)] = strtolower($qCustomField2); 
					}
					if($qCustomField3 && !array_key_exists(strtolower($qCustomField3),$headerArr)){
						$headerArr[strtolower($qCustomField3)] = strtolower($qCustomField3); 
					}
					$headerMap = array_intersect_key($headerMap, $headerArr);
					?>
					<table class="styledtable">
						<tr>
							<th>Symbiota ID</th>
							<?php 
							foreach($headerMap as $k => $v){
								echo '<th>'.$v.'</th>';
							}
							?>
						</tr>
						<?php 
						$recCnt = 0;
						foreach($recArr as $id => $occArr){
							if($occArr['sciname']){
								$scinameStr = '<i>'.$occArr['sciname'].'</i> ';
								$scinameStr .= $occArr['scientificnameauthorship'];
								$occArr['sciname'] = $scinameStr;
							}							
							echo "<tr ".($recCnt%2?'class="alt"':'').">\n";
							echo '<td>';
							echo '<a href="occurrenceeditor.php?occindex='.($recCnt+$occIndex).'&occid='.$id.'" target="_blank">';
							echo $id;
							echo '</a>';
							echo '</td>'."\n";
							foreach($headerMap as $k => $v){
								$displayStr = $occArr[$k];
								if(strlen($displayStr) > 50){
									$displayStr = substr($displayStr,0,50).'...';
								}
								if($k != 'sciname') $displayStr = htmlentities($displayStr);
								if(!$displayStr) $displayStr = '&nbsp;';
								echo '<td>'.$displayStr.'</td>'."\n";
							}
							echo "</tr>\n";
							$recCnt++;
						}
						?>
					</table>
					*Click on the Symbiota identifier in the first column to open the editor.    
					<?php 
				}
				else{
					?>
					<div style="font-weight:bold;font-size:120%;">
						No records found matching the query
					</div>
					<?php 
				}
			}
			else{
				if(!$isEditor){
					echo '<h2>You are not authorized to access this page</h2>';
				}
			}
		}
		?>
	</div>
<?php 	
//include($serverRoot.'/footer.php');
?>

</body>
</html>