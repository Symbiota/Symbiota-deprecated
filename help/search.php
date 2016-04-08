<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> vPlants - Help with Searching</title>
	<link href="../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../css/main.css" type="text/css" rel="stylesheet" />
	<meta name='keywords' content='' />
	<script type="text/javascript">
		<?php include_once($serverRoot.'/config/googleanalytics.php'); ?>
	</script>
</head>
<body>
	<?php
	$displayLeftMenu = "true";
	include($serverRoot."/header.php");
	?> 
        <!-- This is inner text! -->
        <div  id="innervplantstext">
            <h1>Help with Searching</h1>

            <div style="margin:20px;">
            	<p>Choose whether you are looking for plants or fungi. The plant and fungus data are treated separately on this website.</p> 
				<p>Search words can be entered in lowercase or uppercase. Search terms will match the beginnings of words, and is useful for shorthand or uncertain spelling. Fungus Family: <kbd>hygro</kbd> will find <i>Hygrophoraceae</i>, but Fungus Genus: <kbd>hygro</kbd> will find <i>Hygrocybe</i>, <i>Hygrophorus</i>, and <i>Hygrophoropsis</i>. This is also helpful with Latin endings; use Epithet: <kbd>tomentos</kbd> to match <i>tomentosa</i>, <i>tomentosum</i>, and <i>tomentosus</i>.</p>

				<h2>Using Advanced Search</h2>

				<p>Use the <a href="<?php echo $clientRoot; ?>/collections/index.php">Advanced Search</a> to find specimens by particular collectors, dates, or locations.
				Many records, especially older ones, do not have complete collection data. For example, old records may only have the year indicated, or very little locality information, such as only the state.</p>

				<p>When searching collector names, the suffixes (Sr., Jr., III, etc.) are best left off.</p>
				<p>Collection Day needs to be two digits; use <kbd>04</kbd> instead of <kbd>4</kbd>.</p>

				<p>The results page shows the names for those plants or fungi that have specimens that match your search values. Selecting the Specimens link on the search results page for a name will list the specimens that match your search. It does not show all specimens for that name.</p>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>