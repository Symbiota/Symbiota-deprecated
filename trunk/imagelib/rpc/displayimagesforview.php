<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/ImageExplorer.php');
include_once($serverRoot.'/classes/PhotographerManager.php');

    $imageExplorer = new ImageExplorer();
    $imgArr = $imageExplorer->getImages($_POST);

                echo '<div style="clear:both;">';
                echo '<input type="hidden" id="imgCnt" value="'.$imgArr['cnt'].'" />';

                unset($imgArr['cnt']);

                foreach($imgArr as $imgArr){
                    $imgId = $imgArr['imgid'];
                    $imgUrl = $imgArr['url'];
                    $imgTn = $imgArr['thumbnailurl'];
                    if($imgTn){
                        $imgUrl = $imgTn;
                        if($imageDomain && substr($imgTn,0,1)=='/'){
                            $imgUrl = $imageDomain.$imgTn;
                        }
                    }
                    elseif($imageDomain && substr($imgUrl,0,1)=='/'){
                        $imgUrl = $imageDomain.$imgUrl;
                    }
    ?>

                    <script type="text/javascript">
                    function openIndPU(occId,clid){
                    var wWidth = 900;
                    if(document.getElementById('maintable').offsetWidth){
                    wWidth = document.getElementById('maintable').offsetWidth*1.05;
                    }
                    else if(document.body.offsetWidth){
                    wWidth = document.body.offsetWidth*0.9;
                    }
                    if(wWidth > 1000) wWidth = 1000;
                    newWindow = window.open('../collections/individual/index.php?occid='+occId,'indspec' + occId,'scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
                    if (newWindow.opener == null) newWindow.opener = self;
                    return false;
                    }

                    function openTaxonPopup(tid){
                        var wWidth = 900;
                        if(document.getElementById('maintable').offsetWidth){
                            wWidth = document.getElementById('maintable').offsetWidth*1.05;
                        }
                        else if(document.body.offsetWidth){
                            wWidth = document.body.offsetWidth*0.9;
                        }
                        if(wWidth > 1000) wWidth = 1000;
                        newWindow = window.open("../taxa/index.php?taxon="+tid,'taxon'+tid,'scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=700,left=20,top=20');
                        if (newWindow.opener == null) newWindow.opener = self;
                        return false;
                    }

                    function openImagePopup(imageId){
                        var wWidth = 900;
                        if(document.getElementById('maintable').offsetWidth){
                            wWidth = document.getElementById('maintable').offsetWidth*1.05;
                        }
                        else if(document.body.offsetWidth){
                            wWidth = document.body.offsetWidth*0.9;
                        }
                        if(wWidth > 1000) wWidth = 1000;
                        newWindow = window.open("imgdetails.php?imgid="+imageId,'image'+imageId,'scrollbars=1,toolbar=1,resizable=1,width='+(wWidth)+',height=600,left=20,top=20');
                        if (newWindow.opener == null) newWindow.opener = self;
                        return false;
                    }

                    </script>

                    <div class="tndiv" style="margin-top: 15px; margin-bottom: 15px">
                        <div class="tnimg">
                            <a href="#" onclick="openImagePopup(<?php echo $imgId; ?>)">
                                <img src="<?php echo $imgUrl; ?>" />
                            </a>
                        </div>
                        <div>
                            <?php echo "<a href=\"#\" onclick=\"openTaxonPopup(".$imgArr['tid'].")\" >".$imgArr['sciname']."</a><br />" . $imgArr['stateprovince'] . "<br /><a href=\"#\" onclick=\"openIndPU(".$imgArr['occid'].");\">" . $imgArr['instcode'] . " - " . $imgArr['catalognumber']. "</a>"; ?>
                            </a>
                        </div>
                    </div>
                <?php
                }
                echo "</div>";
?>