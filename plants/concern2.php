<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Topics - Causes for Concern</title>
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
            <h1>Causes for Concern</h1>

            <div style="margin:20px;">
            	<p>Over the past 200 years the Chicago Region has changed from the sprawling woodlands, wetlands, and prairie to a bustling metropolis, transportation and industrial center, and network of cities.  As the open wildlands shrink, natural plant communities have decreased in size, and overall biodiversity has declined.  Habitats become smaller and fragmented.  More plants and animals become less common and harder to find.  This causes great concern since the end result leads to extinction of species.  We should act as stewards of the natural world around us, if for no other reason than for our own benefit in terms of health, resources, and serenity.</p>
            </div>
        </div>
		
		<div id="content2">

		<div class="box document">
		<h3>....</h3>
		<ul><li>
		....
		</li></ul>
		</div>
		<!-- I guess a photo of habitat loss would be good, but IӬl have to search later. -->
		<div class="box external">
		<h3>....</h3>
		<ul>
		<li>
		....
		</li>
		</ul>
		</div>

		<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

		</div><!-- end of #content2 -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>