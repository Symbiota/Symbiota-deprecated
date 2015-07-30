<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceChecklistManager.php');

$checklistManager = new OccurrenceChecklistManager();
$taxonFilter = array_key_exists("taxonfilter",$_REQUEST)?$_REQUEST["taxonfilter"]:0;
$stArrCollJson = array_key_exists("jsoncollstarr",$_REQUEST)?$_REQUEST["jsoncollstarr"]:'';
$stArrSearchJson = array_key_exists("starr",$_REQUEST)?$_REQUEST["starr"]:'';

if($stArrCollJson && $stArrSearchJson){
	$stArrSearchJson = str_replace("%apos;","'",$stArrSearchJson);
	$collStArr = json_decode($stArrCollJson, true);
	$searchStArr = json_decode($stArrSearchJson, true);
	$stArr = array_merge($searchStArr,$collStArr);
	$checklistManager->setSearchTermsArr($stArr);
}

?>

	<div>
		<div class='button' style='margin:10px;float:right;width:13px;height:13px;' title='Download Checklist Data'>
			<a href='download/index.php?usecookies=false&starr=<?php echo $stArrSearchJson; ?>&jsoncollstarr=<?php echo $stArrCollJson; ?>&dltype=checklist&taxonFilterCode=<?php echo $taxonFilter; ?>'>
				<img width="15px" src="../images/dl.png" />
			</a>
		</div>
		<?php 
		if($keyModIsActive === true || $keyModIsActive === 1){
		?>
			<div class='button' style='margin:10px;float:right;width:13px;height:13px;' title='Open in Interactive Key Interface'>
				<a href='checklistsymbiota.php?usecookies=false&starr=<?php echo $stArrSearchJson; ?>&jsoncollstarr=<?php echo $stArrCollJson; ?>&taxonfilter=<?php echo $taxonFilter; ?>&interface=key'>
					<img width='15px' src='../images/key.png'/>
				</a>
			</div>
		<?php 
		}
		if($floraModIsActive){
		?>
			<div class='button' style='margin:10px;float:right;width:13px;height:13px;' title='Open in Checklist Explorer Interface'>
				<a href='checklistsymbiota.php?usecookies=false&starr=<?php echo $stArrSearchJson; ?>&jsoncollstarr=<?php echo $stArrCollJson; ?>&taxonfilter=<?php echo $taxonFilter; ?>&interface=checklist'>
					<img width='15px' src='../images/list.png'/>
				</a>
			</div>
		<?php
		}
		?>
		<div style='margin:10px;float:right;'>
			<form name="changetaxonomy" id="changetaxonomy" action="list.php" method="post">
				Taxonomic Filter:
					<select id="taxonfilter" name="taxonfilter" onchange="document.changetaxonomy.submit();">
						<option value="0">Raw Data</option>
						<?php 
							$taxonAuthList = $checklistManager->getTaxonAuthorityList();
							foreach($taxonAuthList as $taCode => $taValue){
								echo "<option value='".$taCode."' ".($taCode == $taxonFilter?"SELECTED":"").">".$taValue."</option>";
							}
	                        ?>
					</select>
					<input type="hidden" name="tabindex" value="0" />
					<input type="hidden" name="usecookies" value="false" />
					<input type="hidden" name="jsoncollstarr" value='<?php echo ($stArrCollJson?$stArrCollJson:''); ?>' />
					<input type="hidden" name="starr" value='<?php echo ($stArrSearchJson?$stArrSearchJson:''); ?>' />
			</form>
		</div>
		<div style="clear:both;"><hr/></div>
		<?php
			$checklistArr = $checklistManager->getChecklist($taxonFilter);
			echo "<div style='font-weight:bold;font-size:125%;'>Taxa Count: ".$checklistManager->getChecklistTaxaCnt()."</div>";
			$undFamilyArray = null;
			if(array_key_exists("undefined",$checklistArr)){
				$undFamilyArray = $checklistArr["undefined"];
				unset($checklistArr["undefined"]); 
			}
			ksort($checklistArr);
			foreach($checklistArr as $family => $sciNameArr){
				sort($sciNameArr);
				echo "<div style='margin-left:5;margin-top:5;'><h3>".$family."</h3></div>";
				foreach($sciNameArr as $sciName){
					echo "<div style='margin-left:20;font-style:italic;'><a target='_blank' href='../taxa/index.php?taxon=".$sciName."'>".$sciName."</a></div>";
				}
			}
			if($undFamilyArray){
				echo "<div style='margin-left:5;margin-top:5;'><h3>Family Not Defined</h3></div>";
				foreach($undFamilyArray as $sciName){
					echo "<div style='margin-left:20;font-style:italic;'><a target='_blank' href='../taxa/index.php?taxon=".$sciName."'>".$sciName."</a></div>";
				}
			}
		?>
	</div>
