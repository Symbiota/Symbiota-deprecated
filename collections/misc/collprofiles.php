<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/CollectionProfileManager.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$showFamilyList = array_key_exists("sfl",$_REQUEST)?$_REQUEST["sfl"]:0;
$familyDist = array_key_exists('family',$_REQUEST)?$_REQUEST['family']:'';
$showGeographicList = array_key_exists("sgl",$_REQUEST)?$_REQUEST["sgl"]:0;
$countryDist = array_key_exists('country',$_REQUEST)?$_REQUEST['country']:'';
$stateDist = array_key_exists('state',$_REQUEST)?$_REQUEST['state']:'';
$newCollRec = array_key_exists("newcoll",$_REQUEST)?1:0;
$eMode = array_key_exists('emode',$_REQUEST)?$_REQUEST['emode']:0;
$statusStr = '';

$collManager = new CollectionProfileManager();
if($collId) $collManager->setCollectionId($collId);

$editCode = 0;		//0 = no permissions; 1 = CollEditor; 2 = CollAdmin; 3 = SuperAdmin 
if($symbUid){
	if($isAdmin){
		$editCode = 3;
	}
	else if($collId){
		if(array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])){
			$editCode = 2;
		}
		elseif(array_key_exists("CollEditor",$userRights) && in_array($collId,$userRights["CollEditor"])){
			$editCode = 1;
		}
	}
}

