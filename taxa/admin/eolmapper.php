<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/EOLManager.php');
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
  
$submitAction = array_key_exists('submitaction',$_REQUEST)?$_REQUEST['submitaction']:'';
$statusStr = array_key_exists('status',$_REQUEST)?$_REQUEST['status']:'';

$editable = false;
if($isAdmin){
	$editable = true;
}

$eolManager = new EOLManager();
 
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN">
<html>
<head>
	<title><?php echo $defaultTitle." EOL Manager: "; ?></title>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $charset;?>"/>
	<link type="text/css" href="../../css/main.css" rel="stylesheet" />
	<script language=javascript>

	</script>
</head>
<body>
<?php
$displayLeftMenu = (isset($taxa_admin_eoladminMenu)?$taxa_admin_eoladminMenu:"true");
include($serverRoot.'/header.php');
if(isset($taxa_admin_eoladminCrumbs)){
	echo "<div class='navpath'>";
	echo "<a href='../index.php'>Home</a> &gt; ";
	echo $taxa_admin_eoladminCrumbs;
	echo " <b>Encyclopedia of Life Manager</b>";
	echo "</div>";
}
?>
	<!-- This is inner text! -->
	<div id="innertext">
		<h1>Encyclopedia of Life Linkage Manager</h1>
		<?php
		if($statusStr){
			?>
			<hr/>
			<div style="color:red;margin:15px;">
				<?php echo $statusStr; ?>
			</div>
			<hr/>
			<?php 
		}
		if($submitAction){
			?>
			<hr/>
			<div style="margin:15px;">
				<?php
				if($submitAction == 'Map Taxa'){
					$makePrimary = 0;
					if(array_key_exists('makeprimary',$_POST) && $_POST['makeprimary']){
						$makePrimary = 1;
					}
					$eolManager->mapTaxa($makePrimary);
				}
				elseif($submitAction == 'Map Images'){
					$startIndex = 0;
					if(array_key_exists('startindex',$_POST) && $_POST['startindex']){
						$startIndex = $_POST['startindex'];
					}
					$eolManager->mapImagesForTaxa($startIndex);
				}
				?>
			</div>
			<hr/>
			<?php 
		}
		?>
		<div style="margin:15px;">
			<?php 
			if($editable){
				?>
				<fieldset style="padding:15px;">
					<legend><b>Taxa Mapping</b></legend>
					<div>
						This module will query EOL for all accepted taxa that do not currently have an EOL link nor identifier assignment. 
						If an EOL taxon object is found, a link to EOL will be created for that taxon. 
					</div>
					<div style="margin:10px;">
						Number of taxa not mapped to EOL: 
						<b><?php echo $eolManager->getEmptyIdentifierCount(); ?></b> 
						<div style="margin:10px;">
							<form name="taxamappingform" action="eolmapper.php" method="post">
								<input type="submit" name="submitaction" value="Map Taxa" />
								<div style="margin:15px;">
									<input type="checkbox" name="makeprimary" value="1" CHECKED /> Make EOL primary link (sort order = 1)
								</div>
							</form>
						</div>
					</div>
				</fieldset>
				<fieldset style="margin-top:15px;padding:15px;">
					<legend><b>Image Mapping</b></legend>
					<div>
						This module will query the EOL image library for all accepted taxa currently linked to EOL  
						that do not have any field images. 
						Up to 5 images will be automatically linked in the mapping procedure. 
					</div>
					<div style="margin:10px;">
						Number of accpeted taxa without images: 
						<b><?php echo $eolManager->getImageDeficiencyCount(); ?></b> 
						<div style="margin:10px;">
							<form name="imagemappingform" action="eolmapper.php" method="post">
								TID Start Index: <input type="text" name="startindex" value="" /><br/>
								<input type="submit" name="submitaction" value="Map Images" />
							</form>
						</div>
					</div>
				</fieldset>
				<?php 
			}
			elseif(!$symbUid){
				?>
				Please <a href="../../profile/index.php?refurl=../taxa/admin/eolmapper.php">login</a> 
				to acess the EOL Mapper Module
				<?php 
			}
			else{
				?>
				You need Super Administrator permissions to use the EOL Mapper Module
				<?php 
			}
			?>
		</div>
	</div>
	<?php 
	include($serverRoot.'/footer.php');
	?>
</body>
</html>
