<?php
include_once('../../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceEditorManager.php');
include_once($SERVER_ROOT.'/classes/OccurrenceDuplicate.php');
header("Content-Type: text/html; charset=".$CHARSET);

$occid = $_GET['occid'];
$occIndex = $_GET['occindex'];
$crowdSourceMode = $_GET['csmode'];

$occManager = new OccurrenceEditorManager();
$occManager->setOccId($occid);
$oArr = $occManager->getOccurMap();
$occArr = $oArr[$occid];

$genticArr = $occManager->getGeneticArr();

$dupManager = new OccurrenceDuplicate();
$dupClusterArr = $dupManager->getClusterArr($occid);
?>
<script>
	function validateVoucherAddForm(f){
		if(f.clidvoucher.value == ""){
			alert("Select a checklist to which you want to link the voucher");
			return false;
		}
		if(f.tidvoucher.value == ""){
			alert("Voucher cannot be linked to a checklist until the taxonomic name has been resolved (e.g. name not linked to taxonomic thesaurus");
			return false;
		}
		return true;
	}

	function openDupeWindow(){
		$url = "rpc/dupelist.php?curoccid=<?php echo $occid.'&recordedby='.urlencode($occArr['recordedby']).'&recordnumber='.$occArr['recordnumber'].'&eventdate='.$occArr['eventdate']; ?>";
		dupeWindow=open($url,"dupelist","resizable=1,scrollbars=1,toolbar=0,width=900,height=600,left=20,top=20");
		if (dupeWindow.opener == null) dupeWindow.opener = self;
	}

	function deleteDuplicateLink(dupid, occid){
		if(confirm("Are you sure you want to unlink the record as a duplicate?")){
			$.ajax({
				type: "POST",
				url: "rpc/dupedelete.php",
				dataType: "json",
				data: { dupid: dupid, occid: occid }
			}).done(function( retStr ) {
				if(retStr == "1"){
					$("#dupediv-"+occid).hide();
				}
				else{
					alert("ERROR deleting duplicate: "+retStr);
				}
			});
		}
	}

	function openIndividual(target) {
		occWindow=open("../individual/index.php?occid="+target,"occdisplay","resizable=1,scrollbars=1,toolbar=0,width=900,height=600,left=20,top=20");
		if (occWindow.opener == null) occWindow.opener = self;
	}

	function submitEditGeneticResource(f){
		if(f.resourcename.value == ""){
			alert("Genetic resource name must not be blank");
		}
		else{
			f.submit();
		}
	}

	function submitDeleteGeneticResource(f){
		if(confirm("Are you sure you want to premently remove this resource?")){
			f.submit();
		}
	}

	function submitAddGeneticResource(f){
		if(f.resourcename.value == ""){
			alert("Genetic resource name must not be blank");
		}
		else{
			f.submit();
		}
	}
</script>

<div id="voucherdiv" style="width:795px;">
	<?php
	$userChecklists = $occManager->getUserChecklists();
	$checklistArr = $occManager->getVoucherChecklists();
	?>
	<fieldset style="padding:20px">
		<legend><b>Checklist Voucher Linkages</b></legend>
		<?php
		if($userChecklists){
			?>
			<div style="float:right;margin-right:15px;">
				<a href="#" onclick="toggle('voucheradddiv');return false;" title="Link Specimen to Checklist as Voucher" ><img src="../../images/add.png" /></a>
			</div>
			<div id="voucheradddiv" style="display:<?php echo ($checklistArr?'none':'block'); ?>;">
				<form name="voucherAddForm" method="post" target="occurrenceeditor.php" onsubmit="return validateVoucherAddForm(this)">
					<select name="clidvoucher">
						<option value="">Select a Checklist</option>
						<option value="">---------------------------------------------</option>
						<?php
						foreach($userChecklists as $clid => $clName){
							echo '<option value="'.$clid.'">'.$clName.'</option>';
						}
						?>
					</select>
					<input name="tidvoucher" type="hidden" value="<?php echo $occArr['tidinterpreted']; ?>" />
					<input name="csmode" type="hidden" value="<?php echo $crowdSourceMode; ?>" />
					<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
					<input name="tabtarget" type="hidden" value="3" />
					<input name="submitaction" type="submit" value="Link to Checklist as Voucher" />
				</form>
			</div>
			<?php
		}
		//Display list of checklists specimen is linked to
		if($checklistArr){
			foreach($checklistArr as $vClid => $vClName){
				echo '<div style="margin:3px">';
				echo '<a href="../../checklists/checklist.php?showvouchers=1&cl='.$vClid.'" target="_blank">'.$vClName.'</a> ';
				if(array_key_exists($vClid, $userChecklists)){
					echo '<a href="occurrenceeditor.php?submitaction=deletevoucher&delclid='.$vClid.'&occid='.$occid.'&tabtarget=3" title="Delete voucher link" onclick="return confirm(\"Are you sure you want to remove this voucher link?\")">';
					echo '<img src="../../images/drop.png" style="width:12px;" />';
					echo '</a>';
				}
				echo '</div>';
			}
			echo '<div style="margin:15px 0px">* If a red X is not display to right of checklist name, you do not have editing rights for that checklist and therefore cannot remove the voucher link without contacting checklist owner';
		}
		?>
	</fieldset>
