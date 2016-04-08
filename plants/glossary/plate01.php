<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> vPlants - Plate 1 - Stem and Root Types</title>
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
						<h1></h1>

						<div style="margin:20px;">

							<div class="plate">
							<img src="<?php echo $clientRoot; ?>/images/vplants/plants/glossary/plate01.jpg" width="712" height="1055" 
							alt="Line drawings of plant features." 
							title="Plate 1: Stem and Root Types.">

							<h2>Plate 1: Stem and Root Types</h2>
							</div>
						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
					
					<div id="content2"><!-- start of side content -->

						<div class="box">
							<h3>Family Glossaries</h3>
							<ul>
								<li><a href="asteraceae.php" title="Glossary for Asteraceae">Asteraceae &#151; Composites</a></li>
								<li><a href="cyperaceae.php" title="Glossary for Cyperaceae">Cyperaceae &#151; Sedges</a></li>
								<li><a href="poaceae.php" title="Glossary for Poaceae">Poaceae &#151; Grasses</a></li>
							</ul>
						</div>

						<div class="box">
							<h3>Contents of Plates</h3>
							<dl>
							
							<dt>Plate 1:</dt>
							<dd><b>Stem and Root Types.</b></dd>
							
							<dt><a href="plate02.php" title="Plate 02">Plate 2</a>:</dt>
							<dd>Leaf Composition, Parts, and Types.</dd>
							
							<dt><a href="plate03.php" title="Plate 03">Plate 3</a>:</dt>
							<dd>Leaf Shapes.</dd>
							
							<dt><a href="plate04.php" title="Plate 04">Plate 4</a>:</dt>
							<dd>Leaf Margins.</dd>
							
							<dt><a href="plate05.php" title="Plate 05">Plate 5</a>:</dt>
							<dd>Leaf Apices, Venation, and Bases.</dd>
							
							<dt><a href="plate06.php" title="Plate 06">Plate 6</a>:</dt>
							<dd>Surface Features.</dd>
							
							<dt><a href="plate07.php" title="Plate 07">Plate 7</a>:</dt>
							<dd>Stem and Leaf Parts, and Variations.</dd>
							
							<dt><a href="plate08.php" title="Plate 08">Plate 8</a>:</dt>
							<dd>Inflorescence Types.</dd>
							
							<dt><a href="plate09.php" title="Plate 09">Plate 9</a>:</dt>
							<dd>Floral Morphology.</dd>
							
							<dt><a href="plate10.php" title="Plate 10">Plate 10</a>:</dt>
							<dd>Corolla Types.</dd>
							
							<dt><a href="plate11.php" title="Plate 11">Plate 11</a>:</dt>
							<dd>Fruit Types.</dd>
							
							<dt><a href="plate12.php" title="Plate 12">Plate 12</a>:</dt>
							<dd>Sedges, Grasses, and Composites.</dd>
							
							<dt><a href="plate_all.php" title="All Plates">Plates 1-12</a>:</dt>
							<dd>All</dd>

							</dl>
						</div>

						<div class="box">
						<h3>Related Pages</h3>
						<ul><li>
						<a href="../../resources/plant_terms.php" 
						 title="vPlants Accepted Plant Terms.">Accepted Plant Terms</a>
						</li><li>
						<a href="../../resources/links2.php" 
						 title="Links to related web sites">Links for Plants</a>
						</li></ul>
						</div>

						<div class="box">
						<h3>Related Web Sites:</h3>
						<h4>Online Plant Glossaries</h4>
						<ul><li>
						<a href="http://glossary.gardenweb.com/glossary/index.html">GardenWeb Glossary</a>
						</li><li>
						<a href="http://www.calflora.net/botanicalnames/botanicalterms.html">Calflora.net Botanical Terms</a>
						</li><li>
						<a href="http://www.m-w.com/dictionary.htm">Merriam Webster Dictionary</a>
						</li></ul>
						</div>
						 
						<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="../../disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>
					</div><!-- end of #content2 -->
					
				</div><!-- end of #wrapper1 -->
			</div><!-- end of #bodywrap -->
		</div><!-- end of #innervplantstext -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>