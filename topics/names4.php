<?php
//error_reporting(E_ALL);
include_once('../config/symbini.php');
header("Content-Type: text/html; charset=".$charset);
?>
<html>
<head>
	<title><?php echo $defaultTitle?>vPlants - Topics - Common Names</title>
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
            <h1>Common Names</h1>

            <div style="margin:20px;">
            	<!-- give example for names for morel in different languages -->
				<p>Common names are nicknames used in a particular region (e.g. American elm, or marsh bellflower). Common names vary between regions and languages.  Because there are no rules for assigning common names, they often cause confusion. Often they are not unique, and the same common name may be applied to very different organisms.  On the other hand, the same organism may be given several common names even within a single, relatively small area such as the Chicago Region. For example, false bugbane and black cohosh both refer to <i>Cimicifuga racemosa</i>.  Beware of relying on common names.  It is always best to use scientific names because they provide a reference framework so we can clearly understand to which organism someone is referring.</p>
            </div>
        </div>
		
		<div id="content2">

			<div class="box">
			<h3>....</h3>
			<ul><li>
			....
			</li></ul>
			</div>

			<div class="box">
			<h3>Related Trivia</h3>
			<p>Birds are the first group of organisms with established, standardized or official common names.</p>
			</div>

			<p class="small">Information provided on this page applies to the Chicago Region and may not be relevant or complete for other regions.</p><p class="small noprint"><a href="../disclaimer.php" title="Read Disclaimer.">Disclaimer</a></p>

		</div><!-- end of #content2 -->

	<?php
	include($serverRoot."/footer.php");
	?> 

</body>
</html>