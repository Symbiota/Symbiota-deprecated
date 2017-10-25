Copy code below into the site's home page (index.php) or any other page of interest.
Modify main.css file to customize quick search plugin to your preferences.
Note that this tool is dependent on target page having JQuery and jQuery-ui links already included within head tag (see index_template.php) 
 
<div id="quicksearchdiv">
	<?php
	//---------------------------QUICK SEARCH SETTINGS---------------------------------------
	//Title text that will appear. 
	$searchText = (isset($LANG['QSEARCH_SEARCH'])?$LANG['QSEARCH_SEARCH']:'Search Taxon'); 

	//Text that will appear on search button. 
	$buttonText = (isset($LANG['QSEARCH_SEARCH_BUTTON'])?$LANG['QSEARCH_SEARCH_BUTTON']:'Search');

	//---------------------------DO NOT CHANGE BELOW HERE-----------------------------
	include_once($SERVER_ROOT.'/classes/PluginsManager.php');
	$pluginManager = new PluginsManager();
	echo $pluginManager->createQuickSearch($buttonText,$searchText);
	?>
</div>