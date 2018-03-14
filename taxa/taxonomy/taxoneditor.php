<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyEditorManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: '.$CLIENT_ROOT.'/profile/index.php?refurl=../taxa/taxonomy/taxoneditor.php?'.$_SERVER['QUERY_STRING']);

$submitAction = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
$tabIndex = array_key_exists('tabindex',$_REQUEST)?$_REQUEST['tabindex']:0;

$tid = $_REQUEST["tid"];
$taxAuthId = array_key_exists('taxauthid', $_REQUEST)?$_REQUEST["taxauthid"]:1;

$taxonEditorObj = new TaxonomyEditorManager();
$taxonEditorObj->setTid($tid);
$taxonEditorObj->setTaxAuthId($taxAuthId);

$editable = false;
if($IS_ADMIN || array_key_exists("Taxonomy",$USER_RIGHTS)){
	$editable = true;
}

$statusStr = '';
if($editable){
	if(array_key_exists("taxonedits",$_POST)){
		$statusStr = $taxonEditorObj->submitTaxonEdits($_POST);
	}
	elseif($submitAction == 'updatetaxstatus'){
		$statusStr = $taxonEditorObj->submitTaxStatusEdits($_POST['parenttid'],$_POST['tidaccepted']);
	}
	elseif(array_key_exists("synonymedits",$_REQUEST)){
		$statusStr = $taxonEditorObj->submitSynonymEdits($_POST['tidsyn'], $tid, $_POST['unacceptabilityreason'], $_POST['notes'], $_POST['sortsequence']);
	}
	elseif($submitAction == 'linktoaccepted'){
		$deleteOther = array_key_exists("deleteother",$_REQUEST)?true:false;
		$statusStr = $taxonEditorObj->submitAddAcceptedLink($_REQUEST["tidaccepted"],$deleteOther);
	}
	elseif(array_key_exists('deltidaccepted',$_REQUEST)){
		$statusStr = $taxonEditorObj->removeAcceptedLink($_REQUEST['deltidaccepted']);
	}
	elseif(array_key_exists("changetoaccepted",$_REQUEST)){
		$tidAccepted = $_REQUEST["tidaccepted"];
		$switchAcceptance = false;
		if(array_key_exists("switchacceptance",$_REQUEST)){
			$switchAcceptance = true;
		}
		$statusStr = $taxonEditorObj->submitChangeToAccepted($tid,$tidAccepted,$switchAcceptance);
	}
	elseif($submitAction == 'changetonotaccepted'){
		$tidAccepted = $_REQUEST["tidaccepted"];
		$statusStr = $taxonEditorObj->submitChangeToNotAccepted($tid,$tidAccepted,$_POST['unacceptabilityreason'],$_POST['notes']);
	}
	elseif($submitAction == 'updatehierarchy'){
		$statusStr = $taxonEditorObj->rebuildHierarchy($tid);
	}
	elseif($submitAction == 'Remap Taxon'){
		$statusStr = $taxonEditorObj->transferResources($_REQUEST['remaptid']);
		header('Location: taxonomydisplay.php?target='.$_REQUEST["genusstr"].'&statusstr='.$statusStr);
	}
	elseif($submitAction == 'Delete Taxon'){
		$statusStr = $taxonEditorObj->deleteTaxon();
		header('Location: taxonomydisplay.php?statusstr='.$statusStr);
	}

	$taxonEditorObj->setTaxon();
}
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE." Taxon Editor: ".$tid; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET;?>"/>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link href="../../js/jquery-ui-1.12.1/jquery-ui.min.css" type="text/css" rel="Stylesheet" />
	<script src="../../js/jquery-3.2.1.min.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui-1.12.1/jquery-ui.min.js" type="text/javascript"></script>
	<script>
		var tid = <?php echo $taxonEditorObj->getTid(); ?>;
		var tabIndex = <?php echo $tabIndex; ?>;
	</script>
	<script src="../../js/symb/taxa.taxonomyeditor.js?ver=201802"></script>
