<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorAttr.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../collections/editor/extras/batchattributes.php?'.$_SERVER['QUERY_STRING']);

$collid = $_REQUEST['collid'];
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';
$attrID = array_key_exists('attrid',$_POST)?$_POST['attrid']:'';
$taxonFilter = array_key_exists('taxonfilter',$_POST)?$_POST['taxonfilter']:'';
$tidFilter = array_key_exists('tidfilter',$_POST)?$_POST['tidfilter']:0;

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($tidFilter)) $tidFilter = 0;

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

if($isEditor){
	if($formSubmit == 'Save Data'){

	}
}

?>
<html>
	<head>
		<title>Occurrence Attribute batch Editor</title>
		<link href="../../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<script src="../../../js/jquery.js" type="text/javascript"></script>
		<script src="../../../js/jquery-ui.js" type="text/javascript"></script>
		<script type="text/javascript">
			$(document).ready(function() {
				$("#taxonfilter").autocomplete({ 
					source: "rpc/getTaxonFilter.php", 
					dataType: "json",
					minLength: 3,
					select: function( event, ui ) {
						$("#tidfilter").val(ui.item.id);
					},
					change: function(event, ui){
						if($("#tidfilter").val() == ""){
							//get tid using ajax	

							alert("Name not found");
						}
						//if($("#tidfilter").val() == "") $("#tidfilter").val(ui.item.id)
					}
				});

				$("#taxonfilter").focus(function(){$("#tidfilter").val("");});
			});

			function verifyFilterForm(f){
				if(f.attrname.value == ""){
					alert("An occurrence trait must be selected");
					return false;
				}
			}
		</script>
		<script src="../../../js/symb/shared.js?ver=151229" type="text/javascript"></script>
	</head>
	<body>
		<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/header.php');
		?>
		<div class="navpath">
			<a href="../../../index.php">Home</a> &gt;&gt; 
			<a href="../../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1">Collection Management</a> &gt;&gt;
			<b>Attribute Editor</b>
		</div>
		<!-- This is inner text! -->
		<div id="innertext">
			<div style="float:right;width:250px;">
				<fieldset>
					<legend>Filter</legend>
					<form name="filterform" method="post" action="batchattributes.php" onsubmit="return verifyFilterForm(this)" >
						<div>
							<b>Taxon: </b>
							<input id="taxonfilter" name="taxonfilter" type="text" value="<?php echo $taxonFilter; ?>" />
							<input id="tidfilter" name="tidfilter" type="text" value="<?php echo $tidFilter; ?>" />
						</div>
						<div>
							<select name="attrid">
								<option value="">Select Trait</option>
								<?php 
								$attrNameArr = $attrManager->getAttrNames();
								if($attrNameArr){
									foreach($attrNameArr as $ID => $aName){
										echo '<option value="'.$ID.'" '.($attrID==$ID?'SELECTED':'').'>'.$aName.'</option>';
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
							<input name="filtersubmit" type="submit" value="Load Images" />
						</div>
					</form>
				</fieldset>
			</div>
			<div>
				<?php 
				if($attrID){
					?>
					<div style="width:100%;height:500px">
						<?php 
						$imgArr = $attrManager->getImageUrls();
						$cnt = 1;
						foreach($imgArr as $occid => $imgArr2){
							$imgTotal = count($imgArr2);
							foreach($imgArr2 as $imgid => $imgArr3){
								$imgUrl = $imgArr3['lgurl'];
								if(!$imgUrl) $imgUrl = $imgArr3['url'];
								if(substr($imgUrl,0,1) == '/'){
									if($IMAGE_DOMAIN) $imgUrl = $IMAGE_DOMAIN.$imgUrl;
								}
								echo '<div style="display:'.($cnt==1?'block':'none').'">';
								echo '<div>';
								echo '<a href="'.$imgUrl.'" target="_blank"><img src="'.$imgUrl.'" style="width:900px;" /></a>';
								echo '</div>';
								echo '<div>'.$cnt.' of '.$imgTotal.'</div>';
								echo '</div>';
								$cnt++;
							}
						}
						?>
					</div>
					<div>
						<div>
							<select name="stateid">
								<?php 
								$attrStateArr = $attrManager->getAttrStates($attrID);
								foreach($attrStateArr as $sid => $sName){
									echo '<option '.$sid.'>'.$sName.'</option>';
								}
								?>
							</select>
						</div>
						<div>
							<a href="../../individual/index.php?occid=<?php echo $imgArr['occid']; ?>" target="_blank"/>Specimen Details</a>
						</div>
					</div>
					<?php
				}
				?>
			</div>
		</div>
	</body>
</html>