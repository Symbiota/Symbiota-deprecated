<?php
/*
 * Rebuilt 29 Jan 2010
 * By E.E. Gilbert
 */
	include_once('../config/symbini.php');
	include_once($serverRoot.'/classes/ChecklistManager.php');
	header("Content-Type: text/html; charset=".$charset);
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
	$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
	$clValue = array_key_exists("cl",$_REQUEST)?$_REQUEST["cl"]:0; 
	$dynClid = array_key_exists("dynclid",$_REQUEST)?$_REQUEST["dynclid"]:0;
	$pageNumber = array_key_exists("pagenumber",$_REQUEST)?$_REQUEST["pagenumber"]:0;
	$proj = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:"";
	$thesFilter = array_key_exists("thesfilter",$_REQUEST)?$_REQUEST["thesfilter"]:0; 
	$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:""; 
	$showAuthors = array_key_exists("showauthors",$_REQUEST)?$_REQUEST["showauthors"]:0; 
	$showCommon = array_key_exists("showcommon",$_REQUEST)?$_REQUEST["showcommon"]:0; 
	$showImages = array_key_exists("showimages",$_REQUEST)?$_REQUEST["showimages"]:0; 
	$showVouchers = array_key_exists("showvouchers",$_REQUEST)?$_REQUEST["showvouchers"]:0; 
	$searchCommon = array_key_exists("searchcommon",$_REQUEST)?$_REQUEST["searchcommon"]:0;
	$searchSynonyms = array_key_exists("searchsynonyms",$_REQUEST)?$_REQUEST["searchsynonyms"]:0;
	$editMode = array_key_exists("emode",$_REQUEST)?$_REQUEST["emode"]:""; 
	$crumbLink = array_key_exists("crumblink",$_REQUEST)?$_REQUEST["crumblink"]:""; 
	
	//Search Synonyms is default
	if($action != "Rebuild List") $searchSynonyms = 1;

	$clManager = new ChecklistManager();
	if($clValue){
		$clManager->setClValue($clValue);
	}
	elseif($dynClid){
		$clManager->setDynClid($dynClid);
	}
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
	if($clValue || $dynClid){
		$clArray = $clManager->getClMetaData();
		$taxaArray = $clManager->getTaxaList($pageNumber);
	}
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
		if(array_key_exists("authors",$clArray) && $clArray["authors"]) $keywordStr .= ",".$clArray["authors"];
		if($proj) $keywordStr .= ",".$proj;
		echo"<meta name=\"keywords\" content=\"".$keywordStr."\" />";
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
		    mapWindow=open("../tools/mappointaid.php?formid=checklisteditform","mappointaid","resizable=0,width=800,height=700,left=20,top=20");
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
				var url = "rpc/removetidfromchklst.php";
				url=url + "?clid=" + clid + "&tid=" + tid;
				url=url + "&sid="+Math.random();
				rtXmlHttp.onreadystatechange=function(){
					if(rtXmlHttp.readyState==4 && rtXmlHttp.status==200){
						var tidDeleted = rtXmlHttp.responseText;
						var sciNameDeletion = sciName.replace(/<.{1,2}>/gi,"");
						if(tidDeleted == 0){
							alert("FAILED: Delection of " + sciNameDeletion + " unsuccessful");
						}
						else{
							document.getElementById("tid-"+tidDeleted).style.display = "none";
						}
					}
				};
				rtXmlHttp.open("POST",url,true);
				rtXmlHttp.send(null);
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

		function validateAddSpecies(f){ 
			var sciName = f.speciestoadd.value;
			if(sciName == ""){
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
			cseXmlHttp.onreadystatechange=function(){
				if(cseXmlHttp.readyState==4 && cseXmlHttp.status==200){
					testTid = cseXmlHttp.responseText;
					if(testTid == ""){
						alert("ERROR: Scientific name does not exist in database. Did you spell it correctly? If so, it may have to be added to taxa table.");
					}
					else{
						document.getElementById("tidtoadd").value = testTid;
						document.forms["addspeciesform"].submit();
					}
				}
			};
			cseXmlHttp.open("POST",url,true);
			cseXmlHttp.send(null);
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
	if($crumbLink || isset($checklists_checklistCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		if($crumbLink == "occurcl"){
			echo "<a href='".$clientRoot."/collections/checklist.php'>";
			echo "Occurrence Checklist";
			echo "</a> &gt; ";
		}
		elseif(!$dynClid){
			echo $checklists_checklistCrumbs;
		}
		echo " <b>".$clManager->getClName()."</b>";
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id='innertext'>
		<?php
		if($clValue || $dynClid){
			if($clValue && $clManager->getEditable()){
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
					<a href="../ident/key.php?cl=<?php echo $clValue."&proj=".$proj."&dynclid=".$dynClid."&crumblink=".$crumbLink;?>&taxon=All+Species">
						<img src='../images/key.jpg' style='width:15px;border:0px;' title='Open Symbiota Key' />
					</a>&nbsp;&nbsp;&nbsp;
					<?php 
				}
				?>
				<a href="flashcards.php?clid=<?php echo $clManager->getClid()."&dynclid=".$dynClid."&taxonfilter=".$taxonFilter."&showcommon=".$showCommon.($clManager->getThesFilter()?"&thesfilter=".$clManager->getThesFilter():"");?>">
					<img src="../images/quiz.jpg" style="height:10px;border:0px;" title="Open Flashcard Quiz" />
				</a>
			</h1>
			<?php
			//Do not show certain fields if Dynamic Checklist ($dynClid)
			if($clValue){
				?>
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
			}
		
			if($clArray["locality"] || ($clValue && ($clArray["latcentroid"] || $clArray["abstract"])) || $clArray["notes"]){
				echo "<div class=\"moredetails\" style=\"color:blue;cursor:pointer;\" onclick=\"toggle('moredetails')\">More Details</div>";
				echo "<div class='moredetails' style='display:none'>";
				$locStr = $clArray["locality"];
				if($clValue && $clArray["latcentroid"]) $locStr .= " (".$clArray["latcentroid"].", ".$clArray["longcentroid"].")";
				if($locStr){
					echo "<div><span style='font-weight:bold;'>Locality: </span>".$locStr."</div>";
				}
				if($clValue && $clArray["abstract"]){
					echo "<div><span style='font-weight:bold;'>Abstract: </span>".$clArray["abstract"]."</div>";
				}
				if($clValue && $clArray["notes"]){
					echo "<div><span style='font-weight:bold;'>Notes: </span>".$clArray["notes"]."</div>";
				}
				echo "</div>";
			}
			if($clValue && $clManager->getEditable()){
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
									<input type="text" name="eclauthors" size="70" value="<?php echo $clArray["authors"]; ?>" />
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
									<input type='submit' name='action' id='editsubmit' value='Submit Changes' />
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
							<?php if($clValue){ ?>
								<div style='display:<?php echo ($showImages?"none":"block");?>' id="showvouchersdiv">
									<!-- Display as Vouchers: 0 = false, 1 = true  --> 
								    <input id='showvouchers' name='showvouchers' type='checkbox' value='1' <?php echo ($showVouchers?"checked":""); ?>/> 
								    Notes &amp; Vouchers
								</div>
							<?php } ?>
							<div style="float:right;">
								<input type='hidden' name='cl' value='<?php echo $clManager->getClid(); ?>' />
								<input type='hidden' name='dynclid' value='<?php echo $dynClid; ?>' />
								<?php if(!$taxonFilter) echo "<input type='hidden' name='pagenumber' value='".$pageNumber."' />"; ?>
								<input type="submit" name="action" value="Rebuild List" />
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
						$argStr .= "&cl=".$clValue."&dynclid=".$dynClid.($showCommon?"&showcommon=".$showCommon:"").($showVouchers?"&showvouchers=".$showVouchers:"");
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
	
					if($clValue && $clManager->getEditable()){
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
								<div>
								<?php 
									foreach($sppArr as $tid => $imgArr){
										echo "<div style='float:left;text-align:center;width:210px;height:".($showCommon?"260":"240")."px;'>";
										$imgSrc = ($imgArr["tnurl"]?$imgArr["tnurl"]:$imgArr["url"]);
										echo "<div class='tnimg' style='".($imgSrc?"":"border:1px solid black;")."'>";
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
			<?php
		}
		else{
			?>
			<div>
				Checklist identification is null!
			</div>
			<?php 
		}
		?>
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
 