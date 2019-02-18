Copy code below into the site's home page (index.php) or any other page of interest.
Modify main.css file to customize quick search plugin to your preferences.
 
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