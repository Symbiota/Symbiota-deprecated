<?php
//error_reporting(E_ALL);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/TaxonomyDisplayManager.php');
  
$target = array_key_exists("target",$_REQUEST)?$_REQUEST["target"]:"";
$taxonDisplayObj = new TaxonomyDisplayManager($target);
 
$editable = false;
if($isAdmin || array_key_exists("Taxonomy",$userRights)){
	$editable = true;
}
 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<title><?php echo $defaultTitle." Taxonomy Display: ".$taxonDisplayObj->getTargetStr(); ?></title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css"/>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset; ?>"/>
</head>
<body onload="">
<?php
$displayLeftMenu = (isset($taxa_admin_taxonomydisplayMenu)?$taxa_admin_taxonomydisplayMenu:"true");
include($serverRoot.'/header.php');
if(isset($taxa_admin_taxonomydisplayCrumbs)){
	echo "<div class='navpath'>";
	echo "<a href='../index.php'>Home</a> &gt; ";
	echo $taxa_admin_taxonomydisplayCrumbs;
	echo " <b>Taxonomy Tree</b>";
	echo "</div>";
}
?>
	<!-- This is inner text! -->
	<div id="innertext">
		<?php 
		if($editable){
		?>
		<div style="float:right;" title="Add a New Taxon">
			<a href="taxonomyloader.php">
				<img style='border:0px;width:15px;' src='../../images/add.png'/>
			</a>
		</div>
		<div>
			<form id="tdform" name="tdform" action="taxonomydisplay.php" method='POST'>
				<fieldset style="padding:10px;width:500px;">
					<legend><b>Enter a taxon</b></legend>
					<div>
						<b>Taxon:</b> <input type="text" name="target" style="width:400px;" value="<?php echo $taxonDisplayObj->getTargetStr(); ?>" /> 
					</div>
					<div style="margin:15px 0px 15px 300px;">
						<input type="submit" name="tdsubmit" value="Display Taxon Tree"/>
					</div>
				</fieldset>
			</form>
		</div>
		<?php 
			if($target){
				$taxonDisplayObj->getTaxa();
			}
		}
		else{
			?>
			<div style="margin:30px;font-weight:bold;font-size:120%;">
				Please 
				<a href="<?php echo $clientRoot; ?>/profile/index.php?target=<?php echo $target; ?>&refurl=<?php echo $clientRoot?>/taxa/admin/taxonomydisplay.php">
					login
				</a>
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

