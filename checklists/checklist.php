<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistManager.php');
include_once($SERVER_ROOT.'/content/lang/checklists/checklist.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:"";
$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;
if(!$clid && array_key_exists("cl",$_REQUEST)) $clid = $_REQUEST["cl"];
$dynClid = array_key_exists("dynclid",$_REQUEST)?$_REQUEST["dynclid"]:0;
$pageNumber = array_key_exists("pagenumber",$_REQUEST)?$_REQUEST["pagenumber"]:1;
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$thesFilter = array_key_exists("thesfilter",$_REQUEST)?$_REQUEST["thesfilter"]:0;
$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:"";
$showAuthors = array_key_exists("showauthors",$_REQUEST)?$_REQUEST["showauthors"]:0;
$showSynonyms = array_key_exists("showsynonyms",$_REQUEST)?$_REQUEST["showsynonyms"]:0;
$showCommon = array_key_exists("showcommon",$_REQUEST)?$_REQUEST["showcommon"]:0;
$showImages = array_key_exists("showimages",$_REQUEST)?$_REQUEST["showimages"]:0;
$showVouchers = array_key_exists("showvouchers",$_REQUEST)?$_REQUEST["showvouchers"]:0;
$showAlphaTaxa = array_key_exists("showalphataxa",$_REQUEST)?$_REQUEST["showalphataxa"]:0;
$searchCommon = array_key_exists("searchcommon",$_REQUEST)?$_REQUEST["searchcommon"]:0;
$searchSynonyms = array_key_exists("searchsynonyms",$_REQUEST)?$_REQUEST["searchsynonyms"]:0;
$defaultOverride = array_key_exists("defaultoverride",$_REQUEST)?$_REQUEST["defaultoverride"]:0;
$editMode = array_key_exists("emode",$_REQUEST)?$_REQUEST["emode"]:0;
$printMode = array_key_exists("printmode",$_REQUEST)?$_REQUEST["printmode"]:0;

//Sanitation
if(!is_numeric($clid)) $clid = 0;
if(!is_numeric($dynClid)) $dynClid = 0;
if(!is_numeric($pid)) $pid = 0;
if(!is_numeric($pageNumber)) $pageNumber = 1;
if(!is_numeric($thesFilter)) $thesFilter = 0;
if(!preg_match('/^[a-z\-\s]+$/i', $taxonFilter)) $taxonFilter = '';
if(!is_numeric($showAuthors)) $showAuthors = 0;
if(!is_numeric($showSynonyms)) $showSynonyms = 0;
if(!is_numeric($showCommon)) $showCommon = 0;
if(!is_numeric($showImages)) $showImages = 0;
if(!is_numeric($showVouchers)) $showVouchers = 0;
if(!is_numeric($showAlphaTaxa)) $showAlphaTaxa = 0;
if(!is_numeric($searchCommon)) $searchCommon = 0;
if(!is_numeric($searchSynonyms)) $searchSynonyms = 0;
if(!is_numeric($defaultOverride)) $defaultOverride = 0;
if(!is_numeric($editMode)) $editMode = 0;
if(!is_numeric($printMode)) $printMode = 0;

$statusStr='';

//Search Synonyms is default
if($action != "Rebuild List" && !array_key_exists('dllist_x',$_POST)) $searchSynonyms = 1;
if($action == "Rebuild List") $defaultOverride = 1;

$clManager = new ChecklistManager();
if($clid){
	$clManager->setClid($clid);
}
elseif($dynClid){
	$clManager->setDynClid($dynClid);
}
$clArray = $clManager->getClMetaData();
$activateKey = $KEY_MOD_IS_ACTIVE;
$showDetails = 0;
if($clid && $clArray["defaultSettings"]){
	$defaultArr = json_decode($clArray["defaultSettings"], true);
	$showDetails = $defaultArr["ddetails"];
	if(!$defaultOverride){
		if(array_key_exists('dsynonyms',$defaultArr)){$showSynonyms = $defaultArr["dsynonyms"];}
		if(array_key_exists('dcommon',$defaultArr)){$showCommon = $defaultArr["dcommon"];}
		if(array_key_exists('dimages',$defaultArr)){$showImages = $defaultArr["dimages"];}
		if(array_key_exists('dvouchers',$defaultArr)){$showVouchers = $defaultArr["dvouchers"];}
		if(array_key_exists('dauthors',$defaultArr)){$showAuthors = $defaultArr["dauthors"];}
		if(array_key_exists('dalpha',$defaultArr)){$showAlphaTaxa = $defaultArr["dalpha"];}
	}
	if(isset($defaultArr['activatekey'])) $activateKey = $defaultArr['activatekey'];
}
if($pid) $clManager->setProj($pid);
elseif(array_key_exists("proj",$_REQUEST) && $_REQUEST['proj']) $pid = $clManager->setProj($_REQUEST['proj']);
if($thesFilter) $clManager->setThesFilter($thesFilter);
if($taxonFilter) $clManager->setTaxonFilter($taxonFilter);
$clManager->setLanguage($LANG_TAG);
if($searchCommon){
	$showCommon = 1;
	$clManager->setSearchCommon(true);
}
if($searchSynonyms) $clManager->setSearchSynonyms(true);
if($showAuthors) $clManager->setShowAuthors(true);
if($showSynonyms) $clManager->setShowSynonyms(true);
if($showCommon) $clManager->setShowCommon(true);
if($showImages) $clManager->setShowImages(true);
if($showVouchers) $clManager->setShowVouchers(true);
if($showAlphaTaxa) $clManager->setShowAlphaTaxa(true);
$clid = $clManager->getClid();
$pid = $clManager->getPid();

if(array_key_exists('dllist_x',$_POST)){
	$clManager->downloadChecklistCsv();
	exit();
}
elseif(array_key_exists('printlist_x',$_POST)){
	$printMode = 1;
}

$isEditor = false;
if($IS_ADMIN || (array_key_exists("ClAdmin",$USER_RIGHTS) && in_array($clid,$USER_RIGHTS["ClAdmin"]))){
	$isEditor = true;
}
if($isEditor){
	//Add species to checklist
	if(array_key_exists("tidtoadd",$_POST) && is_numeric($_POST["tidtoadd"])){
		$dataArr = array();
		$dataArr["tid"] = $_POST["tidtoadd"];
		if($_POST["familyoverride"]) $dataArr["familyoverride"] = $_POST["familyoverride"];
		if($_POST["habitat"]) $dataArr["habitat"] = $_POST["habitat"];
		if($_POST["abundance"]) $dataArr["abundance"] = $_POST["abundance"];
		if($_POST["notes"]) $dataArr["notes"] = $_POST["notes"];
		if($_POST["source"]) $dataArr["source"] = $_POST["source"];
		if($_POST["internalnotes"]) $dataArr["internalnotes"] = $_POST["internalnotes"];
		$statusStr = $clManager->addNewSpecies($dataArr);
	}
}
$taxaArray = $clManager->getTaxaList($pageNumber,($printMode?0:500));
?>
<html>
<head>
	<meta charset="<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE; ?><?php echo $LANG['RESCHECK'];?><?php echo $clManager->getClName(); ?></title>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link type="text/css" href="../css/jquery-ui.css" rel="stylesheet" />
	<script type="text/javascript" src="../js/jquery.js"></script>
	<script type="text/javascript" src="../js/jquery-ui.js"></script>
	<script type="text/javascript">
		<?php include_once($SERVER_ROOT.'/config/googleanalytics.php'); ?>
	</script>
	<script type="text/javascript">
		<?php if($clid) echo 'var clid = '.$clid.';'; ?>
		$( function() {
			$( document ).tooltip();
		} );

	</script>
	<script type="text/javascript" src="../js/symb/checklists.checklist.js?ver=201812"></script>
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
		<?php
		if($printMode){
			?>
			body{ background-color:#ffffff;  }
			#innertext{ background-color:#ffffff; }
			#taxaDiv{ line-height: 1em; }
			.printoff{ display:none; }
			a{ color: currentColor; cursor: none; pointer-events: none; text-decoration: none; }
			<?php
		}
		?>
	</style>
</head>
<body>
<?php
	$displayLeftMenu = (isset($checklists_checklistMenu)?$checklists_checklistMenu:false);
	if(!$printMode) include($SERVER_ROOT.'/header.php');
	echo '<div class="navpath printoff">';
	if($pid){
		echo '<a href="../index.php">'.$LANG['NAV_HOME'].'</a> &gt; ';
		echo '<a href="'.$CLIENT_ROOT.'/projects/index.php?pid='.$pid.'">';
		echo $clManager->getProjName();
		echo '</a> &gt; ';
		echo '<b>'.$clManager->getClName().'</b>';
	}
	else{
		if(isset($checklists_checklistCrumbs)){
			if($checklists_checklistCrumbs){
				echo $checklists_checklistCrumbs;
				echo " <b>".$clManager->getClName()."</b>";
			}
		}
		else{
			echo '<a href="../index.php">'.$LANG['NAV_HOME'].'</a> &gt;&gt; ';
			echo ' <b>'.$clManager->getClName().'</b>';
		}
	}
	echo '</div>';
	?>
	<!-- This is inner text! -->
	<div id='innertext'>
		<?php
		if($clid || $dynClid){
			if($clid && $isEditor){
				?>
				<div class="printoff" style="float:right;width:90px;">
					<span style="">
						<a href="checklistadmin.php?clid=<?php echo $clid.'&pid='.$pid; ?>" style="margin-right:10px;" title="Checklist Administration">
							<img style="border:0px;height:15px;" src="../images/editadmin.png" />
						</a>
					</span>
					<span style="">
						<a href="voucheradmin.php?clid=<?php echo $clid.'&pid='.$pid; ?>" style="margin-right:10px;" title="Manage Linked Voucher">
							<img style="border:0px;height:15px;" src="../images/editvoucher.png" />
						</a>
					</span>
					<span style="" onclick="toggle('editspp');return false;">
						<a href="#" title="Edit Species List">
							<img style="border:0px;height:15px;" src="../images/editspp.png" />
						</a>
					</span>
				</div>
				<?php
			}
			?>
			<div style="float:left;color:#990000;font-size:20px;font-weight:bold;">
				<a href="checklist.php?clid=<?php echo $clid."&pid=".$pid."&dynclid=".$dynClid; ?>">
					<?php echo $clManager->getClName(); ?>
				</a>
			</div>
			<?php
			if($activateKey){
				?>
				<div class="printoff" style="float:left;padding:5px;">
					<a href="../ident/key.php?clid=<?php echo $clid."&pid=".$pid."&dynclid=".$dynClid;?>&taxon=All+Species">
						<img src='../images/key.png' style="width:15px;border:0px;" title='Open Symbiota Key' />
					</a>
				</div>
				<?php
			}
			if($taxaArray){
				?>
				<div class="printoff" style="padding:5px;">
					<ul id="sddm">
						<li>
							<span onmouseover="mopen('m1')" onmouseout="mclosetime()">
								<img src="../images/games/games.png" style="height:17px;" title="Access Species List Games" />
							</span>
							<div id="m1" onmouseover="mcancelclosetime()" onmouseout="mclosetime()">
								<?php
								$varStr = "?clid=".$clid."&dynclid=".$dynClid."&listname=".$clManager->getClName()."&taxonfilter=".$taxonFilter."&showcommon=".$showCommon.($clManager->getThesFilter()?"&thesfilter=".$clManager->getThesFilter():"");
								?>
								<a href="../games/namegame.php<?php echo $varStr; ?>"><?php echo $LANG['NAMEGAME'];?></a>
								<a href="../games/flashcards.php<?php echo $varStr; ?>"><?php echo $LANG['FLASH'];?></a>
							</div>
						</li>
					</ul>
				</div>
				<?php
			}
			echo '<div style="clear:both;"></div>';
			$argStr = "&clid=".$clid."&dynclid=".$dynClid.($showCommon?"&showcommon=".$showCommon:"").($showSynonyms?"&showsynonyms=".$showSynonyms:"").($showVouchers?"&showvouchers=".$showVouchers:"");
			$argStr .= ($showAuthors?"&showauthors=".$showAuthors:"").($clManager->getThesFilter()?"&thesfilter=".$clManager->getThesFilter():"");
			$argStr .= ($pid?"&pid=".$pid:"").($showImages?"&showimages=".$showImages:"").($taxonFilter?"&taxonfilter=".$taxonFilter:"");
			$argStr .= ($searchCommon?"&searchcommon=".$searchCommon:"").($searchSynonyms?"&searchsynonyms=".$searchSynonyms:"");
			$argStr .= ($showAlphaTaxa?"&showalphataxa=".$showAlphaTaxa:"");
			$argStr .= ($defaultOverride?"&defaultoverride=".$defaultOverride:"");
			//Do not show certain fields if Dynamic Checklist ($dynClid)
			if($clid){
				if($clArray['type'] == 'rarespp'){
					echo '<div style="clear:both;"><b>'.(isset($LANG['SENSITIVE_SPECIES'])?$LANG['SENSITIVE_SPECIES']:'Sensitive species checklist for').':</b> '.$clArray["locality"].'</div>';
					if($isEditor && $clArray["locality"]){
						include_once($SERVER_ROOT.'/classes/OccurrenceMaintenance.php');
						$occurMaintenance = new OccurrenceMaintenance();
						echo '<div style="margin-left:15px">'.(isset($LANG['NUMBER_PENDING'])?$LANG['NUMBER_PENDING']:'Number of specimens pending protection').': ';
						if($action == 'protectspp'){
							$occurMaintenance->protectStateRareSpecies($clid,$clArray["locality"]);
							echo '0';
						}
						elseif($action == 'checkstatus'){
							$protectCnt = $occurMaintenance->getStateProtectionCount($clid, $clArray["locality"]);
							echo $protectCnt;
							if($protectCnt){
								echo '<span style="margin-left:10px"><a href="checklist.php?submitaction=protectspp'.$argStr.'">';
								echo '<button style="font-size:70%">'.(isset($LANG['PROTECT_LOCALITY'])?$LANG['PROTECT_LOCALITY']:'Protect Localities').'</button>';
								echo '</a></span>';
							}
						}
						else{
							echo '<span style="margin-left:10px"><a href="checklist.php?submitaction=checkstatus'.$argStr.'">';
							echo '<button style="font-size:70%">'.(isset($LANG['CHECK_STATUS'])?$LANG['CHECK_STATUS']:'Check Status').'</button>';
							echo '</a></span>';
						}
						echo '</div>';
					}
				}
				elseif($clArray['type'] == 'excludespp'){
					$parentArr = $clManager->getParentChecklist();
					echo '<div style="clear:both;">'.(isset($LANG['EXCLUSION_LIST'])?$LANG['EXCLUSION_LIST']:'Exclusion Species List for').' <b><a href="checklist.php?pid='.$pid.'&clid='.key($parentArr).'">'.current($parentArr).'</a></b></div>';
				}
				if($childArr = $clManager->getChildClidArr()){
					echo '<div style="float:left;">'.(isset($LANG['INCLUDE_TAXA'])?$LANG['INCLUDE_TAXA']:'Includes taxa from following child checklists').':</div>';
					echo '<div style="margin-left:10px;float:left">';
					foreach($childArr as $childClid => $childName){
						echo '<div style="clear:both;"><b><a href="checklist.php?pid='.$pid.'&clid='.$childClid.'">'.$childName.'</a></b></div>';
					}
					echo '</div>';
				}
				if($exclusionArr = $clManager->getExclusionChecklist()){
					echo '<div class="printoff" style="clear:both">'.(isset($LANG['TAXA_EXCLUDED'])?$LANG['TAXA_EXCLUDED']:'Taxa explicitly excluded').': <b><a href="checklist.php?pid='.$pid.'&clid='.key($exclusionArr).'">'.current($exclusionArr).'</a></b></div>';
				}
				?>
				<div style="clear:both;">
					<span style="font-weight:bold;">
						<?php echo $LANG['AUTHORS'];?>
					</span>
					<?php echo $clArray["authors"]; ?>
				</div>
				<?php
				if($clArray["publication"]){
					$pubStr = $clArray["publication"];
					if(substr($pubStr,0,4)=='http' && !strpos($pubStr,' ')) $pubStr = '<a href="'.$pubStr.'" target="_blank">'.$pubStr."</a>";
					echo "<div><span style='font-weight:bold;'>".(isset($LANG['CITATION'])?$LANG['CITATION']:'Citation').":</span> ".$pubStr."</div>";
				}
			}

			if(($clArray["locality"] || ($clid && ($clArray["latcentroid"] || $clArray["abstract"])) || $clArray["notes"])){
				?>
				<div class="moredetails printoff" style="<?php echo (($showDetails)?'display:none;':''); ?>color:blue;cursor:pointer;" onclick="toggle('moredetails')"><?php echo $LANG['MOREDETS'];?></div>
				<div class="moredetails printoff" style="display:<?php echo (($showDetails)?'block':'none'); ?>;color:blue;cursor:pointer;" onclick="toggle('moredetails')"><?php echo $LANG['LESSDETS'];?></div>
				<div class="moredetails" style="display:<?php echo (($showDetails || $printMode)?'block':'none'); ?>;">
					<?php
					$locStr = $clArray["locality"];
					if($clid && $clArray["latcentroid"]) $locStr .= " (".$clArray["latcentroid"].", ".$clArray["longcentroid"].")";
					if($locStr){
						echo "<div><span style='font-weight:bold;'>".$LANG['LOC']."</span>".$locStr."</div>";
					}
					if($clid && $clArray["abstract"]){
						echo "<div><span style='font-weight:bold;'>".$LANG['ABSTRACT']."</span>".$clArray["abstract"]."</div>";
					}
					if($clid && $clArray["notes"]){
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
				<!-- Option box -->
				<div class="printoff" id="cloptiondiv">
					<div style="">
						<form name="optionform" action="checklist.php" method="post">
							<fieldset style="background-color:white;padding-bottom:10px;">
								<legend><b><?php echo $LANG['OPTIONS'];?></b></legend>
								<!-- Taxon Filter option -->
								<div id="taxonfilterdiv">
									<div>
										<b><?php echo $LANG['SEARCH'];?></b>
										<input type="text" id="taxonfilter" name="taxonfilter" value="<?php echo $taxonFilter;?>" size="20" />
									</div>
									<div>
										<div style="margin-left:10px;">
											<?php
											if($DISPLAY_COMMON_NAMES){
												echo "<input type='checkbox' name='searchcommon' value='1'".($searchCommon?"checked":"")."/> ".$LANG['COMMON']."<br/>";
											}
											?>
											<input type="checkbox" name="searchsynonyms" value="1"<?php echo ($searchSynonyms?"checked":"");?>/> <?php echo $LANG['SYNON'];?>
										</div>
									</div>
								</div>
								<!-- Thesaurus Filter -->
								<div>
									<b><?php echo $LANG['FILTER'];?></b><br/>
									<select name='thesfilter'>
										<option value='0'><?php echo $LANG['OGCHECK'];?></option>
										<?php
										$taxonAuthList = Array();
										$taxonAuthList = $clManager->getTaxonAuthorityList();
										foreach($taxonAuthList as $taCode => $taValue){
											echo "<option value='".$taCode."'".($taCode == $clManager->getThesFilter()?" selected":"").">".$taValue."</option>\n";
										}
										?>
									</select>
								</div>
								<div id="showsynonymsdiv" style="display:<?php echo ($showImages?"none":"block");?>">
									<input name='showsynonyms' type='checkbox' value='1' <?php echo ($showSynonyms?"checked":""); ?> />
									<?php echo $LANG['DISPLAY_SYNONYMS'];?>
								</div>
								<?php
								if($DISPLAY_COMMON_NAMES){
									echo '<div>';
									echo "<input id='showcommon' name='showcommon' type='checkbox' value='1' ".($showCommon?"checked":"")."/> ".$LANG['COMMON']."";
									echo '</div>';
								}
								?>
								<div>
									<input name='showimages' type='checkbox' value='1' <?php echo ($showImages?"checked":""); ?> onclick="showImagesChecked(this.form);" />
									<?php echo $LANG['DISPLAYIMG'];?>
								</div>
								<?php
								if($clid){
									?>
									<div id="showvouchersdiv" style="display:<?php echo ($showImages?"none":"block");?>">
										<!-- Display as Vouchers: 0 = false, 1 = true  -->
										<input name='showvouchers' type='checkbox' value='1' <?php echo ($showVouchers?"checked":""); ?>/>
										<?php echo $LANG['NOTESVOUC'];?>
									</div>
									<?php
								}
								?>
								<div id="showauthorsdiv" style='display:<?php echo ($showImages?"none":"block");?>'>
									<!-- Display Taxon Authors: 0 = false, 1 = true  -->
									<input name='showauthors' type='checkbox' value='1' <?php echo ($showAuthors?"checked":""); ?>/>
									<?php echo $LANG['TAXONAUT'];?>
								</div>
								<div style='' id="showalphataxadiv">
									<!-- Display Taxa Alphabetically: 0 = false, 1 = true  -->
									<input name='showalphataxa' type='checkbox' value='1' <?php echo ($showAlphaTaxa?"checked":""); ?>/>
									<?php echo $LANG['TAXONABC'];?>
								</div>
								<div style="margin:5px 0px 0px 5px;">
									<input type='hidden' name='clid' value='<?php echo $clid; ?>' />
									<input type='hidden' name='dynclid' value='<?php echo $dynClid; ?>' />
									<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
									<input type='hidden' name='defaultoverride' value='1' />
									<?php if(!$taxonFilter) echo "<input type='hidden' name='pagenumber' value='".$pageNumber."' />"; ?>
									<input type="submit" name="submitaction" value="Rebuild List" onclick="changeOptionFormAction('checklist.php?clid=<?php echo $clid."&pid=".$pid."&dynclid=".$dynClid; ?>','_self');" />
									<div class="button" style='float:right;margin-right:10px;width:16px;height:16px;padding:2px;' title="Download Checklist">
										<input type="image" name="dllist" value="Download List" src="../images/dl.png" onclick="changeOptionFormAction('checklist.php?clid=<?php echo $clid."&pid=".$pid."&dynclid=".$dynClid; ?>','_self');" />
									</div>
									<div class="button" style='float:right;margin-right:10px;width:16px;height:16px;padding:2px;' title="Print in Browser">
										<input type="image" name="printlist" value="Print List" src="../images/print.png" onclick="changeOptionFormAction('checklist.php','_blank');" />
									</div>
									<div class="button" id="wordicondiv" style='float:right;margin-right:10px;width:16px;height:16px;padding:2px;<?php echo ($showImages?'display:none;':''); ?>' title="Export to DOCX">
										<input type="image" name="exportdoc" value="Export to DOCX" src="../images/wordicon.png" onclick="changeOptionFormAction('mswordexport.php','_self');" />
									</div>
								</div>
							</fieldset>
						</form>
					</div>
					<?php
					if($clid && $isEditor){
						?>
						<div class="editspp" style="display:<?php echo ($editMode?'block':'none'); ?>;width:250px;">
							<form id='addspeciesform' action='checklist.php' method='post' name='addspeciesform' onsubmit="return validateAddSpecies(this);">
								<fieldset style='margin:5px 0px 5px 5px;background-color:#FFFFCC;'>
									<legend><b><?php echo $LANG['NEWSPECIES'];?></b></legend>
									<div>
										<b><?php echo $LANG['TAXON'];?>:</b><br/>
										<input type="text" id="speciestoadd" name="speciestoadd" style="width:174px;" />
										<input type="hidden" id="tidtoadd" name="tidtoadd" value="" />
									</div>
									<div>
										<b><?php echo $LANG['FAMOVER'];?></b><br/>
										<input type="text" name="familyoverride" style="width:122px;" title="Only enter if you want to override current family" />
									</div>
									<div>
										<b><?php echo $LANG['HABITAT'];?></b><br/>
										<input type="text" name="habitat" style="width:170px;" />
									</div>
									<div>
										<b><?php echo $LANG['ABUN'];?></b><br/>
										<input type="text" name="abundance" style="width:145px;" />
									</div>
									<div>
										<b><?php echo $LANG['NOTES'];?></b><br/>
										<input type="text" name="notes" style="width:175px;" />
									</div>
									<div style="padding:2px;">
										<b><?php echo $LANG['INTNOTES'];?></b><br/>
										<input type="text" name="internalnotes" style="width:126px;" title="Displayed to administrators only" />
									</div>
									<div>
										<b><?php echo $LANG['SOURCE'];?></b><br/>
										<input type="text" name="source" style="width:167px;" />
									</div>
									<div>
										<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
										<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
										<input type='hidden' name='showsynonyms' value='<?php echo $showSynonyms; ?>' />
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
										<a href="tools/checklistloader.php?clid=<?php echo $clid.'&pid='.$pid;?>"><?php echo $LANG['BATCHSPREAD'];?></a>
									</div>
								</fieldset>
							</form>
						</div>
						<?php
					}
					if(!$showImages){
						?>
						<div style="text-align:center;padding:10px">
							<?php
							$coordArr = $clManager->getVoucherCoordinates(0,true);
							//$googleUrl = '//maps.googleapis.com/maps/api/staticmap?size=170x170&maptype=terrain';
							$googleUrl = $CLIENT_ROOT.'/images/world.png?';
							if(array_key_exists('GOOGLE_MAP_KEY',$GLOBALS) && $GLOBALS['GOOGLE_MAP_KEY']) $googleUrl .= '&key='.$GLOBALS['GOOGLE_MAP_KEY'];
							if($coordArr){
								//$googleUrl .= '&markers=size:tiny|'.implode('|',$coordArr);
								?>
								<span title="Display Vouchers in Simply Map">
									<a href="checklistmap.php?clid=<?php echo $clid.'&thesfilter='.$thesFilter.'&taxonfilter='.$taxonFilter; ?>" target="_blank">
										<img src="<?php echo $googleUrl; ?>" style="border:0px;width:30px" />
									</a>
								</span>
								<?php
							}
							if($coordArr){
								?>
								<span style="margin:5px">
									<a href="../collections/map/index.php?clid=<?php echo $clid.'&cltype=vouchers&taxonfilter='.$taxonFilter; ?>&db=all&type=1&reset=1" target="_blank"><img src="../images/world.png" style="width:30px" title="<?php echo (isset($LANG['VOUCHERS_DYNAMIC_MAP'])?$LANG['VOUCHERS_DYNAMIC_MAP']:'Display Vouchers in Dynamic Map'); ?>" /></a>
								</span>
								<?php
							}
							if(false && $clArray['dynamicsql']){
								?>
								<span style="margin:5px">
									<a href="../collections/map/index.php?clid=<?php echo $clid.'&cltype=all&taxonfilter='.$taxonFilter; ?>&db=all&type=1&reset=1" target="_blank">
										<?php
										if($coordArr){
											echo '<img src="../images/world.png" style="width:30px" title="'.(isset($LANG['OCCUR_DYNAMIC_MAP'])?$LANG['OCCUR_DYNAMIC_MAP']:'Display All Occurrence in Dynamic Map').'" />';
										}
										else{
											$polygonCoordArr = $clManager->getPolygonCoordinates();
											$googleUrl .= '&markers=size:tiny|'.implode('|',$polygonCoordArr);
											echo '<img src="'.$googleUrl.'" style="border:0px;" /><br/>';
										}
										?>
									</a>
								</span>
								<?php
							}
							?>
						</div>
					<?php
					}
					?>
				</div>
				<div>
					<div style="margin:3px;">
						<?php
						echo '<b>'.$LANG['FAMILIES'].'</b>: ';
						echo $clManager->getFamilyCount();
						?>
					</div>
					<div style="margin:3px;">
						<?php
						echo '<b>'.$LANG['GENERA'].'</b>: ';
						echo $clManager->getGenusCount();
						?>
					</div>
					<div style="margin:3px;">
						<?php
						echo '<b>'.$LANG['SPECIES'].'</b>: ';
						echo $clManager->getSpeciesCount();
						?>
					</div>
					<div style="margin:3px;">
						<?php
						echo '<b>';
						echo $LANG['TOTTAX'];
						echo '<span class="printoff"> (<a href="http://symbiota.org/docs/symbiota-species-checklist-data-fields/" target="_blank" >';
						echo '<span style="font-style:italic;color:green" title="A species name and a single infraspecific taxon is assumed to reference a parent-child relationship of a sinlge taxon. Infraspecific taxa only increase taxa counts when more than one have been added to the checklists for a given species. For more information, click here." >?</span>';
						echo '</a>)</span>';
						echo '</b>: ';
						echo $clManager->getTaxaCount();
						?>
					</div>
					<?php
					$taxaLimit = ($showImages?$clManager->getImageLimit():$clManager->getTaxaLimit());
					$pageCount = ceil($clManager->getTaxaCount()/$taxaLimit);
					if($pageCount > 1){
						if(($pageNumber)>$pageCount) $pageNumber = 1;
						echo '<hr /><div class="printoff">'.$LANG['PAGE']."<b> ".($pageNumber)."</b>".$LANG['OF']."<b>$pageCount</b>: ";
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
					if($showImages){
						$prevfam = '';
						foreach($taxaArray as $tid => $sppArr){
							$tu = (array_key_exists('tnurl',$sppArr)?$sppArr['tnurl']:'');
							$u = (array_key_exists('url',$sppArr)?$sppArr['url']:'');
							$imgSrc = ($tu?$tu:$u);
							?>
							<div class="tndiv">
								<div class="tnimg" style="<?php echo ($imgSrc?"":"border:1px solid black;"); ?>">
									<?php
									$spUrl = "../taxa/index.php?taxauthid=1&taxon=$tid&clid=".$clid;
									if($imgSrc){
										$imgSrc = (array_key_exists("imageDomain",$GLOBALS)&&substr($imgSrc,0,4)!="http"?$GLOBALS["imageDomain"]:"").$imgSrc;
										echo "<a href='".$spUrl."' target='_blank'>";
										echo "<img src='".$imgSrc."' />";
										echo "</a>";
									}
									else{
										?>
										<div style="margin-top:50px;">
											<b><?php echo $LANG['IMAGE'];?><br/><?php echo $LANG['NOTY'];?><br/><?php echo $LANG['AVAIL'];?></b>
										</div>
										<?php
									}
									?>
								</div>
								<div style="clear:both">
									<?php
									echo '<a href="'.$spUrl.'" target="_blank">';
									echo '<b>'.$sppArr['sciname'].'</b>';
									echo '</a>';
									?>
									<div class="editspp printoff" style="float:left;<?php echo ($editMode?'':'display:none'); ?>;">
										<?php
										$clidArr = explode(',',$sppArr['clid']);
										foreach($clidArr as $id){
											?>
											<a href="#" onclick="return openPopup('clsppeditor.php?tid=<?php echo $tid."&clid=".$id; ?>','editorwindow');">
												<img src='../images/edit.png' style='width:13px;' title='edit details' />
											</a>
											<?php
										}
										?>
									</div>
									<?php
									if(array_key_exists('vern',$sppArr)){
										echo "<div style='font-weight:bold;'>".$sppArr["vern"]."</div>";
									}
									if(!$showAlphaTaxa){
										$family = $sppArr['family'];
										if($family != $prevfam){
											?>
											<div class="familydiv" id="<?php echo $family; ?>">
												[<?php echo $family; ?>]
											</div>
											<?php
											$prevfam = $family;
										}
									}
									?>
								</div>
							</div>
							<?php
						}
					}
					else{
						//Display taxa
						echo '<div id="taxaDiv">';
						$voucherArr = array();
						if($showVouchers) $voucherArr = $clManager->getVoucherArr();
						$prevGroup = '';
						foreach($taxaArray as $tid => $sppArr){
							$group = $sppArr['taxongroup'];
							if($group != $prevGroup){
								$famUrl = "../taxa/index.php?taxauthid=1&taxon=$group&clid=".$clid;
								?>
								<div class="familydiv" id="<?php echo $group;?>" style="margin:12px 0px 3px 0px;font-weight:bold;">
									<a href="<?php echo $famUrl; ?>" target="_blank" style="color:black;"><?php echo $group;?></a>
								</div>
								<?php
								$prevGroup = $group;
							}
							echo "<div id='tid-$tid' style='margin:0px 0px 3px 10px;'>";
							echo '<div style="clear:left">';
							if(!preg_match('/\ssp\d/',$sppArr["sciname"])) echo '<a href="../taxa/index.php?taxauthid=1&taxon='.$tid.'&clid='.$clid.'" target="_blank">';
							echo "<b><i>".$sppArr["sciname"]."</b></i> ";
							if(array_key_exists("author",$sppArr)) echo $sppArr["author"];
							if(!preg_match('/\ssp\d/',$sppArr["sciname"])) echo "</a>";
							if(array_key_exists('vern',$sppArr)){
								echo " - <span style='font-weight:bold;'>".$sppArr["vern"]."</span>";
							}
							$clidArr = array();
							if(isset($sppArr['clid'])) $clidArr = explode(',',$sppArr['clid']);
							if($clArray["dynamicsql"]){
								?>
								<span class="printoff" style="margin:0px 10px">
									<a href="../collections/list.php?usethes=1&taxontype=2&taxa=<?php echo $tid."&targetclid=".$clid."&targettid=".$tid;?>" target="_blank">
										<img src="../images/list.png" style="width:12px;" title="<?php echo (isset($LANG['VIEW_RELATED'])?$LANG['VIEW_RELATED']:'View Related Specimens'); ?>" />
									</a>
								</span>
								<?php
							}
							if($isEditor){
								//Delete species or edit details specific to this taxon (vouchers, notes, habitat, abundance, etc
								foreach($clidArr as $id){
									?>
									<span class="editspp" style="<?php echo ($editMode?'':'display:none'); ?>;">
										<a href="#" onclick="return openPopup('clsppeditor.php?tid=<?php echo $tid."&clid=".$id; ?>','editorwindow');">
											<img src="../images/edit.png" style="width:13px;" title="edit details (clid = <?php echo $id; ?>)" />
										</a>
									</span>
									<?php
								}
								if(in_array($clid, $clidArr) && $showVouchers && $clArray['dynamicsql']){
									?>
									<span class="editspp" style="margin-left:5px;display:none">
										<a href="../collections/list.php?usethes=1&taxontype=2&taxa=<?php echo $tid."&targetclid=".$clid."&targettid=".$tid.'&mode=voucher'; ?>" target="_blank">
											<img src="../images/link.png" style="width:12px;" title="<?php echo (isset($LANG['VIEW_RELATED'])?$LANG['VIEW_RELATED']:'Link Specimen Vouchers'); ?>" /><span style="font-size:70%">V</span>
										</a>
									</span>
									<?php
								}
							}
							echo "</div>\n";
							if($showSynonyms && isset($sppArr['syn'])){
								echo '<div style="margin-left:15px">['.$sppArr['syn'].']</div>';
							}
							if($showVouchers){
								$voucStr = '';
								if(array_key_exists($tid,$voucherArr)){
									$voucCnt = 0;
									foreach($voucherArr[$tid] as $occid => $collName){
										$voucStr .= ', ';
										if($voucCnt == 4 && !$printMode){
											$voucStr .= '<a href="#" id="morevouch-'.$tid.'" onclick="return toggleVoucherDiv('.$tid.');">'.$LANG['MORE'].'</a>'.
												'<span id="voucdiv-'.$tid.'" style="display:none;">';
										}
										$voucStr .= '<a href="#" onclick="return openIndividualPopup('.$occid.')">'.$collName."</a>\n";
										$voucCnt++;
									}
									if($voucCnt > 4 && !$printMode) $voucStr .= '</span><a href="#" id="lessvouch-'.$tid.'" style="display:none;" onclick="return toggleVoucherDiv('.$tid.');">'.$LANG['LESS'].'</a>';
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
						echo '</div>';
					}
					$taxaLimit = ($showImages?$clManager->getImageLimit():$clManager->getTaxaLimit());
					if($clManager->getTaxaCount() > (($pageNumber)*$taxaLimit)){
						echo '<div class="printoff" style="margin:20px;clear:both;">';
						echo '<a href="checklist.php?pagenumber='.($pageNumber+1).$argStr.'">'.$LANG['DISPLAYNEXT'].''.$taxaLimit.''.$LANG['TAXA'].'</a></div>';
					}
					if(!$taxaArray) echo "<h1 style='margin:40px;'>".$LANG['NOTAXA']."</h1>";
					?>
				</div>
			</div>
			<?php
		}
		else{
			?>
			<div style="color:red;">
				<?php echo $LANG['CHECKNULL'];?>
			</div>
			<?php
		}
		?>
	</div>
	<?php
	if(!$printMode) include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>