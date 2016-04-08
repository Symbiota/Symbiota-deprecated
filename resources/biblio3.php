<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle?>vPlants - Fungus References</title>
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
            <h1>Fungus References</h1>

            <div style="margin:20px;">
            	<p>
				For information on the identification of fungi, refer to mushroom guides or better yet, attend meetings of a local mushroom club.  Always keep in mind that there are many species of fungi that are not found in the popular field guides.  This is one reason for utilizing more than one reference book.  Some species can only be accurately determined by means of microscopic characters.  There are, however, many common fungi that can be readily identified by careful comparison with BOTH the descriptions and illustrations of a good mushroom book.
				</p>
				

				<h2>Recommended mushroom books for the Upper Midwest</h2>

				<p>It is very helpful to use more than one reference so that descriptions and illustrations can be compared.  Don't just look at pictures. The following books are grouped by coverage but all of these are very useful for the Upper Midwest.
				</p>


				<h3>Edible Mushrooms</h3>
				<dl>

				<dt>Kuo, Michael. 2007.</dt>
				<dd><i>100 Edible Mushrooms.</i> University of Michigan Press.</dd>

				<dt>Kuo, Michael. 2005</dt>
				<dd><i>Morels.</i> University of Michigan Press.</dd>

				<dt>McFarland, Joe, and Gregory M. Mueller. 2009.</dt>
				<dd><i>Edible Wild Mushrooms of Illinois and Surrounding States: A Field-to-Kitchen Guide</i> 
				1st Edition, University of Illinois Press.</dd>
				</dl>
				 
				<h3>Upper Midwest</h3>
				<dl>

				<dt>Huffman, Donald M., Lois H. Tiffany, George Knaphaus, Rosanne A. Healy. 2008.</dt>
				<dd><i>Mushrooms and Other Fungi of the Midcontinental United States (Bur Oak Guide).</i>
				2nd edition. University Of Iowa Press.</dd>
				 
				<dt>Huffman, Donald M., and Lois H. Tiffany. 2004.</dt>
				<dd><i>Mushrooms in Your Pocket: A Guide to the Mushrooms of Iowa (Bur Oak Guide).</i> University Of Iowa Press. [43 species, folded map]</dd>

				</dl>
				<h3>Northeastern North America</h3>
				<dl>

				<dt>Barron, George.  1999.</dt>
				<dd><i>Mushrooms of Northeast North America: Midwest to New England.</i>  Lone Pine Publ., Edmonton, Alberta, Canada.</dd>

				<dt>Bessette, Alan E., Arleen R. Bessette, and David W. Fischer.  1997.</dt>
				<dd><i>Mushrooms of Northeastern North America.</i>  Syracuse University Press, New York.</dd>

				<dt>Binion, Denise, Steve Stephenson, William Roody, Harold H. Burdsall, Orson K. Miller, Larissa Vasilyeva. 2008</dt>
				<dd><i>Macrofungi Associated with Oaks of Eastern North America.</i> West Virginia Univ Press</dd>

				</dl>
				<h3>North America</h3>
				<dl>

				<dt>Arora, David.  1986.</dt>
				<dd><i>Mushrooms Demystified.</i>  Second Edition.  Ten Speed Press, Berkeley, California.</dd>

				<dt>Lincoff, Gary H.  1981.</dt>
				<dd><i>The National Audubon Society Field Guide to North American Mushrooms.</i>   Alfred A. Knopf, New York.</dd>

				<dt>Miller, Orson K. Jr. and Hope Miller. 2006.</dt>
				<dd><i>North American Mushrooms: A Field Guide to Edible and Inedible Fungi.</i>  Falconguide, Globe Pequot Press.</dd>

				<dt>Phillips, Roger. 2005.</dt>
				<dd><i>Mushrooms and Other Fungi of North America.</i>  Revised and Updated edition. Firefly Books Ltd.</dd>

				</dl>
            </div>
        </div>
		
		<div id="content2">

			<img src="<?php echo $clientRoot; ?>/images/vplants/feature/ARMITABE.po.jpg" width="250" height="336" alt="Ringless Honey Mushroom" title="Armillaria tabescens">

			<div class="box imgtext">
			<p>The ringless honey mushroom <i>Armillaria tabescens</i>, is a root parasite.

			</p>
			</div>

			<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="../disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

		</div><!-- end of #content2 -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>