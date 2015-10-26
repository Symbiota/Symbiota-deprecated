<?php
/*
 * Modified: 22 June 2010 - E.E. Gilbert
 */
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/SurveyManager.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$surveyId = $_REQUEST["surveyid"]; 
$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
$pageNumber = array_key_exists("pagenumber",$_REQUEST)?$_REQUEST["pagenumber"]:0;
$proj = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:"";
//Display option
$showAuthors = array_key_exists("showauthors",$_REQUEST)?$_REQUEST["showauthors"]:0; 
$showCommon = array_key_exists("showcommon",$_REQUEST)?$_REQUEST["showcommon"]:0; 
$showImages = array_key_exists("showimages",$_REQUEST)?$_REQUEST["showimages"]:0;
$showVouchers = array_key_exists("showvouchers",$_REQUEST)?$_REQUEST["showvouchers"]:0; 
$thesFilter = array_key_exists("thesfilter",$_REQUEST)?$_REQUEST["thesfilter"]:0; 
//Search options
$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:""; 
$searchCommon = array_key_exists("searchcommon",$_REQUEST)?$_REQUEST["searchcommon"]:0;
$searchSynonyms = array_key_exists("searchsynonyms",$_REQUEST)?$_REQUEST["searchsynonyms"]:0;
$editMode = array_key_exists("emode",$_REQUEST)?$_REQUEST["emode"]:""; 
 	 	
$clManager = new SurveyManager($surveyId);
if($proj) $clManager->setProj($proj);
if($thesFilter) $clManager->setThesFilter($thesFilter);
if($taxonFilter) $clManager->setTaxonFilter($taxonFilter);
if($searchCommon) $clManager->setSearchCommon();
if($searchSynonyms) $clManager->setSearchSynonyms();
if($showAuthors) $clManager->setShowAuthors();
if($showCommon) $clManager->setShowCommon();
if($showImages) $clManager->setShowImages();

if($action == "Download List"){
	$clManager->downloadChecklistCsv();
	exit();
}

$editable = 0;
if($isAdmin || (array_key_exists("SurveyAdmin",$userRights) && in_array($surveyId,$userRights["SurveyAdmin"]))){
	$editable = 1;
}
		
if($editable){
	//Submit checklist MetaData edits
 	if($action == "Submit Changes"){
 		$clManager->editMetaData();
 	}
}
$mdArray = $clManager->getMetaData();
$taxaArray = $clManager->getTaxaList($pageNumber);
 ?>

