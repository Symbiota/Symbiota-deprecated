<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ChecklistVoucherAdmin.php');

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0; 
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$displayMode = (array_key_exists('displaymode',$_REQUEST)?$_REQUEST['displaymode']:0);
$startPos = (array_key_exists('start',$_REQUEST)?(int)$_REQUEST['start']:0);

$vManager = new ChecklistVoucherAdmin();
$vManager->setClid($clid);

$isEditor = false;
if($isAdmin || (array_key_exists("ClAdmin",$userRights) && in_array($clid,$userRights["ClAdmin"]))){
	$isEditor = true;
	
}
?>

<div id="innertext">
	<?php 
	$nonVoucherCnt = $vManager->getNonVoucheredCnt();
	?>
	<div style="float:right;">
		<form name="displaymodeform" method="post" action="voucheradmin.php">
			<b>Display Mode:</b> 
			<select name="displaymode" onchange="this.form.submit()">
				<option value="0">Species List</option>
				<option value="1" <?php echo ($displayMode?'SELECTED':''); ?>>Batch Linking</option>
			</select>
			<input name="clid" type="hidden" value="<?php echo $clid; ?>" />
			<input name="pid" type="hidden" value="<?php echo $pid; ?>" />
			<input name="tabindex" type="hidden" value="0" />
		</form>
	</div> 
	<div style='float:left;font-weight:bold;margin-top:3px;height:30px;'>
		Taxa without Vouchers: <?php echo $nonVoucherCnt; ?>
	</div>
	<div style='float:left;'>
		<a href="voucheradmin.php?clid=<?php echo $clid.'&pid='.$pid; ?>"><img src="../images/refresh.jpg" style="border:0px;" title="Refresh List" /></a>
	</div>
	<?php 
	if($displayMode){
		?>
		<div style="clear:both;">
			<div style="margin:20px;">
				

			</div>
			<div>
				<?php 
				if($specArr = $vManager->getNonVoucheredSpecimens($startPos)){
					?>
					<form name="batchnonvoucherform" method="post" action="voucheradmin.php" onsubmit="return validateBatchNonVoucherForm(this)">
						<table class="styledtable">
							<tr>
								<th>
									<span title="Select All">
					         			<input name="occids[]" type="checkbox" onclick="selectAll(this);" value="0-0" />
					         		</span>
								</th>
								<th>Checklist ID</th>
								<th>Collector</th>
								<th>Locality</th>
							</tr>
							<?php 
							foreach($specArr as $cltid => $occArr){
								foreach($occArr as $occid => $oArr){
									echo '<tr>';
									echo '<td><input name="occids[]" type="checkbox" value="'.$occid.'-'.$cltid.'" /></td>';
									echo '<td>'.$oArr['sciname'].'</td>';
									echo '<td>';
									echo $oArr['recordedby'].' ('.($oArr['recordnumber']?$oArr['recordnumber']:'s.n.').')<br/>';
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
						<input name="submitaction" value="Add Vouchers" type="submit" />
					</form>
					<?php 
				}
				else{
					echo '<div style="font-weight:bold;font-size:120%;">No vouchers located</div>';
				}
				?>
			</div>
		</div>
		
		<?php 
	}
	else{
		?>
		<div style="clear:both;">
			<div style="margin:20px;">
				Taxa are listed 100 at a time. Use navigation controls located at the bottom of the list to advance to the next group of taxa. 
				Clicking on a taxon name will use the search statemtn to dynamically query the system for possible voucher specimens.
			</div>
			<div style="margin:20px;">
				<?php 
				if($nonVoucherArr = $vManager->getNonVoucheredTaxa($startPos)){
					foreach($nonVoucherArr as $family => $tArr){
						echo '<div style="font-weight:bold;">'.strtoupper($family).'</div>';
						echo '<div style="margin:10px;text-decoration:italic;">';
						foreach($tArr as $tid => $sciname){
							?>
							<div>
								<a href="#" onclick="openPopup('../taxa/index.php?taxauthid=1&taxon=<?php echo $tid.'&cl='.$clid; ?>','taxawindow');return false;"><?php echo $sciname; ?></a>
								<a href="#" onclick="openPopup('../collections/list.php?db=all&thes=1&reset=1&taxa=<?php echo $tid.'&clid='.$clid.'&targettid='.$tid;?>','editorwindow');return false;">
									<img src="../images/link.png" style="width:13px;" title="Link Voucher Specimens" />
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
						echo '&lt;&lt; Previous';
						if($startPos > 0) echo '</a>';
						echo ' || <b>'.$startPos.'-'.($startPos+($arrCnt<100?$arrCnt:100)).' Records</b> || ';
						if(($startPos + 100) <= $nonVoucherCnt) echo '<a href="voucheradmin.php?clid='.$clid.'&pid='.$pid.'&start='.($startPos+100).'">';
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
		</div>
		<?php
	}	
	?>
</div>
