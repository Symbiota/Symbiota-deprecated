<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistAdmin.php');
include_once($SERVER_ROOT.'/content/lang/checklists/checklistadmin.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$charset);
if(!$SYMB_UID) header('Location: ../profile/index.php?refurl=../checklists/checklistadmin.php?'.$_SERVER['QUERY_STRING']);

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$startPos = (array_key_exists('start',$_REQUEST)?(int)$_REQUEST['start']:0);
$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:0;
$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:"";

$clManager = new ChecklistAdmin();
if(!$clid && isset($_POST['delclid'])) $clid = $_POST['delclid'];
$clManager->setClid($clid);

$statusStr = "";
$isEditor = 0;

if($IS_ADMIN || (array_key_exists("ClAdmin",$USER_RIGHTS) && in_array($clid,$USER_RIGHTS["ClAdmin"]))){
    $isEditor = 1;

    //Submit checklist MetaData edits
    if($action == "SubmitChange"){
        $clManager->editMetaData($_POST);
        header('Location: checklist.php?cl='.$clid.'&pid='.$pid);
    }
    elseif($action == 'DeleteCheck'){
        $statusStr = $clManager->deleteChecklist($_POST['delclid']);
        if($statusStr === true) header('Location: ../index.php');
    }
    elseif($action == 'Addeditor'){
        $statusStr = $clManager->addEditor($_POST['editoruid']);
    }
    elseif(array_key_exists('deleteuid',$_REQUEST)){
        $statusStr = $clManager->deleteEditor($_REQUEST['deleteuid']);
    }
    elseif($action == 'Add Point'){
        $statusStr = $clManager->addPoint($_POST['pointtid'],$_POST['pointlat'],$_POST['pointlng'],$_POST['notes']);
    }
    elseif($action && array_key_exists('clidadd',$_POST)){
        $statusStr = $clManager->addChildChecklist($_POST['clidadd']);
    }
    elseif($action && array_key_exists('cliddel',$_GET)){
        $statusStr = $clManager->deleteChildChecklist($_GET['cliddel']);
    }
}
$clArray = $clManager->getMetaData();
$defaultArr = array();
if($clArray["defaultSettings"]){
    $defaultArr = json_decode($clArray["defaultSettings"], true);
}

$voucherProjects = $clManager->getVoucherProjects();
?>

<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
    <title><?php echo $defaultTitle; ?><?php echo $LANG['CHECKADMIN'];?></title>
    <link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
    <link type="text/css" href="../css/jquery-ui.css" rel="Stylesheet" />
    <script type="text/javascript" src="../js/jquery.js"></script>
    <script type="text/javascript" src="../js/jquery-ui.js"></script>
    <script type="text/javascript" src="../js/tiny_mce/tiny_mce.js"></script>
    <script type="text/javascript">
        var clid = <?php echo $clid; ?>;
        var tabIndex = <?php echo $tabIndex; ?>;

        tinyMCE.init({
            mode : "textareas",
            theme_advanced_buttons1 : "bold,italic,underline,charmap,hr,outdent,indent,link,unlink,code",
            theme_advanced_buttons2 : "",
            theme_advanced_buttons3 : ""
        });
    </script>
    <script type="text/javascript" src="../js/symb/shared.js"></script>
    <script type="text/javascript" src="../js/symb/checklists.checklistadmin.js?ver=20151202"></script>
</head>

<body>
<?php
$displayLeftMenu = false;
include($serverRoot.'/header.php');
?>
<div class="navpath">
    <a href="../index.php"><?php echo $LANG['NAV_HOME'];?></a> &gt;&gt;
    <a href="checklist.php?cl=<?php echo $clid.'&pid='.$pid; ?>"><?php echo $LANG['RETURNCHECK'];?></a> &gt;&gt;
    <b><?php echo $LANG['CHECKADMIN'];?></b>
</div>

<!-- This is inner text! -->
<div id='innertext'>
<div style="color:#990000;font-size:20px;font-weight:bold;margin:0px 10px 10px 0px;">
    <a href="checklist.php?cl=<?php echo $clid.'&pid='.$pid; ?>">
        <?php echo $clManager->getClName(); ?>
    </a>
</div>
<?php
if($statusStr){
    ?>
    <hr />
    <div style="margin:20px;font-weight:bold;color:red;">
        <?php echo $statusStr; ?>
    </div>
    <hr />
<?php
}

if($clid && $isEditor){
    ?>
    <div id="tabs" style="margin:10px;">
    <ul>
        <li><a href="#admintab"><span><?php echo $LANG['ADMIN'];?></span></a></li>
        <li><a href="#desctab"><span><?php echo $LANG['DESCRIPTION'];?></span></a></li>
        <!-- 			        <li><a href="#pointtab"><span>Non-vouchered Points</span></a></li> -->
        <li><a href="checklistadminchildren.php?clid=<?php echo $clid.'&pid='.$pid.'&start='.$startPos; ?>"><span><?php echo $LANG['RELATEDCHECK'];?></span></a></li>
        <?php
        if($voucherProjects){
            ?>
            <li><a href="#imgvouchertab"><?php echo $LANG['ADDIMGV'];?></a></li>
        <?php
        }
        ?>
    </ul>
    <div id="admintab">
        <div style="margin:20px;">
            <div style="font-weight:bold;font-size:120%;"><?php echo $LANG['CURREDIT'];?></div>
            <?php
            $editorArr = $clManager->getEditors();
            if($editorArr){
                ?>
                <ul>
                    <?php
                    foreach($editorArr as $uid => $uName){
                        ?>
                        <li>
                            <?php echo $uName; ?>
                            <a href="checklistadmin.php?clid=<?php echo $clid.'&deleteuid='.$uid.'&pid='.$pid.'&tabindex='.$tabIndex; ?>" onclick="return confirm(<?php echo $LANG['REMOVEEDITPRIVCONFIRM'];?>);" title="<?php echo $LANG['DELETETHISU'];?>">
                                <img src="../images/drop.png" style="width:12px;" />
                            </a>
                        </li>
                    <?php
                    }
                    ?>
                </ul>
            <?php
            }
            else{
                echo "<div>".$LANG['NOEDITOR']."</div>\n";
            }
            ?>
            <fieldset style="margin:40px 5px;padding:15px;">
                <legend><b><?php echo $LANG['ADDNEWU'];?></b></legend>
                <form name="adduser" action="checklistadmin.php" method="post" onsubmit="return verifyAddUser(this)">
                    <div>
                        <select name="editoruid">
                            <option value=""><?php echo $LANG['SELECTU'];?></option>
                            <option value="">--------------------</option>
                            <?php
                            $userArr = $clManager->getUserList();
                            foreach($userArr as $uid => $uName){
                                echo '<option value="'.$uid.'">'.$uName.'</option>';
                            }
                            ?>
                        </select>
                        <input name="submit" type="submit" value="<?php echo $LANG['ADDEDITOR'];?>" />
                        <input type="hidden" name="submitaction" value="Addeditor" />
                        <input type="hidden" name="pid" value="<?php echo $pid; ?>" />
                        <input type="hidden" name="clid" value="<?php echo $clid; ?>" />
                    </div>
                </form>
            </fieldset>
        </div>
        <hr/>
        <div style="margin:20px;">
            <div style="font-weight:bold;font-size:120%;"><?php echo $LANG['INVPROJAS'];?></div>
            <ul>
                <?php
                $projArr = $clManager->getInventoryProjects();
                if($projArr){
                    foreach($projArr as $pid => $pName){
                        echo '<li>';
                        echo '<a href="../projects/index.php?pid='.$pid.'">'.$pName.'</a>';
                        echo '</li>';
                    }
                }
                else{
                    echo '<li>'.$LANG['CHECKNOTAS'].'</li>';
                }
                ?>
            </ul>
        </div>
        <hr/>
        <div style="margin:20px;">
            <div style="font-weight:bold;font-size:120%;"><?php echo $LANG['PERMREMOVECHECK'];?></div>
            <div style="margin:10px;">
                <?php echo $LANG['REMOVEUSERCHECK'];?><br/>
                <b><?php echo $LANG['WARNINGNOUN'];?></b>
            </div>
            <div style="margin:15px;">
                <form action="checklistadmin.php" method="post" name="deleteclform" onsubmit="return window.confirm('<?php echo $LANG['CONFIRMDELETE'];?>')">
                    <input name="delclid" type="hidden" value="<?php echo $clid; ?>" />
                    <input name="submit" type="submit" value="<?php echo $LANG['DELETECHECK'];?>" <?php if($projArr || count($editorArr) > 1) echo 'DISABLED'; ?> />
                    <input type="hidden" name="submitaction" value="DeleteCheck" />
                </form>
            </div>
        </div>
    </div>
    <div id="desctab">
        <form id="checklisteditform" action="checklistadmin.php" method="post" name="editclmatadata" onsubmit="return validateMetadataForm(this)">
            <fieldset style="margin:15px;padding:10px;">
                <legend><b><?php echo $LANG['EDITCHECKDET'];?></b></legend>
                <div>
                    <b><?php echo $LANG['CHECKNAME'];?></b><br/>
                    <input type="text" name="name" style="width:95%" value="<?php echo $clManager->getClName();?>" />
                </div>
                <div>
                    <b><?php echo $LANG['AUTHORS'];?></b><br/>
                    <input type="text" name="authors" style="width:95%" value="<?php echo $clArray["authors"]; ?>" />
                </div>
                <?php
                if(isset($GLOBALS['USER_RIGHTS']['RareSppAdmin']) || $IS_ADMIN){
                    ?>
                    <div>
                        <b><?php echo $LANG['CHECKTYPE'];?></b><br/>
                        <select name="type">
                            <option value="static"><?php echo $LANG['GENCHECK'];?></option>
                            <option value="rarespp" <?php echo ($clArray["type"]=='rarespp'?'SELECTED':'') ?>><?php echo $LANG['RARETHREAT'];?></option>
                        </select>
                    </div>
                <?php
                }
                ?>
                <div>
                    <b><?php echo $LANG['LOC'];?></b><br/>
                    <input type="text" name="locality" style="width:95%" value="<?php echo $clArray["locality"]; ?>" />
                </div>
                <div>
                    <b><?php echo $LANG['CITATION'];?></b><br/>
                    <input type="text" name="publication" style="width:95%" value="<?php echo $clArray["publication"]; ?>" />
                </div>
                <div>
                    <b><?php echo $LANG['ABSTRACT'];?></b><br/>
                    <textarea name="abstract" style="width:95%" rows="3"><?php echo $clArray["abstract"]; ?></textarea>
                </div>
                <div>
                    <b><?php echo $LANG['NOTES'];?></b><br/>
                    <input type="text" name="notes" style="width:95%" value="<?php echo $clArray["notes"]; ?>" />
                </div>
                <div style="width:100%;">
                    <div style="float:left;">
                        <b><?php echo $LANG['LATCENT'];?></b><br/>
                        <input id="latdec" type="text" name="latcentroid" style="width:110px;" value="<?php echo $clArray["latcentroid"]; ?>" />
                    </div>
                    <div style="float:left;margin-left:15px;">
                        <b><?php echo $LANG['LONGCENT'];?></b><br/>
                        <input id="lngdec" type="text" name="longcentroid" style="width:110px;" value="<?php echo $clArray["longcentroid"]; ?>" />
                    </div>
                    <div style="float:left;margin:25px 3px;">
                    	<a href="#" onclick="openMappingAid();return false;"><img src="../images/world.png" style="width:12px;" /></a>
                    </div>
                    <div style="float:left;margin-left:15px;">
                        <b><?php echo $LANG['POINTRAD'];?></b><br/>
                        <input type="text" name="pointradiusmeters" style="width:110px;" value="<?php echo $clArray["pointradiusmeters"]; ?>" />
                    </div>
                    <div style="float:left;margin:8px 0px 0px 25px;">
                        <fieldset style="width:175px;">
                            <legend><b><?php echo $LANG['POLYFOOT'];?></b></legend>
                            <?php
                            if($clArray&&$clArray["footprintWKT"]){
                                ?>
                                <div id="polyexistsbox" style="display:block;clear:both;">
                                    <b><?php echo $LANG['POLYFOOTSAVE'];?></b>
                                </div>
                            <?php
                            }
                            else{
                                ?>
                                <div id="polycreatebox" style="display:block;clear:both;">
                                    <b><?php echo $LANG['CREATEPOLYFOOT'];?></b>
                                </div>
                            <?php
                            }
                            ?>
                            <div id="polysavebox" style="display:none;clear:both;">
                                <b><?php echo $LANG['POLYFOOTRDYSAVE'];?></b>
                            </div>
                            <div style="float:right;margin:8px 0px 0px 10px;cursor:pointer;" onclick="openMappingPolyAid();">
                                <img src="../images/world.png" style="width:12px;" />
                            </div>
                        </fieldset>
                    </div>
                </div>
                <div style="clear:both;margin-top:5px;">
                    <fieldset style="width:300px;">
                        <legend><b><?php echo $LANG['DEFAULTDISPLAY'];?></b></legend>
                        <div>
                            <!-- Display Details: 0 = false, 1 = true  -->
                            <input name='ddetails' id='ddetails' type='checkbox' value='1' <?php echo (($defaultArr&&$defaultArr["ddetails"])?"checked":""); ?> /> 
                            <?php echo $LANG['SHOWDETAILS'];?>
                        </div>
                        <div>
                            <?php
                            //Display Common Names: 0 = false, 1 = true
                            if($displayCommonNames) echo "<input id='dcommon' name='dcommon' type='checkbox' value='1' ".(($defaultArr&&$defaultArr["dcommon"])?"checked":"")." /> ".$LANG['COMMON'];
                            ?>
                        </div>
                        <div>
                            <!-- Display as Images: 0 = false, 1 = true  -->
                            <input name='dimages' id='dimages' type='checkbox' value='1' <?php echo (($defaultArr&&$defaultArr["dimages"])?"checked":""); ?> onclick="showImagesDefaultChecked(this.form);" /> 
                            <?php echo $LANG['DISPLAYIMG'];?>
                        </div>
                        <div>
                            <!-- Display as Vouchers: 0 = false, 1 = true  -->
                            <input name='dvouchers' id='dvouchers' type='checkbox' value='1' <?php echo (($defaultArr&&$defaultArr["dimages"])?"disabled":(($defaultArr&&$defaultArr["dvouchers"])?"checked":"")); ?>/> 
                            <?php echo $LANG['NOTESVOUC'];?>
                        </div>
                        <div>
                            <!-- Display Taxon Authors: 0 = false, 1 = true  -->
                            <input name='dauthors' id='dauthors' type='checkbox' value='1' <?php echo (($defaultArr&&$defaultArr["dimages"])?"disabled":(($defaultArr&&$defaultArr["dauthors"])?"checked":"")); ?>/> 
                            <?php echo $LANG['TAXONAUTHOR'];?>
                        </div>
                        <div>
                            <!-- Display Taxa Alphabetically: 0 = false, 1 = true  -->
                            <input name='dalpha' id='dalpha' type='checkbox' value='1' <?php echo ($defaultArr&&$defaultArr["dalpha"]?"checked":""); ?> /> 
                            <?php echo $LANG['TAXONABC'];?>
                        </div>
                        <div>
                            <?php 
                            // Activate Identification key: 0 = false, 1 = true 
                            $activateKey = $KEY_MOD_IS_ACTIVE;
                            if(array_key_exists('activatekey', $defaultArr)){
								$activateKey = $defaultArr["activatekey"];
                            }
                            ?>
                            <input name='activatekey' type='checkbox' value='1' <?php echo ($activateKey?"checked":""); ?> /> 
                            <?php echo $LANG['ACTIVATEKEY'];?>
                        </div>
                    </fieldset>
                </div>
                <div style="clear:both;margin-top:15px;">
                    <b>Access</b><br/>
                    <select name="access">
                        <option value="private"><?php echo $LANG['PRIVATE'];?></option>
                        <option value="public" <?php echo ($clArray["access"]=="public"?"selected":""); ?>><?php echo $LANG['PUBLIC'];?></option>
                    </select>
                </div>
                <div style="clear:both;float:left;margin-top:15px;">
                    <input type='submit' name='submit' id='editsubmit' value='<?php echo $LANG['SUBMITCHANG'];?>' />
                    <input type="hidden" name="submitaction" value="SubmitChange" />
                </div>
                <input type="hidden" id="footprintWKT" name="footprintWKT" value='<?php echo $clArray["footprintWKT"]; ?>' />
                <input type="hidden" name="tabindex" value="1" />
                <input type='hidden' name='clid' value='<?php echo $clid; ?>' />
                <input type="hidden" name="pid" value="<?php echo $pid; ?>" />
            </fieldset>
        </form>
    </div>
    <!--
				<div id="pointtab">
					<fieldset>
						<legend><b>Add New Point</b></legend>
						<form name="pointaddform" target="checklistadmin.php" method="post" onsubmit="return verifyPointAddForm(this)">
							Taxon<br/>
							<select name="pointtid" onchange="togglePoint(this.form);">
								<option value="">Select Taxon</option>
								<option value="">-----------------------</option>
								<?php
    $taxaArr = $clManager->getTaxa();
    foreach($taxaArr as $tid => $sn){
        echo '<option value="'.$tid.'">'.$sn.'</option>';
    }
    ?>
							</select>
							<div id="pointlldiv" style="display:none;">
								<div style="float:left;">
									Latitude Centroid<br/>
									<input id="latdec" type="text" name="pointlat" style="width:110px;" value="" />
								</div>
								<div style="float:left;margin-left:5px;">
									Longitude Centroid<br/>
									<input id="lngdec" type="text" name="pointlng" style="width:110px;" value="" />
								</div>
								<div style="float:left;margin:15px 0px 0px 10px;cursor:pointer;" onclick="openPointAid(<?php echo $clArray["latcentroid"].','.$clArray["longcentroid"]?>);">
									<img src="../images/world.png" style="width:12px;" />
								</div>
								<div style="clear:both;">
									Notes:<br/>
									<input type="text" name="notes" style="width:95%" value="" />
								</div>
								<div>
									<input name="submitaction" type="submit" value="Add Point" />
									<input type="hidden" name="tabindex" value="2" />
									<input type="hidden" name="pid" value="<?php echo $pid; ?>" />
									<input type="hidden" name="clid" value="<?php echo $clid; ?>" />
								</div>
							</div>
						</form>
					</fieldset>
				</div>
 -->
    <?php
    if($voucherProjects){
        ?>
        <div id="imgvouchertab">
            <form name="addimagevoucher" action="../collections/editor/observationsubmit.php" method="get" target="_blank">
                <fieldset style="margin:15px;padding:25px;">
                    <legend><b><?php echo $LANG['ADDIMGVOUC'];?></b></legend>
                    <?php echo $LANG['FORMADDVOUCH'];?><br><br>
                    <?php echo $LANG['SELECTVOUCPROJ'];?>
                    <div style="margin:5px;">
                        <select name="collid">
                            <?php
                            foreach($voucherProjects as $k => $v){
                                echo '<option value="'.$k.'">'.$v.'</option>';
                            }
                            ?>
                        </select><br/>
                        <input type="hidden" name="clid" value="<?php echo $clid; ?>" />
                    </div>
                    <div style="margin:5px;">
                        <input type="submit" name="submitvoucher" value=<?php echo $LANG['ADDIMGVOUC'];?> /><br/>
                    </div>
                </fieldset>
            </form>
        </div>
    <?php
    }
    ?>
    </div>
<?php
}
else{
    if(!$clid){
        echo '<div><span style="font-weight:bold;font-size:110%;">Error:</span>'.$LANG['IDNOTSET'].'</div>';
    }
    else{
        echo '<div><span style="font-weight:bold;font-size:110%;">Error:</span>'.$LANG['NOADMINPERM'].'</div>';
    }
}
?>
</div>
<?php
include($serverRoot.'/footer.php');
?>

</body>
</html> 