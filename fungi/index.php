<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Fungi</title>
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
            <h1>Fungi of the Chicago Region</h1>

            <div style="margin:20px;">
            	<p>The 
				<a href="/map_county.html" title="See County Map for Chicago Region.">24 counties</a> 
				  of the 
				  <a href="/chicago.html" title="Why the Chicago Region?">Chicago Region</a>
				  support approximately 
				  <a href="/fungi/diversity.html" title="How many fungi.">1,000 kinds</a>
				  of 
				  <a href="/fungi/guide/index.html" title="Guide to fungi.">larger fungi</a>.
				 </p>

				<h2>Kinds of fungi included in vPlants</h2>
				<div id="floatimg"><img src="<?php echo $clientRoot; ?>/images.vplants/feature/fungus_170_250.jpg" width="170" height="250" alt="detail view of gills and stem." title="Tricholoma (Photo by P. R. Leacock)."></div>

				<p class="large">
				Macrofungi are mushrooms, puffballs, brackets, and other fungi forming visible fruiting bodies. 
				Fruiting bodies are reproductive structures, often short-lived, produced by the longer-lived mycelium living in the soil, wood, or other substrate (food source). Unlike plants, the identification of species and the source of specimen data and photos are primarily based on fruiting bodies rather than the entire organism.
				</p>

				<p>The <a href="http://tolweb.org/tree?group=Fungi&amp;contgroup=Eukaryotes">Kingdom Fungi [external link]</a> is comprised of several major groups.
				Many macrofungi treated here, particularly mushrooms, are members of the <a
				 href="http://tolweb.org/tree?group=Basidiomycota&amp;contgroup=Fungi">Phylum Basidiomycota
				 [external link]</a>  (Class Hymenomycetes).  Other fungi found in vPlants, such as morels and cup fungi, are of the  <a href="http://tolweb.org/tree?group=Ascomycota&amp;contgroup=Fungi">Phylum Ascomycota [external link]</a>
				(Class Euascomycetes). 
				</p>

				<h3>Fungi not included in vPlants at the present time</h3>

				<p>
				Lichens (fungi symbiotic with algae) and the microfungi that do not
				form large fruiting bodies (or lack them), such as yeasts, molds,
				powdery mildews, rusts, and soil fungi, are not yet included in vPlants.
				Also absent are the fungus-like protozoa groups: the slime molds and water molds.
				</p>
            </div>
        </div>
		
		<div id="content2"><!-- start of side content -->
			<!-- any image width should be 250 pixels -->

			<div class="box">
				<h3>Directory and Guides</h3>
				<ul><li><a href="guide/"
			 title="Identification guide">Guide to Fungi</a></li>
			<li><a href="/xsql/fungi/famlist.xsql" 
			 title="Index of families">Family Index</a></li>
			<li><a href="/xsql/fungi/genlist.xsql" 
			 title="Index of genera">Genus Index</a></li>
			<li><a href="/resources/biblio3.html" 
			 title="Guides for Chicago Region">Recommended Books</a></li>
			<li><a href="/resources/links3.html" 
			 title="Links to websites">Fungus Links</a></li></ul>
			</div>

			 <div id="simpleform">
			  <form name="simple" method="post" action="/xsql/fungi/findtaxa.xsql">
			   <fieldset><legend title="Enter name of fungus in one or more of the search fields.">Fungus Search</legend>
				<p><label for="family" accesskey="4" 
				 title="Example: Agaricaceae.">Family:</label>
				 <input class="texts" id="family" name="family" type="text" maxlength="30" 
				  title="Enter name of a family [one word, can use first several letters]." 
				  value=""></p>
				<p><label for="genus"  
				 title="Example: Agaricus.">Genus:</label> 
				 <input class="texts" id="genus" name="genus" type="text" maxlength="30" 
				  title="Enter name of a genus [one word, can use first several letters]." 
				  value=""></p>
				<p><label for="species" 
				 title="Example: campestris.">Species Epithet:</label> 
				 <input class="texts" id="species" name="species" type="text" maxlength="30" 
				  title="Enter the species epithet, or subspecies, or variety [one word]."
				  value=""></p>
				<p><label for="common" 
				 title="Example: field mushroom.">Common Name:</label> 
				 <input class="texts" id="common" name="common" type="text" maxlength="50" 
				  title="Enter a common name [can use more than one word]." 
				  value=""></p>
				<p><input class="actions" id="submit" name="submit" type="submit" 
				 value="Go" title="Perform Search."></p>
			   </fieldset>
			  </form>
			 </div>
			  
			 <p class="large">
			  <a href="/search.html" 
			   title="Search by Location, Collector, and more.">Go to Advanced Search</a>
			 </p>

			<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>
		</div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>