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
    <div id="titlebackground"></div>
    <!-- if you need a full width column, just put it outside of .inner-content -->
    <!-- .inner-content makes a column max width 1100px, centered in the viewport -->
    <div class="inner-content">
        <!-- place static page content here. -->
        <h1>Contact Us</h1>
        <h2>Location:</h2>
        <p>1048 Cordley Hall<br />Oregon State University<br />Corvallis, OR 97331</p>
        <h2>Mailing Address:</h2>
        <p>OregonFlora<br />Dept. Botany &amp; Plant Pathology<br />Oregon State University<br />Corvallis, OR 97331-2902</p>
        <h2>Correspondence:</h2>
        <p>OregonFlora<br/>Dr. Linda K. Hardison, Director<br/>Dept. Botany &amp; Plant Pathology<br/>Oregon State University<br/>Corvallis, OR 97331-2902<br/>541-737-4338</p>
        <h2>Send contributions of species lists, digital images, and other data to:</h2>
        <p>ofpflora@oregonflora.org</p>
    </div> <!-- .inner-content -->
</div> <!-- #info-page -->
<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>