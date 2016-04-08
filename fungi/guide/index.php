<?php
//error_reporting(E_ALL);
include_once("../../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Fungi</title>
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
        <div  id="innervplantstext">
            <h1>Guide to Fungi of the Chicago Region</h1>

            <div style="margin:20px;">
            	<div class="floatimg"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/<?php echo $clientRoot; ?>/images.vplants/fungi/guide/feature/johndenk_250.jpg" width="250" height="376" alt="a variety of fungi"></div>

				<p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>
				
				<p>Use the <a href="/xsql/fungi/famlist.xsql" title="Index of families">Family Index</a> and <a href="/xsql/fungi/genlist.xsql" 
				title="Index of genera">Genus Index</a> to see alphabetical lists of included fungi. At the top of the page you can Search for fungi by name.</p>

				<p>This guide is written for the known fungi of the <a href="/map.html" title="See map of states.">Chicago Region</a>, that is northeastern Illinois, northwestern Indiana, southeastern Wisconsin, and the southwest corner of Michigan. It does not cover additional mushrooms and fungi found in other parts of these states, particularly species found in more northern or more southern forests.
				</p>

				<p>
				Choose a group below to start. Groups are separated based on their shape and the kind of structure that produces the spores. Not color. Some groups are mostly restricted to growing on the ground and others to growing on wood. In this key, mushrooms are generally thought of as having a distinct stem and cap (with a top and underside) and they are also fleshy fungi (soft to firm) that are short-lived (lasting a day or a week). Mushrooms in certain groups may have a short stem or a stem attached to the side of the cap.  Some gilled mushrooms don't have a stem. Additional fleshy fungi have other growth forms listed below, such as puffballs, stinkhorns, and jelly fungi. Other longer-lived fungi tend to be tougher, leathery, or hard.
				</p>


				<table class="key" cellpadding="3" cellspacing="0" border="0">
				<caption>Key to Groups</caption>
				<thead>
				<tr ><th ></th><th >Key Choice</th><th ></th></tr>
				</thead>
				<tbody>

				<tr class="keydivision">
				<td colspan="2">
				<h2>Division Basidiomycota</h2>
				<p>
				The following groups of fungi have spores produced on outside of a basidium (reproductive cell).
				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_basidium.gif" width="125" height="100" alt=""></td>
				</tr><tr >

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_agarics.gif" width="125" height="100" alt=""></td>
				<td >
				<h3><a href="agarics.html">Gilled mushrooms, the agarics</a></h3>
				<p>
				<b>Cap has gills underneath.</b> Gills may attach to the stem or run down the stem but the gills are not shallow ridges (if so, then see chanterelles below). Fleshy fungi with cap and stem. Stem may be short, lateral, or absent. Growing anywhere. If stem absent AND fungus is leathery to woody, and growing on wood, then see pored brackets below.
				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_agarics.jpg" width="125" height="100" alt=""></td>
				</tr><tr >

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_boletes.gif" width="125" height="100" alt=""></td>
				<td >
				<h3><a href="boletes.html">Pored mushrooms, the boletes</a></h3>
				<p>
				<b>Cap has pores or tubes underneath.</b> Pores can be round, angular, or radially elongated. Fleshy fungi with cap and stem. Stem may be short or off-center. Growing on the ground, rarely on rotted wood. If stem absent, then see pored brackets below.
				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_boletes.jpg" width="125" height="100" alt=""></td>
				</tr><tr >

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_chanterelles.gif" width="125" height="100" alt=""></td>
				<td >
				<h3>Chanterelles and trumpets</h3>
				<p>
				<b>Cap undersurface has ridges or wrinkles or is smooth.</b> Ridges can have cross-veins.  Fleshy fungi with cap and stem. Stem may be short. Growing on the ground, rarely on rotted wood. If stem absent or if growing on wood, then see crust fungi below. 
				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_chanterelles.jpg" width="125" height="100" alt=""></td>
				</tr><tr >

				<tr class="keychoice">
				<td ><!-- image --></td>
				<td >
				<h3>Toothed fungi, the hydnums</h3>
				<p>
				<b>Fungi with spines or teeth that hang downward.</b> May have a cap. May have a stem or have branches. Growing on the ground or on wood; one species on pine cones. If teeth are flattened and join together at base in a network (use hand lens), then see pored brackets below. If small and gelatinous, then see jelly fungi below.
				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_hydnums.jpg" width="125" height="100" alt=""></td>
				</tr><tr >

				<tr class="keychoice">
				<td ><!-- image --></td>
				<td >
				<h3>Pored brackets, the polypores</h3>
				<p>
				<b>Cap has pores underneath.</b> Pores can be round, angular, radially elongated, or gill-like. Fungi with cap. Stem absent or present. Often growing on wood, some growing on the ground or from buried roots. If stem present and fungus not growing on wood, then fungus is tough, leathery to woody, not fleshy.
				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_polypores.jpg" width="125" height="100" alt=""></td>
				</tr><tr >

				<tr class="keychoice">
				<td ><!-- image --></td>
				<td >
				<h3>Crusts, parchment fungi</h3>
				<p>
				<b>Underside smooth, wrinkled, veined, bumpy.</b> Fungi with or without cap. Most are thin brackets or crusts. One species has split gills. One species has crinkled gill like folds. If stem present then the cap does not have gills, pores, or teeth. Often growing on wood, some growing on the ground.  
				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_crust.jpg" width="125" height="100" alt=""></td>
				</tr><tr >

				<tr class="keychoice">
				<td ><!-- image --></td>
				<td >

				<h3>Club and coral fungi</h3>
				<p>
				<b>Fungi with slender stalks or club-shaped or branched, coral-like.</b> See also earth tongues below. Often growing on the ground, some found on wood.
				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_coral.jpg" width="125" height="100" alt=""></td>
				</tr><tr >

				<tr class="keychoice">
				<td ><!-- image --></td>
				<td >
				<h3>Gastroid fungi: puffballs, stinkhorns, bird's nest fungi, false truffles</h3>
				<p>
				<b>Fungi that have spores produced within an enclosed structure.</b> Most growing on the ground, some found on wood or wood chips. False truffles grow underground.
				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_gastroid.jpg" width="125" height="100" alt=""></td>
				</tr><tr >

				<tr class="keychoice">
				<td ><!-- image --></td>
				<td >
				<h3>Jelly fungi</h3>
				<p>
				<b>Fungi that have a gelatinous texture.</b> Spores produced on surface of the jelly. Most growing on wood, some found on ground or bases of plants.
				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_jelly.jpg" width="125" height="100" alt=""></td>
				</tr><tr >

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_rust2.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3>Rusts, smuts, bunts, and relatives</h3>
				<p>
				Few of these fungi are currently included in vPlants. 
				For more information see Tree of Life Web Project: <a href="http://www.tolweb.org/Urediniomycotina/">Urediniomycotina</a> and <a href="http://www.tolweb.org/Ustilaginomycetes/">Ustilaginomycetes</a>.
				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_rust.jpg" width="125" height="100" alt=""></td>
				</tr><tr >

				<tr class="keydivision">
				<td colspan="2">
				<h2>Division Ascomycota</h2>
				<p>
				The following fungi have spores produced inside an ascus (reproductive cell).
				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_ascus.gif" width="125" height="100" alt=""></td>
				</tr><tr >

				<tr class="keychoice">
				<td ><!-- image --></td>
				<td >
				<h3>Morels, saddle fungi, and cup fungi</h3>
				<p>

				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_cup.jpg" width="125" height="100" alt=""></td>
				</tr><tr >

				<tr class="keychoice">
				<td ><!-- image --></td>
				<td >
				<h3>Earth tongues and jelly babies</h3>
				<p>

				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_jellybaby.jpg" width="125" height="100" alt=""></td>
				</tr><tr >

				<tr class="keychoice">
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_flask1.jpg" width="125" height="100" alt=""></td>
				<td >
				<h3>Flask fungi</h3>
				<p>

				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_flask2.jpg" width="125" height="100" alt=""></td>
				</tr><tr >

				<tr class="keychoice">
				<td ><!-- image --></td>
				<td >
				<h3>Truffles</h3>
				<p>
				Tuber fungi that grow underground.
				</p>
				</td>
				<td ><!-- image --></td>
				</tr><tr >

				<tr class="keychoice">
				<td ><!-- image --></td>
				<td >
				<h3>Lichens and lichenized fungi</h3>
				<p>
				These fungi are not currently included in vPlants.
				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_lichens.jpg" width="125" height="100" alt=""></td>
				</tr><tr >

				<tr class="keychoice">
				<td ><!-- image --></td>
				<td >
				<h3>Yeasts and relatives; mildews; molds, asexual fungi</h3>
				<p>
				These fungi are not currently included in vPlants.
				</p>
				</td>
				<td ><!-- image --></td>
				</tr><tr >

				</tbody>
				</table>




				<table class="key" cellpadding="3" cellspacing="0" border="0">
				<caption>Microscopic fungi and fungus-like organisms</caption>
				<thead>
				<tr ><th >The following groups are not currently included in vPlants</th>
				<th ></th></tr>
				</thead>
				<tbody>

				<tr class="keydivision">
				<td >
				<h2>Division Glomeromycota, the arbuscular mycorrhizal fungi</h2>
				<p>
				Fungi forming large multinucleate spores underground. These microscopic fungi form symbiotic partnerships with the roots of most plants.

				For more information see <a href="http://www.tolweb.org/Glomeromycota/">Tree of Life Web Project</a>.
				</p>
				</td>
				<td ><!-- image --></td>
				</tr><tr >

				<tr class="keydivision">
				<td >
				<h2>Division Zygomycota, the bread molds and sugar molds</h2>
				<p>
				Fungi that have a zygosporangium (reproductive cell). Includes black bread mold.  

				For more information see <a href="http://www.tolweb.org/Zygomycota/">Tree of Life Web Project</a>.
				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_zygos.jpg" width="125" height="100" alt=""></td>
				</tr><tr >

				<tr class="keydivision">
				<td >
				<h2>Division Chytridiomycota, the chytrids</h2>
				<p>
				Fungi that have zoospores (motile reproductive cells). Most are small aquatic fungi.

				For more information see <a href="http://www.tolweb.org/Chytridiomycota/">Tree of Life Web Project</a>.
				</p>
				</td>
				<td ><!-- image --></td>
				</tr><tr >

				<tr class="keydivision">
				<td >
				<h2>Myxomycetes, the slime molds</h2>
				<p>
				Fungus-like protists that are related to amoebae. 
				</p>
				</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/img/g_slimemolds.jpg" width="125" height="100" alt=""></td>
				</tr>

				<tr class="keydivision">
				<td >
				<h2>Oomycetes, the water molds</h2>
				<p>
				Fungus-like protists that are related to brown algae. Includes plant pathogens such as late blight of potato.  
				</p>
				</td>
				<td ><!-- image --></td>
				</tr><tr >

				</tbody>
				</table>

				<p class="small">Basidium drawing by intern Dwyer Kilcollen. All other images by Patrick Leacock unless noted.</p>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>