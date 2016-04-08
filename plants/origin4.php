<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>Plants - Origin - Native Plants</title>
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
						<h1>Native Plants</h1>

						<div style="margin:20px;">
							<p>Native plants are adapted to this region and optimized for the specific conditions of the area.  They succeed in areas where the natural habitat is still intact.  While it is sometimes straightforward to classify a particular plant as being native or non-native, it can often be a challenge to decide on the "natural" distribution of a species.  For this project, with few exceptions, we have agreed with the designations made by Swink and Wilhelm (1994).  The assigned status of a particular taxon is indicated on the Description pages under the section "Regional occurrence."  See <a href="../taxa/index.php?taxon=Campanula%20americana" title="description page"><i>Campanula americana</i></a> for an example of a native plant species.</p>
						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
		
					<div id="content2">
						
						<div class="box">
							<h3>Plants Origin</h3>
							<ul>
								<li><a href="origin.php">Origin Main</a></li>
								<li><strong>Native species</strong></li>
								<li><a href="origin2.php">Non-native species</a></li>
								<li><a href="origin3.php">Invasive species</a></li>
							</ul>
						</div>

						<img src="<?php echo $clientRoot; ?>/images/vplants/feature/ABTH.jpg" width="250" height="293" alt="photo of agricultural weed" title="Abutilon theophrasti">
						<div class="box imgtext">
						<p>Velvet leaf, <a href="../taxa/index.php?taxon=Abutilon%20theophrasti"><i>Abutilon theophrasti</i></a>, is a native to Asia and now a common weed of cultivated ground.</p>
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