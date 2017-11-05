<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecProcessorManager.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCrowdSource.php');
include_once($SERVER_ROOT.'/classes/SpecProcessorOcr.php');
include_once($SERVER_ROOT.'/classes/ImageProcessor.php');

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
			$statusStr .= ' records added to queue';
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
		<title>Specimen Processor Control Panel</title>
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
				echo "<a href='../../index.php'>Home</a> &gt;&gt; ";
				echo $collections_specprocessor_indexCrumbs;
				echo " <b>Specimen Processor Control Panel</b>";
				echo "</div>";
			}
		}
		else{
			echo '<div class="navpath">';
			echo '<a href="../../index.php">Home</a> &gt;&gt; ';
			echo '<a href="../misc/collprofiles.php?collid='.$collid.'&emode=1">Collection Control Panel</a> &gt;&gt; ';
			echo '<b>Specimen Processor Control Panel</b>';
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
				        <li><a href="#introdiv">Introduction</a></li>
				        <li><a href="imageprocessor.php?collid=<?php echo $collid.'&spprid='.$spprId.'&submitaction='.$action.'&filename='.$fileName; ?>">Image Loading</a></li>
				        <li><a href="crowdsource/controlpanel.php?collid=<?php echo $collid; ?>">Crowdsourcing</a></li>
				        <li><a href="ocrprocessor.php?collid=<?php echo $collid.'&procstatus='.$procStatus.'&spprid='.$spprId; ?>">OCR</a></li>
				        <!-- 
				        <li><a href="nlpprocessor.php?collid=<?php echo $collid.'&spnlpid='.$spNlpId; ?>">NLP</a></li>
				         -->
				        <li><a href="reports.php?<?php echo $_SERVER['QUERY_STRING']; ?>">Reports</a></li>
				        <li><a href="exporter.php?collid=<?php echo $collid.'&displaymode='.$displayMode; ?>">Exporter</a></li>
				        <?php 
				        if($ACTIVATE_GEOLOCATE_TOOLKIT){
					        ?>
					        <li><a href="geolocate.php?collid=<?php echo $collid; ?>">GeoLocate CoGe</a></li>
					        <?php 	
				        }
				        ?>
				    </ul>
					<div id="introdiv">
						<h1>Specimen Processor Control Panel</h1>
						<div style="margin:10px">
							This management module is designed to aid in establishing advanced processing workflows 
							for unprocessed specimens using images of the specimen label. The central functions addressed in this page are:
							Batch loading images, Optical Character Resolution (OCR), Natural Language Processing (NLP), 
							and crowdsourcing data entry. 
							Use tabs above for access tools.     
						</div>
						<div style="margin:10px;height:400px;">
							<h2>Image Loading</h2>
							<div style="margin:15px">
								The batch image loading module is designed to batch process specimen images that are deposited in a 
								drop folder. This module will produce web-ready images for a group of specimen images and 
								map the new image derivative to specimen records. Images can be linked to already existing 
								specimen records, or linked to a newly created skeletal specimen record for further digitization within the portal.
								Field data from skeletal data files (.csv, .tab, .dat) placed in the image folders will  
								augment new records by adding content to empty fields only. 
								The column names of skeletal files must match Symbiota field names (e.g. Darwin Core) with catalogNumber as a 
								required field. For more information, see the
								<b><a href="http://symbiota.org/docs/batch-loading-specimen-images-2/">Batch Image Loading</a></b> section 
								on the <b><a href="http://symbiota.org">Symbiota</a> website</b>.   
							</div>

							<h2>Crowdsourcing Module</h2>
							<div style="margin:15px">
								The crowdsourcing module can be used to make unprocessed records accessible for data entry by 
								general users who typically do not have explicit editing writes for a particular collection. 
								For more information, see the
								<b><a href="http://symbiota.org/docs/crowdsourcing-within-symbiota-2/">Crowdsource</a></b> section 
								on the <b><a href="http://symbiota.org">Symbiota</a> website</b>.   
							</div>

							<h2>Optical Character Resolution (OCR)</h2>
							<div style="margin:15px">
								The OCR module gives collection managers the ability to batch OCR specimen images using the Tesseract OCR 
								engine or process and upload text files containing OCR obtained from other OCR software.   
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
					Collection project has not been identified
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