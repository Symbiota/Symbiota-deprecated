<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorAttr.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../collections/traitattr/occurattributes.php?'.$_SERVER['QUERY_STRING']);

$collid = $_REQUEST['collid'];
$submitForm = array_key_exists('submitform',$_POST)?$_POST['submitform']:'';
$traitID = array_key_exists('traitid',$_POST)?$_POST['traitid']:'';
$taxonFilter = array_key_exists('taxonfilter',$_POST)?$_POST['taxonfilter']:'';
$tidFilter = array_key_exists('tidfilter',$_POST)?$_POST['tidfilter']:'';
$paneX = array_key_exists('panex',$_POST)?$_POST['panex']:'600';
$paneY = array_key_exists('paney',$_POST)?$_POST['paney']:'500';
$imgRes = array_key_exists('imgres',$_POST)?$_POST['imgres']:'med';

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($traitID)) $traitID = '';
if(!is_numeric($tidFilter)) $tidFilter = '';
if(!is_numeric($paneX)) $paneX = '';
if(!is_numeric($paneY)) $paneY = '';

$isEditor = 0; 
if($SYMB_UID){
	if($IS_ADMIN){
		$isEditor = 1;
	}
	elseif($collid){
		//If a page related to collections, one maight want to... 
		if(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])){
			$isEditor = 1;
		}
		elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollEditor"])){
			$isEditor = 1;
		}
	}
}

$attrManager = new OccurrenceEditorAttr();
if($tidFilter) $attrManager->setTidFilter($tidFilter);
if($collid) $attrManager->setCollid($collid);

$statusStr = '';
if($isEditor){
	if($submitForm == 'Save and Next'){
		$stateID = $_POST['stateid'];
		$targetOccid = $_POST['targetoccid'];
		if(is_array($stateID)){
			foreach($stateID as $id){
				if(!$attrManager->saveAttributes($id,$targetOccid,$SYMB_UID)){
					$statusStr = $attrManager->getErrorStr();
				}
			}
		}
		else{
			$attrManager->saveAttributes($stateID,$targetOccid,$SYMB_UID);
		}
	}
}
$imgArr = array();
$occid = 0;
if($traitID){
	$traitArr = $attrManager->getTraitArr($traitID);
	$imgRetArr = $attrManager->getImageUrls();
	$imgArr = current($imgRetArr);
	$occid = key($imgRetArr);
}
$imgTotal = count($imgArr);
?>
<html>
	<head>
		<title>Occurrence Attribute batch Editor</title>
		<link href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
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
				if(!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) $imgDomain = 'https://';
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

				$("#taxonfilter").autocomplete({ 
					source: "rpc/getTaxonFilter.php", 
					dataType: "json",
					minLength: 3,
					select: function( event, ui ) {
						$("#tidfilter").val(ui.item.id);
					}
				});

				$("#taxonfilter").change(function(){
					$("#tidfilter").val("");
					$( "#filtersubmit" ).prop( "disabled", true );
					$( "#verify-span" ).show();
					$( "#notvalid-span" ).hide();

					$.ajax({
						type: "POST",
						url: "rpc/getTaxonFilter.php",
						data: { term: $( this ).val(), exact: 1 }
					}).done(function( msg ) {
						if(msg == ""){
							$( "#notvalid-span" ).show();
						}
						else{
							$("#tidfilter").val(msg[0].id);
						}
						$( "#filtersubmit" ).prop( "disabled", false );
						$( "#verify-span" ).hide();
					});
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

			function verifyFilterForm(f){
				if(f.traitid.value == ""){
					alert("You must select a trait");
					return false;
				}
				return true;
			}

			function verifySubmitForm(f){

				return true;
			}
		</script>
		<script src="../../js/symb/shared.js?ver=151229" type="text/javascript"></script>
	</head>
	<body style="width:900px">
		<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/header.php');
		?>
		<div class="navpath">
			<a href="../../index.php">Home</a> &gt;&gt; 
			<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1">Collection Management</a> &gt;&gt;
			<b>Attribute Editor</b>
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
			<div style="position:absolute;top:0px;right:20px;width:250px;">
				<fieldset style="margin-top:20px">
					<legend><b>Filter</b></legend>
					<form name="filterform" method="post" action="occurattributes.php" onsubmit="return verifyFilterForm(this)" >
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
								$attrNameArr = $attrManager->getTraitNames();
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
					</form>
				</fieldset>
				<?php 
				if($traitID){
					?>
					<fieldset style="margin-top:20px">
						<legend><b>Action Panel</b></legend>
						<form name="submitform" method="post" action="occurattributes.php" onsubmit="return verifySubmitForm(this)" >
							<div>
								<?php 
								$controlType = 'checkbox';
								if($traitArr['props']){
									$propArr = json_decode($traitArr['props']);
									if(isset($propArr['controlType'])) $controlType = $propArr['controlType'];
								}
								$attrStateArr = $attrManager->getTraitStates($traitID);
								if($controlType == 'checkbox'){
									foreach($attrStateArr as $sid => $sArr){
										echo '<div title="'.$sArr['description'].'"><input name="stateid[]" type="checkbox" value="'.$sid.'" /> '.$sArr['name'].'</div>';
									}
								}
								elseif($controlType == 'radio'){
									foreach($attrStateArr as $sid => $sArr){
										echo '<div title="'.$sArr['description'].'"><input name="stateid[]" type="radio" value="'.$sid.'" /> '.$sArr['name'].'</div>';
									}
								}
								elseif($controlType == 'select'){
									echo '<select name="stateid">';
									echo '<option value="">Select State</option>';
									echo '<option value="">------------------------------</option>';
									foreach($attrStateArr as $sid => $sArr){
										echo '<option value="'.$sid.'">'.$sArr['name'].'</option>';
									}
									echo '</select>';
								}
								?>
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
								<input name="submitform" type="submit" value="Save and Next" />
							</div>
						</form>
					</fieldset>
					<?php
				} 
				?>
			</div>
			<div>
				<div>
					<span><input id="imgresmed" name="resradio"  type="radio" checked onchange="changeImgRes('med')" />Med Res.</span>
					<span style="margin-left:6px;"><input id="imgreslg" name="resradio" type="radio" onchange="changeImgRes('lg')" />High Res.</span>
					<?php 
					if($occid) echo '<span style="margin-left:60px;"><a href="../individual/index.php?occid='.$occid.'" target="_blank"/>Specimen Details</a></span>';
					echo '<span id="labelcnt" style="margin-left:60px;">1</span> of '.$imgTotal.' images '.($imgTotal>1?'<a href="#" onclick="nextImage()">&gt;&gt; next</a>':'');
					?>
				</div>
				<?php 
				if($imgArr){
					?>
					<div>
						<?php
						$url = $imgArr[1]['web'];
						if(substr($url,0,1) == '/') $url = $imgDomain.$url;
						echo '<img id="specimg" src="'.$url.'" />';
						?>
					</div>
					<?php
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
	</body>
</html>