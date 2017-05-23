<?php
ob_start();
?>
<div id="editbutton">
    <a href="admin/tpeditor.php?tid=<?php echo $taxonManager->getTid(); ?>" <?php echo 'title="'.$LANG['EDIT_TAXON_DATA'].'"'; ?>>
        <img id='editicon' src='../images/edit.png'/>
    </a>
</div>
<?php
$editButtonDiv = ob_get_clean();

ob_start();
if($taxonRank > 180){
    ?>
    <div id="scinameheader" class="<?php echo $styleClass; ?>">
        <span id="sciname" class="<?php echo $styleClass; ?>">
            <i><?php echo $spDisplay; ?></i>
        </span>
        <?php echo $taxonManager->getAuthor(); ?>
        <?php
        $parentLink = "index.php?taxon=".$taxonManager->getParentTid()."&cl=".$taxonManager->getClid()."&proj=".$projValue."&taxauthid=".$taxAuthId;
        echo "<a href='".$parentLink."'><img id='parenttaxonicon' src='../images/toparent.png' title='Go to Parent' /></a>";
        //If submitted tid does not equal accepted tid, state that user will be redirected to accepted
        if(($taxonManager->getTid() != $taxonManager->getSubmittedTid()) && $taxAuthId){
            echo '<span id="redirectedfrom"> ('.$LANG['REDIRECT'].': <i>'.$taxonManager->getSubmittedSciName().'</i>)</span>';
        }
        ?>
    </div>
    <?php
}
else{
    ?>
    <div id="scinameheader" class="<?php echo $styleClass; ?>">
    <?php
    $displayName = $spDisplay;
    if($taxonRank == 180) $displayName = '<i>'.$displayName.'</i> spp. ';
    if($taxonRank > 140){
        $parentLink = "index.php?taxon=".$taxonManager->getParentTid()."&cl=".$taxonManager->getClid()."&proj=".$projValue."&taxauthid=".$taxAuthId;
        $displayName .= ' <a href="'.$parentLink.'">';
        $displayName .= '<img id="parenttaxonicon" src="../images/toparent.png" title="Go to Parent" />';
        $displayName .= '</a>';
    }
    echo "<div id='sciname' class='<?php echo $styleClass; ?>' >$displayName</div>";
    ?>
    </div>
    <?php
}
$scinameHeaderDiv = ob_get_clean();

ob_start();
if($ambiguous){
    $synLinkStr = '';
    $explanationStr = '';
    foreach($synonymArr as $synTid => $sName){
        $synLinkStr .= '<a href="index.php?taxon='.$synTid.'&taxauthid='.$taxAuthId.'&cl='.$clValue.'&proj='.$projValue.'&lang='.$lang.'">'.$sName.'</a>, ';
    }
    $synLinkStr = substr($synLinkStr,0,-2);
    if($acceptedName){
        $explanationStr = $LANG['AMB_ACCEPTED'];
    }
    else{
        $explanationStr = $LANG['AMB_UNACCEPTED'];
    }
    echo "<div id='ambiguous'>";
    echo $explanationStr.$synLinkStr;
    echo '</div>';
}
$ambiguousDiv = ob_get_clean();

ob_start();
if($links && $links[0]['sortseq'] == 1){
    $uStr = str_replace('--SCINAME--',urlencode($taxonManager->getSciName()),$links[0]['url']);
    ?>
    <div id="weblinks">
        <?php echo $LANG['GO_TO']; ?> <a href="<?php echo $uStr; ?>" target="_blank"><?php echo $links[0]['title']; ?></a>...
    </div>
    <?php
}
$webLinksDiv = ob_get_clean();

ob_start();
if($taxonRank > 140){
    ?>
    <div id="family" class="<?php echo $styleClass; ?>">
        <?php echo '<b>'.$LANG['FAMILY'].':</b> '.$taxonManager->getFamily(); ?>
    </div>
    <?php
}
$familyDiv = ob_get_clean();

ob_start();
if($projValue){
    ?>
    <div id='project'><b>Project:</b> <?php echo $taxonManager->getProjName(); ?></div>
    <?php
}
$projectDiv = ob_get_clean();

ob_start();
if($vernStr){
    ?>
    <div id="vernaculars"><?php echo $vernStr; ?></div>
    <?php
}
$vernacularsDiv = ob_get_clean();

ob_start();
if($synStr){
    ?>
    <div id="synonyms" title="<?php echo $LANG['SYNONYMS']; ?>">[<?php echo $synStr; ?>]</div>
    <?php
}
$synonymsDiv = ob_get_clean();

ob_start();
if(!$taxonManager->echoImages(0,1,0)){
    echo '<div id="nocentralimage">';
    if($isEditor){
        echo '<a href="admin/tpeditor.php?category=imageadd&tid='.$taxonManager->getTid().'"><b>'.$LANG['ADD_IMAGE'].'</b></a>';
    }
    else{
        echo $LANG['IMAGE_NOT_AVAILABLE'];
    }
    echo '</div>';
}
$centralImageDiv = ob_get_clean();

