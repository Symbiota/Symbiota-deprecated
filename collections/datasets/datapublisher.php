<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/DwcArchiverPublisher.php');
include_once($SERVER_ROOT.'/classes/OccurrenceCollectionProfile.php');
include_once($SERVER_ROOT.'/content/lang/collections/datasets/datapublisher.'.$LANG_TAG.'.php');

header('Content-Type: text/html; charset=' .$CHARSET);

$collId = array_key_exists('collid',$_REQUEST)?$_REQUEST['collid']:0;
$emode = array_key_exists('emode',$_REQUEST)?$_REQUEST['emode']:0;
$action = array_key_exists('formsubmit',$_REQUEST)?$_REQUEST['formsubmit']:'';
$cSet = array_key_exists('cset',$_REQUEST)?$_REQUEST['cset']:'';
$schema = array_key_exists('schema',$_REQUEST)?$_REQUEST['schema']:1;

$dwcaManager = new DwcArchiverPublisher();
$collManager = new OccurrenceCollectionProfile();

$includeDets = 1;
$includeImgs = 1;
$redactLocalities = 1;
$collPubArr = array();
$publishGBIF = false;
$publishIDIGBIO = false;
$installationKey = '';
$datasetKey = '';
$endpointKey = '';
$idigbioKey = '';
if($collId && isset($GBIF_USERNAME) && $GBIF_USERNAME && isset($GBIF_PASSWORD) && $GBIF_PASSWORD && isset($GBIF_ORG_KEY) && $GBIF_ORG_KEY){
    $collPubArr = $collManager->getCollPubArr($collId);
    if($collPubArr[$collId]['publishToGbif']){
        $publishGBIF = true;
    }
    if($collPubArr[$collId]['publishToIdigbio']){
        $publishIDIGBIO = true;
    }
}
if($action){
	if($action == 'Save Key'){
		$collManager->setAggKeys($_POST['aggKeysStr']);
        $collManager->updateAggKeys($collId);
	}
	else{
		if (!array_key_exists('dets', $_POST)) {
			$includeDets = 0;
			$dwcaManager->setIncludeDets(0);
		}
		if (!array_key_exists('imgs', $_POST)) {
			$includeImgs = 0;
			$dwcaManager->setIncludeImgs(0);
		}
		if (!array_key_exists('redact', $_POST)) {
			$redactLocalities = 0;
			$dwcaManager->setRedactLocalities(0);
		}
		$dwcaManager->setTargetPath($SERVER_ROOT . (substr($SERVER_ROOT, -1) == '/' ? '' : '/') . 'content/dwca/');
	}
}
if(isset($GBIF_USERNAME) && isset($GBIF_PASSWORD) && isset($GBIF_ORG_KEY)){
	$installationKey = $collManager->getInstallationKey();
    $datasetKey = $collManager->getDatasetKey();
    $endpointKey = $collManager->getEndpointKey();
    $idigbioKey = $collManager->getIdigbioKey();
	if($publishIDIGBIO && !$idigbioKey){
        $idigbioKey = $collManager->findIdigbioKey($collPubArr[$collId]['collectionguid']);
        if($idigbioKey){
            $collManager->updateAggKeys($collId);
        }
    }
}

$isEditor = 0;
if($IS_ADMIN || (array_key_exists('CollAdmin',$USER_RIGHTS) && in_array($collId,$USER_RIGHTS['CollAdmin']))){
	$isEditor = 1;
}

