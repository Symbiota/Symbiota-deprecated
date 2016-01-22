<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorAttr.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../collections/editor/extras/attributemining.php?'.$_SERVER['QUERY_STRING']);

$collid = $_REQUEST['collid'];
$attrID = array_key_exists('attrid',$_POST)?$_POST['attrid']:'';
$taxonFilter = array_key_exists('taxonfilter',$_POST)?$_POST['taxonfilter']:'';
$tidFilter = array_key_exists('tidfilter',$_POST)?$_POST['tidfilter']:'';
$fieldName = array_key_exists('fieldname',$_POST)?$_POST['fieldname']:'';
$traitID = array_key_exists('traitid',$_POST)?$_POST['traitid']:'';
$submitForm = array_key_exists('submitform',$_POST)?$_POST['submitform']:'';

//Sanitation
if(!is_numeric($collid)) $collid = 0;
if(!is_numeric($attrID)) $attrID = 0;
if(!is_numeric($tidFilter)) $tidFilter = 0;
if(!is_numeric($traitID)) $traitID = 0;

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

$statusStr = '';
if($isEditor){
	if($submitForm == 'Batch Assign State'){
		$stateID = array_key_exists('stateid',$_POST)?$_POST['stateid']:'';
		$fieldValue = array_key_exists('fieldvalue',$_POST)?$_POST['fieldvalue']:'';
		if($collid && $stateID && $fieldName && $fieldValue){
			if(!$attrManager->submitBatchAttributes($collid, $stateID, $fieldName, $fieldValue, $SYMB_UID)){
				$statusStr = $attrManager->getErrorMessage();
			}
		}
	}
}

?>
<html>
	<head>
		<title>Occurrence Attribute Mining Tool</title>
		<link href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
		<script src="../../js/jquery.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui.js" type="text/javascript"></script>
		<script type="text/javascript">
			$(document).ready(function() {
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
					$.ajax({
						type: "POST",
						url: "rpc/getTaxonFilter.php",
						data: { term: $( this ).val(), exact: 1 }
					}).done(function( msg ) {
						if(msg == ""){
							alert("Taxon not found in taxonomicthesaurus");
						}
						else{
							$("#tidfilter").val(msg[0].id);
						}
					});
				});
			});

			function verifyFilterForm(f){
				if(f.attrid.value == ""){
					alert("You must select a trait");
					return false;
				}
				if(f.fieldname.value == ""){
					alert("A target field must be selected");
					return false;
				}
				return true;
			}

			function verifyMiningForm(f){
				if(f.stateid.value == ""){
					alert("You must select a trait state");
					return false;
				}
				if(f.fieldvalue.value == ""){
					alert("You muct select a target field value");
					return false;
				}
				return true;
			}
		</script>
	</head>
	<body style="width:900px">
		<?php
		$displayLeftMenu = false;
		include($SERVER_ROOT.'/header.php');
		?>
		<div class="navpath">
			<a href="../../index.php">Home</a> &gt;&gt; 
			<a href="../misc/collprofiles.php?collid=<?php echo $collid; ?>&emode=1">Collection Management</a> &gt;&gt;
			<b>Attribute Mining Tool</b>
		</div>
		<?php 
		if($statusStr){
			echo '<div style="color:red">';
			echo $statusStr;
			echo '</div>';
		}
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<div style="float:right;width:250px;">
				<fieldset style="margin:20px">
					<legend><b>Filter</b></legend>
					<form name="filterform" method="post" action="occurattributes.php" onsubmit="return verifyFilterForm(this)" >
						<div>
							<b>Taxon: </b>
							<input id="taxonfilter" name="taxonfilter" type="text" value="<?php echo $taxonFilter; ?>" />
							<input id="tidfilter" name="tidfilter" type="hidden" value="<?php echo $tidFilter; ?>" />
						</div>
						<div>
							<select name="attrid">
								<option value="">Select Trait</option>
								<option value="">------------------------------------</option>
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
							<select name="fieldname">
								<option value="">Select Target Field</option>
								<option value="">------------------------------------</option>
								<?php 
								$fieldArr = array('habitat' => 'habitat', 'substrate' => 'substrate', 'occurrenceremarks' => 'occurrenceRemarks (notes)',
									'dynamicproperties' => 'dynamicProperties', 'verbatimattributes' => 'verbatimAttributes (description)',
									'behavior' => 'behavior', 'reproductivecondition' => 'reproductiveCondition', 'lifestage' => 'lifeStage', 
									'sex' => 'sex');
								foreach($fieldArr as $k => $fName){
									echo '<option value="'.$k.'" '.($k==$fieldName?'SELECTED':'').'>'.$fName.'</option>';
								}
								?>
							</select>
						</div>
						<div>
							<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
							<input name="submitform" type="submit" value="Get Field Values" />
						</div>
					</form>
				</fieldset>
			</div>
			<?php 
			if($traitID && $fieldName){
				?>
				<div>
					<fieldset>
						<legend><b>Batch Attribute Assignment</b></legend>
						<form name="miningform" method="post" action="attributemining.php" onsubmit="return verifyMiningForm(this)">
							<div>
								<select name="stateid">
									<option value="">Select Trait State</option>
									<option value="">-----------------------------</option>
									<?php 
									$stateArr = $attrManager->getAttrStates($traitID);
									foreach($stateArr as $sid => $stateName){
										echo '<option value="'.$sid.'">'.$stateName.'</option>';
									}
									?>
								</select>
							</div>
							<div>
								<b><?php echo $fieldName; ?>:</b> 
								<select name="fieldvalue">
									<option value="">Select Target Field Value</option>
									<option value="">--------------------------------</option>
									<?php 
									$valueArr = $attrManager->getFieldValueArr($collid, $traitID, $fieldName);
									foreach($valueArr as $v){
										echo '<option>'.$v.'</option>';
									}
									?>
								</select>
							</div>
							<div>
								<input name="formsubmit" type="submit" value="Batch Assign State" />
							</div>
						</form>
					</fieldset>
				</div>
				<?php
			} 
			?> 
		</div>
	</body>
</html>