<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Biology of Fungi</title>
	<link href="../css/base.css" type="text/css" rel="stylesheet" />
	<link href="../css/main.css" type="text/css" rel="stylesheet" />
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
            <h1>Fungus Biology</h1>

            <div style="margin:20px;">
				<h2>Fungi are not plants</h2>

				<p>
				Fungi were once treated as plants but botanists and mycologists now consider them to make up a separate <a href="http://tolweb.org/tree?group=Fungi&amp;contgroup=Eukaryotes">Kingdom Fungi [external link]</a>.
				This group with tremendous diversity outnumbers species of plants. 
				</p>



				<h2>Fungi are consumers</h2>

				<p>Like animals, and unlike plants, all fungi are 
				consumers that obtain food from other living or dead organisms.
				Fungi and animals cannot produce their own food like plants. 
				Consumers use the oxygen released by plants and algae to "burn" their food in a controlled manner, breaking it down to simpler molecules and releasing stored energy, which are then used to build up their own bodies.
				</p>
				<p>Fungi, though often
				unseen, provide critical roles as decomposers, scavengers, mutualists, pathogens, and even predators. Decomposers (saprobes) recycle dead organic material, for
				example, bread mold, wood rot, and portabella mushrooms. Pathogens
				(parasites) feed on living organisms. Examples include Dutch elm
				disease, wheat rust, and sulphur shelf. Mutualists (beneficial
				symbionts) form beneficial partnerships with other organisms; notable
				among these are the lichens that are associated with algae, and the
				mycorrhizal fungi that support plant growth by associating with roots.
				Boletes and chanterelles with oak and pine, and microfungi with many
				terrestrial plants are good examples of mycorrhizal fungi.
				</p>
            </div>
        </div>
		
		<div id="content2">

			<img src="<?php echo $clientRoot; ?>/images.vplants/feature/AMANRUBE.po.jpg" width="250" height="300" alt="photo of reddish brown mushrooms" title="Amanita rubescens" />
			<div class="box imgtext">
			<p><i>Amanita rubescens</i> is a common partner of oak trees in the Chicago Region.  The underground portion of this mycorrhizal species supplies the tree roots with nutrients and obtains sugars in return. It is an obligate symbiont; this means that it can not survive without a partner tree.</p>
			</div>

			<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

		</div><!-- end of #content2 -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>