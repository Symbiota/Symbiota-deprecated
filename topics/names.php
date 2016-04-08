<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle?>vPlants - Topics - Names</title>
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
        <div  id="innertext">
            <h1>Names</h1>

            <div style="margin:20px;">
            	<h1>Names</h1>
				<p>What's in a name? It is human nature to name things. We use names to communicate information and assign an identity to people and objects. For plants, fungi, and other organisms there are several kinds of names:
				<dl><dt>Taxon</dt><dd> (plural: taxa) is a general term meaning a group or rank, such as a species, a variety, a genus, a family, etc. </dd>
				<dt><a href="names2.php">Scientific names</a></dt><dd> are official names that follow rules of taxonomy to uniquely identify a taxon, such as a species or family. Example: <i>Ulmus americana</i> and <i>Ulmaceae</i>.</dd>
				<dt><a href="names3.php">Synonyms</a></dt><dd> are different names for the same taxon. Example: <i>Ulmus floridana</i> is a synonym of <i>Ulmus americana</i>.</dd>
				<dt><a href="names4.php">Common names</a></dt><dd> are nicknames that vary between regions and languages. They follow no rules and often are not unique. Example: American elm.</dd></dl></p>


				<h2>Taxonomy</h2>

				<p>Taxonomy is the classification of organisms into categories based on shared characteristics. It is closely tied to nomenclature, the practice of naming things.  Scientific names for plants and fungi follow the rules of the International Code of Botanical Nomenclature. For animals, the International Code of Zoological Nomenclature is used. There is also the International Code of Nomenclature of Bacteria. When a new species or organism is discovered, it is named based upon the rules of these codes.  Because these two sets of rules are independent there are occasionally the same names used for both animals and plants or fungi. For example, <i>Lactarius</i> is a genus of milk mushrooms, but <i>Lactarius</i> is also an economically important milkfish in the Indian Ocean.</p>
            </div>
        </div>
		
		<div id="content2">

			<div class="box document">
			<h3>....</h3>
			<ul><li>
			....
			</li></ul>
			</div>

			<div class="box external">
			<h3>Related Web Sites</h3>
			<ul>
			<li><a href="http://www.bgbm.fu-berlin.de/iapt/nomenclature/code/SaintLouis/0000St.Luistitle.htm">International Code of Botanical Nomenclature</a></li>
			<li><a href="http://www.iczn.org/iczn/index.jsp">International Code of Zoological Nomenclature</a></li>
			</ul>
			</div>

			<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="../disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

		</div><!-- end of #content2 -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>