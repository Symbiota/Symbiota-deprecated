<?php
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
	<head>
		<title>Page Title</title>
		<link rel="stylesheet" href="<?php echo $clientRoot; ?>/css/main.css" type="text/css" />
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($serverRoot.'/header.php');
		?>
		<!-- This is inner text! -->
		<div id="innertext">


		</div>
		<?php
			include($serverRoot.'/footer.php');
		?>
	</body>
</html>
