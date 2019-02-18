<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecProcessorManager.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCrowdSource.php');
include_once($SERVER_ROOT.'/classes/SpecProcessorOcr.php');
include_once($SERVER_ROOT.'/classes/ImageProcessor.php');
include_once($SERVER_ROOT.'/content/lang/collections/specprocessor/index.'.$LANG_TAG.'.php');

header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/specprocessor/index.php?'.$_SERVER['QUERY_STRING']);

$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$spprId = array_key_exists('spprid',$_REQUEST)?$_REQUEST['spprid']:0;
//NLP and OCR variables
$spNlpId = array_key_exists('spnlpid',$_REQUEST)?$_REQUEST['spnlpid']:0;
$procStatus = array_key_exists('procstatus',$_REQUEST)?$_REQUEST['procstatus']:'unprocessed';
$displayMode = array_key_exists('displaymode',$_REQUEST)?$_REQUEST['displaymode']:0;
$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:0; 

//Sanitation
if($action && !preg_match('/^[a-zA-Z0-9\s_]+$/',$action)) $action = '';
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($spprId)) $spprId = 0;
if(!is_numeric($spNlpId)) $spNlpId = 0;
if($procStatus && !preg_match('/^[a-zA-Z]+$/',$procStatus)) $procStatus = '';
if(!is_numeric($displayMode)) $displayMode = 0;
if(!is_numeric($tabIndex)) $tabIndex = 0;


$specManager = new SpecProcessorManager();
$specManager->setCollId($collid);

$isEditor = false;
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
 	$isEditor = true;
}

