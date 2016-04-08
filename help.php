<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Help FAQ</title>
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
            <h1>Help and Frequently Asked Questions</h1>

            <div style="margin:20px;">
            	 <p>
				Click on the tabs above for help with additional topics.
				</p>

				<h3>What is vPlants?</h3>

				<p>
				Please see the information <a href="/about.html" 
				 title="About vPlants and its partners">About Us</a>.
				</p>


				<h3>Why the Chicago area?</h3>
				<p>
				Please see the information <a href="chicago.html" title="Why the Chicago Region?">Why focus on the Chicago Region?</a>
				</p>
				 

				<h3>What plants are included?</h3>
				<p>
				Please see the information  <a href="/plants/diversity.html" 
				 title="Plant Directory.">Plant Directory</a>.
				</p>

				<h3>What fungi are included?</h3>
				<p>
				Please see the information  <a href="/fungi/diversity.html" 
				 title="Fungus Directory.">Fungus Directory</a>.
				</p>
            </div>
        </div>
		
		<div id="content2"><!-- start of side content -->
			<p class="hide">
			<a id="secondary" name="secondary"></a>
			<a href="#sitemenu">Skip to site menu.</a>
			</p>

			<!-- image width is 250 pixels -->

			<div class="box">
			<h3>Where is vPlants?</h3>
			<p><a href="/map.html" title="See State Map for Chicago Region"><img 
			src="img/map_dual_200.gif" width="200" height="297" 
			alt="Maps of North America and the western Great Lakes showing the Chicago Region"></a>
			</p>
			<p>
			<img src="img/map_color_box.gif" width="20" height="20" alt="Green Box"> Region covered by vPlants
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