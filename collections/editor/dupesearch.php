<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceEditorDupes.php');
header("Content-Type: text/html; charset=".$charset);

$occidQuery = array_key_exists('occidquery',$_REQUEST)?$_REQUEST['occidquery']:'';
$curOccid = (array_key_exists('curoccid',$_GET)?$_REQUEST["curoccid"]:0);
$collId = (array_key_exists('collid',$_GET)?$_GET['collid']:0);
$cNum = (array_key_exists('cnum',$_GET)?$_GET['cnum']:'');
$isExactMatch = (array_key_exists('exact',$_GET)?$_GET['exact']:1);

$occIdMerge = (array_key_exists('occidmerge',$_GET)?$_GET['occidmerge']:'');
$submitAction = (array_key_exists('submitaction',$_GET)?$_GET['submitaction']:'');

$dupeManager = new OccurrenceEditorDupes();

$occArr = array();
if(!$submitAction && $occidQuery){
	$occArr = $dupeManager->getDupesOccid($occidQuery);
}

$onLoadStr = '';
$statusStr = '';
if($submitAction){
	$isEditor = 0;
	if($isAdmin 
		|| (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])) 
		|| (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))){
		$isEditor = 1;
	}
	if($isEditor){
		if($submitAction == 'mergerecs'){
			$statusStr = $dupeManager->mergeRecords($curOccid,$occIdMerge);
			$onLoadStr = 'reloadParent()';
		}
	}
}

