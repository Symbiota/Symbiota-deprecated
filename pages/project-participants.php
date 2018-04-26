<?php
//error_reporting(E_ALL);
include_once( "../config/symbini.php" );
header( "Content-Type: text/html; charset=" . $charset );
?>
<html>
<head>
    <title><?php echo $defaultTitle ?> Project Participants</title>
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
    <p><strong>Staff</strong></p>
    <p>Linda Hardison, Director</p>
    <p><a href="mailto:hardisol@science.oregonstate.edu">hardisol@science.oregonstate.edu</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 541-737-4338</p>
    <p>&nbsp;</p>
    <p>Thea Jaster, Database manager, botanist</p>
    <p><a href="mailto:jastert@science.oregonstate.edu">jastert@science.oregonstate.edu</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 541-737-2445</p>
    <p>&nbsp;</p>
    <p>Stephen Meyers, Taxonomic Director</p>
    <p><a href="mailto:meyersst@science.oregonstate.edu">meyersst@science.oregonstate.edu</a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 541-737-4338</p>
    <p>&nbsp;</p>
    <p>Katie Mitchell, Database manager, botanist</p>
    <p><a href="mailto:mitchelk@science.oregonstate.edu">mitchelk@science.oregonstate.edu</a></p>
    <p>&nbsp;</p>
    <p>John Myers, illustrator</p>
    <p><a href="mailto:myersj8@oregonstate.edu">myersj8@oregonstate.edu</a></p>
    <p>&nbsp;</p>
    <p>Tanya Harvey, <em>Flora of Oregon</em> Graphic Designer</p>
    <p><a href="mailto:tanya@westerncascades.com">tanya@westerncascades.com</a></p>
    <p>&nbsp;</p>
    <p>&nbsp;</p>
    <p><strong>Advisory Council </strong></p>
    <p>Lynda Boyer</p>
    <p>Jason Bradford</p>
    <p>Daniel Luoma</p>
    <p>Will McClatchey</p>
    <p>Joan Seevers</p>
    <p>Robert Soreng</p>
    <p>&nbsp;</p>
    <p><strong>Project Associates</strong></p>
    <p>The <a href="http://npsoregon.org/">Native Plant Society of Oregon (NPSO)</a> has been a sponsor of the Oregon Flora Project since the Project&rsquo;s inception in 1994. The Society and its chapters provides financial support and promote the exchange of plant observation data and photographs.</p>
    <p>Our close ties with the <a href="http://oregonstate.edu/dept/botany/herbarium/">OSU Herbarium</a> are mutually beneficial--the Herbarium excels as a dynamic resource with exceptional depth in its Oregon collections, and the Flora Project enhances their value with careful taxonomic analysis.</p>
    <p>This website uses the <a href="http://symbiota.org/docs/">Symbiota</a> platform with customized modules. Our website is hosted at the <a href="http://my.science.oregonstate.edu/">College of Science Information Network</a> (COSINe) at Oregon State University.</p>
    <p>We collaborate and share information with numerous groups: academic institutions, federal agencies (Oregon/Washington Bureau of Land Management, US Forest Service), state organizations (Oregon Natural Heritage Information Center), <a href="https://www.oregonmetro.gov/">Metro</a>, Native Plant Society of Oregon, and individuals.</p>
    <p><strong>Key Supporters</strong></p>
    <p>John and Betty Soreng Environmental Fund of the Oregon Community Foundation</p>
    <p>Oregon/Washington Bureau of Land Management</p>
    <p>Native Plant Society of Oregon</p>
    <p>Metro (Portland Oregon)</p>
    <p>Department of Botany &amp; Plant Pathology, Oregon State University</p>
</div> <!-- .inner-content -->

<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>