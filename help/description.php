<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Help with Descriptions</title>
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
            <h1>vPlants - Help with Descriptions</h1>

            <div style="margin:20px;">
            	<h2>What information do the species descriptions provide?</h2>
				<p>
				The species description pages provide thorough descriptions of the physical appearance of a species, subspecies, or variety along with a photograph and information about similar species.  There are also data about typical habitat conditions, whether the species is native, and other important or interesting facts. 
				</p>

				<h2>How do I find the description pages?</h2>
				<p>
				Use the search form found on the <a href="/">home page</a> or other pages. Choose plants or fungi.  Enter a particular name (common or scientific) and click the Search or Go button.  On the list of returned names click the "Description" link for that description page.  
				See some of the completed pages here: <a href="/news/">Features in Production</a>. 
				We are uploading the description pages as they are completed by the <a href="/about/">partner institutions</a>. 
				</p>
				<p>
				In the future we hope to provide the technology that will allow users to search the database of species based on particular visual character states (e.g. flower color, leaf arrangement or shape, etc.) with the aid of photographs.
				</p>

				<h2>How are the distribution lists made?</h2>
				<p>
				The state and county distribution lists are generated automatically from the specimen data in vPlants. This data comes from each of the <a href="/about/">partner institutions</a>.  The lists do not include other outside sources (i.e. printed floras, other herbaria, other websites).  All states and counties with specimen data records in vPlants are linked to those records from the list by clicking on a particular state or county name.
				</p>
				<p>
				We would like to develop a method to create maps on the species description pages. These maps would be generated automatically from the specimen data in vPlants as well as data indicating other outside sources (i.e. printed floras, other herbaria, other websites).
				</p>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>