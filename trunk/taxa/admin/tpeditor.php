<?php
/*
 * Created on 26 Dec 2008
 * Author: E.E. Gilbert
 */

 //error_reporting(E_ALL);
 include_once('../../config/symbini.php');
 include_once($serverRoot.'/config/dbconnection.php');
 set_time_limit(120);
 ini_set("max_input_time",120);
 
 $tid = $_REQUEST["tid"];
 $category = array_key_exists("category",$_REQUEST)?$_REQUEST["category"]:""; 
 $lang = array_key_exists("lang",$_REQUEST)?$_REQUEST["lang"]:"";
 $action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";
 
 $tEditor = new TPEditor();
 $tEditor->setTid($tid);
 $tEditor->setLanguage($lang);
 
 $editable = false;
 if($isAdmin || array_key_exists("TaxonProfile",$userRights)){
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
	 elseif($action == "Edit Description Block"){
	 	$tEditor->editDescriptionBlock();
	 }
	 elseif($action == "Delete Description Block"){
	 	$tEditor->deleteDescriptionBlock();
	 }
	 elseif($action == "Add Description Block"){
	 	$tEditor->addDescriptionBlock();
	 }
	 elseif($action == "Edit Statement"){
	 	$tEditor->editStatement();
	 }
	 elseif($action == "Delete Statement"){
	 	$tEditor->deleteStatement();
	 }
	 elseif($action == "Add Statement"){
	 	$tEditor->addStatement();
	 }
	 elseif($action == "Submit Image Edits"){
		$status = $tEditor->editImage();
	 }
	 elseif($action == "Transfer Image"){
	 	$tEditor->changeTaxon($_REQUEST["imgid"],$tid,$_REQUEST["sourcetid"]);
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
	 	$status = $tEditor->loadImageData();
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
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>" />
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

		function toggleById(target){
			var obj = document.getElementById(target);
			if(obj.style.display=="none"){
				obj.style.display="block";
			}
			else {
				obj.style.display="none";
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
include($serverRoot.'/header.php');
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
					<li><a href="tpeditor.php?tid=<?php echo $tEditor->getTid(); ?>&category=images">Edit Images</a></li>
					<ul>
						<li><a href="tpeditor.php?tid=<?php echo $tEditor->getTid(); ?>&category=imagequicksort">Edit Image Sorting Order</a></li>
						<li><a href="tpeditor.php?tid=<?php echo $tEditor->getTid(); ?>&category=imageadd">Add a New Image</a></li>
					</ul>
					<li><a href="tpeditor.php?tid=<?php echo $tEditor->getTid(); ?>&category=common">Synonyms / Common Names</a></li>
					<li><a href="tpeditor.php?tid=<?php echo $tEditor->getTid(); ?>&category=textdescr">Text Descriptions</a></li>
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
	if($tEditor->getRankId() > 140) echo "&nbsp;<a href='tpeditor.php?tid=".$tEditor->getParentTid()."'><img border='0' height='10px' src='../../images/toparent.jpg' title='Go to Parent' /></a>";
	echo "</div>\n";
	//Display Family
	echo "<div id='family' style='margin-left:20px;margin-top:0.25em;'><b>Family:</b> ".$tEditor->getFamily()."</div>\n";
	
	//Display children taxa
/*	$childrenArr = $tEditor->getChildrenTaxa();
	if($childrenArr){
		echo "<div style='width:300px;margin:5px 0px 5px 25px;font-weight:bold;border:1px dotted olive;padding:3px;'>Children Taxa:\n ";
		foreach($childrenArr as $tid => $childArr){
			echo "<div style='margin-left:10px;'><a href='tpeditor.php?tid=".$tid."'><b><i>".$childArr["sciname"]."</i></b></a> ".$childArr["author"]."</div>\n";
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
			echo "<input type='hidden' name='tid' value='".$tEditor->getTid()."' />";
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
		echo "<input type='hidden' name='tid' value='".$tEditor->getTid()."' />";
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
					echo "<input type='hidden' name='tid' value='".$tEditor->getTid()."' />";
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
					echo "<input type='hidden' name='tid' value='".$tEditor->getTid()."' />";
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
		?>
		<div>
			<b>Descriptions</b>&nbsp;&nbsp;&nbsp;
			<span onclick="javascript:toggleById('adddescrblock');" title="Add a New Description">
				<img style='border:0px;width:15px;' src='../../images/add.png'/>
			</span>
		</div>
		<div id='adddescrblock' style='display:none;'>
			<form name='adddescrblockform' action="tpeditor.php" method="get">
				<fieldset style='width:475px;margin:20px;'>
	    			<legend><b>New Description Block</b></legend>
					<div style=''>
						Language: <input id='language' name='language' style='margin-top:5px;' type='text' />
					</div>
					<div style=''>
						Source: <input id='source' name='source' style='margin:2px;width:300px;' type='text' />
					</div>
					<div style=''>
						Notes: <input id='notes' name='notes' style='margin:2px;width:300px;' type='text' />
					</div>
					<div style="float:right;">
						<input name='action' style='margin-top:5px;' type='submit' value='Add Description Block' />
						<input type='hidden' name='tid' value='<?php echo $tEditor->getTid();?>' />
						<input type='hidden' name='category' value='<?php echo $category; ?>' />
					</div>
					<div style=''>
						Display Level: <input id='displaylevel' name='displaylevel' style='margin:2px;width:40px;' type='text' />
					</div>
				</fieldset>
			</form>
		</div>
		<?php 
		$descList = $tEditor->getDescriptions();
		if($descList){
			foreach($descList as $lang => $dlArr){
		    	foreach($dlArr as $displayLevel => $bArr){
		    		?>
    				<fieldset style='width:500px;margin:10px 5px 5px 5px;'>
						<legend><b><?php echo $lang.": Display Level ".$displayLevel; ?></b></legend>
						<div style="float:right;" onclick="javascript:toggleById('dblock-<?php echo $bArr["tdbid"];?>');" title="Edit Description Block">
							<img style='border:0px;width:12px;' src='../../images/edit.png'/>
						</div>
						<div><b>Source:</b> <?php echo $bArr["source"]; ?></div> 
						<div><b>Notes:</b> <?php echo $bArr["notes"]; ?></div> 
						<div id="dblock-<?php echo $bArr["tdbid"];?>" style="display:none;margin-top:10px;">
							<fieldset>
								<legend><b>Description Block Edits</b></legend>
								<form id='updatedescrblock' name='updatedescrblock' action="tpeditor.php" method="post">
									<div>
										Language: 
										<input name='language' style='margin-top:5px;border:inset;' type='text' value='<?php echo $lang; ?>' />
									</div>
									<div>
										Source: 
										<input id='source' name='source' style='margin-top:5px;border:inset;width:330px;' type='text' value='<?php echo $bArr["source"];?>' />
									</div>
									<div>
										Notes: 
										<input name='notes' style='margin-top:5px;border:inset;width:400px;' type='text' value='<?php echo $bArr["notes"];?>' />
									</div>
									<div style="float:right;margin:10px;">
										<input type='hidden' name='tdbid' value='<?php echo $bArr["tdbid"];?>' />
										<input type='hidden' name='tid' value='<?php echo $tid;?>' />
										<input type='hidden' name='category' value='<?php echo $category;?>'>
										<input type='submit' name='action' value='Edit Description Block' /> 
									</div> 
									<div>
										Display Level: 
										<input id='displaylevel' name='displaylevel' style='margin-top:5px;border:inset;width:40px;' type='text' value='<?php echo $displayLevel;?>' />
									</div>
								</form>
								<div style='margin:5px 0px 5px 20px;border:2px solid red;padding:2px;'>
									<form name='delstmt' action='tpeditor.php' method='post' onsubmit="javascript: return window.confirm('Are you sure you want to delete this Description?');">
										<input type='hidden' name='tdbid' value='<?php echo $bArr["tdbid"];?>' />
										<input type='hidden' name='tid' value='<?php echo $tid;?>' />
										<input type='hidden' name='category' value='<?php echo $category;?>'>
										<input name='action' value='Delete Description Block' style='margin:10px 0px 0px 20px;height:12px;' type='image' src='../../images/del.gif'/> 
										Delete Description Block (Including all statements below) 
									</form>
								</div>
							</fieldset>
						</div>
    					<div style="margin-top:10px;">
							<fieldset>
								<legend><b>Statements</b></legend>
								<div onclick="javascript:toggleById('addstmt-<?php echo $bArr["tdbid"];?>');" style="float:right;" title="Add a New Statement">
									<img style='border:0px;width:15px;' src='../../images/add.png'/>
								</div>
								<div id='addstmt-<?php echo $bArr["tdbid"];?>' style='display:none;'>
									<form name='adddescrstmtform' action="tpeditor.php" method="post">
										<fieldset style='margin:5px 0px 0px 15px;'>
							    			<legend><b>New Description Statement</b></legend>
											<div style='margin:3px;'>
												Heading: <input name='heading' style='margin-top:5px;' type='text' />&nbsp;&nbsp;&nbsp;&nbsp;
												<input name='displayheader' type='checkbox' value='1' CHECKED /> Display Header
											</div>
											<div style='margin:3px;'>
												<textarea name='statement' cols='50' rows='3'></textarea>
											</div>
											<div style="float:right;">
												<input type='hidden' name='tid' value='<?php echo $tEditor->getTid();?>' />
												<input type='hidden' name='tdbid' value='<?php echo $bArr["tdbid"];?>' />
												<input type='hidden' name='category' value='<?php echo $category; ?>' />
												<input name='action' style='margin:3px;' type='submit' value='Add Statement' />
											</div>
											<div style='margin:3px;'>
												Sort Sequence: <input name='sortsequence' style='margin-top:5px;width:40px;' type='text' />
											</div>
										</fieldset>
									</form>
								</div>
								<?php
								if(array_key_exists("stmts",$bArr)){
									$sArr = $bArr["stmts"];
									foreach($sArr as $tdsid => $stmtArr){
										?>
										<div style="margin-top:3px;">
											<b><?php echo $stmtArr["heading"];?></b>&nbsp;&nbsp;&nbsp;
											<?php echo ($stmtArr["displayheader"]?"(header displayed)":"(heading hidden)");?>&nbsp;&nbsp;&nbsp;
											<span onclick="javascript:toggleById('dstmt-<?php echo $tdsid;?>');" title="Edit Statement">
												<img style='border:0px;width:12px;' src='../../images/edit.png'/>
											</span>
										</div>
										<div style='clear:both;'><?php echo $stmtArr["statement"];?></div>
										<div id="dstmt-<?php echo $tdsid;?>" style="display:none;">
											<div style='margin:5px 0px 5px 20px;border:2px solid cyan;padding:5px;'>
												<form id='updatedescr' name='updatedescr' action="tpeditor.php" method="post">
													<div>
														<b>Heading:</b> <input name='heading' style='margin:3px;' type='text' value='<?php echo $stmtArr["heading"];?>' />&nbsp;&nbsp;&nbsp;
														<input name='displayheader' type='checkbox' value='1' <?php echo ($stmtArr["displayheader"]?"CHECKED":"");?> /> Display Header
													</div>
													<div>
														<textarea name='statement' cols='50' rows='3' style='margin:3px;'><?php echo $stmtArr["statement"];?></textarea>
													</div>
													<div style="float:right;margin:10px;">
														<input name='action' type='submit' value='Edit Statement' />
													</div>
													<div>
														<b>Sort Sequence:</b> 
														<input id='sortsequence' name='sortsequence' style='margin:3px;width:40px;' type='text' value='<?php echo $stmtArr["sortsequence"];?>' />&nbsp;&nbsp;
														<input type='hidden' name='tdsid' value='<?php echo $tdsid;?>'>
														<input type='hidden' name='tid' value='<?php echo $tid;?>' />
														<input type='hidden' name='category' value='<?php echo $category;?>'>
													</div>
												</form>
											</div>
											<div style='margin:5px 0px 5px 20px;border:2px solid red;padding:2px;'>
												<form name='delstmt' action='tpeditor.php' method='post' onsubmit="javascript: return window.confirm('Are you sure you want to delete this Description?');">
													<input type='hidden' name='tdsid' value='<?php echo $tdsid;?>' />
													<input type='hidden' name='tid' value='<?php echo $tid;?>' />
													<input type='hidden' name='category' value='<?php echo $category;?>'>
													<input name='action' value='Delete Statement' style='margin:10px 0px 0px 20px;height:12px;' type='image' src='../../images/del.gif'/> 
													Delete Statement 
												</form>
											</div>
										</div>
									<?php 
									}
								}
							?>
							</fieldset>
						</div>
					</fieldset>
					<?php 
				}
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
			$webUrl = (array_key_exists("imageDomain",$GLOBALS)&&substr($imgArr["url"],0,1)=="/"?$GLOBALS["imageDomain"]:"").$imgArr["url"]; 
			$tnUrl = (array_key_exists("imageDomain",$GLOBALS)&&substr($imgArr["thumbnailurl"],0,1)=="/"?$GLOBALS["imageDomain"]:"").$imgArr["thumbnailurl"];
			?>
			<td align='center' valign='bottom'>
				<div style='margin:20px 0px 0px 0px;'>
					<a href="<?php echo $webUrl; ?>">
						<img width="150" src="<?php echo $tnUrl;?>" />
					</a>
				</div>
				<div style='margin-top:2px;'>
					Sort sequence: 
					<b><?php echo $imgArr["sortsequence"];?></b>
				</div>
				<div>
					New Value: 
					<input name="imgid-<?php echo $imgArr["imgid"];?>" type="text" size="5" maxlength="5" />
				</div>
			</td>
			<?php 
			$imgCnt++;
			if($imgCnt%5 == 0){
				?>
				</tr>
				<tr>
					<td colspan='5'>
						<hr>
						<div style='margin-top:2px;'>
							<input type='submit' name='action' id='submit' value='Submit Image Sort Edits' />
						</div>
					</td>
				</tr>
				<tr>
				<?php 
			}
		}
		for($i = (5 - $imgCnt%5);$i > 0; $i--){
			echo "<td>&nbsp;</td>";
		}
		echo "</tr>\n";
		echo "</table>\n";
		echo "<input name='tid' type='hidden' value='".$tEditor->getTid()."'>\n";
		echo "<input name='category' type='hidden' value='".$category."'>\n";
		if($imgCnt%5 != 0) echo "<div style='margin-top:2px;'><input type='submit' name='action' id='imgsortsubmit' value='Submit Image Sort Edits'/></div>\n";
		echo "</form></div>\n";
	}
	elseif($category == "imageadd"){
		?>
		<form enctype='multipart/form-data' action='tpeditor.php' id='imageaddform' method='post' target='_self' onsubmit='return submitAddForm(this);'>
			<fieldset style='margin:5px;width:485px;'>
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
					Add Image to Species Rank 
					<div style='margin-left:10px;'>
						* If scientific name is a subspecies or variety, click this option if you also want image to be displays at the species level
					</div>
				</div>
				<?php }elseif($cArr = $tEditor->getChildrenArr()){ ?>
				<div style='padding:10px;margin:5px;width:475px;border:1px solid yellow;background-color:FFFF99;'>
					Add Image to a Child Taxon 
					<select name='addtotid'>
						<option value='0'>Child Taxon</option>
						<option value='0'>-----------------------</option>
						<?php 
							foreach($cArr as $t => $sn){
								?><option value="<?php echo $t;?>"><?php echo $sn;?></option><?php 
							}
						?>
					</select> 
				</div>
				<?php } ?>
				<input name="tid" type="hidden" value="<?php echo $tEditor->getTid();?>">
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
					<div style="margin:20px;float:left;text-align:center;">
						<?php 
						$webUrl = (array_key_exists("imageDomain",$GLOBALS)&&substr($imgArr["url"],0,1)=="/"?$GLOBALS["imageDomain"]:"").$imgArr["url"]; 
						$tnUrl = (array_key_exists("imageDomain",$GLOBALS)&&substr($imgArr["thumbnailurl"],0,1)=="/"?$GLOBALS["imageDomain"]:"").$imgArr["thumbnailurl"];
						?>
						<a href="<?php echo $webUrl;?>">
							<img src="<?php echo $tnUrl;?>"/>
						</a>
						<?php 
						if($imgArr["originalurl"]){
							$origUrl = (array_key_exists("imageDomain",$GLOBALS)&&substr($imgArr["originalurl"],0,1)=="/"?$GLOBALS["imageDomain"]:"").$imgArr["originalurl"];
							?>
							<br /><a href="<?php echo $origUrl;?>">Open Large Image</a>
							<?php 
						}
						?>
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
							<a href="<?php echo $clientRoot;?>/collections/individual/individual.php?occid=<?php echo $imgArr["occid"]; ?>">
								<?php echo $imgArr["occid"];?>
							</a>
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
									* Will override above selection
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
									<input id="occid<?php  echo $imgArr["imgid"];?>" name="occid" type="text" value="<?php  echo $imgArr["occid"];?>" />
									<span style="cursor:pointer;color:blue;"  onclick="openOccurrenceSearch('occid<?php  echo $imgArr["imgid"];?>')">Link to Occurrence Record</span>
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
									<b>Web Image:</b> 
									<input name='url' type='text' value='<?php echo $imgArr["url"];?>' size='70' maxlength='150' />
									<?php if(stripos($imgArr["url"],$imageRootUrl) === 0){ ?>
									<div style="margin-left:70px;">
										<input type="checkbox" name="renameweburl" value="1" />
										Rename web image file on server to match above edit (web server editing privileges requiered)
									</div>
									<input name='oldurl' type='hidden' value='<?php echo $imgArr["url"];?>' />
									<?php } ?>
								</div>
								<div style='margin-top:2px;'>
									<b>Thumbnail:</b> 
									<input name='thumbnailurl' type='text' value='<?php echo $imgArr["thumbnailurl"];?>' size='70' maxlength='150'>
									<?php if(stripos($imgArr["thumbnailurl"],$imageRootUrl) === 0){ ?>
									<div style="margin-left:70px;">
										<input type="checkbox" name="renametnurl" value="1" />
										Rename thumbnail image file on server to match above edit (web server editing privileges requiered)
									</div>
									<input name='oldthumbnailurl' type='hidden' value='<?php echo $imgArr["thumbnailurl"];?>' />
									<?php } ?>
								</div>
								<div style='margin-top:2px;'>
									<b>Large Image:</b> 
									<input name='originalurl' type='text' value='<?php echo $imgArr["originalurl"];?>' size='70' maxlength='150'>
									<?php if(stripos($imgArr["originalurl"],$imageRootUrl) === 0){ ?>
									<div style="margin-left:80px;">
										<input type="checkbox" name="renameorigurl" value="1" />
										Rename large image file on server to match above edit (web server editing privileges requiered)
									</div>
									<input name='oldoriginalurl' type='hidden' value='<?php echo $imgArr["originalurl"];?>' />
									<?php } ?>
								</div>
								<?php if($tEditor->getRankId() > 220 && !$tEditor->getSubmittedTid() && !$tEditor->imageExists($imgArr["url"],$tEditor->getParentTid())){ ?>
								<div style='padding:10px;margin:5px;width:475px;border:1px solid yellow;background-color:FFFF99;'>
									<input type='checkbox' name='addtoparent' value='1' /> 
									Add Image to Species Rank 
									<div style='margin-left:10px;'>
										* If scientific name is a subspecies or variety, click this option if you also want image to be displays at the species level
									</div>
								</div>
								<?php }elseif($tEditor->getRankId() == 220 && $cArr = $tEditor->getChildrenArr($imgArr["url"])){ ?>
								<div style='padding:10px;margin:5px;width:475px;border:1px solid yellow;background-color:FFFF99;'>
									Add Image to a Child Taxon 
									<select name='addtotid'>
										<option value='0'>Child Taxon</option>
										<option value='0'>-----------------------</option>
										<?php 
											foreach($cArr as $t => $sn){
												?><option value="<?php echo $t;?>"><?php echo $sn;?></option><?php 
											}
										?>
									</select> 
								</div>
								<?php } ?>
				
								<input name="tid" type="hidden" value="<?php echo $tEditor->getTid();?>" />
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
									<input type="hidden" id="targettid-<?php echo $imgArr["imgid"]; ?>" name="tid" value="" />
	
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
									<input name="tid" type="hidden" value="<?php echo $tEditor->getTid(); ?>" />
									<input name="category" type="hidden" value="<?php echo $category; ?>" />
									<input name="removeimg" type="checkbox" value="1" CHECKED /> Remove image from server 
									<div style="margin-left:20px;">
										(Note: leaving unchecked removes image from database w/o removing from server)
									</div>
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
include($serverRoot.'/footer.php');
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

	private $tnPixWidth = 200;
	private $webPixWidth = 1300;
	private $lgPixWidth = 3168;
	private $webFileSizeLimit = 300000;
	
 	public function __construct(){
		$this->imageRootPath = $GLOBALS["imageRootPath"];
		if(substr($this->imageRootPath,-1) != "/") $this->imageRootPath .= "/";  
		$this->imageRootUrl = $GLOBALS["imageRootUrl"];
		if(substr($this->imageRootUrl,-1) != "/") $this->imageRootUrl .= "/";
 		$this->taxonCon = MySQLiConnectionFactory::getCon("write");
 	}
 	
 	public function __destruct(){
		if(!($this->taxonCon === null)) $this->taxonCon->close();
	}
 	
 	public function setTid($t){
		$this->tid = $t;
		$sql = "SELECT t.TID, ts.family, t.SciName, t.Author, t.RankId, ts.ParentTID, t.SecurityStatus, ts.TidAccepted ". 
			"FROM taxstatus ts INNER JOIN taxa t ON ts.tid = t.TID ".
			"WHERE (ts.taxauthid = 1) AND t.TID = ".$this->tid;
		$result = $this->taxonCon->query($sql);
		if($row = $result->fetch_object()){
			if($row->TID == $row->TidAccepted){
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
				$resultNew->close();
			}
		}
	    else{
	    	$this->sciName = "unknown";
	    }
	    $result->close();
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
		$result->close();
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
		$result->close();
 		return $synArr;
 	}
 	
	public function editSynonymSort($synSort){
		$status = "";
		foreach($synSort as $editKey => $editValue){
			$sql = "UPDATE taxstatus SET SortSequence = ".$editValue." WHERE tid = ".$editKey." AND TidAccepted = ".$this->tid;
			//echo $sql."<br>";
			if(!$this->taxonCon->query($sql)){
				$status .= $this->taxonCon->error."\nSQL: ".$sql.";<br/> ";
			}
		}
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
		$result->close();
		return $vernArr;
	}
	
	public function editVernacular($inArray){
		$editArr = $this->cleanArray($inArray);
		$vid = $editArr["vid"];
		unset($editArr["vid"]);
		$setFrag = "";
		foreach($editArr as $keyField => $value){
			$setFrag .= ",".$keyField." = \"".$value."\" ";
		}
		$sql = "UPDATE taxavernaculars SET ".substr($setFrag,1)." WHERE VID = ".$vid;
		//echo $sql;
		$status = "";
		if(!$this->taxonCon->query($sql)){
			$status = "Error:editingVernacular: ".$this->taxonCon->error."\nSQL: ".$sql;
		}
		return $status;
	}
	
	public function addVernacular($inArray){
		$newVerns = $this->cleanArray($inArray);
		$sql = "INSERT INTO taxavernaculars (tid,".implode(",",array_keys($newVerns)).") VALUES (".$this->getTid().",\"".implode("\",\"",$newVerns)."\")";
		//echo $sql;
		$status = "";
		if(!$this->taxonCon->query($sql)){
			$status = "Error:addingNewVernacular: ".$this->taxonCon->error."\nSQL: ".$sql;
		}
		return $status;
	}
	
	public function deleteVernacular($delVid){
		$sql = "DELETE FROM taxavernaculars WHERE VID = ".$delVid;
		//echo $sql;
		$status = "";
		if(!$this->taxonCon->query($sql)){
			$status = "Error:deleteVernacular: ".$this->taxonCon->error."\nSQL: ".$sql;
		}
		else{
			$status = "";
		}
		return $status;
	}

	public function getDescriptions(){
		$descrArr = Array();
		$sql = "SELECT tdb.tdbid, tdb.displaylevel, tdb.language, tdb.notes, tdb.source, ".
			"tds.tdsid, tds.heading, tds.statement, tds.notes as stmtnotes, tds.displayheader, tds.sortsequence ".
			"FROM (taxstatus ts INNER JOIN taxadescrblock tdb ON ts.TidAccepted = tdb.tid) ".
			"LEFT JOIN taxadescrstmts tds ON tdb.tdbid = tds.tdbid ".
			"WHERE (tdb.tid = $this->tid) AND (ts.taxauthid = 1) ";
		if($this->language) $sql .=	"AND (tdb.Language = '".$this->language."') ";
		$sql .=	"ORDER BY tdb.Language, tdb.DisplayLevel, tds.SortSequence";
		//echo $sql;
		$result = $this->taxonCon->query($sql);
		$prevTdbid = 0;
		while($row = $result->fetch_object()){
			$tdbid = $row->tdbid;
			if($tdbid != $prevTdbid){
				$descrArr[$row->language][$row->displaylevel]["tdbid"] = $tdbid;
				$descrArr[$row->language][$row->displaylevel]["notes"] = $row->notes;
				$descrArr[$row->language][$row->displaylevel]["source"] = $row->source;
			}
			if($tdsid = $row->tdsid){
				$descrArr[$row->language][$row->displaylevel]["stmts"][$tdsid]["heading"] = $row->heading;
				$descrArr[$row->language][$row->displaylevel]["stmts"][$tdsid]["statement"] = $row->statement;
				$descrArr[$row->language][$row->displaylevel]["stmts"][$tdsid]["notes"] = $row->stmtnotes;
				$descrArr[$row->language][$row->displaylevel]["stmts"][$tdsid]["displayheader"] = $row->displayheader;
				$descrArr[$row->language][$row->displaylevel]["stmts"][$tdsid]["sortsequence"] = $row->sortsequence;
			}
			$prevTdbid = $tdbid;
		}
		$result->close();
		return $descrArr;
	}
	
	public function editDescriptionBlock(){
		$sql = "UPDATE taxadescrblock ".
			"SET language = ".($_REQUEST["language"]?"\"".$_REQUEST["language"]."\"":"NULL").
			",displaylevel = ".$_REQUEST["displaylevel"].
			",notes = ".($_REQUEST["notes"]?"\"".$_REQUEST["notes"]."\"":"NULL").
			",source = ".($_REQUEST["source"]?"\"".$_REQUEST["source"]."\"":"NULL").
			" WHERE tdbid = ".$_REQUEST["tdbid"];
		//echo $sql;
		$status = "";
		if(!$this->taxonCon->query($sql)){
			$status = "ERROR editing description block: ".$this->taxonCon->error;
			//$status .= "\nSQL: ".$sql;
		}
		return $status;
	}

	public function deleteDescriptionBlock(){
		$sql = "DELETE FROM taxadescrblock WHERE tdbid = ".$_REQUEST["tdbid"];
		//echo $sql;
		$status = "";
		if(!$this->taxonCon->query($sql)){
			$status = "ERROR deleting description block: ".$this->taxonCon->error;
			//$status .= "\nSQL: ".$sql;
		}
		return $status;
	}

	public function addDescriptionBlock(){
		global $symbUid;
		$sql = "INSERT INTO taxadescrblock(tid,uid,".($_REQUEST["language"]?"language,":"").($_REQUEST["displaylevel"]?"displaylevel,":"")."notes,source) ".
			"VALUES(".$_REQUEST["tid"].",".$symbUid.",".($_REQUEST["language"]?"\"".$_REQUEST["language"]."\",":"").
			($_REQUEST["displaylevel"]?$_REQUEST["displaylevel"].",":"").
			($_REQUEST["notes"]?"\"".$_REQUEST["notes"]."\",":"NULL,").
			($_REQUEST["source"]?"\"".$_REQUEST["source"]."\"":"NULL").")";
		//echo $sql;
		$status = "";
		if(!$this->taxonCon->query($sql)){
			$status = "ERROR adding description block: ".$this->taxonCon->error;
			//$status .= "\nSQL: ".$sql;
		}
		return $status;
	}

	public function editStatement(){
		$sql = "UPDATE taxadescrstmts ".
			"SET heading = \"".$_REQUEST["heading"]."\",".
			"statement = \"".$_REQUEST["statement"]."\"".
			(array_key_exists("displayheader",$_REQUEST)?",displayheader = 1":",displayheader = 0").
			($_REQUEST["sortsequence"]?",sortsequence = ".$_REQUEST["sortsequence"]:"").
			" WHERE tdsid = ".$_REQUEST["tdsid"];
		//echo $sql;
		$status = "";
		if(!$this->taxonCon->query($sql)){
			$status = "ERROR editing description statement: ".$this->taxonCon->error;
			//$status .= "\nSQL: ".$sql;
		}
		return $status;
	}

	public function deleteStatement(){
		$sql = "DELETE FROM taxadescrstmts WHERE tdsid = ".$_REQUEST["tdsid"];
		//echo $sql;
		$status = "";
		if(!$this->taxonCon->query($sql)){
			$status = "ERROR deleting description statement: ".$this->taxonCon->error;
			//$status .= "\nSQL: ".$sql;
		}
		return $status;
	}

	public function addStatement(){
		$sql = "INSERT INTO taxadescrstmts(tdbid,heading,statement,displayheader".($_REQUEST["sortsequence"]?",sortsequence":"").") ".
			"VALUES(".$_REQUEST["tdbid"].",\"".$_REQUEST["heading"]."\",\"".$_REQUEST["statement"]."\",".
			(array_key_exists("displayheader",$_REQUEST)?"1":"0").
			($_REQUEST["sortsequence"]?",".$_REQUEST["sortsequence"]:"").")";
		//echo $sql;
		$status = "";
		if(!$this->taxonCon->query($sql)){
			$status = "ERROR adding description statement: ".$this->taxonCon->error;
			//$status .= "\nSQL: ".$sql;
		}
		return $status;
	}

	public function getImages(){
		$imageArr = Array();
		$sql = "SELECT DISTINCT ti.imgid, ti.url, ti.thumbnailurl, ti.originalurl, ti.photographer, ti.photographeruid, ".
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
			$imageArr[$imgCnt]["originalurl"] = $row->originalurl;
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
		$result->close();
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

	public function editImage(){
		$searchStr = $GLOBALS["imageRootUrl"];
		if(substr($searchStr,-1) != "/") $searchStr .= "/";
		$replaceStr = $GLOBALS["imageRootPath"];
		if(substr($replaceStr,-1) != "/") $replaceStr .= "/";
		$status = "";
		$imgId = $_REQUEST["imgid"];
	 	$url = $_REQUEST["url"];
	 	$tnUrl = $_REQUEST["thumbnailurl"];
	 	$origUrl = $_REQUEST["originalurl"];
	 	if(array_key_exists("renameweburl",$_REQUEST)){
	 		$oldUrl = $_REQUEST["oldurl"];
	 		$oldName = str_replace($searchStr,$replaceStr,$oldUrl);
	 		$newName = str_replace($searchStr,$replaceStr,$url);
	 		if($url != $oldUrl){
	 			if(!rename($oldName,$newName)){
	 				$url = $oldUrl;
		 			$status .= "Web URL rename FAILED; url address unchanged";
	 			}
	 		}
		}
		if(array_key_exists("renametnurl",$_REQUEST)){
	 		$oldTnUrl = $_REQUEST["oldthumbnailurl"];
	 		$oldName = str_replace($searchStr,$replaceStr,$oldTnUrl);
	 		$newName = str_replace($searchStr,$replaceStr,$tnUrl);
	 		if($tnUrl != $oldTnUrl){
	 			if(!rename($oldName,$newName)){
	 				$tnUrl = $oldTnUrl;
		 			$status .= "Thumbnail URL rename FAILED; url address unchanged";
	 			}
	 		}
		}
		if(array_key_exists("renameorigurl",$_REQUEST)){
	 		$oldOrigUrl = $_REQUEST["oldoriginalurl"];
	 		$oldName = str_replace($searchStr,$replaceStr,$oldOrigUrl);
	 		$newName = str_replace($searchStr,$replaceStr,$origUrl);
	 		if($origUrl != $oldOrigUrl){
	 			if(!rename($oldName,$newName)){
	 				$origUrl = $oldOrigUrl;
		 			$status .= "Thumbnail URL rename FAILED; url address unchanged";
	 			}
	 		}
		}
	 	$caption = $this->cleanStr($_REQUEST["caption"]);
		$photographer = $this->cleanStr($_REQUEST["photographer"]);
		$photographerUid = $_REQUEST["photographeruid"];
		$owner = $this->cleanStr($_REQUEST["owner"]);
		$locality = $this->cleanStr($_REQUEST["locality"]);
		$occId = $_REQUEST["occid"];
		$notes = $this->cleanStr($_REQUEST["notes"]);
		$sourceUrl = $this->cleanStr($_REQUEST["sourceurl"]);
		$copyRight = $this->cleanStr($_REQUEST["copyright"]);
		$anatomy = $this->cleanStr($_REQUEST["anatomy"]);
		$imageType = $_REQUEST["imagetype"];
		$sortSequence = (array_key_exists("sortsequence",$_REQUEST)?$_REQUEST["sortsequence"]:0);
		$addToTid = (array_key_exists("addtoparent",$_REQUEST)?$this->parentTid:0);
		if(array_key_exists("addtotid",$_REQUEST)){
			$addToTid = $_REQUEST["addtotid"];
		}
		
		$sql = "UPDATE images SET caption = \"".$caption."\", url = \"".$url."\", thumbnailurl = \"".$tnUrl."\", ".
			"originalurl = \"".$origUrl."\", photographer = ".($photographer?"\"".$photographer."\"":"NULL").", ".
			"photographeruid = ".($photographerUid?$photographerUid:"NULL").", owner = \"".$owner."\", sourceurl = \"".$sourceUrl."\", ".
			"copyright = \"".$copyRight."\", locality = \"".$locality."\", occid = ".($occId?$occId:"NULL").", anatomy = \"".$anatomy."\", ".
			"imagetype = \"".$imageType."\", notes = \"".$notes."\", sortsequence = ".$sortSequence." ".
			" WHERE imgid = ".$imgId;
		//echo $sql;
		if($this->taxonCon->query($sql)){
			$this->setPrimaryImageSort($this->tid);
			if($addToTid){
				$sql = "INSERT INTO images (tid, url, thumbnailurl, originalurl, photographer, photographeruid, imagetype, caption, ".
					"owner, sourceurl, copyright, locality, occid, notes, anatomy) ".
					"VALUES (".$addToTid.",\"".$url."\",\"".$tnUrl."\",\"".$origUrl."\",".
					($photographer?"\"".$photographer."\"":"NULL").",".$photographerUid.",\"".$imageType."\",\"".$caption."\",\"".
					$owner."\",\"".$sourceUrl."\",\"".$copyRight."\",\"".$locality."\",".($occId?$occId:"NULL").",\"".$notes."\",\"".
					$anatomy."\")";
				//echo $sql;
				if($this->taxonCon->query($sql)){
					$this->setPrimaryImageSort($addToTid);
				}
				else{
					$status = "unable to upload image for related taxon";
					//$status = "Error:editImage:loading the parent data: ".$this->taxonCon->error."<br/>SQL: ".$sql;
				}
			}
		}
		else{
			$status = "Error:editImage: ".$this->taxonCon->error."\nSQL: ".$sql;
		}
		return $status;
	}
	
	public function changeTaxon($imgId,$targetTid,$sourceTid){
		$sql = "UPDATE images SET tid = $targetTid, sortsequence = 50 WHERE imgid = $imgId";
		if($this->taxonCon->query($sql)){
			//$sql2 = "DELETE FROM images WHERE tid = $sourceTid AND url = '".."'";
			//$this->taxonCon->query($sql2);
		}
		$this->setPrimaryImageSort($this->tid);
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
		foreach($imgSortEdits as $editKey => $editValue){
			$sql = "UPDATE images SET sortsequence = ".$editValue." WHERE imgid = ".$editKey.";";
			//echo $sql;
			if(!$this->taxonCon->query($sql)){
				$status .= $this->taxonCon->error."\nSQL: ".$sql."; ";
			}
		}
		$this->setPrimaryImageSort($this->tid);
		if($status) $status = "with editImageSort method: ".$status;
		return $status;
	}
	
	public function deleteImage($imgIdDel, $removeImg){
		$imgUrl = ""; $imgThumbnailUrl = ""; $imgOriginalUrl = "";
		$sqlQuery = "SELECT ti.url, ti.thumbnailurl, ti.originalurl FROM images ti WHERE ti.imgid = ".$imgIdDel;
		$result = $this->taxonCon->Query($sqlQuery);
		if($row = $result->fetch_object()){
			$imgUrl = $row->url;
			$imgThumbnailUrl = $row->thumbnailurl;
			$imgOriginalUrl = $row->originalurl;
		}
		$result->close();
				
		$sql = "DELETE FROM images WHERE imgid = ".$imgIdDel;
		//echo $sql;
		$status = "";
		if($this->taxonCon->query($sql)){
			if($removeImg){
				//Remove images only if there are no other references to the image
				$sql = "SELECT imgid FROM images WHERE url = '".$imgUrl."'";
				$rs = $this->taxonCon->query($sql);
				if(!$rs->num_rows){
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
					$imgOriginalDelPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgOriginalUrl);
					if(file_exists($imgOriginalDelPath)){
						unlink($imgOriginalDelPath);
					}
				}
			}
		}
		else{
			$status = "deleteImage: ".$this->taxonCon->error."\nSQL: ".$sql;
		}
		$this->setPrimaryImageSort($this->tid);
		return $status;
	}
	
	public function loadImageData(){
		global $paramsArr;
		$imgUrl = (array_key_exists("filepath",$_REQUEST)?$_REQUEST["filepath"]:"");
		$imgPath = "";
		if(!$imgUrl){
			$imgPath = $this->loadImage();
			$imgUrl = str_replace($this->imageRootPath,$this->imageRootUrl,$imgPath);
		}
		if(!$imgUrl) return;
		$caption = $this->cleanStr($_REQUEST["caption"]);
		$photographer = (array_key_exists("photographer",$_REQUEST)?$this->cleanStr($_REQUEST["photographer"]):"");
		$photographerUid = $_REQUEST["photographeruid"];
		$sourceUrl = (array_key_exists("sourceurl",$_REQUEST)?trim($_REQUEST["sourceurl"]):"");
		$copyRight = $this->cleanStr($_REQUEST["copyright"]);
		$owner = $this->cleanStr($_REQUEST["owner"]);
		$locality = (array_key_exists("locality",$_REQUEST)?$this->cleanStr($_REQUEST["locality"]):"");
		$occId = $_REQUEST["occid"];
		$notes = (array_key_exists("notes",$_REQUEST)?$this->cleanStr($_REQUEST["notes"]):"");
		$anatomy = (array_key_exists("anatomy",$_REQUEST)?$this->cleanStr($_REQUEST["anatomy"]):"");
		$imageType = $_REQUEST["imagetype"];
		$sortSequence = $_REQUEST["sortsequence"];
		$addToTid = (array_key_exists("addtoparent",$_REQUEST)?$this->parentTid:0);
		if(array_key_exists("addtotid",$_REQUEST)){
			$addToTid = $_REQUEST["addtotid"];
		}
		$userName = $paramsArr["un"];
		
		$imgTnUrl = $this->createImageThumbnail($imgUrl);

		$imgWebUrl = $imgUrl;
		$imgLgUrl = "";
		if(strpos($imgUrl,"http://") === false || strpos($imgUrl,$this->imageRootUrl) !== false){
			//Create Large Image
			list($width, $height) = getimagesize($imgPath?$imgPath:$imgUrl);
			$fileSize = filesize($imgPath?$imgPath:$imgUrl);
			if($width > ($this->webPixWidth*1.2) || $fileSize > $this->webFileSizeLimit){
				$lgWebUrlTemp = str_ireplace("_temp.jpg","lg.jpg",$imgPath); 
				if($width < ($this->lgPixWidth*1.2)){
					if(copy($imgPath,$lgWebUrlTemp)){
						$imgLgUrl = str_ireplace($this->imageRootPath,$this->imageRootUrl,$lgWebUrlTemp);
					}
				}
				else{
					if($this->createNewImage($imgPath,$lgWebUrlTemp,$this->lgPixWidth)){
						$imgLgUrl = str_ireplace($this->imageRootPath,$this->imageRootUrl,$lgWebUrlTemp);
					}
				}
			}

			//Create web url
			$imgTargetPath = str_ireplace("_temp.jpg",".jpg",$imgPath);
			if($width < ($this->webPixWidth*1.2) && $fileSize < $this->webFileSizeLimit){
				rename($imgPath,$imgTargetPath);
			}
			else{
				$newWidth = ($width<($this->webPixWidth*1.2)?$width:$this->webPixWidth);
				$this->createNewImage($imgPath,$imgTargetPath,$newWidth);
			}
			$imgWebUrl = str_ireplace($this->imageRootPath,$this->imageRootUrl,$imgTargetPath);
			if(file_exists($imgPath)) unlink($imgPath);
		}
			
		if($imgWebUrl){
			$sql = "INSERT INTO images (tid, url, thumbnailurl, originalurl, photographer, photographeruid, imagetype, caption, ".
				"owner, sourceurl, copyright, locality, occid, notes, anatomy, username, sortsequence) ".
				"VALUES (".$this->tid.",\"".$imgWebUrl."\",".($imgTnUrl?"\"".$imgTnUrl."\"":"NULL").",".($imgLgUrl?"\"".$imgLgUrl."\"":"NULL").",".
				($photographer?"\"".$photographer."\"":"NULL").",".$photographerUid.",\"".
				$imageType."\",\"".$caption."\",\"".$owner."\",\"".$sourceUrl."\",\"".$copyRight."\",\"".$locality."\",".
				($occId?$occId:"NULL").",\"".$notes."\",\"".
				$anatomy."\",\"".$userName."\",".($sortSequence?$sortSequence:"50").")";
			echo $sql;
			$status = "";
			if($this->taxonCon->query($sql)){
				$this->setPrimaryImageSort($this->tid);
				if($addToTid){
					$sql = "INSERT INTO images (tid, url, thumbnailurl, originalurl, photographer, photographeruid, imagetype, caption, ".
						"owner, sourceurl, copyright, locality, occid, notes, anatomy, username, sortsequence) ". 
						"VALUES (".$addToTid.",\"".$imgWebUrl."\",".($imgTnUrl?"\"".$imgTnUrl."\"":"NULL").",".($imgLgUrl?"\"".$imgLgUrl."\"":"NULL").",".
						($photographer?"\"".$photographer."\"":"NULL").",".$photographerUid.",\"".
						$imageType."\",\"".$caption."\",\"".$owner."\",\"".$sourceUrl."\",\"".$copyRight."\",\"".$locality."\",".
						($occId?$occId:"NULL").",\"".$notes."\",\"".
						$anatomy."\",\"".$userName."\")";
					//echo $sql;
					if($this->taxonCon->query($sql)){
						$this->setPrimaryImageSort($addToTid);
					}
					else{
						$status = "Error: unable to upload image for related taxon";
						//$status = "Error:loadImageData:loading the parent data: ".$this->taxonCon->error."<br/>SQL: ".$sql;
					}
				}
			}
			else{
				$status = "loadImageData: ".$this->taxonCon->error."<br/>SQL: ".$sql;
			}
		}
		return $status;
	}
	
	private function loadImage(){
	 	$userFile = basename($_FILES['userfile']['name']);
		$fileName = $this->getFileName($userFile);
	 	$downloadPath = $this->getDownloadPath($fileName, $_REQUEST["imagetype"]); 
	 	if(move_uploaded_file($_FILES['userfile']['tmp_name'], $downloadPath)){
			return $downloadPath;
	 	}
	 	return;
	}

	private function getFileName($fName){
		$fName = str_replace("'","",$fName);
		$fName = str_replace(" ","_",$fName);
		$fName = str_replace("\"","",$fName);
		if(strlen($fName) > 30) {
			$fName = substr($fName,0,25).substr($fName,strrpos($fName,"."));
		}
 		return $fName;
 	}
 	
	private function getDownloadPath($fileName, $subFolder){
		if(substr($this->imageRootPath,-1,1) != "/") $this->imageRootPath .= "/";
		$path = $this->imageRootPath.$this->family."/".$subFolder."/";
 		if(!file_exists($this->imageRootPath.$this->family)){
 			mkdir($this->imageRootPath.$this->family, 0775);
 		}
 		if(!file_exists($this->imageRootPath.$this->family."/".$subFolder)){
 			mkdir($this->imageRootPath.$this->family."/".$subFolder, 0775);
 		}
 		//Check and see if file already exists, if so, rename filename until it has a unique name
 		$tempFileName = $fileName;
 		$cnt = 0;
 		while(file_exists($path.$tempFileName)){
 			$tempFileName = str_ireplace(".jpg","_".$cnt.".jpg",$fileName);
 			$cnt++;
 		}
 		$fileName = str_ireplace(".jpg","_temp.jpg",$tempFileName);
 		return $path.$fileName;
 	}

	private function createImageThumbnail($imgUrl){
		$newThumbnailUrl = "";
		if($imgUrl){
			$imgPath = "";
			$newThumbnailUrl = "";
			$newThumbnailPath = "";
			if(strpos($imgUrl,"http://") === 0 && strpos($imgUrl,$this->imageRootUrl) === false){
				$imgPath = $imgUrl;
				if(!is_dir($this->imageRootPath."misc_thumbnails/")){
					if(!mkdir($this->imageRootPath."misc_thumbnails/", 0775)) return "";
				}
				$fileName = str_ireplace("_temp.jpg","tn.jpg",substr($imgUrl,strrpos($imgUrl,"/")));
				$newThumbnailPath = $this->imageRootPath."misc_thumbnails/".$fileName;
				$newThumbnailUrl = $this->imageRootUrl."misc_thumbnails/".$fileName;
			}
			elseif(strpos($imgUrl,$this->imageRootUrl) === 0){
				$imgPath = str_replace($this->imageRootUrl,$this->imageRootPath,$imgUrl);
				$newThumbnailUrl = str_ireplace("_temp.jpg","tn.jpg",$imgUrl);
				$newThumbnailPath = str_replace($this->imageRootUrl,$this->imageRootPath,$newThumbnailUrl);
			}
			if(!$newThumbnailUrl) return "";
			if(file_exists($imgPath) || ($imgUrl && $this->url_exists($imgUrl))){
				if(!file_exists($newThumbnailPath)){
					$this->createNewImage($imgPath,$newThumbnailPath,$this->tnPixWidth,50);
				}
			}
		}
		return $newThumbnailUrl;
	}
	
	private function createNewImage($sourceImg,$targetPath,$targetWidth,$qualityRating = 60){
        $successStatus = false;
		list($sourceWidth, $sourceHeight, $imageType) = getimagesize($sourceImg);
        $newWidth = $targetWidth;
        $newHeight = round($sourceHeight*($targetWidth/$sourceWidth));
        if($newHeight > $targetWidth*1.2){
        	$newHeight = $targetWidth;
        	$newWidth = round($sourceWidth*($targetWidth/$sourceHeight));
        }
        
	    switch ($imageType){
	        case 1: 
	        	$newImg = imagecreatefromgif($sourceImg);
	        	break;
	        case 2: 
	        	$newImg = imagecreatefromjpeg($sourceImg);  
	        	break;
	        case 3: 
	        	$newImg = imagecreatefrompng($sourceImg);
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
		imagecopyresampled($tmpImg,$newImg,0,0,0,0,$newWidth, $newHeight,$sourceWidth,$sourceHeight);

		switch ($imageType){
	        case 1: 
	        	$successStatus = imagegif($tmpImg,$targetPath);
	        	break;
	        case 2: 
	        	$successStatus = imagejpeg($tmpImg, $targetPath, $qualityRating);
	        	break; // best quality
	        case 3: 
	        	$successStatus = imagepng($tmpImg, $targetPath, 0);
	        	break; // no compression
	    }
	    imagedestroy($tmpImg);
	    return $successStatus;
	}

	public function getChildrenArr($url = ""){
		$returnArr = Array();
		$sql = "SELECT t.tid, t.sciname FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid) ";
		if($url) $sql .= "LEFT JOIN (SELECT i2.tid FROM images i2 WHERE i2.url = '".$url."' ) i ON t.tid = i.tid "; 
		$sql .= "WHERE ts.taxauthid = 1 AND ts.parenttid = ".$this->tid;
		if($url) $sql .= " AND i.tid IS NULL ";
		//echo $sql;
		$result = $this->taxonCon->query($sql);
		while($row = $result->fetch_object()){
			$returnArr[$row->tid] = $row->sciname;
		}
		$result->close();
		return $returnArr;
	}
	
	private function setPrimaryImageSort($subjectTid){
		$sql1 = "SELECT count(ti.imgid) AS reccnt FROM images ti INNER JOIN taxstatus ts ON ti.tid = ts.tid 
			WHERE ts.taxauthid = 1 AND ti.SortSequence = 1 AND ts.tidaccepted = ".$subjectTid;
		//echo $sql1;
		$result = $this->taxonCon->query($sql1);
		if($row = $result->fetch_object()){
			if($row->reccnt == 0){
				$sql2 = "UPDATE images ti2 INNER JOIN (SELECT ti.imgid, ti.sortsequence FROM images ti INNER JOIN taxstatus ts ON ti.tid = ts.tid
					WHERE ((ts.taxauthid = 1) AND (ts.tidaccepted=".$subjectTid.")) ORDER BY ti.SortSequence LIMIT 1) innertab ON ti2.imgid = innertab.imgid 
					SET ti2.SortSequence = 1";
				//echo $sql2;
				$this->taxonCon->query($sql2);
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
		$result->close();
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
 			$newArray[$this->cleanStr($key)] = $this->cleanStr($value);
 		}
 		return $newArray;
 	}
	
 	private function cleanStr($str){
 		$newStr = trim($str);
 		$newStr = preg_replace('/\s\s+/', ' ',$newStr);
		$newStr = str_replace("\"","'",$newStr);
 		return $newStr;
 	}
	
 	private function url_exists($url) {
	    if(!strstr($url, "http://")){
	        $url = "http://".$url;
	    }

	    $fp = @fsockopen($url, 80);

    	if($fp === false){
	        return false;   
    	}
    	return true;
    	
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

