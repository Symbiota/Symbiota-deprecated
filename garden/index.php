<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle?> Home</title>
    <link href="../css/jquery-ui_accordian.css" type="text/css" rel="stylesheet" />
    <link href="../css/jquery-ui.css" type="text/css" rel="stylesheet" />
    <link href="../css/base.css?<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css?<?php echo $CSS_VERSION_LOCAL; ?>" type="text/css" rel="stylesheet" />
    <style type="text/css">

    </style>
    <script src="../js/jquery.js" type="text/javascript"></script>
    <script src="../js/jquery-ui.js" type="text/javascript"></script>
	<script type="text/javascript">
		<?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
	</script>
</head>
<body>
<?php
include($serverRoot."/header.php");
?>
<link rel="stylesheet" href="../css/jquery.bxslider.css">
<script src="../js/jquery.bxslider.js"></script>
<script>
    $(document).ready(function () {
        $("#heightSlider").slider({
            range: "min",
            min: 0,
            max: 200,
            value: 0,
            create: function() {
                var textbox = $("#height-label");
                textbox.text("Any");
            },
            step: 1,
            slide: function(event, ui) {
                var textbox = $("#height-label");
                if(ui.value == 0) textbox.text("Any");
                else textbox.text(ui.value);
            },
            stop: function( event, ui ) {
                //alert(ui.value);
            }
        });

        $("#widthSlider").slider({
            range: "min",
            min: 0,
            max: 15,
            value: 0,
            create: function() {
                var textbox = $("#width-label");
                textbox.text("Any");
            },
            step: 1,
            slide: function(event, ui) {
                var textbox = $("#width-label");
                if(ui.value == 0) textbox.text("Any");
                else textbox.text(ui.value);
            },
            stop: function( event, ui ) {
                //alert(ui.value);
            }
        });
    });

    function toggleAdvSearch(){
        var toggleSwitch = document.getElementById('advSearchToggle');
        if(toggleSwitch.checked){
            document.getElementById("showAdvSearchFilters").style.display = 'none';
            document.getElementById("hideAdvSearchFilters").style.display = 'block';
            document.getElementById("advSearchWrapper").style.display = 'block';
        }
        else{
            document.getElementById("showAdvSearchFilters").style.display = 'block';
            document.getElementById("hideAdvSearchFilters").style.display = 'none';
            document.getElementById("advSearchWrapper").style.display = 'none';
        }
    }
</script>
<div class="native-banner-wrapper">
    <div class="inner-content">
        <h2>Gardening with Natives</h2>
    </div>
</div>
<div class="garden-header-wrapper clearfix">
    <div class="col1">
        <div class="col-content">
            <h2>What is a native?</h2>
            <p>Oregon native plants are those which occur or historically occurred naturally in our state, and established in the
                landscape independently of direct or indirect human intervention.</p>
            <h2>Why plant natives?</h2>
            <p>Native plants are wise gardening choices. If planted in a habitat comparable to their natural one, they will:</p>
            <ul class="square-bullets white-bullets">
                <li>Use less water, fertilizer, and pesticides when established.</li>
                <li>Capture the unique character of a region by preserving its biological heritage and maintaining genetic diversity.</li>
                <li>Provide food and habitat for native pollinators, birds, and other animals.</li>
                <li>Serve as biodiversity corridors, connecting distant natural areas with critical strands of native habitat
                    through urban areas.</li>
            </ul>
        </div>
    </div>
    <div class="col2">&nbsp;</div>
</div>
<div class="garden-content">
    <div class="garden-name-search-wrapper">
        <h2>Garden native plant search</h2>
        <h2>Search by plant name</h2>
        <div class="garden-name-search-box">
            Search by scientific name
            <input type="text" name="taxon" id="garden-sciname-search-input" title="Enter scientific name here." />
            <button name="formsubmit"  id="garden-sciname-search-but" type="submit" value="Search Terms"><i class="fa fa-search"></i></button>
        </div>
        <div class="garden-name-search-box">
            Search by common name
            <input type="text" name="taxon" id="garden-common-search-input" title="Enter common name here." />
            <button name="formsubmit"  id="garden-common-search-but" type="submit" value="Search Terms"><i class="fa fa-search"></i></button>
        </div>
    </div>
