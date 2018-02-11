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

<div class="inner-content">
    <div class="home-boxes clearfix">
        <a href="#">
            <div class="home-box mapping-box">
                <img src="images/layout/mapping-box.jpg" alt="Mapping">
                <h3>Mapping</h3>
                <div class="box-overlay">
                    <div class="centered">Mapping box overlay content here</div>
                </div>
            </div>
        </a>
        <a href="#">
            <div class="home-box key-box">
                <img src="images/layout/interactive-key-box.jpg" alt="Interactive Key">
                <h3>Interactive Key</h3><div class="box-overlay">
                    <div class="centered">Mapping box overlay content here</div>
                </div>
            </div>
        </a>
        <a href="#">
            <div class="home-box image-box">
                <img src="images/layout/image-search-box.jpg" alt="Image Search">
                <h3>Image Search</h3><div class="box-overlay">
                    <div class="centered">Image Search box overlay content here</div>
                </div>
            </div>
        </a>
        <a href="#">
            <div class="home-box herbarium-box">
                <img src="images/layout/herbarium-box.jpg" alt="OSU Herbarium">
                <h3>OSU Herbarium</h3><div class="box-overlay">
                    <div class="centered">Search records of all digitized specimens of the OSU Herbaria, including non-Oregon taxa and type specimens.</div>
                </div>
            </div>
        </a>
        <a href="#">
            <div class="home-box garden-box">
                <img src="images/layout/garden-with-natives-box.jpg" alt="Garden with Natives">
                <h3>Garden with Natives</h3><div class="box-overlay">
                    <div class="centered">Garden with Natives box overlay content here</div>
                </div>
            </div>
        </a>
        <a href="#">
            <div class="home-box plant-box">
                <img src="images/layout/plant-inventories-box.jpg" alt="Plant Inventories">
                <h3>Plant Inventories</h3><div class="box-overlay">
                    <div class="centered">Plant Inventories box overlay content here</div>
                </div>
            </div>
        </a>
    </div>
</div>
<div class="whois-wrapper clearfix">
    <div class="inner-content">
        <div class="col1">
            <h2>Who is OregonFlora?</h2>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Eos facilis incidunt iste iure magni mollitia
                repellat totam! Blanditiis cum debitis illo in laboriosam, non, pariatur perspiciatis quibusdam saepe
                tempora voluptatibus?</p>
            <ul>
                <li>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Deserunt, rerum.</li>
                <li>Aspernatur in iure necessitatibus nobis, quis repellendus unde vero? Beatae.</li>
                <li>Doloribus laudantium magni necessitatibus neque placeat quaerat repellat sit tempore?</li>
            </ul>
            <p>Lorem ipsum dolor sit amet, consectetur adipisicing elit. Aspernatur, at dicta fugit laborum mollitia
                perspiciatis suscipit tempore temporibus! Aspernatur, consequatur!</p>
        </div>
        <div class="col2">
            <img src="images/layout/student-researchers.jpg" alt="Student Researchers" class="bordered">
            <div class="caption">
                More than 1,000 volunteers shared photos, reviewed data and submitted lists of plants seen on hikes. Plants have also been included from studies by university researchers, the Native Plant Society of Oregon and state and federal <agencies></agencies>
            </div>
        </div>
    </div>
</div>
<div class="welcome-wrapper clearfix">
    <div class="inner-content">
        <div class="col1">
            <h2>Welcome to our new site!
                Here’s what’s changed</h2>
            <ul class="square-bullets purple-bullets">
                <li>Oregon Plant Atlas — an interactive mapping program which draws from a database of over 540,000 records to display plant distributions and the data behind each mappable point.</li>
                <li>Photo Gallery — a collection of photographs for each plant featuring its habitat, general features, and details, as well as images of herbarium specimens.</li>
                <li>Rare Plant Guide — a searchable database and field-oriented fact sheets about some of Oregon's rare and threatened species.</li>
                <li>Vascular Plant Checklist — provides the taxonomic foundation for the complete project, listing accepted scientific names, common names, and synonyms for all Oregon vascular plants.</li>
                <li>Garden with Natives  — provides a compreshensive guide for native plants suitable for your location. Includes pictures, cultivation info and plants for unique garden/landscape settings.</li>
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
                <h2>Garden with Natives</h2>
                <p>Learn which of Oregon’s 3,450 native species are commercially available, their characteristics, and
                    where they naturally occur.</p>
                <ul class="square-bullets white-bullets">
                    <li>Search for plants using 16 characteristics for selection, such as type of plant, sunlight,
                        moisture, size, etc.
                    </li>
                    <li>Or browse collections of plants suitable for unique garden/lanscape settings, such as
                        Meadowscape, Woodland Garden, etc.
                    </li>
                    <li>Select a plant and link to a profile page with photos and cultivation details.</li>
                </ul>
                <p>By gardening with Natives you create a landscape that is appropriate for your soils and climate and
                    provide habitat for wildlife. Once established, many native plants need minimal irrigation beyond
                    normal rainfall. They require less maintenance, pest control and fertilization.</p>
                <a href="/gardening-with-natives.php" class="btn light-purple-btn full-width-btn">Learn More about
                    Gardening with Natives</a>
            </div>
        </div>
</div>
            </ul>
        </div>
    </div>
</div>



<?php
include($serverRoot."/footer.php");
?>

</body>
</html>