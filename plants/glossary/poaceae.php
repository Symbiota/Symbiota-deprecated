<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Glossary Poaceae</title>
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
						<h1>Glossary for Poaceae</h1>

						<div style="margin:20px;">
							<p>For terms not listed here, see the <a href="index.php" title="Go to main glossary.">Plant Glossary</a>.</p>

							<div class="plate">
							<img src="<?php echo $clientRoot; ?>/images/vplants/plants/glossary/plate12_grass.jpg" width="703" height="367"
							alt="Line drawings of plant features." 
							title="Plate 12: Grasses.">
							</div>


							<dl id="glossdefs">

							<dt id="awn">Awn</dt>
							<dd>&#151; A stiff bristle situated at the tip of a glume or lemma.</dd>

							<dt id="caryopsis">Caryopsis</dt>
							<dd>&#151; A seed-like fruit with a thin outer wall; a grain.</dd>

							<dt id="collar">Collar</dt>
							<dd>&#151; The junction of the leaf sheath and blade.</dd>

							<dt id="culm">Culm</dt>
							<dd>&#151; The stem of a grass.</dd>

							<dt id="floret">Floret</dt>
							<dd>&#151; A single small flower, usually a member of a cluster, such as a spikelet or a head.</dd>

							<dt id="glume">Glume</dt>
							<dd>&#151; The lowest two (sometimes one) empty scales subtending the usually fertile scales in grass spikelets.</dd>

							<dt id="lemma">Lemma</dt>
							<dd>&#151; The lowermost of the two scales forming the floret in a grass spikelet -- the uppermost, less easily seen, is called the palea.</dd>

							<dt id="ligule">Ligule</dt>
							<dd>&#151; An extension, often scarious (papery), of the summit of the leaf sheath.</dd>

							<dt id="">Nerve</dt>
							<dd>&#151; Same as a vein. The central vein running lengthwise on a scale</dd>

							<dt id="">Node</dt>
							<dd>&#151; The point along a stem which gives rise to leaves, branches, or inflorescences.</dd>

							<dt id="palea">Palea</dt>
							<dd>&#151; The uppermost of the two scales forming the floret in a grass spikelet (often obscure or hidden).</dd>

							<dt id="">Rachilla</dt>
							<dd>&#151; A secondary rachis. The axis of a spikelet.</dd>

							<dt id="">Sheath</dt>
							<dd>&#151; A tubular structure effected by the formation of leaf margins around the stem. The base of a grass leaf that runs from the node up to the blade.</dd>

							<dt id="">Spikelet</dt>
							<dd>&#151; A secondary or small spike; specifically, in the Poaceae family, the unit composed or one or two glumes subtending one to several sets of lemma and palea combinations.</dd>

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
								<li><a href="cyperaceae.php" title="Glossary for Cyperaceae">Cyperaceae &#151; Sedges</a></li>
								<li><strong>Poaceae &#151; Grasses</strong></li>
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