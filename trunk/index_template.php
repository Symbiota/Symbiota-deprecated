<?php
//error_reporting(E_ALL);

header("Content-Type: text/html; charset=ISO-8859-1");
header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
include_once("util/symbini.php");
?>
<html>
<head>
    <title><?php echo $defaultTitle?> Home</title>
    <link rel="stylesheet" href="css/main.css" type="text/css" />
    <meta name='keywords' content='Arizona,New Mexico,Sonora,Sonoran,Desert,plants,lichens,natural history collections,flora, fauna, checklists,species lists' />
</head>

<body>

	<?php
	$displayLeftMenu = "true";
	include($serverRoot."/util/header.php");
	?> 
        <!-- This is inner text! -->
        <div  id="innertext">
            <h1></h1>

            <div style="margin:20px;">
            	Description and introduction of project
            </div>
        </div>

	<?php
	include($serverRoot."/util/footer.php");
	?> 

	<script type="text/javascript">
		var gaJsHost = (("https:" == document.location.protocol) ? "https://ssl." : "http://www.");
		document.write(unescape("%3Cscript src='" + gaJsHost + "google-analytics.com/ga.js' type='text/javascript'%3E%3C/script%3E"));
	</script>
	<script type="text/javascript">
		try {
			var pageTracker = _gat._getTracker("<?php echo $googleAnalyticsKey; ?>");
			pageTracker._trackPageview();
		} catch(err) {}
	</script>

</body>
</html>