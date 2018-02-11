<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle?> Home</title>
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



<?php
include($serverRoot."/footer.php");
?>

</body>
</html>