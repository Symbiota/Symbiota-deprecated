<?php
//error_reporting(E_ALL);
include_once("../../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants - Guide to Paxillus</title>
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
            <h1>Guide to Paxillus</h1>

            <div style="margin:20px;">
            	<div class="floatimg"></div>

				<p class="small">This guide applies to the Chicago Region and is not complete for other regions. <span class="noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></span></p>


				<p>To date we only have <i>Paxillus involutus</i> recorded for the Chicago area. <i>Paxillus atrotomentosus</i> is a brown velvety species associated with conifer wood. <i>Paxillus vernalis</i> is a look-alike to <i>P. involutus</i> that is found with aspen.  Other species of <i>Paxillus</i> have a short lateral stem or lack a stem and grow on wood; the underside may be wrinkled ridges instead of gills.</p>



				<table class="key" cellpadding="3" cellspacing="0" border="0">
				<caption>Key to Species</caption>
				<thead>
				<tr ><th colspan="3">Key Choice</th><th >Go to</th></tr>
				</thead>
				<tbody>

				<tr class="keychoice">
				<td id="k1">1a. Cap margin strongly inrolled. Stem central to off-center. Found on the ground with various trees including oak. May also be found in bogs with spruce.</td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ><a href="/fungi/species/species.jsp?gid=8282"><i class="genus">Paxillus</i> <i class="epithet">involutus</i></a></td>
				</tr><tr >
				<td ></td>
				<td ><!-- image --></td>
				<td ><!-- image --></td>
				<td ></td>
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