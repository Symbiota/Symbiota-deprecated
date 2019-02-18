<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/content/lang/collections/checklist.'.$LANG_TAG.'.php');
include_once($SERVER_ROOT.'/classes/OccurrenceChecklistManager.php');
include_once($SERVER_ROOT.'/classes/SOLRManager.php');

$checklistManager = new OccurrenceChecklistManager();
$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:'';
$stArrCollJson = array_key_exists("jsoncollstarr",$_REQUEST)?$_REQUEST["jsoncollstarr"]:'';
$stArrSearchJson = array_key_exists("starr",$_REQUEST)?$_REQUEST["starr"]:'';

//Sanitation
if(!is_numeric($taxonFilter)) $taxonFilter = 1;

$checklistArr = Array();
$taxaCnt = 0;

$solrManager = new SOLRManager();
$checklistManager = new OccurrenceChecklistManager();

if($stArrCollJson || $stArrSearchJson){
	$stArrSearchJson = str_replace("%apos;","'",$stArrSearchJson);
	$collStArr = ($stArrCollJson?json_decode($stArrCollJson, true):Array());
	$searchStArr = ($stArrSearchJson?json_decode($stArrSearchJson, true):Array());
	$stArr = array_merge($searchStArr,$collStArr);

    if($SOLR_MODE){
        $solrManager->setSearchTermsArr($stArr);
        $solrArr = $solrManager->getTaxaArr();
        if($taxonFilter && is_numeric($taxonFilter)){
            $tidArr = $solrManager->getSOLRTidList($solrArr);
            $checklistArr = $checklistManager->getTidChecklist($tidArr,$taxonFilter);
            $taxaCnt = $checklistManager->getChecklistTaxaCnt();
        }
        else{
            $checklistArr = $solrManager->translateSOLRTaxaList($solrArr);
            $taxaCnt = $solrManager->getChecklistTaxaCnt();
        }
    }
    else{
        $checklistManager->setSearchTermsArr($stArr);
        $checklistArr = $checklistManager->getChecklist($taxonFilter);
        $taxaCnt = $checklistManager->getChecklistTaxaCnt();
    }
}
?>
<div >
    <div class='button' style='margin:10px;float:right;width:13px;height:13px;' title='<?php echo $LANG['DOWNLOAD_TITLE']; ?>'>
		<a href='download/index.php?starr=<?php echo htmlentities($stArrSearchJson, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>&jsoncollstarr=<?php echo htmlentities($stArrCollJson, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>&dltype=checklist&taxonFilterCode=<?php echo $taxonFilter; ?>'>
			<img width="15px" src="../images/dl.png" />
		</a>
	</div>
	<?php
	if($KEY_MOD_IS_ACTIVE){
	?>
		<div class='button' style='margin:10px;float:right;width:13px;height:13px;' title='<?php echo $LANG['OPEN_KEY']; ?>'>
			<a href='checklistsymbiota.php?starr=<?php echo htmlentities($stArrSearchJson, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>&jsoncollstarr=<?php echo htmlentities($stArrCollJson, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>&taxonfilter=<?php echo $taxonFilter; ?>&interface=key'>
				<img width='15px' src='../images/key.png'/>
			</a>
		</div>
	<?php
	}
	if($FLORA_MOD_IS_ACTIVE){
	?>
		<div class='button' style='margin:10px;float:right;width:13px;height:13px;' title='<?php echo $LANG['OPEN_CHECKLIST_EXPLORER']; ?>'>
			<a href='checklistsymbiota.php?starr=<?php echo htmlentities($stArrSearchJson, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>&jsoncollstarr=<?php echo htmlentities($stArrCollJson, ENT_QUOTES | ENT_HTML5, 'UTF-8'); ?>&taxonfilter=<?php echo $taxonFilter; ?>&interface=checklist'>
				<img width='15px' src='../images/list.png'/>
			</a>
		</div>
	<?php
	}
	?>
	<div style='margin:10px;float:right;'>
		<form name="changetaxonomy" id="changetaxonomy" action="list.php" method="post">
			<?php echo $LANG['TAXONOMIC_FILTER']; ?>:
            <select id="taxonfilter" name="taxonfilter" onchange="document.changetaxonomy.submit();">
                <option value="0"><?php echo $LANG['RAW_DATA'];?></option>
                <?php
                    $taxonAuthList = $checklistManager->getTaxonAuthorityList();
                    foreach($taxonAuthList as $taCode => $taValue){
                        echo "<option value='".$taCode."' ".($taCode == $taxonFilter?"SELECTED":"").">".$taValue."</option>";
                    }
                    ?>
            </select>
            <input type="hidden" name="tabindex" value="0" />
        </form>
	</div>
	<div style="clear:both;"><hr/></div>
	<?php
		echo '<div style="font-weight:bold;font-size:125%;">'.$LANG['TAXA_COUNT'].': '.$taxaCnt.'</div>';
		$undFamilyArray = Array();
		if(array_key_exists("undefined",$checklistArr)){
			$undFamilyArray = $checklistArr["undefined"];
			unset($checklistArr["undefined"]);
		}
		ksort($checklistArr);
		foreach($checklistArr as $family => $sciNameArr){
			sort($sciNameArr);
			echo '<div style="margin-left:5;margin-top:5;"><h3>'.$family.'</h3></div>';
			foreach($sciNameArr as $sciName){
				echo '<div style="margin-left:20;font-style:italic;"><a target="_blank" href="../taxa/index.php?taxon='.$sciName.'">'.$sciName.'</a></div>';
			}
		}
		if($undFamilyArray){
			echo '<div style="margin-left:5;margin-top:5;"><h3>'.$LANG['FAMILY_NOT_DEFINED'].'</h3></div>';
			foreach($undFamilyArray as $sciName){
				echo '<div style="margin-left:20;font-style:italic;"><a target="_blank" href="../taxa/index.php?taxon='.$sciName.'">'.$sciName.'</a></div>';
			}
		}
	?>
</div>