$collArr = array();
if($collId){
	$dwcaManager->setCollArr($collId);
	$collArr = $dwcaManager->getCollArr($collId);
}
if($isEditor){
	if(array_key_exists('colliddel',$_POST)){
		$dwcaManager->deleteArchive($_POST['colliddel']);
	}
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title><?php echo $LANG['DCA']; ?></title>
	<link href="../../css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet">
	<link href="../../css/jquery-ui.css" type="text/css" rel="Stylesheet" />
	<style type="text/css">
		.nowrap { white-space: nowrap; }
	</style>
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script type="text/javascript" src="../../js/symb/collections.gbifpublisher.js"></script>
	<script type="text/javascript">
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
			return false;
		}

		function verifyDwcaForm(f){

			return true;
		}

    	function verifyDwcaAdminForm(f){
			var dbElements = document.getElementsByName("coll[]");
			for(i = 0; i < dbElements.length; i++){
				var dbElement = dbElements[i];
				if(dbElement.checked) return true;
			}
		   	alert("<?php echo $LANG['PLEASE_CHOOSE_AT_LEAST_ONE_COLLECTION']; ?>");
			return false;
    	}

		function checkAllColl(cb){
			var boxesChecked = true;
			if(!cb.checked){
				boxesChecked = false;
			}
			var cName = cb.className;
			var dbElements = document.getElementsByName("coll[]");
			for(i = 0; i < dbElements.length; i++){
				var dbElement = dbElements[i];
				if(dbElement.className == cName){
					if(dbElement.disabled == false) dbElement.checked = boxesChecked;
				}
				else{
					dbElement.checked = false;
				}
			}
		}
    </script>
</head>
<body>
<?php
$displayLeftMenu = (isset($collections_datasets_datapublisherMenu)?$collections_datasets_datapublisherMenu: 'true');
include($SERVER_ROOT. '/header.php');
?>
<div class='navpath'>
	<a href="../../index.php"><?php echo $LANG['HOME']; ?></a> &gt;&gt;
	<?php
	if($collId){
		?>
		<a href="../misc/collprofiles.php?collid=<?php echo $collId; ?>&emode=1"><?php echo $LANG['COLLECTION_MANAGEMENT']; ?></a> &gt;&gt;
		<?php
	}
	else{
		?>
		<a href="../../sitemap.php"><?php echo $LANG['SITEMAP']; ?></a> &gt;&gt;
		<?php
	}
	?>
	<b><?php echo $LANG['DARWIN_CORE_ARCHIVE_PUBLISHER']; ?></b>
