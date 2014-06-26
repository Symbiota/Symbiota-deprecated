<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/CollectionProfileManager.php');
header("Content-Type: text/html; charset=".$charset);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/misc/collmetadata.php?'.$_SERVER['QUERY_STRING']);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
$collid = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$statusStr = '';

$collManager = new CollectionProfileManager();
$collManager->setCollectionId($collid);

$isEditor = 0;
if($IS_ADMIN){
	$isEditor = 1;
}
elseif($collid){
	if(array_key_exists("CollAdmin",$userRights) && in_array($collid,$userRights["CollAdmin"])){
		$isEditor = 1;
	}
	elseif(array_key_exists("CollEditor",$userRights) && in_array($collid,$userRights["CollEditor"])){
		$isEditor = 1;
	}
}

if($isEditor){
	if($action == 'Save Edits'){
		$statusStr = $collManager->submitCollEdits();
		if($statusStr == true){
			header('Location: collprofiles.php?collid='.$collid);
		}
	}
	elseif($action == "Create New Collection"){
		if($IS_ADMIN){
			$newCollid = $collManager->submitCollAdd();
			if(is_numeric($newCollid)){
				$statusStr = 'New collection added successfully! <br/>Click <a href="../admin/specuploadmanagement.php?collid='.$newCollid.'&action=addprofile">here</a> to upload specimen records for this new collection.';
				header('Location: collprofiles.php?collid='.$newCollid);
			}
			else{
				$statusStr = $collid;
			}
		}
	}
}
$collData = $collManager->getCollectionData(1);
?>
<html>
<head>
	<title><?php echo $defaultTitle." ".($collid?$collData["collectionname"]:"") ; ?> Collection Profiles</title>
	<link href="../../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css" type="text/css" rel="stylesheet" />
	<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
	<meta name="keywords" content="Natural history collections,<?php echo ($collid?$collData["collectionname"]:""); ?>" />
	<script language=javascript>

		$(function() {
			var dialogArr = new Array("instcode","collcode","pedits","rights","rightsholder","accessrights","guid","colltype","management","icon","collectionguid","sourceurl","sort");
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
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = (isset($collections_misc_collmetadataMenu)?$collections_misc_collmetadataMenu:true);
	include($serverRoot.'/header.php');
	echo '<div class="navpath">';
	if(isset($collections_misc_collmetadataCrumbs)){
		if($collections_misc_collmetadataCrumbs){
			echo '<a href="../../index.php">Home</a> &gt;&gt; ';
			echo $collections_misc_collmetadataCrumbs.' &gt;&gt; ';
			echo '<b>'.$collData["collectionname"].' Metadata Editor</b>';
		}
	}
	else{
		echo '<a href="../../index.php">Home</a> &gt;&gt; ';
		echo '<a href="collprofiles.php?collid='.$collid.'">Collection Profile</a> &gt;&gt; ';
		if($collid){
			echo '<b>'.$collData['collectionname'].' Metadata Editor</b>';
		}
		else{
			echo '<b>Create New Collection Profile</b>';
		}
	}
	echo "</div>";
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
		if($isEditor){
			if($collid){
				echo '<h1>'.$collData['collectionname'].(array_key_exists('institutioncode',$collData)?' ('.$collData['institutioncode'].')':'').'</h1>';
			}
			?>
			<div id="colledit">
				<form id="colleditform" name="colleditform" action="collmetadata.php" method="post" onsubmit="return verifyCollEditForm(this)">
					<fieldset style="background-color:#FFF380;">
						<legend><b><?php echo ($collid?'Edit':'Add New'); ?> Collection Information</b></legend>
						<table style="width:100%;">
							<tr>
								<td>
									Institution Code:
								</td>
								<td>
									<input type="text" name="institutioncode" value="<?php echo ($collid?$collData["institutioncode"]:'');?>" style="width:75px;" />
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
									<input type="text" name="collectioncode" value="<?php echo ($collid?$collData["collectioncode"]:'');?>" style="width:75px;" />
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
									<input type="text" name="collectionname" value="<?php echo ($collid?$collData["collectionname"]:'');?>" style="width:95%;" title="Required field" />
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
											echo '<option value="'.$iid.'" '.($collid && $collData["iid"] == $iid?'SELECTED':'').'>'.$name.'</option>';
										}
										?>
									</select>
									<?php
									if($collid && $collData["iid"]){ 
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
										<a href="../admin/institutioneditor.php?emode=1&instcode=<?php echo ($collid?$collData["collectioncode"]:''); ?>" target="_blank" title="Add a New Institution">
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
									<textarea name="fulldescription" style="width:95%;height:90px;"><?php echo ($collid?$collData["fulldescription"]:'');?></textarea>
								</td>
							</tr>
							<tr>
								<td>
									Homepage:
								</td>
								<td>
									<input type="text" name="homepage" value="<?php echo ($collid?$collData["homepage"]:'');?>" style="width:90%;" />
								</td>
							</tr>
							<tr>
								<td>
								Contact: 
									</td>
								<td>
									<input type="text" name="contact" value="<?php echo ($collid?$collData["contact"]:'');?>" style="width:90%;" />
								</td>
							</tr>
							<tr>
								<td>
									Email:
								</td>
								<td>
									<input type="text" name="email" value="<?php echo ($collid?$collData["email"]:'');?>" style="width:90%;" />
								</td>
							</tr>
							<tr>
								<td>
									Latitude:
								</td>
								<td>
									<input id="latdec" type="text" name="latitudedecimal" value="<?php echo ($collid?$collData["latitudedecimal"]:'');?>" />
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
									<input id="lngdec" type="text" name="longitudedecimal" value="<?php echo ($collid?$collData["longitudedecimal"]:'');?>" />
								</td>
							</tr>
							<?php 
							$collCatArr = $collManager->getCategoryArr();
							if($collCatArr){
								?>
								<tr>
									<td>
										Category:
									</td>
									<td>
										<select name="ccpk">
											<option value="">No Category</option>
											<option value="">-------------------------------------------</option>
											<?php 
											foreach($collCatArr as $ccpk => $category){
												echo '<option value="'.$ccpk.'" '.($collid && $ccpk==$collData['ccpk']?'SELECTED':'').'>'.$category.'</option>';
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
										Checking public edits will allow any user logged into the system to modify specimen records 
										and resolve errors found within the system. However, if the user does not have explicit 
										authorization for the given collection, edits will not be applied until they are 
										reviewed and approved by collection administrator.
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
												if($collid && strtolower($collData["rights"])==strtolower($v)){
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
										<input type="text" name="rights" value="<?php echo ($collid?$collData["rights"]:'');?>" style="width:90%;" />
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
									<input type="text" name="rightsholder" value="<?php echo ($collid?$collData["rightsholder"]:'');?>" style="width:90%;" />
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
									<input type="text" name="accessrights" value="<?php echo ($collid?$collData["accessrights"]:'');?>" style="width:90%;" />
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
										<option value="occurrenceId" <?php echo ($collid && $collData["guidtarget"]=='occurrenceId'?'SELECTED':''); ?>>Occurrence Id</option>
										<option value="catalogNumber" <?php echo ($collid && $collData["guidtarget"]=='catalogNumber'?'SELECTED':''); ?>>Catalog Number</option>
										<option value="symbiotaUUID" <?php echo ($collid && $collData["guidtarget"]=='symbiotaUUID'?'SELECTED':''); ?>>Symbiota Generated GUID (UUID)</option>
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
							<tr>
								<td>
									Source Record URL:
								</td>
								<td>
									<input type="text" name="individualurl" style="width:90%;" value="<?php echo ($collid?$collData["individualurl"]:'');?>" title="Dynamic link to source database individual record page" />
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
											<option <?php echo ($collid && $collData["colltype"]=='Observations'?'SELECTED':''); ?>>Observations</option>
											<option <?php echo ($collid && $collData["colltype"]=='General Observations'?'SELECTED':''); ?>>General Observations</option>
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
											<option <?php echo ($collid && $collData["managementtype"]=='Live Data'?'SELECTED':''); ?>>Live Data</option>
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
										<input type="text" name="icon" style="width:90%;" value="<?php echo ($collid?$collData["icon"]:'');?>" title="Small url representing the collection" />
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
										Sort Sequence:
									</td>
									<td>
										<input type="text" name="sortseq" value="<?php echo ($collid?$collData["sortseq"]:'');?>" />
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
									<?php
									if($collid){
										echo $collData["guid"]; 
										?>
										<a id="collectionguidinfo" href="#" onclick="return false" title="More information">
											<img src="../../images/info.png" style="width:15px;" />
										</a>
										<div id="collectionguidinfodialog">
											Global Unique Identifier for this collection.  
											If your collection already has a GUID (e.g. previously assigned by a  
											collection management application such as Specify), that identifier should be represented here.
											If you need to change this value, contact your portal manager.  
										</div>
										<?php
									}
									else{
										//New collection 
										?>
										<input type="text" name="collectionguid" value="" style="width:90%;" />
										<a id="collectionguidinfo2" href="#" onclick="return false" title="More information">
											<img src="../../images/info.png" style="width:15px;" />
										</a>
										<div id="collectionguidinfo2dialog">
											Global Unique Identifier for this collection. 
											If your collection already has a GUID (e.g. previously assigned by a  
											collection management application such as Specify), that identifier should be entered here.
											If you leave blank, the portal will automatically 
											generate a UUID for this collection (recommended if GUID is not known to already exist).  
										</div>
										<?php
									}
									?>
									</td>
								</tr>
								<?php
								if($collid){ 
									?>
									<tr>
										<td>
											Security Key:
										</td>
										<td>
											<?php echo $collData['skey']; ?>
										</td>
									</tr>
									<?php
								}
							} 
							?>
							<tr>
								<td colspan="2">
									<div style="margin:20px;">
										<?php 
										if($collid){
											?>
											<input type="hidden" name="collid" value="<?php echo $collid;?>" />
											<input type="submit" name="action" value="Save Edits" />
											<?php 
										}
										else{
											?>
											<input type="submit" name="action" value="Create New Collection" />
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
		?>
	</div>
	<?php
		include($serverRoot.'/footer.php');
	?>
</body>
</html>