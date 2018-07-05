<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceAttributes.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../collections/traitattr/occurattributes.php?'.$_SERVER['QUERY_STRING']);

$collid = $_REQUEST['collid'];
$submitForm = array_key_exists('submitform',$_POST)?$_POST['submitform']:'';
$mode = array_key_exists('mode',$_REQUEST)?$_REQUEST['mode']:1;
$traitID = array_key_exists('traitid',$_REQUEST)?$_REQUEST['traitid']:'';
$taxonFilter = array_key_exists('taxonfilter',$_POST)?$_POST['taxonfilter']:'';
$tidFilter = array_key_exists('tidfilter',$_POST)?$_POST['tidfilter']:'';
$paneX = array_key_exists('panex',$_POST)?$_POST['panex']:'575';
$paneY = array_key_exists('paney',$_POST)?$_POST['paney']:'550';
$imgRes = array_key_exists('imgres',$_POST)?$_POST['imgres']:'med';

$reviewUid = array_key_exists('reviewuid',$_POST)?$_POST['reviewuid']:0;
$reviewDate = array_key_exists('reviewdate',$_POST)?$_POST['reviewdate']:'';
$reviewStatus = array_key_exists('reviewstatus',$_POST)?$_POST['reviewstatus']:0;
$start = array_key_exists('start',$_POST)?$_POST['start']:0;

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($traitID)) $traitID = '';
if(!is_numeric($tidFilter)) $tidFilter = '';
if(!is_numeric($paneX)) $paneX = '';
if(!is_numeric($paneY)) $paneY = '';
if(!is_numeric($start)) $start = 0;

$isEditor = 0; 
if($SYMB_UID){
	if($IS_ADMIN){
		$isEditor = 2;
	}
	elseif($collid){
		//If a page related to collections, one maight want to... 
		if(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])){
			$isEditor = 2;
		}
		elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollEditor"])){
			$isEditor = 1;
		}
	}
}

$attrManager = new OccurrenceAttributes();
if($tidFilter) $attrManager->setTidFilter($tidFilter);
$attrManager->setCollid($collid);

