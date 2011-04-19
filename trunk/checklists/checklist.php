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
	$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:""; 
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
	$editMode = array_key_exists("emode",$_REQUEST)?$_REQUEST["emode"]:0; 
	$crumbLink = array_key_exists("crumblink",$_REQUEST)?$_REQUEST["crumblink"]:""; 
	$sqlFrag = array_key_exists("sqlfrag",$_REQUEST)?$_REQUEST["sqlfrag"]:"";
	
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

	if($action == "Download List"){
		$clManager->downloadChecklistCsv();
		exit();
	}

	$dynSqlExists = false;
	$statusStr = "";
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
		elseif($action == "Create SQL Fragment"){
			$sqlFragArr = Array();
			if($_POST['country']) $sqlFragArr['country'] = $_POST['country'];
			if($_POST['state']) $sqlFragArr['state'] = $_POST['state'];
			if($_POST['county']) $sqlFragArr['county'] = $_POST['county'];
			if($_POST['locality']) $sqlFragArr['locality'] = $_POST['locality'];
			if($_POST['latsouth']) $sqlFragArr['latsouth'] = $_POST['latsouth'];
			if($_POST['latnorth']) $sqlFragArr['latnorth'] = $_POST['latnorth'];
			if($_POST['lngeast']) $sqlFragArr['lngeast'] = $_POST['lngeast'];
			if($_POST['lngwest']) $sqlFragArr['lngwest'] = $_POST['lngwest'];
			$statusStr = $clManager->saveSql($sqlFragArr);
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
	if(array_key_exists("dynamicsql",$clArray) && $clArray["dynamicsql"]){
		$dynSqlExists = true;
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
	<link type="text/css" href="../css/jquery-ui.css" rel="Stylesheet" />
	<script type="text/javascript" src="../js/jquery-1.4.4.min.js"></script>
	<script type="text/javascript" src="../js/jquery-ui-1.8.11.custom.min.js"></script>
	<script type="text/javascript">
		<?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
	</script>
	<script type="text/javascript">
		var taxonArr = new Array(<?php $clManager->echoFilterList();?>);
		var clid = <?php echo $clManager->getClid(); ?>;
	</script>
	<script type="text/javascript" src="../js/symb/checklists.checklist.js"></script>
	<style type="text/css">
		#sddm{margin:0;padding:0;z-index:30;}
		#sddm:hover {background-color:#EAEBD8;}
		#sddm img{padding:3px;}
		#sddm:hover img{background-color:#EAEBD8;}
		#sddm li{margin:0px;padding: 0;list-style: none;float: left;font: bold 11px arial}
		#sddm li a{display: block;margin: 0 1px 0 0;padding: 4px 10px;width: 60px;background: #5970B2;color: #FFF;text-align: center;text-decoration: none}
		#sddm li a:hover{background: #49A3FF}
		#sddm div{position: absolute;visibility:hidden;margin:0;padding:0;background:#EAEBD8;border:1px solid #5970B2}
		#sddm div a	{position: relative;display:block;margin:0;padding:5px 10px;width:auto;white-space:nowrap;text-align:left;text-decoration:none;background:#EAEBD8;color:#2875DE;font-weight:bold;}
		#sddm div a:hover{background:#49A3FF;color:#FFF}
	</style>
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
			<div style="float:left;color:#990000;font-size:20px;font-weight:bold;margin:0px 10px 10px 0px;">
				<?php echo $clManager->getClName(); ?>
			</div>
			<?php 
			if($keyModIsActive){
				?>
				<div style="float:left;padding-top:5px;">
					<a href="../ident/key.php?cl=<?php echo $clValue."&proj=".$proj."&dynclid=".$dynClid."&crumblink=".$crumbLink;?>&taxon=All+Species">
						<img src='../images/key.jpg' style="width:15px;border:0px;" title='Open Symbiota Key' />
					</a>&nbsp;&nbsp;&nbsp;
				</div>
				<?php 
			}
			?>
			<div style="float:left;padding-top:5px;">
				<ul id="sddm">
				    <li>
				    	<span onmouseover="mopen('m1')" onmouseout="mclosetime()">
				    		<img src="../images/games/games.png" style="height:17px;" title="Access Species List Games" />
				    	</span>
				        <div id="m1" onmouseover="mcancelclosetime()" onmouseout="mclosetime()">
				        	<?php 
								$varStr = "?clid=".$clManager->getClid()."&dynclid=".$dynClid."&listname=".$clManager->getClName()."&taxonfilter=".$taxonFilter."&showcommon=".$showCommon.($clManager->getThesFilter()?"&thesfilter=".$clManager->getThesFilter():""); 
				        	?>
					        <a href="../games/namegame.php<?php echo $varStr; ?>">Name Game</a>
					        <a href="../games/flashcards.php<?php echo $varStr; ?>">Flash Card Quiz</a>
				        </div>
				    </li>
				</ul>
			</div>
			<div style="clear:both;"></div>
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
			if($statusStr){ 
				?>
				<hr />
				<div style="margin:20px;font-weight:bold;color:red;">
					<?php echo $statusStr; ?>
				</div>
				<hr />
				<?php 
			} 
			
			if($clValue && $clManager->getEditable()){
			?>
			<!-- Checklist editing div  -->
			<div class="editmd" style="display:none;">
				<div id="tabs" style="margin:10px;">
				    <ul>
				        <li><a href="#metadata"><span>Metadata</span></a></li>
				        <li><a href="#dynsql"><span>Dynamic SQL</span></a></li>
				        <li><a href="#editors"><span>Editors</span></a></li>
				    </ul>
					<div id="metadata">
						<form id="checklisteditform" action="checklist.php" method="post" name="editclmatadata" onsubmit="return validateMetadataForm(this)">
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
									<span style="cursor:pointer;" onclick="openMappingAid('editclmatadata','ecllatcentroid','ecllongcentroid');">
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
									<input type='submit' name='submitaction' id='editsubmit' value='Submit Changes' />
								</div>
								<input type='hidden' name='cl' value='<?php echo $clManager->getClid(); ?>' />
								<input type='hidden' name='proj' value='<?php echo $proj; ?>' />
							</fieldset>
						</form>
					</div>
					<div id="dynsql">
						<div style="margin:15px;">
							This editing module will aid you in building an SQL fragment that can be used to help link vouchers to species names within the checklist. 
							When a dynamic SQL fragment exists, the checklist editors will have access to 
							editing tools that will dynamically query occurrence records matching the criteria within the SQL statement. 
							Editors can then go through the list and select the records that are to serve as specimen vouchers for that checklist.
							See the Flora Voucher Mapping Tutorial for more details. 
							Your data administrator can aid you in establishing more complex SQL fragments than can be created within this form.  
						</div>
						<fieldset style="padding:20px;">
							<legend><b>Current Dynamic SQL Fragment</b></legend>
							<?php echo $clManager->getDynamicSql()?$clManager->getDynamicSql():"SQL not set"?>
						</fieldset>
						<form name="sqlbuilder" action="checklist.php" method="post" onsubmit="return validateSqlFragForm(this);" style="margin-bottom:15px;">
							<fieldset style="padding:15px;">
								<legend><b>SQL Fragment Builder</b></legend>
								<div style="margin:0px 10px 10px 10px;">
									Use this form to aid in building the SQL fragment. 
									Click the 'Create SQL Fragment' button to build and save the SQL using the terms 
									supplied in the form. 
								</div>
								<div style="float:left;width:250px;">
									<div style="margin:3px;">
										<b>Country:</b>
										<input type="text" name="country" onchange="" />
									</div>
									<div style="margin:3px;">
										<b>State:</b>
										<input type="text" name="state" onchange="" />
									</div>
									<div style="margin:3px;">
										<b>County:</b>
										<input type="text" name="county" onchange="" />
									</div>
									<div style="margin:3px;">
										<b>Locality:</b>
										<input type="text" name="locality" onchange="" />
									</div>
								</div>
								<div style="float:left;width:350px;">
									<div>
										<b>Latitude/Longitude:</b>
										<span style="margin-left:75px;">
											<input type="text" name="latnorth" style="width:70px;" onchange="" title="Latitude North" />
										</span>
									</div>
									<div style="margin-left:112px;">
										<span style="">
											<input type="text" name="lngwest" style="width:70px;" onchange="" title="Longitude West" />
										</span>
										<span style="margin-left:70px;">
											<input type="text" name="lngeast" style="width:70px;" onchange="" title="Longitude East" />
										</span>
									</div>
									<div style="margin-left:187px;">
										<input type="text" name="latsouth" style="width:70px;" onchange="" title="Latitude South" />
									</div>
									<div style="float:right;margin:20px 20px 0px 0px;">
										<input type="submit" name="submitaction" value="Create SQL Fragment" />
										<input type='hidden' name='cl' value='<?php echo $clManager->getClid(); ?>' />
										<input type='hidden' name='proj' value='<?php echo $proj; ?>' />
									</div>
								</div>
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
					<form id='changetaxonomy' name='changetaxonomy' action='checklist.php' method='post'>
						<fieldset>
						    <legend><b>Options</b></legend>
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
							<div>
								<!-- Display Taxon Authors: 0 = false, 1 = true  --> 
							    <input id='showauthors' name='showauthors' type='checkbox' value='1' <?php echo ($showAuthors?"checked":""); ?>/> 
							    Taxon Authors
							</div>
							<div style="margin:5px 0px 0px 5px;">
								<input type='hidden' name='cl' value='<?php echo $clManager->getClid(); ?>' />
								<input type='hidden' name='dynclid' value='<?php echo $dynClid; ?>' />
								<?php if(!$taxonFilter) echo "<input type='hidden' name='pagenumber' value='".$pageNumber."' />"; ?>
								<input type="submit" name="submitaction" value="Rebuild List" />
								<div class="button" style='float:right;margin-right:10px;width:13px;height:13px;' title="Download Checklist">
									<input type="image" name="submitaction" value="Download List" src="../images/dl.png" />
								</div>
							</div>
						</fieldset>
					</form>
					<?php 
					if($clValue && $clManager->getEditable()){
					?>
					<div class="editspp" style="display:<?php echo ($editMode?"block":"none");?>;width:250px;margin-top:10px;">
						<form id='addspeciesform' action='checklist.php' method='post' name='addspeciesform' onsubmit="return validateAddSpecies(this);">
							<fieldset style='margin:5px 0px 5px 5px;background-color:#FFFFCC;'>
								<legend><b>Add New Species to Checklist</b></legend>
								<div style="clear:left">
									<div style="font-weight:bold;float:left;width:70px;">
										Taxon:
									</div>
									<div style="float:left;">
										<input type="text" id="speciestoadd" name="speciestoadd" />
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
										<input type="text" name="abundance" />
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
								<input type="hidden" name="cl" value="<?php echo $clManager->getClid(); ?>" />
								<input type="hidden" name="proj" value="<?php echo $proj; ?>" />
								<input type='hidden' name='showcommon' value='<?php echo $showCommon; ?>' />
								<input type='hidden' name='showvouchers' value='<?php echo $showVouchers; ?>' />
								<input type='hidden' name='showauthors' value='<?php echo $showAuthors; ?>' />
								<input type='hidden' name='thesfilter' value='<?php echo $clManager->getThesFilter(); ?>' />
								<input type='hidden' name='taxonfilter' value='<?php echo $taxonFilter; ?>' />
								<input type='hidden' name='searchcommon' value='<?php echo $searchCommon; ?>' />
								<input type="hidden" name="emode" value="1" />
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
					?>
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
								foreach($sppArr as $tid => $taxonArr){
									$voucherLink = "";
									$spUrl = "../taxa/index.php?taxauthid=0&taxon=$tid&cl=".$clManager->getClid();
									echo "<div id='tid-$tid'>";
									echo "<div>";
									if(!preg_match('/\ssp\d/',$taxonArr["sciname"])) echo "<a href='".$spUrl."' target='_blank'>";
									echo "<b><i>".$taxonArr["sciname"]."</b></i> ";
									if(array_key_exists("author",$taxonArr)) echo $taxonArr["author"];
									if(!preg_match('/\ssp\d/',$taxonArr["sciname"])) echo "</a>";
									if($clManager->getEditable()){
										//Delete species or edit details specific to this taxon (vouchers, notes, habitat, abundance, etc
										?> 
										<span class="editspp" style="display:none;cursor:pointer;" onclick="openPopup('clsppeditor.php?tid=<?php echo $tid."&clid=".$clManager->getClid(); ?>','editorwindow');">
											<img src='../images/edit.png' style='width:13px;' title='edit details' />
										</span>
										<?php if($showVouchers && $dynSqlExists){ ?>
										<span class="editspp" style="display:none;cursor:pointer;" onclick="openPopup('../collections/list.php?db=all&thes=1&reset=1&taxa=<?php echo $taxonArr["sciname"]."&clid=".$clManager->getClid()."&targettid=".$tid;?>','editorwindow');">
											<img src='../images/link.png' style='width:13px;' title='Link Voucher Specimens' />
										</span>
										<?php
										} 
									}
									echo "</div>\n";
									if(array_key_exists("vern",$taxonArr)){
										echo "<div style='margin-left:10px;font-weight:bold;'>".$taxonArr["vern"]."</div>";
									}
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
					if(!$taxaArray) echo "<h1 style='margin:40px;'>No Taxa Found</h1>";
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

</body>
</html> 