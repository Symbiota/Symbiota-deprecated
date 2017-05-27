<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceIndividualManager.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverCore.php');
include_once($SERVER_ROOT.'/classes/RdfUtility.php');

$occid = array_key_exists("occid",$_REQUEST)?trim($_REQUEST["occid"]):0;
$collid = array_key_exists("collid",$_REQUEST)?trim($_REQUEST["collid"]):0;
$pk = array_key_exists("pk",$_REQUEST)?trim($_REQUEST["pk"]):"";
$guid = array_key_exists("guid",$_REQUEST)?trim($_REQUEST["guid"]):"";
$submit = array_key_exists('formsubmit',$_REQUEST)?trim($_REQUEST['formsubmit']):'';
$tabIndex = array_key_exists('tabindex',$_REQUEST)?$_REQUEST['tabindex']:0;
$clid = array_key_exists("clid",$_REQUEST)?trim($_REQUEST["clid"]):0;
$format = isset($_GET['format'])?$_GET['format']:'';

//Sanitize input variables
if(!is_numeric($occid)) $occid = 0;
if($guid && !preg_match('/[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}/', $guid)) $guid = ''; 
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($tabIndex)) $tabIndex = 0;
if(!is_numeric($clid)) $clid = 0;
if($pk && !preg_match('/^[a-zA-Z0-9\s_]+$/',$pk)) $pk = '';
if($submit && !preg_match('/^[a-zA-Z0-9\s_]+$/',$submit)) $submit = '';
if($format && !in_array($format,array('json','xml','rdf','turtle'))) $format = '';

$indManager = new OccurrenceIndividualManager();
if($occid){
	$indManager->setOccid($occid);
}
elseif($guid){
	$occid = $indManager->setGuid($guid);
}
elseif($collid && $pk){
	$indManager->setCollid($collid);
	$indManager->setDbpk($pk);
}

$occArr = $indManager->getOccData();
if(!$occid) $occid = $indManager->getOccid();
$collMetadata = $indManager->getMetadata();
if(!$collid) $collid = $occArr['collid'];

$genticArr = $indManager->getGeneticArr();

$statusStr = '';
$displayLocality = false;
$isEditor = false;

//  If other than HTML was requested, return just that content.
$done=FALSE;
$accept = RdfUtility::parseHTTPAcceptHeader($_SERVER['HTTP_ACCEPT']);
while (!$done && list($key, $mediarange) = each($accept)) {
    if ($mediarange=='text/turtle' || $format == 'turtle') {
       Header("Content-Type: text/turtle; charset=".$CHARSET);
       $dwcManager = new DwcArchiverCore();
       $dwcManager->setCustomWhereSql(" o.occid = $occid ");
       echo $dwcManager->getAsTurtle();
       $done = TRUE;
    }
    if ($mediarange=='application/rdf+xml' || $format == 'rdf') {
       Header("Content-Type: application/rdf+xml; charset=".$CHARSET);
       $dwcManager = new DwcArchiverCore();
       $dwcManager->setCustomWhereSql(" o.occid = $occid ");
       echo $dwcManager->getAsRdfXml();
       $done = TRUE;
    }
    if ($mediarange=='application/json' || $format == 'json') {
       Header("Content-Type: application/json; charset=".$CHARSET);
       $dwcManager = new DwcArchiverCore();
       $dwcManager->setCustomWhereSql(" o.occid = $occid ");
       echo $dwcManager->getAsJson();
       $done = TRUE;
    }

}
if ($done) {
  die;
}

