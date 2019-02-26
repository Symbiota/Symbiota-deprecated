<?php
//error_reporting(E_ALL);
include_once( "../config/symbini.php" );
header( "Content-Type: text/html; charset=" . $charset );
?>
<html>
<head>
    <title><?php echo $defaultTitle ?> Tips for using this site</title>
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.6/dist/jquery.fancybox.min.css" />
<script src="https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.6/dist/jquery.fancybox.min.js"></script>

<!-- if you need a full width colum, just put it outside of .inner-content -->
<!-- .inner-content makes a column max width 1100px, centered in the viewport -->
<div class="inner-content">
    <!-- place static page content here. -->
    <h1>Tips for Using This Site</h1>
    <p>The website tools described here can be accessed from the dropdowns in the navigation bar, or by clicking on the labeled images at the top of the home page.</p>
    <p><i>Click the video icon associated with each section to view the associated video.</i></p>
    <h3>Plant Taxon Search &nbsp; <a data-fancybox href="https://youtu.be/HwtEXcTO9jA" class="btn light-purple-btn"><i class="fa fa-film"></i></a></h3>
    <p>This tool is found in the upper right corner of each page. Start typing to access a dropdown list of all vascular plant scientific names. Select from the list, click on the magnifying glass icon, and the profile page for that taxon will open. There is a taxon profile page for every one of the ~4,700 taxa&mdash;i.e., species, subspecies, or variety&mdash;of Oregon vascular plants.</p>
    <p><em>Things to note:</em></p>
    <ul>
        <li>The profile page displayed reflects the scientific name accepted by OregonFlora. For example, if you entered &ldquo;<em>Mahonia nervosa</em>&rdquo; in the taxon search box, the page returned is titled &ldquo;<em>Berberis nervosa.</em>&rdquo; All synonyms for a plant taxon are given in the left column of the page.</li>
        <li>The small green arrow following the name in the left column links to the profile page of the next higher taxonomic level.</li>
        <li>Clicking on any field photo or herbarium specimen opens a new window with details of that plant occurrence.</li>
    </ul>
    <p>&nbsp;</p>
     <h3>Mapping &nbsp; <a data-fancybox href="https://youtu.be/Y2sdnibf1O8" class="btn light-purple-btn"><i class="fa fa-film"></i></a></h3>
    <p>This is a GIS-based mapping tool. Shapefiles can be both imported and exported, making it possible to map other datasets together with OregonFlora plant distribution data.</p>
    <ul>
        <li><em>Map settings</em> Set some of the features for your mapping session using the options in the blue selection box at the top of the map.
            <ol>
                <li>Base Layer&mdash;eight base maps to underlie the data mapping</li>
                <li>Draw&mdash;select an option to create your shape</li>
                <li>Settings&mdash;select how points are clustered</li>
                <li>Tools&mdash;additional data visualization options</li>
                <li>Layers&mdash;Ecoregion and County layers which, when selected, are added to your mapping session</li>
            </ol>
        </li>
        <li><em>How to discover plant diversity within a user-defined area</em>
            <ol>
                <li>In the blue selection box, choose from the &lsquo;draw&rsquo; dropdown a shape to define your area of interest.</li>
                <li>Click on the map to begin creating your shape.</li>
                <li>Select the shape by clicking inside it to make the blue outline thick.</li>
                <li>In the upper left corner of the map, click on the &ldquo;Open&rdquo; button.</li>
                <li>Click on the green &ldquo;Load Records&rdquo; button.</li>
            </ol>
        </li>
        <li><em>How to discover the distribution of a species</em>
            <ol>
                <li>Click the &ldquo;Open&rdquo; button in the upper left corner of the map.</li>
                <li>In the dropdown box labeled Taxa, begin typing the plant name. More than one name can be entered; separate names with a comma.</li>
                <li>Click on the green &ldquo;Load Records&rdquo; button.</li>
            </ol>
        </li>
        <li><em>Viewing mapping results</em>
            <ol>
                <li>The Records tab lists all the plant occurrences from your search.</li>
                <ol type="a">
                    <li>Click on a name in the Collector column to view the details of that record.</li>
                    <li>Select particular records using the checkbox at far left to be copied to the Selections tab.</li>
                </ol>
                <li>The Taxa tab lists all taxa returned in your search.</li>
                <ol type="a">
                    <li>Each vascular plant name is a link to the profile page of that taxon.</li>
                    <li>Taxa that are not vascular plants are included in the list; these do not have a profile page.</li>
                </ol>
                <li>Pressing the &lt;Alt&gt; button while clicking on any dot on the map opens a popup with details about that record.</li>
                <li>Click the green &ldquo;Download&rdquo; button to download the results.</li>
            </ol>
        </li>
    </ul>
    <ul>
        <li><em>Rare taxa </em>Currently, access to rare plant (those state and federally listed, and on ORBIC List 1) locality data is restricted to authorized users; the message &ldquo;There were no records matching your query&rdquo; appears.&nbsp; OregonFlora is working with these agencies to establish policy on the accessibility of data.<br><br>To request an account granting access to restricted information, click the &ldquo;Log In&rdquo; link on the top banner, complete and submit the <a href="../profile/newprofile.php?refurl=/portal/index.php?">profile form</a>. Please explain in the Biography section why you need access.<br><br>
            OregonFlora is working with federal and state agencies and ORBIC to establish data access policies.
        </li>
        <li><em>Additional help</em> Access the OregonFlora <a data-fancybox href="https://youtu.be/Y2sdnibf1O8"><i class="fa fa-film"></i> video tutorial describing the mapping module here</a>.&nbsp; Symbiota webinars covering <a href="http://idigbio.adobeconnect.com/pbkdmqf66sgk" target="_blank">basic</a> and <a href="http://idigbio.adobeconnect.com/pgpptu7v6y4d" target="_blank">advanced</a> (including vector tools)&nbsp; features are also available.</li>
    </ul>
    <p>&nbsp;</p>
    <h3>Interactive Key &nbsp;<a data-fancybox href="https://youtu.be/DKxoEEwL3V4" class="btn light-purple-btn"><i class="fa fa-film"></i></a></h3>
    <p>The interactive key is an easy-to-use plant identification tool based on the features you recognize in your unknown plant.</p>
    <ul>
        <li>Click on the map to indicate the approximate location of your unknown. If a radius is defined, species lists are generated using occurrence data from the defined area. If no radius is entered, the program determines the radius that best represents the local species diversity. In other words, poorly collected areas will have a larger radius sampled. Setting the Taxon Filter will limit results to the plant family selected.</li>
        <li>Click on the green &ldquo;Build Checklist&rdquo; to view the plants of the selected location and the characters.</li>
        <li>In the left-hand column, select the checkbox of any character that matches your unknown plant. Each selection will adjust the species list to reflect taxa having those features.</li>
    </ul>
    <p><em>Things to note:</em></p>
    <ul>
        <li>Any number of characters can be selected, in any order.</li>
        <li>View the profile page of a plant species by clicking on the scientific name.</li>
        <li>Clicking on the latitude/longitude location at the top of the page opens the checklist view, with search, filter, and download options available.</li>
    </ul>
    <p>&nbsp;</p>
    <h3>Plant Inventories&nbsp; <a data-fancybox href="https://youtu.be/RB0bdQy4k6k" class="btn light-purple-btn"><i class="fa fa-film"></i></a></h3>
    <p>Plant inventories are checklists of all the species, based on the specimen and observations records of the OregonFlora database, found at a defined place. There are several collections, or projects, each with a theme; every collection contains a number of checklists.</p>
    <p>The OregonFlora Species List Collection contains 5,200 lists of plant observations contributed to OregonFlora by botanical societies, individuals, government agencies, and scholarly research projects over 25 years. Click on the interactive map to enlarge it, pan and zoom to your area of interest, and click on a pin to open species lists from that region.</p>
    <p><em>Things to note:</em></p>
    <ul>
        <li>Open the checklist for a defined location by clicking on the place name or its locality pin on the map.</li>
        <li>View the profile page of a plant species by clicking on the scientific name.</li>
        <li>Search, filter, and download options are available for the checklist.</li>
    </ul>
    <p>&nbsp;</p>
    <h3>Image Search</h3>
    <p>This gallery presents images of all available herbarium specimens and OregonFlora field photos. Select from the available filters of scientific or common name, photographer, and type of image. Click the green &ldquo;Load images&rdquo; button to view results.</p>
    <p><em>Things to note:</em></p>
    <ul>
        <li>Clicking on any image opens a new window with the details of the record. The image can be viewed at full-screen size.</li>
        <li>To limit your results to images from selected herbaria, open the Collections tab and indicate the desired collection. NOTE that your future image searches will be limited to these selected collections until you reload the page or re-select all collections.</li>
    </ul>
    <p>&nbsp;</p>
    <h3>Garden Search &nbsp; <a data-fancybox href="https://youtu.be/1on5abHiruM" class="btn light-purple-btn"><i class="fa fa-film"></i></a></h3>
    <p>View tips about the garden portal <a href="gardening-with-natives.php">here</a>.</p>
    <p>&nbsp;</p>
    <h3>OSU Herbarium &nbsp; <a data-fancybox href="https://youtu.be/OAz83vUq-bs" class="btn light-purple-btn"><i class="fa fa-film"></i></a></h3>
    <p>This feature gives access to all the digitized collections housed in the Herbarium on the Oregon State University Herbarium in Corvallis: &nbsp;vascular plants from Oregon and elsewhere, &nbsp;algae, bryophytes, fungi, and lichens. Select from a number of filtering options to narrow your results.</p>
    <p><em>Things to note:</em></p>
    <ul>
        <li>Currently only the vascular plants names will appear in a dropdown, but you may type in the scientific names of organisms from any group, and all available records will be returned.</li>
        <li>Map-based searches can be performed in the &ldquo;Latitude and Longitude&rdquo; section by clicking on the earth icon in the lower right corner of each subsection.</li>
        <li>View results in table format using the checkbox at the top of the search page, or from the top of the results page.</li>
        <li>Searches can be limited to selected collections by opening the &ldquo;Collections&rdquo; tab (accessed from the navigation path at top of page) and using the checkboxes next to each collection name. Clear these selections using the &ldquo;Reset Form&rdquo; button on the search page.</li>
        <li>Results can be downloaded by clicking on the icon at the top of the page.</li>
    </ul>
    <p>&nbsp;</p>
    <p><strong>Taxonomic Tree </strong>&nbsp;Accessed from the &ldquo;Resources&rdquo; tab in the navigation bar, this feature displays names of all taxa within a given taxonomic ranking. Synonyms are indicated in [brackets]. Each scientific name is a link to its taxon profile page.</p>

</div> <!-- .inner-content -->

<?php
include( $serverRoot . "/footer.php" );
?>

</body>
</html>