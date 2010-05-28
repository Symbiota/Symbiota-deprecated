<?php
//error_reporting(E_ALL);

header("Content-Type: text/html; charset=ISO-8859-1");
include_once("../util/symbini.php");
?>
<html>
<head>
    <title><?php echo $defaultTitle; ?> Links</title>
    <link rel="stylesheet" href="../css/main.css" type="text/css" />
    <meta name='keywords' content='Arizona,New Mexico,Sonora,Sonoran,Desert,plants,lichens,natural history collections,flora, fauna, checklists,species lists' />
	<meta name="verify-v1" content="zPSqPevgoodwW402fOZTaMVBbG7oQY8p5bjcAdknJ/k=" />
</head>
<body>
	<?php
	$displayLeftMenu = (isset($misc_linksMenu)?$misc_linksMenu:"true");
	include($serverRoot."/util/header.php");
	if(isset($misc_linksCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $misc_linksCrumbs;
		echo " <b>Links</b>"; 
		echo "</div>";
	}
	?> 
        <!-- This is inner text! --> 
        <div class="innertext">
            <h1>Links</h1>
			<div>
				<div style="font-weight:bold;">General Resources</div>
				<ul>
					<li><a href='http://symbiota.org' target='_blank'>Symbiota Virtual Flora Software Project</a></li>
				</ul>
			</div>
			<div>
				<div style="font-weight:bold;">Regional Floristic Resources</div>
				<ul>
					<li><a href='http://gilaflora.com/' target='_blank'>Vascular Plants of the Gila Wilderness</a> - By Russ Kleinman</li>
					<li><a href='http://newmexicoflores.com/' target='_blank'>New Mexico Flores</a> - By Gene Jercinovic</li>
				</ul>
			</div>
			<div>
				<div style="font-weight:bold;">Other Symbiota Nodes</div>
				<ul>
					<li><a href='http://symbiota.org/cotram/index.php' target='_blank'>CoTRAM - Cooperative Taxonomic Resource for American Myrtaceae</a></li>
					<li><a href='http://symbiota.org/nalichens/index.php' target='_blank'>Consortium of North American Lichen Herbaria</a></li>
				</ul>
			</div>
			<div>
				<div style="font-weight:bold;">SEINet Project Managers</div>
				<ul>
					<li><b>Arizona Flora Project</b></li>
					<ul>
						<li><a href='http://collections.asu.edu/herbarium/index.html' target='_blank'>Arizona State University Vascular Plant Herbarium</a></li>
					</ul>
					<li><b>New Mexico Flora Project</b></li>
					<ul>
						<li>No Managers Yet Defined</li>
					</ul>
					<li><b>Sonoran Desert Regional Project</b></li>
					<ul>
						<li><a href='http://www.desertmuseum.org/' target='_blank'>Arizona-Sonora Desert Museum</a></li>
						<li><a href='http://www.conabio.gob.mx/remib_ingles/doctos/uson.html' target='_blank'>Herbario de la Universidad de Sonora (DICTUS)</a></li>
					</ul>
				</ul>
			</div>
		</div>

	<?php
	include($serverRoot."/util/footer.php");
	?> 

</body>
</html>
