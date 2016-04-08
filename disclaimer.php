<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Disclaimer</title>
	<link href="css/base.css" type="text/css" rel="stylesheet" />
	<link href="css/main.css" type="text/css" rel="stylesheet" />
	<meta name='keywords' content='' />
	<script type="text/javascript">
		<?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = "true";
	include($serverRoot."/header.php");
	?> 
        <!-- This is inner text! -->
        <div  id="innertext">
            <h1>Disclaimer Statement</h1>

			<div style="margin:20px;">
				<p>We provide no warranty, expressed or implied, as to the accuracy, 
				reliability or completeness of these data.
				Information provided in the species descriptions and other vPlants pages applies to the Chicago Region and may not be relevant or complete for other regions.</p>
				<p>Some links on this server may direct you to information maintained 
				by other organizations. We cannot guarantee the relevance, timeliness, 
				or accuracy of these outside materials.</p>


				<p>&nbsp;</p>
				<p>&nbsp;</p>
				<p>&nbsp;</p>
			</div>
		</div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>