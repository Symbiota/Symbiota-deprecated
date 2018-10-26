<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/editor/batchdeterminations.php?'.$_SERVER['QUERY_STRING']);

$collid = $_REQUEST["collid"];
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$occManager = new OccurrenceEditorDeterminations();
$occManager->setCollId($collid);
$occManager->getCollMap();

$isEditor = 0;
$occArr = array();
if($IS_ADMIN || (array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"]))){
	$isEditor = 1;
}
elseif(array_key_exists("CollEditor",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollEditor"])){
	$isEditor = 1;
}
$statusStr = '';
if($isEditor){
	if($formSubmit == 'Add New Determinations'){
		$occidArr = $_REQUEST['occid'];
		foreach($occidArr as $k){
			$occManager->setOccId($k);
			$occManager->addDetermination($_REQUEST,$isEditor);
		}
		$statusStr = 'SUCCESS: '.count($occidArr).' annotations submitted';
	}
	elseif($formSubmit == 'Adjust Nomenclature'){
		$occidArr = $_REQUEST['occid'];
		foreach($occidArr as $k){
			$occManager->setOccId($k);
			$occManager->addNomAdjustment($_REQUEST,$isEditor);
		}
	}
}
?>

<html>
	<head>
	    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>">
		<title><?php echo $DEFAULT_TITLE; ?> Batch Determinations/Nomenclatural Adjustments</title>
		<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/jquery-ui.css" type="text/css" rel="Stylesheet" />
		<script src="../../js/jquery.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui.js" type="text/javascript"></script>
		<script>
			function initScinameAutocomplete(f){
				$( f.sciname ).autocomplete({
					source: "rpc/getspeciessuggest.php",
					minLength: 3,
					change: function(event, ui) {
					}
				});
			}

			function initDetAutocomplete(f){
				$( f.sciname ).autocomplete({
					source: "rpc/getspeciessuggest.php",
					minLength: 3,
					change: function(event, ui) {
						if(f.sciname.value){
							pauseSubmit = true;
							verifyDetSciName(f);
						}
						else{
							f.scientificnameauthorship.value = "";
							f.family.value = "";
							f.tidtoadd.value = "";
						}
					}
				});
			}

			function submitAccForm(f){
				if(f.catalognumber.value == "") document.getElementById("workingcircle").style.display = "inline";

				$.ajax({
					type: "POST",
					url: "rpc/getnewdetitem.php",
					dataType: "json",
					async: false,
					data: {
						catalognumber: f.catalognumber.value,
						sciname: f.sciname.value,
						collid: f.collid.value
					}
				}).done(function( retStr ) {
					if(retStr){
						for (var occid in retStr) {
							var occObj = retStr[occid];
							if(f.catalognumber.value && checkCatalogNumber(occid, occObj["cn"])){
								alert("Record already exists within list");
							}
							else{
								var trNode = createNewTableRow(occid, occObj);
								var tableBody = document.getElementById("catrecordstbody");
								tableBody.insertBefore(trNode, tableBody.firstElementChild);
							}
						}
					}
					else{
						alert("No records returned matching search criteria");
					}
				});

				if(f.catalognumber.value == ""){
					document.getElementById("workingcircle").style.display = "none";
				}
				else{
					f.catalognumber.value = '';
					f.catalognumber.focus();
				}
				document.getElementById("accrecordlistdviv").style.display = "block";
				return false;
			}

			function checkCatalogNumber(catNum){
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					if(dbElements[i].value == catNum) return true;
				}
				return false;
			}

			function createNewTableRow(occid, occObj){
				var trNode = document.createElement("tr");
				var inputNode = document.createElement("input");
				inputNode.setAttribute("type", "checkbox");
				inputNode.setAttribute("name", "occid[]");
				inputNode.setAttribute("value", occid);
				inputNode.setAttribute("checked", "checked");
				var tdNode1 = document.createElement("td");
				tdNode1.appendChild(inputNode);
				trNode.appendChild(tdNode1);
				var tdNode2 = document.createElement("td");
				var anchor1 = document.createElement("a");
				anchor1.setAttribute("href","#");
				anchor1.setAttribute("onclick","openIndPopup("+occid+"); return false;");
				if(occObj["cn"] != "") anchor1.innerHTML = occObj["cn"];
				else anchor1.innerHTML = "[no catalog number]";
				tdNode2.appendChild(anchor1);
				var anchor2 = document.createElement("a");
				anchor2.setAttribute("href","#");

				tdNode2.appendChild(anchor2);
				trNode.appendChild(tdNode2);
				var tdNode3 = document.createElement("td");
				tdNode3.appendChild(document.createTextNode(occObj["sn"]));
				trNode.appendChild(tdNode3);
				var tdNode4 = document.createElement("td");
				tdNode4.appendChild(document.createTextNode(occObj["coll"]+'; '+occObj["loc"]));
				trNode.appendChild(tdNode4);
				return trNode;
			}

			function clearAccForm(f){
				if(confirm("Clearing the form will reset the process. Are you sure you want to do this?") == true){
					document.getElementById("accrecordlistdviv").style.display = "none";
					document.getElementById("catrecordstbody").innerHTML = '';
					f.catalognumber.value = '';
					f.sciname.value = '';
				}
			}

			function validateSelectForm(f){
				var specNotSelected = true;
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					if(dbElement.checked){
						specNotSelected = false;
						break;
					}
				}
				if(specNotSelected){
					alert("Please select at least one specimen!");
					return false;
				}

				if(f.sciname.value == ""){
					alert("Scientific Name field must have a value");
					return false;
				}
				if(f.identifiedby.value == ""){
					alert("Determiner field must have a value (enter 'unknown' if not defined)");
					return false;
				}
				if(f.dateidentified.value == ""){
					alert("Determination Date field must have a value (enter 's.d.' if not defined)");
					return false;
				}
				return true;
			}

			function selectAll(cb){
				boxesChecked = true;
				if(!cb.checked){
					boxesChecked = false;
				}
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					dbElement.checked = boxesChecked;
				}
			}

			function annotationTypeChanged(selectElem){
				var f = selectElem.form;
				if(selectElem.value == "na"){
					f.identificationqualifier.value = "";
					$("#idQualifierDiv").hide();
					f.confidenceranking.value = "";
					$("#codDiv").hide();
					f.identifiedby.value = "Nomenclatural Adjustment";
					f.identifiedby.readonly = true;
					f.makecurrent.checked = true;

					var today = new Date();
					var month = (today.getMonth() + 1);
					var day = today.getDate();
					var year = today.getFullYear();
					if(month < 10) month = '0' + month;
					if(day < 10) day = '0' + day;
					f.dateidentified.value = [year, month, day].join('-');
				}
				else{
					$("#idQualifierDiv").show();
					f.confidenceranking.value = 5;
					$("#codDiv").show();
					f.identifiedby.value = "";
					f.identifiedby.readonly = true;
					f.dateidentified.value = "";
					f.makecurrent.checked = false;
				}
			}

			function verifyDetSciName(f){
				$.ajax({
					type: "POST",
					url: "rpc/verifysciname.php",
					dataType: "json",
					data: { term: f.sciname.value }
				}).done(function( data ) {
					if(data){
						f.scientificnameauthorship.value = data.author;
						f.family.value = data.family;
						f.tidtoadd.value = data.tid;
					}
					else{
						alert("WARNING: Taxon not found. It may be misspelled or needs to be added to taxonomic thesaurus by a taxonomic editor. If taxon name is correct, continue with the annotation submission and the taxonomic thesaurus can be adjusted later.");
						f.scientificnameauthorship.value = "";
						f.family.value = "";
						f.tidtoadd.value = "";
					}
				});
			}

			function openIndPopup(occid){
				openPopup('../individual/index.php?occid=' + occid);
			}

			function openEditorPopup(occid){
				openPopup('occurrenceeditor.php?occid=' + occid);
			}

			function openPopup(urlStr){
				var wWidth = 900;
				if(document.getElementById('maintable').offsetWidth){
					wWidth = document.getElementById('maintable').offsetWidth*1.05;
				}
				else if(document.body.offsetWidth){
					wWidth = document.body.offsetWidth*0.9;
				}
				newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=0,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
				if (newWindow.opener == null) newWindow.opener = self;
				return false;
			}
		</script>
	</head>
	<body>
	<?php
	$displayLeftMenu = (isset($collections_batchdeterminationsMenu)?$collections_batchdeterminationsMenu:false);
	include($SERVER_ROOT."/header.php");
	?>
	<div class='navpath'>
		<a href='../../index.php'>Home</a> &gt;&gt;
		<?php
		if(isset($collections_batchdeterminationsMenuCrumbs)){
			echo $collections_batchdeterminationsMenuCrumbs;
		}
		else{
			echo '<a href="../misc/collprofiles.php?collid='.$collid.'&emode=1">Collection Management Panel</a> &gt;&gt; ';
		}
		?>
		<b>Batch Determinations/Nomenclatural Adjustments</b>
	</div>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($isEditor){
			echo '<h2>'.$occManager->getCollName().'</h2>';
			if($statusStr) echo '<div style="color:orange;font-weight:bold;">'.$statusStr.'</div>';
			?>
			<div style="margin:0px;">
				<fieldset style="padding:10px;">
					<legend><b>Define Specimen Recordset</b></legend>
					<div style="margin:15px">
						Scan barcodes/catalog numbers into the field below or enter a taxon search term to list specimens for batch annotation.
						Multiple catalog numbers can be entered at once when separated by commas.
						Once list is complete, select/deselect target specimens, enter annotation information, and submit.
					</div>
					<div style="margin:15px;width:700px;">
						<form name="accqueryform" action="batchdeterminations.php" method="post" onsubmit="return submitAccForm(this);return false;">
							<div style="float:right">
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<button name="clearaccform" type="button" style="margin-left:40px" onclick='clearAccForm(this.form)'>Clear List</button>
							</div>
							<div>
								<b>Catalog Number:</b>
								<input name="catalognumber" type="text" style="border-color:green;width:200px;" />
								<button name="addrecord" type="submit">Add Record(s) to Queue</button>
							</div>
							<div>
								<b>Taxon:</b>
								<input type="text" id="nomsciname" name="sciname" style="width:260px;" onfocus="initScinameAutocomplete(this.form)" />
								<button name="addrecord" type="submit">List Record(s)</button>
								<img id="workingcircle" src="../../images/workingcircle.gif" style="display:none;" />
							</div>
						</form>
					</div>
					<div style="margin:15px">
						* Specimen list is limited to 200 records per batch.<br/>
					</div>
				</fieldset>
				<div id="accrecordlistdviv" style="display:none;">
					<form name="accselectform" id="accselectform" action="batchdeterminations.php" method="post" onsubmit="return validateSelectForm(this);">
						<div style="margin-top: 15px; margin-left: 10px;">
							<input name="accselectall" value="" type="checkbox" onclick="selectAll(this);" checked />
							Select/Deselect all Specimens
						</div>
						<table class="styledtable" style="font-family:Arial;font-size:12px;">
							<thead>
								<tr>
									<th style="width:25px;text-align:center;">&nbsp;</th>
									<th style="width:125px;text-align:center;">Catalog Number</th>
									<th style="width:300px;text-align:center;">Scientific Name</th>
									<th style="text-align:center;">Collector/Locality</th>
								</tr>
							</thead>
							<tbody id="catrecordstbody"></tbody>
						</table>
						<div id="newdetdiv" style="">
							<fieldset style="margin: 15px 15px 0px 15px;padding:15px;">
								<legend><b>New Determination Details</b></legend>
								<div style='margin:3px;position:relative;height:35px'>
									<div style="float:left;">
										<b>Annotation Type:</b>
									</div>
									<div style="float:left;">
										<input name="annotype" type="radio" value="id" onchange="annotationTypeChanged(this)" checked /> Identification Adjustment/Verification<br/>
										<input name="annotype" type="radio" value="na" onchange="annotationTypeChanged(this)" /> Nomenclatural Adjustment
									</div>
								</div>
								<div style="clear:both;margin:15px 0px"><hr /></div>
								<div id="idQualifierDiv" style='margin:3px;clear:both'>
									<b>Identification Qualifier:</b>
									<input type="text" name="identificationqualifier" title="e.g. cf, aff, etc" />
								</div>
								<div style='margin:3px;'>
									<b>Scientific Name:</b>
									<input type="text" id="dafsciname" name="sciname" style="background-color:lightyellow;width:350px;" onfocus="initDetAutocomplete(this.form)" />
									<input type="hidden" id="daftidtoadd" name="tidtoadd" value="" />
									<input type="hidden" name="family" value="" />
								</div>
								<div style='margin:3px;'>
									<b>Author:</b>
									<input type="text" name="scientificnameauthorship" style="width:200px;" />
								</div>
								<div id="codDiv" style='margin:3px;'>
									<b>Confidence of Determination:</b>
									<select name="confidenceranking">
										<option value="8">High</option>
										<option value="5" selected>Medium</option>
										<option value="2">Low</option>
									</select>
								</div>
								<div id="identifiedByDiv" style='margin:3px;'>
									<b>Determiner:</b>
									<input type="text" name="identifiedby" id="identifiedby" style="background-color:lightyellow;width:200px;" />
								</div>
								<div id="dateIdentifiedDiv" style='margin:3px;'>
									<b>Date:</b>
									<input type="text" name="dateidentified" id="dateidentified" style="background-color:lightyellow;" onchange="detDateChanged(this.form);" />
								</div>
								<div style='margin:3px;'>
									<b>Reference:</b>
									<input type="text" name="identificationreferences" style="width:350px;" />
								</div>
								<div style='margin:3px;'>
									<b>Notes:</b>
									<input type="text" name="identificationremarks" style="width:350px;" />
								</div>
								<div id="makeCurrentDiv" style='margin:3px;'>
									<input type="checkbox" name="makecurrent" value="1" checked /> Make this the current determination
								</div>
								<div style='margin:3px;'>
									<input type="checkbox" name="printqueue" value="1" checked /> Add to Annotation Print Queue
									<a href="../reports/labelmanager.php?collid=<?php echo $collid; ?>&tabtarget=1" target="_blank"><img src="../../images/list.png" style="width:13px" title="Display Annotation Print Queue" /></a>
								</div>
								<div style='margin:15px;'>
									<div style="float:left;">
										<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
										<input name="tabtarget" type="hidden" value="0" />
										<input type="submit" name="formsubmit" value="Add New Determinations" />
									</div>
								</div>
							</fieldset>
						</div>
					</form>
				</div>
			</div>
			<?php
		}
		else{
			?>
			<div style="font-weight:bold;margin:20px;font-weight:150%;">
				You do not have permissions to set batch determinations for this collection.
				Please contact the site administrator to obtain the necessary permissions.
			</div>
			<?php
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT."/footer.php");
	?>
	</body>
</html>