<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>About Plants - Distribution</title>
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
						<h1>Distribution of Plants</h1>

						<div style="margin:20px;">
							<h2>Biogeography</h2>

							<p>Biogeography is the study of the distribution of life on earth. There are many common patterns for the distribution of plants, fungi, and animals.</p>
							
							<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/distribution2.php">Endemics</a></div>
							<div class="indexdescription"><p>The term endemic means that something is restricted to a locality or region.  In biology, endemism refers to species that have evolved to the specific conditions of their habitats in a particular area, and consequently do not exist naturally anywhere else in the world but that locality of origin. <a href="<?php echo $clientRoot; ?>/plants/distribution2.php">Learn more</a></p></div>
							
							<div class="indexheading"><a href="<?php echo $clientRoot; ?>/plants/distribution3.php">Disjuncts</a></div>
							<div class="indexdescription"><p>Distributions of species are considered disjunct when a portion of the area where the species occurs is far-separated from the majority of the area the species occupies. <a href="<?php echo $clientRoot; ?>/plants/distribution3.php">Learn more</a></p></div>
							
						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
		
					<div id="content2">

						<div class="box">
							<h3>Distribution of Plants</h3>
							<ul>
								<li><strong>Plant Distribution Main</strong></li>
								<li><a href="distribution2.php">Endemics</a></li>
								<li><a href="distribution3.php">Disjuncts</a></li>
							</ul>
						</div>
						
						<div class="box external">
						<h3>....</h3>
						<ul><li>
						....
						</li></ul>
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