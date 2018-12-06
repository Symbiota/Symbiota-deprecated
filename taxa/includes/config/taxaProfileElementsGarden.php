<?php

include_once($SERVER_ROOT.'/classes/OSUTaxaManager.php');


ob_start();
if(!$taxonManager->echoImages(0,1,0)){

        echo $LANG['IMAGE_NOT_AVAILABLE'];

}
$centralImageDiv = ob_get_clean();


$garden_content = '';
if($descArr = $taxonManager->getDescriptions()) {
    //print_r($descArr);exit();
    foreach ($descArr as $dArr) {
        foreach ($dArr as $id => $vArr) {
            if ($vArr["caption"] == "Gardening with Natives") {
                $garden_content .= implode(" ", $vArr["desc"]);
            }
        }
    }
}
//wrap in p if not, so drop caps work
if(stristr($garden_content, '<p') === FALSE) {
    $garden_content = '<p>' . $garden_content . '</p>';
}
$descTabsDiv = $garden_content;


ob_start();
if($taxonRank > 140){
    ?>
    <div id="family" class="<?php echo $styleClass; ?>">
        <?php echo $taxonManager->getFamily(); ?>
    </div>
    <?php
}
$OSUfamilyDiv = ob_get_clean();

ob_start();
if($taxonRank > 180 && $links){
    echo '<div id="links" style=""><span id="linksbanner">'.$LANG['WEB_LINKS'].'</span><ul id="linkslist">';
    foreach($links as $l){
        $urlStr = str_replace('--SCINAME--',urlencode($taxonManager->getSciName()),$l['url']);
        echo '<li><a href="'.$urlStr.'" target="_blank">'.$l['title'].'</a></li>';
        if($l['notes']) echo ' '.$l['notes'];
    }
    echo "</ul></div>";
}
$OSUwebLinksDiv = ob_get_clean();

ob_start();
?>
<div id="obsImgDiv">
    <?php
    $OSUManager = new OSUTaxaManager();
    if($taxAuthId || $taxAuthId === "0") $OSUManager->setTaxAuthId($taxAuthId);
    if($clValue) $OSUManager->setClName($clValue);
    if($projValue) $OSUManager->setProj($projValue);
    if($lang) $OSUManager->setLanguage($lang);
    if($taxonValue) {
        $OSUManager->setTaxon($taxonValue);
        $OSUManager->setAttributes();
    }
    $OSUManager->echoImages('obs',1);
    ?>
</div>
<?php
$OSUobsImgDiv = ob_get_clean();

ob_start();
?>
<div id="specImgDiv">
    <?php
    $OSUManager = new OSUTaxaManager();
    if($taxAuthId || $taxAuthId === "0") $OSUManager->setTaxAuthId($taxAuthId);
    if($clValue) $OSUManager->setClName($clValue);
    if($projValue) $OSUManager->setProj($projValue);
    if($lang) $OSUManager->setLanguage($lang);
    if($taxonValue) {
        $OSUManager->setTaxon($taxonValue);
        $OSUManager->setAttributes();
    }
    $OSUManager->echoImages('spec',1);
    ?>
</div>
<?php
$OSUspecImgDiv = ob_get_clean();

ob_start();
$OSUManager = new OSUTaxaManager();
if($taxAuthId || $taxAuthId === "0") $OSUManager->setTaxAuthId($taxAuthId);
if($clValue) $OSUManager->setClName($clValue);
if($projValue) $OSUManager->setProj($projValue);
if($lang) $OSUManager->setLanguage($lang);
if($taxonValue) {
    $OSUManager->setTaxon($taxonValue);
    $OSUManager->setAttributes();
}
?>
<div id="imagebox">
    <?php
    if($clValue){
        echo "<legend>";
        echo $LANG['SPECIES_WITHIN'].' <b>'.$taxonManager->getClName().'</b>&nbsp;&nbsp;';
        if($taxonManager->getParentClid()){
            echo '<a href="index.php?taxon=$taxonValue&cl='.$taxonManager->getParentClid().'&taxauthid='.$taxAuthId.'" title="'.$LANG['GO_TO'].' '.$taxonManager->getParentName().' '.$LANG['CHECKLIST'].'"><img id="parenttaxonicon" src="../images/toparent.png" title="Go to Parent" /></a>';
        }
        echo "</legend>";
    }
    ?>
    <div>
        <?php
        if($sppArr = $OSUManager->getSppArray()){
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
$OSUimgBoxDiv = ob_get_clean();


?>