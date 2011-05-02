<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
?>
<html>
<head>
    <title><?php echo $defaultTitle?> Home</title>
    <link rel="stylesheet" href="css/main.css" type="text/css" />
    <meta name='keywords' content='' />
	<script type="text/javascript">
		<?php include_once('config/js/googleanalytics.php'); ?>
	</script>
</head>

<body>

	<?php
	$displayLeftMenu = "true";
	include($serverRoot."/header.php");
	?> 
        <!-- This is inner text! -->
        <div  id="innertext">
            <h1></h1>

            <div style="margin:20px;">
            	Description and introduction of project
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>