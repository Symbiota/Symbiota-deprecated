<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
include_once($SERVER_ROOT.'/classes/SOLRManager.php');
header("Content-Type: text/html; charset=".$charset);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/editor/batchdeterminations.php?'.$_SERVER['QUERY_STRING']);

$collid = $_REQUEST["collid"];
$tabTarget = array_key_exists('tabtarget',$_REQUEST)?$_REQUEST['tabtarget']:0;
$formSubmit = array_key_exists('formsubmit',$_POST)?$_POST['formsubmit']:'';

$occManager = new OccurrenceEditorDeterminations();
if($SOLR_MODE) $solrManager = new SOLRManager();

$occManager->setCollId($collid);
$occManager->getCollMap();

$isEditor = 0;
$catTBody = '';
$nomTBody = '';
$catArr = array();
$jsonCatArr = '';
$occArr = array();
if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collid,$userRights["CollAdmin"]))){
	$isEditor = 1;
}
elseif(array_key_exists("CollEditor",$userRights) && in_array($collid,$userRights["CollEditor"])){
	$isEditor = 1;
}
if($isEditor){
	if($formSubmit == 'Add New Determinations'){
		$occidArr = $_REQUEST['occid'];
		$occStr = implode(",",$occidArr);
		$catArr = $occManager->getCatNumArr($occStr);
		$jsonCatArr = json_encode($catArr);
		foreach($occidArr as $k){
			$occManager->setOccId($k);
			$occManager->addDetermination($_REQUEST,$isEditor);
		}
		$catTBody = $occManager->getBulkDetRows($collid,'','',$occStr);
        if($SOLR_MODE) $solrManager->updateSOLR();
	}
	if($formSubmit == 'Adjust Nomenclature'){
		$occidArr = $_REQUEST['occid'];
		$occStr = implode(",",$occidArr);
		foreach($occidArr as $k){
			$occManager->setOccId($k);
			$occManager->addNomAdjustment($_REQUEST,$isEditor);
		}
		$nomTBody = $occManager->getBulkDetRows($collid,'','',$occStr);
        if($SOLR_MODE) $solrManager->updateSOLR();
	}
}
?>

