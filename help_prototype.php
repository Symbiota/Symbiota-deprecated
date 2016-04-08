<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Help with Prototype Pages</title>
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
            <h1>Help with Prototype Pages</h1>

            <div style="margin:20px;">
            	<p>
				The prototype pages are part of the phase two expansion of vPlants.
				We are redesigning the menu system and content for the website.
				New features will be species description pages and photo galleries.

				Also we are adding specimen data, descriptions, and photos for 
				mushrooms and other fungi of the Chicago region.
				</p>

				<p>
				<h3>Why do some of the links to other pages or photos not work?</h3> 
				</p>

				<p>
				Some links are disabled (links end with #) if they point to pages not yet built, such as menu items, photo galleries, description pages for similar species, and glossary. 
				We are making changes and improvements to the site.  Links on the prototype pages are provided to show the features that the user can access from the page.  These links will be active when site design is complete and the vPlants data engine begins serving descriptions and photo galleries.
				</p>

				<p>
				<h3>Why is there no search option for fungi?</h3> 
				</p>

				<p>
				The specimen data for fungi are not yet available. 
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