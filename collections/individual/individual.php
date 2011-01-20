<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceIndividualManager.php');
header("Content-Type: text/html; charset=".$charset);

$occId = array_key_exists("occid",$_REQUEST)?trim($_REQUEST["occid"]):0;
$collId = array_key_exists("collid",$_REQUEST)?trim($_REQUEST["collid"]):0;
$pk = array_key_exists("pk",$_REQUEST)?trim($_REQUEST["pk"]):"";

$indManager = new OccurrenceIndividualManager($occId);
if($collId) $indManager->setCollId($collId); 
if($pk) $indManager->setDbpk($pk);

$occArr = $indManager->getOccData();

$displayLocality = false;
$isEditor = false;
if($symbUid){
	if(array_key_exists("SuperAdmin",$userRights) 
	|| (array_key_exists('CollAdmin',$userRights) && in_array($occArr['occid'],$userRights['CollAdmin']))
	|| (array_key_exists('CollEditor',$userRights) && in_array($occArr['occid'],$userRights['CollEditor']))){
		$displayLocality = true;
		$isEditor = true;
	}
	elseif(!$occArr['localitysecurity'] || array_key_exists("RareSppAdmin",$userRights) || array_key_exists("RareSppReadAll",$userRights) 
	|| (array_key_exists("RareSppReader",$userRights) && in_array($occArr['collid'],$userRights["RareSppReader"]))){
		$displayLocality = true;
	}
}
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Detailed Collection Record Information</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
	<script type="text/javascript">

	    function checkVoucherForm(f){
			var clTarget = f.elements["clid"].value; 
	        if(clTarget == "0"){
	            window.alert("Please select a checklist");
	            return false;
	        }
            return true;
	    }

		function toggle(target){
			var divObjs = document.getElementsByTagName("div");
		  	for (i = 0; i < divObjs.length; i++) {
		  		var obj = divObjs[i];
		  		if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
						if(obj.style.display=="none"){
							obj.style.display="inline";
						}
				 	else {
				 		obj.style.display="none";
				 	}
				}
			}
		}
	</script>
</head>

<body>
<?php 
	$displayLeftMenu = (isset($collections_individual_individualMenu)?$collections_individual_individualMenu:false);
	include($serverRoot."/header.php");
