<?php
//error_reporting(E_ALL);
include_once( "../config/symbini.php" );
header( "Content-Type: text/html; charset=" . $charset );
?>
<html>
<head>
    <title><?php echo $defaultTitle ?> Our Mission</title>
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
    <section id="titlebackground" class="title-leaf">
        <div class="inner-content">
            <h1>Mission and History</h1>
        </div>
    </section>
    <section>
        <!-- if you need a full width column, just put it outside of .inner-content -->
        <!-- .inner-content makes a column max width 1100px, centered in the viewport -->
        <div class="inner-content">
        <!-- place static page content here. -->
            <h2 class="subhead">Our mission is to present comprehensive information about the vascular plants and the biodiversity of Oregon to meet the needs of diverse audiences.</h2>
            <div class="row two-col-row">
                <div class="column-right col-md-4 order-1 order-md-2 pt-5">
                    <figure class="figure">
                        <img
                                srcset="images/volunteer1.png 1x, images/volunteer1@2x.png 2x"
                                src="images/volunteer1.png"
                                class="figure-img img-fluid z-depth-1"
                                alt="Volunteer 1">
                        <figcaption class="figure-caption">A selfless volunteer tabling a table for the Oregon Flora project, which you should donate to at least so we can try out a four-line caption to show how it looks.</figcaption>
                    </figure>
                </div>
                <div class="column-main col-md-8 order-2 order-md-1 pr-md-5">
                    <h2>&nbsp;Scope of Project</h2>
                    <p>OregonFlora addresses the ~4,700 vascular plant species, subspecies, and varieties (taxa) of Oregon that grow in the wild without cultivation. These include:</p>
                    <ul>
                        <li>all extant native taxa</li>
                        <li>native taxa thought to have gone extinct in Oregon in historical times</li>
                        <li>exotic (non-native), cultivated, or weedy taxa that have naturalized</li>
                        <li>interspecific hybrids that are frequent or self-maintaining</li>
                        <li>infrequently collected exotic taxa (e.g., ballast plants and current waifs)</li>
                        <li>unnamed taxa in process of being described</li>
                    </ul>
                    <p>We define “native” as a plant taxon which has established in the landscape independently from direct or indirect human intervention. Native species include those found in Oregon that are new to science and recently described, are disjunct in Oregon if it is considered native in a nearby state, and/or are—to the best of our knowledge—considered an element of Oregon plant life prior to European settlement.</p>
                </div>
            </div>
            <div class="row two-col-row">
                <div class="column-right col-md-4 order-1 order-md-2 pt-5">
                    <figure class="figure">
                        <img
                                srcset="images/Columbia_Gorge_volunteer_pair.png 1x, images/Columbia_Gorge_volunteer_pair@2x.png 2x"
                                src="images/Columbia_Gorge_volunteer_pair.png"
                                class="figure-img img-fluid z-depth-1"
                                alt="Volunteer 2"">
                        <figcaption class="figure-caption">Two people in the field bravely exploring what a two-line caption looks like.</figcaption>
                    </figure>
                    <figure class="figure">
                        <img src="images/volunteer3.png" class="figure-img img-fluid z-depth-1" alt="Volunteer 3"">
                        <figcaption class="figure-caption">Photo of Persona Person by Jane Doe.</figcaption>
                    </figure>
                </div>
                <div class="column-main col-md-8 order-2 order-md-1 pr-md-5">
                    <h2>History</h2>
                    <h3>1994-2004</h3>
                    <p>Our program, then the Oregon Flora Project (OFP), was begun in 1994 by Scott Sundberg at Oregon State University as an effort to prepare a new flora of the vascular plants of Oregon. To ensure that the information remained comprehensive and up-to-date, a database was created to keep track of the nomenclature, synonymy, and literature references for all of Oregon’s plants. It also was used to develop a version of the Flora that could be presented online as an interactive, digital resource.</p>
                    <p>The Oregon Plant Atlas was initiated in 1995, and used the skills of geographers, programmers, and botanists to create an interactive online tool for mapping plant occurrence data from the Oregon Flora Project databases. This effort received a significant boost in 2001 with a grant from the OR/WA Bureau of Land Management to add to the database a record for each taxon found in every county. Sundberg hired the first staff members at this time, including Thea Jaster and later Katie Mitchell.</p>
                    <figure class="figure figure-inline">
                        <img
                            srcset="images/oxa_ore_2338b.png 1x, images/oxa_ore_2338b@2x.png 2x"
                            src="images/oxa_ore_2338b.png"
                            class="figure-img img-fluid z-depth-1"
                            alt="Volunteer 2"">
                        <figcaption class="figure-caption">Oxalis oregana, or Oregon wood sorrel (Gerald D. Carr).</figcaption>
                    </figure>
                    <p>Collaboration with the Northwest Alliance for Computational Science and Engineering at OSU resulted in the award of a grant (2001-2004) from the National Science Foundation to design and develop software for presenting Oregon Flora Project data online. A Photo Gallery was also added featuring field photos contributed by plant enthusiasts; the plant identification for each was confirmed by OFP staff prior to posting online. Digitized images of herbarium specimens were added as a component of the Photo Gallery.</p>
                    <p>The Oregon State University Herbarium and the Oregon Flora Project collaborated on a National Science Foundation proposal (2003-2006) to database and georeference label data from all Oregon herbarium specimens not yet included in the Oregon Plant Atlas. OregonFlora maintains this dataset and serves as the public face to the OSU Herbarium by providing images of herbarium specimens, searchable label data, and the ability to map plant occurrences through the OregonFlora website.</p>
                    <p>Collaboration with the Northwest Alliance for Computational Science and Engineering at OSU resulted in the award of a grant (2001-2004) from the National Science Foundation to design and develop software for presenting Oregon Flora Project data online. A Photo Gallery was also added featuring field photos contributed by plant enthusiasts; the Collaboration with the Northwest Alliance for Computational Science and Engineering at OSU resulted in the award of a grant (2001-2004) from the National Science Foundation to design and develop software for presenting Oregon Flora Project data online. A Photo Gallery was also added featuring field photos contributed by plant enthusiasts; the plant identification for each was confirmed by OFP staff prior to posting online. Digitized images of herbarium specimens were added as a component of the Photo Gallery.</p>
                    <p>The Oregon State University Herbarium and the Oregon Flora Project collaborated on a National Science Foundation proposal (2003-2006) to database and georeference label data from all Oregon herbarium specimens not yet included in the Oregon Plant Atlas. OregonFlora maintains this dataset and serves as the public face to the OSU Herbarium by providing images of herbarium specimens, searchable label data, and the ability to map plant occurrences through the OregonFlora website.</p>
                </div>
            </div>
            <div class="row two-col-row">
                <div class="column-right col-md-4 order-1 order-md-2 pt-5">
                    <figure class="figure">
                        <img
                                srcset="images/volunteer2.png 1x, images/volunteer2@2x.png 2x"
                                src="images/volunteer2.png"
                                class="figure-img img-fluid z-depth-1"
                                alt="Volunteer 2"">
                        <figcaption class="figure-caption">Or maybe it’s a three line quote ensure that the information remained comprehensive and up-to-date about this unique project.</figcaption>
                    </figure>
                </div>
                <div class="column-main col-md-8 order-2 order-md-1 pr-md-5">
                    <h3>2004-2010</h3>
                    <p>2004-2010 In December 2004, the Oregon Flora Project suffered a great loss with the death of its director and founder, Scott Sundberg. The program continued, however, following a path to its completion that was Scott’s vision. Linda Hardison assumed the position of director, and existing staff members maintained their essential roles.</p>
                    <p>In early 2008 the Project was halted indefinitely due to a lack of funds. Through the timely and generous support of the John and Betty Soreng Environmental Fund of the Oregon Community Foundation, all staff members were rehired, and the operations of the Project resumed in Autumn 2008. The sustained support of this fund has allowed OFP to bring to fruition public access to every facet of the Project through its “digital flora” website: the Photo Gallery (2009), version 2.0 of the Oregon Plant Atlas (2010), and the Vascular Plant Checklist (2011). It has also enabled the production of the Flora volumes.</p>
                </div>

            </div>
        </div> <!-- .inner-content -->
    </section>
</div> <!-- #info-page -->

<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>