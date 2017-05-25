<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/SpecProcessorManager.php');
include_once($SERVER_ROOT.'/classes/ImageLocalProcessor.php');
include_once($SERVER_ROOT.'/classes/ImageProcessor.php');
include_once($SERVER_ROOT.'/classes/SpecProcessorOcr.php');

header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/specprocessor/processor.php?'.$_SERVER['QUERY_STRING']);

$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
$collid = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$spprid = array_key_exists('spprid',$_REQUEST)?$_REQUEST['spprid']:0;
$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:0; 

//NLP and OCR variables
$spNlpId = array_key_exists('spnlpid',$_REQUEST)?$_REQUEST['spnlpid']:0;
$procStatus = array_key_exists('procstatus',$_REQUEST)?$_REQUEST['procstatus']:'unprocessed';

$specManager = new SpecProcessorManager();
$specManager->setCollId($collid);

$isEditor = false;
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
	$isEditor = true;
}

if($action == 'dlnoimg'){
	$specManager->downloadReportData($action);
	exit;
}
elseif($action == 'unprocnoimg'){
	$specManager->downloadReportData($action);
	exit;
}
elseif($action == 'noskel'){
	$specManager->downloadReportData($action);
	exit;
}

$statusStr = "";
?>
<html>
	<head>
		<title>Specimen Processor Control Panel</title>
		<link href="<?php echo $CLIENT_ROOT; ?>/css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $CLIENT_ROOT; ?>/css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	</head>
	<body>
		<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/header.php');
		echo '<div class="navpath">';
		echo '<a href="../../index.php">Home</a> &gt;&gt; ';
		echo '<a href="../misc/collprofiles.php?collid='.$collid.'&emode=1">Collection Control Panel</a> &gt;&gt; ';
		echo '<a href="index.php?collid='.$collid.'&tabindex='.$tabIndex.'"><b>Specimen Processor</b></a> &gt;&gt; ';
		echo '<b>Processing Handler</b>';
		echo '</div>';
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<h2><?php echo $specManager->getCollectionName(); ?></h2>
			<?php
			if($isEditor){
				$specManager->setProjVariables($spprid);
				if($action == 'Process Images'){
					if($specManager->getProjectType() == 'iplant'){
						$imageProcessor = new ImageProcessor($specManager->getConn());
						echo '<ul>';
						$imageProcessor->setLogMode(3);
						$imageProcessor->setCollid($collid);
						$imageProcessor->setSpprid($spprid);
						$imageProcessor->processIPlantImages($specManager->getSpecKeyPattern(), $_POST);
						echo '</ul>';
					}
					else{
						echo '<div style="padding:15px;">'."\n";
						$imageProcessor = new ImageLocalProcessor();
		
						$imageProcessor->setLogMode(3);
						$logPath = $SERVER_ROOT.(substr($SERVER_ROOT,-1) == '/'?'':'/').'content/logs/imgProccessing';
						if(!file_exists($logPath)) mkdir($logPath);
						$imageProcessor->setLogPath($logPath);
						$logFile = $collid.'_'.$specManager->getInstitutionCode();
						if($specManager->getCollectionCode()) $logFile .= '-'.$specManager->getCollectionCode();
						$imageProcessor->initProcessor($logFile);
						$imageProcessor->setCollArr(array($collid => array('pmterm' => $specManager->getSpecKeyPattern(),'prpatt' => $specManager->getPatternReplace(),'prrepl' => $specManager->getReplaceStr())));
						$imageProcessor->setMatchCatalogNumber((array_key_exists('matchcatalognumber', $_POST)?1:0));
						$imageProcessor->setMatchOtherCatalogNumbers((array_key_exists('matchothercatalognumbers', $_POST)?1:0));
						$imageProcessor->setDbMetadata(1);
						$imageProcessor->setSourcePathBase($specManager->getSourcePath());
						$imageProcessor->setTargetPathBase($specManager->getTargetPath());
						$imageProcessor->setImgUrlBase($specManager->getImgUrlBase());
						$imageProcessor->setServerRoot($serverRoot);
						if($specManager->getWebPixWidth()) $imageProcessor->setWebPixWidth($specManager->getWebPixWidth());
						if($specManager->getTnPixWidth()) $imageProcessor->setTnPixWidth($specManager->getTnPixWidth());
						if($specManager->getLgPixWidth()) $imageProcessor->setLgPixWidth($specManager->getLgPixWidth());
						if($specManager->getWebMaxFileSize()) $imageProcessor->setWebFileSizeLimit($specManager->getWebMaxFileSize());
						if($specManager->getLgMaxFileSize()) $imageProcessor->setLgFileSizeLimit($specManager->getLgMaxFileSize());
						if($specManager->getJpgQuality()) $imageProcessor->setJpgQuality($specManager->getJpgQuality());
						$imageProcessor->setUseImageMagick($specManager->getUseImageMagick());
						$imageProcessor->setWebImg($_POST['webimg']);
						$imageProcessor->setTnImg($_POST['createtnimg']);
						$imageProcessor->setLgImg($_POST['createlgimg']);
						$imageProcessor->setCreateNewRec($_POST['createnewrec']);
						$imageProcessor->setImgExists($_POST['imgexists']);
						$imageProcessor->setKeepOrig(0);
						$imageProcessor->setSkeletalFileProcessing($_POST['skeletalFileProcessing']);
						
						//Run process
						$imageProcessor->batchLoadImages();
						echo '</div>'."\n";
					}
				}
				elseif($action == 'Process Output File'){
					//Process iDigBio Image ingestion appliance ouput file 
					$imageProcessor = new ImageProcessor($specManager->getConn());
					echo '<ul>';
					$imageProcessor->setLogMode(3);
					$imageProcessor->setSpprid($spprid);
					$imageProcessor->setCollid($collid);
					$imageProcessor->processiDigBioOutput($specManager->getSpecKeyPattern(),$_POST);
					echo '</ul>';
					
				}
				elseif($action == 'Run Batch OCR'){
					$ocrManager = new SpecProcessorOcr();
					$ocrManager->setVerbose(2);
					$batchLimit = 100;
					if(array_key_exists('batchlimit',$_POST)) $batchLimit = $_POST['batchlimit'];
					echo '<ul>';
					$ocrManager->batchOcrUnprocessed($collid,$procStatus,$batchLimit,0);
					echo '</ul>';
				}
				elseif($action == 'Load OCR Files'){
					$specManager->addProject($_POST);
					$ocrManager = new SpecProcessorOcr();
					$ocrManager->setVerbose(2);
					echo '<ul>';
					$ocrManager->harvestOcrText($_POST);
					echo '</ul>';
				}
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
			}
			?>
			<div style="font-weight:bold;font-size:120%;"><a href="index.php?collid=<?php echo $collid.'&tabindex='.$tabIndex; ?>"><b>Return to Specimen Processor</b></a></div>
		</div>
		<?php
			include($SERVER_ROOT.'/footer.php');
		?>
	</body>
</html>