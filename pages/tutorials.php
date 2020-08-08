<?php
//error_reporting(E_ALL);
include_once( "../config/symbini.php" );
header( "Content-Type: text/html; charset=" . $charset );
?>
<html>
<head>
    <title><?php echo $defaultTitle ?>How to get the most our of our site</title>
    <meta charset="UTF-8">
    <link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet"/>
    <link href="../css/main.css?<?php echo $CSS_VERSION_LOCAL; ?>" type="text/css" rel="stylesheet"/>
    <meta name='keywords' content=''/>
    <script type="text/javascript">
        <?php include_once( $serverRoot . '/config/googleanalytics.php' ); ?>
    </script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script type="text/javascript" src="video_modal.js"></script>
    <script src="https://kit.fontawesome.com/a01aa82192.js" crossorigin="anonymous"></script>
</head>
<body>
<?php
include( $serverRoot . "/header.php" );
?>

<div id="info-page">
    <section id="titlebackground" class="title-blueberry">
        <div class="inner-content">
            <h1>How to get the most our of our site</h1>
        </div>
    </section>
    <section>
        <!-- if you need a full width column, just put it outside of .inner-content -->
        <!-- .inner-content makes a column max width 1100px, centered in the viewport -->
        <div class="inner-content" id="tutorials-content">
            <!-- place static page content here. -->
            <h2>Tutorials and tips – in both video and textual form – to help unlock the power of OregonFlora.</h2>
            <p>OregonFlora is made for land managers, gardeners, scientists, restorationists, and plant lovers of all ages. You’ll find information about all the native and exotic plants of the state—ferns, conifers, grasses, herbs, and trees—that grow in the wild.</p>
            <div id="video-tutorial-top"></div>
            <p>Here are a series of tutorials and tips to help you get the most out of our site.</p>
            <h2>Tutorial and tip index:</h2>
            <div class="tutorial-list">
                <div class="row">
                    <div class="col-8 pl-sm-5">
                        <p>An introduction to OregonFlora</p>
                    </div>
                    <div class="col">
                        <p><a href="#video-card-intro">video</a></p>
                    </div>
                    <div class="col">
                        <p><a href="#text-card-intro">text</a></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-8 pl-sm-5">
                        <p>Taxon profile pages</p>
                    </div>
                    <div class="col">
                        <p><a href="#video-card-taxon">video</a></p>
                    </div>
                    <div class="col">
                        <p><a href="#text-card-taxon">text</a></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-8 pl-sm-5">
                        <p>Mapping</p>
                    </div>
                    <div class="col">
                        <p><a href="#video-card-mapping">video</a></p>
                    </div>
                    <div class="col">
                        <p><a href="#text-card-mapping">text</a></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-8 pl-sm-5">
                        <p>Interactive key</p>
                    </div>
                    <div class="col">
                        <p><a href="#video-card-key">video</a></p>
                    </div>
                    <div class="col">
                        <p><a href="#text-card-key">text</a></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-8 pl-sm-5">
                        <p>Plant Inventories</p>
                    </div>
                    <div class="col">
                        <p><a href="#video-card-inventory">video</a></p>
                    </div>
                    <div class="col">
                        <p><a href="#text-card-inventory">text</a></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-8 pl-sm-5">
                        <p>OSU Herbarium</p>
                    </div>
                    <div class="col">
                        <p><a href="#video-card-herbarium">video</a></p>
                    </div>
                    <div class="col">
                        <p><a href="#text-card-herbarium">text</a></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-8 pl-sm-5">
                        <p>Grow Natives</p>
                    </div>
                    <div class="col">
                        <p><a href="#video-card-natives">video</a></p>
                    </div>
                    <div class="col">
                        <p><a href="#text-card-natives">text</a></p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-8 pl-sm-5">
                        <p>Taxonomic Tree</p>
                    </div>
                    <div class="col">
                        <p><a href="#video-card-tree">video</a></p>
                    </div>
                    <div class="col">
                        <p><a href="#text-card-tree">text</a></p>
                    </div>
                </div>
            </div>

            <div class="row tutorials-video">

                <!-- Modal -->
                <div class="modal fade" id="videoModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
                    <div class="modal-dialog" role="document">
                        <div class="modal-content">

                            <div class="modal-body">

                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                                <!-- 16:9 aspect ratio -->
                                <div class="embed-responsive embed-responsive-16by9">
                                    <iframe class="embed-responsive-item" src="" id="video" allowscriptaccess="always" allow="autoplay"></iframe>
                                </div>

                            </div>

                        </div>
                    </div>
                </div>
                <h2>Video Tutorials</h2>
                <div class="row">
                    <div class="col-sm tutorials-video-card" id="video-card-intro">
                        <div class="video-image"><a href="#" type="button" class="btn video-modal-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/9ystxXKEOp4" data-target="#videoModal"><img src="<?php echo $CLIENT_ROOT; ?>/pages/images/YouTube-tutorial-Intro.png" alt="intro video"></a></div>
                        <h3>An Introduction to OregonFlora</h3>
                        <p>Get an overview of the powerful tools available on the website.</p>
                        <p>Text-based tutorial <a href="#text-card-intro">here</a>.</p>
                    </div>
                    <div class="col-sm tutorials-video-card" id="video-card-taxon">
                        <div class="video-image"><a href="#" type="button" class="btn video-modal-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/HwtEXcTO9jA" data-target="#videoModal"><img src="<?php echo $CLIENT_ROOT; ?>/pages/images/YouTube-tutorial-Intro.png" alt="intro video"></a></div>
                        <h3>Taxon profile pages</h3>
                        <p>Comprehensive information—gathered in one location—for each of the ~4,700 vascular plant in the state!</p>
                        <p>Text-based tutorial <a href="#text-card-taxon">here</a>.</p>
                    </div>
                </div>
                <div class="go-top">
                    <p>
                        <a href="#video-tutorial-top" class="toptext">
                            TOP<br />
                            <i class="fas fa-chevron-up"></i>
                        </a>
                    </p>
                </div>
                <div class="row">
                    <div class="col-sm tutorials-video-card" id="video-card-mapping">
                        <div class="video-image"><a href="#" type="button" class="btn video-modal-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/Y2sdnibf1O8" data-target="#videoModal"><img src="<?php echo $CLIENT_ROOT; ?>/pages/images/YouTube-tutorial-Intro.png" alt="intro video"></a></div>
                        <h3>Mapping</h3>
                        <p>Draw a shape on the interactive map to learn what plants occur there or enter plant names to see their distribution.</p>
                        <p>Text-based tutorial <a href="#text-card-mapping">here</a>.</p>
                    </div>
                    <div class="col-sm tutorials-video-card" id="video-card-key">
                        <div class="video-image"><a href="#" type="button" class="btn video-modal-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/DKxoEEwL3V4" data-target="#videoModal"><img src="<?php echo $CLIENT_ROOT; ?>/pages/images/YouTube-tutorial-Intro.png" alt="intro video"></a></div>
                        <h3>Interactive Key</h3>
                        <p>An identification tool based on the plant features you recognize! Mark your location on a map to get a list of species found there, then narrow the possibilities.</p>
                        <p>Text-based tutorial <a href="#text-card-key">here</a>.</p>
                    </div>
                </div>
                <div class="go-top">
                    <p>
                        <a href="#video-tutorial-top" class="toptext">
                            TOP<br />
                            <i class="fas fa-chevron-up"></i>
                        </a>
                    </p>
                </div>
                <div class="row">
                    <div class="col-sm tutorials-video-card" id="video-card-inventory">
                        <div class="video-image"><a href="#" type="button" class="btn video-modal-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/Y2sdnibf1O8" data-target="#videoModal"><img src="<?php echo $CLIENT_ROOT; ?>/pages/images/YouTube-tutorial-Intro.png" alt="intro video"></a></div>
                        <h3>Plant inventories</h3>
                        <p>In-depth information about the plants of a defined place. Choose from thousands of lists.</p>
                        <p>Text-based tutorial <a href="#text-card-inventory">here</a>.</p>
                    </div>
                    <div class="col-sm tutorials-video-card" id="video-card-herbarium">
                        <div class="video-image"><a href="#" type="button" class="btn video-modal-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/OAz83vUq-bs" data-target="#videoModal"><img src="<?php echo $CLIENT_ROOT; ?>/pages/images/YouTube-tutorial-Intro.png" alt="intro video"></a></div>
                        <h3>OSU Herbarium</h3>
                        <p>All databased specimen records of OSU Herbarium’s vascular plants, mosses, lichens, fungi, and algae in a searchable, downloadable format.</p>
                        <p>Text-based tutorial <a href="#text-card-herbarium">here</a>.</p>
                    </div>
                </div>
                <div class="go-top">
                    <p>
                        <a href="#video-tutorial-top" class="toptext">
                            TOP<br />
                            <i class="fas fa-chevron-up"></i>
                        </a>
                    </p>
                </div>
                <div class="row">
                    <div class="col-sm tutorials-video-card" id="video-card-natives">
                        <div class="video-image"><a href="#" type="button" class="btn video-modal-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/1on5abHiruM" data-target="#videoModal"><img src="<?php echo $CLIENT_ROOT; ?>/pages/images/YouTube-tutorial-Intro.png" alt="intro video"></a></div>
                        <h3>Grow Natives</h3>
                        <p>Find the right native species for your garden or landscape! Browse plant collections for suggested garden types, or filter on plant characters.</p>
                        <p>Text-based tutorial <a href="#text-card-natives">here</a>.</p>
                    </div>
                    <div class="col-sm tutorials-video-card" id="video-card-tree">
                        <div class="video-image"><a href="#" type="button" class="btn video-modal-btn" data-toggle="modal" data-src="https://www.youtube.com/embed/DKxoEEwL3V4" data-target="#videoModal"><img src="<?php echo $CLIENT_ROOT; ?>/pages/images/YouTube-tutorial-Intro.png" alt="intro video"></a></div>
                        <h3>Taxonomic Tree</h3>
                        <p>Scientific names organized to show taxonomic rank.</p>
                        <p>Text-based tutorial <a href="#text-card-tree">here</a>.</p>
                    </div>
                </div>
                <div class="go-top">
                    <p>
                        <a href="#video-tutorial-top" class="toptext">
                            TOP<br />
                            <i class="fas fa-chevron-up"></i>
                        </a>
                    </p>
                </div>
            </div>
            <div class="row tutorials-text">
                <h2>Text Tutorials</h2>
                <div class="tutorials-text-card" id="text-card-mapping">
                    <h3>Mapping</h3>
                    <figure class="figure">
                        <a href="#video-card-mapping">
                            <img src="<?php echo $CLIENT_ROOT; ?>/pages/images/YouTube-tutorial-Intro.png" alt="intro video">
                            <figcaption class="figure-caption">Video version of this tutorial.</figcaption>
                        </a>
                    </figure>
                    <p>This is a GIS-based mapping tool. Shapefiles can be both imported and exported, making it possible to map other datasets together with OregonFlora plant distribution data.</p>
                    <ul>
                        <li>Map settings Set some of the features for your mapping session using the options in the blue selection box at the top of the map.
                            <ol>
                                <li>Base Layer—eight base maps to underlie the data mapping</li>
                                <li>Draw—select an option to create your shape</li>
                                <li>Settings—select how points are clustered</li>
                                <li>Tools—additional data visualization options</li>
                                <li>Layers—Ecoregion and County layers which, when selected, are added to your mapping session</li>
                            </ol>
                        </li>
                        <li>How to discover plant diversity within a user-defined area
                            <ol>
                                <li>Click the “Open” button in the upper left corner of the map.</li>
                                <li>In the dropdown box labeled Taxa, begin typing the plant name. More than one name can be entered; separate names with a comma.</li>
                                <li>Click on the green “Load Records” button.</li>
                            </ol>
                        </li>
                        <li>Viewing mapping results
                            <ol>
                                <li>The Records tab lists all the plant occurrences from your search.
                                    <ol type="a">
                                        <li>Click on a name in the Collector column to view the details of that record.</li>
                                        <li>Select particular records to be copied to the Selections tab by using the checkboxes to the left of each record.</li>
                                    </ol>
                                </li>
                                <li>The Taxa tab lists all taxa returned in your search.
                                    <ol type="a">
                                        <li>Each vascular plant name is a link to the profile page of that taxon.</li>
                                        <li>Taxa that are not vascular plants are included in the list; these do not have a profile page.</li>
                                    </ol>
                                </li>
                                <li>Pressing the <Alt> button while clicking on any dot on the map opens a popup with details about that record.</li>
                                <li>Click the green “Download” button to download results presented on any tab.</li>
                            </ol>
                        </li>
                        <li>Rare taxa
                            <p>Currently, access to rare plant (those state and federally listed, and on ORBIC List 1) locality data is restricted to authorized users; the message “There were no records matching your query” appears to users not logged in.  OregonFlora is working with these agencies to establish data access policies.</p>
                            <p>To request an account granting access to restricted information, click the “Log In” link on the top banner, complete and submit the profile form. Please explain in the Biography section why you need access.</p>
                        </li>
                        <li>Additional help
                            <p>In addition to the OregonFlora video tutorial, Symbiota webinars covering basic and advanced (including vector tools) features are also available.</p>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="go-top">
                <p>
                    <a href="#video-tutorial-top" class="toptext">
                        TOP<br />
                        <i class="fas fa-chevron-up"></i>
                    </a>
                </p>
            </div>
            <div class="row tutorials-text">
                <div class="tutorials-text-card" id="text-card-herbarium">
                    <h3>OSU Herbarium</h3>
                    <figure class="figure">
                        <a href="#video-card-herbarium">
                            <img src="<?php echo $CLIENT_ROOT; ?>/pages/images/YouTube-tutorial-Intro.png" alt="intro video">
                            <figcaption class="figure-caption">Video version of this tutorial.</figcaption>
                        </a>
                    </figure>
                    <p>This feature gives access to all the digitized collections housed in the Herbarium on the Oregon State University Herbarium in Corvallis:  vascular plants, algae, bryophytes, fungi, and lichens from Oregon and beyond. Select from a number of filtering options to narrow your results.</p>
                    <p>Things to note:</p>
                    <ul>
                        <li>Currently only the vascular plants names will appear in a dropdown, but you may type in the scientific names of organisms from any group, and all available records will be returned.</li>
                        <li>Map-based searches can be performed in the “Latitude and Longitude” section by clicking on the earth icon in the lower right corner of each subsection.</li>
                        <li>View results in table format using the checkbox at the top of the search page, or from the top of the results page.</li>
                        <li>Searches can be limited to selected collections by opening the “Collections” tab (accessed from the navigation path at top of page) and using the checkboxes next to each collection name. Clear these selections using the “Reset Form” button on the search page.</li>
                        <li>Results can be downloaded by clicking on the icon at the top of the page.</li>
                    </ul>
                </div>
            </div>
            <div class="go-top">
                <p>
                    <a href="#video-tutorial-top" class="toptext">
                        TOP<br />
                        <i class="fas fa-chevron-up"></i>
                    </a>
                </p>
            </div>
        </div> <!-- .inner-content -->
    </section>
</div>
<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>