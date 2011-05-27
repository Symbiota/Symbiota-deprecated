<?php
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/ChecklistManager.php');

$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0; 
$action = array_key_exists("submitaction",$_REQUEST)?$_REQUEST["submitaction"]:''; 
$clManager = new ChecklistManager();
$clManager->setClValue($clid);
$clManager->getNonVoucheredCnt();
?>
<style type="text/css">
	li{margin:5px;}
</style>

<ul>
	<li>
		<a href="#" onclick="document.sqlbuilder.style.display = 'block';return false;"><b>Modify SQL Fragment</b></a>
		<div style="margin-left:5px;"> 
			The SQL fragment defines geographically boundaries of the research area in order to  
			aid researchers in locating specimen vouchers that will serve as proof that a species occurs within the area.  
			See the Flora Voucher Mapping Tutorial for more details. 
		</div>
		<fieldset style="margin:10px;padding:10px;">
			<legend><b>Current Dynamic SQL Fragment</b></legend>
			<?php
			$dynSql = $clManager->getDynamicSql();
			echo $dynSql?$dynSql:"<b>SQL not set</b>"
			?>
			<form name="sqlbuilder" action="checklist.php" method="post" onsubmit="return validateSqlFragForm(this);" style="display:none;">
				<hr/>
				<div style="margin:10px;">
					Use this form to aid in building the SQL fragment. 
					Click the 'Create SQL Fragment' button to build and save the SQL using the terms 
					supplied in the form. 
					Your data administrator can aid you in establishing more complex SQL fragments than can be created within this form.  
				</div>
				<table style="margin:15px;">
					<tr>
						<td>
							<div style="margin:3px;">
								<b>Country:</b>
								<input type="text" name="country" onchange="" />
							</div>
							<div style="margin:3px;">
								<b>State:</b>
								<input type="text" name="state" onchange="" />
							</div>
							<div style="margin:3px;">
								<b>County:</b>
								<input type="text" name="county" onchange="" />
							</div>
							<div style="margin:3px;">
								<b>Locality:</b>
								<input type="text" name="locality" onchange="" />
							</div>
						</td>
						<td>
							<div style="margin-left:30px;">
								<b>Lat/Long:</b>
								<span style="margin-left:100px;">
									<input type="text" name="latnorth" style="width:70px;" onchange="" title="Latitude North" />
								</span>
							</div>
							<div style="margin-left:112px;">
								<span style="">
									<input type="text" name="lngwest" style="width:70px;" onchange="" title="Longitude West" />
								</span>
								<span style="margin-left:70px;">
									<input type="text" name="lngeast" style="width:70px;" onchange="" title="Longitude East" />
								</span>
							</div>
							<div style="margin-left:187px;">
								<input type="text" name="latsouth" style="width:70px;" onchange="" title="Latitude South" />
							</div>
							<div style="margin-left:50px;">
								<input type="checkbox" name="latlngor" value="1" />
								Include Lat/Long as OR statement
							</div>
							<div style="float:right;margin:20px 20px 0px 0px;">
								<input type="submit" name="submitaction" value="Create SQL Fragment" />
								<input type="hidden" name="tabindex" value="1" />
								<input type="hidden" name="emode" value="2" />
								<input type='hidden' name='cl' value='<?php echo $clid; ?>' />
								<input type='hidden' name='proj' value='<?php echo $proj; ?>' />
							</div>
						</td>
					</tr>
				</table>
			</form>
		</fieldset>
	</li>
	<li>
		<a href="checklist.php?cl=<?php echo $clid; ?>&submitaction=ListNonVouchered&tabindex=1&emode=2">
			<b>List Non-vouchered Taxa</b>
		</a> 
		<div style="margin-left:5px;">
			<?php 
			$nonVoucherCnt = $clManager->getNonVoucheredCnt();
			echo $nonVoucherCnt;
			?> 
			taxa without voucher links
		</div>
	</li>
	<li>
		<?php
		if($clManager->getVoucherCnt()){
			echo '<a href="checklist.php?cl='.$clid.'&submitaction=VoucherConflicts&tabindex=1&emode=2">';
		}
		else{
			echo '<a href="#" onclick="alert(\'There are no conflicts because no vouchers have yet been linked to this checklist\')">';
		} 
		?>
			<b>Check for Voucher Conflicts</b>
		</a> 
		<div style="margin-left:5px;">
			List vouchers where the current 
			identifications conflict with the scientific name to which they are linked. 
			This is usually due to recent annotations.
		</div>
	</li>
	<li>
		<?php 
		if($dynSql){
			?>
			<a href="checklist.php?cl=<?php echo $clid; ?>&submitaction=ListMissingTaxa&tabindex=1&emode=2">
			<?php 
		}
		else{
			?>
			<a href="#" onclick="alert('SQL Fragment needs to be established before this function can be used');toggle('sqlfragdiv');">
			<?php 
		}
		?>
		<b>Search for Missing Taxa</b>
		</a>
		<div style="margin-left:5px;">
			Look for specimens collected within the research area that represent taxa not yet added to list
		</div>
	</li>
	<?php 
	if($clManager->hasChildrenChecklists()){
		?>
		<li>
			<a href="checklist.php?cl=<?php echo $clid; ?>&submitaction=ListChildTaxa&tabindex=1&emode=2">
				<b>List New Taxa from Children Lists</b>
			</a> 
			<div style="margin-left:5px;">
				Display taxa that have been added to a child checklist but has not yet been added to this list
			</div> 
		</li>
		<?php
	} 
	?>
