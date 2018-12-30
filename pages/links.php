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
    <h1>Recommended online resources</h1>
    <h2>Oregon Plant Data</h2>
    <p><a href="http://oregonstate.edu/dept/botany/herbarium/">Oregon State University Herbarium</a><br>
        <a href="https://oregondigital.org/sets/herbarium">OSU Herbarium Type Specimens</a>   Images of the OSU Herbarium type specimens and their published descriptions<br>
        <a href="http://www.carexworkinggroup.com/index.html">Carex Working Group</a><br>
        <a href="https://inr.oregonstate.edu/orbic/rare-species/rare-species-oregon-publications">Rare, Threatened & Endangered Species of Oregon</a>   Oregon Biodiversity Information Center, OSU Institute for Natural Resources<br>
        <a href="https://sites.google.com/site/orimapresources/">Oregon iMapInvasives</a>   GIS-based reporting and querying tool<br>
        <a href="http://oregonexplorer.info/"> Oregon Explorer</a> Natural Resources Digital Library, OSU Institute for Natural Resources</p>

    <h2>Regional, National</h2>
    <p><a href="http://ucjeps.berkeley.edu/interchange.html">the Jepson Interchange</a>   California floristics data from the Jepson Herbarium, Univ. California Berkeley<br>
        <a href="http://www.pnwherbaria.org/">Consortium of Pacific Northwest Herbaria</a>   Aggregation of herbarium specimens from the Pacific Northwest<br>
        <a href="http://intermountainbiota.org/portal/index.php">Intermountain Regional Herbarium Network</a><br>
        <a href="http://ibis.geog.ubc.ca/biodiversity/eflora/">E-flora BC</a>   Electronic atlas of the flora of British Columbia<br>
        <a href="http://efloras.org/flora_page.aspx?flora_id=1">Flora of North America eFlora</a><br>
        <a href="https://plants.sc.egov.usda.gov/java/">USDA PLANTS Database</a></p>
    <h2>Photo Collections</h2>
    <p><a href="http://www.botany.hawaii.edu/faculty/carr/ofp/ofp_index.htm">Dr. Gerald Carr’s Oregon plant images</a><br>
        <a href="http://biology.burke.washington.edu/herbarium/imagecollection.php">University of Washington Herbarium Image Collection</a>  Burke Museum<br>
        <a href="https://calphotos.berkeley.edu/flora/">CalPhotos: Plants</a> </p>
    <h2>Native Plant Societies</h2>
    <a href="http://www.npsoregon.org/">Oregon</a><br>
    <a href="https://www.wnps.org/">Washington</a><br>
    <a href="https://www.cnps.org/">California</a><br>
    <a href="https://idahonativeplants.org/">Idaho</a><br>
    <a href="https://www.nvnps.org/">Nevada</a><br>
    <h2>Gardening with Native Plants</h2>
    <p><a href="http://www.npsoregon.org/landscaping1.html">Using Native Plants for Gardening</a>   Native Plant Society of Oregon<br>
        <a href="http://www.plantnative.org/how_intro.htm">How to Naturescape</a>   PlantNative.org<br>
        <a href="http://emerald.npsoregon.org/GardeningWithNatives.html">Gardening with Native Plants</a>   species lists for the southern Willamette Valley. Emerald Chapter, Native Plant Society of Oregon<br>
        <a href="http://www.nwplants.com/index.html">the Wild Garden</a> Hansen’s Northwest Native Plant Database</p>
    <h2>Habitat Restoration, Working Agricultural Lands</h2>
    <p><a href="http://www.heritageseedlings.com/habitat-restoration">Habitat Restoration in the Willamette Valley</a>   Heritage Seedlings<br>
        <a href="https://cascadiaprairieoak.org/wp-content/uploads/2018/10/Hamman_Restoring-Native-Diversity_Working-Lands_CPOP2018.pdf">Restoring native diversity in working lands</a>   Cascadia Prairie Oak Partnership</p>

</div> <!-- .inner-content -->

<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>