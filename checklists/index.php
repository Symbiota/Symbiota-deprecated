<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ChecklistManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$projValue = array_key_exists("proj",$_REQUEST)?$_REQUEST["proj"]:""; 
if(!$projValue && isset($DEFAULT_PROJ_ID)) $projValue = $DEFAULT_PROJ_ID;

$clManager = new ChecklistManager();
$clManager->setProj($projValue);
$pid = $clManager->getPid();
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Species Lists</title>
	<link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
		<?php include_once($SERVER_ROOT.'/config/googleanalytics.php'); ?>
	</script>
</head>

<body>

	<?php
	$displayLeftMenu = (isset($checklists_indexMenu)?$checklists_indexMenu:"true");
	include($SERVER_ROOT."/header.php");
	if(isset($checklists_indexCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $checklists_indexCrumbs;
		echo " <b>Species Checklists</b>";
		echo "</div>";
	}
	?>
	<!-- This is inner text! -->
	<div id="innertext">
		<h1>Species Checklists</h1>
        <div style='margin:20px;'>
			<?php 
            $researchArr = $clManager->getChecklists();
			if($researchArr){
				$pName = $researchArr['name'];
				$clArr = $researchArr['clid'];
				?>
				<div style='margin:3px 0px 0px 15px;'>
					<h3><?php echo $pName; ?> 
						<a href="<?php echo "clgmap.php?proj=".$pid; ?>" title='Show checklists on map'>
							<img src='../images/world.png' style='width:10px;border:0' />
						</a>
					</h3>
					<div>
						<ul>
							<?php 
							foreach($clArr as $clid => $clName){
								echo "<li><a href='checklist.php?cl=".$clid."'>".$clName."</a></li>\n";
							}
							?>
						</ul>
					</div>
				</div>
				<?php 
			}
			?>
		</div>
	</div>
	<?php
		include($serverRoot."/footer.php");
	?>
</body>
</html>
