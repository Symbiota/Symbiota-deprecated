<?php
/*
 * Rebuilt 29 Jan 2010
 * By E.E. Gilbert
 */
	include_once('../config/symbini.php');
	include_once($serverRoot.'/config/dbconnection.php');
	header("Content-Type: text/html; charset=".$charset);
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	$clValue = $_REQUEST["cl"]; 
	$pageNumber = array_key_exists("pagenumber",$_REQUEST)?$_REQUEST["pagenumber"]:0;
	$symClid = array_key_exists("symclid",$_REQUEST)?$_REQUEST["symclid"]:"0"; 
 	$proj = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:"";
 	$thesFilter = array_key_exists("thesfilter",$_REQUEST)?$_REQUEST["thesfilter"]:0; 
 	$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:""; 
 	$showAuthors = array_key_exists("showauthors",$_REQUEST)?$_REQUEST["showauthors"]:0; 
 	$showCommon = array_key_exists("showcommon",$_REQUEST)?$_REQUEST["showcommon"]:0; 
 	$showImages = array_key_exists("showimages",$_REQUEST)?$_REQUEST["showimages"]:0; 
 	$showVouchers = array_key_exists("showvouchers",$_REQUEST)?$_REQUEST["showvouchers"]:0; 
 	$searchCommon = array_key_exists("searchcommon",$_REQUEST)?$_REQUEST["searchcommon"]:0;
 	$searchSynonyms = array_key_exists("searchsynonyms",$_REQUEST)?$_REQUEST["searchsynonyms"]:1;
 	$editMode = array_key_exists("emode",$_REQUEST)?$_REQUEST["emode"]:""; 
 	 	
 	$clManager = new ChecklistManager();
 	$clManager->setClValue($clValue);
 	if($thesFilter) $clManager->setThesFilter($thesFilter);
 	if($taxonFilter) $clManager->setTaxonFilter($taxonFilter);
 	if($searchCommon){
 		$showCommon = 1;
 		$clManager->setSearchCommon();
 	}
 	if($searchSynonyms) $clManager->setSearchSynonyms();
 	if($showAuthors) $clManager->setShowAuthors();
 	if($showCommon) $clManager->setShowCommon();
 	if($showImages) $clManager->setShowImages();
 	if($showVouchers) $clManager->setShowVouchers();
 	
	if($isAdmin || (array_key_exists("ClAdmin",$userRights) && in_array($clManager->getClid(),$userRights["ClAdmin"]))){
		$clManager->setEditable(true);
		
		//Submit checklist MetaData edits
		$action = array_key_exists("editsubmit",$_REQUEST)?$_REQUEST["editsubmit"]:"";
	 	if($action == "Submit Changes"){
	 		$editArr = Array();
			foreach($_REQUEST as $k => $v){
				if(substr($k,0,3) == "ecl"){
					$editArr[substr($k,3)] = $_REQUEST[$k];
				}
			}
	 		$clManager->editMetaData($editArr);
	 	}
	 	
	 	//Add species to checklist
		if(array_key_exists("tidtoadd",$_REQUEST)){
			$dataArr["tid"] = $_REQUEST["tidtoadd"];
			if($_REQUEST["familyoverride"]) $dataArr["familyoverride"] = $_REQUEST["familyoverride"];
			if($_REQUEST["habitat"]) $dataArr["habitat"] = $_REQUEST["habitat"];
			if($_REQUEST["abundance"]) $dataArr["abundance"] = $_REQUEST["abundance"];
			if($_REQUEST["notes"]) $dataArr["notes"] = $_REQUEST["notes"];
			if($_REQUEST["source"]) $dataArr["source"] = $_REQUEST["source"];
			if($_REQUEST["internalnotes"]) $dataArr["internalnotes"] = $_REQUEST["internalnotes"];
			$clManager->addNewSpecies($dataArr);
		}
	}
 	$clArray = $clManager->getClMetaData();
	$taxaArray = $clManager->getTaxaList($pageNumber);
 ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en_US" xml:lang="en_US">

 <head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
	<title><?php echo $defaultTitle; ?> Research Checklist: <?php echo $clManager->getClName(); ?></title>
	<link rel="stylesheet" href="../css/main.css" type="text/css"/>
	<?php
		$keywordStr = "virtual flora,species list,".$clManager->getClName();
		if($clArray["authors"]) $keywordStr .= ",".$clArray["authors"];
		if($proj) $keywordStr .= ",".$proj;
		echo"<meta name='keywords' content='".$keywordStr."' />";
	?>
    <link rel="stylesheet" href="../css/jqac.css" type="text/css" />
	<link type="text/css" href="../css/ui.tabs.css" rel="stylesheet" />
	<link type="text/css" href="http://jqueryui.com/latest/themes/base/jquery.ui.all.css" rel="stylesheet" />
	
	<script type="text/javascript" src="../js/jquery-1.3.2.min.js"></script>
	<script type="text/javascript" src="../js/jquery.autocomplete-1.4.2.js"></script>
	<script type="text/javascript" src="../js/AutoCompleteDB.js"></script>
	<script type="text/javascript" src="../js/ui.core.js"></script>
	<script type="text/javascript" src="../js/ui.tabs.js"></script>
	<script language=javascript>
		var rtXmlHttp;
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
		  	for (var i = 0; i < spans.length; i++) {
		  	var spanObj = spans[i];
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

		function openPointMap() {
		    mapWindow=open("tools/mappointaid.php?formid=checklisteditform","mappointaid","resizable=0,width=800,height=700,left=20,top=20");
		    if (mapWindow.opener == null) mapWindow.opener = self;
		}

		function openPopup(urlStr,windowName){
			newWindow = window.open(urlStr,windowName,'toolbar=1,resizable=1,width=650,height=600,left=20,top=20');
			if (newWindow.opener == null) newWindow.opener = self;
		}
	
		function removeTaxon(tid, clid, sciName){
	        if(window.confirm('Are you sure you want to delete this taxon?')){
				rtXmlHttp = GetXmlHttpObject();
				if (rtXmlHttp==null){
			  		alert ("Your browser does not support AJAX!");
			  		return;
			  	}
				sciNameDeletion = sciName;
				var url = "rpc/removetidfromchklst.php";
				url=url + "?clid=" + clid + "&tid=" + tid;
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
		
		function showImagesChecked(cbObj){
			if(cbObj.checked){
				document.getElementById("showvouchers").checked = false;
				document.getElementById("showvouchersdiv").style.display = "none"; 
			}
			else{
				document.getElementById("showvouchersdiv").style.display = "block"; 
			}
		}

		function validateMetadataForm(f){ 
			if(f.ecllatcentroid.value == "" && f.ecllongcentroid.value == ""){
				return true;
			}
			if(f.ecllatcentroid.value == ""){
				alert("If longitude has a value, latitude must also have a value");
				return false;
			} 
			if(f.ecllongcentroid.value == ""){
				alert("If latitude has a value, longitude must also have a value");
				return false;
			} 
			if(!isNumeric(f.ecllatcentroid.value)){
				alert("Latitude must be strictly numeric (decimal format: e.g. 34.2343)");
				return false;
			}
			if(Math.abs(f.ecllatcentroid.value) > 90){
				alert("Latitude values can not be greater than 90 or less than -90.");
				return false;
			} 
			if(!isNumeric(f.ecllongcentroid.value)){
				alert("Longitude must be strictly numeric (decimal format: e.g. -112.2343)");
				return false;
			}
			if(Math.abs(f.ecllongcentroid.value) > 180){
				alert("Longitude values can not be greater than 180 or less than -180.");
				return false;
			}
			if(f.ecllongcentroid.value > 1){
				alert("Is this checklist in the western hemisphere?\nIf so, decimal longitude should be a negative value (e.g. -112.2343)");
			} 
			if(!isNumeric(f.eclpointradiusmeters.value)){
				alert("Point radius must be a numeric value only");
				return false;
			}
			return true;
		}
		
		function isNumeric(sText){
		   	var ValidChars = "0123456789-.";
		   	var IsNumber = true;
		   	var Char;
		 
		   	for (var i = 0; i < sText.length && IsNumber == true; i++){ 
			   Char = sText.charAt(i); 
				if (ValidChars.indexOf(Char) == -1){
					IsNumber = false;
					break;
	          	}
		   	}
			return IsNumber;
		}

		function validateAddSpecies(){ 
			var sciName = document.getElementById("speciestoadd").value;
			if(sciName = ""){
				alert("Enter the scientific name of species you wish to add");
				return false;
			}
			else{
				checkScinameExistance(sciName);
				return false;
			}
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
					alert("ERROR: Scientific name does not exist in database. Did you spell it correctly? If so, it may have to be added to taxa table.");
				}
				else{
					document.getElementById("tidtoadd").value = renameTid;
					document.forms["addspeciesform"].submit();
				}
			}
		}

		function initAddList(input){
			$(input).autocomplete({ ajax_get:getAddSuggs, minchars:3 });
		}

		function getAddSuggs(key,cont){ 
		   	var script_name = 'rpc/getspecies.php';
		   	var params = { 'q':key,'cl':'<?php echo $clManager->getClid();?>' }
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

		function initFilterList(input){
		    //process lookup list for fast access
			if(!db){
				db = new AutoCompleteDB();
				var taxonArr = new Array(<?php $clManager->echoFilterList();?>);
				var arLen=taxonArr.length;
				if(arLen > 0){
					$(input).autocomplete({ get:getFilterSuggs, minchars:1, timeout:10000 });
					for ( var i=0; i<arLen; ++i ){
						db.add(taxonArr[i]);
					}
				}
			}
		}

		function getFilterSuggs(v){ 
			// get all the matching strings from the AutoCompleteDB
			var matchArr = new Array();
			db.getStrings(v, "", matchArr);
			matchArr = matchArr.unique();
			// add each string to the popup-div
			var displayArr = new Array();
			for( i = 0; i < matchArr.length; i++ ){
				displayArr.push({id:i, value:matchArr[i] });
			}
			return displayArr;
		}
				
		$(document).ready(function(){
			$("#tabs").tabs();
		});

		Array.prototype.unique = function() {
			var a = [];
			var l = this.length;
		    for(var i=0; i<l; i++) {
				for(var j=i+1; j<l; j++) {
				if (this[i] === this[j]) j = ++i;
			}
			a.push(this[i]);
			}
			return a;
		};
	</script>
 </head>
 
 <body>
<?php
	$displayLeftMenu = (isset($checklists_checklistMenu)?$checklists_checklistMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($checklists_checklistCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $checklists_checklistCrumbs;
		echo " <b>".$clManager->getClName()."</b>"; 
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id='innertext'>
		<?php 
			if($clManager->getEditable()){
				?>
				<div style="float:right;cursor:pointer;" onclick="javascript:toggle('editspp');" title="Edit Species List">
					<img style="border:0px;width:12px;" src="../images/edit.png" /><br/>
					<span style="font-size:70%;">Spp.</span>
				</div>
				<div style="float:right;cursor:pointer;margin-right:10px;" onclick="javascript:toggle('editmd');" title="Edit MetaData">
					<img style="border:0px;width:12px;" src="../images/edit.png" /><br/>
					<span style="font-size:70%;">MD</span>
				</div>
				<?php 
			}
		?>
		<h1>
		<?php 
			echo $clManager->getClName()."&nbsp;&nbsp;";
			if($keyModIsActive){
				?>
				<a href="../ident/key.php?cl=<?php echo $clValue."&proj=".$proj;?>&taxon=All+Species">
					<img src='../images/key.jpg' style='width:15px;border:0px;' title='Open Symbiota Key' />
				</a>&nbsp;&nbsp;&nbsp;
				<?php 
			}
			?>
			<a href="flashcards.php?clid=<?php echo $clManager->getClid().($taxonFilter?"&taxonfilter=".$taxonFilter:"")."&showcommon=".$showCommon.($clManager->getThesFilter()?"&thesfilter=".$clManager->getThesFilter():"");?>">
				<img src="../images/quiz.jpg" style="height:10px;border:0px;" title="Open Flashcard Quiz" />
			</a>
		</h1>
		<div>
			<span style="font-weight:bold;">
				Authors: 
			</span>
			<?php echo $clArray["authors"]; ?>
		</div>
		<?php 
		if($clArray["publication"]){
			echo "<div><span style='font-weight:bold;'>Publication: </span>".$clArray["publication"]."</div>";
		}
	
		if($clArray["locality"] || $clArray["latcentroid"] || $clArray["abstract"] || $clArray["notes"]){
			echo "<div class=\"moredetails\" style=\"color:blue;cursor:pointer;\" onclick=\"toggle('moredetails')\">More Details</div>";
			echo "<div class='moredetails' style='display:none'>";
			$locStr = $clArray["locality"].($clArray["latcentroid"]?" (".$clArray["latcentroid"].", ".$clArray["longcentroid"].")":"");
			if($locStr){
				echo "<div><span style='font-weight:bold;'>Locality: </span>".$locStr."</div>";
			}
			if($clArray["abstract"]){
				echo "<div><span style='font-weight:bold;'>Abstract: </span>".$clArray["abstract"]."</div>";
			}
			if($clArray["notes"]){
				echo "<div><span style='font-weight:bold;'>Notes: </span>".$clArray["notes"]."</div>";
			}
			echo "</div>";
		}
		if($clManager->getEditable()){
	?>
		<!-- Checklist editing div  -->
		<div class="editmd" style="display:none;">
			<div id="tabs" style="margin:10px;height:500px;">
			    <ul>
			        <li><a href="#metadata"><span>Metadata</span></a></li>
			        <li><a href="#editors"><span>Editors</span></a></li>
			    </ul>
				<div id="metadata">
					<form id="checklisteditform" action="checklist.php" method="get" name="editclmatadata" onsubmit="return validateMetadataForm(this)">
						<fieldset style="margin:5px 0px 5px 5px;">
							<legend>Edit Checklist Details:</legend>
							<div>
								<span>Checklist Name: </span>
								<input type="text" name="eclname" size="80" value="<?php echo $clManager->getClName();?>" />
							</div>
							<div>
								<span>Authors: </span>
								<input type="text" name="eclauthors" value="<?php echo $clArray["authors"]; ?>" />
							</div>
							<div>
								<span>Locality: </span>
								<input type="text" name="ecllocality" size="80" value="<?php echo $clArray["locality"]; ?>" />
							</div> 
							<div>
								<span>Publication: </span>
								<input type="text" name="eclpublication" size="80" value="<?php echo $clArray["publication"]; ?>" />
							</div>
							<div>
								<span>Abstract: </span>
								<textarea name="eclabstract" cols="70" rows="3"><?php echo $clArray["abstract"]; ?></textarea>
							</div>
							<div>
								<span>Parent Checklist: </span>
								<select name="eclparentclid">
									<option value="">Select a Parent checklist</option>
									<option value="">----------------------------------</option>
									<?php $clManager->echoParentSelect(); ?>
								</select>
							</div>
							<div>
								<span>Notes: </span>
								<input type="text" name="eclnotes" size="80" value="<?php echo $clArray["notes"]; ?>" />
							</div>
							<div>
								<span>Latitude Centroid: </span>
								<input id="latdec" type="text" name="ecllatcentroid" value="<?php echo $clArray["latcentroid"]; ?>" />
								<span style="cursor:pointer;" onclick="openPointMap();">
									<img src="../images/world40.gif" style="width:12px;" />
								</span>
							</div>
							<div>
								<span>Longitude Centroid: </span>
								<input id="lngdec" type="text" name="ecllongcentroid" value="<?php echo $clArray["longcentroid"]; ?>" />
							</div>
							<div>
								<span>Point Radius (meters): </span>
								<input type="text" name="eclpointradiusmeters" value="<?php echo $clArray["pointradiusmeters"]; ?>" />
							</div>
							<div>
								<span>Public Access: </span>
								<select name="eclaccess">
									<option value="private">Private</option>
									<option value="public limited" <?php echo ($clArray["access"]=="public limited"?"selected":""); ?>>Public Limited</option>
								<?php if($isAdmin || $clArray["access"]=="public"){ ?>
									<option value="public" <?php echo ($clArray["access"]=="public"?"selected":""); ?>>Public Research Grade</option>
								<?php } ?>
								</select>
							</div>
							<div>
								<input type='submit' name='editsubmit' id='editsubmit' value='Submit Changes' />
							</div>
							<input type='hidden' name='cl' value='<?php echo $clManager->getClid(); ?>' />
							<input type='hidden' name='proj' value='<?php echo $proj; ?>' />
							<input type='hidden' name='showcommon' value='<?php echo $showCommon; ?>' />
							<input type='hidden' name='showvouchers' value='<?php echo $showVouchers; ?>' />
							<input type='hidden' name='showauthors' value='<?php echo $showAuthors; ?>' />
							<input type='hidden' name='thesfilter' value='<?php echo $clManager->getThesFilter(); ?>' />
							<input type='hidden' name='taxonfilter' value='<?php echo $taxonFilter; ?>' />
							<input type='hidden' name='searchcommon' value='<?php echo $searchCommon; ?>' />
						</fieldset>
					</form>
				</div>
				<div id="editors">
					Editors
					<div style="margin:10px;">
						<?php 
							$clManager->echoEditorList();
						
						?>
					</div>
				</div>
			</div>
		</div>
	<?php
		}
	?>
		<div>
			<hr/>
		</div>
		<div>
			<!-- Option box -->
			<div id="cloptiondiv">
				<form id='changetaxonomy' name='changetaxonomy' action='checklist.php' method='get'>
					<fieldset>
					    <legend>Options</legend>
						<!-- Taxon Filter option -->
					    <div id="taxonfilterdiv" title="Filter species list by family or genus">
					    	<div>
					    		<b>Search:</b> 
								<input type="text" name="taxonfilter" value="<?php echo $taxonFilter;?>" size="20" onfocus="initFilterList(this)" autocomplete="off" />
							</div>
							<div>
								<?php 
									if($displayCommonNames){
										echo "<input type='checkbox' name='searchcommon' value='1'".($searchCommon?"checked":"")."/> Common Names";
									}
								?>
								<input type="checkbox" name="searchsynonyms" value="1"<?php echo ($searchSynonyms?"checked":"");?>/> Synonyms
							</div>
						</div>
					    <!-- Thesaurus Filter -->
					    <div><b>Filter:</b>
					    	<select id='thesfilter' name='thesfilter'>
								<option value='0'>Original Checklist</option>
								<?php 
									$taxonAuthList = Array();
									$taxonAuthList = $clManager->getTaxonAuthorityList();
									foreach($taxonAuthList as $taCode => $taValue){
										echo "<option value='".$taCode."'".($taCode == $clManager->getThesFilter()?" selected":"").">".$taValue."</option>\n";
									}
								?>
							</select>
						</div>
						<div>
							<?php 
								//Display Common Names: 0 = false, 1 = true 
							    if($displayCommonNames) echo "<input id='showcommon' name='showcommon' type='checkbox' value='1' ".($showCommon?"checked":"")."/> Common Names";
							?>
						</div>
						<div>
							<!-- Display as Images: 0 = false, 1 = true  --> 
						    <input id='showimages' name='showimages' type='checkbox' value='1' <?php echo ($showImages?"checked":""); ?> onclick="showImagesChecked(this);" /> 
						    Display as Images
						</div>
						<div style='display:<?php echo ($showImages?"none":"block");?>' id="showvouchersdiv">
							<!-- Display as Vouchers: 0 = false, 1 = true  --> 
						    <input id='showvouchers' name='showvouchers' type='checkbox' value='1' <?php echo ($showVouchers?"checked":""); ?>/> 
						    Notes &amp; Vouchers
						</div>
						<div style="float:right;">
							<input type='hidden' name='cl' value='<?php echo $clManager->getClid(); ?>' />
							<?php if(!$taxonFilter) echo "<input type='hidden' name='pagenumber' value='".$pageNumber."' />"; ?>
							<input type="submit" name="optionsubmit" value="Rebuild List" />
						</div>
						<div>
							<!-- Display Taxon Authors: 0 = false, 1 = true  --> 
						    <input id='showauthors' name='showauthors' type='checkbox' value='1' <?php echo ($showAuthors?"checked":""); ?>/> 
						    Taxon Authors
						</div>
					</fieldset>
				</form>
			</div>
			<div>
				<h1>Species List</h1>
				<div style="margin:3px;">
					<b>Families:</b> 
					<?php echo $clManager->getFamilyCount(); ?>
				</div>
				<div style="margin:3px;">
					<b>Genera:</b>
					<?php echo $clManager->getGenusCount(); ?>
				</div>
				<div style="margin:3px;">
					<b>Species:</b>
					<?php echo $clManager->getSpeciesCount(); ?>
					(species rank)
				</div>
				<div style="margin:3px;">
					<b>Total Taxa:</b> 
					<?php echo $clManager->getTaxaCount(); ?>
					(including ssp. and var.)
				</div>
				<?php 
				$taxaLimit = ($showImages?$clManager->getImageLimit():$clManager->getTaxaLimit());
				$pageCount = ceil($clManager->getTaxaCount()/$taxaLimit);
				$argStr = "";
				if($pageCount > 1){
					if(($pageNumber+1)>$pageCount) $pageNumber = 0;  
					$argStr .= "&cl=".$clValue.($showCommon?"&showcommon=".$showCommon:"").($showVouchers?"&showvouchers=".$showVouchers:"");
					$argStr .= ($showAuthors?"&showauthors=".$showAuthors:"").($clManager->getThesFilter()?"&thesfilter=".$clManager->getThesFilter():"");
					$argStr .= ($proj?"&proj=".$proj:"").($showImages?"&showimages=".$showImages:"").($taxonFilter?"&taxonfilter=".$taxonFilter:"");
					$argStr .= ($searchCommon?"&searchcommon=".$searchCommon:"").($searchSynonyms?"&searchsynonyms=".$searchSynonyms:"");
					echo "<hr /><div>Page <b>".($pageNumber+1)."</b> of <b>$pageCount</b>: ";
					for($x=0;$x<$pageCount;$x++){
						if($x) echo " | ";
						if(($pageNumber) == $x){
							echo "<b>";
						}
						else{
							echo "<a href='checklist.php?pagenumber=".$x.$argStr."'>";
						}
						echo ($x+1);
						if(($pageNumber) == $x){
							echo "</b>";
						}
						else{
							echo "</a>";
						}
					}
					echo "</div><hr />";
				}

				if($clManager->getEditable()){
				?>
					<div class="editspp" style="display:none;width:250px;margin-bottom:15px;">
						<form id='addspeciesform' action='checklist.php' method='get' name='addspeciesform' onsubmit="return validateAddSpecies(this);">
							<fieldset style='margin:5px 0px 5px 5px;background-color:#FFFFCC;'>
								<legend>Add New Species to Checklist:</legend>
								<div style="clear:left">
									<div style="font-weight:bold;float:left;width:70px;">
										Taxon:
									</div>
									<div style="float:left;">
										<input type="text" id="speciestoadd" name="speciestoadd" onfocus="initAddList(this)" autocomplete="off" />
										<input type="hidden" id="tidtoadd" name="tidtoadd" value="" />
									</div>
								</div>
								<div style="clear:left;">
									<div style="font-weight:bold;float:left;width:100px;">
										Family Override: 
									</div>
									<div style="float:left;">
										<input type="text" name="familyoverride" size="15" title="Only enter if you want to override current family" />
									</div>
								</div>
								<div style="clear:left;">
									<div style="font-weight:bold;float:left;width:70px;">
										Habitat:
									</div>
									<div style="float:left;">
										<input type="text" name="habitat" />
									</div>
								</div>
								<div style="clear:left;">
									<div style="font-weight:bold;float:left;width:70px;">
										Abundance:
									</div>
									<div style="float:left;">
										<select name="abundance">
											<option value="">undefined</option>
											<option>abundant</option>
											<option>locally abundant</option>
											<option>seasonal abundant</option>
											<option>frequent</option>
											<option>locally frequent</option>
											<option>seasonal frequent</option>
											<option>occasional</option>
											<option>infrequent</option>
											<option>rare</option>
										</select>
									</div>
								</div>
								<div style="clear:left;">
									<div style="font-weight:bold;float:left;width:70px;">
										Notes:
									</div>
									<div style="float:left;">
										<input type="text" name="notes" />
									</div>
								</div>
								<div style="clear:left;padding-top:2px;">
									<div style="font-weight:bold;float:left;width:70px;">
										Internal Notes:
									</div>
									<div style="float:left;">
										<input type="text" name="internalnotes" title="Displayed to administrators only"/>
									</div>
								</div>
								<div style="clear:left;">
									<div style="font-weight:bold;float:left;width:70px;">
										Source:
									</div>
									<div style="float:left;">
										<input type="text" name="source" />
									</div>
								</div>
								<input type='hidden' name='cl' value='<?php echo $clManager->getClid(); ?>' />
								<input type='hidden' name='proj' value='<?php echo $proj; ?>' />
								<input type='hidden' name='showcommon' value='<?php echo $showCommon; ?>' />
								<input type='hidden' name='showvouchers' value='<?php echo $showVouchers; ?>' />
								<input type='hidden' name='showauthors' value='<?php echo $showAuthors; ?>' />
								<input type='hidden' name='thesfilter' value='<?php echo $clManager->getThesFilter(); ?>' />
								<input type='hidden' name='taxonfilter' value='<?php echo $taxonFilter; ?>' />
								<input type='hidden' name='searchcommon' value='<?php echo $searchCommon; ?>' />
								<input type="submit" name="submitadd" value="Add Species to List"/>
								<hr />
								<div style="text-align:center;">
									<a href="tools/checklistloader.php?clid=<?php echo $clManager->getClid();?>">Batch Upload</a>
								</div>
							</fieldset>
						</form>
					</div>
					<?php
					}
					if($showImages){
						foreach($taxaArray as $family => $sppArr){
							?>
							<div class="familydiv" id="<?php echo $family; ?>" style="clear:both;margin-top:10px;">
								<h3><?php echo $family; ?></h3>
							</div>
							<div>";
							<?php 
								foreach($sppArr as $tid => $imgArr){
									echo "<div style='float:left;text-align:center;width:210px;height:".($showCommon?"260":"240")."px;'>";
									$imgSrc = ($imgArr["tnurl"]?$imgArr["tnurl"]:$imgArr["url"]);
									echo "<div class='tnimg'>";
									$spUrl = "../taxa/index.php?taxauthid=0&taxon=$tid&cl=".$clManager->getClid();
									if($imgSrc){
										$imgSrc = (array_key_exists("imageDomain",$GLOBALS)&&substr($imgSrc,0,4)!="http"?$GLOBALS["imageDomain"]:"").$imgSrc;
										echo "<a href='".$spUrl."'>";
										list($width, $height) = getimagesize((substr($imgSrc,0,4)=="http"?"":"http://".$_SERVER["HTTP_HOST"]).$imgSrc);
										$dim = ($width > $height?"width":"height"); 
										echo "<img src='".$imgSrc."' style='$dim:196px;' />";
										echo "</a>";
									}
									else{
										echo "<div style='margin-top:50px;'><b>Image<br/>not yet<br/>available</b></div>";
									}
									echo "</div>";
									echo "<div><a href='".$spUrl."'><b>".$imgArr["sciname"]."</b></a></div>";
									echo "</div>\n";
								}
							?>
							</div>
							<?php 
						}
					}
					else{
						$voucherArr = Array();
						if($showVouchers) $voucherArr = $clManager->getVoucherArr();
						foreach($taxaArray as $family => $sppArr){
							?>
							<div class="familydiv" id="<?php echo $family;?>" style="margin-top:30px;">
								<h3><?php echo $family;?></h3>
							</div>
							<div>
								<?php 
								foreach($sppArr as $tid => $displayName){
									$voucherLink = "";
									$spUrl = "../taxa/index.php?taxauthid=0&taxon=$tid&cl=".$clManager->getClid();
									echo "<div id='tid-$tid'>";
									echo "<div>";
									if(!preg_match('/\ssp\d/',$displayName)) echo "<a href='".$spUrl."' target='_blank'>";
									echo $displayName;
									if(!preg_match('/\ssp\d/',$displayName)) echo "</a>";
									if($clManager->getEditable()){
										//Delete species or edit details specific to this taxon (vouchers, notes, habitat, abundance, etc
										?> 
										<span class="editspp" style="display:none;cursor:pointer;" onclick="openPopup('clsppeditor.php?tid=<?php echo $tid."&clid=".$clManager->getClid(); ?>','editorwindow');">
											<img src='../images/edit.png' style='width:13px;' title='edit details' />
										</span>
										<?php 
									}
									echo "</div>\n";
									if($showVouchers && array_key_exists($tid,$voucherArr)){
										echo "<div style='margin-left:10px;'>".$voucherArr[$tid]."</div>";
									}
									echo "</div>\n";
								}
								?>
							</div>
							<?php 
						}
					}
					$taxaLimit = ($showImages?$clManager->getImageLimit():$clManager->getTaxaLimit());
					if($clManager->getTaxaCount() > (($pageNumber+1)*$taxaLimit)){
						echo "<div style='margin:20px;clear:both;'><a href='checklist.php?pagenumber=".($pageNumber+1).$argStr."'>Display next ".$taxaLimit." taxa...</a></div>";
					}
					if(!$taxaArray) echo "<h1 style='margin-top:100px;'>No Taxa Found</h1>";
				?>
			</div>
		</div>
	</div>
<?php
 	include($serverRoot.'/footer.php');
?>

	<script type="text/javascript">
		var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
		document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
		try {
			var pageTracker = _gat._getTracker("<?php echo $googleAnalyticsKey; ?>");
			pageTracker._trackPageview();
		} catch(err) {}
	</script>

</body>
</html> 
 <?php
/*
 * Created on May 16, 2006
 *
 * To change the template for this generated file go to
 * Window - Preferences - PHPeclipse - PHP - Code Templates
 */
 
class ChecklistManager {

	private $clCon;
	private $clid;
	private $clName;
	private $clMetaData = Array();
	private $language = "English";
	private $dynamicSql;
	private $voucherArr = Array();
	private $thesFilter = 0;
	private $taxonFilter;
	private $showAuthors;
	private $showCommon;
	private $showImages;
	private $showVouchers;
	private $searchCommon;
	private $searchSynonyms;
	private $filterArr = Array();
	private $imageLimit = 100;
	private $taxaLimit = 500;
	private $speciesCount = 0;
	private $taxaCount = 0;
	private $familyCount = 0;
	private $genusCount = 0;
	private $editable = false;
	
	function __construct() {
		$this->clCon = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->clCon === false)) $this->clCon->close();
	}

	public function echoFilterList(){
		echo "'".implode("',\n'",$this->filterArr)."'";
	}
	
	public function echoSpeciesAddList(){
		$sql = "SELECT DISTINCT t.tid, t.sciname FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
			"WHERE ts.taxauthid = 1 ";
		if($this->taxonFilter){
			$sql .= "AND t.rankid > 140 AND (ts.family = '".$this->taxonFilter."' OR t.sciname LIKE '".$this->taxonFilter."%') ";
		}
		else{
			$sql .= "AND (t.rankid = 140 OR t.rankid = 180) ";
		}
		$sql .= "ORDER BY t.sciname";
		//echo $sql;
		$result = $this->clCon->query($sql);
        while ($row = $result->fetch_object()){
        	if($this->taxonFilter){
        		echo "<option value='".$row->tid."'>".$row->sciname."</option>\n";
        	}
        	else{
        		echo "<option>".$row->sciname."</option>\n";
        	}
       	}
	}
	
	public function addNewSpecies($dataArr){
		$insertStatus = false;
		$colSql = "";
		$valueSql = "";
		foreach($dataArr as $k =>$v){
			$colSql .= ",".$k;
			if($v){
				$valueSql .= ",'".$v."'";
			}
			else{
				$valueSql .= ",NULL";
			}
		}
		$sql = "INSERT INTO fmchklsttaxalink (clid".$colSql.") ".
			"VALUES (".$this->clid.$valueSql.")";
		//echo $sql;
		$con = MySQLiConnectionFactory::getCon("write");
		if($con->query($sql)){
			$insertStatus = true;
		}
		$con->close();
		return $insertStatus;
	}
	
	public function setClValue($clValue){
		if(is_numeric($clValue)){
			$this->clid = $clValue;
		}
		else{
			$sql = "SELECT c.clid FROM fmchecklists c WHERE (c.Name = '".$clValue."')";
			$rs = $this->clCon->query($sql);
			if($row = $rs->fetch_object()){
				$this->clid = $row->clid;
			}
		}
	}

	public function getClMetaData($fieldName = ""){
		if(!$this->clMetaData){
			$this->setClMetaData();
		}
		if($fieldName){
			return $this->clMetaData[$fieldName];
		}
		return $this->clMetaData;
	}
	
	private function setClMetaData(){
		$sql = "SELECT c.CLID, c.Name, c.Locality, c.Publication, ".
			"c.Abstract, c.Authors, c.dynamicsql, c.parentclid, c.Notes, ".
			"c.LatCentroid, c.LongCentroid, c.pointradiusmeters, c.access, ".
			"c.DateLastModified, c.uid, c.InitialTimeStamp ".
			"FROM fmchecklists c WHERE c.CLID = ".$this->clid;
 		$result = $this->clCon->query($sql);
		if($row = $result->fetch_object()){
			if(!$this->clid) $this->clid = $row->CLID;
			if(!$this->clName) $this->clName = $row->Name;
			$this->clMetaData["locality"] = $row->Locality; 
			$this->clMetaData["publication"] = $row->Publication;
			$this->clMetaData["abstract"] = $row->Abstract;
			$this->clMetaData["authors"] = $row->Authors;
			$this->clMetaData["parentclid"] = $row->parentclid;
			$this->clMetaData["notes"] = $row->Notes;
			$this->clMetaData["latcentroid"] = $row->LatCentroid;
			$this->clMetaData["longcentroid"] = $row->LongCentroid;
			$this->clMetaData["pointradiusmeters"] = $row->pointradiusmeters;
			$this->clMetaData["access"] = $row->access;
			$this->clMetaData["datelastmodified"] = $row->DateLastModified;
			$this->dynamicSql = $row->dynamicsql;
    	}
    	$result->close();
	}
	
	public function editMetaData($editArr){
		$setSql = "";
		foreach($editArr as $key =>$value){
			if($value){
				$setSql .= ", ".$key." = \"".$value."\"";
			}
			else{
				$setSql .= ", ".$key." = NULL";
			}
		}
		$sql = "UPDATE fmchecklists SET ".substr($setSql,2)." WHERE clid = ".$this->clid;
		//echo $sql;
		$con = MySQLiConnectionFactory::getCon("write");
		$con->query($sql);
		$con->close();
	}
	
	public function echoEditorList(){
		$sql = "SELECT FROM users";
	}

	public function getTaxonAuthorityList(){
    	$taxonAuthList = Array();
		$sql = "SELECT ta.taxauthid, ta.name FROM taxauthority ta WHERE (ta.isactive <> 0)";
 		$rs = $this->clCon->query($sql);
		while ($row = $rs->fetch_object()){
			$taxonAuthList[$row->taxauthid] = $row->name;
		}
		$rs->close();
		return $taxonAuthList;
	}

	//return an array: family => array(TID => sciName)
	public function getTaxaList($pageNumber = 0){
		if($this->showImages) return $this->getTaxaImageList($pageNumber);
		//Get list that shows which taxa have vouchers
		if($this->showVouchers){
			$vSql = "SELECT DISTINCT v.tid, v.occid, v.collector, v.notes FROM fmvouchers v WHERE (v.CLID = $this->clid)";
	 		$vResult = $this->clCon->query($vSql);
			while ($row = $vResult->fetch_object()){
				$this->voucherArr[$row->tid][] = "<a style='cursor:pointer' onclick=\"openPopup('../collections/individual/individual.php?occid=".$row->occid."','individwindow')\">".$row->collector."</a>\n";
			}
			$vResult->close();
		}
		//Get species list
		$sql = "";
		if($this->thesFilter){
			$sql = "SELECT DISTINCT ts.TID, ts.uppertaxonomy, IFNULL(ctl.familyoverride,ts.Family) AS family, 
				t.SciName, t.Author, ctl.habitat, ctl.abundance, ctl.notes, ctl.source ".
				"FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.TidAccepted) ".
				"INNER JOIN fmchklsttaxalink ctl ON ctl.TID = ts.TID ".
    	  		"WHERE ctl.CLID = ".$this->clid." AND ts.taxauthid = ".$this->thesFilter;
		}
		else{
			$sql = "SELECT DISTINCT t.TID, ts.uppertaxonomy, IFNULL(ctl.familyoverride,ts.Family) AS family, ".
				"t.SciName, t.Author, ctl.habitat, ctl.abundance, ctl.notes, ctl.source ".
				"FROM (taxa t INNER JOIN taxstatus ts ON t.tid = ts.Tid) ".
				"INNER JOIN fmchklsttaxalink ctl ON ctl.TID = t.TID ".
    	  		"WHERE (ts.taxauthid = 1) AND ctl.CLID = ".$this->clid;
		}
		if($this->taxonFilter){
			if($this->searchCommon){
				$sql .= " AND (t.tid IN(SELECT v.tid FROM taxavernaculars v WHERE v.VernacularName LIKE '%".$this->taxonFilter."%')) ";
			}
			else{
				if($this->searchSynonyms){
					$sql .= " AND ((ts.UpperTaxonomy = '".$this->taxonFilter."') OR (IFNULL(ctl.familyoverride,ts.Family) = '".$this->taxonFilter."') ".
						"OR (t.tid IN(SELECT tsb.tid FROM (taxa ta INNER JOIN taxstatus tsa ON ta.tid = tsa.tid) ".
						"INNER JOIN taxstatus tsb ON tsa.tidaccepted = tsb.tidaccepted ".
						"WHERE (tsa.UpperTaxonomy = '".$this->taxonFilter."') OR (ta.SciName Like '".$this->taxonFilter."%')))) ";
				}
				else{
					$sql .= " AND ((ts.UpperTaxonomy = '".$this->taxonFilter."') OR (t.SciName Like '".$this->taxonFilter."%') ".
						"OR (IFNULL(ctl.familyoverride,ts.Family) = '".$this->taxonFilter."')) ";
				}
			}
		}
		if($this->showCommon){
			$sql = "SELECT DISTINCT it.TID, it.uppertaxonomy, it.family, v.VernacularName, it.SciName, it.Author, ".
				"it.habitat, it.abundance, it.notes, it.source ".
				"FROM ((".$sql.") it INNER JOIN taxstatus ts ON it.tid=ts.tid) ".
				"LEFT JOIN (SELECT vern.tid, vern.VernacularName FROM taxavernaculars vern WHERE vern.Language = '".$this->language.
				"' AND vern.SortSequence = 1) v ON ts.TidAccepted = v.tid WHERE ts.taxauthid = 1";
		}
		$sql .= " ORDER BY family, SciName";
		//echo $sql;
		$result = $this->clCon->query($sql);
		$taxaList = Array();
		$familyPrev="";$genusPrev="";$speciesPrev="";$taxonPrev="";
		while($row = $result->fetch_object()){
			$this->filterArr[$row->uppertaxonomy] = "";
			$family = strtoupper($row->family);
			$this->filterArr[$family] = "";
			$tid = $row->TID;
			$sciName = $row->SciName;
			$taxonTokens = explode(" ",$sciName);
			if(in_array("x",$taxonTokens) || in_array("X",$taxonTokens)){
				if(in_array("x",$taxonTokens)) unset($taxonTokens[array_search("x",$taxonTokens)]);
				if(in_array("X",$taxonTokens)) unset($taxonTokens[array_search("X",$taxonTokens)]);
				$newArr = array();
				foreach($taxonTokens as $v){
					$newArr[] = $v;
				}
				$taxonTokens = $newArr;
			}
			if($this->taxaCount >= ($pageNumber*$this->taxaLimit) && $this->taxaCount <= ($pageNumber+1)*$this->taxaLimit){
				if(count($taxonTokens) == 1) $sciName .= " sp.";
				if($this->showVouchers){
					$clStr = "";
					if($row->habitat) $clStr = ", ".$row->habitat;
					if($row->abundance) $clStr .= ", ".$row->abundance;
					if($row->notes) $clStr .= ", ".$row->notes;
					if($row->source) $clStr .= ", <u>source</u>: ".$row->source;
					if(array_key_exists($tid,$this->voucherArr)){
						$clStr .= ($clStr?"; ":"").(is_array($this->voucherArr[$tid])?implode(", ",$this->voucherArr[$tid]):$this->voucherArr[$tid]);
					}
					if($clStr){
						$this->voucherArr[$tid] = substr($clStr,1);
					}
				}
				$author = $row->Author;
				$sciName = "<i><b>".$sciName."</b></i> ";
				if($this->showAuthors) $sciName .= $author;
				if($this->showCommon && $row->VernacularName) $sciName .= "<br />&nbsp;&nbsp;&nbsp;<b>[".$row->VernacularName."]</b>"; 
				$taxaList[$family][$tid] = $sciName;
    		}
    		if($family != $familyPrev) $this->familyCount++;
    		$familyPrev = $family;
    		if($taxonTokens[0] != $genusPrev) $this->genusCount++;
			$this->filterArr[$taxonTokens[0]] = "";
    		$genusPrev = $taxonTokens[0];
    		if(count($taxonTokens) > 1 && $taxonTokens[0]." ".$taxonTokens[1] != $speciesPrev){
    			$this->speciesCount++;
    			$speciesPrev = $taxonTokens[0]." ".$taxonTokens[1];
    		}
    		if(!$taxonPrev || strpos($sciName,$taxonPrev) === false){
    			$this->taxaCount++;
    		}
    		$taxonPrev = implode(" ",$taxonTokens);
		}
		$this->filterArr = array_keys($this->filterArr);
		sort($this->filterArr);
		$result->close();
		if($this->taxaCount < ($pageNumber*$this->taxaLimit)){
			$this->taxaCount = 0; $this->genusCount = 0; $this->familyCount = 0;
			unset($this->filterArr);
			return $this->getTaxaList(0);
		}
		return $taxaList;
	}

	private function getTaxaImageList($pageNumber){
		//Get species list
		$sql = "";
		if($this->thesFilter){
			$sql = "SELECT DISTINCT ts.TID, ts.uppertaxonomy, IFNULL(ctl.familyoverride,ts.Family) AS family, 
				t.SciName, t.Author, imgs.url, imgs.thumbnailurl ".
				"FROM ((taxa t INNER JOIN taxstatus ts ON t.tid = ts.TidAccepted) ".
				"INNER JOIN fmchklsttaxalink ctl ON ctl.TID = ts.TID) ".
				"LEFT JOIN (SELECT DISTINCT ts2.tidaccepted, ti.url, ti.thumbnailurl ".
				"FROM taxstatus ts2 INNER JOIN images ti ON ts2.tid = ti.tid ".
				"WHERE ts2.taxauthid = $this->thesFilter AND ti.SortSequence = 1) imgs ON ts.tidaccepted = imgs.tidaccepted ".
    	  		"WHERE ctl.CLID = ".$this->clid." AND ts.taxauthid = ".$this->thesFilter;
		}
		else{
			$sql = "SELECT DISTINCT t.TID, ts.uppertaxonomy, IFNULL(ctl.familyoverride,ts.Family) AS family, ".
				"t.SciName, t.Author, imgs.url, imgs.thumbnailurl ".
				"FROM ((taxa t INNER JOIN taxstatus ts ON t.tid = ts.Tid) ".
				"INNER JOIN fmchklsttaxalink ctl ON ctl.TID = t.TID) ".
				"LEFT JOIN (SELECT ts2.tidaccepted, ti.url, ti.thumbnailurl ".
				"FROM taxstatus ts2 INNER JOIN images ti ON ts2.tid = ti.tid ".
				"WHERE ts2.taxauthid = 1 AND ti.SortSequence = 1) imgs ON ts.tidaccepted = imgs.tidaccepted ".
				"WHERE (ts.taxauthid = 1) AND ctl.CLID = ".$this->clid;
		}
		if($this->taxonFilter){
			if($this->searchCommon){
				$sql .= " AND (t.tid IN(SELECT v.tid FROM taxavernaculars v WHERE v.VernacularName LIKE '%".$this->taxonFilter."%')) ";
			}
			else{
				if($this->searchSynonyms){
					$sql .= " AND ((ts.UpperTaxonomy = '".$this->taxonFilter."') OR (IFNULL(ctl.familyoverride,ts.Family) = '".$this->taxonFilter."') ".
						"OR (t.tid IN(SELECT tsb.tid FROM (taxa ta INNER JOIN taxstatus tsa ON ta.tid = tsa.tid) ".
						"INNER JOIN taxstatus tsb ON tsa.tidaccepted = tsb.tidaccepted ".
						"WHERE (tsa.UpperTaxonomy = '".$this->taxonFilter."') OR (ta.SciName Like '".$this->taxonFilter."%')))) ";
				}
				else{
					$sql .= " AND ((ts.UpperTaxonomy = '".$this->taxonFilter."') OR (t.SciName Like '".$this->taxonFilter."%') ".
						"OR (IFNULL(ctl.familyoverride,ts.Family) = '".$this->taxonFilter."')) ";
				}
			}
		}
		if($this->showCommon){
			$sql = "SELECT DISTINCT it.TID, it.uppertaxonomy, it.family, v.VernacularName, it.SciName, it.Author, ".
				"imgs.url, imgs.thumbnailurl ".
				"FROM ((".$sql.") it INNER JOIN taxstatus ts ON it.tid=ts.tid) ".
				"LEFT JOIN (SELECT vern.tid, vern.VernacularName FROM taxavernaculars vern WHERE vern.Language = '".$this->language.
				"' AND vern.SortSequence = 1) v ON ts.TidAccepted = v.tid ".
				"LEFT JOIN (SELECT ts2.tidaccepted, ti.url, ti.thumbnailurl ".
				"FROM taxstatus ts2 INNER JOIN images ti ON ts2.tid = ti.tid ".
				"WHERE ts2.taxauthid = 1 AND ti.SortSequence = 1) imgs ON ts.tidaccepted = imgs.tidaccepted ";
		}
		$sql .= " ORDER BY family, SciName";
		//echo $sql;
		$result = $this->clCon->query($sql);
		$taxaList = Array();$upperTaxArr = Array();
		$familyPrev="";$genusPrev="";$speciesPrev="";$taxonPrev="";
		while ($row = $result->fetch_object()){
			$upperTaxArr[$row->uppertaxonomy] = "";
			$family = strtoupper($row->family);
			$tid = $row->TID;
			$sciName = $row->SciName;
			$taxonTokens = explode(" ",$sciName);
			if(in_array("x",$taxonTokens) || in_array("X",$taxonTokens)){
				if(in_array("x",$taxonTokens)) unset($taxonTokens[array_search("x",$taxonTokens)]);
				if(in_array("X",$taxonTokens)) unset($taxonTokens[array_search("X",$taxonTokens)]);
				$newArr = array();
				foreach($taxonTokens as $v){
					$newArr[] = $v;
				}
				$taxonTokens = $newArr;
			}
			if($this->taxaCount >= ($pageNumber*$this->imageLimit) && $this->taxaCount < ($pageNumber+1)*$this->imageLimit){
				if(count($taxonTokens) == 1) $sciName .= " sp.";
				$author = $row->Author;
				$sciName = "<i>".$sciName."</i> ";
				if($this->showAuthors) $sciName .= $author;
				if($this->showCommon && $row->VernacularName) $sciName .= "<br /><b>[".$row->VernacularName."]</b>"; 
				$taxaList[$family][$tid]["sciname"] = $sciName;
				$taxaList[$family][$tid]["url"] = $row->url;
				$taxaList[$family][$tid]["tnurl"] = $row->thumbnailurl;
			}
    		if($family != $familyPrev) $this->familyCount++;
    		$familyPrev = $family;
    		if($taxonTokens[0] != $genusPrev) $this->genusCount++;
    		$genusPrev = $taxonTokens[0];
    		if(count($taxonTokens) > 1 && $taxonTokens[0]." ".$taxonTokens[1] != $speciesPrev) $this->speciesCount++;
    		$speciesPrev = $taxonTokens[0]." ".(count($taxonTokens) > 1?$taxonTokens[1]:"");
    		if(!$taxonPrev || strpos($sciName,$taxonPrev) === false){
    			$this->taxaCount++;
    		}
    		$taxonPrev = implode(" ",$taxonTokens);
		}
		$result->close();
		ksort($upperTaxArr);
		$this->filterArr = array_merge(array_keys($this->filterArr),array_keys($taxaList));
		if($this->taxaCount < ($pageNumber*$this->imageLimit)){
			$this->taxaCount = 0; $this->genusCount = 0; $this->familyCount = 0;
			unset($this->filterArr);
			return $this->getTaxaImageList(0);
		}
		return $taxaList;
	}

	public function setThesFilter($filt){
		$this->thesFilter = $filt;
	}

	public function getThesFilter(){
		return $this->thesFilter;
	}

	public function setTaxonFilter($tFilter){
		$this->taxonFilter = $tFilter;
	}
	
	public function setShowAuthors($value = 1){
		$this->showAuthors = $value;
	}

	public function setShowCommon($value = 1){
		$this->showCommon = $value;
	}

	public function setShowImages($value = 1){
		$this->showImages = $value;
	}

	public function setShowVouchers($value = 1){
		$this->showVouchers = $value;
	}

	public function setSearchCommon($value = 1){
		$this->searchCommon = $value;
	}

	public function setSearchSynonyms($value = 1){
		$this->searchSynonyms = $value;
	}

	public function getClid(){
		return $this->clid;
	}

	public function getClName(){
		return $this->clName;
	}
	
	public function setLanguage($l){
		$this->language = $l;
	}
	
	public function setImageLimit($cnt){
		$this->imageLimit = $cnt;
	}
	
	public function getImageLimit(){
		return $this->imageLimit;
	}
	
	public function setTaxaLimit($cnt){
		$this->taxaLimit = $cnt;
	}
	
	public function getTaxaLimit(){
		return $this->taxaLimit;
	}
	
	public function setEditable($e){
		$this->editable = $e;
	}
	
	public function getEditable(){
		return $this->editable;
	}
	
	public function getVoucherArr(){
		return $this->voucherArr;
	}
	
	public function getTaxaCount(){
		return $this->taxaCount;
	}

	public function getFamilyCount(){
		return $this->familyCount;
	}

	public function getGenusCount(){
		return $this->genusCount;
	}

	public function getSpeciesCount(){
		return $this->speciesCount;
	}

	public function echoParentSelect(){
		$sql = "SELECT c.clid, c.name FROM fmchecklists c ORDER BY c.name";
		$rs = $this->clCon->query($sql);
		while($row = $rs->fetch_object()){
			echo "<option value='".$row->clid."' ".($this->clMetaData["parentclid"]==$row->clid?" selected":"").">".$row->name."</option>";
		}
		$rs->close();
	}
}
?>
 