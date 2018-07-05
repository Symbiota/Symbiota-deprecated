<?php
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$CHARSET);
?>
<html>
	<head>
		<title>Page Title</title>
		<link href="<?php echo $CLIENT_ROOT; ?>/css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
		<link href="<?php echo $CLIENT_ROOT; ?>/css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($SERVER_ROOT.'/header.php');
		?>
		<div class="navpath">
			<a href="<?php echo $CLIENT_ROOT; ?>/index.php">Home</a> &gt;&gt;
			<b>Contactos</b>
		</div>
		<!-- This is inner text! -->
		<div id="innertext">



		</div>
		<?php
			include($SERVER_ROOT.'/footer.php');
		?>
	</body>
</html>
