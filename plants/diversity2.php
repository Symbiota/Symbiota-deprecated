<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> vPlants - Plant Diversity continued</title>
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
            <h1>Plant Diversity Table 2 and Table 3</h1>

            <div style="margin:20px;">
            	<p>
				<a id="table2" name="table2"></a>
				<table cellpadding="3" cellspacing="0" border="1">
				<caption>Table 2. Chicago Region flora compared to other temperate areas.</caption>
				<thead><tr ><th >Locale (ordered by increasing size)</th>
				  <th >Area (square km)</th>
				  <th >% difference in area to the Chicago Region</th>
				  <th >approximate number of vascular plant species</th>
				  <th >% difference in number of species to the Chicago Region</th>
				  <th >species diversity rating (species per square kilometer times 100)</th></tr>
				</thead>
				<tbody class="hover">
				<tr class="highlight"><td>Chicago Region</td>
					<td align="right">30,557</td>
					<td align="right">--</td>
					<td align="right">2700</td>
					<td align="right">--</td>
					<td align="right">8.8</td></tr>
				<tr><td>Belgium</td>
					<td align="right">30,510</td>
					<td align="right">- 0.2 %</td>
					<td align="right">1400</td>
					<td align="right">- 48 %</td>
					<td align="right">4.6</td></tr>
				<tr class="odd"><td>Netherlands</td>
					<td align="right">41,526</td>
					<td align="right">+ 36 %</td>
					<td align="right">914</td>
					<td align="right">- 66 %</td>
					<td align="right">2.2</td></tr>
				<tr><td>Denmark</td>
					<td align="right">43,094</td>
					<td align="right">+ 41 %</td>
					<td align="right">1000</td>
					<td align="right">- 63 %</td>
					<td align="right">2.3</td></tr>
				<tr class="odd"><td>West Virginia, USA</td>
					<td align="right">62,361</td>
					<td align="right">+ 104 %</td>
					<td align="right">2344</td>
					<td align="right">- 8.4 %</td>
					<td align="right">4.0</td></tr>
				<tr><td>Lithuania</td>
					<td align="right">65,200</td>
					<td align="right">+ 113 %</td>
					<td align="right">1350</td>
					<td align="right">- 50 %</td>
					<td align="right">2.1</td></tr>
				<tr class="odd"><td>Ireland</td>
					<td align="right">70,280</td>
					<td align="right">+ 130 %</td>
					<td align="right">950</td>
					<td align="right">- 65 %</td>
					<td align="right">1.4</td></tr>
				<tr><td>Portugal</td>
					<td align="right">92,391</td>
					<td align="right">+ 202 %</td>
					<td align="right">3800</td>
					<td align="right">+ 41 %</td>
					<td align="right">4.1</td></tr>
				<tr class="odd"><td>Indiana, USA</td>
					<td align="right">92,895</td>
					<td align="right">+ 204 %</td>
					<td align="right">1500</td>
					<td align="right">- 44 %</td>
					<td align="right">1.6</td></tr>
				<tr><td>Hungary</td>
					<td align="right">93,030</td>
					<td align="right">+ 204 %</td>
					<td align="right">2450</td>
					<td align="right">- 9 %</td>
					<td align="right">2.6</td></tr>
				<tr class="odd"><td>Wisconsin, USA</td>
					<td align="right">140,663</td>
					<td align="right">+ 360 %</td>
					<td align="right">2640</td>
					<td align="right">- 22 %</td>
					<td align="right">1.9</td></tr>
				<tr><td>Illinois, USA</td>
					<td align="right">143,962</td>
					<td align="right">+ 371 %</td>
					<td align="right">3000</td>
					<td align="right">+ 11 %</td>
					<td align="right">2.1</td></tr>
				<tr class="odd"><td>Michigan, USA</td>
					<td align="right">147,122</td>
					<td align="right">+ 381 %</td>
					<td align="right">2800</td>
					<td align="right">+ 4 %</td>
					<td align="right">1.9</td></tr>
				<tr><td>Italy</td>
					<td align="right">301,230</td>
					<td align="right">+ 886 %</td>
					<td align="right">5599</td>
					<td align="right">+ 107 %</td>
					<td align="right">1.9</td></tr>
				<tr class="odd"><td>France</td>
					<td align="right">547,030</td>
					<td align="right">+ 1690 %</td>
					<td align="right">4630</td>
					<td align="right">+ 71 %</td>
					<td align="right">0.8</td></tr>
				</tbody>
				</table>
				</p>
				<p>
				<a id="table3" name="table3"></a>
				<table cellpadding="3" cellspacing="0" border="1">
				<caption>Table 3. Chicago Region flora compared to tropical areas.</caption>
				<thead><tr ><th >Locale (ordered by increasing size)</th>
				  <th >Area (square km)</th>
				  <th >% difference in area to the Chicago Region</th>
				  <th >approximate number of vascular plant species</th>
				  <th >% difference in number of species to the Chicago Region</th>
				  <th >species diversity rating (species per square kilometer times 100)</th></tr>
				</thead>
				<tbody class="hover">
				<tr class="highlight"><td>Chicago Region</td>
					<td align="right">30,557</td>
					<td align="right">0 %</td>
					<td align="right">2700</td>
					<td align="right">0 %</td>
					<td align="right">8.8</td></tr>
				<tr><td>Belize</td>
					<td align="right">22,966</td>
					<td align="right">- 25 %</td>
					<td align="right">3408</td>
					<td align="right">+ 26 %</td>
					<td align="right">14.8</td></tr>
				<tr class="odd"><td>Costa Rica</td>
					<td align="right">51,100</td>
					<td align="right">+ 67 %</td>
					<td align="right">9000</td>
					<td align="right">+ 233 %</td>
					<td align="right">17.6</td></tr>
				<tr><td>Ecuador</td>
					<td align="right">283,560</td>
					<td align="right">+ 828 %</td>
					<td align="right">25,000</td>
					<td align="right">+ 826 %</td>
					<td align="right">8.8</td></tr>
				</tbody>
				</table>
				</p>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>