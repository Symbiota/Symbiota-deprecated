<?php
//error_reporting(E_ALL);
 include_once('../../config/symbini.php');
 header("Content-Type: text/html; charset=".$charset);
 
?>
<html>
	<head>
		<title>Page</title>
	    <link href="<?php echo $clientRoot; ?>/css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $clientRoot; ?>/css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($serverRoot.'/header.php');
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<h1>Forbidden</h1>
			<div style="font-weight:bold;">
				You don't have permission to access this page.
			</div>
			<div style="font-weight:bold;margin:10px;">
				<a href="<?php echo $clientRoot; ?>/index.php">Return to index page</a>
			</div>
		</div>
		<?php
			include($serverRoot.'/footer.php');
		?>
	</body>
</html>
