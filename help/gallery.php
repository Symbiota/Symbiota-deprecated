<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> vPlants - Help with Photo Galleries</title>
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
            <h1>Help with Photo Galleries</h1>

            <div style="margin:20px;">
            	<h2>Where are the photo galleries?</h2>
				<p>
				The photo galleries are in early production stage. The gallery programming is operational, and we have begun processing our own images. Later, we will solicit images from particular photographers.  The photo gallery for each species is linked from the specimen and description pages as well as search results pages.
				</p>

				<p>
				We will be continually adding to the photo gallery pages.  Our main goal is to have at least one photo available for each plant and fungus species in the Chicago Region. It will take some time to obtain photos of rarely seen or obscure species.  We will do our best to ensure that the identification of the plant or fungus in the photograph is correct. But we cannot fully vouch for their complete accuracy because many of these lack voucher specimens (if the plant or fungus in the photo was not also preserved in a herbarium).  The quality of the photographs differs depending upon the source.  
				</p>
				<p>The photographs displayed on vPlants are collected from various private collections or specific public-access websites as noted on each image.  We ask that the photographs from the vPlants site, whether of specimens or species, be used for <a href="/copyright.html" title="Copyright information.">educational purposes only</a>.
				A <a href="/about/credits.html" title="Credits.">list of persons</a> who provided us with photographs is available upon request. 
				</p>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>