ob_start();
if($descArr = $taxonManager->getDescriptions()){
    if(isset($PORTAL_TAXA_DESC)){
        $tempArr = array();
        $descIndex = 0;
        foreach($descArr as $dArr){
            foreach($dArr as $id => $vArr){
                if($vArr["caption"] == $PORTAL_TAXA_DESC){
                    $tempArr = $descArr[$descIndex][$id];
                    unset($descArr[$descIndex][$id]);
                    array_unshift($descArr[$descIndex],$tempArr);
                    $descIndex++;
                }
            }
        }
    }
    ?>
    <div id="desctabs" class="ui-tabs <?php echo $styleClass; ?>">
        <ul class="ui-tabs-nav">
            <?php
            $capCnt = 1;
            foreach($descArr as $dArr){
                foreach($dArr as $id => $vArr){
                    $cap = $vArr["caption"];
                    if(!$cap){
                        $cap = $LANG['DESCRIPTION'].' #'.$capCnt;
                        $capCnt++;
                    }
                    echo '<li><a href="#tab'.$id.'" class="selected">'.$cap.'</a></li>';
                }
            }
            ?>
        </ul>
        <?php
        foreach($descArr as $dArr){
            foreach($dArr as $id => $vArr){
                ?>
                <div id="tab<?php echo $id; ?>" class="<?php echo ($styleClass=='species'?'sptab':'spptab'); ?>">
                    <?php
                    if($vArr["source"]){
                        echo '<div id="descsource">';
                        if($vArr["url"]){
                            echo '<a href="'.$vArr['url'].'" target="_blank">';
                        }
                        echo $vArr["source"];
                        if($vArr["url"]){
                            echo "</a>";
                        }
                        echo '</div>';
                    }
                    $descArr = $vArr["desc"];
                    ?>
                    <div style="clear:both;">
                        <?php
                        foreach($descArr as $tdsId => $stmt){
                            echo $stmt." ";
                        }
                        //if($this->clInfo){
                        //echo "<div id='clinfo'><b>Local Notes:</b> ".$clInfo."</div>";
                        //}
                        ?>
                    </div>
                </div>
                <?php
            }
        }
        ?>
    </div>
    <?php
}
else{
    echo '<div id="nodesc">'.$LANG['DESCRIPTION_NOT_AVAILABLE'].'</div>';
}
$descTabsDiv = ob_get_clean();

ob_start();
$url = ''; $aUrl = ''; $gAnchor = '';
if($occurrenceModIsActive && $displayLocality){
    $gAnchor = "openMapPopup('".$taxonManager->getTid()."',".($taxonManager->getClid()?$taxonManager->getClid():0).")";
}
if($mapSrc = $taxonManager->getMapArr()){
    $url = array_shift($mapSrc);
    $aUrl = $url;
}
elseif($gAnchor){
    $url = $taxonManager->getGoogleStaticMap();
}
if($url){
    echo '<div class="mapthumb">';
    if($gAnchor){
        echo '<a href="#" onclick="'.$gAnchor.';return false">';
    }
    elseif($aUrl){
        echo '<a href="'.$aUrl.'">';
    }
    echo '<img src="'.$url.'" title="'.$spDisplay.'" alt="'.$spDisplay.'" />';
    if($aUrl || $gAnchor) echo '</a>';
    if($gAnchor) echo '<br /><a href="#" onclick="'.$gAnchor.';return false">'.$LANG['OPEN_MAP'].'</a>';
    echo "</div>";
}
$mapThumbDiv = ob_get_clean();

ob_start();
?>
<div id="img-div">
    <?php
    $taxonManager->echoImages(1);
    ?>
</div>
<?php
$imgDiv = ob_get_clean();

ob_start();
?>
<div id="img-tab-div" style="display:<?php echo $taxonManager->getImageCount()> 6?'block':'none';?>;">
    <a href="#" onclick="expandExtraImages();return false;">
        <div id="img-tab-expand">
            <?php echo $LANG['CLICK_TO_DISPLAY'].'<br/>'.$taxonManager->getImageCount().' '.$LANG['TOTAL_IMAGES']; ?>
        </div>
    </a>
</div>
<?php
$imgTabDiv = ob_get_clean();

