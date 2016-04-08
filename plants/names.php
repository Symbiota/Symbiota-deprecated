<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>About Plants - Names</title>
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
						<h1>Names</h1>

						<div style="margin:20px;">
							<h1>Names</h1>
							
							<p>What's in a name? It is human nature to name things. We use names to communicate information and assign an identity to people and objects. For plants, fungi, and other organisms there are several kinds of names:</p>
							
							<div class="indexheading"><a href="names5.php">Taxon</a></div>
							<div class="indexdescription"><p>(plural: taxa) is a general term meaning a group or rank, such as a species, a variety, a genus, a family, etc.<a href="names5.php">Learn more</a></p></div>
							
							<div class="indexheading"><a href="names2.php">Scientific names</a></div>
							<div class="indexdescription"><p>are official names that follow rules of taxonomy to uniquely identify a taxon, such as a species or family. Example: <i>Ulmus americana</i> and <i>Ulmaceae</i>.<a href="names2.php">Learn more</a></p></div>
							
							<div class="indexheading"><a href="names3.php">Synonyms</a></div>
							<div class="indexdescription"><p>are different names for the same taxon. Example: <i>Ulmus floridana</i> is a synonym of <i>Ulmus americana</i>.<a href="names3.php">Learn more</a></p></div>
							
							<div class="indexheading"><a href="names4.php">Common names</a></div>
							<div class="indexdescription"><p>are nicknames that vary between regions and languages. They follow no rules and often are not unique. Example: American elm.<a href="names4.php">Learn more</a></p></div>

						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
        
		
					<div id="content2">

						<div class="box">
							<h3>Names</h3>
							<ul>
								<li><strong>Names Main</strong></li>
								<li><a href="names5.php">Taxonomy</a></li>
								<li><a href="names2.php">Scientific names</a></li>
								<li><a href="names3.php">Synonyms</a></li>
								<li><a href="names4.php">Common names</a></li>
							</ul>
						</div>

						<div class="box external">
						<h3>Related Web Sites</h3>
						<ul>
						<li><a href="http://www.bgbm.fu-berlin.de/iapt/nomenclature/code/SaintLouis/0000St.Luistitle.htm">International Code of Botanical Nomenclature</a></li>
						<li><a href="http://www.iczn.org/iczn/index.jsp">International Code of Zoological Nomenclature</a></li>
						</ul>
						</div>

						<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="<?php echo $clientRoot; ?>/disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

					</div><!-- end of #content2 -->
				</div><!-- end of #wrapper1 -->
			</div><!-- end of #bodywrap -->
		</div><!-- end of #innervplantstext -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>