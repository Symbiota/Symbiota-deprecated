<?php
include_once("config/symbini.php");
header("Content-Type: text/html; charset=".$CHARSET);
?>
<html>
<head>
	<meta http-equiv="X-Frame-Options" content="deny">
	<title><?php echo $DEFAULT_TITLE; ?> Home</title>
	<link href="css/base.css?ver=<?php echo $CSS_VERSION; ?>" type="text/css" rel="stylesheet" />
	<link href="css/main.css<?php echo (isset($CSS_VERSION_LOCAL)?'?ver='.$CSS_VERSION_LOCAL:''); ?>" type="text/css" rel="stylesheet" />
	<script type="text/javascript">
		<?php include_once($SERVER_ROOT.'/config/googleanalytics.php'); ?>
	</script>
</head>
<body>
	<?php
	include($SERVER_ROOT.'/header.php');
	?> 
	<!-- This is inner text! -->
	<div  id="innertext">
		<h1></h1>

		<div id="quicksearchdiv">
			<div style="float:left;">
				<?php
				//---------------------------QUICK SEARCH SETTINGS---------------------------------------
				//Title text that will appear. 
				$searchText = 'Search Taxon'; 
		
				//Text that will appear on search button. 
				$buttonText = 'Search';
		
				//---------------------------DO NOT CHANGE BELOW HERE-----------------------------
				include_once($SERVER_ROOT.'/classes/PluginsManager.php');
				$pluginManager = new PluginsManager();
				$quicksearch = $pluginManager->createQuickSearch($buttonText,$searchText);
				echo $quicksearch;
				?>
			</div>
		</div>
		<div style="padding: 0px 10px;">
			Description and introduction of project
		</div>
	</div>

	<?php
	include($SERVER_ROOT.'/footer.php');
	?> 
</body>
</html>