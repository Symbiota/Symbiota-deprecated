<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Plants</title>
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
					<h1>Guide to Plants of the Chicago Region</h1>

					<div style="margin:20px;">
						<p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>
						
						<p>Use the <a href="../../checklists/checklist.php?cl=3503&pid=93" title="Index of genera">Species Index</a> to see an alphabetical lists of included plants. At the top of the page you can Search for plants by name. For a complete list of the plant species, subspecies, and varieties represented in the Chicago Region, see the <a href="<?php echo $clientRoot; ?>/resources/plant_checklist.php" title="vPlants document.">Scientific Name Checklist</a>.</p>

						<table class="key" cellpadding="3" cellspacing="0" border="0">
						<caption>Groups included in vPlants</caption>
						<tbody>

						<tr class="keydivision">
						<td>
						<h2>Vascular Plants</h2>
						<p>The vPlants site currently provides information on vascular plants, a group named for the special transport tissues (circulatory system of xylem and phloem) they possess. 
						The vascular plants make up the great majority of the land plants living today. There are two categories of vascular plants: non-flowering and flowering.  Within these categories there are three main groups:
						</p>
						</td>
						</tr><tr >

						<tr class="keychoice">
						<td >
						<h3>Pteridophytes (non-flowering spore plants)</h3>
						<p>
						The pteridophyte group includes ferns, horsetails, club mosses, and other vascular plants without seeds. 
						They reproduce by spores formed on the surface of leaves, or in cone-like structures. The spores grow into a short-lived, haploid, gametophyte stage, which produces gametes.  The male and female gametes unite to form embryos, which grow into adult, sporophyte (spore-producing) plants.
						</p>
						</td>
						</tr>

						<tr class="keychoice">
						<td >
						<h3>Gymnosperms (non-flowering seed plants)</h3>
						<p>
						The most familiar examples of gymnosperms include conifer trees like pines, spruce, fir, and other plants commonly thought of as "evergreen" (though some lose their leaves in winter or in dry seasons, such as larch and ginkgo). They reproduce by seeds, sometimes in cones, but do not have flowers or fruits.
						</p>
						</td>
						</tr>

						<tr class="keychoice">
						<td >
						<h3>Angiosperms (flowering seed plants)</h3>
						<p>
						The angiosperms include all flowering plants.  All angiosperms produce flowers as their reproductive structures, though not all flowers are showy or even conspicuous. Flowers produce fruits that contain seeds.  Angiosperms are by far more dominant on Earth today than are the gymnosperms and pteridophytes. It is the most diverse plant group and includes organisms from lawn grasses to oak trees.
						</p>
						</td>
						</tr>

						</tbody>
						</table>



						<table class="key" cellpadding="3" cellspacing="0" border="0">
						<caption>Groups not included in vPlants</caption>
						<tbody>

						<tr class="keydivision">
						<td colspan="2">
						<h2>Non-vascular Plants</h2>
						<p>These plants lack a true vascular system. Many have a life cycle that has two separate stages. In most cases they require wet habitats or rainwater to allow the sperm to swim between individuals in order to fertilize the eggs.
						</p>
						</td>
						</tr><tr >

						<tr  class="keychoice">
						<td >
						<h3>Bryophytes</h3>
						<p>
						Bryophytes include mosses, liverworts, and hornworts. These are smaller, green plants, which reproduce by spores. Their green, visible, free-living phase of the life cycle is actually the haploid generation, called the gametophyte.  This phase produces male and female gametes, which unite, and consequently develop into a very small structure pertaining to the spore-producing sporophyte phase.  The haploid spores are then released, and grow into a new gametophyte plant.  Bryophytes also often reproduce asexually. 
						</p>
						</td>
						</tr><tr >

						<tr class="keychoice">
						<td >
						<h3>Green Algae</h3>
						<p>
						The green algae are the simplest of green, photosynthetic plants. Growth forms can be single cells, filaments, branched networks, or small leafy patches. They display many different life cycle types, usually involving spores at some stage.
						</p>
						</td>
						</tr><tr >

						</tbody>
						</table>
					</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
				
					<div id="content2"><!-- start of side content -->
						<!-- any image width should be 250 pixels -->

						<div class="box">
						<h3>Related Pages</h3>
						 <ul>
							 <li>
							  <a href="<?php echo $clientRoot; ?>/plants/diversity.php" title="How many plants.">Chicago plant diversity</a>
							 </li>
							 <li>
								<a href="<?php echo $clientRoot; ?>/plants/index.php">More information on plants</a>
							 </li>
							 <li>
							  <a href="<?php echo $clientRoot; ?>/resources/plant_checklist.php" title="vPlants document.">Scientific Name Checklist</a>
							 </li>
							 <li>
							  <a href="<?php echo $clientRoot; ?>/resources/links.php" title="Links to related web sites">Links for Plants</a>
							 </li>
							 <li>
							  <a href="growthforms.php">Growth Forms</a>
							 </li>
						 </ul>
						</div>
						 
						<p>Non-flowering vascular plants</p>
						<img src="<?php echo $clientRoot; ?>/images/vplants/feature/nonflowering.jpg" width="250" height="342" alt="Non-flowering plant examples: fern frond, a pteridophyte; and spruce branch, a gymnosperm.">
						<hr>
						<p>Flowering plants </p>
						<img src="<?php echo $clientRoot; ?>/images/vplants/feature/angios.jpg" width="250" height="322" alt="Flowering plant examples: flowers of cactus and wild rice, both angiosperms.">
						 
						 
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