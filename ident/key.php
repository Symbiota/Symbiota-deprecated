<?php
include_once("../config/symbini.php");
?>
<html>
  <head>
    <meta charset="utf-8"/>
    <title><?php echo $DEFAULT_TITLE; ?></title>
  </head>

  <body>
    <?php
      include("$SERVER_ROOT/header.php");
    ?>
    
    <!-- Header includes jquery, so add jquery scripts after header -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/10.6.2/css/bootstrap-slider.min.css"
      integrity="sha256-G3IAYJYIQvZgPksNQDbjvxd/Ca1SfCDFwu2s2lt0oGo="
      crossorigin="anonymous" />
    <script
      src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-slider/10.6.2/bootstrap-slider.min.js"
      integrity="sha256-oj52qvIP5c7N6lZZoh9z3OYacAIOjsROAcZBHUaJMyw="
      crossorigin="anonymous">
    </script>
    
    <!-- Include page style here to override anything in header -->
    <link rel="stylesheet" type="text/css" href="<?php echo $CLIENT_ROOT?>/js/react/node_modules/@blueprintjs/core/lib/css/blueprint.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $CLIENT_ROOT?>/js/react/node_modules/@blueprintjs/icons/lib/css/blueprint-icons.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $CLIENT_ROOT?>/css/compiled/theme.css">
    <link rel="stylesheet" type="text/css" href="<?php echo $CLIENT_ROOT?>/css/compiled/inventory.css">
    
    <!-- This is inner text! -->
    <div id="innertext">
      <div id="react-identify-app"></div>
      <script src="<?php echo $CLIENT_ROOT?>/js/react/dist/identify.js"></script>
    </div>

    <?php
      include("$SERVER_ROOT/footer.php");
    ?>
  </body>
</html>
