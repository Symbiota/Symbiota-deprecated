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

<div id="info-page">
    <section id="titlebackground" style="background-image: url('/images/header/h1redberry.png');">
        <div class="inner-content">
            <h1>Donate</h1>
        </div>
    </section>
    <section>
        <!-- if you need a full width colum, just put it outside of .inner-content -->
        <!-- .inner-content makes a column max width 1100px, centered in the viewport -->
        <div class="inner-content">
            <!-- place static page content here. -->
            <div class="donate-wrapper">
                <div class="col1">
                    <h2>Your support keeps OregonFlora working for you.</h2>
                    <h3>Donate</h3>
                    <p>OregonFlora brings the amazing diversity and beauty of Oregon’s plant life into your world. Whether you’re a backyard gardener, a restoration scientist, or a student of life, we provide you with the information you need that is easy to use.  Charitable donations are a critical part of our operating budget, and we appreciate your support!</p>
                    <p>We accept donations through the <a href="https://agresearchfoundation.oregonstate.edu/" target="_blank">Agricultural Research Foundation</a>, a nonprofit 501(c)(3) corporation affiliated with Oregon State University. Gift contributions and charitable bequests to the Foundation on behalf of OregonFlora are deductible and can help accrue tax benefits.</p>
                    <form style="text-align: center;" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
                        <input type="hidden" name="cmd" value="_s-xclick">
                        <input type="hidden" name="hosted_button_id" value="ELVFJLHX3T9JU">
                        <input type="image" src="images/donate.png" border="0" name="submit" alt="PayPal - The safer, easier way to support online!" title="Support Oregon Flora Project">
                        <img alt="" border="0" src="https://www.paypalobjects.com/en_US/i/scr/pixel.gif" width="1" height="1">
                    </form>
                    <h3>Sponsorship</h3>
                    <p>Contribute to the legacy of Oregon’s biodiversity by sponsoring a description, illustration, or chapter in the upcoming volumes of the Flora of Oregon.</p>
                    <p>Acknowledgement of your generous support will be published in the corresponding volume. <a href="/pages/pdfs/SponsorshipBrochure.pdf">See here</a> for details.</p>
                </div>
                <div class="col2"><img src="images/TIgerLilyMetolious_SB.jpg" alt="Tiger Lilly"><p>&nbsp;</p>
                </div>
            </div>
            <hr>
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
    </section>
</div>
<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>