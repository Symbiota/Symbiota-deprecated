<?php
include_once('../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
header("Content-Type: text/html; charset=".$charset);

$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$recLimit = array_key_exists('reclimit',$_REQUEST)?$_REQUEST['reclimit']:100;
$occIndex = array_key_exists('occindex',$_REQUEST)?$_REQUEST['occindex']:0;
$ouid = array_key_exists('ouid',$_REQUEST)?$_REQUEST['ouid']:0;
$crowdSourceMode = array_key_exists('csmode',$_REQUEST)?$_REQUEST['csmode']:0;
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';

$occManager = new OccurrenceEditorManager();

if($crowdSourceMode) $occManager->setCrowdSourceMode(1);

$isEditor = 0;		//If not editor, edits will be submitted to omoccuredits table but not applied to omoccurrences 
$displayQuery = 0;
$isGenObs = 0;
$collMap = array();
$recArr = array();
$headerMap = array('catalognumber' => 'Catalog Number',
	'othercatalognumbers' => 'Other Catalog Number','family' => 'Family','identificationqualifier' => 'ID Qualifier',
	'sciname' => 'Scientific name','scientificnameauthorship'=>'Author','recordedby' => 'Collector','recordnumber' => 'Number',
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

	if($collMap['colltype']=='General Observations') $isGenObs = 1;
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

	if(array_key_exists('bufieldname',$_POST)){
		if($ouid){
			$occManager->setQueryVariables(array('ouid' => $ouid));
		}
		else{
			$occManager->setQueryVariables();
		}
		$occManager->setSqlWhere();
		$statusStr = $occManager->batchUpdateField($_POST['bufieldname'],$_POST['buoldvalue'],$_POST['bunewvalue'],$_POST['bumatch']);
	}

	if($ouid){
		$occManager->setQueryVariables(array('ouid' => $ouid));
		$occManager->setSqlWhere(0,$recLimit);
		$qryCnt = $occManager->getQueryRecordCount();
	}
	elseif($occIndex !== false){
		//Query Form has been activated 
		$occManager->setQueryVariables();
		$occManager->setSqlWhere($occIndex,$recLimit);
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
	$navStr .= ($occIndex+1).'-'.($qryCnt<$recLimit+$occIndex?$qryCnt:$recLimit+$occIndex).' of '.$qryCnt.' records';
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
	<script type="text/javascript" src="../../js/symb/collections.occureditorshare.js?cacherefresh=<?php echo time(); ?>"></script>
</head>
<body style="margin-left: 0px; margin-right: 0px;">
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
			if(($isEditor || $crowdSourceMode) && $collId){
				?>
				<div style="text-align:right;width:790px;margin:-30px 15px 5px 0px;">
					<a href="#" title="Search / Filter" onclick="toggleSearch();return false;"><img src="../../images/find.png" style="width:14px;" /></a>
					<?php
					if($isEditor == 1 || $isGenObs){
						?>
						<a href="#" title="Batch Update Tool" onclick="toggleBatchUpdate();return false;"><img src="../../images/editplus.png" style="width:14px;" /></a>
						<?php
					} 
					?>
				</div>
				<?php 
				if(!$recArr) $displayQuery = 1;
				if($crowdSourceMode){
					include 'includes/queryformcrowdsource.php';
				}
				else{
					include 'includes/queryform.php';
				}
				//Setup header map
				if($recArr){
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
					if(isset($qCustomField2) && !array_key_exists(strtolower($qCustomField2),$headerArr)){
						$headerArr[strtolower($qCustomField2)] = strtolower($qCustomField2); 
					}
					if(isset($qCustomField3) && !array_key_exists(strtolower($qCustomField3),$headerArr)){
						$headerArr[strtolower($qCustomField3)] = strtolower($qCustomField3); 
					}
					$headerMap = array_intersect_key($headerMap, $headerArr);
				}
				if($isEditor == 1 || $isGenObs){
					?>
					<div id="batchupdatediv" style="width:600px;clear:both;display:none;">
						<form name="batchupdateform" action="occurrencetabledisplay.php" method="post" onsubmit="return false;">
							<fieldset>
								<legend><b>Batch Update</b></legend>
								<div style="float:left;">
									<div style="margin:2px;">
										Field name: 
										<select name="bufieldname">
											<option value="">Select Field Name</option>
											<option value="">----------------------</option>
											<?php 
											$buFieldName = (array_key_exists('bufieldname',$_POST)?$_POST['bufieldname']:'');
											foreach($headerMap as $k => $v){
												echo '<option value="'.$k.'" '.($buFieldName==$k?$buFieldName:'').'>'.$v.'</option>';
											}
											?>
										</select>
									</div>
									<div style="margin:2px;">
										Current Value: 
										<input name="buoldvalue" type="text" value="<?php echo (array_key_exists('buoldvalue',$_POST)?$_POST['buoldvalue']:''); ?>" /> 
									</div>
									<div style="margin:2px;">
										New Value:
										<input name="bunewvalue" type="text" value="<?php echo (array_key_exists('bunewvalue',$_POST)?$_POST['bunewvalue']:''); ?>" /> 
									</div>
								</div>
								<div style="float:left;margin-left:30px;">
									<div style="margin:2px;">
										<input name="bumatch" type="radio" value="0" <?php echo (!array_key_exists('bumatch',$_POST) || !$_POST['bumatch']?'CHECKED':''); ?> />
										Match Whole Field<br/> 
										<input name="bumatch" type="radio" value="1" <?php echo (array_key_exists('bumatch',$_POST) && $_POST['bumatch']?'CHECKED':''); ?> />
										Match Any Part of Field
									</div>
									<div style="margin:2px;">
										<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
										<input name="ouid" type="hidden" value="<?php echo $ouid; ?>" />
										<input name="occid" type="hidden" value="" />
										<input name="occindex" type="hidden" value="0" />
										<input name="submitaction" type="submit" value="Batch Update Field" onclick="submitBatchUpdate(this.form); return false;" />
									</div>
								</div>
							</fieldset>
						</form>
					</div>
					<?php 
				}					
				?>
				<div style="width:790px;clear:both;">
					<?php
					if(isset($collections_editor_occurrencetableviewCrumbs)){
						if($collections_editor_occurrencetableviewCrumbs){
							?>
							<div class='navpath'>
								<a href='../../index.php'>Home</a> &gt;&gt; 
								<?php echo $collections_editor_occurrencetableviewCrumbs; ?>
								<b>Occurrence Record Table View</b>
							</div>
							<?php 
						}
					}
					else{
					?>
						<span class='navpath'>
							<a href="../../index.php">Home</a> &gt;&gt;
							<?php
							if($crowdSourceMode){
								?>
								<a href="crowdsourcecentral.php?">Crowd Sourcing Central</a> &gt;&gt;
								<?php
							}
							else{
								if(!$isGenObs || $isAdmin){ 
									?>
									<a href="../misc/collprofiles.php?collid=<?php echo $collId; ?>&emode=1">Collection Management</a> &gt;&gt;
									<?php
								}
								if($isGenObs){ 
									?>
									<a href="../../profile/viewprofile.php?tabindex=1">Personal Management</a> &gt;&gt;
									<?php
								}
							}
							?>
							<b>Occurrence Record Table View</b>
						</span>
					<?php
					}
					echo $navStr; ?>
				</div>
				<?php 
				if($recArr){
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
								$occArr['sciname'] = '<i>'.$occArr['sciname'].'</i> ';
							}							
							echo "<tr ".($recCnt%2?'class="alt"':'').">\n";
							echo '<td>';
							echo '<a href="occurrenceeditor.php?csmode='.$crowdSourceMode.'&occindex='.($recCnt+$occIndex).'&occid='.$id.'" target="_blank">';
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
					<div style="width:790px;">
						<?php echo $navStr; ?>
					</div>
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