</div>
<div id="duplicatediv" style="margin-top:20px;">
	<fieldset>
		<legend><b>Duplicate Specimens</b></legend>
		<div style="float:right;margin-right:15px;">
			<button onclick="openDupeWindow();return false;">Search for Records to Link</button>
		</div>
		<div style="clear:both;">
			<form id="dupeRefreshForm" name="dupeRefreshForm" method="post" target="occurrenceeditor.php">
				<input name="tabtarget" type="hidden" value="3" />
				<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
			</form>
			<?php
			if($dupClusterArr){
				foreach($dupClusterArr as $dupid => $dupArr){
					echo '<div id="dupediv-'.$occid.'">';
					echo '<div style="padding:15px;"><b>Cluster Title:</b> '.$dupArr['title'];
					echo '<div style="float:right" title="Unlink this occurrences from duplicate cluster but maintain other specimens as a valid duplicate cluster">';
					echo '<button name="unlinkthisdupebutton" onclick="deleteDuplicateLink('.$dupid.','.$occid.')">Remove this Occurrence from Cluster</button>';
					echo '</div>';
					$note = trim($dupArr['description'].'; '.$dupArr['notes'],' ;');
					if($note) echo ' - '.$notes;
					echo '</div>';
					echo '<div style="20px 0px"><hr/><hr/></div>';
					$innerDupArr = $dupArr['o'];
					foreach($innerDupArr as $dupeOccid => $dArr){
						if($occid != $dupeOccid){
							?>
							<div id="dupediv-<?php echo $dupeOccid; ?>" style="clear:both;margin:15px;">
								<div style="font-weight:bold;font-size:120%;">
									<?php echo $dArr['collname'].' ('.$dArr['instcode'].($dArr['collcode']?':'.$dArr['collcode']:'').')'; ?>
								</div>
								<div style="float:right;">
									<button name="unlinkdupebut" onclick="deleteDuplicateLink(<?php echo $dupid.','.$dupeOccid; ?>)">Unlink</button>
								</div>
								<?php
								echo '<div style="float:left;margin:5px 15px">';
								if($dArr['recordedby']) echo '<div>'.$dArr['recordedby'].' '.$dArr['recordnumber'].'<span style="margin-left:40px;">'.$dArr['eventdate'].'</span></div>';
								if($dArr['catnum']) echo '<div><b>Catalog Number:</b> '.$dArr['catnum'].'</div>';
								if($dArr['occurrenceid']) echo '<div><b>GUID:</b> '.$dArr['occurrenceid'].'</div>';
								if($dArr['sciname']) echo '<div><b>Latest Identification:</b> '.$dArr['sciname'].'</div>';
								if($dArr['identifiedby']) echo '<div><b>Identified by:</b> '.$dArr['identifiedby'].'<span stlye="margin-left:30px;">'.$dArr['dateidentified'].'</span></div>';
								if($dArr['notes']) echo '<div>'.$dArr['notes'].'</div>';
								echo '<div><a href="#" onclick="openIndividual('.$dupeOccid.')">Show Full Details</a></div>';
								echo '</div>';
								if($dArr['url']){
									$url = $dArr['url'];
									$tnUrl = $dArr['tnurl'];
									if(!$tnUrl) $tnUrl = $url;
									if($IMAGE_DOMAIN){
										if(substr($url,0,1) == '/') $url = $IMAGE_DOMAIN.$url;
										if(substr($tnUrl,0,1) == '/') $tnUrl = $IMAGE_DOMAIN.$tnUrl;
									}
									echo '<div style="float:left;margin:10px;">';
									echo '<a href="'.$url.'" target="_blank">';
									echo '<img src="'.$tnUrl.'" style="width:100px;border:1px solid grey" />';
									echo '</a>';
									echo '</div>';
								}
								echo '<div style="margin:10px 0px;clear:both"><hr/></div>';
								?>
							</div>
							<?php
						}
					}
					echo '</div>';
				}
			}
			else{
				if($dupClusterArr === false){
					echo $dupManager->getErrorStr();
				}
				else{
					echo '<div style="font-weight:bold;font-size:120%;margin:15px 0px;">No Linked Duplicate Records</div>';
				}
			}
			?>
		</div>
	</fieldset>
