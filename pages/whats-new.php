<?php
//error_reporting(E_ALL);
include_once( "../config/symbini.php" );
header( "Content-Type: text/html; charset=" . $charset );
?>
<html>
<head>
    <title><?php echo $defaultTitle ?> What's New</title>
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

    <h1>What's New</h1>
    <h3>Restoring biodiversity in agricultural lands</h3>
    <p>OregonFlora is developing ways to restore biodiversity in pastures and fields used for grazing. Here in the Willamette Valley, fields are usually planted with non-native grasses that are not well-adapted for wet, clay soils. We are applying the habitat and plant distribution knowledge of OregonFlora to restore native habitat and increase the number of native species in agricultural lands. Livestock are an essential part of this strategy: the grazers aid in site preparation, control non-native vegetation, and provide economic sustainability for farmers as they use native plants as forage and have controlled access to restored habitats that previously precluded grazing.</p>

    <div class="thumb-wrapper">
        <div class="caption-wrapper">
            <img src="images/WhatsNew_Restoring_1.jpg" alt="">
        </div>
        <div class="caption-wrapper">
            <img src="images/WhatsNew_Restoring_2.jpg" alt="">
        </div>
        <div class="caption-wrapper">
            <img src="images/WhatsNew_Restoring_3c.jpg" alt="">
        </div>
    </div>
    <p>We are restoring this pasture on the Oregon State University dairy farm to a wetland by retaining water using a sandbag dam and seeding it with 23 species of native grasses and herbs.</p>
    <p>&nbsp;</p>
    <p><em>Flora of Oregon Volume 2</em></p>
    <p>There are descriptions and identification keys for 1,680 taxa of dicots in this volume. Notable groups include the legumes (Fabaceae), the stonecrop family (Crassulaceae), the mustards (Brassicaceae), and the sunflower family (Asteraceae), which comprises 12% of the state&rsquo;s flora!</p>
    <p>Two of the front chapters of this volume will cover plant-insect interactions and gardening with native plants. Each will have color photographs and related appendices.</p>
    <p><a target="_blank" href="pdfs/SponsorshipBrochure.pdf">Sponsorship opportunities</a> for portions of Volume 2 (and 3) are available and help to fund the production of these beautiful reference books.</p>
    <p>&nbsp;</p>
    <p>Upcoming OregonFlora events</p>
    <ul>
        <li>Workshop: <strong>Gardening with Native Plants</strong> with Dennis Albert &amp; Linda Hardison. 19 January 2019&nbsp; 1:00-2:00pm.&nbsp; 9000 SE Division, Portland, OR. Sponsored by Portland Nursery. &nbsp;Class size limited; <a target="_blank" href="https://portlandnursery.wufoo.com/forms/ze34hhv1g9lkiq/">click here</a> to register.</li>
        <li>Presentation: <strong>The New OregonFlora website</strong> with Stephen Meyers. 14 February 2019. 7:00pm.&nbsp; Mercy Community Education Center, 2880 NW Stewart Parkway, Roseburg OR. Sponsored by Umpqua Valley Chapter of Native Plant Society of Oregon.</li>
        <li>Presentation: <strong>The OregonFlora website: a digital flora</strong> with Thea Jaster &amp; Linda Hardison. 11 March 2019. 7:30pm.&nbsp; Oregon State University, 2087 Cordley Hall, Corvallis OR. Sponsored by Corvallis Chapter of Native Plant Society of Oregon.</li>
    </ul>
</div> <!-- .inner-content -->

<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>