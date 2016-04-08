<?php
//error_reporting(E_ALL);
include_once("../../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Suillus</title>
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
            <h1>Guide to Suillus</h1>

            <div style="margin:20px;">
            	<div class="floatimg"><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/SUIL/SUILBREV.po.jpg" width="250" height="263" alt="" title="Suillus brevipes"></div>

				<p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>


				<p><!-- why separate from Boletaceae? -->
				This genus of boletes is mostly associated with conifers, such as pines and larch. To find a variety of species one needs to go to pine plantations or areas of native conifers, such as jack pine and white pine near Lake Michigan. In the Chicago Region we have the atypical <i>Suillus castanellus</i> that has a dry cap, short decurrent pores, and is found with oak trees. The genus <i>Fuscoboletinus</i> has been merged with <i>Suillus</i>.
				</p>

				<p>The following key is adapted from the bolete key by Wyatt Gaswick</i>.

				<table class="key" cellpadding="3" cellspacing="0" border="0">
				<caption>Key to Species</caption>
				<thead>
				<tr ><th colspan="3">Key Choice</th><th >Go to</th></tr>
				</thead>
				<tbody>

				<tr class="keychoice">
				<td id="k1">1a. Stem smooth, or with reticulation (netlike pattern).</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k2">&nbsp; &nbsp; &nbsp; &nbsp; 2</a></td>
				</tr><tr >
				<td >1b. Stem with a ring or resinous dots, or ornamented.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k3">&nbsp; &nbsp; &nbsp; &nbsp; 3</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k2">2a. Cap dry. Stem brown. Pores somewhat decurrent. Found with oak.</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/SUIL/SUILCAST.key_coll.jpg" width="125" height="100" alt=""></td>
				<td ><!-- image --></td>
				<td ><a href="/fungi/species/species.jsp?gid=11408"><i class="genus">Suillus</i> <i class="epithet">castanellus</i></a></td>
				</tr><tr >
				<td >2b. Cap viscid to slimy. Stem whitish. Found with pine.</td>
				<td ><img src="<?php echo $clientRoot; ?>/images.vplants/fungi/guide/SUIL/SUILBREV.key_situ.jpg" width="125" height="100" alt=""></td>
				<td ><!-- image --></td>
				<td ><a href="/fungi/species/species.jsp?gid=11402"><i class="genus">Suillus</i> <i class="epithet">brevipes</i></a></td>
				</tr>

				<tr class="keychoice">
				<td id="k3">3a. Stem with a ring, ring often gelatinous.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k4">&nbsp; &nbsp; &nbsp; &nbsp; 4</a></td>
				</tr><tr >
				<td >3b. Stem without a distinct ring.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k8">&nbsp; &nbsp; &nbsp; &nbsp; 8</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k4">4a. Flesh bruising pink to brown when exposed.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k5">&nbsp; &nbsp; &nbsp; &nbsp; 5</a></td>
				</tr><tr >
				<td >4b. Flesh not bruising when exposed.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k6">&nbsp; &nbsp; &nbsp; &nbsp; 6</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k5">5a. Cap brownish to yellowish to olive.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="/fungi/species/species.jsp?gid=11415"><i class="genus">Suillus</i> <i class="epithet">flavidus</i></a></td>
				</tr><tr >
				<td >5b. Cap pinkish-tan or purplish-tan, not yellowish.  (not yet recorded for Chicago)</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><i class="genus">Suillus</i> <i class="epithet">subalutaceus</i></td>
				</tr>

				<tr class="keychoice">
				<td id="k6">6a. Pores radially arranged or elongated.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="/fungi/species/species.jsp?gid=11422"><i class="genus">Suillus</i> <i class="epithet">glandulosus</i></a></td>
				</tr><tr >
				<td >6b. Pores angular.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k7">&nbsp; &nbsp; &nbsp; &nbsp; 7</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k7">7a. Cap yellowish to pale brown, cap slime tastes acidic.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="/fungi/species/species.jsp?gid=11434"><i class="genus">Suillus</i> <i class="epithet">intermedius</i></a></td>
				</tr><tr >
				<td >7b. Cap usually darker, cap slime not acidic if present.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="/fungi/species/species.jsp?gid=11443"><i class="genus">Suillus</i> <i class="epithet">luteus</i></a></td>
				</tr>

				<tr class="keychoice">
				<td id="k8">8a. Pores bruising reddish to brownish. Cap with reddish flecks or patches. Under 5-needle pine.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="/fungi/species/species.jsp?gid=11398"><i class="genus">Suillus</i> <i class="epithet">americanus</i></a></td>
				</tr><tr >
				<td >8b. Pores unchanging when bruised.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k9">&nbsp; &nbsp; &nbsp; &nbsp; 9</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k9">9a. Cap yellow to orange with appressed brownish fibers.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="/fungi/species/species.jsp?gid=11469"><i class="genus">Suillus</i> <i class="epithet">subaureus</i></a></td>
				</tr><tr >
				<td >9b. Not as above.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="#k10">&nbsp; &nbsp; &nbsp; &nbsp; 10</a></td>
				</tr>

				<tr class="keychoice">
				<td id="k10">10a. Resinous dots on stem conspicuous, brownish.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="/fungi/species/species.jsp?gid=11469"><i class="genus">Suillus</i> <i class="epithet">granulatus</i> subsp. 
				<i class="epithet">snellii</i></a></td>
				</tr><tr >
				<td >10b. Resinous dots faint.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="/fungi/species/species.jsp?gid=11402"><i class="genus">Suillus</i> <i class="epithet">brevipes</i></a></td>
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