<?php
//error_reporting(E_ALL);
include_once( "../config/symbini.php" );
header( "Content-Type: text/html; charset=" . $charset );
?>
<html>
<head>
    <title><?php echo $defaultTitle ?>News and Events</title>
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

<div class="info-page">
    <section id="titlebackground" class="title-blueberry">
        <div class="inner-content">
            <h1>News and Events</h1>
        </div>
    </section>
    <section class="news-events-content">
        <!-- if you need a full width column, just put it outside of .inner-content -->
        <!-- .inner-content makes a column max width 1100px, centered in the viewport -->
        <div class="inner-content">
            <div class="row">
                <div id="column-main" class="col-lg-8 news-col">
                    <div class="news-item">
                        <h2>Restoring biodiversity in agricultural lands</h2>
                        <figure class="figure news-figure">
                            <img src="images/volunteer2.png" class="figure-img img-fluid z-depth-1" alt="Volunteer 2" style="width: 400px">
                            <figcaption class="figure-caption">Or maybe it’s a three line quote ensure that the information remained comprehensive and up-to-date about this unique project.</figcaption>
                        </figure>
                        <p class="news-byline">by Erin Gray</p>
                        <p>In June, Native Plant Society of Oregon (NPSO) Citizen’s Rare Plant Watch volunteers set out to search for a historical occurrence of the rare Erigeron howellii (Howell’s daisy) in the Columbia River Gorge. Within ten minutes of hiking we passed a small waterfall and as we approached, we noticed a member of the Saxifragaceae covering the cliff directly under the falls. This turned out to be a previously undocumented occurrence of the rare Sullivantia oregana (Oregon coolwort)! We pulled out our data sheets and began collecting data on the population including location, a count of individuals, habitat description, potential threats, and lots of photographs.</p>
                    </div>

                    <div class="news-item">
                        <h2>Restoring biodiversity in agricultural lands</h2>
                        <figure class="figure news-figure">
                            <img src="images/volunteer2.png" class="figure-img img-fluid z-depth-1" alt="Volunteer 2" style="width: 400px">
                            <figcaption class="figure-caption">Or maybe it’s a three line quote ensure that the information remained comprehensive and up-to-date about this unique project.</figcaption>
                        </figure>
                        <p class="news-byline">by Erin Gray</p>
                        <p>In June, Native Plant Society of Oregon (NPSO) Citizen’s Rare Plant Watch volunteers set out to search for a historical occurrence of the rare Erigeron howellii (Howell’s daisy) in the Columbia River Gorge. Within ten minutes of hiking we passed a small waterfall and as we approached, we noticed a member of the Saxifragaceae covering the cliff directly under the falls. This turned out to be a previously undocumented occurrence of the rare Sullivantia oregana (Oregon coolwort)! We pulled out our data sheets and began collecting data on the population including location, a count of individuals, habitat description, potential threats, and lots of photographs.</p>
                    </div>
                </div>
                <div id="column-right" class="col-lg-4 events-col">
                    <h2>Upcoming Events</h2>
                    <div class="event-item">
                        <h3>2 JULY 2019, 8am-5pm</h3>
                        <div class="event-content">
                            <p><strong>Display: BEEvent Pollinator Conference.</strong> Sponsored by Linn Co. Master Gardeners. Linn Co. Fair & Expo Center, Albany, OR.</p>
                        </div>
                    </div>
                    <div class="event-item">
                        <h3>2 JULY 2019, 8am-5pm</h3>
                        <div class="event-content">
                            <p><strong>Display: BEEvent Pollinator Conference.</strong> Sponsored by Linn Co. Master Gardeners. Linn Co. Fair & Expo Center, Albany, OR.</p>
                        </div>
                    </div>
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