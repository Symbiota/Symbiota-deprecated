<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
header("Content-Type: text/html; charset=".$charset);

$oid = $_REQUEST["oid"];
$occidStr = (array_key_exists('occids',$_GET)?$_GET['occids']:'');
$collId = (array_key_exists('collid',$_GET)?$_GET['collid']:'');
$occId = (array_key_exists('occid',$_GET)?$_GET['occid']:'');
$submitAction = (array_key_exists('submitaction',$_GET)?$_GET['submitaction']:'');

$dupManager = new OccurrenceEditorManager();
$occArr = array();
if($occidStr) $occArr = $dupManager->getDupOccurrences($oid,$occidStr);

$onLoadStr = '';
$statusStr = '';
if($submitAction){
	$isEditor = 0;
	if($isAdmin 
		|| (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])) 
		|| (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))){
		$isEditor = 1;
	}
	if(!$isEditor && $occManager->getObserverUid() == $symbUid){
		$isEditor = 1;
	}
	if($isEditor){
		if($submitAction == 'mergerecs'){
			$onLoadStr = 'window.close()';
			$statusStr = $dupManager->mergeRecords($oid,$occId);
			if($statusStr){
				$onLoadStr = '';
			}
		}
	}
}

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
					foreach($oArr as $k => $v){
						if($v) echo 'oArr["'.$k.'"] = "'.str_replace('"',"''",$v).'";'."\n";
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
				openerForm.processingstatus.value = "reviewed";
				window.close();
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
				foreach($occArr as $occId => $occObj){
					?>
					<div style="font-weight:bold;font-size:120%;">
						<?php echo $occObj['institutioncode'].($occObj['collectioncode']?':'.$occObj['collectioncode']:''); ?>
					</div>
					<?php if($collId == $occObj['colliddup']){ ?>
						<div style="color:red;">
							NOTICE: Matches target collection. May already exist in this collection.
						</div>
					<?php } ?>
					<div>
						<?php 
						echo '<span title="recordedby">'.$occObj['recordedby'].'</span>';
						if($occObj['recordnumber']) echo '<span style="margin-left:20px;" title="recordnumber">'.$occObj['recordnumber'].'</span>';
						if($occObj['eventdate']){
							echo '<span style="margin-left:20px;" title="eventdate">'.$occObj['eventdate'].'</span>';
						}
						else{
							echo '<span style="margin-left:20px;" title="verbatimeventdate">'.$occObj['verbatimeventdate'].'</span>';
						}
						if($occObj['associatedcollectors']) echo '<div style="margin-left:10px;" title="associatedcollectors">Assoc. Collectors: '.$occObj['associatedcollectors'].'</div>';
						?> 
					</div>
					<div>
						<?php
						if($occObj['identificationqualifier']) echo '<span title="identificationqualifier">'.$occObj['identificationqualifier'].'</span> '; 
						echo '<span title="sciname"><i>'.$occObj['sciname'].'</i></span> ';
						echo '<span title="scientificnameauthorship">'.$occObj['scientificnameauthorship'].'</span>';
						echo '<span style="margin-left:25px;color:red;" title="typestatus">'.$occObj['typestatus'].'</span>'; 
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
						echo '<span title="country">'.$occObj['country'].'</span>; ';
						echo '<span title="stateprovince">'.$occObj['stateprovince'].'</span>; ';
						echo '<span title="county">'.$occObj['county'].'</span>; ';
						echo '<span title="locality">'.$occObj['locality'].'</span>';
						?>
					</div>
					<?php 
					if($occObj['habitat']) echo '<div title="habitat">'.$occObj['habitat'].'</div>';
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
								Import Full Record
							</a>
						</span>
						<span style="margin-left:30px;">
							<a href="" onclick="transferRecord(<?php echo $occId; ?>,true);">
								Append Record
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
						if($oid){ 
							?>
							<span style="margin-left:30px;">
								<a href="dupsearch.php?submitaction=mergerecs&oid=<?php echo $oid.'&occid='.$occId; ?>" onclick="return confirm('Are you sure you want to merge these two records?')">
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

