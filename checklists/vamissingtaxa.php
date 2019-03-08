<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistVoucherReport.php');

$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:"";
$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;
$pid = array_key_exists("pid",$_REQUEST)?$_REQUEST["pid"]:"";
$displayMode = (array_key_exists('displaymode',$_REQUEST)?$_REQUEST['displaymode']:0);
$startIndex = array_key_exists("start",$_REQUEST)?$_REQUEST["start"]:0;

$vManager = new ChecklistVoucherReport();
$vManager->setClid($clid);
$vManager->setCollectionVariables();

$isEditor = false;
if($IS_ADMIN || (array_key_exists("ClAdmin",$USER_RIGHTS) && in_array($clid,$USER_RIGHTS["ClAdmin"]))){
	$isEditor = true;
}
//Get records
$missingArr = array();
if($displayMode==1){
	$missingArr = $vManager->getMissingTaxaSpecimens($startIndex);
}
elseif($displayMode==2){
	$missingArr = $vManager->getMissingProblemTaxa();
}
else{
	$missingArr = $vManager->getMissingTaxa();
}
?>

<div id="innertext" style="background-color:white;">
	<div style='float:left;font-weight:bold;margin-left:5px'>

		<?php
		if($displayMode == 2){
			echo 'Problem Taxa: ';
		}
		else{
			echo 'Possible Missing Taxa: ';
		}
		echo $vManager->getMissingTaxaCount();
		?>
	</div>
	<div style="float:left;margin-left:5px">
		<a href="voucheradmin.php?clid=<?php echo $clid.'&pid='.$pid.'&displaymode='.$displayMode; ?>&tabindex=1"><img src="../images/refresh.png" style="border:0px;" title="Refresh List" /></a>
	</div>
	<div style="float:left;margin-left:5px;">
		<a href="voucherreporthandler.php?rtype=<?php echo ($displayMode==2?'problemtaxacsv':'missingoccurcsv').'&clid='.$clid; ?>" target="_blank" title="Download Specimen Records">
			<img src="<?php echo $CLIENT_ROOT; ?>/images/dl.png" style="border:0px;" />
		</a>
	</div>
	<div style="float:right;">
		<form name="displaymodeform" method="post" action="voucheradmin.php">
			<b>Display Mode:</b>
			<select name="displaymode" onchange="this.form.submit()">
				<option value="0">Species List</option>
				<option value="1" <?php echo ($displayMode==1?'SELECTED':''); ?>>Batch Linking</option>
				<option value="2" <?php echo ($displayMode==2?'SELECTED':''); ?>>Problem Taxa</option>
			</select>
			<input name="clid" id="clvalue" type="hidden" value="<?php echo $clid; ?>" />
			<input name="pid" type="hidden" value="<?php echo $pid; ?>" />
			<input name="tabindex" type="hidden" value="1" />
		</form>
	</div>
	<div>
		<?php
		$recCnt = 0;
		if($displayMode==1){
			if($missingArr){
				?>
				<div style="clear:both;margin:10px;">
					Listed below are specimens identified to a species not found in the checklist. Use the form to add the
					names and link the vouchers as a batch action.
				</div>
				<form name="batchmissingform" method="post" action="voucheradmin.php" onsubmit="return validateBatchMissingForm(this.form);">
					<table class="styledtable" style="font-family:Arial;font-size:12px;">
						<tr>
							<th>
								<span title="Select All">
									<input name="selectallbatch" type="checkbox" onclick="selectAll(this);" value="0-0" />
								</span>
							</th>
							<th>Specimen ID</th>
							<th>Collector</th>
							<th>Locality</th>
						</tr>
						<?php
						ksort($missingArr);
						foreach($missingArr as $sciname => $sArr){
							foreach($sArr as $occid => $oArr){
								echo '<tr>';
								echo '<td><input name="occids[]" type="checkbox" value="'.$occid.'-'.$oArr['tid'].'" /></td>';
								echo '<td><a href="../taxa/index.php?taxon='.$oArr['tid'].'" target="_blank">'.$sciname.'</a></td>';
								echo '<td>';
								echo $oArr['recordedby'].' '.$oArr['recordnumber'].'<br/>';
								if($oArr['eventdate']) echo $oArr['eventdate'].'<br/>';
								echo '<a href="../collections/individual/index.php?occid='.$occid.'" target="_blank">';
								echo $oArr['collcode'];
								echo '</a>';
								echo '</td>';
								echo '<td>'.$oArr['locality'].'</td>';
								echo '</tr>';
								$recCnt++;
							}
						}
						?>
					</table>
					<div style="margin-top:8px;">
						<input name="usecurrent" type="checkbox" value="1" type="checkbox" checked /> Add name using current taxonomy
					</div>
					<div style="margin-top:3px;">
						<input name="excludevouchers" type="checkbox" value="1" <?php echo ($_REQUEST['excludevouchers']?'checked':''); ?>/> Add names without linking vouchers
					</div>
					<div style="margin-top:8px;">
						<input name="tabindex" value="1" type="hidden" />
						<input name="clid" value="<?php echo $clid; ?>" type="hidden" />
						<input name="pid" value="<?php echo $pid; ?>" type="hidden" />
						<input name="displaymode" value="1" type="hidden" />
						<input name="start" type="hidden" value="<?php echo $startIndex; ?>" />
						<button name="submitaction" type="submit" value="submitVouchers">Submit Vouchers</button>
					</div>
				</form>
				<?php
				echo '<div style="float:left">Specimen count: '.$recCnt.'</div>';
				$queryStr = 'tabindex=1&displaymode=1&clid='.$clid.'&pid='.$pid.'&start='.(++$startIndex);
				if($recCnt > 399) echo '<div style="float:right;margin-right:30px;"><a style="margin-left:10px;" href="voucheradmin.php?'.$queryStr.'">View Next 400</a></div>';
			}
		}
		elseif($displayMode==2){
			if($missingArr){
				?>
				<div style="clear:both;margin:10px;">
					Listed below are species name obtained from specimens matching the above search term but
					are not found within the taxonomic thesaurus (possibly misspelled?). To add as a voucher,
					type the correct name from the checklist, and then click the Link Voucher button.
					The correct name must already be added to the checklist before voucher can be linked.
				</div>
				<table class="styledtable" style="font-family:Arial;font-size:12px;">
					<tr>
						<th>Specimen ID</th>
						<th>Link to</th>
						<th>Collector</th>
						<th>Locality</th>
					</tr>
					<?php
					ksort($missingArr);
					foreach($missingArr as $sciname => $sArr){
						foreach($sArr as $occid => $oArr){
							?>
							<tr>
								<td><?php echo $sciname; ?></td>
								<td>
									<input id="tid-<?php echo $occid; ?>" name="sciname" type="text" value="" onfocus="initAutoComplete('tid-<?php echo $occid; ?>')" />
									<input name="formsubmit" type="button" value="Link Voucher" onclick="linkVoucher(<?php echo $occid.','.$clid; ?>)" title="Link Voucher" />
								</td>
								<?php
								echo '<td>';
								echo $oArr['recordedby'].' '.$oArr['recordnumber'].'<br/>';
								if($oArr['eventdate']) echo $oArr['eventdate'].'<br/>';
								echo '<a href="../collections/individual/index.php?occid='.$occid.'" target="_blank">';
								echo $oArr['collcode'];
								echo '</a>';
								echo '</td>';
								?>
								<td><?php echo $oArr['locality']; ?></td>
							</tr>
							<?php
							$recCnt++;
						}
					}
					?>
				</table>
				<?php
			}
		}
		else{
			if($missingArr){
				?>
				<div style="margin:20px;clear:both;">
					<div style="clear:both;margin:10px;">
						Listed below are species name not found in the checklist but are represented by one or more specimens
						that have a locality matching the above search term.
					</div>
					<?php
					foreach($missingArr as $tid => $sn){
						?>
						<div>
							<a href="#" onclick="openPopup('../taxa/index.php?taxauthid=1&taxon=<?php echo $tid.'&clid='.$clid; ?>','taxawindow');return false;"><?php echo $sn; ?></a>
							<a href="#" onclick="openPopup('../collections/list.php?db=all&thes=1&reset=1&mode=voucher&taxa=<?php echo $tid.'&targetclid='.$clid.'&targettid='.$tid;?>','editorwindow');return false;">
								<img src="../images/link.png" style="width:13px;" title="Link Voucher Specimens" />
							</a>
						</div>
						<?php
						$recCnt++;
					}
					?>
				</div>
				<?php
			}
		}
		?>
	</div>
</div>