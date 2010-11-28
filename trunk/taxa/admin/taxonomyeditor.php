<?php
/*
 * Created on 24 Aug 2009
 * E.E. Gilbert
 */

//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TaxonomyEditorManager.php');
  
$target = array_key_exists("target",$_REQUEST)?$_REQUEST["target"]:"";
$taxonEditorObj = new TaxonomyEditorManager($target);
if(array_key_exists("taxauthid",$_REQUEST)){
	$taxonEditorObj->setTaxAuthId($_REQUEST["taxauthid"]);
}

$editable = false;
if($isAdmin || in_array("Taxonomy",$userRights)){
	$editable = true;
}

if($editable){
	if(array_key_exists("taxonedits",$_REQUEST)){
		$taxonEditArray = Array();
		$taxonEditArray["tid"] = $target;
		$taxonEditArray["unitind1"] = trim($_REQUEST["unitind1"]);
		$taxonEditArray["unitname1"] = trim($_REQUEST["unitname1"]);
		$taxonEditArray["unitind2"] = trim($_REQUEST["unitind2"]);
		$taxonEditArray["unitname2"] = trim($_REQUEST["unitname2"]);
		$taxonEditArray["unitind3"] = trim($_REQUEST["unitind3"]);
		$taxonEditArray["unitname3"] = trim($_REQUEST["unitname3"]);
		$taxonEditArray["author"] = $_REQUEST["author"];
		$taxonEditArray["kingdomid"] = $_REQUEST["kingdomid"];
		$taxonEditArray["rankid"] = $_REQUEST["rankid"];
		$taxonEditArray["source"] = $_REQUEST["source"];
		$taxonEditArray["notes"] = $_REQUEST["notes"];
		$taxonEditArray["securitystatus"] = $_REQUEST["securitystatus"];
		$taxonEditorObj->submitTaxonEdits($taxonEditArray);
	}
	elseif(array_key_exists("taxstatuseditsubmit",$_REQUEST)){
		$tsArr = Array();
		$tsArr["tid"] = $target;
		$tsArr["tidaccepted"] = $_REQUEST["tidaccepted"];
		$tsArr["uppertaxonomy"] = $_REQUEST["uppertaxonomy"];
		$tsArr["family"] = $_REQUEST["family"];
		$tsArr["parenttid"] = $_REQUEST["parenttid"];
		$taxonEditorObj->submitTaxstatusEdits($tsArr);
	}
	elseif(array_key_exists("synonymedits",$_REQUEST)){
		$synEditArr = Array();
		$synEditArr["tid"] = $_REQUEST["tid"];
		$synEditArr["tidaccepted"] = $target;
		$synEditArr["unacceptabilityreason"] = $_REQUEST["unacceptabilityreason"];
		$synEditArr["notes"] = $_REQUEST["notes"];
		$synEditArr["sortsequence"] = $_REQUEST["sortsequence"];
		$taxonEditorObj->submitSynEdits($synEditArr);
	}
	elseif(array_key_exists("addacceptedlink",$_REQUEST)){
		$deleteOther = false;
		if(array_key_exists("deleteother",$_REQUEST)){
			$deleteOther = true;
		}
		$taxonEditorObj->submitAddAcceptedLink($target,$_REQUEST["tidaccepted"],$deleteOther);
	}
	elseif(array_key_exists("changetoaccepted",$_REQUEST)){
		$tidAccepted = $_REQUEST["tidaccepted"];
		$switchAcceptance = false;
		if(array_key_exists("switchacceptance",$_REQUEST)){
			$switchAcceptance = true;
		}
		$taxonEditorObj->submitChangeToAccepted($target,$tidAccepted,$switchAcceptance);
	}
	elseif(array_key_exists("changetonotaccepted",$_REQUEST)){
		$tidAccepted = $_REQUEST["tidaccepted"];
		$taxonEditorObj->submitChangeToNotAccepted($target,$tidAccepted);
	}
	elseif(array_key_exists("updatehierarchy",$_REQUEST)){
		$taxonEditorObj->rebuildHierarchy($target);
	}
	
	$taxonEditorObj->setTaxon();
}
 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<title><?php echo $defaultTitle." Taxon Editor: ".$target; ?></title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css"/>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>"/>
	<script language=javascript>
		var tid = <?php echo $taxonEditorObj->getTid(); ?>;
		var tidAccepted;
		var dalXmlHttp;
	
		function toogle(target){
		  	var divs = document.getElementsByTagName("div");
		  	var i;
		  	for(i = 0; i < divs.length; i++) {
			  	var divObj = divs[i];
				if(divObj.className == target){
					if(divObj.style.display=="none"){
						divObj.style.display="block";
					}
				 	else {
				 		divObj.style.display="none";
				 	}
				}
			}

		  	var spans = document.getElementsByTagName("span");
		  	var j;
		  	for(j = 0; j < spans.length; j++) {
			  	var spanObj = spans[j];
				if(spanObj.className == target){
					if(spanObj.style.display=="none"){
						spanObj.style.display="inline";
					}
				 	else {
				 		spanObj.style.display="none";
				 	}
				}
			}
		}

		function toogleById(target){
			var obj = document.getElementById(target);
			if(obj.style.display=="none"){
				obj.style.display="block";
			}
		 	else {
		 		obj.style.display="none";
		 	}
		}

		function deleteAcceptedLink(tidAcc){
			if (tidAcc == null){
		  		return;
		  	}
			tidAccepted = tidAcc;
			dalXmlHttp=GetXmlHttpObject();
			if (dalXmlHttp==null){
		  		alert ("Your browser does not support AJAX!");
		  		return;
		  	}
			var url="rpc/deleteacceptedlink.php";
			url=url+"?tid="+tid;
			url=url+"&tidaccepted="+tidAccepted;
			url=url+"&sid="+Math.random();
			dalXmlHttp.onreadystatechange=dalStateChanged;
			dalXmlHttp.open("POST",url,true);
			dalXmlHttp.send(null);
		} 
		
		function dalStateChanged(){
			if (dalXmlHttp.readyState==4){
				status = dalXmlHttp.responseText;
				if(status == "0"){
					alert("FAILED: sorry, error while attempting to delete accepted link");
				}
				else{
					document.getElementById("acclink-"+tidAccepted).style.display = "none";
				}
			}
		}

		function GetXmlHttpObject(){
			var xmlHttp=null;
			try{
				// Firefox, Opera 8.0+, Safari, IE 7.x
		  		xmlHttp=new XMLHttpRequest();
		  	}
			catch (e){
		  		// Internet Explorer
		  		try{
		    		xmlHttp=new ActiveXObject("Msxml2.XMLHTTP");
		    	}
		  		catch(e){
		    		xmlHttp=new ActiveXObject("Microsoft.XMLHTTP");
		    	}
		  	}
			return xmlHttp;
		}

		function addFamilyTextBox(){
			document.getElementById("familydiv").innerHTML = "<input type='text' name='family' style='width:250px;'/>";
		}
			
		function addUpperTaxonTextBox(){
			document.getElementById("uppertaxondiv").innerHTML = "<input type='text' name='uppertaxonomy' style='width:250px;' />";
		}
	</script>
