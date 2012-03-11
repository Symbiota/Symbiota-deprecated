<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
include_once($serverRoot.'/classes/ExsiccatiManager.php');
header("Content-Type: text/html; charset=".$charset);

$ometId = array_key_exists('ometid',$_REQUEST)?$_REQUEST['ometid']:0;
$omenId = array_key_exists('omenid',$_REQUEST)?$_REQUEST['omenid']:0;

$exsManager = new ExsiccatiManager();

$exsNumArr = array();
$exsOccArr = array();
$titleArr = array();
if($ometId){
	$exsNumArr = $exsManager->getExsNumberArr($ometId);
}
elseif($omenId){
	$exsOccArr = $exsManager->getExsOccArr($omenId);
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
			?>
			<ul>
				<?php  
				foreach($titleArr as $k => $tArr){
					?>
					<li>
						<a href="index.php?ometid=<?php echo $k; ?>">
							<?php echo $tArr['t'].', '.$tArr['e'].($tArr['r']?' ('.$tArr['r'].')':''); ?>
						</a>
					</li>
					<?php
				}
				?>
			</ul>
			<?php  
		}
		elseif($exsNumArr){
			$title = $exsNumArr['t'];
			unset($exsNumArr['t']);
			?>
			<div style="font-weight:bold;font-size:110%;"><?php echo $title; ?></div>
			<div style="margin-left:10px;">
				<ul>
					<?php 
					foreach($exsArr as $k => $numArr){
						?>
						<li>
							<a href="index.php?omenid=<?php echo $k; ?>">
								<?php echo $tArr['n'].' - '.$tArr['c']; ?>
							</a>
						</li>
						<?php
					}
					?>
				</ul>
			</div>
			<?php 
		}
		elseif($exsOccArr){
			$title = $exsOccArr['t'];
			unset($exsOccArr['t']);
			?>
			<div style="font-weight:bold;font-size:110%;"><?php echo $title; ?></div>
			<div style="margin-left:10px;">
				<?php 
				foreach($exsArr as $k => ){
					
				}
				?>
			</div>
			<?php 
		}
		?>
	</div>
	<?php
	include($serverRoot."/footer.php");
	?>
</body>
</html> 

