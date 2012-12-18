<?php
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/OccurrenceDwcArchiver.php');

$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$action = array_key_exists("formsubmit",$_REQUEST)?$_REQUEST["formsubmit"]:'';
$cSet = array_key_exists("cset",$_REQUEST)?$_REQUEST["cset"]:'';
$includeDets = array_key_exists("dets",$_REQUEST)?$_REQUEST["dets"]:1;
$includeImgs = array_key_exists("imgs",$_REQUEST)?$_REQUEST["imgs"]:1;
$redactLocalities = array_key_exists("redact",$_REQUEST)?$_REQUEST["redact"]:1;
$schema = array_key_exists("schema",$_REQUEST)?$_REQUEST["schema"]:1;

$editable = 0;
if($isAdmin || array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"])){
	$editable = 1;
}

$dwcaManager = new OccurrenceDwcArchiver();
if($collId) $dwcaManager->setCollId($collId);

?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>">
	<title>Darwin Core Archiver Publisher</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
	<script type="text/javascript">
    
    </script>
</head>
<body>
<?php 
$displayLeftMenu = (isset($collections_datasets_datapublisherMenu)?$collections_datasets_datapublisherMenu:"true");
include($serverRoot."/header.php");
?>
<!-- This is inner text! -->
<div id="innertext">
	<?php 
	if($editable && $collId){
		if($action == 'Create/Refresh Darwin Core Archive'){
			echo '<ul>';
			$dwcaManager->createDwcArchive($includeDets, $includeImgs, $redactLocalities);
			echo '</ul>';
		}
		$dwcaArr = $dwcaManager->getDwcaItem();
		?>
		<h3>Current Archives published within RSS feed</h3>
		<div style="margin:10px;">
			Feed: 
			<?php 
			$feedLink = 'http://'.$_SERVER["SERVER_NAME"].$clientRoot.(substr($clientRoot,-1)=='/'?'':'/').'webservices/dwc/rss.xml';
			if(file_exists($feedLink)){
				echo '<a href="'.$feedLink.'">'.$feedLink.'</a>';
			}
			else{
				echo '--portal feed not published--';
			}
			?>
		</div>
		<?php
		if($dwcaArr){
			foreach($dwcaArr as $k => $v){ 
				?>
				<div style="margin:10px;">
					<div><b>Title:</b> <?php echo $v['title']; ?></div>
					<div><b>Id:</b> <?php echo $v['id']; ?></div>
					<div><b>Description:</b> <?php echo $v['description']; ?></div>
					<div><b>Type:</b> <?php echo $v['type']; ?></div>
					<div><b>Record Type:</b> <?php echo $v['recordType']; ?></div>
					<div><b>Link:</b> <?php echo $v['link']; ?></div>
					<div><b>Publication Date:</b> <?php echo $v['pubDate']; ?></div>
				</div>
				<?php 
			}
		}
		else{
			echo '<div style="margin:10px;font-weight:bold;color:red;">No data archives have been published for this collection</div>';
		}
		?>
		<hr/>
		
		<form name="dwcaform" action="datapublisher.php" method="post" onsubmit="verifyDwcaForm(this)">
			<fieldset style="padding:15px;">
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
					<input type="checkbox" name="dets" value="1" CHECKED /> Include Determination History<br/>
					<input type="checkbox" name="imgs" value="1" CHECKED /> Include Image URLs<br/>
					<input type="checkbox" name="redact" value="1" CHECKED /> Redact Sensitive Localities<br/>
					<!-- 
					<input type="radio" name="schema" value="1" CHECKED /> Darwin Core Archive<br/>
					<input type="radio" name="schema" value="2" /> Symbiota Archive
					-->
				</div>
				<div style="clear:both;">
					<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
					<input type="submit" name="formsubmit" value="Create/Refresh Darwin Core Archive" />
				</div>
			</fieldset>
		</form>
		<?php 
	}
	elseif(!$symbUid){
		?>
		<h2>Please <a href="../../profile/index.php?refurl=<?php echo $clientRoot; ?>/collections/datasets/datapublisher.php?collid=<?php echo $collid; ?>">login</a></h2>
		<?php 
	}
	elseif(!$collId){
		if($isAdmin){
			?>
			<div style="margin:10px;">
				Feed: 
				<?php 
				$feedLink = 'http://'.$_SERVER["SERVER_NAME"].$clientRoot.(substr($clientRoot,-1)=='/'?'':'/').'webservices/dwc/rss.xml';
				if(file_exists($feedLink)){
					echo '<a href="'.$feedLink.'">'.$feedLink.'</a>';
				}
				else{
					echo '--feed not published for any of the collections within the portal--';
				}
				?>
			</div>
			<form name="dwcaadminform" action="datapublisher.php" method="post" onsubmit="verifyDwcaAdminForm(this)">
				<fieldset style="padding:15px;">
					<legend><b>Publish / Refresh DWCA Files</b></legend>
					<div>
						<input name="collcheckall" type="checkbox" value="" onclick="checkAllColl()" /> Select/Deselect All 
						<?php 
						$collArr = $dwcaManager->getCollectionArr();
						foreach($collArr as $k => $v){
							echo '<input name="coll[]" type="checkbox" value="'.$k.'">'.$v.' /><br/>';
						}
						?>
					</div>
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
						<input type="checkbox" name="dets" value="1" CHECKED /> Include Determination History<br/>
						<input type="checkbox" name="imgs" value="1" CHECKED /> Include Image URLs<br/>
						<input type="checkbox" name="redact" value="1" CHECKED /> Redact Sensitive Localities<br/>
						<!-- 
						<input type="radio" name="schema" value="1" CHECKED /> Darwin Core Archive<br/>
						<input type="radio" name="schema" value="2" /> Symbiota Archive
						-->
					</div>
					<div style="clear:both;">
						<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
						<input type="submit" name="formsubmit" value="Create/Refresh Darwin Core Archive" />
					</div>
				</fieldset>
			</form>
			<?php 
		}
		else{
			?>
			<h2>ERROR: Collection id not determined</h2>
			<?php
		} 
	}
	?>
</div>
<?php 
include($serverRoot."/footer.php");
?>
</body>
</html>
