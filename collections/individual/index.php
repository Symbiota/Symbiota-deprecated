<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceIndividualManager.php');
header("Content-Type: text/html; charset=".$charset);

$occId = array_key_exists("occid",$_REQUEST)?trim($_REQUEST["occid"]):0;
$collId = array_key_exists("collid",$_REQUEST)?trim($_REQUEST["collid"]):0;
$pk = array_key_exists("pk",$_REQUEST)?trim($_REQUEST["pk"]):"";
$submit = array_key_exists('formsubmit',$_REQUEST)?trim($_REQUEST['formsubmit']):'';
$tabIndex = array_key_exists('tabindex',$_REQUEST)?$_REQUEST['tabindex']:0;
$clid = array_key_exists("clid",$_REQUEST)?trim($_REQUEST["clid"]):0;

$indManager = new OccurrenceIndividualManager();
if($occId){
	$indManager->setOccid($occId);
}
elseif($collId && $pk){
	$indManager->setCollId($collId);
	$indManager->setDbpk($pk);
}

$occArr = $indManager->getOccData();
if(!$occId) $occId = $indManager->getOccid();
$collMetadata = $indManager->getMetadata();
if(!$collId) $collId = $occArr['collid'];

$statusStr = '';
$displayLocality = false;
$isEditor = false;

if($symbUid){
	if(array_key_exists("SuperAdmin",$userRights) 
	|| (array_key_exists('CollAdmin',$userRights) && in_array($collId,$userRights['CollAdmin']))
	|| (array_key_exists('CollEditor',$userRights) && in_array($collId,$userRights['CollEditor']))
	|| $occArr['observeruid'] == $symbUid){
		$isEditor = true;
	}
	if($isEditor || array_key_exists("RareSppAdmin",$userRights) || array_key_exists("RareSppReadAll",$userRights) 
	|| (array_key_exists("RareSppReader",$userRights) && in_array($collId,$userRights["RareSppReader"]))){
		$displayLocality = true;
	}
	//Form action submitted
	if(array_key_exists('commentstr',$_POST)){
		$indManager->addComment($_POST['commentstr']);
	}
	elseif($submit == "Delete Comment"){
		$indManager->deleteComment($_POST['comid']);
	}
	elseif(array_key_exists('repcomid',$_GET)){
		$indManager->reportComment($_GET['repcomid']);
	}
	elseif(array_key_exists('publiccomid',$_GET)){
		$indManager->makeCommentPublic($_GET['publiccomid']);
	}
}
if(!$occArr['localitysecurity']) $displayLocality = true;


$displayMap = false;
if($displayLocality && is_numeric($occArr['decimallatitude']) && is_numeric($occArr['decimallongitude'])) $displayMap = true;
$dupeArr = array();
//$dupeArr = $indManager->getDuplicateArr($occArr['duplicateid']);
$commentArr = $indManager->getCommentArr($isEditor);
$editArr = ($isEditor?$indManager->getEditArr():null);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Detailed Collection Record Information</title>
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
    <link href="../../css/main.css" type="text/css" rel="stylesheet">
	<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
	<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>
	<script type="text/javascript">
		var tabIndex = <?php echo $tabIndex; ?>;
		var map;
		var mapInit = false;

		$(document).ready(function() {
			$('#tabs').tabs({ 
				select: function(event, ui) {
					if(ui.panel.id == "maptab" && !mapInit){
						initializeMap();
						mapInit = true;
					}
					return true;
				},
				selected: tabIndex 
			});
		});

		function toggle(target){
			var objDiv = document.getElementById(target);
			if(objDiv){
				if(objDiv.style.display=="none"){
					objDiv.style.display = "block";
				}
				else{
					objDiv.style.display = "none";
				}
			}
			else{
				var divObjs = document.getElementsByTagName("div");
			  	for (i = 0; i < divObjs.length; i++) {
			  		var obj = divObjs[i];
			  		if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
							if(obj.style.display=="none"){
								obj.style.display="inline";
							}
					 	else {
					 		obj.style.display="none";
					 	}
					}
				}
			}
		}

		function verifyVoucherForm(f){
			var clTarget = f.elements["clid"].value; 
	        if(clTarget == "0"){
	            window.alert("Please select a checklist");
	            return false;
	        }
            return true;
	    }

		function verifyCommentForm(f){
	        if(f.commentstr.value.replace(/^\s+|\s+$/g,"")){
	            return true;
	        }
			alert("Please enter a comment");
            return false;
	    }

		<?php 
		if($displayMap){
			?>
			function initializeMap(){
		    	var mLatLng = new google.maps.LatLng(<?php echo $occArr['decimallatitude'].",".$occArr['decimallongitude']; ?>);
		    	var dmOptions = {
					zoom: 8,
					center: mLatLng,
					marker: mLatLng,
					mapTypeId: google.maps.MapTypeId.TERRAIN,
					scaleControl: true
				};
		    	map = new google.maps.Map(document.getElementById("map_canvas"), dmOptions);
		    	//Add marker
				var marker = new google.maps.Marker({
					position: mLatLng,
					map: map
				});
	        }
			<?php 
		}
		?>
	</script>
