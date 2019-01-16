<?php
//error_reporting(E_ALL);
include_once( "../config/symbini.php" );
header( "Content-Type: text/html; charset=" . $charset );
?>
<html>
<head>
    <title><?php echo $defaultTitle ?> Donate</title>
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
    <h1>Donate</h1>
    <p>Your support of OregonFlora will allow us to develop and share information about the diverse plant life of Oregon. Whether you’re a backyard gardener, a restoration scientist, or a student of life, OregonFlora provides you with relevant information about the plants you encounter.</p>
    <p>&nbsp;</p>
    <form style="text-align: center;" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
        <input type="hidden" name="cmd" value="_s-xclick">
        <input type="hidden" name="hosted_button_id" value="ELVFJLHX3T9JU">
        <input type="image" src="https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif" border="0" name="submit" alt="PayPal - The safer, easier way to support online!" title="Support Oregon Flora Project">
        <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
    </form>

<p>&nbsp;</p>
    <p>We accept donations through the <a href="http://agresearchfoundation.oregonstate.edu" target="_blank">Agricultural Research Foundation</a>, a nonprofit 501(c)(3) corporation affiliated with Oregon State University. Gift contributions and charitable bequests to the Foundation on behalf of the Oregon Flora Project are deductible and can help accrue tax benefits.</p>
    <p>&nbsp;</p>
    <h2>Sponsorship</h2>
    <p>Contribute to the legacy of Oregon&rsquo;s biodiversity by sponsoring a description, illustration, or chapter in the upcoming volumes of Flora of Oregon.<br>
        Acknowledgement of your generous support will be published in the corresponding volume. <a href="pdfs/SponsorshipBrochure.pdf" target="_blank">See here for details.</a>
    </p>
<a href="pdfs/SponsorshipBrochure.pdf" class="sponsor-wrapper" target="_blank">
    <p>Sponsor a description, illustration, or chapter in the upcoming volumes of <em>Flora of Oregon</em>.</p>
    <p><img src="images/sponsor.png" alt="sponsor"></p>
</a>

    <div class="thumb-wrapper">
        <div class="caption-wrapper">
            <img src="images/donate1.jpg" alt="">
            <div class="caption">Inspire curiosity</div>
        </div>
        <div class="caption-wrapper">
            <img src="images/donate2.jpg" alt="">
            <div class="caption">Inform land management decisions</div>
        </div>
        <div class="caption-wrapper">
            <img src="images/Donate child photo_2.jpg" alt="">
            <div class="caption">Engage with plants!</div>
        </div>
    </div>
</div> <!-- .inner-content -->

<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>