<?php
/*
 * Created on 24 Aug 2009
 * E.E. Gilbert
 */

 //error_reporting(E_ALL);
 //set_include_path( get_include_path() . PATH_SEPARATOR . $_SERVER['DOCUMENT_ROOT']."" );
 include_once('../../config/symbini.php');
 include_once($serverRoot.'/classes/TaxonomyDisplayManager.php');
  
 $target = array_key_exists("target",$_REQUEST)?$_REQUEST["target"]:"";
 $taxonDisplayObj = new TaxonomyDisplayManager($target);
 
 $editable = false;
 if($isAdmin || in_array("Taxonomy",$userRights)){
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
				<fieldset style="padding:10px;width:200px;">
					<legend>Enter a taxon</legend>
					<div>
						Taxon: <input type="text" name="target" value="<?php echo $taxonDisplayObj->getTargetStr();; ?>"/>
					</div>
					<div>
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
			echo "<div>You must be <a href='../../profile/index.php'>logged in</a> and authorized to view internal taxonomy. Please login.</div>";
		}
		?>
	</div>
	<?php 
	include($serverRoot.'/footer.php');
	?>
	<script type="text/javascript">

		
	</script>

</body>
</html>

