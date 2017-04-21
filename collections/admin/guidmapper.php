<?php
include_once('../../config/symbini.php');
include_once($SERVER_ROOT.'/classes/UuidFactory.php');
header("Content-Type: text/html; charset=".$charset);
ini_set('max_execution_time', 3600);

if(!$SYMB_UID) header('Location: ../../profile/index.php?refurl=../collections/admin/guidmapper.php?'.$_SERVER['QUERY_STRING']);

$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;
$action = array_key_exists("formsubmit",$_POST)?$_POST["formsubmit"]:'';

$isEditor = 0;
if($IS_ADMIN || array_key_exists("CollAdmin",$USER_RIGHTS) && in_array($collId,$USER_RIGHTS["CollAdmin"])){
	$isEditor = 1;
}

$uuidManager = new UuidFactory();
?>
<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $CHARSET; ?>">
	<title>UUID GUID Mapper</title>
	<link rel="stylesheet" href="../../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" />
    <link rel="stylesheet" href="../../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" />
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

		function verifyGuidForm(f){

			return true;
    	}

		function verifyGuidAdminForm(f){

			return true;
    	}
    </script>
</head>
<body>
<?php 
$displayLeftMenu = (isset($admin_guidmapperMenu)?$admin_guidmapperMenu:"true");
include($SERVER_ROOT."/header.php");
?>
<!-- This is inner text! -->
<div id="innertext">
	<?php 
	if($isEditor){
		?>
		<h3>GUID Maintenance Control Panel</h3>
		<div style="margin:10px;">
			 
		</div>
		<?php 
		if($action == 'Populate Collection GUIDs'){
			echo '<ul>';
			$uuidManager->populateGuids($collId);
			echo '</ul>';
		}
		elseif($action == 'Populate GUIDs'){
			echo '<ul>';
			$uuidManager->populateGuids();
			echo '</ul>';
		}
		
		//$collCnt = $uuidManager->getCollectionCount();
		$occCnt = $uuidManager->getOccurrenceCount($collId);
		$detCnt = $uuidManager->getDeterminationCount($collId);
		$imgCnt = $uuidManager->getImageCount($collId);
		?>
		<?php if($collId) echo '<h3>'.$uuidManager->getCollectionName($collId).'</h3>'; ?>
		<div style="font-weight:bold;">Records without GUIDs (UUIDs)</div>
		<div style="margin:10px;">
			<div><b>Occurrences: </b><?php echo $occCnt; ?></div>
			<div><b>Determinations: </b><?php echo $detCnt; ?></div>
			<div><b>Images: </b><?php echo $imgCnt; ?></div>
		</div>
		<?php 
		if($collId){
			?>
			<form name="guidform" action="guidmapper.php" method="post" onsubmit="return verifyGuidForm(this)">
				<fieldset style="padding:15px;">
					<legend><b>GUID (UUID) Mapper</b></legend>
					<div style="clear:both;">
						<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
						<input type="submit" name="formsubmit" value="Populate Collection GUIDs" />
					</div>
				</fieldset>
			</form>
			<?php
		}
		elseif($IS_ADMIN){
			?>
			<div id="guidadmindiv">
				<form name="dwcaguidform" action="guidmapper.php" method="post" onsubmit="return verifyGuidAdminForm(this)">
					<fieldset style="padding:15px;">
						<legend><b>GUID (UUID) Mapper</b></legend>
						<div style="clear:both;margin:10px;">
							<input type="hidden" name="collid" value="<?php echo $collId; ?>" />
							<input type="submit" name="formsubmit" value="Populate GUIDs" />
						</div>
					</fieldset>
				</form>
			</div>
			<?php
		}
	}
	else{
		echo '<h2>You are not authorized to access this page</h2>';
	}
	?>
</div>
<?php 
include($SERVER_ROOT."/footer.php");
?>
</body>
</html>