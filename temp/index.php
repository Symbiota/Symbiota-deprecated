<?php
//error_reporting(E_ALL);
 header("Content-Type: text/html; charset=ISO-8859-1");
 include_once("../util/symbini.php");
 
?>
<html>
	<head>
		<title>Page</title>
		<link rel="stylesheet" href="<?php echo $clientRoot; ?>/css/main.css" type="text/css" />
	</head>
	<body>
		<?php
		$displayLeftMenu = true;
		include($serverRoot."/util/header.php");
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
			include($serverRoot."/util/footer.php");
		?>
	</body>
</html>
