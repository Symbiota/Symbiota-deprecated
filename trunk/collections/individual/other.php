<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceIndividualManager.php');
header("Content-Type: text/html; charset=".$charset);

$occid = $_GET["occid"];
$tid = $_GET["tid"];
$collId = $_GET["collid"];
$observerUid = $_GET["obsuid"];
$clid = array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0;

$indManager = new OccurrenceIndividualManager();
$indManager->setOccid($occid);
$genticArr = $indManager->getGeneticArr();
$vClArr = $indManager->getVoucherChecklists();

$isEditor = false;
if($symbUid){
	if(array_key_exists("SuperAdmin",$userRights) 
	|| (array_key_exists('CollAdmin',$userRights) && in_array($collId,$userRights['CollAdmin']))
	|| (array_key_exists('CollEditor',$userRights) && in_array($collId,$userRights['CollEditor']))
	|| $observerUid == $symbUid){
		$isEditor = true;
	}
}
?>
<div id='innertext' style='width:95%; height:95%; clear:both;'>
	<div style="font-weight:bold;font-size:120%;margin-bottom:15px;">Genetic Resources</div>
	<div>
		<?php 
		if($genticArr){
			foreach($genticArr as $genId => $gArr){
				?>
				<div style="margin:15px;">
					<div style="font-weight:bold;"><b>Name:</b> <?php echo $gArr['name']; ?></div>
					<div style="margin-left:10px;"><b>Identifier:</b> <?php echo $gArr['id']; ?></div>
					<div style="margin-left:10px;"><b>Locus:</b> <?php echo $gArr['locus']; ?></div>
					<div style="margin-left:10px;">
						<b>URL:</b> 
						<a href="<?php echo $gArr['resourceurl']; ?>"><?php echo $gArr['resourceurl']; ?></a>
					</div>
					<div style="margin-left:10px;"><b>Notes:</b> <?php echo $gArr['notes']; ?></div>
				</div>
				<?php 
			}
		}
		else{
			echo '<div style="margin:10px 0px 100px 10px">No genetic resources linked to this specimen</div>';
		}
		?>
	</div>
	<div>
		<div style="font-weight:bold;font-size:120%;margin-bottom:15px;">Voucher Relationships</div>
		<?php 	
		if($vClArr){
	    	echo '<div style="font-weight:bold;font-size:120%;">Specimen serves as voucher of the following checklists</div>';
	    	echo '<ul>';
	    	foreach($vClArr as $id => $clName){
	    		echo '<li><a href="../../checklists/checklist.php?showvouchers=1&cl='.$id.'" target="_blank">'.$clName.'</a></li>';
	    	}
	    	echo '</ul>';
	    }
	    else{
	    	echo '<div style="margin:10px">Specimen has not been designated as a voucher for a species checklist</div>';
	    }
		if($isAdmin || array_key_exists("ClAdmin",$userRights)){
			?>
			<div style='margin-top:15px;height:400px;'>
	    		<?php 
				if($clArr = $indManager->getChecklists(array_keys($vClArr))){
		    		?>
					<fieldset style='margin:20px;'>
			    		<legend><b>New Voucher Assignment</b></legend>
						<?php
						if($tid){
							?>
							<div style='margin:10px;'>
								<form action="../../checklists/clsppeditor.php" onsubmit="return verifyVoucherForm(this);">
									<div>
										Add as voucher to checklist: 
										<input name='voccid' type='hidden' value='<?php echo $occid; ?>'>
										<input name='tid' type='hidden' value='<?php echo $tid; ?>'>
										<select id='clid' name='clid'>
							  				<option value='0'>Select a Checklist</option>
							  				<option value='0'>--------------------------</option>
							  				<?php 
								  			foreach($clArr as $clKey => $clValue){
								  				echo "<option value='".$clKey."' ".($clid==$clKey?"SELECTED":"").">$clValue</option>\n";
											}
											?>
										</select>
									</div>
									<div style='margin:5px 0px 0px 10px;'>
										Notes: 
										<input name='vnotes' type='text' size='50' title='Viewable to public'>
									</div>
									<div style='margin:5px 0px 0px 10px;'>
										Editor Notes: 
										<input name='veditnotes' type='text' size='50' title='Viewable only to checklist editors'>
									</div>
									<div>
										<input type='submit' name='action' value='Add Voucher'>
									</div>
								</form>
							</div>
							<?php 
						}
			    		else{
			    			?>
			    			<div style='margin:20px;'>
			    				Unable to use this specimen record as a voucher because  
			    				scientific name counld not be verified in the taxonomic thesaurus (misspelled?)
			    			</div>
							<?php 
						}
						?>
					</fieldset>
					<?php
				}
				?>
			</div>
			<?php 
		}
		?>
	</div>
</div>
