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
            <h2>Tutorials and tips – in both video and textual form – to help unlock the power of Oregon Flora.</h2>
            <p>OregonFlora makes information about Oregon plants accessible to diverse audiences: scientists, restorationists, gardeners, land managers, and plant enthusiasts of all ages. We focus on the vascular plants of the state—ferns, conifers, grasses, herbs, and trees—that grow in the wild. We communicate data through our website, app, custom data requests, and the Flora of Oregon books.</p>
            <p>Now, OregonFlora has joined forces with Symbiota to present our website as a Symbiota portal! Here are a series of tutorials and tips to help you get the most out of our site.</p>
            <h2>Tutorial and tip index:</h2>
            <div class="row tutorial-list">
                <div class="col-sm">
                    <p>Video tutorials:</p>
                    <ul>
                        <li><a href="#">An introduction to Oregon Flora</a></li>
                        <li><a href="#">Taxon profile pages</a></li>
                        <li><a href="#">Mapping</a></li>
                        <li><a href="#">Interactive key</a></li>
                        <li><a href="#">Plant Inventories</a></li>
                        <li><a href="#">OSU Herbarium</a></li>
                    </ul>
                </div>
                <div class="col-sm">
                    <p>Text-based tutorials and tips:</p>
                    <ul>
                        <li><a href="#">An introduction to Oregon Flora in words</a></li>
                        <li><a href="#">Text on taxon profile pages</a></li>
                        <li><a href="#">Phrases ’n phonemes on mapping</a></li>
                        <li><a href="#">Letters in order on the Interactive key</a></li>
                        <li><a href="#">A catalog on Plant Inventories</a></li>
                        <li><a href="#">A lovely sonnet on the OSU Herbarium</a></li>
                    </ul>
                </div>
            </div>
            <div class="row tutorials-video">
                <h2>Video Tutorials</h2>
                <div class="row">
                    <div class="col-sm tutorials-video-card">
                        <div class="video-image"><a href="#"><img src="/pages/images/YouTube-tutorial-Intro.png" alt="intro video"></a></div>
                        <h3>An Introduction to Oregon Flora</h3>
                        <p>All databased specimen records of OSU Herbarium’s vascular plants, mosses, lichens, fungi, and algae in a searchable, downloadable format.</p>
                        <p>Text-based tutorial here.</p>
                    </div>
                    <div class="col-sm tutorials-video-card">
                        <div class="video-image"><a href="#"><img src="/pages/images/YouTube-tutorial-Intro.png" alt="intro video"></a></div>
                        <h3>Taxon profile pages</h3>
                        <p>Comprehensive information, gathered in one location—for each of the ~4,700 vascular plants in the state!</p>
                        <p>Text-based tutorial here.</p>
                    </div>
                </div>
                <div class="row">
                    <div class="col-sm tutorials-video-card">
                        <div class="video-image"><a href="#"><img src="/pages/images/YouTube-tutorial-Intro.png" alt="intro video"></a></div>
                        <h3>Mapping</h3>
                        <p>Draw a shape on the interactive map to learn what plant diversity is found there, or enter plant names to view their distribution.</p>
                        <p>Text-based tutorial here.</p>
                    </div>
                    <div class="col-sm tutorials-video-card">
                        <div class="video-image"><a href="#"><img src="/pages/images/YouTube-tutorial-Intro.png" alt="intro video"></a></div>
                        <h3>Interactive Key</h3>
                        <p>An identification tool based on the plant features you recognize! Start with a list of species, then narrow the possibilities.</p>
                        <p>Text-based tutorial here.</p>
                    </div>
                </div>
            </div>
            <div class="row tutorials-text">
                <h2>Text Tutorials</h2>
                <div class="tutorials-text-card">
                    <h3>An Introduction to Oregon Flora</h3>
                    <figure class="figure">
                        <img src="/pages/images/YouTube-tutorial-Intro.png" alt="intro video">
                        <figcaption class="figure-caption">Video version of this tutorial.</figcaption>
                    </figure>
                    <p>At vero eos et accusamus et iusto odio dignissimos ducimus qui blanditiis praesentium voluptatum deleniti atque corrupti quos dolores et quas molestias excepturi sint occaecati cupiditate non provident, similique sunt in culpa qui officia deserunt mollitia animi, id est laborum et dolorum fuga. Et harum quidem rerum facilis est et expedita distinctio. Nam libero tempore, cum soluta nobis est eligendi optio cumque nihil impedit quo minus id quod maxime placeat facere possimus, omnis voluptas assumenda est, omnis dolor repellendus. Temporibus autem quibusdam et aut officiis debitis aut rerum necessitatibus saepe eveniet ut et voluptates repudiandae sint et molestiae non recusandae. Itaque earum rerum hic tenetur a sapiente delectus, ut aut reiciendis voluptatibus maiores alias consequatur aut perferendis doloribus asperiores repellat.</p>
                    <p><strong>Things to note:</strong></p>
                    <ul>
                        <li>At vero eos et accusamus et iusto odio dignissimos ducimus</li>
                        <li>Qui blanditiis praesentium voluptatum deleniti atque corrupti quos </li>
                        <li>Dolores et quas molestias excepturi sint occaecati cupiditate non provident</li>
                    </ul>
                </div>
                </div>
            </div>

        </div> <!-- .inner-content -->
    </section>
</div>
<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>