<?php
include_once('../config/symbini.php');
include_once($SERVER_ROOT . '/classes/ImageLibraryManager.php');
include_once($SERVER_ROOT . '/classes/ImageExplorer.php');
header("Content-Type: text/html; charset=" . $charset);

$phUid = array_key_exists("phuid", $_REQUEST) ? $_REQUEST["phuid"] : 0;
$collId = array_key_exists("collid", $_REQUEST) ? $_REQUEST["collid"] : 0;
$limitStart = array_key_exists("lstart", $_REQUEST) ? $_REQUEST["lstart"] : 0;
$limitNum = array_key_exists("lnum", $_REQUEST) ? $_REQUEST["lnum"] : 100;
$imgCnt = array_key_exists("imgcnt", $_REQUEST) ? $_REQUEST["imgcnt"] : 0;

$pManager = new ImageLibraryManager();
$imageExplorer = new ImageExplorer();

?>
<html>
<head>
    <title><?php echo $DEFAULT_TITLE; ?> Image Search</title>
	<link href="../css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="../css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
    <script type="text/javascript" src="../js/jquery.js"></script>
    <script type="text/javascript" src="../js/jquery-ui.js"></script>
    <script type="text/javascript" src="../js/underscore-1.4.3.js"></script>
    <script type="text/javascript" src="../js/backbone-0.9.10.js"></script>
    <script type="text/javascript" src="../js/symb/imagelib.imgexplorer.js"></script>

    <script src="../js/visualsearch.js" type="text/javascript"></script>
    <!--[if (!IE)|(gte IE 8)]><!-->
    <link href="../css/visualsearch-datauri.css" media="screen" rel="stylesheet" type="text/css"/>
    <!--<![endif]-->
    <!--[if lte IE 7]><!-->
    <link href="../css/visualsearch.css" media="screen" rel="stylesheet" type="text/css"/>
    <!--<![endif]-->

    <style>
        /* VisualSearch (VS) works hard to autosize the width.
         * Unfortunately, it, or jquery-ui.autocomplete has a problem with computing the initial width.
         * The first rendering of the autocomplete box is not wide enough.  Then, once a 2nd letter is
         * hit and the box is updated, the autowidth seems to work.
         * as a workaround, a min-width is set here.
         */
        .VS-interface.ui-autocomplete {
            min-width: 400px;
        }

        /* Start by setting display:none to make this hidden.
   Then we position it in relation to the viewport window
   with position:fixed. Width, height, top and left speak
   speak for themselves. Background we set to 80% white with
   our animation centered, and no-repeating */
        .modal {
            display:    none;
            position:   fixed;
            z-index:    1000;
            top:        0;
            left:       0;
            height:     100%;
            width:      100%;
            background: rgba( 255, 255, 255, .8 )
            url('http://sampsonresume.com/labs/pIkfp.gif')
            50% 50%
            no-repeat;
        }

        /* When the body has the loading class, we turn
           the scrollbar off with overflow:hidden */
        body.loading {
            overflow: hidden;
        }

        /* Anytime the body has the loading class, our
           modal element will be visible */
        body.loading .modal {
            display: block;
        }
    </style>
    <script type="text/javascript">
        <?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
    </script>
    <script type="text/javascript">
        <?php
            $pList = array();
            foreach($pManager->getPhotographerList() as $uid => $pArr){
                $pList[] = (object)array(
                    'value' => (string)$uid,
                    'label' => $pArr['name']);
            }

            echo("var photographers = ".json_encode($pList).";");
            echo("var collections = ".$imageExplorer->getCollections().";");
            echo("var countries = [");

            $countries = $imageExplorer->getCountries();

            for($i = 0; $i < count($countries); $i++) {
                echo('"'.$countries[$i].'"');
                if ($i < count($countries)-1) {
                    echo(', ');
                }
            }
            echo('];');

            echo("var states = [");

            $states = $imageExplorer->getStates();

            for($i = 0; $i < count($states); $i++) {
                echo('"'.$states[$i].'"');
                if ($i < count($states)-1) {
                    echo(', ');
                }
            }
            echo('];');
        ?>

        $(document).ready(function () {

            var taxaSuggest = function (searchTerm, callback) {
                if (searchTerm.length >= 3) {
                    $.get('rpc/gettaxasuggest.php?term=' + searchTerm, function (data, status) {
                        callback(eval(data));
                    });
                }
            };

            var options = {
                displayUrl : "rpc/displayimagesforview.php",
                limit: 100,
                start: 0,
                facets: [
                    {
                        name: 'taxon',
                        source: taxaSuggest
                    },
                    { name: 'photographer',
                        source: function (searchTerm, callback) {
                            callback(photographers);
                        }
                    },
                    { name: 'collection',
                        source: function (searchTerm, callback) {
                            callback(collections);
                        }
                    },
                    { name: 'country',
                        source: function (searchTerm, callback) {
                            callback(countries);
                        }
                    },
                    { name: 'state',
                        source: function (searchTerm, callback) {
                            callback(states);
                        }
                    },
                    { name: 'tags',
                        source: function (searchTerm, callback) {
                            callback(<?php echo $imageExplorer->getTags(); ?>);
                        }
                    }
                ]/*,
                initialCriteria : {
                    country : ["USA"],
                    state : ["Arizona"]
                }*/
            };

            var imgExplorer = new ImageExplorer(options);

            imgExplorer.init("imagesearch");

            $('#nextPage').click(function(event) {
                imgExplorer.nextPage();
            });

            $('#previousPage').click(function(event) {
                imgExplorer.previousPage();
            });

            $('#nextPage_bottom').click(function(event) {
                imgExplorer.nextPage();
            });

            $('#previousPage_bottom').click(function(event) {
                imgExplorer.previousPage();
            });

            $('#firstPage').click(function(event) {
                imgExplorer.firstPage();
            });

            $('#lastPage').click(function(event) {
                imgExplorer.lastPage();
            });

            $('#firstPage_bottom').click(function(event) {
                imgExplorer.firstPage();
            });

            $('#lastPage_bottom').click(function(event) {
                imgExplorer.lastPage();
            });

            $('#displayOptions').click(function(event) {
                $("#options" ).toggle('slide', 500 );
                console.log($('#displayOptions').text());
                if ($('#displayOptions').text() == "Show options...") {
                    $('#displayOptions').text("Hide options...");
                } else {
                    $('#displayOptions').text("Show options...");
                }
            });

        });
    </script>
    <meta name='keywords' content=''/>
</head>

<body>


<?php
$displayLeftMenu = (isset($imagelib_photographersMenu) ? $imagelib_photographersMenu : false);
include($SERVER_ROOT . '/header.php');
echo '<div class="navpath">';
echo '<a href="../index.php">Home</a> &gt;&gt; ';
echo '<a href="index.php">Browse Images</a> &gt;&gt; ';
echo '<b>Image Search</b>';
echo "</div>";
?>
<!-- This is inner text! -->
<div id="innertext">
    <div style="margin:20px 0px 10px 0px;">
        <div style="font-weight:bold;">
            <div id="imagesearch"></div>
        </div>
    </div>

</div>
<?php
include($SERVER_ROOT . '/footer.php');
?>
<div class="modal"></div>
</body>
</html>