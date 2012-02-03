<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/ExsiccatiManager.php');
header("Content-Type: text/html; charset=".$charset);

$ometId = array_key_exists("ometid",$_REQUEST)?$_REQUEST["ometid"]:0;

$exsManager = new ExsiccatiManager();

if($ometId){
	$exsicArr = $exsManager->getExsiccateArr($ometId);
}
else{
	$titleArr = $exsManager->getTitleArr();
}


$statusStr = '';
$isEditor = false;

?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Exsiccati</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
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
				var divObjs = document.getElementsByTagName("div");
			  	for (i = 0; i < divObjs.length; i++) {
			  		var obj = divObjs[i];
			  		if(obj.getAttribute("class") == target || obj.getAttribute("className") == target){
							if(obj.style.display=="none"){
								obj.style.display="inline";
							}
					 	else {
					 		obj.style.display="none";
					 	}
					}
				}
			}
		}

	</script>
</head>

<body>
	<?php 
	$displayLeftMenu = (isset($collections_exsiccati_index)?$collections_exsiccati_index:false);
	include($serverRoot."/header.php");
	?>
	<!-- This is inner text! -->
	<div id="innertext" style="width:600px;">
		<?php 
		if($titleArr){
			
		}
		elseif($exsicArr){
			
		}
		?>
		
	</div>
	<?php
	include($serverRoot."/footer.php");
	?>
</body>
</html> 

