<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<meta http-equiv="X-Frame-Options" content="deny">
	<title><?php echo $defaultTitle?> Home</title>
	<link href="css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
		<?php include_once('config/googleanalytics.php'); ?>
	</script>
</head>
<body>
	<?php
	include($serverRoot."/header.php");
	?> 
        <!-- This is inner text! -->
        <div  id="innertext">
            <h1></h1>

            <div style="padding: 0px 10px;">
            	Description and introduction of project
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>