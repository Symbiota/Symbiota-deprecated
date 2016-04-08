<?php
//error_reporting(E_ALL);
include_once('../../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> vPlants - Plant Glossary</title>
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
					<p>
					<a href="#a">A</a> | 
					<a href="#b">B</a> | 
					<a href="#c">C</a> |
					<a href="#d">D</a> |
					<a href="#e">E</a> |
					<a href="#f">F</a> |
					<a href="#g">G</a> |
					<a href="#h">H</a> |
					<a href="#i">I</a> |
					<a href="#j">J</a> |
					<a href="#k">K</a> |
					<a href="#l">L</a> |
					<a href="#m">M</a> |
					<a href="#n">N</a> |
					<a href="#o">O</a> |
					<a href="#p">P</a> |
					<a href="#q">Q</a> |
					<a href="#r">R</a> |
					<a href="#s">S</a> |
					<a href="#t">T</a> |
					<a href="#u">U</a> |
					<a href="#v">V</a> |
					<a href="#w">W</a> |
					<a href="#x">X</a> |
					<a href="#y">Y</a> |
					<a href="#z">Z</a> |
					</p>
					<h1>Plant Glossary</h1>

					<div style="margin:20px;">
						<p>
						This online glossary is based on the printed glossary from the Fourth Edition of <cite title="4th ed. Indianapolis: Indiana Academy of Science."><em>Plants of the Chicago Region</em> by Swink and Wilhelm (1994)</cite>.  The contents and the illustrated plates (linked from appropriate terms) are used with permission of the publisher, the Indiana Academy of Science.  Minor modifications and additions have been made from the print version in order to correct errors as well as make some points more clear.  The majority of the terms listed in this glossary are not used in the vPlants written description pages.  Other terms, which were deemed necessary to concisely provide clear descriptions have been used and are defined in the glossary for our users.  The terms in this glossary are those only with special meaning in a botanical context.  Words that users are unfamiliar with that are not listed in this glossary are readily defined in any dictionary, such as the online version of the <a href="http://www.m-w.com/dictionary.htm">Merriam-Webster Dictionary</a>.
						</p>

						<p><strong>The definitions given below relate to the usage of these terms with plants. Some of these terms have a somewhat different meaning in relation to other things, such as fungi.
						</strong></p>

						<dl id="glossdefs">

						<dt id="a">A</dt>
						<dd><hr /></dd>

						<dt id="a-">A-</dt>
						<dd>&#151; Without; not.</dd>

						<dt id="abaxial">Abaxial</dt>
						<dd>&#151; Said of a surface facing away from the <a href="#axis">axis</a> of the structure to which it is attached.</dd>

						<dt id="abortive">Abortive</dt>
						<dd>&#151; Defective; barren (unproductive); not developed.</dd>

						<dt id="abscission">Abscission</dt>
						<dd>&#151; A clean-cut scar or separating of a leaf from a self-healing.</dd>

						<dt id="acaulescent">Acaulescent</dt>
						<dd>&#151; Stemless, or apparently so.</dd>

						<dt id="achene">Achene</dt>
						<dd>&#151; A hard, one-seeded, <a href="#indehiscent">indehiscent</a> <a href="#nutlet">nutlet</a> with a tight <a href="#pericarp">pericarp</a>. [<a href="plate11.php" 
						title="Plate 11">Plate 11</a> and <a href="plate12.php" 
						title="Plate 12">Plate 12</a>]</dd>

						<dt id="acicular">Acicular</dt>
						<dd>&#151; Needle-like. [<a href="plate03.php" title="Plate 03">Plate 3</a>]</dd>

						<dt id="acorn">Acorn</dt>
						<dd>&#151; The specialized fruit of members of the <a href="#genus">genus</a> <em>Quercus</em> (oaks) that is composed of a nut with a cap of overlapping rows of <a href="#scale">scales</a> [<a href="plate11.php" title="Plate 11">Plate 11</a>]</dd>

						<dt id="actinomorphic">Actinomorphic</dt>
						<dd>&#151; <a href="#radiallysymmetrical">Radially symmetrical</a>; capable of being bisected into two or more similar planes.  Same as <a href="#regular">regular</a>[<a href="plate09.php" title="Plate 09">Plate 9</a>]</dd>

						<dt id="acuminate">Acuminate</dt>
						<dd>&#151; Tapering to a slender tip. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="acute">Acute</dt>
						<dd>&#151; Sharp-pointed. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="adaxial">Adaxial</dt>
						<dd>&#151; Said of a surface facing toward the <a href="#axis">axis</a> of the structure to which it is attached.</dd>

						<dt id="adherent">Adherent</dt>
						<dd>&#151; Joined to a dissimilar plant part. Compare <a href="#coherent">coherent</a>.</dd>

						<dt id="adnate">Adnate</dt>
						<dd>&#151; Same as <a href="#adherent">adherent</a>.</dd>

						<dt id="adventitious">Adventitious</dt>
						<dd>&#151; Sprouting or growing from unusual or abnormal places, such as roots originating from a stem, or buds appearing about wounds.</dd>

						<dt id="aerial">Aerial</dt>
						<dd>&#151; Said of structures originating above ground.</dd>

						<dt id="aggregated">Aggregated</dt>
						<dd>&#151; Crowded together. [<a href="plate11.php" title="Plate 11">Plate 11</a>]</dd>

						<dt id="allopatric">Allopatric</dt>
						<dd>&#151; Occupying different, though sometimes adjacent, regions.</dd>

						<dt id="alluvium">Alluvium</dt>
						<dd>&#151; Sands, silts, et cetera deposited by gradually moving water.</dd>

						<dt id="alternate">Alternate</dt>
						<dd>&#151; One after the other along an <a href="#axis">axis</a>; not <a href="#opposite">opposite</a>. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="ament">Ament</dt>
						<dd>&#151; A dry, usually <a href="#elongate">elongate</a> often drooping, scaly <a href="#spike">spike</a> bearing <a href="#imperfect">imperfect</a> flowers; a <a href="#catkin">catkin</a>. A frequent feature of woody plants. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="anastomose">Anastomose</dt>
						<dd>&#151; To interconnect, such as the <a href="#vein">veins</a> of a leaf.</dd>

						<dt id="anastomosing">Anastomosing</dt>
						<dd>&#151; Connecting and intersecting, forming a network.</dd>

						<dt id="androecium">Androecium</dt>
						<dd>&#151; The <a href="#staminate">staminate</a> portions of the flower.  Compare with <a href="#gynoecium">gynoecium</a>.</dd>

						<dt id="androgynous">Androgynous</dt>
						<dd>&#151; With <a href="#staminate">staminate</a> flowers situated above the <a href="#pistillate">pistillate</a> ones in the same <a href="#inflorescence">inflorescence</a>.</dd>

						<dt id="angiosperm">Angiosperm</dt>
						<dd>&#151; Flowering plant producing seeds enclosed in a structure derived from the <a href="#ovary">ovary</a>.</dd>

						<dt id="angulate">Angulate</dt>
						<dd>&#151; Having angles.</dd>

						<dt id="annual">Annual</dt>
						<dd>&#151; A plant which completes its life cycle in one year or less.</dd>

						<dt id="annulus">Annulus</dt>
						<dd>&#151; Tissue forming a ring or arranged in a circle.</dd>

						<dt id="anterior">Anterior</dt>
						<dd>&#151; On the side away from the main stem; <a href="#abaxial">abaxial</a>.</dd>

						<dt id="anther">Anther</dt>
						<dd>&#151; The pollen-bearing portion of the <a href="#stamen">stamen</a>. [<a href="plate09.php" title="Plate 09">Plate 9</a>]</dd>

						<dt id="anthesis">Anthesis</dt>
						<dd>&#151; Time of the year during which the <a href="#anther">anthers</a> are <a href="#dehiscence">dehiscing</a> and the <a href="#stigma">stigmas</a> are receptive to pollen; in a looser sense, the time of flowering.</dd>

						<dt id="antrorse">Antrorse</dt>
						<dd>&#151; Directed forward or upward. [<a href="plate06.php" title=Plate 06>Plate 6</a>]</dd>

						<dt id="aparinaceous">Aparinaceous</dt>
						<dd>&#151; Scratchy; clingy.</dd>

						<dt id="apetalous">Apetalous</dt>
						<dd>&#151; Having no petals.</dd>

						<dt id="apex">Apex</dt>
						<dd>&#151; The tip; end. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="apical">Apical</dt>
						<dd>&#151; Pertaining to the <a href="#apex">apex</a>.</dd>

						<dt id="apiculate">Apiculate</dt>
						<dd>&#151; Abruptly short-pointed. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="apiculus">Apiculus</dt>
						<dd>&#151; Abruptly short-<a href="#beak">beaked</a> or pointed.</dd>

						<dt id="appressed">Appressed</dt>
						<dd>&#151; Lying flat against a surface.</dd>

						<dt id="aquatic">Aquatic</dt>
						<dd>&#151; A plant which carries out its life cycle in water.</dd>

						<dt id="arachnoid">Arachnoid</dt>
						<dd>&#151; Cobweb-like.</dd>

						<dt id="arcuate">Arcuate</dt>
						<dd>&#151; Arching. [<a href=plate05.php title="Plate 05">Plate 5</a>]</dd>

						<dt id="areola">Areola</dt>
						<dd>&#151; A small space on or near the surface of some <a href="#vegetative">vegetative</a> organ, usually formed by <a href="#anastomosing">anastomosing</a> <a href="#vein">veins</a>.</dd>

						<dt id="areolae">Areolae</dt>
						<dd>&#151; The spaces between the <a href="#vein">veins</a> of a leaf or some similar structure.</dd>

						<dt id="aril">Aril</dt>
						<dd>&#151; An appendage growing out from a seed.</dd>

						<dt id="arillate">Arillate</dt>
						<dd>&#151; Having an <a href="#aril">aril</a>.</dd>

						<dt id="aristate">Aristate</dt>
						<dd>&#151; <a href="#awn">Awned</a>; tipped by a stiff <a href="#bristle">bristle</a>. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="aromatic">Aromatic</dt>
						<dd>&#151; Having a fragrant smell, sometimes only if broken or crushed.</dd>

						<dt id="article">Article</dt>
						<dd>&#151; Section of a <a href="#legume">legume</a> <a href="#pod">pod</a>, separated from other sections by a constriction or partition.</dd>

						<dt id="articulation">Articulation</dt>
						<dd>&#151; A joint.</dd>

						<dt id="ascending">Ascending</dt>
						<dd>&#151; Growing or directed in an upward direction, or at least tending to. [<a href="plate01.php" title="Plate 01">Plate 1</a>]</dd>

						<dt id="asymmetrical">Asymmetrical</dt>
						<dd>&#151; Unequally developed on either side of a common <a href="#axis">axis</a>.  Opposite of <a href="#symmetrical">symmetrical</a>.</dd>

						<dt id="atom">Atom</dt>
						<dd>&#151; Small, usually <a href="#resinous">resinous</a>, dot or <a href="#gland">gland</a>.</dd>

						<dt id="atomate">Atomate</dt>
						<dd>&#151; Having small, usually <a href="#resinous">resinous</a>, dots or <a href="#gland">glands</a>.</dd>

						<dt id="attenuate">Attenuate</dt>
						<dd>&#151; Gradually tapered to a slender tip. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="auricle">Auricle</dt>
						<dd>&#151; An ear-shaped appendage or <a href="#lobe">lobe</a> (such often being quite small).</dd>

						<dt id="auriculate">Auriculate</dt>
						<dd>&#151; With an ear-shaped flange or <a href="#lobe">lobe</a>. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="autecology">Autecology</dt>
						<dd>&#151; Pertaining to the ecology of an individual <a href="#species">species</a>.</dd>

						<dt id="awn">Awn</dt>
						<dd>&#151; A stiff <a href="#bristle">bristle</a>, usually situated at the tip of a leaf or <a href="#perianth">perianth</a> element, or (in grasses), at the tip of a <a href="#glume">glume</a> or <a href="#lemma">lemma</a>.</dd>

						<dt id="axil">Axil</dt>
						<dd>&#151; The area or angle formed between the base of an organ and the structure from which it originated.  Such as the upper angle between the leaf base and the stem.</dd>

						<dt id="axillary">Axillary</dt>
						<dd>&#151; Pertaining to the <a href="#axil">axil</a>.</dd>

						<dt id="axis">Axis</dt>
						<dd>&#151; The central part of a longitudinal support (usually of a stem or <a href="#inflorescence">inflorescence</a>) on which organs or parts are arranged.</dd>


						<dt id="b">B</dt>
						<dd><hr /></dd>

						<dt id="barbellate">Barbellate</dt>
						<dd>&#151; Beset with fine barbs. [<a href="plate06.php" 
						title="Plate 06">Plate 6</a>] </dd>

						<dt id="barren">Barren</dt>
						<dd>&#151; Land with sparse vegetation, often with bedrock at or very near the surface (especially in mountainous states, often populated with scrubby pines).</dd>

						<dt id="basal">Basal</dt>
						<dd>&#151; Pertaining to the base of the plant or some organ of the plant.</dd>

						<dt id="basifixed">Basifixed</dt>
						<dd>&#151; Attached by the base.</dd>

						<dt id="beak">Beak</dt>
						<dd>&#151; A slender <a href="#terminal">terminal</a> <a href="#process">process</a>, usually abruptly differentiated from the general outline of the organ from which it originates; usually applied to fruits and <a href="#pistil">pistils</a>.</dd>

						<dt id="berry">Berry</dt>
						<dd>&#151; A usually fleshy or pulpy fruit, typically with two or more seeds developed from a single <a href="#ovary">ovary</a>. [<a href="plate11.php" title="Plate 11">Plate 11</a>]</dd>

						<dt id="biconvex">Biconvex</dt>
						<dd>&#151; <a href="#convex">Convex</a> on both surfaces. </dd>

						<dt id="bidentate">Bidentate</dt>
						<dd>&#151; Having two <a href="#teeth">teeth</a>. </dd>

						<dt id="biennial">Biennial</dt>
						<dd>&#151; A plant which requires two years to complete a life cycle, the first year typically forming a <a href="#rosette">rosette</a>, the second year forming an <a href="#inflorescence">inflorescence</a>.</dd>

						<dt id="bifid">Bifid</dt>
						<dd>&#151; <a href="#cleft">Cleft</a> into two parts, usually at the summit of some organ.</dd>

						<dt id="bilabiate">Bilabiate</dt>
						<dd>&#151; Two-<a href="#lip">lipped</a>; most often applied to <a href="#zygomorphic">zygomorphic</a> <a href="#perianth">perianths</a>. [<a href="plate11.php" title="Plate 11">Plate 11</a>]</dd>

						<dt id="bilateral">Bilateral</dt>
						<dd>&#151; Having two equal sides on either side of an <a href="#axis">axis</a>.</dd>

						<dt id="bilaterallysymmetrical">Bilaterally symmetrical</dt>
						<dd>&#151; Referring to a <a href="#calyx">calyx or <a href="#corolla">corolla</a> that is <a href="#zygomorphic">zygomorphic</a>, capable of being divided into two equal halves along one plane only. [<a href="plate09.php" 
						title="Plate 09">Plate 9</a>]</dd>

						<dt id="bilobed">Bilobed</dt>
						<dd>&#151; Having two <a href="#lobe">lobes</a>.</dd>

						<dt id="bipinnate">Bipinnate</dt>
						<dd>&#151; Twice <a href="#pinnate">pinnately</a> <a href="#compound">compound</a>. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="bipinnatifid">Bipinnatifid</dt>
						<dd>&#151; Twice <a href="#pinnatifid">pinnatifid</a>.</dd>

						<dt id="biternate">Biternate</dt>
						<dd>&#151; Twice ternate; when the divisions of a <a href="#ternate">ternate</a> leaf are divided into three. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="bivalved">Bivalved</dt>
						<dd>&#151; Having two sides or units originating at a common point.</dd>

						<dt id="bladder">Bladder</dt>
						<dd>&#151; An <a href="#inflated">inflated</a> <a href="#sac">sac</a> or <a href="#receptacle">receptacle</a> containing a fluid.</dd>

						<dt id="blade">Blade</dt>
						<dd>&#151; The expanded portion of a foliar or floral organ.</dd>

						<dt id="bloom">Bloom</dt>
						<dd>&#151; A whitish powdery covering of the surface, often of a waxy nature.</dd>

						<dt id="blunt">Blunt</dt>
						<dd>&#151; <a href="#obtuse">Obtuse</a>, round-tipped.</dd>

						<dt id="bog">Bog</dt>
						<dd>&#151; A wetland, usually <a href="#peat">peaty</a>, in which the substrate is typically acid.</dd>

						<dt id="bole">Bole</dt>
						<dd>&#151; A strong unbranched <a href="#caudex">caudex</a>; the trunk of a tree.</dd>

						<dt id="boreal">Boreal</dt>
						<dd>&#151; Northern.</dd>

						<dt id="bract">Bract</dt>
						<dd>&#151; A reduced leaf or <a href="#scale">scale</a>, typically one which <a href="#subtend">subtends</a> a <a href="#pedicel">pedicel</a> or <a href="#inflorescence">inflorescence</a>, but it also can refer to minute leaves on a stem. [<a href="plate09.php" title="Plate 09">Plate 9</a>]</dd>

						<dt id="bracteal">Bracteal</dt>
						<dd>&#151; Pertaining to a <a href="#bract">bract</a>.</dd>

						<dt id="bracteate">Bracteate</dt>
						<dd>&#151; Having <a href="#bract">bracts</a></s>.</dd>

						<dt id="bracteole">Bracteole</dt>
						<dd>&#151; A small <a href="#bract">bract</a>, typically that which <a href="#subtend">subtends</a> a flower, the <a href="#pedicel">pedicel</a> of which is already <a href="#subtend">subtended</a> by a <a href="#bract">bract</a>.</dd>

						<dt id="bractlet">Bractlet</dt>
						<dd>&#151; A <a href="#secondary">secondary</a> <a href="#bract">bract</a>, as one upon the <a href="#pedicel">pedicel</a> of a flower.</dd>

						<dt id="branchlet">Branchlet</dt>
						<dd>&#151; A division of a branch, smaller than the main branch.</dd>

						<dt id="bristle">Bristle</dt>
						<dd>&#151; Stiff hair or <a href="#trichome">trichome</a>.</dd>

						<dt id="bristly">Bristly</dt>
						<dd>&#151; With <a href="#bristle">bristles</a>. [<a href="plate07.php" title="Plate 07">Plate 7</a>]</dd>

						<dt id="bronzing">Bronzing</dt>
						<dd>&#151; Referring especially to the color of foliage after a winter; usually a metallic bronze or coppery color.</dd>

						<dt id="bud">Bud</dt>
						<dd>&#151; Very young developing tissue enclosed in <a href="#scale">scales</a> or <a href="#valve">valves</a>. [<a href="plate02.php" title="Plate 02">Plate 2</a> and [<a href="plate07.php" title="Plate 07">Plate 7</a>]</dd>

						<dt id="bulb">Bulb</dt>
						<dd>&#151; A short, often subglobose, stem surrounded by <a href="#scale">scales</a> or modified leaves, typically underground.</dd>

						<dt id="bulbil">Bulbil</dt>
						<dd>&#151; A small, usually <a href="#axillary">axillary</a> <a href="#bulb">bulb</a>-like organ. [<a href="plate07.php" title="Plate 07">Plate 7</a>]</dd>

						<dt id="bulblet">Bulblet</dt>
						<dd>&#151; A small bulbiform organ, particularly one proliferating from a leaf <a href="#axil">axil</a> or <a href="#sterile">sterile</a> flower. [Plate 7]</dd>

						<dt id="bulbous">Bulbous</dt>
						<dd>&#151; Having the character of a <a href="#bulb">bulb</a>.</dd>

						<dt id="bullate">Bullate</dt>
						<dd>&#151; Blistered or puckered.</dd>

						<dt id="bur">Bur</dt>
						<dd>&#151; A spiny or prickly, usually dry, fruit or cluster of fruits.</dd>

						<dt id="c">C</dt>
						<dd><hr /></dd>

						<dt id="caducous">Caducous</dt>
						<dd>&#151; Falling off early or prematurely; <a href="#deciduous">deciduous</a>.</dd>

						<dt id="caespitose">Caespitose</dt>
						<dd>&#151; See <a href="#cespitose">cespitose</a>.</dd>

						<dt id="calcareous">Calcareous</dt>
						<dd>&#151; Limy; as in water or soil made basic by a prevailing amount of calcium ions.</dd>

						<dt id="calciphilous">Calciphilous</dt>
						<dd>&#151; Lime-loving.</dd>

						<dt id="callosity">Callosity</dt>
						<dd>&#151; A hardened thickening.</dd>

						<dt id="callous">Callous</dt>
						<dd>&#151; Having the texture of a <a href="#callus">callus</a>.</dd>

						<dt id="callus">Callus</dt>
						<dd>&#151; A hard protuberance or <a href="#callosity">callosity</a>; often (in grasses) the swelling at the base or joint of insertion of the <a href="#lemma">lemma</a> or <a href="#palea">palea</a>.</dd>

						<dt id="calyx">Calyx</dt>
						<dd>&#151; The outer, usually green, series of <a href="#perianth">perianth</a> parts; the <a href="#sepal">sepals</a> taken collectively.</dd>

						<dt id="cambium">Cambium</dt>
						<dd>&#151; Thin layer of meristematic cells, typically that which gives rise to <a href="#secondary">secondary</a> xylem or phloem.</dd>

						<dt id="campanulate">Campanulate</dt>
						<dd>&#151; Bell-shaped or cup-shaped, typically with a flared or enhanced rim. [<a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="canaliculate">Canaliculate</dt>
						<dd>&#151; Having a groove or channel.</dd>

						<dt id="cancellate">Cancellate</dt>
						<dd>&#151; Having a net-like or sculptured surface.</dd>

						<dt id="cane">Cane</dt>
						<dd>&#151; The <a href="#elongate">elongated</a> new shoot of <a href="#shrub">shrubs</a>, such as in <em>Rubus</em>.</dd>

						<dt id="canescent">Canescent</dt>
						<dd>&#151; Densely beset with matted, often grayish-<a href="#pubescent">pubescent</a>, hairs. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="capillary">Capillary</dt>
						<dd>&#151; Hair-like.</dd>

						<dt id="capitate">Capitate</dt>
						<dd>&#151; <a href="#head">Head</a>-like; very densely clustered.</dd>

						<dt id="capitulum">Capitulum</dt>
						<dd>&#151; A small <a href="#head">head</a> of flowers. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="capsule">Capsule</dt>
						<dd>&#151; A dry <a href="#dehiscent">dehiscent</a> fruit composed of two or more <a href="#carpel">carpels</a>. [<a href="plate11.php" title="Plate 11">Plate 11</a>]</dd>

						<dt id="carinate">Carinate</dt>
						<dd>&#151; <a href="#keel">Keeled</a>. [<a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="carpel">Carpel</dt>
						<dd>&#151; A <a href="#pistil">pistil</a>, or one of the units of a <a href="#compound">compound</a> <a href="#pistil">pistil</a>.</dd>

						<dt id="carpellate">Carpellate</dt>
						<dd>&#151; Having <a href="#carpel">carpels</a>.</dd>

						<dt id="cartilaginous">Cartilaginous</dt>
						<dd>&#151; Cartilage-like; firm and tough but neither rigid nor bony.</dd>

						<dt id="caryopsis">Caryopsis</dt>
						<dd>&#151; In grasses, a seed-like fruit with a thin <a href="#pericarp">pericarp</a>; a <a href="#grain">grain</a>. [<a href="plate12.php" title="Plate 12">Plate 12</a>]</dd>

						<dt id="catkin">Catkin</dt>
						<dd>&#151; Same as <a href="#ament">ament</a>. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="caudate">Caudate</dt>
						<dd>&#151; Tail-like, or bearing a tail-like appendage. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd

						<dt id="caudex">Caudex</dt>
						<dd>&#151; The <a href="#ligneous">ligneous</a> or woody base or a <a href="#perennial">perennial</a> plant.</dd>

						<dt id="caulescent">Caulescent</dt>
						<dd>&#151; Having an above-ground stem.</dd>

						<dt id="cauline">Cauline</dt>
						<dd>&#151; Pertaining to the stem or features of the stem.</dd>

						<dt id="cespitose">Cespitose</dt>
						<dd>&#151; <a href="#tufted">Tufted</a>, usually referring to the compact arrangement of the stem bases with respect to each other and their position in the soil; sometimes spelled <a href="#caespitose">caespitose</a>.</dd>

						<dt id="chaff">Chaff</dt>
						<dd>&#151; Dry, <a href="#scale">scaly</a>, often small, <a href="#bract">bracts</a>; typically referring to those <a href="#scale">scales</a> <a href="#subtend">subtending</a> the individual flowers in composite <a href="#head">heads</a>.</dd>

						<dt id="chalaza">Chalaza</dt>
						<dd>&#151; The <a href="#basal">basal</a> part of an <a href="#ovule">ovule</a> where it is attached to the funiculus.</dd>

						<dt id="chambered">Chambered</dt>
						<dd>&#151; Areas in the hollow <a href="#pith">pith</a> of twigs where vertical walls occur at close intervals. [<a href="plate07.php" title="Plate 07">Plate 7</a>]</dd>

						<dt id="chartaceous">Chartaceous</dt>
						<dd>&#151; Thin, but firm; resembling the more ancient writing paper.</dd>

						<dt id="chink">Chink</dt>
						<dd>&#151; A modified <a href="#pore">pore</a>, usually involving an opening in the <a href="#anther">anther</a>.</dd>

						<dt id="chlorophyll">Chlorophyll</dt>
						<dd>&#151; The green photosynthetic pigment.</dd>

						<dt id="cilia">Cilia</dt>
						<dd>&#151; Hairs or slender <a href="#bristle">bristles</a> confined to the <a href="#margin">margins</a> of some organ.</dd>

						<dt id="ciliate">Ciliate</dt>
						<dd>&#151; Fringed with <a href="#cilia">cilia</a>; bearing <a href="#cilia">cilia</a> on the <a href="#margin">margins</a>. [<a href="plate04.php" title="Plate 04">Plate 4</a>]</dd>

						<dt id="Ciliolate">Ciliolate</dt>
						<dd>&#151; Minutely <a href="#ciliate">ciliate</a>.</dd>

						<dt id="ciliolulate">Ciliolulate</dt>
						<dd>&#151; Minutely <a href="#ciliolulate">ciliolulate</a>.</dd>

						<dt id="cinereous">Cinereous</dt>
						<dd>&#151; Ash-gray colored.</dd>

						<dt id="circinate">Circinate</dt>
						<dd>&#151; Rolled coilwise from the top downward, as in unopened fern <a href="#frond">fronds</a>.</dd>

						<dt id="circumscissile">Circumscissile</dt>
						<dd>&#151; Pertaining to the <a href="#dehiscence">dehiscence</a> of a <a href="#capsule">capsule</a> (pyxis) which opens by a circular, horizontal line, the top usually coming off as a lid. [<a href="plate11.php" title="Plate 11">Plate 11</a>]</dd>

						<dt id="clammy">Clammy</dt>
						<dd>&#151; Sticky-hairy.</dd>

						<dt id="clasping">Clasping</dt>
						<dd>&#151; Tending to encircle or invest, as in the base of a leaf which forms partly around the stem to which it is attached. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="clavate">Clavate</dt>
						<dd>&#151; Club-shaped; <a href="#dilated">dilated</a> upwards.</dd>

						<dt id="claw">Claw</dt>
						<dd>&#151; The narrowed base or stalk of some petals.</dd>

						<dt id="cleft">Cleft</dt>
						<dd>&#151; <a href="#distinct">Distinctly</a> divided or <a href="#incised">incised</a>, usually to about the middle. [<a href="plate04.php" title="Plate 04">Plate 4</a>]</dd>

						<dt id="cleistogamous">Cleistogamous</dt>
						<dd>&#151; Fertilized in the bud, without the opening of the flower.</dd>

						<dt id="clone">Clone</dt>
						<dd>&#151; A group of individuals, resulting from <a href="#vegetative">vegetative</a> multiplication; any 
						plant propagated <a href="#vegetative">vegetatively</a> and therefore, presumably a duplicate of its parent.</dd>

						<dt id="coarse">Coarse</dt>
						<dd>&#151; Rough.</dd>

						<dt id="column">Column</dt>
						<dd>&#151; Sheath or structure formed by the uniting of <a href="#stamen">stamens</a> around the <a href="#pistil">pistil</a>.</dd>

						<dt id="columnar">Columnar</dt>
						<dd>&#151; Shaped like a column or pillar.</dd>

						<dt id="coma">Coma</dt>
						<dd>&#151; A dense tuft of hairs, often resembling a beard, attached to a seed.</dd>

						<dt id="comose">Comose</dt>
						<dd>&#151; Bearded, with a <a href="#coma">coma</a>. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="compound">Compound</dt>
						<dd>&#151; Pertaining to leaves which are divided into distinct <a href="#leaflet">leaflets</a>.</dd>

						<dt id="compressed">Compressed</dt>
						<dd>&#151; Strongly flattened, especially <a href="#lateral">laterally</a>. </dd>

						<dt id="concave">Concave</dt>
						<dd>&#151; Hollow; in the context of the interior of a curved surface; opposite 
						of <a href="#convex">convex</a>.</dd>

						<dt id="concentric">Concentric</dt>
						<dd>&#151; Two or more circles having a center in common.</dd>

						<dt id="conduplicate">Conduplicate</dt>
						<dd>&#151; Folded together lengthwise.</dd>

						<dt id="cone">Cone</dt>
						<dd>&#151; Three-dimensional object with a circular base, the sides all tapering to a point at the summit; the fruit of pines and their relatives; <a href="#spore">spore</a> case of <em>Equisetum</em>. Compare <a href="#strobile">strobile</a>.</dd>

						<dt id="conical">Conical</dt>
						<dd>&#151; <a href="#cone">Cone</a>-shaped.</dd>

						<dt id="coniferous">Coniferous</dt>
						<dd>&#151; <a href="#cone">Cone</a>-bearing.</dd>

						<dt id="connate">Connate</dt>
						<dd>&#151; Fused or <a href="#united">united</a> to a similar plant part. Compare <a href="#adnate">adnate</a>.</dd>

						<dt id="connective">Connective</dt>
						<dd>&#151; The part of the <a href="#stamen">stamen</a> which connects the two parts of an <a href="#anther">anther</a>.</dd>

						<dt id="connivent">Connivent</dt>
						<dd>&#151; Coming together; meeting at a common point but not fused.</dd>

						<dt id="conspecific">Conspecific</dt>
						<dd>&#151; Said of two or more taxa belonging to the same <a href="#species">species</a>.</dd>

						<dt id="contracted">Contracted</dt>
						<dd>&#151; Abruptly narrowed or reduced.</dd>

						<dt id="convex">Convex</dt>
						<dd>&#151; Curved or rounded, as the exterior of a circular form viewed from 
						without; opposite of <a href="#concave">concave</a>.</dd>

						<dt id="convolute">Convolute</dt>
						<dd>&#151; Rolled up longitudinally.</dd>

						<dt id="coralline">Coralline</dt>
						<dd>&#151; White and coral-like.</dd>

						<dt id="cordate">Cordate</dt>
						<dd>&#151; Heart-shaped. [<a href="plate03.php" title="Plate 03">Plate 3</a> and <a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="coriaceous">Coriaceous</dt>
						<dd>&#151; Leather-like.</dd>

						<dt id="corm">Corm</dt>
						<dd>&#151; A solid, <a href="#bulb">bulb</a>-like part, usually <a href="#subterranean">subterranean</a>, as the "<a href="#bulb">bulb</a>" of a crocus or gladiolus. [<a href="plate01.php" title="Plate 01">Plate 1</a>]</dd>

						<dt id="cormose">Cormose</dt>
						<dd>&#151; Bearing <a href="#corm">corms</a>.</dd>

						<dt id="corniculate">Corniculate</dt>
						<dd>&#151; Furnished with a little <a href="#horn">horn</a>.</dd>

						<dt id="corolla">Corolla</dt>
						<dd>&#151; The inner series of <a href="#perianth">perianth</a> parts, often colored; the petals taken collectively.</dd>

						<dt id="corona">Corona</dt>
						<dd>&#151; A short-cylindric or <a href="#crown">crown</a>-like modification of the <a href="#corolla">corolla</a>; also, a small <a href="#crown">crown</a> in the throat of a <a href="#corolla">corolla</a>, as in <em>Narcissus</em>.</dd>

						<dt id="coronate">Coronate</dt>
						<dd>&#151; With a <a href="#corona">corona</a>. [<a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="corrugated">Corrugated</dt>
						<dd>&#151; Wrinkled or folded longitudinally.</dd>

						<dt id="corymb">Corymb</dt>
						<dd>&#151; An arrangement of the <a href="#inflorescence">inflorescence</a> in which stalked flowers are situated along a central <a href="#axis">axis</a>, but with the flowers all nearly or quite attaining the same elevation with respect to each other, the oldest at the edges. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="corymbiform">Corymbiform</dt>
						<dd>&#151; Resembling a <a href="#corymb">corymb</a>.</dd>

						<dt id="corymbose">Corymbose</dt>
						<dd>&#151; <a href="#corymb">Corymb</a>-like.</dd>

						<dt id="corymbulose">Corymbulose</dt>
						<dd>&#151; Resembling small <a href="#corymb">corymbs</a>.</dd>

						<dt id="costate">Costate</dt>
						<dd>&#151; Ribbed; having one or longitudinal <a href="#nerve">nerves</a>.</dd>

						<dt id="cottony">Cottony</dt>
						<dd>&#151; With the consistency of cotton.</dd>

						<dt id="cotyledon">Cotyledon</dt>
						<dd>&#151; A seed leaf; the first leaf (or leaves) to appear during the 
						development of a seedling.</dd>

						<dt id="crateriform">Crateriform</dt>
						<dd>&#151; Saucer-shaped or cup-shaped (usually shallowly so).</dd>

						<dt id="crenate">Crenate</dt>
						<dd>&#151; Very shallowly <a href="#toothed">toothed</a> with broad, <a href="#blunt">blunt</a> <a href="#teeth">teeth</a>. [<a href="plate04.php" title="Plate 04">Plate 4</a>]</dd>

						<dt id="crenulate">Crenulate</dt>
						<dd>&#151; Minutely <a href="#crenate">crenate</a>. [<a href="plate04.php" title="Plate 04">Plate 4</a>]</dd>

						<dt id="crest">Crest</dt>
						<dd>&#151; A ridge or strong <a href="#keel">keel</a>, typically along one side of an <a href="#achene">achene</a> or <a href="#nutlet">nutlet</a>; also, the <a href="#elevated">elevated</a> portion of a petal, as in some <em>Iris</em>.</dd>

						<dt id="crown">Crown</dt>
						<dd>&#151; That portion of a stem at the ground surface; also, in the Asteraceae family, <a href="#scale">scales</a> or <a href="#awn">awns</a> at the summit of an <a href="#achene">achene</a>.</dd>

						<dt id="cruciform">Cruciform</dt>
						<dd>&#151; Cross-shaped. [<a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="cucullate">Cucullate</dt>
						<dd>&#151; <a href="#hood">Hood</a>-shaped.</dd>

						<dt id="culm">Culm</dt>
						<dd>&#151; The stem of grasses, sedges, and rushes. [<a href="plate12.php" title="Plate 12">Plate 12</a>]</dd>

						<dt id="cultivar">Cultivar</dt>
						<dd>&#151; A cultivated variation.</dd>

						<dt id="cuneate">Cuneate</dt>
						<dd>&#151; Wedge-shaped. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="cusp">Cusp</dt>
						<dd>&#151; An abrupt point or tooth.</dd>

						<dt id="cuspidate">Cuspidate</dt>
						<dd>&#151; Bearing a <a href="#cusp">cusp</a>. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="cuticle">Cuticle</dt>
						<dd>&#151; An often waxy, outer film of dead epidermal cells.</dd>

						<dt id="cyathium">Cyathium</dt>
						<dd>&#151; The cup-like <a href="#involucre">involucre</a> characteristic of the <a href="#genus">genus</a> <em>Euphorbia</em>.</dd>

						<dt id="cylindrical">Cylindrical</dt>
						<dd>&#151; Shaped like a cylinder.</dd>

						<dt id="cyme">Cyme</dt>
						<dd>&#151; An often flat-topped <a href="#inflorescence">inflorescence</a>, the central <a href="#floret">floret</a> of which blooms first. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="cymose">Cymose</dt>
						<dd>&#151; Resembling a <a href="#cyme">cyme</a>.</dd>

						<dt id="cymule">Cymule</dt>
						<dd>&#151; A small, often compacted and usually few-flowered, <a href="#cyme">cyme</a>.</dd>

						<dt id="d">D</dt>
						<dd><hr /></dd>

						<dt id="deciduous">Deciduous</dt>
						<dd>&#151; Pertaining to plants which shed their <a href="#herbaceous">herbaceous</a> tissues after one year's growth; not <a href="#evergreen">evergreen</a>; <a href="#caducous">caducous</a>.</dd>
						 
						<dt id="decompound">Decompound</dt>
						<dd>&#151; Divided or <a href="#compound">compound</a> more than once. </dd>

						<dt id="decumbent">Decumbent</dt>
						<dd>&#151; Trailing along the ground but with the <a href="#inflorescence">inflorescence</a> or summit of the stem <a href="#ascending">ascending</a> or <a href="#erect">erect</a>. [<a href="plate01.php" title="Plate 01">Plate 1</a>]</dd>

						<dt id="decurrent">Decurrent</dt>
						<dd>&#151; Usually pertaining to some flat, foliar organ, the tissue of which continues beyond its base down an <a href="#elongate">elongate</a> <a href="#axis">axis</a> (usually a stem or <a href="#petiole">petiole</a>).</dd>

						<dt id="decussate">Decussate</dt>
						<dd>&#151; <a href="#opposite">Opposite</a> leaves in four rows up and down the stem; alternating in pairs at right angles.</dd>

						<dt id="deflexed">Deflexed</dt>
						<dd>&#151; Abruptly directed downward; <a href="#reflexed">reflexed</a>.</dd>

						<dt id="dehiscent">Dehiscent</dt>
						<dd>&#151; Said of a fruit or <a href="#anther">anther</a> that opens by <a href="#suture">sutures</a>, <a href="#valve">valves</a>, slits, <a href="#pore">pores</a>, etc.</dd>

						<dt id="dehiscence">Dehiscence</dt>
						<dd>&#151; The opening of a fruit or <a href="#anther">anther</a> by <a href="#suture">sutures</a>, <a href="#valve">valves</a>, slits, <a href="#pore">pores</a>, etc.</dd>

						<dt id="deltoid">Deltoid</dt>
						<dd>&#151; Triangular. [<a href="plate03.php" title="Plate 03">Plate 3</a>]</dd>

						<dt id="dentate">Dentate</dt>
						<dd>&#151; <a href="#toothed">Toothed</a>, the <a href="#teeth">teeth</a> perpendicular to the <a href="#margin">margin</a>. [<a href="plate04.php" title="Plate 04">Plate 4</a>]</dd>

						<dt id="denticulate">Denticulate</dt>
						<dd>&#151; Minutely <a href="#dentate">dentate</a>. [<a href="plate04.php" title="Plate 04">Plate 4</a>]</dd>

						<dt id="depauperate">Depauperate</dt>
						<dd>&#151; Poor; with little sustenance or vigor.</dd>

						<dt id="determinate">Determinate</dt>
						<dd>&#151; <a href="#inflorescence">Inflorescence</a> whose <a href="#terminal">terminal</a> flowers open first. See <a href="#indeterminate">indeterminate</a>.</dd>

						<dt id="diadelphous">Diadelphous</dt>
						<dd>&#151; Combined into two, often unequal sets; primarily spoken of in connection with the Fabaceae family, where the flowers typically have a set of <a href="#stamen">stamens</a> consisting of nine and another consisting of only one <a href="#stamen">stamen</a>.</dd>

						<dt id="diaphragm">Diaphragm</dt>
						<dd>&#151; A dividing membrane or partition, a feature of <a href="#chambered">chambered</a> <a href="#pith">pith</a>.</dd>

						<dt id="dichasium">Dichasium</dt>
						<dd>&#151; A <a href="#cyme">cyme</a> with two <a href="#lateral">lateral</a> <a href="#axis">axes</a>.</dd>

						<dt id="dichotomous">Dichotomous</dt>
						<dd>&#151; Forking <a href="#regular">regularly</a> in two directions.</dd>

						<dt id="dicot">Dicot</dt>
						<dd>&#151; <a href="#angiosperm">Angiosperm</a> with 2 seed leaves.</dd>

						<dt id="diffuse">Diffuse</dt>
						<dd>&#151; Widely or loosely spreading.</dd>

						<dt id="digitate">Digitate</dt>
						<dd>&#151; Typically referring to a <a href="#compound">compound</a> leaf in which the <a href="#leaflet">leaflets</a> originate from a common point at the <a href="#apex">apex</a> of a <a href="#petiole">petiole</a>; also spoken of a flower cluster.</dd>

						<dt id="dilated">Dilated</dt>
						<dd>&#151; Expanded or enlarged.</dd>

						<dt id="dimorphic">Dimorphic</dt>
						<dd>&#151; Having two forms.</dd>

						<dt id="dioecious">Dioecious</dt>
						<dd>&#151; Pertaining to plants, individuals of which bear either <a href="#staminate">staminate</a> or <a href="#pistillate">pistillate</a> flowers but not both.</dd>

						<dt id="disarticulate">Disarticulate</dt>
						<dd>&#151; To separate.</dd>

						<dt id="disk">Disk or disc</dt>
						<dd>&#151; The central portion of a <a href="#capitate">capitate</a> <a href="#inflorescence">inflorescence</a>, or the <a href="#receptacle">receptacle</a> of such an <a href="#inflorescence">inflorescence</a>; also, a structure formed by the coalescence of <a href="#stigma">stigmas</a> as in the Papaveraceae family; also, the development of the <a href="#receptacle">receptacle</a> at or around the base of a petals, as in <em>Acer</em> and <em>Euonymus</em>. [<a href="plate09.php" title="Plate 09">Plate 9</a> and [<a href="plate12S.php" title="Plate 12">Plate 12</a>]</dd>

						<dt id="diskflower">Disk flowers</dt>
						<dd>&#151; In the Asteraceae family, the central, <a href="#tubular">tubular</a> flowers of the <a href="#head">head</a>. Compare <a href="#rayflower">ray flower</a>.</dd>

						<dt id="dissected">Dissected</dt>
						<dd>&#151; Cut or divided into narrow <a href="#segment">segments</a>.</dd>

						<dt id="distal">Distal</dt>
						<dd>&#151; The direction or point away from the point of attachment.</dd>

						<dt id="distichous">Distichous</dt>
						<dd>&#151; Arranged in two vertical series; two-<a href="#ranked">ranked</a>.</dd>

						<dt id="distigmatic">Distigmatic</dt>
						<dd>&#151; Bearing two <a href="#stigma">stigmas</a>.</dd>

						<dt id="distinct">Distinct</dt>
						<dd>&#151; Separate, and usually evident.</dd>

						<dt id="divaricate">Divaricate</dt>
						<dd>&#151; Widely spreading or <a href="#divergent">divergent</a>.</dd>

						<dt id="divergent">Divergent</dt>
						<dd>&#151; Directed away from each other.</dd>

						<dt id="dorsal">Dorsal</dt>
						<dd>&#151; Relating to the back or outer surface of an organ.  Compare <a href="#ventral">ventral</a></dd>

						<dt id="downy">Downy</dt>
						<dd>&#151; Covered with soft hair.</dd>

						<dt id="drupe">Drupe</dt>
						<dd>&#151; A typically one-<a href="#locular">locular</a>, fleshy or pulpy fruit with a hard or stony center. </dd>

						<dt id="drupelet">Drupelet</dt>
						<dd>&#151; A small <a href="#drupe">drupe</a>. [<a href="plate11.php" title="Plate 11">Plate 11</a>]</dd>


						<dt id="e">E</dt>
						<dd><hr /></dd>

						<dt id="ex">E- or Ex</dt>
						<dd>&#151; Without; not.</dd>

						<dt id="ebracteate">Ebracteate</dt>
						<dd>&#151; Without <a href="#bract">bracts</a>.</dd>

						<dt id="eccentric">Eccentric</dt>
						<dd>&#151; Off center, or one-sided.</dd>

						<dt id="echinate">Echinate</dt>
						<dd>&#151; Bearing stout, often <a href="#blunt">bluntish</a>, <a href="#spine">spines</a> or <a href="#prickle">prickles</a>. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="eciliate">Eciliate</dt>
						<dd>&#151; Without <a href="#cilia">cilia</a>.</dd>

						<dt id="eglandular">Eglandular</dt>
						<dd>&#151; Without <a href="#gland">glands</a>.</dd>

						<dt id="elevated">Elevated</dt>
						<dd>&#151; Raised, often forming a ridge.</dd>

						<dt id="ellipsoid">Ellipsoid</dt>
						<dd>&#151; Solid but with an <a href="#elliptic">elliptical</a> outline.</dd>

						<dt id="elliptic">Elliptic</dt>
						<dd>&#151; A circular shape which has been <a href="#lateral">laterally</a> <a href="#compressed">compressed</a>, widest about the middle. [<a href="plate03.php" title="Plate 03">Plate 3</a>]</dd>

						<dt id="elongate">Elongate</dt>
						<dd>&#151; Drawn out into a form much longer than wide.</dd>

						<dt id="emarginate">Emarginate</dt>
						<dd>&#151; With a shallow notch at the tip. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="emergent">Emergent</dt>
						<dd>&#151; Pertaining to <a href="#aquatic">aquatic</a> plants which have some portion of the plant extended out of the water.</dd>

						<dt id="emersed">Emersed</dt>
						<dd>&#151; Above water.</dd>

						<dt id="endemic">Endemic</dt>
						<dd>&#151; Confined to a small geographic area.</dd>

						<dt id="endosperm">Endosperm</dt>
						<dd>&#151; In a seed, the reserve food stored around, or next to, the embryo.</dd>

						<dt id="entire">Entire</dt>
						<dd>&#151; Pertaining to <a href="#margin">margins</a> without <a href="#crenate">crenation</a>, serration, or <a href="#dentate">dentition</a>; even though the <a href="#margin">margin</a> may be variously <a href="#cilliate">ciliate</a> or <a href="#pubescent">pubescent</a>. [<a href="plate04.php" title="Plate 04">Plate 4</a>]</dd>

						<dt id="ephemeral">Ephemeral</dt>
						<dd>&#151; Lasting for one day or less.</dd>

						<dt id="epidermis">Epidermis</dt>
						<dd>&#151; The superficial layer of cells.</dd>

						<dt id="epigynous">Epigynous</dt>
						<dd>&#151; Flower with the <a href="#calyx">calyx</a> situated on the <a href="#ovary">ovary</a>. [<a href="plate09.php" title="Plate 09">Plate 9</a>]</dd>

						<dt id="equitant">Equitant</dt>
						<dd>&#151; Pertaining to the two-<a href="#ranked">ranked</a> arrangement of usually <a href="#conduplicate">conduplicate</a> leaves, overlapping in two ranks.</dd>

						<dt id="erect">Erect</dt>
						<dd>&#151; Upright.</dd>

						<dt id="erose">Erose</dt>
						<dd>&#151; Pertaining to <a href="#margin">margins</a> which appear unevenly cut or <a href="#incised">incised</a>, as if eroded or eaten.</dd>

						<dt id="evanescent">Evanescent</dt>
						<dd>&#151; Fading, disappearing in time.</dd>

						<dt id="evergreen">Evergreen</dt>
						<dd>&#151; Refers to having green foliage throughout the year.</dd>

						<dt id="excurrent">Excurrent</dt>
						<dd>&#151; Usually in reference to <a href="#vein">veins</a> and <a href="#nerve">nerves</a> which run beyond the <a href="#margin">margin</a> of the organ from which it originates; often as an <a href="#awn">awn</a> or <a href="#bristle">bristle</a>.</dd>

						<dt id="exfoliating">Exfoliating</dt>
						<dd>&#151; Loosely shedding in thin or stringy layers.</dd>

						<dt id="exserted">Exserted</dt>
						<dd>&#151; Prolonged beyond the rim of an enveloping or confining structure.</dd>

						<dt id="extrorse">Extrorse</dt>
						<dd>&#151; Looking or facing outward.</dd>

						<dt id="f">F</dt>
						<dd><hr /></dd>

						<dt id="face">Face</dt>
						<dd>&#151; A flat side.</dd>

						<dt id="falcate">Falcate</dt>
						<dd>&#151; Sickle-shaped; slenderly curved and tapering to a usually sharp tip. [<a href="plate03.php" title="Plate 03">Plate 3</a>]</dd>

						<dt id="falls">Falls</dt>
						<dd>&#151; Outer <a href="#whorl">whorl</a> or series of <a href="#perianth">perianth</a> parts of an iridaceous flower, often broader than those of the inner series and, in some Iris, drooping or <a href="#flexuous">flexuous</a>.</dd>

						<dt id="farinose">Farinose</dt>
						<dd>&#151; Resembling farina; typically used to describe the white-<a href="#mealy">mealy</a>, strongly modified hairs in the <a href="#genus">genus</a> <em>Chenopodium</em>. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="fascicle">Fascicle</dt>
						<dd>&#151; A cluster or bundle. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="fasciculate">Fasciculate</dt>
						<dd>&#151; With <a href="#fascicle">fascicles</a>.</dd>

						<dt id="fastigiate">Fastigiate</dt>
						<dd>&#151; Usually in reference to branches which are stiffly <a href="#erect">erect</a>; neither <a href="#divaricate">divaricate</a> nor <a href="#divergent">divergent</a>.</dd>

						<dt id="fen">Fen</dt>
						<dd>&#151; A general term used in reference to habitats which are <a href="#calcareous">calcareous</a> in nature and which are fed throughout the year by a flow of water at or just beneath the surface.</dd>

						<dt id="ferruginous">Ferruginous</dt>
						<dd>&#151; Rust-colored.</dd>

						<dt id="fertile">Fertile</dt>
						<dd>&#151; Capable of reproducing sexually.</dd>

						<dt id="fetid">Fetid</dt>
						<dd>&#151; Having a disagreeable odor.</dd>

						<dt id="fibrillose">Fibrillose</dt>
						<dd>&#151; Beset or provided with numerous fine fibers.</dd>

						<dt id="fibrous">Fibrous</dt>
						<dd>&#151; Referring usually to a much branched root system with progressively smaller branches. [<a href="plate01.php" title="Plate 01">Plate 1</a>]</dd>

						<dt id="fiddlehead">Fiddlehead</dt>
						<dd>&#151; Referring to the unusual <a href="#circinate">circinate</a> unrolling of <a href="#frond">fronds</a>, in many ferns.</dd>

						<dt id="filament">Filament</dt>
						<dd>&#151; <a href="#anther">Anther</a>-bearing stalk of the <a href="#stamen">stamen</a>. [<a href="plate09.php" title="Plate 09">Plate 9</a>]</dd>

						<dt id="filiform">Filiform</dt>
						<dd>&#151; Very slender, thread-shaped; usually <a href="#terete">terete</a> in cross section.</dd>

						<dt id="fimbriate">Fimbriate</dt>
						<dd>&#151; Fringed.</dd>

						<dt id="fimbriolate">Fimbriolate</dt>
						<dd>&#151; With tiny fringes.</dd>

						<dt id="fistulose">Fistulose</dt>
						<dd>&#151; Hollow, often pertaining to stems with hollow centers.</dd>

						<dt id="flabelliform">Flabelliform</dt>
						<dd>&#151; Fan-like.</dd>

						<dt id="flaccid">Flaccid</dt>
						<dd>&#151; Very limber, without apparent support.</dd>

						<dt id="flange">Flange</dt>
						<dd>&#151; A bit of projecting tissue.</dd>

						<dt id="flexuous">Flexuous</dt>
						<dd>&#151; Flexible; easily bent this way and that.</dd>

						<dt id="floccose">Floccose</dt>
						<dd>&#151; Copiously beset with tangled <a href="#woolly">woolly</a> hairs. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="floret">Floret</dt>
						<dd>&#151; A single small flower, usually a member of a cluster, such as a <a href="#head">head</a>; used particularly in grasses (Poaceae family) and composites (Asteraceae family).</dd>

						<dt id="floriferous">Floriferous</dt>
						<dd>&#151; Bearing flowers.</dd>

						<dt id="fluted">Fluted</dt>
						<dd>&#151; With a <a href="#parallel">parallel</a> series of grooves.</dd>

						<dt id="foliaceous">Foliaceous</dt>
						<dd>&#151; Leafy; leaf-like.</dd>

						<dt id="foliate">Foliate</dt>
						<dd>&#151; With leaves. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="foliolate">Foliolate</dt>
						<dd>&#151; Having <a href="#leaflet">leaflets</a>; often used with a prefix, such as trifoliolate. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="follicle">Follicle</dt>
						<dd>&#151; A dry fruit consisting of a single <a href="#carpel">carpel</a> and <a href="#dehiscence">dehiscing</a> along only one <a href="#suture">suture</a>. [<a href="plate11.php" title="Plate 11">Plate 11</a>]</dd>

						<dt id="form">-form</dt>
						<dd>&#151; Suffix meaning like or resembling.</dd>

						<dt id="forma">Forma</dt>
						<dd>&#151; A <a href="#infraspecific">infraspecific</a> taxonomic entity, usually involving single-gene traits such as flower or fruit color.</dd>

						<dt id="friable">Friable</dt>
						<dd>&#151; Easily crumbled; fragile.</dd>

						<dt id="frond">Frond</dt>
						<dd>&#151; The <a href="#foliaceous">foliaceous</a> blade of a fern leaf.</dd>

						<dt id="fruit">Fruit</dt>
						<dd>&#151; That structure which bears the seeds.</dd>

						<dt id="fruticose">Fruticose</dt>
						<dd>&#151; <a href="#shrub">Shrubby</a> or <a href="#shrub">shrub</a>-like and also woody.</dd>

						<dt id="fugacious">Fugacious</dt>
						<dd>&#151; Falling away early.</dd>

						<dt id="fulvous">Fulvous</dt>
						<dd>&#151; Tawny.</dd>

						<dt id="funnelform">Funnelform</dt>
						<dd>&#151; Shaped approximately like a funnel; sometimes called infundibuliform. [<a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="furcate">Furcate</dt>
						<dd>&#151; Forked.</dd>

						<dt id="fuscous">Fuscous</dt>
						<dd>&#151; Grayish-brown.</dd>

						<dt id="fusiform">Fusiform</dt>
						<dd>&#151; Spindle-shaped; swollen in the middle and gradually narrowed toward each end.</dd>

						<dt id="g">G</dt>
						<dd><hr /></dd>

						<dt id="galeate">Galeate</dt>
						<dd>&#151; <a href="#hood">Hood</a>-like; <a href="#helmet">helmet</a>-shaped. [<a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="geniculate">Geniculate</dt>
						<dd>&#151; Knee-like; usually referring to the <a href="#alternate">alternate</a>, abrupt bends at the <a href="#node">nodes</a> of some stems; also referring to bent <a href="#awn">awns</a>.</dd>

						<dt id="genus">Genus</dt>
						<dd>&#151;  A group of related <a href="#species">species</a>, as the genus <em>Ulmus</em> (elm), the genus <em>Syringa</em> (lilac), embracing respectively all kinds of elms and all kinds of lilacs.</dd>

						<dt id="gibbous">Gibbous</dt>
						<dd>&#151; Swollen on one side; protuberant, often interrupting the radial symmetry of a structure. [<a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="glabrate">Glabrate or Glabrescent</dt>
						<dd>&#151; Becoming smooth.</dd>

						<dt id="glabrous">Glabrous</dt>
						<dd>&#151; Smooth, in the sense of not possessing hairs.</dd>

						<dt id="gland">Gland</dt>
						<dd>&#151; A general term applying to any number of small protuberances, <a href="#viscid">viscid</a> dots, or secretions.</dd>

						<dt id="glandular">Glandular</dt>
						<dd>&#151; With <a href="#gland">glands</a>. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="glaucescent">Glaucescent</dt>
						<dd>&#151; Weakly <a href="#glaucous">glaucous</a>. </dd>

						<dt id="glaucous">Glaucous</dt>
						<dd>&#151; Covered by a white or pale, often waxy, <a href="#bloom">bloom</a>.</dd>

						<dt id="globose">Globose</dt>
						<dd>&#151; Spherical; globe-like.</dd>

						<dt id="globular">Globular</dt>
						<dd>&#151; Circular.</dd>

						<dt id="glochidiate">Glochidiate</dt>
						<dd>&#151; With minute barbed <a href="#bristle">bristles</a>. [<a href="plate07.php" title="Plate 07">Plate 7</a>]</dd>

						<dt id="glomerate">Glomerate</dt>
						<dd>&#151; Tightly clustered, usually in reference to compact clusters of 
						short-stalked flowers.</dd>

						<dt id="glomerulate">Glomerulate</dt>
						<dd>&#151; Similar to <a href="#glomerate">glomerate</a>, but with smaller clusters.</dd>

						<dt id="glomerule">Glomerule</dt>
						<dd>&#151; A small, compact cluster. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="glume">Glume</dt>
						<dd>&#151; The lowest two (sometimes one) empty <a href="#scale">scales</a> <a href="#subtend">subtending</a> the usually fertile <a href="#scale">scales</a> in grass <a href="#spikelet">spikelets</a>. [<a href="plate12.php" title="Plate 12">Plate 12</a>]</dd>

						<dt id="glutinous">Glutinous</dt>
						<dd>&#151; Covered with a sticky exudation.</dd>

						<dt id="grain">Grain</dt>
						<dd>&#151; The fruit of most grasses; a <a href="#caryopsis">caryopsis</a>.</dd>

						<dt id="granular">Granular</dt>
						<dd>&#151; Appearing to consist of tiny grains.</dd>

						<dt id="granulate">Granulate</dt>
						<dd>&#151; <a href="#granular">Granular</a>.</dd>

						<dt id="granulose">Granulose</dt>
						<dd>&#151; <a href="#granular">Granular</a>.</dd>

						<dt id="gritcells">Grit Cells</dt>
						<dd>&#151; The hard, almost stony, cells, found in some fruits, especially pears.</dd>

						<dt id="gymnosperm">Gymnosperm</dt>
						<dd>&#151; Seed-bearing plant in which the <a href="#ovule">ovules</a> are borne on open <a href="#scale">scales</a>.</dd>

						<dt id="gynoecium">Gynoecium</dt>
						<dd>&#151; The <a href="#pistil">pistil</a> or collective <a href="#pistil">pistils</a> of a flower; the female portions of a flower as a whole -- the corresponding term for <a href="#stamen">stamens</a> is the <a href="#androecium">androecium</a>.</dd>

						<dt id="h">H</dt>
						<dd><hr /></dd>

						<dt id="halophilic">Halophilic</dt>
						<dd>&#151; Preferring <a href="#saline">saline</a> soils.</dd>

						<dt id="halophyte">Halophyte</dt>
						<dd>&#151; A plant that grows in <a href="#saline">saline</a> soils.</dd>

						<dt id="hastate">Hastate</dt>
						<dd>&#151; Resembling an arrowhead, particularly with respect to the lobed <a href="#basal">basal</a> portion, which is usually at about right angles to the main portion. [<a href="plate03.php" title="Plate 03">Plate 3</a>and <a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="hastiform">Hastiform</dt>
						<dd>&#151; More or less <a href="#hastate">hastate</a>.</dd>

						<dt id="haustorium">Haustorium</dt>
						<dd>&#151; In parasitic plants, a specialized outgrowth of a stem or root, 
						serving for the absorption of food, as in the dodders.</dd>

						<dt id="head">Head</dt>
						<dd>&#151; A dense, compact cluster of mostly <a href="#sessile">sessile</a> flowers. [<a href="plate08.php" title="Plate 08">Plate 8</a>] Also used to describe the inflorescence in the Asteraceae family. [<a href="plate12.php" title="Plate 12">Plate 12</a>]</dd></dd>

						<dt id="helicoid">Helicoid</dt>
						<dd>&#151; Refers to <a href="#raceme">racemes</a> or <a href="#spike">spikes</a> which are coiled from the tip downward with successive <a href="#lateral">lateral</a> branches arising on the same side. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="helmet">Helmet</dt>
						<dd>&#151; A <a href="#hood">hood</a>-shaped organ, usually a petal, best exemplified in the <a href="#genus">genus</a> <em>Aconitum</em>.</dd>

						<dt id="herb">Herb</dt>
						<dd>&#151; A non-woody, non-grass-like plant.</dd>

						<dt id="herbaceous">Herbaceous</dt>
						<dd>&#151; Not woody.</dd>

						<dt id="herbage">Herbage</dt>
						<dd>&#151; Referring to green leaves and shoots.</dd>

						<dt id="hilum">Hilum</dt>
						<dd>&#151; The scar or point of attachment of the seed.</dd>

						<dt id="hip">Hip</dt>
						<dd>&#151; The unusual fruit exemplified by the <a href="#genus">genus</a> <em>Rosa</em>.</dd>

						<dt id="hirsute">Hirsute</dt>
						<dd>&#151; Beset with stiff or stiffish, usually straight, hairs. [<a href="plate06.php" title="Plate 06">Plate 6</a> and <a href="plate07.php" title="Plate 07">Plate 7</a>]</dd>

						<dt id="hirsutulous">Hirsutulous</dt>
						<dd>&#151; Slightly <a href="#hirsute">hirsute</a>.</dd>

						<dt id="hirtellous">Hirtellous</dt>
						<dd>&#151; Minutely <a href="#hirsute">hirsute</a>. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="hispid">Hispid</dt>
						<dd>&#151; <a href="#coarse">Coarsely</a> <a href="#hirsute">hirsute</a> or <a href="#bristle">bristly</a>-hairy. [<a href="plate06.php" title="Plate 06">Plate 6</a> and <a href="plate07.php" title="Plate 07">Plate 7</a>]</dd>

						<dt id="hispidulous">Hispidulous</dt>
						<dd>&#151; Minutely <a href="#hispid">hispid</a>.</dd>

						<dt id="hoary">Hoary</dt>
						<dd>&#151; <a href="#pubescent">Pubescent</a> with close, fine, usually grayish or whitish, hairs.</dd>

						<dt id="hood">Hood</dt>
						<dd>&#151; Specifically, that part of the milkweed flower in which the <a href="#stamen">stamens</a> are greatly modified into hood-like organs; in general, an organ which is arched or <a href="#concave">concave</a>. [<a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="horn">Horn</dt>
						<dd>&#151; A <a href="#incurved">incurved</a> body often present in the <a href="#hood">hooded</a> body of milkweed flowers.</dd>

						<dt id="humifuse">Humifuse</dt>
						<dd>&#151; Spreading over the ground.</dd>

						<dt id="hummock">Hummock</dt>
						<dd>&#151; A small, low mound in an otherwise wet plant community.</dd>

						<dt id="hyaline">Hyaline</dt>
						<dd>&#151; Transparent or <a href="#translucent">translucent</a>.</dd>

						<dt id="hybrid">Hybrid</dt>
						<dd>&#151; The progeny of sexual reproduction between two different, recognized 
						<a href="#species">species</a>.</dd>

						<dt id="hydromesophytic">Hydromesophytic</dt>
						<dd>&#151; Referring to the wet <a href="#mesophytic">mesophytic</a> swamps behind the high dunes near Lake Michigan.</dd>

						<dt id="hypanthium">Hypanthium</dt>
						<dd>&#151; Floral tube formed by the <a href="#adnate">adnation</a> of the <a href="#sepal">sepals</a>,  <a href="#petal">petals</a>, and <a href="#stamen">stamens</a>; most commonly tubular and simulating a <a href="#calyx">calyx</a> tube.</dd>

						<dt id="hypogynium">Hypogynium</dt>
						<dd>&#151; The disk-like structure subtending the <a href="#ovary">ovary</a> in the genus <em>Scleria</em>.</dd>

						<dt id="hypogynous">Hypogynous</dt>
						<dd>&#151; Flower with the <a href="#calyx">calyx</a> situated below the <a href="#ovary">ovary</a>. [<a href="plate09.php" title="Plate 09">Plate 9</a>]</dd>

						<dt id="i">I</dt>
						<dd><hr /></dd>

						<dt id="imbricate">Imbricate</dt>
						<dd>&#151; A general term which applies under various conditions where one organ, or series of organs, overlaps another organ or series of organs; as in roof shingles. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="immaculate">Immaculate</dt>
						<dd>&#151; Without spots.  Compare with <a href="#maculate">maculate</a>.</dd>

						<dt id="immersed">Immersed</dt>
						<dd>&#151; Growing beneath the surface of the water.</dd>

						<dt id="imperfect">Imperfect</dt>
						<dd>&#151; Pertaining to a flower in which there is but one set of sex organs; i.e., those flowers which are either strictly male or strictly female; imperfect flowers occur in both <a href="#monoecious">monoecious</a> and <a href="#dioecious">dioecious</a> plants.</dd>

						<dt id="impressed">Impressed</dt>
						<dd>&#151; Sunken in; situated <a href="#inferior">inferior</a> to the surface of a blade, usually in reference to <a href="#vein">veins</a> which are neither flush with nor raised above the surface of the blade or organ.</dd>

						<dt id="incised">Incised</dt>
						<dd>&#151; Deeply cut or divided, usually <a href="#irregular">irregularly</a>. [<a href="plate04.php" title="Plate 04">Plate 4</a>]</dd>

						<dt id="included">Included</dt>
						<dd>&#151; Contained within, usually in reference to <a href="#stamen">stamens</a>, <a href="#pistil">pistils</a>, or <a href="#capsule">capsules</a> which do not surpass or exceed the <a href="#calyx">calyx</a> or <a href="#corolla">corolla</a> in length.</dd>

						<dt id="incurved">Incurved</dt>
						<dd>&#151; Curled or directed inward, such as hairs, the tips of which curve back toward the stem or surface of an organ.</dd>

						<dt id="indehiscent">Indehiscent</dt>
						<dd>&#151; Not opening at maturity; a term generally referring to some fruits.</dd>

						<dt id="indeterminate">Indeterminate</dt>
						<dd>&#151; <a href="#inflorescence">Inflorescence</a> whose terminal flowers open last. See <a href="#determinate">determinate</a>.</dd>

						<dt id="indument">Indument</dt>
						<dd>&#151; Hairy or <a href="#pubescent">pubescent</a>, usually rather heavy, covering.</dd>

						<dt id="indurated">Indurated</dt>
						<dd>&#151; Hardened.</dd>

						<dt id="indusium">Indusium</dt>
						<dd>&#151; A delicate flap or covering connected to the <a href="#sorus">sorus</a> in ferns.</dd>

						<dt id="inferior">Inferior</dt>
						<dd>&#151; In reference to an organ which appears subordinate to or lower than another similar organ; in reference to an ovary, at least the sides of which are <a href="#adnate">adnate</a> to the <a href="#hypanthium">hypanthium</a>. [<a href="plate09.php" title="Plate 09">Plate 9</a>]</dd>

						<dt id="infertile">Infertile</dt>
						<dd>&#151; <a href="#sterile">Sterile</a>; unable to produce seeds.</dd>

						<dt id="inflated">Inflated</dt>
						<dd>&#151; Blown up or <a href="#dilated">dilated</a> as if by air; <a href="#bladder">bladder</a>-like.</dd>

						<dt id="inflexed">Inflexed</dt>
						<dd>&#151; Bent inward.</dd>

						<dt id="inflorescence">Inflorescence</dt>
						<dd>&#151; The discrete flowering portion or portions of a plant; a flower cluster. [<a href="plate08.php" title="Plate 08">Plate 8</a> and <a href="plate12.php" title="Plate 12">Plate 12</a>]</dd>

						<dt id="infra-">Infra-</dt>
						<dd>&#151; Prefix meaning beneath, or less than, or within. Opposite of <a href="#supra-">supra-</a>.</dd>

						<dt id="infraspecific">Infraspecific</dt>
						<dd>&#151; Pertaining to any <a href="#taxon">taxon</a> within a <a href="#species">species</a>, such as a subspecies, variety, or form. Compare to <a href="#interspecific">interspecific</a> and <a href="#intraspecific">intraspecific</a>.</dd>

						<dt id="infructescence">Infructescence</dt>
						<dd>&#151; The fruiting <a href="#inflorescence">inflorescence</a>.</dd>

						<dt id="inrolled">Inrolled</dt>
						<dd>&#151; Said of leaf <a href="#margin">margins</a> rolled inward toward the <a href="#midnerve">midrib</a>.</dd>

						<dt id="insipid">Insipid</dt>
						<dd>&#151; Without taste or flavor.</dd>

						<dt id="inter-">Inter-</dt>
						<dd>&#151; Prefix meaning between, or among.</dd>

						<dt id="internode">Internode</dt>
						<dd>&#151; That portion of the stem other than the <a href="#node">node</a>; the distance between two <a href="#node">nodes</a>. [<a href="plate07.php" title="Plate 07">Plate 7</a>]</dd>

						<dt id="interspecific">Interspecific</dt>
						<dd>&#151; Among species or between two species. Compare to <a href="#infraspecific">infraspecific</a> and <a href="#intraspecific">intraspecific</a>.</dd>

						<dt id="interstitial">Interstitial</dt>
						<dd>&#151; That space which is between or among two or more discriminate structures; in the Rosaceae family, referring to the small <a href="#leaflet">leaflets</a> between two large <a href="#leaflet">leaflets</a> on the <a href="#rachis">rachis</a>.</dd>

						<dt id="intra-">Intra-</dt>
						<dd>&#151; Prefix meaning within.</dd>

						<dt id="intraspecific">Intraspecific</dt>
						<dd>&#151; Referring to a taxonomic entity with a <a href="#species">species</a>.  Compare to <a href="#infraspecific">infraspecific</a> and <a href="#interspecific">interspecific</a>.</dd>

						<dt id="intrastaminal">Intrastaminal</dt>
						<dd>&#151; Among the <a href="#stamen">stamens</a>.</dd>

						<dt id="introrse">Introrse</dt>
						<dd>&#151; Turned inward or toward the <a href="#axis">axis</a>.</dd>

						<dt id="invaginated">Invaginated</dt>
						<dd>&#151; Sunken inwardly; used in connection with the <a href="#achene">achene</a> in <em>Carex</em>.</dd>

						<dt id="involucel">Involucel</dt>
						<dd>&#151; A <a href="#secondary">secondary</a> <a href="#involucre">involucre</a>, such as that <a href="#subtend">subtending</a> an <a href="#umbellet">umbellet</a> in the Apiaceae family. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="involucral">Involucral</dt>
						<dd>&#151; Pertaining to an <a href="#involucre">involucre</a>. </dd>

						<dt id="involucrate">Involucrate</dt>
						<dd>&#151; Having an <a href="#involucre">involucre</a>.</dd>

						<dt id="involucre">Involucre</dt>
						<dd>&#151; A <a href="#whorl">whorl</a> or <a href="#imbricate">imbricated</a> series of <a href="#bract">bracts</a>, often appearing somewhat <a href="#calyx">calyx</a>-like, typically <a href="#subtend">subtending</a> a flower cluster or a solitary flower. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="involute">Involute</dt>
						<dd>&#151; Leaf <a href="#margin">margins</a> rolled toward the upper surface of the <a href="#midnerve">midrib</a>. [<a href="plate04.php" title="Plate 04">Plate 4</a>]</dd>

						<dt id="irregular">Irregular</dt>
						<dd>&#151; Referring to a <a href="#calyx">calyx</a> or <a href="#corolla">corolla</a> which is <a href="#bilateralsymmetrical">bilaterally symmetrical</a>, capable of being divided into two equal halves along only one plane. Same as <a href="#zygomorphic">zygomorphic</a>.</dd>

						<dt id="isodiametric">Isodiametric</dt>
						<dd>&#151; Shapes with sides or diameters of nearly equal lengths.</dd>

						<dt id="j">J</dt>
						<dd><hr /></dd>

						<dt id="jointed">Jointed</dt>
						<dd>&#151; With <a href="#node">nodes</a>, or points of real or apparent <a href="#articulation">articulation</a>.</dd>

						<dt id="k">K</dt>
						<dd><hr /></dd>

						<dt id="keel">Keel</dt>
						<dd>&#151; A longitudinal fold or ridge; in the Fabaceae family, the two <a href="#anterior">anterior</a> <a href="#united">united</a> <a href="#petal">petals</a> of a <a href="#papilionaceous">papilionaceous</a> flower -- a flower shaped like a sweet pea blossom.</dd>

						<dt id="l">L</dt>
						<dd><hr /></dd>

						<dt id="lacerate">Lacerate</dt>
						<dd>&#151; Unevenly cut or <a href="#incised">incised</a>. [<a href="plate04.php" title="Plate 04">Plate 4</a>]</dd>

						<dt id="laciniate">Laciniate</dt>
						<dd>&#151; Deeply and sharply slashed into slender <a href="#segment">segments</a>. [<a href="plate04.php" title="Plate 04">Plate 4</a>]</dd>

						<dt id="lacuna">Lacuna</dt>
						<dd>&#151; Defined space.</dd>

						<dt id="lamellae">Lamellae</dt>
						<dd>&#151; Thin flat plates or <a href="#lateral">laterally</a> flattened ridges.</dd>

						<dt id="lamina">Lamina</dt>
						<dd>&#151; Blade, usually of a leaf. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="lanate">Lanate</dt>
						<dd>&#151; Densely white <a href="#woolly">woolly</a>-<a href="#pubescent">pubescent</a>. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="lanceolate">Lanceolate</dt>
						<dd>&#151; Lance-shaped, broadest below the middle, long-tapering above the middle, several times longer than wide.  See <a href="#oblanceolate">oblanceolate</a>[<a href="plate03.php" title="Plate 03">Plate 3</a>]</dd>

						<dt id="lanuginose">Lanuginose</dt>
						<dd>&#151; <a href="#woolly">Woolly</a> or <a href="#cottony">cottony</a>; <a href="#downy">downy</a>, the hairs somewhat shorter than in <a href="#lanate">lanate</a>.</dd>

						<dt id="lanulose">Lanulose</dt>
						<dd>&#151; Very short-<a href="#woolly">woolly</a>.</dd>

						<dt id="lateral">Lateral</dt>
						<dd>&#151; Pertaining to the sides.</dd>

						<dt id="latex">Latex</dt>
						<dd>&#151; The <a href="#milky">milky</a> juice (or highly colored juice) of some plants.</dd>

						<dt id="lax">Lax</dt>
						<dd>&#151; General term meaning open, loose, without clear form or shape, or scattered, depending on the context.</dd>

						<dt id="leaf">Leaf</dt>
						<dd>&#151; Usually a <a href="#blade">blade</a>-like organ attached to the stem, often by a <a href="#petiole">petiole</a> or <a href="#sheath">sheath</a>, and commonly functioning as a principal organ in photosynthesis and transpiration. Leaves characteristically <a href="#subtend">subtend</a> buds and extend from the stem in various planes. See also <a href="#leaflet">leaflet</a>. A leaf <a href="#axil">axil</a> is the upper angle between a leaf <a href="#petiole">petiole</a>, or <a href="#sessile">sessile</a> leaf base, and the <a href="#node">node</a> from which it grows. A leaf scar is formed on a twig following the fall of a leaf, usually revealing the pattern of <a href="#vascular">vascular</a> bundles in the leaf trace.</dd>

						<dt id="leaflet">Leaflet</dt>
						<dd>&#151; One of the discriminate <a href="#segment">segments</a> of the <a href="#compound">compound</a> leaf of a dicotyledonous plant. Leaflets may resemble leaves, but differ principally in that buds are not found in the <a href="#axil">axils</a> of leaflets, and that leaflets all lie in the same plane. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="legume">Legume</dt>
						<dd>&#151; The fruit in the Fabaceae family, produced from a one-celled <a href="#ovary">ovary</a>, and typically splitting along both <a href="#suture">sutures</a>; as in the pea <a href="#pod">pod</a>. [<a href="plate11.php" title="Plate 11">Plate 11</a>]</dd>

						<dt id="lemma">Lemma</dt>
						<dd>&#151; The lowermost of the two <a href="#scale">scales</a> forming the <a href="#floret">floret</a> in a grass <a href="#spikelet">spikelet</a> -- the uppermost, less easily seen, is called the <a href="#palea">palea</a>. [<a href="plate12.php" title="Plate 12">Plate 12</a>]</dd>

						<dt id="lenticel">Lenticel</dt>
						<dd>&#151; A corky spot on young bark, corresponding functionally to a <a href="#stoma">stoma</a> on a leaf. [<a href="plate07.php" title="Plate 07">Plate 7</a>]</dd>

						<dt id="lenticular">Lenticular</dt>
						<dd>&#151; Lens-shaped; two-sided, with the <a href="#face">faces</a> <a href="#convex">convex</a>.</dd>

						<dt id="lepidote">Lepidote</dt>
						<dd>&#151; Surfaced with small <a href="#scurfy">scurfy</a> <a href="#scale">scales</a>. [<a href="plate06.php" title="Plate 06">Plate 6</a>]

						<dt id="ligneous">Ligneous</dt>
						<dd>&#151; Woody.</dd>

						<dt id="ligulate">Ligulate</dt>
						<dd>&#151; Bearing a <a href="#ligule">ligule</a>. [<a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="ligule">Ligule</dt>
						<dd>&#151; In the Asteraceae family, pertaining to the <a href="#dilated">dilated</a> or flattened, spreading <a href="#limb">limb</a> of the composite <a href="#ray">ray flower</a>; in other families, such as Poaceae family, an extension, often <a href="#scarious">scarious</a>, of the summit of the leaf <a href="#sheath">sheath</a>. [<a href="plate12.php" title="Plate 12">Plate 12</a>]</dd>

						<dt id="limb">Limb</dt>
						<dd>&#151; The expanded portion of a <a href="#corolla">corolla</a> above the throat; the expanded portion of any petal.</dd>

						<dt id="linear">Linear</dt>
						<dd>&#151; Very long and narrow, with nearly or quite <a href="#parallel">parallel</a> <a href="#margin">margins</a>. [Plate 3]</dd>

						<dt id="lip">Lip</dt>
						<dd>&#151; Referring to either the upper or lower lip of a <a href="#bilabiate">bilabiate</a> <a href="#corolla">corolla</a>; the principal, seemingly lower, petal in the Orchidaceae.</dd>

						<dt id="lobe">Lobe</dt>
						<dd>&#151; Any <a href="#segment">segment</a> or division, particularly if <a href="#blunt">blunt</a>. [<a href="plate04.php" title="Plate 04">Plate 4</a>]</dd>
						 
						<dt id="lobulate">Lobulate</dt>
						<dd>&#151; Bearing <a href="#lobe">lobes</a>.</dd>

						<dt id="locular">Locular</dt>
						<dd>&#151; Having <a href="#locule">locules</a>.</dd>

						<dt id="locule">Locule</dt>
						<dd>&#151;  A discriminate cavity or space within an <a href="#ovary">ovary</a>, fruit, or <a href="#anther">anther</a>.</dd>

						<dt id="loculicidal">Loculicidal</dt>
						<dd>&#151; Pertaining to a <a href="#capsule">capsule</a> which <a href="#dehiscence">dehisces</a> along the <a href="#dorsal">dorsal</a> <a href="#suture">suture</a> of each <a href="#locule">locule</a>, thus opening directly into the cavity. [<a href="plate11.php" title="Plate 11">Plate 11</a>]</dd>

						<dt id="locus">Locus</dt>
						<dd>&#151; Place.</dd>

						<dt id="loment">Loment</dt>
						<dd>&#151; Specifically applied to the series of one-seeded <a href="#article">articles</a> of a fruit in the <a href="#genus">genus</a> <em>Desmodium</em>. [<a href="plate11.php" title="Plate 11">Plate 11</a>]</dd>

						<dt id="longiligulate">Longiligulate</dt>
						<dd>&#151; With long <a href="#ligule">ligules</a>.</dd>

						<dt id="lustrous">Lustrous</dt>
						<dd>&#151; Shiny.</dd>

						<dt id="lyrate">Lyrate</dt>
						<dd>&#151; <a href="#pinnate">Pinnately</a> <a href="#lobe">lobed</a> into large, broad <a href="#lobe">lobes</a>, the <a href="#terminal">terminal</a> one typically noticeably larger than the reduced <a href="#lateral">lateral</a> ones. [<a href="plate03.php" title="Plate 03">Plate 3</a>]</dd>

						<dt id="m">M</dt>
						<dd><hr /></dd>

						<dt id="maculate">Maculate</dt>
						<dd>&#151; Spotted.  Compare with <a href="#immaculate">immaculate</a>.</dd>

						<dt id="malodorous">Malodorous</dt>
						<dd>&#151; Foul-smelling.</dd>

						<dt id="malpighian">Malpighian</dt>
						<dd>&#151; Spoken of hairs which are straight and attached by the middle, and typically <a href="#appressed">appressed</a> to the leaf surface.</dd>

						<dt id="marcescent">Marcescent</dt>
						<dd>&#151; Withering but <a href="#persistent">persistent</a>, usually remaining green.</dd>

						<dt id="margin">Margin</dt>
						<dd>&#151; Edge. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="marine">Marine</dt>
						<dd>&#151; Referring to an <a href="#aquatic">aquatic</a> habitat in salt water.</dd>

						<dt id="marly">Marly</dt>
						<dd>&#151; Very limy, often with calcium carbonate concretions at or near the surface.</dd>

						<dt id="mealy">Mealy</dt>
						<dd>&#151; See <a href="#farinose">farinose</a>.</dd>

						<dt id="megaspore">Megaspore</dt>
						<dd>&#151; The larger type of haploid <a href="#spore">spore</a> (when two sizes are present) which gives rise to the female gametophyte; the other called a <a href="#microspore">microspore</a>.</dd>

						<dt id="membranaceous">Membranaceous</dt>
						<dd>&#151; Membrane-like; very thin, flimsy, and often more or less <a href="#translucent">translucent</a>.</dd>

						<dt id="mericarp">Mericarp</dt>
						<dd>&#151; The discriminate units of a <a href="#schizocarp">schizocarp</a> which <a href="#ultimate">ultimately</a> splits apart into two individual <a href="#nutlet">nutlets</a>, usually referring to units of the fruits of the parsley family.</dd>

						<dt id="merous">-merous</dt>
						<dd>&#151; A suffix pertaining to the discriminate portions into which a floral organ or series of organs can be divided; for example, a flower with 5 <a href="#sepal">sepals</a>, 5 <a href="#petal">petals</a>, and 10 <a href="#stamen">stamens</a> can be said to be 5-merous.</dd>

						<dt id="mesic">Mesic</dt>
						<dd>&#151; A microclimatic term which refers to an area in which the soils are usually well drained, but contain a lot of moisture for all or much of the year; such areas typically occur on north or east-facing exposures. Compare to <a href="#xeric">xeric</a>.</dd>

						<dt id="mesophytic">Mesophytic</dt>
						<dd>&#151; Refers to plant <a href="#species">species</a> or plant communities which grow under <a href="#mesic">mesic</a> conditions.</dd>

						<dt id="microspore">Microspore</dt>
						<dd>&#151; Haploid <a href="#spore">spore</a> which gives rise to the male gametophyte; other being called <a href="#megaspore">megaspore</a>.</dd>

						<dt id="midnerve">Midnerve, Midrib, Midvein</dt>
						<dd>&#151; The central or principal <a href="#vein">vein</a> of a foliar or <a href="#bracteal">bracteal</a> organ, or of a <a href="#sepal">sepal</a> or <a href="#petal">petal</a>. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="milky">Milky</dt>
						<dd>&#151; Like a thick white juice.</dd>

						<dt id="minerotrophic">Minerotrophic</dt>
						<dd>&#151; Rich in calcium and magnesium carbonate.</dd>

						<dt id="monadelphous">Monadelphous</dt>
						<dd>&#151; Spoken of <a href="#stamen">stamens</a> united by their <a href="#filament">filaments</a> into a tube or <a href="#column">column</a>.</dd>

						<dt id="moniliform">Moniliform</dt>
						<dd>&#151; Appearing as a string of beads.</dd>

						<dt id="monocot">Monocot</dt>
						<dd>&#151; <a href="#angiosperm">Angiospermous</a> plant having only one <a href="#cotyledon">cotyledon</a>.</dd>

						<dt id="monoecious">Monoecious</dt>
						<dd>&#151; Pertaining to plants, individuals of which bear both <a href="#staminate">staminate</a> and <a href="#pistillate">pistillate</a> flowers but not perfect flowers.</dd>

						<dt id="moniliform">Moniliform</dt>
						<dd>&#151; Resembling a string of beads; cylindrical, with contractions at regular intervals.</dd>

						<dt id="mottled">Mottled</dt>
						<dd>&#151; Covered in part with spots, areas, or lines of different color than the main surface.</dd>

						<dt id="mucro">Mucro</dt>
						<dd>&#151; A short and small abrupt tip.</dd>

						<dt id="mucronate">Mucronate</dt>
						<dd>&#151; With a short, abrupt tip. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="mucronulate">Mucronulate</dt>
						<dd>&#151; Minutely <a href="#mucronate">mucronate</a>.</dd>

						<dt id="multifid">Multifid</dt>
						<dd>&#151; <a href="#cleft">Cleft</a> into many <a href="#lobe">lobes</a> or <a href="#segment">segments</a>.</dd>

						<dt id="muricate">Muricate</dt>
						<dd>&#151; Copiously beset with hard, often sharp, tubercles. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="n">N</dt>
						<dd><hr /></dd>

						<dt id="native">Native</dt>
						<dd>&#151; Inherent and original to an area.</dd>

						<dt id="nectar">Nectar</dt>
						<dd>&#151; A sweet substance typically produced by flowers which are insect-pollinated.</dd>

						<dt id="nerve">Nerve</dt>
						<dd>&#151; Same as a <a href="#vein">vein</a>.</dd>

						<dt id="neutral">Neutral</dt>
						<dd>&#151; Spoken of a flower which has neither <a href="#stamen">stamens</a> or <a href="#pistil">pistils</a>.</dd>

						<dt id="nigrescent">Nigrescent</dt>
						<dd>&#151; Becoming black or blackish.</dd>

						<dt id="nodding">Nodding</dt>
						<dd>&#151; Hanging on a bent <a href="#peduncle">peduncle</a> or <a href="#pedicel">pedicels</a>.</dd>

						<dt id="node">Node</dt>
						<dd>&#151; The point along a stem which gives rise to leaves, branches, or 
						<a href="#inflorescence">inflorescences</a>.</dd>

						<dt id="nodose">Nodose</dt>
						<dd>&#151; Knotty or knobby.</dd>

						<dt id="nodulose">Nodulose</dt>
						<dd>&#151; Provided with little knots or knobs.</dd>

						<dt id="nut">Nut</dt>
						<dd>&#151; A hard, <a href="#indehiscent">indehiscent</a>, one-seeded, fruit, typically with an outer shell.</dd>

						<dt id="nutlet">Nutlet</dt>
						<dd>&#151; A small <a href="#nut">nut</a> or <a href="#achene">achene</a>, typically 1-seeded, usually lacking a specific outer shell.</dd>

						<dt id="o">O</dt>
						<dd><hr /></dd>

						<dt id="obconic">Obconic</dt>
						<dd>&#151; Inversely <a href="#conical">conical</a>.</dd>

						<dt id="obcordate">Obcordate</dt>
						<dd>&#151; Referring to leaves or petals which are heart-shaped at the tip and tapering to a wedge-shaped base. [<a href="plate03.php" title="Plate 03">Plate 3</a>]</dd>

						<dt id="oblanceolate">Oblanceolate</dt>
						<dd>&#151; Several times longer than wide, but widest above the middle, long-tapering at the base.</dd>

						<dt id="oblique">Oblique</dt>
						<dd>&#151; Slanting, or unequal-sided. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="oblong">Oblong</dt>
						<dd>&#151; Several times longer than wide with nearly or quite <a href="#parallel">parallel</a> sides. [<a href="plate03.php" title="Plate 03">Plate 3</a>]</dd>

						<dt id="obovate">Obovate</dt>
						<dd>&#151; Inversely <a href="#ovate">ovate</a>. [<a href="plate03.php" title="Plate 03">Plate 3</a>]</dd>

						<dt id="obovoid">Obovoid</dt>
						<dd>&#151; Having the form of an egg, but with the broad end at the tip.</dd>

						<dt id="obsolete">Obsolete</dt>
						<dd>&#151; <a href="#rudimentary">Rudimentary</a>; not evident.</dd>

						<dt id="obtuse">Obtuse</dt>
						<dd>&#151; <a href="#blunt">Blunt</a> or rounded. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="ochroleucous">Ochroleucous</dt>
						<dd>&#151; Yellowish white.</dd>

						<dt id="ocrea">Ocrea</dt>
						<dd>&#151; In the Polygonaceae, refers to the tubular <a href="#sheath">sheathing</a> <a href="#stipule">stipules</a> along the stem.</dd>

						<dt id="ocreola">Ocreola</dt>
						<dd>&#151; In the Polygonaceae, a <a href="#secondary">secondary</a> <a href="#ocrea">ocrea</a>, usually referring to those of the <a href="#inflorescence">inflorescence</a>.</dd>

						<dt id="olivaceous">Olivaceous</dt>
						<dd>&#151; Having an olive-green color.</dd>

						<dt id="opaque">Opaque</dt>
						<dd>&#151; Dull; neither shining nor <a href="#translucent">translucent</a>.</dd>

						<dt id="opposite">Opposite</dt>
						<dd>&#151; Arranged in pairs along an <a href="#axis">axis</a>, not <a href="#alternate">alternate</a>. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="orbicular">Orbicular</dt>
						<dd>&#151; Circular in outline. [<a href="plate03.php" title="Plate 03">Plate 3</a>]</dd>

						<dt id="osier">Osier</dt>
						<dd>&#151; A long, lithe stem.</dd>

						<dt id="oval">Oval</dt>
						<dd>&#151; Broadly <a href="#elliptic">elliptical</a>. [<a href="plate03.php" title="Plate 03">Plate 3</a>]</dd>

						<dt id="ovary">Ovary</dt>
						<dd>&#151; That portion of the <a href="#pistil">pistil</a> which contains the <a href="#ovule">ovules</a>. [<a href="plate09.php" title="Plate 09">Plate 9</a>]</dd>

						<dt id="ovate">Ovate</dt>
						<dd>&#151; Egg-shaped. [<a href="plate03.php" title="Plate 03">Plate 3</a>]</dd>

						<dt id="ovoid">Ovoid</dt>
						<dd>&#151; A solid with an <a href="#ovate">ovate</a> outline.</dd>

						<dt id="ovule">Ovule</dt>
						<dd>&#151; The body which, after fertilization, becomes the seed.</dd>

						<dt id="p">P</dt>
						<dd><hr /></dd>

						<dt id="palate">Palate</dt>
						<dd>&#151; A rounded projection of the lower <a href="#lip">lip</a> of some <a href="#irregular">irregular</a> <a href="#corolla">corollas</a>, often closing the throat, as in <em>Utricularia</em>. [<a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="palea">Palea</dt>
						<dd>&#151; The uppermost of the two <a href="#scale">scales</a> forming the <a href="#floret">floret</a> in a grass <a href="#spikelet">spikelet</a> (often obscure). [<a href="plate12.php" title="Plate 12">Plate 12</a>]</dd>

						<dt id="palmate">Palmate</dt>
						<dd>&#151; <a href="#radiate">Radiately</a> <a href="#lobe">lobed</a> or divided, the axes of the individual <a href="#segment">segments</a> originating at a common point or nearly so. [<a href="plate02.php" title="Plate 02">Plate 2</a> and <a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="paludal">Paludal</dt>
						<dd>&#151; Pertaining to marshes.</dd>

						<dt id="pandurate">Pandurate</dt>
						<dd>&#151; Fiddle-shaped.</dd>

						<dt id="panicle">Panicle</dt>
						<dd>&#151; An <a href="#inflorescence">inflorescence</a> composed of two or more <a href="#raceme">racemes</a> or <a href="#racemiform">racemiform</a> <a href="#corymb">corymbs</a>. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="paniculate">Paniculate</dt>
						<dd>&#151; Bearing <a href="#panicle">panicles</a></dd>

						<dt id="paniculiform">Paniculiform</dt>
						<dd>&#151; <a href="#panicle">Panicle</a> shaped.</dd>

						<dt id="pannate">Pannate, Pannose</dt>
						<dd>&#151; With a tight, densely tangled <a href="#tomentum">tomentum</a>; Appearing felt-like. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="panne">Panne</dt>
						<dd>&#151; Typically, a moist interdunal depression, often scoured down to the water table, in <a href="#calcareous">calcareous</a> sands on the lee sides of dunes near Lake Michigan -- the vegetation quite <a href="#fen">fen</a>-like in composition.</dd>

						<dt id="pannose">Pannose</dt>
						<dd>&#151; See <a href="#pannate">Pannte</a></dd>

						<dt id="papilionaceous">Papilionaceous</dt>
						<dd>&#151; Butterfly-like; in the Fabaceae family particularly, having a <a href="#corolla">corolla</a> composed of a <a href="#standard">standard</a>, <a href="#keel">keel</a>, and two <a href="#wing">wing</a> petals. [Plate 10]</dd>

						<dt id="papilla">Papilla</dt>
						<dd>&#151; A minute, nipple-shaped projection.</dd>

						<dt id="papillate">Papillate, Papillose</dt>
						<dd>&#151; Bearing <a href="#papilla">papillae</a>; <a href="#warty">warty</a> or <a href="#tuberculate">tuberculate</a>. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="pappus">Pappus</dt>
						<dd>&#151; A modification of the <a href="#calyx">calyx</a>, usually in the Asteraceae family, such that the <a href="#segment">segments</a> are manifest as a low <a href="#crown">crown</a>, a ring of <a href="#scale">scales</a>, or fine hairs. [<a href="plate12.php" title="Plate 12">Plate 12</a>]</dd>

						<dt id="papule">Papule</dt>
						<dd>&#151; A single wart or <a href="#tubercle">tubercle</a>.</dd>

						<dt id="parallel">Parallel</dt>
						<dd>&#151; Running side-by-side, from base to tip. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="parallel-veined">Parallel-veined</dt>
						<dd>&#151; A feature occurring largely in the <a href="#monocot">Monocots</a>, where, instead of a network, the observable <a href="#vein">veins</a> are parallel to each other and the <a href="#midnerve">midrib</a>, or nearly so.</dd>

						<dt id="parasite">Parasite</dt>
						<dd>&#151; A plant which grows on and derives nourishment from another living plant.</dd>

						<dt id="parenchymatous">Parenchymatous</dt>
						<dd>&#151; Composed of thin-walled cells.</dd>

						<dt id="patina">Patina</dt>
						<dd>&#151; A fine crust or film.</dd>

						<dt id="peat">Peat</dt>
						<dd>&#151; Soil or substrate heavily invested with or even totally composed of 
						partially decayed organic matter.</dd>

						<dt id="pectinate">Pectinate</dt>
						<dd>&#151; Fringed or <a href="#dissected">dissected</a> in comb-like fashion.</dd>

						<dt id="pedicel">Pedicel</dt>
						<dd>&#151; The stalk of a single flower in a cluster. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="pedicellate">Pedicellate</dt>
						<dd>&#151; Having a <a href="#pedicel">pedicel</a>.</dd>

						<dt id="peduncle">Peduncle</dt>
						<dd>&#151; Characteristically referring to the second <a href="#internode">internode</a> below a flower, but generally applied to any <a href="#primary">primary</a> stalk which supports a <a href="#head">head</a>, flower cluster, or occasionally a single flower. [<a href="plate08.php" title="Plate 08">Plate 8</a> and <a href="plate09.php" title="Plate 09">Plate 9</a>]</dd>

						<dt id="pedunculate">Pedunculate</dt>
						<dd>&#151; Having a <a href="#peduncle">peduncle</a>.</dd>

						<dt id="pellucid">Pellucid</dt>
						<dd>&#151; Clear; transparent.</dd>

						<dt id="peltate">Peltate</dt>
						<dd>&#151; Leaf/<a href="#petiole">petiole</a> relationship in which the <a href="#petiole">petiole</a> attaches to the blade away from the blade <a href="#margin">margin</a>. Also similar relationships between <a href="#stigma">stigmas</a> and <a href="#style">styles</a>, <a href="#indusium">indusium</a> attachments to the <a href="#frond">frond</a> surface, etc. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="pendulous">Pendulous</dt>
						<dd>&#151; Drooping.</dd>

						<dt id="pepo">Pepo</dt>
						<dd>&#151; The specialized fruit in the gourd family -- essentially a large berry but possessing a thick rind.</dd>

						<dt id="perennial">Perennial</dt>
						<dd>&#151; Pertaining to a plant which lives for more than two years.</dd>

						<dt id="perfect">Perfect</dt>
						<dd>&#151; Pertaining to flowers which contain both <a href="#stamen">stamens</a> and <a href="#pistil">pistils</a>.</dd>

						<dt id="perfoliate">Perfoliate</dt>
						<dd>&#151; A condition in which the stem appears to pass through the leaf. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="perianth">Perianth</dt>
						<dd>&#151; Pertaining to the floral series of <a href="#sepal">sepals</a>, <a href="#petal">petals</a>, or both, spoken of collectively. [<a href="plate09.php" title="Plate 09">Plate 9</a>]</dd>

						<dt id="pericarp">Pericarp</dt>
						<dd>&#151; The wall of the matured <a href="#ovary">ovary</a>.</dd>

						<dt id="perigynium">Perigynium</dt>
						<dd>&#151; Referring specifically to the often <a href="#inflated">inflated</a> <a href="#sac">sac</a> which encloses the <a href="#achene">achene</a> in the <a href="#genus">genus</a> <em>Carex</em>. [<a href="plate12.php" title="Plate 12">Plate 12</a>]</dd>

						<dt id="perigynous">Perigynous</dt>
						<dd>&#151; With the <a href="#perianth">perianth</a> surrounding the <a href="#ovary">ovary</a>.</dd>

						<dt id="persistent">Persistent</dt>
						<dd>&#151; Remaining attached, especially after withering; not <a href="#caducous">caducous</a>.</dd>

						<dt id="petal">Petal</dt>
						<dd>&#151; A segment of the <a href="#corolla">corolla</a>. [<a href="plate09.php" title="Plate 09">Plate 9</a> and <a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="petaloid">Petaloid</dt>
						<dd>&#151; Colored like, or resembling, a petal.</dd>

						<dt id="petiolar">Petiolar, Petiolate</dt>
						<dd>&#151; Having a leafstalk.</dd>

						<dt id="petiole">Petiole</dt>
						<dd>&#151; A leafstalk. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="petiolulate">Petiolulate</dt>
						<dd>&#151; Having a <a href="#leaflet">leaflet</a> stalk.</dd>

						<dt id="petiolule">Petiolule</dt>
						<dd>&#151; The stalk of a <a href="#leaflet">leaflet</a>. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="phloem">Phloem</dt>
						<dd>&#151; The conducting tissue of the vascular system that transports sugars and other compounds, primarily from the leaves,  throughout the plant. Compare to <a href="#xylem">xylem</a>.</dd>

						<dt id="phyllary">Phyllary</dt>
						<dd>&#151; An <a href="#involucral">involucral</a> <a href="#bract">bract</a> in the Asteraceae family. [<a href="plate12.php" title="Plate 12">Plate 12</a>]</dd>

						<dt id="phyllodium">Phyllodium</dt>
						<dd>&#151; A somewhat <a href="#dilated">dilated</a> leafstalk having the form of and serving as a leaf blade.</dd>

						<dt id="pilose">Pilose</dt>
						<dd>&#151; <a href="#pubescent">Pubescent</a> with soft hairs. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="pinna">Pinna</dt>
						<dd>&#151; One of the principal divisions in a <a href="#pinnate">pinnate</a> or <a href="#pinnate">pinnately</a> <a href="#compound">compound</a> leaf or <a href="#frond">frond</a>.</dd>

						<dt id="pinnate">Pinnate</dt>
						<dd>&#151; Referring to a foliar structure which is <a href="#compound">compound</a> or deeply divided, the principal divisions arranged along each side of a common <a href="#axis">axis</a>. [<a href="plate02.php" title="Plate 02">Plate 2</a> and <a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="pinnatifid">Pinnatifid</dt>
						<dd>&#151; Incompletely <a href="#pinnate">pinnate</a>, the <a href="#cleft">clefts</a> between <a href="#segment">segments</a> not reaching the <a href="#axis">axis</a>. [<a href="plate04.php" title="Plate 04">Plate 4</a>]</dd>

						<dt id="pinnatisect">Pinnatisect</dt>
						<dd>&#151; <a href="#pinnate">Pinnately</a> <a href="#dissected">dissected</a>.</dd>

						<dt id="pinnule">Pinnule</dt>
						<dd>&#151; One of the principal divisions of a <a href="#pinna">pinna</a>.</dd>

						<dt id="pistil">Pistil</dt>
						<dd>&#151; That organ comprised of <a href="#ovary">ovary</a>, <a href="#style">style</a> (when present), and <a href="#stigma">stigma</a>. [<a href="plate09.php" title="Plate 09">Plate 9</a>]</dd>

						<dt id="pistillate">Pistillate</dt>
						<dd>&#151; Referring either to plants, <a href="#inflorescence">inflorescences</a>, or flowers which bear <a href="#pistil">pistils</a> but not <a href="#stamen">stamens</a>.</dd>

						<dt id="pith">Pith</dt>
						<dd>&#151; The <a href="#parenchymatous">parenchymatous</a>, often spongy or porous, central portions of stems and branchlets. [<a href="plate07.php" title="Plate 07">Plate 7</a>]</dd>

						<dt id="pitted">Pitted</dt>
						<dd>&#151; Beset with depressions or pits.</dd>

						<dt id="placenta">Placenta</dt>
						<dd>&#151; The inside portion of the <a href="#ovary">ovary</a> which bears the <a href="#ovule">ovules</a>.</dd>

						<dt id="plait">Plait</dt>
						<dd>&#151; Specifically, referring to the folded, often fringed, membrane between the <a href="#corolla">corolla</a> <a href="#lobe">lobes</a> in the <a href="#genus">genus</a> <em>Gentiana</em>.</dd>

						<dt id="plano-convex">Plano-convex</dt>
						<dd>&#151; Similar to <a href="#lenticular">lenticular</a>, but with one of the <a href="#face">faces</a> flat instead of <a href="#convex">convex</a>.</dd>

						<dt id="plicate">Plicate</dt>
						<dd>&#151; Folded into <a href="#plait">plaits</a>, usually lengthwise, thus similar to <a href="#corrugated">corrugated</a>.</dd>

						<dt id="plumose">Plumose</dt>
						<dd>&#151; Beset with numerous, fine, <a href="#pinnate">pinnately</a> arranged hairs; resembling a feather.</dd>

						<dt id="pod">Pod</dt>
						<dd>&#151; A general term used with different fruit types, such as <a href="#legume">legume</a> (pea pod), <a href="#follicle">follicle</a> (milkweed pod), or for certain seed-bearing <a href="#capsule">capsules</a> (iris pod).</dd>

						<dt id="pollinium">Pollinium</dt>
						<dd>&#151; A coherent mass of pollen, such as in the Orchidaceae family and Asclepiadaceae family.  Plural: pollinia.</dd>

						<dt id="polygamous">Polygamous</dt>
						<dd>&#151; Typically referring to an individual plant which contains both <a href="#perfect">perfect</a> and <a href="#imperfect">imperfect</a> flowers.</dd>

						<dt id="polymorphic">Polymorphic</dt>
						<dd>&#151; Having a number of various forms.</dd>

						<dt id="pome">Pome</dt>
						<dd>&#151; A fleshy fruit (as in the apple), formed from an <a href="#inferior">inferior</a> <a href="#ovary">ovary</a> with several <a href="#locule">locules</a>. [<a href="plate11.php" title="Plate 11">Plate 11</a>]</dd>

						<dt id="pore">Pore</dt>
						<dd>&#151; The small area which bursts open in some types of <a href="#dehiscent">dehiscent</a> <a href="#capsule">capsules</a>; also the opening in some <a href="#anther">anthers</a> from which the pollen discharges.</dd>

						<dt id="poricidal">Poricidal</dt>
						<dd>&#151; <a href="#dehiscence">Dehiscing</a> by means of <a href="#pore">pores</a>. [<a href="plate11.php" title="Plate 11">Plate 11</a>]</dd>

						<dt id="posterior">Posterior</dt>
						<dd>&#151; Next to or close to the main <a href="#axis">axis</a>; its opposite is <a href="#anterior">anterior</a>.</dd>

						<dt id="prickle">Prickle</dt>
						<dd>&#151; A sharp, usually slender, <a href="#bristle">bristle</a> or <a href="#spine">spine</a> of the <a href="#epidermis">epidermis</a>, though originating in the deeper cell layers. [<a href="plate07.php" title="Plate 07">Plate 7</a>]</dd>

						<dt id="primary">Primary</dt>
						<dd>&#151; Principal; first order.</dd>

						<dt id="primocane">Primocane</dt>
						<dd>&#151; In <em>Rubus</em>, the <a href="#cane">cane</a> of the first year (usually lacking flowers).</dd>

						<dt id="prismatic">Prismatic</dt>
						<dd>&#151; Of the shape of a prism -- <a href="#angulate">angulate</a> with flat sides.</dd>

						<dt id="process">Process</dt>
						<dd>&#151; A projection or outgrowth from some parent tissue.</dd>

						<dt id="procumbent">Procumbent</dt>
						<dd>&#151; Trailing or reclining, but not rooting at the <a href="#node">nodes</a>. [<a href="plate01.php" title="Plate 01">Plate 1</a>]</dd>

						<dt id="prostrate">Prostrate</dt>
						<dd>&#151; Lying flat upon the substrate.</dd>

						<dt id="proximate">Proximate</dt>
						<dd>&#151; Near.  The near end. Opposite meaning of distal.</dd>

						<dt id="puberulent">Puberulent</dt>
						<dd>&#151; Minutely hairy. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="pubescent">Pubescent</dt>
						<dd>&#151; Hairy.</dd>

						<dt id="pulverulent">Pulverulent</dt>
						<dd>&#151; Appearing powdery or <a href="#mealy">mealy</a>. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="pulvinus">Pulvinus</dt>
						<dd>&#151; A swelling or enlargement, typically in the <a href="#axil">axils</a> of the branches in a grass <a href="#inflorescence">inflorescence</a>.</dd>

						<dt id="punctate">Punctate</dt>
						<dd>&#151; Dotted, particularly with dark or <a href="#translucent">translucent</a> dots or <a href="#gland">glands</a>.</dd>

						<dt id="puncticulate">Puncticulate</dt>
						<dd>&#151; Minutely <a href="#punctate">punctate</a>.</dd>

						<dt id="pungent">Pungent</dt>
						<dd>&#151; Very sharp; acrid to the taste or smell.</dd>

						<dt id="pustular">Pustular</dt>
						<dd>&#151; Bearing blisters or pustules.</dd>

						<dt id="pyramidal">Pyramidal</dt>
						<dd>&#151; Broadest at the base, tapering <a href="#apical">apically</a>; pyramid-shaped.</dd>

						<dt id="pyrene">Pyrene</dt>
						<dd>&#151; The <a href="#nutlet">nutlet</a> of a <a href="#drupe">drupe</a>, such as the seed and bony endocarp of a cherry.</dd>

						<dt id="pyriform">Pyriform</dt>
						<dd>&#151; Pear-shaped.</dd>

						<dt id="q">Q</dt>
						<dd><hr /></dd>

						<dt id="quadrangular">Quadrangular</dt>
						<dd>&#151; Four-angled.</dd>

						<dt id="r">R</dt>
						<dd><hr /></dd>

						<dt id="raceme">Raceme</dt>
						<dd>&#151; A <a href="#simple">simple</a> <a href="#inflorescence">inflorescence</a> in which the flowers are <a href="#pedicellate">pedicellate</a> and arranged singly along an <a href="#elongate">elongate</a> <a href="#axis">axis</a>. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="racemiform">Racemiform</dt>
						<dd>&#151; Resembling a <a href="#raceme">raceme</a>; or an adjective describing a <a href="#raceme">raceme</a>.</dd>

						<dt id="racemose">Racemose</dt>
						<dd>&#151; Having flowers in <a href="#raceme">racemes</a>.</dd>

						<dt id="rachilla">Rachilla</dt>
						<dd>&#151; A <a href="#secondary">secondary</a> <a href="#rachis">rachis</a>. [<a href="plate12.php" title="Plate 12">Plate 12</a>]</dd>

						<dt id="rachis">Rachis</dt>
						<dd>&#151; The principal <a href="#axis">axis</a> of an <a href="#inflorescence">inflorescence</a> or <a href="#compound">compound</a> leaf. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd></dd>

						<dt id="radiallysymmetrical">Radially symmetrical</dt>
						<dd>&#151; <a href="#actinomorphic">Actinomorphic</a>; capable of being bisected into two or more similar planes. Same as <a href="#regular">regular</a>. [<a href="plate09.php" title="Plate 09">Plate 9</a>]</dd>

						<dt id="radiate">Radiate</dt>
						<dd>&#151; Spreading in all directions.</dd>

						<dt id="ranked">Ranked</dt>
						<dd>&#151; Ordered in a series, usually used with a number, such as two-ranked.</dd>

						<dt id="ray">Ray</dt>
						<dd>&#151; A strap-shaped, <a href="#ligulate">ligulate</a>, typically <a href="#margin">marginal</a>, flower in the <a href="#head">head</a> of a composite <a href="#inflorescence">inflorescence</a>; also one of the principal branches of an <a href="#umbellate">umbellate</a> or <a href="#cymose">cymose</a> <a href="#inflorescence">inflorescence</a>. [<a href="plate08.php" title="Plate 08">Plate 8</a> and [<a href="plate12.php" title="Plate 12">Plate 12</a>]</dd>

						<dt id="rayflower">Ray flower</dt>
						<dd>&#151; A strap-shaped, <a href="#ligulate">ligulate</a>, typically <a href="#margin">marginal</a>, flower in the head of a composite <a href="#inflorescence">inflorescence</a>. Also called <a href="#ligulate">ligulate</a> flower. Compare to <a href="#diskflower">disk flower</a>. [<a href="plate12.php" title="Plate 12">Plate 12</a>]</dd>

						<dt id="receptacle">Receptacle</dt>
						<dd>&#151; An enlarged or <a href="#elongate">elongated</a> end of a <a href="#pedicel">pedicel</a>, <a href="#peduncle">peduncle</a>, or <a href="#scape">scape</a> on which some or all of the flower parts are borne, such as in the Asteraceae family or certain genera in the Rosaceae family. [<a href="plate09.php" title="Plate 09">Plate 9</a>]</dd>

						<dt id="recurved">Recurved</dt>
						<dd>&#151; Directed backward or downward.</dd>

						<dt id="reflexed">Reflexed</dt>
						<dd>&#151; Abruptly turned or bent downward.</dd>

						<dt id="regular">Regular</dt>
						<dd>&#151; <a href="#radiallysymmetrical">Radially symmetrical</a>, capable of being bisected into two or more similar planes. See <a href="#actinomorphic">actinomorphic</a>.</dd>

						<dt id="remotely">Remotely</dt>
						<dd>&#151; Distantly; far apart.</dd>

						<dt id="reniform">Reniform</dt>
						<dd>&#151; Kidney-shaped. [<a href="plate03.php" title="Plate 03">Plate 3</a>]</dd>

						<dt id="repand">Repand</dt>
						<dd>&#151; Typically with a shallowly, unevenly <a href="#lobe">lobed</a> or <a href="#sinuate">sinuate</a> <a href="#margin">margin</a>.</dd>

						<dt id="repent">Repent</dt>
						<dd>&#151; <a href="#prostrate">Prostrate</a>, creeping along the ground, typically applying to those plants which root at the <a href="#node">nodes</a>. [<a href="plate01.php" title="Plate 01">Plate 1</a>]</dd>

						<dt id="resinous">Resinous</dt>
						<dd>&#151; Appearing to secrete or exude resin. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="resupinate">Resupinate</dt>
						<dd>&#151; Literally oriented upside down.</dd>

						<dt id="reticulate">Reticulate</dt>
						<dd>&#151; Forming a network of interconnecting <a href="#vein">veins</a>. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="retrorse">Retrorse</dt>
						<dd>&#151; Directed backward or downward. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="retuse">Retuse</dt>
						<dd>&#151; Notched slightly at an usually <a href="#obtuse">obtuse</a> <a href="#apex">apex</a>. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="revolute">Revolute</dt>
						<dd>&#151; Referring to <a href="#margin">margins</a> which tend to roll back toward the lower surface of the <a href="#midnerve">midrib</a> of a foliar structure. [<a href="plate04.php" title="Plate 04">Plate 4</a>]</dd>

						<dt id="rhizomatous">Rhizomatous</dt>
						<dd>&#151; Bearing <a href="#rhizome">rhizomes</a>.</dd>

						<dt id="rhizome">Rhizome</dt>
						<dd>&#151; An underground stem, typically horizontal. [<a href="plate11.php" title="Plate 11">Plate 11</a>]</dd>

						<dt id="rhombic">Rhombic</dt>
						<dd>&#151; A four-sided, typically <a href="#oblique">obliquely</a> angled, shape.</dd>

						<dt id="rhomboidal">Rhomboidal</dt>
						<dd>&#151; A solid with a <a href="#rhombic">rhombic</a> outline.</dd>

						<dt id="riparian">Riparian</dt>
						<dd>&#151; Growing along rivers; pertaining to rivers.</dd>

						<dt id="rootstock">Rootstock</dt>
						<dd>&#151; Same as a <a href="#rhizome">rhizome</a>; or the root system to which a scion is grafted.</dd>

						<dt id="roseate">Roseate</dt>
						<dd>&#151; Rose-colored.</dd>

						<dt id="rosette">Rosette</dt>
						<dd>&#151; Referring to a dense cluster of <a href="#basal">basal</a> leaves, particularly with reference to <a href="#winterannual">winter annuals</a> or <a href="#biennial">biennials</a>, or to <a href="#scapose">scapose</a> plants in which all the leaves are <a href="#basal">basal</a>.</dd>

						<dt id="rostellar">Rostellar</dt>
						<dd>&#151; Pertaining to the little <a href="#beak">beak</a>, or rostellum, found in some orchid flowers such as <em>Goodyera</em>.</dd>

						<dt id="rostrate">Rostrate</dt>
						<dd>&#151; <a href="#beak">Beaked</a>.</dd>

						<dt id="rosulate">Rosulate</dt>
						<dd>&#151; Turning outward and downward, such as in the petals of a double rose.</dd>

						<dt id="rotate">Rotate</dt>
						<dd>&#151; Pertaining to <a href="#corolla">corollas</a> which are more or less flat and circular in general outline; wheel-like. [<a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="rudimentary">Rudimentary</dt>
						<dd>&#151; Primitive; poorly developed.</dd>

						<dt id="rufescent">Rufescent, Rufous</dt>
						<dd>&#151; Reddish-brown.</dd>

						<dt id="rugose">Rugose</dt>
						<dd>&#151; Wrinkled.</dd>

						<dt id="rugulose">Rugulose</dt>
						<dd>&#151; Minutely <a href="#rugose">rugose</a>.</dd>

						<dt id="runcinate">Runcinate</dt>
						<dd>&#151; <a href="#coarse">Coarsely</a> and sharply cut or <a href="#incised">incised</a>, the principal divisions typically directed backward, typified by the leaf of a dandelion. [<a href="plate03.php" title="Plate 03">Plate 3</a>]</dd>

						<dt id="runner">Runner</dt>
						<dd>&#151; A <a href="#filiform">filiform</a> or very slender <a href="#stolon">stolon</a>.</dd>

						<dt id="s">S</dt>
						<dd><hr /></dd>

						<dt id="sac">Sac</dt>
						<dd>&#151; A pouch or <a href="#bladder">bladder</a>.</dd>

						<dt id="saccate">Saccate</dt>
						<dd>&#151; Having a <a href="#sac">sac</a>. [<a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="sagittate">Sagittate</dt>
						<dd>&#151; Shaped like an arrowhead, usually referring to leaves in which two <a href="#basal">basal</a> <a href="#lobe">lobes</a> are directed backward and downward. [<a href="plate03.php" title="Plate 03">Plate 3</a> and <a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="saline">Saline</dt>
						<dd>&#151; Salty.</dd>

						<dt id="salverform">Salverform</dt>
						<dd>&#151; Having a slender <a href="#tube">tube</a> abruptly expanded into a flat <a href="#limb">limb</a>, like a <em>Phlox</em> blossom. [<a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="samara">Samara</dt>
						<dd>&#151; An <a href="#indehiscent">indehiscent</a>, <a href="#wing">winged</a> fruit. [<a href="plate11.php" title="Plate 11">Plate 11</a>]</dd>

						<dt id="saprophyte">Saprophyte</dt>
						<dd>&#151; A plant which grows on and derives nourishment from a dead plant or organic matter.</dd>

						<dt id="sarmentose">Sarmentose</dt>
						<dd>&#151; Producing slender, often, <a href="#prostrate">prostrate</a>, <a href="#runner">runners</a> or branches.</dd>

						<dt id="scaberulous">Scaberulous</dt>
						<dd>&#151; Minutely <a href="#scabrous">scabrous</a>.</dd>
						 
						<dt id="scabrid">Scabrid</dt>
						<dd>&#151; Slightly roughened.</dd>

						<dt id="scabridulous">Scabridulous</dt>
						<dd>&#151; Minutely <a href="#scabrous">scabrous</a>.</dd>

						<dt id="scabrous">Scabrous</dt>
						<dd>&#151; Rough; harsh to the touch. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="scale">Scale</dt>
						<dd>&#151; Generally a thin, sometimes <a href="#scarious">scarious</a>, much reduced, leaf, <a href="#bract">bract</a>, or <a href="#perianth">perianth</a> part.</dd>

						<dt id="scalloped">Scalloped</dt>
						<dd>&#151; Said of <a href="#margin">margins</a> marked by a series of circular or arc-shaped <a href="#teeth">teeth</a> or projections.</dd>

						<dt id="scape">Scape</dt>
						<dd>&#151; A leafless flowering stem arising directly from the ground; or, such a stem which possesses minute <a href="#scale">scale</a>-like leaves much smaller than the <a href="#basal">basal</a> leaves. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="scapose">Scapose</dt>
						<dd>&#151; Having or appearing to have a <a href="#scape">scape</a>.</dd>

						<dt id="scarious">Scarious</dt>
						<dd>&#151; Typically, thin, dry, papery or membranous; usually not green.</dd>

						<dt id="schizocarp">Schizocarp</dt>
						<dd>&#151; A <a href="#pericarp">pericarp</a> which splits into two to several one-seeded portions, termed <a href="#mericarp">mericarps</a> or <a href="#nutlet">nutlets</a>. [<a href="plate11.php" title="Plate 11">Plate 11</a>]</dd>

						<dt id="scurfy">Scurfy</dt>
						<dd>&#151; Bearing <a href="#mealy">mealy</a> or bran-like granules or <a href="#scale">scales</a>. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="secondary">Secondary</dt>
						<dd>&#151; Once removed from <a href="#primary">primary</a>, which see. </dd>

						<dt id="secund">Secund</dt>
						<dd>&#151; Arranged or oriented along one side of an <a href="#axis">axis</a>, typically referring to the flowers of an <a href="#inflorescence">inflorescence</a>. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="segment">Segment</dt>
						<dd>&#151; One of the units of a leaf or <a href="#perianth">perianth</a> that is divided but not fully <a href="#compound">compound</a>.</dd>

						<dt id="senescent">Senescent</dt>
						<dd>&#151; Growing old; aging.</dd>

						<dt id="sepal">Sepal</dt>
						<dd>&#151; A <a href="#segment">segment</a> of the <a href="#calyx">calyx</a>. [<a href="plate09.php" title="Plate 09">Plate 9</a> and <a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="sepaloid">Sepaloid</dt>
						<dd>&#151; Of the texture of, or resembling, a <a href="#sepal">sepal</a>.</dd>

						<dt id="septate">Septate</dt>
						<dd>&#151; Divided by partitions.</dd>

						<dt id="septicidal">Septicidal</dt>
						<dd>&#151; Referring to <a href="#capsule">capsules</a> which dehisce through the side walls or partitions, not opening directly into the <a href="#locule">locule</a>. <a href="plate11.php" title="Plate 11">Plate 11</a>

						<dt id="septum">Septum</dt>
						<dd>&#151; Any kind of partition.</dd>

						<dt id="sericeous">Sericeous</dt>
						<dd>&#151; With silky hairs. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="serotinous">Serotinous</dt>
						<dd>&#151; Produced late in the season; late to open; having <a href="#cone">cones</a> that remain closed long after the seeds are ripe.</dd>

						<dt id="serrate">Serrate</dt>
						<dd>&#151; With sharp, typically forward-pointing, <a href="#teeth">teeth</a>. [<a href="plate04.php" title="Plate 04">Plate 4</a>]</dd>

						<dt id="serrulate">Serrulate</dt>
						<dd>&#151; Minutely <a href="#serrate">serrate</a>. [<a href="plate04.php" title="Plate 04">Plate 4</a>]</dd>

						<dt id="sessile">Sessile</dt>
						<dd>&#151; Without a stalk. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="setaceous">Setaceous</dt>
						<dd>&#151; <a href="#bristle">Bristle-like</a>.</dd>
						 
						<dt id="seriform">Seriform</dt>
						<dd>&#151; Having the form of a <a href="#bristle">bristle</a>.</dd>

						<dt id="setose">Setose</dt>
						<dd>&#151; Beset with <a href="#bristle">bristles</a>. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="setulose">Setulose</dt>
						<dd>&#151; Having minute <a href="#bristle">bristles</a>.</dd>

						<dt id="sheath">Sheath</dt>
						<dd>&#151; A <a href="#tubular">tubular</a> structure effected by the formation of leaf <a href="#margin">margins</a> around the stem.</dd>

						<dt id="shrub">Shrub</dt>
						<dd>&#151; A woody plant, typically smaller than a tree, and typified as being branched from the base with two or more main stems. [<a href="plate01.php" title="Plate 01">Plate 1</a>]</dd>

						<dt id="silicle">Silicle</dt>
						<dd>&#151; A short <a href="#silique">silique</a>. [<a href="plate11.php" title="Plate 11">Plate 11</a>]</dd>

						<dt id="silique">Silique</dt>
						<dd>&#151; A specialized <a href="#capsule">capsule</a> in which a frame-like <a href="#placenta">placenta</a> or partition separates the two <a href="#valve">valves</a>, most often occurring in the mustard family. [<a href="plate11.php" title="Plate 11">Plate 11</a>]</dd>

						<dt id="simple">Simple</dt>
						<dd>&#151; Not <a href="#compound">compound</a>, a term usually applied to leaves; also, referring to a stem without branches or modifications. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="sinuate">Sinuate</dt>
						<dd>&#151; Wavy. [<a href="plate04.php" title="Plate 04">Plate 4</a>]</dd>

						<dt id="sinus">Sinus</dt>
						<dd>&#151; A <a href="#cleft">cleft</a> or dissection between two <a href="#lobe">lobes</a>.</dd>

						<dt id="solitary">Solitary</dt>
						<dd>&#151; Alone; single.</dd>

						<dt id="sordid">Sordid</dt>
						<dd>&#151; Appearing dirty; definitely not white.</dd>

						<dt id="sorus">Sorus</dt>
						<dd>&#151; Specifically, in ferns, the clusters or discrete aggregations of <a href="#sporangium">sporangia</a>.</dd>

						<dt id="spadix">Spadix</dt>
						<dd>&#151; An <a href="#inflorescence">inflorescence</a> <a href="#spike">spike</a> typified by a very fleshy <a href="#axis">axis</a>. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="spathe">Spathe</dt>
						<dd>&#151; A <a href="#foliaceous">foliaceous</a> <a href="#bract">bract</a>-like or sheathiform structure enclosing or partly enclosing an <a href="#inflorescence">inflorescence</a>. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="spathiform">Spathiform</dt>
						<dd>&#151; Resembling a <a href="#spathe">spathe</a>.</dd>

						<dt id="spatulate">Spatulate</dt>
						<dd>&#151; Strongly <a href="#dilated">dilated</a> or expanded toward the <a href="#distal">distal</a> end; spoon-shaped. [<a href="plate03.php" title="Plate 03">Plate 3</a>]</dd>

						<dt id="species">Species</dt>
						<dd>&#151; A group of like individuals, as white pine or bur oak.</dd>

						<dt id="spicate">Spicate</dt>
						<dd>&#151; Arranged in, or resembling, a <a href="#spike">spike</a>.</dd>

						<dt id="spiciform">Spiciform</dt>
						<dd>&#151; <a href="#spike">Spike</a>-like.</dd>

						<dt id="spicule">Spicule</dt>
						<dd>&#151; A hard point or protuberance, typically on a leaf <a href="#margin">margin</a>.</dd>

						<dt id="spike">Spike</dt>
						<dd>&#151; An unbranched <a href="#inflorescence">inflorescence</a> in which the flowers are <a href="#sessile">sessile</a> or subsessile along an <a href="#elongate">elongate</a> <a href="#axis">axis</a>. [<a href="plate08.php" title="Plate 08">Plate 8</a> and <a href="plate12.php" title="Plate 12">Plate 12</a>]</dd>

						<dt id="spikelet">Spikelet</dt>
						<dd>&#151; A <a href="#secondary">secondary</a> or small <a href="#spike">spike</a>; specifically, in the Poaceae family, the unit composed or one or two <a href="#glume">glumes</a> <a href="#subtend">subtending</a> one to several sets of <a href="#lemma">lemma</a> and <a href="#palea">palea</a> combinations. [<a href="plate12.php" title="Plate 12">Plate 12</a>]</dd>

						<dt id="spine">Spine</dt>
						<dd>&#151; A sharp, stiff, often slender, <a href="#process">process</a>; a <a href="#thorn">thorn</a>. [<a href="plate07.php" title="Plate 07">Plate 7</a>]</dd>

						<dt id="spinescent">Spinescent</dt>
						<dd>&#151; Ending in a <a href="#spine">spine</a>, or bearing a <a href="#spine">spine</a>.</dd>

						<dt id="spinose">Spinose</dt>
						<dd>&#151; Having <a href="#spine">spines</a>; spiny.</dd>

						<dt id="spinulose">Spinulose</dt>
						<dd>&#151; With minute <a href="#spine">spines</a> or stiff <a href="#bristle">bristles</a>.</dd>

						<dt id="spontaneous">Spontaneous</dt>
						<dd>&#151; Growing wild, without cultivation.</dd>

						<dt id="sporangium">Sporangium</dt>
						<dd>&#151; <a href="#spore">Spore</a>-producing structure.</dd>

						<dt id="spore">Spore</dt>
						<dd>&#151; An asexual, one-<a href="#locule">loculed</a> propagule of ferns and fern allies.</dd>

						<dt id="sporocarp">Sporocarp</dt>
						<dd>&#151; The fruit case of certain flowerless plants, containing <a href="#sporangium">sporangia</a> or <a href="#spore">spores</a>.</dd>

						<dt id="sporophyll">Sporophyll</dt>
						<dd>&#151; A foliar organ upon which <a href="#sporangium">sporangia</a> are produced.</dd>

						<dt id="spur">Spur</dt>
						<dd>&#151; An extended <a href="#sac">sac</a> at the base of a <a href="#corolla">corolla</a>; a short branchlet with a very compact arrangement of leaf scars. [<a href="plate07.php" title="Plate 07">Plate 7</a> and <a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="squarrose">Squarrose</dt>
						<dd>&#151; Pertaining typically to <a href="#perianth">perianth</a> or <a href="#involucral">involucral</a> <a href="#segment">segments</a> which bend outward or downward at the tip.</dd>

						<dt id="stalk">Stalk</dt>
						<dd>&#151; The stem of any organ, as the <a href="#petiole">petiole</a>, <a href="#peduncle">peduncle</a>, <a href="#pedicel">pedicel</a>, <a href="#filament">filament</a>, or <a href="#stipe">stipe</a>.</dd>

						<dt id="stamen">Stamen</dt>
						<dd>&#151; Pollen-producing structure comprised of the <a href="#anther">anther</a> and the <a href="#filament">filament</a>. [<a href="plate09.php" title="Plate 09">Plate 9</a>]</dd>

						<dt id="staminate">Staminate</dt>
						<dd>&#151; Referring either to plants, <a href="#inflorescence">inflorescences</a>, or flowers which bear <a href="#stamen">stamens</a> but not <a href="#pistil">pistils</a>.</dd>

						<dt id="staminodium">Staminodium</dt>
						<dd>&#151; A <a href="#sterile">sterile</a> <a href="#stamen">stamen</a>, or any structure lacking an anther but which corresponds to a <a href="#stamen">stamen</a>.</dd>

						<dt id="standard">Standard</dt>
						<dd>&#151; The upper, <a href="#dilated">dilated</a> or expanded, petal in a <a href="#papilionaceous">papilionaceous</a> flower.</dd>

						<dt id="stellate">Stellate</dt>
						<dd>&#151; Star-shaped, usually in reference to hairs which are branched, forked or divided into two to several <a href="#ray">rays</a>. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="stem">Stem</dt>
						<dd>&#151; The main <a href="#axis">axis</a> or principal shoot of a plant.</dd>

						<dt id="sterile">Sterile</dt>
						<dd>&#151; Incapable of reproducing sexually; also, referring to soil, very poor in nutrients.</dd>

						<dt id="stigma">Stigma</dt>
						<dd>&#151; That part of the <a href="#pistil">pistil</a> receptive to pollen. [<a href="plate09.php" title="Plate 09">Plate 9</a>]</dd>

						<dt id="stipe">Stipe</dt>
						<dd>&#151; A small connecting stalk; sometimes a small stalk which elevates the <a href="#pistil">pistil</a> or flower above the receptacle or <a href="#pedicel">pedicel</a>; also, the <a href="#petiole">petiole</a> of a fern frond or of <em>Lemna</em>.</dd>

						<dt id="stipel">Stipel</dt>
						<dd>&#151; An appendage of a <a href="#leaflet">leaflet</a> analogous to a <a href="#stipule">stipule</a>.</dd>

						<dt id="stipitate">Stipitate</dt>
						<dd>&#151; Stalked, as defined above under <a href="#stipe">stipe</a>.</dd>

						<dt id="stipular">Stipular</dt>
						<dd>&#151; Belonging to <a href="#stipule">stipules</a>.</dd>

						<dt id="stipulate">Stipulate</dt>
						<dd>&#151; With <a href="#stipule">stipules</a>.</dd>

						<dt id="stipule">Stipule</dt>
						<dd>&#151; An appendage or <a href="#bract">bract</a> situated at either side of a leaf <a href="#axil">axil</a>. [<a href="plate02.php" title="Plate 02">Plate 2</a> and <a href="plate07.php" title="Plate 07">Plate 7</a>]</dd>

						<dt id="stipuliform">Stipuliform</dt>
						<dd>&#151; Resembling a <a href="#stipule">stipule</a>.</dd>

						<dt id="stolon">Stolon</dt>
						<dd>&#151; A horizontal, <a href="#prostrate">prostrate</a>, running branch or stem, often tending to root at the <a href="#node">nodes</a>.</dd>

						<dt id="stoloniferous">Stoloniferous</dt>
						<dd>&#151; Having <a href="#stolon">stolons</a>. [<a href="plate01.php" title="Plate 01">Plate 1</a>]</dd>

						<dt id="stoma">Stoma</dt>
						<dd>&#151; A minute orifice between two guard cells in a leaf <a href="#epidermis">epidermis</a>, through which gaseous exchange is effected -- plural stomata.</dd>

						<dt id="stramineous">Stramineous</dt>
						<dd>&#151; Tan or straw-colored.</dd>

						<dt id="striate">Striate</dt>
						<dd>&#151; Beset with fine, longitudinal lines or grooves.</dd>

						<dt id="strigillose">Strigillose</dt>
						<dd>&#151; Minutely <a href="#strigose">strigose</a>.</dd>

						<dt id="strigose">Strigose</dt>
						<dd>&#151; <a href="#pubescent">Pubescent</a> with <a href="#appressed">appressed</a> hairs. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="strigulose">Strigulose</dt>
						<dd>&#151; Minutely <a href="#strigose">strigose</a>.</dd>

						<dt id="strobile">Strobile</dt>
						<dd>&#151; An <a href="#inflorescence">inflorescence</a>, often, but not always, <a href="#indurated">indurated</a> or woody, characterized by a series of <a href="#imbricate">imbricated</a> <a href="#scale">scales</a>; a <a href="#cone">cone</a>.</dd>

						<dt id="style">Style</dt>
						<dd>&#151; A usually slender stalk connecting the <a href="#stigma">stigma</a> with the <a href="#ovary">ovary</a>. [<a href="plate09.php" title="Plate 09">Plate 9</a>]</dd>

						<dt id="stylopodium">Stylopodium</dt>
						<dd>&#151; A <a href="#disk">disk</a>-like expansion of the base of the <a href="#style">style</a>, with the term often meaning to include the <a href="#style">style</a> as well.</dd>

						<dt id="sub-">Sub-</dt>
						<dd>&#151; Prefix meaning nearly, almost, or less than.</dd>

						<dt id="submersed">Submersed</dt>
						<dd>&#151; Found under water.</dd>

						<dt id="subtend">Subtend</dt>
						<dd>&#151; Referring to any structure situated at the base of another structure.</dd>

						<dt id="subterranean">Subterranean</dt>
						<dd>&#151; Below the ground.</dd>

						<dt id="subulate">Subulate</dt>
						<dd>&#151; Awl-shaped.</dd>

						<dt id="subulus">Subulus</dt>
						<dd>&#151; A small point or <a href="#bristle">bristle</a>.</dd>

						<dt id="succulent">Succulent</dt>
						<dd>&#151; Very fleshy and juicy.</dd>

						<dt id="suckers">Suckers</dt>
						<dd>&#151; <a href="#vegetative">Vegetative</a> shoots from a proliferating root system.</dd>

						<dt id="suifruticose">Suifruticose</dt>
						<dd>&#151; Nearly or slightly woody.  Compare <a href="#fruticose">fruticose</a>.</dd>

						<dt id="sulcate">Sulcate</dt>
						<dd>&#151; Grooved or furrowed lengthwise.</dd>

						<dt id="superior">Superior</dt>
						<dd>&#151; Referring to an organ which stands above or appears over or higher than another similar organ; or in reference to an <a href="#ovary">ovary</a>, free from the <a href="#calyx">calyx</a>. [<a href="plate09.php" title="Plate 09">Plate 9</a>]</dd>

						<dt id="supra-">Supra-, super-</dt>
						<dd>&#151; Prefix meaning above, or upon, or more than. Opposite of <a href="#infra-">infra-</a>.</dd>

						<dt id="supra-axillary">Supra-axillary</dt>
						<dd>&#151; Borne above the <a href="#axil">axil</a>.</dd>

						<dt id="suture">Suture</dt>
						<dd>&#151; A seam or union between partitions; a line of <a href="#dehiscence">dehiscence</a> as in a <a href="#follicle">follicle</a> or <a href="#capsule">capsule</a>.</dd>

						<dt id="symmetrical">Symmetrical</dt>
						<dd>&#151; <a href="#regular">Regular</a> as to the number of its parts and their shape.</dd>

						<dt id="sympatric">Sympatric</dt>
						<dd>&#151; Occupying the same region.</dd>

						<dt id="sympetalous">Sympetalous</dt>
						<dd>&#151; With petals <a href="#united">united</a>, at least at the base.</dd>

						<dt id="sympodial">Sympodial</dt>
						<dd>&#151; A <a href="#determinate">determinate</a> <a href="#inflorescence">inflorescence</a> that simulates an <a href="#indeterminate">indeterminate</a> <a href="#inflorescence">inflorescence</a>, as if a scorpioid <a href="#cyme">cyme</a> were straight rather than <a href="#circinate">circinate</a>; or when an <a href="#alternate">alternate</a>-leaved plant's branching pattern mimics an <a href="#opposite">opposite</a>-leaved plant, producing forked branching.</dd>

						<dt id="syncarp">Syncarp</dt>
						<dd>&#151; A multiple fruit (usually fleshy), typified by the mulberry group.</dd>

						<dt id="synecology">Synecology</dt>
						<dd>&#151; Referring to the total ecology of a given plant community or community complex.</dd>

						<dt id="t">T</dt>
						<dd><hr /></dd>

						<dt id="taproot">Taproot</dt>
						<dd>&#151; The <a href="#primary">primary</a>, central, downward-growing root. [<a href="plate01.php" title="Plate 01">Plate 1</a>]</dd>

						<dt id="taxon">Taxon</dt>
						<dd>&#151; A discrete taxonomic unit.</dd>

						<dt id="teeth">Teeth</dt>
						<dd>&#151; Sharp <a href="#process">processes</a> at the edges of tissues.</dd>

						<dt id="tendril">Tendril</dt>
						<dd>&#151; A slender, often <a href="#ultimate">ultimately</a> coiled, foliar or branch-like organ which clings to a support. [<a href="plate07.php" title="Plate 07">Plate 7</a>]</dd>

						<dt id="tepal">Tepal</dt>
						<dd>&#151; Used in reference to the <a href="#sepal">sepals</a> and <a href="#petal">petals</a> (usually in the <a href="#monocot">Monocots</a>) which often resemble each other; in such instances either a given <a href="#sepal">sepal</a> or a given <a href="#petal">petal</a> is termed a tepal. [<a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="terete">Terete</dt>
						<dd>&#151; Circular in cross section.</dd>

						<dt id="terminal">Terminal</dt>
						<dd>&#151; Positioned at the summit.</dd>

						<dt id="terminus">Terminus</dt>
						<dd>&#151; End.</dd>

						<dt id="ternate">Ternate</dt>
						<dd>&#151; Three-parted; with three principal divisions; also, occurring in threes. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="terrestrial">Terrestrial</dt>
						<dd>&#151; Referring to plants which live out their lives on land.</dd>

						<dt id="testa">Testa</dt>
						<dd>&#151; Outer coat of a seed.</dd>

						<dt id="tetragonal">Tetragonal</dt>
						<dd>&#151; Four-angled.</dd>

						<dt id="thorn">Thorn</dt>
						<dd>&#151; A reduced, sharply pointed branch or modified leaf; or remnant that originates below the <a href="#epidermis">epidermis</a>.  About the same as a <a href="#spine">spine</a>. [<a href="plate07.php" title="Plate 07">Plate 7</a>]</dd>

						<dt id="thyrse">Thyrse</dt>
						<dd>&#151; A cylindrical or <a href="#ovoid">ovoid</a>, often compact, <a href="#panicle">panicle</a>. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="thyrsiform">Thyrsiform</dt>
						<dd>&#151; Resembling a <a href="#thyrse">thyrse</a>.</dd>

						<dt id="thyrsoid">Thyrsoid</dt>
						<dd>&#151; Having the form of a <a href="#thyrse">thyrse</a>.</dd>

						<dt id="tomentose">Tomentose</dt>
						<dd>&#151; Densely <a href="#pubescent">pubescent</a> with matted hairs. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="tomentulose">Tomentulose</dt>
						<dd>&#151; Finely <a href="#tomentose">tomentose</a>.</dd>

						<dt id="tomentum">Tomentum</dt>
						<dd>&#151; Closely matted or tangled hairs.</dd>

						<dt id="toothed">Toothed</dt>
						<dd>&#151; Bearing <a href="#teeth">teeth</a>.</dd>

						<dt id="torulose">Torulose</dt>
						<dd>&#151; Cylindrical, abruptly <a href="#contracted">contracted</a> at intervals, typically occurring in fruits, between the seeds.</dd>

						<dt id="translucent">Translucent</dt>
						<dd>&#151; Between <a href="#opaque">opaque</a> and transparent, thus allowing some light to get through.</dd>

						<dt id="transverse">Transverse</dt>
						<dd>&#151; Running or lying across something.</dd>

						<dt id="tree">Tree</dt>
						<dd>&#151; A woody plant, typically higher than a <a href="#shrub">shrub</a>, and typified as being unbranched at the base and having a strong single trunk. [<a href="plate01.php" title="Plate 01">Plate 1</a>]</dd>

						<dt id="tri">Tri-</dt>
						<dd>&#151; A prefix meaning three; for example, trifoliolate refers to three <a href="#leaflet">leaflets</a>.</dd>

						<dt id="trichome">Trichome</dt>
						<dd>&#151; A stiff, often multicellular, hair.</dd>

						<dt id="trident">Trident</dt>
						<dd>&#151; With three <a href="#segment">segments</a> or <a href="#lobe">lobes</a>, usually having a common origin.</dd>

						<dt id="trifid">Trifid</dt>
						<dd>&#151; Three-<a href="#cleft">cleft</a>.</dd>

						<dt id="trigonous">Trigonous</dt>
						<dd>&#151; Three-sided.</dd>

						<dt id="tripinnate">Tripinnate</dt>
						<dd>&#151; Said of a leaf in which the blade is <a href="#pinnate">pinnately</a> <a href="#compound">compound</a> with each of the divisions then <a href="#bipinnate">bipinnately</a> <a href="#compound">compound</a>. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="tristigmatic">Tristigmatic</dt>
						<dd>&#151; Bearing three <a href="#stigma">stigmas</a>.</dd>

						<dt id="truncate">Truncate</dt>
						<dd>&#151; Ending abruptly, as if cut straight across. [<a href="plate05.php" title="Plate 05">Plate 5</a>]</dd>

						<dt id="tube">Tube</dt>
						<dd>&#151; Usually referring to the <a href="#connate">connate</a> parts of either the <a href="#calyx">calyx</a> or the <a href="#corolla">corolla</a>.</dd>

						<dt id="tuber">Tuber</dt>
						<dd>&#151; A term generally referring to any thick, fleshy enlargement of a <a href="#rhizome">rhizome</a> or <a href="#stolon">stolon</a>. [<a href="plate01.php" title="Plate 01">Plate 1</a>]</dd>

						<dt id="tubercle">Tubercle</dt>
						<dd>&#151; A small <a href="#tuber">tuber</a>-like, often <a href="#indurated">indurated</a>, <a href="#process">process</a> or protuberance.</dd>

						<dt id="tuberculate">Tuberculate</dt>
						<dd>&#151; Having <a href="#tubercle">tubercles</a>. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="tuberiferous">Tuberiferous</dt>
						<dd>&#151; Bearing <a href="#tuber">tubers</a>.</dd>

						<dt id="tuberose">Tuberose</dt>
						<dd>&#151; Resembling a <a href="#tuber">tuber</a>.</dd>

						<dt id="tuberous">Tuberous</dt>
						<dd>&#151; Having the character of a <a href="#tuber">tuber</a>;  <a href="#tuber">tuber</a>-like in appearance. [<a href="plate01.php" title="Plate 01">Plate 1</a>]</dd>

						<dt id="tubular">Tubular</dt>
						<dd>&#151; <a href="#tube">Tube</a>-like. [<a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="tufted">Tufted</dt>
						<dd>&#151; Usually referring to the compact arrangement of the stem bases with respect to each other and their position in the soil; same as <a href="#cespitose">cespitose</a>.</dd>

						<dt id="tumid">Tumid</dt>
						<dd>&#151; Swollen.</dd>

						<dt id="turbinate">Turbinate</dt>
						<dd>&#151; Top-shaped; inversely <a href="#conical">conical</a>.</dd>

						<dt id="turgid">Turgid</dt>
						<dd>&#151; Swollen, or tightly drawn; said of a membrane or covering expanded by pressure from within.</dd>

						<dt id="Twig">Twig</dt>
						<dd>&#151; The shoot of a woody plant representing the growth of the current season and terminated basally by the circumferential <a href="#terminal">terminal</a> <a href="#bud">bud</a>-scar of the previous year. [<a href="plate07.php" title="Plate 07">Plate 7</a>]</dd>

						<dt id="u">U</dt>
						<dd><hr /></dd>

						<dt id="ultimate">Ultimate</dt>
						<dd>&#151; Last; final.</dd>

						<dt id="umbel">Umbel</dt>
						<dd>&#151; An <a href="#inflorescence">inflorescence</a> in which the branches all radiate from a common point. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="umbellate">Umbellate</dt>
						<dd>&#151; With <a href="#umbel">umbels</a>.</dd>

						<dt id="umbellet">Umbellet</dt>
						<dd>&#151; A <a href="#secondary">secondary</a> <a href="#umbel">umbel</a>.</dd>

						<dt id="umbelliform">Umbelliform</dt>
						<dd>&#151; Resembling an <a href="#umbel">umbel</a>.</dd>

						<dt id="umbilicate">Umbilicate</dt>
						<dd>&#151; Indented, <a href="#invaginated">invaginated</a>, or depressed near the center.</dd>

						<dt id="uncinate">Uncinate</dt>
						<dd>&#151; Hooked or bent at the tip. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="undulate">Undulate</dt>
						<dd>&#151; With a <a href="#sinuate">sinuate</a> or wavy surface or <a href="#margin">margin</a> (up and down, not in and out).</dd>

						<dt id="unisexual">Unisexual</dt>
						<dd>&#151; Of one sex, either <a href="#staminate">staminate</a> or <a href="#pistillate">pistillate</a> only.</dd>

						<dt id="united">United</dt>
						<dd>&#151; Connected.</dd>

						<dt id="unsymmetrical">Unsymmetrical</dt>
						<dd>&#151; <a href="#irregular">Irregular</a> as to the number of its parts, or their shape.</dd>

						<dt id="urceolate">Urceolate</dt>
						<dd>&#151; <a href="#urn-shaped">Urn-shaped</a>. [<a href="plate10.php" title="Plate 10">Plate 10</a>]</dd>

						<dt id="urn-shaped">Urn-shaped</dt>
						<dd>&#151; Hollow and cylindrical or <a href="#ovoid">ovoid</a>, and <a href="#contracted">contracted</a> at or below the mouth, like an urn; also known as <a href="#urceolate">urceolate</a>.</dd>

						<dt id="utricle">Utricle</dt>
						<dd>&#151; A bladder-like, usually <a href="#indehiscent">indehiscent</a>, one-seeded fruit.</dd>

						<dt id="v">V</dt>
						<dd><hr /></dd>

						<dt id="valvate">Valvate</dt>
						<dd>&#151; Opening by <a href="#valve">valves</a>; meeting at the edges without overlapping.</dd>

						<dt id="valve">Valve</dt>
						<dd>&#151; One of the <a href="#segment">segments</a> into which a <a href="#capsule">capsule</a> <a href="#dehiscence">dehisces</a>, previously having been held together by union along a <a href="#suture">suture</a>.</dd>

						<dt id="variety">Variety</dt>
						<dd>&#151; An <a href="#infraspecific">infraspecific</a> <a href="#taxon">taxon</a> with a range or habitat relatively <a href="#distinct">distinct</a> from other taxa within a <a href="#species">species</a>.</dd>

						<dt id="vascular">Vascular</dt>
						<dd>&#151; Having <a href="#vein">veins</a> or conducting vessels.</dd>

						<dt id="vascularbundle">Vascular Bundle</dt>
						<dd>&#151; An aggregate or cluster of vessels. [<a href="plate07.php" title="Plate 07">Plate 7</a>]</dd>

						<dt id="vegetative">Vegetative</dt>
						<dd>&#151; Referring to plant parts that are not involved in sexual reproduction.</dd>

						<dt id="vein">Vein</dt>
						<dd>&#151; A thread of fibro-vascular tissue in a leaf or other organ (which often branches).  Same as <a href="#nerve">nerve</a>.</dd>

						<dt id="veinlet">Veinlet</dt>
						<dd>&#151; A small <a href="#vein">vein</a>.</dd>

						<dt id="velutinous">Velutinous</dt>
						<dd>&#151; <a href="#pubescent">Pubescent</a> with velvety hairs. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="venation">Venation</dt>
						<dd>&#151; The arrangement or nature of the <a href="#vein">veins</a>.</dd>

						<dt id="ventral">Ventral</dt>
						<dd>&#151; Pertaining to the inner or anterior face of an organ; opposite of <a href="#dorsal">dorsal</a>.</dd>

						<dt id="versatile">Versatile</dt>
						<dd>&#151; Attached at or near the middle and turning freely on its support, such as an <a href="#anther">anther</a>.</dd>

						<dt id="verticil">Verticil</dt>
						<dd>&#151; A <a href="#whorl">whorl</a>. [<a href="plate08.php" title="Plate 08">Plate 8</a>]</dd>

						<dt id="verticillate">Verticillate</dt>
						<dd>&#151; Having <a href="#verticil">verticils</a>; that is, <a href="#whorl">whorled</a> or appearing so.</dd>

						<dt id="vestigial">Vestigial</dt>
						<dd>&#151; <a href="#rudimentary">Rudimentary</a>.</dd>

						<dt id="villous">Villous</dt>
						<dd>&#151; With long, straight, soft hairs. [<a href="plate06.php" title="Plate 06">Plate 6</a>]</dd>

						<dt id="vine">Vine</dt>
						<dd>&#151; A plant which climbs or sprawls by means of twining or <a href="#tendril">tendrils</a>; also, a plant which trails or creeps extensively along the ground.</dd>

						<dt id="virgate">Virgate</dt>
						<dd>&#151; Slenderly straight and upright; wand-shaped.</dd>

						<dt id="viscid">Viscid</dt>
						<dd>&#151; <a href="#glutinous">Glutinous</a>; sticky; <a href="#glandular">glandular</a>.</dd>

						<dt id="vivipary">Vivipary</dt>
						<dd>&#151; Germinating while still on the plant, as certain <a href="#bulb">bulbs</a> and transformations of floral tissues.</dd>

						<dt id="w">W</dt>
						<dd><hr /></dd>

						<dt id="warty">Warty</dt>
						<dd>&#151; <a href="#coarse">Coarsely</a> <a href="#papillate">papillose</a>.</dd>

						<dt id="whorl">Whorl</dt>
						<dd>&#151; An arrangement of three or more organs at a single <a href="#node">node</a>. [<a href="plate02.php" title="Plate 02">Plate 2</a>]</dd>

						<dt id="wing">Wing</dt>
						<dd>&#151; In general, any thin, expanded portion of an organ; sometimes referring to the well developed, exaggerated <a href="#decurrent">decurrence</a> of a leaf base; also, one of the two <a href="#lateral">lateral</a> petals of a <a href="#papilionaceous">papilionaceous</a> flower.</dd>

						<dt id="winterannual">Winter annual</dt>
						<dd>&#151; An <a href="#annual">annual</a> which sets its <a href="#rosette">rosette</a> and flowers the following spring.</dd>

						<dt id="wiry">Wiry</dt>
						<dd>&#151; Said of a stem which is thin but stiff.</dd>

						<dt id="woolly">Woolly</dt>
						<dd>&#151; With long, soft, matted or tangled hairs.</dd>

						<dt id="x">X</dt>
						<dd><hr /></dd>

						<dt id="xeric">Xeric</dt>
						<dd>&#151; A microclimatic term which refers to an area in which the soils are dry, containing very little, if any, moisture. Compare to <a href="#mesic">mesic</a>.</dd>

						<dt id="xylem">Xylem</dt>
						<dd>&#151; The conducting tissue of the vascular system that transports water, primarily from the roots, throughout the plant. Compare to <a href="#phloem">phloem</a>.</dd>

						<dt id="y">Y</dt>
						<dd><hr /></dd>

						<dt id="z">Z</dt>
						<dd><hr /></dd>

						<dt id="zygomorphic">Zygomorphic</dt>
						<dd>&#151; Referring to a <a href="#calyx">calyx</a> or <a href="#corolla">corolla</a> which is <a href="#bilaterallysymmetrical">bilaterally symmetrical</a>, capable of being divided into two equal halves along one plane only. [<a href="plate09.php" title="Plate 09">Plate 9</a>]</dd>

						</dl>
					</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
				
		
					<div id="content2"><!-- start of side content -->

					<div class="box">
					<h3>Family Glossaries</h3>
					<ul><li>
					<a href="asteraceae.php" 
					title="Glossary for Asteraceae">Asteraceae &#151; Composites</a>
					</li><li>
					<a href="cyperaceae.php" 
					title="Glossary for Cyperaceae">Cyperaceae &#151; Sedges</a>
					</li><li>
					<a href="poaceae.php" 
					title="Glossary for Poaceae">Poaceae &#151; Grasses</a>
					</li></ul></div>

					<div class="box">
					<h3>Contents of Plates</h3>
					<dl>
					<dt>
					<a href="plate01.php" 
					title="Plate 01">Plate 1</a>:</dt>
					<dd>Stem and Root Types.
					</dd>
					<dt>
					<a href="plate02.php" 
					title="Plate 02">Plate 2</a>:</dt>
					<dd>Leaf Composition, Parts, and Types.
					</dd>
					<dt>
					<a href="plate03.php" 
					title="Plate 03">Plate 3</a>:</dt>
					<dd>Leaf Shapes.
					</dd>
					<dt>
					<a href="plate04.php" 
					title="Plate 04">Plate 4</a>:</dt>
					<dd>Leaf Margins.
					</dd>
					<dt>
					<a href="plate05.php" 
					title="Plate 05">Plate 5</a>:</dt>
					<dd>Leaf Apices, Venation, and Bases.
					</dd>
					<dt>
					<a href="plate06.php" 
					title="Plate 06">Plate 6</a>:</dt>
					<dd>Surface Features.
					</dd>
					<dt>
					<a href="plate07.php" 
					title="Plate 07">Plate 7</a>:</dt>
					<dd>Stem and Leaf Parts, and Variations.
					</dd>
					<dt>
					<a href="plate08.php" 
					title="Plate 08">Plate 8</a>:</dt>
					<dd>Inflorescence Types.
					</dd>
					<dt>
					<a href="plate09.php" 
					title="Plate 09">Plate 9</a>:</dt>
					<dd>Floral Morphology.
					</dd>
					<dt>
					<a href="plate10.php" 
					title="Plate 10">Plate 10</a>:</dt>
					<dd>Corolla Types.
					</dd>
					<dt>
					<a href="plate11.php" 
					title="Plate 11">Plate 11</a>:</dt>
					<dd>Fruit Types.
					</dd>
					<dt>
					<a href="plate12.php" 
					title="Plate 12">Plate 12</a>:</dt>
					<dd>Sedges, Grasses, and Composites.
					</dd>

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
					</div>
				</div><!-- end of #wrapper1 -->
			</div><!-- end of #bodywrap -->
		</div><!-- end of #innervplantstext -->
		
	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>