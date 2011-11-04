<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/RareSpeciesManager.php');

$rsManager = new RareSpeciesManager();
$submitAction = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:"";


$editable = 0;
if($isAdmin || array_key_exists("RareSppAdmin",$userRights)){
	$editable = 1;
}
if($editable){
	if($submitAction == "addspecies"){
		$rsManager->addSpecies($_REQUEST["tidtoadd"]);
	}
	elseif($submitAction == "deletespecies"){
		$rsManager->deleteSpecies($_REQUEST["tidtodel"]);
	}
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title>Rare, Threatened, Sensitive Species</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />	
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script language=javascript>
		$(document).ready(function() {
			$("#speciestoadd").autocomplete({ source: "rpc/speciessuggest.php" },{ minLength: 3, autoFocus: true });
		});

		function toggle(target){
		  	var divs = document.getElementsByTagName("div");
		  	for (var i = 0; i < divs.length; i++) {
		  	var divObj = divs[i];
				if(divObj.className == target){
					if(divObj.style.display=="none"){
						divObj.style.display="block";
					}
				 	else {
				 		divObj.style.display="none";
				 	}
				}
			}

		  	var spans = document.getElementsByTagName("span");
		  	for (var h = 0; h < spans.length; h++) {
		  	var spanObj = spans[h];
				if(spanObj.className == target){
					if(spanObj.style.display=="none"){
						spanObj.style.display="inline";
					}
				 	else {
				 		spanObj.style.display="none";
				 	}
				}
			}
		}

		function submitAddSpecies(f){
			var sciName = f.speciestoadd.value;
			if(sciName == ""){
				alert("Enter the scientific name of species you wish to add");
				return false;
			}

			vasXmlHttp=GetXmlHttpObject();
			if (vasXmlHttp==null){
		  		alert ("Your browser does not support AJAX!");
		  		return false;
		  	}
			var url="rpc/gettid.php?sciname="+sciName;
			vasXmlHttp.onreadystatechange=function(){
				if(vasXmlHttp.readyState==4){
					addTid = vasXmlHttp.responseText;
					if(addTid == ""){
						alert("ERROR: Scientific name does not exist in database. Did you spell it correctly? If so, it may have to be added to taxa table.");
					}
					else{
						f.tidtoadd.value = addTid;
						f.submit();
					}
				}
			};
			vasXmlHttp.open("POST",url,true);
			vasXmlHttp.send(null);
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
<body>
<?php
	$displayLeftMenu = (isset($collections_misc_rarespeciesMenu)?$collections_misc_rarespeciesMenu:true);
	include($serverRoot.'/header.php');
	if(isset($collections_misc_rarespeciesCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $collections_misc_rarespeciesCrumbs;
		echo " <b>Sensitive Species for Masking Locality Details</b>";
		echo "</div>";
	}
	?>
<!-- This is inner text! -->
<div id="innertext">

	<?php 
	if($editable){
		?>
		<div style="float:right;cursor:pointer;" onclick="javascript:toggle('editobj');" title="Toggle Editing Functions">
			<img style="border:0px;" src="../../images/edit.png" />
		</div>
		<?php 
	}
	?>
	<h1>Rare, Threatened, Sensitive Species</h1>
	<div style='margin-left:10px;'>The following species have a protective status within <?php echo $defaultTitle; ?>.  
	Sensitive population numbers and a threatened status are the typical cause for this though some 
	species that are cherished by collectors (Orchids and Cacti) or wild harvesters will also occur 
	on this list. In some cases, whole families have a blanket protection. Specific locality 
	information is withheld from lists and maps within the search engine for the following species.</div>
		
<?php
	if($editable){
		?>
		<div class="editobj" style="display:none;width:400px;">
			<form name="addspeciesform" action='rarespecies.php' method='post'>
				<fieldset style='margin:5px;background-color:#FFFFCC;'>
					<legend><b>Add Species to List</b></legend>
					<div style="margin:3px;">
						Scientific Name:
						<input type="text" id="speciestoadd" name="speciestoadd" />
						<input type="hidden" id="tidtoadd" name="tidtoadd" value="" />
					</div>
					<div style="margin:3px;">
						<input type="hidden" name="submitaction" value="addspecies" />
						<input type="button" value="Add Species" onclick="submitAddSpecies(this.form)" />
					</div>
				</fieldset>
			</form>
		</div>
		<?php 
	}
	$rsArr = $rsManager->getRareSpeciesList();
	if($rsArr){
		foreach($rsArr as $family => $speciesArr){
			?>
			<h3><?php echo $family; ?></h3>
			<div style='margin-left:20px;'>
			<?php 
			foreach($speciesArr as $tid => $sciName){
				echo "<div id='tid-".$tid."'>".$sciName;
				if($editable){
					?>
					<span class="editobj" style="display:none;">
						<a href="rarespecies.php?submitaction=deletespecies&tidtodel=<?php echo $tid;?>">
							<img src="../../images/del.gif" style="width:13px;border:0px;" title="remove species from list" />
						</a>
					</span>
					<?php
				}
				echo "</div>";
			}
			?>
			</div>
			<?php 
		}
	}
	else{
		?>
		<div sytle="margin:30px;font-weight:bold;font-size:120px;">
			No species have been marked as sensitive within the system.
		</div>
		<?php 
	}
?>
</div>
<?php 		
	include($serverRoot.'/footer.php')
?>
</body>
</html>
