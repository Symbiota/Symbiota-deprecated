<?php
//error_reporting(E_ALL);
include_once( "../config/symbini.php" );
header( "Content-Type: text/html; charset=" . $charset );
?>
<html>
<head>
    <title><?php echo $defaultTitle ?>News and Events</title>
    <meta charset="UTF-8">
    <meta name='keywords' content=''/>
    <script type="text/javascript">
		<?php include_once( $serverRoot . '/config/googleanalytics.php' ); ?>
    </script>

</head>
<body>
    <?php
      include("$SERVER_ROOT/header.php");
    ?>
    <!-- Include page style here to override anything in header -->
    <link rel="stylesheet" type="text/css" href="<?php echo $CLIENT_ROOT?>/css/base.css?<?php echo $CSS_VERSION; ?>">
    <link rel="stylesheet" type="text/css" href="<?php echo $CLIENT_ROOT?>/css/main.css?<?php echo $CSS_VERSION_LOCAL; ?>">


		<div id="react-whatsnew-app"></div>
		<script src="<?php echo $CLIENT_ROOT?>/js/react/dist/whatsnew.js"></script>

		<?php
		include( $serverRoot . "/footer.php" );
		?>

</body>
</html>