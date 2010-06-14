<?php
/*
 * Created on 24 Aug 2009
 * E.E. Gilbert
 */

 error_reporting(E_ALL);
 //set_include_path( get_include_path() . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT']."" );
 include_once("../../util/dbconnection.php");
 include_once("../../util/symbini.php");
  
 $target = array_key_exists("target",$_REQUEST)?$_REQUEST["target"]:"";
 $taxonEditorObj = new TaxonEditor($target);
 if(array_key_exists("taxauthid",$_REQUEST)){
 	$taxonEditorObj->setTaxAuthId($_REQUEST["taxauthid"]);
 }
 
 $editable = false;
 if(isset($userRights) && $userRights && ($isAdmin || in_array("TaxonAdmin",$userRights))){
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
	<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1"/>
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
include($serverRoot."/util/header.php");
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
					echo "<a href='../admin/tpeditor.php?taxon=".$taxonEditorObj->getTid()."' style='color:inherit;text-decoration:none;'>";
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
								<option value="1" <?php if($taxonEditorObj->getSecurityStatus()==1) echo "SELECTED"; ?>>show all locality data</option>
								<option value="2" <?php if($taxonEditorObj->getSecurityStatus()==2) echo "SELECTED"; ?>>hide locality data</option>
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
		include($serverRoot."/util/footer.php");
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

<?php 
class TaxonEditor{

	private $conn;
	private $taxAuthId = 1;
	private $tid = 0;
	private $upperTaxon;
	private $family;
	private $sciName;
	private $kingdomId;
	private $rankId = 0;
	private $rankName;
	private $unitInd1;
	private $unitName1;
	private $unitInd2;
	private $unitName2;
	private $unitInd3;
	private $unitName3;
	private $author;
	private $parentTid = 0;
	private $parentName;
	private $source;
	private $notes;
	private $hierarchy;
	private $securityStatus;
	private $isAccepted = -1;			// 1 = accepted, 0 = not accepted, -1 = not assigned, -2 in conflict
	private $acceptedArr = Array();
	private $synonymArr = Array();

	function __construct($target) {
		$this->conn = MySQLiConnectionFactory::getCon("readonly");
		if(is_numeric($target)){
			$this->tid = $target;
		}
		else{
			$sql = "SELECT T.tid FROM taxa t WHERE t.sciname = '".$target."'";
			$rs = $this->conn->query($sql);
			if($row = $rs->fetch_object()){
				$this->tid = $row->tid;
			}
			$rs->close();
		}
	}
	
	function __destruct(){
		if($this->conn) $this->conn->close();
	}
	
	public function setTaxon(){
		
		$sqlTaxon = "SELECT t.tid, t.kingdomid, t.rankid, tu.rankname, t.sciname, t.unitind1, t.unitname1, ".
			"t.unitind2, t.unitname2, t.unitind3, t.unitname3, t.author, ts.parenttid, t.source, t.notes, ts.hierarchystr, ".
			"t.securitystatus, t.initialtimestamp, ts.tidaccepted, ts.unacceptabilityreason, ".
			"ts.uppertaxonomy, ts.family, t2.sciname AS accsciname, t2.author AS accauthor, t2.notes AS accnotes, ts.sortsequence ".
			"FROM ((taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid) INNER JOIN taxa t2 ON ts.tidaccepted = t2.tid) ".
			"LEFT JOIN taxonunits tu ON t.rankid = tu.rankid AND t.kingdomid = tu.kingdomid ".
			"WHERE ts.taxauthid = ".$this->taxAuthId." AND t.tid = ".$this->tid;
		//echo $sqlTaxon;
		$rs = $this->conn->query($sqlTaxon); 
		if($row = $rs->fetch_object()){
			$this->upperTaxon = $row->uppertaxonomy;
			$this->family = $row->family;
			$this->sciName = $row->sciname;
			$this->kingdomId = $row->kingdomid;
			$this->rankId = $row->rankid;
			$this->rankName = $row->rankname;
			$this->unitInd1 = $row->unitind1;
			$this->unitName1 = $row->unitname1;
			$this->unitInd2 = $row->unitind2;
			$this->unitName2 = $row->unitname2;
			$this->unitInd3 = $row->unitind3;
			$this->unitName3 = $row->unitname3;
			$this->author = $row->author;
			$this->parentTid = $row->parenttid;
			$this->source = $row->source;
			$this->notes = $row->notes;
			$this->hierarchy = $row->hierarchystr;
			$this->securityStatus = $row->securitystatus;

			//Deal with TaxaStatus table stuff
			do{
				$tidAccepted = $row->tidaccepted;
				if($this->tid == $tidAccepted){
					if($this->isAccepted == -1 || $this->isAccepted == 1){
						$this->isAccepted = 1;
					}
					else{
						$this->isAccepted = -2;
					}
				}
				else{
					if($this->isAccepted == -1 || $this->isAccepted == 0){
						$this->isAccepted = 0;
					}
					else{
						$this->isAccepted = -2;
					}
					$this->acceptedArr[$tidAccepted]["unacceptabilityreason"] = $row->unacceptabilityreason;
					$this->acceptedArr[$tidAccepted]["sciname"] = $row->accsciname;
					$this->acceptedArr[$tidAccepted]["author"] = $row->accauthor;
					$this->acceptedArr[$tidAccepted]["usagenotes"] = $row->accnotes;
					$this->acceptedArr[$tidAccepted]["sortsequence"] = $row->sortsequence;
				}
			}while($row = $rs->fetch_object());
		}
		if($this->isAccepted == 1) $this->setSynonyms();
		if($this->parentTid) $this->setParentName();
		$rs->close();
	}
	
	private function setSynonyms(){
		$sql = "SELECT t.tid, t.sciname, t.author, ts.unacceptabilityreason, ts.notes, ts.sortsequence ".
			"FROM taxstatus ts INNER JOIN taxa t ON ts.tid = t.tid ".
			"WHERE (ts.taxauthid = ".$this->taxAuthId.") AND (ts.tid <> ts.tidaccepted) AND (ts.tidaccepted = ".$this->tid.") ".
			"ORDER BY ts.sortsequence,t.sciname";
		//echo $sql."<br>";
		$result = $this->conn->query($sql);
		while($row = $result->fetch_object()){
			$this->synonymArr[$row->tid]["sciname"] = $row->sciname;
			$this->synonymArr[$row->tid]["author"] = $row->author;
			$this->synonymArr[$row->tid]["unacceptabilityreason"] = $row->unacceptabilityreason;
			$this->synonymArr[$row->tid]["notes"] = $row->notes;
			$this->synonymArr[$row->tid]["sortsequence"] = $row->sortsequence;
		}
		$result->close();
	}

	private function setParentName(){
		$sql = "SELECT t.sciname, t.author ".
			"FROM taxa t ".
			"WHERE (t.tid = ".$this->parentTid.")";
		//echo $sql."<br>";
		$result = $this->conn->query($sql);
		if($row = $result->fetch_object()){
			$this->parentName = "<i>".$row->sciname."</i> ".$row->author;
		}
		$result->close();
	}
	
	//Misc methods for retrieving field data
	public function echoTaxonomicThesaurusIds(){
		//For now, just return the default taxonomy (taxauthid = 1)
		$sql = "SELECT ta.taxauthid, ta.name FROM taxauthority ta INNER JOIN taxstatus ts ON ta.taxauthid = ts.taxauthid ".
			"WHERE ta.isactive = 1 AND ts.tid = ".$this->tid." AND ta.taxauthid = 1 ORDER BY ta.taxauthid ";
		$rs = $this->conn->query($sql); 
		while($row = $rs->fetch_object()){
			echo "<option value=".$row->taxauthid." ".($this->taxAuthId==$row->taxauthid?"SELECTED":"").">".$row->name."</option>\n";
		}
		$rs->close();
	}

	public function echoUpperTaxonomySelect(){
		$sql = "SELECT DISTINCT ts.uppertaxonomy FROM taxstatus ts ".
			"WHERE ts.taxauthid = ".$this->taxAuthId." AND ts.uppertaxonomy IS NOT NULL ORDER BY ts.uppertaxonomy ";
		$rs = $this->conn->query($sql); 
		while($row = $rs->fetch_object()){
			echo "<option ".($this->upperTaxon==$row->uppertaxonomy?"SELECTED":"").">".$row->uppertaxonomy."</option>\n";
		}
		$rs->close();
	}  

	public function echoFamilySelect(){
		$sql = "SELECT t.unitname1 FROM taxa t ".
			"WHERE t.rankid = 140 ORDER BY t.unitname1 ";
		$rs = $this->conn->query($sql); 
		while($row = $rs->fetch_object()){
			echo "<option ".($this->family==$row->unitname1?"SELECTED":"").">".$row->unitname1."</option>\n";
		}
		$rs->close();
	}

	public function echoParentTidSelect(){
		$sql = "SELECT t.tid, t.sciname ".
			"FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
			"WHERE (ts.taxauthid = ".$this->taxAuthId.") AND (ts.tid = ts.tidaccepted) ";
		if($this->rankId < 220){
			$sql .= "AND (t.rankid < ".$this->rankId.") ";
		}
		elseif($this->rankId == 220){
			$sql .= "AND (t.rankid = 180) AND (t.unitname1 = '".$this->unitName1."') ";
		}
		elseif($this->rankId > 220 && $this->family){
			$sql .= "AND (t.rankid = 220) AND (t.unitname1 = '".$this->unitName1."') ";
		}
		$sql .= "ORDER BY t.sciname ";
		//echo $sql;
		$rs = $this->conn->query($sql); 
		while($row = $rs->fetch_object()){
			echo "<option value='".$row->tid."' ".($this->parentTid==$row->tid?"SELECTED":"").">".$row->sciname."</option>\n";
		}
		$rs->close();
	}  

	public function echoRankIdSelect(){
		$sql = "SELECT tu.rankid, tu.rankname FROM taxonunits tu ".
			"WHERE tu.kingdomid = ".$this->kingdomId." ORDER BY tu.rankid ";
		$rs = $this->conn->query($sql); 
		echo "<option value='0'>Select Taxon Rank</option>\n";
		while($row = $rs->fetch_object()){
			echo "<option value='".$row->rankid."' ".($this->rankId==$row->rankid?"SELECTED":"").">".$row->rankname."</option>\n";
		}
		$rs->close();
	}  

	public function echoAcceptedTaxaSelect(){
		$sql = "SELECT t.tid, t.sciname FROM taxa t INNER JOIN taxstatus ts ON t.tid = ts.tid ".
			"WHERE ts.taxauthid = ".$this->taxAuthId." AND ts.tid = ts.tidaccepted ";
		if($this->family){
			$sql .= "AND ts.family = '".$this->family."' ";
		}
		if($this->rankId < 220){
			$sql .= "AND t.rankid < 220 ";
		}
		else{
			$sql .= "AND t.rankid >= 220 ";
		}
		$sql .= "ORDER BY t.sciname ";
		//echo "<div>".$sql."</div>";
		$rs = $this->conn->query($sql); 
		while($row = $rs->fetch_object()){
			echo "<option value='".$row->tid."'>".$row->sciname."</option>\n";
		}
		$rs->close();
	}  

	public function echoHierarchy(){
		if($this->hierarchy){
			$sql = "SELECT t.tid, t.sciname FROM taxa t ".
				"WHERE t.tid IN(".$this->hierarchy.") ORDER BY t.rankid, t.sciname ";
			$rs = $this->conn->query($sql); 
			$indent = 0;
			while($row = $rs->fetch_object()){
				echo "<div style='margin-left:".$indent.";'><a href='taxonomyeditor.php?target=".$row->tid."'>".$row->sciname."</a></div>\n";
				$indent += 10;
			}
			$rs->close();
		}
		else{
			echo "<div style='margin:10px;'>Empty</div>";
		}
	}
	
	//Edit Functions
	public function submitTaxonEdits($taxonEditArr){
		$tid = $taxonEditArr["tid"];
		unset($taxonEditArr["tid"]);
		
		//Update taxa record
		$sql = "UPDATE taxa SET ";
		foreach($taxonEditArr as $key => $value){
			$sql .= $key." = \"".trim($value)."\",";
		}
		$sql .= "sciname = \"".($taxonEditArr["unitind1"]?$taxonEditArr["unitind1"]." ":"").
			$taxonEditArr["unitname1"].($taxonEditArr["unitind2"]?" ".$taxonEditArr["unitind2"]:"").
			($taxonEditArr["unitname2"]?" ".$taxonEditArr["unitname2"]:"").
			($taxonEditArr["unitind3"]?" ".$taxonEditArr["unitind3"]:"").
			($taxonEditArr["unitname3"]?" ".$taxonEditArr["unitname3"]:"")."\"";
		$sql .= " WHERE tid = ".$tid;
		//echo $sql;
		$con = MySQLiConnectionFactory::getCon("write");
		$status = $con->query($sql);
		$con->close();
		
		return $status;
	}
	
	public function submitTaxstatusEdits($tsArr){
		//See if parent changed
		$currentParentTid = 0;
		$sqlParent = "SELECT ts.parenttid FROM taxstatus ts WHERE ts.tid = ".$tsArr["tid"];
		$rs = $this->conn->query($sqlParent);
		if($row = $rs->fetch_object()){
			$currentParentTid = $row->parenttid;
		}
		$rs->close();
		
		$sql = "UPDATE taxstatus ".
			"SET family = '".trim($tsArr["family"])."',uppertaxonomy = '".trim($tsArr["uppertaxonomy"])."', parenttid = ".$tsArr["parenttid"]." ".
			"WHERE taxauthid = ".$this->taxAuthId." AND tid = ".$tsArr["tid"]." AND tidaccepted = ".$tsArr["tidaccepted"];
		$con = MySQLiConnectionFactory::getCon("write");
		$status = $con->query($sql);
		$con->close();
		
		if($currentParentTid != $tsArr["parenttid"]){
			$this->rebuildHierarchy($tsArr["tid"]);
		}
		
		return $status;
	}
	
	public function submitSynEdits($synEditArr){
		$tid = $synEditArr["tid"];
		unset($synEditArr["tid"]);
		$tidAccepted = $synEditArr["tidaccepted"];
		unset($synEditArr["tidaccepted"]);
		$sql = "UPDATE taxstatus SET ";
		$sqlSet = "";
		foreach($synEditArr as $key => $value){
			$sqlSet .= ",".$key." = '".trim($value)."'";
		}
		$sql .= substr($sqlSet,1);
		$sql .= " WHERE taxauthid = ".$this->taxAuthId." AND tid = ".$tid." AND tidaccepted = ".$tidAccepted;
		//echo $sql;
		$con = MySQLiConnectionFactory::getCon("write");
		$status = $con->query($sql);
		$con->close();
		return $status;
	}
	
	public function submitAddAcceptedLink($tid, $tidAcc, $deleteOther = true){
		$con = MySQLiConnectionFactory::getCon("write");
		
		$upperTax = "";$family = "";$parentTid = 0;$hierarchyStr = "";
		$sqlFam = "SELECT ts.uppertaxonomy, ts.family, ts.parenttid, ts.hierarchystr ".
			"FROM taxstatus ts WHERE ts.tid = $tid AND ts.taxauthid = ".$this->taxAuthId;
		$rs = $con->query($sqlFam);
		if($row = $rs->fetch_object()){
			$upperTax = $row->uppertaxonomy;
			$family = $row->family;
			$parentTid = $row->parenttid;
			$hierarchyStr = $row->hierarchystr;
		}
		$rs->close();
		
		if($deleteOther){
			$sqlDel = "DELETE FROM taxstatus WHERE tid = $tid AND taxauthid = ".$this->taxAuthId;
			$con->query($sqlDel);
		}
		$sql = "INSERT INTO taxstatus (tid,tidaccepted,taxauthid,uppertaxonomy,family,parenttid,hierarchystr) ".
			"VALUES ($tid, $tidAcc, $this->taxAuthId,".($upperTax?"\"".$upperTax."\"":"NULL").",".
			($family?"\"".$family."\"":"NULL").",".$parentTid.",'".$hierarchyStr."') ";
		//echo $sql;
		$status = $con->query($sql);
		$con->close();
		return $status;
	}
	
	public function submitChangeToAccepted($tid,$tidAccepted,$switchAccpetance = true){
		$con = MySQLiConnectionFactory::getCon("write");
		
		$sql = "UPDATE taxstatus SET tidaccepted = $tid WHERE tid = $tid AND taxauthid = $this->taxAuthId";
		$status = $con->query($sql);

		if($switchAccpetance){
			$sqlSwitch = "UPDATE taxstatus SET tidaccepted = $tid WHERE tidaccepted = $tidAccepted AND taxauthid = $this->taxAuthId";
			$status = $con->query($sqlSwitch);
			
			$this->updateDependentData($tidAccepted,$tid);
		}
		$con->close();
		return $status;
	}
	
	public function submitChangeToNotAccepted($tid,$tidAccepted){
		$con = MySQLiConnectionFactory::getCon("write");
		
		//Change subject taxon to Not Accepted
		$sql = "UPDATE taxstatus SET tidaccepted = $tidAccepted WHERE tid = $tid AND taxauthid = $this->taxAuthId";
		$status = $con->query($sql);

		//Switch synonyms of subject to Accepted Taxon 
		$sqlSyns = "UPDATE taxstatus SET tidaccepted = $tidAccepted WHERE tidaccepted = $tid AND taxauthid = $this->taxAuthId";
		$status = $con->query($sqlSyns);
		
		$con->close();
		
		$this->updateDependentData($tid,$tidAccepted);
		
		return $status;
	}
	
	public function rebuildHierarchy($tid){
		$parentArr = Array();
		$parCnt = 0;
		$targetTid = $tid;
		do{
			$sqlParents = "SELECT IFNULL(ts.parenttid,0) AS parenttid FROM taxstatus ts WHERE ts.tid = ".$targetTid;
			$resultParent = $this->conn->query($sqlParents);
			if($rowParent = $resultParent->fetch_object()){
				$parentTid = $rowParent->parenttid;
				if($parentTid) {
					$parentArr[$parentTid] = $parentTid;
				}
			}
			else{
				break;
			}
			$resultParent->close();
			$parCnt++;
			if($targetTid == $parentTid) break;
			$targetTid = $parentTid;
		}while($targetTid && $parCnt < 16);
		
		//Add hierarchy string to taxa table
		$hierarchyStr = implode(",",array_reverse($parentArr));
		if($parentArr){
			$con = MySQLiConnectionFactory::getCon("write");
			$sqlInsert = "UPDATE taxstatus ts SET ts.hierarchystr = '".$hierarchyStr."' WHERE ts.tid = ".$tid;
			$con->query($sqlInsert);
			$con->close();
		}
	}

	public function updateDependentData($tid, $tidNew){
		//method to update descr, vernaculars,
		$con = MySQLiConnectionFactory::getCon("write");

		$con->query("UPDATE fmdescr SET tid = ".$tidNew." WHERE tid = ".$tid);
		$con->query("DELETE FROM fmdescr WHERE tid = ".$tid);
		
		$sqlVerns = "UPDATE taxavernaculars SET tid = ".$tidNew." WHERE tid = ".$tid;
		$con->query($sqlVerns);
		
		$sqlTest = "SELECT tid FROM taxadescriptions WHERE tid = ".$tidNew;
		$rsTest = $con->query($sqlTest);
		if($rsTest->num_rows == 0){
			$sqltd = "UPDATE taxadescriptions SET tid = ".$tidNew." WHERE tid = ".$tid;
			$con->query($sqltd);
		}
		
		$sqltl = "UPDATE taxalinks SET tid = ".$tidNew." WHERE tid = ".$tid;
		$con->query($sqltl);
		
		$con->close();
		
	}
	
	//Regular getter functions for this class
	public function getTargetName(){
		return $this->targetName;
	}

	public function getTid(){
		return $this->tid;
	}
	
	public function setTaxAuthId($taid){
		if($taid){
			$this->taxAuthId = $taid;
		}
	}
	
	public function getTaxAuthId(){
		return $this->taxAuthId;
	}

	public function getUpperTaxon(){
		return $this->upperTaxon;
	}

	public function getFamily(){
		return $this->family;
	}

	public function getSciName(){
		return $this->sciName;
	}

	public function getKingdomId(){
		return $this->kingdomId;
	}

	public function getRankId(){
		return $this->rankId;
	}
	
	public function getRankName(){
		return $this->rankName;
	}

	public function getUnitInd1(){
		return $this->unitInd1;
	}

	public function getUnitName1(){
		return $this->unitName1;
	}

	public function getUnitInd2(){
		return $this->unitInd2;
	}

	public function getUnitName2(){
		return $this->unitName2;
	}

	public function getUnitInd3(){
		return $this->unitInd3;
	}

	public function getUnitName3(){
		return $this->unitName3;
	}

	public function getAuthor(){
		return $this->author;
	}

	public function getParentTid(){
		return $this->parentTid;
	}

	public function getParentName(){
		return $this->parentName;
	}

	public function getSource(){
		return $this->source;
	}

	public function getNotes(){
		return $this->notes;
	}

	public function getHierarchy(){
		return $this->hierarchy;
	}

	public function getSecurityStatus(){
		return $this->securityStatus;
	}

	public function getIsAccepted(){
		return $this->isAccepted;
	}

	public function getAcceptedArr(){
		return $this->acceptedArr;
	}
	
	public function getSynonyms(){
		return $this->synonymArr;
	}
}
?>