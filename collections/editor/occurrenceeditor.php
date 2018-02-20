<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorManager.php');
include_once($SERVER_ROOT.'/classes/ProfileManager.php');
header("Content-Type: text/html; charset=".$CHARSET);
header('Access-Control-Allow-Origin: http://www.catalogueoflife.org/col/webservice');

$occId = array_key_exists('occid',$_REQUEST)?$_REQUEST['occid']:0;
$tabTarget = array_key_exists('tabtarget',$_REQUEST)?$_REQUEST['tabtarget']:0;
$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$goToMode = array_key_exists('gotomode',$_REQUEST)?$_REQUEST['gotomode']:0;
$occIndex = array_key_exists('occindex',$_REQUEST)&&$_REQUEST['occindex']!=""?$_REQUEST['occindex']:false;
$ouid = array_key_exists('ouid',$_REQUEST)?$_REQUEST['ouid']:0;
$crowdSourceMode = array_key_exists('csmode',$_REQUEST)?$_REQUEST['csmode']:0;
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
if(!$action && array_key_exists('carryloc',$_REQUEST)){
	$goToMode = 2;
}

//Create Occurrence Manager
$occManager;
if(strpos($action,'Determination') || strpos($action,'Verification')){
	$occManager = new OccurrenceEditorDeterminations();
}
elseif(strpos($action,'Image')){
	$occManager = new OccurrenceEditorImages();
}
else{
	$occManager = new OccurrenceEditorManager();
}

if($crowdSourceMode){
	$occManager->setCrowdSourceMode(1);
}

$isEditor = 0;		//If not editor, edits will be submitted to omoccuredits table but not applied to omoccurrences
$displayQuery = 0;
$isGenObs = 0;
$collMap = Array();
$occArr = array();
$imgArr = array();
$specImgArr = array();
$fragArr = array();
$qryCnt = false;
$statusStr = '';
$navStr = '';

