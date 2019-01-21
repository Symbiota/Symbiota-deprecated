<?php
//error_reporting(E_ALL);
include_once( "../config/symbini.php" );
header( "Content-Type: text/html; charset=" . $charset );
?>
<html>
<head>
    <title><?php echo $defaultTitle ?> Merchandise</title>
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
<h1>Merchandise</h1>
    <div class="col-wrapper">
        <div class="our-books">
            <h3>Flora of Oregon</h3>
            <p>A detailed  reference containing plant descriptions, identification keys, pen and ink illustrations, and front chapters covering diverse topics. The three-volume set is the first comprehensive flora for the state in almost 60 years.</p>
            <div class="vol1">
                <img src="../images/layout/flora-of-oregon-book.jpg" alt="Flora of Oregon Vol 1" class="image-left">
                <strong>Flora of Oregon
                    Volume 1:</strong>
                Pteridophytes, Gymnosperms, and Monocots <br><br>
                <a href="https://shop.brit.org/products/floraoforegon1" class="btn" target="_blank">Order Online</a>
            </div>
            <div class="vol2">
                <img src="../images/layout/flora-of-oregon-book2.jpg" alt="Flora of Oregon Vol 2" class="image-left">
                <strong>Flora of Oregon
                    Volume 2:</strong>
                Dicots Adoxaceae - Fagaceae <br><br>
                <a href="pdfs/SponsorshipBrochure.pdf " class="btn" target="_blank">Available late 2019</a>
            </div>
        </div>
        <div class="our-app" style="clear: both;">
            <h3><br>Oregon Wildflowers App</h3>
            <a href="http://www.highcountryapps.com/OregonWildflowers.aspx" target="_blank"><img src="../images/layout/oregon-wildflowers-app.jpg" alt="Oregon Wildflowers App" class="image-left"></a>
            <div>
                <p>An identification guide to over 1,050 wildflowers, shrubs and vines across the state. Works
                    without an internet connection once downloaded onto your mobile phone or tablet.</p>
                <p><a href="https://play.google.com/store/apps/details?id=com.emountainworks.android.oregonfieldguide" target="_blank"><img src="../images/layout/icon-google-play.png" alt="Google Play"></a></p>
                <p><a href="https://itunes.apple.com/us/app/id828499164&mt=8" target="_blank"><img src="../images/layout/icon-apple-app-store.png" alt="Apple App Store"></a></p>
            </div>
        </div>
    </div>

</div> <!-- .inner-content -->

<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>