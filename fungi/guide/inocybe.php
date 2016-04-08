<?php
//error_reporting(E_ALL);
include_once("../../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Inocybe</title>
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
            <h1>Guide to Inocybe</h1>

            <div style="margin:20px;">
            	 <div class="floatimg"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/INOC2/INOC2RIMO1.po.jpg" width="250" height="228" alt=""></div>

				<p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>


				<p>The genus <i>Inocybe</i> is a group of typically little brown mushrooms (or LBMs) in the family Cortinariaceae.  This large group is found throughout the world and is relatively common in the temperate regions of North America.  Members of <i>Inocybe</i> are primarily terrestrial and are present in many habitats and forest types.  All species in this genus are believed to form symbiotic relationships with plants, known as mycorrhizae.</i>

				<p>To most mushroom collectors, this genus may not seem to have much to offer.  No species are considered edible and macroscopic identification is often difficult.  Members of <i>Inocybe</i> are typically dull-colored mushrooms, with a dry, conical or umbonate cap, that is covered with conspicuous minute hairs (hence the genus name, which means &quot;fiber head&quot;).  The spore print color is brown, with variation in shade between species.  Many species do have a distinctive odor, which can be helpful in differentiating between very similar looking species.  Other important macroscopic characters for identification include the presence or absence of a bulbous or rimmed base on the stem, texture of the cap and stem surface, arrangement of the cap hairs, appearance of gill margins, and discoloration from bruising.</p>

				<img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/INOC2/micro.jpg" width="437" height="167" border="0" alt="microscopic images." title="Spores and cystidia of Inocybe.">

				<p>Under the microscope, the genus <i>Inocybe</i> stands out as one of the more interesting of the gilled mushrooms.  The spores range from smooth and bean shaped to spherical and very nodulose -- reminiscent of sea urchins.  The cystidia of most members of <i>Inocybe</i> are also quite notable, with some being thin-walled and clavate and others being langeniform, metuloid (thick-walled), and encrusted with small crystals at the apex.  The cystidia are often quite abundant and are present both on the gills as well as the stem of many species.  These microscopic features and their size (length and width) will help in identifying different species.</p>

				<p>This key to <i>Inocybe</i> covers those species found in the Chicago region that are under study at The Field Museum herbarium. Many of the names below are tentative (cf. and aff.).  The key includes all of the common species encountered in this area, although there are other rare or uncertain species in the herbarium not included.  Species were found across a range of habitats from shrub prairie to oak woodlands.  Both macroscopic and microscopic features (spores and cystidia) are included here to facilitate identification.  A <a href="#">table of microscopic features</a> has also been included for comparison between species.</p>

				<table class="key" cellpadding="3" cellspacing="0" border="0">
				<caption>Key to Species (in progress, not complete)</caption>
				<thead>
				<tr ><th colspan="2">Key Choice</th><th><span class="small">bar = 10 &micro;m</span></th>

				<th >Go&nbsp;to&nbsp;&nbsp;&nbsp;&nbsp;</th></tr>
				</thead>
				<tbody>

				<tr class="keychoice">
				<td id="k1">1a. Cap color white, cream, or lilac and cap surface not noticeably radially striate or rimose.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k2">&nbsp; &nbsp; &nbsp; &nbsp; 2</a></td>
				</tr><tr >
				<td >1b. Cap color not as above (e.g. yellow, orange-brown, gray-brown, vinaceous) and/or cap noticeably radially striate or rimose.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k3">&nbsp; &nbsp; &nbsp; &nbsp; 3</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k2">2a. Cap lilac, typically with umbo, surface smooth-silky, margin typically entire, 1-4 cm in diameter; gills white to gray-brown, margins often white fimbriate; stem whitish, silky.  Spores smooth, ovoid, 8-11 x 4-6 &micro;m; cystidia encrusted, metuloid, langeniform 35-70 x 14-20 &micro;m.</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/INOC2/INOC2LILA.key_cap.jpg" width="125" height="100" alt=""></td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/INOC2/INOC2LILA.key_spore.jpg" width="125" height="100" alt=""></td>
				<td ><span class="taxon"><a href="/fungi/species/species.jsp?gid=5509">
				 <i class="genus">Inocybe</i> 
				 <i class="epithet">lilacina</i></a>
					</span></td>
				</tr><tr >
				<td >2b. Cap white, typically with umbo, surface smooth-silky, margin typically entire, fruitbodies not staining salmon in age, 1-4 cm in diameter; gills white to gray-brown, margins typically white fimbriate; stem white, silky.  Spores smooth, ovoid, 8-11 x 4-6 &micro;m; cystidia encrusted, metuloid (but relatively thin-walled), langeniform, 35-70 x 14-20 &micro;m.</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/INOC2/INOC2GEOP.key_coll.jpg" width="125" height="100" alt=""></td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/INOC2/INOC2GEOP.key_spore.jpg" width="125" height="100" alt=""></td>
				<td ><span class="taxon"><a href="/fungi/species/species.jsp?gid=5468">
				 <i class="genus">Inocybe</i> 
				 <i class="epithet">geophylla</i></a>
					</span></td>
				</tr><tr >
				<td >2c. Cap cream to light tan-brown, with white umbo, surface typically smooth or finely striate, margin typically entire; gills white to gray-brown, margins not white fimbriate; stem pale pinkish-cream to grayish.  Spores nodulose, broadly ovoid, 6-8 x 4-6 &micro;m; cystidia encrusted, metuloid, langeniform, 35-70 x 11-16 &micro;m.</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/INOC2/INOC2ALBO1.key_coll.jpg" width="125" height="100" alt=""></td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/INOC2/INOC2ALBO1.key_spore.jpg" width="125" height="100" alt=""></td>
				<td ><span class="taxon"><a href="/fungi/species/species.jsp?gid=5397">
				 <i class="genus">Inocybe</i> cf. 
				 <i class="epithet">albodisca</i></a>
					</span></td>
				</tr>

				<tr class="keychoice">
				<td id="k3">3a. Cap conspicuously radially striate and/or rimose, with cap hairs typically splitting to reveal cap surface underneath, not scaly.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k4">&nbsp; &nbsp; &nbsp; &nbsp; 4</a></td>
				</tr><tr >
				<td >3b. Cap not consipuously radially striate, surface may be finely striate, with cap hairs appressed, or surface has patchy hairs or scales.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k9">&nbsp; &nbsp; &nbsp; &nbsp; 9</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k4">4a.  Cap hairs always appressed, generally lighter in color; gills margins white fimbriate; stem without bulbous base or collar.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k5">&nbsp; &nbsp; &nbsp; &nbsp; 5</a></td>
				</tr><tr >
				<td >4b. Cap hairs various shades of brown or vinaceous, typically not widely split apart, and sometimes erect; gills marginate or not.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k6">&nbsp; &nbsp; &nbsp; &nbsp; 6</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k5">5a. Cap typically straw-colored to ochre, surface conspicuously radially striate to margin, with variable colored hairs ranging from light yellow to rusty brown, often somewhat browner in center, and margin rimose with age; gills straw colored to olivaceous brown, margins white fimbriate; stem equal without bulbous base. Odor spermatic.  Spores smooth, bean-shaped to elliptical, 9-13 x 5-8 &micro;m; cystidia thin-walled, clavate, 28-60 x 12-22 &micro;m.  </td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><span class="taxon"><a href="/fungi/species/species.jsp?gid=5564">
				 <i class="genus">Inocybe</i> 
				 <i class="epithet">rimosa</i></a>
					</span></td>
				</tr><tr >
				<td >5b. Almost identical characters to <i>I. rimosa</i>, but with an odor of green corn.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><span class="taxon"><a href="/fungi/species/species.jsp?gid=5578">
				 <i class="genus">Inocybe</i> cf. 
				 <i class="epithet">sororia</i></a>
					</span></td>
				</tr><tr >
				<td >5c. Cap cream to honey yellow, surface with radially striate hairs, not darker in center, otherwise similar in appearance to <i>I. rimosa</i>; gills honey yellow, margins finely white fimbriate.  Spores nodulose, subglobose, 7 x 5 &micro;m; cystidia encrusted (but with relatively few crystals), metuloid, clavate, 45-60 &micro;m.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><span class="taxon">
				 <i class="genus">Inocybe</i> aff. 
				 <i class="epithet">pseudoumbrina</i>
					</span></td>
				</tr>

				<tr class="keychoice">
				<td id="k6">6a. Cap light yellow brown to rusty brown, with rusty brown radially erect or appressed, striate hairs (striate lines on cap tend to be more closely spaced than in <i>I. rimosa</i>), typically darker brown colored at center, sometimes with umbo, margin not rimose; gills whitish to grayish brown, margins not white fimbriate; stem with conspicuous collar at base.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k7">&nbsp; &nbsp; &nbsp; &nbsp; 7</a></td>
				</tr><tr >
				<td >6b. Cap hairs dark brown or vinaceous.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k8">&nbsp; &nbsp; &nbsp; &nbsp; 8</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k7">7a. Cap 2.5-6 cm in diameter.  Spores very nodulose, ovoid, 9-12 x 7-8 &micro;m; cheilo- and pleurocystidia encrusted, metuloid, 45-70 x 13-21 &micro;m, caulocystidia encrusted, metuloid, langeniform, 60-85 &micro;m; cystidia typically with long narrow necks.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><span class="taxon"><a href="/fungi/species/species.jsp?gid=5553">
				 <i class="genus">Inocybe</i> 
				 <i class="epithet">praetervisa</i></a>
					</span></td>
				</tr><tr >
				<td >7b. Fruitbodies typically smaller than <i>I. praetervisa</i>, cap 2-4.5 cm in diameter.  Spores very nodulose, ovoid, 7-9.5 x 5-7 &micro;m; cheilo- and pleurocystidia encrusted, metuloid, langeniform, 40-60 x 13-20 &micro;m, caulocystidia encrusted, metuloid, langeniform, 30-65 x 12-20 &micro;m.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><span class="taxon"><a href="/fungi/species/species.jsp?gid=5522">
				 <i class="genus">Inocybe</i> aff. 
				 <i class="epithet">mixtilis</i></a>
					</span></td>
				</tr>

				<tr class="keychoice">
				<td id="k8">8a. Cap honey yellow to straw colored, but conspicously covered by dark purplish brown radially striate hairs (much darker than <i>I. rimosa</i>), darker at center, usually with papillate or rounded umbo, margin entire; gills cream brown, margins not white fimbriate; stem entirely pruinose.  Spores very nodulose, ovoid to subcylindrical, 5-10 x 5-7 &micro;m; cystidia encrusted, metuloid, langeniform, 40-60 x 12-22 &micro;m.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><span class="taxon">
				 <i class="genus">Inocybe</i> aff. 
				 <i class="epithet">striata</i>
					</span></td>
				</tr><tr >
				<td >8b. Fruitbodies larger in stature than <i>I.</i> aff. <i>striata</i>; cap 3-10 cm in diameter, straw colored, but typically vinaceous in color because of abundant vinaceous radially striate almost scaly hairs (i.e. tending to clump into larger patches); gills medium brown, margins white fimbriate.  Spores smooth, bean shaped to oval, 9-13 x 5-7 &micro;m; cystidia thin-walled, clavate, 30-48 x 10-15 &micro;m.</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/INOC2/INOC2ADAE.key_coll.jpg" width="125" height="100" alt=""></td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/INOC2/INOC2ADAE.key_spore.jpg" width="125" height="100" alt=""></td>
				<td ><span class="taxon"><a href="/fungi/species/species.jsp?gid=5393">
				 <i class="genus">Inocybe</i> 
				 <i class="epithet">adaequata</i></a>
					</span></td>
				</tr>

				<tr class="keychoice">
				<td id="k9">9a. Cap medium to dark brown and scaly; fruitbodies small in stature (cap diameter 1-3 cm).</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k10">&nbsp; &nbsp; &nbsp; &nbsp; 10</a></td>
				</tr><tr >
				<td >9b. Cap color otherwise (with yellow, orange, reddish, or grayish tints) <B><U>or</U></B> fruitbodies larger in stature; if cap diameter less than 3 cm then cap not both dark brown and scaly.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k11">&nbsp; &nbsp; &nbsp; &nbsp; 11</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k10">10a. Cap medium to dark brown, umbo with patchy brown erect clumps of hairs (like raised shingles), becoming striate towards margin; stem medium brown (may have pinkish tint), often sinuous and texture more rubbery than fibrous, base sometimes with small whitish bulb; gills pale to medium brown.  Spores spherical, with distinctly long spines (like a sea urchin), 8-13 x 7-10 &micro;m; cystidia encrusted (but with few crystals), metuloid, langeniform, 30-55 x 10-13 &micro;m.</td>
				<td ><!-- image --></td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/INOC2/INOC2CALO.key_spore.jpg" width="125" height="100" alt=""></td>
				<td ><span class="taxon"><a href="/fungi/species/species.jsp?gid=5419">
				 <i class="genus">Inocybe</i> 
				 <i class="epithet">calospora</i></a>
					</span></td>
				</tr><tr >
				<td >10b. Cap dark brown with brown scales, typically plane with entire margin; stem dark brown and darker at base; gills dark chocolate brown, not marginate.  Spores smooth, almond shaped to cylindrical, 8-13 x 4-8 &micro;m; cystidia encrusted, metuloid (but relatively thin-walled), broadly langeniform, 48-70 x 13-20 &micro;m.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><span class="taxon"><a href="/fungi/species/species.jsp?gid=5499">
				 <i class="genus">Inocybe</i> aff. 
				 <i class="epithet">lacera</i></a>
					</span></td>
				</tr>

				<tr class="keychoice">
				<td id="k11">11a. Cap diameter 2-7 cm, yellow-ochre to orange-brown, covered with minute ochre brown scales, sometimes darker at center, broadly convex to plane, occasionally with umbo, margin entire; stem with patchy ochre brown scales; gills pallid ochre brown to dark brown, margins typically white fimbriate.  Spores smooth, ovoid, 8-12 x 4-6 &micro;m; cystidia thin-walled, clavate, 37-55 x 8-13 &micro;m.</td>
				<td ><!-- image --></td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/INOC2/INOC2CAES.key_spore.jpg" width="125" height="100" alt=""></td>
				<td ><span class="taxon"><a href="/fungi/species/species.jsp?gid=5417">
				 <i class="genus">Inocybe</i> 
				 <i class="epithet">caesariata</i></a>
					</span></td>
				</tr><tr >
				<td >11b. Cap color not as above; stem without patchy scales (but can be pruinose).</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k12">&nbsp; &nbsp; &nbsp; &nbsp; 12</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k12">12a. Cap hairs typically patchy or scaly, grayish to lighter brown.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k13">&nbsp; &nbsp; &nbsp; &nbsp; 13</a></td>
				</tr><tr >
				<td >12b. Cap hairs not patchy, darker brown.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k14">&nbsp; &nbsp; &nbsp; &nbsp; 14</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k13">13a. Cap diameter 3-5 cm, yellow brown to grayish tan, cap hairs scaly, close radially striate hairs present towards margin but can also appear patchy, center covered with light brown hairs that are sometimes patchy; gills grayish becoming medium brown; stem whitish.  Spores smooth, ovoid to almond shaped, 8-11 x 5-6 &micro;m; cystidia encrusted (but relatively few crystals), metuloid, langeniform, with very long and narrow necks, 48-75 x 13-19 &micro;m.</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/INOC2/INOC2FLOCFLOC.key_coll.jpg" width="125" height="100" alt=""></td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/INOC2/INOC2FLOCFLOC.key_spore.jpg" width="125" height="100" alt=""></td>
				<td ><span class="taxon">
				 <i class="genus">Inocybe</i> cf. 
				 <i class="epithet">flocculosa</i> var. <i class="epithet">flocculosa</i>
					</span></td>
				</tr><tr >
				<td >13b. Cap diameter 1-3 cm, grayish to brown, typically with patches of grey appressed hairs, not umbonate; gills medium to dark brown, not marginate; stem pruinose.  Spores nodulose, ovoid, 7-12 x 5-7 &micro;m; cystidia encrusted, metuloid, broadly clavate, 40-55 &micro;m in length x 15-23 &micro;m.</td>
				<td ><!-- image --></td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/INOC2/INOC2CICA.key_spore.jpg" width="125" height="100" alt=""></td>
				<td ><span class="taxon"><a href="/fungi/species/species.jsp?gid=5426">
				 <i class="genus">Inocybe</i> cf. 
				 <i class="epithet">cicatricata</i></a>
					</span></td>
				</tr>

				<tr class="keychoice">
				<td id="k14">14a. Cap diameter 2-5 cm, rusty to cocoa brown, finely radially striate, typically with dark brown umbo; gills variably pale to brown; stem pale white to cream colored, pruinose at least at apex.  Spores smooth, ovoid, 8-11 x 4-6 &micro;m; cystidia unencrusted to lightly encrusted, metuloid, langeniform but variable in shape, 35-70 x 16-20 &micro;m.</td>
				<td ><!-- image --></td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/INOC2/INOC2FUSCFUSC.key_spore.jpg" width="125" height="100" alt=""></td>
				<td ><span class="taxon"><a href="/fungi/species/species.jsp?gid=5464">
				 <i class="genus">Inocybe</i> aff. 
				 <i class="epithet">fuscidula</i> var. <i class="epithet">fuscidula</i></a>
					</span></td>
				</tr><tr >
				<td >14b. Cap diameter 1.5-4 cm, dark grey brown to reddish brown, not radially striate, with rounded or indistinct umbo (sometimes a different color of brown); gills white to dark chocolate brown; stem with upper part reddish pruinose, cream colored at base.  Spores smooth, ovoid, 6-12 x 5-6 &micro;m; cystidia encrusted (but typically with few crystals), metuloid, langeniform, 40-80 x 12-22 &micro;m.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><span class="taxon"><a href="/fungi/species/species.jsp?gid=5529">
				 <i class="genus">Inocybe</i> aff. 
				 <i class="epithet">nitidiuscula</i></a>
					</span></td>
				</tr>

				</tbody>
				</table>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>