<?php
/*
 * Modified: 22 June 2010 - E.E. Gilbert
 */
header("Content-Type: text/html; charset=ISO-8859-1");
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
include_once("../util/dbconnection.php");
include_once("../util/symbini.php");

$surveyId = $_REQUEST["surveyid"]; 
$pageNumber = array_key_exists("pagenumber",$_REQUEST)?$_REQUEST["pagenumber"]:0;
$proj = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:"";
//Display option
$showAuthors = array_key_exists("showauthors",$_REQUEST)?$_REQUEST["showauthors"]:0; 
$showCommon = array_key_exists("showcommon",$_REQUEST)?$_REQUEST["showcommon"]:0; 
$showImages = array_key_exists("showimages",$_REQUEST)?$_REQUEST["showimages"]:0; 
$thesFilter = array_key_exists("thesfilter",$_REQUEST)?$_REQUEST["thesfilter"]:0; 
//Search options
$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:""; 
$searchCommon = array_key_exists("searchcommon",$_REQUEST)?$_REQUEST["searchcommon"]:0;
$searchSynonyms = array_key_exists("searchsynonyms",$_REQUEST)?$_REQUEST["searchsynonyms"]:0;
$editMode = array_key_exists("emode",$_REQUEST)?$_REQUEST["emode"]:""; 
 	 	
$clManager = new SurveyManager($surveyId);
if($thesFilter) $clManager->setThesFilter($thesFilter);
if($taxonFilter) $clManager->setTaxonFilter($taxonFilter);
if($searchCommon) $clManager->setSearchCommon();
if($searchSynonyms) $clManager->setSearchSynonyms();
if($showAuthors) $clManager->setShowAuthors();
if($showCommon) $clManager->setShowCommon();
if($showImages) $clManager->setShowImages();

$editable = 0;
if($isAdmin || in_array("survey-".$surveyId,$userRights)){
	$editable = 1;
}
		