if($SYMB_UID){
	//Check editing status
	if($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin']))){
		$isEditor = true;
	}
	elseif((array_key_exists('CollEditor',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollEditor']))){
		$isEditor = true;
	}
	elseif($occArr['observeruid'] == $SYMB_UID){
		$isEditor = true;
	}
	elseif($indManager->isTaxonomicEditor()){
		$isEditor = true;
	}
	
	//Check locality security
	if($isEditor || array_key_exists("RareSppAdmin",$USER_RIGHTS) || array_key_exists("RareSppReadAll",$USER_RIGHTS)){
		$displayLocality = true;
	}
	elseif(array_key_exists("RareSppReader",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["RareSppReader"])){
		$displayLocality = true;
	}
	elseif(array_key_exists('CollAdmin',$USER_RIGHTS) || array_key_exists('CollEditor',$USER_RIGHTS)){
		$displayLocality = true;
	}
	
	//Form action submitted
	if(array_key_exists('delvouch',$_GET) && $occid){
		if(!$indManager->deleteVoucher($occid,$_GET['delvouch'])){
			$statusStr = $indManager->getErrorMessage();
		}
	}
	if(array_key_exists('commentstr',$_POST)){
		if(!$indManager->addComment($_POST['commentstr'])){
			$statusStr = $indManager->getErrorMessage();
		}
	}
	elseif($submit == "Delete Comment"){
		if(!$indManager->deleteComment($_POST['comid'])){
			$statusStr = $indManager->getErrorMessage();
		}
	}
	elseif(array_key_exists('repcomid',$_GET)){
		if($indManager->reportComment($_GET['repcomid'])){
			$statusStr = 'Comment reported as inappropriate. Comment will remain unavailable to public until reviewed by an administrator.';
		}
		else{
			$statusStr = $indManager->getErrorMessage();
		}
	}
	elseif(array_key_exists('publiccomid',$_GET)){
		if(!$indManager->makeCommentPublic($_GET['publiccomid'])){
			$statusStr = $indManager->getErrorMessage();
		}
	}
	elseif($submit == "Add Voucher"){
		if(!$indManager->linkVoucher($_POST)){
			$statusStr = $indManager->getErrorMessage();
		}
	}
	elseif($submit == "Link to Dataset"){
		$dsid = (isset($_POST['dsid'])?$_POST['dsid']:0);
		if(!$indManager->linkToDataset($dsid,$_POST['dsname'],$_POST['notes'],$SYMB_UID)){
			$statusStr = $indManager->getErrorMessage();
		}
	}
}
if(!$occArr['localitysecurity']) $displayLocality = true;

$displayMap = false;
if($displayLocality && is_numeric($occArr['decimallatitude']) && is_numeric($occArr['decimallongitude'])) $displayMap = true;
$dupClusterArr = $indManager->getDuplicateArr();
$commentArr = $indManager->getCommentArr($isEditor);

header("Content-Type: text/html; charset=".$CHARSET);
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Detailed Collection Record Information</title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<meta name="description" content="<?php echo 'Occurrence author: '.$occArr['recordedby'].','.$occArr['recordnumber']; ?>" />
	<meta name="keywords" content="<?php echo $occArr['guid']; ?>">
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet">
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet">
	<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
	<script src="//maps.googleapis.com/maps/api/js?<?php echo (isset($GOOGLE_MAP_KEY) && $GOOGLE_MAP_KEY?'key='.$GOOGLE_MAP_KEY:''); ?>"></script>
	<script type="text/javascript">
		var tabIndex = <?php echo $tabIndex; ?>;
		var map;
		var mapInit = false;

		$(document).ready(function() {
			$('#tabs').tabs({ 
				beforeActivate: function(event, ui) {
					if(document.getElementById("map_canvas") && ui.newTab.index() == 1 && !mapInit){
						mapInit = true;
						initializeMap();
					}
					return true;
				},
				active: tabIndex 
			});

			$("#tabs").tabs().css({
				'min-height': '400px',
				'overflow': 'auto'
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

		function openIndividual(target) {
			occWindow=open("index.php?occid="+target,"occdisplay","resizable=1,scrollbars=1,toolbar=1,width=900,height=600,left=20,top=20");
			if (occWindow.opener == null) occWindow.opener = self;
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
	<div id="fb-root"></div>
	<script>
		(function(d, s, id) {
			var js, fjs = d.getElementsByTagName(s)[0];
			if (d.getElementById(id)) return;
			js = d.createElement(s); js.id = id;
			js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.0";
			fjs.parentNode.insertBefore(js, fjs);
		}(document, 'script', 'facebook-jssdk'));
	</script>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($statusStr){
			?>
			<hr />
			<div style="padding:15px;">
				<span style="color:red;"><?php echo $statusStr; ?></span>
			</div>
			<hr />
			<?php 
		}
		if($occArr){
			?>
			<div id="tabs" style="margin:10px;clear:both;">
				<ul>
					<li><a href="#occurtab"><span>Details</span></a></li>
					<?php 
					if($displayMap){
						?>
						<li><a href="#maptab"><span>Map</span></a></li>
						<?php 
					}
					if($genticArr) echo '<li><a href="#genetictab"><span>Genetic Data</span></a></li>'; 
					if($dupClusterArr){
						?>
						<li><a href="#dupestab"><span>Duplicates</span></a></li>
						<?php
					}
					?> 
					<li><a href="#commenttab"><span><?php echo ($commentArr?count($commentArr).' ':''); ?>Comments</span></a></li> 
					<li><a href="linkedresources.php?occid=<?php echo $occid.'&tid='.$occArr['tidinterpreted'].'&clid='.$clid.'&collid='.$collid; ?>"><span>Linked Resources</span></a></li>
					<?php 
					if($isEditor){
						?>
						<li><a href="#edittab"><span>Edit History</span></a></li> 
						<?php 
					}
					if (isset($fpEnabled) && $fpEnabled) { // FP Annotations tab
						$detVars = 'catalognumber='.urlencode($occArr['catalognumber']) . 						
						(isset($occArr['secondarycollcode'])?'&collectioncode='.urlencode($occArr['secondarycollcode']):'').
						(isset($collMap['collectioncode'])?'&collectioncode='.urlencode($collMap['collectioncode']):'').
						(isset($collMap['institutioncode'])?'&institutioncode='.urlencode($collMap['institutioncode']):'');
						echo '<li>';
						echo '<a href="../editor/includes/findannotations.php?'.$detVars.'"';
						echo ' style="margin: 0px 20px 0px 20px;"> Annotations </a>';
						echo '</li>';
					}
					?>
				</ul>
				<div id="occurtab">
					<div style="float:right;">
						<div style="float:right;">
							<a class="twitter-share-button" href="https://twitter.com/share" data-url="<?php echo $_SERVER['HTTP_HOST'].$clientRoot.'/collections/individual/index.php?occid='.$occid.'&clid='.$clid; ?>">Tweet</a>
							<script>
								window.twttr=(function(d,s,id){
									var js,fjs=d.getElementsByTagName(s)[0],t=window.twttr||{};
									if(d.getElementById(id))return;js=d.createElement(s);
									js.id=id;js.src="https://platform.twitter.com/widgets.js";
									fjs.parentNode.insertBefore(js,fjs);t._e=[];
									t.ready=function(f){t._e.push(f);};
									return t;
								}(document,"script","twitter-wjs"));
							</script>
						</div>
						<div style="float:right;margin-right:10px;">
							<div class="fb-share-button" data-href="" data-layout="button_count"></div>
						</div>
					</div>
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
							if($occArr['occurrenceid']){ 
								?>
								<div>
									<b>Occurrence ID (GUID):</b> 
									<?php
									$resolvableGuid = false;
									if(substr($occArr['occurrenceid'],0,4) == 'http') $resolvableGuid = true;
									if($resolvableGuid) echo '<a href="'.$occArr['occurrenceid'].'" target="_blank">';
									echo $occArr['occurrenceid'];
									if($resolvableGuid) echo '</a>';
									?>
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
							<?php 
							echo ($occArr['identificationqualifier']?$occArr['identificationqualifier']." ":""); 
							?>
							<i><?php echo $occArr['sciname']; ?></i> <?php echo $occArr['scientificnameauthorship']; ?>
							<?php 
							if($occArr['tidinterpreted']){
								//echo ' <a href="../../taxa/index.php?taxon='.$occArr['tidinterpreted'].'" title="Open Species Profile Page"><img src="" /></a>';
							}
							?>
							<br/>
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
							if($occArr['taxonremarks']){ 
								?>
								<div style="margin-left:10px;">
									<b>Taxon Remarks:</b>
									<?php echo $occArr['taxonremarks']; ?>
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
									<img src="../../images/plus_sm.png" style="border:0px;" />
									Show Determination History
								</div>
								<div class="detdiv" style="display:none;">
									<div style="margin-left:10px;cursor:pointer;" onclick="toggle('detdiv');">
										<img src="../../images/minus_sm.png" style="border:0px;" />
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
							if($displayLocality) echo $occArr['recordnumber'].'&nbsp;&nbsp;&nbsp;';
							?>
						</div>
						<?php
						if($displayLocality){
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
						$localityStr1 = '';
						if($occArr['country']) $localityStr1 .= $occArr['country'].', ';
						if($occArr['stateprovince']) $localityStr1 .= $occArr['stateprovince'].', ';
						if($occArr['county']) $localityStr1 .= $occArr['county'].', ';
						if($occArr['municipality']) $localityStr1 .= $occArr['municipality'].', ';
						?>
						<div>
							<b>Locality:</b>
							<?php 
							if($displayLocality){
								$localityStr1 .= $occArr['locality'];
							}
							else{
								$localityStr1 .= '<span style="color:red;">Detailed locality information protected.'; 
								if($occArr['localitysecurityreason']){
									$localityStr1 .= $occArr['localitysecurityreason'];
								}
								else{
									$localityStr1 .= 'This is typically done to protect rare or threatened species localities.';
								}
								$localityStr1 .= '</span>';
							}
							echo trim($localityStr1,',; ');
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
								<b>Disposition: </b>
								<?php echo $occArr['disposition']; ?>
							</div>
							<?php 
						}
						if(isset($occArr['exs'])){
							?>
							<div style="clear:both;">
								<b>Exsiccati series:</b> 
								<?php 
								echo '<a href="../exsiccati/index.php?omenid='.$occArr['exs']['omenid'].'">';
								echo $occArr['exs']['title'].'&nbsp;#'.$occArr['exs']['exsnumber'];
								echo '</a>';
								?>
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
											<a href='<?php echo $imgArr['url']; ?>' target="_blank">
												<img border=1 width='180' src='<?php echo ($imgArr['tnurl']?$imgArr['tnurl']:$imgArr['url']); ?>' title='<?php echo $imgArr['caption']; ?>'/>
											</a>
											<?php 
											if($imgArr['url'] != $imgArr['lgurl']) echo '<div><a href="'.$imgArr['url'].'" target="_blank">Open Medium Image</a></div>';
											if($imgArr['lgurl']) echo '<div><a href="'.$imgArr['lgurl'].'" target="_blank">Open Large Image</a></div>';
											?>
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
							if(strpos($collMetadata['individualurl'],'--DBPK--') !== false && $occArr['dbpk']){
								$indUrl = str_replace('--DBPK--',$occArr['dbpk'],$collMetadata['individualurl']);
							}
							elseif(strpos($collMetadata['individualurl'],'--CATALOGNUMBER--') !== false && $occArr['catalognumber']){
								$indUrl = str_replace('--CATALOGNUMBER--',$occArr['catalognumber'],$collMetadata['individualurl']);
							}
							elseif(strpos($collMetadata['individualurl'],'--OCCURRENCEID--') !== false && $occArr['occurrenceid']){
								$indUrl = str_replace('--OCCURRENCEID--',$occArr['occurrenceid'],$collMetadata['individualurl']);
							}
							if($indUrl){
								echo '<div style="margin-top:10px;clear:both;">';
								echo '<b>Link to Source:</b> <a href="'.$indUrl.'" target="_blank">';
								echo $collMetadata['institutioncode'].' #'.($occArr['catalognumber']?$occArr['catalognumber']:$occArr['dbpk']);
								echo '</a></div>';
							}
						}
						//Rights
						$rightsStr = $collMetadata['rights'];
						if($collMetadata['rights']){
							$rightsHeading = '';
							if(isset($rightsTerms)) $rightsHeading = array_search($rightsStr,$rightsTerms);
							if(substr($collMetadata['rights'],0,4) == 'http'){
								$rightsStr = '<a href="'.$rightsStr.'" target="_blank">'.($rightsHeading?$rightsHeading:$rightsStr).'</a>';
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
						<div style="margin:3px 0px;"><b>Record Id:</b> <?php echo $occArr['guid']; ?></div>
						
						<div style="margin-top:10px;clear:both;">
							For additional information on this specimen, please contact: 
							<?php 
							$emailSubject = $DEFAULT_TITLE.' occurrence: '.$occArr['catalognumber'].' ('.$occArr['othercatalognumbers'].')';
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
								if($SYMB_UID){
									?>
									Do you see an error? If so, errors can be fixed using the  
									<a href="../editor/occurrenceeditor.php?occid=<?php echo $occArr['occid'];?>">
										Occurrence Editor.
									</a>
									<?php
								}
								else{
									?>
									See an error? <a href="../../profile/index.php?refurl=../collections/individual/index.php?occid=<?php echo $occid; ?>">Login</a> to edit data
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
				if($genticArr){
					?>
					<div id="genetictab">
						<?php 
						foreach($genticArr as $genId => $gArr){
							?>
							<div style="margin:15px;">
								<div style="font-weight:bold;margin-bottom:5px;"><?php echo $gArr['name']; ?></div>
								<div style="margin-left:15px;"><b>Identifier:</b> <?php echo $gArr['id']; ?></div>
								<div style="margin-left:15px;"><b>Locus:</b> <?php echo $gArr['locus']; ?></div>
								<div style="margin-left:15px;">
									<b>URL:</b> 
									<a href="<?php echo $gArr['resourceurl']; ?>" target="_blank"><?php echo $gArr['resourceurl']; ?></a>
								</div>
								<div style="margin-left:15px;"><b>Notes:</b> <?php echo $gArr['notes']; ?></div>
							</div>
							<?php 
						}
						?>
					</div>
					<?php 
				}
				if($dupClusterArr){
					?>
					<div id="dupestab">
						<div style="margin:20px;">
							<div style="font-weight:bold;font-size:120%;margin-bottom:10px;"><u>Current Record</u></div>
							<?php
							echo '<div style="font-weight:bold;font-size:120%;">'.$collMetadata['collectionname'].' ('.$collMetadata['institutioncode'].($collMetadata['collectioncode']?':'.$collMetadata['collectioncode']:'').')</div>';
							echo '<div style="margin:5px 15px">';
							if($occArr['recordedby']) echo '<div>'.$occArr['recordedby'].' '.$occArr['recordnumber'].'<span style="margin-left:40px;">'.$occArr['eventdate'].'</span></div>';
							if($occArr['catalognumber']) echo '<div><b>Catalog Number:</b> '.$occArr['catalognumber'].'</div>';
							if($occArr['occurrenceid']) echo '<div><b>GUID:</b> '.$occArr['occurrenceid'].'</div>';
							if($occArr['sciname']) echo '<div><b>Latest Identification:</b> '.$occArr['sciname'].'</div>';
							if($occArr['identifiedby']) echo '<div><b>Identified by:</b> '.$occArr['identifiedby'].'<span stlye="margin-left:30px;">'.$occArr['dateidentified'].'</span></div>';
							echo '</div>';
							echo '<div style="margin:20px 0px;clear:both"><hr/><hr/></div>';
							//Grab other records
							foreach($dupClusterArr as $dupid => $dArr){
								$innerDupArr = $dArr['o'];
								foreach($innerDupArr as $dupOccid => $dupArr){
									if($dupOccid != $occid){
										echo '<div style="clear:both;margin:15px;">';
										echo '<div style="font-weight:bold;font-size:120%;">'.$dupArr['collname'].' ('.$dupArr['instcode'].($dupArr['collcode']?':'.$dupArr['collcode']:'').')</div>';
										echo '<div style="float:left;margin:5px 15px">';
										if($dupArr['recordedby']) echo '<div>'.$dupArr['recordedby'].' '.$dupArr['recordnumber'].'<span style="margin-left:40px;">'.$dupArr['eventdate'].'</span></div>';
										if($dupArr['catnum']) echo '<div><b>Catalog Number:</b> '.$dupArr['catnum'].'</div>';
										if($dupArr['occurrenceid']) echo '<div><b>GUID:</b> '.$dupArr['occurrenceid'].'</div>';
										if($dupArr['sciname']) echo '<div><b>Latest Identification:</b> '.$dupArr['sciname'].'</div>';
										if($dupArr['identifiedby']) echo '<div><b>Identified by:</b> '.$dupArr['identifiedby'].'<span stlye="margin-left:30px;">'.$dupArr['dateidentified'].'</span></div>';
										if($dupArr['notes']) echo '<div>'.$dupArr['notes'].'</div>';
										echo '<div><a href="#" onclick="openIndividual('.$dupOccid.')">Show Full Details</a></div>';
										echo '</div>';
										if($dupArr['url']){
											$url = $dupArr['url'];
											$tnUrl = $dupArr['tnurl'];
											if(!$tnUrl) $tnUrl = $url;
											if($IMAGE_DOMAIN){
												if(substr($url,0,1) == '/') $url = $IMAGE_DOMAIN.$url;
												if(substr($tnUrl,0,1) == '/') $tnUrl = $IMAGE_DOMAIN.$tnUrl;
											}
											echo '<div style="float:left;margin:10px;">';
											echo '<a href="'.$url.'">';
											echo '<img src="'.$tnUrl.'" style="width:100px;border:1px solid grey" />';
											echo '</a>';
											echo '</div>';
										}
										echo '<div style="margin:10px 0px;clear:both"><hr/></div>';
										echo '</div>';
									}
								}
							}
							?>
						</div>
					</div>
					<?php
				}
				?>
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
								if($comArr['reviewstatus'] == 0 || $comArr['reviewstatus'] == 2) echo '<div style="color:red;">Comment not public due to pending abuse report (viewable to administrators only)</div>';
								echo '<div style="margin:10px;">'.$comArr['comment'].'</div>';
								if($comArr['reviewstatus']){
									if($SYMB_UID){
										?>
										<div><a href="index.php?repcomid=<?php echo $comId.'&occid='.$occid.'&tabindex='.($displayMap?2:1); ?>">Report as inappropriate or abusive</a></div>
										<?php
									}
								}
								else{
									?>
									<div><a href="index.php?publiccomid=<?php echo $comId.'&occid='.$occid.'&tabindex='.($displayMap?2:1); ?>">Make comment public</a></div>
									<?php
								}
								if($isEditor || ($SYMB_UID && $comArr['username'] == $PARAMS_ARR['un'])){
									?>
									<div style="margin:20px;">
										<form name="delcommentform" action="index.php" method="post" onsubmit="return confirm('Are you sure you want to delete comment?')">
											<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
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
						if($SYMB_UID){
							?>
							<form name="commentform" action="index.php" method="post" onsubmit="return verifyCommentForm(this);">
								<textarea name="commentstr" rows="8" style="width:98%;"></textarea>
								<div style="margin:15px;">
									<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
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
								<a href="../../profile/index.php?refurl=../collections/individual/index.php?tabindex=2&occid=<?php echo $occid; ?>">Login</a> to leave a comment.
							</div>
							<?php
						}
						?>
					</fieldset>
				
				</div>
				<?php 
				if($isEditor){
					?>
					<div id="edittab">
						<div style="padding:15px;">
							<?php 
							if(array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collid,$USER_RIGHTS['CollAdmin']) && in_array($collid,$USER_RIGHTS['CollEditor'])){
								?>
								<div style="float:right;" title="Manage Edits">
									<a href="../editor/editreviewer.php?collid=<?php echo $collid.'&occid='.$occid; ?>"><img src="../../images/edit.png" style="border:0px;width:14px;" /></a>
								</div>
								<?php
							}
							echo '<div style="margin:20px 0px 30px 0px;">';
							echo '<b>Entered By:</b> '.($occArr['recordenteredby']?$occArr['recordenteredby']:'not recorded').'<br/>';
							echo '<b>Date entered:</b> '.($occArr['dateentered']?$occArr['dateentered']:'not recorded').'<br/>';
							echo '<b>Date modified:</b> '.($occArr['datelastmodified']?$occArr['datelastmodified']:'not recorded').'<br/>';
							if($occArr['modified'] && $occArr['modified'] != $occArr['datelastmodified']) echo '<b>Source date modified:</b> '.$occArr['modified'];
							echo '</div>';
							$editArr = $indManager->getEditArr();
							$externalEdits = $indManager->getExternalEditArr();
							if($editArr || $externalEdits){
								if($editArr){
									?>
									<fieldset style="padding:20px;">
										<legend><b>Internal Edits</b></legend>
										<?php 
										foreach($editArr as $k => $eArr){
											$reviewStr = 'OPEN';
											if($eArr['reviewstatus'] == 2) $reviewStr = 'PENDING';
											elseif($eArr['reviewstatus'] == 3) $reviewStr = 'CLOSED';
											?>
											<div>
												<b>Editor:</b> <?php echo $eArr['editor']; ?>
												<span style="margin-left:30px;"><b>Date:</b> <?php echo $eArr['ts']; ?></span>
											</div>
											<div>
												<span><b>Applied Status:</b> <?php echo ($eArr['appliedstatus']?'applied':'not applied'); ?></span>
												<span style="margin-left:30px;"><b>Review Status:</b> <?php echo $reviewStr; ?></span>
											</div>
											<?php
											$edArr = $eArr['edits'];
											foreach($edArr as $vArr){
												echo '<div style="margin:15px;">';
												echo '<b>Field:</b> '.$vArr['fieldname'].'<br/>';
												echo '<b>Old Value:</b> '.$vArr['old'].'<br/>';
												echo '<b>New Value:</b> '.$vArr['new'].'<br/>';
												echo '</div>';
											}
											echo '<div style="margin:15px 0px;"><hr/></div>';
										}
										?>
									</fieldset>
									<?php
								}
								if($externalEdits){
									?>
									<fieldset style="margin-top:20px;padding:20px;">
										<legend><b>External Edits</b></legend>
										<?php 
										foreach($externalEdits as $orid => $eArr){
											foreach($eArr as $appliedStatus => $eArr2){
												$reviewStr = 'OPEN';
												if($eArr2['reviewstatus'] == 2) $reviewStr = 'PENDING';
												elseif($eArr2['reviewstatus'] == 3) $reviewStr = 'CLOSED';
												?>
												<div>
													<b>Editor:</b> <?php echo $eArr2['editor']; ?>
													<span style="margin-left:30px;"><b>Date:</b> <?php echo $eArr2['ts']; ?></span>
													<span style="margin-left:30px;"><b>Source:</b> <?php echo $eArr2['source']; ?></span>
												</div>
												<div>
													<span><b>Applied Status:</b> <?php echo ($appliedStatus?'applied':'not applied'); ?></span>
													<span style="margin-left:30px;"><b>Review Status:</b> <?php echo $reviewStr; ?></span>
												</div>
												<?php
												$edArr = $eArr2['edits'];
												foreach($edArr as $fieldName => $vArr){
													echo '<div style="margin:15px;">';
													echo '<b>Field:</b> '.$fieldName.'<br/>';
													echo '<b>Old Value:</b> '.$vArr['old'].'<br/>';
													echo '<b>New Value:</b> '.$vArr['new'].'<br/>';
													echo '</div>';
												}
												echo '<div style="margin:15px 0px;"><hr/></div>';
											}
										}
										?>
									</fieldset>
									<?php
								}
							}
							else{
								echo '<div style="margin:25px 15px;"><b>Record has not been edited</b></div>';
							}
							echo '<div style="margin:15px">Note: Edits are only viewable by collection administrators and editors</div>';
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
			?>
			<h2>Unable to locate occurrence record</h2>
			<div style="margin:20px">
				<div>Checking archive...</div>
				<div style="margin:10px">
					<?php
					ob_flush();
					flush();
					$rawArchArr = $indManager->checkArchive();
					//print_r($rawArchArr);
					if($rawArchArr && $rawArchArr['obj']){
						$archArr = $rawArchArr['obj'];
						if(isset($archArr['dateDeleted'])) echo '<div><b>Record deleted:</b> '.$archArr['dateDeleted'].'</div>';
						if($rawArchArr['notes']) echo '<div style="margin-left:15px"><b>Notes: </b>'.$rawArchArr['notes'].'</div>';
						$dets = array();
						$imgs = array();
						if(isset($archArr['dets'])){
							$dets = $archArr['dets'];
							unset($archArr['dets']);
						}
						if(isset($archArr['imgs'])){
							$imgs = $archArr['imgs'];
							unset($archArr['imgs']);
						}
						echo '<table class="styledtable" style="font-family:Arial;font-size:12px;"><tr><th>Field</th><th>Value</th></tr>';
						foreach($archArr as $f => $v){
							echo '<tr><td style="width:175px;"><b>'.$f.'</b></td><td>';
							if(is_array($v)){
								echo implode(', ',$v);
							}
							else{
								echo $v;
							}
							echo '</td></tr>';
						}
						if($dets){
							foreach($dets as $id => $dArr){
								echo '<tr><td><b>Determination #'.$id.'</b></td><td>';
								foreach($dArr as $f => $v){
									echo '<b>'.$f.'</b>: '.$v.'<br/>';
								}
								echo '</td></tr>';
							}
						}
						if($imgs){
							foreach($imgs as $id => $iArr){
								echo '<tr><td><b>Image #'.$id.'</b></td><td>';
								foreach($iArr as $f => $v){
									echo '<b>'.$f.'</b>: '.$v.'<br/>';
								}
								echo '</td></tr>';
							}
						}
						echo '</table>';
					}
					else{
						echo 'Unable to located record within archive';
					}
					?>
				</div>
			</div>
			<?php 
		}
		?>
	</div>
</body>
</html>