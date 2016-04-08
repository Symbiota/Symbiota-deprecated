<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Feedback</title>
	<link href="../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../css/main.css" type="text/css" rel="stylesheet" />
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
        <div  id="innervplantstext">
            <h1><Add header text, if any, here.></h1>
			<div style="margin:20px;">
            	<h1>Feedback Form</h1>
					<p>
					The Feedback form is disabled until we get it fixed.
					</p>
					<p>
					Please e-mail any suggestions or questions to <a href="http://systematics.mortonarb.org/lab">Andrew Hipp</a>, The Morton Arboretum.
					</p>

					<p>&nbsp; </p>
					<p>&nbsp; </p>
					<p>&nbsp; </p>
					<p>&nbsp; </p>
					<p>&nbsp; </p>
					<p>&nbsp; </p>
					<p>&nbsp; </p>
					<p>&nbsp; </p>
					<p>&nbsp; </p>
								
			</div>
		</div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>