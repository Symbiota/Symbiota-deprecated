<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php'); 
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$occId = array_key_exists('occid',$_REQUEST)?$_REQUEST['occid']:0;
$tabTarget = array_key_exists('tabtarget',$_REQUEST)?$_REQUEST['tabtarget']:0;
$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$goToMode = array_key_exists('gotomode',$_REQUEST)?$_REQUEST['gotomode']:0;
$autoPStatus = array_key_exists('autoprocessingstatus',$_POST)?$_POST['autoprocessingstatus']:'';
$occIndex = array_key_exists('occindex',$_REQUEST)&&$_REQUEST['occindex']!=""?$_REQUEST['occindex']:false;
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

$isEditor = 0;		//If not editor, edits will be submitted to omoccuredits table but not applied to omoccurrences 
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

	$isGenObs = ($collMap['colltype']=='General Observations'?1:0);
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
		elseif($action == "Save Edits"){
			$statusStr = $occManager->editOccurrence($_POST,$isEditor);
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
			$occManager->setSqlWhere(0,($isEditor==1?1:0));
			$qryCnt = $occManager->getQueryRecordCount();
			$occIndex = $qryCnt;
		}
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
				$occManager->setSqlWhere($occIndex,($isEditor==1?1:0));
			}
			else{
				setCookie('editorquery','',time()-3600,($clientRoot?$clientRoot:'/'));
				$occIndex = false;
			}
		}
		elseif($action == 'Save Edits'){
			$occManager->setSqlWhere(0,($isEditor==1?1:0));
			//Get query count and then reset; don't use new count for this display
			$qryCnt = $occManager->getQueryRecordCount();
			$occManager->getQueryRecordCount(1);
		}
		else{
			$occManager->setSqlWhere($occIndex,($isEditor==1?1:0));
			$qryCnt = $occManager->getQueryRecordCount();
		}
	}
	elseif(isset($_COOKIE["editorquery"])){
		//Make sure query is null
		setCookie('editorquery','',time()-3600,($clientRoot?$clientRoot:'/'));
	}
	
	if(!$goToMode){
		$occArr = $occManager->getOccurMap();
		if(!$occId) $occId = $occManager->getOccId(); 
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
			if($occIndex<$qryCnt-1) $navStr .= '<a href="#" onclick="return submitQueryForm('.($occIndex+($action=="Save Edits"?0:1)).');"  title="Next Record">';
			$navStr .= '&gt;&gt;';
			if($occIndex<$qryCnt-1) $navStr .= '</a>';
			$navStr .= '&nbsp;&nbsp;&nbsp;&nbsp;';
			if($occIndex<$qryCnt-1) $navStr .= '<a href="#" onclick="return submitQueryForm('.($qryCnt-1).');" title="Last Record">';
			$navStr .= '&gt;|';
			if($occIndex<$qryCnt-1) $navStr .= '</a> ';
			$navStr .= '&nbsp;&nbsp;&nbsp;&nbsp;';
			$navStr .= '<a href="occurrenceeditor.php?gotomode=1&collid='.$collId.'" title="New Record">&gt;*</a>';
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
	
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Occurrence Editor</title>
    <link type="text/css" href="../../css/main.css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />
	<style type="text/css">
		img.dwcimg {border:0px;width:9px;margin-bottom:2px;}
	</style>
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
	<script type="text/javascript" src="../../js/symb/collections.occureditormain.js"></script>
	<script type="text/javascript" src="../../js/symb/collections.occureditortools.js"></script>
	<script type="text/javascript" src="../../js/symb/collections.occureditorimgtools.js"></script>
</head>
<body>
	<!-- inner text -->
	<div id="innertext">
		<?php 
		if(!$symbUid){
			?>
			Please <a href="../../profile/index.php?refurl=<?php echo $_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']; ?>">LOGIN</a> to edit or add an occurrence record 
			<?php 
		}
		else{
			echo '<div style="width:790px;">';
			if($isEditor){
				?>
				<div style="float:right;">
					<div style="cursor:pointer;" onclick="toggle('querydiv');document.getElementById('statusdiv').style.display = 'none';">
						Search / Filter
					</div>
				</div>
				<?php
			}
			if($collMap){
				echo '<h2>'.$collMap['collectionname'].' ('.$collMap['institutioncode'].($collMap['collectioncode']?':'.$collMap['collectioncode']:'').')</h2>';
			}
			echo '</div>';
			if($statusStr){
				?>
				<div id="statusdiv" style="margin:5px 0px 5px 15px;">
					<b>Action Status: </b>
					<span style="color:red;"><?php echo $statusStr; ?></span>
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
			if($occId || ($isEditor && $collId)){
				$qIdentifier=''; $qRecordedBy=''; $qRecordNumber=''; $qEnteredBy=''; $qProcessingStatus=''; $qDateLastModified='';
				$qryArr = $occManager->getQueryVariables();
				if($qryArr){
					$qIdentifier = (array_key_exists('id',$qryArr)?$qryArr['id']:'');
					$qRecordedBy = (array_key_exists('rb',$qryArr)?$qryArr['rb']:'');
					$qRecordNumber = (array_key_exists('rn',$qryArr)?$qryArr['rn']:'');
					$qEnteredBy = (array_key_exists('eb',$qryArr)?$qryArr['eb']:'');
					$qProcessingStatus = (array_key_exists('ps',$qryArr)?$qryArr['ps']:'');
					$qDateLastModified = (array_key_exists('dm',$qryArr)?$qryArr['dm']:'');
				}
				?>
				<div id="querydiv" style="width:790px;display:<?php echo (!$occArr&&!$goToMode?'block':'none'); ?>;">
					<form name="queryform" action="occurrenceeditor.php" method="post" onsubmit="return verifyQueryForm(this)">
						<fieldset style="padding:5px;">
							<legend><b>Record Search Form</b></legend>
							<div style="margin:2px;">
								<span title="Full name of collector as entered in database. To search just on last name, place the wildcard character (%) before name (%Gentry).">
									Collector: 
									<input type="text" name="q_recordedby" value="<?php echo $qRecordedBy; ?>" />
								</span>
								<span style="margin-left:25px;">Number:</span>
								<span title="Separate multiple terms by comma and ranges by ' - ' (space before and after dash requiered), e.g.: 3542,3602,3700 - 3750">
									<input type="text" name="q_recordnumber" value="<?php echo $qRecordNumber; ?>" style="width:120px;" />
								</span>
								<span style="margin-left:30px;">Identifier:</span> 
								<span title="Separate multiples by comma and ranges by ' - ' (space before and after dash requiered), e.g.: 3542,3602,3700 - 3750">
									<input type="text" name="q_identifier" value="<?php echo $qIdentifier; ?>" />
								</span>
							</div>
							<div style="margin:2px;">
								Entered by: 
								<input type="text" name="q_enteredby" value="<?php echo $qEnteredBy; ?>" />
								<span style="margin-left:15px;" title="Enter ranges separated by ' - ' (space before and after dash requiered), e.g.: 2002-01-01 - 2003-01-01">
									Date entered: 
									<input type="text" name="q_datelastmodified" value="<?php echo $qDateLastModified; ?>" style="width:160px" />
								</span>
								<span style="margin-left:15px;">Status:</span> 
								<select name="q_processingstatus">
									<option value=''>All Records</option>
									<option>-------------------</option>
									<option value="unprocessed" <?php echo ($qProcessingStatus=='unprocessed'?'SELECTED':''); ?>>
										Unprocessed
									</option>
									<option value="unprocessed/OCR" <?php echo ($qProcessingStatus=='unprocessed/OCR'?'SELECTED':''); ?>>
										Unprocessed/OCR 
									</option>
									<option  value="unprocessed/NLP" <?php echo ($qProcessingStatus=='unprocessed/NLP'?'SELECTED':''); ?>>
										Unprocessed/NLP
									</option>
									<option value="stage 1" <?php echo ($qProcessingStatus=='stage 1'?'SELECTED':''); ?>>
										Stage 1
									</option>
									<option value="stage 2" <?php echo ($qProcessingStatus=='stage 2'?'SELECTED':''); ?>>
										Stage 2
									</option>
									<option value="stage 3" <?php echo ($qProcessingStatus=='stage 3'?'SELECTED':''); ?>>
										Stage 3
									</option>
									<option value="pending duplicate" <?php echo ($qProcessingStatus=='pending duplicate'?'SELECTED':''); ?>>
										Pending Duplicate
									</option>
									<option value="pending review" <?php echo ($qProcessingStatus=='pending review'?'SELECTED':''); ?>>
										Pending Review
									</option>
									<option value="expert requiered" <?php echo ($qProcessingStatus=='expert required'?'SELECTED':''); ?>>
										Expert Requiered
									</option>
									<option value="reviewed" <?php echo ($qProcessingStatus=='reviewed'?'SELECTED':''); ?>>
										Reviewed
									</option>
								</select>
							</div>
							<?php 
							$qryStr = '';
							if($qRecordedBy) $qryStr .= '&recordedby='.$qRecordedBy;
							if($qRecordNumber) $qryStr .= '&recordnumber='.$qRecordNumber;
							if($qIdentifier) $qryStr .= '&identifier='.$qIdentifier;
							if($qEnteredBy) $qryStr .= '&recordenteredby='.$qEnteredBy;
							if($qDateLastModified) $qryStr .= '&datelastmodified='.$qDateLastModified;
							if($qryStr){
								?>
								<div style="float:right;margin-top:10px;" title="Go to Label Printing Module">
									<a href="../datasets/index.php?collid=<?php echo $collId.$qryStr; ?>">
										<img src="../../images/list.png" style="width:15px;" />
									</a>
								</div>
								<?php 
							}
							?>
							<div style="margin:5px;">
								<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
								<input type="hidden" name="occindex" value="0" />
								<input type="submit" name="submitaction" value="Query Records" />
								<input type="hidden" name="autoprocessingstatus" value="<?php echo $autoPStatus; ?>" />
								<span style="margin-left:10px;">
									<input type="button" name="reset" value="Reset Form" onclick="resetQueryForm(this.form)" /> 
								</span>
							</div>
						</fieldset>
					</form>
				</div>
				<div style="width:790px;">
					<span class='navpath'>
						<a href="../../index.php">Home</a> &gt;&gt;
						<a href="../misc/collprofiles.php?collid=<?php echo $collId; ?>&emode=1">Collection Management Panel</a> &gt;&gt;
						<b>Editor</b>
					</span>
					<?php
					if($navStr){
						?>
						<span style="float:right;margin-right:30px;">
							<?php echo $navStr; ?>
						</span>
						<?php 
					}
					?>
				</div>
				<?php 
				if($occArr || $goToMode == 1 || $goToMode == 2){		//$action == 'gotonew'
					?>
					<table id="edittable" style="">
						<tr><td id="editortd" style="width:785px;" valign="top">
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
										$detVars = '&identby='.$occArr['identifiedby'].'&dateident='.$occArr['dateidentified'].'&sciname='.$occArr['sciname'];
										$imgVars = '&tid='.$occArr['tidinterpreted'].'&instcode='.$collMap['institutioncode'];
										?>
										<li>
											<a href="includes/determinationtab.php?occid=<?php echo $occId.'&occindex='.$occIndex.$detVars; ?>" style="margin:0px 20px 0px 20px;">
												Determination History
											</a>
										</li>
										<li>
											<a href="includes/imagetab.php?occid=<?php echo $occId.'&occindex='.$occIndex.$imgVars; ?>" style="margin:0px 20px 0px 20px;">
												Images
											</a>
										</li>
										<li>
											<a href="#admindiv" style="margin:0px 20px 0px 20px;">
												Admin
											</a>
										</li>
										<?php
									}
									?>
								</ul>
								<div id="occdiv" style="position:relative;">
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
											<div>
												<span style="margin-left:2px;">
													Catalog Number
													<a href="#" onclick="return dwcDoc('catalogNumber')">
														<img class="dwcimg" src="../../images/qmark.png" />
													</a>
												</span>
												<span style="margin-left:3px;">
													Occurrence ID
													<a href="#" onclick="return dwcDoc('occurrenceID')">
														<img class="dwcimg" src="../../images/qmark.png" />
													</a>
												</span>
												<span style="margin-left:18px;">
													Collector
												</span>
												<span style="margin-left:184px;">
													Number
												</span>
												<span style="margin-left:25px;">
													Date
												</span>
											</div>
											<div>
												<span>
													<input type="text" name="catalognumber" tabindex="2" maxlength="32" style="width:100px;" value="<?php echo array_key_exists('catalognumber',$occArr)?$occArr['catalognumber']:''; ?>" onchange="catalogNumberChanged(this.value)" />
												</span>
												<span>
													<input type="text" name="occurrenceid" tabindex="4" maxlength="255" style="width:110px;" value="<?php echo array_key_exists('occurrenceid',$occArr)?$occArr['occurrenceid']:''; ?>" onchange="occurrenceIdChanged(this.value);" title="Global Unique Identifier (GUID)" />
												</span>
												<span>
													<input type="text" name="recordedby" tabindex="6" maxlength="255" style="width:220px;background-color:lightyellow;" value="<?php echo array_key_exists('recordedby',$occArr)?$occArr['recordedby']:''; ?>" onchange="fieldChanged('recordedby');" />
												</span>
												<span style="margin-left:10px;">
													<input type="text" name="recordnumber" tabindex="8" maxlength="45" style="width:60px;" value="<?php echo array_key_exists('recordnumber',$occArr)?$occArr['recordnumber']:''; ?>" onchange="fieldChanged('recordnumber');" />
												</span>
												<span style="margin-left:10px;" title="Earliest Date Collected">
													<input type="text" name="eventdate" tabindex="10" style="width:110px;" value="<?php echo array_key_exists('eventdate',$occArr)?$occArr['eventdate']:''; ?>" onchange="eventDateModified(this);" />
												</span>
												<span style="margin-left:5px;cursor:pointer;" onclick="">
													<input type="button" value="Dupes" tabindex="12" onclick="lookForDupes(this.form);" />
												</span>
											</div>
											<div style="margin-top:5px;">
												<span>
													Associated Collectors
												</span>
												<span style="margin-left:226px;">
													Other Catalog Numbers
													<a href="#" onclick="return dwcDoc('otherCatalogNumbers')">
														<img class="dwcimg" src="../../images/qmark.png" />
													</a>
												</span>
												<div id="dupespan" style="display:none;float:right;width:150px;border:2px outset blue;background-color:#FFFFFF;padding:3px;font-weight:bold;">
													<span id="dupesearchspan">Looking for Dupes...</span>
													<span id="dupenonespan" style="display:none;color:red;">No Dupes Found</span>
													<span id="dupedisplayspan" style="display:none;color:red;">Displaying Dupes</span>
												</div>
											</div>
											<div>
												<input type="text" name="associatedcollectors" tabindex="14" maxlength="255" style="width:330px;" value="<?php echo array_key_exists('associatedcollectors',$occArr)?$occArr['associatedcollectors']:''; ?>" onchange="fieldChanged('associatedcollectors');" />
												<span style="margin-left:10px;">
													<input type="text" name="othercatalognumbers" tabindex="15" maxlength="255" style="width:150px;" value="<?php echo array_key_exists('othercatalognumbers',$occArr)?$occArr['othercatalognumbers']:''; ?>" onchange="fieldChanged('othercatalognumbers');" />
												</span>
												<span style="margin-left:5px;cursor:pointer;" onclick="toggle('dateextradiv')">
													<img src="../../images/showedit.png" style="width:15px;" />
												</span>
											</div>
											<div id="dateextradiv" style="padding:10px;margin:5px;border:1px solid gray;display:none;">
												<span>
													Verbatim Date:
													<input type="text" name="verbatimeventdate" tabindex="16" maxlength="255" style="width:200px;" value="<?php echo array_key_exists('verbatimeventdate',$occArr)?$occArr['verbatimeventdate']:''; ?>" onchange="verbatimEventDateChanged(this)" />
												</span>
												<span style="margin-left:10px;">
													YYYY-MM-DD:
													<input type="text" name="year" tabindex="18" style="width:45px;" value="<?php echo array_key_exists('year',$occArr)?$occArr['year']:''; ?>" onchange="inputIsNumeric(this, 'Year');fieldChanged('year');" title="Numeric Year" />-
													<input type="text" name="month" tabindex="20" style="width:30px;" value="<?php echo array_key_exists('month',$occArr)?$occArr['month']:''; ?>" onchange="inputIsNumeric(this, 'Month');fieldChanged('month');" title="Numeric Month" />-
													<input type="text" name="day" tabindex="22" style="width:30px;" value="<?php echo array_key_exists('day',$occArr)?$occArr['day']:''; ?>" onchange="inputIsNumeric(this, 'Day');fieldChanged('day');" title="Numeric Day" />
												</span>
												<span style="margin-left:10px;">
													Day of Year:
													<input type="text" name="startdayofyear" tabindex="24" style="width:40px;" value="<?php echo array_key_exists('startdayofyear',$occArr)?$occArr['startdayofyear']:''; ?>" onchange="inputIsNumeric(this, 'Start Day of Year');fieldChanged('startdayofyear');" title="Start Day of Year" /> -
													<input type="text" name="enddayofyear" tabindex="26" style="width:40px;" value="<?php echo array_key_exists('enddayofyear',$occArr)?$occArr['enddayofyear']:''; ?>" onchange="inputIsNumeric(this, 'End Day of Year');fieldChanged('enddayofyear');" title="End Day of Year" />
												</span>
											</div>
										</fieldset>
										<fieldset>
											<legend><b>Latest Identification</b></legend>
											<div style="clear:both;">
												<span style="width:125px;">
													Scientific Name:
												</span>
												<span style="margin-left:315px;">
													Author:
												</span>
											</div>
											<div style="clear:both;">
												<span>
													<input type="text" id="ffsciname" name="sciname" maxlength="250" tabindex="28" style="width:390px;background-color:lightyellow;" value="<?php echo array_key_exists('sciname',$occArr)?$occArr['sciname']:''; ?>" <?php echo ($isEditor?'':'disabled '); ?> />
													<input type="hidden" id="tidtoadd" name="tidtoadd" value="" />
												</span>
												<span style="margin-left:10px;">
													<input type="text" name="scientificnameauthorship" maxlength="100" tabindex="0" style="width:200px;" value="<?php echo array_key_exists('scientificnameauthorship',$occArr)?$occArr['scientificnameauthorship']:''; ?>" onchange="fieldChanged('scientificnameauthorship');" <?php echo ($isEditor?'':'disabled '); ?> />
												</span>
												<?php 
												if(!$isEditor && $occArr){
													echo '<div style="color:red;margin-left:5px;">Note: Full editing permissions are needed to edit an identification</div>';
												} 
												?>
												<div></div>
											</div>
											<div style="clear:both;padding:3px 0px 0px 10px;">
												<div style="float:left;">
													<span>ID Qualifier:</span>
													<a href="#" onclick="return dwcDoc('identificationQualifier')">
														<img class="dwcimg" src="../../images/qmark.png" />
													</a>
													<input type="text" name="identificationqualifier" tabindex="30" size="25" style="" value="<?php echo array_key_exists('identificationqualifier',$occArr)?$occArr['identificationqualifier']:''; ?>" onchange="fieldChanged('identificationqualifier');" <?php echo ($isEditor?'':'disabled '); ?> />
												</div>
												<div style="float:left;margin-left:60px;">
													<span>Family:</span>
													<input type="text" name="family" size="30" maxlength="50" style="" tabindex="0" value="<?php echo array_key_exists('family',$occArr)?$occArr['family']:''; ?>" onchange="fieldChanged('family');" />
												</div>
											</div>
											<div style="clear:both;padding:3px 0px 0px 10px;margin-bottom:20px;">
												<div style="float:left;">
													Identified By:
													<input type="text" name="identifiedby" maxlength="255" tabindex="32" style="" value="<?php echo array_key_exists('identifiedby',$occArr)?$occArr['identifiedby']:''; ?>" onchange="fieldChanged('identifiedby');" />
												</div>
												<div style="float:left;margin-left:15px;padding:3px 0px 0px 10px;">
													Date Identified:
													<input type="text" name="dateidentified" maxlength="45" tabindex="34" style="" value="<?php echo array_key_exists('dateidentified',$occArr)?$occArr['dateidentified']:''; ?>" onchange="fieldChanged('dateidentified');" />
												</div>
												<div style="float:left;margin-left:15px;cursor:pointer;" onclick="toggleIdDetails();">
													<img src="../../images/showedit.png" style="width:15px;" />
												</div>
											</div>
											<div style="clear:both;">
												<div id="idrefdiv" style="display:none;padding:3px 0px 0px 10px;" class="p2">
													ID References:
													<a href="#" onclick="return dwcDoc('identificationReferences')">
														<img class="dwcimg" src="../../images/qmark.png" />
													</a>
													<input type="text" name="identificationreferences" tabindex="36" style="width:450px;" value="<?php echo array_key_exists('identificationreferences',$occArr)?$occArr['identificationreferences']:''; ?>" onchange="fieldChanged('identificationreferences');" />
												</div>
												<div id="idremdiv" style="display:none;padding:3px 0px 0px 10px;" class="p2">
													ID Remarks:
													<input type="text" name="identificationremarks" tabindex="38" style="width:500px;" value="<?php echo array_key_exists('identificationremarks',$occArr)?$occArr['identificationremarks']:''; ?>" onchange="fieldChanged('identificationremarks');" />
												</div>
											</div>
										</fieldset>
										<fieldset>
											<legend><b>Locality</b></legend>
											<div>
												<span style="">
													Country
												</span>
												<span style="margin-left:110px;">
													State/Province
												</span>
												<span style="margin-left:72px;">
													County
												</span>
												<span style="margin-left:112px;">
													Municipality
												</span>
											</div>
											<div>
												<span>
													<input type="text" id="ffcountry" name="country" tabindex="40" style="width:150px;background-color:lightyellow;" value="<?php echo array_key_exists('country',$occArr)?$occArr['country']:''; ?>" onchange="countryChanged(this.form);" />
												</span>
												<span>
													<input type="text" id="ffstate" name="stateprovince" tabindex="42" style="width:150px;background-color:lightyellow;" value="<?php echo array_key_exists('stateprovince',$occArr)?$occArr['stateprovince']:''; ?>" onchange="stateProvinceChanged(this.form);" />
												</span>
												<span>
													<input type="text" id="ffcounty" name="county" tabindex="44" style="width:150px;" value="<?php echo array_key_exists('county',$occArr)?$occArr['county']:''; ?>" onchange="countyChanged(this.form);" />
												</span>
												<span>
													<input type="text" id="ffmunicipality" name="municipality" tabindex="45" style="width:150px;" value="<?php echo array_key_exists('municipality',$occArr)?$occArr['municipality']:''; ?>" onchange="fieldChanged('municipality');" />
												</span>
											</div>
											<div style="margin:4px 0px 2px 0px;">
												Locality:<br />
												<input type="text" name="locality" tabindex="46" style="width:600px;background-color:lightyellow;" value="<?php echo array_key_exists('locality',$occArr)?$occArr['locality']:''; ?>" onchange="fieldChanged('locality');" />
											</div>
											<div style="margin-bottom:5px;">
												<?php $hasValue = array_key_exists("localitysecurity",$occArr)&&$occArr["localitysecurity"]?1:0; ?>
												<input type="checkbox" name="localitysecurity" tabindex="0" style="" value="1" <?php echo $hasValue?"CHECKED":""; ?> onchange="fieldChanged('localitysecurity');toggleLocSecReason(this.form);" title="Hide Locality Data from General Public" />
												Locality Security
												<span id="locsecreason" style="margin-left:40px;display:<?php echo ($hasValue?'inline':'none') ?>">
													<?php $lsrValue = array_key_exists('localitysecurityreason',$occArr)?$occArr['localitysecurityreason']:''; ?>
													Security Reason Override: <input type="text" name="localitysecurityreason" tabindex="0" onchange="fieldChanged('localitysecurityreason');" value="<?php echo $lsrValue; ?>" title="Leave blank for default rare, threatened, or sensitive status" />
												</span>
											</div>
											<div>
												<span style="">
													Latitude
												</span>
												<span style="margin-left:45px;">
													Longitude
												</span>
												<span style="margin-left:36px;">
													Uncertainty
													<a href="#" onclick="return dwcDoc('coordinateUncertaintyInMeters')">
														<img class="dwcimg" src="../../images/qmark.png" />
													</a>
												</span>
												<span style="margin-left:62px;">
													Datum
													<a href="#" onclick="return dwcDoc('geodeticDatum')">
														<img class="dwcimg" src="../../images/qmark.png" />
													</a>
												</span>
												<span style="margin-left:30px;">
													Elevation in Meters
												</span>
												<span style="margin-left:47px;">
													Verbatim Elevation
												</span>
											</div>
											<div>
												<span>
													<?php
													$latValue = "";
													if(array_key_exists("decimallatitude",$occArr) && $occArr["decimallatitude"] != "") {
														$latValue = $occArr["decimallatitude"];
													}
													?>
													<input type="text" name="decimallatitude" tabindex="50" maxlength="15" style="width:88px;background-color:lightyellow" value="<?php echo $latValue; ?>" onchange="inputIsNumeric(this, 'Decimal Latitude');fieldChanged('decimallatitude');" />
												</span>
												<span>
													<?php
													$longValue = "";
													if(array_key_exists("decimallongitude",$occArr) && $occArr["decimallongitude"] != "") {
														$longValue = $occArr["decimallongitude"];
													}
													?>
													<input type="text" name="decimallongitude" tabindex="52" maxlength="15" style="width:88px;background-color:lightyellow" value="<?php echo $longValue; ?>" onchange="inputIsNumeric(this, 'Decimal Longitude');fieldChanged('decimallongitude');" />
												</span>
												<span>
													<input type="text" name="coordinateuncertaintyinmeters" tabindex="54" maxlength="10" style="width:70px;" value="<?php echo array_key_exists('coordinateuncertaintyinmeters',$occArr)?$occArr['coordinateuncertaintyinmeters']:''; ?>" onchange="inputIsNumeric(this, 'Coordinate Uncertainty');fieldChanged('coordinateuncertaintyinmeters');" title="Uncertainty in Meters" />
												</span>
												<span style="cursor:pointer;padding:3px;" onclick="openMappingAid();">
													<img src="../../images/world40.gif" style="border:0px;width:13px;"  />
												</span>
												<span style="text-align:center;font-size:85%;font-weight:bold;color:maroon;background-color:#FFFFD7;padding:2px;margin:3px;border:1px outset #A0A0A0;cursor:pointer;" onclick="toggleCoordDiv();" title="Other Coordinate Formats">
													Tools
												</span>
												<span>
													<input type="text" name="geodeticdatum" tabindex="56" maxlength="255" style="width:80px;" value="<?php echo array_key_exists('geodeticdatum',$occArr)?$occArr['geodeticdatum']:''; ?>" onchange="fieldChanged('geodeticdatum');" />
												</span>
												<span>
													<input type="text" name="minimumelevationinmeters" tabindex="58" maxlength="6" style="width:55px;" value="<?php echo array_key_exists('minimumelevationinmeters',$occArr)?$occArr['minimumelevationinmeters']:''; ?>" onchange="inputIsNumeric(this, 'Minumum Elevation');fieldChanged('minimumelevationinmeters');" title="Minumum Elevation In Meters" />
												</span> -
												<span>
													<input type="text" name="maximumelevationinmeters" tabindex="60" maxlength="6" style="width:55px;" value="<?php echo array_key_exists('maximumelevationinmeters',$occArr)?$occArr['maximumelevationinmeters']:''; ?>" onchange="inputIsNumeric(this, 'Maximum Elevation');fieldChanged('maximumelevationinmeters');" title="Maximum Elevation In Meters" />
												</span>
												<span style="text-align:center;font-weight:bold;color:maroon;background-color:#FFFFD7;padding:2px;margin:3px;border:1px outset #A0A0A0;cursor:pointer;" onclick="toggleElevDiv()">
													ft.
												</span>
												<span>
													<input type="text" name="verbatimelevation" tabindex="62" maxlength="255" style="width:100px;" value="<?php echo array_key_exists('verbatimelevation',$occArr)?$occArr['verbatimelevation']:''; ?>" onchange="fieldChanged('verbatimelevation');" title="" />
												</span>
												<span style="margin-left:5px;cursor:pointer;" onclick="toggle('locextradiv1');toggle('locextradiv2');">
													<img src="../../images/showedit.png" style="width:15px;" />
												</span>
											</div>
											<?php 
												$locExtraDiv1 = "none";
												if(array_key_exists("verbatimcoordinates",$occArr) && $occArr["verbatimcoordinates"]){
													$locExtraDiv1 = "block";
												}
												elseif(array_key_exists("georeferencedby",$occArr) && $occArr["georeferencedby"]){
													$locExtraDiv1 = "block";
												}
												elseif(array_key_exists("georeferenceprotocol",$occArr) && $occArr["georeferenceprotocol"]){
													$locExtraDiv1 = "block";
												}
												include_once('includes/geotools.php');
											?>
											<div id="locextradiv1" style="position:relative;clear:both;display:<?php echo $locExtraDiv1; ?>;">
												<div>
													<span style="">
														Verbatim Coordinates
													</span>
													<span style="margin-left:185px;">
														Georeferenced By
													</span>
													<span style="margin-left:54px;">
														Georeference Protocol
														<a href="#" onclick="return dwcDoc('georeferenceProtocol')">
															<img class="dwcimg" src="../../images/qmark.png" />
														</a>
													</span>
												</div>
												<div>
													<span>
														<input type="text" name="verbatimcoordinates" tabindex="64" maxlength="255" style="width:300px;" value="<?php echo array_key_exists('verbatimcoordinates',$occArr)?$occArr['verbatimcoordinates']:''; ?>" onchange="fieldChanged('verbatimcoordinates');" title="" />
													</span>
													<span>
														<input type="text" name="georeferencedby" tabindex="66" maxlength="255" style="width:150px;" value="<?php echo array_key_exists('georeferencedby',$occArr)?$occArr['georeferencedby']:''; ?>" onchange="fieldChanged('georeferencedby');" />
													</span>
													<span>
														<input type="text" name="georeferenceprotocol" tabindex="68" maxlength="255" style="width:150px;" value="<?php echo array_key_exists('georeferenceprotocol',$occArr)?$occArr['georeferenceprotocol']:''; ?>" onchange="fieldChanged('georeferenceprotocol');" />
													</span>
												</div>
											</div>
											<?php 
												$locExtraDiv2 = "none";
												if(array_key_exists("georeferencesources",$occArr) && $occArr["georeferencesources"]){
													$locExtraDiv2 = "block";
												}
												elseif(array_key_exists("georeferenceverificationstatus",$occArr) && $occArr["georeferenceverificationstatus"]){
													$locExtraDiv2 = "block";
												}
												elseif(array_key_exists("georeferenceremarks",$occArr) && $occArr["georeferenceremarks"]){
													$locExtraDiv2 = "block";
												}
											?>
											<div id="locextradiv2" style="display:<?php echo $locExtraDiv2; ?>;">
												<div>
													<span style="">
														Georeference Sources
														<a href="#" onclick="return dwcDoc('georeferenceSources')">
															<img class="dwcimg" src="../../images/qmark.png" />
														</a>
													</span>
													<span style="margin-left:30px;">
														Georef Verification Status
														<a href="#" onclick="return dwcDoc('georeferenceVerificationStatus')">
															<img class="dwcimg" src="../../images/qmark.png" />
														</a>
													</span>
													<span style="margin-left:10px;">
														Georeference Remarks
													</span>
												</div>
												<div>
													<span>
														<input type="text" name="georeferencesources" tabindex="70" maxlength="255" style="width:160px;" value="<?php echo array_key_exists('georeferencesources',$occArr)?$occArr['georeferencesources']:''; ?>" onchange="fieldChanged('georeferencesources');" />
													</span>
													<span>
														<input type="text" name="georeferenceverificationstatus" tabindex="72" maxlength="32" style="width:160px;" value="<?php echo array_key_exists('georeferenceverificationstatus',$occArr)?$occArr['georeferenceverificationstatus']:''; ?>" onchange="fieldChanged('georeferenceverificationstatus');" />
													</span>
													<span>
														<input type="text" name="georeferenceremarks" tabindex="74" maxlength="255" style="width:250px;" value="<?php echo array_key_exists('georeferenceremarks',$occArr)?$occArr['georeferenceremarks']:''; ?>" onchange="fieldChanged('georeferenceremarks');" />
													</span>
												</div>
											</div>
										</fieldset>
										<fieldset>
											<legend><b>Misc</b></legend>
											<div style="padding:3px;">
												Habitat:
												<input type="text" name="habitat" tabindex="82" style="width:600px;" value="<?php echo array_key_exists('habitat',$occArr)?$occArr['habitat']:''; ?>" onchange="fieldChanged('habitat');" />
											</div>
											<div style="padding:3px;">
												Substrate:
												<input type="text" name="substrate" tabindex="82" style="width:600px;" value="<?php echo array_key_exists('substrate',$occArr)?$occArr['substrate']:''; ?>" onchange="fieldChanged('substrate');" />
											</div>
											<div style="padding:3px;">
												Associated Taxa:
												<input type="text" name="associatedtaxa" tabindex="84" style="width:575px;" value="<?php echo array_key_exists('associatedtaxa',$occArr)?$occArr['associatedtaxa']:''; ?>" onchange="fieldChanged('associatedtaxa');" /> 
												<a href="#" onclick="openAssocSppAid();return false;">
													<img src="../../images/list.png" style="width:15px;border:0px;" />
												</a>
											</div>
											<div style="padding:3px;">
												Description:
												<input type="text" name="verbatimattributes" tabindex="86" style="width:600px;" value="<?php echo array_key_exists('verbatimattributes',$occArr)?$occArr['verbatimattributes']:''; ?>" onchange="fieldChanged('verbatimattributes');" />
											</div>
											<div style="padding:3px;">
												Notes:
												<input type="text" name="occurrenceremarks" tabindex="88" style="width:600px;" value="<?php echo array_key_exists('occurrenceremarks',$occArr)?$occArr['occurrenceremarks']:''; ?>" onchange="fieldChanged('occurrenceremarks');" title="Occurrence Remarks" />
												<span style="margin-left:5px;cursor:pointer;" onclick="toggle('miscextradiv');">
													<img src="../../images/showedit.png" style="width:15px;" />
												</span>
											</div>
											<div id="miscextradiv" style="padding:3px;display:none;">
												Dynamic Properties:
												<a href="#" onclick="return dwcDoc('dynamicProperties')">
													<img class="dwcimg" src="../../images/qmark.png" />
												</a>
												<input type="text" name="dynamicproperties" tabindex="90" style="width:550px;" value="<?php echo array_key_exists('dynamicproperties',$occArr)?$occArr['dynamicproperties']:''; ?>" onchange="fieldChanged('dynamicproperties');" />
											</div>
										</fieldset>
										<fieldset>
											<legend><b>Curation</b></legend>
											<div style="padding:3px;">
												<span>
													Type Status:
													<a href="#" onclick="return dwcDoc('typeStatus')">
														<img class="dwcimg" src="../../images/qmark.png" />
													</a>
													<input type="text" name="typestatus" tabindex="94" maxlength="255" style="width:150px;" value="<?php echo array_key_exists('typestatus',$occArr)?$occArr['typestatus']:''; ?>" onchange="fieldChanged('typestatus');" />
												</span>
												<span style="margin-left:30px;">
													Disposition:
													<a href="#" onclick="return dwcDoc('disposition')">
														<img class="dwcimg" src="../../images/qmark.png" />
													</a>
													<input type="text" name="disposition" tabindex="96" maxlength="32" style="width:200px;" value="<?php echo array_key_exists('disposition',$occArr)?$occArr['disposition']:''; ?>" onchange="fieldChanged('disposition');" />
												</span>
											</div>
											<div style="padding:3px;">
												<span>
													Reproductive Condition:
													<a href="#" onclick="return dwcDoc('reproductiveCondition')">
														<img class="dwcimg" src="../../images/qmark.png" />
													</a>
													<input type="text" name="reproductivecondition" tabindex="98" maxlength="255" style="width:140px;" value="<?php echo array_key_exists('reproductivecondition',$occArr)?$occArr['reproductivecondition']:''; ?>" onchange="fieldChanged('reproductivecondition');" />
												</span>
												<span style="margin-left:30px;">
													Establishment Means:
													<a href="#" onclick="return dwcDoc('establishmentMeans')">
														<img class="dwcimg" src="../../images/qmark.png" />
													</a>
													<input type="text" name="establishmentmeans" tabindex="100" maxlength="32" style="width:140px;" value="<?php echo array_key_exists('establishmentmeans',$occArr)?$occArr['establishmentmeans']:''; ?>" onchange="fieldChanged('establishmentmeans');" />
												</span>
												<span style="margin-left:15px;">
													<?php $hasValue = array_key_exists("cultivationstatus",$occArr)&&$occArr["cultivationstatus"]?1:0; ?>
													<input type="checkbox" name="cultivationstatus" tabindex="102" style="" value="1" <?php echo $hasValue?'CHECKED':''; ?> onchange="fieldChanged('cultivationstatus');" />
													Cultivated
												</span>
											</div>
											<div style="padding:3px;">
												<span title="If different than institution code">
													Owner Code:
													<a href="#" onclick="return dwcDoc('ownerInstitutionCode')">
														<img class="dwcimg" src="../../images/qmark.png" />
													</a>
													<input type="text" name="ownerinstitutioncode" tabindex="104" maxlength="32" style="width:150px;" value="<?php echo array_key_exists('ownerinstitutioncode',$occArr)?$occArr['ownerinstitutioncode']:''; ?>" onchange="fieldChanged('ownerinstitutioncode');" />
												</span>
												<span style="margin-left:10px;">
													Basis of Record:
													<a href="#" onclick="return dwcDoc('basisOfRecord')">
														<img class="dwcimg" src="../../images/qmark.png" />
													</a>
													<input type="text" name="basisofrecord" tabindex="106" maxlength="32" value="<?php echo array_key_exists('basisofrecord',$occArr)?$occArr['basisofrecord']:''; ?>" onchange="fieldChanged('basisofrecord');" />
												</span>
												<span style="margin-left:10px;">
													Language:
													<input type="text" name="language" tabindex="108" maxlength="20" value="<?php echo array_key_exists('language',$occArr)?$occArr['language']:''; ?>" onchange="fieldChanged('language');" />
												</span>
											</div>
										</fieldset>
										<fieldset>
											<legend><b>Other</b></legend>
											<div style="padding:3px;">
												<span>
													Processing Status:
													<?php 
														$pStatus = array_key_exists('processingstatus',$occArr)?$occArr['processingstatus']:'';
													?>
													<select name="processingstatus" tabindex="110" onchange="fieldChanged('processingstatus');">
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
														<option value='expert requiered' <?php echo ($pStatus=='expert requiered'?'SELECTED':''); ?>>
															Expert Requiered
														</option>
														<option value='reviewed' <?php echo ($pStatus=='reviewed'?'SELECTED':''); ?>>
															Reviewed
														</option>
													</select>
												</span>
												<span style="margin-left:20px;">
													Label Project:
													<input type="text" name="labelproject" tabindex="112" maxlength="45" value="<?php echo array_key_exists('labelproject',$occArr)?$occArr['labelproject']:''; ?>" onchange="fieldChanged('labelproject');" />
												</span>
												<span style="margin-left:20px;" title="aka label quantity">
													Duplicate Quantity:
													<input type="text" name="duplicatequantity" tabindex="116" style="width:35px;" value="<?php echo array_key_exists('duplicatequantity',$occArr)?$occArr['duplicatequantity']:''; ?>" onchange="fieldChanged('duplicatequantity');" />
												</span>
											</div>
											<div style="padding:3px;">
												<span style="" title="Internal occurrence record Primary Key">
													<?php if($occId) echo 'Portal ID: '.$occId; ?>
												</span>
												<span style="margin-left:90px;">
													<?php if(array_key_exists('datelastmodified',$occArr)) echo 'Date Last Modified: '.$occArr['datelastmodified']; ?>
												</span>
												<span style="margin-left:90px;">
													<?php if(array_key_exists('recordenteredby',$occArr)) echo 'Entered By: '.$occArr['recordenteredby']; ?>
												</span>
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
											<?php if($occId){ ?>
												<fieldset style="float:right;margin:15px 20px 20px 0px;padding:15px;background-color:lightyellow">
													<legend><b>Options</b></legend>
													<input type="submit" name="gotonew" value="Go to New Occurrence Record" onclick="return verifyGotoNew(this.form);" /><br/>
													<input type="checkbox" name="carryloc" value="1" /> Carry over locality values
												</fieldset>
												<div style="margin:15px 0px 20px 30px;">
													<input type="submit" name="submitaction" value="Save Edits" style="width:150px;" onclick="return verifyFullFormEdits(this.form)" /><br/>
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
														<option value='expert requiered' <?php echo ($autoPStatus=='expert requiered'?'SELECTED':''); ?>>
															Expert Requiered
														</option>
														<option value='reviewed' <?php echo ($autoPStatus=='reviewed'?'SELECTED':''); ?>>
															Reviewed
														</option>
													</select><br/>
													<input type="hidden" name="editedfields" value="" />
													<?php 
													if($occIndex !== false){
														?>
														<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
														<?php 
													}
													?>
												</div>
											<?php }else{ ?>
												<div style="width:450px;border:1px solid black;background-color:lightyellow;padding:10px;margin:20px;">
													<input type="submit" name="submitaction" value="Add Record" style="width:150px;font-weight:bold;margin:10px;" />
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
											<?php } ?>
										</div>
										<div style="clear:both;">&nbsp;</div>
									</form>
								</div>
								<?php
								if($occId && $isEditor){
									?>
									<div id="admindiv" style="">
										<form name="deleteform" method="post" action="occurrenceeditor.php" onsubmit="return confirm('Are you sure you want to delete this record?')">
											<fieldset>
												<legend>Delete Occurrence Record</legend>
												<div style="margin:15px">
													Record first needs to be evaluated before it can be deleted from the system. 
													The evaluation ensures that the deletion of this record will not interfer with 
													the integrity of other data linked to this record. Note that all determination and 
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
						<td id="imgtd" style="display:none;width:430px;" valign="top";>
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