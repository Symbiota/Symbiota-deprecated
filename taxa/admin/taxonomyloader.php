<?php
/*
 * Created on 10 Aug 2009
 * E.E. Gilbert
 */

 //error_reporting(E_ALL);
 //set_include_path( get_include_path() . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT']."" );
 include_once("../../util/dbconnection.php");
 include_once("../../util/symbini.php");
  
 $target = array_key_exists("target",$_REQUEST)?$_REQUEST["target"]:"";
 $action = array_key_exists("taxonsubmit",$_REQUEST)?$_REQUEST["taxonsubmit"]:"";
 $status = "";

 $dataArr = Array();
 if($action){
	 $dataArr["sciname"] = (array_key_exists("sciname",$_REQUEST)?$_REQUEST["sciname"]:""); 
	 $dataArr["author"] = (array_key_exists("author",$_REQUEST)?$_REQUEST["author"]:"");
	 $dataArr["rankid"] = (array_key_exists("rankid",$_REQUEST)?$_REQUEST["rankid"]:"");
	 $dataArr["unitind1"] = (array_key_exists("unitind1",$_REQUEST)?$_REQUEST["unitind1"]:""); 
	 $dataArr["unitname1"] = (array_key_exists("unitname1",$_REQUEST)?$_REQUEST["unitname1"]:""); 
	 $dataArr["unitind2"] = (array_key_exists("unitind2",$_REQUEST)?$_REQUEST["unitind2"]:""); 
	 $dataArr["unitname2"] = (array_key_exists("unitname2",$_REQUEST)?$_REQUEST["unitname2"]:""); 
	 $dataArr["unitind3"] = (array_key_exists("unitind3",$_REQUEST)?$_REQUEST["unitind3"]:""); 
	 $dataArr["unitname3"] = (array_key_exists("unitname3",$_REQUEST)?$_REQUEST["unitname3"]:""); 
	 $dataArr["parenttid"] = (array_key_exists("parenttid",$_REQUEST)?$_REQUEST["parenttid"]:"");
	 $dataArr["source"] = (array_key_exists("source",$_REQUEST)?$_REQUEST["source"]:"");
	 $dataArr["notes"] = (array_key_exists("notes",$_REQUEST)?$_REQUEST["notes"]:"");
	 $dataArr["securitystatus"] = (array_key_exists("securitystatus",$_REQUEST)?$_REQUEST["securitystatus"]:"");
	 $dataArr["family"] = (array_key_exists("family",$_REQUEST)?$_REQUEST["family"]:"");
	 $dataArr["uppertaxonomy"] = (array_key_exists("uppertaxonomy",$_REQUEST)?$_REQUEST["uppertaxonomy"]:"");
	 $dataArr["newuppertaxon"] = (array_key_exists("newuppertaxon",$_REQUEST)?$_REQUEST["newuppertaxon"]:"");
	 $dataArr["UnacceptabilityReason"] = (array_key_exists("UnacceptabilityReason",$_REQUEST)?$_REQUEST["UnacceptabilityReason"]:"");
	 $dataArr["tidaccepted"] = (array_key_exists("tidaccepted",$_REQUEST)?$_REQUEST["tidaccepted"]:"");
	 $dataArr["acceptstatus"] = (array_key_exists("acceptstatus",$_REQUEST)?$_REQUEST["acceptstatus"]:0);
 }
 $loaderObj = new TaxonLoader();
 
 $editable = false;
 if($isAdmin || array_key_exists("",$userRights)){
 	$editable = true;
 }
 
 if($dataArr && $editable){
	$status = $loaderObj->loadNewName($dataArr);
 }
 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<title><?php echo $defaultTitle; ?> Taxon Loader: </title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css"/>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
