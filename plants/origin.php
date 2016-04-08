<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>Plants - Origin</title>
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
		<!-- start of inner text and right side content -->
		<div  id="innervplantstext">
			<div id="bodywrap">
				<div id="wrapper1"><!-- for navigation and content -->

					<!-- PAGE CONTENT STARTS -->

					<div id="content1wrap"><!--  for content1 only -->

					<div id="content1"><!-- start of primary content --><a id="pagecontent" name="pagecontent"></a>
						<h1>Origin</h1>

						<div style="margin:20px;">
							<p>Plants, fungi, and other organisms have different <a href="distribution.php">distribution</a> patterns and ranges. In a particular place, such as the Chicago Region, the life that occurs there can be placed in several categories based on origin and lifestyle:
							</p>
							
							<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/origin4.php">Native species</a></div>
							<div class="indexdescription"><p>are those that we believe naturally occurred in an area for a long time before European settlers arrived. They have evolved with their surrounding ecosystem. <a href="<?php echo $clientRoot; ?>/plants/origin4.php">Learn more</a></p></div>
							
							<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/origin2.php">Non-native species</a></div>
							<div class="indexdescription"><p>or alien species are those that are originally from somewhere outside the area, and have been introduced by humans either purposely or accidentally. There are many examples of non-native plants, animals, and pathogens introduced to various parts of the world. These introductions can be very dangerous because they can often spread widely since they lack natural predators in the new area, and they also can modify the area in such a way as to decrease the survival efficiency of native species. <a href="<?php echo $clientRoot; ?>/plants/origin2.php">Learn more</a></p></div>
							
							<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/origin3.php">Invasive species</a></div>
							<div class="indexdescription"><p>are non-native, aggressive species that out-compete native species for space and resources. Invasive species are incredibly threatening to natural communities because they usually spread quickly, modify the environment to their advantage (and the detriment of native species), and drive out native species while depleting ecosystems of their natural resources. <a href="<?php echo $clientRoot; ?>/plants/origin3.php">Learn more</a></p></div>

						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
        
		
					<div id="content2">

						<div class="box">
							<h3>Plants Origin</h3>
							<ul>
								<li><strong>Origin Main</strong></li>
								<li><a href="origin4.php">Native species</a></li>
								<li><a href="origin2.php">Non-native species</a></li>
								<li><a href="origin3.php">Invasive species</a></li>
							</ul>
						</div>
						
						<img src="<?php echo $clientRoot; ?>/images/vplants/feature/CAAM6.jpg" width="250" height="321" alt="Blue flowers" title="Campanula americana">
						<div class="box imgtext">
						<p>The tall bellflower, <i>Campanula americana</i> is one of the 1650 native plants in the region.
						</p>
						</div>

						<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

					</div><!-- end of #content2 -->
				</div><!-- end of #wrapper1 -->
			</div><!-- end of #bodywrap -->
		</div><!-- end of #innervplantstext -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>