if($newCollRec && $editCode < 3){
	$newCollRec = 0;		//Only Admin should be able to add a new collection profile
}
if($editCode > 1){
	if($action == 'Submit Edits'){
		$collManager->submitCollEdits();
	}
}
if($editCode == 3){
	if($action == "Add New Profile"){
		$collId = $collManager->submitCollAdd();
		if(is_numeric($collId)){
			$collManager->setCollectionId($collId);
		}
		else{
			$statusStr = $collId;
			$newCollRec = 1;
		}
	}
}
$collData = Array();
if($collId) $collData = $collManager->getCollectionData();
?>
<html>
<head>
	<title><?php echo $defaultTitle." ".($collId?$collData["collectionname"]:"") ; ?> Collection Profiles</title>
	<link href="../../css/main.css" type="text/css" rel="stylesheet" />
	<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
	<meta name="keywords" content="Natural history collections,<?php echo ($collId?$collData["collectionname"]:""); ?>" />
	<script language=javascript>

		$(function() {
			var dialogArr = new Array("instcode","collcode","pedits","rights","rightsholder","accessrights","guid","colltype","management","icon","sourceurl","sort");
			var dialogStr = "";
			for(i=0;i<dialogArr.length;i++){
				dialogStr = dialogArr[i]+"info";
				$( "#"+dialogStr+"dialog" ).dialog({
					autoOpen: false,
					modal: true
				});

				$( "#"+dialogStr ).click(function() {
					$( "#"+this.id+"dialog" ).dialog( "open" );
				});
			}

		});
	
		function toggleById(target){
			if(target != null){
			  	var obj = document.getElementById(target);
				if(obj.style.display=="none" || obj.style.display==""){
					obj.style.display="block";
				}
			 	else {
			 		obj.style.display="none";
			 	}
			}
			return false;
		}

		function openMappingAid() {
		    mapWindow=open("../../tools/mappointaid.php?formname=colleditform&latname=latitudedecimal&longname=longitudedecimal","mappointaid","resizable=0,width=800,height=700,left=20,top=20");
		    if (mapWindow.opener == null) mapWindow.opener = self;
		}

		function verifyCollEditForm(f){
			if(f.institutioncode.value == ''){
				alert("Institution Code must have a value");
				return false;
			}
			else if(f.collectionname.value == ''){
				alert("Collection Name must have a value");
				return false;
			}
			else if(!isNumeric(f.latdec.value) || !isNumeric(f.lngdec.value)){
				alert("Latitdue and longitude values must be in the decimal format (numeric only)");
				return false;
			}
			else if(f.rights.value == ""){
				alert("Rights field (e.g. Creative Commons license) must have a selection");
				return false;
			}
			try{
				if(!isNumeric(f.sortseq.value)){
					alert("Sort sequence must be numeric only");
					return false;
				}
			}
			catch(ex){}
			return true;
		}

		function isNumeric(sText){
		   	var ValidChars = "0123456789-.";
		   	var IsNumber = true;
		   	var Char;
		 
		   	for(var i = 0; i < sText.length && IsNumber == true; i++){ 
			   Char = sText.charAt(i); 
				if(ValidChars.indexOf(Char) == -1){
					IsNumber = false;
					break;
		      	}
		   	}
			return IsNumber;
		}

		function dwcDoc(dcTag){
		    dwcWindow=open("http://rs.tdwg.org/dwc/terms/index.htm#"+dcTag,"dwcaid","width=1250,height=300,left=20,top=20,scrollbars=1");
		    if(dwcWindow.opener == null) dwcWindow.opener = self;
		    return false;
		}

	</script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_misc_collprofilesMenu)?$collections_misc_collprofilesMenu:true);
	include($serverRoot.'/header.php');
	if(isset($collections_misc_collprofilesCrumbs)){
		if($collections_misc_collprofilesCrumbs){
			echo "<div class='navpath'>";
			echo "<a href='../../index.php'>Home</a> &gt;&gt; ";
			echo $collections_misc_collprofilesCrumbs.' &gt;&gt; ';
			echo "<b>".($collData?$collData["collectionname"]:"Collection Profiles")."</b>";
			echo "</div>";
		}
	}
	?>

	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		if($statusStr){ 
			?>
			<hr />
			<div style="margin:20px;font-weight:bold;color:red;">
				<?php echo $statusStr; ?>
			</div>
			<hr />
			<?php 
		} 
		if($editCode > 1){
			if($action == 'UpdateStatistics'){
				echo '<h2>Updating statisitcs related to this collection...</h2>';
				echo '<ul>';
				$collManager->updateStatistics();
				echo '</ul><hr/>';
				$collData = $collManager->getCollectionData();
			}
		}
		if($editCode > 0 && $collId){
			?>
			<div style="float:right;margin:3px;cursor:pointer;" onclick="toggleById('controlpanel');" title="Toggle Manager's Control Panel">
				<img style='border:0px;' src='../../images/edit.png' />
			</div>
			<?php 
		}
		if($collId){
			echo '<h1>'.$collData['collectionname'].(array_key_exists('institutioncode',$collData)?' ('.$collData['institutioncode'].')':'').'</h1>';
		}
		if($editCode > 0 && $collId){
			?>
			<div id="controlpanel" style="clear:both;display:<?php echo ($eMode?'block':'none'); ?>;">
				<fieldset style="padding:15px;">
					<legend><b>Data Editor Control Panel</b></legend>
					<ul>
						<?php
						if(stripos($collData['colltype'],'observation') !== false){ 
							?>
							<li>
								<a href="../editor/observationsubmit.php?collid=<?php echo $collId; ?>">
									Submit an Image Voucher (observation supported by a photo)
								</a>
							</li>
							<?php
						}
						?>
						<li>
							<a href="../editor/occurrenceeditor.php?gotomode=1&collid=<?php echo $collId; ?>">
								Add New Occurrence Record
							</a>
						</li>
						<li>
							<a href="../editor/occurrenceeditor.php?collid=<?php echo $collId; ?>">
								Edit Existing Occurrence Records
							</a>
						</li>
						<?php
						if($collData['colltype'] == 'Preserved Specimens'){ 
							?>
							<li>
								<a href="../datasets/index.php?collid=<?php echo $collId; ?>">
									Print Labels
								</a>
							</li>
							<?php
						}
						?>
						<li>
							<a href="../georef/batchgeoreftool.php?collid=<?php echo $collId; ?>">
								Batch Georeference Specimens
							</a>
						</li>
						<?php
						if($collData['colltype'] == 'Preserved Specimens'){ 
							?>
							<li>
								<a href="../loans/index.php?collid=<?php echo $collId; ?>">
									Loan Management
								</a>
							</li>
							<?php
						}
						?>
						<li>
							<a href="../datasets/duplicatemanager.php?collid=<?php echo $collId; ?>">
								Duplicate Clustering
							</a>
						</li>
						<li>
							<a href="#" onclick="newWindow = window.open('collbackup.php?collid=<?php echo $collId; ?>','bucollid','scrollbars=1,toolbar=1,resizable=1,width=400,height=200,left=20,top=20');">
								Download Data Backup File
							</a>
						</li>
					</ul>
				</fieldset>
				<?php 
				if($editCode > 1){ 
					?>
					<fieldset>
						<legend><b>Administration Control Panel</b></legend>
						<ul>
							<li>
								<a href="#" onclick="toggleById('colledit');" >
									Edit Metadata and Contact Information
								</a>
							</li>
							<li>
								<a href="collprofiles.php?collid=<?php echo $collId; ?>&action=UpdateStatistics" >
									Update Statistics
								</a>
							</li>
							<li>
								<a href="collpermissions.php?collid=<?php echo $collId; ?>" >
									Manage Permissions
								</a>
							</li>
							<li>
								<a href="../admin/specuploadmanagement.php?collid=<?php echo $collId; ?>">
									Import/Update Specimen Records
								</a>
							</li>
							<?php
							if($collData['managementtype'] == 'Live Data'){ 
								?>
								<li style="margin-left:10px;">
									<a href="../admin/specupload.php?uploadtype=3&collid=<?php echo $collId; ?>">
										Quick File Upload
									</a>
								</li>
								<?php 
							}
							?>
							<li>
								<a href="../specprocessor/index.php?collid=<?php echo $collId; ?>">
									Batch Load Specimen Images
								</a>
							</li>
							<li>
								<a href="../editor/editreviewer.php?collid=<?php echo $collId; ?>">
									Review/Verify General Specimen Edits 
								</a>
							</li>
							<li>
								<a href="../datasets/datapublisher.php?collid=<?php echo $collId; ?>">
									Update Darwin Core Archive 
								</a>
							</li>
							<li>
								<a href="../editor/occurrencecleaner.php?obsuid=0&collid=<?php echo $collId; ?>">
									Data Cleaning Tools 
								</a>
							</li>
						</ul>
					</fieldset>
					<?php 
				} 
				?>
			</div>
			<?php 
		}
		if($collId || $newCollRec){
			if($action == "Add New Profile"){
				?>
				<hr />
				<div style="font-weight:bold;margin:20px;">
					New collection added successfully! <br/>
					Click <a href="../admin/specimenupload.php?collid=<?php echo $collId; ?>">here</a> 
					to upload specimen records for this new collection.
				</div>
				<hr />
				<?php 
			}
			if($editCode > 1){
				?>
				<div id="colledit" style="display:<?php echo ($newCollRec?'block':'none'); ?>;">
					<form id="colleditform" name="colleditform" action="collprofiles.php" method="post" onsubmit="return verifyCollEditForm(this)">
						<fieldset style="background-color:#FFF380;">
							<legend><b><?php echo ($newCollRec?'Add New':'Edit'); ?> Collection Information</b></legend>
							<table style="width:100%;">
								<tr>
									<td>
										Institution Code:
									</td>
									<td>
										<input type="text" name="institutioncode" value="<?php echo ($collId?$collData["institutioncode"]:'');?>" style="width:75px;" />
										<a id="instcodeinfo" href="#" onclick="return false" title="More information about Institution Code">
											<img src="../../images/info.png" style="width:15px;" />
										</a>
										<div id="instcodeinfodialog">
											The name (or acronym) in use by the institution having custody of the occurrence records. This field is required. 
											For more details, see <a href="http://darwincore.googlecode.com/svn/trunk/terms/index.htm#institutionCode" target="_blank">Darwin Core definition</a>.
										</div>
									</td>
								</tr>
								<tr>
									<td>
										Collection Code:
									</td>
									<td>
										<input type="text" name="collectioncode" value="<?php echo ($collId?$collData["collectioncode"]:'');?>" style="width:75px;" />
										<a id="collcodeinfo" href="#" onclick="return false" title="More information about Collection Code">
											<img src="../../images/info.png" style="width:15px;" />
										</a>
										<div id="collcodeinfodialog">
											The name, acronym, or code identifying the collection or data set from which the record was derived. This field is optional. 
											For more details, see <a href="http://darwincore.googlecode.com/svn/trunk/terms/index.htm#collectionCode" target="_blank">Darwin Core definition</a>.
										</div>
									</td>
								</tr>
								<tr>
									<td>
										Collection Name: 
									</td>
									<td>
										<input type="text" name="collectionname" value="<?php echo ($collId?$collData["collectionname"]:'');?>" style="width:95%;" title="Required field" />
									</td>
								</tr>
								<tr>
									<td>
										Mailing Address: 
									</td>
									<td>
										<?php 
										$instArr = $collManager->getInstitutionArr();
										?>
										<select name="iid" style="width:450px;">
											<option value="">Select Institution</option>
											<option value="">-----------------------</option>
											<?php 
											foreach($instArr as $iid => $name){
												echo '<option value="'.$iid.'" '.($collId && $collData["iid"] == $iid?'SELECTED':'').'>'.$name.'</option>';
											}
											?>
										</select>
										<?php
										if($collId && $collData["iid"]){ 
											?>
											<span>
												<a href="../admin/institutioneditor.php?iid=<?php echo $collData["iid"]; ?>" target="_blank" title="Edit institution currently linked to this collection">
													<img src="../../images/edit.png" style="width:15px;" />
												</a>
											</span>
											<?php 
										}
										?>
										<span>
											<a href="../admin/institutioneditor.php?emode=1&instcode=<?php echo ($collId?$collData["collectioncode"]:''); ?>" target="_blank" title="Add a New Institution">
												<img src="../../images/add.png" style="width:15px;" />
											</a>
										</span>
									</td>
								</tr>
								<tr>
									<td>
										Description<br/>
										(2000 character max): 
									</td>
									<td>
										<textarea name="fulldescription" style="width:95%;height:90px;"><?php echo ($collId?$collData["fulldescription"]:'');?></textarea>
									</td>
								</tr>
								<tr>
									<td>
										Homepage:
									</td>
									<td>
										<input type="text" name="homepage" value="<?php echo ($collId?$collData["homepage"]:'');?>" style="width:350;" />
									</td>
								</tr>
								<tr>
									<td>
									Contact: 
										</td>
									<td>
										<input type="text" name="contact" value="<?php echo ($collId?$collData["contact"]:'');?>" style="width:350;" />
									</td>
								</tr>
								<tr>
									<td>
										Email:
									</td>
									<td>
										<input type="text" name="email" value="<?php echo ($collId?$collData["email"]:'');?>" style="width:350;" />
									</td>
								</tr>
								<tr>
									<td>
										Latitude:
									</td>
									<td>
										<input id="latdec" type="text" name="latitudedecimal" value="<?php echo ($collId?$collData["latitudedecimal"]:'');?>" />
										<span style="cursor:pointer;" onclick="openMappingAid();">
											<img src="../../images/world40.gif" style="width:12px;" />
										</span>
									</td>
								</tr>
								<tr>
									<td>
										Longitude:
									</td>
									<td>
										<input id="lngdec" type="text" name="longitudedecimal" value="<?php echo ($collId?$collData["longitudedecimal"]:'');?>" />
									</td>
								</tr>
								<?php 
								$collCatArr = $collManager->getCatagoryArr();
								if($collCatArr){
									?>
									<tr>
										<td>
											Catagory:
										</td>
										<td>
											<select name="ccpk">
												<option value="">No Catagory</option>
												<option value="">-------------------------------------------</option>
												<?php 
												foreach($collCatArr as $ccpk => $catagory){
													echo '<option value="'.$ccpk.'" '.($collId && $ccpk==$collData['ccpk']?'SELECTED':'').'>'.$catagory.'</option>';
												}
												?>
											</select>
										</td>
									</tr>
									<?php
								}
								?>
								<tr>
									<td>
										Allow Public Edits:
									</td>
									<td>
										<input type="checkbox" name="publicedits" value="1" <?php echo ($collData && $collData['publicedits']?'CHECKED':''); ?> />
										<a id="peditsinfo" href="#" onclick="return false" title="More information about Public Edits">
											<img src="../../images/info.png" style="width:15px;" />
										</a>
										<div id="peditsinfodialog">
											Checking public edits will allow any logged in user to edit a errors. 
											However, if the user does not have explicit authorizaation to edit the collection, 
											edits will not be appied until they are reveiwed and approved by collection administrator.
										</div>
									</td>
								</tr>
								<tr>
									<td>
										Rights:
									</td>
									<td>
										<?php 
										if(isset($rightsTerms)){
											?>
											<select name="rights">
												<option value="">Select usage rights</option>
												<option value="">-----------------------</option>
												<?php
												$hasOrphanTerm = true; 
												foreach($rightsTerms as $k => $v){
													$selectedTerm = '';
													if($collId && strtolower($collData["rights"])==strtolower($v)){
														$selectedTerm = 'SELECTED';
														$hasOrphanTerm = false;
													}
													echo '<option value="'.$v.'" '.$selectedTerm.'>'.$k.'</option>'."\n";
												}
												if($hasOrphanTerm && $collData["rights"]){
													echo '<option value="'.$collData["rights"].'" SELECTED>'.$collData["rights"].' [orphaned term]</option>'."\n";
												}
												?>
											</select>
											<?php 
										}
										else{
											?>
											<input type="text" name="rights" value="<?php echo ($collId?$collData["rights"]:'');?>" style="width:350px;" />
											<?php 
										}
										?>
										<a id="rightsinfo" href="#" onclick="return false" title="More information about Rights">
											<img src="../../images/info.png" style="width:15px;" />
										</a>
										<div id="rightsinfodialog">
											Information about rights held in and over the resource. 
											This field can be limited to a set of values by modifying the portal's central configuration file.
											For more details, see <a href="http://darwincore.googlecode.com/svn/trunk/terms/index.htm#dcterms:rights" target="_blank">Darwin Core definition</a>.
										</div>
									</td>
								</tr>
								<tr>
									<td>
										Rights Holder:
									</td>
									<td>
										<input type="text" name="rightsholder" value="<?php echo ($collId?$collData["rightsholder"]:'');?>" style="width:350px;" />
										<a id="rightsholderinfo" href="#" onclick="return false" title="More information about Rights Holder">
											<img src="../../images/info.png" style="width:15px;" />
										</a>
										<div id="rightsholderinfodialog">
											The organization or person managing or owning the rights of the resource.
											For more details, see <a href="http://darwincore.googlecode.com/svn/trunk/terms/index.htm#dcterms:rightsHolder" target="_blank">Darwin Core definition</a>.
										</div>
									</td>
								</tr>
								<tr>
									<td>
										Access Rights:
									</td>
									<td>
										<input type="text" name="accessrights" value="<?php echo ($collId?$collData["accessrights"]:'');?>" style="width:350px;" />
										<a id="accessrightsinfo" href="#" onclick="return false" title="More information about Access Rights">
											<img src="../../images/info.png" style="width:15px;" />
										</a>
										<div id="accessrightsinfodialog">
											Informations or a URL link to page with details explaining how one can use the data.   
											See <a href="http://darwincore.googlecode.com/svn/trunk/terms/index.htm#dcterms:accessRights" target="_blank">Darwin Core definition</a>.
										</div>
									</td>
								</tr>
								<tr>
									<td>
										<span title="Source of Global Unique Identifier">GUID source:</span> 
									</td>
									<td>
										<select name="guidtarget">
											<option value="">Not defined</option>
											<option value="">-------------------</option>
											<option value="occurrenceId" <?php echo ($collId && $collData["guidtarget"]=='occurrenceId'?'SELECTED':''); ?>>Occurrence Id</option>
											<option value="catalogNumber" <?php echo ($collId && $collData["guidtarget"]=='catalogNumber'?'SELECTED':''); ?>>Catalog Number</option>
											<option value="symbiotaUUID" <?php echo ($collId && $collData["guidtarget"]=='symbiotaUUID'?'SELECTED':''); ?>>Symbiota Generated GUID (UUID)</option>
										</select>
										<a id="guidinfo" href="#" onclick="return false" title="More information about Global Unique Identifier">
											<img src="../../images/info.png" style="width:15px;" />
										</a>
										<div id="guidinfodialog">
											Occurrence Id is generally used for Snapshot datasets when a Global Unique Identifier (GUID) is field  
											is supplied by the source database and the GUID is mapped to the occurrenceId field.
											The use of the Occurrence Id as the GUID is not recommended for live datasets. 
											Catalog Number can be used when the value within the catalog number field is globally unique.
											The Symbiota Generated GUID (UUID) option will inform the Symbiota instance to automatically 
											generate UUID GUIDs for each records. This option is particularly recommended for many for Live Datasets.  
										</div>
									</td>
								</tr>
								<?php 
								if($isAdmin){ 
									?>
									<tr>
										<td>
											Collection Type:
										</td>
										<td>
											<select name="colltype">
												<option>Preserved Specimens</option>
												<option <?php echo ($collId && $collData["colltype"]=='Observations'?'SELECTED':''); ?>>Observations</option>
												<option <?php echo ($collId && $collData["colltype"]=='General Observations'?'SELECTED':''); ?>>General Observations</option>
											</select>
											<a id="colltypeinfo" href="#" onclick="return false" title="More information about Collection Type">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="colltypeinfodialog">
												Preserve Specimens mean that physical samples exist and can be inspected by researchers. 
												Use Observations when the record is not based on a physical specimen. 
												General Observations are used for setting up group projects where registered users
												can independently manage their own dataset directly within the single project. General Observation 
												projects are typically used by field researchers to manage their collection data and print labels 
												prior to depositing the physIcal material within a collection. Even though personal collections 
												are represented by a physical sample, they are classified as &quot;observations&quot; until the 
												physical material is deposited within a publicly available collection with active curation.     
											</div>
										</td>
									</tr>
									<tr>
										<td>
											Management:
										</td>
										<td>
											<select name="managementtype">
												<option>Snapshot</option>
												<option <?php echo ($collId && $collData["managementtype"]=='Live Data'?'SELECTED':''); ?>>Live Data</option>
											</select>
											<a id="managementinfo" href="#" onclick="return false" title="More information about Management Type">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="managementinfodialog">
												Use Snapshot when there is a separate in-house database maintained in the collection and the dataset 
												within the Symbiota portal is only a periotically updated snapshot of the central database. 
												A Live dataset is when the data is managed directly within the portal and the central database is the portal data. 
											</div>
										</td>
									</tr>
									<tr>
										<td>
											Icon URL:
										</td>
										<td>
											<input type="text" name="icon" style="width:350px;" value="<?php echo ($collId?$collData["icon"]:'');?>" title="Small url representing the collection" />
											<a id="iconinfo" href="#" onclick="return false" title="What is an Icon?">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="iconinfodialog">
												URL to an image icon that represents the collection. Icons are usually place in the 
												/images/collicon/ folder. Path can be absolute, relative, or of the format 
												&quot;images/collicon/acro.jpg&quot; 
												The use of icons are optional.
											</div>
										</td>
									</tr>
									<tr>
										<td>
											Source Record URL:
										</td>
										<td>
											<input type="text" name="individualurl" style="width:350px;" value="<?php echo ($collId?$collData["individualurl"]:'');?>" title="Dynamic link to source database individual record page" />
											<a id="sourceurlinfo" href="#" onclick="return false" title="More information about Source Records URL">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="sourceurlinfodialog">
												Adding a URL template here will dynamically generate and add the specimen details page a link to the 
												source record. For example, &quot;http://sweetgum.nybg.org/vh/specimen.php?irn=--DBPK--&quot;
												will generate a url to the NYBG collection with &quot;--DBPK--&quot; being replaced with the 
												NYBG's Primary Key (dbpk data field within the ommoccurrence table). 
												Template pattern --CATALOGNUMBER-- can also be used in place of --DBPK-- 
											</div>
										</td>
									</tr>
									<tr>
										<td>
											Sort Sequence:
										</td>
										<td>
											<input type="text" name="sortseq" value="<?php echo ($collId?$collData["sortseq"]:'');?>" />
											<a id="sortinfo" href="#" onclick="return false" title="More information about Sorting">
												<img src="../../images/info.png" style="width:15px;" />
											</a>
											<div id="sortinfodialog">
												Leave this field empty if you want the collections to sort alphabetically (default) 
											</div>
										</td>
									</tr>
									<tr>
										<td>
											Global Unique ID:
										</td>
										<td>
											<?php echo ($collId?$collData['guid']:''); ?>
										</td>
									</tr>
									<tr>
										<td>
											Security Key:
										</td>
										<td>
											<?php echo ($collId?$collData['skey']:''); ?>
										</td>
									</tr>
									<?php 
								} 
								?>
								<tr>
									<td colspan="2">
										<div style="margin:20px;">
											<?php 
											if($newCollRec){
												?>
												<input type="submit" name="action" value="Add New Profile" />
												<?php
											}
											else{
												?>
												<input type="hidden" name="collid" value="<?php echo $collId;?>" />
												<input type="submit" name="action" value="Submit Edits" />
												<?php 
											}
											?>
										</div>
									</td>
								</tr>
							</table>
						</fieldset>
					</form>
				</div>
				<?php
			}
			if(!$newCollRec){
				?>
				<div style='margin:10px;'>
					<div>
						<?php echo $collData["fulldescription"]; ?>
					</div>
					<div style='margin-top:5px;'>
						<b>Contact:</b> <?php echo $collData["contact"]." (".str_replace("@","&lt;at&gt;",$collData["email"]);?>)
					</div>
					<?php 
					if($collData["homepage"]){
						?>
						<div style="margin-top:5px;">
							<b>Home Page:</b> 
							<a href="<?php echo $collData["homepage"]; ?>">
								<?php echo $collData["homepage"]; ?>
							</a>
						</div>
						<?php 
					}
					?>
					<div style="margin-top:5px;"> 
						<b>Management: </b> 
						<?php 
						if(stripos($collData['managementtype'],'live') !== false){
							echo 'Live Data managed directly within data portal';
						}
						else{
							echo 'Data snapshot of central database ';
							echo '<div style="margin-top:5px;"><b>Last Update:</b> '.$collData['uploaddate'].'</div>';
						}
						?>
					</div>
					<div style="margin-top:5px;">
						<b>Global Unique Identifier: </b>
						<?php echo ($collId?$collData['guid']:''); ?>
					</div>
	 				<?php 
	 				if($collData["institutionname"]){ 
	 					?>
						<div style="float:left;font-weight:bold;margin-top:5px;">Address:&nbsp;</div>
						<div style="float:left;margin-top:5px;">
							<?php 
							echo "<div>".$collData["institutionname"].($collData["institutioncode"]?" (".$collData["institutioncode"].")":"")."</div>";
							if($collData["address1"]) echo "<div>".$collData["address1"]."</div>";
							if($collData["address2"]) echo "<div>".$collData["address2"]."</div>";
							if($collData["city"]) echo "<div>".$collData["city"].", ".$collData["stateprovince"]."&nbsp;&nbsp;&nbsp;".$collData["postalcode"]."</div>";
							if($collData["country"]) echo "<div>".$collData["country"]."</div>";
							if($collData["phone"]) echo "<div>".$collData["phone"]."</div>";
							?>
						</div>
						<?php 
	 				} 
	 				?>
	 				<div style="clear:both;">&nbsp;</div>
					<div style="clear:both;">
						<div style="font-weight:bold;">Collection Statistics</div>
						<ul>
							<li><?php echo $collData["recordcnt"];?> specimens</li>
							<li><?php echo $collData["georefpercent"];?>% georeferenced</li>
							<?php 
							if($collData['imgpercent']) echo '<li>'.$collData['imgpercent'].'% with images</li>';
							if($collData['gencnt']) echo '<li>'.$collData['gencnt'].' GenBank references</li>'; 
							if($collData['boldcnt']) echo '<li>'.$collData['boldcnt'].' BOLD references</li>'; 
							if($collData['refcnt']) echo '<li>'.$collData['refcnt'].' publication references</li>'; 
							?>
							<li><?php echo $collData["familycnt"];?> families</li>
							<li><?php echo $collData["genuscnt"];?> genera</li>
							<li><?php echo $collData["speciescnt"];?> species</li>
						</ul>
					</div>
				</div>
				<fieldset style='margin:20px;width:200px;background-color:#FFFFCC;'>
					<legend><b>Extra Statistics</b></legend>
					<div>
						<a href='collprofiles.php?collid=<?php echo $collId;?>&sfl=1'>
							Show Family Distribution
						</a>
					</div>
					<div>
						<a href='collprofiles.php?collid=<?php echo $collId;?>&sgl=1'>
							Show Geographic Distribution
						</a>
					</div>
				</fieldset>
				<div style="margin:25px;">
					<a href="collectionindex.php?collid=<?php echo $collId;?>">Full Specimen List</a>
				</div>
				<?php
				if($showFamilyList || $showGeographicList){
					?>
					<fieldset style="margin:20px;width:90%;">
						<legend>
							<b>
								<?php 
								if($showFamilyList){
									echo 'Family Distribution';
									if($familyDist){
										echo ' - '.$familyDist;
									}
								}
								else{
									echo 'Geographic Distribution';
									if($countryDist){
										echo ' - '.$countryDist;
									}
									elseif($stateDist){
										echo ' - '.$stateDist;
									}
								}
								?>
							</b>
						</legend>
						<div style="margin:15px;">Click on the specimen record counts within the parenthesis to return the records for that term</div>
						<ul>
							<?php 
							$distArr = array();
							if($showFamilyList){
								$distArr = $collManager->getTaxonCounts();
							}
							else{
								$distArr = $collManager->getGeographicCounts($countryDist,$stateDist);
							}
							foreach($distArr as $term => $cnt){
								echo '<li>';
								$colTarget = 'county';
								if($showGeographicList && !$stateDist){
									echo '<a href="collprofiles.php?sgl=1&collid='.$collId.($countryDist?'&state=':'&country=').$term.'">';
									echo $term;
									echo '</a>';
									$colTarget = 'country';
									if($countryDist) $colTarget = 'state';
									echo ' (<a href="../list.php?db[]='.$collId.'&reset=1&'.$colTarget.'='.$term.'" target="_blank">'.$cnt.'</a>)';
								}
								elseif($showFamilyList && !$familyDist){
									//echo '<a href="collprofiles.php?sfl=1&collid='.$collId.'&family='.$term.'">';
									echo $term;
									//echo '</a>';
									echo ' (<a href="../list.php?db[]='.$collId.'&type=1&reset=1&taxa='.$term.'" target="_blank">'.$cnt.'</a>)';
								}
								else{
									echo $term;
									echo ' (<a href="../list.php?db[]='.$collId.'&reset=1&'.$colTarget.'='.$term.'" target="_blank">'.$cnt.'</a>)';
								}
								echo '</li>';
							}
							?>
						</ul>
						<?php 
							if(!$stateDist && !$familyDist) echo '*Clicking on term in list will display distributions within that term';
						?>
					</fieldset>
					<?php 
				}
			}
		}
		else{
			$collList = $collManager->getCollectionList();
			if($isAdmin){
				?>
				<div style="float:right;">
					<a href="collprofiles.php?newcoll=1">
						<img src="../../images/add.png" title="Add a brand new collection profile to portal" />
					</a>
				</div>
				<?php
			} 
			?>
			<h1><?php echo $defaultTitle; ?> Collections </h1>
			<div style='margin:10px;clear:both;'>
				Select a collection to see full details. 
			</div>
			<table style='margin:10px;'>
				<?php 
				foreach($collList as $cId => $collArr){
					?>
					<tr>
						<td style='text-align:center;vertical-align:top;'>
							<?php 
							$iconStr = $collArr['icon'];
							if($iconStr){
								if(substr($iconStr,0,6) == 'images') $iconStr = '../../'.$iconStr; 
								?>
								<img src='<?php echo $iconStr; ?>' style='border-size:1px;height:30;width:30;' /><br/>
								<?php
								echo $collArr['institutioncode'];
							} 
							?>
						</td>
						<td>
							<h3>
								<a href='collprofiles.php?collid=<?php echo $cId;?>'>
									<?php echo $collArr['collectionname']; ?>
								</a>
							</h3>
							<div style='margin:10px;'>
								<div><?php echo $collArr['fulldescription']; ?></div>
								<div style='margin-top:5px;'>
									<b>Contact:</b> 
									<?php echo $collArr['contact'].' ('.str_replace('@','&lt;at&gt;',$collArr['email']).')';?>
								</div>
								<div style='margin-top:5px'>
									<b>Home Page:</b> 
									<a href='<?php echo $collArr['homepage']; ?>'>
										<?php echo $collArr['homepage']; ?>
									</a>
								</div>
							</div>
							<div style='margin:5px 0px 15px 10px;'>
								<a href='collprofiles.php?collid=<?php echo $cId; ?>'>More Information</a>
							</div>
						</td>
					</tr>
					<tr>
						<td colspan='2'><hr/></td>
					</tr>
					<?php 
				}
				?>
			</table>
			<?php 
		}
		?>
	</div>
	<?php
		include($serverRoot.'/footer.php');
	?>
</body>
</html>