</head>
<body>
<?php
	$displayLeftMenu = (isset($taxa_admin_taxonomyeditorMenu)?$taxa_admin_taxonomyeditorMenu:"true");
	include($SERVER_ROOT.'/header.php');
	if(isset($taxa_admin_taxonomyeditorCrumbs)){
		if($taxa_admin_taxonomyeditorCrumbs){
			echo "<div class='navpath'>";
			echo $taxa_admin_taxonomyeditorCrumbs;
			echo " <b>Taxonomy Editor</b>";
			echo "</div>";
		}
	}
	else{
		?>
		<div class="navpath">
			<a href="../../index.php">Home</a> &gt;&gt;
			<a href="taxonomydisplay.php">Taxonomy Tree Viewer</a> &gt;&gt;
			<b>Taxonomy Editor</b>
		</div>
		<?php
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="color:<?php echo (strpos($statusStr,'SUCCESS') !== false?'green':'red'); ?>;margin:15px;">
				<?php echo $statusStr; ?>
			</div>
			<hr/>
			<?php
		}
		if($editable && $tid){
			?>

			<div style="float:right;" title="Go to taxonomy display">
				<a href="taxonomydisplay.php?target=<?php echo $taxonEditorObj->getUnitName1();?>&showsynonyms=1">
					<img style='border:0px;width:15px;' src='../../images/toparent.png'/>
				</a>
			</div>
			<div style="float:right;" title="Add a New Taxon">
				<a href="taxonomyloader.php">
					<img style='border:0px;width:15px;' src='../../images/add.png'/>
				</a>
			</div>
			<h1>
				<?php
					echo "<a href='../admin/tpeditor.php?tid=".$taxonEditorObj->getTid()."' style='color:inherit;text-decoration:none;'>";
					echo "<i>".$taxonEditorObj->getSciName()."</i> ".$taxonEditorObj->getAuthor()." [".$taxonEditorObj->getTid()."]";
					echo "</a>"
				?>
			</h1>
			<div id="tabs" class="taxondisplaydiv">
				<ul>
					<li><a href="#editdiv">Editor</a></li>
					<li><a href="#taxonstatusdiv">Taxonomic Status</a></li>
					<li><a href="#hierarchydiv">Hierarchy</a></li>
					<li><a href="taxonomydelete.php?tid=<?php echo $tid; ?>&genusstr=<?php echo $taxonEditorObj->getUnitName1(); ?>">Delete</a></li>
				</ul>
				<div id="editdiv" style="height:400px;">
					<div style="float:right;cursor:pointer;" onclick="toggleEditFields()" title="Toggle Taxon Editing Functions">
						<img style='border:0px;' src='../../images/edit.png'/>
					</div>
					<form id="taxoneditform" name="taxoneditform" action="taxoneditor.php" method="post" onsubmit="return validateTaxonEditForm(this)">
						<div style="clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">UnitName1: </div>
							<div class="editfield">
								<?php echo $taxonEditorObj->getUnitInd1()." ".$taxonEditorObj->getUnitName1();?>
							</div>
							<div class="editfield" style="display:none;">
								<div style="float:left;">
									<input type="text" id="unitind1" name="unitind1" style="width:20px;border-style:inset;" value="<?php echo $taxonEditorObj->getUnitInd1(); ?>" />
								</div>
								<div>
									<input type="text" id="unitname1" name="unitname1" style="width:300px;border-style:inset;" value="<?php echo $taxonEditorObj->getUnitName1(); ?>" />
								</div>
							</div>
						</div>
						<div style="clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">UnitName2: </div>
							<div class="editfield">
								<?php echo $taxonEditorObj->getUnitInd2()." ".$taxonEditorObj->getUnitName2();?>
							</div>
							<div class="editfield" style="display:none;">
								<div style="float:left;">
									<input type="text" id="unitind2" name="unitind2" style="width:20px;border-style:inset;" value="<?php echo $taxonEditorObj->getUnitInd2(); ?>" />
								</div>
								<div>
									<input type="text" id="unitname2" name="unitname2" style="width:300px;border-style:inset;" value="<?php echo $taxonEditorObj->getUnitName2(); ?>" />
								</div>
							</div>
						</div>
						<div style="clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">UnitName3: </div>
							<div class="editfield">
								<?php echo $taxonEditorObj->getUnitInd3()." ".$taxonEditorObj->getUnitName3();?>
							</div>
							<div class="editfield" style="display:none;">
								<div style="float:left;">
									<input type="text" id="unitind3" name="unitind3" style="width:50px;border-style:inset;" value="<?php echo $taxonEditorObj->getUnitInd3(); ?>" />
								</div>
								<div>
									<input type="text" id="unitname3" name="unitname3" style="width:300px;border-style:inset;" value="<?php echo $taxonEditorObj->getUnitName3(); ?>" />
								</div>
							</div>
						</div>
						<div style="clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">Author: </div>
							<div class="editfield">
								<?php echo $taxonEditorObj->getAuthor();?>
							</div>
							<div class="editfield" style="display:none;">
								<input type="text" id="author" name="author" style="width:400px;border-style:inset;" value="<?php echo $taxonEditorObj->getAuthor(); ?>" />
							</div>
						</div>
						<div id="kingdomdiv" style="clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">Kingdom: </div>
							<div class="editfield">
								<?php
								echo $taxonEditorObj->getKingdomName();
								?>
							</div>
						</div>
						<div style="clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">Rank Name: </div>
							<div class="editfield">
								<?php echo ($taxonEditorObj->getRankName()?$taxonEditorObj->getRankName():'Non-Ranked Node'); ?>
							</div>
							<div class="editfield" style="display:none;">
								<select id="rankid" name="rankid">
									<option value="0">Non-Ranked Node</option>
									<option value="">---------------------------------</option>
									<?php
									$rankArr = $taxonEditorObj->getRankArr();
									foreach($rankArr as $rankId => $rName){
										echo '<option value="'.$rankId.'" '.($taxonEditorObj->getRankId()==$rankId?'SELECTED':'').'>'.$rName.'</option>';
									}
									?>
								</select>
							</div>
						</div>
						<div style="clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">Notes: </div>
							<div class="editfield">
								<?php echo $taxonEditorObj->getNotes();?>
							</div>
							<div class="editfield" style="display:none;">
								<input type="text" id="notes" name="notes" value="<?php echo $taxonEditorObj->getNotes(); ?>" style="width:475px;" />
							</div>
						</div>
						<div style="clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">Source: </div>
							<div class="editfield">
								<?php echo $taxonEditorObj->getSource();?>
							</div>
							<div class="editfield" style="display:none;">
								<input type="text" id="source" name="source" style="width:475px;" value="<?php echo $taxonEditorObj->getSource(); ?>" />
							</div>
						</div>
						<div style="clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">Locality Security: </div>
							<div class="editfield">
								<?php
									switch($taxonEditorObj->getSecurityStatus()){
										case 0:
											echo "show all locality data";
											break;
										case 1:
											echo "hide locality data";
											break;
										default:
											echo "not set or set to an unknown setting";
											break;
									}
								?>
							</div>
							<div class="editfield" style="display:none;">
								<select id="securitystatus" name="securitystatus">
									<option value="0">select a locality setting</option>
									<option value="0">---------------------------------</option>
									<option value="0" <?php if($taxonEditorObj->getSecurityStatus()==0) echo "SELECTED"; ?>>show all locality data</option>
									<option value="1" <?php if($taxonEditorObj->getSecurityStatus()==1) echo "SELECTED"; ?>>hide locality data</option>
								</select>
								<input type='hidden' name='securitystatusstart' value='<?php echo $taxonEditorObj->getSecurityStatus(); ?>' />
							</div>
						</div>
						<div class="editfield" style="display:none;">
							<input type="hidden" name="tid" value="<?php echo $taxonEditorObj->getTid(); ?>" />
							<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId;?>">
							<input type="submit" id="taxoneditsubmit" name="taxonedits" value="Submit Edits" />
						</div>
					</form>
				</div>
				<div id="taxonstatusdiv" style="min-height:400px;">
					<fieldset style="width:95%;">
						<legend><b>Taxonomic Placement</b></legend>
						<div style="padding:3px 7px;margin:-12px -10px 5px 0px;float:right;">
							<form name="taxauthidform" action="taxoneditor.php" method="post">
								<select name="taxauthid" onchange="this.form.submit()">
									<option value="1">Default Taxonomy</option>
									<option value="1">----------------------------</option>
									<?php
										$ttIdArr = $taxonEditorObj->getTaxonomicThesaurusIds();
										foreach($ttIdArr as $ttID => $ttName){
											echo '<option value='.$ttID.' '.($taxAuthId==$ttID?'SELECTED':'').'>'.$ttName.'</option>';
										}
									?>
								</select>
								<input type="hidden" name="tid" value="<?php echo $taxonEditorObj->getTid(); ?>" />
								<input type="hidden" name="tabindex" value="1" />
							</form>
						</div>
						<div style="font-size:120%;font-weight:bold;">Status:
							<span style='color:red;'>
							<?php
								switch($taxonEditorObj->getIsAccepted()){
									case -2:		//In conflict, needs to be resolved
										echo "In Conflict, needs to be resolved!";
										break;
									case -1:		//Taxonomic status not yet assigned
										echo "Taxonomy not yet defined for this taxon.";
										break;
									case 0:			//Not Accepted
										echo "Not Accepted";
										break;
									case 1:			//Accepted
										echo "Accepted";
										break;
								}
							?>
							</span>
						</div>
						<div style="clear:both;margin:10px;">
							<form name="taxstatusform" action="taxoneditor.php" method="post">
								<div style="float:right;">
									<a href="" onclick="toggle('tsedit');return false;">
										<img style='border:0px;' src='../../images/edit.png'/>
									</a>
								</div>
								<?php
								if($taxonEditorObj->getRankId() > 140 && $taxonEditorObj->getFamily()){
									?>
									<div>
										<div style="float:left;width:120px;font-weight:bold;">Family: </div>
										<div style="">
											<?php echo $taxonEditorObj->getFamily();?>
										</div>
									</div>
									<?php
								}
								?>
								<div>
									<div style="float:left;width:120px;font-weight:bold;">Parent Taxon: </div>
									<div class="tsedit">
										<?php echo $taxonEditorObj->getParentNameFull();?>
									</div>
									<div class="tsedit" style="display:none;margin:3px;">
										<input id="parentstr" name="parentstr" type="text" value="<?php echo $taxonEditorObj->getParentName(); ?>" />
										<input name="parenttid" type="hidden" value="<?php echo $taxonEditorObj->getParentTid(); ?>" />
									</div>
								</div>
								<div class="tsedit" style="display:none;clear:both;">
									<input type="hidden" name="tid" value="<?php echo $taxonEditorObj->getTid(); ?>" />
									<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId;?>">
									<?php
										$aArr = $taxonEditorObj->getAcceptedArr();
										$aStr = key($aArr);
									?>
									<input type="hidden" name="tidaccepted" value="<?php echo ($taxonEditorObj->getIsAccepted()==1?$taxonEditorObj->getTid():$aStr); ?>" />
									<input type="hidden" name="tabindex" value="1" />
									<input type="hidden" name="submitaction" value="updatetaxstatus" />
									<input type='button' name='taxstatuseditsubmit' value='Submit Upper Taxonomy Edits' onclick="submitTaxStatusForm(this.form)" />
								</div>
							</form>
						</div>
						<div id="AcceptedDiv" style="margin-top:30px;clear:both;">
							<?php
							if($taxonEditorObj->getIsAccepted() <> 1){	//Is Not Accepted
								$acceptedArr = $taxonEditorObj->getAcceptedArr();
								?>
								<h3>Accepted Taxon:</h3>
								<div style="float:right;cursor:pointer;" onclick="toggle('acceptedits')">
									<img style="border:0px;width:15px;" src="../../images/edit.png" />
								</div>
								<?php
								if($acceptedArr){
									echo "<ul>\n";
									foreach($acceptedArr as $tidAccepted => $linkedTaxonArr){
										echo "<li id='acclink-".$tidAccepted."'>\n";
										echo "<a href='taxoneditor.php?tid=".$tidAccepted."&taxauthid=".$taxAuthId."'><i>".$linkedTaxonArr["sciname"]."</i></a> ".$linkedTaxonArr["author"]."\n";
										if(count($acceptedArr)>1){
											echo '<span class="acceptedits" style="display:none;"><a href="taxoneditor.php?tabindex=1&tid='.$tid.'&deltidaccepted='.$tidAccepted.'&taxauthid='.$taxAuthId.'">';
											echo '<img style="border:0px;width:12px;" src="../../images/del.png" />';
											echo '</a></span>';
										}
										if($linkedTaxonArr["usagenotes"]){
											echo "<div style='margin-left:10px;'>";
											if($linkedTaxonArr["usagenotes"]) echo "<u>Notes</u>: ".$linkedTaxonArr["usagenotes"];
											echo "</div>\n";
										}
										echo "</li>\n";
									}

									echo "</ul>\n";
								}
								else{
									echo "<div style='margin:20px;'>Accepted Name not yet Designated for this Taxon</div>\n";
								}
								?>
								<div class="acceptedits" style="display:none;">
									<form id="accepteditsform" name="accepteditsform" action="taxoneditor.php" method="post" onsubmit="return verifyLinkToAcceptedForm(this);" >
										<fieldset style="width:380px;margin:20px;">
											<legend><b>Link to Another Accepted Name</b></legend>
											<div>
												Accepted Taxon:
												<input id="aefacceptedstr" name="acceptedstr" type="text" style="width:300px;" />
												<input name="tidaccepted" type="hidden" />
											</div>
											<div>
												<input type="checkbox" name="deleteother" checked /> Remove Other Accepted Links
											</div>
											<div>
												<input type="hidden" name="tid" value="<?php echo $taxonEditorObj->getTid();?>" />
												<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId;?>" />
												<input type="hidden" name="tabindex" value="1" />
												<input type="hidden" name="submitaction" value="linktoaccepted" />
												<input type="submit" name="pseudosubmit" value="Add Link" />
											</div>
										</fieldset>
									</form>
									<?php
									if($acceptedArr && count($acceptedArr)==1){
										?>
										<form id="changetoacceptedform" name="changetoacceptedform" action="taxoneditor.php" method="post">
											<fieldset style="width:350px;margin:20px;">
												<legend><b>Change to Accepted</b></legend>
												<div>
													<input type="checkbox" name="switchacceptance" value="1" checked /> Switch Acceptance with Currently Accepted Name
												</div>
												<div>
													<input type="hidden" name="tid" value="<?php echo $taxonEditorObj->getTid();?>" />
													<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId;?>" />
													<input type="hidden" name="tidaccepted" value="<?php echo $aStr; ?>" />
													<input type="hidden" name="tabindex" value="1" />
													<input type='submit' id='changetoacceptedsubmit' name='changetoaccepted' value='Change Status to Accepted' />
												</div>
											</fieldset>
										</form>
										<?php
									}
									?>
								</div>
							<?php
							}
							?>
						</div>
						<div id="SynonymDiv" style="clear:both;padding-top:15px;">
							<?php
							if($taxonEditorObj->getIsAccepted() <> 0){	//Is Accepted
								?>
								<div style="font-size:110%;"><u><b>Synonyms</b></u></div>
								<div style="float:right;cursor:pointer;" onclick="toggle('tonotaccepted');">
									<img style='border:0px;width:15px;' src='../../images/edit.png'/>
								</div>
								<ul>
								<?php
								$synonymArr = $taxonEditorObj->getSynonyms();
								if($synonymArr){
									foreach($synonymArr as $tidSyn => $synArr){
										echo '<li> ';
										echo '<a href="taxoneditor.php?tid='.$tidSyn.'&taxauthid='.$taxAuthId.'"><i>'.$synArr['sciname'].'</i></a> '.$synArr['author'].' ';
										echo '<a href="#" onclick="toggle(\'syn-'.$tidSyn.'\');">';
										echo '<img style="border:0px;width:10px;" src="../../images/edit.png" />';
										echo '</a>';
										if($synArr["notes"] || $synArr["unacceptabilityreason"]){
											if($synArr["unacceptabilityreason"]){
												echo "<div style='margin-left:10px;'>";
												echo "<u>Reason:</u> ".$synArr["unacceptabilityreason"];
												echo "</div>";
											}
											if($synArr["notes"]){
												echo "<div style='margin-left:10px;'>";
												echo "<u>Notes:</u> ".$synArr["notes"];
												echo "</div>";
											}
										}
										echo "</li>";
										?>
										<fieldset id="syn-<?php echo $tidSyn;?>" style="display:none;">
											<legend><b>Synonym Link Editor</b></legend>
											<form id="synform-<?php echo $tidSyn;?>" name="synform-<?php echo $tidSyn;?>" action="taxoneditor.php" method="post">
												<div style="clear:both;">
													<div style="float:left;width:200px;font-weight:bold;">Unacceptability Reason:</div>
													<div>
														<input id='unacceptabilityreason' name='unacceptabilityreason' type='text' style="width:240px;" value='<?php echo $synArr["unacceptabilityreason"]; ?>' />
													</div>
												</div>
												<div style="clear:both;">
													<div style="float:left;width:200px;font-weight:bold;">Notes:</div>
													<div>
														<input id='notes' name='notes' type='text' style="width:240px;" value='<?php echo $synArr["notes"]; ?>' />
													</div>
												</div>
												<div style="clear:both;">
													<div style="float:left;width:200px;font-weight:bold;">Sort Sequence: </div>
													<div>
														<input id='sortsequence' name='sortsequence' type='text' style="width:30px;" value='<?php echo $synArr["sortsequence"]; ?>' />
													</div>
												</div>
												<div style="clear:both;">
													<div>
														<input type="hidden" name="tid" value="<?php echo $taxonEditorObj->getTid(); ?>" />
														<input type="hidden" name="tidsyn" value="<?php echo $tidSyn; ?>" />
														<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId;?>">
														<input type="hidden" name="tabindex" value="1" />
														<input type='submit' id='syneditsubmit' name='synonymedits' value='Submit Changes' />
													</div>
												</div>
											</form>
										</fieldset>
										<?php
									}
									?>
								</ul>
							<?php
								}
								else{
									echo "<div style='margin:20px;'>No Synonyms Linked to this Taxon</div>";
								}
								?>
								<div id="tonotaccepted" style="display:none;">
									<form id="changetonotacceptedform" name="changetonotacceptedform" action="taxoneditor.php" method="post" onsubmit="return verifyChangeToNotAcceptedForm(this);">
										<fieldset style="width:90%px;">
											<legend><b>Change to Not Accepted</b></legend>
											<div style="margin:5px;">
												<b>Accepted Name:</b>
												<input id="ctnafacceptedstr" name="acceptedstr" type="text" style="width:270px;" />
												<input name="tidaccepted" type="hidden" value="" />
											</div>
											<div style="margin:5px;">
												<b>Reason:</b>
												<input name="unacceptabilityreason" type="text" style="width:400px;" />
											</div>
											<div style="margin:5px;">
												<b>Notes:</b>
												<input name="notes" type="text" style="width:400px;" />
											</div>
											<div style="margin:5px;">
												<input name="tid" type="hidden" value="<?php echo $taxonEditorObj->getTid();?>" />
												<input name="taxauthid" type="hidden" value="<?php echo $taxAuthId;?>">
												<input name="tabindex" type="hidden" value="1" />
												<input name="submitaction" type="hidden" value="changetonotaccepted" />
												<input name="pseudosubmit" type="submit" value="Change Status to Not Accepted" />
											</div>
											<div style="margin:5px;">
												* Synonyms will be transferred to Accepted Taxon
											</div>
											<fieldset id="ctnaError" style="margin:5px;display:none">
												<legend><b>Accepted child taxa need to be resolved</b></legend>

											</fieldset>
										</fieldset>
									</form>
								</div>
								<?php
							}
							?>
						</div>
					</fieldset>
				</div>
				<div id="hierarchydiv" style="height:400px;">
					<fieldset style="width:420px;padding:25px;">
						<legend><b>Quick Query Taxonomic Hierarchy</b></legend>
						<div style="float:right;" title="Rebuild Hierarchy">
							<form name="updatehierarchyform" action="taxoneditor.php" method="post">
								<input type="hidden" name="tid" value="<?php echo $taxonEditorObj->getTid(); ?>"/>
								<input type="hidden" name="taxauthid" value="<?php echo $taxAuthId;?>">
								<input type="hidden" name="submitaction" value="updatehierarchy" />
								<input type="hidden" name="tabindex" value="2" />
								<input type="image" name="imagesubmit" src="../../images/undo.png" style="width:20px;"/>
							</form>
						</div>
						<?php
						if($hierarchyArr = $taxonEditorObj->getHierarchyArr()){
							$indent = 0;
							foreach($hierarchyArr as $hierTid => $hierSciname){
								echo '<div style="margin-left:'.$indent.'px;">';
								echo '<a href="taxoneditor.php?tid='.$hierTid.'">'.$hierSciname.'</a>';
								echo "</div>\n";
								$indent += 10;
							}
							echo '<div style="margin-left:'.$indent.'px;">';
							echo '<a href="taxoneditor.php?tid='.$taxonEditorObj->getTid().'">'.$taxonEditorObj->getSciName().'</a>';
							echo "</div>\n";
						}
						else{
							echo "<div style='margin:10px;'>Empty</div>";
						}
						?>
					</fieldset>
				</div>
			</div>
		<?php
		}
		else{
			if(!$tid){
				if($statusStr != 'SUCCESS: taxon deleted!'){
					echo "<div>Target Taxon missing</div>";
				}
			}
			else{
				?>
				<div style="margin:30px;font-weight:bold;font-size:120%;">
					You are not authorized to access this page
				</div>
				<?php
			}
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>