?>
	<!-- This is inner text! -->
	<div id="innertext" style="width:600px;">
		<?php
		if($occArr){
			?>
			<div style="float:left;padding:15px;text-align:center;font-weight:bold;width:60px;">
				<img border='1' height='50' width='50' src='../../<?php echo $occArr['icon']; ?>'/><br/>
				<?php 
				echo $occArr['institutioncode'];
				if($occArr['collectioncode']) echo ':'.$occArr['collectioncode'];
				if($occArr['secondaryinstcode']){
					echo '<br/>';
					echo $occArr['secondaryinstcode'];
					if($occArr['secondarycollcode']) echo ':'.$occArr['secondarycollcode'];
				}
				?>
			</div>
			<div style="float:left;padding:25px;width:450px;">
				<span style="font-size:18px;font-weight:bold;vertical-align:60%;">
					<?php echo $occArr['collectionname']; ?>
				</span>
			</div>
			<div style="clear:both;">
				<div style='clear:both;'>
					<div style='float:left;'>
						<b>Taxon:</b> 
						<?php echo ($occArr['identificationqualifier']?$occArr['identificationqualifier']." ":""); ?>
						<i><?php echo $occArr['sciname']; ?></i> <?php echo $occArr['scientificnameauthorship']; ?><br/>
						<b>Family:</b> <?php echo $occArr['family']; ?>
					</div>
					<div style='float:right;'>
						<b>Accession #:</b> <?php echo $occArr['catalognumber']; ?>
						<?php 
						if($occArr['occurrenceid']){
							?> 
							<div title="Global Unique Identifier">
								<b>GUID: </b>
								<?php echo $occArr['occurrenceid']; ?>
							</div>
							<?php 
						}
						if($occArr['othercatalognumbers']){
							?>
							<div style="float:right;padding:2px;background-color:#EEEEEE;border:1px solid #AAC7E9;">
								<b><?php echo $occArr['ownerinstitutioncode']; ?>:</b>
								<?php echo $occArr['othercatalognumbers']; ?>
							</div>
							<?php 
						}
						?>
					</div>
				</div>
				<div style="clear:both;">
					<?php 
					if($occArr['identifiedby']){ 
						?>
						<div>
							<b>Determiner:</b> <?php echo $occArr['identifiedby']; ?>
							<?php if($occArr['dateidentified']) echo ' ('.$occArr['dateidentified'].')'; ?>
						</div>
						<?php 
					} 
					if($occArr['identificationremarks']){ 
						?>
						<div style="margin-left:10px;">
							<b>ID Remarks:</b>
							<?php echo $occArr['identificationremarks']; ?>
						</div>
						<?php 
					} 
					if($occArr['identificationreferences']){ ?>
						<div style="margin-left:10px;">
							<b>ID References:</b>
							<?php echo $occArr['identificationreferences']; ?>
						</div>
						<?php 
					}
					if(array_key_exists('dets',$occArr)){
						?>
						<div class="detdiv" style="margin-left:10px;cursor:pointer;" onclick="toggle('detdiv');">
							<img src="../../images/plus.gif" style="border:0px;" />
							Show Determination History
						</div>
						<div class="detdiv" style="display:none;">
							<div style="margin-left:10px;cursor:pointer;" onclick="toggle('detdiv');">
								<img src="../../images/minus.gif" style="border:0px;" />
								Hide Determination History
							</div>
							<fieldset style="width:350px;margin:5px 0px 10px 10px;border:1px solid grey;">
								<legend><b>Determination History</b></legend>
								<?php
								$firstIsOut = false;
								$dArr = $occArr['dets'];
								foreach($dArr as $detId => $detArr){
								 	if($firstIsOut) echo '<hr />';
									 	$firstIsOut = true;
								 	?>
									 <div style="margin:10px;">
									 	<?php 
									 	if($detArr['qualifier']) echo $detArr['qualifier']; 
									 	echo ' <b><i>'.$detArr['sciname'].'</i></b> ';
									 	echo $detArr['author']."\n";
									 	?>
									 	<div style="">
									 		<b>Determiner: </b>
									 		<?php echo $detArr['identifiedby']; ?>
									 	</div>
									 	<div style="">
									 		<b>Date: </b>
									 		<?php echo $detArr['date']; ?>
									 	</div>
									 	<?php 
									 	if($detArr['ref']){ ?>
										 	<div style="">
										 		<b>ID References: </b>
										 		<?php echo $detArr['ref']; ?>
										 	</div>
									 		<?php 
									 	} 
									 	if($detArr['notes']){ 
									 		?>
										 	<div style="">
										 		<b>ID Remarks: </b>
										 		<?php echo $detArr['notes']; ?>
										 	</div>
									 		<?php 
									 	}
									 	?>
									 </div>
									<?php 
								}
								?>
							</fieldset>
						</div>
						<?php 
					}
					if($occArr['typestatus']){ ?>
						<div>
							<b>Type Status:</b>
							<?php echo $occArr['typestatus']; ?>
						</div>
						<?php 
					} 
					?>
				</div>
				<div style="clear:both;">
					<div style="float:left;">
						<b>Collector:</b> 
						<?php 
						echo $occArr['recordedby'].'&nbsp;&nbsp;&nbsp;';
						echo $occArr['recordnumber'].'&nbsp;&nbsp;&nbsp;';
						?>
					</div>
					<div style="float:right;">
						<?php
						if($occArr['eventdate']){
							?>
							<div>
								<b>Collection Date: </b> 
								<?php 
								echo $occArr['eventdate']; 
								if($occArr['eventdateend']){
									echo ' - '.$occArr['eventdateend'];
								}
								?>
							</div>
							<?php 
						}
						if($occArr['verbatimeventdate']){
							?>
							<div>
								<b>Verbatim Collection Date: </b> 
								<?php echo $occArr['verbatimeventdate']; ?>
							</div>
							<?php 
						}
						?>
					</div> 
				</div>
				<div style="clear:both;">
					<?php 
					if($occArr['associatedcollectors']){ 
						?>
						<div>
							<b>Additional Collectors:</b> 
							<?php echo $occArr['associatedcollectors']; ?>
						</div>
						<?php 
					}
					?>
				</div>
				<?php 
				$localityStr1 = ($occArr['country']?$occArr['country']:'Country Not Recorded').'; ';
				$localityStr1 .= ($occArr['stateprovince']?$occArr['stateprovince']:'State/Province Not Recorded').'; ';
				if($occArr['county']) $localityStr1 .= $occArr['county'].'; ';
				?>
				<div>
					<b>Locality:</b>
					<?php 
					echo $localityStr1;
					if($displayLocality){
						echo $occArr['locality'];
					}
					?>
				</div>
				<?php 
				if($displayLocality){
					if($occArr['decimallatitude']){
						?>
						<div style="margin-left:10px;">
							<?php 
							echo $occArr['decimallatitude'].'&nbsp;&nbsp;'.$occArr['decimallongitude'];
							if($occArr['coordinateuncertaintyinmeters']) echo ' +-'.$occArr['coordinateuncertaintyinmeters']; 
							if($occArr['geodeticdatum']) echo '&nbsp;&nbsp;'.$occArr['geodeticdatum'];
							?>
						</div>
						<?php 
					}
					if($occArr['verbatimcoordinates']){
						?>
						<div style="margin-left:10px;">
							<b>Verbatim Coordinates: </b>
							<?php echo $occArr['verbatimcoordinates']; ?>
						</div>
						<?php 
					}
					if($occArr['georeferenceremarks']){
						?>
						<div style="margin-left:10px;clear:both;">
							<b>Georeference Remarks: </b>
							<?php echo $occArr['georeferenceremarks']; ?>
						</div>
						<?php 
					}
					if($occArr['minimumelevationinmeters'] || $occArr['verbatimelevation']){
						?>
						<div style="clear:both;margin-left:10px;">
							<div style="float:left;">
								<b>Elevation:</b>
								<?php 
								echo $occArr['minimumelevationinmeters'];
								if($occArr['maximumelevationinmeters']){
									echo ' - '.$occArr['maximumelevationinmeters'];
								} 
								?>
								meters
							</div>
							<?php
							if($occArr['verbatimelevation']){
								?>
								<div style="float:right;">
									<b>Verbatim Elevation: </b>
									<?php echo $occArr['verbatimelevation']; ?>
								</div>
								<?php 
							}
							?>
						</div>
						<?php 
					}
				}
				else{
					?>
					<div style='color:red;'>This species has a sensitive status.</div>
					<div>For more information, please contact collection manager below.</div>
					<?php 
				}
				if($occArr['habitat']){ 
					?>
					<div style="clear:both;">
						<b>Habitat:</b> 
						<?php echo $occArr['habitat']; ?>
					</div>
					<?php 
				}
				if($occArr['associatedtaxa']){ 
					?>
					<div style="clear:both;">
						<b>Associated Species:</b> 
						<?php echo $occArr['associatedtaxa']; ?>
					</div>
					<?php 
				}
				if($occArr['dynamicproperties']){ 
					?>
					<div style="clear:both;">
						<b>Description:</b> 
						<?php echo $occArr['dynamicproperties']; ?>
					</div>
					<?php 
				}
				if($occArr['reproductivecondition']){ 
					?>
					<div style="clear:both;">
						<b>Phenology:</b> 
						<?php echo $occArr['reproductivecondition']; ?>
					</div>
					<?php 
				}
				$noteStr = '';
				if($occArr['occurrenceremarks']) $noteStr .= "; ".$occArr['occurrenceremarks'];
				if($occArr['establishmentmeans']) $noteStr .= "; ".$occArr['establishmentmeans'];
				if($occArr['cultivationstatus']) $noteStr .= "; Cultivated";
				if($noteStr){ 
					?>
					<div style="clear:both;">
						<b>Notes:</b>
						<?php echo substr($noteStr,2); ?>
					</div>
					<?php 
				}
				if($occArr['disposition']){
					?>
					<div style="clear:both;">
						<b>Duplicates sent to: </b>
						<?php echo $occArr['disposition']; ?>
					</div>
					<?php 
				}
				?>
			</div>
			<div style="clear:both;padding:10px;">
				<?php 
				if(array_key_exists('imgs',$occArr)){
					$iArr = $occArr['imgs'];
					?>
					<fieldset style="padding:10px;">
						<legend><b>Images of Specimen</b></legend>
						<?php 
						foreach($iArr as $imgId => $imgArr){
							?>
							<div style='float:left;text-align:center;padding:5px;'>
								<a href='/../../imagelib/imgdetails.php?imgid=<?php echo $imgId; ?>'>
									<img border=1 width='150' src='<?php echo ($imgArr['tnurl']?$imgArr['tnurl']:$imgArr['url']); ?>' title='<?php echo $imgArr['caption']; ?>'/>
								</a>
								<?php if($imgArr['lgurl']) echo '<br/><a href="'.$imgArr['lgurl'].'">Large Version</a>'; ?>
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
			if($occArr['individualurl']){
				$indUrl = str_replace('--PK--',$occArr['dbpk'],$occArr['individualurl']);
				echo '<div style="margin-top:10px;clear:both;">'.$occArr['collectionname'].' <a href="'.$indUrl.'"> display page</a></div>';
			}
			?>
			<div style="margin-top:10px;clear:both;">
				For additional information on this specimen, please contact: 
				<?php 
				$emailSubject = $defaultTitle.' occurrence #'.$occArr['occid'];
				$emailBody = 'Specimen being referenced: http://'.$_SERVER['SERVER_NAME'].$clientRoot.'/collections/individual/individual.php?occid='.$occArr['occid'];
				$emailRef = 'subject='.$emailSubject.'&cc='.$adminEmail.'&body='.$emailBody;
				?>
				<a href="mailto:<?php echo $occArr['email'].'?'.$emailRef; ?>">
					<?php echo $occArr['contact'].' ('.$occArr['email'].')'; ?>
				</a>
			</div>
			<?php 
			if($isEditor){
				?>
				<div style="margin:10px 0px;">
					You are authorized to edit this record: 
					<a href="../editor/occurrenceeditor.php?occid=<?php echo $occArr['occid'];?>">
						Open Occurrence Editor
					</a>
				</div>
				<?php
			}

			if($isAdmin || array_key_exists("ClAdmin",$userRights)){
				?>
	    		<div style='margin-top:15px;'>
					<div class='voucheredit' style="display:block;">
						<span onclick="toggle('voucheredit');">
							<img src='../../images/plus.gif'>
						</span>
						Show Voucher Editing Box
					</div>
					<div class='voucheredit' style="display:none;">
						<div>
							<span onclick="toggle('voucheredit');">
								<img src='../../images/minus.gif'>
							</span>
							Hide Voucher Editing Box
						</div>
						<fieldset style='margin:5px 0px 0px 0px;'>
	    					<legend>Voucher Assignment:</legend>
							<?php
				    		if($occArr['tidinterpreted']){
								if($clArr = $indManager->getChecklists($paramsArr)){
									?>
									<form action="../../checklists/clsppeditor.php" onsubmit="return checkVoucherForm(this);">
										<div style='margin:5px 0px 0px 10px;'>
											Add as voucher to checklist: 
											<input name='voccid' type='hidden' value='<?php echo $occArr['occid']; ?>'>
											<input name='tid' type='hidden' value='<?php echo $occArr['tidinterpreted']; ?>'>
											<select id='clid' name='clid'>
								  				<option value='0'>Select a Checklist</option>
								  				<option value='0'>--------------------------</option>
								  				<?php 
									  			$clid = (array_key_exists("clid",$_REQUEST)?$_REQUEST["clid"]:0);
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
										<div style='margin:5px 0px 0px 10px;'>
											<input type='submit' name='action' value='Add Voucher'>
										</div>
									</form>
							<?php 
								}
				    		}
				    		else{
				    			?>
				    			<div style='font-weight:bold;'>
				    				Unable to use this specimen record as a voucher due to:
				    			</div>
				    			<ul>
				    			<?php 
				    				if(!$occArr['tidinterpreted']){
				    					echo "<li>Scientific name is not in Taxonomic Thesaurus (name maybe misspelled)";
				    					
				    				}
				    			?>
				    			</ul>
								<?php 
							}
							?>
						</fieldset>
					</div>
				</div>
				<?php 
			}
        }
        else{
        	echo "<h2>There is a problem retrieving data.</h2><h3>Please try again later.</h3>";
        }
		?>
	</div>
	<?php
	include($serverRoot."/footer.php");
	?>
</body>
</html> 

