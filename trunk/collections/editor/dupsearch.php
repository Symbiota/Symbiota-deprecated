<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
header("Content-Type: text/html; charset=".$charset);
$occidStr = array_key_exists("occids",$_REQUEST)?$_REQUEST["occids"]:0;
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$dupManager = new OccurrenceEditorManager();
$occArr = $dupManager->getDupOccurrences($occidStr);

?>
<html xmlns="http://www.w3.org/1999/xhtml">
	<head>
		<title><?php echo $defaultTitle; ?> - Duplicate Record Search</title>
	</head> 
	<body>
		<!-- inner text -->
		<div id="innertext">
			<?php 
			if($occArr){
				foreach($occArr as $occId => $occObj){
					?>
					<div style="font-weight:bold;font-size:120%;">
						<?php echo $occObj['institutioncode'].($occObj['collectioncode']?':'.$occObj['collectioncode']:''); ?>
					</div>
					<div>
						<?php 
						echo $occObj['recordedby'];
						if($occObj['recordnumber']) echo '<span style="margin-left:20px;">'.$occObj['recordnumber'].'</span>';
						echo '<span style="margin-left:20px;">'.($occObj['eventdate']?$occObj['eventdate']:$occObj['verbatimeventdate']).'</span>'; 
						if($occObj['associatedcollectors']) echo '<div style="margin-left:10px;"><b>Assoc. Collectors:</b> '.$occObj['associatedcollectors'].'</div>';
						?> 
					</div>
					<div>
						<?php
						if($occObj['identificationqualifier']) echo $occObj['identificationqualifier'].' '; 
						echo '<i>'.$occObj['sciname'].'</i> '.$occObj['scientificnameauthorship'];
						echo '<span style="margin-left:25px;color:red;">'.$occObj['typestatus'].'</span>'; 
						?>
					</div>
					<div style='margin-left:10px;'>
						<?php 
						if($occObj['identificationremarks'] || $occObj['identificationreferences']){
							echo $occObj['identificationremarks'];
							if($occObj['identificationremarks'] && $occObj['identificationreferences']) echo '<br/>';
							echo $occObj['identificationreferences'];
						} 
						?>
					</div>
					<div>
						<?php 
						echo $occObj['country'].'; '.$occObj['stateprovince'].'; '.$occObj['county'].'; '.$occObj['locality'];
						?>
					</div>
					<div>
						<?php 
						echo $occObj['decimallatitude'].' '.$occObj['decimallongitude'];
						if($occObj['coordinateuncertaintyinmeters']) echo ' +-'.$occObj['coordinateuncertaintyinmeters'].'m. ';
						if($occObj['geodeticdatum']) echo ' ('.$occObj['geodeticdatum'].')';
						if($occObj['verbatimcoordinates']) echo '<div style="margin-left:10px;">'.$occObj['verbatimcoordinates'].'</div>';
						$geoDetails = ''; 
						if($occObj['georeferenceprotocol']) $geoDetails = '; '.$occObj['georeferenceprotocol'];
						if($occObj['georeferencesources']) $geoDetails = '; '.$occObj['georeferencesources'];
						if($occObj['georeferenceremarks']) $geoDetails = '; '.$occObj['georeferenceremarks'];
						$geoDetails = trim($geoDetails,'; ');
						if($geoDetails) echo '<div style="margin-left:10px;">'.$geoDetails.'</div>';
						?>
					</div>
					<div>
						<?php 
						if($occObj['minimumelevationinmeters']){
							echo $occObj['minimumelevationinmeters'];
							if($occObj['maximumelevationinmeters']) echo '-'.$occObj['maximumelevationinmeters'];
							echo ' meters ';
						}
						if($occObj['verbatimelevation']) echo 'Verbatim elev: '.$occObj['verbatimelevation'];
						?>
					</div>
					<?php 
					if($occObj['habitat']) echo '<div><b>Habitat:</b> '.$occObj['habitat'].'</div>';
					if($occObj['occurrenceremarks']) echo '<div><b>Notes:</b> '.$occObj['occurrenceremarks'].'</div>';
					if($occObj['associatedtaxa']) echo '<div><b>Associated Taxa:</b> '.$occObj['associatedtaxa'].'</div>';
					if($occObj['dynamicproperties']) echo '<div><b>Description:</b> '.$occObj['dynamicproperties'].'</div>';
					if($occObj['reproductivecondition'] || $occObj['establishmentmeans']){
						echo '<div><b>Misc:</b> '.trim($occObj['reproductivecondition'].'; '.$occObj['establishmentmeans'],'; ').'</div>';
					}
					?>
					<div style="margin:10px;">
						<a href="occurrenceeditor.php?submitaction=carryoverdup&collid=<?php echo $collId.'&targetoccid='.$occId; ?>" target="_parent">
							Copy Record Data to Entry Form
						</a>
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

