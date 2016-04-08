<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Plants, Growth Form</title>
	<link href="../../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../../css/main.css" type="text/css" rel="stylesheet" />
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
						<h1>Key to Vascular Plants</h1>

						<div style="margin:20px;">

							<table class="key blocks" cellpadding="0" cellspacing="10" border="0">
							<caption>Step 1: Growth Form.</caption>
							<tbody>

							<tr>
							<td>
							<p>
							Trees and shrubs.
							</p>
							<img src="<?php echo $clientRoot; ?>/images.vplants/plants/guide/temp/tree.jpg" width="190" height="258" alt="">
							</td><td>
							<p>
							Herbs and wildflowers.
							</p>
							<img src="<?php echo $clientRoot; ?>/images.vplants/plants/guide/temp/herb.jpg" width="190" height="258" alt="">
							</td><td>
							<p>
							Grass-like plants.
							</p>
							<img src="<?php echo $clientRoot; ?>/images.vplants/plants/guide/temp/grass.jpg" width="190" height="258" alt="">
							</td>
							</tr>

							<tr>
							<td>
							<p>
							Ferns and horsetails.
							</p>
							<img src="<?php echo $clientRoot; ?>/images.vplants/plants/guide/temp/fern.jpg" width="190" height="258" alt="">
							</td><td>
							<p>
							Aquatic plants.
							</p>
							<img src="<?php echo $clientRoot; ?>/images.vplants/plants/guide/temp/aquatic.jpg" width="190" height="258" alt="">
							</td><td>
							<p>
							Vines and climbing plants.
							</p>
							<img src="<?php echo $clientRoot; ?>/images.vplants/plants/guide/temp/vine.jpg" width="190" height="258" alt="">
							</td>
							</tr>

							</tbody>
							</table>
						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
					
					<div id="content2"><!-- start of side content -->
						<!-- any image width should be 250 pixels -->

						<div class="box">
						<h3>....</h3>
						<ul><li>
						<!-- content here -->
						</li></ul>
						</div>

						<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

					</div><!-- end of #content2 -->
				</div><!-- end of #wrapper1 -->
			</div><!-- end of #bodywrap -->
		</div><!-- end of #innervplantstext -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>