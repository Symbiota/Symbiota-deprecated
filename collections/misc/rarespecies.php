<?php
include_once("../../util/dbconnection.php");
include_once("../../util/symbini.php");

$rsManager = new RareSpeciesManager();
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";


$editable = 0;
if($isAdmin){
	$editable = 1;
}
if($editable){
	if(array_key_exists("tidtoadd",$_REQUEST) && $_REQUEST["tidtoadd"]){
		$rsManager->addSpecies($_REQUEST["tidtoadd"]);
	}
}

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title>Rare, Threatened, Sensitive Species</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
    <link rel="stylesheet" href="../../css/jqac.css" type="text/css" />
	<script type="text/javascript" src="../../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../../js/jquery.autocomplete-1.4.2.js"></script>
	<script language=javascript>
		var rtXmlHttp;
		var vasXmlHttp;
		var sciNameDeletion;

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

		function removeTaxon(tid, sciName){
	        if(window.confirm('Are you sure you want to delete this taxon?')){
				rtXmlHttp = GetXmlHttpObject();
				if (rtXmlHttp==null){
			  		alert ("Your browser does not support AJAX!");
			  		return;
			  	}
				sciNameDeletion = sciName;
				var url = "rpc/removetid.php";
				url=url + "?tid=" + tid;
				url=url + "&sid="+Math.random();
				rtXmlHttp.onreadystatechange=rtStateChanged;
				rtXmlHttp.open("POST",url,true);
				rtXmlHttp.send(null);
	        }
		} 
		
		function rtStateChanged(){
			if (rtXmlHttp.readyState==4){
				var tidDeleted = rtXmlHttp.responseText;
				sciNameDeletion = sciNameDeletion.replace(/<.{1,2}>/gi,"");
				if(tidDeleted == 0){
					alert("FAILED: Delection of " + sciNameDeletion + " unsuccessful");
				}
				else{
					document.getElementById("tid-"+tidDeleted).style.display = "none";
				}
			}
		}
	
		function validateAddSpecies(sciname){
			var sciName = document.getElementById("speciestoadd").value;
			if(sciName == ""){
				alert("Enter the scientific name of species you wish to add");
				return false;
			}

			vasXmlHttp=GetXmlHttpObject();
			if (vasXmlHttp==null){
		  		alert ("Your browser does not support AJAX!");
		  		return false;
		  	}
			var url="rpc/gettid.php";
			url=url+"?sciname="+sciName;
			url=url+"&sid="+Math.random();
			vasXmlHttp.onreadystatechange=vasStateChanged;
			vasXmlHttp.open("POST",url,true);
			vasXmlHttp.send(null);
			return false;
		} 
		
		function vasStateChanged(){
			if (vasXmlHttp.readyState==4){
				addTid = vasXmlHttp.responseText;
				if(addTid == ""){
					alert("ERROR: Scientific name does not exist in database. Did you spell it correctly? If so, it may have to be added to taxa table.");
				}
				else{
					document.getElementById("tidtoadd").value = addTid;
					document.forms["addspeciesform"].submit();
				}
			}
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

		function initAddList(input){
			$(input).autocomplete({ ajax_get:getAddSuggs, minchars:3 });
		}

		function getAddSuggs(key,cont){ 
		   	var script_name = 'rpc/getspecies.php';
		   	var params = { 'q':key, }
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
	</script>
</head>
<body>
<?php
	$displayLeftMenu = (isset($collections_misc_rarespeciesMenu)?$collections_misc_rarespeciesMenu:"true");
	include($serverRoot."/util/header.php");
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
			<form name="addspeciesform" action='rarespecies.php' method='post' onsubmit="return validateAddSpecies();">
				<fieldset style='margin:5px;background-color:#FFFFCC;'>
					<legend><b>Add Species to List</b></legend>
					<div style="margin:3px;">
						Scientific Name:
						<input type="text" id="speciestoadd" name="speciestoadd" onfocus="initAddList(this)" autocomplete="off" size="35" />
						<input type="hidden" id="tidtoadd" name="tidtoadd" value="" />
					</div>
					<div style="margin:3px;">
						<input type="submit" name="action" value="Add Species"/>
					</div>
				</fieldset>
			</form>
		</div>
		<?php 
	}
	$rsArr = $rsManager->getRareSpeciesList();
	foreach($rsArr as $family => $speciesArr){
		?>
		<h3><?php echo $family; ?></h3>
		<div style='margin-left:20px;'>
		<?php 
		foreach($speciesArr as $tid => $sciName){
			echo "<div id='tid-".$tid."'>".$sciName;
			if($editable){
				?>
				<span class="editobj" style="display:none;cursor:pointer;" onclick="javascript:removeTaxon(<?php echo $tid.",'".$sciName."'";?>)">
					<img src="../../images/del.gif" style="width:13px;" title="remove species from list" />
				</span>
				<?php
			}
			echo "</div>";
		}
		?>
		</div>
		<?php 
	}
?>
</div>
<?php 		
	include($serverRoot."/util/footer.php")
?>
</body>
</html>

<?php
 
 class RareSpeciesManager {
    
 	private $con;
    
    function __construct(){
		$this->con = MySQLiConnectionFactory::getCon("write");
    }
    
 	public function __destruct(){
		if(!($this->con === null)) $this->con->close();
	}
    
	public function getRareSpeciesList(){
 		$returnArr = Array();
		$sql = "SELECT t.tid, ts.Family, t.SciName, t.Author ".
			"FROM taxa t INNER JOIN taxstatus ts ON t.TID = ts.tid ".
			"WHERE ((t.SecurityStatus = 2) AND (ts.taxauthid = 1)) ".
			"ORDER BY ts.Family, t.SciName";
		//echo $sql;
 		$result = $this->con->query($sql);
		if($result) {
			while($row = $result->fetch_object()){
				$returnArr[$row->Family][$row->tid] = "<i>".$row->SciName."</i>&nbsp;&nbsp;".$row->Author;
			}
		}
		$result->close();
		return $returnArr;
 	}
 	
 	public function addSpecies($tid){
 		$sql = "UPDATE taxa t SET t.SecurityStatus = 2 WHERE t.tid = ".$tid;
 		//echo $sql;
		$this->con->query($sql);
		//Update specimen records
		$sql2 = "UPDATE omoccurrences o SET o.LocalitySecurity = 2 WHERE o.tidinterpreted = ".$tid;
		$this->con->query($sql2);
	}
 }
?>