</ul>

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
		<table id="edittable">
			<tr><th><b>Checklist ID</b></th><th><b>Collector</b></th><th><b>Specimen ID</b></th><th><b>Identified By</b></th></tr>
			<?php
			foreach($conflictArr as $tid => $vArr){
				?>
				<tr>
					<td>
						<a href="#" onclick="openPopup('clsppeditor.php?tid=<?php echo $tid."&clid=".$clid; ?>','editorwindow');return false;">
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
	$startPos = (array_key_exists('start',$_REQUEST)?(int)$_REQUEST['start']:0);
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
					if($dynSql) echo '<a href="#" onclick="openPopup(\'../collections/list.php?db=all&thes=1&reset=1&taxa='.$sciname.'&clid='.$clid.'&targettid='.$tid.'\',\'editorwindow\');return false;">';
					echo $sciname;
					if($dynSql) echo '</a>';
					echo '</div>';
				}
				echo '</div>';
			}
			if($nonVoucherCnt > 100){
				echo '<div style="text-weight:bold;">';
				if($startPos > 100) echo '<a href="checklist.php?cl='.$clid.'&submitaction=ListNonVouchered&tabindex=1&emode=2&start='.($startPos-100).'">';
				echo '&lt;&lt; Previous';
				if($startPos > 100) echo '</a>';
				echo ' || '.$startPos.'-'.($startPos+100).' Records || ';
				if(($startPos + 100) <= $nonVoucherCnt) echo '<a href="checklist.php?cl='.$clid.'&submitaction=ListNonVouchered&tabindex=1&emode=2&start='.($startPos+100).'">';
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
	$startPos = (array_key_exists('start',$_REQUEST)?(int)$_REQUEST['start']:0);
	$missingArr = $clManager->getMissingTaxa($startPos);
	?>
	<hr/>
	<h2>Possible Missing Taxa</h2>
	<div style="margin:20px;">
		SQL Fragment is used in an attempt to query for specimens that represent species not yet added to the list.  
		Taxa are listed 100 at a time. Use navigation controls located at the bottom of the list to advance to the next group of taxa. 
		If the SQL fragment has been set, clicking on taxon name will dynamically query the system for possible voucher specimens.  
	</div>
	<div style="margin:20px;">
		<?php 
		if($missingArr){
			foreach($missingArr as $tid => $sn){
				echo '<div>';
				echo '<a href="#" onclick="openPopup(\'../collections/list.php?db=all&thes=1&reset=1&taxa='.$sn.'&clid='.$clid.'&targettid='.$tid.'\',\'editorwindow\');return false;">';
				echo $sn;
				echo '</a>';
				echo '</div>';
			}
			if(count($missingArr) > 100){
				echo '<div style="text-weight:bold;">';
				if($startPos > 100) echo '<a href="checklist.php?cl='.$clid.'&submitaction=ListNonVouchered&tabindex=1&emode=2&start='.($startPos-100).'">';
				echo '&lt;&lt; Previous';
				if($startPos > 100) echo '</a>';
				echo ' || '.$startPos.'-'.($startPos+100).' Records || ';
				if(($startPos + 100) <= $nonVoucherCnt) echo '<a href="checklist.php?cl='.$clid.'&submitaction=ListNonVouchered&tabindex=1&emode=2&start='.($startPos+100).'">';
				echo 'Next &gt;&gt;';
				if(($startPos + 100) <= $nonVoucherCnt) echo '</a>';
				echo '</div>';
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
			<table id="edittable">
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