</div>
<!-- This is inner text! -->
<div id="innertext">
	<?php
	if(!$collId && $IS_ADMIN){
		?>
		<div style="float:right;">
			<a href="#" title="<?php echo $LANG['DISPLAY_PUBLISHING_CONTROL_PANEL']; ?>" onclick="toggle('dwcaadmindiv')">
				<img style="border:0;width:12px;" src="../../images/edit.png" />
			</a>
		</div>
		<?php
	}
	?>
	<h1><?php echo $LANG['DCA_PUB'];?></h1>
	<?php
	if($collId){
		echo '<div style="font-weight:bold;font-size:120%;">'.$collArr['collname'].'</div>';
		?>
		<div style="margin:10px;">
			<?php echo $LANG['USE_CONTROL'];?>
			<a href="http://rs.tdwg.org/dwc/terms/guides/text/index.htm"><?php echo $LANG['DCA'].'</a>'.$LANG['FILE']; ?>
			<?php echo $LANG['A_DWC_A_FILE_IS_A_SINGLE_COMPRESSED_ZIP']; ?>
		</div>
		<?php
	}
	else{
		?>
    <div style="margin:10px;">
      <?php echo $LANG['FOLL_DOWNLOAD'];?>

			<a href="http://rs.tdwg.org/dwc/terms/guides/text/index.htm"><?php echo $LANG['DCA'];?></a> <?php echo $LANG['FILE'];?>
      <?php echo $LANG['A_DWC_ZIP'];?>
     <a href="http://rs.tdwg.org/dwc/terms/index.htm"><?php echo $LANG['DC'];?></a>
			<?php echo $LANG['IDENT_IMAGE'];?>

		</div>
		<div style="margin:10px;">
			<h3><?php echo $LANG['DATA_USAGE_POL'];?></h3>
			<?php echo $LANG['USE_DATABASE'];?>
			<a href="../../misc/usagepolicy.php"> <?php echo $LANG['DATA_USAGE_POL'];?></a>.
			<?php echo $LANG['LOC_DETAILS'];?>
		</div>
		<?php
	}
	?>
	<div style="margin:20px;">
		<b></b>
		<b><?php echo $LANG['RSS_FEED']; ?>:</b>
		<?php
		$urlPrefix = $dwcaManager->getServerDomain().$CLIENT_ROOT.(substr($CLIENT_ROOT,-1)=='/'?'':'/');
		if(file_exists('../../webservices/dwc/rss.xml')){
			$feedLink = $urlPrefix.'webservices/dwc/rss.xml';
			echo '<a href="'.$feedLink.'" target="_blank">'.$feedLink.'</a>';
		}
		else{
			echo '--'.$LANG['FEED_NOT_PUBLISHED_FOR_ANY'].'--';
		}
		?>
	</div>
	<?php
	if($collId){
		if($action == 'Create/Refresh Darwin Core Archive'){
            $dwcaManager->setCollID($collId);
		    echo '<ul>';
			$dwcaManager->setVerboseMode(3);
			$dwcaManager->setLimitToGuids(true);
			$dwcaManager->createDwcArchive();
			$dwcaManager->writeRssFile();
			echo '</ul>';
			if($publishGBIF && $endpointKey){
				$collManager->triggerGBIFCrawl($datasetKey);
			}
		}
		if($dwcaArr = $dwcaManager->getDwcaItems($collId)){
			$dArr = current($dwcaArr);
			$dwcUri = ($dArr['collid'] == $collId?$dArr['link']:'');
			?>
			<div style="margin:10px;">
				<div>
					<b><?php echo $LANG['TITLE']; ?>:</b> <?php echo $dArr['title']; ?>
					<form action="datapublisher.php" method="post" style="display:inline;" onsubmit="return window.confirm('<?php echo $LANG['ARE_YOU_SURE_YOU_WANT_TO_DELETE']; ?>');">
						<input type="hidden" name="colliddel" value="<?php echo $dArr['collid']; ?>">
						<input type="hidden" name="collid" value="<?php echo $dArr['collid']; ?>">
						<input type="image" src="../../images/del.png" name="action" value="DeleteCollid" title="<?php echo $LANG['DELETE_ARCHIVE']; ?>" style="width:15px;" />
					</form>
				</div>
				<div><b><?php echo $LANG['DESCRIPTION']; ?>:</b> <?php echo $dArr['description']; ?></div>
				<?php
				$emlLink = $urlPrefix.'collections/datasets/emlhandler.php?collid='.$collId;
				?>
				<div><b>EML:</b> <a href="<?php echo $emlLink; ?>"><?php echo $emlLink; ?></a></div>
				<div><b>DwC-<?php echo $LANG['ARCHIVE_FILE']; ?>:</b> <a href="<?php echo $dArr['link']; ?>"><?php echo $dArr['link']; ?></a></div>
				<div><b><?php echo $LANG['PUBLICATION_DATE']; ?>:</b> <?php echo $dArr['pubDate']; ?></div>
			</div>
			<?php
		}
		else{
			echo '<div style="margin:20px;font-weight:bold;color:orange;">'.$LANG['NO_DATA_ARCHIVES_HAVE_BEEN_PUBLISHED'].'</div>';
		}
		?>







































		<fieldset style="margin:15px;padding:15px;">
			<legend><b><?php echo $LANG['PUBLISHING_INFORMATION']; ?></b></legend>
			<?php
			//Data integrity checks
			$blockSubmitMsg = '';
			$recFlagArr = $dwcaManager->verifyCollRecords($collId);
			if($collArr['guidtarget']){
				echo '<div style="margin:10px;"><b>'.$LANG['GUID_SOURCE'].':</b> '.$collArr['guidtarget'].'</div>';
				if($recFlagArr['nullGUIDs']){
					echo '<div style="margin:10px;">';
					if($collArr['guidtarget'] == 'occurrenceId'){
						echo '<b>'.$LANG['RECORD_MISSING'].' <a href="" target="_blank">'.$LANG['OCCURRENCE_ID_GUID'].'</a>:</b> '.$recFlagArr['nullGUIDs'];
						echo ' <span style="color:red;margin-left:15px;">'.$LANG['THESE_RECORDS_WILL_NOT_BE'].'</span> ';
					}
					elseif($collArr['guidtarget'] == 'catalogNumber'){
						echo '<b>'.$LANG['RECORDS_MISSING_SYMBIOTA_GUIDs'].':</b> '.$recFlagArr['nullGUIDs'];
						echo ' <span style="color:red;margin-left:15px;">'.$LANG['THESE_RECORDS_WILL_NOT_BE'].'</span> ';
					}
					else{
						echo $LANG['RECORDS_MISSING_SYMBIOTA_GUIDs'].': '.$recFlagArr['nullGUIDs'].'<br/>';
						echo $LANG['PLEASE_GO_TO_THE'].' <a href="../admin/guidmapper.php?collid='.$collId.'">'.$LANG['COLLECTION_GUID_MAPPER'].'</a> '.$LANG['TO_ASSIGN_SYMBIOTA'].'.';
					}
					echo '</div>';
				}
				if($collArr['dwcaurl']){
					$serverName = $_SERVER["SERVER_NAME"];
					if(substr($serverName, 0, 4) == 'www.') $serverName = substr($serverName, 4);
					if(!strpos($collArr['dwcaurl'],$serverName)){
						$baseUrl = substr($collArr['dwcaurl'],0,strpos($collArr['dwcaurl'],'/content')).'/collections/datasets/datapublisher.php';
						$blockSubmitMsg = $LANG['ALREADY_PUBLISHED_ON_SISTER_PORTAL'].' (<a href="'.$baseUrl.'" target="_blank">'.substr($baseUrl,0,strpos($baseUrl,'/',10)).'</a>) ';
					}
				}
			}
			else{
				echo '<div style="margin:10px;font-weight:bold;color:red;">'.$LANG['THE_GUID_SOURCE_HAS_NOT_BEEN_SET'].' <a href="../misc/collmetadata.php?collid='.$collId.'">'.$LANG['EDIT_M'].'.</div>';
				$blockSubmitMsg = 'Archive cannot be published until occurrenceID GUID source is set<br/>';
			}
			if($recFlagArr['nullBasisRec']){
				echo '<div style="margin:10px;font-weight:bold;color:red;">'.$LANG['THERE_ARE'].' '.$recFlagArr['nullBasisRec'].' '.$LANG['RECORDS_MISSING_BASIS_OF_RECORD'].' <a href="../editor/occurrencetabledisplay.php?q_recordedby=&q_recordnumber=&q_eventdate=&q_catalognumber=&q_othercatalognumbers=&q_observeruid=&q_recordenteredby=&q_dateentered=&q_datelastmodified=&q_processingstatus=&q_customfield1=basisOfRecord&q_customtype1=NULL&q_customvalue1=Something&q_customfield2=&q_customtype2=EQUALS&q_customvalue2=&q_customfield3=&q_customtype3=EQUALS&q_customvalue3=&collid='.$collId.'&csmode=0&occid=&occindex=0&orderby=&orderbydir=ASC">'.$LANG['EDIT_EXISTING_OCCURRENCE_RECORDS'].'.</div>';
			}


























			if(($publishGBIF || $publishIDIGBIO) && $dwcUri && isset($GBIF_USERNAME) && isset($GBIF_PASSWORD) && isset($GBIF_ORG_KEY)){
				if($publishGBIF && !$datasetKey) {
					?>
					<div style="margin:10px;">
						<?php echo $LANG['YOU_HAVE_SELECTED_TO_HAVE_THIS_COLLECTIONS']; ?>
						<a href="https://www.gbif.org/become-a-publisher" target="_blank"><?php echo $LANG['GBIF_ENDORSEMENT_REQUEST_PAGE']; ?></a>
						<?php echo $LANG['TO_REGISTER_YOUR_COLLECTION']; ?><a href="mailto:helpdesk@gbif.org">helpdesk@gbif.org</a><?php echo $LANG['AND_REQUEST_THAT_THE_USER']; ?> <b><?php echo $GBIF_USERNAME; ?></b> <?php echo $LANG['HAS_PERMISSIONS_TO_CREATE_AND_EDIT']; ?>
						<form style="margin-top:10px;" name="gbifpubform" action="datapublisher.php" method="post" onsubmit="return processGbifOrgKey(this.form);">
							<?php echo $LANG['GBIF_KEY']; ?> <input type="text" name="gbifOrgKey" id="gbifOrgKey" value="" style="width:250px;"/>
							<input type="hidden" name="collid" value="<?php echo $collId; ?>"/>
							<input type="hidden" name="portalname" id="portalname" value='<?php echo $DEFAULT_TITLE; ?>'/>
							<input type="hidden" name="collname" id="collname" value='<?php echo $collArr['collname']; ?>'/>
							<input type="hidden" name="aggKeysStr" id="aggKeysStr" value=''/>
							<input type="hidden" id="gbifInstOrgKey" value='<?php echo $GBIF_ORG_KEY; ?>'/>
							<input type="hidden" id="gbifInstKey" value='<?php echo $installationKey; ?>'/>
							<input type="hidden" id="gbifDataKey" value=''/>
							<input type="hidden" id="gbifEndKey" value=''/>
							<input type="hidden" name="dwcUri" id="dwcUri" value="<?php echo $dwcUri; ?>"/>
							<input type="hidden" name="formsubmit" value="Save Key" />
							<input type="submit" value="<?php echo $LANG['SAVE_KEY']; ?>" />
						</form>
					</div>
					<?php
				}
				if($publishGBIF && $datasetKey){
                    $dataUrl = 'http://www.gbif.org/dataset/'.$datasetKey;
                    ?>
                    <div style="margin:10px;">
                        <div><b><?php echo $LANG['GBIF_DATASET_PAGE']; ?>:</b> <a href="<?php echo $dataUrl; ?>" target="_blank"><?php echo $dataUrl; ?></a></div>
                    </div>
                    <?php
                }
                if($publishIDIGBIO && $idigbioKey){
                    $dataUrl = 'https://www.idigbio.org/portal/recordsets/'.$idigbioKey;
                    ?>
                    <div style="margin:10px;">
                        <div><b><?php echo $LANG['IDIGBIO_DATASET_PAGE']; ?>:</b> <a href="<?php echo $dataUrl; ?>" target="_blank"><?php echo $dataUrl; ?></a></div>
                    </div>
                    <?php
                }
			}
			?>
		</fieldset>
		<fieldset style="padding:15px;margin:15px;">
			<legend><b><?php echo $LANG['PUBLISH_REFRESH_DWC_A_FILE']; ?></b></legend>
			<form name="dwcaform" action="datapublisher.php" method="post" onsubmit="return verifyDwcaForm(this)">
				<div>
					<input type="checkbox" name="dets" value="1" <?php echo ($includeDets?'CHECKED':''); ?> /> <?php echo $LANG['INCLUDE_DETERMINATION_HISTORY']; ?><br/>
					<input type="checkbox" name="imgs" value="1" <?php echo ($includeImgs?'CHECKED':''); ?> /> <?php echo $LANG['INCLUDE_IMAGE_URLS']; ?><br/>
					<input type="checkbox" name="redact" value="1" <?php echo ($redactLocalities?'CHECKED':''); ?> /> <?php echo $LANG['REACT_SENSITIVE_LOCALITIES']; ?><br/>
				</div>
				<div style="clear:both;margin:10px;">
					<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
					<input type="hidden" name="formsubmit" value="Create/Refresh Darwin Core Archive" />
					<input type="submit" value="<?php echo $LANG['CREATE_REFRESH_DARWIN_CORE_ARCHIVED']; ?>" <?php if($blockSubmitMsg) echo 'disabled'; ?> />
					<?php
					if($blockSubmitMsg){
						echo '<span style="color:red;margin-left:10px;">'.$blockSubmitMsg.'</span>';
					}
					?>
				</div>
				<?php
				if($collArr['managementtype'] != 'Live Data' || $collArr['guidtarget'] != 'symbiotaUUID'){
					?>
					<div style="margin:10px;font-weight:bold">
						<?php echo $LANG['NOTE_ALL_RECORDS_LACKING_OCCURENCE_ID']; ?>
					</div>
					<?php
				}
				?>
			</form>
		</fieldset>
		<?php
	}
	else{
		$catID = (isset($DEFAULTCATID)?$DEFAULTCATID:0);
		$catTitle = $dwcaManager->getCategoryName($catID);
		if($IS_ADMIN){
			if($action == 'Create/Refresh Darwin Core Archive(s)'){
				echo '<ul>';
				$dwcaManager->setVerboseMode(3);
				$dwcaManager->setLimitToGuids(true);
				$dwcaManager->batchCreateDwca($_POST['coll']);
				echo '</ul>';
				if($publishGBIF){
					$collManager->batchTriggerGBIFCrawl($_POST['coll']);
				}
			}
			?>
			<div id="dwcaadmindiv" style="margin:10px;display:<?php echo ($emode?'block':'none'); ?>;" >
				<form name="dwcaadminform" action="datapublisher.php" method="post" onsubmit="return verifyDwcaAdminForm(this)">
					<fieldset style="padding:15px;">
						<legend><b> <?php echo $LANG['PUBLISH_REFRESH']." ".$catTitle ." ". $LANG['DWC_A_FILES']; ?> </b></legend>
						<div style="margin:10px;">
							<input name="collcheckall" type="checkbox" value="" onclick="checkAllColl(this)" /> <?php echo $LANG['SELECT_DESELECT_ALL']; ?><br/><br/>
							<?php
							$collList = $dwcaManager->getCollectionList($catID);
							foreach($collList as $k => $v){
								$errMsg = '';
								if(!$v['guid']){
									$errMsg = $LANG['MISSING_GUID_SOURCE'];
								}
								elseif($v['url'] && !strpos($v['url'],str_replace('www.', '', $_SERVER["SERVER_NAME"]))){
									$baseUrl = substr($v['url'],0,strpos($v['url'],'/content')).'/collections/datasets/datapublisher.php';
									$errMsg = $LANG['ALREADY_PUBLISHED_ON_DIFFERENT_DOMAIN'].' (<a href="'.$baseUrl.'" target="_blank">'.substr($baseUrl,0,strpos($baseUrl,'/',10)).'</a>)';
								}
								echo '<input name="coll[]" type="checkbox" value="'.$k.'" '.($errMsg?'DISABLED':'').' />';
								echo '<a href="../misc/collprofiles.php?collid='.$k.'" target="_blank">'.$v['name'].'</a>';
								if($errMsg) echo '<span style="color:red;margin-left:15px;">'.$errMsg.'</span>';
								echo '<br/>';
							}
							?>
						</div>
						<fieldset style="margin:10px;padding:15px;">
							<legend><b><?php echo $LANG['OPTIONS']; ?></b></legend>
							<input type="checkbox" name="dets" value="1" <?php echo ($includeDets?'CHECKED':''); ?> /> <?php echo $LANG['INCLUDE_DETERMINATION_HISTORY']; ?><br/>
							<input type="checkbox" name="imgs" value="1" <?php echo ($includeImgs?'CHECKED':''); ?> /> <?php echo $LANG['INCLUDE_IMAGE_URLS']; ?><br/>
							<input type="checkbox" name="redact" value="1" <?php echo ($redactLocalities?'CHECKED':''); ?> /> <?php echo $LANG['REACT_SENSITIVE_LOCALITIES']; ?><br/>
						</fieldset>
						<div style="clear:both;margin:20px;">
							<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
							<input type="submit" name="formsubmit" value="Create/Refresh Darwin Core Archive(s)" />
							<input type="submit" value="<?php echo $LANG['CREATE_REFRESH_DARWIN_CORE_ARCHIVED']; ?>(s)" />
						</div>
					</fieldset>
				</form>
			</div>
			<?php
		}
		if($dwcaArr = $dwcaManager->getDwcaItems()){
			if($catTitle) echo '<div style="font-weight:bold;font-size:140%;margin:50px 0px 15px 0px;">'.$catTitle.' DwC-Archive Files</div>';
			?>
			<table class="styledtable" style="font-family:Arial;font-size:12px;margin:10px;">
				<tr><th><?php echo $LANG['CODE']; ?></th><th><?php echo $LANG['COLLECTION_NAME']; ?></th><th><?php echo $LANG['DWC_ARCHIVE']; ?></th><th><?php echo $LANG['METADATA']; ?></th><th><?php echo $LANG['PUB_DATE']; ?></th></tr>
				<?php
				foreach($dwcaArr as $k => $v){
					?>
					<tr>
						<td><?php echo '<a href="../misc/collprofiles.php?collid='.$v['collid'].'">'.str_replace(' DwC-Archive','',$v['title']).'</a>'; ?></td>
						<td><?php echo substr($v['description'],24); ?></td>
						<td class="nowrap">
							<?php
							echo '<a href="'.$v['link'].'">DwC-A ('.$dwcaManager->humanFileSize($v['link']).')</a>';
							if($IS_ADMIN){
								?>
								<form action="datapublisher.php" method="post" style="display:inline;" onsubmit="return window.confirm('<?php echo $LANG['ARE_YOU_SURE_YOU_WANT_TO_DELETE']; ?>');">
									<input type="hidden" name="colliddel" value="<?php echo $v['collid']; ?>">
									<input type="image" src="../../images/del.png" name="action" value="DeleteCollid" title="<?php echo $LANG['DELETE_ARCHIVE']; ?>" style="width:15px;" />
								</form>
								<?php
							}
							?>
						</td>
						<td>
							<?php
							echo '<a href="'.$urlPrefix.'collections/datasets/emlhandler.php?collid='.$v['collid'].'">EML</a>';
							?>
						</td>
						<td class="nowrap"><?php echo date('Y-m-d', strtotime($v['pubDate'])); ?></td>
					</tr>
					<?php
				}
				?>
			</table>
			<?php
		}
		else{
			echo '<div style="margin:10px;font-weight:bold;">'.$LANG['THERE_ARE_NO_PUBLISHABLE_COLLECTION'].'</div>';
		}
		if($catID){
			if($addDwca = $dwcaManager->getAdditionalDWCA($catID)){
				echo '<div style="font-weight:bold;font-size:140%;margin:50px 0px 15px 0px;">'.$LANG['ADDITIONAL_DATA_SOURCES_WITHIN_THE_PORTAL_NETWORK'].'</div>';
				echo '<ul>';
				foreach($addDwca as $domanName => $domainArr){
					echo '<li><a href="'.$domainArr['url'].'/collections/datasets/datapublisher.php'.'" target="_blank">http://'.$domanName.'</a> - '.$domainArr['cnt'].' '.$LANG['ARCHIVES'].'</li>';
				}
				echo '</ul>';
			}
		}
	}
	?>
</div>
<?php
include($SERVER_ROOT.'/footer.php');
?>
</body>
</html>