</div>
<div class="garden-content">
    <div class="basic-feature-search-wrapper">
        <h2>Search by plant features</h2>
        <span class="garden-feature-search-text">Filter for any combination of features within one or more categories</span>
        <div class="divTable gardenSearchTable">
            <div class="divTableHeading">
                <div class="divTableRow">
                    <div class="divTableHead">Plant Type</div>
                    <div class="divTableHead">Sunlight</div>
                    <div class="divTableHead">Moisture</div>
                    <div class="divTableHead">Size</div>
                    <div class="divTableHead">Ease of Growth</div>
                </div>
            </div>
            <div class="divTableBody">
                <div class="divTableRow">
                    <div class="divTableCell">
                        <div class="divTable plantTypeCol">
                            <div class="divTableBody">
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" id="planttype1">
                                        <div class="featureCheckBoxDiv">
                                            <label class="featureCheckBoxLabel planttype1" for="planttype1"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" id="planttype2">
                                        <div class="featureCheckBoxDiv">
                                            <label class="featureCheckBoxLabel planttype2" for="planttype2"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" id="planttype3">
                                        <div class="featureCheckBoxDiv">
                                            <label class="featureCheckBoxLabel planttype3" for="planttype3"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" id="planttype4">
                                        <div class="featureCheckBoxDiv">
                                            <label class="featureCheckBoxLabel planttype4" for="planttype4"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" id="planttype5">
                                        <div class="featureCheckBoxDiv">
                                            <label class="featureCheckBoxLabel planttype5" for="planttype5"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" id="planttype6">
                                        <div class="featureCheckBoxDiv">
                                            <label class="featureCheckBoxLabel planttype6" for="planttype6"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="divTableCell">
                        <div class="divTable sunlightCol">
                            <div class="divTableBody">
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" id="sunlight1">
                                        <div class="featureCheckBoxDiv">
                                            <label class="featureCheckBoxLabel sunlight1" for="sunlight1"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" id="sunlight2">
                                        <div class="featureCheckBoxDiv">
                                            <label class="featureCheckBoxLabel sunlight2" for="sunlight2"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" id="sunlight3">
                                        <div class="featureCheckBoxDiv">
                                            <label class="featureCheckBoxLabel sunlight3" for="sunlight3"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" id="sunlight4">
                                        <div class="featureCheckBoxDiv">
                                            <label class="featureCheckBoxLabel sunlight4" for="sunlight4"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" id="sunlight5">
                                        <div class="featureCheckBoxDiv">
                                            <label class="featureCheckBoxLabel sunlight5" for="sunlight5"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="divTableCell">
                        <div class="divTable moistureCol">
                            <div class="divTableBody">
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" id="moisture1">
                                        <div class="featureCheckBoxDiv">
                                            <label class="featureCheckBoxLabel moisture1" for="moisture1"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" id="moisture2">
                                        <div class="featureCheckBoxDiv">
                                            <label class="featureCheckBoxLabel moisture2" for="moisture2"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" id="moisture3">
                                        <div class="featureCheckBoxDiv">
                                            <label class="featureCheckBoxLabel moisture3" for="moisture3"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" id="moisture4">
                                        <div class="featureCheckBoxDiv">
                                            <label class="featureCheckBoxLabel moisture4" for="moisture4"></label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureCheckBox" type="checkbox" id="moisture5">
                                        <div class="featureCheckBoxDiv">
                                            <label class="featureCheckBoxLabel moisture5" for="moisture5"></label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="divTableCell">
                        <div class="divTable sizeCol">
                            <div class="divTableBody">
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <div class="feature-slider-range">
                                            <div class="feature-slider-low-value">Any</div>
                                            <div class="feature-slider-high-value">200</div>
                                        </div>
                                        <div class="feature-slider-wrapper">
                                            <div id="heightSlider">
                                                <div id="height-handle" class="ui-slider-handle">
                                                    <div class="custom-label-bar"></div>
                                                    <div id="height-label" class="custom-label"></div>
                                                </div>
                                            </div>
                                            <div class="feature-slider-label">
                                                Height (ft)
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <div class="feature-slider-range">
                                            <div class="feature-slider-low-value">Any</div>
                                            <div class="feature-slider-high-value">15</div>
                                        </div>
                                        <div class="feature-slider-wrapper">
                                            <div id="widthSlider">
                                                <div id="width-handle" class="ui-slider-handle">
                                                    <div class="custom-label-bar"></div>
                                                    <div id="width-label" class="custom-label"></div>
                                                </div>
                                            </div>
                                            <div class="feature-slider-label">
                                                Width
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="divTableCell">
                        <div class="divTable growthCol">
                            <div class="divTableBody">
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureTextCheckBox" type="checkbox" id="growth1">
                                        <div class="featureTextCheckBoxDiv unselectable">
                                            <label class="featureTextCheckBoxLabel" for="growth1">Easy</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureTextCheckBox" type="checkbox" id="growth2">
                                        <div class="featureTextCheckBoxDiv unselectable">
                                            <label class="featureTextCheckBoxLabel" for="growth2">Moderate</label>
                                        </div>
                                    </div>
                                </div>
                                <div class="divTableRow">
                                    <div class="divTableCell">
                                        <input class="featureTextCheckBox" type="checkbox" id="growth3">
                                        <div class="featureTextCheckBoxDiv unselectable">
                                            <label class="featureTextCheckBoxLabel" for="growth3">Difficult</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="garden-content">
    <div class="advanced-toggle-wrapper">
        <input id="advSearchToggle" class="advSearchToggleCheck" onchange="toggleAdvSearch();" type="checkbox">
        <label for="advSearchToggle" class="advSearchToggleLabel unselectable">
            <div id="showAdvSearchFilters">More Filters +</div>
            <div id="hideAdvSearchFilters">Less Filters -</div>
        </label>
    </div>
