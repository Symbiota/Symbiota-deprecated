<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistManager.php');
include_once($SERVER_ROOT.'/content/lang/ident/index.'.$LANG_TAG.'.php');
header("Content-Type: text/html; charset=".$CHARSET);

$proj = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:"";
if(!$proj && isset($DEFAULT_PROJ_ID)) $proj = $DEFAULT_PROJ_ID;

$clManager = new ChecklistManager();
$clManager->setProj($proj);
$pid = $clManager->getPid();
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?><?php echo $LANG['IDKEY'];?></title>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
</head>

<body>
	<?php

	$displayLeftMenu = (isset($ident_indexMenu)?$ident_indexMenu:"true");
	include($SERVER_ROOT.'/header.php');
	if(isset($ident_indexCrumbs)){
		echo "<div class='navpath'>";
		echo $ident_indexCrumbs;
		echo "<b>".$LANG['IDKEYLIST']."</b>";
		echo "</div>";
	}
	
	?> 
	
	<!-- This is inner text! -->
	<div id="innertext">
		<h2><?php echo $LANG['IDKEYS']; ?></h2>
	    <div style='margin:20px;'>
	        <?php
	        $clList = $clManager->getChecklists();
			if($clList){
				$projName = $clList['name'];
				$clArr = $clList['clid'];
				echo '<div style="margin:3px 0px 0px 15px;">';
				echo '<h3>'.$projName;
				echo ' <a href="../checklists/clgmap.php?proj='.$pid.'&target=keys"><img src="../images/world.png" style="width:10px;border:0" /></a>';
				echo '</h3>';
				echo "<div><ul>";
				foreach($clArr as $clid => $clName){
					echo "<li><a href='key.php?cl=$clid&proj=$pid&taxon=All+Species'>".$clName."</a></li>";
				}
				echo "</ul></div>";
				echo "</div>";
			}
			?>
		</div>
	</div>
	<?php 
		include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>