</head>
<body onload="">
<?php
$displayLeftMenu = (isset($taxa_admin_taxonomyloaderMenu)?$taxa_admin_taxonomyloaderMenu:"true");
include($serverRoot."/util/header.php");
if(isset($taxa_admin_taxonomyloaderCrumbs)){
	echo "<div class='navpath'>";
	echo "<a href='../index.php'>Home</a> &gt; ";
	echo $taxa_admin_taxonomyloaderCrumbs;
	echo " <b>Taxonomy Loader</b>";
	echo "</div>";
}
?>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($editable){
			if($status){
				echo "<div style='color:red;font-size:120%;'>".$status."</div>";
			}
		?>
		
			<form id="loaderform" action="taxonomyloader.php" method="get" onsubmit="return validate();">
				<fieldset>
					<legend>New Taxon</legend>
					<div>
						<div style="float:left;width:110px;">Taxon Name:</div>
						<input type="text" id="sciname" name="sciname" style="width:200px;border:inset;" value="<?php echo $target;?>" onchange="parseName(this)"/>
					</div>
					<div>
						<div style="float:left;width:110px;">Author:</div>
						<input type='text' id='author' name='author' style='width:200px;border:inset;' />
					</div>
					<div style="margin-top:5px;">
						<div style="float:left;width:110px;">Rank:</div>
						<select id="rankid" name="rankid" title="Rank ID" onchange=""  style="border:inset;">
						<?php 
							$loaderObj->echoTaxonRanks();
						?>
						</select>
					</div>
					<div>
						<div style="float:left;width:110px;">Genus:</div>
						<input type='text' id='unitind1' name='unitind1' style='width:20px;border:inset;' title='Genus hybrid indicator'/>
						<input type='text' id='unitname1' name='unitname1' style='width:200px;border:inset;' title='Genus or Base Name'/>
					</div>
					<div>
						<div style="float:left;width:110px;">Epithet:</div>
						<input type='text' id='unitind2' name='unitind2' style='width:20px;border:inset;' title='Species hybrid indicator'/>
						<input type='text' id='unitname2' name='unitname2' style='width:200px;border:inset;' title='epithet'/>
					</div>
					<div>
						<div style="float:left;width:110px;">Infrasp:</div>
						<input type='text' id='unitind3' name='unitind3' style='width:40px;border:inset;' title='Rank: e.g. ssp., var., f.'/>
						<input type='text' id='unitname3' name='unitname3' style='width:200px;border:inset;' title='infrasp. epithet'/>
					</div>
					<div id="uppertaxondiv" name="uppertaxondiv" style="margin-top:5px;position:relative;overflow:visible">
						<div style="float:left;width:110px;">Upper Taxonomy:</div>
						<select id="uppertaxonomy" name="uppertaxonomy" style="border:inset;">
							<option value="">Select an Upper Taxon</option>
							<option value=""></option>
							<?php
								$loaderObj->echoUpperTaxa();
							?>
						</select>
						<span style="cursor:pointer;border:1px solid black;" onclick="document.getElementById('uppertaxondiv').style.display='none';document.getElementById('newuppertaxondiv').style.display='block';">
							<img src="../../images/add.png" style="height:12px" title="Add a New Upper Taxon to List"/>
						</span>
					</div>
					<div id="newuppertaxondiv" name="newuppertaxondiv" style="display:none;margin-top:5px;">
						<div style="float:left;width:110px;">New Upper Taxon:</div>
						<input type="text" id="newuppertaxon" name="newuppertaxon" style="border:inset;width:200px;"/>
					</div>
					<div>
						<div style="float:left;width:110px;">Family:</div>
						<input type='text' id='family' name='family' style='width:200px;border:inset;' title='Family'/>
					</div>
					<div>
						<div style="float:left;width:110px;">Parent Taxon:</div>
						<input type="text" id="parentname" name="parentname" style="width:200px;border:inset;" onchange="checkParentExistance(this.value)" />
						<span id="addparentspan" style="display:none;"><a id="addparentanchor" href="taxonomyloader.php?target=" target="_blank">Add Parent</a></span>
						<input type="hidden" id="parenttid" name="parenttid" value="" />
					</div>
					<div>
						<div style="float:left;width:110px;">Notes:</div>
						<input type='text' id='notes' name='notes' style='width:200px;border:inset;' title=''/>
					</div>
					<div>
						<div style="float:left;width:110px;">Source:</div>
						<input type='text' id='source' name='source' style='width:200px;border:inset;' title=''/>
					</div>
					<div>
						Locality Security Status:
						<select id="securitystatus" name="securitystatus" style='border:inset;'>
							<option value="1">No Security</option>
							<option value="2">Hide Locality Details</option>
						</select>
					</div>
					<fieldset>
						<legend>Acceptance Status</legend>
						<div>
							<input type="radio" id="isaccepted" name="acceptstatus" value="1" onchange="acceptanceChanged()" checked> Accepted
							<input type="radio" id="isnotaccepted" name="acceptstatus" value="0" onchange="acceptanceChanged()"> Not Accepted
						</div>
						<div id="accdiv" style="display:none;">
							Accepted Taxon: 
							<select id="tidaccepted" name="tidaccepted">
								<option value="">Loading Species List</option>
							</select>
							<div>
								<div style="float:left;width:150px;">UnacceptabilityReason:</div>
								<input type='text' id='UnacceptabilityReason' name='UnacceptabilityReason' style='width:200px;border:inset;' title=''/>
							</div>
						</div>
					</fieldset>
					<div>
						<input type="submit" id="taxonsubmit" name="taxonsubmit" value="Submit New Name"/>
					</div>
				</fieldset>
			</form>
		</div>
		<?php 
		}
		else{
			echo "<div>You must be logged in and authorized to view this page. Please login.</div>";
		}
		include($serverRoot."/util/footer.php");
		?>
		
	<script type="text/javascript">

		var cpeXmlHttp;
		var galXmlHttp;
		var sutXmlHttp;
		var cseXmlHttp;

		function loadBody(){
			if(document.getElementById("sciname").value != ""){
				parseName(document.getElementById("sciname"));
			}
		}
		
		function validate(){
			var errorStr = "";
			if(document.getElementById("sciname").value == "") errorStr += "Scientific Name \n"; 
			if(document.getElementById("unitname1").value == "") errorStr += "Genus (Base Name) \n"; 
			if(document.getElementById("parenttid").value == "") errorStr += "Parent Name \n"; 
			if(errorStr != ""){
				alert("Following Fields Required:\n"+errorStr);
				return false;
			}

			if(document.getElementById("uppertaxonomy").value == "" && document.getElementById("newuppertaxon").value == "") errorStr += "Upper Taxonomy \n"; 
			if(document.getElementById("family").value == "" || document.getElementById("family").value == "undefined") errorStr += "family \n"; 
			if(errorStr != ""){
				var answer = confirm("Following fields are recommended. Are you sure you want to leave them blank?\n"+errorStr);
				return answer;
			}
			 
			return true;
		}

		function parseName(sciNameObj){

			var sciName = trim(sciNameObj.value);
			checkScinameExistance(sciName);
			document.getElementById("loaderform").reset();
			document.getElementById("sciname").value = sciName;
			var sciNameArr = new Array(); 
			var activeIndex = 0;
			var unitName1 = "";
			var unitName2 = "";
			var rankId = 180;
			sciNameArr = sciName.split(' ');

			if(sciNameArr[activeIndex].length == 1){
				document.getElementById("unitind1").value = sciNameArr[activeIndex];
				document.getElementById("unitname1").value = sciNameArr[activeIndex+1];
				unitName1 = sciNameArr[activeIndex+1];
				activeIndex = 2;
			}
			else{
				document.getElementById("unitname1").value = sciNameArr[activeIndex];
				unitName1 = sciNameArr[activeIndex];
				activeIndex = 1;
			}
			if(sciNameArr.length > activeIndex){
				if(sciNameArr[activeIndex].length == 1){
					document.getElementById("unitind2").value = sciNameArr[activeIndex];
					document.getElementById("unitname2").value = sciNameArr[activeIndex+1];
					unitName2 = sciNameArr[activeIndex+1];
					activeIndex = activeIndex+2;
				}
				else{
					document.getElementById("unitname2").value = sciNameArr[activeIndex];
					unitName2 = sciNameArr[activeIndex];
					activeIndex = activeIndex+1;
				}
				rankId = 220;
			}
			if(sciNameArr.length > activeIndex){
				if(sciNameArr[activeIndex].substring(sciNameArr[activeIndex].length-1,sciNameArr[activeIndex].length) == "." || sciNameArr[activeIndex].length == 1){
					rankName = sciNameArr[activeIndex];
					document.getElementById("unitind3").value = sciNameArr[activeIndex];
					document.getElementById("unitname3").value = sciNameArr[activeIndex+1];
					if(sciNameArr[activeIndex] == "ssp." || sciNameArr[activeIndex] == "subsp.") rankId = 230;
					if(sciNameArr[activeIndex] == "var.") rankId = 240;
					if(sciNameArr[activeIndex] == "f.") rankId = 260;
					if(sciNameArr[activeIndex] == "x" || sciNameArr[activeIndex] == "X") rankId = 220;
				}
				else{
					document.getElementById("unitname3").value = sciNameArr[activeIndex];
					rankId = 230;
				}
			}
			if(unitName1.indexOf("aceae") == (unitName1.length - 5) || unitName1.indexOf("idae") == (unitName1.length - 4)){
				rankId = 140;
				document.getElementById("family").value = unitName1;  
			}
			document.getElementById("rankid").value = rankId;
			if(rankId >= 140){
				setUpperTaxonomy(unitName1);
			}
		}

		function setParent(){
			var rankId = document.getElementById("rankid").value;
			var unitName1 = document.getElementById("unitname1").value;
			var unitName2 = document.getElementById("unitname2").value;
			var parentName = "";
			if(rankId == 180){
				parentName = document.getElementById("family").value;
			}
			else if(rankId == 220){
				parentName = unitName1; 
			}
			else if(rankId > 220){
				parentName = unitName1 + " " + unitName2; 
			}
			document.getElementById("parentname").value = parentName;
			checkParentExistance(parentName);
		}			

		function checkScinameExistance(sciname){
			if (sciname.length == 0){
		  		return;
		  	}
			cseXmlHttp=GetXmlHttpObject();
			if (cseXmlHttp==null){
		  		alert ("Your browser does not support AJAX!");
		  		return;
		  	}
			var url="rpc/gettid.php";
			url=url+"?sciname="+sciname;
			url=url+"&sid="+Math.random();
			cseXmlHttp.onreadystatechange=cseStateChanged;
			cseXmlHttp.open("POST",url,true);
			cseXmlHttp.send(null);
		} 
		
		function cseStateChanged(){
			if (cseXmlHttp.readyState==4){
				var responseStr = cseXmlHttp.responseText;
				if(responseStr != ""){
					var sciName = document.getElementById("sciname").value;
					alert("INSERT FAILED: "+sciName+" ("+responseStr+")"+" already exists in database.");
					return false;
				}
				return true;
			}
		}

		function acceptanceChanged(){
			if(document.getElementById("isaccepted").checked == true){
				document.getElementById("accdiv").style.display = "none";
			}
			else{
				document.getElementById("accdiv").style.display = "block";
				getAcceptedList(document.getElementById("family").value);
			}
		}

		function setUpperTaxonomy(str){
			if (str.length == 0){
		  		return;
		  	}
			sutXmlHttp=GetXmlHttpObject();
			if (sutXmlHttp==null){
		  		alert ("Your browser does not support AJAX!");
		  		return;
		  	}
			var url="rpc/getuppertaxonomy.php";
			url=url+"?sciname="+str;
			url=url+"&sid="+Math.random();
			sutXmlHttp.onreadystatechange=sutStateChanged;
			sutXmlHttp.open("POST",url,true);
			sutXmlHttp.send(null);
		} 
		
		function sutStateChanged(){
			if (sutXmlHttp.readyState==4){
				var responseStr = sutXmlHttp.responseText;
				var responseArr = new Array();
				responseArr = responseStr.split("|");
				if(responseArr.length = 2){
					document.getElementById("uppertaxonomy").value = responseArr[0];
					document.getElementById("family").value = responseArr[1];
				}
				setParent();
			}
		}

		function getAcceptedList(family){
			if (family.length == 0){
		  		return;
		  	}
			galXmlHttp=GetXmlHttpObject();
			if (galXmlHttp==null){
		  		alert ("Your browser does not support AJAX!");
		  		return;
		  	}
			var url="rpc/getacceptedlist.php";
			url=url+"?family="+family;
			url=url+"&sid="+Math.random();
			galXmlHttp.onreadystatechange=galStateChanged;
			galXmlHttp.open("POST",url,true);
			galXmlHttp.send(null);
		} 
		
		function galStateChanged(){
			if (galXmlHttp.readyState==4){
				document.getElementById("tidaccepted").innerHTML=galXmlHttp.responseText;
			}
			document.getElementById("accdiv").style.display = "block";
		}

		function checkParentExistance(parentStr){
			if (parentStr.length == 0){
		  		return;
		  	}
			cpeXmlHttp=GetXmlHttpObject();
			if (cpeXmlHttp==null){
		  		alert ("Your browser does not support AJAX!");
		  		return;
		  	}
			var url="rpc/getparenttid.php";
			url=url+"?parent="+parentStr;
			url=url+"&sid="+Math.random();
			cpeXmlHttp.onreadystatechange=cpeStateChanged;
			cpeXmlHttp.open("POST",url,true);
			cpeXmlHttp.send(null);
		} 
		
		function cpeStateChanged(){
			if (cpeXmlHttp.readyState==4){
				var parentTid = cpeXmlHttp.responseText;
				document.getElementById("parenttid").value = parentTid;
				if(parentTid == "empty"){
					alert("Parent does not exist. Please first add parent to system. This can be done by clicking on 'Add Parent' button to the right of parent name.");
					document.getElementById("addparentspan").style.display = "inline";
					document.getElementById("addparentanchor").href = "taxonomyloader.php?target="+document.getElementById("parentname").value;
					return false;
				}
				return true;
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

		function trim(stringToTrim) {
			return stringToTrim.replace(/^\s+|\s+$/g,"");
		}
		
	</script>

</body>
</html>

<?php 
class TaxonLoader{

	private $conn;
	
	public function __construct(){
		$this->setConnection();
	}
	
	function __destruct(){
		$this->conn->close();
	}
	
	private function setConnection($conType = "write"){
 		$this->conn = MySQLiConnectionFactory::getCon($conType);
 	}
 	
	public function echoTaxonRanks(){
		$sql = "SELECT tu.rankid, tu.rankname FROM taxonunits tu ORDER BY tu.rankid";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$rankId = $row->rankid;
			$rankName = $row->rankname;
			echo "<option value='".$rankId."' ".($rankId==220?" SELECTED":"").">".$rankName."</option>\n";
		}
	}
	
	public function loadNewName($dataArr){
		//Load new name into taxa table
		$sqlTaxa = "INSERT INTO taxa(sciname, author, rankid, unitind1, unitname1, unitind2, unitname2, unitind3, unitname3, ".
			"source, notes, securitystatus) ".
			"VALUES (\"".$dataArr["sciname"]."\",".($dataArr["author"]?"\"".$dataArr["author"]."\"":"NULL").",".$dataArr["rankid"].
			",".($dataArr["unitind1"]?"\"".$dataArr["unitind1"]."\"":"NULL").",\"".$dataArr["unitname1"]."\",".
			($dataArr["unitind2"]?"\"".$dataArr["unitind2"]."\"":"NULL").",".($dataArr["unitname2"]?"\"".$dataArr["unitname2"]."\"":"NULL").
			",".($dataArr["unitind3"]?"\"".$dataArr["unitind3"]."\"":"NULL").",".($dataArr["unitname3"]?"\"".$dataArr["unitname3"]."\"":"NULL").
			",".($dataArr["source"]?"\"".$dataArr["source"]."\"":"NULL").",".($dataArr["notes"]?"\"".$dataArr["notes"]."\"":"NULL").
			",".$dataArr["securitystatus"].")";
		//echo "sqlTaxa: ".$sqlTaxa;
		if(!$this->conn->query($sqlTaxa)){
			return "Taxon Insert FAILED: sql = ".$sqlTaxa;
		}
		$tid = $this->conn->insert_id;
		if($dataArr["acceptstatus"]){
			$tidAccepted = $tid;
		}
		else{
			$tidAccepted = $dataArr["tidaccepted"];
		}
		
	 	//Load accepteance status into taxstatus table
	 	$hierarchy = $this->getHierarchy($dataArr["parenttid"]);
	 	$upperTaxon = ($dataArr["newuppertaxon"]?$dataArr["newuppertaxon"]:$dataArr["uppertaxonomy"]);
		$sqlTaxStatus = "INSERT INTO taxstatus(tid, tidaccepted, taxauthid, family, uppertaxonomy, parenttid, UnacceptabilityReason, hierarchystr) ".
			"VALUES (".$tid.",".$tidAccepted.",1,".($dataArr["family"]?"\"".$dataArr["family"]."\"":"NULL").",".
			($upperTaxon?"\"".$upperTaxon."\"":"NULL").",".$dataArr["parenttid"].",\"".$dataArr["UnacceptabilityReason"]."\",\"".$hierarchy."\") ";
		//echo "sqlTaxStatus: ".$sqlTaxStatus;
		if(!$this->conn->query($sqlTaxStatus)){
			return "Taxon inserted, but taxonomy insert FAILED: sql = ".$sqlTaxa;
		}
	 	
	 	header("Location: taxonomyeditor.php?target=".$tid);
	}
	
	private function getHierarchy($tid){
		$parentArr = Array($tid);
		$parCnt = 0;
		$targetTid = $tid;
		do{
			$sqlParents = "SELECT IFNULL(ts.parenttid,0) AS parenttid FROM taxstatus ts WHERE ts.tid = ".$targetTid;
			//echo "<div>".$sqlParents."</div>";
			$resultParent = $this->conn->query($sqlParents);
			if($rowParent = $resultParent->fetch_object()){
				$parentTid = $rowParent->parenttid;
				if($parentTid) {
					$parentArr[$parentTid] = $parentTid;
				}
			}
			else{
				break;
			}
			$resultParent->close();
			$parCnt++;
			if($targetTid == $parentTid) break;
			$targetTid = $parentTid;
		}while($targetTid && $parCnt < 16);
		
		return implode(",",array_reverse($parentArr));
	}
	
	public function echoUpperTaxa(){
		$sql = "SELECT DISTINCT ts.uppertaxonomy FROM taxstatus ts WHERE ts.taxauthid = 1 ORDER BY ts.uppertaxonomy";
		//echo $sql;
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			if($row->uppertaxonomy) echo "<option>".$row->uppertaxonomy."</option>\n";
		}
	}
}
?>