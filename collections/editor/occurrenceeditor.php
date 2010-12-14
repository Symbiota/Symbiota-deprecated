<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
header("Content-Type: text/html; charset=".$charset);

$occId = array_key_exists("occid",$_REQUEST)?$_REQUEST["occid"]:"";
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";

$occManager = new OccurrenceEditorManager();
$occArr = Array();
$editable = 0;
if($occId){
	if($action == "Edit Record"){
		$occManager->editOccurrence($_REQUEST);
	}
	elseif($action == "Add New Record"){
		$occManager->addOccurrence($_REQUEST);
	}
	elseif($action == "Submit Image Edits"){
		$occManager->editImage($_REQUEST);
	}
	$occArr = $occManager->getOccurArr($occId);
	$collId = $occArr["collid"]["value"];
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])) || (array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"]))){
		$editable = 1;
	}
}

?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title><?php echo $defaultTitle; ?> Detailed Collection Record Information</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
	<link rel="stylesheet" type="text/css" href="../../css/tabcontent.css" />
    <link rel="stylesheet" href="../../css/jqac.css" type="text/css">
	<script type="text/javascript" src="../../js/tabcontent.js"></script>
	<script type="text/javascript" src="../../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../../js/jquery.autocomplete-1.4.2.js"></script>
	<script language="javascript">

		function initTabs(tabObjId){
			var dTabs=new ddtabcontent(tabObjId); 
			dTabs.setpersist(true);
			dTabs.setselectedClassTarget("link"); 
			dTabs.init();
		}

		function toggle(target){
			var ele = document.getElementById(target);
			if(ele){
				if(ele.style.display=="none"){
					ele.style.display="block";
		  		}
			 	else {
			 		ele.style.display="none";
			 	}
			}
			else{
				var divObjs = document.getElementsByTagName("div");
			  	for (i = 0; i < divObjs.length; i++) {
			  		var divObj = divObjs[i];
			  		if(divObj.getAttribute("class") == target || divObj.getAttribute("className") == target){
						if(divObj.style.display=="none"){
							divObj.style.display="block";
						}
					 	else {
					 		divObj.style.display="none";
					 	}
					}
				}
			}
		}

		function toggleSpan(target){
			var spanObjs = document.getElementsByTagName("span");
			var objLen = spanObjs.length;
		  	for (var i = 0; i < objLen; i++) {
		  		var spanObj = spanObjs[i];
		  		if(spanObj.getAttribute("class") == target || spanObj.getAttribute("className") == target){
					if(spanObj.style.display=="none"){
						spanObj.style.display="inline";
					}
				 	else {
				 		spanObj.style.display="none";
				 	}
				}
			}
		}

		function toggleIdDetails(){
			toggle("idrefdiv");
			toggle("taxremdiv");
		}

	    function submitAddImageForm(f){
	        if(f.elements["userfile"].value.replace(/\s/g, "") == "" ){
	            if(f.elements["filepath"].value.replace(/\s/g, "") == ""){
	            	window.alert("File path must be entered");
					return false;
	            }
	        }
	        if(isNumeric(f.sortsequence.value) == false){
				window.alert("Sort value must be a number");
				return false;
	        }
	        return true;
	    }

	    function initTaxonList(input){
			$(input).autocomplete({ ajax_get:getTaxonSuggs, minchars:3 });
		}

		function getTaxonSuggs(key,cont){ 
		   	var script_name = 'rpc/getspecies.php';
		   	var params = { 'q':key }
		   	$.get(script_name,params,
				function(obj){
					// obj is just array of strings
					var res = [];
					for(var i=0;i<obj.length;i++){
						res.push({ id:i , value:obj[i]});
					}
					// will build suggestions list
					cont(res);
				},
			'json');
		}

		function verifySciName(sciNameInput){
			snXmlHttp = GetXmlHttpObject();
			if(snXmlHttp==null){
		  		alert ("Your browser does not support AJAX!");
		  		return;
		  	}
			var url = "rpc/verifysciname.php";
			url=url + "?sciname=" + sciNameInput.value; 
			snXmlHttp.onreadystatechange=function(){
				if(snXmlHttp.readyState==4 && snXmlHttp.status==200){
					if(snXmlHttp.responseText){
						var retObj = eval("("+snXmlHttp.responseText+")");
						document.fullform.scientificnameauthorship.value = retObj.author;
						document.fullform.family.value = retObj.family;
					}
					else{
						alert("Taxon not found. Maybe misspelled or needs to be added to taxonomic thesaurus.");
					}
				}
			};
			snXmlHttp.open("POST",url,true);
			snXmlHttp.send(null);
		} 
		
		function verifyDate(eventDateInput){
			var dateStr = eventDateInput.value;
			//test date and return mysqlformat

			
		}

		function inputIsNumeric(inputObj, titleStr){
			if(!isNumeric(inputObj.value)){
				alert("Input value for " + titleStr + " must be a number value only! " );
			}
		}

		function isNumeric(sText){
		   	var ValidChars = "0123456789-.";
		   	var IsNumber = true;
		   	var Char;
		 
		   	for(var i = 0; i < sText.length && IsNumber == true; i++){ 
			   Char = sText.charAt(i); 
				if(ValidChars.indexOf(Char) == -1){
					IsNumber = false;
					break;
	          	}
		   	}
			return IsNumber;
		}

		function GetXmlHttpObject(){
			var xmlHttp=null;
			try{
				// Firefox, Opera 8.0+, Safari, IE 7.x
		  		xmlHttp=new XMLHttpRequest();
		  	}
			catch (e){
		  		// Internet Explorer
		  		try{
		    		xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
		    	}
		  		catch(e){
		    		xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
		    	}
		  	}
			return xmlHttp;
		}

	</script>
