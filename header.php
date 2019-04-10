<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
<link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,300i,400,400i,600,600i,700,700i" rel="stylesheet">
<link rel='stylesheet' href='//maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css' type='text/css' media='all' />
<script src="<?php echo $clientRoot; ?>/js/jquery-ui-1.12.1/external/jquery/jquery.js"></script>
<script src="<?php echo $clientRoot; ?>/js/jquery-ui-1.12.1/jquery-ui.js"></script>
<script src="<?php echo $clientRoot; ?>/js/superfish.min.js"></script>
<script src="<?php echo $clientRoot; ?>/js/menu.js"></script>
<link href="<?php echo $clientRoot; ?>/css/component.css" type="text/css" rel="stylesheet" />
<div class="container" id="maintable">
    <div class="header-wrapper clearfix">
        <div class="top-menu-container">
            <div class="top-menu">
                <ul>
                    <li><a href="<?php echo $clientRoot; ?>/pages/contact.php">Contact Info</a></li>
                    <li><a href="<?php echo $clientRoot; ?>/pages/donate.php">Donate</a></li>
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
		        $searchText = 'Plant Taxon Search';
		        //Placeholder text that will appear.
		        $placeholderText = "Enter taxon name";

		        //Text that will appear on search button.
		        $buttonText = '<i class="fa fa-search"></i>';

		        //---------------------------DO NOT CHANGE BELOW HERE-----------------------------
		        include_once($SERVER_ROOT.'/classes/PluginsManager.php');
		        $pluginManager = new PluginsManager();
		        $quicksearch = $pluginManager->createQuickSearch($buttonText,$searchText,$placeholderText);
		        echo $quicksearch;
		        ?>
            </div><!-- .search-wrapper -->
            <nav class="main-navigation" id="site-navigation">
                <button class="menu-toggle" id="menu-button" aria-controls="primary-menu" aria-expanded="false">&#8681; Menu</button>
                <ul class="nav-menu">
                    <li class="menu-item-has-children"><a href="#">Explore Our Site</a>
                        <ul>
                            <li><a href="<?php echo $clientRoot; ?>/spatial/index.php">Mapping</a></li>
                            <li><a href="<?php echo $clientRoot; ?>/checklists/dynamicmap.php?interface=key">Interactive Key</a></li>
                            <li><a href="<?php echo $clientRoot; ?>/projects/index.php">Plant Inventories</a></li>
                            <li><a href="<?php echo $clientRoot; ?>/collections/harvestparams.php?db[]=5,8,10,7,238,239,240,241">OSU Herbarium</a></li>
                            <li><a href="<?php echo $clientRoot; ?>/garden/index.php">Gardening with Natives</a></li>
                            <li><a href="<?php echo $clientRoot; ?>/imagelib/search.php">Image Search</a></li>
                            <li><a href="<?php echo $clientRoot; ?>/taxa/admin/taxonomydisplay.php">Taxonomic Tree</a></li>
                        </ul>
                    </li>
                    <li class="menu-item-has-children"><a href="#">Resources</a>
                        <ul>
                            <li><a href="<?php echo $clientRoot; ?>/pages/whats-new.php">What's New</a></li>
                            <li><a href="<?php echo $clientRoot; ?>/newsletters/index.php">Archived Newsletter</a></li>
                            <li><a href="<?php echo $clientRoot; ?>/pages/links.php">Links</a></li>
                        </ul>
                    </li>
                    <li class="menu-item-has-children"><a href="#">About</a>
                        <ul>
                            <li><a href="<?php echo $clientRoot; ?>/pages/mission.php">Mission and History</a></li>
                            <li><a href="<?php echo $clientRoot; ?>/pages/contact.php">Contact Info</a></li>
                            <li><a href="<?php echo $clientRoot; ?>/pages/project-participants.php">Project Participants</a></li>
                        </ul>
                    </li>
                    <li class="menu-item-has-children"><a href="#">Support</a>
                        <ul>
                            <li><a href="<?php echo $clientRoot; ?>/pages/donate.php">Donate</a></li>
                            <li><a href="<?php echo $clientRoot; ?>/pages/volunteer.php">Volunteer</a></li>
                            <li><a href="<?php echo $clientRoot; ?>/pages/merchandise.php">Merchandise</a></li>
                        </ul>
                    </li>
                </ul><!-- .nav -->
            </nav><!-- .main-nav -->
        </div><!--.main-header -->
    </div><!-- .header-wrapper -->
    <div class="content-wrapper" id="site-content">