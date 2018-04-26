<?php
//error_reporting(E_ALL);
include_once( "../config/symbini.php" );
header( "Content-Type: text/html; charset=" . $charset );
?>
<html>
<head>
    <title><?php echo $defaultTitle ?> Links</title>
    <meta charset="UTF-8">
    <link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet"/>
    <link href="../css/main.css?<?php echo $CSS_VERSION_LOCAL; ?>" type="text/css" rel="stylesheet"/>
    <meta name='keywords' content=''/>
    <script type="text/javascript">
		<?php include_once( $serverRoot . '/config/googleanalytics.php' ); ?>
    </script>

</head>
<body>
<?php
include( $serverRoot . "/header.php" );
?>

<!-- if you need a full width colum, just put it outside of .inner-content -->
<!-- .inner-content makes a column max width 1100px, centered in the viewport -->
<div class="inner-content">
    <!-- place static page content here. -->
    <h1>Recommended Links</h1>
    <h2>Oregon Plant Data</h2>
    <p><a href="http://oregonstate.edu/dept/botany/herbarium/">Oregon State University Herbarium</a><br /><a href="http://digitalcollections.library.oregonstate.edu/cdm4/client/herbarium/index.php?CISOROOT=/herbarium">OSU Herbarium Type Specimens</a><br /><a href="http://www.carexworkinggroup.com/index.html">Carex Working Group</a><br /><a href="http://oregonstate.edu/ornhic/index.html">Oregon Natural Heritage Information Center</a><br /><a href="http://oregonexplorer.info/">OSU Institute for Natural Resources-Oregon Explorer</a><br /><br /></p>
    <p>&nbsp;</p>
    <p>&nbsp;</p>
    <h2>Regional, National</h2>
    <p><a href="http://ucjeps.berkeley.edu/interchange.html">California Floristics-the Jepson Interchange</a><br /><a href="http://eflora.bc.ca/">Electronic Atlas of the Plants of British Columbia</a><br /><a href="http://www.herbarium.usu.edu/">Intermountain Herbarium, Utah State University</a><br /><a href="http://hua.huh.harvard.edu/FNA/">Flora of North America</a><br /><a href="http://plants.usda.gov/index.html">USDA PLANTS Database</a><br /><br /></p>
    <p>&nbsp;</p>
    <p>&nbsp;</p>
    <h2>Photo Collections</h2>
    <p><a href="http://biology.burke.washington.edu/herbarium/imagecollection.php">University of Washington Herbarium's Image Collection</a><br /><a href="http://www.npsoregon.org/photos/index.html">Native Plant Society of Oregon Plant Photo Gallery</a><br /><a href="http://www.botany.hawaii.edu/faculty/carr/ofp/ofp_index.htm">Dr. Gerald Carr's Oregon plant images</a><br /><br /></p>
    <p>&nbsp;</p>
    <p>&nbsp;</p>
    <h2>Native Plant Societies</h2>
    <p><a href="http://www.npsoregon.org/">Native Plant Society of Oregon</a><br /><a href="http://www.wnps.org/">Washington Native Plant Society</a><br /><a href="http://www.cnps.org/">California Native Plant Society</a></p>
</div> <!-- .inner-content -->

<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>