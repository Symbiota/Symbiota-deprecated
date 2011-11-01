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

$occManager->setSymbUid($symbUid); 
if($occId) $occManager->setOccId($occId); 
if($collId) $occManager->setCollId($collId);
$collMap = Array();
$collMap = $occManager->getCollMap();
if($occId && !$collId) $collId = $collMap['collid'];

$isEditor = 0;		//If not editor, edits will be submitted to omoccuredits table but not applied to omoccurrences 
if($symbUid){
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
		$isEditor = 1;
	}
}
$occArr = Array();
$qryArr = array();
$qryCnt = false;
//Check to see if Query Form has been activated
if($occIndex !== false){ 
	$qryArr = $occManager->getQueryVariables();
	if(array_key_exists('rc',$qryArr)) $qryCnt = $qryArr['rc'];
	if($action != "Save Edits" && $action != 'Delete Occurrence'){
		$qryWhere = $occManager->getQueryWhere($qryArr,$occIndex,($isEditor==1?1:0));
		if(!$qryCnt) $qryCnt = $occManager->getQueryRecordCount($qryArr,$qryWhere);
		$occManager->setOccurArr($qryWhere);
		$occId = $occManager->getOccId();
		$occArr = $occManager->getOccurMap();
	}
}
elseif(isset($_COOKIE["editorquery"])){
	//Make sure query is null
	setCookie('editorquery','',time()-3600,($clientRoot?$clientRoot:'/'));
}

