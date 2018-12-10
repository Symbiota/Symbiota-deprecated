<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistManager.php');
include_once($SERVER_ROOT.'/classes/ChecklistAdmin.php');
if($CHECKLIST_FG_EXPORT) include_once($SERVER_ROOT.'/classes/ChecklistFGExportManager.php');
include_once($SERVER_ROOT.'/content/lang/checklists/checklist.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

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
$showAlphaTaxa = array_key_exists("showalphataxa",$_REQUEST)?$_REQUEST["showalphataxa"]:0; 
$searchCommon = array_key_exists("searchcommon",$_REQUEST)?$_REQUEST["searchcommon"]:0;
$searchSynonyms = array_key_exists("searchsynonyms",$_REQUEST)?$_REQUEST["searchsynonyms"]:0;
$defaultOverride = array_key_exists("defaultoverride",$_REQUEST)?$_REQUEST["defaultoverride"]:0;
$editMode = array_key_exists("emode",$_REQUEST)?$_REQUEST["emode"]:0; 
$printMode = array_key_exists("printmode",$_REQUEST)?$_REQUEST["printmode"]:0; 
$exportDoc = array_key_exists("exportdoc",$_REQUEST)?$_REQUEST["exportdoc"]:0;

$statusStr='';
$locStr = '';

//Search Synonyms is default
if($action != "Rebuild List" && !array_key_exists('dllist_x',$_POST)) $searchSynonyms = 1;
if($action == "Rebuild List") $defaultOverride = 1;

$clManager = new ChecklistManager();
if($clValue){
	$statusStr = $clManager->setClValue($clValue);
}
elseif($dynClid){
	$clManager->setDynClid($dynClid);
}
$clArray = Array();
if($clValue || $dynClid){
	$clArray = $clManager->getClMetaData();
}
$activateKey = $KEY_MOD_IS_ACTIVE;
$showDetails = 0;
if($clValue && $clArray["defaultSettings"]){
	$defaultArr = json_decode($clArray["defaultSettings"], true);
	$showDetails = $defaultArr["ddetails"];
	if(!$defaultOverride){
		if(array_key_exists('dcommon',$defaultArr)){$showCommon = $defaultArr["dcommon"];}
		if(array_key_exists('dimages',$defaultArr)){$showImages = $defaultArr["dimages"];} 
		if(array_key_exists('dvouchers',$defaultArr)){$showVouchers = $defaultArr["dvouchers"];}
		if(array_key_exists('dauthors',$defaultArr)){$showAuthors = $defaultArr["dauthors"];}
		if(array_key_exists('dalpha',$defaultArr)){$showAlphaTaxa = $defaultArr["dalpha"];}
	}
	if(isset($defaultArr['activatekey'])) $activateKey = $defaultArr['activatekey'];
}
if($pid) $clManager->setProj($pid);
elseif(array_key_exists("proj",$_REQUEST)) $pid = $clManager->setProj($_REQUEST['proj']);
if($thesFilter) $clManager->setThesFilter($thesFilter);
if($taxonFilter) $clManager->setTaxonFilter($taxonFilter);
$clManager->setLanguage($LANG_TAG);
if($searchCommon){
	$showCommon = 1;
	$clManager->setSearchCommon();
}
if($searchSynonyms) $clManager->setSearchSynonyms();
if($showAuthors) $clManager->setShowAuthors();
if($showCommon) $clManager->setShowCommon();
if($showImages) $clManager->setShowImages();
if($showVouchers) $clManager->setShowVouchers();
if($showAlphaTaxa) $clManager->setShowAlphaTaxa();
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
		$setRareSpp = false;
		if($_POST["cltype"] == 'rarespp') $setRareSpp = true;
		$clAdmin = new ChecklistAdmin();
		$clAdmin->setClid($clid);
		$statusStr = $clAdmin->addNewSpecies($dataArr,$setRareSpp);
	}
}
$taxaArray = Array();
if($clValue || $dynClid){
	$taxaArray = $clManager->getTaxaList($pageNumber,($printMode?0:500));
    if($CHECKLIST_FG_EXPORT){
        $fgManager = new ChecklistFGExportManager();
        if($clValue){
            $fgManager->setClValue($clValue);
        }
        elseif($dynClid){
            $fgManager->setDynClid($dynClid);
        }
        $fgManager->setSqlVars();
        $fgManager->setLanguage($LANG_TAG);
        $fgManager->primeDataArr();
    }
}
if($clArray["locality"]){
    $locStr = $clArray["locality"];
    if($clValue && $clArray["latcentroid"]) $locStr .= " (".$clArray["latcentroid"].", ".$clArray["longcentroid"].")";
}
?>
<html>
<head>
	<meta charset="<?php echo $CHARSET; ?>">
	<title><?php echo $DEFAULT_TITLE; ?><?php echo $LANG['RESCHECK'];?><?php echo $clManager->getClName(); ?></title>
    <link type="text/css" href="../css/bootstrap.css" rel="stylesheet" />
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link href="<?php echo $CLIENT_ROOT; ?>/css/jquery-ui.css" type="text/css" rel="stylesheet" />
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery-ui.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/jquery.popupoverlay.js" type="text/javascript"></script>
    <script src="<?php echo $CLIENT_ROOT; ?>/js/images-loaded.js" type="text/javascript"></script>
    <script src="https://unpkg.com/masonry-layout@4/dist/masonry.pkgd.min.js"></script>
    <script type="text/javascript">
		<?php include_once($SERVER_ROOT.'/config/googleanalytics.php'); ?>
	</script>
	<script type="text/javascript">
        <?php if($clid) echo 'var clid = '.$clid.';'; ?>

        <?php if($clManager->getClName()) echo 'var checklistName = "'.$clManager->getClName().'";'; ?>

        var checklistName = "<?php echo $clManager->getClName(); ?>";
        var checklistAuthors = "<?php echo $clArray["authors"]; ?>";
        var checklistCitation = "<?php echo $clArray["publication"]; ?>";
        var checklistLocality = "<?php echo $locStr; ?>";
        var checklistAbstract = "<?php echo $clArray["abstract"]; ?>";
        var checklistNotes = "<?php echo $clArray["notes"]; ?>";
        var fieldguideDisclaimer = "This field guide was produced through the <?php echo $DEFAULT_TITLE; ?> portal. This field guide is intended for educational use only, no commercial uses are allowed. It is created under Fair Use copyright provisions supporting educational uses of information. All rights are reserved to authors and photographers unless otherwise specified.";

        function lazyLoadData(index,callback){
            var startindex = 0;
            if(index > 0) startindex = (index*lazyLoadCnt) + 1;
            var http = new XMLHttpRequest();
            var url = "rpc/fieldguideexporter.php";
            var params = 'rows='+lazyLoadCnt+'&photogArr='+JSON.stringify(photog)+'&photoNum='+photoNum+'&start='+startindex+'&cl=<?php echo $clValue."&pid=".$pid."&dynclid=".$dynClid."&thesfilter=".($thesFilter?$thesFilter:1); ?>';
            //console.log(url+'?'+params);
            http.open("POST", url, true);
            http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
            http.onreadystatechange = function() {
                if(http.readyState == 4 && http.status == 200) {
                    callback(http.responseText);
                }
            };
            http.send(params);
        }
	</script>
	<script type="text/javascript" src="../js/symb/checklists.checklist.js?ver=201805"></script>
    <?php
    if($CHECKLIST_FG_EXPORT){
        ?>
        <script src="<?php echo $CLIENT_ROOT; ?>/js/pdfmake.min.js" type="text/javascript"></script>
        <script src="<?php echo $CLIENT_ROOT; ?>/js/vfs_fonts.js" type="text/javascript"></script>
        <script src="<?php echo $CLIENT_ROOT; ?>/js/jszip.min.js" type="text/javascript"></script>
        <script src="<?php echo $CLIENT_ROOT; ?>/js/FileSaver.min.js" type="text/javascript"></script>
        <script src="<?php echo $CLIENT_ROOT; ?>/js/symb/checklists.fieldguideexport.js?ver=59" type="text/javascript"></script>
        <?php
    }
    ?>
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

        a.boxclose{
            float:right;
            width:36px;
            height:36px;
            background:transparent url(../images/spatial_close_icon.png) repeat top left;
            margin-top:-35px;
            margin-right:-35px;
            cursor:pointer;
        }

        #loader {
            position: absolute;
            left: 50%;
            top: 50%;
            z-index: 1;
            width: 150px;
            height: 150px;
            margin: -75px 0 0 -75px;
            border: 16px solid #f3f3f3;
            border-radius: 50%;
            border-top: 16px solid #3498db;
            width: 120px;
            height: 120px;
            -webkit-animation: spin 2s linear infinite;
            animation: spin 2s linear infinite;
        }

        #loaderMessage {
            position: absolute;
            top: 65%;
            z-index: 1;
            font-size: 25px;
            font-weight: bold;
            text-align: center;
            width: 100%;
            color: #f3f3f3;
        }
        .checklist-left {
            width: 50%;
            float: left;
            padding-right: 10px;
        }
        .checklist-right {
            width: 50%;
            float: left;
            padding-left: 10px;
        }

        @media screen and (max-width:700px){
            .checklist-left {
                width: 100%;
                float: none;
                padding: 0;
            }
            .checklist-right {
                width: 100%;
                float: none;
                padding: 0;
            }
        }
        @-webkit-keyframes spin {
            0% { -webkit-transform: rotate(0deg); }
            100% { -webkit-transform: rotate(360deg); }
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
	</style>
</head>

<body <?php echo ($printMode?'style="background-color:#ffffff;"':''); ?>>
<?php
	if(!$printMode){
		$displayLeftMenu = (isset($checklists_checklistMenu)?$checklists_checklistMenu:false);
		include($SERVER_ROOT.'/header.php');
		echo '<div class="navpath">';
		if($pid){
			echo '<a href="../index.php">'.$LANG['NAV_HOME'].'</a> &gt; ';
			echo '<a href="'.$clientRoot.'/projects/index.php?pid='.$pid.'">';
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
	}
	?>
	<!-- This is inner text! -->
	<div id='innertext' style="<?php echo ($printMode?'background-color:#ffffff;':''); ?>">
		<?php
		if($clValue || $dynClid){
			if($clValue && $isEditor && !$printMode){
				?>
				<div style="float:right;width:90px;">
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


            <?php
			//Do not show certain fields if Dynamic Checklist ($dynClid)
			if($clValue){
				if($clArray['type'] == 'rarespp'){
					echo '<div style="clear:both;">';
					echo '<b>Sensitive species checklist for:</b> '.$clArray["locality"];
					echo '</div>';
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
			if($clValue && $clArray["parentclid"]== 54 && $clArray["iconurl"] !='') {//garden collection ?>
                <div class="checklist-left">
                    <img src="<?php echo $clArray['iconurl'] ?>" alt="<?php echo $clArray['iconurl'] ?>">
                </div>
                <div class="checklist-right">
                    <div style="float:left;color:#990000;font-size:20px;font-weight:bold;">
                        <a href="checklist.php?cl=<?php echo $clValue."&proj=".$pid."&dynclid=".$dynClid; ?>">
                            <?php echo $clManager->getClName(); ?>
                        </a>
                    </div>
                    <?php
                    if($activateKey && !$printMode){
                        ?>
                        <div style="float:left;padding:5px;">
                            <a href="../ident/key.php?cl=<?php echo $clValue."&proj=".$pid."&dynclid=".$dynClid;?>&taxon=All+Species">
                                <img src='../images/key.png' style="width:15px;border:0px;" title='Open Symbiota Key' />
                            </a>
                        </div>
                        <?php
                    }
                    if(!$printMode && $taxaArray){
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
                                        <a href="../games/namegame.php<?php echo $varStr; ?>"><?php echo $LANG['NAMEGAME'];?></a>
                                        <a href="../games/flashcards.php<?php echo $varStr; ?>"><?php echo $LANG['FLASH'];?></a>
                                    </div>
                                </li>
                            </ul>
                        </div>
                        <div style="clear:both;"></div>
                        <?php
                    } ?>
                    <div class="abstract"><?php echo $clArray['abstract'] ?></div>
                </div>
                <hr style="clear: both;">
             <?php }else { ?>
                <div style="float:left;color:#990000;font-size:20px;font-weight:bold;">
                    <a href="checklist.php?cl=<?php echo $clValue."&proj=".$pid."&dynclid=".$dynClid; ?>">
                        <?php echo $clManager->getClName(); ?>
                    </a>
                </div>
                <?php
                if($activateKey && !$printMode){
                    ?>
                    <div style="float:left;padding:5px;">
                        <a href="../ident/key.php?cl=<?php echo $clValue."&proj=".$pid."&dynclid=".$dynClid;?>&taxon=All+Species">
                            <img src='../images/key.png' style="width:15px;border:0px;" title='Open Symbiota Key' />
                        </a>
                    </div>
                    <?php
                }
                if(!$printMode && $taxaArray){
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
                                    <a href="../games/namegame.php<?php echo $varStr; ?>"><?php echo $LANG['NAMEGAME'];?></a>
                                    <a href="../games/flashcards.php<?php echo $varStr; ?>"><?php echo $LANG['FLASH'];?></a>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div style="clear:both;"></div>
                    <?php
                } ?>

                <?php
                if (($locStr || ($clValue && ($clArray["latcentroid"] || $clArray["abstract"])) || $clArray["notes"])) {
                    ?>
                    <div class="moredetails"
                         style="<?php echo(($showDetails || $printMode) ? 'display:none;' : ''); ?>color:blue;cursor:pointer;"
                         onclick="toggle('moredetails')"><?php echo $LANG['MOREDETS']; ?></div>
                    <div class="moredetails"
                         style="display:<?php echo(($showDetails && ! $printMode) ? 'block' : 'none'); ?>;color:blue;cursor:pointer;"
                         onclick="toggle('moredetails')"><?php echo $LANG['LESSDETS']; ?></div>
                    <div class="moredetails"
                         style="display:<?php echo(($showDetails || $printMode) ? 'block' : 'none'); ?>;">
                        <?php
                        if ($locStr) {
                            echo "<div><span style='font-weight:bold;'>".$LANG['LOC']."</span>".$locStr."</div>";
                        }
                        if ($clValue && $clArray["abstract"]) {
                            echo "<div><span style='font-weight:bold;'>".$LANG['ABSTRACT']."</span>".$clArray["abstract"]."</div>";
                        }
                        if ($clValue && $clArray["notes"]) {
                            echo "<div><span style='font-weight:bold;'>Notes: </span>".$clArray["notes"]."</div>";
                        }
                        ?>
                    </div>
                    <?php
                }
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
						<form name="optionform" action="checklist.php" method="post">
							<fieldset style="background-color:white;padding-bottom:10px;">
							    <legend><b><?php echo $LANG['OPTIONS'];?></b></legend>
								<!-- Taxon Filter option -->
							    <div id="taxonfilterdiv" title="Filter species list by family or genus">
							    	<div>
							    		<b><?php echo $LANG['SEARCH'];?></b>
										<input type="text" id="taxonfilter" name="taxonfilter" value="<?php echo $taxonFilter;?>" size="20" />
									</div>
									<div>
										<div style="margin-left:10px;">
											<?php 
												if($displayCommonNames){
													echo "<input data-role='none' type='checkbox' name='searchcommon' value='1'".($searchCommon?"checked":"")."/>".$LANG['COMMON']."<br/>";
												}
											?>
											<input data-role='none' type="checkbox" name="searchsynonyms" value="1"<?php echo ($searchSynonyms?"checked":"");?>/><?php echo $LANG['SYNON'];?>
                                        </div>
									</div>
								</div>
							    <!-- Thesaurus Filter -->
							    <div>
							    	<b><?php echo $LANG['FILTER'];?></b><br/>
							    	<select data-role='none' name='thesfilter' id='thesfilter'>
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
								<div>
									<?php 
										//Display Common Names: 0 = false, 1 = true 
									    if($displayCommonNames) echo "<input data-role='none' id='showcommon' name='showcommon' type='checkbox' value='1' ".($showCommon?"checked":"")."/>".$LANG['COMMON']."";
									?>
								</div>
								<div>
									<!-- Display as Images: 0 = false, 1 = true  --> 
								    <input data-role='none' name='showimages' type='checkbox' value='1' <?php echo ($showImages?"checked":""); ?> onclick="showImagesChecked(this.form);" />
                                    <?php echo $LANG['DISPLAYIMG'];?>
								</div>
								<?php if($clValue){ ?>
									<div style='display:<?php echo ($showImages?"none":"block");?>' id="showvouchersdiv">
										<!-- Display as Vouchers: 0 = false, 1 = true  --> 
									    <input data-role='none' name='showvouchers' type='checkbox' value='1' <?php echo ($showVouchers?"checked":""); ?>/>
                                        <?php echo $LANG['NOTESVOUC'];?>
									</div>
								<?php } ?>
								<div style='display:<?php echo ($showImages?"none":"block");?>' id="showauthorsdiv">
									<!-- Display Taxon Authors: 0 = false, 1 = true  --> 
								    <input data-role='none' name='showauthors' type='checkbox' value='1' <?php echo ($showAuthors?"checked":""); ?>/>
                                    <?php echo $LANG['TAXONAUT'];?>
								</div>
								<div style='' id="showalphataxadiv">
									<!-- Display Taxa Alphabetically: 0 = false, 1 = true  --> 
								    <input data-role='none' name='showalphataxa' type='checkbox' value='1' <?php echo ($showAlphaTaxa?"checked":""); ?>/>
                                    <?php echo $LANG['TAXONABC'];?>
								</div>
								<div style="margin:5px 0px 0px 5px;">
									<input type='hidden' name='cl' value='<?php echo $clid; ?>' />
									<input type='hidden' name='dynclid' value='<?php echo $dynClid; ?>' />
									<input type="hidden" name="proj" value="<?php echo $pid; ?>" />
									<input type='hidden' name='defaultoverride' value='1' />
									<?php if(!$taxonFilter) echo "<input type='hidden' name='pagenumber' value='".$pageNumber."' />"; ?>
									<input data-role='none' type="submit" name="submitaction" value="Rebuild List" onclick="changeOptionFormAction('checklist.php?cl=<?php echo $clValue."&proj=".$pid."&dynclid=".$dynClid; ?>','_self');" />
									<div class="button" style='float:right;margin-right:10px;width:16px;height:16px;padding:2px;' title="Download Checklist">
                                        <input data-role='none' type="image" name="dllist" value="Download List" src="../images/dl.png" onclick="changeOptionFormAction('checklist.php?cl=<?php echo $clValue."&proj=".$pid."&dynclid=".$dynClid; ?>','_self');" />
                                    </div>
									<div class="button" style='float:right;margin-right:10px;width:16px;height:16px;padding:2px;' title="Print in Browser">
										<input data-role='none' type="image" name="printlist" value="Print List" src="../images/print.png" onclick="changeOptionFormAction('checklist.php','_blank');" />
									</div>
									<div class="button" id="wordicondiv" style='float:right;margin-right:10px;width:16px;height:16px;padding:2px;<?php echo ($showImages?'display:none;':''); ?>' title="Export to DOCX">
										<input data-role='none' type="image" name="exportdoc" value="Export to DOCX" src="../images/wordicon.png" onclick="changeOptionFormAction('defaultchecklistexport.php','_self');" />
									</div>
								</div>
                                <?php
                                if($CHECKLIST_FG_EXPORT){
                                    ?>
                                    <div style="margin:5px 0px 0px 5px;clear:both;">
                                        <a class="" href="#" onclick="openFieldGuideExporter();"><b>Open Export Panel</b></a>
                                    </div>
                                    <?php
                                }
                                ?>
                            </fieldset>
						</form>
						<?php 
						if($clValue && $isEditor){
							?>
							<div class="editspp" style="display:<?php echo ($editMode?'block':'none'); ?>;width:250px;margin-top:10px;">
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
											<input type="hidden" name="cl" value="<?php echo $clid; ?>" />
											<input type="hidden" name="cltype" value="<?php echo $clArray['type']; ?>" />
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
											<a href="tools/checklistloader.php?clid=<?php echo $clid.'&pid='.$pid;?>"><?php echo $LANG['BATCHSPREAD'];?></a>
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
									<div>
										<a href="checklistmap.php?clid=<?php echo $clid.'&thesfilter='.$thesFilter.'&taxonfilter='.$taxonFilter; ?>" target="_blank">
											<?php 
											$googleUrl = '//maps.googleapis.com/maps/api/staticmap?size=170x170&maptype=terrain';
											if(array_key_exists('GOOGLE_MAP_KEY',$GLOBALS) && $GLOBALS['GOOGLE_MAP_KEY']) $googleUrl .= '&key='.$GLOBALS['GOOGLE_MAP_KEY'];
											$googleUrl .= '&markers=size:tiny|'.implode('|',$coordArr);
											?>
											<img src="<?php echo $googleUrl; ?>" style="border:0px;" /><br/>
											Simple Map
										</a>
									</div>
									<div>
										<a href="../collections/map/mapinterface.php?clid=<?php echo $clid.'&taxonfilter='.$taxonFilter; ?>&db=all&maptype=occquery&type=1&reset=1" target="_blank">
											Advanced Map
										</a>
									</div>
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
						<b><?php echo $LANG['FAMILIES'];?></b>
						<?php echo $clManager->getFamilyCount(); ?>
					</div>
					<div style="margin:3px;">
						<b><?php echo $LANG['GENERA'];?></b>
						<?php echo $clManager->getGenusCount(); ?>
					</div>
					<div style="margin:3px;">
						<b><?php echo $LANG['SPECIES'];?></b>
						<?php echo $clManager->getSpeciesCount(); ?>
                        <?php echo $LANG['SPECRANK'];?>
					</div>
					<div style="margin:3px;">
						<b><?php echo $LANG['TOTTAX'];?></b>
						<?php echo $clManager->getTaxaCount(); ?>
                        <?php echo $LANG['INCLUDSUB'];?>
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
                        $argStr .= ($showAlphaTaxa?"&showalphataxa=".$showAlphaTaxa:"");
						$argStr .= ($defaultOverride?"&defaultoverride=".$defaultOverride:"");
						echo "<hr /><div>".$LANG['PAGE']."<b>".($pageNumber)."</b>".$LANG['OF']."<b>$pageCount</b>: ";
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
						echo '<div class="images-wrapper">';
						foreach($taxaArray as $tid => $sppArr){
							$family = $sppArr['family'];
							$tu = (array_key_exists('tnurl',$sppArr)?$sppArr['tnurl']:'');
							$u = (array_key_exists('url',$sppArr)?$sppArr['url']:'');
							$imgSrc = ($tu?$tu:$u);
							?>
							<div class="grid-item">
								<div class="image-item">
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
											<b><?php echo $LANG['IMAGE'];?><br/><?php echo $LANG['NOTY'];?><br/><?php echo $LANG['AVAIL'];?></b>
										</div>
										<?php 
									}
									?>
								</div>
								<div class="image-overlay">
                                    <div class="centered">
                                        <?php
                                        if(!$printMode) echo '<a href="'.$spUrl.'" target="_blank">';
                                        echo '<b>'.$sppArr['sciname'].'</b>';
                                        if(!$printMode) echo '</a>';
                                        if(array_key_exists('vern',$sppArr)){
                                            echo "<div style='font-weight:bold;'>".$sppArr["vern"]."</div>";
                                        }
                                        if(!$showAlphaTaxa){
                                            //show family name, EVEN if it has already been displayed on the previous thumbnail
                                            //if($family != $prevfam){
                                                ?>
                                                <div class="familydiv" id="<?php echo $family; ?>">
                                                    [<?php echo $family; ?>]
                                                </div>
                                                <?php
                                                $prevfam = $family;
                                            //} //end if $prevfam
                                        }
                                        ?>
                                    </div>
								</div>
							</div>
							<?php 
						}
						echo "</div> <!-- .images-wrapper -->";
					}
					else{
						$voucherArr = $clManager->getVoucherArr();
						foreach($taxaArray as $tid => $sppArr){
							if(!$showAlphaTaxa){
								$family = $sppArr['family'];
								if($family != $prevfam){
									$famUrl = "../taxa/index.php?taxauthid=1&taxon=$family&cl=".$clid;
									?>
									<div class="familydiv" id="<?php echo $family;?>" style="margin:15px 0px 5px 0px;font-weight:bold;font-size:120%;">
										<a href="<?php echo $famUrl; ?>" target="_blank" style="color:black;"><?php echo $family;?></a>
									</div>
									<?php
									$prevfam = $family;
								}
							}
							$spUrl = "../taxa/index.php?taxauthid=1&taxon=$tid&cl=".$clid;
							echo "<div id='tid-$tid' style='margin:0px 0px 3px 10px;'>";
							echo '<div style="clear:left">';
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
								<span class="editspp" style="display:<?php echo ($editMode?'inline':'none'); ?>;">
									<a href="#" onclick="return openPopup('clsppeditor.php?tid=<?php echo $tid."&clid=".$clid; ?>','editorwindow');">
										<img src='../images/edit.png' style='width:13px;' title='edit details' />
									</a>
								</span>
								<?php 
								if($showVouchers && array_key_exists("dynamicsql",$clArray) && $clArray["dynamicsql"]){ 
									?>
									<span class="editspp" style="display:none;">
										<a href="#" onclick="return openPopup('../collections/list.php?db=all&thes=1&reset=1&taxa=<?php echo $tid."&targetclid=".$clid."&targettid=".$tid;?>','editorwindow');">
											<img src='../images/link.png' style='width:13px;' title='Link Voucher Specimens' />
										</a>
									</span>
									<?php
								} 
							}
							echo "</div>\n";
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
										if(!$printMode) $voucStr .= '<a href="#" onclick="return openIndividualPopup('.$occid.')">';
										$voucStr .= $collName;
										if(!$printMode) $voucStr .= "</a>\n";
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
					}
					$taxaLimit = ($showImages?$clManager->getImageLimit():$clManager->getTaxaLimit());
					if($clManager->getTaxaCount() > (($pageNumber)*$taxaLimit) && !$printMode){
						echo '<div style="margin:20px;clear:both;">';
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

	if($CHECKLIST_FG_EXPORT){
        ?>
        <!-- Field Guide Export -->
        <div id="fieldguideexport" data-role="popup" class="well" style="width:600px;min-height:250px;font-size:14px;">
            <a class="boxclose fieldguideexport_close" id="boxclose"></a>
            <h2>Fieldguide Export Settings</h2>

            <div style="margin-top:5px;">
                <b>Primary Description Source:</b>
                <select data-role='none' name='fgPriDescSource' id='fgPriDescSource'>
                    <?php
                    $descSourceList = Array();
                    $descSourceList = $fgManager->getDescSourceList();
                    foreach($descSourceList as $source){
                        echo "<option value='".$source."'>".$source."</option>\n";
                    }
                    ?>
                </select>
            </div>
            <div style="margin-top:5px;">
                <b>Secondary Description Source:</b>
                <select data-role='none' name='fgSecDescSource' id='fgSecDescSource'>
                    <?php
                    foreach($descSourceList as $source){
                        echo "<option value='".$source."'>".$source."</option>\n";
                    }
                    ?>
                </select>
            </div>
            <div style="margin-top:5px;">
                <b>Use Other Description Sources:</b>
                <input data-role='none' name='fgUseAltDesc' id='fgUseAltDesc' type='checkbox' value='1' checked />
            </div>
            <div style="margin-top:5px;">
                <b>Photographers:</b>
                <input data-role='none' name='fgUseAllPhotog' id='fgUseAllPhotog' type='checkbox' value='1' onclick="selectAllPhotog();" checked /> Use All
                <a href="#" id='fgShowPhotog' title="Show Photographers List" style="margin-left:8px;font-size:10px;" onclick="toggle('fgPhotogBox');toggle('fgShowPhotog');toggle('fgHidePhotog');return false;">Show Photographers</a>
                <a href="#" id='fgHidePhotog' title="Hide Photographers List" style="display:none;margin-left:8px;font-size:10px;" onclick="toggle('fgPhotogBox');toggle('fgShowPhotog');toggle('fgHidePhotog');return false;">Hide Photographers</a>
                <div id='fgPhotogBox' style="display:none;width:570px;margin-top:10px;margin-bottom:10px;">
                    <table style="font-family:Arial;font-size:12px;">
                        <?php
                        $photogList = Array();
                        $i = 1;
                        $innerHtml = '';
                        $innerHtml .= '<tr>';
                        $photogList = $fgManager->getPhotogList();
                        ksort($photogList, SORT_STRING | SORT_FLAG_CASE);
                        foreach($photogList as $name => $id){
                            if($name){
                                $value = $id.'---'.$name;
                                if((($i % 3) == 1)) $innerHtml .= '</tr><tr>';
                                $innerHtml .= '<td style="width:190px;">';
                                $innerHtml .= "<input data-role='none' name='photog[]' type='checkbox' value='".$value."' onclick='checkPhotogSelections();' checked /> ".$name;
                                $innerHtml .= '</td>';
                                $i++;
                            }
                        }
                        $innerHtml .= '</tr>';
                        echo $innerHtml;
                        ?>
                    </table>
                </div>
            </div>
            <div style="margin-top:5px;">
                <b>Max Images Per Taxon:</b>
                <input data-role="none" name="fgMaxImages" type="radio" value="0" checked /> 0
                <input data-role="none" name="fgMaxImages" type="radio" value="1"/> 1
                <input data-role="none" name="fgMaxImages" type="radio" value="2"/> 2
                <input data-role="none" name="fgMaxImages" type="radio" value="3"/> 3
            </div>
            <?php
            if($clManager->getTaxaCount() > 300){
                $highIndex = ceil(($clManager->getTaxaCount()/300));
                ?>
                <div style="margin-top:5px;">
                    <b>File set:</b>
                    <select data-role='none' id='zipindex'>
                        <?php
                        $optIndex = 1;
                        while($optIndex <= $highIndex) {
                            echo "<option value='".$optIndex."'>".$optIndex."</option>\n";
                            $optIndex++;
                        }
                        ?>
                    </select>
                </div>
                <?php
            }
            else{
                ?>
                <input type="hidden" id="zipindex" value="1" />
                <?php
            }
            ?>
            <div style="margin-top:10px;float:right;">
                <button data-role="none" type="button" onclick='prepareFieldGuideExport(<?php echo $clManager->getTaxaCount(); ?>);' >Export Field Guide</button>
            </div>
        </div>

        <div id="loadingOverlay" data-role="popup" style="width:100%;height:100%;position:relative;display:none;">
            <div id="loader"></div>
            <div id="loaderMessage">This may take several minutes...</div>
        </div>
        <?php
    }
    ?>
<script>
    var container = document.querySelector('.images-wrapper');
    var msnry;

    // initialize Masonry after all images have loaded
    imagesLoaded( container, function() {
        msnry = new Masonry( container, {
            // options
            itemSelector: '.grid-item',
            columnWidth: 200
        });
    });
</script>
</body>
</html>