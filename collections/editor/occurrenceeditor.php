<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceEditorManager.php');
header("Content-Type: text/html; charset=".$charset);

$occId = array_key_exists("occid",$_REQUEST)?$_REQUEST["occid"]:"";

$occManager = new OccurrenceEditorManager();
$occArr = Array();
$editable = 0;
if($occId){
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
	<script type="text/javascript" src="../../js/tabcontent.js"></script>
	<script language="javascript">
		function initTabs(tabObjId){
			var dTabs=new ddtabcontent(tabObjId); 
			dTabs.setpersist(true);
			dTabs.setselectedClassTarget("link"); 
			dTabs.init();
		}

		function toggle(target){
			var divObjs = document.getElementsByTagName("div");
		  	for (i = 0; i < divObjs.length; i++) {
		  		var obj = divObjs[i];
		  		if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
						if(obj.style.display=="none"){
							obj.style.display="inline";
						}
				 	else {
				 		obj.style.display="none";
				 	}
				}
			}
		}
	</script>
</head>
<body onload="initTabs('occedittabs');">

<?php
	$displayLeftMenu = (isset($collections_individual_occurrenceEditorMenu)?$collections_individual_occurrenceEditorMenu:false);
	include($serverRoot.'/header.php');
	if(isset($collections_individual_occurrenceEditorCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $collections_individual_occurrenceEditorCrumbs;
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
	        <li><a href="#" rel="shortdiv" class="selected">All Fields</a></li>
	        <li><a href="#" rel="eventdiv">Occurrence Event</a></li>
	        <li><a href="#" rel="identdiv">Identification</a></li>
	        <li><a href="#" rel="localitydiv">Locality</a></li>
	    </ul>
		<div style="border:1px solid gray;width:800px;margin-bottom:1em;padding:10px;">
			<div id="shortdiv" class="tabcontent" style="margin:10px;">
				<form id='shortform' name='shortform' action='occurrenceeditor.php' method='get'>
					<div style="margin:10px;padding:10px;height:120px;border:1px solid gray;">
						<div style="clear:both;" class="p1">
							<span style="width:125px;">
								Scientific Name:
							</span>
							<span style="margin-left:300px;">
								Author:
							</span>
						</div>
						<div style="clear:both;" class="p1">
							<span>
								<input type="text" name="sciname" size="60" maxlength="250" tabindex="1" value="<?php echo array_key_exists("sciname",$occArr)?$occArr["sciname"]["value"]:""; ?>" />
							</span>
							<span style="margin-left:10px;">
								<input type="text" name="author" maxlength="100" tabindex="0" value="<?php echo array_key_exists("author",$occArr)?$occArr["author"]["value"]:""; ?>" />
							</span>
						</div>
						<div style="clear:both;padding:3px 0px 0px 10px;" class="p1">
							<div style="float:left;">
								ID Qualifier:
								<input type="text" name="identificationQualifier" tabindex="2" size="5" value="<?php echo array_key_exists("identificationqualifier",$occArr)?$occArr["identificationqualifier"]["value"]:""; ?>" />
							</div>
							<div style="float:left;margin-left:160px;">
								Family:
								<input type="text" name="family" size="30" maxlength="50" tabindex="0" value="<?php echo array_key_exists("family",$occArr)?$occArr["family"]["value"]:""; ?>" />
							</div>
						</div>
						<div style="clear:both;padding:3px 0px 0px 10px;" class="p1">
							<div style="float:left;">
								Identified By:
								<input type="text" name="identifiedBy" maxlength="255" tabindex="3" value="<?php echo array_key_exists("identifiedby",$occArr)?$occArr["identifiedby"]["value"]:""; ?>" />
							</div>
							<div style="float:left;margin-left:15px;">
								Date Identified:
								<input type="text" name="dateIdentified" maxlength="45" tabindex="4" value="<?php echo array_key_exists("dateidentified",$occArr)?$occArr["dateidentified"]["value"]:""; ?>" />
							</div>
						</div>
						<div style="clear:both;padding:3px 0px 0px 10px;">
							<div style="float:left;">
								ID References:
								<input type="text" name="identificationReferences" tabindex="5" value="<?php echo array_key_exists("identificationreferences",$occArr)?$occArr["identificationreferences"]["value"]:""; ?>" />
							</div>
							<div style="float:left;margin-left:20px;">
								ID Remarks:
								<input type="text" name="taxonRemarks" tabindex="6" value="<?php echo array_key_exists("identificationremarks",$occArr)?$occArr["identificationremarks"]["value"]:""; ?>" />
							</div>
						</div>
					</div>
					<div style="margin:10px;padding:10px;height:75px;border:1px solid gray">
						<div style="clear:both;" class="p1">
							<span>
								Collector:
							</span>
							<span style="margin-left:210px;">
								Number:
							</span>
							<span style="margin-left:60px;">
								Date:
							</span>
							<span style="margin-left:80px;">
								Verbatim Date:
							</span>
						</div>
						<div style="clear:both;" class="p1">
							<span>
								<input type="text" name="recordedBy" size="35" maxlength="255" tabindex="7" value="<?php echo array_key_exists("recordedBy",$occArr)?$occArr["recordedBy"]:""; ?>" />
							</span>
							<span style="margin-left:20px;">
								<input type="text" name="recordNumber" size="10" maxlength="45" tabindex="8" value="<?php echo array_key_exists("recordNumber",$occArr)?$occArr["recordNumber"]:""; ?>" />
							</span>
							<span style="margin-left:20px;">
								<input type="text" name="eventDate" size="10" tabindex="12" value="<?php echo array_key_exists("eventDate",$occArr)?$occArr["eventDate"]:""; ?>" />
							</span>
							<img src="" />
							<span style="margin-left:20px;">
								<input type="text" name="verbatimEventDate" tabindex="0" maxlength="255" value="<?php echo array_key_exists("verbatimEventDate",$occArr)?$occArr["verbatimEventDate"]:""; ?>" />
							</span>
						</div>
						<div style="clear:both;padding:2px 0px 0px 10px;">
							<div style="float:left;">
								Associated Collectors:
								<input type="text" name="associatedCollectors" tabindex="15" maxlength="255" value="<?php echo array_key_exists("associatedCollectors",$occArr)?$occArr["associatedCollectors"]:""; ?>" />
							</div>
							<div style="float:left;margin-left:90px;">
								<span>
									<input type="text" name="month" tabindex="0" size="1" value="<?php echo array_key_exists("month",$occArr)?$occArr["month"]:""; ?>" title="Month" />/
								</span>
								<span>
									<input type="text" name="day" tabindex="0" size="1" value="<?php echo array_key_exists("day",$occArr)?$occArr["day"]:""; ?>" title="Day" />/
								</span>
								<span>
									<input type="text" name="year" tabindex="0" size="2" value="<?php echo array_key_exists("year",$occArr)?$occArr["year"]:""; ?>" title="Year" />
								</span>
								<span style="margin-left:30px;">
									<input type="text" name="startDayOfYear" tabindex="0" size="3" value="<?php echo array_key_exists("startDayOfYear",$occArr)?$occArr["startDayOfYear"]:""; ?>" title="Start Day of Year" /> -
								</span>
								<span>
									<input type="text" name="endDayOfYear" tabindex="0" size="3" value="<?php echo array_key_exists("endDayOfYear",$occArr)?$occArr["endDayOfYear"]:""; ?>" title="End Day of Year" />
								</span>
							</div>
						</div>
					</div>
					<div style="margin:10px;padding:10px;border:1px solid gray">
						<div style="padding:3px;">
							Habitat:
							<input type="text" name="habitat" tabindex="18" size="80" value="<?php echo array_key_exists("habitat",$occArr)?$occArr["habitat"]:""; ?>" />
						</div>
						<div style="padding:3px;">
							Associated Taxa:
							<input type="text" name="associatedTaxa" tabindex="20" size="80" value="<?php echo array_key_exists("associatedTaxa",$occArr)?$occArr["associatedTaxa"]:""; ?>" />
						</div>
						<div style="padding:3px;">
							Description (Attributes):
							<input type="text" name="attributes" tabindex="23" size="70" value="<?php echo array_key_exists("attributes",$occArr)?$occArr["attributes"]:""; ?>" title="Description of Organism" />
						</div>
						<div style="padding:3px;">
							Occurrence Remarks:
							<input type="text" name="attributes" tabindex="25" size="75" value="<?php echo array_key_exists("attributes",$occArr)?$occArr["attributes"]:""; ?>" title="Description of Organism" />
						</div>
					</div>
					<div style="margin:10px;padding:10px;border:1px solid gray">
						<div style="padding:3px;">
							<span>
								Catalog Number:
								<input type="text" name="catalogNumber" tabindex="28" maxlength="32" value="<?php echo array_key_exists("catalogNumber",$occArr)?$occArr["catalogNumber"]:""; ?>" />
							</span>
							<span style="margin-left:30px;">
								Occurrence ID (GUID):
								<input type="text" name="occurrenceID" tabindex="30" maxlength="255" value="<?php echo array_key_exists("occurrenceID",$occArr)?$occArr["occurrenceID"]:""; ?>" title="Global Unique Identifier" />
							</span>
						</div>
						<div style="padding:3px;">
							<span>
								Type Status:
								<input type="text" name="typeStatus" tabindex="33" maxlength="255" value="<?php echo array_key_exists("typeStatus",$occArr)?$occArr["typeStatus"]:""; ?>" />
							</span>
							<span style="margin-left:30px;">
								Disposition:
								<input type="text" name="disposition" tabindex="35" maxlength="32" value="<?php echo array_key_exists("disposition",$occArr)?$occArr["disposition"]:""; ?>" />
							</span>
						</div>
						<div style="padding:3px;">
							<span>
								Reproductive Condition:
								<input type="text" name="reproductiveCondition" tabindex="38" maxlength="255" value="<?php echo array_key_exists("reproductiveCondition",$occArr)?$occArr["reproductiveCondition"]:""; ?>" />
							</span>
							<span style="margin-left:30px;">
								Establishment Means:
								<input type="text" name="establishmentMeans" tabindex="45" maxlength="32" value="<?php echo array_key_exists("establishmentMeans",$occArr)?$occArr["establishmentMeans"]:""; ?>" />
							</span>
							<span style="margin-left:30px;">
								<input type="checkbox" name="cultivationStatus" tabindex="48" value="<?php echo array_key_exists("cultivationStatus",$occArr)?$occArr["cultivationStatus"]:0; ?>" />
								Cultivated
							</span>
						</div>
						<div style="padding:3px;">
							<span>
								Owner InstitutionCode:
								<input type="text" name="ownerInstitutionCode" tabindex="50" maxlength="32" value="<?php echo array_key_exists("ownerInstitutionCode",$occArr)?$occArr["ownerInstitutionCode"]:""; ?>" />
							</span>
							<span style="margin-left:30px;">
								Other Catalog Numbers:
								<input type="text" name="otherCatalogNumbers" tabindex="53" maxlength="255" value="<?php echo array_key_exists("otherCatalogNumbers",$occArr)?$occArr["otherCatalogNumbers"]:""; ?>" />
							</span>
						</div>
					</div>
					<fieldset>
						<legend>Other</legend>
						<div style="padding:3px;">
							<span>
								Basis of Record:
								<input type="text" name="basisOfRecord" tabindex="55" maxlength="32" value="<?php echo array_key_exists("basisOfRecord",$occArr)?$occArr["basisOfRecord"]:""; ?>" />
							</span>
							<span style="margin-left:20px;">
								Language:
								<input type="text" name="language" tabindex="53" maxlength="20" value="<?php echo array_key_exists("language",$occArr)?$occArr["language"]:""; ?>" />
							</span>
							<span style="margin-left:20px;">
								Dataset ID:
								<input type="text" name="datasetID" tabindex="53" maxlength="255" value="<?php echo array_key_exists("datasetID",$occArr)?$occArr["datasetID"]:""; ?>" />
							</span>
						</div>
						<div style="padding:3px;">
							Associated Occurrences:
							<input type="text" name="associatedOccurrences" tabindex="53" value="<?php echo array_key_exists("associatedOccurrences",$occArr)?$occArr["associatedOccurrences"]:""; ?>" />
						</div>
						<div style="padding:3px;">
							Field Notes:
							<input type="text" name="fieldNotes" tabindex="53" value="<?php echo array_key_exists("fieldNotes",$occArr)?$occArr["fieldNotes"]:""; ?>" />
						</div>
						<div style="padding:3px;">
							Dynamic Properties:
							<input type="text" name="dynamicProperties" tabindex="53" value="<?php echo array_key_exists("dynamicProperties",$occArr)?$occArr["dynamicProperties"]:""; ?>" />
						</div>
						
					</fieldset>
				</form>
			</div>
			<form id='longform' name='longform' action='occurrenceeditor.php' method='get'>
				<div id="eventdiv" class="tabcontent" style="margin:10px;">
					
				</div>
				<div id="identdiv" class="tabcontent" style="margin:10px;">
				</div>
				<div id="localitydiv" class="tabcontent" style="margin:10px;">
					
				</div>
			</form>
		</div>
	<?php 
	}
	?>
	</div>
<?php 	
	include($serverRoot.'/footer.php');
?>

</body>
</html>
