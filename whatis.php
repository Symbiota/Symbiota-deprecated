<?php
//error_reporting(E_ALL);
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>vPlants | What is an Herbarium?</title>
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

            <div style="margin:20px;">
            	<table width="750" height="350" border="0" cellpadding="0" cellspacing="0">
					<tr> 
					  
					<td width="90" valign="top" class="bgdkgr"><img src="<?php echo $clientRoot; ?>/images.vplants/img/spacer.gif" width="90" height="1" alt=""></td>
					  
					  
					  <td valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
						<tr> 
						  <td width="1%"><img src="<?php echo $clientRoot; ?>/images.vplants/img/spacer.gif" width="20" height="20" alt=""></td>
						  <td width="99%">&nbsp;</td>
						</tr>
						<tr> 
						  <td width="1%">&nbsp;</td>
						  <td width="99%"><h1>What is an Herbarium?</h1></td>
						</tr>
						<tr> 
						  <td width="1%">&nbsp;</td>
						  <td width="99%" valign="top" id="disclaimer"><table width="100%" border="0" cellspacing="0" cellpadding="0">
							  <tr> 
								<td width="49%" valign="top"> <table width="100%" border="0" cellspacing="0" cellpadding="0">
									<tr>
									  <td><p>An herbarium houses a collection of pressed, dried 
										  plants. Each herbarium specimen contains actual plant 
										  material as well as label information detailing attributes 
										  of the specimen such as the collector/s, date of collection, 
										  and collection site details (e.g., geopolitical location, 
										  GPS coordinates, locality).</p>
										<p>An herbarium records the past, providing users with 
										  documented occurrences of plants in specific locations 
										  over time. Users of a traditional herbarium include 
										  the following:</p>
										</td>
									</tr>
								  </table>
								  <table width="100%" border="0" cellspacing="0" cellpadding="0">
									<tr> 
									  <td width="1%">&nbsp;</td>
									  <td width="1%">&nbsp;</td>
									  <td width="98%">&nbsp;</td>
									</tr>
									<tr> 
									  <td colspan="3" class="bgmdgr"><img src="<?php echo $clientRoot; ?>/images.vplants/img/spacer.gif" width="1" height="1" alt=""></td>
									</tr>
									<tr> 
									  <td width="1%" class="text"><strong>Users</strong></td>
									  <td width="1%" class="text"><img src="<?php echo $clientRoot; ?>/images.vplants/img/spacer.gif" width="10" height="1" alt=""></td>
									  <td width="98%" class="text"><strong>Usage Examples</strong></td>
									</tr>
									<tr> 
									  <td colspan="3" class="bgmdgr"><img src="<?php echo $clientRoot; ?>/images.vplants/img/spacer.gif" width="1" height="1" alt=""></td>
									</tr>
									<tr> 
									  <td colspan="3"><img src="<?php echo $clientRoot; ?>/images.vplants/img/spacer.gif" width="1" height="4" alt=""></td>
									</tr>
									<tr> 
									  <td width="1%" valign="top" class="text">Taxonomists</td>
									  <td width="1%" class="text">&nbsp;</td>
									  <td width="98%" class="text"><table width="100%" border="0" cellspacing="0" cellpadding="0">
										  <tr> 
											<td valign="top" class="bullet">&#8226;&nbsp;</td>
											<td valign="top" class="text">Identify and validate 
											  specimen data </td>
										  </tr>
										  <tr> 
											<td valign="top" class="bullet">&#8226;&nbsp;</td>
											<td valign="top" class="text">Annotate the scientific 
											  names used to describe the specimen </td>
										  </tr>
										  <tr> 
											<td valign="top" class="bullet">&#8226;&nbsp;</td>
											<td valign="top" class="text">Conduct molecular genetics 
											  studies</td>
										  </tr>
										  <tr> 
											<td valign="top" class="bullet">&#8226;&nbsp;</td>
											<td class="text">Compile regional floras or keys</td>
										  </tr>
										</table></td>
									</tr>
									<tr> 
									  <td colspan="3"><img src="<?php echo $clientRoot; ?>/images.vplants/img/spacer.gif" width="1" height="4" alt=""></td>
									</tr>
									 <tr> 
									  <td colspan="3" class="bgmdgr"><img src="<?php echo $clientRoot; ?>/images.vplants/img/spacer.gif" width="1" height="1" alt=""></td>
									</tr>
									<tr> 
									  <td colspan="3"><img src="<?php echo $clientRoot; ?>/images.vplants/img/spacer.gif" width="1" height="4" alt=""></td>
									</tr>
									<tr> 
									  <td width="1%" valign="top" class="text">Conservation Scientists</td>
									  <td width="1%">&nbsp;</td>
									  <td width="98%" valign="top"> <table width="100%" border="0" cellspacing="0" cellpadding="0">
										  <tr> 
											<td valign="top" class="bullet">&#8226;&nbsp;</td>
											<td valign="top" class="text">Perform research </td>
										  </tr>
										  <tr> 
											<td valign="top" class="bullet">&#8226;&nbsp;</td>
											<td valign="top" class="text">Sample plant material 
											  for chemical or genetic analyses </td>
										  </tr>
										</table>
									  </td>
									</tr>
									<tr> 
									  <td colspan="3"><img src="<?php echo $clientRoot; ?>/images.vplants/img/spacer.gif" width="1" height="4" alt=""></td>
									</tr>
									 <tr> 
									  <td colspan="3" class="bgmdgr"><img src="<?php echo $clientRoot; ?>/images.vplants/img/spacer.gif" width="1" height="1" alt=""></td>
									</tr>
									<tr> 
									  <td colspan="3"><img src="<?php echo $clientRoot; ?>/images.vplants/img/spacer.gif" width="1" height="4" alt=""></td>
									</tr>
									<tr>
									  <td valign="top" class="text">Conservation Stewards, Students, and Educators</td>
									  <td>&nbsp;</td>
									  <td><table width="100%" border="0" cellspacing="0" cellpadding="0">
										  <tr> 
											<td valign="top" class="bullet">&#8226;&nbsp;</td>
											<td valign="top" class="text">Interpret the past for 
											  guidance in restoration projects </td>
										  </tr>
										  <tr> 
											<td valign="top" class="bullet">&#8226;&nbsp;</td>
											<td valign="top" class="text">Learn characteristics 
											  of native and cultivated plants </td>
										  </tr>
										  <tr> 
											<td valign="top" class="bullet">&#8226;&nbsp;</td>
											<td valign="top" class="text">Foster future botanists 
											</td>
										  </tr>
										</table></td>
									</tr>
								  </table></td>
								<td width="2%"><img src="<?php echo $clientRoot; ?>/images.vplants/img/spacer.gif" width="30" height="1" alt=""></td>
								<td width="49%" valign="top"><table width="100%" border="0" cellspacing="0" cellpadding="0">
									<tr> 
									  <td><img src="<?php echo $clientRoot; ?>/images.vplants/img/figure1.jpg" width="300" height="450" alt=""></td>
									</tr>
									<tr> 
									  <td><img src="<?php echo $clientRoot; ?>/images.vplants/img/spacer.gif" width="1" height="4" alt=""></td>
									</tr>
									<tr> 
									  <td class="text"><strong>Figure 1: </strong>Herbarium Sheet 
										- Veronica longifolia L.;<br>
										Image from the Morton Arboretum Herbarium, Accession #26486.</td>
									</tr>
								  </table>
								  </td>
							  </tr>
							</table></td>
						</tr>
					  </table></td>
					</tr>
					<tr>
					  <td valign="top" class="bgdkgr">&nbsp;</td>
					  
					<td align="center" valign="top" class="footer"> 
				<!--	
					  <a href="index.html" class="footlink">Home</a> 
					  | <a href="about_partners.html" class="footlink">About Us</a> | <a href="whatis.html" class="footlink">What's 
					  An Herbarium</a> | <a href="browse_genus.html" class="footlink">Browse Plant 
					  List</a> | <a href="search.html" class="footlink">Search</a>
				-->	  
					</td>
					</tr>
				</table>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>