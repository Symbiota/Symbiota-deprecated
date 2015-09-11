Copy code below into the site's home page (index.php) or any other page of interest.
Modify main.css file to customize quick search plugin to your preferences.
 
<div style="float:right;width:330px;margin:8px;padding:5px 5px 0px 5px;-moz-border-radius:5px;-webkit-border-radius:5px;border:1px solid black;" >
	<div style="float:left;width:320px;">
		<?php
		//---------------------------QUICK SEARCH SETTINGS---------------------------------------
		//Title text that will appear. 
		$searchText = ''; 

		//Text that will appear on search button. 
		$buttonText = 'Search';

		//---------------------------DO NOT CHANGE BELOW HERE-----------------------------
		include_once($serverRoot.'/classes/PluginsManager.php');
		$pluginManager = new PluginsManager();
		$quicksearch = $pluginManager->createQuickSearch($buttonText,$searchText);
		echo $quicksearch;
		?>
	</div>
</div>