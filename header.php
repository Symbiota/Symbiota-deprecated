<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<link href='http://fonts.googleapis.com/css?family=Open+Sans:400,300,600,700,800' rel='stylesheet' type='text/css'>
<link rel='stylesheet' href='//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' type='text/css' media='all' />
<script src="<?php echo $clientRoot; ?>/js/jquery.js"></script>
<script src="<?php echo $clientRoot; ?>/js/superfish.min.js"></script>
<script src="<?php echo $clientRoot; ?>/js/menu.js"></script>
<link href="<?php echo $clientRoot; ?>/css/component.css" type="text/css" rel="stylesheet" />
<div class="container">
    <div class="header-wrapper clearfix">
        <div class="top-menu-container">
            <div class="top-menu">
                <ul>
                    <li><a href="#">Contact Info</a></li>
                    <li><a href="#">Donate</a></li>
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
                </ul>
            </div>
        </div>
        <div class="main-header">
            <div class="header-logo">
                <a href="<?php echo $clientRoot; ?>/index.php"><img src="<?php echo $clientRoot; ?>/images/layout/new-logo.png" alt="Oregon Flora"></a>
            </div><!-- .logo -->
            <div class="search-wrapper">
		        <?php
		        //---------------------------QUICK SEARCH SETTINGS---------------------------------------
		        //Title text that will appear.
		        $searchText = '';

		        //Text that will appear on search button.
		        $buttonText = '<i class="fa fa-search"></i>';

		        //---------------------------DO NOT CHANGE BELOW HERE-----------------------------
		        include_once($SERVER_ROOT.'/classes/PluginsManager.php');
		        $pluginManager = new PluginsManager();
		        $quicksearch = $pluginManager->createQuickSearch($buttonText,$searchText);
		        echo $quicksearch;
		        ?>
            </div><!-- .search-wrapper -->
            <nav class="main-navigation" id="site-navigation">
                <button class="menu-toggle" id="menu-button" aria-controls="primary-menu" aria-expanded="false">&#8681; Menu</button>
                <ul class="nav-menu">
                    <li class="menu-item-has-children"><a href="#">Explore Our Site</a>
                        <ul>
                            <li><a href="#">Mapping</a></li>
                            <li><a href="#">Interactive Key</a></li>
                            <li><a href="#">Image Search</a></li>
                            <li><a href="#">OSU Herbarium</a></li>
                            <li><a href="#">Garden with Natives</a></li>
                            <li><a href="#">Plant Inventories</a></li>
                        </ul>
                    </li>
                    <li class="menu-item-has-children"><a href="#">Sitemap</a>
                        <ul>
                            <li><a href="#">Links</a></li>
                            <li><a href="#">Newsletter</a></li>
                            <li><a href="#">Rare Plant Guide</a></li>
                            <li><a href="#">Current News</a></li>
                        </ul>
                    </li>
                    <li class="menu-item-has-children"><a href="#">About</a>
                        <ul>
                            <li><a href="#">Mission</a></li>
                            <li><a href="#">Contact Info</a></li>
                            <li><a href="#">Project Participants</a></li>
                        </ul>
                    </li>
                    <li class="menu-item-has-children"><a href="#">Support</a>
                        <ul>
                            <li><a href="#">Donate</a></li>
                            <li><a href="#">Volunteer</a></li>
                            <li><a href="#">Merchandise</a></li>
                        </ul>
                    </li>
                </ul><!-- .nav -->
            </nav><!-- .main-nav -->
        </div><!--.main-header -->
    </div><!-- .header-wrapper -->
    <div class="content-wrapper" id="site-content">