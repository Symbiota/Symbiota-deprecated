<?php
 header("Content-Type: text/html; charset=ISO-8859-1");
 include_once("../../util/symbini.php");
 ?>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
    <title><?php echo $defaultTitle; ?> - Collections Search Maps</title>
    <link rel="stylesheet" href="../../css/main.css" type="text/css">
</head>
<body>

<?php
	$displayLeftMenu = (isset($collections_maps_indexMenu)?$collections_maps_indexMenu:"true");
	include($serverRoot."/util/header.php");
	if(isset($collections_maps_indexCrumbs)){
		echo "<div class='navpath'>";
		echo "<a href='../index.php'>Home</a> &gt; ";
		echo $collections_maps_indexCrumbs;
		echo " &gt; <b>Mapping Options</b>"; 
		echo "</div>";
	}
?>
	<!-- This is inner text! -->
    <div id="innertext">
		<div id="tabdiv">
			<div class='backendleft'>&nbsp;</div>
			<div class='backtab'><a href='../checklist.php'>Checklist</a></div>
			<div class="midleft">&nbsp;</div>
			<div class='backtab'><a href='../list.php'>List</a></div>
			<div class="midleft" style='border-bottom:0px;height:100%;'>&nbsp;</div>
			<div class='fronttab'>Maps</div>
			<div class='backendright' style='border-bottom:0px;'>&nbsp;</div>
		</div>

	    <div class="button" style="margin-top:20px;float:right;width:13px;height:13px;" title="Download Coordinate Data">
			<a href="../download/downloadhandler.php?dltype=georef"><img src="../../images/dl.png"/></a>
        </div>
        <div style='margin-top:10px;'>
        	<h2>Google Map</h2>
        </div>
		<div style='margin:10 0 0 20;'>
		    <a href='javascript:var popupReference=window.open("googlemap.php<?php echo (array_key_exists("clid",$_REQUEST)?"?clid=".$_REQUEST["clid"]:"");?>","gmap","toolbar=0,location=0,directories=0,status=0,menubar=0,scrollbars=1,resizable=1,width=950,height=700,left=20,top=20");'>
		        Display coordinates in Google Map
		    </a>
		</div>
		<div style='margin:10 0 0 20;'>Google Maps is a free web mapping service application and technology provided by Google that features a 
		    map that users can pan (by dragging the mouse) and zoom (by using the mouse wheel). Collection points are 
		    displayed as colored markers that when clicked on, displays the full imformation for that collection. When 
		    multiple species are queried (separated by semi-colons in the Taxon Criteria search box), 
		    different colored markers denote each individual species. Note that the Google Map has a limit to the first 1000 georeferenced specimens for each taxon.
		</div>

		<div style='margin-top:10px;'>
		    <h2>Google Earth (KML)</h2>
		</div>
		<div style='margin:10 0 0 20;'>
		    <a href="googlekml.php" target="_blank">
		        Display coordinates in Google Earth 
		    </a>
		</div>
		<div style='margin:10 0 0 20;'>
		    This link creates an KML file that can be opened in the Google Earth mapping application.
		    Note that you must have <a href='http://earth.google.com/' target="_blank">Google Earth</a> installed on your computer to make use of this option.
		</div>

    </div>

	<?php
	include($serverRoot."/util/footer.php");
	?>

	<script type="text/javascript">
		var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
		document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
		try {
			var pageTracker = _gat._getTracker("<?php echo $googleAnalyticsKey; ?>");
			pageTracker._trackPageview();
		} catch(err) {}
	</script>
</body>

</html>
