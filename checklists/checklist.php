<?php
	include_once('../config/symbini.php');
	include_once($serverRoot.'/classes/ChecklistManager.php');
	include_once($serverRoot.'/classes/ChecklistAdmin.php');
	header("Content-Type: text/html; charset=".$charset);
	header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
	header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

	$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:""; 
	$clValue = array_key_exists("cl",$_REQUEST)?$_REQUEST["cl"]:0; 
	$dynClid = array_key_exists("dynclid",$_REQUEST)?$_REQUEST["dynclid"]:0;
	$pageNumber = array_key_exists("pagenumber",$_REQUEST)?$_REQUEST["pagenumber"]:1;
	$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
	$thesFilter = array_key_exists("thesfilter",$_REQUEST)?$_REQUEST["thesfilter"]:0;
	$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:""; 
	$showAuthors = array_key_exists("showauthors",$_REQUEST)?$_REQUEST["showauthors"]:0; 
	$showCommon = array_key_exists("showcommon",$_REQUEST)?$_REQUEST["showcommon"]:0; 
	$showImages = array_key_exists("showimages",$_REQUEST)?$_REQUEST["showimages"]:0; 
	$showVouchers = array_key_exists("showvouchers",$_REQUEST)?$_REQUEST["showvouchers"]:0; 
	$searchCommon = array_key_exists("searchcommon",$_REQUEST)?$_REQUEST["searchcommon"]:0;
	$searchSynonyms = array_key_exists("searchsynonyms",$_REQUEST)?$_REQUEST["searchsynonyms"]:0;
	$editMode = array_key_exists("emode",$_REQUEST)?$_REQUEST["emode"]:0; 
	$printMode = array_key_exists("printmode",$_REQUEST)?$_REQUEST["printmode"]:0; 
	
	$statusStr='';
	
	//Search Synonyms is default
	if($action != "Rebuild List" && !array_key_exists('dllist_x',$_POST)) $searchSynonyms = 1;

	$clManager = new ChecklistManager();
	if($clValue){
		$statusStr = $clManager->setClValue($clValue);
	}
	elseif($dynClid){
		$clManager->setDynClid($dynClid);
	}
	if($pid) $clManager->setProj($pid);
	elseif(array_key_exists("proj",$_REQUEST)) $pid = $clManager->setProj($_REQUEST['proj']);
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
	$clid = $clManager->getClid();
	$pid = $clManager->getPid();
	
	if(array_key_exists('dllist_x',$_POST)){
		$clManager->downloadChecklistCsv();
		exit();
	}
	elseif(array_key_exists('printlist',$_POST)){
		$printMode = 1;
	}

	$dynSqlExists = false;
	$isEditor = false;
	if($isAdmin || (array_key_exists("ClAdmin",$userRights) && in_array($clid,$userRights["ClAdmin"]))){
		$isEditor = true;
		
		//Add species to checklist
		if(array_key_exists("tidtoadd",$_POST)){
			$dataArr = array();
			$dataArr["tid"] = $_POST["tidtoadd"];
			if($_POST["familyoverride"]) $dataArr["familyoverride"] = $_POST["familyoverride"];
			if($_POST["habitat"]) $dataArr["habitat"] = $_POST["habitat"];
			if($_POST["abundance"]) $dataArr["abundance"] = $_POST["abundance"];
			if($_POST["notes"]) $dataArr["notes"] = $_POST["notes"];
			if($_POST["source"]) $dataArr["source"] = $_POST["source"];
			if($_POST["internalnotes"]) $dataArr["internalnotes"] = $_POST["internalnotes"];
			$clAdmin = new ChecklistAdmin();
			$clAdmin->setClid($clid);
			$statusStr = $clAdmin->addNewSpecies($dataArr);
		}
	}
	$clArray = Array();
	$taxaArray = Array();
	if($clValue || $dynClid){
		$clArray = $clManager->getClMetaData();
		$taxaArray = $clManager->getTaxaList($pageNumber,($printMode?0:500));
	}
	if(array_key_exists("dynamicsql",$clArray) && $clArray["dynamicsql"]){
		$dynSqlExists = true;
	}
?>

