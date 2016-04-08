<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Plant References</title>
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
            <h1>Plant References Commonly Used for vPlants</h1>

            <div style="margin:20px;">
            	<p>
				This is a partial list of published bibliographic references that are commonly used and cited on the vPlants website.  Often, much of the specific information on the Species Description pages has been written with the aid of these sources.  Less commonly used, or other specific sources, are cited on the individual Species Description pages.
				</p>

				<dl>

				<dt>Deam, C. C. 1940.</dt>
				<dd><em>Flora of Indiana.</em> Indianapolis: Department of Conservation, Division of Forestry.</dd>

				<dt>Fernald, M. L. 1950.</dt>
				<dd><em>Grays manual of botany: A handbook of the flowering plants and ferns of the central and northeastern United States and adjacent Canada.</em> 8th ed. New York: American Book Company.</dd>

				<dt>Gleason, H. A. and A. Cronquist. 1991.</dt>
				<dd><em>Manual of vascular plants of northeastern United States and adjacent Canada.</em> 2nd ed. New York: The New York Botanical Garden.</dd>

				<dt>Mohlenbrock, R. H. 1986.</dt>
				<dd><em>Guide to the vascular flora of Illinois.</em> revised and enlarged ed. Carbondale, IL: Southern Illinois University Press.</dd>

				<dt>Swink, F. and G. Wilhelm. 1994.</dt>
				<dd><em>Plants of the Chicago region.</em> 4th ed. Indianapolis: Indiana Academy of Science.</dd>

				<dt>Voss, E. G. 1972 - 1996.</dt>
				<dd><em>Michigan flora: A guide to the identification and occurrence of the native and naturalized seed-plants of the state.</em> 3 vols. Bloomfield Hills, MI: Cranbrook Institute of Science.</dd>

				</dl>

				<p>&nbsp;</p>
				<p>&nbsp;</p>
				<p>&nbsp;</p>
            </div>
        </div>
		
		<div id="content2"><!-- start of side content -->
			<p class="hide">
			<a id="secondary" name="secondary"></a>
			<a href="#sitemenu">Skip to site menu.</a>
			</p>

			<!-- image width is 250 pixels -->
			<div class="box">

			<p>Natural science does not simply 
			describe and explain nature, 
			it is part of the interplay 
			between nature and ourselves.<br /><br />
			   &#151;Werner Karl Heisenberg</p>
			</div>


			<p class="small">
			Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p>
			<p class="small">
			<a class="popup" href="/disclaimer.html" 
			title="Read Disclaimer [opens new window]." 
			onclick="window.open(this.href, 'disclaimer', 
			'width=500,height=350,resizable,top=100,left=100');
			return false;" 
			onkeypress="window.open(this.href, 'disclaimer', 
			'width=500,height=350,resizable,top=100,left=100');
			return false;">Disclaimer</a>
			</p>

		</div><!-- end of #content2 -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>