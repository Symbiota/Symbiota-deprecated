<?php
//error_reporting(E_ALL);
include_once( "../config/symbini.php" );
header( "Content-Type: text/html; charset=" . $charset );

function obfuscate($email) {
  //build the mailto link
  $unencrypted_link = '<a href="mailto:'.$email.'">'.$email.'</a>';
  $noscript_link = "email";
  //put them together and encrypt
  return '<script type="text/javascript">Rot13.write(\''.str_rot13($unencrypted_link).'\');</script><noscript>'.$noscript_link . '</noscript>';
}
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
<div class="info-page">
    <section id="titlebackground" class="title-leaf">
        <!-- if you need a full width column, just put it outside of .inner-content -->
        <!-- .inner-content makes a column max width 1100px, centered in the viewport -->
        <div class="inner-content">
            <h1>Contact Us</h1>
        </div>
    </section>
    <section>
        <div class="inner-content">
            <!-- place static page content here. -->
            <h2>Location:</h2>
            <p>Room 1162<br />4575 SW Research Way<br />Corvallis, OR 97333</p>
            <h2>Mailing Address:</h2>
            <p>OregonFlora<br />OSU Dept. Botany &amp; Plant Pathology<br />4575 SW Research Way<br />Corvallis, OR 97333</p>
            <h2>Correspondence:</h2>
            <p>OregonFlora<br/>Dr. Linda K. Hardison, Director<br/>OSU Dept. Botany &amp; Plant Pathology<br/>4575 SW Research Way<br/>Corvallis, OR 97333<br/>541-737-4338</p>
            <h2>Send contributions of species lists, digital images, and other data to:</h2>
            <p><a href="mailto:info@oregonflora.org">info@oregonflora.org</a></p>
        </div> <!-- .inner-content -->
    </section>
</div> <!-- .info-page -->
<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>