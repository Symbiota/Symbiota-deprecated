<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
include_once($serverRoot.'/classes/ProfileManager.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$occId = array_key_exists('occid',$_REQUEST)?$_REQUEST['occid']:0;
$tabTarget = array_key_exists('tabtarget',$_REQUEST)?$_REQUEST['tabtarget']:0;
$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$autoDupeSearch = array_key_exists('autodupe',$_REQUEST)?$_REQUEST['autodupe']:0;
$goToMode = array_key_exists('gotomode',$_REQUEST)?$_REQUEST['gotomode']:0;
$autoPStatus = array_key_exists('autoprocessingstatus',$_POST)?$_POST['autoprocessingstatus']:'';
$occIndex = array_key_exists('occindex',$_REQUEST)&&$_REQUEST['occindex']!=""?$_REQUEST['occindex']:false;
$ouid = array_key_exists('ouid',$_REQUEST)?$_REQUEST['ouid']:0;
$crowdSourceMode = array_key_exists('csmode',$_REQUEST)?$_REQUEST['csmode']:0;
$action = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
if(!$action && array_key_exists('gotonew',$_REQUEST)){
	if(array_key_exists('carryloc',$_REQUEST)){
		$goToMode = 2;
	}
	else{
		$goToMode = 1;
	}
}

$occManager;
if(strpos($action,'Determination')){
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
	if(!$autoPStatus) $autoPStatus = 'pending review';
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

if($symbUid){
	//Set variables
	$occManager->setSymbUid($symbUid); 
	if($occId) $occManager->setOccId($occId); 
	if($collId) $occManager->setCollId($collId);
	$collMap = $occManager->getCollMap();
	if($occId && !$collId) $collId = $collMap['collid'];
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
		$isEditor = 1;
	}

	if($collMap['colltype']=='General Observations') $isGenObs = 1;
	if(!$isEditor){
		if($isGenObs){ 
			if(!$occId && array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"])){
				//Approved General Observation editors can add records
				$isEditor = 2;
			}
			elseif($action){
				//Lets assume that Edits where submitted and they remain on same specimen, user is still approved
				 $isEditor = 2;
			}
			elseif($occManager->getObserverUid() == $symbUid){
				//User can only edit their own records
				$isEditor = 2;
			}
		}
		elseif(array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"])){
			$isEditor = 2;
		}
	}
	$retainCurrentRec = 0;
	$resetCnt = 0;
	if($action == "Save Edits"){
		$statusStr = $occManager->editOccurrence($_POST,($crowdSourceMode?1:$isEditor));
	}
	if($isEditor || $crowdSourceMode){
		if($action == 'Save OCR'){
			$statusStr = $occManager->insertTextFragment($_REQUEST['imgid'],$_REQUEST['rawtext'],$_REQUEST['rawnotes']);
			if(is_numeric($statusStr)){
				$newPrlid = $statusStr;
				$statusStr = '';
			}
		}
		elseif($action == 'Save OCR Edits'){
			$statusStr = $occManager->saveTextFragment($_REQUEST['editprlid'],$_REQUEST['rawtext'],$_REQUEST['rawnotes']);
		}
		elseif($action == 'Delete OCR'){
			$statusStr = $occManager->deleteTextFragment($_REQUEST['delprlid']);
		}
	}
	if($isEditor){
		if($action == 'Add Record'){
			$statusStr = $occManager->addOccurrence($_REQUEST);
			if(strpos($statusStr,'SUCCESS') !== false){
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
		}
		elseif($action == 'Delete Occurrence'){
			$statusStr = $occManager->deleteOccurrence($occId);
			if(strpos($statusStr,'SUCCESS') !== false){
				$occId = 0;
				$occManager->setOccId(0);
			}
		}
		elseif($action == "Submit Image Edits"){
			$statusStr = $occManager->editImage($_REQUEST);
		}
		elseif($action == "Submit New Image"){
			$statusStr = $occManager->addImage($_REQUEST);
			$retainCurrentRec = 1;
		}
		elseif($action == "Delete Image"){
			$removeImg = (array_key_exists("removeimg",$_REQUEST)?$_REQUEST["removeimg"]:0);
			$statusStr = $occManager->deleteImage($_REQUEST["imgid"], $removeImg);
		}
		elseif($action == "Remap Image"){
			$statusStr = $occManager->remapImage($_REQUEST["imgid"], $_REQUEST["occid"]);
		}
		elseif($action == "Add New Determination"){
			$statusStr = $occManager->addDetermination($_REQUEST);
		}
		elseif($action == "Submit Determination Edits"){
			$statusStr = $occManager->editDetermination($_REQUEST);
		}
		elseif($action == "Delete Determination"){
			$statusStr = $occManager->deleteDetermination($_REQUEST['detid']);
		}
		elseif($action == "Make Determination Current"){
			$remapImages = array_key_exists('remapimages',$_REQUEST)?$_REQUEST['remapimages']:0;
			$statusStr = $occManager->makeDeterminationCurrent($_REQUEST['detid'],$remapImages);
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
				setCookie('editorquery','',time()-3600,($clientRoot?$clientRoot:'/'));
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
	elseif(isset($_COOKIE["editorquery"])){
		//Make sure query is null
		setCookie('editorquery','',time()-3600,($clientRoot?$clientRoot:'/'));
	}
	
	if(!$goToMode){
		$oArr = $occManager->getOccurMap();
		if($oArr){
			if(!$occId) $occId = $occManager->getOccId(); 
			$occArr = $oArr[$occId];
		}
	}
	elseif($goToMode == 2){
		$occArr = $occManager->carryOverValues($_REQUEST);
	}

	$navStr = '';
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
				$navStr .= '<a href="occurrenceeditor.php?gotomode=1&collid='.$collId.'" title="New Record">&gt;*</a>';
			}
			$navStr .= '</b>';
		}
	}
	
	//Images and other things needed for OCR
	$specImgArr = $occManager->getImageMap();
	if($specImgArr){
		$imgUrlPrefix = (isset($imageDomain)?$imageDomain:'');
		foreach($specImgArr as $imgId => $i2){
			$iUrl = ($i2['origurl']?$i2['origurl']:$i2['url']);
			if($imgUrlPrefix && substr($iUrl,0,4) != 'http') $iUrl = $imgUrlPrefix.$iUrl;
			$imgArr[$imgId] = $iUrl;
		}
		$fragArr = $occManager->getRawTextFragments();
	}
	
	$isLocked = false;
	if($occId) $isLocked = $occManager->getLock();
	
}
?>
<!DOCTYPE html>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Occurrence Editor</title>
    <?php 
    if($crowdSourceMode == 1){
		?>
		<link href="../../css/occureditorcrowdsource.css?ver=1302" type="text/css" rel="stylesheet" id="editorCssLink" /> 
		<?php 
    }
    else{
		?>
		<link href="../../css/occureditor.css?ver=1302" type="text/css" rel="stylesheet" id="editorCssLink" />
		<?php 
    }
    ?>
	<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
	<script src="../../js/jquery.imagetool-1.7.js" type="text/javascript"></script>
	<script type="text/javascript">
		var collId = "<?php echo $collId; ?>";
		var countryArr = new Array(<?php $occManager->echoCountryList();?>);
		var tabTarget = <?php echo $tabTarget; ?>;
		<?php
		if($imgArr){
			?>
			var activeImageArr = new Array("<?php echo implode('","',$imgArr); ?>");
			var activeImageKeys = new Array(<?php echo '"'.implode('","',array_keys($imgArr)).'"'; ?>);
			var activeImageIndex = 0; 
			<?php 
		}
		?>
	</script>
	<script type="text/javascript" src="../../js/symb/collections.occureditormain.js?ver=1302"></script>
	<script type="text/javascript" src="../../js/symb/collections.occureditortools.js?ver=1302"></script>
	<script type="text/javascript" src="../../js/symb/collections.occureditorimgtools.js?ver=1302"></script>
	<script type="text/javascript" src="../../js/symb/collections.occureditorshare.js?ver=1302"></script>
</head>
<body>
	<!-- inner text -->
	<div id="innertext">
		<?php 
		if(!$symbUid){
			?>
			<div style="font-weight:bold;font-size:120%;margin:30px;">
				Please 
				<a href="../../profile/index.php?refurl=<?php echo $clientRoot.'/collections/editor/occurrenceeditor.php?collid='.$collId; ?>">
					LOGIN
				</a> 
			</div>
			<?php 
		}
		else{
			if($collMap){
				?>
				<div id="titleDiv">
					<?php 
					echo $collMap['collectionname'].' ('.$collMap['institutioncode'].($collMap['collectioncode']?':'.$collMap['collectioncode']:'').')'; 
					if($isEditor || $crowdSourceMode){
						?>
						<div id="querySymbolDiv">
							<a href="#" title="Search / Filter" onclick="toggle('querydiv');document.getElementById('statusdiv').style.display = 'none';return false;"><img src="../../images/find.png" style="width:16px;" /></a>
						</div>
						
						<?php 
					}
					?> 
				</div>
				<?php 
			}
			if($occId || $crowdSourceMode || ($isEditor && $collId)){
				if(!$occArr && !$goToMode) $displayQuery = 1;
				if($crowdSourceMode){
					include 'includes/queryformcrowdsource.php';
				}
				else{
					include 'includes/queryform.php';
				}
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
							<a href="../../index.php">Home</a> &gt;&gt;
							<?php
							if($crowdSourceMode){
								?>
								<a href="crowdsourcecentral.php?">Crowd Sourcing Central</a> &gt;&gt;
								<?php
							}
							else{
								if(!$isGenObs || $isAdmin){ 
									?>
									<a href="../misc/collprofiles.php?collid=<?php echo $collId; ?>&emode=1">Collection Management</a> &gt;&gt;
									<?php
								}
								if($isGenObs){ 
									?>
									<a href="../../profile/viewprofile.php?tabindex=1">Personal Management</a> &gt;&gt;
									<?php
								}
							}
							?>
							<b>Editor</b>
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
											<a href="#occdiv"  style="margin:0px 20px 0px 20px;">
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
											$person = $pHandler->getPersonByUid($symbUid);
											$userEmail = ($person?$person->getEmail():'');
											
											$detVars = 'identby='.urlencode($occArr['identifiedby']).'&dateident='.urlencode($occArr['dateidentified']).'&sciname='.urlencode($occArr['sciname']).
												'&annotatorname='.urlencode($userDisplayName).'&annotatoremail='.urlencode($userEmail).
												($collMap['collectioncode']?'&collectioncode='.urlencode($collMap['collectioncode']):'').
												'&institutioncode='.urlencode($collMap['institutioncode']).'&catalognumber='.urlencode($occArr['catalognumber']);
											
											$imgVars = '&tid='.$occArr['tidinterpreted'].'&instcode='.urlencode($collMap['institutioncode']);
											?>
											<li id="detTab">
												<a href="includes/determinationtab.php?occid=<?php echo $occId.'&occindex='.$occIndex.'&'.$detVars; ?>" 
													style="margin:0px 20px 0px 20px;">Determination History</a>
											</li>
											<?php 
											if (isset($fpEnabled) && $fpEnabled) { // FP Annotations tab
												echo '<li>';
												echo '<a href="includes/findannotations.php?'.$detVars.'"';
												echo ' style="margin: 0px 20px 0px 20px;"> Annotations </a>';
												echo '</li>';
											} 
											?>
											<li id="imgTab">
												<a href="includes/imagetab.php?occid=<?php echo $occId.'&occindex='.$occIndex.'&em='.$isEditor.$imgVars; ?>" 
													style="margin:0px 20px 0px 20px;">Images</a>
											</li>
											<li id="adminTab">
												<a href="#admindiv" style="margin:0px 20px 0px 20px;">Admin</a>
											</li>
											<?php
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
														<div id="imgprocondiv" style="float:right;margin:-7px -4px 0px 0px;font-weight:bold;">
															<a href="#" onclick="toggleImageTd();return false;">&gt;&gt;</a>
														</div>
														<div id="imgprocoffdiv" style="float:right;margin:-7px -4px 0px 0px;font-weight:bold;display:none;">
															<a href="#" onclick="toggleImageTd();return false;">&lt;&lt;</a>
														</div>
														<?php
													}
												} 
												?>
												<div style="clear:both;">
													<div id="catalogNumberDiv">
														Catalog Number
														<a href="#" onclick="return openDoc('catalogNumber')"><img class="docimg" src="../../images/qmark.png" /></a>
														<br/>
														<input type="text" id="catalognumber" name="catalognumber" tabindex="2" maxlength="32" value="<?php echo array_key_exists('catalognumber',$occArr)?$occArr['catalognumber']:''; ?>" onchange="catalogNumberChanged(this.form)" <?php if(!$isEditor) echo 'disabled'; ?> />
													</div>
													<div id="otherCatalogNumbersDiv">
														Other Numbers
														<a href="#" onclick="return openDoc('otherCatalogNumbers')"><img class="docimg" src="../../images/qmark.png" /></a>
														<br/>
														<input type="text" name="othercatalognumbers" tabindex="4" maxlength="255" value="<?php echo array_key_exists('othercatalognumbers',$occArr)?$occArr['othercatalognumbers']:''; ?>" onchange="otherCatalogNumbersChanged(this.form);" />
													</div>
													<div id="recordedByDiv">
														Collector
														<a href="#" onclick="return openDoc('recordedBy')"><img class="docimg" src="../../images/qmark.png" /></a>
														<br/>
														<input type="text" name="recordedby" tabindex="6" maxlength="255" value="<?php echo array_key_exists('recordedby',$occArr)?$occArr['recordedby']:''; ?>" onchange="fieldChanged('recordedby');" />
													</div>
													<div id="recordNumberDiv">
														Number
														<a href="#" onclick="return openDoc('recordNumber')"><img class="docimg" src="../../images/qmark.png" /></a>
														<br/>
														<input type="text" name="recordnumber" tabindex="8" maxlength="45" value="<?php echo array_key_exists('recordnumber',$occArr)?$occArr['recordnumber']:''; ?>" onchange="recordNumberChanged(this);" />
													</div>
													<div id="eventDateDiv" title="Earliest Date Collected">
														Date
														<a href="#" onclick="return openDoc('eventDate')"><img class="docimg" src="../../images/qmark.png" /></a>
														<br/>
														<input type="text" name="eventdate" tabindex="10" value="<?php echo array_key_exists('eventdate',$occArr)?$occArr['eventdate']:''; ?>" onchange="eventDateChanged(this);" />
													</div>
													<?php
													if(!$isGenObs){ 
														?>
														<div id="dupesDiv">
															<input type="button" value="Dupes?" tabindex="12" onclick="searchDupesCollector(this.form);" /><br/>
															<input type="checkbox" name="autodupe" value="1" <?php echo ($autoDupeSearch?'checked':''); ?> />
															Auto search
														</div>
														<?php
													}
													?>
												</div>
												<div style="clear:both;height:40px;">
													<div id="associatedCollectorsDiv">
														Associated Collectors
														<a href="#" onclick="return openDoc('associatedCollectors')"><img class="docimg" src="../../images/qmark.png" /></a>
														<br/>
														<input type="text" name="associatedcollectors" tabindex="14" maxlength="255" value="<?php echo array_key_exists('associatedcollectors',$occArr)?$occArr['associatedcollectors']:''; ?>" onchange="fieldChanged('associatedcollectors');" />
													</div>
													<div id="dateToggleDiv">
														<a href="#" onclick="toggle('dateextradiv');return false;"><img src="../../images/editplus.png" style="width:15px;" /></a>
														<?php
														if(isset($activateExsiccati) && $activateExsiccati){ 
															?>
															<a href="#" style="margin:20px 0px 0px 5px;" onclick="toggle('exsdiv');return false;">
																<img src="../../images/exsiccatiadd.jpg" style="width:23px;" />
															</a>
															<?php
														}
														?>
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
													<div id="dupediv">
														<span id="dupesearch">Searching for Dupes...</span>
														<span id="dupenone" style="display:none;color:red;">No Dupes Found</span>
														<span id="dupedisplay" style="display:none;color:green;">Displaying Dupes</span>
													</div>
												</div>
												<div id="dateextradiv">
													<div id="verbatimEventDateDiv">
														Verbatim Date:
														<a href="#" onclick="return openDoc('verbatimEventDate')"><img class="docimg" src="../../images/qmark.png" /></a>
														<input type="text" name="verbatimeventdate" tabindex="19" maxlength="255" value="<?php echo array_key_exists('verbatimeventdate',$occArr)?$occArr['verbatimeventdate']:''; ?>" onchange="verbatimEventDateChanged(this)" />
													</div>
													<div id="ymdDiv">
														YYYY-MM-DD:
														<a href="#" onclick="return openDoc('year')"><img class="docimg" src="../../images/qmark.png" /></a>
														<input type="text" name="year" tabindex="20" value="<?php echo array_key_exists('year',$occArr)?$occArr['year']:''; ?>" onchange="inputIsNumeric(this, 'Year');fieldChanged('year');" title="Numeric Year" />-
														<input type="text" name="month" tabindex="21" value="<?php echo array_key_exists('month',$occArr)?$occArr['month']:''; ?>" onchange="inputIsNumeric(this, 'Month');fieldChanged('month');" title="Numeric Month" />-
														<input type="text" name="day" tabindex="22" value="<?php echo array_key_exists('day',$occArr)?$occArr['day']:''; ?>" onchange="inputIsNumeric(this, 'Day');fieldChanged('day');" title="Numeric Day" />
													</div>
													<div id="dayOfYearDiv">
														Day of Year:
														<a href="#" onclick="return openDoc('startDayOfYear')"><img class="docimg" src="../../images/qmark.png" /></a>
														<input type="text" name="startdayofyear" tabindex="24" value="<?php echo array_key_exists('startdayofyear',$occArr)?$occArr['startdayofyear']:''; ?>" onchange="inputIsNumeric(this, 'Start Day of Year');fieldChanged('startdayofyear');" title="Start Day of Year" /> -
														<input type="text" name="enddayofyear" tabindex="26" value="<?php echo array_key_exists('enddayofyear',$occArr)?$occArr['enddayofyear']:''; ?>" onchange="inputIsNumeric(this, 'End Day of Year');fieldChanged('enddayofyear');" title="End Day of Year" />
													</div>
												</div>
												<?php
												if(isset($activateExsiccati) && $activateExsiccati){ 
													?>
													<div id="exsdiv" style="display:none;">
														<iframe id="exsiframe" src="includes/exsiccatiframe.php?occid=<?php echo $occId; ?>" style="width:95%;height:100px;"></iframe>
													</div>
													<?php 
												}
												?>
											</fieldset>
											<fieldset>
												<legend><b>Latest Identification</b></legend>
												<div style="clear:both;">
													<div id="scinameDiv">
														Scientific Name:
														<a href="#" onclick="return openDoc('scientificName')"><img class="docimg" src="../../images/qmark.png" /></a>
														<br/>
														<input type="text" id="ffsciname" name="sciname" maxlength="250" tabindex="28" value="<?php echo array_key_exists('sciname',$occArr)?$occArr['sciname']:''; ?>" <?php echo ($isEditor?'':'disabled '); ?> />
														<input type="hidden" id="tidtoadd" name="tidtoadd" value="" />
													</div>
													<div id="scientificNameAuthorshipDiv">
														Author:
														<a href="#" onclick="return openDoc('scientificNameAuthorship')"><img class="docimg" src="../../images/qmark.png" /></a>
														<br/>
														<input type="text" name="scientificnameauthorship" maxlength="100" tabindex="0" value="<?php echo array_key_exists('scientificnameauthorship',$occArr)?$occArr['scientificnameauthorship']:''; ?>" onchange="fieldChanged('scientificnameauthorship');" <?php echo ($isEditor?'':'disabled '); ?> />
													</div>
													<?php 
													if(!$isEditor && $occArr){
														echo '<div style="clear:both;color:red;margin-left:5px;">Note: Full editing permissions are needed to edit an identification</div>';
													}
													?>
												</div>
												<div style="clear:both;padding:3px 0px 0px 10px;">
													<div id="identificationQualifierDiv">
														ID Qualifier:
														<a href="#" onclick="return openDoc('identificationQualifier')"><img class="docimg" src="../../images/qmark.png" /></a>
														<input type="text" name="identificationqualifier" tabindex="30" size="25" value="<?php echo array_key_exists('identificationqualifier',$occArr)?$occArr['identificationqualifier']:''; ?>" onchange="fieldChanged('identificationqualifier');" <?php echo ($isEditor?'':'disabled '); ?> />
													</div>
													<div  id="familyDiv">
														Family:
														<a href="#" onclick="return openDoc('family')"><img class="docimg" src="../../images/qmark.png" /></a>
														<input type="text" name="family" maxlength="50" tabindex="0" value="<?php echo array_key_exists('family',$occArr)?$occArr['family']:''; ?>" onchange="fieldChanged('family');" />
													</div>
												</div>
												<div style="clear:both;padding:3px 0px 0px 10px;margin-bottom:20px;">
													<div id="identifiedByDiv">
														Identified By:
														<a href="#" onclick="return openDoc('identifiedBy')"><img class="docimg" src="../../images/qmark.png" /></a>
														<input type="text" name="identifiedby" maxlength="255" tabindex="32" value="<?php echo array_key_exists('identifiedby',$occArr)?$occArr['identifiedby']:''; ?>" onchange="fieldChanged('identifiedby');" />
													</div>
													<div id="dateIdentifiedDiv">
														Date Identified:
														<a href="#" onclick="return openDoc('dateIdentified')"><img class="docimg" src="../../images/qmark.png" /></a>
														<input type="text" name="dateidentified" maxlength="45" tabindex="34" value="<?php echo array_key_exists('dateidentified',$occArr)?$occArr['dateidentified']:''; ?>" onchange="fieldChanged('dateidentified');" />
													</div>
													<div id="idrefToggleDiv" onclick="toggle('idrefdiv');">
														<img src="../../images/editplus.png" style="width:15px;" />
													</div>
												</div>
												<div  id="idrefdiv">
													<div id="identificationReferencesDiv">
														ID References:
														<a href="#" onclick="return openDoc('identificationReferences')"><img class="docimg" src="../../images/qmark.png" /></a>
														<input type="text" name="identificationreferences" tabindex="36" value="<?php echo array_key_exists('identificationreferences',$occArr)?$occArr['identificationreferences']:''; ?>" onchange="fieldChanged('identificationreferences');" />
													</div>
													<div id="identificationRemarksDiv">
														ID Remarks:
														<a href="#" onclick="return openDoc('identificationRemarks')"><img class="docimg" src="../../images/qmark.png" /></a>
														<input type="text" name="identificationremarks" tabindex="38" value="<?php echo array_key_exists('identificationremarks',$occArr)?$occArr['identificationremarks']:''; ?>" onchange="fieldChanged('identificationremarks');" />
													</div>
												</div>
											</fieldset>
											<fieldset>
												<legend><b>Locality</b></legend>
												<div style="clear:both;">
													<div id="countryDiv">
														Country
														<br/>
														<input type="text" id="ffcountry" name="country" tabindex="40" value="<?php echo array_key_exists('country',$occArr)?$occArr['country']:''; ?>" onchange="fieldChanged('country');" />
													</div>
													<div id="stateProvinceDiv">
														State/Province
														<br/>
														<input type="text" id="ffstate" name="stateprovince" tabindex="42" value="<?php echo array_key_exists('stateprovince',$occArr)?$occArr['stateprovince']:''; ?>" onchange="fieldChanged('stateprovince');" />
													</div>
													<div id="countyDiv">
														County
														<br/>
														<input type="text" id="ffcounty" name="county" tabindex="44" value="<?php echo array_key_exists('county',$occArr)?$occArr['county']:''; ?>" onchange="fieldChanged('county');" />
													</div>
													<div id="municipalityDiv">
														Municipality
														<br/>
														<input type="text" id="ffmunicipality" name="municipality" tabindex="45" value="<?php echo array_key_exists('municipality',$occArr)?$occArr['municipality']:''; ?>" onchange="fieldChanged('municipality');" />
													</div>
												</div>
												<div id="localityDiv">
													Locality:
													<br />
													<input type="text" name="locality" tabindex="46" value="<?php echo array_key_exists('locality',$occArr)?$occArr['locality']:''; ?>" onchange="fieldChanged('locality');" />
												</div>
												<div id="localSecurityDiv">
													<?php $hasValue = array_key_exists("localitysecurity",$occArr)&&$occArr["localitysecurity"]?1:0; ?>
													<input type="checkbox" name="localitysecurity" tabindex="0" value="1" <?php echo $hasValue?"CHECKED":""; ?> onchange="fieldChanged('localitysecurity');toggleLocSecReason(this.form);" title="Hide Locality Data from General Public" />
													Locality Security
													<span id="locsecreason" style="margin-left:40px;display:<?php echo ($hasValue?'inline':'none') ?>">
														<?php $lsrValue = array_key_exists('localitysecurityreason',$occArr)?$occArr['localitysecurityreason']:''; ?>
														Security Reason Override: <input type="text" name="localitysecurityreason" tabindex="0" onchange="fieldChanged('localitysecurityreason');" value="<?php echo $lsrValue; ?>" title="Leave blank for default rare, threatened, or sensitive status" />
													</span>
												</div>
												<div style="clear:both;">
													<div id="decimalLatitudeDiv">
														Latitude
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
														Longitude
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
														Uncertainty (meters)
														<a href="#" onclick="return openDoc('coordinateUncertaintyInMeters')"><img class="docimg" src="../../images/qmark.png" /></a>
														<br/>
														<input type="text" id="coordinateuncertaintyinmeters" name="coordinateuncertaintyinmeters" tabindex="54" maxlength="10" value="<?php echo array_key_exists('coordinateuncertaintyinmeters',$occArr)?$occArr['coordinateuncertaintyinmeters']:''; ?>" onchange="coordinateUncertaintyInMetersChanged(this.form);" title="Uncertainty in Meters" />
														<span style="cursor:pointer;padding:3px;" onclick="openMappingAid();" title="Google Maps">
															<img src="../../images/world40.gif" style="border:0px;width:13px;"  />
														</span>
														<a href="#" onclick="geoLocateLocality();"><img src="../../images/geolocate.gif" title="GeoLocate locality" style="width:18px;" /></a>
														<input type="button" value="Tools" onclick="toggleCoordDiv();" title="Other Coordinate Formats" />
													</div>
													<div id="geodeticDatumDiv">
														Datum
														<a href="#" onclick="return openDoc('geodeticDatum')"><img class="docimg" src="../../images/qmark.png" /></a>
														<br/>
														<input type="text" id="geodeticdatum" name="geodeticdatum" tabindex="56" maxlength="255" value="<?php echo array_key_exists('geodeticdatum',$occArr)?$occArr['geodeticdatum']:''; ?>" onchange="fieldChanged('geodeticdatum');" />
													</div>
													<div id="elevationDiv">
														Elevation in Meters
														<br/>
														<input type="text" name="minimumelevationinmeters" tabindex="58" maxlength="6" value="<?php echo array_key_exists('minimumelevationinmeters',$occArr)?$occArr['minimumelevationinmeters']:''; ?>" onchange="minimumElevationInMetersChanged(this.form);" title="Minumum Elevation In Meters" /> - 
														<input type="text" name="maximumelevationinmeters" tabindex="60" maxlength="6" value="<?php echo array_key_exists('maximumelevationinmeters',$occArr)?$occArr['maximumelevationinmeters']:''; ?>" onchange="maximumElevationInMetersChanged(this.form);" title="Maximum Elevation In Meters" />
													</div>
													<div style="float:left;margin:20px 2px 0px 2px">
														<a href="#" onclick="parseVerbatimElevation(document.fullform);return false">&lt;&lt;</a>
													</div>
													<div id="verbatimElevationDiv">
														Verbatim Elevation
														<br/>
														<input type="text" name="verbatimelevation" tabindex="62" maxlength="255" value="<?php echo array_key_exists('verbatimelevation',$occArr)?$occArr['verbatimelevation']:''; ?>" onchange="verbatimElevationChanged(this.form);" title="" />
													</div>
													<div style="float:left;margin:15px 5px;cursor:pointer;" onclick="toggle('locextradiv');">
														<img src="../../images/editplus.png" style="width:15px;" />
													</div>
												</div>
												<?php 
												include_once('includes/geotools.php');
												$locExtraDiv = "none";
												if(array_key_exists("verbatimcoordinates",$occArr) && $occArr["verbatimcoordinates"]){
													$locExtraDiv = "block";
												}
												elseif(array_key_exists("georeferencedby",$occArr) && $occArr["georeferencedby"]){
													$locExtraDiv = "block";
												}
												elseif(array_key_exists("footprintwkt",$occArr) && $occArr["footprintwkt"]){
													$locExtraDiv = "block";
												}
												elseif(array_key_exists("georeferenceprotocol",$occArr) && $occArr["georeferenceprotocol"]){
													$locExtraDiv = "block";
												}
												elseif(array_key_exists("georeferencesources",$occArr) && $occArr["georeferencesources"]){
													$locExtraDiv = "block";
												}
												elseif(array_key_exists("georeferenceverificationstatus",$occArr) && $occArr["georeferenceverificationstatus"]){
													$locExtraDiv = "block";
												}
												elseif(array_key_exists("georeferenceremarks",$occArr) && $occArr["georeferenceremarks"]){
													$locExtraDiv = "block";
												}
												?>
												<div id="locextradiv" style="position:relative;clear:both;display:<?php echo $locExtraDiv; ?>;">
													<div style="clear:both;">
														<div style="float:left;margin:20px 2px 0px 2px">
															<a href="#" onclick="parseVerbatimCoordinates(document.fullform);return false">&lt;&lt;</a>
														</div>
														<div id="verbatimCoordinatesDiv">
															Verbatim Coordinates
															<br/>
															<input type="text" name="verbatimcoordinates" tabindex="64" maxlength="255" value="<?php echo array_key_exists('verbatimcoordinates',$occArr)?$occArr['verbatimcoordinates']:''; ?>" onchange="verbatimCoordinatesChanged(this.form);" title="" />
														</div>
														<div id="georeferencedByDiv">
															Georeferenced By
															<br/>
															<input type="text" name="georeferencedby" tabindex="66" maxlength="255" value="<?php echo array_key_exists('georeferencedby',$occArr)?$occArr['georeferencedby']:''; ?>" onchange="fieldChanged('georeferencedby');" />
														</div>
														<div id="footprintWktDiv">
															footprint (polygon)
															<br/>
															<textarea name="footprintwkt" onchange="fieldChanged('footprintwkt');"><?php echo array_key_exists('footprintwkt',$occArr)?$occArr['footprintwkt']:''; ?></textarea>
														</div>
													</div>
													<div style="clear:both;">
														<div id="georeferenceProtocolDiv">
															Georeference Protocol
															<a href="#" onclick="return openDoc('georeferenceProtocol')"><img class="docimg" src="../../images/qmark.png" /></a>
															<br/>
															<input type="text" name="georeferenceprotocol" tabindex="68" maxlength="255" value="<?php echo array_key_exists('georeferenceprotocol',$occArr)?$occArr['georeferenceprotocol']:''; ?>" onchange="fieldChanged('georeferenceprotocol');" />
														</div>
														<div id="georeferenceSourcesDiv">
															Georeference Sources
															<a href="#" onclick="return openDoc('georeferenceSources')"><img class="docimg" src="../../images/qmark.png" /></a>
															<br/>
															<input type="text" name="georeferencesources" tabindex="70" maxlength="255" value="<?php echo array_key_exists('georeferencesources',$occArr)?$occArr['georeferencesources']:''; ?>" onchange="fieldChanged('georeferencesources');" />
														</div>
														<div id="georeferenceVerificationStatusDiv">
															Georef Verification Status
															<a href="#" onclick="return openDoc('georeferenceVerificationStatus')"><img class="docimg" src="../../images/qmark.png" /></a>
															<br/>
															<input type="text" name="georeferenceverificationstatus" tabindex="72" maxlength="32" value="<?php echo array_key_exists('georeferenceverificationstatus',$occArr)?$occArr['georeferenceverificationstatus']:''; ?>" onchange="fieldChanged('georeferenceverificationstatus');" />
														</div>
														<div id="georeferenceRemarksDiv">
															Georeference Remarks
															<br/>
															<input type="text" name="georeferenceremarks" tabindex="74" maxlength="255" value="<?php echo array_key_exists('georeferenceremarks',$occArr)?$occArr['georeferenceremarks']:''; ?>" onchange="fieldChanged('georeferenceremarks');" />
														</div>
													</div>
												</div>
											</fieldset>
											<fieldset>
												<legend><b>Misc</b></legend>
												<div id="habitatDiv">
													Habitat: 
													<input type="text" name="habitat" tabindex="82" value="<?php echo array_key_exists('habitat',$occArr)?$occArr['habitat']:''; ?>" onchange="fieldChanged('habitat');" />
												</div>
												<div id="substrateDiv">
													Substrate: 
													<input type="text" name="substrate" tabindex="82" maxlength="500" value="<?php echo array_key_exists('substrate',$occArr)?$occArr['substrate']:''; ?>" onchange="fieldChanged('substrate');" />
												</div>
												<div id="associatedTaxaDiv">
													Associated Taxa: 
													<textarea name="associatedtaxa" tabindex="84" onchange="fieldChanged('associatedtaxa');"><?php echo array_key_exists('associatedtaxa',$occArr)?$occArr['associatedtaxa']:''; ?></textarea> 
													<a href="#" onclick="openAssocSppAid();return false;">
														<img src="../../images/list.png" />
													</a>
												</div>
												<div id="verbatimAttributesDiv">
													Description: 
													<input type="text" name="verbatimattributes" tabindex="86" value="<?php echo array_key_exists('verbatimattributes',$occArr)?$occArr['verbatimattributes']:''; ?>" onchange="fieldChanged('verbatimattributes');" />
												</div>
												<div id="occurrenceRemarksDiv">
													Notes: 
													<input type="text" name="occurrenceremarks" tabindex="88" value="<?php echo array_key_exists('occurrenceremarks',$occArr)?$occArr['occurrenceremarks']:''; ?>" onchange="fieldChanged('occurrenceremarks');" title="Occurrence Remarks" />
													<span onclick="toggle('dynamicPropertiesDiv');">
														<img src="../../images/editplus.png" />
													</span>
												</div>
												<div id="dynamicPropertiesDiv">
													Dynamic Properties: 
													<a href="#" onclick="return openDoc('dynamicProperties')"><img class="docimg" src="../../images/qmark.png" /></a>
													<input type="text" name="dynamicproperties" tabindex="89" value="<?php echo array_key_exists('dynamicproperties',$occArr)?$occArr['dynamicproperties']:''; ?>" onchange="fieldChanged('dynamicproperties');" />
												</div>
												<div style="padding:2px;">
													<div id="lifeStageDiv">
														Life Stage
														<a href="#" onclick="return openDoc('lifeStage')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
														<input type="text" name="lifestage" tabindex="90" maxlength="45" value="<?php echo array_key_exists('lifestage',$occArr)?$occArr['lifestage']:''; ?>" onchange="fieldChanged('lifestage');" />
													</div>
													<div id="sexDiv">
														Sex
														<a href="#" onclick="return openDoc('sex')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
														<input type="text" name="sex" tabindex="92" maxlength="45" value="<?php echo array_key_exists('sex',$occArr)?$occArr['sex']:''; ?>" onchange="fieldChanged('sex');" />
													</div>
													<div id="individualCountDiv">
														Individual Count
														<a href="#" onclick="return openDoc('individualCount')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
														<input type="text" name="individualcount" tabindex="94" maxlength="45" value="<?php echo array_key_exists('individualcount',$occArr)?$occArr['individualcount']:''; ?>" onchange="fieldChanged('individualcount');" />
													</div>
													<div id="samplingProtocolDiv">
														Sampling Protocol
														<a href="#" onclick="return openDoc('samplingProtocol')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
														<input type="text" name="samplingprotocol" tabindex="95" maxlength="100" value="<?php echo array_key_exists('samplingprotocol',$occArr)?$occArr['samplingprotocol']:''; ?>" onchange="fieldChanged('samplingprotocol');" />
													</div>
													<div id="preparationsDiv">
														Preparations
														<a href="#" onclick="return openDoc('preparations')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
														<input type="text" name="preparations" tabindex="97" maxlength="100" value="<?php echo array_key_exists('preparations',$occArr)?$occArr['preparations']:''; ?>" onchange="fieldChanged('preparations');" />
													</div>
													<div id="reproductiveConditionDiv">
														Phenology
														<a href="#" onclick="return openDoc('reproductiveCondition')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
														<?php
														if(isset($reproductiveConditionTerms)){
															if($reproductiveConditionTerms){
																?>
																<select name="reproductivecondition" onchange="fieldChanged('reproductivecondition');" tabindex="99" />
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
														Establishment Means
														<a href="#" onclick="return openDoc('establishmentMeans')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
														<input type="text" name="establishmentmeans" tabindex="100" maxlength="32" value="<?php echo array_key_exists('establishmentmeans',$occArr)?$occArr['establishmentmeans']:''; ?>" onchange="fieldChanged('establishmentmeans');" />
													</div>
													<div id="cultivationStatusDiv">
														<?php $hasValue = array_key_exists("cultivationstatus",$occArr)&&$occArr["cultivationstatus"]?1:0; ?>
														<input type="checkbox" name="cultivationstatus" tabindex="102" value="1" <?php echo $hasValue?'CHECKED':''; ?> onchange="fieldChanged('cultivationstatus');" />
														Cultivated
													</div>
												</div>
											</fieldset>
											<fieldset>
												<legend><b>Curation</b></legend>
												<div style="padding:3px;">
													<div id="typeStatusDiv">
														Type Status
														<a href="#" onclick="return openDoc('typeStatus')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
														<input type="text" name="typestatus" tabindex="103" maxlength="255" value="<?php echo array_key_exists('typestatus',$occArr)?$occArr['typestatus']:''; ?>" onchange="fieldChanged('typestatus');" />
													</div>
													<div id="dispositionDiv">
														Disposition
														<a href="#" onclick="return openDoc('disposition')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
														<input type="text" name="disposition" tabindex="104" maxlength="32" value="<?php echo array_key_exists('disposition',$occArr)?$occArr['disposition']:''; ?>" onchange="fieldChanged('disposition');" />
													</div>
													<div id="occurrenceIdDiv" title="If different than institution code">
														Occurrence ID
														<a href="#" onclick="return openDoc('occurrenceid')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
														<input type="text" name="occurrenceid" tabindex="105" maxlength="255" value="<?php echo array_key_exists('occurrenceid',$occArr)?$occArr['occurrenceid']:''; ?>" onchange="fieldChanged('occurrenceid');" />
													</div>
													<div id="fieldNumberDiv" title="If different than institution code">
														Field Number
														<a href="#" onclick="return openDoc('fieldnumber')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
														<input type="text" name="fieldnumber" tabindex="107" maxlength="45" value="<?php echo array_key_exists('fieldnumber',$occArr)?$occArr['fieldnumber']:''; ?>" onchange="fieldChanged('fieldnumber');" />
													</div>
													<div id="ownerInstitutionCodeDiv" title="If different than institution code">
														Owner Code
														<a href="#" onclick="return openDoc('ownerInstitutionCode')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
														<input type="text" name="ownerinstitutioncode" tabindex="108" maxlength="32" value="<?php echo array_key_exists('ownerinstitutioncode',$occArr)?$occArr['ownerinstitutioncode']:''; ?>" onchange="fieldChanged('ownerinstitutioncode');" />
													</div>
													<div id="basisOfRecordDiv">
														Basis of Record
														<a href="#" onclick="return openDoc('basisOfRecord')"><img class="docimg" src="../../images/qmark.png" /></a><br/>
														<input type="text" name="basisofrecord" tabindex="109" maxlength="32" value="<?php echo array_key_exists('basisofrecord',$occArr)?$occArr['basisofrecord']:($collMap['colltype']=='Preserved Specimens'?'PreservedSpecimen':'Observation'); ?>" onchange="fieldChanged('basisofrecord');" />
													</div>
													<div id="languageDiv">
														Language<br/>
														<input type="text" name="language" tabindex="111" maxlength="20" value="<?php echo array_key_exists('language',$occArr)?$occArr['language']:''; ?>" onchange="fieldChanged('language');" />
													</div>
													<div id="labelProjectDiv">
														Label Project<br/>
														<input type="text" name="labelproject" tabindex="112" maxlength="45" value="<?php echo array_key_exists('labelproject',$occArr)?$occArr['labelproject']:''; ?>" onchange="fieldChanged('labelproject');" />
													</div>
													<div id="duplicateQuantityDiv" title="aka label quantity">
														Dupe Count: 
														<input type="text" name="duplicatequantity" tabindex="116" value="<?php echo array_key_exists('duplicatequantity',$occArr)?$occArr['duplicatequantity']:''; ?>" onchange="fieldChanged('duplicatequantity');" />
													</div>
													<div id="processingStatusDiv">
														Processing Status<br/>
														<?php 
															$pStatus = array_key_exists('processingstatus',$occArr)?$occArr['processingstatus']:'';
														?>
														<select name="processingstatus" tabindex="120" onchange="fieldChanged('processingstatus');">
															<option value=''>No Set Status</option>
															<option value=''>-------------------</option>
															<option value='unprocessed' <?php echo ($pStatus=='unprocessed'?'SELECTED':''); ?>>
																Unprocessed
															</option>
															<option value='unprocessed/OCR' <?php echo ($pStatus=='unprocessed/OCR'?'SELECTED':''); ?>>
																Unprocessed/OCR
															</option>
															<option value='unprocessed/NLP' <?php echo ($pStatus=='unprocessed/NLP'?'SELECTED':''); ?>>
																Unprocessed/NLP
															</option>
															<option value='stage 1' <?php echo ($pStatus=='stage 1'?'SELECTED':''); ?>>
																Stage 1
															</option>
															<option value='stage 2' <?php echo ($pStatus=='stage 2'?'SELECTED':''); ?>>
																Stage 2
															</option>
															<option value='stage 3' <?php echo ($pStatus=='stage 3'?'SELECTED':''); ?>>
																Stage 3
															</option>
															<option value='pending duplicate' <?php echo ($pStatus=='pending duplicate'?'SELECTED':''); ?>>
																Pending Duplicate
															</option>
															<option value='pending review' <?php echo (!$occId || $pStatus=='pending review'?'SELECTED':''); ?>>
																Pending Review
															</option>
															<option value='expert required' <?php echo ($pStatus=='expert required'?'SELECTED':''); ?>>
																Expert Required
															</option>
															<?php
															if($isEditor){
																//Don't display these options is editor is crowd sourced 
																?>
																<option value='reviewed' <?php echo ($pStatus=='reviewed'?'SELECTED':''); ?>>
																	Reviewed
																</option>
																<option value='closed' <?php echo ($pStatus=='closed'?'SELECTED':''); ?>>
																	Closed
																</option>
																<?php 
															}
															?>
														</select>
													</div>
												</div>
												<div id="pkDiv">
													<hr/>
													<div style="float:left;" title="Internal occurrence record Primary Key">
														<?php if($occId) echo 'Primary Key: '.$occId; ?>
													</div>
													<div style="float:left;margin-left:90px;">
														<?php if(array_key_exists('datelastmodified',$occArr)) echo 'Date Last Modified: '.$occArr['datelastmodified']; ?>
													</div>
													<div style="float:left;margin-left:90px;">
														<?php if(array_key_exists('recordenteredby',$occArr)) echo 'Entered By: '.$occArr['recordenteredby']; ?>
													</div>
												</div>
											</fieldset>
											<?php 
											if($navStr){
												echo '<div style="float:right;margin-right:20px;">'.$navStr.'</div>'."\n";
											}
											?>
											<div style="padding:10px;clear:both;">
												<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
												<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
												<input type="hidden" name="userid" value="<?php echo $paramsArr['un']; ?>" />
												<input type="hidden" name="observeruid" value="<?php echo $symbUid; ?>" />
												<input type="hidden" name="csmode" value="<?php echo $crowdSourceMode; ?>" />
												<?php 
												if($occId){ 
													?>
													<div style="margin:15px 30px;float:left;">
														<input type="submit" name="submitaction" value="Save Edits" style="width:150px;" onclick="return verifyFullFormEdits(this.form)" disabled />
														<br/>
														<?php 
														if($isEditor){
															?>
															Status Auto-Set:
															<select name="autoprocessingstatus">
																<option value=''>Not Activated</option>
																<option value=''>-------------------</option>
																<option value='unprocessed' <?php echo ($autoPStatus=='unprocessed'?'SELECTED':''); ?>>
																	Unprocessed
																</option>
																<option value='unprocessed/OCR' <?php echo ($autoPStatus=='unprocessed/OCR'?'SELECTED':''); ?>>
																	Unprocessed/OCR
																</option>
																<option value='unprocessed/NLP' <?php echo ($autoPStatus=='unprocessed/NLP'?'SELECTED':''); ?>>
																	Unprocessed/NLP
																</option>
																<option value='stage 1' <?php echo ($autoPStatus=='stage 1'?'SELECTED':''); ?>>
																	Stage 1
																</option>
																<option value='stage 2' <?php echo ($autoPStatus=='stage 2'?'SELECTED':''); ?>>
																	Stage 2
																</option>
																<option value='stage 3' <?php echo ($autoPStatus=='stage 3'?'SELECTED':''); ?>>
																	Stage 3
																</option>
																<option value='pending duplicate' <?php echo ($autoPStatus=='pending duplicate'?'SELECTED':''); ?>>
																	Pending Duplicate
																</option>
																<option value='pending review' <?php echo ($autoPStatus=='pending review'?'SELECTED':''); ?>>
																	Pending Review
																</option>
																<option value='expert required' <?php echo ($autoPStatus=='expert required'?'SELECTED':''); ?>>
																	Expert Required
																</option>
																<?php
																if($isEditor){
																	//Don't display these options is editor is crowd sourced 
																	?>
																	<option value='reviewed' <?php echo ($autoPStatus=='reviewed'?'SELECTED':''); ?>>
																		Reviewed
																	</option>
																	<option value='closed' <?php echo ($autoPStatus=='closed'?'SELECTED':''); ?>>
																		Closed
																	</option>
																	<?php
																} 
																?>
															</select>
															<br/>
															<?php
														}
														?>
														<input type="hidden" name="editedfields" value="" />
														<?php 
														if($occIndex !== false){
															?>
															<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
															<?php 
														}
														?>
													</div>
													<?php
													if($isEditor && !$crowdSourceMode){ 
														?>
														<div style="float:left;margin-left:200px;">
															<fieldset style="padding:15px;background-color:lightyellow;">
																<legend><b>Options</b></legend>
																<input type="submit" name="gotonew" value="Go to New Occurrence Record" onclick="return verifyGotoNew(this.form);" /><br/>
																<input type="checkbox" name="carryloc" value="1" /> Carry over locality values
															</fieldset>
														</div>
														<?php
													} 
												}
												else{ 
													?>
													<div style="width:450px;border:1px solid black;background-color:lightyellow;padding:10px;margin:20px;">
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
									<?php
									if($occId && $isEditor){
										?>
										<div id="admindiv">
											<form name="deleteform" method="post" action="occurrenceeditor.php" onsubmit="return confirm('Are you sure you want to delete this record?')">
												<fieldset>
													<legend>Delete Occurrence Record</legend>
													<div style="margin:15px">
														Record first needs to be evaluated before it can be deleted from the system. 
														The evaluation ensures that the deletion of this record will not interfer with 
														the integrity of other linked data. Note that all determination and 
														comments for this occurrence will be automatically deleted. Links to images, checklist vouchers, 
														and surveys will have to be individually addressed before can be deleted.      
														<div style="margin:15px;display:block;">
															<input name="verifydelete" type="button" value="Evaluate record for deletion" onclick="verifyDeletion(this.form);" />
														</div>
														<div id="delverimgdiv" style="margin:15px;">
															<b>Image Links: </b>
															<span id="delverimgspan" style="color:orange;display:none;">checking image links...</span>
															<div id="delimgfailspan" style="display:none;style:0px 10px 10px 10px;">
																<span style="color:red;">Warning:</span> 
																One or more images are linked to this occurrence. 
																Before this specimen can be deleted, images have to be deleted or disassociated 
																with this occurrence record. Continuing will remove associations to 
																the occurrence record being deleted but leave image in system linked only to the scientific name.  
															</div>
															<div id="delimgappdiv" style="display:none;">
																<span style="color:green;">Approved for deletion.</span>
																No images are directly associated with this occurrence record.  
															</div>
														</div>
														<div id="delvervoucherdiv" style="margin:15px;">
															<b>Checklist Voucher Links: </b>
															<span id="delvervouspan" style="color:orange;display:none;">checking checklist links...</span>
															<div id="delvouappdiv" style="display:none;">
																<span style="color:green;">Approved for deletion.</span>
																No checklists have been linked to this occurrence record. 
															</div>
															<div id="delvoulistdiv" style="display:none;style:0px 10px 10px 10px;">
																<span style="color:red;">Warning:</span> 
																This occurrence serves as an occurrence voucher for the following species checklists.
																Deleting this occurrence will remove these association. 
																You may want to first verify this action with the checklist administrators.
																<ul id="voucherlist">
																</ul> 
															</div>
														</div>
														<div id="delversurveydiv" style="margin:15px;">
															<b>Survey Voucher Links: </b>
															<span id="delversurspan" style="color:orange;display:none;">checking survey links...</span>
															<div id="delsurappdiv" style="display:none;">
																<span style="color:green;">Approved for deletion.</span>
																No survey projects have been linked to this occurrence record. 
															</div>
															<div id="delsurlistdiv" style="display:none;style:0px 10px 10px 10px;">
																<span style="color:red;">Warning:</span> 
																This occurrence serves as an occurrence voucher for the following survey projects.
																Deleting this occurrence will remove these association. 
																You may want to first verify this action with the project administrators.
																<ul id="surveylist">
																</ul> 
															</div>
														</div>
														<div id="delapprovediv" style="margin:15px;display:none;">
															<input name="occid" type="hidden" value="<?php echo $occId; ?>" />
															<input name="occindex" type="hidden" value="<?php echo $occIndex; ?>" />
															<input name="collid" type="hidden" value="<?php echo $collId; ?>" />
															<input name="submitaction" type="submit" value="Delete Occurrence" />
														</div>
													</div>
												</fieldset>
											</form>
										</div>
										<?php
									}
									?>
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
				if(!$isEditor){
					echo '<h2>You are not authorized to add occurrence records</h2>';
				}
			}
		}
		?>
	</div>
<?php 	
//include($serverRoot.'/footer.php');
?>

</body>
</html>