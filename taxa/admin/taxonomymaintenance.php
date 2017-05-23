<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/TaxonomyHarvester.php');

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../taxa/admin/taxonomymaintenance.php?'.$_SERVER['QUERY_STRING']);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:"";

$harvesterManager = new TaxonomyHarvester();
 
$isEditor = false;
if($IS_ADMIN || array_key_exists("Taxonomy",$USER_RIGHTS)){
	$isEditor = true;
}

if($isEditor){
	if($action == 'buildenumtree'){
		if($harvesterManager->buildHierarchyEnumTree()){
			$statusStr = 'SUCCESS building Taxonomic Index';
		}
		else{
			$statusStr = 'ERROR building Taxonomic Index: '.$harvesterManager->getErrorMessage();
		}
	}
	elseif($action == 'rebuildenumtree'){
		if($harvesterManager->rebuildHierarchyEnumTree()){
			$statusStr = 'SUCCESS building Taxonomic Index';
		}
		else{
			$statusStr = 'ERROR building Taxonomic Index: '.$harvesterManager->getErrorMessage();
		}
	}
}

?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE." Taxonomy Maintenance "; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>"/>
	<link href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script type="text/javascript">
	</script>
</head>
<body>
<?php
$displayLeftMenu = (isset($taxa_admin_taxonomydisplayMenu)?$taxa_admin_taxonomydisplayMenu:"true");
include($SERVER_ROOT.'/header.php');
if(isset($taxa_admin_taxonomydisplayCrumbs)){
	echo "<div class='navpath'>";
	echo "<a href='../index.php'>Home</a> &gt; ";
	echo $taxa_admin_taxonomydisplayCrumbs;
	echo " <b>Taxonomic Tree Viewer</b>";
	echo "</div>";
}
if(isset($taxa_admin_taxonomydisplayCrumbs)){
	if($taxa_admin_taxonomydisplayCrumbs){
		echo '<div class="navpath">';
		echo $taxa_admin_taxonomydisplayCrumbs;
		echo ' <b>Taxonomic Tree Viewer</b>'; 
		echo '</div>';
	}
}
else{
	?>
	<div class="navpath">
		<a href="../../index.php">Home</a> &gt;&gt; 
		<a href="taxaloader.php"><b>Taxonomic Tree Viewer</b></a> 
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
		if($isEditor){
			?>


			<?php 
		}
		else{
			?>
			<div style="margin:30px;font-weight:bold;font-size:120%;">
				You do not have permission to view this page. Please contact your portal administrator
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