if($SYMB_UID){
	//Set variables
	$occManager->setSymbUid($SYMB_UID);
	$occManager->setOccId($occId);
	$occManager->setCollId($collId);
	$collMap = $occManager->getCollMap();
	if($occId && !$collId && !$crowdSourceMode) $collId = $collMap['collid'];

	if($collMap && $collMap['colltype']=='General Observations') $isGenObs = 1;

	//Bring in config variables
	if($isGenObs){
		if(file_exists('includes/config/occurVarGenObs'.$SYMB_UID.'.php')){
			//Specific to particular collection
			include('includes/config/occurVarGenObs'.$SYMB_UID.'.php');
		}
		elseif(file_exists('includes/config/occurVarGenObsDefault.php')){
			//Specific to Default values for portal
			include('includes/config/occurVarGenObsDefault.php');
		}
	}
	else{
		if($collId && file_exists('includes/config/occurVarColl'.$collId.'.php')){
			//Specific to particular collection
			include('includes/config/occurVarColl'.$collId.'.php');
		}
		elseif(file_exists('includes/config/occurVarDefault.php')){
			//Specific to Default values for portal
			include('includes/config/occurVarDefault.php');
		}
		if($crowdSourceMode && file_exists('includes/config/crowdSourceVar.php')){
			//Specific to Crowdsourcing
			include('includes/config/crowdSourceVar.php');
		}
	}
	if(isset($ACTIVATE_EXSICCATI) && $ACTIVATE_EXSICCATI) $occManager->setExsiccatiMode(true);

	if($IS_ADMIN || ($collId && array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"]))){
		$isEditor = 1;
	}
	else{
		if($isGenObs){
			if(!$occId && array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollEditor"])){
				//Approved General Observation editors can add records
				$isEditor = 2;
			}
			elseif($action){
				//Lets assume that Edits where submitted and they remain on same specimen, user is still approved
				 $isEditor = 2;
			}
			elseif($occManager->getObserverUid() == $SYMB_UID){
				//Users can edit their own records
				$isEditor = 2;
			}
		}
		elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollEditor"])){
			$isEditor = 2;
		}
		elseif(array_key_exists("CollTaxon",$USER_RIGHTS) && $occId){
			//Check to see if this user is authorized to edit this occurrence given their taxonomic editing authority
			//0 = not editor, 2 = full editor, 3 = taxon editor, but not for this specific occurrence
			$isEditor = $occManager->isTaxonomicEditor();
		}
	}
	if($action == "Save Edits"){
		$statusStr = $occManager->editOccurrence($_POST,($crowdSourceMode?1:$isEditor));
	}
	if($isEditor == 1 || $isEditor == 2 || $crowdSourceMode){
		if($action == 'Save OCR'){
			$statusStr = $occManager->insertTextFragment($_POST['imgid'],$_POST['rawtext'],$_POST['rawnotes'],$_POST['rawsource']);
			if(is_numeric($statusStr)){
				$newPrlid = $statusStr;
				$statusStr = '';
			}
		}
		elseif($action == 'Save OCR Edits'){
			$statusStr = $occManager->saveTextFragment($_POST['editprlid'],$_POST['rawtext'],$_POST['rawnotes'],$_POST['rawsource']);
		}
		elseif($action == 'Delete OCR'){
			$statusStr = $occManager->deleteTextFragment($_POST['delprlid']);
		}
	}
	if($isEditor){
		//Available to full editors and taxon editors
		if($action == "Submit Determination"){
			//Adding a new determination
			$statusStr = $occManager->addDetermination($_POST,$isEditor);
			$tabTarget = 1;
		}
		elseif($action == "Submit Determination Edits"){
			$statusStr = $occManager->editDetermination($_POST);
			$tabTarget = 1;
		}
		elseif($action == "Delete Determination"){
			$statusStr = $occManager->deleteDetermination($_POST['detid']);
			$tabTarget = 1;
		}
		//Only full editors can perform following actions
		if($isEditor == 1 || $isEditor == 2){
			if($action == 'Add Record'){
				if($occManager->addOccurrence($_POST)){
					$occManager->setQueryVariables();
					$qryCnt = $occManager->getQueryRecordCount();
					$qryCnt++;
					if($goToMode){
						//Go to new record
						$occIndex = $qryCnt;
					}
					else{
						//Stay on record and get $occId
						$occId = $occManager->getOccId();
					}
				}
				else{
					$statusStr = $occManager->getErrorStr();
				}
			}
			elseif($action == 'Delete Occurrence'){
				if($occManager->deleteOccurrence($occId)){
					$occId = 0;
					$occManager->setOccId(0);
				}
				else{
					$statusStr = $occManager->getErrorStr();
				}
			}
			elseif($action == 'Transfer Record'){
				$transferCollid = $_POST['transfercollid'];
				if($transferCollid){
					if($occManager->transferOccurrence($occId,$transferCollid)){
						if(!isset($_POST['remainoncoll']) || !$_POST['remainoncoll']){
							$occManager->setCollId($transferCollid);
							$collId = $transferCollid;
							$collMap = $occManager->getCollMap();
						}
					}
					else{
						$statusStr = $occManager->getErrorStr();
					}
				}
			}
			elseif($action == "Submit Image Edits"){
				$statusStr = $occManager->editImage($_POST);
				$tabTarget = 2;
			}
			elseif($action == "Submit New Image"){
				if($occManager->addImage($_POST)){
					$statusStr = 'Image added successfully';
					$tabTarget = 2;
				}
				if($occManager->getErrorStr()){
					$statusStr .= $occManager->getErrorStr();
				}
			}
			elseif($action == "Delete Image"){
				$removeImg = (array_key_exists("removeimg",$_POST)?$_POST["removeimg"]:0);
				if($occManager->deleteImage($_POST["imgid"], $removeImg)){
					$statusStr = 'Image deleted successfully';
					$tabTarget = 2;
				}
				else{
					$statusStr = $occManager->getErrorStr();
				}
			}
			elseif($action == "Remap Image"){
				if($occManager->remapImage($_POST["imgid"], $_POST["targetoccid"])){
					$statusStr = 'SUCCESS: Image remapped to record <a href="occurrenceeditor.php?occid='.$_POST["targetoccid"].'" target="_blank">'.$_POST["targetoccid"].'</a>';
				}
				else{
					$statusStr = 'ERROR linking image to new specimen: '.$occManager->getErrorStr();
				}
			}
			elseif($action == "Disassociate Image"){
				if($occManager->remapImage($_POST["imgid"])){
					$statusStr = 'SUCCESS disassociating image <a href="../../imagelib/imgdetails.php?imgid='.$_POST["imgid"].'" target="_blank">#'.$_POST["imgid"].'</a>';
				}
				else{
					$statusStr = 'ERROR disassociating image: '.$occManager->getErrorStr();
				}

			}
			elseif($action == "Apply Determination"){
				$makeCurrent = 0;
				if(array_key_exists('makecurrent',$_POST)) $makeCurrent = 1;
				$statusStr = $occManager->applyDetermination($_POST['detid'],$makeCurrent);
				$tabTarget = 1;
			}
			elseif($action == "Make Determination Current"){
				$statusStr = $occManager->makeDeterminationCurrent($_POST['detid']);
				$tabTarget = 1;
			}
			elseif($action == "Submit Verification Edits"){
				$statusStr = $occManager->editIdentificationRanking($_POST['confidenceranking'],$_POST['notes']);
				$tabTarget = 1;
			}
			elseif($action == 'Link to Checklist as Voucher'){
				$statusStr = $occManager->linkChecklistVoucher($_POST['clidvoucher'],$_POST['tidvoucher']);
			}
			elseif($action == 'deletevoucher'){
				$statusStr = $occManager->deleteChecklistVoucher($_REQUEST['delclid']);
			}
			elseif($action == 'editgeneticsubmit'){
				$statusStr = $occManager->editGeneticResource($_POST);
			}
			elseif($action == 'deletegeneticsubmit'){
				$statusStr = $occManager->deleteGeneticResource($_POST['genid']);
			}
			elseif($action == 'addgeneticsubmit'){
				$statusStr = $occManager->addGeneticResource($_POST);
			}
		}
	}

	if($goToMode){
		$occId = 0;
		//Adding new record, override query form and prime for current user's dataentry for the day
		$today = date('Y-m-d');
		$occManager->setQueryVariables(array('eb'=>$paramsArr['un'],'dm'=>$today));
		if(!$qryCnt){
			$occManager->setSqlWhere(0);
			$qryCnt = $occManager->getQueryRecordCount();
			$occIndex = $qryCnt;
		}
	}
	if($ouid){
		$occManager->setQueryVariables(array('ouid' => $ouid));
	}
	elseif($occIndex !== false){
		//Query Form has been activated
		$occManager->setQueryVariables();
		if($action == 'Delete Occurrence'){
			//Reset query form index to one less, unless it's already 1, then just reset
			$qryCnt = $occManager->getQueryRecordCount();		//Value won't be returned unless set in cookies in previous query
			if($qryCnt > 1){
				if(($occIndex + 1) >= $qryCnt) $occIndex = $qryCnt - 2;
				$qryCnt--;
				$occManager->setSqlWhere($occIndex);
			}
			else{
				unset($_SESSION['editorquery']);
				$occIndex = false;
			}
		}
		elseif($action == 'Save Edits'){
			$occManager->setSqlWhere(0);
			//Get query count and then reset; don't use new count for this display
			$qryCnt = $occManager->getQueryRecordCount();
			$occManager->getQueryRecordCount(1);
		}
		else{
			$occManager->setSqlWhere($occIndex);
			$qryCnt = $occManager->getQueryRecordCount();
		}
	}
	elseif(isset($_SESSION['editorquery'])){
		//Make sure query variables are null
		unset($_SESSION['editorquery']);
	}

	if(!$goToMode){
		$oArr = $occManager->getOccurMap();
		if($oArr){
			if(!$occId) $occId = $occManager->getOccId();
			$occArr = $oArr[$occId];
			if(!$collMap) $collMap = $occManager->getCollMap();
		}
	}
	elseif($goToMode == 2){
		$occArr = $occManager->carryOverValues($_REQUEST);
	}

	if($qryCnt !== false){
		if($qryCnt == 0){
			if(!$goToMode){
				$navStr .= '<div style="margin:20px;font-size:150%;font-weight:bold;">';
				$navStr .= 'Search returned 0 records</div>'."\n";
			}
		}
		else{
			$navStr = '<b>';
			if($occIndex > 0) $navStr .= '<a href="#" onclick="return submitQueryForm(0);" title="First Record">';
			$navStr .= '|&lt;';
			if($occIndex > 0) $navStr .= '</a>';
			$navStr .= '&nbsp;&nbsp;&nbsp;&nbsp;';
			if($occIndex > 0) $navStr .= '<a href="#" onclick="return submitQueryForm('.($occIndex-1).');" title="Previous Record">';
			$navStr .= '&lt;&lt;';
			if($occIndex > 0) $navStr .= '</a>';
			$recIndex = ($occIndex<$qryCnt?($occIndex + 1):'*');
			$navStr .= '&nbsp;&nbsp;| '.$recIndex.' of '.$qryCnt.' |&nbsp;&nbsp;';
			if($occIndex<$qryCnt-1) $navStr .= '<a href="#" onclick="return submitQueryForm('.($occIndex+1).');"  title="Next Record">';
			$navStr .= '&gt;&gt;';
			if($occIndex<$qryCnt-1) $navStr .= '</a>';
			$navStr .= '&nbsp;&nbsp;&nbsp;&nbsp;';
			if($occIndex<$qryCnt-1) $navStr .= '<a href="#" onclick="return submitQueryForm('.($qryCnt-1).');" title="Last Record">';
			$navStr .= '&gt;|';
			if($occIndex<$qryCnt-1) $navStr .= '</a> ';
			if(!$crowdSourceMode){
				$navStr .= '&nbsp;&nbsp;&nbsp;&nbsp;';
				$navStr .= '<a href="occurrenceeditor.php?gotomode=1&collid='.$collId.'" onclick="return verifyLeaveForm()" title="New Record">&gt;*</a>';
			}
			$navStr .= '</b>';
		}
	}

	//Images and other things needed for OCR
	$specImgArr = $occManager->getImageMap();
	if($specImgArr){
		$imgUrlPrefix = (isset($imageDomain)?$imageDomain:'');
		$imgCnt = 1;
		foreach($specImgArr as $imgId => $i2){
			$iUrl = $i2['url'];
			if($imgUrlPrefix && substr($iUrl,0,4) != 'http') $iUrl = $imgUrlPrefix.$iUrl;
			$imgArr[$imgCnt]['imgid'] = $imgId;
			$imgArr[$imgCnt]['web'] = $iUrl;
			if($i2['origurl']){
				$lgUrl = $i2['origurl'];
				if($imgUrlPrefix && substr($lgUrl,0,4) != 'http') $lgUrl = $imgUrlPrefix.$lgUrl;
				$imgArr[$imgCnt]['lg'] = $lgUrl;
			}
			if(isset($i2['error'])) $imgArr[$imgCnt]['error'] = $i2['error'];
			$imgCnt++;
		}
		$fragArr = $occManager->getRawTextFragments();
	}

	$isLocked = false;
	if($occId) $isLocked = $occManager->getLock();

}
else{
	header('Location: ../../profile/index.php?refurl=../collections/editor/occurrenceeditor.php?'.$_SERVER['QUERY_STRING']);
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE; ?> Occurrence Editor</title>
	<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
    <?php
    if($crowdSourceMode == 1){
		?>
		<link href="includes/config/occureditorcrowdsource.css?ver=1802" type="text/css" rel="stylesheet" id="editorCssLink" />
		<?php
    }
    else{
		?>
		<link href="../../css/occureditor.css?ver=170601" type="text/css" rel="stylesheet" id="editorCssLink" />
		<?php
		if(isset($CSSARR)){
			foreach($CSSARR as $cssVal){
				echo '<link href="includes/config/'.$cssVal.'?ver=170601" type="text/css" rel="stylesheet" id="editorCssLink" />';
			}
		}
		if(isset($JSARR)){
			foreach($JSARR as $jsVal){
				echo '<script src="includes/config/'.$jsVal.'?ver=170601" type="text/javascript"></script>';
			}
		}
	}
    ?>
	<script src="../../js/jquery.js?ver=140310" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js?ver=140310" type="text/javascript"></script>
	<script src="../../js/jquery.imagetool-1.7.js?ver=140310" type="text/javascript"></script>
	<script type="text/javascript">
		var collId = "<?php echo $collId; ?>";
		var csMode = "<?php echo $crowdSourceMode; ?>";
		var tabTarget = <?php echo (is_numeric($tabTarget)?$tabTarget:'0'); ?>;
		var imgArr = [];
		var imgLgArr = [];
		var localityAutoLookup = 1;
		<?php
		if($imgArr){
			foreach($imgArr as $iCnt => $iArr){
				echo 'imgArr['.$iCnt.'] = "'.$iArr['web'].'";'."\n";
				if(isset($iArr['lg'])) echo 'imgLgArr['.$iCnt.'] = "'.$iArr['lg'].'";'."\n";
			}
		}
		if(defined('LOCALITYAUTOLOOKUP') && !LOCALITYAUTOLOOKUP){
			echo 'localityAutoLookup = 0';
		}
		?>

		function requestImage(){
            $.ajax({
                type: "POST",
                url: 'rpc/makeactionrequest.php',
                data: { <?php echo " occid: '$occId' , "; ?> requesttype: 'Image' },
                success: function( response ) {
                   $('div#imagerequestresult').html(response);
                }
            });
        }

	</script>
	<script type="text/javascript" src="../../js/symb/collections.coordinateValidation.js?ver=170310"></script>
	<script type="text/javascript" src="../../js/symb/wktpolygontools.js?ver=180208"></script>
	<script type="text/javascript" src="../../js/symb/collections.occureditormain.js?ver=20180203"></script>
	<script type="text/javascript" src="../../js/symb/collections.occureditortools.js?ver=170204"></script>
	<script type="text/javascript" src="../../js/symb/collections.occureditorimgtools.js?ver=170310"></script>
	<script type="text/javascript" src="../../js/symb/collections.occureditorshare.js?ver=170302"></script>
</head>
<body>
	<!-- inner text -->
	<div id="innertext">
		<?php
		if($collMap){
			?>
			<div id="titleDiv">
				<?php
				echo $collMap['collectionname'].' ('.$collMap['institutioncode'].($collMap['collectioncode']?':'.$collMap['collectioncode']:'').')';
				if($isEditor == 1 || $isEditor == 2 || $crowdSourceMode){
					?>
					<div id="querySymbolDiv">
						<a href="#" title="Search / Filter" onclick="toggleQueryForm();"><img src="../../images/find.png" style="width:16px;" /></a>
					</div>
					<?php
				}
				?>
			</div>
			<?php
		}
		if($occId || $crowdSourceMode || ($isEditor && $collId)){
			if(!$occArr && !$goToMode) $displayQuery = 1;
			include 'includes/queryform.php';
			?>
			<div id="navDiv">
				<?php
				if($navStr){
					?>
					<div style="float:right;">
						<?php echo $navStr; ?>
					</div>
					<?php
				}
				if(isset($collections_editor_occurrenceeditorCrumbs)){
					if($collections_editor_occurrenceeditorCrumbs){
						?>
						<div class="navpath">
							<a href='../../index.php'>Home</a> &gt;&gt;
							<?php echo $collections_editor_occurrenceeditorCrumbs; ?>
							<b>Editor</b>
						</div>
						<?php
					}
				}
				else{
					?>
					<div class='navpath'>
						<a href="../../index.php" onclick="return verifyLeaveForm()">Home</a> &gt;&gt;
						<?php
						if($crowdSourceMode){
							?>
							<a href="../specprocessor/crowdsource/index.php">Crowd Sourcing Central</a> &gt;&gt;
							<?php
						}
						else{
							if($isGenObs){
								?>
								<a href="../../profile/viewprofile.php?tabindex=1" onclick="return verifyLeaveForm()">Personal Management</a> &gt;&gt;
								<?php
							}
							else{
								if($isEditor == 1 || $isEditor == 2){
									?>
									<a href="../misc/collprofiles.php?collid=<?php echo $collId; ?>&emode=1" onclick="return verifyLeaveForm()">Collection Management</a> &gt;&gt;
									<?php
								}
							}
						}
						if($occId) echo '<a href="../individual/index.php?occid='.$occId.'">Public Display</a> &gt;&gt;';
						?>
						<b><?php if($isEditor == 3) echo 'Taxonomic '; ?>Editor</b>
					</div>
					<?php
				}
				?>
			</div>
			<?php
			if($statusStr){
				?>
				<div id="statusdiv" style="margin:5px 0px 5px 15px;">
					<b>Action Status: </b>
					<span style="color:<?php echo (stripos($statusStr,'ERROR')!==false?'red':'green'); ?>;"><?php echo $statusStr; ?></span>
					<?php
					if($action == 'Delete Occurrence'){
						?>
						<br/>
						<a href="#" style="margin:5px;" onclick="window.opener.location.href = window.opener.location.href;window.close();">
							Return to Search Page
						</a>
						<?php
					}
					?>
				</div>
				<?php
			}
			if($occArr || $goToMode == 1 || $goToMode == 2){		//$action == 'gotonew'
				if($occId && $isLocked){
					?>
					<div style="margin:25px;border:2px double;padding:20px;width:90%;">
						<div style="color:red;font-weight:bold;font-size:110%;">
							Record Locked!
						</div>
						<div>
							This record is locked for editing by another user. Once the user is done with the record, the lock will be removed. Records are locked for a maximum of 15 minutes.
						</div>
						<div style="margin:20px;font-weight:bold;">
							<a href="../individual/index.php?occid=<?php echo $occId; ?>" target="_blank">Read-only Display</a>
						</div>
					</div>
					<?php
				}
				else{
					?>
					<table id="edittable" style="">
						<tr><td id="editortd" style="" valign="top">
							<div id="occedittabs" style="clear:both;">
								<ul>
									<li>
										<a href="#occdiv"  style="">
											<?php
											if($occId){
												echo 'Occurrence Data';
											}
											else{
												echo '<span style="color:red;">New Occurrence Record</span>';
											}
											?>
										</a>
									</li>
									<?php
									if($occId && $isEditor){
										// Get symbiota user email as the annotator email (for fp)
										$pHandler = new ProfileManager();
										$pHandler->setUid($SYMB_UID);
										$person = $pHandler->getPerson();
										$userEmail = ($person?$person->getEmail():'');

										$anchorVars = 'occid='.$occId.'&occindex='.$occIndex.'&csmode='.$crowdSourceMode.'&collid='.$collId;
										$detVars = 'identby='.urlencode($occArr['identifiedby']).'&dateident='.urlencode($occArr['dateidentified']).
											'&sciname='.urlencode($occArr['sciname']).'&em='.$isEditor.
											'&annotatorname='.urlencode($userDisplayName).'&annotatoremail='.urlencode($userEmail).
											(isset($collMap['collectioncode'])?'&collectioncode='.urlencode($collMap['collectioncode']):'').
											(isset($collMap['institutioncode'])?'&institutioncode='.urlencode($collMap['institutioncode']):'').
											'&catalognumber='.urlencode($occArr['catalognumber']);
										?>
										<li id="detTab">
											<a href="includes/determinationtab.php?<?php echo $anchorVars.'&'.$detVars; ?>"
												style="">Determination History</a>
										</li>
										<?php
										if (isset($fpEnabled) && $fpEnabled) { // FP Annotations tab
											echo '<li>';
											echo '<a href="includes/findannotations.php?'.$anchorVars.'&'.$detVars.'"';
											echo ' style=""> Annotations </a>';
											echo '</li>';
										}
										if($isEditor == 1 || $isEditor == 2){
											?>
											<li id="imgTab">
												<a href="includes/imagetab.php?<?php echo $anchorVars; ?>"
													style="">Images</a>
											</li>
											<li id="resourceTab">
												<a href="includes/resourcetab.php?<?php echo $anchorVars; ?>"
													style="">Linked Resources</a>
											</li>
											<li id="adminTab">
												<a href="includes/admintab.php?<?php echo $anchorVars; ?>"
													style="">Admin</a>
											</li>
											<?php
										}
									}
									?>
								</ul>
								<div id="occdiv">
									<form id="fullform" name="fullform" action="occurrenceeditor.php" method="post" onsubmit="return verifyFullForm(this);">
										<fieldset>
											<legend><b>Collector Info</b></legend>
											<?php
											if($occId){
												if($fragArr || $specImgArr){
													?>
													<div style="float:right;margin:-7px -4px 0px 0px;font-weight:bold;">
														<span id="imgProcOnSpan" style="display:block;">
															<a href="#" onclick="toggleImageTdOn();return false;">&gt;&gt;</a>
														</span>
														<span id="imgProcOffSpan" style="display:none;">
															<a href="#" onclick="toggleImageTdOff();return false;">&lt;&lt;</a>
														</span>
													</div>
													<?php
												}
												if($crowdSourceMode){
													?>
													<div style="float:right;margin:-7px 10px 0px 0px;font-weight:bold;">
														<span id="longtagspan" style="cursor:pointer;" onclick="toggleCsMode(0);return false;">Long Form</span>
														<span id="shorttagspan" style="cursor:pointer;display:none;" onclick="toggleCsMode(1);return false;">Short Form</span>
													</div>
													<?php
												}
											}
											?>
											<div style="clear:both;">
												<div id="catalogNumberDiv">
													<?php echo (defined('CATALOGNUMBERLABEL')?CATALOGNUMBERLABEL:'Catalog Number'); ?>
													<a href="#" onclick="return dwcDoc('catalogNumber')"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" id="catalognumber" name="catalognumber" tabindex="2" maxlength="32" value="<?php echo array_key_exists('catalognumber',$occArr)?$occArr['catalognumber']:''; ?>" onchange="fieldChanged('catalognumber');<?php if(!defined('CATNUMDUPECHECK') || CATNUMDUPECHECK) echo 'searchDupesCatalogNumber(this.form,true)'; ?>" <?php if(!$isEditor || $isEditor == 3) echo 'disabled'; ?> />
												</div>
												<div id="otherCatalogNumbersDiv">
													<?php echo (defined('OTHERCATALOGNUMBERSLABEL')?OTHERCATALOGNUMBERSLABEL:'Other Cat. #s'); ?>
													<a href="#" onclick="return dwcDoc('otherCatalogNumbers')"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" name="othercatalognumbers" tabindex="4" maxlength="255" value="<?php echo array_key_exists('othercatalognumbers',$occArr)?$occArr['othercatalognumbers']:''; ?>" onchange="fieldChanged('othercatalognumbers');<?php if(!defined('OTHERCATNUMDUPECHECK') || OTHERCATNUMDUPECHECK) echo 'searchDupesOtherCatalogNumbers(this.form)'; ?>" />
												</div>
												<div id="recordedByDiv">
													<?php echo (defined('RECORDEDBYLABEL')?RECORDEDBYLABEL:'Collector'); ?>
													<a href="#" onclick="return dwcDoc('recordedBy')"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" name="recordedby" tabindex="6" maxlength="255" value="<?php echo array_key_exists('recordedby',$occArr)?$occArr['recordedby']:''; ?>" onchange="fieldChanged('recordedby');" />
												</div>
												<div id="recordNumberDiv">
													<?php echo (defined('RECORDNUMBERLABEL')?RECORDNUMBERLABEL:'Number'); ?>
													<a href="#" onclick="return dwcDoc('recordNumber')"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" name="recordnumber" tabindex="8" maxlength="45" value="<?php echo array_key_exists('recordnumber',$occArr)?$occArr['recordnumber']:''; ?>" onchange="recordNumberChanged(this);" />
												</div>
												<div id="eventDateDiv" title="Earliest Date Collected">
													<?php echo (defined('EVENTDATELABEL')?EVENTDATELABEL:'Date'); ?>
													<a href="#" onclick="return dwcDoc('eventDate')"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" name="eventdate" tabindex="10" value="<?php echo array_key_exists('eventdate',$occArr)?$occArr['eventdate']:''; ?>" onchange="eventDateChanged(this);" />
												</div>
												<?php
												if(!defined('DUPESEARCH') || DUPESEARCH){
													?>
													<div id="dupesDiv">
														<input type="button" value="Dupes?" tabindex="12" onclick="searchDupes(this.form);" /><br/>
														<input type="checkbox" name="autodupe" value="1" onchange="autoDupeChanged(this)" />
														Auto search
													</div>
													<?php
												}
												?>
											</div>
											<div style="clear:both;">
												<div id="associatedCollectorsDiv">
													<div class="flabel">
														<?php echo (defined('ASSOCIATEDCOLLECTORSLABEL')?ASSOCIATEDCOLLECTORSLABEL:'Associated Collectors'); ?>
														<a href="#" onclick="return dwcDoc('associatedCollectors')"><img class="docimg" src="../../images/qmark.png" /></a>
													</div>
													<input type="text" name="associatedcollectors" tabindex="14" maxlength="255" value="<?php echo array_key_exists('associatedcollectors',$occArr)?$occArr['associatedcollectors']:''; ?>" onchange="fieldChanged('associatedcollectors');" />
												</div>
												<div id="verbatimEventDateDiv">
													<div class="flabel">
														<?php echo (defined('VERBATIMEVENTDATELABEL')?VERBATIMEVENTDATELABEL:'Verbatim Date'); ?>
														<a href="#" onclick="return dwcDoc('verbatimEventDate')"><img class="docimg" src="../../images/qmark.png" /></a>
													</div>
													<input type="text" name="verbatimeventdate" tabindex="19" maxlength="255" value="<?php echo array_key_exists('verbatimeventdate',$occArr)?$occArr['verbatimeventdate']:''; ?>" onchange="verbatimEventDateChanged(this)" />
												</div>
												<div id="dateToggleDiv">
													<a href="#" onclick="toggle('dateextradiv');return false;"><img src="../../images/editplus.png" style="width:15px;" /></a>
												</div>
												<?php
												if(array_key_exists('loan',$occArr)){
													?>
													<fieldset style="float:right;margin:3px;border:1px solid red;">
														<legend style="color:red;font-weight:bold;">Out On Loan</legend>
														<b>To:</b> <a href="../loans/index.php?loantype=out&collid=<?php echo $collId.'&loanid='.$occArr['loan']['id']; ?>">
															<?php echo $occArr['loan']['code']; ?></a><br/>
														<b>Due date:</b> <?php echo (isset($occArr['loan']['date'])?$occArr['loan']['date']:'Not Defined'); ?>
													</fieldset>
													<?php
												}
												?>
												<div id="dupeMsgDiv">
													<div id="dupesearch">Searching for Dupes...</div>
													<div id="dupenone" style="display:none;color:red;">No Dupes Found</div>
													<div id="dupedisplay" style="display:none;color:green;">Displaying Dupes</div>
												</div>
											</div>
											<div id="dateextradiv">
												<div id="ymdDiv">
													<?php echo (defined('YYYYMMDDLABEL')?YYYYMMDDLABEL:'YYYY-MM-DD'); ?>:
													<a href="#" onclick="return dwcDoc('year')"><img class="docimg" src="../../images/qmark.png" /></a>
													<input type="text" name="year" tabindex="20" value="<?php echo array_key_exists('year',$occArr)?$occArr['year']:''; ?>" onchange="inputIsNumeric(this, 'Year');fieldChanged('year');" title="Numeric Year" />-
													<input type="text" name="month" tabindex="21" value="<?php echo array_key_exists('month',$occArr)?$occArr['month']:''; ?>" onchange="inputIsNumeric(this, 'Month');fieldChanged('month');" title="Numeric Month" />-
													<input type="text" name="day" tabindex="22" value="<?php echo array_key_exists('day',$occArr)?$occArr['day']:''; ?>" onchange="inputIsNumeric(this, 'Day');fieldChanged('day');" title="Numeric Day" />
												</div>
												<div id="dayOfYearDiv">
													<?php echo (defined('DAYOFYEARLABEL')?DAYOFYEARLABEL:'Day of Year'); ?>:
													<a href="#" onclick="return dwcDoc('startDayOfYear')"><img class="docimg" src="../../images/qmark.png" /></a>
													<input type="text" name="startdayofyear" tabindex="24" value="<?php echo array_key_exists('startdayofyear',$occArr)?$occArr['startdayofyear']:''; ?>" onchange="inputIsNumeric(this, 'Start Day of Year');fieldChanged('startdayofyear');" title="Start Day of Year" /> -
													<input type="text" name="enddayofyear" tabindex="26" value="<?php echo array_key_exists('enddayofyear',$occArr)?$occArr['enddayofyear']:''; ?>" onchange="inputIsNumeric(this, 'End Day of Year');fieldChanged('enddayofyear');" title="End Day of Year" />
												</div>
                                                <div id="endDateDiv">
                                                    <?php echo (defined('ENDDATELABEL')?ENDDATELABEL:'Calculate End Day of Year'); ?>:
                                                    <input type="text" id="endDate" value="" onchange="endDateChanged();" />
                                                </div>
											</div>
											<?php
											if(isset($ACTIVATE_EXSICCATI) && $ACTIVATE_EXSICCATI){
												?>
												<div id="exsDiv">
													<div id="ometidDiv">
														Exsiccati Title<br/>
														<input id="exstitleinput" name="exstitle" value="<?php echo (isset($occArr['exstitle'])?$occArr['exstitle']:''); ?>" />
														<input id="ometidinput" name="ometid" type="text" style="display: none;" value="<?php echo (isset($occArr['ometid'])?$occArr['ometid']:''); ?>" onchange="fieldChanged('ometid')" />
													</div>
													<div id="exsnumberDiv">
														Number<br/>
														<input name="exsnumber" type="text" value="<?php echo isset($occArr['exsnumber'])?$occArr['exsnumber']:''; ?>" onchange="fieldChanged('exsnumber')" />
													</div>
												</div>
												<?php
											}
											?>
										</fieldset>
										<fieldset>
											<legend><b>Latest Identification</b></legend>
											<div style="clear:both;">
												<div id="scinameDiv">
													<?php echo (defined('SCIENTIFICNAMELABEL')?SCIENTIFICNAMELABEL:'Scientific Name'); ?>
													<a href="#" onclick="return dwcDoc('scientificName')"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" id="ffsciname" name="sciname" maxlength="250" tabindex="28" value="<?php echo array_key_exists('sciname',$occArr)?$occArr['sciname']:''; ?>" onchange="fieldChanged('sciname');" <?php if(!$isEditor || $isEditor == 3) echo 'disabled '; ?> />
													<input type="hidden" id="tidinterpreted" name="tidinterpreted" value="<?php echo array_key_exists('tidinterpreted',$occArr)?$occArr['tidinterpreted']:''; ?>" />
													<?php
													if(!$isEditor){
														echo '<div style="clear:both;color:red;margin-left:5px;">Note: Full editing permissions are needed to edit an identification</div>';
													}
													elseif($isEditor == 3){
														echo '<div style="clear:both;color:red;margin-left:5px;">Limited editing rights: use determination tab to edit identification</div>';
													}
													?>
												</div>
												<div id="scientificNameAuthorshipDiv">
													<?php echo (defined('SCIENTIFICNAMEAUTHORSHIPLABEL')?SCIENTIFICNAMEAUTHORSHIPLABEL:'Author'); ?>
													<a href="#" onclick="return dwcDoc('scientificNameAuthorship')"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" name="scientificnameauthorship" maxlength="100" tabindex="0" value="<?php echo array_key_exists('scientificnameauthorship',$occArr)?$occArr['scientificnameauthorship']:''; ?>" onchange="fieldChanged('scientificnameauthorship');" <?php if(!$isEditor || $isEditor == 3) echo 'disabled'; ?> />
												</div>
											</div>
											<div style="clear:both;padding:3px 0px 0px 10px;">
												<?php
												if(!$occId){
													echo '<div id="idRankDiv">';
													echo (defined('IDCONFIDENCELABEL')?IDCONFIDENCELABEL:'ID Confidence');
													echo ' <a href="#" onclick="return dwcDoc(\'idConfidence\')"><img class="docimg" src="../../images/qmark.png" /></a> ';
													echo '<select name="confidenceranking" onchange="fieldChanged(\'confidenceranking\')">';
													echo '<option value="">Undefined</option>';
													$idRankArr = array(10 => 'Absolute', 9 => 'Very High', 8 => 'High', 7 => 'High - verification requested', 6 => 'Medium - insignificant material', 5 => 'Medium', 4 => 'Medium - verification requested', 3 => 'Low - insignificant material', 2 => 'Low', 1 => 'Low - ID Requested', 0 => 'ID Requested');
													foreach($idRankArr as $rankKey => $rankText){
														echo '<option value="'.$rankKey.'">'.$rankKey.' - '.$rankText.'</option>';
													}
													echo '</select>';
													echo '</div>';
												}
												?>
												<div id="identificationQualifierDiv">
													<?php echo (defined('IDENTIFICATIONQUALIFIERLABEL')?IDENTIFICATIONQUALIFIERLABEL:'ID Qualifier'); ?>
													<a href="#" onclick="return dwcDoc('identificationQualifier')"><img class="docimg" src="../../images/qmark.png" /></a>
													<input type="text" name="identificationqualifier" tabindex="30" size="25" value="<?php echo array_key_exists('identificationqualifier',$occArr)?$occArr['identificationqualifier']:''; ?>" onchange="fieldChanged('identificationqualifier');" <?php if(!$isEditor || $isEditor == 3) echo 'disabled'; ?> />
												</div>
												<div  id="familyDiv">
													<?php echo (defined('FAMILYLABEL')?FAMILYLABEL:'Family'); ?>
													<a href="#" onclick="return dwcDoc('family')"><img class="docimg" src="../../images/qmark.png" /></a>
													<input type="text" name="family" maxlength="50" tabindex="0" value="<?php echo array_key_exists('family',$occArr)?$occArr['family']:''; ?>" onchange="fieldChanged('family');" />
												</div>
											</div>
											<div style="clear:both;padding:3px 0px 0px 10px;">
												<div id="identifiedByDiv">
													<?php echo (defined('IDENTIFIEDBYLABEL')?IDENTIFIEDBYLABEL:'Identified By'); ?>
													<a href="#" onclick="return dwcDoc('identifiedBy')"><img class="docimg" src="../../images/qmark.png" /></a>
													<input type="text" name="identifiedby" maxlength="255" tabindex="32" value="<?php echo array_key_exists('identifiedby',$occArr)?$occArr['identifiedby']:''; ?>" onchange="fieldChanged('identifiedby');" />
												</div>
												<div id="dateIdentifiedDiv">
													<?php echo (defined('DATEIDENTIFIEDLABEL')?DATEIDENTIFIEDLABEL:'Date Identified'); ?>
													<a href="#" onclick="return dwcDoc('dateIdentified')"><img class="docimg" src="../../images/qmark.png" /></a>
													<input type="text" name="dateidentified" maxlength="45" tabindex="34" value="<?php echo array_key_exists('dateidentified',$occArr)?$occArr['dateidentified']:''; ?>" onchange="fieldChanged('dateidentified');" />
												</div>
												<div id="idrefToggleDiv" onclick="toggle('idrefdiv');">
													<img src="../../images/editplus.png" style="width:15px;" />
												</div>
											</div>
											<div  id="idrefdiv">
												<div id="identificationReferencesDiv">
													<?php echo (defined('IDENTIFICATIONREFERENCELABEL')?IDENTIFICATIONREFERENCELABEL:'ID References'); ?>:
													<a href="#" onclick="return dwcDoc('identificationReferences')"><img class="docimg" src="../../images/qmark.png" /></a>
													<input type="text" name="identificationreferences" tabindex="36" value="<?php echo array_key_exists('identificationreferences',$occArr)?$occArr['identificationreferences']:''; ?>" onchange="fieldChanged('identificationreferences');" />
												</div>
												<div id="identificationRemarksDiv">
													<?php echo (defined('IDENTIFICATIONREMARKSLABEL')?IDENTIFICATIONREMARKSLABEL:'ID Remarks'); ?>:
													<a href="#" onclick="return dwcDoc('identificationRemarks')"><img class="docimg" src="../../images/qmark.png" /></a>
													<input type="text" name="identificationremarks" tabindex="38" value="<?php echo array_key_exists('identificationremarks',$occArr)?$occArr['identificationremarks']:''; ?>" onchange="fieldChanged('identificationremarks');" />
												</div>
												<div id="taxonRemarksDiv">
													<?php echo (defined('TAXONREMARKSLABEL')?TAXONREMARKSLABEL:'Taxon Remarks'); ?>:
													<a href="#" onclick="return dwcDoc('taxonRemarks')"><img class="docimg" src="../../images/qmark.png" /></a>
													<input type="text" name="taxonremarks" tabindex="39" value="<?php echo array_key_exists('taxonremarks',$occArr)?$occArr['taxonremarks']:''; ?>" onchange="fieldChanged('taxonremarks');" />
												</div>
											</div>
										</fieldset>
										<fieldset>
											<legend><b>Locality</b></legend>
											<div style="clear:both;">
												<div id="countryDiv">
													<?php echo (defined('COUNTRYLABEL')?COUNTRYLABEL:'Country'); ?>
													<a href="#" onclick="return dwcDoc('country')"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" id="ffcountry" name="country" tabindex="40" value="<?php echo array_key_exists('country',$occArr)?$occArr['country']:''; ?>" onchange="fieldChanged('country');" />
												</div>
												<div id="stateProvinceDiv">
													<?php echo (defined('STATEPROVINCELABEL')?STATEPROVINCELABEL:'State/Province'); ?>
													<a href="#" onclick="return dwcDoc('stateProvince')"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" id="ffstate" name="stateprovince" tabindex="42" value="<?php echo array_key_exists('stateprovince',$occArr)?$occArr['stateprovince']:''; ?>" onchange="stateProvinceChanged(this.value)" />
												</div>
												<div id="countyDiv">
													<?php echo (defined('COUNTYLABEL')?COUNTYLABEL:'County'); ?>
													<a href="#" onclick="return dwcDoc('county')"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" id="ffcounty" name="county" tabindex="44" value="<?php echo array_key_exists('county',$occArr)?$occArr['county']:''; ?>" onchange="fieldChanged('county');" />
												</div>
												<div id="municipalityDiv">
													<?php echo (defined('MUNICIPALITYLABEL')?MUNICIPALITYLABEL:'Municipality'); ?>
													<a href="#" onclick="return dwcDoc('municipality')"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" id="ffmunicipality" name="municipality" tabindex="45" value="<?php echo array_key_exists('municipality',$occArr)?$occArr['municipality']:''; ?>" onchange="fieldChanged('municipality');" />
												</div>
											</div>
											<div id="localityDiv">
												<?php echo (defined('LOCALITYLABEL')?LOCALITYLABEL:'Locality'); ?>
												<a href="#" onclick="return dwcDoc('locality')"><img class="docimg" src="../../images/qmark.png" /></a>
												<br />
												<input type="text" id="fflocality" name="locality" tabindex="46" value="<?php echo array_key_exists('locality',$occArr)?$occArr['locality']:''; ?>" onchange="fieldChanged('locality');" />
												<a id="localityExtraToggle" onclick="toggle('localityExtraDiv');">
													<img src="../../images/editplus.png" style="width:15px;" />
												</a>
											</div>
											<?php
											$localityExtraDiv = 'none';
											if(array_key_exists("locationremarks",$occArr) && $occArr["locationremarks"]) $localityExtraDiv = "block";
											?>
											<div id="localityExtraDiv" style="display:<?php echo $localityExtraDiv; ?>">
												<div id="locationRemarksDiv">
													<?php echo (defined('LOCATIONREMARKSLABEL')?LOCATIONREMARKSLABEL:'Location Remarks'); ?>
													<a href="#" onclick="return dwcDoc('locationRemarks')"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" id="locationremarks" name="locationremarks" value="<?php echo array_key_exists('locationremarks',$occArr)?$occArr['locationremarks']:''; ?>" onchange="fieldChanged('locationremarks');" />
												</div>
											</div>
											<?php
											if(!defined('LOCALITYAUTOLOOKUP') || LOCALITYAUTOLOOKUP){
												echo '<div id="localAutoDeactivatedDiv">';
												echo '<input name="localautodeactivated" type="checkbox" value="1" onchange="localAutoChanged(this)" '.(defined('LOCALITYAUTOLOOKUP') && LOCALITYAUTOLOOKUP==2?'checked':'').' /> ';
												echo 'Deactivate Locality Lookup</div>';
											}
											$lsHasValue = array_key_exists("localitysecurity",$occArr)&&$occArr["localitysecurity"]?1:0;
											$lsrValue = array_key_exists('localitysecurityreason',$occArr)?$occArr['localitysecurityreason']:'';
											?>
											<div id="localSecurityDiv">
												<div style="float:left;">
													<input type="checkbox" name="localitysecurity" tabindex="0" value="1" <?php echo $lsHasValue?"CHECKED":""; ?> onchange="localitySecurityChanged(this.form);" title="Hide Locality Data from General Public" />
													<?php echo (defined('LOCALITYSECURITYLABEL')?LOCALITYSECURITYLABEL:'Locality Security'); ?>
													<a href="#" onclick="return dwcDoc('localitySecurity')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												</div>
												<div id="locsecreason" style="margin-left:5px;border:2px solid gray;float:left;display:<?php echo ($lsrValue?'inline':'none') ?>;padding:3px">
													<div ><input name="lockLocalitySecurity" type="checkbox" onchange="securityLockChanged(this)"  <?php echo ($lsrValue?'checked':'') ?> /> Lock Security Setting</div>
													<?php
													echo (defined('LOCALITYSECURITYREASONLABEL')?LOCALITYSECURITYREASONLABEL:'Reason');
													?>:
													<input type="text" name="localitysecurityreason" tabindex="0" onchange="localitySecurityReasonChanged();" value="<?php echo $lsrValue; ?>" title="Entering any text will lock security status on or off; leave blank to accept default security status" />
												</div>
											</div>
											<div style="clear:both;">
												<div id="decimalLatitudeDiv">
													<?php echo (defined('DECIMALLATITUDELABEL')?DECIMALLATITUDELABEL:'Latitude'); ?>
													<br/>
													<?php
													$latValue = "";
													if(array_key_exists("decimallatitude",$occArr) && $occArr["decimallatitude"] != "") {
														$latValue = $occArr["decimallatitude"];
													}
													?>
													<input type="text" id="decimallatitude" name="decimallatitude" tabindex="50" maxlength="15" value="<?php echo $latValue; ?>" onchange="decimalLatitudeChanged(this.form)" />
												</div>
												<div id="decimalLongitudeDiv">
													<?php echo (defined('DECIMALLONGITUDELABEL')?DECIMALLONGITUDELABEL:'Longitude'); ?>
													<br/>
													<?php
													$longValue = "";
													if(array_key_exists("decimallongitude",$occArr) && $occArr["decimallongitude"] != "") {
														$longValue = $occArr["decimallongitude"];
													}
													?>
													<input type="text" id="decimallongitude" name="decimallongitude" tabindex="52" maxlength="15" value="<?php echo $longValue; ?>" onchange="decimalLongitudeChanged(this.form);" />
												</div>
												<div id="coordinateUncertaintyInMetersDiv">
													<?php echo (defined('COORDINATEUNCERTAINITYINMETERSLABEL')?COORDINATEUNCERTAINITYINMETERSLABEL:'Uncertainty'); ?>
													<a href="#" onclick="return dwcDoc('coordinateUncertaintyInMeters')"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" id="coordinateuncertaintyinmeters" name="coordinateuncertaintyinmeters" tabindex="54" maxlength="10" value="<?php echo array_key_exists('coordinateuncertaintyinmeters',$occArr)?$occArr['coordinateuncertaintyinmeters']:''; ?>" onchange="coordinateUncertaintyInMetersChanged(this.form);" title="Uncertainty in Meters" />
												</div>
												<div id="googleDiv" onclick="openMappingAid();" title="Google Maps">
													<img src="../../images/world.png" />
												</div>
												<div id="geoLocateDiv" title="GeoLocate locality">
													<a href="#" onclick="geoLocateLocality();"><img src="../../images/geolocate.png"/></a>
												</div>
												<div id="coordCloningDiv" title="Coordinate cloning tool" >
													<input type="button" value="C" onclick="geoCloneTool()" />
												</div>
												<div id="geoToolsDiv" title="Tools for converting additional formats" >
													<input type="button" value="F" onclick="toggleCoordDiv()" />
												</div>
												<div id="geodeticDatumDiv">
													<?php echo (defined('GEODETICDATIMLABEL')?GEODETICDATIMLABEL:'Datum'); ?>
													<a href="#" onclick="return dwcDoc('geodeticDatum')"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" id="geodeticdatum" name="geodeticdatum" tabindex="56" maxlength="255" value="<?php echo array_key_exists('geodeticdatum',$occArr)?$occArr['geodeticdatum']:''; ?>" onchange="fieldChanged('geodeticdatum');" />
												</div>
												<div id="verbatimCoordinatesDiv">
													<div style="float:left;margin:18px 2px 0px 2px" title="Recalculate Decimal Coordinates">
														<a href="#" onclick="parseVerbatimCoordinates(document.fullform,1);return false">&lt;&lt;</a>
													</div>
													<div style="float:left;">
														<?php echo (defined('VERBATIMCOORDINATESLABEL')?VERBATIMCOORDINATESLABEL:'Verbatim Coordinates'); ?>
														<a href="#" onclick="return dwcDoc('verbatimCoordinates')"><img class="docimg" src="../../images/qmark.png" /></a>
														<br/>
														<input type="text" name="verbatimcoordinates" tabindex="57" maxlength="255" value="<?php echo array_key_exists('verbatimcoordinates',$occArr)?$occArr['verbatimcoordinates']:''; ?>" onchange="verbatimCoordinatesChanged(this.form);" title="" />
													</div>
												</div>
											</div>
											<div style="clear:both;">
												<div id="elevationDiv">
													<?php echo (defined('ELEVATIONINMETERSLABEL')?ELEVATIONINMETERSLABEL:'Elevation in Meters'); ?>
													<a href="#" onclick="return dwcDoc('minimumElevationInMeters')"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" name="minimumelevationinmeters" tabindex="58" maxlength="6" value="<?php echo array_key_exists('minimumelevationinmeters',$occArr)?$occArr['minimumelevationinmeters']:''; ?>" onchange="minimumElevationInMetersChanged(this.form);" title="Minumum Elevation In Meters" /> -
													<input type="text" name="maximumelevationinmeters" tabindex="60" maxlength="6" value="<?php echo array_key_exists('maximumelevationinmeters',$occArr)?$occArr['maximumelevationinmeters']:''; ?>" onchange="maximumElevationInMetersChanged(this.form);" title="Maximum Elevation In Meters" />
												</div>
												<div id="verbatimElevationDiv">
													<div style="float:left;margin:18px 2px 0px 2px" title="Recalculate Elevation in Meters">
														<a href="#" onclick="parseVerbatimElevation(document.fullform);return false">&lt;&lt;</a>
													</div>
													<div style="float:left;">
														<?php echo (defined('VERBATIMELEVATIONLABEL')?VERBATIMELEVATIONLABEL:'Verbatim Elevation'); ?>
														<a href="#" onclick="return dwcDoc('verbatimElevation')"><img class="docimg" src="../../images/qmark.png" /></a>
														<br/>
														<input type="text" name="verbatimelevation" tabindex="61" maxlength="255" value="<?php echo array_key_exists('verbatimelevation',$occArr)?$occArr['verbatimelevation']:''; ?>" onchange="verbatimElevationChanged(this.form);" />
													</div>
												</div>
												<div id="depthDiv">
													<?php echo (defined('DEPTHINMETERSLABEL')?DEPTHINMETERSLABEL:'Depth in Meters'); ?>
													<a href="#" onclick="return dwcDoc('minimumDepthInMeters')"><img class="docimg" src="../../images/qmark.png" /></a>
													<br/>
													<input type="text" name="minimumdepthinmeters" tabindex="63" maxlength="6" value="<?php echo array_key_exists('minimumdepthinmeters',$occArr)?$occArr['minimumdepthinmeters']:''; ?>" onchange="minimumDepthInMetersChanged(this.form);" title="Minumum Depth In Meters" /> -
													<input type="text" name="maximumdepthinmeters" tabindex="64" maxlength="6" value="<?php echo array_key_exists('maximumdepthinmeters',$occArr)?$occArr['maximumdepthinmeters']:''; ?>" onchange="maximumDepthInMetersChanged(this.form);" title="Depth In Meters" />
												</div>
												<div id="verbatimDepthDiv">
													<div style="float:left;">
														<?php echo (defined('VERBATIMDEPTHLABEL')?VERBATIMDEPTHLABEL:'Verbatim Depth'); ?>
														<a href="#" onclick="return dwcDoc('verbatimDepth')"><img class="docimg" src="../../images/qmark.png" /></a>
														<br/>
														<input type="text" name="verbatimdepth" tabindex="65" maxlength="255" value="<?php echo array_key_exists('verbatimdepth',$occArr)?$occArr['verbatimdepth']:''; ?>" onchange="fieldChanged('verbatimdepth');" />
													</div>
												</div>
												<div id="georefExtraToggleDiv" onclick="toggle('georefExtraDiv');">
													<img src="../../images/editplus.png" style="width:15px;" />
												</div>
											</div>
											<?php
											include_once('includes/geotools.php');
											$georefExtraDiv = 'display:';
											if(array_key_exists("georeferencedby",$occArr) && $occArr["georeferencedby"]){
												$georefExtraDiv .= "block";
											}
											elseif(array_key_exists("footprintwkt",$occArr) && $occArr["footprintwkt"]){
												$georefExtraDiv .= "block";
											}
											elseif(array_key_exists("georeferenceprotocol",$occArr) && $occArr["georeferenceprotocol"]){
												$georefExtraDiv .= "block";
											}
											elseif(array_key_exists("georeferencesources",$occArr) && $occArr["georeferencesources"]){
												$georefExtraDiv .= "block";
											}
											elseif(array_key_exists("georeferenceverificationstatus",$occArr) && $occArr["georeferenceverificationstatus"]){
												$georefExtraDiv .= "block";
											}
											elseif(array_key_exists("georeferenceremarks",$occArr) && $occArr["georeferenceremarks"]){
												$georefExtraDiv .= "block";
											}
											?>
											<div id="georefExtraDiv" style="<?php echo $georefExtraDiv; ?>;">
												<div style="clear:both;">
													<div id="georeferencedByDiv">
														<?php echo (defined('GEOREFERENCEDBYLABEL')?GEOREFERENCEDBYLABEL:'Georeferenced By'); ?>
														<br/>
														<input type="text" name="georeferencedby" tabindex="66" maxlength="255" value="<?php echo array_key_exists('georeferencedby',$occArr)?$occArr['georeferencedby']:''; ?>" onchange="fieldChanged('georeferencedby');" />
													</div>
													<div id="georeferenceSourcesDiv">
														<?php echo (defined('GEOREFERENCESOURCESLABEL')?GEOREFERENCESOURCESLABEL:'Georeference Sources'); ?>
														<a href="#" onclick="return dwcDoc('georeferenceSources')"><img class="docimg" src="../../images/qmark.png" /></a>
														<br/>
														<input type="text" name="georeferencesources" tabindex="70" maxlength="255" value="<?php echo array_key_exists('georeferencesources',$occArr)?$occArr['georeferencesources']:''; ?>" onchange="fieldChanged('georeferencesources');" />
													</div>
													<div id="georeferenceRemarksDiv">
														<?php echo (defined('GEOREFERENCEREMARKSLABEL')?GEOREFERENCEREMARKSLABEL:'Georeference Remarks'); ?>
														<br/>
														<input type="text" name="georeferenceremarks" tabindex="74" maxlength="255" value="<?php echo array_key_exists('georeferenceremarks',$occArr)?$occArr['georeferenceremarks']:''; ?>" onchange="fieldChanged('georeferenceremarks');" />
													</div>
												</div>
												<div style="clear:both;">
													<div id="georeferenceProtocolDiv">
														<?php echo (defined('GEOREFERENCEPROTOCOLLABEL')?GEOREFERENCEPROTOCOLLABEL:'Georeference Protocol'); ?>
														<a href="#" onclick="return dwcDoc('georeferenceProtocol')"><img class="docimg" src="../../images/qmark.png" /></a>
														<br/>
														<input type="text" name="georeferenceprotocol" tabindex="76" maxlength="255" value="<?php echo array_key_exists('georeferenceprotocol',$occArr)?$occArr['georeferenceprotocol']:''; ?>" onchange="fieldChanged('georeferenceprotocol');" />
													</div>
													<div id="georeferenceVerificationStatusDiv">
														<?php echo (defined('GEOREFERENCEVERIFICATIONSTATUSLABEL')?GEOREFERENCEVERIFICATIONSTATUSLABEL:'Georef Verification Status'); ?>
														<a href="#" onclick="return dwcDoc('georeferenceVerificationStatus')"><img class="docimg" src="../../images/qmark.png" /></a>
														<br/>
														<input type="text" name="georeferenceverificationstatus" tabindex="78" maxlength="32" value="<?php echo array_key_exists('georeferenceverificationstatus',$occArr)?$occArr['georeferenceverificationstatus']:''; ?>" onchange="fieldChanged('georeferenceverificationstatus');" />
													</div>
													<div id="footprintWktDiv">
														<?php echo (defined('FOOTPRINTWKTLABEL')?FOOTPRINTWKTLABEL:'Footprint Polygon'); ?>
														<br/>
														<div style="float:right;margin-top:-2px;margin-left:2px;" id="googleDiv" onclick="openMappingPolyAid();" title="Google Maps">
															<img src="../../images/world.png" />
														</div>
														<textarea name="footprintwkt" id="footprintwkt" onchange="footPrintWktChanged(this)" style="height:40px;resize:vertical;" ><?php echo array_key_exists('footprintwkt',$occArr)?$occArr['footprintwkt']:''; ?></textarea>
													</div>
												</div>
											</div>
										</fieldset>
										<fieldset>
											<legend><b>Misc</b></legend>
											<div id="habitatDiv">
												<?php echo (defined('HABITATLABEL')?HABITATLABEL:'Habitat'); ?>
												<a href="#" onclick="return dwcDoc('habitat')"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" name="habitat" tabindex="80" value="<?php echo array_key_exists('habitat',$occArr)?$occArr['habitat']:''; ?>" onchange="fieldChanged('habitat');" />
											</div>
											<div id="substrateDiv">
												<?php echo (defined('SUBSTRATELABEL')?SUBSTRATELABEL:'Substrate'); ?>
												<a href="#" onclick="return dwcDoc('substrate')"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" name="substrate" tabindex="82" maxlength="500" value="<?php echo array_key_exists('substrate',$occArr)?$occArr['substrate']:''; ?>" onchange="fieldChanged('substrate');" />
											</div>
											<?php
											if(isset($QuickHostEntryIsActive) && $QuickHostEntryIsActive) { // Quick host field
												$quickHostArr = $occManager->getQuickHost($occId);
												?>
												<div id="hostDiv">
													<?php echo (defined('HOSTLABEL')?HOSTLABEL:'Host'); ?><br/>
													<input type="text" name="host" id="quickhost" tabindex="82" maxlength="500" value="<?php echo ($quickHostArr?$quickHostArr['verbatimsciname']:''); ?>" onchange="fieldChanged('host');" />
													<input type="hidden" name="hostassocid" value="<?php echo ($quickHostArr?$quickHostArr['associd']:''); ?>" />
												</div>
												<?php
											}
											?>
											<div id="associatedTaxaDiv">
												<?php echo (defined('ASSOCIATEDTAXALABEL')?ASSOCIATEDTAXALABEL:'Associated Taxa'); ?>
												<a href="#" onclick="return dwcDoc('associatedTaxa')"><img class="docimg" src="../../images/qmark.png" style="width:9px;margin-bottom:2px" /></a>
												<br/>
												<textarea name="associatedtaxa" tabindex="84" onchange="fieldChanged('associatedtaxa');" style="height:22px;"><?php echo array_key_exists('associatedtaxa',$occArr)?$occArr['associatedtaxa']:''; ?></textarea>
												<?php
												if(!isset($ACTIVATEASSOCTAXAAID) || $ACTIVATEASSOCTAXAAID){
													echo '<a href="#" onclick="openAssocSppAid();return false;"><img src="../../images/list.png" /></a>';
												}
												?>
											</div>
											<div id="verbatimAttributesDiv">
												<?php echo (defined('VERBATIMATTRIBUTESLABEL')?VERBATIMATTRIBUTESLABEL:'Description'); ?>
												<a href="#" onclick="return dwcDoc('verbatimAttributes')"><img class="docimg" src="../../images/qmark.png" /></a>
												<br/>
												<input type="text" name="verbatimattributes" tabindex="86" value="<?php echo array_key_exists('verbatimattributes',$occArr)?$occArr['verbatimattributes']:''; ?>" onchange="fieldChanged('verbatimattributes');" />
											</div>
											<div id="occurrenceRemarksDiv">
												<?php echo (defined('OCCURRENCEREMARKSLABEL')?OCCURRENCEREMARKSLABEL:'Notes (Occurrence Remarks)'); ?>
												<a href="#" onclick="return dwcDoc('occurrenceRemarks')"><img class="docimg" src="../../images/qmark.png" style="width:9px;margin-bottom:2px" /></a>
												<br/>
												<input type="text" name="occurrenceremarks" tabindex="88" value="<?php echo array_key_exists('occurrenceremarks',$occArr)?$occArr['occurrenceremarks']:''; ?>" onchange="fieldChanged('occurrenceremarks');" title="Occurrence Remarks" />
												<span id="dynPropToggleSpan" onclick="toggle('dynamicPropertiesDiv');">
													<img src="../../images/editplus.png" />
												</span>
											</div>
											<div id="dynamicPropertiesDiv">
												<?php echo (defined('DYNAMICPROPERTIESLABEL')?DYNAMICPROPERTIESLABEL:'Dynamic Properties'); ?>
												<a href="#" onclick="return dwcDoc('dynamicProperties')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
												<input type="text" name="dynamicproperties" tabindex="89" value="<?php echo array_key_exists('dynamicproperties',$occArr)?$occArr['dynamicproperties']:''; ?>" onchange="fieldChanged('dynamicproperties');" />
											</div>
											<div style="padding:2px;">
												<div id="lifeStageDiv">
													<?php echo (defined('LIFESTAGELABEL')?LIFESTAGELABEL:'Life Stage'); ?>
													<a href="#" onclick="return dwcDoc('lifeStage')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
													<input type="text" name="lifestage" tabindex="90" maxlength="45" value="<?php echo array_key_exists('lifestage',$occArr)?$occArr['lifestage']:''; ?>" onchange="fieldChanged('lifestage');" />
												</div>
												<div id="sexDiv">
													<?php echo (defined('SEXLABEL')?SEXLABEL:'Sex'); ?>
													<a href="#" onclick="return dwcDoc('sex')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
													<input type="text" name="sex" tabindex="92" maxlength="45" value="<?php echo array_key_exists('sex',$occArr)?$occArr['sex']:''; ?>" onchange="fieldChanged('sex');" />
												</div>
												<div id="individualCountDiv">
													<?php echo (defined('INDIVIDUALCOUNTLABEL')?INDIVIDUALCOUNTLABEL:'Individual Count'); ?>
													<a href="#" onclick="return dwcDoc('individualCount')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
													<input type="text" name="individualcount" tabindex="94" maxlength="45" value="<?php echo array_key_exists('individualcount',$occArr)?$occArr['individualcount']:''; ?>" onchange="fieldChanged('individualcount');" />
												</div>
												<div id="samplingProtocolDiv">
													<?php echo (defined('SAMPLINGPROTOCOLLABEL')?SAMPLINGPROTOCOLLABEL:'Sampling Protocol'); ?>
													<a href="#" onclick="return dwcDoc('samplingProtocol')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
													<input type="text" name="samplingprotocol" tabindex="95" maxlength="100" value="<?php echo array_key_exists('samplingprotocol',$occArr)?$occArr['samplingprotocol']:''; ?>" onchange="fieldChanged('samplingprotocol');" />
												</div>
												<div id="preparationsDiv">
													<?php echo (defined('PREPARATIONSLABEL')?PREPARATIONSLABEL:'Preparations'); ?>
													<a href="#" onclick="return dwcDoc('preparations')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
													<input type="text" name="preparations" tabindex="97" maxlength="100" value="<?php echo array_key_exists('preparations',$occArr)?$occArr['preparations']:''; ?>" onchange="fieldChanged('preparations');" />
												</div>
												<div id="reproductiveConditionDiv">
													<?php echo (defined('REPRODUCTIVECONDITIONLABEL')?REPRODUCTIVECONDITIONLABEL:'Phenology'); ?>
													<a href="#" onclick="return dwcDoc('reproductiveCondition')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
													<?php
													if(isset($reproductiveConditionTerms)){
														if($reproductiveConditionTerms){
															?>
															<select name="reproductivecondition" onchange="fieldChanged('reproductivecondition');" tabindex="99" >
																<option value="">-----------------</option>
																<?php
																foreach($reproductiveConditionTerms as $term){
																	echo '<option value="'.$term.'" '.(isset($occArr['reproductivecondition']) && $term==$occArr['reproductivecondition']?'SELECTED':'').'>'.$term.'</option>';
																}
																?>
															</select>
															<?php
														}
													}
													else{
													?>
														<input type="text" name="reproductivecondition" tabindex="99" maxlength="255" value="<?php echo array_key_exists('reproductivecondition',$occArr)?$occArr['reproductivecondition']:''; ?>" onchange="fieldChanged('reproductivecondition');" />
													<?php
													}
													?>

												</div>
												<div id="establishmentMeansDiv">
													<?php echo (defined('ESTABLISHMENTMEANSLABEL')?ESTABLISHMENTMEANSLABEL:'Establishment Means'); ?>
													<a href="#" onclick="return dwcDoc('establishmentMeans')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
													<input type="text" name="establishmentmeans" tabindex="100" maxlength="32" value="<?php echo array_key_exists('establishmentmeans',$occArr)?$occArr['establishmentmeans']:''; ?>" onchange="fieldChanged('establishmentmeans');" />
												</div>
												<div id="cultivationStatusDiv">
													<?php $hasValue = array_key_exists("cultivationstatus",$occArr)&&$occArr["cultivationstatus"]?1:0; ?>
													<input type="checkbox" name="cultivationstatus" tabindex="102" value="1" <?php echo $hasValue?'CHECKED':''; ?> onchange="fieldChanged('cultivationstatus');" />
													<?php echo (defined('CULTIVATIONSTATUSLABEL')?CULTIVATIONSTATUSLABEL:'Cultivated'); ?>
												</div>
											</div>
										</fieldset>
										<fieldset>
											<legend><b>Curation</b></legend>
											<div style="padding:3px;clear:both;">
												<div id="typeStatusDiv">
													<?php echo (defined('TYPESTATUSLABEL')?TYPESTATUSLABEL:'Type Status'); ?>
													<a href="#" onclick="return dwcDoc('typeStatus')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
													<input type="text" name="typestatus" tabindex="103" maxlength="255" value="<?php echo array_key_exists('typestatus',$occArr)?$occArr['typestatus']:''; ?>" onchange="fieldChanged('typestatus');" />
												</div>
												<div id="dispositionDiv">
													<?php echo (defined('DISPOSITIONLABEL')?DISPOSITIONLABEL:'Disposition'); ?>
													<a href="#" onclick="return dwcDoc('disposition')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
													<input type="text" name="disposition" tabindex="104" maxlength="32" value="<?php echo array_key_exists('disposition',$occArr)?$occArr['disposition']:''; ?>" onchange="fieldChanged('disposition');" />
												</div>
												<div id="occurrenceIdDiv" title="If different than institution code">
													<?php echo (defined('OCCURRENCEIDLABEL')?OCCURRENCEIDLABEL:'Occurrence ID'); ?>
													<a href="#" onclick="return dwcDoc('occurrenceid')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
													<input type="text" name="occurrenceid" tabindex="105" maxlength="255" value="<?php echo array_key_exists('occurrenceid',$occArr)?$occArr['occurrenceid']:''; ?>" onchange="fieldChanged('occurrenceid');" />
												</div>
												<div id="fieldNumberDiv" title="If different than institution code">
													<?php echo (defined('FIELDNUMBERLABEL')?FIELDNUMBERLABEL:'Field Number'); ?>
													<a href="#" onclick="return dwcDoc('fieldnumber')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
													<input type="text" name="fieldnumber" tabindex="107" maxlength="45" value="<?php echo array_key_exists('fieldnumber',$occArr)?$occArr['fieldnumber']:''; ?>" onchange="fieldChanged('fieldnumber');" />
												</div>
												<div id="basisOfRecordDiv">
													<?php echo (defined('BASISOFRECORDLABEL')?BASISOFRECORDLABEL:'Basis of Record'); ?>
													<a href="#" onclick="return dwcDoc('basisOfRecord')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
													<?php
													$borArr = array('FossilSpecimen','HumanObservation','LivingSpecimen','MachineObservation','PreservedSpecimen');
													$targetBOR = '';
													$extraBOR = '';
													if(isset($occArr['basisofrecord']) && $occArr['basisofrecord']){
														if(in_array($occArr['basisofrecord'],$borArr)){
															$targetBOR = $occArr['basisofrecord'];
														}
														else{
															$extraBOR = $occArr['basisofrecord'];
														}
													}
													if(!isset($occArr['basisofrecord']) || !$occArr['basisofrecord']){
														if($collMap['colltype']=='General Observations' || $collMap['colltype']=='Observations'){
															$targetBOR = 'HumanObservation';
														}
														elseif($collMap['colltype']=='Preserved Specimens'){
															$targetBOR = 'PreservedSpecimen';
														}
													}
													?>
													<select name="basisofrecord" tabindex="109" onchange="fieldChanged('basisofrecord');">
														<?php
														foreach($borArr as $bValue){
															echo '<option '.($bValue == $targetBOR?'SELECTED':'').'>'.$bValue.'</option>';
														}
														if($extraBOR) echo '<option value="">---Non Sanctioned Value---</option><option SELECTED>'.$extraBOR.'</option>';
														?>
													</select>
												</div>
												<div id="languageDiv">
													<?php echo (defined('LANGUAGELABEL')?LANGUAGELABEL:'Language'); ?><br/>
													<input type="text" name="language" tabindex="111" maxlength="20" value="<?php echo array_key_exists('language',$occArr)?$occArr['language']:''; ?>" onchange="fieldChanged('language');" />
												</div>
												<div id="labelProjectDiv">
													<?php echo (defined('LABELPROJECTLABEL')?LABELPROJECTLABEL:'Label Project'); ?><br/>
													<input type="text" name="labelproject" tabindex="112" maxlength="45" value="<?php echo array_key_exists('labelproject',$occArr)?$occArr['labelproject']:''; ?>" onchange="fieldChanged('labelproject');" />
												</div>
												<div id="duplicateQuantityDiv" title="aka label quantity">
													<?php echo (defined('DUPLICATEQUALITYCOUNTLABEL')?DUPLICATEQUALITYCOUNTLABEL:'Dupe Count'); ?><br/>
													<input type="text" name="duplicatequantity" tabindex="116" value="<?php echo array_key_exists('duplicatequantity',$occArr)?$occArr['duplicatequantity']:''; ?>" onchange="fieldChanged('duplicatequantity');" />
												</div>
											</div>
											<div style="padding:3px;clear:both;">
												<div id="institutionCodeDiv" title="Overrides institution code set within collection's metadata form">
													<?php echo (defined('INSTITUTIONCODELABEL')?INSTITUTIONCODELABEL:'Institution Code (override)'); ?>
													<a href="#" onclick="return dwcDoc('institutionCode')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
													<input type="text" name="institutioncode" tabindex="116" maxlength="32" value="<?php echo array_key_exists('institutioncode',$occArr)?$occArr['institutioncode']:''; ?>" onchange="fieldChanged('institutioncode');" />
												</div>
												<div id="collectionCodeDiv" title="Overrides collection code set within collection's metadata form">
													<?php echo (defined('COLLECTIONCODELABEL')?COLLECTIONCODELABEL:'Collection Code (override)'); ?>
													<a href="#" onclick="return dwcDoc('collectionCode')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
													<input type="text" name="collectioncode" tabindex="117" maxlength="32" value="<?php echo array_key_exists('collectioncode',$occArr)?$occArr['collectioncode']:''; ?>" onchange="fieldChanged('collectioncode');" />
												</div>
												<div id="ownerInstitutionCodeDiv" title="If different than institution code">
													<?php echo (defined('OWNERINSTITUTIONCODELABEL')?OWNERINSTITUTIONCODELABEL:'Owner Code (override)'); ?>
													<a href="#" onclick="return dwcDoc('ownerInstitutionCode')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
													<input type="text" name="ownerinstitutioncode" tabindex="118" maxlength="32" value="<?php echo array_key_exists('ownerinstitutioncode',$occArr)?$occArr['ownerinstitutioncode']:''; ?>" onchange="fieldChanged('ownerinstitutioncode');" />
												</div>
												<div id="processingStatusDiv">
													<?php echo (defined('PROCESSINGSTATUSLABEL')?PROCESSINGSTATUSLABEL:'Processing Status'); ?><br/>
													<?php
														$pStatus = array_key_exists('processingstatus',$occArr)?strtolower($occArr['processingstatus']):'';
														if(!$pStatus && !$occId) $pStatus = 'pending review';
													?>
													<select name="processingstatus" tabindex="120" onchange="fieldChanged('processingstatus');">
														<option value=''>No Set Status</option>
														<option value=''>-------------------</option>
														<?php
														foreach($processingStatusArr as $v){
															//Don't display these options is editor is crowd sourced
															$keyOut = strtolower($v);
															if($isEditor || ($keyOut != 'reviewed' && $keyOut != 'closed')){
																echo '<option value="'.$keyOut.'" '.($pStatus==$keyOut?'SELECTED':'').'>'.ucwords($v).'</option>';
															}
														}
														if($pStatus && $pStatus != 'isnull' && !in_array($pStatus,$processingStatusArr)){
															echo '<option value="'.$pStatus.'" SELECTED>'.$pStatus.'</option>';
														}
														?>
													</select>
												</div>
                                                <div id="dataGeneralizationsDiv" title="aka data generalizations">
                                                    <?php echo (defined('DATAGENERALIZATIONSLABEL')?DATAGENERALIZATIONSLABEL:'Data Generalizations'); ?><br/>
                                                    <input type="text" name="datageneralizations" tabindex="121" value="<?php echo array_key_exists('datageneralizations',$occArr)?$occArr['datageneralizations']:''; ?>" onchange="fieldChanged('datageneralizations');" />
                                                </div>
											</div>
											<?php
											if($occId){
												?>
												<div id="pkDiv">
													<hr/>
													<div style="float:left;" title="Internal occurrence record Primary Key (occid)">
														<?php if($occId) echo 'Key: '.$occId; ?>
													</div>
													<div style="float:left;margin-left:50px;">
														<?php if(array_key_exists('datelastmodified',$occArr)) echo 'Modified: '.$occArr['datelastmodified']; ?>
													</div>
													<div style="float:left;margin-left:50px;">
														<?php
														if(array_key_exists('recordenteredby',$occArr)){
															echo 'Entered by: '.($occArr['recordenteredby']?$occArr['recordenteredby']:'not recorded');
														}
														if(isset($occArr['dateentered']) && $occArr['dateentered']) echo ' ['.$occArr['dateentered'].']';
														?>
													</div>
												</div>
												<?php
											}
											?>
										</fieldset>
										<?php
										if($navStr){
											//echo '<div style="float:right;margin-right:20px;">'.$navStr.'</div>'."\n";
										}
										?>
										<div style="padding:10px;">
											<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
											<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
											<input type="hidden" name="observeruid" value="<?php echo $SYMB_UID; ?>" />
											<input type="hidden" name="csmode" value="<?php echo $crowdSourceMode; ?>" />
											<input type="hidden" name="linkdupe" value="" />
											<?php
											if($occId){
												if(($isEditor == 1 || $isEditor == 2) && !$crowdSourceMode){
													?>
													<div style="float:right;">
														<fieldset style="padding:15px;background-color:lightyellow;">
															<legend><b>Additional Options</b></legend>
															<input type="button" value="Go to New Occurrence Record" onclick="verifyGotoNew(this.form);" /><br/>
															<input type="hidden" name="gotomode" value="" />
															<input type="checkbox" name="carryloc" value="1" /> Carry over locality values
														</fieldset>
													</div>
													<?php
												}
												?>
												<div id="editButtonDiv">
													<input type="submit" name="submitaction" value="Save Edits" style="width:150px;" onclick="return verifyFullFormEdits(this.form)" disabled />
													<br/>
													Status Auto-Set:
													<select name="autoprocessingstatus" onchange="autoProcessingStatusChanged(this)">
														<option value=''>Not Activated</option>
														<option value=''>-------------------</option>
														<?php
														foreach($processingStatusArr as $v){
															$keyOut = strtolower($v);
															//Don't display all options if editor is crowd sourced
															if($isEditor || ($keyOut != 'reviewed' && $keyOut != 'closed')){
																echo '<option value="'.$keyOut.'" '.($crowdSourceMode && $keyOut == "pending review"?'SELECTED':'').'>'.ucwords($v).'</option>';
															}
														}
														?>
													</select>
													<?php
													if($occIndex !== false){
														?>
														<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
														<?php
													}
													?>
													<input type="hidden" name="editedfields" value="" />
												</div>
												<?php
											}
											else{
												$userChecklists = $occManager->getUserChecklists();
												if($userChecklists){
													?>
													<fieldset>
														<legend><b>Checklist Voucher</b></legend>
														Link Occurrence to Checklist:
														<select name="clidvoucher">
															<option value="">No Checklist Selected</option>
															<option value="">---------------------------------------------</option>
															<?php
															foreach($userChecklists as $clid => $clName){
																echo '<option value="'.$clid.'">'.$clName.'</option>';
															}
															?>
														</select>
													</fieldset>
													<?php
												}
												?>
												<div id="addButtonDiv">
													<input type="hidden" name="recordenteredby" value="<?php echo $paramsArr['un']; ?>" />
													<input type="button" name="submitaddbutton" value="Add Record" onclick="this.disabled=true;this.form.submit();" style="width:150px;font-weight:bold;margin:10px;" />
													<input type="hidden" name="submitaction" value="Add Record" />
													<input type="hidden" name="qrycnt" value="<?php echo $qryCnt?$qryCnt:''; ?>" />
													<div style="margin-left:15px;font-weight:bold;">
														Follow-up Action:
													</div>
													<div style="margin-left:20px;">
														<input type="radio" name="gotomode" value="1" <?php echo ($goToMode==1?'CHECKED':''); ?> /> Go to New Record<br/>
														<input type="radio" name="gotomode" value="2" <?php echo ($goToMode==2?'CHECKED':''); ?> /> Go to New Record and Carryover Locality Information<br/>
														<input type="radio" name="gotomode" value="0" <?php echo (!$goToMode?'CHECKED':''); ?> /> Remain on Editing Page (add images, determinations, etc)
													</div>
												</div>

												<?php
											}
											?>
										</div>
										<div style="clear:both;">&nbsp;</div>
									</form>
								</div>
							</div>
						</td>
						<td id="imgtd" style="display:none;width:430px;" valign="top">
							<?php
							if($occId && ($fragArr || $specImgArr )){
								include_once('includes/imgprocessor.php');
							}
							?>
						</td></tr>
					</table>
					<?php
				}
			}
		}
		else{
			if($action == "Submit New Image"){
				echo '<div style="font-weight:bold;font-size:130%;">';
				echo 'ERROR: You may have tried to upload an image that was too large for the system. ';
				echo 'There is a 10MB limit set within the application, though there may be tighter restrictions set on the server (PHP configurations). ';
				echo 'Check with your server administrator to check on options for importing larger images. ';
				echo 'Use the back button to return to previous page and try to upload a smaller image </div>';
			}
			elseif(!$collId && !$occId){
				echo '<h2>ERROR: collection and occurrence identifiers are NULL</h2>';
			}
			elseif(!$isEditor){
				echo '<h2>ERROR: you are not authorized to add occurrence records</h2>';
			}
		}
		?>
	</div>
</body>
</html>
