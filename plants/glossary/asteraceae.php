<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> vPlants - Glossary Asteraceae</title>
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
						<h1>Glossary for Asteraceae</h1>

						<div style="margin:20px;">
							<p>For terms not listed here, see the <a href="index.php" title="Go to main glossary.">Plant Glossary</a>.</p>

							<div class="plate">
							<img src="<?php echo $clientRoot; ?>/images/vplants/plants/glossary/plate12_comp.jpg" width="703" height="424"
							alt="Line drawings of plant features." 
							title="Plate 12: Composites.">
							</div>

							<dl id="glossdefs">

							<dt id="achene">Achene</dt>
							<dd>&#151; A hard, one-seeded, <a href="index.php#indehiscent">indehiscent</a> <a href="index.php#nutlet">nutlet</a> with a tight <a href="index.php#pericarp">pericarp</a>. An example is the sunflower seed in the shell (pericarp).</dd>

							<dt id="biseriate">Biseriate</dt>
							<dd>&#151; Having two series, or rows, of parts; having two rows or sets of <a href="#phyllary">phyllaries</a> (bracts) on the involucre.</dd>

							<dt id="claw">Claw</dt>
							<dd>&#151; The narrowed base of the <a href="index.php#corolla">corolla</a> of a <a href="#rayflower">ray flower</a>.</dd>

							<dt id="crown">Crown</dt>
							<dd>&#151; In the Asteraceae family, <a href="index.php#scale">scales</a> or <a href="index.php#awn">awns</a> at the summit of an <a href="#achene">achene</a>.</dd>

							<dt id="disk">Disk or disc</dt>
							<dd>&#151; The central portion of a <a href="index.php#capitate">capitate</a> <a href="#inflorescence">inflorescence</a>, or the <a href="#receptacle">receptacle</a> of such an <a href="#inflorescence">inflorescence</a>.</dd>

							<dt id="diskflower">Disk flowers</dt>
							<dd>&#151; The central, <a href="index.php#tubular">tubular</a> flowers of the <a href="#head">head</a>. Compare <a href="#rayflower">ray flower</a>.</dd>

							<dt id="floret">Floret</dt>
							<dd>&#151; A single small flower, usually a member of a cluster, such as a <a href="#head">head</a>; see <a href="#diskflower">disk flower</a> and <a href="#rayflower">ray flower</a>.</dd>

							<dt id="head">Head</dt>
							<dd>&#151; A dense, compact cluster of mostly <a href="index.php#sessile">sessile</a> flowers, used to describe the inflorescence in the Asteraceae family.</dd></dd>

							<dt id="imbricate">Imbricate</dt>
							<dd>&#151; Having <a href="#phyllary">phyllaries</a> (bracts) on the involucre that overlap each other like roof shingles.</dd>

							<dt id="inflorescence">Inflorescence</dt>
							<dd>&#151; The discrete flowering portion or portions of a plant; a flower cluster.</dd>

							<dt id="involucre">Involucre</dt>
							<dd>&#151; A <a href="index.php#whorl">whorl</a> or <a href="#imbricate">imbricated</a> series of <a href="index.php#bract">bracts</a>, often appearing somewhat <a href="index.php#calyx">calyx</a>-like, typically <a href="index.php#subtend">subtending</a> the <a href="#head">head</a>.</dd>

							<dt id="ligulate">Ligulate</dt>
							<dd>&#151; Bearing a <a href="#ligule">ligule</a>.</dd>

							<dt id="ligule">Ligule</dt>
							<dd>&#151; The <a href="index.php#dilated">dilated</a> or flattened, spreading <a href="#limb">limb</a> of the composite <a href="#ray">ray flower</a>.</dd>

							<dt id="limb">Limb</dt>
							<dd>&#151; The expanded portion of a <a href="index.php#corolla">corolla</a> above the throat; the expanded portion of any petal.</dd>

							<dt id="pappus">Pappus</dt>
							<dd>&#151; A modification of the <a href="index.php#calyx">calyx</a>, usually in the Asteraceae family, such that the <a href="index.php#segment">segments</a> appear as a low <a href="#crown">crown</a>, a ring of <a href="index.php#scale">scales</a>, or fine hairs.</dd>

							<dt id="peduncle">Peduncle</dt>
							<dd>&#151; The stalk which supports a <a href="#head">head</a>.</dd>

							<dt id="phyllary">Phyllary</dt>
							<dd>&#151; A <a href="index.php#bract">bract</a> of the <a href="#involucre">involucre</a>.</dd>

							<dt id="ray">Ray</dt>
							<dd>&#151; A strap-shaped, <a href="#ligulate">ligulate</a>, typically <a href="index.php#margin">marginal</a>, flower in the <a href="#head">head</a> of a composite <a href="#inflorescence">inflorescence</a>.</dd>

							<dt id="rayflower">Ray flower</dt>
							<dd>&#151; A strap-shaped, <a href="#ligulate">ligulate</a>, typically <a href="index.php#margin">marginal</a>, flower in the head of a composite <a href="#inflorescence">inflorescence</a>. Also called <a href="#ligulate">ligulate</a> flower. Compare to <a href="#diskflower">disk flower</a>.</dd>

							<dt id="receptacle">Receptacle</dt>
							<dd>&#151; An enlarged or <a href="index.php#elongate">elongated</a> base of a <a href="#head">head</a> on which the flowers are borne.</dd>

							<dt id="staminaltube">Staminal tube</dt>
							<dd>&#151; The <a href="index.php#stamen">stamens</a> of a composite flower united into a ring.</dd>

							<dt id="uniseriate">Uniseriate</dt>
							<dd>&#151; Having only one series, or row, of parts; having only one row of <a href="#phyllary">phyllaries</a> (bracts) on the involucre.</dd>
							</dl>
						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
					
					<div id="content2"><!-- start of side content -->

						<div class="box">
							<h3>Family Glossaries</h3>
							<ul>
								<li><strong>Asteraceae &#151; Composites</strong></li>
								<li><a href="cyperaceae.php" title="Glossary for Cyperaceae">Cyperaceae &#151; Sedges</a></li>
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