</div>
<div id="advSearchWrapper" class="garden-content">

</div>
<div class="garden-content">
    <hr />
    <h2>Browse Plant Collections</h2>
    <div class="home-boxes">
        <a href="<?php echo $clientRoot; ?>/spatial/index.php" class="home-box image-box" target="_blank">
            <img src="<?php echo $clientRoot; ?>/images/layout/Meadowscape_sm.jpg" alt="Meadowscape">
            <h3>Meadowscape</h3>
            <div class="box-overlay">
                <div class="centered">A sun-loving mix of flowering herbs, perennials, and grasses</div>
            </div>
        </a>
        <a href="<?php echo $clientRoot; ?>/checklists/dynamicmap.php?interface=key" class="home-box image-box">
            <img src="<?php echo $clientRoot; ?>/images/layout/Pollinator_garden_sm.jpg" alt="Pollinator Garden">
            <h3>Pollinator Garden</h3>
            <div class="box-overlay">
                <div class="centered">Description text</div>
            </div>
        </a>
        <a href="#" class="home-box image-box">
            <img src="<?php echo $clientRoot; ?>/images/layout/Rock_garden_sm.jpg" alt="Rock Garden">
            <h3>Rock Garden</h3>
            <div class="box-overlay">
                <div class="centered">Description text</div>
            </div>
        </a>
        <a href="<?php echo $clientRoot; ?>/collections/harvestparams.php?db[]=5,8,10,7" class="home-box image-box">
            <img src="<?php echo $clientRoot; ?>/images/layout/Water_features_garden_sm.jpg" alt="Rain Garden">
            <h3>Rain Garden</h3>
            <div class="box-overlay">
                <div class="centered">Description text</div>
            </div>
        </a>
        <a href="<?php echo $clientRoot; ?>/garden/index.php" class="home-box image-box">
            <img src="<?php echo $clientRoot; ?>/images/layout/Woodland_garden_sm.jpg" alt="Woodland Garden">
            <h3>Woodland Garden</h3>
            <div class="box-overlay">
                <div class="centered">Description text</div>
            </div>
        </a>
    </div>
</div>
<div class="metro-wrapper">
    <div class="inner-content">
        <hr />
        <div class="metro-col1"> </div>
        <div class="metro-col2">
            <div class="col-content">
                <p>Metro is a primary contributor to OregonFlora's Gardening with Native Plants and supports efforts to protect clean
                    air, water and habitat in greater portland.</p>
            </div>
        </div>
    </div>
</div>


<?php
include($serverRoot."/footer.php");
?>

</body>
</html>