<?php 
include_once('../config/symbini.php');
include_once($serverRoot.'/classes/PhotographerManager.php');
header("Content-Type: text/html; charset=".$charset);

$phUid = array_key_exists("phuid",$_REQUEST)?$_REQUEST["phuid"]:0;
$limitStart = array_key_exists("lstart",$_REQUEST)?$_REQUEST["lstart"]:0;
$limitNum = array_key_exists("lnum",$_REQUEST)?$_REQUEST["lnum"]:50;
$imgCnt = array_key_exists("imgcnt",$_REQUEST)?$_REQUEST["imgcnt"]:0;

$pManager = new PhotographerManager();
?>
<html>
<head>
<title><?php echo $defaultTitle; ?> Photographer List</title>
	<link rel="stylesheet" href="../css/main.css" type="text/css" />
	<link rel="stylesheet" href="../css/speciesprofile.css" type="text/css"/>
	<meta name='keywords' content='' />
</head>

<body>

	<?php
	$displayLeftMenu = (isset($imagelib_photographersMenu)?$imagelib_photographersMenu:"true");
	include($serverRoot.'/header.php');
	if(isset($imagelib_photographersCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $imagelib_photographersCrumbs;
		echo " <b>Photographer List</b>"; 
		echo "</div>";
	}
	?> 
	<!-- This is inner text! -->
	<div id="innertext">
		<h1><?php echo $defaultTitle; ?> Photographers</h1>
		<?php
			if($phUid){
				echo "<div style='margin:0px 0px 5px 20px;'>"; 
				$pManager->echoPhotographerInfo($phUid);
				echo "</div>";
				echo "<div style='float:right;'><a href='photographers.php'>Return to Photographer List</a></div>";
				if($imgCnt < 51){
					echo "<div>Total Image: $imgCnt</div>";
				}
				else{
					echo "<div style='font-weight:bold;'>Images: $limitStart - ".($limitStart+$limitNum)." of $imgCnt</div>";
				}
				echo "<hr />";
				$pManager->echoPhotographerImages($phUid,$limitStart,$limitNum,$imgCnt);
			}
			else{
				$pManager->echoPhotographerList(); 
			}
		?>
	</div>
	<?php 
	include($serverRoot.'/footer.php');
	?>
	
</body>
</html>

