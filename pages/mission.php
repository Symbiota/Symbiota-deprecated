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
    <section id="titlebackground" style="background-image: url('/images/header/h1leaf.png');">
        <div class="inner-content">
            <h1>Mission and History</h1>
        </div>
    </section>
    <section>
        <!-- if you need a full width column, just put it outside of .inner-content -->
        <!-- .inner-content makes a column max width 1100px, centered in the viewport -->
        <div class="inner-content">
        <!-- place static page content here. -->
            <h2 class="subhead">Our mission is to engage researchers, plant growers, land managers, and nature enthusiasts by creating the basis of knowledge of Oregon&rsquo;s vascular plants.</h2>
            <div class="row">
                <div id="column-main" class="col-lg-8">
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
                    <p>We define <strong>&ldquo;native&rdquo;</strong> as a plant taxon which has established in the landscape independently from direct or indirect human intervention. Native species include those found in Oregon that are new to science and recently described, are disjunct in Oregon if it is considered native in a nearby state, and/or are&mdash;to the best of our knowledge&mdash;considered an element of Oregon plant life prior to European settlement.</p>
                    <p>A <strong>non-native</strong>, or exotic plant is one from distant parts of North America or from other continents that established in Oregon post-European settlement. Examples include weeds, naturalized escapes, waifs, and ballast plants.</p>
                    <p>Two categories of non-native plants fall within the scope of the project:</p>
                    <ul>
                        <li>Escaped cultivated plants: agricultural and garden taxa that have persisted in the wild for at least 3-5 years and have spread beyond the area where they were originally cultivated.</li>
                        <li>Noncultivated exotic plants: weeds (nuisance non-native taxa), waifs (solitary or small groups of non-native plants persisting for only one season), ballast plants (waifs growing on ship ballast).</li>
                    </ul>
                    <h2>History</h2>
                    <h3>1994-2004</h3>
                    <p>Our program, then the Oregon Flora Project (OFP), was begun in 1994 by Scott Sundberg at Oregon State University as an effort to prepare a new flora of the vascular plants of Oregon. To ensure that the information remained comprehensive and up-to-date, a database was created&nbsp; the nomenclature, synonymy, and literature references for all of Oregon&rsquo;s plants. It also was used to develop a version of the Flora that could be presented online as an interactive, digital resource.</p>
                    <p>The Oregon Plant Atlas was initiated in 1995, and used the skills of geographers, programmers, and botanists to create an interactive online tool for mapping plant occurrence data from the Oregon Flora Project databases. This effort received a significant boost in 2001 with a grant from the OR/WA Bureau of Land Management to add a record for each taxon found in every county to the database. Sundberg hired the first staff members at this time, including Thea Jaster and later Katie Mitchell.</p>
                    <p>Collaboration with the Northwest Alliance for Computational Science and Engineering at OSU resulted in the award of a grant (2001-2004) from the National Science Foundation to design and develop software for presenting Oregon Flora Project data online. A Photo Gallery was also added featuring field photos contributed by plant enthusiasts; the plant identification for each was confirmed by OFP staff prior to posting online. Digitized images of herbarium specimens were added as a component of the Photo Gallery.</p>
                    <p>The Oregon State University Herbarium and the Oregon Flora Project collaborated on a National Science Foundation proposal (2003-2006) to database and georeference label data from all Oregon herbarium specimens not yet included in the Oregon Plant Atlas. OregonFlora maintains this dataset and serves as the public face to the OSU Herbarium by providing images of herbarium specimens, searchable label data, and the ability to map plant occurrences through the OregonFlora website.</p>
                    <h3>2004-2010</h3>
                    <p>In December 2004, the Oregon Flora Project suffered a great loss with the death of its director and founder, Scott Sundberg. The program continued, however, following a path to its completion that was Scott&rsquo;s vision. Linda Hardison assumed the position of director, and existing staff members maintained their essential roles.</p>
                    <p>In early 2008 the Project was halted indefinitely due to a lack of funds. Through the timely and generous support of the John and Betty Soreng Environmental Fund of the Oregon Community Foundation, all staff members were rehired, and the operations of the Project resumed in Autumn 2008. The sustained support of this fund has allowed OFP to bring to fruition public access to every facet of the Project through its &ldquo;digital flora&rdquo; website: the Photo Gallery (2009), version 2.0 of the Oregon Plant Atlas (2010), and the Vascular Plant Checklist (2011). It has also enabled the production of the <em>Flora</em> volumes.&nbsp;</p>
                    <h3>2010 - present</h3>
                    <p>With the Checklist serving as a robust foundation, OFP efforts focused on the production of the printed <em>Flora of Oregon</em>. Stephen Meyers was hired in 2010 as the taxonomic director to oversee the writing of the floristic treatments and identification keys. In 2012 artist John Myers joined the staff to contribute artwork for the first ever illustrated flora for the state of Oregon. That same year, an eleven-member advisory board was established, with members representing the diversity of stakeholders that are informed by the work of the OFP. BRIT Press was selected to publish the flora, and with the design and layout expertise of Tanya Harvey, Volume 1 of the <em>Flora of Oregon</em> was published in September 2015.</p>
                    <p>As the body of knowledge for the nearly 4,700 plant taxa grew, so did the capacity to apply it. An OFP strategic plan, first drafted in 2015, identified three strategic initiatives:&nbsp; using native species in gardens and planted environments, increasing biodiversity in working agricultural lands, and science education.</p>
                    <p>Through a partnership with Metro (Portland) and the Adult Conservation Educators Northwest, OFP assumed oversight of their dataset of native plants used for gardening and landscaping in western Oregon. Funding from the Oregon Dept. of Agriculture&rsquo;s Specialty Crop Block Grant program (2014-2016) helped OFP develop information promoting use of natives in gardening and, with Metro, design an interactive portal to share the data on the OFP&rsquo;s redesigned website.</p>
                    <p>In 2016, the OFP initiated a redesign of its website using the Symbiota software platform. This has allowed adoption of more versatile ways to analyze and communicate information, and the linking of OFP plant data to other datasets. The program also changed its name to OregonFlora.</p>
                    <p>As part of Oregon State University, the state&rsquo;s land grant institution, OregonFlora is researching effective ways to return plant diversity and natural habitats to working agricultural lands. Grants from the Oregon Watershed Enhancement Board (2017) and the Oregon Natural Resources Conservation Service (2018) and the Department of State Lands (2020) have helped to launch studies on OSU lands to restore native habitat in wet pastures and oak woodlands using grazers, fire, and beaver.</p>
                    <p>Publication of Volumes 2 and 3 of the <em>Flora of Oregon</em> remain a priority for OregonFlora. The knowledge gained from this floristic project will drive far-reaching research initiatives, land management practices and activities by people and organizations nationwide. Presenting the <em>Flora</em> as both printed books and through an interactive website makes the information accessible to everyone.</p>
                    <h2>Project Organization</h2>
                    <p>OregonFlora is based in the <a href="https://bpp.oregonstate.edu">Department of Botany &amp; Plant Pathology</a> (BPP) at Oregon State University. We work closely with the <a href="https://bpp.oregonstate.edu/herbarium">OSU Herbarium</a>. &nbsp;BPP supports our program by providing indirect costs and office space. Grants and charitable donations fund all OregonFlora salaries, employee benefits, and direct operating expenses. The <a href="http://npsoregon.org/">Native Plant Society of Oregon</a> has been a sponsor of OregonFlora since its inception in 1994. The <a href="https://agresearchfoundation.oregonstate.edu/">Agricultural Research Foundation</a>, a nonprofit 501(c)3 affiliated with OSU, serves as our fiscal agent.</p>

                </div>
                <div id="column-right" class="col-lg-4">
                    <figure class="figure">
                        <img src="images/volunteer3.png" class="figure-img img-fluid z-depth-1" alt="Volunteer 3" style="width: 400px">
                        <figcaption class="figure-caption">Photo of Persona Person by Jane Doe.</figcaption>
                    </figure>
                    <figure class="figure">
                        <img src="images/volunteer1.png" class="figure-img img-fluid z-depth-1" alt="Volunteer 1" style="width: 400px">
                        <figcaption class="figure-caption">Photo of Humana Human by John Doe in a two line caption that looks like this.</figcaption>
                    </figure>
                    <figure class="figure">
                        <img src="images/volunteer2.png" class="figure-img img-fluid z-depth-1" alt="Volunteer 2" style="width: 400px">
                        <figcaption class="figure-caption">Or maybe it’s a three line quote ensure that the information remained comprehensive and up-to-date about this unique project.</figcaption>
                    </figure>
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