</head>
<body onload="initTabs('occedittabs');">

<?php
	$displayLeftMenu = (isset($collections_editor_occurrenceEditorMenu)?$collections_individual_occurrenceEditorMenu:false);
	include($serverRoot.'/header.php');
	if(isset($collections_editor_occurrenceEditorCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $collections_editor_occurrenceEditorCrumbs;
		echo " &gt; <b>Occurrence Editor</b>";
		echo "</div>";
	}
?>
	<!-- inner text -->
	<div id="innertext">
	<?php 
	if($editable){
		?>
	    <ul id="occedittabs" class="shadetabs">
	        <li><a href="#" rel="shortdiv" class="selected">Specimen Data</a></li>
	        <li><a href="#" rel="determdiv">Determination History</a></li>
	        <li><a href="#" rel="imagediv">Images</a></li>
	    </ul>
		<div style="border:1px solid gray;width:96%;margin-bottom:1em;padding:5px;">
			<div id="shortdiv" class="tabcontent" style="margin:10px;">
				<form id='fullform' name='fullform' action='occurrenceeditor.php' method='get'>
					<fieldset>
						<legend><b>Latest Description</b></legend>
						<div style="clear:both;" class="p1">
							<span class="flabel" style="width:125px;">
								Scientific Name:
							</span>
							<span class="flabel" style="margin-left:315px;">
								Author:
							</span>
						</div>
						<div style="clear:both;" class="p1">
							<span>
								<?php $hasValue = array_key_exists("sciname",$occArr)&&$occArr["sciname"]["value"]?1:0; ?>
								<input type="text" name="sciname" maxlength="250" tabindex="2" style="width:390px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["sciname"]["value"]:""; ?>" onfocus="initTaxonList(this)" autocomplete="off" onchange="verifySciName(this);" />
								<input type="hidden" id="tidtoadd" name="tidtoadd" value="" />
							</span>
							<span style="margin-left:10px;">
								<?php $hasValue = array_key_exists("scientificnameauthorship",$occArr)&&$occArr["scientificnameauthorship"]["value"]?1:0; ?>
								<input type="text" name="scientificnameauthorship" maxlength="100" tabindex="0" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["scientificnameauthorship"]["value"]:""; ?>" />
							</span>
						</div>
						<div style="clear:both;padding:3px 0px 0px 10px;" class="p1">
							<div style="float:left;">
								<?php $hasValue = array_key_exists("identificationqualifier",$occArr)&&$occArr["identificationqualifier"]["value"]?1:0; ?>
								<span class="flabel">ID Qualifier:</span>
								<input type="text" name="identificationqualifier" tabindex="4" size="5" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["identificationqualifier"]["value"]:""; ?>" />
							</div>
							<div style="float:left;margin-left:160px;">
								<?php $hasValue = array_key_exists("family",$occArr)&&$occArr["family"]["value"]?1:0; ?>
								<span class="flabel">Family:</span>
								<input type="text" name="family" size="30" maxlength="50" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" tabindex="0" value="<?php echo $hasValue?$occArr["family"]["value"]:""; ?>" />
							</div>
						</div>
						<div style="clear:both;padding:3px 0px 0px 10px;margin-bottom:20px;" class="p1">
							<div style="float:left;">
								<?php $hasValue = array_key_exists("identifiedby",$occArr)&&$occArr["identifiedby"]["value"]?1:0; ?>
								Identified By:
								<input type="text" name="identifiedby" maxlength="255" tabindex="6" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["identifiedby"]["value"]:""; ?>" />
							</div>
							<div style="float:left;margin-left:15px;padding:3px 0px 0px 10px;">
								<?php $hasValue = array_key_exists("dateidentified",$occArr)&&$occArr["dateidentified"]["value"]?1:0; ?>
								Date Identified:
								<input type="text" name="dateidentified" maxlength="45" tabindex="8" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["dateidentified"]["value"]:""; ?>" />
							</div>
							<div style="float:left;margin-left:15px;cursor:pointer;" onclick="toggleIdDetails();">
								<img src="../../images/showedit.png" style="width:15px;" />
							</div>
						</div>
						<div style="clear:both;">
							<div id="idrefdiv" style="display:none;padding:3px 0px 0px 10px;" class="p2">
								<?php $hasValue = array_key_exists("identificationreferences",$occArr)&&$occArr["identificationreferences"]["value"]?1:0; ?>
								ID References:
								<input type="text" name="identificationreferences" tabindex="10" size="60" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["identificationreferences"]["value"]:""; ?>" />
							</div>
							<div id="taxremdiv" style="display:none;padding:3px 0px 0px 10px;" class="p2">
								<?php $hasValue = array_key_exists("taxonremarks",$occArr)&&$occArr["taxonremarks"]["value"]?1:0; ?>
								ID Remarks:
								<input type="text" name="taxonremarks" tabindex="12" size="60" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["taxonremarks"]["value"]:""; ?>" />
							</div>
						</div>
					</fieldset>
					<fieldset>
						<legend><b>Collector Info</b></legend>
						<div style="float:left;">
							<div style="clear:both;" class="p1">
								<span>
									Collector:
								</span>
								<span style="margin-left:210px;">
									Number:
								</span>
								<span style="margin-left:40px;">
									Date:
								</span>
							</div>
							<div style="clear:both;" class="p1">
								<span>
									<?php $hasValue = array_key_exists("recordedby",$occArr)&&$occArr["recordedby"]["value"]?1:0; ?>
									<input type="text" name="recordedby" maxlength="255" tabindex="14" style="width:250px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["recordedby"]["value"]:""; ?>" />
								</span>
								<span style="margin-left:10px;">
									<?php $hasValue = array_key_exists("recordnumber",$occArr)&&$occArr["recordnumber"]["value"]?1:0; ?>
									<input type="text" name="recordnumber" maxlength="45" tabindex="16" style="width:80px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["recordnumber"]["value"]:""; ?>" />
								</span>
								<span style="margin-left:10px;">
									<?php $hasValue = array_key_exists("eventdate",$occArr)&&$occArr["eventdate"]["value"]?1:0; ?>
									<input type="text" name="eventdate" tabindex="18" style="width:80px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["eventdate"]["value"]:""; ?>" onchange="verifyDate(this);" />
								</span>
								<span style="margin-left:5px;cursor:pointer;" onclick="toggle('dateextradiv')">
									<img src="../../images/showedit.png" style="width:15px;" />
								</span>
							</div>
							<div style="clear:both;margin-top:5px;" class="p1">
								<?php $hasValue = array_key_exists("associatedcollectors",$occArr)&&$occArr["associatedcollectors"]["value"]?1:0; ?>
								Associated Collectors:<br />
								<input type="text" name="associatedcollectors" tabindex="20" maxlength="255" style="width:430px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["associatedcollectors"]["value"]:""; ?>" />
							</div>
						</div>
						<?php 
							$dateExtraDiv = "none";
							if(array_key_exists("verbatimeventdate",$occArr) && $occArr["verbatimeventdate"]["value"]){
								$dateExtraDiv = "block";
							}
							elseif(array_key_exists("month",$occArr) && $occArr["month"]["value"]){
								$dateExtraDiv = "block";
							}
							elseif(array_key_exists("day",$occArr) && $occArr["day"]["value"]){
								$dateExtraDiv = "block";
							}
							elseif(array_key_exists("year",$occArr) && $occArr["year"]["value"]){
								$dateExtraDiv = "block";
							}
							elseif(array_key_exists("startdayofyear",$occArr) && $occArr["startdayofyear"]["value"]){
								$dateExtraDiv = "block";
							}
							elseif(array_key_exists("enddayofyear",$occArr) && $occArr["enddayofyear"]["value"]){
								$dateExtraDiv = "block";
							}
						?>
						<div id="dateextradiv" style="float:left;padding:5px;margin-left:10px;border:1px solid gray;display:<?php echo $dateExtraDiv; ?>;" class="p2">
							<div>
								Verbatim Date:
								<?php $hasValue = array_key_exists("verbatimeventdate",$occArr)&&$occArr["verbatimeventdate"]["value"]?1:0; ?>
								<input type="text" name="verbatimeventdate" tabindex="20" maxlength="255" style="width:120px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["verbatimeventdate"]["value"]:""; ?>" />
							</div>
							<div>
								MM/DD/YYYY:
								<span style="margin:8px;">
									<?php $hasValue = array_key_exists("month",$occArr)&&$occArr["month"]["value"]?1:0; ?>
									<input type="text" name="month" tabindex="22" style="width:30px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["month"]["value"]:""; ?>" onchange="inputIsNumeric(this, 'Month')" title="Numeric Month" />/
									<?php $hasValue = array_key_exists("day",$occArr)&&$occArr["day"]["value"]?1:0; ?>
									<input type="text" name="day" tabindex="24" style="width:30px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["day"]["value"]:""; ?>" onchange="inputIsNumeric(this, 'Day')" title="Numeric Day" />/
									<?php $hasValue = array_key_exists("year",$occArr)&&$occArr["year"]["value"]?1:0; ?>
									<input type="text" name="year" tabindex="26" style="width:45px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["year"]["value"]:""; ?>" onchange="inputIsNumeric(this, 'Year')" title="Numeric Year" />
								</span>
							</div>
							<div>
								Day of Year:
								<span style="margin:16px;">
									<?php $hasValue = array_key_exists("startdayofyear",$occArr)&&$occArr["startdayofyear"]["value"]?1:0; ?>
									<input type="text" name="startdayofyear" tabindex="28" style="width:40px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["startdayofyear"]["value"]:""; ?>" onchange="inputIsNumeric(this, 'Start Day of Year')" title="Start Day of Year" /> -
									<?php $hasValue = array_key_exists("enddayofyear",$occArr)&&$occArr["enddayofyear"]["value"]?1:0; ?>
									<input type="text" name="enddayofyear" tabindex="30" style="width:40px;background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["enddayofyear"]["value"]:""; ?>" onchange="inputIsNumeric(this, 'End Day of Year')" title="End Day of Year" />
								</span>
							</div>
						</div>
					</fieldset>
					<fieldset>
						<legend><b>Locality</b></legend>
						<div>
							<span style="">
								Country
							</span>
							<span style="margin-left:109px;">
								State/Province
							</span>
							<span style="margin-left:70px;">
								County
							</span>
							<span style="margin-left:110px;">
								Municipality
							</span>
						</div>
						<div>
							<span>
								<?php $hasValue = array_key_exists("country",$occArr)&&$occArr["country"]["value"]?1:0; ?>
								<input type="text" name="country" tabindex="32" size="" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["country"]["value"]:""; ?>" />
							</span>
							<span>
								<?php $hasValue = array_key_exists("stateprovince",$occArr)&&$occArr["stateprovince"]["value"]?1:0; ?>
								<input type="text" name="stateprovince" tabindex="34" size="" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["stateprovince"]["value"]:""; ?>" />
							</span>
							<span>
								<?php $hasValue = array_key_exists("county",$occArr)&&$occArr["county"]["value"]?1:0; ?>
								<input type="text" name="county" tabindex="36" size="" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["county"]["value"]:""; ?>" />
							</span>
							<span>
								<?php $hasValue = array_key_exists("municipality",$occArr)&&$occArr["municipality"]["value"]?1:0; ?>
								<input type="text" name="municipality" tabindex="38" size="" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["municipality"]["value"]:""; ?>" />
							</span>
						</div>
						<div style="margin:4px 0px 2px 0px;">
							Locality:<br />
							<?php $hasValue = array_key_exists("locality",$occArr)&&$occArr["locality"]["value"]?1:0; ?>
							<input type="text" name="locality" tabindex="40" size="100" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["locality"]["value"]:""; ?>" />
						</div>
						<div style="margin-bottom:5px;">
							<?php $hasValue = array_key_exists("localitysecurity",$occArr)&&$occArr["localitysecurity"]["value"]?1:0; ?>
							<input type="checkbox" name="localitysecurity" tabindex="42" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="1" <?php echo $hasValue?"CHECKED":""; ?> title="Hide Locality Data from General Public" />
							Hide Locality Data from Public
						</div>
						<div>
							<span style="">
								Latitude
							</span>
							<span style="margin-left:45px;">
								Longitude
							</span>
							<span style="margin-left:35px;">
								Uncertainty
							</span>
							<span style="margin-left:10px;">
								Datum
							</span>
							<span style="margin-left:53px;">
								Elevation in Meters
							</span>
							<span style="margin-left:25px;">
								Verbatim Elevation
							</span>
						</div>
						<div>
							<span>
								<?php $hasValue = array_key_exists("decimallatitude",$occArr)&&$occArr["decimallatitude"]["value"]?1:0; ?>
								<input type="text" name="decimallatitude" tabindex="44" size="10" maxlength="10" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["decimallatitude"]["value"]:""; ?>" onchange="inputIsNumeric(this, 'Decimal Latitude')" />
							</span>
							<span>
								<?php $hasValue = array_key_exists("decimallongitude",$occArr)&&$occArr["decimallongitude"]["value"]?1:0; ?>
								<input type="text" name="decimallongitude" tabindex="46" size="10" maxlength="13" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["decimallongitude"]["value"]:""; ?>" onchange="inputIsNumeric(this, 'Decimal Longitude')" />
							</span>
							<span>
								<?php $hasValue = array_key_exists("coordinateuncertaintyinmeters",$occArr)&&$occArr["coordinateuncertaintyinmeters"]["value"]?1:0; ?>
								<input type="text" name="coordinateuncertaintyinmeters" tabindex="48" size="7" maxlength="10" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["coordinateuncertaintyinmeters"]["value"]:""; ?>" onchange="inputIsNumeric(this, 'Coordinate Uncertainty')" title="Uncertainty in Meters" />
							</span>
							<span>
								<?php $hasValue = array_key_exists("geodeticdatum",$occArr)&&$occArr["geodeticdatum"]["value"]?1:0; ?>
								<input type="text" name="geodeticdatum" tabindex="50" size="10" maxlength="255" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["geodeticdatum"]["value"]:""; ?>" />
							</span>
							<span>
								<?php $hasValue = array_key_exists("minimumelevationinmeters",$occArr)&&$occArr["minimumelevationinmeters"]["value"]?1:0; ?>
								<input type="text" name="minimumelevationinmeters" tabindex="52" size="5" maxlength="6" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["minimumelevationinmeters"]["value"]:""; ?>" onchange="inputIsNumeric(this, 'Minumum Elevation')" title="Minumum Elevation In Meters" />
							</span> -
							<span>
								<?php $hasValue = array_key_exists("maximumelevationinmeters",$occArr)&&$occArr["maximumelevationinmeters"]["value"]?1:0; ?>
								<input type="text" name="maximumelevationinmeters" tabindex="54" size="5" maxlength="6" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["maximumelevationinmeters"]["value"]:""; ?>" onchange="inputIsNumeric(this, 'Maximum Elevation')" title="Maximum Elevation In Meters" />
							</span>
							<span>
								<?php $hasValue = array_key_exists("verbatimelevation",$occArr)&&$occArr["verbatimelevation"]["value"]?1:0; ?>
								<input type="text" name="verbatimelevation" tabindex="56" size="" maxlength="255" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["verbatimelevation"]["value"]:""; ?>" title="" />
							</span>
							<span style="margin-left:5px;cursor:pointer;" onclick="toggle('locextradiv1');toggle('locextradiv2');">
								<img src="../../images/showedit.png" style="width:15px;" />
							</span>
						</div>
						<?php 
							$locExtraDiv1 = "none";
							if(array_key_exists("verbatimcoordinates",$occArr) && $occArr["verbatimcoordinates"]["value"]){
								$locExtraDiv1 = "block";
							}
							elseif(array_key_exists("verbatimcoordinatesystem",$occArr) && $occArr["verbatimcoordinatesystem"]["value"]){
								$locExtraDiv1 = "block";
							}
							elseif(array_key_exists("georeferencedby",$occArr) && $occArr["georeferencedby"]["value"]){
								$locExtraDiv1 = "block";
							}
							elseif(array_key_exists("georeferenceprotocol",$occArr) && $occArr["georeferenceprotocol"]["value"]){
								$locExtraDiv1 = "block";
							}
						?>
						<div id="locextradiv1" style="display:<?php echo $locExtraDiv1; ?>;">
							<div>
								<span style="">
									Verbatim Coordinates
								</span>
								<span style="margin-left:30px;">
									Verbatim Coordinate System
								</span>
								<span style="margin-left:22px;">
									Georeferenced By
								</span>
								<span style="margin-left:50px;">
									Georeference Protocol
								</span>
							</div>
							<div>
								<span>
									<?php $hasValue = array_key_exists("verbatimcoordinates",$occArr)&&$occArr["verbatimcoordinates"]["value"]?1:0; ?>
									<input type="text" name="verbatimcoordinates" tabindex="58" size="" maxlength="255" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["verbatimcoordinates"]["value"]:""; ?>" title="" />
								</span>
								<span>
									<?php $hasValue = array_key_exists("verbatimcoordinatesystem",$occArr)&&$occArr["verbatimcoordinatesystem"]["value"]?1:0; ?>
									<input type="text" name="verbatimcoordinatesystem" tabindex="60" size="25" maxlength="255" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["verbatimcoordinatesystem"]["value"]:""; ?>" title="" />
								</span>
								<span>
									<?php $hasValue = array_key_exists("georeferencedby",$occArr)&&$occArr["georeferencedby"]["value"]?1:0; ?>
									<input type="text" name="georeferencedby" tabindex="62" maxlength="255" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["georeferencedby"]["value"]:""; ?>" />
								</span>
								<span>
									<?php $hasValue = array_key_exists("georeferenceprotocol",$occArr)&&$occArr["georeferenceprotocol"]["value"]?1:0; ?>
									<input type="text" name="georeferenceprotocol" tabindex="64" maxlength="255" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["georeferenceprotocol"]["value"]:""; ?>" />
								</span>
							</div>
						</div>
						<?php 
							$locExtraDiv2 = "none";
							if(array_key_exists("georeferencesources",$occArr) && $occArr["georeferencesources"]["value"]){
								$locExtraDiv2 = "block";
							}
							elseif(array_key_exists("georeferenceverificationstatus",$occArr) && $occArr["georeferenceverificationstatus"]["value"]){
								$locExtraDiv2 = "block";
							}
							elseif(array_key_exists("georeferenceremarks",$occArr) && $occArr["georeferenceremarks"]["value"]){
								$locExtraDiv2 = "block";
							}
						?>
						<div id="locextradiv2" style="display:<?php echo $locExtraDiv2; ?>;">
							<div>
								<span style="">
									Georeference Sources
								</span>
								<span style="margin-left:28px;">
									Georef Verification Status
								</span>
								<span style="margin-left:10px;">
									Georeference Remarks
								</span>
							</div>
							<div>
								<span>
									<?php $hasValue = array_key_exists("georeferencesources",$occArr)&&$occArr["georeferencesources"]["value"]?1:0; ?>
									<input type="text" name="georeferencesources" tabindex="66" maxlength="255" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["georeferencesources"]["value"]:""; ?>" />
								</span>
								<span>
									<?php $hasValue = array_key_exists("georeferenceverificationstatus",$occArr)&&$occArr["georeferenceverificationstatus"]["value"]?1:0; ?>
									<input type="text" name="georeferenceverificationstatus" tabindex="68" size="" maxlength="32" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["georeferenceverificationstatus"]["value"]:""; ?>" />
								</span>
								<span>
									<?php $hasValue = array_key_exists("georeferenceremarks",$occArr)&&$occArr["georeferenceremarks"]["value"]?1:0; ?>
									<input type="text" name="georeferenceremarks" tabindex="70" size="50" maxlength="255" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["georeferenceremarks"]["value"]:""; ?>" />
								</span>
							</div>
						</div>
					</fieldset>
					<fieldset>
						<legend><b>Misc</b></legend>
						<div style="padding:3px;">
							<?php $hasValue = array_key_exists("habitat",$occArr)&&$occArr["habitat"]["value"]?1:0; ?>
							Habitat:
							<input type="text" name="habitat" tabindex="82" size="105" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["habitat"]["value"]:""; ?>" />
						</div>
						<div style="padding:3px;">
							<?php $hasValue = array_key_exists("associatedtaxa",$occArr)&&$occArr["associatedtaxa"]["value"]?1:0; ?>
							Associated Taxa:
							<input type="text" name="associatedtaxa" tabindex="84" size="97" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["associatedtaxa"]["value"]:""; ?>" />
						</div>
						<div style="padding:3px;">
							<?php $hasValue = array_key_exists("dynamicproperties",$occArr)&&$occArr["dynamicproperties"]["value"]?1:0; ?>
							Description:
							<input type="text" name="dynamicproperties" tabindex="86" size="101" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["dynamicproperties"]["value"]:""; ?>" />
						</div>
						<div style="padding:3px;">
							<?php $hasValue = array_key_exists("occurrenceremarks",$occArr)&&$occArr["occurrenceremarks"]["value"]?1:0; ?>
							Notes:
							<input type="text" name="occurrenceremarks" tabindex="88" size="106" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["occurrenceremarks"]["value"]:""; ?>" title="Occurrence Remarks" />
						</div>
					</fieldset>
					<fieldset>
						<legend><b>Curation</b></legend>
						<div style="padding:3px;">
							<span>
								<?php $hasValue = array_key_exists("catalognumber",$occArr)&&$occArr["catalognumber"]["value"]?1:0; ?>
								Catalog Number:
								<input type="text" name="catalognumber" tabindex="90" maxlength="32" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["catalognumber"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:30px;">
								<?php $hasValue = array_key_exists("occurrenceid",$occArr)&&$occArr["occurrenceid"]["value"]?1:0; ?>
								Occurrence ID (GUID):
								<input type="text" name="occurrenceid" tabindex="92" maxlength="255" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["occurrenceid"]["value"]:""; ?>" title="Global Unique Identifier" />
							</span>
						</div>
						<div style="padding:3px;">
							<span>
								<?php $hasValue = array_key_exists("typestatus",$occArr)&&$occArr["typestatus"]["value"]?1:0; ?>
								Type Status:
								<input type="text" name="typestatus" tabindex="94" maxlength="255" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php $hasValue?$occArr["typestatus"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:30px;">
								<?php $hasValue = array_key_exists("disposition",$occArr)&&$occArr["disposition"]["value"]?1:0; ?>
								Disposition:
								<input type="text" name="disposition" size="40" tabindex="96" maxlength="32" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["disposition"]["value"]:""; ?>" />
							</span>
						</div>
						<div style="padding:3px;">
							<span>
								<?php $hasValue = array_key_exists("reproductivecondition",$occArr)&&$occArr["reproductivecondition"]["value"]?1:0; ?>
								Reproductive Condition:
								<input type="text" name="reproductivecondition" tabindex="98" maxlength="255" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["reproductivecondition"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:30px;">
								<?php $hasValue = array_key_exists("establishmentmeans",$occArr)&&$occArr["establishmentmeans"]["value"]?1:0; ?>
								Establishment Means:
								<input type="text" name="establishmentmeans" tabindex="100" maxlength="32" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["establishmentmeans"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:15px;">
								<?php $hasValue = array_key_exists("cultivationstatus",$occArr)&&$occArr["cultivationstatus"]["value"]?1:0; ?>
								<input type="checkbox" name="cultivationstatus" tabindex="102" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["cultivationstatus"]["value"]:0; ?>" />
								Cultivated
							</span>
						</div>
						<div style="padding:3px;">
							<span>
								<?php $hasValue = array_key_exists("ownerinstitutioncode",$occArr)&&$occArr["ownerinstitutioncode"]["value"]?1:0; ?>
								Owner InstitutionCode:
								<input type="text" name="ownerinstitutioncode" tabindex="104" maxlength="32" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["ownerinstitutioncode"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:30px;">
								<?php $hasValue = array_key_exists("othercatalognumbers",$occArr)&&$occArr["othercatalognumbers"]["value"]?1:0; ?>
								Other Catalog Numbers:
								<input type="text" name="othercatalognumbers" tabindex="106" maxlength="255" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["othercatalognumbers"]["value"]:""; ?>" />
							</span>
						</div>
					</fieldset>
					<fieldset>
						<legend><b>Other</b></legend>
						<div style="padding:3px;">
							<span>
								<?php $hasValue = array_key_exists("basisofrecord",$occArr)&&$occArr["basisofrecord"]["value"]?1:0; ?>
								Basis of Record:
								<input type="text" name="basisofrecord" tabindex="108" maxlength="32" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["basisofrecord"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:20px;">
								<?php $hasValue = array_key_exists("language",$occArr)&&$occArr["language"]["value"]?1:0; ?>
								Language:
								<input type="text" name="language" tabindex="110" maxlength="20" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["language"]["value"]:""; ?>" />
							</span>
						</div>
						<div style="padding:3px;">
							<span>
								<?php $hasValue = array_key_exists("datasetid",$occArr)&&$occArr["datasetid"]["value"]?1:0; ?>
								Dataset ID:
								<input type="text" name="datasetid" tabindex="112" maxlength="255" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["datasetid"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:20px;">
								<?php $hasValue = array_key_exists("associatedoccurrences",$occArr)&&$occArr["associatedoccurrences"]["value"]?1:0; ?>
								Associated Occurrences:
								<input type="text" name="associatedoccurrences" size="40" tabindex="114" style="background-color:<?php echo $hasValue?"lightyellow":"white"; ?>;" value="<?php echo $hasValue?$occArr["associatedoccurrences"]["value"]:""; ?>" />
							</span>
						</div>
					</fieldset>
					<div>
						<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
						<?php if($occId){ ?>
							<input type="submit" name="action" value="Edit Record" />
						<?php }else{ ?>
							<input type="submit" name="action" value="Add New Record" />
						<?php } ?>
					</div>
				</form>
			</div>
			<div id="determdiv" class="tabcontent" style="margin:10px;">
				
			</div>
			<div id="imagediv" class="tabcontent" style="margin:10px;">
				<div style="float:right;cursor:pointer;margin:10px;" onclick="toggle('addimgdiv');" title="Add a New Image">
					<img style="border:0px;width:12px;" src="../../images/add.png" />
				</div>
				<div id="addimgdiv" style="display:none;">
					<form name="newimgform" action="occurrenceeditor.php" method="post" enctype="multipart/form-data" onsubmit="return submitAddImageForm(this);">
						<fieldset>
							<legend>Add a New Image</legend>
							<div style='padding:10px;width:475px;border:1px solid yellow;background-color:FFFF99;'>
								<div style="font-weight:bold;font-size:110%;margin-bottom:7px;">
									Select an image file located on your computer that you want to upload 
									OR enter a URL to an image already located on a web server (don't do both)
								</div>
						    	<!-- following line sets MAX_FILE_SIZE (must precede the file input field)  -->
								<input type='hidden' name='MAX_FILE_SIZE' value='2000000' />
								<div>
									<b>Upload File:</b> <input name='userfile' type='file' size='50'/>
								</div>
								<div>Note: upload image size can not be greater than 1MB</div>
								<div style='margin-top:7px;'>
									<b>URL:</b> <input type='text' name='filepath' size='50'/>
								</div>
								<div style='margin-left:10px;'>
									* url can be relative or absolute
								</div>
							</div>
							<div style="clear:both;">
								<b>Caption:</b> 
								<input name="caption" type="text" size="40" value="" />
							</div>
							<div>
								<b>Notes:</b> 
								<input name="notes" type="text" size="40" value="" />
							</div>
							<div>
								<b>Copyright:</b>
								<input name="copyright" type="text" size="40" value="" />
							</div>
							<div>
								<b>Source Webpage:</b>
								<input name="sourceurl" type="text" size="40" value="" />
							</div>
							<div>
								<b>Sort Sequence:</b>
								<input name="sortseq" type="text" size="40" value="" />
							</div>
							<div style="margin-top:10px;">
								<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
								<input type="submit" name="action" value="Submit New Image" />
							</div>
						</fieldset>
					</form>
				</div>
				<?php
				if(array_key_exists("images",$occArr)){
					?>
					<table>
					<?php 
					$imagesArr = $occArr["images"];
					foreach($imagesArr as $imgId => $imgArr){
						?>
						<tr>
							<td style="width:45%;text-align:center;padding:20px;">
								<?php
								$imgUrl = $imgArr["url"];
								$origUrl = $imgArr["origurl"];
								$tnUrl = $imgArr["tnurl"];
								if(array_key_exists("imageDomain",$GLOBALS)){
									if(substr($imgUrl,0,1)=="/"){
										$imgUrl = $GLOBALS["imageDomain"].$imgUrl;
									}
									if($origUrl && substr($origUrl,0,1)=="/"){
										$origUrl = $GLOBALS["imageDomain"].$origUrl;
									}
									if($tnUrl && substr($tnUrl,0,1)=="/"){
										$tnUrl = $GLOBALS["imageDomain"].$tnUrl;
									}
								}
								?>
								<a href="<?php echo $imgUrl;?>">
									<img src="<?php echo $imgUrl;?>" style="width:90%;" title="<?php echo $imgArr["caption"]; ?>" />
								</a>
								<?php 
								if($origUrl){
									echo "<div><a href='".$origUrl."'>Click on Image to Enlarge</a></div>";
								}
								?>
							</td>
							<td style="text-align:left;padding:30px 10px 10px 10px;">
								<div style="float:right;cursor:pointer;margin:10px;" onclick="toggleSpan('img-<?php echo $imgId; ?>');" title="Edit Image MetaData">
									<img style="border:0px;width:12px;" src="../../images/edit.png" />
								</div>
								<form name='img<?php echo $imgId; ?>form' action='occurrenceeditor.php' method='post'>
									<div style="clear:both;">
										<b>Caption:</b> 
										<span class="img-<?php echo $imgId; ?>">
											<?php echo $imgArr["caption"]; ?>
										</span>
										<span class="img-<?php echo $imgId; ?>" style="display:none;">
											<input name="caption" type="text" size="40" value="<?php echo $imgArr["caption"]; ?>" />
										</span>
									</div>
									<div>
										<b>Notes:</b> 
										<span class="img-<?php echo $imgId; ?>">
											<?php echo $imgArr["notes"]; ?>
										</span>
										<span class="img-<?php echo $imgId; ?>" style="display:none;">
											<input name="notes" type="text" size="40" value="<?php echo $imgArr["notes"]; ?>" />
										</span>
									</div>
									<div>
										<b>Copyright:</b>
										<span class="img-<?php echo $imgId; ?>">
											<?php echo $imgArr["copyright"]; ?>
										</span>
										<span class="img-<?php echo $imgId; ?>" style="display:none;">
											<input name="copyright" type="text" size="40" value="<?php echo $imgArr["copyright"]; ?>" />
										</span>
									</div>
									<div>
										<b>Source Webpage:</b>
										<span class="img-<?php echo $imgId; ?>">
											<a href="<?php echo $imgArr["sourceurl"]; ?>">
												<?php echo $imgArr["sourceurl"]; ?>
											</a>
										</span>
										<span class="img-<?php echo $imgId; ?>" style="display:none;">
											<input name="sourceurl" type="text" size="40" value="<?php echo $imgArr["sourceurl"]; ?>" />
										</span>
									</div>
									<div>
										<b>Web URL: </b>
										<span class="img-<?php echo $imgId; ?>">
											<a href="<?php echo $imgArr["url"]; ?>">
												<?php echo $imgArr["url"]; ?>
											</a>
										</span>
										<span class="img-<?php echo $imgId; ?>" style="display:none;">
											<input name="url" type="text" size="40" value="<?php echo $imgArr["url"]; ?>" />
										</span>
									</div>
									<div>
										<b>Large Image URL: </b>
										<span class="img-<?php echo $imgId; ?>">
											<a href="<?php echo $imgArr["origurl"]; ?>">
												<?php echo $imgArr["origurl"]; ?>
											</a>
										</span>
										<span class="img-<?php echo $imgId; ?>" style="display:none;">
											<input name="origurl" type="text" size="40" value="<?php echo $imgArr["origurl"]; ?>" />
										</span>
									</div>
									<div>
										<b>Thumbnail URL: </b>
										<span class="img-<?php echo $imgId; ?>">
											<a href="<?php echo $imgArr["tnurl"]; ?>">
												<?php echo $imgArr["tnurl"]; ?>
											</a>
										</span>
										<span class="img-<?php echo $imgId; ?>" style="display:none;">
											<input name="tnurl" type="text" size="40" value="<?php echo $imgArr["tnurl"]; ?>" />
										</span>
									</div>
									<div>
										<b>Sort Sequence:</b>
										<span class="img-<?php echo $imgId; ?>">
											<?php echo $imgArr["sortseq"]; ?>
										</span>
										<span class="img-<?php echo $imgId; ?>" style="display:none;">
											<input name="sortseq" type="text" size="40" value="<?php echo $imgArr["sortseq"]; ?>" />
										</span>
									</div>
									<div style="margin-top:10px;">
										<input type="hidden" name="occid" value="<?php echo $occId; ?>" />
										<input type="hidden" name="imgid" value="<?php echo $imgId; ?>" />
										<input type="submit" name="action" value="Submit Image Edits" />
									</div>
								</form>
							</td>
						</tr>
						<?php 
					}
					?>
					</table>
					<?php 
				}				
				?>
			</div>
		</div>
	<?php 
	}
	else{
		echo "Please <a href='../../profile/index.php?refurl=/seinet/collections/editor/occurrenceeditor.php?occid=".$occId."'>login</a>";
	}
	?>
	</div>
<?php 	
	include($serverRoot.'/footer.php');
?>

</body>
</html>
