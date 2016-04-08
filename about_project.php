<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants | Project Resources</title>
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
            <h1>Project Resources</h1>

            <div style="margin:20px;">
            	<table width="720" height="350" border="0" cellpadding="0" cellspacing="0">
					<tr> 
					  <td width="90" valign="top" class="bgdkgr"><img src="img/spacer.gif" width="90" height="1" alt=""></td>
					  <td width="20" valign="top"><img src="img/spacer.gif" width="20" height="1" alt=""></td>
					  <td width="610" valign="top"><table width="450" border="0" cellpadding="0" cellspacing="0">
						  <tr> 
							<td>&nbsp;</td>
						  </tr>
						  <tr> 
							<td><h1>Project Resources</h1></td>
						  </tr>
						</table>
						<table width="100%" border="0">
						<tr>
						<td width="5%"></td>
							<td> 
							<b>vPlants II</b> 
							<br><br>
							  <H2><a href="pr/species/index.htm">Species Page Prototypes</a></h2>
							  <font size="1">[updated: 27-May-2004]</font>
							<br>
							<br>
							  <H2><a href="pr/speciesPages.htm">Links of Interest</a></h2>
							  <font size="1">[updated: 2-Feb-2004]</font>
							<br>
							<br>
							<b>vPlants I</b><br>
							<br>
							  <H2><a href="/xsql/plants/stats.xsql">Data Statistics</a></H2>
							  <font size="1">[updated: 24-Aug-2004]</font>
							  <br>
							  <br>
							  <H2><a href="pr/gallery/gallery.htm">Example Image Gallery</a></H2>
							  <font size="1">[updated: 13-May-2002]</font>
							  <br>
							  <br>
							  <H2><a href="pr/checklist.htm">Scientific Name Checklist</a></H2>
							  <font size="1">[updated: 17-Oct-2003]</font>
							  <br>
							  <br>
							  <H2><a href="pr/collectors.htm">Common Collector Names</a></H2>
							  <font size="1">[updated: 13-May-2002]</font>
							  <br>
							  <br>
							  <H2><a href="pr/metadata/metadata.htm"> Metadata Standards Page</a></H2>
							  <font size="1">[updated: 13-May-2002]</font>
							  <br>
							  </td>
						</tr>
						</table>
						</td>
					</tr>
					<tr>
					  <td valign="top" class="bgdkgr">&nbsp;</td>
					  <td valign="top">&nbsp;</td>
					  <td align="center" class="footer">
				<!--	  
					  <a href="index.html" class="footlink">Home</a> | <a href="about_partners.html" class="footlink">About 
						Us</a> | <a href="whatis.html" class="footlink">What's An Herbarium</a> | <a href="browse_genus.html" class="footlink">Browse 
						Plant List</a> | <a href="search.html" class="footlink">Search</a></td>
				-->		
					</tr>
				</table>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>