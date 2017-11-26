<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle?> Home</title>
	<link href="css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="css/main.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<meta name='keywords' content='' />
	<script type="text/javascript">
		<?php include_once('config/googleanalytics.php'); ?>
	</script>
</head>
<body>
	<?php
	include($serverRoot."/header.php");
	?> 
        <!-- This is inner text! -->

			<div class="works" id="works">
				<div class="container">
					<div id="portfoliolist">
						<div class="portfolio logo1 mix_all" data-cat="logo" style="display: inline-block; opacity: 1;">
							<div class="portfolio-wrapper">
								<a href="<?php echo $clientRoot; ?>/collections/map/mapinterface.php" class="b-link-stripe b-animate-go thickbox" target="_blank">
									<img src="<?php echo $clientRoot; ?>/images/map_homepg.jpg" /><div class="b-wrapper"><h2 class="b-animate b-from-left    b-delay03 "><img src="images/icon-eye.png" alt=""/></h2>
										<p class="b-animate b-from-right    b-delay03 ">Map Search<br><span class="m_4">Determine plant distributions, create checklists for an area, and identify plants of an area</span></p></div></a>
							</div>
						</div>
                        <div class="portfolio logo1 mix_all" data-cat="logo" style="display: inline-block; opacity: 1;">
                            <div class="portfolio-wrapper">
                                <a href="<?php echo $clientRoot; ?>/checklists/dynamicmap.php?interface=key" class="b-link-stripe b-animate-go thickbox" target="_blank">
                                    <img src="<?php echo $clientRoot; ?>/images/07PENRUP.jpg" /><div class="b-wrapper"><h2 class="b-animate b-from-left    b-delay03 "><img src="images/icon-eye.png" alt=""/></h2>
                                        <p class="b-animate b-from-right    b-delay03 ">Dynamic Key<br><span class="m_4">Use dynamic keys to identify unknown plants from anywhere in the state or from inventories of defined areas</span></p></div></a>
                            </div>
                        </div>
						<div class="portfolio web mix_all" data-cat="web" style="display: inline-block; opacity: 1;">
							<div class="portfolio-wrapper">
								<a href="#" class="b-link-stripe b-animate-go thickbox" target="_blank">
									<img src="<?php echo $clientRoot; ?>/images/gardening_homepg.jpg" /><div class="b-wrapper"><h2 class="b-animate b-from-left    b-delay03 "><img src="images/icon-eye.png" alt=""/></h2>
										<p class="b-animate b-from-right    b-delay03 ">Garden with Natives<br><span class="m_4">Explore features of native plant species used in gardens and landscapes, as well as where to purchase them</span></p></div></a>
							</div>
						</div>
                        <div class="portfolio app mix_all" data-cat="app" style="display: inline-block; opacity: 1;">
                            <div class="portfolio-wrapper">
                                <a href="<?php echo $clientRoot; ?>/taxa/admin/taxonomydynamicdisplay.php" target="_blank" class="b-link-stripe b-animate-go thickbox" target="_blank">
                                    <img src="<?php echo $clientRoot; ?>/images/SuttonCreek.jpg" /><div class="b-wrapper"><h2 class="b-animate b-from-left    b-delay03 "><img src="images/icon-eye.png" alt=""/></h2>
                                        <p class="b-animate b-from-right    b-delay03 ">Taxonomy Explorer<br><span class="m_4">Review taxonomy of the 4,637 vascular plant taxa found in Oregon</span></p></div></a>
                            </div>
                        </div>
						<div class="portfolio app mix_all" data-cat="app" style="display: inline-block; opacity: 1;">
							<div class="portfolio-wrapper">
								<a href="<?php echo $clientRoot; ?>/collections/index.php" class="b-link-stripe b-animate-go thickbox" target="_blank">
									<img src="<?php echo $clientRoot; ?>/images/specimens.jpg" /><div class="b-wrapper"><h2 class="b-animate b-from-left    b-delay03 "><img src="images/icon-eye.png" alt=""/></h2>
										<p class="b-animate b-from-right    b-delay03 ">OSU Herbarium<br><span class="m_4">Search records of all digitized specimens of the OSU Herbaria, including non-Oregon taxa and type specimens</span></p></div></a>
							</div>
						</div>
                        <div class="portfolio app mix_all" data-cat="app" style="display: inline-block; opacity: 1;">
                            <div class="portfolio-wrapper">
                                <a href="<?php echo $clientRoot; ?>/imagelib/search.php" class="b-link-stripe b-animate-go thickbox" target="_blank">
                                    <img src="<?php echo $clientRoot; ?>/images/ERILAN_good.JPG" /><div class="b-wrapper"><h2 class="b-animate b-from-left    b-delay03 "><img src="images/icon-eye.png" alt=""/></h2>
                                        <p class="b-animate b-from-right    b-delay03 ">Photo Gallery<br><span class="m_4">Browse field photos and specimen images</span></p></div></a>
                            </div>
                        </div>
						<div class="portfolio card mix_all" data-cat="card" style="display: inline-block; opacity: 1;">
							<div class="portfolio-wrapper">
								<a href="<?php echo $clientRoot; ?>/projects/index.php?pid=1" class="b-link-stripe b-animate-go thickbox" target="_blank">
									<img src="<?php echo $clientRoot; ?>/images/DIG37787.jpg" /><div class="b-wrapper"><h2 class="b-animate b-from-left    b-delay03 "><img src="images/icon-eye.png" alt=""/></h2>
										<p class="b-animate b-from-right    b-delay03 ">Plant Inventories<br><span class="m_4">Create a dynamic checklist from a point on a map</span></p></div></a>
							</div>
						</div>
						<div class="portfolio logo1 mix_all" data-cat="icon" style="display: inline-block; opacity: 1;">
							<div class="portfolio-wrapper">
								<a href="<?php echo $clientRoot; ?>/checklists/dynamicmap.php?interface=key" class="b-link-stripe b-animate-go thickbox" target="_blank">
									<img src="<?php echo $clientRoot; ?>/images/Callitriche_verna.jpg" /><div class="b-wrapper"><h2 class="b-animate b-from-left    b-delay03 "><img src="images/icon-eye.png" alt=""/></h2>
										<p class="b-animate b-from-right    b-delay03 ">Dynamic Key<br><span class="m_4">Create a dynamic key from a point on a map</span></p></div></a>
							</div>
						</div>
					</div>
				</div><!-- container -->
			</div>
			<div class="grey-item-home" id="features">
				<div class="wrap">
					<h3 class="m_2"><a href="http://www.highcountryapps.com/OregonWildflowers.aspx" target="_blank" >Oregon Wildflowers app</a></h3>
					<div class="project">
						<div class="rsidebar span_1_of_2">
							<a href="http://www.highcountryapps.com/OregonWildflowers.aspx" target="_blank" ><img src="<?php echo $clientRoot; ?>/images/App_small.png" style="height:250px;" /></a>
						</div>
						<div class="cont span_2_of_2">
							An identification guide to over 1,500 wildflowers, shrubs and vines across the state. Works without an
							internet connection once downloaded onto your mobile phone or tablet.
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>
			<div class="white-item-home" id="contact">
				<div class="wrap">
					<h3 class="m_2"><a href="http://shop.brit.org/products/floraoforegon1" target="_blank" >Flora of Oregon</a></h3>
					<div class="project">
						<div class="rsidebar span_1_of_2">
							<a href="http://shop.brit.org/products/floraoforegon1" target="_blank" ><img src="<?php echo $clientRoot; ?>/images/BookCover_old.jpg" style="height:250px;" /></a>
						</div>
						<div class="cont span_2_of_2">
							Do you have your copy of Flora of Oregon Volume 1? Ask your local bookstore to stock it as well!
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>


	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>