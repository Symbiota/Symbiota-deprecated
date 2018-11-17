<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle?> Home</title>
    <meta charset="UTF-8">
	<link href="css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="css/main.css?<?php echo $CSS_VERSION_LOCAL; ?>" type="text/css" rel="stylesheet" />
	<meta name='keywords' content='' />
	<script type="text/javascript">
		<?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
	</script>
</head>
<body>
<?php
include($serverRoot."/header.php");
?>
<link rel="stylesheet" href="css/jquery.bxslider.css">
<script src="js/jquery.bxslider.js"></script>
<script>
$(document).ready(function () {
    $('.slider').bxSlider({
        auto: true,
        stopAutoOnClick: true,
        controls: true,
        slideWidth: 600
    });
})
</script>
<div class="inner-content">
    <div class="home-boxes">
        <a href="<?php echo $clientRoot; ?>/spatial/index.php" class="home-box mapping-box" target="_blank">
            <img src="images/layout/mapping-box.jpg" alt="Mapping">
            <h3>Mapping</h3>
            <div class="box-overlay">
                <div class="centered">GIS mapping of plant occurrences within and beyond Oregon</div>
            </div>
        </a>
        <a href="<?php echo $clientRoot; ?>/checklists/dynamicmap.php?interface=key" class="home-box key-box">
            <img src="images/layout/interactive-key-box.jpg" alt="Interactive Key">
            <h3>Interactive Key</h3>
            <div class="box-overlay">
                <div class="centered">Identification tool allowing user to select recognizable characters</div>
            </div>
        </a>
        <a href="<?php echo $clientRoot; ?>/projects/index.php" class="home-box plant-box">
            <img src="images/layout/plant-inventories-box.jpg" alt="Plant Inventories">
            <h3>Plant Inventories</h3>
            <div class="box-overlay">
                <div class="centered">Curated lists of plants within a defined area coupled with identification tools</div>
            </div>
        </a>
        <a href="<?php echo $clientRoot; ?>/collections/harvestparams.php?db[]=5,8,10,7,238,239,240,241" class="home-box herbarium-box">
            <img src="images/layout/herbarium-box.jpg" alt="OSU Herbarium">
            <h3>OSU Herbarium</h3>
            <div class="box-overlay">
                <div class="centered">Searchable access to all digitized specimens of OSU’s herbaria, including non-Oregon taxa and type specimens</div>
            </div>
        </a>
        <a href="<?php echo $clientRoot; ?>/garden/index.php" class="home-box garden-box">
            <img src="images/layout/garden-with-natives-box.jpg" alt="Garden with Natives">
            <h3>Gardening with Natives</h3>
            <div class="box-overlay">
                <div class="centered">Garden planning tools and searchable information about native species for gardens and landscapes</div>
            </div>
        </a>
        <a href="<?php echo $clientRoot; ?>/imagelib/search.php" class="home-box image-box">
            <img src="images/layout/image-search-box.jpg" alt="Image Search">
            <h3>Image Search</h3>
            <div class="box-overlay">
                <div class="centered">Field photos and herbarium specimen images of Oregon plants to view by browsing or targeted searches</div>
            </div>
        </a>
    </div>
</div>
<div class="whois-wrapper">
    <div class="inner-content">
        <div class="col1">
            <h2>Who is OregonFlora?</h2>
            <p>LWe are a passionate group of scientists based at Oregon State University whose mission is to provide accurate information about our state’s vascular plants. We communicate data to a broad audience through our website, custom data requests, and the Flora of Oregon books. People with a wide range of plant expertise are also a part of our team—more than 1,000 volunteers have shared photos, reviewed data, prepared floristic treatments and submitted lists of plants observed on hikes. Some of our key programmatic partners include:</p>
            <ul>
                <li>Native Plant Society of Oregon</li>
                <li>OR/WA Bureau of Land Management</li>
                <li>Metro</li>
                <li>OSU Herbarium</li>
            </ul>
        </div>
        <div class="col2">
            <div class="slider">
                <div><img src="images/layout/student-researchers.jpg" alt="Student Researchers" class="bordered"></div>
                <div><img src="images/layout/volunteers-plant.jpg" alt="Volunteers Plant" class="bordered"></div>
                <div><img src="images/layout/lady-photographer.jpg" alt="Lady Photographer" class="bordered"></div>
            </div>
            <div class="caption">
                More than 1,000 volunteers shared photos, reviewed data and submitted lists of plants seen on hikes. Plants have also been included from studies by university researchers, the Native Plant Society of Oregon and state and federal agencies.
            </div>
        </div>
    </div>
