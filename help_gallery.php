<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Help with Photo Galleries</title>
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
        <div  id="innervplantstext">
            <h1></h1>

            <div style="margin:20px;">
            	<h3>Where are the photo galleries?</h3>
				<p>
				The photo galleries are in the planning stage.  We have begun processing our own images. When the gallery programming is operational we will then solicit additional photos.
				</p>

				<h3>How big are the photo galleries?</h3>
				<p>
				We will be continually adding to the photo gallery pages.  Our main goal is to have at least one photo available for each plant and fungus taxon in the Chicago Region, however this is not always possible.  We will do our best to ensure that the identification of the plant or fungus in the photograph is correct, but we cannot fully vouch for their complete accuracy.  The quality of the photographs differs depending upon the source.  The photographs displayed on the vPlants site are collected from various private collections or specific public-access websites as noted on each image. A list of persons who provided us with photographs is available upon request.  We ask that the photographs from the vPlants site, whether of specimens or species, be replicated for education purposes only.
				</p>

				<p>&nbsp;</p>
				<p>&nbsp;</p>
				<p>&nbsp;</p>
				<p>&nbsp;</p>
            </div>
        </div>
		
		<div id="content2"><!-- start of side content -->
			<p class="hide">
			<a id="secondary" name="secondary"></a>
			<a href="#sitemenu">Skip to site menu.</a>
			</p>

			<!-- image width is 250 pixels -->
			<div class="box">
			<h3>Related Pages</h3>

			<p>
			<a href="/pr/species/" 
			 title="See prototype description pages and more.">Features in production</a>
			</p>

			<p><!-- Link to photographers list / thank you page? -->

			</p>
			</div>

			<p class="small">
			Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p>
			<p class="small">
			<a class="popup" href="/disclaimer.html" 
			title="Read Disclaimer [opens new window]." 
			onclick="window.open(this.href, 'disclaimer', 
			'width=500,height=350,resizable,top=100,left=100');
			return false;" 
			onkeypress="window.open(this.href, 'disclaimer', 
			'width=500,height=350,resizable,top=100,left=100');
			return false;">Disclaimer</a>
			</p>

		</div><!-- end of #content2 -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>