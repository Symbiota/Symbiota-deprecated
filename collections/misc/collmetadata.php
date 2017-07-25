<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCollectionProfile.php');
header("Content-Type: text/html; charset=".$CHARSET);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/misc/collmetadata.php?'.$_SERVER['QUERY_STRING']);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
$collid = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;

$statusStr = '';

$collManager = new OccurrenceCollectionProfile();
if(!$collManager->setCollid($collid)) $collid = '';

$isEditor = 0;
$collPubArr = array();
$publishGBIF = false;
$publishIDIGBIO = false;

if($IS_ADMIN){
	$isEditor = 1;
}
elseif($collid){
	if(array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collid,$USER_RIGHTS["CollAdmin"])){
		$isEditor = 1;
	}
}

if($isEditor){
	if($action == 'Save Edits'){
		$statusStr = $collManager->submitCollEdits($_POST);
		if($statusStr == true){
			header('Location: collprofiles.php?collid='.$collid);
		}
	}
	elseif($action == "Create New Collection"){
		if($IS_ADMIN){
			$newCollid = $collManager->submitCollAdd($_POST);
			if(is_numeric($newCollid)){
				$statusStr = 'New collection added successfully! <br/>Click <a href="../admin/specuploadmanagement.php?collid='.$newCollid.'&action=addprofile">here</a> to upload specimen records for this new collection.';
				header('Location: collprofiles.php?collid='.$newCollid);
			}
			else{
				$statusStr = $collid;
			}
		}
	}
	elseif($action == 'Link Address'){
		if(!$collManager->linkAddress($_POST['iid'])){
			$statusStr = $collManager->getErrorStr();
		}
	}
	elseif(array_key_exists('removeiid',$_GET)){
		if(!$collManager->removeAddress($_GET['removeiid'])){
			$statusStr = $collManager->getErrorStr();
		}
	}
}
if(isset($GBIF_USERNAME) && isset($GBIF_PASSWORD) && isset($GBIF_ORG_KEY) && $collid){
	$collPubArr = $collManager->getCollPubArr($collid);
	if($collPubArr[$collid]['publishToGbif']){
		$publishGBIF = true;
	}
	if($collPubArr[$collid]['publishToIdigbio']){
		$publishIDIGBIO = true;
	}
}
$collData = $collManager->getCollectionData(true);
?>
<html>
<head>
	<title><?php echo $defaultTitle." ".($collid?$collData["collectionname"]:"") ; ?> Collection Profiles</title>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/jquery-ui.css" type="text/css" rel="stylesheet" />
	<script src="../../js/jquery.js" type="text/javascript"></script>
	<script src="../../js/jquery-ui.js" type="text/javascript"></script>
	<script>

		$(function() {
			var dialogArr = new Array("instcode","collcode","pedits","pubagg","rights","rightsholder","accessrights","guid","colltype","management","icon","collectionguid","sourceurl","sort");
			var dialogStr = "";
			for(i=0;i<dialogArr.length;i++){
				dialogStr = dialogArr[i]+"info";
				$( "#"+dialogStr+"dialog" ).dialog({
					autoOpen: false,
					modal: true,
					position: { my: "left top", at: "right bottom", of: "#"+dialogStr }
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
			else if(f.managementtype.value == "Snapshot" && f.guidtarget.value == "symbiotaUUID"){
				alert("The Symbiota Generated GUID option cannot be selected for a collection that is managed locally outside of the data portal (e.g. Snapshot management type). In this case, the GUID must be generated within the source collection database and delivered to the data portal as part of the upload process.");
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

		function mtypeguidChanged(f){
			if(f.managementtype.value == "Snapshot" && f.guidtarget.value == "symbiotaUUID"){
				alert("The Symbiota Generated GUID option cannot be selected for a collection that is managed locally outside of the data portal (e.g. Snapshot management type). In this case, the GUID must be generated within the source collection database and delivered to the data portal as part of the upload process.");
			}
			else if(f.managementtype.value == "Aggregate" && f.guidtarget.value != "" && f.guidtarget.value != "occurrenceId"){
				alert("An Aggregate dataset (e.g. specimens coming from multiple collections) can only have occurrenceID selected for the GUID source");
				f.guidtarget.value = 'occurrenceId';
			}
			if(!f.guidtarget.value){
				f.publishToGbif.checked = false;
			}
		}
		
		function checkGUIDSource(f){
			if(f.publishToGbif.checked == true){
				if(!f.guidtarget.value){
					alert("You must select a GUID source in order to publish to data aggregators.");
					f.publishToGbif.checked = false;
				}
			}
		}

		function verifyAddAddressForm(f){
			if(f.iid.value == ""){
				alert("Select an institution to be linked");
				return false;
			}
			return true;
		}
		
		function toggle(target){
			var objDiv = document.getElementById(target);
			if(objDiv){
				if(objDiv.style.display=="none"){
					objDiv.style.display = "block";
				}
				else{
					objDiv.style.display = "none";
				}
			}
			else{
				var divs = document.getElementsByTagName("div");
				for (var h = 0; h < divs.length; h++) {
				var divObj = divs[h];
					if(divObj.className == target){
						if(divObj.style.display=="none"){
							divObj.style.display="block";
						}
						else {
							divObj.style.display="none";
						}
					}
				}
			}
		}
		
		function verifyIconImage(f){
			var iconImageFile = document.getElementById("iconfile").value;
			if(iconImageFile){
				var iconExt = iconImageFile.substr(iconImageFile.length-4);
				iconExt = iconExt.toLowerCase();
				if((iconExt != '.jpg') && (iconExt != 'jpeg') && (iconExt != '.png') && (iconExt != '.gif')){
					document.getElementById("iconfile").value = '';
					alert("The file you have uploaded is not a supported image file. Please upload a jpg, png, or gif file.");
				}
				else{
					var fr = new FileReader;
					fr.onload = function(){
						var img = new Image;
						img.onload = function(){
							if((img.width>350) || (img.height>350)){
								document.getElementById("iconfile").value = '';
								img = '';
								alert("The image file must be less than 350 pixels in both width and height.");
							}
						};
						img.src = fr.result;
					};
					fr.readAsDataURL(document.getElementById("iconfile").files[0]);
				}
			}
		}
		
		function verifyIconURL(f){
			var iconImageFile = document.getElementById("iconurl").value;
			if((iconImageFile.substr(iconImageFile.length-4) != '.jpg') && (iconImageFile.substr(iconImageFile.length-4) != '.png') && (iconImageFile.substr(iconImageFile.length-4) != '.gif')){
				document.getElementById("iconurl").value = '';
				alert("The url you have entered is not for a supported image file. Please enter a url for a jpg, png, or gif file.");
			}
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
	include($SERVER_ROOT.'/header.php');
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
		if($collid){
			echo '<a href="collprofiles.php?collid='.$collid.'&emode=1">Collection Management</a> &gt;&gt; ';
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
				<fieldset style="background-color:#FFF380;">
					<legend><b><?php echo ($collid?'Edit':'Add New'); ?> Collection Information</b></legend>
					<form id="colleditform" name="colleditform" action="collmetadata.php" method="post" enctype="multipart/form-data" onsubmit="return verifyCollEditForm(this)">
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
										<img src="../../images/world.png" style="width:12px;" />
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
										and resolve errors found within the collection. However, if the user does not have explicit 
										authorization for the given collection, edits will not be applied until they are 
										reviewed and approved by collection administrator.
									</div>
								</td>
							</tr>
							<tr>
								<td>
									License:
								</td>
								<td>
									<?php 
									if(isset($rightsTerms)){
										?>
										<select name="rights">
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
											if($hasOrphanTerm && array_key_exists("rights",$collData)){
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
										A legal document giving official permission to do something with the resource. 
										This field can be limited to a set of values by modifying the portal's central configuration file.
										For more details, see <a href="http://darwincore.googlecode.com/svn/trunk/terms/index.htm#dcterms:license" target="_blank">Darwin Core definition</a>.
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
									<select name="guidtarget" onchange="mtypeguidChanged(this.form)">
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
										Occurrence Id is generally used for Snapshot datasets when a Global Unique Identifier (GUID) field  
										is supplied by the source database (e.g. Specify database) and the GUID is mapped to the 
										<a href="http://darwincore.googlecode.com/svn/trunk/terms/index.htm#occurrenceID" target="_blank">occurrenceId</a> field.
										The use of the Occurrence Id as the GUID is not recommended for live datasets. 
										Catalog Number can be used when the value within the catalog number field is globally unique.
										The Symbiota Generated GUID (UUID) option will trigger the Symbiota data portal to automatically 
										generate UUID GUIDs for each record. This option is recommended for many for Live Datasets 
										but not allowed for Snapshot collections that are managed in local management system.
									</div>
								</td>
							</tr>
                            <tr>
                                <td>
                                    Publish to Aggregators:
                                </td>
                                <td>
                                    <?php
                                    if(isset($GBIF_USERNAME) && isset($GBIF_PASSWORD) && isset($GBIF_ORG_KEY)) {
                                        ?>
                                        <div>
                                            GBIF <input type="checkbox" name="publishToGbif" value="1"
                                                        onchange="checkGUIDSource(this.form);" <?php echo($publishGBIF ? 'CHECKED' : ''); ?> />
                                            <a id="pubagginfo" href="#" onclick="return false"
                                               title="More information about Publishing to Aggregators">
                                                <img src="../../images/info.png" style="width:15px;"/>
                                            </a>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                    <div>
                                        iDigBio <input type="checkbox" name="publishToIdigbio" value="1" onchange="checkGUIDSource(this.form);" <?php echo($publishIDIGBIO?'CHECKED':''); ?> />
                                    </div>
                                    <div id="pubagginfodialog">
                                        Check boxes to make Darwin Core Archives published from this collection
                                        available to iDigBio and/or GBIF (if activated in this portal).
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
							<tr>
								<td>
									Icon URL:
								</td>
								<td>
									<div style='float:left;width:90%;margin-top:0px;'>
										<div class="targetdiv" style="<?php echo (($collid&&$collData["icon"])?'display:none;':'display:block;'); ?>margin-top:0px;">
											<!-- following line sets MAX_FILE_SIZE (must precede the file input field)  -->
											<div style="float:left;">
												<input type='hidden' name='MAX_FILE_SIZE' value='20000000' />
												<input name='iconfile' id='iconfile' type='file' size='70' onchange="verifyIconImage(this.form);" />
											</div>
											<div style="margin-right:15px;text-decoration:underline;float:right;font-weight:bold;">
												<a href="#" onclick="toggle('targetdiv');return false;">Enter URL</a>
											</div>
										</div>
										<div class="targetdiv" style="<?php echo (($collid&&$collData["icon"])?'display:block;':'display:none;'); ?>margin-top:0px;">
											<div style="float:left;width:65%;">
												<input style="width:100%;margin-top:0px;" type='text' name='iconurl' id='iconurl' value="<?php echo ($collid?$collData["icon"]:'');?>" onchange="verifyIconURL(this.form);" />
											</div>
											<div style="margin-right:15px;text-decoration:underline;float:right;font-weight:bold;">
												<a href="#" onclick="toggle('targetdiv');return false;">
													Upload Local Image
												</a>
											</div>
										</div>
									</div>
									<a id="iconinfo" href="#" onclick="return false" title="What is an Icon?">
										<img src="../../images/info.png" style="width:15px;" />
									</a>
									<div id="iconinfodialog">
										Upload an icon image file or enter the URL of an image icon that represents the collection. If entering the URL of an image already located 
										on a server, click on &quot;Enter URL&quot;. The URL path can be absolute or relative. The use of icons are optional.
									</div>
								</td>
							</tr>
							<?php 
							if($IS_ADMIN){ 
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
											Preserve Specimens means that physical samples exist and can be inspected by researchers. 
											Use Observations when the record is not based on a physical specimen. 
											General Observations are used for setting up group projects where registered users
											can independently manage their own dataset directly within the single collection. General Observation 
											collections are typically used by field researchers to manage their collection data and print labels 
											prior to depositing the physical material within a collection. Even though personal collections 
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
										<select name="managementtype" onchange="mtypeguidChanged(this.form)">
											<option>Snapshot</option>
											<option <?php echo ($collid && $collData["managementtype"]=='Live Data'?'SELECTED':''); ?>>Live Data</option>
											<option <?php echo ($collid && $collData["managementtype"]=='Aggregate'?'SELECTED':''); ?>>Aggregate</option>
										</select>
										<a id="managementinfo" href="#" onclick="return false" title="More information about Management Type">
											<img src="../../images/info.png" style="width:15px;" />
										</a>
										<div id="managementinfodialog">
											Use Snapshot when there is a separate in-house database maintained in the collection and the dataset 
											within the Symbiota portal is only a periodically updated snapshot of the central database. 
											A Live dataset is when the data is managed directly within the portal and the central database is the portal data. 
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
								<?php 
							} 
							if($collid){ 
								?>
								<tr>
									<td>
										Global Unique ID:
									</td>
									<td>
										<?php 
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
									</td>
								</tr>
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
							else{
								//New collection 
								?>
								<tr>
									<td>
										Global Unique ID:
									</td>
									<td>
										<input type="text" name="collectionguid" value="" style="width:90%;" />
										<a id="collectionguidinfo" href="#" onclick="return false" title="More information">
											<img src="../../images/info.png" style="width:15px;" />
										</a>
										<div id="collectionguidinfodialog">
											Global Unique Identifier for this collection. 
											If your collection already has a GUID (e.g. previously assigned by a  
											collection management application such as Specify), that identifier should be entered here.
											If you leave blank, the portal will automatically 
											generate a UUID for this collection (recommended if GUID is not known to already exist).  
										</div>
									</td>
								</tr>
								<?php
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
					</form>
				</fieldset>
			</div>
			<div>
				<fieldset style="background-color:#FFF380;">
					<legend><b>Mailing Address</b></legend>
					<?php
					$instArr = $collManager->getAddresses();
					if($instArr){
						//Edit or remove address
						$cnt = 0;
						foreach($instArr as $iid => $iArr){
							?>
							<div style="margin:25px;">
								<?php 
								echo '<div>';
								echo $iArr['institutionname'].($iArr['institutioncode']?' ('.$iArr['institutioncode'].')':'');
								?>
								<a href="../admin/institutioneditor.php?emode=1&targetcollid=<?php echo $collid.'&iid='.$iid; ?>" title="Edit institution address">
									<img src="../../images/edit.png" style="width:14px;" />
								</a>
								<a href="collmetadata.php?collid=<?php echo $collid.'&removeiid='.$iid; ?>" title="Unlink institution address">
									<img src="../../images/drop.png" style="width:14px;" />
								</a>
								<?php 
								echo '</div>';
								if($iArr['address1']) echo '<div>'.$iArr['address1'].'</div>';
								if($iArr['address2']) echo '<div>'.$iArr['address2'].'</div>';
								if($iArr['city'] || $iArr['stateprovince']) echo '<div>'.$iArr['city'].', '.$iArr['stateprovince'].' '.$iArr['postalcode'].'</div>';
								if($iArr['country']) echo '<div>'.$iArr['country'].'</div>';
								if($iArr['phone']) echo '<div>'.$iArr['phone'].'</div>';
								if($iArr['contact']) echo '<div>'.$iArr['contact'].'</div>';
								if($iArr['email']) echo '<div>'.$iArr['email'].'</div>';
								if($iArr['url']) echo '<div><a href="'.$iArr['url'].'">'.$iArr['url'].'</a></div>';
								if($iArr['notes']) echo '<div>'.$iArr['notes'].'</div>';
								?>
							</div>
							<?php 
							if($cnt) echo '<hr/>';
							$cnt++;
						}
					}
					else{
						//Link new institution
						?>
						<div style="margin:40px;"><b>No addesses linked</b></div>
						<div style="margin:20px;">
							<form name="addaddressform" action="collmetadata.php" method="post" onsubmit="return verifyAddAddressForm(this)">
								<select name="iid" style="width:425px;">
									<option value="">Select Institution Address</option>
									<option value="">------------------------------------</option>
									<?php 
									$addrArr = $collManager->getInstitutionArr();
									foreach($addrArr as $iid => $name){
										echo '<option value="'.$iid.'">'.$name.'</option>';
									}
									?>
								</select>
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<input name="action" type="submit" value="Link Address" />
							</form>
							<div style="margin:15px;">
								<a href="../admin/institutioneditor.php?emode=1&targetcollid=<?php echo $collid; ?>" title="Add a new address not on the list">
									<b>Add an institution not on list</b>
								</a>
							</div>
						</div>
						<?php 
					}
					?>
				</fieldset>
			</div>
			<?php
		}
		?>
	</div>
	<?php
	include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>