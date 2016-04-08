<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle?>vPlants - Topics - Synonyms</title>
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
        <div  id="innertext">
            <h1>Synonyms</h1>

            <div style="margin:20px;">
            	<p>Synonyms are different scientific names that have been assigned to the same organism. For example, the names <i>Aster azureus</i> and <i>Aster oolentangiensis</i> refer to the same species of aster.  Synonyms can exist when scientists have different opinions about how a specific organism should be defined, or someone names the same organism with a new name because they were unaware of the previous name.</p>
            </div>
        </div>
		
		<div id="content2">

			<div class="box document">
			<h3>....</h3>
			<ul><li>
			....
			</li></ul>
			</div>

			<div class="box external">
			<h3>....</h3>
			<ul>
			<li>
			<!-- link to Index Fungorum -->
			</li>
			</ul>
			</div>

			<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="../disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

		</div><!-- end of #content2 -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>