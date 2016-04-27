<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TaxonomyDisplayManager.php');
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past

$target = array_key_exists("target",$_REQUEST)?$_REQUEST["target"]:"";
$displayAuthor = array_key_exists('displayauthor',$_REQUEST)?$_REQUEST['displayauthor']:0;
$displayFullTree = array_key_exists('displayfulltree',$_REQUEST)?$_REQUEST['displayfulltree']:0;
$taxAuthId = array_key_exists("taxauthid",$_REQUEST)?$_REQUEST["taxauthid"]:1;
$statusStr = array_key_exists('statusstr',$_REQUEST)?$_REQUEST['statusstr']:'';

$taxonDisplayObj = new TaxonomyDisplayManager($target);
if($displayAuthor) $taxonDisplayObj->setDisplayAuthor(1);
 
$editable = false;
if($isAdmin || array_key_exists("Taxonomy",$userRights)){
	$editable = true;
}
 
?>
<html>
<head>
	<title><?php echo $defaultTitle." Taxonomy Display: ".$taxonDisplayObj->getTargetStr(); ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
	<link href="../../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link type="text/css" href="../../css/jquery-ui.css" rel="Stylesheet" />
	<script type="text/javascript" src="../../js/jquery.js"></script>
	<script type="text/javascript" src="../../js/jquery-ui.js"></script>
	<script type="text/javascript">
		$(document).ready(function() {

			$("#taxontarget").autocomplete({
				source: function( request, response ) {
					$.getJSON( "rpc/gettaxasuggest.php", { term: request.term, taid: document.tdform.taxauthid.value }, response );
				}
			},{ minLength: 3 }
			);


		});
		
	</script>
</head>
<body>
<?php
$displayLeftMenu = (isset($taxa_admin_taxonomydisplayMenu)?$taxa_admin_taxonomydisplayMenu:"true");
include($serverRoot.'/header.php');
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
		if($editable){
			?>
			<div style="float:right;" title="Add a New Taxon">
				<a href="taxonomyloader.php">
					<img style='border:0px;width:15px;' src='../../images/add.png'/>
				</a>
			</div>
			<?php
		}
		?>
		<div>
			<form id="tdform" name="tdform" action="taxonomydisplay.php" method='POST'>
				<fieldset style="padding:10px;width:500px;">
					<legend><b>Enter a taxon</b></legend>
					<div>
						<b>Taxon:</b> 
						<input id="taxontarget" name="target" type="text" style="width:400px;" value="<?php echo $taxonDisplayObj->getTargetStr(); ?>" /> 
					</div>
					<div style="float:right;margin:15px 80px 15px 15px;">
						<input name="tdsubmit" type="submit" value="Display Taxon Tree"/>
						<input name="taxauthid" type="hidden" value="<?php echo $taxAuthId; ?>" /> 
					</div>
					<div style="margin:15px 15px 0px 60px;">
						<input name="displayauthor" type="checkbox" value="1" <?php echo ($displayAuthor?'checked':''); ?> /> Display authors
					</div>
					<div style="margin:3px 15px 15px 60px;">
						<input name="displayfulltree" type="checkbox" value="1" <?php echo ($displayFullTree?'checked':''); ?> /> Display full tree below family 
					</div>
				</fieldset>
			</form>
		</div>
		<?php 
		if($target){
			$taxonDisplayObj->getTaxa($displayFullTree);
		}
		?>
	</div>
	<?php 
	include($serverRoot.'/footer.php');
	?>

</body>
</html>

