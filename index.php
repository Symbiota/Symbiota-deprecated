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
<div id="fb-root"></div>
<script async defer src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&autoLogAppEvents=1&version=v3.2&appId=101991959877943"></script>
<?php
include($serverRoot."/header.php");
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.6/dist/jquery.fancybox.min.css" />
<script src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.6/dist/jquery.fancybox.min.js"></script>
<link rel="stylesheet" href="css/jquery.bxslider.css">
<script src="js/jquery.bxslider.js"></script>
<script>
$(document).ready(function () {
    $('.slider').bxSlider({
        auto: true,
        pause: 6000,
        stopAutoOnClick: true,
        controls: true,
        slideWidth: 600
    });
})
</script>
<div class="inner-content">
    <div class="home-boxes">
        <a href="<?php echo $clientRoot; ?>/spatial/index.php" class="home-box mapping-box">
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
            <h2>Who We Are</h2>
            <p>OregonFlora makes information about Oregon plants accessible to diverse audiences. We focus on the vascular plants of the state—ferns, conifers, grasses, herbs, and trees—that grow in the wild. We communicate data through our website, app, custom data requests, and the <i>Flora of Oregon</i> books. We collaborate with scientists, restorationists, gardeners, land managers, and plant enthusiasts of all ages. Volunteers are an important part of our team—please join us!</p>
        </div>
        <div class="col2">
            <div class="slider">
                <div><img src="images/layout/student-researchers.jpg" alt="Student Researchers" class="bordered"></div>
                <div><img src="images/layout/volunteers-plant.jpg" alt="Volunteers Plant" class="bordered"></div>
                <div><img src="images/layout/lady-photographer.jpg" alt="Lady Photographer" class="bordered"></div>
            </div>
<!--            <div class="caption"> -->
<!--            </div>-->
        </div>
    </div>
</div>
<div class="welcome-wrapper">
    <div class="inner-content">
        <div class="col1">
            <h2>Welcome to our new site! Here’s what you’ll find</h2>
            <p>OregonFlora has joined forces with <a href="http://symbiota.org/docs/wp-content/uploads/Symbiota_Dec_27_2017.pdf" target="_blank">Symbiota</a> to present our website as a Symbiota portal! It’s easy to collaborate, share data, and tailor the information we present to meet your needs with these features:</p>
            <p><span class="h3">Taxon profile pages </span>Comprehensive information, gathered in one location—for each of the ~4,700 vascular plants in the state! Select a page using the Plant Taxon Search box in the header or in links throughout the website.
                <a data-fancybox href="https://youtu.be/HwtEXcTO9jA">Learn how here</a>.</p>
            <p><span class="h3">Mapping </span>Draw a shape on the interactive map to learn what plant diversity is found there, or enter plant names to view their distribution across Oregon and beyond. <a data-fancybox href="https://youtu.be/Y2sdnibf1O8">Learn how here</a>.</p>
            <p><span class="h3">Interactive key </span>An identification tool based on the plant features you recognize! Start with a list of species from the surrounding area, then select characters that match your unknown plant to narrow the possibilities. <a data-fancybox href="https://youtu.be/DKxoEEwL3V4">Learn how here</a>.</p>
            <p><span class="h3">Plant Inventories </span>Species lists for defined places, presented as a checklist and an interactive key. <a data-fancybox href="https://youtu.be/RB0bdQy4k6k">Learn more here</a>.</p>
            <h2></h2>
            <p><span class="h3">OSU Herbarium </span>All databased specimen records of OSU Herbarium’s vascular plants, mosses, lichens, fungi, and algae in a searchable, downloadable format. <a data-fancybox href="https://youtu.be/OAz83vUq-bs">Learn more here</a>.</p>
        </div>
        <div class="col2">
            <div class="video-wrapper">
                <iframe width="560" height="315" src="https://www.youtube.com/embed/9ystxXKEOp4" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
            </div>
            <a href="pages/tips.php" class="btn purple-btn full-width-btn" target="_blank">Tips for using this site</a>
        </div>
    </div>
</div>
<div class="garden-wrapper clearfix">
        <div class="col1">&nbsp;</div>
        <div class="col2">
            <div class="col-content">
                <h2><a href="garden/index.php">Gardening with Natives</a></h2>
                <p>Almost 75% of Oregon’s plants are native. Discover hundreds of native species that are cultivated and commercially available for use in gardens and landscapes.</p>
                <ul class="square-bullets white-bullets">
                    <li>Search for plants that will meet your gardening needs by selecting from 17 characteristics, such as sunlight and moisture needs, size, wildlife support, and ease of growth.</li>
                    <li>Browse collections of plants suitable for unique garden and landscape objectives, including pollinator gardens, meadowscapes, and more.</li>
                    <li>Link to printable garden-focused profile pages with photos and cultivation details.</li>
                </ul>
                <a data-fancybox href="https://youtu.be/1on5abHiruM" class="btn light-purple-btn full-width-btn">Video tutorial for the Gardening with Natives portal</a>
            </div>
        </div>
</div>
<div class="news-wrapper">
    <div class="inner-content">
        <div class="col1">
            <img src="images/layout/news-events.jpg" alt="News and Events">
        </div>
        <div class="col2 expanded">
            <div>
                <h2>News and Events</h2>
                <ul class="square-bullets purple-bullets">
                    <li>Display: <strong><a target="_blank" href="https://www.linnmastergardeners.com/store/p1/Pollinator_Conference.html">BEEvent Pollinator Conference</a></strong>. 2 March 2019. 8a - 5p. Linn Co. Fair &amp; Expo Center, Albany, OR. Sponsored by Linn Co. Master Gardeners.</li>
                    <li>Panel Discussion, Winter Forum 2019: <strong><a target="_blank" href="https://www.facebook.com/events/1015219372008927/">The Oak Creek Story</a></strong>. 5 March 2019. 7:00p. Corvallis Benton Co. Library, 645 NW Monroe, Corvallis, OR. Sponsored by Marys River Watershed Council.</li>
                    <li>Presentation: <strong>The OregonFlora website: a digital flora</strong>. 11 March 2019. 7:30pm. Oregon State University, 2087 Cordley Hall, Corvallis OR. Sponsored by Corvallis Chapter of <a href="http://www.npsoregon.org/calendar.html" target="_blank" >Native Plant Society of Oregon</a>.</li>
                    <li>Presentation: <strong>The New OregonFlora website</strong> 27 April 2019, 10:30a &amp; 28 April 2019, 12:00n. <a target="_blank" href="http://www.glidewildflowershow.org/">Glide Wildflower Show</a>, Community Center, Glide, OR.</li>
                </ul>
            </div>
            <div>
                <div class="fb-like" data-href="https://www.facebook.com/OregonFlora" data-layout="standard" data-action="like" data-size="large" data-show-faces="false" data-share="true"></div>
                <a href="https://www.facebook.com/OregonFlora/" target="_blank"><img src="images/layout/facebook.png" alt="Facebook"></a>
            </div>
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
                    <a class="btn" target="_blank" href="pages/pdfs/SponsorshipBrochure.pdf">Available 2019</a>
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