<html>
	<head>
	    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>">
		<title><?php echo $defaultTitle; ?> Batch Determinations/Nomenclatural Adjustments</title>
		<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
		<link href="../../css/jquery-ui.css" type="text/css" rel="Stylesheet" />
		<script src="../../js/jquery.js" type="text/javascript"></script>
		<script src="../../js/jquery-ui.js" type="text/javascript"></script>
		<script language="javascript" type="text/javascript">
			$(document).ready(function() {
				if(!navigator.cookieEnabled){
					alert("Your browser cookies are disabled. To be able to login and access your profile, they must be enabled for this domain.");
				}
				$("#tabs").tabs({
					active: <?php echo (is_numeric($tabTarget)?$tabTarget:'0'); ?>
				});
			});
			
			var catalogNumbers = <?php echo ($jsonCatArr?$jsonCatArr:'[]'); ?>;
			
			function adjustAccTab(){
				if(catalogNumbers.length > 0){
					document.getElementById("accrecordlistdviv").style.display = "block";
				}
				else{
					document.getElementById("accrecordlistdviv").style.display = "none";
				}
			}
			
			function clearAccForm(){
				if(confirm("Clearing the form will clear the form and restart the process. Are you sure you want to do this?") == true){
					catalogNumbers.length = 0;
					adjustAccTab();
					document.getElementById("catrecordstbody").innerHTML = '';
					document.getElementById("fcatalognumber").value = '';
					document.getElementById("accselectall").checked = false;
				}
			}
			
			function clearNomForm(){
				if(confirm("Clearing the form will clear the form and restart the process. Are you sure you want to do this?") == true){
					document.getElementById("nomrecordlistdviv").style.display = "none";
					document.getElementById("nomrecordstbody").innerHTML = '';
					document.getElementById("nomsciname").value = '';
					document.getElementById("nomselectall").checked = false;
				}
			}
			
			function submitAccForm(f){
				var continueSubmit = true;
				var catNum = document.getElementById("fcatalognumber").value;
				if(catalogNumbers.length < 401){
					if(continueSubmit && $( "#fcatalognumber" ).val() != ""){
						if(catalogNumbers.indexOf(catNum) < 0){
							//Add new occurrence 
							$.ajax({
								type: "POST",
								url: "rpc/getnewdetspeclist.php",
								data: { 
									catalognumber: $( "#fcatalognumber" ).val(),
									collid: $( "#fcollid" ).val()
								}
							}).done(function( retStr ) {
								if(retStr){
									var oldList = document.getElementById("catrecordstbody").innerHTML;
									var newList = retStr+oldList;
									document.getElementById("catrecordstbody").innerHTML = newList;
									catalogNumbers.push(catNum);
									adjustAccTab();
									document.getElementById("fcatalognumber").value = '';
									document.getElementById("accselectall").checked = false;
								}
								else{
									alert("That catalog number does not exist in the database.");
								}
							});
						}
						else{
							alert("That catalog number has already been added to the list.");
						}
					}
				}
				else{
					alert("You cannot add more than 400 occurrences to the list.");
				}
				
				$( "#fcatalognumber" ).focus();
				return false;
			}
			
			function submitNomForm(f){
				document.getElementById("nomrecordsubmit").disabled = true;
				document.getElementById("workingcircle").style.display = "inline";
				var continueSubmit = true;
				var sciName = document.getElementById("nomsciname").value;
				if(continueSubmit && $( "#nomsciname" ).val() != ""){
					//Add new occurrence 
					$.ajax({
						type: "POST",
						url: "rpc/getnewdetspeclist.php",
						data: { 
							sciname: $( "#nomsciname" ).val(),
							collid: $( "#nomcollid" ).val()
						}
					}).done(function( retStr ) {
						if(retStr){
							document.getElementById("nomrecordstbody").innerHTML = retStr;
							document.getElementById("nomrecordlistdviv").style.display = "block";
							document.getElementById("nomselectall").checked = false;
						}
						else{
							document.getElementById("nomrecordlistdviv").style.display = "none";
							document.getElementById("nomrecordstbody").innerHTML = '';
							document.getElementById("nomsciname").value = '';
							alert("There are no occurrences identified to that taxon.");
						}
					});
				}
				
				$( "#nomsciname" ).focus();
				document.getElementById("workingcircle").style.display = "none";
				document.getElementById("nomrecordsubmit").disabled = false;
				return false;
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

			function validateSelectForm(f){
				var dbElements = document.getElementsByName("occid[]");
				for(i = 0; i < dbElements.length; i++){
					var dbElement = dbElements[i];
					if(dbElement.checked) return true;
				}
			   	alert("Please select at least one occurrence!");
		      	return false;
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
				newWindow = window.open(urlStr,'popup','scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
				if (newWindow.opener == null) newWindow.opener = self;
				return false;
			}
			
			function initNomAdjAutocomplete(f){
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
						alert("WARNING: Taxon not found. It may be misspelled or needs to be added to taxonomic thesaurus by a taxonomic editor.");
						f.scientificnameauthorship.value = "";
						f.family.value = "";
						f.tidtoadd.value = "";
					}
				});
			}
			
			function verifyCatDet(f){
				if(f.sciname.value == ""){
					alert("Scientific Name field must have a value");
					return false;
				}
				if(f.identifiedby.value == ""){
					alert("Determiner field must have a value (enter 'unknown' if not defined)");
					return false;
				}
				if(f.dateidentified.value == ""){
					alert("Determination Date field must have a value (enter 'unknown' if not defined)");
					return false;
				}
				//If sciname was changed and submit was clicked immediately afterward, wait 5 seconds so that name can be verified 
				if(pauseSubmit){
					var date = new Date();
					var curDate = null;
					do{ 
						curDate = new Date(); 
					}while(curDate - date < 5000 && pauseSubmit);
				}
				return true;
			}
			
			function verifyNomDet(f){
				var firstTaxon = document.getElementById("nomsciname").value;
				if(f.sciname.value == ""){
					alert("Scientific Name field must have a value");
					return false;
				}
				if(f.sciname.value == firstTaxon){
					f.sciname.value = '';
					alert("Taxon must be different than taxon to be adjusted.");
					return false;
				}
				if(pauseSubmit){
					var date = new Date();
					var curDate = null;
					do{ 
						curDate = new Date(); 
					}while(curDate - date < 5000 && pauseSubmit);
				}
				return true;
			}
		</script>
	</head>
	<body>
	<?php
	$displayLeftMenu = (isset($collections_batchdeterminationsMenu)?$collections_batchdeterminationsMenu:false);
	include($serverRoot."/header.php");
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
			?>
			<div id="tabs" style="margin:0px;">
				<ul>
					<li><a href="#batchdet">Batch Determinations</a></li>
					<li><a href="#nomadjust">Nomenclatural Adjustments</a></li>
				</ul>
				
				<div id="batchdet">
					<form name="accqueryform" action="batchdeterminations.php" method="post" onsubmit="return submitAccForm(this);">
						<fieldset>
							<legend><b>Define Specimen Recordset</b></legend>
							<div style="margin:3px;">
								<div style="clear:both;padding:8px 0px 0px 0px;">
									* Specimen list is limited to 400 records
								</div>
								<div style="clear:both;padding:15px 0px 0px 20px;">
									<div style="float:right;">
										<button name="clearaccform"  type="button" style="margin-right:40px" onclick='clearAccForm();' >Clear Form</button>
									</div>
									<b>Catalog Number:</b>
									<input id="fcatalognumber" name="catalognumber" type="text" style="border-color:green;" />
									<input id="fcollid" name="collid" type="hidden" value="<?php echo $collid; ?>" />
									<input name="recordsubmit" type="submit" value="Add Record" />
								</div>
							</div>
						</fieldset>
					</form>
					<div id="accrecordlistdviv" style="display:<?php echo ($catTBody?'block;':'none;'); ?>none;">
						<form name="accselectform" id="accselectform" action="batchdeterminations.php" method="post" onsubmit="return validateSelectForm(this);">
							<div style="margin-top: 15px; margin-left: 15px;">
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
								<tbody id="catrecordstbody"><?php echo ($catTBody?$catTBody:''); ?></tbody>
							</table>
							<div id="newdetdiv" style="">
								<fieldset style="margin: 15px 15px 0px 15px;padding:15px;">
									<legend><b>Add a New Determination</b></legend>
									<div style='margin:3px;'>
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
									<div style='margin:3px;'>
										<b>Confidence of Determination:</b> 
										<select name="confidenceranking">
											<option value="8">High</option>
											<option value="5" selected>Medium</option>
											<option value="2">Low</option>
										</select>
									</div>
									<div style='margin:3px;'>
										<b>Determiner:</b> 
										<input type="text" name="identifiedby" id="identifiedby" style="background-color:lightyellow;width:200px;" />
									</div>
									<div style='margin:3px;'>
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
									<div style='margin:3px;'>
										<input type="checkbox" name="makecurrent" value="1" /> Make this the current determination
									</div>
									<div style='margin:3px;'>
										<input type="checkbox" name="printqueue" value="1" /> Add to Annotation Queue
									</div>
									<?php 
									global $fpEnabled;
									if($fpEnabled){
										echo '<div style="float:left;margin-left:30px;">';
										echo '<input type="checkbox" name="fpsubmit" value="1" checked="true" /> Submit determination to Filtered Push network';
										echo '</div>';
									}
									?>
									<div style='margin:15px;'>
										<div style="float:left;">
											<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
											<input name="tabtarget" type="hidden" value="0" />
											<input type="submit" name="formsubmit" onclick="verifyCatDet(this.form);" value="Add New Determinations" />
										</div>
									</div>
								</fieldset>
							</div>
						</form>
					</div>
				</div>
				
				<div id="nomadjust">
					<form name="nomqueryform" action="batchdeterminations.php" method="post" onsubmit="return submitNomForm(this);">
						<fieldset>
							<legend><b>Taxon To Be Adjusted</b></legend>
							<div style="margin:3px;">
								<div style="clear:both;padding:8px 0px 0px 0px;">
									* Specimen list is limited to 400 records
								</div>
								<div style="clear:both;padding:15px 0px 0px 20px;">
									<div style="float:right;">
										<button name="clearnomform"  type="button" style="margin-right:15px" onclick='clearNomForm();' >Clear Form</button>
									</div>
									<div style="float:left;width:675px;">
										<b>Taxon:</b>
										<input type="text" id="nomsciname" name="sciname" style="background-color:lightyellow;width:450px;" onfocus="initNomAdjAutocomplete(this.form)" />
										<input id="nomcollid" name="collid" type="hidden" value="<?php echo $collid; ?>" />
										<input name="recordsubmit" id="nomrecordsubmit" type="submit" value="Find Records" />
										<img id="workingcircle" src="../../images/workingcircle.gif" style="display:none;" />
									</div>
								</div>
							</div>
						</fieldset>
					</form>
					<div id="nomrecordlistdviv" style="display:<?php echo ($nomTBody?'block;':'none;'); ?>none;">
						<form name="nomselectform" id="accselectform" action="batchdeterminations.php" method="post" onsubmit="return validateSelectForm(this);">
							<div style="margin-top: 15px; margin-left: 15px;">
								<input type="checkbox" name="nomselectall" value="" onclick="selectAll(this);" checked />
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
								<tbody id="nomrecordstbody"><?php echo ($nomTBody?$nomTBody:''); ?></tbody>
							</table>
							<div id="newdetdiv" style="">
								<fieldset style="margin: 15px 15px 0px 15px;padding:15px;">
									<legend><b>Adjust To Taxon</b></legend>
									<div style='margin:3px;'>
										<b>Scientific Name:</b> 
										<input type="text" id="dafsciname" name="sciname" style="background-color:lightyellow;width:350px;" onfocus="initDetAutocomplete(this.form);" />
										<input type="hidden" id="daftidtoadd" name="tidtoadd" value="" />
										<input type="hidden" name="family" value="" />
									</div>
									<div style='margin:3px;'>
										<b>Author:</b> 
										<input type="text" name="scientificnameauthorship" style="width:200px;" />
									</div>
									<div style='margin:3px;'>
										<b>Reference:</b> 
										<input type="text" name="identificationreferences" style="width:350px;" />
									</div>
									<div style='margin:3px;'>
										<b>Notes:</b> 
										<input type="text" name="identificationremarks" style="width:350px;" value="" />
									</div>
									<div style='margin:3px;'>
										<input type="checkbox" name="printqueue" value="1" /> Add to Annotation Queue
									</div>
									<?php 
									global $fpEnabled;
									if($fpEnabled){
										echo '<div style="float:left;margin-left:30px;">';
										echo '<input type="checkbox" name="fpsubmit" value="1" checked="true" /> Submit determination to Filtered Push network';
										echo '</div>';
									}
									?>
									<div style='margin:15px;'>
										<div style="float:left;">
											<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
											<input name="tabtarget" type="hidden" value="1" />
											<input name="makecurrent" type="hidden" value="1" />
											<input type="submit" name="formsubmit" onclick="verifyNomDet(this.form);" value="Adjust Nomenclature" />
										</div>
									</div>
								</fieldset>
							</div>
						</form>
					</div>
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
	include($serverRoot."/footer.php");
	?>
	</body>
</html>