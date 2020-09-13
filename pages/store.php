<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
header("Content-Type: text/html; charset=" . $charset);
?>
<html>
<head>
    <title><?php echo $defaultTitle ?>OregonFlora Store</title>
    <meta charset="UTF-8">
    <link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet"/>
    <link href="../css/main.css?<?php echo $CSS_VERSION_LOCAL; ?>" type="text/css" rel="stylesheet"/>
    <meta name='keywords' content=''/>
    <script type="text/javascript">
        <?php include_once($serverRoot . '/config/googleanalytics.php'); ?>
    </script>

</head>
<body>
<?php
include($serverRoot . "/header.php");
?>

<div class="info-page">
    <section id="titlebackground" class="title-redberry">
        <div class="inner-content">
            <h1>OregonFlora Store</h1>
        </div>
    </section>
    <section>
        <!-- if you need a full width column, just put it outside of .inner-content -->
        <!-- .inner-content makes a column max width 1100px, centered in the viewport -->
        <div class="inner-content">
            <!-- place static page content here. -->
            <h2>Help support our work while learning about the diverse plants of Oregon. Here we share our comprehensive
                and research-based information in beautiful, easy-to-use tools.</h2>
            <div class="store-item">
                <div class="row">
                    <div class="store-item-info col-lg-8">
                        <p class="store-item-title">Flora of Oregon Volume 1:</p>
                        <p class="store-item-subtitle">Pteridophytes, Gymnosperms, and Monocots</p>
                        <div class="store-item-desc">
                            <p>OregonFlora, Oregon State University, and Botanical Research Institute of Texas have
                                collaborated to publish Volume 1 of the Flora of Oregon.</p>
                            <p>The first comprehensive plant guide for the state in over 50 years, and the first that is
                                illustrated, Volume 1 addresses the ferns, conifers, grasses, sedges, and lilies—1,054
                                taxa of native and naturalized plants. Plant identification keys, descriptions, and an
                                Oregon map with the distribution for each taxon are provided. Pen and ink illustrations
                                of 521 species are interspersed with the descriptions.</p>
                            <p>Chapters describe the state’s ecology and predominant plant habitats, 50 of the best
                                places to see wildflowers, and biographical sketches of notable Oregon botanists.
                                Appendices detail taxa restricted to a single ecoregion, endemics, and those not
                                collected in more than 50 years.</p>
                            <p>The Flora of Oregon is a valuable reference for naturalists, ecologists, historians, and
                                policy-makers and a welcome resource for all who study the biodiversity of Oregon.</p>
                        </div>
                    </div>
                    <div class="store-item-pic col-lg-4">
                        <img src="./images/flora_vol1.png" alt="Flora of Oregon Vol 1">
                    </div>
                </div>
                <div class="store-item-purchase">
                    <div class="store-item-price">
                        <p>$75.00</p>
                    </div>
                    <div class="store-item-link">
                        <p><a href="https://shop.brit.org/Flora-of-Oregon" target="_blank"
                              class="btn btn-primary btn-lg active" role="button" aria-pressed="true">Learn more or
                                purchase online</a></p>
                    </div>
                </div>
            </div><!-- .store-item -->
            <div class="store-item">
                <div class="row">
                    <div class="store-item-info col-lg-8">
                        <p class="store-item-title">Flora of Oregon Volume 2: </p>
                        <p class="store-item-subtitle">Dicots Aizoaceae - Fagaceae</p>
                        <div class="store-item-desc">
                            <p>Upholding the unparalleled standards of quality and design established
                                with the first volume, Volume 2 presents 1,677 taxa of Oregon dicots from families Aizoaceae
                                through Fagaceae. Notable groups include the Asteraceae (representing >11% of the state’s
                                flora), the mustard family (Brassicaceae), stonecrops (Crassulaceae), and the legumes
                                (Fabaceae). The richly-illustrated front chapters cover gardening with native plants and
                                plant-insect interactions with a focus on butterflies and pollinators. Appendices list
                                butterfly-foodplant pairs, pollinator specialists and their targeted plants, native garden
                                plants that support insects, and features of native species used for gardening and
                                landscaping.</p>
                        </div>
                    </div>
                    <div class="store-item-pic col-lg-4">
                        <img src="./images/flora_vol2.png" alt="Flora of Oregon Vol 2">
                    </div>
                </div>
                <div class="store-item-purchase">
                    <div class="store-item-price">
                        <p>Preorder your copy now!</p>
                    </div>
                    <div class="store-item-link">
                        <p><a href="https://shop.brit.org/Flora-of-Oregon" target="_blank"
                              class="btn btn-primary btn-lg active" role="button" aria-pressed="true">Learn more or
                                purchase online</a></p>
                    </div>
                </div>
            </div><!-- .store-item -->
            <div class="store-item">
                <div class="row">
                    <div class="store-item-info col-lg-8">
                        <p class="store-item-title">Oregon Wildflowers App</p>
                        <div class="store-item-desc">
                            <p>Capture OregonFlora’s depth of knowledge on your mobile device with this plant identification
                                app. Select from any of a dozen characters you recognize in your unknown plant to identify
                                more than 1,050 common wildflowers, shrubs, and vines found in Oregon and adjacent areas of
                                Washington, Idaho, and northern California. The app provides images, range maps, bloom
                                period, and technical descriptions. Save your favorites, or create and share species lists.
                                The app does not need an Internet connection to run, so you can use it no matter how remote
                                your wanderings take you.</p>
                        </div>
                    </div>
                    <div class="store-item-pic col-lg-4">
                        <img src="./images/flora_app.png" alt="Oregon Wildflowers App">
                    </div>
                </div>
                <div class="store-item-purchase">
                    <div class="store-item-price">
                        <p>$9.99</p>
                    </div>
                    <div class="store-item-link">
                        <p>
                            <a href="https://apps.apple.com/us/app/id828499164" target="_blank" alt="Oregon Wildflowers App on Apple Store"><img src="./images/applestore.png" alt="Apple Store button" width="150px"></a><a href="https://play.google.com/store/apps/details?id=com.emountainworks.android.oregonfieldguide" target="_blank" alt="Oregon Wildflowers App on Google play"><img src="./images/googleplay.png" alt="Google play button" width="150px"></a>
                        </p>
                    </div>
                </div>
            </div><!-- .store-item -->
        </div><!-- .inner-content -->
    </section>
</div>
<?php
include($serverRoot . "/footer.php");
?>

</body>
</html>