</head>
<body onload="">
<?php
$displayLeftMenu = (isset($taxa_admin_taxonomyeditorMenu)?$taxa_admin_taxonomyeditorMenu:"true");
include($serverRoot.'/header.php');
if(isset($taxa_admin_taxonomyeditorCrumbs)){
	echo "<div class='navpath'>";
	echo "<a href='../index.php'>Home</a> &gt; ";
	echo $taxa_admin_taxonomyeditorCrumbs;
	echo " <b>Taxonomy Editor</b>";
	echo "</div>";
}
?>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($editable && $target){
		?>
		<div class="taxondisplaydiv">
			<div style="float:right;cursor:pointer;" onclick="javascript:toogle('editfield');" title="Toogle Taxon Editing Functions">
				<img style='border:0px;' src='../../images/edit.png'/>
			</div>
			<div style="float:right;" title="Go to taxonomy display">
				<a href="taxonomydisplay.php?target=<?php echo $taxonEditorObj->getUnitName1();?>&showsynonyms=1">
					<img style='border:0px;width:15px;' src='../../images/toparent.jpg'/>
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
			<div>
				<form id="taxoneditform" name="taxoneditform" action="taxonomyeditor.php" method='GET'>
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
								<input type="text" id="unitname1" name="unitname1" style="border-style:inset;" value="<?php echo $taxonEditorObj->getUnitName1(); ?>" />
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
								<input type="text" id="unitname2" name="unitname2" style="border-style:inset;" value="<?php echo $taxonEditorObj->getUnitName2(); ?>" />
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
								<input type="text" id="unitind3" name="unitind3" style="width:30px;border-style:inset;" value="<?php echo $taxonEditorObj->getUnitInd3(); ?>" />
							</div>
							<div>
								<input type="text" id="unitname3" name="unitname3" style="border-style:inset;" value="<?php echo $taxonEditorObj->getUnitName3(); ?>" />
							</div>
						</div>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:110px;font-weight:bold;">Author: </div>
						<div class="editfield">
							<?php echo $taxonEditorObj->getAuthor();?>
						</div>
						<div class="editfield" style="display:none;">
							<input type="text" id="author" name="author" style="border-style:inset;" value="<?php echo $taxonEditorObj->getAuthor(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:110px;font-weight:bold;">Kingdom: </div>
						<div class="editfield">
							<?php 
								switch($taxonEditorObj->getKingdomId()){
									case 3:
										echo "Plantae";
										break;
									case 4:
										echo "Fungi";
										break;
									case 5:
										echo "Animalia";
										break;
								} 
							?>
						</div>
						<div class="editfield" style="display:none;">
							<select id="kingdomid" name="kingdomid">
								<option value="3" <?php if($taxonEditorObj->getKingdomId()==3) echo "SELECTED"; ?>>Plantae</option>
								<option value="4" <?php if($taxonEditorObj->getKingdomId()==4) echo "SELECTED"; ?>>Fungi</option>
								<option value="5" <?php if($taxonEditorObj->getKingdomId()==5) echo "SELECTED"; ?>>Animalia</option>
							</select>
						</div>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:110px;font-weight:bold;">Rank Name: </div>
						<div class="editfield">
							<?php echo $taxonEditorObj->getRankName();?>
						</div>
						<div class="editfield" style="display:none;">
							<select id="rankid" name="rankid">
								<?php 
									$taxonEditorObj->echoRankIdSelect();
								?>
							</select>
						</div>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:110px;font-weight:bold;">Notes: </div>
						<div class="editfield">
							<?php echo $taxonEditorObj->getNotes();?>
						</div>
						<div class="editfield" style="display:none;width:275px;">
							<input type="text" id="notes" name="notes" value="<?php echo $taxonEditorObj->getNotes(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:110px;font-weight:bold;">Source: </div>
						<div class="editfield">
							<?php echo $taxonEditorObj->getSource();?>
						</div>
						<div class="editfield" style="display:none;">
							<input type="text" id="source" name="source" style="width:250px;" value="<?php echo $taxonEditorObj->getSource(); ?>" />
						</div>
					</div>
					<div style="clear:both;">
						<div style="float:left;width:110px;font-weight:bold;">Locality Security: </div>
						<div class="editfield">
							<?php 
								switch($taxonEditorObj->getSecurityStatus()){
									case 1:
										echo "show all locality data";
										break;
									case 2:
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
						</div>
					</div>
					<div class="editfield" style="display:none;">
						<input type="hidden" name="target" value="<?php echo $taxonEditorObj->getTid(); ?>" />
						<input type="hidden" name="taxauthid" value="<?php echo $taxonEditorObj->getTaxAuthId();?>">
						<input type='submit' id='taxoneditsubmit' name='taxonedits' value='Submit Edits' />
					</div>
				</form>
			</div>
			<div class="fieldset" style="width:420px;">
				<div class="legend">Taxonomic Placement</div>
				<div style="padding:7px 7px 0px 7px;background-color:silver;margin:-15px -10px 5px 0px;float:right;">
					<form id="taxauthidform" name="taxauthidform" action="taxonomyeditor.php" method="GET">
						<input type="hidden" name="target" value="<?php echo $taxonEditorObj->getTid(); ?>" />
						<select name="taxauthid" onchange="document.getElementById('taxauthidform').submit()">
							<option value="0">Default Taxonomy</option>
							<option value="0">----------------------------</option>
							<?php 
								$taxonEditorObj->echoTaxonomicThesaurusIds();
							?>
						</select>
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
					<form id="taxstatuslinksform" name="taxstatuslinksform" action="taxonomyeditor.php" method='GET'>
						<div>
							<div style="float:right;cursor:pointer;" onclick="toogle('tsedit');">
								<img style='border:0px;' src='../../images/edit.png'/>
							</div>
							<div style="float:left;width:110px;font-weight:bold;">Upper Taxonomy: </div>
							<div class="tsedit" style="">
								<?php echo $taxonEditorObj->getUpperTaxon(); ?>
							</div>
							<div id="uppertaxondiv" class="tsedit" style="display:none;">
								<select name="uppertaxonomy">
									<option value="">Select a Taxon Group</option>
									<option value="">----------------------------</option>
									<?php $taxonEditorObj->echoUpperTaxonomySelect(); ?>
								</select>
								<img src="../../images/add.png" onclick="addUpperTaxonTextBox();" title="Add an Upper Taxon not on list" />
							</div>
						</div>
						<div style="clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">Family: </div>
							<div class="tsedit" style="">
								<?php echo $taxonEditorObj->getFamily();?>&nbsp;
							</div>
							<div id="familydiv" class="tsedit" style="display:none;">
								<select name="family">
									<option value="">Select a Family</option>
									<option value="">----------------------------</option>
									<?php $taxonEditorObj->echoFamilySelect(); ?>
								</select>
								<img src="../../images/add.png" onclick="addFamilyTextBox();" title="Add a family not on list" />
							</div>
						</div>
						<div style="clear:both;">
							<div style="float:left;width:110px;font-weight:bold;">Parent Taxon: </div>
							<div class="tsedit">
								<?php echo $taxonEditorObj->getParentName();?>
							</div>
							<div class="tsedit" style="display:none;">
								<select id="parenttid" name="parenttid" style="width:275px;">
									<?php 
										$taxonEditorObj->echoParentTidSelect();
									?>
								</select>
							</div>
						</div>
						<div class="tsedit" style="display:none;clear:both;">
							<input type="hidden" name="target" value="<?php echo $taxonEditorObj->getTid(); ?>" />
							<input type="hidden" name="taxauthid" value="<?php echo $taxonEditorObj->getTaxAuthId();?>">
							<input type="hidden" name="tidaccepted" value="<?php echo ($taxonEditorObj->getIsAccepted()==1?$taxonEditorObj->getTid():array_shift(array_keys($taxonEditorObj->getAcceptedArr()))); ?>" />
							<input type='submit' id='taxstatuseditsubmit' name='taxstatuseditsubmit' value='Submit Upper/Family Edits' />
						</div>
					</form>
				</div>
				<div id="AcceptedDiv" style="margin-top:30px;clear:both;">
					<?php 
					if($taxonEditorObj->getIsAccepted() <> 1){	//Is Not Accepted
						$acceptedArr = $taxonEditorObj->getAcceptedArr();
						echo "<h3>Accepted Taxon:</h3>\n";
						echo "<div style=\"float:right;cursor:pointer;\" onclick=\"toogle('acceptedits');\">";
						echo "<img style='border:0px;width:15px;' src='../../images/edit.png'/>";
						echo "</div>\n";
						if($acceptedArr){
							echo "<ul>\n";
							foreach($acceptedArr as $tidAccepted => $linkedTaxonArr){
								echo "<li id='acclink-".$tidAccepted."'>\n";
								echo "<a href='taxonomyeditor.php?target=".$tidAccepted."&taxauthid=".$taxonEditorObj->getTaxAuthId()."'><i>".$linkedTaxonArr["sciname"]."</i></a> ".$linkedTaxonArr["author"]."\n";
								if(count($acceptedArr)>1){
									echo "<span class='acceptedits' style=\"cursor:pointer;display:none;\" onclick=\"deleteAcceptedLink('".$tidAccepted."')\">";
									echo "<img style='border:0px;width:12px;' src='../../images/del.gif' />";
									echo "</span>\n";
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
							<div class="fieldset" style="width:380px;margin:20px;">
								<div class="legend">Add an Accepted Link</div>
								<form id="accepteditsform" name="accepteditsform" action="taxonomyeditor.php" method="get">
									<div>
										<select id="acceptaddselect" name="tidaccepted">
											<option value="0">Select an Accepted Taxon</option>
											<option value="0">-------------------------------</option>
											<?php 
												$taxonEditorObj->echoAcceptedTaxaSelect();
											?>
										</select>
									</div>
									<div>
										<input type="checkbox" name="deleteother" checked /> Delete Other Accepted Links
									</div>
									<div>
										<input type="hidden" name="target" value="<?php echo $taxonEditorObj->getTid();?>" />
										<input type="hidden" name="taxauthid" value="<?php echo $taxonEditorObj->getTaxAuthId();?>">
										<input type='submit' id='addacceptedsubmit' name='addacceptedlink' value='Add Link' />
									</div>
								</form>
							</div>
							<?php if($acceptedArr && count($acceptedArr)==1){ ?>
							<div class="fieldset" style="width:350px;margin:20px;">
								<div class="legend">Change to Accepted</div>
								<form id="changetoacceptedform" name="changetoacceptedform" action="taxonomyeditor.php" method="get">
									<div>
										<input type="checkbox" name="switchacceptance" checked /> Switch Acceptance with Currently Accepted Name
									</div>
									<div>
										<input type="hidden" name="target" value="<?php echo $taxonEditorObj->getTid();?>" />
										<input type="hidden" name="taxauthid" value="<?php echo $taxonEditorObj->getTaxAuthId();?>">
										<input type="hidden" name="tidaccepted" value="<?php echo array_shift(array_keys($acceptedArr));?>" />
										<input type='submit' id='changetoacceptedsubmit' name='changetoaccepted' value='Change Status to Accepted' />
									</div>
								</form>
							</div>
							<?php } ?>
						</div>
					<?php
					}
					?>
				</div>
				<div id="SynonymDiv">
					<?php 
					if($taxonEditorObj->getIsAccepted() <> 0){	//Is Accepted
					?>
						<h3>Synonyms:</h3>
						<div style="float:right;cursor:pointer;" onclick="toogleById('tonotaccepteddiv');">
							<img style='border:0px;width:15px;' src='../../images/edit.png'/>
						</div>
						<ul>
						<?php 
						$synonymArr = $taxonEditorObj->getSynonyms();
						if($synonymArr){
							foreach($synonymArr as $tidSyn => $synArr){
								echo "<li> ";
								echo "<a href='taxonomyeditor.php?target=".$tidSyn."&taxauthid=".$taxonEditorObj->getTaxAuthId()."'><i>".$synArr["sciname"]."</i></a> ".$synArr["author"]." ";
								echo "<span style=\"cursor:pointer;\" onclick=\"toogleById('syn-".$tidSyn."');\">";
								echo "<img style='border:0px;width:10px;' src='../../images/edit.png'/>";
								echo "</span>";
								if($synArr["notes"] || $synArr["unacceptabilityreason"]){
									echo "<div style='margin-left:10px;'>";
									if($synArr["unacceptabilityreason"]){
										echo "<u>Unacceptability Reason:</u> ".$synArr["unacceptabilityreason"];
									}
									if($synArr["notes"]){
										echo "<u>Notes:</u> ".$synArr["notes"];
									}
									echo "</div>";
								}
								echo "</li>";
								?>
								<div id="syn-<?php echo $tidSyn;?>" class="fieldset" style="display:none;">
									<form id="synform-<?php echo $tidSyn;?>" name="synform-<?php echo $tidSyn;?>" action="taxonomyeditor.php" method="get">
										<div class="legend">Synonym Link Editor</div>
										<div style="clear:both;">
											<div style="float:left;width:150px;font-weight:bold;">Unacceptability Reason:</div>
											<div>
												<input type='text' id='unacceptabilityreason' name='unacceptabilityreason' style="width:240px;" value='<?php echo $synArr["unacceptabilityreason"]; ?>' />
											</div>
										</div>
										<div style="clear:both;">
											<div style="float:left;width:150px;font-weight:bold;">Notes:</div>
											<div>
												<input type='text' id='notes' name='notes' style="width:240px;" value='<?php echo $synArr["notes"]; ?>' />
											</div>
										</div>
										<div style="clear:both;">
											<div style="float:left;width:150px;font-weight:bold;">Sort Sequence: </div>
											<div>
												<input type='text' id='sortsequence' name='sortsequence' style="width:30px;" value='<?php echo $synArr["sortsequence"]; ?>' />
											</div>
										</div>
										<div style="clear:both;">
											<div>
												<input type="hidden" name="target" value="<?php echo $taxonEditorObj->getTid(); ?>" />
												<input type="hidden" name="tid" value="<?php echo $tidSyn; ?>" />
												<input type="hidden" name="taxauthid" value="<?php echo $taxonEditorObj->getTaxAuthId();?>">
												<input type='submit' id='syneditsubmit' name='synonymedits' value='Submit Changes' />
											</div>
										</div>
									</form>
								</div>
								
					<?php 	} ?>
						</ul>
					<?php
						}
						else{
							echo "<div style='margin:20px;'>No Synonyms Linked to this Taxon</div>";
						}
						?>
						<div id="tonotaccepteddiv" class="fieldset" style="width:350px;display:none;">
							<div class="legend">Change to Not Accepted</div>
							<form id="changetoacceptedform" name="changetoacceptedform" action="taxonomyeditor.php" method="get">
								<div style="margin:5px;">
									<select name="tidaccepted">
										<option value="">Select Accepted Taxon</option>
										<option value="">----------------------------</option>
										<?php $taxonEditorObj->echoAcceptedTaxaSelect(); ?>
									</select>
								</div>
								<div style="margin:5px;">
									<input type="hidden" name="target" value="<?php echo $taxonEditorObj->getTid();?>" />
									<input type="hidden" name="taxauthid" value="<?php echo $taxonEditorObj->getTaxAuthId();?>">
									<input type='submit' id='changetonotacceptedsubmit' name='changetonotaccepted' value='Change Status to Not Accepted' />
								</div>
								<div style="margin:5px;">
									* Synonyms will be transferred to Accepted Taxon
								</div>
							</form>
						</div>
						<?php 
					}
					?>
				</div>
			</div>
			<div class="fieldset" style="width:420px;">
				<div class="legend">Quick Query Taxonomic Hierarchy</div>
				<div style="float:right;" title="Rebuild Hierarchy">
					<form name="updatehierarchyform" action="taxonomyeditor.php" method="get">
						<input type="hidden" name="target" value="<?php echo $taxonEditorObj->getTid(); ?>"/>
						<input type="hidden" name="taxauthid" value="<?php echo $taxonEditorObj->getTaxAuthId();?>">
						<input type="image" name="updatehierarchy" value="1" src="../../images/undo.jpg" style="width:20px;"/>
					</form>
				</div>
				<?php 
					$taxonEditorObj->echoHierarchy();
				?>
			</div>
			
		</div>

		<?php 
		}
		else{
			if(!$target){
				echo "<div>Target Taxon missing</div>";
			}
			else{
				echo "<div>You must be logged in and authorized to view internal taxonomy. Please login.</div>";
			}
		}
		include($serverRoot.'/footer.php');
		?>
	</div>
	<script type="text/javascript">
		function acceptanceChanged(){
			if(document.getElementById("isaccepted").checked == true){
				document.getElementById("acceptancediv").style.display = "none";
			}
			else{
				document.getElementById("acceptancediv").style.display = "block";
			}
		}
		
	</script>

</body>
</html>
