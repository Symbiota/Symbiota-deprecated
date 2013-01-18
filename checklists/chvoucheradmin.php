<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ChecklistManager.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0; 
$startPos = (array_key_exists('start',$_REQUEST)?(int)$_REQUEST['start']:0);
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:''; 
$clManager = new ChecklistManager();
$clManager->setClValue($clid);
$clManager->getNonVoucheredCnt();
?>
<style type="text/css">
	li{margin:5px;}
</style>


<?php 
if($action == 'VoucherConflicts'){
	$conflictArr = $clManager->getConflictVouchers();
	?>
	<hr/>
	<h2>Possible Voucher Conflicts</h2>
	<div>
		Click on Checklist ID to open the editing pane for that record. 
	</div>
	<?php 
	if($conflictArr){
		?>
		<table class="styledtable">
			<tr><th><b>Checklist ID</b></th><th><b>Collector</b></th><th><b>Specimen ID</b></th><th><b>Identified By</b></th></tr>
			<?php
			foreach($conflictArr as $tid => $vArr){
				?>
				<tr>
					<td>
						<a href="#" onclick="return openPopup('clsppeditor.php?tid=<?php echo $tid."&clid=".$clid; ?>','editorwindow');">
							<?php echo $vArr['listid'] ?>
						</a>
					</td>
					<td>
						<?php echo $vArr['recordnumber'] ?>
					</td>
					<td>
						<?php echo $vArr['specid'] ?>
					</td>
					<td>
						<?php echo $vArr['identifiedby'] ?>
					</td>
				</tr>
				<?php 
			}
			?>
		</table>
		<?php 
	}
	else{
		echo '<h3>No conflicts exist</h3>';
	}
}
elseif($action == 'ListNonVouchered'){
	$nonVoucherArr = $clManager->getNonVoucheredTaxa($startPos);
	?>
	<hr/>
	<h2>Taxa without Vouchers</h2>
	<div style="margin:20px;">
		Taxa are listed 100 at a time. Use navigation controls located at the bottom of the list to advance to the next group of taxa. 
		If the SQL fragment has been set, clicking on taxon name will dynamically query the system for possible voucher specimens.  
	</div>
	<div style="margin:20px;">
		<?php 
		if($nonVoucherArr){
			foreach($nonVoucherArr as $family => $tArr){
				echo '<div style="font-weight:bold;">'.strtoupper($family).'</div>';
				echo '<div style="margin:10px;text-decoration:italic;">';
				foreach($tArr as $tid => $sciname){
					echo '<div>';
					echo '<a href="#" onclick="return openPopup(\'../taxa/index.php?taxauthid=1&taxon='.$tid.'&cl='.$clid.'\',\'taxawindow\');">'.$sciname.'</a> ';
					if($dynSql){
						echo '<a href="#" onclick="return openPopup(\'../collections/list.php?db=all&thes=1&reset=1&taxa='.$tid.'&clid='.$clid.'&targettid='.$tid.'\',\'editorwindow\');">';
						echo '<img src="../images/link.png" style="width:13px;" title="Link Voucher Specimens" />';
						echo '</a>';
					}
					echo '</div>';
				}
				echo '</div>';
			}
			if($nonVoucherCnt > 100){
				echo '<div style="text-weight:bold;">';
				if($startPos > 100) echo '<a href="checklist.php?cl='.$clid.'&proj='.$pid.'&submitaction=ListNonVouchered&tabindex=2&emode=2&start='.($startPos-100).'">';
				echo '&lt;&lt; Previous';
				if($startPos > 100) echo '</a>';
				echo ' || '.$startPos.'-'.($startPos+100).' Records || ';
				if(($startPos + 100) <= $nonVoucherCnt) echo '<a href="checklist.php?cl='.$clid.'&proj='.$pid.'&submitaction=ListNonVouchered&tabindex=2&emode=2&start='.($startPos+100).'">';
				echo 'Next &gt;&gt;';
				if(($startPos + 100) <= $nonVoucherCnt) echo '</a>';
				echo '</div>';
			}
		}
		else{
			echo '<h2>All taxa contain voucher links</h2>';
		}
		?>
	</div>
	<?php 
}
elseif($action == 'ListMissingTaxa'){
	$missingArr = $clManager->getMissingTaxa($startPos);
	?>
	<hr/>
	<h2>Possible Missing Taxa</h2>
	<div style="margin:20px;">
		SQL Fragment is used in an attempt to query for specimens that represent species not yet added to the list.  
		Taxa are listed 100 at a time. Use navigation controls located at the bottom of the list to advance to the next group of taxa. 
		If the SQL fragment has been set, clicking on taxon name will dynamically query the system for possible voucher specimens.  
	</div>
	<div style="float:right;">
		<a href="voucherreports.php?rtype=missingoccurcsv&clid=<?php echo $clid; ?>" target="_blank" title="Download list of possible vouchers">
			<img src="<?php echo $clientRoot; ?>/images/dl.png" style="border:0px;" />
		</a>
	</div>
	<div style="margin:20px;clear:both;">
		<?php 
		if($missingArr){
			$paginationStr = '';
			if(count($missingArr) > 100){
				$paginationStr = '<div style="margin:15px;text-weight:bold;width:100%;text-align:right;">';
				if($startPos > 100) $paginationStr .= '<a href="checklist.php?cl='.$clid.'&proj='.$pid.'&submitaction=ListNonVouchered&tabindex=2&emode=2&start='.($startPos-100).'">';
				$paginationStr .= '&lt;&lt; Previous';
				if($startPos > 100) $paginationStr .= '</a>';
				$paginationStr .= ' || '.$startPos.'-'.($startPos+100).' Records || ';
				if(($startPos + 100) <= $nonVoucherCnt) $paginationStr .= '<a href="checklist.php?cl='.$clid.'&proj='.$pid.'&submitaction=ListNonVouchered&tabindex=2&emode=2&start='.($startPos+100).'">';
				$paginationStr .= 'Next &gt;&gt;';
				if(($startPos + 100) <= $nonVoucherCnt) $paginationStr .= '</a>';
				$paginationStr .= '</div>';
				echo $paginationStr;
			}
			foreach($missingArr as $tid => $sn){
				echo '<div>';
				echo '<a href="#" onclick="return openPopup(\'../collections/list.php?db=all&thes=1&reset=1&taxa='.$sn.'&clid='.$clid.'&targettid='.$tid.'\',\'editorwindow\');">';
				echo $sn;
				echo '</a>';
				echo '</div>';
			}
			if(count($missingArr) > 100){
				echo $paginationStr;
			}
		}
		else{
			echo '<h2>No possible addition found</h2>';
		}
		?>
	</div>
	<?php 
}
elseif($action == 'ListChildTaxa'){ 
	$childArr = $clManager->getChildTaxa();
	?>
	<hr/>
	<h2>Children Taxa</h2>
	<div style="margin:20px;">
	</div>
	<div style="margin:20px;">
		<?php 
		if($childArr){
			?>
			<table class="styledtable">
				<tr><th><b>Taxon</b></th><th><b>Source Checklist</b></th></tr>
				<?php
				foreach($childArr as $tid => $sArr){
					?>
					<tr>
						<td>
							<?php echo $sArr['sciname'] ?>
						</td>
						<td>
							<?php echo $sArr['cl'] ?>
						</td>
					</tr>
					<?php 
				}
				?>
			</table>
			<?php
		} 
		else{
			echo '<h2>No new taxa to inherit from a child checklist</h2>';
		}
		?>
	</div>
	<?php 
}	
?>

