<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/DwcArchiverOccurrence.php');
header("Content-Type: text/html; charset=".$charset);

$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$emode = array_key_exists("emode",$_REQUEST)?$_REQUEST["emode"]:0;
$action = array_key_exists("formsubmit",$_REQUEST)?$_REQUEST["formsubmit"]:'';
$cSet = array_key_exists("cset",$_REQUEST)?$_REQUEST["cset"]:'';
$schema = array_key_exists("schema",$_REQUEST)?$_REQUEST["schema"]:1;

$dwcaManager = new DwcArchiverOccurrence();

$includeDets = 1;
$includeImgs = 1;
$redactLocalities = 1;
if($action){
	if(!array_key_exists('dets',$_POST)){
		$includeDets = 0;
		$dwcaManager->setIncludeDets(0);
	}
	if(!array_key_exists('imgs',$_POST)){
		$includeImgs = 0;
		$dwcaManager->setIncludeImgs(0);
	}
	if(!array_key_exists('redact',$_POST)){
		$redactLocalities = 0;
		$dwcaManager->setRedactLocalities(0);
	}
	$dwcaManager->setTargetPath($serverRoot.(substr($serverRoot,-1)=='/'?'':'/').'collections/datasets/dwc/');
}

$isEditor = 0;
if($isAdmin || array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])){
	$isEditor = 1;
}

$collArr = array();
if($collId){
	$dwcaManager->setCollArr($collId);
	$collArr = $dwcaManager->getCollArr();
}
if($isEditor){
	if(array_key_exists('colliddel',$_POST)){
		$dwcaManager->deleteArchive($_POST['colliddel']);
	}
}
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title>Darwin Core Archiver Publisher</title>
	<link href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
    <link href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet">
	<style type="text/css">
		.nowrap { white-space: nowrap; }
	</style>
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
		   	alert("Please choose at least one collection!");
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
					dbElement.checked = boxesChecked;
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
$displayLeftMenu = (isset($collections_datasets_datapublisherMenu)?$collections_datasets_datapublisherMenu:"true");
include($serverRoot."/header.php");
?>
<div class='navpath'>
	<a href="../../index.php">Home</a> &gt;&gt;
	<?php 
	if($collId){
		?>
		<a href="../misc/collprofiles.php?collid=<?php echo $collId; ?>&emode=1">Collection Management</a> &gt;&gt;
		<?php 
	}
	else{
		?>
		<a href="../../sitemap.php">Sitemap</a> &gt;&gt;
		<?php 
	}
	?>
	<b>Darwin Core Archive Publisher</b>
