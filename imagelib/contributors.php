<?php 
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImageLibraryManager.php');
header("Content-Type: text/html; charset=".$CHARSET);

$pManager = new ImageLibraryManager();
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Photographer List</title>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<meta name='keywords' content='' />
</head>
<body>
	<?php
	$displayLeftMenu = (isset($imagelib_photographersMenu)?$imagelib_photographersMenu:false);
	include($SERVER_ROOT.'/header.php');
	?>
	<div class="navpath">
		<a href="../index.php">Home</a> &gt;&gt; 
		<a href="index.php">Image Library</a> &gt;&gt; 
		<b>Image contributors</b> 
	</div>

	<!-- This is inner text! -->
	<div id="innertext" style="height:100%">
		<div style="float:left;;margin-right:40px;">
			<h2>Photographers</h2>
			<div style="margin-left:15px">
				<?php 
				$pList = $pManager->getPhotographerList();
				foreach($pList as $uid => $pArr){
					echo '<div>';
					$phLink = 'search.php?imagedisplay=thumbnail&imagetype=all&phuidstr='.$uid.'&phjson=[{'.urlencode('"name":"'.$pArr['fullname'].'","id":"'.$uid.'"').'}]&submitaction=Load Images';
					echo '<a href="'.$phLink.'">'.$pArr['name'].'</a> ('.$pArr['imgcnt'].')</div>';
				}
				?>
			</div>
		</div>
		<div style="float:left">
			<h2>Specimens</h2>
			<div style="margin-left:15px;margin-bottom:20px">
				<?php
				ob_flush();
				flush();
				$collList = $pManager->getCollectionImageList();
				$specList = $collList['coll'];
				foreach($specList as $k => $cArr){
					echo '<div>';
					$phLink = 'search.php?nametype=2&taxtp=2&imagecount=all&imagedisplay=thumbnail&imagetype=all&submitaction=Load%20Images&db[]='.$k.'&usecookies=false';
					echo '<a href="'.$phLink.'">'.$cArr['name'].'</a> ('.$cArr['imgcnt'].')</div>';
				}
				?>
			</div>
			<h2>Observations</h2>
			<div style="margin-left:15px">
				<?php
				$obsList = $collList['obs'];
				foreach($obsList as $k => $cArr){
					echo '<div>';
					$phLink = 'search.php?nametype=2&taxtp=2&imagecount=all&imagedisplay=thumbnail&imagetype=all&submitaction=Load%20Images&db[]='.$k.'&usecookies=false';
					echo '<a href="'.$phLink.'">'.$cArr['name'].'</a> ('.$cArr['imgcnt'].')</div>';
				}
				?>
			</div>
		</div>
	</div>
	<?php 
	include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>