<?php
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$CHARSET);

?>
<html>
	<head>
		<title>Page</title>
		<link href="<?php echo $CLIENT_ROOT; ?>/css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $CLIENT_ROOT; ?>/css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($SERVER_ROOT.'/header.php');
		?>
		<!-- This is inner text! -->
		<div id="innertext">
			<h1>Forbidden</h1>
			<div style="font-weight:bold;">
				You don't have permission to access this page.
			</div>
			<div style="font-weight:bold;margin:10px;">
				<a href="<?php echo $CLIENT_ROOT; ?>/index.php">Return to index page</a>
			</div>
		</div>
		<?php
			include($SERVER_ROOT.'/footer.php');
		?>
	</body>
</html>