</div>
<!-- This is inner text! -->
<div id="innertext">
	<?php 
	if(!$collId && $isAdmin){
		?>
		<div style="float:right;">
			<a href="#" title="Display Publishing Control Panel" onclick="toggle('dwcaadmindiv')">
				<img style="border:0px;width:12px;" src="../../images/edit.png" />
			</a>
		</div>
		<?php
	} 
	?>
	<h1>Darwin Core Archive Publishing</h1>
	
	<?php 
	if($collId){
		echo '<div style="font-weight:bold;font-size:120%;">'.$collArr[$collId]['collname'].'</div>';
		?>
		<div style="margin:10px;">
			Use the controls below to publically publish occurrence data within this collection as a 
			<a href="http://rs.tdwg.org/dwc/terms/guides/text/index.htm">Darwin Core Archive (DWCA)</a> file.
			A DWCA file is a single compressed ZIP file that contains one to several data files along with a meta.xml 
			document that describes the content. 
			The occurrence data file required, but identifications (determinations) and images data are optional. 
			Fields within the occurrences.csv file are defined by the <a href="http://rs.tdwg.org/dwc/terms/index.htm">Darwin Core</a> 
			exchange standard. 
		</div>
		<?php 
	}
	else{
		?>
		<div style="margin:10px;">
			The following downloads are occurrence data packages from collections 
			that have chosen to publish their complete dataset as a
			<a href="http://rs.tdwg.org/dwc/terms/guides/text/index.htm">Darwin Core Archive (DWCA)</a> file.
			A DWCA file is a single compressed ZIP file that contains one to several data files along with a meta.xml 
			document that describes the content. 
			The archives below contain three comma separated (CSV) files containing occurrences, identifications (determinations), and images data. 
			Fields within the occurrences.csv file are defined by the <a href="http://rs.tdwg.org/dwc/terms/index.htm">Darwin Core</a> 
			exchange standard. The identifications and images files follow the DwC extensions for those data types. 
		</div>
		<div style="margin:10px;">
			<h3>Data Usage Policy:</h3>
			Use of these datasets requires agreement with the terms and conditions in our 
			<a href="../../misc/usagepolicy.php">Data Usage Policy</a>.
			Locality details for rare, threatened, or sensitive records have been redacted from these data files. 
			One must contact the collections directly to obtain access to sensitive locality data.
		</div>
		<?php
	} 
	?>
	<div style="margin:20px;">
		RSS Feed: 
		<?php 
		$urlPrefix = 'http://'.$_SERVER["SERVER_NAME"];
		if($_SERVER["SERVER_PORT"] && $_SERVER["SERVER_PORT"] != 80) $urlPrefix .= ':'.$_SERVER["SERVER_PORT"];
		$urlPrefix .= $clientRoot.(substr($clientRoot,-1)=='/'?'':'/');
		if(file_exists('../../webservices/dwc/rss.xml')){
			$feedLink = $urlPrefix.'webservices/dwc/rss.xml';
			echo '<a href="'.$feedLink.'" target="_blank">'.$feedLink.'</a>';
		}
		else{
			echo '--feed not published for any of the collections within the portal--';
		}
		?>
	</div>
	<?php 
	if($collId){
		if($action == 'Create/Refresh Darwin Core Archive'){
			echo '<ul>';
			$dwcaManager->setVerbose(1);
			$dwcaManager->setLimitToGuids(true);
			$dwcaManager->createDwcArchive();
			$dwcaManager->writeRssFile();
			echo '</ul>';
		}
		if($dwcaArr = $dwcaManager->getDwcaItems($collId)){
			foreach($dwcaArr as $k => $v){ 
				?>
				<div style="margin:10px;">
					<div>
						<b>Title:</b> <?php echo $v['title']; ?> 
						<form action="datapublisher.php" method="post" style="display:inline;" onsubmit="return window.confirm('Are you sure you want to delete this archive?');">
							<input type="hidden" name="colliddel" value="<?php echo $v['collid']; ?>">
							<input type="hidden" name="collid" value="<?php echo $v['collid']; ?>">
							<input type="image" src="../../images/del.png" name="action" value="DeleteCollid" title="Delete Archive" style="width:15px;" />
						</form>
					</div>
					<div><b>Description:</b> <?php echo $v['description']; ?></div>
					<?php
					$emlLink = $urlPrefix.'collections/datasets/emlhandler.php?collid='.$collId; 
					?>
					<div><b>EML:</b> <a href="<?php echo $emlLink; ?>"><?php echo $emlLink; ?></a></div>
					<div><b>Link:</b> <a href="<?php echo $v['link']; ?>"><?php echo $v['link']; ?></a></div>
					<div><b>Type:</b> <?php echo $v['type']; ?></div>
					<div><b>Record Type:</b> <?php echo $v['recordType']; ?></div>
					<div><b>Publication Date:</b> <?php echo $v['pubDate']; ?></div>
				</div>
				<?php 
			}
		}
		else{
			echo '<div style="margin:20px;font-weight:bold;color:red;">No data archives have been published for this collection</div>';
		}
		?>
		<form name="dwcaform" action="datapublisher.php" method="post" onsubmit="return verifyDwcaForm(this)">
			<fieldset style="padding:15px;margin:15px;">
				<legend><b>Publish / Refresh DWCA File</b></legend>
				<!-- 
				<div>
					<?php 
					$cSet = str_replace('-','',strtolower($charset));
					?>
					<input type="radio" name="cset" value="iso88591" <?php echo ($cSet=='iso88591'?'checked':''); ?> /> ISO-8859-1 (western)<br/>
					<input type="radio" name="cset" value="utf8" <?php echo ($cSet=='utf8'?'checked':''); ?> /> UTF-8 (unicode)
				</div>
				-->
				<div>
					<input type="checkbox" name="dets" value="1" <?php echo ($includeDets?'CHECKED':''); ?> /> Include Determination History<br/>
					<input type="checkbox" name="imgs" value="1" <?php echo ($includeImgs?'CHECKED':''); ?> /> Include Image URLs<br/>
					<input type="checkbox" name="redact" value="1" <?php echo ($redactLocalities?'CHECKED':''); ?> /> Redact Sensitive Localities<br/>
					<!-- 
					<input type="radio" name="schema" value="1" CHECKED /> Darwin Core Archive<br/>
					<input type="radio" name="schema" value="2" /> Symbiota Archive
					-->
				</div>
				<div style="clear:both;margin:10px;">
					<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
					<input type="submit" name="formsubmit" value="Create/Refresh Darwin Core Archive" />
				</div>
				<?php 
				if($collArr[$collId]['managementtype'] != 'Live Data' || $collArr[$collId]['guidtarget'] != 'symbiotaUUID'){
					?>
					<div style="margin:10px;font-weight:bold">
						NOTE: all records lacking occurrenceID GUIDs will be excluded
					</div>
					<?php
				}
				?>
			</fieldset>
		</form>
		<?php
	}
	else{
		if($isAdmin){
			if($action == 'Create/Refresh Darwin Core Archive(s)'){
				echo '<ul>';
				$dwcaManager->setVerbose(1);
				$dwcaManager->setLimitToGuids(true);
				$dwcaManager->batchCreateDwca($_POST['coll']);
				echo '</ul>';
			}
			?>
			<div id="dwcaadmindiv" style="margin:10px;display:<?php echo ($emode?'block':'none'); ?>;" >
				<form name="dwcaadminform" action="datapublisher.php" method="post" onsubmit="return verifyDwcaAdminForm(this)">
					<fieldset style="padding:15px;">
						<legend><b>Publish / Refresh DWCA Files</b></legend>
						<div style="margin:10px;">
							&nbsp;&nbsp;&nbsp;&nbsp;
							<input name="collcheckall" type="checkbox" value="" onclick="checkAllColl(this)" /> Select/Deselect All<br/> 
							<?php 
							$collArr = $dwcaManager->getCollectionList();
							foreach($collArr as $k => $v){
								echo '<input name="coll[]" type="checkbox" value="'.$k.'" />'.$v.'<br/>';
							}
							?>
						</div>
						<!-- 
						<div style="margin:10px;">
							<?php 
							$cSet = str_replace('-','',strtolower($charset));
							?>
							<input type="radio" name="cset" value="iso88591" <?php echo ($cSet=='iso88591'?'checked':''); ?> /> ISO-8859-1 (western)<br/>
							<input type="radio" name="cset" value="utf8" <?php echo ($cSet=='utf8'?'checked':''); ?> /> UTF-8 (unicode)
						</div>
						-->
						<fieldset style="margin:10px;">
							<legend>Options</legend>
							<input type="checkbox" name="dets" value="1" <?php echo ($includeDets?'CHECKED':''); ?> /> Include Determination History<br/>
							<input type="checkbox" name="imgs" value="1" <?php echo ($includeImgs?'CHECKED':''); ?> /> Include Image URLs<br/>
							<input type="checkbox" name="redact" value="1" <?php echo ($redactLocalities?'CHECKED':''); ?> /> Redact Sensitive Localities<br/>
							<!-- 
							<input type="radio" name="schema" value="1" CHECKED /> Darwin Core Archive<br/>
							<input type="radio" name="schema" value="2" /> Symbiota Archive
							-->
						</fieldset>
						<div style="clear:both;margin:10px;">
							<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
							<input type="submit" name="formsubmit" value="Create/Refresh Darwin Core Archive(s)" />
						</div>
					</fieldset>
				</form>
			</div>
			<?php 
		}
		if($dwcaArr = $dwcaManager->getDwcaItems()){
			?>
			<table class="styledtable" style="font-family:Arial;font-size:12px;margin:10px;">
				<tr><th>Code</th><th>Collection Name</th><th>DwC-Archive</th><th>Pub Date</th></tr>
				<?php 
				foreach($dwcaArr as $k => $v){ 
					?>
					<tr>
						<td><?php echo '<a href="../misc/collprofiles.php?collid='.$v['collid'].'">'.str_replace(' DwC-Archive','',$v['title']).'</a>'; ?></td>
						<td><?php echo substr($v['description'],24); ?></td>
						<td class="nowrap">
							<?php 
							$filePath = 'dwc'.substr($v['link'],strrpos($v['link'],'/'));
							$sizeStr = $dwcaManager->humanFilesize($filePath);
							echo '<a href="'.$filePath.'">DwC-A ('.$sizeStr.')</a>';
							if($isAdmin){
								?>
								<form action="datapublisher.php" method="post" style="display:inline;" onsubmit="return window.confirm('Are you sure you want to delete this archive?');">
									<input type="hidden" name="colliddel" value="<?php echo $v['collid']; ?>">
									<input type="image" src="../../images/del.png" name="action" value="DeleteCollid" title="Delete Archive" style="width:15px;" />
								</form>
								<?php
							}
							?>
						</td> 
						<td class="nowrap"><?php echo date("Y-m-d", strtotime($v['pubDate'])); ?></td>
					</tr>
					<?php 
				}
				?>
			</table>
			<?php 
		}
		else{
			echo '<div style="margin:10px;font-weight:bold;color:red;">No data archives have been published for this collection</div>';
		}
	}
	?>
</div>
<?php 
include($serverRoot."/footer.php");
?>
</body>
</html>
