<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceGeoLocate.php');
header("Content-Type: text/html; charset=".$charset);

if(!$SYMB_UID) header('Location: '.$serverRoot.'/profile/index.php?refurl=../misc/generaltemplate.php?'.$_SERVER['QUERY_STRING']);

$collid = $_REQUEST['collid'];
$action = array_key_exists('action',$_POST)?$_POST['action']:'';
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$geoLocateManager = new OccurrenceGeoLocate.php();
$geoLocateManager->setCollid($collid);

$isEditor = 0; 
if($SYMB_UID){
	if($IS_ADMIN){
		$isEditor = 1;
	}
	elseif($collid){
		if(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])){
			$isEditor = 1;
		}
	}
}

$occRecArr = array();
if($isEditor){
	if(array_key_exists('qCountry',$_POST) && $_POST['qCountry']){
		$geoLocateManager->addFilterTerm('country', $_POST['qCountry']);
	}
	if(array_key_exists('qStateProvince',$_POST) && $_POST['qStateProvince']){
		$geoLocateManager->addFilterTerm('stateProvince', $_POST['qStateProvince']);
	}
	if(array_key_exists('qCounty',$_POST) && $_POST['qCounty']){
		$geoLocateManager->addFilterTerm('county', $_POST['qCounty']);
	}
	if(array_key_exists('qLocality',$_POST) && $_POST['qLocality']){
		$geoLocateManager->addFilterTerm('locality', $_POST['qLocality']);
	}

	if($action == '1'){
		$occRecArr = $geoLocateManager->batchConvertTrs();
	}
	elseif($action == '2'){
		$occRecArr = $geoLocateManager->batchConvertTrs();
	}
	elseif($formSubmit == 'Submit Batch Coordinates'){
		$statusStr = $geoLocateManager->loadOccurrences($_POST);
	}
	
}

?>
<html>
	<head>
		<title>GeoLocate Batch Processes</title>
		<link href="<?php echo $clientRoot; ?>/css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $clientRoot; ?>/css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $clientRoot; ?>/css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<script src="<?php echo $clientRoot; ?>/js/jquery.js" type="text/javascript"></script>
		<script src="<?php echo $clientRoot; ?>/js/jquery-ui.js" type="text/javascript"></script>
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($serverRoot.'/header.php');
		?>
		<div class="navpath">
			<a href="<?php echo $clientRoot; ?>/index.php">Home</a> &gt;&gt; 
			<a href="../misc/collprofiles.php?emode=1&collid=<?php echo $collId; ?>">Collection Management Menu</a> &gt;&gt;
			<b>Batch GeoLocate Tools</b>
		</div>
		<!-- This is inner text! -->
		<div id="innertext">
		<?php 
		if($collId){
			if($isEditor){
				?>
				<fieldset>
					<legend>Main Menu</legend>
					<div>
						Records available for TRS conversion: <?php echo $classManager->getTrsOccurrenceCount(); ?>
					</div>
					<div>
						Records available for batch GeoReferencing: <?php echo $classManager->getOccurrenceCount(); ?>
					</div>
					<form method="post" action="geolocatetools">
						<div>
							<b><u>Filter Terms</u></b>
							<div style="margin:0px 10px;">
								<b>Country:</b> <input name="country" type="text" value="<?php echo $qCountry; ?>" /><br/>
								<b>State / Province:</b> <input name="stateProvince" type="text" value="<?php echo $qStateProvince; ?>" /><br/>
								<b>County / Parish:</b> <input name="county" type="text" value="<?php echo $qCounty; ?>" /><br/>
								<b>Locality:</b> <input name="locality" type="text" value="<?php echo $qLocality; ?>" /><br/>
							</div>
						</div>
						<div>
							<b><u>Action</u></b>
							<div style="margin:0px 10px;">
								<input name="action" type="radio" value="1" /> Batch process TRS records<br/>
								<input name="action" type="radio" value="2" /> Batch process locality reocrds<br/>
								<input name="action" type="radio" value="0" checked /> Refresh counts
							</div>
						</div>
						<div>
							<input name="formsubmit" type="submit" value="Perform Action" />
							<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
						</div>
					</form>
				</fieldset>
				<?php 
				if($occRecArr){
					//Review conversions before submitting
					//Reviews need to be limited to a few hunred 
					//More than 100 records can be batch processed, but without review 
					
					?>
					<form name="coordsubmitform" action="geolocatetool" method="post">
						<table class="styledtable" style="font-family:Arial;font-size:12px;">
							<tr>
								<th>occid</th>
								<th>Map Tool</th>
								<th>Locality</th>
								<th>Decimal Lat.</th>
								<th>Decimal Long.</th>
								<th>Coord. Error in meters</th>
							</tr>
							<?php 
							foreach($occRecArr as $occid => $occArr){
								echo '<tr>';
								echo '<td><a href="">'.$occid.'</a></td>';
								echo '<td>'.$occArr['loc'].'</td>';
								echo '<td></td>';
								echo '<td><input name="lat-'.$occid.'" type="text" value="'.$occArr['declat'].'" /></td>';
								echo '<td><input name="lng-'.$occid.'" type="text" value="'.$occArr['declng'].' /></td>';
								echo '<td><input name="err-'.$occid.'" type="text" value="'.$occArr['coorderr'].' /></td>';
								echo '</tr>';
							}
							?>
						</table>
						<div>
							<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
							<input name="formsubmit" type="submit" value="Submit Batch Coordinates" />
						</div>
					</form>
					<?php 
				}
			}
			else{
				?>
				<div style='font-weight:bold;font-size:120%;'>
					ERROR: You do not have permission to edit this collection
				</div>
				<?php 
			}
		}
		else{
			?>
			<div style='font-weight:bold;font-size:120%;'>
				ERROR: Collection identifier is null
			</div>
			<?php 
		}
		

		?>	
		</div>
		<?php
			include($serverRoot.'/footer.php');
		?>
	</body>
</html>