<!DOCTYPE html >
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
	<title><?php echo $defaultTitle; ?> Research Checklist: <?php echo $clManager->getClName(); ?></title>
	<link rel="stylesheet" href="../css/main.css" type="text/css"/>
	<?php
		$keywordStr = "virtual flora,species list,".$clManager->getClName();
		if(array_key_exists("authors",$clArray) && $clArray["authors"]) $keywordStr .= ",".$clArray["authors"];
		echo'<meta name="keywords" content="'.$keywordStr.'" />';
	?>
	<link type="text/css" href="../css/jquery-ui.css" rel="Stylesheet" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript">
		<?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
	</script>
	<script type="text/javascript">
		var taxonArr = new Array(<?php $clManager->echoFilterList();?>);
		var clid = <?php echo $clid; ?>;
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
	if(!$printMode){
		$displayLeftMenu = (isset($checklists_checklistMenu)?$checklists_checklistMenu:false);
		include($serverRoot.'/header.php');
		echo '<div class="navpath">';
		if($pid){
			echo '<a href="../index.php">Home</a> &gt; ';
			echo '<a href="'.$clientRoot.'/projects/index.php?proj='.$pid.'">';
			echo $clManager->getProjName();
			echo '</a> &gt; ';
			echo '<b>'.$clManager->getClName().'</b>';
		}
		else{
			if(isset($checklists_checklistCrumbs)){
				if($checklists_checklistCrumbs){
					if($dynClid){
						if($clArray["type"] == "Specimen Checklist"){
							echo "<a href='".$clientRoot."/collections/list.php'>";
							echo "Occurrence Checklist";
							echo "</a> &gt;&gt; ";
						}
					}
					else{
						echo $checklists_checklistCrumbs;
					}
					echo " <b>".$clManager->getClName()."</b>";
				}
			}
			else{
				echo '<a href="../index.php">Home</a> &gt;&gt; ';
				if($dynClid){
					if($clArray['type'] == 'Specimen Checklist'){
						echo '<a href="'.$clientRoot.'/collections/list.php">';
						echo 'Occurrence Checklist';
						echo '</a> &gt;&gt; ';
					}
				}
				echo ' <b>'.$clManager->getClName().'</b>';
			}
		}
		echo '</div>';
	}
	?>
	<!-- This is inner text! -->
	<div id='innertext'>
		<?php
		if($clValue || $dynClid){
			if($clValue && $isEditor && !$printMode){
				?>
				<div style="float:right;width:90px;">
					<span style="">
						<a href="checklistadmin.php?clid=<?php echo $clid.'&pid='.$pid; ?>" style="margin-right:10px;" title="Checklist Administration">
							<img style="border:0px;height:15px;" src="../images/editadmin.png" /></a>
					</span>
					<span style="">
						<a href="voucheradmin.php?clid=<?php echo $clid.'&pid='.$pid; ?>" style="margin-right:10px;" title="Manage Linked Voucher">
							<img style="border:0px;height:15px;" src="../images/editvoucher.png" /></a>
					</span>
					<span style="" onclick="toggle('editspp');return false;" >
						<a href="#" title="Edit Species List">
							<img style="border:0px;height:17px;" src="../images/editspp.png" /></a>
					</span>
				</div>
				<?php 
			}
			?>
			<div style="float:left;color:#990000;font-size:20px;font-weight:bold;">
				<a href="checklist.php?cl=<?php echo $clValue."&proj=".$pid."&dynclid=".$dynClid; ?>">
					<?php echo $clManager->getClName(); ?>
				</a>
			</div>
			<?php 
			if($keyModIsActive && !$printMode){
				?>
				<div style="float:left;padding:5px;">
					<a href="../ident/key.php?cl=<?php echo $clValue."&proj=".$pid."&dynclid=".$dynClid;?>&taxon=All+Species">
						<img src='../images/key.jpg' style="width:15px;border:0px;" title='Open Symbiota Key' />
					</a>
				</div>
				<?php 
			}
			if(!$printMode){
				?>
				<div style="padding:5px;">
					<ul id="sddm">
					    <li>
					    	<span onmouseover="mopen('m1')" onmouseout="mclosetime()">
					    		<img src="../images/games/games.png" style="height:17px;" title="Access Species List Games" />
					    	</span>
					        <div id="m1" onmouseover="mcancelclosetime()" onmouseout="mclosetime()">
					        	<?php 
									$varStr = "?clid=".$clid."&dynclid=".$dynClid."&listname=".$clManager->getClName()."&taxonfilter=".$taxonFilter."&showcommon=".$showCommon.($clManager->getThesFilter()?"&thesfilter=".$clManager->getThesFilter():""); 
					        	?>
						        <a href="../games/namegame.php<?php echo $varStr; ?>">Name Game</a>
						        <a href="../games/flashcards.php<?php echo $varStr; ?>">Flash Card Quiz</a>
					        </div>
					    </li>
					</ul>
				</div>
				<div style="clear:both;"></div>
				<?php
			}
			//Do not show certain fields if Dynamic Checklist ($dynClid)
			if($clValue){
				?>
				<div style="clear:both;">
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
		
			if(($clArray["locality"] || ($clValue && ($clArray["latcentroid"] || $clArray["abstract"])) || $clArray["notes"]) && !$printMode){
				?>
				<div class="moredetails" style="color:blue;cursor:pointer;" onclick="toggle('moredetails')">More Details</div>
				<div class="moredetails" style="display:none;color:blue;cursor:pointer;" onclick="toggle('moredetails')">Less Details</div>
				<div class="moredetails" style="display:none">
					<?php 
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
					?>
				</div>
				<?php 
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
			?>
			<div>
				<hr/>
			</div>
			<div>
				<?php 
				if(!$printMode){
					?>
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
										<div style="margin-left:10px;">
											<?php 
												if($displayCommonNames){
													echo "<input type='checkbox' name='searchcommon' value='1'".($searchCommon?"checked":"")."/> Common Names<br/>";
												}
											?>
											<input type="checkbox" name="searchsynonyms" value="1"<?php echo ($searchSynonyms?"checked":"");?>/> Synonyms
										</div>
									</div>
								</div>
							    <!-- Thesaurus Filter -->
							    <div>
							    	<b>Filter:</b><br/>
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
									<input type='hidden' name='cl' value='<?php echo $clid; ?>' />
									<input type='hidden' name='dynclid' value='<?php echo $dynClid; ?>' />
									<input type="hidden" name="proj" value="<?php echo $pid; ?>" />
									<?php if(!$taxonFilter) echo "<input type='hidden' name='pagenumber' value='".$pageNumber."' />"; ?>
									<input type="submit" name="submitaction" value="Rebuild List" />
									<div class="button" style='float:right;margin-right:10px;width:13px;height:13px;' title="Download Checklist">
										<input type="image" name="dllist" value="Download List" src="../images/dl.png" />
									</div>
									<div class="button" style='float:right;margin-right:10px;width:13px;height:13px;' title="Print Checklist">
										<input type="image" name="printlist" value="Print List" src="../images/print.png" formtarget="_blank" />
									</div>
								</div>
							</fieldset>
						</form>
						<?php 
						if($clValue && $isEditor){
							?>
							<div class="editspp" style="display:<?php echo ($editMode==1?'block':'none');?>;width:250px;margin-top:10px;">
								<form id='addspeciesform' action='checklist.php' method='post' name='addspeciesform' onsubmit="return validateAddSpecies(this);">
									<fieldset style='margin:5px 0px 5px 5px;background-color:#FFFFCC;'>
										<legend><b>Add New Species to Checklist</b></legend>
										<div>
											<b>Taxon:</b><br/> 
											<input type="text" id="speciestoadd" name="speciestoadd" style="width:174px;" />
											<input type="hidden" id="tidtoadd" name="tidtoadd" value="" />
										</div>
										<div>
											<b>Family Override:</b><br/>
											<input type="text" name="familyoverride" style="width:122px;" title="Only enter if you want to override current family" />
										</div>
										<div>
											<b>Habitat:</b><br/>
											<input type="text" name="habitat" style="width:170px;" />
										</div>
										<div>
											<b>Abundance:</b><br/>
											<input type="text" name="abundance" style="width:145px;" />
										</div>
										<div>
											<b>Notes:</b><br/>
											<input type="text" name="notes" style="width:175px;" />
										</div>
										<div style="padding:2px;">
											<b>Internal Notes:</b><br/>
											<input type="text" name="internalnotes" style="width:126px;" title="Displayed to administrators only" />
										</div>
										<div>
											<b>Source:</b><br/>
											<input type="text" name="source" style="width:167px;" />
										</div>
										<div>
											<input type="hidden" name="cl" value="<?php echo $clid; ?>" />
											<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
											<input type='hidden' name='showcommon' value='<?php echo $showCommon; ?>' />
											<input type='hidden' name='showvouchers' value='<?php echo $showVouchers; ?>' />
											<input type='hidden' name='showauthors' value='<?php echo $showAuthors; ?>' />
											<input type='hidden' name='thesfilter' value='<?php echo $clManager->getThesFilter(); ?>' />
											<input type='hidden' name='taxonfilter' value='<?php echo $taxonFilter; ?>' />
											<input type='hidden' name='searchcommon' value='<?php echo $searchCommon; ?>' />
											<input type="hidden" name="emode" value="1" />
											<input type="submit" name="submitadd" value="Add Species to List"/>
											<hr />
										</div>
										<div style="text-align:center;">
											<a href="tools/checklistloader.php?clid=<?php echo $clid;?>">Batch Upload Spreadsheet</a>
										</div>
									</fieldset>
								</form>
							</div>
							<?php 
						}
						if(!$showImages){
							if($coordArr = $clManager->getCoordinates(0,true)){
								?>
								<div style="text-align:center;padding:10px">
									<a href="checklistmap.php?clid=<?php echo $clid.'&thesfilter='.$thesFilter.'&taxonfilter='.$taxonFilter; ?>" >
										<img src="http://maps.google.com/maps/api/staticmap?size=170x170&maptype=terrain&sensor=false&markers=size:tiny|<?php echo implode('|',$coordArr); ?>" style="border:0px;" />
									</a>
								</div>
								<?php
							}
						} 
						?>
					</div>
					<?php
				} 
				?>
				<div>
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
					if($pageCount > 1 && !$printMode){
						if(($pageNumber)>$pageCount) $pageNumber = 1;  
						$argStr .= "&cl=".$clValue."&dynclid=".$dynClid.($showCommon?"&showcommon=".$showCommon:"").($showVouchers?"&showvouchers=".$showVouchers:"");
						$argStr .= ($showAuthors?"&showauthors=".$showAuthors:"").($clManager->getThesFilter()?"&thesfilter=".$clManager->getThesFilter():"");
						$argStr .= ($pid?"&pid=".$pid:"").($showImages?"&showimages=".$showImages:"").($taxonFilter?"&taxonfilter=".$taxonFilter:"");
						$argStr .= ($searchCommon?"&searchcommon=".$searchCommon:"").($searchSynonyms?"&searchsynonyms=".$searchSynonyms:"");
						echo "<hr /><div>Page <b>".($pageNumber)."</b> of <b>$pageCount</b>: ";
						for($x=1;$x<=$pageCount;$x++){
							if($x>1) echo " | ";
							if(($pageNumber) == $x){
								echo "<b>";
							}
							else{
								echo "<a href='checklist.php?pagenumber=".$x.$argStr."'>";
							}
							echo ($x);
							if(($pageNumber) == $x){
								echo "</b>";
							}
							else{
								echo "</a>";
							}
						}
						echo "</div><hr />";
					}
					$prevfam = ''; 
					if($showImages){
						echo '<div style="clear:both;">&nbsp;</div>';
						foreach($taxaArray as $tid => $sppArr){
							$family = $sppArr['family'];
							$tu = (array_key_exists('tnurl',$sppArr)?$sppArr['tnurl']:'');
							$u = (array_key_exists('url',$sppArr)?$sppArr['url']:'');
							$imgSrc = ($tu?$tu:$u);
							?>
							<div style="float:left;text-align:center;width:210px;height:<?php echo 240+($showCommon?20:0);?>px;">
								<div class="tnimg" style="<?php echo ($imgSrc?"":"border:1px solid black;"); ?>">
									<?php 
									$spUrl = "../taxa/index.php?taxauthid=1&taxon=$tid&cl=".$clid;
									if($imgSrc){
										$imgSrc = (array_key_exists("imageDomain",$GLOBALS)&&substr($imgSrc,0,4)!="http"?$GLOBALS["imageDomain"]:"").$imgSrc;
										if(!$printMode) echo "<a href='".$spUrl."' target='_blank'>";
										echo "<img src='".$imgSrc."' />";
										if(!$printMode) echo "</a>";
									}
									else{
										?>
										<div style="margin-top:50px;">
											<b>Image<br/>not yet<br/>available</b>
										</div>
										<?php 
									}
									?>
								</div>
								<div>
									<?php 
									if(!$printMode) echo '<a href="'.$spUrl.'">'; 
									echo '<b>'.$sppArr['sciname'].'</b>';
									if(!$printMode) echo '</a>';
									if($family != $prevfam){
										?>
										<div class="familydiv" id="<?php echo $family; ?>">
											[<?php echo $family; ?>]
										</div>
										<?php
										$prevfam = $family;
									}
									if(array_key_exists('vern',$sppArr)){
										echo "<div style='font-weight:bold;'>".$sppArr["vern"]."</div>";
									}
									?>
								</div>
							</div>
							<?php 
						}
					}
					else{
						foreach($taxaArray as $tid => $sppArr){
							$family = $sppArr['family'];
							if($family != $prevfam){
								?>
								<div class="familydiv" id="<?php echo $family;?>" style="margin:15px 0px 5px 0px;">
									<div style="font-weight:bold;font-size:120%;"><?php echo $family;?></div>
								</div>
								<?php
								$prevfam = $family;
							}
							$spUrl = "../taxa/index.php?taxauthid=1&taxon=$tid&cl=".$clid;
							echo "<div id='tid-$tid' style='margin:0px 0px 3px 10px;'>";
							echo "<div>";
							if(!preg_match('/\ssp\d/',$sppArr["sciname"]) && !$printMode) echo "<a href='".$spUrl."' target='_blank'>";
							echo "<b><i>".$sppArr["sciname"]."</b></i> ";
							if(array_key_exists("author",$sppArr)) echo $sppArr["author"];
							if(!preg_match('/\ssp\d/',$sppArr["sciname"]) && !$printMode) echo "</a>";
							if(array_key_exists('vern',$sppArr)){
								echo " - <span style='font-weight:bold;'>".$sppArr["vern"]."</span>";
							}
							if($isEditor){
								//Delete species or edit details specific to this taxon (vouchers, notes, habitat, abundance, etc
								?> 
								<span class="editspp" style="display:<?php echo ($editMode==1?'inline':'none'); ?>;cursor:pointer;" onclick="openPopup('clsppeditor.php?tid=<?php echo $tid."&clid=".$clid; ?>','editorwindow');">
									<img src='../images/edit.png' style='width:13px;' title='edit details' />
								</span>
								<?php if($showVouchers && $dynSqlExists){ ?>
								<span class="editspp" style="display:none;cursor:pointer;" onclick="openPopup('../collections/list.php?db=all&thes=1&reset=1&taxa=<?php echo $tid."&clid=".$clid."&targettid=".$tid;?>','editorwindow');">
									<img src='../images/link.png' style='width:13px;' title='Link Voucher Specimens' />
								</span>
								<?php
								} 
							}
							echo "</div>\n";
							if($showVouchers){
								$voucStr = '';
								if(array_key_exists('vouchers',$sppArr)){
									$vArr = $sppArr['vouchers'];
									foreach($vArr as $occid => $collName){
										$voucStr .= ', ';
										if(!$printMode) $voucStr .= '<a style="cursor:pointer" onclick="return openPopup(\'../collections/individual/index.php?occid='.$occid.'\',\'individwindow\')">';
										$voucStr .= $collName;
										if(!$printMode) $voucStr .= "</a>\n";
									}
									$voucStr = substr($voucStr,2);
								}
								$noteStr = '';
								if(array_key_exists('notes',$sppArr)){
									$noteStr = $sppArr['notes'];
								}
								if($noteStr || $voucStr){
									echo "<div style='margin-left:15px;'>".$noteStr.($noteStr && $voucStr?'; ':'').$voucStr."</div>";
								}
							}
							echo "</div>\n";
						}
					}
					$taxaLimit = ($showImages?$clManager->getImageLimit():$clManager->getTaxaLimit());
					if($clManager->getTaxaCount() > (($pageNumber)*$taxaLimit) && !$printMode){
						echo '<div style="margin:20px;clear:both;">';
						echo '<a href="checklist.php?pagenumber='.($pageNumber+1).$argStr.'">Display next '.$taxaLimit.' taxa...</a></div>';
					}
					if(!$taxaArray) echo "<h1 style='margin:40px;'>No Taxa Found</h1>";
					?>
				</div>
			</div>
			<?php
		}
		else{
			?>
			<div style="color:red;">
				ERROR: Checklist identification is null!
			</div>
			<?php 
		}
		?>
	</div>
	<?php
	if(!$printMode) include($serverRoot.'/footer.php');
	?>
</body>
</html> 