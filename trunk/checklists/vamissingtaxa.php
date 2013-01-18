<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ChecklistVoucherAdmin.php');

$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:""; 
$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0; 
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$startPos = (array_key_exists('start',$_REQUEST)?(int)$_REQUEST['start']:0);

$vManager = new ChecklistVoucherAdmin();
$vManager->setClid($clid);

$isEditor = false;
if($isAdmin || (array_key_exists("ClAdmin",$userRights) && in_array($clid,$userRights["ClAdmin"]))){
	$isEditor = true;
}
?>

<div id="innertext">
	<div style='float:left;font-weight:bold;margin:3px 5px 10px 0px;'>
		Possible Missing Taxa
	</div>
	<div style="float:left;">
		<a href="voucheradmin.php?clid=<?php echo $clid.'&pid='.$pid; ?>&tabindex=1"><img src="../images/refresh.jpg" style="border:0px;" title="Refresh List" /></a>
	</div>
	<div style="margin-left:5px;clear:both;">
		Look for specimens collected within the research area for taxa that are not within checklist. 
		Be patient, this list may take a minute or so to render.
	</div>
	<div>
		<div style="float:right;">
			<a href="voucherreporthandler.php?rtype=missingoccurcsv&clid=<?php echo $clid; ?>" target="_blank" title="Download list of possible vouchers">
				<img src="<?php echo $clientRoot; ?>/images/dl.png" style="border:0px;" />
			</a>
		</div>
		<div style="margin:20px;clear:both;">
			<?php 
			if($missingArr = $vManager->getMissingTaxa($startPos)){
				$taxaCnt = count($missingArr);
				$paginationStr = '';
				if($startPos || count($missingArr) > 100){
					$paginationStr = '<div style="margin:15px;text-weight:bold;width:100%;text-align:right;">';
					if($startPos > 0) $paginationStr .= '<a href="voucheradmin.php?clid='.$clid.'&pid='.$pid.'&tabindex=1&start='.($startPos-100).'">';
					$paginationStr .= '&lt;&lt; Previous';
					if($startPos > 0) $paginationStr .= '</a>';
					$paginationStr .= ' || <b>'.$startPos.'-'.($startPos+($taxaCnt<100?$taxaCnt:100)).' Records</b> || ';
					if(($startPos + 100) <= $taxaCnt) $paginationStr .= '<a href="voucheradmin.php?clid='.$clid.'&pid='.$pid.'&tabindex=1&start='.($startPos+100).'">';
					$paginationStr .= 'Next &gt;&gt;';
					if(($startPos + 100) <= $taxaCnt) $paginationStr .= '</a>';
					$paginationStr .= '</div>';
				}
				foreach($missingArr as $tid => $sn){
					?>
					<div>
						<a href="#" onclick="openPopup('../taxa/index.php?taxauthid=1&taxon=<?php echo $tid.'&cl='.$clid; ?>','taxawindow');return false;"><?php echo $sn; ?></a>
						<a href="#" onclick="openPopup('../collections/list.php?db=all&thes=1&reset=1&taxa=<?php echo $tid.'&clid='.$clid.'&targettid='.$tid;?>','editorwindow');return false;">
							<img src="../images/link.png" style="width:13px;" title="Link Voucher Specimens" />
						</a>
					</div>
					<?php 
				}
				echo $paginationStr;
			}
			else{
				echo '<h2>No possible addition found</h2>';
			}
			?>
		</div>
	</div>
</div>
