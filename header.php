<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700,800' rel='stylesheet' type='text/css'>
<script type='text/javascript'>if(!window.jQuery){
    var jqresource = document.createElement("script");
    jqresource.async = "true";
    jqresource.src = "<?php echo $clientRoot; ?>/js/jquery.js";
    var jqscript = document.getElementsByTagName("script")[0];
    jqscript.parentNode.insertBefore(jqresource,jqscript);

    var jquiresource = document.createElement("script");
    jquiresource.async = "true";
    jquiresource.src = "<?php echo $clientRoot; ?>/js/jquery-ui.js";
    var jquiscript = document.getElementsByTagName("script")[0];
    jquiscript.parentNode.insertBefore(jquiscript,jquiresource);
}
</script>
<script src="<?php echo $clientRoot; ?>/js/hover_pack.js"></script>
<script type="text/javascript" src="<?php echo $clientRoot; ?>/js/move-top.js"></script>
<script type="text/javascript" src="<?php echo $clientRoot; ?>/js/easing.js"></script>
<link href="<?php echo $clientRoot; ?>/css/component.css" type="text/css" rel="stylesheet" />
<script src="<?php echo $clientRoot; ?>/js/modernizr.custom.js"></script>
<script type="text/javascript">
	jQuery(document).ready(function($) {
		$(".scroll").click(function(event){
			event.preventDefault();
			$('html,body').animate({scrollTop:$(this.hash).offset().top},1200);
		});
	});
</script>
<script type="text/javascript" src="<?php echo $clientRoot; ?>/js/jquery.mixitup.min.js"></script>
<style type="text/css">
    .cbp-af-header {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        background:white;
        border-bottom: 2px solid black;
        z-index: 10000;
        height: 135px;
        overflow:visible;
        -webkit-transition: height 0.1s;
        -moz-transition: height 0.1s;
        transition: height 0.1s;
    }

    .cbp-af-header .cbp-af-inner {
        width: 100%;
        max-width: 69em;
        margin: 0 auto;
        padding: 0;
    }

    /* Transitions and class for reduced height */
    .cbp-af-header.cbp-af-header-shrink {
        height: 75px;
        overflow:hidden;
    }

    .container {
        position: relative;
        margin-top: 135px;
    }

</style>
<div class="container">
<div class="cbp-af-header">
    <div class="cbp-af-inner">
        <div class="header-top">
            <div class="wrap" style="width:850px;">
                <div class="logo">
                    <ul>
                        <li><a href="<?php echo $clientRoot; ?>/index.php"><img src="<?php echo $clientRoot; ?>/images/blackLogo.png" alt=""></a></li> &nbsp;&nbsp;&nbsp;
                        <div class="clear"></div>
                    </ul>
                </div>
                <div class="menu" style="font-size:15px;margin-top:-25px;">
                    <a class="toggleMenu" href="#"><img src="<?php echo $clientRoot; ?>/images/nav_icon.png" alt="" /> </a>
                    <ul class="nav" id="nav">
                        <li><a href="<?php echo $clientRoot; ?>/index.php">Home</a></li>
                        <?php
                        if($userDisplayName){
                            ?>
                            <li><a href="">Welcome <?php echo $userDisplayName; ?>!</a></li>
                            <li><a href="<?php echo $clientRoot; ?>/profile/viewprofile.php">My Profile</a></li>
                            <li><a href="<?php echo $clientRoot; ?>/profile/index.php?submit=logout">Logout</a></li>
                            <?php
                        }
                        else{
                            ?>
                            <li><a href="<?php echo $clientRoot."/profile/index.php?refurl=".$_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING']; ?>">Log In</a></li>
                            <?php
                        }
                        ?>
                        <li><a href="<?php echo $clientRoot; ?>/sitemap.php">Sitemap</a></li>
                    </ul>
                    <script type="text/javascript" src="<?php echo $clientRoot; ?>/js/responsive-nav.js"></script>
                    <div class="clear"></div>
                    <div id="quicksearchdiv" style="width:425px;padding: 5px 5px 5px 5px;margin-top:-10px;">
                        <div style="float:left;">
                            <?php
                            //---------------------------QUICK SEARCH SETTINGS---------------------------------------
                            //Title text that will appear.
                            $searchText = 'Taxon Search';

                            //Text that will appear on search button.
                            $buttonText = 'Search';

                            //---------------------------DO NOT CHANGE BELOW HERE-----------------------------
                            include_once($SERVER_ROOT.'/classes/PluginsManager.php');
                            $pluginManager = new PluginsManager();
                            $quicksearch = $pluginManager->createQuickSearch($buttonText,$searchText);
                            echo $quicksearch;
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>