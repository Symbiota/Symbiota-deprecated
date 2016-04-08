<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants | What Are Fungi?</title>
	<link href="css/base.css" type="text/css" rel="stylesheet" />
	<link href="css/main.css" type="text/css" rel="stylesheet" />
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
            <h1>What Are Fungi?</h1>

            <div style="margin:20px;">
            	<p>
				We are expanding vPlants to provide information on the macrofungi of the Chicago region. Macrofungi are those fungi forming visible fruiting bodies, such as mushrooms, puffballs, and brackets.  The identification of these species relies on characters of their fruiting bodies, and fruiting bodies are the source of specimen data and images on vPlants.  Lichens (fungi symbiotic with algae) and the microfungi that do not form large fruiting bodies (or lack them), such as yeasts, molds, powdery mildews, rusts, and soil fungi, are not included on vPlants.
				</p>
				<p>
				Fungi were once treated as plants but botanists now consider them to make up a separate <a href="http://tolweb.org/tree?group=Fungi&contgroup=Eukaryotes">Kingdom Fungi</a>.  This group with tremendous diversity outnumbers species of plants.  Like animals, and unlike plants, all fungi are consumers or scavengers that obtain food from other living or dead organisms.  Fungi, though often unseen, provide critical roles as decomposers, pathogens, and mutualists.  Decomposers (saprobes) recycle dead organic material, for example, bread mold, wood rot, and portabella mushrooms.  Pathogens (parasites) feed on living organisms.  Examples include Dutch elm disease, wheat rust, and sulphur shelf.  Mutualists (beneficial symbionts) form beneficial partnerships with other organisms; notable among these are the lichens that are associated with algae, and the mycorrhizal fungi that support plant growth by associating with roots.  Boletes and chanterelles with oak and pine, and microfungi with many terrestrial plants are good examples of mycorrhizal fungi.
				</p>
				<p>
				Most macrofungi treated here are members of the <a href="http://tolweb.org/tree?group=Basidiomycota&contgroup=Fungi">Phylum Basidiomycota</a>  (Class Hymenomycetes).  Other fungi found in vPlants, such as morels and cup fungi, are of the  <a href="http://tolweb.org/tree?group=Ascomycota&contgroup=Fungi">Phylum Ascomycota</a> (Class Euascomycetes).  In the future, vPlants may include other fungal groups such as lichens and plant pathogens (rusts, etc.).
				</p>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>