if($editable){
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
		$dataArr["notes"] = $_REQUEST["notes"];
		$clManager->addNewSpecies($dataArr);
	}
}
$mdArray = $clManager->getMetaData();
$taxaArray = $clManager->getTaxaList($pageNumber);
 ?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
	   "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html xmlns="http://www.w3.org/1999/xhtml" lang="en_US" xml:lang="en_US">

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"/>
	<title><?php echo $defaultTitle; ?> Survey Checklist: <?php echo $clManager->getSurveyName(); ?></title>
	<link rel="stylesheet" href="../css/main.css" type="text/css"/>
	<?php
		$keywordStr = "virtual flora,species list,".$clManager->getSurveyName();
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

		function openIndPu(occId){
			urlStr = "<?php echo $clientRoot;?>/collections/individual/individual.php?occid=" + occId;
			newWindow = window.open(urlStr,"newind","toolbar=1,resizable=1,width=650,height=600,left=20,top=20");
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
	$displayLeftMenu = (isset($checklists_surveyMenu)?$checklists_surveyMenu:"true");
	include($serverRoot."/util/header.php");
	if(isset($checklists_surveyCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $checklists_surveyCrumbs;
		echo " <b>".$clManager->getSurveyName()."</b>"; 
		echo "</div>";
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
				if($keyModIsActive){
					?>
					<a href="../ident/key.php?surveyid=<?php echo $surveyId."&proj=".$proj;?>&taxon=All+Species">
						<img src='../images/key.jpg' style='width:15px;border:0px;' title='Open Symbiota Key' />
					</a>&nbsp;&nbsp;&nbsp;
					<?php 
				}
				?>
				<a href="flashcards.php?surveyid=<?php echo $surveyId.($taxonFilter?"&taxonfilter=".$taxonFilter:"")."&showcommon=".$showCommon.($clManager->getThesFilter()?"&thesfilter=".$clManager->getThesFilter():"");?>">
					<img src="../images/quiz.jpg" style="height:10px;border:0px;" title="Open Flashcard Quiz" />
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
					<form id="editform" action='survey.php' method='get' name='editmatadata' target='_self'>
						<fieldset style='margin:5px 0px 5px 5px;'>
							<legend>Edit Survey Details:</legend>
							<div>
								<span>Survey Name: </span>
								<input type='text' name='esprojectname' size='80' value='<?php echo $clManager->getSurveyName();?>' />
							</div>
							<div>
								<span>Managers: </span>
								<input type='text' name='eclmanagers' value='<?php echo $mdArray["managers"]; ?>' />
							</div>
							<div>
								<span>Locality: </span>
								<input type='text' name='ecllocality' size='80' value='<?php echo $mdArray["locality"]; ?>' />
							</div> 
							<div>
								<span>Notes: </span>
								<input type='text' name='eclnotes' size='80' value='<?php echo $mdArray["notes"]; ?>' />
							</div>
							<div>
								<span>Latitude Centroid: </span>
								<input id="latdec" type='text' name='ecllatcentroid' value='<?php echo $mdArray["latcentroid"]; ?>' />
								<span style="cursor:pointer;" onclick="openPointMap();">
									<img src="../images/world40.gif" style="width:12px;" />
								</span>
							</div>
							<div>
								<span>Longitude Centroid: </span>
								<input id="lngdec" type='text' name='ecllongcentroid' value='<?php echo $mdArray["longcentroid"]; ?>' />
							</div>
							<div>
								<span>Public Access: </span>
								<input type="radio" name="ispublic" value="0" <?php if(!$mdArray["ispublic"]) echo "checked"; ?>/> Not Public
								<input type="radio" name="ispublic" value="1" <?php if($mdArray["ispublic"]) echo "checked"; ?>/> Available to Public
							</div>
							<div>
								<input type='submit' name='editsubmit' id='editsubmit' value='Submit Changes' />
							</div>
							<input type='hidden' name='survey' value='<?php echo $surveyId(); ?>' />
							<input type='hidden' name='proj' value='<?php echo $proj; ?>' />
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
				<form id='changetaxonomy' name='changetaxonomy' action='survey.php' method='get'>
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
						<div style="float:right;">
							<input type='hidden' name='surveyid' value='<?php echo $surveyId; ?>' />
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
					$argStr .= "&surveyid=".$surveyId.($showCommon?"&showcommon=".$showCommon:"");
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
										list($width, $height) = getimagesize((substr($imgSrc,0,4)=="http"?"":"http://".$_SERVER["HTTP_HOST"]).$imgSrc);
										$dim = ($width > $height?"width":"height"); 
										?>
										<a href="../taxa/index.php?taxon=<?php echo $tid; ?>">
											<img src="<?php echo $imgSrc;?>" style="<?php echo $dim; ?>:196px;" />
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
 	include($serverRoot."/util/footer.php");
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
 
class SurveyManager {

	private $conn;
	private $surveyId;
	private $surveyName;
	private $metaData = Array();
	private $language = "English";
	private $thesFilter = 1;
	private $taxonFilter;
	private $showAuthors;
	private $showCommon;
	private $showImages;
	private $searchCommon;
	private $searchSynonyms;
	private $sqlBase = "";

	private $imageLimit = 100;
	private $taxaLimit = 500;
	
	private $speciesCount = 0;
	private $taxaCount = 0;
	private $familyCount = 0;
	private $genusCount = 0;
	
	function __construct($sId) {
		$this->surveyId = $sId;
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
	}

	function __destruct(){
 		if(!($this->conn === false)) $this->conn->close();
	}

	public function echoFilterList(){
		$sql = "SELECT DISTINCT ts.uppertaxonomy, ts.family, t.unitname1 ".
			"FROM ((omsurveyoccurlink sol INNER JOIN omoccurrences o ON sol.occid = o.occid) ".
			"INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid) ".
			"INNER JOIN taxa t ON ts.tidaccepted = t.tid ".
			"WHERE sol.surveyid = ".$this->surveyId." AND ts.taxauthid = ".$this->thesFilter." ";
		//echo $sql;
		$uArr = Array(); $fArr = Array(); $gArr = Array(); 
		$rs = $this->conn->query($sql);
		while($row = $rs->fetch_object()){
			$uArr[$row->uppertaxonomy] = "";
			$fArr[$row->family] = "";
			$gArr[] = $row->unitname1;
		}
		$rs->close();
		echo "'".implode("',\n'",array_keys($uArr))."',\n";
		echo "'".implode("',\n'",array_keys($fArr))."',\n";
		echo "'".implode("',\n'",$gArr)."'";
	}
	
	public function getMetaData(){
		if(!$this->metaData){
			$sql = "SELECT s.projectname, s.locality, s.managers, s.latcentroid, s.longcentroid, s.notes ".
				"FROM omsurveys s WHERE s.surveyid = ".$this->surveyId;
	 		$result = $this->conn->query($sql);
			if($row = $result->fetch_object()){
				$this->surveyName = $row->projectname;
				$this->metaData["locality"] = $row->locality;
				$this->metaData["managers"] = $row->managers;
				$this->metaData["latcentroid"] = $row->latcentroid;
				$this->metaData["longcentroid"] = $row->longcentroid;
				$this->metaData["notes"] = $row->notes;
	    	}
	    	$result->close();
		}
		return $this->metaData;
	}
	
	public function editMetaData($editArr){
		$setSql = "";
		foreach($editArr as $key =>$value){
			if($value){
				$setSql .= ", ".$key." = '".$value."'";
			}
			else{
				$setSql .= ", ".$key." = NULL";
			}
		}
		$sql = "UPDATE omsurveys SET ".substr($setSql,2)." WHERE surveyid = ".$this->surveyId;
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
 		$rs = $this->conn->query($sql);
		while ($row = $rs->fetch_object()){
			$taxonAuthList[$row->taxauthid] = $row->name;
		}
		$rs->close();
		return $taxonAuthList;
	}

	//return an array: [family][sciname][occid] => collector
	public function getTaxaList($pageNumber = 0){
		$sql = "SELECT ts.family, t.tid, t.sciname, t.author, o.occid, CONCAT_WS(' ',o.recordedby,o.recordnumber) as collstr ";
		if($this->showCommon) $sql .= ", v.vernacularname ";
		$sql .= "FROM (((omsurveyoccurlink sol INNER JOIN omoccurrences o ON sol.occid = o.occid) ".
			"INNER JOIN taxstatus ts ON o.tidinterpreted = ts.tid) ".
			"INNER JOIN taxa t ON ts.tidaccepted = t.tid) ";
		if($this->showCommon){
			$sql .= "LEFT JOIN (SELECT vern.tid, vern.vernacularname FROM taxavernaculars vern WHERE vern.Language = '".$this->language.
				"' AND vern.SortSequence = 1) v ON t.Tid = v.tid ";
		}
		$sql .= "WHERE sol.surveyid = ".$this->surveyId." AND ts.taxauthid = ".$this->thesFilter." ";
		if($this->taxonFilter){
			if($this->searchCommon){
				$sql .= "AND (t.tid IN(SELECT v.tid FROM taxavernaculars v WHERE v.VernacularName LIKE '%".$this->taxonFilter."%')) ";
			}
			else{
				$sql .= "AND (ts.UpperTaxonomy = '".$this->taxonFilter."' OR t.SciName Like '".$this->taxonFilter."%' ".
					"OR ts.Family = '".$this->taxonFilter."' ";
				if($this->searchSynonyms){
					$sql .= "OR (t.tid IN(SELECT tsa.tidaccepted FROM taxstatus tsa INNER JOIN taxa ta ON tsa.tid = ta.tid ".
						"WHERE ta.SciName Like '".$this->taxonFilter."%'))";
				}
				$sql .= ")";
			}
		}
		$sql .= " ORDER BY ts.family, t.SciName";
		//echo $sql;
		$result = $this->conn->query($sql);
		$itemLimit = ($this->showImages?$this->imageLimit:$this->taxaLimit);
		$taxaArr = Array();
		$activeTids = Array();
		$genusPrev="";$speciesPrev="";$taxonPrev="";$tidPrev=0;
		while($row = $result->fetch_object()){
			$family = strtoupper($row->family);
			$tid = $row->tid;
			if($tid != $tidPrev){
				$sciName = $row->sciname;
				$taxonTokens = explode(" ",$sciName);
				if(strtolower($taxonTokens[0]) == "x") array_shift($taxonTokens);
				if(count($taxonTokens) > 1 && strtolower($taxonTokens[1]) == "x") array_splice($taxonTokens,1,1);

				if($this->taxaCount >= ($pageNumber*$itemLimit) && $this->taxaCount <= ($pageNumber+1)*$itemLimit){
					//taxaCount is within range for being displayed
					if(count($taxonTokens) == 1) $sciName .= " sp.";
					$taxaArr[$family][$tid]["sciname"] = $sciName;
					if($this->showAuthors) $taxaArr[$family][$tid]["author"] = $row->author; 
					if($this->showCommon && $row->vernacularname) $taxaArr[$family][$tid]["vern"] = $row->vernacularname;
					$activeTids[] = $tid;
				}
				
	    		if($taxonTokens[0] != $genusPrev) $this->genusCount++;
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
			$tidPrev = $tid;
	    	if(array_key_exists($family,$taxaArr) && array_key_exists($tid,$taxaArr[$family])){
				$taxaArr[$family][$tid]["vs"][$row->occid] = $row->collstr;
	    	}
	    }
		$result->close();
		$this->familyCount = count($taxaArr);
		
		//User is asking for too high of a page number, thus return first page only
		if(($pageNumber*$itemLimit) > $this->taxaCount){
			$this->taxaCount = 0;
			return $this->getTaxaList(0);
		}
		//Grab images, if requested
		if($this->showImages && $activeTids){
			$imgArr = Array();
			$sql = "SELECT DISTINCT ts2.tid, i.url, i.thumbnailurl ".
				"FROM (images i INNER JOIN taxstatus ts1 ON i.tid = ts1.tid) ".
				"INNER JOIN taxstatus ts2 ON ts1.tidaccepted = ts2.tidaccepted ".
				"WHERE ts1.taxauthid = 1 AND ts2.taxauthid = 1 AND i.sortsequence = 1 AND ts1.tid IN (".implode(",",$activeTids).")";
			$rs = $this->conn->query($sql);
			while($row = $rs->fetch_object()){
				$imgArr[$row->tid]["url"] = ($row->thumbnailurl?$row->thumbnailurl:$row->url); 
			}
			$rs->close();
			foreach($taxaArr as $family => $tidArr){
				foreach($tidArr as $t => $v){
					if(array_key_exists($t,$imgArr)){
						$taxaArr[$family][$t]["url"] = $imgArr[$t]["url"];
					} 
				}
			}
		}
		return $taxaArr;
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

	public function setSearchCommon($value = 1){
		$this->searchCommon = $value;
		if($value && $this->taxonFilter) $this->showCommon = 1;
	}

	public function setSearchSynonyms($value = 1){
		$this->searchSynonyms = $value;
	}

	public function getSurveyId(){
		return $this->surveyId;
	}

	public function getSurveyName(){
		return $this->surveyName;
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
}
?>
 