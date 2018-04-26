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

<!-- if you need a full width colum, just put it outside of .inner-content -->
<!-- .inner-content makes a column max width 1100px, centered in the viewport -->
<div class="inner-content">
    <!-- place static page content here. -->
<h1>Contact Us</h1>
    <p><b>Location:</b><br>
        1048 Cordley Hall<br>
        Oregon State University<br>
        Corvallis, OR 97331</p>


    <p><b>Mailing Address:</b><br>
        OregonFlora<br>
        Dept. Botany & Plant Pathology<br>
        Oregon State University<br>
        Corvallis, OR 97331-2902</p>


    <p><b>Correspondence:</b><br>
        Dr. Linda K. Hardison<br>
        Director, OregonFlora<br>
        Dept. Botany & Plant Pathology<br>
        Oregon State University<br>
        Corvallis, OR 97331-2902<br>
        541-737-4338</p>

    <p>Send species lists, digital images, and other data to:<br>
        <a href="mailto:ofpflora@oregonflora.org">ofpflora@oregonflora.org</a></p>

</div> <!-- .inner-content -->

<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>