</div>
<div id="geneticdiv" style="margin-top:20px;">
	<fieldset>
		<legend><b>Genetic Resources</b></legend>
		<div style="float:right;">
			<a href="#" onclick="toggle('genadddiv');return false;" title="Add a new genetic resource" ><img src="../../images/add.png" /></a>
		</div>
		<div id="genadddiv" style="display:<?php echo ($genticArr?'none':'block'); ?>;">
			<fieldset>
				<legend><b>Add New Resource</b></legend>
				<form name="addgeneticform" method="post" action="occurrenceeditor.php">
					<div style="margin:2px;">
						<b>Name:</b><br/>
						<input name="resourcename" type="text" value="" style="width:50%" />
					</div>
					<div style="margin:2px;">
						<b>Identifier:</b><br/>
						<input name="identifier" type="text" value="" style="width:50%" />
					</div>
					<div style="margin:2px;">
						<b>Locus:</b><br/>
						<input name="locus" type="text" value="" style="width:95%" />
					</div>
					<div style="margin:2px;">
						<b>URL:</b><br/>
						<input name="resourceurl" type="text" value="" style="width:95%" />
					</div>
					<div style="margin:2px;">
						<b>Notes:</b><br/>
						<input name="notes" type="text" value="" style="width:95%" />
					</div>
					<div style="margin:2px;">
						<input name="submitaction" type="hidden" value="addgeneticsubmit" />
						<input name="csmode" type="hidden" value="<?php echo $crowdSourceMode; ?>" />
						<input name="tabtarget" type="hidden" value="3" />
						<input name="subbut" type="button" value="Add New Genetic Resource" onclick="submitAddGeneticResource(this.form)" />
						<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
					</div>
				</form>
			</fieldset>
		</div>
		<div style="clear:both;">
			<?php
			foreach($genticArr as $genId => $gArr){
				?>
				<div style="float:right;">
					<a href="#" onclick="toggle('genedit-<?php echo $genId; ?>');return false;"><img src="../../images/edit.png" /></a>
				</div>
				<div style="margin:15px;">
					<div style="font-weight:bold;margin-bottom:5px;"><?php echo $gArr['name']; ?></div>
					<div style="margin-left:15px;"><b>Identifier:</b> <?php echo $gArr['id']; ?></div>
					<div style="margin-left:15px;"><b>Locus:</b> <?php echo $gArr['locus']; ?></div>
					<div style="margin-left:15px;">
						<b>URL:</b> <a href="<?php echo $gArr['resourceurl']; ?>" target="_blank"><?php echo $gArr['resourceurl']; ?></a>
					</div>
					<div style="margin-left:15px;"><b>Notes:</b> <?php echo $gArr['notes']; ?></div>
				</div>
				<div id="genedit-<?php echo $genId; ?>" style="display:none;margin-left:25px;">
					<fieldset>
						<legend><b>Genetic Resource Editor</b></legend>
						<form name="editgeneticform" method="post" action="occurrenceeditor.php">
							<div style="margin:2px;">
								<b>Name:</b><br/>
								<input name="resourcename" type="text" value="<?php echo $gArr['name']; ?>" style="width:50%" />
							</div>
							<div style="margin:2px;">
								<b>Identifier:</b><br/>
								<input name="identifier" type="text" value="<?php echo $gArr['id']; ?>" style="width:50%" />
							</div>
							<div style="margin:2px;">
								<b>Locus:</b><br/>
								<input name="locus" type="text" value="<?php echo $gArr['locus']; ?>" style="width:95%" />
							</div>
							<div style="margin:2px;">
								<b>URL:</b><br/>
								<input name="resourceurl" type="text" value="<?php echo $gArr['resourceurl']; ?>" style="width:95%" />
							</div>
							<div style="margin:2px;">
								<b>Notes:</b><br/>
								<input name="notes" type="text" value="<?php echo $gArr['notes']; ?>" style="width:95%" />
							</div>
							<div style="margin:2px;">
								<input name="submitaction" type="hidden" value="editgeneticsubmit" />
								<input name="subbut" type="button" value="Save Edits" onclick="submitEditGeneticResource(this.form)" />
								<input name="genid" type="hidden" value="<?php echo $genId; ?>" />
								<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
								<input name="csmode" type="hidden" value="<?php echo $crowdSourceMode; ?>" />
								<input name="tabtarget" type="hidden" value="3" />
							</div>
						</form>
					</fieldset>
					<fieldset>
						<legend><b>Delete Genetic Resource</b></legend>
						<form name="delgeneticform" method="post" action="occurrenceeditor.php">
							<div style="margin:2px;">
								<input name="submitaction" type="hidden" value="deletegeneticsubmit" />
								<input name="subbut" type="button" value="Delete Resource" onclick="submitDeleteGeneticResource(this.form)" />
								<input name="genid" type="hidden" value="<?php echo $genId; ?>" />
								<input name="occid" type="hidden" value="<?php echo $occid; ?>" />
								<input name="csmode" type="hidden" value="<?php echo $crowdSourceMode; ?>" />
								<input name="tabtarget" type="hidden" value="3" />
							</div>
						</form>
					</fieldset>
				</div>
				<?php
			}
			?>
		</div>
	</fieldset>
</div>