ob_start();
?>
<div id="imagebox">
    <?php
    if($clValue){
        echo "<legend>";
        echo $LANG['SPECIES_WITHIN'].' <b>'.$taxonManager->getClName().'</b>&nbsp;&nbsp;';
        if($taxonManager->getParentClid()){
            echo '<a href="index.php?taxon='.$taxonValue.'&cl='.$taxonManager->getParentClid().'&taxauthid='.$taxAuthId.'" title="'.$LANG['GO_TO'].' '.$taxonManager->getParentName().' '.$LANG['CHECKLIST'].'"><img id="parenttaxonicon" src="../images/toparent.png" title="Go to Parent" /></a>';
        }
        echo "</legend>";
    }
    ?>
    <div>
        <?php
        if($sppArr = $taxonManager->getSppArray()){
            $cnt = 0;
            ksort($sppArr);
            foreach($sppArr as $sciNameKey => $subArr){
                echo "<div class='spptaxon'>";
                echo "<div class='spptaxonbox'>";
                echo "<a href='index.php?taxon=".$subArr["tid"]."&taxauthid=".$taxAuthId.($clValue?"&cl=".$clValue:"")."'>";
                echo "<i>".$sciNameKey."</i>";
                echo "</a></div>\n";
                echo "<div class='sppimg'>";

                if(array_key_exists("url",$subArr)){
                    $imgUrl = $subArr["url"];
                    if(array_key_exists("imageDomain",$GLOBALS) && substr($imgUrl,0,1)=="/"){
                        $imgUrl = $GLOBALS["imageDomain"].$imgUrl;
                    }
                    echo "<a href='index.php?taxon=".$subArr["tid"]."&taxauthid=".$taxAuthId.($clValue?"&cl=".$clValue:"")."'>";

                    if($subArr["thumbnailurl"]){
                        $imgUrl = $subArr["thumbnailurl"];
                        if(array_key_exists("imageDomain",$GLOBALS) && substr($subArr["thumbnailurl"],0,1)=="/"){
                            $imgUrl = $GLOBALS["imageDomain"].$subArr["thumbnailurl"];
                        }
                    }
                    echo '<img class="taxonimage" src="'.$imgUrl.'" title="'.$subArr['caption'].'" alt="Image of '.$sciNameKey.'" />';
                    echo '</a>';
                    echo '<div id="imgphotographer" title="'.$LANG['PHOTOGRAPHER'].': '.$subArr['photographer'].'">';
                    echo '</div>';
                }
                elseif($isEditor){
                    echo '<div class="spptext"><a href="admin/tpeditor.php?category=imageadd&tid='.$subArr['tid'].'">'.$LANG['ADD_IMAGE'].'!</a></div>';
                }
                else{
                    echo '<div class="spptext">'.$LANG['IMAGE_NOT_AVAILABLE'].'</div>';
                }
                echo "</div>\n";

                //Display thumbnail map
                echo '<div class="sppmap">';
                if(array_key_exists("map",$subArr) && $subArr["map"]){
                    echo '<img src="'.$subArr['map'].'" title="'.$spDisplay.'" alt="'.$spDisplay.'" />';
                }
                elseif($taxonManager->getRankId()>140){
                    echo '<div class="spptext">'.$LANG['MAP_NOT_AVAILABLE'].'</div>';
                }
                echo '</div>';

                echo "</div>";
                $cnt++;
            }
        }
        ?>
        <div class="clear"><hr></div>
    </div>
</div>
<?php
$imgBoxDiv = ob_get_clean();

ob_start();
echo '<div id="footerlinkstoggle">';
if($taxonRank > 180){
    if($taxonRank > 180 && $links){
        echo '<a href="#" onclick="toggleLinks(\'links\');return false;">'.$LANG['WEB_LINKS'].'</a>';
    }
}

if($taxonRank > 140){
    $parentLink = "index.php?taxon=".$taxonManager->getParentTid()."&taxauthid=".$taxAuthId;
    if($clValue) $parentLink .= "&cl=".$taxonManager->getClid();
    if($projValue) $parentLink .= "&proj=".$projValue;
    echo '<a href="'.$parentLink.'" class="parentlink">'.$LANG['VIEW_PARENT'].'</a>';
}
//Search tool failing to open properly; will debug shortly
//echo "<a href='../imagelib/search.php?nametype=1&imagedisplay=thumbnail&taxastr=".$taxonManager->getSciName()."&submitaction=Load+Images' style='margin-left:30px;'>Open Image Search Tool</a>";
echo "</div>";

//List Web Links as a list
if($taxonRank > 180 && $links){
    echo '<div id="links" style="display:none;"><h1 id="linksbanner">'.$LANG['WEB_LINKS'].'</h1><ul id="linkslist">';
    foreach($links as $l){
        $urlStr = str_replace('--SCINAME--',urlencode($taxonManager->getSciName()),$l['url']);
        echo '<li><a href="'.$urlStr.'" target="_blank">'.$l['title'].'</a></li>';
        if($l['notes']) echo ' '.$l['notes'];
    }
    echo "</ul>\n</div>";
}
$footerLinksDiv = ob_get_clean();

ob_start();
?>
<div id="notfoundbox">
    <?php
    if(is_numeric($taxonValue)){
        echo '<h1>Illegal identifier submitted: '.$taxonValue.'</h1>';
    }
    else{
        ?>
        <h1><?php echo '<i>'.$taxonValue.'</i> '.$LANG['NOT_FOUND']; ?></h1>
        <?php
        if($matchArr = $taxonManager->getCloseTaxaMatches($taxonValue)){
            ?>
            <div id="suggestionbox">
                <?php echo $LANG['DID_YOU_MEAN'];?>
                <div id="suggestionlist">
                    <?php
                    foreach($matchArr as $t => $n){
                        echo '<a href="index.php?taxon='.$t.'">'.$n.'</a><br/>';
                    }
                    ?>
                </div>
            </div>
            <?php
        }
    }
    ?>
</div>
<?php
$notFoundDiv = ob_get_clean();
?>