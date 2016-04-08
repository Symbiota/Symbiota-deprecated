<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> About Us - vPlants Partnership</title>
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
		<!-- start of inner text and right side content -->
		<div  id="innervplantstext">
			<div id="bodywrap">
				<div id="wrapper1"><!-- for navigation and content -->

					<!-- PAGE CONTENT STARTS -->

					<div id="content1wrap"><!--  for content1 only -->

					<div id="content1"><!-- start of primary content --><a id="pagecontent" name="pagecontent"></a>
					<h1>vPlants Partnership</h1>
					<div style="margin:20px;">
				
						<div class="indexheading"><a href="http://www.mortonarb.org/">The Morton Arboretum</a></div>
						<div class="indexdescription"> The Morton Arboretum, a 1,700-acre botanical garden of trees and other plants, displays more than 3,300 kinds of plants from throughout the north temperate zone. These living collections are combined with 700 acres of oak woodland, reconstructed prairie, rare species habitat, and wetlands, presenting a showcase of horticultural and native plant diversity. The Arboretum and its staff are actively involved in regional, national and international conservation efforts.
						</div>
						<p>The Morton Arboretum, <br>
						  4100 IL Route 53, <br>
						  Lisle, IL   60532-4293, <br>
						  (630) 968-0074, <br>
						  www.mortonarb.org
						</p>
						
						<div class="indexheading"><a href="http://www.fieldmuseum.org/">The Field Museum</a></div>
						<div class="indexdescription">Using collections-based research and self-directed learning through exhibits and education programs, The Field Museum promotes greater public understanding and appreciation of the world in which we live. The Museum's expanding programs on the region's biological diversity help integrate natural riches into everyday life and culture. Regional inventory and population monitoring programs focus on species of conservation concern, or those that serve as sensitive indicators of the health of an ecological community.
						</div>
						<p>
						  The Field Museum of Natural History, <br>
						  1400 S. Lake Shore Drive, <br>
						  Chicago, IL   60605, <br>
						  (312) 922-9410, <br>
						  www.fieldmuseum.org 
						</p>
						
						<div class="indexheading"><a href="http://www.chicagobotanic.org/">Chicago Botanic Garden</a></div>
						<div class="indexdescription">Since its founding more than 30 years ago, the Chicago Botanic Garden has become a world-class cultural landmark. Owned by the Forest Preserve District of Cook County and managed by the Chicago Horticultural Society, the Garden spans 385 acres, features 23 garden areas, and serves over 700,000 visitors each year. The Garden's Skokie River restoration project is a permanent study site for streambank stabilization techniques, and Mary Mix McDonald Woods, a wet savanna and open oak woodland, is a nearly 100-acre restoration management project.
						</div>
						<p>
						  Chicago Botanic Garden, <br>
						  1000 Lake-Cook Road, <br>
						  P.O. Box 400, <br>
						  Glencoe, IL   60022, <br>
						  (847) 835-5440, <br>
						  www.chicagobotanic.org
						</p>
						
						<div class="indexheading"><a href="http://www.chicagowilderness.org">Chicago Wilderness</a></div>
						<div class="indexdescription">The Chicago Wilderness consortium is an unprecedented alliance of more than 200 public and private organizations that have joined forces to protect, restore and manage the region's natural lands and the plants and animals that inhabit them.
						</div>
						
						<div class="indexheading"><a href="http://www.swbiodiversity.org">Southwest Environmental Information Network (SEINet)</a></div>
						<div class="indexdescription">SEINet was created to distribute data of interest to the environmental community of Arizona. It has grown to become a provider of a suite of data-access technologies and a distributed network of collections, museums and agencies across much of the United States.
						</div>
						
						<div class="indexheading"><a href="http://imls.gov">The Institute of Museum and Library Services (IMLS)</a></div>
						<div class="indexdescription">The Institute of Museum and Library Services is the primary source of federal support for the nation’s 122,000 libraries and 17,500 museums. The Institute's mission is to create strong libraries and museums that connect people to information and ideas. The Institute works at the national level and in coordination with state and local organizations to sustain heritage, culture, and knowledge; enhance learning and innovation; and support professional development.</div>
					
					</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
					
					<!-- start of side content -->
					<div id="content2">
						<!-- any image width should be 250 pixels -->
				
						<!--image here?-->
							
						<div class="box">
						 <h3></h3>
						 <p>
						  ...
						 </p>
						<ul><li>

						</li></ul>
						</div>

						<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

					</div>
				</div><!-- end of #wrapper1 -->
			</div><!-- end of #bodywrap -->
		</div><!-- end of #innervplantstext -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>