$statusStr = '';
if($isEditor){
	if($submitForm == 'Save and Next'){
		$attrManager->setTargetOccid($_POST['targetoccid']);
		if(!$attrManager->saveAttributes($_POST,$_POST['notes'],$SYMB_UID)){
			$statusStr = $attrManager->getErrorMessage();
		}
	}
	elseif($submitForm == 'Set Status and Save'){
		$attrManager->setTargetOccid($_POST['targetoccid']);
		$attrManager->saveReviewStatus($traitID,$_POST);
	}
}
$imgArr = array();
$occid = 0;
$catNum = '';
if($traitID){
	$imgRetArr = array();
	if($mode == 1){
		$imgRetArr = $attrManager->getImageUrls();
		$imgArr = current($imgRetArr);
	}
	elseif($mode == 2){
		$imgRetArr = $attrManager->getReviewUrls($traitID, $reviewUid, $reviewDate, $reviewStatus, $start);
		if($imgRetArr) $imgArr = current($imgRetArr);
		
	}
	if($imgRetArr){
		$catNum = $imgArr['catnum'];
		unset($imgArr['catnum']);
		$occid = key($imgRetArr);
		if($occid) $attrManager->setTargetOccid($occid);
	}
}
?>
<html>
	<head>
		<title>Occurrence Attribute batch Editor</title>
		<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<script src="../../js/jquery.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui.js" type="text/javascript"></script>
		<script src="../../js/jquery.imagetool-1.7.js?ver=160102" type="text/javascript"></script>
		<script type="text/javascript">
			var activeImgIndex = 1;
			var imgArr = [];
			var imgLgArr = [];
			<?php
			$imgDomain = $IMAGE_DOMAIN;
			if(!$imgDomain){
				$imgDomain = 'http://';
				if((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443) $imgDomain = 'https://';
				$imgDomain .= $_SERVER['SERVER_NAME'];
				if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $imgDomain .= ':'.$_SERVER["SERVER_PORT"];
			}
			foreach($imgArr as $cnt => $iArr){
				//Regular url
				$url = $iArr['web'];
				if(substr($url,0,1) == '/') $url = $imgDomain.$url;
				echo 'imgArr['.$cnt.'] = "'.$url.'";'."\n";
				//Large Url
				$lgUrl = $iArr['lg'];
				if($lgUrl){
					if(substr($lgUrl,0,1) == '/') $lgUrl = $imgDomain.$lgUrl;
					echo 'imgLgArr['.$cnt.'] = "'.$lgUrl.'";'."\n";
				}
			}
			?>

			$(document).ready(function() {
				setImgRes();
				$("#specimg").imagetool({
					maxWidth: 6000
					,viewportWidth: <?php echo $paneX; ?>
			        ,viewportHeight: <?php echo $paneY; ?>
				});
			});

			function setImgRes(){
				if(imgLgArr[activeImgIndex] != null){
					if($("#imgres1").val() == 'lg') changeImgRes('lg');
				}
				else{
					if(imgArr[activeImgIndex] != null){
						$("#specimg").attr("src",imgArr[activeImgIndex]);
						document.getElementById("imgresmed").checked = true;
						var imgResLgRadio = document.getElementById("imgreslg");
						imgResLgRadio.disabled = true;
						imgResLgRadio.title = "Large resolution image not available";
					}
				}
				if(imgArr[activeImgIndex] != null){
					//Do nothing
				}
				else{
					if(imgLgArr[activeImgIndex] != null){
						$("#specimg").attr("src",imgLgArr[activeImgIndex]);
						document.getElementById("imgreslg").checked = true;
						var imgResMedRadio = document.getElementById("imgresmed");
						imgResMedRadio.disabled = true;
						imgResMedRadio.title = "Medium resolution image not available";
					}
				}
			}

			function changeImgRes(resType){
				if(resType == 'lg'){
					$("#imgres1").val("lg");
					$("#imgres2").val("lg");
			    	if(imgLgArr[activeImgIndex]){
						$("#specimg").attr("src",imgLgArr[activeImgIndex]);
						$( "#imgreslg" ).prop( "checked", true );
			    	}
				}
				else{
					$("#imgres1").val("med");
					$("#imgres2").val("med");
			    	if(imgArr[activeImgIndex]){
						$("#specimg").attr("src",imgArr[activeImgIndex]);
						$( "#imgresmed" ).prop( "checked", true );
			    	}
				}
			}

			function setPortXY(portWidth,portHeight){
				$("#panex1").val(portWidth);
				$("#paney1").val(portHeight);
				$("#panex2").val(portWidth);
				$("#paney2").val(portHeight);
			}

			function nextImage(){
				activeImgIndex = activeImgIndex + 1;
				if(activeImgIndex >= imgArr.length) activeImgIndex = 1;
				$("#specimg").attr("src",imgArr[activeImgIndex]);
				$("#specimg").imagetool({
					maxWidth: 6000
					,viewportWidth: $("#panex1").val()
			        ,viewportHeight: $("#paney1").val()
				});
				//setImgRes();
				$("#labelcnt").html(activeImgIndex);
				return false;
			}

			function skipSpecimen(){
				$("#filterform").submit();
			}

			function verifyFilterForm(f){
				if(f.traitid.value == ""){
					alert("An occurrence trait must be selected");
					return false;
				}
				if(f.taxonfilter.value != "" && f.tidfilter.value == ""){
					alert("Taxon filter not syncronized with thesaurus");
					return false;
				}
				return true;
			}

			function verifyReviewForm(f){
				if(f.traitid.value == ""){
					alert("An occurrence trait must be selected");
					return false;
				}
				return true;
			}

			function nextReviewRecord(startValue){
				var f = document.getElementById("reviewform");
				f.start.value = startValue;
				f.submit();
			}

			function verifySubmitForm(f){

				return true;
			}

		</script>
		<script src="../../js/symb/collections.traitattr.js" type="text/javascript"></script>
		<script src="../../js/symb/shared.js?ver=151229" type="text/javascript"></script>
	</head>
	<body>
		<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/header.php');
		if($isEditor == 2){
			echo '<div style="float:right;margin:0px 3px;font-size:90%">';
			if($mode == 1){
				echo '<a href="occurattributes.php?collid='.$collid.'&mode=2&traitid='.$traitID.'"><img src="../../images/edit.png" style="" />review</a>';
			}
			else{
				echo '<a href="occurattributes.php?collid='.$collid.'&mode=1&traitid='.$traitID.'"><img src="../../images/edit.png" style="" />edit</a>';
			}
			echo '</div>';
		}
		?>
		<div class="navpath">
			<a href="../../index.php">Home</a> &gt;&gt; 
			<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1">Collection Management</a> &gt;&gt;
			<?php 
			if($mode == 2){
				echo '<b>Attribute Reviewer</b>';
			}
			else{
				echo '<b>Attribute Editor</b>';
			}
			?>
		</div>
		<?php 
		if($statusStr){
			echo '<div style="color:red">';
			echo $statusStr;
			echo '</div>';
		}
		?>
		<!-- This is inner text! -->
		<div id="innertext" style="position:relative;">
		<?php
		if($collid){
			?>
			<div style="float:right;width:290px;">
				<?php
				$attrNameArr = $attrManager->getTraitNames();
				if($mode == 1){ 
					?>
					<fieldset style="margin-top:25px">
						<legend><b>Filter</b></legend>
						<form id="filterform" name="filterform" method="post" action="occurattributes.php" onsubmit="return verifyFilterForm(this)" >
							<div>
								<b>Taxon: </b>
								<input id="taxonfilter" name="taxonfilter" type="text" value="<?php echo $taxonFilter; ?>" />
								<input id="tidfilter" name="tidfilter" type="hidden" value="<?php echo $tidFilter; ?>" />
							</div>
							<div>
								<select name="traitid">
									<option value="">Select Trait</option>
									<option value="">------------------------------------</option>
									<?php 
									if($attrNameArr){
										foreach($attrNameArr as $ID => $aName){
											echo '<option value="'.$ID.'" '.($traitID==$ID?'SELECTED':'').'>'.$aName.'</option>';
										}
									}
									else{
										echo '<option value="0">No attributes are available</option>';
									}
									?>
								</select>
							</div>
							<div>
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<input id="panex1" name="panex" type="hidden" value="<?php echo $paneX; ?>" />
								<input id="paney1" name="paney" type="hidden" value="<?php echo $paneY; ?>" />
								<input id="imgres1"  name="imgres" type="hidden" value="<?php echo $imgRes; ?>" />
								<input id="filtersubmit" name="submitform" type="submit" value="Load Images" />
								<span id="verify-span" style="display:none;font-weight:bold;color:green;">verifying taxonomy...</span>
								<span id="notvalid-span" style="display:none;font-weight:bold;color:red;">taxon not valid...</span>
							</div>
							<div style="margin:10px">
								<?php if($traitID) echo '<b>Target Specimens:</b> '.$attrManager->getSpecimenCount(); ?>
							</div>
						</form>
					</fieldset>
				<?php
				} 
				elseif($mode == 2){
					?>
					<fieldset style="margin-top:25px">
						<legend><b>Reviewer</b></legend>
						<form id="reviewform" name="reviewform" method="post" action="occurattributes.php" onsubmit="return verifyReviewForm(this)" >
							<div style="margin:3px">
								<select name="traitid">
									<option value="">Select Trait</option>
									<option value="">------------------------------------</option>
									<?php 
									if($attrNameArr){
										foreach($attrNameArr as $ID => $aName){
											echo '<option value="'.$ID.'" '.($traitID==$ID?'SELECTED':'').'>'.$aName.'</option>';
										}
									}
									else{
										echo '<option value="0">No attributes are available</option>';
									}
									?>
								</select>
							</div>
							<div style="margin:3px">
								<select name="reviewuid">
									<option value="">All Editors</option>
									<option value="">-----------------------</option>
									<?php 
									$editorArr = $attrManager->getEditorArr();
									foreach($editorArr as $uid => $name){
										echo '<option value="'.$uid.'" '.($uid==$reviewUid?'SELECTED':'').'>'.$name.'</option>';
									}
									?>
								</select>
							</div>
							<div style="margin:3px">
								<select name="reviewdate">
									<option value="">All Dates</option>
									<option value="">-----------------------</option>
									<?php 
									$dateArr = $attrManager->getEditDates();
									foreach($dateArr as $date){
										echo '<option '.($date==$reviewDate?'SELECTED':'').'>'.$date.'</option>';
									}
									?>
								</select>
							</div>
							<div style="margin:3px">
								<select name="reviewstatus">
									<option value="0">Not reviewed</option>
									<option value="5" <?php echo  ($reviewStatus==5?'SELECTED':''); ?>>Expert Needed</option>
									<option value="10" <?php echo  ($reviewStatus==10?'SELECTED':''); ?>>Reviewed</option>
								</select>
							</div>
							<div style="margin:10px;">
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<input id="panex1" name="panex" type="hidden" value="<?php echo $paneX; ?>" />
								<input id="paney1" name="paney" type="hidden" value="<?php echo $paneY; ?>" />
								<input id="imgres1" name="imgres" type="hidden" value="<?php echo $imgRes; ?>" />
								<input name="mode" type="hidden" value="2" />
								<input name="start" type="hidden" value="" />
								<input name="submitform" type="submit" value="Get Images" />
							</div>
							<div>
								<?php 
								if($traitID){
									$rCnt = $attrManager->getReviewCount($traitID, $reviewUid, $reviewDate, $reviewStatus);
									echo '<b>'.($rCnt?$start+1:0).' of '.$rCnt.' records</b>';
									if($rCnt > 1){
										$next = ($start+1);
										if($next >= $rCnt) $next = 0; 
										echo ' (<a href="#" onclick="nextReviewRecord('.($next).')">Next record &gt;&gt;</a>)';
									} 
								} 
								?>
							</div>
						</form>
					</fieldset>
					<?php
				} 
				if($imgArr){
					$traitArr = $attrManager->getTraitArr($traitID,($mode==2?true:false));
					?>
					<fieldset style="margin-top:20px">
						<legend><b>Action Panel - <?php echo $traitArr[$traitID]['name']; ?></b></legend>
						<form name="submitform" method="post" action="occurattributes.php" onsubmit="return verifySubmitForm(this)" >
							<div>
								<?php 
								$attrManager->echoFormTraits($traitID);
								?>
							</div>
							<div style="margin-left:5;">
								Status: 
								<select name="setstatus">
									<?php
									if($mode == 2){
										?>
										<option value="0">Not reviewed</option>
										<option value="5">Expert Needed</option>
										<option value="10" selected>Reviewed</option>
										<?php
									}
									else{
										?>
										<option value="0">---------------</option>
										<option value="5">Expert Needed</option>
										<?php
									} 
									?>
								</select>
							</div>
							<div style="margin:20px">
								<input name="taxonfilter" type="hidden" value="<?php echo $taxonFilter; ?>" />
								<input name="tidfilter" type="hidden" value="<?php echo $tidFilter; ?>" />
								<input name="traitid" type="hidden" value="<?php echo $traitID; ?>" />
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<input id="panex2" name="panex" type="hidden" value="<?php echo $paneX; ?>" />
								<input id="paney2" name="paney" type="hidden" value="<?php echo $paneY; ?>" />
								<input id="imgres2" name="imgres" type="hidden" value="<?php echo $imgRes; ?>" />
								<input name="targetoccid" type="hidden" value="<?php echo $occid; ?>" />
								<input name="mode" type="hidden" value="<?php echo $mode; ?>" />
								<input name="reviewuid" type="hidden" value="<?php echo $reviewUid; ?>" /> 
								<input name="reviewdate" type="hidden" value="<?php echo $reviewDate; ?>" /> 
								<input name="reviewstatus" type="hidden" value="<?php echo $reviewStatus; ?>" /> 
								<?php 
								if($mode == 2){
									echo '<input name="submitform" type="submit" value="Set Status and Save" />';
								}
								else{
									echo '<input name="submitform" type="submit" value="Save and Next" disabled />';
								}
								?>
							</div>
						</form>
					</fieldset>
					<?php
				} 
				?>
			</div>
			<div style="height:600px">
				<?php 
				if($imgArr){
					?>
					<div>
						<span><input id="imgresmed" name="resradio"  type="radio" checked onchange="changeImgRes('med')" />Med Res.</span>
						<span style="margin-left:6px;"><input id="imgreslg" name="resradio" type="radio" onchange="changeImgRes('lg')" />High Res.</span>
						<?php 
						if($occid){
							if(!$catNum) $catNum = 'Specimen Details';
							echo '<span style="margin-left:50px;">';
							echo '<a href="../individual/index.php?occid='.$occid.'" target="_blank" title="Specimen Details">'.$catNum.'</a>';
							echo '</span>';
						}
						$imgTotal = count($imgArr);
						if($imgTotal > 1) echo '<span id="labelcnt" style="margin-left:60px;">1</span> of '.$imgTotal.' images '.($imgTotal>1?'<a href="#" onclick="nextImage()">&gt;&gt; next</a>':'');
						if($occid && $mode != 2) echo '<span style="margin-left:80px" title="Skip Specimen"><a href="#" onclick="skipSpecimen()">SKIP &gt;&gt;</a></span>';
						?>
					</div>
					<div>
						<?php
						$url = $imgArr[1]['web'];
						if(substr($url,0,1) == '/') $url = $imgDomain.$url;
						echo '<img id="specimg" src="'.$url.'" />';
						?>
					</div>
					<?php
				}
				else{
					if($submitForm)
						echo '<div style="margin:50px;color:red;font-weight:bold;font-size:140%">No images available matching taxon search criteria</div>';
					else 
						echo '<div style="margin:50px;font-weight:bold;font-size:140%">Select a trait and submit filter in the form to the right to display images that have not yet been scored</div>';
				}
				?>
			</div>
			<?php
		}
		else{
			echo '<div><b>ERROR: collection identifier is not set</b></div>';
		} 
		?>
		</div>
		<?php 
		include($SERVER_ROOT.'/footer.php');
		?>
	</body>
</html>