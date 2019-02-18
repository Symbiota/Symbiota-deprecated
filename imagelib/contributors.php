<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT.'/classes/ImageLibraryManager.php');
include_once($SERVER_ROOT.'/content/lang/imagelib/contributors.'.$LANG_TAG.'.php');

header("Content-Type: text/html; charset=".$CHARSET);

$pManager = new ImageLibraryManager();
?>
<html>
<head>
	<title><?php echo $DEFAULT_TITLE; ?> Photographer List</title>
	<link href="../css/bootstrap.min.css" type="text/css" rel="stylesheet"/>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<!--inicio favicon -->
	<link rel="shortcut icon" href="../images/favicon.png" type="image/x-icon">
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
		<?php
		$pList = $pManager->getPhotographerList();
		if($pList){
			echo '<div style="float:left;;margin-right:40px;">';
			echo '<h2>'.$LANG['IMG_CONTRIB'].'</h2>';
			echo '<div style="margin-left:15px">';
			foreach($pList as $uid => $pArr){
				echo '<div>';
				$phLink = 'search.php?imagedisplay=thumbnail&imagetype=all&phuidstr='.$uid.'&phjson=[{'.urlencode('"name":"'.$pArr['fullname'].'","id":"'.$uid.'"').'}]&submitaction=Load Images';
				echo '<a href="'.$phLink.'">'.$pArr['name'].'</a> ('.$pArr['imgcnt'].')</div>';
			}
			echo '</div>';
			echo '</div>';
		}
		?>

		<div style="float:left">
			<?php
			ob_flush();
			flush();
			$collList = $pManager->getCollectionImageList();
			$specList = $collList['coll'];
			if($specList){
				echo '<h2>'.$LANG['SPEC'].'</h2>';
				echo '<div style="margin-left:15px;margin-bottom:20px">';
				foreach($specList as $k => $cArr){
					echo '<div>';
					$phLink = 'search.php?nametype=2&taxtp=2&imagecount=all&imagedisplay=thumbnail&imagetype=all&submitaction=Load%20Images&db[]='.$k;
					echo '<a href="'.$phLink.'">'.$cArr['name'].'</a> ('.$cArr['imgcnt'].')</div>';
				}
				echo '</div>';
			}

			$obsList = $collList['obs'];
			if($obsList){
				echo '<h2>'.$LANG['OBS'].'</h2>';
				echo '<div style="margin-left:15px">';
				foreach($obsList as $k => $cArr){
					echo '<div>';
					$phLink = 'search.php?nametype=2&taxtp=2&imagecount=all&imagedisplay=thumbnail&imagetype=all&submitaction=Load%20Images&db[]='.$k;
					echo '<a href="'.$phLink.'">'.$cArr['name'].'</a> ('.$cArr['imgcnt'].')</div>';
				}
				echo '</div>';
			}
			?>
		</div>
	</div>
	<?php
	include($SERVER_ROOT.'/footer.php');
	?>
</body>
</html>