$firstOcc = reset($occArr);
?>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php echo $defaultTitle; ?> - Duplicate Record Search</title>
		<script type="text/javascript">
			var occArr = new Array();
			<?php 
			if($occArr){
				foreach($occArr as $occId => $oArr){
					echo 'var oArr = new Array();'."\n";
					$tempOcc = $oArr;
					unset($tempOcc['occid']);
					unset($tempOcc['catalognumber']);
					unset($tempOcc['othercatalognumbers']);
					if(!$isExactMatch){
						unset($tempOcc['family']);
						unset($tempOcc['sciname']);
						unset($tempOcc['tidtoadd']);
						unset($tempOcc['scientificnameauthorship']);
						unset($tempOcc['taxonremarks']);
						unset($tempOcc['identifiedby']);
						unset($tempOcc['dateidentified']);
						unset($tempOcc['identificationreferences']);
						unset($tempOcc['identificationremarks']);
						unset($tempOcc['identificationqualifier']);
						unset($tempOcc['typestatus']);
						unset($tempOcc['recordnumber']);
					}
					foreach($tempOcc as $k => $v){
						if($v) echo 'oArr["'.$k.'"] = "'.str_replace(array('"',"\n"),"",$v).'";'."\n";
					}
					echo 'occArr['.$occId.'] = oArr;'."\n";
				}
			}
			?>

			function transferRecord(occId,appendMode){
				var tArr = occArr[occId]; 
				var openerForm = opener.document.fullform;
				for(var k in tArr){
					try{
						var elem = openerForm.elements[k];
						if(appendMode == false || elem.value == ""){
							elem.value = tArr[k];
							if(k != "tidtoadd") opener.fieldChanged(k);
						}
					}
					catch(err){
					}
				}
				window.close();
			}

			function reloadParent(){
				opener.pendingDataEdits = false;
				var qForm = opener.document.queryform;
				qForm.occid.value = <?php echo $curOccid; ?>;
				if(opener.document.fullform.occindex) qForm.occindex.value = opener.document.fullform.occindex.value;
				opener.document.queryform.submit();
				//opener.location.reload();
				<?php
				if($statusStr === true){ 
					?>
					window.close();
					<?php 
				}
				?>
			}

		</script>
	</head> 
	<body onload="<?php echo $onLoadStr; ?>">
		<!-- inner text -->
		<div id="innertext">
			<?php
			if($statusStr){
				?>
				<hr/>
				<div style="margin:10px;color:red;">
					<?php echo $statusStr; ?>
				</div>
				<hr/>
				<?php 
			}
			if($occArr){
				echo '<div style="font-weight:bold;font-size:130%;">';
				if($isExactMatch){
					echo 'Possible EXACT duplicates';
				}
				else{
					echo 'Possible matching duplicate events';
				}
				echo '</div><hr/>';
				$collArr = array();
				if(!$isAdmin){
					if(array_key_exists('CollAdmin',$userRights)){
						$collArr = $userRights['CollAdmin'];
					}
					if(array_key_exists('CollEditor',$userRights)){
						$collArr = array_merge($collArr,$userRights['CollEditor']);
					}
				}
				foreach($occArr as $occId => $occObj){
					if($isAdmin || in_array($occObj['colliddup'],$collArr)){
						?>
						<div style="float:right;margin:10px;">
							<a href="occurrenceeditor.php?occid=<?php echo $occId; ?>">
								<img src="../../images/edit.png" />
							</a>
						</div>
						<?php
					}
					?> 
					<div style="font-weight:bold;font-size:120%;">
						<?php echo $occObj['institutioncode'].($occObj['collectioncode']?':'.$occObj['collectioncode']:''); ?>
					</div>
					<?php if($collId == $occObj['colliddup'] && $isExactMatch){ ?>
						<div style="color:red;">
							NOTICE: Possible exact matches within collection. Record may already exist.
						</div>
						<div style="font-weight:bold;">
							<?php 
							if($occObj['catalognumber']) echo $occObj['catalognumber'];
							if($occObj['othercatalognumbers']) echo ' ('.$occObj['othercatalognumbers'].')';
							?>
						</div>
					<?php } ?>
					<div>
						<?php 
						echo '<span title="recordedby">'.($occObj['recordedby']?$occObj['recordedby']:'Collector field empty').'</span>';
						if($occObj['recordnumber']) echo '<span style="margin-left:20px;" title="recordnumber">'.$occObj['recordnumber'].'</span>';
						if($occObj['eventdate']){
							echo '<span style="margin-left:20px;" title="eventdate">'.$occObj['eventdate'].'</span>';
						}
						elseif($occObj['verbatimeventdate']){
							echo '<span style="margin-left:20px;" title="verbatimeventdate">'.$occObj['verbatimeventdate'].'</span>';
						}
						else{
							echo '<span style="margin-left:20px;" title="eventdate">Date field empty</span>';
						}
						if($occObj['associatedcollectors']) echo '<div style="margin-left:10px;" title="associatedcollectors">Assoc. Collectors: '.$occObj['associatedcollectors'].'</div>';
						?> 
					</div>
					<div>
						<?php
						if($occObj['sciname']){
							if($occObj['identificationqualifier']) echo '<span title="identificationqualifier">'.$occObj['identificationqualifier'].'</span> '; 
							echo '<span title="sciname"><i>'.$occObj['sciname'].'</i></span> ';
							echo '<span title="scientificnameauthorship">'.$occObj['scientificnameauthorship'].'</span>';
							echo '<span style="margin-left:25px;color:red;" title="typestatus">'.$occObj['typestatus'].'</span>';
						}
						else{
							echo '<span title="sciname">Scientific Name empty</span> ';
						}
						?>
					</div>
					<div style='margin-left:10px;'>
						<?php 
						if($occObj['identificationremarks'] || $occObj['identificationreferences']){
							echo '<span title="identificationremarks">'.$occObj['identificationremarks'].'</span>';
							if($occObj['identificationremarks'] && $occObj['identificationreferences']) echo '<br/>';
							echo '<span title="identificationreferences">'.$occObj['identificationreferences'].'</span>';
						} 
						?>
					</div>
					<div>
						<?php
						if($occObj['country']) echo '<span title="country">'.$occObj['country'].'</span>; ';
						if($occObj['stateprovince']) echo '<span title="stateprovince">'.$occObj['stateprovince'].'</span>; ';
						if($occObj['county']) echo '<span title="county">'.$occObj['county'].'</span>; ';
						echo '<span title="locality">'.($occObj['locality']?$occObj['locality']:'Locality data empty').'</span>';
						?>
					</div>
					<?php 
					if($occObj['habitat']) echo '<div title="habitat">'.$occObj['habitat'].'</div>';
					if($occObj['substrate']) echo '<div title="substrate">'.$occObj['substrate'].'</div>';
					if($occObj['decimallatitude'] || $occObj['verbatimcoordinates']){
						?>
						<div>
							<?php 
							echo '<span title="decimallatitude">'.$occObj['decimallatitude'].'</span>; ';
							echo '<span title="decimallongitude">'.$occObj['decimallongitude'].'</span>';
							if($occObj['coordinateuncertaintyinmeters']) echo ' +-<span title="coordinateuncertaintyinmeters">'.$occObj['coordinateuncertaintyinmeters'].'</span>m. ';
							if($occObj['geodeticdatum']) echo ' (<span title="geodeticdatum">'.$occObj['geodeticdatum'].'</span>)';
							if($occObj['verbatimcoordinates']) echo '<div style="margin-left:10px;" title="verbatimcoordinates">'.$occObj['verbatimcoordinates'].'</div>';
							$geoDetails = ''; 
							if($occObj['georeferenceprotocol']) $geoDetails = '; <span title="georeferenceprotocol">'.$occObj['georeferenceprotocol']."</span>";
							if($occObj['georeferencesources']) $geoDetails = '; <span title="georeferencesources">'.$occObj['georeferencesources']."</span>";
							if($occObj['georeferenceremarks']) $geoDetails = '; <span title="georeferenceremarks">'.$occObj['georeferenceremarks']."</span>";
							$geoDetails = trim($geoDetails,';');
							if($geoDetails) echo '<div style="margin-left:10px;">'.$geoDetails.'</div>';
							?>
						</div>
						<?php
					}
					if($occObj['minimumelevationinmeters'] || $occObj['verbatimelevation']){
						?>
						<div>
							<?php 
							if($occObj['minimumelevationinmeters']){
								echo '<span title="minimumelevationinmeters">'.$occObj['minimumelevationinmeters'].'</span>';
								if($occObj['maximumelevationinmeters']) echo '-<span title="maximumelevationinmeters">'.$occObj['maximumelevationinmeters'].'</span>';
								echo ' meters ';
							}
							if($occObj['verbatimelevation']) echo 'Verbatim elev: <span title="verbatimelevation">'.$occObj['verbatimelevation'].'</span>';
							?>
						</div>
						<?php
					} 
					if($occObj['occurrenceremarks']) echo '<div title="occurrenceremarks">Notes: '.$occObj['occurrenceremarks'].'</div>';
					if($occObj['associatedtaxa']) echo '<div title="associatedtaxa">Associated Taxa: '.$occObj['associatedtaxa'].'</div>';
					if($occObj['dynamicproperties']) echo '<div title="dynamicproperties">Description: '.$occObj['dynamicproperties'].'</div>';
					if($occObj['reproductivecondition'] || $occObj['establishmentmeans']){
						echo '<div>Misc: '.trim($occObj['reproductivecondition'].'; '.$occObj['establishmentmeans'],'; ').'</div>';
					}
					?>
					<div style="margin:10px;">
						<span>
							<a href="" onclick="transferRecord(<?php echo $occId; ?>,false);">
								Transfer All Fields
							</a>
						</span>
						<span style="margin-left:30px;">
							<a href="" onclick="transferRecord(<?php echo $occId; ?>,true);">
								Transfer to Empty Fields Only 
							</a>
						</span>
					<?php 
					if($collId == $occObj['colliddup']){ 
						?>
						<span style="margin-left:30px;">
							<a href="occurrenceeditor.php?occid=<?php echo $occId; ?>">
								Go To Record
							</a>
						</span>
						<?php 
						if($curOccid){ 
							?>
							<span style="margin-left:30px;">
								<a href="dupesearch.php?submitaction=mergerecs&curoccid=<?php echo $curOccid.'&occidmerge='.$occId; ?>" onclick="return confirm('Are you sure you want to merge these two records?')">
									Merge Records
								</a>
							</span>
							<?php 
						}
					} 
					?>
					</div>
					<hr/>
					<?php 
				}
			}
			else{
				echo '<h2>No duplicate records have been located</h2>';
			}
			?>
		</div>
	</body>
</html>

