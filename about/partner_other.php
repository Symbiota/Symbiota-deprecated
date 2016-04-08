<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Additional Partners and Affiliates</title>
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
            <h1>Additional Partners and Affiliates</h1>
			<div style="margin:20px;">
            	 <h3><a href="http://www.chias.org/">Chicago Academy of Sciences [Notebaert Nature Museum]</a></h3>
				 <p>
				  Founded in 1857, the Chicago Academy of Sciences has natural history collections and archives extending back to the 1840s.
				  The academy's new home in Lincoln Park is the Peggy Notebaert Nature Museum, which opened in 1999.  
				  [<a href="http://www.encyclopedia.chicagohistory.org/pages/237.html" title="external link">see history at Encyclopedia of Chicago (external link)</a>] www.chias.org
				 </p>
				 <p>The mission of the Peggy Notebaert Nature Museum is to expand the public's knowledge of nature and environmental science to promote greater understanding of Midwestern environmental issues and how those issues relate to the rest of the world. 
				 </p>
				 <p>Notebaert Nature Museum,  
				  2430 N. Cannon Drive,  
				  Chicago, IL   60614,  
				  (773) 755-5100,  
				  www.naturemuseum.org
				 </p>

				 <h3><a href="http://www.inhs.uiuc.edu">Illinois Natural History Survey</a></h3>
				<p>
				Illinois Natural History Survey scientists study the organisms of Illinois and how they interact with the variety of ecosystems found in the state. Through its research and education programs, the Survey fosters responsible management and appreciation of the state's biological resources. The Survey's collections of plant and animal specimens are among the largest and oldest in North America and are used by researchers from all over the world.
				</p>
				 <p> Illinois Natural History Survey, 
				  1816 S Oak Street, 
				  Champaign, IL   61820, 
				  (217) 333-6880, 
				  www.inhs.uiuc.edu
				</p>
				 
				<h2>Major funding provided by</h2>
				 <h3><a href="http://www.imls.gov">Institute of Museum and Library Services</a></h3>
				 <p>The Institute of Museum and Library Services is the primary source of federal support for the nation’s 122,000 libraries and 17,500 museums. The Institute's mission is to create strong libraries and museums that connect people to information and ideas. The Institute works at the national level and in coordination with state and local organizations to sustain heritage, culture, and knowledge; enhance learning and innovation; and support professional development.
				  www.imls.gov
				 </p>

				 <h2>Additional support provided by</h2>
				 <h3><a href="http://www.chicagowilderness.org/coalition/index.cfm">The Chicago Wilderness consortium</a></h3>
				 <p>The Chicago Wilderness consortium is an unprecedented alliance of more than 200 public and private organizations that have joined forces to protect, restore and manage the region's natural lands and the plants and animals that inhabit them.
				  www.chicagowilderness.org
				 </p>
				 
				 <h3>The Neuman Family Fund</h3>
				 <p>
				 </p>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>