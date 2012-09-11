<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/SpecProcessorManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$spprId = array_key_exists('spprid',$_REQUEST)?$_REQUEST['spprid']:0;
//NLP variables
$spNlpId = array_key_exists('spnlpid',$_REQUEST)?$_REQUEST['spnlpid']:0;

$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:0; 

$specManager;
if($action == 'Upload ABBYY File'){
	$specManager = new SpecProcessorAbbyy($logPath);
}
elseif($action == 'Process Images'){
	$specManager = new SpecProcessorImage($logPath);
}
else{
	$specManager = new SpecProcessorManager($logPath);
}

$specManager->setCollId($collId);
$specManager->setSpprId($spprId);

$editable = false;
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
 	$editable = true;
}

$status = "";
if($editable){
	if($action == 'Add New Project'){
		$specManager->addProject($_REQUEST);
	}
	elseif($action == 'Edit Project'){
		$specManager->editProject($_REQUEST);
	}
	elseif($action == 'Delete Project'){
		$specManager->deleteProject($_REQUEST['sppriddel']);
	}
}
$specProjects = Array();
if(!$spprId){
	$specProjects = $specManager->getProjects();
	if(count($specProjects) == 1){
		$spprId = array_shift(array_keys($specProjects));
		$specManager->setSpprId($spprId);
	}
}
if($spprId){
	$specManager->setProjVariables();
}

?>
<html>
	<head>
		<title>Specimen Processor Control Panel</title>
		<link rel="stylesheet" href="<?php echo $clientRoot; ?>/css/main.css" type="text/css" />
		<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />	
		<script type="text/javascript" src="../../js/jquery.js"></script>
		<script type="text/javascript" src="../../js/jquery-ui.js"></script>
		<script language=javascript>
			$(document).ready(function() {
				$('#tabs').tabs({
					selected: <?php echo $tabIndex; ?>,
					//spinner: 'Loading...',
					cache: false,
					ajaxOptions: {cache: false}
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
			echo '<a href="../misc/collprofileS.php?collid='.$collId.'emode=1">Collection Control Panel</a> &gt;&gt; ';
			echo '<b>Specimen Processor Control Panel</b>';
			echo '</div>';
		}
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<h1>Specimen Processor Control Panel</h1>
			<div style="clear:both;padding:15px;">
				<?php 
				if($status){ 
					?>
					<div style='margin:20px 0px 20px 0px;'>
						<hr/>
						<?php echo $status; ?>
						<hr/>
					</div>
					<?php 
				}
				if($action == 'Upload ABBYY File'){
					$statusArr = $specManager->loadLabelFile();
					if($statusArr){
						$status = '<ul><li>'.implode('</li><li>',$statusArr).'</li></ul>';
					}
				}
				elseif($action == 'Process Images'){
					echo '<h3>Batch Processing Images</h3>'."\n";
					echo '<ul>'."\n";
					$specManager->setCreateWebImg(array_key_exists('mapweb',$_REQUEST)?$_REQUEST['mapweb']:1);
					$specManager->setCreateTnImg(array_key_exists('maptn',$_REQUEST)?$_REQUEST['maptn']:1);
					$specManager->setCreateLgImg(array_key_exists('maplarge',$_REQUEST)?$_REQUEST['maplarge']:1);
					$specManager->setCreateNewRec($_REQUEST['createnewrec']);
					$specManager->setCopyOverImg($_REQUEST['copyoverimg']);
					if(isset($useImageMagick) && $useImageMagick) $specManager->setUseImageMagick(1);
					$specManager->batchLoadImages();
					echo '</ul>'."\n";
				}
				?>
			</div>
			<?php 
			if($symbUid && $collId){
				?>
				<div id="tabs" class="taxondisplaydiv">
				    <ul>
				        <li><a href="#introdiv">Introduction</a></li>
				        <li><a href="imageprocessor.php?collid=<?php echo $collId; ?>">Image Loading</a></li>
				        <li><a href="ocrprocessor.php?collid=<?php echo $collId.'&spprid='.$spprId; ?>">OCR</a></li>
				        <li><a href="nlpprocessor.php?collid=<?php echo $collId.'&spnlpid='.$spNlpId; ?>">NLP</a></li>
				    </ul>
					<div id="introdiv" style="height:400px;">
						<div style="margin:10px">
							This an management module designed to aid in establishing advanced processing workflows 
							for specimen images. The three central functions available within this module are
							1) Batch loading images, 2) Optical Character Resolution (OCR), and 3) Natural Language Processing (NLP). 
							Click on the tabs above to access these tools or read below for more information covering these procedures.    
						</div>
						<div style="margin:10px">
							<h2>Image Loading</h2>
							<div style="margin:15px">
								The batch image loading module will create web-ready images for a group of specimen images and 
								map the new image derivative within the database. 
								Note that in order to use this built-in module, the web server will need to have 
								writable access to the target folders. If images are to be stored on a separate server, than
								a folder mount can be established to given the web server access to storage. 
								Another option would be to trigger image processing scripts on the image server. These scripts 
								can map the image urls within the portal database remotely, or the image URLs can be written to a log 
								file and loaded to the database separately.
							</div>
							<h2>Optical Character Resolution (OCR)</h2>
							<div style="margin:15px">Description to be added </div>
							<h2>Natural Language Processing (NLP)</h2>
							<div style="margin:15px">Description to be added </div>
						</div>
					</div>
				</div>
				<?php 
			}
			else{
				?>
				<div style='font-weight:bold;'>
					<?php 
					if(!$symbUid){
						echo "Please <a href='../../profile/index.php?refurl=".$clientRoot."/collections/specprocessor/index.php'>login</a>!";
					} 
					else{
						echo 'Collection project has not been identified';						
					}
					?>
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