<html>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
	<title><?php echo $defaultTitle; ?> Survey Checklist: <?php echo $clManager->getSurveyName(); ?></title>
	<link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<?php
		$keywordStr = "virtual flora,species list,".$clManager->getSurveyName();
		echo"<meta name='keywords' content='".$keywordStr."' />";
	?>
	<link type="text/css" href="../css/jquery-ui.css" rel="Stylesheet" />	
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script language=javascript>
		$(document).ready(function() {
			//Filter autocomplete
			var taxonArr = new Array(<?php $clManager->echoFilterList();?>);
			$("#taxonfilter").autocomplete({ source: taxonArr }, { delay: 0, minLength: 2 });
	
			$('#tabs').tabs();
	
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

		function openIndPu(occId){
			var wWidth = 900;
			if(document.getElementById('maintable').offsetWidth){
				wWidth = document.getElementById('maintable').offsetWidth*1.05;
			}
			else if(document.body.offsetWidth){
				wWidth = document.body.offsetWidth*0.9;
			}
			urlStr = "<?php echo $clientRoot;?>/collections/individual/index.php?occid=" + occId;
			newWindow = window.open(urlStr,"newind","toolbar=1,resizable=1,width="+(wWidth)+",height=600,left=20,top=20");
			if (newWindow.opener == null) newWindow.opener = self;
		}
	
		function openMappingAid() {
		    mapWindow=open("../tools/mappointaid.php?formname=editmetadata&latname=latcentroid&longname=longcentroid","mappointaid","resizable=0,width=800,height=700,left=20,top=20");
		    if (mapWindow.opener == null) mapWindow.opener = self;
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
				rtXmlHttp.onreadystatechange=function(){
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

		function validateAddSpecies(){ 
			var sciName = document.getElementById("speciestoadd").value;
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
			};
			cseXmlHttp.open("POST",url,true);
			cseXmlHttp.send(null);
		} 

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
	<script type="text/javascript">
		<?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
	</script>
</head>
 
<body>
<?php
	$displayLeftMenu = (isset($checklists_surveyMenu)?$checklists_surveyMenu:"true");
	include($serverRoot."/header.php");
	if($proj){
		echo '<div class="navpath">';
		echo '<a href="../index.php">Home</a> &gt; ';
		echo '<a href="'.$clientRoot.'/projects/index.php?pid='.$clManager->getPid().'">';
		echo $clManager->getProjName();
		echo '</a> &gt; ';
		echo '<b>'.$clManager->getSurveyName().'</b>';
		echo '</div>';
	}
	else{
		if(isset($checklists_surveyCrumbs)){
			if($checklists_surveyCrumbs){
				echo "<div class='navpath'>";
				echo "<a href='../index.php'>Home</a> &gt; ";
				echo $checklists_surveyCrumbs;
				echo " <b>".$clManager->getSurveyName()."</b>";
				echo "</div>";
			}
		}
		else{
			echo '<div class="navpath">';
			echo '<a href="../index.php">Home</a> &gt; ';
			echo ' <b>'.$clManager->getSurveyName().'</b>';
			echo '</div>';
		}
	}
	?>
	<!-- This is inner text! -->
	<div id='innertext'>
		<?php 
			if($editable){
				?>
				<div style="float:right;cursor:pointer;" onclick="javascript:toggle('editingobj');" title="Toggle Checklist Editing Functions">
					<img style="border:0px;" src="../images/edit.png" />
				</div>
				<?php 
			}
		?>
		<h1>
		<?php 
			echo $clManager->getSurveyName()."&nbsp;&nbsp;";
			if(1 == 2){
				if($keyModIsActive === true || $keyModIsActive === 1){
					?>
					<a href="../ident/key.php?surveyid=<?php echo $surveyId."&proj=".$clManager->getPid();?>&taxon=All+Species">
						<img src='../images/key.png' style='width:15px;border:0px;' title='Open Symbiota Key' />
					</a>&nbsp;&nbsp;&nbsp;
					<?php 
				}
				?>
				<a href="flashcards.php?surveyid=<?php echo $surveyId.($taxonFilter?"&taxonfilter=".$taxonFilter:"")."&showcommon=".$showCommon.($clManager->getThesFilter()?"&thesfilter=".$clManager->getThesFilter():"");?>">
					<img src="../images/quiz.png" style="height:10px;border:0px;" title="Open Flashcard Quiz" />
				</a>
				<?php 
			}
		?>
		</h1>
		<div>
			<span style="font-weight:bold;">
				Managers: 
			</span>
			<?php echo $mdArray["managers"]; ?>
		</div>
		<?php if($mdArray["notes"]){ ?>
		<div>
			<span style="font-weight:bold;">
				Notes: 
			</span>
			<?php echo $mdArray["notes"]; ?>
		</div>
		<?php } ?>
		<div>
			<span style="font-weight:bold;">Locality: </span>
			<?php 
				echo $mdArray["locality"]." ";
				if($mdArray["latcentroid"]){
					echo "(".$mdArray["latcentroid"].", ".$mdArray["longcentroid"].")";
				} 
			?>
		</div>
		<?php 
		if($editable){
		?>
		<!-- Checklist editing div  -->
		<div class="editingobj" style="display:none;">
			<div id="tabs" style="margin:10px;height:500px;">
			    <ul>
			        <li><a href="#metadata"><span>Metadata</span></a></li>
			        <li><a href="#editors"><span>Editors</span></a></li>
			    </ul>
				<div id="metadata">
					<form id="editform" action='survey.php' method='post' name='editmetadata' target='_self'>
						<fieldset style='margin:5px 0px 5px 5px;'>
							<legend>Edit Survey Details:</legend>
							<div>
								<span>Survey Name: </span>
								<input type='text' name='projectname' size='80' value='<?php echo $clManager->getSurveyName();?>' />
							</div>
							<div>
								<span>Managers: </span>
								<input type='text' name='managers' value='<?php echo $mdArray["managers"]; ?>' />
							</div>
							<div>
								<span>Locality: </span>
								<input type='text' name='locality' size='80' value='<?php echo $mdArray["locality"]; ?>' />
							</div> 
							<div>
								<span>Notes: </span>
								<input type='text' name='notes' size='80' value='<?php echo $mdArray["notes"]; ?>' />
							</div>
							<div>
								<span>Latitude Centroid: </span>
								<input id="latdec" type='text' name='latcentroid' value='<?php echo $mdArray["latcentroid"]; ?>' />
								<span style="cursor:pointer;" onclick="openMappingAid();">
									<img src="../images/world.png" style="width:12px;" />
								</span>
							</div>
							<div>
								<span>Longitude Centroid: </span>
								<input id="lngdec" type='text' name='longcentroid' value='<?php echo $mdArray["longcentroid"]; ?>' />
							</div>
							<div>
								<span>Public Access: </span>
								<input type="radio" name="ispublic" value="0" <?php if(!$mdArray["ispublic"]) echo "checked"; ?>/> Not Public
								<input type="radio" name="ispublic" value="1" <?php if($mdArray["ispublic"]) echo "checked"; ?>/> Available to Public
							</div>
							<div>
								<input type='submit' name='action' id='editsubmit' value='Submit Changes' />
							</div>
							<input type='hidden' name='surveyid' value='<?php echo $surveyId; ?>' />
							<input type='hidden' name='proj' value='<?php echo $clManager->getPid(); ?>' />
							<input type='hidden' name='showcommon' value='<?php echo $showCommon; ?>' />
							<input type='hidden' name='showvouchers' value='<?php echo $showVouchers; ?>' />
							<input type='hidden' name='thesfilter' value='<?php echo $thesFilter; ?>' />
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
				<form id='changetaxonomy' name='changetaxonomy' action='survey.php' method='post'>
					<fieldset>
					    <legend>Options</legend>
						<!-- Taxon Filter option -->
					    <div id="taxonfilterdiv" title="Filter species list by family or genus">
					    	<div>
					    		<b>Search:</b> 
								<input type="text" id="taxonfilter" name="taxonfilter" value="<?php echo $taxonFilter;?>" size="20" />
							</div>
							<div>
								<?php 
									if($displayCommonNames){
										echo "<input type='checkbox' name='searchcommon' value='1'".($searchCommon?"checked":"")."/> Common Names";
									}
								?>
							</div>
							<div>
								<input type="checkbox" name="searchsynonyms" value="1"<?php echo ($searchSynonyms?"checked":"");?>/> Synonyms
							</div>
						</div>
					    <!-- Thesaurus Filter -->
					    <div><b>Filter:</b>
					    	<select id='thesfilter' name='thesfilter'>
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
						<div>
							<!-- Display Taxon Authors: 0 = false, 1 = true  --> 
						    <input id='showauthors' name='showauthors' type='checkbox' value='1' <?php echo ($showAuthors?"checked":""); ?>/> 
						    Taxon Authors
						</div>
						<div style="margin:5px 0px 0px 5px;">
							<input type='hidden' name='surveyid' value='<?php echo $surveyId; ?>' />
							<input type='hidden' name='proj' value='<?php echo $clManager->getPid(); ?>' />
							<?php if(!$taxonFilter) echo "<input type='hidden' name='pagenumber' value='".$pageNumber."' />"; ?>
							<input type="submit" name="action" value="Rebuild List" />
							<div class="button" style='float:right;margin-right:10px;width:16px;height:16px;padding:2px;' title="Download Checklist">
								<input type="image" name="action" value="Download List" src="../images/dl.png" />
							</div>
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
					(including subsp. and var.)
				</div>
				<?php 
				$taxaLimit = ($showImages?$clManager->getImageLimit():$clManager->getTaxaLimit());
				$pageCount = ceil($clManager->getTaxaCount()/$taxaLimit);
				$argStr = "";
				if($pageCount > 1){
					if(($pageNumber+1)>$pageCount) $pageNumber = 0;  
					$argStr .= "&surveyid=".$surveyId.($showCommon?"&showcommon=".$showCommon:"");
					$argStr .= ($showAuthors?"&showauthors=".$showAuthors:"").($clManager->getThesFilter()?"&thesfilter=".$clManager->getThesFilter():"");
					$argStr .= ($proj?"&proj=".$clManager->getPid():"").($showImages?"&showimages=".$showImages:"").($taxonFilter?"&taxonfilter=".$taxonFilter:"");
					$argStr .= ($searchCommon?"&searchcommon=".$searchCommon:"").($searchSynonyms?"&searchsynonyms=".$searchSynonyms:"");
					echo "<hr /><div>Page <b>".($pageNumber+1)."</b> of <b>$pageCount</b>: ";
					for($x=0;$x<$pageCount;$x++){
						if($x) echo " | ";
						if(($pageNumber) == $x){
							echo "<b>";
						}
						else{
							echo "<a href='survey.php?pagenumber=".$x.$argStr."'>";
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

				if($showImages){
					foreach($taxaArray as $family => $sppArr){
						?>
						<div class="familydiv" id="<?php echo $family; ?>" style="clear:both;margin-top:10px;">
							<h3><?php echo $family; ?></h3>
						</div>
						<div>
							<?php 
							foreach($sppArr as $tid => $imgArr){
								?>
								<div style="float:left;text-align:center;width:210px;height:<?php echo ($showCommon?"260":"240");?>px;">
									<div class='tnimg'>
									<?php 
									if(array_key_exists("url",$imgArr)){ 
										$imgSrc = (array_key_exists("imageDomain",$GLOBALS)&&substr($imgArr["url"],0,4)!="http"?$GLOBALS["imageDomain"]:"").$imgArr["url"];
										?>
										<a href="../taxa/index.php?taxon=<?php echo $tid; ?>">
											<img src="<?php echo $imgSrc;?>" style="height:100%;" />
										</a>
										<?php 
									}
									else{
										?>
										<div style='margin-top:50px;'>
											<b>Image<br/>not yet<br/>available</b>
										</div>
										<?php 
									}
									?>
									</div>
									<div>
										<a href="../taxa/index.php?taxon=<?php echo $tid; ?>">
											<b><i><?php echo $imgArr["sciname"];?></i></b>
											<?php 
											if(array_key_exists("author",$imgArr)) echo $imgArr["author"];
											?>
										</a>
										<?php if(array_key_exists("vern",$imgArr)) echo "<br /><b>[".$imgArr["vern"]."]</b>"; ?>
									</div>
								</div>
								<?php 
							}
							?>
						</div>
						<?php 
					}
				}
				else{
					foreach($taxaArray as $family => $sppArr){
						?>
						<div class="familydiv" id="<?php echo $family;?>" style="margin-top:30px;">
							<h3><?php echo $family;?></h3>
						</div>
						<div>
							<?php 
							foreach($sppArr as $tid => $spArr){
								?>
								<div id="tid-<?php echo $tid;?>">
									<div>
										<?php if(!preg_match('/\ssp\d/',$spArr["sciname"])) echo "<a href='../taxa/index.php?taxon=$tid' target='_blank'>"; ?>
										<b><i><?php echo $spArr["sciname"];?></i></b>
										<?php
										if(array_key_exists("author",$spArr)) echo $spArr["author"]; 
										if(!preg_match('/\ssp\d/',$spArr["sciname"])) echo "</a>";
										if(array_key_exists("vern",$spArr)) echo "<div style='margin-left:10px;'><b>[".$spArr["vern"]."]</b></div>";
										$vStr = "";
										foreach($spArr["vs"] as $occId => $oStr){
											$vStr .= "; <a style='cursor:pointer;' onclick=\"openIndPu('".$occId."')\">".$oStr."</a>";
										}
										echo "<div style='margin-left:10px;'>".substr($vStr,2)."</div>"
										?>
									</div>
								</div>
								<?php 
							}
							?>
						</div>
						<?php 
					}
				}
				$taxaLimit = ($showImages?$clManager->getImageLimit():$clManager->getTaxaLimit());
				if($clManager->getTaxaCount() > (($pageNumber+1)*$taxaLimit)){
					echo "<div style='margin:20px;clear:both;'><a href='survey.php?pagenumber=".($pageNumber+1).$argStr."'>Display next ".$taxaLimit." taxa...</a></div>";
				}
				if(!$taxaArray) echo "<h1 style='margin-top:100px;'>No Taxa Found</h1>";
				?>
			</div>
		</div>
	</div>
<?php
 	include($serverRoot."/footer.php");
?>
</body>
</html> 
 