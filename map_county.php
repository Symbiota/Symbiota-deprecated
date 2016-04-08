<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle?>vPlants - County Map</title>
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
        <div  id="innertext">
            <h1>County Map for Chicago Region</h1>

            <div style="margin:20px;">
            	 <p>Map of the 24 counties included in the Chicago Region for vPlants. Please be aware when searching records that there is a Lake County in Illinois as well as a Lake County in Indiana.</p>
 
				 <img class="floatleft" src="<?php echo $clientRoot; ?>/images/vplants/img/map_vplants.gif" width="490" height="484" alt="Map of the vPlants Chicago Region showing counties included">


				 <div class="floatleft">
				  <h3>Illinois</h3>
				  <ul>
				   <li>Boone</li>
				   <li>Cook</li>
				   <li>DeKalb</li>
				   <li>DuPage</li>
				   <li>Grundy</li>
				   <li>Kane</li>
				   <li>Kankakee</li>
				   <li>Kendall</li>
				   <li>Lake</li>
				   <li>McHenry</li>
				   <li>Will</li>
				  </ul>
				  <h3>Indiana</h3>
				  <ul>
				   <li>Jasper</li>
				   <li>Lake</li>
				   <li>LaPorte</li>
				   <li>Newton</li>
				   <li>Porter</li>
				   <li>Starke</li>
				   <li>St. Joseph</li>
				  </ul>
				  <h3>Michigan</h3>
				  <ul>
				   <li>Berrien</li>
				  </ul>
				  <h3>Wisconsin</h3>
				  <ul>
				   <li>Kenosha</li>
				   <li>Milwaukee</li>
				   <li>Racine</li>
				   <li>Walworth</li>
				   <li>Waukesha</li>
				  </ul>
				 </div>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>