$isGenObs = ($collMap['colltype']=='General Observations'?1:0);
$statusStr = '';
if($symbUid){
	if(!$isEditor){
		if($isGenObs){ 
			if(!$occId && array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"])){
				//Approved General Observation editors can add records
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
	if($action == "Save Edits"){
		$statusStr = $occManager->editOccurrence($_REQUEST,$symbUid,$isEditor);
		//Reset query counts if it is activated
		if($occIndex !== false){
			$qryWhere = $occManager->getQueryWhere($qryArr,$occIndex,($isEditor==1?1:0));
			$newQryCnt = $occManager->getQueryRecordCount($qryArr,$qryWhere);
			if($newQryCnt == 0){
				setCookie('editorquery','',time()-3600,($clientRoot?$clientRoot:'/'));
				$occIndex = false;
			}
			elseif($qryCnt != $newQryCnt){
				$qryCnt = $newQryCnt;
				$occIndex--;
			}
			$qryWhere = $occManager->getQueryWhere($qryArr,$occIndex,($isEditor==1?1:0));
			$occManager->setOccurArr($qryWhere);
			$occId = $occManager->getOccId();
			$occArr = $occManager->getOccurMap();
			if($isEditor == 2 && $isGenObs && $occManager->getObserverUid() != $symbUid){
				//User can only edit their own records
				$isEditor = 0;
			}
		}
	}
	if($isEditor){
		if($goToMode){
			if($action == 'Add Record'){
				$statusStr = $occManager->addOccurrence($_REQUEST);
				$occId = $occManager->getOccId();
			}
			//When a new record is added, reset query form if it already exists
			setCookie('editorquery','',time()-3600,($clientRoot?$clientRoot:'/'));
			$occIndex = false;

			if($goToMode == 1){
				$occId = 0;
			}
			elseif($goToMode == 2){
				$occArr = $occManager->carryOverValues($_REQUEST);
				$occId = 0;
			}
			elseif($goToMode == 4){
				header('Location: ../individual/index.php?occid='.$occId);
			}
		}

		if($isEditor){
			if($action == 'Delete Occurrence'){
				$statusStr = $occManager->deleteOccurrence($occId);
				if(strpos($statusStr,'SUCCESS') !== false) $occId = 0;
				//Reset query form index to one less, unless it's already 1, then just reset
				if($occIndex !== false){
					if($qryCnt > 1){
						if(($occIndex + 1) >= $qryCnt) $occIndex = $qryCnt - 2;
						$qryCnt--;
						$qryWhere = $occManager->getQueryWhere($qryArr,$occIndex,($isEditor==1?1:0));
						$occManager->setOccurArr($qryWhere);
						$occId = $occManager->getOccId();
						$occArr = $occManager->getOccurMap();
						if($isEditor == 2 && $isGenObs && $occManager->getObserverUid() != $symbUid){
							//User can only edit their own records
							$isEditor = 0;
						}
					}
					else{
						setCookie('editorquery','',time()-3600,($clientRoot?$clientRoot:'/'));
						$occIndex = false;
					}
				}
			}
			elseif($action == "Submit Image Edits"){
				$statusStr = $occManager->editImage($_REQUEST);
			}
			elseif($action == "Submit New Image"){
				$statusStr = $occManager->addImage($_REQUEST);
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
	}
	if($occId && !$occArr){
		$occArr = $occManager->getOccurMap();
	}

	$navStr = '';
	if($qryCnt !== false){
		if($qryCnt == 0){
			$navStr .= '<div style="margin:20px;font-size:150%;font-weight:bold;">';
			$navStr .= 'Search returned 0 records</div>'."\n";
		}
		else{
			$navStr = '<b>';
			if($occIndex > 0) $navStr .= '<a href="#" onclick="return submitQueryForm(0);">';
			$navStr .= '|&lt;';
			if($occIndex > 0) $navStr .= '</a>';
			$navStr .= '&nbsp;&nbsp;|&nbsp;&nbsp;';
			if($occIndex > 0) $navStr .= '<a href="#" onclick="return submitQueryForm('.($occIndex-1).');">';
			$navStr .= '&lt;&lt;';
			if($occIndex > 0) $navStr .= '</a>';
			$navStr .= '&nbsp;&nbsp;| '.($occIndex + 1).' of '.$qryCnt.' |&nbsp;&nbsp;';
			if($occIndex<$qryCnt-1) $navStr .= '<a href="#" onclick="return submitQueryForm('.($occIndex+1).');">';
			$navStr .= '&gt;&gt;';
			if($occIndex<$qryCnt-1) $navStr .= '</a>';
			$navStr .= '&nbsp;&nbsp;|&nbsp;&nbsp;';
			if($occIndex<$qryCnt-1) $navStr .= '<a href="#" onclick="return submitQueryForm('.($qryCnt-1).');">';
			$navStr .= '&gt;|';
			if($occIndex<$qryCnt-1) $navStr .= '</a> ';
			$navStr .= '</b>';
			$navStr = $navStr;
		}
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
	</script>
	<script type="text/javascript" src="../../js/symb/collections.occurrenceeditor.js"></script>
</head>
<body>
	<!-- inner text -->
	<div id="innertext" style="width:790px">
		<?php 
		if(!$symbUid){
			?>
			Please <a href="../../profile/index.php?refurl=<?php echo $_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']; ?>">LOGIN</a> to edit or add an occurrence record 
			<?php 
		}
		else{
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
			if($statusStr){
				?>
				<div id="statusdiv">
					<fieldset style="margin:10px;padding:10px;">
						<legend><b>Action Status</b></legend>
						<div style="margin:10px;color:red;">
							<?php echo $statusStr; ?>
						</div>
						<?php 
						if($action == 'Delete Occurrence'){
							?>
							<a href="" style="margin:10px;" onclick="window.opener.location.href = window.opener.location.href;window.close();">
								Return to Search Page
							</a>
							<?php
						}
						else{
							?>
							<div style="margin:10px;">
								Go to <a href="../individual/index.php?occid=<?php echo $occManager->getOccId(); ?>">Occurrence Display Page</a>
							</div>
							<?php 
						}
						?>
					</fieldset>
				</div>
				<?php 
			}
			if($occId || $isEditor){
				if(!$occId && !$collId){
					?>
					<div style="margin:10px;">
						Select the collection to which you wish to work with:
						<?php
						$collList = Array();
						if(!$isAdmin){
							if(array_key_exists("CollAdmin",$userRights)){
								$collList = $userRights["CollAdmin"];
							}
							if(array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"])){
								$collList = array_merge($collList,$userRights["CollEditor"]);
							}
						}
						$collList = $occManager->getCollectionList($collList);
						if($collList){
							echo '<ul>';
							foreach($collList as $cid => $cName){
								echo '<li><a href="occurrenceeditor.php?collid='.$cid.'">'.$cName.'</a></li>';
							}
							echo '</ul>';
						}
						else{
							echo '<h2>You are not authorized to add occurrence records</h2>';
						}
						?>
					</div>
					<?php 
				}
				else{
					if($isEditor){
						$qIdentifier=''; $qRecordedBy=''; $qRecordNumber=''; $qEnteredBy=''; $qProcessingStatus=''; $qDateLastModified='';
						if($qryArr){
							$qIdentifier = (array_key_exists('id',$qryArr)?$qryArr['id']:'');
							$qRecordedBy = (array_key_exists('rb',$qryArr)?$qryArr['rb']:'');
							$qRecordNumber = (array_key_exists('rn',$qryArr)?$qryArr['rn']:'');
							$qEnteredBy = (array_key_exists('eb',$qryArr)?$qryArr['eb']:'');
							$qProcessingStatus = (array_key_exists('ps',$qryArr)?$qryArr['ps']:'');
							$qDateLastModified = (array_key_exists('dm',$qryArr)?$qryArr['dm']:'');
						}
						?>
						<div id="querydiv" style="display:<?php echo (!$occArr&&!$goToMode?'block':'none'); ?>;">
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
											<option <?php echo ($qProcessingStatus=='unprocessed'?'SELECTED':''); ?>>
												unprocessed
											</option>
											<option <?php echo ($qProcessingStatus=='OCR processed'?'SELECTED':''); ?>>
												OCR processed
											</option>
											<option <?php echo ($qProcessingStatus=='OCR parsed'?'SELECTED':''); ?>>
												OCR parsed
											</option>
											<option <?php echo ($qProcessingStatus=='pending duplicate'?'SELECTED':''); ?>>
												pending duplicate
											</option>
											<option <?php echo ($qProcessingStatus=='pending review'?'SELECTED':''); ?>>
												pending review
											</option>
											<option <?php echo ($qProcessingStatus=='reviewed'?'SELECTED':''); ?>>
												reviewed
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
										<span style="margin-left:10px;">
											<input type="button" name="reset" value="Reset Form" onclick="resetQueryForm(this.form)" /> 
										</span>
									</div>
								</fieldset>
							</form>
						</div>
						<?php
					} 
					if($navStr){
						?>
						<div>
							<span class='navpath'>
								<a href="../../index.php">Home</a> &gt;&gt;
								<a href="..//misc/collprofiles.php?collid=<?php echo $collId; ?>">Collection Editor Panel</a> &gt;&gt;
								<b>Editor</b>
							</span>
							<span style="margin-left:370px;">
								<?php echo $navStr; ?>
							</span>
						</div>
						<?php 
					}
					if($occArr || $goToMode == 1 || $goToMode == 2){		//$action == 'gotonew'
						?>
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
										<a href="occurrenceeditordets.php?occid=<?php echo $occId.'&occindex='.$occIndex.$detVars; ?>" style="margin:0px 20px 0px 20px;">
											Determination History
										</a>
									</li>
									<li>
										<a href="occurrenceeditorimages.php?occid=<?php echo $occId.'&occindex='.$occIndex.$imgVars; ?>" style="margin:0px 20px 0px 20px;">
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
								<table id="edittable">
									<tr><td style="width:745px;">
										<form id="fullform" name="fullform" action="occurrenceeditor.php" method="post" >
											<fieldset>
												<legend><b>Collector Info</b></legend>
												<?php
												if($occId){ 
													$fragArr = $occManager->getRawTextFragments();
													$specImgArr = $occManager->getImageMap();
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
													<input type="checkbox" name="localitysecurity" tabindex="0" style="" value="1" <?php echo $hasValue?"CHECKED":""; ?> onchange="fieldChanged('localitysecurity');toogleLocSecReason(this.form);" title="Hide Locality Data from General Public" />
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
												?>
												<div>
													<div id="coordaiddiv" style="display:none;">
														<div style="float:left;padding:15px 10px;background-color:lightyellow;border:1px solid yellow;width:260px;">
															<div>
																Latitude: 
																<input id="latdeg" style="width:35px;" title="Latitude Degree" />&deg; 
																<input id="latmin" style="width:50px;" title="Latitude Minutes" />' 
																<input id="latsec" style="width:50px;" title="Latitude Seconds" />&quot; 
																<select id="latns">
																	<option>N</option>
																	<option>S</option>
																</select>
															</div>
															<div>
																Longitude: 
																<input id="lngdeg" style="width:35px;" title="Longitude Degree" />&deg; 
																<input id="lngmin" style="width:50px;" title="Longitude Minutes" />' 
																<input id="lngsec" style="width:50px;" title="Longitude Seconds" />&quot; 
																<select id="lngew">
																	<option>E</option>
																	<option SELECTED>W</option>
																</select>
															</div>
															<div style="margin:5px;">
																<input type="button" value="Insert Lat/Long Values" onclick="insertLatLng(this.form)" />
															</div>
														</div>
														<div style="float:left;padding:15px 10px;background-color:lightyellow;border:1px solid yellow;width:140px;margin-bottom:10px;">
															Zone: <input id="utmzone" style="width:40px;" /><br/>
															East: <input id="utmeast" type="text" style="width:100px;" /><br/>
															North: <input id="utmnorth" type="text" style="width:100px;" /><br/>
															Hemisphere: <select id="hemisphere" title="Use hemisphere designator (e.g. 12N) rather than grid zone ">
																<option value="Northern">North</option>
																<option value="Southern">South</option>
															</select><br/>
															<div style="margin-top:5px;">
																<input type="button" value="Insert UTM Values" onclick="insertUtm(this.form)" />
															</div>
														</div>
														<div style="float:left;padding:15px 10px;background-color:lightyellow;border:1px solid yellow;">
															T<input id="township" style="width:30px;" title="Township" />
															<select id="townshipNS">
																<option>N</option>
																<option>S</option>
															</select>&nbsp;&nbsp;&nbsp;&nbsp;
															R<input id="range" style="width:30px;" title="Range" />
															<select id="rangeEW">
																<option>E</option>
																<option>W</option>
															</select><br/>
															Sec: 
															<input id="section" style="width:30px;" title="Section" />&nbsp;&nbsp;&nbsp; 
															Details: 
															<input id="secdetails" style="width:90px;" title="Section Details" /><br/>
															<select id="meridian" title="Meridian">
																<option value="G-AZ">Arizona, Gila &amp; Salt River</option>
																<option value="NAAZ">Arizona, Navajo</option>
																<option value="F-AR">Arkansas, Fifth Principal</option> 
																<option value="H-CA">California, Humboldt</option>
																<option value="M-CA">California, Mt. Diablo</option>
																<option value="S-CA">California, San Bernardino</option>
																<option value="NMCO">Colorado, New Mexico</option>
																<option value="SPCO">Colorado, Sixth Principal</option>
																<option value="UTCO">Colorado, Ute</option>
																<option value="B-ID">Idaho, Boise</option>
																<option value="SPKS">Kansas, Sixth Principal</option>
																<option value="F-MO">Missouri, Fifth Principal</option>
																<option value="P-MT">Montana, Principal</option>
																<option value="SPNE">Nebraska, Sixth Principal</option>
																<option value="M-NV">Nevada, Mt. Diablo</option>
																<option value="NMNM">New Mexico, New Mexico</option>
																<option value="F-ND">North Dakota, Fifth Principal</option>
																<option value="C-OK">Oklahoma, Cimarron</option>
																<option value="I-OK">Oklahoma, Indian</option>
																<option value="W-OR">Oregon, Willamette</option>
																<option value="BHSD">South Dakota, Black Hills</option>
																<option value="F-SD">South Dakota, Fifth Principal</option>
																<option value="SPSD">South Dakota, Sixth Principal</option>
																<option value="SLUT">Utah, Salt Lake</option>
																<option value="U-UT">Utah, Uinta</option>
																<option value="W-WA">Washington, Willamette</option>
																<option value="SPWY">Wyoming, Sixth Principal</option>
																<option value="WRWY">Wyoming, Wind River</option>
															</select>
															<div style="margin:5px;">
																<input type="button" value="Insert TRS Values" onclick="insertTRS(this.form)" />
															</div>
														</div>
													</div>
													<div id="elevaiddiv" style="display:none;">
														<div style="float:right;padding:15px;background-color:lightyellow;border:1px solid yellow;width:180px;margin-bottom:10px;">
															Elevation: 
															<input id="elevminft" style="width:45px;" /> - 
															<input id="elevmaxft" style="width:45px;" /> feet
															<div style="margin:5px;">
																<input type="button" value="Insert Elevation" onclick="insertElevFt(this.form)" />
															</div>
														</div>
													</div>
												</div>
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
																unprocessed
															</option>
															<option value='OCR processed' <?php echo ($pStatus=='OCR processed'?'SELECTED':''); ?>>
																OCR processed
															</option>
															<option value='OCR parsed' <?php echo ($pStatus=='NLP parsed'?'SELECTED':''); ?>>
																NLP parsed
															</option>
															<option value='pending duplicate' <?php echo ($pStatus=='pending duplicate'?'SELECTED':''); ?>>
																pending duplicate
															</option>
															<option value='pending review' <?php echo (!$occId || $pStatus=='pending review'?'SELECTED':''); ?>>
																pending review
															</option>
															<option value='reviewed' <?php echo ($pStatus=='reviewed'?'SELECTED':''); ?>>
																reviewed
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
												echo '<div style="margin-left:580px;">'.$navStr.'</div>'."\n";
											}
											?>
											<div style="padding:10px;">
												<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
												<?php 
												if($occId){
													?>
													<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
													<input type="hidden" name="editedfields" value="" />
													<?php 
												}
												?>
												<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
												<input type="hidden" name="userid" value="<?php echo $paramsArr['un']; ?>" />
												<input type="hidden" name="observeruid" value="<?php echo $symbUid; ?>" />
												<?php if($occId){ ?>
													<div style="margin:15px 0px 20px 30px;">
														<input type="submit" name="submitaction" value="Save Edits" style="width:150px;" onclick="return verifyFullFormEdits(this.form)" />
														<?php 
														if($occIndex !== false){
															?>
															<input type="hidden" name="occindex" value="<?php echo $occIndex; ?>" />
															<?php 
														}
														?>
													</div>
													<div style="width:250px;border:1px solid black;background-color:lightyellow;padding:10px;margin:20px;">
														<input type="submit" name="gotonew" value="Go to New Occurrence Record" onclick="return verifyGotoNew(this.form);" /><br/>
														<input type="checkbox" name="carryloc" value="1" /> Carry over locality values<br/>
														* For data entry purposes only 
													</div>
												<?php }else{ ?>
													<div style="width:450px;border:1px solid black;background-color:lightyellow;padding:10px;margin:20px;">
														<input type="submit" name="submitaction" value="Add Record" style="width:150px;font-weight:bold;margin:10px;" onclick="return verifyFullForm(this.form)" />
														<div style="margin-left:15px;font-weight:bold;">
															Follow-up Action:
														</div>
														<div style="margin-left:20px;">
															<input type="radio" name="gotomode" value="1" <?php echo ($goToMode>1?'':'CHECKED'); ?> /> Go to New Record<br/>
															<input type="radio" name="gotomode" value="2" <?php echo ($goToMode==2?'CHECKED':''); ?> /> Go to New Record and Carryover Locality Information<br/> 
															<input type="radio" name="gotomode" value="3" <?php echo ($goToMode==3?'CHECKED':''); ?> /> Remain on Editing Page (add images, determinations, etc)<br/>
															<input type="radio" name="gotomode" value="4" <?php echo ($goToMode==4?'CHECKED':''); ?> /> Go to Specimen Display Page 
														</div>
													</div>
												<?php } ?>
											</div>
											<div style="clear:both;">&nbsp;</div>
										</form>
									</td>
									<td id="imgproctd" style="display:none;">
										<?php 
										if($occId && ($fragArr || $specImgArr )){
											?>
											<div style="width:100%;height:825px;">
												<fieldset style="height:95%">
													<legend><b>Label Processing</b></legend>
													<?php
													if($specImgArr){
														$imgArr = array();
														foreach($specImgArr as $i2){
															$imgArr[] = ($i2['origurl']?$i2['origurl']:$i2['url']);
														}
														$imgUrlPrefix = (isset($imageDomain)?$imageDomain:'');
														?>
														<div id="labelimagediv">
															<img id="activeimage" src="<?php echo $imgUrlPrefix.$imgArr[0]; ?>" />
														</div>
														<div style="width:100%;">
															<input type="button" name="ocrsubmit" value="OCR Image" onclick="ocrImage()" />
															<?php 
															if(count($imgArr)>1){
																?>
																<script type="text/javascript"> 
																	var activeImageArr = new Array("<?php echo $imgUrlPrefix.implode('","'.$imgUrlPrefix,$imgArr); ?>");
																	var activeImageIndex = 0; 
																	function nextLabelProcessingImage(){
																		activeImageIndex++;
																		if(activeImageIndex >= activeImageArr.length){
																			activeImageIndex = 0;
																		}
																		document.getElementById("activeimage").src = activeImageArr[activeImageIndex];
																		document.getElementById("imageindex").innerHTML = activeImageIndex + 1;
																	}
																</script>
																<?php 
															}
															?>
															<span style="margin-left:200px;font-weight:bold;">
																Image <span id="imageindex">1</span> 
																of <?php echo count($imgArr); ?> 
																<a href="#" onclick="nextLabelProcessingImage(); return false;">=&gt;&gt;</a>
															</span>
														</div>
														<?php
													}
													if($fragArr){
														?>
														<div id="rawtextdiv">
															<?php 
															$fragCnt = 0;
															foreach($fragArr as $prlid => $rawStr){
																echo '<div id="txtfrag'.$fragCnt.'" '.($fragCnt?'style="display:none"':'').'>'."\n";
																echo '<textarea name="rawtext-'.$prlid.'" style="width:400px;height:325px;">';
																echo $rawStr;
																echo '</textarea>'."\n";
																echo '</div>'."\n";
																$fragCnt++;
															}
															?>
															<div style="width:100%;text-align:right;font-weight:bold;">
																<span id="textfragindex">1</span> of <?php echo $fragCnt; ?>
																<?php 
																if($fragCnt > 1){
																	?>
																	<script type="text/javascript"> 
																		var textFragIndex = 0;
																		var totalFragCnt = <?php echo $fragCnt; ?>; 
																		function nextRawText(){
																			textFragIndex++;
																			document.getElementById("txtfrag"+(textFragIndex-1)).style.display = "none";
																			if(textFragIndex == totalFragCnt){
																				textFragIndex = 0;
																			}
																			document.getElementById("txtfrag"+textFragIndex).style.display = "block";
																			document.getElementById("textfragindex").innerHTML = textFragIndex + 1;
																		}
																	</script>
																	<a href="#" onclick="nextRawText();return false;">=>></a>
																	<?php 
																}
																?>
															</div>
														</div>
														<?php
													}
													?>
												</fieldset>
											</div>
											<?php
										} 
										?>
									</td></tr>
								</table>
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