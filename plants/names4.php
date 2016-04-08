<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle; ?>About Plants - Names - Common Names</title>
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
						<h1>Common Names</h1>

						<div style="margin:20px;">
							<!-- give example for names for morel in different languages -->
							<p>Common names are nicknames used in a particular region (e.g. American elm, or marsh bellflower). Common names vary between regions and languages.  Because there are no rules for assigning common names, they often cause confusion. Often they are not unique, and the same common name may be applied to very different organisms.  On the other hand, the same organism may be given several common names even within a single, relatively small area such as the Chicago Region. For example, false bugbane and black cohosh both refer to <i>Cimicifuga racemosa</i>.  Beware of relying on common names.  It is always best to use scientific names because they provide a reference framework so we can clearly understand to which organism someone is referring.</p>
						</div>
					</div><!-- end of #content1 -->
					</div><!-- end of #content1wrap -->
		
					<div id="content2">

						<div class="box">
							<h3>Names</h3>
							<ul>
								<li><a href="names.php">Names Main</a></li>
								<li><a href="names5.php">Taxonomy</a></li>
								<li><a href="names2.php">Scientific names</a></li>
								<li><a href="names3.php">Synonyms</a></li>
								<li><strong>Common names</strong></li>
							</ul>
						</div>

						<div class="box">
						<h3>Related Trivia</h3>
						<p>Birds are the first group of organisms with established, standardized or official common names.</p>
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