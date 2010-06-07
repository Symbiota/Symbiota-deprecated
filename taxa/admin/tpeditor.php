<?php
/*
 * Created on 26 Dec 2008
 * Author: E.E. Gilbert
 */

 //error_reporting(E_ALL);
 include_once("../../util/dbconnection.php");
 include_once("../../util/symbini.php");
 set_time_limit(120);
 ini_set("max_input_time",120);
 
 $taxonValue = array_key_exists("taxon",$_REQUEST)?$_REQUEST["taxon"]:"";
 $category = array_key_exists("category",$_REQUEST)?$_REQUEST["category"]:""; 
 $lang = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:"";
 $action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
 
 $tEditor = new TPEditor();
 $tEditor->setTaxon($taxonValue);
 $tEditor->setLanguage($lang);
 
 $editable = false;
 if(isset($userRights) && $userRights && $isAdmin){
 	$editable = true;
 }
 
 $status = "";
 if($editable){
	 if($action == "Edit Synonym Sort Order"){
	 	$synSortArr = Array();
		foreach($_REQUEST as $sortKey => $sortValue){
			if($sortValue && (substr($sortKey,0,4) == "syn-")){
				$synSortArr[substr($sortKey,4)] = $sortValue;
			}
		}
		$status = $tEditor->editSynonymSort($synSortArr);
	 }
 	 elseif($action == "Submit Common Name Edits"){
 		$editVernArr = Array();
	 	$editVernArr["vid"] = $_REQUEST["vid"];
 		if($_REQUEST["vernacularname"]) $editVernArr["vernacularname"] = str_replace("\"","-",$_REQUEST["vernacularname"]);
	 	if($_REQUEST["language"]) $editVernArr["language"] = $_REQUEST["language"];
	 	$editVernArr["notes"] = str_replace("\"","-",$_REQUEST["notes"]);
	 	$editVernArr["source"] = $_REQUEST["source"];
	 	if($_REQUEST["sortsequence"]) $editVernArr["sortsequence"] = $_REQUEST["sortsequence"];
	 	$editVernArr["username"] = $paramsArr["un"];
	 	$status = $tEditor->editVernacular($editVernArr);
	 }
	 elseif($action == "Add Common Name"){
	 	$addVernArr = Array();
	 	$addVernArr["vernacularname"] = str_replace("\"","-",$_REQUEST["vern"]);
	 	if($_REQUEST["language"]) $addVernArr["language"] = $_REQUEST["language"];
	 	if($_REQUEST["notes"]) $addVernArr["notes"] = str_replace("\"","-",$_REQUEST["notes"]);
	 	if($_REQUEST["source"]) $addVernArr["source"] = $_REQUEST["source"];
	 	if($_REQUEST["sortsequence"]) $addVernArr["sortsequence"] = $_REQUEST["sortsequence"];
	 	$addVernArr["username"] = $paramsArr["un"];
	 	$status = $tEditor->addVernacular($addVernArr);
	 }
	 elseif($action == "Delete Common Name"){
 		$delVern = $_REQUEST["delvern"];
	 	$status = $tEditor->deleteVernacular($delVern);
	 }
	 elseif($action == "Submit Description Edits"){
 		$editDescrArr = Array();
		$editDescrArr["tdid"] = $_REQUEST["tdid"];
		if($_REQUEST["heading"]) $editDescrArr["heading"] = $_REQUEST["heading"];
	 	$editDescrArr["displayheader"] = (array_key_exists("displayheader",$_REQUEST)?$_REQUEST["displayheader"]:0);
		if($_REQUEST["description"]) $editDescrArr["description"] = str_replace("\"","-",$_REQUEST["description"]);
	 	if($_REQUEST["language"]) $editDescrArr["language"] = $_REQUEST["language"];
	 	$editDescrArr["notes"] = $_REQUEST["notes"];
	 	$editDescrArr["source"] = $_REQUEST["source"];
	 	if($_REQUEST["sortsequence"]) $editDescrArr["sortsequence"] = $_REQUEST["sortsequence"];
	 	if($_REQUEST["displaylevel"]) $editDescrArr["displaylevel"] = $_REQUEST["displaylevel"];
	 	$editDescrArr["username"] = $paramsArr["un"];
	 	$status = $tEditor->editDescription($editDescrArr);
	 }
	 elseif($action == "Add Description"){
	 	$addDescrArr = Array();
	 	$addDescrArr["heading"] = $_REQUEST["heading"];
	 	$addDescrArr["displayheader"] = (array_key_exists("displayheader",$_REQUEST)?$_REQUEST["displayheader"]:0);
	 	$addDescrArr["description"] = str_replace("\"","-",$_REQUEST["description"]);
	 	if($_REQUEST["language"]) $addDescrArr["language"] = $_REQUEST["language"];
	 	if($_REQUEST["notes"]) $addDescrArr["notes"] = str_replace("\"","-",$_REQUEST["notes"]);
	 	if($_REQUEST["source"]) $addDescrArr["source"] = $_REQUEST["source"];
	 	if($_REQUEST["sortsequence"]) $addDescrArr["sortsequence"] = $_REQUEST["sortsequence"];
	 	if($_REQUEST["displaylevel"]) $addDescrArr["displaylevel"] = $_REQUEST["displaylevel"];
	 	$addDescrArr["username"] = $paramsArr["un"];
	 	$status = $tEditor->addDescription($addDescrArr);
	 }
	 elseif($action == "Delete Description"){
 		$delTdid = $_REQUEST["deltdid"];
 		$status = $tEditor->deleteDescription($delTdid);
	 }
	 elseif($action == "Submit Image Edits"){
	 	$imgEditArr = Array();
		$imgEditArr["imgid"] = $_REQUEST["imgid"];
	 	$imgEditArr["url"] = $_REQUEST["url"];
	 	$imgEditArr["thumbnailurl"] = $_REQUEST["thumbnailurl"];
	 	$imgEditArr["caption"] = $_REQUEST["caption"];
		$imgEditArr["photographer"] = $_REQUEST["photographer"];
		$imgEditArr["photographeruid"] = $_REQUEST["photographeruid"];
		$imgEditArr["owner"] = $_REQUEST["owner"];
		$imgEditArr["locality"] = str_replace("\"","-",$_REQUEST["locality"]);
		$imgEditArr["occid"] = $_REQUEST["occid"];
		$imgEditArr["notes"] = str_replace("\"","-",$_REQUEST["notes"]);
		$imgEditArr["sourceurl"] = $_REQUEST["sourceurl"];
		$imgEditArr["copyright"] = $_REQUEST["copyright"];
		$imgEditArr["anatomy"] = $_REQUEST["anatomy"];
		$imgEditArr["imagetype"] = $_REQUEST["imagetype"];
		$imgEditArr["sortsequence"] = $_REQUEST["sortsequence"];
		if(array_key_exists("addtoparent",$_REQUEST)) $imgEditArr["addtoparent"] = $_REQUEST["addtoparent"];
		$status = $status = $tEditor->editImage($imgEditArr);
	 }
	 elseif($action == "Transfer Image"){
	 	$tEditor->changeTaxon($_REQUEST["imgid"],$taxonValue,$_REQUEST["sourcetid"]);
	 }
	 elseif($action == "Submit Image Sort Edits"){
	 	$imgSortArr = Array();
		foreach($_REQUEST as $sortKey => $sortValue){
			if($sortValue && substr($sortKey,0,6) == "imgid-"){
				$imgSortArr[substr($sortKey,6)]  = $sortValue;
			}
		}
	 	$status = $tEditor->editImageSort($imgSortArr);
	 } 
	 elseif($action == "Upload Image"){
	 	$imgPath = trim($_REQUEST["filepath"]);
	 	if(!$imgPath){
		 	$userFile = basename($_FILES['userfile']['name']);
			$tEditor->setFileName($userFile);
		 	$downloadPath = $tEditor->getDownloadPath($_REQUEST["imagetype"]);
	 	}
		if($imgPath || move_uploaded_file($_FILES['userfile']['tmp_name'], $downloadPath)) {
			$imgData = Array();
			if($imgPath) $imgData["url"] = $imgPath;
			$imgData["caption"] = trim($_REQUEST["caption"]);
			$imgData["photographer"] = trim($_REQUEST["photographer"]);
			$imgData["photographeruid"] = $_REQUEST["photographeruid"];
			$imgData["sourceurl"] = $_REQUEST["sourceurl"];
			$imgData["copyright"] = $_REQUEST["copyright"];
			$imgData["owner"] = trim($_REQUEST["owner"]);
			$imgData["locality"] = str_replace("\"","-",trim($_REQUEST["locality"]));
			$imgData["occid"] = trim($_REQUEST["occid"]);
			$imgData["notes"] = str_replace("\"","-",trim($_REQUEST["notes"]));
			$imgData["anatomy"] = trim($_REQUEST["anatomy"]);
			$imgData["imagetype"] = $_REQUEST["imagetype"];
			$imgData["sortsequence"] = trim($_REQUEST["sortsequence"]);
			if(array_key_exists("addtoparent",$_REQUEST)) $imgData["addtoparent"] = $_REQUEST["addtoparent"];
			$imgData["username"] = $paramsArr["un"];
			$status = $tEditor->loadImageData($imgData);
		} else {
			$status = "<h1>Problem loading image</h1>\n";
			$status .= "<div style='text-weight:bold;'>Remeber that image size can not be greater than 250KB</div>";
			//echo "Path: ".$uploadPath;
			//echo 'Debugging info:';
			//print_r($_FILES);
		}
	 }
	 elseif($action == "Delete Image"){
		$imgDel = $_REQUEST["imgdel"];
		$removeImg = (array_key_exists("removeimg",$_REQUEST)?$_REQUEST["removeimg"]:0);
		$status = $tEditor->deleteImage($imgDel, $removeImg);
	 }
 }
 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<title><?php echo $defaultTitle." Taxon Editor: ".$tEditor->getSciName(); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
	<link rel="stylesheet" href="../../css/speciesprofile.css" type="text/css"/>
    <link rel="stylesheet" href="../../css/jqac.css" type="text/css" />
	<script type="text/javascript" src="../../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../../js/jquery.autocomplete-1.4.2.js"></script>
	<script type="text/javascript">
		var cseXmlHttp;
		var imageArr = new Array();
		var imgCnt = 0;
		var targetImg = "";
	
		function toggle(target){
			var spanObjs = document.getElementsByTagName("span");
			for (i = 0; i < spanObjs.length; i++) {
				var obj = spanObjs[i];
				if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
					if(obj.style.display=="none"){
						obj.style.display="inline";
					}
					else {
						obj.style.display="none";
					}
				}
			}
	
			var divObjs = document.getElementsByTagName("div");
			for (var i = 0; i < divObjs.length; i++) {
				var obj = divObjs[i];
				if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
					if(obj.style.display=="none"){
						obj.style.display="block";
					}
					else {
						obj.style.display="none";
					}
				}
			}
		}
		
		function expandImages(){
			var divCnt = 0;
			var divObjs = document.getElementsByTagName("div");
			for (i = 0; i < divObjs.length; i++) {
				var obj = divObjs[i];
				if(obj.getAttribute("class") == "extraimg" || obj.getAttribute("className") == "extraimg"){
					if(obj.style.display=="none"){
						obj.style.display="inline";
						divCnt++;
						if(divCnt >= 5) break;
					}
				}
			}
		}
		
	    function submitAddForm(f){
	        var errorText = "";
	
	        if(f.elements["userfile"].value.replace(/\s/g, "") == "" ){
	            if(f.elements["filepath"].value.replace(/\s/g, "") == ""){
	                errorText += "\nFile path must be entered";
	            }
	        }
	        if(errorText != ""){
	            window.alert("Errors:\n " + errorText);
	            return false;
	        }
	        return true;
	    }

	    function submitEditForm(f){
	        var errorText = "";
	
	        if(f.elements["url"].value.replace(/\s/g, "") == "" ){
	            errorText += "\nFile path must be entered";
	        }
	        if(errorText != ""){
	            window.alert("Errors:\n " + errorText);
	            return false;
	        }
	        return true;
	    }

	    function submitChangeTaxonForm(f){
			var sciName = f.elements["targettaxon"].value.replace(/^\s+|\s+$/g, ""); 
	        if(sciName == ""){
	            window.alert("Error: Enter a taxon name to which the image will be transferred");
	        }
			else{
				checkScinameExistance(sciName);
			}
            return false;	//Submit takes place in the checkScinameExistance method
	    }

		function initChangeTaxonList(input,tImg){
			targetImg = tImg;
			$(input).autocomplete({ ajax_get:getChangeTaxonList, minchars:3 });
		}

		function getChangeTaxonList(key,cont){ 
		   	var script_name = 'rpc/getchangetaxonlist.php';
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
				renameTid = cseXmlHttp.responseText;
				if(renameTid == ""){
					alert("ERROR: Scientific name does not exist in database. Did you spell it correctly? It may have to be added to database.");
				}
				else{
					document.getElementById("targettid-"+targetImg).value = renameTid;
					document.forms["changetaxonform-"+targetImg].submit();
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

		function openOccurrenceSearch(target) {
			occWindow=open("occurrencesearch.php?targetid="+target,"occsearch","resizable=1,scrollbars=1,width=530,height=500,left=20,top=20");
			if (occWindow.opener == null) occWindow.opener = self;
		}
		
	</script>
</head>
<body>
<?php
$displayLeftMenu = (isset($taxa_admin_tpeditorMenu)?$taxa_admin_tpeditorMenu:"true");
include($serverRoot."/util/header.php");
if(isset($taxa_admin_tpeditorCrumbs)){
	echo "<div class='navpath'>";
	echo "<a href='../index.php'>Home</a> &gt; ";
	echo $taxa_admin_tpeditorCrumbs;
	echo " <b>Taxon Profile Editor</b>"; 
	echo "</div>";
}

 if($editable){
	?>
 	<table id='innertable'>
 		<tr><td>
			<div style='float:right;'>
				<ul style="margin-top:15px;width:200px;border:dotted 1px;">
					<li><a href="tpeditor.php?taxon=<?php echo $tEditor->getTid(); ?>&category=images">Edit Images</a></li>
					<ul>
						<li><a href="tpeditor.php?taxon=<?php echo $tEditor->getTid(); ?>&category=imagequicksort">Edit Image Sorting Order</a></li>
						<li><a href="tpeditor.php?taxon=<?php echo $tEditor->getTid(); ?>&category=imageadd">Add a New Image</a></li>
					</ul>
					<li><a href="tpeditor.php?taxon=<?php echo $tEditor->getTid(); ?>&category=common">Synonyms / Common Names</a></li>
					<li><a href="tpeditor.php?taxon=<?php echo $tEditor->getTid(); ?>&category=textdescr">Text Descriptions</a></li>
		<!-- 
					<li><a href="taxonomydisplay.php?target=<?php echo $tEditor->getFamily(); ?>">View Taxonomic Tree</a></li>
					<ul>
						<li><a href="taxonomyeditor.php?target=<?php echo $tEditor->getTid(); ?>">Edit Taxonomic Placement</a></li>
						<li><a href="taxonomyloader.php">Add New Taxonomic Name</a></li>
					</ul>
		-->
				</ul>
			</div>
		<?php 

 	//If submitted tid does not equal accepted tid, state that user will be redirected to accepted
 	if($tEditor->getSubmittedTid()){
 		echo "<div style='font-size:16px;margin-top:5px;margin-left:10px;font-weight:bold;'>Redirected from: <i>".$tEditor->getSubmittedSciName()."</i></div>"; 
 	}
	//Display Scientific Name and Family
	echo "<div style='font-size:16px;margin-top:15px;margin-left:10px;'><a href='../index.php?taxon=".$tEditor->getTid()."' style='color:#990000;text-decoration:none;'><b><i>".$tEditor->getSciName()."</i></b></a> ".$tEditor->getAuthor();
	//Display Parent link
	if($tEditor->getRankId() > 140) echo "&nbsp;<a href='tpeditor.php?taxon=".$tEditor->getParentTid()."'><img border='0' height='10px' src='../../images/toparent.jpg' title='Go to Parent' /></a>";
	echo "</div>\n";
	//Display Family
	echo "<div id='family' style='margin-left:20px;margin-top:0.25em;'><b>Family:</b> ".$tEditor->getFamily()."</div>\n";
	
	//Display children taxa
/*	$childrenArr = $tEditor->getChildrenTaxa();
	if($childrenArr){
		echo "<div style='width:300px;margin:5px 0px 5px 25px;font-weight:bold;border:1px dotted olive;padding:3px;'>Children Taxa:\n ";
		foreach($childrenArr as $tid => $childArr){
			echo "<div style='margin-left:10px;'><a href='tpeditor.php?taxon=".$tid."'><b><i>".$childArr["sciname"]."</i></b></a> ".$childArr["author"]."</div>\n";
		}
		echo "</div>\n";
	}
*/	
	if($status){
		echo "<h3 style='color:red;'>Error: $status<h3>";
	}

	if($category == "common"){
		//Display Synonyms
		$synonymArr = $tEditor->getSynonym();
		if($synonymArr){
			$synStr = "";
			foreach($synonymArr as $tidKey => $valueArr){
				 $synStr .= ", ".$valueArr["sciname"];
			}
			echo "<div style='margin:10px 0px 10px 0px;width:450px;'><b>Synonyms:</b> ".substr($synStr,2)."&nbsp;&nbsp;&nbsp;";
			echo "<span onclick='javascript:toggle(\"synsort\");' title='Edit Synonym Sort Order'><img style='border:0px;width:12px;' src='../../images/edit.png'/></span>";
			echo "</div>\n";
			echo "<div class='synsort' style='display:none;'>";
			echo "<form action='".$_SERVER["PHP_SELF"]."' method='post'>\n";
			echo "<input type='hidden' name='taxon' value='".$tEditor->getTid()."' />";
			echo "<fieldset style='margin:5px 0px 5px 5px;margin-left:20px;width:350px;'>";
	    	echo "<legend>Synonym Sort Order</legend>";
			foreach($synonymArr as $tidKey => $valueArr){
				echo "<div><b>".$valueArr["sortsequence"]."</b> - ".$valueArr["sciname"]."</div>\n";
				echo "<div style='margin:0px 0px 5px 10px;'>new sort value: <input type='text' name='syn-".$tidKey."' style='width:35px;border:inset;' /></div>\n";
			}
			echo "<div><input type='submit' name='action' value='Edit Synonym Sort Order' /></div>\n";
			echo"</fieldset></form></div>\n";
		}
	
		//Display Common Names (vernaculars)
		$vernList = $tEditor->getVernaculars();
		echo "<div>";
		echo "<div><b>Common Names</b>&nbsp;&nbsp;&nbsp;<span onclick='javascript:toggle(\"addvern\");' title='Add a New Image'><img style='border:0px;width:15px;' src='../../images/add.png'/></span></div>\n";
		//Add new image section
		echo "<div id='addvern' class='addvern' style='display:none;'>";
		echo "<form id='addvernform' name='addvernform'>";
		echo "<fieldset style='width:250px;margin:5px 0px 0px 20px;'>";
	    echo "<legend>New Common Name</legend>";
		echo "<div style=''>Common Name: <input id='vern' name='vern' style='margin-top:5px;border:inset;' type='text' /></div>\n";
	    echo "<div style=''>Language: <input id='language' name='language' style='margin-top:5px;border:inset;' type='text' /></div>\n";
		echo "<div style=''>Notes: <input id='notes' name='notes' style='margin-top:5px;border:inset;' type='text' /></div>\n";
		echo "<div style=''>Source: <input id='source' name='source' style='margin-top:5px;border:inset;' type='text' /></div>\n";
		echo "<div style=''>Sort Sequence: <input id='sortsequence' name='sortsequence' style='margin-top:5px;border:inset;width:40px;' type='text' /></div>\n";
		echo "<input type='hidden' name='taxon' value='".$tEditor->getTid()."' />";
		echo "<div><input id='vernsadd' name='action' style='margin-top:5px;' type='submit' value='Add Common Name' /></div>\n";
		echo "</fieldset>";
		echo "</form>\n";
		echo "</div>";
		if($vernList){
			foreach($vernList as $lang => $vernsList){
				echo "<div style='width:250px;margin:5px 0px 0px 15px;'><fieldset>";
		    	echo "<legend>".$lang."</legend>";
				foreach($vernsList as $vernArr){
					echo "<div style='margin-left:10px;'><b>".$vernArr["vernacularname"]."</b>&nbsp;&nbsp;&nbsp;\n";
					echo "<span onclick='javascript:toggle(\"vid-".$vernArr["vid"]."\");' title='Edit Common Name'><img style='border:0px;width:12px;' src='../../images/edit.png'/></span>&nbsp;&nbsp;&nbsp;\n";
					echo "</div>\n";
					echo "<div class='vid-".$vernArr["vid"]."' style='display:none;'><div style='background-color:yellow;'>\n";
					echo "<form id='delvern' name='delvern' action='".$_SERVER["PHP_SELF"]."' method='post' onsubmit=\"javascript: return window.confirm('Are you sure you want to delete this Common Name?');\">\n";
					echo "<input type='hidden' name='delvern' value='".$vernArr["vid"]."'>\n";
					echo "<input type='hidden' name='taxon' value='".$tEditor->getTid()."' />";
					echo "<input id='vernsubmitimg' name='action' type='image' value='Delete Common Name' style='margin:10px 0px 0px 20px;height:12px;' src='../../images/del.gif'/> Delete Common Name ";
					echo "</form></div></div>\n";
					echo "<form id='updatevern' name='updatevern' style='margin-left:20px;'>";
					echo "<div class='vid-".$vernArr["vid"]."' style='display:none;'><input id='vernacularname' name='vernacularname' style='margin:2px 0px 5px 15px;border:inset;' type='text' value='".$vernArr["vernacularname"]."' /></div>\n";
					echo "<div style='display:none;'>Language: ".$vernArr["language"]."</div>";
					echo "<div class='vid-".$vernArr["vid"]."' style='display:none;'><input id='language' name='language' style='margin:2px 0px 5px 15px;border:inset;' type='text' value='".$vernArr["language"]."' /></div>\n";
					echo "<div style=''>Notes: ".$vernArr["notes"]."</div>";
					echo "<div class='vid-".$vernArr["vid"]."' style='display:none;'><input id='notes' name='notes' style='margin:2px 0px 5px 15px;border:inset;' type='text' value='".$vernArr["notes"]."' /></div>\n";
					echo "<div style=''>Source: ".$vernArr["source"]."</div>";
					echo "<div class='vid-".$vernArr["vid"]."' style='display:none;'><input id='source' name='source' style='margin:2px 0px 5px 15px;border:inset;' type='text' value='".$vernArr["source"]."' /></div>\n";
					echo "<div style=''>Sort Sequence: ".$vernArr["sortsequence"]."</div>";
					echo "<div class='vid-".$vernArr["vid"]."' style='display:none;'><input id='sortsequence' name='sortsequence' style='margin:2px 0px 5px 15px;border:inset;width:40px;' type='text' value='".$vernArr["sortsequence"]."' /></div>\n";
					echo "<input type='hidden' name='vid' value='".$vernArr["vid"]."'>\n";
					echo "<input type='hidden' name='taxon' value='".$tEditor->getTid()."' />";
					echo "<div class='vid-".$vernArr["vid"]."' style='display:none;'><input id='vernssubmit' name='action' style='margin:2px 0px 20px 15px;' type='submit' value='Submit Common Name Edits' /></div>\n";
					echo "</form>\n";
					
				}
				echo "</fieldset></div>";
			}
			echo "</div>";
		}
	}
	elseif($category == "textdescr"){
		//Display Description info
		$descList = $tEditor->getDescriptions();
		echo "<div><b>Descriptions</b>&nbsp;&nbsp;&nbsp;<span onclick='javascript:toggle(\"adddescr\");' title='Add a New Description'><img style='border:0px;width:15px;' src='../../images/add.png'/></span></div>\n";
		//Add new Description section
		echo "<div id='adddescr' class='adddescr' style='display:none;'>";
		echo "<form id='adddescrform' name='adddescrform'>";
		echo "<fieldset style='width:535px;margin:5px 0px 0px 15px;'>";
	    echo "<legend>New Description</legend>";
		echo "<div style=''>Heading: <input id='heading' name='heading' style='margin-top:5px;border:inset;' type='text' />&nbsp;&nbsp;&nbsp;&nbsp;";
		echo "<input id='displayheader' name='displayheader' type='checkbox' value='1' CHECKED /> Display Header</div>\n";
		echo "<div style=''><textarea id='description' name='description' cols='63' rows='3' style='border:inset;'></textarea></div>\n";
		echo "<div style=''>Language: <input id='language' name='language' style='margin-top:5px;border:inset;' type='text' /></div>\n";
		echo "<div style=''>Notes: <input id='notes' name='notes' style='margin-top:5px;border:inset;' type='text' /></div>\n";
		echo "<div style=''>Source: <input id='source' name='source' style='margin-top:5px;border:inset;' type='text' /></div>\n";
		echo "<div style=''>Display Level: <input id='displaylevel' name='displaylevel' style='margin-top:5px;border:inset;width:40px;' type='text' /></div>\n";
		echo "<div style=''>Sort Sequence: <input id='sortsequence' name='sortsequence' style='margin-top:5px;border:inset;width:40px;' type='text' /></div>\n";
		echo "<input type='hidden' name='taxon' value='".$tEditor->getTid()."' />";
		echo "<input type='hidden' name='category' value='".$category."'>\n";
		echo "<div><input id='submitdesrcadd' name='action' style='margin-top:5px;' type='submit' value='Add Description' /></div>\n";
		echo "</fieldset>";
		echo "</form>\n";
		echo "</div>";
		if($descList){
			foreach($descList as $lang => $levelList){
				echo "<div style='width:550px;margin:5px 0px 0px 15px;'><fieldset>";
		    	echo "<legend>".$lang."</legend>";
				foreach($levelList as $displayLevel => $headingList){
					echo "<div style='width:500px;margin:5px 0px 0px 15px;'><fieldset>";
		    		echo "<legend>Display Level ".$displayLevel."</legend>";
					foreach($headingList as $heading => $descrArr){
						echo "<div style='margin-left:10px;'><b>".$heading."</b>&nbsp;&nbsp;&nbsp;".($descrArr["displayheader"]?"(header displayed)":"(heading hidden)")."&nbsp;&nbsp;&nbsp;\n";
						echo "<span onclick='javascript:toggle(\"descr-".$descrArr["tdid"]."\");' title='Edit Image Data'><img style='border:0px;width:12px;' src='../../images/edit.png'/></span>\n";
						//Delete Description
						echo "<div>".$descrArr["description"]."</div>\n";
						echo "<div class='descr-".$descrArr["tdid"]."' style='display:none;'>";
						//Display and Edit Description
						echo "<div style='margin:5px 0px 5px 20px;border:2px solid cyan;padding:5px;'>";
						echo "<form id='updatedescr' name='updatedescr' action='".$_SERVER["PHP_SELF"]."'>";
						echo "<div>Heading: <input id='heading' name='heading' style='margin-top:5px;border:inset;' type='text' value='".$heading."' />&nbsp;&nbsp;&nbsp;";
						echo "<input id='displayheader' name='displayheader' type='checkbox' value='1' ".($descrArr["displayheader"]?"CHECKED":"")." /> Display Header</div>\n";
						echo "<div><textarea id='description' name='description' cols='50' rows='3' style='border:inset;'>".$descrArr["description"]."</textarea></div>\n";
						echo "<div>Language: <input id='language' name='language' style='margin-top:5px;border:inset;' type='text' value='".$lang."' /></div>\n";
						echo "<div>Notes: <input id='notes' name='notes' style='margin-top:5px;border:inset;width:400px;' type='text' value='".$descrArr["notes"]."' /></div>\n";
						echo "<div>Source: <input id='source' name='source' style='margin-top:5px;border:inset;width:330px;' type='text' value='".$descrArr["source"]."' /></div>\n";
						echo "<div>Display Level: <input id='displaylevel' name='displaylevel' style='margin-top:5px;border:inset;width:40px;' type='text' value='".$displayLevel."' /></div>\n";
						echo "<div>Sort Sequence: <input id='sortsequence' name='sortsequence' style='margin-top:5px;border:inset;width:40px;' type='text' value='".$descrArr["sortsequence"]."' />&nbsp;&nbsp;\n";
						echo "<input type='hidden' name='tdid' value='".$descrArr["tdid"]."'>\n";
						echo "<input type='hidden' name='taxon' value='".$tEditor->getTid()."' />";
						echo "<input type='hidden' name='category' value='".$category."'>\n";
						echo "<input id='descrsubmit' name='action' type='submit' value='Submit Description Edits' /></div>\n";
						echo "</form></div>\n";
						//Delete Description
						echo "<div style='margin:5px 0px 5px 20px;border:2px solid red;padding:2px;'>\n";
						echo "<form id='deldescr' name='deldescr' action='".$_SERVER["PHP_SELF"]."' method='post' onsubmit=\"javascript: return window.confirm('Are you sure you want to delete this Description?');\">\n";
						echo "<input type='hidden' name='deltdid' value='".$descrArr["tdid"]."'>\n";
						echo "<input type='hidden' name='taxon' value='".$tEditor->getTid()."' />";
						echo "<input type='hidden' name='category' value='".$category."'>\n";
						echo "<input id='descrsubmitimage' name='action' value='Delete Description' style='margin:10px 0px 0px 20px;height:12px;' type='image' src='../../images/del.gif'/> Delete Description ";
						echo "</form></div>\n";
						echo "</div></div>";
					}
					echo "</fieldset></div>";
				}
				echo "</fieldset></div>";
			}
		}
	}
	elseif($category == "imagequicksort"){
		$images = $tEditor->getImages();
		echo "<div style='clear:both;'><form action='".$_SERVER["PHP_SELF"]."' method='post' target='_self'>\n";
		echo "<table border='0' cellspacing='0'>";
		echo "<tr>";
		$imgCnt = 0;
		foreach($images as $imgArr){
			echo "<td align='center' valign='bottom'>";
			echo "<div style='margin:20px 0px 0px 0px;'>";
			echo "<a href='".(array_key_exists("imageDomain",$GLOBALS)&&substr($imgArr["url"],0,1)=="/"?$GLOBALS["imageDomain"]:"").$imgArr["url"]."'>";
			echo "<img width='150' src='".(array_key_exists("imageDomain",$GLOBALS)&&substr($imgArr["url"],0,1)=="/"?$GLOBALS["imageDomain"]:"").$imgArr["url"]."'/></a></div>";
			echo "<div style='margin-top:2px;'>Sort sequence: <b>".$imgArr["sortsequence"]."</b></div>";
			echo "<div>New Value: <input name='imgid-".$imgArr["imgid"]."' type='text' value='' size='5' maxlength='5'></div>\n";
			echo "</td>\n";
			$imgCnt++;
			if($imgCnt%5 == 0){
				echo "</tr><tr><td colspan='5'><hr><div style='margin-top:2px;'><input type='submit' name='action' id='submit' value='Submit Image Sort Edits'/></div></td></tr>\n<tr>";
			}
		}
		for($i = (5 - $imgCnt%5);$i > 0; $i--){
			echo "<td>&nbsp;</td>";
		}
		echo "</tr>\n";
		echo "</table>\n";
		echo "<input name='taxon' type='hidden' value='".$tEditor->getTid()."'>\n";
		echo "<input name='category' type='hidden' value='".$category."'>\n";
		if($imgCnt%5 != 0) echo "<div style='margin-top:2px;'><input type='submit' name='action' id='imgsortsubmit' value='Submit Image Sort Edits'/></div>\n";
		echo "</form></div>\n";
	}
	elseif($category == "imageadd"){
		?>
		<form enctype='multipart/form-data' action='tpeditor.php' id='imageaddform' method='post' target='_self' onsubmit='return submitAddForm(this);'>
			<fieldset style='margin:5px;width:485px;'>
		    	<legend>Add a New Image</legend>
		
		    	<!-- following line sets MAX_FILE_SIZE (must precede the file input field)  -->
				<div style='padding:10px;width:475px;border:1px solid yellow;background-color:FFFF99;'>
					<div style="font-weight:bold;font-size:110%;margin-bottom:7px;">
						Select an image file located on your computer that you want to upload 
						OR enter a URL to an image already located on a web server (don't do both)
					</div>
					<input type='hidden' name='MAX_FILE_SIZE' value='250000' />
					<div>
						<b>Upload File:</b> <input name='userfile' type='file' size='50'/>
					</div>
					<div>Note: upload image size can not be greater than 250KB</div>
					<div style='margin-top:7px;'>
						<b>URL:</b> <input type='text' name='filepath' size='50'/>
					</div>
					<div style='margin-left:10px;'>
						* url can be relative or absolute
					</div>
				</div>
				
				<!-- Image metadata -->
		    	<div style='margin-top:2px;'>
		    		<b>Caption:</b> 
					<input name='caption' type='text' value='' size='25' maxlength='100'>
				</div>
				<div style='margin-top:2px;'>
					<b>Photographer:</b> 
					<select name='photographeruid' name='photographeruid'>
						<option value="">Select Photographer</option>
						<option value="">---------------------------------------</option>
						<?php $tEditor->echoPhotographerSelect($paramsArr["uid"]); ?>
					</select>
				</div>
				<div style='margin-top:2px;'>
					<b>Photographer Override:</b> 
					<input name='photographer' type='text' value='' size='37' maxlength='100'> 
					* Only enter a value to override value entered in above Select Box
				</div>
				<div style='margin-top:2px;'>
					<b>Manager:</b> 
					<input name='owner' type='text' value='' size='35' maxlength='100'>
				</div>
				<div style='margin-top:2px;'>
					<b>Source URL:</b> 
					<input name='sourceurl' type='text' value='' size='70' maxlength='250'>
				</div>
				<div style='margin-top:2px;'>
					<b>Copyright:</b> 
					<input name='copyright' type='text' value='' size='70' maxlength='250'>
				</div>
				<div style='margin-top:2px;'>
					<b>Locality:</b> 
					<input name='locality' type='text' value='' size='70' maxlength='250'>
				</div>
				<div style='margin-top:2px;'>
					<b>Occurrence Record #:</b> 
					<input id="occidadd" name="occid" type="text" value="" READONLY/>
					<span style="cursor:pointer;color:blue;"  onclick="openOccurrenceSearch('occidadd')">Link to Occurrence Record</span>
				</div>
				<div style='margin-top:2px;'>
					<b>Notes:</b> 
					<input name='notes' type='text' value='' size='70' maxlength='250'>
				</div>
				<div style='margin-top:2px;'>
					<b>Anatomy:</b> 
					<input name='anatomy' type='text' value='' size='25' maxlength='100'>
				</div>
				<div style='margin-top:2px;'>
					<b>Image Type:</b> 
					<select name='imagetype'>
						<option value='photos'>field photo</option>
						<option value='specimen'>specimen image</option>
					</select>
				</div>
				<div style='margin-top:2px;'>
					<b>Sort sequence:</b> 
					<input name='sortsequence' type='text' value='' size='5' maxlength='5'>
				</div>
				<?php if($tEditor->getRankId() > 220 && !$tEditor->getSubmittedTid()){ ?>
				<div style='padding:10px;margin:5px;width:475px;border:1px solid yellow;background-color:FFFF99;'>
					<input type='checkbox' name='addtoparent' value='1' /> 
					Add to Parent Taxon 
					<div style='margin-left:10px;'>
						* If scientific name is a subspecies or variety, click this option if you also want image to be displays at the species level
					</div>
				</div>
				<?php } ?>
				<input name="taxon" type="hidden" value="<?php echo $tEditor->getTid();?>">
				<input name='category' type='hidden' value='images'>
				<div style='margin-top:2px;'>
					<input type='submit' name='action' id='imgaddsubmit' value='Upload Image'/>
				</div>
			</fieldset>
		</form>
		<?php 
	}
	elseif($category == "maps"){
		$maps = $tEditor->getMaps();
		foreach($maps as $imgArr){
		?>
		<?php 
		}
	}
	else{
		//catagory == images or is null
		$images = $tEditor->getImages();
		foreach($images as $imgArr){
			?>
			<table>
				<tr><td>
					<div style="margin:20px;float:left;">
						<a href="<?php echo (array_key_exists("imageDomain",$GLOBALS)&&substr($imgArr["url"],0,1)=="/"?$GLOBALS["imageDomain"]:"").$imgArr["url"];?>">
							<img width="250" src="<?php echo (array_key_exists("imageDomain",$GLOBALS)&&substr($imgArr["url"],0,1)=="/"?$GLOBALS["imageDomain"]:"").$imgArr["url"];?>"/>
						</a>
					</div>
				</td>
				<td valign="middle">
					<div style='float:right;margin-right:10px;cursor:pointer;'>
						<img src="../../images/edit.png" onclick="toggle('image<?php echo $imgArr["imgid"];?>');">
					</div>
					<div style='margin:60px 0px 10px 10px;clear:both;'>
						<?php if($imgArr["caption"]){ ?>
						<div>
							<b>Caption:</b> 
							<?php echo $imgArr["caption"];?>
						</div>
						<?php 
						}
						?>
						<div>
							<b>Photographer:</b> 
							<?php echo $imgArr["photographerdisplay"];?>
						</div>
						<?php 
						if($imgArr["owner"]){
						?>
						<div>
							<b>Manager:</b> 
							<?php echo $imgArr["owner"];?>
						</div>
						<?php
						} 
						if($imgArr["sourceurl"]){
						?>
						<div>
							<b>Source URL:</b> 
							<?php echo $imgArr["sourceurl"];?>
						</div>
						<?php
						} 
						if($imgArr["copyright"]){
						?>
						<div>
							<b>Copyright:</b> 
							<?php echo $imgArr["copyright"];?>
						</div>
						<?php
						} 
						if($imgArr["locality"]){
						?>
						<div>
							<b>Locality:</b> 
							<?php echo $imgArr["locality"];?>
						</div>
						<?php
						} 
						if($imgArr["occid"]){
						?>
						<div>
							<b>Occurrence Record #:</b> 
							<a href="<?php echo $clientRoot;?>/collections/individual/individual.php?occid=<?php echo $imgArr["occid"]; ?>"><?php echo $imgArr["occid"];?></a>
						</div>
						<?php
						} 
						if($imgArr["anatomy"]){
						?>
						<div>
							<b>Anatomy:</b> 
							<?php echo $imgArr["anatomy"];?>
						</div>
						<?php
						} 
						if($imgArr["imagetype"]){
						?>
						<div>
							<b>Image Type:</b> 
							<?php echo $imgArr["imagetype"];?>
						</div>
						<?php
						} 
						if($imgArr["notes"]){
						?>
						<div>
							<b>Notes:</b> 
							<?php echo $imgArr["notes"];?>
						</div>
						<?php
						} 
						?>
						<div>
							<b>Sort sequence:</b> 
							<?php echo $imgArr["sortsequence"];?>
						</div>
					</div>
				
				</td></tr>
				<tr><td colspan='2'>
					<div class='image<?php  echo $imgArr["imgid"];?>' style='display:none;'>
						<form action='tpeditor.php' method='post' target='_self' onsubmit='return submitEditForm(this);'>
							<fieldset style='margin:5px 0px 5px 5px;'>
						    	<legend>Edit Image Details</legend>
						    	<div style='margin-top:2px;'>
						    		<b>Caption:</b>
									<input name='caption' type='text' value='<?php echo $imgArr["caption"];?>' size='25' maxlength='100'>
								</div>
								<div style='margin-top:2px;'>
									<b>Photographer User ID:</b> 
									<select name='photographeruid' name='photographeruid'>
										<option value="">Select Photographer</option>
										<option value="">---------------------------------------</option>
										<?php $tEditor->echoPhotographerSelect($imgArr["photographeruid"]); ?>
									</select>
									* Users registered within system
								</div>
								<div style='margin-top:2px;'>
									<b>Photographer (override):</b> 
									<input name='photographer' type='text' value='<?php echo $imgArr["photographer"];?>' size='37' maxlength='100'>
									* Only enter a value to override value entered in above Select Box
								</div>
								<div style='margin-top:2px;'>
									<b>Manager:</b> 
									<input name='owner' type='text' value='<?php echo $imgArr["owner"];?>' size='35' maxlength='100'>
								</div>
								<div style='margin-top:2px;'>
									<b>Source URL:</b> 
									<input name='sourceurl' type='text' value='<?php echo $imgArr["sourceurl"];?>' size='70' maxlength='250'>
								</div>
								<div style='margin-top:2px;'>
									<b>Copyright:</b> 
									<input name='copyright' type='text' value='<?php echo $imgArr["copyright"];?>' size='70' maxlength='250'>
								</div>
								<div style='margin-top:2px;'>
									<b>Locality:</b> 
									<input name='locality' type='text' value='<?php echo $imgArr["locality"];?>' size='70' maxlength='250'>
								</div>
								<div style='margin-top:2px;'>
									<b>Occurrence Record #:</b> 
									<input id="occidedit" name="occid" type="text" value="" READONLY/>
									<span style="cursor:pointer;color:blue;"  onclick="openOccurrenceSearch('occidedit')">Link to Occurrence Record</span>
								</div>
								<div style='margin-top:2px;'>
									<b>Anatomy:</b> 
									<input name='anatomy' type='text' value='<?php echo $imgArr["anatomy"];?>' size='25' maxlength='100'>
								</div>
								<div style='margin-top:2px;'>
									<b>Image Type:</b> 
									<select name='imagetype'>
										<option value='photos' <?php echo ($imgArr["imagetype"]=="field image"?"SELECTED":"");?>>
											field photo
										</option>
										<option value='specimen' <?php echo ($imgArr["imagetype"]=="herbarium specimen image"?"SELECTED":"")?>>
											specimen image
										</option>
									</select>
								</div>
								<div style='margin-top:2px;'>
									<b>Notes:</b> 
									<input name='notes' type='text' value='<?php echo $imgArr["notes"];?>' size='70' maxlength='250' />
								</div>
								<div style='margin-top:2px;'>
									<b>Sort sequence:</b> 
									<input name='sortsequence' type='text' value='<?php echo $imgArr["sortsequence"];?>' size='5' maxlength='5' />
								</div>
								<div style='margin-top:2px;'>
									<b>URL:</b> 
									<input name='url' type='text' value='<?php echo $imgArr["url"];?>' size='85' maxlength='100' />
								</div>
								<div style='margin-top:2px;'>
									<b>Thumbnail URL:</b> 
									<input name='thumbnailurl' type='text' value='<?php echo $imgArr["thumbnailurl"];?>' size='85' maxlength='100'>
								</div>
								<?php if($tEditor->getRankId() > 220 && !$tEditor->getSubmittedTid() && !$tEditor->imageExists($imgArr["url"],$tEditor->getParentTid())){ ?>
								<div style='padding:10px;margin:5px;width:475px;border:1px solid yellow;background-color:FFFF99;'>
									<input type='checkbox' name='addtoparent' value='1' /> 
									Add to Parent Taxon 
									<div style='margin-left:10px;'>
										* If scientific name is a subspecies or variety, click this option if you also want image to be displays at the species level
									</div>
								</div>
								<?php }?>
				
								<input name="taxon" type="hidden" value="<?php echo $tEditor->getTid();?>" />
								<input name="category" type="hidden" value="<?php echo $category; ?>" />
								<input name="imgid" type="hidden" value="<?php echo $imgArr["imgid"]; ?>" />
								<div style='margin-top:2px;'>
									<input type='submit' name='action' id='editsubmit' value='Submit Image Edits' />
								</div>
							</fieldset>
						</form>
						<form id="changetaxonform-<?php echo $imgArr["imgid"]; ?>" action='tpeditor.php' method='post' target='_self' onsubmit='return submitChangeTaxonForm(this);'>
							<fieldset style='margin:5px 0px 5px 5px;'>
						    	<legend>Transfer Image to a Different Scientific Name</legend>
								<div style="font-weight:bold;">
									Transfer to Taxon: 
									<input type="text" id="targettaxon" name="targettaxon" size="40" onfocus="initChangeTaxonList(this,<?php echo $imgArr["imgid"]; ?>)" autocomplete="off" />
									<input type="hidden" id="targettid-<?php echo $imgArr["imgid"]; ?>" name="taxon" value="" />
	
									<input name="sourcetid" type="hidden" value="<?php echo $tEditor->getTid();?>" />
									<input name="imgid" type="hidden" value="<?php echo $imgArr["imgid"]; ?>" />
									<input name="category" type="hidden" value="<?php echo $category; ?>" />
									<input name="action" type="hidden" value="Transfer Image" />
									<input name="action2" type="submit" id="changetaxonsubmit" value="Transfer Image" />
								</div>
						    </fieldset>
						</form>
						
						<?php 
						if($paramsArr["un"] == $imgArr["username"] || $isAdmin){
							?>
							<form action="tpeditor.php" method="post" target="_self" onsubmit="return window.confirm('Are you sure you want to delete this image? Note that the physical image will be deleted from the server if checkbox is selected.');">
								<fieldset style="margin:5px 0px 5px 5px;">
							    	<legend>Authorized to Remove this Image</legend>
									<input name="imgdel" type="hidden" value="<?php echo $imgArr["imgid"]; ?>" />
									<input name="taxon" type="hidden" value="<?php echo $tEditor->getTid(); ?>" />
									<input name="category" type="hidden" value="<?php echo $category; ?>" />
									<input name="removeimg" type="checkbox" value="1" CHECKED /> Remove image from server (as well as database)
									<div style='margin-top:2px;'>
										<input type='submit' name='action' id='submit' value='Delete Image'/>
									</div>
						    	</fieldset>
						    </form>
					    	<?php 
						}
						?>
					</div>
				</td></tr>
				<tr><td colspan='2'>
					<div style='margin:10px 0px 0px 0px;clear:both;'>
						<hr>
					</div>
				</td></tr>
			</table>
			<?php 
		}
	}
 }
 else{
 	echo "<h1>You must be logged in and authorized to edit images. Please login.</h1>";
 }
 ?>
 	</td></tr>
 </table>
<?php  
include($serverRoot."/util/footer.php");
 ?>
	
</body>
</html>

<?php
 
 class TPEditor {

 	private $tid;
	private $sciName;
	private $author;
	private $parentTid;
	private $family;
	private $rankId;
	private $language;
 	private $submittedTid;
 	private $submittedSciName;
	private $fileName;
	private $taxonCon;
	private $imageRootPath = "";
	private $imageRootUrl = "";

	private $maxImageWidth = 1024;
	private $maxImageHeight = 1024;
	private $maxThumbnailWidth = 250;
	private $maxThumbnailHeight = 300;
	
 	public function __construct(){
		$this->imageRootPath = $GLOBALS["imageRootPath"];
		if(substr($this->imageRootPath,-1) != "/") $this->imageRootPath .= "/";  
		$this->imageRootUrl = $GLOBALS["imageRootUrl"];
		if(substr($this->imageRootUrl,-1) != "/") $this->imageRootUrl .= "/";
 		$this->taxonCon = MySQLiConnectionFactory::getCon("readonly");
 	}
 	
 	public function __destruct(){
		if(!($this->taxonCon === null)) $this->taxonCon->close();
	}
 	
 	public function getConnection($conType){
 		if(!$conType) $conType = "readonly";
 		$con = MySQLiConnectionFactory::getCon($conType);
 		return $con;
 	}
	
 	public function setTaxon($t){
		if(intval($t)){
			$this->tid = $t;
		}
		else{
			$this->sciName = $t;
		}
		$sql = "SELECT t.TID, ts.family, t.SciName, t.Author, t.RankId, ts.ParentTID, t.SecurityStatus, ts.TidAccepted ". 
			"FROM taxstatus ts INNER JOIN taxa t ON ts.tid = t.TID ".
			"WHERE (ts.taxauthid = 1) ";
		if($this->tid){
			$sql .= "AND t.TID = ".$this->tid;
		}
		else{
			$sql .= "AND t.SciName = '".$this->sciName."'";
		}
		$result = $this->taxonCon->query($sql);
		if($row = $result->fetch_object()){
			if($row->TID == $row->TidAccepted){
				$this->tid = $row->TID;
				if(!$this->sciName) $this->sciName = $row->SciName;
				$this->family = $row->family;
				$this->author = $row->Author;
				$this->rankId = $row->RankId;
				$this->parentTid = $row->ParentTID;
			}
			else{
				$this->submittedTid = $row->TID;
				$this->submittedSciName = $row->SciName;
				$this->tid = $row->TidAccepted;
				$sqlNew = "SELECT ts.family, t.SciName, t.Author, t.RankId, ts.ParentTID, t.SecurityStatus, ts.TidAccepted ". 
					"FROM taxstatus ts INNER JOIN taxa t ON ts.tid = t.TID ".
					"WHERE (ts.taxauthid = 1) AND (t.TID = ".$this->tid.")";
				$resultNew = $this->taxonCon->query($sqlNew);
				if($rowNew = $resultNew->fetch_object()){
					if(!$this->sciName) $this->sciName = $row->SciName;
					$this->family = $row->family;
					$this->author = $row->Author;
					$this->rankId = $row->RankId;
					$this->parentTid = $row->ParentTID;
				}
				$resultNew->free();
			}
		}
	    else{
	    	$this->sciName = "unknown";
	    }
	    $result->free();
 	}
 	
 	public function getTid(){
 		return $this->tid;
 	}
 	
 	public function getSciName(){
 		return $this->sciName;
 	}
	
 	public function getSubmittedTid(){
 		return $this->submittedTid;
 	}
 	
 	public function getSubmittedSciName(){
 		return $this->submittedSciName;
 	}
 	
 	public function getChildrenTaxa(){
		$childrenArr = Array();
		$sql = "SELECT t.Tid, t.SciName, t.Author ".
			"FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
			"WHERE ts.taxauthid = 1 AND ts.ParentTid = ".$this->tid." ORDER BY t.SciName";
		$result = $this->taxonCon->query($sql);
		while($row = $result->fetch_object()){
			$childrenArr[$row->Tid]["sciname"] = $row->SciName;
			$childrenArr[$row->Tid]["author"] = $row->Author;
		}
		$result->free();
		return $childrenArr;
 	}
	
 	public function getSynonym(){
 		$synArr = Array();
		$sql = "SELECT t2.tid, t2.SciName, ts.SortSequence ".
			"FROM (taxa t1 INNER JOIN taxstatus ts ON t1.tid = ts.tidaccepted) ".
			"INNER JOIN taxa t2 ON ts.tid = t2.tid ".
			"WHERE (ts.taxauthid = 1) AND (ts.tid <> ts.TidAccepted) AND (t1.tid = ".$this->tid.") ".
			"ORDER BY ts.SortSequence, t2.SciName";
		//echo $sql."<br>";
		$result = $this->taxonCon->query($sql);
		while($row = $result->fetch_object()){
			$synArr[$row->tid]["sciname"] = $row->SciName;
			$synArr[$row->tid]["sortsequence"] = $row->SortSequence;
		}
		$result->free();
 		return $synArr;
 	}
 	
	public function editSynonymSort($synSort){
		$status = "";
		$con = $this->getConnection("write");
		foreach($synSort as $editKey => $editValue){
			$sql = "UPDATE taxstatus SET SortSequence = ".$editValue." WHERE tid = ".$editKey." AND TidAccepted = ".$this->tid;
			//echo $sql."<br>";
			if(!$con->query($sql)){
				$status .= $con->error."\nSQL: ".$sql.";<br/> ";
			}
		}
		$con->close();
		if($status) $status = "Errors with editVernacularSort method:<br/> ".$status;
		return $status;
	}

 	public function getVernaculars(){
		$vernArr = Array();
		$sql = "SELECT v.VID, v.VernacularName, v.Language, v.Source, v.username, v.notes, v.SortSequence ".
			"FROM taxavernaculars v ".
			"WHERE (v.tid = ".$this->tid.") ";
		if($this->language) $sql .= "AND v.Language = '".$this->language."' ";
		$sql .= "ORDER BY v.Language, v.SortSequence";
		$result = $this->taxonCon->query($sql);
		$vernCnt = 0;
		while($row = $result->fetch_object()){
			$lang = $row->Language;
			$vernArr[$lang][$vernCnt]["vid"] = $row->VID;
			$vernArr[$lang][$vernCnt]["vernacularname"] = $row->VernacularName;
			$vernArr[$lang][$vernCnt]["source"] = $row->Source;
			$vernArr[$lang][$vernCnt]["username"] = $row->username;
			$vernArr[$lang][$vernCnt]["notes"] = $row->notes;
			$vernArr[$lang][$vernCnt]["language"] = $row->Language;
			$vernArr[$lang][$vernCnt]["sortsequence"] = $row->SortSequence;
			$vernCnt++;
		}
		$result->free();
		return $vernArr;
	}
	
	public function editVernacular($inArray){
		$editArr = $this->cleanArray($inArray);
		$con = $this->getConnection("write");
		$vid = $editArr["vid"];
		unset($editArr["vid"]);
		$setFrag = "";
		foreach($editArr as $keyField => $value){
			$setFrag .= ",".$keyField." = \"".$value."\" ";
		}
		$sql = "UPDATE taxavernaculars SET ".substr($setFrag,1)." WHERE VID = ".$vid;
		//echo $sql;
		$status = "";
		if(!$con->query($sql)){
			$status = "Error:editingVernacular: ".$con->error."\nSQL: ".$sql;
		}
		$con->close();
		return $status;
	}
	
	public function addVernacular($inArray){
		$newVerns = $this->cleanArray($inArray);
		$con = $this->getConnection("write");
		$sql = "INSERT INTO taxavernaculars (tid,".implode(",",array_keys($newVerns)).") VALUES (".$this->getTid().",\"".implode("\",\"",$newVerns)."\")";
		//echo $sql;
		$status = "";
		if(!$con->query($sql)){
			$status = "Error:addingNewVernacular: ".$con->error."\nSQL: ".$sql;
		}
		$con->close();
		return $status;
	}
	
	public function deleteVernacular($delVid){
		$con = $this->getConnection("write");
		$sql = "DELETE FROM taxavernaculars WHERE VID = ".$delVid;
		//echo $sql;
		$status = "";
		if(!$con->query($sql)){
			$status = "Error:deleteVernacular: ".$con->error."\nSQL: ".$sql;
		}
		else{
			$status = "";
		}
		$con->close();
		return $status;
	}

	public function getDescriptions(){
		$descriptionsArr = Array();
		$sql = "SELECT td.tdid, td.Heading, td.Description, td.Notes, td.Source, td.Language, td.DisplayLevel, td.DisplayHeader, td.SortSequence ".
			"FROM taxadescriptions td ".
			"WHERE (td.TID = $this->tid) ";
		if($this->language) $sql .=	"AND (td.Language = '".$this->language."') ";
		$sql .=	"ORDER BY td.Language, td.DisplayLevel, td.SortSequence";
		//echo $sql;
		$result = $this->taxonCon->query($sql);
		while($row = $result->fetch_object()){
			$descriptionsArr[$row->Language][$row->DisplayLevel][$row->Heading]["tdid"] = $row->tdid;
			$descriptionsArr[$row->Language][$row->DisplayLevel][$row->Heading]["displayheader"] = $row->DisplayHeader;
			$descriptionsArr[$row->Language][$row->DisplayLevel][$row->Heading]["description"] = $row->Description;
			$descriptionsArr[$row->Language][$row->DisplayLevel][$row->Heading]["notes"] = $row->Notes;
			$descriptionsArr[$row->Language][$row->DisplayLevel][$row->Heading]["source"] = $row->Source;
			$descriptionsArr[$row->Language][$row->DisplayLevel][$row->Heading]["sortsequence"] = $row->SortSequence;
		}
		$result->free();
		ksort($descriptionsArr);
		return $descriptionsArr;
	}

	public function editDescription($inArray){
		$descrArr = $this->cleanArray($inArray);
		$con = $this->getConnection("write");
		$targetTdid = $descrArr["tdid"];
		unset($descrArr["tdid"]);
		$setFrag = "";
		foreach($descrArr as $keyField => $value){
			$setFrag .= ",".$keyField." = \"".$value."\" ";
		}
		$sql = "UPDATE taxadescriptions SET ".substr($setFrag,1)." ".
			"WHERE tdid = ".$targetTdid;
		//echo $sql;
		$status = "";
		if(!$con->query($sql)){
			$status = "Error:editingDescription: ".$con->error."\nSQL: ".$sql;
		}
		$con->close();
		return $status;
	}

	public function deleteDescription($tdid){
		$con = $this->getConnection("write");
		$sql = "DELETE FROM taxadescriptions WHERE tdid = ".$tdid;
		//echo $sql;
		$status = "";
		if(!$con->query($sql)){
			$status = "Error:deleteDescription: ".$con->error."\nSQL: ".$sql;
		}
		else{
			$status = "";
		}
		$con->close();
		return $status;
	}

	public function addDescription($inArray){
		$descrArr = $this->cleanArray($inArray);
		$con = $this->getConnection("write");
		$sql = "INSERT INTO taxadescriptions (tid,".implode(",",array_keys($descrArr)).") VALUES (".$this->tid.",\"".implode("\",\"",$descrArr)."\")";
		//echo $sql;
		$status = "";
		if(!$con->query($sql)){
			$status = "Error:addingNewDescription: ".$con->error."\nSQL: ".$sql;
		}
		$con->close();
		return $status;
	}

	public function getImages(){
		$imageArr = Array();
		$sql = "SELECT DISTINCT ti.imgid, ti.url, ti.thumbnailurl, ti.photographer, ti.photographeruid, ".
			"IFNULL(ti.photographer,CONCAT_WS(' ',u.firstname,u.lastname)) AS photographerdisplay, ti.imagetype, ti.caption, ti.owner, ".
			"ti.anatomy, ti.locality, ti.occid, ti.notes, ti.sortsequence, ti.username, ti.sourceurl, ti.copyright ".
			"FROM ((images ti INNER JOIN taxstatus ts ON ti.tid = ts.tid) ".
			"LEFT JOIN users u ON ti.photographeruid = u.uid) ".
			"INNER JOIN taxa t ON ts.tidaccepted = t.TID ".
			"WHERE (ts.taxauthid = 1) AND (t.tid = ".$this->tid.") ".
			"ORDER BY ti.sortsequence";
		//echo $sql;
		$result = $this->taxonCon->query($sql);
		$imgCnt = 0;
		while($row = $result->fetch_object()){
			$imageArr[$imgCnt]["imgid"] = $row->imgid;
			$imageArr[$imgCnt]["url"] = $row->url;
			$imageArr[$imgCnt]["thumbnailurl"] = $row->thumbnailurl;
			$imageArr[$imgCnt]["photographer"] = $row->photographer;
			$imageArr[$imgCnt]["photographeruid"] = $row->photographeruid;
			$imageArr[$imgCnt]["photographerdisplay"] = $row->photographerdisplay;
			$imageArr[$imgCnt]["imagetype"] = $row->imagetype;
			$imageArr[$imgCnt]["caption"] = $row->caption;
			$imageArr[$imgCnt]["owner"] = $row->owner;
			$imageArr[$imgCnt]["anatomy"] = $row->anatomy;
			$imageArr[$imgCnt]["locality"] = $row->locality;
			$imageArr[$imgCnt]["sourceurl"] = $row->sourceurl;
			$imageArr[$imgCnt]["copyright"] = $row->copyright;
			$imageArr[$imgCnt]["occid"] = $row->occid;
			$imageArr[$imgCnt]["notes"] = $row->notes;
			$imageArr[$imgCnt]["sortsequence"] = $row->sortsequence;
			$imageArr[$imgCnt]["username"] = $row->username;
			$imgCnt++;
		}
		$result->free();
		return $imageArr;
	}
	
	public function echoPhotographerSelect($userId = 0){
		$sql = "SELECT u.uid, CONCAT_WS(' ',u.lastname,u.firstname) AS fullname ".
			"FROM users u ORDER BY u.lastname, u.firstname ";
		$result = $this->taxonCon->query($sql);
		while($row = $result->fetch_object()){
			echo "<option value='".$row->uid."' ".($row->uid == $userId?"SELECTED":"").">".$row->fullname."</option>";
		}
		$result->close();
	}

	public function editImage($inArray){
		$imgEdits = $this->cleanArray($inArray);
		$con = $this->getConnection("write");
		$sql = "UPDATE images SET ";
		$sql .= "caption = \"".$imgEdits["caption"]."\", ";
		$sql .= "url = \"".$imgEdits["url"]."\", ";
		$sql .= "thumbnailurl = \"".$imgEdits["thumbnailurl"]."\", ";
		$sql .= "photographer = ".($imgEdits["photographer"]?"\"".$imgEdits["photographer"]."\"":"NULL").", ";
		$sql .= "photographeruid = ".($imgEdits["photographeruid"]?$imgEdits["photographeruid"]:"\N").", ";
		$sql .= "owner = \"".$imgEdits["owner"]."\", ";
		$sql .= "sourceurl = \"".$imgEdits["sourceurl"]."\", ";
		$sql .= "copyright = \"".$imgEdits["copyright"]."\", ";
		$sql .= "locality = \"".$imgEdits["locality"]."\", ";
		$sql .= "occid = ".($imgEdits["occid"]?$imgEdits["occid"]:"NULL").", ";
		$sql .= "anatomy = \"".$imgEdits["anatomy"]."\", ";
		$sql .= "imagetype = \"".$imgEdits["imagetype"]."\", ";
		$sql .= "notes = \"".$imgEdits["notes"]."\", ";
		if(!$imgEdits["sortsequence"]){
			$sql .= "sortsequence = 50 ";
		}
		else{
			$sql .= "sortsequence = ".$imgEdits["sortsequence"]." ";
		}
		$sql .= " WHERE imgid = ".$imgEdits["imgid"];
		//echo $sql;
		$status = "";
		if($con->query($sql)){
			$this->setPrimaryImage($con, $this->tid);
			if(array_key_exists("addtoparent",$imgEdits)){
				$sql = "INSERT INTO images (tid, url, thumbnailurl, photographer, photographeruid, imagetype, caption, owner, sourceurl, copyright, locality, occid, notes, anatomy, sortsequence) 
					VALUES (".$this->parentTid.",\"".$imgEdits["url"]."\",\"".$imgEdits["thumbnailurl"]."\",\"".
					$imgEdits["photographer"]."\",".$imgEdits["photographeruid"].",\"".
					$imgEdits["imagetype"]."\",\"".$imgEdits["caption"]."\",\"".$imgEdits["owner"]."\",\"".$imgEdits["sourceurl"]."\",\"".
					$imgEdits["copyright"]."\",\"".$imgEdits["locality"]."\",\"".$imgEdits["occid"]."\",\"".$imgEdits["notes"]."\",\"".
					$imgEdits["anatomy"]."\",".($imgEdits["sortsequence"]?$imgEdits["sortsequence"]:"50").")";
				//echo $sql;
				if($con->query($sql)){
					$this->setPrimaryImage($con,$this->parentTid);
				}
				else{
					$status = "unable to upload image to parent taxon";
					//$status = "Error:editImage:loading the parent data: ".$con->error."<br/>SQL: ".$sql;
				}
			}
		}
		else{
			$status = "Error:editImage: ".$con->error."\nSQL: ".$sql;
		}
		$con->close();
		return $status;
	}
	
	public function changeTaxon($imgId,$targetTid,$sourceTid){
		$con = $this->getConnection("write");
		$sql = "UPDATE images SET tid = $targetTid, sortsequence = 50 WHERE imgid = $imgId";
		if($con->query($sql)){
			//$sql2 = "DELETE FROM images WHERE tid = $sourceTid AND url = '".."'";
			//$con->query($sql2);
		}
		$this->setPrimaryImage($con,$this->tid);
		$con->close();
	}
	
	public function imageExists($url, $targetTid){
		if($url && $targetTid){
			$sql = "SELECT ti.imgid FROM images ti WHERE ti.tid = ".$targetTid." AND ti.url = '".$url."'";
			$result = $this->taxonCon->query($sql);
			if($result->num_rows > 0) return true;
		}
		return false;
	}
	
	public function editImageSort($imgSortEdits){
		$status = "";
		$con = $this->getConnection("write");
		foreach($imgSortEdits as $editKey => $editValue){
			$sql = "UPDATE images SET sortsequence = ".$editValue." WHERE imgid = ".$editKey.";";
			//echo $sql;
			if(!$con->query($sql)){
				$status .= $con->error."\nSQL: ".$sql."; ";
			}
		}
		$this->setPrimaryImage($con,$this->tid);
		$con->close();
		if($status) $status = "with editImageSort method: ".$status;
		return $status;
	}

	public function loadImageData($inArray){
		$imageData = $this->cleanArray($inArray);
		$con = $this->getConnection("write");
		$imgUrl = "";
		if(array_key_exists("url",$imageData)){
			$imgUrl = $imageData["url"];
		}
		else{
			$imgUrl = $this->getUrlPath($imageData["imagetype"]);
		}
		$imgThumbnailUrl = $this->createImageThumbnail($imgUrl);
		$sql = "INSERT INTO images (tid, url, thumbnailurl, photographer, photographeruid, imagetype, caption, ".
			"owner, sourceurl, copyright, locality, occid, notes, anatomy, username, sortsequence) ".
			"VALUES (".$this->tid.",\"".$imgUrl."\",".($imgThumbnailUrl?"\"".$imgThumbnailUrl."\"":"NULL").",".
			($imageData["photographer"]?"\"".$imageData["photographer"]."\"":"NULL").",".$imageData["photographeruid"].",\"".
			$imageData["imagetype"]."\",\"".$imageData["caption"]."\",\"".$imageData["owner"]."\",\"".$imageData["sourceurl"]."\",\"".$imageData["copyright"]."\",\"".$imageData["locality"]."\",".
			($imageData["occid"]?$imageData["occid"]:"NULL").",\"".$imageData["notes"]."\",\"".
			$imageData["anatomy"]."\",\"".$imageData["username"]."\",".($imageData["sortsequence"]?$imageData["sortsequence"]:"50").")";
		//echo $sql;
		$status = "";
		if($con->query($sql)){
			$this->setPrimaryImage($con, $this->tid);
			if($this->rankId > 220 && !$this->submittedTid && array_key_exists("addtoparent",$imageData)){
				$sql = "INSERT INTO images (tid, url, thumbnailurl, photographer, photographeruid, imagetype, caption, ".
					"owner, sourceurl, copyright, locality, occid, notes, anatomy, username, sortsequence) ". 
					"VALUES (".$this->parentTid.",\"".$imgUrl."\",".($imgThumbnailUrl?"\"".$imgThumbnailUrl."\"":"NULL").",".
					($imageData["photographer"]?"\"".$imageData["photographer"]."\"":"NULL").",".$imageData["photographeruid"].",\"".
					$imageData["imagetype"]."\",\"".$imageData["caption"]."\",\"".$imageData["owner"]."\",\"".$imageData["sourceurl"]."\",\"".$imageData["copyright"]."\",\"".$imageData["locality"]."\",".
					($imageData["occid"]?$imageData["occid"]:"NULL").",\"".$imageData["notes"]."\",\"".
					$imageData["anatomy"]."\",\"".$imageData["username"]."\",".($imageData["sortsequence"]?$imageData["sortsequence"]:"50").")";
				//echo $sql;
				if($con->query($sql)){
					$this->setPrimaryImage($con,$this->parentTid);
				}
				else{
					$status = "Error: unable to upload image to parent taxon";
					//$status = "Error:loadImageData:loading the parent data: ".$con->error."<br/>SQL: ".$sql;
				}
			}
		}
		else{
			$status = "loadImageData: ".$con->error."<br/>SQL: ".$sql;
		}
		$con->close();
		return $status;
	}
	
 	public function setFileName($fName){
		$fName = str_replace("'","",$fName);
		$fName = str_replace(" ","_",$fName);
		$fName = str_replace("\"","",$fName);
		if(strlen($fName) > 30) {
			$fName = substr($fName,0,25).substr($fName,strrpos($fName,"."));
		}
 		$this->fileName = $fName;
 	}
 	
	public function getDownloadPath($subFolder){
		if(substr($this->imageRootPath,-1,1) != "/") $this->imageRootPath .= "/";
		$path = $this->imageRootPath.$this->family."/".$subFolder."/";
 		if(!file_exists($this->imageRootPath.$this->family)){
 			mkdir($this->imageRootPath.$this->family, 0775);
 		}
 		if(!file_exists($this->imageRootPath.$this->family."/".$subFolder)){
 			mkdir($this->imageRootPath.$this->family."/".$subFolder, 0775);
 		}
 		//Check and see if file already exists, if so, rename filename until it has a unique name
 		$tempFileName = $this->fileName;
 		$cnt = 0;
 		while(file_exists($path.$tempFileName)){
 			$tempFileName = substr($this->fileName,0,strrpos($this->fileName,"."))."_".$cnt.substr($this->fileName,strrpos($this->fileName,".")).""; 
 			$cnt++;
 		}
 		$this->fileName = $tempFileName;
 		return $path.$this->fileName;
 	}

 	private function getUrlPath($imagetype){
		$path = $this->imageRootUrl.$this->family."/".$imagetype."/".$this->fileName;
		return $path;
 	}
 	
	public function deleteImage($imgIdDel, $removeImg){
		$con = $this->getConnection("write");
		$imgUrl = "";
		$imgThumbnailUrl = ""; 
		$sqlQuery = "SELECT ti.url, ti.thumbnailurl FROM images ti WHERE ti.imgid = ".$imgIdDel;
		$result = $con->Query($sqlQuery);
		if($row = $result->fetch_object()){
			$imgUrl = $row->url;
			$imgThumbnailUrl = $row->thumbnailurl;
		}
		$result->close();

		$sql = "DELETE FROM images WHERE imgid = ".$imgIdDel;
		//echo $sql;
		$status = "";
		if(!$con->query($sql)){
			$status = "deleteImage: ".$con->error."\nSQL: ".$sql;
		}
		else{
			if($removeImg){
				//Delete other references to this image so that you don't create broken links
				$sql = "DELETE FROM images WHERE url = '".$imgUrl."'";
				$con->query($sql);
				
				//Delete image from server
				$imgDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgUrl);
				if(file_exists($imgDelPath)){
					if(!unlink($imgDelPath)){
						$status = "Deleted records from database successfully but FAILED to delete image from server. The Image will have to be deleted manually.";
					}
				}
				$imgTnDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgThumbnailUrl);
				if(file_exists($imgTnDelPath)){
					unlink($imgTnDelPath);
				}
			}
		}
		$this->setPrimaryImage($con,$this->tid);
		$con->close();
		return $status;
	}
	
	private function createImageThumbnail($imgUrl){
		$newThumbnailUrl = "";
		if($imgUrl){
			$imgPath = "";
			$newThumbnailUrl = "";
			$newThumbnailPath = "";
			if(strpos($imgUrl,"http://") === 0 && strpos($imgUrl,$this->imageRootUrl) !== 0){
				$imgPath = $imgUrl;
				if(!is_dir($this->imageRootPath."misc_thumbnails/")){
					if(!mkdir($this->imageRootPath."misc_thumbnails/", 0775)) return "";
				}
				$fileName = str_ireplace(".jpg","_tn.jpg",substr($imgUrl,strrpos($imgUrl,"/")));
				$newThumbnailPath = $this->imageRootPath."misc_thumbnails/".$fileName;
				$newThumbnailUrl = $this->imageRootUrl."misc_thumbnails/".$fileName;
			}
			elseif(strpos($imgUrl,$this->imageRootUrl) === 0){
				$imgPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgUrl);
				$newThumbnailUrl = str_ireplace(".jpg","_tn.jpg",$imgUrl);
				$newThumbnailPath = str_replace($this->imageRootUrl,$this->imageRootPath,$newThumbnailUrl);
			}
			if(!$newThumbnailUrl) return "";
			if(file_exists($imgPath) || $this->url_exists($filePath)){
				if(!file_exists($newThumbnailPath)){
		        	list($sourceWidth, $sourceHeight, $imageType) = getimagesize($imgPath);
		        	$newWidth = $this->maxThumbnailWidth;
		        	$newHeight = round($sourceHeight*($this->maxThumbnailWidth/$sourceWidth));
		        	if($newHeight > $this->maxThumbnailHeight){
		        		$newHeight = $this->maxThumbnailHeight;
		        		$newWidth = round($sourceWidth*($this->maxThumbnailHeight/$sourceHeight));
		        	}
		        	
				    switch ($imageType){
				        case 1: 
				        	$sourceImg = imagecreatefromgif($imgPath);
				        	break;
				        case 2: 
				        	$sourceImg = imagecreatefromjpeg($imgPath);  
				        	break;
				        case 3: 
				        	$sourceImg = imagecreatefrompng($imgPath);
				        	break;
				        default: 
				        	return "";
				        	break;
				    }
		        	
		    		$tmpImg = imagecreatetruecolor($newWidth,$newHeight);
		
				    /* Check if this image is PNG or GIF to preserve its transparency */
				    if(($imageType == 1) || ($imageType==3)){
				        imagealphablending($tmpImg, false);
				        imagesavealpha($tmpImg,true);
				        $transparent = imagecolorallocatealpha($tmpImg, 255, 255, 255, 127);
				        imagefilledrectangle($tmpImg, 0, 0, $newWidth, $newHeight, $transparent);
				    }
					imagecopyresampled($tmpImg,$sourceImg,0,0,0,0,$newWidth, $newHeight,$sourceWidth,$sourceHeight);
		
					switch ($imageType){
				        case 1: 
				        	if(!imagegif($tmpImg,$newThumbnailPath)){
				        		echo "<div style='margin:5px;'>Failed to write GIF thumbnail: $newThumbnailPath</div>";
				        	}
				        	break;
				        case 2: 
				        	if(!imagejpeg($tmpImg, $newThumbnailPath, 50)){
				        		echo "<div style='margin:5px;'>Failed to write JPG thumbnail: $newThumbnailPath</div>";
				        	}
				        	break; // best quality
				        case 3: 
				        	if(!imagepng($tmpImg, $newThumbnailPath, 0)){
				        		echo "<div style='margin:5px;'>Failed to write PNG thumbnail: $newThumbnailPath</div>";
				        	}
				        	break; // no compression
				    }
				    imagedestroy($tmpImg);
				}
			}
		}
		return $newThumbnailUrl;
	}

	private function setPrimaryImage($conn,$subjectTid){
		$sql1 = "SELECT count(ti.imgid) AS reccnt FROM images ti INNER JOIN taxstatus ts ON ti.tid = ts.tid 
			WHERE ts.taxauthid = 1 AND ti.SortSequence = 1 AND ts.tidaccepted = ".$subjectTid;
		//echo $sql1;
		$result = $conn->query($sql1);
		if($row = $result->fetch_object()){
			if($row->reccnt == 0){
				$sql2 = "UPDATE images ti2 INNER JOIN (SELECT ti.imgid, ti.sortsequence FROM images ti INNER JOIN taxstatus ts ON ti.tid = ts.tid
					WHERE ((ts.taxauthid = 1) AND (ts.tidaccepted=".$subjectTid.")) ORDER BY ti.SortSequence LIMIT 1) innertab ON ti2.imgid = innertab.imgid 
					SET ti2.SortSequence = 1";
				//echo $sql2;
				$conn->query($sql2);
			}
		}
		$result->close();
	}
	
	public function getMaps(){
		$mapArr = Array();
		$sql = "SELECT tm.mid, tm.url, tm.title, tm.initialtimestamp ".
			"FROM taxamaps tm INNER JOIN taxa t ON tm.tid = t.TID ".
			"WHERE (tm.tid = ".$this->tid.") ";
		$result = $this->taxonCon->query($sql);
		$mapCnt = 0;
		while($row = $result->fetch_object()){
			$mapArr[$mapCnt]["url"] = $row->url;
			$mapArr[$mapCnt]["title"] = $row->title;
			$mapCnt++;
		}
		$result->close();
		return $mapArr;
	}
	
	public function getLinks(){
		$linkArr = Array();
		$sql = "SELECT tl.url, tl.title ".
			"FROM taxalinks tl INNER JOIN taxa ON tl.tid = taxa.TID ".
			"WHERE ((taxa.TID = $tid)) ";
		$result = $this->taxonCon->query($sql);
		$linkCnt = 0;
		while($row = $result->fetch_object()){
			$linkArr[$linkCnt]["url"] = $row->url;
			$linkArr[$linkCnt]["title"] = $row->title;
			$linkCnt++;
		}
		$result->free();
		return $linkArr;
	}
	
	public function getAuthor(){
 		return $this->author;
 	}
 
 	public function getFamily(){
 		return $this->family;
 	}
 
 	public function getRankId(){
 		return $this->rankId;
 	}
 
 	public function getParentTid(){
 		return $this->parentTid;
 	}

 	public function setLanguage($lang){
 		return $this->language = $lang;
 	}
 	
 	private function cleanArray($arr){
 		$newArray = Array();
 		foreach($arr as $key => $value){
 			$newKey = trim($key);
 			$newKey = preg_replace('/\s\s+/', ' ',$newKey);
 			$newValue = trim($value);
 			$newValue = preg_replace('/\s\s+/', ' ',$newValue);
 			$newArray[$newKey] = $newValue;
 		}
 		return $newArray;
 	}
	
	private function url_exists($url) {
	    // Version 4.x supported
	    $handle   = curl_init($url);
	    if (false === $handle)
	    {
	        return false;
	    }
	    curl_setopt($handle, CURLOPT_HEADER, false);
	    curl_setopt($handle, CURLOPT_FAILONERROR, true);  // this works
	    curl_setopt($handle, CURLOPT_HTTPHEADER, Array("User-Agent: Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.15) Gecko/20080623 Firefox/2.0.0.15") ); // request as if Firefox   
	    curl_setopt($handle, CURLOPT_NOBODY, true);
	    curl_setopt($handle, CURLOPT_RETURNTRANSFER, false);
	    $connectable = curl_exec($handle);
	    curl_close($handle);  
	    return $connectable;
	}	
 }
?>

