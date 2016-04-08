<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants | What Plants Are Included?</title>
	<link href="css/base.css" type="text/css" rel="stylesheet" />
	<link href="css/main.css" type="text/css" rel="stylesheet" />
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
            <h1>What Plants Are Included?</h1>

            <div style="margin:20px;">
            	<p>
				The vPlants site currently provides information on vascular plants, a group named for the special transport tissues (circulatory system) they posses.  Vascular plants make up the great majority of the land plants living today and exhibit tremendous diversity.  Some plants are green and use the sun's energy to stay alive; others have no color and feed off other organisms; some grow in the water and some on land.
				</p>
				<p>
				Based on characters of anatomy and morphology, pteridophytes, gymnosperms and angiosperms all belong to the vascular plant group.  The pteridophyte group includes ferns, horsetails, club mosses, and their relatives.  The most familiar examples of gymnosperms include groups like pines, spruce, fir, and other plants commonly thought of as "evergreen" (though some lose their leaves in winter or in dry seasons).  The angiosperms include all flowering plants.  All angiosperms produce flowers as their reproductive structures (though not all flowers are showy or even conspicuous).  Angiosperms are by far more dominant on Earth today than are the gymnosperms and pteridophytes; it is the most diverse plant group alive and includes organisms from lawn grasses to oak trees.
				</p>
				<p>
				In the future, vPlants may include other plant groups like algae and bryophytes (mosses, hornworts, and liverworts).  We are currently adding fungi to the site, but remember these are not plants. [<a href="./fungi.html">What are fungi?</a>]
				</p>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>