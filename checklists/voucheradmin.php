<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ChecklistVoucherAdmin.php');
include_once($SERVER_ROOT.'/content/lang/checklists/voucheradmin.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$charset);
if(!$SYMB_UID) header('Location: ../profile/index.php?refurl=../checklists/voucheradmin.php?'.$_SERVER['QUERY_STRING']);

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$startPos = (array_key_exists('start',$_REQUEST)?(int)$_REQUEST['start']:0);
$tabIndex = array_key_exists("tabindex",$_REQUEST)?$_REQUEST["tabindex"]:0;
$sqlFrag = array_key_exists("sqlfrag",$_REQUEST)?$_REQUEST["sqlfrag"]:"";
$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:"";

$displayMode = (array_key_exists('displaymode',$_REQUEST)?$_REQUEST['displaymode']:0);

$clManager = new ChecklistVoucherAdmin();
$clManager->setClid($clid);

$statusStr = "";
$isEditor = 0;
if($isAdmin || (array_key_exists("ClAdmin",$userRights) && in_array($clid,$userRights["ClAdmin"]))){
    $isEditor = 1;

    if($action == "SaveSearch"){
        $statusStr = $clManager->saveSql($_POST);
    }
    elseif($action == 'DeleteSql'){
        $statusStr = $clManager->deleteSql();
    }
    elseif($action == 'Add Vouchers'){
        $clManager->linkVouchers($_POST['occids']);
    }
    elseif($action == 'Add Taxa and Vouchers'){
        $clManager->linkTaxaVouchers($_POST['occids'],(array_key_exists('usecurrent',$_POST)?$_POST['usecurrent']:0));
    }
}
?>

<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
    <title><?php echo $defaultTitle; ?> <?php echo $LANG['CHECKADMIN'];?></title>
    <link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link type="text/css" href="../css/jquery-ui.css" rel="Stylesheet" />
    <script type="text/javascript" src="../js/jquery.js"></script>
    <script type="text/javascript" src="../js/jquery-ui.js"></script>
    <script type="text/javascript">
        var clid = <?php echo $clid; ?>;
        var tabIndex = <?php echo $tabIndex; ?>;
    </script>
    <script type="text/javascript" src="../js/symb/checklists.voucheradmin.js?ver=130330"></script>
    <style type="text/css">
        li{margin:5px;}
    </style>
</head>

<body>
<?php
$displayLeftMenu = false;
include($serverRoot.'/header.php');
?>
<div class="navpath">
    <a href="../index.php"><?php echo $LANG['NAV_HOME']?></a> &gt;&gt;
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
    $dynSql = $clManager->getDynamicSql();
    $termArr = $clManager->parseSql();
    $collList = $clManager->getCollectionList();
    if($dynSql){
        ?>
        <div style="margin:10px;">
            <?php
            $searchStr = '';
            if($termArr['collid']) $searchStr .= '<b>collection:</b> '.$collList[$termArr['collid']].'; ';
            if($termArr['country']) $searchStr .= '<b>country:</b> '.$termArr['country'].'; ';
            if($termArr['state']) $searchStr .= '<b>state:</b> '.$termArr['state'].'; ';
            if($termArr['county']) $searchStr .= '<b>county:</b> '.$termArr['county'].'; ';
            if($termArr['locality']) $searchStr .= '<b>locality:</b> '.$termArr['locality'].'; ';
            if($termArr['taxon']) $searchStr .= '<b>taxon:</b> '.$termArr['taxon'].'; ';
            if($termArr['recordedBy']) $searchStr .= '<b>recordedBy:</b> '.$termArr['recordedBy'].'; ';
            if($termArr['lats'] && $termArr['latn']) $searchStr .= '<b>Lat</b> between '.$termArr['lats'].' and '.$termArr['latn'].'; ';
            if($termArr['lngw'] && $termArr['lnge']) $searchStr .= '<b>Long</b> between '.$termArr['lngw'].' and '.$termArr['lnge'].'; ';
            if($termArr['latLngOr']) $searchStr .= 'Include Lat/Long and locality as an "OR" condition; ';
            if($termArr['culStatus']) $searchStr .= 'Exclude cultivated species; ';
            if($termArr['onlyCoord']) $searchStr .= 'Only include occurrences with coordinates; ';
            echo trim($searchStr,' ;');
            ?>
            <span style="margin-left:10px;"><a href="#" onclick="toggle('sqlbuilderdiv');return false;" title="Edit Search Statement"><img src="../images/edit.png" style="width:15px;border:0px;"/></a></span>
        </div>
    <?php
    }
    ?>
    <div id="sqlbuilderdiv" style="display:<?php echo ($dynSql?'none':'block'); ?>;margin-top:15px;">
        <fieldset>
            <legend><b><?php echo $LANG['EDITSEARCH'];?></b></legend>
            <form name="sqlbuilderform" action="voucheradmin.php" method="post" onsubmit="return validateSqlFragForm(this);">
                <div style="margin:10px;">
                    <?php echo $LANG['CHECKVOUCINSTRUC'];?>
                </div>
                <table style="margin:15px;">
                    <tr>
                        <td>
                            <div style="margin:2px;">
                                <b><?php echo $LANG['COUNTRY'];?>:</b>
                                <input type="text" name="country" value="<?php echo $termArr['country']; ?>" />
                            </div>
                            <div style="margin:2px;">
                                <b><?php echo $LANG['STATE'];?>:</b>
                                <input type="text" name="state" value="<?php echo $termArr['state']; ?>" />
                            </div>
                            <div style="margin:2px;">
                                <b><?php echo $LANG['COUNTY'];?>:</b>
                                <input type="text" name="county" value="<?php echo $termArr['county']; ?>" />
                            </div>
                            <div style="margin:2px;">
                                <b><?php echo $LANG['LOCALITY'];?>:</b>
                                <input type="text" name="locality" value="<?php echo $termArr['locality']; ?>" />
                            </div>
                            <div style="margin:2px;" title="Genus, family, or higher rank">
                                <b><?php echo $LANG['TAXON'];?>:</b>
                                <input type="text" name="taxon" value="<?php echo $termArr['taxon']; ?>" />
                            </div>
                            <div>
                                <b><?php echo $LANG['COLLECTION'];?>:</b>
                                <select name="collid" style="width:275px;">
                                    <option value=""><?php echo $LANG['TARGETCOLL'];?></option>
                                    <option value="">-------------------------------------</option>
                                    <?php
                                    foreach($collList as $id => $name){
                                        echo '<option value="'.$id.'" '.($termArr['collid']==$id?'SELECTED':'').'>'.$name.'</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div>
                                <b><?php echo $LANG['COLLECTOR'];?>:</b>
                                <input name="recordedby" type="text" value="<?php echo $termArr['recordedBy']; ?>" style="width:250px" />
                            </div>
                        </td>
                        <td style="padding-left:20px;">
                            <div style="float:left;">
                                <div>
                                    <b><?php echo $LANG['LATN'];?>:</b>
                                    <input id="upperlat" type="text" name="latnorth" style="width:70px;" value="<?php echo $termArr['latn']; ?>" title="Latitude North" />
                                    <a href="#" onclick="openPopup('../collections/mapboundingbox.php','boundingbox')"><img src="../images/world.png" width="15px" title="Find Coordinate" /></a>
                                </div>
                                <div>
                                    <b><?php echo $LANG['LATS'];?>:</b>
                                    <input id="bottomlat" type="text" name="latsouth" style="width:70px;" value="<?php echo $termArr['lats']; ?>" title="Latitude South" />
                                </div>
                                <div>
                                    <b><?php echo $LANG['LONGE'];?>:</b>
                                    <input id="rightlong" type="text" name="lngeast" style="width:70px;" value="<?php echo $termArr['lnge']; ?>" title="Longitude East" />
                                </div>
                                <div>
                                    <b><?php echo $LANG['LONGW'];?>:</b>
                                    <input id="leftlong" type="text" name="lngwest" style="width:70px;" value="<?php echo $termArr['lngw']; ?>" title="Longitude West" />
                                </div>
                                <div>
                                    <input type="checkbox" name="latlngor" value="1" <?php if ($termArr['latLngOr']) echo 'CHECKED'; ?> />
                                    <?php echo $LANG['INCLUDELATLONG'];?>
                                </div>
                                <div>
                                    <input name="onlycoord" value="1" type="checkbox" <?php if ($termArr['onlyCoord']) echo 'CHECKED'; ?> />
                                    <?php echo $LANG['ONLYCOORD'];?>
                                </div>
                                <div>
                                    <input name="excludecult" value="1" type="checkbox" <?php if ($termArr['culStatus']) echo 'CHECKED'; ?> />
                                    <?php echo $LANG['EXCLUDE'];?>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td colspan="2">
                            <div style="margin:10px;">
                                <input type="submit" name="submit" value="<?php echo $LANG['SAVESEARCH'];?>" />
                                <input type="hidden" name="submitaction" value="SaveSearch" />
                                <input type='hidden' name='clid' value='<?php echo $clid; ?>' />
                                <input type='hidden' name='pid' value='<?php echo $pid; ?>' />
                            </div>
                        </td>
                    </tr>
                </table>
            </form>
        </fieldset>
        <?php
        if($dynSql){
            ?>
            <fieldset>
                <legend><b><?php echo $LANG['REMOVESEARCH'];?></b></legend>
                <form name="sqldeleteform" action="voucheradmin.php" method="post" onsubmit="return confirm('Are you sure you want to delete current SQL statement?');">
                    <div style="margin:20px">
                        <input type="submit" name="submit" value="<?php echo $LANG['DELETESQL'];?>" />
                        <input type="hidden" name="submitaction" value="DeleteSql" />
                    </div>
                    <input type="hidden" name="clid" value="<?php echo $clid; ?>" />
                    <input type="hidden" name="pid" value="<?php echo $pid; ?>" />
                </form>
            </fieldset>
        <?php
        }
        ?>
    </div>
    <?php
    if($dynSql){
        ?>
        <div id="tabs" style="margin-top:25px;">
            <ul>
                <li><a href="#nonVoucheredDiv"><span><?php echo $LANG['NEWVOUCH'];?></span></a></li>
                <li><a href="vamissingtaxa.php?clid=<?php echo $clid.'&pid='.$pid.'&start='.$startPos.'&displaymode='.($tabIndex==1?$displayMode:0); ?>"><span><?php echo $LANG['MISSINGTAXA'];?></span></a></li>
                <li><a href="vaconflicts.php?clid=<?php echo $clid.'&pid='.$pid.'&start='.$startPos; ?>"><span><?php echo $LANG['VOUCHCONF'];?></span></a></li>
                <li><a href="#reportDiv"><span><?php echo $LANG['REPORTS'];?></span></a></li>
            </ul>
            <div id="nonVoucheredDiv">
                <div style="margin:10px;">
                    <?php
                    $nonVoucherCnt = $clManager->getNonVoucheredCnt();
                    ?>
                    <div style="float:right;">
                        <form name="displaymodeform" method="post" action="voucheradmin.php">
                            <b><?php echo $LANG['DISPLAYMODE'];?>:</b>
                            <select name="displaymode" onchange="this.form.submit()">
                                <option value="0"><?php echo $LANG['NONVOUCHTAX'];?></option>
                                <option value="1" <?php echo ($displayMode==1?'SELECTED':''); ?>><?php echo $LANG['OCCURNONVOUCH'];?></option>
                                <option value="2" <?php echo ($displayMode==2?'SELECTED':''); ?>><?php echo $LANG['NEWOCCUR'];?></option>
                                <!-- <option value="3" <?php //echo ($displayMode==3?'SELECTED':''); ?>>Non-species level or poorly identified vouchers</option> -->
                            </select>
                            <input name="clid" type="hidden" value="<?php echo $clid; ?>" />
                            <input name="pid" type="hidden" value="<?php echo $pid; ?>" />
                            <input name="tabindex" type="hidden" value="0" />
                        </form>
                    </div>
                    <?php
                    if(!$displayMode || $displayMode==1 || $displayMode==2){
                        ?>
                        <div style='float:left;margin-top:3px;height:30px;'>
                            <b><?php echo $LANG['TAXWITHOUTVOUCH'];?>: <?php echo $nonVoucherCnt; ?></b>
                            <?php
                            if($clManager->getChildClidArr()){
                                echo ' (excludes taxa from children checklists)';
                            }
                            ?>
                        </div>
                        <div style='float:left;'>
                            <a href="voucheradmin.php?clid=<?php echo $clid.'&pid='.$pid; ?>"><img src="../images/refresh.png" style="border:0px;" title="<?php echo $LANG['REFRESHLIST'];?>" /></a>
                        </div>
                    <?php
                    }
                    if($displayMode){
                        ?>
                        <div style="clear:both;">
                            <div style="margin:10px;">
                                <?php echo $LANG['LISTEDBELOW'];?>
                            </div>
                            <div>
                                <?php
                                if($specArr = $clManager->getNewVouchers($startPos,$displayMode)){
                                    ?>
                                    <form name="batchnonvoucherform" method="post" action="voucheradmin.php" onsubmit="return validateBatchNonVoucherForm(this)">
                                        <table class="styledtable" style="font-family:Arial;font-size:12px;">
                                            <tr>
                                                <th>
													<span title="Select All">
														<input name="occids[]" type="checkbox" onclick="selectAll(this);" value="0-0" />
													</span>
                                                </th>
                                                <th><?php echo $LANG['CHECKLISTID'];?></th>
                                                <th><?php echo $LANG['COLLECTOR'];?></th>
                                                <th><?php echo $LANG['LOCALITY'];?></th>
                                            </tr>
                                            <?php
                                            foreach($specArr as $cltid => $occArr){
                                                foreach($occArr as $occid => $oArr){
                                                    echo '<tr>';
                                                    echo '<td><input name="occids[]" type="checkbox" value="'.$occid.'-'.$cltid.'" /></td>';
                                                    echo '<td><a href="../taxa/index.php?taxon='.$oArr['tid'].'" target="_blank">'.$oArr['sciname'].'</a></td>';
                                                    echo '<td>';
                                                    echo $oArr['recordedby'].' '.$oArr['recordnumber'].'<br/>';
                                                    if($oArr['eventdate']) echo $oArr['eventdate'].'<br/>';
                                                    echo '<a href="../collections/individual/index.php?occid='.$occid.'" target="_blank">';
                                                    echo $oArr['collcode'];
                                                    echo '</a>';
                                                    echo '</td>';
                                                    echo '<td>'.$oArr['locality'].'</td>';
                                                    echo '</tr>';
                                                }
                                            }
                                            ?>
                                        </table>
                                        <input name="tabindex" value="0" type="hidden" />
                                        <input name="clid" value="<?php echo $clid; ?>" type="hidden" />
                                        <input name="pid" value="<?php echo $pid; ?>" type="hidden" />
                                        <input name="displaymode" value="1" type="hidden" />
                                        <input name="usecurrent" value="1" type="checkbox" checked /><?php echo $LANG['ADDNAMECURRTAX'];?><br/>
                                        <input name="submitaction" value="Add Vouchers" type="submit" />
                                    </form>
                                <?php
                                }
                                else{
                                    echo '<div style="font-weight:bold;font-size:120%;">'.$LANG['NOVOUCHLOCA'].'</div>';
                                }
                                ?>
                            </div>
                        </div>

                    <?php
                    }
                    else{
                        ?>
                        <div style="clear:both;">
                            <div style="margin:10px;">
                                <?php echo $LANG['LISTEDBELOWARESPECINSTRUC'];?>
                            </div>
                            <div style="margin:20px;">
                                <?php
                                if($nonVoucherArr = $clManager->getNonVoucheredTaxa($startPos)){
                                    foreach($nonVoucherArr as $family => $tArr){
                                        echo '<div style="font-weight:bold;">'.strtoupper($family).'</div>';
                                        echo '<div style="margin:10px;font-style:italic;">';
                                        foreach($tArr as $tid => $sciname){
                                            ?>
                                            <div>
                                                <a href="#" onclick="openPopup('../taxa/index.php?taxauthid=1&taxon=<?php echo $tid.'&cl='.$clid; ?>','taxawindow');return false;"><?php echo $sciname; ?></a>
                                                <a href="#" onclick="openPopup('../collections/list.php?db=all&thes=1&reset=1&taxa=<?php echo $sciname.'&targetclid='.$clid.'&targettid='.$tid;?>','editorwindow');return false;">
                                                    <img src="../images/link.png" style="width:13px;" title="<?php echo $LANG['LINKVOUCHSPECIMEN'];?>" />
                                                </a>
                                            </div>
                                        <?php
                                        }
                                        echo '</div>';
                                    }
                                    $arrCnt = $nonVoucherArr;
                                    if($startPos || $nonVoucherCnt > 100){
                                        echo '<div style="text-weight:bold;">';
                                        if($startPos > 0) echo '<a href="voucheradmin.php?clid='.$clid.'&pid='.$pid.'&start='.($startPos-100).'">';
                                        echo '&lt;&lt; '.$LANG['PREVIOUS'].'';
                                        if($startPos > 0) echo '</a>';
                                        echo ' || <b>'.$startPos.'-'.($startPos+($arrCnt<100?$arrCnt:100)).''.$LANG['RECORDS'].'</b> || ';
                                        if(($startPos + 100) <= $nonVoucherCnt) echo '<a href="voucheradmin.php?clid='.$clid.'&pid='.$pid.'&start='.($startPos+100).'">';
                                        echo ''.$LANG['NEXT'].' &gt;&gt;';
                                        if(($startPos + 100) <= $nonVoucherCnt) echo '</a>';
                                        echo '</div>';
                                    }
                                }
                                else{
                                    echo '<h2>'.$LANG['ALLTAXACONTAINVOUCH'].'</h2>';
                                }
                                ?>
                            </div>
                        </div>
                    <?php
                    }
                    ?>
                </div>
            </div>
            <div id="reportDiv">
                <div style="margin:25px;height:400px;">
                    <ul>
                        <li><a href="voucherreporthandler.php?rtype=fullvoucherscsv&clid=<?php echo $clid; ?>"><?php echo $LANG['FULLSPECLIST'];?></a></li>
                        <li><a href="checklist.php?printmode=1&showvouchers=1&cl=<?php echo $clid; ?>"><?php echo $LANG['FULLPRINT'];?></a></li>
                        <li><a href="voucherreporthandler.php?rtype=missingoccurcsv&clid=<?php echo $clid; ?>"><?php echo $LANG['SPECMISSTAXA'];?></a></li>
                        <li><a href="voucherreporthandler.php?rtype=problemtaxacsv&clid=<?php echo $clid; ?>"><?php echo $LANG['SPECMISSPELLED'];?></a></li>
                    </ul>
                </div>
            </div>
        </div>
    <?php
    }
}
else{
    if(!$clid){
        echo '<div><span style="font-weight:bold;font-size:110%;">Error:</span>'.$LANG['CHECKIDNOTSET'].'</div>';
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