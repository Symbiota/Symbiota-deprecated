<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCollectionProfile.php');
include_once($SERVER_ROOT.'/content/lang/collections/misc/collmetadata.'.$LANG_TAG.'.php');

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
				$statusStr = 'New collection added successfully! <br/>Click <a href="../admin/specuploadmanagement.php?collid='.$newCollid.'&action=addprofile">here</a> to upload occurrence records for this new collection.';
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
if(isset($GBIF_USERNAME) && $GBIF_USERNAME && isset($GBIF_PASSWORD) && $GBIF_PASSWORD && isset($GBIF_ORG_KEY) && $GBIF_ORG_KEY && $collid){
	$collPubArr = $collManager->getCollPubArr($collid);
	if($collPubArr[$collid]['publishToGbif']){
		$publishGBIF = true;
	}
	if($collPubArr[$collid]['publishToIdigbio']){
		$publishIDIGBIO = true;
	}
}
$collData = current($collManager->getCollectionMetadata());
$collManager->cleanOutArr($collData);
?>
<html>
<head>
	<title><?php echo $defaultTitle." ".($collid?$collData["collectionname"]:"")." ".$LANG['COLLECTION_PROFILES']; ?></title>
	<link href="../../css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
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
				alert(<?php echo $LANG['ALERT_INSTITUTION_CODE_MUST']; ?>);
				return false;
			}
			else if(f.collectionname.value == ''){
				alert(<?php echo $LANG['ALERT_COLLECTION_NAME_MUST_HAVE']; ?>);
				return false;
			}
			else if(f.managementtype.value == "Snapshot" && f.guidtarget.value == "symbiotaUUID"){
				alert(<?php echo $LANG['ALERT_THE_SYMBIOTA_GENERATED_GUID']; ?>);
				return false;
			}
			else if(!isNumeric(f.latdec.value) || !isNumeric(f.lngdec.value)){
				alert(<?php echo $LANG['ALERT_LATITUDE_AND_LONGITUDE']; ?>);
				return false;
			}
			else if(f.rights.value == ""){
				alert(<?php echo $LANG['ALERT_RIGHTS_FIELD']; ?>);
				return false;
			}
			try{
				if(!isNumeric(f.sortseq.value)){
					alert(<?php echo $LANG['ALERT_SORT_SEQUENCE_MUST']; ?>);
					return false;
				}
			}
			catch(ex){}
			return true;
		}

		function mtypeguidChanged(f){
			if(f.managementtype.value == "Snapshot" && f.guidtarget.value == "symbiotaUUID"){
				alert(<?php echo $LANG['ALERT_THE_SYMBIOTA_GENERATED']; ?>);
			}
			else if(f.managementtype.value == "Aggregate" && f.guidtarget.value != "" && f.guidtarget.value != "occurrenceId"){
				alert(<?php echo $LANG['ALERT_AND_AGGREGATE_DATASET']; ?>);
				f.guidtarget.value = 'occurrenceId';
			}
			if(!f.guidtarget.value){
				f.publishToGbif.checked = false;
			}
		}

		function checkGUIDSource(f){
			if(f.publishToGbif.checked == true){
				if(!f.guidtarget.value){
					alert(<?php echo $LANG['ALERT_YOU_MUST_SELECT_A_GUID']; ?>);
					f.publishToGbif.checked = false;
				}
			}
		}

		function verifyAddAddressForm(f){
			if(f.iid.value == ""){
				alert(<?php echo $LANG['ALERT_SELECT_AN_INSTITUTION_TO_BE']; ?>);
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
					alert(<?php echo $LANG['ALERT_THE_FILE_YOU_HAVE_UPLOADED']; ?>);
				}
				else{
					var fr = new FileReader;
					fr.onload = function(){
						var img = new Image;
						img.onload = function(){
							if((img.width>350) || (img.height>350)){
								document.getElementById("iconfile").value = '';
								img = '';
								alert(<?php echo $LANG['ALERT_THE_IMAGE_FILE_MUST']; ?>);
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
				alert(<?php echo $LANG['ALERT_THE_URL_YOU_HAVE_ENTERED']; ?>);
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
			echo '<a href="../../index.php">'.$LANG['HOME'].'</a> &gt;&gt; ';
			echo $collections_misc_collmetadataCrumbs.' &gt;&gt; ';
			echo '<b>'.$collData["collectionname"].' '.$LANG['METADATA_EDITOR'].'</b>';
		}
	}
	else{
		echo '<a href="../../index.php">Home</a> &gt;&gt; ';
		if($collid){
			echo '<a href="collprofiles.php?collid='.$collid.'&emode=1">'.$LANG['COLLECTION_MANAGER'].'</a> &gt;&gt; ';
			echo '<b>'.$collData['collectionname'].' '.$LANG['METADATA_EDITOR'].'</b>';
		}
		else{
			echo '<b>'.$LANG['CREATE_NEW_COLLECTION_PROFILE'].'</b>';
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
				<fieldset style="background-color:#FFFFFF;">
					<legend><b><?php echo $LANG['CREATE'];?></b></legend>
					<form id="colleditform" name="colleditform" action="collmetadata.php" method="post" enctype="multipart/form-data" onsubmit="return verifyCollEditForm(this)">
						<table style="width:100%;">
							<tr>
								<td>
									<?php echo $LANG['COD'];?>
								</td>
								<td>
									<input type="text" name="institutioncode" value="<?php echo ($collid?$collData["institutioncode"]:'');?>" style="width:75px;" />
									<a id="instcodeinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFORMATION_ABOUT_INSTITUTION_CODE']; ?>">
										<img src="../../images/info.png" style="width:15px;" />
									</a>
									<div id="instcodeinfodialog">
										<?php echo $LANG['THE'];?> <a href="http://darwincore.googlecode.com/svn/trunk/terms/index.htm#institutionCode" target="_blank"><?php echo $LANG['DARWIN']; ?></a>.
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<?php echo $LANG['COD_1'];?>
								</td>
								<td>
									<input type="text" name="collectioncode" value="<?php echo ($collid?$collData["collectioncode"]:'');?>" style="width:75px;" />
									<a id="collcodeinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE']; ?>">
										<img src="../../images/info.png" style="width:15px;" />
									</a>
									<div id="collcodeinfodialog">
										<?php echo $LANG['THE_NAME'];?> <a href="http://darwincore.googlecode.com/svn/trunk/terms/index.htm#collectionCode" target="_blank"><?php echo $LANG['DARWIN']; ?></a>.
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<?php echo $LANG['NAME_COL'];?>
								</td>
								<td>
									<input type="text" name="collectionname" value="<?php echo ($collid?$collData["collectionname"]:'');?>" style="width:95%;" title="<?php echo $LANG['REQUIRED_FIELD']; ?>" />
								</td>
							</tr>
							<tr>
								<td>
									<?php echo $LANG['DES'];?><br/>
									<?php echo $LANG['MAX'];?>
								</td>
								<td>
									<textarea name="fulldescription" style="width:95%;height:90px;"><?php echo ($collid?$collData["fulldescription"]:'');?></textarea>
								</td>
							</tr>
							<tr>
								<td>
									<?php echo $LANG['PAG'];?>
								</td>
								<td>
									<input type="text" name="homepage" value="<?php echo ($collid?$collData["homepage"]:'');?>" style="width:90%;" />
								</td>
							</tr>
							<tr>
								<td>
								<?php echo $LANG['CONTA'];?>
									</td>
								<td>
									<input type="text" name="contact" value="<?php echo ($collid?$collData["contact"]:'');?>" style="width:90%;" />
								</td>
							</tr>
							<tr>
								<td>
									<?php echo $LANG['MAIL'];?>
								</td>
								<td>
									<input type="text" name="email" value="<?php echo ($collid?$collData["email"]:'');?>" style="width:90%;" />
								</td>
							</tr>
							<tr>
								<td>
									<?php echo $LANG['LATITUDE'];?>
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
									<?php echo $LANG['LONG'];?>
								</td>
								<td>
									<input id="lngdec" type="text" name="longitudedecimal" value="<?php echo ($collid?$collData["longitudedecimal"]:'');?>" />
								</td>
							</tr>
							<?php
							$fullCatArr = $collManager->getCategoryArr();
							if($fullCatArr){
								?>
								<tr>
									<td>
										<?php echo $LANG['CATE'];?>
									</td>
									<td>
										<select name="ccpk">
											<option value=""><?php echo $LANG['NO_CATE'];?></option>
											<option value="">-------------------------------------------</option>
											<?php
											$catArr = $collManager->getCollectionCategories();
											foreach($fullCatArr as $ccpk => $category){
												echo '<option value="'.$ccpk.'" '.($collid && array_key_exists($ccpk, $catArr)?'SELECTED':'').'>'.$category.'</option>';
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
									<?php echo $LANG['PERM_EDI'];?>
								</td>
								<td>
									<input type="checkbox" name="publicedits" value="1" <?php echo ($collData && $collData['publicedits']?'CHECKED':''); ?> />
									<a id="peditsinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFORMATION_ABOUT_PUBLIC_EDITS']; ?>">
										<img src="../../images/info.png" style="width:15px;" />
									</a>
									<div id="peditsinfodialog">
										<?php echo $LANG['LA'];?>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<?php echo $LANG['LICEN'];?>
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
												echo '<option value="'.$collData["rights"].'" SELECTED>'.$collData["rights"].' ['.$LANG['ORPHANED_TERM'].']</option>'."\n";
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
									<a id="rightsinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFORMATION_ABOUT_RIGHTS']; ?>">
										<img src="../../images/info.png" style="width:15px;" />
									</a>
									<div id="rightsinfodialog">
										<?php echo $LANG['AL_LEGAL'];?> <a href="http://darwincore.googlecode.com/svn/trunk/terms/index.htm#dcterms:license" target="_blank"><?php echo $LANG['DARWIN']; ?></a>.
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<?php echo $LANG['TIT'];?>
								</td>
								<td>
									<input type="text" name="rightsholder" value="<?php echo ($collid?$collData["rightsholder"]:'');?>" style="width:90%;" />
									<a id="rightsholderinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFORMATION_ABOUT_RIGHTS_HOLDER']; ?>">
										<img src="../../images/info.png" style="width:15px;" />
									</a>
									<div id="rightsholderinfodialog">
										<?php echo $LANG['THE_ORG'];?> <a href="http://darwincore.googlecode.com/svn/trunk/terms/index.htm#dcterms:rightsHolder" target="_blank"><?php echo $LANG['DARWIN']; ?></a>.
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<?php echo $LANG['DER'];?>
								</td>
								<td>
									<input type="text" name="accessrights" value="<?php echo ($collid?$collData["accessrights"]:'');?>" style="width:90%;" />
									<a id="accessrightsinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFORMATION_ABOUT_ACCESS_RIGHTS']; ?>">
										<img src="../../images/info.png" style="width:15px;" />
									</a>
									<div id="accessrightsinfodialog">
										<?php echo $LANG['INFOR'];?> <a href="http://darwincore.googlecode.com/svn/trunk/terms/index.htm#dcterms:accessRights" target="_blank"><?php echo $LANG['DARWIN']; ?></a>.
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<span title="<?php echo $LANG['SOURCE_OF_GLOBAL_UNIQUE_IDENTIFIER']; ?>"><?php echo $LANG['FUENT'];?></span>
								</td>
								<td>
									<select name="guidtarget" onchange="mtypeguidChanged(this.form)">
										<option value=""><?php echo $LANG['NO_DEF']; ?></option>
										<option value="">-------------------</option>
										<option value="occurrenceId" <?php echo ($collid && $collData["guidtarget"]=='occurrenceId'?'SELECTED':''); ?>><?php echo $LANG['OCURRENCE_ID_O']; ?></option>
										<option value="catalogNumber" <?php echo ($collid && $collData["guidtarget"]=='catalogNumber'?'SELECTED':''); ?>><?php echo $LANG['CAT_NUMBER']; ?></option>
										<option value="symbiotaUUID" <?php echo ($collid && $collData["guidtarget"]=='symbiotaUUID'?'SELECTED':''); ?>><?php echo $LANG['SYM']; ?></option>
									</select>
									<a id="guidinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFORMATION_ABOUT_GLOBAL_UNIQUE_IDENTIFIER']; ?>">
										<img src="../../images/info.png" style="width:15px;" />
									</a>
									<div id="guidinfodialog">
										<?php echo $LANG['OCURRENCE'];?>
										<a href="http://darwincore.googlecode.com/svn/trunk/terms/index.htm#occurrenceID" target="_blank"><?php echo $LANG['OCURRENCE_ID']; ?></a> <?php echo $LANG['FILED'];?>
									</div>
								</td>
							</tr>
                            <?php
                            if(isset($GBIF_USERNAME) && $GBIF_USERNAME && isset($GBIF_PASSWORD) && $GBIF_PASSWORD && isset($GBIF_ORG_KEY) && $GBIF_ORG_KEY) {
                                ?>
	                            <tr>
	                                <td>
	                                    <?php echo $LANG['PUBLISH'];?>
	                                </td>
	                                <td>
                                        <div>
                                            GBIF <input type="checkbox" name="publishToGbif" value="1"
                                                        onchange="checkGUIDSource(this.form);" <?php echo($publishGBIF ? 'CHECKED' : ''); ?> />
                                            <a id="pubagginfo" href="#" onclick="return false"
                                               title="<?php echo $LANG['MORE_INFORMATION_ABOUT_PUBLISHING_TO_AGGREGATORS']; ?>">
                                                <img src="../../images/info.png" style="width:15px;"/>
                                            </a>
                                        </div>
	                                    <!--
	                                    <div>
	                                        iDigBio <input type="checkbox" name="publishToIdigbio" value="1" onchange="checkGUIDSource(this.form);" <?php echo($publishIDIGBIO?'CHECKED':''); ?> />
	                                    </div>
	                                    <div id="pubagginfodialog">
	                                        Check boxes to make Darwin Core Archives published from this collection
	                                        available to iDigBio and/or GBIF (if activated in this portal).
	                                    </div>
	                                    -->
	                                </td>
	                            </tr>
                                <?php
                            }
                            ?>
							<tr>
								<td>
									<?php echo $LANG['URL'];?>
								</td>
								<td>
									<input type="text" name="individualurl" style="width:90%;" value="<?php echo ($collid?$collData["individualurl"]:'');?>" title="<?php echo $LANG['DYNAMIC_LINK_TO_SOURCE_DATABASE_INDIVIDUAL']; ?>" />
									<a id="sourceurlinfo" href="#" onclick="return false" title="<?php $LANG['MORE_INFORMATION_ABOUT_SOURCE_RECORDS_URL']; ?>">
										<img src="../../images/info.png" style="width:15px;" />
									</a>
									<div id="sourceurlinfodialog">
										<?php echo $LANG['ADDING_A_URL_TEMPLATE_HERE_WILL']; ?>
									</div>
								</td>
							</tr>
							<tr>
								<td>
									<?php echo $LANG['AGRE'];?>:
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
												<a href="#" onclick="toggle('targetdiv');return false;"><?php echo $LANG['ENTER_URL']; ?></a>
											</div>
										</div>
										<div class="targetdiv" style="<?php echo (($collid&&$collData["icon"])?'display:block;':'display:none;'); ?>margin-top:0px;">
											<div style="float:left;width:65%;">
												<input style="width:100%;margin-top:0px;" type='text' name='iconurl' id='iconurl' value="<?php echo ($collid?$collData["icon"]:'');?>" onchange="verifyIconURL(this.form);" />
											</div>
											<div style="margin-right:15px;text-decoration:underline;float:right;font-weight:bold;">
												<a href="#" onclick="toggle('targetdiv');return false;">
													<?php echo $LANG['UPLOAD'];?>
												</a>
											</div>
										</div>
									</div>
									<a id="iconinfo" href="#" onclick="return false" title="<?php echo $LANG['WHAT_IS_A_ICON']; ?>">
										<img src="../../images/info.png" style="width:15px;" />
									</a>
									<div id="iconinfodialog">
										<?php echo $LANG['UP_ICON'];?>
									</div>
								</td>
							</tr>
							<?php
							if($IS_ADMIN){
								?>
								<tr>
									<td>
										<?php echo $LANG['TIPO'];?>
									</td>
									<td>
										<select name="colltype">
											<option><?php echo $LANG['PRESERVED_SPECIMENS']; ?></option>
											<option <?php echo ($collid && $collData["colltype"]=='Observations'?'SELECTED':''); ?>><?php echo $LANG['OBSERVATIONS']; ?></option>
											<option <?php echo ($collid && $collData["colltype"]=='General Observations'?'SELECTED':''); ?>><?php echo $LANG['GENERAL_OBSERVATIONS']; ?></option>
										</select>
										<a id="colltypeinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFORMATION_ABOUT_COLLECTION_TYPE']; ?>">
											<img src="../../images/info.png" style="width:15px;" />
										</a>
										<div id="colltypeinfodialog">
											<?php echo $LANG['PRESER'];?>
										</div>
									</td>
								</tr>
								<tr>
									<td>
										<?php echo $LANG['ADMIN'];?>:
									</td>
									<td>
										<select name="managementtype" onchange="mtypeguidChanged(this.form)">
											<option>Snapshot</option>
											<option <?php echo ($collid && $collData["managementtype"]=='Live Data'?'SELECTED':''); ?>><?php echo $LANG['LIVE_DATA']; ?></option>
											<option <?php echo ($collid && $collData["managementtype"]=='Aggregate'?'SELECTED':''); ?>><?php echo $LANG['AGGREGATE']; ?></option>
										</select>
										<a id="managementinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFORMATION_ABOUT_MANAGEMENT_TYPE']; ?>">
											<img src="../../images/info.png" style="width:15px;" />
										</a>
										<div id="managementinfodialog">
											<?php echo $LANG['USE'];?>
										</div>
									</td>
								</tr>
								<tr>
									<td>
										<?php echo $LANG['ORDER'];?>
									</td>
									<td>
										<input type="text" name="sortseq" value="<?php echo ($collid?$collData["sortseq"]:'');?>" />
										<a id="sortinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFORMATION_ABOUT_SORTING']; ?>">
											<img src="../../images/info.png" style="width:15px;" />
										</a>
										<div id="sortinfodialog">
											<?php echo $LANG['LEAVE'];?>
										</div>
									</td>
								</tr>
								<?php
							}
							if($collid){
								?>
								<tr>
									<td>
										<?php echo $LANG['ID_GLOBAL'];?>
									</td>
									<td>
										<?php
										echo $collData["guid"];
										?>
										<a id="collectionguidinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFORMATION']; ?>">
											<img src="../../images/info.png" style="width:15px;" />
										</a>
										<div id="collectionguidinfodialog">
											<?php echo $LANG['GLOBAL_UNIQUE'];?>
										</div>
									</td>
								</tr>
								<tr>
									<td>
										<?php echo $LANG['SECURITY'];?>
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
										<?php echo $LANG['ID_GLO'];?>
									</td>
									<td>
										<input type="text" name="collectionguid" value="" style="width:90%;" />
										<a id="collectionguidinfo" href="#" onclick="return false" title="<?php echo $LANG['MORE_INFORMATION']; ?>">
											<img src="../../images/info.png" style="width:15px;" />
										</a>
										<div id="collectionguidinfodialog">
											<?php echo $LANG['IDENTY'];?>
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
											<input type="hidden" name="action" value="Save Edits" />
											<input type="submit" value="<?php echo $LANG['SAVE_EDITS']; ?>" />
											<?php
										}
										else{
											?>
											<input type="hidden" name="action" value="Create New Collection" />
											<input type="submit" value="<?php echo $LANG['CREATE_NEW_COLLECTION']; ?>" />
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
				<fieldset style="background-color:#FFFFFF;">
					<legend><b><?php echo $LANG['INSTI'];?></b></legend>
					<?php
					if($instArr = $collManager->getAddress()){
						?>
						<div style="margin:25px;">
							<?php
							echo '<div>';
							echo $instArr['institutionname'].($instArr['institutioncode']?' ('.$instArr['institutioncode'].')':'');
							?>
							<a href="../admin/institutioneditor.php?emode=1&targetcollid=<?php echo $collid.'&iid='.$instArr['iid']; ?>" title="<?php echo $LANG['EDIT_INSTITUTION_ADDRESS']; ?>">
								<img src="../../images/edit.png" style="width:14px;" />
							</a>
							<a href="collmetadata.php?collid=<?php echo $collid.'&removeiid='.$instArr['iid']; ?>" title="<?php echo $LANG['UNLINK_INSTITUTION_ADDRESS']; ?>">
								<img src="../../images/drop.png" style="width:14px;" />
							</a>
							<?php
							echo '</div>';
							if($instArr['address1']) echo '<div>'.$instArr['address1'].'</div>';
							if($instArr['address2']) echo '<div>'.$instArr['address2'].'</div>';
							if($instArr['city'] || $instArr['stateprovince']) echo '<div>'.$instArr['city'].', '.$instArr['stateprovince'].' '.$instArr['postalcode'].'</div>';
							if($instArr['country']) echo '<div>'.$instArr['country'].'</div>';
							if($instArr['phone']) echo '<div>'.$instArr['phone'].'</div>';
							if($instArr['contact']) echo '<div>'.$instArr['contact'].'</div>';
							if($instArr['email']) echo '<div>'.$instArr['email'].'</div>';
							if($instArr['url']) echo '<div><a href="'.$instArr['url'].'">'.$instArr['url'].'</a></div>';
							if($instArr['notes']) echo '<div>'.$instArr['notes'].'</div>';
							?>
						</div>
						<?php
					}
					else{
						//Link new institution
						?>
						<div style="margin:40px;"><b><?php echo $LANG['NO_EXIS'];?></b></div>
						<div style="margin:20px;">
							<form name="addaddressform" action="collmetadata.php" method="post" onsubmit="return verifyAddAddressForm(this)">
								<select name="iid" style="width:425px;">
									<option value=""><?php echo $LANG['SELECT_INSTITUTION_ADDRESS']; ?></option>
									<option value="">------------------------------------</option>
									<?php
									$addrArr = $collManager->getInstitutionArr();
									foreach($addrArr as $iid => $name){
										echo '<option value="'.$iid.'">'.$name.'</option>';
									}
									?>
								</select>
								<input name="collid" type="hidden" value="<?php echo $collid; ?>" />
								<input type=hidden name="action" value="Link Address" />
								<input type="submit" value="<?php echo $LANG['LINK_ADDRESSS']; ?>" />|
							</form>
							<div style="margin:15px;">
								<a href="../admin/institutioneditor.php?emode=1&targetcollid=<?php echo $collid; ?>" title="<?php echo $LANG['ADD_A_NEW_ADDRESS_NOT_ON_THE_LIST']; ?>">
									<b><?php echo $LANG['ADD_IN'];?></b>
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