$fileName = '';
$statusStr = '';
if($isEditor){
	if($action == 'Analyze Image Data File'){
		if($_POST['projecttype'] == 'file'){
			$imgProcessor = new ImageProcessor();
			$fileName = $imgProcessor->loadImageFile();
		}
	}
	elseif($action == 'Save Profile'){
		if($_POST['spprid']){
			$specManager->editProject($_POST);
		}
		else{
			$specManager->addProject($_POST);
		}
	}
	elseif($action == 'Delete Profile'){
		$specManager->deleteProject($_POST['sppriddel']);
	}
	elseif($action == 'Add to Queue'){
		$csManager = new OccurrenceCrowdSource();
		$csManager->setCollid($collid);
		$statusStr = $csManager->addToQueue($_POST['omcsid'],$_POST['family'],$_POST['taxon'],$_POST['country'],$_POST['stateprovince'],$_POST['limit']);
		if(is_numeric($statusStr)){
			$statusStr .= ' '.$LANG['RECORDS_ADDED_TO_QUEUE'];
		}
		$action = '';
	}
	elseif($action == 'delqueue'){
		$csManager = new OccurrenceCrowdSource();
		$csManager->setCollid($collid);
		$statusStr = $csManager->deleteQueue($_GET['omcsid']);
	}
	elseif($action == 'Edit Crowdsource Project'){
		$omcsid = $_POST['omcsid'];
		$csManager = new OccurrenceCrowdSource();
		$csManager->setCollid($collid);
		$statusStr = $csManager->editProject($omcsid,$_POST['instr'],$_POST['url']);
	}
}
?>
<html>
	<head>
		<title><?php echo $LANG['SPECIMEN_PROCESSOR_CONTROL_PANEL']; ?></title>
		<link href="<?php echo $CLIENT_ROOT; ?>/css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
		<link href="<?php echo $CLIENT_ROOT; ?>/css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $CLIENT_ROOT; ?>/css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<link href="../../js/jquery-ui-1.12.1/jquery-ui.css" type="text/css" rel="Stylesheet" />	
		<script src="../../js/jquery-3.2.1.min.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui-1.12.1/jquery-ui.js" type="text/javascript"></script>
		<script src="../../js/symb/shared.js?ver=131106" type="text/javascript"></script>
		<script>
			$(document).ready(function() {
				$('#tabs').tabs({
					select: function(event, ui) {
						return true;
					},
					active: <?php echo $tabIndex; ?>,
					beforeLoad: function( event, ui ) {
						$(ui.panel).html("<p>Loading...</p>");
					}
				});

			});
		</script>
	</head>
	<body>
		<?php
		$displayLeftMenu = false;
		include($serverRoot.'/header.php');
		if(isset($collections_specprocessor_indexCrumbs)){
			if($collections_specprocessor_indexCrumbs){
				echo "<div class='navpath'>";
				echo "<a href='../../index.php'>".$LANG['HOME']."</a> &gt;&gt; ";
				echo $collections_specprocessor_indexCrumbs;
				echo " <b>".$LANG['SPECIMEN_PROCESSOR_CONTROL_PANEL']."</b>";
				echo "</div>";
			}
		}
		else{
			echo '<div class="navpath">';
			echo '<a href="../../index.php">'.$LANG['HOME'].'</a> &gt;&gt; ';
			echo '<a href="../misc/collprofiles.php?collid='.$collid.'&emode=1">Collection Control Panel</a> &gt;&gt; ';
			echo '<b>'.$LANG['SPECIMEN_PROCESSOR_CONTROL_PANEL'].'</b>';
			echo '</div>';
		}
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<h2><?php echo $specManager->getCollectionName(); ?></h2>
			<?php
			if($statusStr){ 
				?>
				<div style='margin:20px 0px 20px 0px;'>
					<hr/>
					<div style="margin:15px;color:<?php echo (stripos($statusStr,'error') !== false?'red':'green'); ?>">
						<?php echo $statusStr; ?>
					</div>
					<hr/>
				</div>
				<?php 
			}
			if($collid){
				?>
				<div id="tabs" class="taxondisplaydiv">
				    <ul>
				        <li><a href="#introdiv"><?php echo $LANG['INTRO'];?></a></li>
				        <li><a href="imageprocessor.php?collid=<?php echo $collid.'&spprid='.$spprId.'&submitaction='.$action.'&filename='.$fileName; ?>"><?php echo $LANG['IMAGE'];?></a></li>
				        <li><a href="crowdsource/controlpanel.php?collid=<?php echo $collid; ?>"><?php echo $LANG['CROW'];?></a></li>
				        <li><a href="ocrprocessor.php?collid=<?php echo $collid.'&procstatus='.$procStatus.'&spprid='.$spprId; ?>">OCR</a></li>
				        <!-- 
				        <li><a href="nlpprocessor.php?collid=<?php echo $collid.'&spnlpid='.$spNlpId; ?>">NLP</a></li>
				         -->
				        <li><a href="reports.php?<?php echo $_SERVER['QUERY_STRING']; ?>"><?php echo $LANG['REPORTS'];?></a></li>
				        <li><a href="exporter.php?collid=<?php echo $collid.'&displaymode='.$displayMode; ?>"><?php echo $LANG['EXPORTER'];?></a></li>
				        <?php 
				        if($ACTIVATE_GEOLOCATE_TOOLKIT){
					        ?>
					        <li><a href="geolocate.php?collid=<?php echo $collid; ?>">GeoLocate CoGe</a></li>
					        <?php 	
				        }
				        ?>
				    </ul>
					<div id="introdiv">
						<h5><?php echo $LANG['SPECIMEN'];?></h5>
						<div style="margin:10px">
							<?php echo $LANG['THIS'];?>     
						</div>
						<div style="margin:10px;height:400px;">
							<h5><?php echo $LANG['IMAGE_1'];?></h5>
							<div style="margin:15px">
								<?php echo $LANG['THE_BATCH'];?>
								<b><a href="http://symbiota.org/docs/batch-loading-specimen-images-2/"><?php echo $LANG['BTACH_IMAGE'];?></a></b> <?php echo $LANG['SECTION'];?> <b><a href="http://symbiota.org">Symbiota</a> <?php echo $LANG['WEB'];?></b>.   
							</div>

							<h5><?php echo $LANG['CRO_MOD'];?></h5>
							<div style="margin:15px">
								<?php echo $LANG['THE_CROW'];?>
								<b><a href="http://symbiota.org/docs/crowdsourcing-within-symbiota-2/"><?php echo $LANG['CROWSOURCE'];?></a></b> <?php echo $LANG['SEC'];?> <b><a href="http://symbiota.org">Symbiota</a> <?php echo $LANG['WEBSITE'];?></b>.   
							</div>

							<h5><?php echo $LANG['OPTICAL'];?></h5>
							<div style="margin:15px">
								<?php echo $LANG['THE_OCR'];?>   
							</div>

							<!--  
							<h2>Natural Language Processing (NLP)</h2>
							<div style="margin:15px 0px 40px 15px">Description to be added </div>
							-->

						</div>
					</div>
				</div>
				<?php 
			}
			else{
				?>
				<div style='font-weight:bold;'>
					<?php echo $LANG['COLLECTION_PRO'];?> 
				</div>
				<?php
			}
			?>
		</div>
		<?php
			include($SERVER_ROOT.'/footer.php');
		?>
	</body>
</html>