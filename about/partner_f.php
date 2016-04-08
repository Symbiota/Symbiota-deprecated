<?php
//error_reporting(E_ALL);
include_once("../config/symbini.php");
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?> Partners: The Field Museum</title>
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
            <h1>Partners: <a href="http://www.fieldmuseum.org/">The Field Museum</a></h1>
			<div style="margin:20px;">
            	 <p>Using collections-based research and self-directed learning through exhibits and education programs, The Field Museum promotes greater public understanding and appreciation of the world in which we live. The Museum's expanding programs on the region's biological diversity help integrate natural riches into everyday life and culture. Regional inventory and population monitoring programs focus on species of conservation concern, or those that serve as sensitive indicators of the health of an ecological community.
				 </p>
				 <p>
				  The Field Museum of Natural History, 
				  1400 S. Lake Shore Drive, 
				  Chicago, IL   60605, 
				  (312) 922-9410, 
				  www.fieldmuseum.org 
				</p>
            </div>
        </div>

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>