</head>

<body>
	<?php 
	//$displayLeftMenu = (isset($collections_individual_individualMenu)?$collections_individual_individualMenu:false);
	//include($serverRoot."/header.php");
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<div>
			<?php 
			if($statusStr){
				echo '<hr/>';
				if(array_key_exists('commentstr',$_REQUEST)){
					echo '<div style="padding:15px;">';
					if($isEditor){
						echo 'Comment add successfully. Once reviewed, comment will be made public.';
					}
					echo '</div>';
				}
				echo "<hr/>\n";
			}
			?>
		</div>
		<?php
		if($occArr){
			?>
			<div id="tabs" style="margin:10px;clear:both;">
			    <ul>
			        <li><a href="#occurtab"><span>Details</span></a></li>
			        <?php 
			        if($displayMap){
			        	?>
				        <li><a href="#mapTab"><span>Map</span></a></li>
			        	<?php 
			        }
			        if($dupeArr){
				        ?>
						<li><a href="#dupestab"><span>Duplicate Links</span></a></li>
						<?php
			        }
					?> 
					<li><a href="#commenttab"><span><?php echo ($commentArr?count($commentArr).' ':''); ?>Comments</span></a></li> 
					<li><a href="other.php?occid=<?php echo $occId.'&tid='.$occArr['tidinterpreted'].'&clid='.$clid.'&collid='.$collId.'&obsuid='.$occArr['observeruid']; ?>"><span>Related Resources</span></a></li>
					<?php 
					if($editArr){
				        ?>
						<li><a href="#edittab"><span>Edit History</span></a></li> 
			        	<?php 
					}
					?> 
			    </ul>
				<div id="occurtab" style="padding:30px;">
					<div style="float:left;margin:15px 0px;text-align:center;font-weight:bold;width:120px;">
						<img border='1' height='50' width='50' src='<?php echo (substr($collMetadata["icon"],0,6)=='images'?'../../':'').$collMetadata['icon']; ?>'/><br/>
						<?php 
						echo $collMetadata['institutioncode'];
						if(isset($collMetadata['collectioncode'])){
							echo (strlen($collMetadata['institutioncode'])<7?' : ':'<br/>').$collMetadata['collectioncode'];
						}
						elseif(!isset($occArr['secondaryinstcode']) && isset($occArr['secondarycollcode'])){
							echo (strlen($collMetadata['institutioncode'])<7?' : ':'<br/>').$occArr['secondarycollcode'];
						}
						if($occArr['secondaryinstcode']){
							echo '<div>';
							echo $occArr['secondaryinstcode'];
							if(isset($occArr['secondarycollcode'])){
								echo (strlen($occArr['secondaryinstcode'])<7?' : ':'<br/>');
								echo $occArr['secondarycollcode'];
							}
							echo '</div>';
						}
						?>
					</div>
					<div style="float:left;padding:25px;">
						<span style="font-size:18px;font-weight:bold;vertical-align:60%;">
							<?php echo $collMetadata['collectionname']; ?>
						</span>
					</div>
					<div style="clear:both;margin-left:60px;">
						<div>
							<?php
							if(array_key_exists('loan',$occArr)){
								?>
								<div style="float:right;color:red;font-weight:bold;" title="<?php echo 'Loan #'.$occArr['loan']['identifier']; ?>">
									On Loan to 
									<?php echo $occArr['loan']['code']; ?>
								</div>
								<?php 
							}
							if($occArr['catalognumber']){ 
								?>
								<div>
									<b>Catalog #:</b> 
									<?php echo $occArr['catalognumber']; ?>
								</div>
								<?php 
							}
							if($occArr['othercatalognumbers']){
								?>
								<div title="Other Catalog Numbers">
									<b>Secondary Catalog #:</b>
									<?php echo $occArr['othercatalognumbers']; ?>
								</div>
								<?php 
							}
							?>
						</div>
						<div>
							<b>Taxon:</b> 
							<?php echo ($occArr['identificationqualifier']?$occArr['identificationqualifier']." ":""); ?>
							<i><?php echo $occArr['sciname']; ?></i> <?php echo $occArr['scientificnameauthorship']; ?><br/>
							<b>Family:</b> <?php echo $occArr['family']; ?>
						</div>
						<div>
							<?php 
							if($occArr['identifiedby']){ 
								?>
								<div>
									<b>Determiner:</b> <?php echo $occArr['identifiedby']; ?>
									<?php if($occArr['dateidentified']) echo ' ('.$occArr['dateidentified'].')'; ?>
								</div>
								<?php 
							} 
							if($occArr['identificationremarks']){ 
								?>
								<div style="margin-left:10px;">
									<b>ID Remarks:</b>
									<?php echo $occArr['identificationremarks']; ?>
								</div>
								<?php 
							} 
							if($occArr['identificationreferences']){ ?>
								<div style="margin-left:10px;">
									<b>ID References:</b>
									<?php echo $occArr['identificationreferences']; ?>
								</div>
								<?php 
							}
							if(array_key_exists('dets',$occArr)){
								?>
								<div class="detdiv" style="margin-left:10px;cursor:pointer;" onclick="toggle('detdiv');">
									<img src="../../images/plus.gif" style="border:0px;" />
									Show Determination History
								</div>
								<div class="detdiv" style="display:none;">
									<div style="margin-left:10px;cursor:pointer;" onclick="toggle('detdiv');">
										<img src="../../images/minus.gif" style="border:0px;" />
										Hide Determination History
									</div>
									<fieldset style="width:350px;margin:5px 0px 10px 10px;border:1px solid grey;">
										<legend><b>Determination History</b></legend>
										<?php
										$firstIsOut = false;
										$dArr = $occArr['dets'];
										foreach($dArr as $detId => $detArr){
										 	if($firstIsOut) echo '<hr />';
											 	$firstIsOut = true;
										 	?>
											 <div style="margin:10px;">
											 	<?php 
											 	if($detArr['qualifier']) echo $detArr['qualifier']; 
											 	echo ' <b><i>'.$detArr['sciname'].'</i></b> ';
											 	echo $detArr['author']."\n";
											 	?>
											 	<div style="">
											 		<b>Determiner: </b>
											 		<?php echo $detArr['identifiedby']; ?>
											 	</div>
											 	<div style="">
											 		<b>Date: </b>
											 		<?php echo $detArr['date']; ?>
											 	</div>
											 	<?php 
											 	if($detArr['ref']){ ?>
												 	<div style="">
												 		<b>ID References: </b>
												 		<?php echo $detArr['ref']; ?>
												 	</div>
											 		<?php 
											 	} 
											 	if($detArr['notes']){ 
											 		?>
												 	<div style="">
												 		<b>ID Remarks: </b>
												 		<?php echo $detArr['notes']; ?>
												 	</div>
											 		<?php 
											 	}
											 	?>
											 </div>
											<?php 
										}
										?>
									</fieldset>
								</div>
								<?php 
							}
							if($occArr['typestatus']){ ?>
								<div>
									<b>Type Status:</b>
									<?php echo $occArr['typestatus']; ?>
								</div>
								<?php 
							} 
							?>
						</div>
						<div style="clear:both;">
							<b>Collector:</b> 
							<?php 
							echo $occArr['recordedby'].'&nbsp;&nbsp;&nbsp;';
							echo $occArr['recordnumber'].'&nbsp;&nbsp;&nbsp;';
							?>
						</div>
						<?php
						if($occArr['eventdate']){
							echo '<div><b>Date: </b>'; 
							echo $occArr['eventdate']; 
							if($occArr['eventdateend']){
								echo ' - '.$occArr['eventdateend'];
							}
							echo '</div>';
						}
						if($occArr['verbatimeventdate']){
							echo '<div><b>Verbatim Date:</b>'.$occArr['verbatimeventdate'].'</div>';
						}
						?>
						<div>
							<?php 
							if($occArr['associatedcollectors']){ 
								?>
								<div>
									<b>Additional Collectors:</b> 
									<?php echo $occArr['associatedcollectors']; ?>
								</div>
								<?php 
							}
							?>
						</div>
						<?php 
						$localityStr1 = ($occArr['country']?$occArr['country']:'Country Not Recorded').', ';
						$localityStr1 .= ($occArr['stateprovince']?$occArr['stateprovince']:'State/Province Not Recorded').', ';
						if($occArr['county']) $localityStr1 .= $occArr['county'].', ';
						?>
						<div>
							<b>Locality:</b>
							<?php 
							echo $localityStr1;
							if($displayLocality){
								echo $occArr['locality'];
							}
							else{
								?>
								<span style="color:red;">
									Detailed locality information protected. 
									<?php 
									if($occArr['localitysecurityreason']){
										echo $occArr['localitysecurityreason'];
									}
									else{
										echo 'This is typically done to protect rare or threatened species localities.';
									}
									?>
								</span>
								<?php 
							}
							?>
						</div>
						<?php 
						if($displayLocality){
							if($occArr['decimallatitude']){
								?>
								<div style="margin-left:10px;">
									<?php 
									echo $occArr['decimallatitude'].'&nbsp;&nbsp;'.$occArr['decimallongitude'];
									if($occArr['coordinateuncertaintyinmeters']) echo ' +-'.$occArr['coordinateuncertaintyinmeters'].'m.'; 
									if($occArr['geodeticdatum']) echo '&nbsp;&nbsp;'.$occArr['geodeticdatum'];
									?>
								</div>
								<?php 
							}
							if($occArr['verbatimcoordinates']){
								?>
								<div style="margin-left:10px;">
									<b>Verbatim Coordinates: </b>
									<?php echo $occArr['verbatimcoordinates']; ?>
								</div>
								<?php 
							}
							if($occArr['georeferenceremarks']){
								?>
								<div style="margin-left:10px;clear:both;">
									<b>Georeference Remarks: </b>
									<?php echo $occArr['georeferenceremarks']; ?>
								</div>
								<?php 
							}
							if($occArr['minimumelevationinmeters'] || $occArr['verbatimelevation']){
								?>
								<div style="margin-left:10px;">
									<b>Elevation:</b>
									<?php 
									echo $occArr['minimumelevationinmeters'];
									if($occArr['maximumelevationinmeters']){
										echo '-'.$occArr['maximumelevationinmeters'];
									} 
									?>
									meters 
									<?php
									if(!$occArr['verbatimelevation']){
										echo '('.round($occArr['minimumelevationinmeters']*3.28).($occArr['maximumelevationinmeters']?'-'.round($occArr['maximumelevationinmeters']*3.28):'').'ft)'; 
									}
									?>
								</div>
								<?php
								if($occArr['verbatimelevation']){
									?>
									<div>
										<b>Verbatim Elevation: </b>
										<?php echo $occArr['verbatimelevation']; ?>
									</div>
									<?php 
								}
							}
							if($occArr['habitat']){ 
								?>
								<div style="clear:both;">
									<b>Habitat:</b> 
									<?php echo $occArr['habitat']; ?>
								</div>
								<?php 
							}
							if($occArr['substrate']){ 
								?>
								<div style="clear:both;">
									<b>Substrate:</b> 
									<?php echo $occArr['substrate']; ?>
								</div>
								<?php 
							}
							if($occArr['associatedtaxa']){ 
								?>
								<div style="clear:both;">
									<b>Associated Species:</b> 
									<?php echo $occArr['associatedtaxa']; ?>
								</div>
								<?php 
							}
						}
						if($occArr['verbatimattributes']){ 
							?>
							<div style="clear:both;">
								<b>Description:</b> 
								<?php echo $occArr['verbatimattributes']; ?>
							</div>
							<?php 
						}
						if($occArr['reproductivecondition']){ 
							?>
							<div style="clear:both;">
								<b>Phenology:</b> 
								<?php echo $occArr['reproductivecondition']; ?>
							</div>
							<?php 
						}
						$noteStr = '';
						if($occArr['occurrenceremarks']) $noteStr .= "; ".$occArr['occurrenceremarks'];
						if($occArr['establishmentmeans']) $noteStr .= "; ".$occArr['establishmentmeans'];
						if($occArr['cultivationstatus']) $noteStr .= "; Cultivated";
						if($noteStr){ 
							?>
							<div style="clear:both;">
								<b>Notes:</b>
								<?php echo substr($noteStr,2); ?>
							</div>
							<?php 
						}
						if($occArr['disposition']){
							?>
							<div style="clear:both;">
								<b>Duplicates sent to: </b>
								<?php echo $occArr['disposition']; ?>
							</div>
							<?php 
						}
						?>
						<div style="clear:both;padding:10px;">
							<?php 
							if($displayLocality && array_key_exists('imgs',$occArr)){
								$iArr = $occArr['imgs'];
								?>
								<fieldset style="padding:10px;">
									<legend><b>Specimen Images</b></legend>
									<?php 
									foreach($iArr as $imgId => $imgArr){
										?>
										<div style='float:left;text-align:center;padding:5px;'>
											<a href='<?php echo $imgArr['url']; ?>'>
												<img border=1 width='150' src='<?php echo ($imgArr['tnurl']?$imgArr['tnurl']:$imgArr['url']); ?>' title='<?php echo $imgArr['caption']; ?>'/>
											</a>
											<?php if($imgArr['lgurl']) echo '<br/><a href="'.$imgArr['lgurl'].'">Large Version</a>'; ?>
										</div>
										<?php 
									}
									?>
								</fieldset>
								<?php 
							}
							?>
						</div>
						<?php 
						if($collMetadata['individualurl']){
							$indUrl = '';
							if(strpos($collMetadata['individualurl'],'--DBPK--') && $occArr['dbpk']){
								$indUrl = str_replace('--DBPK--',$occArr['dbpk'],$collMetadata['individualurl']);
							}
							elseif(strpos($collMetadata['individualurl'],'--CATALOGNUMBER--') && $occArr['catalognumber']){
								$indUrl = str_replace('--CATALOGNUMBER--',$occArr['catalognumber'],$collMetadata['individualurl']);
							}
							if($indUrl){
								echo '<div style="margin-top:10px;clear:both;">';
								echo '<b>Source:</b> <a href="'.$indUrl.'">';
								echo $collMetadata['institutioncode'].' #'.($occArr['catalognumber']?$occArr['catalognumber']:$occArr['dbpk']);
								echo '</a></div>';
							}
						}
						$rightsStr = $collMetadata['rights'];
						if($collMetadata['rights']){
							$rightsHeading = '';
							if(isset($rightsTerms)) $rightsHeading = array_search($rightsStr,$rightsTerms);
							if(substr($collMetadata['rights'],0,4) == 'http'){
								$rightsStr = '<a href="'.$rightsStr.'">'.($rightsHeading?$rightsHeading:$rightsStr).'</a>';
							}
							$rightsStr = '<div style="margin-top:2px;"><b>Usage Rights:</b> '.$rightsStr.'</div>';
						}
						if($collMetadata['rightsholder']){
							$rightsStr .= '<div style="margin-top:2px;"><b>Rights Holder:</b> '.$collMetadata['rightsholder'].'</div>';
						}
						if($collMetadata['accessrights']){
							$rightsStr .= '<div style="margin-top:2px;"><b>Access Rights:</b> '.$collMetadata['accessrights'].'</div>';
						}
						?>
						<div style="margin:5px 0px 5px 0px;">
							<?php 
							if($rightsStr){
								echo $rightsStr;
							}
							else{
								echo '<a href="../../misc/usagepolicy.php">General Data Usage Policy</a>';
							}
							?>
						</div>
						
						<div style="margin-top:10px;clear:both;">
							For additional information on this specimen, please contact: 
							<?php 
							$emailSubject = $defaultTitle.' occurrence #'.$occArr['occid'];
							$emailBody = 'Specimen being referenced: http://'.$_SERVER['SERVER_NAME'].$clientRoot.'/collections/individual/index.php?occid='.$occArr['occid'];
							$emailRef = 'subject='.$emailSubject.'&cc='.$adminEmail.'&body='.$emailBody;
							?>
							<a href="mailto:<?php echo $collMetadata['email'].'?'.$emailRef; ?>">
								<?php echo $collMetadata['contact'].' ('.$collMetadata['email'].')'; ?>
							</a>
						</div>
						<?php 
						if($isEditor || ($displayLocality && $collMetadata['publicedits'])){
							?>
							<div style="margin-bottom:10px;">
								<?php 
								if($symbUid){
									?>
									Do you see an obvious error? If so, errors can fixed using the  
									<a href="../editor/occurrenceeditor.php?occid=<?php echo $occArr['occid'];?>">
										Occurrence Editor.
									</a>
									<?php
								}
								else{
									?>
									See an error? <a href="../../profile/index.php?refurl=../collections/individual/index.php?occid=<?php echo $occId; ?>">Login</a> to edit data
									<?php
								}
								?>
							</div>
							<?php
						}
						?>
					</div>
				</div>
		        <?php 
		        if($displayMap){
		        	?>
						<div id="maptab">
							<div id='map_canvas' style='width:100%;height:600px;'></div>
						</div>
		        	<?php 
		        }
		        ?>
				<div id="dupestab">
				
				</div>
				<div id="commenttab">
					<?php 
					if($commentArr){
						echo '<div><b>'.count($commentArr).' Comments</b></div>';
						echo '<hr style="color:gray;"/>';
						foreach($commentArr as $comId => $comArr){
							?>
							<div style="margin:15px;">
								<?php 
								echo '<div>';
								echo '<b>'.$comArr['username'].'</b> <span style="color:gray;">posted '.$comArr['initialtimestamp'].'</span>';
								echo '</div>';
								if($comArr['reviewstatus'] == 0) echo '<div style="color:red;">Comment not public (available to administrators only)</div>';
								echo '<div style="margin:10px 0px;">'.$comArr['comment'].'</div>';
								if($comArr['reviewstatus']){
									?>
									<div><a href="index.php?repcomid=<?php echo $comId.'&occid='.$occId; ?>">Report as unappropriate or abusive</a></div>
									<?php
								}
								else{
									?>
									<div><a href="index.php?publiccomid=<?php echo $comId.'&occid='.$occId; ?>">Make comment public</a></div>
									<?php
								}
								if($isEditor || $comArr['username'] == $paramsArr['un']){
									?>
									<div style="margin:20px;">
										<form name="delcommentform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to delete comment?')">
											<input name="occid" type="hidden" value="<?php echo $occId; ?>" />
											<input name="comid" type="hidden" value="<?php echo $comId; ?>" />
											<input name="tabindex" type="hidden" value="<?php echo ($displayMap?2:1); ?>" />
											<input name="formsubmit" type="submit" value="Delete Comment" /> 
										</form>
									</div>
									<?php 
								}
								?>
							</div>
							<hr style="color:gray;"/>
							<?php 
						}
					}
					else{
						echo '<div style="font-weight:bold;font-size:120%;margin:20px;">No comments have been submitted</div>';
					}
					?>
					<fieldset style="padding:20px;">
						<legend><b>New Comment</b></legend>
						<?php 
						if($symbUid){
							?>
							<form name="commentform" action="index.php" method="post" onsubmit="return verifyCommentForm(this);">
								<textarea name="commentstr" rows="8" style="width:98%;"></textarea>
								<div style="margin:15px;">
									<input name="occid" type="hidden" value="<?php echo $occId; ?>" />
									<input name="tabindex" type="hidden" value="<?php echo ($displayMap?2:1); ?>" />
									<input type="submit" name="formsubmit" value="Submit Comment" />
								</div>
								<div>
									Messages over 500 words long may be automatically truncated. All comments are moderated.
								</div>
							</form>
							<?php
						}
						else{
							?>
							<div style="margin:10px;">
								<a href="../../profile/index.php?refurl=../collections/individual/index.php?tabindex=2&occid=<?php echo $occId; ?>">Login</a> to leave a comment.
							</div>
							<?php
						}
						?>
					</fieldset>
				
				</div>
				<?php 
				if($editArr){
					?>
					<div id="edittab">
						<div style="padding:15px;">
							<?php 
							foreach($editArr as $k => $eArr){
								?>
								<div>
									<b>Editor:</b> <?php echo $eArr['editor']; ?>
									<span style="margin-left:30px;"><b>Date:</b> <?php echo $eArr['ts']; ?></span>
								</div>
								<?php 
								unset($eArr['editor']);
								unset($eArr['ts']);
								foreach($eArr as $vArr){
									echo '<div style="margin:15px;">';
									echo '<b>Field:</b> '.$vArr['fieldname'].'<br/>';
									echo '<b>Old Value:</b> '.$vArr['old'].'<br/>';
									echo '<b>New Value:</b> '.$vArr['new'].'<br/>';
									$reviewStr = 'OPEN';
									if($vArr['reviewstatus'] == 2){
										$reviewStr = 'PENDING';
									}
									elseif($vArr['reviewstatus'] == 3){
										$reviewStr = 'CLOSED';
									}
									echo 'Edit '.($vArr['appliedstatus']?'applied':'not applied').'; status '.$reviewStr;
									echo '</div>';
								}
								echo '<div style="margin:15px 0px;"><hr/></div>';
							}
							?>
						</div>
					</div>
					<?php 
				}
				?>
			</div>
			<?php 
        }
        else{
        	echo "<h2>There is a problem retrieving data.</h2><h3>Please try again later.</h3>";
        }
		?>
	</div>
	<?php
	//include($serverRoot."/footer.php");
	?>
</body>
</html> 

