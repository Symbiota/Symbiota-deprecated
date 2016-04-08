<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>Plants - Origin - Non-native Plants</title>
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
					
						<h1>Non-native Plants</h1>

						<div style="margin:20px;">
							<p>Non-native species (aliens) are not native to an area, but rather were introduced by humans, either intentionally or accidentally. These introductions can be very dangerous to natural occurring plant communities because the non-native plants can negatively modify the habitat and thus decrease the survival of native species.  In many ways they out-compete the native species because they lack natural inhibitors (such as disease or predators).  While many alien species of plants do not make changes to the natural environment or are not able to survive outside of cultivation, many non-native plant species are problematic because they can become <a href="origin3.php">invasive</a>.

							<dl><dt>Escaped species</dt><dd> 
							are those that were purposely introduced to an area for agriculture, ornamental gardening, or for land stabilization (i.e. along steep slopes, riverbanks, or floodplains), but spread beyond cultivation by reproducing on their own.  Many times these plants are only localized to disturbed areas and cannot penetrate native habitats.  However, many aggressive alien plants that are now invasive were first introduced and later escaped.</dd>
							<dt>Weeds or weedy species</dt><dd> 
							are difficult to define because the term weed is subjective.  A particular plant is characterized as a weed only because it is growing in a location that a human does not want it to grow.  Perhaps a more appropriate term is prolific species, those that reproduce in large numbers and can spread widely.  There are both native and non-native plants that can be categorized under the terms weedy or prolific.</dd></dl></p>
						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
        
		
					<div id="content2">

						<div class="box">
							<h3>Plants Origin</h3>
							<ul>
								<li><a href="origin.php">Origin Main</a></li>
								<li><a href="origin4.php">Native species</a></li>
								<li><strong>Non-native species</strong></li>
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