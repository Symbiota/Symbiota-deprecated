<?php
//error_reporting(E_ALL);
include_once( "../config/symbini.php" );
header( "Content-Type: text/html; charset=" . $charset );
?>
<html>
<head>
    <title><?php echo $defaultTitle ?> Contact Us</title>
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
    <section id="titlebackground" style="background-image: url('/images/header/h1leaf.png');">
        <div class="inner-content">
            <h1>Contact Us</h1>
        </div>
    </section>
    <section>
        <div class="inner-content">
            <!-- place static page content here. -->
            <h2>Location:</h2>
            <p>1048 Cordley Hall<br />Oregon State University<br />Corvallis, OR 97331</p>
            <h2>Mailing Address:</h2>
            <p>OregonFlora<br />Dept. Botany &amp; Plant Pathology<br />Oregon State University<br />Corvallis, OR 97331-2902</p>
            <h2>Correspondence:</h2>
            <p>OregonFlora<br/>Dr. Linda K. Hardison, Director<br/>Dept. Botany &amp; Plant Pathology<br/>Oregon State University<br/>Corvallis, OR 97331-2902<br/>541-737-4338</p>
            <h2>Send contributions of species lists, digital images, and other data to:</h2>
            <p><a href="mailto:ofpflora@oregonflora.org">ofpflora@oregonflora.org</a></p>
        </div> <!-- .inner-content -->
    </section>
</div> <!-- #info-page -->
<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>