<?php
//error_reporting(E_ALL);
include_once( "../config/symbini.php" );
header( "Content-Type: text/html; charset=" . $charset );
?>
<html>
<head>
    <title><?php echo $defaultTitle ?> Gardening With Natives</title>
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
    <h1>Gardening With Natives</h1>
    <p>The Gardening with Natives section has information on 200 species of native plants that are commercially available and suitable for use in gardens and landscapes. Select any combination of features simply by clicking on the icon or word representing that feature.</p>
    <p>Things to note:</p>
    <ul>
        <li>As you make selections, the results are immediately displayed in the purple Results section beneath all search features.</li>
        <li>Clicking on any image in the results will open that plants&rsquo; garden profile page; the page can be downloaded and printed.</li>
        <li>Any number of search options may be selected. An icon is highlighted in green if it has been selected; likewise, the option is indicated in bright green at the top of the results section.</li>
        <li>To remove a search option, simply click on the highlighted icon to deselect, or remove it from the green buttons displayed at the top of the results section.</li>
        <li>Opening the &ldquo;Characteristics&rdquo; and the &ldquo;Growth &amp; Maintenance&rdquo; panels reveal additional search options.</li>
        <li>The &ldquo;Commercial Availability&rdquo; panel provides a list and contact information for businesses that sell the species featured in the garden portal. Note that inventory fluctuates; it is highly recommended that you contact the vendor to verify availability. We anticipate developing this resource further as a searchable character pending financial support.</li>
    </ul>
    <p><strong>Plant Collections</strong>&nbsp;&nbsp; Browse photos of species within any of the featured plant collections by clicking on its image. Once opened, change the options to view as a list of scientific &amp;/or common names.&nbsp;</p>
    <p>A compilation of the species featured in the Gardening with Natives section can be found <a href="../checklists/checklist.php?cl=54&amp;pid=3">here</a>.</p>

</div> <!-- .inner-content -->

<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>