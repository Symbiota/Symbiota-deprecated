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

<div class="info-page donate-page">
    <section id="titlebackground" class="title-donate">
        <div class="inner-content">
            <h1 class="hidden">Support OregonFlora</h1>
            <h2>We depend on the natural world around us.</h2>
            <h2>That’s why it’s so important we understand it.</h2>
            <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
                <input type="hidden" name="cmd" value="_s-xclick">
                <input type="hidden" name="hosted_button_id" value="ELVFJLHX3T9JU">
                <button type="submit" class="btn btn-primary btn-lg active button-donate" formaction="https://www.paypal.com/cgi-bin/webscr">Donate today</button>
            </form>
        </div>
    </section>
    <section>
        <!-- if you need a full width column, just put it outside of .inner-content -->
        <!-- .inner-content makes a column max width 1100px, centered in the viewport -->
        <div class="inner-content mb-4">
            <!-- place static page content here. -->
            <div class="donate-wrapper">
                <div class="col1 col-left">
                    <h2>Our mission is to provide technically sound information about Oregon’s sunning bounty of vascular plants—more than 4,700 so far—to diverse audiences.
                        <form class="link-donate" action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
                            <input type="hidden" name="cmd" value="_s-xclick">
                            <input type="hidden" name="hosted_button_id" value="ELVFJLHX3T9JU">
                            <button type="submit" formaction="https://www.paypal.com/cgi-bin/webscr">Will you help us</button>?
                        </form>
                    </h2>
                    <p>From the windswept marshes feeding the mouth of the Columbia, to the craggy face of Steens Mountain; from the pristine meadows of the Wallowas to the serpentine soils of the Siskiyous, Oregon is home to a fragile and varied trove of plant life.</p>
                    <p>And those plants are more than just fun to look at. They clean our air and water, feed and sustain us and the animals ranging our state, contribute to medicines that heal and comfort us, and bring a sense of place to our backyard gardens, planters, and landscapes.</p>
                    <p>In short, we depend upon Oregon’s plant life to help us live better, healthier lives. That’s why our tireless crew of volunteers and staff is dedicated to finding and cataloging every last one of them. Will you help support this important work?</p>
                    <h3>With knowledge comes understanding</h3>
                    <p><img
                                src="images/OSC242249-web500x338U100.jpg"
                                class="figure-img img-fluid z-depth-1"
                                style="width: 25%; float: right; margin: 0 0 10px 15px; border: 1px solid black;"
                                alt="plant illustration"">We study Oregon’s rich plant diversity and put this knowledge into context, making it relevant to all kinds of people. If you’re restoring habitats, helping pollinators, or appreciating wildflowers, information you need is a click away on our site.</p>
                    <h3>With understanding comes wonder</h3>
                    <p>Once we really start to look at the natural world, we notice how plants are intertwined with everything. Their patterns and idiosyncrasies are a stunning tapestry to explore, and one our website was specifically designed to illuminate.</p>
                    <h3>With wonder comes connection</h3>
                    <p><img
                                src="images/children-looking-at-seed-web375x500U100.jpg"
                                class="figure-img img-fluid z-depth-1"
                                style="width: 35%; float: left; margin: 0 15px 10px 0;"
                                alt="Children"">We depend on the natural world, but the natural world also depends on us. Once we start to understand the beauty, complexity, and fragility of the plant life around us, it’s hard not to feel a connection with it—and the importance of protecting it.</p>
                    <p>Supporting OregonFlora doesn’t just support our work providing essential plant information—it builds the kind of appreciation that drives public support for critical land use decisions, environmental and economic policies, and protection of natural areas and parks.</p>
                    <h2>Your donation sets all this in motion. Help us inspire everyone with our state’s plant diversity—and plant the seeds for a smarter, healthier, and happier Oregon.</h2>
                    <p>We accept donations through the <a href="https://agresearchfoundation.oregonstate.edu/">Agricultural Research Foundation</a>, a nonprofit 501c3  corporation affiliated with Oregon State University. Gift contributions and charitable bequests to the Foundation on behalf of OregonFlora are deductible and can help accrue tax benefits.</p>
                    <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
                        <input type="hidden" name="cmd" value="_s-xclick">
                        <input type="hidden" name="hosted_button_id" value="ELVFJLHX3T9JU">
                        <button class="btn btn-primary btn-lg active button-donate" type="submit" class="btn btn-primary btn-lg active button-donate" formaction="https://www.paypal.com/cgi-bin/webscr">Donate to OregonFlora today!</button>
                    </form>
                </div>
                <div class="col2 col-right">
                    <img
                            srcset="images/volunteer2.png 1x, images/volunteer2@2x.png 2x"
                            src="images/volunteer2.png"
                            class="figure-img img-fluid z-depth-1"
                            alt="Volunteer 2"">
                    <form action="https://www.paypal.com/cgi-bin/webscr" method="post" target="_blank">
                        <input type="hidden" name="cmd" value="_s-xclick">
                        <input type="hidden" name="hosted_button_id" value="ELVFJLHX3T9JU">
                        <button class="btn btn-primary btn-lg active button-donate" type="submit" class="btn btn-primary btn-lg active button-donate" formaction="https://www.paypal.com/cgi-bin/webscr">Donate to<br />OregonFlora</button>
                    </form>
                    <p><em>“It was as though the plants wanted me to write a different kind of book and sent gentle roots deep into my brain. They wanted me to fully acknowledge their importance in human history, their amazing powers of healing, the nourishment they provide, their ability to harm if we misused them, and, ultimately, our dependence on the plant kingdom.”</em></p>
                    <p class="text-right"><strong>– Jane Goodall,<br /><em>Seeds of Hope</em></strong></p>
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