</div>
<div class="welcome-wrapper">
    <div class="inner-content">
        <div class="col1">
            <h2>Welcome to our new site!
                Here’s what’s changed</h2>
            <p>OregonFlora has joined forces with Symbiota to present our website as a Symbiota portal! Symbiota is an open source content management system that allows us to collaborate and share data with diverse biodiversity collections, and to tailor the information we present to the needs of our website users. </p>
            <ul class="square-bullets purple-bullets">
                <li>Taxon profile pages—comprehensive information about a plant gathered in one location. Find accepted names and synonyms, native/exotic status, images, distribution map, and external links for each of the ~4,700 species, subspecies, and varieties of Oregon vascular plants. Use the Plant Taxon Search box in the header to navigate to the page of your choice or click on a species name in other website tools to link to its profile page.</li>
                <li>Mapping –a spatial mapping tool. Enter search parameters such as plant names or collectors to retrieve records statewide or draw a shape on the map to limit your results to a defined region. Import and export datasets for analysis.</li>
                <li>Interactive key—identification tool for all plants in the state. Define the general area of your unknown plant and select its recognizable characters to narrow the possibilities. </li>
                <li>Inventories—plant occurrences of a defined place presented as a checklist and an interactive key.</li>
                <li>OSU Herbarium—all databased specimen records of angiosperms and bryophytes in a searchable, downloadable format.</li>
            </ul>
        </div>
        <div class="col2">
            <div class="video-wrapper">
                <iframe width="560" height="315" src="https://www.youtube.com/embed/JhvrQeY3doI" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>
            </div>
            <a href="/tips.php" class="btn purple-btn full-width-btn">Tips for using this site</a>
        </div>
    </div>
</div>
<div class="garden-wrapper clearfix">
        <div class="col1">&nbsp;</div>
        <div class="col2">
            <div class="col-content">
                <h2>Gardening with Natives</h2>
                <p>Learn which of Oregon’s 3,450 native species are commercially available, their characteristics, and where they naturally occur.</p>
                <ul class="square-bullets white-bullets">
                    <li>Search for plants using 23 characteristics for selection, such as type of plant, sunlight and moisture needs, size, and ease of growth.</li>
                    <li>Browse collections of plants suitable for unique garden and landscape objectives, including pollinator gardens, meadowscapes, and more.</li>
                    <li>Link to garden-focused profile pages with photos and cultivation details.</li>
                </ul>
                <p>Native plants are wise gardening choices, letting you create a landscape that reflects the unique character of the soils, climate, and plant communities of your region. Once established, many native species require less maintenance, irrigation, and fertilizer.</p>
                <a href="/gardening-with-natives.php" class="btn light-purple-btn full-width-btn">Learn More about
                    Gardening with Natives</a>
            </div>
        </div>
</div>
<div class="news-wrapper">
    <div class="inner-content">
        <div class="col1">
            <img src="images/layout/news-events.jpg" alt="News and Events">
        </div>
        <div class="col2">
            <h2>News and Events</h2>
            <ul>
                <li><a href="https://www.facebook.com/OregonFloraProject/">Facebook</a></li>
            </ul>
            <p>news and events go here.</p>
        </div>
    </div>
</div>
<div class="books-wrapper">
    <div class="inner-content">
        <h2>Order Our Books and App</h2>
        <div class="col-wrapper">
            <div class="our-books">
                <h3>Flora of Oregon</h3>
                <div class="vol1">
                    <img src="images/layout/flora-of-oregon-book.jpg" alt="Flora of Oregon Vol 1" class="image-left">
                    <strong>Flora of Oregon
                        Volume 1:</strong>
                    Pteridophytes, Gymnosperms, and Monocots <br>
                    <a href="https://shop.brit.org/products/floraoforegon1" class="btn" target="_blank">Order Online</a>
                </div>
                <div class="vol2">
                    <img src="images/layout/flora-of-oregon-book2.jpg" alt="Flora of Oregon Vol 2" class="image-left">
                    <strong>Flora of Oregon
                        Volume 2:</strong>
                    Dicots Adoxaceae - Fagaceae <br><br>
                    <span class="btn">Available late 2018</span>
                </div>
                <p>A comprehensive reference containing plant descriptions, pen and ink illustrations, and front chapters covering diverse topics. Do you have your copy of Flora of Oregon?</p>
            </div>
            <div class="our-app">
                <h3>Oregon Wildflowers App</h3>
                <a href="http://www.highcountryapps.com/OregonWildflowers.aspx" target="_blank"><img src="images/layout/oregon-wildflowers-app.jpg" alt="Oregon Wildflowers App" class="image-left"></a>
                <div>
                    <p>An identification guide to over 1,050 wildflowers, shrubs and vines across the state. Works
                    without an internet connection once downloaded onto your mobile phone or tablet.</p>
                    <p><a href="https://play.google.com/store/apps/details?id=com.emountainworks.android.oregonfieldguide" target="_blank"><img src="images/layout/icon-google-play.png" alt="Google Play"></a></p>
                    <p><a href="https://itunes.apple.com/us/app/id828499164&mt=8" target="_blank"><img src="images/layout/icon-apple-app-store.png" alt="Apple App Store"></a></p>
                </div>
            </div>
        </div>
    </div>
</div>


<?php
include($serverRoot."/footer.php");
?>

</body>
</html>