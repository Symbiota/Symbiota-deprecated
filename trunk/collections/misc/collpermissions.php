<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/CollectionPermissions.php');
header("Content-Type: text/html; charset=".$charset);

$action = array_key_exists("action",$_REQUEST)?$_REQUEST["action"]:""; 
$collId = array_key_exists("collid",$_REQUEST)?$_REQUEST["collid"]:0;

$permManager = new CollectionPermissions();
$collManager->setCollectionId($collId);

$isEditor = 0;		 
if($symbUid){
	if($isAdmin || (array_key_exists("CollAdmin",$userRights) && in_array($collId,$userRights["CollAdmin"]))){
		$editCode = 1;
	}
}

if($isEditor){

}

$collData = Array();
$collData = $collManager->getCollectionData();

?>
<html>
<head>
	<title><?php echo $defaultTitle." ".($collId?$collData["collectionname"]:"") ; ?> Collection Permissions</title>
	<link rel="stylesheet" href="../../css/main.css" type="text/css" />
	<script language=javascript>
		function toggleById(target){
			if(target != null){
			  	var obj = document.getElementById(target);
				if(obj.style.display=="none" || obj.style.display==""){
					obj.style.display="block";
				}
			 	else {
			 		obj.style.display="none";
			 	}
			}
			return false;
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
	$displayLeftMenu = (isset($collections_misc_collpermissionsMenu)?$collections_misc_collpermissionsMenu:true);
	include($serverRoot.'/header.php');
	if(isset($collections_misc_collpermissionsCrumbs)){
		if($collections_misc_collpermissionsCrumbs){
			echo "<div class='navpath'>";
			echo "<a href='../../index.php'>Home</a> &gt; ";
			echo $collections_misc_collpermissionsCrumbs;
			echo " <b>".($collData?$collData["collectionname"]:"Collection Profiles")."</b>";
			echo "</div>";
		}
	}
	else{
		?>
		<div class='navpath'>
			<a href='../../index.php'>Home</a> &gt; 
			<a href='../index.php'>Collections</a> &gt; 
			<b><?php echo ($collData?$collData['collectionname']:'Collection Permissions'); ?></b>
		</div>
		<?php 
	}
	?>

	<!-- This is inner text! -->
	<div id="innertext">
		<?php
		
		?>
	</div>
	<?php
		include($serverRoot.'/footer.php');
	?>

</body>
</html>