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

<div id="info-page">
    <section id="titlebackground" class="title-blueberry">
        <div class="inner-content">
            <h1>Recommended online resources</h1>
        </div>
    </section>
    <section>
        <!-- if you need a full width column, just put it outside of .inner-content -->
        <!-- .inner-content makes a column max width 1100px, centered in the viewport -->
        <div class="inner-content links-page">
            <!-- place static page content here. -->
            <h2>Oregon Plant Data</h2>
            <p><a target="_blank" href="https://bpp.oregonstate.edu/herbarium">Oregon State University Herbarium</a></p>
            <p><a target="_blank" href="https://oregondigital.org/sets/herbarium">OSU Herbarium Type Specimens</a>
                <span class="link-details">Images of the OSU Herbarium type specimens and their published descriptions</span></p>
            <p><a target="_blank" href="https://bpp.oregonstate.edu/herbarium/plantweed-identification">OSU Plant and Weed Identification Service</a></p>
            <p><a target="_blank" href="https://www.oregon.gov/ODA/programs/Weeds/Pages/Default.aspx">Oregon Dept. Agriculture (ODA) Weeds and WeedMapper</a></p>
            <p><a target="_blank" href="https://www.oregon.gov/ODA/programs/PlantConservation/Pages/Default.aspx">ODA Plant Conservation</a></p>
            <p><a target="_blank" href="https://inr.oregonstate.edu/orbic/rare-species/rare-species-oregon-publications">Rare, Threatened & Endangered Species of Oregon</a>
                <span class="link-details">Oregon Biodiversity Information Center, OSU Institute for Natural Resources</span></p>
            <p><a target="_blank" href="http://www.carexworkinggroup.com/index.html">Carex Working Group</a></p>
            <p><a target="_blank" href="https://sites.google.com/site/orimapresources/">Oregon iMapInvasives</a>
                <span class="link-details">GIS-based reporting and querying tool</p>
            <p><a target="_blank" href="http://oregonexplorer.info/"> Oregon Explorer</a>
                <span class="link-details">Natural Resources Digital Library, OSU Institute for Natural Resources</span></p>

            <h2>Regional, National</h2>
            <p><a target="_blank" href="https://plants.sc.egov.usda.gov/java/">USDA PLANTS Database</a></p>
            <p>	<a target="_blank" href="https://www.fs.usda.gov/main/r6/plants-animals/plants">U.S. Forest Service Region 6 Plants</a></p>
            <p><a target="_blank" href="http://ucjeps.berkeley.edu/interchange.html">the Jepson Interchange</a>
                <span class="link-details">California floristics data from the Jepson Herbarium, Univ. California Berkeley</span></p>
            <p><a target="_blank" href="http://www.pnwherbaria.org/">Consortium of Pacific Northwest Herbaria</a>
                <span class="link-details">Aggregation of herbarium specimens from the Pacific Northwest</span></p>
            <p><a target="_blank" href="http://intermountainbiota.org/portal/index.php">Intermountain Regional Herbarium Network</a></p>
            <p><a target="_blank" href="http://ibis.geog.ubc.ca/biodiversity/eflora/">E-flora BC</a>
                <span class="link-details">Electronic atlas of the flora of British Columbia</span></p>
            <p><a target="_blank" href="http://efloras.org/flora_page.aspx?flora_id=1">Flora of North America eFlora</a></p>

            <h2>Biodiversity Data Sharing</h2>
            <p><a target="_blank" href="http://symbiota.org/docs/">Symbiota</a>
                <span class="link-details">An open source content management system for curating specimen- and observation-based biodiversity data</span></p>
             <p><a target="_blank" href="https://www.gbif.org/">GBIF Global Biodiversity Information Facility</a>
                <span class="link-details">Free and open access to biodiversity data</p>
             <p><a target="_blank" href="https://www.idigbio.org/">iDigBio</a>
                <span class="link-details">Data and images of millions of biological specimens on the web</p>

            <h2>Photo Collections</h2>
            <p><a target="_blank" href="http://www.botany.hawaii.edu/faculty/carr/ofp/ofp_index.htm">Dr. Gerald Carr’s Oregon plant images</a></p>
            <p><a target="_blank" href="http://biology.burke.washington.edu/herbarium/imagecollection.php">University of Washington Herbarium Image Collection</a>
                <span class="link-details">Burke Museum</span></p>
            <p><a target="_blank" href="https://calphotos.berkeley.edu/flora/">CalPhotos: Plants</a></p>

            <h2>Native Plant Societies</h2>
            <p><a target="_blank" href="http://www.npsoregon.org/">Oregon</a></p>
            <p><a target="_blank" href="https://www.wnps.org/">Washington</a><br>
            <p><a target="_blank" href="https://www.cnps.org/">California</a></p>
            <p><a target="_blank" href="https://idahonativeplants.org/">Idaho</a></p>
            <p><a target="_blank" href="https://www.nvnps.org/">Nevada</a></p>

            <h2>Gardening with Native Plants</h2>
            <p><a target="_blank" href="http://www.npsoregon.org/landscaping1.html">Using Native Plants for Gardening</a>
                <span class="link-details">Native Plant Society of Oregon</span></p>
            <p><a target="_blank" href="http://emerald.npsoregon.org/GardeningWithNatives.html">Gardening with Native Plants</a>
                <span class="link-details">Species lists for the southern Willamette Valley. Emerald Chapter, Native Plant Society of Oregon</span></p>
            <p><a target="_blank" href="http://www.plantnative.org/how_intro.htm">How to Naturescape</a>
                <span class="link-details">PlantNative.org</span></p>
            <p><a target="_blank" href="https://catalog.extension.oregonstate.edu/ec1577">Gardening with Native Plants West of the Cascades</a
                <span class="link-details">Linda McMahan, OSU Extension</span></p>
            <p><a target="_blank" href="http://www.nwplants.com/index.html">The Wild Garden</a>
                <span class="link-details">Hansen’s Northwest Native Plant Database</span></p>

            <h2>Habitat Restoration, Working Agricultural Lands</h2>
            <p><a target="_blank" href="http://www.heritageseedlings.com/habitat-restoration">Habitat Restoration in the Willamette Valley</a>
                <span class="link-details">Heritage Seedlings</span></p>
            <p><a target="_blank" href="https://cascadiaprairieoak.org/wp-content/uploads/2018/10/Hamman_Restoring-Native-Diversity_Working-Lands_CPOP2018.pdf">Restoring native diversity in working lands</a>
                 <span class="link-details">Cascadia Prairie Oak Partnership</span></p>
            <p><a target="_blank" href="https://cascadiaprairieoak.org/resources/wet-prairie-guide">Cascadia Prairie Oak Partnership</a>
                <span class="link-details">Wetland Prairie Restoration Guide</span></p>

            <h2>Connect with OregonFlora on Social Media</h2>
            <p>
                <a target="_blank" href="https://www.facebook.com/OregonFlora/"><img src="./images/icon-facebook.png" width=80px alt="Facebook" title="Facebook"></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                <a target="_blank" href="https://www.instagram.com/oregonflora/?hl=en"><img src="./images/icon-instagram.png" width=80px alt="Instagram" title="Instagram"</a></p>

        </div> <!-- .inner-content -->
    </section>
</div>
<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>