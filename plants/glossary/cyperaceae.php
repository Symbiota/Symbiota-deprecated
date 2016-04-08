<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> vPlants - Glossary Cyperaceae</title>
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
						<h1>Glossary for Cyperaceae</h1>

						<div style="margin:20px;">
							<p>For terms not listed here, see the <a href="index.php" title="Go to main glossary.">Plant Glossary</a>.</p>

							<div class="plate">
							<img src="plate12_sedge.jpg" width="703" height="385" 
							alt="Line drawings of plant features." 
							title="Plate 12: Sedges.">
							</div>

							<dl id="glossdefs">

							<dt id="achene">Achene</dt>
							<dd>&#151; A hard, one-seeded, <a href="index.php#indehiscent">indehiscent</a> <a href="index.php#nutlet">nutlet</a> with a tight <a href="index.php#pericarp">pericarp</a>.</dd>

							<dt id="bract">Bract</dt>
							<dd>&#151; A reduced leaf or <a href="#scale">scale</a>, typically one which <a href="index.php#subtend">subtends</a> a <a href="index.php#pedicel">pedicel</a> or <a href="#inflorescence">inflorescence</a>, but it also can refer to minute leaves on a stem.</dd>

							<dt id="bristle">Bristle</dt>
							<dd>&#151; A stiff hair.</dd>

							<dt id="culm">Culm</dt>
							<dd>&#151; The stem of grasses, sedges, and rushes.</dd>

							<dt id="inflorescence">Inflorescence</dt>
							<dd>&#151; The discrete flowering portion or portions of a plant; a flower cluster.</dd>

							<dt id="perianth">Perianth</dt>
							<dd>&#151; Pertaining to the floral series of <a href="index.php#sepal">sepals</a>, <a href="index.php#petal">petals</a>, or both, spoken of collectively.</dd>

							<dt id="perianthbristle">Perianth bristles</dt>
							<dd>&#151; Pertaining to <a href="#perianth">perianth</a> parts (petals, sepals) that are reduced to a set of bristles.</dd>

							<dt id="perigynium">Perigynium</dt>
							<dd>&#151; Referring specifically to the often <a href="index.php#inflated">inflated</a> <a href="index.php#sac">sac</a> which encloses the <a href="index.php#ovary">ovary</a>, and later the <a href="#achene">achene</a> in the genus <i>Carex</i>.</dd>

							<dt id="pistillate">Pistillate</dt>
							<dd>&#151; Referring to flowers or <a href="#spikelet">spikelets</a> which bear <a href="index.php#pistil">pistils</a> but not <a href="index.php#stamen">stamens</a>.</dd>

							<dt id="scale">Scale</dt>
							<dd>&#151; Generally a thin, sometimes papery, much reduced, leaf, <a href="#bract">bract</a>, or <a href="#perianth">perianth</a> part.</dd>

							<dt id="spikelet">Spikelet</dt>
							<dd>&#151; A <a href="index.php#secondary">secondary</a> or small <a href="index.php#spike">spike</a> of flowers.</dd>

							<dt id="staminate">Staminate</dt>
							<dd>&#151; Referring unisexual flowers or <a href="#spikelet">spikelets</a> which bear <a href="index.php#stamen">stamens</a> but not <a href="index.php#pistil">pistils</a>.</dd>

							<!-- <dt id=""></dt>
							<dd>&#151; </dd> -->
							</dl>
						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
					
					<div id="content2"><!-- start of side content -->

						<div class="box">
							<h3>Family Glossaries</h3>
							<ul>
								<li><a href="asteraceae.php" title="Glossary for Asteraceae">Asteraceae &#151; Composites</a></li>
								<li><strong>Cyperaceae &#151; Sedges</strong></li>
								<li><a href="poaceae.php" title="Glossary for Poaceae">Poaceae &#151; Grasses</a></li>
							</ul>
						</div>

						<div class="box">
							<h3>Contents of Plates</h3>
							<dl>
							
							<dt><a href="plate01.php" title="Plate 01">Plate 1:</a></dt>
							<dd>Stem and Root Types.</dd>
							
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