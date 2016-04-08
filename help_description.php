<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Help with Description Pages</title>
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
            	<h3>Where are the species description pages?</h3>
				<p>
				The description pages for plants are in the final production phase.  When testing is done we will post the description pages as they are completed by the <a href="/about_partners.html" title="About vPlants Partners.">partner institutions</a>.  See prototype pages here: <a href="/pr/species/" 
				 title="See prototype description pages and more.">Features in production</a>.
				</p>

				<h3>What information do the species descriptions provide?</h3>
				<p>
				The species description pages provide thorough descriptions of the physical appearance of a species, subspecies, or variety along with a photograph and information about similar species.  There is also data about typical habitat conditions, whether the species is native, and several other important or interesting facts. Currently the species description pages are only searchable by entering a particular name of a plant or plant group (common or scientific).  In the future we hope to provide the technology that will allow users to search the database of species based on particular visual character states (e.g. flower color, leaf arrangement or shape, etc.) with the aid of photographs.
				</p>

				<h3>How are the county-level distribution maps made?</h3>
				<p>
				The distribution maps presented on the prototype pages are done manually. 

				The maps on the species description pages are
				planned to be generated automatically from the specimen data in vPlants as well as data indicating other outside sources (i.e. printed floras, other herbaria, other websites).  All counties with specimen data records in vPlants are linked to those records from the maps by clicking on a particular darkly shaded county.  Due to the nature of the data recorded on the specimen labels, currently no exact location points are mapped.
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
			<h3>Related Pages</h3>

			<p>
			<a 
			 href="/about.html" 
			 title="About vPlants and its partners.">About Us</a> has more information on specimens and descriptions.
			</p>

			<p>
			<a href="/pr/species/" 
			 title="See prototype description pages and more.">Features in production</a>
			</p>

			<p><!-- Link to acknowledgements, page authors -->

			</p>

			<p>
			<a href="/pr/species/" 
			 title="See prototype description pages and more."><img src="feature/prototype_210.jpg" width="210" height="291" alt="Thumbnail image of prototype description page." /></a>
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